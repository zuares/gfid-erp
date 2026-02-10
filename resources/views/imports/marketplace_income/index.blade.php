{{-- resources/views/imports/marketplace_income/index.blade.php --}}
@extends('layouts.app')
@section('title','Imports • Marketplace Income')

@php
  $stores  = $stores ?? collect();
  $batches = $batches ?? null;

  $channel  = $channel ?? '';
  $storeId  = (int)($storeId ?? 0);
  $q        = $q ?? '';
  $dateFrom = $dateFrom ?? '';
  $dateTo   = $dateTo ?? '';

  $filterActive = ($channel !== '') || ($storeId > 0) || (trim((string)$q) !== '') || ($dateFrom !== '') || ($dateTo !== '');
@endphp

@push('head')
<style>
  .page{ max-width:1220px; margin:0 auto; padding: 1rem .9rem 4.8rem; }
  .page > .d-flex{ position:relative; z-index:20; }
  @media(min-width:768px){ .page{ padding: 1.1rem 1rem 4.8rem; } }

  :root{
    --line: rgba(148,163,184,.18);
    --line2: rgba(148,163,184,.22);
    --ink: rgba(15,23,42,.92);
    --muted: rgba(100,116,139,1);
    --soft: rgba(148,163,184,.06);
    --soft2: rgba(148,163,184,.10);
    --shadow: 0 10px 24px rgba(15,23,42,.05);
  }
  body[data-theme="dark"]{
    --ink: rgba(226,232,240,.92);
    --muted: rgba(148,163,184,.85);
    --line: rgba(148,163,184,.14);
    --line2: rgba(148,163,184,.18);
    --soft: rgba(148,163,184,.08);
    --soft2: rgba(148,163,184,.12);
    --shadow: 0 12px 28px rgba(0,0,0,.35);
  }

  .cardx{ border:1px solid var(--line); border-radius:14px; background:var(--card,#fff); box-shadow:var(--shadow); }
  .chip{ display:inline-flex; align-items:center; gap:.35rem; padding:.18rem .55rem; border-radius:999px; font-size:.78rem; border:1px solid var(--line2); background:var(--soft); white-space:nowrap; }
  .mono{ font-family: ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace; }
  .muted{ color: var(--muted); }

  .filter-head{ display:flex; justify-content:space-between; align-items:center; gap:.75rem; margin-bottom:.55rem; }
  .filter-grid{ display:grid; grid-template-columns: 2fr 1fr 1fr 1.2fr; gap:.65rem; align-items:end; }
  @media(max-width:992px){ .filter-grid{ grid-template-columns: 1fr 1fr; } }
  @media(max-width:520px){ .filter-grid{ grid-template-columns: 1fr; } }
  .filter-grid .form-label{ font-size:.78rem; margin-bottom:.25rem; color: var(--muted); }

  .tbl{ width:100%; border-collapse:separate; border-spacing:0; }
  .tbl th,.tbl td{ padding:.6rem .65rem; border-bottom:1px solid var(--line); vertical-align:top; }
  .tbl th{ font-size:.82rem; color: var(--muted); font-weight:800; }
  .tbl tr.rowlink{ cursor:pointer; }
  .tbl tr.rowlink:hover td{ background: var(--soft2); }
  .link{ color: inherit; text-decoration:none; }
  .link:hover{ text-decoration:underline; }

  .show-sm{ display:none; }
  .hide-sm{ display:table-cell; }
  @media(max-width:820px){
    thead{ display:none; }
    .hide-sm{ display:none; }
    .show-sm{ display:block; }
    tbody td{ display:block; border-bottom:none; padding:.75rem .85rem; }
    tbody tr{ display:block; border-bottom:1px solid rgba(148,163,184,.14); }
    body[data-theme="dark"] tbody tr{ border-bottom:1px solid rgba(148,163,184,.10); }
    .mrow{ display:flex; justify-content:space-between; gap:.75rem; }
    .mright{ text-align:right; white-space:nowrap; }
  }

  .head-actions .btn{ white-space:nowrap; border-radius:12px; }
</style>
@endpush

@section('content')
@php
  $money = function ($n) {
    $v = (int) round((float)($n ?? 0));
    return 'Rp ' . number_format($v, 0, ',', '.');
  };
@endphp

<div class="page">

  {{-- Alerts --}}
  @if(session('error')) <div class="alert alert-danger mb-3">{{ session('error') }}</div> @endif
  @if(session('success')) <div class="alert alert-success mb-3">{{ session('success') }}</div> @endif

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1 fw-bold">Marketplace Income</h1>
      <div class="text-muted small d-flex flex-wrap gap-1 align-items-center">
        <span class="chip">group by <span class="mono">import_batch_id</span></span>
        @if($filterActive)
          <span class="chip" style="border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.10);">
            Filter aktif
          </span>
        @endif
      </div>
    </div>

    <div class="d-flex gap-2 align-items-center head-actions">
      <a class="btn btn-outline-secondary btn-sm px-3" href="{{ route('imports.marketplace_income.index') }}">Refresh</a>
      <a class="btn btn-success btn-sm px-3" href="{{ route('imports.marketplace_income.create') }}">+ Import</a>
    </div>
  </div>

  {{-- FILTER --}}
  <form method="GET" action="{{ route('imports.marketplace_income.index') }}" class="cardx p-3 mb-3">
    <div class="filter-head">
      <div class="fw-bold">Filter</div>
      <a href="{{ route('imports.marketplace_income.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
    </div>

    <div class="filter-grid">
      <div>
        <label class="form-label">Cari Order ID</label>
        <input class="form-control" name="q" value="{{ $q }}" placeholder="mis: 2401... / 2602...">
      </div>

      <div>
        <label class="form-label">Channel</label>
        <select class="form-select" name="channel">
          <option value="">Semua</option>
          <option value="shopee" @selected($channel==='shopee')>Shopee</option>
          <option value="tiktok" @selected($channel==='tiktok')>TikTok</option>
        </select>
      </div>

      <div>
        <label class="form-label">Store</label>
        <select class="form-select" name="store_id">
          <option value="0">Semua</option>
          @foreach($stores as $st)
            <option value="{{ $st->id }}" @selected((int)$storeId === (int)$st->id)>{{ $st->name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="form-label">Released Date</label>
        <div class="d-flex gap-2">
          <input type="date" class="form-control" name="date_from" value="{{ $dateFrom }}" title="from">
          <input type="date" class="form-control" name="date_to" value="{{ $dateTo }}" title="to">
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 mt-3">
      <button class="btn btn-outline-primary btn-sm px-3" type="submit">Apply</button>
      <a class="btn btn-outline-secondary btn-sm px-3" href="{{ route('imports.marketplace_income.create') }}">Import Baru</a>
    </div>
  </form>

  {{-- TABLE --}}
  <div class="cardx">
    <div class="p-3 d-flex justify-content-between align-items-center">
      <div class="fw-bold">Batches</div>
      <div class="text-muted small">Total: {{ $batches?->total() ?? 0 }}</div>
    </div>

    <div class="table-responsive">
      <table class="tbl">
        <thead>
          <tr>
            <th style="width:28%">Batch</th>
            <th style="width:16%">Channel / Store</th>
            <th style="width:12%">Imported</th>
            <th style="width:10%">Released</th>
            <th class="text-end" style="width:10%">Orders</th>
            <th class="text-end" style="width:12%">Net</th>
            <th class="text-end hide-sm" style="width:12%">Fees</th>
            <th class="text-end hide-sm" style="width:12%">Refund</th>
          </tr>
        </thead>
        <tbody>
          @forelse($batches as $b)
            @php
              $bid = $b->import_batch_id ?? '-';
              $ch = $b->channel ?? '-';
              $sid = (int)($b->store_id ?? 0);
              $importedAt = $b->imported_at_max ?? null;
              $releasedMax = $b->released_date_max ?? null;
              $ordersCount = (int)($b->orders_count ?? 0);
              $netSum = $b->net_sum ?? 0;
              $feeSum = $b->fee_sum ?? 0;
              $refundSum = $b->refund_sum ?? 0;
              $storeName = (string) optional($stores->firstWhere('id', $sid))->name;
              $source = $b->source_file_max ?? '-';
              $showUrl = route('imports.marketplace_income.show', ['batch' => $bid]);
            @endphp

            <tr class="rowlink" onclick="window.location='{{ $showUrl }}'">
              {{-- mobile --}}
              <td class="show-sm">
                <div class="mrow">
                  <div>
                    <div class="fw-bold mono"><a class="link" href="{{ $showUrl }}" onclick="event.stopPropagation()">{{ $bid }}</a></div>
                    <div class="mt-1 d-flex flex-wrap gap-1">
                      <span class="chip">{{ $ch }}</span>
                      <span class="chip">store <span class="mono">{{ $sid }}</span>@if($storeName) • {{ $storeName }}@endif</span>
                    </div>
                    <div class="text-muted small mt-1 mono">{{ $source }}</div>
                    <div class="text-muted small mt-1">
                      imp: {{ $importedAt ? \Illuminate\Support\Carbon::parse($importedAt)->format('Y-m-d H:i') : '-' }}
                      • rel: <span class="mono">{{ $releasedMax ?: '-' }}</span>
                    </div>
                  </div>
                  <div class="mright">
                    <div class="fw-bold">{{ $money($netSum) }}</div>
                    <div class="text-muted small">orders {{ number_format($ordersCount) }}</div>
                  </div>
                </div>
              </td>

              {{-- desktop --}}
              <td class="hide-sm">
                <div class="fw-bold mono"><a class="link" href="{{ $showUrl }}" onclick="event.stopPropagation()">{{ $bid }}</a></div>
                <div class="text-muted small mono">{{ $source }}</div>
              </td>

              <td class="hide-sm">
                <div class="chip">{{ $ch }}</div>
                <div class="text-muted small">
                  store: <span class="mono">{{ $sid }}</span>
                  @if($storeName) • {{ $storeName }}@endif
                </div>
              </td>

              <td class="hide-sm text-muted small">
                {{ $importedAt ? \Illuminate\Support\Carbon::parse($importedAt)->format('Y-m-d H:i') : '-' }}
              </td>

              <td class="hide-sm text-muted small mono">{{ $releasedMax ?: '-' }}</td>
              <td class="hide-sm text-end mono">{{ number_format($ordersCount) }}</td>
              <td class="hide-sm text-end fw-bold">{{ $money($netSum) }}</td>
              <td class="hide-sm text-end">{{ $money($feeSum) }}</td>
              <td class="hide-sm text-end">{{ $money($refundSum) }}</td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-muted p-3">Belum ada data import income.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-3">
      {{ $batches->links() }}
    </div>
  </div>
</div>
@endsection
