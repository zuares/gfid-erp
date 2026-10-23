@php
    $activeFilterCount = collect([
        $filters['store_id'] ?? null,
        $filters['channel'] ?? null,
        ($filters['date_basis'] ?? 'ordered_at') !== 'ordered_at' ? ($filters['date_basis'] ?? null) : null,
        filled($filters['date_from'] ?? null) || filled($filters['date_to'] ?? null) ? 'date_range' : null,
        $filters['order_sn'] ?? null,
    ])->filter(fn ($value) => filled($value))->count();
@endphp

<form method="GET" action="{{ route('marketplace.finance.transactions') }}" class="finance-card finance-filter-card card" data-finance-auto-filter>
    <details class="finance-filter-disclosure" open data-finance-filter>
        <summary class="finance-filter-summary">
            <span class="d-flex align-items-center gap-2">
                <span class="finance-filter-icon" aria-hidden="true"><i class="bi bi-sliders2"></i></span>
                <span>
                    <span class="finance-section-title d-block">Filter transaksi</span>
                    <span class="finance-section-meta d-block mt-1">Persempit data sebelum ditampilkan.</span>
                </span>
            </span>
            <span class="finance-filter-summary-end">
                @if ($activeFilterCount > 0)
                    <span class="finance-filter-count">{{ $activeFilterCount }} aktif</span>
                @endif
                <i class="bi bi-chevron-down finance-filter-chevron" aria-hidden="true"></i>
            </span>
        </summary>

        <div class="finance-filter-body">
            <div class="finance-sidebar-field">
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

            <div class="finance-sidebar-field">
                <label class="form-label" for="transaction-channel">Marketplace</label>
                <select id="transaction-channel" name="channel" class="form-select">
                    <option value="">Semua marketplace</option>
                    @foreach ($channels as $channel)
                        <option value="{{ $channel->code }}" @selected(($filters['channel'] ?? '') === $channel->code)>{{ $channel->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="finance-sidebar-field">
                <label class="form-label" for="transaction-date-basis">Gunakan tanggal</label>
                <select id="transaction-date-basis" name="date_basis" class="form-select">
                    <option value="ordered_at" @selected(($filters['date_basis'] ?? 'ordered_at') === 'ordered_at')>Tanggal order</option>
                    <option value="created_at" @selected(($filters['date_basis'] ?? '') === 'created_at')>Transaksi dicatat</option>
                    <option value="released_at" @selected(($filters['date_basis'] ?? '') === 'released_at')>Dana cair</option>
                </select>
            </div>

            <div class="finance-sidebar-field">
                <label class="form-label" id="transaction-period-label" for="transaction-period-display">Periode tanggal</label>
                <input id="transaction-from" type="hidden" name="date_from" value="{{ $filters['date_from'] ?? '' }}" data-gf-date="off">
                <input id="transaction-to" type="hidden" name="date_to" value="{{ $filters['date_to'] ?? '' }}" data-gf-date="off">
                <div class="date-section finance-date-section">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <input id="transaction-period-display" type="text" class="rts-date-picker" value="" placeholder="Pilih rentang tanggal" readonly autocomplete="off" aria-labelledby="transaction-period-label" data-gf-date="off" data-finance-date-range-picker data-finance-date-from="transaction-from" data-finance-date-to="transaction-to">
                    <button type="button" class="finance-date-clear" data-finance-date-clear hidden aria-label="Hapus periode tanggal" title="Hapus periode tanggal"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </div>
            </div>

            <div class="finance-sidebar-field">
                <label class="form-label" for="transaction-order">Order SN</label>
                <div class="finance-filter-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input id="transaction-order" type="search" name="order_sn" class="form-control" value="{{ $filters['order_sn'] ?? '' }}" placeholder="Cari nomor order" autocomplete="off">
                </div>
            </div>

            <div class="finance-sidebar-field finance-sidebar-field--last">
                <label class="form-label" for="transaction-per-page">Baris per halaman</label>
                <select id="transaction-per-page" name="per_page" class="form-select">
                    @foreach ([25, 50] as $option)
                        <option value="{{ $option }}" @selected((int) request('per_page', 25) === $option)>{{ $option }} transaksi</option>
                    @endforeach
                </select>
            </div>

            <div class="finance-filter-footer">
                <span class="finance-filter-note"><i class="bi bi-lightning-charge" aria-hidden="true"></i>Filter otomatis aktif</span>
                @if (request()->query())
                    <a class="finance-filter-reset" href="{{ route('marketplace.finance.transactions') }}"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Reset filter</a>
                @endif
            </div>
        </div>
    </details>
</form>
