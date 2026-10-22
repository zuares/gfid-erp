import { navItems } from './designSystemConfig';

export default function DesignSystemSidebar({ activeSection, onNavigate }) {
    return (
        <aside className="ds-sidebar" aria-label="Design system navigation">
            <div className="ds-mark">
                <div className="ds-mark-icon" aria-hidden="true">G</div>
                <div><strong>GFID UI</strong><small>Design system · v0.1</small></div>
            </div>
            <div className="ds-nav-label">Explore</div>
            <nav className="ds-nav">
                {navItems.map((item) => (
                    <a
                        className={activeSection === item.id ? 'is-active' : ''}
                        href={`#${item.id}`}
                        key={item.id}
                        aria-current={activeSection === item.id ? 'page' : undefined}
                        onClick={() => onNavigate(item.id)}
                    >
                        <i className={`bi ${item.icon}`} aria-hidden="true" />
                        {item.label}
                    </a>
                ))}
            </nav>
            <div className="ds-sidebar-foot">
                <p><strong>Migration ready</strong><br />Setiap contoh punya token dan kontrak yang bisa dipindahkan ke halaman React berikutnya.</p>
            </div>
        </aside>
    );
}
