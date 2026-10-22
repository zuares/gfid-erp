import { useEffect, useId, useRef, useState } from 'react';
import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';

export function SectionHeader({ id, title, description, count }) {
    return (
        <div className="ds-section-head">
            <div>
                <h2 id={`${id}-title`}>{title}</h2>
                <p>{description}</p>
            </div>
            <span className="ds-section-count">{count}</span>
        </div>
    );
}
export function Panel({ id, title, meta, children, className = '' }) {
    return (
        <article id={id} className={`ds-panel ${className}`.trim()}>
            {(title || meta) && (
                <div className="ds-panel-title">
                    <h3>{title}</h3>
                    {meta && <span>{meta}</span>}
                </div>
            )}
            {children}
        </article>
    );
}

export function ComparisonKpi({ title, value, description, comparisons = [] }) {
    return (
        <article className="ds-comparison-kpi" aria-label={title}>
            <div className="ds-comparison-kpi-main">
                <div>
                    <span className="ds-comparison-kpi-label">{title}</span>
                    <strong>{value}</strong>
                    <span className="ds-comparison-kpi-description">{description}</span>
                </div>
                <span className="ds-comparison-kpi-icon" aria-hidden="true"><i className="bi bi-graph-up-arrow" /></span>
            </div>
            <div className="ds-comparison-kpi-comparisons" aria-label="Perbandingan periode">
                {comparisons.map((comparison) => {
                    const direction = comparison.direction || 'up';
                    const icon = direction === 'down' ? 'bi-arrow-down-right' : direction === 'neutral' ? 'bi-dash' : 'bi-arrow-up-right';

                    return (
                        <div className="ds-comparison-kpi-row" key={`${comparison.label}-${comparison.period}`}>
                            <div>
                                <span>{comparison.label}</span>
                                <small>{comparison.period}</small>
                            </div>
                            <div className="ds-comparison-kpi-result">
                                <strong>{comparison.value}</strong>
                                <span className={`ds-comparison-kpi-change is-${direction}`}>
                                    <i className={`bi ${icon}`} aria-hidden="true" /> {comparison.change}
                                </span>
                            </div>
                        </div>
                    );
                })}
            </div>
        </article>
    );
}

export function Button({ children, variant = 'primary', size = 'normal', icon, onClick, type = 'button' }) {
    return (
        <button className={`ds-btn ds-btn-${variant} ${size === 'sm' ? 'ds-btn-sm' : ''}`.trim()} type={type} onClick={onClick}>
            {icon && <i className={`bi ${icon}`} aria-hidden="true" />}
            {children}
        </button>
    );
}

export function SearchInput({ id, label = 'Cari', placeholder = 'Cari…', value = '', onChange, onClear, resultCount, totalCount, itemLabel = 'hasil' }) {
    const generatedId = useId();
    const inputId = id || `ds-search-${generatedId.replace(/:/g, '')}`;
    const hasQuery = Boolean(value);
    const isFiltered = hasQuery && typeof totalCount === 'number';

    const selectAllOnFocus = (event) => {
        if (!event.currentTarget.value) return;
        window.requestAnimationFrame(() => event.currentTarget.select());
    };

    const selectAllOnMouseDown = (event) => {
        if (!event.currentTarget.value) return;
        event.currentTarget.focus();
        event.currentTarget.select();
        event.preventDefault();
    };

    return (
        <div className="ds-search-component">
            <label className="sr-only" htmlFor={inputId}>{label}</label>
            <div className="ds-search-control">
                <i className="bi bi-search ds-search-leading-icon" aria-hidden="true" />
                <input
                    id={inputId}
                    type="search"
                    value={value}
                    placeholder={placeholder}
                    autoComplete="off"
                    spellCheck="false"
                    onChange={(event) => onChange?.(event.target.value)}
                    onFocus={selectAllOnFocus}
                    onMouseDown={selectAllOnMouseDown}
                    aria-describedby={`${inputId}-meta`}
                />
                {hasQuery && <button type="button" className="ds-search-clear" onClick={onClear} aria-label="Hapus pencarian"><i className="bi bi-x-circle-fill" aria-hidden="true" /></button>}
            </div>
            <div className="ds-search-meta" id={`${inputId}-meta`} aria-live="polite">
                <span><strong>{typeof resultCount === 'number' ? resultCount : '—'}</strong>{isFiltered ? ` dari ${totalCount} ${itemLabel}` : ` ${itemLabel} tersedia`}</span>
                <span>{isFiltered ? 'Hasil pencarian' : 'Realtime'}</span>
            </div>
        </div>
    );
}

export function FlatpickrField({
    id,
    label,
    placeholder = 'Pilih tanggal',
    mode = 'single',
    enableTime = false,
    dateFormat = 'd M Y',
    rangeSeparator = ' – ',
    defaultDate,
    minDate,
    maxDate,
    disableWeekends = false,
    clearable = true,
    onChange,
}) {
    const generatedId = useId();
    const inputId = id || `ds-flatpickr-${generatedId.replace(/:/g, '')}`;
    const inputRef = useRef(null);
    const instanceRef = useRef(null);
    const onChangeRef = useRef(onChange);

    useEffect(() => {
        onChangeRef.current = onChange;
    }, [onChange]);

    useEffect(() => {
        if (!inputRef.current) return undefined;

        const locale = window.flatpickr?.l10ns?.id || Indonesian;
        const disable = disableWeekends ? [(date) => date.getDay() === 0 || date.getDay() === 6] : undefined;
        const instance = flatpickr(inputRef.current, {
            mode,
            enableTime,
            time_24hr: true,
            dateFormat,
            defaultDate,
            minDate,
            maxDate,
            ...(disable ? { disable } : {}),
            allowInput: true,
            disableMobile: true,
            locale,
            rangeSeparator,
            conjunction: rangeSeparator,
            onChange: (selectedDates, dateStr, flatpickrInstance) => onChangeRef.current?.(selectedDates, dateStr, flatpickrInstance),
        });

        instanceRef.current = instance;

        return () => {
            instance.destroy();
            instanceRef.current = null;
        };
    }, [dateFormat, defaultDate, disableWeekends, enableTime, maxDate, minDate, mode, rangeSeparator]);

    const clearValue = () => {
        instanceRef.current?.clear();
        inputRef.current?.focus();
    };

    return (
        <div className="ds-flatpickr-field">
            <label htmlFor={inputId}>{label}</label>
            <div className="ds-flatpickr-control">
                <i className="bi bi-calendar3 ds-flatpickr-icon" aria-hidden="true" />
                <input ref={inputRef} id={inputId} type="text" placeholder={placeholder} autoComplete="off" aria-label={label} />
                {clearable && <button type="button" className="ds-flatpickr-clear" onClick={clearValue} aria-label={`Hapus ${label}`} title={`Hapus ${label}`}><i className="bi bi-x-lg" aria-hidden="true" /></button>}
            </div>
        </div>
    );
}

function formatPeriodDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function parsePeriodDate(value) {
    if (value instanceof Date) return new Date(value.getFullYear(), value.getMonth(), value.getDate());
    if (!value) return new Date();
    const [year, month, day] = String(value).split('-').map(Number);
    return new Date(year, month - 1, day);
}

function periodDateKey(value) {
    const date = parsePeriodDate(value);
    return Date.UTC(date.getFullYear(), date.getMonth(), date.getDate());
}

function shiftPeriodDate(value, days) {
    const date = parsePeriodDate(value);
    date.setDate(date.getDate() + days);
    return formatPeriodDate(date);
}

function weekPeriodRange(value) {
    const date = parsePeriodDate(value);
    const day = date.getDay();
    const mondayOffset = day === 0 ? -6 : 1 - day;
    const start = new Date(date);
    start.setDate(date.getDate() + mondayOffset);

    return {
        start: formatPeriodDate(start),
        end: shiftPeriodDate(formatPeriodDate(start), 6),
    };
}

function clearPeriodWeekClass(calendar, className) {
    const relatedClasses = [className, `${className}-start`, `${className}-end`];
    relatedClasses.forEach((relatedClass) => {
        calendar?.querySelectorAll(`.${relatedClass}`).forEach((dayElement) => dayElement.classList.remove(relatedClass));
    });
}

function clearPeriodWeekHover(calendar) {
    clearPeriodWeekClass(calendar, 'ds-week-hover');
}

function highlightPeriodWeek(calendar, value, className = 'ds-week-hover') {
    if (!calendar || !value) return;

    const week = weekPeriodRange(value);
    const startTime = periodDateKey(week.start);
    const endTime = periodDateKey(week.end);
    const startClass = `${className}-start`;
    const endClass = `${className}-end`;

    clearPeriodWeekClass(calendar, className);
    calendar.querySelectorAll('.flatpickr-day').forEach((dayElement) => {
        const dayTime = dayElement.dateObj ? formatPeriodDate(dayElement.dateObj) : null;
        const dayValue = dayTime ? periodDateKey(dayTime) : null;
        const isInWeek = dayValue >= startTime && dayValue <= endTime;
        dayElement.classList.toggle(className, isInWeek);
        dayElement.classList.toggle(startClass, isInWeek && dayValue === startTime);
        dayElement.classList.toggle(endClass, isInWeek && dayValue === endTime);
    });
}

export function PeriodPicker({ id = 'ds-period-picker', label = 'Periode Data', defaultStart, defaultEnd, onChange }) {
    const today = formatPeriodDate(new Date());
    const initialStart = defaultStart || today;
    const initialEnd = defaultEnd || today;
    const [isOpen, setIsOpen] = useState(false);
    const [view, setView] = useState('default');
    const [presetLabel, setPresetLabel] = useState('Rentang tanggal');
    const [startDate, setStartDate] = useState(initialStart);
    const [endDate, setEndDate] = useState(initialEnd);
    const [monthCursor, setMonthCursor] = useState(() => parsePeriodDate(initialStart));
    const [yearCursor, setYearCursor] = useState(() => parsePeriodDate(initialStart).getFullYear());
    const wrapperRef = useRef(null);
    const triggerRef = useRef(null);
    const dayInputRef = useRef(null);
    const rangeInputRef = useRef(null);
    const weekInputRef = useRef(null);
    const selectedWeekRef = useRef(null);

    useEffect(() => {
        const handleOutsideClick = (event) => {
            if (!wrapperRef.current?.contains(event.target)) setIsOpen(false);
        };
        const handleEscape = (event) => {
            if (event.key === 'Escape') {
                setIsOpen(false);
                triggerRef.current?.focus();
            }
        };

        document.addEventListener('mousedown', handleOutsideClick);
        document.addEventListener('keydown', handleEscape);
        return () => {
            document.removeEventListener('mousedown', handleOutsideClick);
            document.removeEventListener('keydown', handleEscape);
        };
    }, []);

    useEffect(() => {
        if (!isOpen || !['day', 'range', 'week'].includes(view)) return undefined;

        const input = view === 'day'
            ? dayInputRef.current
            : view === 'week'
                ? weekInputRef.current
                : rangeInputRef.current;
        if (!input) return undefined;

        const picker = flatpickr(input, {
            inline: true,
            mode: view === 'range' ? 'range' : 'single',
            dateFormat: 'Y-m-d',
            defaultDate: view === 'range' ? [startDate, endDate] : view === 'day' ? startDate : undefined,
            disableMobile: true,
            locale: { ...Indonesian, firstDayOfWeek: 1 },
            rangeSeparator: ' – ',
            onDayCreate: (_selectedDates, _dateString, instance, dayElement) => {
                if (view !== 'week') return;
                dayElement.addEventListener('mouseenter', () => {
                    const preview = weekPeriodRange(dayElement.dateObj);
                    setStartDate(preview.start);
                    setEndDate(preview.end);
                    setPresetLabel('Per Minggu');
                    highlightPeriodWeek(instance.calendarContainer, dayElement.dateObj);
                });
                dayElement.addEventListener('mouseleave', () => clearPeriodWeekHover(instance.calendarContainer));
            },
            onReady: (_selectedDates, _dateString, instance) => {
                if (view === 'week' && selectedWeekRef.current) {
                    highlightPeriodWeek(instance.calendarContainer, selectedWeekRef.current, 'ds-week-selected');
                }
            },
            onChange: (selectedDates, _dateString, instance) => {
                if (!selectedDates.length) return;
                if (view === 'range') {
                    const nextStart = formatPeriodDate(selectedDates[0]);
                    const nextEnd = selectedDates[1] ? formatPeriodDate(selectedDates[1]) : nextStart;
                    setStartDate(nextStart);
                    setEndDate(nextEnd);
                    setPresetLabel('Rentang tanggal');
                    if (selectedDates.length === 2) onChange?.({ start: nextStart, end: nextEnd, label: 'Rentang tanggal' });
                    return;
                }

                if (view === 'week') {
                    const selected = formatPeriodDate(selectedDates[0]);
                    const week = weekPeriodRange(selected);
                    setStartDate(week.start);
                    setEndDate(week.end);
                    setPresetLabel('Per Minggu');
                    selectedWeekRef.current = week.start;
                    clearPeriodWeekClass(instance.calendarContainer, 'ds-week-selected');
                    instance.calendarContainer.querySelectorAll('.flatpickr-day.selected').forEach((dayElement) => dayElement.classList.remove('selected'));
                    highlightPeriodWeek(instance.calendarContainer, week.start, 'ds-week-selected');
                    onChange?.({ start: week.start, end: week.end, label: 'Per Minggu' });
                    return;
                }

                if (view === 'day') {
                    const selected = formatPeriodDate(selectedDates[0]);
                    setStartDate(selected);
                    setEndDate(selected);
                    setPresetLabel('Per Hari');
                    onChange?.({ start: selected, end: selected, label: 'Per Hari' });
                }
            },
        });

        return () => picker.destroy();
    }, [isOpen, view]);

    const applyPeriod = (start, end, nextLabel) => {
        setStartDate(start);
        setEndDate(end);
        setPresetLabel(nextLabel);
        setMonthCursor(parsePeriodDate(start));
        setYearCursor(parsePeriodDate(start).getFullYear());
        onChange?.({ start, end, label: nextLabel });
    };

    const applyPreset = (preset) => {
        if (preset === 'today') applyPeriod(today, today, 'Real-time');
        if (preset === 'yesterday') {
            const yesterday = shiftPeriodDate(today, -1);
            applyPeriod(yesterday, yesterday, 'Kemarin');
        }
        if (preset === 'last-7-days') applyPeriod(shiftPeriodDate(today, -6), today, '7 hari sebelumnya');
        if (preset === 'last-30-days') applyPeriod(shiftPeriodDate(today, -29), today, '30 hari sebelumnya');
        setView('default');
    };

    const setPickerView = (nextView) => {
        if (nextView === 'week') {
            const week = weekPeriodRange(selectedWeekRef.current || startDate);
            setStartDate(week.start);
            setEndDate(week.end);
            setPresetLabel('Per Minggu');
        }
        setView(nextView);
        if (nextView === 'month') setMonthCursor(parsePeriodDate(startDate));
        if (nextView === 'year') setYearCursor(parsePeriodDate(startDate).getFullYear());
    };

    const selectMonth = (month) => {
        const year = monthCursor.getFullYear();
        const monthStart = formatPeriodDate(new Date(year, month, 1));
        const monthEnd = formatPeriodDate(new Date(year, month + 1, 0));
        applyPeriod(monthStart, monthEnd, 'Per Bulan');
    };

    const selectYear = (year) => {
        applyPeriod(`${year}-01-01`, `${year}-12-31`, 'Berdasarkan Tahun');
    };

    const handleTriggerClick = () => {
        if (isOpen) {
            setIsOpen(false);
            return;
        }

        setView('range');
        setIsOpen(true);
    };

    const yearStart = Math.floor(yearCursor / 10) * 10;
    const monthNames = Array.from({ length: 12 }, (_, index) => new Intl.DateTimeFormat('id-ID', { month: 'short' }).format(new Date(2026, index, 1)));

    return (
        <div className="ds-period-picker" ref={wrapperRef}>
            <button
                ref={triggerRef}
                type="button"
                className="ds-period-trigger"
                aria-expanded={isOpen}
                aria-controls={`${id}-popover`}
                onClick={handleTriggerClick}
            >
                <span className="ds-period-trigger-label">{label}</span>
                <span className="ds-period-trigger-preset">{presetLabel}</span>
                <span className="ds-period-trigger-summary">{startDate} – {endDate}</span>
                <i className="bi bi-calendar3 ds-period-trigger-icon" aria-hidden="true" />
            </button>

            <div id={`${id}-popover`} className="ds-period-popover" hidden={!isOpen}>
                <div className="ds-period-layout">
                    <div className="ds-period-menu" role="menu" aria-label="Pilihan periode">
                        <button type="button" className="ds-period-option" onClick={() => applyPreset('today')}>Real-time</button>
                        <button type="button" className="ds-period-option" onClick={() => applyPreset('yesterday')}>Kemarin</button>
                        <button type="button" className="ds-period-option" onClick={() => applyPreset('last-7-days')}>7 hari sebelumnya</button>
                        <button type="button" className="ds-period-option" onClick={() => applyPreset('last-30-days')}>30 hari sebelumnya</button>
                        <div className="ds-period-divider" />
                        <button type="button" className={`ds-period-option ${view === 'day' ? 'is-active' : ''}`.trim()} onClick={() => setPickerView('day')}>Per Hari <span className="ds-period-arrow">›</span></button>
                        <button type="button" className={`ds-period-option ${view === 'week' ? 'is-active' : ''}`.trim()} onClick={() => setPickerView('week')}>Per Minggu <span className="ds-period-arrow">›</span></button>
                        <button type="button" className={`ds-period-option ${view === 'month' ? 'is-active' : ''}`.trim()} onClick={() => setPickerView('month')}>Per Bulan <span className="ds-period-arrow">›</span></button>
                        <button type="button" className={`ds-period-option ${view === 'year' ? 'is-active' : ''}`.trim()} onClick={() => setPickerView('year')}>Berdasarkan Tahun <span className="ds-period-arrow">›</span></button>
                    </div>

                    {view === 'default' && (
                        <div className="ds-period-panel">
                            <div className="ds-period-eyebrow">Periode terpilih</div>
                            <div className="ds-period-title">{startDate} – {endDate}</div>
                            <p className="ds-period-help">Pilih preset di sebelah kiri atau tentukan rentang tanggal.</p>
                            <button type="button" className="ds-period-range-action" onClick={() => setPickerView('range')}>
                                <i className="bi bi-calendar3" aria-hidden="true" />
                                <span>Tentukan rentang tanggal</span>
                            </button>
                        </div>
                    )}

                    {view === 'day' && (
                        <div className="ds-period-panel">
                            <div className="ds-period-eyebrow">Per Hari</div>
                            <div className="ds-period-title">Pilih hari</div>
                            <div className="ds-period-calendar-wrap"><input ref={dayInputRef} type="text" aria-label="Pilih hari" tabIndex={-1} /></div>
                        </div>
                    )}

                    {view === 'range' && (
                        <div className="ds-period-panel">
                            <div className="ds-period-eyebrow">Rentang tanggal</div>
                            <div className="ds-period-title">Pilih periode</div>
                            <p className="ds-period-help">Pilih tanggal mulai dan tanggal akhir secara bebas.</p>
                            <div className="ds-period-calendar-wrap"><input ref={rangeInputRef} type="text" aria-label="Pilih rentang tanggal" tabIndex={-1} /></div>
                        </div>
                    )}

                    {view === 'week' && (
                        <div className="ds-period-panel">
                            <div className="ds-period-eyebrow">Per Minggu</div>
                            <div className="ds-period-title">Pilih minggu</div>
                            <p className="ds-period-help">Arahkan kursor untuk melihat rentang, lalu klik satu tanggal.</p>
                            <div className="ds-period-calendar-wrap"><input ref={weekInputRef} type="text" aria-label="Pilih minggu" tabIndex={-1} /></div>
                        </div>
                    )}

                    {view === 'month' && (
                        <div className="ds-period-panel">
                            <div className="ds-period-eyebrow">Per Bulan</div>
                            <div className="ds-period-nav"><button type="button" onClick={() => setMonthCursor(new Date(monthCursor.getFullYear() - 1, monthCursor.getMonth(), 1))} aria-label="Tahun sebelumnya">‹</button><strong>{monthCursor.getFullYear()}</strong><button type="button" onClick={() => setMonthCursor(new Date(monthCursor.getFullYear() + 1, monthCursor.getMonth(), 1))} aria-label="Tahun berikutnya">›</button></div>
                            <div className="ds-period-grid">{monthNames.map((month, index) => <button type="button" className={parsePeriodDate(startDate).getMonth() === index && parsePeriodDate(startDate).getFullYear() === monthCursor.getFullYear() ? 'is-selected' : ''} key={month} onClick={() => selectMonth(index)}>{month}</button>)}</div>
                        </div>
                    )}

                    {view === 'year' && (
                        <div className="ds-period-panel">
                            <div className="ds-period-eyebrow">Berdasarkan Tahun</div>
                            <div className="ds-period-nav"><button type="button" onClick={() => setYearCursor(yearCursor - 10)} aria-label="Dekade sebelumnya">‹</button><strong>{yearStart} – {yearStart + 9}</strong><button type="button" onClick={() => setYearCursor(yearCursor + 10)} aria-label="Dekade berikutnya">›</button></div>
                            <div className="ds-period-grid">{Array.from({ length: 10 }, (_, index) => yearStart + index).map((year) => <button type="button" className={parsePeriodDate(startDate).getFullYear() === year ? 'is-selected' : ''} disabled={year > new Date().getFullYear()} key={year} onClick={() => selectYear(year)}>{year}</button>)}</div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

export function Tabs({ idPrefix = 'ds-tabs', items = [], activeId, onChange, ariaLabel = 'Tabs', variant = 'primary' }) {
    const moveFocus = (index) => {
        const nextItem = items[index];
        if (!nextItem) return;
        onChange?.(nextItem.id);
        window.requestAnimationFrame(() => document.getElementById(`${idPrefix}-${nextItem.id}`)?.focus());
    };

    const handleKeyDown = (event, index) => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const nextIndex = event.key === 'Home'
            ? 0
            : event.key === 'End'
                ? items.length - 1
                : (index + (event.key === 'ArrowRight' ? 1 : -1) + items.length) % items.length;
        moveFocus(nextIndex);
    };

    return (
        <div className={`ds-tab-system ${variant === 'subtabs' ? 'is-subtabs' : ''}`.trim()} role="tablist" aria-label={ariaLabel}>
            {items.map((item, index) => {
                const isActive = activeId === item.id;
                return (
                    <button
                        className={`ds-tab-system-button ${isActive ? 'is-active' : ''}`.trim()}
                        type="button"
                        role="tab"
                        id={`${idPrefix}-${item.id}`}
                        key={item.id}
                        aria-selected={isActive}
                        tabIndex={isActive ? 0 : -1}
                        onClick={() => onChange?.(item.id)}
                        onKeyDown={(event) => handleKeyDown(event, index)}
                    >
                        {item.icon && <i className={`bi ${item.icon}`} aria-hidden="true" />}
                        {item.label}
                        {item.badge && <span className="ds-tab-system-badge">{item.badge}</span>}
                    </button>
                );
            })}
        </div>
    );
}

export function Badge({ children, tone = 'neutral' }) {
    return <span className={`ds-badge ds-badge-${tone}`}>{children}</span>;
}

export function Alert({ children, tone = 'info', icon }) {
    return (
        <div className={`ds-alert ds-alert-${tone}`} role={tone === 'danger' ? 'alert' : 'status'}>
            <i className={`bi ${icon || (tone === 'success' ? 'bi-check-circle-fill' : 'bi-info-circle-fill')}`} aria-hidden="true" />
            <div>{children}</div>
        </div>
    );
}

export function TokenSwatch({ name, value, className }) {
    return (
        <div className={`ds-swatch ${className}`}>
            <small>{name}</small>
            <strong>{value}</strong>
        </div>
    );
}

export function LayoutPreview({ variant }) {
    return (
        <div className={`ds-layout-card ds-layout-${variant}`} aria-hidden="true">
            <div className="ds-layout-top" />
            <div className="ds-layout-body">
                <div className="ds-layout-side" />
                <div className="ds-layout-content"><span /><span /><span /></div>
            </div>
        </div>
    );
}
