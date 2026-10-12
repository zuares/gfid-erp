@extends('layouts.app')

@section('title', $purchase_request->code)

@php
    $pr = $purchase_request;
    $estTotal        = $pr->lines->sum(fn ($l) => ($l->qty ?? 0) * ($l->unit_price ?? 0));
    $hasEstimate     = $pr->lines->whereNotNull('unit_price')->isNotEmpty();
    $actualSuppliers = $pr->lines->pluck('supplier')->filter()->unique('id');
    $firstPo         = $pr->purchaseOrders->first();
    $isDraft         = $pr->isDraft();
    $isConverted     = $pr->isConverted();
    $isConvertible   = $pr->isConvertible();
@endphp

@push('head')
<style>
  .pr-wrap { max-width:1080px; margin-inline:auto; padding:.65rem .75rem 3rem; }
  .mono { font-variant-numeric:tabular-nums; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono"; }
  .pr-topbar { position:sticky; top:0; z-index:250; display:flex; align-items:center; gap:.55rem; flex-wrap:wrap; padding:.55rem .7rem; margin:0 -.75rem .7rem; background:var(--card,#fff); border-bottom:1px solid rgba(148,163,184,.18); }
  .pr-back { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border:1px solid rgba(148,163,184,.3); border-radius:7px; color:#64748b; text-decoration:none; }
  .pr-back:hover { background:rgba(148,163,184,.1); color:#111827; }
  .pr-heading { min-width:150px; }
  .pr-eyebrow { display:block; color:#64748b; font-size:.64rem; font-weight:850; letter-spacing:.08em; text-transform:uppercase; }
  .pr-code { color:#111827; font-size:1.04rem; font-weight:900; }
  .pr-spacer { flex:1; }
  .pr-btn { display:inline-flex; align-items:center; justify-content:center; gap:.35rem; min-height:34px; padding:.3rem .65rem; border:1px solid rgba(148,163,184,.32); border-radius:7px; background:transparent; color:#475569; text-decoration:none; font-size:.76rem; font-weight:800; cursor:pointer; }
  .pr-btn:hover { background:rgba(148,163,184,.1); color:#111827; text-decoration:none; }
  .pr-btn-primary { color:#fff!important; background:#334155!important; border-color:#334155!important; }
  .pr-btn-primary:hover { background:#1e293b!important; border-color:#1e293b!important; }
  .pr-btn-success { color:#fff!important; background:#15803d!important; border-color:#15803d!important; }
  .pr-btn-danger { color:#b91c1c; border-color:rgba(220,38,38,.28); }
  .pr-actions-more { position:relative; }
  .pr-actions-more summary { list-style:none; }
  .pr-actions-more summary::-webkit-details-marker { display:none; }
  .pr-actions-menu { position:absolute; right:0; top:calc(100% + .35rem); z-index:20; min-width:150px; padding:.35rem; background:var(--card,#fff); border:1px solid var(--line); border-radius:8px; box-shadow:0 12px 25px rgba(15,23,42,.12); }
  .pr-actions-menu form { margin:0; }
  .pr-actions-menu button { width:100%; justify-content:flex-start; border:0; background:transparent; }
  .pr-actions-menu button:hover { background:rgba(148,163,184,.1); }
  .pr-card { background:var(--card,#fff); border:1px solid rgba(148,163,184,.18); border-radius:9px; overflow:hidden; margin-bottom:.65rem; }
  .pr-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); }
  .pr-kpi { min-width:0; padding:.7rem .8rem; border-right:1px solid rgba(148,163,184,.14); }
  .pr-kpi:last-child { border-right:0; }
  .pr-label { color:#64748b; font-size:.68rem; font-weight:850; letter-spacing:.04em; text-transform:uppercase; }
  .pr-value { margin-top:.18rem; color:#111827; font-size:.9rem; font-weight:850; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .pr-value.mono { font-size:.86rem; }
  .pr-badge { display:inline-flex; align-items:center; border-radius:999px; padding:.16rem .55rem; border:1px solid transparent; font-size:.7rem; font-weight:850; white-space:nowrap; }
  .pr-draft { background:rgba(148,163,184,.12); color:#64748b; border-color:rgba(148,163,184,.45); }
  .pr-approved { background:rgba(22,163,74,.1); color:#15803d; border-color:rgba(22,163,74,.4); }
  .pr-rejected { background:rgba(220,38,38,.08); color:#b91c1c; border-color:rgba(220,38,38,.4); }
  .pr-converted { background:rgba(59,130,246,.1); color:#1d4ed8; border-color:rgba(59,130,246,.4); }
  .pr-cancelled { background:rgba(100,116,139,.08); color:#475569; border-color:rgba(100,116,139,.4); }
  .pr-meta-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0; }
  .pr-meta { min-width:0; padding:.78rem .85rem; }
  .pr-meta + .pr-meta { border-left:1px solid rgba(148,163,184,.14); }
  .pr-meta-value { margin-top:.25rem; color:#334155; font-size:.84rem; font-weight:750; }
  .pr-meta-muted { color:#64748b; font-size:.8rem; }
  .pr-link { color:#2563eb; text-decoration:none; }
  .pr-link:hover { text-decoration:underline; }
  .pr-card-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.68rem .85rem; border-bottom:1px solid rgba(148,163,184,.14); }
  .pr-title { color:#334155; font-size:.88rem; font-weight:900; }
  .pr-subtitle { color:#64748b; font-size:.76rem; }
  .pr-table-wrap { overflow:auto; }
  .pr-table { width:100%; border-collapse:collapse; }
  .pr-table th,.pr-table td { padding:.58rem .7rem; border-bottom:1px solid rgba(148,163,184,.12); vertical-align:middle; }
  .pr-table th { color:#64748b; background:rgba(148,163,184,.04); font-size:.68rem; font-weight:900; letter-spacing:.04em; text-transform:uppercase; white-space:nowrap; }
  .pr-table td { color:#334155; font-size:.82rem; }
  .pr-table tbody tr:last-child td { border-bottom:0; }
  .pr-item-name { color:#111827; font-weight:850; line-height:1.2; }
  .pr-item-code { color:#64748b; font-size:.72rem; margin-top:.12rem; }
  .pr-qty { color:#111827; font-weight:800; white-space:nowrap; }
  .pr-unit { color:#64748b; font-size:.72rem; }
  .pr-secondary { color:#64748b; font-size:.72rem; margin-top:.12rem; }
  .pr-table tfoot td { border-top:2px solid rgba(148,163,184,.2); border-bottom:0; color:#111827; font-weight:900; }
  .pr-history { display:flex; overflow-x:auto; }
  .pr-history-step { position:relative; min-width:150px; flex:1 0 0; padding:.75rem .85rem .8rem 1.7rem; border-right:1px solid rgba(148,163,184,.14); }
  .pr-history-step:last-child { border-right:0; }
  .pr-history-step::before { content:""; position:absolute; left:.8rem; top:1rem; width:7px; height:7px; border-radius:50%; background:#94a3b8; box-shadow:0 0 0 3px rgba(148,163,184,.14); }
  .pr-history-title { color:#334155; font-size:.78rem; font-weight:850; }
  .pr-history-meta { color:#64748b; font-size:.7rem; margin-top:.12rem; }
  .pr-note { padding:.75rem .85rem; border-top:1px solid rgba(148,163,184,.14); color:#475569; font-size:.8rem; white-space:pre-line; }
  body[data-theme="dark"] .pr-code, body[data-theme="dark"] .pr-value, body[data-theme="dark"] .pr-item-name, body[data-theme="dark"] .pr-qty, body[data-theme="dark"] .pr-table tfoot td { color:#e2e8f0; }
  body[data-theme="dark"] .pr-meta-value, body[data-theme="dark"] .pr-table td, body[data-theme="dark"] .pr-title, body[data-theme="dark"] .pr-history-title { color:#cbd5e1; }
  body[data-theme="dark"] .pr-actions-menu { box-shadow:0 12px 25px rgba(0,0,0,.4); }
  @media (max-width:767.98px){
    .pr-wrap { padding:.45rem .5rem 3rem; }
    .pr-topbar { margin-inline:-.5rem; padding:.5rem; }
    .pr-spacer { display:none; }
    .pr-heading { flex:1; min-width:0; }
    .pr-topbar > .pr-btn-primary { order:5; width:100%; }
    .pr-topbar > .pr-btn:not(.pr-btn-primary), .pr-actions-more { order:4; }
    .pr-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .pr-kpi { border-bottom:1px solid rgba(148,163,184,.14); }
    .pr-kpi:nth-child(2n) { border-right:0; }
    .pr-kpi:nth-last-child(-n+2) { border-bottom:0; }
    .pr-meta-grid { grid-template-columns:1fr; }
    .pr-meta + .pr-meta { border-left:0; border-top:1px solid rgba(148,163,184,.14); }
    .pr-table-wrap { overflow:visible; }
    .pr-table,.pr-table tbody,.pr-table tr,.pr-table td { display:block; width:100%; }
    .pr-table thead { display:none; }
    .pr-table tr { padding:.62rem .7rem; border-bottom:1px solid rgba(148,163,184,.14); }
    .pr-table td { display:grid; grid-template-columns:6.5rem minmax(0,1fr); gap:.5rem; padding:.16rem 0; border:0; text-align:left!important; }
    .pr-table td.item-cell { display:block; padding:0 0 .35rem; }
    .pr-table td[data-label]::before { content:attr(data-label); color:#94a3b8; font-size:.63rem; font-weight:900; letter-spacing:.04em; text-transform:uppercase; }
    .pr-table tfoot { display:none; }
  }
</style>
@endpush

@section('content')
@php
  $hasLineNotes = $pr->lines->contains(fn ($line) => filled($line->notes));
  $totalQty = $pr->lines->sum(fn ($line) => (float) ($line->qty ?? 0));
@endphp
<div class="pr-wrap">

  <div class="pr-topbar">
    <a href="{{ route('purchasing.purchase_requests.index') }}" class="pr-back" title="Kembali"><i class="bi bi-arrow-left"></i></a>
    <div class="pr-heading"><span class="pr-eyebrow">Purchase Request</span><span class="pr-code mono">{{ $pr->code }}</span></div>
    <span class="pr-badge pr-{{ $pr->status }}">{{ pr_status_label($pr->status) }}</span>
    <div class="pr-spacer"></div>
    @if ($isConvertible && ($user->isOwner() || in_array($user->role, ['admin'], true) || $user->isDeveloper()))
      <a href="{{ route('purchasing.purchase_requests.allocate_suppliers', $pr) }}" class="pr-btn pr-btn-primary"><i class="bi bi-arrow-right-circle"></i>Pilih Supplier</a>
    @elseif ($firstPo)
      <a href="{{ route('purchasing.purchase_orders.show', $firstPo) }}" class="pr-btn pr-btn-primary"><i class="bi bi-box-arrow-up-right"></i>Buka PO</a>
    @endif
    @if ($isDraft)
      <a href="{{ route('purchasing.purchase_requests.edit', $pr) }}" class="pr-btn" title="Edit PR"><i class="bi bi-pencil"></i><span class="d-none d-md-inline">Edit</span></a>
    @endif
    @if ($canApproveReject)
      <details class="pr-actions-more">
        <summary class="pr-btn" title="Aksi lainnya"><i class="bi bi-three-dots"></i></summary>
        <div class="pr-actions-menu">
          <form method="POST" action="{{ route('purchasing.purchase_requests.approve', $pr) }}" onsubmit="return confirm('Setujui {{ $pr->code }}?')">@csrf<button class="pr-btn pr-btn-success" type="submit"><i class="bi bi-check-lg"></i>Setujui</button></form>
          <form method="POST" action="{{ route('purchasing.purchase_requests.reject', $pr) }}" onsubmit="return confirm('Tolak {{ $pr->code }}?')">@csrf<button class="pr-btn pr-btn-danger" type="submit"><i class="bi bi-x-lg"></i>Tolak</button></form>
        </div>
      </details>
    @endif
  </div>

  @if (session('success'))<div class="alert alert-success py-2 small mb-3">{{ session('success') }}</div>@endif
  @if (session('error'))<div class="alert alert-danger py-2 small mb-3">{{ session('error') }}</div>@endif

  <div class="pr-card">
    <div class="pr-kpis">
      <div class="pr-kpi"><div class="pr-label">Status</div><div class="pr-value"><span class="pr-badge pr-{{ $pr->status }}">{{ pr_status_label($pr->status) }}</span></div></div>
      <div class="pr-kpi"><div class="pr-label">Tanggal</div><div class="pr-value mono">{{ $pr->date?->format('d/m/Y') ?? '—' }}</div></div>
      <div class="pr-kpi"><div class="pr-label">Peminta</div><div class="pr-value">{{ $pr->requestedBy?->name ?? '—' }}</div></div>
      <div class="pr-kpi"><div class="pr-label">{{ $canSeeMoney ? 'Estimasi' : 'Barang' }}</div><div class="pr-value mono">{{ $canSeeMoney && $hasEstimate ? rupiah($estTotal) : $pr->lines->count().' item' }}</div></div>
    </div>
  </div>

  <div class="pr-card">
    <div class="pr-meta-grid">
      <div class="pr-meta"><div class="pr-label">Supplier</div><div class="pr-meta-value">
        @if ($actualSuppliers->isNotEmpty()){{ $actualSuppliers->pluck('name')->join(', ') }}
        @elseif ($pr->supplier){{ $pr->supplier->name }} <span class="pr-meta-muted mono">· {{ $pr->supplier->code }}</span>
        @else<span class="pr-meta-muted">—</span>@endif
      </div></div>
      <div class="pr-meta"><div class="pr-label">PO Terkait</div><div class="pr-meta-value">
        @forelse ($pr->purchaseOrders as $order)<a href="{{ route('purchasing.purchase_orders.show', $order) }}" class="pr-link mono me-2">{{ $order->code }}</a>@empty<span class="pr-meta-muted">—</span>@endforelse
      </div></div>
    </div>
    @if ($pr->notes)<div class="pr-note"><span class="pr-label d-block mb-1">Catatan</span>{{ $pr->notes }}</div>@endif
  </div>

  <div class="pr-card">
    <div class="pr-card-head">
      <div><div class="pr-title">Barang</div><div class="pr-subtitle">{{ $pr->lines->count() }} item · {{ number_format($totalQty, 2, ',', '.') }} qty</div></div>
      @if ($isDraft)<span class="pr-subtitle">Draft</span>@endif
    </div>
    <div class="pr-table-wrap">
      <table class="pr-table">
        <thead>
          <tr>
            <th>Barang</th>
            <th class="text-end">Qty</th>
            @if ($canSeeMoney)
              <th class="text-end">Harga Est.</th>
              <th class="text-end">Total</th>
            @endif
            @if ($hasLineNotes)<th>Catatan</th>@endif
            @if ($isConverted)
              <th>Supplier / PO</th>
            @endif
          </tr>
        </thead>
        <tbody>
          @forelse ($pr->lines as $line)
            <tr>
              <td class="item-cell">
                <div class="pr-item-name">{{ $line->item?->name ?? 'Barang tidak ditemukan' }}</div>
                <div class="pr-item-code mono">{{ $line->item?->code }}</div>
              </td>
              <td class="text-end mono" data-label="Qty">
                <span class="pr-qty">{{ number_format($line->qty, 2, ',', '.') }}</span> <span class="pr-unit">{{ $line->effectivePurchaseUnit() }}</span>
                @if ($line->effectiveConversionFactor() != 1)
                  <div class="pr-secondary">{{ number_format($line->qty * $line->effectiveConversionFactor(), 2, ',', '.') }} {{ $line->effectiveStockUnit() }}</div>
                @endif
              </td>
              @if ($canSeeMoney)
                <td class="text-end mono" data-label="Harga Est.">
                  {{ $line->unit_price !== null ? rupiah($line->unit_price) : '—' }}
                </td>
                <td class="text-end mono" data-label="Total">
                  {{ $line->unit_price !== null ? rupiah($line->qty * $line->unit_price) : '—' }}
                </td>
              @endif
              @if ($hasLineNotes)
                <td data-label="Catatan" class="pr-secondary">{{ $line->notes ?: '—' }}</td>
              @endif
              @if ($isConverted)
                <td data-label="Supplier / PO">
                  <span class="pr-qty">{{ $line->supplier?->code ?? '—' }}</span>
                  @if ($line->purchaseOrder)
                    <a href="{{ route('purchasing.purchase_orders.show', $line->purchaseOrder) }}"
                       class="pr-link mono d-block">
                      {{ $line->purchaseOrder->code }}
                    </a>
                  @endif
                </td>
              @endif
            </tr>
          @empty
            <tr>
              <td colspan="{{ 2 + ($canSeeMoney ? 2 : 0) + ($hasLineNotes ? 1 : 0) + ($isConverted ? 1 : 0) }}" class="text-center pr-secondary py-4">—</td>
            </tr>
          @endforelse
        </tbody>
        @if ($canSeeMoney && $hasEstimate && $pr->lines->count() > 1)
          <tfoot>
            <tr>
              <td colspan="{{ 2 + ($hasLineNotes ? 1 : 0) + ($isConverted ? 1 : 0) }}" class="text-end">Total Estimasi</td>
              <td></td>
              <td class="text-end mono">{{ rupiah($estTotal) }}</td>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>
  </div>

  <div class="pr-card">
    <div class="pr-card-head"><div class="pr-title">Riwayat</div></div>
    <div class="pr-history">
      <div class="pr-history-step">
        <div class="pr-history-title">Dibuat</div>
        <div class="pr-history-meta">{{ $pr->requestedBy?->name ?? '—' }}</div>
        <div class="pr-history-meta">{{ $pr->created_at?->format('d/m/Y H:i') }}</div>
      </div>
      @if ($pr->approved_by || $pr->isApproved())
        <div class="pr-history-step">
        <div class="pr-history-title">Disetujui</div>
          <div class="pr-history-meta">{{ $pr->approvedBy?->name ?? '—' }}</div>
        </div>
      @endif
      @if ($pr->isRejected())
        <div class="pr-history-step">
        <div class="pr-history-title">Ditolak</div>
          <div class="pr-history-meta">{{ $pr->rejectedBy?->name ?? '—' }}</div>
          <div class="pr-history-meta">{{ $pr->updated_at?->format('d/m/Y H:i') }}</div>
        </div>
      @endif
      @if ($pr->isConverted())
        <div class="pr-history-step">
        <div class="pr-history-title">Dibuatkan PO</div>
          <div class="pr-history-meta mono">{{ $pr->purchaseOrders->pluck('code')->join(', ') }}</div>
          <div class="pr-history-meta">{{ $pr->converted_at?->format('d/m/Y H:i') }}</div>
        </div>
      @endif
      @if ($isDraft)
        <div class="pr-history-step">
        <div class="pr-history-title">Draft</div>
        <div class="pr-history-meta">{{ $pr->updated_at?->format('d/m/Y H:i') }}</div>
        </div>
      @endif
    </div>
  </div>

</div>
@endsection
