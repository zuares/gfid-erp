{{-- resources/views/imports/marketplace_income/orders.blade.php --}}
@extends('layouts.app')
@section('title','Marketplace Income')

@php
  use Illuminate\Support\Str;

  $fmt0 = fn($n) => number_format((float)($n ?? 0), 0, ',', '.');
  $money = fn($n) => 'Rp ' . $fmt0($n);

  $from = $dateFrom ?? request('date_from', '');
  $to = $dateTo ?? request('date_to', '');
  $only = $only ?? request('only', 'all');
  $storeMap = [];

  foreach (($stores ?? []) as $st) {
      $storeMap[(int) $st->id] = (string) $st->name;
  }

  $total = (int) ($totalOrders ?? 0);
  $matched = (int) ($matchedOrders ?? 0);
  $unmatched = (int) ($unmatchedOrders ?? max(0, $total - $matched));
  $matchRate = $total > 0 ? round(($matched / $total) * 100, 1) : 0;
  $netTotal = (float) ($audit->net_sum ?? 0);
  $feeTotal = (float) ($audit->fee_sum ?? 0);
  $refundTotal = (float) ($audit->refund_sum ?? 0);
  $refundOrders = (int) ($audit->refund_orders_count ?? 0);

  $tabUrl = function (string $tab) {
      return route('imports.marketplace_income.index', array_merge(request()->query(), ['only' => $tab]));
  };
@endphp

@push('head')
<style>
  .income-page {
    max-width: 1440px;
    margin: 0 auto;
    padding: 1rem .9rem 5rem;
    color: var(--income-ink);
  }

  :root {
    --income-ink: #0f172a;
    --income-muted: #64748b;
    --income-line: rgba(148,163,184,.18);
    --income-soft: rgba(148,163,184,.08);
    --income-card: #fff;
    --income-shadow: 0 14px 34px rgba(15,23,42,.06);
  }

  body[data-theme="dark"] {
    --income-ink: #e2e8f0;
    --income-muted: #94a3b8;
    --income-line: rgba(148,163,184,.16);
    --income-soft: rgba(148,163,184,.10);
    --income-card: rgba(15,23,42,.92);
    --income-shadow: 0 14px 34px rgba(0,0,0,.24);
  }

  .income-hero {
    position: relative;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
    overflow: hidden;
    margin-bottom: .9rem;
    padding: 1.15rem 1.2rem;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 14px 34px rgba(15,23,42,.06);
  }

  .income-hero > * { position: relative; z-index: 1; }

  .income-hero-title {
    margin: 0;
    color: #1f2937;
    font-size: 1.35rem;
    font-weight: 900;
    letter-spacing: -.04em;
  }

  .income-hero-sub {
    max-width: 48rem;
    margin-top: .25rem;
    color: #64748b;
    font-size: .82rem;
  }

  .income-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    margin-bottom: .35rem;
    color: #475569;
    font-size: .65rem;
    font-weight: 900;
    letter-spacing: .1em;
    text-transform: uppercase;
  }

  .income-badges,
  .income-actions,
  .income-tabs {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: .45rem;
  }

  .income-badges { margin-top: .8rem; }

  .income-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .33rem .62rem;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    background: #f8fafc;
    color: #475569;
    font-size: .7rem;
    font-weight: 800;
    white-space: nowrap;
  }

  .income-hero .btn {
    border-radius: 999px;
    font-weight: 800;
  }

  .income-hero .btn-outline-light {
    background: #fff;
    border-color: #cbd5e1;
    color: #334155;
  }

  .income-card {
    border: 1px solid var(--income-line);
    border-radius: 18px;
    background: var(--income-card);
    box-shadow: var(--income-shadow);
  }

  .income-tabs-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: .9rem;
    padding: .28rem;
    border: 1px solid var(--income-line);
    border-radius: 999px;
    background: var(--income-card);
    box-shadow: var(--income-shadow);
  }

  .income-tab {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .52rem .82rem;
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: var(--income-muted);
    font-size: .76rem;
    font-weight: 850;
    text-decoration: none;
  }

  .income-tab:hover,
  .income-tab.active {
    background: #0f172a;
    color: #fff;
  }

  body[data-theme="dark"] .income-tab:hover,
  body[data-theme="dark"] .income-tab.active {
    background: #2563eb;
  }

  .income-tab-meta {
    padding: 0 .8rem;
    color: var(--income-muted);
    font-size: .72rem;
    font-weight: 700;
  }

  .income-filter-head,
  .income-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    flex-wrap: wrap;
    padding: 1rem 1rem .75rem;
  }

  .income-card-title {
    margin: 0;
    color: var(--income-ink);
    font-size: .9rem;
    font-weight: 900;
  }

  .income-card-note {
    margin-top: .2rem;
    color: var(--income-muted);
    font-size: .72rem;
  }

  .income-filter-grid {
    display: grid;
    grid-template-columns: 1.55fr .85fr .95fr 1.25fr 1.25fr;
    gap: .65rem;
    padding: 0 1rem 1rem;
  }

  .income-field label {
    display: block;
    margin-bottom: .28rem;
    color: var(--income-muted);
    font-size: .68rem;
    font-weight: 850;
  }

  .income-field .form-control,
  .income-field .form-select {
    min-height: 40px;
    border-color: var(--income-line);
    border-radius: 11px;
    background: transparent;
    color: var(--income-ink);
    font-size: .78rem;
  }

  .income-field .form-control::placeholder { color: var(--income-muted); }

  .income-kpi-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: .7rem;
    margin: .9rem 0;
  }

  .income-kpi {
    position: relative;
    min-height: 132px;
    overflow: hidden;
    padding: .95rem 1rem;
    border: 1px solid var(--income-line);
    border-radius: 16px;
    background: var(--income-card);
    box-shadow: var(--income-shadow);
  }

  .income-kpi::before {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    content: '';
    background: var(--kpi-color, #2563eb);
  }

  .income-kpi-label {
    color: var(--income-muted);
    font-size: .65rem;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
  }

  .income-kpi-value {
    margin-top: .45rem;
    color: var(--income-ink);
    font-size: 1.25rem;
    font-weight: 950;
    letter-spacing: -.04em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .income-kpi-sub {
    margin-top: .35rem;
    color: var(--income-muted);
    font-size: .7rem;
    font-weight: 750;
  }

  .income-kpi.order { --kpi-color: #334155; }
  .income-kpi.net { --kpi-color: #16a34a; }
  .income-kpi.fee { --kpi-color: #f59e0b; }
  .income-kpi.refund { --kpi-color: #ef4444; }
  .income-kpi.match { --kpi-color: #2563eb; }

  .income-table-wrap {
    overflow-x: auto;
    border-top: 1px solid var(--income-line);
    position: relative;
    scrollbar-gutter: stable both-edges;
    overscroll-behavior: contain;
  }

  /* Desktop: keep the table header and horizontal scrollbar within reach. */
  .income-table-wrap.has-rows {
    height: clamp(240px, calc(100vh - 390px), 560px);
    overflow: auto;
  }

  .income-table thead,
  .income-table thead tr {
    position: sticky;
    top: 0;
    z-index: 20;
  }

  .income-table {
    width: 100%;
    min-width: 1380px;
    border-collapse: separate;
    border-spacing: 0;
  }

  .income-table th,
  .income-table td {
    padding: .78rem .85rem;
    border-bottom: 1px solid var(--income-line);
    vertical-align: top;
  }

  .income-table th {
    color: var(--income-muted);
    font-size: .65rem;
    font-weight: 900;
    letter-spacing: .07em;
    text-transform: uppercase;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 21;
    background: var(--income-card, #fff);
    box-shadow: 0 1px 0 var(--income-line), 0 5px 12px rgba(15, 23, 42, .06);
  }

  .income-table td {
    color: var(--income-ink);
    font-size: .77rem;
  }

  .income-table tbody tr:hover td { background: var(--income-soft); }

  .income-order-id {
    color: var(--income-ink);
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: .76rem;
    font-weight: 900;
  }

  .income-muted { color: var(--income-muted); }
  .income-number { white-space: nowrap; text-align: right; font-variant-numeric: tabular-nums; }
  .income-net { color: #16a34a; font-weight: 950; }
  .income-net.negative { color: #dc2626; }
  .income-refund { color: #b45309; font-weight: 800; }

  .income-status {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .26rem .5rem;
    border-radius: 999px;
    font-size: .65rem;
    font-weight: 900;
    white-space: nowrap;
  }

  .income-status.matched {
    border: 1px solid rgba(16,185,129,.2);
    background: rgba(16,185,129,.1);
    color: #059669;
  }

  .income-status.unmatched {
    border: 1px solid rgba(245,158,11,.22);
    background: rgba(245,158,11,.1);
    color: #b45309;
  }

  .income-detail-stack {
    display: flex;
    flex-direction: column;
    gap: .18rem;
  }

  .income-page-link {
    color: inherit;
    text-decoration: none;
  }

  .income-page-link:hover { text-decoration: underline; }

  .income-mobile-list { display: none; }

  .income-empty {
    padding: 2.5rem 1rem;
    color: var(--income-muted);
    text-align: center;
  }

  @media (max-width: 1180px) {
    .income-filter-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .income-kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  }

  @media (max-width: 820px) {
    .income-page { padding-inline: .65rem; }
    .income-hero { padding: 1rem; }
    .income-tabs-wrap { border-radius: 16px; }
    .income-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .income-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .income-table-wrap { display: none; }
    .income-mobile-list { display: block; }
  }

  @media (max-width: 520px) {
    .income-filter-grid,
    .income-kpi-grid { grid-template-columns: 1fr; }
    .income-actions { width: 100%; }
    .income-actions .btn { flex: 1 1 auto; }
    .income-tab { flex: 1 1 auto; justify-content: center; }
    .income-tab-meta { width: 100%; padding: .3rem .55rem .5rem; }
  }
</style>
@endpush

@section('content')
<div class="income-page">
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <section class="income-hero">
    <div>
      <div class="income-eyebrow"><i class="bi bi-cash-coin"></i> Marketplace income</div>
      <h1 class="income-hero-title">Rincian Income Import</h1>
      <div class="income-hero-sub">
        Dana cair, biaya platform, refund, dan status pencocokan shipment per order dari file marketplace.
      </div>
      <div class="income-badges">
        <span class="income-chip"><i class="bi bi-database"></i> mp_incomes</span>
        @if($channel ?? '')
          <span class="income-chip">{{ strtoupper($channel) }}</span>
        @endif
        @if((int)($storeId ?? 0) > 0)
          <span class="income-chip"><i class="bi bi-shop"></i> {{ $storeMap[(int)$storeId] ?? 'Store #'.$storeId }}</span>
        @endif
        @if($from || $to)
          <span class="income-chip"><i class="bi bi-calendar3"></i> {{ $from ?: 'awal' }} — {{ $to ?: 'akhir' }}</span>
        @endif
      </div>
    </div>

    <div class="income-actions">
      <a class="btn btn-sm btn-outline-light px-3" href="{{ route('imports.marketplace_income.index') }}">
        <i class="bi bi-arrow-clockwise"></i> Refresh
      </a>
      <a class="btn btn-sm btn-primary px-3" href="{{ route('imports.marketplace_income.create') }}">
        <i class="bi bi-upload"></i> Import Income
      </a>
      <a class="btn btn-sm btn-outline-primary px-3" href="{{ route('imports.marketplace.create') }}">
        <i class="bi bi-box-seam"></i> Import Order
      </a>
    </div>
  </section>

  <div class="income-tabs-wrap">
    <div class="income-tabs">
      <a class="income-tab {{ $only === 'all' ? 'active' : '' }}" href="{{ $tabUrl('all') }}">
        Semua <span class="badge rounded-pill text-bg-secondary">{{ $total }}</span>
      </a>
      <a class="income-tab {{ $only === 'matched' ? 'active' : '' }}" href="{{ $tabUrl('matched') }}">
        Matched <span class="badge rounded-pill text-bg-success">{{ $matched }}</span>
      </a>
      <a class="income-tab {{ $only === 'unmatched' ? 'active' : '' }}" href="{{ $tabUrl('unmatched') }}">
        Unmatched <span class="badge rounded-pill text-bg-warning">{{ $unmatched }}</span>
      </a>
    </div>
    <div class="income-tab-meta">
      {{ $items->count() }} row • halaman {{ $items->currentPage() }} / {{ $items->lastPage() }} • total {{ $items->total() }}
    </div>
  </div>

  <section class="income-card">
    <div class="income-filter-head">
      <div>
        <h2 class="income-card-title"><i class="bi bi-funnel"></i> Filter data</h2>
        <div class="income-card-note">Gunakan filter batch untuk audit satu file import.</div>
      </div>
      <a class="btn btn-sm btn-outline-secondary" href="{{ route('imports.marketplace_income.index') }}">Reset</a>
    </div>

    <form method="GET" action="{{ route('imports.marketplace_income.index') }}">
      <div class="income-filter-grid">
        <div class="income-field">
          <label>Order ID</label>
          <input class="form-control" name="q" value="{{ $q ?? '' }}" placeholder="Cari order id...">
        </div>
        <div class="income-field">
          <label>Channel</label>
          <select class="form-select" name="channel">
            <option value="">Semua</option>
            <option value="shopee" @selected(($channel ?? '') === 'shopee')>Shopee</option>
            <option value="tiktok" @selected(($channel ?? '') === 'tiktok')>TikTok</option>
          </select>
        </div>
        <div class="income-field">
          <label>Match</label>
          <select class="form-select" name="only">
            <option value="all" @selected($only === 'all')>Semua</option>
            <option value="matched" @selected($only === 'matched')>Matched</option>
            <option value="unmatched" @selected($only === 'unmatched')>Unmatched</option>
          </select>
        </div>
        <div class="income-field">
          <label>Store</label>
          <select class="form-select" name="store_id">
            <option value="0">Semua</option>
            @foreach($stores as $st)
              <option value="{{ $st->id }}" @selected((int)($storeId ?? 0) === (int)$st->id)>{{ $st->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="income-field">
          <label>Batch</label>
          <input class="form-control mono" name="batch" value="{{ $batch ?? '' }}" placeholder="Opsional">
        </div>
        <div class="income-field">
          <label>Released From</label>
          <input type="date" class="form-control" name="date_from" value="{{ $from }}">
        </div>
        <div class="income-field">
          <label>Released To</label>
          <input type="date" class="form-control" name="date_to" value="{{ $to }}">
        </div>
        <div class="income-field">
          <label>Per page</label>
          <select class="form-select" name="per_page">
            @foreach([25, 50, 100, 200] as $pp)
              <option value="{{ $pp }}" @selected((int)request('per_page', 50) === $pp)>{{ $pp }}</option>
            @endforeach
          </select>
        </div>
        <div class="income-field" style="display:flex;align-items:end;">
          <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-search"></i> Terapkan</button>
        </div>
      </div>
    </form>
  </section>

  <section class="income-kpi-grid">
    <div class="income-kpi order">
      <div class="income-kpi-label">Orders</div>
      <div class="income-kpi-value">{{ number_format($total) }}</div>
      <div class="income-kpi-sub">{{ number_format($refundOrders) }} order memiliki refund</div>
    </div>
    <div class="income-kpi net">
      <div class="income-kpi-label">Dana Cair / Net</div>
      <div class="income-kpi-value">{{ $money($netTotal) }}</div>
      <div class="income-kpi-sub">Nilai payout dari income file</div>
    </div>
    <div class="income-kpi fee">
      <div class="income-kpi-label">Fee Marketplace</div>
      <div class="income-kpi-value">{{ $money($feeTotal) }}</div>
      <div class="income-kpi-sub">Total biaya platform / layanan</div>
    </div>
    <div class="income-kpi refund">
      <div class="income-kpi-label">Refund</div>
      <div class="income-kpi-value">{{ $money($refundTotal) }}</div>
      <div class="income-kpi-sub">Refund order: {{ number_format($refundOrders) }}</div>
    </div>
    <div class="income-kpi match">
      <div class="income-kpi-label">Shipment Match</div>
      <div class="income-kpi-value">{{ $matchRate }}%</div>
      <div class="income-kpi-sub">{{ number_format($matched) }} matched • {{ number_format($unmatched) }} unmatched</div>
    </div>
  </section>

  <section class="income-card">
    <div class="income-card-head">
      <div>
        <h2 class="income-card-title"><i class="bi bi-list-columns-reverse"></i> Income per order</h2>
        <div class="income-card-note">Klik detail untuk melihat snapshot income dan item shipment.</div>
      </div>
      <span class="income-card-note">Sumber: mp_incomes</span>
    </div>

    <div class="income-table-wrap {{ $items->count() > 0 ? 'has-rows' : '' }}">
      <table class="income-table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Channel / Store</th>
            <th>Released</th>
            <th class="text-end">Order value</th>
            <th>Shipment</th>
            <th class="text-end">Fee</th>
            <th class="text-end">Refund</th>
            <th class="text-end">Net payout</th>
            <th>Match</th>
            <th>Batch</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        @forelse($items as $row)
          @php
            $net = (float)($row->net_payout_actual ?? 0);
            $fee = (float)($row->platform_fee_total ?? 0);
            $ref = (float)($row->refund_total ?? 0);
            $batchId = (string)($row->import_batch_id ?? '');
            $isMatched = !empty($row->mp_shipment_id);
            $storeName = $storeMap[(int)($row->store_id ?? 0)] ?? ('Store #'.($row->store_id ?? 0));
            $itemsQty = (int)($row->ship_items_qty_sum ?? 0);
            $itemsRows = (int)($row->ship_items_rows_count ?? 0);
            $shipmentQty = (int)($row->shipment_total_qty ?? 0);
            $displayQty = $itemsQty > 0 ? $itemsQty : $shipmentQty;
            $shipmentStatusRaw = trim((string)($row->shipment_marketplace_status ?? ''));
            $shipmentStatusRaw = $shipmentStatusRaw !== ''
              ? $shipmentStatusRaw
              : trim((string)($row->shipment_status_norm ?? ''));
            $shipmentStatus = $shipmentStatusRaw !== ''
              ? ucwords(str_replace('_', ' ', strtolower($shipmentStatusRaw)))
              : 'Belum ada status';
            $orderValue = $row->shipment_grand_total !== null && (float)$row->shipment_grand_total > 0
              ? (float)$row->shipment_grand_total
              : ($row->shipment_order_subtotal !== null && (float)$row->shipment_order_subtotal > 0 ? (float)$row->shipment_order_subtotal : null);
            $releasedDate = $row->released_date ?? '-';
            $releasedTime = $row->released_at ? Str::of((string)$row->released_at)->after(' ')->limit(8, '') : '';
          @endphp
          <tr>
            <td>
              <a class="income-page-link income-order-id" href="{{ route('imports.marketplace_income.order.show', ['income' => $row->id]) }}">
                {{ $row->platform_order_id }}
              </a>
              @if(!empty($row->source_file))
                <div class="income-muted small">{{ Str::limit((string)$row->source_file, 32) }}</div>
              @endif
            </td>
            <td>
              <div class="income-detail-stack">
                <strong>{{ strtoupper((string)$row->channel) }}</strong>
                <span class="income-muted">{{ $storeName }}</span>
              </div>
            </td>
            <td>
              <div class="income-detail-stack">
                <strong>{{ $releasedDate }}</strong>
                <span class="income-muted mono">{{ $releasedTime ?: '—' }}</span>
              </div>
            </td>
            <td class="income-number">
              @if($orderValue !== null)
                <strong>{{ $money($orderValue) }}</strong>
                @if($row->shipment_order_subtotal !== null && (float)$row->shipment_order_subtotal !== $orderValue)
                  <div class="income-muted small">Subtotal {{ $money((float)$row->shipment_order_subtotal) }}</div>
                @endif
              @else
                <span class="income-muted">—</span>
              @endif
            </td>
            <td>
              @if($isMatched)
                <span class="badge text-bg-light border">{{ Str::limit($shipmentStatus, 28) }}</span>
                @if(!empty($row->shipment_tracking_no))
                  <div class="income-muted small mono mt-1">{{ $row->shipment_tracking_no }}</div>
                @endif
                <div class="income-muted small mt-1">Items {{ $itemsRows }} • Qty {{ $displayQty }}</div>
              @else
                <span class="income-muted">Belum terhubung</span>
              @endif
            </td>
            <td class="income-number">{{ $money($fee) }}</td>
            <td class="income-number {{ $ref != 0 ? 'income-refund' : 'income-muted' }}">{{ $money($ref) }}</td>
            <td class="income-number {{ $net < 0 ? 'income-net negative' : 'income-net' }}">{{ $money($net) }}</td>
            <td>
              @if($isMatched)
                <span class="income-status matched"><i class="bi bi-check-circle"></i> Matched</span>
              @else
                <span class="income-status unmatched"><i class="bi bi-exclamation-circle"></i> Unmatched</span>
              @endif
            </td>
            <td>
              @if($batchId !== '')
                <a class="income-page-link income-muted mono small" href="{{ route('imports.marketplace_income.show', ['batch' => $batchId]) }}" title="{{ $batchId }}">
                  {{ Str::limit($batchId, 18, '…') }}
                </a>
              @else
                <span class="income-muted">—</span>
              @endif
            </td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="{{ route('imports.marketplace_income.order.show', ['income' => $row->id]) }}">Detail</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="11" class="income-empty">Belum ada data income. Import file marketplace untuk memulai.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="income-mobile-list">
      @forelse($items as $row)
        @php
          $net = (float)($row->net_payout_actual ?? 0);
          $fee = (float)($row->platform_fee_total ?? 0);
          $ref = (float)($row->refund_total ?? 0);
          $batchId = (string)($row->import_batch_id ?? '');
          $isMatched = !empty($row->mp_shipment_id);
          $storeName = $storeMap[(int)($row->store_id ?? 0)] ?? ('Store #'.($row->store_id ?? 0));
          $releasedDate = $row->released_date ?? '-';
          $itemsQty = (int)($row->ship_items_qty_sum ?? 0);
          $itemsRows = (int)($row->ship_items_rows_count ?? 0);
          $shipmentQty = (int)($row->shipment_total_qty ?? 0);
          $displayQty = $itemsQty > 0 ? $itemsQty : $shipmentQty;
          $shipmentStatusRaw = trim((string)($row->shipment_marketplace_status ?? ''));
          $shipmentStatusRaw = $shipmentStatusRaw !== ''
            ? $shipmentStatusRaw
            : trim((string)($row->shipment_status_norm ?? ''));
          $shipmentStatus = $shipmentStatusRaw !== ''
            ? ucwords(str_replace('_', ' ', strtolower($shipmentStatusRaw)))
            : 'Belum ada status';
          $orderValue = $row->shipment_grand_total !== null && (float)$row->shipment_grand_total > 0
            ? (float)$row->shipment_grand_total
            : ($row->shipment_order_subtotal !== null && (float)$row->shipment_order_subtotal > 0 ? (float)$row->shipment_order_subtotal : null);
        @endphp
        <div style="padding:1rem;border-bottom:1px solid var(--income-line);">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="min-w-0">
              <a class="income-order-id income-page-link" href="{{ route('imports.marketplace_income.order.show', ['income' => $row->id]) }}">{{ $row->platform_order_id }}</a>
              <div class="income-muted small mt-1">{{ strtoupper((string)$row->channel) }} • {{ $storeName }}</div>
              <div class="income-badges mt-2" style="margin-top:.5rem;">
                <span class="badge text-bg-light border">{{ $releasedDate }}</span>
                @if($isMatched)
                  <span class="income-status matched">Matched</span>
                  <span class="badge text-bg-light border">Items {{ $itemsRows }}</span>
                  <span class="badge text-bg-light border">Qty {{ $displayQty }}</span>
                  <span class="badge text-bg-light border">{{ Str::limit($shipmentStatus, 24) }}</span>
                @else
                  <span class="income-status unmatched">Unmatched</span>
                @endif
              </div>
              @if($isMatched && !empty($row->shipment_tracking_no))
                <div class="income-muted small mono mt-2">Resi: {{ $row->shipment_tracking_no }}</div>
              @endif
              @if($orderValue !== null)
                <div class="income-muted small mt-1">Order value: <strong>{{ $money($orderValue) }}</strong></div>
              @endif
            </div>
            <div class="text-end">
              <div class="income-net {{ $net < 0 ? 'negative' : '' }}">{{ $money($net) }}</div>
              <div class="income-muted small">Fee {{ $money($fee) }}</div>
              <div class="income-muted small">Refund {{ $money($ref) }}</div>
            </div>
          </div>
          <div class="d-flex gap-2 mt-3">
            @if($batchId !== '')
              <a class="btn btn-sm btn-outline-secondary" href="{{ route('imports.marketplace_income.show', ['batch' => $batchId]) }}">Batch</a>
            @endif
            <a class="btn btn-sm btn-primary" href="{{ route('imports.marketplace_income.order.show', ['income' => $row->id]) }}">Detail</a>
          </div>
        </div>
      @empty
        <div class="income-empty">Belum ada data income.</div>
      @endforelse
    </div>

    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap p-3">
      <div class="income-muted small">Total {{ $items->total() }} rows</div>
      <div>{{ $items->links() }}</div>
    </div>
  </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
  if (typeof window.flatpickr !== 'function') return;
  document.querySelectorAll('input[type="date"]').forEach(function (el) {
    if (el._flatpickr) return;
    window.flatpickr(el, { dateFormat: "Y-m-d", allowInput: true });
  });
})();
</script>
@endpush
