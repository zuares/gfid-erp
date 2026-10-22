export const navItems = [
    { id: 'overview', label: 'Overview', icon: 'bi-grid-1x2-fill' },
    { id: 'foundations', label: 'Foundations', icon: 'bi-palette-fill' },
    { id: 'components', label: 'Components', icon: 'bi-boxes' },
    { id: 'patterns', label: 'Patterns', icon: 'bi-layout-text-window-reverse' },
    { id: 'layouts', label: 'Layouts', icon: 'bi-columns-gap' },
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
    { title: 'Workspace shell', note: 'Sidebar + top navigation + content canvas untuk modul utama.', variant: 'workspace' },
    { title: 'Dashboard overview', note: 'KPI, insight, lalu detail data untuk keputusan cepat.', variant: 'dashboard' },
    { title: 'List + detail', note: 'Master list di kiri, konteks dan action di panel detail.', variant: 'detail' },
];
