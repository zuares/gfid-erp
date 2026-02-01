{{-- resources/views/imports/marketplace/partials/_table.blade.php --}}
<div class="cardx">
  <div class="p-3 d-flex justify-content-between align-items-center">
    <div class="fw-bold">Data</div>
    <div class="text-muted small">
      {{ (int)($summary['rows'] ?? 0) }} rows • Qty {{ (int)($summary['sum_qty'] ?? 0) }} • {{ $money($summary['sum_grand_total'] ?? 0) }}
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:290px;">Order</th>
          <th style="width:170px;">Store</th>
          <th style="width:230px;">Tracking</th>
          <th style="width:130px;">Status</th>
          <th style="width:130px;">Tanggal</th>
          <th class="text-end" style="width:90px;">Qty</th>
          <th class="text-end" style="width:170px;">Grand</th>
          <th class="text-end" style="width:90px;">Aksi</th>
        </tr>
      </thead>

      <tbody>
        @forelse(($shipments ?? []) as $s)
          @php [$stText,$tone] = $statusLabel($s->status_norm); @endphp

          <tr role="button" onclick="window.location='{{ route('imports.marketplace.show', $s->id) }}'">

            {{-- MOBILE --}}
            <td class="show-sm">
              <div class="mrow">
                <div>
                  <div class="fw-bold mono">{{ $s->platform_order_id }}</div>
                  <div class="mt-1 d-flex flex-wrap gap-1">
                    <span class="chip">Ch: {{ $s->channel }}</span>
                    <span class="chip">Status: {{ $stText }}</span>
                    @if($s->tracking_no) <span class="chip mono">{{ $s->tracking_no }}</span> @endif
                  </div>
                  <div class="text-muted small mt-1">{{ $fmtDate($s->order_created_at) }}</div>
                </div>
                <div class="mright">
                  <div class="fw-bold">{{ $money($s->grand_total) }}</div>
                  <div class="text-muted small">Qty {{ (int)($s->total_qty ?? 0) }}</div>
                </div>
              </div>
            </td>

            {{-- DESKTOP --}}
            <td class="hide-sm">
              <div class="fw-bold mono">{{ $s->platform_order_id }}</div>
              <div class="text-muted small">
                <span class="chip">Ch: {{ $s->channel }}</span>
                @if($s->platform_shipment_id)
                  <span class="chip mono">Ship: {{ $s->platform_shipment_id }}</span>
                @endif
              </div>
            </td>

            <td class="hide-sm">{{ $s->store->name ?? '-' }}</td>
            <td class="hide-sm"><span class="mono">{{ $s->tracking_no ?? '-' }}</span></td>

            <td class="hide-sm">
              <span class="badge bg-{{ $tone }}">{{ $stText }}</span>
            </td>

            <td class="hide-sm">{{ $fmtDate($s->order_created_at) }}</td>
            <td class="hide-sm text-end">{{ (int)($s->total_qty ?? 0) }}</td>
            <td class="hide-sm text-end fw-bold">{{ $money($s->grand_total) }}</td>

            <td class="hide-sm text-end" onclick="event.stopPropagation()">
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                  Aksi
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                  <a class="dropdown-item" href="{{ route('imports.marketplace.show', $s->id) }}">Detail</a>
                  @if($s->tracking_no)
                    <button class="dropdown-item" type="button"
                      onclick="navigator.clipboard.writeText('{{ $s->tracking_no }}')">
                      Copy Tracking
                    </button>
                  @endif
                </div>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="p-3 text-muted">Belum ada data pada periode ini.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($shipments && method_exists($shipments, 'links'))
    <div class="p-3">{{ $shipments->links() }}</div>
  @endif
</div>
