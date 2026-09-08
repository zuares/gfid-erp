<form method="GET" class="finance-card finance-filter-card card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <div class="finance-section-title">Filter data</div>
                <div class="finance-section-meta">Persempit transaksi yang ingin kamu audit.</div>
            </div>
            @if (request()->query())
                <a class="btn btn-sm btn-light border" href="{{ url()->current() }}"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
            @endif
        </div>
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label" for="finance-store">Toko</label>
                <select id="finance-store" name="store_id" class="form-select">
                    <option value="">Semua toko</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" @selected((string) ($filters['store_id'] ?? '') === (string) $store->id)>{{ $store->name }}{{ $store->channel ? ' · '.$store->channel->name : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label" for="finance-from">Dari tanggal</label>
                <input id="finance-from" type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label" for="finance-to">Sampai tanggal</label>
                <input id="finance-to" type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label" for="finance-order">Order SN</label>
                <input id="finance-order" type="search" name="order_sn" class="form-control" value="{{ $filters['order_sn'] ?? '' }}" placeholder="Cari order">
            </div>
            @if (request()->routeIs('marketplace.finance.reconciliation'))
                <div class="col-12 col-md-2">
                    <label class="form-label" for="finance-status">Status</label>
                    <select id="finance-status" name="status" class="form-select">
                        <option value="">Semua status</option>
                        @foreach (['matched' => 'Matched', 'mismatch' => 'Mismatch', 'unmatched' => 'Unmatched', 'pending' => 'Pending'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-12 col-md-1 d-grid">
                <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
        </div>
    </div>
</form>
