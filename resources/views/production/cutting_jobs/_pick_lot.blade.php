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

        /* === FILTER WARNA === */
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
                max-width: 340px;
            }
        }

        @media (max-width: 575.98px) {
            .lot-picker-filter {
                margin-top: .35rem;
                width: 100%;
                border-radius: 10px;
            }
            .lot-picker-filter select.form-select {
                flex: 1;
                border-radius: 8px;
            }
        }

        /* === ACCORDION ITEM GROUP === */
        .lot-item-group {
            margin-top: .55rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.4);
            background: color-mix(in srgb, var(--card) 88%, rgba(59, 130, 246, 0.06));
            overflow: hidden;
            transition: border-color 0.15s;
        }

        .lot-item-group.has-selected {
            border-color: rgba(59, 130, 246, 0.6);
        }

        body[data-theme="dark"] .lot-item-group {
            background: color-mix(in srgb, var(--card) 90%, rgba(15, 23, 42, 0.9));
            border-color: rgba(148, 163, 184, 0.5);
        }

        body[data-theme="dark"] .lot-item-group.has-selected {
            border-color: rgba(99, 149, 246, 0.7);
        }

        /* === ACCORDION HEADER (clickable) === */
        .lot-accordion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            padding: .48rem .58rem;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .lot-accordion-toggle {
            border: 0;
            background: transparent;
            color: inherit;
            padding: 0;
            margin: 0;
            min-width: 0;
            flex: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            text-align: left;
            cursor: pointer;
        }

        .lot-accordion-header:hover,
        .lot-accordion-toggle:focus-visible {
            background: rgba(59, 130, 246, 0.04);
        }

        .lot-accordion-header-left {
            display: flex;
            flex-direction: column;
            gap: .08rem;
            min-width: 0;
        }

        .lot-accordion-header-right {
            display: flex;
            align-items: center;
            gap: .38rem;
            flex-shrink: 0;
        }

        .lot-group-check {
            display: inline-flex;
            align-items: center;
            font-size: .72rem;
            color: var(--muted);
            cursor: pointer;
        }

        .lot-group-check input {
            cursor: pointer;
        }

        .lot-item-name {
            font-size: .82rem;
            font-weight: 600;
            line-height: 1.18;
        }

        .lot-item-code-inline {
            font-weight: 800;
            margin-right: .35rem;
        }

        .lot-item-meta {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            flex-wrap: wrap;
            color: var(--muted);
            font-size: .72rem;
            line-height: 1.15;
        }

        /* Badge count LOT terpilih */
        .lot-selected-badge {
            display: none;
            font-size: .68rem;
            font-weight: 700;
            padding: .1rem .45rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.12);
            color: #1d4ed8;
            border: 1px solid rgba(37, 99, 235, 0.35);
            white-space: nowrap;
        }

        body[data-theme="dark"] .lot-selected-badge {
            background: rgba(99, 149, 246, 0.2);
            color: #93c5fd;
            border-color: rgba(99, 149, 246, 0.4);
        }

        .lot-selected-badge.visible {
            display: inline-flex;
        }

        /* Chevron */
        .lot-accordion-chevron {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            transition: transform 0.2s ease;
            color: rgba(100, 116, 139, 0.8);
        }

        .lot-item-group.open .lot-accordion-chevron {
            transform: rotate(180deg);
        }

        /* === ACCORDION BODY === */
        .lot-accordion-body {
            display: none;
            padding: 0 .65rem .6rem;
        }

        .lot-item-group.open .lot-accordion-body {
            display: block;
        }

        /* === LOT GRID === */
        .lot-grid {
            display: grid;
            gap: .5rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        @media (min-width: 992px) {
            .lot-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        /* === LOT CARD === */
        .lot-card-modern {
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.45);
            padding: .5rem .55rem .4rem;
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
                0 8px 20px rgba(15, 23, 42, 0.13),
                0 0 0 1px rgba(59, 130, 246, 0.3);
            border-color: rgba(59, 130, 246, 0.5);
            transform: translateY(-1px);
        }

        .lot-card-modern .lot-code {
            font-size: .78rem;
            letter-spacing: .02em;
        }

        .lot-card-modern .lot-balance {
            font-size: .82rem;
        }

        .lot-card-modern .lot-warehouse {
            font-size: .68rem;
        }

        .lot-card-modern .lot-purchase-date {
            font-size: .68rem;
            line-height: 1.2;
            white-space: nowrap;
        }

        .lot-card-modern.lot-selected {
            border-color: rgba(59, 130, 246, 0.9);
            box-shadow:
                0 8px 24px rgba(37, 99, 235, 0.16),
                0 0 0 1px rgba(59, 130, 246, 0.5);
            background: color-mix(in srgb, var(--card) 80%, rgba(59, 130, 246, 0.12));
        }

        .lot-row.lot-hidden {
            display: none;
        }

        .lot-card-badge {
            font-size: .65rem;
            padding: .04rem .4rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.6);
            background: rgba(148, 163, 184, 0.1);
        }

        .lot-card-check {
            margin-top: .45rem;
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

        /* === MOBILE OVERRIDES === */
        @media (max-width: 575.98px) {
            .lot-accordion-header {
                min-height: 54px;
                padding: .58rem .68rem;
                gap: .75rem;
            }

            .lot-accordion-toggle {
                min-height: 44px;
            }

            .lot-item-name {
                font-size: .86rem;
                line-height: 1.24;
            }

            .lot-item-meta {
                font-size: .74rem;
                gap: .42rem;
            }

            .lot-group-check {
                min-width: 44px;
                min-height: 44px;
                justify-content: center;
                border-radius: 999px;
                background: rgba(148, 163, 184, .1);
            }

            .lot-group-check input,
            .lot-checkbox {
                width: 1.28rem;
                height: 1.28rem;
            }

            .lot-grid {
                grid-template-columns: 1fr;
                gap: .58rem;
            }

            .lot-accordion-body {
                padding: 0 .58rem .68rem;
            }

            .lot-card-modern {
                min-height: 64px;
                padding: .62rem .7rem;
                border-radius: 12px;
            }

            .lot-card-main {
                align-items: center !important;
                gap: .7rem !important;
            }

            .lot-card-check {
                margin-top: 0;
                min-width: 44px;
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                background: rgba(148, 163, 184, .08);
            }

            .lot-card-check .form-check {
                min-height: auto;
                margin: 0;
            }

            .lot-picker-help {
                font-size: .78rem;
            }

            .btn-pill-sm {
                font-size: .75rem;
                padding-block: .2rem;
            }

            .lot-picker-footer {
                flex-direction: column;
                align-items: stretch;
                gap: .4rem;
            }

            .lot-picker-footer .btn-primary {
                width: 100%;
                justify-content: center;
            }

            .lot-picker-actions {
                margin-top: .25rem;
            }
        }
    </style>
@endpush

<div class="lot-picker-wrap" id="cutting-pick-lot">
    {{-- Header + filter warna + tombol select all --}}
    <div class="lot-picker-header">
        <div style="flex:1; min-width:0;">
            <div class="lot-picker-title">Pilih LOT Kain</div>
            <div class="lot-picker-help">
                Tap <strong>item kain</strong> untuk buka, lalu centang LOT yang mau dipakai.
            </div>

            @if (!$lotStocks->isEmpty())
                @php
                    $itemGroupsForSelect = $lotStocks->groupBy(fn($row) => $row->lot->item_id);
                @endphp

                <div class="lot-picker-filter">
                    <div class="lot-picker-filter-label">Warna Kain</div>
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
            <button type="button" class="btn btn-outline-secondary btn-pill-sm btn-sm" id="btn-select-all-lots" disabled>
                Centang semua
            </button>
            <button type="button" class="btn btn-outline-secondary btn-pill-sm btn-sm" id="btn-unselect-all-lots" disabled>
                Hapus centang
            </button>
        </div>
    </div>

    {{-- Accordion groups --}}
    @if ($lotStocks->isEmpty())
        <div id="lot-grid-hint" class="lot-empty-hint">
            Belum ada LOT bahan baku utama yang siap dipakai. Cek stok kain utama di GRN / gudang RM.
        </div>
    @else
        @php
            $groupedLots = $lotStocks->groupBy(fn($row) => $row->lot->item_id);
        @endphp

        <div id="lot-grid">
            @foreach ($groupedLots as $itemId => $rows)
                @php
                    $firstRow = $rows->first();
                    $lot      = $firstRow->lot;
                    $item     = $lot->item;
                    $totalBalance = $rows->sum('qty_balance');
                    $lotCount = $rows->count();
                    $whCodes  = $rows->pluck('warehouse.code')->filter()->unique()->values();
                @endphp

                <div class="lot-item-group" data-item-id="{{ $itemId }}">

                    {{-- ACCORDION HEADER --}}
                    <div class="lot-accordion-header">
                        <button type="button" class="lot-accordion-toggle">
                            <div class="lot-accordion-header-left">
                                <div class="lot-item-name">
                                    <span class="mono lot-item-code-inline">{{ $item->code }}</span>{{ $item->name }}
                                </div>
                                <div class="lot-item-meta">
                                    <span>{{ $lotCount }} LOT</span>
                                    <span class="mono">{{ number_format($totalBalance, 2, ',', '.') }} kg</span>
                                    @if ($whCodes->isNotEmpty())
                                        <span>{{ $whCodes->implode(', ') }}</span>
                                    @endif
                                    <span class="lot-selected-badge" id="badge-item-{{ $itemId }}"></span>
                                </div>
                            </div>
                            {{-- Chevron --}}
                            <svg class="lot-accordion-chevron" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="lot-accordion-header-right">
                            <label class="lot-group-check" title="Centang semua LOT item ini">
                                <input type="checkbox" class="form-check-input lot-group-checkbox">
                            </label>
                        </div>
                    </div>

                    {{-- ACCORDION BODY --}}
                    <div class="lot-accordion-body">
                        <div class="lot-grid">
                            @foreach ($rows as $row)
                                @php
                                    $lotRow = $row->lot;
                                    $wh     = $row->warehouse;
                                    $purchaseDateValue = $row->purchase_date ?: $row->first_in_date ?: $lotRow?->created_at;
                                    $purchaseDate = $purchaseDateValue ? \Illuminate\Support\Carbon::parse($purchaseDateValue) : null;
                                    $shortDays = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
                                @endphp

                                <div class="lot-card-modern lot-row lot-card-item"
                                    data-lot-id="{{ $row->lot_id }}"
                                    data-balance="{{ $row->qty_balance }}"
                                    data-item-id="{{ $item->id }}">

                                    <div class="d-flex justify-content-between align-items-start gap-1 lot-card-main">
                                        <div style="min-width:0;">
                                            <div class="fw-semibold mono lot-code" style="word-break:break-all;">
                                                {{ $lotRow->code }}
                                            </div>
                                            @if ($wh?->code)
                                                <div class="lot-warehouse text-muted mt-1">
                                                    <span class="lot-card-badge">{{ $wh->code }}</span>
                                                </div>
                                            @endif
                                            @if ($purchaseDate)
                                                <div class="lot-purchase-date text-muted mt-1">
                                                    Beli {{ $shortDays[$purchaseDate->dayOfWeek] }} {{ $purchaseDate->format('d/m/y') }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-end flex-shrink-0">
                                            <div class="fw-semibold mono lot-balance">
                                                {{ number_format($row->qty_balance, 2, ',', '.') }}
                                            </div>
                                            <div class="text-muted" style="font-size:.68rem;">kg</div>
                                        </div>
                                        <div class="lot-card-check">
                                            <div class="form-check">
                                            <input type="checkbox" class="form-check-input lot-checkbox"
                                                name="selected_lots[]" value="{{ $row->lot_id }}"
                                                data-item-id="{{ $item->id }}">
                                            <span class="visually-hidden">Pakai LOT ini</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                @php $isFirst = false; @endphp
            @endforeach
        </div>
    @endif

    {{-- Footer --}}
    <div class="d-flex justify-content-between align-items-center mt-3 lot-picker-footer">
        <div class="small text-muted">
            Setelah LOT dipilih, lanjutkan isi bundles di bawah.
        </div>
        <button type="button" class="btn btn-primary btn-sm" id="btn-confirm-lots">
            Simpan LOT &amp; Lanjut
        </button>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const lotGrid       = document.getElementById('lot-grid');
            const lotCards      = lotGrid ? Array.from(lotGrid.querySelectorAll('.lot-card-item')) : [];
            const lotGroups     = lotGrid ? Array.from(lotGrid.querySelectorAll('.lot-item-group')) : [];
            const btnSelectAll  = document.getElementById('btn-select-all-lots');
            const btnUnselectAll= document.getElementById('btn-unselect-all-lots');
            const itemFilter    = document.getElementById('lot-item-filter');

            /* ── ACCORDION ─────────────────────────────────────── */
            function openGroup(group) {
                group.classList.add('open');
            }

            function closeGroup(group) {
                group.classList.remove('open');
            }

            function toggleGroup(group) {
                group.classList.toggle('open');
            }

            // Bind click on header
            lotGroups.forEach(group => {
                const toggle = group.querySelector('.lot-accordion-toggle');
                if (!toggle) return;

                toggle.addEventListener('click', function () {
                    toggleGroup(group);
                });
            });

            /* ── BADGE: N LOT dipilih ───────────────────────────── */
            function updateGroupBadge(group) {
                const itemId = group.getAttribute('data-item-id');
                const badge  = document.getElementById('badge-item-' + itemId);
                const groupCheckbox = group.querySelector('.lot-group-checkbox');

                const children = Array.from(group.querySelectorAll('.lot-checkbox'));
                const checked = children.filter(cb => cb.checked).length;
                const total = children.length;

                if (groupCheckbox) {
                    groupCheckbox.checked = total > 0 && checked === total;
                    groupCheckbox.indeterminate = checked > 0 && checked < total;
                }

                if (!badge) return;
                if (checked > 0) {
                    badge.textContent  = checked + ' dipilih';
                    badge.classList.add('visible');
                    group.classList.add('has-selected');
                } else {
                    badge.textContent  = '';
                    badge.classList.remove('visible');
                    group.classList.remove('has-selected');
                }
            }

            function updateAllBadges() {
                lotGroups.forEach(updateGroupBadge);
            }

            lotGroups.forEach(group => {
                const groupCheckbox = group.querySelector('.lot-group-checkbox');
                if (!groupCheckbox) return;

                groupCheckbox.addEventListener('click', function (event) {
                    event.stopPropagation();
                });

                groupCheckbox.addEventListener('change', function () {
                    openGroup(group);
                    const shouldCheck = groupCheckbox.checked;
                    group.querySelectorAll('.lot-checkbox').forEach(cb => {
                        if (cb.checked !== shouldCheck) {
                            cb.checked = shouldCheck;
                            cb.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                    updateGroupBadge(group);
                });
            });

            /* ── CARD CLICK → toggle checkbox + highlight ─────── */
            lotCards.forEach(card => {
                const checkbox = card.querySelector('.lot-checkbox');
                if (!checkbox) return;

                function syncCardState() {
                    card.classList.toggle('lot-selected', checkbox.checked);
                    const group = card.closest('.lot-item-group');
                    if (group) updateGroupBadge(group);
                }

                card.addEventListener('click', function (e) {
                    if (e.target === checkbox) return;
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                });

                checkbox.addEventListener('change', syncCardState);
                syncCardState();
            });

            /* ── FILTER WARNA ────────────────────────────────────── */
            function getCurrentItemId() {
                return itemFilter ? (itemFilter.value || '') : '';
            }

            function getVisibleCards() {
                const selectedItemId = getCurrentItemId();
                return lotCards.filter(card => {
                    const cardItemId = card.getAttribute('data-item-id') || '';
                    if (selectedItemId) return cardItemId === selectedItemId;
                    return card.closest('.lot-item-group')?.style.display !== 'none';
                });
            }

            function updateButtonsState() {
                const visible = getVisibleCards();
                if (btnSelectAll)   btnSelectAll.disabled   = visible.length === 0;
                if (btnUnselectAll) btnUnselectAll.disabled = visible.length === 0;
            }

            function applyItemFilter() {
                const selectedItemId = getCurrentItemId();

                lotGroups.forEach(group => {
                    const itemId = group.getAttribute('data-item-id') || '';
                    const show   = !selectedItemId || selectedItemId === itemId;
                    group.style.display = show ? '' : 'none';

                    // Jika filter ke 1 item → auto buka accordion-nya
                    if (show && selectedItemId) {
                        openGroup(group);
                    }
                });

                updateButtonsState();
            }

            /* ── SELECT ALL / UNSELECT ALL ───────────────────────── */
            if (btnSelectAll) {
                btnSelectAll.addEventListener('click', function () {
                    const selectedItemId = getCurrentItemId();
                    lotGroups.forEach(group => {
                        const itemId = group.getAttribute('data-item-id') || '';
                        if (selectedItemId && itemId !== selectedItemId) return;
                        if (group.style.display === 'none') return;

                        // Auto-buka accordion supaya user tahu mana yang dipilih
                        openGroup(group);
                        group.querySelectorAll('.lot-checkbox').forEach(cb => {
                            if (!cb.checked) {
                                cb.checked = true;
                                cb.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        });
                    });
                });
            }

            if (btnUnselectAll) {
                btnUnselectAll.addEventListener('click', function () {
                    const selectedItemId = getCurrentItemId();
                    lotGroups.forEach(group => {
                        const itemId = group.getAttribute('data-item-id') || '';
                        if (selectedItemId && itemId !== selectedItemId) return;
                        if (group.style.display === 'none') return;

                        group.querySelectorAll('.lot-checkbox').forEach(cb => {
                            if (cb.checked) {
                                cb.checked = false;
                                cb.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        });
                    });
                });
            }

            if (itemFilter) {
                itemFilter.addEventListener('change', applyItemFilter);
            }

            // Init
            applyItemFilter();
            updateAllBadges();
        });
    </script>
@endpush
