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

    public function __construct(Store $store, ?int $createTimeFrom = null, ?int $createTimeTo = null)
    {
        $this->store = $store;
        $this->createTimeFrom = $createTimeFrom;
        $this->createTimeTo = $createTimeTo;
    }

    public function handle(ChannelManager $manager): void
    {
        try {
            $driver = $manager->driver($this->store);
            if (!method_exists($driver, 'getReturnList')) {
                return;
            }

            // Sync 15 hari terakhir jika tidak ditentukan
            $tsTo = $this->createTimeTo ?? time();
            $tsFrom = $this->createTimeFrom ?? ($tsTo - (14 * 86400));

            $pageNo = 0;
            $pageSize = 40;
            $hasMore = true;

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
                // Safety net
                if ($pageNo > 1000) break;
            }

            // --- Sync Active Old Returns ---
            // Cari retur di DB lokal yang statusnya masih belum selesai, tapi umurnya lebih dari 15 hari
            // Kita fetch ulang manual per return_sn untuk update status terakhirnya
            $activeOldReturns = MarketplaceReturn::where('store_id', $this->store->id)
                ->whereNotIn('status', ['COMPLETED', 'CLOSED', 'CANCELLED', 'REFUND_PAID'])
                ->where('create_time', '<', $tsFrom)
                ->get();

            if (method_exists($driver, 'getReturnDetail')) {
                foreach ($activeOldReturns as $oldReturn) {
                    try {
                        $detailResult = $driver->getReturnDetail($this->store, $oldReturn->return_sn);
                        if (!isset($detailResult['error']) && isset($detailResult['response'])) {
                            $this->processReturn($detailResult['response']);
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

    protected function processReturn(array $data)
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

        if (isset($data['item']) && is_array($data['item'])) {
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

                $retItem = MarketplaceReturnItem::updateOrCreate(
                    [
                        'marketplace_return_id' => $returnObj->id,
                        'item_sku' => $itm['item_sku'] ?? null,
                        'variation_sku' => $itm['variation_sku'] ?? null,
                        'item_name' => $itm['item_name'] ?? null,
                    ],
                    [
                        'item_id' => $internalItemId,
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
