@extends('layouts.app')

@section('title', 'Produksi • Input Perbaikan Reject Finishing')

@push('head')
<style>
  .page-wrap{ max-width:1000px; margin-inline:auto; padding:.8rem .8rem 5rem; }
  body[data-theme="light"] .page-wrap{ background: radial-gradient(circle at top left, rgba(56,189,248,.18), #f9fafb 58%); }
  .card{ background:var(--card); border-radius:16px; border:1px solid rgba(148,163,184,.22); box-shadow:0 10px 26px rgba(15,23,42,.08),0 0 0 1px rgba(15,23,42,.03); }
  .card-section{ padding:.9rem 1rem; }
  .mono{ font-variant-numeric:tabular-nums; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono"; }
  .btn-pill{ border-radius:999px; padding:.28rem .8rem; font-size:.8rem; font-weight:800; }
  .pill{ border-radius:999px; padding:.12rem .6rem; font-size:.72rem; font-weight:800; background:rgba(14,165,233,.12); color:#0369a1; border:1px solid rgba(14,165,233,.4); }
  .table thead th{ font-size:.74rem; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); border-top:none; white-space:nowrap; }
  .table tbody td{ font-size:.82rem; vertical-align:middle; }
  .qty{ max-width:120px; margin-left:auto; text-align:right; font-weight:900; }
  .row-rj{ background:rgba(239,246,255,.96); }
  .sticky-actions{ position:fixed; right:14px; bottom:88px; z-index:20; display:flex; gap:.5rem; }
  .sticky-actions .btn{ box-shadow:0 12px 26px rgba(15,23,42,.18); }
</style>
@endpush

@section('content')
<div class="page-wrap">
  @if ($errors->any())
    <div class="alert alert-danger">
      <div class="fw-bold mb-1">Data belum bisa disimpan:</div>
      <ul class="mb-0">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('production.finishing_repairs.store') }}">
    @csrf

    <div class="card mb-2">
      <div class="card-section d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
          <div class="fw-bold">Input Perbaikan Reject Finishing</div>
          <div class="small text-muted">Qty OK masuk WH-PRD. Qty Tidak Bisa berubah menjadi SKU kategori-RJCT di REJ-FIN.</div>
        </div>
        <a href="{{ route('production.finishing_repairs.index') }}" class="btn btn-outline-secondary btn-sm btn-pill">Kembali</a>
      </div>
    </div>

    <div class="card mb-2">
      <div class="card-section">
        <div class="row g-2">
          <div class="col-6 col-md-3">
            <label class="form-label small text-muted">Tanggal</label>
            <input type="date" name="date" class="form-control form-control-sm mono" value="{{ old('date', $dateValue) }}" required>
          </div>
          <div class="col-12 col-md-9">
            <label class="form-label small text-muted">Catatan</label>
            <input type="text" name="notes" class="form-control form-control-sm" value="{{ old('notes') }}" maxlength="1000" placeholder="Opsional">
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-section">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
          <div>
            <div class="fw-bold">Daftar REJ-FIN</div>
            <div class="small text-muted">{{ number_format($lines->count(), 0, ',', '.') }} baris bisa diperbaiki</div>
          </div>
          <span class="pill">Isi hasil repair</span>
        </div>

        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th style="width:44px;">#</th>
                <th style="width:150px;">Item</th>
                <th>Bundle / Finishing</th>
                <th class="text-end" style="width:120px;">Sisa</th>
                <th class="text-end" style="width:135px;">Qty OK</th>
                <th class="text-end" style="width:135px;">Tidak Bisa</th>
                <th style="width:220px;">Catatan</th>
              </tr>
            </thead>
            <tbody>
              @forelse($lines as $idx => $line)
                <tr class="row-rj">
                  <td class="text-muted mono">{{ $idx + 1 }}</td>
                  <td>
                    <div class="fw-bold mono">{{ $line->item_code }}</div>
                    <div class="small text-muted">{{ $line->item_name }}</div>
                  </td>
                  <td>
                    <div class="small">Bundle <span class="mono fw-bold">{{ $line->bundle_code }}</span></div>
                    <div class="small text-muted">Finishing: <span class="mono">{{ $line->finishing_code }}</span> • {{ $line->finishing_date }}</div>
                    @if($line->reject_reason || $line->reject_notes)
                      <div class="small text-muted">{{ $line->reject_reason ?: $line->reject_notes }}</div>
                    @endif
                  </td>
                  <td class="text-end mono fw-bold">{{ number_format((float) $line->remaining_qty, 2, ',', '.') }}</td>
                  <td class="text-end">
                    <input type="hidden" name="results[{{ $idx }}][finishing_job_line_id]" value="{{ $line->finishing_job_line_id }}">
                    <input type="number" step="0.01" min="0" max="{{ (float) $line->remaining_qty }}"
                      name="results[{{ $idx }}][qty_ok]" class="form-control form-control-sm qty mono"
                      value="{{ old("results.$idx.qty_ok") }}" placeholder="0">
                  </td>
                  <td class="text-end">
                    <input type="number" step="0.01" min="0" max="{{ (float) $line->remaining_qty }}"
                      name="results[{{ $idx }}][qty_reject]" class="form-control form-control-sm qty mono"
                      value="{{ old("results.$idx.qty_reject") }}" placeholder="0">
                    <div class="small text-muted mono mt-1">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::slug($line->category_code ?: 'REJECT', '-')) }}-RJCT</div>
                  </td>
                  <td>
                    <input type="text" name="results[{{ $idx }}][notes]" class="form-control form-control-sm"
                      value="{{ old("results.$idx.notes") }}" maxlength="255" placeholder="Opsional">
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">Tidak ada stok REJ-FIN yang bisa diperbaiki.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="sticky-actions">
      <a href="{{ route('production.finishing_repairs.index') }}" class="btn btn-outline-secondary btn-sm btn-pill">Batal</a>
      <button type="submit" class="btn btn-primary btn-sm btn-pill" @disabled($lines->isEmpty())>Simpan</button>
    </div>
  </form>
</div>
@endsection
