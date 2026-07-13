{{-- resources/views/purchasing/purchase_returns/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Return Pembelian ' . ($ret->code ?? ''))

@push('head')
<style>
  /* ===== Purchase Return — selaras dgn Shipment (neutral/compact) ===== */
  .pr-wrap { max-width:1000px; margin-inline:auto; padding:.7rem .75rem 3rem; color:#111827; }
  .pr-mono { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-variant-numeric:tabular-nums; }

  /* Topbar sticky */
  .pr-topbar {
    position:sticky; top:0; z-index:250;
    display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;
    padding:.55rem .75rem; margin-bottom:.75rem;
    background:var(--card,#fff); border-bottom:1px solid rgba(148,163,184,.18);
  }
  .pr-code { font-weight:900; font-size:1rem; color:#111827; }
  .pr-sub  { color:#64748b; font-size:.74rem; font-weight:650; }
  .pr-spacer { flex:1; }

  .pr-btn {
    display:inline-flex; align-items:center; justify-content:center; gap:.35rem;
    border-radius:7px; border:1px solid rgba(148,163,184,.3); background:transparent;
    color:#475569; text-decoration:none; font-size:.78rem; font-weight:800;
    padding:.32rem .65rem; min-height:34px; cursor:pointer;
  }
  .pr-btn:hover { background:rgba(148,163,184,.09); color:#111827; text-decoration:none; }
  .pr-btn-primary  { background:#334155!important; border-color:#334155!important; color:#fff!important; }
  .pr-btn-success  { background:#16a34a!important; border-color:#16a34a!important; color:#fff!important; }
  .pr-btn-danger   { border-color:rgba(220,38,38,.4)!important; color:#b91c1c!important; }
  .pr-btn-danger:hover { background:rgba(220,38,38,.08)!important; }
  .pr-btn:disabled { opacity:.45; cursor:not-allowed; }

  /* Status pill */
  .pr-pill {
    display:inline-flex; align-items:center; gap:.35rem; border-radius:999px;
    padding:.18rem .55rem; border:1px solid rgba(148,163,184,.28);
    color:#475569; background:rgba(148,163,184,.10);
    font-size:.74rem; font-weight:850; white-space:nowrap;
  }
  .pr-pill::before { content:""; width:7px; height:7px; border-radius:999px; background:#64748b; }
  .pr-pill.is-plain::before { display:none; }
  .pr-pill.is-draft { color:#92400e; background:rgba(245,158,11,.10); border-color:rgba(245,158,11,.3); }
  .pr-pill.is-draft::before { background:#f59e0b; }
  .pr-pill.is-info  { color:#1d4ed8; background:rgba(59,130,246,.10); border-color:rgba(59,130,246,.28); }
  .pr-pill.is-info::before { background:#3b82f6; }
  .pr-pill.is-posted{ color:#166534; background:rgba(34,197,94,.10); border-color:rgba(34,197,94,.28); }
  .pr-pill.is-posted::before { background:#22c55e; }
  .pr-pill.is-void  { color:#991b1b; background:rgba(153,27,27,.08); border-color:rgba(153,27,27,.25); }
  .pr-pill.is-void::before { background:#dc2626; }

  /* KPI grid */
  .pr-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.55rem; margin-bottom:.75rem; }
  .pr-kpi  { background:var(--card,#fff); border:1px solid rgba(148,163,184,.18); border-radius:8px; padding:.6rem .7rem; min-width:0; }
  .pr-kpi-label { font-size:.68rem; font-weight:850; color:#64748b; text-transform:uppercase; letter-spacing:.02em; }
  .pr-kpi-value { font-size:1.12rem; font-weight:900; color:#111827; margin-top:.12rem; font-variant-numeric:tabular-nums; }
  .pr-kpi-value.sm { font-size:.92rem; }

  /* Panel */
  .pr-panel { background:var(--card,#fff); border:1px solid rgba(148,163,184,.18); border-radius:8px; overflow:hidden; margin-bottom:.75rem; }
  .pr-panel-head {
    display:flex; align-items:center; justify-content:space-between; gap:.55rem;
    padding:.62rem .8rem; border-bottom:1px solid rgba(148,163,184,.12);
  }
  .pr-panel-title { font-weight:900; color:#334155; font-size:.86rem; }
  .pr-panel-hint  { color:#64748b; font-size:.74rem; font-weight:650; }
  .pr-panel-body  { padding:.75rem .8rem; }

  /* Meta boxes */
  .pr-meta { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.5rem; }
  .pr-meta-box { border:1px solid rgba(148,163,184,.16); border-radius:8px; padding:.5rem .6rem; background:#f8fafc; min-width:0; }
  .pr-meta-label { font-size:.66rem; font-weight:850; color:#64748b; text-transform:uppercase; letter-spacing:.02em; }
  .pr-meta-value { margin-top:.1rem; color:#111827; font-size:.84rem; font-weight:800; overflow-wrap:anywhere; }
  .pr-meta-value .sub { color:#64748b; font-size:.72rem; font-weight:650; }

  /* Effect card */
  .pr-effect-head { display:flex; align-items:flex-start; justify-content:space-between; gap:.6rem; flex-wrap:wrap; }
  .pr-effect-note { color:#64748b; font-size:.78rem; font-weight:600; margin-top:.15rem; max-width:60ch; }
  .pr-effect-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.5rem; margin-top:.7rem; }
  .pr-effect-item { border:1px solid rgba(148,163,184,.16); border-radius:8px; padding:.5rem .6rem; background:#f8fafc; }
  .pr-effect-label { font-size:.66rem; color:#64748b; font-weight:850; text-transform:uppercase; }
  .pr-effect-value { font-size:.9rem; font-weight:900; color:#111827; margin-top:.1rem; }

  /* Tools bar */
  .pr-tools { display:flex; gap:.4rem; flex-wrap:wrap; align-items:center; margin-bottom:.65rem; }
  .pr-tools-summary { margin-left:auto; color:#64748b; font-size:.76rem; font-weight:650; }
  .pr-tools-summary strong { color:#111827; }
  .return-total-live { color:#334155; font-weight:900; }

  /* Formbar */
  .pr-formbar { display:grid; grid-template-columns:150px minmax(180px,1fr) 1fr auto; gap:.5rem; align-items:end; margin-bottom:.7rem; }
  .pr-formbar .form-label { font-size:.66rem; color:#64748b; font-weight:850; text-transform:uppercase; letter-spacing:.02em; margin-bottom:.15rem; }
  .pr-formbar-total { display:flex; align-items:flex-end; justify-content:flex-end; color:#111827; font-size:.85rem; font-weight:900; padding-bottom:.3rem; }

  /* Table */
  .pr-table-wrap { overflow-x:auto; border:1px solid rgba(148,163,184,.16); border-radius:8px; }
  .pr-table { width:100%; border-collapse:collapse; --bs-table-bg:transparent; }
  .pr-table thead th {
    text-align:left; font-size:.68rem; color:#64748b; font-weight:900; text-transform:uppercase; letter-spacing:.02em;
    background:rgba(148,163,184,.05); padding:.5rem .65rem; border-bottom:1px solid rgba(148,163,184,.14); white-space:nowrap;
  }
  .pr-table tbody td { padding:.55rem .65rem; border-bottom:1px solid rgba(148,163,184,.1); vertical-align:middle; font-size:.84rem; color:#334155; }
  .pr-table tbody tr:last-child td { border-bottom:0; }

  .item-main { font-weight:850; color:#111827; line-height:1.15; }
  .item-sub  { font-size:.73rem; color:#64748b; margin-top:.08rem; }

  /* State pills in rows */
  .primary-label, .state-pill {
    display:inline-flex; align-items:center; border-radius:999px;
    padding:.1rem .5rem; font-size:.66rem; font-weight:850; border:1px solid transparent; white-space:nowrap;
  }
  .primary-label { color:#166534; background:rgba(34,197,94,.1); border-color:rgba(34,197,94,.28); }
  .state-pill.is-muted { color:#64748b; background:rgba(148,163,184,.1); border-color:rgba(148,163,184,.28); }

  /* Metric pills */
  .return-metrics { display:flex; gap:.3rem; flex-wrap:wrap; justify-content:flex-end; }
  .metric-pill { display:inline-flex; align-items:center; gap:.25rem; border:1px solid rgba(148,163,184,.25); border-radius:999px; padding:.1rem .45rem; font-size:.68rem; font-weight:700; color:#64748b; background:#f8fafc; }
  .metric-pill strong { color:#111827; }
  .metric-pill.is-blue  { color:#1d4ed8; border-color:rgba(59,130,246,.28); background:rgba(59,130,246,.07); }
  .metric-pill.is-red   { color:#b91c1c; border-color:rgba(220,38,38,.28); background:rgba(220,38,38,.07); }
  .metric-pill.is-green { color:#166534; border-color:rgba(34,197,94,.28); background:rgba(34,197,94,.07); }

  /* Qty input row */
  .qty-return-input { min-height:36px; border-radius:7px; font-weight:800; font-size:.9rem; }
  .return-input-wrap { display:flex; flex-direction:column; align-items:flex-end; gap:.3rem; }
  .row-main-action { display:flex; gap:.3rem; justify-content:flex-end; flex-wrap:wrap; }
  .quick-btn { border-radius:999px; padding:.12rem .5rem; font-size:.68rem; }

  .return-row.has-qty td { background:rgba(34,197,94,.04); }
  .return-row.has-qty td:first-child { box-shadow:inset 3px 0 0 rgba(34,197,94,.5); }
  .return-row.is-empty { opacity:.85; }
  .return-mobile-head { display:none; }

  .line-stock-short { color:#b91c1c; font-weight:900; }
  .line-stock-ok    { color:#166534; font-weight:900; }

  /* Reason + foto */
  .return-line-extra { display:flex; flex-direction:column; gap:.4rem; }
  .return-photo-box { display:flex; flex-wrap:wrap; align-items:center; gap:.4rem; }
  .return-photo-thumbs { display:flex; flex-wrap:wrap; gap:.4rem; }
  .return-photo-thumb { position:relative; display:inline-flex; flex-direction:column; align-items:center; border:1px solid rgba(148,163,184,.28); border-radius:8px; padding:2px; background:#fff; }
  .return-photo-thumb img { width:50px; height:50px; object-fit:cover; border-radius:6px; display:block; }
  .return-photo-del { font-size:.6rem; color:#b91c1c; display:flex; align-items:center; gap:.15rem; margin-top:2px; cursor:pointer; }
  .return-photo-add { display:inline-flex; align-items:center; font-size:.72rem; color:#1d4ed8; border:1px dashed rgba(59,130,246,.5); border-radius:8px; padding:.3rem .6rem; cursor:pointer; background:rgba(59,130,246,.05); }

  /* Actions bar */
  .pr-actions { display:flex; justify-content:space-between; align-items:center; gap:.5rem; flex-wrap:wrap; }
  .pr-actions-group { display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; }
  .pr-inline-form { margin:0; }

  /* Alerts */
  .pr-alert { border-radius:8px; font-size:.82rem; padding:.6rem .8rem; margin-bottom:.75rem; border:1px solid transparent; }
  .pr-alert-success { background:rgba(34,197,94,.08); border-color:rgba(34,197,94,.25); color:#166534; }
  .pr-alert-danger  { background:rgba(220,38,38,.07); border-color:rgba(220,38,38,.25); color:#b91c1c; }
  .pr-alert-info    { background:rgba(59,130,246,.07); border-color:rgba(59,130,246,.22); color:#1d4ed8; }
  .pr-alert-warn    { background:rgba(245,158,11,.09); border-color:rgba(245,158,11,.28); color:#92400e; }

  @media (max-width:820px){
    .pr-wrap { padding:.5rem .5rem 3.5rem; }
    .pr-topbar { padding:.5rem; }
    .pr-code { flex:1; min-width:140px; }
    .pr-sub, .pr-topbar .pr-btn.hide-mobile { display:none; }
    .pr-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:.45rem; }
    .pr-kpi-value { font-size:1.02rem; }
    .pr-meta { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .pr-effect-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .pr-formbar { grid-template-columns:1fr 1fr; }
    .pr-formbar-total { grid-column:1 / -1; justify-content:flex-start; padding-bottom:0; }

    .pr-table-wrap { border:0; border-radius:0; overflow:visible; }
    .pr-table thead { display:none; }
    .pr-table tbody tr { display:block; border:1px solid rgba(148,163,184,.18); border-radius:8px; margin-bottom:.55rem; padding:.5rem .6rem; background:var(--card,#fff); }
    .pr-table tbody td { display:flex; justify-content:space-between; align-items:flex-start; gap:.75rem; border:0; padding:.2rem 0; }
    .pr-table tbody td[data-label]::before { content:attr(data-label); font-size:.7rem; color:#64748b; font-weight:800; text-transform:uppercase; flex:0 0 auto; }
    .pr-table .td-item { display:block; }
    .pr-table .td-item::before { display:none; }
    .return-metrics { justify-content:flex-start; }
    .return-input-wrap { width:100%; align-items:stretch; }
    .qty-return-input { width:100%; }
    .return-mobile-head { display:block; margin-bottom:.3rem; }
    .row-main-action { justify-content:stretch; }
    .row-main-action .btn { flex:1; }
    .pr-tools .pr-btn { flex:1 1 auto; }
    .pr-tools-summary { width:100%; margin-left:0; text-align:center; }
  }
</style>
@endpush

@section('content')
@php
  $canSeeMoney = auth()->user()?->isOwner() ?? false;
  $isDraft = ($ret->status ?? '') === 'draft';
  $isSubmitted = ($ret->status ?? '') === 'submitted';
  $isPosted = ($ret->status ?? '') === 'posted';
  $isVoided = (bool) ($ret->voided_at);
  $isEditable = ($isDraft || $isSubmitted) && ! $isVoided;
  $reasons = \App\Models\PurchaseReturnLine::REASONS;

  $grand = (float)($ret->total ?? 0);
  $totalLines = (int) (($returnRows ?? collect())->count());
  $totalReturnLines = (int) (($returnRows ?? collect())->filter(fn($row) => (float) $row->qty > 0.0001)->count());
  $totalQty = (float) (($returnRows ?? collect())->sum('qty'));
  $grnHref = route('purchasing.purchase_receipts.show', $ret->purchase_receipt_id ?? $ret->grn_id ?? ($ret->grn?->id ?? 0));
  $dateValue = old('date', $ret->date ? \Illuminate\Support\Carbon::parse($ret->date)->format('Y-m-d') : now()->toDateString());
  $effect = $journalEffect ?? [];
  $effectTotal = (float) ($effect['total'] ?? 0);
  $effectInv = (float) ($effect['inventory_total'] ?? 0);
  $effectExpense = (float) ($effect['expense_total'] ?? 0);
  $effectAp = (float) ($effect['ap_portion'] ?? 0);
  $effectClaim = (float) ($effect['claim_portion'] ?? 0);
  $totalReceived = (float) (($returnRows ?? collect())->sum('replacement_qty_received'));
  $hasReceivedReplacement = $ret->resolution_type === 'replacement' && (in_array($ret->replacement_status, ['partial', 'received']) || $totalReceived > 0);

  // Status pill
  if ($isVoided) { $statusPill = 'is-void'; $statusText = 'Void'; }
  elseif ($isPosted) {
    if ($ret->resolution_type === 'replacement' && $ret->replacement_status === 'pending') { $statusPill = 'is-draft'; $statusText = 'Menunggu Pengganti'; }
    elseif ($ret->resolution_type === 'replacement' && $ret->replacement_status === 'partial') { $statusPill = 'is-info'; $statusText = 'Diterima Sebagian'; }
    elseif ($ret->resolution_type === 'replacement' && $ret->replacement_status === 'received') { $statusPill = 'is-posted'; $statusText = 'Pengganti Diterima'; }
    else { $statusPill = 'is-posted'; $statusText = 'Posted'; }
  }
  elseif ($isSubmitted) { $statusPill = 'is-info'; $statusText = 'Diajukan'; }
  else { $statusPill = 'is-draft'; $statusText = 'Draft'; }
@endphp

{{-- ===== TOPBAR ===== --}}
<div class="pr-topbar">
  <a href="{{ route('purchasing.purchase_returns.index') }}" class="pr-btn">
    <i class="bi bi-arrow-left"></i> Kembali
  </a>
  <div>
    <span class="pr-code">{{ $ret->code }}</span>
    <div class="pr-sub">Return Pembelian</div>
  </div>
  <span class="pr-pill {{ $statusPill }}">{{ $statusText }}</span>
  <span class="pr-spacer"></span>
  <span class="pr-pill is-plain">Item <strong class="ms-1">{{ $totalReturnLines }}/{{ $totalLines }}</strong></span>
  <span class="pr-pill is-plain">Qty <strong class="ms-1">{{ decimal_id($totalQty, 2) }}</strong></span>
  <a href="{{ $grnHref }}" class="pr-btn hide-mobile"><i class="bi bi-box-arrow-up-right"></i> GRN</a>
</div>

<div class="pr-wrap">

  {{-- ALERTS --}}
  @if(session('success'))
    <div class="pr-alert pr-alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="pr-alert pr-alert-danger">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="pr-alert pr-alert-danger">
      <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
      @foreach($errors->all() as $e)
        <div>{{ $e }}</div>
      @endforeach
    </div>
  @endif

  {{-- ===== KPI GRID ===== --}}
  <div class="pr-grid">
    <div class="pr-kpi">
      <div class="pr-kpi-label">Item Return</div>
      <div class="pr-kpi-value pr-mono">{{ $totalReturnLines }}<span style="color:#94a3b8;font-weight:700">/{{ $totalLines }}</span></div>
    </div>
    <div class="pr-kpi">
      <div class="pr-kpi-label">Qty Return</div>
      <div class="pr-kpi-value pr-mono">{{ decimal_id($totalQty, 2) }}</div>
    </div>
    @if($canSeeMoney)
      <div class="pr-kpi">
        <div class="pr-kpi-label">Total Return</div>
        <div class="pr-kpi-value pr-mono sm">{{ rupiah($grand) }}</div>
      </div>
    @else
      <div class="pr-kpi">
        <div class="pr-kpi-label">Tipe</div>
        <div class="pr-kpi-value sm">{{ $ret->resolution_type === 'replacement' ? 'Tukar Barang' : 'Refund' }}</div>
      </div>
    @endif
    <div class="pr-kpi">
      <div class="pr-kpi-label">{{ $isDraft ? 'Stok' : 'Jurnal' }}</div>
      <div class="pr-kpi-value sm">
        @if($isDraft)
          <span class="{{ $stockReady ? 'line-stock-ok' : 'line-stock-short' }}">{{ $stockReady ? 'Siap' : 'Kurang' }}</span>
        @else
          {{ $journalCount > 0 ? $journalCount . ' jurnal' : 'Belum' }}
        @endif
      </div>
    </div>
  </div>

  {{-- ===== INFO DOKUMEN ===== --}}
  <div class="pr-panel">
    <div class="pr-panel-head">
      <div class="pr-panel-title">Informasi Dokumen</div>
    </div>
    <div class="pr-panel-body">
      <div class="pr-meta">
        <div class="pr-meta-box">
          <div class="pr-meta-label">Tanggal</div>
          <div class="pr-meta-value pr-mono">{{ $ret->date ? \Illuminate\Support\Carbon::parse($ret->date)->format('d M Y') : '-' }}</div>
        </div>
        <div class="pr-meta-box">
          <div class="pr-meta-label">Dari GRN</div>
          <div class="pr-meta-value">
            <a href="{{ $grnHref }}" class="text-decoration-none pr-mono">{{ $ret->grn?->code ?? '-' }}</a>
          </div>
        </div>
        <div class="pr-meta-box">
          <div class="pr-meta-label">Supplier</div>
          <div class="pr-meta-value">{{ $ret->grn?->supplier?->name ?? '-' }}
            @if($ret->grn?->supplier?->code)<div class="sub pr-mono">{{ $ret->grn->supplier->code }}</div>@endif
          </div>
        </div>
        <div class="pr-meta-box">
          <div class="pr-meta-label">Gudang</div>
          <div class="pr-meta-value">{{ $ret->grn?->warehouse?->name ?? '-' }}
            @if($ret->grn?->warehouse?->code)<div class="sub pr-mono">{{ $ret->grn->warehouse->code }}</div>@endif
          </div>
        </div>
        @if($ret->notes)
          <div class="pr-meta-box" style="grid-column:1 / -1;">
            <div class="pr-meta-label">Catatan</div>
            <div class="pr-meta-value" style="font-weight:650;">{{ $ret->notes }}</div>
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- ===== EFEK JURNAL ===== --}}
  <div class="pr-panel">
    <div class="pr-panel-body">
      <div class="pr-effect-head">
        <div>
          <div class="pr-panel-title">
            @if($isVoided) Efek Void
            @elseif($isPosted) Efek Sudah Posted
            @else Efek Saat Diposting @endif
          </div>
          <div class="pr-effect-note">
            @if($isVoided)
              Stok sudah dikembalikan ke gudang asal dan jurnal return sudah dibatalkan.
            @elseif($isPosted)
              Stok sudah keluar dari gudang asal dan jurnal return sudah tercatat.
            @else
              Draft belum mengubah stok atau jurnal. Efek di bawah baru terjadi saat tombol Post ditekan.
            @endif
          </div>
        </div>
        @if($isPosted && !$isVoided)
          <span class="pr-pill is-posted">{{ $mutationCount }} mutasi · {{ $journalCount }} jurnal</span>
        @elseif($isVoided)
          <span class="pr-pill is-void">Void</span>
        @else
          <span class="pr-pill is-draft">Belum berdampak</span>
        @endif
      </div>

      @if($canSeeMoney)
        <div class="pr-effect-grid">
          <div class="pr-effect-item">
            <div class="pr-effect-label">Total Return</div>
            <div class="pr-effect-value pr-mono">{{ rupiah($effectTotal) }}</div>
          </div>
          <div class="pr-effect-item">
            <div class="pr-effect-label">Kurangi Stok</div>
            <div class="pr-effect-value pr-mono">{{ rupiah($effectInv) }}</div>
          </div>
          <div class="pr-effect-item">
            <div class="pr-effect-label">Potong Hutang</div>
            <div class="pr-effect-value pr-mono">{{ $isDraft && !$isVoided ? rupiah($effectAp) : '-' }}</div>
          </div>
          <div class="pr-effect-item">
            <div class="pr-effect-label">Klaim Supplier</div>
            <div class="pr-effect-value pr-mono">{{ $isDraft && !$isVoided ? rupiah($effectClaim) : '-' }}</div>
          </div>
        </div>
        @if($isDraft && !$isVoided && $effectClaim > 0.0001)
          <div class="pr-alert pr-alert-warn mt-3 mb-0">
            Sebagian nilai return akan masuk ke <strong>Klaim Supplier</strong> karena sisa hutang tidak cukup.
          </div>
        @endif
      @endif
    </div>
  </div>

  {{-- ===== RIWAYAT PENERIMAAN PENGGANTI ===== --}}
  @if($ret->resolution_type === 'replacement' && $ret->replacementReceipts->isNotEmpty())
  <div class="pr-panel">
    <div class="pr-panel-head">
      <div class="pr-panel-title">Riwayat Penerimaan Barang Pengganti</div>
      <span class="pr-pill is-info">{{ $ret->replacementReceipts->count() }} Dokumen</span>
    </div>
    <div class="pr-panel-body" style="padding-top:.5rem;padding-bottom:.5rem;">
      <div class="pr-table-wrap">
        <table class="pr-table">
          <thead>
            <tr>
              <th>No. Penerimaan</th>
              <th>Tanggal</th>
              <th>Gudang</th>
              <th>Status</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($ret->replacementReceipts as $rr)
            <tr>
              <td data-label="No" class="pr-mono" style="font-weight:900;color:#111827;">{{ $rr->code }}</td>
              <td data-label="Tanggal" class="pr-mono">{{ \Illuminate\Support\Carbon::parse($rr->date)->format('d M Y') }}</td>
              <td data-label="Gudang">{{ $rr->warehouse?->name ?? '-' }}</td>
              <td data-label="Status">
                @if($rr->status === 'posted')<span class="pr-pill is-posted">Posted</span>
                @elseif($rr->status === 'draft')<span class="pr-pill is-draft">Draft</span>
                @else<span class="pr-pill">{{ ucfirst($rr->status) }}</span>@endif
              </td>
              <td data-label="Aksi" class="text-end">
                <a href="{{ route('purchasing.purchase_receipts.show', $rr->id) }}" class="pr-btn">Lihat GRN</a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

  {{-- ===== DETAIL RETURN ===== --}}
  <div class="pr-panel">
    <div class="pr-panel-head">
      <div class="pr-panel-title">Detail Return</div>
      <span class="pr-panel-hint">{{ $totalReturnLines }} / {{ $totalLines }} item diretur</span>
    </div>

    <div class="pr-panel-body">
      <form method="POST" action="{{ route('purchasing.purchase_returns.update', $ret->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @if($isEditable)
          <div class="pr-formbar">
            <div>
              <label class="form-label">Tanggal</label>
              <input type="text" name="date" class="form-control form-control-sm gf-date-input pr-mono"
                value="{{ $dateValue }}" data-gf-date autocomplete="off" required>
            </div>
            <div>
              <label class="form-label">Tipe Penyelesaian</label>
              <select name="resolution_type" class="form-select form-select-sm">
                <option value="refund" {{ old('resolution_type', $ret->resolution_type) === 'refund' ? 'selected' : '' }}>Refund (Potong Tagihan)</option>
                <option value="replacement" {{ old('resolution_type', $ret->resolution_type) === 'replacement' ? 'selected' : '' }}>Tukar Barang (Replacement)</option>
              </select>
            </div>
            <div>
              <label class="form-label">Catatan</label>
              <input type="text" name="notes" class="form-control form-control-sm" value="{{ old('notes', $ret->notes) }}" placeholder="Opsional">
            </div>
            @if($canSeeMoney)
              <div class="pr-formbar-total pr-mono">Rp {{ number_format($grand, 0, ',', '.') }}</div>
            @endif
          </div>
        @else
          <input type="hidden" name="date" value="{{ $dateValue }}">
          <input type="hidden" name="notes" value="{{ $ret->notes }}">
          <input type="hidden" name="resolution_type" value="{{ $ret->resolution_type }}">
        @endif

        @if($isEditable)
          <div class="pr-alert pr-alert-info">
            <i class="bi bi-info-circle me-1"></i> Stok baru akan dialokasikan setelah draft disimpan.
          </div>
          <div class="pr-tools">
            <button type="button" class="pr-btn" id="btn-zero-all">Reset</button>
            <button type="button" class="pr-btn" id="btn-max-all">Maks Semua</button>
            <button type="button" class="pr-btn" id="btn-focus-first">Cari Item</button>
            <span class="pr-tools-summary">
              <span class="return-total-live" id="live-return-lines">{{ $totalReturnLines }}</span> item ·
              <span class="return-total-live" id="live-return-qty">{{ decimal_id($totalQty, 2) }}</span> qty
            </span>
          </div>
        @endif

        <div class="pr-table-wrap">
          <table class="pr-table return-table">
            <thead>
              <tr>
                <th>Item</th>
                <th class="text-end">Batas</th>
                <th class="text-end" style="width:190px;">Return</th>
                @if($canSeeMoney)<th class="text-end">Nilai</th>@endif
              </tr>
            </thead>
            <tbody>
              @foreach($returnRows as $i => $row)
                @php
                  $ln = $row->line;
                  $received = (float)($row->received ?? 0);
                  $rem = (float)($row->remaining ?? 0);
                  $stock = (float)($row->stock ?? 0);
                  $lotStock = $row->lot_stock;
                  $shownStock = $lotStock !== null ? (float) $lotStock : $stock;
                  $maxReturn = (float)($row->max_return ?? $rem);
                  $isInventoryLine = (bool)($row->is_inventory ?? true);
                  $qty = (float)($row->qty ?? 0);
                  $unitPrice = (float)($row->unit_price ?? 0);
                  $lineTotal = (float)($row->line_total ?? 0);
                  $rowClass = $qty > 0.0001 ? 'has-qty' : 'is-empty';
                @endphp
                <tr class="return-row {{ $rowClass }}">
                  <td class="td-item" data-label="Item">
                    <div class="return-mobile-head">
                      @if($qty > 0.0001)
                        <span class="primary-label">Diretur</span>
                      @else
                        <span class="state-pill is-muted">Tidak diretur</span>
                      @endif
                    </div>
                    <div class="item-main">{{ $row->item?->name ?? '-' }}</div>
                    <div class="item-sub pr-mono">
                      {{ $row->item?->code ?? '-' }}
                      @if($row->lot_id) • LOT #{{ $row->lot_id }} @endif
                      <span class="d-none d-md-inline"> • Terima {{ rtrim(rtrim(number_format($received, 4, ',', '.'), '0'), ',') }}</span>
                    </div>

                    @if($isEditable)
                      <input type="hidden" name="lines[{{ $i }}][id]" value="{{ $ln?->id }}">
                      <input type="hidden" name="lines[{{ $i }}][purchase_receipt_line_id]" value="{{ $row->purchase_receipt_line_id }}">

                      <div class="return-line-extra mt-2">
                        <select name="lines[{{ $i }}][reason_code]" class="form-select form-select-sm">
                          <option value="">- Alasan retur -</option>
                          @foreach($reasons as $code => $label)
                            <option value="{{ $code }}" @selected(old("lines.$i.reason_code", $ln?->reason_code) === $code)>{{ $label }}</option>
                          @endforeach
                        </select>

                        <input type="text" name="lines[{{ $i }}][notes]" class="form-control form-control-sm"
                          placeholder="Catatan item (opsional)" value="{{ old("lines.$i.notes", $row->notes) }}">

                        <div class="return-photo-box">
                          @if($ln && $ln->photos && $ln->photos->count())
                            <div class="return-photo-thumbs">
                              @foreach($ln->photos as $photo)
                                <label class="return-photo-thumb" title="{{ $photo->original_name }}">
                                  <img src="{{ $photo->url }}" alt="foto">
                                  <span class="return-photo-del">
                                    <input type="checkbox" name="delete_photos[]" value="{{ $photo->id }}"> hapus
                                  </span>
                                </label>
                              @endforeach
                            </div>
                          @endif
                          <label class="return-photo-add">
                            <i class="bi bi-camera me-1"></i> Tambah foto
                            <input type="file" name="lines[{{ $i }}][photos][]" accept="image/*" multiple hidden>
                          </label>
                        </div>
                      </div>
                    @elseif($row->reason_label ?? ($ln?->reason_label))
                      <div class="item-sub mt-1">Alasan: <strong>{{ $ln?->reason_label }}</strong></div>
                    @endif

                    @if(!$isEditable && $ln && $ln->photos && $ln->photos->count())
                      <div class="return-photo-thumbs mt-1">
                        @foreach($ln->photos as $photo)
                          <a href="{{ $photo->url }}" target="_blank" class="return-photo-thumb" title="{{ $photo->original_name }}">
                            <img src="{{ $photo->url }}" alt="foto">
                          </a>
                        @endforeach
                      </div>
                    @endif
                  </td>

                  <td class="text-end" data-label="Batas">
                    <div class="return-metrics">
                      <span class="metric-pill is-blue">Maks <strong class="pr-mono">{{ rtrim(rtrim(number_format($maxReturn, 4, ',', '.'), '0'), ',') }}</strong></span>
                      @if($isInventoryLine)
                        <span class="metric-pill {{ $shownStock + .0001 >= $qty ? 'is-green' : 'is-red' }}">
                          {{ $lotStock !== null ? 'Stok Lot' : 'Stok' }}
                          <strong class="pr-mono">{{ rtrim(rtrim(number_format($shownStock, 4, ',', '.'), '0'), ',') }}</strong>
                        </span>
                      @else
                        <span class="metric-pill">Non-stok</span>
                      @endif
                      <span class="metric-pill d-md-none">Terima <strong class="pr-mono">{{ rtrim(rtrim(number_format($received, 4, ',', '.'), '0'), ',') }}</strong></span>
                    </div>
                  </td>

                  <td class="text-end" data-label="Return">
                    @if($isEditable)
                      <div class="return-input-wrap">
                        <input type="number" name="lines[{{ $i }}][qty]"
                          class="form-control form-control-sm text-end pr-mono qty-return-input"
                          value="{{ old("lines.$i.qty", $qty > 0.0001 ? $qty : '') }}"
                          step="0.0001" min="0" max="{{ $maxReturn }}"
                          inputmode="decimal" placeholder="0"
                          data-max="{{ $maxReturn }}" data-row-code="{{ $row->item?->code ?? '-' }}">
                        <div class="row-main-action">
                          <button type="button" class="pr-btn quick-btn js-zero-row">0</button>
                          <button type="button" class="pr-btn quick-btn js-max-row">Maks</button>
                        </div>
                      </div>
                    @else
                      <span class="pr-mono" style="font-weight:800;">{{ rtrim(rtrim(number_format($qty, 4, ',', '.'), '0'), ',') }}</span>
                    @endif
                  </td>

                  @if($canSeeMoney)
                    <td class="text-end pr-mono" data-label="Nilai">
                      <div style="font-weight:800;color:#111827;">Rp {{ number_format($lineTotal, 0, ',', '.') }}</div>
                      <div class="item-sub">Harga {{ number_format($unitPrice, 0, ',', '.') }}</div>
                    </td>
                  @endif
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        @if($isEditable)
          <div class="d-flex justify-content-end mt-3">
            <button class="pr-btn pr-btn-primary" type="submit"><i class="bi bi-save2"></i> Simpan Item Return</button>
          </div>
        @endif
      </form>
    </div>
  </div>

  {{-- ===== ACTIONS ===== --}}
  <div class="pr-panel">
    <div class="pr-panel-body">
      <div class="pr-actions">
        <div class="pr-actions-group">
          @if($isSubmitted)
            <span class="pr-pill is-info">Menunggu persetujuan</span>
          @endif
          @if($isEditable && !$stockReady)
            <span class="pr-pill is-void is-plain" style="color:#b91c1c;">Stok kurang</span>
          @elseif($isEditable)
            <span class="pr-pill is-posted">Siap diposting</span>
          @endif
        </div>

        <div class="pr-actions-group">
          @if($isDraft && !$isVoided)
            <form method="POST" action="{{ route('purchasing.purchase_returns.submit', $ret->id) }}" class="js-submit-return pr-inline-form">
              @csrf
              <button class="pr-btn" type="submit"><i class="bi bi-send"></i> Ajukan</button>
            </form>
          @endif

          @if($isEditable)
            <form method="POST" action="{{ route('purchasing.purchase_returns.post', $ret->id) }}" class="js-post-return pr-inline-form">
              @csrf
              <button class="pr-btn {{ $stockReady ? 'pr-btn-success' : 'pr-btn-danger' }}" type="submit" {{ $stockReady ? '' : 'disabled' }}>
                <i class="bi bi-check2-circle"></i> {{ $stockReady ? 'POST Return' : 'Kurangi Qty Dulu' }}
              </button>
            </form>
          @endif

          @if($isPosted && !$isVoided)
            @if($ret->resolution_type === 'replacement' && in_array($ret->replacement_status, ['pending', 'partial']))
              <button type="button" class="pr-btn pr-btn-primary" data-bs-toggle="modal" data-bs-target="#receiveReplacementModal">
                <i class="bi bi-box-seam"></i> Terima Barang Pengganti
              </button>
            @endif
            @if(!$hasReceivedReplacement)
              <form method="POST" action="{{ route('purchasing.purchase_returns.void', $ret->id) }}" class="js-void-return pr-inline-form">
                @csrf
                <button class="pr-btn pr-btn-danger" type="submit"><i class="bi bi-x-circle"></i> VOID</button>
              </form>
            @endif
          @endif
        </div>
      </div>

      @if($hasReceivedReplacement)
        <div class="pr-alert pr-alert-warn mt-3 mb-0">
          <i class="bi bi-exclamation-triangle me-1"></i> Retur ini sudah memiliki penerimaan barang pengganti dan tidak dapat di-void melalui pembatalan biasa.
        </div>
      @endif
    </div>
  </div>

</div>{{-- /pr-wrap --}}

{{-- Modal Terima Barang Pengganti --}}
@if($isPosted && !$isVoided && $ret->resolution_type === 'replacement' && in_array($ret->replacement_status, ['pending', 'partial']))
<div class="modal fade" id="receiveReplacementModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('purchasing.purchase_returns.receive_replacement', $ret->id) }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Terima Barang Pengganti</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Tanggal Terima</label>
              <input type="text" name="received_at" class="form-control form-control-sm gf-date-input" required value="{{ now()->toDateString() }}">
            </div>
            <div class="col-md-6">
              <label class="form-label">Gudang Penerima</label>
              <select name="warehouse_id" class="form-select form-select-sm" required>
                <option value="">Pilih Gudang...</option>
                @foreach(\App\Models\Warehouse::all() as $wh)
                  <option value="{{ $wh->id }}" {{ $ret->grn?->warehouse_id == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Catatan</label>
              <input type="text" name="notes" class="form-control form-control-sm">
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>Item</th>
                  <th>Menunggu</th>
                  <th width="150">Qty Terima</th>
                </tr>
              </thead>
              <tbody>
                @foreach($ret->lines as $line)
                  @if(round((float)$line->replacement_qty_expected - (float)$line->replacement_qty_received, 4) > 0)
                  @php $outstanding = round((float)$line->replacement_qty_expected - (float)$line->replacement_qty_received, 4); @endphp
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ $line->item?->name }}</div>
                      <small class="text-muted">{{ $line->item?->code }}</small>
                      <input type="hidden" name="lines[{{ $line->id }}][id]" value="{{ $line->id }}">
                    </td>
                    <td>{{ $outstanding }}</td>
                    <td>
                      <input type="number" step="0.0001" min="0" max="{{ $outstanding }}" name="lines[{{ $line->id }}][qty]" class="form-control form-control-sm" value="{{ $outstanding }}" required>
                    </td>
                  </tr>
                  @endif
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Penerimaan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const qtyInputs = Array.from(document.querySelectorAll('.qty-return-input'));
  const liveLines = document.getElementById('live-return-lines');
  const liveQty = document.getElementById('live-return-qty');

  function toNumber(value) {
    if (value === null || value === '') return 0;
    return Number(String(value).replace(',', '.')) || 0;
  }

  function formatQty(value) {
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(value);
  }

  function refreshRow(input) {
    const row = input.closest('.return-row');
    if (!row) return;
    const qty = toNumber(input.value);
    row.classList.toggle('has-qty', qty > 0.0001);
    row.classList.toggle('is-empty', qty <= 0.0001);
    const badgeWrap = row.querySelector('.return-mobile-head');
    if (badgeWrap) {
      badgeWrap.innerHTML = qty > 0.0001
        ? '<span class="primary-label">Diretur</span>'
        : '<span class="state-pill is-muted">Tidak diretur</span>';
    }
  }

  function refreshTotals() {
    let count = 0;
    let total = 0;
    qtyInputs.forEach(function (input) {
      const qty = toNumber(input.value);
      if (qty > 0.0001) count++;
      total += qty;
      refreshRow(input);
    });
    if (liveLines) liveLines.textContent = count;
    if (liveQty) liveQty.textContent = formatQty(total);
  }

  qtyInputs.forEach(function (input) {
    input.addEventListener('focus', function () { setTimeout(function () { input.select(); }, 0); });
    input.addEventListener('input', refreshTotals);
  });

  document.querySelectorAll('.js-zero-row').forEach(function (button) {
    button.addEventListener('click', function () {
      const input = button.closest('.return-input-wrap')?.querySelector('.qty-return-input');
      if (!input) return;
      input.value = '';
      input.focus();
      refreshTotals();
    });
  });

  document.querySelectorAll('.js-max-row').forEach(function (button) {
    button.addEventListener('click', function () {
      const input = button.closest('.return-input-wrap')?.querySelector('.qty-return-input');
      if (!input) return;
      input.value = input.dataset.max || input.max || '';
      input.focus();
      refreshTotals();
    });
  });

  document.getElementById('btn-zero-all')?.addEventListener('click', function () {
    qtyInputs.forEach(function (input) { input.value = ''; });
    qtyInputs[0]?.focus();
    refreshTotals();
  });

  document.getElementById('btn-max-all')?.addEventListener('click', function () {
    qtyInputs.forEach(function (input) { input.value = input.dataset.max || input.max || ''; refreshRow(input); });
    qtyInputs[0]?.focus();
    refreshTotals();
  });

  document.getElementById('btn-focus-first')?.addEventListener('click', function () {
    const emptyInput = qtyInputs.find(input => toNumber(input.value) <= 0.0001);
    (emptyInput || qtyInputs[0])?.focus();
  });

  refreshTotals();

  function confirmSubmit(form, options) {
    form.addEventListener('submit', function (event) {
      if (form.dataset.confirmed === '1' || !window.Swal) return;
      event.preventDefault();
      Swal.fire({
        icon: options.icon,
        title: options.title,
        text: options.text,
        showCancelButton: true,
        confirmButtonText: options.confirmText,
        cancelButtonText: 'Batal',
        confirmButtonColor: options.color,
        reverseButtons: true
      }).then(function (result) {
        if (!result.isConfirmed) return;
        form.dataset.confirmed = '1';
        form.submit();
      });
    });
  }

  document.querySelectorAll('.js-post-return').forEach(function (form) {
    confirmSubmit(form, {
      icon: 'question',
      title: 'Posting return?',
      text: 'Draft akan resmi: stok keluar dari gudang asal dan jurnal return dibuat.',
      confirmText: 'Ya, Posting',
      color: '#16a34a'
    });
  });

  document.querySelectorAll('.js-void-return').forEach(function (form) {
    confirmSubmit(form, {
      icon: 'warning',
      title: 'Void return?',
      text: 'Stok akan dikembalikan ke gudang asal dan jurnal return akan dibatalkan.',
      confirmText: 'Ya, Void Return',
      color: '#dc2626'
    });
  });
});
</script>
@endpush
