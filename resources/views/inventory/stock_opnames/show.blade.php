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

    $adjustment =
        $adjustment ??
        InventoryAdjustment::query()
            ->where('source_type', StockOpname::class)
            ->where('source_id', $opname->id)
            ->latest('id')
            ->first();

    // Progress global (all lines)
    $totalLinesGlobal = $opname->lines->count();
    $countedLinesGlobal = $opname->lines->where('is_counted', true)->count();
    $notCountedGlobal = max($totalLinesGlobal - $countedLinesGlobal, 0);

    // Filters
    $filters = $filters ?? [
        'q' => request('q', ''),
        'counted' => request('counted', 'all'),
        'diff_only' => request()->boolean('diff_only'),
        'diff_sign' => request('diff_sign', 'all'),
        'sort' => request('sort', 'item'),
        'dir' => request('dir', 'asc'),
    ];

    $lines = $lines ?? $opname->lines;

    // Summary filtered (first render)
    $totalPlusQty = 0.0;
    $totalMinusQty = 0.0;
    $totalPlusValue = 0.0;
    $totalMinusValue = 0.0;
    $totalSystemValue = 0.0;
    $totalPhysicalValue = 0.0;
    $missingCostCount = 0;

    foreach ($lines as $line) {
        $system = (float)($line->system_qty ?? 0);
        $physical = $line->physical_qty !== null ? (float)$line->physical_qty : null;
        $unitCost = (float)($line->unit_cost ?? 0);
        if ($unitCost <= 0) $unitCost = (float)($line->item->hpp ?? 0);
        if ($unitCost <= 0) $unitCost = (float)($line->effective_unit_cost ?? 0);

        if ($unitCost > 0) {
            $totalSystemValue += $system * $unitCost;
            if ($physical !== null) {
                $totalPhysicalValue += $physical * $unitCost;
            }
        } else {
            $missingCostCount++;
        }

        if (!($line->is_counted ?? false)) continue;
        if ($physical === null) continue;

        $diff = (float)($line->difference_qty ?? ($physical - $system));
        if (abs($diff) < 0.0000001) continue;

        $diffValue = $unitCost > 0 ? $diff * $unitCost : 0.0;

        if ($diff > 0) {
            $totalPlusQty += $diff;
            $totalPlusValue += abs($diffValue);
        } else {
            $totalMinusQty += $diff;
            $totalMinusValue += $diffValue <= 0 ? $diffValue : -abs($diffValue);
        }
    }

    $netQty = $totalPlusQty + $totalMinusQty;
    $netValue = $totalPlusValue + $totalMinusValue;

    $netQtyClass = $netQty < 0 ? 'diff-danger' : ($netQty > 0 ? 'diff-warning' : 'diff-success');
    $netValueClass = $netValue < 0 ? 'diff-danger' : ($netValue > 0 ? 'diff-warning' : 'diff-success');

    $statusClass = match ($opname->status) {
        StockOpname::STATUS_DRAFT => 'badge-status badge-status--draft',
        StockOpname::STATUS_COUNTING => 'badge-status badge-status--counting',
        StockOpname::STATUS_REVIEWED => 'badge-status badge-status--reviewed',
        StockOpname::STATUS_FINALIZED => 'badge-status badge-status--finalized',
        StockOpname::STATUS_CANCELLED => 'badge-status badge-status--cancelled',
        default => 'badge-status badge-status--draft',
    };

    $typeLabel = $isOpening ? 'Opening' : 'Periodic';
    $typeClass = $isOpening ? 'badge-type badge-type--opening' : 'badge-type badge-type--periodic';

    $isCancelled = defined(StockOpname::class.'::STATUS_CANCELLED')
        ? ($opname->status === StockOpname::STATUS_CANCELLED)
        : false;

    $canEdit = !$adjustment && !$isCancelled && in_array($opname->status, [StockOpname::STATUS_DRAFT, StockOpname::STATUS_COUNTING], true);
    $canFinalize = !$isCancelled
        && $isOwner
        && (method_exists($opname, 'canFinalize')
            ? $opname->canFinalize()
            : $opname->status === StockOpname::STATUS_REVIEWED);

    $canMarkReviewed =
        !$isCancelled &&
        !$adjustment &&
        in_array($opname->status, [StockOpname::STATUS_DRAFT, StockOpname::STATUS_COUNTING], true) &&
        in_array($userRole, ['operating', 'admin'], true);

    // ✅ NEW: can cancel (owner/admin), not finalized, not adjusted, not cancelled
    $canCancel =
        !$isCancelled
        && !$adjustment
        && in_array($opname->status, [StockOpname::STATUS_DRAFT, StockOpname::STATUS_COUNTING, StockOpname::STATUS_REVIEWED], true)
        && in_array($userRole, ['owner','admin'], true);

    $canSetUnitCost =
        !$isCancelled
        && !$adjustment
        && in_array($opname->status, [StockOpname::STATUS_DRAFT, StockOpname::STATUS_COUNTING, StockOpname::STATUS_REVIEWED], true)
        && in_array($userRole, ['owner', 'admin', 'operating'], true);

    $canSeeAdjustmentLink = !$isOpOrAdmin;

    $hasReviewedInfo = $opname->status === StockOpname::STATUS_REVIEWED && $opname->reviewed_by && $opname->reviewed_at;

    // (opsional) info cancel
    $hasCancelledInfo =
        $isCancelled && (
            !empty($opname->cancelled_at ?? null)
            || !empty($opname->cancel_reason ?? null)
            || !empty($opname->cancelled_by ?? null)
        );

    $countedPercent = $totalLinesGlobal > 0
        ? min(100, max(0, round(($countedLinesGlobal / $totalLinesGlobal) * 100)))
        : 0;
@endphp

@push('head')
<style>
    .page-wrap{ max-width:1100px; margin-inline:auto; padding:.6rem .6rem 2.5rem; }
    body[data-theme=light] .page-wrap{ background: radial-gradient(circle at top left, rgba(129,140,248,.10) 0, rgba(45,212,191,.08) 26%, #f9fafb 60%); }
    body[data-theme=dark] .page-wrap{ background: radial-gradient(circle at top left, rgba(15,23,42,.92) 0, #020617 65%); }

    .card-main{ background:var(--card); border-radius:12px; border:1px solid rgba(148,163,184,.26); box-shadow:0 8px 22px rgba(15,23,42,.06),0 0 0 1px rgba(15,23,42,.02); }
    .card-main .card-body{ padding:.75rem .85rem; }

    .page-head{ display:flex; align-items:flex-start; justify-content:space-between; gap:.55rem; margin-bottom:.55rem; }
    .page-title{ margin:0; font-size:1rem; font-weight:850; letter-spacing:-.01em; }
    .subtle{ color:rgba(100,116,139,1); font-size:.78rem; margin:.1rem 0 0; }

    .chip{ display:inline-flex; align-items:center; gap:.35rem; padding:.18rem .45rem; border-radius:999px; font-size:.68rem; font-weight:800; border:1px solid rgba(148,163,184,.22); background:rgba(15,23,42,.02); color:rgba(71,85,105,1); }
    body[data-theme=dark] .chip{ background:rgba(148,163,184,.08); border-color:rgba(148,163,184,.18); color:rgba(226,232,240,.86); }

    .badge-status{ font-size:.66rem; padding:.14rem .45rem; border-radius:999px; font-weight:900; display:inline-flex; align-items:center; gap:.35rem; }
    .badge-status--draft{ background:rgba(148,163,184,.2); color:#475569; }
    .badge-status--counting{ background:rgba(59,130,246,.16); color:#1d4ed8; }
    .badge-status--reviewed{ background:rgba(234,179,8,.18); color:#854d0e; }
    .badge-status--finalized{ background:rgba(22,163,74,.18); color:#15803d; }
    .badge-status--cancelled{ background:rgba(239,68,68,.16); color:#b91c1c; }

    .badge-type{ font-size:.62rem; padding:.10rem .40rem; border-radius:999px; font-weight:900; }
    .badge-type--periodic{ background:rgba(59,130,246,.15); color:#1d4ed8; }
    .badge-type--opening{ background:rgba(249,115,22,.16); color:#c2410c; }

    .badge-review{ font-size:.64rem; padding:.12rem .45rem; border-radius:999px; font-weight:700; display:inline-flex; align-items:center; gap:.35rem; margin-top:.25rem; border:1px solid rgba(234,179,8,.40); background:rgba(251,191,36,.10); color:#854d0e; }
    .badge-review-dot{ width:7px; height:7px; border-radius:999px; background:currentColor; }

    .badge-cancel{ font-size:.64rem; padding:.12rem .45rem; border-radius:999px; font-weight:800; display:inline-flex; align-items:center; gap:.35rem; margin-top:.25rem; border:1px solid rgba(239,68,68,.35); background:rgba(239,68,68,.10); color:#b91c1c; }

    .meta{ font-size:.76rem; color:rgba(100,116,139,1); }
    .text-mono{ font-variant-numeric:tabular-nums; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace; }

    .kv{ display:grid; grid-template-columns:140px 1fr; gap:.25rem .6rem; font-size:.86rem; }
    .kv .k{ color:rgba(100,116,139,1); font-weight:800; }
    .kv .v{ font-weight:700; }

    .summary-grid{ display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.5rem; margin-top:.55rem; }
    .sum-card{ border-radius:10px; border:1px solid rgba(148,163,184,.20); background:rgba(15,23,42,.01); padding:.6rem .7rem; }
    body[data-theme=dark] .sum-card{ background:rgba(148,163,184,.06); }
    .sum-label{ font-size:.66rem; text-transform:uppercase; letter-spacing:.06em; color:rgba(100,116,139,1); font-weight:900; margin-bottom:.2rem; }
    .so-info-grid{ display:grid; grid-template-columns:1.15fr .85fr; gap:.55rem; }
    .so-info-card{ border:1px solid rgba(148,163,184,.18); border-radius:12px; padding:.65rem .7rem; background:rgba(148,163,184,.05); }
    body[data-theme=dark] .so-info-card{ background:rgba(15,23,42,.28); }
    .so-info-line{ display:grid; grid-template-columns:78px 1fr; gap:.45rem; align-items:start; padding:.16rem 0; font-size:.82rem; }
    .so-info-line .k{ color:rgba(100,116,139,1); font-weight:900; font-size:.68rem; }
    .so-info-line .v{ font-weight:900; min-width:0; }
    .so-progress-main{ display:flex; align-items:baseline; justify-content:space-between; gap:.65rem; }
    .so-progress-main .val{ font-size:1.45rem; line-height:1; font-weight:950; color:#2563eb; }
    .so-progress-main .lbl{ color:rgba(100,116,139,1); font-size:.68rem; font-weight:900; }
    .so-progress-bar{ height:7px; border-radius:999px; background:rgba(148,163,184,.18); overflow:hidden; margin-top:.55rem; }
    .so-progress-bar span{ display:block; height:100%; border-radius:999px; background:#2563eb; }
    .so-summary-kpis{ display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.45rem; margin-top:.55rem; }
    .so-summary-kpi{ border:1px solid rgba(148,163,184,.18); border-radius:12px; padding:.5rem .58rem; background:rgba(255,255,255,.45); min-width:0; }
    body[data-theme=dark] .so-summary-kpi{ background:rgba(15,23,42,.20); }
    .so-summary-kpi .lbl{ display:block; color:rgba(100,116,139,1); font-size:.6rem; font-weight:900; line-height:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .so-summary-kpi .val{ display:block; margin-top:.2rem; font-size:.95rem; line-height:1; font-weight:950; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .so-summary-kpi.is-main{ border-color:rgba(37,99,235,.20); background:rgba(37,99,235,.06); }

    .diff-plus{ color:#16a34a; font-weight:900; }
    .diff-minus{ color:#dc2626; font-weight:900; }
    .diff-danger{ color:#b91c1c; font-weight:900; }
    .diff-success{ color:#15803d; font-weight:900; }
    .diff-warning{ color:#854d0e; font-weight:900; }

    .btn.btn-sm{ padding:.28rem .55rem; font-size:.78rem; }
    .form-control-sm,.form-select-sm{ padding:.28rem .45rem; font-size:.78rem; }

    .filter-bar{ margin-top:.35rem; }
    .so-filter-box{ border:1px solid rgba(148,163,184,.18); border-radius:12px; padding:.55rem; background:rgba(148,163,184,.05); }
    body[data-theme=dark] .so-filter-box{ background:rgba(15,23,42,.35); }
    .so-filter-row{ --bs-gutter-x:.45rem; --bs-gutter-y:.42rem; }
    .pill-label{ display:block; margin-bottom:.18rem; font-size:.62rem; color:rgba(100,116,139,1); font-weight:900; line-height:1.05; white-space:nowrap; }
    .so-filter-row .form-control-sm,
    .so-filter-row .form-select-sm{ min-height:36px; border-radius:10px; padding:.34rem .45rem; font-size:.8rem; }
    .so-filter-reset{ min-height:36px; border-radius:10px; font-weight:900; width:100%; display:inline-flex; align-items:center; justify-content:center; }
    .so-mini-kpis{ display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.4rem; margin-top:.5rem; }
    .so-mini-kpi{ border:1px solid rgba(148,163,184,.18); border-radius:10px; padding:.4rem .45rem; background:rgba(255,255,255,.45); min-width:0; }
    body[data-theme=dark] .so-mini-kpi{ background:rgba(15,23,42,.20); }
    .so-mini-kpi .lbl{ display:block; color:rgba(100,116,139,1); font-size:.58rem; font-weight:900; line-height:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .so-mini-kpi .val{ display:block; margin-top:.16rem; font-size:.84rem; line-height:1; font-weight:950; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .so-mini-kpi.is-main{ border-color:rgba(37,99,235,.20); background:rgba(37,99,235,.06); }
    .so-mini-kpi.is-main .val{ color:#2563eb; }
    .so-sort-btn{
        border:0;
        background:transparent;
        color:inherit;
        padding:0;
        margin:0;
        font:inherit;
        font-weight:900;
        text-transform:inherit;
        letter-spacing:inherit;
        display:inline-flex;
        align-items:center;
        gap:.22rem;
        cursor:pointer;
    }
    .so-sort-btn .arr{ opacity:.35; font-size:.72em; transform:translateY(-1px); }
    .so-sort-btn.is-active{ color:#2563eb; }
    .so-sort-btn.is-active .arr{ opacity:.9; }

    .table-wrap{ margin-top:.5rem; border-radius:10px; border:1px solid rgba(148,163,184,.22); overflow-x:auto; overflow-y:auto; max-height:460px; background:rgba(248,250,252,.9); }
    body[data-theme=dark] .table-wrap{ background:rgba(15,23,42,.92); border-color:rgba(51,65,85,.9); }

    .table thead th{ position:sticky; top:0; z-index:3; font-size:.66rem; text-transform:uppercase; letter-spacing:.06em; white-space:nowrap; padding:.45rem .5rem; background:#fff !important; color:rgba(100,116,139,1); }
    body[data-theme=dark] .table thead th{ background:#0f172a !important; color:#e5e7eb; }

    .table tbody td{ padding:.42rem .5rem; font-size:.78rem; white-space:nowrap; vertical-align:middle; }
    .col-notes-compact{ max-width:240px; }
    .col-notes-compact .note-text{ display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; white-space:normal; line-height:1.2; }
    .hpp-inline-form{ display:flex; align-items:center; justify-content:flex-end; gap:.25rem; min-width:155px; }
    .hpp-inline-form .form-control{ max-width:105px; text-align:right; font-variant-numeric:tabular-nums; }
    .hpp-save-btn{ padding:.24rem .42rem !important; line-height:1; font-weight:900; }
    .hpp-missing{ color:#b91c1c; font-size:.68rem; font-weight:900; display:inline-flex; align-items:center; gap:.24rem; }
    .hpp-muted{ color:rgba(100,116,139,1); font-size:.68rem; font-weight:800; }

    .sum-row{ display:none; margin-top:.55rem; border-radius:10px; border:1px solid rgba(148,163,184,.22); background:rgba(15,23,42,.01); padding:.55rem .65rem; }
    .sum-row .cell{ display:flex; align-items:baseline; justify-content:space-between; gap:.65rem; padding:.12rem 0; font-size:.86rem; }
    .sum-row .label{ color:rgba(100,116,139,1); font-weight:900; font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; }
    .sum-row .value{ font-weight:900; font-variant-numeric:tabular-nums; text-align:right; }

    .mobile-compact-table{ display:none; margin-top:.5rem; border-radius:10px; border:1px solid rgba(148,163,184,.22); overflow-x:auto; overflow-y:auto; max-height:460px; background:rgba(248,250,252,.9); }
    .mobile-compact-table .table{ margin-bottom:0; table-layout:fixed; width:100%; }
    .mobile-compact-table thead th{ position:sticky; top:0; z-index:3; background:#fff !important; color:rgba(100,116,139,1); text-transform:uppercase; letter-spacing:.06em; white-space:nowrap; font-size:.66rem; padding:.42rem .5rem; }

    .mobile-compact-table th,.mobile-compact-table td{ padding:.40rem .5rem; vertical-align:top; }
    .m-col-no{ width:42px; }
    .m-col-item{ width:auto; }
    .m-col-diff{ width:112px; }

    .m-item-title{ font-weight:900; line-height:1.12; word-break:break-word; }
    .m-item-sub{ font-size:.76rem; color:rgba(100,116,139,1); margin-top:.08rem; word-break:break-word; }

    .m-badges{ margin-top:.30rem; display:flex; flex-wrap:wrap; gap:.30rem; }
    .mini-badge{ display:inline-flex; align-items:center; gap:.28rem; padding:.10rem .42rem; border-radius:999px; font-size:.64rem; font-weight:900; border:1px solid rgba(148,163,184,.22); background:rgba(15,23,42,.02); color:rgba(71,85,105,1); white-space:nowrap; }
    .tone-danger{ background:rgba(239,68,68,.12); border-color:rgba(239,68,68,.20); color:#b91c1c; }
    .tone-success{ background:rgba(22,163,74,.14); border-color:rgba(22,163,74,.22); color:#15803d; }
    .tone-warning{ background:rgba(234,179,8,.16); border-color:rgba(234,179,8,.26); color:#854d0e; }

    @media (max-width: 767.98px){
        .subtle{ display:none; }
        .summary-grid{ display:none; }
        .sum-row{ display:block; }
        .table-wrap{ display:none; }
        .mobile-compact-table{ display:block; }
        .kv{ grid-template-columns:110px 1fr; }
        .so-info-grid{ grid-template-columns:1fr; gap:.45rem; }
        .so-info-card{ padding:.55rem .6rem; border-radius:11px; }
        .so-info-line{ grid-template-columns:70px 1fr; font-size:.8rem; }
        .so-progress-main .val{ font-size:1.25rem; }
        .so-summary-kpis{ grid-template-columns:repeat(3,minmax(0,1fr)); gap:.32rem; margin-top:.45rem; }
        .so-summary-kpi{ padding:.42rem .4rem; border-radius:10px; }
        .so-summary-kpi .val{ font-size:.78rem; }
        .card-main .card-body{ padding:.65rem; }
        .so-filter-box{ padding:.5rem; border-radius:12px; }
        .so-filter-row{ --bs-gutter-x:.35rem; --bs-gutter-y:.35rem; }
        .so-mini-kpis{ gap:.32rem; }
        .so-mini-kpi{ padding:.36rem .38rem; }
        .so-mini-kpi .val{ font-size:.78rem; }
        .so-sort-btn .arr{ font-size:.8em; }
    }
</style>
@endpush

@section('content')
<div class="page-wrap">

    {{-- HEADER --}}
    <div class="page-head">
        <div>
            <a href="{{ route('inventory.stock_opnames.index') }}" class="btn btn-sm btn-link px-0 mb-1">← Kembali</a>

            <h1 class="page-title">
                {{ $opname->code }}
                <span class="{{ $typeClass }} ms-1">{{ $typeLabel }}</span>
                <span class="{{ $statusClass }} ms-1">{{ ucfirst($opname->status) }}</span>

                @if($adjustment && $canSeeAdjustmentLink)
                    <span class="chip ms-1">Adj: {{ $adjustment->code }}</span>
                @endif
            </h1>

            <div class="subtle">{{ $opname->warehouse?->code ?? '-' }} — {{ $opname->warehouse?->name ?? '-' }}</div>

            @if ($hasReviewedInfo)
                <div class="badge-review">
                    <span class="badge-review-dot"></span>
                    Selesai oleh <span class="fw-semibold">{{ $opname->reviewer?->name ?? '-' }}</span>
                    <span class="text-mono" style="font-size:.68rem;">{{ $opname->reviewed_at?->format('d M H:i') ?? '-' }}</span>
                </div>
            @endif

            @if ($hasCancelledInfo)
                <div class="badge-cancel">
                    <span class="badge-review-dot"></span>
                    Dibatalkan
                    @if($opname->cancelled_by)
                        oleh <span class="fw-semibold">{{ $opname->cancelledBy?->name ?? '-' }}</span>
                    @endif
                    @if($opname->cancelled_at)
                        <span class="text-mono" style="font-size:.68rem;">{{ $opname->cancelled_at?->format('d M H:i') }}</span>
                    @endif
                    @if($opname->cancel_reason)
                        — <span class="fw-semibold">{{ $opname->cancel_reason }}</span>
                    @endif
                </div>
            @endif
        </div>

        <div class="text-end">
            {{-- actions --}}
            @if($canEdit)
                <div class="d-flex flex-wrap justify-content-end gap-2 mb-1">
                    <a href="{{ route('inventory.stock_opnames.edit', $opname) }}" class="btn btn-sm btn-outline-primary">Lanjut</a>

                    @if($canMarkReviewed)
                        <form action="{{ route('inventory.stock_opnames.update', $opname) }}" method="POST"
                              onsubmit="return confirm('Tandai selesai hitung? Pastikan semua Qty Fisik sudah terisi.');"
                              class="d-inline">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="mark_reviewed" value="1">
                            <button type="submit" class="btn btn-sm btn-primary">Selesai</button>
                        </form>
                    @endif
                </div>
            @endif

            @if($canFinalize)
                <form action="{{ route('inventory.stock_opnames.finalize', $opname) }}" method="POST" class="mt-1"
                      onsubmit="return confirm('Finalize? Stok gudang akan dikoreksi sesuai hasil fisik.');">
                    @csrf
                    <input type="hidden" name="reason" value="Stock Opname {{ $opname->code }}">
                    <button type="submit" class="btn btn-sm btn-success">{{ $isOpening ? 'Finalize Opening' : 'Finalize' }}</button>
                </form>
            @elseif($opname->status === StockOpname::STATUS_FINALIZED)
                @if($adjustment && $canSeeAdjustmentLink)
                    <a href="{{ route('inventory.adjustments.show', $adjustment) }}" class="btn btn-sm btn-outline-secondary mt-1">Adjustment →</a>
                @endif
            @endif

            {{-- ✅ NEW: Cancel button --}}
            @if($canCancel)
                <button type="button" class="btn btn-sm btn-outline-danger mt-1"
                        data-bs-toggle="modal" data-bs-target="#cancelSoModal">
                    Batal
                </button>
            @endif
        </div>
    </div>

    {{-- FLASH --}}
    @if (session('message'))
        <div class="alert alert-{{ session('status') === 'success' ? 'success' : (session('status') === 'error' ? 'danger' : 'info') }} py-2">
            {{ session('message') }}
        </div>
    @endif

    {{-- INFO --}}
    <div class="card card-main mb-2">
        <div class="card-body">
            <div class="so-info-grid">
                <div class="so-info-card">
                    <div class="so-info-line">
                        <div class="k">Tanggal</div>
                        <div class="v">{{ $opname->date?->format('d M Y') ?? '-' }}</div>
                    </div>

                    <div class="so-info-line">
                        <div class="k">Gudang</div>
                        <div class="v">
                            {{ $opname->warehouse?->code ?? '-' }}
                            <div class="meta">{{ $opname->warehouse?->name }}</div>
                        </div>
                    </div>

                    <div class="so-info-line">
                        <div class="k">Dibuat</div>
                        <div class="v">
                            {{ $opname->creator?->name ?? '-' }}
                            <div class="meta">{{ $opname->created_at?->format('d M H:i') }}</div>
                        </div>
                    </div>

                    @if ($opname->finalized_at)
                        <div class="so-info-line">
                            <div class="k">Final</div>
                            <div class="v">
                                {{ $opname->finalizer?->name ?? '-' }}
                                <div class="meta">{{ $opname->finalized_at?->format('d M H:i') }}</div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="so-info-card">
                    <div class="so-progress-main">
                        <div>
                            <div class="lbl">Progress</div>
                            <div class="val text-mono">{{ $countedLinesGlobal }}/{{ $totalLinesGlobal }}</div>
                        </div>
                        <div class="text-end">
                            <div class="lbl">Selesai</div>
                            <div class="fw-black text-mono">{{ $countedPercent }}%</div>
                        </div>
                    </div>
                    <div class="so-progress-bar"><span style="width:{{ $countedPercent }}%;"></span></div>
                    @if ($notCountedGlobal > 0)
                        <div class="meta mt-2">{{ $notCountedGlobal }} item belum dihitung</div>
                    @endif
                    @if ($opname->notes)
                        <div class="meta mt-2">{!! nl2br(e($opname->notes)) !!}</div>
                    @endif
                </div>
            </div>

            {{-- SUMMARY (FILTERED) --}}
            <div class="so-summary-kpis">
                <div class="so-summary-kpi">
                    <span class="lbl">Lebih Stok</span>
                    <span class="val text-mono diff-plus" id="sumPlusQty">+{{ number_format($totalPlusQty, 2) }}</span>
                </div>

                <div class="so-summary-kpi">
                    <span class="lbl">Kurang Stok</span>
                    <span class="val text-mono diff-minus" id="sumMinusQty">{{ number_format($totalMinusQty, 2) }}</span>
                </div>

                <div class="so-summary-kpi is-main">
                    <span class="lbl">Selisih Bersih</span>
                    <span class="val text-mono {{ $netQtyClass }}" id="sumNetQty">
                        @if (abs($netQty) < 0.0000001)
                            0.00
                        @else
                            {{ $netQty > 0 ? '+' : '' }}{{ number_format($netQty, 2) }}
                        @endif
                    </span>
                </div>

                <div class="so-summary-kpi">
                    <span class="lbl">Nilai Sistem</span>
                    <span class="val text-mono" id="sumSystemValue">Rp {{ number_format($totalSystemValue, 0, ',', '.') }}</span>
                </div>

                <div class="so-summary-kpi">
                    <span class="lbl">Nilai Fisik</span>
                    <span class="val text-mono" id="sumPhysicalValue">Rp {{ number_format($totalPhysicalValue, 0, ',', '.') }}</span>
                </div>

                <div class="so-summary-kpi {{ $missingCostCount > 0 ? '' : 'is-main' }}">
                    <span class="lbl">HPP Kosong</span>
                    <span class="val text-mono {{ $missingCostCount > 0 ? 'diff-danger' : 'diff-success' }}" id="sumMissingCost">{{ number_format($missingCostCount, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- SUMMARY MOBILE --}}
            <div class="sum-row">
                <div class="cell">
                    <div class="label">Qty</div>
                    <div class="value">
                        <span class="diff-plus" id="mSumPlusQty">+{{ number_format($totalPlusQty, 2) }}</span>
                        <span style="opacity:.55; padding:0 .35rem;">|</span>
                        <span class="diff-minus" id="mSumMinusQty">{{ number_format($totalMinusQty, 2) }}</span>
                    </div>
                </div>
                <div class="cell">
                    <div class="label">Rp</div>
                    <div class="value">
                        <span class="diff-plus" id="mSumPlusValue">+{{ number_format($totalPlusValue, 0, ',', '.') }}</span>
                        <span style="opacity:.55; padding:0 .35rem;">|</span>
                        <span class="diff-minus" id="mSumMinusValue">{{ $totalMinusValue < 0 ? '-' : '' }}{{ number_format(abs($totalMinusValue), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- DETAIL --}}
    <div class="card card-main">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                <div class="fw-semibold" style="font-size:.9rem;">Detail Item</div>
                <span class="chip" id="soShownCount" data-base="{{ $lines->count() }} item">{{ $lines->count() }} item</span>
            </div>

            {{-- FILTER (AJAX realtime) --}}
            <form method="GET" class="filter-bar" id="soFilterForm" autocomplete="off">
                @php
                    $diffMode = ($filters['diff_sign'] ?? 'all') === 'plus'
                        ? 'plus'
                        : ((($filters['diff_sign'] ?? 'all') === 'minus')
                            ? 'minus'
                            : (($filters['diff_only'] ?? false) ? 'different' : 'all'));
                @endphp
                <div class="so-filter-box">
                    <div class="row align-items-end so-filter-row">
                    <div class="col-7 col-lg-3">
                        <label class="pill-label">Cari</label>
                        <input type="text" name="q" id="soFilterQ" value="{{ $filters['q'] ?? '' }}"
                               class="form-control form-control-sm" placeholder="Kode / nama"
                               @disabled($isCancelled)>
                    </div>

                    <div class="col-5 col-lg-2">
                        <label class="pill-label">Status</label>
                        <select name="counted" class="form-select form-select-sm" @disabled($isCancelled)>
                            <option value="all" @selected(($filters['counted'] ?? 'all') === 'all')>Semua</option>
                            <option value="yes" @selected(($filters['counted'] ?? '') === 'yes')>Sudah</option>
                            <option value="no" @selected(($filters['counted'] ?? '') === 'no')>Belum</option>
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <label class="pill-label">Selisih</label>
                        <select id="soDiffMode" class="form-select form-select-sm" @disabled($isCancelled)>
                            <option value="all" @selected($diffMode === 'all')>Semua</option>
                            <option value="different" @selected($diffMode === 'different')>Ada selisih</option>
                            <option value="plus" @selected($diffMode === 'plus')>Lebih</option>
                            <option value="minus" @selected($diffMode === 'minus')>Kurang</option>
                        </select>
                        <input type="hidden" name="diff_sign" id="soDiffSign" value="{{ $filters['diff_sign'] ?? 'all' }}">
                        <input type="hidden" name="diff_only" id="soDiffOnly" value="{{ ($filters['diff_only'] ?? false) ? 1 : 0 }}">
                    </div>

                    <input type="hidden" name="sort" id="soSortField" value="{{ $filters['sort'] ?? 'item' }}">
                    <input type="hidden" name="dir" id="soSortDir" value="{{ $filters['dir'] ?? 'asc' }}">

                    <div class="col-6 col-lg-3">
                        <label class="pill-label">&nbsp;</label>
                        <a class="btn btn-sm btn-light so-filter-reset" href="{{ route('inventory.stock_opnames.show', $opname) }}">Reset</a>
                    </div>
                    </div>

                    <div class="so-mini-kpis">
                        <div class="so-mini-kpi">
                            <span class="lbl">Tampil</span>
                            <span class="val text-mono" id="soKpiShown">{{ $lines->count() }}</span>
                        </div>
                        <div class="so-mini-kpi">
                            <span class="lbl">Sudah</span>
                            <span class="val text-mono">{{ $countedLinesGlobal }}/{{ $totalLinesGlobal }}</span>
                        </div>
                        <div class="so-mini-kpi is-main">
                            <span class="lbl">Selisih</span>
                            <span class="val text-mono" id="soKpiNetQty">
                                @if (abs($netQty) < 0.0000001)
                                    0
                                @else
                                    {{ $netQty > 0 ? '+' : '' }}{{ number_format($netQty, 0, ',', '.') }}
                                @endif
                            </span>
                        </div>
                        <div class="so-mini-kpi">
                            <span class="lbl">Nilai</span>
                            <span class="val text-mono" id="soKpiNetValue">
                                @if (abs($netValue) < 0.0000001)
                                    Rp 0
                                @else
                                    {{ $netValue > 0 ? '+Rp' : '-Rp' }} {{ number_format(abs($netValue), 0, ',', '.') }}
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </form>

            {{-- DESKTOP TABLE --}}
            <div class="table-wrap" id="so-show-table-wrap">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>
                            <button type="button" class="so-sort-btn js-so-sort @if(($filters['sort'] ?? 'item') === 'item') is-active @endif" data-sort="item">
                                Item <span class="arr">{{ ($filters['sort'] ?? 'item') === 'item' && ($filters['dir'] ?? 'asc') === 'desc' ? '↓' : '↑' }}</span>
                            </button>
                        </th>
                        <th class="text-end">
                            <button type="button" class="so-sort-btn js-so-sort @if(($filters['sort'] ?? '') === 'system') is-active @endif" data-sort="system">
                                Stok <span class="arr">{{ ($filters['sort'] ?? '') === 'system' && ($filters['dir'] ?? 'asc') === 'desc' ? '↓' : '↑' }}</span>
                            </button>
                        </th>
                        <th class="text-end">
                            <button type="button" class="so-sort-btn js-so-sort @if(($filters['sort'] ?? '') === 'physical') is-active @endif" data-sort="physical">
                                Fisik <span class="arr">{{ ($filters['sort'] ?? '') === 'physical' && ($filters['dir'] ?? 'asc') === 'desc' ? '↓' : '↑' }}</span>
                            </button>
                        </th>
                        <th class="text-end">
                            <button type="button" class="so-sort-btn js-so-sort @if(($filters['sort'] ?? '') === 'diff') is-active @endif" data-sort="diff">
                                Selisih <span class="arr">{{ ($filters['sort'] ?? '') === 'diff' && ($filters['dir'] ?? 'asc') === 'desc' ? '↓' : '↑' }}</span>
                            </button>
                        </th>
                        <th class="text-end">HPP</th>
                        <th class="text-end">Nilai Fisik</th>
                        <th class="text-end">
                            <button type="button" class="so-sort-btn js-so-sort @if(($filters['sort'] ?? '') === 'value') is-active @endif" data-sort="value">
                                Nilai Selisih <span class="arr">{{ ($filters['sort'] ?? '') === 'value' && ($filters['dir'] ?? 'asc') === 'desc' ? '↓' : '↑' }}</span>
                            </button>
                        </th>
                        <th class="col-notes-compact">Catatan</th>
                    </tr>
                    </thead>
                    <tbody id="soDesktopTbody">
                    @forelse ($lines as $i => $line)
                        @php
                            $system = (float) ($line->system_qty ?? 0);
                            $physical = $line->physical_qty !== null ? (float) $line->physical_qty : null;
                            $counted = (bool) ($line->is_counted ?? false);

                            $diff = null;
                            if ($counted && $physical !== null) {
                                $diff = (float) ($line->difference_qty ?? ($physical - $system));
                            }

                            $tone = '';
                            if ($diff !== null) {
                                $tone = $diff < 0 ? 'diff-danger' : ($diff > 0 ? 'diff-warning' : 'diff-success');
                            }

                            $unitCost = (float) ($line->unit_cost ?? 0);
                            if ($unitCost <= 0) $unitCost = (float) ($line->item->hpp ?? 0);
                            if ($unitCost <= 0) $unitCost = (float) ($line->effective_unit_cost ?? 0);
                            $costMissing = $unitCost <= 0;
                            $physicalValue = (!$costMissing && $physical !== null) ? $physical * $unitCost : null;
                            $value = null;
                            if ($diff !== null && abs($diff) >= 0.0000001 && $unitCost > 0) {
                                $value = $diff * $unitCost;
                            }
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <div class="fw-semibold">{{ $line->item?->code ?? '-' }}</div>
                                <div class="meta">{{ $line->item?->name ?? '' }}</div>
                            </td>
                            <td class="text-end text-mono">{{ number_format($system, 2) }}</td>
                            <td class="text-end text-mono">
                                @if ($physical !== null && $counted)
                                    {{ number_format($physical, 2) }}
                                @else
                                    <span class="meta">-</span>
                                @endif
                            </td>
                            <td class="text-end text-mono {{ $diff !== null ? $tone : '' }}">
                                @if ($diff !== null)
                                    {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 2) }}
                                @else
                                    <span class="meta">-</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if (!$costMissing)
                                    <div class="text-mono fw-semibold">Rp {{ number_format($unitCost, 0, ',', '.') }}</div>
                                @elseif ($canSetUnitCost)
                                    <form method="POST" action="{{ route('inventory.stock_opnames.lines.unit_cost', ['stockOpname' => $opname, 'line' => $line]) }}" class="hpp-inline-form">
                                        @csrf
                                        <input type="number" name="unit_cost" class="form-control form-control-sm" min="1" step="0.01" placeholder="HPP" required>
                                        <button type="submit" class="btn btn-sm btn-dark hpp-save-btn">OK</button>
                                    </form>
                                @else
                                    <span class="hpp-missing">Belum ada</span>
                                @endif
                            </td>
                            <td class="text-end text-mono">
                                @if ($physicalValue !== null)
                                    Rp {{ number_format($physicalValue, 0, ',', '.') }}
                                @else
                                    <span class="meta">-</span>
                                @endif
                            </td>
                            <td class="text-end text-mono {{ $value !== null ? $tone : '' }}">
                                @if ($value !== null)
                                    {{ $value > 0 ? '+Rp' : '-Rp' }} {{ number_format(abs($value), 0, ',', '.') }}
                                @else
                                    <span class="meta">-</span>
                                @endif
                            </td>
                            <td class="col-notes-compact"><div class="note-text">{{ $line->notes }}</div></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <div class="fw-semibold">Tidak ada item.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- MOBILE TABLE --}}
            <div class="mobile-compact-table" id="so-show-mobile-table-wrap">
                <table class="table table-sm align-middle">
                    <thead>
                    <tr>
                        <th class="m-col-no">#</th>
                        <th class="m-col-item">
                            <button type="button" class="so-sort-btn js-so-sort @if(($filters['sort'] ?? 'item') === 'item') is-active @endif" data-sort="item">
                                Item <span class="arr">{{ ($filters['sort'] ?? 'item') === 'item' && ($filters['dir'] ?? 'asc') === 'desc' ? '↓' : '↑' }}</span>
                            </button>
                        </th>
                        <th class="m-col-diff text-end">
                            <button type="button" class="so-sort-btn js-so-sort @if(($filters['sort'] ?? '') === 'diff') is-active @endif" data-sort="diff">
                                Selisih <span class="arr">{{ ($filters['sort'] ?? '') === 'diff' && ($filters['dir'] ?? 'asc') === 'desc' ? '↓' : '↑' }}</span>
                            </button>
                        </th>
                    </tr>
                    </thead>
                    <tbody id="soMobileTbody">
                    @forelse ($lines as $i => $line)
                        @php
                            $system = (float) ($line->system_qty ?? 0);
                            $physical = $line->physical_qty !== null ? (float) $line->physical_qty : null;
                            $counted = (bool) ($line->is_counted ?? false);

                            $diff = null;
                            if ($counted && $physical !== null) {
                                $diff = (float) ($line->difference_qty ?? ($physical - $system));
                            }

                            $tone = 'tone-success';
                            if ($diff !== null) {
                                $tone = $diff < 0 ? 'tone-danger' : ($diff > 0 ? 'tone-warning' : 'tone-success');
                            }

                            $unitCost = (float) ($line->unit_cost ?? 0);
                            if ($unitCost <= 0) $unitCost = (float) ($line->item->hpp ?? 0);
                            if ($unitCost <= 0) $unitCost = (float) ($line->effective_unit_cost ?? 0);
                            $costMissing = $unitCost <= 0;
                            $physicalValue = (!$costMissing && $physical !== null) ? $physical * $unitCost : null;
                            $value = null;
                            if ($diff !== null && abs($diff) >= 0.0000001 && $unitCost > 0) {
                                $value = $diff * $unitCost;
                            }
                        @endphp
                        <tr>
                            <td class="text-mono">{{ $i + 1 }}</td>
                            <td>
                                <div class="m-item-title">{{ $line->item?->code ?? '-' }}</div>
                                @if ($line->item?->name)
                                    <div class="m-item-sub">{{ $line->item->name }}</div>
                                @endif

                                <div class="m-badges">
                                    <span class="mini-badge"><span class="k">Sys</span><span class="v">{{ number_format($system, 2) }}</span></span>
                                    <span class="mini-badge"><span class="k">F</span><span class="v">{{ $physical !== null && $counted ? number_format($physical, 2) : '-' }}</span></span>
                                    <span class="mini-badge {{ $costMissing ? 'tone-danger' : '' }}"><span class="k">HPP</span><span class="v">{{ $costMissing ? '-' : number_format($unitCost, 0, ',', '.') }}</span></span>
                                    <span class="mini-badge"><span class="k">Nilai</span><span class="v">{{ $physicalValue !== null ? number_format($physicalValue, 0, ',', '.') : '-' }}</span></span>

                                    <span class="mini-badge {{ $tone }}">
                                        <span class="k">Rp</span>
                                        <span class="v">
                                            @if ($value !== null)
                                                {{ $value > 0 ? '+' : '-' }}{{ number_format(abs($value), 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </span>
                                </div>

                                @if ($costMissing && $canSetUnitCost)
                                    <form method="POST" action="{{ route('inventory.stock_opnames.lines.unit_cost', ['stockOpname' => $opname, 'line' => $line]) }}" class="hpp-inline-form mt-2" style="justify-content:flex-start;">
                                        @csrf
                                        <input type="number" name="unit_cost" class="form-control form-control-sm" min="1" step="0.01" placeholder="Isi HPP" required>
                                        <button type="submit" class="btn btn-sm btn-dark hpp-save-btn">Simpan</button>
                                    </form>
                                @endif
                            </td>

                            <td class="text-end text-mono">
                                <span class="mini-badge {{ $tone }}" style="justify-content:flex-end;">
                                    <span class="v">
                                        @if ($diff !== null)
                                            {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 2) }}
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
                                <div class="fw-semibold">Tidak ada item.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- ✅ NEW: Cancel modal --}}
    @if($canCancel)
        <div class="modal fade" id="cancelSoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('inventory.stock_opnames.cancel', $opname) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Batalkan Stock Opname</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-2" style="font-size:.86rem;">
                                Kamu akan membatalkan <span class="fw-semibold">{{ $opname->code }}</span>.
                                Status akan menjadi <span class="fw-semibold">cancelled</span>.
                            </div>

                            <label class="form-label" style="font-size:.78rem; font-weight:800;">Alasan</label>
                            <input type="text" name="cancel_reason" class="form-control form-control-sm"
                                   placeholder="contoh: salah gudang / input salah / batal opname"
                                   required maxlength="190" autofocus>

                            <div class="meta mt-2">
                                Tidak bisa batal jika sudah finalized atau sudah ada Inventory Adjustment.
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Yakin batalkan Stock Opname ini?');">
                                Ya, Batalkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    // ---------- Debounce helper ----------
    function debounce(fn, wait) {
        let t = null;
        return function() {
            const ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function(){ fn.apply(ctx, args); }, wait || 150);
        };
    }
</script>

<script>
    // ---------- max-height helper ----------
    document.addEventListener('DOMContentLoaded', function () {
        setMaxHeight10RowsForShowTables();
        window.addEventListener('resize', debounce(setMaxHeight10RowsForShowTables, 150));
    });

    function setMaxHeight10RowsForShowTables(){
        applyMaxHeight10Rows('#so-show-table-wrap');
        applyMaxHeight10Rows('#so-show-mobile-table-wrap');
    }

    function applyMaxHeight10Rows(selector){
        const wrap = document.querySelector(selector);
        if(!wrap) return;

        const table = wrap.querySelector('table');
        if(!table) return;

        const thead = table.querySelector('thead');
        const firstRow = table.querySelector('tbody tr');
        if(!firstRow) return;

        const rowH = firstRow.getBoundingClientRect().height || 34;
        const headH = thead ? (thead.getBoundingClientRect().height || 30) : 30;

        wrap.style.maxHeight = Math.ceil((rowH * 10) + headH + 8) + 'px';
        wrap.style.overflowY = 'auto';
    }
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('soFilterForm');
    if(!form) return;

    const qInput = document.getElementById('soFilterQ');
    const desktopTbody = document.getElementById('soDesktopTbody');
    const mobileTbody  = document.getElementById('soMobileTbody');
    const countEl      = document.getElementById('soShownCount');
    const kpiShown     = document.getElementById('soKpiShown');
    const kpiNetQty    = document.getElementById('soKpiNetQty');
    const kpiNetValue  = document.getElementById('soKpiNetValue');
    const diffMode     = document.getElementById('soDiffMode');
    const diffSign     = document.getElementById('soDiffSign');
    const diffOnly     = document.getElementById('soDiffOnly');
    const sortSelect   = document.getElementById('soSortField');
    const sortDir      = document.getElementById('soSortDir');

    // kalau cancelled: jangan nyalain AJAX (read-only)
    const isCancelled = @json($isCancelled);
    if(isCancelled) return;

    let abortCtrl = null;
    syncDiffInputs();
    let lastQs = new URLSearchParams(new FormData(form)).toString(); // init from current

    const showUrl = @json(route('inventory.stock_opnames.show', $opname));

    function escapeHtml(str){
        return String(str ?? '')
            .replaceAll('&','&amp;')
            .replaceAll('<','&lt;')
            .replaceAll('>','&gt;')
            .replaceAll('"','&quot;')
            .replaceAll("'","&#039;");
    }

    function fmt2(n){
        return new Intl.NumberFormat('id-ID', { minimumFractionDigits:2, maximumFractionDigits:2 })
            .format(Number(n || 0));
    }
    function fmt0abs(n){
        return new Intl.NumberFormat('id-ID', { maximumFractionDigits:0 })
            .format(Math.round(Math.abs(Number(n || 0))));
    }

    function setLoading(isLoading){
        if(!countEl) return;
        if(isLoading){
            countEl.dataset.base = countEl.dataset.base || countEl.textContent.trim();
            countEl.textContent = 'Loading…';
            countEl.style.opacity = '0.7';
        }else{
            countEl.style.opacity = '1';
        }
    }

    function setText(id, text){
        const el = document.getElementById(id);
        if(el) el.textContent = text;
    }

    function syncDiffInputs(){
        if(!diffMode || !diffSign || !diffOnly) return;
        const mode = diffMode.value || 'all';
        diffSign.value = (mode === 'plus' || mode === 'minus') ? mode : 'all';
        diffOnly.value = (mode === 'different') ? '1' : '0';
    }

    function syncSortButtons(){
        const activeSort = sortSelect?.value || 'item';
        const activeDir = sortDir?.value || 'asc';

        document.querySelectorAll('.js-so-sort').forEach(btn => {
            const isActive = btn.dataset.sort === activeSort;
            btn.classList.toggle('is-active', isActive);
            const arr = btn.querySelector('.arr');
            if(arr) arr.textContent = isActive && activeDir === 'desc' ? '↓' : '↑';
        });
    }

    function applySummary(s){
        if(!s) return;

        setText('sumPlusQty', `+${fmt2(s.plus_qty)}`);
        setText('sumMinusQty', `${fmt2(s.minus_qty)}`);

        setText('sumPlusValue', `Rp ${fmt0abs(s.plus_value)}`);
        setText('sumMinusValue', `Rp ${Number(s.minus_value) < 0 ? '-' : ''}${fmt0abs(s.minus_value)}`);

        const netQtyEl = document.getElementById('sumNetQty');
        if(netQtyEl){
            const v = Number(s.net_qty || 0);
            netQtyEl.textContent = (Math.abs(v) < 1e-7) ? '0.00' : `${v > 0 ? '+' : ''}${fmt2(v)}`;
            netQtyEl.classList.remove('diff-danger','diff-warning','diff-success');
            netQtyEl.classList.add(s.net_qty_class || 'diff-success');
        }

        const netValEl = document.getElementById('sumNetValue');
        if(netValEl){
            const v = Number(s.net_value || 0);
            netValEl.classList.remove('diff-danger','diff-warning','diff-success');
            netValEl.classList.add(s.net_value_class || 'diff-success');
            if(Math.abs(v) < 1e-7) netValEl.textContent = 'Rp 0';
            else netValEl.textContent = `${v > 0 ? '+Rp' : '-Rp'} ${fmt0abs(v)}`;
        }

        // mobile summary
        setText('mSumPlusQty', `+${fmt2(s.plus_qty)}`);
        setText('mSumMinusQty', `${fmt2(s.minus_qty)}`);
        setText('mSumPlusValue', `+${fmt0abs(s.plus_value)}`);
        setText('mSumMinusValue', `${Number(s.minus_value) < 0 ? '-' : ''}${fmt0abs(s.minus_value)}`);
        setText('sumSystemValue', `Rp ${fmt0abs(s.system_value)}`);
        setText('sumPhysicalValue', `Rp ${fmt0abs(s.physical_value)}`);
        setText('sumMissingCost', new Intl.NumberFormat('id-ID').format(Number(s.missing_cost_count || 0)));

        if(kpiNetQty){
            const v = Number(s.net_qty || 0);
            kpiNetQty.textContent = (Math.abs(v) < 1e-7) ? '0' : `${v > 0 ? '+' : ''}${fmt0abs(v)}`;
        }

        if(kpiNetValue){
            const v = Number(s.net_value || 0);
            kpiNetValue.textContent = Math.abs(v) < 1e-7 ? 'Rp 0' : `${v > 0 ? '+Rp' : '-Rp'} ${fmt0abs(v)}`;
        }
    }

    function renderDesktopRows(lines){
        if(!desktopTbody) return;
        if(!lines || lines.length === 0){
            desktopTbody.innerHTML = `<tr><td colspan="9" class="text-center py-4"><div class="fw-semibold">Tidak ada item.</div></td></tr>`;
            return;
        }

        const csrf = @json(csrf_token());
        const canSetUnitCost = @json($canSetUnitCost);

        desktopTbody.innerHTML = lines.map(r => {
            const itemCode = escapeHtml(r.item_code);
            const itemName = escapeHtml(r.item_name);
            const notes = escapeHtml(r.notes);
            const setCostUrl = escapeHtml(r.set_cost_url || '#');

            const sys = fmt2(r.system_qty);

            const fisik = (r.physical_qty === null || r.physical_qty === undefined)
                ? `<span class="meta">-</span>`
                : fmt2(r.physical_qty);

            const diff = (r.diff_qty === null || r.diff_qty === undefined)
                ? `<span class="meta">-</span>`
                : `${Number(r.diff_qty) > 0 ? '+' : ''}${fmt2(r.diff_qty)}`;

            const val = (r.value === null || r.value === undefined)
                ? `<span class="meta">-</span>`
                : `${Number(r.value) > 0 ? '+Rp' : '-Rp'} ${fmt0abs(r.value)}`;

            const unitCost = (r.unit_cost === null || r.unit_cost === undefined)
                ? null
                : Number(r.unit_cost);

            const hpp = unitCost && unitCost > 0
                ? `<div class="text-mono fw-semibold">Rp ${fmt0abs(unitCost)}</div>`
                : (canSetUnitCost
                    ? `<form method="POST" action="${setCostUrl}" class="hpp-inline-form">
                        <input type="hidden" name="_token" value="${csrf}">
                        <input type="number" name="unit_cost" class="form-control form-control-sm" min="1" step="0.01" placeholder="HPP" required>
                        <button type="submit" class="btn btn-sm btn-dark hpp-save-btn">OK</button>
                      </form>`
                    : `<span class="hpp-missing">Belum ada</span>`);

            const physicalValue = (r.physical_value === null || r.physical_value === undefined)
                ? `<span class="meta">-</span>`
                : `Rp ${fmt0abs(r.physical_value)}`;

            const tone = escapeHtml(r.tone || '');

            return `
<tr>
  <td>${r.no}</td>
  <td>
    <div class="fw-semibold">${itemCode}</div>
    <div class="meta">${itemName}</div>
  </td>
  <td class="text-end text-mono">${sys}</td>
  <td class="text-end text-mono">${fisik}</td>
  <td class="text-end text-mono ${tone}">${diff}</td>
  <td class="text-end">${hpp}</td>
  <td class="text-end text-mono">${physicalValue}</td>
  <td class="text-end text-mono ${tone}">${val}</td>
  <td class="col-notes-compact"><div class="note-text">${notes}</div></td>
</tr>`;
        }).join('');
    }

    function renderMobileRows(lines){
        if(!mobileTbody) return;
        if(!lines || lines.length === 0){
            mobileTbody.innerHTML = `<tr><td colspan="3" class="text-center py-4"><div class="fw-semibold">Tidak ada item.</div></td></tr>`;
            return;
        }

        mobileTbody.innerHTML = lines.map(r => {
            const itemCode = escapeHtml(r.item_code);
            const itemName = escapeHtml(r.item_name);
            const setCostUrl = escapeHtml(r.set_cost_url || '#');
            const csrf = @json(csrf_token());
            const canSetUnitCost = @json($canSetUnitCost);

            const sys = fmt2(r.system_qty);
            const fisik = (r.physical_qty === null || r.physical_qty === undefined) ? '-' : fmt2(r.physical_qty);

            const diff = (r.diff_qty === null || r.diff_qty === undefined)
                ? '-'
                : `${Number(r.diff_qty) > 0 ? '+' : ''}${fmt2(r.diff_qty)}`;

            const val = (r.value === null || r.value === undefined)
                ? '-'
                : `${Number(r.value) > 0 ? '+' : '-'}${fmt0abs(r.value)}`;

            const unitCost = (r.unit_cost === null || r.unit_cost === undefined) ? null : Number(r.unit_cost);
            const hpp = unitCost && unitCost > 0 ? fmt0abs(unitCost) : '-';
            const physicalValue = (r.physical_value === null || r.physical_value === undefined) ? '-' : fmt0abs(r.physical_value);
            const hppForm = (!unitCost || unitCost <= 0) && canSetUnitCost
                ? `<form method="POST" action="${setCostUrl}" class="hpp-inline-form mt-2" style="justify-content:flex-start;">
                    <input type="hidden" name="_token" value="${csrf}">
                    <input type="number" name="unit_cost" class="form-control form-control-sm" min="1" step="0.01" placeholder="Isi HPP" required>
                    <button type="submit" class="btn btn-sm btn-dark hpp-save-btn">Simpan</button>
                  </form>`
                : '';

            let tone = 'tone-success';
            if(r.diff_qty !== null && r.diff_qty !== undefined){
                const d = Number(r.diff_qty);
                tone = d < 0 ? 'tone-danger' : (d > 0 ? 'tone-warning' : 'tone-success');
            }

            return `
<tr>
  <td class="text-mono">${r.no}</td>
  <td>
    <div class="m-item-title">${itemCode}</div>
    ${itemName ? `<div class="m-item-sub">${itemName}</div>` : ``}
    <div class="m-badges">
      <span class="mini-badge"><span class="k">Sys</span><span class="v">${sys}</span></span>
      <span class="mini-badge"><span class="k">F</span><span class="v">${fisik}</span></span>
      <span class="mini-badge ${(!unitCost || unitCost <= 0) ? 'tone-danger' : ''}"><span class="k">HPP</span><span class="v">${hpp}</span></span>
      <span class="mini-badge"><span class="k">Nilai</span><span class="v">${physicalValue}</span></span>
      <span class="mini-badge ${tone}"><span class="k">Rp</span><span class="v">${val}</span></span>
    </div>
    ${hppForm}
  </td>
  <td class="text-end text-mono">
    <span class="mini-badge ${tone}" style="justify-content:flex-end;"><span class="v">${diff}</span></span>
  </td>
</tr>`;
        }).join('');
    }

    async function fetchAndRender(force = false){
        syncDiffInputs();
        const qs = new URLSearchParams(new FormData(form)).toString();
        if(!force && qs === lastQs) return;
        lastQs = qs;

        // url untuk history (rapih)
        const urlForHistory = `${showUrl}?${qs}`;
        window.history.replaceState({}, '', urlForHistory);

        if(abortCtrl) abortCtrl.abort();
        abortCtrl = new AbortController();

        setLoading(true);

        try{
            const res = await fetch(urlForHistory, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                signal: abortCtrl.signal,
            });

            if(!res.ok) throw new Error(`HTTP ${res.status}`);

            const data = await res.json();
            if(!data || !data.ok) throw new Error('Invalid response');

            renderDesktopRows(data.lines || []);
            renderMobileRows(data.lines || []);

            if(countEl){
                const txt = `${data.count ?? 0} item`;
                countEl.textContent = txt;
                countEl.dataset.base = txt;
            }
            if(kpiShown) kpiShown.textContent = new Intl.NumberFormat('id-ID').format(Number(data.count ?? 0));

            applySummary(data.summary);

            if(typeof setMaxHeight10RowsForShowTables === 'function'){
                setMaxHeight10RowsForShowTables();
            }
        }catch(err){
            if(err.name === 'AbortError') return;
            console.error(err);
        }finally{
            setLoading(false);
        }
    }

    const fetchDebounced = debounce(function(){ fetchAndRender(false); }, 350);

    if(qInput){
        qInput.addEventListener('focus', function(){
            setTimeout(() => {
                try { qInput.select(); } catch (_) {}
            }, 0);
        });
        qInput.addEventListener('click', function(){
            setTimeout(() => {
                try { qInput.select(); } catch (_) {}
            }, 0);
        });
        qInput.addEventListener('input', function(){
            const start = qInput.selectionStart;
            const end = qInput.selectionEnd;
            qInput.value = qInput.value.toUpperCase();
            try { qInput.setSelectionRange(start, end); } catch (_) {}
            fetchDebounced();
        });
        qInput.addEventListener('keydown', function(e){
            if(e.key === 'Enter'){
                e.preventDefault();
                fetchAndRender(true);
            }
        });
    }

    form.querySelectorAll('select, input[type="checkbox"]').forEach(el => {
        el.addEventListener('change', function(){ fetchAndRender(true); });
    });

    document.querySelectorAll('.js-so-sort').forEach(btn => {
        btn.addEventListener('click', function(){
            if(!sortSelect || !sortDir) return;

            const nextSort = btn.dataset.sort || 'item';
            const sameSort = sortSelect.value === nextSort;

            sortSelect.value = nextSort;
            sortDir.value = sameSort && sortDir.value === 'asc' ? 'desc' : 'asc';

            syncSortButtons();
            fetchAndRender(true);
        });
    });

    form.addEventListener('submit', function(e){
        e.preventDefault();
        fetchAndRender(true);
    });

    syncSortButtons();
});
</script>
@endpush
