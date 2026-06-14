@extends('layouts.app')

@section('title', 'Produksi • Perbaikan Reject Finishing')

@push('head')
<style>
  .page-wrap{ max-width:1000px; margin-inline:auto; padding:.8rem .8rem 3.5rem; }
  body[data-theme="light"] .page-wrap{ background: radial-gradient(circle at top left, rgba(56,189,248,.18), #f9fafb 58%); }
  .card{ background:var(--card); border-radius:16px; border:1px solid rgba(148,163,184,.22); box-shadow:0 10px 26px rgba(15,23,42,.08),0 0 0 1px rgba(15,23,42,.03); }
  .card-section{ padding:.9rem 1rem; }
  .mono{ font-variant-numeric:tabular-nums; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono"; }
  .btn-pill{ border-radius:999px; padding:.28rem .8rem; font-size:.8rem; font-weight:800; }
  .badge-status{ font-size:.7rem; text-transform:uppercase; letter-spacing:.12em; border-radius:999px; padding:.16rem .7rem; font-weight:800; background:rgba(14,165,233,.14); color:#0369a1; border:1px solid rgba(14,165,233,.45); }
  .table thead th{ font-size:.74rem; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); border-top:none; white-space:nowrap; }
  .table tbody td{ font-size:.82rem; vertical-align:middle; }
</style>
@endpush

@section('content')
<div class="page-wrap">
  @if(session('status'))
    <div class="alert alert-success py-2">{{ session('status') }}</div>
  @endif

  <div class="card mb-2">
    <div class="card-section d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <div class="fw-bold">Perbaikan Reject Finishing</div>
        <div class="small text-muted">Riwayat barang REJ-FIN: OK ke WH-PRD, tidak bisa diperbaiki jadi SKU kategori-RJCT.</div>
      </div>
      <a href="{{ route('production.finishing_repairs.create') }}" class="btn btn-primary btn-sm btn-pill">Input Perbaikan</a>
    </div>
  </div>

  <div class="card">
    <div class="card-section">
      <table class="table table-sm align-middle mb-0">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Kode</th>
            <th class="text-end">Qty OK</th>
            <th class="text-end">Tidak Bisa</th>
            <th class="text-end">Baris</th>
            <th>User</th>
            <th class="text-end"></th>
          </tr>
        </thead>
        <tbody>
          @forelse($repairs as $repair)
            <tr>
              <td class="mono">{{ optional($repair->date)->format('d M Y') ?? '-' }}</td>
              <td><span class="badge-status mono">{{ $repair->code }}</span></td>
              <td class="text-end mono fw-bold">{{ number_format((float) ($repair->total_qty_ok ?? 0), 2, ',', '.') }}</td>
              <td class="text-end mono fw-bold">{{ number_format((float) ($repair->total_qty_reject ?? 0), 2, ',', '.') }}</td>
              <td class="text-end mono">{{ number_format((int) $repair->lines_count, 0, ',', '.') }}</td>
              <td>{{ $repair->createdBy?->name ?? '-' }}</td>
              <td class="text-end">
                <a href="{{ route('production.finishing_repairs.show', $repair) }}" class="btn btn-outline-primary btn-sm btn-pill">Detail</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">Belum ada perbaikan reject finishing.</td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="mt-3">{{ $repairs->links() }}</div>
    </div>
  </div>
</div>
@endsection
