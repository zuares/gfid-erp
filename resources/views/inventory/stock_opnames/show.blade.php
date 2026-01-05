{{-- resources/views/inventory/stock_opnames/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Stock Opname • ' . $opname->code)

@php
    use App\Models\StockOpname;
    use App\Models\InventoryAdjustment;

    $userRole = auth()->user()->role ?? null;
    $isOperating = $userRole === 'operating';
    $isAdmin = $userRole === 'admin';
    $isOwner = $userRole === 'owner';
    $isOpOrAdmin = $isOperating || $isAdmin;

    $isOpening = method_exists($opname, 'isOpening')
        ? $opname->isOpening()
        : $opname->type === StockOpname::TYPE_OPENING;

    /**
     * Adjustment yang sumbernya Stock Opname ini.
     * (Kalau controller sudah kirim $adjustment, pakai itu.)
     */
    $adjustment =
        $adjustment ??
        InventoryAdjustment::query()
            ->where('source_type', StockOpname::class)
            ->where('source_id', $opname->id)
            ->latest('id')
            ->first();

    // ===== Ringkasan qty & nilai selisih
    $totalPlusQty = 0.0;
    $totalMinusQty = 0.0; // disimpan negatif
    $totalPlusValue = 0.0; // disimpan positif
    $totalMinusValue = 0.0; // disimpan negatif

    foreach ($opname->lines as $line) {
        $diff = (float) $line->difference; // accessor (physical - system)
        $unitCost = (float) $line->effective_unit_cost; // accessor (HPP efektif)

        // Hitung nilai selisih berbasis HPP efektif
        $diffValue = 0.0;
        if (abs($diff) >= 0.0000001 && $unitCost > 0) {
            $diffValue = $diff * $unitCost;
        }

        if (abs($diff) < 0.0000001) {
            continue;
        }

        if ($diff > 0) {
            $totalPlusQty += $diff;
            $totalPlusValue += abs($diffValue);
        } else {
            $totalMinusQty += $diff; // negatif
            $totalMinusValue += $diffValue <= 0 ? $diffValue : -abs($diffValue); // negatif
        }
    }

    $totalLines = $opname->lines->count();
    $countedLines = $opname->lines->whereNotNull('physical_qty')->count();

    $statusClass = match ($opname->status) {
        StockOpname::STATUS_DRAFT => 'badge-status badge-status--draft',
        StockOpname::STATUS_COUNTING => 'badge-status badge-status--counting',
        StockOpname::STATUS_REVIEWED => 'badge-status badge-status--reviewed',
        StockOpname::STATUS_FINALIZED => 'badge-status badge-status--finalized',
        default => 'badge-status badge-status--draft',
    };

    $typeLabel = $isOpening ? 'Opening' : 'Periodic';
    $typeClass = $isOpening ? 'badge-type badge-type--opening' : 'badge-type badge-type--periodic';

    // Boleh edit (lanjut counting) selama masih draft/counting
    $canEdit = in_array($opname->status, [StockOpname::STATUS_DRAFT, StockOpname::STATUS_COUNTING], true);

    // Finalize hanya kalau REVIEWED dan role Owner
    $canFinalize = $opname->status === StockOpname::STATUS_REVIEWED && $isOwner;

    // ✅ Role rule: admin/operating tidak boleh lihat / buka adjustment
    $canSeeAdjustmentLink = !$isOpOrAdmin;

    // ✅ Siapa yang boleh tandai selesai hitung (hanya operating/admin) & belum ada adjustment
    $canMarkReviewed =
        in_array($opname->status, [StockOpname::STATUS_DRAFT, StockOpname::STATUS_COUNTING], true) &&
        in_array($userRole, ['operating', 'admin'], true) &&
        !$adjustment;

    // Badge "Counting selesai oleh ..."
    $hasReviewedInfo = $opname->status === StockOpname::STATUS_REVIEWED && $opname->reviewed_by && $opname->reviewed_at;

    // ===== Net total selisih (Qty & Nilai)
    $netQty = $totalPlusQty + $totalMinusQty;
    $netValue = $totalPlusValue + $totalMinusValue;

    $netQtyClass = $netQty < 0 ? 'diff-danger' : ($netQty > 0 ? 'diff-warning' : 'diff-success');
    $netValueClass = $netValue < 0 ? 'diff-danger' : ($netValue > 0 ? 'diff-warning' : 'diff-success');
@endphp

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .85rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(129, 140, 248, 0.12) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 60%);
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(15, 23, 42, 0.92) 0,
                    #020617 65%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.28);
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

        .subtle {
            color: rgba(100, 116, 139, 1);
            font-size: .85rem;
            margin: .2rem 0 0;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            border: 1px solid rgba(148, 163, 184, .22);
            background: rgba(15, 23, 42, 0.02);
            color: rgba(71, 85, 105, 1);
        }

        body[data-theme="dark"] .chip {
            background: rgba(148, 163, 184, 0.08);
            border-color: rgba(148, 163, 184, 0.18);
            color: rgba(226, 232, 240, .86);
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

        .badge-type {
            font-size: .65rem;
            padding: .12rem .45rem;
            border-radius: 999px;
            font-weight: 800;
        }

        .badge-type--periodic {
            background: rgba(59, 130, 246, 0.15);
            color: #1d4ed8;
        }

        .badge-type--opening {
            background: rgba(249, 115, 22, 0.16);
            color: #c2410c;
        }

        .badge-review {
            font-size: .68rem;
            padding: .16rem .55rem;
            border-radius: 999px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            margin-top: .25rem;
            border: 1px solid rgba(234, 179, 8, .45);
            background: rgba(251, 191, 36, .12);
            color: #854d0e;
        }

        .badge-review-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: currentColor;
        }

        .meta {
            font-size: .82rem;
            color: rgba(100, 116, 139, 1);
        }

        .text-mono {
            font-variant-numeric: tabular-nums;
        }

        .kv {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: .35rem .75rem;
            font-size: .92rem;
        }

        .kv .k {
            color: rgba(100, 116, 139, 1);
            font-weight: 600;
        }

        .kv .v {
            font-weight: 600;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .65rem;
            margin-top: .75rem;
        }

        .sum-card {
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .22);
            background: rgba(15, 23, 42, 0.01);
            padding: .75rem .85rem;
        }

        body[data-theme="dark"] .sum-card {
            background: rgba(148, 163, 184, 0.06);
        }

        .sum-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
            font-weight: 800;
            margin-bottom: .35rem;
        }

        .diff-plus {
            color: #16a34a;
            font-weight: 800;
        }

        .diff-minus {
            color: #dc2626;
            font-weight: 800;
        }

        .diff-danger {
            color: #b91c1c;
            font-weight: 800;
        }

        .diff-success {
            color: #15803d;
            font-weight: 800;
        }

        .diff-warning {
            color: #854d0e;
            font-weight: 800;
        }

        body[data-theme="dark"] .diff-danger {
            color: rgba(254, 202, 202, .98);
        }

        body[data-theme="dark"] .diff-success {
            color: rgba(134, 239, 172, .98);
        }

        body[data-theme="dark"] .diff-warning {
            color: rgba(253, 230, 138, .98);
        }

        /* ✅ DESKTOP TABLE: max-height 10 row + scroll + sticky solid thead */
        .table-wrap {
            margin-top: .65rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .22);
            overflow-x: auto;
            overflow-y: auto;
            max-height: 520px;
            /* fallback; JS akan hitung 10 row */
            background: rgba(248, 250, 252, .9);
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

            background: #fff !important;
            /* ✅ SOLID */
            color: rgba(100, 116, 139, 1);
        }

        body[data-theme="dark"] .table thead th {
            background: #0f172a !important;
            /* ✅ SOLID dark */
            color: #e5e7eb;
        }

        .badge-counted {
            font-size: .7rem;
            padding: .18rem .5rem;
            border-radius: 999px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .badge-counted--yes {
            background: rgba(22, 163, 74, 0.18);
            color: #15803d;
        }

        .badge-counted--no {
            background: rgba(148, 163, 184, 0.2);
            color: #475569;
        }

        /* ================= MOBILE SUMMARY (1 BARIS) ================= */
        .sum-row {
            display: none;
            margin-top: .75rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .22);
            background: rgba(15, 23, 42, 0.01);
            padding: .65rem .75rem;
        }

        body[data-theme="dark"] .sum-row {
            background: rgba(148, 163, 184, 0.06);
        }

        .sum-row .cell {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: .75rem;
            padding: .15rem 0;
            font-size: .92rem;
        }

        .sum-row .label {
            color: rgba(100, 116, 139, 1);
            font-weight: 900;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .sum-row .value {
            font-weight: 900;
            font-variant-numeric: tabular-nums;
            text-align: right;
        }

        /* ================= MOBILE TABLE: max-height 10 row + scroll + sticky solid thead ================= */
        .mobile-compact-table {
            display: none;
            margin-top: .65rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .22);

            overflow-x: auto;
            overflow-y: auto;
            /* ✅ scroll vertical */
            max-height: 520px;
            /* fallback; JS akan hitung 10 row */
            background: rgba(248, 250, 252, .9);
        }

        body[data-theme="dark"] .mobile-compact-table {
            background: rgba(15, 23, 42, 0.92);
            border-color: rgba(51, 65, 85, .9);
        }

        .mobile-compact-table .table {
            margin-bottom: 0;
            table-layout: fixed;
            width: 100%;
        }

        .mobile-compact-table thead th {
            position: sticky;
            top: 0;
            z-index: 3;

            background: #fff !important;
            /* ✅ SOLID */
            color: rgba(100, 116, 139, 1);
            text-transform: uppercase;
            letter-spacing: .06em;
            white-space: nowrap;
            font-size: .7rem;
        }

        body[data-theme="dark"] .mobile-compact-table thead th {
            background: #0f172a !important;
            /* ✅ SOLID dark */
            color: #e5e7eb;
        }

        .mobile-compact-table th,
        .mobile-compact-table td {
            padding: .45rem .5rem;
            vertical-align: top;
        }

        .m-col-no {
            width: 42px;
        }

        .m-col-item {
            width: auto;
        }

        .m-col-diff {
            width: 102px;
        }

        .m-item-title {
            font-weight: 900;
            line-height: 1.12;
            word-break: break-word;
        }

        .m-item-sub {
            font-size: .8rem;
            color: rgba(100, 116, 139, 1);
            margin-top: .1rem;
            word-break: break-word;
        }

        .m-badges {
            margin-top: .35rem;
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }

        .mini-badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .12rem .45rem;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 900;
            border: 1px solid rgba(148, 163, 184, .22);
            background: rgba(15, 23, 42, 0.02);
            color: rgba(71, 85, 105, 1);
            white-space: nowrap;
        }

        body[data-theme="dark"] .mini-badge {
            background: rgba(148, 163, 184, 0.08);
            border-color: rgba(148, 163, 184, 0.18);
            color: rgba(226, 232, 240, .88);
        }

        .mini-badge .k {
            opacity: .75;
            font-weight: 900;
        }

        .mini-badge .v {
            font-variant-numeric: tabular-nums;
            font-weight: 900;
        }

        .tone-danger {
            background: rgba(239, 68, 68, 0.12);
            border-color: rgba(239, 68, 68, 0.20);
            color: #b91c1c;
        }

        .tone-success {
            background: rgba(22, 163, 74, 0.14);
            border-color: rgba(22, 163, 74, 0.22);
            color: #15803d;
        }

        .tone-warning {
            background: rgba(234, 179, 8, 0.16);
            border-color: rgba(234, 179, 8, 0.26);
            color: #854d0e;
        }

        body[data-theme="dark"] .tone-danger {
            background: rgba(239, 68, 68, 0.16);
            border-color: rgba(239, 68, 68, 0.24);
            color: rgba(254, 202, 202, .95);
        }

        body[data-theme="dark"] .tone-success {
            background: rgba(22, 163, 74, 0.18);
            border-color: rgba(22, 163, 74, 0.26);
            color: rgba(134, 239, 172, .95);
        }

        body[data-theme="dark"] .tone-warning {
            background: rgba(234, 179, 8, 0.20);
            border-color: rgba(234, 179, 8, 0.28);
            color: rgba(253, 230, 138, .95);
        }

        @media (max-width: 991.98px) {
            .kv {
                grid-template-columns: 130px 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .55rem;
            }

            .subtle {
                display: none;
            }

            .summary-grid {
                display: none;
            }

            .sum-row {
                display: block;
            }

            .table-wrap {
                display: none;
            }

            .mobile-compact-table {
                display: block;
            }

            .kv {
                grid-template-columns: 110px 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="page-head">
            <div>
                <a href="{{ route('inventory.stock_opnames.index') }}" class="btn btn-sm btn-link px-0 mb-1">
                    ← Kembali
                </a>

                <h1 class="page-title">
                    {{ $opname->code }}
                    <span class="{{ $typeClass }} ms-1">{{ $typeLabel }}</span>
                    <span class="{{ $statusClass }} ms-1">{{ ucfirst($opname->status) }}</span>

                    @if ($adjustment && $canSeeAdjustmentLink)
                        <span class="chip ms-1">
                            Adj: {{ $adjustment->code }}
                        </span>
                    @endif
                </h1>

                <div class="subtle">
                    {{ $opname->warehouse?->code ?? '-' }} — {{ $opname->warehouse?->name ?? '-' }}
                </div>

                @if ($hasReviewedInfo)
                    <div class="badge-review mt-2">
                        <span class="badge-review-dot"></span>
                        Counting selesai oleh
                        <span class="fw-semibold">{{ $opname->reviewer?->name ?? '-' }}</span>
                        <span class="text-mono" style="font-size:.72rem;">
                            {{ $opname->reviewed_at?->format('d M Y H:i') ?? '-' }}
                        </span>
                    </div>
                @endif
            </div>

            <div class="text-end">
                @if (!$adjustment && $canEdit)
                    <div class="d-flex flex-wrap justify-content-end gap-2 mb-1">
                        <a href="{{ route('inventory.stock_opnames.edit', $opname) }}"
                            class="btn btn-sm btn-outline-primary">
                            Lanjut Counting
                        </a>

                        @if ($canMarkReviewed)
                            <form action="{{ route('inventory.stock_opnames.update', $opname) }}" method="POST"
                                onsubmit="return confirm('Tandai sesi ini sebagai selesai hitung? Pastikan semua item sudah di-count.');"
                                class="d-inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="mark_reviewed" value="1">

                                <button type="submit" class="btn btn-sm btn-primary">
                                    Simpan &amp; Selesai Hitung
                                </button>
                            </form>
                        @endif
                    </div>
                @endif

                @if ($canFinalize)
                    <form action="{{ route('inventory.stock_opnames.finalize', $opname) }}" method="POST" class="mt-2"
                        onsubmit="return confirm('Yakin finalize stock opname ini? Stok gudang akan dikoreksi sesuai hasil fisik.');">
                        @csrf
                        <input type="hidden" name="reason" value="Stock Opname {{ $opname->code }}">
                        <button type="submit" class="btn btn-sm btn-success">
                            {{ $isOpening ? 'Finalize Opening' : 'Finalize & Buat Adjustment' }}
                        </button>
                    </form>
                @elseif ($opname->status === StockOpname::STATUS_FINALIZED)
                    @if ($adjustment && $canSeeAdjustmentLink)
                        <a href="{{ route('inventory.adjustments.show', $adjustment) }}"
                            class="btn btn-sm btn-outline-secondary mt-2">
                            Buka Adjustment →
                        </a>
                    @endif
                @endif
            </div>
        </div>

        @if ($errors->has('mark_reviewed'))
            <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:.82rem;">
                {{ $errors->first('mark_reviewed') }}
            </div>
        @endif

        {{-- INFO CARD --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="kv">
                            <div class="k">Tanggal</div>
                            <div class="v">{{ $opname->date?->format('d M Y') ?? '-' }}</div>

                            <div class="k">Gudang</div>
                            <div class="v">
                                {{ $opname->warehouse?->code ?? '-' }}
                                <div class="meta">{{ $opname->warehouse?->name }}</div>
                            </div>

                            <div class="k">Dibuat</div>
                            <div class="v">
                                {{ $opname->creator?->name ?? '-' }}
                                <div class="meta">{{ $opname->created_at?->format('d M Y H:i') }}</div>
                            </div>

                            @if ($opname->finalized_at)
                                <div class="k">Finalized</div>
                                <div class="v">
                                    {{ $opname->finalizer?->name ?? '-' }}
                                    <div class="meta">{{ $opname->finalized_at?->format('d M Y H:i') }}</div>
                                </div>
                            @endif

                            @if ($adjustment && $canSeeAdjustmentLink)
                                <div class="k">Adjustment</div>
                                <div class="v">
                                    <a href="{{ route('inventory.adjustments.show', $adjustment) }}"
                                        class="text-decoration-none">
                                        {{ $adjustment->code }}
                                    </a>
                                    <div class="meta">Status: {{ ucfirst($adjustment->status) }}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        @if ($opname->notes)
                            <div class="sum-card" style="height: 100%;">
                                <div class="sum-label">Catatan</div>
                                <div style="font-size:.92rem;">
                                    {!! nl2br(e($opname->notes)) !!}
                                </div>
                            </div>
                        @else
                            <div class="sum-card" style="height: 100%;">
                                <div class="sum-label">Progress</div>
                                <div style="font-size:.92rem;">
                                    <span class="fw-semibold">{{ $countedLines }}</span> / {{ $totalLines }} item sudah
                                    diinput.
                                    @if ($opname->status === StockOpname::STATUS_COUNTING)
                                        <div class="meta mt-1">Isi qty fisik lalu gunakan "Simpan &amp; Selesai Hitung".
                                        </div>
                                    @elseif($opname->status === StockOpname::STATUS_REVIEWED)
                                        <div class="meta mt-1">Counting selesai, menunggu Finalize dari Owner.</div>
                                    @elseif($opname->status === StockOpname::STATUS_FINALIZED)
                                        <div class="meta mt-1">Sesi sudah difinalkan & stok terkoreksi.</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- SUMMARY DESKTOP --}}
                <div class="summary-grid">
                    <div class="sum-card">
                        <div class="sum-label">Selisih Qty</div>
                        <div style="font-size:.95rem;">
                            <div>
                                Lebih:
                                <span class="text-mono diff-plus">+{{ number_format($totalPlusQty, 2) }}</span>
                            </div>
                            <div>
                                Kurang:
                                <span class="text-mono diff-minus">{{ number_format($totalMinusQty, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="sum-card">
                        <div class="sum-label">Nilai Selisih (Rp)</div>
                        <div style="font-size:.95rem;">
                            <div>
                                Lebih:
                                <span class="text-mono diff-plus">+Rp
                                    {{ number_format($totalPlusValue, 0, ',', '.') }}</span>
                            </div>
                            <div>
                                Kurang:
                                <span class="text-mono diff-minus">
                                    Rp
                                    {{ $totalMinusValue < 0 ? '-' : '' }}{{ number_format(abs($totalMinusValue), 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="sum-card">
                        <div class="sum-label">Total Selisih</div>
                        <div style="font-size:.95rem;">
                            <div>
                                Qty:
                                <span class="text-mono {{ $netQtyClass }}">
                                    @if (abs($netQty) < 0.0000001)
                                        0.00
                                    @else
                                        {{ $netQty > 0 ? '+' : '' }}{{ number_format($netQty, 2) }}
                                    @endif
                                </span>
                            </div>
                            <div>
                                Nilai:
                                <span class="text-mono {{ $netValueClass }}">
                                    @if (abs($netValue) < 0.0000001)
                                        Rp 0
                                    @else
                                        {{ $netValue > 0 ? '+Rp' : '-Rp' }}
                                        {{ number_format(abs($netValue), 0, ',', '.') }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SUMMARY MOBILE --}}
                <div class="sum-row">
                    <div class="cell">
                        <div class="label">Selisih Qty</div>
                        <div class="value">
                            <span class="diff-plus">+{{ number_format($totalPlusQty, 2) }}</span>
                            <span style="opacity:.55; padding:0 .35rem;">|</span>
                            <span class="diff-minus">{{ number_format($totalMinusQty, 2) }}</span>
                        </div>
                    </div>
                    <div class="cell">
                        <div class="label">Nilai (Rp)</div>
                        <div class="value">
                            <span class="diff-plus">+Rp {{ number_format($totalPlusValue, 0, ',', '.') }}</span>
                            <span style="opacity:.55; padding:0 .35rem;">|</span>
                            <span class="diff-minus">
                                Rp
                                {{ $totalMinusValue < 0 ? '-' : '' }}{{ number_format(abs($totalMinusValue), 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- TABLE DETAIL --}}
        <div class="card card-main">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Detail Per Item</h2>
                    <span class="chip">{{ $countedLines }} / {{ $totalLines }} dihitung</span>
                </div>

                {{-- DESKTOP TABLE --}}
                <div class="table-wrap" id="so-show-table-wrap">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Item</th>
                                <th class="text-end">Sistem</th>
                                <th class="text-end">Fisik</th>
                                <th class="text-end">Selisih</th>
                                <th class="text-end">HPP</th>
                                <th class="text-end">Nilai (Rp)</th>
                                <th class="text-center">Hitung</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($opname->lines as $index => $line)
                                @php
                                    $system = (float) ($line->system_qty ?? 0);
                                    $physical = (float) ($line->physical_qty ?? 0);
                                    $diff = (float) $line->difference;
                                    $unitCost = (float) $line->effective_unit_cost;

                                    $diffValue = 0.0;
                                    if (abs($diff) >= 0.0000001 && $unitCost > 0) {
                                        $diffValue = $diff * $unitCost;
                                    }

                                    $diffText = $diff > 0 ? '+' . number_format($diff, 2) : number_format($diff, 2);

                                    $counted = !is_null($line->physical_qty) || ($line->is_counted ?? false);
                                    $countedClass = $counted
                                        ? 'badge-counted badge-counted--yes'
                                        : 'badge-counted badge-counted--no';

                                    $showDiff = !is_null($line->physical_qty);
                                    $showValue = !is_null($line->physical_qty) && $unitCost > 0;

                                    if (!is_null($line->physical_qty)) {
                                        $diffToneClass =
                                            $diff < 0 ? 'diff-danger' : ($diff > 0 ? 'diff-warning' : 'diff-success');
                                    } else {
                                        $diffToneClass = '';
                                    }
                                @endphp

                                <tr>
                                    <td>{{ $index + 1 }}</td>

                                    <td>
                                        <div class="fw-semibold">{{ $line->item?->code ?? '-' }}</div>
                                        <div class="meta">{{ $line->item?->name ?? '' }}</div>
                                    </td>

                                    <td class="text-end text-mono">{{ number_format($system, 2) }}</td>

                                    <td class="text-end text-mono">
                                        @if (!is_null($line->physical_qty))
                                            {{ number_format($physical, 2) }}
                                        @else
                                            <span class="meta">-</span>
                                        @endif
                                    </td>

                                    <td class="text-end text-mono {{ $showDiff ? $diffToneClass : '' }}">
                                        @if ($showDiff)
                                            {{ $diffText }}
                                        @else
                                            <span class="meta">-</span>
                                        @endif
                                    </td>

                                    <td class="text-end text-mono">
                                        @if ($unitCost > 0)
                                            {{ number_format($unitCost, 2) }}
                                        @else
                                            <span class="meta">-</span>
                                        @endif
                                    </td>

                                    <td class="text-end text-mono {{ $showValue ? $diffToneClass : '' }}">
                                        @if ($showValue)
                                            @if (abs($diffValue) < 0.0000001)
                                                Rp 0
                                            @else
                                                {{ $diffValue > 0 ? '+Rp' : '-Rp' }}
                                                {{ number_format(abs($diffValue), 0, ',', '.') }}
                                            @endif
                                        @else
                                            <span class="meta">-</span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        <span class="{{ $countedClass }}">{{ $counted ? 'Sudah' : 'Belum' }}</span>
                                    </td>

                                    <td>
                                        <span style="font-size:.85rem;">{{ $line->notes }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="fw-semibold">Belum ada item</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE TABLE (3 KOLOM) --}}
                <div class="mobile-compact-table" id="so-show-mobile-table-wrap">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th class="m-col-no">#</th>
                                <th class="m-col-item">Item</th>
                                <th class="m-col-diff text-end">Selisih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($opname->lines as $index => $line)
                                @php
                                    $system = (float) ($line->system_qty ?? 0);
                                    $physical = (float) ($line->physical_qty ?? 0);
                                    $diff = (float) $line->difference;
                                    $unitCost = (float) $line->effective_unit_cost;

                                    $diffValue = 0.0;
                                    if (abs($diff) >= 0.0000001 && $unitCost > 0) {
                                        $diffValue = $diff * $unitCost;
                                    }

                                    $showDiff = !is_null($line->physical_qty) && abs($diff) > 0.0000001;
                                    $showValue = !is_null($line->physical_qty) && $unitCost > 0;

                                    $counted = !is_null($line->physical_qty) || ($line->is_counted ?? false);
                                    $countedClass = $counted
                                        ? 'badge-counted badge-counted--yes'
                                        : 'badge-counted badge-counted--no';

                                    if (!is_null($line->physical_qty)) {
                                        $tone =
                                            $diff < 0 ? 'tone-danger' : ($diff > 0 ? 'tone-warning' : 'tone-success');
                                    } else {
                                        $tone = 'tone-success';
                                    }

                                    $diffText = $diff > 0 ? '+' . number_format($diff, 2) : number_format($diff, 2);

                                    $itemCode = $line->item?->code ?? '-';
                                    $itemName = $line->item?->name ?? '';
                                @endphp

                                <tr>
                                    <td class="text-mono">{{ $index + 1 }}</td>

                                    <td>
                                        <div class="m-item-title">{{ $itemCode }}</div>
                                        @if ($itemName)
                                            <div class="m-item-sub">{{ $itemName }}</div>
                                        @endif

                                        <div class="m-badges">
                                            <span class="mini-badge">
                                                <span class="k">Sys</span>
                                                <span class="v">{{ number_format($system, 2) }}</span>
                                            </span>

                                            <span class="mini-badge">
                                                <span class="k">Fisik</span>
                                                <span class="v">
                                                    @if (!is_null($line->physical_qty))
                                                        {{ number_format($physical, 2) }}
                                                    @else
                                                        -
                                                    @endif
                                                </span>
                                            </span>

                                            <span class="{{ $countedClass }}">{{ $counted ? 'Sudah' : 'Belum' }}</span>

                                            <span class="mini-badge {{ $tone }}">
                                                <span class="k">Nilai</span>
                                                <span class="v">
                                                    @if ($showValue)
                                                        @if (abs($diffValue) < 0.0000001)
                                                            Rp 0
                                                        @else
                                                            {{ $diffValue > 0 ? '+Rp' : '-Rp' }}
                                                            {{ number_format(abs($diffValue), 0, ',', '.') }}
                                                        @endif
                                                    @else
                                                        -
                                                    @endif
                                                </span>
                                            </span>
                                        </div>
                                    </td>

                                    <td class="text-end text-mono">
                                        <span class="mini-badge {{ $tone }}" style="justify-content:flex-end;">
                                            <span class="v">
                                                @if (!is_null($line->physical_qty))
                                                    {{ $showDiff ? $diffText : '0.00' }}
                                                @else
                                                    -
                                                @endif
                                            </span>
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4">
                                        <div class="fw-semibold">Belum ada item</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setMaxHeight10RowsForShowTables();
            window.addEventListener('resize', debounce(setMaxHeight10RowsForShowTables, 150));
        });

        function debounce(fn, wait) {
            let t = null;
            return function() {
                const ctx = this,
                    args = arguments;
                clearTimeout(t);
                t = setTimeout(function() {
                    fn.apply(ctx, args);
                }, wait || 150);
            };
        }

        /**
         * ✅ max-height agar muat ~10 baris (dinamis) untuk:
         * - Desktop table (#so-show-table-wrap)
         * - Mobile compact table (#so-show-mobile-table-wrap)
         */
        function setMaxHeight10RowsForShowTables() {
            applyMaxHeight10Rows('#so-show-table-wrap');
            applyMaxHeight10Rows('#so-show-mobile-table-wrap');
        }

        function applyMaxHeight10Rows(selector) {
            const wrap = document.querySelector(selector);
            if (!wrap) return;

            const table = wrap.querySelector('table');
            if (!table) return;

            const thead = table.querySelector('thead');
            const firstRow = table.querySelector('tbody tr');
            if (!firstRow) return;

            const rowH = firstRow.getBoundingClientRect().height || 38;
            const headH = thead ? (thead.getBoundingClientRect().height || 34) : 34;

            // 10 row + header + padding kecil
            const maxH = Math.ceil((rowH * 10) + headH + 8);

            wrap.style.maxHeight = maxH + 'px';
            wrap.style.overflowY = 'auto';
        }
    </script>
@endpush
