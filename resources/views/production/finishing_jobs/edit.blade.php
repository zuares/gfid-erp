@extends('layouts.app')

@section('title', 'Produksi • Finishing (Edit)')

@push('head')
<style>
  .page-wrap{max-width:1150px;margin:0 auto;padding:1rem 1rem 4rem}
  .card-main{background:var(--card);border-radius:14px;border:1px solid rgba(148,163,184,.35);box-shadow:0 10px 30px rgba(15,23,42,.14)}
  .card-head{padding:.9rem 1.1rem;border-bottom:1px solid rgba(148,163,184,.35);display:flex;gap:.75rem;align-items:flex-start;justify-content:space-between}
  .card-head h1{font-size:1.05rem;font-weight:700;margin:0}
  .muted{color:var(--muted-foreground)}
  .pill{display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;padding:.25rem .6rem;font-size:.72rem;border:1px solid rgba(148,163,184,.35);background:rgba(148,163,184,.08)}
  .pill-info{background:rgba(37,99,235,.08);border-color:rgba(59,130,246,.25);color:#1d4ed8}
  .pill-warn{background:rgba(245,158,11,.10);border-color:rgba(245,158,11,.25);color:#92400e}
  .body{padding:1rem 1.1rem}
  .table-wrap{border-radius:12px;border:1px solid rgba(148,163,184,.35);overflow:hidden}
  .table thead th{background:rgba(15,23,42,.03);font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:var(--muted-foreground);white-space:nowrap}
  .table td{vertical-align:middle}
  .badge-in{display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;padding:.22rem .55rem;font-size:.72rem;background:rgba(16,185,129,.08);color:#047857}
  .qty-ok{border-color:rgba(34,197,94,.55)}
  .qty-rj{border-color:rgba(239,68,68,.55)}
  .footer{display:flex;gap:.75rem;justify-content:flex-end;margin-top:1rem;padding-top:.8rem;border-top:1px dashed rgba(148,163,184,.5)}
  .btn-save{border-radius:999px;padding-inline:1.25rem;font-weight:700}
  .small-note{font-size:.72rem}
  .req{color:rgba(239,68,68,.9);font-weight:800}
  @media(max-width:768px){ .card-head{flex-direction:column} .footer{flex-direction:column-reverse} .btn-save{width:100%} }
</style>
@endpush

@section('content')
@php
  $dateDefault = old('date', optional($job->date)->format('Y-m-d') ?? now()->format('Y-m-d'));
  $linesData = old('lines') ?? $lines;
  $isPosted = (string)($job->status ?? '') === 'posted';

  $totalLines = is_iterable($linesData) ? count($linesData) : 0;
@endphp

<div class="page-wrap">
  @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
      <strong>Oops!</strong> Ada error input. Silakan cek form di bawah.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if ($isPosted)
    <div class="alert alert-info mb-3">
      <i class="bi bi-lock-fill"></i>
      Finishing Job ini sudah <strong>POSTED</strong>. Form dinonaktifkan.
    </div>
  @endif

  <div class="card card-main">
    <div class="card-head">
      <div>
        <h1>Edit Finishing</h1>
        <div class="muted small mt-1">
          Kode: <strong>{{ $job->code }}</strong> ·
          Tanggal: <strong>{{ optional($job->date)->format('d M Y') ?? '-' }}</strong> ·
          Status: <strong>{{ strtoupper((string)$job->status) }}</strong>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <span class="pill pill-info">
          <i class="bi bi-pencil-square"></i>
          Draft: OK + Reject ≤ IN (sisa direlease)
        </span>
        <span class="pill pill-warn">
          <i class="bi bi-arrow-counterclockwise"></i>
          Sisa = IN - (OK+Reject)
        </span>
      </div>
    </div>

    <form action="{{ route('production.finishing_jobs.update', $job->id) }}" method="POST"
          @if($isPosted) onsubmit="return false;" @endif novalidate>
      @csrf
      @method('PUT')

      <div class="body">
        {{-- header form --}}
        <div class="row g-3 mb-3">
          <div class="col-6 col-md-3">
            <label class="form-label form-label-sm mb-1">Tanggal</label>
            <input type="date" name="date"
                   class="form-control form-control-sm @error('date') is-invalid @enderror"
                   value="{{ $dateDefault }}" @disabled($isPosted)>
            @error('date') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>

          <div class="col-12 col-md-9">
            <label class="form-label form-label-sm mb-1">Catatan</label>
            <textarea name="notes" rows="1"
                      class="form-control form-control-sm @error('notes') is-invalid @enderror"
                      placeholder="Catatan tambahan" @disabled($isPosted)>{{ old('notes', $job->notes) }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>

        {{-- table --}}
        <div class="table-wrap mb-2">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 finishing-table">
              <thead>
                <tr>
                  <th class="text-center" style="width:5%;">No</th>
                  <th style="width:34%;">Item & Bundle</th>
                  <th class="text-end" style="width:11%;">Qty IN</th>
                  <th class="text-end" style="width:11%;">Qty OK</th>
                  <th class="text-end" style="width:11%;">Qty Reject</th>
                  <th class="text-end" style="width:11%;">Sisa</th>
                  <th style="width:17%;">Alasan Reject</th>
                </tr>
              </thead>
              <tbody>
                @forelse($linesData as $idx => $line)
                  @php
                    $isOld = is_array($line);

                    $lineId   = $isOld ? ($line['id'] ?? null) : ($line->id ?? null);
                    $bundleId = $isOld ? ($line['bundle_id'] ?? null) : ($line->bundle_id ?? null);
                    $itemId   = $isOld ? ($line['item_id'] ?? null) : ($line->item_id ?? null);

                    $qtyIn = $isOld
                      ? (int)round((float)($line['qty_in'] ?? ((float)($line['qty_ok'] ?? 0) + (float)($line['qty_reject'] ?? 0))))
                      : (int)round((float)($line->qty_in ?? ((float)($line->qty_ok ?? 0) + (float)($line->qty_reject ?? 0))));

                    $qtyOk = (int) old("lines.$idx.qty_ok", $isOld ? ($line['qty_ok'] ?? 0) : ($line->qty_ok ?? 0));
                    $qtyRj = (int) old("lines.$idx.qty_reject", $isOld ? ($line['qty_reject'] ?? 0) : ($line->qty_reject ?? 0));
                    $reason = old("lines.$idx.reject_reason", $isOld ? ($line['reject_reason'] ?? '') : ($line->reject_reason ?? ''));

                    // (opsional) kalau mau super cepat tanpa N+1, preload item+bundle di controller.
                    $bundleModel = $isOld ? \App\Models\CuttingJobBundle::find($bundleId) : ($line->bundle ?? null);
                    $item = $isOld ? \App\Models\Item::find($itemId) : ($line->item ?? ($bundleModel?->item ?? null));
                    $bundleLabel = $bundleModel?->code ? $bundleModel->code : ('#' . ($bundleModel?->id ?? '-'));
                    $sisa = max(0, $qtyIn - ($qtyOk + $qtyRj));
                  @endphp

                  <tr data-idx="{{ $idx }}">
                    <td class="text-center">
                      {{ $loop->iteration }}

                      <input type="hidden" name="lines[{{ $idx }}][id]" value="{{ $lineId }}">
                      {{-- qty_in ini hanya untuk JS clamp, server tetap pakai qty_in DB line saat update --}}
                      <input type="hidden" class="js-in" name="lines[{{ $idx }}][qty_in]" value="{{ $qtyIn }}">
                    </td>

                    <td>
                      <div class="fw-semibold">
                        {{ $item?->code ?? 'ITEM' }} — {{ $item?->name ?? 'Tidak ditemukan' }}
                      </div>
                      <div class="muted small-note">
                        Item ID: {{ $item?->id ?? '-' }} · Bundle: <strong>{{ $bundleLabel }}</strong>
                      </div>
                      @error("lines.$idx.id")
                        <div class="text-danger small mt-1">{{ $message }}</div>
                      @enderror
                    </td>

                    <td class="text-end">
                      <span class="badge-in">
                        <i class="bi bi-arrow-up-circle"></i>
                        <span class="js-in-text">{{ number_format($qtyIn, 0, ',', '.') }}</span> pcs
                      </span>
                    </td>

                    <td class="text-end">
                      <input type="number" min="0" step="1" inputmode="numeric" pattern="\d*"
                             name="lines[{{ $idx }}][qty_ok]"
                             class="form-control form-control-sm text-end qty-ok js-ok qty-ok @error("lines.$idx.qty_ok") is-invalid @enderror"
                             value="{{ $qtyOk }}" @disabled($isPosted)>
                      @error("lines.$idx.qty_ok")
                        <div class="invalid-feedback d-block text-start">{{ $message }}</div>
                      @enderror
                    </td>

                    <td class="text-end">
                      <input type="number" min="0" step="1" inputmode="numeric" pattern="\d*"
                             name="lines[{{ $idx }}][qty_reject]"
                             class="form-control form-control-sm text-end qty-rj js-rj @error("lines.$idx.qty_reject") is-invalid @enderror"
                             value="{{ $qtyRj }}" @disabled($isPosted)>
                      @error("lines.$idx.qty_reject")
                        <div class="invalid-feedback d-block text-start">{{ $message }}</div>
                      @enderror
                    </td>

                    <td class="text-end">
                      <span class="fw-semibold js-sisa">{{ number_format($sisa, 0, ',', '.') }}</span>
                    </td>

                    <td>
                      <input type="text"
                             name="lines[{{ $idx }}][reject_reason]"
                             class="form-control form-control-sm js-reason @error("lines.$idx.reject_reason") is-invalid @enderror"
                             placeholder="Wajib jika reject > 0"
                             value="{{ $reason }}" @disabled($isPosted)">
                      <div class="small-note muted mt-1 js-reason-hint" style="{{ $qtyRj > 0 ? '' : 'display:none;' }}">
                        <span class="req">*</span> Wajib isi jika Reject > 0
                      </div>
                      @error("lines.$idx.reject_reason")
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                      Tidak ada detail untuk diedit.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        {{-- summary --}}
        <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
          <span class="pill"><i class="bi bi-collection"></i> Lines: <strong>{{ $totalLines }}</strong></span>
          <span class="pill"><i class="bi bi-check2-circle"></i> Total OK: <strong id="sumOk">0</strong></span>
          <span class="pill"><i class="bi bi-x-octagon"></i> Total Reject: <strong id="sumRj">0</strong></span>
          <span class="pill pill-warn"><i class="bi bi-arrow-counterclockwise"></i> Total Sisa: <strong id="sumSisa">0</strong></span>
        </div>

        {{-- actions --}}
        <div class="footer">
          <a href="{{ route('production.finishing_jobs.show', $job->id) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> <span class="ms-1">Batal</span>
          </a>

          @if($isPosted)
            <button type="button" class="btn btn-sm btn-secondary" disabled>
              <i class="bi bi-lock-fill"></i> Diposted — tidak bisa diubah
            </button>
          @else
            <button type="submit" class="btn btn-sm btn-success btn-save">
              <i class="bi bi-check2-circle"></i> <span class="ms-1 text-white">Simpan Perubahan</span>
            </button>
          @endif
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const isPosted = @json($isPosted);
  if (isPosted) return;

  const table = document.querySelector('.finishing-table');
  if (!table) return;

  const sumOkEl = document.getElementById('sumOk');
  const sumRjEl = document.getElementById('sumRj');
  const sumSisaEl = document.getElementById('sumSisa');

  const toInt = (v) => {
    const n = parseInt(String(v ?? '').replace(/[^0-9]/g,''), 10);
    return Number.isFinite(n) ? n : 0;
  };
  const fmt = (n) => (n || 0).toLocaleString('id-ID');

  // digits only (ok & reject)
  const integerInputs = document.querySelectorAll('input[name*="[qty_ok]"], input[name*="[qty_reject]"]');
  integerInputs.forEach(el => {
    el.addEventListener('paste', function(e){
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text');
      const digits = String(text || '').replace(/[^0-9]/g,'');
      document.execCommand('insertText', false, digits);
    });
    el.addEventListener('input', function(){
      const clean = String(this.value || '').replace(/[^0-9]/g,'');
      if (String(this.value) !== clean) this.value = clean;
    });
    el.addEventListener('wheel', function(e){
      if (document.activeElement === this) e.preventDefault();
    }, {passive:false});
  });

  function clampRow(row) {
    const idx = row.getAttribute('data-idx');
    const inEl = row.querySelector('.js-in');
    const okEl = row.querySelector('.js-ok');
    const rjEl = row.querySelector('.js-rj');
    const sisaEl = row.querySelector('.js-sisa');
    const reasonEl = row.querySelector('.js-reason');
    const hintEl = row.querySelector('.js-reason-hint');

    const qtyIn = toInt(inEl?.value || 0);
    let ok = toInt(okEl?.value || 0);
    let rj = toInt(rjEl?.value || 0);

    // clamp 0..IN
    if (ok > qtyIn) ok = qtyIn;
    if (rj > qtyIn) rj = qtyIn;

    // clamp OK+Reject <= IN (TIDAK auto-balance)
    const maxRj = Math.max(0, qtyIn - ok);
    if (rj > maxRj) rj = maxRj;

    if (okEl) okEl.value = String(ok);
    if (rjEl) rjEl.value = String(rj);

    const sisa = Math.max(0, qtyIn - (ok + rj));
    if (sisaEl) sisaEl.textContent = fmt(sisa);

    // reason required if reject > 0 (UI only)
    if (hintEl) hintEl.style.display = (rj > 0) ? 'block' : 'none';
    if (reasonEl) reasonEl.required = (rj > 0);
  }

  function recomputeTotals(){
    let totOk=0, totRj=0, totSisa=0;

    document.querySelectorAll('tr[data-idx]').forEach(row => {
      const inEl = row.querySelector('.js-in');
      const okEl = row.querySelector('.js-ok');
      const rjEl = row.querySelector('.js-rj');

      const qtyIn = toInt(inEl?.value || 0);
      const ok = toInt(okEl?.value || 0);
      const rj = toInt(rjEl?.value || 0);

      totOk += ok;
      totRj += rj;
      totSisa += Math.max(0, qtyIn - (ok + rj));
    });

    if (sumOkEl) sumOkEl.textContent = `${fmt(totOk)} pcs`;
    if (sumRjEl) sumRjEl.textContent = `${fmt(totRj)} pcs`;
    if (sumSisaEl) sumSisaEl.textContent = `${fmt(totSisa)} pcs`;
  }

  // init
  document.querySelectorAll('tr[data-idx]').forEach(row => clampRow(row));
  recomputeTotals();

  table.addEventListener('input', function(e){
    const el = e.target;
    if (!el || !el.name) return;
    if (!el.name.includes('[qty_ok]') && !el.name.includes('[qty_reject]')) return;

    const row = el.closest('tr[data-idx]');
    if (!row) return;

    clampRow(row);
    recomputeTotals();
  });
});
</script>
@endpush
