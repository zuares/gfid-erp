<form method="GET" action="{{ route('marketplace.finance.transactions') }}" class="finance-card card">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between gap-2 mb-4">
            <div>
                <div class="finance-section-title">Filter transaksi</div>
                <div class="finance-section-meta mt-1">Persempit data sebelum ditampilkan.</div>
            </div>
            <i class="bi bi-sliders text-primary fs-5" aria-hidden="true"></i>
        </div>

        <div class="mb-3">
            <label class="form-label" for="transaction-store">Toko</label>
            <select id="transaction-store" name="store_id" class="form-select">
                <option value="">Semua toko</option>
                @foreach ($stores as $store)
                    <option value="{{ $store->id }}" @selected((string) ($filters['store_id'] ?? '') === (string) $store->id)>
                        {{ $store->name }}{{ $store->channel ? ' · '.$store->channel->name : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label" for="transaction-date-basis">Gunakan tanggal</label>
            <select id="transaction-date-basis" name="date_basis" class="form-select">
                <option value="created_at" @selected(($filters['date_basis'] ?? 'created_at') === 'created_at')>Transaksi dibuat</option>
                <option value="released_at" @selected(($filters['date_basis'] ?? '') === 'released_at')>Income released</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label" for="transaction-from">Dari tanggal</label>
            <input id="transaction-from" type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
        </div>

        <div class="mb-3">
            <label class="form-label" for="transaction-to">Sampai tanggal</label>
            <input id="transaction-to" type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
        </div>

        <div class="mb-3">
            <label class="form-label" for="transaction-order">Order SN</label>
            <input id="transaction-order" type="search" name="order_sn" class="form-control" value="{{ $filters['order_sn'] ?? '' }}" placeholder="Cari order">
        </div>

        <div class="mb-4">
            <label class="form-label" for="transaction-per-page">Baris per halaman</label>
            <select id="transaction-per-page" name="per_page" class="form-select">
                @foreach ([25, 50, 100] as $option)
                    <option value="{{ $option }}" @selected((int) request('per_page', 50) === $option)>{{ $option }} transaksi</option>
                @endforeach
            </select>
        </div>

        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan filter</button>
        @if (request()->query())
            <a class="btn btn-light border w-100 mt-2" href="{{ route('marketplace.finance.transactions') }}"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset filter</a>
        @endif
    </div>
</form>
