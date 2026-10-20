@extends('layouts.app')

@section('title', 'Marketplace Order ' . ($order->channel_order_id ?: $order->external_order_id))

@push('head')
<style>
.od-wrap{max-width:1240px;margin-inline:auto;padding:1rem 1rem 4rem;color:var(--text,#172033);display:flex;flex-direction:column}
.od-topbar{position:relative;width:min(100%,1240px);margin-inline:auto;display:flex;align-items:center;gap:.85rem;padding:.7rem 1rem;background:var(--card,#fff);border:1px solid var(--line,#e7ebf0);border-top:0;box-shadow:0 1px 2px rgba(15,23,42,.035)}
.od-topbar-inner{width:100%;display:flex;align-items:center;gap:.75rem;min-width:0}
.od-identity{min-width:0;display:grid;gap:.08rem}
.od-kicker{font-size:.65rem;font-weight:750;letter-spacing:.07em;text-transform:uppercase;color:var(--muted,#64748b)}
.od-code{font-size:1rem;font-weight:800;letter-spacing:-.015em;color:var(--text,#172033);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.od-spacer{flex:1}
.od-topbar-store{display:grid;gap:.08rem;min-width:0;padding-left:.75rem;border-left:1px solid var(--line,#e7ebf0)}
.od-topbar-store b{font-size:.78rem;font-weight:750;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px}
.od-btn,.od-pill{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;border-radius:9px;border:1px solid var(--line,#dce2e9);background:var(--card,#fff);color:#475569;text-decoration:none;font-size:.76rem;padding:.38rem .7rem;min-height:36px;white-space:nowrap}
.od-btn{font-weight:700;transition:background .15s ease,border-color .15s ease,transform .15s ease}
.od-btn:hover{background:var(--card-soft,#f8fafc);border-color:#cbd5e1;color:var(--text,#172033);text-decoration:none;transform:translateY(-1px)}
.od-btn-ghost{background:transparent}
.od-primary{background:#2563eb!important;border-color:#2563eb!important;color:#fff!important;box-shadow:0 5px 12px rgba(37,99,235,.16)}
.od-primary:hover{background:#1d4ed8!important;border-color:#1d4ed8!important;color:#fff!important}
.od-status{font-weight:750;letter-spacing:.02em;color:#475569;background:#f1f5f9;border-color:#e2e8f0}
.od-status::before{content:"";width:7px;height:7px;border-radius:50%;background:#94a3b8;box-shadow:0 0 0 3px rgba(148,163,184,.16)}
.od-status.completed::before,.od-status.shipped::before,.od-status.processed::before{background:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.14)}
.od-status.completed,.od-status.shipped,.od-status.processed{color:#166534;background:#f0fdf4;border-color:#bbf7d0}
.od-status.cancelled{color:#991b1b;background:#fff1f2;border-color:#fecdd3}
.od-status.cancelled::before{background:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.14)}
.od-flow{display:flex;align-items:center;gap:.55rem;flex-wrap:wrap;margin:0 0 1rem;padding:.7rem .8rem;border:1px solid var(--line,#e7ebf0);border-radius:14px;background:var(--card,#fff);box-shadow:0 1px 2px rgba(15,23,42,.035)}
.od-flow::before{content:"Order lifecycle";margin-right:.25rem;color:var(--muted,#64748b);font-size:.68rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase}
.od-step{border:1px solid var(--line,#dce2e9);border-radius:999px;padding:.34rem .7rem;font-size:.72rem;font-weight:700;color:var(--muted,#64748b);background:var(--card-soft,#f8fafc)}
.od-step.done{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
.od-step.active{background:#2563eb;border-color:#2563eb;color:#fff;box-shadow:0 3px 8px rgba(37,99,235,.18)}
.od-step.cancel{background:#fff1f2;border-color:#fecdd3;color:#be123c}
.od-sep{color:#cbd5e1;font-size:.72rem}
.od-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;margin-bottom:.75rem}
.od-grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem;margin-bottom:.75rem}
.od-card{background:var(--card,#fff);border:1px solid var(--line,#e7ebf0);border-radius:14px;overflow:hidden;margin-bottom:.75rem;box-shadow:0 1px 2px rgba(15,23,42,.035)}
.od-card:hover{box-shadow:0 8px 24px rgba(15,23,42,.055)}
.od-kpi{position:relative;min-height:116px;padding:1rem 1.1rem;overflow:hidden}
.od-kpi::after{content:"";position:absolute;width:96px;height:96px;right:-36px;bottom:-46px;border-radius:50%;background:#2563eb;opacity:.055}
.od-kpi-head{display:flex;align-items:center;gap:.65rem;margin-bottom:.8rem}
.od-kpi-icon{width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border-radius:10px;background:#eff6ff;color:#2563eb;font-size:1rem}
.od-label{font-size:.69rem;font-weight:750;color:var(--muted,#64748b);text-transform:uppercase;letter-spacing:.05em}
.od-value{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:1.05rem;font-weight:800;color:var(--text,#172033);margin-top:.12rem;letter-spacing:-.025em;font-variant-numeric:tabular-nums}
.od-value-text{font-size:1rem;font-weight:750;color:var(--text,#172033);margin-top:.12rem}
.od-muted{color:var(--muted,#64748b);font-size:.8rem}
.od-head{display:flex;align-items:center;gap:.75rem;justify-content:space-between;padding:.9rem 1.1rem;border-bottom:1px solid var(--line,#eef1f4);background:var(--card,#fff)}
.od-title{font-size:.9rem;font-weight:750;color:var(--text,#172033);letter-spacing:-.01em}
.od-body{padding:1rem 1.1rem}
.od-list{display:grid;gap:.55rem}
.od-table-wrap{overflow:auto;border-radius:0 0 14px 14px}
.od-table{width:100%;border-collapse:collapse}
.od-table th,.od-table td{padding:.72rem 1.1rem;border-bottom:1px solid #eef1f4;vertical-align:middle}
.od-table th{text-align:left;font-size:.68rem;color:var(--muted,#64748b);font-weight:750;text-transform:uppercase;letter-spacing:.05em;background:#f8fafc}
.od-table td{font-size:.84rem;color:var(--text,#334155)}
.od-table tbody tr:last-child td{border-bottom:0}
.od-table tbody tr:hover td{background:#fbfdff}
.od-code-cell{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:750;color:var(--text,#172033);font-variant-numeric:tabular-nums}
.od-sku-cell{display:block;max-width:8rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.od-name{color:var(--muted,#64748b);font-size:.77rem;margin-top:.12rem}
.od-product-name{max-width:32rem;overflow:hidden;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;color:var(--text,#334155);font-size:.78rem;font-weight:600;line-height:1.35;text-overflow:ellipsis}
.od-r{text-align:right}.od-c{text-align:center}
.od-total td{font-weight:800;color:var(--text,#172033);background:#f8fafc}
.od-fee-tab.active{background:#2563eb!important;border-color:#2563eb!important;color:#fff!important;box-shadow:0 3px 8px rgba(37,99,235,.16)}
.od-fee-panel{display:grid;gap:.45rem;margin-top:.6rem;padding:.75rem .8rem;background:var(--card-soft,#f8fafc);border:1px solid var(--line,#e7ebf0);border-radius:10px}
.od-section-caption{font-size:.73rem;color:var(--muted,#64748b);margin-top:.16rem}
.od-finance-card{height:100%;margin-bottom:0}
.od-finance-head{min-height:88px;padding:1.05rem 1.15rem}
.od-finance-head .od-title{font-size:1rem}
.od-finance-total{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:1.25rem;font-weight:850;letter-spacing:-.035em;color:#15803d;white-space:nowrap;font-variant-numeric:tabular-nums}
.od-finance-head-values{display:grid;justify-items:end;gap:.18rem;text-align:right}
.od-finance-cogs{display:flex;align-items:center;gap:.35rem;color:var(--muted,#64748b);font-size:.7rem;white-space:nowrap}
.od-finance-cogs strong{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:800;color:var(--text,#172033);font-variant-numeric:tabular-nums}
.od-finance-percent{font-size:.68em;font-weight:750;color:var(--muted,#64748b);white-space:nowrap}
.od-finance-body{display:grid;gap:.8rem}
.od-finance-group{display:grid;gap:.48rem}
.od-finance-group-title{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding-bottom:.35rem;border-bottom:1px solid #eef1f4;color:var(--text,#172033);font-size:.76rem;font-weight:800}
.od-finance-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;color:var(--muted,#64748b);font-size:.8rem}
.od-finance-row > :last-child{color:var(--text,#172033);font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:750;font-variant-numeric:tabular-nums;white-space:nowrap}
.od-finance-row-total{padding-top:.65rem;margin-top:.1rem;border-top:1px solid var(--line,#e7ebf0);font-weight:800;color:var(--text,#172033)}
.od-finance-row-total > :last-child{font-size:1.05rem;font-weight:850}
.od-finance-tabs{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.25rem;width:100%;padding:.25rem;background:var(--card-soft,#f8fafc);border:1px solid var(--line,#e7ebf0);border-radius:10px}
.od-finance-tabs .od-fee-tab{width:100%;min-width:0;border-color:transparent;background:transparent;min-height:36px;padding:.35rem .3rem;font-size:.7rem;line-height:1.2;text-align:center}
.od-finance-tabs .od-fee-tab:not(.active):hover{background:var(--card,#fff);border-color:var(--line,#e7ebf0)}
.od-settlement-address{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(0,1fr);gap:.75rem;padding:.85rem 1.15rem;border-bottom:1px solid var(--line,#eef1f4);background:var(--card-soft,#f8fafc)}
.od-settlement-address-label{font-size:.65rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--muted,#64748b);margin-bottom:.25rem}
.od-settlement-address-name{font-size:.82rem;font-weight:800;color:var(--text,#172033)}
.od-settlement-address-detail{font-size:.75rem;line-height:1.45;color:var(--muted,#64748b)}
.od-payment-summary{display:grid;gap:.5rem;margin-top:.85rem;padding-top:.85rem;border-top:1px solid var(--line,#eef1f4)}
.od-payment-summary-head{display:flex;align-items:center;justify-content:space-between;gap:.75rem}
.od-payment-summary-total{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.95rem;font-weight:850;color:var(--text,#172033);font-variant-numeric:tabular-nums;white-space:nowrap}
.od-finance-single{margin-bottom:.75rem}
.od-order-lifecycle{order:1}
.od-order-kpis{order:2}
.od-order-products{order:3}
.od-order-settlement{order:4}
.od-order-profitability{order:5}
.od-order-customer{order:6}
.od-order-meta{order:7}
body[data-theme="dark"] .od-topbar{background:color-mix(in srgb,var(--card,#11263f) 92%,transparent);box-shadow:0 8px 24px rgba(0,0,0,.18)}
body[data-theme="dark"] .od-kpi-icon{background:rgba(37,99,235,.2);color:#93c5fd}
body[data-theme="dark"] .od-table th{background:var(--card-soft,#132a45)}
body[data-theme="dark"] .od-table tbody tr:hover td{background:rgba(96,165,250,.06)}
body[data-theme="dark"] .od-status{background:rgba(148,163,184,.12);border-color:rgba(148,163,184,.22)}
body[data-theme="dark"] .od-finance-group-title,body[data-theme="dark"] .od-finance-row-total{border-color:var(--line,#1e3a5f)}
body[data-theme="dark"] .od-finance-tabs,body[data-theme="dark"] .od-fee-panel{background:rgba(19,42,69,.7);border-color:var(--line,#1e3a5f)}
@media(max-width:860px){
  .od-wrap{padding:.7rem .65rem 4rem}
  .od-topbar{padding:.65rem .7rem}
  .od-topbar-inner{flex-wrap:wrap}
  .od-identity{flex:1;min-width:150px}
  .od-topbar-store{order:5;width:100%;padding:.55rem 0 0;border-left:0;border-top:1px solid var(--line,#e7ebf0)}
  .od-topbar-store b{max-width:none}
  .od-grid,.od-grid-2{grid-template-columns:repeat(2,minmax(0,1fr));gap:.6rem}
  .od-kpi{padding:.8rem .85rem;min-height:104px}
  .od-kpi-head{margin-bottom:.6rem}
  .od-value,.od-value-text{font-size:.95rem}
  .od-head{padding:.8rem .85rem}
  .od-body{padding:.85rem}
  .od-table th,.od-table td{padding:.62rem .75rem}
  .od-flow::before{width:100%;margin:0 0 .1rem}
  .od-finance-tabs{grid-template-columns:repeat(3,minmax(0,1fr))}
}
@media(max-width:520px){
  .od-grid,.od-grid-2{grid-template-columns:1fr}
  .od-settlement-address{grid-template-columns:1fr}
  .od-finance-tabs{grid-template-columns:repeat(2,minmax(0,1fr))}
  .od-topbar .od-primary{flex:1}
  .od-flow{gap:.35rem}
  .od-sep{display:none}
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
    $settlement = $order->settlement;
    $settlementRaw = is_array($settlement?->raw_json ?? null) ? $settlement->raw_json : [];
    $inc = array_replace($liveData['income_details'] ?? [], $settlementRaw);
    $promotionBreakdown = $promotionBreakdown ?? [
        'product_discount' => 0,
        'voucher_store' => 0,
        'voucher_platform' => 0,
        'bundle_discount' => 0,
        'combo_hemat' => 0,
        'coin' => 0,
        'total_promotion' => 0,
    ];
    $settlementItems = (array) ($settlementRaw['items'] ?? []);
    $displayItems = $settlementItems ?: (array) ($liveData['item_list'] ?? []);
    $hppBySku = [];
    foreach (($order->items ?? collect()) as $costLine) {
        $costQty = max((float) ($costLine->qty ?? 0), 0);
        $costTotal = (float) ($costLine->hpp_total_snapshot ?? 0);
        $costUnit = max((float) ($costLine->hpp_snapshot ?? 0), (float) ($costLine->hpp_unit_snapshot ?? 0));
        if ($costTotal <= 0 && $costUnit > 0 && $costQty > 0) {
            $costTotal = $costUnit * $costQty;
        }
        if ($costUnit <= 0 && $costTotal > 0 && $costQty > 0) {
            $costUnit = $costTotal / $costQty;
        }
        foreach ([$costLine->model_sku, $costLine->item_sku, $costLine->external_sku] as $costSku) {
            $costSku = strtolower(trim((string) $costSku));
            if ($costSku !== '') {
                $hppBySku[$costSku] = ['unit' => $costUnit, 'total' => $costTotal];
            }
        }
    }
    $buyerShippingAmount = function (array $income, array $orderData, mixed $storedShipping): float {
        foreach ([$income, $orderData] as $source) {
            if (array_key_exists('buyer_paid_shipping_fee', $source)
                && $source['buyer_paid_shipping_fee'] !== ''
                && is_numeric($source['buyer_paid_shipping_fee'])) {
                return max((float) $source['buyer_paid_shipping_fee'], 0);
            }
        }

        if (array_key_exists('actual_shipping_fee', $orderData)
            && $orderData['actual_shipping_fee'] !== ''
            && is_numeric($orderData['actual_shipping_fee'])) {
            return max((float) $orderData['actual_shipping_fee'], 0);
        }

        $stored = is_numeric($storedShipping) ? (float) $storedShipping : 0.0;
        if ($stored > 0) {
            return $stored;
        }

        $rebate = (float) ($income['shopee_shipping_rebate'] ?? 0);
        $estimated = (float) ($orderData['estimated_shipping_fee']
            ?? $income['estimated_shipping_fee']
            ?? $storedShipping
            ?? 0);

        return $estimated > 0 ? max($estimated - $rebate, 0) : max($stored, 0);
    };

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

    $paymentSubtotal = (float) ($inc['order_discounted_price'] ?? $order->subtotal_items ?? 0);
    if ($paymentSubtotal <= 0 && isset($liveData['item_list']) && is_array($liveData['item_list'])) {
        foreach ($liveData['item_list'] as $paymentItem) {
            $paymentPrice = (float) ($paymentItem['model_discounted_price'] ?? $paymentItem['discounted_price'] ?? $paymentItem['model_original_price'] ?? $paymentItem['selling_price'] ?? 0);
            $paymentQty = (float) ($paymentItem['model_quantity_purchased'] ?? $paymentItem['quantity'] ?? 1);
            $paymentSubtotal += $paymentPrice * $paymentQty;
        }
    }
    if ($paymentSubtotal <= 0 && $order->items && $order->items->count() > 0) {
        foreach ($order->items as $paymentLine) {
            $paymentLineTotal = (float) ($paymentLine->line_gross_amount ?? 0);
            if ($paymentLineTotal <= 0) {
                $paymentLineTotal = (float) ($paymentLine->price ?? $paymentLine->price_original ?? 0) * (float) ($paymentLine->qty ?? 1);
            }
            $paymentSubtotal += $paymentLineTotal;
        }
    }
    $paymentVoucherPlatform = (float) ($inc['voucher_from_shopee'] ?? $inc['voucher_from_platform'] ?? $inc['platform_voucher'] ?? 0);
    $paymentVoucherStore = (float) ($inc['voucher_from_seller'] ?? $inc['seller_voucher_rebate'] ?? 0);
    $paymentCoins = (float) ($inc['coin'] ?? 0);
    $paymentShipping = $buyerShippingAmount($inc, $liveData, $order->shipping_fee_customer);
    $paymentTotal = (float) ($inc['buyer_total_amount'] ?? $inc['buyer_paid_amount'] ?? ($order->total_paid_customer > 0
        ? $order->total_paid_customer
        : ($liveData['total_amount'] ?? $order->total_amount ?? 0)));
    $paymentServiceFee = (float) ($inc['buyer_transaction_fee'] ?? 0);
    if ($paymentTotal > 0) {
        $paymentServiceFee = $paymentTotal - ($paymentSubtotal + $paymentShipping - $paymentVoucherPlatform - $paymentVoucherStore - $paymentCoins);
        if ($paymentServiceFee < 0) {
            $paymentServiceFee = (float) ($inc['buyer_transaction_fee'] ?? 0);
        }
    } else {
        $paymentServiceFee = $paymentServiceFee > 0 ? $paymentServiceFee : 2000;
        $paymentTotal = $paymentSubtotal + $paymentShipping - $paymentVoucherPlatform - $paymentVoucherStore - $paymentCoins + $paymentServiceFee;
    }

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
    $buyerUsername = $buyerUsername ?? ($order->buyer_username ?: $buyerName);
    $buyerPurchaseHistory = $buyerPurchaseHistory ?? [
        'count' => 1,
        'last_order_at' => $order->ordered_at ?: $order->order_date,
    ];
    $buyerPurchaseCount = max(1, (int) ($buyerPurchaseHistory['count'] ?? 1));
    $buyerLastPurchaseAt = $buyerPurchaseHistory['last_order_at'] ?? null;
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
    <div class="od-topbar-inner">
        <a href="{{ route('marketplace.orders') }}" class="od-btn od-btn-ghost"><i class="bi bi-arrow-left"></i><span>Kembali</span></a>
        <div class="od-identity">
            <div class="od-kicker">Marketplace order</div>
            <div class="od-code">{{ $order->channel_order_id ?: $order->external_order_id }}</div>
        </div>
        <span class="od-pill od-status {{ $statusText }}">{{ $statusLabel }}</span>
        <span class="od-spacer"></span>
        <div class="od-topbar-store">
            <div class="od-kicker">Toko{{ $order->store?->channel?->code ? ' · '.$order->store->channel->code : '' }}</div>
            <b>{{ $order->store?->name ?? '-' }}</b>
        </div>
        <a href="{{ route('sales.shipments.create', ['marketplace_order_id' => $order->id]) }}" class="od-btn od-primary"><i class="bi bi-box-seam"></i><span>Buat Shipment</span></a>
    </div>
</div>

<div class="od-wrap">
    <div class="od-flow od-order-lifecycle">
        <span class="od-step {{ $step >= 1 || $isCancel ? 'done' : 'active' }} {{ $isCancel ? 'cancel' : '' }}">Dibuat</span><span class="od-sep">-&gt;</span>
        <span class="od-step {{ $step >= 2 || $isCancel ? 'done' : ($step == 1 && !$isCancel ? 'active' : '') }} {{ $isCancel ? 'cancel' : '' }}">Dibayar</span><span class="od-sep">-&gt;</span>
        <span class="od-step {{ $step >= 3 || $isCancel ? 'done' : ($step == 2 && !$isCancel ? 'active' : '') }} {{ $isCancel ? 'cancel' : '' }}">Dikirim</span><span class="od-sep">-&gt;</span>
        <span class="od-step {{ $step >= 4 ? 'active' : ($isCancel ? 'cancel' : '') }}">{{ $isCancel ? 'Dibatalkan' : 'Selesai' }}</span>
    </div>

    <div class="od-grid od-order-kpis">
        <div class="od-card od-kpi">
            <div class="od-kpi-head"><span class="od-kpi-icon"><i class="bi bi-credit-card-2-front"></i></span><div class="od-label">Metode Bayar</div></div>
            <div class="od-value-text">{{ $payMethod }}</div>
        </div>
        <div class="od-card od-kpi">
            <div class="od-kpi-head"><span class="od-kpi-icon"><i class="bi bi-truck"></i></span><div class="od-label">Kurir</div></div>
            <div class="od-value-text">{{ $carrier }}</div>
        </div>
        <div class="od-card od-kpi">
            <div class="od-kpi-head"><span class="od-kpi-icon"><i class="bi bi-upc-scan"></i></span><div class="od-label">Nomor Resi</div></div>
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
            <div class="od-kpi-head"><span class="od-kpi-icon"><i class="bi bi-wallet2"></i></span><div class="od-label">Total Pembayaran</div></div>
            <div class="od-value">Rp{{ number_format($liveData['total_amount'] ?? $order->total_paid_customer ?? $order->total_amount ?? 0, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="od-grid-2 od-order-meta">
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
                    <div class="od-payment-summary">
                        <div class="od-payment-summary-head">
                            <span class="od-kicker">Customer payment</span>
                            <span class="od-payment-summary-total">Rp{{ number_format($paymentTotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="od-finance-row">
                            <span class="od-muted">Subtotal Pesanan</span>
                            <span class="od-code-cell" style="font-size:.85rem">Rp{{ number_format($paymentSubtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="od-finance-row">
                            <span class="od-muted">Ongkos Kirim</span>
                            <span class="od-code-cell" style="font-size:.85rem">Rp{{ number_format($paymentShipping, 0, ',', '.') }}</span>
                        </div>
                        <div class="od-finance-row">
                            <span class="od-muted">Voucher Platform</span>
                            <span class="od-code-cell" style="font-size:.85rem">{{ $paymentVoucherPlatform > 0 ? '-' : '' }}Rp{{ number_format($paymentVoucherPlatform, 0, ',', '.') }}</span>
                        </div>
                        <div class="od-finance-row">
                            <span class="od-muted">Voucher Toko</span>
                            <span class="od-code-cell" style="font-size:.85rem">{{ $paymentVoucherStore > 0 ? '-' : '' }}Rp{{ number_format($paymentVoucherStore, 0, ',', '.') }}</span>
                        </div>
                        <div class="od-finance-row">
                            <span class="od-muted">Koin Shopee Ditukarkan</span>
                            <span class="od-code-cell" style="font-size:.85rem">{{ $paymentCoins > 0 ? '-' : '' }}Rp{{ number_format($paymentCoins, 0, ',', '.') }}</span>
                        </div>
                        <div class="od-finance-row">
                            <span class="od-muted">Biaya Layanan & Penanganan</span>
                            <span class="od-code-cell" style="font-size:.85rem">Rp{{ number_format($paymentServiceFee, 0, ',', '.') }}</span>
                        </div>
                        <div class="od-finance-row od-finance-row-total">
                            <span>Total Pembayaran Pembeli</span>
                            <span>Rp{{ number_format($paymentTotal, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="od-card">
                <div class="od-head">
                    <div class="od-title">Rincian Promosi</div>
                </div>
                <div class="od-body od-list">
                    @php
                        $promoVoucherPlatform = (float) ($promotionBreakdown['voucher_platform'] ?? 0);
                        $promoVoucherToko = (float) ($promotionBreakdown['voucher_store'] ?? 0);
                        $promoDiskonProduk = (float) ($promotionBreakdown['product_discount'] ?? 0);
                        $promoPaketDiskon = (float) ($promotionBreakdown['bundle_discount'] ?? 0);
                        $promoKomboHemat = (float) ($promotionBreakdown['combo_hemat'] ?? 0);
                        $promoKoinShopee = (float) ($promotionBreakdown['coin'] ?? $inc['coin'] ?? 0);
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
                        $promoOngkir = $buyerShippingAmount($inc, $liveData, $order->shipping_fee_customer);
                        $promoBiayaLayanan = (float)($inc['buyer_transaction_fee'] ?? max($promoBuyerPaid - $promoSubtotal - $promoOngkir - $promoVoucherPlatform - $promoVoucherToko - $promoKoinShopee, 0));
                        $promoTotal = (float) ($promotionBreakdown['total_promotion'] ?? ($promoDiskonProduk + $promoVoucherToko + $promoVoucherPlatform + $promoPaketDiskon + $promoKomboHemat));
                    @endphp
                    <div style="display:flex; justify-content:space-between">
                        <span class="od-muted">Buyer Paid</span>
                        <span class="od-code-cell" style="font-size:.85rem; font-weight:900; color:#111827">Rp{{ number_format($promoBuyerPaid, 0, ',', '.') }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between">
                        <span class="od-muted">Diskon Produk</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#991b1b">-Rp{{ number_format($promoDiskonProduk, 0, ',', '.') }}</span>
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
                        <span class="od-muted">Paket Diskon</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#991c1b">-Rp{{ number_format($promoPaketDiskon, 0, ',', '.') }}</span>
                    </div>
                    <div style="display:flex; justify-content:space-between">
                        <span class="od-muted">Kombo Hemat</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#991c1b">-Rp{{ number_format($promoKomboHemat, 0, ',', '.') }}</span>
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
                        <span class="od-muted">Total Promosi</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#b91c1c">-Rp{{ number_format($promoTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
    </div>

    <div class="od-card od-order-products">
        <div class="od-head">
            <div class="od-title">Rincian Produk</div>
            <span class="od-pill">{{ count($displayItems ?: ($order->items ?? [])) }} Item</span>
        </div>
        <div class="od-table-wrap">
            <table class="od-table">
                <thead>
                    <tr>
                        <th class="od-c" style="width:3.5rem">No.</th>
                        <th>SKU</th>
                        <th>Produk</th>
                        <th class="od-c">Harga Satuan</th>
                        <th class="od-c">Qty</th>
                        <th class="od-r">HPP / Unit</th>
                        <th class="od-r">Total HPP</th>
                        <th class="od-r">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $items = $displayItems;
                        $fallbackItems = $order->items;
                        $subtotalItems = 0;
                        $itemNumber = 0;
                    @endphp
                    
                    @if(!empty($items))
                        @foreach($items as $item)
                        @php 
                            $qty = (float) ($item['quantity_purchased'] ?? $item['model_quantity_purchased'] ?? $item['quantity'] ?? 1);
                            $qty = $qty > 0 ? $qty : 1;
                            $lineTotal = (float) ($item['discounted_price'] ?? 0);
                            $isSettlementItem = array_key_exists('discounted_price', $item) || array_key_exists('selling_price', $item);
                            if ($isSettlementItem && $lineTotal <= 0) {
                                $lineTotal = (float) ($item['selling_price'] ?? 0);
                            }
                            if ($isSettlementItem && $lineTotal > 0) {
                                $price = $lineTotal / $qty;
                            } else {
                                $price = (float) ($item['model_discounted_price'] ?? 0);
                                if ($price <= 0) {
                                    $price = (float) ($item['model_original_price'] ?? $item['selling_price'] ?? 0);
                                }
                                $lineTotal = $price * $qty;
                            }
                            $subtotalItems += $lineTotal;
                            $itemSku = trim((string) ($item['model_sku'] ?? $item['item_sku'] ?? ''));
                            $itemHpp = $hppBySku[strtolower($itemSku)] ?? null;
                            $itemNumber++;
                        @endphp
                        <tr>
                            <td class="od-c od-muted">{{ $itemNumber }}</td>
                            <td class="od-code-cell"><span class="od-sku-cell" title="{{ $itemSku ?: '-' }}">{{ $itemSku ?: '-' }}</span></td>
                            <td>
                                <div class="od-product-name" title="{{ $item['item_name'] ?? 'Unknown Item' }}">{{ $item['item_name'] ?? 'Unknown Item' }}</div>
                                <div class="od-name">Var: {{ $item['model_name'] ?? '-' }}</div>
                            </td>
                            <td class="od-c">{{ number_format($price, 0, ',', '.') }}</td>
                            <td class="od-c od-code-cell">{{ $qty }}</td>
                            <td class="od-r od-code-cell">{{ $itemHpp && $itemHpp['unit'] > 0 ? 'Rp'.number_format($itemHpp['unit'], 0, ',', '.') : '—' }}</td>
                            <td class="od-r od-code-cell">{{ $itemHpp && $itemHpp['total'] > 0 ? 'Rp'.number_format($itemHpp['total'], 0, ',', '.') : '—' }}</td>
                            <td class="od-r od-code-cell">{{ number_format($lineTotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    @elseif($fallbackItems && $fallbackItems->count() > 0)
                        @foreach($fallbackItems as $line)
                        @php 
                            $price = $line->price ?? $line->price_original ?? 0;
                            $qty = (int) $line->qty;
                            $subtotal = $price * $qty;
                            $subtotalItems += $line->line_gross_amount > 0 ? $line->line_gross_amount : $subtotal;
                            $lineHppUnit = max((float) ($line->hpp_snapshot ?? 0), (float) ($line->hpp_unit_snapshot ?? 0));
                            $lineHppTotal = (float) ($line->hpp_total_snapshot ?? 0);
                            if ($lineHppTotal <= 0 && $lineHppUnit > 0) {
                                $lineHppTotal = $lineHppUnit * max($qty, 0);
                            }
                            $itemNumber++;
                        @endphp
                        <tr>
                            <td class="od-c od-muted">{{ $itemNumber }}</td>
                            @php $lineSku = $line->model_sku ?? $line->item_sku ?? $line->external_sku ?? '-'; @endphp
                            <td class="od-code-cell"><span class="od-sku-cell" title="{{ $lineSku }}">{{ $lineSku }}</span></td>
                            <td>
                                <div class="od-product-name" title="{{ $line->item_name ?? $line->item_name_snapshot ?? '-' }}">{{ $line->item_name ?? $line->item_name_snapshot ?? '-' }}</div>
                                @if($line->variant_name || $line->variant_snapshot)
                                <div class="od-name">Var: {{ $line->variant_name ?? $line->variant_snapshot }}</div>
                                @endif
                            </td>
                            <td class="od-c">{{ number_format($price, 0, ',', '.') }}</td>
                            <td class="od-c od-code-cell">{{ $qty }}</td>
                            <td class="od-r od-code-cell">{{ $lineHppUnit > 0 ? 'Rp'.number_format($lineHppUnit, 0, ',', '.') : '—' }}</td>
                            <td class="od-r od-code-cell">{{ $lineHppTotal > 0 ? 'Rp'.number_format($lineHppTotal, 0, ',', '.') : '—' }}</td>
                            <td class="od-r od-code-cell">{{ number_format($line->line_gross_amount > 0 ? $line->line_gross_amount : $subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="8" class="od-c" style="padding:2rem; color:#94a3b8">Tidak ada data produk</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="od-card od-shipping-card od-order-customer">
        <div class="od-head">
            <div>
                <div class="od-kicker">Customer profile</div>
                <div class="od-title">Alamat Pengiriman</div>
            </div>
            <span class="od-pill"><i class="bi bi-bag-check"></i> {{ $buyerPurchaseCount }}x pembelian</span>
        </div>
        <div class="od-settlement-address">
            <div>
                <div class="od-settlement-address-label">User</div>
                <div class="od-settlement-address-name">{{ $buyerUsername }}</div>
                <div class="od-settlement-address-detail">{{ $buyerName }} · {{ $buyerPhone }}</div>
                <div class="od-settlement-address-label" style="margin-top:.7rem">Riwayat Pembelian</div>
                <div class="od-settlement-address-name">Pembelian ke-{{ $buyerPurchaseCount }}</div>
                <div class="od-settlement-address-detail">
                    Terakhir {{ $buyerLastPurchaseAt ? id_datetime($buyerLastPurchaseAt) : 'Belum ada data' }}
                </div>
            </div>
            <div class="od-settlement-address-detail">
                <div class="od-settlement-address-label">Alamat</div>
                {{ $buyerAddress }}
                @if($zipcode) <br>{{ $zipcode }} @endif
                @if($order->remarks)
                    <br><strong style="color:#92400e">Catatan:</strong> {{ $order->remarks }}
                @endif
            </div>
        </div>
    </div>

    @php
        // Seller Income Data
        // Subtotal = Harga Produk Setelah Diskon (dari loop item_list atau fallback database)
        $subtotal = (float)($inc['order_discounted_price'] ?? $order->subtotal_items ?? ($subtotalItems > 0 ? $subtotalItems : 0));
        
        $ongkirRebate = (float)($inc['shopee_shipping_rebate'] ?? 0);
        $ongkirDibayarPembeli = $buyerShippingAmount($inc, $liveData, $order->shipping_fee_customer);
        
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

        $estimatedAdminFee = $isEstimasi;
        $buildFeeRows($sellerFeeBreakdown, $estimatedAdminFee ? 'Estimasi Fee Marketplace' : 'Biaya Administrasi', $feeValue('commission_fee', $biayaAdmin));
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

        $hppSummary = $hppSummary ?? ['total' => 0, 'complete' => false, 'missing_lines' => 0];
        $adsDaily = $adsDaily ?? null;
        $adsDate = $adsDate ?? null;
        $hppTotal = (float) ($hppSummary['total'] ?? 0);
        $hppComplete = (bool) ($hppSummary['complete'] ?? false);
        $dailyAdsSpend = $adsDaily ? (float) ($adsDaily->spend ?? 0) : null;
        $dailyAdsGmv = $adsDaily ? (float) ($adsDaily->gmv ?? 0) : null;
        $dailyAcos = $dailyAdsGmv > 0
            ? $dailyAdsSpend / $dailyAdsGmv
            : (($adsDaily && (float) ($adsDaily->roas ?? 0) > 0) ? 1 / (float) $adsDaily->roas : null);
        // ACOS adalah rasio biaya iklan terhadap GMV harian. Beban order
        // dialokasikan memakai subtotal produk order pada tanggal tersebut.
        $adTaxRate = 0.11;
        $orderAdCostBeforeTax = $dailyAcos !== null ? round(max($subtotal, 0) * $dailyAcos, 2) : null;
        $orderAdTax = $orderAdCostBeforeTax !== null ? round($orderAdCostBeforeTax * $adTaxRate, 2) : null;
        $orderAdCost = $orderAdCostBeforeTax !== null ? round($orderAdCostBeforeTax + $orderAdTax, 2) : null;
        $grossProfit = $hppComplete ? round($penghasilan - $hppTotal, 2) : null;
        $profitAfterAds = $grossProfit !== null && $orderAdCost !== null
            ? round($grossProfit - $orderAdCost, 2)
            : null;
        $marginBase = max($subtotal, 0);
        $cogsPct = $hppComplete && $marginBase > 0 ? round(($hppTotal / $marginBase) * 100, 2) : null;
        $adPct = $orderAdCost !== null && $marginBase > 0 ? round(($orderAdCost / $marginBase) * 100, 2) : null;
        $marketplaceFeeTotal = (float) $grandFeeBreakdownTotal;
        $sellerFeePct = $marginBase > 0 ? round(($sellerFeeBreakdownTotal / $marginBase) * 100, 2) : null;
        $grossProfitPct = $grossProfit !== null && $marginBase > 0 ? round(($grossProfit / $marginBase) * 100, 2) : null;
    @endphp

    <div class="od-card od-order-profitability">
        <div class="od-head">
            <div>
                <div class="od-title">Profitabilitas Pesanan</div>
                <div class="od-muted" style="margin-top:.15rem">Gross profit = penghasilan akhir − HPP, sebelum biaya iklan.</div>
            </div>
        </div>
        <div class="od-body">
            <div class="od-grid" style="grid-template-columns:repeat(4,minmax(0,1fr)); margin-bottom:.35rem">
                <div>
                    <div class="od-label">HPP</div>
                    <div class="od-value" style="color:{{ $hppComplete ? '#111827' : '#b45309' }}">
                        {{ $hppComplete ? 'Rp'.number_format($hppTotal, 0, ',', '.') : 'Belum lengkap' }}
                    </div>
                </div>
                <div>
                    <div class="od-label">ACOS {{ $adsDate ? $adsDate : 'hari order' }}</div>
                    <div class="od-value">{{ $dailyAcos !== null ? number_format($dailyAcos * 100, 2, ',', '.').'%' : '—' }}</div>
                </div>
                <div>
                    <div class="od-label">Biaya Iklan Order + PPN 11%</div>
                    <div class="od-value">{{ $orderAdCost !== null ? 'Rp'.number_format($orderAdCost, 0, ',', '.') : '—' }}</div>
                </div>
                <div>
                    <div class="od-label">Gross Profit</div>
                    <div class="od-value" style="color:{{ $grossProfit !== null && $grossProfit < 0 ? '#b91c1c' : '#166534' }}">
                        {{ $grossProfit !== null ? 'Rp'.number_format($grossProfit, 0, ',', '.') : 'Belum tersedia' }}
                    </div>
                </div>
            </div>
            <div class="od-muted" style="font-size:.72rem">
                @if($adsDaily && $dailyAcos !== null)
                    Iklan dihitung dari subtotal produk × ACOS harian toko ({{ $adsDate }}): Rp{{ number_format($subtotal, 0, ',', '.') }} × {{ number_format($dailyAcos * 100, 2, ',', '.') }}% = Rp{{ number_format($orderAdCostBeforeTax, 0, ',', '.') }}, lalu ditambah PPN 11% Rp{{ number_format($orderAdTax, 0, ',', '.') }}.
                @else
                    Data ACOS harian toko untuk tanggal order belum tersedia, sehingga biaya iklan order belum dihitung.
                @endif
                @if($hppComplete && $orderAdCost !== null)
                    <span style="display:block; margin-top:.15rem">Profit setelah iklan: <strong style="color:{{ $profitAfterAds < 0 ? '#b91c1c' : '#166534' }}">Rp{{ number_format($profitAfterAds, 0, ',', '.') }}</strong>.</span>
                @endif
            </div>
        </div>
    </div>

    <div class="od-finance-single od-order-settlement">
        <div class="od-card od-finance-card">
            <div class="od-head od-finance-head">
                <div>
                    <div class="od-kicker">Seller settlement</div>
                    <div class="od-title">{{ $isEstimasi ? 'Estimasi ' : '' }}Penghasilan Akhir</div>
                    @if($isEstimasi)
                        <div class="od-section-caption">
                            Berdasarkan rata-rata fee historis toko ini ({{ number_format($estimatedFeePct, 1, ',', '.') }}%).
                        </div>
                    @endif
                </div>
                <div class="od-finance-head-values">
                    <div class="od-finance-total">Rp{{ number_format($penghasilan, 0, ',', '.') }}</div>
                    <div class="od-finance-cogs">
                        <span>Total COGS</span>
                        <strong style="color:{{ $hppComplete ? 'var(--text,#172033)' : '#b45309' }}">
                            {{ $hppComplete ? 'Rp'.number_format($hppTotal, 0, ',', '.') : 'Belum lengkap' }}
                            @if($cogsPct !== null)<span class="od-finance-percent">({{ number_format($cogsPct, 2, ',', '.') }}%)</span>@endif
                        </strong>
                    </div>
                    <div class="od-finance-cogs">
                        <span>Biaya iklan / order + PPN 11%</span>
                        <strong style="color:{{ $orderAdCost !== null ? 'var(--text,#172033)' : '#b45309' }}">
                            {{ $orderAdCost !== null ? 'Rp'.number_format($orderAdCost, 0, ',', '.') : 'Belum tersedia' }}
                            @if($adPct !== null)<span class="od-finance-percent">({{ number_format($adPct, 2, ',', '.') }}%)</span>@endif
                        </strong>
                    </div>
                </div>
            </div>
            <div class="od-body od-finance-body">
                <div class="od-finance-group">
                    <div class="od-finance-group-title"><span>Pendapatan produk</span><span>Rp{{ number_format($subtotal, 0, ',', '.') }}</span></div>
                    <div class="od-finance-row">
                        <span class="od-muted">Harga Produk</span>
                        <span class="od-code-cell" style="font-size:.85rem">Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="od-finance-group">
                    <div class="od-finance-group-title"><span>Dampak ongkos kirim</span><span>{{ $subtotalOngkir < 0 ? '-Rp' : 'Rp' }}{{ number_format(abs($subtotalOngkir), 0, ',', '.') }}</span></div>
                    <div class="od-finance-row">
                        <span class="od-muted">Ongkir dibayar pembeli</span>
                        <span class="od-code-cell" style="font-size:.85rem">Rp{{ number_format($ongkirDibayarPembeli, 0, ',', '.') }}</span>
                    </div>
                    <div class="od-finance-row">
                        <span class="od-muted">Ongkos Kirim yang Dibayarkan ke Jasa Kirim</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#b91c1c">-Rp{{ number_format($ongkosJasaKirim, 0, ',', '.') }}</span>
                    </div>
                    <div class="od-finance-row">
                        <span class="od-muted">Potongan Ongkos Kirim dari Shopee</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#15803d">Rp{{ number_format($potonganOngkir, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="od-finance-group">
                    <div class="od-finance-group-title">
                        <span>Rincian Biaya Marketplace</span>
                        <span style="color:#b91c1c">
                            -Rp{{ number_format($marketplaceFeeTotal, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="od-finance-tabs">
                        <button type="button" class="od-btn od-fee-tab active" data-fee-tab="seller">Beban Seller</button>
                        <button type="button" class="od-btn od-fee-tab" data-fee-tab="voucher">Voucher</button>
                        <button type="button" class="od-btn od-fee-tab" data-fee-tab="buyer">Beban Pembeli</button>
                        <button type="button" class="od-btn od-fee-tab" data-fee-tab="platform">Beban Platform</button>
                        <button type="button" class="od-btn od-fee-tab" data-fee-tab="adjustment">Penyesuaian</button>
                    </div>

                <div class="od-fee-panel" data-fee-panel="seller">
                    <div style="font-weight:700; color:#334155; margin-bottom:.1rem">
                        Total Beban Seller
                        <span style="float:right; color:#b91c1c">
                            -Rp{{ number_format($sellerFeeBreakdownTotal, 0, ',', '.') }}
                            @if($sellerFeePct !== null)<small class="od-finance-percent">({{ number_format($sellerFeePct, 2, ',', '.') }}%)</small>@endif
                        </span>
                    </div>
                    @forelse($sellerFeeBreakdown as $feeRow)
                    @php $feeRowPct = $marginBase > 0 ? round(($feeRow['amount'] / $marginBase) * 100, 2) : null; @endphp
                    <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                        <span class="od-muted">{{ $feeRow['label'] }}</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#b91c1c">
                            -Rp{{ number_format($feeRow['amount'], 0, ',', '.') }}
                            @if($feeRowPct !== null)<small class="od-finance-percent">({{ number_format($feeRowPct, 2, ',', '.') }}%)</small>@endif
                        </span>
                    </div>
                    @empty
                    <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                        <span class="od-muted">Belum ada rincian beban seller</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#94a3b8">—</span>
                    </div>
                    @endforelse
                    @if($isEstimasi && $sellerFeeBreakdownTotal <= 0)
                    <div style="display:flex; justify-content:space-between; margin-left:.5rem">
                        <span class="od-muted">Estimasi Fee Marketplace</span>
                        <span class="od-code-cell" style="font-size:.85rem; color:#b91c1c">
                            -Rp{{ number_format($estimatedMarketplaceFee, 0, ',', '.') }}
                            @if($marginBase > 0)<small class="od-finance-percent">({{ number_format(($estimatedMarketplaceFee / $marginBase) * 100, 2, ',', '.') }}%)</small>@endif
                        </span>
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
                </div>

                <div class="od-finance-row od-finance-row-total">
                    <span>{{ $isEstimasi ? 'Estimasi Laba Kotor' : 'Laba Kotor' }}</span>
                    <span style="color:{{ $grossProfit === null ? '#b45309' : ($grossProfit < 0 ? '#b91c1c' : '#15803d') }}">
                        {{ $grossProfit !== null ? 'Rp'.number_format($grossProfit, 0, ',', '.') : 'Belum lengkap' }}
                        @if($grossProfitPct !== null)<small class="od-finance-percent">({{ number_format($grossProfitPct, 2, ',', '.') }}%)</small>@endif
                    </span>
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
