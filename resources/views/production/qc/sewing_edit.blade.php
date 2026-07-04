{{-- resources/views/production/qc/sewing_edit.blade.php --}}
@extends('layouts.app')

@section('title', 'QC Jahit · ' . $sewingReturn->code)

@push('head')
<style>
    /* ═══════════════════════════════════════════════════════
     * QC JAHIT — Production detail layout
     * ═══════════════════════════════════════════════════════ */

    :root {
        --gf-ink:    #0a0a0a;
        --gf-mid:    #64748b;
        --gf-line:   #e5e7eb;
        --gf-soft:   #f8fafc;
        --gf-white:  #fff;
        --gf-ok:     #16a34a;
        --gf-ok-soft:#ecfdf5;
        --gf-rej:    #dc2626;
        --gf-rej-soft:#fef2f2;
        --gf-warn:   #f59e0b;
        --gf-warn-soft:#fffbeb;
        --gf-accent: #6366f1;
        --gf-accent-soft: rgba(99,102,241,.06);
        --gf-font:   'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        --gf-mono:   ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        --gf-radius: 16px;
        --gf-radius-pill: 999px;
    }

    body[data-theme="dark"] {
        --gf-ink:    #f0f0f0;
        --gf-mid:    #94a3b8;
        --gf-line:   #1e293b;
        --gf-soft:   #0f172a;
        --gf-white:  #020617;
        --gf-ok-soft: rgba(22,163,74,.08);
        --gf-rej-soft: rgba(220,38,38,.08);
        --gf-warn-soft: rgba(245,158,11,.08);
        --gf-accent-soft: rgba(99,102,241,.10);
    }

    .qc-sewing-page {
        min-height: 100vh;
        font-family: var(--gf-font);
        -webkit-font-smoothing: antialiased;
    }

    .qc-sewing-page .page-wrap {
        max-width: 780px;
        margin-inline: auto;
        padding: 0 16px 120px;
    }

    /* ── Breadcrumb ───────────────────────────────────── */
    .qcs-breadcrumb {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 16px 0 0;
        font-size: 12px;
        font-weight: 600;
        color: var(--gf-mid);
    }
    .qcs-breadcrumb a {
        color: var(--gf-mid);
        text-decoration: none;
        transition: color .15s;
    }
    .qcs-breadcrumb a:hover { color: var(--gf-ink); }

    /* ── Page Head ────────────────────────────────────── */
    .qcs-page-head {
        padding: 14px 0 18px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    .qcs-page-title {
        font-size: 22px;
        font-weight: 900;
        letter-spacing: -.02em;
        color: var(--gf-ink);
        margin: 0;
        line-height: 1.2;
    }
    .qcs-page-title code {
        font-family: var(--gf-mono);
        font-size: .82em;
        font-weight: 800;
        background: none;
        color: inherit;
    }
    .qcs-page-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 6px;
        flex-wrap: wrap;
    }
    .qcs-meta-item {
        font-size: 12px;
        font-weight: 600;
        color: var(--gf-mid);
    }
    .qcs-meta-dot {
        width: 3px; height: 3px;
        border-radius: 50%;
        background: var(--gf-mid);
        opacity: .5;
    }

    /* ── Badges ───────────────────────────────────────── */
    .qcs-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        height: 24px;
        padding: 0 10px;
        border-radius: var(--gf-radius-pill);
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .qcs-badge-done {
        background: var(--gf-ok-soft);
        color: var(--gf-ok);
        border: 1px solid rgba(22,163,74,.18);
    }
    .qcs-badge-pending {
        background: var(--gf-warn-soft);
        color: var(--gf-warn);
        border: 1px solid rgba(245,158,11,.18);
    }

    /* ── Link chip (like storefront checkout-chip) ──── */
    .qcs-link-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        height: 32px;
        padding: 0 14px;
        border-radius: var(--gf-radius-pill);
        background: var(--gf-ink);
        color: var(--gf-white);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .02em;
        text-decoration: none;
        transition: opacity .15s;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .qcs-link-chip:hover { opacity: .85; }
    body[data-theme="dark"] .qcs-link-chip {
        background: var(--gf-soft);
        color: var(--gf-ink);
        border: 1px solid var(--gf-line);
    }

    /* ── Section / Card ──────────────────────────────── */
    .qcs-section {
        background: var(--gf-white);
        border: 1px solid var(--gf-line);
        border-radius: var(--gf-radius);
        padding: 16px;
        margin-bottom: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
    }
    .qcs-section-title {
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .10em;
        text-transform: uppercase;
        color: var(--gf-mid);
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* ── Form fields ─────────────────────────────────── */
    .qcs-field-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }
    .qcs-field-label {
        display: block;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--gf-mid);
        margin-bottom: 6px;
    }
    .qcs-field-input {
        width: 100%;
        height: 42px;
        padding: 0 12px;
        border-radius: 12px;
        border: 1.5px solid var(--gf-line);
        background: var(--gf-soft);
        color: var(--gf-ink);
        font-family: var(--gf-font);
        font-size: 14px;
        font-weight: 700;
        transition: border-color .15s, box-shadow .15s;
    }
    .qcs-field-input:focus {
        outline: none;
        border-color: var(--gf-ink);
        box-shadow: 0 0 0 3px rgba(10,10,10,.06);
    }
    .qcs-field-static {
        font-size: 14px;
        font-weight: 800;
        color: var(--gf-ink);
        padding: 10px 0 0;
    }

    /* ── Alert banners ───────────────────────────────── */
    .qcs-alert {
        border-radius: 14px;
        padding: 12px 14px;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 10px;
        line-height: 1.5;
    }
    .qcs-alert-success {
        background: var(--gf-ok-soft);
        border: 1px solid rgba(22,163,74,.18);
        color: #166534;
    }
    body[data-theme="dark"] .qcs-alert-success { color: #4ade80; }
    .qcs-alert-error {
        background: var(--gf-rej-soft);
        border: 1px solid rgba(220,38,38,.18);
        color: #991b1b;
    }
    body[data-theme="dark"] .qcs-alert-error { color: #fca5a5; }
    .qcs-alert-info {
        background: rgba(99,102,241,.05);
        border: 1px solid rgba(99,102,241,.15);
        color: var(--gf-ink);
    }

    /* ── QC Table ─────────────────────────────────────── */
    .qcs-table-wrap { overflow-x: auto; }
    .qcs-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .qcs-table thead th {
        font-size: 9px;
        font-weight: 950;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--gf-mid);
        background: var(--gf-soft);
        padding: 10px 12px;
        white-space: nowrap;
        border-bottom: 1.5px solid var(--gf-line);
    }
    .qcs-table thead th:first-child { border-radius: 10px 0 0 0; }
    .qcs-table thead th:last-child { border-radius: 0 10px 0 0; }
    .qcs-table tbody tr {
        border-bottom: 1px solid rgba(229,231,235,.6);
        transition: background .1s;
    }
    .qcs-table tbody tr:last-child { border-bottom: none; }
    .qcs-table tbody tr:hover { background: rgba(99,102,241,.02); }
    .qcs-table tbody td {
        padding: 10px 12px;
        vertical-align: middle;
    }

    /* Bundle pill */
    .qcs-bundle-pill {
        display: inline-flex;
        align-items: center;
        height: 24px;
        padding: 0 10px;
        border-radius: 8px;
        font-family: var(--gf-mono);
        font-size: 11px;
        font-weight: 800;
        background: var(--gf-accent-soft);
        color: var(--gf-accent);
        border: 1px solid rgba(99,102,241,.12);
    }
    .qcs-item-code {
        font-family: var(--gf-mono);
        font-size: 11px;
        font-weight: 800;
        color: var(--gf-mid);
    }
    .qcs-item-name {
        font-size: 12px;
        font-weight: 700;
        color: var(--gf-ink);
        line-height: 1.3;
    }
    .qcs-cut-ref {
        font-size: 10px;
        font-weight: 600;
        color: var(--gf-mid);
        margin-top: 2px;
    }
    .qcs-qty-display {
        font-family: var(--gf-mono);
        font-size: 14px;
        font-weight: 900;
        color: var(--gf-ink);
    }

    /* Input fields for QC */
    .qcs-qty-input {
        width: 72px;
        height: 36px;
        padding: 0 8px;
        border-radius: 10px;
        text-align: right;
        font-family: var(--gf-mono);
        font-size: 14px;
        font-weight: 900;
        font-variant-numeric: tabular-nums;
        border: 1.5px solid var(--gf-line);
        background: var(--gf-white);
        color: var(--gf-ink);
        transition: border-color .12s, box-shadow .12s;
    }
    .qcs-qty-input:focus {
        outline: none;
        border-color: var(--gf-ink);
        box-shadow: 0 0 0 3px rgba(10,10,10,.06);
    }
    .qcs-qty-input.is-ok {
        color: var(--gf-ok);
    }
    .qcs-qty-input.is-ok:focus {
        border-color: var(--gf-ok);
        box-shadow: 0 0 0 3px rgba(22,163,74,.10);
    }
    .qcs-qty-input.is-reject {
        color: var(--gf-rej);
    }
    .qcs-qty-input.is-reject:focus {
        border-color: var(--gf-rej);
        box-shadow: 0 0 0 3px rgba(220,38,38,.10);
    }

    .qcs-reason-input {
        width: 100%;
        height: 34px;
        padding: 0 10px;
        border-radius: 10px;
        font-family: var(--gf-font);
        font-size: 12px;
        font-weight: 600;
        border: 1.5px solid var(--gf-line);
        background: var(--gf-white);
        color: var(--gf-ink);
        transition: border-color .12s, box-shadow .12s;
    }
    .qcs-reason-input:focus {
        outline: none;
        border-color: var(--gf-warn);
        box-shadow: 0 0 0 3px rgba(245,158,11,.10);
    }
    .qcs-reason-input::placeholder {
        color: rgba(148,163,184,.6);
        font-weight: 500;
    }

    /* ── Summary card ────────────────────────────────── */
    .qcs-summary {
        margin-top: 16px;
        padding: 14px 16px;
        border-radius: 14px;
        background: var(--gf-ok-soft);
        border: 1px solid rgba(22,163,74,.12);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .qcs-summary-label {
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--gf-mid);
        margin-bottom: 4px;
    }
    .qcs-summary-values {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .qcs-summary-stat {
        text-align: center;
    }
    .qcs-summary-num {
        font-family: var(--gf-mono);
        font-size: 18px;
        font-weight: 950;
        line-height: 1;
        letter-spacing: -.02em;
    }
    .qcs-summary-num.is-ok { color: var(--gf-ok); }
    .qcs-summary-num.is-reject { color: var(--gf-rej); }
    .qcs-summary-tag {
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--gf-mid);
        margin-top: 3px;
    }

    /* ── Action bar (sticky bottom) ──────────────────── */
    .qcs-action-bar {
        position: fixed;
        left: 0; right: 0; bottom: 0;
        z-index: 100;
        background: rgba(255,255,255,.97);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border-top: 1px solid var(--gf-line);
        box-shadow: 0 -8px 24px rgba(0,0,0,.06);
        padding: 12px 16px calc(12px + env(safe-area-inset-bottom, 0px));
    }
    body[data-theme="dark"] .qcs-action-bar {
        background: rgba(2,6,23,.97);
        box-shadow: 0 -8px 24px rgba(0,0,0,.3);
    }
    .qcs-action-inner {
        max-width: 780px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }
    .qcs-action-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }
    .qcs-action-label {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--gf-mid);
    }
    .qcs-action-hint {
        font-size: 11px;
        font-weight: 600;
        color: var(--gf-mid);
    }
    .qcs-btn-group {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }
    .qcs-btn-save {
        height: 44px;
        min-width: 140px;
        padding: 0 20px;
        border-radius: 14px;
        background: var(--gf-ok);
        color: #fff;
        border: none;
        font-family: var(--gf-font);
        font-size: 13px;
        font-weight: 900;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: opacity .15s, transform .08s;
        box-shadow: 0 8px 20px rgba(22,163,74,.18);
    }
    .qcs-btn-save:hover { opacity: .9; }
    .qcs-btn-save:active { transform: scale(.97); }

    .qcs-btn-cancel {
        height: 44px;
        padding: 0 16px;
        border-radius: 14px;
        background: transparent;
        color: var(--gf-mid);
        border: 1.5px solid var(--gf-line);
        font-family: var(--gf-font);
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: border-color .15s, color .15s;
    }
    .qcs-btn-cancel:hover {
        border-color: var(--gf-ink);
        color: var(--gf-ink);
    }

    /* ── Empty state ─────────────────────────────────── */
    .qcs-empty {
        text-align: center;
        padding: 48px 20px;
        color: var(--gf-mid);
        font-size: 14px;
        font-weight: 600;
    }

    /* ── Production detail style override ─────────────── */
    :root {
        --gf-ink: var(--text, #0f172a);
        --gf-mid: var(--muted, #6b7280);
        --gf-line: var(--line, #e5e7eb);
        --gf-soft: rgba(148,163,184,.06);
        --gf-white: var(--card, #fff);
        --gf-accent: #2563eb;
        --gf-accent-soft: rgba(37,99,235,.06);
        --gf-radius: 14px;
        --gf-font: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .qc-sewing-page {
        font-family: inherit;
    }
    .qc-sewing-page .card {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 14px;
        box-shadow: none;
    }
    .qc-sewing-page .card-section {
        padding: 1rem 1.25rem;
    }
    .qc-sewing-page .mono {
        font-variant-numeric: tabular-nums;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
    }
    .qc-sewing-page .help {
        color: var(--muted);
        font-size: .85rem;
    }
    .qc-sewing-page .small-muted {
        color: var(--muted);
        font-size: .8rem;
    }
    .qc-sewing-page .hdr {
        display: flex;
        justify-content: space-between;
        gap: .75rem;
        flex-wrap: wrap;
        align-items: center;
    }
    .qc-sewing-page .hdr h1 {
        font-size: 1.02rem;
        font-weight: 900;
        margin: 0;
        letter-spacing: -.01em;
    }
    .qc-sewing-page .sub {
        color: var(--muted);
        font-size: .8rem;
        line-height: 1.35;
        margin-top: .15rem;
    }
    .qc-sewing-page .hdr-right {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
    }
    .qc-sewing-page .btn-header-link {
        border-radius: 999px;
        padding: .32rem .9rem;
        font-size: .78rem;
        font-weight: 600;
    }
    .qc-sewing-page .status-stepper {
        display: flex;
        align-items: center;
        gap: .75rem;
        font-size: .78rem;
        margin-top: .6rem;
    }
    .qc-sewing-page .status-step {
        display: flex;
        align-items: center;
        gap: .35rem;
    }
    .qc-sewing-page .status-dot {
        width: 18px;
        height: 18px;
        border-radius: 999px;
        border: 2px solid rgba(148,163,184,.7);
        background: transparent;
    }
    .qc-sewing-page .status-dot.active {
        background: rgba(34,197,94,.18);
        border-color: #22c55e;
    }
    .qc-sewing-page .status-dot.current {
        background: rgba(37,99,235,.18);
        border-color: #2563eb;
    }
    .qc-sewing-page .status-label {
        text-transform: uppercase;
        letter-spacing: .08em;
        font-size: .72rem;
        color: #6b7280;
    }
    .qc-sewing-page .status-label.current {
        color: #2563eb;
        font-weight: 700;
    }
    .qc-sewing-page .status-label.done {
        color: #16a34a;
        font-weight: 700;
    }
    .qc-sewing-page .status-separator {
        flex: 0 0 26px;
        height: 1px;
        background: linear-gradient(to right, rgba(148,163,184,.7), transparent);
    }
    .qc-sewing-page .page-wrap {
        max-width: 1100px;
        padding: .75rem .75rem 5.25rem;
    }
    .qcs-breadcrumb {
        padding-top: 0;
        margin-bottom: .5rem;
        font-size: .78rem;
        font-weight: 600;
    }
    .qcs-page-head {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 1rem 1.25rem;
        margin-bottom: .75rem;
        align-items: center;
    }
    .qcs-page-title {
        font-size: 1.02rem;
        letter-spacing: -.01em;
    }
    .qcs-page-meta {
        gap: .45rem;
        margin-top: .25rem;
    }
    .qcs-meta-item {
        font-size: .8rem;
        font-weight: 500;
    }
    .qcs-badge,
    .qcs-bundle-pill {
        border-radius: 999px;
        letter-spacing: 0;
        text-transform: none;
    }
    .qcs-link-chip,
    .qcs-btn-save,
    .qcs-btn-cancel {
        border-radius: 999px;
        font-family: inherit;
        letter-spacing: 0;
        box-shadow: none;
    }
    .qcs-link-chip {
        height: 32px;
        background: transparent;
        color: var(--gf-mid);
        border: 1px solid var(--gf-line);
    }
    .qcs-link-chip:hover {
        background: rgba(148,163,184,.08);
        color: var(--gf-ink);
        opacity: 1;
    }
    .qcs-section {
        border-radius: 14px;
        box-shadow: none;
        padding: 1rem 1.25rem;
        margin-bottom: .75rem;
    }
    .qcs-section-title {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .04em;
        margin-bottom: .75rem;
    }
    .qcs-field-input,
    .qcs-qty-input,
    .qcs-reason-input,
    .qcs-mobile-input,
    .qcs-mobile-reason-input {
        border-radius: 8px;
        border-width: 1px;
        box-shadow: none !important;
        font-family: inherit;
    }
    .qcs-qty-input,
    .qcs-mobile-input {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
    }
    .qcs-table thead th {
        font-size: .78rem;
        font-weight: 600;
        letter-spacing: .04em;
        padding: .5rem .65rem;
        background: transparent;
    }
    .qcs-table tbody td {
        padding: .5rem .65rem;
    }
    .qcs-table tbody tr:hover {
        background: rgba(148,163,184,.04);
    }
    .qcs-bundle-pill {
        height: 22px;
        background: var(--card);
        color: var(--gf-mid);
        border: 1px solid rgba(148,163,184,.3);
    }
    .qcs-item-name {
        font-size: .82rem;
        font-weight: 600;
    }
    .qcs-summary {
        margin-top: .75rem;
        padding: .75rem 1rem;
        border-radius: 14px;
        background: transparent;
        border: 1px solid var(--gf-line);
    }
    .qcs-action-bar {
        background: var(--card);
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
        box-shadow: none;
    }
    .qcs-action-inner {
        max-width: 1100px;
    }
    .qcs-btn-save {
        height: 40px;
        min-width: 132px;
        background: #16a34a;
    }
    .qcs-btn-cancel {
        height: 40px;
    }
    .qcs-alert {
        border-radius: 14px;
        font-weight: 600;
    }

    /* ── Mobile Cards ────────────────────────────────── */
    .qcs-mobile-cards { display: none; }

    @media (max-width: 767.98px) {
        .qc-sewing-page .hdr {
            align-items: center;
            gap: .4rem;
        }
        .qc-sewing-page .hdr h1 {
            font-size: .95rem;
        }
        .qc-sewing-page .hdr-right {
            width: auto;
            gap: .4rem;
            margin-left: auto;
        }
        .qc-sewing-page .btn-header-link {
            padding: .28rem .58rem;
            font-size: .75rem;
        }
        .qc-sewing-page .status-stepper {
            display: none;
        }
        .qc-sewing-page .status-separator {
            display: none;
        }
        .qc-sewing-page .page-wrap {
            padding: 0 6px 88px;
        }
        .qc-sewing-page > .page-wrap > .card:first-child {
            position: sticky;
            top: 0;
            z-index: 60;
            margin: 0 -6px 6px;
            border-radius: 0;
            border-left: 0;
            border-right: 0;
        }
        .qc-sewing-page .card-section {
            padding: .55rem .65rem;
        }
        .qc-sewing-page .sub,
        .qc-sewing-page .hdr .badge,
        .qc-sewing-page .hdr-right .btn-outline-primary {
            display: none !important;
        }
        .qc-sewing-page .hdr-right .btn-outline-secondary {
            border-color: transparent;
            padding-inline: .25rem;
        }
        .qcs-breadcrumb,
        .qcs-page-head > .qcs-link-chip,
        .qcs-alert-info,
        .qc-sewing-page .alert-info,
        .qcs-action-info {
            display: none !important;
        }
        .qcs-page-head {
            position: sticky;
            top: 0;
            z-index: 50;
            margin: 0 -8px 8px;
            padding: 10px;
            background: var(--gf-white);
            border-bottom: 1px solid var(--gf-line);
        }
        .qcs-page-title {
            font-size: 16px;
            letter-spacing: 0;
        }
        .qcs-page-title code { font-size: .9em; }
        .qcs-page-meta {
            gap: 6px;
            margin-top: 5px;
        }
        .qcs-meta-item { font-size: 11px; }
        .qcs-meta-dot { display: none; }
        .qcs-badge {
            height: 21px;
            padding: 0 7px;
            border-radius: 7px;
            font-size: 9px;
            letter-spacing: 0;
        }

        .qcs-field-grid { grid-template-columns: 1fr; gap: 10px; }
        .qc-sewing-page form > section.card:first-of-type {
            display: none;
        }
        .qc-sewing-page form > section.card.mb-3 {
            margin-bottom: 0 !important;
            border-radius: 10px;
        }
        .qc-sewing-page form > section.card.mb-3 > .card-section {
            display: none;
        }

        .qcs-section {
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 8px;
            box-shadow: none;
        }
        .qcs-section:first-of-type {
            display: none;
        }
        .qcs-section[style*="padding:0"] {
            padding: 0 !important;
        }
        .qcs-section-title {
            margin-bottom: 8px !important;
            font-size: 9px;
            letter-spacing: .04em;
        }
        .qcs-section-title svg { display: none; }

        /* Hide desktop table on mobile */
        .qcs-table-wrap { display: none; }
        .qc-sewing-page section.card > .card-section .badge {
            display: none;
        }

        /* Show mobile cards */
        .qcs-mobile-cards {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 6px !important;
        }

        .qcs-mobile-card {
            border: 1px solid var(--gf-line);
            border-radius: 8px;
            padding: 7px;
            background: var(--gf-white);
        }
        .qcs-mobile-row {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) 54px 64px 64px;
            gap: 6px;
            align-items: end;
        }
        .qcs-mobile-card-item {
            font-family: var(--gf-mono);
            font-size: 14px;
            font-weight: 900;
            color: var(--gf-ink);
            line-height: 1.15;
            min-width: 0;
            overflow-wrap: anywhere;
        }
        .qcs-mobile-card-item small {
            display: none;
        }
        .qcs-mobile-card-qty {
            font-family: var(--gf-mono);
            font-size: 13px;
            font-weight: 900;
            color: var(--gf-ink);
            white-space: nowrap;
            text-align: center;
        }
        .qcs-mobile-card-qty span {
            display: block;
            color: var(--gf-mid);
            font-family: inherit;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: uppercase;
        }
        .qcs-mobile-card-inputs {
            display: contents;
        }
        .qcs-mobile-field-label {
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 0;
            text-transform: uppercase;
            color: var(--gf-mid);
            margin-bottom: 2px;
        }
        .qcs-mobile-input {
            width: 100%;
            height: 34px;
            padding: 0 6px;
            border-radius: 7px;
            font-family: var(--gf-mono);
            font-size: 14px;
            font-weight: 900;
            border: 1px solid var(--gf-line);
            background: var(--gf-soft);
            color: var(--gf-ink);
            text-align: center;
        }
        .qcs-mobile-input:focus {
            outline: none;
            border-color: var(--gf-ink);
            box-shadow: 0 0 0 3px rgba(10,10,10,.06);
        }
        .qcs-mobile-input.is-ok { color: var(--gf-ok); }
        .qcs-mobile-input.is-ok:focus {
            border-color: var(--gf-ok);
            box-shadow: 0 0 0 3px rgba(22,163,74,.10);
        }
        .qcs-mobile-input.is-reject {
            color: var(--gf-rej);
            background: var(--gf-rej-soft);
            border-color: rgba(220,38,38,.22);
            text-align: center;
        }
        .qcs-mobile-input.is-reject:focus {
            border-color: var(--gf-rej);
            box-shadow: 0 0 0 3px rgba(220,38,38,.10);
        }
        .qcs-mobile-reason {
            display: none;
            margin-top: 8px;
        }
        .qcs-mobile-reason.is-visible {
            display: block;
        }
        .qcs-mobile-reason-input {
            width: 100%;
            height: 36px;
            padding: 0 10px;
            border-radius: 10px;
            font-family: var(--gf-font);
            font-size: 12px;
            font-weight: 600;
            border: 1.5px solid var(--gf-line);
            background: var(--gf-soft);
            color: var(--gf-ink);
        }
        .qcs-mobile-reason-input:focus {
            outline: none;
            border-color: var(--gf-warn);
            box-shadow: 0 0 0 3px rgba(245,158,11,.10);
        }
        .qcs-mobile-reason-input::placeholder {
            color: rgba(148,163,184,.6);
            font-weight: 500;
        }

        .qcs-summary {
            display: none;
        }
        .qcs-summary-label { display: none; }
        .qcs-summary-values {
            width: 100%;
            justify-content: space-around;
            gap: 8px;
        }
        .qcs-summary-num {
            font-size: 17px;
        }

        .qcs-action-bar {
            padding: 8px 10px calc(8px + env(safe-area-inset-bottom, 0px));
            box-shadow: none;
        }
        .qcs-action-inner { display: block; }
        .qcs-btn-group { width: 100%; }
        .qcs-btn-save {
            width: 100%;
            height: 46px;
            border-radius: 10px;
            box-shadow: none;
        }
        .qcs-btn-save svg { display: none; }
        .qcs-btn-cancel { display: none; }
    }
</style>
@endpush

@section('content')
@php
    $statusLabel = $hasQcSewing ? 'QC JAHIT SELESAI' : 'BELUM QC';
    $statusClass = $hasQcSewing ? 'success' : 'warning';
@endphp
<div class="qc-sewing-page">
<div class="page-wrap">

    {{-- HEADER --}}
    <div class="card mb-3">
        <div class="card-section">
            <div class="hdr">
                <div>
                    <div class="d-flex align-items-center gap-2 w-100">
                        <h1>{{ $sewingReturn->code }}</h1>
                        <span class="badge bg-{{ $statusClass }} d-md-none"
                              style="font-size:.68rem;white-space:normal;line-height:1.3;max-width:110px;text-align:center;margin-left:auto">
                            {{ $statusLabel }}
                        </span>
                    </div>
                    <div class="sub">
                        QC Jahit
                        @if($sewingReturn->date) • {{ $sewingReturn->date->format('d/m/Y') }} @endif
                        @if($sewingReturn->operator) • {{ $sewingReturn->operator->name }} @endif
                    </div>
                </div>
                <div class="hdr-right">
                    <span class="badge bg-{{ $statusClass }} d-none d-md-inline-flex">{{ $statusLabel }}</span>
                    <a href="{{ route('production.qc.index', ['stage' => 'sewing']) }}"
                       class="btn btn-sm btn-outline-secondary btn-header-link">Kembali</a>
                    <a href="{{ route('production.sewing.returns.show', $sewingReturn) }}"
                       class="btn btn-sm btn-outline-primary btn-header-link">Lihat Setor Jahit</a>
                </div>
            </div>

            <div class="status-stepper">
                <div class="status-step">
                    <div class="status-dot active"></div>
                    <div class="status-label done">Setor Jahit</div>
                </div>
                <div class="status-separator"></div>
                <div class="status-step">
                    <div class="status-dot current"></div>
                    <div class="status-label current">Input QC</div>
                </div>
                <div class="status-separator"></div>
                <div class="status-step">
                    <div class="status-dot {{ $hasQcSewing ? 'active' : '' }}"></div>
                    <div class="status-label {{ $hasQcSewing ? 'done' : '' }}">QC Jahit</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ALERTS --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($hasQcSewing)
        <div class="alert alert-info">
            QC Jahit untuk Setor Jahit ini sudah pernah diinput. Simpan ulang akan <strong>menimpa</strong> hasil QC sebelumnya dan membuat mutasi stok koreksi.
        </div>
    @endif

    @if(empty($rows))
        <div class="card p-3">
            <div class="qcs-empty">Tidak ada bundle yang bisa di-QC pada Setor Jahit ini.</div>
        </div>
    @else

    <form method="POST" action="{{ route('production.qc.sewing.update', $sewingReturn) }}">
        @csrf
        @method('PUT')

        {{-- INFO QC --}}
        <section class="card p-3 mb-3">
            <div class="d-flex gap-4 flex-wrap align-items-start">
                <div>
                    <div class="help mb-1">Tanggal QC</div>
                    <input type="date"
                           name="qc_date"
                           value="{{ old('qc_date', now()->toDateString()) }}"
                           required
                           class="qcs-field-input">
                </div>
                <div>
                    <div class="help mb-1">Operator QC</div>
                    <input type="hidden" name="operator_id" value="{{ $loginOperator?->id }}">
                    <div class="mono" style="padding-top:.55rem">
                        {{ $loginOperator?->name ?? '(Operator tidak ditemukan)' }}
                    </div>
                </div>
                <div>
                    <div class="help mb-1">Bundle</div>
                    <div class="mono" style="padding-top:.55rem">{{ count($rows) }} bundle</div>
                </div>
            </div>
        </section>

        {{-- TABEL BUNDLE --}}
        <section class="card mb-3" style="overflow:hidden;">
            <div class="card-section pb-2">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <div>
                        <div class="fw-semibold">Hasil QC</div>
                        <div class="small-muted">Fokus item dan qty.</div>
                    </div>
                    <span class="badge bg-secondary">{{ count($rows) }} bundle</span>
                </div>
            </div>

            {{-- DESKTOP TABLE --}}
            <div class="qcs-table-wrap">
                <table class="qcs-table">
                    <thead>
                        <tr>
                            <th style="text-align:left">Bundle</th>
                            <th style="text-align:left">Barang</th>
                            <th style="text-align:right">Masuk</th>
                            <th style="text-align:right;color:var(--gf-ok)">OK</th>
                            <th style="text-align:right;color:var(--gf-rej)">Reject</th>
                            <th style="text-align:left">Alasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $i => $row)
                        <tr>
                            <td>
                                <span class="qcs-bundle-pill">{{ $row['bundle_code'] }}</span>
                                @if($row['cutting_job_code'])
                                    <div class="qcs-cut-ref">{{ $row['cutting_job_code'] }}</div>
                                @endif
                                <input type="hidden"
                                       name="results[{{ $i }}][bundle_id]"
                                       value="{{ $row['bundle_id'] }}">
                            </td>
                            <td>
                                <div class="qcs-item-code">{{ $row['item_code'] }}</div>
                                <div class="qcs-item-name">{{ $row['item_name'] }}</div>
                            </td>
                            <td style="text-align:right">
                                <span class="qcs-qty-display">{{ number_format($row['qty_max'], 0) }}</span>
                            </td>
                            <td style="text-align:right">
                                <input type="number"
                                       name="results[{{ $i }}][qty_ok]"
                                       class="qcs-qty-input is-ok qty-ok"
                                       value="{{ old("results.{$i}.qty_ok", $row['qty_ok']) }}"
                                       min="0"
                                       max="{{ $row['qty_max'] }}"
                                       step="1"
                                       oninput="syncReject(this, {{ $i }}, {{ $row['qty_max'] }})">
                            </td>
                            <td style="text-align:right">
                                <input type="number"
                                       name="results[{{ $i }}][qty_reject]"
                                       class="qcs-qty-input is-reject qty-reject"
                                       id="reject_{{ $i }}"
                                       value="{{ old("results.{$i}.qty_reject", $row['qty_reject']) }}"
                                       min="0"
                                       max="{{ $row['qty_max'] }}"
                                       step="1">
                            </td>
                            <td>
                                <input type="text"
                                       name="results[{{ $i }}][reject_reason]"
                                       class="qcs-reason-input"
                                       value="{{ old("results.{$i}.reject_reason", $row['reject_reason']) }}"
                                       placeholder="opsional"
                                       maxlength="100">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- MOBILE CARDS --}}
            <div class="qcs-mobile-cards" style="padding:12px;">
                @foreach($rows as $i => $row)
                <div class="qcs-mobile-card">
                    <div class="qcs-mobile-row">
                        <div class="qcs-mobile-card-item">
                            {{ $row['item_code'] }}
                        </div>
                        <div class="qcs-mobile-card-qty">
                            <span>Masuk</span>
                            {{ number_format($row['qty_max'], 0) }}
                        </div>

                        <div class="qcs-mobile-card-inputs">
                            <div>
                            <div class="qcs-mobile-field-label">OK</div>
                            <input type="number"
                                   class="qcs-mobile-input is-ok qty-ok"
                                   data-idx="{{ $i }}"
                                   data-max="{{ $row['qty_max'] }}"
                                   data-target="results[{{ $i }}][qty_ok]"
                                   value="{{ old("results.{$i}.qty_ok", $row['qty_ok']) }}"
                                   min="0" max="{{ $row['qty_max'] }}" step="1"
                                   oninput="syncMobile(this)">
                            </div>
                            <div>
                            <div class="qcs-mobile-field-label">Reject</div>
                            <input type="number"
                                   class="qcs-mobile-input is-reject qty-reject"
                                   id="m_reject_{{ $i }}"
                                   data-target="results[{{ $i }}][qty_reject]"
                                   value="{{ old("results.{$i}.qty_reject", $row['qty_reject']) }}"
                                   min="0" max="{{ $row['qty_max'] }}" step="1">
                            </div>
                        </div>
                    </div>
                    <div class="qcs-mobile-reason {{ (float) old("results.{$i}.qty_reject", $row['qty_reject']) > 0 ? 'is-visible' : '' }}">
                        <div class="qcs-mobile-field-label">Alasan Reject</div>
                        <input type="text"
                               class="qcs-mobile-reason-input"
                               data-target="results[{{ $i }}][reject_reason]"
                               value="{{ old("results.{$i}.reject_reason", $row['reject_reason']) }}"
                               placeholder="opsional"
                               maxlength="100">
                    </div>
                </div>
                @endforeach
            </div>

            {{-- SUMMARY --}}
            <div style="padding:0 1rem 1rem;">
                <div class="qcs-summary">
                    <div>
                        <div class="qcs-summary-label">Ringkasan QC</div>
                    </div>
                    <div class="qcs-summary-values">
                        <div class="qcs-summary-stat">
                            <div class="qcs-summary-num is-ok" id="sum-ok">–</div>
                            <div class="qcs-summary-tag">OK</div>
                        </div>
                        <div class="qcs-summary-stat">
                            <div class="qcs-summary-num is-reject" id="sum-reject">–</div>
                            <div class="qcs-summary-tag">Reject</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ACTION BAR --}}
        <div class="qcs-action-bar">
            <div class="qcs-action-inner">
                <div class="qcs-action-info">
                    <div class="qcs-action-label">QC Jahit</div>
                    <div class="qcs-action-hint">WIP-SEW ke WIP-FIN / REJ-SEW</div>
                </div>
                <div class="qcs-btn-group">
                    <a href="{{ route('production.sewing.returns.show', $sewingReturn) }}" class="qcs-btn-cancel">Batal</a>
                    <button type="submit" class="qcs-btn-save">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Simpan QC
                    </button>
                </div>
            </div>
        </div>

    </form>
    @endif

</div>
</div>
@endsection

@push('scripts')
<script>
    /* ── Desktop: sync reject from OK input ───────────── */
    function syncReject(okInput, idx, max) {
        const ok = parseFloat(okInput.value) || 0;
        const rejectField = document.getElementById('reject_' + idx);
        const suggestedReject = Math.max(0, max - ok);
        if (rejectField && rejectField.dataset.manual !== '1') {
            rejectField.value = suggestedReject;
        }
        // sync mobile inputs
        syncToMobile(idx, ok, suggestedReject);
        updateSummary();
    }

    /* ── Mobile: sync reject from OK input ────────────── */
    function syncMobile(okInput) {
        const idx = okInput.dataset.idx;
        const max = parseFloat(okInput.dataset.max) || 0;
        const ok = parseFloat(okInput.value) || 0;
        const rejectField = document.getElementById('m_reject_' + idx);
        const suggestedReject = Math.max(0, max - ok);
        if (rejectField && rejectField.dataset.manual !== '1') {
            rejectField.value = suggestedReject;
        }
        toggleMobileReason(idx);
        // sync desktop inputs
        syncToDesktop(idx, ok, suggestedReject);
        updateSummary();
    }

    /* ── Cross-sync desktop ↔ mobile ──────────────────── */
    function syncToMobile(idx, ok, reject) {
        const mobileOk = document.querySelector('.qcs-mobile-input.is-ok[data-idx="' + idx + '"]');
        const mobileReject = document.getElementById('m_reject_' + idx);
        if (mobileOk) mobileOk.value = ok;
        if (mobileReject && mobileReject.dataset.manual !== '1') mobileReject.value = reject;
        toggleMobileReason(idx);
    }
    function syncToDesktop(idx, ok, reject) {
        const desktopOk = document.querySelectorAll('.qcs-qty-input.is-ok');
        const desktopReject = document.getElementById('reject_' + idx);
        if (desktopOk[idx]) desktopOk[idx].value = ok;
        if (desktopReject && desktopReject.dataset.manual !== '1') desktopReject.value = reject;
    }

    function toggleMobileReason(idx) {
        const rejectField = document.getElementById('m_reject_' + idx);
        const reasonWrap = rejectField?.closest('.qcs-mobile-card')?.querySelector('.qcs-mobile-reason');
        if (!reasonWrap || !rejectField) return;
        const rejectQty = parseFloat(rejectField.value) || 0;
        reasonWrap.classList.toggle('is-visible', rejectQty > 0);
    }

    /* ── Update summary ──────────────────────────────── */
    function updateSummary() {
        let totalOk = 0, totalReject = 0;
        document.querySelectorAll('.qcs-table .qty-ok, .qcs-mobile-cards .qty-ok').forEach(el => {
            // avoid double-counting — only count if visible
            if (el.offsetParent !== null) {
                totalOk += parseFloat(el.value) || 0;
            }
        });
        document.querySelectorAll('.qcs-table .qty-reject, .qcs-mobile-cards .qty-reject').forEach(el => {
            if (el.offsetParent !== null) {
                totalReject += parseFloat(el.value) || 0;
            }
        });
        // fallback: if no visible (unusual), count desktop
        if (totalOk === 0 && totalReject === 0) {
            document.querySelectorAll('.qcs-qty-input.is-ok').forEach(el => {
                totalOk += parseFloat(el.value) || 0;
            });
            document.querySelectorAll('.qcs-qty-input.is-reject').forEach(el => {
                totalReject += parseFloat(el.value) || 0;
            });
        }
        const sumOk = document.getElementById('sum-ok');
        const sumReject = document.getElementById('sum-reject');
        if (sumOk) sumOk.textContent = totalOk.toLocaleString('id-ID');
        if (sumReject) sumReject.textContent = totalReject.toLocaleString('id-ID');
    }

    /* ── Mark reject fields as manual-edited ─────────── */
    document.querySelectorAll('.qty-reject').forEach(el => {
        el.addEventListener('input', () => {
            el.dataset.manual = '1';
            const idx = el.id?.replace('m_reject_', '') ?? null;
            if (idx !== null) toggleMobileReason(idx);
            updateSummary();
        });
    });

    /* ── Mobile → Desktop form sync on submit ────────── */
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[id^="m_reject_"]').forEach(el => {
            toggleMobileReason(el.id.replace('m_reject_', ''));
        });
        updateSummary();

        // On mobile, sync values to hidden desktop form inputs before submit
        const form = document.querySelector('form[method="POST"]');
        if (form) {
            form.addEventListener('submit', function() {
                // Desktop inputs are the "official" ones — update them from mobile if mobile is visible
                const isMobile = window.innerWidth < 768;
                if (isMobile) {
                    document.querySelectorAll('.qcs-mobile-input[data-target]').forEach(mInput => {
                        const target = form.querySelector('[name="' + mInput.dataset.target + '"]');
                        if (target) target.value = mInput.value;
                    });
                    document.querySelectorAll('.qcs-mobile-reason-input[data-target]').forEach(mInput => {
                        const target = form.querySelector('[name="' + mInput.dataset.target + '"]');
                        if (target) target.value = mInput.value;
                    });
                }
            });
        }
    });
</script>
@endpush
