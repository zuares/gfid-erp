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
