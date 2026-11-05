@extends('layouts.app')

@section('title', $bom ? 'Master • Edit BOM' : 'Master • Buat BOM')

@push('head')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    /* GF BOM UI FINAL - selaras Master Items */
    .gf-category-page,
    .page-wrap {
        max-width: var(--gf-content-max, 1280px) !important;
        width: 100% !important;
        margin: 0 auto !important;
        padding: 1rem var(--gf-content-gutter, 1.1rem) 4rem !important;
        color: var(--gf-color-content, #101828) !important;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
    }

    .cardx {
        border: 1px solid var(--gf-color-line, #e4e7ec) !important;
        border-radius: var(--gf-radius-card, 16px) !important;
        background: var(--gf-color-surface, #ffffff) !important;
        box-shadow: var(--gf-shadow-lg, 0 16px 42px rgba(15, 23, 42, .07)) !important;
        padding: 16px !important;
        overflow: hidden !important;
    }

    .cardx.gf-category-head,
    .gf-bom-form {
        margin-bottom: 14px !important;
        padding: 18px !important;
        border: 1px solid var(--gf-color-line, #e4e7ec) !important;
        border-radius: var(--gf-radius-card, 16px) !important;
        background: var(--gf-color-surface, #ffffff) !important;
        box-shadow: var(--gf-shadow-sm, 0 10px 28px rgba(15, 23, 42, .05)) !important;
    }

    .cardx.sub {
        margin-top: 18px !important;
        padding: 0 !important;
        background: transparent !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    .rowx {
        display: flex !important;
        gap: 10px !important;
        flex-wrap: wrap !important;
        align-items: center !important;
    }

    .h1 {
        color: var(--gf-color-content, #101828) !important;
        font-size: 1.34rem !important;
        font-weight: 950 !important;
        letter-spacing: -.05em !important;
        line-height: 1.1 !important;
        margin: 0 !important;
    }

    .subt,
    .small {
        color: var(--gf-color-muted, #667085) !important;
        font-size: .82rem !important;
        font-weight: 650 !important;
        line-height: 1.45 !important;
    }

    .mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace !important;
        letter-spacing: -.02em !important;
    }

    .hr {
        height: 1px !important;
        background: var(--gf-color-line, #e4e7ec) !important;
        border: 0 !important;
        margin: 14px 0 !important;
    }

    .lbl {
        font-size: .72rem !important;
        font-weight: 900 !important;
        color: var(--gf-color-content, #101828) !important;
        margin-bottom: 5px !important;
        text-transform: uppercase !important;
        letter-spacing: .045em !important;
    }

    .grid {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 12px !important;
    }

    .inp,
    .form-control,
    .form-select {
        border-radius: var(--gf-radius-control, 10px) !important;
        border: 1px solid var(--gf-color-line, #e4e7ec) !important;
        min-height: 38px !important;
        color: var(--gf-color-content, #101828) !important;
        font-size: .84rem !important;
        font-weight: 650 !important;
        background: var(--gf-color-surface, #ffffff) !important;
        box-shadow: none !important;
    }

    .inp:focus,
    .form-control:focus,
    .form-select:focus {
        border-color: #94a3b8 !important;
        box-shadow: 0 0 0 .22rem rgba(15, 23, 42, .08) !important;
    }

    .btnx,
    .btn {
        border-radius: 999px !important;
        font-weight: 850 !important;
        letter-spacing: -.01em !important;
        min-height: 34px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        text-decoration: none !important;
    }

    .btnx {
        padding: 8px 13px !important;
        border: 1px solid var(--gf-color-line, #e4e7ec) !important;
        background: color-mix(in srgb, var(--gf-color-surface, #fff) 86%, transparent) !important;
        color: var(--gf-color-muted, #667085) !important;
    }

    .btnx:hover {
        background: var(--gf-color-surface-muted, #f2f4f7) !important;
        color: var(--gf-color-content, #101828) !important;
        box-shadow: var(--gf-shadow-sm, 0 10px 22px rgba(15, 23, 42, .08)) !important;
    }

    .btnx > *,
    .btn > * {
        color: inherit !important;
    }

    .btnx:focus-visible,
    .btn:focus-visible,
    .inp:focus-visible,
    .form-control:focus-visible,
    .form-select:focus-visible,
    .select2-container--default.select2-container--focus .select2-selection--single {
        outline: 3px solid color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 24%, transparent) !important;
        outline-offset: 2px !important;
    }

    .btnx:disabled,
    .btn:disabled {
        cursor: not-allowed !important;
        opacity: .58 !important;
        box-shadow: none !important;
    }

    .btnx.primary,
    .btnx.success,
    .btn-primary {
        color: #ffffff !important;
        background: var(--gf-color-brand, #5b5ce2) !important;
        border-color: transparent !important;
        box-shadow: var(--gf-shadow-sm, 0 12px 24px rgba(15, 23, 42, .12)) !important;
    }

    .btnx.primary:hover,
    .btnx.success:hover,
    .btn-primary:hover {
        background: var(--gf-color-brand-strong, #4142bb) !important;
        color: #ffffff !important;
    }

    .btnx.ghost {
        background: var(--gf-color-surface, #ffffff) !important;
        color: var(--gf-color-muted, #667085) !important;
        border-color: var(--gf-color-line, #e4e7ec) !important;
    }

    .btnx.danger,
    .btn-outline-danger {
        color: #991b1b !important;
        background: #fee2e2 !important;
        border-color: #fecaca !important;
    }

    .alert-successx,
    .alert-success {
        margin-top: 14px !important;
        border-radius: 16px !important;
        padding: 10px 12px !important;
        background: #dcfce7 !important;
        color: #166534 !important;
        border: 1px solid #bbf7d0 !important;
        font-size: .82rem !important;
        font-weight: 800 !important;
    }

    .alert,
    .alert-danger {
        border-radius: 16px !important;
        font-size: .82rem !important;
    }

    .table-wrap {
        max-height: 68vh !important;
        overflow: auto !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 18px !important;
        background: #ffffff !important;
    }

    .table-bom,
    .gf-clean-table {
        width: 100% !important;
        min-width: 0 !important;
        margin: 0 !important;
        background: #ffffff !important;
        color: #0f172a !important;
        font-size: .82rem !important;
    }

    .table-bom thead th,
    .gf-clean-table thead th,
    .gf-sticky-table thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 8 !important;
        background: #f8fafc !important;
        color: #64748b !important;
        font-size: .7rem !important;
        text-transform: uppercase !important;
        letter-spacing: .045em !important;
        font-weight: 900 !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 12px 10px !important;
        white-space: nowrap !important;
    }

    .table-bom td,
    .gf-clean-table td {
        border-color: #eef2f7 !important;
        padding: 12px 10px !important;
        vertical-align: middle !important;
        color: #0f172a !important;
    }

    .table-bom tbody tr:hover td,
    .gf-clean-table tbody tr:hover td {
        background: #f8fbff !important;
    }

    .bom-price-cell {
        min-width: 118px;
        white-space: nowrap;
    }
    .bom-price-value {
        display: block;
        font-weight: 850;
        color: #0f172a;
    }
    .bom-price-unit {
        display: block;
        margin-top: 2px;
        color: #64748b;
        font-size: .68rem;
    }
    body[data-theme="dark"] .bom-price-value { color: #f8fafc; }

    .table-bom td:nth-child(3) .inp,
    .table-bom td:nth-child(4) .inp,
    .table-bom td:nth-child(5) .inp,
    .table-bom td:nth-child(6) .inp,
    .table-bom td:nth-child(7) .inp {
        width: 100% !important;
        max-width: 100px !important;
    }

    .badge {
        border-radius: 999px !important;
        padding: 6px 10px !important;
        font-weight: 850 !important;
    }

    .pagination {
        margin: 0 !important;
        gap: 4px !important;
    }

    .pagination .page-link {
        border-radius: 11px !important;
        border-color: #e2e8f0 !important;
        color: #475569 !important;
        font-size: .78rem !important;
        font-weight: 700 !important;
    }

    .pagination .active .page-link,
    .pagination .page-item.active .page-link {
        color: #ffffff !important;
        background: #0f172a !important;
        border-color: #0f172a !important;
    }

    .select2-container {
        width: 100% !important;
    }

    .select2-container .select2-selection--single {
        height: 40px !important;
        border-radius: 14px !important;
        border: 1px solid #e2e8f0 !important;
        display: flex !important;
        align-items: center !important;
        background: #ffffff !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 40px !important;
        color: #0f172a !important;
        font-size: .84rem !important;
        font-weight: 650 !important;
        padding-left: 12px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
        right: 8px !important;
    }

    .select2-dropdown {
        border: 1px solid #e2e8f0 !important;
        border-radius: 14px !important;
        overflow: hidden !important;
        box-shadow: 0 18px 46px rgba(15, 23, 42, .14) !important;
    }

    .select2-results__option {
        font-size: .84rem !important;
        padding: 8px 12px !important;
    }

    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background: #0f172a !important;
        color: #ffffff !important;
    }

    .gf-live-filter-wrap {
        position: relative !important;
    }

    .gf-live-filter-indicator {
        position: absolute !important;
        right: 12px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        display: none !important;
        color: #334155 !important;
        background: rgba(255,255,255,.88) !important;
        padding-left: 8px !important;
        font-size: .72rem !important;
        font-weight: 850 !important;
    }

    .gf-live-filter-indicator.is-show {
        display: inline-flex !important;
    }

    @media (max-width: 992px) {
        .cardx.gf-category-head,
        .gf-bom-form {
            border-radius: 18px !important;
        }

        .grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 768px) {
        .gf-category-page,
        .page-wrap {
            padding: 12px 10px 24px !important;
        }

        .cardx,
        .cardx.gf-category-head,
        .gf-bom-form {
            border-radius: 15px !important;
            padding: 14px !important;
        }

        .rowx {
            align-items: stretch !important;
        }

        .btnx,
        .btn {
            width: auto;
        }

        form.rowx {
            display: grid !important;
            grid-template-columns: 1fr !important;
        }

        form.rowx > div {
            width: 100% !important;
        }

        form.rowx .btnx {
            width: 100% !important;
        }

        .table-wrap {
            max-height: none !important;
            overflow: visible !important;
            border: 0 !important;
            border-radius: 0 !important;
        }

        .table-bom,
        .table-bom tbody,
        .table-bom tbody tr,
        .table-bom tbody td {
            display: block !important;
            width: 100% !important;
        }

        .table-bom thead { display: none !important; }
        .table-bom tbody { display: grid !important; gap: 10px !important; }
        .table-bom tbody tr.line {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 8px !important;
            padding: 11px !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            background: #ffffff !important;
        }
        .table-bom tbody td {
            display: flex !important;
            min-width: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            flex-direction: column !important;
            align-items: stretch !important;
        }
        .table-bom tbody td::before {
            margin-bottom: 3px !important;
            color: #64748b !important;
            font-size: .62rem !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: .04em !important;
        }
        .table-bom tbody td:nth-child(1) { grid-column: 1 / -1 !important; }
        .table-bom tbody td:nth-child(1)::before { content: 'Baris'; }
        .table-bom tbody td:nth-child(2),
        .table-bom tbody td:nth-child(8),
        .table-bom tbody td:nth-child(9),
        .table-bom tbody td:nth-child(10),
        .table-bom tbody td:nth-child(11) { grid-column: 1 / -1 !important; }
        .table-bom tbody td:nth-child(2)::before { content: 'Komponen'; }
        .table-bom tbody td:nth-child(3)::before { content: 'Kelompok'; }
        .table-bom tbody td:nth-child(4)::before { content: 'Qty'; }
        .table-bom tbody td:nth-child(5)::before { content: 'Satuan unit'; }
        .table-bom tbody td:nth-child(6)::before { content: 'Waste'; }
        .table-bom tbody td:nth-child(7)::before { content: 'Optional'; }
        .table-bom tbody td:nth-child(8)::before { content: 'Harga / satuan stok'; }
        .table-bom tbody td:nth-child(9)::before { content: 'Harga / satuan beli'; }
        .table-bom tbody td:nth-child(10)::before { content: 'Biaya baris'; }
        .table-bom tbody td:nth-child(11)::before { content: ''; }
        .table-bom tbody td:nth-child(11) { align-items: flex-end !important; }
        .table-bom tbody td:nth-child(11) .btnx { width: auto !important; }

        .usage-history-wrap { overflow: visible !important; }
        .usage-history-table { min-width: 0 !important; }
        .usage-history-table th:nth-child(3),
        .usage-history-table td:nth-child(3) { display: none !important; }
    }

    @media (min-width: 769px) and (max-width: 1100px) {
        .table-wrap { overflow-x: hidden !important; }
        .table-bom { table-layout: fixed !important; }
        .table-bom th:nth-child(1) { width: 5% !important; }
        .table-bom th:nth-child(2) { width: 28% !important; min-width: 0 !important; }
        .table-bom th:nth-child(3) { width: 17% !important; }
        .table-bom th:nth-child(4) { width: 14% !important; }
        .table-bom th:nth-child(5) { width: 10% !important; }
        .table-bom th:nth-child(6) { width: 10% !important; }
        .table-bom th:nth-child(7) { width: 10% !important; }
        .table-bom th:nth-child(8),
        .table-bom th:nth-child(9),
        .table-bom th:nth-child(10) { width: 10% !important; }
        .table-bom th:nth-child(11) { width: 6% !important; }
    }

    .bom-form-head {
        display: flex !important;
        align-items: flex-start !important;
        justify-content: space-between !important;
        gap: 12px !important;
        padding-bottom: 14px !important;
        border-bottom: 1px solid #e2e8f0 !important;
    }

    .bom-usage-panel {
        margin-top: 14px !important;
        overflow: hidden !important;
        border: 1px solid color-mix(in srgb, var(--gf-color-accent, #13b8c4) 32%, var(--gf-color-line, #e4e7ec)) !important;
        border-radius: var(--gf-radius-card, 16px) !important;
        background: var(--gf-color-surface, #fff) !important;
    }

    .bom-usage-head {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 12px !important;
        padding: 13px 15px !important;
        background: color-mix(in srgb, var(--gf-color-accent, #13b8c4) 8%, var(--gf-color-surface, #fff)) !important;
    }

    .bom-usage-head h3 {
        margin: 0 !important;
        color: color-mix(in srgb, var(--gf-color-accent, #13b8c4) 74%, var(--gf-color-content, #101828)) !important;
        font-size: .82rem !important;
        font-weight: 850 !important;
    }

    .bom-usage-head h3 span { margin-right: 4px !important; }
    .bom-usage-head p { margin: 3px 0 0 !important; color: var(--gf-color-muted, #667085) !important; font-size: .68rem !important; font-weight: 650 !important; }
    .bom-usage-count { flex: 0 0 auto !important; padding: 4px 8px !important; border-radius: 999px !important; color: var(--gf-color-accent, #13b8c4) !important; background: color-mix(in srgb, var(--gf-color-accent, #13b8c4) 12%, var(--gf-color-surface, #fff)) !important; font-size: .65rem !important; font-weight: 850 !important; }
    .bom-usage-list { display: grid !important; }
    .bom-usage-row { display: grid !important; grid-template-columns: minmax(190px, 1.15fr) minmax(115px, .7fr) minmax(300px, 1.65fr) !important; align-items: center !important; gap: 14px !important; padding: 12px 15px !important; border-top: 1px solid color-mix(in srgb, var(--gf-color-line, #e4e7ec) 78%, transparent) !important; }
    .bom-usage-material { min-width: 0 !important; }
    .bom-usage-material strong { display: block !important; overflow: hidden !important; color: var(--gf-color-content, #101828) !important; font-size: .76rem !important; font-weight: 850 !important; text-overflow: ellipsis !important; white-space: nowrap !important; }
    .bom-usage-material span { display: block !important; overflow: hidden !important; margin-top: 3px !important; color: var(--gf-color-muted, #667085) !important; font-size: .68rem !important; font-weight: 650 !important; text-overflow: ellipsis !important; white-space: nowrap !important; }
    .bom-usage-latest span { display: block !important; color: var(--gf-color-muted, #667085) !important; font-size: .62rem !important; font-weight: 750 !important; }
    .bom-usage-latest strong { display: block !important; margin-top: 3px !important; color: color-mix(in srgb, var(--gf-color-accent, #13b8c4) 70%, var(--gf-color-content, #101828)) !important; font-size: .8rem !important; font-weight: 900 !important; }
    .bom-usage-latest small { color: var(--gf-color-muted, #667085) !important; font-size: .63rem !important; font-weight: 700 !important; }
    .bom-usage-actions { display: flex !important; align-items: center !important; justify-content: flex-end !important; gap: 7px !important; flex-wrap: wrap !important; }
    .bom-usage-waste { display: inline-flex !important; align-items: center !important; gap: 4px !important; min-height: 32px !important; padding: 4px 7px !important; border: 1px solid var(--gf-color-line, #e4e7ec) !important; border-radius: var(--gf-radius-control, 10px) !important; color: var(--gf-color-muted, #667085) !important; background: var(--gf-color-surface, #fff) !important; font-size: .66rem !important; font-weight: 750 !important; }
    .bom-usage-waste input { width: 52px !important; min-height: 24px !important; padding: 2px 4px !important; border: 0 !important; border-bottom: 1px solid var(--gf-color-line, #e4e7ec) !important; border-radius: 0 !important; color: var(--gf-color-content, #101828) !important; background: transparent !important; text-align: right !important; font-size: .7rem !important; font-weight: 800 !important; }
    .bom-usage-waste input:focus { outline: 2px solid color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 24%, transparent) !important; outline-offset: 1px !important; }
    .bom-usage-waste b { color: var(--gf-color-muted, #667085) !important; font-size: .64rem !important; }
    .bom-usage-detail { display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 5px !important; min-height: 34px !important; padding: 7px 10px !important; border: 1px solid var(--gf-color-line, #e4e7ec) !important; border-radius: 999px !important; color: var(--gf-color-brand-strong, #4142bb) !important; background: var(--gf-color-surface, #fff) !important; font-size: .68rem !important; font-weight: 800 !important; }
    .bom-usage-detail:hover { border-color: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 34%, var(--gf-color-line, #e4e7ec)) !important; background: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 7%, var(--gf-color-surface, #fff)) !important; }
    .bom-usage-detail:focus-visible { outline: 3px solid color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 22%, transparent) !important; outline-offset: 2px !important; }

    .bom-history-modal { position: fixed !important; inset: 0 !important; z-index: 1200 !important; display: none !important; align-items: center !important; justify-content: center !important; padding: 16px !important; }
    .bom-history-modal.is-open { display: flex !important; }
    .bom-history-backdrop { position: absolute !important; inset: 0 !important; background: rgba(16, 24, 40, .42) !important; backdrop-filter: blur(2px) !important; }
    .bom-history-dialog { position: relative !important; width: min(560px, 100%) !important; max-height: min(680px, calc(100vh - 32px)) !important; overflow: auto !important; border: 1px solid var(--gf-color-line, #e4e7ec) !important; border-radius: var(--gf-radius-card, 16px) !important; background: var(--gf-color-surface, #fff) !important; box-shadow: var(--gf-shadow-lg, 0 24px 56px rgba(48,49,125,.14)) !important; }
    .bom-history-dialog-head { display: flex !important; align-items: flex-start !important; justify-content: space-between !important; gap: 12px !important; padding: 17px 18px 13px !important; border-bottom: 1px solid var(--gf-color-line, #e4e7ec) !important; }
    .bom-history-eyebrow { color: var(--gf-color-brand-strong, #4142bb) !important; font-size: .63rem !important; font-weight: 850 !important; letter-spacing: .08em !important; text-transform: uppercase !important; }
    .bom-history-dialog h3 { margin: 4px 0 0 !important; color: var(--gf-color-content, #101828) !important; font-size: 1rem !important; font-weight: 850 !important; }
    .bom-history-dialog-head p { margin: 4px 0 0 !important; color: var(--gf-color-muted, #667085) !important; font-size: .7rem !important; font-weight: 650 !important; }
    .bom-history-close { display: inline-grid !important; flex: 0 0 32px !important; place-items: center !important; width: 32px !important; height: 32px !important; border: 1px solid var(--gf-color-line, #e4e7ec) !important; border-radius: 50% !important; color: var(--gf-color-muted, #667085) !important; background: var(--gf-color-surface, #fff) !important; }
    .bom-history-close:hover { color: var(--gf-color-content, #101828) !important; background: var(--gf-color-surface-muted, #f2f4f7) !important; }
    .bom-history-close:focus-visible { outline: 3px solid color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 22%, transparent) !important; outline-offset: 2px !important; }
    .bom-history-summary { display: flex !important; align-items: center !important; justify-content: space-between !important; gap: 12px !important; margin: 15px 18px !important; padding: 11px 12px !important; border: 1px solid color-mix(in srgb, var(--gf-color-accent, #13b8c4) 24%, var(--gf-color-line, #e4e7ec)) !important; border-radius: var(--gf-radius-control, 10px) !important; background: color-mix(in srgb, var(--gf-color-accent, #13b8c4) 7%, var(--gf-color-surface, #fff)) !important; }
    .bom-history-summary span { color: var(--gf-color-muted, #667085) !important; font-size: .68rem !important; font-weight: 750 !important; }
    .bom-history-summary strong { color: color-mix(in srgb, var(--gf-color-accent, #13b8c4) 70%, var(--gf-color-content, #101828)) !important; font-size: .86rem !important; font-weight: 900 !important; }
    .bom-history-detail-label { margin: 0 18px 7px !important; color: var(--gf-color-muted, #667085) !important; font-size: .65rem !important; font-weight: 850 !important; letter-spacing: .06em !important; text-transform: uppercase !important; }
    .bom-history-list { display: grid !important; gap: 7px !important; margin: 0 18px !important; padding: 0 !important; list-style: none !important; counter-reset: history-item !important; }
    .bom-history-list li { display: grid !important; grid-template-columns: 24px minmax(0, 1fr) auto !important; align-items: center !important; gap: 9px !important; padding: 10px !important; border: 1px solid var(--gf-color-line, #e4e7ec) !important; border-radius: var(--gf-radius-control, 10px) !important; background: var(--gf-color-surface, #fff) !important; counter-increment: history-item !important; }
    .bom-history-list li::before { content: counter(history-item) !important; display: inline-grid !important; place-items: center !important; width: 22px !important; height: 22px !important; border-radius: 7px !important; color: var(--gf-color-brand-strong, #4142bb) !important; background: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 10%, var(--gf-color-surface, #fff)) !important; font-size: .65rem !important; font-weight: 900 !important; }
    .bom-history-list li strong { color: var(--gf-color-content, #101828) !important; font-size: .72rem !important; font-weight: 850 !important; }
    .bom-history-list li small { display: block !important; margin-top: 2px !important; color: var(--gf-color-muted, #667085) !important; font-size: .63rem !important; font-weight: 650 !important; }
    .bom-history-list li > span { color: var(--gf-color-content, #101828) !important; font-size: .72rem !important; font-weight: 850 !important; font-variant-numeric: tabular-nums !important; }
    .bom-history-empty { grid-template-columns: 1fr !important; color: var(--gf-color-muted, #667085) !important; font-size: .7rem !important; font-style: italic !important; }
    .bom-history-empty::before { display: none !important; }
    .bom-history-dialog-foot { display: flex !important; justify-content: flex-end !important; padding: 13px 18px 17px !important; }

    @media (max-width: 920px) {
        .bom-usage-row { grid-template-columns: minmax(180px, 1fr) minmax(110px, .7fr) !important; }
        .bom-usage-actions { grid-column: 1 / -1 !important; justify-content: flex-start !important; }
    }

    @media (max-width: 520px) {
        .bom-usage-head { align-items: flex-start !important; flex-direction: column !important; }
        .bom-usage-row { grid-template-columns: 1fr !important; gap: 9px !important; }
        .bom-usage-actions { align-items: stretch !important; display: grid !important; grid-template-columns: auto 1fr !important; }
        .bom-usage-waste { justify-content: center !important; }
        .bom-usage-detail,
        .bom-usage-actions .btn-use-material { width: 100% !important; }
        .bom-usage-actions .btn-use-material { grid-column: 1 / -1 !important; }
    }

    @media (prefers-reduced-motion: reduce) {
        .bom-history-dialog { scroll-behavior: auto !important; }
    }

    .bom-form-head .subt { margin-top: 5px !important; }

    .bom-form-context {
        display: inline-flex !important;
        align-items: center !important;
        margin-left: 6px !important;
        padding: 3px 8px !important;
        border: 1px solid color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 24%, var(--gf-color-line, #e4e7ec)) !important;
        border-radius: 999px !important;
        background: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 8%, var(--gf-color-surface, #fff)) !important;
        color: var(--gf-color-brand-strong, #4142bb) !important;
        font-size: .72rem !important;
        font-weight: 800 !important;
        vertical-align: middle !important;
    }

    .bom-lines-toolbar {
        justify-content: flex-end !important;
        align-items: center !important;
        gap: 8px !important;
        margin-top: 12px !important;
    }

    .bom-active-toggle {
        display: inline-flex !important;
        align-items: center !important;
        gap: 7px !important;
        min-height: 34px !important;
        padding: 6px 10px !important;
        border: 1px solid color-mix(in srgb, var(--gf-color-success, #14945a) 30%, var(--gf-color-line, #e4e7ec)) !important;
        border-radius: 999px !important;
        background: color-mix(in srgb, var(--gf-color-success, #14945a) 7%, var(--gf-color-surface, #fff)) !important;
        color: var(--gf-color-success, #14945a) !important;
        cursor: pointer !important;
        font-size: .76rem !important;
        font-weight: 850 !important;
        line-height: 1 !important;
        user-select: none !important;
    }

    .bom-active-toggle input {
        width: 16px !important;
        height: 16px !important;
        margin: 0 !important;
        accent-color: var(--gf-color-success, #14945a) !important;
    }

    .bom-active-toggle:focus-within {
        outline: 3px solid color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 22%, transparent) !important;
        outline-offset: 2px !important;
    }

    @media (max-width: 768px) {
        .bom-form-context {
            display: table !important;
            margin: 6px 0 0 !important;
        }

        .bom-lines-toolbar {
            justify-content: stretch !important;
            flex-wrap: wrap !important;
        }

        .bom-lines-toolbar .btnx,
        .bom-lines-toolbar .bom-active-toggle {
            flex: 1 1 auto !important;
        }
    }

    .bom-overview {
        display: grid !important;
        grid-template-columns: minmax(0, .95fr) minmax(0, 1.35fr) !important;
        gap: 14px !important;
        margin-top: 16px !important;
    }

    .bom-overview:not(.has-estimate) { grid-template-columns: 1fr !important; }

    .bom-identity {
        padding: 14px !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        background: #f8fafc !important;
    }

    .bom-identity .grid { gap: 10px !important; }
    .bom-identity .inp,
    .bom-identity .select2-container .select2-selection--single { background: #ffffff !important; }

    .bom-usage-tooltip {
        position: relative !important;
        cursor: help !important;
        outline: none !important;
    }

    .bom-usage-tooltip::after {
        content: attr(data-tooltip) !important;
        position: absolute !important;
        z-index: 30 !important;
        left: 0 !important;
        bottom: calc(100% + 7px) !important;
        min-width: 190px !important;
        max-width: 260px !important;
        padding: 7px 9px !important;
        border-radius: 8px !important;
        background: #0f172a !important;
        color: #ffffff !important;
        font-size: .68rem !important;
        font-weight: 650 !important;
        line-height: 1.35 !important;
        white-space: normal !important;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .18) !important;
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
        transform: translateY(3px) !important;
        transition: opacity .15s ease, transform .15s ease !important;
    }

    .bom-usage-tooltip:hover::after,
    .bom-usage-tooltip:focus::after {
        opacity: 1 !important;
        visibility: visible !important;
        transform: translateY(0) !important;
    }

    .bom-use-actual {
        display: inline-flex !important;
        align-items: center !important;
        gap: 3px !important;
        margin-left: 4px !important;
        padding: 3px 7px !important;
        border: 1px solid #99f6e4 !important;
        border-radius: 6px !important;
        background: #ecfdf5 !important;
        color: #0f766e !important;
        font-size: .68rem !important;
        font-weight: 850 !important;
        line-height: 1.1 !important;
        text-decoration: none !important;
    }

    .bom-use-actual:hover,
    .bom-use-actual:focus {
        background: #ccfbf1 !important;
        border-color: #5eead4 !important;
        color: #115e59 !important;
        text-decoration: none !important;
    }

    @media (max-width: 768px) {
        .bom-form-head { align-items: stretch !important; flex-direction: column !important; }
        .bom-form-head .btnx { align-self: flex-start !important; }
        .bom-overview { grid-template-columns: 1fr !important; gap: 10px !important; }
    }

</style>

@endpush

@section('content')
@php
  $isEdit = (bool) $bom;
  $prefilledItem = $prefilledItem ?? null;
  $bomEstimate = $bomEstimate ?? null;
  $action = $isEdit ? route('master.item_boms.update', $bom) : route('master.item_boms.store');

  // controller sebaiknya kirim $rows (sudah kamu punya)
  $rows = $rows ?? old('lines') ?? [[
    'material_item_id' => null,
    'material_text' => null,
    'qty' => '',
    'uom' => 'pcs',
    'scrap_pct' => 0,
    'is_optional' => 0,
  ]];
@endphp

<div class="gf-category-page">
  <form method="post" action="{{ $action }}" class="cardx gf-bom-form" id="bom-form">
    @csrf
    @if($isEdit) @method('PUT') @endif
    @if(!empty($materialCorrectionId))
      <input type="hidden" name="material_correction_id" value="{{ $materialCorrectionId }}">
    @endif

    <div class="bom-form-head">
      <div>
        <div class="h1">{{ $isEdit ? 'Edit BOM' : 'Buat BOM' }}</div>
        <div class="subt">
          {{ $isEdit ? ($bom->item->code . ' — ' . $bom->item->name) : 'Tentukan komponen dan kebutuhan material produksi.' }}
          @if($isEdit && $bom->name)
            <span class="bom-form-context">{{ $bom->name }}</span>
          @endif
        </div>
      </div>
      <div class="rowx">
        @if($isEdit)
          <a class="btnx ghost" href="{{ route('master.item_boms.show', $bom) }}"><i class="bi bi-calculator"></i> Lihat HPP</a>
        @endif
        <a class="btnx ghost" href="{{ route('master.item_boms.index') }}"><i class="bi bi-arrow-left"></i> Kembali</a>
      </div>
    </div>

    @if($errors->any())
      <div class="mt-3 alert alert-danger">
        <div style="font-weight:950">Error:</div>
        <ul class="mb-0">
          @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
      </div>
    @endif

    @if(!$isEdit)
    <div class="bom-overview">
      <div class="bom-identity">
        <div class="grid">
          <div>
            <div class="lbl">SKU Finished Goods</div>
            @if($prefilledItem)
              <input type="hidden" name="item_id" value="{{ $prefilledItem->id }}">
              <input class="inp mono" value="{{ $prefilledItem->code }} — {{ $prefilledItem->name }}" disabled>
            @else
              <select id="sku_id" name="item_id" style="width:100%"></select>
            @endif
          </div>

          <div>
            <div class="lbl">Nama BOM</div>
            <input class="inp" name="name" value="{{ old('name', $bom->name ?? '') }}" placeholder="Misal: BOM C5BLK">
          </div>
        </div>
      </div>

    </div>
    @else
      {{-- Nama BOM tetap dikirim saat edit, tetapi tidak lagi mengambil ruang di form utama. --}}
      <input type="hidden" name="name" value="{{ old('name', $bom->name ?? '') }}">
    @endif

    <div style="height:12px"></div>

    <div class="cardx sub">
      <div class="rowx" style="justify-content:space-between;align-items:flex-start">
        <div>
          <div class="lbl" style="margin:0">Lines</div>
          <div class="small">Qty 3 desimal · harga beli terakhir mengikuti Master Item.</div>
        </div>
      </div>

      @if(!empty($usageByMaterial ?? []))
        {{-- ── RIWAYAT PEMAKAIAN KAIN (dari cutting job non-void) ── --}}
        <div class="bom-usage-panel" aria-labelledby="bom-usage-title">
          <div class="bom-usage-head">
            <div>
              <h3 id="bom-usage-title"><span aria-hidden="true">🧵</span> Riwayat pemakaian kain</h3>
              <p>Data aktual cutting · detail riwayat tersedia saat dibutuhkan.</p>
            </div>
            <span class="bom-usage-count">{{ count($usageByMaterial) }} material</span>
          </div>
          <div class="bom-usage-list">
            @foreach($usageByMaterial as $umid => $u)
              @php
                $defaultScrap = (string) ($bom?->lines
                    ?->firstWhere('usage_stage', \App\Models\ItemBomLine::STAGE_MAIN_MATERIAL)
                    ?->scrap_pct ?? 0);
              @endphp
              <article class="bom-usage-row">
                <div class="bom-usage-material">
                  <strong class="mono">{{ $u['material_code'] ?? ('#'.$umid) }}</strong>
                  <span>{{ $u['material_name'] ?? '' }}</span>
                </div>
                <div class="bom-usage-latest">
                  <span>Aktual terakhir</span>
                  <strong class="mono">{{ number_format($u['kg_per_pcs'], 3, ',', '') }} <small>/pcs</small></strong>
                </div>
                <div class="bom-usage-actions">
                  <label class="bom-usage-waste">
                    <span>Waste</span>
                    <input type="number" class="use-scrap-input mono" value="{{ $defaultScrap }}" min="0" max="100" step="0.1" aria-label="Waste {{ $u['material_code'] ?? ('#'.$umid) }}">
                    <b>%</b>
                  </label>
                  <button type="button" class="bom-usage-detail" data-usage-detail
                          data-code="{{ $u['material_code'] ?? ('#'.$umid) }}"
                          data-name="{{ $u['material_name'] ?? '' }}"
                          data-latest="{{ number_format($u['kg_per_pcs'], 3, ',', '') }}"
                          data-history="{{ e(json_encode($u['history'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) }}">
                    <i class="bi bi-clock-history" aria-hidden="true"></i> Lihat detail
                  </button>
                  <button type="button" class="btnx btn-use-material"
                          data-mid="{{ $umid }}"
                          data-code="{{ $u['material_code'] ?? '' }}"
                          data-name="{{ $u['material_name'] ?? '' }}"
                          data-kg="{{ $u['kg_per_pcs'] }}">
                    Tambah sebagai bahan utama
                  </button>
                </div>
              </article>
            @endforeach
          </div>
        </div>
      @endif

      <div class="bom-history-modal" id="bom-history-modal" aria-hidden="true">
        <div class="bom-history-backdrop" data-history-close></div>
        <div class="bom-history-dialog" role="dialog" aria-modal="true" aria-labelledby="bom-history-modal-title" tabindex="-1">
          <header class="bom-history-dialog-head">
            <div>
              <span class="bom-history-eyebrow">Riwayat cutting</span>
              <h3 id="bom-history-modal-title">Detail pemakaian material</h3>
              <p id="bom-history-modal-subtitle"></p>
            </div>
            <button type="button" class="bom-history-close" data-history-close aria-label="Tutup detail riwayat">
              <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
          </header>
          <div class="bom-history-summary">
            <span>Aktual terakhir</span>
            <strong id="bom-history-modal-latest">-</strong>
          </div>
          <div class="bom-history-detail-label">3 pemakaian terakhir</div>
          <ol class="bom-history-list" id="bom-history-list"></ol>
          <footer class="bom-history-dialog-foot">
            <button type="button" class="btnx ghost" data-history-close>Tutup</button>
          </footer>
        </div>
      </div>

      <div class="rowx bom-lines-toolbar">
        <label class="bom-active-toggle" for="bom-active-toggle">
          <input id="bom-active-toggle" type="checkbox" name="active" value="1" {{ old('active', $bom->active ?? 1) ? 'checked' : '' }}>
          <span>Aktif</span>
        </label>
        <button type="button" class="btnx primary" id="btn-add"><i class="bi bi-plus-lg"></i> Tambah Baris</button>
        <button type="button" class="btnx" id="btn-default-waste">Waste default 0%</button>
      </div>

      <div class="table-wrap mt-3">
        @php
          $bomComponentsByLine = collect($bomEstimate['components'] ?? [])->keyBy('line_id');
        @endphp
        <table class="table table-hover table-sm table-bom gf-clean-table gf-sticky-table align-middle mb-0">
          <thead>
            <tr>
              <th style="width:56px">#</th>
              <th style="min-width:280px">Komponen (bahan / accessories)</th>
              <th style="width:100px">Kelompok</th>
              <th style="width:100px">Qty (raw)</th>
              <th style="width:100px">Satuan unit</th>
              <th style="width:92px">Waste (%)</th>
              <th style="width:88px">Optional</th>
              <th style="width:150px">Harga beli terakhir</th>
              <th style="width:130px">Biaya baris</th>
              <th style="width:76px"></th>
            </tr>
          </thead>
          <tbody id="lines">
            @foreach($rows as $i => $r)
              @php
                $mid = data_get($r,'material_item_id');
                $mtext = data_get($r,'material_text');
                $component = $bomComponentsByLine->get((int) data_get($r, 'id'));
                $qtyRaw = data_get($r, 'qty', '');
                $qtyDisplay = ($qtyRaw === null || $qtyRaw === '')
                    ? ''
                    : number_format((float) str_replace(',', '.', (string) $qtyRaw), 3, ',', '');
              @endphp
              <tr class="line" data-i="{{ $i }}">
                <td class="mono idx" style="font-weight:950">{{ $i+1 }}</td>
                <td>
                  <select class="mat" name="lines[{{ $i }}][material_item_id]" style="width:100%">
                    @if(!empty($mid))
                      @php
                        $mcode = data_get($r, 'material_code');
                        if (!$mcode && $mtext) {
                            $mcode = trim((string) preg_split('/\s+—\s+/u', (string) $mtext, 2)[0]);
                        }
                      @endphp
                      <option value="{{ $mid }}" selected>{{ $mcode ?: ($mtext ?: 'Material terpilih') }}</option>
                    @endif
                  </select>
                </td>
                <td>
                  <select class="inp mono usage-stage" name="lines[{{ $i }}][usage_stage]">
                    @foreach(\App\Models\ItemBomLine::usageStageLabels() as $value => $label)
                      <option value="{{ $value }}" {{ data_get($r,'usage_stage', \App\Models\ItemBomLine::STAGE_MAIN_MATERIAL) === $value ? 'selected' : '' }}>
                        {{ $label }}
                      </option>
                    @endforeach
                  </select>
                </td>
                <td>
                  <input class="inp mono qty"
                         inputmode="decimal"
                         data-max-decimals="3"
                         name="lines[{{ $i }}][qty]"
                         value="{{ $qtyDisplay }}"
                         placeholder="0,000">
                  <div class="bom-usage-hint" style="font-size:.68rem;line-height:1.45;margin-top:3px;"></div>
                </td>
                <td>
                  <input class="inp mono uom"
                         name="lines[{{ $i }}][uom]"
                         value="{{ (string) data_get($r,'uom','pcs') }}"
                         placeholder="Dari satuan unit master"
                         readonly
                         aria-readonly="true"
                         title="Satuan BOM mengikuti stock unit Master Item">
                </td>
                <td>
                  <input class="inp mono scrap"
                         type="number"
                         inputmode="decimal"
                         min="0"
                         max="100"
                         step="0.1"
                         name="lines[{{ $i }}][scrap_pct]"
                         value="{{ (string) data_get($r,'scrap_pct',0) }}"
                         placeholder="0">
                </td>
                <td>
                  <select class="inp mono" name="lines[{{ $i }}][is_optional]">
                    <option value="0" {{ (int) data_get($r,'is_optional',0)===0 ? 'selected':'' }}>No</option>
                    <option value="1" {{ (int) data_get($r,'is_optional',0)===1 ? 'selected':'' }}>Yes</option>
                  </select>
                </td>
                <td class="text-end bom-price-cell line-purchase-price" data-price-value="{{ (float) data_get($component, 'last_purchase_price', 0) }}" data-price-unit="{{ data_get($component, 'purchase_unit', data_get($r, 'uom', 'pcs')) }}">
                  <span class="bom-price-value">-</span>
                  <span class="bom-price-unit">/{{ data_get($component, 'purchase_unit', data_get($r, 'uom', 'pcs')) }}</span>
                </td>
                <td class="text-end bom-price-cell line-estimated-price" data-price-value="{{ (float) data_get($component, 'estimated_cost', 0) }}">
                  <span class="bom-price-value">-</span>
                  <span class="bom-price-unit">qty × harga stok</span>
                </td>
                <td><button type="button" class="btnx danger btn-del">Hapus</button></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="rowx" style="justify-content:flex-end;margin-top:14px">
        <button class="btnx primary" type="submit"><i class="bi bi-check2"></i> Simpan</button>
        @if($isEdit)
          <a class="btnx" href="{{ route('master.item_boms.duplicate_form', ['from_item_id' => $bom->item_id]) }}">
            <i class="bi bi-files"></i> Duplikat BOM
          </a>
        @endif
      </div>

      <div class="small mt-2">Bahan baku utama boleh lebih dari satu. Satu material hanya boleh muncul sekali; jika materialnya sama, gabungkan qty-nya.</div>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function(){
  const ajaxUrl = @json(route('master.item_boms.ajax_items'));

  // Memudahkan penggantian nilai qty, UOM, waste, dan nama BOM.
  // Delegated listener tetap berlaku untuk baris BOM yang ditambahkan dinamis.
  document.addEventListener('click', function(event){
    const input = event.target.closest('#bom-form input.inp:not([type="checkbox"]):not([type="radio"])');
    if (!input || input.disabled || input.readOnly) return;

    requestAnimationFrame(function(){
      input.select();
    });
  });

  function initSkuSelect(){
    const $el = $('#sku_id');
    if(!$el.length) return;

    $el.select2({
      placeholder: 'Cari SKU...',
      allowClear: true,
      ajax: {
        url: ajaxUrl,
        dataType: 'json',
        delay: 150,
        data: params => ({ q: params.term || '', type: 'finished_good' }),
        processResults: data => data,
        cache: true
      },
      width: 'resolve'
    });
  }

  function initMaterialSelect($select){
    $select.select2({
      placeholder: 'Cari bahan / accessories...',
      // Nilai yang sudah dipilih cukup menampilkan kode; hasil pencarian
      // tetap memakai text dari API (kode + nama) agar proses mencari tidak berubah.
      templateSelection: function(data){
        return data.code || data.text || '';
      },
      allowClear: true,
      ajax: {
        url: ajaxUrl,
        dataType: 'json',
        delay: 150,
        data: params => ({ q: params.term || '', type: 'material' }),
        processResults: data => data,
        cache: true
      },
      width: 'resolve'
    });

    // auto set BOM unit from ajaxItems() -> stock_unit
    $select.on('select2:select', function(e){
      const data = e.params.data || {};
      const $row = $(this).closest('tr.line');
      const unit = data.stock_unit || data.unit || '';
      const $uom = $row.find('input.uom');
      if (unit && $uom.length) {
        $uom.val(unit);
      }
      if (typeof renderCostCells === 'function') renderCostCells($row);
    });
  }

  function renumber(){
    $('#lines tr.line').each(function(idx){
      $(this).attr('data-i', idx);
      $(this).find('.idx').text(idx+1);

      // rename index after delete
      $(this).find('select.mat').attr('name', `lines[${idx}][material_item_id]`);
      $(this).find('select.usage-stage').attr('name', `lines[${idx}][usage_stage]`);
      $(this).find('input.qty').attr('name', `lines[${idx}][qty]`);
      $(this).find('input.uom').attr('name', `lines[${idx}][uom]`);
      $(this).find('input.scrap').attr('name', `lines[${idx}][scrap_pct]`);
      $(this).find('select[name*="[is_optional]"]').attr('name', `lines[${idx}][is_optional]`);
    });
  }

  function ensureOneRow(){
    const $tbody = $('#lines');
    if ($tbody.find('tr.line').length === 0) addRow();
  }

  function addRow(){
    const $tbody = $('#lines');
    const i = $tbody.find('tr.line').length;
        const usageOptions = @json(\App\Models\ItemBomLine::usageStageLabels());
        const usageHtml = Object.entries(usageOptions)
          .map(([value, label]) => `<option value="${value}" ${value === 'main_material' ? 'selected' : ''}>${label}</option>`)
          .join('');

	    const html = `
	      <tr class="line" data-i="${i}">
	        <td class="mono idx" style="font-weight:950">${i+1}</td>
	        <td><select class="mat" name="lines[${i}][material_item_id]" style="width:100%"></select></td>
            <td><select class="inp mono usage-stage" name="lines[${i}][usage_stage]">${usageHtml}</select></td>
        <td><input class="inp mono qty" inputmode="decimal" data-max-decimals="3" name="lines[${i}][qty]" placeholder="0,000"><div class="bom-usage-hint" style="font-size:.68rem;line-height:1.45;margin-top:3px;"></div></td>
        <td><input class="inp mono uom" name="lines[${i}][uom]" value="" placeholder="Dari satuan unit master" readonly aria-readonly="true" title="Satuan BOM mengikuti stock unit Master Item"></td>
        <td><input class="inp mono scrap" type="number" inputmode="decimal" min="0" max="100" step="0.1" name="lines[${i}][scrap_pct]" value="0" placeholder="0"></td>
        <td>
          <select class="inp mono" name="lines[${i}][is_optional]">
            <option value="0" selected>No</option>
            <option value="1">Yes</option>
          </select>
        </td>
        <td class="text-end bom-price-cell line-purchase-price" data-price-value="0" data-price-unit="pcs">
          <span class="bom-price-value">-</span>
          <span class="bom-price-unit">/pcs</span>
        </td>
        <td class="text-end bom-price-cell line-estimated-price" data-price-value="0">
          <span class="bom-price-value">-</span>
          <span class="bom-price-unit">qty × harga stok</span>
        </td>
        <td><button type="button" class="btnx danger btn-del">Hapus</button></td>
      </tr>
    `;
    const $row = $(html);
    $tbody.append($row);
    initMaterialSelect($row.find('select.mat'));
    renumber();
  }

  $(document).on('click', '#btn-add', addRow);
  $(document).on('click', '#btn-default-waste', function(){
    $('#lines input.scrap').val('0');
    $('#lines tr.line').css('background', '#ecfdf5');
    setTimeout(() => $('#lines tr.line').css('background', ''), 900);
  });
  $(document).on('click', '.btn-del', function(){
    $(this).closest('tr').remove();
    ensureOneRow();
    renumber();
  });

  function limitDecimalPlaces(input){
    const maxDecimals = Number(input.dataset.maxDecimals || 3);
    const parts = input.value.replace(/\./g, ',').split(',');
    const whole = parts.shift() || '';
    if (!parts.length) {
      input.value = whole;
      return;
    }

    input.value = `${whole},${parts.join('').slice(0, maxDecimals)}`;
  }

  $('#lines').on('input', 'input[data-max-decimals]', function(){
    limitDecimalPlaces(this);
  });

  initSkuSelect();
  $('#lines select.mat').each(function(){ initMaterialSelect($(this)); });

  // ── Pemakaian aktual terakhir per material (dari cutting job non-void) ──
  // { material_item_id: { kg_per_pcs, job_code, date, history: [..3] } }
  const bomUsage = @json($usageByMaterial ?? []);
  const bomPrices = @json($bomComponentsByLine->keyBy('material_item_id')->values()->all());

  function formatRupiah(value){
    const amount = Number(value || 0);
    return amount > 0
      ? `Rp ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(amount)}`
      : '-';
  }

  function selectedMaterialMeta($row){
    const $select = $row.find('select.mat');
    const selected = $select.select2('data')[0] || {};
    const materialId = Number($select.val() || 0);
    const fromBom = bomPrices.find(item => Number(item.material_item_id) === materialId) || {};

    return {
      stockUnit: selected.stock_unit || fromBom.stock_unit || selected.unit || fromBom.uom || $row.find('input.uom').val() || 'pcs',
      purchaseUnit: selected.purchase_unit || fromBom.purchase_unit || selected.stock_unit || fromBom.stock_unit || 'pcs',
      lastPurchasePrice: Number(selected.last_purchase_price ?? fromBom.last_purchase_price ?? 0),
      unitCost: Number(selected.unit_cost ?? fromBom.unit_cost ?? 0),
      conversionFactor: Number(selected.purchase_conversion_factor ?? fromBom.purchase_conversion_factor ?? 1) || 1,
    };
  }

  function renderCostCells($row){
    const meta = selectedMaterialMeta($row);
    const qty = Number(String($row.find('input.qty').val() || '0').replace(',', '.')) || 0;
    const scrap = Number($row.find('input.scrap').val() || 0) || 0;
    const estimated = qty * (1 + scrap / 100) * meta.unitCost;

    const $purchase = $row.find('.line-purchase-price');
    const $estimated = $row.find('.line-estimated-price');

    $purchase.attr('data-price-value', meta.lastPurchasePrice);
    $purchase.find('.bom-price-value').text(formatRupiah(meta.lastPurchasePrice));
    $purchase.find('.bom-price-unit').text(`/${meta.purchaseUnit}`);

    $estimated.attr('data-price-value', estimated);
    $estimated.find('.bom-price-value').text(formatRupiah(estimated));
  }

  function renderUsageHint($tr){
    const $hint = $tr.find('.bom-usage-hint');
    if (!$hint.length) return;

    const stage = $tr.find('select.usage-stage').val();
    const mid   = parseInt($tr.find('select.mat').val() || '0', 10);
    const u     = bomUsage[mid];

    if (stage !== 'main_material' || !u) { $hint.empty(); return; }

    const actual = Number(u.kg_per_pcs).toFixed(3).replace('.', ',');
    const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }[char]));
    const tooltip = escapeHtml(`Aktual ${actual}/pcs · ${u.job_code}`);
    $hint.html(
      `<a href="#" class="use-actual bom-use-actual bom-usage-tooltip" tabindex="0" data-tooltip="${tooltip}" title="${tooltip}" aria-label="${tooltip}"><i class="bi bi-arrow-down-circle"></i> Pakai</a>`
    );

    $hint.find('.use-actual').off('click').on('click', function(e){
      e.preventDefault();
      // Qty BOM = aktual per pcs dibagi (1 + scrap%) — scrap dipertahankan
      const scrap = parseFloat($tr.find('input.scrap').val() || '0');
      const suggested = Number(u.kg_per_pcs) / (1 + scrap / 100);
      $tr.find('input.qty').val(suggested.toFixed(3)).trigger('input');
    });
  }

  $('#lines tr.line').each(function(){ renderUsageHint($(this)); });
  $('#lines tr.line').each(function(){ renderCostCells($(this)); });
  $('#lines').on('change', 'select.mat, select.usage-stage', function(){
    const $row = $(this).closest('tr.line');
    renderUsageHint($row);
    renderCostCells($row);
  });
  $('#lines').on('input change', 'input.qty, input.scrap', function(){
    renderCostCells($(this).closest('tr.line'));
  });

  // ── Detail riwayat cutting (single reusable modal) ──
  const historyModal = document.getElementById('bom-history-modal');
  const historyDialog = historyModal?.querySelector('.bom-history-dialog');
  const historyTitle = document.getElementById('bom-history-modal-title');
  const historySubtitle = document.getElementById('bom-history-modal-subtitle');
  const historyLatest = document.getElementById('bom-history-modal-latest');
  const historyList = document.getElementById('bom-history-list');
  let historyLastFocus = null;
  let previousBodyOverflow = '';

  function formatHistoryQty(value){
    const number = Number(value || 0);
    return `${number.toFixed(3).replace('.', ',')} /pcs`;
  }

  function closeHistoryModal(){
    if (!historyModal) return;
    historyModal.classList.remove('is-open');
    historyModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = previousBodyOverflow;
    historyLastFocus?.focus();
    historyLastFocus = null;
  }

  function openHistoryModal(button){
    if (!historyModal || !historyList) return;

    historyLastFocus = button;
    historyTitle.textContent = button.dataset.code || 'Material';
    historySubtitle.textContent = button.dataset.name || 'Detail pemakaian material';
    historyLatest.textContent = `${button.dataset.latest || '-'} /pcs`;
    historyList.innerHTML = '';

    let history = [];
    try {
      history = JSON.parse(button.dataset.history || '[]');
    } catch (error) {
      history = [];
    }

    if (!history.length) {
      const empty = document.createElement('li');
      empty.className = 'bom-history-empty';
      empty.textContent = 'Belum ada riwayat cutting lainnya.';
      historyList.appendChild(empty);
    } else {
      history.forEach(function(item){
        const row = document.createElement('li');
        const detail = document.createElement('div');
        const job = document.createElement('strong');
        const date = document.createElement('small');
        const qty = document.createElement('span');

        job.textContent = item.job_code || 'Cutting Job';
        date.textContent = item.date || '-';
        qty.textContent = formatHistoryQty(item.kg_per_pcs);
        detail.append(job, date);
        row.append(detail, qty);
        historyList.appendChild(row);
      });
    }

    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    historyModal.classList.add('is-open');
    historyModal.setAttribute('aria-hidden', 'false');
    requestAnimationFrame(() => historyDialog?.querySelector('.bom-history-close')?.focus());
  }

  document.addEventListener('click', function(event){
    const detailButton = event.target.closest('[data-usage-detail]');
    if (detailButton) {
      event.preventDefault();
      openHistoryModal(detailButton);
      return;
    }

    if (event.target.closest('[data-history-close]')) {
      event.preventDefault();
      closeHistoryModal();
    }
  });

  document.addEventListener('keydown', function(event){
    if (event.key === 'Escape' && historyModal?.classList.contains('is-open')) {
      closeHistoryModal();
    }
  });

  // ── "Pakai sebagai bahan utama" dari panel riwayat pemakaian ──
  $(document).on('click', '.btn-use-material', function(){
    const mid  = parseInt(this.dataset.mid, 10);
    const kg   = parseFloat(this.dataset.kg || '0');
    const text = `${this.dataset.code} — ${this.dataset.name}`.replace(/^ — | — $/g, '');
    if (!mid || kg <= 0) return;

    // 1) Cari baris existing dengan material sama
    let $target = null;
    $('#lines tr.line').each(function(){
      if (parseInt($(this).find('select.mat').val() || '0', 10) === mid) {
        $target = $(this);
        return false;
      }
    });

    // 2) Kalau tidak ada: pakai baris kosong pertama, atau tambah baris baru.
    //    Jangan mengganti bahan baku utama lain karena satu BOM boleh punya
    //    beberapa bahan baku utama.
    if (!$target) {
      $('#lines tr.line').each(function(){
        if (!$(this).find('select.mat').val()) { $target = $(this); return false; }
      });
      if (!$target) {
        addRow();
        $target = $('#lines tr.line').last();
      }
    }

    // Pastikan material di baris target = material dari riwayat (replace jika beda)
    const $sel = $target.find('select.mat');
    if (parseInt($sel.val() || '0', 10) !== mid) {
      $sel.append(new Option(text, String(mid), true, true)).trigger('change');
    }

    // 3) Set kelompok = bahan baku utama.
    //    Scrap% diambil dari input di panel riwayat → di-set ke baris BOM,
    //    lalu qty = aktual / (1 + scrap%) sehingga qty×(1+scrap) = aktual.
    $target.find('select.usage-stage').val('main_material').trigger('change');
    const scrap = Math.max(0, parseFloat($(this).closest('.bom-usage-row').find('.use-scrap-input').val() || '0'));
    $target.find('input.scrap').val(scrap);
    $target.find('input.qty').val((kg / (1 + scrap / 100)).toFixed(3)).trigger('input');

    const $uom = $target.find('input.uom');
    if (!$uom.val() || $uom.val() === 'pcs') $uom.val('kg');

    renderUsageHint($target);
    $target[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    $target.css('background', '#ecfdf5');
    setTimeout(() => $target.css('background', ''), 1500);
  });
})();
</script>
@endpush
