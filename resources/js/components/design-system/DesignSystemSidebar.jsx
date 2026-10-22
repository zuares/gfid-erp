import { useEffect, useRef, useState } from 'react';
import { componentNavItems, navItems } from './designSystemConfig';

export default function DesignSystemSidebar({ activeSection, onNavigate }) {
    const [query, setQuery] = useState('');
    const searchInputRef = useRef(null);

    useEffect(() => {
        searchInputRef.current?.focus();
    }, []);

    const normalizedQuery = query.trim().toLowerCase();

    useEffect(() => {
        if (!normalizedQuery) return undefined;

        const searchableItems = [...componentNavItems, ...navItems];
        const target = searchableItems.find((item) => {
            const searchableText = [item.label, item.id, ...(item.keywords || [])].join(' ').toLowerCase();
            return searchableText.includes(normalizedQuery);
        });

        if (!target) return undefined;

        const timer = window.setTimeout(() => {
            document.getElementById(target.id)?.scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                block: 'start',
            });
        }, 180);

        return () => window.clearTimeout(timer);
    }, [normalizedQuery]);

    const visibleGroups = navItems.map((section) => {
        const sectionMatches = !normalizedQuery || [section.label, section.id].join(' ').toLowerCase().includes(normalizedQuery);
        const children = componentNavItems.filter((item) => item.parent === section.id);
        const visibleChildren = normalizedQuery && !sectionMatches
            ? children.filter((item) => [item.label, item.id, ...(item.keywords || [])].join(' ').toLowerCase().includes(normalizedQuery))
            : children;

        return { section, visibleChildren, isVisible: sectionMatches || visibleChildren.length > 0 };
    }).filter((group) => group.isVisible);

    return (
        <aside className="ds-sidebar" aria-label="Design system navigation">
            <div className="ds-mark">
                <div className="ds-mark-icon" aria-hidden="true">G</div>
                <div><strong>GFID UI</strong><small>Design system · v0.1</small></div>
            </div>
            <div className="ds-nav-label">Explore</div>
            <div className="ds-sidebar-search">
                <label className="sr-only" htmlFor="ds-component-search">Cari komponen</label>
                <i className="bi bi-search" aria-hidden="true" />
                <input
                    ref={searchInputRef}
                    id="ds-component-search"
                    type="search"
                    value={query}
                    placeholder="Cari komponen…"
                    autoComplete="off"
                    onChange={(event) => setQuery(event.target.value)}
                />
                {query && (
                    <button type="button" onClick={() => setQuery('')} aria-label="Hapus pencarian komponen">
                        <i className="bi bi-x-circle-fill" aria-hidden="true" />
                    </button>
                )}
            </div>
            <nav className="ds-nav">
                {visibleGroups.map(({ section, visibleChildren }) => (
                    <div className="ds-nav-group" key={section.id}>
                        <a
                            className={activeSection === section.id ? 'is-active' : ''}
                            href={`#${section.id}`}
                            aria-current={activeSection === section.id ? 'page' : undefined}
                            onClick={() => onNavigate(section.id)}
                        >
                            <i className={`bi ${section.icon}`} aria-hidden="true" />
                            {section.label}
                        </a>
                        {visibleChildren.length > 0 && (
                            <div className="ds-nav-children" aria-label={`${section.label} components`}>
                                {visibleChildren.map((item) => (
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
                            </div>
                        )}
                    </div>
                ))}
                {visibleGroups.length === 0 && (
                    <p className="ds-sidebar-search-empty">Komponen tidak ditemukan.</p>
                )}
            </nav>
            <div className="ds-sidebar-foot">
                <p><strong>Migration ready</strong><br />Setiap contoh punya token dan kontrak yang bisa dipindahkan ke halaman React berikutnya.</p>
            </div>
        </aside>
    );
}
