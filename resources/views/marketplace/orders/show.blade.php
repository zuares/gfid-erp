@extends('layouts.app')

@section('title', 'Marketplace Order ' . ($order->channel_order_id ?: $order->external_order_id))

@push('head')
<style>
.od-wrap{max-width:1040px;margin-inline:auto;padding:.7rem .75rem 3rem}
.od-topbar{position:sticky;top:0;z-index:250;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;padding:.55rem .75rem;background:var(--card,#fff);border-bottom:1px solid rgba(148,163,184,.18)}
.od-code{font-weight:900;font-size:1rem;color:#111827}
.od-spacer{flex:1}
.od-btn,.od-pill{display:inline-flex;align-items:center;justify-content:center;gap:.35rem;border-radius:7px;border:1px solid rgba(148,163,184,.3);background:transparent;color:#475569;text-decoration:none;font-size:.76rem;padding:.28rem .6rem;min-height:34px}
.od-btn{font-weight:800}
.od-btn:hover{background:rgba(148,163,184,.09);color:#111827;text-decoration:none}
.od-primary{background:#334155!important;border-color:#334155!important;color:#fff!important}
.od-status{font-weight:850;color:#334155;background:rgba(148,163,184,.08)}
.od-status.completed,.od-status.shipped,.od-status.processed{color:#166534;background:rgba(22,101,52,.08);border-color:rgba(22,101,52,.2)}
.od-status.cancelled{color:#991b1b;background:rgba(153,27,27,.08);border-color:rgba(153,27,27,.2)}
.od-flow{display:flex;align-items:center;gap:.35rem;flex-wrap:wrap;margin:.6rem 0;padding:.45rem .55rem;border:1px solid rgba(148,163,184,.18);border-radius:8px;background:var(--card,#fff)}
.od-step{border:1px solid rgba(148,163,184,.25);border-radius:7px;padding:.18rem .5rem;font-size:.72rem;font-weight:800;color:#64748b}
.od-step.done{background:rgba(148,163,184,.08);color:#334155}
.od-step.active{background:#334155;border-color:#334155;color:#fff}
.od-step.cancel{background:#991b1b;border-color:#991b1b;color:#fff}
.od-sep{color:#cbd5e1;font-size:.72rem}
.od-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.55rem;margin-bottom:.65rem}
.od-grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.55rem;margin-bottom:.65rem}
.od-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:8px;overflow:hidden;margin-bottom:.65rem}
.od-kpi{padding:.65rem .75rem}
.od-label{font-size:.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.02em}
.od-value{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:1.05rem;font-weight:900;color:#111827;margin-top:.12rem}
.od-value-text{font-size:1rem;font-weight:800;color:#111827;margin-top:.12rem}
.od-muted{color:#64748b;font-size:.8rem}
.od-head{display:flex;align-items:center;gap:.55rem;justify-content:space-between;padding:.7rem .85rem;border-bottom:1px solid rgba(148,163,184,.12)}
.od-title{font-weight:900;color:#334155}
.od-body{padding:.75rem .85rem}
.od-list{display:grid;gap:.45rem}
.od-table-wrap{overflow:auto;border-radius:8px}
.od-table{width:100%;border-collapse:collapse}
.od-table th,.od-table td{padding:.55rem .65rem;border-bottom:1px solid rgba(148,163,184,.12);vertical-align:middle}
.od-table th{text-align:left;font-size:.72rem;color:#64748b;font-weight:900;text-transform:uppercase;letter-spacing:.02em;background:rgba(148,163,184,.04)}
.od-table td{font-size:.86rem;color:#334155}
.od-code-cell{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827}
.od-name{color:#64748b;font-size:.8rem;margin-top:.08rem}
.od-r{text-align:right}
.od-c{text-align:center}
.od-total td{font-weight:900;color:#111827;background:rgba(148,163,184,.04)}
.od-fee-tab.active{background:#334155!important;border-color:#334155!important;color:#fff!important}
.od-fee-panel{padding-top:.25rem}
@media(max-width:860px){
  .od-wrap{padding:.5rem .5rem 3.5rem}
  .od-topbar{padding:.5rem}
  .od-code{flex:1;min-width:150px;font-size:1.02rem}
  .od-grid,.od-grid-2{grid-template-columns:repeat(2,minmax(0,1fr));gap:.45rem}
  .od-kpi{padding:.58rem .62rem}
  .od-value,.od-value-text{font-size:1.08rem}
  .od-head{padding:.65rem .7rem}
  .od-body{padding:.65rem .7rem}
}
</style>
@endpush

@php
    $raw = $order->raw_json ?? [];
    if (is_string($raw)) {
        $raw = json_decode($raw, true) ?? [];
    }
    $liveData = $raw['order_list'][0] ?? $raw['response']['order_list'][0] ?? (isset($raw['order_sn']) ? $raw : []);
    $pkg = $liveData['package_list'][0] ?? [];

    $normalizeDateTime = function ($value) {
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->copy()->timezone(config('app.timezone'));
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $timestamp = (int) $value;
            if ($timestamp > 9999999999) {
                $timestamp = (int) floor($timestamp / 1000);
            }

            return \Carbon\Carbon::createFromTimestamp($timestamp, config('app.timezone'));
        }

        try {
            return \Carbon\Carbon::parse($value)->timezone(config('app.timezone'));
        } catch (\Throwable $e) {
            return null;
        }
    };

    $firstDateTime = function (...$values) use ($normalizeDateTime) {
        foreach ($values as $value) {
            $dt = $normalizeDateTime($value);
            if ($dt) {
                return $dt;
            }
        }

        return null;
    };

    $cleanAwb = function ($value) {
        $awb = trim((string) $value);
        if ($awb === '') {
            return null;
        }

        // OFG adalah nomor paket internal Shopee, bukan resi kurir.
        if (preg_match('/^OFG/i', $awb)) {
            return null;
        }

        return $awb;
    };
    
    // Fallbacks
    $buyerName = $liveData['recipient_address']['name'] ?? $order->buyer_name ?? '—';
    $buyerPhone = $liveData['recipient_address']['phone'] ?? $order->buyer_phone ?? '—';
    $buyerAddress = $liveData['recipient_address']['full_address'] ?? $order->shipping_address ?? '—';
    $zipcode = $liveData['recipient_address']['zipcode'] ?? $order->shipping_postal_code ?? '';
    
    $carrier = $liveData['shipping_carrier'] ?? $pkg['shipping_carrier'] ?? $order->shipping_carrier ?? $order->shipping_courier_code ?? '—';
    $awbCandidates = [
        $pkg['tracking_number'] ?? null,
        $liveData['tracking_no'] ?? null,
        $liveData['shipping_document_info']['tracking_number'] ?? null,
        $order->shipping_awb_no ?? null,
        $pkg['package_number'] ?? null,
        $liveData['package_list'][0]['package_number'] ?? null,
    ];
    $awb = null;
    foreach ($awbCandidates as $candidate) {
        $candidate = $cleanAwb($candidate);
        if (!empty($candidate)) {
            $awb = $candidate;
            break;
        }
    }
    $internalPackageNo = null;
    $packageNoCandidate = $pkg['package_number'] ?? $liveData['package_list'][0]['package_number'] ?? null;
    if (!empty($packageNoCandidate) && preg_match('/^OFG/i', (string) $packageNoCandidate)) {
        $internalPackageNo = $packageNoCandidate;
    }
    
    $payMethod = $liveData['payment_method'] ?? $order->payment_method ?? '—';
    $statusText = strtolower($order->order_status ?: $order->status);
    
    $statusMap = [
        'unpaid' => 1,
        'ready_to_ship' => 2,
        'processed' => 2,
        'shipped' => 3,
        'completed' => 4,
        'cancelled' => -1
    ];
    $step = $statusMap[$statusText] ?? 1;
    $isCancel = $step === -1;
    $trackableStatuses = ['ready_to_ship', 'processed', 'shipped', 'completed', 'to_confirm_receive', 'ready_to_handover'];
    $canTrackOrder = !$isCancel && $statusText !== 'unpaid' && in_array($statusText, $trackableStatuses, true);

    $statusLabel = strtoupper(str_replace('_', ' ', $statusText));
@endphp

@section('content')

<div class="od-topbar">
    <a href="{{ route('marketplace.orders') }}" class="od-btn">Kembali</a>
    <span class="od-code">{{ $order->channel_order_id ?: $order->external_order_id }}</span>
    <span class="od-pill od-status {{ $statusText }}">{{ $statusLabel }}</span>
    <span class="od-spacer"></span>
    <span class="od-pill">Toko <b>{{ $order->store->name ?? '-' }}</b></span>
    <a href="{{ route('sales.shipments.create', ['marketplace_order_id' => $order->id]) }}" class="od-btn od-primary">Buat Shipment Internal</a>
</div>

@php
    $testingToolsEnabled = app()->environment(['local', 'testing']) || (auth()->user()?->role === 'owner');
@endphp

<div class="od-wrap">
    @if($testingToolsEnabled)
        <div class="od-card">
                <div class="od-head">
                    <div class="od-title">Testing Settlement</div>
                    <div class="od-muted">Owner/dev only</div>
                </div>
                <form method="POST" action="{{ url('/marketplace/orders/' . $order->id . '/test-settlement-fields') }}" class="od-body">
                    @csrf
                    <div class="od-grid" style="grid-template-columns:repeat(3,minmax(0,1fr)); margin-bottom:.5rem;">
                        <label style="display:grid; gap:.25rem;">
                            <span class="od-muted" style="font-size:.72rem; font-weight:800; text-transform:uppercase;">order_ams_commission_fee</span>
                        <input type="number" min="0" step="1" name="order_ams_commission_fee" value="{{ (int) round((float)($order->settlement?->activity_fee ?? data_get($liveData, 'income_details.order_ams_commission_fee') ?? data_get($liveData, 'income_details.ams_commission_fee') ?? 0)) }}" class="form-control form-control-sm">
                        </label>
                        <label style="display:grid; gap:.25rem;">
                            <span class="od-muted" style="font-size:.72rem; font-weight:800; text-transform:uppercase;">voucher_from_shopee</span>
                        <input type="number" min="0" step="1" name="voucher_from_shopee" value="{{ (int) round((float)(data_get($liveData, 'income_details.voucher_from_shopee') ?? data_get($liveData, 'income_details.voucher_from_platform') ?? data_get($liveData, 'income_details.platform_voucher') ?? 0)) }}" class="form-control form-control-sm">
                        </label>
                        <label style="display:grid; gap:.25rem;">
                            <span class="od-muted" style="font-size:.72rem; font-weight:800; text-transform:uppercase;">voucher_from_seller</span>
                        <input type="number" min="0" step="1" name="voucher_from_seller" value="{{ (int) round((float)($order->settlement?->seller_voucher ?? data_get($liveData, 'income_details.voucher_from_seller') ?? data_get($liveData, 'income_details.seller_voucher_rebate') ?? 0)) }}" class="form-control form-control-sm">
                        </label>
                    </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="submit" class="od-btn od-primary">Simpan Data Testing</button>
                    <span class="od-muted" style="font-size:.78rem;">Nilai ini disimpan ke settlement raw_json supaya halaman penghasilan ikut kebaca.</span>
                </div>
            </form>
        </div>
    @endif

    <div class="od-flow">
        <span class="od-step {{ $step >= 1 || $isCancel ? 'done' : 'active' }} {{ $isCancel ? 'cancel' : '' }}">Dibuat</span><span class="od-sep">-&gt;</span>
        <span class="od-step {{ $step >= 2 || $isCancel ? 'done' : ($step == 1 && !$isCancel ? 'active' : '') }} {{ $isCancel ? 'cancel' : '' }}">Dibayar</span><span class="od-sep">-&gt;</span>
        <span class="od-step {{ $step >= 3 || $isCancel ? 'done' : ($step == 2 && !$isCancel ? 'active' : '') }} {{ $isCancel ? 'cancel' : '' }}">Dikirim</span><span class="od-sep">-&gt;</span>
        <span class="od-step {{ $step >= 4 ? 'active' : ($isCancel ? 'cancel' : '') }}">{{ $isCancel ? 'Dibatalkan' : 'Selesai' }}</span>
    </div>

    <div class="od-grid">
        <div class="od-card od-kpi">
            <div class="od-label">Metode Bayar</div>
            <div class="od-value-text">{{ $payMethod }}</div>
        </div>
        <div class="od-card od-kpi">
            <div class="od-label">Kurir</div>
            <div class="od-value-text">{{ $carrier }}</div>
        </div>
        <div class="od-card od-kpi">
            <div class="od-label">No. Resi</div>
            <div class="od-value" style="font-size:1rem">{{ $awb ?: ($canTrackOrder ? 'Sedang disinkronkan' : 'Belum ada') }}</div>
            @if(!$awb && !empty($internalPackageNo))
                <div class="od-muted" style="margin-top:.28rem; font-size:.68rem;">
                    Nomor paket internal terdeteksi, resi kurir sedang dicari dari API pengiriman.
                </div>
            @endif
            @if($canTrackOrder)
                <div style="margin-top:.4rem">
                    <a href="#" class="od-btn" style="padding:.1rem .4rem; min-height:24px; font-size:.7rem" onclick="trackOrder({{ $order->store_id }}, '{{ $order->channel_order_id }}', event)">Lacak</a>
                </div>
            @endif
        </div>
        <div class="od-card od-kpi">
            <div class="od-label">Total Pembayaran</div>
            <div class="od-value">Rp{{ number_format($liveData['total_amount'] ?? $order->total_paid_customer ?? $order->total_amount ?? 0, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="od-grid-2">
        <div class="od-card">
            <div class="od-head">
                <div class="od-title">Alamat Pengiriman</div>
            </div>
            <div class="od-body">
                <div style="font-weight:900; color:#111827">{{ $buyerName }}</div>
                <div class="od-muted" style="margin-top:.2rem">{{ $buyerPhone }}</div>
                <div class="od-muted" style="margin-top:.4rem; line-height:1.4">
                    {{ $buyerAddress }}
                    @if($zipcode) <br>{{ $zipcode }} @endif
                </div>
                @if($order->remarks)
                    <div style="margin-top:.8rem; padding:.5rem; background:rgba(245,158,11,.08); border:1px solid rgba(245,158,11,.25); border-radius:6px; font-size:.8rem; color:#92400e">
                        <strong>Catatan:</strong> {{ $order->remarks }}
                    </div>
                @endif
            </div>
        </div>
        
        <div style="display:grid; gap:.65rem;">
            <div class="od-card">
                <div class="od-head">
                    <div class="od-title">Waktu Transaksi</div>
                </div>
                <div class="od-body od-list">
                    @php
                        $createTime = $firstDateTime(
                            $order->ordered_at,
                            $order->order_date,
                            $liveData['create_time'] ?? null,
                            $liveData['payment_info']['create_time'] ?? null
                        );
                        $payTime = $firstDateTime(
                            $order->payment_date,
                            $liveData['pay_time'] ?? null,
                            $liveData['paid_time'] ?? null,
                            $liveData['payment_info']['pay_time'] ?? null
                        );
                        $shipTime = $firstDateTime(
                            $order->shipping_arranged_at,
                            $liveData['ship_time'] ?? null,
                            $liveData['arrange_ship_time'] ?? null,
                            $liveData['shipping_document_info']['create_time'] ?? null
                        );
                        $completeTime = $firstDateTime(
                            $order->completed_at,
                            $order->cancelled_at,
                            $liveData['complete_time'] ?? null,
                            $liveData['success_time'] ?? null,
                            $liveData['finish_time'] ?? null,
                            $liveData['cancel_time'] ?? null
                        );
                    @endphp
                    <div style="display:flex; justify-content:space-between">
                        <span class="od-muted">Dipesan</span>
                        <span class="od-code-cell" style="font-size:.85rem">{{ $createTime ? id_datetime($createTime) : '—' }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between">
                        <span class="od-muted">Dibayar</span>
                        <span class="od-code-cell" style="font-size:.85rem">{{ $payTime ? id_datetime($payTime) : '—' }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between">
                        <span class="od-muted">Dikirim</span>
                        <span class="od-code-cell" style="font-size:.85rem">{{ $shipTime ? id_datetime($shipTime) : '—' }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between">
                        <span class="od-muted">Selesai / Batal</span>
                        <span class="od-code-cell" style="font-size:.85rem">{{ $completeTime ? id_datetime($completeTime) : '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="od-card">
                <div class="od-head">
                    <div class="od-title">Rincian Promosi</div>
                </div>
                <div class="od-body od-list">
                    @php
                        $promoVoucherPlatform = (float)($inc['voucher_from_shopee'] ?? $inc['voucher_from_platform'] ?? $inc['platform_voucher'] ?? 0);
                        $promoVoucherToko = (float)($settlement->seller_voucher ?? $inc['voucher_from_seller'] ?? $inc['seller_voucher_rebate'] ?? 0);
                        $promoKoinShopee = (float)($inc['coin'] ?? 0);
                        $promoSubtotal = (float)($inc['order_discounted_price'] ?? $order->subtotal_items ?? 0);
                        if ($promoSubtotal <= 0 && isset($liveData['item_list']) && is_array($liveData['item_list'])) {
                            foreach ($liveData['item_list'] as $it) {
                                $price = (float)($it['model_discounted_price'] ?? $it['discounted_price'] ?? $it['model_original_price'] ?? $it['selling_price'] ?? 0);
                                $qty = (float)($it['model_quantity_purchased'] ?? $it['quantity'] ?? 1);
                                $promoSubtotal += $price * $qty;
                            }
                        }
                        if ($promoSubtotal <= 0 && $order->items && $order->items->count() > 0) {
                            foreach ($order->items as $line) {
                                $lineSubtotal = (float)($line->line_gross_amount ?? 0);
                                if ($lineSubtotal <= 0) {
                                    $price = (float)($line->price ?? $line->price_original ?? 0);
                                    $qty = (float)($line->qty ?? 1);
                                    $lineSubtotal = $price * $qty;
                                }
                                $promoSubtotal += $lineSubtotal;
                            }
                        }
                        $promoBuyerPaid = (float)($inc['buyer_total_amount'] ?? $inc['buyer_paid_amount'] ?? ($order->total_paid_customer > 0
                            ? $order->total_paid_customer
                            : ($liveData['total_amount'] ?? $order->total_amount ?? 0)));
                        $promoOngkir = (float)($inc['buyer_paid_shipping_fee'] ?? $order->shipping_fee_customer ?? 0);
                        $promoShippingRebate = (float)($inc['shopee_shipping_rebate'] ?? 0);
                        $promoEstimasiOngkir = (float)($liveData['estimated_shipping_fee'] ?? $inc['estimated_shipping_fee'] ?? $liveData['actual_shipping_fee'] ?? $order->shipping_fee_customer ?? 0);
                        if ($promoOngkir == 0 && $promoEstimasiOngkir > $promoShippingRebate) {
                            $promoOngkir = $promoEstimasiOngkir - $promoShippingRebate;
                        }
                        $promoBiayaLayanan = (float)($inc['buyer_transaction_fee'] ?? max($promoBuyerPaid - $promoSubtotal - $promoOngkir - $promoVoucherPlatform - $promoVoucherToko - $promoKoinShopee, 0));
                        $promoTotal = $promoVoucherPlatform + $promoVoucherToko + $promoKoinShopee;
                    @endphp
                    <div style="display:flex; justify-content:space-between">
                        <span class="od-muted">Buyer Paid</span>
                        <span class="od-code-cell" style="font-size:.85rem; font-weight:900; color:#111827">Rp{{ number_format($promoBuyerPaid, 0, ',', '.') }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between">
                        <span class="od-muted">Voucher Platform</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#991b1b">-Rp{{ number_format($promoVoucherPlatform, 0, ',', '.') }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between">
                        <span class="od-muted">Voucher Toko</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#991b1b">-Rp{{ number_format($promoVoucherToko, 0, ',', '.') }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between">
                        <span class="od-muted">Koin Shopee</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#991b1b">-Rp{{ number_format($promoKoinShopee, 0, ',', '.') }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between">
                        <span class="od-muted">Ongkos Kirim Dibayar</span>
                        <span class="od-code-cell" style="font-size:.85rem">Rp{{ number_format($promoOngkir, 0, ',', '.') }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between">
                        <span class="od-muted">Biaya Layanan</span>
                        <span class="od-code-cell" style="font-size:.85rem">Rp{{ number_format($promoBiayaLayanan, 0, ',', '.') }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding-top:.35rem; margin-top:.1rem; border-top:1px dashed rgba(148,163,184,.3)">
                        <span class="od-muted">Total Promo Konsumen</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#b91c1c">-Rp{{ number_format($promoTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="od-card">
        <div class="od-head">
            <div class="od-title">Rincian Produk</div>
            <span class="od-pill">{{ count($liveData['item_list'] ?? $order->items ?? []) }} Item</span>
        </div>
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Produk</th>
                        <th class="od-c">Harga Satuan</th>
                        <th class="od-c">Qty</th>
                        <th class="od-r">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $items = $liveData['item_list'] ?? [];
                        $fallbackItems = $order->items;
                        $subtotalItems = 0;
                    @endphp
                    
                    @if(!empty($items))
                        @foreach($items as $item)
                        @php 
                            $price = $item['model_discounted_price'] ?? $item['model_original_price'] ?? 0;
                            $qty = $item['model_quantity_purchased'] ?? 1;
                            $subtotalItems += $price * $qty;
                        @endphp
                        <tr>
                            <td class="od-code-cell">{{ $item['model_sku'] ?? $item['item_sku'] ?? '-' }}</td>
                            <td>
                                <div style="font-weight:700; color:#111827">{{ $item['item_name'] ?? 'Unknown Item' }}</div>
                                <div class="od-name">Var: {{ $item['model_name'] ?? '-' }}</div>
                            </td>
                            <td class="od-c">{{ number_format($price, 0, ',', '.') }}</td>
                            <td class="od-c od-code-cell">{{ $qty }}</td>
                            <td class="od-r od-code-cell">{{ number_format($price * $qty, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    @elseif($fallbackItems && $fallbackItems->count() > 0)
                        @foreach($fallbackItems as $line)
                        @php 
                            $price = $line->price ?? $line->price_original ?? 0;
                            $qty = (int) $line->qty;
                            $subtotal = $price * $qty;
                            $subtotalItems += $line->line_gross_amount > 0 ? $line->line_gross_amount : $subtotal;
                        @endphp
                        <tr>
                            <td class="od-code-cell">{{ $line->model_sku ?? $line->item_sku ?? $line->external_sku ?? '-' }}</td>
                            <td>
                                <div style="font-weight:700; color:#111827">{{ $line->item_name ?? $line->item_name_snapshot ?? '-' }}</div>
                                @if($line->variant_name || $line->variant_snapshot)
                                <div class="od-name">Var: {{ $line->variant_name ?? $line->variant_snapshot }}</div>
                                @endif
                            </td>
                            <td class="od-c">{{ number_format($price, 0, ',', '.') }}</td>
                            <td class="od-c od-code-cell">{{ $qty }}</td>
                            <td class="od-r od-code-cell">{{ number_format($line->line_gross_amount > 0 ? $line->line_gross_amount : $subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5" class="od-c" style="padding:2rem; color:#94a3b8">Tidak ada data produk</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    @php
        $settlement = $order->settlement;
        $settlementIncome = is_array($settlement?->raw_json ?? null) ? $settlement->raw_json : [];
        $inc = array_replace($liveData['income_details'] ?? [], $settlementIncome);
        
        // Seller Income Data
        // Subtotal = Harga Produk Setelah Diskon (dari loop item_list atau fallback database)
        $subtotal = (float)($inc['order_discounted_price'] ?? $order->subtotal_items ?? ($subtotalItems > 0 ? $subtotalItems : 0));
        
        $estimasiOngkir = (float)($liveData['estimated_shipping_fee'] ?? $inc['estimated_shipping_fee'] ?? 0);
        $ongkirRebate = (float)($inc['shopee_shipping_rebate'] ?? 0);
        $ongkirDibayarPembeli = (float)($inc['buyer_paid_shipping_fee'] ?? $order->shipping_fee_customer ?? 0);
        
        // Terkadang Shopee mengisi 0 pada buyer_paid_shipping_fee meski pembeli bayar ongkir. Kita hitung manual selisihnya jika begitu.
        if ($ongkirDibayarPembeli == 0 && $estimasiOngkir > $ongkirRebate) {
            $ongkirDibayarPembeli = $estimasiOngkir - $ongkirRebate;
        }
        
        $ongkosJasaKirim = (float)($settlement->actual_shipping_fee ?? $inc['actual_shipping_fee'] ?? ($inc['estimated_shipping_fee'] ?? ($liveData['actual_shipping_fee'] ?? ($liveData['estimated_shipping_fee'] ?? 0))));
        $potonganOngkir = (float)($settlement->shipping_fee_subsidy ?? $inc['shopee_shipping_rebate'] ?? $order->shipping_discount_platform ?? 0);
        
        $voucherToko = (float)($settlement->seller_voucher ?? $inc['voucher_from_seller'] ?? $inc['seller_voucher_rebate'] ?? 0);
        
        // Buyer Payment Data dipakai juga untuk estimasi saat settlement belum ada.
        $voucherPlatform = (float)($inc['voucher_from_shopee'] ?? $inc['voucher_from_platform'] ?? $inc['platform_voucher'] ?? 0);
        $koinShopee = (float)($inc['coin'] ?? 0); // Koin yang ditukarkan pembeli
        $totalPembeli = (float)($inc['buyer_total_amount'] ?? $inc['buyer_paid_amount'] ?? ($order->total_paid_customer > 0
            ? $order->total_paid_customer
            : ($settlement->buyer_payment_amount ?? ($liveData['total_amount'] ?? 0))));
        
        $isEstimasi = false;
        $estimatedMarketplaceFee = 0.0;
        $estimatedFeeBase = 0.0;
        
        if ($settlement) {
            $biayaAdmin = abs((float)($settlement->commission_fee ?? 0)) + abs((float)($settlement->transaction_fee ?? 0));
            $biayaLayananSeller = abs((float)($settlement->service_fee ?? 0));
            $biayaAffiliate = abs((float)($settlement->affiliate_fee ?? 0));
            $biayaAsuransiPengiriman = abs((float)($settlement->shipping_insurance_fee ?? 0));
            $biayaLainnyaLuar = abs((float)($settlement->activity_fee ?? 0)) + abs((float)($settlement->escrow_tax ?? 0)) + abs((float)($settlement->drc_adjustable_refund ?? 0)) + abs((float)($settlement->ad_cost ?? 0)) + $biayaAffiliate + $biayaAsuransiPengiriman;
            
            $biayaLayanan = $biayaAdmin + $biayaLayananSeller;
            $biayaLainnya = $biayaLainnyaLuar;
            $penghasilan = (float)$settlement->final_income;
        } else {
            $biayaAdmin = abs((float)($inc['commission_fee'] ?? 0)) + abs((float)($inc['seller_transaction_fee'] ?? 0));
            $biayaLayananSeller = abs((float)($inc['service_fee'] ?? 0));
            $biayaAffiliate = abs((float)($inc['affiliate_fee'] ?? $inc['affiliate_commission_fee'] ?? $inc['affiliate_commission'] ?? $inc['seller_affiliate_fee'] ?? 0));
            $biayaAsuransiPengiriman = abs((float)($inc['shipping_insurance_fee'] ?? $inc['shipping_insurance'] ?? $inc['insurance_fee'] ?? 0));
            $biayaLainnyaLuar = abs((float)($inc['payment_promotion'] ?? 0)) + abs((float)($inc['cross_border_tax'] ?? 0)) + abs((float)($inc['escrow_tax'] ?? 0)) + $biayaAffiliate + $biayaAsuransiPengiriman;
            
            $biayaLayanan = $biayaAdmin + $biayaLayananSeller;
            if ($biayaLayanan == 0) $biayaLayanan = abs((float)($order->platform_fee_total ?? 0));

            $biayaLainnya = $biayaLainnyaLuar;

            $penghasilan = (float)($inc['escrow_amount'] 
                ?? $liveData['payment_info']['net_revenue'] 
                ?? $order->net_payout_estimated ?? 0);
                
            if ($penghasilan <= 0) {
                // Estimasi saat data penghasilan/settlement belum ada.
                $estimatedFeeBase = max((float) $subtotal - (float) $voucherToko, 0);

                if ($estimatedFeeBase <= 0) {
                    $estimatedFeeBase = max((float)($liveData['total_amount'] ?? $order->total_paid_customer ?? $order->total_amount ?? 0), 0);
                }

                $estimatedMarketplaceFee = round($estimatedFeeBase * (float) $estimatedFeeRatio);

                if ($biayaLayanan <= 0) {
                    $biayaLayanan = $estimatedMarketplaceFee;
                    $biayaAdmin = $biayaLayanan;
                }

                $penghasilan = max($estimatedFeeBase - $biayaLayanan - $biayaLainnya, 0);
                $isEstimasi = true;
            }
        }
        
        $totalVoucherSubsidi = $voucherPlatform + $voucherToko;
        $subtotalOngkir = $ongkirDibayarPembeli - $ongkosJasaKirim + $potonganOngkir;
        
        // Coba ambil kode voucher toko
        $voucherCodes = $inc['seller_voucher_code'] ?? [];
        $voucherCodeStr = !empty($voucherCodes) ? (is_array($voucherCodes) ? implode(', ', $voucherCodes) : $voucherCodes) : '';

        $settlementRaw = is_array($settlement?->raw_json ?? null) ? $settlement->raw_json : [];
        $feeValue = function (string $key, $default = null) use ($settlementRaw, $inc) {
            $settlementVal = data_get($settlementRaw, $key);
            if ($settlementVal !== null && $settlementVal !== '') {
                return $settlementVal;
            }

            $incomeVal = data_get($inc, $key);
            if ($incomeVal !== null && $incomeVal !== '') {
                return $incomeVal;
            }

            return $default;
        };

        $buildFeeRows = function (array &$rows, string $label, $value) {
            $amount = abs((float) ($value ?? 0));
            if ($amount > 0) {
                $rows[] = [
                    'label' => $label,
                    'amount' => $amount,
                ];
            }
        };

        $sellerFeeBreakdown = [];
        $voucherFeeBreakdown = [];
        $buyerFeeBreakdown = [];
        $platformFeeBreakdown = [];
        $adjustmentFeeBreakdown = [];

        $buildFeeRows($sellerFeeBreakdown, 'Biaya Administrasi', $feeValue('commission_fee', $biayaAdmin));
        $buildFeeRows($sellerFeeBreakdown, 'Biaya Layanan', $feeValue('service_fee', $biayaLayananSeller));
        $buildFeeRows($sellerFeeBreakdown, 'Biaya Transaksi', $feeValue('seller_transaction_fee'));
        $buildFeeRows($sellerFeeBreakdown, 'Biaya Proses Pesanan', $feeValue('seller_order_processing_fee'));
        $buildFeeRows($sellerFeeBreakdown, 'Biaya Komisi AMS', $feeValue('order_ams_commission_fee', $feeValue('ams_commission_fee', $biayaLainnyaLuar)));
        $buildFeeRows($sellerFeeBreakdown, 'Biaya Affiliate', $feeValue('affiliate_fee', $biayaAffiliate));
        $buildFeeRows($sellerFeeBreakdown, 'Affiliate', $feeValue('affiliate_commission', $feeValue('affiliate_commission_amount')));
        $buildFeeRows($sellerFeeBreakdown, 'Biaya Asuransi Pengiriman', $feeValue('shipping_insurance_fee', $biayaAsuransiPengiriman));
        $buildFeeRows($sellerFeeBreakdown, 'Pajak (Escrow)', $feeValue('escrow_tax'));
        $buildFeeRows($sellerFeeBreakdown, 'Biaya Iklan', $feeValue('ad_cost'));
        $buildFeeRows($sellerFeeBreakdown, 'Premi', $feeValue('premi', $feeValue('shipping_insurance', $feeValue('insurance_fee'))));

        $buildFeeRows($voucherFeeBreakdown, 'Voucher Platform', $voucherPlatform);
        $buildFeeRows($voucherFeeBreakdown, 'Voucher Toko', $voucherToko);

        $buildFeeRows($platformFeeBreakdown, 'Biaya Kampanye', $feeValue('campaign_fee'));
        $buildFeeRows($platformFeeBreakdown, 'Subsidi Ongkir Platform', $feeValue('shipping_fee_subsidy', $potonganOngkir));

        $buildFeeRows($adjustmentFeeBreakdown, 'Refund / Adjustment', $feeValue('drc_adjustable_refund', $feeValue('seller_return_refund_amount', $settlement?->drc_adjustable_refund ?? 0)));

        $sellerFeeBreakdownTotal = array_sum(array_column($sellerFeeBreakdown, 'amount'));
        $voucherFeeBreakdownTotal = array_sum(array_column($voucherFeeBreakdown, 'amount'));
        $buyerFeeBreakdownTotal = array_sum(array_column($buyerFeeBreakdown, 'amount'));
        $platformFeeBreakdownTotal = array_sum(array_column($platformFeeBreakdown, 'amount'));
        $adjustmentFeeBreakdownTotal = array_sum(array_column($adjustmentFeeBreakdown, 'amount'));
        $grandFeeBreakdownTotal = $sellerFeeBreakdownTotal + $voucherFeeBreakdownTotal + $buyerFeeBreakdownTotal + $platformFeeBreakdownTotal + $adjustmentFeeBreakdownTotal;

        if ($totalPembeli > 0) {
            // Hitung mundur biaya layanan pembeli agar totalnya pas (balancing)
            $biayaLayananPembeli = $totalPembeli - ($subtotal + $ongkirDibayarPembeli - $voucherPlatform - $voucherToko - $koinShopee);
            if ($biayaLayananPembeli < 0) {
                // Jika negatif, mungkin subtotal tidak klop, pakai dari API
                $biayaLayananPembeli = (float)($inc['buyer_transaction_fee'] ?? 0);
            }
        } else {
            $biayaLayananPembeli = (float)($inc['buyer_transaction_fee'] ?? 2000);
            if (empty($inc) && !$settlement) $biayaLayananPembeli = 2000;
            $totalPembeli = $subtotal + $ongkirDibayarPembeli - $voucherPlatform - $voucherToko - $koinShopee + $biayaLayananPembeli;
        }

        $buildFeeRows($buyerFeeBreakdown, 'Ongkos Kirim Dibayar Pembeli', $ongkirDibayarPembeli);
        $buildFeeRows($buyerFeeBreakdown, 'Biaya Pembayaran Pembeli', $biayaLayananPembeli);
    @endphp

    <div class="od-grid-2">
        <div class="od-card">
            <div class="od-head">
                <div>
                    <div style="font-weight:500; color:#64748b; font-size:.85rem; margin-bottom:2px">{{ $isEstimasi ? 'Estimasi ' : '' }}Penghasilan Akhir</div>
                    @if($isEstimasi)
                        <div style="font-size:.68rem; color:#64748b; font-weight:600;">
                            Berdasarkan rata-rata fee historis toko ini ({{ number_format($estimatedFeePct, 1, ',', '.') }}%).
                        </div>
                    @endif
                </div>
                <div style="font-weight:900; color:#166534; font-size:1.1rem">Rp{{ number_format($penghasilan, 0, ',', '.') }}</div>
            </div>
            <div class="od-body od-list" style="border-top:1px solid rgba(148,163,184,.12)">
                <div style="font-weight:700; color:#334155; margin-bottom:.1rem">Subtotal Pesanan <span style="float:right">Rp{{ number_format($subtotal, 0, ',', '.') }}</span></div>
                <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                    <span class="od-muted">Harga Produk</span>
                    <span class="od-code-cell" style="font-size:.85rem">Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div style="font-weight:700; color:#334155; margin-top:.5rem; margin-bottom:.1rem">Subtotal Ongkos Kirim <span style="float:right">Rp{{ number_format($subtotalOngkir, 0, ',', '.') }}</span></div>
                <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                    <span class="od-muted">Ongkir Dibayar Pembeli</span>
                    <span class="od-code-cell" style="font-size:.85rem">Rp{{ number_format($ongkirDibayarPembeli, 0, ',', '.') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                    <span class="od-muted">Ongkos Kirim yang Dibayarkan ke Jasa Kirim</span>
                    <span class="od-code-cell" style="font-size:.85rem; color:#b91c1c">-Rp{{ number_format($ongkosJasaKirim, 0, ',', '.') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                    <span class="od-muted">Potongan Ongkos Kirim dari Shopee</span>
                    <span class="od-code-cell" style="font-size:.85rem; color:#15803d">Rp{{ number_format($potonganOngkir, 0, ',', '.') }}</span>
                </div>

                <div style="font-weight:700; color:#334155; margin-top:.6rem; margin-bottom:.35rem">
                    Rincian Biaya Marketplace
                    <span style="float:right; color:#b91c1c">-Rp{{ number_format($sellerFeeBreakdownTotal + $voucherFeeBreakdownTotal + $buyerFeeBreakdownTotal + $platformFeeBreakdownTotal + $adjustmentFeeBreakdownTotal, 0, ',', '.') }}</span>
                </div>

                <div class="od-fee-tabs" style="display:flex; gap:.35rem; flex-wrap:wrap; margin: .25rem 0 .65rem;">
                    <button type="button" class="od-btn od-fee-tab active" data-fee-tab="seller">Beban Seller</button>
                    <button type="button" class="od-btn od-fee-tab" data-fee-tab="voucher">Voucher</button>
                    <button type="button" class="od-btn od-fee-tab" data-fee-tab="buyer">Beban Pembeli</button>
                    <button type="button" class="od-btn od-fee-tab" data-fee-tab="platform">Beban Platform</button>
                    <button type="button" class="od-btn od-fee-tab" data-fee-tab="adjustment">Penyesuaian</button>
                </div>

                <div class="od-fee-panel" data-fee-panel="seller">
                    <div style="font-weight:700; color:#334155; margin-bottom:.1rem">
                        Total Beban Seller <span style="float:right; color:#b91c1c">-Rp{{ number_format($sellerFeeBreakdownTotal, 0, ',', '.') }}</span>
                    </div>
                    @forelse($sellerFeeBreakdown as $feeRow)
                    <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                        <span class="od-muted">{{ $feeRow['label'] }}</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#b91c1c">-Rp{{ number_format($feeRow['amount'], 0, ',', '.') }}</span>
                    </div>
                    @empty
                    <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                        <span class="od-muted">Belum ada rincian beban seller</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#94a3b8">—</span>
                    </div>
                    @endforelse
                    @if($isEstimasi)
                    <div style="display:flex; justify-content:space-between; margin-left:.5rem; margin-top:.25rem">
                        <span class="od-muted">Estimasi Fee Marketplace</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#b91c1c">-Rp{{ number_format($estimatedMarketplaceFee, 0, ',', '.') }}</span>
                    </div>
                    @endif
                </div>

                <div class="od-fee-panel" data-fee-panel="voucher" style="display:none">
                    <div style="font-weight:700; color:#334155; margin-bottom:.1rem">
                        Total Voucher <span style="float:right; color:#b91c1c">-Rp{{ number_format($voucherFeeBreakdownTotal, 0, ',', '.') }}</span>
                    </div>
                    @forelse($voucherFeeBreakdown as $feeRow)
                    <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                        <span class="od-muted">{{ $feeRow['label'] }}</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#b91c1c">-Rp{{ number_format($feeRow['amount'], 0, ',', '.') }}</span>
                    </div>
                    @empty
                    <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                        <span class="od-muted">Belum ada voucher terdeteksi</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#94a3b8">—</span>
                    </div>
                    @endforelse
                    @if($voucherCodeStr)
                    <div style="display:flex; justify-content:space-between; margin-left:.5rem; margin-top:.25rem">
                        <span class="od-muted">Kode Voucher Toko</span>
                        <span class="od-code-cell" style="font-size:.75rem; color:#64748b">{{ $voucherCodeStr }}</span>
                    </div>
                    @endif
                </div>

                <div class="od-fee-panel" data-fee-panel="buyer" style="display:none">
                    <div style="font-weight:700; color:#334155; margin-bottom:.1rem">
                        Total Beban Pembeli <span style="float:right; color:#0f766e">Rp{{ number_format($buyerFeeBreakdownTotal, 0, ',', '.') }}</span>
                    </div>
                    @forelse($buyerFeeBreakdown as $feeRow)
                    <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                        <span class="od-muted">{{ $feeRow['label'] }}</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#0f766e">Rp{{ number_format($feeRow['amount'], 0, ',', '.') }}</span>
                    </div>
                    @empty
                    <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                        <span class="od-muted">Belum ada beban pembeli</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#94a3b8">—</span>
                    </div>
                    @endforelse
                </div>

                <div class="od-fee-panel" data-fee-panel="platform" style="display:none">
                    <div style="font-weight:700; color:#334155; margin-bottom:.1rem">
                        Total Beban Platform <span style="float:right; color:#15803d">Rp{{ number_format($platformFeeBreakdownTotal, 0, ',', '.') }}</span>
                    </div>
                    @forelse($platformFeeBreakdown as $feeRow)
                    <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                        <span class="od-muted">{{ $feeRow['label'] }}</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#15803d">Rp{{ number_format($feeRow['amount'], 0, ',', '.') }}</span>
                    </div>
                    @empty
                    <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                        <span class="od-muted">Belum ada beban platform</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#94a3b8">—</span>
                    </div>
                    @endforelse
                </div>

                <div class="od-fee-panel" data-fee-panel="adjustment" style="display:none">
                    <div style="font-weight:700; color:#334155; margin-bottom:.1rem">
                        Total Penyesuaian <span style="float:right; color:#b91c1c">-Rp{{ number_format($adjustmentFeeBreakdownTotal, 0, ',', '.') }}</span>
                    </div>
                    @forelse($adjustmentFeeBreakdown as $feeRow)
                    <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                        <span class="od-muted">{{ $feeRow['label'] }}</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#b91c1c">-Rp{{ number_format($feeRow['amount'], 0, ',', '.') }}</span>
                    </div>
                    @empty
                    <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                        <span class="od-muted">Belum ada penyesuaian untuk pesanan ini</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#94a3b8">—</span>
                    </div>
                    @endforelse
                </div>

                <div style="display:flex; justify-content:space-between; margin-top:1.5rem; padding-top:.5rem; border-top:1px solid rgba(148,163,184,.3)">
                    <span style="font-weight:800; color:#111827; font-size:1.1rem">{{ $isEstimasi ? 'Estimasi ' : '' }}Penghasilan Akhir</span>
                    <span class="od-code-cell" style="font-size:1.1rem; font-weight:900; color:#166534">Rp{{ number_format($penghasilan, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="od-card">
            <div class="od-head">
                <div class="od-title">Pembayaran Pembeli</div>
            </div>
            <div class="od-body od-list" style="border-top:1px solid rgba(148,163,184,.12)">
                <div style="display:flex; justify-content:space-between">
                    <span class="od-muted">Subtotal Pesanan</span>
                    <span class="od-code-cell" style="font-size:.85rem">Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between">
                    <span class="od-muted">Ongkos Kirim</span>
                    <span class="od-code-cell" style="font-size:.85rem">Rp{{ number_format($ongkirDibayarPembeli, 0, ',', '.') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between">
                    <span class="od-muted">Voucher Platform</span>
                    <span class="od-code-cell" style="font-size:.85rem; color:{{ $voucherPlatform > 0 ? '#991b1b' : 'inherit' }}">{{ $voucherPlatform > 0 ? '-' : '' }}Rp{{ number_format($voucherPlatform, 0, ',', '.') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between">
                    <span class="od-muted">Voucher Toko</span>
                    <span class="od-code-cell" style="font-size:.85rem; color:{{ $voucherToko > 0 ? '#991b1b' : 'inherit' }}">{{ $voucherToko > 0 ? '-' : '' }}Rp{{ number_format($voucherToko, 0, ',', '.') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between">
                    <span class="od-muted">Koin Shopee Ditukarkan</span>
                    <span class="od-code-cell" style="font-size:.85rem; color:{{ $koinShopee > 0 ? '#991b1b' : 'inherit' }}">{{ $koinShopee > 0 ? '-' : '' }}Rp{{ number_format($koinShopee, 0, ',', '.') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between">
                    <span class="od-muted">Biaya Layanan & Penanganan</span>
                    <span class="od-code-cell" style="font-size:.85rem">Rp{{ number_format($biayaLayananPembeli, 0, ',', '.') }}</span>
                </div>
                
                <div style="display:flex; justify-content:space-between; margin-top:.8rem; padding-top:.5rem; border-top:1px dashed rgba(148,163,184,.3)">
                    <span style="font-weight:800; color:#111827">Total Pembayaran Pembeli</span>
                    <span class="od-code-cell" style="font-size:1.1rem; color:#111827">Rp{{ number_format($totalPembeli, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.trackOrder = async function(storeId, orderSn, e) {
        if(e) e.preventDefault();
        
        let modalEl = document.getElementById('trackingModal');
        if (!modalEl) {
            document.body.insertAdjacentHTML('beforeend', `
                <div class="modal fade" id="trackingModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content border-0 shadow" style="border-radius:12px;">
                            <div class="modal-header border-bottom-0 pb-0">
                                <h5 class="modal-title fw-bold" style="font-size:1.1rem; color:#0f172a;">Status Pengiriman</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="trackingModalBody"></div>
                        </div>
                    </div>
                </div>
            `);
            modalEl = document.getElementById('trackingModal');
        }
        
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        const body = document.getElementById('trackingModalBody');
        
        body.innerHTML = '<div style="text-align:center; padding:30px; color:#64748b"><div class="spinner-border spinner-border-sm text-primary mb-2"></div><br>Mengambil data tracking...</div>';
        modal.show();

        try {
            const res = await fetch(`/api/marketplace/stores/${storeId}/orders/${orderSn}/tracking`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            
            if (!res.ok || data.error) {
                body.innerHTML = `<div style="color:#ef4444; text-align:center; padding:20px">❌ Gagal mengambil tracking: ${data.message || data.error || 'Terjadi kesalahan'}</div>`;
                return;
            }

            const trk = data.response?.tracking_info || data.tracking_info || [];
            if (!trk.length) {
                body.innerHTML = `<div style="color:#d97706; text-align:center; padding:20px; font-weight:500;">ℹ️ Belum ada riwayat perjalanan paket.</div>`;
                return;
            }

            let html = '<div style="position:relative; padding-left:1.5rem; margin-top:10px;">';
            html += '<div style="position:absolute; left:7px; top:10px; bottom:10px; width:2px; background:#e2e8f0;"></div>';
            
            trk.forEach((t, i) => {
                const isFirst = i === 0;
                const dateObj = new Date(t.update_time * 1000);
                const dText = dateObj.toLocaleString('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
                const dotColor = isFirst ? '#334155' : '#cbd5e1';
                const textColor = isFirst ? '#0f172a' : '#64748b';
                
                html += `
                    <div style="position:relative; margin-bottom:1.2rem;">
                        <div style="position:absolute; left:-1.5rem; top:4px; width:10px; height:10px; border-radius:50%; background:${dotColor}; border:2px solid #fff; box-shadow:0 0 0 1px ${dotColor}"></div>
                        <div style="font-size:0.85rem; font-weight:${isFirst ? '600' : '500'}; color:${textColor};">${t.description || t.logistics_status}</div>
                        <div style="font-size:0.75rem; color:#94a3b8; margin-top:2px;">${dText}</div>
                    </div>
                `;
            });
            html += '</div>';
            body.innerHTML = html;
        } catch (err) {
            body.innerHTML = `<div style="color:#ef4444; text-align:center; padding:20px">❌ Gagal menghubungi server: ${err.message}</div>`;
        }
    };

    (function initFeeTabs() {
        const tabs = Array.from(document.querySelectorAll('[data-fee-tab]'));
        const panels = Array.from(document.querySelectorAll('[data-fee-panel]'));
        if (!tabs.length || !panels.length) return;

        const activate = (key) => {
            tabs.forEach((tab) => {
                const active = tab.dataset.feeTab === key;
                tab.classList.toggle('active', active);
            });
            panels.forEach((panel) => {
                panel.style.display = panel.dataset.feePanel === key ? '' : 'none';
            });
        };

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => activate(tab.dataset.feeTab));
        });

        activate('seller');
    })();
</script>
@endpush
