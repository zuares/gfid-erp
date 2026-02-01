

{{-- resources/views/imports/marketplace/partials/_filter.blade.php --}}

@once
  @include('imports.marketplace.partials._helpers')
@endonce
<form id="filterForm" method="GET" action="{{ route('imports.marketplace.index') }}" class="cardx p-3 mb-3">
  <div class="d-flex justify-content-between align-items-center">
    <div class="fw-bold">Daftar Pengiriman</div>
    @if($advActive)
      <span class="chip" style="border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.10);">Filter aktif</span>
    @endif
  </div>

  <div class="row g-2 mt-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label small text-muted mb-1">Cari</label>
      <input class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="order / tracking / shipment">
    </div>

    <div class="col-md-2">
      <label class="form-label small text-muted mb-1">Channel</label>
      <select class="form-select" name="channel">
        <option value="">Semua</option>
        <option value="shopee" @selected(($filters['channel'] ?? '')==='shopee')>Shopee</option>
        <option value="tiktok" @selected(($filters['channel'] ?? '')==='tiktok')>TikTok</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label small text-muted mb-1">Status</label>
      <select class="form-select" name="status">
        <option value="">Semua</option>
        <option value="in_transit" @selected(($filters['status'] ?? '')==='in_transit')>In Transit</option>
        <option value="delivered" @selected(($filters['status'] ?? '')==='delivered')>Delivered</option>
        <option value="canceled" @selected(($filters['status'] ?? '')==='canceled')>Canceled</option>
        <option value="unknown" @selected(($filters['status'] ?? '')==='unknown')>Unknown</option>
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label small text-muted mb-1">Dari</label>
      <input type="date" class="form-control" name="from" value="{{ $filters['from'] ?? '' }}">
    </div>

    <div class="col-md-2">
      <label class="form-label small text-muted mb-1">Sampai</label>
      <input type="date" class="form-control" name="to" value="{{ $filters['to'] ?? '' }}">
    </div>

    <div class="col-12 d-flex gap-2 mt-2">
      <a href="{{ route('imports.marketplace.index') }}" class="btn btn-outline-secondary">Reset</a>

      <details class="ms-auto">
        <summary class="btn btn-outline-secondary">Filter lanjutan</summary>
        <div class="mt-2">
          <label class="form-label small text-muted mb-1">Store</label>
          <select class="form-select" name="store_id">
            <option value="">Semua</option>
            @foreach($stores as $st)
              <option value="{{ $st->id }}" @selected((string)($filters['store_id'] ?? '') === (string)$st->id)>
                {{ $st->name }}
              </option>
            @endforeach
          </select>
        </div>
      </details>

      @if($draft)
        <a href="{{ route('imports.marketplace.create') }}" class="btn btn-outline-warning ms-2">Lanjutkan Draft</a>
      @endif
    </div>
  </div>
</form>
