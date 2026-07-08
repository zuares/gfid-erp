{{-- resources/views/production/qc/cutting_edit.blade.php --}}
@extends('layouts.app')

@section('title', 'QC Cutting · ' . $cuttingJob->code)

@push('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800;900&display=swap" rel="stylesheet">
<style>
    /* ═══════════════════════════════════════════════════════
     * QC CUTTING — Greatfit Storefront Design Language
     * Selaras dengan sewing_edit.blade.php
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
        --gf-blue:   #2563eb;
        --gf-blue-soft: rgba(37,99,235,.06);
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
        --gf-blue-soft: rgba(37,99,235,.10);
    }

    .qc-cutting-page {
        font-family: var(--gf-font);
        -webkit-font-smoothing: antialiased;
    }

    .qc-cutting-page .page-wrap {
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
    .qcs-head-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
        flex-wrap: wrap;
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
    .qcs-badge-done, .qcs-badge-success {
        background: var(--gf-ok-soft);
        color: var(--gf-ok);
        border: 1px solid rgba(22,163,74,.18);
    }
    .qcs-badge-pending, .qcs-badge-warning {
        background: var(--gf-warn-soft);
        color: var(--gf-warn);
        border: 1px solid rgba(245,158,11,.18);
    }
    .qcs-badge-info, .qcs-badge-primary {
        background: var(--gf-blue-soft);
        color: var(--gf-blue);
        border: 1px solid rgba(37,99,235,.18);
    }
    .qcs-badge-danger {
        background: var(--gf-rej-soft);
        color: var(--gf-rej);
        border: 1px solid rgba(220,38,38,.18);
    }
    .qcs-badge-secondary {
        background: var(--gf-soft);
        color: var(--gf-mid);
        border: 1px solid var(--gf-line);
    }

    /* ── Link chips ──────────────────────────────────── */
    .qcs-link-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        height: 32px;
        padding: 0 14px;
        border-radius: var(--gf-radius-pill);
        background: var(--gf-ink);
        color: var(--gf-white);
        font-family: var(--gf-font);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .02em;
        text-decoration: none;
        transition: opacity .15s;
        white-space: nowrap;
        flex-shrink: 0;
        border: none;
        cursor: pointer;
    }
    .qcs-link-chip:hover { opacity: .85; }
    body[data-theme="dark"] .qcs-link-chip {
        background: var(--gf-soft);
        color: var(--gf-ink);
        border: 1px solid var(--gf-line);
    }
    .qcs-link-chip-outline {
        background: transparent;
        color: var(--gf-mid);
        border: 1.5px solid var(--gf-line);
    }
    .qcs-link-chip-outline:hover {
        border-color: var(--gf-ink);
        color: var(--gf-ink);
    }
    .qcs-link-chip-danger {
        background: var(--gf-rej-soft);
        color: var(--gf-rej);
        border: 1px solid rgba(220,38,38,.18);
    }
    .qcs-link-chip-danger:hover {
        background: var(--gf-rej);
        color: #fff;
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
    .qcs-section-title-count {
        font-weight: 600;
        letter-spacing: 0;
        text-transform: none;
        font-size: 11px;
    }

    /* ── Status stepper ──────────────────────────────── */
    .qcs-stepper {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 10px;
    }
    .qcs-step {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .qcs-step-dot {
        width: 18px;
        height: 18px;
        border-radius: var(--gf-radius-pill);
        border: 2px solid var(--gf-line);
        background: transparent;
        display: grid;
        place-items: center;
        transition: .15s;
        flex-shrink: 0;
    }
    .qcs-step-dot.is-done {
        background: var(--gf-ok-soft);
        border-color: var(--gf-ok);
    }
    .qcs-step-dot.is-done::after {
        content: '';
        width: 6px; height: 6px;
        border-radius: 50%;
        background: var(--gf-ok);
    }
    .qcs-step-dot.is-current {
        background: var(--gf-blue-soft);
        border-color: var(--gf-blue);
        box-shadow: 0 0 0 3px rgba(37,99,235,.10);
    }
    .qcs-step-dot.is-current::after {
        content: '';
        width: 6px; height: 6px;
        border-radius: 50%;
        background: var(--gf-blue);
    }
    .qcs-step-label {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--gf-mid);
    }
    .qcs-step-label.is-done { color: var(--gf-ok); }
    .qcs-step-label.is-current { color: var(--gf-blue); }
    .qcs-step-sep {
        width: 20px;
        height: 1px;
        background: var(--gf-line);
        flex-shrink: 0;
    }

    /* ── LOT pills ───────────────────────────────────── */
    .qcs-lot-pill {
        display: inline-flex;
        align-items: center;
        height: 22px;
        padding: 0 8px;
        border-radius: 8px;
        font-family: var(--gf-mono);
        font-size: 10px;
        font-weight: 800;
        background: var(--gf-soft);
        color: var(--gf-mid);
        border: 1px solid var(--gf-line);
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
    .qcs-alert-warning {
        background: var(--gf-warn-soft);
        border: 1px solid rgba(245,158,11,.18);
        color: #92400e;
    }
    body[data-theme="dark"] .qcs-alert-warning { color: #fbbf24; }

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
    .qcs-table tbody tr.row-has-reject { background: rgba(220,38,38,.02); }
    .qcs-table tbody tr.row-has-reject:hover { background: rgba(220,38,38,.04); }

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
    .qcs-lot-ref {
        font-size: 10px;
        font-weight: 600;
        color: var(--gf-mid);
        margin-top: 2px;
    }
    .qcs-qty-display {
        font-family: monospace;
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: .02em;
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
    .qcs-qty-input.is-reject {
        color: var(--gf-rej);
    }
    .qcs-qty-input.is-reject:focus {
        border-color: var(--gf-rej);
        box-shadow: 0 0 0 3px rgba(220,38,38,.10);
    }
    .row-has-reject .qcs-qty-input.is-reject {
        border-color: rgba(220,38,38,.5);
        background: rgba(220,38,38,.04);
    }

    .qcs-notes-input {
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
    .qcs-notes-input:focus {
        outline: none;
        border-color: var(--gf-warn);
        box-shadow: 0 0 0 3px rgba(245,158,11,.10);
    }
    .qcs-notes-input::placeholder {
        color: rgba(148,163,184,.6);
        font-weight: 500;
    }

    /* LOT usage input */
    .qcs-lot-input {
        width: 100px;
        height: 36px;
        padding: 0 8px;
        border-radius: 10px;
        text-align: right;
        font-family: var(--gf-mono);
        font-size: 13px;
        font-weight: 800;
        border: 1.5px solid var(--gf-line);
        background: var(--gf-white);
        color: var(--gf-ink);
        transition: border-color .12s, box-shadow .12s;
    }
    .qcs-lot-input:focus {
        outline: none;
        border-color: var(--gf-ink);
        box-shadow: 0 0 0 3px rgba(10,10,10,.06);
    }
    .qcs-lot-input[readonly] {
        background: var(--gf-soft);
        border-color: var(--gf-line);
        color: var(--gf-mid);
        cursor: default;
    }

    /* ── Row action buttons ──────────────────────────── */
    .qcs-row-btn {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        border: 1.5px solid var(--gf-line);
        background: var(--gf-white);
        color: var(--gf-mid);
        display: inline-grid;
        place-items: center;
        font-size: 13px;
        cursor: pointer;
        transition: .12s;
        font-family: var(--gf-font);
    }
    .qcs-row-btn:hover { border-color: var(--gf-ink); color: var(--gf-ink); }
    .qcs-row-btn-ok {
        color: var(--gf-ok);
        border-color: rgba(22,163,74,.25);
    }
    .qcs-row-btn-ok:hover {
        background: var(--gf-ok);
        color: #fff;
        border-color: var(--gf-ok);
    }
    .qcs-row-btn-adjust {
        color: var(--gf-warn);
        border-color: rgba(245,158,11,.25);
    }
    .qcs-row-btn-adjust:hover {
        background: var(--gf-warn);
        color: #fff;
        border-color: var(--gf-warn);
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
    .qcs-summary-stat { text-align: center; }
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

    /* ── Modal (styled over Bootstrap modal) ─────────── */
    .qcs-modal .modal-content {
        border-radius: var(--gf-radius);
        border: 1px solid var(--gf-line);
        background: var(--gf-white);
        box-shadow: 0 24px 48px rgba(0,0,0,.14);
        overflow: hidden;
        font-family: var(--gf-font);
    }
    .qcs-modal .modal-header {
        background: var(--gf-soft);
        border-bottom: 1px solid var(--gf-line);
        padding: 14px 16px;
    }
    .qcs-modal .modal-header .modal-title-main {
        font-size: 14px;
        font-weight: 900;
        color: var(--gf-ink);
    }
    .qcs-modal .modal-header .modal-title-sub {
        font-family: var(--gf-mono);
        font-size: 11px;
        font-weight: 700;
        color: var(--gf-mid);
        margin-top: 2px;
    }
    .qcs-modal .modal-body {
        padding: 16px;
    }
    .qcs-modal .modal-footer {
        border-top: 1px solid var(--gf-line);
        padding: 12px 16px;
        gap: 8px;
    }
    .qcs-modal .qcs-hint-box {
        border: 1px solid var(--gf-line);
        border-radius: 12px;
        padding: 10px 12px;
        background: var(--gf-soft);
        font-size: 12px;
        font-weight: 600;
        color: var(--gf-mid);
        line-height: 1.5;
    }
    .qcs-modal .qcs-hint-box b { color: var(--gf-ink); }
    .qcs-modal .qcs-modal-btn {
        height: 38px;
        padding: 0 16px;
        border-radius: 12px;
        font-family: var(--gf-font);
        font-size: 13px;
        font-weight: 800;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: opacity .15s;
    }
    .qcs-modal .qcs-modal-btn-outline {
        background: transparent;
        color: var(--gf-mid);
        border: 1.5px solid var(--gf-line);
    }
    .qcs-modal .qcs-modal-btn-outline:hover {
        border-color: var(--gf-ink);
        color: var(--gf-ink);
    }
    .qcs-modal .qcs-modal-btn-ok {
        background: var(--gf-ok);
        color: #fff;
    }
    .qcs-modal .qcs-modal-btn-ok:hover { opacity: .88; }
    .qcs-modal .qcs-modal-btn-warn {
        background: var(--gf-warn);
        color: #fff;
    }
    .qcs-modal .qcs-modal-btn-warn:hover { opacity: .88; }
    .qcs-modal .qcs-modal-field {
        width: 100%;
        height: 40px;
        padding: 0 12px;
        border-radius: 12px;
        border: 1.5px solid var(--gf-line);
        background: var(--gf-soft);
        color: var(--gf-ink);
        font-family: var(--gf-font);
        font-size: 14px;
        font-weight: 700;
    }
    .qcs-modal .qcs-modal-field:focus {
        outline: none;
        border-color: var(--gf-ink);
        box-shadow: 0 0 0 3px rgba(10,10,10,.06);
    }
    .qcs-modal .qcs-modal-field::placeholder { color: rgba(148,163,184,.5); font-weight: 500; }

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

    /* ── Warning text ────────────────────────────────── */
    .qcs-warn-text {
        font-size: 12px;
        font-weight: 700;
        color: var(--gf-warn);
        margin-top: 8px;
    }
    .qcs-error-text {
        font-size: 12px;
        font-weight: 700;
        color: var(--gf-rej);
        margin-top: 8px;
    }

    /* ── Owner hint ──────────────────────────────────── */
    .qcs-owner-hint {
        font-size: 12px;
        font-weight: 600;
        color: var(--gf-mid);
        max-width: 520px;
    }
    .qcs-owner-hint b { color: var(--gf-ink); font-weight: 800; }

    /* ── Production detail style override ─────────────── */
    :root {
        --gf-ink: var(--text, #0f172a);
        --gf-mid: var(--muted, #6b7280);
        --gf-line: var(--line, #e5e7eb);
        --gf-soft: rgba(148,163,184,.06);
        --gf-white: var(--card, #fff);
        --gf-accent: #2563eb;
        --gf-accent-soft: rgba(37,99,235,.06);
        --gf-blue: #2563eb;
        --gf-blue-soft: rgba(37,99,235,.06);
        --gf-radius: 14px;
        --gf-font: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .qc-cutting-page {
        font-family: inherit;
    }
    .qc-cutting-page .page-wrap {
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
    .qcs-lot-pill,
    .qcs-bundle-pill {
        border-radius: 999px;
        letter-spacing: 0;
        text-transform: none;
    }
    .qcs-link-chip,
    .qcs-link-chip-outline,
    .qcs-link-chip-danger,
    .qcs-btn-save,
    .qcs-btn-cancel,
    .qcs-modal .qcs-modal-btn {
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
    .qcs-link-chip:hover,
    .qcs-link-chip-outline:hover {
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
    .qcs-step-dot {
        width: 18px;
        height: 18px;
    }
    .qcs-step-dot.is-current {
        box-shadow: none;
    }
    .qcs-field-input,
    .qcs-qty-input,
    .qcs-notes-input,
    .qcs-lot-input,
    .qcs-modal .qcs-modal-field {
        border-radius: 8px;
        border-width: 1px;
        box-shadow: none !important;
        font-family: inherit;
    }
    .qcs-qty-input,
    .qcs-lot-input {
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
    .qcs-table tbody tr:hover,
    .qcs-table tbody tr.row-has-reject:hover {
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
    .qcs-modal .modal-content {
        border-radius: 14px;
        box-shadow: none;
        font-family: inherit;
    }

    /* ── Mobile ──────────────────────────────────────── */
    @media (max-width: 767.98px) {
        .qc-cutting-page .page-wrap {
            padding: 0 8px 96px;
        }
        .qcs-breadcrumb,
        .qcs-stepper,
        .qcs-head-actions,
        .qcs-page-meta + .qcs-page-meta,
        .qcs-alert-info,
        .qcs-action-info {
            display: none !important;
        }
        .qcs-page-head {
            position: sticky;
            top: 0;
            z-index: 50;
            margin: 0 -8px 8px;
            padding: 10px 10px;
            background: var(--gf-white);
            border-bottom: 1px solid var(--gf-line);
        }
        body[data-theme="dark"] .qcs-page-head {
            background: var(--gf-white);
        }
        .qcs-page-title {
            font-size: 16px;
            letter-spacing: 0;
        }
        .qcs-page-title code {
            font-size: .9em;
        }
        .qcs-page-meta {
            gap: 6px;
            margin-top: 5px;
        }
        .qcs-meta-item {
            font-size: 11px;
        }
        .qcs-meta-dot {
            display: none;
        }
        .qcs-badge {
            height: 21px;
            padding: 0 7px;
            border-radius: 7px;
            font-size: 9px;
            letter-spacing: 0;
        }
        .qcs-field-grid { grid-template-columns: 1fr; gap: 10px; }
        .qcs-section {
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 8px;
            box-shadow: none;
        }
        .qcs-section[style*="padding:0"] {
            padding: 0 !important;
        }
        .qcs-section-title {
            margin-bottom: 8px;
            font-size: 9px;
            letter-spacing: .04em;
        }
        .qcs-section-title svg {
            display: none;
        }

        .qcs-hide-mobile { display: none !important; }

        .qcs-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .qcs-table {
            font-size: 12px;
            white-space: normal;
            table-layout: fixed;
        }
        .qcs-table thead th {
            padding: 7px 5px;
            font-size: 8px;
            letter-spacing: .03em;
        }
        .qcs-table tbody td {
            padding: 7px 5px;
        }
        .qcs-table tbody td:nth-child(5) {
            text-align: right !important;
            padding-right: 8px;
        }
        .qcs-table thead th:nth-child(7),
        .qcs-table tbody td:nth-child(7),
        .qcs-table thead th:nth-child(9),
        .qcs-table tbody td:nth-child(9) {
            display: none;
        }
        .qcs-table thead th:nth-child(1) { width: 36px; }
        .qcs-table thead th:nth-child(2) { width: 42px; }
        .qcs-table thead th:nth-child(3) { width: auto; }
        .qcs-table thead th:nth-child(4),
        .qcs-table thead th:nth-child(5) { width: 46px; }
        .qcs-table thead th:nth-child(6) { width: 92px; }
        .qcs-bundle-pill {
            height: 24px;
            padding: 0 7px;
            border-radius: 7px;
            font-size: 10px;
        }
        .qcs-item-code {
            font-size: 11px;
            white-space: normal;
            overflow-wrap: anywhere;
        }
        .qcs-lot-ref {
            display: none;
        }
        .qcs-qty-display {
            font-size: .95rem;
        }
        .qcs-qty-input {
            width: 86px;
            max-width: 100%;
            height: 42px;
            border-radius: 8px;
            font-size: 16px;
            text-align: center;
        }
        .qcs-qty-input.is-reject {
            background: var(--gf-rej-soft);
            border-color: rgba(220,38,38,.22);
        }
        .qcs-row-btn {
            width: 34px;
            height: 34px;
            border-radius: 8px;
        }
        .qcs-lot-input { width: 80px; }

        .qcs-summary {
            margin-top: 8px;
            padding: 10px 12px;
            border-radius: 10px;
            background: transparent;
            gap: 6px;
        }
        .qcs-summary-label {
            display: none;
        }
        .qcs-summary-values {
            width: 100%;
            justify-content: space-around;
            gap: 8px;
        }
        .qcs-summary-num {
            font-size: 17px;
        }

        .qcs-lot-usage-section {
            display: none;
        }

        .qcs-action-bar {
            padding: 8px 10px calc(8px + env(safe-area-inset-bottom, 0px));
            box-shadow: none;
        }
        .qcs-action-inner {
            display: block;
        }
        .qcs-btn-group {
            width: 100%;
        }
        .qcs-btn-save {
            width: 100%;
            height: 46px;
            border-radius: 10px;
            box-shadow: none;
        }
        .qcs-btn-save svg {
            display: none;
        }
        .qcs-btn-cancel { display: none; }

        .qcs-lot-input[readonly] {
            background: var(--gf-soft);
            border-color: var(--gf-line);
            cursor: default;
        }
        
        .qcs-action-bar {
            padding: 10px 12px calc(10px + 72px + env(safe-area-inset-bottom, 0px));
        }
        .page-wrap {
            padding-bottom: calc(140px + env(safe-area-inset-bottom, 0px)) !important;
        }
    }

    /* =========================================================
       SELARAS DENGAN HALAMAN DETAIL CUTTING JOB (show.blade)
       Override append-only, aman: tidak mengubah markup/logika,
       hanya menyamakan look (kartu, header, tabel, input).
       ========================================================= */
    .qc-cutting-page .page-wrap { max-width: 1100px; padding: .75rem .75rem 4.5rem; }

    .qcs-breadcrumb { padding: 12px 0 8px; font-size: 12px; font-weight: 500; }

    /* Header seperti kartu di halaman detail */
    .qcs-page-head {
        background: var(--card);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 1rem 1.25rem;
        margin-bottom: 12px;
        align-items: flex-start;
    }
    .qcs-page-title {
        font-size: 1.02rem; font-weight: 900; letter-spacing: -.01em;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
    }
    .qcs-page-title code { font-weight: 900; font-size: .95em; }
    .qcs-page-meta { gap: 8px; margin-top: 6px; }
    .qcs-meta-item { font-size: .8rem; font-weight: 500; color: var(--muted); }

    /* Badge status soft (mirip bg-* di detail) */
    .qcs-badge {
        height: auto; padding: .18rem .55rem; border-radius: 999px;
        font-size: .66rem; font-weight: 800; letter-spacing: .03em;
    }

    /* Chip aksi header → pill konsisten */
    .qcs-link-chip { height: auto; padding: .34rem .85rem; border-radius: 999px; font-size: .8rem; font-weight: 700; }

    /* Section = kartu radius 14 seperti .card p-3 */
    .qcs-section {
        border-radius: 14px;
        border: 1px solid var(--line);
        padding: 1rem 1.25rem;
        box-shadow: none;
    }
    .qcs-section-title { font-size: .72rem; letter-spacing: .06em; }

    /* Tabel bundle ala .table-sm (lebih ringan, konsisten dengan detail) */
    .qcs-table { font-size: .86rem; }
    .qcs-table thead th {
        font-size: .72rem; font-weight: 600; letter-spacing: .04em;
        background: transparent; color: var(--muted);
        padding: .55rem .6rem; border-bottom: 1px solid var(--line);
        border-radius: 0 !important;
    }
    .qcs-item-code {
        font-family: monospace;
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: .04em;
        color: var(--gf-ink);
        text-align: left;
        display: block;
    }
    .qcs-item-name {
        font-size: .88rem;
        font-weight: 500;
        color: var(--gf-mid);
        line-height: 1.3;
        text-align: left;
        display: block;
    }
    .qcs-table tbody td { padding: .55rem .6rem; }
    .qcs-table tbody tr { border-bottom-color: rgba(148,163,184,.16); }

    /* Field tanggal/operator ala form-control-sm */
    .qcs-field-input {
        height: 32px; border-radius: 8px; border: 1px solid var(--line);
        font-weight: 600; font-size: .9rem; padding: .2rem .5rem; box-shadow: none;
    }
    /* Input OK/Reject sama persis dengan .input-ok / .input-reject di halaman detail:
       form-control-sm, TERPUSAT, .8rem, lebar 68px, bobot normal (bukan mono tebal). */
    .qcs-qty-input {
        width: 68px !important; height: auto; min-height: 31px;
        text-align: center;
        font-size: .8rem; font-weight: 600;
        padding: .2rem .3rem; border-radius: 6px; border: 1px solid var(--line);
        box-shadow: none;
    }
    .qcs-qty-input.is-reject { color: inherit; }
    .qcs-qty-input:focus,
    .qcs-field-input:focus {
        border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,.15);
    }
    .qcs-qty-input.is-reject:focus { border-color: #dc2626; box-shadow: 0 0 0 2px rgba(220,38,38,.15); }

    /* Kode item jadi focal point (seperti di halaman detail): besar, gelap, tebal.
       Nama item turun jadi teks sekunder muted. */
    .qcs-item-code {
        font-size: .92rem; font-weight: 800; letter-spacing: .01em;
        color: var(--gf-ink);
    }
    .qcs-item-name {
        font-size: .78rem; font-weight: 500; color: var(--gf-mid);
    }

    @media (max-width: 767.98px) {
        .qc-cutting-page .page-wrap { padding-top: 0; }
        .qcs-item-code { font-size: .9rem; }
        /* Header tetap ringkas & menempel; kartu dipertahankan tapi rapat */
        .qcs-page-head { padding: .7rem .85rem !important; border-radius: 12px; margin: 0 0 10px !important; }
        .qcs-page-title { font-size: 1rem; }
        .qcs-section { padding: .7rem .85rem; border-radius: 12px; }
        .qcs-qty-input { width: 64px !important; min-height: 34px; }
    }
</style>
@endpush

@section('content')
    @php
        $lot = $cuttingJob->lot;
        $warehouse = $cuttingJob->warehouse;
        $jobLots = $cuttingJob->lots ?? collect();

        $defaultOperatorId = old('operator_id', $loginOperator->id ?? null);
        $defaultOperatorLabel = $loginOperator
            ? ($loginOperator->code ?? 'OP') . ' — ' . ($loginOperator->name ?? 'Operator')
            : (auth()->user()?->name ?: 'User login');

        $userRole = auth()->user()->role ?? null;
        $isOwner = $userRole === 'owner';
        $isErrorBag = $errors instanceof \Illuminate\Support\ViewErrorBag;

        $defaultQcDate = old('qc_date', optional($cuttingJob->qc_date ?? ($cuttingJob->date ?? now()))->toDateString());

        $status = $cuttingJob->status;

        $badgeClass = [
            'draft' => 'secondary',
            'cut' => 'primary',
            'qc_ok' => 'success',
            'qc_mixed' => 'warning',
            'qc_reject' => 'danger',
            'sent_to_qc' => 'info',
            'cut_sent_to_qc' => 'info',
            'qc_done' => 'success',
        ][$status] ?? 'secondary';

        $stepCurrent = 1;
        if (in_array($status, ['draft', 'cut', 'cut_sent_to_qc', 'sent_to_qc'], true)) {
            $stepCurrent = 2;
        } elseif (in_array($status, ['qc_ok', 'qc_mixed', 'qc_reject', 'qc_done'])) {
            $stepCurrent = 3;
        }

        $step1State = $stepCurrent >= 1 ? ($stepCurrent === 1 ? 'current' : 'done') : '';
        $step2State = $stepCurrent >= 2 ? ($stepCurrent === 2 ? 'current' : 'done') : '';
        $step3State = $stepCurrent >= 3 ? ($stepCurrent === 3 ? 'current' : 'done') : '';

        // deteksi sudah ada QC
        $hasExistingQc = in_array($cuttingJob->status, ['qc_ok', 'qc_mixed', 'qc_reject', 'qc_done']);
        if (!$hasExistingQc && isset($rows) && is_array($rows)) {
            foreach ($rows as $r) {
                $st = $r['status'] ?? null;
                $ok = (float) ($r['qty_ok'] ?? 0);
                $rej = (float) ($r['qty_reject'] ?? 0);
                if (in_array($st, ['qc_ok', 'qc_mixed', 'qc_reject']) || $ok > 0 || $rej > 0) {
                    $hasExistingQc = true;
                    break;
                }
            }
        }

        $canCancelQc = $isOwner && $hasExistingQc && Route::has('production.qc.cutting.cancel');
        $canAdjustQc =
            $isOwner &&
            $hasExistingQc &&
            in_array($cuttingJob->status, ['qc_done', 'qc_ok', 'qc_mixed', 'qc_reject'], true) &&
            Route::has('production.qc.cutting.bundle_adjust');
    @endphp

    <div class="qc-cutting-page">
        <div class="page-wrap">

            {{-- BREADCRUMB --}}
            <div class="qcs-breadcrumb">
                <a href="{{ route('production.qc.index', ['stage' => 'cutting']) }}">QC</a>
                <span>/</span>
                <span>QC Cutting</span>
            </div>

            {{-- PAGE HEAD --}}
            <div class="qcs-page-head">
                <div>
                    <h1 class="qcs-page-title">
                        QC Cutting — <code>{{ $cuttingJob->code }}</code>
                    </h1>
                    <div class="qcs-page-meta">
                        <span class="qcs-meta-item">LOT {{ $lot?->code ?? '-' }}</span>
                        <span class="qcs-meta-dot"></span>
                        <span class="qcs-meta-item">{{ $lot?->item?->code ?? '-' }}</span>
                        <span class="qcs-meta-dot"></span>
                        <span class="qcs-meta-item">Gudang {{ $warehouse?->code ?? '-' }}</span>
                        <span class="qcs-meta-dot"></span>
                        <span class="qcs-badge qcs-badge-{{ $badgeClass }}">{{ strtoupper($status) }}</span>
                    </div>

                    @if ($jobLots->count() > 0)
                        <div class="qcs-page-meta" style="margin-top:4px;">
                            <span class="qcs-meta-item">LOT dipakai:</span>
                            @foreach ($jobLots as $jl)
                                <span class="qcs-lot-pill">
                                    {{ $jl->lot?->code ?? 'LOT?' }}
                                    ({{ number_format($jl->planned_fabric_qty, 2, ',', '.') }})
                                </span>
                            @endforeach
                        </div>
                    @endif

                    {{-- STEPPER --}}
                    <div class="qcs-stepper">
                        <div class="qcs-step">
                            <div class="qcs-step-dot {{ $step1State === 'current' ? 'is-current' : ($step1State === 'done' ? 'is-done' : '') }}"></div>
                            <div class="qcs-step-label {{ $step1State === 'current' ? 'is-current' : ($step1State === 'done' ? 'is-done' : '') }}">Cutting</div>
                        </div>
                        <div class="qcs-step-sep"></div>
                        <div class="qcs-step">
                            <div class="qcs-step-dot {{ $step2State === 'current' ? 'is-current' : ($step2State === 'done' ? 'is-done' : '') }}"></div>
                            <div class="qcs-step-label {{ $step2State === 'current' ? 'is-current' : ($step2State === 'done' ? 'is-done' : '') }}">Input QC</div>
                        </div>
                        <div class="qcs-step-sep"></div>
                        <div class="qcs-step">
                            <div class="qcs-step-dot {{ $step3State === 'current' ? 'is-current' : ($step3State === 'done' ? 'is-done' : '') }}"></div>
                            <div class="qcs-step-label {{ $step3State === 'current' ? 'is-current' : ($step3State === 'done' ? 'is-done' : '') }}">Hasil QC</div>
                        </div>
                    </div>
                </div>

                {{-- HEAD ACTIONS --}}
                <div class="qcs-head-actions">
                    <a href="{{ route('production.cutting_jobs.show', $cuttingJob) }}" class="qcs-link-chip">
                        Lihat Job
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7"/><path d="M9 7h8v8"/></svg>
                    </a>
                    @if ($canCancelQc)
                        <form action="{{ route('production.qc.cutting.cancel', $cuttingJob) }}" method="post"
                            onsubmit="return confirm('Batalkan QC Cutting? Sistem akan reversal mutasi QC dan QC harus diinput ulang.')">
                            @csrf
                            <button type="submit" class="qcs-link-chip qcs-link-chip-danger">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                Batalkan QC
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- ALERTS --}}
            @if ($hasExistingQc && !$isOwner)
                <div class="qcs-alert qcs-alert-info">
                    QC sudah tersimpan. Jika ada salah input setelah QC done, minta <strong>OWNER</strong> untuk <strong>Batalkan QC</strong> lalu input QC ulang.
                </div>
            @endif
            @if ($hasExistingQc && $isOwner)
                <div class="qcs-alert qcs-alert-info">
                    QC sudah tersimpan. Sebagai Owner, kamu bisa <strong>Batalkan QC</strong> atau <strong>Adjust</strong> per bundle.
                </div>
            @endif

            {{-- =========================
                 FORM QC NORMAL (PUT)
                 ========================= --}}
            <form action="{{ route('production.qc.cutting.update', $cuttingJob) }}" method="post">
                @csrf
                @method('PUT')

                @if (in_array($userRole, ['operating', 'produksi'], true))
                    <input type="hidden" name="qc_date" value="{{ $defaultQcDate }}">
                    <input type="hidden" name="operator_id" value="{{ $defaultOperatorId }}">
                    <input type="hidden" name="notes_global" value="{{ old('notes_global') }}">
                @endif

                {{-- HEADER QC: Date & Operator --}}
                @if (!in_array($userRole, ['operating', 'produksi'], true))
                    @php
                        $qcDateError  = $isErrorBag ? $errors->first('qc_date') : null;
                        $operatorError = $isErrorBag ? $errors->first('operator_id') : null;
                    @endphp
                    <section class="qcs-section">
                        <div class="qcs-section-title">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            Info QC
                        </div>
                        <div class="qcs-field-grid">
                            <div>
                                <label class="qcs-field-label">Tanggal QC</label>
                                <input type="date" name="qc_date" value="{{ $defaultQcDate }}"
                                    class="qcs-field-input {{ $qcDateError ? 'is-invalid' : '' }}">
                                @if ($qcDateError)
                                    <div class="qcs-error-text">{{ $qcDateError }}</div>
                                @endif
                            </div>
                            <div>
                                <label class="qcs-field-label">Operator QC</label>
                                <input type="hidden" name="operator_id" value="{{ $defaultOperatorId }}">
                                <div class="qcs-field-static">{{ $defaultOperatorLabel }}</div>
                            </div>
                        </div>
                    </section>
                @endif

                {{-- QC per Bundle --}}
                <section class="qcs-section" style="padding:0;overflow:hidden;">
                    <div style="padding:16px 16px 0;">
                        <div class="qcs-section-title" style="margin-bottom:0;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                            QC per Bundle
                            <span class="qcs-section-title-count">({{ count($rows) }} bundle)</span>
                        </div>

                        @if ($canAdjustQc)
                            <div class="qcs-owner-hint qcs-hide-mobile" style="margin:6px 0 12px;">
                                Owner dapat <b>Adjust</b> per bundle (audit trail). Adjustment ditolak jika WIP sudah kepakai sewing.
                            </div>
                        @endif
                    </div>

                    <div class="qcs-table-wrap">
                        @php $hasAnyReject = false; @endphp

                        <table class="qcs-table">
                            <thead>
                                <tr>
                                    <th style="width:36px; text-align:center;"><input type="checkbox" id="checkAllBundles" class="form-check-input" style="cursor:pointer"></th>
                                    <th style="text-align:left">No</th>
                                    <th style="text-align:left">Item</th>
                                    <th style="text-align:right">Cut</th>
                                    <th style="text-align:right">OK</th>
                                    <th style="text-align:center;color:var(--gf-rej)">Reject</th>
                                    <th></th>
                                    <th class="qcs-hide-mobile" style="text-align:left">Catatan</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($rows as $i => $row)
                                    @php
                                        $bundleId = (int) $row['cutting_job_bundle_id'];
                                        $bundleQty = (float) $row['qty_pcs'];

                                        $qtyOkExisting = (float) ($row['qty_ok'] ?? 0);
                                        $qtyRejectExisting = (float) ($row['qty_reject'] ?? 0);

                                        // QC normal: input reject, OK dihitung otomatis
                                        $qtyRejectOld = old("results.$i.qty_reject", $qtyRejectExisting);
                                        $qtyReject = (float) $qtyRejectOld;
                                        if ($qtyReject < 0) {
                                            $qtyReject = 0;
                                        }
                                        if ($qtyReject > $bundleQty) {
                                            $qtyReject = $bundleQty;
                                        }
                                        $qtyOkCalc = max($bundleQty - $qtyReject, 0);

                                        if ($qtyReject > 0) {
                                            $hasAnyReject = true;
                                        }

                                        $fieldReject = "results.$i.qty_reject";
                                        $fieldReason = "results.$i.reject_reason";
                                        $fieldNotes = "results.$i.notes";

                                        $rejectError = $isErrorBag ? $errors->first($fieldReject) : null;
                                        $reasonError = $isErrorBag ? $errors->first($fieldReason) : null;
                                        $notesError = $isErrorBag ? $errors->first($fieldNotes) : null;

                                        $st = $row['status'] ?: 'cut';
                                        $stBadge = [
                                            'cut' => 'secondary',
                                            'qc_ok' => 'success',
                                            'qc_reject' => 'danger',
                                            'qc_mixed' => 'warning',
                                            'qc_done' => 'success',
                                        ][$st] ?? 'secondary';

                                        $modalId = 'qcAdjustModal_' . $bundleId;
                                    @endphp

                                    <tr class="{{ $qtyReject > 0 ? 'row-has-reject' : '' }}" data-bundle-id="{{ $bundleId }}">
                                        <td style="text-align:center; vertical-align:middle;">
                                            <input type="hidden" name="results[{{ $i }}][cutting_job_bundle_id]"
                                                value="{{ $bundleId }}">
                                            <input type="hidden" name="results[{{ $i }}][qty_ok]"
                                                class="input-ok-hidden" value="{{ old("results.$i.qty_ok", $qtyOkCalc) }}">
                                            <input type="checkbox" class="form-check-input bundle-check" style="cursor:pointer">
                                        </td>
                                        <td>
                                            <span class="qcs-bundle-pill">#{{ $i + 1 }}</span>
                                            <div class="qcs-lot-ref qcs-hide-mobile">
                                                Bundle #{{ $row['bundle_no'] ?? '-' }}
                                                {{ $row['bundle_code'] ? '· ' . $row['bundle_code'] : '' }}
                                            </div>
                                        </td>

                                        <td>
                                            <div class="qcs-item-code">{{ $row['item_code'] }}</div>
                                            <div class="qcs-item-name qcs-hide-mobile">{{ $row['item_name'] ?? '' }}</div>
                                            @if (!empty($row['lot_code']))
                                                <div class="qcs-lot-ref">{{ $row['lot_code'] }}</div>
                                            @endif
                                        </td>

                                        <td style="text-align:right">
                                            <span class="qcs-qty-display">{{ number_format($bundleQty, 0, ',', '.') }}</span>
                                        </td>

                                        <td style="text-align:right">
                                            <span class="qcs-qty-display cell-ok" style="color:var(--gf-ok)">{{ number_format(old("results.$i.qty_ok", $qtyOkCalc), 0, ',', '.') }}</span>
                                        </td>

                                        <td style="text-align:center">
                                            <input type="number" step="1" min="0" inputmode="numeric"
                                                pattern="\d*" name="results[{{ $i }}][qty_reject]"
                                                class="qcs-qty-input is-reject input-reject {{ $rejectError ? 'is-invalid' : '' }}"
                                                value="{{ old("results.$i.qty_reject", $qtyReject) }}"
                                                data-bundle="{{ $bundleQty }}"
                                                onfocus="this.select()">
                                            @if ($rejectError)
                                                <div class="qcs-error-text" style="font-size:10px;">{{ $rejectError }}</div>
                                            @endif
                                        </td>

                                        <td>
                                            <span class="qcs-badge qcs-badge-{{ $stBadge }}" style="font-size:8px;height:20px;padding:0 7px;">{{ $st }}</span>
                                        </td>

                                        <td class="qcs-hide-mobile">
                                            <input type="text" name="results[{{ $i }}][notes]"
                                                class="qcs-notes-input {{ $notesError ? 'is-invalid' : '' }}"
                                                value="{{ old("results.$i.notes", $row['notes'] ?? '') }}"
                                                placeholder="catatan"
                                                maxlength="200">
                                            @if ($notesError)
                                                <div class="qcs-error-text" style="font-size:10px;">{{ $notesError }}</div>
                                            @endif
                                        </td>

                                        {{-- AKSI: Adjust (owner) --}}
                                        <td>
                                            <div style="display:flex;gap:4px;justify-content:flex-end;align-items:center;">
                                                @if ($canAdjustQc)
                                                <button type="button" class="qcs-row-btn qcs-row-btn-adjust"
                                                    title="Adjust"
                                                    data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                                    ✏
                                                </button>

                                                {{-- Adjust Modal --}}
                                                <div class="modal fade qcs-modal" id="{{ $modalId }}" tabindex="-1"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <div>
                                                                    <div class="modal-title-main">QC Adjust · Bundle #{{ $row['bundle_no'] ?? '-' }}</div>
                                                                    <div class="modal-title-sub">
                                                                        {{ $row['bundle_code'] ?? '' }} · Cut
                                                                        {{ number_format($bundleQty, 0, ',', '.') }} pcs
                                                                    </div>
                                                                </div>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>

                                                            <form
                                                                action="{{ route('production.qc.cutting.bundle_adjust', [$cuttingJob, $bundleId]) }}"
                                                                method="post" class="qc-adjust-form">
                                                                @csrf

                                                                <div class="modal-body">
                                                                    <div class="qcs-hint-box" style="margin-bottom:14px;">
                                                                        Aturan: <b>OK + Reject ≤ Cut</b>.
                                                                        Adjustment ini untuk koreksi QC yang sudah
                                                                        terlanjur <b>qc_done</b>.
                                                                        Sistem idealnya <b>menolak</b> jika WIP sudah
                                                                        kepakai sewing.
                                                                    </div>

                                                                    <input type="hidden" name="qc_date"
                                                                        value="{{ $defaultQcDate }}">

                                                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                                                        <div>
                                                                            <label class="qcs-field-label">Qty OK</label>
                                                                            <input type="number"
                                                                                class="qcs-modal-field input-adjust-ok"
                                                                                name="qty_ok" min="0"
                                                                                step="1" inputmode="numeric"
                                                                                value="{{ (int) $qtyOkExisting }}"
                                                                                data-max="{{ $bundleQty }}">
                                                                            <div style="font-size:10px;color:var(--gf-mid);font-weight:600;margin-top:4px;">Default dari QC terakhir.</div>
                                                                        </div>

                                                                        <div>
                                                                            <label class="qcs-field-label">Qty Reject</label>
                                                                            <input type="number"
                                                                                class="qcs-modal-field input-adjust-reject"
                                                                                name="qty_reject" min="0"
                                                                                step="1" inputmode="numeric"
                                                                                value="{{ (int) $qtyRejectExisting }}"
                                                                                data-max="{{ $bundleQty }}">
                                                                            <div style="font-size:10px;color:var(--gf-mid);font-weight:600;margin-top:4px;">Sisa dari Cut.</div>
                                                                        </div>
                                                                    </div>

                                                                    <div style="margin-top:14px;">
                                                                        <label class="qcs-field-label">Catatan Adjust (opsional)</label>
                                                                        <input type="text" class="qcs-modal-field"
                                                                            name="notes"
                                                                            placeholder="mis: salah input qty OK"
                                                                            value="">
                                                                    </div>

                                                                    <div class="qcs-warn-text qc-adjust-warning"
                                                                        style="display:none;">
                                                                        ⚠️ Nilai OK+Reject melebihi Cut. Sistem akan mengunci ke batas maksimum.
                                                                    </div>
                                                                </div>

                                                                <div class="modal-footer">
                                                                    <button type="button"
                                                                        class="qcs-modal-btn qcs-modal-btn-outline"
                                                                        data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit" class="qcs-modal-btn qcs-modal-btn-warn"
                                                                        onclick="return confirm('Simpan QC Adjust untuk bundle ini? Pastikan WIP belum kepakai sewing.')">
                                                                        Simpan Adjust
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @php $resultsError = $isErrorBag ? $errors->first('results') : null; @endphp

                    {{-- Warnings --}}
                    <div style="padding:0 16px;">
                        @if ($resultsError)
                            <div class="qcs-error-text">{{ $resultsError }}</div>
                        @endif

                        <div id="qc-warning" class="qcs-warn-text" style="display:none;">
                            ⚠️ Qty Reject tidak boleh melebihi Qty Cutting. Nilai otomatis dikunci ke batas maksimum.
                        </div>

                        @if ($hasAnyReject)
                            <div class="qcs-warn-text">
                                ⚠️ Terdapat bundle dengan reject. Pastikan alasan reject sudah terisi dengan jelas.
                            </div>
                        @endif
                    </div>

                    {{-- SUMMARY --}}
                    <div style="padding:0 16px 16px;">
                        <div class="qcs-summary">
                            <div>
                                <div class="qcs-summary-label">Ringkasan QC</div>
                            </div>
                            <div class="qcs-summary-values">
                                <div class="qcs-summary-stat">
                                    <div class="qcs-summary-num is-ok" id="sum-ok">0</div>
                                    <div class="qcs-summary-tag">OK</div>
                                </div>
                                <div class="qcs-summary-stat">
                                    <div class="qcs-summary-num is-reject" id="sum-reject">0</div>
                                    <div class="qcs-summary-tag">Reject</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- MULTI-LOT --}}
                @if ($jobLots->count() > 0)
                    <section class="qcs-section qcs-lot-usage-section" style="padding:0;overflow:hidden;">
                        <div style="padding:16px 16px 0;">
                            <div class="qcs-section-title" style="margin-bottom:0;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                                Pemakaian Kain per LOT
                            </div>
                        </div>

                        <div class="qcs-table-wrap">
                            <table class="qcs-table">
                                <thead>
                                    <tr>
                                        <th style="text-align:left;width:150px;">LOT</th>
                                        <th class="qcs-hide-mobile" style="text-align:left">Item</th>
                                        <th style="text-align:right;width:130px;">Rencana</th>
                                        <th style="text-align:right;width:150px;">Dipakai (QC)</th>
                                        <th class="qcs-hide-mobile" style="text-align:right;width:130px;">Est. Sisa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($jobLots as $j => $jobLot)
                                        @php
                                            $lotModel = $jobLot->lot;
                                            $planned = (float) $jobLot->planned_fabric_qty;

                                            $usedOld = old(
                                                "lots.$j.used_fabric_qty",
                                                $jobLot->used_fabric_qty ?: $planned,
                                            );
                                            $used = (float) $usedOld;
                                            if ($used < 0) {
                                                $used = 0;
                                            }
                                            if ($planned > 0 && $used > $planned) {
                                                $used = $planned;
                                            }
                                            $balance = $planned - $used;

                                            $fieldUsed = "lots.$j.used_fabric_qty";
                                            $usedError = $isErrorBag ? $errors->first($fieldUsed) : null;
                                        @endphp

                                        <tr>
                                            <input type="hidden" name="lots[{{ $j }}][id]"
                                                value="{{ $jobLot->id }}">

                                            <td>
                                                <div class="qcs-item-code qcs-hide-mobile">{{ $lotModel?->code ?? 'LOT ?' }}</div>
                                                <div style="display:none;" class="qcs-item-name" id="lot-mobile-{{ $j }}">{{ $lotModel?->item?->name ?? '-' }}</div>
                                                <div class="qcs-lot-ref qcs-hide-mobile">{{ $lotModel?->item?->code ?? '-' }}</div>
                                                {{-- Mobile --}}
                                                <div class="qcs-item-name" style="display:none;" id="lot-mobile-name-{{ $j }}">{{ $lotModel?->item?->name ?? '-' }}</div>
                                                <div class="qcs-lot-ref" style="font-size:10px;">
                                                    <span class="qcs-hide-mobile" style="display:none;">{{ $lotModel?->code ?? '-' }} · {{ $lotModel?->item?->code ?? '-' }}</span>
                                                </div>
                                            </td>

                                            <td class="qcs-hide-mobile">
                                                <div class="qcs-item-name">{{ $lotModel?->item?->name ?? '-' }}</div>
                                                <div class="qcs-lot-ref">Gudang {{ $warehouse?->code ?? '-' }}</div>
                                            </td>

                                            <td style="text-align:right">
                                                <span class="qcs-qty-display" style="font-size:13px;">{{ number_format($planned, 2, ',', '.') }}</span>
                                            </td>

                                            <td style="text-align:right">
                                                <x-number-input name="lots[{{ $j }}][used_fabric_qty]"
                                                    mode="decimal" :value="$used" decimals="2" min="0"
                                                    class="qcs-lot-input input-lot-used {{ $usedError ? 'is-invalid' : '' }}"
                                                    data-planned="{{ $planned }}" />
                                                @if ($usedError)
                                                    <div class="qcs-error-text" style="font-size:10px;">{{ $usedError }}</div>
                                                @endif
                                            </td>

                                            <td class="qcs-hide-mobile" style="text-align:right">
                                                <span class="qcs-qty-display lot-balance-desktop" style="font-size:13px;">{{ number_format($balance, 2, ',', '.') }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div style="padding:0 16px 12px;">
                            @php $lotsError = $isErrorBag ? $errors->first('lots') : null; @endphp
                            @if ($lotsError)
                                <div class="qcs-error-text">{{ $lotsError }}</div>
                            @endif

                            <div id="lot-warning" class="qcs-warn-text" style="display:none;">
                                ⚠️ Pemakaian per LOT tidak boleh melebihi qty rencana. Nilai otomatis dikunci ke batas maksimum.
                            </div>
                        </div>
                    </section>
                @endif

                {{-- ACTION BAR --}}
                <div class="qcs-action-bar">
                    <div class="qcs-action-inner">
                        <div class="qcs-action-info">
                            <div class="qcs-action-label">QC Cutting</div>
                            <div class="qcs-action-hint">WIP-CUT → WIP-SEW / REJ-CUT</div>
                        </div>
                        <div class="qcs-btn-group">
                            <a href="{{ route('production.cutting_jobs.show', $cuttingJob) }}" class="qcs-btn-cancel">Batal</a>
                            <button type="submit" class="qcs-btn-save">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Simpan QC
                            </button>
                        </div>
                    </div>
                </div>

            </form>



        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputsReject = document.querySelectorAll('.input-reject');
            const sumOkSpan = document.getElementById('sum-ok');
            const sumRejectSpan = document.getElementById('sum-reject');
            const warningEl = document.getElementById('qc-warning');

            function attachSelectAllOnFocus(input) {
                input.addEventListener('focus', function() {
                    setTimeout(() => this.select(), 0);
                });
                input.addEventListener('mouseup', function(e) {
                    e.preventDefault();
                });
            }

            function formatInt(num) {
                return num.toLocaleString('id-ID');
            }

            // ===== QC NORMAL: reject -> ok auto =====
            function recalcTotals() {
                let totalOk = 0;
                let totalReject = 0;
                let anyOver = false;

                inputsReject.forEach(rejInput => {
                    const tr = rejInput.closest('tr');
                    const okHidden = tr.querySelector('.input-ok-hidden');
                    const okCell = tr.querySelector('.cell-ok');
                    const maxBundle = parseFloat(rejInput.dataset.bundle || '0') || 0;

                    let rej = parseFloat(rejInput.value || '0');
                    if (isNaN(rej) || rej < 0) rej = 0;

                    if (rej > maxBundle) {
                        rej = maxBundle;
                        anyOver = true;
                        rejInput.value = rej;
                    }

                    const ok = maxBundle - rej;

                    if (okHidden) okHidden.value = ok;
                    if (okCell) okCell.textContent = formatInt(Math.round(ok));

                    totalOk += ok;
                    totalReject += rej;

                    if (rej > 0) tr.classList.add('row-has-reject');
                    else tr.classList.remove('row-has-reject');
                });

                const okInt = Math.round(totalOk);
                const rejInt = Math.round(totalReject);

                if (sumOkSpan) sumOkSpan.textContent = formatInt(okInt);
                if (sumRejectSpan) sumRejectSpan.textContent = formatInt(rejInt);

                if (warningEl) warningEl.style.display = anyOver ? 'block' : 'none';
            }

            inputsReject.forEach(i => {
                attachSelectAllOnFocus(i);
                i.addEventListener('input', recalcTotals);
                i.addEventListener('focus', () => {
                    i.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                });
            });
            recalcTotals();

            // ===== MULTI-LOT =====
            const lotInputs = document.querySelectorAll('.input-lot-used');
            const lotWarningEl = document.getElementById('lot-warning');

            function recalcLotBalances() {
                let anyOver = false;

                lotInputs.forEach(input => {
                    const planned = parseFloat(input.dataset.planned || '0') || 0;
                    let used = parseFloat(input.value || '0');
                    if (isNaN(used) || used < 0) used = 0;

                    if (planned > 0 && used > planned) {
                        used = planned;
                        anyOver = true;
                        input.value = used;
                    }

                    const balance = planned - used;
                    const tr = input.closest('tr');
                    if (!tr) return;

                    const balDesktop = tr.querySelector('.lot-balance-desktop');
                    const text = balance.toFixed(2).replace('.', ',');

                    if (balDesktop) balDesktop.textContent = text;
                });

                if (lotWarningEl) lotWarningEl.style.display = anyOver ? 'block' : 'none';
            }

            lotInputs.forEach(input => {
                attachSelectAllOnFocus(input);
                input.addEventListener('input', recalcLotBalances);
            });
            recalcLotBalances();

            // mobile: readonly lot usage
            const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
            if (isMobile) lotInputs.forEach(input => input.readOnly = true);

            // ===== QC ADJUST (owner modal): clamp ok+reject <= cut =====
            document.querySelectorAll('.qc-adjust-form').forEach(form => {
                const okInput = form.querySelector('.input-adjust-ok');
                const rejInput = form.querySelector('.input-adjust-reject');
                const warn = form.querySelector('.qc-adjust-warning');
                if (!okInput || !rejInput) return;

                attachSelectAllOnFocus(okInput);
                attachSelectAllOnFocus(rejInput);

                function clampAdjust() {
                    const max = parseFloat(okInput.dataset.max || '0') || 0;

                    let ok = parseFloat(okInput.value || '0');
                    let rej = parseFloat(rejInput.value || '0');

                    if (isNaN(ok) || ok < 0) ok = 0;
                    if (isNaN(rej) || rej < 0) rej = 0;

                    if (ok > max) ok = max;
                    if (rej > max) rej = max;

                    if (ok + rej > max) {
                        // turunkan reject dulu
                        rej = Math.max(0, max - ok);
                        if (warn) warn.style.display = 'block';
                    } else {
                        if (warn) warn.style.display = 'none';
                    }

                    okInput.value = Math.round(ok);
                    rejInput.value = Math.round(rej);
                }

                okInput.addEventListener('input', clampAdjust);
                rejInput.addEventListener('input', clampAdjust);
                clampAdjust();
            });

            // ===== CHECKBOX LOGIC =====
            const checkAll = document.getElementById('checkAllBundles');
            const bundleChecks = document.querySelectorAll('.bundle-check');
            const btnSave = document.getElementById('btn-bulk-save');
            const bulkForm = btnSave ? btnSave.closest('form') : null;

            function updateSaveBtnState() {
                if (!btnSave) return;
                let allChecked = true;
                bundleChecks.forEach(chk => {
                    if (!chk.checked) allChecked = false;
                });
                
                if (checkAll) {
                    checkAll.checked = allChecked && bundleChecks.length > 0;
                }

                if (bundleChecks.length === 0) {
                    btnSave.disabled = true;
                    btnSave.style.opacity = '0.5';
                    btnSave.style.cursor = 'not-allowed';
                    return;
                }

                if (allChecked) {
                    btnSave.disabled = false;
                    btnSave.style.opacity = '1';
                    btnSave.style.cursor = 'pointer';
                } else {
                    btnSave.disabled = true;
                    btnSave.style.opacity = '0.5';
                    btnSave.style.cursor = 'not-allowed';
                }
            }

            if (checkAll) {
                checkAll.addEventListener('change', function() {
                    const isChecked = this.checked;
                    bundleChecks.forEach(chk => {
                        chk.checked = isChecked;
                    });
                    updateSaveBtnState();
                });
            }

            bundleChecks.forEach(chk => {
                chk.addEventListener('change', updateSaveBtnState);
            });
            updateSaveBtnState();

            // ===== FORM VALIDATION: Reject Notes =====
            if (bulkForm) {
                bulkForm.addEventListener('submit', function(e) {
                    let isValid = true;
                    let firstInvalidInput = null;

                    inputsReject.forEach(rejInput => {
                        const tr = rejInput.closest('tr');
                        const notesInput = tr.querySelector('.qcs-notes-input');
                        const rej = parseFloat(rejInput.value || '0');
                        
                        if (rej > 0) {
                            if (!notesInput || notesInput.value.trim() === '') {
                                isValid = false;
                                notesInput.classList.add('is-invalid');
                                if (!firstInvalidInput) firstInvalidInput = notesInput;
                            } else {
                                notesInput.classList.remove('is-invalid');
                            }
                        } else {
                            if (notesInput) notesInput.classList.remove('is-invalid');
                        }
                    });

                    if (!isValid) {
                        e.preventDefault();
                        alert('Catatan wajib diisi jika terdapat barang Reject.');
                        if (firstInvalidInput) firstInvalidInput.focus();
                    }
                });
            }
        });
    </script>
@endpush
