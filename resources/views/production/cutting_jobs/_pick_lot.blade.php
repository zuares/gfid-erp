{{-- resources/views/production/cutting_jobs/_pick_lot.blade.php --}}

@push('head')
    <style>
        .lot-picker-wrap {
            margin-bottom: .75rem;
        }

        .lot-picker-header {
            display: flex;
            flex-direction: column;
            gap: .4rem;
            margin-bottom: .75rem;
        }

        @media (min-width: 576px) {
            .lot-picker-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: flex-end;
            }
        }

        .lot-picker-title {
            font-size: .9rem;
            font-weight: 600;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .lot-picker-help {
            font-size: .8rem;
        }

        .lot-picker-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
        }

        .lot-refresh-btn {
            border: 1px solid rgba(37, 99, 235, .28);
            background: rgba(37, 99, 235, .06);
            color: #1d4ed8;
            border-radius: 999px;
            padding: .18rem .55rem;
            font-size: .68rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .lot-refresh-btn:disabled {
            opacity: .62;
        }

        .lot-refresh-status {
            font-size: .68rem;
            color: var(--muted);
        }

        /* === STEP INDICATOR BAR === */
        .lot-step-bar {
            display: flex;
            align-items: center;
            margin-bottom: .7rem;
            padding: .42rem .52rem;
            background: rgba(148, 163, 184, .06);
            border-radius: 8px;
            border: 1px solid rgba(148, 163, 184, .15);
        }

        body[data-theme="dark"] .lot-step-bar {
            background: rgba(15, 23, 42, .3);
            border-color: rgba(148, 163, 184, .12);
        }

        .lot-step-item {
            display: flex;
            align-items: center;
            gap: .25rem;
        }

        .lot-step-num {
            width: 17px;
            height: 17px;
            border-radius: 50%;
            border: 1.5px solid rgba(148, 163, 184, .4);
            background: transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .6rem;
            font-weight: 700;
            color: var(--muted);
            transition: all .18s;
            flex-shrink: 0;
        }

        .lot-step-item.active .lot-step-num {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .18);
        }

        .lot-step-label {
            font-size: .62rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .05em;
            transition: color .18s;
        }

        .lot-step-item.active .lot-step-label {
            color: var(--text);
        }

        .lot-step-sep {
            flex: 1;
            height: 1px;
            background: rgba(148, 163, 184, .25);
            margin: 0 .35rem;
            min-width: 12px;
        }

        /* === ITEM PICK BUTTONS (step 1) === */
        .lot-item-select-list {
            display: flex;
            flex-direction: column;
            gap: .22rem;
        }

        .lot-item-pick-btn {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .52rem .62rem .52rem .7rem;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, .3);
            border-left: 3px solid rgba(148, 163, 184, .35);
            background: var(--card);
            cursor: pointer;
            text-align: left;
            width: 100%;
            transition:
                border-color .14s,
                border-left-color .14s,
                box-shadow .14s,
                transform .07s,
                background .14s;
            -webkit-tap-highlight-color: transparent;
        }

        .lot-item-pick-btn:hover {
            border-color: rgba(59, 130, 246, .35);
            border-left-color: #2563eb;
            box-shadow: 0 3px 12px rgba(15, 23, 42, .10);
            transform: translateX(2px);
            outline: none;
        }

        .lot-item-pick-btn:active {
            transform: translateX(1px);
        }

        .lot-item-pick-btn.has-selected {
            border-color: rgba(37, 99, 235, .3);
            border-left-color: #2563eb;
            background: color-mix(in srgb, var(--card) 90%, rgba(59, 130, 246, .06));
        }

        body[data-theme="dark"] .lot-item-pick-btn {
            border-left-color: rgba(99, 149, 246, .4);
        }

        body[data-theme="dark"] .lot-item-pick-btn:hover,
        body[data-theme="dark"] .lot-item-pick-btn.has-selected {
            border-left-color: #3b82f6;
            border-color: rgba(99, 149, 246, .35);
            background: color-mix(in srgb, var(--card) 85%, rgba(37, 99, 235, .15));
        }

        /* Left section: name + meta */
        .lipb-left {
            display: flex;
            flex-direction: column;
            gap: .06rem;
            min-width: 0;
            flex: 1;
        }

        .lipb-name {
            font-size: .8rem;
            font-weight: 650;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
        }

        .lipb-meta {
            display: flex;
            align-items: center;
            gap: .25rem;
            font-size: .62rem;
            color: var(--muted);
        }

        .lipb-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: .58rem;
            padding: .02rem .3rem;
            border-radius: 4px;
            background: rgba(148, 163, 184, .1);
            border: 1px solid rgba(148, 163, 184, .2);
            letter-spacing: .02em;
        }

        .lipb-dot { opacity: .35; font-size: .55rem; }

        /* Center: qty stock (focal point) */
        .lipb-qty {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            flex-shrink: 0;
            gap: 0;
        }

        .lipb-qty-num {
            font-size: .88rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            color: var(--text);
            line-height: 1;
        }

        .lipb-qty-unit {
            font-size: .6rem;
            color: var(--muted);
            font-weight: 500;
            text-align: right;
        }

        /* Right: selected badge + arrow */
        .lipb-right {
            display: flex;
            align-items: center;
            gap: .28rem;
            flex-shrink: 0;
        }

        .lipb-selected {
            font-size: .58rem;
            font-weight: 700;
            padding: .06rem .4rem;
            border-radius: 999px;
            background: #2563eb;
            color: #fff;
            display: none;
            white-space: nowrap;
        }

        .lipb-selected.visible { display: inline-block; }

        .lipb-arrow {
            width: 12px;
            height: 12px;
            color: rgba(100, 116, 139, .5);
            flex-shrink: 0;
            transition: transform .14s, color .14s;
        }

        .lot-item-pick-btn:hover .lipb-arrow {
            color: #2563eb;
            transform: translateX(2px);
        }

        /* Step done (completed) */
        .lot-step-item.done .lot-step-num {
            background: rgba(37, 99, 235, .15);
            border-color: rgba(37, 99, 235, .35);
            color: #2563eb;
        }

        .lot-step-item.done .lot-step-label {
            color: var(--muted);
        }

        /* === STEP 3 SUMMARY (compact) === */
        .lot-step3-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding: .38rem .48rem;
            background: color-mix(in srgb, var(--card) 85%, rgba(37, 99, 235, .1));
            border: 1px solid rgba(37, 99, 235, .2);
            border-radius: 8px;
        }

        .lot-step3-info {
            font-size: .72rem;
            color: var(--muted);
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .lot-step3-info strong {
            color: var(--text);
        }

        /* === STEP 2 HEADER === */
        .lot-step2-header {
            display: flex;
            align-items: center;
            gap: .45rem;
            margin-bottom: .45rem;
        }

        .lot-back-btn {
            background: transparent;
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 999px;
            padding: .18rem .55rem;
            font-size: .68rem;
            font-weight: 500;
            color: var(--muted);
            cursor: pointer;
            flex-shrink: 0;
            transition: border-color .12s, color .12s;
            line-height: 1.4;
        }

        .lot-back-btn:hover {
            border-color: rgba(59, 130, 246, .45);
            color: var(--text);
        }

        .lot-step2-item-name {
            font-size: .75rem;
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
        }

        /* Hide accordion header in step 2 */
        .lot-accordion-header-hidden {
            display: none !important;
        }

        /* Open accordion body by default in step 2 */
        .lot-item-group.step2-open .lot-accordion-body {
            display: block;
        }

        /* === ACCORDION ITEM GROUP === */
        .lot-item-group {
            margin-top: .4rem;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.4);
            background: color-mix(in srgb, var(--card) 88%, rgba(59, 130, 246, 0.06));
            overflow: hidden;
            transition: border-color 0.15s;
        }

        .lot-item-group.has-selected {
            border-color: rgba(59, 130, 246, 0.6);
        }

        body[data-theme="dark"] .lot-item-group {
            background: color-mix(in srgb, var(--card) 90%, rgba(15, 23, 42, 0.9));
            border-color: rgba(148, 163, 184, 0.5);
        }

        body[data-theme="dark"] .lot-item-group.has-selected {
            border-color: rgba(99, 149, 246, 0.7);
        }

        /* === ACCORDION HEADER (clickable) === */
        .lot-accordion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .4rem;
            padding: .32rem .48rem;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .lot-accordion-toggle {
            border: 0;
            background: transparent;
            color: inherit;
            padding: 0;
            margin: 0;
            min-width: 0;
            flex: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            text-align: left;
            cursor: pointer;
        }

        .lot-accordion-header:hover,
        .lot-accordion-toggle:focus-visible {
            background: rgba(59, 130, 246, 0.04);
        }

        .lot-accordion-header-left {
            display: flex;
            flex-direction: column;
            gap: .05rem;
            min-width: 0;
        }

        .lot-item-header-row {
            display: flex;
            align-items: baseline;
            gap: .35rem;
            flex-wrap: nowrap;
            min-width: 0;
        }

        .lot-accordion-header-right {
            display: flex;
            align-items: center;
            gap: .38rem;
            flex-shrink: 0;
        }

        .lot-group-check {
            display: inline-flex;
            align-items: center;
            font-size: .72rem;
            color: var(--muted);
            cursor: pointer;
        }

        .lot-group-check input {
            cursor: pointer;
        }

        .lot-item-name {
            font-size: .78rem;
            font-weight: 600;
            line-height: 1.18;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .lot-item-code-inline {
            font-size: .72rem;
            font-weight: 500;
            color: var(--muted);
            margin-right: .3rem;
            opacity: .8;
        }

        .lot-item-code-pill {
            display: inline-block;
            font-size: .58rem;
            font-weight: 500;
            padding: .04rem .36rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, .1);
            border: 1px solid rgba(148, 163, 184, .25);
            color: var(--muted);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .lot-item-meta {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            flex-wrap: wrap;
            color: var(--muted);
            font-size: .67rem;
            line-height: 1.15;
        }

        /* Badge count LOT terpilih */
        .lot-selected-badge {
            display: none;
            font-size: .68rem;
            font-weight: 700;
            padding: .1rem .45rem;
            border-radius: 999px;
            background: rgba(37, 99, 235, 0.12);
            color: #1d4ed8;
            border: 1px solid rgba(37, 99, 235, 0.35);
            white-space: nowrap;
        }

        body[data-theme="dark"] .lot-selected-badge {
            background: rgba(99, 149, 246, 0.2);
            color: #93c5fd;
            border-color: rgba(99, 149, 246, 0.4);
        }

        .lot-selected-badge.visible {
            display: inline-flex;
        }

        /* Chevron */
        .lot-accordion-chevron {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            transition: transform 0.2s ease;
            color: rgba(100, 116, 139, 0.8);
        }

        .lot-item-group.open .lot-accordion-chevron {
            transform: rotate(180deg);
        }

        /* === ACCORDION BODY === */
        .lot-accordion-body {
            display: none;
            padding: 0 .5rem .45rem;
        }

        .lot-item-group.open .lot-accordion-body {
            display: block;
        }

        /* === LOT GRID === */
        .lot-grid {
            display: grid;
            gap: .38rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        @media (min-width: 992px) {
            .lot-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        /* === LOT CARD === */
        .lot-card-modern {
            border-radius: 8px;
            border: 1px solid rgba(148, 163, 184, 0.45);
            padding: .38rem .42rem .3rem;
            background: var(--card);
            cursor: pointer;
            transition:
                background-color 0.15s ease,
                box-shadow 0.15s ease,
                border-color 0.15s ease,
                transform 0.06s ease;
        }

        .lot-card-modern:hover {
            box-shadow:
                0 8px 20px rgba(15, 23, 42, 0.13),
                0 0 0 1px rgba(59, 130, 246, 0.3);
            border-color: rgba(59, 130, 246, 0.5);
            transform: translateY(-1px);
        }

        .lot-card-modern .lot-code {
            font-size: .78rem;
            letter-spacing: .02em;
        }

        .lot-card-modern .lot-balance {
            font-size: .82rem;
        }

        .lot-card-modern .lot-warehouse {
            font-size: .68rem;
        }

        .lot-card-modern .lot-purchase-date {
            font-size: .68rem;
            line-height: 1.2;
            white-space: nowrap;
        }

        .lot-card-modern.lot-selected {
            border-color: rgba(59, 130, 246, 0.9);
            box-shadow:
                0 8px 24px rgba(37, 99, 235, 0.16),
                0 0 0 1px rgba(59, 130, 246, 0.5);
            background: color-mix(in srgb, var(--card) 80%, rgba(59, 130, 246, 0.12));
        }

        .lot-row.lot-hidden {
            display: none;
        }

        .lot-card-badge {
            font-size: .65rem;
            padding: .04rem .4rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.6);
            background: rgba(148, 163, 184, 0.1);
        }

        .lot-card-check {
            margin-top: .45rem;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .lot-empty-hint {
            font-size: .8rem;
            color: var(--muted);
            margin-top: .35rem;
        }

        /* === CHECKBOX HIDDEN === */
        .lot-checkbox-hidden {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 0;
            height: 0;
        }

        /* === LOT CARD REDESIGN === */
        .lot-card-modern {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: .1rem;
        }

        .lot-qty-main {
            display: flex;
            align-items: baseline;
            gap: .18rem;
        }

        .lot-qty-main .lot-balance {
            font-size: .95rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1;
        }

        .lot-qty-unit {
            font-size: .62rem;
            font-weight: 500;
            color: var(--muted);
        }

        .lot-supplier-name {
            font-size: .65rem;
            color: var(--muted);
            font-weight: 400;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px;
            line-height: 1.2;
        }

        .lot-pills-row {
            display: flex;
            flex-wrap: wrap;
            gap: .2rem;
            margin-top: .08rem;
        }

        .lot-pill {
            display: inline-block;
            font-size: .58rem;
            font-weight: 500;
            padding: .05rem .32rem;
            border-radius: 999px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100px;
        }

        .lot-pill-item {
            background: rgba(148, 163, 184, .12);
            border: 1px solid rgba(148, 163, 184, .3);
            color: var(--muted);
        }

        .lot-pill-lot {
            background: rgba(148, 163, 184, .08);
            border: 1px solid rgba(148, 163, 184, .2);
            color: var(--muted);
            opacity: .75;
        }

        body[data-theme="dark"] .lot-pill-item,
        body[data-theme="dark"] .lot-pill-lot {
            background: rgba(148, 163, 184, .08);
            border-color: rgba(148, 163, 184, .2);
        }

        /* === CHECKMARK INDICATOR === */
        .lot-check-indicator {
            position: absolute;
            top: .34rem;
            right: .4rem;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 1.5px solid rgba(148, 163, 184, .5);
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            color: transparent;
            transition: all 0.15s ease;
        }

        .lot-card-modern.lot-selected .lot-check-indicator {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }

        body[data-theme="dark"] .lot-card-modern.lot-selected .lot-check-indicator {
            background: #3b82f6;
            border-color: #3b82f6;
        }

        /* === MOBILE OVERRIDES === */
        @media (max-width: 575.98px) {
            .lot-picker-wrap {
                margin-bottom: .65rem;
            }

            .lot-step-bar {
                position: sticky;
                top: .35rem;
                z-index: 20;
                border-radius: 14px;
                padding: .5rem .58rem;
                background: color-mix(in srgb, var(--card) 88%, rgba(37, 99, 235, .08));
                backdrop-filter: blur(12px);
            }

            .lot-step-label {
                font-size: .58rem;
                letter-spacing: .03em;
            }

            .lot-step-num {
                width: 20px;
                height: 20px;
                font-size: .65rem;
            }

            .lot-item-select-list {
                gap: .5rem;
            }

            .lot-item-pick-btn {
                min-height: 74px;
                padding: .74rem .78rem;
                border-radius: 14px;
                border-left-width: 4px;
                gap: .7rem;
            }

            .lipb-name {
                font-size: .9rem;
                white-space: normal;
                line-height: 1.22;
            }

            .lipb-meta {
                margin-top: .12rem;
                font-size: .68rem;
            }

            .lipb-code {
                font-size: .64rem;
                padding: .03rem .36rem;
            }

            .lipb-qty-num {
                font-size: 1rem;
            }

            .lipb-qty-unit {
                font-size: .64rem;
            }

            .lipb-right {
                gap: .2rem;
            }

            .lipb-arrow {
                width: 16px;
                height: 16px;
            }

            .lot-accordion-header {
                min-height: 48px;
                padding: .42rem .58rem;
                gap: .5rem;
            }

            .lot-step2-header {
                align-items: flex-start;
                gap: .55rem;
                margin-bottom: .62rem;
            }

            .lot-back-btn {
                min-height: 34px;
                padding-inline: .72rem;
                font-size: .72rem;
            }

            .lot-step2-item-name {
                padding-top: .25rem;
                font-size: .84rem;
                line-height: 1.25;
                white-space: normal;
            }

            .lot-accordion-toggle {
                min-height: 40px;
            }

            .lot-item-name {
                font-size: .82rem;
                line-height: 1.22;
            }

            .lot-item-meta {
                font-size: .7rem;
                gap: .32rem;
            }

            .lot-group-check {
                min-width: 48px;
                min-height: 48px;
                justify-content: center;
                border-radius: 999px;
                background: rgba(148, 163, 184, .1);
            }

            .lot-group-check input,
            .lot-checkbox {
                width: 1.5rem;
                height: 1.5rem;
            }

            .lot-grid {
                grid-template-columns: 1fr;
                gap: .62rem;
            }

            .lot-accordion-body {
                padding: 0 .58rem .68rem;
            }

            /* Sticky confirm bar at bottom */
            .lot-picker-footer {
                position: sticky;
                bottom: 0;
                background: var(--card, #fff);
                margin: .5rem -.85rem -.85rem;
                padding: .75rem .85rem;
                border-top: 1px solid rgba(148, 163, 184, .2);
                box-shadow: 0 -6px 20px rgba(15, 23, 42, .10);
                z-index: 30;
                border-radius: 0 0 14px 14px;
            }

            .lot-picker-footer .btn-primary {
                min-height: 48px;
                font-size: .95rem;
                font-weight: 700;
                letter-spacing: .02em;
            }

            .lot-card-modern {
                min-height: 72px;
                padding: .72rem .78rem;
                border-radius: 14px;
            }

            .lot-card-main {
                align-items: center !important;
                gap: .7rem !important;
            }

            .lot-card-check {
                margin-top: 0;
                min-width: 44px;
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                background: rgba(148, 163, 184, .08);
            }

            .lot-card-check .form-check {
                min-height: auto;
                margin: 0;
            }

            .lot-picker-help {
                font-size: .78rem;
            }

            .btn-pill-sm {
                font-size: .75rem;
                padding-block: .2rem;
            }

            .lot-picker-footer {
                flex-direction: column;
                align-items: stretch;
                gap: .4rem;
                border-radius: 16px 16px 0 0;
            }

            .lot-picker-footer .btn-primary {
                width: 100%;
                justify-content: center;
                border-radius: 12px;
            }

            .lot-picker-actions {
                margin-top: .25rem;
            }

            #lot-footer-hint {
                font-size: .74rem;
                text-align: center;
            }

            .lot-step3-summary {
                border-radius: 14px;
                padding: .62rem .68rem;
            }

            .lot-step3-info {
                font-size: .78rem;
                white-space: normal;
                line-height: 1.3;
            }
        }
    </style>
@endpush

<div class="lot-picker-wrap" id="cutting-pick-lot">

    {{-- Step indicator --}}
    <div class="lot-step-bar">
        <div class="lot-step-item active" id="lstep-ind-1">
            <span class="lot-step-num">1</span>
            <span class="lot-step-label">Pilih Kain</span>
        </div>
        <div class="lot-step-sep"></div>
        <div class="lot-step-item" id="lstep-ind-2">
            <span class="lot-step-num">2</span>
            <span class="lot-step-label">Centang LOT</span>
        </div>
        <div class="lot-step-sep"></div>
        <div class="lot-step-item" id="lstep-ind-3">
            <span class="lot-step-num">3</span>
            <span class="lot-step-label">Hasil Cutting</span>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center gap-2 mb-2" id="lot-live-tools">
        <button type="button" class="lot-refresh-btn" id="btn-refresh-lots"
            data-url="{{ route('production.cutting_jobs.lots.live') }}">
            Update LOT
        </button>
        <span class="lot-refresh-status" id="lot-refresh-status"></span>
    </div>

    @if ($lotStocks->isEmpty())
        <div class="lot-empty-hint">
            Belum ada LOT bahan baku utama yang siap dipakai. Cek stok kain utama di GRN / gudang RM.
        </div>
        <div id="lot-step1-panel" class="mt-2">
            <div class="lot-item-select-list"></div>
        </div>
        <div id="lot-step2-panel" style="display:none;">
            <div class="lot-step2-header">
                <button type="button" id="lot-back-btn" class="lot-back-btn">← Pilih kain lain</button>
                <div id="lot-step2-item-name" class="lot-step2-item-name"></div>
            </div>
            <div id="lot-grid"></div>
        </div>
    @else
        @php $groupedLots = $lotStocks->groupBy(fn($row) => $row->lot->item_id); @endphp

        {{-- STEP 1: Pilih Kain --}}
        <div id="lot-step1-panel">
            <div class="lot-item-select-list">
                @foreach ($groupedLots as $itemId => $rows)
                    @php
                        $firstRow     = $rows->first();
                        $item         = $firstRow->lot->item;
                        $totalBalance = $rows->sum('qty_balance');
                        $lotCount     = $rows->count();
                    @endphp
                    <button type="button" class="lot-item-pick-btn"
                        data-item-id="{{ $itemId }}"
                        data-item-name="{{ $item->name }}">
                        <div class="lipb-left">
                            <span class="lipb-name">{{ $item->name }}</span>
                            <div class="lipb-meta">
                                <span class="lipb-code">{{ $item->code }}</span>
                                <span class="lipb-dot">·</span>
                                <span>{{ $lotCount }} LOT</span>
                            </div>
                        </div>
                        <div class="lipb-qty">
                            <span class="lipb-qty-num">{{ number_format($totalBalance, 2, ',', '.') }}</span>
                            <span class="lipb-qty-unit">kg stok</span>
                        </div>
                        <div class="lipb-right">
                            <span class="lipb-selected" id="lipb-sel-{{ $itemId }}">✓</span>
                            <svg class="lipb-arrow" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- STEP 2: Centang LOT --}}
        <div id="lot-step2-panel" style="display:none;">
            <div class="lot-step2-header">
                <button type="button" id="lot-back-btn" class="lot-back-btn">← Pilih kain lain</button>
                <div id="lot-step2-item-name" class="lot-step2-item-name"></div>
            </div>

            <div id="lot-grid">
                @foreach ($groupedLots as $itemId => $rows)
                    @php
                        $firstRow     = $rows->first();
                        $lot          = $firstRow->lot;
                        $item         = $lot->item;
                        $totalBalance = $rows->sum('qty_balance');
                        $lotCount     = $rows->count();
                        $whCodes      = $rows->pluck('warehouse.code')->filter()->unique()->values();
                    @endphp

                    <div class="lot-item-group" data-item-id="{{ $itemId }}">

                        {{-- Accordion header — hidden in step 2, kept for JS compatibility --}}
                        <div class="lot-accordion-header lot-accordion-header-hidden">
                            <button type="button" class="lot-accordion-toggle">
                                <div class="lot-accordion-header-left">
                                    <div class="lot-item-header-row">
                                        <span class="lot-item-name">{{ $item->name }}</span>
                                        <span class="lot-item-code-pill mono">{{ $item->code }}</span>
                                    </div>
                                    <div class="lot-item-meta">
                                        <span>{{ $lotCount }} LOT</span>
                                        <span class="mono">{{ number_format($totalBalance, 2, ',', '.') }} kg</span>
                                        @if ($whCodes->isNotEmpty())
                                            <span>{{ $whCodes->implode(', ') }}</span>
                                        @endif
                                        <span class="lot-selected-badge" id="badge-item-{{ $itemId }}"></span>
                                    </div>
                                </div>
                                <svg class="lot-accordion-chevron" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        {{-- LOT cards --}}
                        <div class="lot-accordion-body">
                            <div class="lot-grid">
                                @foreach ($rows as $row)
                                    @php
                                        $lotRow      = $row->lot;
                                        $supplierName = $lotSupplierMap[$row->lot_id] ?? null;
                                    @endphp
                                    <div class="lot-card-modern lot-row lot-card-item"
                                        data-lot-id="{{ $row->lot_id }}"
                                        data-balance="{{ $row->qty_balance }}"
                                        data-item-id="{{ $item->id }}"
                                        data-item-code="{{ $item->code }}">

                                        <input type="checkbox" class="lot-checkbox lot-checkbox-hidden"
                                            name="selected_lots[]" value="{{ $row->lot_id }}"
                                            data-item-id="{{ $item->id }}"
                                            autocomplete="off"
                                            tabindex="-1">

                                        <div class="lot-qty-main">
                                            <span class="mono lot-balance">{{ number_format($row->qty_balance, 2, ',', '.') }}</span>
                                            <span class="lot-qty-unit">kg</span>
                                        </div>

                                        @if ($supplierName)
                                            <div class="lot-supplier-name">{{ $supplierName }}</div>
                                        @endif

                                        <div class="lot-pills-row">
                                            <span class="lot-pill lot-pill-item mono">{{ $item->code }}</span>
                                            <span class="lot-pill lot-pill-lot mono">{{ $lotRow->code }}</span>
                                        </div>

                                        <div class="lot-check-indicator">
                                            <svg viewBox="0 0 20 20" fill="currentColor" width="10" height="10">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- STEP 3: Ringkasan LOT (compact, setelah confirm) --}}
    <div id="lot-step3-panel" style="display:none;">
        <div class="lot-step3-summary">
            <div class="lot-step3-info">
                <span id="lot-step3-label"></span>
            </div>
            <button type="button" id="btn-change-lots-picker" class="lot-back-btn">
                Ubah LOT
            </button>
        </div>
    </div>

    {{-- Footer --}}
    <div class="d-flex justify-content-between align-items-center mt-3 lot-picker-footer" id="lot-picker-footer">
        <div class="small text-muted" id="lot-footer-hint">
            Pilih kain dulu untuk melihat LOT yang tersedia.
        </div>
        <button type="button" class="btn btn-primary btn-sm" id="btn-confirm-lots">
            Simpan LOT &amp; Lanjut
        </button>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Reset checkbox saat load — cegah browser restore
            document.querySelectorAll('.lot-checkbox').forEach(cb => { cb.checked = false; });

            const lotGrid    = document.getElementById('lot-grid');
            const step1Panel = document.getElementById('lot-step1-panel');
            const step2Panel = document.getElementById('lot-step2-panel');
            const step3Panel = document.getElementById('lot-step3-panel');
            const pickerFooter = document.getElementById('lot-picker-footer');
            const backBtn    = document.getElementById('lot-back-btn');
            const step2Name  = document.getElementById('lot-step2-item-name');
            const step3Label = document.getElementById('lot-step3-label');
            const footerHint = document.getElementById('lot-footer-hint');
            const stepInd1   = document.getElementById('lstep-ind-1');
            const stepInd2   = document.getElementById('lstep-ind-2');
            const stepInd3   = document.getElementById('lstep-ind-3');
            const btnChangeLotsPicker = document.getElementById('btn-change-lots-picker');
            const btnRefreshLots = document.getElementById('btn-refresh-lots');
            const lotRefreshStatus = document.getElementById('lot-refresh-status');

            let lotCards  = lotGrid ? Array.from(lotGrid.querySelectorAll('.lot-card-item')) : [];
            let lotGroups = lotGrid ? Array.from(lotGrid.querySelectorAll('.lot-item-group')) : [];

            /* ── STEP NAVIGATION ─────────────────── */
            function setStepActive(n) {
                [stepInd1, stepInd2, stepInd3].forEach((el, i) => {
                    if (!el) return;
                    const step = i + 1;
                    el.classList.toggle('active', step === n);
                    el.classList.toggle('done',   step < n);
                });
            }

            function goToStep1() {
                lotGroups = lotGrid ? Array.from(lotGrid.querySelectorAll('.lot-item-group')) : [];
                if (step1Panel) step1Panel.style.display = '';
                if (step2Panel) step2Panel.style.display = 'none';
                if (step3Panel) step3Panel.style.display = 'none';
                if (pickerFooter) pickerFooter.style.display = '';
                lotGroups.forEach(g => {
                    g.style.display = 'none';
                    g.classList.remove('step2-open');
                });
                setStepActive(1);
                if (footerHint) footerHint.textContent = 'Pilih kain dulu untuk melihat LOT yang tersedia.';
            }

            function goToStep2(itemId, itemName) {
                lotGroups = lotGrid ? Array.from(lotGrid.querySelectorAll('.lot-item-group')) : [];
                if (step1Panel) step1Panel.style.display = 'none';
                if (step2Panel) step2Panel.style.display = '';
                if (step3Panel) step3Panel.style.display = 'none';
                if (pickerFooter) pickerFooter.style.display = '';
                lotGroups.forEach(g => {
                    const gId = g.getAttribute('data-item-id');
                    if (gId === String(itemId)) {
                        g.style.display = '';
                        g.classList.add('step2-open');
                    } else {
                        g.style.display = 'none';
                        g.classList.remove('step2-open');
                    }
                });
                if (step2Name) step2Name.textContent = itemName || '';
                setStepActive(2);
                if (footerHint) footerHint.textContent = 'Centang satu atau beberapa LOT, lalu lanjut.';
            }

            function goToStep3(summaryText) {
                if (step1Panel) step1Panel.style.display = 'none';
                if (step2Panel) step2Panel.style.display = 'none';
                if (step3Panel) step3Panel.style.display = '';
                if (pickerFooter) pickerFooter.style.display = 'none';
                if (step3Label) step3Label.innerHTML = summaryText || '';
                setStepActive(3);
            }

            // Expose for _form.blade.php
            window.lotPickerGoToStep1 = goToStep1;
            window.lotPickerGoToStep2 = goToStep2;
            window.lotPickerGoToStep3 = goToStep3;

            // Init: show step 1, hide all groups
            lotGroups.forEach(g => { g.style.display = 'none'; });

            // Item pick buttons
            if (step1Panel) {
                step1Panel.querySelectorAll('.lot-item-pick-btn').forEach(btn => {
                    if (btn.dataset.bound === '1') return;
                    btn.dataset.bound = '1';
                    btn.addEventListener('click', function () {
                        const itemId   = this.getAttribute('data-item-id');
                        const itemName = this.getAttribute('data-item-name');
                        goToStep2(itemId, itemName);
                    });
                });
            }

            // Back button (step 2 → step 1)
            if (backBtn) backBtn.addEventListener('click', goToStep1);

            // "Ubah LOT" in step 3 → step 1
            if (btnChangeLotsPicker) {
                btnChangeLotsPicker.addEventListener('click', () => {
                    // delegate to _form.blade.php's unlock + showPickLot
                    document.getElementById('btn-change-lots')?.click();
                });
            }

            /* ── BADGE: N LOT dipilih ───────────────── */
            function updateGroupBadge(group) {
                const itemId  = group.getAttribute('data-item-id');
                const checked = Array.from(group.querySelectorAll('.lot-checkbox')).filter(cb => cb.checked).length;

                // Badge inside accordion header (not visible but kept for compat)
                const badge = document.getElementById('badge-item-' + itemId);
                if (badge) {
                    if (checked > 0) {
                        badge.textContent = checked + ' dipilih';
                        badge.classList.add('visible');
                    } else {
                        badge.textContent = '';
                        badge.classList.remove('visible');
                    }
                }

                // Badge + border on step 1 pick button
                const pickBtn  = step1Panel ? step1Panel.querySelector('.lot-item-pick-btn[data-item-id="' + itemId + '"]') : null;
                const lipbSel  = document.getElementById('lipb-sel-' + itemId);
                if (pickBtn) pickBtn.classList.toggle('has-selected', checked > 0);
                if (lipbSel) {
                    if (checked > 0) {
                        lipbSel.textContent = checked + ' dipilih';
                        lipbSel.classList.add('visible');
                    } else {
                        lipbSel.textContent = '';
                        lipbSel.classList.remove('visible');
                    }
                }
            }

            /* ── CARD CLICK → toggle checkbox ──────── */
            function bindLotPickerCards() {
                lotCards  = lotGrid ? Array.from(lotGrid.querySelectorAll('.lot-card-item')) : [];
                lotGroups = lotGrid ? Array.from(lotGrid.querySelectorAll('.lot-item-group')) : [];

                if (step1Panel) {
                    step1Panel.querySelectorAll('.lot-item-pick-btn').forEach(btn => {
                        if (btn.dataset.bound === '1') return;
                        btn.dataset.bound = '1';
                        btn.addEventListener('click', function () {
                            const itemId   = this.getAttribute('data-item-id');
                            const itemName = this.getAttribute('data-item-name');
                            goToStep2(itemId, itemName);
                        });
                    });
                }

                lotCards.forEach(card => {
                    const checkbox = card.querySelector('.lot-checkbox');
                    if (!checkbox) return;

                    function syncCardState() {
                        card.classList.toggle('lot-selected', checkbox.checked);
                        const group = card.closest('.lot-item-group');
                        if (group) updateGroupBadge(group);
                    }

                    if (card.dataset.bound !== '1') {
                        card.dataset.bound = '1';
                        card.addEventListener('click', function () {
                            checkbox.checked = !checkbox.checked;
                            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                            syncCardState();
                        });
                    }

                    if (checkbox.dataset.boundPicker !== '1') {
                        checkbox.dataset.boundPicker = '1';
                        checkbox.addEventListener('change', syncCardState);
                    }

                    syncCardState();
                });
            }

            function formatNumber(value) {
                return Number(value || 0).toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
            }

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, function (ch) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
                });
            }

            function renderLotGroups(groups, selectedIds) {
                if (!step1Panel || !lotGrid) return;

                step1Panel.innerHTML = '<div class="lot-item-select-list"></div>';
                const itemList = step1Panel.querySelector('.lot-item-select-list');
                lotGrid.innerHTML = '';

                groups.forEach(group => {
                    const itemId = String(group.item_id);
                    const selectedCount = (group.lots || []).filter(lot => selectedIds.has(String(lot.lot_id))).length;

                    itemList.insertAdjacentHTML('beforeend', `
                        <button type="button" class="lot-item-pick-btn ${selectedCount > 0 ? 'has-selected' : ''}"
                            data-item-id="${escapeHtml(itemId)}"
                            data-item-name="${escapeHtml(group.item_name)}">
                            <div class="lipb-left">
                                <span class="lipb-name">${escapeHtml(group.item_name)}</span>
                                <div class="lipb-meta">
                                    <span class="lipb-code">${escapeHtml(group.item_code)}</span>
                                    <span class="lipb-dot">·</span>
                                    <span>${formatNumber(group.lot_count)} LOT</span>
                                </div>
                            </div>
                            <div class="lipb-qty">
                                <span class="lipb-qty-num">${formatNumber(group.total_balance)}</span>
                                <span class="lipb-qty-unit">kg stok</span>
                            </div>
                            <div class="lipb-right">
                                <span class="lipb-selected ${selectedCount > 0 ? 'visible' : ''}" id="lipb-sel-${escapeHtml(itemId)}">${selectedCount > 0 ? selectedCount + ' dipilih' : ''}</span>
                                <svg class="lipb-arrow" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </button>
                    `);

                    const cards = (group.lots || []).map(lot => {
                        const checked = selectedIds.has(String(lot.lot_id));
                        const supplier = lot.supplier_name ? `<div class="lot-supplier-name">${escapeHtml(lot.supplier_name)}</div>` : '';
                        const date = lot.purchase_date ? `<span class="lot-pill lot-pill-lot mono">${escapeHtml(lot.purchase_date)}</span>` : '';

                        return `
                            <div class="lot-card-modern lot-row lot-card-item ${checked ? 'lot-selected' : ''}"
                                data-lot-id="${escapeHtml(lot.lot_id)}"
                                data-balance="${escapeHtml(lot.balance)}"
                                data-item-id="${escapeHtml(lot.item_id)}"
                                data-item-code="${escapeHtml(lot.item_code)}">
                                <input type="checkbox" class="lot-checkbox lot-checkbox-hidden"
                                    name="selected_lots[]" value="${escapeHtml(lot.lot_id)}"
                                    data-item-id="${escapeHtml(lot.item_id)}"
                                    autocomplete="off"
                                    tabindex="-1"
                                    ${checked ? 'checked' : ''}>
                                <div class="lot-qty-main">
                                    <span class="mono lot-balance">${formatNumber(lot.balance)}</span>
                                    <span class="lot-qty-unit">kg</span>
                                </div>
                                ${supplier}
                                <div class="lot-pills-row">
                                    <span class="lot-pill lot-pill-item mono">${escapeHtml(lot.item_code)}</span>
                                    <span class="lot-pill lot-pill-lot mono">${escapeHtml(lot.lot_code)}</span>
                                    ${date}
                                </div>
                                <div class="lot-check-indicator">
                                    <svg viewBox="0 0 20 20" fill="currentColor" width="10" height="10">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                        `;
                    }).join('');

                    lotGrid.insertAdjacentHTML('beforeend', `
                        <div class="lot-item-group" data-item-id="${escapeHtml(itemId)}" style="display:none;">
                            <div class="lot-accordion-header lot-accordion-header-hidden">
                                <button type="button" class="lot-accordion-toggle">
                                    <div class="lot-accordion-header-left">
                                        <div class="lot-item-header-row">
                                            <span class="lot-item-name">${escapeHtml(group.item_name)}</span>
                                            <span class="lot-item-code-pill mono">${escapeHtml(group.item_code)}</span>
                                        </div>
                                        <div class="lot-item-meta">
                                            <span>${formatNumber(group.lot_count)} LOT</span>
                                            <span class="mono">${formatNumber(group.total_balance)} kg</span>
                                            <span class="lot-selected-badge ${selectedCount > 0 ? 'visible' : ''}" id="badge-item-${escapeHtml(itemId)}">${selectedCount > 0 ? selectedCount + ' dipilih' : ''}</span>
                                        </div>
                                    </div>
                                </button>
                            </div>
                            <div class="lot-accordion-body">
                                <div class="lot-grid">${cards}</div>
                            </div>
                        </div>
                    `);
                });

                bindLotPickerCards();
            }

            async function refreshLotsLive(manual = false) {
                if (!btnRefreshLots || !btnRefreshLots.dataset.url) return;

                const selectedIds = new Set(Array.from(document.querySelectorAll('.lot-checkbox:checked')).map(cb => String(cb.value)));
                btnRefreshLots.disabled = true;
                if (lotRefreshStatus) lotRefreshStatus.textContent = 'Mengupdate...';

                try {
                    const resp = await fetch(btnRefreshLots.dataset.url, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const json = await resp.json();
                    if (!json.ok) throw new Error(json.message || 'LOT gagal diupdate.');

                    renderLotGroups(json.groups || [], selectedIds);
                    window.cuttingLotsDidRefresh?.();

                    const totalLots = (json.groups || []).reduce((sum, group) => sum + Number(group.lot_count || 0), 0);
                    if (lotRefreshStatus) lotRefreshStatus.textContent = `${formatNumber(totalLots)} LOT · ${json.updated_at || ''}`;
                } catch (e) {
                    if (lotRefreshStatus) lotRefreshStatus.textContent = manual ? 'Gagal update LOT' : '';
                } finally {
                    btnRefreshLots.disabled = false;
                }
            }

            window.cuttingLotPickerBind = bindLotPickerCards;
            window.cuttingLotPickerRefresh = refreshLotsLive;
            bindLotPickerCards();

            btnRefreshLots?.addEventListener('click', () => refreshLotsLive(true));

            setInterval(() => {
                const pickerVisible = document.getElementById('cutting-pick-lot')?.offsetParent !== null;
                const mainHidden = document.getElementById('cutting-main-content')?.classList.contains('d-none');
                if (pickerVisible && mainHidden) refreshLotsLive(false);
            }, 45000);
        });
    </script>
@endpush
