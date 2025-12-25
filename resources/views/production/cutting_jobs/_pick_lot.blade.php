{{-- resources/views/production/cutting_jobs/_pick_lot.blade.php --}}

@push('head')
    <style>
        .lot-picker-wrap {
            margin-bottom: .75rem;
        }

        .lot-picker-header {
            display: flex;
            flex-direction: column;
            gap: .4rem;
            margin-bottom: .75rem;
        }

        @media (min-width: 576px) {
            .lot-picker-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: flex-end;
            }
        }

        .lot-picker-title {
            font-size: .9rem;
            font-weight: 600;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .lot-picker-help {
            font-size: .8rem;
        }

        .lot-picker-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
        }

        /* === FILTER WARNA = FOCAL POINT, TAPI LEBIH SOFT === */
        .lot-picker-filter {
            margin-top: .45rem;
            padding: .4rem .6rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            background: color-mix(in srgb, var(--card) 85%, rgba(59, 130, 246, 0.15));
            border: 1px solid rgba(59, 130, 246, 0.35);
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08);
        }

        body[data-theme="dark"] .lot-picker-filter {
            background: color-mix(in srgb, var(--card) 80%, rgba(37, 99, 235, 0.25));
            border-color: rgba(129, 140, 248, 0.75);
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.5);
        }

        .lot-picker-filter-label {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .11em;
            font-weight: 600;
            color: #0f172a;
            white-space: nowrap;
        }

        body[data-theme="dark"] .lot-picker-filter-label {
            color: #e5e7eb;
        }

        .lot-picker-filter select.form-select {
            border-radius: 999px;
            border-color: rgba(37, 99, 235, 0.7);
            font-size: .82rem;
            font-weight: 500;
            padding-inline: .75rem;
            padding-block: .24rem;
            min-height: 2.05rem;
            background-color: rgba(255, 255, 255, 0.96);
        }

        body[data-theme="dark"] .lot-picker-filter select.form-select {
            background-color: rgba(15, 23, 42, 0.92);
            border-color: rgba(191, 219, 254, 0.9);
            color: #e5e7eb;
        }

        .lot-picker-filter select.form-select:focus {
            box-shadow:
                0 0 0 1px rgba(248, 250, 252, 0.8),
                0 0 0 2px rgba(59, 130, 246, 0.55);
        }

        @media (min-width: 576px) {
            .lot-picker-filter {
                max-width: 320px;
            }
        }

        @media (max-width: 575.98px) {
            .lot-picker-filter {
                margin-top: .35rem;
            }
        }

        .lot-item-group {
            margin-top: .75rem;
            border-radius: 12px;
            border: 1px dashed rgba(148, 163, 184, 0.5);
            padding: .6rem .65rem .55rem;
            background: color-mix(in srgb, var(--card) 88%, rgba(59, 130, 246, 0.06));
        }

        body[data-theme="dark"] .lot-item-group {
            background: color-mix(in srgb, var(--card) 90%, rgba(15, 23, 42, 0.9));
            border-color: rgba(148, 163, 184, 0.7);
        }

        .lot-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            margin-bottom: .45rem;
        }

        .lot-item-main {
            display: flex;
            flex-direction: column;
            gap: .15rem;
        }

        .lot-item-name {
            font-size: .85rem;
            font-weight: 600;
        }

        .lot-item-code-pill {
            font-size: .72rem;
            padding: .08rem .45rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.7);
            background: rgba(148, 163, 184, 0.12);
            display: inline-flex;
            align-items: center;
            gap: .25rem;
        }

        .lot-item-color-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            opacity: .9;
        }

        .lot-item-total-balance {
            font-size: .78rem;
            text-align: right;
        }

        .lot-item-total-balance .mono {
            font-weight: 600;
        }

        .lot-grid {
            display: grid;
            gap: .6rem;
        }

        @media (min-width: 576px) {
            .lot-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 992px) {
            .lot-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .lot-card-modern {
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.45);
            padding: .55rem .6rem .45rem;
            background: var(--card);
            cursor: pointer;
            transition:
                background-color 0.15s ease,
                box-shadow 0.15s ease,
                border-color 0.15s ease,
                transform 0.06s ease;
        }

        .lot-card-modern:hover {
            box-shadow:
                0 10px 26px rgba(15, 23, 42, 0.16),
                0 0 0 1px rgba(59, 130, 246, 0.3);
            border-color: rgba(59, 130, 246, 0.5);
            transform: translateY(-1px);
        }

        .lot-card-modern .lot-code {
            font-size: .8rem;
            letter-spacing: .03em;
        }

        .lot-card-modern .lot-warehouse {
            font-size: .72rem;
        }

        .lot-card-modern.lot-selected {
            border-color: rgba(59, 130, 246, 0.9);
            box-shadow:
                0 10px 30px rgba(37, 99, 235, 0.18),
                0 0 0 1px rgba(59, 130, 246, 0.5);
            background: color-mix(in srgb, var(--card) 80%, rgba(59, 130, 246, 0.12));
        }

        /* Ikuti pola lama: JS pakai .lot-row + .lot-hidden */
        .lot-row.lot-hidden {
            display: none;
        }

        .lot-card-badge {
            font-size: .68rem;
            padding: .05rem .45rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.6);
            background: rgba(148, 163, 184, 0.12);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .lot-empty-hint {
            font-size: .8rem;
            color: var(--muted);
            margin-top: .35rem;
        }

        @media (max-width: 767.98px) {
            .lot-card-modern {
                padding: .5rem .55rem .4rem;
            }

            .lot-picker-help {
                font-size: .78rem;
            }

            .btn-pill-sm {
                font-size: .75rem;
                padding-block: .2rem;
            }

            /* Footer di mobile: tombol Simpan LOT full width */
            .lot-picker-footer {
                flex-direction: column;
                align-items: stretch;
                gap: .4rem;
            }

            .lot-picker-footer .btn-primary {
                width: 100%;
                justify-content: center;
            }

            .lot-picker-footer .small.text-muted {
                text-align: left;
            }
        }
    </style>
@endpush

<div class="lot-picker-wrap" id="cutting-pick-lot">
    {{-- Header + filter warna + tombol select all --}}
    <div class="lot-picker-header">
        <div>
            <div class="lot-picker-title">
                Pilih LOT Kain
            </div>
            <div class="lot-picker-help">
                Pilih <strong>warna kain</strong> dulu, lalu centang LOT yang mau dipakai.
            </div>

            @if (!$lotStocks->isEmpty())
                @php
                    // Untuk dropdown filter per warna (item kain)
                    $itemGroupsForSelect = $lotStocks->groupBy(fn($row) => $row->lot->item_id);
                @endphp

                <div class="lot-picker-filter">
                    <div class="lot-picker-filter-label">
                        Warna Kain
                    </div>
                    <select id="lot-item-filter" class="form-select form-select-sm">
                        <option value="">Semua warna</option>
                        @foreach ($itemGroupsForSelect as $itemId => $rowsForSelect)
                            @php
                                $firstRowForSelect = $rowsForSelect->first();
                                $itemSelect = $firstRowForSelect->lot->item ?? null;
                            @endphp
                            @if ($itemSelect)
                                <option value="{{ $itemId }}">
                                    {{ $itemSelect->name }} ({{ $itemSelect->code }})
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <div class="lot-picker-actions">
            <button type="button" class="btn btn-outline-secondary btn-pill-sm" id="btn-select-all-lots" disabled>
                Centang semua
            </button>
            <button type="button" class="btn btn-outline-secondary btn-pill-sm" id="btn-unselect-all-lots" disabled>
                Hapus centang
            </button>
        </div>
    </div>

    {{-- Hint + GRID LOT --}}
    @if ($lotStocks->isEmpty())
        <div id="lot-grid-hint" class="lot-empty-hint">
            Belum ada LOT kain yang siap dipakai. Cek stok di modul GRN / gudang RM.
        </div>
    @else
        @php
            // Group berdasarkan item kain (warna)
            $groupedLots = $lotStocks->groupBy(fn($row) => $row->lot->item_id);
        @endphp

        <div class="mt-2" id="lot-grid">
            @foreach ($groupedLots as $itemId => $rows)
                @php
                    $firstRow = $rows->first();
                    $lot = $firstRow->lot;
                    $item = $lot->item;
                    $totalBalance = $rows->sum('qty_balance');
                    $whCodes = $rows->pluck('warehouse.code')->filter()->unique()->values();
                @endphp

                {{-- Tambah data-item-id untuk filter JS --}}
                <div class="lot-item-group" data-item-id="{{ $itemId }}">
                    <div class="lot-item-header">
                        <div class="lot-item-main">
                            <div class="lot-item-name">
                                {{ $item->name }}
                            </div>
                            <div class="lot-item-code-pill">
                                <span class="lot-item-color-label">Item kain</span>
                                <span class="mono">{{ $item->code }}</span>
                            </div>
                        </div>
                        <div class="lot-item-total-balance">
                            <div class="text-muted small">Total kain (semua LOT):</div>
                            <div class="mono">
                                {{ number_format($totalBalance, 2, ',', '.') }}
                            </div>
                            @if ($whCodes->isNotEmpty())
                                <div class="small text-muted">
                                    Gudang: {{ $whCodes->implode(', ') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Grid LOT untuk item/warna ini --}}
                    <div class="lot-grid">
                        @foreach ($rows as $row)
                            @php
                                $lot = $row->lot;
                                $wh = $row->warehouse;
                            @endphp

                            {{-- .lot-row tetap untuk kompat dengan JS di _form --}}
                            <div class="lot-card-modern lot-row lot-card-item" data-lot-id="{{ $row->lot_id }}"
                                data-balance="{{ $row->qty_balance }}" data-item-id="{{ $item->id }}">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold mono lot-code">
                                            {{ $lot->code }}
                                        </div>
                                        <div class="small text-muted lot-warehouse d-md-none mt-1">
                                            @if ($wh?->code)
                                                <span class="lot-card-badge">{{ $wh->code }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-semibold mono">
                                            {{ number_format($row->qty_balance, 2, ',', '.') }}
                                        </div>
                                        @if ($wh?->code)
                                            <div class="small text-muted d-none d-md-block">
                                                {{ $wh->code }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input lot-checkbox"
                                            name="selected_lots[]" value="{{ $row->lot_id }}">
                                        <span class="ms-1 small">Pakai LOT ini</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Footer info + tombol lanjut --}}
    <div class="d-flex justify-content-between align-items-center mt-3 lot-picker-footer">
        <div class="small text-muted">
            Setelah LOT dipilih, lanjutkan isi bundles di bawah. Sistem akan kunci 1 item/warna kain per Cutting Job.
        </div>
        <button type="button" class="btn btn-primary btn-sm btn-pill-sm" id="btn-confirm-lots">
            Simpan LOT &amp; Lanjut
        </button>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lotGrid = document.getElementById('lot-grid');
            const lotCards = lotGrid ? Array.from(lotGrid.querySelectorAll('.lot-card-item')) : [];
            const lotGroups = lotGrid ? Array.from(lotGrid.querySelectorAll('.lot-item-group')) : [];
            const btnSelectAll = document.getElementById('btn-select-all-lots');
            const btnUnselectAll = document.getElementById('btn-unselect-all-lots');
            const itemFilter = document.getElementById('lot-item-filter');

            function getCurrentItemId() {
                return itemFilter ? (itemFilter.value || '') : '';
            }

            function getCardsForCurrentSelection() {
                const selectedItemId = getCurrentItemId();

                return lotCards.filter(card => {
                    const cardItemId = card.getAttribute('data-item-id') || '';
                    // Jika dropdown pilih warna tertentu → hanya LOT dengan item_id itu.
                    if (selectedItemId) {
                        return cardItemId === selectedItemId && card.offsetParent !== null;
                    }
                    // Kalau "Semua warna" → pakai semua yang sedang terlihat.
                    return card.offsetParent !== null;
                });
            }

            function updateButtonsState(hasLots) {
                if (btnSelectAll) btnSelectAll.disabled = !hasLots;
                if (btnUnselectAll) btnUnselectAll.disabled = !hasLots;
            }

            function applyItemFilter() {
                if (!itemFilter || lotGroups.length === 0) {
                    updateButtonsState(lotCards.length > 0);
                    return;
                }

                const selectedItemId = getCurrentItemId();

                lotGroups.forEach(group => {
                    const itemId = group.getAttribute('data-item-id') || '';
                    const show = !selectedItemId || selectedItemId === itemId;
                    group.style.display = show ? '' : 'none';
                });

                const targetCards = getCardsForCurrentSelection();
                updateButtonsState(targetCards.length > 0);
            }

            // Klik card = toggle checkbox + highlight
            lotCards.forEach(card => {
                const checkbox = card.querySelector('.lot-checkbox');
                if (!checkbox) return;

                function syncCardState() {
                    card.classList.toggle('lot-selected', checkbox.checked);
                }

                card.addEventListener('click', function(e) {
                    if (e.target === checkbox) return;
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                });

                checkbox.addEventListener('change', syncCardState);

                syncCardState();
            });

            // tombol select all / unselect all
            if (btnSelectAll) {
                btnSelectAll.addEventListener('click', function() {
                    const targetCards = getCardsForCurrentSelection();
                    targetCards.forEach(card => {
                        const checkbox = card.querySelector('.lot-checkbox');
                        if (checkbox && !checkbox.checked) {
                            checkbox.checked = true;
                            checkbox.dispatchEvent(new Event('change', {
                                bubbles: true
                            }));
                        }
                    });
                });
            }

            if (btnUnselectAll) {
                btnUnselectAll.addEventListener('click', function() {
                    const targetCards = getCardsForCurrentSelection();
                    targetCards.forEach(card => {
                        const checkbox = card.querySelector('.lot-checkbox');
                        if (checkbox && checkbox.checked) {
                            checkbox.checked = false;
                            checkbox.dispatchEvent(new Event('change', {
                                bubbles: true
                            }));
                        }
                    });
                });
            }

            if (itemFilter) {
                itemFilter.addEventListener('change', applyItemFilter);
            }

            // initial state
            applyItemFilter();
        });
    </script>
@endpush
