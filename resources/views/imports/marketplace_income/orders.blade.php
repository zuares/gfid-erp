{{-- resources/views/imports/marketplace_income/orders.blade.php --}}
@extends('layouts.app')
@section('title','Imports • Marketplace Income (Orders)')

@php
  use Illuminate\Support\Str;

  $fmt0 = fn($n) => number_format((float)($n ?? 0), 0, ',', '.');

  $from = $dateFrom ?? request('date_from', '');
  $to   = $dateTo   ?? request('date_to', '');
  $only = $only ?? request('only', 'all');

  $storeMap = [];
  foreach(($stores ?? []) as $st){ $storeMap[(int)$st->id] = (string)$st->name; }
@endphp

@push('head')
<style>
  .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }
  .cell-2line { line-height: 1.1; }
  .cell-2line .sub { font-size: .825rem; }
</style>
@endpush

@section('content')
<div class="container-fluid" style="max-width: 1280px;">

  {{-- FLASH --}}
  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  {{-- HEADER --}}
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-3 mb-3">
    <div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <h1 class="h5 mb-0">Marketplace Income</h1>

        @if(($channel ?? '') !== '')
          <span class="badge text-bg-secondary">{{ strtoupper((string)$channel) }}</span>
        @endif

        @if((int)($storeId ?? 0) > 0)
          <span class="badge text-bg-light border">
            {{ $storeMap[(int)$storeId] ?? ('Store #'.$storeId) }}
          </span>
        @endif

        @if($from && $to)
          <span class="badge text-bg-light border">
            {{ $from }} — {{ $to }}
          </span>
        @endif

        @if(($batch ?? '') !== '')
          <span class="badge text-bg-light border mono" title="{{ (string)$batch }}">
            {{ Str::limit((string)$batch, 18, '…') }}
          </span>
        @endif

        @if(($only ?? 'all') === 'matched')
          <span class="badge text-bg-success">Matched</span>
        @elseif(($only ?? 'all') === 'unmatched')
          <span class="badge text-bg-warning">Unmatched</span>
        @endif
      </div>

      <div class="text-muted small mt-1">
        {{ $items->total() }} orders
      </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-outline-secondary" href="{{ route('imports.marketplace_income.index') }}">Reset</a>
      <a class="btn btn-success" href="{{ route('imports.marketplace_income.create') }}">+ Import Income</a>
    </div>
  </div>

  {{-- FILTER --}}
  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" action="{{ route('imports.marketplace_income.index') }}">
        <div class="row g-2">

          <div class="col-12 col-md-4">
            <label class="form-label mb-1">Order ID</label>
            <input class="form-control" name="q" value="{{ $q ?? '' }}" placeholder="Cari order id...">
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label mb-1">Channel</label>
            <select class="form-select" name="channel">
              <option value="">Semua</option>
              <option value="shopee" @selected(($channel ?? '')==='shopee')>Shopee</option>
              <option value="tiktok" @selected(($channel ?? '')==='tiktok')>TikTok</option>
            </select>
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label mb-1">Match</label>
            <select class="form-select" name="only">
              <option value="all" @selected(($only ?? 'all')==='all')>All</option>
              <option value="matched" @selected(($only ?? 'all')==='matched')>Matched</option>
              <option value="unmatched" @selected(($only ?? 'all')==='unmatched')>Unmatched</option>
            </select>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label mb-1">Store</label>
            <select class="form-select" name="store_id">
              <option value="0">Semua</option>
              @foreach($stores as $st)
                <option value="{{ $st->id }}" @selected((int)($storeId ?? 0)===(int)$st->id)>{{ $st->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label mb-1">Batch</label>
            <input class="form-control mono" name="batch" value="{{ $batch ?? '' }}" placeholder="(opsional)">
          </div>

          {{-- FLATPICKR --}}
          <div class="col-6 col-md-2">
            <label class="form-label mb-1">Date From</label>
            <input type="text" class="form-control js-flatpickr-date" name="date_from" value="{{ $from }}" placeholder="YYYY-MM-DD" autocomplete="off">
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label mb-1">Date To</label>
            <input type="text" class="form-control js-flatpickr-date" name="date_to" value="{{ $to }}" placeholder="YYYY-MM-DD" autocomplete="off">
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label mb-1">Per page</label>
            <select class="form-select" name="per_page">
              @foreach([25,50,100,200] as $pp)
                <option value="{{ $pp }}" @selected((int)request('per_page',50)===$pp)>{{ $pp }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-6 col-md-2 d-flex align-items-end gap-2">
            <button class="btn btn-primary w-100" type="submit">Terapkan</button>
            <a class="btn btn-outline-secondary w-100" href="{{ route('imports.marketplace_income.index') }}">Reset</a>
          </div>

        </div>
      </form>
    </div>
  </div>

  {{-- KPI --}}
  <div class="row g-2 mb-3">
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="text-muted small mb-2">Ringkasan</div>
          <div class="row g-2">
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="text-muted small">Total</div><div class="fw-bold">{{ (int)($totalOrders ?? 0) }}</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="text-muted small">Matched</div><div class="fw-bold">{{ (int)($matchedOrders ?? 0) }}</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="text-muted small">Unmatched</div><div class="fw-bold">{{ (int)($unmatchedOrders ?? 0) }}</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="text-muted small">Net Negative</div><div class="fw-bold">{{ (int)($audit->net_negative_count ?? 0) }}</div></div></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="text-muted small mb-2">Nilai</div>
          <div class="row g-2">
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="text-muted small">Net</div><div class="fw-bold">Rp {{ $fmt0($audit->net_sum ?? 0) }}</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="text-muted small">Fee</div><div class="fw-bold">Rp {{ $fmt0($audit->fee_sum ?? 0) }}</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="text-muted small">Refund</div><div class="fw-bold">Rp {{ $fmt0($audit->refund_sum ?? 0) }}</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="text-muted small">Refund Orders</div><div class="fw-bold">{{ (int)($audit->refund_orders_count ?? 0) }}</div></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- MOBILE LIST --}}
  <div class="d-md-none">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="fw-semibold">Orders</div>
        <div class="text-muted small">Page {{ $items->currentPage() }} / {{ $items->lastPage() }}</div>
      </div>

      <div class="list-group list-group-flush">
        @forelse($items as $row)
          @php
            $net = (float)($row->net_payout_actual ?? 0);
            $fee = (float)($row->platform_fee_total ?? 0);
            $ref = (float)($row->refund_total ?? 0);

            $batchId = (string)($row->import_batch_id ?? '');
            $isMatched = !empty($row->mp_shipment_id);

            $storeName = $storeMap[(int)($row->store_id ?? 0)] ?? ('Store #'.($row->store_id ?? 0));
            $itemsQty  = (int)($row->ship_items_qty_sum ?? 0);
            $itemsRows = (int)($row->ship_items_rows_count ?? 0);

            $netClass = 'text-success';
            if ($net < 0) $netClass = 'text-danger';
            elseif ($ref != 0) $netClass = 'text-warning';

            $realisedDate = $row->released_date ?? '-';
            $realisedTime = $row->released_at ? (Str::contains((string)$row->released_at, ' ') ? explode(' ', (string)$row->released_at)[1] : (string)$row->released_at) : '';
            $realisedTime = $realisedTime ? Str::limit($realisedTime, 8, '') : '';
          @endphp

          <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class="fw-bold mono">{{ $row->platform_order_id }}</div>
                <div class="text-muted small">{{ strtoupper((string)$row->channel) }} • {{ $storeName }}</div>

                {{-- ✅ Realised + Match clean --}}
                <div class="mt-2 d-flex flex-wrap gap-1">
                  <span class="badge text-bg-light border">
                    {{ $realisedDate }}@if($realisedTime) <span class="text-muted">•</span> {{ $realisedTime }}@endif
                  </span>

                  @if($isMatched)
                    <span class="badge text-bg-success">Matched</span>
                    <span class="badge text-bg-light border">Items {{ $itemsRows }}</span>
                    <span class="badge text-bg-light border">Qty {{ $itemsQty }}</span>
                  @else
                    <span class="badge text-bg-warning text-dark">Unmatched</span>
                  @endif
                </div>
              </div>

              <div class="text-end">
                <div class="fw-bold {{ $netClass }}">Rp {{ $fmt0($net) }}</div>
                <div class="text-muted small">Fee Rp {{ $fmt0($fee) }}</div>
                <div class="text-muted small">Refund Rp {{ $fmt0($ref) }}</div>
              </div>
            </div>

            <div class="mt-2 d-flex gap-2">
              @if($batchId !== '')
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('imports.marketplace_income.show', ['batch' => $batchId]) }}">Batch</a>
              @endif
              <a class="btn btn-primary btn-sm" href="{{ route('imports.marketplace_income.order.show', ['income' => $row->id]) }}">Detail</a>
            </div>
          </div>
        @empty
          <div class="list-group-item text-muted">Belum ada data.</div>
        @endforelse
      </div>

      <div class="card-footer">
        {{ $items->links() }}
      </div>
    </div>
  </div>

  {{-- DESKTOP TABLE --}}
  <div class="d-none d-md-block">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="fw-semibold">Orders</div>
        <div class="text-muted small">
          {{ $items->count() }} row • Page {{ $items->currentPage() }} / {{ $items->lastPage() }} • Total {{ $items->total() }}
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:260px;">Order</th>
              <th style="width:110px;">Channel</th>
              <th style="width:220px;">Store</th>
              <th style="width:150px;">Realised</th>
              <th class="text-end" style="width:140px;">Fee</th>
              <th class="text-end" style="width:140px;">Refund</th>
              <th class="text-end" style="width:170px;">Net</th>
              <th style="width:200px;">Batch</th>
              <th style="width:150px;">Match</th>
              <th style="width:120px;"></th>
            </tr>
          </thead>

          <tbody>
          @forelse($items as $row)
            @php
              $net = (float)($row->net_payout_actual ?? 0);
              $fee = (float)($row->platform_fee_total ?? 0);
              $ref = (float)($row->refund_total ?? 0);

              $batchId  = (string)($row->import_batch_id ?? '');
              $isMatched = !empty($row->mp_shipment_id);

              $storeName = $storeMap[(int)($row->store_id ?? 0)] ?? ('Store #'.($row->store_id ?? 0));
              $itemsQty  = (int)($row->ship_items_qty_sum ?? 0);
              $itemsRows = (int)($row->ship_items_rows_count ?? 0);

              $netClass = 'text-success';
              if ($net < 0) $netClass = 'text-danger';
              elseif ($ref != 0) $netClass = 'text-warning';

              // ✅ Realised clean: date + time only
              $realisedDate = $row->released_date ?? '-';
              $realisedTime = $row->released_at ? (Str::contains((string)$row->released_at, ' ') ? explode(' ', (string)$row->released_at)[1] : (string)$row->released_at) : '';
              $realisedTime = $realisedTime ? Str::limit($realisedTime, 8, '') : '';
            @endphp

            <tr>
              <td>
                <div class="fw-semibold mono">{{ $row->platform_order_id }}</div>
                @if(!empty($row->source_file))
                  <div class="text-muted small">{{ Str::limit((string)$row->source_file, 36) }}</div>
                @endif
              </td>

              <td>
                <span class="badge text-bg-secondary">{{ strtoupper((string)$row->channel) }}</span>
              </td>

              <td>
                <div class="fw-semibold">{{ $storeName }}</div>
              </td>

              {{-- ✅ Realised CLEAN --}}
              <td class="cell-2line">
                <div class="fw-semibold">{{ $realisedDate }}</div>
                <div class="text-muted sub mono">{{ $realisedTime ?: '—' }}</div>
              </td>

              <td class="text-end">Rp {{ $fmt0($fee) }}</td>
              <td class="text-end">Rp {{ $fmt0($ref) }}</td>

              <td class="text-end">
                <div class="fw-bold {{ $netClass }}">Rp {{ $fmt0($net) }}</div>
              </td>

              <td>
                @if($batchId !== '')
                  <a class="text-decoration-none mono" href="{{ route('imports.marketplace_income.show', ['batch' => $batchId]) }}" title="{{ $batchId }}">
                    {{ Str::limit($batchId, 18, '…') }}
                  </a>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>

              {{-- ✅ Match CLEAN --}}
              <td class="cell-2line">
                @if($isMatched)
                  <div>
                    <span class="badge text-bg-success">Matched</span>
                  </div>
                  <div class="text-muted sub">
                    Items <span class="fw-semibold">{{ $itemsRows }}</span> • Qty <span class="fw-semibold">{{ $itemsQty }}</span>
                  </div>
                @else
                  <div>
                    <span class="badge text-bg-warning text-dark">Unmatched</span>
                  </div>
                  <div class="text-muted sub">—</div>
                @endif
              </td>

              <td class="text-end">
                <a class="btn btn-sm btn-primary" href="{{ route('imports.marketplace_income.order.show', ['income' => $row->id]) }}">
                  Detail
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-muted p-3">Belum ada data.</td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>

      <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="text-muted small">Total {{ $items->total() }} rows</div>
        <div>{{ $items->links() }}</div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
(function(){
  if (typeof window.flatpickr !== 'function') return;

  document.querySelectorAll('.js-flatpickr-date').forEach(el => {
    if (el._flatpickr) return;
    window.flatpickr(el, { dateFormat: "Y-m-d", allowInput: true });
  });
})();
</script>
@endpush
