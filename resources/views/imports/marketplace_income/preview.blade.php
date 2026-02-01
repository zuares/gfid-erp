@extends('layouts.app')
@section('title','Preview • Marketplace Income')

@push('head')
<style>
  .page{ max-width:1200px; margin:0 auto; padding:.9rem .85rem 5rem; }
  .cardx{ background:var(--card,#fff); border:1px solid rgba(148,163,184,.22); border-radius:16px; box-shadow:0 10px 24px rgba(15,23,42,.06); }
  .pad{ padding:1rem; }
  .muted{ color:rgba(100,116,139,1); }
  .mono{ font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; }
  .chip{ display:inline-flex; align-items:center; gap:.35rem; padding:.22rem .55rem; border-radius:999px; border:1px solid rgba(148,163,184,.30); background:rgba(148,163,184,.08); font-size:.82rem; }
  .tbl{ width:100%; }
  .tbl th,.tbl td{ padding:.5rem .55rem; border-bottom:1px solid rgba(148,163,184,.18); vertical-align:top; }
  .show-sm{ display:none; }
  @media(max-width:820px){
    .hide-sm{ display:none; }
    .show-sm{ display:table-cell; }
  }
  .kpi{ font-size:1.15rem; font-weight:800; }
</style>
@endpush

@section('content')
@php
  $stats = $stats ?? [];
  $sample = $sample ?? [];

  $money = function ($n) {
    return 'Rp ' . number_format((float)($n ?? 0), 0, ',', '.');
  };
@endphp

<div class="page">
  <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1">Preview Import • Income</h1>
      <div class="muted small">
        <span class="chip">channel <span class="mono">{{ $channel }}</span></span>
        <span class="chip">store_id <span class="mono">{{ $store_id }}</span></span>
        <span class="chip">file <span class="mono">{{ $source_file }}</span></span>
      </div>
    </div>
    <div class="d-flex gap-2">
      <form method="POST" action="{{ route('imports.marketplace_income.cancel') }}">
        @csrf
        <button class="btn btn-outline-danger btn-sm" type="submit">Batal</button>
      </form>
      <form method="POST" action="{{ route('imports.marketplace_income.commit') }}">
        @csrf
        <button class="btn btn-primary btn-sm" type="submit">Commit Income</button>
      </form>
    </div>
  </div>

  {{-- SUMMARY --}}
  <div class="cardx pad mb-3">
    <div class="row g-2">
      <div class="col-md-3">
        <div class="muted small">Rows parsed</div>
        <div class="kpi">{{ (int)($stats['rows_parsed'] ?? 0) }}</div>
      </div>
      <div class="col-md-3">
        <div class="muted small">Orders parsed</div>
        <div class="kpi">{{ (int)($stats['orders_parsed'] ?? 0) }}</div>
      </div>
      <div class="col-md-3">
        <div class="muted small">Matched shipments</div>
        <div class="kpi">{{ (int)($stats['orders_matched_shipments'] ?? 0) }}</div>
        <div class="muted small">Unmatched {{ (int)($stats['orders_unmatched_shipments'] ?? 0) }}</div>
      </div>
      <div class="col-md-3">
        <div class="muted small">Shipments updated</div>
        <div class="kpi">{{ (int)($stats['shipments_updated'] ?? 0) }}</div>
      </div>
    </div>

    <div class="row g-2 mt-2">
      <div class="col-md-6">
        <div class="muted small">Batch</div>
        <div class="mono small">{{ $stats['batch'] ?? '-' }}</div>
      </div>
      <div class="col-md-3">
        <div class="muted small">Rows skipped</div>
        <div class="h6 mb-0">{{ (int)($stats['rows_skipped'] ?? 0) }}</div>
      </div>
      <div class="col-md-3">
        <div class="muted small">Dry run</div>
        <div class="h6 mb-0">{{ !empty($stats['dry_run']) ? 'YES' : 'NO' }}</div>
      </div>
    </div>

    <div class="muted small mt-2">
      Ditampilkan sample maksimal 5 order. Commit akan upsert ke <span class="mono">mp_incomes</span> dan apply snapshot ke <span class="mono">mp_shipments</span> yang match.
    </div>
  </div>

  {{-- SAMPLE TABLE --}}
  <div class="cardx">
    <div class="pad">
      <div class="fw-semibold mb-2">Sample Income (per Order)</div>

      <div class="table-responsive">
        <table class="tbl">
          <thead>
            <tr class="muted small">
              <th>Order ID</th>
              <th class="hide-sm">Released at</th>
              <th class="hide-sm text-end">Fee</th>
              <th class="hide-sm text-end">Refund</th>
              <th class="hide-sm text-end">Net</th>
              <th class="show-sm">Ringkas</th>
            </tr>
          </thead>
          <tbody>
            @forelse($sample as $r)
              @php
                $oid = $r['platform_order_id'] ?? '-';
                $releasedAt = $r['released_at'] ?? null;
                $fee = (float)($r['platform_fee_total'] ?? 0);
                $refund = (float)($r['refund_total'] ?? 0);
                $net = (float)($r['net_payout_actual'] ?? 0);
              @endphp
              <tr>
                <td class="mono">{{ $oid }}</td>
                <td class="hide-sm">{{ $releasedAt ? $releasedAt : '-' }}</td>
                <td class="hide-sm text-end">{{ $money($fee) }}</td>
                <td class="hide-sm text-end">{{ $money($refund) }}</td>
                <td class="hide-sm text-end fw-semibold">{{ $money($net) }}</td>

                <td class="show-sm">
                  <div class="mono">{{ $oid }}</div>
                  <div class="muted small">
                    {{ $money($net) }} • fee {{ $money($fee) }}
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="muted">Tidak ada sample.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection
