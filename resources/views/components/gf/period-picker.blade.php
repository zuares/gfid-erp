@props([
    'id' => 'gf-period-picker',
    'formId' => null,
    'dateFrom' => '',
    'dateTo' => '',
    'nameFrom' => 'date_from',
    'nameTo' => 'date_to',
    'inputIdPrefix' => 'gf-period-date',
    'activePreset' => 'custom',
    'activePresetLabel' => 'Rentang tanggal',
    'summary' => null,
    'label' => 'Periode Data',
    'class' => '',
])

@php
    $pickerId = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $id);
    $inputIdPrefix = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $inputIdPrefix);
    $dateSummary = $summary ?: ($dateFrom && $dateTo ? $dateFrom.' – '.$dateTo : 'Pilih rentang tanggal');
    $componentConfig = [
        'pickerId' => $pickerId,
        'formId' => $formId,
        'inputIdPrefix' => $inputIdPrefix,
    ];
@endphp

<input type="hidden" name="{{ $nameFrom }}" id="{{ $inputIdPrefix }}-from" value="{{ $dateFrom }}" data-gf-date="off">
<input type="hidden" name="{{ $nameTo }}" id="{{ $inputIdPrefix }}-to" value="{{ $dateTo }}" data-gf-date="off">

<div id="{{ $pickerId }}" class="gf-period-picker {{ $class }}" data-gf-period-picker>
    <button type="button" class="gf-period-trigger" data-gf-period-trigger aria-expanded="false" aria-controls="{{ $pickerId }}-popover">
        <span class="gf-period-trigger-label">{{ $label }}</span>
        <span class="gf-period-trigger-preset" data-gf-period-trigger-label>{{ $activePresetLabel }}</span>
        <span class="gf-period-trigger-summary" data-gf-period-trigger-summary>{{ $dateSummary }}</span>
        <i class="bi bi-calendar3 gf-period-trigger-icon" aria-hidden="true"></i>
    </button>

    <div id="{{ $pickerId }}-popover" class="gf-period-popover" data-gf-period-popover hidden>
        <div class="gf-period-layout">
            <div class="gf-period-menu" role="menu" aria-label="Pilihan periode">
                <button type="button" class="gf-period-option {{ $activePreset === 'today' ? 'is-selected' : '' }}" data-gf-period-preset="today" data-gf-period-label="Real-time">Real-time</button>
                <button type="button" class="gf-period-option {{ $activePreset === 'yesterday' ? 'is-selected' : '' }}" data-gf-period-preset="yesterday" data-gf-period-label="Kemarin">Kemarin</button>
                <button type="button" class="gf-period-option {{ $activePreset === 'last-7-days' ? 'is-selected' : '' }}" data-gf-period-preset="last-7-days" data-gf-period-label="7 hari sebelumnya">7 hari sebelumnya</button>
                <button type="button" class="gf-period-option {{ $activePreset === 'last-30-days' ? 'is-selected' : '' }}" data-gf-period-preset="last-30-days" data-gf-period-label="30 hari sebelumnya">30 hari sebelumnya</button>
                <div class="gf-period-divider"></div>
                <button type="button" class="gf-period-option" data-gf-period-view="day">Per Hari <span class="arrow">›</span></button>
                <button type="button" class="gf-period-option" data-gf-period-view="week">Per Minggu <span class="arrow">›</span></button>
                <button type="button" class="gf-period-option" data-gf-period-view="month">Per Bulan <span class="arrow">›</span></button>
                <button type="button" class="gf-period-option" data-gf-period-view="year">Berdasarkan Tahun <span class="arrow">›</span></button>
            </div>

            <div class="gf-period-panel" data-gf-period-panel="default">
                <div class="gf-period-eyebrow">Periode terpilih</div>
                <div class="gf-period-title" data-gf-period-detail-label>{{ $dateSummary }}</div>
                <p class="gf-period-help" data-gf-period-detail-help>Pilih preset di sebelah kiri atau tentukan rentang tanggal.</p>
                <button type="button" class="gf-period-range-action" data-gf-period-open-range>
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <span>Tentukan rentang tanggal</span>
                </button>
                <input id="{{ $inputIdPrefix }}-range" class="gf-period-range-input" type="text" value="{{ $dateFrom }} to {{ $dateTo }}" aria-hidden="true" tabindex="-1" data-gf-date="off">
            </div>

            <div class="gf-period-panel" data-gf-period-panel="day" hidden>
                <div class="gf-period-eyebrow">Per Hari</div>
                <div class="gf-period-title">Pilih hari</div>
                <div class="gf-period-calendar-wrap"><input id="{{ $inputIdPrefix }}-day" type="text" aria-label="Pilih hari" data-gf-date="off"></div>
            </div>

            <div class="gf-period-panel" data-gf-period-panel="week" hidden>
                <div class="gf-period-eyebrow">Per Minggu</div>
                <div class="gf-period-title">Pilih minggu</div>
                <div class="gf-period-calendar-wrap"><input id="{{ $inputIdPrefix }}-week" type="text" aria-label="Pilih minggu" data-gf-date="off"></div>
            </div>

            <div class="gf-period-panel" data-gf-period-panel="month" hidden>
                <div class="gf-period-nav"><button type="button" data-gf-period-month-prev aria-label="Tahun sebelumnya">‹</button><strong data-gf-period-month-year></strong><button type="button" data-gf-period-month-next aria-label="Tahun berikutnya">›</button></div>
                <div class="gf-period-grid" data-gf-period-month-grid></div>
            </div>

            <div class="gf-period-panel" data-gf-period-panel="year" hidden>
                <div class="gf-period-nav"><button type="button" data-gf-period-year-prev aria-label="Dekade sebelumnya">‹</button><strong data-gf-period-year-range></strong><button type="button" data-gf-period-year-next aria-label="Dekade berikutnya">›</button></div>
                <div class="gf-period-grid" data-gf-period-year-grid></div>
            </div>
        </div>
    </div>
</div>

@once
    @push('head')
        <style>
            .gf-period-picker {
                --gf-period-line: var(--line, #d4d7e3);
                --gf-period-muted: var(--muted, #6b7280);
                --gf-period-card: var(--card, #fff);
                --gf-period-soft: var(--card-soft, #f9fafb);
                --gf-period-ink: var(--text, #111827);
                --gf-period-accent: var(--accent, #2563eb);
                --gf-period-accent-soft: var(--accent-soft, #dbeafe);
                position: relative;
                width: min(100%, 340px);
                z-index: 20;
            }
            .gf-period-trigger {
                display: flex; align-items: center; gap: .35rem; width: 100%; min-height: 38px;
                padding: .45rem .6rem; border: 1px solid rgba(148,163,184,.38); border-radius: 9px;
                background: var(--gf-period-card); color: var(--gf-period-ink); text-align: left; cursor: pointer;
                transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
            }
            .gf-period-trigger:hover,
            .gf-period-trigger[aria-expanded="true"] {
                border-color: color-mix(in srgb, var(--gf-period-accent) 55%, transparent);
                box-shadow: 0 8px 20px rgba(15,23,42,.12); transform: translateY(-1px);
            }
            .gf-period-trigger-label { color: var(--gf-period-muted); font-size: .68rem; white-space: nowrap; }
            .gf-period-trigger-preset { color: var(--gf-period-accent); font-size: .71rem; font-weight: 800; white-space: nowrap; }
            .gf-period-trigger-summary { color: var(--gf-period-muted); font-size: .69rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .gf-period-trigger-icon { margin-left: auto; color: #64748b; font-size: .85rem; flex: 0 0 auto; }
            .gf-period-popover {
                position: absolute; left: 0; top: calc(100% + .45rem); width: min(100vw - 2rem, 480px);
                overflow: hidden; border: 1px solid rgba(148,163,184,.20); border-radius: 16px;
                background: var(--gf-period-card); box-shadow: 0 18px 45px rgba(15,23,42,.18), 0 1px 0 rgba(15,23,42,.04); z-index: 3000;
            }
            .gf-period-popover[hidden], .gf-period-panel[hidden] { display: none; }
            .gf-period-layout { display: grid; grid-template-columns: 150px minmax(0, 1fr); min-height: 0; }
            .gf-period-menu { padding: .45rem; border-right: 1px solid rgba(148,163,184,.17); background: var(--gf-period-card); }
            .gf-period-option {
                display: flex; align-items: center; justify-content: space-between; width: 100%; min-height: 32px;
                padding: .4rem .5rem; border: 0; border-radius: 7px; background: transparent; color: var(--gf-period-ink);
                font-size: .7rem; font-weight: 650; text-align: left; cursor: pointer; transition: background .14s ease, color .14s ease, transform .14s ease;
            }
            .gf-period-option:hover, .gf-period-option.is-selected {
                background: color-mix(in srgb, var(--gf-period-accent-soft) 72%, transparent);
                color: var(--gf-period-accent); transform: translateX(2px);
            }
            .gf-period-option .arrow { color: #94a3b8; font-size: .95rem; line-height: 1; }
            .gf-period-divider { height: 1px; margin: .35rem .2rem; background: rgba(148,163,184,.19); }
            .gf-period-panel { min-width: 0; padding: .75rem .8rem; background: color-mix(in srgb, var(--gf-period-soft) 60%, transparent); }
            .gf-period-eyebrow { color: var(--gf-period-accent); font-size: .6rem; font-weight: 850; letter-spacing: .08em; text-transform: uppercase; }
            .gf-period-title { margin: .35rem 0 0; color: var(--gf-period-ink); font-size: .95rem; font-weight: 850; letter-spacing: -.02em; }
            .gf-period-help { margin: .25rem 0 .7rem; color: var(--gf-period-muted); font-size: .74rem; line-height: 1.45; }
            .gf-period-range-action {
                display: flex; align-items: center; gap: .45rem; width: 100%; min-height: 36px;
                margin-top: .65rem; padding: .5rem .6rem; border: 1px solid rgba(148,163,184,.25);
                border-radius: 10px; background: var(--gf-period-card); color: var(--gf-period-ink); text-align: left;
                font-size: .72rem; font-weight: 700; cursor: pointer; transition: border-color .14s ease, box-shadow .14s ease, transform .14s ease;
            }
            .gf-period-range-action:hover { border-color: color-mix(in srgb, var(--gf-period-accent) 55%, transparent); box-shadow: 0 5px 14px rgba(15,23,42,.08); transform: translateY(-1px); }
            .gf-period-range-action i { color: var(--gf-period-accent); }
            .gf-period-range-input { display: none !important; }
            .gf-period-calendar-wrap { margin-top: .35rem; }
            .gf-period-calendar-wrap > input { display: none !important; }
            .gf-period-calendar-wrap .flatpickr-calendar { box-shadow: none !important; border: 0 !important; width: 320px !important; max-width: 100%; margin-inline: auto; }
            .gf-period-calendar-wrap .flatpickr-calendar.inline { display: block; }
            .gf-period-calendar-wrap .flatpickr-day.selected,
            .gf-period-calendar-wrap .flatpickr-day.startRange,
            .gf-period-calendar-wrap .flatpickr-day.endRange { background: var(--gf-period-accent) !important; border-color: var(--gf-period-accent) !important; }
            [data-gf-period-panel="week"] .flatpickr-day.gf-week-hover { background: color-mix(in srgb, var(--gf-period-accent-soft) 78%, transparent); border-color: transparent; color: var(--gf-period-accent); }
            [data-gf-period-panel="week"] .flatpickr-day.gf-week-hover-start { border-radius: 8px 0 0 8px; }
            [data-gf-period-panel="week"] .flatpickr-day.gf-week-hover-end { border-radius: 0 8px 8px 0; }
            [data-gf-period-panel="week"] .flatpickr-day.gf-week-hover.selected,
            [data-gf-period-panel="week"] .flatpickr-day.gf-week-hover.startRange,
            [data-gf-period-panel="week"] .flatpickr-day.gf-week-hover.endRange { color: #fff; }
            .gf-period-nav { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
            .gf-period-nav button { width: 26px; height: 26px; border: 0; border-radius: 7px; background: transparent; color: var(--gf-period-muted); cursor: pointer; }
            .gf-period-nav button:hover { background: rgba(148,163,184,.15); color: var(--gf-period-ink); }
            .gf-period-nav strong { color: var(--gf-period-ink); font-size: .8rem; }
            .gf-period-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .35rem; margin-top: .7rem; }
            .gf-period-grid button { min-height: 36px; border: 1px solid rgba(148,163,184,.19); border-radius: 7px; background: var(--gf-period-card); color: var(--gf-period-ink); font-size: .68rem; font-weight: 650; cursor: pointer; transition: all .14s ease; }
            .gf-period-grid button:hover, .gf-period-grid button.is-selected { border-color: color-mix(in srgb, var(--gf-period-accent) 42%, transparent); background: color-mix(in srgb, var(--gf-period-accent-soft) 72%, transparent); color: var(--gf-period-accent); transform: translateY(-1px); }
            .gf-period-grid button:disabled { cursor: not-allowed; color: #cbd5e1; background: rgba(148,163,184,.07); }
            body[data-theme="dark"] .gf-period-panel { background: rgba(15,23,42,.38); }
            body[data-theme="dark"] .gf-period-option:hover,
            body[data-theme="dark"] .gf-period-option.is-selected,
            body[data-theme="dark"] .gf-period-grid button:hover,
            body[data-theme="dark"] .gf-period-grid button.is-selected { color: var(--gf-period-accent); background: color-mix(in srgb, var(--gf-period-accent) 18%, transparent); }
            @media (max-width: 640px) {
                .gf-period-picker { width: 100%; }
                .gf-period-popover { width: min(100vw - 1.5rem, 480px); left: 50%; transform: translateX(-50%); }
                .gf-period-layout { grid-template-columns: 1fr; }
                .gf-period-menu { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .2rem; border-right: 0; border-bottom: 1px solid rgba(148,163,184,.17); }
                .gf-period-divider { grid-column: 1 / -1; }
            }
        </style>
    @endpush
@endonce

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const config = @json($componentConfig);
            const root = document.getElementById(config.pickerId);
            const form = config.formId ? document.getElementById(config.formId) : root?.closest('form');
            if (!root || !form || typeof window.flatpickr !== 'function') return;

            const fromInput = document.getElementById(config.inputIdPrefix + '-from');
            const toInput = document.getElementById(config.inputIdPrefix + '-to');
            const rangePicker = document.getElementById(config.inputIdPrefix + '-range');
            const dayPicker = document.getElementById(config.inputIdPrefix + '-day');
            const weekPicker = document.getElementById(config.inputIdPrefix + '-week');
            const trigger = root.querySelector('[data-gf-period-trigger]');
            const popover = root.querySelector('[data-gf-period-popover]');
            const panels = root.querySelectorAll('[data-gf-period-panel]');
            const triggerLabel = root.querySelector('[data-gf-period-trigger-label]');
            const triggerSummary = root.querySelector('[data-gf-period-trigger-summary]');
            const detailLabel = root.querySelector('[data-gf-period-detail-label]');
            const detailHelp = root.querySelector('[data-gf-period-detail-help]');
            const rangeAction = root.querySelector('[data-gf-period-open-range]');
            const monthGrid = root.querySelector('[data-gf-period-month-grid]');
            const monthYear = root.querySelector('[data-gf-period-month-year]');
            const yearGrid = root.querySelector('[data-gf-period-year-grid]');
            const yearRange = root.querySelector('[data-gf-period-year-range]');
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
            const locale = window.flatpickr.l10ns?.id || 'default';
            let monthViewYear = new Date().getFullYear();
            let yearViewStart = Math.floor(new Date().getFullYear() / 10) * 10;

            const dateToYmd = date => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
            const parseYmd = value => {
                if (!value) return null;
                const parts = value.split('-').map(Number);
                return parts.length === 3 ? new Date(parts[0], parts[1] - 1, parts[2]) : null;
            };
            const displayRange = (from, to) => from && to ? (from === to ? from : from + ' – ' + to) : 'Pilih rentang tanggal';
            const closePopover = () => {
                popover.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
                rangePicker?._flatpickr?.close();
            };
            const setView = view => {
                panels.forEach(panel => { panel.hidden = panel.dataset.gfPeriodPanel !== view; });
                root.querySelectorAll('[data-gf-period-view]').forEach(option => option.classList.toggle('is-selected', option.dataset.gfPeriodView === view));
                if (view !== 'default') rangePicker?._flatpickr?.close();
                if (detailHelp && view === 'default') detailHelp.textContent = 'Pilih preset di sebelah kiri atau tentukan rentang tanggal.';
            };
            const syncSummary = (label, from, to) => {
                const summary = displayRange(from, to);
                if (triggerLabel) triggerLabel.textContent = label;
                if (triggerSummary) triggerSummary.textContent = summary;
                if (detailLabel) detailLabel.textContent = summary;
                root.querySelectorAll('[data-gf-period-preset]').forEach(option => option.classList.toggle('is-selected', option.dataset.gfPeriodLabel === label));
            };
            const submitPeriod = (from, to, label, view = 'default') => {
                fromInput.value = from;
                toInput.value = to;
                syncSummary(label, from, to);
                setView(view);
                closePopover();
                form.requestSubmit();
            };
            const rangeForPreset = preset => {
                const end = new Date();
                const start = new Date(end);
                if (preset === 'yesterday') { start.setDate(start.getDate() - 1); end.setDate(end.getDate() - 1); }
                else if (preset === 'last-7-days') start.setDate(start.getDate() - 6);
                else if (preset === 'last-30-days') start.setDate(start.getDate() - 29);
                return [dateToYmd(start), dateToYmd(end)];
            };
            const weekBounds = date => {
                const start = new Date(date);
                start.setDate(start.getDate() - ((start.getDay() + 6) % 7));
                const end = new Date(start);
                end.setDate(end.getDate() + 6);
                return { start, end };
            };
            const clearWeekHover = instance => instance.calendarContainer.querySelectorAll('.gf-week-hover').forEach(day => day.classList.remove('gf-week-hover', 'gf-week-hover-start', 'gf-week-hover-end'));
            const paintWeekHover = (instance, date) => {
                const bounds = weekBounds(date);
                clearWeekHover(instance);
                instance.calendarContainer.querySelectorAll('.flatpickr-day').forEach(day => {
                    if (!day.dateObj || day.classList.contains('prevMonthDay') || day.classList.contains('nextMonthDay')) return;
                    const dayTime = day.dateObj.getTime();
                    if (dayTime < bounds.start.getTime() || dayTime > bounds.end.getTime()) return;
                    day.classList.add('gf-week-hover');
                    if (dayTime === bounds.start.getTime()) day.classList.add('gf-week-hover-start');
                    if (dayTime === bounds.end.getTime()) day.classList.add('gf-week-hover-end');
                });
            };

            let settingWeek = false;
            window.flatpickr(rangePicker, {
                mode: 'range', dateFormat: 'Y-m-d', altInput: false, locale, disableMobile: true,
                positionElement: rangeAction || trigger, defaultDate: [fromInput.value, toInput.value].filter(Boolean),
                onChange(dates, value, instance) {
                    if (dates.length === 2) submitPeriod(instance.formatDate(dates[0], 'Y-m-d'), instance.formatDate(dates[1], 'Y-m-d'), 'Rentang tanggal');
                },
            });
            window.flatpickr(dayPicker, {
                inline: true, dateFormat: 'Y-m-d', altInput: false, locale, disableMobile: true, defaultDate: fromInput.value,
                onChange(dates, value, instance) {
                    if (dates.length) { const day = instance.formatDate(dates[0], 'Y-m-d'); submitPeriod(day, day, 'Per Hari', 'day'); }
                },
            });
            window.flatpickr(weekPicker, {
                mode: 'range', inline: true, dateFormat: 'Y-m-d', altInput: false, locale, disableMobile: true,
                defaultDate: [fromInput.value, toInput.value].filter(Boolean),
                onReady(selectedDates, value, instance) {
                    instance.calendarContainer.addEventListener('pointerover', event => {
                        const day = event.target.closest('.flatpickr-day');
                        if (day?.dateObj) paintWeekHover(instance, day.dateObj);
                    });
                    instance.calendarContainer.addEventListener('pointerleave', () => clearWeekHover(instance));
                },
                onChange(dates, value, instance) {
                    if (!dates.length || settingWeek) return;
                    const bounds = weekBounds(dates[dates.length - 1]);
                    settingWeek = true;
                    instance.setDate([bounds.start, bounds.end], false);
                    settingWeek = false;
                    submitPeriod(dateToYmd(bounds.start), dateToYmd(bounds.end), 'Per Minggu', 'week');
                },
            });

            root.querySelectorAll('[data-gf-period-preset]').forEach(option => {
                option.addEventListener('mouseenter', () => setView('default'));
                option.addEventListener('click', () => { const range = rangeForPreset(option.dataset.gfPeriodPreset); submitPeriod(range[0], range[1], option.dataset.gfPeriodLabel); });
            });
            root.querySelectorAll('[data-gf-period-view]').forEach(option => {
                option.addEventListener('mouseenter', () => setView(option.dataset.gfPeriodView));
                option.addEventListener('click', () => setView(option.dataset.gfPeriodView));
            });
            const renderMonths = year => {
                monthViewYear = year;
                monthYear.textContent = String(year);
                monthGrid.replaceChildren();
                monthNames.forEach((name, month) => {
                    const button = document.createElement('button');
                    button.type = 'button'; button.textContent = name;
                    button.className = fromInput.value === dateToYmd(new Date(year, month, 1)) ? 'is-selected' : '';
                    button.addEventListener('click', () => submitPeriod(dateToYmd(new Date(year, month, 1)), dateToYmd(new Date(year, month + 1, 0)), 'Per Bulan', 'month'));
                    monthGrid.appendChild(button);
                });
            };
            const renderYears = start => {
                yearViewStart = start;
                yearRange.textContent = start + ' – ' + (start + 9);
                yearGrid.replaceChildren();
                const currentYear = new Date().getFullYear();
                for (let year = start; year <= start + 9; year += 1) {
                    const button = document.createElement('button');
                    button.type = 'button'; button.textContent = String(year); button.disabled = year > currentYear;
                    button.className = fromInput.value === dateToYmd(new Date(year, 0, 1)) ? 'is-selected' : '';
                    if (!button.disabled) button.addEventListener('click', () => submitPeriod(dateToYmd(new Date(year, 0, 1)), dateToYmd(new Date(year, 11, 31)), 'Berdasarkan Tahun', 'year'));
                    yearGrid.appendChild(button);
                }
            };
            root.querySelector('[data-gf-period-month-prev]')?.addEventListener('click', () => renderMonths(monthViewYear - 1));
            root.querySelector('[data-gf-period-month-next]')?.addEventListener('click', () => renderMonths(monthViewYear + 1));
            root.querySelector('[data-gf-period-year-prev]')?.addEventListener('click', () => renderYears(yearViewStart - 10));
            root.querySelector('[data-gf-period-year-next]')?.addEventListener('click', () => renderYears(yearViewStart + 10));
            renderMonths(parseYmd(fromInput.value)?.getFullYear() || new Date().getFullYear());
            renderYears(Math.floor((parseYmd(fromInput.value)?.getFullYear() || new Date().getFullYear()) / 10) * 10);

            trigger.addEventListener('click', () => {
                const willOpen = popover.hidden;
                popover.hidden = !willOpen;
                trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                if (!willOpen) rangePicker._flatpickr?.close();
                else setView('default');
            });
            rangeAction?.addEventListener('click', () => rangePicker._flatpickr?.open());
            document.addEventListener('click', event => {
                if (!root.contains(event.target) && !event.target.closest('.flatpickr-calendar')) closePopover();
            });
            document.addEventListener('keydown', event => { if (event.key === 'Escape') closePopover(); });
        });
    </script>
@endpush
