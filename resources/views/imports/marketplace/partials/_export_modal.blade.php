<div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form class="modal-content" method="GET" action="{{ route('imports.marketplace.export') }}">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">Export CSV</h5>
          <div class="text-muted small">Pilih kolom yang mau diexport.</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        {{-- keep current filters (important) --}}
        @foreach(($filters ?? []) as $k => $v)
          <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach

        <div class="fw-bold mb-2">Kolom utama</div>
        @php
          $mainCols = [
            'platform_order_id' => 'Order ID',
            'platform_shipment_id' => 'Shipment ID',
            'channel' => 'Channel',
            'store' => 'Store',
            'tracking_no' => 'Tracking',
            'status_norm' => 'Status',
            'order_created_at' => 'Order Date',
            'total_qty' => 'Qty',
            'grand_total' => 'Grand Total',
            'shipped_at' => 'Shipped At',
            'delivered_at' => 'Delivered At',
          ];
        @endphp

        <div class="row g-2 mb-3">
          @foreach($mainCols as $k => $label)
            <div class="col-6 col-md-4">
              <label class="form-check">
                <input class="form-check-input" type="checkbox" name="cols[]" value="{{ $k }}" checked>
                <span class="form-check-label">{{ $label }}</span>
              </label>
            </div>
          @endforeach
        </div>

        <div class="fw-bold mb-2">Kolom raw_line</div>
        @if(!empty($rawCols))
          <div class="row g-2">
            @foreach($rawCols as $c)
              <div class="col-6 col-md-4">
                <label class="form-check">
                  <input class="form-check-input" type="checkbox" name="raw_cols[]" value="{{ $c }}">
                  <span class="form-check-label">{{ $c }}</span>
                </label>
              </div>
            @endforeach
          </div>
        @else
          <div class="text-muted small">Tidak ada raw_line pada periode/filter ini.</div>
        @endif
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary">Download CSV</button>
      </div>
    </form>
  </div>
</div>
