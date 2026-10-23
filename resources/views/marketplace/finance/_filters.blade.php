@php
    $isReconciliation = request()->routeIs('marketplace.finance.reconciliation');
    $activeFilterCount = collect([
        $filters['store_id'] ?? null,
        filled($filters['date_from'] ?? null) || filled($filters['date_to'] ?? null) ? 'date_range' : null,
        $filters['order_sn'] ?? null,
        $filters['status'] ?? null,
    ])->filter(fn ($value) => filled($value))->count();
@endphp

<form method="GET" class="finance-card finance-filter-card card mb-4" data-finance-auto-filter>
    <details class="finance-filter-disclosure" open data-finance-filter>
        <summary class="finance-filter-summary">
            <span class="d-flex align-items-center gap-2">
                <span class="finance-filter-icon" aria-hidden="true"><i class="bi bi-sliders2"></i></span>
                <span>
                    <span class="finance-section-title d-block">Cakupan analitik</span>
                    <span class="finance-section-meta d-block mt-1">Pilih toko, periode, dan order yang ingin dianalisis.</span>
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
            <div class="finance-filter-grid {{ $isReconciliation ? 'has-status' : '' }}">
                <div class="finance-filter-field">
                    <label class="form-label" for="finance-store">Toko</label>
                    <select id="finance-store" name="store_id" class="form-select">
                        <option value="">Semua toko</option>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" @selected((string) ($filters['store_id'] ?? '') === (string) $store->id)>{{ $store->name }}{{ $store->channel ? ' · '.$store->channel->name : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="finance-filter-field">
                    <label class="form-label" for="finance-date-basis">Gunakan tanggal</label>
                    <select id="finance-date-basis" name="date_basis" class="form-select">
                        <option value="ordered_at" @selected(($filters['date_basis'] ?? 'ordered_at') === 'ordered_at')>Tanggal order</option>
                        <option value="created_at" @selected(($filters['date_basis'] ?? '') === 'created_at')>Transaksi dicatat</option>
                        <option value="released_at" @selected(($filters['date_basis'] ?? '') === 'released_at')>Dana cair</option>
                    </select>
                    <small class="text-muted">{{ $isReconciliation ? 'Rekonsiliasi mengikuti basis tanggal order yang dipilih.' : 'Settlement tetap menggunakan tanggal settlement.' }}</small>
                </div>
                <div class="finance-filter-field finance-filter-period-field">
                    <label class="form-label" id="finance-period-label" for="finance-period-display">Periode tanggal</label>
                    <input id="finance-from" type="hidden" name="date_from" value="{{ $filters['date_from'] ?? '' }}" data-gf-date="off">
                    <input id="finance-to" type="hidden" name="date_to" value="{{ $filters['date_to'] ?? '' }}" data-gf-date="off">
                    <div class="date-section finance-date-section">
                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                        <input id="finance-period-display" type="text" class="rts-date-picker" value="" placeholder="Pilih rentang tanggal" readonly autocomplete="off" aria-labelledby="finance-period-label" data-gf-date="off" data-finance-date-range-picker data-finance-date-from="finance-from" data-finance-date-to="finance-to">
                        <button type="button" class="finance-date-clear" data-finance-date-clear hidden aria-label="Hapus periode tanggal" title="Hapus periode tanggal"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    </div>
                </div>
                <div class="finance-filter-field">
                    <label class="form-label" for="finance-order">Order SN</label>
                    <div class="finance-filter-search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input id="finance-order" type="search" name="order_sn" class="form-control" value="{{ $filters['order_sn'] ?? '' }}" placeholder="Cari order" autocomplete="off">
                    </div>
                </div>
                @if ($isReconciliation)
                    <div class="finance-filter-field">
                        <label class="form-label" for="finance-status">Status</label>
                        <select id="finance-status" name="status" class="form-select">
                            <option value="">Semua status</option>
                            @foreach (['matched' => 'Matched', 'mismatch' => 'Mismatch', 'unmatched' => 'Unmatched', 'pending' => 'Pending'] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <div class="finance-filter-footer">
                <span class="finance-filter-note"><i class="bi bi-lightning-charge" aria-hidden="true"></i>Filter otomatis aktif</span>
                @if (request()->query())
                    <a class="finance-filter-reset" href="{{ url()->current() }}"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Reset filter</a>
                @endif
            </div>
        </div>
    </details>
</form>
