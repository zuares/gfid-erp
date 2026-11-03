{{-- resources/views/production/sewing_pickups/_bundle_picker.blade.php --}}

@push('head')
    <style>
        .sewing-pickup-bundle-picker {
            /* wrapper khusus partial ini */
        }

        .sewing-pickup-bundle-picker-card {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        body[data-theme="light"] .sewing-pickup-bundle-picker-card {
            background: #ffffff;
        }

        body[data-theme="dark"] .sewing-pickup-bundle-picker-card {
            border-color: rgba(51, 65, 85, 0.9);
            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.8),
                0 0 0 1px rgba(15, 23, 42, 0.9);
        }

        .sewing-pickup-bundle-picker-card .card-section {
            padding: 1rem 1.1rem;
            display: flex;
            flex-direction: column;
            gap: .6rem;
        }

        @media (min-width: 768px) {
            .sewing-pickup-bundle-picker-card .card-section {
                padding: 1.1rem 1.4rem;
            }
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .14rem .5rem;
            font-size: .7rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        /* ====== HEADER / RINGKASAN ====== */
        .bundle-picker-control-panel {
            display: flex;
            flex-direction: column;
            gap: .6rem;
            padding: .75rem;
            margin: -.25rem -.35rem .25rem;
            border: 1px solid rgba(148, 163, 184, .24);
            border-radius: 14px;
            background: #ffffff;
            box-shadow:
                0 12px 28px rgba(15, 23, 42, .12),
                0 0 0 1px rgba(255, 255, 255, .72);
        }

        body[data-theme="dark"] .bundle-picker-control-panel {
            background: #0f172a;
            border-color: rgba(71, 85, 105, .82);
            box-shadow:
                0 14px 30px rgba(0, 0, 0, .55),
                0 0 0 1px rgba(15, 23, 42, .9);
        }

        .pickup-control-meta {
            border-bottom: 1px dashed rgba(148, 163, 184, .36);
            padding-bottom: .65rem;
        }

        .pickup-control-meta .field-block {
            margin-bottom: 0;
        }

        .filter-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: .7rem;
            align-items: flex-start;
        }

        /* ========= SEARCH + FILTER SECTION ========= */
        .search-filter-section {
            margin-top: .2rem;
            margin-bottom: .4rem;
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }

        .search-filter-top {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem .75rem;
            align-items: flex-end;
            justify-content: space-between;
        }

        .search-label {
            font-size: .75rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .search-left {
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }

        .item-code-select-wrap {
            max-width: 260px;
            position: relative;
        }

        .item-code-select-wrap .form-select {
            font-size: .8rem;
            font-weight: 600;
            border-radius: .45rem;
            border-color: rgba(37, 99, 235, 0.35);
            background-color: var(--card);
            box-shadow:
                0 0 0 0 rgba(191, 219, 254, 0),
                0 0 0 0 rgba(37, 99, 235, 0);
            transition: box-shadow .2s ease, border-color .2s ease, background-color .2s ease;
        }

        .item-code-select-wrap .form-select:focus {
            border-color: rgba(37, 99, 235, 0.7);
            box-shadow:
                0 0 0 1px rgba(191, 219, 254, 0.85),
                0 4px 10px rgba(37, 99, 235, 0.15);
        }

        body[data-theme="dark"] .item-code-select-wrap .form-select {
            background-color: rgba(15, 23, 42, 0.96);
            border-color: rgba(129, 140, 248, 0.7);
        }

        @keyframes select-soft-pulse {
            0% {
                box-shadow:
                    0 0 0 1px rgba(191, 219, 254, 0.9),
                    0 6px 14px rgba(37, 99, 235, 0.25);
            }

            100% {
                box-shadow:
                    0 0 0 1px rgba(191, 219, 254, 0.5),
                    0 3px 8px rgba(37, 99, 235, 0.14);
            }
        }

        .item-code-select-wrap.focal-pulse .form-select {
            animation: select-soft-pulse .6s ease-out 2;
        }

        .search-input-wrap {
            max-width: 320px;
            width: 100%;
        }

        .search-input-wrap .form-control {
            font-size: .8rem;
            letter-spacing: .6px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .input-group-sm .input-group-text {
            font-size: .8rem;
        }

        .bundle-picker-control-panel .input-group-text,
        .bundle-picker-control-panel .form-control,
        .bundle-picker-control-panel .form-select {
            background-color: #ffffff;
        }

        body[data-theme="dark"] .bundle-picker-control-panel .input-group-text,
        body[data-theme="dark"] .bundle-picker-control-panel .form-control,
        body[data-theme="dark"] .bundle-picker-control-panel .form-select {
            background-color: #0f172a;
            color: #e5e7eb;
            border-color: rgba(71, 85, 105, .82);
        }

        .selection-toggle-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            font-size: .78rem;
            color: var(--muted);
        }

        .selection-toggle-right {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            font-size: .76rem;
        }

        .selection-toggle-right .form-check-input {
            cursor: pointer;
        }

        .selected-bundle-summary {
            border: 1px solid rgba(37, 99, 235, .22);
            border-radius: 10px;
            background: rgba(239, 246, 255, .72);
            padding: .48rem .58rem;
            display: flex;
            flex-direction: column;
            gap: .36rem;
        }

        body[data-theme="dark"] .selected-bundle-summary {
            background: rgba(30, 41, 59, .78);
            border-color: rgba(96, 165, 250, .28);
        }

        .selected-summary-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .selected-summary-title {
            display: inline-flex;
            align-items: center;
            gap: .28rem;
            font-size: .74rem;
            font-weight: 800;
            color: #1d4ed8;
        }

        body[data-theme="dark"] .selected-summary-title {
            color: #93c5fd;
        }

        .selected-summary-clear {
            border: 1px solid rgba(220, 38, 38, .22);
            border-radius: 999px;
            background: rgba(254, 242, 242, .86);
            color: #dc2626;
            font-size: .66rem;
            font-weight: 900;
            padding: .16rem .46rem;
            line-height: 1.2;
        }

        body[data-theme="dark"] .selected-summary-clear {
            background: rgba(127, 29, 29, .28);
            border-color: rgba(248, 113, 113, .30);
            color: #fca5a5;
        }

        .selected-summary-table-wrap {
            border: 1px solid rgba(148, 163, 184, .24);
            border-radius: 9px;
            overflow: auto;
            max-height: 132px;
            background: #ffffff;
            -webkit-overflow-scrolling: touch;
        }

        body[data-theme="dark"] .selected-summary-table-wrap {
            background: rgba(15, 23, 42, .96);
            border-color: rgba(71, 85, 105, .82);
        }

        .selected-summary-table {
            width: 100%;
            margin: 0;
            border-collapse: collapse;
            font-size: .7rem;
        }

        .selected-summary-table th {
            background: rgba(248, 250, 252, .9);
            color: var(--muted);
            font-size: .58rem;
            font-weight: 900;
            letter-spacing: .06em;
            padding: .28rem .4rem;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(148, 163, 184, .20);
        }

        body[data-theme="dark"] .selected-summary-table th {
            background: rgba(30, 41, 59, .72);
        }

        .selected-summary-table td {
            padding: .3rem .4rem;
            border-bottom: 1px solid rgba(148, 163, 184, .14);
            vertical-align: middle;
        }

        .selected-summary-table tbody tr:last-child td {
            border-bottom: none;
        }

        .selected-summary-table tfoot td {
            background: rgba(248, 250, 252, .92);
            border-top: 1px solid rgba(148, 163, 184, .24);
            color: #0f172a;
            font-weight: 900;
            padding: .34rem .4rem;
        }

        body[data-theme="dark"] .selected-summary-table tfoot td {
            background: rgba(30, 41, 59, .74);
            color: #e5e7eb;
        }

        .selected-summary-table .item-code-cell {
            font-weight: 900;
            color: #1d4ed8;
        }

        body[data-theme="dark"] .selected-summary-table .item-code-cell {
            color: #93c5fd;
        }

        .selected-summary-remove {
            width: 22px;
            height: 22px;
            border: 1px solid rgba(220, 38, 38, .24);
            border-radius: 999px;
            background: rgba(254, 242, 242, .9);
            color: #dc2626;
            font-size: .82rem;
            font-weight: 800;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        body[data-theme="dark"] .selected-summary-remove {
            background: rgba(127, 29, 29, .28);
            color: #fca5a5;
            border-color: rgba(248, 113, 113, .30);
        }

        .selected-summary-empty {
            font-size: .78rem;
            color: var(--muted);
            font-weight: 600;
        }

        /* Preview read-only, mengikuti accordion finansial di halaman cutting. */
        .sewing-pickup-hpp-section {
            overflow: hidden;
            margin-top: .6rem;
            padding: 0;
            border: 1px solid rgba(148, 163, 184, .24);
            border-radius: 12px;
            background: var(--card);
        }

        .sewing-pickup-hpp-section > summary {
            list-style: none;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .7rem;
            padding: .75rem .85rem;
            background: rgba(148, 163, 184, .045);
            border-bottom: 1px solid rgba(148, 163, 184, .16);
        }

        .sewing-pickup-hpp-section > summary::-webkit-details-marker { display: none; }
        .sewing-pickup-hpp-section > summary::after {
            content: '⌄';
            color: var(--muted);
            font-weight: 800;
            transition: transform .15s ease;
        }
        .sewing-pickup-hpp-section[open] > summary::after { transform: rotate(180deg); }
        .sewing-pickup-hpp-title { color: var(--text); font-size: .78rem; font-weight: 900; }
        .sewing-pickup-hpp-subtitle { color: var(--muted); font-size: .68rem; line-height: 1.35; margin-top: .12rem; }
        .sewing-pickup-hpp-body { padding: .75rem .85rem .85rem; }
        .sewing-pickup-hpp-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)); gap: .4rem; }
        .sewing-pickup-hpp-item {
            min-width: 0;
            padding: .4rem .5rem;
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 9px;
            background: var(--card);
        }
        .sewing-pickup-hpp-item span { display: block; color: var(--muted); font-size: .6rem; font-weight: 800; text-transform: uppercase; }
        .sewing-pickup-hpp-item strong { display: block; color: var(--text); font-size: .78rem; margin-top: .08rem; }
        .sewing-pickup-hpp-item.is-total { border-color: rgba(59, 130, 246, .25); background: rgba(219, 234, 254, .68); }
        .sewing-pickup-hpp-item.is-total strong { color: #1d4ed8; }
        .sewing-pickup-hpp-item.is-subtotal { border-color: rgba(59, 130, 246, .18); background: rgba(239, 246, 255, .55); }
        .sewing-pickup-hpp-item.is-subtotal strong { color: #1e40af; }
        .sewing-pickup-hpp-section.hpp-section { border-color: rgba(37, 99, 235, .2); }
        .sewing-pickup-hpp-section.hpp-section > summary {
            background: rgba(37, 99, 235, .045);
            border-bottom-color: rgba(37, 99, 235, .14);
        }
        .sewing-pickup-hpp-section.hpp-section .sewing-pickup-hpp-title { color: #1d4ed8; }
        .sewing-pickup-hpp-section.accounting-section { border-color: rgba(20, 184, 166, .2); }
        .sewing-pickup-hpp-section.accounting-section > summary {
            background: rgba(20, 184, 166, .045);
            border-bottom-color: rgba(20, 184, 166, .14);
        }
        .sewing-pickup-hpp-section.accounting-section .sewing-pickup-hpp-title { color: #0f766e; }
        .sewing-pickup-hpp-section.accounting-section .sewing-pickup-hpp-item.is-total {
            border-color: rgba(20, 184, 166, .25);
            background: rgba(204, 251, 241, .68);
        }
        .sewing-pickup-hpp-section.accounting-section .sewing-pickup-hpp-item.is-total strong { color: #0f766e; }
        .sewing-pickup-hpp-section.mutation-section { border-color: rgba(148, 163, 184, .28); }
        .sewing-pickup-hpp-section.mutation-section > summary {
            background: rgba(148, 163, 184, .045);
            border-bottom-color: rgba(148, 163, 184, .16);
        }
        .sewing-pickup-hpp-section.mutation-section .sewing-pickup-hpp-title { color: var(--text); }
        .sewing-pickup-hpp-table { font-size: .7rem; margin-top: .65rem; }
        .sewing-pickup-hpp-table th { color: var(--muted); font-size: .6rem; text-transform: uppercase; white-space: nowrap; }
        .sewing-pickup-hpp-table td, .sewing-pickup-hpp-table th { padding: .32rem .4rem; vertical-align: middle; }
        .sewing-pickup-hpp-note { color: var(--muted); font-size: .66rem; line-height: 1.4; }
        .sewing-pickup-hpp-unit { display: block; color: var(--muted); font-size: .61rem; font-weight: 500; line-height: 1.25; white-space: nowrap; }
        .sewing-pickup-hpp-warning { color: #92400e; background: rgba(254, 243, 199, .72); border: 1px solid rgba(245, 158, 11, .35); border-radius: 8px; padding: .35rem .5rem; font-size: .66rem; line-height: 1.35; }

        body[data-theme="dark"] .sewing-pickup-hpp-section { border-color: rgba(71, 85, 105, .82); background: var(--card); }
        body[data-theme="dark"] .sewing-pickup-hpp-section > summary { background: rgba(148, 163, 184, .08); border-bottom-color: rgba(71, 85, 105, .82); }
        body[data-theme="dark"] .sewing-pickup-hpp-section.hpp-section { border-color: rgba(96, 165, 250, .35); }
        body[data-theme="dark"] .sewing-pickup-hpp-section.hpp-section > summary { background: rgba(37, 99, 235, .12); border-bottom-color: rgba(96, 165, 250, .25); }
        body[data-theme="dark"] .sewing-pickup-hpp-section.hpp-section .sewing-pickup-hpp-title { color: #93c5fd; }
        body[data-theme="dark"] .sewing-pickup-hpp-section.accounting-section { border-color: rgba(45, 212, 191, .35); }
        body[data-theme="dark"] .sewing-pickup-hpp-section.accounting-section > summary { background: rgba(20, 184, 166, .12); border-bottom-color: rgba(45, 212, 191, .25); }
        body[data-theme="dark"] .sewing-pickup-hpp-section.accounting-section .sewing-pickup-hpp-title,
        body[data-theme="dark"] .sewing-pickup-hpp-section.accounting-section .sewing-pickup-hpp-item.is-total strong { color: #5eead4; }
        body[data-theme="dark"] .sewing-pickup-hpp-section.accounting-section .sewing-pickup-hpp-item.is-total { background: rgba(19, 78, 74, .42); }
        body[data-theme="dark"] .sewing-pickup-hpp-item { background: rgba(15, 23, 42, .42); }
        body[data-theme="dark"] .sewing-pickup-hpp-item.is-subtotal { background: rgba(30, 64, 175, .2); }

        @media (max-width: 575.98px) {
            .sewing-pickup-hpp-grid { gap: .3rem; }
            .sewing-pickup-hpp-item { padding: .34rem .4rem; }
            .sewing-pickup-hpp-item strong { font-size: .68rem; }
            .sewing-pickup-hpp-table { min-width: 760px; }
        }

        .sewing-supply-summary {
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 10px;
            background: #ffffff;
            padding: .5rem .58rem;
            display: flex;
            flex-direction: column;
            gap: .38rem;
        }

        body[data-theme="dark"] .sewing-supply-summary {
            background: rgba(15, 23, 42, .96);
            border-color: rgba(71, 85, 105, .82);
        }

        .sewing-supply-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
            font-size: .72rem;
            font-weight: 900;
        }

        .sewing-supply-title {
            color: #0f172a;
        }

        body[data-theme="dark"] .sewing-supply-title {
            color: #e5e7eb;
        }

        .sewing-supply-status {
            border-radius: 999px;
            padding: .12rem .48rem;
            font-size: .64rem;
            font-weight: 900;
            background: rgba(22, 163, 74, .10);
            color: #15803d;
        }

        .sewing-supply-status.is-danger {
            background: rgba(220, 38, 38, .10);
            color: #dc2626;
        }

        .sewing-supply-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .68rem;
        }

        .sewing-supply-table th,
        .sewing-supply-table td {
            padding: .28rem .35rem;
            border-bottom: 1px solid rgba(148, 163, 184, .14);
            vertical-align: middle;
        }

        .sewing-supply-table th {
            color: var(--muted);
            font-size: .56rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .06em;
            background: rgba(248, 250, 252, .88);
        }

        body[data-theme="dark"] .sewing-supply-table th {
            background: rgba(30, 41, 59, .72);
        }

        .sewing-supply-table tr:last-child td {
            border-bottom: none;
        }

        .supply-ok {
            color: #15803d;
            font-weight: 900;
        }

        .supply-short {
            color: #dc2626;
            font-weight: 900;
        }

        /* ========== TABLE / BUNDLE ROW ========== */
        .bundle-row {
            transition:
                background-color .16s ease,
                box-shadow .16s ease,
                border-color .16s ease,
                transform .1s ease;
        }

        .bundle-row td {
            border-top-color: rgba(148, 163, 184, 0.25) !important;
        }

        .bundle-card-row {
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(255, 255, 255, 0.96);
            box-shadow:
                0 6px 16px rgba(15, 23, 42, 0.10),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .bundle-card-row:hover {
            transform: translateY(-1px);
            box-shadow:
                0 10px 24px rgba(15, 23, 42, 0.14),
                0 0 0 1px rgba(148, 163, 184, 0.25);
        }

        body[data-theme="dark"] .bundle-card-row {
            background: rgba(15, 23, 42, 0.96);
            border-color: rgba(51, 65, 85, 0.9);
            box-shadow:
                0 8px 20px rgba(0, 0, 0, 0.55),
                0 0 0 1px rgba(30, 64, 175, 0.6);
        }

        .bundle-card-row.is-selected {
            border-color: rgba(59, 130, 246, 0.85);
            box-shadow:
                0 0 0 1px rgba(191, 219, 254, 0.9),
                0 14px 26px rgba(37, 99, 235, 0.18);
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.08) 0,
                    rgba(255, 255, 255, 0.98) 55%);
            transform: translateY(-1px);
        }

        body[data-theme="dark"] .bundle-card-row.is-selected {
            background: radial-gradient(circle at top left,
                    rgba(37, 99, 235, 0.3) 0,
                    rgba(15, 23, 42, 0.96) 55%);
            border-color: rgba(147, 197, 253, 0.9);
            box-shadow:
                0 0 0 1px rgba(59, 130, 246, 0.8),
                0 18px 32px rgba(15, 23, 42, 0.9);
        }

        .bundle-card-row.summary-focus {
            outline: 2px solid rgba(37, 99, 235, .75);
            outline-offset: 2px;
        }

        .row-empty {
            box-shadow: inset 3px 0 0 rgba(148, 163, 184, .3);
        }

        .row-picked {
            box-shadow: inset 3px 0 0 rgba(37, 99, 235, .9);
        }

        .qty-ready-pill {
            border-radius: 999px;
            padding: .08rem .58rem;
            font-size: .78rem;
            font-weight: 600;
            background: rgba(13, 110, 253, 0.08);
            color: #0d6efd;
            display: inline-block;
            max-width: 100%;
        }

        .reject-qc-pill {
            display: inline-block;
            border-radius: 999px;
            padding: .08rem .48rem;
            font-size: .72rem;
            font-weight: 800;
            background: rgba(220, 38, 38, .09);
            color: #dc2626;
        }

        .reject-qc-zero {
            color: var(--muted);
            font-size: .72rem;
        }

        .reject-recorded-qty {
            display: inline-flex;
            align-items: center;
            margin-right: .3rem;
            color: #9f1239;
            font-size: .68rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .reject-picker-trigger {
            display: inline-flex;
            align-items: center;
            gap: .28rem;
            min-width: 74px;
            justify-content: center;
            border: 1px dashed rgba(220, 38, 38, .38);
            border-radius: 999px;
            padding: .16rem .5rem;
            background: rgba(254, 242, 242, .72);
            color: #b91c1c;
            font-size: .72rem;
            font-weight: 800;
            cursor: pointer;
            transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
        }

        .reject-picker-trigger:hover,
        .reject-picker-trigger:focus-visible,
        .reject-picker-trigger.has-value {
            border-style: solid;
            border-color: rgba(220, 38, 38, .62);
            background: rgba(254, 226, 226, .92);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, .1);
            outline: none;
        }

        .reject-picker-trigger .reject-picker-reason {
            max-width: 92px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: var(--muted);
            font-size: .61rem;
            font-weight: 600;
        }

        .reject-picker-trigger.is-invalid {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, .12);
        }

        body[data-theme="dark"] .reject-picker-trigger {
            background: rgba(127, 29, 29, .2);
            color: #fca5a5;
        }

        body[data-theme="dark"] .reject-picker-trigger:hover,
        body[data-theme="dark"] .reject-picker-trigger:focus-visible,
        body[data-theme="dark"] .reject-picker-trigger.has-value {
            background: rgba(127, 29, 29, .42);
            color: #fecaca;
        }

        .reject-picker-modal-note {
            color: var(--muted);
            font-size: .72rem;
            line-height: 1.45;
        }

        .reject-picker-modal .form-label {
            font-size: .72rem;
            font-weight: 800;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        /* Focal point sumber bundle: tanggal cutting harus mudah dikenali dan diklik. */
        .cutting-date-link {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border: 1px solid rgba(37, 99, 235, .24);
            border-radius: 9px;
            padding: .22rem .42rem;
            color: #1d4ed8;
            background: rgba(239, 246, 255, .72);
            line-height: 1.15;
            text-decoration: none;
            transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
        }

        .cutting-date-link:hover,
        .cutting-date-link:focus-visible {
            color: #1e40af;
            background: rgba(219, 234, 254, .92);
            border-color: rgba(37, 99, 235, .55);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
            text-decoration: none;
        }

        .cutting-date-link .cutting-date-caption {
            display: block;
            color: var(--muted);
            font-size: .58rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .cutting-date-link .cutting-date-value {
            display: block;
            font-size: .74rem;
            font-weight: 800;
        }

        .cutting-date-link .cutting-bundle-code {
            display: block;
            margin-top: .08rem;
            color: var(--muted);
            font-size: .62rem;
            font-weight: 600;
        }

        body[data-theme="dark"] .cutting-date-link {
            color: #bfdbfe;
            background: rgba(30, 64, 175, .24);
            border-color: rgba(96, 165, 250, .35);
        }

        body[data-theme="dark"] .cutting-date-link:hover,
        body[data-theme="dark"] .cutting-date-link:focus-visible {
            color: #dbeafe;
            background: rgba(30, 64, 175, .42);
        }

        .qty-input {
            font-weight: 500;
            transition: font-weight .12s ease, box-shadow .12s ease, border-color .12s ease;
        }

        .qty-input-active {
            font-weight: 600;
            border-color: rgba(37, 99, 235, .75);
            box-shadow: 0 0 0 1px rgba(37, 99, 235, .4);
        }

        .qty-input.border-warning {
            border-color: #eab308 !important;
        }

        /* FLOATING ACTION (MOBILE): SUBMIT SHORTCUT */
        .bundle-actions-fab {
            position: fixed;
            right: .9rem;
            bottom: 7.2rem;
            z-index: 40;
            display: none;
        }

        .bundle-submit-fab {
            border-radius: 999px;
            border: none;
            padding: .4rem 1rem;
            font-size: .82rem;
            font-weight: 600;
            background: linear-gradient(135deg, #0d6efd 0%, #2563eb 60%, #1d4ed8 100%);
            color: #f9fafb;
            box-shadow:
                0 12px 24px rgba(15, 23, 42, .35),
                0 0 0 1px rgba(191, 219, 254, .9);
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .bundle-submit-fab:disabled {
            opacity: .6;
            cursor: not-allowed;
            box-shadow:
                0 6px 16px rgba(15, 23, 42, .25),
                0 0 0 1px rgba(148, 163, 184, .7);
        }

        /* ============ MOBILE (<= 767.98px) ============ */
        /* ============ MOBILE (<= 767.98px) ============ */
        @media (max-width: 767.98px) {

            .sewing-pickup-bundle-picker-card .card-section {
                padding: .85rem .9rem;
                gap: .5rem;
            }

            .filter-header {
                order: 0;
            }

            .search-filter-section {
                order: 1;
            }

            .bundle-picker-control-panel {
                gap: .55rem;
                margin: -.15rem -.15rem .25rem;
                padding: .65rem;
            }

            .pickup-control-meta {
                padding-bottom: .55rem;
            }

            .table-wrap {
                order: 2;
                overflow-x: visible;
            }

            .search-filter-top {
                flex-direction: column;
                align-items: stretch;
                gap: .6rem;
            }

            .search-input-wrap,
            .item-code-select-wrap {
                max-width: 100%;
            }

            .table-sewing-pickup {
                width: 100%;
                table-layout: fixed;
                border-collapse: separate;
                border-spacing: 0 8px;
            }

            .table-sewing-pickup thead {
                display: none;
            }

            .table-sewing-pickup tbody tr {
                display: block;
                width: 100%;
                box-sizing: border-box;
                border-radius: 12px;
                border: 1px solid rgba(148, 163, 184, 0.3);
                padding: .48rem .62rem .52rem;
                margin-bottom: .34rem;
                background: #ffffff;
                cursor: pointer;
                box-shadow:
                    0 8px 24px rgba(15, 23, 42, 0.10),
                    0 0 0 1px rgba(15, 23, 42, 0.02);
                overflow: hidden;
            }

            body[data-theme="dark"] .table-sewing-pickup tbody tr {
                background: rgba(15, 23, 42, 0.96);
            }

            .table-sewing-pickup tbody tr:last-child {
                margin-bottom: 2.75rem;
            }

            .table-sewing-pickup td {
                display: block;
                border: none !important;
                padding: .1rem 0;
            }

            .td-mobile-extra {
                padding: 0 !important;
            }

            .td-desktop-only {
                display: none !important;
            }

            .mobile-row-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: .58rem;
                margin-bottom: .12rem;
            }

            .mobile-row-header-left {
                font-size: clamp(.74rem, 2.4vw, .86rem);
                display: flex;
                flex-direction: column;
                gap: .02rem;
                flex: 1;
                min-width: 0;
            }

            .mobile-row-header-topline {
                display: flex;
                align-items: center;
                gap: .4rem;
                min-width: 0;
                flex-wrap: wrap;
            }

            .mobile-row-header-left .row-index {
                font-size: clamp(.62rem, 1.9vw, .72rem);
                color: var(--muted);
                flex-shrink: 0;
            }

            /* ✅ KODE BARANG RESPONSIVE */
            .mobile-row-header-left .item-code {
                font-size: clamp(.84rem, 3.35vw, 1rem) !important;
                font-weight: 800 !important;
                color: #2563eb !important;
                letter-spacing: .12px;

                white-space: normal;
                max-width: 100%;
                overflow: visible;
                text-overflow: clip;
                word-break: break-word;
                flex: 1 1 auto;
            }

            .mobile-row-header-left .item-name {
                font-size: clamp(.72rem, 2.6vw, .84rem) !important;
                color: var(--muted) !important;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .mobile-row-header-right {
                text-align: right;
                font-size: clamp(.66rem, 2.3vw, .78rem);
                width: clamp(128px, 42vw, 180px);
                min-width: clamp(128px, 42vw, 180px);
                flex-shrink: 0;
            }

            .mobile-row-header-right .qty-ready-label {
                font-size: clamp(.56rem, 1.9vw, .66rem) !important;
                font-weight: 600;
                color: var(--muted);
                text-transform: uppercase;
                letter-spacing: .28px;
                margin-bottom: .02rem;
            }

            .mobile-row-header-right .qty-ready-value {
                display: flex;
                justify-content: flex-end;
            }

            .mobile-row-header-right .qty-ready-value .qty-ready-pill {
                background: rgba(37, 99, 235, 0.12) !important;
                color: #1d4ed8 !important;
                padding: .12rem .46rem !important;
                border-radius: 999px !important;
                font-size: clamp(.78rem, 3vw, .9rem) !important;
                font-weight: 800 !important;
                max-width: 100%;
                min-width: 0;
                text-align: center;
                display: inline-block;
            }

            .mobile-row-meta {
                font-size: clamp(.64rem, 2.25vw, .74rem);
                color: var(--muted);
                margin-bottom: .06rem;
                display: flex;
                justify-content: space-between;
                gap: .4rem;
            }

            .mobile-row-meta-left {
                flex: 1;
                min-width: 0;
            }

            .mobile-row-meta-label {
                font-size: clamp(.56rem, 1.9vw, .64rem);
                text-transform: uppercase;
                color: var(--muted);
                opacity: .9;
                letter-spacing: .08em;
            }

            .mobile-row-meta-value {
                font-size: clamp(.64rem, 2.25vw, .74rem);
                word-break: break-word;
            }

            .mobile-row-meta-value .mono {
                font-size: clamp(.72rem, 2.4vw, .82rem);
            }

            .mobile-cutting-date {
                display: inline-flex;
                align-items: center;
                gap: .35rem;
                min-height: 36px;
                padding: .26rem .42rem;
                border: 1px solid rgba(37, 99, 235, .28);
                border-radius: 9px;
                background: rgba(239, 246, 255, .72);
                color: #1d4ed8;
                text-decoration: none;
            }

            .mobile-cutting-date:focus-visible,
            .mobile-cutting-date:hover {
                color: #1e40af;
                border-color: rgba(37, 99, 235, .58);
                box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
                text-decoration: none;
            }

            .mobile-cutting-date .cutting-date-value {
                font-size: .74rem;
                font-weight: 800;
            }

            .mobile-cutting-date .cutting-bundle-code {
                display: block;
                color: var(--muted);
                font-size: .61rem;
                font-weight: 600;
            }

            body[data-theme="dark"] .mobile-cutting-date {
                background: rgba(30, 64, 175, .24);
                border-color: rgba(96, 165, 250, .35);
                color: #bfdbfe;
            }

            .mobile-row-footer-left .pickup-label {
                font-size: clamp(.62rem, 2.2vw, .72rem) !important;
                font-weight: 600 !important;
                color: #2563eb !important;
                margin-bottom: .04rem;
                letter-spacing: .17px;
                text-transform: uppercase;
            }

            .mobile-row-footer-left input.qty-input {
                width: 100% !important;
                min-width: 0;
                font-size: clamp(.82rem, 3vw, .94rem) !important;
                font-weight: 600 !important;
                padding-block: .24rem !important;
                border: 1.4px solid rgba(37, 99, 235, .45) !important;
                border-radius: 8px !important;
                box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .18) !important;
                text-align: center !important;
            }

            @media (max-width: 380px) {
                .mobile-row-header {
                    gap: .5rem;
                }

                .mobile-row-header-right {
                    width: clamp(120px, 44vw, 156px);
                    min-width: clamp(120px, 44vw, 156px);
                }
            }

            .mobile-row-footer-left input.qty-input:focus {
                border-color: #2563eb !important;
                box-shadow: 0 0 0 2px rgba(37, 99, 235, .3) !important;
            }

            .gudang-section {
                display: none !important;
            }

            .bundle-actions-fab {
                display: flex;
            }
        }

        @media (min-width: 768px) {
            .td-mobile-extra {
                display: none !important;
            }

            .bundle-actions-fab {
                display: none !important;
            }
        }
    </style>
@endpush
@php
    $oldLines = old('lines', []);
    $preselectedBundleId = request('bundle_id');
    $wipCostByBundle = $wipCostByBundle ?? [];
    $sewingRatePreview = $sewingRatePreview ?? [];
    $sewingRejectQtyByBundle = $sewingRejectQtyByBundle ?? [];
    $rejectInputEnabled = (bool) ($rejectInputEnabled ?? false);
    $formatQty = static function ($value): string {
        return rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',');
    };

    // filter dulu kalau ada bundle_id spesifik
    $displayBundles = $preselectedBundleId ? $bundles->where('id', (int) $preselectedBundleId) : $bundles;

    // hanya bundle dengan qty_ready_for_sewing > 0
    $displayBundles = $displayBundles
        ->map(function ($b) {
            $b->computed_qty_ready = (float) $b->qty_ready_for_sewing;
            return $b;
        })
        ->filter(fn($b) => $b->computed_qty_ready > 0 || $b->status === 'cut')
        ->values();

    $totalBundlesReady = $displayBundles->count();
    $totalQtyReady = $displayBundles->sum(fn($b) => (float) $b->computed_qty_ready);

    $itemCodes = $displayBundles->pluck('finishedItem.code')->filter()->unique()->sort()->values();
@endphp

<div class="sewing-pickup-bundle-picker mb-3">
    <div class="card sewing-pickup-bundle-picker-card">
        <div class="card-section">

            <div class="bundle-picker-control-panel">
                {{-- DESKTOP: toggle hanya baris yang ada pickup --}}
                <div class="filter-header">
                    <div class="selection-toggle-row d-none d-md-flex">
                        <div class="selection-toggle-right">
                            <label class="form-check-label" for="toggle-only-picked">
                                Tampilkan hanya baris dengan pickup
                            </label>
                            <input type="checkbox" class="form-check-input" id="toggle-only-picked">
                        </div>
                    </div>
                </div>

                {{-- FILTER (select kode barang + search) --}}
                <div class="search-filter-section">
                    <div class="search-filter-top">
                        <div class="search-left">
                            <div class="search-label">
                                Cari item
                            </div>

                            <div class="item-code-select-wrap" id="item-code-select-wrap">
                                <select id="item-code-select" class="form-select form-select-sm">
                                    <option value="">Semua kode item</option>
                                    @foreach ($itemCodes as $code)
                                        <option value="{{ $code }}">{{ $code }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="input-group input-group-sm search-input-wrap">
                            <span class="input-group-text border-end-0 bg-white">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" id="bundle-filter-input"
                                class="form-control form-control-sm border-start-0 text-uppercase"
                                placeholder="Kode item / lot.." autocomplete="off">
                        </div>
                    </div>
                </div>

                <div class="selected-bundle-summary" id="selected-bundle-summary">
                    <div class="selected-summary-head">
                        <div class="selected-summary-title">
                            <i class="bi bi-check2-square"></i>
                            <span>Item sudah dipilih</span>
                            <span class="badge bg-primary rounded-pill" id="selected-summary-count">0</span>
                        </div>
                        <button type="button" class="selected-summary-clear d-none" id="selected-summary-clear">
                            Bersihkan semua
                        </button>
                    </div>
                    <div class="selected-summary-table-wrap d-none" id="selected-summary-list">
                        <table class="selected-summary-table">
                            <thead>
                                <tr>
                                    <th style="width:44px;">No</th>
                                    <th>Kode Item</th>
                                    <th class="text-end" style="width:110px;">Qty</th>
                                    <th class="text-end" style="width:80px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="selected-summary-body"></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2">Total</td>
                                    <td class="text-end mono" id="selected-summary-total-qty">0,00</td>
                                    <td class="text-end mono">
                                        <span id="selected-summary-total-items">0</span> ikat
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="selected-summary-empty" id="selected-summary-empty">
                        Belum ada item yang diambil.
                    </div>
                </div>

                {{-- Ringkasan kelengkapan RM: hanya untuk owner (menyingkap kebutuhan & stok). --}}
                @if (auth()->user()?->isOwner())
                <div class="sewing-supply-summary d-none" id="sewing-supply-summary">
                    <div class="sewing-supply-head">
                        <span class="sewing-supply-title">Kelengkapan jahit dari RM</span>
                        <span class="sewing-supply-status" id="sewing-supply-status">Cukup</span>
                    </div>
                    <div class="table-responsive">
                        <table class="sewing-supply-table">
                            <thead>
                                <tr>
                                    <th>Material</th>
                                    <th class="text-end">PCS</th>
                                </tr>
                            </thead>
                            <tbody id="sewing-supply-body"></tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>

            @if (auth()->user()?->isOwner())
                <details class="sewing-pickup-hpp-section hpp-section" id="sewing-pickup-hpp-section">
                    <summary>
                        <div>
                            <div class="sewing-pickup-hpp-title">
                                <i class="bi bi-calculator me-1" aria-hidden="true"></i> Estimasi HPP Sewing Pickup
                            </div>
                            <div class="sewing-pickup-hpp-subtitle">
                                HPP pickup baik = nilai WIP-CUT + upah jahit. BOM kelengkapan sudah dipotong saat QC Cutting, jadi tidak dihitung ulang di sini.
                            </div>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border" id="sewing-pickup-hpp-badge">Belum pilih</span>
                    </summary>
                    <div class="sewing-pickup-hpp-body">
                        <div class="sewing-pickup-hpp-grid">
                            <div class="sewing-pickup-hpp-item">
                                <span>Bahan baku</span>
                                <strong class="mono" id="sewing-hpp-raw-material-total">Rp 0</strong>
                            </div>
                            <div class="sewing-pickup-hpp-item">
                                <span>Bahan pendukung</span>
                                <strong class="mono" id="sewing-hpp-support-total">Rp 0</strong>
                            </div>
                            <div class="sewing-pickup-hpp-item">
                                <span>Upah potong</span>
                                <strong class="mono" id="sewing-hpp-cutting-wage-total">Rp 0</strong>
                            </div>
                            <div class="sewing-pickup-hpp-item is-subtotal">
                                <span>Total WIP-CUT</span>
                                <strong class="mono" id="sewing-hpp-wip-cut-total">Rp 0</strong>
                            </div>
                            <div class="sewing-pickup-hpp-item">
                                <span>Upah jahit</span>
                                <strong class="mono" id="sewing-hpp-sewing-wage-total">Rp 0</strong>
                            </div>
                            <div class="sewing-pickup-hpp-item is-total">
                                <span>Total HPP masuk WIP-SEW</span>
                                <strong class="mono" id="sewing-hpp-total">Rp 0</strong>
                            </div>
                        </div>
                        <div class="sewing-pickup-hpp-note mt-2" id="sewing-hpp-operator-note">
                            Upah akan mengikuti operator jahit yang dipilih pada konfirmasi.
                        </div>
                        <div class="sewing-pickup-hpp-note">Nilai pada kolom komponen dihitung per pcs.</div>
                        <div class="sewing-pickup-hpp-warning d-none mt-2" id="sewing-hpp-warning"></div>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm align-middle sewing-pickup-hpp-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Bundle / item</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end" title="Bahan baku per pcs">Bahan</th>
                                        <th class="text-end" title="Bahan pendukung per pcs">Pendukung</th>
                                        <th class="text-end" title="Upah potong per pcs">Potong</th>
                                        <th class="text-end" title="Total WIP-CUT per pcs">WIP-CUT</th>
                                        <th class="text-end" title="Upah jahit per pcs">Jahit</th>
                                        <th class="text-end" title="HPP per pcs">HPP</th>
                                        <th class="text-end" title="Total HPP">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="sewing-pickup-hpp-body"></tbody>
                            </table>
                        </div>
                    </div>
                </details>

                <details class="sewing-pickup-hpp-section accounting-section" id="sewing-pickup-accounting-section">
                    <summary>
                        <div>
                            <div class="sewing-pickup-hpp-title">
                                <i class="bi bi-journal-check me-1" aria-hidden="true"></i> Akuntansi Baru
                            </div>
                            <div class="sewing-pickup-hpp-subtitle">
                                Preview jurnal mirror berdasarkan pickup baik dan reject susulan. Legacy tidak diubah.
                            </div>
                        </div>
                        <span class="badge bg-success text-white" id="sewing-pickup-accounting-badge">Belum pilih</span>
                    </summary>
                    <div class="sewing-pickup-hpp-body">
                        <div class="sewing-pickup-hpp-grid">
                            <div class="sewing-pickup-hpp-item">
                                <span>Nilai pickup baik</span>
                                <strong class="mono" id="sewing-accounting-good-total">Rp 0</strong>
                            </div>
                            <div class="sewing-pickup-hpp-item">
                                <span>Reject susulan</span>
                                <strong class="mono" id="sewing-accounting-reject-total">Rp 0</strong>
                            </div>
                            <div class="sewing-pickup-hpp-item is-total">
                                <span>Total jurnal baru</span>
                                <strong class="mono" id="sewing-accounting-total">Rp 0</strong>
                            </div>
                        </div>
                        <div class="sewing-pickup-hpp-note mt-2" id="sewing-accounting-note">
                            Pickup baik memindahkan nilai WIP-CUT ke WIP-SEW dan menambahkan upah jahit. Reject susulan dipindahkan ke REJ-CUT tanpa upah.
                        </div>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm align-middle sewing-pickup-hpp-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Skenario</th>
                                        <th>Debit</th>
                                        <th>Kredit</th>
                                        <th class="text-end">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody id="sewing-pickup-accounting-body"></tbody>
                            </table>
                        </div>
                    </div>
                </details>

                <details class="sewing-pickup-hpp-section mutation-section" id="sewing-pickup-mutation-section">
                    <summary>
                        <div>
                            <div class="sewing-pickup-hpp-title">
                                <i class="bi bi-arrow-left-right me-1" aria-hidden="true"></i> Mutasi Stok
                            </div>
                            <div class="sewing-pickup-hpp-subtitle">
                                Rencana perpindahan stok sebelum tombol Simpan, termasuk jalur reject susulan.
                            </div>
                        </div>
                        <span class="badge bg-secondary text-white" id="sewing-pickup-mutation-badge">Belum pilih</span>
                    </summary>
                    <div class="sewing-pickup-hpp-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle sewing-pickup-hpp-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Skenario</th>
                                        <th>Dari</th>
                                        <th>Ke</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Nilai keluar</th>
                                        <th class="text-end">Nilai masuk</th>
                                    </tr>
                                </thead>
                                <tbody id="sewing-pickup-mutation-body"></tbody>
                            </table>
                        </div>
                        <div class="sewing-pickup-hpp-note mt-2" id="sewing-mutation-note">
                            Belum ada mutasi yang dipilih.
                        </div>
                    </div>
                </details>
            @endif
            @error('lines')
                <div class="alert alert-danger py-1 small mb-2">
                    {{ $message }}
                </div>
            @enderror

            {{-- LIST BUNDLES --}}
            <div class="table-wrap">
                <table class="table table-sm align-middle mono table-sewing-pickup mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="text-center">#</th>
                            <th style="width: 130px;">Tgl Cutting</th>
                            <th style="width: 160px;">Item Jadi</th>
                            <th style="width: 140px;">Lot</th>
                            <th style="width: 110px;" class="text-end">Cutting</th>
                            <th style="width: 110px;" class="text-end">Ready</th>
                            <th style="width: 105px;" class="text-end">Reject QC</th>
                            @if ($rejectInputEnabled)
                                                <th style="width: 150px;" class="text-end">Reject Susulan</th>
                            @endif
                            <th style="width: 130px;" class="text-end">Pickup</th>
                            <th style="width: 80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($displayBundles as $idx => $b)
                            @php
                                $qc = $b->qcResults
                                    ? $b->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first()
                                    : null;

                                $oldLine = $oldLines[$idx] ?? null;

                                // sekarang ambil dari accessor / computed
                                $qtyRemain = (float) ($b->computed_qty_ready ?? $b->qty_ready_for_sewing);
                                $qtyRejectQc = (float) ($qc?->qty_reject ?? 0);

                                $isUnQCed = $b->status === 'cut';

                                if ($qtyRemain <= 0 && !$isUnQCed) {
                                    continue;
                                }

                                $defaultQtyPickup = $oldLine['qty_bundle'] ?? null;

                                if ($defaultQtyPickup === null && $preselectedBundleId == $b->id) {
                                    $defaultQtyPickup = $qtyRemain;
                                }

                                $oldQtyName = 'lines.' . $idx . '.qty_bundle';
                                $oldRejectName = 'lines.' . $idx . '.qty_reject';
                                $oldRejectReasonName = 'lines.' . $idx . '.reject_reason';

                                $cutDateObj = $b->cuttingJob?->date ?? $b->cuttingJob?->created_at;
                                $cutDateLabel = $cutDateObj ? $cutDateObj->format('d M Y') : '-';

                                $lotCode = $b->cuttingJob?->lot?->code;
                                $shortBundleCode = preg_replace('/^BND-\d{8}-/', '', $b->bundle_code);
                                $wipUnitCost = (float) data_get($wipCostByBundle, $b->id . '.unit_cost', 0);
                                $rawMaterialUnitCost = (float) data_get($wipCostByBundle, $b->id . '.raw_material_unit_cost', 0);
                                $supportUnitCost = (float) data_get($wipCostByBundle, $b->id . '.support_unit_cost', 0);
                                $cuttingWageUnitCost = (float) data_get($wipCostByBundle, $b->id . '.cutting_wage_unit_cost', 0);
                                $postedRejectQty = (float) ($sewingRejectQtyByBundle[$b->id] ?? 0);
                            @endphp

                            <tr class="bundle-row bundle-card-row row-empty {{ $isUnQCed ? 'opacity-50 is-unqced cursor-pointer' : '' }}" data-row-index="{{ $idx }}"
                                data-qty-ready="{{ $qtyRemain }}" data-bundle-code="{{ $b->bundle_code }}"
                                data-finished-item-id="{{ $b->finished_item_id }}"
                                data-item-code="{{ $b->finishedItem?->code }}"
                                data-item-name="{{ $b->finishedItem?->name }}" data-lot-code="{{ $lotCode }}"
                                data-wip-unit-cost="{{ number_format($wipUnitCost, 2, '.', '') }}"
                                data-raw-material-unit-cost="{{ number_format($rawMaterialUnitCost, 2, '.', '') }}"
                                data-support-unit-cost="{{ number_format($supportUnitCost, 2, '.', '') }}"
                                data-cutting-wage-unit-cost="{{ number_format($cuttingWageUnitCost, 2, '.', '') }}"
                                data-detail-url="{{ route('production.qc.cutting.edit', $b->cutting_job_id) }}">

                                <td class="d-none">
                                    <input type="hidden" name="lines[{{ $idx }}][bundle_id]"
                                        value="{{ $b->id }}">
                                </td>

                                {{-- DESKTOP --}}
                                <td class="d-none d-md-table-cell td-desktop-only text-center">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        @if ($isUnQCed)
                                            <input type="checkbox" class="form-check-input row-check" disabled title="Belum di QC">
                                        @else
                                            <input type="checkbox" class="form-check-input row-check"
                                                data-row-index="{{ $idx }}">
                                        @endif
                                        <span class="small text-muted">{{ $loop->iteration }}</span>
                                    </div>
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only">
                                    <a href="{{ route('production.qc.cutting.edit', $b->cutting_job_id) }}"
                                       class="cutting-date-link"
                                       aria-label="Buka QC Cutting {{ $cutDateLabel }} untuk bundle {{ $b->bundle_code }}">
                                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                                        <span>
                                            <span class="cutting-date-caption">Tanggal Cutting</span>
                                            <span class="cutting-date-value">{{ $cutDateLabel }}</span>
                                            <span class="cutting-bundle-code mono">#{{ $shortBundleCode }}</span>
                                        </span>
                                    </a>
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only">
                                    <span class="fw-bold">
                                        {{ $b->finishedItem?->code ?? '-' }}
                                    </span>
                                    <div class="small text-muted">
                                        {{ $b->finishedItem?->name ?? '' }}
                                    </div>
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only">
                                    {{ $b->cuttingJob?->lot?->item?->code ?? '-' }}
                                    @if ($b->cuttingJob?->lot)
                                        <span class="badge-soft bg-light border text-muted ms-1">
                                            {{ $b->cuttingJob->lot->code }}
                                        </span>
                                    @endif
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only text-end">
                                    {{ $formatQty($b->qty_pcs) }}
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only text-end">
                                    @if($isUnQCed)
                                        <span class="badge bg-danger">Belum QC</span>
                                    @else
                                        <span class="qty-ready-pill">
                                            {{ $formatQty($qtyRemain) }}
                                        </span>
                                    @endif
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only text-end">
                                    @if ($qtyRejectQc > 0)
                                        <span class="reject-qc-pill">{{ $formatQty($qtyRejectQc) }}</span>
                                    @else
                                        <span class="reject-qc-zero">0</span>
                                    @endif
                                </td>

                                @if ($rejectInputEnabled)
                                    <td class="d-none d-md-table-cell td-desktop-only text-end">
                                        @if ($postedRejectQty > 0.000001)
                                            <span class="reject-recorded-qty">Tercatat {{ $formatQty($postedRejectQty) }} pcs</span>
                                        @endif
                                        <button type="button" class="reject-picker-trigger reject-picker-open"
                                            data-row-index="{{ $idx }}" aria-label="Isi Reject Susulan bundle {{ $b->bundle_code }}">
                                            <i class="bi bi-plus-circle" aria-hidden="true"></i>
                                            <span class="reject-picker-label">0</span>
                                        </button>
                                        <input type="hidden" name="lines[{{ $idx }}][qty_reject]"
                                            class="reject-qty-input @error($oldRejectName) is-invalid @enderror"
                                            value="{{ old($oldRejectName, data_get($oldLine, 'qty_reject')) }}">
                                        <input type="hidden" name="lines[{{ $idx }}][reject_reason]"
                                            class="reject-reason-input @error($oldRejectReasonName) is-invalid @enderror"
                                            value="{{ old($oldRejectReasonName, data_get($oldLine, 'reject_reason')) }}">
                                        @error($oldRejectName)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @error($oldRejectReasonName)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                @endif

                                <td class="d-none d-md-table-cell td-desktop-only text-end">
                                    <input type="number" step="0.01" min="0" inputmode="decimal"
                                        name="lines[{{ $idx }}][qty_bundle]"
                                        class="form-control form-control-sm text-end qty-input @error($oldQtyName) is-invalid @enderror"
                                        value="{{ old($oldQtyName, $defaultQtyPickup) }}"
                                        {{ $isUnQCed ? 'disabled' : '' }}>
                                    @error($oldQtyName)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>

                                <td class="d-none d-md-table-cell td-desktop-only text-end">
                                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 btn-pick"
                                        data-row-index="{{ $idx }}" {{ $isUnQCed ? 'disabled' : '' }}>
                                        Max
                                    </button>
                                </td>

                                {{-- MOBILE CARD --}}
                                <td class="td-mobile-extra" colspan="9">
                                    <div class="mobile-row-header">
                                        <div class="mobile-row-header-left">
                                            <div class="mobile-row-header-topline">
                                                <span class="row-index">#{{ $loop->iteration }}</span>
                                                <span class="item-code mono">
                                                    {{ $b->finishedItem?->code ?? '-' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="mobile-row-header-right">
                                            <div class="qty-ready-label">Qty Ready</div>
                                            <div class="qty-ready-value">
                                                @if($isUnQCed)
                                                    <span class="badge bg-danger">Belum QC</span>
                                                @else
                                                    <span class="qty-ready-pill">
                                                        {{ $formatQty($qtyRemain) }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="mobile-row-footer-left mt-1">
                                                @if($isUnQCed)
                                                    <div class="mt-2 text-primary small fw-semibold">
                                                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka Detail Cutting
                                                    </div>
                                                @else
                                                    <div class="pickup-label pickup-max-label">
                                                        Pickup (maks {{ $formatQty($qtyRemain) }})
                                                    </div>
                                                    <input type="number" step="0.01" min="0"
                                                        inputmode="decimal"
                                                        class="form-control form-control-sm qty-input @error($oldQtyName) is-invalid @enderror"
                                                        value="{{ old($oldQtyName, $defaultQtyPickup) }}"
                                                        placeholder="Isi pickup">
                                                    @error($oldQtyName)
                                                        <div class="invalid-feedback">
                                                            {{ $message }}</div>
                                                    @enderror
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- MOBILE META: tgl cutting & qty cutting --}}
                                    <div class="mobile-row-meta">
                                        <div class="mobile-row-meta-left">
                                            <div class="mobile-row-meta-label">Tanggal Cutting</div>
                                            <div class="mobile-row-meta-value">
                                                <a href="{{ route('production.qc.cutting.edit', $b->cutting_job_id) }}"
                                                   class="mobile-cutting-date"
                                                   aria-label="Buka QC Cutting {{ $cutDateLabel }} untuk bundle {{ $b->bundle_code }}">
                                                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                                                    <span>
                                                        <span class="cutting-date-value">{{ $cutDateLabel }}</span>
                                                        <span class="cutting-bundle-code mono">#{{ $shortBundleCode }}</span>
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="mobile-row-meta-left text-end">
                                            <div class="mobile-row-meta-label">Cutting</div>
                                            <div class="mobile-row-meta-value">
                                                {{ $formatQty($b->qty_pcs) }} pcs
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mobile-row-meta mt-1">
                                        <div class="mobile-row-meta-left">
                                            <div class="mobile-row-meta-label">Reject QC</div>
                                            <div class="mobile-row-meta-value">
                                                @if ($qtyRejectQc > 0)
                                                    <span class="reject-qc-pill">{{ $formatQty($qtyRejectQc) }} pcs</span>
                                                @else
                                                    <span class="reject-qc-zero">0 pcs</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mobile-row-meta-left text-end text-muted">
                                            <div class="mobile-row-meta-label">Sumber</div>
                                            <div class="mobile-row-meta-value">QC Cutting</div>
                                        </div>
                                    </div>
                                    @if ($rejectInputEnabled && ! $isUnQCed)
                                        <div class="mobile-row-meta mt-1 align-items-end">
                                            <div class="mobile-row-meta-left">
                                                <div class="mobile-row-meta-label">Reject Susulan</div>
                                                @if ($postedRejectQty > 0.000001)
                                                    <span class="reject-recorded-qty">Tercatat {{ $formatQty($postedRejectQty) }} pcs</span>
                                                @endif
                                                <button type="button" class="reject-picker-trigger reject-picker-open mt-1"
                                                    data-row-index="{{ $idx }}" aria-label="Isi Reject Susulan bundle {{ $b->bundle_code }}">
                                                    <i class="bi bi-plus-circle" aria-hidden="true"></i>
                                                    <span class="reject-picker-label">0</span>
                                                </button>
                                            </div>
                                            <div class="mobile-row-meta-left text-end">
                                                <div class="mobile-row-meta-label">Klik untuk isi qty & alasan</div>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted small py-3">
                                    Belum ada bundle hasil QC Cutting dengan qty ready &gt; 0.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

@if ($rejectInputEnabled)
    <div class="modal fade reject-picker-modal" id="sewingRejectModal" tabindex="-1"
        aria-labelledby="sewingRejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <div>
                        <h5 class="modal-title mb-0" id="sewingRejectModalLabel">Reject Susulan</h5>
                        <div class="small text-muted" id="sewingRejectModalBundle">Bundle</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="reject-picker-modal-note mb-3">
                        Reject langsung masuk ke <strong>REJ-CUT</strong> dan tidak membutuhkan operator jahit.
                        Setelah disimpan di sini, transaksi langsung dicatat.
                    </div>
                    <div class="mb-3">
                        <label for="sewingRejectModalQty" class="form-label">Qty Reject</label>
                        <input type="number" id="sewingRejectModalQty" class="form-control" min="0" step="0.01"
                            inputmode="decimal" placeholder="0">
                        <div class="form-text" id="sewingRejectModalMax"></div>
                        <div class="invalid-feedback" id="sewingRejectModalQtyError"></div>
                    </div>
                    <div>
                        <label for="sewingRejectModalReason" class="form-label">Alasan Reject</label>
                        <textarea id="sewingRejectModalReason" class="form-control" rows="3" maxlength="255"
                            placeholder="Contoh: noda, sobek, ukuran tidak sesuai"></textarea>
                        <div class="invalid-feedback" id="sewingRejectModalReasonError"></div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="sewingRejectModalClear">
                        Hapus Reject
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-sm btn-danger" id="sewingRejectModalSave">Simpan &amp; Catat</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- FLOATING ACTION (MOBILE): SUBMIT SHORTCUT --}}
<div class="bundle-actions-fab d-md-none" aria-label="Aksi sewing pickup">
    <button type="button" id="bundle-submit-shortcut" class="bundle-submit-fab">
        <span class="bi bi-person-check"></span>
        <span class="text-white">Pilih Penjahit</span>
    </button>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rowsArr = Array.from(document.querySelectorAll('.bundle-row'));

            const searchInput = document.getElementById('bundle-filter-input');
            const toggleOnlyPicked = document.getElementById('toggle-only-picked');
            const itemCodeSelect = document.getElementById('item-code-select');
            const itemCodeSelectWrap = document.getElementById('item-code-select-wrap');
            const selectedSummaryCount = document.getElementById('selected-summary-count');
            const selectedSummaryList = document.getElementById('selected-summary-list');
            const selectedSummaryBody = document.getElementById('selected-summary-body');
            const selectedSummaryEmpty = document.getElementById('selected-summary-empty');
            const selectedSummaryClear = document.getElementById('selected-summary-clear');
            const selectedSummaryTotalItems = document.getElementById('selected-summary-total-items');
            const selectedSummaryTotalQty = document.getElementById('selected-summary-total-qty');
            const suppliesPayload = document.getElementById('supplies_checklist_payload');
            const sewingSupplySummary = document.getElementById('sewing-supply-summary');
            const sewingSupplyBody = document.getElementById('sewing-supply-body');
            const sewingSupplyStatus = document.getElementById('sewing-supply-status');
            const rejectModalEl = document.getElementById('sewingRejectModal');
            const rejectModalQty = document.getElementById('sewingRejectModalQty');
            const rejectModalReason = document.getElementById('sewingRejectModalReason');
            const rejectModalBundle = document.getElementById('sewingRejectModalBundle');
            const rejectModalMax = document.getElementById('sewingRejectModalMax');
            const rejectModalQtyError = document.getElementById('sewingRejectModalQtyError');
            const rejectModalReasonError = document.getElementById('sewingRejectModalReasonError');
            const rejectModalSave = document.getElementById('sewingRejectModalSave');
            const rejectModalClear = document.getElementById('sewingRejectModalClear');
            const rejectModalInstance = rejectModalEl && window.bootstrap
                ? new bootstrap.Modal(rejectModalEl)
                : null;
            let activeRejectRow = null;

            const submitBtn = document.getElementById('btn-submit-main');
            const submitLabel = document.getElementById('btn-submit-label');
            const submitShortcutBtn = document.getElementById('bundle-submit-shortcut');
            const bomSuppliesByItem = @json($bomSuppliesByItem ?? []);
            const sewingRatePreview = @json($sewingRatePreview ?? []);
            const autoBomNegativeEnabled = @json((bool) ($autoBomNegativeEnabled ?? false));
            let supplyShortageCount = 0;

            const currentOperatorId = () => document.getElementById('operator_id_auto')?.value
                || document.getElementById('operator_id_hidden')?.value
                || '';

            let state = {
                activeItemCode: '',
                showOnlyPickedDesktop: false,
            };

            let searchTimer = null;

            const isMobile = () => window.innerWidth < 768;

            let nf;
            try {
                nf = new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            } catch (e) {
                nf = {
                    format: num => (num || 0).toFixed(2)
                };
            }

            let qtyNf;
            try {
                qtyNf = new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 4
                });
            } catch (e) {
                qtyNf = {
                    format: num => String(Number(num || 0))
                };
            }

            const normalizeText = v => (v || '').toString().trim().toUpperCase();
            const draftStorageKey = 'gfid:sewing-pickup:create:selected-bundles';
            let restoringDraft = false;

            function getRowBundleId(row) {
                return row?.querySelector('input[name$="[bundle_id]"]')?.value || '';
            }

            const hppRawMaterialTotal = document.getElementById('sewing-hpp-raw-material-total');
            const hppSupportTotal = document.getElementById('sewing-hpp-support-total');
            const hppCuttingWageTotal = document.getElementById('sewing-hpp-cutting-wage-total');
            const hppWipCutTotal = document.getElementById('sewing-hpp-wip-cut-total');
            const hppSewingWageTotal = document.getElementById('sewing-hpp-sewing-wage-total');
            const hppTotal = document.getElementById('sewing-hpp-total');
            const hppBadge = document.getElementById('sewing-pickup-hpp-badge');
            const hppBody = document.getElementById('sewing-pickup-hpp-body');
            const hppWarning = document.getElementById('sewing-hpp-warning');
            const hppOperatorNote = document.getElementById('sewing-hpp-operator-note');
            const accountingBadge = document.getElementById('sewing-pickup-accounting-badge');
            const accountingGoodTotal = document.getElementById('sewing-accounting-good-total');
            const accountingRejectTotal = document.getElementById('sewing-accounting-reject-total');
            const accountingTotal = document.getElementById('sewing-accounting-total');
            const accountingNote = document.getElementById('sewing-accounting-note');
            const accountingBody = document.getElementById('sewing-pickup-accounting-body');
            const mutationBadge = document.getElementById('sewing-pickup-mutation-badge');
            const mutationBody = document.getElementById('sewing-pickup-mutation-body');
            const mutationNote = document.getElementById('sewing-mutation-note');

            function updateRejectTriggers(row) {
                if (!row) return;

                const qty = parseFloat(row.querySelector('input.reject-qty-input')?.value || '0') || 0;
                const reason = (row.querySelector('input.reject-reason-input')?.value || '').trim();
                const invalid = qty > 0.000001 && !reason;

                row.querySelectorAll('.reject-picker-open').forEach(trigger => {
                    const label = trigger.querySelector('.reject-picker-label');
                    if (label) label.textContent = qty > 0 ? `${qtyNf.format(qty)} pcs` : 'Tambah';
                    trigger.classList.toggle('has-value', qty > 0.000001);
                    trigger.classList.toggle('is-invalid', invalid);
                    trigger.setAttribute('aria-label', qty > 0
                        ? `Edit Reject Susulan ${qtyNf.format(qty)} pcs`
                        : 'Isi Reject Susulan');
                });
            }

            function rowBaseReadyQty(row) {
                return Math.max(parseFloat(row?.dataset.qtyReady || '0') || 0, 0);
            }

            function rowGoodQty(row) {
                return Math.max(parseFloat(row?.querySelector('input.qty-input')?.value || '0') || 0, 0);
            }

            function rowRejectQty(row) {
                return Math.max(parseFloat(row?.querySelector('input.reject-qty-input')?.value || '0') || 0, 0);
            }

            function rowRemainingReadyQty(row) {
                return Math.max(rowBaseReadyQty(row) - rowGoodQty(row) - rowRejectQty(row), 0);
            }

            function updateReadyDisplay(row) {
                if (!row) return;

                const remaining = rowRemainingReadyQty(row);
                row.querySelectorAll('.qty-ready-pill').forEach(pill => {
                    pill.textContent = qtyNf.format(remaining);
                });
                row.querySelectorAll('.pickup-max-label').forEach(label => {
                    label.textContent = `Pickup (maks ${qtyNf.format(Math.max(rowBaseReadyQty(row) - rowRejectQty(row), 0))})`;
                });
            }

            function clearRejectModalErrors() {
                rejectModalQty?.classList.remove('is-invalid');
                rejectModalReason?.classList.remove('is-invalid');
                if (rejectModalQtyError) rejectModalQtyError.textContent = '';
                if (rejectModalReasonError) rejectModalReasonError.textContent = '';
            }

            function openRejectModal(row) {
                if (!row || !rejectModalInstance || !rejectModalQty || !rejectModalReason) return;

                activeRejectRow = row;
                const qty = parseFloat(row.querySelector('input.reject-qty-input')?.value || '0') || 0;
                const reason = row.querySelector('input.reject-reason-input')?.value || '';
                // Reject sebelum jahit berdiri sendiri dari input Qty Baik. Batasnya
                // adalah sisa WIP-CUT yang ditampilkan sebagai Qty Ready.
                const max = Math.max(rowBaseReadyQty(row) - rowGoodQty(row), 0);

                rejectModalQty.value = qty > 0 ? qty : '';
                rejectModalReason.value = reason;
                if (rejectModalBundle) {
                    rejectModalBundle.textContent = `${row.dataset.itemCode || 'Item'} · ${row.dataset.bundleCode || 'Bundle'}`;
                }
                if (rejectModalMax) rejectModalMax.textContent = `Maksimal ${qtyNf.format(max)} pcs pada baris ini.`;
                clearRejectModalErrors();
                rejectModalInstance.show();
                setTimeout(() => rejectModalQty.focus(), 120);
            }

            function saveRejectModalValue() {
                if (!activeRejectRow || !rejectModalQty || !rejectModalReason) return;

                clearRejectModalErrors();
                const qty = Math.max(parseFloat(rejectModalQty.value || '0') || 0, 0);
                const reason = rejectModalReason.value.trim();
                const max = Math.max(rowBaseReadyQty(activeRejectRow) - rowGoodQty(activeRejectRow), 0);
                let valid = true;

                if (qty > max + 0.000001) {
                    valid = false;
                    rejectModalQty.classList.add('is-invalid');
                    if (rejectModalQtyError) rejectModalQtyError.textContent = `Qty reject maksimal ${qtyNf.format(max)} pcs.`;
                }
                if (qty > 0.000001 && !reason) {
                    valid = false;
                    rejectModalReason.classList.add('is-invalid');
                    if (rejectModalReasonError) rejectModalReasonError.textContent = 'Alasan reject wajib diisi.';
                }
                if (!valid) return;

                const qtyInput = activeRejectRow.querySelector('input.reject-qty-input');
                const reasonInput = activeRejectRow.querySelector('input.reject-reason-input');
                if (qtyInput) qtyInput.value = qty > 0 ? qty : '';
                if (reasonInput) reasonInput.value = qty > 0 ? reason : '';
                updateRejectTriggers(activeRejectRow);
                recalcSummaryAndUI();
                rejectModalInstance?.hide();

                // Reject-only adalah transaksi mandiri: setelah qty dan alasan
                // valid, langsung submit ke endpoint store. Dengan begitu user
                // tidak perlu menyimpan reject untuk kedua kalinya lewat tombol
                // utama di bawah halaman. Jika ada pickup baik di baris lain,
                // tetap gunakan tombol utama agar operator dan kelengkapan jahit
                // dapat dikonfirmasi bersama transaksi baik tersebut.
                const form = document.getElementById('sewing-pickup-form');
                const hasGoodQty = rowsArr.some(row => rowGoodQty(row) > 0.000001);
                const hasRejectQty = rowsArr.some(row => rowRejectQty(row) > 0.000001);
                if (qty > 0.000001 && form && hasRejectQty && !hasGoodQty) {
                    const dateInput = form.querySelector('input[name="date"]');
                    if (dateInput && !dateInput.value) {
                        dateInput.reportValidity?.();
                        return;
                    }

                    const operatorHidden = form.querySelector('#operator_id_hidden');
                    if (operatorHidden) operatorHidden.value = '';
                    form.dataset.directReject = '1';
                    try {
                        localStorage.removeItem(draftStorageKey);
                    } catch (e) {
                        // Draft hanya cache UI; kegagalan menghapusnya tidak
                        // boleh menggagalkan posting reject.
                    }
                    if (rejectModalSave) {
                        rejectModalSave.disabled = true;
                        rejectModalSave.textContent = 'Mencatat...';
                    }
                    HTMLFormElement.prototype.submit.call(form);
                }
            }

            const escapeHpp = value => String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[char]));

            const hppMoneyNf = new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            });

            function money(value) {
                return `Rp ${hppMoneyNf.format(Number(value || 0))}`;
            }

            function renderHppPreview(items) {
                if (!hppBody) return;

                // Reject-only tidak masuk WIP-SEW. Jangan tampilkan sebagai
                // bundle HPP dengan qty 0 karena itu terlihat seperti cost
                // gagal dihitung, padahal mutasinya menuju REJ-CUT.
                const hppItems = items.filter(item => (item.qtyValue || 0) > 0.000001);
                const operatorId = currentOperatorId();
                let rawMaterialTotal = 0;
                let supportTotal = 0;
                let cuttingWageTotal = 0;
                let wipCutTotal = 0;
                let sewingWageTotal = 0;
                let missingCost = false;
                let missingWage = false;
                hppBody.innerHTML = '';

                hppItems.forEach(item => {
                    const row = rowsArr.find(candidate => candidate.dataset.rowIndex === item.rowIndex);
                    const wipUnitCost = Number(row?.dataset.wipUnitCost || 0) || 0;
                    const rawMaterialUnitCost = Number(row?.dataset.rawMaterialUnitCost || 0) || 0;
                    const supportUnitCost = Number(row?.dataset.supportUnitCost || 0) || 0;
                    const cuttingWageUnitCost = Number(row?.dataset.cuttingWageUnitCost || 0) || 0;
                    const wageUnitCost = Number(sewingRatePreview?.[operatorId]?.[item.bundleId] || 0) || 0;
                    const rawMaterialCost = item.qtyValue * rawMaterialUnitCost;
                    const supportCost = item.qtyValue * supportUnitCost;
                    const cuttingWageCost = item.qtyValue * cuttingWageUnitCost;
                    const sewingWageCost = item.qtyValue * wageUnitCost;
                    const wipCutTotalCost = item.qtyValue * wipUnitCost;
                    const unitTotal = wipUnitCost + wageUnitCost;
                    rawMaterialTotal += rawMaterialCost;
                    supportTotal += supportCost;
                    cuttingWageTotal += cuttingWageCost;
                    wipCutTotal += wipCutTotalCost;
                    sewingWageTotal += sewingWageCost;
                    missingCost = missingCost || wipUnitCost <= 0;
                    missingWage = missingWage || wageUnitCost <= 0;

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><div class="fw-semibold">${escapeHpp(item.itemCode || '-')}</div><div class="sewing-pickup-hpp-note">${escapeHpp(item.bundleCode || '-')}</div></td>
                        <td class="text-end mono">${item.qtyLabel}</td>
                        <td class="text-end mono">${money(rawMaterialUnitCost)}</td>
                        <td class="text-end mono">${money(supportUnitCost)}</td>
                        <td class="text-end mono">${money(cuttingWageUnitCost)}</td>
                        <td class="text-end mono fw-semibold">${money(wipUnitCost)}</td>
                        <td class="text-end mono">${money(wageUnitCost)}</td>
                        <td class="text-end mono fw-semibold">${money(unitTotal)}</td>
                        <td class="text-end mono fw-semibold">${money(item.qtyValue * unitTotal)}</td>`;
                    hppBody.appendChild(tr);
                });

                if (hppItems.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.innerHTML = `<td colspan="9" class="text-center text-muted py-3">
                        ${items.length > 0
                            ? 'Tidak ada Qty Pickup Baik. Reject susulan masuk REJ-CUT dan tidak menambah HPP WIP-SEW.'
                            : 'Pilih qty pickup baik untuk melihat estimasi HPP WIP-SEW.'}
                    </td>`;
                    hppBody.appendChild(emptyRow);
                }

                const total = rawMaterialTotal + supportTotal + cuttingWageTotal + sewingWageTotal;
                if (hppRawMaterialTotal) hppRawMaterialTotal.textContent = money(rawMaterialTotal);
                if (hppSupportTotal) hppSupportTotal.textContent = money(supportTotal);
                if (hppCuttingWageTotal) hppCuttingWageTotal.textContent = money(cuttingWageTotal);
                if (hppWipCutTotal) hppWipCutTotal.textContent = money(wipCutTotal);
                if (hppSewingWageTotal) hppSewingWageTotal.textContent = money(sewingWageTotal);
                if (hppTotal) hppTotal.textContent = money(total);
                if (hppBadge) hppBadge.textContent = hppItems.length
                    ? `${hppItems.length} bundle`
                    : (items.length ? 'Reject saja' : 'Belum pilih');
                if (hppOperatorNote) {
                    hppOperatorNote.textContent = hppItems.length === 0 && items.length > 0
                        ? 'Reject susulan tidak menerima upah jahit dan tidak masuk WIP-SEW.'
                        : operatorId
                        ? 'Nilai upah dihitung dari Piece Rate operator jahit yang dipilih.'
                        : 'Pilih operator jahit pada konfirmasi untuk menghitung upah.';
                }
                if (hppWarning) {
                    const warnings = [];
                    if (missingCost) warnings.push('Nilai WIP-CUT belum tersedia pada sebagian bundle; posting akan dihentikan.');
                    if (missingWage) warnings.push('Piece Rate Sewing operator belum tersedia pada sebagian bundle.');
                    hppWarning.textContent = warnings.join(' ');
                    hppWarning.classList.toggle('d-none', warnings.length === 0 || hppItems.length === 0);
                }
            }

            function renderAccountingAndMutationPreview(items) {
                if (!accountingBody && !mutationBody) return;

                const operatorId = currentOperatorId();
                let goodQty = 0;
                let rejectQty = 0;
                let goodMaterial = 0;
                let goodWage = 0;
                let rejectValue = 0;
                let missingCost = false;
                let missingWage = false;

                items.forEach(item => {
                    const row = rowsArr.find(candidate => candidate.dataset.rowIndex === item.rowIndex);
                    const wipUnitCost = Number(row?.dataset.wipUnitCost || 0) || 0;
                    const wageUnitCost = Number(sewingRatePreview?.[operatorId]?.[item.bundleId] || 0) || 0;
                    const itemGoodQty = Number(item.qtyValue || 0);
                    const itemRejectQty = Number(item.rejectValue || 0);

                    goodQty += itemGoodQty;
                    rejectQty += itemRejectQty;
                    goodMaterial += itemGoodQty * wipUnitCost;
                    goodWage += itemGoodQty * wageUnitCost;
                    rejectValue += itemRejectQty * wipUnitCost;
                    missingCost = missingCost || ((itemGoodQty + itemRejectQty) > 0 && wipUnitCost <= 0);
                    missingWage = missingWage || (itemGoodQty > 0 && wageUnitCost <= 0);
                });

                const goodTotal = goodMaterial + goodWage;
                const total = goodTotal + rejectValue;
                const hasGood = goodQty > 0.000001;
                const hasReject = rejectQty > 0.000001;
                const scenario = hasGood && hasReject
                    ? 'C — Pickup Baik + Reject Susulan'
                    : hasGood
                        ? 'A — Pickup Baik'
                        : hasReject
                            ? 'B — Reject Susulan'
                            : 'Belum pilih';

                if (accountingGoodTotal) accountingGoodTotal.textContent = money(goodTotal);
                if (accountingRejectTotal) accountingRejectTotal.textContent = money(rejectValue);
                if (accountingTotal) accountingTotal.textContent = money(total);
                if (accountingBadge) accountingBadge.textContent = scenario;
                if (mutationBadge) mutationBadge.textContent = scenario;

                if (accountingBody) {
                    accountingBody.innerHTML = '';
                    const accountingRows = [];
                    if (hasGood) {
                        accountingRows.push({
                            scenario: 'Pickup baik · pindah nilai WIP',
                            debit: '1-151-02 WIP Sewing',
                            credit: '1-151-01 WIP Cutting',
                            amount: goodMaterial,
                        });
                        accountingRows.push({
                            scenario: 'Pickup baik · upah',
                            debit: '1-151-02 WIP Sewing',
                            credit: '2-130 Accrued Production Costs',
                            amount: goodWage,
                        });
                    }
                    if (hasReject) {
                        accountingRows.push({
                            scenario: 'Reject susulan',
                            debit: '1-153 Persediaan Reject',
                            credit: '1-151-01 WIP Cutting',
                            amount: rejectValue,
                        });
                    }

                    if (accountingRows.length === 0) {
                        const emptyRow = document.createElement('tr');
                        emptyRow.innerHTML = '<td colspan="4" class="text-center text-muted py-3">Pilih pickup baik atau reject susulan untuk melihat jurnal baru.</td>';
                        accountingBody.appendChild(emptyRow);
                    } else {
                        accountingRows.forEach(entry => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>${escapeHpp(entry.scenario)}</td>
                                <td><span class="sewing-pickup-hpp-unit">${escapeHpp(entry.debit)}</span></td>
                                <td><span class="sewing-pickup-hpp-unit">${escapeHpp(entry.credit)}</span></td>
                                <td class="text-end mono fw-semibold">${money(entry.amount)}</td>`;
                            accountingBody.appendChild(tr);
                        });
                    }
                }

                if (accountingNote) {
                    const notes = [`Skenario ${scenario}.`, 'Jurnal baru harus balance, transactional, dan idempotent. BOM kelengkapan tidak dipotong ulang di Sewing Pickup.'];
                    if (hasReject && !hasGood) notes.push('Reject susulan: WIP-CUT dipindahkan ke REJ-CUT tanpa upah dan tanpa WIP-SEW.');
                    if (missingCost) notes.push('Nilai WIP-CUT belum tersedia; posting akan dihentikan.');
                    if (missingWage) notes.push('Piece Rate operator belum tersedia; posting pickup baik akan dihentikan.');
                    accountingNote.textContent = notes.join(' ');
                }

                if (mutationBody) {
                    mutationBody.innerHTML = '';
                    const mutationRows = [];
                    if (hasGood) {
                        mutationRows.push({
                            scenario: 'Pickup baik',
                            from: 'WIP-CUT',
                            to: 'WIP-SEW',
                            qty: goodQty,
                            outValue: goodMaterial,
                            inValue: goodTotal,
                        });
                    }
                    if (hasReject) {
                        mutationRows.push({
                            scenario: 'Reject susulan',
                            from: 'WIP-CUT',
                            to: 'REJ-CUT',
                            qty: rejectQty,
                            outValue: rejectValue,
                            inValue: rejectValue,
                        });
                    }

                    if (mutationRows.length === 0) {
                        const emptyRow = document.createElement('tr');
                        emptyRow.innerHTML = '<td colspan="6" class="text-center text-muted py-3">Pilih qty untuk melihat rencana mutasi stok.</td>';
                        mutationBody.appendChild(emptyRow);
                    } else {
                        mutationRows.forEach(entry => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>${escapeHpp(entry.scenario)}</td>
                                <td class="mono">${escapeHpp(entry.from)}</td>
                                <td class="mono">${escapeHpp(entry.to)}</td>
                                <td class="text-end mono">${qtyNf.format(entry.qty)} pcs</td>
                                <td class="text-end mono">${money(entry.outValue)}</td>
                                <td class="text-end mono fw-semibold">${money(entry.inValue)}</td>`;
                            mutationBody.appendChild(tr);
                        });
                    }
                }

                if (mutationNote) {
                    mutationNote.textContent = hasGood && hasReject
                        ? 'Skenario C: pickup baik masuk WIP-SEW, reject susulan masuk REJ-CUT.'
                        : hasGood
                            ? 'Skenario A: WIP-CUT berkurang dan WIP-SEW bertambah sebesar material + upah.'
                            : hasReject
                                ? 'Skenario B: WIP-CUT berkurang dan REJ-CUT bertambah; tidak ada perpindahan ke WIP-SEW.'
                                : 'Belum ada mutasi yang dipilih.';
                }
            }

            function readPickupDraft() {
                try {
                    const parsed = JSON.parse(localStorage.getItem(draftStorageKey) || '{}');
                    return parsed && typeof parsed === 'object' ? parsed : {};
                } catch (e) {
                    return {};
                }
            }

            function savePickupDraft() {
                if (restoringDraft) return;

                const draft = {};
                rowsArr.forEach(row => {
                    const bundleId = getRowBundleId(row);
                    const input = row.querySelector('input.qty-input');
                    const qty = parseFloat(input?.value || '0') || 0;
                    const rejectInput = row.querySelector('input.reject-qty-input');
                    const rejectQty = parseFloat(rejectInput?.value || '0') || 0;
                    const rejectReason = row.querySelector('input.reject-reason-input')?.value || '';

                    if (bundleId && (qty > 0 || rejectQty > 0)) {
                        draft[bundleId] = {
                            qty,
                            reject: rejectQty,
                            reason: rejectReason,
                        };
                    }
                });

                try {
                    if (Object.keys(draft).length > 0) {
                        localStorage.setItem(draftStorageKey, JSON.stringify(draft));
                    } else {
                        localStorage.removeItem(draftStorageKey);
                    }
                } catch (e) {
                    // Kalau storage browser penuh/nonaktif, form tetap bisa dipakai normal.
                }
            }

            function restorePickupDraft() {
                const draft = readPickupDraft();
                if (!Object.keys(draft).length) return;

                restoringDraft = true;
                rowsArr.forEach(row => {
                    const bundleId = getRowBundleId(row);
                    if (!bundleId || !Object.prototype.hasOwnProperty.call(draft, bundleId)) return;

                    const qtyInputs = row.querySelectorAll('input.qty-input');
                    if (!qtyInputs.length) return;

                    const existingQty = parseFloat(qtyInputs[0].value || '0') || 0;
                    if (existingQty > 0) return;

                    const saved = typeof draft[bundleId] === 'object'
                        ? draft[bundleId]
                        : { qty: draft[bundleId], reject: 0, reason: '' };
                    const qty = parseFloat(saved.qty || '0') || 0;
                    const reject = parseFloat(saved.reject || '0') || 0;
                    if (qty <= 0 && reject <= 0) return;

                    qtyInputs.forEach(input => input.value = qty);
                    row.querySelectorAll('input.reject-qty-input, input.reject-qty-input-mobile')
                        .forEach(input => input.value = reject);
                    row.querySelectorAll('input.reject-reason-input, input.reject-reason-input-mobile')
                        .forEach(input => input.value = saved.reason || '');
                    clampRowQuantities(row);
                    updateRejectTriggers(row);
                });
                restoringDraft = false;
            }

            function focusSearchInput() {
                if (!searchInput) return;
                setTimeout(() => {
                    try {
                        searchInput.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                            inline: 'nearest'
                        });
                    } catch (e) {
                        // fallback fokus tetap jalan walau browser tidak support scrollIntoView options
                    }

                    setTimeout(() => {
                        try {
                            searchInput.focus({
                                preventScroll: true
                            });
                            searchInput.select();
                        } catch (e) {
                            searchInput.focus();
                        }
                    }, 180);
                }, 80);
            }

            function rowHasPickup(row) {
                const input = row.querySelector('input.qty-input');
                const rejectInput = row.querySelector('input.reject-qty-input');
                const current = parseFloat(input?.value || '0') || 0;
                const reject = parseFloat(rejectInput?.value || '0') || 0;
                return current + reject > 0;
            }

            function clampToReady(input, row) {
                if (!row) return;
                const max = Math.max(rowBaseReadyQty(row) - rowRejectQty(row), 0);
                let val = parseFloat(input.value || '0') || 0;

                if (val > max) {
                    val = max;
                    input.value = max > 0 ? max : '';
                    input.classList.add('border-warning');
                    setTimeout(() => input.classList.remove('border-warning'), 400);
                }

                if (val < 0) {
                    input.value = '';
                }
            }

            function clampRowQuantities(row) {
                if (!row) return;

                const max = rowBaseReadyQty(row);
                const pickupInputs = row.querySelectorAll('input.qty-input');
                const rejectInputs = row.querySelectorAll('input.reject-qty-input, input.reject-qty-input-mobile');
                const pickup = Math.max(parseFloat(pickupInputs[0]?.value || '0') || 0, 0);
                let reject = Math.max(parseFloat(rejectInputs[0]?.value || '0') || 0, 0);
                const allowedReject = Math.max(max - pickup, 0);

                if (pickup + reject > max) {
                    reject = allowedReject;
                    rejectInputs.forEach(input => {
                        input.value = reject > 0 ? reject : '';
                        input.classList.add('border-warning');
                        setTimeout(() => input.classList.remove('border-warning'), 400);
                    });
                }

                pickupInputs.forEach(input => {
                    if ((parseFloat(input.value || '0') || 0) > max) {
                        input.value = max > 0 ? max : '';
                    }
                });
                rejectInputs.forEach(input => {
                    if ((parseFloat(input.value || '0') || 0) < 0) input.value = '';
                });
            }

            function applyRowVisibility() {
                const term = normalizeText(searchInput?.value || '');
                const itemCodeFilter = normalizeText(state.activeItemCode);

                rowsArr.forEach(row => {
                    const bundle = normalizeText(row.dataset.bundleCode);
                    const itemCode = normalizeText(row.dataset.itemCode);
                    const itemName = normalizeText(row.dataset.itemName);
                    const lotCode = normalizeText(row.dataset.lotCode);

                    const haystack = [bundle, itemCode, itemName, lotCode].join(' ');
                    const matchSearch = !term || haystack.includes(term);
                    const matchItemCode = !itemCodeFilter || itemCode === itemCodeFilter;
                    const inCart = rowHasPickup(row);

                    let visible = matchSearch && matchItemCode;

                    if (!isMobile()) {
                        const pickedOk = !state.showOnlyPickedDesktop || inCart;
                        visible = visible && pickedOk;
                    }

                    row.style.display = visible ? '' : 'none';
                });
            }

            function updateSubmitButtons(pickedBundles, totalPickupQty, totalGoodQty, totalRejectQty) {
                const hasShortage = supplyShortageCount > 0;
                const rejectOnly = totalRejectQty > 0 && totalGoodQty <= 0;
                // Boleh submit walau shortage — stok boleh minus (allow_negative=true)
                // Shortage tetap ditampilkan sebagai warning, bukan blocker
                const canSubmit = pickedBundles > 0 && totalPickupQty > 0;

                if (submitBtn && submitLabel) {
                    submitBtn.disabled = !canSubmit;
                    submitBtn.classList.toggle('btn-warning', hasShortage && canSubmit);
                    submitBtn.classList.toggle('btn-primary', !hasShortage && canSubmit);
                    submitLabel.textContent = !canSubmit ? 'Belum Ambil'
                        : rejectOnly ? 'Simpan Reject'
                        : autoBomNegativeEnabled ? 'Simpan & Potong BOM'
                        : hasShortage ? 'Pilih Penjahit ⚠'
                        : 'Pilih Penjahit';
                }

                if (submitShortcutBtn) {
                    submitShortcutBtn.disabled = !canSubmit;
                    submitShortcutBtn.classList.toggle('btn-warning', hasShortage && canSubmit);
                    submitShortcutBtn.classList.toggle('btn-primary', !hasShortage && canSubmit);
                    const shortcutText = submitShortcutBtn.querySelector('span:last-child');
                    if (shortcutText) {
                        shortcutText.textContent = rejectOnly ? 'Simpan Reject'
                            : autoBomNegativeEnabled ? 'Simpan & Potong BOM'
                            : hasShortage ? 'Pilih Penjahit ⚠' : 'Pilih Penjahit';
                    }
                }

                const printBtn = document.getElementById('btn-submit-print');
                if (printBtn) printBtn.disabled = !canSubmit;
            }

            function renderSelectedSummary(items) {
                if (selectedSummaryCount) selectedSummaryCount.textContent = items.length.toString();
                if (!selectedSummaryList || !selectedSummaryBody || !selectedSummaryEmpty) return;

                selectedSummaryBody.innerHTML = '';
                selectedSummaryList.classList.toggle('d-none', items.length === 0);
                selectedSummaryEmpty.classList.toggle('d-none', items.length > 0);
                selectedSummaryClear?.classList.toggle('d-none', items.length === 0);

                const totalQty = items.reduce((sum, item) => sum + (item.qtyValue || 0) + (item.rejectValue || 0), 0);
                if (selectedSummaryTotalItems) selectedSummaryTotalItems.textContent = items.length.toString();
                if (selectedSummaryTotalQty) selectedSummaryTotalQty.textContent = qtyNf.format(totalQty);

                items.forEach((item, index) => {
                    const tr = document.createElement('tr');
                    tr.dataset.rowIndex = item.rowIndex;

                    const numberTd = document.createElement('td');
                    numberTd.className = 'text-muted';
                    numberTd.textContent = index + 1;

                    const itemTd = document.createElement('td');
                    itemTd.className = 'item-code-cell mono';
                    itemTd.textContent = item.itemCode || 'Item';

                    const qtyTd = document.createElement('td');
                    qtyTd.className = 'text-end mono fw-bold';
                    qtyTd.textContent = item.rejectValue > 0
                        ? `${item.qtyLabel} OK · ${item.rejectLabel} RJ`
                        : item.qtyLabel;

                    const actionTd = document.createElement('td');
                    actionTd.className = 'text-end';

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'selected-summary-remove';
                    removeBtn.dataset.rowIndex = item.rowIndex;
                    removeBtn.textContent = '×';
                    removeBtn.title = 'Hapus item dari pilihan';

                    actionTd.appendChild(removeBtn);
                    tr.append(numberTd, itemTd, qtyTd, actionTd);
                    selectedSummaryBody.appendChild(tr);
                });

            }

            function buildSewingSupplyItems(selectedItems) {
                const byMaterial = {};

                selectedItems.forEach(item => {
                    const supplies = bomSuppliesByItem[item.finishedItemId] || [];
                    supplies.forEach(supply => {
                        const materialId = parseInt(supply.id || 0, 10);
                        if (!materialId) return;

                        const need = (parseFloat(supply.qty || '0') || 0) * (item.qtyValue || 0);
                        if (need <= 0) return;

                        if (!byMaterial[materialId]) {
                            byMaterial[materialId] = {
                                id: materialId,
                                code: supply.code || '',
                                name: supply.name || '',
                                uom: supply.uom || '',
                                qty: 0,
                                issued_qty: 0,
                                totalPieces: 0,
                                issued_pcs: 0,
                                stock_available: parseFloat(supply.stock_available || '0') || 0,
                            };
                        }

                        byMaterial[materialId].qty += need;
                        byMaterial[materialId].issued_qty += need;
                        byMaterial[materialId].totalPieces += item.qtyValue || 0;
                        byMaterial[materialId].issued_pcs += item.qtyValue || 0;
                    });
                });

                return Object.values(byMaterial).map(item => ({
                    ...item,
                    shortage_qty: Math.max((item.qty || 0) - (item.stock_available || 0), 0),
                }));
            }

            function renderSewingSupplySummary(items) {
                supplyShortageCount = items.filter(item => item.shortage_qty > 0.000001).length;

                if (suppliesPayload) {
                    suppliesPayload.value = JSON.stringify({
                        items
                    });
                }

                if (!sewingSupplySummary || !sewingSupplyBody || !sewingSupplyStatus) return;

                sewingSupplySummary.classList.toggle('d-none', items.length === 0);
                sewingSupplyBody.innerHTML = '';

                sewingSupplyStatus.classList.toggle('is-danger', supplyShortageCount > 0);
                sewingSupplyStatus.textContent = supplyShortageCount > 0 ?
                    `${supplyShortageCount} kurang` :
                    'Cukup';

                items.forEach(item => {
                    const tr = document.createElement('tr');

                    const materialTd = document.createElement('td');
                    const codeEl = document.createElement('strong');
                    codeEl.className = 'mono';
                    codeEl.textContent = item.code || 'Material';
                    materialTd.appendChild(codeEl);
                    if (item.name) {
                        const nameEl = document.createElement('div');
                        nameEl.className = 'text-muted';
                        nameEl.textContent = item.name;
                        materialTd.appendChild(nameEl);
                    }

                    const pcsTd = document.createElement('td');
                    pcsTd.className = 'text-end mono fw-bold';
                    pcsTd.textContent = `${qtyNf.format(item.totalPieces || 0)} pcs`;

                    tr.append(materialTd, pcsTd);
                    sewingSupplyBody.appendChild(tr);
                });
            }

            function recalcSummaryAndUI() {
                let pickedBundles = 0;
                let totalPickupQty = 0;
                let totalGoodQty = 0;
                let totalRejectQty = 0;
                const selectedItems = [];

                rowsArr.forEach(row => {
                    const input = row.querySelector('input.qty-input');
                    if (!input) return;
                    const current = parseFloat(input.value || '0') || 0;
                    const reject = parseFloat(row.querySelector('input.reject-qty-input')?.value || '0') || 0;
                    updateRejectTriggers(row);
                    updateReadyDisplay(row);
                    if (current + reject > 0) {
                        pickedBundles++;
                        totalPickupQty += current + reject;
                        totalGoodQty += current;
                        totalRejectQty += reject;
                        selectedItems.push({
                            rowIndex: row.dataset.rowIndex || '',
                            bundleCode: row.dataset.bundleCode || '',
                            itemCode: row.dataset.itemCode || '',
                            bundleId: getRowBundleId(row),
                            finishedItemId: row.dataset.finishedItemId || '',
                            qtyValue: current,
                            rejectValue: reject,
                            qtyLabel: qtyNf.format(current),
                            rejectLabel: qtyNf.format(reject),
                        });
                    }
                });

                renderSelectedSummary(selectedItems);
                renderHppPreview(selectedItems);
                renderAccountingAndMutationPreview(selectedItems);
                renderSewingSupplySummary(buildSewingSupplyItems(selectedItems));

                updateSubmitButtons(pickedBundles, totalPickupQty, totalGoodQty, totalRejectQty);
                savePickupDraft();
                applyRowVisibility();
            }

            document.addEventListener('click', function(e) {
                const trigger = e.target.closest('.reject-picker-open');
                if (!trigger) return;

                const row = rowsArr.find(candidate => candidate.dataset.rowIndex === trigger.dataset.rowIndex);
                if (row) openRejectModal(row);
            });

            rejectModalSave?.addEventListener('click', saveRejectModalValue);
            rejectModalClear?.addEventListener('click', function() {
                if (!activeRejectRow) return;
                const qtyInput = activeRejectRow.querySelector('input.reject-qty-input');
                const reasonInput = activeRejectRow.querySelector('input.reject-reason-input');
                if (qtyInput) qtyInput.value = '';
                if (reasonInput) reasonInput.value = '';
                updateRejectTriggers(activeRejectRow);
                recalcSummaryAndUI();
                rejectModalInstance?.hide();
            });

            rejectModalEl?.addEventListener('hidden.bs.modal', function() {
                activeRejectRow = null;
                clearRejectModalErrors();
            });

            function clearPickedRow(row) {
                if (!row) return;

                const inputs = row.querySelectorAll('input.qty-input');
                inputs.forEach(input => {
                    input.value = '';
                    input.classList.remove('qty-input-active', 'border-warning');
                });
                row.querySelectorAll('input.reject-qty-input, input.reject-qty-input-mobile').forEach(input => {
                    input.value = '';
                    input.classList.remove('reject-input-active', 'border-warning');
                });
                row.querySelectorAll('input.reject-reason-input, input.reject-reason-input-mobile').forEach(input => {
                    input.value = '';
                });

                const checks = row.querySelectorAll('.row-check');
                checks.forEach(check => check.checked = false);

                row.classList.remove('row-picked', 'is-selected', 'summary-focus');
                row.classList.add('row-empty');
                updateRejectTriggers(row);
            }

            selectedSummaryList?.addEventListener('click', function(e) {
                const trigger = e.target.closest('.selected-summary-remove');
                if (!trigger) return;

                const row = rowsArr.find(item => item.dataset.rowIndex === trigger.dataset.rowIndex);
                if (!row) return;

                clearPickedRow(row);
                recalcSummaryAndUI();
                focusSearchInput();
            });

            selectedSummaryClear?.addEventListener('click', function() {
                rowsArr.forEach(row => clearPickedRow(row));
                recalcSummaryAndUI();
                focusSearchInput();
            });

            // DESKTOP toggle only picked
            toggleOnlyPicked?.addEventListener('change', function() {
                state.showOnlyPickedDesktop = !!this.checked;
                applyRowVisibility();
            });

            // SUBMIT SHORTCUT
            if (submitShortcutBtn && submitBtn) {
                submitShortcutBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!this.disabled) submitBtn.click();
                });
            }

            // FILTER KODE BARANG
            itemCodeSelect?.addEventListener('change', function() {
                state.activeItemCode = this.value || '';
                recalcSummaryAndUI();
            });

            document.getElementById('operator_id_auto')?.addEventListener('change', recalcSummaryAndUI);

            // PRE-FILTER dari query ?sku= (mis. klik baris di Dashboard Produksi → Prioritas)
            (function applySkuFromQuery() {
                if (!itemCodeSelect) return;
                const params = new URLSearchParams(window.location.search);
                const sku = normalizeText(params.get('sku') || params.get('item_code'));
                if (!sku) return;
                const match = Array.from(itemCodeSelect.options)
                    .some(o => normalizeText(o.value) === sku);
                if (!match) {
                    itemCodeSelect.add(new Option(`${sku} — belum ada stok jahit`, sku));
                }
                itemCodeSelect.value = sku;
                state.activeItemCode = sku;
                recalcSummaryAndUI();
            })();

            // FOCAL POINT SELECT di mobile
            if (isMobile() && itemCodeSelectWrap) {
                setTimeout(() => {
                    itemCodeSelectWrap.classList.add('focal-pulse');
                    setTimeout(
                        () => itemCodeSelectWrap.classList.remove('focal-pulse'),
                        1200
                    );
                }, 250);
            }

            // SEARCH
            searchInput?.addEventListener('input', function() {
                const cursorPos = this.selectionStart;
                this.value = this.value.toUpperCase();
                this.setSelectionRange(cursorPos, cursorPos);

                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    recalcSummaryAndUI();
                }, 120);
            });

            // PER-ROW BEHAVIOR
            restorePickupDraft();

            rowsArr.forEach(row => {
                const qtyInputs = row.querySelectorAll('input.qty-input');
                const rejectInputs = row.querySelectorAll('input.reject-qty-input, input.reject-qty-input-mobile');
                const rejectReasonInputs = row.querySelectorAll('input.reject-reason-input, input.reject-reason-input-mobile');
                const pickButtons = row.querySelectorAll('.btn-pick');
                const rowChecks = row.querySelectorAll('.row-check');

                if (!qtyInputs.length) return;

                const desktopInput = qtyInputs[0];
                const mobileInput = qtyInputs[1] || null;
                const desktopRejectInput = rejectInputs[0] || null;
                const mobileRejectInput = rejectInputs[1] || null;
                const desktopRejectReasonInput = rejectReasonInputs[0] || null;
                const mobileRejectReasonInput = rejectReasonInputs[1] || null;

                const getCurrentQty = () => parseFloat(desktopInput.value || '0');
                const getCurrentRejectQty = () => parseFloat(desktopRejectInput?.value || '0') || 0;
                const isPicked = () => getCurrentQty() + getCurrentRejectQty() > 0;

                function syncInputsFromDesktop() {
                    if (mobileInput) mobileInput.value = desktopInput.value;
                }

                function syncDesktopFromMobile() {
                    if (mobileInput) desktopInput.value = mobileInput.value;
                }

                function syncRejectInputsFromDesktop() {
                    if (mobileRejectInput && desktopRejectInput) mobileRejectInput.value = desktopRejectInput.value;
                    if (mobileRejectReasonInput && desktopRejectReasonInput) mobileRejectReasonInput.value = desktopRejectReasonInput.value;
                }

                function syncRejectDesktopFromMobile() {
                    if (mobileRejectInput && desktopRejectInput) desktopRejectInput.value = mobileRejectInput.value;
                    if (mobileRejectReasonInput && desktopRejectReasonInput) desktopRejectReasonInput.value = mobileRejectReasonInput.value;
                }

                function updateVisual() {
                    const picked = isPicked();
                    rowChecks.forEach(chk => chk.checked = picked);

                    row.classList.toggle('row-picked', picked);
                    row.classList.toggle('is-selected', picked);
                    row.classList.toggle('row-empty', !picked);
                    updateReadyDisplay(row);
                }

                function applyFromState(picked) {
                    const nextQty = picked ? Math.max(rowBaseReadyQty(row) - rowRejectQty(row), 0) : 0;
                    desktopInput.value = nextQty > 0 ? nextQty : '';
                    if (mobileInput) mobileInput.value = desktopInput.value;
                    if (desktopRejectInput) desktopRejectInput.value = '';
                    if (mobileRejectInput) mobileRejectInput.value = '';
                    if (desktopRejectReasonInput) desktopRejectReasonInput.value = '';
                    if (mobileRejectReasonInput) mobileRejectReasonInput.value = '';

                    updateVisual();
                    recalcSummaryAndUI();

                    if (picked) {
                        focusSearchInput();
                    }
                }

                function togglePicked() {
                    const nextState = !isPicked();
                    applyFromState(nextState);
                }

                row.addEventListener('click', function(e) {
                    if (e.target.tagName === 'INPUT' || e.target.closest('button') || e.target.tagName === 'A' || e.target.closest('a')) return;
                    
                    if (row.classList.contains('is-unqced')) {
                        window.open(row.dataset.detailUrl, '_blank');
                        return;
                    }

                    togglePicked();
                });

                pickButtons.forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        applyFromState(true);
                    });
                });

                rowChecks.forEach(chk => {
                    chk.addEventListener('change', function(e) {
                        e.stopPropagation();
                        applyFromState(this.checked);
                    });
                });

                desktopInput.addEventListener('focus', function() {
                    this.select();
                    this.classList.add('qty-input-active');
                });

                desktopInput.addEventListener('blur', function() {
                    this.classList.remove('qty-input-active');
                    clampToReady(this, row);
                    clampRowQuantities(row);
                    syncInputsFromDesktop();
                    syncRejectInputsFromDesktop();
                    updateVisual();
                    recalcSummaryAndUI();

                    if (isPicked()) {
                        focusSearchInput();
                    }
                });

                desktopInput.addEventListener('input', function() {
                    clampRowQuantities(row);
                    syncInputsFromDesktop();
                    updateVisual();
                    recalcSummaryAndUI();
                });

                if (mobileInput) {
                    mobileInput.addEventListener('focus', function() {
                        this.select();
                        this.classList.add('qty-input-active');

                        if (isMobile()) {
                            const inputEl = this;
                            setTimeout(function() {
                                try {
                                    inputEl.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'center',
                                        inline: 'nearest'
                                    });
                                } catch (e) {}
                            }, 180);
                        }
                    });

                    mobileInput.addEventListener('blur', function() {
                        this.classList.remove('qty-input-active');
                        clampToReady(this, row);
                        clampRowQuantities(row);
                        syncDesktopFromMobile();
                        syncRejectInputsFromDesktop();
                        updateVisual();
                        recalcSummaryAndUI();

                        if (isPicked()) {
                            focusSearchInput();
                        }
                    });

                    mobileInput.addEventListener('input', function() {
                        clampRowQuantities(row);
                        syncDesktopFromMobile();
                        updateVisual();
                        recalcSummaryAndUI();
                    });
                }

                [desktopRejectInput, mobileRejectInput].filter(Boolean).forEach(input => {
                    input.addEventListener('focus', function() {
                        this.select();
                        this.classList.add('reject-input-active');
                    });

                    input.addEventListener('blur', function() {
                        this.classList.remove('reject-input-active');
                        if (this === mobileRejectInput) syncRejectDesktopFromMobile();
                        clampRowQuantities(row);
                        syncRejectInputsFromDesktop();
                        updateVisual();
                        recalcSummaryAndUI();
                    });

                    input.addEventListener('input', function() {
                        if (this === mobileRejectInput) syncRejectDesktopFromMobile();
                        clampRowQuantities(row);
                        syncRejectInputsFromDesktop();
                        updateVisual();
                        recalcSummaryAndUI();
                    });
                });

                [desktopRejectReasonInput, mobileRejectReasonInput].filter(Boolean).forEach(input => {
                    input.addEventListener('input', function() {
                        if (this === mobileRejectReasonInput && desktopRejectReasonInput) {
                            desktopRejectReasonInput.value = this.value;
                        } else if (mobileRejectReasonInput) {
                            mobileRejectReasonInput.value = this.value;
                        }
                        savePickupDraft();
                    });
                    input.addEventListener('blur', function() {
                        if (this === mobileRejectReasonInput && desktopRejectReasonInput) {
                            desktopRejectReasonInput.value = this.value;
                        } else if (mobileRejectReasonInput) {
                            mobileRejectReasonInput.value = this.value;
                        }
                        syncRejectInputsFromDesktop();
                    });
                });

                // inisialisasi awal (kalau ada old value)
                clampToReady(desktopInput, row);
                clampRowQuantities(row);
                syncInputsFromDesktop();
                syncRejectInputsFromDesktop();
                updateRejectTriggers(row);
                updateVisual();
            });

            // Auto fokus ke search saat halaman dibuka
            if (searchInput) {
                focusSearchInput();
            }

            // Scroll ke input pertama yang error (kalau ada)
            const firstInvalid = document.querySelector('.qty-input.is-invalid');
            if (firstInvalid) {
                setTimeout(() => {
                    firstInvalid.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    firstInvalid.focus();
                }, 200);
            }

            // INIT
            recalcSummaryAndUI();

            document.addEventListener('sewing-pickup:operator-changed', () => {
                const selectedItems = rowsArr.flatMap(row => {
                    const qty = parseFloat(row.querySelector('input.qty-input')?.value || '0') || 0;
                        const reject = parseFloat(row.querySelector('input.reject-qty-input')?.value || '0') || 0;
                        return qty + reject > 0 ? [{
                        rowIndex: row.dataset.rowIndex || '',
                        bundleCode: row.dataset.bundleCode || '',
                        itemCode: row.dataset.itemCode || '',
                        bundleId: getRowBundleId(row),
                        qtyValue: qty,
                            rejectValue: reject,
                        qtyLabel: qtyNf.format(qty),
                            rejectLabel: qtyNf.format(reject),
                    }] : [];
                });
                renderHppPreview(selectedItems);
                renderAccountingAndMutationPreview(selectedItems);
            });
        });
    </script>
@endpush
