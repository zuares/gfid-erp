{{-- resources/views/imports/marketplace_income/show_batch.blade.php --}}
@extends('layouts.app')
@section('title','Imports • Marketplace Income (Batch)')

@php
  use Illuminate\Support\Str;

  $fmt0 = fn($n) => number_format((float)($n ?? 0), 0, ',', '.');
  $storeMap = [];
  foreach(($stores ?? []) as $st){ $storeMap[(int)$st->id] = (string)$st->name; }
@endphp

@push('head')
<style>
  .page{ max-width:1260px; margin:0 auto; padding: 1rem .9rem 4.8rem; }
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

  .cardx{ border:1px solid var(--line); border-radius:14px; background: var(--card, #fff); box-shadow: var(--shadow); }
  .chip{ display:inline-flex; align-items:center; gap:.35rem; padding:.18rem .55rem; border-radius:999px; font-size:.78rem; border:1px solid var(--line2); background: var(--soft); white-space:nowrap; }
  .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }

  .topbar{ display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:.75rem; }
  .subchips{ display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.35rem; }
  .actions{ display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }

  .grid-2{ display:grid; grid-template-columns: 1.2fr .8fr; gap:.75rem; }
  @media(max-width:992px){ .grid-2{ grid-template-columns: 1fr; } }

  .filter-grid{ display:grid; grid-template-columns: 2fr 1fr 1.2fr auto; gap:.6rem; align-items:end; }
  @media(max-width:992px){ .filter-grid{ grid-template-columns: 1fr 1fr; } }
  @media(max-width:560px){ .filter-grid{ grid-template-columns: 1fr; } }
  .filter-grid .form-label{ font-size:.78rem; margin-bottom:.25rem; color: var(--muted); }

  .kpi-row{ display:grid; grid-template-columns: repeat(4, 1fr); gap:.55rem; }
  @media(max-width:560px){ .kpi-row{ grid-template-columns: repeat(2, 1fr); } }
  .kpi-box{ padding: .85rem .9rem; border:1px solid var(--line); border-radius:14px; background: var(--soft); }
  .kpi-box .t{ font-size:.78rem; color: var(--muted); }
  .kpi-box .v{ font-size:1.05rem; font-weight:800; color: var(--ink); margin-top:.1rem; }

  .row-expander{
    cursor:pointer; user-select:none;
    display:inline-flex; align-items:center; gap:.4rem;
  }
  .expando{
    display:none;
    background: var(--soft);
    border-top:1px solid var(--line);
  }
  .expando .inner{ padding: .75rem .85rem; }

  .table-mini th, .table-mini td{ padding:.35rem .5rem; font-size:.9rem; }
  .muted{ color: var(--muted); }

  .show-sm{ display:none; }
  .hide-sm{ display:table-cell; }
  @media(max-width:900px){
    thead{ display:none; }
    .hide-sm{ display:none; }
    .show-sm{ display:block; }
    tbody td{ display:block; border-bottom:none; padding:.75rem .85rem; }
    tbody tr{ display:block; border-bottom:1px solid rgba(148,163,184,.14); }
    body[data-theme="dark"] tbody tr{ border-bottom:1px solid rgba(148,163,184,.10); }
    .mrow{ display:flex; justify-content:space-between; gap:.85rem; }
    .mright{ text-align:right; white-space:nowrap; }
  }
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
  <div class="topbar">
    <div>
      <div class="h4 mb-0 fw-bold">Marketplace Income • Batch</div>
      <div class="subchips">
        <span class="chip mono">Batch: {{ $batch }}</span>
        @if(($channel ?? '')!=='')
          <span class="chip">Ch: {{ strtoupper((string)$channel) }}</span>
        @endif
        @if((int)($storeId ?? 0) > 0)
          <span class="chip">Store: {{ $storeMap[(int)$storeId] ?? ('#'.$storeId) }}</span>
        @endif
        @if(($q ?? '')!=='')
          <span class="chip">q: <span class="mono">{{ Str::limit((string)$q, 22) }}</span></span>
        @endif
      </div>
    </div>

    <div class="actions">
      <a class="btn btn-outline-secondary btn-sm px-3" href="{{ route('imports.marketplace_income.index', ['batch' => $batch]) }}">
        ← Back to orders
      </a>

      <form method="POST" action="{{ route('imports.marketplace_income.apply', ['batch' => $batch]) }}">
        @csrf
        <input type="hidden" name="channel" value="{{ $channel ?? '' }}">
        <input type="hidden" name="store_id" value="{{ (int)($storeId ?? 0) }}">
        <button type="submit" class="btn btn-primary btn-sm px-3">
          Re-apply batch
        </button>
      </form>
    </div>
  </div>

  {{-- SUMMARY + KPI --}}
  <div class="grid-2 mb-3">
    <div class="cardx p-3">
      <div class="fw-bold mb-2">Summary (grouped)</div>
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
            </tr>
          </thead>
          <tbody>
          @forelse($summaryRows as $s)
            @php
              $sStore = $storeMap[(int)($s->store_id ?? 0)] ?? ('#'.($s->store_id ?? 0));
            @endphp
            <tr>
              <td><span class="chip">Ch: {{ strtoupper((string)$s->channel) }}</span></td>
              <td>{{ $sStore }}</td>
              <td class="text-end fw-bold">{{ (int)($s->orders_count ?? 0) }}</td>
              <td class="text-end fw-bold">Rp {{ $fmt0($s->net_sum ?? 0) }}</td>
              <td class="text-end">Rp {{ $fmt0($s->fee_sum ?? 0) }}</td>
              <td class="text-end">Rp {{ $fmt0($s->refund_sum ?? 0) }}</td>
              <td class="mono small text-muted">
                {{ $s->released_date_min ?? '-' }} → {{ $s->released_date_max ?? '-' }}
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-muted p-2">No summary.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="cardx p-3">
      <div class="fw-bold mb-2">KPI (batch)</div>
      <div class="kpi-box">
        <div class="kpi-row">
          <div>
            <div class="t">Total Orders</div>
            <div class="v">{{ (int)($totalOrders ?? 0) }}</div>
          </div>
          <div>
            <div class="t">Matched</div>
            <div class="v">{{ (int)($matchedOrders ?? 0) }}</div>
          </div>
          <div>
            <div class="t">Unmatched</div>
            <div class="v">{{ (int)($unmatchedOrders ?? 0) }}</div>
          </div>
          <div>
            <div class="t">Net Negative</div>
            <div class="v">{{ (int)($audit->net_negative_count ?? 0) }}</div>
          </div>
        </div>
        <hr style="border-color: var(--line);">
        <div class="kpi-row">
          <div>
            <div class="t">Net</div>
            <div class="v">Rp {{ $fmt0($audit->net_sum ?? 0) }}</div>
          </div>
          <div>
            <div class="t">Fee</div>
            <div class="v">Rp {{ $fmt0($audit->fee_sum ?? 0) }}</div>
          </div>
          <div>
            <div class="t">Refund</div>
            <div class="v">Rp {{ $fmt0($audit->refund_sum ?? 0) }}</div>
          </div>
          <div>
            <div class="t">Refund Orders</div>
            <div class="v">{{ (int)($audit->refund_orders_count ?? 0) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- FILTER --}}
  <form id="filterForm" method="GET" action="{{ route('imports.marketplace_income.show', ['batch' => $batch]) }}" class="cardx p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
      <div class="fw-bold">Filter in batch</div>
      <a class="btn btn-outline-secondary btn-sm" href="{{ route('imports.marketplace_income.show', ['batch' => $batch]) }}">Reset</a>
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
            <option value="{{ $st->id }}" @selected((int)($storeId ?? 0)===(int)$st->id)>{{ $st->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-primary w-100" type="submit">Apply</button>
      </div>
    </div>
  </form>

  {{-- TABLE --}}
  <div class="cardx">
    <div class="p-3 d-flex justify-content-between align-items-center gap-2">
      <div class="fw-bold">Orders in this batch</div>
      <div class="text-muted small">
        {{ $items->count() }} row • Page {{ $items->currentPage() }} / {{ $items->lastPage() }} • Total {{ $items->total() }}
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:290px;">Order</th>
            <th style="width:120px;">Channel</th>
            <th style="width:210px;">Store</th>
            <th style="width:130px;">Released</th>
            <th class="text-end" style="width:140px;">Fee</th>
            <th class="text-end" style="width:140px;">Refund</th>
            <th class="text-end" style="width:170px;">Net</th>
            <th style="width:170px;">Shipment</th>
            <th class="text-end" style="width:90px;">Items</th>
          </tr>
        </thead>

        <tbody>
        @forelse($items as $row)
          @php
            $mpShipmentId = $row->mp_shipment_id ?? null;
            $storeName = $storeMap[(int)($row->store_id ?? 0)] ?? ('#'.($row->store_id ?? 0));
            $itemsQty = (int)($row->ship_items_qty_sum ?? 0);
            $itemsRows = (int)($row->ship_items_rows_count ?? 0);

            $net = (float)($row->net_payout_actual ?? 0);
            $tone = 'text-success';
            if ($net < 0) $tone = 'text-danger';
            elseif ((float)($row->refund_total ?? 0) != 0) $tone = 'text-warning';

            $rowKey = 'r'.$row->id;
            $shipItems = $mpShipmentId ? ($shipmentItemsByShip[$mpShipmentId] ?? collect()) : collect();
          @endphp

          <tr data-key="{{ $rowKey }}">
            {{-- mobile --}}
            <td class="show-sm">
              <div class="mrow">
                <div>
                  <div class="fw-bold mono">{{ $row->platform_order_id }}</div>
                  <div class="mt-1 d-flex flex-wrap gap-1">
                    <span class="chip">Ch: {{ strtoupper((string)$row->channel) }}</span>
                    <span class="chip">{{ $storeName }}</span>

                    @if($mpShipmentId)
                      <span class="chip" style="border-color: rgba(34,197,94,.35); background: rgba(34,197,94,.10);">
                        ship #{{ $mpShipmentId }} • {{ $itemsRows }} rows • qty {{ $itemsQty }}
                      </span>
                    @else
                      <span class="chip" style="border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.10);">unmatched</span>
                    @endif
                  </div>

                  <div class="mt-2 d-flex gap-2 flex-wrap">
                    <a class="btn btn-primary btn-sm" href="{{ route('imports.marketplace_income.order.show', ['income' => $row->id]) }}">
                      Show items (detail)
                    </a>

                    @if($mpShipmentId && $shipItems->count())
                      <button type="button" class="btn btn-outline-secondary btn-sm js-toggle" data-target="{{ $rowKey }}">Toggle shipment items</button>
                    @endif
                  </div>
                </div>

                <div class="mright">
                  <div class="fw-bold {{ $tone }}">Rp {{ $fmt0($net) }}</div>
                  <div class="text-muted small">Fee {{ $fmt0($row->platform_fee_total ?? 0) }}</div>
                  <div class="text-muted small">Refund {{ $fmt0($row->refund_total ?? 0) }}</div>
                </div>
              </div>
            </td>

            {{-- desktop --}}
            <td class="hide-sm">
              <div class="fw-bold mono">{{ $row->platform_order_id }}</div>
              <div class="text-muted small d-flex flex-wrap gap-1 mt-1">
                <span class="chip">income_id: {{ $row->id }}</span>
                @if($mpShipmentId)
                  <span class="chip" style="border-color: rgba(34,197,94,.35); background: rgba(34,197,94,.10);">
                    ship #{{ $mpShipmentId }} • {{ $itemsRows }} rows • qty {{ $itemsQty }}
                  </span>
                @else
                  <span class="chip" style="border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.10);">unmatched</span>
                @endif
              </div>

              <div class="mt-2 d-flex gap-2 flex-wrap">
                <a class="btn btn-primary btn-sm" href="{{ route('imports.marketplace_income.order.show', ['income' => $row->id]) }}">
                  Show items (detail)
                </a>

                @if($mpShipmentId && $shipItems->count())
                  <button type="button" class="btn btn-outline-secondary btn-sm js-toggle" data-target="{{ $rowKey }}">
                    Toggle shipment items
                  </button>
                @endif
              </div>
            </td>

            <td class="hide-sm"><span class="chip">Ch: {{ strtoupper((string)$row->channel) }}</span></td>
            <td class="hide-sm">
              {{ $storeName }}
              <div class="text-muted small mono">store_id={{ (int)($row->store_id ?? 0) }}</div>
            </td>

            <td class="hide-sm">
              <div class="fw-bold">{{ $row->released_date ?? '-' }}</div>
              <div class="text-muted small mono">{{ $row->released_at ?? '' }}</div>
            </td>

            <td class="hide-sm text-end">Rp {{ $fmt0($row->platform_fee_total ?? 0) }}</td>
            <td class="hide-sm text-end">Rp {{ $fmt0($row->refund_total ?? 0) }}</td>
            <td class="hide-sm text-end">
              <div class="fw-bold {{ $tone }}">Rp {{ $fmt0($row->net_payout_actual ?? 0) }}</div>
            </td>

            <td class="hide-sm">
              @if($mpShipmentId)
                <span class="chip" style="border-color: rgba(34,197,94,.35); background: rgba(34,197,94,.10);">#{{ $mpShipmentId }}</span>
              @else
                <span class="chip" style="border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.10);">-</span>
              @endif
            </td>

            <td class="hide-sm text-end">
              <div class="fw-bold">{{ $itemsRows }}</div>
              <div class="text-muted small">qty {{ $itemsQty }}</div>
            </td>
          </tr>

          {{-- expando row --}}
          @if($mpShipmentId && $shipItems->count())
            <tr class="expando" data-exp="{{ $rowKey }}">
              <td colspan="9" class="expando">
                <div class="inner">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-bold">mp_shipment_items • ship #{{ $mpShipmentId }}</div>
                    <div class="text-muted small">rows: {{ $shipItems->count() }} • qty sum: {{ $shipItems->sum('qty') }}</div>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-sm table-mini mb-0 align-middle">
                      <thead class="table-light">
                        <tr>
                          <th style="width:180px;">SKU</th>
                          <th>Product</th>
                          <th style="width:220px;">Variant</th>
                          <th class="text-end" style="width:90px;">Qty</th>
                          <th class="text-end" style="width:140px;">Unit</th>
                          <th class="text-end" style="width:140px;">Subtotal</th>
                        </tr>
                      </thead>
                      <tbody>
                      @foreach($shipItems as $si)
                        <tr>
                          <td class="mono">
                            {{ $si->sku_code ?? '-' }}
                            @if(!empty($si->sku_parent))
                              <div class="text-muted small">parent: <span class="mono">{{ $si->sku_parent }}</span></div>
                            @endif
                          </td>
                          <td>{{ $si->product_name ?? '-' }}</td>
                          <td>{{ $si->variant_name ?? '-' }}</td>
                          <td class="text-end fw-bold">{{ (int)($si->qty ?? 0) }}</td>
                          <td class="text-end">Rp {{ $fmt0($si->unit_price ?? 0) }}</td>
                          <td class="text-end fw-bold">Rp {{ $fmt0($si->subtotal ?? 0) }}</td>
                        </tr>
                      @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
              </td>
            </tr>
          @endif
        @empty
          <tr><td colspan="9" class="p-3 text-muted">Belum ada data.</td></tr>
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
  function toggle(key){
    const row = document.querySelector(`tr[data-exp="${key}"]`);
    if(!row) return;
    const isOpen = row.style.display === 'table-row';
    row.style.display = isOpen ? 'none' : 'table-row';
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-toggle');
    if(!btn) return;
    const key = btn.getAttribute('data-target');
    toggle(key);
  });

  // default: closed
  document.querySelectorAll('tr.expando').forEach(r => r.style.display = 'none');
})();
</script>
@endpush
