@extends('layouts.app')

@section('title', 'Saldo Awal (Batch)')

@push('head')
<style>
  :root{
    --r: 16px;
    --b: rgba(148,163,184,.22);
    --muted: #6b7280;
    --soft: rgba(148,163,184,.10);
    --shadow: 0 10px 26px rgba(15,23,42,.08), 0 0 0 1px rgba(15,23,42,.03);
  }
  .ob-wrap{ max-width: 1100px; margin: 0 auto; padding: 16px 14px 28px; }
  .cardx{ background: var(--card, #fff); border: 1px solid var(--b); border-radius: var(--r); box-shadow: var(--shadow); }
  .mono{ font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; }

  .head{ display:flex; align-items:flex-end; justify-content:space-between; gap:12px; margin-bottom:12px; }
  .h1{ font-size: 18px; font-weight: 800; margin:0; }
  .sub{ color: var(--muted); font-size: 13px; }
  .actions{ display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }

  .pill{
    display:inline-flex; align-items:center; gap:6px;
    border: 1px solid var(--b);
    background: color-mix(in srgb, var(--card) 85%, var(--bg, #fff) 15%);
    padding: .25rem .6rem; border-radius: 999px; font-size: 12px; color: var(--muted);
  }

  .stat-grid{ display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:10px; margin: 12px 0; }
  .stat{ padding: 12px; border-radius: 14px; border: 1px solid var(--b); background: color-mix(in srgb, var(--card) 90%, var(--bg, #fff) 10%); }
  .stat .k{ font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }
  .stat .v{ font-size: 16px; font-weight: 800; margin-top: 2px; }
  .stat .s{ font-size: 12px; color: var(--muted); margin-top: 2px; }

  .table thead th{
    font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: .06em;
    background: color-mix(in srgb, var(--card) 92%, var(--bg, #fff) 8%);
    border-bottom: 1px solid var(--b);
    padding: 10px 12px;
    white-space: nowrap;
  }
  .table tbody td{
    padding: 12px;
    border-top: 1px solid var(--soft);
    vertical-align: middle;
  }
  .badge-soft{
    display:inline-flex; align-items:center; gap:6px;
    padding: .18rem .55rem; border-radius: 999px; font-size: 12px;
    border: 1px solid var(--b);
    background: color-mix(in srgb, var(--card) 85%, var(--bg, #fff) 15%);
    color: var(--muted);
  }
  .badge-ok{ border-color: rgba(34,197,94,.25); background: rgba(34,197,94,.10); color: rgb(22,163,74); }
  .badge-void{ border-color: rgba(239,68,68,.25); background: rgba(239,68,68,.10); color: rgb(220,38,38); }

  .row-title{ font-weight: 800; }
  .row-sub{ font-size: 12px; color: var(--muted); margin-top: 2px; }
  .amount{ font-weight: 900; }

  @media (max-width: 767.98px){
    .ob-wrap{ padding: 12px 12px 22px; }
    .head{ align-items:flex-start; flex-direction:column; }
    .actions{ width:100%; }
    .actions .btn{ flex: 1 1 auto; }
    .stat-grid{ grid-template-columns: 1fr; }
    .table thead{ display:none; }
    .table tbody tr{ display:block; border-top: 1px solid var(--b); padding: 10px 10px; }
    .table tbody td{ display:flex; justify-content:space-between; gap:10px; border:0; padding: 6px 2px; }
    .table tbody td[data-label]::before{
      content: attr(data-label);
      color: var(--muted);
      font-size: 12px;
    }
    .table tbody td .stack{ text-align:right; }
  }
</style>
@endpush

@section('content')
@php
  $fmtMoney = fn($n) => number_format((float)$n, 0, ',', '.');

  $activeCount = 0;
  $voidCount = 0;

  // hitung ringkasan dari page ini (bukan seluruh DB)
  foreach ($journals as $j) {
    if ($j->voided_at) $voidCount++;
    else $activeCount++;
  }
@endphp

<div class="ob-wrap">

  {{-- HEADER --}}
  <div class="head">
    <div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <h1 class="h1">Saldo Awal (Batch)</h1>
        <span class="pill">Sumber: opening_balance_batch</span>
      </div>
      <div class="sub">Isi saldo awal banyak akun sekaligus dalam 1 jurnal (batch) dan bisa di-VOID.</div>
    </div>

    <div class="actions">
      {{-- ganti route ini kalau kamu punya create khusus batch --}}
      <a href="{{ route('accounting.opening-balances-batch.create') }}" class="btn btn-primary btn-sm">
        + Tambah
      </a>
      <a href="{{ route('accounting.journals.index') }}" class="btn btn-outline-secondary btn-sm">
        Semua Jurnal
      </a>
    </div>
  </div>

  {{-- FLASH --}}
  @if (session('message'))
    <div class="alert alert-{{ session('status') === 'ok' ? 'success' : 'danger' }} mb-3">
      {{ session('message') }}
    </div>
  @endif

  {{-- FILTERS --}}
  <form method="GET" class="cardx mb-3">
    <div class="p-3">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label mb-1 small text-muted">Dari Tanggal</label>
          <input type="date" name="from" class="form-control" value="{{ request('from') }}">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label mb-1 small text-muted">Sampai</label>
          <input type="date" name="to" class="form-control" value="{{ request('to') }}">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label mb-1 small text-muted">Status</label>
          <select name="status" class="form-select">
            <option value="" @selected(request('status') === null || request('status') === '')>Semua</option>
            <option value="active" @selected(request('status') === 'active')>Aktif</option>
            <option value="void" @selected(request('status') === 'void')>Void</option>
          </select>
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
          <button class="btn btn-outline-secondary w-100">Filter</button>
          <a href="{{ route('accounting.opening-balances-batch.index') }}" class="btn btn-light w-100">
            Reset
          </a>
        </div>
      </div>
    </div>
  </form>

  {{-- RINGKASAN --}}
  <div class="stat-grid">
    <div class="stat">
      <div class="k">Total data (halaman ini)</div>
      <div class="v">{{ $journals->count() }}</div>
      <div class="s">Baris jurnal yang tampil</div>
    </div>
    <div class="stat">
      <div class="k">Aktif</div>
      <div class="v">{{ $activeCount }}</div>
      <div class="s">voided_at kosong</div>
    </div>
    <div class="stat">
      <div class="k">Void</div>
      <div class="v">{{ $voidCount }}</div>
      <div class="s">voided_at terisi</div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="cardx">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th style="width:140px;">Tanggal</th>
            <th>Jurnal Batch</th>
            <th style="width:240px;">Ringkasan</th>
            <th class="text-end" style="width:190px;">Nominal (Debit)</th>
            <th class="text-end" style="width:220px;">Aksi</th>
          </tr>
        </thead>

        <tbody>
          @forelse ($journals as $j)
            @php
              $isVoided = !is_null($j->voided_at);

              // batch: pakai SUM(debit) sebagai "total dana yang di-set"
              $sumDebit = (float) ($j->lines?->sum('debit') ?? 0);

              // quick summary: berapa akun kena
              $accountsTouched = (int) ($j->lines?->count() ?? 0);

              // tampilkan 1-2 akun contoh
              $sampleAccounts = $j->lines
                  ->take(2)
                  ->map(fn($l) => $l->account?->name ?: ('Akun#'.$l->account_id))
                  ->filter()
                  ->values()
                  ->all();
            @endphp

            <tr class="{{ $isVoided ? 'table-secondary' : '' }}">
              <td class="text-nowrap" data-label="Tanggal">
                <div class="mono">{{ \Illuminate\Support\Carbon::parse($j->date)->format('Y-m-d') }}</div>
              </td>

              <td data-label="Jurnal">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <a href="{{ route('accounting.journals.show', $j) }}" class="text-decoration-none row-title">
                    {{ $j->description ?: 'Saldo Awal Batch' }}
                  </a>

                  @if ($isVoided)
                    <span class="badge-soft badge-void">VOID</span>
                  @else
                    <span class="badge-soft badge-ok">AKTIF</span>
                  @endif
                </div>

                <div class="row-sub">
                  <span class="mono">{{ $j->source_type }}</span>
                  @if ($j->posted_at)
                    <span class="mx-1">•</span>
                    Posted: {{ \Illuminate\Support\Carbon::parse($j->posted_at)->format('Y-m-d H:i') }}
                  @endif
                  @if ($isVoided)
                    <span class="mx-1">•</span>
                    Void: {{ \Illuminate\Support\Carbon::parse($j->voided_at)->format('Y-m-d H:i') }}
                  @endif
                </div>
              </td>

              <td data-label="Ringkasan">
                <div class="stack">
                  <div class="fw-semibold">{{ $accountsTouched }} akun</div>
                  <div class="row-sub">
                    @if (count($sampleAccounts))
                      {{ implode(', ', $sampleAccounts) }}@if($accountsTouched > 2)…@endif
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </div>
                </div>
              </td>

              <td class="text-end" data-label="Nominal">
                <div class="amount mono">{{ $fmtMoney($sumDebit) }}</div>
                <div class="row-sub">Rupiah</div>
              </td>

              <td class="text-end" data-label="Aksi">
                <div class="d-inline-flex gap-2 flex-wrap justify-content-end">
                  <a href="{{ route('accounting.journals.show', $j) }}" class="btn btn-sm btn-outline-primary">
                    Detail
                  </a>

                  @if (!$isVoided)
                    {{-- GANTI route ini kalau nama route void batch kamu berbeda --}}
                    <form method="POST"
                      action="{{ route('accounting.opening-balances-batch.void', $j) }}"
                      class="d-inline"
                      onsubmit="return confirm('Void saldo awal batch ini? Sistem akan membuat jurnal pembalik (reversal).')">
                      @csrf
                      <input type="hidden" name="reason" value="Manual void">
                      <button class="btn btn-sm btn-outline-danger">
                        Void
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>

          @empty
            <tr>
              <td colspan="5" class="text-center text-muted py-4">
                Belum ada data saldo awal batch.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $journals->links() }}
  </div>

</div>
@endsection
