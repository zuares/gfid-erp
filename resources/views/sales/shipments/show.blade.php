{{-- resources/views/sales/shipments/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Shipment ' . $shipment->code)

@push('head')
<style>
.sd-wrap{max-width:1040px;margin-inline:auto;padding:.7rem .75rem 3rem}
.sd-topbar{position:sticky;top:0;z-index:250;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;padding:.55rem .75rem;background:var(--card,#fff);border-bottom:1px solid rgba(148,163,184,.18)}
.sd-code{font-weight:900;font-size:1rem;color:#111827}
.sd-spacer{flex:1}
.sd-btn,.sd-pill{display:inline-flex;align-items:center;justify-content:center;gap:.35rem;border-radius:7px;border:1px solid rgba(148,163,184,.3);background:transparent;color:#475569;text-decoration:none;font-size:.76rem;padding:.28rem .6rem;min-height:34px}
.sd-btn{font-weight:800}
.sd-btn:hover{background:rgba(148,163,184,.09);color:#111827;text-decoration:none}
.sd-primary{background:#334155!important;border-color:#334155!important;color:#fff!important}
.sd-status{font-weight:850;color:#334155;background:rgba(148,163,184,.08)}
.sd-status.submitted,.sd-status.posted{color:#166534;background:rgba(22,101,52,.08);border-color:rgba(22,101,52,.2)}
.sd-status.cancelled{color:#991b1b;background:rgba(153,27,27,.08);border-color:rgba(153,27,27,.2)}
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
.sd-list{display:grid;gap:.45rem}
.sd-order{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:.65rem;border:1px solid rgba(148,163,184,.18);border-radius:8px;padding:.58rem .65rem}
.sd-order-no{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827;word-break:break-word}
.sd-badge{display:inline-flex;align-items:center;justify-content:center;border-radius:7px;border:1px solid rgba(148,163,184,.25);padding:.16rem .48rem;font-size:.69rem;font-weight:900;color:#64748b;white-space:nowrap}
.sd-badge.pending{background:rgba(245,158,11,.08);border-color:rgba(245,158,11,.25);color:#92400e}
.sd-badge.skip{background:rgba(148,163,184,.08)}
.sd-empty{padding:1.6rem 1rem;text-align:center;color:#64748b;font-size:.84rem}
.sd-table-wrap{overflow:auto;border:1px solid rgba(148,163,184,.16);border-radius:8px}
.sd-table{width:100%;border-collapse:collapse}
.sd-table th,.sd-table td{padding:.55rem .65rem;border-bottom:1px solid rgba(148,163,184,.12);vertical-align:middle}
.sd-table th{text-align:left;font-size:.72rem;color:#64748b;font-weight:900;text-transform:uppercase;letter-spacing:.02em;background:rgba(148,163,184,.04)}
.sd-table td{font-size:.86rem;color:#334155}
.sd-code-cell{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827}
.sd-name{color:#64748b;font-size:.8rem;margin-top:.08rem}
.sd-r{text-align:right}
.sd-total td{font-weight:900;color:#111827;background:rgba(148,163,184,.04)}
.sd-group{cursor:pointer;user-select:none}
.sd-group td{background:rgba(148,163,184,.10);font-weight:900;color:#334155}
.sd-group:hover td{background:rgba(148,163,184,.16)}
.sd-caret{display:inline-block;margin-right:.4rem;color:#94a3b8;font-size:.62rem;transition:transform .15s}
.sd-group.is-open .sd-caret{transform:rotate(90deg)}
.sd-group-cat{font-size:.72rem;letter-spacing:.02em}
.sd-group-count{margin-left:.4rem;color:#94a3b8;font-weight:700;font-size:.66rem}
.sd-collapsed{display:none!important}
.sd-meta{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.5rem}
.sd-meta-box{border:1px solid rgba(148,163,184,.16);border-radius:8px;padding:.55rem .65rem}
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
.sd-order-group>.sd-badge{margin:.5rem 0 0 2.05rem}
.sd-tabs{display:flex;gap:.25rem;margin-bottom:.65rem;border-bottom:1px solid rgba(148,163,184,.18);flex-wrap:wrap}
.sd-tab{appearance:none;display:inline-flex;align-items:center;gap:.4rem;border:none;background:transparent;color:#64748b;font-weight:800;font-size:.82rem;padding:.55rem .8rem;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px}
.sd-tab:hover{color:#334155}
.sd-tab.active{color:#111827;border-bottom-color:#334155}
.sd-tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:1.35rem;height:1.35rem;padding:0 .3rem;border-radius:999px;background:rgba(148,163,184,.16);color:#475569;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.7rem;font-weight:900}
.sd-tab.active .sd-tab-count{background:#334155;color:#fff}
.sd-tabpane{display:none}
.sd-tabpane.active{display:block}
.sd-tabpane .sd-card{margin-bottom:0}
.sd-waves{display:grid;gap:.45rem}
.sd-wave{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:.65rem;border:1px solid rgba(148,163,184,.18);border-radius:8px;padding:.6rem .7rem}
.sd-wave-no{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827}
.sd-wave-title{font-weight:900;color:#334155}
.sd-wave-meta{font-size:.76rem;color:#64748b;margin-top:.08rem}
.sd-wave-status{font-size:.7rem;font-weight:900;border-radius:999px;padding:.2rem .5rem;background:rgba(245,158,11,.08);color:#92400e;border:1px solid rgba(245,158,11,.22)}
.sd-wave-status.posted{background:rgba(22,101,52,.08);color:#166534;border-color:rgba(22,101,52,.2)}
@media(max-width:860px){
  .sd-wrap{padding:.5rem .5rem 3.5rem}
  .sd-topbar{padding:.5rem}
  .sd-code{flex:1;min-width:150px;font-size:1.02rem}
  .sd-topbar .sd-pill:not(.sd-status),.sd-flow,.sd-hide-mobile{display:none}
  .sd-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:.45rem}
  .sd-kpi{padding:.58rem .62rem}
  .sd-value{font-size:1.08rem}
  .sd-head{padding:.65rem .7rem}
  .sd-body{padding:.65rem .7rem}
  .sd-order{grid-template-columns:1fr;align-items:flex-start;gap:.4rem}
  .sd-meta{grid-template-columns:1fr}
  .sd-wave{grid-template-columns:auto minmax(0,1fr)}
  .sd-wave-status{grid-column:2;justify-self:start}
  .sd-table-wrap{border:none;border-radius:0;overflow:visible}
  .sd-table,.sd-table tbody,.sd-table tr,.sd-table td{display:block;width:100%}
  .sd-table thead{display:none}
  .sd-table tr{border:1px solid rgba(148,163,184,.16);border-radius:8px;margin-bottom:.45rem;padding:.55rem .6rem;background:var(--card,#fff)}
  .sd-table td{border:0;padding:0}
  .sd-table td.sd-r{text-align:left;margin-top:.35rem}
  .sd-name,.sd-mobile-muted{display:none}
  .sd-total{display:none!important}
}
</style>
@endpush

@section('content')
@php
    $canSeeNominal = $canSeeNominal ?? ((auth()->user()->role ?? null) !== 'admin');
    $isCancelled = !empty($shipment->cancelled_at);
    $isPosted = !empty($shipment->posted_at) || ($shipment->status ?? null) === 'posted';
    $isSubmitted = !empty($shipment->submitted_at) || ($shipment->status ?? null) === 'submitted';
    $statusKey = $isCancelled ? 'cancelled' : ($isPosted ? 'posted' : ($isSubmitted ? 'submitted' : 'draft'));
    $statusLabel = [
        'draft' => 'Draft',
        'submitted' => 'Stok Dikurangi',
        'posted' => 'Posted',
        'cancelled' => 'Cancelled',
    ][$statusKey] ?? ucfirst($shipment->status ?? '-');
    $shipDateTxt = $shipment->date ? \Illuminate\Support\Carbon::parse($shipment->date)->format('d M Y') : '-';
    $orderScans = $shipment->orderScans ?? collect();
    $waves = $waves ?? collect();
    $ungroupedLines = $shipment->lines->filter(fn ($line) => !$line->shipment_order_scan_id)->values();
    $singleOrderFallbackLines = $orderScans->count() === 1 ? $ungroupedLines : collect();
    $pendingOrders = $orderScans->where('status', 'pending')->count();
    $skippedOrders = $orderScans->where('status', 'skip')->count();
    $flowStage = $statusKey === 'draft'
        ? ($orderScans->isNotEmpty() ? 'confirm' : ($shipment->lines->isNotEmpty() ? 'scan_order' : 'scan_item'))
        : 'done';
@endphp

@if(isset($isDummy) && $isDummy)
<div style="background-color: #fff3cd; color: #856404; padding: 10px 15px; border-radius: 6px; border: 1px solid #ffeeba; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;">
    <div>
        <strong>🧪 MENGGUNAKAN DUMMY MODE</strong><br>
        <span style="font-size: 0.9em;">Halaman ini sedang menggunakan data order simulasi. Aksi cetak resi dan lainnya tidak akan berdampak pada production/Shopee.</span>
    </div>
    <a href="?" style="background: #856404; color: #fff; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 0.85em; font-weight: bold;">Keluar Dummy</a>
</div>
@endif

<div class="sd-topbar">
        <a href="{{ route('sales.shipments.index') }}" class="sd-btn">Kembali</a>
    @if(app()->environment(['local', 'testing']))
    <button class="sd-btn" style="background:#fff3cd;color:#856404;border-color:#ffeeba" onclick="window.location.search = '?dummy=1'">🧪 Test Dummy</button>
    @endif
    <span class="sd-code">{{ $shipment->code }}</span>
    <span class="sd-pill sd-status {{ $statusKey }}">{{ $statusLabel }}</span>
    <span class="sd-spacer"></span>
    <span class="sd-pill">Qty <b>{{ number_format($totalQty,0,',','.') }}</b></span>
    <span class="sd-pill">Pesanan <b>{{ number_format($orderScans->count(),0,',','.') }}</b></span>
    @if($shipment->lines->isNotEmpty())
        <a href="{{ route('sales.shipments.export_lines', $shipment) }}" class="sd-btn sd-hide-mobile">Export CSV</a>
    @endif
    @if($statusKey === 'draft')
        <a href="{{ route('sales.shipments.edit', $shipment) }}" class="sd-btn sd-primary">Lanjut Scan</a>
    @endif
</div>

<div class="sd-wrap">
    <div class="sd-flow">
        <span class="sd-step {{ in_array($flowStage, ['scan_order','confirm','done'], true) ? 'done' : 'active' }}">Scan Barang</span><span class="sd-sep">-&gt;</span>
        <span class="sd-step {{ in_array($flowStage, ['confirm','done'], true) ? 'done' : ($flowStage === 'scan_order' ? 'active' : '') }}">Scan Pesanan</span><span class="sd-sep">-&gt;</span>
        <span class="sd-step {{ $flowStage === 'done' ? 'done' : ($flowStage === 'confirm' ? 'active' : '') }}">Konfirmasi Pesanan</span><span class="sd-sep">-&gt;</span>
        <span class="sd-step {{ $flowStage === 'done' ? 'active' : '' }}">Simpan &amp; Kurangi Stok</span>
    </div>

    @if($isDaily)
        <div class="sd-card">
            <div class="sd-head">
                <div>
                    <div class="sd-title">Gelombang Pengiriman</div>
                    <div class="sd-muted">Satu shipment harian dapat diposting bertahap per gelombang.</div>
                </div>
                <span class="sd-pill">{{ $waves->count() }} gelombang</span>
            </div>
            <div class="sd-body">
                @if($waves->isEmpty())
                    <div class="sd-empty">Gelombang pertama akan dibuat saat mulai scan.</div>
                @else
                    <div class="sd-waves">
                        @foreach($waves as $wave)
                            @php
                                $waveStatus = $wave->status === \App\Models\ShipmentWave::STATUS_POSTED ? 'posted' : '';
                                $waveStatusLabel = $wave->status === \App\Models\ShipmentWave::STATUS_POSTED
                                    ? 'Sudah diposting'
                                    : ($wave->status === \App\Models\ShipmentWave::STATUS_CANCELLED ? 'Dibatalkan' : 'Terbuka');
                                $waveQty = (int) $wave->lines->sum('qty_scanned');
                                $waveOrders = (int) $wave->orderScans->where('status', '!=', 'skip')->count();
                            @endphp
                            <div class="sd-wave">
                                <div class="sd-wave-no">W{{ str_pad((string) $wave->sequence, 2, '0', STR_PAD_LEFT) }}</div>
                                <div>
                                    <div class="sd-wave-title">{{ $wave->label ?: 'Gelombang ' . $wave->sequence }}</div>
                                    <div class="sd-wave-meta">{{ number_format($waveQty, 0, ',', '.') }} item · {{ number_format($waveOrders, 0, ',', '.') }} pesanan</div>
                                </div>
                                <span class="sd-wave-status {{ $waveStatus }}">{{ $waveStatusLabel }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="sd-grid">
        <div class="sd-card sd-kpi">
            <div class="sd-label">Qty Batch</div>
            <div class="sd-value">{{ number_format($totalQty,0,',','.') }}</div>
        </div>
        <div class="sd-card sd-kpi">
            <div class="sd-label">SKU</div>
            <div class="sd-value">{{ number_format($totalLines,0,',','.') }}</div>
        </div>
        <div class="sd-card sd-kpi">
            <div class="sd-label">Pesanan</div>
            <div class="sd-value">{{ number_format($orderScans->count(),0,',','.') }}</div>
        </div>
        @if($canSeeNominal)
            <div class="sd-card sd-kpi">
                <div class="sd-label">Estimasi HPP</div>
                <div class="sd-value">{{ number_format($totalHpp,0,',','.') }}</div>
            </div>
        @else
            <div class="sd-card sd-kpi">
                <div class="sd-label">Status</div>
                <div class="sd-value" style="font-size:1rem">{{ $statusLabel }}</div>
            </div>
        @endif
    </div>

    <div class="sd-tabs" role="tablist">
        <button type="button" class="sd-tab active" data-tab="pesanan">Pesanan <span class="sd-tab-count">{{ number_format($orderScans->count(),0,',','.') }}</span></button>
        <button type="button" class="sd-tab" data-tab="item">Item Batch <span class="sd-tab-count">{{ number_format($totalLines,0,',','.') }}</span></button>
        <button type="button" class="sd-tab" data-tab="info">Info Shipment</button>
    </div>

    <div class="sd-tabpane active" id="sd-tab-pesanan" role="tabpanel">
    <div class="sd-card">
        <div class="sd-head">
            <div>
                <div class="sd-title">Pesanan Tercatat</div>
                <div class="sd-muted">Nomor pesanan disimpan di shipment. Tautan invoice/order menyusul nanti.</div>
            </div>
            <span class="sd-pill">{{ number_format($pendingOrders,0,',','.') }} tunda</span>
        </div>
        <div class="sd-body">
            @if($orderScans->isEmpty())
                <div class="sd-empty">
                    Belum ada nomor pesanan yang dikonfirmasi.
                    @if($statusKey === 'draft')
                        <div style="margin-top:.55rem">
                            <a href="{{ route('sales.shipments.scan_order', $shipment) }}" class="sd-btn sd-primary">Scan Pesanan</a>
                        </div>
                    @endif
                </div>
            @else
                <div class="sd-list">
                    @foreach($orderScans->sortBy('id') as $scan)
                        @php
                            $scanStatus = $scan->status ?: 'pending';
                            $scanLabel = $scanStatus === 'skip' ? 'Diabaikan' : 'Ditunda';
                            $orderLines = $scan->lines
                                ->merge($singleOrderFallbackLines)
                                ->values();
                            $orderQty = (int) $orderLines->sum('qty_scanned');
                        @endphp
                        <div class="sd-order sd-order-group">
                            <div class="sd-order-lead">
                                <span class="sd-order-num">{{ $loop->iteration }}</span>
                                <div>
                                    <div class="sd-order-no">
                                        {{ $scan->order_no }}
                                        @if($scan->fulfillment && $scan->fulfillment->marketplaceOrder && $scan->fulfillment->marketplaceOrder->store)
                                            <span style="font-size:0.6rem; background:#f1f5f9; padding:2px 6px; border-radius:4px; margin-left:6px; color:#475569; border:1px solid #e2e8f0; vertical-align:middle;">{{ $scan->fulfillment->marketplaceOrder->store->name }}</span>
                                        @endif
                                    </div>
                                    <div class="sd-muted">
                                        {{ $scan->source === 'manual_scan' ? 'Belum tertaut' : $scan->source }}
                                        @if($scan->confirmed_at)
                                            · {{ $scan->confirmed_at->format('d M Y H:i') }}
                                        @endif
                                    </div>
                                </div>
                                <span class="sd-order-qty">x{{ number_format($orderQty,0,',','.') }}</span>
                            </div>
                            <div class="sd-order-items">
                                @forelse($orderLines as $line)
                                    <div class="sd-order-item">
                                        <div>
                                            <div class="sd-order-item-code">{{ $line->item?->code ?? '-' }}</div>
                                            <div class="sd-order-item-name">{{ $line->item?->name ?? '' }}</div>
                                        </div>
                                        <div class="sd-order-item-qty">x{{ number_format((int) $line->qty_scanned,0,',','.') }}</div>
                                    </div>
                                @empty
                                    <div class="sd-muted" style="padding:.45rem 0">Belum ada item di order ini.</div>
                                @endforelse
                            </div>
                            <span class="sd-badge {{ $scanStatus }}">{{ $scanLabel }}</span>
                        </div>
                    @endforeach
                    @if($orderScans->count() > 1 && $ungroupedLines->isNotEmpty())
                        <div class="sd-order sd-order-group">
                            <div class="sd-order-lead">
                                <span class="sd-order-num">—</span>
                                <div>
                                    <div class="sd-order-no">BELUM DIKELOMPOKKAN</div>
                                    <div class="sd-muted">Item lama atau scan tanpa order</div>
                                </div>
                                <span class="sd-order-qty">x{{ number_format((int) $ungroupedLines->sum('qty_scanned'),0,',','.') }}</span>
                            </div>
                            <div class="sd-order-items">
                                @foreach($ungroupedLines as $line)
                                    <div class="sd-order-item">
                                        <div>
                                            <div class="sd-order-item-code">{{ $line->item?->code ?? '-' }}</div>
                                            <div class="sd-order-item-name">{{ $line->item?->name ?? '' }}</div>
                                        </div>
                                        <div class="sd-order-item-qty">x{{ number_format((int) $line->qty_scanned,0,',','.') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
    </div>{{-- /sd-tab-pesanan --}}

    <div class="sd-tabpane" id="sd-tab-item" role="tabpanel">
    <div class="sd-card">
        <div class="sd-head">
            <div>
                <div class="sd-title">Item Batch</div>
                <div class="sd-muted">Ringkasan barang yang discan untuk shipment ini.</div>
            </div>
            <span class="sd-pill">{{ number_format($totalLines,0,',','.') }} SKU</span>
        </div>
        <div class="sd-body">
            @if($shipment->lines->isEmpty())
                <div class="sd-empty">Belum ada item.</div>
            @else
                @php
                    $linesGrouped = $shipment->lines
                        ->groupBy(fn ($l) => $l->item?->category?->name ?: 'Tanpa Kategori')
                        ->sortKeys();
                @endphp
                <div class="sd-table-wrap">
                    <table class="sd-table" id="sdBatchTable">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="sd-r">Qty</th>
                                @if($canSeeNominal)
                                    <th class="sd-r sd-hide-mobile">Unit HPP</th>
                                    <th class="sd-r sd-hide-mobile">Total HPP</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($linesGrouped as $catName => $group)
                                @php
                                    $gIdx = $loop->index;
                                    $catQty = (int) $group->sum(fn ($l) => (int)($l->qty_scanned ?? 0));
                                    $catHpp = (float) $group->sum(fn ($l) => (float)($l->total_hpp ?? 0));
                                    $catSku = $group->count();
                                @endphp
                                <tr class="sd-group" data-group="{{ $gIdx }}" aria-expanded="false">
                                    <td>
                                        <span class="sd-caret">▶</span>
                                        <span class="sd-group-cat">{{ $catName }}</span>
                                        <span class="sd-group-count">{{ $catSku }} SKU</span>
                                    </td>
                                    <td class="sd-r"><span class="sd-badge">{{ number_format($catQty,0,',','.') }} pcs</span></td>
                                    @if($canSeeNominal)
                                        <td class="sd-r sd-hide-mobile"></td>
                                        <td class="sd-r sd-hide-mobile">{{ number_format($catHpp,0,',','.') }}</td>
                                    @endif
                                </tr>
                                @foreach($group as $line)
                                    <tr class="sd-item-row sd-collapsed" data-group-row="{{ $gIdx }}">
                                        <td>
                                            <div class="sd-code-cell">{{ $line->item?->code ?? '-' }}</div>
                                            <div class="sd-name">{{ $line->item?->name ?? '-' }}</div>
                                        </td>
                                        <td class="sd-r">
                                            <span class="sd-badge">{{ number_format((int)($line->qty_scanned ?? 0),0,',','.') }} pcs</span>
                                        </td>
                                        @if($canSeeNominal)
                                            <td class="sd-r sd-hide-mobile">{{ number_format((float)($line->unit_hpp ?? 0),0,',','.') }}</td>
                                            <td class="sd-r sd-hide-mobile">{{ number_format((float)($line->total_hpp ?? 0),0,',','.') }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            @endforeach
                            <tr class="sd-total">
                                <td class="sd-r">Total</td>
                                <td class="sd-r">{{ number_format($totalQty,0,',','.') }}</td>
                                @if($canSeeNominal)
                                    <td></td>
                                    <td class="sd-r">{{ number_format($totalHpp,0,',','.') }}</td>
                                @endif
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    </div>{{-- /sd-tab-item --}}

    <div class="sd-tabpane" id="sd-tab-info" role="tabpanel">
    <div class="sd-card">
        <div class="sd-head">
            <div class="sd-title">Info Shipment</div>
        </div>
        <div class="sd-body">
            <div class="sd-meta">
                <div class="sd-meta-box">
                    <div class="sd-label">Store</div>
                    <div class="sd-value" style="font-size:.95rem">{{ $shipment->store?->code ?? '-' }}</div>
                    <div class="sd-muted">{{ $shipment->store?->name ?? '-' }}</div>
                </div>
                <div class="sd-meta-box">
                    <div class="sd-label">Tanggal</div>
                    <div class="sd-value" style="font-size:.95rem">{{ $shipDateTxt }}</div>
                    <div class="sd-muted">Dibuat oleh {{ $shipment->creator?->name ?? '-' }}</div>
                </div>
                <div class="sd-meta-box">
                    <div class="sd-label">Submit</div>
                    <div class="sd-value" style="font-size:.95rem">{{ $shipment->submitted_at ? $shipment->submitted_at->format('d M Y H:i') : '-' }}</div>
                    <div class="sd-muted">{{ $shipment->submitter?->name ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>
    </div>{{-- /sd-tab-info --}}
</div>

<script>
(function(){
    var tabs = document.querySelectorAll('.sd-tab');
    var panes = document.querySelectorAll('.sd-tabpane');
    function activate(name){
        tabs.forEach(function(t){ t.classList.toggle('active', t.dataset.tab === name); });
        panes.forEach(function(p){ p.classList.toggle('active', p.id === 'sd-tab-' + name); });
        try { history.replaceState(null, '', '#' + name); } catch(e){}
    }
    tabs.forEach(function(t){
        t.addEventListener('click', function(){ activate(t.dataset.tab); });
    });
    var hash = (location.hash || '').replace('#','');
    if (['pesanan','item','info'].indexOf(hash) !== -1) activate(hash);

    /* ── accordion Item Batch per kategori (default tertutup) ── */
    var batchTable = document.getElementById('sdBatchTable');
    if (batchTable) {
        batchTable.querySelectorAll('.sd-group').forEach(function (header) {
            header.addEventListener('click', function () {
                var idx = header.dataset.group;
                var open = header.classList.toggle('is-open');
                header.setAttribute('aria-expanded', open ? 'true' : 'false');
                batchTable.querySelectorAll('.sd-item-row[data-group-row="' + idx + '"]').forEach(function (row) {
                    row.classList.toggle('sd-collapsed', !open);
                });
            });
        });
    }
})();
</script>
@endsection

@if (session('stock_insufficient'))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const items = @json(session('stock_insufficient'));
    if (!window.GFID || typeof window.GFID.errorAlert !== 'function') return;
    const codes = items.map(item => item.code).filter(Boolean).join(', ');
    window.GFID.errorAlert(
        codes
            ? `Stok WH-RTS belum cukup untuk: ${codes}.`
            : 'Stok WH-RTS belum cukup untuk shipment ini.'
    );
});
</script>
@endpush
@endif

@if(($shipment->status ?? null) !== 'draft')
@push('scripts')
<script>
try {
    localStorage.removeItem('rk_state_{{ $shipment->id }}');
} catch {}
</script>
@endpush
@endif
