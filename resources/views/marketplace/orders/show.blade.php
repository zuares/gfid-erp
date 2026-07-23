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
    $liveData = $raw['order_list'][0] ?? $raw['response']['order_list'][0] ?? (isset($raw['order_sn']) ? $raw : []);
    $pkg = $liveData['package_list'][0] ?? [];
    
    // Fallbacks
    $buyerName = $liveData['recipient_address']['name'] ?? $order->buyer_name ?? '—';
    $buyerPhone = $liveData['recipient_address']['phone'] ?? $order->buyer_phone ?? '—';
    $buyerAddress = $liveData['recipient_address']['full_address'] ?? $order->shipping_address ?? '—';
    $zipcode = $liveData['recipient_address']['zipcode'] ?? $order->shipping_postal_code ?? '';
    
    $carrier = $liveData['shipping_carrier'] ?? $pkg['shipping_carrier'] ?? $order->shipping_carrier ?? $order->shipping_courier_code ?? '—';
    $awb = $pkg['package_number'] ?? $pkg['tracking_number'] ?? $order->shipping_awb_no ?? '';
    
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

<div class="od-wrap">
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
            <div class="od-value" style="font-size:1rem">{{ $awb ?: 'Belum ada' }}</div>
            @if($awb && !$isCancel && $statusText !== 'unpaid')
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
        
        <div class="od-card">
            <div class="od-head">
                <div class="od-title">Waktu Transaksi</div>
            </div>
            <div class="od-body od-list">
                <div style="display:flex; justify-content:space-between">
                    <span class="od-muted">Dipesan</span>
                    <span class="od-code-cell" style="font-size:.85rem">{{ $order->ordered_at ? id_datetime($order->ordered_at) : ($order->order_date ? id_datetime($order->order_date) : '—') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between">
                    <span class="od-muted">Dibayar</span>
                    <span class="od-code-cell" style="font-size:.85rem">{{ $order->payment_date ? id_datetime($order->payment_date) : '—' }}</span>
                </div>
                <div style="display:flex; justify-content:space-between">
                    <span class="od-muted">Dikirim</span>
                    <span class="od-code-cell" style="font-size:.85rem">{{ $order->shipping_arranged_at ? id_datetime($order->shipping_arranged_at) : '—' }}</span>
                </div>
                <div style="display:flex; justify-content:space-between">
                    <span class="od-muted">Selesai / Batal</span>
                    <span class="od-code-cell" style="font-size:.85rem">{{ $order->completed_at ? id_datetime($order->completed_at) : ($order->cancelled_at ? id_datetime($order->cancelled_at) : '—') }}</span>
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
                        @php $subtotalItems += $line->line_gross_amount; @endphp
                        <tr>
                            <td class="od-code-cell">{{ $line->external_sku ?: '-' }}</td>
                            <td>
                                <div style="font-weight:700; color:#111827">{{ $line->item_name_snapshot ?: '-' }}</div>
                            </td>
                            <td class="od-c">{{ number_format($line->price_original, 0, ',', '.') }}</td>
                            <td class="od-c od-code-cell">{{ (int) $line->qty }}</td>
                            <td class="od-r od-code-cell">{{ number_format($line->line_gross_amount, 0, ',', '.') }}</td>
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
        $inc = $liveData['income_details'] ?? [];
        
        // Seller Income Data
        // Subtotal = Harga Produk Setelah Diskon (dari loop item_list atau fallback database)
        $subtotal = $subtotalItems > 0 ? $subtotalItems : ($order->subtotal_items ?? ($inc['original_price'] ?? 0));
        
        $ongkirDibayarPembeli = (float)($inc['buyer_paid_shipping_fee'] ?? $order->shipping_fee_customer ?? 0);
        
        $ongkosJasaKirim = (float)($settlement->actual_shipping_fee ?? $inc['actual_shipping_fee'] ?? ($inc['estimated_shipping_fee'] ?? ($liveData['actual_shipping_fee'] ?? ($liveData['estimated_shipping_fee'] ?? 0))));
        $potonganOngkir = (float)($settlement->shipping_fee_subsidy ?? $inc['shopee_shipping_rebate'] ?? $order->shipping_discount_platform ?? 0);
        
        $subsidiShopee = (float)($inc['shopee_discount'] ?? 0);
        
        $paketDiskon = 0;
        if (isset($inc['items']) && is_array($inc['items'])) {
            foreach ($inc['items'] as $it) {
                $sellP = (float)($it['selling_price'] ?? 0);
                $discP = (float)($it['discounted_price'] ?? 0);
                if ($sellP > $discP) {
                    $paketDiskon += ($sellP - $discP);
                }
            }
        }
        $voucherToko = (float)($settlement->seller_voucher ?? $inc['voucher_from_seller'] ?? $order->voucher_discount ?? 0) + $paketDiskon;
        
        $isEstimasi = false;
        
        if ($settlement) {
            $biayaAdmin = abs((float)($settlement->commission_fee ?? 0)) + abs((float)($settlement->transaction_fee ?? 0));
            $biayaLayananSeller = abs((float)($settlement->service_fee ?? 0));
            $biayaLainnyaLuar = abs((float)($settlement->activity_fee ?? 0)) + abs((float)($settlement->escrow_tax ?? 0)) + abs((float)($settlement->drc_adjustable_refund ?? 0)) + abs((float)($settlement->ad_cost ?? 0));
            
            $biayaLayanan = $biayaAdmin + $biayaLayananSeller;
            $biayaLainnya = $biayaLainnyaLuar;
            $penghasilan = (float)$settlement->final_income;
        } else {
            $biayaAdmin = abs((float)($inc['commission_fee'] ?? 0)) + abs((float)($inc['seller_transaction_fee'] ?? 0));
            $biayaLayananSeller = abs((float)($inc['service_fee'] ?? 0));
            $biayaLainnyaLuar = abs((float)($inc['payment_promotion'] ?? 0)) + abs((float)($inc['cross_border_tax'] ?? 0)) + abs((float)($inc['escrow_tax'] ?? 0));
            
            $biayaLayanan = $biayaAdmin + $biayaLayananSeller;
            if ($biayaLayanan == 0) $biayaLayanan = abs((float)($order->platform_fee_total ?? 0));

            $biayaLainnya = $biayaLainnyaLuar;

            $penghasilan = (float)($inc['escrow_amount'] 
                ?? $liveData['payment_info']['net_revenue'] 
                ?? $order->net_payout_estimated ?? 0);
                
            if ($penghasilan <= 0) {
                // Estimasi (saat data settlement/escrow belum ada)
                if ($biayaLayanan == 0 && $biayaLainnya == 0) {
                    $ratio = in_array($statusText, ['ready_to_ship', 'processed', 'shipped']) ? 0.24 : ((float)($estimatedFeeRatio ?? 0.15));
                    $biayaLayanan = round((float)$subtotal * $ratio);
                    $biayaAdmin = $biayaLayanan; // For display
                }
                $penghasilan = (float)$subtotal - $voucherToko - $biayaLayanan - $biayaLainnya;
                $isEstimasi = true;
            }
        }
        
        $totalBiayaLainnya = $biayaLayanan + $biayaLainnya;
        $totalVoucherSubsidi = $voucherToko - $subsidiShopee; // - if seller voucher is higher
        $subtotalOngkir = $ongkirDibayarPembeli - $ongkosJasaKirim + $potonganOngkir;
        
        // Coba ambil kode voucher toko
        $voucherCodes = $inc['seller_voucher_code'] ?? [];
        $voucherCodeStr = !empty($voucherCodes) ? (is_array($voucherCodes) ? implode(', ', $voucherCodes) : $voucherCodes) : '';

        // Buyer Payment Data
        $voucherShopee = (float)($inc['voucher_from_shopee'] ?? $order->other_discount ?? 0);
        $koinShopee = (float)($settlement->seller_coin_cash_back ?? $inc['seller_coin_cash_back'] ?? 0);
        $biayaLayananPembeli = (float)($inc['buyer_transaction_fee'] ?? 2000);
        
        if (empty($inc) && !$settlement && (float)($liveData['total_amount'] ?? 0) > 0) {
            $biayaLayananPembeli = 2000; 
        }

        $totalPembeli = (float)($settlement->buyer_payment_amount ?? $inc['buyer_total_amount'] ?? ($order->total_paid_customer > 0
            ? $order->total_paid_customer
            : ($liveData['total_amount'] ?? ($subtotal + $ongkirDibayarPembeli - $voucherShopee - $voucherToko + $biayaLayananPembeli))));
    @endphp

    <div class="od-grid-2">
        <div class="od-card">
            <div class="od-head">
                <div style="font-weight:500; color:#64748b; font-size:.85rem; margin-bottom:2px">{{ $isEstimasi ? 'Estimasi ' : '' }}Penghasilan Akhir</div>
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

                @if($totalVoucherSubsidi > 0 || $subsidiShopee > 0)
                <div style="font-weight:700; color:#334155; margin-top:.6rem; margin-bottom:.1rem">
                    Voucher & Subsidi Shopee 
                    <span style="float:right; color:{{ $totalVoucherSubsidi > 0 ? '#b91c1c' : '#15803d' }}">
                        {{ $totalVoucherSubsidi > 0 ? '-' : '' }}Rp{{ number_format(abs($totalVoucherSubsidi), 0, ',', '.') }}
                    </span>
                </div>
                @if($voucherToko > 0)
                <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                    <span class="od-muted">Voucher Toko yang ditanggung Penjual {{ $voucherCodeStr ? '- '.$voucherCodeStr : '' }}</span>
                    <span class="od-code-cell" style="font-size:.85rem; color:#b91c1c">-Rp{{ number_format($voucherToko, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($subsidiShopee > 0)
                <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                    <span class="od-muted">Subsidi Shopee</span>
                    <span class="od-code-cell" style="font-size:.85rem; color:#15803d">Rp{{ number_format($subsidiShopee, 0, ',', '.') }}</span>
                </div>
                @endif
                @endif

                <div style="font-weight:700; color:#334155; margin-top:{{ ($totalVoucherSubsidi > 0 || $subsidiShopee > 0) ? '.6rem' : '.6rem' }}; margin-bottom:.1rem">
                    Biaya Lainnya <span style="float:right; color:#b91c1c">-Rp{{ number_format($totalBiayaLainnya, 0, ',', '.') }}</span>
                </div>
                @if($biayaAdmin > 0)
                <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                    <span class="od-muted">Biaya Administrasi</span>
                    <span class="od-code-cell" style="font-size:.85rem; color:#b91c1c">-Rp{{ number_format($biayaAdmin, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($biayaLayananSeller > 0)
                <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                    <span class="od-muted">Biaya Layanan</span>
                    <span class="od-code-cell" style="font-size:.85rem; color:#b91c1c">-Rp{{ number_format($biayaLayananSeller, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($biayaLainnyaLuar > 0)
                <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                    <span class="od-muted">Pajak / Biaya Lainnya</span>
                    <span class="od-code-cell" style="font-size:.85rem; color:#b91c1c">-Rp{{ number_format($biayaLainnyaLuar, 0, ',', '.') }}</span>
                </div>
                @endif
                
                <div style="margin-top:1.5rem">
                    <div style="font-weight:700; color:#334155; margin-bottom:.5rem">Penyesuaian Pesanan</div>
                    <table style="width:100%; border-collapse:collapse; font-size:.85rem; text-align:left">
                        <thead style="background:rgba(241,245,249,.5)">
                            <tr>
                                <th style="padding:.5rem; border-bottom:1px solid rgba(148,163,184,.2); font-weight:600; color:#64748b">Tanggal Penyesuaian Dibuat</th>
                                <th style="padding:.5rem; border-bottom:1px solid rgba(148,163,184,.2); font-weight:600; color:#64748b">Alasan Penyesuaian</th>
                                <th style="padding:.5rem; border-bottom:1px solid rgba(148,163,184,.2); font-weight:600; color:#64748b; text-align:right">Penyesuaian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="3" style="padding:1rem; text-align:center; color:#94a3b8">Belum ada biaya penyesuaian untuk pesanan ini</td>
                            </tr>
                        </tbody>
                    </table>
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
                    <span class="od-muted">Voucher Shopee</span>
                    <span class="od-code-cell" style="font-size:.85rem; color:{{ $voucherShopee > 0 ? '#991b1b' : 'inherit' }}">{{ $voucherShopee > 0 ? '-' : '' }}Rp{{ number_format($voucherShopee, 0, ',', '.') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between">
                    <span class="od-muted">Voucher Toko</span>
                    <span class="od-code-cell" style="font-size:.85rem; color:{{ $voucherToko > 0 ? '#991b1b' : 'inherit' }}">{{ $voucherToko > 0 ? '-' : '' }}Rp{{ number_format($voucherToko, 0, ',', '.') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between">
                    <span class="od-muted">Dapatkan Koin Shopee 
                        @if($koinShopee > 0) 
                        <span style="font-size:0.75rem; color:#64748b">( {{ number_format($koinShopee, 0, ',', '.') }} Koin )</span> 
                        @endif
                    </span>
                    <span class="od-code-cell" style="font-size:.85rem; color:{{ $koinShopee > 0 ? '#991b1b' : 'inherit' }}">{{ $koinShopee > 0 ? '-' : '' }}Rp{{ number_format($koinShopee, 0, ',', '.') }}</span>
                </div>
                <div style="display:flex; justify-content:space-between">
                    <span class="od-muted">Biaya Layanan</span>
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
</script>
@endpush
