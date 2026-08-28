<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Store;
use App\Models\MarketplaceBooking;
use App\Models\MarketplaceOrder;
use App\Services\Channels\ChannelManager;

/**
 * Tarik & simpan "Pesanan Kilat" (booking) dari Shopee ke DB.
 * Anti-duplikat via booking_sn unik + updateOrCreate.
 *
 * get_booking_list hanya memberi booking_sn + booking_status — order_sn, kurir,
 * nomor paket & item ada di get_booking_detail. Karena itu setelah menarik daftar,
 * job ini melakukan enrichment detail secara batch (booking_sn_list, 50/panggilan).
 */
class SyncMarketplaceBookings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $store;
    public $timeFrom;
    public $timeTo;
    public $fullSync;
    public $bookingSn;

    public function __construct(
        Store $store,
        ?int $timeFrom = null,
        ?int $timeTo = null,
        bool $fullSync = false,
        ?string $bookingSn = null
    )
    {
        $this->store    = $store;
        $this->timeFrom = $timeFrom;
        $this->timeTo   = $timeTo;
        $this->fullSync = $fullSync;
        $this->bookingSn = $bookingSn;
    }

    public function handle(ChannelManager $manager): void
    {
        if (! $this->store->is_active || $this->store->status !== 'active') {
            Log::info("SyncMarketplaceBookings skipped inactive store [{$this->store->id}].");
            return;
        }

        try {
            $driver = $manager->driver($this->store);

            // Webhook booking tidak perlu menjalankan sync rentang waktu.
            // Ambil hanya booking yang berubah agar item/status segera masuk DB
            // tanpa menghabiskan kuota get_booking_list.
            if ($this->bookingSn !== null && $this->bookingSn !== '') {
                if (method_exists($driver, 'getBookingDetail')) {
                    $this->enrichDetails($driver, [$this->bookingSn]);
                }
                return;
            }

            if (! method_exists($driver, 'getBookingList')) {
                return;
            }

            // Shopee membatasi rentang create_time maksimal 15 hari per panggilan.
            $span = 15 * 86400;
            if ($this->fullSync) {
                $end   = time();
                $start = strtotime('-6 months', $end);
            } else {
                $end   = $this->timeTo ?? time();
                $start = $this->timeFrom ?? ($end - (14 * 86400));
            }

            $windows = [];
            if ($end <= $start) {
                $windows[] = [$start, max($start, $end)];
            } else {
                for ($s = $start; $s < $end; $s += $span) {
                    $windows[] = [$s, min($s + $span - 1, $end)];
                }
            }

            $bookingSns = [];
            foreach ($windows as [$wFrom, $wTo]) {
                $cursor = '';
                $guard  = 0;
                do {
                    $res = $driver->getBookingList($this->store, $wFrom, $wTo, 50, $cursor);
                    if (! empty($res['error'])) {
                        Log::error("SyncMarketplaceBookings [{$this->store->id}]: " . ($res['message'] ?? $res['error']));
                        break;
                    }

                    $list = $res['response']['booking_list'] ?? [];
                    foreach ($list as $b) {
                        $this->processBooking($b);
                        if (! empty($b['booking_sn'])) {
                            $bookingSns[] = $b['booking_sn'];
                        }
                    }

                    $more   = $res['response']['more'] ?? $res['response']['has_more'] ?? false;
                    $cursor = (string) ($res['response']['next_cursor'] ?? '');
                } while ($more && $cursor !== '' && ++$guard < 50);
            }

            // Sertakan juga booking tersimpan yang belum punya order_sn (mis. dari webhook)
            // agar ikut di-enrich walau di luar jendela tanggal sync ini.
            $pending = MarketplaceBooking::where('store_id', $this->store->id)
                ->where(fn ($q) => $q->whereNull('order_sn')->orWhere('order_sn', ''))
                ->orderByDesc('id')->limit(100)->pluck('booking_sn')->all();

            // Lengkapi order_sn / kurir / nomor paket / item dari get_booking_detail.
            $this->enrichDetails($driver, array_values(array_unique(array_merge($bookingSns, $pending))));

            // Backfill: tarik order kilat yang belum ada di marketplace_orders supaya
            // muncul di halaman Orders (halaman itu hanya membaca marketplace_orders).
            $this->backfillMissingOrders();

        } catch (\Throwable $e) {
            Log::error("Exception in SyncMarketplaceBookings [{$this->store->id}]: " . $e->getMessage());
        } finally {
            // Status READY_TO_SHIP yang sudah tersimpan tetap harus
            // menormalisasi marketplace_orders walaupun request API terbaru
            // gagal. Sebelumnya exception sebelum akhir proses membuat order
            // lama PROCESSED tidak pernah dipindahkan.
            try {
                $this->normalizeUnarrangedOrders();
            } catch (\Throwable $e) {
                Log::error("Gagal menormalisasi order booking [{$this->store->id}]: " . $e->getMessage());
            }
        }
    }

    protected function processBooking(array $b): void
    {
        $bookingSn = $b['booking_sn'] ?? null;
        if (! $bookingSn) {
            return;
        }

        $attrs = [
            'booking_status' => $b['booking_status'] ?? null,
            'create_time'    => $b['create_time'] ?? null,
            'update_time'    => $b['update_time'] ?? null,
            // Field operasional disimpan di kolom tersendiri; hindari
            // menduplikasi seluruh payload booking dan daftar item di JSON.
            'raw_json'       => array_intersect_key($b, array_flip([
                'booking_sn', 'order_sn', 'booking_status', 'shipping_carrier',
                'tracking_number', 'package_number', 'shipping_document_status',
                'create_time', 'update_time',
            ])),
        ];
        // Jangan menimpa nilai yang sudah terisi dengan null — order_sn/kurir bisa berasal
        // dari get_booking_detail dan tidak selalu ada di get_booking_list.
        if (! empty($b['order_sn']))         $attrs['order_sn'] = $b['order_sn'];
        if (! empty($b['shipping_carrier'])) $attrs['shipping_carrier'] = $b['shipping_carrier'];

        // Upsert atomik mencegah race condition ketika scheduler dan sync manual
        // menerima booking yang sama pada waktu berdekatan.
        MarketplaceBooking::upsert(
            [[
                'store_id'   => $this->store->id,
                'booking_sn' => $bookingSn,
                ...$attrs,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['store_id', 'booking_sn'],
            array_values(array_unique(array_merge(array_keys($attrs), ['updated_at'])))
        );

        if (! empty($b['order_sn'])) {
            $this->linkOrder($bookingSn, $b['order_sn']);
        }
    }

    /** Ambil detail booking secara batch untuk melengkapi order_sn, kurir, paket & item. */
    protected function enrichDetails($driver, array $bookingSns): void
    {
        if (empty($bookingSns) || ! method_exists($driver, 'getBookingDetail')) {
            return;
        }

        foreach (array_chunk($bookingSns, 50) as $chunk) {
            try {
                $res  = $driver->getBookingDetail($this->store, implode(',', $chunk));
                $list = $res['response']['booking_list'] ?? $res['response']['order_list'] ?? [];
                if (empty($list)) {
                    continue;
                }

                $models = MarketplaceBooking::where('store_id', $this->store->id)
                    ->whereIn('booking_sn', $chunk)
                    ->get()
                    ->keyBy('booking_sn');

                foreach ($list as $d) {
                    $normalized = $this->normalizeBookingDetail($d);
                    $sn = $normalized['booking_sn'] ?? (count($chunk) === 1 ? $chunk[0] : null);
                    $m  = $sn ? ($models[$sn] ?? null) : null;
                    if (! $m) {
                        continue;
                    }

                    if (! empty($normalized['order_sn']))         $m->order_sn = $normalized['order_sn'];
                    if (! empty($normalized['shipping_carrier'])) $m->shipping_carrier = $normalized['shipping_carrier'];

                    $pkg = $normalized['package_number'];
                    if (! empty($pkg))                   $m->package_number = $pkg;
                    if (! empty($normalized['tracking_number'])) $m->tracking_number = $normalized['tracking_number'];
                    if (! empty($normalized['shipping_document_status'])) {
                        $m->shipping_document_status = $normalized['shipping_document_status'];
                    }
                    if (! empty($normalized['item_list']))       $m->items = $normalized['item_list'];
                    if (! empty($normalized['booking_status']))  $m->booking_status = $normalized['booking_status']; // Pastikan status terupdate

                    // Jika sudah diproses tapi belum punya resi, coba tarik resinya
                    if (in_array(strtoupper((string) $m->booking_status), ['PROCESSED', 'SHIPPED', 'READY_TO_HANDOVER', 'COMPLETED']) && blank($m->tracking_number) && method_exists($driver, 'getBookingTrackingNumber')) {
                        try {
                            $trk = $driver->getBookingTrackingNumber($this->store, $sn);
                            $trkNum = $trk['response']['tracking_number'] ?? null;
                            if (! empty($trkNum)) {
                                $m->tracking_number = $trkNum;
                            }
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning("Gagal narik resi booking {$sn}: " . $e->getMessage());
                        }
                    }

                    if ($m->isDirty()) {
                        $m->save();
                    }

                    // Propagate booking_status ke order_status di marketplace_orders
                    // agar tab UI otomatis berubah tanpa menunggu webhook.
                    if (! empty($normalized['order_sn']) || ! empty($m->order_sn)) {
                        $realOrderSn = $normalized['order_sn'] ?? $m->order_sn;
                        
                        // Panggil linkOrder untuk membersihkan duplicate order sisa jika get_booking_list
                        // sebelumnya gagal membawa order_sn, tetapi get_booking_detail berhasil.
                        $this->linkOrder($sn, $realOrderSn);

                        $bookingStatusUpper = strtoupper((string) ($normalized['booking_status'] ?? $m->booking_status ?? ''));
                        
                        if (! empty($bookingStatusUpper)) {
                            $orderStatusMap = [
                                'PROCESSED'               => ['order_status' => 'PROCESSED', 'logistics_status' => 'LOGISTICS_READY_TO_SHIP'],
                                'READY_TO_HANDOVER'       => ['order_status' => 'SHIPPED',    'logistics_status' => 'LOGISTICS_PICKUP_DONE'],
                                'SHIPPED'                 => ['order_status' => 'SHIPPED',    'logistics_status' => 'LOGISTICS_PICKUP_DONE'],
                                'COMPLETED'               => ['order_status' => 'COMPLETED',  'logistics_status' => null],
                                'CANCELLED_BEFORE_SHIPPING' => ['order_status' => 'CANCELLED', 'logistics_status' => null],
                            ];
                            
                            if (isset($orderStatusMap[$bookingStatusUpper])) {
                                $targetStatus = $orderStatusMap[$bookingStatusUpper]['order_status'];
                                $targetLogistics = $orderStatusMap[$bookingStatusUpper]['logistics_status'];
                                
                                $orderQuery = MarketplaceOrder::where('store_id', $this->store->id)
                                    ->where('channel_order_id', $realOrderSn);
                                
                                $localOrder = $orderQuery->first();
                                if ($localOrder && $localOrder->order_status !== $targetStatus) {
                                    $updateData = ['order_status' => $targetStatus];
                                    if ($targetLogistics) {
                                        $updateData['logistics_status'] = $targetLogistics;
                                    }
                                    // Jangan rollback: SHIPPED/COMPLETED tidak boleh kembali ke PROCESSED
                                    $noRollback = ['SHIPPED', 'COMPLETED', 'TO_CONFIRM_RECEIVE'];
                                    if (! (in_array($localOrder->order_status, $noRollback) && ! in_array($targetStatus, $noRollback))) {
                                        $localOrder->update($updateData);
                                        Log::info("SyncMarketplaceBookings: Propagated booking_status {$bookingStatusUpper} → order_status {$targetStatus} for order {$realOrderSn}");
                                    }
                                }
                            }
                        }
                    }

                    if (! empty($normalized['order_sn'])) {
                        $this->linkOrder($sn, $normalized['order_sn']);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("SyncMarketplaceBookings enrichDetails [{$this->store->id}]: " . $e->getMessage());
            }
        }
    }

    /**
     * Shopee dapat mengembalikan detail langsung di booking_list atau
     * membungkusnya di order_list. Samakan bentuknya sebelum disimpan agar
     * item dari get_booking_detail tidak hilang pada salah satu bentuk respons.
     */
    protected function normalizeBookingDetail(array $detail): array
    {
        $nestedOrder = [];
        if (isset($detail['order_list']) && is_array($detail['order_list'])) {
            $nestedOrder = is_array($detail['order_list'][0] ?? null)
                ? $detail['order_list'][0]
                : [];
        }

        $package = [];
        foreach ([$detail['package_list'] ?? null, $nestedOrder['package_list'] ?? null] as $packages) {
            if (is_array($packages) && is_array($packages[0] ?? null)) {
                $package = $packages[0];
                break;
            }
        }

        return [
            'booking_sn' => $detail['booking_sn'] ?? $nestedOrder['booking_sn'] ?? null,
            'order_sn' => $detail['order_sn'] ?? $nestedOrder['order_sn'] ?? null,
            'booking_status' => $detail['booking_status'] ?? $nestedOrder['booking_status'] ?? null,
            'shipping_carrier' => $detail['shipping_carrier'] ?? $nestedOrder['shipping_carrier'] ?? null,
            'tracking_number' => $detail['tracking_number'] ?? $nestedOrder['tracking_number'] ?? null,
            'shipping_document_status' => $detail['shipping_document_status']
                ?? $nestedOrder['shipping_document_status']
                ?? null,
            'package_number' => $detail['package_number']
                ?? $nestedOrder['package_number']
                ?? ($package['package_number'] ?? null),
            'item_list' => $detail['item_list'] ?? $nestedOrder['item_list'] ?? [],
        ];
    }

    /**
     * Tarik order untuk booking yang punya order_sn tapi order-nya belum ada
     * di marketplace_orders. Tanpa ini, Pesanan Kilat lama (MATCHED) tidak akan
     * pernah tampil di halaman Orders. Dibatasi 500/run agar hemat kuota API.
     */
    protected function backfillMissingOrders(): void
    {
        // JALUR 1: Booking yang punya order_sn → sync via order_sn (cara lama)
        $orderSns = MarketplaceBooking::where('store_id', $this->store->id)
            ->whereNotNull('order_sn')->where('order_sn', '!=', '')
            ->pluck('order_sn')->unique()->values();

        if ($orderSns->isNotEmpty()) {
            $existing = MarketplaceOrder::where(function ($q) use ($orderSns) {
                    $q->whereIn('channel_order_id', $orderSns)
                      ->orWhereIn('external_order_id', $orderSns);
                })
                ->get(['channel_order_id', 'external_order_id']);

            $known = $existing->pluck('channel_order_id')
                ->merge($existing->pluck('external_order_id'))
                ->filter()->flip();

            $missing = $orderSns->reject(fn ($sn) => $known->has($sn))->take(500)->values()->all();
            if (! empty($missing)) {
                $result = app(\App\Services\MarketplaceSyncService::class)->syncOrdersBySn($this->store, $missing);
                Log::info("SyncMarketplaceBookings backfill (order_sn) [{$this->store->id}]: " . count($missing)
                    . " → {$result['new']} baru, {$result['updated']} update.");

                MarketplaceBooking::where('store_id', $this->store->id)
                    ->whereIn('order_sn', $missing)
                    ->get()
                    ->each(fn ($b) => $this->linkOrder($b->booking_sn, $b->order_sn));
            }
        }

        // JALUR 2: Booking yang order_sn-nya kosong (khas Pesanan Kilat murni Shopee)
        // → gunakan booking_sn itu sendiri sebagai channel_order_id untuk sync
        $pureSns = MarketplaceBooking::where('store_id', $this->store->id)
            ->where(fn ($q) => $q->whereNull('order_sn')->orWhere('order_sn', ''))
            ->pluck('booking_sn')->filter()->unique()->values();

        if ($pureSns->isNotEmpty()) {
            $existingPure = MarketplaceOrder::where('store_id', $this->store->id)
                ->where(function ($q) use ($pureSns) {
                    $q->whereIn('channel_order_id', $pureSns)
                      ->orWhereIn('booking_sn', $pureSns);
                })
                ->pluck('channel_order_id')->flip();

            $missingPure = $pureSns->reject(fn ($sn) => $existingPure->has($sn))->take(300)->values()->all();
            if (! empty($missingPure)) {
                // booking_sn tidak bisa di-query via getOrderDetail (itu hanya untuk order_sn biasa).
                // Solusi: buat order stub langsung dari data booking yang sudah ada di DB.
                $bookingMap = MarketplaceBooking::where('store_id', $this->store->id)
                    ->whereIn('booking_sn', $missingPure)
                    ->get()->keyBy('booking_sn');

                \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF');
                $newCount = 0;
                foreach ($missingPure as $bSn) {
                    $bk = $bookingMap->get($bSn);
                    if (! $bk) continue;

                    $bkStatusUpper = strtoupper((string) $bk->booking_status);
                    $orderStatus   = match ($bkStatusUpper) {
                        'SHIPPED', 'READY_TO_HANDOVER' => 'SHIPPED',
                        'COMPLETED'                    => 'COMPLETED',
                        'PROCESSED'                    => 'PROCESSED',
                        default                        => 'READY_TO_SHIP',
                    };
                    $legacyStatus = match ($orderStatus) {
                        'SHIPPED'   => 'shipped',
                        'COMPLETED' => 'completed',
                        default     => 'packed',
                    };

                    try {
                        $newOrder = MarketplaceOrder::create([
                            'store_id'         => $this->store->id,
                            'channel_order_id' => $bSn,
                            'external_order_id'=> $bSn,
                            'booking_sn'       => $bSn,
                            'order_status'     => $orderStatus,
                            'status'           => $legacyStatus,
                            'shipping_carrier' => $bk->shipping_carrier ?? null,
                            'shipping_awb_no'  => $bk->tracking_number  ?? null,
                            'order_date'       => $bk->create_time ? date('Y-m-d', $bk->create_time) : now()->toDateString(),
                            'ordered_at'       => $bk->create_time ? date('Y-m-d H:i:s', $bk->create_time) : now(),
                            'synced_at'        => now(),
                            'currency'         => 'IDR',
                        ]);

                        if (!empty($bk->items) && is_array($bk->items)) {
                            foreach ($bk->items as $idx => $item) {
                                \App\Models\MarketplaceOrderItem::create([
                                    'marketplace_order_id' => $newOrder->id,
                                    'order_id'             => $newOrder->id,
                                    'external_item_id'     => $item['item_id'] ?? null,
                                    'external_model_id'    => $item['model_id'] ?? null,
                                    'line_no'              => $idx + 1,
                                    'item_name'            => $item['item_name'] ?? '-',
                                    'item_sku'             => $item['item_sku']  ?? null,
                                    'model_sku'            => $item['model_sku'] ?? null,
                                    'variant_name'         => $item['model_name'] ?? null,
                                    'qty'                  => (int) ($item['model_quantity_purchased'] ?? $item['active_qty'] ?? 0),
                                    'price'                => $item['model_original_price'] ?? $item['model_discounted_price'] ?? 0,
                                    'image_url'            => data_get($item, 'image_info.image_url'),
                                    'raw_json'             => null,
                                ]);
                            }
                        }

                        $newCount++;
                    } catch (\Throwable $e) {
                        Log::warning("SyncMarketplaceBookings stub [{$this->store->id}] {$bSn}: " . $e->getMessage());
                    }
                }
                \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON');
                Log::info("SyncMarketplaceBookings stub (booking_sn) [{$this->store->id}]: {$newCount} order baru dari booking murni.");
            }
        }

        // JALUR 3: Propagate booking_status ke order_status untuk semua booking SHIPPED/READY_TO_HANDOVER/COMPLETED/CANCELLED
        // yang ordernya masih PROCESSED di marketplace_orders
        $shippedBookings = MarketplaceBooking::where('store_id', $this->store->id)
            ->whereIn('booking_status', ['SHIPPED', 'READY_TO_HANDOVER', 'COMPLETED', 'CANCELLED', 'CANCELLED_BEFORE_SHIPPING'])
            ->get(['booking_sn', 'order_sn', 'booking_status']);

        foreach ($shippedBookings as $bk) {
            $upperStatus = strtoupper((string) $bk->booking_status);
            $targetStatus = match(true) {
                in_array($upperStatus, ['SHIPPED', 'READY_TO_HANDOVER']) => 'SHIPPED',
                in_array($upperStatus, ['CANCELLED', 'CANCELLED_BEFORE_SHIPPING']) => 'CANCELLED',
                default => 'COMPLETED',
            };

            $order = MarketplaceOrder::where('store_id', $this->store->id)
                ->where(function ($q) use ($bk) {
                    $q->where('channel_order_id', $bk->booking_sn)
                      ->orWhere('booking_sn', $bk->booking_sn);
                    if (! empty($bk->order_sn)) {
                        $q->orWhere('channel_order_id', $bk->order_sn);
                    }
                })
                ->where('order_status', 'PROCESSED')
                ->first();

            if ($order) {
                $order->update([
                    'order_status'    => $targetStatus,
                    'booking_sn'      => $bk->booking_sn,
                ]);
                Log::info("SyncMarketplaceBookings propagate: {$order->channel_order_id} PROCESSED → {$targetStatus} (booking {$bk->booking_sn})");
            }
        }
    }

    /**
     * Sinkronkan order yang sudah punya booking tetapi booking-nya masih
     * READY_TO_SHIP/PENDING/PROCESSED tanpa bukti kirim. Ini mencegah status lama PROCESSED membuat order
     * masuk ke Sedang Dikemas sebelum tombol Atur Pengiriman dijalankan.
     */
    protected function normalizeUnarrangedOrders(): void
    {
        $bookings = MarketplaceBooking::where('store_id', $this->store->id)
            ->where(function ($query) {
                $query->whereNull('booking_status')
                    ->orWhereIn('booking_status', ['PENDING', 'READY_TO_SHIP', 'PROCESSED', '']);
            })
            ->where(function ($query) {
                $query->whereNull('tracking_number')->orWhere('tracking_number', '');
            })
            ->where(function ($query) {
                $query->whereNull('package_number')->orWhere('package_number', '');
            })
            ->where(function ($query) {
                $query->whereNull('shipping_document_status')->orWhere('shipping_document_status', '');
            })
            ->get(['booking_sn', 'order_sn', 'booking_status']);

        foreach ($bookings as $booking) {
            $order = MarketplaceOrder::where('store_id', $this->store->id)
                ->where('order_status', 'PROCESSED')
                ->where(function ($query) use ($booking) {
                    $query->where('booking_sn', $booking->booking_sn)
                        ->orWhere('channel_order_id', $booking->booking_sn);

                    if (! empty($booking->order_sn)) {
                        $query->orWhere('channel_order_id', $booking->order_sn);
                    }
                })
                ->first();

            if (! $order) {
                continue;
            }

            $order->update([
                'order_status' => 'READY_TO_SHIP',
                'status' => 'packed',
                'booking_sn' => $booking->booking_sn,
            ]);

            Log::info("SyncMarketplaceBookings: {$order->channel_order_id} PROCESSED → READY_TO_SHIP karena booking {$booking->booking_sn} belum diatur pengirimannya.");
        }
    }

    /**
     * Tautkan booking_sn ke order lokal DAN bersihkan order "hantu".
     *
     * Alur duplikat: saat order_sn belum diketahui, backfill JALUR 2 membuat order stub
     * dengan channel_order_id = booking_sn. Begitu order_sn muncul, JALUR 1 menarik detail
     * lengkap via syncOrdersBySn (updateOrCreate keyed pada channel_order_id == order_sn)
     * → terbentuk baris order ASLI yang datanya lengkap. Method ini lalu menautkan
     * booking_sn ke order asli itu dan MENGHAPUS stub-nya, sehingga tinggal satu baris.
     *
     * PENTING: kita TIDAK mempromosikan (rename) stub, karena stub hanya berisi data
     * minimal dari booking. Membiarkan JALUR 1 menariknya via getOrderDetail memastikan
     * order di DB selalu berisi detail terkini dari Shopee — tidak ada baris parsial /
     * "sudah update di Shopee tapi belum update di database".
     */
    protected function linkOrder(string $bookingSn, string $orderSn): void
    {
        if ($bookingSn === '' || $orderSn === '') {
            return;
        }

        // Order "asli" yang sudah dikunci pada order_sn sebenarnya.
        $real = MarketplaceOrder::where('store_id', $this->store->id)
            ->where('channel_order_id', $orderSn)
            ->first();

        // Belum ada order asli → jangan sentuh stub. Backfill JALUR 1 yang akan
        // menariknya lewat getOrderDetail (data lengkap), lalu memanggil linkOrder lagi.
        if (! $real) {
            return;
        }

        if (blank($real->booking_sn)) {
            $real->update(['booking_sn' => $bookingSn]);
        }

        // Order asli sudah lengkap → stub yang masih memakai booking_sn adalah
        // duplikat hantu, hapus (beserta itemnya agar tidak jadi orphan).
        if ($bookingSn !== $orderSn) {
            $stub = MarketplaceOrder::where('store_id', $this->store->id)
                ->where('channel_order_id', $bookingSn)
                ->first();
            if ($stub && $stub->id !== $real->id) {
                $stub->items()->delete();
                $stub->delete();
                Log::info("SyncMarketplaceBookings: hapus stub duplikat {$bookingSn} (order asli {$orderSn} sudah ada).");
            }
        }
    }
}
