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
.sd-danger{color:#991b1b;border-color:rgba(153,27,27,.25);background:transparent}
.sd-status{font-weight:850;color:#334155;background:rgba(148,163,184,.08)}
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
@endphp

<div class="sd-topbar">
    <a href="{{ route('sales.shipments.edit', $shipment) }}" class="sd-btn sd-topbar-nav">
        <i class="bi bi-box-seam" aria-hidden="true"></i> Scan Item
    </a>
    @if (!$isItemFirst)
        <a href="{{ route('sales.shipments.scan_order', $shipment) }}" class="sd-btn sd-topbar-nav">
            <i class="bi bi-upc-scan" aria-hidden="true"></i> Scan Order
        </a>
    @endif
    <a href="{{ route('sales.shipments.index') }}" class="sd-btn sd-topbar-nav">
        <i class="bi bi-list-ul" aria-hidden="true"></i> Daftar Shipment
    </a>
    <span class="sd-code">{{ $shipment->code }}</span>
    <span class="sd-pill sd-status">{{ $statusLabel }}</span>
    <span class="sd-spacer"></span>
    <span class="sd-pill">Qty <b>{{ number_format($totalQty, 0, ',', '.') }}</b></span>
    @if ($isItemFirst)
        <span class="sd-pill">Mapping <b>Otomatis</b></span>
    @else
        <span class="sd-pill">Pesanan <b>{{ number_format($orderScans->count(), 0, ',', '.') }}</b></span>
    @endif
    @if($statusKey === 'draft')
        <form id="shipmentSubmitForm" class="sd-inline-form" action="{{ route('sales.shipments.submit', $shipment) }}" method="POST"
              data-gf-confirm
              data-gf-confirm-title="Submit shipment?"
              data-gf-confirm-summary='@json(["orders" => $orderScans->count(), "items" => $totalLines, "qty" => $totalQty])'
              data-gf-confirm-text="{{ $isItemFirst ? 'Mapping item otomatis · Item/SKU: ' . $totalLines . ' · Total qty: ' . $totalQty : 'Order discan: ' . $orderScans->count() . ' · Item/SKU: ' . $totalLines . ' · Total qty: ' . $totalQty }}. Stok akan dikurangi dari WH-RTS."
              data-gf-confirm-ok="Submit"
              data-gf-confirm-cancel="Batal">
            @csrf
            <button type="submit" class="sd-btn sd-primary sd-topbar-primary"
                    title="{{ !empty($stockInsufficient) ? 'Stok WH-RTS belum cukup' : 'Submit shipment' }}"
                    @disabled($lines->count() === 0 || !empty($stockInsufficient))>
                <i class="bi bi-check2-circle" aria-hidden="true"></i> Submit Shipment
            </button>
        </form>
    @else
        <a href="{{ route('sales.shipments.show', $shipment) }}" class="sd-btn sd-primary sd-topbar-primary">Lihat Detail</a>
    @endif
</div>

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

    <div class="sd-card">
            <div class="sd-head">
                <div><div class="sd-title">{{ $isItemFirst ? 'Mapping Order & Item' : 'Order Tercatat' }}</div><div class="sd-muted">{{ $isItemFirst ? 'Item dipetakan otomatis dari shipment sebelum stok dikurangi.' : 'Review grouping order/no resi dan item sebelum stok dikurangi.' }}</div></div>
                <span class="sd-pill">{{ $isItemFirst ? 'Otomatis' : number_format($pendingOrders, 0, ',', '.') . ' tunda' }}</span>
            </div>
            <div class="sd-order-search">
                <input type="search" id="orderSearchInput" placeholder="Cari nomor order atau no resi..." autocomplete="off">
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

})();
</script>
@endsection
