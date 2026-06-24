{{-- resources/views/purchasing/purchase_returns/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Purchasing • Return Pembelian')

@push('head')
<style>
  .purchase-return-index .page-wrap{ max-width: 1120px; margin-inline:auto; }
  .purchase-return-index .page-title{ font-size:1.28rem; font-weight:700; }
  .purchase-return-index .page-subtitle{ font-size:.84rem; color:var(--muted); }
  .purchase-return-index .card-clean{
    border:1px solid var(--line);
    border-radius:14px;
    background:color-mix(in srgb, var(--card) 96%, var(--bg) 4%);
  }
  .purchase-return-index .stat{
    border:1px solid var(--line);
    border-radius:10px;
    padding:.65rem .75rem;
    background:rgba(148,163,184,.07);
  }
  .purchase-return-index .stat .lbl{ display:block; font-size:.72rem; color:var(--muted); }
  .purchase-return-index .stat .val{ display:block; font-weight:800; line-height:1.25; }
  .purchase-return-index .mono{
    font-variant-numeric:tabular-nums;
    font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono";
  }
  .purchase-return-index .status-badge{
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    padding:.14rem .58rem;
    font-size:.75rem;
    font-weight:700;
    border:1px solid var(--line);
  }
  .purchase-return-index .status-draft{ background:rgba(245,158,11,.12); color:#b45309; }
  .purchase-return-index .status-posted{ background:rgba(34,197,94,.12); color:#15803d; }
  .purchase-return-index .status-void{ background:rgba(239,68,68,.12); color:#b91c1c; }
  .purchase-return-index .effect-text{
    margin-top:.2rem;
    color:var(--muted);
    font-size:.72rem;
    line-height:1.2;
  }
  .purchase-return-index .table-wrap{ overflow-x:auto; }
  .purchase-return-index thead th{
    background:color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
    border-bottom-color:var(--line);
    font-size:.74rem;
    letter-spacing:.06em;
    text-transform:uppercase;
    white-space:nowrap;
  }
  .purchase-return-index tbody td{ vertical-align:middle; border-bottom-color:var(--line); }
  .purchase-return-index .row-link{ cursor:pointer; }
  .purchase-return-index .row-link:hover{ background:rgba(59,130,246,.05); }
  @media (max-width:767.98px){
    .purchase-return-index .page-header{ flex-direction:column; align-items:stretch !important; }
    .purchase-return-index .table thead{ display:none; }
    .purchase-return-index .table tbody tr{
      display:block;
      border:1px solid var(--line);
      border-radius:12px;
      margin-bottom:.65rem;
      padding:.55rem .65rem;
      background:rgba(15,23,42,.02);
    }
    .purchase-return-index .table tbody td{
      display:flex;
      justify-content:space-between;
      gap:.75rem;
      border:0;
      padding:.22rem 0;
    }
    .purchase-return-index .table tbody td[data-label]::before{
      content:attr(data-label);
      color:var(--muted);
      font-size:.76rem;
    }
  }
</style>
@endpush

@section('content')
@php
  $canSeeMoney = auth()->user()?->isOwner() ?? false;
  $totalReturns = (int) ($summary->total_returns ?? 0);
  $draftCount = (int) ($summary->draft_count ?? 0);
  $postedCount = (int) ($summary->posted_count ?? 0);
  $voidCount = (int) ($summary->void_count ?? 0);
@endphp

<div class="container py-3 purchase-return-index">
  <div class="page-wrap">
    <div class="page-header d-flex justify-content-between align-items-start gap-2 mb-3">
      <div>
        <h1 class="page-title mb-1">Return Pembelian</h1>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('purchasing.purchase_receipts.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">
          Lihat GRN
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    <div class="row g-2 mb-3">
      <div class="col-6 col-md-3"><div class="stat"><span class="lbl">Total Return</span><span class="val mono">{{ angka($totalReturns) }}</span></div></div>
      <div class="col-6 col-md-3"><div class="stat"><span class="lbl">Draft</span><span class="val mono">{{ angka($draftCount) }}</span></div></div>
      <div class="col-6 col-md-3"><div class="stat"><span class="lbl">Posted</span><span class="val mono">{{ angka($postedCount) }}</span></div></div>
      <div class="col-6 col-md-3"><div class="stat"><span class="lbl">Void</span><span class="val mono">{{ angka($voidCount) }}</span></div></div>
    </div>

    <form method="GET" class="card-clean p-3 mb-3">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label small mb-1">Cari</label>
          <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm"
            placeholder="Kode return, GRN, supplier...">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">Semua</option>
            <option value="draft" @selected($status === 'draft')>Draft</option>
            <option value="posted" @selected($status === 'posted')>Posted</option>
            <option value="void" @selected($status === 'void')>Void</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Dari</label>
          <input type="text" name="from_date" value="{{ request('from_date') }}"
            class="form-control form-control-sm gf-date-input" data-gf-date autocomplete="off">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Sampai</label>
          <input type="text" name="to_date" value="{{ request('to_date') }}"
            class="form-control form-control-sm gf-date-input" data-gf-date autocomplete="off">
        </div>
        <div class="col-6 col-md-2 d-grid">
          <button class="btn btn-primary btn-sm">Filter</button>
        </div>
      </div>
    </form>

    <div class="card-clean">
      <div class="table-wrap">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr>
              <th style="width:48px;">No</th>
              <th>Return</th>
              <th>GRN / Supplier</th>
              <th class="text-end">Qty</th>
              @if($canSeeMoney)
                <th class="text-end">Nilai</th>
              @endif
              <th>Status / Efek</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($returns as $ret)
              @php
                $isVoid = (bool) $ret->voided_at;
                $statusCss = $isVoid ? 'status-void' : (($ret->status === 'posted') ? 'status-posted' : 'status-draft');
                $statusText = $isVoid ? 'VOID' : strtoupper((string) $ret->status);
                $effectText = $isVoid
                  ? 'Stok balik, jurnal batal'
                  : (($ret->status === 'posted') ? 'Stok keluar, jurnal masuk' : 'Belum ubah stok/jurnal');
                $href = route('purchasing.purchase_returns.show', $ret->id);
              @endphp
              <tr class="row-link" data-href="{{ $href }}">
                <td class="mono text-muted" data-label="No">{{ $returns->firstItem() + $loop->index }}</td>
                <td data-label="Return">
                  <div class="fw-bold mono">{{ $ret->code }}</div>
                  <div class="small text-muted">{{ $ret->date ? id_date($ret->date) : '-' }}</div>
                </td>
                <td data-label="GRN / Supplier">
                  <div class="mono fw-semibold">{{ $ret->grn?->code ?? '-' }}</div>
                  <div class="small text-muted">{{ $ret->supplier?->name ?? $ret->grn?->supplier?->name ?? '-' }}</div>
                </td>
                <td class="text-end mono" data-label="Qty">
                  {{ decimal_id($ret->total_qty ?? 0, 2) }}
                </td>
                @if($canSeeMoney)
                  <td class="text-end mono" data-label="Nilai">{{ rupiah($ret->total ?? 0) }}</td>
                @endif
                <td data-label="Status / Efek">
                  <span class="status-badge {{ $statusCss }}">{{ $statusText }}</span>
                  <div class="effect-text">{{ $effectText }}</div>
                </td>
                <td class="text-end" data-label="Aksi">
                  <a href="{{ $href }}" class="btn btn-outline-primary btn-sm rounded-pill">Buka</a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="{{ $canSeeMoney ? 7 : 6 }}" class="text-center text-muted py-4">
                  Belum ada return pembelian.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      {{ $returns->links() }}
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.purchase-return-index .row-link').forEach(function(row){
    row.addEventListener('click', function(e){
      if (e.target.closest('a, button, input, select')) return;
      const href = row.getAttribute('data-href');
      if (href) window.location.href = href;
    });
  });
});
</script>
@endpush
