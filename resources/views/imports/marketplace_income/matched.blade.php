{{-- resources/views/imports/marketplace_income/matched.blade.php --}}
@extends('layouts.app')
@section('title','Matched • Marketplace Income')

@php
  $stores = $stores ?? collect();
  $items = $items ?? null;

  $q = (string)($q ?? '');
  $channel = (string)($channel ?? '');
  $storeId = (int)($storeId ?? 0);

  $ordersMatched = (int)($ordersMatched ?? 0);
  $multiShipOrders = (int)($multiShipOrders ?? 0);

  $audit = $audit ?? (object)[
    'net_sum' => 0,
    'fee_sum' => 0,
    'refund_sum' => 0,
    'net_negative_count' => 0,
    'refund_orders_count' => 0,
  ];

  $money = fn($n) => 'Rp ' . number_format((int)round((float)($n ?? 0)), 0, ',', '.');

  $filterActive = ($channel !== '') || ($storeId > 0) || (trim($q) !== '');

  // raw helpers (same as show)
  $safeRaw = function ($it) {
    $raw = $it->raw_payload ?? null;
    if (is_array($raw)) return $raw;
    if (is_string($raw) && $raw !== '') {
      $decoded = json_decode($raw, true);
      return is_array($decoded) ? $decoded : [];
    }
    return [];
  };

  $hint = function ($it) use ($safeRaw) {
    $raw = $safeRaw($it);

    $hint = $raw['income']['hint'] ?? null;
    if (is_array($hint)) {
      return [
        'buyer' => !empty($hint['buyer']) ? (string)$hint['buyer'] : null,
        'pay' => !empty($hint['pay']) ? (string)$hint['pay'] : null,
        'courier' => !empty($hint['courier']) ? (string)$hint['courier'] : null,
      ];
    }

    $row0 = $raw['income']['raw'][0] ?? [];
    if (!is_array($row0)) $row0 = [];

    return [
      'buyer' => $row0['Username (Pembeli)'] ?? $row0['Pembeli'] ?? $row0['Buyer'] ?? null,
      'pay' => $row0['Metode pembayaran pembeli'] ?? $row0['Metode Pembayaran'] ?? $row0['Payment Method'] ?? null,
      'courier' => $row0['Nama Kurir'] ?? $row0['Kurir'] ?? $row0['Courier'] ?? ($row0['Jasa Kirim'] ?? null),
    ];
  };

  $flags = function ($it) {
    $net = (float) ($it->net_payout_actual ?? 0);
    $refund = (float) ($it->refund_total ?? 0);
    $fee = (float) ($it->platform_fee_total ?? 0);

    return [
      'net_negative' => $net < 0,
      'has_refund' => $refund != 0,
      'fee_high' => $fee >= 20000,
    ];
  };

  $showUrl = route('imports.marketplace_income.show', ['batch' => $batch, 'channel' => $channel, 'store_id' => $storeId, 'q' => $q]);
  $unmatchedUrl = route('imports.marketplace_income.unmatched', ['batch' => $batch, 'channel' => $channel, 'store_id' => $storeId, 'q' => $q]);
  $matchedUrl = route('imports.marketplace_income.matched', ['batch' => $batch, 'channel' => $channel, 'store_id' => $storeId, 'q' => $q]);
@endphp

@push('head')
<style>
  .page{ max-width:1220px; margin:0 auto; padding: 1rem .9rem 4.8rem; }
  @media(min-width:768px){ .page{ padding: 1.1rem 1rem 4.8rem; } }

  :root{
    --line: rgba(148,163,184,.18);
    --line2: rgba(148,163,184,.22);
    --ink: rgba(15,23,42,.92);
    --muted: rgba(100,116,139,1);
    --soft: rgba(148,163,184,.06);
    --shadow: 0 10px 24px rgba(15,23,42,.05);
  }
  body[data-theme="dark"]{
    --ink: rgba(226,232,240,.92);
    --muted: rgba(148,163,184,.85);
    --line: rgba(148,163,184,.14);
    --line2: rgba(148,163,184,.18);
    --soft: rgba(148,163,184,.08);
    --shadow: 0 12px 28px rgba(0,0,0,.35);
  }

  .cardx{ border:1px solid var(--line); border-radius:14px; background:var(--card,#fff); box-shadow:var(--shadow); }
  .chip{ display:inline-flex; align-items:center; gap:.35rem; padding:.18rem .55rem; border-radius:999px; font-size:.78rem; border:1px solid var(--line2); background:var(--soft); white-space:nowrap; }
  .mono{ font-family: ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace; }
  .muted{ color: var(--muted); }
  .kpi{ font-weight:900; letter-spacing:.01em; color: var(--ink); font-size:1.05rem; }
  .sep{ height:1px; background: var(--line); margin:.75rem 0; }

  .tabs{ display:flex; flex-wrap:wrap; gap:.4rem; }
  .tabx{ display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .7rem; border-radius:999px; border:1px solid var(--line2); background:var(--soft); color:inherit; text-decoration:none; font-weight:800; font-size:.8rem; }
  .tabx.active{ background: rgba(15,23,42,.92); color:#fff; border-color: rgba(15,23,42,.92); }
  body[data-theme="dark"] .tabx.active{ background: rgba(226,232,240,.92); color:#0b1220; border-color: rgba(226,232,240,.92); }

  .kpi-grid{ display:grid; grid-template-columns: repeat(5, 1fr); gap:.6rem; }
  @media(max-width:980px){ .kpi-grid{ grid-template-columns: repeat(3, 1fr); } }
  @media(max-width:560px){ .kpi-grid{ grid-template-columns: repeat(2, 1fr); } }
  .kpi-card{ padding:.75rem .85rem; box-shadow:none; }
  .kpi-label{ font-size:.76rem; color: var(--muted); font-weight:800; }

  .filter-grid{ display:grid; grid-template-columns: 2fr 1fr 1fr auto; gap:.65rem; align-items:end; }
  @media(max-width:992px){ .filter-grid{ grid-template-columns: 1fr 1fr; } }
  @media(max-width:520px){ .filter-grid{ grid-template-columns: 1fr; } }
  .filter-grid .form-label{ font-size:.78rem; margin-bottom:.25rem; color: var(--muted); }

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

  .badge-warn{ border-color:rgba(245,158,11,.35) !important; background:rgba(245,158,11,.10) !important; }
  .badge-danger{ border-color:rgba(239,68,68,.35) !important; background:rgba(239,68,68,.10) !important; }
  .badge-info{ border-color:rgba(59,130,246,.35) !important; background:rgba(59,130,246,.10) !important; }
  .row-danger{ background: rgba(239,68,68,.05); }
  .hintline{ display:flex; gap:.5rem; align-items:center; }
  .hintline .ico{ width:18px; text-align:center; opacity:.9; }
   .head-actions .btn{
    border-radius:5px !important;
    padding: .42rem .82rem !important;
    line-height: 1.05rem;
  }

  /* optional: unify focus ring feel */
  .head-actions .btn:focus{
    box-shadow: 0 0 0 .2rem rgba(99,102,241,.18);
  }
</style>
@endpush

@section('content')
<div class="page">

  @if(session('error')) <div class="alert alert-danger mb-3">{{ session('error') }}</div> @endif
  @if(session('success')) <div class="alert alert-success mb-3">{{ session('success') }}</div> @endif

  {{-- HEADER + TABS --}}
  <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1 fw-bold">Income</h1>
      <div class="text-muted small d-flex flex-wrap gap-1 align-items-center">
        <span class="chip">batch <span class="mono">{{ $batch }}</span></span>
        <span class="chip badge-info">matched</span>
        @if($filterActive) <span class="chip badge-warn">filtered</span> @endif
      </div>

      <div class="tabs mt-2">
        <a class="tabx" href="{{ $showUrl }}">All</a>
        <a class="tabx active" href="{{ $matchedUrl }}">Matched</a>
        <a class="tabx" href="{{ $unmatchedUrl }}">Unmatched</a>
      </div>
    </div>

    <div class="d-flex gap-2 align-items-center">
      <a class="btn btn-outline-secondary btn-sm px-3" href="{{ route('imports.marketplace_income.index') }}">← Index</a>

      <form method="POST"
            action="{{ route('imports.marketplace_income.apply', ['batch' => $batch]) }}"
            onsubmit="return confirm('Re-Apply income batch ini ke mp_shipments?')">
        @csrf
        <input type="hidden" name="channel" value="{{ $channel }}">
        <input type="hidden" name="store_id" value="{{ (int)$storeId }}">
        <button class="btn btn-primary btn-sm px-3" type="submit">Re-Apply</button>
      </form>

      <a class="btn btn-success btn-sm px-3" href="{{ route('imports.marketplace_income.create') }}">+ Import</a>
    </div>
  </div>

  {{-- KPI (minimal) --}}
  <div class="cardx p-3 mb-3">
    <div class="kpi-grid">
      <div class="cardx kpi-card">
        <div class="kpi-label">Orders</div>
        <div class="kpi">{{ number_format($ordersMatched) }}</div>
      </div>
      <div class="cardx kpi-card">
        <div class="kpi-label">Net</div>
        <div class="kpi">{{ $money($audit->net_sum ?? 0) }}</div>
      </div>
      <div class="cardx kpi-card">
        <div class="kpi-label">Fee</div>
        <div class="kpi">{{ $money($audit->fee_sum ?? 0) }}</div>
      </div>
      <div class="cardx kpi-card">
        <div class="kpi-label">Refund</div>
        <div class="kpi">{{ $money($audit->refund_sum ?? 0) }}</div>
      </div>
      <div class="cardx kpi-card">
        <div class="kpi-label">Multi-ship</div>
        <div class="kpi">{{ number_format($multiShipOrders) }}</div>
      </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-2">
      <span class="chip badge-danger">neg {{ number_format((int)($audit->net_negative_count ?? 0)) }}</span>
      <span class="chip badge-warn">refund {{ number_format((int)($audit->refund_orders_count ?? 0)) }}</span>
    </div>
  </div>

  {{-- FILTER (minimal) --}}
  <form method="GET" action="{{ route('imports.marketplace_income.matched', ['batch' => $batch]) }}" class="cardx p-3 mb-3">
    <div class="filter-grid">
      <div>
        <label class="form-label">Order</label>
        <input class="form-control" name="q" value="{{ $q }}" placeholder="platform_order_id...">
      </div>

      <div>
        <label class="form-label">Channel</label>
        <select class="form-select" name="channel">
          <option value="">All</option>
          <option value="shopee" @selected($channel==='shopee')>Shopee</option>
          <option value="tiktok" @selected($channel==='tiktok')>TikTok</option>
        </select>
      </div>

      <div>
        <label class="form-label">Store</label>
        <select class="form-select" name="store_id">
          <option value="0">All</option>
          @foreach($stores as $st)
            <option value="{{ $st->id }}" @selected((int)$storeId === (int)$st->id)>{{ $st->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm px-3" type="submit" style="height:40px;">Apply</button>
        <a class="btn btn-outline-secondary btn-sm px-3" style="height:40px;" href="{{ route('imports.marketplace_income.matched', ['batch' => $batch]) }}">Reset</a>
      </div>
    </div>
  </form>

  {{-- TABLE --}}
  <div class="cardx">
    <div class="p-3 d-flex justify-content-between align-items-center">
      <div class="fw-bold">Matched Orders</div>
      <div class="text-muted small">Total: {{ $items?->total() ?? 0 }}</div>
    </div>

    <div class="table-responsive">
      <table class="tbl">
        <thead>
          <tr>
            <th style="width:28%">Order</th>
            <th style="width:18%">Hint</th>
            <th style="width:14%">Released</th>
            <th style="width:12%">MP Ship</th>
            <th class="text-end" style="width:10%">Net</th>
            <th class="text-end" style="width:9%">Fee</th>
            <th class="text-end" style="width:9%">Refund</th>
          </tr>
        </thead>
        <tbody>
          @forelse($items as $it)
            @php
              $sid = (int)($it->store_id ?? 0);
              $storeName = (string) optional($stores->firstWhere('id', $sid))->name;

              $h = $hint($it);
              $f = $flags($it);
              $mpShipId = $it->mp_shipment_id ?? null;
            @endphp

            <tr @class(['row-danger' => $f['net_negative']])>

              {{-- mobile --}}
              <td class="show-sm">
                <div class="mrow">
                  <div>
                    <div class="fw-bold mono">{{ $it->platform_order_id }}</div>
                    <div class="mt-1 d-flex flex-wrap gap-1">
                      <span class="chip">{{ $it->channel }}</span>
                      <span class="chip">store <span class="mono">{{ $sid }}</span>@if($storeName) • {{ $storeName }}@endif</span>
                      @if($mpShipId) <span class="chip badge-info">mp_ship <span class="mono">{{ $mpShipId }}</span></span> @endif
                      @if($f['net_negative']) <span class="chip badge-danger">⚠ net</span> @endif
                      @if($f['has_refund']) <span class="chip badge-warn">↩ rf</span> @endif
                      @if($f['fee_high']) <span class="chip badge-info">fee</span> @endif
                    </div>

                    <div class="text-muted small mt-2">
                      <div class="hintline"><span class="ico">👤</span><span class="mono">{{ $h['buyer'] ?? '-' }}</span></div>
                      <div class="hintline"><span class="ico">💳</span><span class="mono">{{ $h['pay'] ?? '-' }}</span></div>
                      <div class="hintline"><span class="ico">🚚</span><span class="mono">{{ $h['courier'] ?? '-' }}</span></div>
                    </div>

                    <div class="text-muted small mt-2">
                      <span class="mono">{{ $it->released_date ?: '-' }}</span>
                      @if($it->released_at) • <span class="mono">{{ $it->released_at->format('Y-m-d H:i') }}</span> @endif
                    </div>
                  </div>

                  <div class="mright">
                    <div class="fw-bold">{{ $money($it->net_payout_actual) }}</div>
                    <div class="text-muted small">fee {{ $money($it->platform_fee_total) }}</div>
                    <div class="text-muted small">rf {{ $money($it->refund_total) }}</div>
                  </div>
                </div>
              </td>

              {{-- desktop --}}
              <td class="hide-sm">
                <div class="fw-bold mono">{{ $it->platform_order_id }}</div>
                <div class="text-muted small d-flex flex-wrap gap-1 mt-1">
                  <span class="chip">{{ $it->channel }}</span>
                  <span class="chip">store <span class="mono">{{ $sid }}</span>@if($storeName) • {{ $storeName }}@endif</span>
                  @if($f['net_negative']) <span class="chip badge-danger">⚠</span> @endif
                  @if($f['has_refund']) <span class="chip badge-warn">↩</span> @endif
                  @if($f['fee_high']) <span class="chip badge-info">fee</span> @endif
                </div>
              </td>

              <td class="hide-sm">
                <div class="text-muted small">
                  <div class="hintline"><span class="ico">👤</span><span class="mono">{{ $h['buyer'] ?? '-' }}</span></div>
                  <div class="hintline"><span class="ico">💳</span><span class="mono">{{ $h['pay'] ?? '-' }}</span></div>
                  <div class="hintline"><span class="ico">🚚</span><span class="mono">{{ $h['courier'] ?? '-' }}</span></div>
                </div>
              </td>

              <td class="hide-sm">
                <div class="mono">{{ $it->released_date ?: '-' }}</div>
                <div class="text-muted small">{{ $it->released_at ? $it->released_at->format('Y-m-d H:i') : '-' }}</div>
              </td>

              <td class="hide-sm">
                <div class="mono">{{ $mpShipId ?? '-' }}</div>
              </td>

              <td class="hide-sm text-end fw-bold">{{ $money($it->net_payout_actual) }}</td>
              <td class="hide-sm text-end">{{ $money($it->platform_fee_total) }}</td>
              <td class="hide-sm text-end">{{ $money($it->refund_total) }}</td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-muted p-3">No data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-3">
      {{ $items->links() }}
    </div>
  </div>

</div>
@endsection
