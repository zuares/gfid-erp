@extends('layouts.app')

@section('title', 'Dashboard Pengadaan')

@push('head')
<style>
  /* ── Layout ──────────────────────────────────────────────── */
  .page-wrap { max-width:1080px; margin-inline:auto; padding-bottom:3rem; }
  .mono { font-variant-numeric:tabular-nums; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono"; }

  /* ── KPI Strip ───────────────────────────────────────────── */
  .kpi-strip { display:grid; grid-template-columns:repeat(auto-fill,minmax(148px,1fr)); gap:.65rem; }
  @media(max-width:575.98px){ .kpi-strip { grid-template-columns:repeat(2,1fr); } }

  .kpi-card {
    background:var(--card); border:1px solid var(--line); border-radius:14px;
    padding:.9rem 1rem; display:block; text-decoration:none; color:inherit;
    transition:box-shadow .15s, transform .1s;
  }
  a.kpi-card:hover { box-shadow:0 4px 20px rgba(0,0,0,.09); transform:translateY(-1px); color:inherit; }
  .kpi-num   { font-size:1.85rem; font-weight:750; line-height:1; letter-spacing:-.02em; }
  .kpi-rp    { font-size:1.05rem; font-weight:700; line-height:1.2; }
  .kpi-label { font-size:.72rem; color:var(--muted); margin-top:.3rem; line-height:1.3; font-weight:500; }
  .kpi-sub   { font-size:.68rem; color:var(--muted); margin-top:.1rem; }

  .kpi-red    { border-left:4px solid #ef4444; }
  .kpi-yellow { border-left:4px solid #f59e0b; }
  .kpi-blue   { border-left:4px solid #3b82f6; }
  .kpi-green  { border-left:4px solid #22c55e; }
  .kpi-purple { border-left:4px solid #8b5cf6; }
  .kpi-slate  { border-left:4px solid #94a3b8; }

  /* ── Pipeline ────────────────────────────────────────────── */
  .pipeline {
    background:var(--card); border:1px solid var(--line); border-radius:14px;
    display:flex; overflow:hidden; min-width:520px;
  }
  .pipeline-stage {
    flex:1; padding:.85rem .9rem; border-right:1px solid var(--line); position:relative;
  }
  .pipeline-stage:last-child { border-right:none; }
  .pipeline-num   { font-size:1.5rem; font-weight:750; line-height:1; }
  .pipeline-label {
    font-size:.68rem; text-transform:uppercase; letter-spacing:.06em;
    color:var(--muted); font-weight:600; margin-top:.3rem;
  }
  .pipeline-sub { font-size:.67rem; color:var(--muted); margin-top:.08rem; }
  .pipeline-arrow {
    position:absolute; right:-9px; top:50%; transform:translateY(-50%);
    width:18px; height:18px; background:var(--card); border:1px solid var(--line);
    border-radius:50%; display:flex; align-items:center; justify-content:center;
    font-size:.55rem; color:var(--muted); z-index:1;
  }
  @media(max-width:767.98px){
    .pipeline { flex-direction:column; min-width:unset; }
    .pipeline-stage { border-right:none; border-bottom:1px solid var(--line); }
    .pipeline-stage:last-child { border-bottom:none; }
    .pipeline-arrow { display:none; }
  }

  /* ── Cards (same as PR/GRN show pages) ──────────────────── */
  .card-section { background:var(--card); border:1px solid var(--line); border-radius:14px; overflow:hidden; }
  .card-section-header {
    padding:.6rem 1rem; border-bottom:1px solid var(--line);
    font-size:.72rem; text-transform:uppercase; letter-spacing:.07em;
    color:var(--muted); font-weight:600;
    display:flex; align-items:center; justify-content:space-between;
  }
  .card-section-header .hcount {
    font-weight:700; color:var(--body); font-size:.78rem;
    text-transform:none; letter-spacing:0;
  }

  /* ── Table (same pattern as PO/PR index) ─────────────────── */
  .table thead th {
    border-bottom:1px solid var(--line);
    font-size:.72rem; text-transform:uppercase; letter-spacing:.07em;
    color:var(--muted); padding:.5rem .75rem; white-space:nowrap; font-weight:600;
  }
  .table tbody td {
    vertical-align:middle; font-size:.83rem; padding:.45rem .75rem;
    border-bottom:1px solid var(--line);
  }
  .table tbody tr:last-child td { border-bottom:none; }
  .dash-row:hover td { background:rgba(59,130,246,.035); }

  /* ── Badges ──────────────────────────────────────────────── */
  .badge-status {
    border-radius:999px; font-size:.7rem; padding:.1rem .6rem;
    border:1px solid transparent; white-space:nowrap; display:inline-block;
  }
  .badge-draft    { background:rgba(148,163,184,.12); color:#64748b; border-color:rgba(148,163,184,.5); }
  .badge-approved { background:rgba(22,163,74,.12);  color:#15803d; border-color:rgba(22,163,74,.6); }
  .badge-partial  { background:rgba(234,179,8,.12);  color:#a16207; border-color:rgba(234,179,8,.5); }
  .badge-danger   { background:rgba(220,38,38,.08);  color:#b91c1c; border-color:rgba(220,38,38,.5); }
  .badge-paid     { background:rgba(22,163,74,.12);  color:#15803d; border-color:rgba(22,163,74,.5); }
  .badge-blue     { background:rgba(59,130,246,.10); color:#1d4ed8; border-color:rgba(59,130,246,.5); }

  /* ── Blocker tags ────────────────────────────────────────── */
  .blocker {
    font-size:.64rem; background:rgba(234,179,8,.12); color:#92400e;
    border:1px solid rgba(234,179,8,.4); border-radius:4px;
    padding:.1rem .4rem; display:inline-block;
  }

  /* ── Activity grid ───────────────────────────────────────── */
  .activity-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:.75rem; }
  .act-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:.45rem 1rem; border-bottom:1px solid var(--line);
    font-size:.82rem; gap:.5rem;
  }
  .act-row:last-child { border-bottom:none; }
  .act-code { font-weight:600; white-space:nowrap; color:inherit; text-decoration:none; }
  .act-code:hover { text-decoration:underline; color:#2563eb; }
  .act-meta { font-size:.7rem; color:var(--muted); margin-top:.05rem; }

  /* ── Section divider ─────────────────────────────────────── */
  .sec-divider {
    font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em;
    color:var(--muted); margin-top:1.75rem; margin-bottom:.65rem;
    display:flex; align-items:center; gap:.5rem;
  }
  .sec-divider::after { content:''; flex:1; height:1px; background:var(--line); }

  /* ── Link ────────────────────────────────────────────────── */
  .tbl-link { color:inherit; text-decoration:none; font-weight:600; }
  .tbl-link:hover { text-decoration:underline; color:#2563eb; }

  @media(max-width:767.98px){
    .page-wrap { padding-inline:.75rem; }
    .kpi-strip { grid-template-columns:repeat(2,1fr); }
    .table-responsive .table tbody td { font-size:.78rem; }
  }
</style>
@endpush

@section('content')
<div class="page-wrap py-3">

  {{-- ── HEADER ──────────────────────────────────────────────── --}}
  <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
    <div>
      <h2 class="mb-0">Dashboard Pengadaan</h2>
      <div class="text-muted small">{{ now()->isoFormat('dddd, D MMMM Y') }}</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      @if ($hasPrTable && Route::has('purchasing.purchase_requests.create'))
        <a href="{{ route('purchasing.purchase_requests.create') }}" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-plus me-1"></i>PR
        </a>
      @endif
      @if (Route::has('purchasing.purchase_orders.create') && ($isOwner || $isAdmin))
        <a href="{{ route('purchasing.purchase_orders.create') }}" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-plus me-1"></i>PO
        </a>
      @endif
      @if (Route::has('purchasing.purchase_receipts.create'))
        <a href="{{ route('purchasing.purchase_receipts.create') }}" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-plus me-1"></i>GRN
        </a>
      @endif
      @if ($hasInvoiceTable && Route::has('purchasing.supplier_invoices.create') && ($isOwner || $isAdmin))
        <a href="{{ route('purchasing.supplier_invoices.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-plus me-1"></i>Faktur
        </a>
      @endif
    </div>
  </div>

  {{-- ── KPI STRIP ────────────────────────────────────────────── --}}
  <div class="kpi-strip mb-3">

    <a href="{{ route('purchasing.material_shortages.index') }}"
       class="kpi-card {{ $shortageCount > 0 ? 'kpi-red' : 'kpi-slate' }}">
      <div class="kpi-num {{ $shortageCount > 0 ? 'text-danger' : '' }}">{{ $shortageCount }}</div>
      <div class="kpi-label">Material Kurang</div>
      <div class="kpi-sub">dari {{ $shortageItemCount }} material</div>
    </a>

    @if ($hasPrTable)
    <a href="{{ route('purchasing.purchase_requests.index', ['status' => 'draft']) }}"
       class="kpi-card {{ $prDraftCount > 0 ? 'kpi-yellow' : 'kpi-slate' }}">
      <div class="kpi-num">{{ $prDraftCount }}</div>
      <div class="kpi-label">PR Draft</div>
      <div class="kpi-sub">menunggu approval</div>
    </a>
    @endif

    @if ($hasPrTable)
    <a href="{{ route('purchasing.purchase_requests.index', ['status' => 'approved']) }}"
       class="kpi-card {{ $prApprovedNotConvertedCount > 0 ? 'kpi-purple' : 'kpi-slate' }}">
      <div class="kpi-num">{{ $prApprovedNotConvertedCount }}</div>
      <div class="kpi-label">PR Belum Jadi PO</div>
      <div class="kpi-sub">sudah disetujui</div>
    </a>
    @endif

    <a href="{{ route('purchasing.purchase_orders.index') }}"
       class="kpi-card {{ ($poApprovedNotReceivedCount + $poPartialCount) > 0 ? 'kpi-yellow' : 'kpi-slate' }}">
      <div class="kpi-num">{{ $poApprovedNotReceivedCount + $poPartialCount }}</div>
      <div class="kpi-label">PO Belum Diterima</div>
      <div class="kpi-sub">{{ $poApprovedNotReceivedCount }} belum · {{ $poPartialCount }} sebagian</div>
    </a>

    @if ($hasInvoiceTable)
    <a href="{{ route('purchasing.supplier_invoices.index') }}"
       class="kpi-card {{ $invOverdueCount > 0 ? 'kpi-red' : ($invOutstandingCount > 0 ? 'kpi-yellow' : 'kpi-slate') }}">
      <div class="kpi-num {{ $invOverdueCount > 0 ? 'text-danger' : '' }}">{{ $invOverdueCount }}</div>
      <div class="kpi-label">Invoice Overdue</div>
      @if ($canSeeMoney && $invOverdueTotal > 0)
        <div class="kpi-sub mono">{{ rupiah($invOverdueTotal) }}</div>
      @else
        <div class="kpi-sub">{{ $invOutstandingCount }} outstanding</div>
      @endif
    </a>
    @endif

    @if ($canSeeMoney)
    <div class="kpi-card kpi-green" style="cursor:default;">
      <div class="kpi-rp mono">{{ rupiah($payThisMonthTotal) }}</div>
      <div class="kpi-label">Bayar Bulan Ini</div>
      <div class="kpi-sub">{{ now()->isoFormat('MMMM') }}</div>
    </div>
    @endif

    <div class="kpi-card kpi-slate" style="cursor:default;">
      <div class="kpi-num">{{ $poClosedMonthCount }}</div>
      <div class="kpi-label">PO Closed</div>
      <div class="kpi-sub">{{ now()->isoFormat('MMMM') }}</div>
    </div>

  </div>

  {{-- ── PIPELINE ─────────────────────────────────────────────── --}}
  <div class="sec-divider">Pipeline Pengadaan</div>
  <div style="overflow-x:auto;" class="mb-4">
    <div class="pipeline">

      @if ($hasPrTable)
      <div class="pipeline-stage">
        <div class="pipeline-num">{{ $prDraftCount + $prApprovedNotConvertedCount }}</div>
        <div class="pipeline-label">Purchase Request</div>
        <div class="pipeline-sub">{{ $prDraftCount }} draft · {{ $prApprovedNotConvertedCount }} approved</div>
        <div class="pipeline-arrow"><i class="bi bi-chevron-right"></i></div>
      </div>
      @endif

      <div class="pipeline-stage">
        <div class="pipeline-num">{{ $poDraftCount + $poApprovedNotReceivedCount + $poPartialCount }}</div>
        <div class="pipeline-label">Purchase Order</div>
        <div class="pipeline-sub">{{ $poDraftCount }} draft · {{ $poApprovedNotReceivedCount + $poPartialCount }} belum terima</div>
        <div class="pipeline-arrow"><i class="bi bi-chevron-right"></i></div>
      </div>

      <div class="pipeline-stage">
        <div class="pipeline-num">{{ $grnThisMonth }}</div>
        <div class="pipeline-label">Penerimaan Barang</div>
        <div class="pipeline-sub">GRN bulan ini</div>
        @if ($hasInvoiceTable)<div class="pipeline-arrow"><i class="bi bi-chevron-right"></i></div>@endif
      </div>

      @if ($hasInvoiceTable)
      <div class="pipeline-stage">
        <div class="pipeline-num {{ $poFullyNoInvoiceCount > 0 ? 'text-warning' : '' }}">{{ $poFullyNoInvoiceCount }}</div>
        <div class="pipeline-label">Perlu Faktur</div>
        <div class="pipeline-sub">terima penuh, belum invoice</div>
        <div class="pipeline-arrow"><i class="bi bi-chevron-right"></i></div>
      </div>

      <div class="pipeline-stage">
        <div class="pipeline-num {{ $invOutstandingCount > 0 ? 'text-danger' : '' }}">{{ $invOutstandingCount }}</div>
        <div class="pipeline-label">Invoice Belum Bayar</div>
        @if ($canSeeMoney && $invOutstandingTotal > 0)
          <div class="pipeline-sub mono">{{ rupiah($invOutstandingTotal) }}</div>
        @else
          <div class="pipeline-sub">outstanding</div>
        @endif
      </div>
      @endif

    </div>
  </div>

  {{-- ── ACTION ITEMS ─────────────────────────────────────────── --}}
  @php
    $hasActions = $prApprovedNotConvertedList->isNotEmpty()
               || $poBelumTerimaList->isNotEmpty()
               || $poFullyNoInvoiceList->isNotEmpty()
               || $invOutstandingList->isNotEmpty();
  @endphp

  <div class="sec-divider">Perlu Tindakan</div>

  @if (!$hasActions)
    <div class="card-section mb-3">
      <div class="py-5 text-center text-muted">
        <i class="bi bi-check-circle" style="font-size:2rem;color:#22c55e;display:block;margin-bottom:.5rem;"></i>
        <div class="fw-semibold">Semua bersih!</div>
        <div class="small">Tidak ada item yang perlu perhatian saat ini.</div>
      </div>
    </div>
  @endif

  {{-- A: PR approved → buat PO --}}
  @if ($hasPrTable && $prApprovedNotConvertedList->isNotEmpty())
  <div class="card-section mb-3">
    <div class="card-section-header">
      <span>PR Disetujui — Belum Dibuat PO</span>
      <span class="hcount">{{ $prApprovedNotConvertedList->count() }}</span>
    </div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead>
          <tr>
            <th>No PR</th>
            <th>Tanggal</th>
            <th>Peminta</th>
            <th class="text-center">Item</th>
            @if ($canSeeMoney)<th class="text-end">Est. Total</th>@endif
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($prApprovedNotConvertedList as $pr)
          @php $estTotal = $pr->lines->sum(fn($l) => ($l->qty ?? 0) * ($l->unit_price ?? 0)); @endphp
          <tr class="dash-row">
            <td><a href="{{ route('purchasing.purchase_requests.show', $pr->id) }}" class="tbl-link mono">{{ $pr->code }}</a></td>
            <td class="mono text-muted">{{ $pr->created_at?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $pr->requestedBy?->name ?? '—' }}</td>
            <td class="text-center">{{ $pr->lines->count() }}</td>
            @if ($canSeeMoney)
            <td class="text-end mono">{{ $estTotal > 0 ? rupiah($estTotal) : '—' }}</td>
            @endif
            <td class="text-end">
              <a href="{{ route('purchasing.purchase_requests.show', $pr->id) }}"
                 class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;padding:.2rem .6rem;">
                Lihat
              </a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  {{-- B: PO belum diterima --}}
  @if ($poBelumTerimaList->isNotEmpty())
  <div class="card-section mb-3">
    <div class="card-section-header">
      <span>PO Approved — Belum Diterima Barang</span>
      <span class="hcount">{{ $poBelumTerimaList->count() }}</span>
    </div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead>
          <tr>
            <th>No PO</th>
            <th>Supplier</th>
            <th>Tanggal PO</th>
            <th>Status Terima</th>
            @if ($canSeeMoney)<th class="text-end">Total</th>@endif
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($poBelumTerimaList as $po)
          @php
            $rs      = $po->received_status ?? 'not_received';
            $rsCls   = match($rs) { 'partial' => 'badge-partial', 'fully_received' => 'badge-approved', default => 'badge-draft' };
            $rsLabel = match($rs) { 'partial' => 'Sebagian', 'fully_received' => 'Lengkap', default => 'Belum ada GRN' };
          @endphp
          <tr class="dash-row">
            <td><a href="{{ route('purchasing.purchase_orders.show', $po->id) }}" class="tbl-link mono">{{ $po->code }}</a></td>
            <td>{{ $po->supplier?->name ?? '—' }}</td>
            <td class="mono text-muted">{{ $po->date?->format('d/m/Y') ?? '—' }}</td>
            <td><span class="badge-status {{ $rsCls }}">{{ $rsLabel }}</span></td>
            @if ($canSeeMoney)<td class="text-end mono">{{ rupiah($po->grand_total) }}</td>@endif
            <td class="text-end">
              <a href="{{ route('purchasing.purchase_orders.show', $po->id) }}"
                 class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;padding:.2rem .6rem;">
                Lihat
              </a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  {{-- C: Sudah terima penuh, belum ada faktur --}}
  @if ($hasInvoiceTable && $poFullyNoInvoiceList->isNotEmpty())
  <div class="card-section mb-3">
    <div class="card-section-header">
      <span>Barang Diterima Penuh — Belum Ada Faktur Supplier</span>
      <span class="hcount">{{ $poFullyNoInvoiceList->count() }}</span>
    </div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead>
          <tr>
            <th>No PO</th>
            <th>Supplier</th>
            <th>Tanggal PO</th>
            @if ($canSeeMoney)<th class="text-end">Total PO</th>@endif
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($poFullyNoInvoiceList as $po)
          <tr class="dash-row">
            <td><a href="{{ route('purchasing.purchase_orders.show', $po->id) }}" class="tbl-link mono">{{ $po->code }}</a></td>
            <td>{{ $po->supplier?->name ?? '—' }}</td>
            <td class="mono text-muted">{{ $po->date?->format('d/m/Y') ?? '—' }}</td>
            @if ($canSeeMoney)<td class="text-end mono">{{ rupiah($po->grand_total) }}</td>@endif
            <td class="text-end" style="white-space:nowrap;">
              <a href="{{ route('purchasing.purchase_orders.show', $po->id) }}"
                 class="btn btn-sm btn-outline-secondary me-1" style="font-size:.72rem;padding:.2rem .6rem;">
                Lihat
              </a>
              @if (($isOwner || $isAdmin) && Route::has('purchasing.supplier_invoices.create'))
              <a href="{{ route('purchasing.supplier_invoices.create', ['purchase_order_id' => $po->id]) }}"
                 class="btn btn-sm btn-outline-primary" style="font-size:.72rem;padding:.2rem .6rem;">
                <i class="bi bi-plus me-1"></i>Faktur
              </a>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  {{-- D: Invoice outstanding --}}
  @if ($hasInvoiceTable && $invOutstandingList->isNotEmpty())
  <div class="card-section mb-3">
    <div class="card-section-header">
      <span>Invoice Outstanding</span>
      <span class="hcount d-flex align-items-center gap-2">
        {{ $invOutstandingList->count() }}
        @if ($canSeeMoney && $invOutstandingTotal > 0)
          <span class="text-danger mono fw-bold" style="font-size:.82rem;">{{ rupiah($invOutstandingTotal) }}</span>
        @endif
      </span>
    </div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead>
          <tr>
            <th>No Faktur</th>
            <th>Supplier</th>
            <th>No PO</th>
            <th>Jatuh Tempo</th>
            <th>Status</th>
            @if ($canSeeMoney)
            <th class="text-end">Total</th>
            <th class="text-end">Sisa</th>
            @endif
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($invOutstandingList as $inv)
          @php
            $overdue     = $inv->due_date && $inv->due_date < now()->startOfDay();
            $outstanding = max(0, ($inv->total_amount ?? 0) - ($inv->paid_amount ?? 0));
          @endphp
          <tr class="dash-row">
            <td>
              <a href="{{ route('purchasing.supplier_invoices.show', $inv->id) }}" class="tbl-link mono">
                {{ $inv->invoice_no }}
              </a>
            </td>
            <td>{{ $inv->supplier?->name ?? '—' }}</td>
            <td class="mono">
              @if ($inv->purchaseOrder)
                <a href="{{ route('purchasing.purchase_orders.show', $inv->purchaseOrder->id) }}" class="tbl-link">
                  {{ $inv->purchaseOrder->code }}
                </a>
              @else —
              @endif
            </td>
            <td class="mono {{ $overdue ? 'text-danger fw-semibold' : 'text-muted' }}">
              {{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '—' }}
              @if ($overdue)<span class="badge-status badge-danger ms-1">Overdue</span>@endif
            </td>
            <td>
              <span class="badge-status {{ $inv->status === 'partial_paid' ? 'badge-partial' : 'badge-blue' }}">
                {{ $inv->status === 'partial_paid' ? 'Sebagian' : 'Belum Bayar' }}
              </span>
            </td>
            @if ($canSeeMoney)
            <td class="text-end mono">{{ rupiah($inv->total_amount ?? 0) }}</td>
            <td class="text-end mono {{ $overdue ? 'text-danger' : '' }}">{{ rupiah($outstanding) }}</td>
            @endif
            <td>
              <a href="{{ route('purchasing.supplier_invoices.show', $inv->id) }}"
                 class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;padding:.2rem .6rem;">
                Lihat
              </a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  {{-- PO siap close --}}
  @if ($poReadyCloseList->isNotEmpty())
  <div class="card-section mb-3">
    <div class="card-section-header">
      <span>PO Siap Close</span>
      <span class="hcount">{{ $poReadyCloseList->count() }}</span>
    </div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead>
          <tr>
            <th>No PO</th>
            <th>Supplier</th>
            <th>Tanggal</th>
            @if ($canSeeMoney)<th class="text-end">Nilai</th>@endif
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($poReadyCloseList as $po)
          <tr class="dash-row">
            <td><a href="{{ route('purchasing.purchase_orders.show', $po->id) }}" class="tbl-link mono">{{ $po->code }}</a></td>
            <td>{{ $po->supplier?->name ?? '—' }}</td>
            <td class="mono text-muted">{{ $po->date?->format('d/m/Y') ?? '—' }}</td>
            @if ($canSeeMoney)<td class="text-end mono">{{ rupiah($po->grand_total) }}</td>@endif
            <td class="text-end" style="white-space:nowrap;">
              <a href="{{ route('purchasing.purchase_orders.show', $po->id) }}"
                 class="btn btn-sm btn-outline-secondary me-1" style="font-size:.72rem;padding:.2rem .6rem;">
                Lihat
              </a>
              @if ($isOwner && Route::has('purchasing.purchase_orders.close'))
              <form method="POST" action="{{ route('purchasing.purchase_orders.close', $po->id) }}"
                    style="display:inline;" onsubmit="return confirm('Close PO {{ $po->code }}?')">
                @csrf
                <button type="submit" class="btn btn-sm btn-dark" style="font-size:.72rem;padding:.2rem .6rem;">
                  Close
                </button>
              </form>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  {{-- PO belum bisa close --}}
  @if ($poNotReadyCloseList->isNotEmpty())
  <div class="card-section mb-3">
    <div class="card-section-header">
      <span>PO Belum Bisa Close</span>
      <span class="hcount">{{ $poNotReadyCloseList->count() }}</span>
    </div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead>
          <tr>
            <th>No PO</th>
            <th>Supplier</th>
            <th>Tanggal</th>
            <th>Alasan</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($poNotReadyCloseList as $po)
          <tr class="dash-row">
            <td><a href="{{ route('purchasing.purchase_orders.show', $po->id) }}" class="tbl-link mono">{{ $po->code }}</a></td>
            <td>{{ $po->supplier?->name ?? '—' }}</td>
            <td class="mono text-muted">{{ $po->date?->format('d/m/Y') ?? '—' }}</td>
            <td>
              @foreach ($po->_blockers as $blk)
                <span class="blocker me-1">{{ $blk }}</span>
              @endforeach
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif

  {{-- ── RECENT ACTIVITY ──────────────────────────────────────── --}}
  @php
    $hasActivity = $recentPr->isNotEmpty() || $recentPo->isNotEmpty()
                || $recentGrn->isNotEmpty() || $recentPayments->isNotEmpty();
  @endphp
  @if ($hasActivity)
  <div class="sec-divider">Aktivitas Terbaru</div>
  <div class="activity-grid">

    @if ($hasPrTable && $recentPr->isNotEmpty())
    <div class="card-section">
      <div class="card-section-header"><span>Purchase Request</span></div>
      @foreach ($recentPr as $pr)
      <div class="act-row">
        <div>
          <a href="{{ route('purchasing.purchase_requests.show', $pr->id) }}" class="act-code mono">{{ $pr->code }}</a>
          <div class="act-meta">{{ $pr->requestedBy?->name ?? '—' }}</div>
        </div>
        <div class="text-end">
          @php $cls = match($pr->status) { 'approved'=>'badge-approved','converted'=>'badge-blue','rejected'=>'badge-danger',default=>'badge-draft' }; @endphp
          <span class="badge-status {{ $cls }}">{{ function_exists('pr_status_label') ? pr_status_label($pr->status) : $pr->status }}</span>
          <div class="act-meta">{{ $pr->created_at?->diffForHumans() }}</div>
        </div>
      </div>
      @endforeach
    </div>
    @endif

    @if ($recentPo->isNotEmpty())
    <div class="card-section">
      <div class="card-section-header"><span>Purchase Order</span></div>
      @foreach ($recentPo as $po)
      <div class="act-row">
        <div>
          <a href="{{ route('purchasing.purchase_orders.show', $po->id) }}" class="act-code mono">{{ $po->code }}</a>
          <div class="act-meta">{{ $po->supplier?->name ?? '—' }}</div>
        </div>
        <div class="text-end">
          @php $cls = match($po->status) { 'approved'=>'badge-approved','cancelled'=>'badge-danger',default=>'badge-draft' }; @endphp
          <span class="badge-status {{ $cls }}">{{ ucfirst($po->status) }}</span>
          <div class="act-meta">{{ $po->created_at?->diffForHumans() }}</div>
        </div>
      </div>
      @endforeach
    </div>
    @endif

    @if ($recentGrn->isNotEmpty())
    <div class="card-section">
      <div class="card-section-header"><span>Penerimaan Barang</span></div>
      @foreach ($recentGrn as $grn)
      <div class="act-row">
        <div>
          <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}" class="act-code mono">{{ $grn->code ?? 'GRN-'.$grn->id }}</a>
          <div class="act-meta">{{ $grn->supplier?->name ?? '—' }}</div>
        </div>
        <div class="text-end">
          <div class="act-meta mono">{{ $grn->date?->format('d/m/Y') ?? '—' }}</div>
          <div class="act-meta">{{ $grn->created_at?->diffForHumans() }}</div>
        </div>
      </div>
      @endforeach
    </div>
    @endif

    @if ($hasPaymentTable && $recentPayments->isNotEmpty())
    <div class="card-section">
      <div class="card-section-header"><span>Pembayaran</span></div>
      @foreach ($recentPayments as $pay)
      <div class="act-row">
        <div>
          <a href="{{ route('purchasing.purchase_orders.show', $pay->purchase_order_id) }}" class="act-code mono">
            {{ $pay->purchaseOrder?->code ?? '—' }}
          </a>
          <div class="act-meta">{{ $pay->purchaseOrder?->supplier?->name ?? '—' }}</div>
        </div>
        <div class="text-end">
          @if ($canSeeMoney)
          <div class="mono fw-semibold" style="font-size:.8rem;">{{ rupiah($pay->amount) }}</div>
          @endif
          <div class="act-meta">{{ $pay->created_at?->diffForHumans() }}</div>
        </div>
      </div>
      @endforeach
    </div>
    @endif

  </div>
  @endif

</div>
@endsection
