import { useEffect, useState } from 'react';
import DesignSystemSidebar from './DesignSystemSidebar';
import CrudModal, { crudFieldCatalog, selectAllOnFocus, selectAllOnMouseDown } from './CrudModal';
import DataTable from './DataTable';
import { colorTokens, componentNavItems, kpis, layoutTemplates, navItems } from './designSystemConfig';
import { Alert, Badge, Button, ComparisonKpi, LayoutPreview, Panel, SectionHeader, TokenSwatch } from './DesignSystemPrimitives';

const tabItems = [
    { id: 'summary', label: 'Summary', content: 'Summary menyajikan angka penting tanpa mengalihkan fokus pengguna.' },
    { id: 'activity', label: 'Activity', content: 'Activity memperlihatkan perubahan terbaru secara kronologis.' },
    { id: 'settings', label: 'Settings', content: 'Settings menampung preferensi yang jarang diubah.' },
];

const productFields = [
    { name: 'name', type: 'text', label: 'Nama produk', placeholder: 'Contoh: Active jacket', required: true, icon: 'bi-box-seam', autoFocus: true, clearable: true, hint: 'Gunakan nama yang mudah dicari tim.' },
    { name: 'sku', type: 'text', label: 'SKU', placeholder: 'GF-XXXX', required: true, icon: 'bi-upc-scan', clearable: true, autoComplete: 'off' },
    { name: 'category', type: 'combobox', label: 'Kategori', placeholder: 'Cari atau pilih kategori', required: true, icon: 'bi-grid-3x3-gap', options: [{ value: 'outerwear', label: 'Outerwear' }, { value: 'bottoms', label: 'Bottoms' }, { value: 'accessories', label: 'Accessories' }] },
    { name: 'price', type: 'currency', label: 'Harga jual', placeholder: '0', required: true, min: 0, clearable: true, hint: 'Harga dalam rupiah.' },
    { name: 'stock', type: 'number', label: 'Stok awal', placeholder: '0', min: 0, suffix: 'pcs', clearable: true, quickOptions: [{ value: 10, label: '10' }, { value: 24, label: '24' }, { value: 50, label: '50' }, { value: 100, label: '100' }] },
    { name: 'description', type: 'textarea', label: 'Deskripsi', placeholder: 'Tulis deskripsi singkat produk…', fullWidth: true, maxLength: 160, hint: 'Maksimal 160 karakter.' },
    { name: 'status', type: 'toggle', label: 'Status', toggleLabel: 'Aktif saat dibuat', defaultValue: true, fullWidth: true },
];

const salesTableColumns = [
    { key: 'product', label: 'Produk', mobile: 'title' },
    { key: 'sku', label: 'SKU', mobile: 'meta' },
    { key: 'orders', label: 'Pesanan', align: 'right' },
    { key: 'sales', label: 'Penjualan', align: 'right', render: (row) => <strong>{row.sales}</strong> },
    { key: 'status', label: 'Status', render: (row) => <Badge tone={row.tone}>{row.status}</Badge> },
];

const salesTableRows = [
    { id: 'active-jacket', product: 'Active jacket', sku: 'GF-AJ-001', orders: '128', sales: 'Rp 58.752.000', status: 'Naik 12,4%', tone: 'success' },
    { id: 'track-jacket', product: 'Track jacket', sku: 'GF-TJ-014', orders: '94', sales: 'Rp 32.430.000', status: 'Stabil', tone: 'neutral' },
    { id: 'active-pants', product: 'Active pants', sku: 'GF-AP-008', orders: '61', sales: 'Rp 18.910.000', status: 'Perlu review', tone: 'warning' },
];

export default function DesignSystemApp() {
    const [activeSection, setActiveSection] = useState('overview');
    const [isDarkPreview, setIsDarkPreview] = useState(false);
    const [activeTab, setActiveTab] = useState('summary');
    const [workspaceName, setWorkspaceName] = useState('Greatfit Operations');
    const [workspaceError, setWorkspaceError] = useState('');
    const [toast, setToast] = useState('');
    const [modalMode, setModalMode] = useState('create');
    const [modalOpen, setModalOpen] = useState(false);
    const [modalLoading, setModalLoading] = useState(false);

    useEffect(() => {
        const sections = [...navItems, ...componentNavItems].map(({ id }) => document.getElementById(id)).filter(Boolean);
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

    const openCrudModal = (mode) => {
        setModalMode(mode);
        setModalOpen(true);
    };

    const closeCrudModal = () => {
        if (modalLoading) return;
        setModalOpen(false);
    };

    const handleCrudSubmit = () => {
        setModalLoading(true);
        window.setTimeout(() => {
            setModalLoading(false);
            setModalOpen(false);
            setToast(modalMode === 'edit' ? 'Produk berhasil diperbarui.' : 'Produk baru berhasil ditambahkan.');
        }, 650);
    };

    const handleCrudDelete = () => {
        setModalLoading(true);
        window.setTimeout(() => {
            setModalLoading(false);
            setModalOpen(false);
            setToast('Produk berhasil dihapus.');
        }, 650);
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
                            <Button icon="bi-plus-lg" onClick={() => openCrudModal('create')}>Try CRUD modal</Button>
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
                        <Panel id="foundation-color-roles" title="Color roles" meta="color.*">
                            <div className="ds-grid ds-grid-4">
                                {colorTokens.map((token) => <TokenSwatch {...token} key={token.name} />)}
                            </div>
                        </Panel>
                        <Panel id="foundation-typography" title="Typography scale" meta="type.*">
                            <div className="ds-type-sample"><small>display / 40 · 850</small><div className="ds-type-display">Make it clear.</div></div>
                            <div className="ds-type-sample"><small>body / 14 · 400</small><div className="ds-type-body">Good interfaces make the next action obvious.</div></div>
                            <div className="ds-type-sample"><small>caption / 11 · 650</small><div className="ds-type-caption">LAST SYNCED 2 MIN AGO</div></div>
                        </Panel>
                    </div>
                </section>

                <section className="ds-section" id="components" aria-labelledby="components-title">
                    <SectionHeader id="components" title="Components" description="Primitives yang harus terasa sama di seluruh domain." count="Atoms → molecules" />
                    <div className="ds-grid ds-grid-2">
                        <Panel id="component-kpi-comparison" title="KPI comparison" meta="ComparisonKpi">
                            <ComparisonKpi
                                title="Net orders hari ini"
                                value="3"
                                description="Setelah pembatalan, refund, dan retur"
                                comparisons={[
                                    { label: 'Dibanding kemarin', period: 'Kemarin · 21 Oct', value: '1', change: '+200%' },
                                    { label: 'Tanggal yang sama', period: '22 Sep', value: '2', change: '+50%' },
                                ]}
                            />
                        </Panel>
                        <Panel title="KPI anatomy" meta="value / context / comparison">
                            <div className="ds-kpi-anatomy">
                                <div><strong>Primary value</strong><span>Angka utama untuk periode aktif.</span></div>
                                <div><strong>Data context</strong><span>Definisi metrik agar tidak ambigu.</span></div>
                                <div><strong>Period comparison</strong><span>DoD, WoW, atau tanggal yang sama.</span></div>
                            </div>
                        </Panel>
                    </div>
                    <div className="ds-grid ds-grid-2">
                        <Panel id="component-actions-status" title="Actions & status" meta="Button / Badge">
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
                        <Panel id="component-feedback" title="Feedback" meta="Alert / Toast">
                            <div className="ds-alert-stack">
                                <Alert tone="info"><strong>Heads up.</strong> Data akan diperbarui setelah sinkronisasi berikutnya.</Alert>
                                <Alert tone="success"><strong>All good.</strong> Perubahan berhasil disimpan.</Alert>
                            </div>
                        </Panel>
                    </div>
                    <div className="ds-grid ds-grid-2 ds-space-top">
                        <Panel id="component-form-controls" title="Form controls" meta="Input / Select / Error">
                            <div className="ds-control">
                                <label htmlFor="ds-name">Workspace name</label>
                                <div className="ds-crud-control">
                                    <i className="bi bi-building ds-crud-leading-icon" aria-hidden="true" />
                                    <input id="ds-name" type="text" value={workspaceName} onChange={(event) => setWorkspaceName(event.target.value)} onFocus={selectAllOnFocus} onMouseDown={selectAllOnMouseDown} onBlur={() => workspaceName.trim() && setWorkspaceError('')} />
                                    {workspaceName && <button className="ds-crud-clear" type="button" onClick={() => setWorkspaceName('')} aria-label="Kosongkan workspace name"><i className="bi bi-x-circle-fill" aria-hidden="true" /></button>}
                                </div>
                                <span className="ds-hint"><i className="bi bi-lightning-charge-fill" aria-hidden="true" /> Fokus input akan memilih semua teks · siap diganti.</span>
                            </div>
                            <div className="ds-grid ds-grid-2">
                                <div className="ds-control"><label htmlFor="ds-module">Module</label><select id="ds-module" defaultValue="Marketplace"><option>Marketplace</option><option>Production</option><option>Finance</option></select></div>
                                <div className="ds-control"><label htmlFor="ds-invalid">Validation state</label><div className="ds-crud-control"><i className="bi bi-shield-exclamation ds-crud-leading-icon" aria-hidden="true" /><input id="ds-invalid" aria-invalid="true" defaultValue="Invalid value" onFocus={selectAllOnFocus} onMouseDown={selectAllOnMouseDown} /></div><span className="ds-error">Gunakan format yang benar.</span></div>
                            </div>
                            <div className="ds-form-actions"><Button size="sm" onClick={validateWorkspace}>Save example</Button><Button size="sm" variant="ghost" icon="bi-window-stack" onClick={() => openCrudModal('create')}>Open CRUD modal</Button></div>
                            {workspaceError && <p className="ds-form-error" role="alert">{workspaceError}</p>}
                        </Panel>
                        <Panel id="component-navigation" title="Navigation" meta="Tabs / Segmented">
                            <div className="ds-tabs" role="tablist" aria-label="Component navigation example">
                                {tabItems.map((tab) => (
                                    <button className={`ds-tab ${activeTab === tab.id ? 'is-active' : ''}`} type="button" role="tab" key={tab.id} id={`tab-${tab.id}`} aria-selected={activeTab === tab.id} aria-controls={`panel-${tab.id}`} onClick={() => setActiveTab(tab.id)}>{tab.label}</button>
                                ))}
                            </div>
                            {tabItems.map((tab) => activeTab === tab.id && <div className="ds-tab-panel is-active" role="tabpanel" id={`panel-${tab.id}`} aria-labelledby={`tab-${tab.id}`} key={tab.id}>{tab.content}</div>)}
                        </Panel>
                    </div>
                </section>

                <section className="ds-section" id="crud-modal" aria-labelledby="crud-modal-title">
                    <SectionHeader id="crud-modal" title="CRUD modal" description="Satu pola reusable untuk create, edit, dan delete dengan field yang bisa dikonfigurasi." count="Modal / Form pattern" />
                    <div className="ds-crud-showcase">
                        <Panel id="crud-product-form" className="ds-crud-preview-panel">
                            <div className="ds-crud-preview-top"><div><span className="ds-preview-label">Interactive preview</span><h3>Product form</h3><p>Contoh form produk dengan validasi, status, dan feedback yang siap dihubungkan ke API.</p></div><span className="ds-preview-code">CrudModal</span></div>
                            <div className="ds-crud-preview-card"><div className="ds-crud-preview-card-head"><div className="ds-crud-mini-icon"><i className="bi bi-box-seam" aria-hidden="true" /></div><div><strong>Active jacket</strong><span>GF-AJ-001 · Outerwear</span></div><Badge tone="success">Active</Badge></div><div className="ds-crud-preview-lines"><span /><span /><span /></div><div className="ds-crud-preview-actions"><button className="ds-btn ds-btn-ghost ds-btn-sm" type="button" onClick={() => openCrudModal('edit')}><i className="bi bi-pencil" aria-hidden="true" /> Edit sample</button><button className="ds-btn ds-btn-danger ds-btn-sm" type="button" onClick={() => openCrudModal('delete')}><i className="bi bi-trash3" aria-hidden="true" /> Delete sample</button></div></div>
                            <div className="ds-action-row ds-crud-main-action"><Button icon="bi-plus-lg" onClick={() => openCrudModal('create')}>Create new product</Button><span className="ds-crud-shortcut"><kbd>Esc</kbd> untuk menutup modal</span></div>
                        </Panel>
                        <Panel id="crud-field-choices" title="Field choices" meta={`${crudFieldCatalog.length} options`}>
                            <p className="ds-catalog-intro">Pilih tipe input sesuai kebutuhan data. Semua field mendukung label, hint, required, disabled, dan pesan error.</p>
                            <div className="ds-field-catalog">{crudFieldCatalog.map((field) => <div className="ds-field-catalog-item" key={field.type}><div className="ds-catalog-icon"><i className={`bi ${({ text: 'bi-input-cursor-text', textarea: 'bi-textarea-t', select: 'bi-menu-button-wide', combobox: 'bi-search', currency: 'bi-cash-coin', number: 'bi-123', date: 'bi-calendar3', toggle: 'bi-toggle-on', checkbox: 'bi-check-square', radio: 'bi-ui-radios', file: 'bi-paperclip' })[field.type]}`} aria-hidden="true" /></div><div><strong>{field.label}</strong><span>{field.note}</span></div></div>)}</div>
                        </Panel>
                    </div>
                    <Panel id="crud-contract" className="ds-crud-anatomy"><div className="ds-panel-title"><h3>Reusable contract</h3><span>props</span></div><div className="ds-contract-row"><code>mode</code><span>create · edit · delete</span><em>required</em></div><div className="ds-contract-row"><code>fields</code><span>array of field definitions</span><em>required</em></div><div className="ds-contract-row"><code>onSubmit</code><span>values → API request</span><em>callback</em></div><div className="ds-contract-row"><code>loading</code><span>disable action + feedback</span><em>state</em></div></Panel>
                </section>

                <section className="ds-section" id="patterns" aria-labelledby="patterns-title">
                    <SectionHeader id="patterns" title="Patterns" description="Komposisi yang berulang untuk pekerjaan sehari-hari." count="04 patterns" />
                    <div className="ds-grid ds-grid-2">
                        <Panel id="patterns-data-table" title="Responsive data table" meta="DataTable" className="ds-pattern-main">
                            <p className="ds-pattern-intro">Field penting tetap terlihat. Detail sekunder otomatis menjadi kartu pada mobile, tanpa mengubah kontrak data.</p>
                            <DataTable caption="Ringkasan penjualan produk" columns={salesTableColumns} rows={salesTableRows} />
                            <div className="ds-table-contract" aria-label="DataTable contract">
                                <code>columns · rows · rowKey · render</code>
                                <span>Desktop table → mobile cards</span>
                            </div>
                        </Panel>
                        <Panel id="patterns-system-states" title="Empty & loading" meta="System states">
                            <div className="ds-grid ds-grid-2">
                                <div className="ds-empty"><div><div className="ds-empty-icon"><i className="bi bi-inbox" aria-hidden="true" /></div><strong>Belum ada data</strong><p>Mulai dengan menambahkan item pertama.</p><Button size="sm">Add item</Button></div></div>
                                <div className="ds-empty"><div className="ds-skeleton" role="status" aria-label="Loading"><span /><span /><span /></div><p className="ds-empty-loading-label">Loading state</p></div>
                            </div>
                        </Panel>
                    </div>
                    <Panel id="patterns-data-table-contract" className="ds-table-guidance">
                        <div className="ds-panel-title"><h3>DataTable contract</h3><span>portable props</span></div>
                        <div className="ds-contract-grid">
                            <div><code>columns</code><span>label, key, align, render, mobile role</span></div>
                            <div><code>rows</code><span>domain data yang sudah dinormalisasi</span></div>
                            <div><code>actions</code><span>row action yang touch-friendly</span></div>
                            <div><code>emptyState</code><span>state kosong yang dapat diakses</span></div>
                        </div>
                    </Panel>
                </section>

                <section className="ds-section" id="layouts" aria-labelledby="layouts-title">
                    <SectionHeader id="layouts" title="Layouts" description="Kerangka halaman untuk menjaga ritme dan hierarchy tetap konsisten." count="03 templates" />
                    <div className="ds-grid ds-grid-3">
                        {layoutTemplates.map((layout) => <Panel id={layout.id} key={layout.title}><LayoutPreview variant={layout.variant} /><div className="ds-layout-label">{layout.title}</div><div className="ds-layout-note">{layout.note}</div></Panel>)}
                    </div>
                    <Panel id="layout-implementation-tokens" className="ds-token-panel">
                        <div className="ds-panel-title"><h3>Implementation tokens</h3><span>contract</span></div>
                        <div className="ds-token-row"><code>--gf-color-brand</code><span>#5B5CE2 · primary action</span></div>
                        <div className="ds-token-row"><code>--gf-radius-card</code><span>16px · cards and panels</span></div>
                        <div className="ds-token-row"><code>--gf-space-section</code><span>24px · section rhythm</span></div>
                        <div className="ds-token-row"><code>--gf-content-max</code><span>1280px · workspace canvas</span></div>
                    </Panel>
                </section>
            </main>

            <CrudModal
                open={modalOpen}
                mode={modalMode}
                title={modalMode === 'delete' ? 'Hapus Active jacket?' : modalMode === 'edit' ? 'Edit product' : 'Tambah produk baru'}
                description={modalMode === 'delete' ? 'Anda akan menghapus record produk dari katalog.' : 'Lengkapi informasi di bawah untuk menjaga data katalog tetap rapi.'}
                fields={productFields}
                initialValues={{ name: 'Active jacket', sku: 'GF-AJ-001', category: 'outerwear', price: '459000', stock: '24', description: 'Jaket ringan untuk aktivitas harian.', status: true }}
                loading={modalLoading}
                onClose={closeCrudModal}
                onSubmit={handleCrudSubmit}
                onDelete={handleCrudDelete}
            />

            <div className={`ds-toast ${toast ? 'is-visible' : ''}`} role="status" aria-live="polite"><i className="bi bi-check-circle-fill" aria-hidden="true" /><span>{toast}</span></div>
        </div>
    );
}
