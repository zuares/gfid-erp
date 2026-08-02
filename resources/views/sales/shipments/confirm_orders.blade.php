{{-- resources/views/sales/shipments/confirm_orders.blade.php --}}
@extends('layouts.app')

@section('title', 'Konfirmasi Shipment · ' . $shipment->code)

@push('head')
<style>
.sd-wrap{max-width:1040px;margin-inline:auto;padding:.7rem .75rem 5rem}
.sd-topbar{position:sticky;top:0;z-index:250;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;padding:.55rem .75rem;background:var(--card,#fff);border-bottom:1px solid rgba(148,163,184,.18)}
.sd-code{font-weight:900;font-size:1rem;color:#111827}
.sd-spacer{flex:1}
.sd-btn,.sd-pill{display:inline-flex;align-items:center;justify-content:center;gap:.35rem;border-radius:7px;border:1px solid rgba(148,163,184,.3);background:transparent;color:#475569;text-decoration:none;font-size:.76rem;padding:.28rem .6rem;min-height:34px}
.sd-btn{font-weight:800;cursor:pointer}.sd-btn:disabled{opacity:.45;cursor:not-allowed;pointer-events:none}
.sd-btn:hover{background:rgba(148,163,184,.09);color:#111827;text-decoration:none}
.sd-primary{background:#334155!important;border-color:#334155!important;color:#fff!important}
.sd-danger{color:#991b1b;border-color:rgba(153,27,27,.25);background:transparent}
.sd-status{font-weight:850;color:#334155;background:rgba(148,163,184,.08)}
.sd-flow{display:flex;align-items:center;gap:.35rem;flex-wrap:wrap;margin:.6rem 0;padding:.45rem .55rem;border:1px solid rgba(148,163,184,.18);border-radius:8px;background:var(--card,#fff)}
.sd-step{border:1px solid rgba(148,163,184,.25);border-radius:7px;padding:.18rem .5rem;font-size:.72rem;font-weight:800;color:#64748b}
.sd-step.done{background:rgba(148,163,184,.08);color:#334155}
.sd-step.active{background:#334155;border-color:#334155;color:#fff}
.sd-sep{color:#cbd5e1;font-size:.72rem}
.sd-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.55rem;margin-bottom:.65rem}
.sd-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:8px;overflow:hidden;margin-bottom:.65rem}
.sd-kpi{padding:.65rem .75rem}
.sd-label{font-size:.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.02em}
.sd-value{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:1.18rem;font-weight:900;color:#111827;margin-top:.12rem}
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
.sd-badge{display:inline-flex;align-items:center;justify-content:center;border-radius:7px;border:1px solid rgba(148,163,184,.25);padding:.16rem .48rem;font-size:.69rem;font-weight:900;color:#64748b;white-space:nowrap}
.sd-badge.pending{background:rgba(245,158,11,.08);border-color:rgba(245,158,11,.25);color:#92400e}
.sd-empty{padding:1.6rem 1rem;text-align:center;color:#64748b;font-size:.84rem}
.sd-warning{margin-bottom:.65rem;padding:.65rem .75rem;border:1px solid rgba(245,158,11,.28);border-radius:8px;color:#92400e;background:rgba(245,158,11,.08);font-size:.8rem;font-weight:650}
.sd-match-panel{margin-bottom:.65rem;border:1px solid rgba(148,163,184,.22);border-radius:8px;background:var(--card,#fff);padding:.65rem .75rem}.sd-match-row{display:flex;align-items:center;justify-content:space-between;gap:.75rem}.sd-match-title{font-size:.82rem;font-weight:900;color:#334155}.sd-match-note{margin-top:.18rem;color:#64748b;font-size:.72rem}.sd-toggle{display:inline-flex;align-items:center;justify-content:center;gap:.35rem;min-width:68px;min-height:32px;border:1px solid rgba(148,163,184,.35);border-radius:999px;padding:.22rem .5rem;background:#f8fafc;color:#64748b;font-size:.68rem;font-weight:900;cursor:pointer;transition:.15s ease}.sd-toggle-dot{width:10px;height:10px;border-radius:999px;background:#94a3b8}.sd-toggle.is-on{border-color:#334155;background:#334155;color:#fff}.sd-toggle.is-on .sd-toggle-dot{background:#fff}
.sd-stock-warning{margin-bottom:.65rem;border:1px solid rgba(220,38,38,.24);border-radius:8px;background:rgba(254,242,242,.72);overflow:hidden}
.sd-stock-head{padding:.65rem .75rem;border-bottom:1px solid rgba(220,38,38,.16);color:#991b1b;font-size:.8rem;font-weight:900}
.sd-stock-table-wrap{overflow:auto}.sd-stock-table{width:100%;border-collapse:collapse}.sd-stock-table th,.sd-stock-table td{padding:.5rem .65rem;border-bottom:1px solid rgba(220,38,38,.12);font-size:.78rem}.sd-stock-table th{text-align:left;color:#991b1b;font-size:.68rem;text-transform:uppercase;letter-spacing:.02em;background:rgba(254,226,226,.45)}.sd-stock-table td{color:#475569}.sd-stock-table tr:last-child td{border-bottom:0}.sd-stock-table .sd-stock-code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827}.sd-stock-table .sd-stock-number{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}.sd-stock-table .sd-stock-short{color:#dc2626;font-weight:900}
.sd-tabs{display:flex;gap:.25rem;margin-bottom:.65rem;border-bottom:1px solid rgba(148,163,184,.18);flex-wrap:wrap}
.sd-tab{appearance:none;display:inline-flex;align-items:center;gap:.4rem;border:none;background:transparent;color:#64748b;font-weight:800;font-size:.82rem;padding:.55rem .8rem;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px}
.sd-tab.active{color:#111827;border-bottom-color:#334155}
.sd-tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:1.35rem;height:1.35rem;padding:0 .3rem;border-radius:999px;background:rgba(148,163,184,.16);color:#475569;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.7rem;font-weight:900}
.sd-tab.active .sd-tab-count{background:#334155;color:#fff}
.sd-tabpane{display:none}.sd-tabpane.active{display:block}
.sd-table-wrap{overflow:auto;border:1px solid rgba(148,163,184,.16);border-radius:8px}
.sd-table{width:100%;border-collapse:collapse}.sd-table th,.sd-table td{padding:.55rem .65rem;border-bottom:1px solid rgba(148,163,184,.12);vertical-align:middle}.sd-table th{text-align:left;font-size:.72rem;color:#64748b;font-weight:900;text-transform:uppercase;background:rgba(148,163,184,.04)}.sd-table td{font-size:.86rem;color:#334155}
.sd-table td.sd-r{text-align:right}.sd-code-cell{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827}.sd-name{color:#64748b;font-size:.8rem;margin-top:.08rem}.sd-total td{font-weight:900;color:#111827;background:rgba(148,163,184,.04)}
.sd-meta{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.5rem}.sd-meta-box{border:1px solid rgba(148,163,184,.16);border-radius:8px;padding:.55rem .65rem}
.sd-actions{display:flex;justify-content:space-between;align-items:center;gap:.55rem;flex-wrap:wrap;margin-top:.15rem}.sd-actions-group{display:flex;gap:.45rem;flex-wrap:wrap}
@media(max-width:860px){
  .sd-wrap{padding:.5rem .5rem 6rem}.sd-topbar{padding:.5rem}.sd-code{flex:1;min-width:150px;font-size:1.02rem}.sd-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:.45rem}.sd-kpi{padding:.58rem .62rem}.sd-value{font-size:1.08rem}.sd-head{padding:.65rem .7rem}.sd-body{padding:.65rem .7rem}.sd-order{grid-template-columns:1fr;align-items:flex-start;gap:.4rem}.sd-actions,.sd-actions-group{width:100%}.sd-actions-group .sd-btn,.sd-actions form{width:100%}.sd-actions form .sd-btn{width:100%}.sd-meta{grid-template-columns:1fr}.sd-table-wrap{border:none;border-radius:0;overflow:visible}.sd-table,.sd-table tbody,.sd-table tr,.sd-table td{display:block;width:100%}.sd-table thead{display:none}.sd-table tr{border:1px solid rgba(148,163,184,.16);border-radius:8px;margin-bottom:.45rem;padding:.55rem .6rem;background:var(--card,#fff)}.sd-table td{border:0;padding:0}.sd-table td.sd-r{text-align:left;margin-top:.35rem}.sd-name{display:none}.sd-total{display:none!important}
}
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
@endphp

<div class="sd-topbar">
    <a href="{{ route('sales.shipments.index') }}" class="sd-btn">Kembali</a>
    <span class="sd-code">{{ $shipment->code }}</span>
    <span class="sd-pill sd-status">{{ $statusLabel }}</span>
    <span class="sd-spacer"></span>
    <span class="sd-pill">Qty <b>{{ number_format($totalQty, 0, ',', '.') }}</b></span>
    <span class="sd-pill">Pesanan <b>{{ number_format($orderScans->count(), 0, ',', '.') }}</b></span>
</div>

<div class="sd-wrap">
    <div class="sd-flow">
        <span class="sd-step done">Scan Barang</span><span class="sd-sep">-&gt;</span>
        <span class="sd-step done">Scan Pesanan</span><span class="sd-sep">-&gt;</span>
        <span class="sd-step active">Konfirmasi Pesanan</span><span class="sd-sep">-&gt;</span>
        <span class="sd-step">Simpan &amp; Kurangi Stok</span>
    </div>

    <div class="sd-grid">
        <div class="sd-card sd-kpi"><div class="sd-label">Qty Batch</div><div class="sd-value">{{ number_format($totalQty, 0, ',', '.') }}</div></div>
        <div class="sd-card sd-kpi"><div class="sd-label">SKU</div><div class="sd-value">{{ number_format($totalLines, 0, ',', '.') }}</div></div>
        <div class="sd-card sd-kpi"><div class="sd-label">Pesanan</div><div class="sd-value">{{ number_format($orderScans->count(), 0, ',', '.') }}</div></div>
        <div class="sd-card sd-kpi"><div class="sd-label">Status</div><div class="sd-value" style="font-size:1rem">{{ $statusLabel }}</div></div>
    </div>

    <div class="sd-warning">
        Order di halaman ini masih berupa pencatatan. Stok WH-RTS akan dikurangi saat shipment disubmit.
    </div>

    <div class="sd-match-panel">
        <div class="sd-match-row">
            <div>
                <div class="sd-match-title">Matching Marketplace / Fulfillment</div>
                <div class="sd-match-note" id="matchingNote">OFF · Order disimpan sebagai pencatatan saja.</div>
            </div>
            <button type="button" class="sd-toggle" id="matchingToggle" aria-pressed="false">
                <span class="sd-toggle-dot"></span><span id="matchingToggleLabel">OFF</span>
            </button>
        </div>
        <input type="hidden" name="matching_enabled" id="matchingEnabledInput" value="0" form="shipmentSubmitForm">
    </div>

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

    <div class="sd-tabs" role="tablist">
        <button type="button" class="sd-tab active" data-tab="pesanan">Order <span class="sd-tab-count">{{ number_format($orderScans->count(), 0, ',', '.') }}</span></button>
        <button type="button" class="sd-tab" data-tab="item">Item Batch <span class="sd-tab-count">{{ number_format($totalLines, 0, ',', '.') }}</span></button>
        <button type="button" class="sd-tab" data-tab="info">Info Shipment</button>
    </div>

    <div class="sd-tabpane active" id="sd-tab-pesanan" role="tabpanel">
        <div class="sd-card">
            <div class="sd-head">
                <div><div class="sd-title">Order Tercatat</div><div class="sd-muted">Review grouping order/no resi dan item sebelum stok dikurangi.</div></div>
                <span class="sd-pill">{{ number_format($pendingOrders, 0, ',', '.') }} tunda</span>
            </div>
            <div class="sd-order-search">
                <input type="search" id="orderSearchInput" placeholder="Cari nomor order atau no resi..." autocomplete="off">
            </div>
            <div class="sd-body">
                @if($orderScans->isEmpty())
                    <div class="sd-empty">Belum ada nomor pesanan yang dikonfirmasi.</div>
                @else
                    <div class="sd-list">
                        @foreach($orderScans as $scan)
                            @php
                                $scanStatus = $scan->status ?: 'pending';
                                $scanLabel = $scanStatus === 'skip' ? 'Diabaikan' : 'Ditunda';
                                $orderLines = $scan->lines->merge($singleOrderFallbackLines)->values();
                                $orderQty = (int) $orderLines->sum('qty_scanned');
                            @endphp
                            <div class="sd-order sd-order-group" data-order-card data-order-search="{{ strtolower($scan->order_no . ' ' . ($scan->source ?: '')) }}">
                                <div class="sd-order-lead">
                                    <span class="sd-order-num">{{ $loop->iteration }}</span>
                                    <div>
                                        <div class="sd-order-no">{{ $scan->order_no }}</div>
                                        <div class="sd-muted">{{ $scan->source === 'manual_scan' ? 'Belum tertaut' : ($scan->source ?: 'Pencatatan manual') }}@if($scan->confirmed_at) · {{ $scan->confirmed_at->format('d M Y H:i') }}@endif</div>
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
                                <span class="sd-badge {{ $scanStatus }}">{{ $scanLabel }}</span>
                            </div>
                        @endforeach

                        @if($orderScans->count() > 1 && $ungroupedLines->isNotEmpty())
                            <div class="sd-order sd-order-group" data-order-card data-order-search="belum dikelompokkan">
                                <div class="sd-order-lead"><span class="sd-order-num">—</span><div><div class="sd-order-no">BELUM DIKELOMPOKKAN</div><div class="sd-muted">Item lama atau scan tanpa order</div></div><span class="sd-order-qty">x{{ number_format((int) $ungroupedLines->sum('qty_scanned'), 0, ',', '.') }}</span></div>
                                <div class="sd-order-items">
                                    @foreach($ungroupedLines as $line)
                                        <div class="sd-order-item"><div><div class="sd-order-item-code">{{ $line->item?->code ?? '-' }}</div><div class="sd-order-item-name">{{ $line->item?->name ?? '' }}</div></div><div class="sd-order-item-qty">x{{ number_format((int) $line->qty_scanned, 0, ',', '.') }}</div></div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="sd-empty" id="orderSearchEmpty" hidden>Tidak ada order/no resi yang cocok.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="sd-tabpane" id="sd-tab-item" role="tabpanel">
        <div class="sd-card"><div class="sd-head"><div><div class="sd-title">Item Batch</div><div class="sd-muted">Semua item yang akan diproses pada shipment ini.</div></div><span class="sd-pill">{{ number_format($totalLines, 0, ',', '.') }} SKU</span></div><div class="sd-body">
            @if($lines->isEmpty())
                <div class="sd-empty">Belum ada item.</div>
            @else
                <div class="sd-table-wrap"><table class="sd-table"><thead><tr><th>Item</th><th class="sd-r">Qty</th></tr></thead><tbody>
                    @foreach($lines as $line)
                        <tr><td><div class="sd-code-cell">{{ $line->item?->code ?? '-' }}</div><div class="sd-name">{{ $line->item?->name ?? '-' }}</div></td><td class="sd-r"><span class="sd-badge">{{ number_format((int) $line->qty_scanned, 0, ',', '.') }} pcs</span></td></tr>
                    @endforeach
                    <tr class="sd-total"><td class="sd-r">Total</td><td class="sd-r">{{ number_format($totalQty, 0, ',', '.') }}</td></tr>
                </tbody></table></div>
            @endif
        </div></div>
    </div>

    <div class="sd-tabpane" id="sd-tab-info" role="tabpanel">
        <div class="sd-card"><div class="sd-head"><div class="sd-title">Info Shipment</div></div><div class="sd-body"><div class="sd-meta">
            <div class="sd-meta-box"><div class="sd-label">Store</div><div class="sd-value" style="font-size:.95rem">{{ $shipment->store?->code ?? '-' }}</div><div class="sd-muted">{{ $shipment->store?->name ?? 'Belum dihubungkan' }}</div></div>
            <div class="sd-meta-box"><div class="sd-label">Tanggal</div><div class="sd-value" style="font-size:.95rem">{{ $shipment->date ? $shipment->date->format('d M Y') : '-' }}</div><div class="sd-muted">Shipment manual</div></div>
            <div class="sd-meta-box"><div class="sd-label">Warehouse</div><div class="sd-value" style="font-size:.95rem">{{ $shipment->warehouse?->code ?? 'WH-RTS' }}</div><div class="sd-muted">Stok dikurangi saat submit</div></div>
        </div></div></div>
    </div>

    <div class="sd-actions">
        <div class="sd-actions-group"><a href="{{ route('sales.shipments.scan_order', $shipment) }}" class="sd-btn">Kembali Scan</a><a href="{{ route('sales.shipments.index') }}" class="sd-btn">Daftar Shipment</a></div>
        <div class="sd-actions-group">
            @if($statusKey === 'draft')
                <a href="{{ route('sales.shipments.cancel_form', $shipment) }}" class="sd-btn sd-danger">Batalkan Shipment</a>
                <form id="shipmentSubmitForm" action="{{ route('sales.shipments.submit', $shipment) }}" method="POST"
                      data-gf-confirm
                      data-gf-confirm-title="Submit shipment?"
                      data-gf-confirm-summary='@json(["orders" => $orderScans->count(), "items" => $totalLines, "qty" => $totalQty])'
                      data-gf-confirm-text="Order discan: {{ $orderScans->count() }} · Item/SKU: {{ $totalLines }} · Total qty: {{ $totalQty }}. Stok akan dikurangi dari WH-RTS."
                      data-gf-confirm-ok="Submit"
                      data-gf-confirm-cancel="Batal">
                    @csrf
                    <button type="submit" class="sd-btn sd-primary" title="{{ !empty($stockInsufficient) ? 'Stok WH-RTS belum cukup' : 'Submit shipment' }}" @disabled($lines->count() === 0 || !empty($stockInsufficient))>Submit Shipment</button>
                </form>
            @else
                <a href="{{ route('sales.shipments.show', $shipment) }}" class="sd-btn sd-primary">Lihat Detail</a>
            @endif
        </div>
    </div>
</div>

<script>
(function(){
    var tabs = document.querySelectorAll('.sd-tab');
    var panes = document.querySelectorAll('.sd-tabpane');
    function activate(name){
        tabs.forEach(function(tab){ tab.classList.toggle('active', tab.dataset.tab === name); });
        panes.forEach(function(pane){ pane.classList.toggle('active', pane.id === 'sd-tab-' + name); });
        try { history.replaceState(null, '', '#' + name); } catch(e) {}
    }
    tabs.forEach(function(tab){ tab.addEventListener('click', function(){ activate(tab.dataset.tab); }); });
    var hash = (location.hash || '').replace('#', '');
    if (['pesanan','item','info'].indexOf(hash) !== -1) activate(hash);

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

    var matchingToggle = document.getElementById('matchingToggle');
    var matchingToggleLabel = document.getElementById('matchingToggleLabel');
    var matchingEnabledInput = document.getElementById('matchingEnabledInput');
    var matchingNote = document.getElementById('matchingNote');
    matchingToggle?.addEventListener('click', function(){
        var enabled = matchingToggle.getAttribute('aria-pressed') !== 'true';
        matchingToggle.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        matchingToggle.classList.toggle('is-on', enabled);
        if (matchingToggleLabel) matchingToggleLabel.textContent = enabled ? 'ON' : 'OFF';
        if (matchingEnabledInput) matchingEnabledInput.value = enabled ? '1' : '0';
        if (matchingNote) matchingNote.textContent = enabled
            ? 'ON · Mode matching dipilih untuk integrasi marketplace.'
            : 'OFF · Order disimpan sebagai pencatatan saja.';
    });
})();
</script>
@endsection
