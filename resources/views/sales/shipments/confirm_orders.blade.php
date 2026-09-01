{{-- resources/views/sales/shipments/confirm_orders.blade.php --}}
@extends('layouts.app')

@section('title', 'Konfirmasi Shipment · ' . $shipment->code)

@push('head')
<style>
.sd-wrap{max-width:1040px;margin-inline:auto;padding:.7rem .75rem 1.5rem}
.sd-topbar{position:sticky;top:0;z-index:250;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;padding:.55rem .75rem;background:var(--card,#fff);border-bottom:1px solid rgba(148,163,184,.18)}
.sd-code{font-weight:900;font-size:1rem;color:#111827}
.sd-spacer{flex:1}
.sd-btn,.sd-pill{display:inline-flex;align-items:center;justify-content:center;gap:.35rem;border-radius:7px;border:1px solid rgba(148,163,184,.3);background:transparent;color:#475569;text-decoration:none;font-size:.76rem;padding:.28rem .6rem;min-height:34px}
.sd-btn{font-weight:800;cursor:pointer}.sd-btn:disabled{opacity:.45;cursor:not-allowed;pointer-events:none}
.sd-btn:hover{background:rgba(148,163,184,.09);color:#111827;text-decoration:none}
.sd-primary{background:#334155!important;border-color:#334155!important;color:#fff!important}
.sd-link{color:#1d4ed8;border-color:rgba(37,99,235,.35);background:rgba(239,246,255,.7)}
.sd-link:hover{color:#1e40af;background:rgba(219,234,254,.9)}
.sd-danger{color:#991b1b;border-color:rgba(153,27,27,.25);background:transparent}
.sd-status{font-weight:850;color:#334155;background:rgba(148,163,184,.08)}
.sd-flash{max-width:1040px;margin:.65rem auto 0;padding:.65rem .8rem;border:1px solid rgba(37,99,235,.2);border-radius:8px;background:#eff6ff;color:#1e40af;font-size:.78rem;font-weight:700}
.sd-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:8px;overflow:hidden;margin-bottom:.65rem}
.sd-muted{color:#64748b;font-size:.8rem}
.sd-head{display:flex;align-items:center;gap:.55rem;justify-content:space-between;padding:.7rem .85rem;border-bottom:1px solid rgba(148,163,184,.12)}
.sd-title{font-weight:900;color:#334155}
.sd-body{padding:.75rem .85rem}
.sd-order-search{padding:.6rem .85rem;border-bottom:1px solid rgba(148,163,184,.12)}.sd-order-search input{width:100%;min-height:36px;border:1px solid rgba(148,163,184,.3);border-radius:7px;padding:.35rem .6rem;color:#334155;background:var(--card,#fff);font-size:.78rem}.sd-order-search input:focus{outline:0;border-color:#334155;box-shadow:0 0 0 .16rem rgba(51,65,85,.1)}
.sd-list{display:grid;gap:.45rem}
.sd-order{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:.65rem;border:1px solid rgba(148,163,184,.18);border-radius:8px;padding:.58rem .65rem}
.sd-order-no{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827;word-break:break-word}
.sd-order-num{display:inline-flex;align-items:center;justify-content:center;min-width:1.5rem;height:1.5rem;padding:0 .35rem;border-radius:6px;background:rgba(148,163,184,.12);color:#475569;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;font-size:.78rem;flex-shrink:0}
.sd-order-lead{display:flex;align-items:flex-start;gap:.55rem;min-width:0}
.sd-order-group{display:block;padding:.58rem .65rem}
.sd-order-group .sd-order-lead{justify-content:space-between}
.sd-order-qty{margin-left:auto;min-width:42px;text-align:center;border-radius:999px;padding:.2rem .45rem;background:rgba(148,163,184,.12);color:#334155;font-size:.76rem;font-weight:900}
.sd-order-items{margin:.55rem 0 0 2.05rem;padding:.2rem .6rem 0;border-top:1px solid rgba(148,163,184,.14)}
.sd-order-item{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.6rem;align-items:center;padding:.48rem 0;border-bottom:1px solid rgba(148,163,184,.1)}
.sd-order-item:last-child{border-bottom:0}
.sd-order-item-code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827}
.sd-order-item-name{color:#64748b;font-size:.76rem;margin-top:.06rem}
.sd-order-item-qty{color:#334155;font-size:.8rem;font-weight:900}
.sd-marketplace-detail{margin:.7rem 0 0 2.05rem;padding:.7rem .75rem;border:1px solid rgba(37,99,235,.16);border-radius:8px;background:rgba(239,246,255,.55)}
.sd-marketplace-head{display:flex;align-items:flex-start;justify-content:space-between;gap:.6rem;flex-wrap:wrap;margin-bottom:.55rem}
.sd-marketplace-title{font-size:.72rem;font-weight:900;text-transform:uppercase;letter-spacing:.04em;color:#1e40af}
.sd-marketplace-meta{display:flex;gap:.4rem .75rem;flex-wrap:wrap;margin-top:.15rem;color:#64748b;font-size:.72rem}
.sd-marketplace-actions{display:flex;gap:.35rem;flex-wrap:wrap}
.sd-marketplace-actions a{min-height:28px;padding:.18rem .5rem;font-size:.7rem}
.sd-marketplace-table{width:100%;border-collapse:collapse}
.sd-marketplace-table th,.sd-marketplace-table td{padding:.38rem .4rem;border-bottom:1px solid rgba(37,99,235,.1);font-size:.73rem;vertical-align:middle}
.sd-marketplace-table th{text-align:left;color:#64748b;font-size:.64rem;text-transform:uppercase;letter-spacing:.03em}
.sd-marketplace-table td{color:#334155}.sd-marketplace-table tr:last-child td{border-bottom:0}
.sd-marketplace-table .sd-marketplace-code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#0f172a}
.sd-marketplace-table .sd-marketplace-number{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
.sd-marketplace-short{color:#b91c1c;font-weight:900}.sd-marketplace-ok{color:#15803d;font-weight:900}
body[data-theme="dark"] .sd-marketplace-detail{background:rgba(30,64,175,.14);border-color:rgba(96,165,250,.35)}
body[data-theme="dark"] .sd-marketplace-title{color:#bfdbfe}body[data-theme="dark"] .sd-marketplace-table td{color:#cbd5e1}body[data-theme="dark"] .sd-marketplace-table .sd-marketplace-code{color:#f1f5f9}
.sd-badge{display:inline-flex;align-items:center;justify-content:center;border-radius:7px;border:1px solid rgba(148,163,184,.25);padding:.16rem .48rem;font-size:.69rem;font-weight:900;color:#64748b;white-space:nowrap}
.sd-badge.pending{background:rgba(245,158,11,.08);border-color:rgba(245,158,11,.25);color:#92400e}
.sd-empty{padding:1.6rem 1rem;text-align:center;color:#64748b;font-size:.84rem}
.sd-stock-warning{margin-bottom:.65rem;border:1px solid rgba(220,38,38,.24);border-radius:8px;background:rgba(254,242,242,.72);overflow:hidden}
.sd-stock-head{padding:.65rem .75rem;border-bottom:1px solid rgba(220,38,38,.16);color:#991b1b;font-size:.8rem;font-weight:900}
.sd-stock-table-wrap{overflow:auto}.sd-stock-table{width:100%;border-collapse:collapse}.sd-stock-table th,.sd-stock-table td{padding:.5rem .65rem;border-bottom:1px solid rgba(220,38,38,.12);font-size:.78rem}.sd-stock-table th{text-align:left;color:#991b1b;font-size:.68rem;text-transform:uppercase;letter-spacing:.02em;background:rgba(254,226,226,.45)}.sd-stock-table td{color:#475569}.sd-stock-table tr:last-child td{border-bottom:0}.sd-stock-table .sd-stock-code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827}.sd-stock-table .sd-stock-number{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}.sd-stock-table .sd-stock-short{color:#dc2626;font-weight:900}
@media(max-width:860px){
  .sd-wrap{padding:.5rem .5rem 1.5rem}.sd-topbar{padding:.5rem}.sd-code{flex:1;min-width:150px;font-size:1.02rem}.sd-head{padding:.65rem .7rem}.sd-body{padding:.65rem .7rem}.sd-order{grid-template-columns:1fr;align-items:flex-start;gap:.4rem}
}
.sd-topbar .sd-topbar-nav { white-space:nowrap; }
.sd-inline-form { display:contents; }
.sd-topbar .sd-topbar-primary { white-space:nowrap; }
@media(max-width:860px){
  .sd-topbar .sd-topbar-nav { min-height:34px; padding-inline:.5rem; }
  .sd-topbar .sd-topbar-primary { width:100%; order:5; }
}

/* Visual hierarchy: the submit action must be the clearest control. */
.sd-topbar .sd-topbar-primary {
  color:#fff!important;
  background:#2563eb!important;
  border-color:#2563eb!important;
  font-weight:900;
  box-shadow:0 3px 10px rgba(37,99,235,.28);
}
.sd-topbar .sd-topbar-primary:hover:not(:disabled) {
  color:#fff!important;
  background:#1d4ed8!important;
  border-color:#1d4ed8!important;
}
.sd-topbar .sd-topbar-primary:disabled {
  color:#64748b!important;
  background:#e2e8f0!important;
  border-color:#cbd5e1!important;
  box-shadow:none;
}
.sd-topbar .sd-status {
  color:#9a3412!important;
  background:#fff7ed!important;
  border-color:#fdba74!important;
}
.sd-topbar .sd-pill:not(.sd-status) b { color:#1d4ed8; }
.sd-order-search input:focus {
  border-color:#2563eb;
  box-shadow:0 0 0 .16rem rgba(37,99,235,.14);
}
.sd-title { color:#0f172a; font-size:.9rem; }
.sd-order-no { color:#0f172a; }
.sd-tabs { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:.35rem; margin-bottom:.65rem; padding:.3rem; border:1px solid rgba(148,163,184,.2); border-radius:9px; background:var(--card,#fff); }
.sd-tabs.has-unlinked { grid-template-columns:repeat(4, minmax(0, 1fr)); }
.sd-tab-btn { display:inline-flex; align-items:center; justify-content:center; gap:.35rem; min-height:38px; border:0; border-radius:7px; color:#64748b; background:transparent; font-size:.76rem; font-weight:850; cursor:pointer; }
.sd-tab-btn:hover { color:#1d4ed8; background:#eff6ff; }
.sd-tab-btn.active { color:#fff; background:#2563eb; box-shadow:0 3px 9px rgba(37,99,235,.22); }
.sd-tab-panel[hidden] { display:none!important; }
.sd-item-list { display:grid; gap:.45rem; }
.sd-item-row { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:.65rem; align-items:center; padding:.6rem .65rem; border:1px solid rgba(148,163,184,.18); border-radius:8px; }
.sd-item-code { color:#0f172a; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:.8rem; font-weight:900; }
.sd-item-name { margin-top:.1rem; color:#64748b; font-size:.74rem; }
.sd-item-map { margin-top:.25rem; color:#2563eb; font-size:.68rem; font-weight:800; }
.sd-item-qty { min-width:48px; padding:.22rem .45rem; border-radius:999px; color:#334155; background:rgba(148,163,184,.12); font-size:.76rem; font-weight:900; text-align:center; }
.sd-rekon-summary { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:.5rem; margin-bottom:.7rem; }
.sd-rekon-stat { padding:.65rem; border:1px solid rgba(148,163,184,.18); border-radius:8px; background:rgba(248,250,252,.72); }
.sd-rekon-stat-label { color:#64748b; font-size:.68rem; }
.sd-rekon-stat-value { margin-top:.15rem; color:#0f172a; font-size:1.1rem; font-weight:900; }
.sd-rekon-message { padding:.7rem; border:1px solid rgba(37,99,235,.18); border-radius:8px; color:#1e40af; background:#eff6ff; font-size:.78rem; line-height:1.45; }
.sd-direct-rekon { margin:.7rem .85rem 0; padding:.75rem; border:1px solid rgba(37,99,235,.22); border-radius:9px; background:rgba(239,246,255,.55); }
.sd-direct-rekon-head { display:flex; align-items:flex-start; justify-content:space-between; gap:.6rem; flex-wrap:wrap; margin-bottom:.5rem; }
.sd-direct-rekon-title { color:#1e40af; font-size:.8rem; font-weight:900; }
.sd-direct-rekon-sub { margin-top:.15rem; color:#64748b; font-size:.72rem; line-height:1.4; }
.sd-direct-rekon-status { margin-top:.45rem; color:#64748b; font-size:.72rem; line-height:1.4; }
.sd-direct-rekon-status.is-ok { color:#15803d; font-weight:800; }
.sd-direct-rekon-status.is-error { color:#b91c1c; font-weight:800; }
body[data-theme="dark"] .sd-direct-rekon { background:rgba(30,64,175,.14); border-color:rgba(96,165,250,.35); }
body[data-theme="dark"] .sd-direct-rekon-title { color:#bfdbfe; }
@media(max-width:600px){
  .sd-tabs,.sd-tabs.has-unlinked{grid-template-columns:repeat(2, minmax(0, 1fr));gap:.2rem;padding:.22rem}.sd-tab-btn{min-height:36px;font-size:.7rem}.sd-tab-btn i{display:none}
  .sd-rekon-summary{grid-template-columns:1fr 1fr}.sd-rekon-stat:last-child{grid-column:1 / -1}
}

/* Confirm page: compact operator view. */
.sd-topbar{gap:.35rem;padding:.45rem .65rem;box-shadow:0 1px 8px rgba(15,23,42,.05)}
.sd-topbar .sd-btn,.sd-topbar .sd-pill{min-height:30px;padding:.2rem .5rem;font-size:.7rem}
.sd-topbar .sd-code{font-size:.86rem}
.sd-wrap{max-width:960px;padding:.6rem .7rem 1.25rem}
.sd-card{border-radius:9px;box-shadow:0 1px 5px rgba(15,23,42,.03)}
.sd-head{padding:.6rem .75rem}
.sd-body{padding:.6rem .75rem}
.sd-order-search{padding:.45rem .75rem}
.sd-order-search input{min-height:32px;padding:.28rem .5rem}
.sd-order{padding:.5rem .6rem}
.sd-order-items{margin-left:1.8rem}
.sd-marketplace-detail{margin:.55rem 0 0 1.8rem;padding:.55rem .65rem}
.sd-marketplace-table th,.sd-marketplace-table td{padding:.32rem .35rem}
</style>
@endpush

@section('content')
@php
    $statusKey = $shipment->status ?? 'draft';
    $statusLabel = $statusKey === 'draft' ? 'Draft' : ucfirst($statusKey);
    $lines = $shipment->lines ?? collect();
    $orderScans = ($shipment->orderScans ?? collect())->sortBy('id')->values();
    $ungroupedLines = $lines->filter(fn ($line) => !$line->shipment_order_scan_id)->values();
    $singleOrderFallbackLines = $orderScans->count() === 1 ? $ungroupedLines : collect();
    $totalQty = (int) $lines->sum('qty_scanned');
    $totalLines = (int) $lines->count();
    $pendingOrders = $orderScans->where('status', 'pending')->count();
    $isItemFirst = ($shipment->scan_mode ?? 'item_first') === 'item_first';
    $isDaily = (bool) ($isDaily ?? (($shipment->dispatch_mode ?? 'single') === 'daily'));
    $currentWave = $currentWave ?? null;
    $mappedLines = $lines->filter(fn ($line) => !empty($line->shipment_order_scan_id))->values();
    $unmappedLines = $lines->filter(fn ($line) => empty($line->shipment_order_scan_id))->values();
    $mappedQty = (int) $mappedLines->sum('qty_scanned');
    $unmappedQty = (int) $unmappedLines->sum('qty_scanned');
    $unlinkedOrderScans = $orderScans->filter(fn ($scan) => empty($scan->fulfillment_id) && $scan->source !== 'sales_invoice')->values();
    $mappingErrors = $mappingErrors ?? [];
@endphp

<div class="sd-topbar">
    <a href="{{ route('sales.shipments.index') }}" class="sd-btn sd-topbar-nav" title="Kembali ke shipment">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Shipment
    </a>
    <a href="{{ route('sales.shipments.edit', $shipment) }}" class="sd-btn sd-topbar-nav">
        <i class="bi bi-pencil-square" aria-hidden="true"></i> Edit
    </a>
    @if($shipment->shipment_type === \App\Models\Shipment::TYPE_MARKETPLACE)
        <a href="{{ route('sales.shipments.rekon', $shipment) }}" class="sd-btn sd-topbar-nav">
            <i class="bi bi-diagram-3" aria-hidden="true"></i> Rekon
        </a>
        @if($statusKey === 'draft')
            <form class="sd-inline-form" action="{{ route('sales.shipments.rekon_auto_link', $shipment) }}" method="POST">
                @csrf
                <button type="submit" class="sd-btn sd-link sd-topbar-nav" title="Tautkan otomatis">
                    <i class="bi bi-link-45deg" aria-hidden="true"></i>
                </button>
            </form>
        @endif
    @endif
    <span class="sd-code">{{ $shipment->code }}</span>
    @if($isDaily && $currentWave)
        <span class="sd-pill">{{ $currentWave->label ?: ('Gelombang ' . $currentWave->sequence) }}</span>
    @endif
    <span class="sd-pill sd-status">{{ $statusLabel }}</span>
    <span class="sd-spacer"></span>
    <span class="sd-pill">Qty <b>{{ number_format($totalQty, 0, ',', '.') }}</b></span>
    @if ($isItemFirst)
        <span class="sd-pill">Auto</span>
    @else
        <span class="sd-pill">Order <b>{{ number_format($orderScans->count(), 0, ',', '.') }}</b></span>
    @endif
    @if($statusKey === 'draft')
        <form id="shipmentSubmitForm" class="sd-inline-form" action="{{ $isDaily ? route('sales.shipments.wave_post', $shipment) : route('sales.shipments.submit', $shipment) }}" method="POST"
              data-gf-confirm
              data-gf-confirm-title="Submit shipment?"
              data-gf-confirm-summary='@json(["orders" => $orderScans->count(), "items" => $totalLines, "qty" => $totalQty])'
              data-gf-confirm-text="{{ $isDaily ? 'Gelombang: ' . ($currentWave?->label ?: '-') . ' · Item/SKU: ' . $totalLines . ' · Total qty: ' . $totalQty : ($isItemFirst ? 'Mapping item otomatis · Item/SKU: ' . $totalLines . ' · Total qty: ' . $totalQty : 'Order discan: ' . $orderScans->count() . ' · Item/SKU: ' . $totalLines . ' · Total qty: ' . $totalQty) }}. Stok akan dikurangi dari WH-RTS."
              data-gf-confirm-ok="{{ $isDaily ? 'Selesaikan Gelombang' : 'Submit' }}"
              data-gf-confirm-cancel="Batal">
            @csrf
            <button type="submit" class="sd-btn sd-primary sd-topbar-primary"
                    title="{{ !empty($stockInsufficient) ? 'Stok WH-RTS belum cukup' : 'Submit shipment' }}"
                    @disabled($lines->count() === 0 || !empty($stockInsufficient) || !empty($mappingErrors))>
                <i class="bi bi-check2-circle" aria-hidden="true"></i> {{ $isDaily ? 'Selesaikan' : 'Submit' }}
            </button>
        </form>
    @else
        <a href="{{ route('sales.shipments.show', $shipment) }}" class="sd-btn sd-primary sd-topbar-primary">Lihat Detail</a>
    @endif
</div>

@if(session('message'))
    <div class="sd-flash" role="status">{{ session('message') }}</div>
@endif

<div class="sd-wrap">

    @if(!empty($stockInsufficient))
        <div class="sd-stock-warning" role="alert">
            <div class="sd-stock-head">Stok WH-RTS belum cukup untuk shipment ini</div>
            <div class="sd-stock-table-wrap">
                <table class="sd-stock-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Item</th>
                            <th class="sd-stock-number">Stok</th>
                            <th class="sd-stock-number">Perlu</th>
                            <th class="sd-stock-number">Kurang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stockInsufficient as $stockError)
                            <tr>
                                <td class="sd-stock-code">{{ $stockError['code'] ?? '-' }}</td>
                                <td>{{ $stockError['name'] ?? '-' }}</td>
                                <td class="sd-stock-number">{{ number_format((int) ($stockError['stock'] ?? 0), 0, ',', '.') }}</td>
                                <td class="sd-stock-number">{{ number_format((int) ($stockError['needed'] ?? 0), 0, ',', '.') }}</td>
                                <td class="sd-stock-number sd-stock-short">-{{ number_format((int) ($stockError['short'] ?? 0), 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if(!empty($mappingErrors))
        <div class="sd-stock-warning" role="alert">
            <div class="sd-stock-head">Shipment belum siap dikirim</div>
            <div class="sd-body" style="padding-top:.55rem;padding-bottom:.55rem">
                <ul style="margin:0;padding-left:1.1rem;color:#991b1b;font-size:.78rem;line-height:1.5">
                    @foreach($mappingErrors as $mappingError)
                        <li>{{ $mappingError }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if($isItemFirst)
        <div class="sd-tabs {{ $shipment->shipment_type === \App\Models\Shipment::TYPE_MARKETPLACE ? 'has-unlinked' : '' }}" role="tablist" aria-label="Ringkasan shipment">
            <button type="button" class="sd-tab-btn active" data-sd-tab="orders" role="tab" aria-selected="true">
                <i class="bi bi-receipt" aria-hidden="true"></i> Order
            </button>
            @if($shipment->shipment_type === \App\Models\Shipment::TYPE_MARKETPLACE)
                <button type="button" class="sd-tab-btn" data-sd-tab="unlinked" role="tab" aria-selected="false">
                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i> Belum Tertaut <span class="sd-tab-count">{{ $unlinkedOrderScans->count() }}</span>
                </button>
            @endif
            <button type="button" class="sd-tab-btn" data-sd-tab="items" role="tab" aria-selected="false">
                <i class="bi bi-box-seam" aria-hidden="true"></i> Items
            </button>
            <button type="button" class="sd-tab-btn" data-sd-tab="rekon" role="tab" aria-selected="false">
                <i class="bi bi-diagram-3" aria-hidden="true"></i> Rekonsiliasi
            </button>
        </div>
        <div id="sd-tab-orders" class="sd-tab-panel" role="tabpanel">
    @endif

    <div class="sd-card">
            <div class="sd-head">
                <div class="sd-title">{{ $isItemFirst ? 'Order & Item' : 'Order' }}</div>
                <span class="sd-pill">{{ number_format($pendingOrders, 0, ',', '.') }} pending</span>
            </div>
            <div class="sd-order-search">
                <input type="search" id="orderSearchInput" placeholder="Cari order / resi" autocomplete="off">
            </div>
            <div class="sd-body">
                @if($orderScans->isEmpty() && $isItemFirst)
                    <div class="sd-order sd-order-group">
                        <div class="sd-order-lead">
                            <span class="sd-order-num">✓</span>
                            <div>
                                <div class="sd-order-no">ITEM SHIPMENT</div>
                                <div class="sd-muted">Belum ada order spesifik; item dipakai sebagai mapping otomatis.</div>
                            </div>
                            <span class="sd-order-qty">x{{ number_format($totalQty, 0, ',', '.') }}</span>
                        </div>
                        <div class="sd-order-items">
                            @forelse($ungroupedLines as $line)
                                <div class="sd-order-item">
                                    <div><div class="sd-order-item-code">{{ $line->item?->code ?? '-' }}</div><div class="sd-order-item-name">{{ $line->item?->name ?? '' }}</div></div>
                                    <div class="sd-order-item-qty">x{{ number_format((int) $line->qty_scanned, 0, ',', '.') }}</div>
                                </div>
                            @empty
                                <div class="sd-muted" style="padding:.45rem 0">Belum ada item yang discan.</div>
                            @endforelse
                        </div>
                    </div>
                @elseif($orderScans->isEmpty())
                    <div class="sd-empty">Belum ada order.</div>
                @else
                    <div class="sd-list">
                        @foreach($orderScans as $scan)
                            @php
                                $scanStatus = $scan->status ?: 'pending';
                                $scanIsUnlinked = empty($scan->fulfillment_id);
                                $matchMethod = $scan->match_method ?: data_get($scan->raw_payload, 'match_method');
                                $matchReason = $scan->match_reason ?: data_get($scan->raw_payload, 'match_reason') ?: data_get($scan->raw_payload, 'lookup_status');
                                $matchMethodLabel = match ($matchMethod) {
                                    'awb' => 'AWB order',
                                    'awb_booking' => 'AWB booking',
                                    'booking_sn' => 'Booking SN',
                                    'order_no' => 'No. pesanan',
                                    default => null,
                                };
                                $matchReasonLabel = match ($matchReason) {
                                    'order_not_found' => 'Order belum ada di database marketplace',
                                    'empty_scan_code' => 'Kode scan kosong',
                                    'duplicate_active_shipment' => 'Order sedang ada di shipment aktif lain',
                                    'fulfillment_cancelled' => 'Fulfillment sudah dibatalkan',
                                    default => str_starts_with((string) $matchReason, 'status_')
                                        ? 'Status marketplace tidak termasuk status aktif'
                                        : null,
                                };
                                $scanLabel = $scanStatus === 'skip'
                                    ? 'Diabaikan'
                                    : ($scanIsUnlinked ? 'Belum Tertaut' : 'Tertaut');
                                $orderLines = $scan->lines->merge($singleOrderFallbackLines)->values();
                                $orderQty = (int) $orderLines->sum('qty_scanned');
                            @endphp
                            <div class="sd-order sd-order-group" data-order-card data-order-search="{{ strtolower($scan->order_no . ' ' . ($scan->source ?: '')) }}">
                                <div class="sd-order-lead">
                                    <span class="sd-order-num">{{ $loop->iteration }}</span>
                                    <div>
                                        <div class="sd-order-no">{{ $scan->order_no }}</div>
                                        <div class="sd-muted">{{ $scanIsUnlinked ? 'Belum tertaut' : ($matchMethodLabel ?: ($scan->source ?: 'Tertaut')) }}@if($scan->confirmed_at) · {{ $scan->confirmed_at->format('d M Y H:i') }}@endif</div>
                                        @if($scanIsUnlinked && $matchReasonLabel)
                                            <div class="sd-muted" title="{{ $matchReason }}">Alasan: {{ $matchReasonLabel }}</div>
                                        @endif
                                    </div>
                                    <span class="sd-order-qty">x{{ number_format($orderQty, 0, ',', '.') }}</span>
                                </div>
                                <div class="sd-order-items">
                                    @forelse($orderLines as $line)
                                        <div class="sd-order-item">
                                            <div><div class="sd-order-item-code">{{ $line->item?->code ?? '-' }}</div><div class="sd-order-item-name">{{ $line->item?->name ?? '' }}</div></div>
                                            <div class="sd-order-item-qty">x{{ number_format((int) $line->qty_scanned, 0, ',', '.') }}</div>
                                        </div>
                                    @empty
                                        <div class="sd-muted" style="padding:.45rem 0">Belum ada item di order ini.</div>
                                    @endforelse
                                </div>
                                @if($scan->fulfillment?->marketplaceOrder)
                                    @php
                                        $marketplaceOrder = $scan->fulfillment->marketplaceOrder;
                                        $marketplaceLines = $marketplaceOrder->items ?? collect();
                                        $pickedByItem = $orderLines->groupBy('item_id')->map(fn ($itemLines) => (int) $itemLines->sum('qty_scanned'));
                                        $marketplaceStatus = strtoupper(str_replace('_', ' ', (string) ($marketplaceOrder->order_status ?: $marketplaceOrder->status ?: '')));
                                    @endphp
                                    <div class="sd-marketplace-detail">
                                        <div class="sd-marketplace-head">
                                            <div>
                                            <div class="sd-marketplace-title"><i class="bi bi-shop me-1" aria-hidden="true"></i>Marketplace</div>
                                                <div class="sd-marketplace-meta">
                                                    <span>Status: <b>{{ $marketplaceStatus ?: '—' }}</b></span>
                                                    @if($marketplaceOrder->buyer_username)<span>Pembeli: {{ $marketplaceOrder->buyer_username }}</span>@endif
                                                    @if($marketplaceOrder->shipping_awb_no)<span>Resi: <b>{{ $marketplaceOrder->shipping_awb_no }}</b></span>@endif
                                                    @if($marketplaceOrder->total_amount)<span>Total: <b>Rp{{ number_format((float) $marketplaceOrder->total_amount, 0, ',', '.') }}</b></span>@endif
                                                </div>
                                            </div>
                                            <div class="sd-marketplace-actions">
                                                @if(Route::has('marketplace.orders.show'))
                                                    <a href="{{ route('marketplace.orders.show', $marketplaceOrder) }}" class="sd-btn" target="_blank" rel="noopener">Lihat Order</a>
                                                @endif
                                                <a href="{{ route('sales.shipments.edit', $shipment) }}" class="sd-btn">Edit Qty</a>
                                            </div>
                                        </div>
                                        @if($marketplaceLines->isNotEmpty())
                                            <div style="overflow:auto">
                                                <table class="sd-marketplace-table">
                                                    <thead><tr><th>SKU</th><th>Internal</th><th class="sd-marketplace-number">Order</th><th class="sd-marketplace-number">Ship</th><th class="sd-marketplace-number">Diff</th></tr></thead>
                                                    <tbody>
                                                    @foreach($marketplaceLines as $marketplaceLine)
                                                        @php
                                                            $internalItem = $marketplaceLine->internalItem;
                                                            $itemId = $marketplaceLine->internal_item_id ?: $marketplaceLine->item_id;
                                                            $qtyOrder = (int) $marketplaceLine->qty;
                                                            $qtyShipment = (int) ($pickedByItem[$itemId] ?? 0);
                                                            $difference = $qtyShipment - $qtyOrder;
                                                        @endphp
                                                        <tr>
                                                            <td class="sd-marketplace-code">{{ $marketplaceLine->marketplace_sku ?: $marketplaceLine->item_sku ?: $marketplaceLine->model_sku ?: '—' }}<div class="sd-muted">{{ $marketplaceLine->item_name ?: $marketplaceLine->item_name_snapshot ?: '' }}</div></td>
                                                            <td>{{ $internalItem?->code ?: ($marketplaceLine->item_code_snapshot ?: 'Belum mapped') }}@if($internalItem)<div class="sd-muted">{{ $internalItem->name }}</div>@endif</td>
                                                            <td class="sd-marketplace-number">{{ number_format($qtyOrder, 0, ',', '.') }}</td>
                                                            <td class="sd-marketplace-number">{{ number_format($qtyShipment, 0, ',', '.') }}</td>
                                                            <td class="sd-marketplace-number {{ $difference === 0 ? 'sd-marketplace-ok' : 'sd-marketplace-short' }}">{{ $difference > 0 ? '+' : '' }}{{ number_format($difference, 0, ',', '.') }}</td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="sd-muted">Detail item marketplace belum tersedia.</div>
                                        @endif
                                    </div>
                                @endif
                                <span class="sd-badge {{ $scanStatus }}">{{ $scanLabel }}</span>
                            </div>
                        @endforeach

                        @if($orderScans->count() > 1 && $ungroupedLines->isNotEmpty())
                            <div class="sd-order sd-order-group" data-order-card data-order-search="belum dikelompokkan">
                                <div class="sd-order-lead"><span class="sd-order-num">—</span><div><div class="sd-order-no">SISA BELUM TERKELOMPOK</div><div class="sd-muted">Item menunggu nomor order atau belum cocok</div></div><span class="sd-order-qty">x{{ number_format((int) $ungroupedLines->sum('qty_scanned'), 0, ',', '.') }}</span></div>
                                <div class="sd-order-items">
                                    @foreach($ungroupedLines as $line)
                                        <div class="sd-order-item"><div><div class="sd-order-item-code">{{ $line->item?->code ?? '-' }}</div><div class="sd-order-item-name">{{ $line->item?->name ?? '' }}</div></div><div class="sd-order-item-qty">x{{ number_format((int) $line->qty_scanned, 0, ',', '.') }}</div></div>
                                    @endforeach
                                </div>
                                <div class="sd-muted" style="padding:.55rem .75rem;border-top:1px dashed rgba(148,163,184,.25)">Belum ada order.</div>
                            </div>
                        @endif
                    </div>
                    <div class="sd-empty" id="orderSearchEmpty" hidden>Tidak ada order/no resi yang cocok.</div>
                @endif
            </div>
    </div>

    @if($isItemFirst)
        </div>

        @if($shipment->shipment_type === \App\Models\Shipment::TYPE_MARKETPLACE)
            <div id="sd-tab-unlinked" class="sd-tab-panel" role="tabpanel" hidden>
                <div class="sd-card">
                    <div class="sd-head">
                        <div>
                            <div class="sd-title">Order Belum Tertaut</div>
                            <div class="sd-muted">Order/AWB yang sudah discan tetapi belum terhubung ke data marketplace.</div>
                        </div>
                        <span class="sd-pill">{{ number_format($unlinkedOrderScans->count(), 0, ',', '.') }} order</span>
                    </div>
                    <div class="sd-body">
                        @forelse($unlinkedOrderScans as $scan)
                            @php
                                $payload = is_array($scan->raw_payload) ? $scan->raw_payload : [];
                                $reason = $scan->match_reason ?: data_get($payload, 'match_reason') ?: data_get($payload, 'lookup_status');
                                $reasonLabel = match ($reason) {
                                    'order_not_found' => 'Order/AWB belum ditemukan di database marketplace',
                                    'empty_scan_code' => 'Kode scan kosong',
                                    'duplicate_active_shipment' => 'Order sedang ada di shipment aktif lain',
                                    'fulfillment_cancelled' => 'Fulfillment sudah dibatalkan',
                                    default => str_starts_with((string) $reason, 'status_')
                                        ? 'Status marketplace belum boleh diproses'
                                        : 'Belum ada tautan marketplace',
                                };
                            @endphp
                            <div class="sd-order sd-order-group" style="margin-bottom:.45rem">
                                <div class="sd-order-lead">
                                    <span class="sd-order-num">!</span>
                                    <div>
                                        <div class="sd-order-no">{{ $scan->order_no }}</div>
                                        <div class="sd-muted">{{ $scan->source ?: 'scanner' }} · {{ $reasonLabel }}</div>
                                    </div>
                                    <span class="sd-badge pending">Belum Tertaut</span>
                                </div>
                            </div>
                        @empty
                            <div class="sd-rekon-message">Semua order/AWB yang discan sudah tertaut ke marketplace.</div>
                        @endforelse
                        <a href="{{ route('sales.shipments.rekon', $shipment) }}" class="sd-btn sd-primary" style="margin-top:.35rem">Buka Editor Rekonsiliasi</a>
                    </div>
                </div>
            </div>
        @endif

        <div id="sd-tab-items" class="sd-tab-panel" role="tabpanel" hidden>
            <div class="sd-card">
                <div class="sd-head">
                    <div>
                        <div class="sd-title">Items Shipment</div>
                        <div class="sd-muted">Daftar item yang sudah discan dan hasil mapping order.</div>
                    </div>
                    <span class="sd-pill">{{ number_format($totalLines, 0, ',', '.') }} SKU</span>
                </div>
                <div class="sd-body">
                    <div class="sd-item-list">
                        @forelse($lines as $line)
                            @php
                                $lineOrder = $line->shipment_order_scan_id
                                    ? $orderScans->firstWhere('id', $line->shipment_order_scan_id)
                                    : null;
                            @endphp
                            <div class="sd-item-row">
                                <div>
                                    <div class="sd-item-code">{{ $line->item?->code ?? '-' }}</div>
                                    <div class="sd-item-name">{{ $line->item?->name ?? 'Nama item belum tersedia' }}</div>
                                    <div class="sd-item-map">
                                        {{ $lineOrder ? 'Order: ' . $lineOrder->order_no : 'Belum terhubung ke order' }}
                                    </div>
                                </div>
                                <span class="sd-item-qty">x{{ number_format((int) $line->qty_scanned, 0, ',', '.') }}</span>
                            </div>
                        @empty
                            <div class="sd-empty">Belum ada item yang discan.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div id="sd-tab-rekon" class="sd-tab-panel" role="tabpanel" hidden>
            <div class="sd-card">
                <div class="sd-head">
                    <div>
                        <div class="sd-title">Rekonsiliasi</div>
                        <div class="sd-muted">Ringkasan hubungan order dan item sebelum shipment dikirim.</div>
                    </div>
                    <span class="sd-pill">Otomatis</span>
                </div>
                <div class="sd-body">
                    <div class="sd-rekon-summary">
                        <div class="sd-rekon-stat"><div class="sd-rekon-stat-label">Order</div><div class="sd-rekon-stat-value">{{ number_format($orderScans->count(), 0, ',', '.') }}</div></div>
                        <div class="sd-rekon-stat"><div class="sd-rekon-stat-label">Item ter-mapping</div><div class="sd-rekon-stat-value">{{ number_format($mappedQty, 0, ',', '.') }} qty</div></div>
                        <div class="sd-rekon-stat"><div class="sd-rekon-stat-label">Belum ter-mapping</div><div class="sd-rekon-stat-value">{{ number_format($unmappedQty, 0, ',', '.') }} qty</div></div>
                    </div>
                    @if($orderScans->isEmpty())
                        <div class="sd-rekon-message">Belum ada order yang tercatat. Scan nomor order terlebih dahulu agar item dapat dipetakan.</div>
                        <a href="{{ route('sales.shipments.scan_order', $shipment) }}" class="sd-btn sd-primary mt-3">Scan No Order</a>
                    @elseif($unmappedLines->isNotEmpty())
                        <div class="sd-rekon-message">Sebagian item belum memiliki order. Alokasikan item ke order terlebih dahulu sebelum submit shipment.</div>
                        <a href="{{ route('sales.shipments.rekon', $shipment) }}" class="sd-btn sd-primary mt-3">Alokasikan Item ke Order</a>
                    @else
                        <div class="sd-rekon-message">Semua item sudah terhubung ke order. Mapping siap direview sebelum submit.</div>
                        <a href="{{ route('sales.shipments.rekon', $shipment) }}" class="sd-btn sd-primary mt-3">Review Rekonsiliasi</a>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<script>
(function(){
    var orderSearchInput = document.getElementById('orderSearchInput');
    var orderSearchEmpty = document.getElementById('orderSearchEmpty');
    var orderCards = document.querySelectorAll('[data-order-card]');
    orderSearchInput?.addEventListener('input', function(){
        var query = (orderSearchInput.value || '').trim().toLowerCase();
        var visibleCount = 0;

        orderCards.forEach(function(card){
            var searchable = (card.dataset.orderSearch || '').toLowerCase();
            var visible = !query || searchable.indexOf(query) !== -1;
            card.hidden = !visible;
            if (visible) visibleCount++;
        });

        if (orderSearchEmpty) orderSearchEmpty.hidden = visibleCount > 0;
    });

    var tabButtons = document.querySelectorAll('[data-sd-tab]');
    var tabPanels = document.querySelectorAll('.sd-tab-panel');
    tabButtons.forEach(function(button){
        button.addEventListener('click', function(){
            var target = button.dataset.sdTab;
            tabButtons.forEach(function(tab){
                var active = tab === button;
                tab.classList.toggle('active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            tabPanels.forEach(function(panel){
                panel.hidden = panel.id !== 'sd-tab-' + target;
            });
        });
    });

})();
</script>
@endsection
