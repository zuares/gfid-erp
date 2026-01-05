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
    $countedLines = $linesCollection->whereNotNull('physical_qty')->count();
    $notCounted = max($totalLines - $countedLines, 0);

    /**
     * ✅ URUTAN: terbaru berada di urutan terakhir
     * (terlama dulu, terbaru paling bawah)
     */
    $lines = $linesCollection
        ->sortBy(function ($line) {
            return $line->updated_at ?? ($line->created_at ?? $line->id);
        })
        ->values();

    /**
     * ✅ line terakhir (untuk highlight soft setelah add/update)
     */
    $lastLine = $lines->last();
    $lastLineId = $lastLine?->id;
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
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(148, 163, 184, 0.07) 30%,
                    #f9fafb 70%);
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.26) 0,
                    rgba(15, 23, 42, 1) 55%);
        }

        .card-main {
            background: var(--card);
            border-radius: var(--so-card-radius);
            border: 1px solid var(--so-border);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.03);
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
            background: rgba(248, 250, 252, .9);
        }

        body[data-theme="dark"] .table-wrap {
            background: rgba(15, 23, 42, 0.92);
            border-color: rgba(51, 65, 85, .9);
        }

        .table thead th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
            background: rgba(15, 23, 42, 0.02);
            white-space: nowrap;
        }

        body[data-theme="dark"] .table thead th {
            background: rgba(15, 23, 42, 0.8);
            color: #e5e7eb;
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

        /* ✅ Highlight soft untuk row terbaru */
        /* ✅ Highlight soft tapi KONTRAS (light & dark) */
        .so-row-highlight {
            position: relative;
            animation: soRowPulseStrong 1.8s ease-out 1;
        }

        @keyframes soRowPulseStrong {
            0% {
                background-color: rgba(59, 130, 246, 0.00);
                box-shadow:
                    inset 0 0 0 0 rgba(59, 130, 246, 0),
                    0 0 0 0 rgba(59, 130, 246, 0);
            }

            25% {
                background-color: rgba(59, 130, 246, 0.16);
                /* 🔥 lebih kontras */
                box-shadow:
                    inset 0 0 0 2px rgba(59, 130, 246, 0.35),
                    0 4px 14px rgba(59, 130, 246, 0.25);
            }

            100% {
                background-color: transparent;
                box-shadow:
                    inset 0 0 0 0 rgba(59, 130, 246, 0),
                    0 0 0 0 rgba(59, 130, 246, 0);
            }
        }

        /* 🌙 Dark mode tuning */
        body[data-theme="dark"] .so-row-highlight {
            animation: soRowPulseStrongDark 1.8s ease-out 1;
        }

        @keyframes soRowPulseStrongDark {
            0% {
                background-color: rgba(59, 130, 246, 0.00);
                box-shadow:
                    inset 0 0 0 0 rgba(59, 130, 246, 0),
                    0 0 0 0 rgba(59, 130, 246, 0);
            }

            25% {
                background-color: rgba(59, 130, 246, 0.28);
                /* 🔥 kontras di dark */
                box-shadow:
                    inset 0 0 0 2px rgba(147, 197, 253, 0.45),
                    0 6px 18px rgba(59, 130, 246, 0.35);
            }

            100% {
                background-color: transparent;
                box-shadow:
                    inset 0 0 0 0 rgba(59, 130, 246, 0),
                    0 0 0 0 rgba(59, 130, 246, 0);
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

            <div class="text-end">
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
            <input type="hidden" name="force_auto_fill" id="force_auto_fill" value="0">

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
                                        <div class="fw-semibold">
                                            {{ $opname->warehouse?->code ?? '-' }}
                                        </div>
                                        <div class="meta">
                                            {{ $opname->warehouse?->name }}
                                        </div>
                                    </div>

                                    <div class="col-md-6 so-meta--hide-mobile">
                                        <div class="pill-label mb-1">Dokumen</div>
                                        <div class="text-mono fw-semibold">{{ $opname->code }}</div>

                                        <div class="pill-label mt-3 mb-1">Catatan</div>
                                        <textarea name="notes" class="form-control form-control-sm" rows="3" placeholder="Catatan…"
                                            @if ($isReadonly) readonly @endif>{{ old('notes', $opname->notes) }}</textarea>
                                    </div>
                                @else
                                    <div class="col-md-4">
                                        <div class="pill-label mb-1">Kode</div>
                                        <div class="text-mono fw-semibold">{{ $opname->code }}</div>

                                        <div class="pill-label mt-3 mb-1">Tanggal</div>
                                        <div style="font-size:.9rem;">
                                            {{ $opname->date?->format('d M Y') ?? '-' }}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="pill-label mb-1">Gudang</div>
                                        <div class="fw-semibold">
                                            {{ $opname->warehouse?->code ?? '-' }}
                                        </div>
                                        <div class="meta">
                                            {{ $opname->warehouse?->name }}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
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
                                    {{-- CSRF untuk AJAX --}}
                                    <input type="hidden" id="openingAddTokenMobile" value="{{ csrf_token() }}">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">

                                    <div class="opening-add-row">
                                        <div id="opening-item-suggest-mobile">
                                            <label class="pill-label mb-1">Item</label>
                                            <x-item-suggest idName="item_id" :idValue="old('item_id')" :displayValue="''"
                                                placeholder="Kode / nama" :autofocus="false" :autoSelectFirst="false"
                                                :maxResults="3" />
                                        </div>

                                        <div>
                                            <label class="pill-label mb-1">Qty Fisik</label>
                                            <x-number-input name="physical_qty" :value="old('physical_qty')" mode="integer"
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
                                        {{ $isOpening ? 'Tambah item saldo awal' : 'Tambah item opname' }}
                                    </div>
                                    <span class="chip">{{ $isOpening ? 'Mode Opening' : 'Mode Periodik' }}</span>
                                </div>

                                <div id="openingAddDesktop"
                                    data-action="{{ route('inventory.stock_opnames.lines.store', $opname) }}">
                                    {{-- CSRF untuk AJAX --}}
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
                                            <x-number-input name="physical_qty" :value="old('physical_qty')" mode="integer"
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
                                    {{ $isOpening ? 'Saldo awal per item' : 'Hasil hitung fisik per item' }}
                                </div>
                                <span class="chip">{{ $countedLines }} / {{ $totalLines }} terisi</span>
                            </div>

                            <div class="table-wrap" id="opname-lines-table"
                                data-delete-url-template="{{ route('inventory.stock_opnames.lines.destroy', ['stockOpname' => $opname, 'line' => '__LINE_ID__']) }}"
                                data-last-line-id="{{ $lastLineId ?? '' }}">
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

                                                $rawSystemQty = $line->system_qty ?? 0;

                                                $rawPhysical = old(
                                                    $inputNamePrefix . '.physical_qty',
                                                    $line->physical_qty,
                                                );

                                                $hasPhysicalValue = $rawPhysical !== null && $rawPhysical !== '';
                                                if ($hasPhysicalValue) {
                                                    $rawPhysical = (float) $rawPhysical;
                                                }

                                                if (!$isOpening && !$hasPhysicalValue) {
                                                    $rawPhysical = 0;
                                                    $hasPhysicalValue = true;
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

                                                $fallbackUnitCost = null;
                                                if (
                                                    !$hasUnitCostValue &&
                                                    $line->item &&
                                                    $line->item->base_unit_cost > 0
                                                ) {
                                                    $fallbackUnitCost = (float) $line->item->base_unit_cost;
                                                }

                                                $effectiveUnitCost = $hasUnitCostValue
                                                    ? $rawUnitCost
                                                    : $fallbackUnitCost;

                                                $rowClasses = [];
                                                $showNotCountedBadge = false;
                                                if ($isOpening && !$hasPhysicalValue) {
                                                    $rowClasses[] = 'so-row-not-counted';
                                                    $showNotCountedBadge = true;
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

                                                <td class="text-end">
                                                    @if ($isOpening)
                                                        @if ($hasPhysicalForDisplay)
                                                            <span
                                                                class="text-mono">{{ number_format($rawPhysical, 2) }}</span>
                                                            <input type="hidden"
                                                                name="{{ $inputNamePrefix }}[physical_qty]"
                                                                value="{{ $rawPhysical }}">
                                                        @else
                                                            <span class="meta">-</span>
                                                        @endif
                                                    @else
                                                        <span
                                                            class="text-mono">{{ number_format($rawPhysical ?? 0, 2) }}</span>
                                                        <input type="hidden" name="{{ $inputNamePrefix }}[physical_qty]"
                                                            value="{{ $rawPhysical ?? 0 }}">
                                                    @endif
                                                </td>

                                                <td class="text-end text-mono col-diff d-none d-md-table-cell">
                                                    @if ($hasPhysicalForDisplay)
                                                        <span class="{{ $diffClass }}">{{ $diffDisplay }}</span>
                                                    @else
                                                        <span class="meta">-</span>
                                                    @endif
                                                </td>

                                                <input type="hidden" name="{{ $inputNamePrefix }}[unit_cost]"
                                                    value="{{ $effectiveUnitCost !== null ? $effectiveUnitCost : '' }}">

                                                @if ($isOpening && !$isOpOrAdmin)
                                                    <td class="text-end col-unit">
                                                        @if ($effectiveUnitCost && $effectiveUnitCost > 0)
                                                            <span class="text-mono {{ $hasUnitCostValue ? '' : 'meta' }}">
                                                                {{ number_format($effectiveUnitCost, 2) }}
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
                                                    <td class="text-end">
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
                                        Mode Periodik • Tambah / edit Qty Fisik. Item tanpa input akan dianggap Qty Fisik =
                                        0.
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
        // true jika mode Opening, false jika Periodik
        window.IS_OPENING_MODE = @json($isOpening);
    </script>

    <script>
        let duplicateItemModalInstance = null;
        let pendingOpeningSubmit = null;

        document.addEventListener('DOMContentLoaded', function() {
            initDuplicateItemModal();

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

            // nomor rapi saat load awal
            renumberOpnameRows();

            // ✅ highlight row terakhir setelah reload (jika barusan add/update)
            highlightLastRowIfNeeded();

            const soForm = document.getElementById('soUpdateForm');
            const markReviewedEl = document.getElementById('mark_reviewed');
            const forceAutoFillEl = document.getElementById('force_auto_fill');

            if (soForm && markReviewedEl && forceAutoFillEl) {
                const strictBtn = document.querySelector('[data-action="finish-counting-strict"]');
                if (strictBtn) {
                    strictBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        markReviewedEl.value = '1';
                        forceAutoFillEl.value = '0';
                        soForm.submit();
                    });
                }
            }
        });

        /**
         * ✅ UPDATE PENOMORAN: kolom "#" selalu 1..N
         */
        function renumberOpnameRows() {
            const tbody = document.querySelector('#opname-lines-table tbody');
            if (!tbody) return;

            const rows = tbody.querySelectorAll('tr');
            rows.forEach((tr, idx) => {
                const firstCell = tr.querySelector('td');
                if (firstCell) firstCell.textContent = String(idx + 1);
            });
        }

        /**
         * ✅ Highlight soft untuk baris terakhir (terbaru)
         * Dipicu hanya kalau sebelumnya kita set flag di sessionStorage (set saat add/update OK).
         */
        function highlightLastRowIfNeeded() {
            try {
                const flag = sessionStorage.getItem('so_opening_highlight_last');
                if (!flag) return;

                sessionStorage.removeItem('so_opening_highlight_last');

                const tableWrap = document.getElementById('opname-lines-table');
                if (!tableWrap) return;

                const lastId = tableWrap.dataset.lastLineId;
                if (!lastId) return;

                const row = tableWrap.querySelector('tr[data-line-id="' + lastId + '"]');
                if (!row) return;

                // scroll ke bawah supaya row terlihat (halus)
                row.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                // kasih class highlight
                row.classList.add('so-row-highlight');

                // bersihkan class setelah animasi selesai
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
            const submitBtn = document.querySelector(opts.submitBtnSelector);

            const updateExistingInput = rootEl.querySelector('input[name="update_existing"]');

            if (itemSuggestInput && window.innerWidth >= 768 && opts.rootSelector === '#openingAddDesktop') {
                itemSuggestInput.focus();
                itemSuggestInput.select && itemSuggestInput.select();
            }

            // Enter di item -> pindah fokus ke qty
            if (itemSuggestInput && qtyInput) {
                itemSuggestInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        qtyInput.focus();
                        qtyInput.select && qtyInput.select();
                    }
                });
            }

            // Enter di qty -> submit
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
                alert('Pilih item terlebih dahulu.');
                itemSuggestInput && itemSuggestInput.focus();
                return;
            }
            if (!qtyField || qtyField.value.trim() === '') {
                alert('Isi Qty Fisik terlebih dahulu.');
                qtyField && qtyField.focus();
                qtyField && qtyField.select && qtyField.select();
                return;
            }

            const itemId = itemIdField.value;
            const newQtyValue = qtyField.value;

            if (existingIds.has(itemId)) {
                // Periodik -> langsung update; Opening -> pakai modal
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
                    if (qtyDiffEl) {
                        const prefix = diff > 0 ? '+' : '';
                        qtyDiffEl.textContent = prefix + formatNumberForDisplay(diff);
                    }

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
                alert('URL penyimpanan tidak ditemukan.');
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
                        alert(msg);
                        return null;
                    }
                    return response.json();
                })
                .then((data) => {
                    if (!data) return;
                    if (data.status === 'ok') {
                        try {
                            sessionStorage.setItem('so_opening_focus_back', '1');
                            // ✅ flag untuk highlight baris terakhir setelah reload
                            sessionStorage.setItem('so_opening_highlight_last', '1');
                        } catch (e) {}
                        window.location.reload();
                    } else {
                        alert(data.message || 'Gagal menyimpan item.');
                    }
                })
                .catch(() => alert('Terjadi kesalahan saat menyimpan item.'));
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
                                alert(msg);
                                return null;
                            }
                            return response.json();
                        })
                        .then((data) => {
                            if (!data) return;
                            if (data.status === 'ok') {
                                const tr = btn.closest('tr');
                                if (tr) tr.remove();

                                // ✅ renumber setelah delete
                                renumberOpnameRows();
                            } else {
                                alert(data.message || 'Gagal menghapus item.');
                            }
                        })
                        .catch(() => alert('Terjadi kesalahan saat menghapus item.'));
                });
            });
        }
    </script>
@endpush
