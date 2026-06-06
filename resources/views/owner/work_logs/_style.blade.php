<style>
    /* OWNER WORK LOG - SELARAS GREATFIT */
    .owl-page {
        display: grid;
        gap: 1rem;
    }

    .owl-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .5rem;
        flex-wrap: wrap;
        width: 100%;
    }

    .owl-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        min-height: 36px;
        border-radius: 999px;
        padding: .45rem .8rem;
        font-size: .78rem;
        font-weight: 850;
        line-height: 1;
        text-decoration: none;
        white-space: nowrap;
        border: 1px solid rgba(15,23,42,.10);
        color: #0f172a;
        background: #fff;
        box-shadow: none;
        transition: .15s ease;
    }

    .owl-btn:hover {
        color: #0f172a;
        background: #f8fafc;
        border-color: rgba(15,23,42,.18);
    }

    .owl-btn-primary {
        color: #fff;
        background: #0f172a;
        border-color: #0f172a;
        box-shadow: 0 8px 18px rgba(15,23,42,.12);
    }

    .owl-btn-primary:hover {
        color: #fff;
        background: #020617;
    }

    .owl-btn-success {
        color: #166534;
        background: #dcfce7;
        border-color: #bbf7d0;
    }

    .owl-btn-danger {
        color: #b91c1c;
        background: #fff5f5;
        border-color: #fecaca;
    }

    .owl-dashboard {
        display: grid;
        gap: .9rem;
    }

    .owl-kpi-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .65rem;
    }

    .owl-kpi {
        border: 1px solid rgba(15,23,42,.075);
        border-radius: 16px;
        background: linear-gradient(180deg, #fff 0%, #fcfcfd 100%);
        padding: .85rem .9rem;
        min-height: 78px;
    }

    .owl-kpi-label {
        color: #64748b;
        font-size: .64rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .055em;
        margin-bottom: .3rem;
    }

    .owl-kpi-value {
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 950;
        line-height: 1.1;
        letter-spacing: -.02em;
    }

    .owl-filter {
        display: grid;
        grid-template-columns: minmax(240px, 1fr) 150px 170px auto;
        gap: .5rem;
        align-items: end;
    }

    .owl-filter .form-control,
    .owl-filter .form-select,
    .owl-form-grid .form-control,
    .owl-form-grid .form-select {
        min-height: 40px;
        border-radius: 14px;
        border-color: rgba(15,23,42,.10);
        font-size: .84rem;
        font-weight: 700;
        box-shadow: none;
    }

    .owl-filter .form-control:focus,
    .owl-filter .form-select:focus,
    .owl-form-grid .form-control:focus,
    .owl-form-grid .form-select:focus {
        border-color: #0f172a;
        box-shadow: 0 0 0 3px rgba(15,23,42,.08);
    }

    .owl-log-list {
        display: grid;
        gap: .65rem;
    }

    .owl-log-card {
        position: relative;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: .8rem;
        align-items: center;
        border: 1px solid rgba(15,23,42,.075);
        border-radius: 17px;
        background: #fff;
        padding: .85rem .9rem;
        overflow: hidden;
    }

    .owl-log-card::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: #2563eb;
        opacity: .75;
    }

    .owl-log-card.is-done {
        background: linear-gradient(180deg, rgba(22,163,74,.055) 0%, #fff 82%);
        border-color: rgba(22,163,74,.20);
    }

    .owl-log-card.is-done::before {
        background: #16a34a;
    }

    .owl-log-main {
        min-width: 0;
        display: grid;
        gap: .25rem;
        padding-left: .25rem;
    }

    .owl-log-title-row {
        display: flex;
        align-items: center;
        gap: .45rem;
        flex-wrap: wrap;
    }

    .owl-log-title {
        color: #0f172a;
        font-size: .9rem;
        font-weight: 950;
        line-height: 1.25;
        text-decoration: none;
    }

    .owl-log-title:hover {
        color: #0f172a;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .owl-log-meta {
        color: #64748b;
        font-size: .74rem;
        font-weight: 750;
        line-height: 1.45;
    }

    .owl-log-desc {
        color: #475569;
        font-size: .78rem;
        font-weight: 700;
        line-height: 1.45;
        margin-top: .1rem;
    }

    .owl-log-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .4rem;
        flex-wrap: wrap;
    }

    .owl-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .2rem .55rem;
        font-size: .64rem;
        font-weight: 950;
        border: 1px solid rgba(15,23,42,.08);
        color: #475569;
        background: #f8fafc;
        white-space: nowrap;
    }

    .owl-badge-status-done {
        color: #166534;
        background: #dcfce7;
        border-color: #bbf7d0;
    }

    .owl-badge-status-progress {
        color: #1d4ed8;
        background: #dbeafe;
        border-color: #bfdbfe;
    }

    .owl-badge-priority-high {
        color: #b45309;
        background: #fef3c7;
        border-color: #fde68a;
    }

    .owl-badge-priority-medium {
        color: #334155;
        background: #f1f5f9;
        border-color: #e2e8f0;
    }

    .owl-badge-priority-low {
        color: #475569;
        background: #f8fafc;
        border-color: #e2e8f0;
    }

    .owl-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .8rem;
    }

    .owl-field {
        display: grid;
        gap: .35rem;
    }

    .owl-field-full {
        grid-column: 1 / -1;
    }

    .owl-field span {
        color: #475569;
        font-size: .72rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .035em;
    }

    .owl-field textarea {
        min-height: 92px;
        resize: vertical;
    }

    .owl-modal .modal-dialog {
        max-width: 720px;
    }

    .owl-modal .modal-content {
        border: 0;
        border-radius: 20px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 24px 60px rgba(15,23,42,.18);
    }

    .owl-modal .modal-header {
        padding: 1rem 1.1rem .75rem;
        border-bottom: 1px solid #eef2f7;
        background: #fff;
    }

    .owl-modal-sub {
        color: #64748b;
        font-size: .68rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: .07em;
        margin-bottom: .12rem;
    }

    .owl-modal .modal-title {
        color: #0f172a;
        font-size: 1.05rem;
        font-weight: 950;
        line-height: 1.2;
    }

    .owl-modal .modal-body {
        padding: 1rem 1.1rem;
    }

    .owl-modal .modal-footer {
        position: sticky;
        bottom: 0;
        z-index: 5;
        gap: .55rem;
        padding: .85rem 1.1rem 1rem;
        border-top: 1px solid #eef2f7;
        background: #fff;
    }

    .owl-modal .modal-footer .owl-btn {
        flex: 1 1 0;
        min-height: 42px;
    }

    body.modal-open .mobile-bottom-nav {
        display: none;
    }

    @media (max-width: 992px) {
        .owl-filter {
            grid-template-columns: 1fr 1fr;
        }

        .owl-log-card {
            grid-template-columns: 1fr;
        }

        .owl-log-actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 768px) {
        .owl-kpi-grid,
        .owl-filter,
        .owl-form-grid {
            grid-template-columns: 1fr;
        }

        .owl-actions,
        .owl-actions .owl-btn,
        .owl-actions form,
        .owl-actions form .owl-btn,
        .owl-log-actions,
        .owl-log-actions .owl-btn,
        .owl-log-actions form,
        .owl-log-actions form .owl-btn {
            width: 100%;
        }

        .owl-modal .modal-dialog {
            width: calc(100% - 1.5rem);
            max-width: 480px;
            margin: .75rem auto calc(.75rem + env(safe-area-inset-bottom));
        }

        .owl-modal .modal-content {
            max-height: calc(var(--app-vh, 100vh) - 1.5rem - env(safe-area-inset-bottom));
            border-radius: 18px;
        }

        .owl-modal .modal-body {
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: .9rem;
        }

        .owl-modal .modal-header,
        .owl-modal .modal-footer {
            padding: .8rem .9rem;
        }
    }

    /* OWNER HEADER ALIGN WITH GREATFIT / CASH EXPENSES */
    .owl-owner-page > .gf-master-header {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        padding: 14px 18px;
        box-shadow: none;
    }

    .owl-owner-page > .gf-master-header .gf-master-header-layout {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .owl-owner-page > .gf-master-header .gf-master-header-copy {
        min-width: 200px;
    }

    .owl-owner-page > .gf-master-header .gf-master-eyebrow {
        color: #64748b;
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
        margin-bottom: 2px;
    }

    .owl-owner-page > .gf-master-header .gf-master-title {
        color: #111827;
        font-size: 20px;
        line-height: 1.25;
        font-weight: 800;
        margin-bottom: 0;
        letter-spacing: -.01em;
    }

    .owl-owner-page > .gf-master-header .gf-master-desc,
    .owl-owner-page > .gf-master-header .gf-master-meta,
    .owl-owner-page .gf-subtext {
        display: none !important;
    }

    .owl-owner-page > .gf-master-header .gf-master-actions {
        flex: 1 1 420px;
        display: flex;
        justify-content: flex-end;
    }

    .owl-owner-page > .gf-master-header .owl-actions {
        justify-content: flex-end;
    }

    @media (max-width: 768px) {
        .owl-owner-page > .gf-master-header {
            padding: 13px 14px;
        }

        .owl-owner-page > .gf-master-header .gf-master-actions,
        .owl-owner-page > .gf-master-header .owl-actions,
        .owl-owner-page > .gf-master-header .owl-actions .owl-btn {
            width: 100%;
            flex-basis: 100%;
        }
    }


    /* OWNER PANEL TITLE STYLE */
    .owl-owner-page .gf-panel {
        border: 1px solid rgba(15, 23, 42, .075);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .045);
        overflow: hidden;
    }

    .owl-owner-page .gf-panel-header {
        padding: 16px 18px;
        background: linear-gradient(180deg, #fff 0%, #fbfcfe 100%);
        border-bottom: 1px solid #eef2f7;
    }

    .owl-owner-page .gf-panel-title {
        position: relative;
        display: flex;
        align-items: center;
        gap: .5rem;
        color: #0f172a !important;
        font-size: .95rem;
        line-height: 1.2;
        font-weight: 950;
        letter-spacing: -.01em;
    }

    .owl-owner-page .gf-panel-title::before {
        content: "";
        width: .55rem;
        height: .55rem;
        border-radius: 999px;
        background: #0f172a;
        box-shadow: 0 0 0 4px rgba(15, 23, 42, .08);
        flex: 0 0 auto;
    }

    .owl-owner-page .gf-panel-body {
        padding: 18px;
    }

    @media (max-width: 768px) {
        .owl-owner-page .gf-panel-header {
            padding: 14px;
        }

        .owl-owner-page .gf-panel-body {
            padding: 14px;
        }

        .owl-owner-page .gf-panel-title {
            font-size: .92rem;
        }
    }


    /* OWNER WORK LOG RELATED LINK */
    .owl-related-link {
        color: #2563eb !important;
        font-weight: 900;
        text-decoration: underline;
        text-underline-offset: 3px;
        word-break: break-word;
        cursor: pointer;
        position: relative;
        z-index: 10;
    }

    .owl-related-link:hover {
        color: #1d4ed8 !important;
    }
\n
    /* OWNER WORK LOG ACTION BUTTON PRECISION */
    .owl-log-actions {
        min-width: 230px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6px;
        flex-wrap: nowrap;
    }

    .owl-log-actions form {
        margin: 0;
        display: inline-flex;
    }

    .owl-log-actions .owl-btn {
        width: 70px;
        min-width: 70px;
        height: 34px;
        min-height: 34px;
        padding: 0 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
        line-height: 1;
    }

    .owl-log-actions .owl-btn-success {
        width: 70px;
        min-width: 70px;
    }

    .owl-log-actions .owl-btn-danger {
        width: 70px;
        min-width: 70px;
    }

    .owl-log-card {
        grid-template-columns: minmax(0, 1fr) 230px;
        align-items: center;
    }

    @media (max-width: 992px) {
        .owl-log-card {
            grid-template-columns: 1fr;
        }

        .owl-log-actions {
            min-width: 0;
            justify-content: flex-start;
        }
    }

    @media (max-width: 768px) {
        .owl-log-actions {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .owl-log-actions form,
        .owl-log-actions .owl-btn {
            width: 100%;
            min-width: 0;
        }
    }



    /* OWNER WORK LOG TABS - GREATFIT SOFT CONTRAST */
    .owl-tabs {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        width: fit-content;
        max-width: 100%;
        padding: 5px;
        border-radius: 18px;
        border: 1px solid rgba(15, 23, 42, .08);
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.75);
    }

    .owl-tab {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 36px;
        border-radius: 14px;
        padding: 0 15px;
        color: #64748b;
        font-size: 12px;
        font-weight: 950;
        line-height: 1;
        text-decoration: none;
        white-space: nowrap;
        transition: .15s ease;
    }

    .owl-tab:hover {
        color: #0f172a;
        background: rgba(255,255,255,.72);
    }

    .owl-tab span {
        position: relative;
        z-index: 1;
    }

    .owl-tab b {
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        height: 22px;
        border-radius: 999px;
        padding: 0 7px;
        color: #475569;
        background: #e2e8f0;
        font-size: 11px;
        font-weight: 950;
        box-shadow: inset 0 0 0 1px rgba(15,23,42,.04);
    }

    .owl-tab.is-active {
        color: #0f172a;
        background: #fff;
        box-shadow:
            0 10px 22px rgba(15, 23, 42, .08),
            inset 0 0 0 1px rgba(15, 23, 42, .06);
    }

    .owl-tab.is-active::before {
        content: "";
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: #38bdf8;
        box-shadow: 0 0 0 4px rgba(56, 189, 248, .16);
    }

    .owl-tab.is-active b {
        color: #075985;
        background: #e0f2fe;
    }

    .owl-tab[href*="tab=done"].is-active::before {
        background: #22c55e;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, .16);
    }

    .owl-tab[href*="tab=done"].is-active b {
        color: #166534;
        background: #dcfce7;
    }

    @media (max-width: 768px) {
        .owl-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            width: 100%;
            border-radius: 18px;
        }

        .owl-tab {
            width: 100%;
            min-height: 38px;
        }
    }

</style>
