{{-- resources/views/imports/marketplace/partials/_kpi.blade.php --}}
<div class="row g-2 mb-3">

  {{-- ======================
    PERFORMA PESANAN
  ====================== --}}
  <div class="col-12 col-lg-6">
    <div class="cardx p-3">
      <div class="fw-bold mb-2">Performa Pesanan</div>

      <div class="row g-2">
        <div class="col-6">
          <div class="text-muted small">Penjualan</div>
          <div class="fw-bold">{{ $money($orders['sales'] ?? 0) }}</div>
          <div class="small {{ $deltaClass($delta['orders_sales'] ?? 0) }}">
            {{ $dPct($delta['orders_sales'] ?? 0) }}
          </div>
        </div>

        <div class="col-6">
          <div class="text-muted small">Pesanan</div>
          <div class="fw-bold">{{ (int)($orders['orders'] ?? 0) }}</div>
          <div class="small {{ $deltaClass($delta['orders_orders'] ?? 0) }}">
            {{ $dPct($delta['orders_orders'] ?? 0) }}
          </div>
        </div>

        <div class="col-6">
          <div class="text-muted small">Item Terjual</div>
          <div class="fw-bold">{{ (int)($orders['items'] ?? 0) }}</div>
          <div class="small {{ $deltaClass($delta['orders_items'] ?? 0) }}">
            {{ $dPct($delta['orders_items'] ?? 0) }}
          </div>
        </div>

        <div class="col-6">
          <div class="text-muted small">Delivery Rate</div>
          <div class="fw-bold">
            {{ number_format((float)($orders['delivery_rate'] ?? 0), 1) }}%
          </div>
          <div class="small {{ $deltaClass($delta['orders_delivery_rate'] ?? 0) }}">
            {{ ($delta['orders_delivery_rate'] ?? 0) >= 0 ? '+' : '' }}
            {{ number_format((float)($delta['orders_delivery_rate'] ?? 0), 1) }}%
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ======================
    PERFORMA PENGIRIMAN
  ====================== --}}
  <div class="col-12 col-lg-6">
    <div class="cardx p-3">
      <div class="fw-bold mb-2">Performa Pengiriman</div>

      <div class="row g-2">
        <div class="col-6">
          <div class="text-muted small">In Transit</div>
          <div class="fw-bold">{{ (int)($ship['in_transit'] ?? 0) }}</div>
          <div class="small {{ $deltaClass($delta['ship_in_transit'] ?? 0) }}">
            {{ $dPct($delta['ship_in_transit'] ?? 0) }}
          </div>
        </div>

        <div class="col-6">
          <div class="text-muted small">Delivered</div>
          <div class="fw-bold">{{ (int)($ship['delivered'] ?? 0) }}</div>
          <div class="small {{ $deltaClass($delta['ship_delivered'] ?? 0) }}">
            {{ $dPct($delta['ship_delivered'] ?? 0) }}
          </div>
        </div>

        <div class="col-6">
          <div class="text-muted small">Untracked</div>
          <div class="fw-bold">{{ (int)($ship['untracked'] ?? 0) }}</div>
          <div class="small {{ $deltaClass($delta['ship_untracked'] ?? 0) }}">
            {{ $dPct($delta['ship_untracked'] ?? 0) }}
          </div>
        </div>

        <div class="col-6">
          <div class="text-muted small">Avg Delivery</div>
          <div class="fw-bold">
            {{ ($ship['avg_delivery_days'] ?? null) !== null
                ? number_format((float)$ship['avg_delivery_days'], 1) . ' hari'
                : '-' }}
          </div>
          <div class="small {{ $deltaClass($delta['ship_avg_days'] ?? 0) }}">
            {{ ($delta['ship_avg_days'] ?? 0) >= 0 ? '+' : '' }}
            {{ number_format((float)($delta['ship_avg_days'] ?? 0), 1) }}h
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
