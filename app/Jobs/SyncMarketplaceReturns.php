<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Store;
use App\Models\MarketplaceReturn;
use App\Models\MarketplaceReturnItem;
use App\Models\Item;
use App\Services\Channels\ChannelManager;
use Illuminate\Support\Facades\Log;

class SyncMarketplaceReturns implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $store;
    public $createTimeFrom;
    public $createTimeTo;
    public $fullSync;

    public function __construct(Store $store, ?int $createTimeFrom = null, ?int $createTimeTo = null, bool $fullSync = false)
    {
        $this->store = $store;
        $this->createTimeFrom = $createTimeFrom;
        $this->createTimeTo = $createTimeTo;
        $this->fullSync = $fullSync;
    }

    public function handle(ChannelManager $manager): void
    {
        try {
            $driver = $manager->driver($this->store);
            if (!method_exists($driver, 'getReturnList')) {
                return;
            }

            // Shopee membatasi rentang create_time maksimal 15 hari per panggilan.
            // Untuk fullSync kita pecah ~12 bulan ke belakang menjadi jendela 15 hari,
            // sehingga seluruh riwayat masuk DB tanpa terbentur batas rentang tanggal.
            // Karena penyimpanan pakai updateOrCreate(return_sn), data TIDAK terduplikat.
            $span = 15 * 86400;
            if ($this->fullSync) {
                $end   = time();
                $start = strtotime('-12 months', $end);
            } else {
                $end   = $this->createTimeTo ?? time();
                $start = $this->createTimeFrom ?? ($end - (14 * 86400));
            }

            // Selalu pecah rentang menjadi jendela <=15 hari — bukan hanya saat fullSync.
            // Ini mencegah error batas rentang Shopee saat user memilih rentang lebar
            // di tombol Refresh (mis. 30/90 hari). Karena upsert(return_sn), tidak duplikat.
            $windows = [];
            if ($end <= $start) {
                $windows[] = [$start, max($start, $end)];
            } else {
                for ($s = $start; $s < $end; $s += $span) {
                    $windows[] = [$s, min($s + $span - 1, $end)];
                }
            }

            $syncFloor = $windows[0][0] ?? null; // batas bawah waktu yang sudah dicakup

            foreach ($windows as [$tsFrom, $tsTo]) {
                $pageNo   = 0;
                $pageSize = 40;
                $hasMore  = true;
                $guard    = 0;

                while ($hasMore) {
                    $result = $driver->getReturnList($this->store, $pageNo, $pageSize, $tsFrom, $tsTo);

                    if (isset($result['error']) && !empty($result['error'])) {
                        Log::error("SyncMarketplaceReturns Error for Store {$this->store->id}: " . ($result['message'] ?? $result['error']));
                        break;
                    }

                    $returns = $result['response']['return'] ?? [];
                    $hasMore = $result['response']['more'] ?? false;

                    foreach ($returns as $r) {
                        $this->processReturn($r);
                    }

                    $pageNo += $pageSize;
                    if (++$guard > 50) break; // safety net per jendela
                }
            }

            // --- Sync Active Old Returns ---
            // Cari retur di DB lokal yang statusnya masih belum selesai, tapi umurnya lebih tua
            // dari batas bawah sync. Kita fetch ulang per return_sn untuk update status terakhirnya.
            $query = MarketplaceReturn::where('store_id', $this->store->id)
                ->whereNotIn('status', ['COMPLETED', 'CLOSED', 'CANCELLED', 'REFUND_PAID']);

            if ($syncFloor !== null) {
                $query->where('create_time', '<', $syncFloor);
            }
            
            $activeOldReturns = $query->get();

            if (method_exists($driver, 'getReturnDetail')) {
                foreach ($activeOldReturns as $oldReturn) {
                    try {
                        $detailResult = $driver->getReturnDetail($this->store, $oldReturn->return_sn);
                        if (!isset($detailResult['error']) && isset($detailResult['response'])) {
                            // Hanya perbarui header (status dsb). Item TIDAK di-resync dari
                            // payload detail karena bentuk field item-nya berbeda dari list
                            // (model_sku/model_name/amount) — kalau dipaksa bisa membuat
                            // baris item duplikat ber-SKU null. Item sudah tersimpan dari sync list.
                            $this->processReturn($detailResult['response'], false);
                        }
                    } catch (\Exception $e) {
                        Log::warning("Failed to fetch detail for old return {$oldReturn->return_sn}: " . $e->getMessage());
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error("Exception in SyncMarketplaceReturns for Store {$this->store->id}: " . $e->getMessage());
        }
    }

    protected function processReturn(array $data, bool $syncItems = true)
    {
        $returnSn = $data['return_sn'] ?? null;
        if (!$returnSn) return;

        $returnObj = MarketplaceReturn::updateOrCreate(
            [
                'store_id' => $this->store->id,
                'return_sn' => $returnSn,
            ],
            [
                'order_sn' => $data['order_sn'] ?? '',
                'status' => $data['status'] ?? null,
                'reason' => $data['reason'] ?? null,
                'reason_text_code' => $data['reason_text_code'] ?? null,
                'return_solution' => $data['return_solution'] ?? null,
                'amount_before_discount' => $data['amount_before_discount'] ?? 0,
                'needs_logistics' => $data['needs_logistics'] ?? false,
                'tracking_number' => $data['tracking_number'] ?? null,
                'create_time' => $data['create_time'] ?? null,
                'update_time' => $data['update_time'] ?? null,
            ]
        );

        if ($syncItems && isset($data['item']) && is_array($data['item'])) {
            // Kita bisa sinkronisasi ulang item-itemnya
            $existingItemIds = [];
            foreach ($data['item'] as $itm) {
                $sku = $itm['item_sku'] ?? $itm['variation_sku'] ?? null;
                $internalItemId = null;
                if ($sku) {
                    $internalItem = Item::where('code', $sku)->first();
                    if ($internalItem) {
                        $internalItemId = $internalItem->id;
                    }
                }

                // Kunci upsert sejajar dengan unique index DB
                // (marketplace_return_id, item_sku, variation_sku) — item_name dipindah
                // ke nilai agar perubahan nama produk tidak memunculkan baris duplikat.
                $retItem = MarketplaceReturnItem::updateOrCreate(
                    [
                        'marketplace_return_id' => $returnObj->id,
                        'item_sku' => $itm['item_sku'] ?? null,
                        'variation_sku' => $itm['variation_sku'] ?? null,
                    ],
                    [
                        'item_id' => $internalItemId,
                        'item_name' => $itm['item_name'] ?? null,
                        'variation_name' => $itm['variation_name'] ?? null,
                        'return_item_quantity' => $itm['return_item_quantity'] ?? 1,
                        'images' => isset($itm['images']) && is_array($itm['images']) ? $itm['images'] : null,
                    ]
                );
                $existingItemIds[] = $retItem->id;
            }
            
            // Hapus item lama yang tidak ada di payload terbaru
            if (!empty($existingItemIds)) {
                MarketplaceReturnItem::where('marketplace_return_id', $returnObj->id)
                    ->whereNotIn('id', $existingItemIds)
                    ->delete();
            }
        }
    }
}
