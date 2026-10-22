import { useId } from 'react';

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
