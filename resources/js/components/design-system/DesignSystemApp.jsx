import { useEffect, useState } from 'react';
import DesignSystemSidebar from './DesignSystemSidebar';
import { colorTokens, kpis, layoutTemplates, navItems, orders } from './designSystemConfig';
import { Alert, Badge, Button, LayoutPreview, Panel, SectionHeader, TokenSwatch } from './DesignSystemPrimitives';

const tabItems = [
    { id: 'summary', label: 'Summary', content: 'Summary menyajikan angka penting tanpa mengalihkan fokus pengguna.' },
    { id: 'activity', label: 'Activity', content: 'Activity memperlihatkan perubahan terbaru secara kronologis.' },
    { id: 'settings', label: 'Settings', content: 'Settings menampung preferensi yang jarang diubah.' },
];

export default function DesignSystemApp() {
    const [activeSection, setActiveSection] = useState('overview');
    const [isDarkPreview, setIsDarkPreview] = useState(false);
    const [activeTab, setActiveTab] = useState('summary');
    const [workspaceName, setWorkspaceName] = useState('Greatfit Operations');
    const [workspaceError, setWorkspaceError] = useState('');
    const [toast, setToast] = useState('');

    useEffect(() => {
        const sections = navItems.map(({ id }) => document.getElementById(id)).filter(Boolean);
        const observer = new IntersectionObserver(
            (entries) => {
                const visible = entries.filter((entry) => entry.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                if (visible) setActiveSection(visible.target.id);
            },
            { rootMargin: '-18% 0px -68% 0px', threshold: [0.1, 0.4, 0.8] },
        );

        sections.forEach((section) => observer.observe(section));
        return () => observer.disconnect();
    }, []);

    useEffect(() => {
        if (!toast) return undefined;
        const timer = window.setTimeout(() => setToast(''), 2600);
        return () => window.clearTimeout(timer);
    }, [toast]);

    const navigateTo = (section) => {
        setActiveSection(section);
        document.getElementById(section)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const validateWorkspace = () => {
        if (!workspaceName.trim()) {
            setWorkspaceError('Nama workspace wajib diisi.');
            return;
        }

        setWorkspaceError('');
        setToast('Perubahan berhasil disimpan.');
    };

    return (
        <div className={`ds-shell ${isDarkPreview ? 'is-dark-preview' : ''}`} id="design-system">
            <DesignSystemSidebar activeSection={activeSection} onNavigate={navigateTo} />

            <main className="ds-main">
                <section className="ds-hero" id="overview" aria-labelledby="overview-title">
                    <div className="ds-hero-grid">
                        <div>
                            <div className="ds-eyebrow"><span /> GFID operating system</div>
                            <h1 id="overview-title">One visual language for every flow.</h1>
                            <p>Ruang kerja untuk merancang, menguji, dan menyepakati komponen sebelum dipakai di halaman Laravel maupun React.</p>
                            <div className="ds-meta-row">
                                <span className="ds-chip"><i className="bi bi-check2-circle" aria-hidden="true" /> Accessible by default</span>
                                <span className="ds-chip"><i className="bi bi-braces" aria-hidden="true" /> Token-driven</span>
                                <span className="ds-chip"><i className="bi bi-phone" aria-hidden="true" /> Responsive</span>
                            </div>
                        </div>
                        <div className="ds-hero-actions">
                            <Button variant="ghost" icon="bi-moon-stars" onClick={() => setIsDarkPreview((value) => !value)}>
                                {isDarkPreview ? 'Light preview' : 'Preview mode'}
                            </Button>
                            <Button icon="bi-plus-lg" onClick={() => setToast('Komponen siap dipakai di halaman baru.')}>Add component</Button>
                        </div>
                    </div>
                </section>

                <section className="ds-section" id="foundations" aria-labelledby="foundations-title">
                    <SectionHeader id="foundations" title="Foundations" description="Token dasar yang menjadi sumber kebenaran visual." count="04 groups" />
                    <div className="ds-grid ds-grid-4">
                        {kpis.map((kpi) => (
                            <Panel className="ds-kpi" key={kpi.label}>
                                <small>{kpi.label}</small>
                                <strong>{kpi.value}</strong>
                                <p><i className={`bi ${kpi.icon}`} aria-hidden="true" /> {kpi.note}</p>
                            </Panel>
                        ))}
                    </div>
                    <div className="ds-grid ds-grid-2 ds-space-top">
                        <Panel title="Color roles" meta="color.*">
                            <div className="ds-grid ds-grid-4">
                                {colorTokens.map((token) => <TokenSwatch {...token} key={token.name} />)}
                            </div>
                        </Panel>
                        <Panel title="Typography scale" meta="type.*">
                            <div className="ds-type-sample"><small>display / 40 · 850</small><div className="ds-type-display">Make it clear.</div></div>
                            <div className="ds-type-sample"><small>body / 14 · 400</small><div className="ds-type-body">Good interfaces make the next action obvious.</div></div>
                            <div className="ds-type-sample"><small>caption / 11 · 650</small><div className="ds-type-caption">LAST SYNCED 2 MIN AGO</div></div>
                        </Panel>
                    </div>
                </section>

                <section className="ds-section" id="components" aria-labelledby="components-title">
                    <SectionHeader id="components" title="Components" description="Primitives yang harus terasa sama di seluruh domain." count="Atoms → molecules" />
                    <div className="ds-grid ds-grid-2">
                        <Panel title="Actions & status" meta="Button / Badge">
                            <div className="ds-action-row ds-action-row-spaced">
                                <Button icon="bi-lightning-charge-fill" onClick={() => setToast('Primary action berhasil dijalankan.')}>Primary</Button>
                                <Button variant="ghost">Secondary</Button>
                                <Button variant="dark" icon="bi-download">Export</Button>
                                <Button variant="danger" size="sm">Delete</Button>
                            </div>
                            <div className="ds-action-row">
                                <Badge tone="success"><i className="bi bi-check2" aria-hidden="true" /> Active</Badge>
                                <Badge tone="warning">Needs review</Badge>
                                <Badge tone="danger">Failed</Badge>
                                <Badge>Draft</Badge>
                            </div>
                        </Panel>
                        <Panel title="Feedback" meta="Alert / Toast">
                            <div className="ds-alert-stack">
                                <Alert tone="info"><strong>Heads up.</strong> Data akan diperbarui setelah sinkronisasi berikutnya.</Alert>
                                <Alert tone="success"><strong>All good.</strong> Perubahan berhasil disimpan.</Alert>
                            </div>
                        </Panel>
                    </div>
                    <div className="ds-grid ds-grid-2 ds-space-top">
                        <Panel title="Form controls" meta="Input / Select / Error">
                            <div className="ds-control">
                                <label htmlFor="ds-name">Workspace name</label>
                                <input id="ds-name" type="text" value={workspaceName} onChange={(event) => setWorkspaceName(event.target.value)} onBlur={() => workspaceName.trim() && setWorkspaceError('')} />
                                <span className="ds-hint">Nama ini terlihat oleh seluruh tim.</span>
                            </div>
                            <div className="ds-grid ds-grid-2">
                                <div className="ds-control"><label htmlFor="ds-module">Module</label><select id="ds-module" defaultValue="Marketplace"><option>Marketplace</option><option>Production</option><option>Finance</option></select></div>
                                <div className="ds-control"><label htmlFor="ds-invalid">Validation state</label><input id="ds-invalid" aria-invalid="true" defaultValue="Invalid value" /><span className="ds-error">Gunakan format yang benar.</span></div>
                            </div>
                            <Button size="sm" onClick={validateWorkspace}>Save example</Button>
                            {workspaceError && <p className="ds-form-error" role="alert">{workspaceError}</p>}
                        </Panel>
                        <Panel title="Navigation" meta="Tabs / Segmented">
                            <div className="ds-tabs" role="tablist" aria-label="Component navigation example">
                                {tabItems.map((tab) => (
                                    <button className={`ds-tab ${activeTab === tab.id ? 'is-active' : ''}`} type="button" role="tab" key={tab.id} id={`tab-${tab.id}`} aria-selected={activeTab === tab.id} aria-controls={`panel-${tab.id}`} onClick={() => setActiveTab(tab.id)}>{tab.label}</button>
                                ))}
                            </div>
                            {tabItems.map((tab) => activeTab === tab.id && <div className="ds-tab-panel is-active" role="tabpanel" id={`panel-${tab.id}`} aria-labelledby={`tab-${tab.id}`} key={tab.id}>{tab.content}</div>)}
                        </Panel>
                    </div>
                </section>

                <section className="ds-section" id="patterns" aria-labelledby="patterns-title">
                    <SectionHeader id="patterns" title="Patterns" description="Komposisi yang berulang untuk pekerjaan sehari-hari." count="04 patterns" />
                    <div className="ds-grid ds-grid-2">
                        <Panel title="Data table" meta="Table / Row actions">
                            <div className="ds-table-wrap">
                                <table className="ds-table"><caption className="sr-only">Example order table</caption><thead><tr><th scope="col">Order</th><th scope="col">Channel</th><th scope="col">Status</th><th scope="col">Total</th></tr></thead><tbody>{orders.map((order) => <tr key={order.id}><td><strong>{order.id}</strong></td><td>{order.channel}</td><td><Badge tone={order.tone}>{order.status}</Badge></td><td><strong>{order.total}</strong></td></tr>)}</tbody></table>
                            </div>
                        </Panel>
                        <Panel title="Empty & loading" meta="System states">
                            <div className="ds-grid ds-grid-2">
                                <div className="ds-empty"><div><div className="ds-empty-icon"><i className="bi bi-inbox" aria-hidden="true" /></div><strong>Belum ada data</strong><p>Mulai dengan menambahkan item pertama.</p><Button size="sm">Add item</Button></div></div>
                                <div className="ds-empty"><div className="ds-skeleton" role="status" aria-label="Loading"><span /><span /><span /></div><p className="ds-empty-loading-label">Loading state</p></div>
                            </div>
                        </Panel>
                    </div>
                </section>

                <section className="ds-section" id="layouts" aria-labelledby="layouts-title">
                    <SectionHeader id="layouts" title="Layouts" description="Kerangka halaman untuk menjaga ritme dan hierarchy tetap konsisten." count="03 templates" />
                    <div className="ds-grid ds-grid-3">
                        {layoutTemplates.map((layout) => <Panel key={layout.title}><LayoutPreview variant={layout.variant} /><div className="ds-layout-label">{layout.title}</div><div className="ds-layout-note">{layout.note}</div></Panel>)}
                    </div>
                    <Panel className="ds-token-panel">
                        <div className="ds-panel-title"><h3>Implementation tokens</h3><span>contract</span></div>
                        <div className="ds-token-row"><code>--gf-color-brand</code><span>#5B5CE2 · primary action</span></div>
                        <div className="ds-token-row"><code>--gf-radius-card</code><span>16px · cards and panels</span></div>
                        <div className="ds-token-row"><code>--gf-space-section</code><span>24px · section rhythm</span></div>
                        <div className="ds-token-row"><code>--gf-content-max</code><span>1380px · workspace canvas</span></div>
                    </Panel>
                </section>
            </main>

            <div className={`ds-toast ${toast ? 'is-visible' : ''}`} role="status" aria-live="polite"><i className="bi bi-check-circle-fill" aria-hidden="true" /><span>{toast}</span></div>
        </div>
    );
}
