export const navItems = [
    { id: 'overview', label: 'Overview', icon: 'bi-grid-1x2-fill', keywords: ['intro', 'overview'] },
    { id: 'foundations', label: 'Foundations', icon: 'bi-palette-fill', keywords: ['token', 'color', 'typography'] },
    { id: 'components', label: 'Components', icon: 'bi-boxes', keywords: ['button', 'badge', 'alert', 'toast', 'input', 'select', 'tabs', 'navigation', 'form'] },
    { id: 'crud-modal', label: 'CRUD modal', icon: 'bi-window-stack', keywords: ['modal', 'dialog', 'create', 'edit', 'delete', 'combobox'] },
    { id: 'patterns', label: 'Patterns', icon: 'bi-layout-text-window-reverse', keywords: ['table', 'datatable', 'empty state', 'loading', 'responsive'] },
    { id: 'layouts', label: 'Layouts', icon: 'bi-columns-gap', keywords: ['workspace', 'dashboard', 'detail', 'responsive'] },
];

export const componentNavItems = [
    { id: 'foundation-color-roles', parent: 'foundations', label: 'Color roles', icon: 'bi-droplet', keywords: ['color', 'brand', 'surface'] },
    { id: 'foundation-typography', parent: 'foundations', label: 'Typography scale', icon: 'bi-type', keywords: ['type', 'font', 'text'] },
    { id: 'component-actions-status', parent: 'components', label: 'Actions & status', icon: 'bi-lightning-charge', keywords: ['button', 'badge', 'action', 'status'] },
    { id: 'component-kpi-comparison', parent: 'components', label: 'KPI comparison', icon: 'bi-graph-up-arrow', keywords: ['kpi', 'metric', 'net orders', 'comparison', 'dod', 'mom'] },
    { id: 'component-feedback', parent: 'components', label: 'Feedback', icon: 'bi-chat-square-text', keywords: ['alert', 'toast', 'feedback', 'notification'] },
    { id: 'component-form-controls', parent: 'components', label: 'Form controls', icon: 'bi-input-cursor-text', keywords: ['input', 'select', 'form', 'validation', 'error'] },
    { id: 'component-search-input', parent: 'components', label: 'Search input', icon: 'bi-search', keywords: ['search', 'filter', 'input', 'clear', 'select all'] },
    { id: 'component-tabs-subtabs', parent: 'components', label: 'Tabs & subtabs', icon: 'bi-layout-three-columns', keywords: ['tabs', 'subtabs', 'navigation', 'hierarchy', 'keyboard'] },
    { id: 'component-flatpickr', parent: 'components', label: 'Flatpickr fields', icon: 'bi-calendar3', keywords: ['flatpickr', 'date', 'range', 'datetime', 'calendar', 'input', 'field'] },
    { id: 'component-flatpickr-period', parent: 'components', label: 'Flatpickr period picker', icon: 'bi-calendar-range', keywords: ['flatpickr', 'period', 'preset', 'day', 'week', 'month', 'year', 'range'] },
    { id: 'component-navigation', parent: 'components', label: 'Navigation', icon: 'bi-signpost-split', keywords: ['tabs', 'segmented', 'navigation'] },
    { id: 'crud-product-form', parent: 'crud-modal', label: 'Product form', icon: 'bi-box-seam', keywords: ['create', 'edit', 'delete', 'product'] },
    { id: 'crud-field-choices', parent: 'crud-modal', label: 'Field choices', icon: 'bi-ui-checks-grid', keywords: ['field', 'input types', 'combobox'] },
    { id: 'crud-contract', parent: 'crud-modal', label: 'Reusable contract', icon: 'bi-braces', keywords: ['props', 'contract', 'api'] },
    { id: 'patterns-data-table', parent: 'patterns', label: 'Responsive data table', icon: 'bi-table', keywords: ['table', 'datatable', 'responsive', 'rows'] },
    { id: 'patterns-system-states', parent: 'patterns', label: 'Empty & loading', icon: 'bi-hourglass-split', keywords: ['empty', 'loading', 'skeleton', 'state'] },
    { id: 'patterns-data-table-contract', parent: 'patterns', label: 'DataTable contract', icon: 'bi-braces', keywords: ['columns', 'actions', 'empty state'] },
    { id: 'layout-workspace-shell', parent: 'layouts', label: 'Workspace shell', icon: 'bi-window-sidebar', keywords: ['sidebar', 'workspace'] },
    { id: 'layout-dashboard-overview', parent: 'layouts', label: 'Dashboard overview', icon: 'bi-speedometer2', keywords: ['dashboard', 'kpi', 'overview'] },
    { id: 'layout-list-detail', parent: 'layouts', label: 'List + detail', icon: 'bi-layout-split', keywords: ['list', 'detail', 'master'] },
    { id: 'layout-implementation-tokens', parent: 'layouts', label: 'Implementation tokens', icon: 'bi-braces-asterisk', keywords: ['token', 'css', 'implementation'] },
];

export const colorTokens = [
    { name: 'brand.primary', value: '#5B5CE2', className: 'is-brand' },
    { name: 'brand.accent', value: '#13B8C4', className: 'is-accent' },
    { name: 'content.strong', value: '#101828', className: 'is-ink' },
    { name: 'surface.muted', value: '#F2F4F7', className: 'is-muted' },
];

export const kpis = [
    { label: 'Components ready', value: '24', note: '+6 this sprint', icon: 'bi-arrow-up-right' },
    { label: 'Design tokens', value: '68', note: 'synced with CSS', icon: 'bi-check2' },
    { label: 'Coverage', value: '72%', note: 'target 90%', icon: 'bi-graph-up-arrow' },
    { label: 'Migration stage', value: '01', note: 'Blade-first', icon: 'bi-circle-half' },
];

export const orders = [
    { id: '#GF-10284', channel: 'Shopee', status: 'Paid', tone: 'success', total: 'Rp 1.240.000' },
    { id: '#GF-10283', channel: 'Website', status: 'Packing', tone: 'warning', total: 'Rp 865.000' },
    { id: '#GF-10282', channel: 'TikTok', status: 'Draft', tone: 'neutral', total: 'Rp 420.000' },
];

export const layoutTemplates = [
    { id: 'layout-workspace-shell', title: 'Workspace shell', note: 'Sidebar + top navigation + content canvas untuk modul utama.', variant: 'workspace' },
    { id: 'layout-dashboard-overview', title: 'Dashboard overview', note: 'KPI, insight, lalu detail data untuk keputusan cepat.', variant: 'dashboard' },
    { id: 'layout-list-detail', title: 'List + detail', note: 'Master list di kiri, konteks dan action di panel detail.', variant: 'detail' },
];
