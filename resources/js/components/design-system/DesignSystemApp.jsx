import { useEffect, useState } from 'react';
import DesignSystemSidebar from './DesignSystemSidebar';
import CrudModal, { crudFieldCatalog, selectAllOnFocus, selectAllOnMouseDown } from './CrudModal';
import DataTable from './DataTable';
import { colorTokens, componentNavItems, kpis, layoutTemplates, navItems } from './designSystemConfig';
import { Alert, Badge, Button, ComparisonKpi, LayoutPreview, Panel, SearchInput, SectionHeader, Tabs, TokenSwatch } from './DesignSystemPrimitives';

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

const searchExamples = [
    { name: 'Opening stock bahan baku', meta: 'Inventory · Stock Opname', keywords: 'opening stock bahan baku rm hpp' },
    { name: 'Opening balance accounting', meta: 'Accounting · Opening Balance Batch', keywords: 'opening balance accounting kas piutang hutang ekuitas' },
    { name: 'Verifikasi trial balance', meta: 'Accounting · Trial Balance', keywords: 'verifikasi trial balance debit kredit jurnal' },
    { name: 'Mulai transaksi baru', meta: 'Operations · Cutting / Sewing / Shipment', keywords: 'transaksi baru cutting sewing finishing shipment' },
];

const productTabs = [
    { id: 'overview', label: 'Overview', icon: 'bi-grid-1x2' },
    { id: 'activity', label: 'Activity', icon: 'bi-activity', badge: '12' },
    { id: 'settings', label: 'Settings', icon: 'bi-sliders2' },
    { id: 'inventory', label: 'Inventory', icon: 'bi-boxes', badge: '24' },
    { id: 'reports', label: 'Reports', icon: 'bi-bar-chart-line' },
];

const productSubtabs = {
    overview: [
        { id: 'summary', label: 'Summary' },
        { id: 'performance', label: 'Performance' },
        { id: 'channels', label: 'Channels' },
    ],
    activity: [
        { id: 'recent', label: 'Recent' },
        { id: 'audit', label: 'Audit log' },
        { id: 'alerts', label: 'Alerts', badge: '3' },
    ],
    settings: [
        { id: 'general', label: 'General' },
        { id: 'access', label: 'Access' },
        { id: 'notifications', label: 'Notifications' },
    ],
    inventory: [
        { id: 'stock', label: 'Stock' },
        { id: 'warehouses', label: 'Warehouses' },
        { id: 'movements', label: 'Movements' },
    ],
    reports: [
        { id: 'sales', label: 'Sales' },
        { id: 'exports', label: 'Exports' },
        { id: 'scheduled', label: 'Scheduled' },
    ],
};

const tabPanelCopy = {
    overview: {
        summary: ['Summary', 'Ringkasan metrik utama untuk membantu pengguna memahami kondisi produk dalam sekali lihat.'],
        performance: ['Performance', 'Tampilkan tren, perbandingan periode, dan insight performa pada konteks tab yang sama.'],
        channels: ['Channels', 'Bandingkan kontribusi marketplace, website, dan channel penjualan lainnya.'],
    },
    activity: {
        recent: ['Recent activity', 'Urutkan perubahan terbaru secara kronologis dengan status dan actor yang jelas.'],
        audit: ['Audit log', 'Sediakan jejak perubahan yang dapat ditelusuri tanpa memenuhi halaman dengan detail sekunder.'],
        alerts: ['Alerts', 'Pisahkan notifikasi yang membutuhkan tindakan agar tidak tercampur dengan histori biasa.'],
    },
    settings: {
        general: ['General settings', 'Tempatkan preferensi dasar yang paling sering digunakan oleh pemilik workspace.'],
        access: ['Access control', 'Kelola role dan izin dalam konteks settings yang lebih spesifik.'],
        notifications: ['Notifications', 'Berikan kontrol notifikasi tanpa memindahkan pengguna ke halaman baru.'],
    },
    inventory: {
        stock: ['Stock overview', 'Pantau ketersediaan stok dan tandai item yang membutuhkan perhatian lebih awal.'],
        warehouses: ['Warehouses', 'Bandingkan distribusi stok antar gudang tanpa meninggalkan konteks inventory.'],
        movements: ['Stock movements', 'Telusuri perpindahan stok berdasarkan waktu, lokasi, dan sumber transaksi.'],
    },
    reports: {
        sales: ['Sales reports', 'Ringkas performa penjualan dalam periode yang dipilih dengan metrik yang mudah dipindai.'],
        exports: ['Export reports', 'Sediakan hasil laporan yang siap diunduh atau dibagikan ke tim terkait.'],
        scheduled: ['Scheduled reports', 'Atur laporan berkala agar insight penting datang tanpa pekerjaan manual berulang.'],
    },
};

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
    const [searchQuery, setSearchQuery] = useState('');
    const [activeProductTab, setActiveProductTab] = useState('overview');
    const [activeProductSubtab, setActiveProductSubtab] = useState('summary');

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

    const filteredSearchExamples = searchExamples.filter((item) => {
        const query = searchQuery.trim().toLowerCase();
        return !query || [item.name, item.meta, item.keywords].join(' ').toLowerCase().includes(query);
    });

    const handleProductTabChange = (tabId) => {
        setActiveProductTab(tabId);
        setActiveProductSubtab(productSubtabs[tabId][0].id);
    };

    const activeTabCopy = tabPanelCopy[activeProductTab][activeProductSubtab];

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
                    <div className="ds-grid ds-grid-2 ds-space-top">
                        <Panel id="component-search-input" title="Search input" meta="SearchInput">
                            <p className="ds-pattern-intro">Input pencarian reusable dengan select-all saat focus/click, clear action, result count, dan empty state.</p>
                            <SearchInput
                                id="ds-search-component-input"
                                label="Cari komponen"
                                placeholder="Cari komponen, menu, atau kata kunci…"
                                value={searchQuery}
                                onChange={setSearchQuery}
                                onClear={() => setSearchQuery('')}
                                resultCount={filteredSearchExamples.length}
                                totalCount={searchQuery.trim() ? searchExamples.length : undefined}
                                itemLabel="komponen"
                            />
                            <div className="ds-search-results" role="list" aria-label="Hasil pencarian komponen">
                                {filteredSearchExamples.map((item) => (
                                    <div className="ds-search-result" role="listitem" key={item.name}>
                                        <span className="ds-search-result-icon"><i className="bi bi-command" aria-hidden="true" /></span>
                                        <span><strong>{item.name}</strong><small>{item.meta}</small></span>
                                    </div>
                                ))}
                                {!filteredSearchExamples.length && (
                                    <div className="ds-search-empty" role="status"><i className="bi bi-search" aria-hidden="true" /><strong>Komponen tidak ditemukan</strong><span>Coba kata kunci lain atau hapus pencarian.</span></div>
                                )}
                            </div>
                            <div className="ds-search-hint"><i className="bi bi-lightning-charge-fill" aria-hidden="true" /> Klik input untuk memilih semua teks sebelum mengganti query.</div>
                        </Panel>
                        <Panel title="Search input contract" meta="props / states">
                            <div className="ds-search-contract">
                                <div><code>value</code><span>Query terkontrol dari parent.</span></div>
                                <div><code>onChange</code><span>Filter berjalan realtime saat mengetik.</span></div>
                                <div><code>onClear</code><span>Reset query dan fokus kembali ke input.</span></div>
                                <div><code>resultCount</code><span>Jumlah hasil visible untuk feedback.</span></div>
                                <div><code>empty state</code><span>Pesan saat tidak ada hasil.</span></div>
                            </div>
                        </Panel>
                    </div>
                    <div className="ds-grid ds-grid-2 ds-space-top">
                        <Panel id="component-tabs-subtabs" title="Tabs & subtabs" meta="Tabs / Subtabs">
                            <p className="ds-pattern-intro">Gunakan tab utama untuk mengganti konteks halaman, lalu subtab untuk detail di dalam konteks tersebut.</p>
                            <Tabs
                                idPrefix="ds-product-tabs"
                                items={productTabs}
                                activeId={activeProductTab}
                                onChange={handleProductTabChange}
                                ariaLabel="Product sections"
                            />
                            <div className="ds-tabs-connector" aria-hidden="true" />
                            <Tabs
                                idPrefix="ds-product-subtabs"
                                items={productSubtabs[activeProductTab]}
                                activeId={activeProductSubtab}
                                onChange={setActiveProductSubtab}
                                ariaLabel={`${activeProductTab} subsections`}
                                variant="subtabs"
                            />
                            <div className="ds-tab-preview-panel" role="tabpanel" aria-live="polite" aria-label={`${activeProductTab} ${activeProductSubtab}`}>
                                <span className="ds-preview-label">Active panel</span>
                                <strong>{activeTabCopy[0]}</strong>
                                <p>{activeTabCopy[1]}</p>
                            </div>
                            <div className="ds-search-hint"><i className="bi bi-keyboard" aria-hidden="true" /> Gunakan ← →, Home, atau End untuk berpindah tab melalui keyboard.</div>
                        </Panel>
                        <Panel title="Tabs hierarchy" meta="interaction contract">
                            <div className="ds-tab-hierarchy">
                                <div className="ds-tab-hierarchy-row is-primary"><i className="bi bi-layout-three-columns" aria-hidden="true" /><span><strong>Primary tab</strong><small>Mengganti konteks utama halaman.</small></span><code>role=tablist</code></div>
                                <div className="ds-tab-hierarchy-branch" aria-hidden="true"><span /></div>
                                <div className="ds-tab-hierarchy-row is-sub"><i className="bi bi-layout-text-sidebar-reverse" aria-hidden="true" /><span><strong>Subtab</strong><small>Memecah detail tanpa berpindah halaman.</small></span><code>variant=subtabs</code></div>
                                <div className="ds-tab-hierarchy-branch" aria-hidden="true"><span /></div>
                                <div className="ds-tab-hierarchy-row is-panel"><i className="bi bi-window" aria-hidden="true" /><span><strong>Panel content</strong><small>Konten mengikuti kombinasi tab aktif.</small></span><code>aria-live</code></div>
                            </div>
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
