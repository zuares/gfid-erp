{{-- resources/views/imports/marketplace_income/preview.blade.php --}}
@extends('layouts.app')
@section('title','Preview • Marketplace Income')

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

  .tbl{ width:100%; border-collapse:separate; border-spacing:0; }
  .tbl th,.tbl td{ padding:.6rem .65rem; border-bottom:1px solid var(--line); vertical-align:top; }
  .tbl th{ font-size:.82rem; color: var(--muted); font-weight:800; }

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

  .kpi{ font-weight:900; letter-spacing:.01em; color: var(--ink); font-size:1.05rem; }
  .sep{ height:1px; background: var(--line); margin:.75rem 0; }
  .head-actions .btn{ white-space:nowrap; border-radius:12px; }
</style>
@endpush

@section('content')
@php
  $stats = $stats ?? [];
  $sample = $sample ?? [];
  $draftId = (string)($draft_id ?? '');

  $money = function ($n) {
    $v = (int) round((float)($n ?? 0));
    return 'Rp ' . number_format($v, 0, ',', '.');
  };
@endphp

<div class="page">

  @if(session('error')) <div class="alert alert-danger mb-3">{{ session('error') }}</div> @endif
  @if(session('success')) <div class="alert alert-success mb-3">{{ session('success') }}</div> @endif

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1 fw-bold">Preview Import • Income</h1>
      <div class="text-muted small d-flex flex-wrap gap-1 align-items-center">
        <span class="chip">draft <span class="mono">{{ $draftId }}</span></span>
        <span class="chip">channel <span class="mono">{{ $channel }}</span></span>
        <span class="chip">store_id <span class="mono">{{ $store_id }}</span></span>
        <span class="chip">file <span class="mono">{{ $source_file }}</span></span>
      </div>
    </div>

    <div class="d-flex gap-2 align-items-center head-actions">
      <a class="btn btn-outline-secondary btn-sm px-3"
         href="{{ route('imports.marketplace_income.create', ['draft_id' => $draftId]) }}">
        ← Kembali
      </a>

      <form method="POST" action="{{ route('imports.marketplace_income.cancel') }}"
            onsubmit="return confirm('Batalkan draft dan hapus file upload?')">
        @csrf
        <input type="hidden" name="draft_id" value="{{ $draftId }}">
        <button class="btn btn-outline-danger btn-sm px-3" type="submit">Batal</button>
      </form>

      <form method="POST" action="{{ route('imports.marketplace_income.commit') }}"
            onsubmit="return confirm('Commit import income? Ini akan menulis ke database.')">
        @csrf
        <input type="hidden" name="draft_id" value="{{ $draftId }}">
        <button class="btn btn-primary btn-sm px-3" type="submit">Commit</button>
      </form>
    </div>
  </div>

  {{-- SUMMARY --}}
  <div class="cardx p-3 mb-3">
    <div class="fw-bold mb-2">Ringkasan</div>

    <div class="row g-2">
      <div class="col-6 col-md-3">
        <div class="muted small">Rows parsed</div>
        <div class="kpi">{{ (int)($stats['rows_parsed'] ?? 0) }}</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="muted small">Orders parsed</div>
        <div class="kpi">{{ (int)($stats['orders_parsed'] ?? 0) }}</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="muted small">Matched shipments</div>
        <div class="kpi">{{ (int)($stats['orders_matched_shipments'] ?? 0) }}</div>
        <div class="muted small">
          Unmatched {{ (int)($stats['orders_unmatched_shipments'] ?? 0) }}
          @if(!empty($stats['orders_with_multi_shipments']))
            • Multi-ship {{ (int)$stats['orders_with_multi_shipments'] }}
          @endif
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="muted small">Shipments updated</div>
        <div class="kpi">{{ (int)($stats['shipments_updated'] ?? 0) }}</div>
      </div>
    </div>

    <div class="sep"></div>

    <div class="row g-2">
      <div class="col-md-6">
        <div class="muted small">Batch</div>
        <div class="mono small">{{ $stats['batch'] ?? '-' }}</div>
        <div class="muted small mt-1">Stored path</div>
        <div class="mono small">{{ $stored_path ?? '-' }}</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="muted small">Rows skipped</div>
        <div class="kpi" style="font-size:1rem;">{{ (int)($stats['rows_skipped'] ?? 0) }}</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="muted small">Dry run</div>
        <div class="kpi" style="font-size:1rem;">{{ !empty($stats['dry_run']) ? 'YES' : 'NO' }}</div>
      </div>
    </div>

    <div class="muted small mt-2">
      Sample maksimal 5 order. Commit akan upsert ke <span class="mono">mp_incomes</span>
      dan apply snapshot ke <span class="mono">mp_shipments</span> yang match.
    </div>
  </div>

  {{-- SAMPLE --}}
  <div class="cardx">
    <div class="p-3 d-flex justify-content-between align-items-center">
      <div class="fw-bold">Sample Income (per Order)</div>
      <div class="text-muted small">max 5</div>
    </div>

    <div class="table-responsive">
      <table class="tbl">
        <thead>
          <tr>
            <th>Order</th>
            <th class="hide-sm">Released</th>
            <th class="hide-sm text-end">Fee</th>
            <th class="hide-sm text-end">Refund</th>
            <th class="hide-sm text-end">Net</th>
          </tr>
        </thead>
        <tbody>
          @forelse($sample as $r)
            @php
              $oid = $r['platform_order_id'] ?? '-';
              $releasedAt = $r['released_at'] ?? null;
              $fee = (int)($r['platform_fee_total'] ?? 0);
              $refund = (int)($r['refund_total'] ?? 0);
              $net = (int)($r['net_payout_actual'] ?? 0);
            @endphp

            <tr>
              {{-- mobile --}}
              <td class="show-sm">
                <div class="mrow">
                  <div>
                    <div class="fw-bold mono">{{ $oid }}</div>
                    <div class="text-muted small mt-1">{{ $releasedAt ?: '-' }}</div>
                    <div class="mt-1 d-flex flex-wrap gap-1">
                      <span class="chip">fee {{ $money($fee) }}</span>
                      <span class="chip">refund {{ $money($refund) }}</span>
                    </div>
                  </div>
                  <div class="mright">
                    <div class="fw-bold">{{ $money($net) }}</div>
                  </div>
                </div>
              </td>

              {{-- desktop --}}
              <td class="hide-sm mono">{{ $oid }}</td>
              <td class="hide-sm">{{ $releasedAt ?: '-' }}</td>
              <td class="hide-sm text-end">{{ $money($fee) }}</td>
              <td class="hide-sm text-end">{{ $money($refund) }}</td>
              <td class="hide-sm text-end fw-bold">{{ $money($net) }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-muted p-3">Tidak ada sample.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>

</div>
@endsection
