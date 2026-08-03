@extends('layouts.app')
@section('title','Marketplace • Sales Summary')

@php
  $fmt = fn($n)=>number_format((float)$n,0,',','.');
  $fm2 = fn($n)=>number_format((float)$n,2,',','.');
@endphp

@section('content')
<div class="container py-3">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-0">Sales Summary</h4>
      <div class="text-muted small">
        Channel: <b>{{ $channel }}</b> • Period: <b>{{ $month }}</b> • Date: <b>{{ $dateField }}</b>
        @if($storeId) • Store: <b>#{{ $storeId }}</b> @endif
      </div>
    </div>

    <a class="btn btn-outline-secondary"
       href="{{ route('marketplace.reports.sales.export', ['month'=>$month,'channel'=>$channel,'date_field'=>$dateField,'store_id'=>$storeId]) }}">
      Export CSV (Daily)
    </a>
  </div>

  {{-- FILTERS --}}
  <form class="row g-2 mb-3">
    <div class="col-auto">
      <input type="month" name="month" value="{{ $month }}" class="form-control">
    </div>

    <div class="col-auto">
      <select name="channel" class="form-select">
        @foreach(['shopee'=>'Shopee','tiktok'=>'TikTok'] as $k=>$v)
          <option value="{{ $k }}" @selected($channel===$k)>{{ $v }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-auto">
      <select name="date_field" class="form-select">
        @foreach(['ordered_at','paid_at','shipped_at','delivered_at','completed_at','settlement_time'] as $f)
          <option value="{{ $f }}" @selected($dateField===$f)>{{ $f }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-auto">
      <select name="store_id" class="form-select">
        <option value="0">All Stores</option>
        @foreach($stores as $st)
          <option value="{{ $st->id }}" @selected((int)$storeId === (int)$st->id)>
            {{ $st->name }} ({{ $st->channel?->code ?? '-' }})
          </option>
        @endforeach
      </select>
    </div>

    <div class="col-auto">
      <button class="btn btn-primary">Apply</button>
    </div>
  </form>

  {{-- SUMMARY CARDS --}}
  <div class="row g-2">
    <div class="col-md-4">
      <div class="card"><div class="card-body">
        <div class="text-muted small">Orders</div>
        <div class="fs-4 fw-bold">{{ $fmt($summary->total_orders) }}</div>
      </div></div>
    </div>

    <div class="col-md-4">
      <div class="card"><div class="card-body">
        <div class="text-muted small">Total Qty (Shipments)</div>
        <div class="fs-4 fw-bold">{{ $fmt($summary->total_qty) }}</div>
      </div></div>
    </div>

    <div class="col-md-4">
      <div class="card"><div class="card-body">
        <div class="text-muted small">Gross Sales (grand_total)</div>
        <div class="fs-4 fw-bold">Rp {{ $fmt($summary->gross_sales) }}</div>
      </div></div>
    </div>

    <div class="col-md-3">
      <div class="card"><div class="card-body">
        <div class="text-muted small">Platform Fee</div>
        <div class="fs-5 fw-bold">Rp {{ $fmt($summary->platform_fee) }}</div>
      </div></div>
    </div>

    <div class="col-md-3">
      <div class="card"><div class="card-body">
        <div class="text-muted small">Refund</div>
        <div class="fs-5 fw-bold">Rp {{ $fmt($summary->refund_total) }}</div>
      </div></div>
    </div>

    <div class="col-md-3">
      <div class="card"><div class="card-body">
        <div class="text-muted small">Net Payout</div>
        <div class="fs-5 fw-bold">Rp {{ $fmt($summary->net_payout) }}</div>
      </div></div>
    </div>

    <div class="col-md-3">
      <div class="card"><div class="card-body">
        <div class="text-muted small">AOV</div>
        <div class="fs-5 fw-bold">Rp {{ $fmt($summary->aov) }}</div>
      </div></div>
    </div>
  </div>

  {{-- CATEGORY TABLE --}}
  <hr class="my-4">
  <h5 class="mb-2">Sales by Category</h5>

  <div class="card mb-4">
    <div class="table-responsive">
      <table class="table table-sm table-striped mb-0">
        <thead>
          <tr>
            <th style="width:140px">Category</th>
            <th>Name</th>
            <th class="text-end" style="width:110px">Qty</th>
            <th class="text-end" style="width:160px">Sales</th>
            <th class="text-end" style="width:160px">Avg Price</th>
          </tr>
        </thead>
        <tbody>
          @forelse($categories as $c)
            <tr>
              <td class="font-monospace">{{ $c->category_code }}</td>
              <td>{{ $c->category_name }}</td>
              <td class="text-end">{{ $fmt($c->total_qty) }}</td>
              <td class="text-end">Rp {{ $fmt($c->total_sales) }}</td>
              <td class="text-end">Rp {{ $fm2($c->avg_unit_price) }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-4">No category data</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- ITEMS TABLE --}}
  <div class="d-flex align-items-end justify-content-between mb-2">
    <div>
      <h5 class="mb-0">Items</h5>
      <div class="text-muted small">
        Qty: Packet (SKU parent) • Sales: Shipment Items (subtotal)
      </div>
    </div>

    <form class="d-flex gap-2" method="get">
      <input type="hidden" name="month" value="{{ $month }}">
      <input type="hidden" name="channel" value="{{ $channel }}">
      <input type="hidden" name="date_field" value="{{ $dateField }}">
      <input type="hidden" name="store_id" value="{{ $storeId }}">
      <input type="text" name="q_item" value="{{ request('q_item') }}" class="form-control"
             placeholder="Search SKU / name / category" style="max-width:280px">
      <button class="btn btn-outline-secondary">Search</button>
    </form>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-sm table-striped mb-0">
        <thead>
          <tr>
            <th style="width:140px">SKU</th>
            <th>Name</th>
            <th style="width:140px">Category</th>
            <th class="text-end" style="width:110px">Qty</th>
            <th class="text-end" style="width:160px">Sales</th>
            <th class="text-end" style="width:160px">Avg Price</th>
          </tr>
        </thead>
        <tbody>
          @forelse($items as $r)
            <tr>
              <td class="font-monospace">{{ $r->sku }}</td>
              <td>
                {{ $r->name }}
                @if($r->item_id)
                  <div class="text-muted small">Item: #{{ $r->item_id }} • {{ $r->item_code }}</div>
                @else
                  <div class="text-muted small">Item: <span class="text-danger">UNMAPPED</span></div>
                @endif
              </td>
              <td class="font-monospace">
                {{ $r->category_code ?? 'UNMAPPED' }}
                <div class="text-muted small">{{ $r->category_name ?? 'Unmapped' }}</div>
              </td>
              <td class="text-end">{{ $fmt($r->qty) }}</td>
              <td class="text-end">Rp {{ $fmt($r->sales) }}</td>
              <td class="text-end">Rp {{ $fm2($r->avg_price) }}</td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No items</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $items->links() }}
  </div>

</div>
@endsection
