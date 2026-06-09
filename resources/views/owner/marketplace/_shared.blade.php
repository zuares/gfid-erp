{{-- Shared CSS + JS helpers for all marketplace pages --}}
@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .prod-tab-loading {
            display: flex; align-items: center; justify-content: center;
            gap: .6rem; color: var(--gf-muted); font-size: .85rem; padding: 2.4rem 1rem;
        }
        .prod-tab-spinner {
            width: 18px; height: 18px; border-radius: 50%;
            border: 2px solid rgba(148,163,184,.35); border-top-color: #2563eb;
            animation: mpspin .7s linear infinite;
        }
        @keyframes mpspin { to { transform: rotate(360deg); } }

        /* KPI grid */
        .oc-kpi-grid {
            display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .65rem; margin-bottom: .9rem;
        }
        .oc-kpi-card {
            border: 1px solid rgba(15,23,42,.075); border-radius: 16px;
            padding: .82rem .9rem;
            background: linear-gradient(180deg, #fff 0%, #fcfcfd 100%);
        }
        .oc-kpi-label { color: #64748b; font-size: .66rem; font-weight: 950; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .18rem; }
        .oc-kpi-value { color: #0f172a; font-size: 1.25rem; font-weight: 950; line-height: 1.15; letter-spacing: -.02em; }
        .oc-kpi-note  { color: #94a3b8; font-size: .7rem; font-weight: 800; margin-top: .2rem; }

        /* Channel pill */
        .oc-channel-pill {
            display: inline-flex; align-items: center; gap: .3rem; font-size: .72rem; font-weight: 800;
            padding: .15rem .55rem; border-radius: 999px; background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; white-space: nowrap;
        }
        .oc-channel-shopee    { background: rgba(238,77,45,.08);   border-color: rgba(238,77,45,.2);   color: #b91c1c; }
        .oc-channel-tokopedia { background: rgba(0,177,79,.08);    border-color: rgba(0,177,79,.2);    color: #166534; }
        .oc-channel-tiktok    { background: rgba(0,0,0,.06);       border-color: rgba(0,0,0,.12);      color: #1e293b; }
        .oc-channel-lazada    { background: rgba(0,51,204,.07);    border-color: rgba(0,51,204,.18);   color: #1d4ed8; }
        .oc-channel-offline   { background: rgba(100,116,139,.08); border-color: rgba(100,116,139,.2); color: #475569; }

        /* Status badge */
        .oc-badge { display: inline-block; font-size: .68rem; font-weight: 800; padding: .14rem .5rem; border-radius: 999px; white-space: nowrap; }
        .oc-badge-green { background: rgba(34,197,94,.14);  color: #166534; }
        .oc-badge-amber { background: rgba(245,158,11,.16); color: #b45309; }
        .oc-badge-muted { background: rgba(148,163,184,.16);color: #64748b; }
        .oc-badge-red   { background: rgba(239,68,68,.14);  color: #b91c1c; }

        /* Rekomendasi chips */
        .oc-reco-chip {
            display: inline-flex; flex-direction: column; padding: .42rem .72rem; border-radius: 12px;
            border: 1.5px solid rgba(15,23,42,.1); background: #f8fafc; cursor: pointer;
            transition: border-color .15s, background .15s; text-align: left; max-width: 160px;
        }
        .oc-reco-chip:hover { border-color: #2563eb; background: #eff6ff; }
        .oc-reco-chip.is-selected { border-color: #16a34a; background: #f0fdf4; }
        .oc-reco-chip-code { font-size: .78rem; font-weight: 900; color: #0f172a; }
        .oc-reco-chip-name { font-size: .68rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* Empty state */
        .oc-empty { text-align: center; color: var(--gf-muted); font-size: .85rem; padding: 2.4rem 1rem; }

        @media (max-width: 576px) {
            .oc-kpi-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
@endpush

@push('scripts')
<script>
window.mpHelpers = (function () {
    function api(url, opts = {}) {
        return fetch(url, {
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', ...(opts.headers || {}) },
            ...opts,
        }).then(async r => {
            const p = await r.json().catch(() => ({ message: 'Response tidak valid.' }));
            if (!r.ok) throw new Error(p.message || p.error || 'API error');
            return p;
        });
    }

    function fmt(d) {
        if (!d) return '—';
        return new Date(d).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
    function fmtDate(d) {
        if (!d) return '—';
        return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    }
    function fmtRp(v) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v || 0);
    }
    function esc(s) { const d = document.createElement('div'); d.innerText = s ?? ''; return d.innerHTML; }

    function channelPill(ch) {
        if (!ch) return '<span class="oc-channel-pill">—</span>';
        const cls = { shopee: 'oc-channel-shopee', tokopedia: 'oc-channel-tokopedia', tiktok: 'oc-channel-tiktok', lazada: 'oc-channel-lazada', offline: 'oc-channel-offline' }[ch.code] || '';
        return `<span class="oc-channel-pill ${cls}">${esc(ch.name)}</span>`;
    }
    function statusBadge(s) {
        const map = { active: 'oc-badge-green', inactive: 'oc-badge-muted', expired: 'oc-badge-red', COMPLETED: 'oc-badge-green', CANCELLED: 'oc-badge-red', READY_TO_SHIP: 'oc-badge-amber', PROCESSED: 'oc-badge-amber', fulfilled: 'oc-badge-green', draft: 'oc-badge-muted', pending_review: 'oc-badge-amber', confirmed: 'oc-badge-green' };
        return `<span class="oc-badge ${map[s] || 'oc-badge-muted'}">${esc(s || '—')}</span>`;
    }
    function loading(id) { document.getElementById(id).innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>'; }

    return { api, fmt, fmtDate, fmtRp, esc, channelPill, statusBadge, loading };
})();
</script>
@endpush
