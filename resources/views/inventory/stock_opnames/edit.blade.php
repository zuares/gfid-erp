{{-- resources/views/inventory/stock_opnames/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Stock Opname • ' . $opname->code)

@php
    use App\Models\StockOpname;

    $userRole = auth()->user()->role ?? null;
    $isOperating = $userRole === 'operating';
    $isAdmin = $userRole === 'admin';
    $isOpOrAdmin = $isOperating || $isAdmin;

    $isOpening = method_exists($opname, 'isOpening')
        ? $opname->isOpening()
        : $opname->type === StockOpname::TYPE_OPENING;

    $canModifyLines = method_exists($opname, 'canModifyLines')
        ? $opname->canModifyLines()
        : !in_array($opname->status, [StockOpname::STATUS_REVIEWED, StockOpname::STATUS_FINALIZED], true);

    $isReadonly = !$canModifyLines;

    $statusClass = match ($opname->status) {
        StockOpname::STATUS_DRAFT => 'badge-status badge-status--draft',
        StockOpname::STATUS_COUNTING => 'badge-status badge-status--counting',
        StockOpname::STATUS_REVIEWED => 'badge-status badge-status--reviewed',
        StockOpname::STATUS_FINALIZED => 'badge-status badge-status--finalized',
        default => 'badge-status badge-status--draft',
    };

    $linesCollection = $opname->lines;

    $totalLines = $linesCollection->count();
    // ✅ benar: progress pakai is_counted
    $countedLines = $linesCollection->where('is_counted', true)->count();
    $notCounted = max($totalLines - $countedLines, 0);

    // Urutan: terbaru -> terlama, agar item yang baru diinput selalu di atas.
    $lines = $linesCollection
        ->sortByDesc(function ($line) {
            return $line->updated_at ?? ($line->created_at ?? $line->id);
        })
        ->values();

    $newestLine = $lines->first();
    $newestLineId = $newestLine?->id;
@endphp

@push('head')
    <style>
        :root {
            --so-card-radius: 14px;
            --so-border: rgba(148, 163, 184, 0.28);
            --so-muted: rgba(100, 116, 139, 1);
        }

        .page-wrap {
            max-width: 1050px;
            margin-inline: auto;
            padding: .85rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.10) 0, rgba(148, 163, 184, 0.07) 30%, #f9fafb 70%);
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.26) 0, rgba(15, 23, 42, 1) 55%);
        }

        .card-main {
            background: var(--card);
            border-radius: var(--so-card-radius);
            border: 1px solid var(--so-border);
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06), 0 0 0 1px rgba(15, 23, 42, 0.03);
        }

        .page-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .85rem;
        }

        .page-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: -.01em;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 800;
            border: 1px solid rgba(148, 163, 184, .22);
            background: rgba(15, 23, 42, 0.02);
            color: rgba(71, 85, 105, 1);
        }

        body[data-theme="dark"] .chip {
            background: rgba(148, 163, 184, 0.08);
            border-color: rgba(148, 163, 184, 0.18);
            color: rgba(226, 232, 240, .86);
        }

        .pill-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
            font-weight: 800;
        }

        .meta {
            color: var(--so-muted);
        }

        .text-mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }

        .badge-status {
            font-size: .7rem;
            padding: .18rem .52rem;
            border-radius: 999px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .badge-status--draft {
            background: rgba(148, 163, 184, 0.2);
            color: #475569;
        }

        .badge-status--counting {
            background: rgba(59, 130, 246, 0.16);
            color: #1d4ed8;
        }

        .badge-status--reviewed {
            background: rgba(234, 179, 8, 0.18);
            color: #854d0e;
        }

        .badge-status--finalized {
            background: rgba(22, 163, 74, 0.18);
            color: #15803d;
        }

        .table-wrap {
            margin-top: .65rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .22);
            overflow-x: auto;
            overflow-y: auto;
            background: rgba(248, 250, 252, .9);
            max-height: 480px;
        }

        body[data-theme="dark"] .table-wrap {
            background: rgba(15, 23, 42, 0.92);
            border-color: rgba(51, 65, 85, .9);
        }

        .table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            white-space: nowrap;
            background: var(--card) !important;
            opacity: 1 !important;
            color: rgba(100, 116, 139, 1);
            box-shadow: inset 0 -1px 0 rgba(148, 163, 184, .22);
        }

        body[data-theme="dark"] .table thead th {
            background: rgba(15, 23, 42, 0.98) !important;
            color: #e5e7eb;
            box-shadow: inset 0 -1px 0 rgba(51, 65, 85, .9);
        }

        .table tbody td {
            vertical-align: middle;
            font-size: .82rem;
            white-space: nowrap;
        }

        .diff-negative {
            color: #dc2626;
            font-weight: 800;
        }

        .diff-zero {
            color: #16a34a;
            font-weight: 800;
        }

        .diff-positive {
            color: #ca8a04;
            font-weight: 800;
        }

        .so-row-not-counted {
            background: rgba(250, 204, 21, .06);
        }

        body[data-theme="dark"] .so-row-not-counted {
            background: rgba(234, 179, 8, .12);
        }

        .badge-not-counted {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .1rem .55rem;
            font-size: .72rem;
            font-weight: 800;
            background: rgba(234, 179, 8, .18);
            color: #854d0e;
        }

        .mobile-stack {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .section-meta {
            order: 1;
        }

        .section-add {
            order: 2;
        }

        .section-table {
            order: 3;
        }

        .opening-add-simple {
            margin-top: 0;
        }

        .opening-add-row {
            display: grid;
            grid-template-columns: 1fr 120px 96px;
            gap: .5rem;
            align-items: end;
        }

        .opening-add-grid {
            display: grid;
            grid-template-columns: 1fr 160px 160px 1fr 120px;
            gap: .5rem;
            align-items: end;
        }

        @media (max-width: 991.98px) {
            .opening-add-grid {
                grid-template-columns: 1fr 140px 160px 1fr 110px;
            }
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .55rem;
            }

            .so-meta--hide-mobile {
                display: none !important;
            }

            .col-system,
            .col-notes,
            .col-unit {
                display: none !important;
            }

            .table tbody td {
                font-size: .78rem;
            }

            .opening-add-desktop {
                display: none !important;
            }
        }

        @media (min-width: 768px) {
            .opening-add-simple {
                display: none !important;
            }
        }

        .dup-meta {
            font-size: .78rem;
            color: #6b7280;
        }

        .dup-meta .text-mono {
            font-size: .8rem;
        }

        .so-action-bottom-card {
            margin-top: .75rem;
        }

        .so-action-bottom-inner {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            align-items: center;
            justify-content: space-between;
        }

        .so-action-bottom-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }

        .so-action-bottom-meta {
            font-size: .78rem;
            color: #6b7280;
        }

        body[data-theme="dark"] .so-action-bottom-meta {
            color: #9ca3af;
        }

        .so-row-highlight {
            position: relative;
            animation: soRowPulseStrong 1.8s ease-out 1;
        }

        @keyframes soRowPulseStrong {
            0% {
                background-color: rgba(59, 130, 246, 0.00);
                box-shadow: inset 0 0 0 0 rgba(59, 130, 246, 0), 0 0 0 0 rgba(59, 130, 246, 0);
            }

            25% {
                background-color: rgba(59, 130, 246, 0.16);
                box-shadow: inset 0 0 0 2px rgba(59, 130, 246, 0.35), 0 4px 14px rgba(59, 130, 246, 0.25);
            }

            100% {
                background-color: transparent;
                box-shadow: inset 0 0 0 0 rgba(59, 130, 246, 0), 0 0 0 0 rgba(59, 130, 246, 0);
            }
        }

        body[data-theme="dark"] .so-row-highlight {
            animation: soRowPulseStrongDark 1.8s ease-out 1;
        }

        @keyframes soRowPulseStrongDark {
            0% {
                background-color: rgba(59, 130, 246, 0.00);
                box-shadow: inset 0 0 0 0 rgba(59, 130, 246, 0), 0 0 0 0 rgba(59, 130, 246, 0);
            }

            25% {
                background-color: rgba(59, 130, 246, 0.28);
                box-shadow: inset 0 0 0 2px rgba(147, 197, 253, 0.45), 0 6px 18px rgba(59, 130, 246, 0.35);
            }

            100% {
                background-color: transparent;
                box-shadow: inset 0 0 0 0 rgba(59, 130, 246, 0), 0 0 0 0 rgba(59, 130, 246, 0);
            }
        }

        /* ── Inline qty edit ────────────────────────── */
        .so-inline-qty {
            width: 90px;
            min-width: 70px;
            margin-left: auto;
            font-size: .82rem;
            padding: .18rem .42rem;
            text-align: right;
            border-radius: 6px;
            transition: border-color .15s, box-shadow .15s, opacity .15s;
            font-variant-numeric: tabular-nums;
        }

        .so-inline-hpp {
            width: 110px;
            min-width: 86px;
            margin-left: auto;
            font-size: .82rem;
            padding: .18rem .42rem;
            text-align: right;
            border-radius: 6px;
            transition: border-color .15s, box-shadow .15s, opacity .15s;
            font-variant-numeric: tabular-nums;
        }

        .so-inline-hpp.is-saving {
            border-color: #93c5fd !important;
            opacity: .6;
            pointer-events: none;
        }

        .so-inline-hpp.is-saved {
            border-color: #22c55e !important;
            box-shadow: 0 0 0 2px rgba(34,197,94,.2) !important;
        }

        .so-inline-hpp.is-error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 2px rgba(239,68,68,.2) !important;
        }
        .so-inline-qty.is-saving {
            border-color: #93c5fd !important;
            opacity: .6;
            pointer-events: none;
        }
        .so-inline-qty.is-saved {
            border-color: #22c55e !important;
            box-shadow: 0 0 0 2px rgba(34,197,94,.2) !important;
        }
        .so-inline-qty.is-error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 2px rgba(239,68,68,.2) !important;
        }
        .so-inline-diff {
            font-variant-numeric: tabular-nums;
            font-size: .82rem;
            font-weight: 800;
        }

        .so-sound-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            min-height: 34px;
            padding: .3rem .65rem;
            border: 1px solid rgba(148, 163, 184, .3);
            border-radius: 999px;
            background: var(--card);
            color: #475569;
            font-size: .72rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .so-sound-toggle.is-off {
            color: #94a3b8;
            border-color: rgba(148, 163, 184, .22);
        }

        .col-physical {
            min-width: 100px;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-top: .65rem;
                padding-bottom: 9.5rem;
            }

            .page-head {
                display: block;
            }

            .page-head > .text-end {
                margin-top: .5rem;
                text-align: left !important;
            }

            .page-title {
                font-size: 1rem;
                line-height: 1.55;
            }

            .so-sound-toggle {
                min-height: 40px;
                padding-inline: .75rem;
            }

            .section-add .card-body,
            .section-meta .card-body,
            .section-table .card-body {
                padding: .8rem;
            }

            .section-meta .card-body {
                padding: .55rem .7rem;
            }

            .section-meta .row {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                gap: .4rem .65rem;
                margin: 0;
            }

            .section-meta .row > [class*="col-"] {
                width: auto;
                padding-inline: 0;
            }

            .section-meta .so-meta-identity {
                grid-column: 1;
            }

            .section-meta .so-meta-warehouse {
                grid-column: 2;
            }

            .section-meta .so-meta-notes {
                grid-column: 1 / -1;
            }

            .section-meta .so-meta-identity-content {
                display: flex;
                align-items: flex-start;
                gap: .8rem;
            }

            .section-meta .so-meta-identity-content > div {
                min-width: 0;
                flex: 1 1 0;
            }

            .section-meta .so-meta-identity-content,
            .section-meta .so-meta-warehouse,
            .section-meta .so-meta-notes {
                font-size: .74rem;
                line-height: 1.25;
            }

            .section-meta .so-meta-identity .pill-label,
            .section-meta .so-meta-warehouse .pill-label,
            .section-meta .so-meta-notes .pill-label {
                margin-top: 0 !important;
                margin-bottom: .15rem !important;
                font-size: .58rem;
                letter-spacing: .045em;
            }

            .section-meta .so-meta-identity .text-mono,
            .section-meta .so-meta-identity-content [style],
            .section-meta .so-meta-warehouse .fw-semibold {
                font-size: .74rem !important;
            }

            .section-meta .so-meta-warehouse .meta {
                font-size: .68rem;
            }

            .section-meta .so-meta-identity .text-mono,
            .section-meta .so-meta-warehouse .fw-semibold,
            .section-meta .so-meta-warehouse .meta {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .section-meta .so-meta-notes textarea {
                min-height: 36px;
                height: 36px;
                resize: vertical;
                padding: .3rem .45rem;
                font-size: .74rem;
            }

            .opening-add-row {
                grid-template-columns: minmax(0, 1fr) 104px;
                gap: .65rem;
            }

            .opening-add-row > .d-grid {
                grid-column: 1 / -1;
            }

            .opening-add-row > .d-grid {
                margin-top: .05rem;
            }

            .opening-add-row .form-control,
            .opening-add-row button {
                min-height: 44px;
            }

            .table-wrap {
                max-height: none !important;
                overflow: visible;
                border: 0;
                background: transparent;
            }

            .table-wrap table,
            .table-wrap tbody {
                display: block;
                width: 100%;
            }

            .table-wrap thead {
                display: none;
            }

            .table-wrap tbody tr {
                display: grid;
                grid-template-columns: 26px minmax(0, 1fr) auto;
                gap: .25rem .55rem;
                align-items: center;
                margin-bottom: .55rem;
                padding: .7rem .65rem;
                border: 1px solid rgba(148, 163, 184, .22);
                border-radius: 12px;
                background: var(--card);
                box-shadow: 0 4px 12px rgba(15, 23, 42, .04);
            }

            body[data-theme="dark"] .table-wrap tbody tr {
                border-color: rgba(51, 65, 85, .9);
                box-shadow: none;
            }

            .table-wrap tbody td {
                display: none;
                min-width: 0;
                padding: 0;
                border: 0;
                white-space: normal;
            }

            .table-wrap tbody td:first-child,
            .table-wrap tbody td:nth-child(2),
            .table-wrap tbody td.col-physical,
            .table-wrap tbody td.col-unit,
            .table-wrap tbody td.so-line-action {
                display: block;
            }

            .table-wrap tbody td:first-child {
                grid-column: 1;
                grid-row: 1;
                align-self: start;
                padding-top: .1rem;
                color: var(--so-muted);
                font-size: .72rem;
                font-weight: 800;
            }

            .table-wrap tbody td:nth-child(2) {
                grid-column: 2;
                grid-row: 1;
                overflow-wrap: anywhere;
            }

            .table-wrap tbody tr.so-row-with-hpp td:nth-child(2) {
                grid-column: 2 / -1;
            }

            .table-wrap tbody td:nth-child(2) .meta {
                display: block;
                line-height: 1.25;
            }

            .table-wrap tbody td.col-physical {
                grid-column: 3;
                grid-row: 1;
                min-width: 92px;
            }

            .table-wrap tbody td.so-line-action {
                grid-column: 2 / -1;
                grid-row: 3;
                text-align: right;
            }

            .table-wrap tbody td.so-line-action .btn {
                min-height: 36px;
                padding-inline: .75rem;
            }

            .table-wrap tbody td.col-physical .so-inline-qty {
                width: 100%;
                min-width: 0;
                min-height: 40px;
                font-size: .9rem;
            }

            .table-wrap tbody td.col-unit {
                display: block !important;
                grid-column: 2 / -1;
                grid-row: 2;
                text-align: left !important;
                min-width: 0;
            }

            .table-wrap tbody tr.so-row-with-hpp td.col-physical {
                grid-column: 2;
                grid-row: 2;
                min-width: 0;
                text-align: left !important;
            }

            .table-wrap tbody tr.so-row-with-hpp td.col-unit {
                grid-column: 3;
                grid-row: 2;
                min-width: 104px;
                text-align: left !important;
            }

            .table-wrap tbody td.col-unit .so-inline-hpp {
                width: 100%;
                min-width: 0;
                min-height: 40px;
                font-size: .9rem;
            }

            .so-mobile-field-label {
                display: block;
                margin-bottom: .2rem;
            }

            .table-wrap tbody td.col-physical > .text-mono {
                display: inline-block;
                padding: .45rem .5rem;
                border-radius: 8px;
                background: rgba(148, 163, 184, .1);
            }

            .so-action-bottom-inner {
                display: block;
            }

            .so-action-bottom-buttons {
                display: grid;
                grid-template-columns: 1fr;
                margin-top: .7rem;
            }

            .so-action-bottom-buttons .btn {
                min-height: 44px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        {{-- HEADER --}}
        <div class="page-head">
            <div>
                <a href="{{ route('inventory.stock_opnames.show', $opname) }}" class="btn btn-sm btn-link px-0 mb-1">
                    ← Kembali
                </a>

                <h1 class="page-title">
                    {{ $opname->code }}
                    <span class="{{ $statusClass }} ms-1">{{ ucfirst($opname->status) }}</span>
                    <span class="chip ms-1">{{ $isOpening ? 'Opening' : 'Periodic' }}</span>
                </h1>
            </div>

            <div class="text-end d-flex align-items-center justify-content-end gap-2 flex-wrap">
                <button type="button" class="so-sound-toggle" id="soSoundToggle" aria-pressed="true">
                    🔊 Suara ON
                </button>
                <span class="chip">
                    {{ $countedLines }} / {{ $totalLines }} terisi
                    @if ($notCounted > 0)
                        ({{ $notCounted }} belum)
                    @endif
                </span>
            </div>
        </div>

        {{-- FORM WRAPPER --}}
        <form id="soUpdateForm" action="{{ route('inventory.stock_opnames.update', $opname) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- kontrol mode submit --}}
            <input type="hidden" name="mark_reviewed" id="mark_reviewed" value="0">

            <div class="mobile-stack">
                {{-- ================= META ================= --}}
                <div class="section-meta">
                    <div class="card card-main">
                        <div class="card-body">
                            <div class="row g-3 align-items-start">
                                @if ($isOpOrAdmin)
                                    <div class="col-md-6">
                                        <div class="pill-label mb-1">Tanggal</div>
                                        <div style="font-size:.92rem;">
                                            {{ $opname->date?->format('d M Y') ?? '-' }}
                                        </div>

                                        <div class="pill-label mt-3 mb-1">Gudang</div>
                                        <div class="fw-semibold">{{ $opname->warehouse?->code ?? '-' }}</div>
                                        <div class="meta">{{ $opname->warehouse?->name }}</div>
                                    </div>

                                    <div class="col-md-6 so-meta--hide-mobile">
                                        <div class="pill-label mb-1">Dokumen</div>
                                        <div class="text-mono fw-semibold">{{ $opname->code }}</div>

                                        <div class="pill-label mt-3 mb-1">Catatan</div>
                                        <textarea name="notes" class="form-control form-control-sm" rows="3" placeholder="Catatan…"
                                            @if ($isReadonly) readonly @endif>{{ old('notes', $opname->notes) }}</textarea>
                                    </div>
                                @else
                                    <div class="col-md-4 so-meta-identity">
                                        <div class="so-meta-identity-content">
                                            <div>
                                                <div class="pill-label mb-1">Kode</div>
                                                <div class="text-mono fw-semibold">{{ $opname->code }}</div>
                                            </div>

                                            <div>
                                                <div class="pill-label mt-3 mb-1">Tanggal</div>
                                                <div style="font-size:.9rem;">{{ $opname->date?->format('d M Y') ?? '-' }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 so-meta-warehouse">
                                        <div class="pill-label mb-1">Gudang</div>
                                        <div class="fw-semibold">{{ $opname->warehouse?->code ?? '-' }}</div>
                                        <div class="meta">{{ $opname->warehouse?->name }}</div>
                                    </div>

                                    <div class="col-md-4 so-meta-notes">
                                        <div class="pill-label mb-1">Catatan</div>
                                        <textarea name="notes" class="form-control form-control-sm" rows="3" placeholder="Catatan…"
                                            @if ($isReadonly) readonly @endif>{{ old('notes', $opname->notes) }}</textarea>
                                    </div>
                                @endif
                            </div>

                            @if ($errors->has('mark_reviewed'))
                                <div class="alert alert-warning mt-3 mb-2 py-2 px-3" style="font-size:.82rem;">
                                    {{ $errors->first('mark_reviewed') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ================= TAMBAH ITEM (OPENING & PERIODIK, AJAX) ================= --}}
                <div class="section-add">
                    @if ($canModifyLines)
                        {{-- MOBILE SIMPLE ADD --}}
                        <div class="card card-main opening-add-simple">
                            <div class="card-body">
                                <div id="openingAddMobile"
                                    data-action="{{ route('inventory.stock_opnames.lines.store', $opname) }}">
                                    <input type="hidden" id="openingAddTokenMobile" value="{{ csrf_token() }}">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">

                                    <div class="opening-add-row">
                                        <div id="opening-item-suggest-mobile">
                                            <label class="pill-label mb-1">Kode Item</label>
                                            <x-item-suggest idName="item_id" :idValue="old('item_id')" :displayValue="''"
                                                displayMode="code-name" mobileDisplayMode="code"
                                                :showName="false" :showCategory="false"
                                                placeholder="Kode item" mobilePlaceholder="Kode item"
                                                :autofocus="false" :autoSelectFirst="false" :maxResults="3" />
                                        </div>

                                        <div>
                                            <label class="pill-label mb-1">Qty Fisik</label>
                                            <x-number-input name="physical_qty" :value="old('physical_qty')" mode="decimal" :decimals="3"
                                                min="0" class="text-end js-opening-qty-mobile" />
                                        </div>

                                        <div class="d-grid">
                                            <button type="button" class="btn btn-sm btn-primary" style="height:34px;"
                                                id="btnOpeningAddMobile">
                                                Tambah
                                            </button>
                                        </div>
                                    </div>

                                    <input type="hidden" name="unit_cost" value="{{ old('unit_cost', '') }}">
                                    <input type="hidden" name="notes" value="{{ old('notes', '') }}">
                                    <input type="hidden" name="update_existing" value="0">
                                </div>
                            </div>
                        </div>

                        {{-- DESKTOP FULL ADD --}}
                        <div class="card card-main mb-3 opening-add-desktop">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="pill-label">
                                        {{ $isOpening ? 'Tambah item saldo awal' : 'Tambah item opname' }}</div>
                                    <span class="chip">{{ $isOpening ? 'Mode Opening' : 'Mode Periodik' }}</span>
                                </div>

                                <div id="openingAddDesktop"
                                    data-action="{{ route('inventory.stock_opnames.lines.store', $opname) }}">
                                    <input type="hidden" id="openingAddTokenDesktop" value="{{ csrf_token() }}">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">

                                    <div class="opening-add-grid">
                                        <div id="opening-item-suggest">
                                            <label class="pill-label mb-1">Item</label>
                                            <x-item-suggest idName="item_id" :idValue="old('item_id')" :displayValue="''"
                                                placeholder="Kode / nama barang" :autofocus="true" :autoSelectFirst="false"
                                                :maxResults="3" />
                                        </div>

                                        <div>
                                            <label class="pill-label mb-1">Qty Fisik</label>
                                            <x-number-input name="physical_qty" :value="old('physical_qty')" mode="decimal" :decimals="3"
                                                min="0" class="text-end js-opening-qty" />
                                        </div>

                                        @if ($isOpening)
                                            <div>
                                                <label class="pill-label mb-1">HPP / Unit</label>
                                                <x-number-input name="unit_cost" :value="old('unit_cost')" mode="decimal"
                                                    :decimals="2" min="0" class="text-end" />
                                            </div>
                                        @else
                                            <div>
                                                <label class="pill-label mb-1 text-muted">HPP / Unit</label>
                                                <input type="text" class="form-control form-control-sm"
                                                    value="Otomatis" disabled>
                                                <input type="hidden" name="unit_cost"
                                                    value="{{ old('unit_cost', '') }}">
                                            </div>
                                        @endif

                                        <div>
                                            <label class="pill-label mb-1">Catatan</label>
                                            <input type="text" name="notes" value="{{ old('notes') }}"
                                                class="form-control form-control-sm">
                                        </div>

                                        <input type="hidden" name="update_existing" value="0">

                                        <div class="d-grid">
                                            <button type="button" class="btn btn-sm btn-primary"
                                                id="btnOpeningAddDesktop">
                                                + Tambah
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                @if ($errors->has('item_id') || $errors->has('physical_qty') || $errors->has('unit_cost'))
                                    <div class="mt-2">
                                        @error('item_id')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                        @error('physical_qty')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                        @error('unit_cost')
                                            <div class="text-danger small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ================= TABLE LINES ================= --}}
                <div class="section-table">
                    <div class="card card-main">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="pill-label">
                                    {{ $isOpening ? 'Saldo awal per item' : 'Hasil hitung fisik per item' }}</div>
                                <span class="chip">{{ $countedLines }} / {{ $totalLines }} terisi</span>
                            </div>

                            <div class="table-wrap" id="opname-lines-table"
                                data-delete-url-template="{{ route('inventory.stock_opnames.lines.destroy', ['stockOpname' => $opname, 'line' => '__LINE_ID__']) }}"
                                data-newest-line-id="{{ $newestLineId ?? '' }}">
                                <table class="table table-sm mb-0 align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width:40px;">#</th>
                                            <th>Item</th>

                                            @unless ($isOpOrAdmin)
                                                <th class="text-end col-system">Qty Sistem</th>
                                            @endunless

                                            <th class="text-end">Qty Fisik</th>
                                            <th class="text-end col-diff d-none d-md-table-cell">Selisih</th>

                                            @if ($isOpening && !$isOpOrAdmin)
                                                <th class="text-end col-unit">HPP / Unit</th>
                                            @endif

                                            @unless ($isOpOrAdmin)
                                                <th class="col-notes d-none d-md-table-cell">Catatan</th>
                                            @endunless

                                            @if ($isOpening && $canModifyLines)
                                                <th class="text-end" style="width:70px;">Aksi</th>
                                            @endif
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($lines as $index => $line)
                                            @php
                                                $inputNamePrefix = "lines[{$line->id}]";

                                                $rawSystemQty = (float) ($line->system_qty ?? 0);

                                                // physical di DB (bukan dipaksa 0 lagi)
                                                $rawPhysical = old(
                                                    $inputNamePrefix . '.physical_qty',
                                                    $line->physical_qty,
                                                );
                                                $hasPhysicalValue = $rawPhysical !== null && $rawPhysical !== '';
                                                if ($hasPhysicalValue) {
                                                    $rawPhysical = (float) $rawPhysical;
                                                }

                                                $diffFromModel = $line->difference ?? ($line->difference_qty ?? null);
                                                if ($diffFromModel !== null) {
                                                    $diff = (float) $diffFromModel;
                                                } elseif ($hasPhysicalValue) {
                                                    $diff = $rawPhysical - $rawSystemQty;
                                                } else {
                                                    $diff = 0;
                                                }

                                                $hasPhysicalForDisplay = $hasPhysicalValue;

                                                $diffDisplay =
                                                    $diff > 0 ? '+' . number_format($diff, 2) : number_format($diff, 2);

                                                if ($hasPhysicalForDisplay) {
                                                    if ($diff < 0) {
                                                        $diffClass = 'diff-negative';
                                                    } elseif ($diff > 0) {
                                                        $diffClass = 'diff-positive';
                                                    } else {
                                                        $diffClass = 'diff-zero';
                                                    }
                                                } else {
                                                    $diffClass = '';
                                                }

                                                $rawUnitCost = old($inputNamePrefix . '.unit_cost', $line->unit_cost);
                                                $hasUnitCostValue = $rawUnitCost !== null && $rawUnitCost !== '';
                                                if ($hasUnitCostValue) {
                                                    $rawUnitCost = (float) $rawUnitCost;
                                                }

                                                // Fallback HPP master untuk baris lama yang unit_cost-nya masih NULL.
                                                // Prioritaskan items.hpp agar sama dengan halaman detail SO.
                                                $fallbackUnitCost = null;
                                                if (!$hasUnitCostValue && $line->item) {
                                                    $masterHpp = (float) ($line->item->hpp ?? 0);
                                                    $baseUnitCost = (float) ($line->item->base_unit_cost ?? 0);

                                                    if ($masterHpp > 0) {
                                                        $fallbackUnitCost = $masterHpp;
                                                    } elseif ($baseUnitCost > 0) {
                                                        $fallbackUnitCost = $baseUnitCost;
                                                    }
                                                }

                                                $effectiveUnitCost = $hasUnitCostValue
                                                    ? $rawUnitCost
                                                    : $fallbackUnitCost;

                                                $formattedUnitCost = $effectiveUnitCost !== null
                                                    ? rtrim(rtrim(number_format((float) $effectiveUnitCost, 4, '.', ''), '0'), '.')
                                                    : '';

                                                $rowClasses = [];
                                                $showNotCountedBadge = false;

                                                // ✅ opening & periodic: kalau belum input physical -> not counted
                                                if (!$hasPhysicalValue) {
                                                    $rowClasses[] = 'so-row-not-counted';
                                                    $showNotCountedBadge = true;
                                                }

                                                if ($isOpening && !$isOpOrAdmin) {
                                                    $rowClasses[] = 'so-row-with-hpp';
                                                }
                                            @endphp

                                            <tr class="{{ implode(' ', $rowClasses) }}"
                                                data-line-id="{{ $line->id }}" data-item-id="{{ $line->item_id }}"
                                                data-item-code="{{ $line->item?->code }}"
                                                data-item-name="{{ $line->item?->name }}"
                                                data-physical-qty="{{ $hasPhysicalValue ? $rawPhysical : '' }}">
                                                <td>{{ $index + 1 }}</td>

                                                <td>
                                                    <div class="fw-semibold">{{ $line->item?->code ?? '-' }}</div>
                                                    <div class="meta">{{ $line->item?->name ?? '' }}</div>

                                                    @if ($showNotCountedBadge)
                                                        <div class="d-md-none mt-1">
                                                            <span class="badge-not-counted">Belum dihitung</span>
                                                        </div>
                                                    @endif
                                                </td>

                                                @unless ($isOpOrAdmin)
                                                    <td class="text-end text-mono col-system">
                                                        {{ number_format($rawSystemQty, 2) }}
                                                    </td>
                                                @endunless

                                                {{-- ✅ Qty Fisik --}}
                                                <td class="text-end col-physical" style="min-width:100px;">
                                                    <span class="pill-label so-mobile-field-label d-md-none">Qty Fisik</span>
                                                    @if ($canModifyLines)
                                                        {{-- OPENING + PERIODIC: inline input --}}
                                                        <input type="text" inputmode="decimal" autocomplete="off"
                                                            name="{{ $inputNamePrefix }}[physical_qty]"
                                                            class="form-control form-control-sm so-inline-qty"
                                                            value="{{ $hasPhysicalValue ? number_format((float)$rawPhysical, 2, '.', '') : '' }}"
                                                            placeholder="-"
                                                            data-item-id="{{ $line->item_id }}"
                                                            data-system-qty="{{ $rawSystemQty }}"
                                                            data-original="{{ $hasPhysicalValue ? (float)$rawPhysical : '' }}"
                                                            data-unit-cost="{{ $effectiveUnitCost !== null ? $effectiveUnitCost : '' }}"
                                                            data-line-id="{{ $line->id }}">
                                                    @elseif ($isOpening)
                                                        @if ($hasPhysicalForDisplay)
                                                            <span class="text-mono">{{ number_format($rawPhysical, 2) }}</span>
                                                            <input type="hidden"
                                                                name="{{ $inputNamePrefix }}[physical_qty]"
                                                                value="{{ $rawPhysical }}">
                                                        @else
                                                            <span class="meta">-</span>
                                                        @endif
                                                    @else
                                                        {{-- READ-ONLY (reviewed/finalized) --}}
                                                        @if ($hasPhysicalForDisplay)
                                                            <span class="text-mono">{{ number_format($rawPhysical, 2) }}</span>
                                                        @else
                                                            <span class="meta">-</span>
                                                        @endif
                                                    @endif
                                                </td>

                                                {{-- Selisih --}}
                                                <td class="text-end col-diff d-none d-md-table-cell"
                                                    id="diff-cell-{{ $line->id }}">
                                                    @if ($hasPhysicalForDisplay)
                                                        <span class="so-inline-diff {{ $diffClass }}">{{ $diffDisplay }}</span>
                                                    @else
                                                        <span class="meta so-inline-diff">-</span>
                                                    @endif
                                                </td>

                                                {{-- ✅ unit_cost: opening selalu kirim; periodic hanya kalau sudah ada physical --}}
                                                @if ($isOpening)
                                                    <input type="hidden" class="so-unit-cost-hidden" data-line-id="{{ $line->id }}"
                                                        name="{{ $inputNamePrefix }}[unit_cost]"
                                                        value="{{ $effectiveUnitCost !== null ? $effectiveUnitCost : '' }}">
                                                @else
                                                    @if ($hasPhysicalForDisplay)
                                                        <input type="hidden" class="so-unit-cost-hidden" data-line-id="{{ $line->id }}"
                                                            name="{{ $inputNamePrefix }}[unit_cost]"
                                                            value="{{ $effectiveUnitCost !== null ? $effectiveUnitCost : '' }}">
                                                    @endif
                                                @endif

                                                @if ($isOpening && !$isOpOrAdmin)
                                                    <td class="text-end col-unit">
                                                        <span class="pill-label so-mobile-field-label d-md-none">HPP / Unit</span>
                                                        @if ($canModifyLines)
                                                            <input type="text" inputmode="decimal" autocomplete="off"
                                                                class="form-control form-control-sm so-inline-hpp"
                                                                value="{{ $formattedUnitCost }}"
                                                                placeholder="HPP"
                                                                data-original="{{ $effectiveUnitCost !== null ? (float) $effectiveUnitCost : '' }}"
                                                                data-line-id="{{ $line->id }}"
                                                                data-save-url="{{ route('inventory.stock_opnames.lines.unit_cost', ['stockOpname' => $opname, 'line' => $line]) }}">
                                                        @elseif ($effectiveUnitCost && $effectiveUnitCost > 0)
                                                            <span class="text-mono {{ $hasUnitCostValue ? '' : 'meta' }}">
                                                                {{ $formattedUnitCost }}
                                                            </span>
                                                        @else
                                                            <span class="meta">-</span>
                                                        @endif
                                                    </td>
                                                @endif

                                                @unless ($isOpOrAdmin)
                                                    <td class="col-notes d-none d-md-table-cell">
                                                        <input type="text" name="{{ $inputNamePrefix }}[notes]"
                                                            class="form-control form-control-sm"
                                                            value="{{ old($inputNamePrefix . '.notes', $line->notes) }}"
                                                            @if ($isReadonly) readonly @endif>
                                                    </td>
                                                @else
                                                    <input type="hidden" name="{{ $inputNamePrefix }}[notes]"
                                                        value="{{ old($inputNamePrefix . '.notes', $line->notes) }}">
                                                @endunless

                                                @if ($isOpening && $canModifyLines)
                                                    <td class="text-end so-line-action">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-danger js-delete-line"
                                                            data-line-id="{{ $line->id }}">
                                                            Hapus
                                                        </button>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- ACTION BAR BAWAH --}}
                    @if ($canModifyLines)
                        <div class="card card-main so-action-bottom-card">
                            <div class="card-body so-action-bottom-inner">
                                <div class="so-action-bottom-meta">
                                    @if ($isOpening)
                                        Mode Opening • Tambah / edit item di atas, lalu simpan di sini.
                                    @else
                                        Mode Periodik • Isi Qty Fisik untuk item yang dihitung. Selesai hitung hanya bisa jika semua item sudah terisi.
                                    @endif
                                </div>

                                <div class="so-action-bottom-buttons">
                                    <button type="submit" name="save_and_view" value="1"
                                        class="btn btn-sm btn-primary">
                                        Simpan
                                    </button>

                                    @if (in_array($opname->status, [StockOpname::STATUS_DRAFT, StockOpname::STATUS_COUNTING], true))
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-action="finish-counting-strict">
                                            Simpan &amp; Tandai Selesai
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- MINI MODAL: KONFIRMASI DUPLIKAT ITEM (Opening) --}}
    <div class="modal fade" id="duplicateItemModal" tabindex="-1" aria-labelledby="duplicateItemModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title" id="duplicateItemModalLabel">Item sudah ada</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-1">
                        <div class="fw-semibold" id="dupItemLabel">Item</div>
                        <div class="dup-meta">Akan <span class="fw-semibold">mengganti</span> Qty baris existing.</div>
                    </div>

                    <div class="dup-meta mt-2">
                        <div class="d-flex justify-content-between">
                            <span>Qty lama</span>
                            <span class="text-mono" id="dupQtyOld">0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Qty baru</span>
                            <span class="text-mono" id="dupQtyNew">0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Perubahan</span>
                            <span class="text-mono" id="dupQtyDiff">0</span>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-1 py-1 px-2" style="font-size:.78rem;">
                        Lanjutkan untuk <strong>update baris existing</strong>.
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-sm btn-primary" data-duplicate-confirm="1">
                        Lanjutkan update
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.IS_OPENING_MODE   = @json($isOpening);
        window.SO_CAN_MODIFY     = @json($canModifyLines);
        window.SO_INLINE_SAVE_URL = @json(route('inventory.stock_opnames.lines.store', $opname));
        window.SO_CSRF           = @json(csrf_token());
        window.SO_FLASH_SUCCESS  = @json(session('success'));
        window.SO_FLASH_ERROR    = @json(session('error'));
        window.SO_HAS_ERRORS     = @json($errors->any());
    </script>

    <script>
        let duplicateItemModalInstance = null;
        let pendingOpeningSubmit = null;

        document.addEventListener('DOMContentLoaded', function() {
            initSoSound();
            consumePendingSoFeedback();

            if (window.SO_FLASH_SUCCESS) playSoSound('success');
            if (window.SO_FLASH_ERROR || window.SO_HAS_ERRORS) playSoSound('error');

            initDuplicateItemModal();
            initInlineQtyEdit();
            initInlineHppEdit();

            initOpeningAddBlock({
                rootSelector: '#openingAddMobile',
                tokenSelector: '#openingAddTokenMobile',
                itemSuggestInputSelector: '#opening-item-suggest-mobile .js-item-suggest-input',
                qtySelector: '.js-opening-qty-mobile',
                submitBtnSelector: '#btnOpeningAddMobile',
            });

            initOpeningAddBlock({
                rootSelector: '#openingAddDesktop',
                tokenSelector: '#openingAddTokenDesktop',
                itemSuggestInputSelector: '#opening-item-suggest .js-item-suggest-input',
                qtySelector: '.js-opening-qty',
                submitBtnSelector: '#btnOpeningAddDesktop',
            });

            initDeleteLineAjax();
            focusBackAfterReload();
            renumberOpnameRows();

            setTableMaxHeight10Rows();
            highlightNewestRowIfNeeded();

            const soForm = document.getElementById('soUpdateForm');
            const markReviewedEl = document.getElementById('mark_reviewed');

            if (soForm) {
                soForm.addEventListener('submit', function() {
                    disableOpeningAddInputsBeforeSubmit();
                });
            }

            // tombol finish: mark_reviewed=1 tanpa auto-fill 0
            if (soForm && markReviewedEl) {
                const strictBtn = document.querySelector('[data-action="finish-counting-strict"]');
                if (strictBtn) {
                    strictBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        disableOpeningAddInputsBeforeSubmit();

                        markReviewedEl.value = '1';
                        soForm.submit();
                    });
                }
            }

            window.addEventListener('resize', debounce(() => {
                setTableMaxHeight10Rows();
            }, 150));
        });

        function debounce(fn, wait = 150) {
            let t = null;
            return function(...args) {
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), wait);
            };
        }

        function disableOpeningAddInputsBeforeSubmit() {
            const blocks = ['#openingAddMobile', '#openingAddDesktop'];
            blocks.forEach(sel => {
                const root = document.querySelector(sel);
                if (!root) return;
                root.querySelectorAll('input, select, textarea, button').forEach(el => {
                    el.disabled = true;
                });
                const suggest = root.querySelector('.js-item-suggest-input');
                if (suggest) suggest.disabled = true;
            });
        }

        /* ────────────────────────────────────────────────────
         * INPUT FEEDBACK SOUND
         * Uses the shared GFID sound engine so the tone is generated locally
         * and remains available without adding an audio asset to the page.
         * ──────────────────────────────────────────────────── */
        const SO_SOUND_STORAGE_KEY = 'so_input_sound_enabled';
        const SO_FEEDBACK_STORAGE_KEY = 'so_input_feedback';

        function initSoSound() {
            const button = document.getElementById('soSoundToggle');
            if (!button) return;

            let enabled = true;
            try {
                const saved = localStorage.getItem(SO_SOUND_STORAGE_KEY);
                enabled = saved !== '0';
            } catch (e) {}

            if (window.GFID?.setScanSoundEnabled) {
                window.GFID.setScanSoundEnabled(enabled);
            }
            window.SO_SOUND_ENABLED = enabled;

            const render = () => {
                const active = window.GFID?.isScanSoundEnabled
                    ? window.GFID.isScanSoundEnabled()
                    : enabled;
                button.classList.toggle('is-off', !active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
                button.textContent = active ? '🔊 Suara ON' : '🔇 Suara OFF';
                button.title = active ? 'Matikan suara feedback input' : 'Nyalakan suara feedback input';
            };

            render();
            button.addEventListener('click', function() {
                const current = window.GFID?.isScanSoundEnabled
                    ? window.GFID.isScanSoundEnabled()
                    : enabled;
                enabled = !current;
                window.SO_SOUND_ENABLED = enabled;
                if (window.GFID?.setScanSoundEnabled) window.GFID.setScanSoundEnabled(enabled);
                try { localStorage.setItem(SO_SOUND_STORAGE_KEY, enabled ? '1' : '0'); } catch (e) {}
                render();
            });

            // Resume AudioContext from a real user gesture (required by mobile browsers).
            const unlock = () => {
                try {
                    const Ctx = window.AudioContext || window.webkitAudioContext;
                    if (!Ctx || !window.GFID) return;
                    const ctx = window.GFID.scanAudioContext || (window.GFID.scanAudioContext = new Ctx());
                    if (ctx.state === 'suspended') ctx.resume().catch(() => {});
                } catch (e) {}
            };
            document.addEventListener('pointerdown', unlock, { once: true, passive: true });
            document.addEventListener('keydown', unlock, { once: true });
        }

        function playSoSound(type) {
            const enabled = window.GFID?.isScanSoundEnabled
                ? window.GFID.isScanSoundEnabled()
                : window.SO_SOUND_ENABLED !== false;
            if (!enabled) return;

            if (window.GFID?.playScanSoundPreset) {
                window.GFID.playScanSoundPreset(type === 'error' ? 'error' : 'ok');
            }
        }

        function showSoSuccess(message) {
            playSoSound('success');
            if (window.GFID?.toast) {
                window.GFID.toast(message || 'Input berhasil disimpan.', {
                    position: 'top-end',
                    timer: 1800,
                });
            } else if (message) {
                alert(message);
            }
        }

        function showSoError(message) {
            playSoSound('error');
            if (window.GFID?.errorAlert) {
                window.GFID.errorAlert(message || 'Input gagal disimpan.');
            } else {
                alert(message || 'Input gagal disimpan.');
            }
        }

        function queueSoFeedback(type, message) {
            try {
                sessionStorage.setItem(SO_FEEDBACK_STORAGE_KEY, JSON.stringify({ type, message }));
            } catch (e) {}
        }

        function consumePendingSoFeedback() {
            let pending = null;
            try {
                pending = JSON.parse(sessionStorage.getItem(SO_FEEDBACK_STORAGE_KEY) || 'null');
                sessionStorage.removeItem(SO_FEEDBACK_STORAGE_KEY);
            } catch (e) {}
            if (!pending) return;

            setTimeout(() => {
                if (pending.type === 'error') showSoError(pending.message);
                else showSoSuccess(pending.message);
            }, 90);
        }

        function renumberOpnameRows() {
            const tbody = document.querySelector('#opname-lines-table tbody');
            if (!tbody) return;
            tbody.querySelectorAll('tr').forEach((tr, idx) => {
                const firstCell = tr.querySelector('td');
                if (firstCell) firstCell.textContent = String(idx + 1);
            });
        }

        function setTableMaxHeight10Rows() {
            const tableWrap = document.getElementById('opname-lines-table');
            if (!tableWrap) return;

            if (window.innerWidth < 768) {
                tableWrap.style.maxHeight = 'none';
                tableWrap.style.overflowY = 'visible';
                return;
            }

            const table = tableWrap.querySelector('table');
            const thead = table ? table.querySelector('thead') : null;
            const firstRow = table ? table.querySelector('tbody tr') : null;
            if (!firstRow) return;

            const rowH = firstRow.getBoundingClientRect().height || 38;
            const headH = thead ? (thead.getBoundingClientRect().height || 34) : 34;
            const maxH = Math.ceil((rowH * 10) + headH + 8);

            tableWrap.style.maxHeight = maxH + 'px';
            tableWrap.style.overflowY = 'auto';
        }

        function scrollRowIntoTableWrap(row, tableWrap, behavior = 'smooth') {
            if (!row || !tableWrap) return;
            const rowTop = row.offsetTop;
            const rowH = row.offsetHeight || 32;
            const target = rowTop - (tableWrap.clientHeight / 2) + (rowH / 2);

            tableWrap.scrollTo({
                top: Math.max(0, target),
                behavior
            });
        }

        function highlightNewestRowIfNeeded() {
            try {
                const flag = sessionStorage.getItem('so_opening_highlight_newest');
                if (!flag) return;
                sessionStorage.removeItem('so_opening_highlight_newest');

                const tableWrap = document.getElementById('opname-lines-table');
                if (!tableWrap) return;

                const newestId = tableWrap.dataset.newestLineId;
                if (!newestId) return;

                const row = tableWrap.querySelector('tr[data-line-id="' + newestId + '"]');
                if (!row) return;

                setTableMaxHeight10Rows();
                scrollRowIntoTableWrap(row, tableWrap, 'smooth');

                row.classList.add('so-row-highlight');
                setTimeout(() => row.classList.remove('so-row-highlight'), 1800);
            } catch (e) {}
        }

        function initDuplicateItemModal() {
            const modalEl = document.getElementById('duplicateItemModal');
            if (!modalEl || !window.bootstrap) return;

            duplicateItemModalInstance = new bootstrap.Modal(modalEl);

            const confirmBtn = modalEl.querySelector('[data-duplicate-confirm]');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    if (!pendingOpeningSubmit) return;

                    const {
                        rootEl,
                        updateExistingInput,
                        actionUrl,
                        csrf
                    } = pendingOpeningSubmit;

                    if (updateExistingInput) updateExistingInput.value = '1';

                    performOpeningAjaxSubmit(rootEl, {
                        actionUrl,
                        csrf,
                        focusBackToItemOnSuccess: true
                    });

                    pendingOpeningSubmit = null;
                    duplicateItemModalInstance.hide();
                });
            }
        }

        function formatNumberForDisplay(num) {
            const n = Number(num) || 0;
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }).format(n);
        }

        function formatHppForInput(value) {
            const n = Number(value);
            if (!Number.isFinite(n)) return '';
            return n.toFixed(4).replace(/\.?0+$/, '');
        }

        function collectExistingItemIds() {
            const existingIds = new Set();
            document.querySelectorAll('tr[data-item-id]').forEach(tr => {
                const id = tr.getAttribute('data-item-id');
                if (id) existingIds.add(id);
            });
            return existingIds;
        }

        function initOpeningAddBlock(opts) {
            const rootEl = document.querySelector(opts.rootSelector);
            if (!rootEl) return;

            const actionUrl = rootEl.dataset.action;
            const tokenEl = document.querySelector(opts.tokenSelector);
            const csrf = tokenEl ? tokenEl.value : '';

            const itemSuggestInput = document.querySelector(opts.itemSuggestInputSelector);
            const qtyInput = rootEl.querySelector(opts.qtySelector);
            const itemIdInput = rootEl.querySelector('input[name="item_id"]');
            const submitBtn = document.querySelector(opts.submitBtnSelector);

            const updateExistingInput = rootEl.querySelector('input[name="update_existing"]');

            if (itemSuggestInput && window.innerWidth >= 768 && opts.rootSelector === '#openingAddDesktop') {
                itemSuggestInput.focus();
                itemSuggestInput.select && itemSuggestInput.select();
            }

            if (itemSuggestInput && qtyInput) {
                itemSuggestInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        qtyInput.focus();
                        qtyInput.select && qtyInput.select();
                    }
                });
            }

            // Kode item mobile selalu ditulis dengan huruf kapital.
            if (itemSuggestInput && opts.rootSelector === '#openingAddMobile') {
                itemSuggestInput.addEventListener('input', function() {
                    const cursor = this.selectionStart;
                    this.value = this.value.toUpperCase();
                    if (cursor !== null) this.setSelectionRange(cursor, cursor);
                });
            }

            // Setelah opsi autosuggest dipilih (klik atau keyboard), lanjut ke Qty.
            if (itemIdInput && qtyInput) {
                itemIdInput.addEventListener('change', function() {
                    if (!itemIdInput.value) return;

                    window.setTimeout(() => {
                        qtyInput.focus();
                        qtyInput.select && qtyInput.select();
                    }, 0);
                });
            }

            if (qtyInput) {
                qtyInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        submitOpening(rootEl, actionUrl, csrf, itemSuggestInput, updateExistingInput);
                    }
                });
            }

            if (submitBtn) {
                submitBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    submitOpening(rootEl, actionUrl, csrf, itemSuggestInput, updateExistingInput);
                });
            }
        }

        function submitOpening(rootEl, actionUrl, csrf, itemSuggestInput, updateExistingInput) {
            const existingIds = collectExistingItemIds();

            const itemIdField = rootEl.querySelector('input[name="item_id"]');
            const qtyField = rootEl.querySelector('input[name="physical_qty"]');

            if (!itemIdField || !itemIdField.value) {
                showSoError('Pilih item terlebih dahulu.');
                itemSuggestInput && itemSuggestInput.focus();
                return;
            }
            if (!qtyField || qtyField.value.trim() === '') {
                showSoError('Isi Qty Fisik terlebih dahulu.');
                qtyField && qtyField.focus();
                qtyField && qtyField.select && qtyField.select();
                return;
            }

            const itemId = itemIdField.value;
            const newQtyValue = qtyField.value;

            if (existingIds.has(itemId)) {
                if (!window.IS_OPENING_MODE) {
                    updateExistingInput && (updateExistingInput.value = '1');
                    performOpeningAjaxSubmit(rootEl, {
                        actionUrl,
                        csrf,
                        focusBackToItemOnSuccess: true
                    });
                    return;
                }

                if (duplicateItemModalInstance) {
                    const row = document.querySelector('tr[data-item-id="' + itemId + '"]');
                    const oldQty = row ? parseFloat(row.getAttribute('data-physical-qty') || '0') : 0;
                    const newQty = parseFloat(newQtyValue || '0');
                    const diff = newQty - oldQty;

                    const code = row ? (row.getAttribute('data-item-code') || '') : '';
                    const name = row ? (row.getAttribute('data-item-name') || '') : '';

                    const labelEl = document.getElementById('dupItemLabel');
                    const qtyOldEl = document.getElementById('dupQtyOld');
                    const qtyNewEl = document.getElementById('dupQtyNew');
                    const qtyDiffEl = document.getElementById('dupQtyDiff');

                    if (labelEl) labelEl.textContent = (code ? code : 'Item') + (name ? ' — ' + name : '');
                    if (qtyOldEl) qtyOldEl.textContent = formatNumberForDisplay(oldQty);
                    if (qtyNewEl) qtyNewEl.textContent = formatNumberForDisplay(newQty);
                    if (qtyDiffEl) qtyDiffEl.textContent = (diff > 0 ? '+' : '') + formatNumberForDisplay(diff);

                    pendingOpeningSubmit = {
                        rootEl,
                        updateExistingInput,
                        actionUrl,
                        csrf
                    };
                    duplicateItemModalInstance.show();
                    return;
                } else {
                    const ok = confirm('Item ini sudah ada.\n\nUpdate baris existing (Qty diganti)?');
                    if (!ok) return;
                    updateExistingInput && (updateExistingInput.value = '1');
                }
            } else {
                updateExistingInput && (updateExistingInput.value = '0');
            }

            performOpeningAjaxSubmit(rootEl, {
                actionUrl,
                csrf,
                focusBackToItemOnSuccess: true
            });
        }

        function performOpeningAjaxSubmit(rootEl, opts = {}) {
            const actionUrl = opts.actionUrl || rootEl.dataset.action;
            if (!actionUrl) {
                showSoError('URL penyimpanan tidak ditemukan.');
                return;
            }

            let csrf = opts.csrf || '';
            if (!csrf) {
                const tokenInput = rootEl.querySelector('input[name="_token"]');
                if (tokenInput && tokenInput.value) csrf = tokenInput.value;
                else csrf = getCsrfToken();
            }

            const formData = new FormData();
            if (csrf) formData.append('_token', csrf);

            rootEl.querySelectorAll('input[name]').forEach(inp => {
                if (inp.name === '_token') return;
                formData.append(inp.name, inp.value ?? '');
            });

            fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf || getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                })
                .then(async (response) => {
                    if (!response.ok) {
                        let msg = 'Gagal menyimpan item.';
                        try {
                            const data = await response.json();
                            if (data?.message) msg = data.message;
                            else if (data?.errors) {
                                const firstKey = Object.keys(data.errors)[0];
                                msg = data.errors[firstKey][0] ?? msg;
                            }
                        } catch (e) {}
                        showSoError(msg);
                        return null;
                    }
                    return response.json();
                })
                .then((data) => {
                    if (!data) return;
                    if (data.status === 'ok') {
                        try {
                            sessionStorage.setItem('so_opening_focus_back', '1');
                            sessionStorage.setItem('so_opening_highlight_newest', '1');
                        } catch (e) {}
                        queueSoFeedback('success', data.message || 'Item opname berhasil disimpan.');
                        window.setTimeout(() => window.location.reload(), 120);
                    } else {
                        showSoError(data.message || 'Gagal menyimpan item.');
                    }
                })
                .catch(() => showSoError('Terjadi kesalahan saat menyimpan item.'));
        }

        function focusBackAfterReload() {
            try {
                const flag = sessionStorage.getItem('so_opening_focus_back');
                if (!flag) return;
                sessionStorage.removeItem('so_opening_focus_back');

                const mobileInput = document.querySelector('#opening-item-suggest-mobile .js-item-suggest-input');
                const desktopInput = document.querySelector('#opening-item-suggest .js-item-suggest-input');

                const target = (mobileInput && isElementVisible(mobileInput)) ? mobileInput : desktopInput;
                if (target) {
                    setTimeout(() => {
                        target.focus();
                        target.select && target.select();
                    }, 140);
                }
            } catch (e) {}
        }

        function isElementVisible(el) {
            if (!el) return false;
            const rect = el.getBoundingClientRect();
            return rect.width > 0 && rect.height > 0;
        }

        function getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) return meta.getAttribute('content');
            return '';
        }

        /* ────────────────────────────────────────────────────
         * INLINE QTY EDIT — auto-save on blur / Enter
         * ──────────────────────────────────────────────────── */
        function initInlineQtyEdit() {
            if (!window.SO_CAN_MODIFY) return;

            const tableWrap = document.getElementById('opname-lines-table');
            if (!tableWrap) return;

            tableWrap.querySelectorAll('.so-inline-qty').forEach(input => {
                // filter angka + titik + koma
                input.addEventListener('input', function() {
                    let v = this.value.replace(/,/g, '.').replace(/[^0-9.]/g, '');
                    const parts = v.split('.');
                    if (parts.length > 2) v = parts[0] + '.' + parts.slice(1).join('');
                    this.value = v;

                    // update diff column real-time
                    updateDiffCell(this);
                    // hapus state saved/error kalau user mulai ngetik lagi
                    this.classList.remove('is-saved', 'is-error');
                });

                // select all on focus
                input.addEventListener('focus', function() {
                    this.select && this.select();
                });

                // Enter → blur (trigger save)
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.blur();
                    }
                    // Escape → restore original
                    if (e.key === 'Escape') {
                        this.value = this.dataset.original ?? '';
                        updateDiffCell(this);
                        this.blur();
                    }
                });

                // blur → save jika ada perubahan
                input.addEventListener('blur', function() {
                    const raw = this.value.trim();
                    const newVal = raw === '' ? null : parseFloat(raw);
                    const original = this.dataset.original === '' ? null : parseFloat(this.dataset.original || '');

                    // tidak ada perubahan → skip
                    if (newVal === original) return;
                    // null dan belum pernah diisi → skip
                    if (newVal === null && original === null) return;

                    saveInlineQty(this, newVal);
                });
            });
        }

        function updateDiffCell(input) {
            const lineId    = input.dataset.lineId;
            const systemQty = parseFloat(input.dataset.systemQty || '0');
            const raw       = input.value.trim();
            const physical  = raw === '' ? null : parseFloat(raw);

            const cell = document.getElementById('diff-cell-' + lineId);
            if (!cell) return;

            const span = cell.querySelector('.so-inline-diff');
            if (!span) return;

            if (physical === null) {
                span.textContent = '-';
                span.className = 'so-inline-diff meta';
            } else {
                const diff = physical - systemQty;
                const formatted = (diff > 0 ? '+' : '') + diff.toFixed(2);
                span.textContent = formatted;
                span.className = 'so-inline-diff ' + (
                    diff < 0 ? 'diff-negative' : diff > 0 ? 'diff-positive' : 'diff-zero'
                );
            }

            // Update warna baris not-counted
            const tr = input.closest('tr');
            if (tr) {
                if (physical === null) {
                    tr.classList.add('so-row-not-counted');
                } else {
                    tr.classList.remove('so-row-not-counted');
                }
            }
        }

        function saveInlineQty(input, newVal) {
            const itemId = input.dataset.itemId;
            if (!itemId) return;

            input.classList.remove('is-saved', 'is-error');
            input.classList.add('is-saving');

            const formData = new FormData();
            formData.append('_token', window.SO_CSRF || getCsrfToken());
            formData.append('item_id', itemId);
            formData.append('update_existing', '1');
            formData.append('physical_qty', newVal !== null ? String(newVal) : '');
            formData.append('unit_cost', input.dataset.unitCost || '');
            formData.append('notes', '');

            fetch(window.SO_INLINE_SAVE_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.SO_CSRF || getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            })
            .then(async (res) => {
                input.classList.remove('is-saving');
                if (!res.ok) {
                    let msg = 'Gagal menyimpan.';
                    try { const d = await res.json(); msg = d?.message || msg; } catch(e) {}
                    input.classList.add('is-error');
                    // tooltip singkat
                    input.title = msg;
                    showSoError(msg);
                    return;
                }
                const d = await res.json();
                if (d?.status === 'ok') {
                    // update data-original supaya next blur bisa compare dengan benar
                    input.dataset.original = newVal !== null ? String(newVal) : '';
                    // format nilai setelah save
                    if (newVal !== null) {
                        input.value = newVal.toFixed(2);
                    }
                    input.classList.add('is-saved');
                    input.title = '';
                    showSoSuccess('Qty berhasil disimpan.');
                    // hapus class saved setelah 2 detik
                    setTimeout(() => input.classList.remove('is-saved'), 2000);
                } else {
                    input.classList.add('is-error');
                    input.title = d?.message || 'Gagal menyimpan.';
                    showSoError(d?.message || 'Gagal menyimpan.');
                }
            })
            .catch(() => {
                input.classList.remove('is-saving');
                input.classList.add('is-error');
                input.title = 'Koneksi error.';
                showSoError('Koneksi error saat menyimpan qty.');
            });
        }

        /* ────────────────────────────────────────────────────
         * INLINE HPP EDIT — auto-save on blur / Enter (Opening)
         * ──────────────────────────────────────────────────── */
        function initInlineHppEdit() {
            if (!window.SO_CAN_MODIFY) return;

            const tableWrap = document.getElementById('opname-lines-table');
            if (!tableWrap) return;

            tableWrap.querySelectorAll('.so-inline-hpp').forEach(input => {
                input.addEventListener('input', function() {
                    let v = this.value.replace(/,/g, '.').replace(/[^0-9.]/g, '');
                    const parts = v.split('.');
                    if (parts.length > 2) v = parts[0] + '.' + parts.slice(1).join('');
                    this.value = v;
                    this.classList.remove('is-saved', 'is-error');
                });

                input.addEventListener('focus', function() {
                    this.select && this.select();
                });

                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.blur();
                    }
                    if (e.key === 'Escape') {
                        this.value = this.dataset.original ?? '';
                        this.blur();
                    }
                });

                input.addEventListener('blur', function() {
                    const raw = this.value.trim();
                    const newVal = raw === '' ? null : parseFloat(raw);
                    const original = this.dataset.original === '' ? null : parseFloat(this.dataset.original || '');

                    if (newVal === original) {
                        if (newVal !== null) this.value = formatHppForInput(newVal);
                        return;
                    }
                    if (newVal === null || !Number.isFinite(newVal) || newVal <= 0) {
                        this.value = this.dataset.original ?? '';
                        this.classList.add('is-error');
                        showSoError('HPP harus lebih besar dari 0.');
                        return;
                    }

                    saveInlineHpp(this, newVal);
                });
            });
        }

        function saveInlineHpp(input, newVal) {
            const saveUrl = input.dataset.saveUrl;
            if (!saveUrl) return;

            input.classList.remove('is-saved', 'is-error');
            input.classList.add('is-saving');

            const formData = new FormData();
            formData.append('_token', window.SO_CSRF || getCsrfToken());
            formData.append('unit_cost', String(newVal));

            fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.SO_CSRF || getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            })
            .then(async (res) => {
                input.classList.remove('is-saving');
                if (!res.ok) {
                    let msg = 'Gagal menyimpan HPP.';
                    try { const d = await res.json(); msg = d?.message || msg; } catch (e) {}
                    input.value = input.dataset.original ?? '';
                    input.classList.add('is-error');
                    input.title = msg;
                    showSoError(msg);
                    return;
                }

                const d = await res.json();
                if (d?.status === 'ok') {
                    const savedValue = Number(d.unit_cost ?? newVal);
                    input.dataset.original = String(savedValue);
                    input.value = formatHppForInput(savedValue);
                    input.classList.add('is-saved');
                    input.title = '';

                    const hidden = document.querySelector('.so-unit-cost-hidden[data-line-id="' + input.dataset.lineId + '"]');
                    if (hidden) hidden.value = String(savedValue);

                    const qtyInput = document.querySelector('.so-inline-qty[data-line-id="' + input.dataset.lineId + '"]');
                    if (qtyInput) qtyInput.dataset.unitCost = String(savedValue);

                    showSoSuccess(d.message || 'HPP berhasil disimpan.');
                    setTimeout(() => input.classList.remove('is-saved'), 2000);
                } else {
                    input.value = input.dataset.original ?? '';
                    input.classList.add('is-error');
                    input.title = d?.message || 'Gagal menyimpan HPP.';
                    showSoError(d?.message || 'Gagal menyimpan HPP.');
                }
            })
            .catch(() => {
                input.classList.remove('is-saving');
                input.value = input.dataset.original ?? '';
                input.classList.add('is-error');
                input.title = 'Koneksi error.';
                showSoError('Koneksi error saat menyimpan HPP.');
            });
        }

        function initDeleteLineAjax() {
            const tableWrap = document.getElementById('opname-lines-table');
            if (!tableWrap) return;

            const urlTemplate = tableWrap.dataset.deleteUrlTemplate;
            if (!urlTemplate) return;

            tableWrap.querySelectorAll('.js-delete-line').forEach(btn => {
                btn.addEventListener('click', function() {
                    const lineId = this.dataset.lineId;
                    if (!lineId) return;

                    if (!confirm('Hapus baris ini dari sesi opname?')) return;

                    const url = urlTemplate.replace('__LINE_ID__', lineId);

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: new URLSearchParams({
                                '_method': 'DELETE'
                            }),
                        })
                        .then(async (response) => {
                            if (!response.ok) {
                                let msg = 'Gagal menghapus item.';
                                try {
                                    const data = await response.json();
                                    if (data?.message) msg = data.message;
                                } catch (e) {}
                                showSoError(msg);
                                return null;
                            }
                            return response.json();
                        })
                        .then((data) => {
                            if (!data) return;
                            if (data.status === 'ok') {
                                const tr = btn.closest('tr');
                                if (tr) tr.remove();
                                renumberOpnameRows();
                                setTableMaxHeight10Rows();
                                showSoSuccess(data.message || 'Item berhasil dihapus dari opname.');
                            } else {
                                showSoError(data.message || 'Gagal menghapus item.');
                            }
                        })
                        .catch(() => showSoError('Terjadi kesalahan saat menghapus item.'));
                });
            });
        }
    </script>
@endpush
