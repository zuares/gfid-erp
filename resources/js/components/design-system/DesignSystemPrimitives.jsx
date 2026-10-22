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

export function Panel({ title, meta, children, className = '' }) {
    return (
        <article className={`ds-panel ${className}`.trim()}>
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
