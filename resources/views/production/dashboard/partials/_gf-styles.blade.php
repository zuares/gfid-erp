<style>
    /* ============================================================
       gf design-system tokens + master header + panel + tables
       (ported from greatfit-project: inventory.css + admin.css)
       ============================================================ */
    :root {
        --gf-border: #e5e7eb;
        --gf-muted: #64748b;
        --gf-soft: #f8fafc;
        --gf-dark: #111827;
    }

    .gf-master-section { display: grid; gap: 1rem; }

    .gf-master-header {
        border: 1px solid var(--gf-border);
        border-radius: 16px;
        background: #fff;
        padding: 14px 18px;
        box-shadow: none;
    }

    .gf-master-header-layout {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .gf-master-header-copy { min-width: 200px; }

    .gf-master-eyebrow {
        color: var(--gf-muted);
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
        margin-bottom: 2px;
    }

    .gf-master-title {
        color: var(--gf-dark);
        font-size: 20px;
        line-height: 1.25;
        font-weight: 800;
        margin-bottom: 1px;
        letter-spacing: -.01em;
    }

    .gf-master-desc {
        color: var(--gf-muted);
        font-size: 12.5px;
        margin-bottom: 0;
        max-width: 860px;
    }

    .gf-master-actions { flex: 1 1 420px; }

    .gf-panel {
        border: 1px solid var(--gf-border);
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 12px 30px rgba(15, 23, 42, .05);
        overflow: hidden;
    }

    .gf-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 18px 20px;
        background: #fff;
        border-bottom: 1px solid #eef2f7;
    }

    .gf-panel-title { font-weight: 900; color: var(--gf-dark); }
    .gf-panel-body { padding: 22px; }
    .gf-subtext { color: var(--gf-muted); font-size: 12px; }

    /* Tables */
    .gf-table-scroll {
        max-height: calc(100vh - 290px);
        overflow: auto;
        -webkit-overflow-scrolling: touch;
    }

    .gf-sticky-table thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        box-shadow: 0 1px 0 #edf0f5;
    }

    .gf-sticky-table tfoot td {
        position: sticky;
        bottom: 0;
        z-index: 5;
    }

    /* Baris total di tfoot (mis. total upah borongan) */
    .gf-total-row td {
        background: #f8fafc;
        border-top: 2px solid #e4e7ec;
        color: #344054;
        font-weight: 700;
        box-shadow: 0 -1px 0 #edf0f5;
    }
    .gf-total-row b { color: #0f172a; }

    .gf-clean-table thead th {
        background: #fbfcfe;
        color: #667085;
        font-weight: 750;
    }

    .gf-clean-table tbody td { color: #344054; }

    /* Insight banner — dipakai semua tab */
    .gf-mpl-insight:empty { display: none; }
    .gf-mpl-insight {
        display: flex; align-items: flex-start; gap: 8px; flex-wrap: wrap;
        background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
        border: 1px solid rgba(15, 23, 42, .08); border-left: 3px solid #0f172a;
        border-radius: 12px; padding: 10px 14px; margin-bottom: 14px;
        font-size: 12.5px; color: #334155; line-height: 1.5;
    }
    .gf-mpl-insight b { color: #0f172a; }
    .gf-mpl-insight .pos { color: #16a34a; font-weight: 800; }
    .gf-mpl-insight .neg { color: #dc2626; font-weight: 800; }
    .gf-mpl-insight .warn { color: #d97706; font-weight: 800; }
    .gf-mpl-insight-ico { font-size: 15px; line-height: 1.3; flex-shrink: 0; }

    .gf-marketplace-dashboard,
    .gf-marketplace-clean-ui {
        display: grid;
        gap: 1rem;
        overflow: visible;
    }

    .gf-marketplace-clean-ui .gf-marketplace-sticky-head {
        position: sticky;
        top: 0;
        z-index: 1000;
        display: grid;
        gap: .65rem;
        padding: .6rem 0 .75rem;
        margin-top: -.25rem;
        background: linear-gradient(
            180deg,
            rgba(248, 250, 252, .98) 0%,
            rgba(248, 250, 252, .95) 82%,
            rgba(248, 250, 252, 0) 100%
        );
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border-bottom: 1px solid rgba(15, 23, 42, .04);
    }

    .gf-marketplace-filter-grid {
        display: grid;
        grid-template-columns: minmax(240px, 1fr) minmax(260px, 1.1fr) auto;
        gap: .65rem;
        align-items: end;
    }

    .gf-marketplace-clean-ui .form-label {
        color: #64748b;
        font-size: .73rem;
        font-weight: 900;
        letter-spacing: .02em;
        margin-bottom: .35rem;
    }

    .gf-marketplace-clean-ui .form-control,
    .gf-marketplace-clean-ui .form-select,
    .gf-marketplace-clean-ui .gf-form-control {
        min-height: 40px;
        border-radius: 14px;
        border-color: rgba(15, 23, 42, .10);
        font-size: .86rem;
        box-shadow: none;
    }

    .gf-marketplace-periods,
    .gf-marketplace-tabs,
    .gf-overview-marketplace-tabs {
        display: flex;
        gap: .32rem;
        flex-wrap: nowrap;
        width: 100%;
        max-width: 100%;
        padding: .28rem;
        overflow-x: auto;
        overflow-y: hidden;
        border-radius: 18px;
        background: rgba(255, 255, 255, .94);
        border: 1px solid rgba(15, 23, 42, .08);
        scrollbar-width: thin;
    }

    .gf-marketplace-periods::-webkit-scrollbar,
    .gf-marketplace-tabs::-webkit-scrollbar,
    .gf-overview-marketplace-tabs::-webkit-scrollbar {
        height: 4px;
    }

    .gf-marketplace-periods::-webkit-scrollbar-thumb,
    .gf-marketplace-tabs::-webkit-scrollbar-thumb,
    .gf-overview-marketplace-tabs::-webkit-scrollbar-thumb {
        background: rgba(15, 23, 42, .16);
        border-radius: 999px;
    }

    .gf-marketplace-period,
    .gf-marketplace-tab,
    .gf-overview-marketplace-tab {
        flex: 0 0 auto;
        border: 0;
        background: transparent;
        color: #64748b;
        font-size: .78rem;
        font-weight: 850;
        border-radius: 14px;
        padding: .48rem .85rem;
        white-space: nowrap;
        transition: .16s ease;
    }

    .gf-marketplace-period.is-active,
    .gf-marketplace-tab.is-active,
    .gf-overview-marketplace-tab.is-active {
        background: #0f172a;
        color: #fff;
        box-shadow: 0 8px 18px rgba(15, 23, 42, .10);
    }

    .gf-marketplace-tab-panel[hidden],
    .gf-tab-kpi-panel[hidden] {
        display: none !important;
    }

    /* Beri jarak antar panel yang ditumpuk dalam satu tab (anti-mepet) */
    .gf-marketplace-tab-panel > .gf-panel + .gf-panel,
    .gf-marketplace-tab-panel > .gf-overview-kpi-grid + .gf-panel {
        margin-top: 1.1rem;
    }
    .gf-marketplace-tab-panel > .gf-overview-metric-chips + .gf-panel {
        margin-top: 1.1rem;
    }

    .gf-tab-kpi-stack {
        margin-top: -.05rem;
    }

    .gf-tab-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .65rem;
    }

    .gf-tab-kpi-card {
        border: 1px solid rgba(15, 23, 42, .075);
        border-radius: 16px;
        background: linear-gradient(180deg, #fff 0%, #fcfcfd 100%);
        padding: .82rem .9rem;
        box-shadow: none;
    }

    .gf-tab-kpi-label {
        color: #64748b;
        font-size: .66rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .32rem;
    }

    .gf-tab-kpi-value {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 950;
        line-height: 1.15;
        letter-spacing: -.02em;
    }

    .gf-tab-kpi-note {
        color: #94a3b8;
        font-size: .7rem;
        font-weight: 800;
        margin-top: .22rem;
    }

    .gf-overview-section-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        flex-wrap: wrap;
        margin-bottom: .75rem;
    }

    .gf-overview-section-title {
        color: #0f172a;
        font-size: .92rem;
        font-weight: 950;
    }

    .gf-overview-section-sub {
        color: #64748b;
        font-size: .76rem;
        margin-top: .15rem;
    }

    .gf-chart-card-grid,
    .gf-chart-card-grid-friendly {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(145px, 1fr));
        gap: .58rem;
        padding: .8rem;
    }

    .gf-chart-metric-card {
        position: relative;
        width: 100%;
        min-height: 78px;
        text-align: left;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 15px;
        background: #fff;
        padding: .72rem .78rem;
        color: #0f172a;
        overflow: hidden;
        transition: .16s ease;
        box-shadow: none;
        cursor: pointer;
        user-select: none;
    }

    .gf-chart-metric-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 3px;
        background: var(--metric-color, #0f172a);
        opacity: .22;
    }

    .gf-chart-metric-card.is-active {
        border-color: rgba(15, 23, 42, .13);
        box-shadow: 0 10px 22px rgba(15, 23, 42, .055);
        opacity: 1;
    }

    .gf-chart-metric-card.is-active::before {
        opacity: 1;
    }

    .gf-chart-metric-card:not(.is-active) {
        opacity: .52;
    }

    .gf-chart-metric-card-label {
        display: flex;
        align-items: center;
        gap: .42rem;
        color: #64748b;
        font-size: .64rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .35rem;
    }

    .gf-chart-metric-dot {
        width: .48rem;
        height: .48rem;
        border-radius: 999px;
        background: var(--metric-color, #0f172a);
    }

    .gf-chart-metric-card-value {
        color: #0f172a;
        font-size: .94rem;
        font-weight: 950;
        line-height: 1.15;
        letter-spacing: -.02em;
    }

    .gf-chart-metric-card-note {
        color: #94a3b8;
        font-size: .68rem;
        font-weight: 800;
        margin-top: .22rem;
    }

    .gf-marketplace-chart-box {
        height: 265px;
        min-height: 265px;
        padding: .65rem .9rem .9rem;
    }

    #gfMarketplaceDailyChart {
        max-height: 265px;
    }

    .gf-marketplace-two-col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .85rem;
    }

    .gf-marketplace-mini-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .65rem;
    }

    .gf-marketplace-mini-card {
        border: 1px solid rgba(15, 23, 42, .075);
        border-radius: 16px;
        padding: .82rem .9rem;
        background: #fff;
        box-shadow: none;
    }

    .gf-marketplace-mini-label {
        color: #64748b;
        font-size: .66rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .32rem;
    }

    .gf-marketplace-mini-value {
        color: #0f172a;
        font-size: .98rem;
        font-weight: 950;
        line-height: 1.15;
    }

    .gf-marketplace-action-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .85rem;
        padding: .88rem .95rem;
        border-radius: 16px;
        border: 1px solid rgba(245, 158, 11, .22);
        background: rgba(245, 158, 11, .06);
    }

    .gf-marketplace-action-title {
        color: #0f172a;
        font-weight: 950;
    }

    .gf-marketplace-action-sub {
        color: #64748b;
        font-size: .78rem;
        margin-top: .16rem;
    }

    .gf-mapping-action-buttons {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .55rem;
        flex-wrap: wrap;
    }

    .gf-marketplace-clean-ui .btn {
        min-height: 40px;
        border-radius: 14px !important;
        font-size: .84rem;
        font-weight: 850;
    }

    .gf-table-scroll {
        overflow-x: auto;
        border-radius: 16px;
    }

    .gf-clean-table {
        font-size: .82rem;
        min-width: 760px;
    }

    .gf-clean-table th {
        color: #64748b;
        font-size: .68rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .035em;
        background: #f8fafc;
        white-space: nowrap;
        padding-top: .72rem;
        padding-bottom: .72rem;
    }

    .gf-clean-table td {
        vertical-align: middle;
        padding-top: .66rem;
        padding-bottom: .66rem;
    }

    .gf-table-title {
        color: #0f172a;
        font-size: .82rem;
        font-weight: 850;
    }

    .gf-table-subtitle {
        color: #94a3b8;
        font-size: .72rem;
    }

    .gf-table-money {
        font-weight: 850;
        letter-spacing: -.01em;
    }

    .gf-marketplace-loading {
        opacity: .55;
        pointer-events: none;
    }

    @media (max-width: 1200px) {
        .gf-marketplace-filter-grid,
        .gf-tab-kpi-grid,
        .gf-marketplace-mini-grid,
        .gf-marketplace-two-col {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .gf-marketplace-clean-ui .gf-marketplace-sticky-head {
            padding-top: .5rem;
            gap: .5rem;
        }

        .gf-marketplace-filter-grid,
        .gf-tab-kpi-grid,
        .gf-marketplace-mini-grid,
        .gf-marketplace-two-col {
            grid-template-columns: 1fr;
        }

        .gf-chart-card-grid,
        .gf-chart-card-grid-friendly {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding: .65rem;
        }

        .gf-marketplace-chart-box {
            height: 245px;
            min-height: 245px;
        }

        #gfMarketplaceDailyChart {
            max-height: 245px;
        }

        .gf-marketplace-action-card {
            align-items: flex-start;
            flex-direction: column;
        }

        .gf-mapping-action-buttons {
            width: 100%;
            justify-content: flex-start;
        }

        .gf-mapping-action-buttons .btn {
            width: 100%;
        }

        .gf-clean-table {
            font-size: .78rem;
        }
    }

    @media (max-width: 460px) {
        .gf-chart-card-grid,
        .gf-chart-card-grid-friendly {
            grid-template-columns: 1fr;
        }
    }

    /* GFID_FIX_MARKETPLACE_CHART_LAYOUT_START */
    .gf-marketplace-clean-ui .gf-metric-graphic-head,
    .gf-marketplace-minimal-ui .gf-metric-graphic-head {
        display: grid !important;
        grid-template-columns: 1fr !important;
        align-items: stretch !important;
        gap: .75rem !important;
        width: 100% !important;
    }

    .gf-marketplace-clean-ui .gf-metric-graphic-head > div:first-child,
    .gf-marketplace-minimal-ui .gf-metric-graphic-head > div:first-child {
        width: 100% !important;
    }

    .gf-marketplace-clean-ui .gf-metric-graphic-head .gf-chart-card-grid,
    .gf-marketplace-minimal-ui .gf-metric-graphic-head .gf-chart-card-grid {
        width: 100% !important;
        max-width: 100% !important;
        display: grid !important;
        grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)) !important;
        align-items: stretch !important;
        gap: .6rem !important;
        padding: .75rem 0 0 !important;
    }

    .gf-marketplace-clean-ui .gf-metric-graphic-head .gf-chart-metric-card,
    .gf-marketplace-minimal-ui .gf-metric-graphic-head .gf-chart-metric-card {
        width: 100% !important;
        min-width: 0 !important;
    }

    .gf-marketplace-clean-ui .gf-marketplace-chart-box,
    .gf-marketplace-minimal-ui .gf-marketplace-chart-box {
        height: 300px !important;
        min-height: 300px !important;
        padding: .75rem 1rem 1rem !important;
    }

    .gf-marketplace-clean-ui #gfMarketplaceDailyChart,
    .gf-marketplace-minimal-ui #gfMarketplaceDailyChart {
        max-height: 300px !important;
    }

    @media (max-width: 768px) {
        .gf-marketplace-clean-ui .gf-metric-graphic-head .gf-chart-card-grid,
        .gf-marketplace-minimal-ui .gf-metric-graphic-head .gf-chart-card-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .gf-marketplace-clean-ui .gf-marketplace-chart-box,
        .gf-marketplace-minimal-ui .gf-marketplace-chart-box {
            height: 250px !important;
            min-height: 250px !important;
        }

        .gf-marketplace-clean-ui #gfMarketplaceDailyChart,
        .gf-marketplace-minimal-ui #gfMarketplaceDailyChart {
            max-height: 250px !important;
        }
    }

    @media (max-width: 480px) {
        .gf-marketplace-clean-ui .gf-metric-graphic-head .gf-chart-card-grid,
        .gf-marketplace-minimal-ui .gf-metric-graphic-head .gf-chart-card-grid {
            grid-template-columns: 1fr !important;
        }
    }
    /* GFID_FIX_MARKETPLACE_CHART_LAYOUT_END */


    /* GFID_COMPACT_HEADER_DATE_FILTER_START */
    .gf-dashboard-header-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .55rem;
        flex-wrap: wrap;
        width: 100%;
    }

    .gf-dashboard-header-filter {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .45rem;
        flex-wrap: nowrap;
        min-width: 0;
    }

    .gf-dashboard-header-links {
        display: flex;
        align-items: center;
        gap: .45rem;
        flex-wrap: nowrap;
    }

    .gf-header-period-select {
        width: 112px;
        min-height: 36px;
        border-radius: 999px !important;
        font-size: .78rem;
        font-weight: 600;
        padding-left: .85rem;
        padding-right: 2rem;
        border-color: rgba(15, 23, 42, .12);
        box-shadow: none !important;
    }

    .gf-header-date-input {
        width: 200px;
        min-height: 36px;
        border-radius: 999px !important;
        font-size: .78rem;
        font-weight: 600;
        color: #0f172a;
        border-color: rgba(15, 23, 42, .12);
        box-shadow: none !important;
    }

    /* Selaras-kan select filter (penjahit/kategori/SKU) dgn gaya pill yg sama */
    .gf-header-select {
        min-height: 36px;
        max-width: 168px;
        border-radius: 999px !important;
        font-size: .78rem;
        font-weight: 600;
        padding-left: .85rem;
        padding-right: 2rem;
        border-color: rgba(15, 23, 42, .12);
        box-shadow: none !important;
        color: #0f172a;
    }

    .gf-header-icon-btn,
    .gf-header-action-btn {
        min-height: 36px !important;
        border-radius: 999px !important;
        padding: .42rem .85rem !important;
        font-size: .78rem !important;
        font-weight: 600 !important;
        color: var(--gf-muted) !important;
        white-space: nowrap;
    }

    .gf-marketplace-clean-ui .gf-marketplace-sticky-head,
    .gf-marketplace-minimal-ui .gf-marketplace-sticky-head {
        padding-top: .35rem !important;
        padding-bottom: .65rem !important;
        gap: .45rem !important;
    }

    .gf-marketplace-clean-ui .gf-marketplace-tabs,
    .gf-marketplace-minimal-ui .gf-marketplace-tabs {
        margin-top: 0 !important;
    }

    @media (max-width: 992px) {
        .gf-dashboard-header-actions {
            justify-content: flex-start;
        }

        .gf-dashboard-header-filter {
            width: 100%;
            justify-content: flex-start;
            overflow-x: auto;
            padding-bottom: .15rem;
        }

        .gf-dashboard-header-links {
            width: 100%;
            justify-content: flex-start;
        }

        .gf-header-date-input {
            width: 190px;
        }
    }

    @media (max-width: 576px) {
        .gf-dashboard-header-filter {
            display: grid;
            grid-template-columns: 1fr 1.25fr auto;
            gap: .4rem;
        }

        .gf-header-period-select,
        .gf-header-date-input {
            width: 100%;
            min-width: 0;
        }

        .gf-header-icon-btn {
            padding-left: .75rem !important;
            padding-right: .75rem !important;
        }

        .gf-dashboard-header-links {
            display: none;
        }
    }
    /* GFID_COMPACT_HEADER_DATE_FILTER_END */


    /* GFID_REBUILD_OVERVIEW_TAB_START */
    .gf-overview-topbar {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: .75rem;
        margin-bottom: .9rem;
    }

    .gf-overview-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .65rem;
        margin-bottom: .9rem;
    }

    .gf-overview-kpi-card {
        border: 1px solid rgba(15, 23, 42, .075);
        border-radius: 16px;
        padding: .82rem .9rem;
        background: linear-gradient(180deg, #fff 0%, #fcfcfd 100%);
    }

    .gf-overview-kpi-card-strong {
        border-color: rgba(22, 163, 74, .20);
        background: linear-gradient(180deg, rgba(22, 163, 74, .06) 0%, #fff 86%);
    }

    .gf-overview-kpi-label {
        color: #64748b;
        font-size: .66rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .32rem;
    }

    .gf-overview-kpi-value {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 950;
        line-height: 1.15;
        letter-spacing: -.02em;
    }

    .gf-overview-kpi-note {
        color: #94a3b8;
        font-size: .7rem;
        font-weight: 800;
        margin-top: .22rem;
    }

    .gf-overview-chart-toolbar {
        padding: .85rem .95rem .2rem;
    }

    .gf-overview-metric-chips {
        display: flex;
        gap: .45rem;
        overflow-x: auto;
        padding-bottom: .3rem;
        scrollbar-width: thin;
    }

    .gf-overview-metric-chips::-webkit-scrollbar {
        height: 4px;
    }

    .gf-overview-metric-chips::-webkit-scrollbar-thumb {
        background: rgba(15, 23, 42, .16);
        border-radius: 999px;
    }

    .gf-overview-metric-chip {
        flex: 0 0 auto;
        min-width: 122px;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 14px;
        background: #fff;
        padding: .58rem .7rem;
        text-align: left;
        opacity: .56;
        transition: .16s ease;
    }

    .gf-overview-metric-chip span {
        display: block;
        color: #64748b;
        font-size: .63rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .18rem;
    }

    .gf-overview-metric-chip strong {
        display: block;
        color: #0f172a;
        font-size: .82rem;
        font-weight: 950;
        white-space: nowrap;
    }

    .gf-overview-metric-chip.is-active {
        opacity: 1;
        border-color: rgba(15, 23, 42, .16);
        box-shadow: 0 8px 18px rgba(15, 23, 42, .055);
    }

    .gf-overview-insight-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .65rem;
        padding: .9rem;
    }

    .gf-overview-insight-card {
        border: 1px solid rgba(15, 23, 42, .075);
        border-radius: 16px;
        background: #fff;
        padding: .85rem .9rem;
    }

    .gf-overview-insight-label {
        color: #64748b;
        font-size: .66rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .32rem;
    }

    .gf-overview-insight-value {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 950;
        line-height: 1.15;
    }

    .gf-overview-insight-note {
        color: #94a3b8;
        font-size: .72rem;
        font-weight: 800;
        margin-top: .25rem;
    }

    .gf-overview-insight-empty {
        grid-column: 1 / -1;
        color: #94a3b8;
        text-align: center;
        padding: 1rem;
        font-size: .82rem;
        font-weight: 800;
    }

    @media (max-width: 1200px) {
        .gf-overview-kpi-grid,
        .gf-overview-insight-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .gf-overview-kpi-grid,
        .gf-overview-insight-grid {
            grid-template-columns: 1fr;
        }

        .gf-overview-metric-chip {
            min-width: 112px;
        }
    }
    /* GFID_REBUILD_OVERVIEW_TAB_END */


    /* GFID_FIX_OVERVIEW_DOUBLE_KPI_START */
    .gf-tab-kpi-stack[hidden] {
        display: none !important;
    }

    .gf-tab-kpi-stack:empty {
        display: none !important;
    }

    .gf-overview-kpi-grid {
        margin-top: .75rem;
    }
    /* GFID_FIX_OVERVIEW_DOUBLE_KPI_END */


    /* GFID_REBUILD_ORDER_TAB_START */
    .gf-order-topbar {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: .75rem;
        flex-wrap: wrap;
        margin-bottom: .9rem;
    }

    .gf-order-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .65rem;
        margin-bottom: .9rem;
    }

    .gf-order-kpi-card {
        border: 1px solid rgba(15, 23, 42, .075);
        border-radius: 16px;
        padding: .82rem .9rem;
        background: linear-gradient(180deg, #fff 0%, #fcfcfd 100%);
    }

    .gf-order-kpi-card-strong {
        border-color: rgba(37, 99, 235, .20);
        background: linear-gradient(180deg, rgba(37, 99, 235, .055) 0%, #fff 86%);
    }

    .gf-order-kpi-label {
        color: #64748b;
        font-size: .66rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .32rem;
    }

    .gf-order-kpi-value {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 950;
        line-height: 1.15;
        letter-spacing: -.02em;
    }

    .gf-order-kpi-note {
        color: #94a3b8;
        font-size: .7rem;
        font-weight: 800;
        margin-top: .22rem;
    }

    .gf-order-two-col {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: .85rem;
    }

    .gf-order-daily-table {
        min-width: 820px;
    }

    .gf-order-status-table,
    .gf-order-top-table {
        min-width: 520px;
    }

    @media (max-width: 1200px) {
        .gf-order-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 900px) {
        .gf-order-two-col {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .gf-order-kpi-grid {
            grid-template-columns: 1fr;
        }
    }
    /* GFID_REBUILD_ORDER_TAB_END */


    /* GFID_ORDER_PERIOD_CHART_START */
    .gf-order-chart-toolbar {
        padding: .85rem .95rem .2rem;
    }

    .gf-order-metric-chips {
        display: flex;
        gap: .45rem;
        overflow-x: auto;
        padding-bottom: .3rem;
        scrollbar-width: thin;
    }

    .gf-order-metric-chips::-webkit-scrollbar {
        height: 4px;
    }

    .gf-order-metric-chips::-webkit-scrollbar-thumb {
        background: rgba(15, 23, 42, .16);
        border-radius: 999px;
    }

    .gf-order-metric-chip {
        flex: 0 0 auto;
        min-width: 120px;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 14px;
        background: #fff;
        padding: .58rem .7rem;
        text-align: left;
        opacity: .56;
        transition: .16s ease;
    }

    .gf-order-metric-chip span {
        display: block;
        color: #64748b;
        font-size: .63rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .18rem;
    }

    .gf-order-metric-chip strong {
        display: block;
        color: #0f172a;
        font-size: .82rem;
        font-weight: 950;
        white-space: nowrap;
    }

    .gf-order-metric-chip.is-active {
        opacity: 1;
        border-color: rgba(15, 23, 42, .16);
        box-shadow: 0 8px 18px rgba(15, 23, 42, .055);
    }

    .gf-order-chart-box {
        height: 280px;
        min-height: 280px;
        padding: .65rem .9rem .9rem;
    }

    #gfMarketplaceOrderChart {
        max-height: 280px;
    }

    @media (max-width: 768px) {
        .gf-order-chart-box {
            height: 245px;
            min-height: 245px;
        }

        #gfMarketplaceOrderChart {
            max-height: 245px;
        }

        .gf-order-metric-chip {
            min-width: 112px;
        }
    }
    /* GFID_ORDER_PERIOD_CHART_END */


    /* GFID_ORDER_TAB_POLISH_START */
    .gf-order-chart-wrap {
        margin-bottom: 1rem;
    }

    .gf-order-chart-wrap + .gf-order-chart-wrap {
        margin-top: 0;
    }

    .gf-order-daily-scroll {
        max-height: 460px;
        overflow: auto;
        border-radius: 16px;
    }

    .gf-order-daily-scroll .gf-order-daily-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .gf-order-daily-scroll .gf-order-daily-table thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #f8fafc;
        box-shadow: 0 1px 0 rgba(15, 23, 42, .08);
    }

    .gf-order-daily-scroll .gf-order-daily-table tbody tr:nth-child(even) {
        background: rgba(248, 250, 252, .45);
    }

    @media (max-width: 768px) {
        .gf-order-chart-wrap {
            margin-bottom: .85rem;
        }

        .gf-order-daily-scroll {
            max-height: 420px;
        }
    }
    /* GFID_ORDER_TAB_POLISH_END */


    /* GFID_ORDER_UPGRADE_FRONTEND_START */
    .gf-order-upgrade-shell {
        display: grid;
        gap: 1rem;
    }

    .gf-order-upgrade-head {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: .85rem;
        flex-wrap: wrap;
    }

    .gf-order-data-note {
        display: flex;
        align-items: center;
        gap: .45rem;
        padding: .42rem .68rem;
        border-radius: 999px;
        border: 1px solid rgba(37, 99, 235, .16);
        background: rgba(37, 99, 235, .055);
        white-space: nowrap;
    }

    .gf-order-data-note span {
        color: #2563eb;
        font-size: .65rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .gf-order-data-note strong {
        color: #0f172a;
        font-size: .74rem;
        font-weight: 900;
    }

    .gf-order-upgrade-kpi-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .65rem;
    }

    .gf-order-upgrade-kpi {
        display: grid;
        grid-template-columns: 38px minmax(0, 1fr);
        gap: .72rem;
        align-items: center;
        border: 1px solid rgba(15, 23, 42, .075);
        border-radius: 16px;
        background: linear-gradient(180deg, #fff 0%, #fcfcfd 100%);
        padding: .82rem .9rem;
        min-height: 88px;
    }

    .gf-order-upgrade-kpi-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        color: #0f172a;
        background: #f1f5f9;
        font-size: .72rem;
        font-weight: 950;
    }

    .gf-order-upgrade-kpi-strong {
        border-color: rgba(37, 99, 235, .20);
        background: linear-gradient(180deg, rgba(37, 99, 235, .055) 0%, #fff 86%);
    }

    .gf-order-upgrade-kpi-profit {
        border-color: rgba(22, 163, 74, .20);
        background: linear-gradient(180deg, rgba(22, 163, 74, .06) 0%, #fff 86%);
    }

    .gf-order-upgrade-kpi-label,
    .gf-order-insight-label {
        color: #64748b;
        font-size: .66rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .22rem;
    }

    .gf-order-upgrade-kpi-value,
    .gf-order-insight-value {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 950;
        line-height: 1.15;
        letter-spacing: -.02em;
    }

    .gf-order-upgrade-kpi-note,
    .gf-order-insight-note {
        color: #94a3b8;
        font-size: .7rem;
        font-weight: 800;
        margin-top: .22rem;
    }

    .gf-order-upgrade-alert {
        border: 1px dashed rgba(245, 158, 11, .38);
        background: rgba(245, 158, 11, .055);
        color: #64748b;
        border-radius: 16px;
        padding: .78rem .9rem;
        font-size: .78rem;
        font-weight: 800;
    }

    .gf-order-upgrade-alert strong {
        color: #0f172a;
    }

    .gf-order-insight-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .65rem;
        padding: .9rem;
    }

    .gf-order-insight-card {
        border: 1px solid rgba(15, 23, 42, .075);
        border-radius: 16px;
        background: #fff;
        padding: .85rem .9rem;
    }

    .gf-order-empty-state {
        grid-column: 1 / -1;
        color: #94a3b8;
        text-align: center;
        padding: 1rem;
        font-size: .82rem;
        font-weight: 800;
    }

    .gf-order-dashboard-grid {
        display: grid;
        grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr);
        gap: .85rem;
    }

    .gf-order-daily-table {
        min-width: 1040px;
    }

    .gf-order-sku-table {
        min-width: 860px;
    }

    .gf-order-small-scroll,
    .gf-order-sku-scroll {
        max-height: 360px;
        overflow: auto;
    }

    .gf-order-daily-scroll {
        max-height: 480px;
        overflow: auto;
    }

    .gf-order-daily-scroll thead th,
    .gf-order-small-scroll thead th,
    .gf-order-sku-scroll thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #f8fafc;
        box-shadow: 0 1px 0 rgba(15, 23, 42, .08);
    }

    @media (max-width: 1200px) {
        .gf-order-upgrade-kpi-grid,
        .gf-order-insight-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 900px) {
        .gf-order-dashboard-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .gf-order-upgrade-head {
            align-items: stretch;
        }

        .gf-order-data-note {
            width: 100%;
            justify-content: space-between;
            border-radius: 14px;
        }

        .gf-order-upgrade-kpi-grid,
        .gf-order-insight-grid {
            grid-template-columns: 1fr;
        }
    }
    /* GFID_ORDER_UPGRADE_FRONTEND_END */


    /* GFID_ORDER_ADS_TOP_CUSTOMER_START */
    .gf-order-daily-table {
        min-width: 1160px;
    }

    .gf-order-top-table {
        min-width: 960px;
    }

    .gf-order-upgrade-kpi-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    @media (max-width: 1200px) {
        .gf-order-upgrade-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .gf-order-upgrade-kpi-grid {
            grid-template-columns: 1fr;
        }
    }
    /* GFID_ORDER_ADS_TOP_CUSTOMER_END */


    /* GFID_ORDER_REMOVE_STATUS_TOPSKU_TABS_START */
    .gf-order-top-order-wrap {
        display: block;
    }

    .gf-order-sku-tabs {
        display: flex;
        align-items: center;
        gap: .4rem;
        overflow-x: auto;
        padding: .85rem .9rem .25rem;
        scrollbar-width: thin;
    }

    .gf-order-sku-tab {
        flex: 0 0 auto;
        border: 1px solid rgba(15, 23, 42, .08);
        background: #fff;
        color: #64748b;
        border-radius: 999px;
        padding: .45rem .82rem;
        font-size: .76rem;
        font-weight: 900;
        white-space: nowrap;
        transition: .16s ease;
    }

    .gf-order-sku-tab.is-active {
        color: #fff;
        background: #0f172a;
        border-color: #0f172a;
        box-shadow: 0 8px 18px rgba(15, 23, 42, .10);
    }

    .gf-order-upgrade-kpi-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    @media (max-width: 1200px) {
        .gf-order-upgrade-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .gf-order-upgrade-kpi-grid {
            grid-template-columns: 1fr;
        }
    }
    /* GFID_ORDER_REMOVE_STATUS_TOPSKU_TABS_END */

    /* GFID_ORDER_METRICS_INPLACE_START */
    .gf-order-metrics-shell {
        display: grid;
        gap: 1rem;
    }

    .gf-order-metrics-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: .8rem;
        flex-wrap: wrap;
    }

    .gf-order-metrics-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: .65rem;
    }

    .gf-order-metric-card {
        border: 1px solid rgba(15, 23, 42, .075);
        border-radius: 16px;
        background: linear-gradient(180deg, #fff 0%, #fcfcfd 100%);
        padding: .82rem .9rem;
        min-height: 88px;
    }

    .gf-order-metric-card-strong {
        border-color: rgba(37, 99, 235, .20);
        background: linear-gradient(180deg, rgba(37, 99, 235, .055) 0%, #fff 86%);
    }

    .gf-order-metric-card-profit {
        border-color: rgba(22, 163, 74, .20);
        background: linear-gradient(180deg, rgba(22, 163, 74, .06) 0%, #fff 86%);
    }

    .gf-order-metric-label {
        color: #64748b;
        font-size: .66rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .28rem;
    }

    .gf-order-metric-value {
        color: #0f172a;
        font-size: .98rem;
        font-weight: 950;
        line-height: 1.15;
        letter-spacing: -.02em;
    }

    .gf-order-metric-note {
        color: #94a3b8;
        font-size: .68rem;
        font-weight: 800;
        margin-top: .24rem;
    }

    .gf-order-daily-scroll,
    .gf-order-small-scroll,
    .gf-order-sku-scroll {
        overflow: auto;
        border-radius: 16px;
    }

    .gf-order-daily-scroll { max-height: 480px; }
    .gf-order-small-scroll { max-height: 360px; }
    .gf-order-sku-scroll { max-height: 420px; }

    .gf-order-daily-table { min-width: 1120px; }
    .gf-order-top-table { min-width: 960px; }
    .gf-order-sku-table { min-width: 860px; }

    .gf-order-daily-scroll thead th,
    .gf-order-small-scroll thead th,
    .gf-order-sku-scroll thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #f8fafc;
        box-shadow: 0 1px 0 rgba(15, 23, 42, .08);
    }

    .gf-order-sku-tabs {
        display: flex;
        align-items: center;
        gap: .4rem;
        overflow-x: auto;
        padding: .85rem .9rem .25rem;
        scrollbar-width: thin;
    }

    .gf-order-sku-tab {
        flex: 0 0 auto;
        border: 1px solid rgba(15, 23, 42, .08);
        background: #fff;
        color: #64748b;
        border-radius: 999px;
        padding: .45rem .82rem;
        font-size: .76rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .gf-order-sku-tab.is-active {
        color: #fff;
        background: #0f172a;
        border-color: #0f172a;
    }

    @media (max-width: 1400px) {
        .gf-order-metrics-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (max-width: 900px) {
        .gf-order-metrics-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 640px) {
        .gf-order-metrics-grid { grid-template-columns: 1fr; }
    }
    /* GFID_ORDER_METRICS_INPLACE_END */


    /* GFID_ORDER_CHART_TOPSKU_FINAL_START */
    .gf-order-chart-toolbar {
        padding: .85rem .95rem .2rem;
    }

    .gf-order-metric-chips {
        display: flex;
        gap: .45rem;
        overflow-x: auto;
        padding-bottom: .3rem;
        scrollbar-width: thin;
    }

    .gf-order-metric-chip {
        flex: 0 0 auto;
        min-width: 122px;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 14px;
        background: #fff;
        padding: .58rem .7rem;
        text-align: left;
        opacity: .58;
        transition: .16s ease;
    }

    .gf-order-metric-chip span {
        display: block;
        color: #64748b;
        font-size: .63rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .18rem;
    }

    .gf-order-metric-chip strong {
        display: block;
        color: #0f172a;
        font-size: .82rem;
        font-weight: 950;
        white-space: nowrap;
    }

    .gf-order-metric-chip.is-active {
        opacity: 1;
        border-color: rgba(15, 23, 42, .16);
        box-shadow: 0 8px 18px rgba(15, 23, 42, .055);
    }

    .gf-order-chart-box {
        height: 300px;
        min-height: 300px;
        padding: .75rem 1rem 1rem;
    }

    #gfMarketplaceOrderChart {
        max-height: 300px;
    }

    .gf-order-sku-table {
        min-width: 1120px !important;
    }

    .gf-order-sku-scroll {
        max-height: 460px;
        overflow: auto;
    }

    @media (max-width: 768px) {
        .gf-order-chart-box {
            height: 250px;
            min-height: 250px;
        }

        #gfMarketplaceOrderChart {
            max-height: 250px;
        }

        .gf-order-metric-chip {
            min-width: 112px;
        }
    }
    /* GFID_ORDER_CHART_TOPSKU_FINAL_END */


    /* GFID_SYNC_ORDER_MARKETPLACE_TABS_START */
    .gf-order-marketplace-sync {
        margin-bottom: .9rem;
    }

    .gf-order-marketplace-tabs-sync {
        width: 100% !important;
        max-width: 100%;
        justify-content: flex-start;
        flex-wrap: nowrap !important;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: thin;
    }

    .gf-order-marketplace-tabs-sync::-webkit-scrollbar {
        height: 4px;
    }

    .gf-order-marketplace-tabs-sync::-webkit-scrollbar-thumb {
        background: rgba(15, 23, 42, .16);
        border-radius: 999px;
    }

    .gf-order-marketplace-tabs-sync .gf-overview-marketplace-tab {
        flex: 0 0 auto;
    }

    @media (max-width: 768px) {
        .gf-order-marketplace-sync {
            margin-bottom: .75rem;
        }

        .gf-order-marketplace-tabs-sync {
            padding-bottom: .3rem !important;
        }
    }
    /* GFID_SYNC_ORDER_MARKETPLACE_TABS_END */


    /* GFID_FIX_ORDER_EMPTY_ROWS_TOPSKU_START */
    .gf-order-daily-table {
        min-width: 1160px;
    }

    .gf-order-sku-table {
        min-width: 1120px;
    }

    .gf-order-daily-scroll,
    .gf-order-sku-scroll,
    .gf-order-small-scroll {
        overflow: auto;
    }
    /* GFID_FIX_ORDER_EMPTY_ROWS_TOPSKU_END */


    /* GFID_ORDER_DAILY_SORTABLE_START */
    .gf-order-sort-btn {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: .28rem;
        width: 100%;
        border: 0;
        background: transparent;
        color: inherit;
        font: inherit;
        font-weight: 950;
        text-transform: inherit;
        letter-spacing: inherit;
        padding: 0;
        white-space: nowrap;
        cursor: pointer;
    }

    .gf-order-daily-table th:first-child .gf-order-sort-btn {
        justify-content: flex-start;
    }

    .gf-order-sort-btn span {
        display: inline-grid;
        place-items: center;
        min-width: .8rem;
        color: #0f172a;
        font-size: .72rem;
    }

    .gf-order-sort-btn.is-active {
        color: #0f172a;
    }
    /* GFID_ORDER_DAILY_SORTABLE_END */


    /* GFID_ORDER_DAILY_SORTABLE_FIX_START */
    .gf-order-sort-btn {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: .28rem;
        width: 100%;
        border: 0;
        background: transparent;
        color: inherit;
        font: inherit;
        font-weight: 950;
        text-transform: inherit;
        letter-spacing: inherit;
        padding: 0;
        white-space: nowrap;
        cursor: pointer;
    }

    .gf-order-daily-table th:first-child .gf-order-sort-btn {
        justify-content: flex-start;
    }

    .gf-order-sort-btn span {
        display: inline-grid;
        place-items: center;
        min-width: .8rem;
        color: #0f172a;
        font-size: .72rem;
    }

    .gf-order-sort-btn.is-active {
        color: #0f172a;
    }
    /* GFID_ORDER_DAILY_SORTABLE_FIX_END */


    /* GFID_ORDER_DAILY_FINAL_ALIGNMENT_START */
    .gf-order-sort-btn {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: .28rem;
        width: 100%;
        border: 0;
        background: transparent;
        color: inherit;
        font: inherit;
        font-weight: 950;
        text-transform: inherit;
        letter-spacing: inherit;
        padding: 0;
        white-space: nowrap;
        cursor: pointer;
    }

    .gf-order-daily-table th:first-child .gf-order-sort-btn {
        justify-content: flex-start;
    }

    .gf-order-sort-btn span {
        display: inline-grid;
        place-items: center;
        min-width: .8rem;
        color: #0f172a;
        font-size: .72rem;
    }

    .gf-order-sort-btn.is-active {
        color: #0f172a;
    }
    /* GFID_ORDER_DAILY_FINAL_ALIGNMENT_END */

    /* Badge perbandingan vs periode sebelumnya */
    .gf-cmp{display:inline-flex;align-items:center;gap:2px;font-size:10.5px;font-weight:800;line-height:1;padding:2px 6px;border-radius:999px;white-space:nowrap;margin-top:3px;}
    .gf-cmp-up{color:#0f7b3f;background:rgba(34,197,94,.12);}
    .gf-cmp-down{color:#c0392b;background:rgba(239,68,68,.12);}
    .gf-cmp-flat{color:#64748b;background:rgba(100,116,139,.10);}
    .gf-cmp-cell{display:block;font-size:10px;font-weight:700;white-space:nowrap;margin-top:1px;}
    .gf-cmp-cell.up{color:#0f7b3f;}
    .gf-cmp-cell.down{color:#c0392b;}
    .gf-cmp-cell.flat{color:#94a3b8;}
</style>
