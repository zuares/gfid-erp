@php
    $activeFilterCount = collect([
        $filters['store_id'] ?? null,
        filled($filters['date_from'] ?? null) || filled($filters['date_to'] ?? null) ? 'date_range' : null,
    ])->filter(fn ($value) => filled($value))->count();
@endphp

<form method="GET" action="{{ route('marketplace.finance.settlements') }}" class="finance-card finance-filter-card card" data-finance-auto-filter>
    <details class="finance-filter-disclosure" open data-finance-filter>
        <summary class="finance-filter-summary">
            <span class="d-flex align-items-center gap-2">
                <span class="finance-filter-icon" aria-hidden="true"><i class="bi bi-sliders2"></i></span>
                <span>
                    <span class="finance-section-title d-block">Filter settlement</span>
                    <span class="finance-section-meta d-block mt-1">Pilih toko dan periode pencairan.</span>
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
                <label class="form-label" for="settlement-store">Toko</label>
                <select id="settlement-store" name="store_id" class="form-select">
                    <option value="">Semua toko</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" @selected((string) ($filters['store_id'] ?? '') === (string) $store->id)>
                            {{ $store->name }}{{ $store->channel ? ' · '.$store->channel->name : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="finance-sidebar-field finance-sidebar-field--last">
                <label class="form-label" id="settlement-period-label" for="settlement-period-display">Periode settlement</label>
                <input id="settlement-from" type="hidden" name="date_from" value="{{ $filters['date_from'] ?? '' }}" data-gf-date="off">
                <input id="settlement-to" type="hidden" name="date_to" value="{{ $filters['date_to'] ?? '' }}" data-gf-date="off">
                <div class="date-section finance-date-section">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <input id="settlement-period-display" type="text" class="rts-date-picker" value="" placeholder="Pilih rentang tanggal" readonly autocomplete="off" aria-labelledby="settlement-period-label" data-gf-date="off" data-finance-date-range-picker data-finance-date-from="settlement-from" data-finance-date-to="settlement-to">
                    <button type="button" class="finance-date-clear" data-finance-date-clear hidden aria-label="Hapus periode settlement" title="Hapus periode settlement"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </div>
            </div>

            <div class="finance-filter-footer">
                <span class="finance-filter-note"><i class="bi bi-lightning-charge" aria-hidden="true"></i>Filter otomatis aktif</span>
                @if (request()->query())
                    <a class="finance-filter-reset" href="{{ route('marketplace.finance.settlements') }}"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Reset filter</a>
                @endif
            </div>
        </div>
    </details>
</form>
