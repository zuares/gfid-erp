{{-- resources/views/imports/marketplace_income/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Imports • Marketplace Income • Batch ' . ($batch ?? '-'))

@php
  use Illuminate\Support\Str;

  $fmt = fn($n) => number_format((float)($n ?? 0), 0, ',', '.');

  $stores = $stores ?? collect();
  $items  = $items ?? collect();
  $summaryRows = $summaryRows ?? collect();
  $shipmentItemsByShip = $shipmentItemsByShip ?? collect();

  $storeMap = $stores->keyBy('id');
@endphp

@push('head')
<style>
  .page{max-width:1220px;margin:0 auto;padding: 1rem .9rem 4.8rem;}
  .page > .d-flex { position: relative; z-index: 20; }
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

  .cardx{border:1px solid var(--line);border-radius:14px;background: var(--card, #fff);box-shadow: var(--shadow);}
  .chip{display:inline-flex;align-items:center;gap:.35rem;padding:.18rem .55rem;border-radius:999px;font-size:.78rem;border:1px solid var(--line2);background: var(--soft);white-space:nowrap;}
  .mono{font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace;}

  .show-sm{display:none;}
  .hide-sm{display:table-cell;}
  @media(max-width:820px){
    thead{display:none;}
    .hide-sm{display:none;}
    .show-sm{display:block;}
    tbody td{display:block;border-bottom:none;padding:.75rem .85rem;}
    tbody tr{display:block;border-bottom:1px solid rgba(148,163,184,.14);}
    body[data-theme="dark"] tbody tr{border-bottom:1px solid rgba(148,163,184,.10);}
    .mrow{display:flex;justify-content:space-between;gap:.75rem;}
    .mright{text-align:right;white-space:nowrap;}
  }

  .filter-head{display:flex;justify-content:space-between;align-items:center;gap:.75rem;margin-bottom:.55rem;}
  .filter-grid{display:grid;grid-template-columns: 2fr 1fr 1.2fr;gap:.65rem;align-items:end;}
  @media(max-width:992px){ .filter-grid{grid-template-columns:1fr 1fr;} }
  @media(max-width:520px){ .filter-grid{grid-template-columns:1fr;} }
  .filter-grid .form-label{font-size:.78rem;margin-bottom:.25rem;color:var(--muted);}

  .subtle{color:var(--muted);}
  .btn{white-space:nowrap;}
</style>
@endpush

@section('content')
<div class="page">

  {{-- FLASH --}}
  @if (session('success'))
    <div class="alert alert-success py-2 px-3 mb-3">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger py-2 px-3 mb-3">{{ session('error') }}</div>
  @endif

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1 fw-bold">Marketplace Income · Batch</h1>
      <div class="text-muted small d-flex flex-wrap gap-1 align-items-center">
        <span class="chip mono">batch: {{ $batch }}</span>
        <span class="chip">Orders: {{ (int)($totalOrders ?? 0) }}</span>
        <span class="chip" style="border-color: rgba(34,197,94,.35); background: rgba(34,197,94,.10);">Matched {{ (int)($matchedOrders ?? 0) }}</span>
        <span class="chip" style="border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.10);">Unmatched {{ (int)($unmatchedOrders ?? 0) }}</span>
      </div>
    </div>

    <div class="d-flex gap-2 align-items-center head-actions">
      <a class="btn btn-outline-secondary btn-sm px-3" href="{{ route('imports.marketplace_income.index') }}">← Orders</a>

      {{-- Re-apply (pakai route kamu yang sudah ada) --}}
      @if(Route::has('imports.marketplace_income.apply'))
        <form method="POST" action="{{ route('imports.marketplace_income.apply', ['batch' => $batch]) }}">
          @csrf
          <input type="hidden" name="channel" value="{{ $channel }}">
          <input type="hidden" name="store_id" value="{{ (int)$storeId }}">
          <button class="btn btn-outline-primary btn-sm px-3" type="submit">Re-apply</button>
        </form>
      @endif
    </div>
  </div>

  {{-- SUMMARY --}}
  <div class="cardx p-3 mb-3">
    <div class="fw-bold mb-2">Summary per Channel / Store</div>
    <div class="table-responsive">
      <table class="table table-sm mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>Channel</th>
            <th>Store</th>
            <th class="text-end">Orders</th>
            <th class="text-end">Net</th>
            <th class="text-end">Fee</th>
            <th class="text-end">Refund</th>
            <th>Released</th>
            <th>Source</th>
          </tr>
        </thead>
        <tbody>
          @forelse($summaryRows as $r)
            @php
              $stName = data_get($storeMap, (int)$r->store_id.'.name', '#'.$r->store_id);
            @endphp
            <tr>
              <td><span class="chip">Ch: {{ strtoupper((string)$r->channel) }}</span></td>
              <td>{{ $stName }}</td>
              <td class="text-end fw-bold">{{ (int)($r->orders_count ?? 0) }}</td>
              <td class="text-end">Rp {{ $fmt($r->net_sum ?? 0) }}</td>
              <td class="text-end">Rp {{ $fmt($r->fee_sum ?? 0) }}</td>
              <td class="text-end">Rp {{ $fmt($r->refund_sum ?? 0) }}</td>
              <td class="mono">
                {{ $r->released_date_min ?? '-' }} → {{ $r->released_date_max ?? '-' }}
              </td>
              <td class="text-muted small">{{ Str::limit((string)($r->source_file_max ?? '-'), 40) }}</td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-muted p-3">Tidak ada summary.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- KPI --}}
  <div class="row g-2 mb-3">
    <div class="col-12 col-lg-6">
      <div class="cardx p-3">
        <div class="fw-bold mb-2">Audit</div>
        <div class="row g-2">
          <div class="col-6">
            <div class="text-muted small">Net Payout</div>
            <div class="fw-bold">Rp {{ $fmt($audit->net_sum ?? 0) }}</div>
          </div>
          <div class="col-6">
            <div class="text-muted small">Platform Fee</div>
            <div class="fw-bold">Rp {{ $fmt($audit->fee_sum ?? 0) }}</div>
          </div>
          <div class="col-6">
            <div class="text-muted small">Refund Total</div>
            <div class="fw-bold">Rp {{ $fmt($audit->refund_sum ?? 0) }}</div>
          </div>
          <div class="col-6">
            <div class="text-muted small">Refund Orders</div>
            <div class="fw-bold">{{ (int)($audit->refund_orders_count ?? 0) }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="cardx p-3">
        <div class="fw-bold mb-2">Match on this page</div>
        <div class="row g-2">
          <div class="col-6">
            <div class="text-muted small">Orders in page</div>
            <div class="fw-bold">{{ (int)($items->count()) }}</div>
          </div>
          <div class="col-6">
            <div class="text-muted small">Matched orders in page</div>
            <div class="fw-bold">{{ (int)($matchedCount ?? 0) }}</div>
          </div>
          <div class="col-12">
            <div class="text-muted small">
              Net negative count: <span class="fw-bold">{{ (int)($audit->net_negative_count ?? 0) }}</span>
              • Net negative sum: <span class="fw-bold text-danger">Rp {{ $fmt($audit->net_negative_sum ?? 0) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- FILTER --}}
  <form id="filterForm" method="GET" action="{{ route('imports.marketplace_income.show', ['batch' => $batch]) }}" class="cardx p-3 mb-3">
    <div class="filter-head">
      <div class="fw-bold">Filter</div>
      <a href="{{ route('imports.marketplace_income.show', ['batch' => $batch]) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
    </div>

    <div class="filter-grid">
      <div>
        <label class="form-label">Cari Order ID</label>
        <input class="form-control" name="q" value="{{ $q ?? '' }}" placeholder="platform_order_id…">
      </div>

      <div>
        <label class="form-label">Channel</label>
        <select class="form-select" name="channel">
          <option value="">Semua</option>
          <option value="shopee" @selected(($channel ?? '')==='shopee')>Shopee</option>
          <option value="tiktok" @selected(($channel ?? '')==='tiktok')>TikTok</option>
        </select>
      </div>

      <div>
        <label class="form-label">Store</label>
        <select class="form-select" name="store_id">
          <option value="0">Semua</option>
          @foreach($stores as $st)
            <option value="{{ $st->id }}" @selected((int)($storeId ?? 0)===(int)$st->id)>
              {{ $st->name }}
            </option>
          @endforeach
        </select>
      </div>
    </div>
  </form>

  {{-- TABLE --}}
  <div class="cardx position-relative">
    <div class="p-3 d-flex justify-content-between align-items-center">
      <div class="fw-bold">Data</div>
      <div class="text-muted small">
        {{ $items->count() }} rows • Page {{ $items->currentPage() }} / {{ $items->lastPage() }}
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:260px;">Order</th>
            <th style="width:140px;">Released</th>
            <th class="text-end" style="width:150px;">Fee</th>
            <th class="text-end" style="width:150px;">Refund</th>
            <th class="text-end" style="width:170px;">Net</th>
            <th style="width:140px;">Match</th>
            <th style="width:120px;" class="text-end">Items</th>
            <th style="width:90px;" class="text-end">Detail</th>
          </tr>
        </thead>

        <tbody>
          @forelse($items as $row)
            @php
              $net = (float)($row->net_payout_actual ?? 0);
              $fee = (float)($row->platform_fee_total ?? 0);
              $ref = (float)($row->refund_total ?? 0);

              $tone = 'text-success';
              if ($net < 0) $tone = 'text-danger';
              elseif ($ref != 0) $tone = 'text-warning';

              $mpShipmentId = $row->mp_shipment_id ?? null;
              $lines = $mpShipmentId ? ($shipmentItemsByShip[$mpShipmentId] ?? collect()) : collect();

              $collapseId = 'it' . (int)$row->id;
            @endphp

            <tr>
              {{-- mobile --}}
              <td class="show-sm">
                <div class="mrow">
                  <div>
                    <div class="fw-bold mono">{{ $row->platform_order_id }}</div>
                    <div class="mt-1 d-flex flex-wrap gap-1">
                      <span class="chip">Ch: {{ strtoupper((string)$row->channel) }}</span>
                      <span class="chip">{{ data_get($storeMap, (int)$row->store_id.'.name', '#'.$row->store_id) }}</span>

                      @if($mpShipmentId)
                        <span class="chip" style="border-color: rgba(34,197,94,.35); background: rgba(34,197,94,.10);">matched</span>
                      @else
                        <span class="chip" style="border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.10);">unmatched</span>
                      @endif

                      <span class="chip">items: {{ (int)($row->mp_items_rows_count ?? 0) }} • qty: {{ (int)($row->mp_items_qty_sum ?? 0) }}</span>
                    </div>
                    <div class="text-muted small mt-1">
                      {{ $row->released_date ?? '-' }} • <span class="mono">{{ $row->released_at ?? '' }}</span>
                    </div>

                    @if($mpShipmentId && $lines->count())
                      <button class="btn btn-outline-secondary btn-sm mt-2" type="button"
                              data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}">
                        Lihat Item ({{ $lines->count() }})
                      </button>
                    @endif
                  </div>

                  <div class="mright">
                    <div class="fw-bold {{ $tone }}">Rp {{ $fmt($net) }}</div>
                    <div class="text-muted small">Fee {{ $fmt($fee) }}</div>
                    <div class="text-muted small">Refund {{ $fmt($ref) }}</div>
                  </div>
                </div>

                @if($mpShipmentId && $lines->count())
                  <div class="collapse mt-2" id="{{ $collapseId }}">
                    <div class="card card-body">
                      <div class="fw-bold mb-2">Shipment Items</div>
                      <div class="text-muted small mb-2 mono">mp_shipment_id={{ $mpShipmentId }}</div>
                      <ul class="mb-0 ps-3">
                        @foreach($lines as $li)
                          <li class="mb-1">
                            <span class="mono">
                              {{ data_get($li,'sku_ref', data_get($li,'sku', data_get($li,'platform_sku','-'))) }}
                            </span>
                            • qty {{ (int)data_get($li,'qty',0) }}
                            @php $nm = data_get($li,'name', data_get($li,'item_name', data_get($li,'title',''))); @endphp
                            @if($nm) — {{ $nm }} @endif
                          </li>
                        @endforeach
                      </ul>
                    </div>
                  </div>
                @endif
              </td>

              {{-- desktop --}}
              <td class="hide-sm">
                <div class="fw-bold mono">{{ $row->platform_order_id }}</div>
                <div class="text-muted small d-flex flex-wrap gap-1">
                  <span class="chip">Ch: {{ strtoupper((string)$row->channel) }}</span>
                  <span class="chip">{{ data_get($storeMap, (int)$row->store_id.'.name', '#'.$row->store_id) }}</span>
                  <span class="chip">ID: {{ (int)$row->id }}</span>
                </div>
              </td>

              <td class="hide-sm">
                <div class="fw-bold">{{ $row->released_date ?? '-' }}</div>
                <div class="text-muted small mono">{{ $row->released_at ?? '' }}</div>
              </td>

              <td class="hide-sm text-end">Rp {{ $fmt($fee) }}</td>
              <td class="hide-sm text-end">Rp {{ $fmt($ref) }}</td>

              <td class="hide-sm text-end">
                <div class="fw-bold {{ $tone }}">Rp {{ $fmt($net) }}</div>
              </td>

              <td class="hide-sm">
                @if($mpShipmentId)
                  <span class="chip" style="border-color: rgba(34,197,94,.35); background: rgba(34,197,94,.10);">matched</span>
                  <div class="text-muted small mono">ship={{ $mpShipmentId }}</div>
                @else
                  <span class="chip" style="border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.10);">unmatched</span>
                @endif
              </td>

              <td class="hide-sm text-end">
                <div class="fw-bold">{{ (int)($row->mp_items_rows_count ?? 0) }}</div>
                <div class="text-muted small">qty {{ (int)($row->mp_items_qty_sum ?? 0) }}</div>
              </td>

              <td class="hide-sm text-end">
                @if($mpShipmentId && $lines->count())
                  <button class="btn btn-outline-secondary btn-sm"
                          type="button"
                          data-bs-toggle="collapse"
                          data-bs-target="#{{ $collapseId }}">
                    Items
                  </button>
                @else
                  <span class="text-muted small">-</span>
                @endif
              </td>
            </tr>

            {{-- desktop collapse row --}}
            @if($mpShipmentId && $lines->count())
              <tr class="hide-sm">
                <td colspan="8" class="p-0">
                  <div class="collapse" id="{{ $collapseId }}">
                    <div class="p-3" style="background: rgba(148,163,184,.06);">
                      <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                          <div class="fw-bold">Shipment Items</div>
                          <div class="text-muted small mono">mp_shipment_id={{ $mpShipmentId }}</div>
                        </div>
                        <div class="text-muted small">
                          rows: {{ $lines->count() }} • qty: {{ (int)($row->mp_items_qty_sum ?? 0) }}
                        </div>
                      </div>

                      <div class="table-responsive mt-2">
                        <table class="table table-sm mb-0 align-middle">
                          <thead class="table-light">
                            <tr>
                              <th style="width:260px;">SKU</th>
                              <th>Name</th>
                              <th class="text-end" style="width:100px;">Qty</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach($lines as $li)
                              @php
                                $sku = data_get($li,'sku_ref', data_get($li,'sku', data_get($li,'platform_sku','-')));
                                $nm  = data_get($li,'name', data_get($li,'item_name', data_get($li,'title','-')));
                                $qty = (int) data_get($li,'qty', 0);
                              @endphp
                              <tr>
                                <td class="mono">{{ $sku }}</td>
                                <td>{{ $nm }}</td>
                                <td class="text-end fw-bold">{{ $qty }}</td>
                              </tr>
                            @endforeach
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            @endif

          @empty
            <tr>
              <td colspan="8" class="p-3 text-muted">Belum ada data.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-3 d-flex justify-content-between align-items-center gap-2">
      <div class="text-muted small">Total {{ $items->total() }} rows</div>
      <div>{{ $items->links() }}</div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
(function(){
  const form = document.getElementById('filterForm');
  if (!form) return;

  const debounce = (fn, ms=250) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; };

  const run = debounce(() => form.submit(), 250);
  form.addEventListener('input', run);
  form.addEventListener('change', run);
})();
</script>
@endpush
