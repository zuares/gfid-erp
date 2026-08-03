<style>
    :root {
        --r: 14px;
        --br: rgba(148, 163, 184, .22);
        --muted: #6b7280;
        --soft: rgba(148, 163, 184, .10);
        --soft2: rgba(148, 163, 184, .06);

        --primary: #0f172a;
        --primary-2: #111827;

        --chip-bg: rgba(59, 130, 246, .10);
        --chip-br: rgba(59, 130, 246, .22);
        --chip-tx: rgba(29, 78, 216, 1);

        --ok-bg: rgba(34, 197, 94, .10);
        --ok-br: rgba(34, 197, 94, .22);
        --ok-tx: rgba(22, 163, 74, 1);

        --code-tx: #0b1220;
        --code-bg: rgba(15, 23, 42, .06);
        --code-br: rgba(15, 23, 42, .14);
        --code-icon: rgba(15, 23, 42, .55);

        --total-tx: #0b1220;
        --total-bg: rgba(34, 197, 94, .10);
        --total-br: rgba(34, 197, 94, .22);
    }

    body[data-theme="dark"] {
        --muted: #9ca3af;
        --br: rgba(148, 163, 184, .18);
        --soft: rgba(148, 163, 184, .10);
        --soft2: rgba(148, 163, 184, .06);

        --primary: #e5e7eb;
        --primary-2: #f3f4f6;

        --chip-bg: rgba(147, 197, 253, .12);
        --chip-br: rgba(147, 197, 253, .22);
        --chip-tx: rgba(191, 219, 254, 1);

        --ok-bg: rgba(34, 197, 94, .14);
        --ok-br: rgba(34, 197, 94, .24);
        --ok-tx: rgba(134, 239, 172, 1);

        --code-tx: #eaf2ff;
        --code-bg: rgba(147, 197, 253, .14);
        --code-br: rgba(147, 197, 253, .26);
        --code-icon: rgba(191, 219, 254, .85);

        --total-tx: #eafff1;
        --total-bg: rgba(34, 197, 94, .16);
        --total-br: rgba(34, 197, 94, .30);
    }

    /* ====== SCROLL SAFETY (MOBILE) ====== */


    /* ====== LAYOUT ====== */
    .page-wrap {
        max-width: 1280px;
        margin-inline: auto;
        padding: .85rem .85rem 4rem;
    }

    body[data-theme="light"] .page-wrap {
        background: radial-gradient(circle at top left,
                rgba(59, 130, 246, .10) 0,
                rgba(45, 212, 191, .08) 22%,
                #f8fafc 62%);
    }

    body[data-theme="dark"] .page-wrap {
        background: radial-gradient(circle at top left, rgba(15, 23, 42, .92) 0, #020617 65%);
    }

    .cardx {
        background: var(--card);
        border: 1px solid var(--br);
        border-radius: var(--r);
        box-shadow: 0 10px 24px rgba(15, 23, 42, .06),
            0 0 0 1px rgba(148, 163, 184, .08);
    }

    .cardx-b {
        padding: .75rem .85rem;
    }

    .meta {
        font-size: .70rem;
        letter-spacing: .10em;
        text-transform: uppercase;
        color: var(--muted);
    }

    .mono {
        font-variant-numeric: tabular-nums;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
    }

    /* ====== TOPBAR ====== */
    .topbar {
        display: flex;
        flex-direction: column;
        gap: .55rem;
        margin-bottom: .55rem;
    }

    .title {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: .5rem;
    }

    .title h4 {
        margin: 0;
        font-weight: 850;
        letter-spacing: -.01em;
    }

    .tabs {
        display: inline-flex;
        border: 1px solid var(--br);
        border-radius: 999px;
        overflow: hidden;
        background: rgba(248, 250, 252, .6);
    }

    body[data-theme="dark"] .tabs {
        background: rgba(15, 23, 42, .55);
    }

    .tabs a {
        padding: .30rem .70rem;
        font-size: .80rem;
        text-decoration: none;
        color: inherit;
        border-right: 1px solid var(--br);
    }

    .tabs a:last-child {
        border-right: none;
    }

    .tabs a.active {
        background: var(--primary);
        color: #fff;
    }

    body[data-theme="dark"] .tabs a.active {
        background: var(--primary-2);
        color: #020617;
    }

    .pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .20rem .55rem;
        border-radius: 999px;
        border: 1px solid var(--chip-br);
        background: var(--chip-bg);
        color: var(--chip-tx);
        font-size: .70rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .pill--ok {
        border-color: var(--ok-br);
        background: var(--ok-bg);
        color: var(--ok-tx);
    }

    /* ====== CONTROLS ====== */
    /* .sticky {
        position: sticky;
        top: .70rem;
        z-index: 25;
    } */

    .controls {
        display: grid;
        grid-template-columns: 1fr;
        gap: .45rem;
        align-items: end;
    }

    @media (min-width:768px) {
        .controls {
            grid-template-columns: 1.8fr 1fr .7fr;
            gap: .55rem;
        }

        .controls.is-owner {
            grid-template-columns: 1.55fr 1fr 1fr .7fr;
        }
    }

    @media (max-width: 767.98px) {
        .stock-control-desktop-only {
            display: none !important;
        }
    }

    .search {
        position: relative;
    }

    .search i {
        position: absolute;
        left: .75rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--muted);
        pointer-events: none;
    }

    .search input {
        padding-left: 2.2rem;
    }

    .form-control-sm,
    .form-select-sm {
        border-radius: 10px;
    }

    /* ====== SUMMARY ====== */
    .summary {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
        margin-top: .55rem;
        align-items: center;
        color: var(--muted);
        font-size: .80rem;
    }

    .metric {
        display: inline-flex;
        align-items: baseline;
        gap: .35rem;
        padding: .14rem .5rem;
        border-radius: 999px;
        border: 1px solid var(--br);
        background: var(--soft2);
    }

    body[data-theme="dark"] .metric {
        background: rgba(15, 23, 42, .55);
    }

    .metric .k {
        font-size: .66rem;
        letter-spacing: .10em;
        text-transform: uppercase;
        color: var(--muted);
    }

    .metric .v {
        font-weight: 900;
        color: inherit;
    }

    /* ====== TABLE ====== */
    .table thead th {
        font-size: .70rem;
        text-transform: uppercase;
        letter-spacing: .10em;
        color: var(--muted);
        border-bottom: 1px solid var(--br);
        padding: .55rem .65rem;
        white-space: nowrap;
    }

    .table tbody td {
        padding: .52rem .65rem;
        border-top: 1px solid var(--soft);
        font-size: .90rem;
    }

    .code-btn {
        padding: .16rem .5rem;
        border-radius: 10px;
        border: 1px solid var(--code-br);
        background: var(--code-bg);
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-weight: 900;
        letter-spacing: .02em;
        color: var(--code-tx);
        text-decoration: none;
    }

    .code-btn .caret {
        color: var(--code-icon);
    }

    .caret {
        transition: transform .16s ease-out;
    }

    tr.is-open .caret {
        transform: rotate(90deg);
    }

    .badge-mover {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .14rem .5rem;
        border-radius: 999px;
        font-size: .68rem;
        letter-spacing: .10em;
        text-transform: uppercase;
        border: 1px solid var(--br);
        background: var(--soft2);
        color: var(--muted);
        white-space: nowrap;
    }

    .badge-fast {
        border-color: rgba(34, 197, 94, .28);
        background: rgba(34, 197, 94, .10);
        color: rgba(22, 163, 74, 1);
    }

    .badge-med {
        border-color: rgba(59, 130, 246, .28);
        background: rgba(59, 130, 246, .10);
        color: rgba(29, 78, 216, 1);
    }

    .badge-slow {
        border-color: rgba(245, 158, 11, .28);
        background: rgba(245, 158, 11, .10);
        color: rgba(180, 83, 9, 1);
    }

    .badge-dead {
        border-color: rgba(148, 163, 184, .32);
        background: rgba(148, 163, 184, .10);
        color: rgba(71, 85, 105, 1);
    }

    /* ====== DETAIL ====== */
    .detail-row {
        background: var(--soft2);
    }

    body[data-theme="dark"] .detail-row {
        background: rgba(15, 23, 42, .85);
    }

    .detail-inner {
        padding: .65rem .75rem;
        font-size: .82rem;
    }

    .detail-table {
        width: 100%;
        border-collapse: collapse;
    }

    .detail-table td {
        padding: .25rem .2rem;
        font-size: .82rem;
    }

    .detail-table tr+tr td {
        border-top: 1px dashed rgba(148, 163, 184, .35);
    }

    /* ====== LOADING OVERLAY ====== */
    .data-card {
        position: relative;
        overflow: visible;
        /* <— penting: jangan hidden biar nggak berasa kepotong */
    }

    .data-card .cardx-b {
        transition: opacity .15s ease-out;
    }

    .data-card.is-loading .cardx-b {
        opacity: .55;
    }

    .overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        pointer-events: none;
        /* <— jangan pernah ngeblok scroll */
        transition: opacity .15s ease-out;
        background: rgba(255, 255, 255, .55);
        backdrop-filter: blur(4px);
    }

    body[data-theme="dark"] .overlay {
        background: rgba(2, 6, 23, .55);
    }

    .overlay.show {
        opacity: 1;
        pointer-events: none;
    }

    .overlay-box {
        display: inline-flex;
        align-items: center;
        gap: .55rem;
        padding: .45rem .8rem;
        border-radius: 999px;
        border: 1px solid var(--br);
        background: rgba(255, 255, 255, .55);
    }

    body[data-theme="dark"] .overlay-box {
        background: rgba(2, 6, 23, .6);
    }

    /* ====== MINI TABLE ====== */
    .mini-table {
        width: 100%;
        border-collapse: collapse;
    }

    .mini-table th,
    .mini-table td {
        padding: .48rem .6rem;
        border-top: 1px solid var(--soft);
        vertical-align: top;
    }

    .mini-table thead th {
        border-top: none;
        border-bottom: 1px solid var(--br);
        font-size: .68rem;
        letter-spacing: .10em;
        text-transform: uppercase;
        color: var(--muted);
    }

    .mini-table .cat {
        font-weight: 850;
    }

    /* ====== MOBILE ====== */
    @media (max-width:576px) {
        .page-wrap {
            padding: .8rem .7rem 4.2rem;
        }

        .sticky {
            position: static !important;
            top: auto !important;
            z-index: auto !important;
        }

        /* hide table head already fine */
        .table thead {
            display: none;
        }

        .mcard {
            border-top: 1px solid var(--br);
            padding: .7rem .75rem;
        }

        .mcard:first-child {
            border-top: none;
        }

        .mcard-btn {
            padding: 0;
            border: none;
            background: none;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            text-align: left;
            gap: .7rem;
        }

        .m-left {
            display: flex;
            gap: .5rem;
            align-items: flex-start;
        }

        .m-no {
            font-size: .7rem;
            color: var(--muted);
            margin-top: .15rem;
        }

        .m-code {
            color: var(--code-tx);
            font-weight: 900;
            font-size: 1rem;
            background: var(--code-bg);
            border: 1px solid var(--code-br);
            border-radius: 10px;
            padding: .14rem .5rem;
            display: inline-flex;
            align-items: center;
        }

        .m-right {
            text-align: right;
        }

        .m-metric {
            display: grid;
            gap: .2rem;
        }

        .m-metric .k {
            font-size: .66rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .10em;
        }

        .m-metric .v {
            font-weight: 950;
            font-size: 1rem;
            color: var(--total-tx);
            background: var(--total-bg);
            border: 1px solid var(--total-br);
            border-radius: 10px;
            padding: .14rem .5rem;
            display: inline-flex;
            justify-content: flex-end;
            align-items: center;
            min-width: 5.3rem;
        }

        .m-detail {
            display: none;
            margin-top: .55rem;
        }

        .m-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .5rem;
            margin-bottom: .55rem;
        }

        .m-kpi {
            border: 1px solid var(--br);
            background: var(--soft2);
            border-radius: 12px;
            padding: .45rem .55rem;
        }

        body[data-theme="dark"] .m-kpi {
            background: rgba(15, 23, 42, .55);
        }

        .m-kpi .k {
            font-size: .64rem;
            color: var(--muted);
            letter-spacing: .10em;
            text-transform: uppercase;
        }

        .m-kpi .v {
            margin-top: .10rem;
            font-weight: 900;
        }

        /* anti zoom iOS */
        #searchInput {
            font-size: 16px;
        }

    }



</style>
