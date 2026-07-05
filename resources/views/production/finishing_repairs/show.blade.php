@extends('layouts.app')

@section('title', 'Produksi • Perbaikan ' . $repair->code)

@push('head')
<style>
  .page-wrap{ max-width:1000px; margin-inline:auto; padding:.8rem .8rem 3.5rem; }
  body[data-theme="light"] .page-wrap{ background: radial-gradient(circle at top left, rgba(56,189,248,.18), #f9fafb 58%); }
  .card{ background:var(--card); border-radius:16px; border:1px solid rgba(148,163,184,.22); box-shadow:0 10px 26px rgba(15,23,42,.08),0 0 0 1px rgba(15,23,42,.03); }
  .card-section{ padding:.9rem 1rem; }
  .mono{ font-variant-numeric:tabular-nums; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono"; }
  .btn-pill{ border-radius:999px; padding:.28rem .8rem; font-size:.8rem; font-weight:800; }
  .badge-status{ font-size:.7rem; text-transform:uppercase; letter-spacing:.12em; border-radius:999px; padding:.16rem .7rem; font-weight:800; background:rgba(14,165,233,.14); color:#0369a1; border:1px solid rgba(14,165,233,.45); }
  .summary-grid{ display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.5rem .9rem; }
  @media(min-width:768px){ .summary-grid{ grid-template-columns:repeat(4,minmax(0,1fr)); } }
  .summary-label{ font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); }
  .summary-value{ font-weight:900; }
  .table thead th{ font-size:.74rem; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); border-top:none; white-space:nowrap; }
  .table tbody td{ font-size:.82rem; vertical-align:middle; }
</style>
@endpush

@section('content')
@php
  $totalOk = (float) $repair->lines->sum('qty_ok');
  $totalReject = (float) $repair->lines->sum('qty_reject');
  $itemCount = $repair->lines->pluck('item_id')->unique()->count();
@endphp

<div class="page-wrap">
  @if(session('status'))
    <div class="alert alert-success py-2">{{ session('status') }}</div>
  @endif

  <div class="card mb-2">
    <div class="card-section d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <div class="fw-bold">Perbaikan <span class="mono">{{ $repair->code }}</span></div>
        <div class="small text-muted">{{ optional($repair->date)->format('d M Y') ?? '-' }} • Dibuat oleh {{ $repair->createdBy?->name ?? '-' }}</div>
        @if($repair->notes)
          <div class="small text-muted mt-1">{{ $repair->notes }}</div>
        @endif
      </div>
      <div class="d-flex gap-2 flex-wrap justify-content-end">
        <span class="badge-status">{{ strtoupper($repair->status) }}</span>
        <a href="{{ route('production.finishing_repairs.index') }}" class="btn btn-outline-secondary btn-sm btn-pill">Kembali</a>
        <a href="{{ route('production.finishing_repairs.create') }}" class="btn btn-primary btn-sm btn-pill">Input Baru</a>
      </div>
    </div>
  </div>

  <div class="card mb-2">
    <div class="card-section">
      <div class="summary-grid">
        <div>
          <div class="summary-label">Qty OK</div>
          <div class="summary-value mono">{{ number_format($totalOk, 2, ',', '.') }}</div>
        </div>
        <div>
          <div class="summary-label">Tidak Bisa</div>
          <div class="summary-value mono">{{ number_format($totalReject, 2, ',', '.') }}</div>
        </div>
        <div>
          <div class="summary-label">Item</div>
          <div class="summary-value mono">{{ number_format($itemCount, 0, ',', '.') }}</div>
        </div>
        <div>
          <div class="summary-label">Baris</div>
          <div class="summary-value mono">{{ number_format($repair->lines->count(), 0, ',', '.') }}</div>
        </div>
        <div>
          <div class="summary-label">Alur Stok</div>
          <div class="summary-value">OK ke WH-PRD · Tidak Bisa ke WH-RTS</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-section">
      <table class="table table-sm align-middle mb-0">
        <thead>
          <tr>
            <th style="width:44px;">#</th>
            <th style="width:150px;">Item</th>
            <th>Bundle / Finishing</th>
            <th class="text-end" style="width:130px;">Qty OK</th>
            <th class="text-end" style="width:130px;">Tidak Bisa</th>
            <th style="width:150px;">SKU Reject</th>
            <th>Catatan</th>
          </tr>
        </thead>
        <tbody>
          @foreach($repair->lines as $idx => $line)
            <tr>
              <td class="text-muted mono">{{ $idx + 1 }}</td>
              <td>
                <div class="fw-bold mono">{{ $line->item?->code ?? '-' }}</div>
                <div class="small text-muted">{{ $line->item?->name ?? '' }}</div>
              </td>
              <td>
                <div class="small">Bundle <span class="mono fw-bold">{{ $line->bundle?->bundle_code ?? '-' }}</span></div>
                <div class="small text-muted">Finishing: <span class="mono">{{ $line->finishingJobLine?->job?->code ?? '-' }}</span></div>
              </td>
              <td class="text-end mono fw-bold">{{ number_format((float) $line->qty_ok, 2, ',', '.') }}</td>
              <td class="text-end mono fw-bold">{{ number_format((float) $line->qty_reject, 2, ',', '.') }}</td>
              <td>
                @if((float) $line->qty_reject > 0 && $line->rejectItem)
                  <div class="fw-bold mono">{{ $line->rejectItem->code }}</div>
                  <div class="small text-muted">{{ $line->rejectItem->name }}</div>
                  <div class="small text-muted mono">-> WH-RTS</div>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
              <td class="small text-muted">{{ $line->notes ?: '-' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
