{{-- resources/views/production/finishing_jobs/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Finishing')

@push('head')
<style>
    :root{
        --r:14px; --b: rgba(148,163,184,.22);
        --muted:#6b7280; --soft2: rgba(148,163,184,.05);
        --accent:#2563eb; --ok:#16a34a; --rj:#b91c1c;
        --shadow: 0 10px 26px rgba(15,23,42,.08), 0 0 0 1px rgba(15,23,42,.03);
        --bottom-nav-h:72px; --fab-gap:12px;
        --fab-bottom: calc(var(--bottom-nav-h) + var(--fab-gap) + env(safe-area-inset-bottom));
    }

    .page-wrap{ max-width:980px; margin:0 auto; padding:14px 12px 96px; }
    @media(max-width:767.98px){
        .page-wrap{ padding-bottom: calc(var(--bottom-nav-h) + 130px + var(--vv-kbd)); }
        body.keyboard-open .page-wrap{ padding-bottom: calc(14rem + var(--vv-kbd)); }
        .modal-dialog{ margin:.75rem; }
        .modal-content{ border-radius:16px; }
        .modal-body{ max-height: calc(100vh - 210px); overflow:auto; }
    }

    .panel{ background:var(--card); border:1px solid var(--b); border-radius:var(--r); box-shadow:var(--shadow); }
    .panel-h{ padding:9px 12px; border-bottom:1px solid rgba(148,163,184,.12); }
    .panel-b{ padding:10px 12px; }

    .h-title{ font-weight:900; font-size:.95rem; margin:0; }

    .meta{
        border: 1px solid rgba(148,163,184,.18);
        border-radius: var(--r);
        padding: 8px;
        background: var(--soft2);
    }
    body[data-theme="dark"] .meta{ background: rgba(15,23,42,.35); }

    .form-label-sm{ font-size:.68rem; font-weight:800; color:var(--muted); }
    .form-control-sm, .form-select-sm{ font-size:.82rem; padding:.28rem .45rem; border-radius:10px; }

    .mono{ font-variant-numeric: tabular-nums; font-family: ui-monospace,SFMono-Regular,Menlo,Consolas; }

    .list{ display:grid; gap:.45rem; margin-top:10px; }

    .cardx{
        border:1px solid rgba(148,163,184,.22);
        border-radius:12px;
        background:var(--card);
        overflow:hidden;
    }
    .cardx-h{
        padding:7px 10px 2px;
        border-bottom:1px solid rgba(148,163,184,.12);
        display:flex; justify-content:space-between; gap:10px; align-items:flex-start;
    }
    .cardx-left{ display:flex; gap:10px; align-items:flex-start; min-width:0; }
    .cardx-left>div{ min-width:0; }

    .chk{ width:16px; height:16px; border-radius:6px; cursor:pointer; margin-top:2px; flex:0 0 auto; }
    .code{
        font-weight:900; letter-spacing:.08em; color:var(--accent);
        font-size:.92rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%;
    }
    .op-chip{
        display:inline-flex; align-items:center; gap:.35rem; margin-top:.35rem;
        padding:.18rem .5rem; border-radius:999px;
        border:1px solid rgba(148,163,184,.22);
        font-size:.72rem; font-weight:900; color:var(--muted);
        background: rgba(148,163,184,.04); white-space:nowrap;
    }
    body[data-theme="dark"] .op-chip{ background: rgba(15,23,42,.18); }

    .wip{ font-size:.78rem; color:var(--muted); font-weight:900; white-space:nowrap; text-align:right; flex:0 0 auto; }

    .meta-inline{ margin-top:.18rem; font-size:.68rem; color:var(--muted); font-weight:900; display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; }
    .meta-inline .dot{ opacity:.5; }

    .mini-kpi-row{
        display:grid; grid-template-columns:repeat(4,minmax(0,1fr));
        margin-top:.4rem; border:1px solid rgba(148,163,184,.15);
        border-radius:10px; overflow:hidden; background:rgba(255,255,255,.42);
    }
    body[data-theme="dark"] .mini-kpi-row{ background:rgba(15,23,42,.20); }
    .mini-kpi{
        border-right:1px solid rgba(148,163,184,.16);
        padding:.25rem .38rem; min-width:0; text-align:center;
    }
    .mini-kpi:last-child{ border-right:0; }
    .mini-kpi .lbl{
        display:block; color:var(--muted); font-size:.52rem; font-weight:900;
        text-transform:uppercase; letter-spacing:.06em; white-space:nowrap;
        overflow:hidden; text-overflow:ellipsis;
    }
    .mini-kpi .val{
        display:flex; align-items:baseline; justify-content:center; gap:.12rem;
        font-size:.82rem; font-weight:900; margin-top:.08rem;
        white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .mini-kpi .unit{ color:var(--muted); font-size:.65em; font-weight:900; }
    .mini-kpi.is-main{ background:rgba(37,99,235,.06); }
    .mini-kpi.is-main .val{ color:#2563eb; }

    .cardx-b{ padding:5px 10px 8px; display:grid; gap:.38rem; }
    .grid2{ display:grid; grid-template-columns:1fr 1fr; gap:.4rem; }
    .field label{
        display:block; font-size:.65rem; font-weight:900; color:var(--muted);
        text-transform:uppercase; letter-spacing:.08em; margin-bottom:.25rem;
    }

    .qty{ text-align:center !important; font-weight:900; padding:.32rem .4rem !important; border-radius:10px; }
    .qty.ok{ border:1px solid rgba(22,163,74,.22); background: rgba(22,163,74,.05); }
    .qty.rj{ border:1px solid rgba(185,28,28,.22); background: rgba(185,28,28,.05); }
    .qty:focus{ box-shadow:none; }

    .notes{ display:none; }
    .notes.is-show{ display:block; }
    .notes input{ border-radius:12px; }

    .fab-wrap{
        position:fixed; right:14px; bottom:var(--fab-bottom);
        z-index:1090; display:flex; gap:10px; align-items:center;
        pointer-events:none;
    }
    .fab-wrap .btn{
        pointer-events:auto;
        border-radius:999px;
        font-weight:900;
        box-shadow: 0 12px 26px rgba(15,23,42,.22), 0 4px 10px rgba(15,23,42,.14);
    }
    .fab-back{ width:46px; padding-left:0; padding-right:0; }
    .fab-save{ width:auto; padding:.62rem 1.05rem; white-space:nowrap; }

    /* summary mini box (info baris operator) */
    .mini-box{
        border:1px solid rgba(148,163,184,.18);
        border-radius:14px;
        padding:10px 12px;
        background: rgba(148,163,184,.06);
        height: 100%;
    }
    body[data-theme="dark"] .mini-box{ background: rgba(15,23,42,.25); }
    .mini-top{ display:flex; justify-content:space-between; gap:10px; align-items:center; }
    .mini-top .ttl{ font-weight:900; color: var(--muted); font-size:.78rem; text-transform:uppercase; letter-spacing:.08em; }
    .mini-top .val{ font-weight:900; font-size:1rem; }

    /* modal summary */
    .sum-box{
        border:1px solid rgba(148,163,184,.18);
        border-radius:14px;
        padding:10px 12px;
        background: rgba(148,163,184,.06);
    }
    body[data-theme="dark"] .sum-box{ background: rgba(15,23,42,.25); }

    .sum-top{ display:flex; justify-content:space-between; gap:10px; align-items:flex-start; flex-wrap:wrap; }
    .sum-top .ttl{ font-weight:900; }
    .sum-top .sub{ color:var(--muted); font-weight:900; font-size:.82rem; }
    .sum-pillrow{ display:grid; grid-template-columns:1fr 1fr 1fr; gap:.5rem; margin-top:.65rem; }

    .sum-pill{
        border:1px solid rgba(148,163,184,.18);
        border-radius:999px;
        padding:.35rem .6rem;
        text-align:center;
        font-weight:900;
        font-size:.82rem;
        background: rgba(255,255,255,.55);
    }
    body[data-theme="dark"] .sum-pill{ background: rgba(15,23,42,.15); }
    .sum-pill .lbl{
        display:block; font-size:.7rem; color:var(--muted);
        font-weight:900; letter-spacing:.08em; text-transform:uppercase;
    }
    .sum-pill .val{ display:block; margin-top:.12rem; }

    /* accordion — override Bootstrap supaya mirip cardx */
    .accordion-item.all-item {
        border: 1px solid rgba(148,163,184,.18) !important;
        background: var(--card) !important;
    }
    .acc-op-btn {
        background: var(--card) !important;
        color: inherit !important;
        padding: 7px 10px 5px !important;
        box-shadow: none !important;
        font-size: .88rem;
        font-weight: 900;
    }
    .acc-op-btn:not(.collapsed) {
        color: inherit !important;
        background: var(--card) !important;
        border-bottom: 1px solid rgba(148,163,184,.12);
    }
    /* sembunyikan chevron Bootstrap */
    .acc-op-btn::after { display: none !important; }

    .acc-pill{
        display:inline-flex; align-items:center; gap:.3rem;
        padding:.1rem .4rem; border-radius:999px;
        border:1px solid rgba(148,163,184,.15);
        background: rgba(148,163,184,.06);
        font-weight:900; font-size:.72rem; color:var(--muted);
    }
    body[data-theme="dark"] .acc-pill{ background: rgba(15,23,42,.22); }
    .acc-op-sub{ font-size:.72rem; font-weight:900; color:var(--muted); }

    .finishing-financial-section{
        border:1px solid rgba(148,163,184,.2);
        border-radius:14px;
        overflow:hidden;
        margin-top:.65rem;
        background:var(--card);
    }
    .finishing-financial-section > summary{
        list-style:none;
        cursor:pointer;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:.7rem;
        padding:.7rem .8rem;
    }
    .finishing-financial-section > summary::-webkit-details-marker{ display:none; }
    .finishing-financial-section > summary::after{
        content:'⌄';
        color:var(--muted);
        font-size:1rem;
        line-height:1;
        transition:transform .18s ease;
    }
    .finishing-financial-section[open] > summary::after{ transform:rotate(180deg); }
    .finishing-financial-title{ color:var(--text); font-size:.78rem; font-weight:900; }
    .finishing-financial-subtitle{ color:var(--muted); font-size:.66rem; line-height:1.35; margin-top:.12rem; }
    .finishing-financial-body{ padding:.7rem .8rem .8rem; border-top:1px solid rgba(148,163,184,.15); }
    .finishing-financial-grid{ display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.35rem; }
    .finishing-financial-item{
        min-width:0;
        border:1px solid rgba(148,163,184,.16);
        border-radius:10px;
        padding:.38rem .45rem;
        background:rgba(148,163,184,.045);
    }
    .finishing-financial-item span{ display:block; color:var(--muted); font-size:.59rem; font-weight:800; text-transform:uppercase; }
    .finishing-financial-item strong{ display:block; color:var(--text); font-size:.76rem; margin-top:.08rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .finishing-financial-item.is-total{ border-color:rgba(37,99,235,.25); background:rgba(219,234,254,.62); }
    .finishing-financial-item.is-total strong{ color:#1d4ed8; }
    .finishing-financial-table{ font-size:.68rem; margin-top:.6rem; width:100%; }
    .finishing-financial-table th{ color:var(--muted); font-size:.59rem; text-transform:uppercase; white-space:nowrap; }
    .finishing-financial-table td,
    .finishing-financial-table th{ padding:.3rem .38rem; vertical-align:middle; }
    .finishing-financial-note{ color:var(--muted); font-size:.64rem; line-height:1.4; }
    .finishing-financial-unit{ display:block; color:var(--muted); font-size:.59rem; line-height:1.25; white-space:nowrap; }
    .finishing-financial-section.hpp-section{ border-color:rgba(37,99,235,.2); }
    .finishing-financial-section.hpp-section > summary{ background:rgba(37,99,235,.045); }
    .finishing-financial-section.hpp-section .finishing-financial-title{ color:#1d4ed8; }
    .finishing-financial-section.accounting-section{ border-color:rgba(20,184,166,.2); }
    .finishing-financial-section.accounting-section > summary{ background:rgba(20,184,166,.045); }
    .finishing-financial-section.accounting-section .finishing-financial-title{ color:#0f766e; }
    .finishing-financial-section.accounting-section .finishing-financial-item.is-total{ border-color:rgba(20,184,166,.25); background:rgba(204,251,241,.62); }
    .finishing-financial-section.accounting-section .finishing-financial-item.is-total strong{ color:#0f766e; }
    @media(max-width:767.98px){
        .finishing-financial-grid{ gap:.28rem; }
        .finishing-financial-item{ padding:.32rem .35rem; }
        .finishing-financial-item strong{ font-size:.67rem; }
        .finishing-financial-table{ min-width:620px; }
    }
    body[data-theme="dark"] .finishing-financial-section{ border-color:rgba(71,85,105,.82); }
    body[data-theme="dark"] .finishing-financial-section > summary{ background:rgba(148,163,184,.08); }
    body[data-theme="dark"] .finishing-financial-section.hpp-section > summary{ background:rgba(37,99,235,.12); }
    body[data-theme="dark"] .finishing-financial-section.hpp-section .finishing-financial-title{ color:#93c5fd; }
    body[data-theme="dark"] .finishing-financial-section.accounting-section > summary{ background:rgba(20,184,166,.12); }
    body[data-theme="dark"] .finishing-financial-section.accounting-section .finishing-financial-title,
    body[data-theme="dark"] .finishing-financial-section.accounting-section .finishing-financial-item.is-total strong{ color:#5eead4; }
    body[data-theme="dark"] .finishing-financial-item{ background:rgba(15,23,42,.42); }
</style>
@endpush

@section('content')
<div class="page-wrap">
@php
    $dateValue = old('date', $dateDefault ?? now()->toDateString());
    $linesAll = $linesAll ?? [];
    $linesByOp = $linesByOp ?? [];

    $operatorOptions = collect($linesByOp)
        ->filter(fn($l) => (int)($l['total_wip'] ?? 0) > 0)
        ->map(function($l){
            $id = $l['operator_id'] ?? null;
            if(!$id) return null;
            $label = trim(($l['operator_code'] ? $l['operator_code'].' — ' : '').($l['operator_name'] ?? ''));
            return ['id'=>(int)$id,'label'=>$label !== '' ? $label : 'OP-'.(int)$id];
        })
        ->filter()
        ->unique('id')
        ->sortBy('label')
        ->values();

    $itemOptionsBase = collect(array_merge($linesAll, $linesByOp))
        ->filter(fn($l) => (int)($l['total_wip'] ?? 0) > 0)
        ->map(function($l){
            $id = $l['item_id'] ?? null;
            $code = strtoupper($l['item_code'] ?? ('ITEM-'.$id));
            return ['id'=>$id, 'code'=>$code];
        })
        ->filter(fn($x)=>!empty($x['id']))
        ->unique('id')
        ->sortBy('code')
        ->values();

    $hasAnyWipAll = collect($linesAll)->sum(fn($l)=>(int)($l['total_wip'] ?? 0)) > 0;
    $hasAnyWipByOp = collect($linesByOp)->sum(fn($l)=>(int)($l['total_wip'] ?? 0)) > 0;

    $summaryRows = collect($linesAll)->filter(fn($l)=>(int)($l['total_wip'] ?? 0) > 0)->count();
    $summaryQty  = collect($linesAll)->sum(fn($l)=> (int)($l['total_wip'] ?? 0));
    $summaryOps  = collect($linesByOp)->filter(fn($l)=>(int)($l['total_wip'] ?? 0) > 0)->pluck('operator_id')->filter(fn($id)=>(int)$id>0)->unique()->count();

    $wipUnitCostByItemId = $wipUnitCostByItemId ?? [];
    $finishingSupplyUnitCostByItemId = $finishingSupplyUnitCostByItemId ?? [];
    $canCorrectStock = auth()->user()?->isOwner() ?? false;
@endphp

    <div class="panel mb-2">
        <div class="panel-h d-flex align-items-start justify-content-between gap-2 flex-wrap">
            <div>
                <div class="h-title">Finishing</div>
            </div>
            <a href="{{ route('production.finishing_jobs.index') }}"
               class="btn btn-sm btn-outline-primary" style="border-radius:999px;">Riwayat</a>
        </div>
    </div>

    {{-- ── AUTO BOM PACKING ── --}}
    @php
        $ps         = $packingSupplies ?? collect();
        $psMaterials = $ps->flatMap(fn ($group) => $group->materials ?? collect());
        $psShortage = $psMaterials->where('has_shortage', true)->count();
        $psTotal    = $psMaterials->count();
    @endphp
    @if($hasAnyWipAll)
    <div class="panel mb-2">
        <div class="panel-h d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <div>
                    <div class="h-title" style="font-size:.85rem;">📦 Auto BOM Packing</div>
                    <div style="color:var(--muted);font-size:.68rem;margin-top:.12rem;">
                        Preview saja. RM dipotong saat Finishing di-POST untuk Qty OK.
                    </div>
                </div>
                @if($psShortage > 0)
                    <span style="background:rgba(220,38,38,.12);color:#b91c1c;border:1px solid rgba(220,38,38,.3);border-radius:999px;padding:.1rem .55rem;font-size:.68rem;font-weight:900;">
                        {{ $psShortage }} KURANG
                    </span>
                @else
                    <span style="background:rgba(22,163,74,.1);color:#15803d;border:1px solid rgba(22,163,74,.3);border-radius:999px;padding:.1rem .55rem;font-size:.68rem;font-weight:900;">
                        ✓ SIAP SAAT POST
                    </span>
                @endif
            </div>
        </div>
        <div class="panel-b" style="padding-top:.5rem;padding-bottom:.5rem;">
            @foreach($ps as $group)
            <div style="border:1px solid rgba(148,163,184,.15);border-radius:10px;padding:.45rem .55rem;margin-bottom:.5rem;">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-1">
                    <div>
                        <span style="font-weight:900;font-size:.8rem;">{{ $group->item_code }}</span>
                        <span style="color:var(--muted);font-size:.7rem;margin-left:.3rem;">{{ $group->item_name }}</span>
                    </div>
                    <span style="color:var(--muted);font-size:.68rem;font-weight:800;">WIP {{ number_format($group->wip_qty, 0, ',', '.') }}</span>
                </div>
                <div style="display:grid;grid-template-columns:1fr 72px 72px 110px;gap:.25rem .5rem;font-size:.65rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;padding:.1rem .1rem .25rem;border-bottom:1px solid rgba(148,163,184,.15);">
                    <span>Material terkait</span><span style="text-align:right;">Butuh</span><span style="text-align:right;">Stok RM</span><span style="text-align:right;">Status</span>
                </div>
                @foreach($group->materials as $sup)
                <div style="display:grid;grid-template-columns:1fr 72px 72px 110px;gap:.2rem .5rem;padding:.35rem .1rem;border-bottom:1px solid rgba(148,163,184,.08);align-items:center;">
                    <div>
                        <span style="font-weight:700;font-size:.78rem;">{{ $sup->name }}</span>
                        <span style="color:var(--muted);font-size:.66rem;margin-left:.3rem;">[{{ $sup->code }}]</span>
                    </div>
                    <div style="text-align:right;font-size:.76rem;font-weight:700;">{{ number_format($sup->required_qty, 2, ',', '.') }} <span style="color:var(--muted);font-size:.65em;">{{ $sup->unit }}</span></div>
                    <div style="text-align:right;font-size:.76rem;font-weight:700;color:{{ $sup->stock_qty < $sup->required_qty ? '#b91c1c' : '#15803d' }};">{{ number_format($sup->stock_qty, 2, ',', '.') }}</div>
                    <div style="text-align:right;">
                        @if($sup->has_shortage)
                            <div class="d-flex align-items-center justify-content-end gap-1 flex-wrap">
                                <span style="background:rgba(220,38,38,.1);color:#b91c1c;border:1px solid rgba(220,38,38,.25);border-radius:6px;padding:.1rem .35rem;font-size:.62rem;font-weight:900;white-space:nowrap;">-{{ number_format($sup->shortage_qty, 2, ',', '.') }}</span>
                                <button type="button" class="btn btn-sm btn-outline-danger material-correction-trigger" style="border-radius:7px;font-size:.62rem;font-weight:900;padding:.18rem .35rem;" data-bs-toggle="modal" data-bs-target="#materialCorrectionModal" data-finishing-item-id="{{ $group->item_id }}" data-finishing-item-label="{{ $group->item_code }} — {{ $group->item_name }}" data-material-item-id="{{ $sup->id }}" data-material-label="{{ $sup->code }} — {{ $sup->name }}" data-qty="{{ $sup->shortage_qty }}" data-unit="{{ $sup->unit }}">Koreksi Material</button>
                            </div>
                        @else
                            <span style="color:#15803d;font-size:.72rem;font-weight:900;">✓</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endforeach
            @if($psTotal === 0)
                <div class="text-muted" style="font-size:.72rem;padding:.5rem .1rem;">
                    Belum ada BOM <span class="mono">packing_supply</span> aktif untuk WIP yang tersedia.
                </div>
            @endif
        </div>
    </div>
    @endif

    <div class="modal fade" id="materialCorrectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('production.finishing_material_corrections.store') }}">
                    @csrf
                    <input type="hidden" name="finishing_item_id" id="correction-finishing-item-id">
                    <input type="hidden" name="material_item_id" id="correction-material-item-id">
                    <div class="modal-header"><h5 class="modal-title">Koreksi Material</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <div class="alert alert-light border py-2 mb-3" style="font-size:.78rem;"><div class="fw-bold" id="correction-finishing-label">-</div><div class="text-muted" id="correction-material-label">-</div></div>
                        <label class="form-label form-label-sm">Jenis koreksi</label>
                        <select class="form-select form-select-sm mb-3" name="action" required>
                            @if($canCorrectStock)
                                <option value="stock">Koreksi Persediaan</option>
                            @else
                                <option value="owner_contact" disabled>Koreksi Persediaan — Hubungi Owner</option>
                            @endif
                            <option value="bom">Koreksi BOM</option>
                            <option value="purchase">Kebutuhan Pembelian</option>
                        </select>
                        <label class="form-label form-label-sm">Qty koreksi <span class="text-muted" id="correction-unit"></span></label>
                        <input class="form-control form-control-sm mb-3" type="number" name="qty" id="correction-qty" min="0.001" step="0.001" required>
                        <label class="form-label form-label-sm">Alasan <span class="text-danger">*</span></label>
                        <textarea class="form-control form-control-sm" name="reason" rows="3" minlength="3" maxlength="2000" required placeholder="Jelaskan penyebab dan tindakan koreksi"></textarea>
                        <div class="text-muted mt-2" style="font-size:.68rem;">Pembuat dan waktu koreksi dicatat otomatis untuk audit trail.</div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-sm btn-primary">Simpan Koreksi</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="panel">
        <form id="finishing-form" action="{{ route('production.finishing_jobs.store') }}" method="POST" novalidate>
            @csrf
            <input type="hidden" name="operator_mode" id="operator_mode" value="{{ old('operator_mode','all') }}">
            <input type="hidden" name="operator_global_id" id="operator_global_id_hidden" value="{{ old('operator_global_id','') }}">

            <div class="panel-b">

                <div class="meta">
                    <div class="row g-2 align-items-end">
                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm">Tanggal</label>
                            <input type="date" name="date"
                                   class="form-control form-control-sm @error('date') is-invalid @enderror"
                                   value="{{ $dateValue }}">
                            @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm">Tujuan Gudang</label>
                            <select name="destination_warehouse_id"
                                    class="form-select form-select-sm @error('destination_warehouse_id') is-invalid @enderror"
                                    required>
                                <option value="" disabled @selected(blank(old('destination_warehouse_id')))>
                                    Silakan pilih gudang
                                </option>
                                @foreach($destinationWarehouses ?? [] as $wh)
                                    <option value="{{ $wh->id }}"
                                        @selected(old('destination_warehouse_id') == $wh->id)>
                                        {{ $wh->code }} — {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('destination_warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm">Filter operator</label>
                            <select id="op-filter" class="form-select form-select-sm">
                                <option value="">Semua (gabung)</option>
                                @foreach($operatorOptions as $opt)
                                    <option value="{{ $opt['id'] }}"
                                        @selected(old('operator_global_id','') == $opt['id'])>
                                        {{ $opt['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if(strtolower(auth()->user()?->role ?? '') !== 'operating')
                        <div class="col-12 col-md-3">
                            <label class="form-label form-label-sm">Filter item</label>
                            <select id="item-filter" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                @foreach($itemOptionsBase as $opt)
                                    <option value="{{ $opt['id'] }}">{{ $opt['code'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <select id="item-filter" class="d-none">
                            <option value="">Semua</option>
                            @foreach($itemOptionsBase as $opt)
                                <option value="{{ $opt['id'] }}">{{ $opt['code'] }}</option>
                            @endforeach
                        </select>
                        @endif

                        <div class="col-12 col-md-3">
                            <label class="form-label form-label-sm">Cari kode</label>
                            <input type="text" id="q" class="form-control form-control-sm mono"
                                   placeholder="Cari item..." autocomplete="off">
                        </div>
                    </div>

                    <div class="mini-kpi-row">
                        <div class="mini-kpi">
                            <span class="lbl">Item</span>
                            <span class="val mono">{{ number_format($summaryRows,0,',','.') }}</span>
                        </div>
                        <div class="mini-kpi is-main">
                            <span class="lbl">Total WIP</span>
                            <span class="val mono">{{ number_format($summaryQty,0,',','.') }}<span class="unit">pcs</span></span>
                        </div>
                        <div class="mini-kpi">
                            <span class="lbl">Operator</span>
                            <span class="val mono">{{ number_format($summaryOps,0,',','.') }}</span>
                        </div>
                        <div class="mini-kpi">
                            <span class="lbl">Baris OP</span>
                            <span class="val mono" id="op-row-count">-</span>
                        </div>
                    </div>
                </div>

                @if (auth()->user()?->isOwner())
                    <details class="finishing-financial-section hpp-section" id="finishing-hpp-section">
                        <summary>
                            <div>
                                <div class="finishing-financial-title">
                                    <i class="bi bi-calculator me-1" aria-hidden="true"></i> Estimasi HPP Finishing
                                </div>
                                <div class="finishing-financial-subtitle">
                                    Nilai WIP-FIN yang diproses ditambah Auto BOM Packing untuk Qty OK. Read-only.
                                </div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border" id="finishing-hpp-badge">Belum pilih</span>
                        </summary>
                        <div class="finishing-financial-body">
                            <div class="finishing-financial-grid">
                                <div class="finishing-financial-item">
                                    <span>Nilai WIP-FIN</span>
                                    <strong class="mono" id="finishing-hpp-wip-total">Rp 0</strong>
                                </div>
                                <div class="finishing-financial-item">
                                    <span>BOM packing</span>
                                    <strong class="mono" id="finishing-hpp-supply-total">Rp 0</strong>
                                </div>
                                <div class="finishing-financial-item is-total">
                                    <span>Total HPP output</span>
                                    <strong class="mono" id="finishing-hpp-total">Rp 0</strong>
                                </div>
                            </div>
                            <div class="finishing-financial-note mt-2" id="finishing-hpp-note">
                                Isi qty setor atau reject untuk melihat estimasi HPP. BOM packing hanya dihitung untuk Qty OK.
                            </div>
                            <div class="table-responsive mt-2">
                                <table class="table table-sm align-middle finishing-financial-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th class="text-end">OK</th>
                                            <th class="text-end">Reject</th>
                                            <th class="text-end">WIP-FIN / pcs</th>
                                            <th class="text-end">BOM / pcs</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="finishing-hpp-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </details>

                    <details class="finishing-financial-section accounting-section" id="finishing-accounting-section">
                        <summary>
                            <div>
                                <div class="finishing-financial-title">
                                    <i class="bi bi-journal-check me-1" aria-hidden="true"></i> Akuntansi Baru
                                </div>
                                <div class="finishing-financial-subtitle">
                                    Preview mirror jurnal Finishing: output barang jadi/reject dan konsumsi Auto BOM Packing. Legacy tidak diubah.
                                </div>
                            </div>
                            <span class="badge bg-success text-white" id="finishing-accounting-badge">Belum pilih</span>
                        </summary>
                        <div class="finishing-financial-body">
                            <div class="finishing-financial-grid">
                                <div class="finishing-financial-item">
                                    <span>Total debit</span>
                                    <strong class="mono" id="finishing-accounting-debit">Rp 0</strong>
                                </div>
                                <div class="finishing-financial-item">
                                    <span>Total kredit</span>
                                    <strong class="mono" id="finishing-accounting-credit">Rp 0</strong>
                                </div>
                                <div class="finishing-financial-item is-total">
                                    <span>Status</span>
                                    <strong id="finishing-accounting-status">Belum pilih</strong>
                                </div>
                            </div>
                            <div class="finishing-financial-note mt-2" id="finishing-accounting-note">
                                Mapping: WIP Finishing <span class="mono">1-151-03</span>, Barang Jadi <span class="mono">1-152</span>, Reject <span class="mono">1-153</span>, Bahan <span class="mono">1-150</span>.
                            </div>
                            <div class="table-responsive mt-2">
                                <table class="table table-sm align-middle finishing-financial-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Skenario</th>
                                            <th>Debit</th>
                                            <th>Kredit</th>
                                            <th class="text-end">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody id="finishing-accounting-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </details>
                @endif

                {{-- LIST ALL (dashboard only) --}}
                <div class="list" id="list-all">
                    @if(!$hasAnyWipAll)
                        <div class="text-center py-4 text-muted">Tidak ada WIP-FIN.</div>
                    @else
                        @php
                            $opsByItem = collect($linesByOp)
                                ->filter(fn($l) => (int)($l['total_wip'] ?? 0) > 0)
                                ->groupBy(fn($l) => (int)($l['item_id'] ?? 0))
                                ->map(function($rows){
                                    return collect($rows)->map(function($r){
                                        $opId = (int)($r['operator_id'] ?? 0);
                                        $opLabel = trim(($r['operator_code'] ? $r['operator_code'].' — ' : '').($r['operator_name'] ?? ''));
                                        if($opLabel === '' && $opId > 0) $opLabel = 'OP-'.$opId;
                                        return [
                                            'operator_id' => $opId,
                                            'operator_label' => $opLabel,
                                            'wip' => (int)($r['total_wip'] ?? 0),
                                            'setor_date' => $r['setor_date'] ?? null,
                                        ];
                                    })
                                    ->filter(fn($x)=>$x['wip']>0)
                                    ->sortBy('operator_label')
                                    ->values();
                                });
                        @endphp

                        <div class="accordion" id="all-items-accordion">
                            @php $no = 0; @endphp
                            @foreach($linesAll as $idx => $line)
                                @php
                                    $itemId = (int)($line['item_id'] ?? 0);
                                    $wip = (int)($line['total_wip'] ?? 0);
                                    $code = strtoupper($line['item_code'] ?? 'ITEM-'.$itemId);
                                    if($wip <= 0) continue;

                                    $no++;
                                    $ops = $opsByItem->get($itemId, collect());
                                    $opCount = $ops->pluck('operator_id')->unique()->count();
                                    $collapseId = 'allItemCollapse-'.$itemId.'-'.$idx;
                                    $headingId  = 'allItemHeading-'.$itemId.'-'.$idx;
                                @endphp

                                <div class="accordion-item all-item"
                                     data-code="{{ $code }}"
                                     data-item-id="{{ $itemId }}"
                                     style="border-radius:12px; overflow:hidden; border:1px solid rgba(148,163,184,.18); background:var(--card); margin-bottom:.38rem;">
                                    <h2 class="accordion-header" id="{{ $headingId }}">
                                        <button class="accordion-button collapsed acc-op-btn d-flex justify-content-between align-items-start w-100" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                                                aria-expanded="false" aria-controls="{{ $collapseId }}">
                                            <div class="min-w-0">
                                                <div class="code">{{ $code }}</div>
                                                <div class="meta-inline">
                                                    <span>{{ $opCount }} operator</span>
                                                    <span class="dot">·</span>
                                                    <span>WIP <span class="mono">{{ number_format($wip,0,',','.') }}</span></span>
                                                </div>
                                            </div>
                                            <div class="wip ms-2">SISA {{ number_format($wip,0,',','.') }}</div>
                                        </button>
                                    </h2>

                                    <div id="{{ $collapseId }}" class="accordion-collapse collapse"
                                         aria-labelledby="{{ $headingId }}" data-bs-parent="#all-items-accordion">
                                        <div class="accordion-body" style="padding:.45rem .65rem;">
                                            @if($opCount === 0)
                                                <div class="text-muted text-center py-2">Tidak ada detail operator.</div>
                                            @else
                                                <div class="table-responsive">
                                                    <table class="table table-sm align-middle mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th style="width:56px;">No</th>
                                                                <th>Operator</th>
                                                                <th class="text-end" style="width:160px;">WIP</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($ops as $i => $op)
                                                                <tr>
                                                                    <td class="mono">{{ $i+1 }}</td>
                                                                    <td class="mono" style="font-weight:900;">
                                                                        {{ $op['operator_label'] ?: ('OP-'.$op['operator_id']) }}
                                                                        @if(!empty($op['setor_date']))
                                                                            <div class="acc-op-sub mt-1" style="font-weight:700;">
                                                                                Setor {{ \Illuminate\Support\Carbon::parse($op['setor_date'])->locale('id')->translatedFormat('d M Y') }}
                                                                            </div>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-end mono" style="font-weight:900;">
                                                                        {{ number_format((int)$op['wip'],0,',','.') }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="mt-2 acc-op-sub">
                                                    Total item: <span class="mono">{{ number_format($wip,0,',','.') }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- LIST BYOP (input) --}}
                <div class="list" id="list-byop" style="display:none;">
                    @if(!$hasAnyWipByOp)
                        <div class="text-center py-4 text-muted">Tidak ada WIP-FIN.</div>
                    @else
                        @foreach($linesByOp as $idx => $line)
                            @php
                                $itemId = (int)($line['item_id'] ?? 0);
                                $wip = (int)($line['total_wip'] ?? 0);
                                $code = strtoupper($line['item_code'] ?? 'ITEM-'.$itemId);
                                if($wip <= 0) continue;

                                $opId = (int)($line['operator_id'] ?? 0);
                                $opLabel = trim(($line['operator_code'] ? $line['operator_code'].' — ' : '').($line['operator_name'] ?? ''));
                                if($opLabel === '' && $opId > 0) $opLabel = 'OP-'.$opId;
                            @endphp

                            <div class="cardx mono fin-item"
                                 data-idx="{{ $idx }}"
                                 data-item-id="{{ $itemId }}"
                                 data-operator-id="{{ $opId }}"
                                 data-operator-label="{{ $opLabel }}"
                                 data-code="{{ $code }}"
                                 data-wip="{{ $wip }}"
                                 data-item-name="{{ $line['item_name'] ?? '' }}"
                                 data-wip-unit-cost="{{ (float) ($wipUnitCostByItemId[$itemId] ?? 0) }}"
                                 data-supply-unit-cost="{{ (float) ($finishingSupplyUnitCostByItemId[$itemId] ?? 0) }}">
                                <div class="cardx-h">
                                    <div class="cardx-left">
                                        <input type="checkbox" class="chk row-check">
                                        <div>
                                            <div class="code">{{ $code }}</div>
                                            @if($opId > 0)
                                                <div class="op-chip">OP: {{ $opLabel }}</div>
                                            @endif
                                            @if(!empty($line['setor_date']))
                                                <div class="op-chip">Setor: {{ \Illuminate\Support\Carbon::parse($line['setor_date'])->locale('id')->translatedFormat('d M Y') }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="wip">SISA {{ number_format($wip,0,',','.') }}</div>
                                </div>

                                <div class="cardx-b">
                                    <div class="grid2">
                                        <div class="field">
                                            <label>Setor</label>
                                            <input type="text" inputmode="numeric"
                                                class="form-control form-control-sm qty ok integer-input select-all-on-focus"
                                                name="lines_byop[{{ $idx }}][qty_in]" value="" placeholder="0">
                                        </div>
                                        <div class="field">
                                            <label>Reject</label>
                                            <input type="text" inputmode="numeric"
                                                class="form-control form-control-sm qty rj integer-input select-all-on-focus"
                                                name="lines_byop[{{ $idx }}][qty_reject]" value="" placeholder="0">
                                        </div>
                                    </div>

                                    <div class="notes">
                                        <select class="form-select form-select-sm mb-2"
                                            name="lines_byop[{{ $idx }}][reject_cause]">
                                            <option value="finishing">Reject Finishing</option>
                                            <option value="sewing">Reject Jahit</option>
                                        </select>
                                        <input type="text" class="form-control form-control-sm"
                                            name="lines_byop[{{ $idx }}][reject_notes]" placeholder="Catatan reject (opsional)">
                                    </div>

                                    <input type="hidden" name="lines_byop[{{ $idx }}][item_id]" value="{{ $itemId }}">
                                    <input type="hidden" name="lines_byop[{{ $idx }}][setor_date]" value="{{ $line['setor_date'] ?? '' }}">
                                    <input type="hidden" name="lines_byop[{{ $idx }}][total_wip]" value="{{ $wip }}">
                                    <input type="hidden" name="lines_byop[{{ $idx }}][operator_id]" value="{{ $opId }}">
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="fab-wrap">
                    <a href="{{ route('production.finishing_jobs.index') }}" class="btn btn-sm btn-outline-secondary fab-back">←</a>
                    <button type="button" class="btn btn-sm btn-primary fab-save" id="btn-open-modal" disabled>Simpan & Post</button>
                </div>

                {{-- MODAL --}}
                <div class="modal fade" id="finishingModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Konfirmasi Finishing</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="sum-box">
                                    <div class="sum-top">
                                        <div>
                                            <div class="ttl">Ringkasan Posting</div>
                                            <div class="sub">Mode: <span class="mono" id="m-mode">ALL</span></div>
                                        </div>
                                        <div class="text-end">
                                            <div class="sub">Baris: <span class="mono" id="m-rows">0</span></div>
                                        </div>
                                    </div>
                                    <div class="sum-pillrow">
                                        <div class="sum-pill"><span class="lbl">Total Setor</span><span class="val mono" id="m-ok">0</span></div>
                                        <div class="sum-pill"><span class="lbl">Total Reject</span><span class="val mono" id="m-rj">0</span></div>
                                        <div class="sum-pill"><span class="lbl">Total Qty</span><span class="val mono" id="m-total">0</span></div>
                                    </div>
                                </div>

                                {{-- ALL: accordion preview --}}
                                <div class="mt-3" id="wrap-all-summary">
                                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                        <div style="font-weight:900;">Summary per Operator</div>
                                        <div class="text-muted" style="font-size:.78rem;font-weight:800;">Operator → Item & WIP</div>
                                    </div>
                                    <div class="accordion mt-2" id="all-ops-accordion">
                                        <div class="text-muted text-center py-3">—</div>
                                    </div>
                                </div>

                                {{-- BYOP: detail checked --}}
                                <div class="mt-3" id="wrap-byop-detail" style="display:none;">
                                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                        <div style="font-weight:900;">Detail item dipilih</div>
                                        <div class="text-muted" style="font-size:.78rem;font-weight:800;">No • Kode Item • Setor • Reject</div>
                                    </div>

                                    <div class="table-responsive mt-2">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width:56px;">No</th>
                                                    <th>Kode Item</th>
                                                    <th class="text-end" style="width:140px;">Setor</th>
                                                    <th class="text-end" style="width:140px;">Reject</th>
                                                </tr>
                                            </thead>
                                            <tbody id="byop-detail-body">
                                                <tr><td colspan="4" class="text-muted text-center py-3">Belum ada item dipilih.</td></tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-3">
                                        <label class="form-label form-label-sm">Operator</label>
                                        <input type="text" class="form-control form-control-sm" id="m-operator" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                                <button type="button" class="btn btn-sm btn-primary" id="modal-submit" style="display:none;">Simpan & Post</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- panel-b --}}
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('finishing-form');
    const listAll = document.getElementById('list-all');
    const listByOp = document.getElementById('list-byop');

    const q = document.getElementById('q');
    const itemFilter = document.getElementById('item-filter');
    const opFilter = document.getElementById('op-filter');

    const operatorModeHidden = document.getElementById('operator_mode');
    const operatorHidden = document.getElementById('operator_global_id_hidden');

    const btnOpenModal = document.getElementById('btn-open-modal');

    const modalEl = document.getElementById('finishingModal');
    const modalSubmit = document.getElementById('modal-submit');

    // modal ui
    const mMode = document.getElementById('m-mode');
    const mRows = document.getElementById('m-rows');
    const mOk = document.getElementById('m-ok');
    const mRj = document.getElementById('m-rj');
    const mTotal = document.getElementById('m-total');

    const wrapAllSummary = document.getElementById('wrap-all-summary');
    const wrapByopDetail = document.getElementById('wrap-byop-detail');

    const byopDetailBody = document.getElementById('byop-detail-body');
    const mOperator = document.getElementById('m-operator');
    const acc = document.getElementById('all-ops-accordion');

    const opRowCountEl = document.getElementById('op-row-count');

    const finishingHppBadge = document.getElementById('finishing-hpp-badge');
    const finishingHppWipTotal = document.getElementById('finishing-hpp-wip-total');
    const finishingHppSupplyTotal = document.getElementById('finishing-hpp-supply-total');
    const finishingHppTotal = document.getElementById('finishing-hpp-total');
    const finishingHppNote = document.getElementById('finishing-hpp-note');
    const finishingHppBody = document.getElementById('finishing-hpp-body');
    const finishingAccountingBadge = document.getElementById('finishing-accounting-badge');
    const finishingAccountingDebit = document.getElementById('finishing-accounting-debit');
    const finishingAccountingCredit = document.getElementById('finishing-accounting-credit');
    const finishingAccountingStatus = document.getElementById('finishing-accounting-status');
    const finishingAccountingNote = document.getElementById('finishing-accounting-note');
    const finishingAccountingBody = document.getElementById('finishing-accounting-body');

    const body = document.body;
    const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));

    const itemFilterAllHTML = itemFilter ? itemFilter.innerHTML : '';

    const nf = (n) => (Math.max(0, parseInt(n || 0, 10) || 0)).toLocaleString('id-ID');

    const esc = (s) => String(s ?? '')
        .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
        .replaceAll('"','&quot;').replaceAll("'","&#039;");

    const getMode = () => (operatorModeHidden?.value || 'all').toString(); // all/byop

    function setInputsEnabled(root, enabled){
        if(!root) return;
        root.querySelectorAll('input,select,textarea').forEach(el => { el.disabled = !enabled; });
    }

    const finishingMoneyNf = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
    const finishingQtyNf = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
    const finishingMoney = (value) => 'Rp ' + finishingMoneyNf.format(Number(value || 0));

    function renderFinishingFinancialPreview(){
        if(!finishingHppBody && !finishingAccountingBody) return;

        const entries = [];
        if(getMode() === 'byop'){
            $$('.fin-item', listByOp).forEach(card => {
                const { qtyIn, qtyRj } = getEls(card);
                const qtyInValue = Math.max(0, parseInt(qtyIn?.value || 0, 10) || 0);
                const qtyReject = Math.max(0, parseInt(qtyRj?.value || 0, 10) || 0);
                const qtyOk = qtyInValue;
                const qtyUsed = qtyOk + qtyReject;
                if(qtyUsed <= 0) return;

                const wipUnitCost = Math.max(0, parseFloat(card.dataset.wipUnitCost || '0') || 0);
                const supplyUnitCost = Math.max(0, parseFloat(card.dataset.supplyUnitCost || '0') || 0);
                entries.push({
                    code: card.dataset.code || '-',
                    itemName: card.dataset.itemName || '',
                    operatorLabel: card.dataset.operatorLabel || '',
                    qtyOk,
                    qtyReject,
                    qtyUsed,
                    wipUnitCost,
                    supplyUnitCost,
                    wipValue: qtyUsed * wipUnitCost,
                    supplyValue: qtyOk * supplyUnitCost,
                });
            });
        }

        const wipTotal = entries.reduce((sum, row) => sum + row.wipValue, 0);
        const supplyTotal = entries.reduce((sum, row) => sum + row.supplyValue, 0);
        const outputTotal = wipTotal + supplyTotal;
        const qtyOkTotal = entries.reduce((sum, row) => sum + row.qtyOk, 0);
        const qtyRejectTotal = entries.reduce((sum, row) => sum + row.qtyReject, 0);
        const missingCost = entries.some(row => row.wipUnitCost <= 0);

        if(finishingHppBadge) finishingHppBadge.textContent = entries.length ? entries.length + ' baris' : 'Belum pilih';
        if(finishingHppWipTotal) finishingHppWipTotal.textContent = finishingMoney(wipTotal);
        if(finishingHppSupplyTotal) finishingHppSupplyTotal.textContent = finishingMoney(supplyTotal);
        if(finishingHppTotal) finishingHppTotal.textContent = finishingMoney(outputTotal);
        if(finishingHppNote){
            finishingHppNote.textContent = !entries.length
                ? 'Isi qty setor atau reject untuk melihat estimasi HPP. Auto BOM Packing hanya dihitung untuk Qty OK.'
                : 'Qty OK ' + finishingQtyNf.format(qtyOkTotal) + ' pcs, reject ' + finishingQtyNf.format(qtyRejectTotal) + ' pcs. Auto BOM Packing hanya dihitung untuk Qty OK.';
        }

        if(finishingHppBody){
            finishingHppBody.innerHTML = entries.length
                ? entries.map(row =>
                    '<tr>' +
                        '<td><div class="fw-semibold">' + esc(row.code) + '</div>' +
                        '<span class="finishing-financial-unit">' + esc(row.operatorLabel || row.itemName || '') + '</span></td>' +
                        '<td class="text-end mono">' + finishingQtyNf.format(row.qtyOk) + '</td>' +
                        '<td class="text-end mono">' + finishingQtyNf.format(row.qtyReject) + '</td>' +
                        '<td class="text-end mono">' + finishingMoney(row.wipUnitCost) + '<span class="finishing-financial-unit">per pcs</span></td>' +
                        '<td class="text-end mono">' + finishingMoney(row.supplyUnitCost) + '<span class="finishing-financial-unit">per pcs</span></td>' +
                        '<td class="text-end mono">' + finishingMoney(row.wipValue + row.supplyValue) + '</td>' +
                    '</tr>'
                ).join('')
                : '<tr><td colspan="6" class="text-muted text-center py-2">Pilih operator lalu isi qty untuk melihat estimasi HPP.</td></tr>';
        }

        const okWipValue = entries.reduce((sum, row) => sum + row.qtyOk * row.wipUnitCost, 0);
        const rejectWipValue = entries.reduce((sum, row) => sum + row.qtyReject * row.wipUnitCost, 0);
        // BOM finishing hanya menambah nilai barang OK. Reject tetap memakai
        // nilai WIP-FIN agar sama dengan mutasi stok dan jurnal backend.
        const okBomAllocation = okWipValue > 0 ? supplyTotal : 0;
        const okOutputValue = okWipValue + okBomAllocation;
        const rejectOutputValue = rejectWipValue;
        const accountingTotal = okOutputValue + rejectOutputValue + supplyTotal;

        if(finishingAccountingBadge) finishingAccountingBadge.textContent = entries.length ? 'Preview' : 'Belum pilih';
        if(finishingAccountingDebit) finishingAccountingDebit.textContent = finishingMoney(accountingTotal);
        if(finishingAccountingCredit) finishingAccountingCredit.textContent = finishingMoney(accountingTotal);
        if(finishingAccountingStatus){
            finishingAccountingStatus.textContent = !entries.length ? 'Belum pilih' : (missingCost ? 'Perlu review' : 'Balance');
            finishingAccountingStatus.classList.toggle('text-danger', missingCost && entries.length > 0);
            finishingAccountingStatus.classList.toggle('text-success', !missingCost && entries.length > 0);
        }
        if(finishingAccountingNote && entries.length){
            finishingAccountingNote.textContent = missingCost
                ? 'Unit cost WIP-FIN belum tersedia untuk sebagian item; preview nominal belum lengkap.'
                : 'Mapping: output memakai 1-152 / 1-153 melawan 1-151-03; Auto BOM Packing memakai Dr 1-151-03 dan Cr 1-150.';
        }
        if(finishingAccountingBody){
            const rows = [];
            if(okOutputValue > 0.000001){
                rows.push(['Finishing OK', '1-152 Barang Jadi', '1-151-03 WIP Finishing', okOutputValue]);
            }
            if(rejectOutputValue > 0.000001){
                rows.push(['Reject Finishing/Jahit', '1-153 Persediaan Reject', '1-151-03 WIP Finishing', rejectOutputValue]);
            }
            if(supplyTotal > 0.000001){
                rows.push(['Konsumsi packing', '1-151-03 WIP Finishing', '1-150 Persediaan Bahan', supplyTotal]);
            }
            finishingAccountingBody.innerHTML = rows.length
                ? rows.map(row =>
                    '<tr><td>' + esc(row[0]) + '</td>' +
                    '<td><span class="finishing-financial-unit">' + esc(row[1]) + '</span></td>' +
                    '<td><span class="finishing-financial-unit">' + esc(row[2]) + '</span></td>' +
                    '<td class="text-end mono">' + finishingMoney(row[3]) + '</td></tr>'
                ).join('')
                : '<tr><td colspan="4" class="text-muted text-center py-2">Pilih operator lalu isi qty untuk melihat mapping jurnal.</td></tr>';
        }
    }

    // ===== FILTER OPTIONS =====
    function buildItemOptionsForOperator(opId){
        if(!itemFilter) return;
        if(!opId){
            itemFilter.innerHTML = itemFilterAllHTML;
            return;
        }

        const map = new Map();
        $$('.fin-item', listByOp).forEach(card => {
            const wip = parseInt(card.dataset.wip || '0', 10) || 0;
            if(wip <= 0) return;
            if((card.dataset.operatorId || '') !== opId) return;

            const itemId = card.dataset.itemId || '';
            const code = card.dataset.code || '';
            if(itemId) map.set(itemId, code);
        });

        const arr = Array.from(map.entries()).map(([id, code]) => ({ id, code }))
            .sort((a,b) => (a.code||'').localeCompare(b.code||''));

        itemFilter.innerHTML = ['<option value="">Semua</option>']
            .concat(arr.map(x => `<option value="${x.id}">${esc(x.code)}</option>`))
            .join('');
    }

    function ensureSelectedItemValid(){
        if(!itemFilter) return;
        const cur = (itemFilter.value || '').toString();
        if(!cur) return;
        const exists = Array.from(itemFilter.options).some(o => o.value === cur);
        if(!exists) itemFilter.value = '';
    }

    // ===== INPUT HELPERS =====
    function sanitizeInt(v, allowEmpty){
        v = (v ?? '').toString().trim();
        if(v === '') return allowEmpty ? '' : '0';
        const digits = v.replace(/[^0-9]/g,'');
        if(digits === '') return allowEmpty ? '' : '0';
        return String(Math.max(0, parseInt(digits,10)));
    }

    function getEls(card){
        return {
            qtyIn: card.querySelector('input.integer-input[name*="[qty_in]"]'),
            qtyRj: card.querySelector('input.integer-input[name*="[qty_reject]"]'),
            wipHidden: card.querySelector('input[type="hidden"][name*="[total_wip]"]'),
            notesWrap: card.querySelector('.notes'),
            cb: card.querySelector('.row-check'),
        };
    }

    function getWip(card){
        const { wipHidden } = getEls(card);
        if(wipHidden) return Math.max(0, parseInt(wipHidden.value || 0,10) || 0);
        return Math.max(0, parseInt(card.dataset.wip || 0,10) || 0);
    }

    function syncNotes(card){
        const { qtyRj, notesWrap } = getEls(card);
        if(!notesWrap) return;
        const rj = Math.max(0, parseInt(qtyRj?.value || 0,10) || 0);
        notesWrap.classList.toggle('is-show', rj > 0);
    }

    function syncCheckFromInputs(card){
        const { qtyIn, qtyRj, cb } = getEls(card);
        if(!cb) return;
        const masuk = Math.max(0, parseInt(qtyIn?.value || 0,10) || 0);
        const rj = Math.max(0, parseInt(qtyRj?.value || 0,10) || 0);
        cb.checked = ((masuk + rj) > 0);
    }

    function clampCard(card, changed){
        const { qtyIn, qtyRj } = getEls(card);
        const W = getWip(card);

        let setor = Math.max(0, parseInt(qtyIn?.value || 0,10) || 0);
        let rj = Math.max(0, parseInt(qtyRj?.value || 0,10) || 0);

        if(setor > W) setor = W;

        const maxReject = Math.max(0, W - setor);
        if(rj > maxReject) rj = maxReject;

        if(qtyIn) qtyIn.value = setor === 0 ? '' : String(setor);
        if(qtyRj) qtyRj.value = rj === 0 ? '' : String(rj);
    }

    function fillBySisa(card){
        const { qtyIn, qtyRj } = getEls(card);
        const W = getWip(card);

        let rj = Math.max(0, parseInt(qtyRj?.value || 0,10) || 0);
        if(rj > W) rj = W;

        if(qtyIn) qtyIn.value = W === 0 ? '' : String(W);
        if(qtyRj) qtyRj.value = rj === 0 ? '' : String(rj);

        clampCard(card, 'qty_in');
        syncNotes(card);
    }

    // ===== BUTTON ENABLE =====
    function computeSubmitEnabled(){
        if(getMode() !== 'byop'){
            btnOpenModal.disabled = false;
            btnOpenModal.textContent = 'Summary';
            return;
        }

        let anyChecked = false;
        let total = 0;

        $$('.fin-item', listByOp).forEach(card => {
            const wip = parseInt(card.dataset.wip || '0',10) || 0;
            if(wip <= 0) return;

            const { qtyIn, qtyRj, cb } = getEls(card);
            if(cb?.checked) anyChecked = true;

            const ok = Math.max(0, parseInt(qtyIn?.value || 0,10) || 0);
            const rj = Math.max(0, parseInt(qtyRj?.value || 0,10) || 0);
            total += (ok + rj);
        });

        btnOpenModal.textContent = 'Simpan & Post';
        btnOpenModal.disabled = !(anyChecked || total > 0);
    }

    // ===== OP ROW COUNT (visible rows after filter) =====
    function updateOperatorRowCount(){
        if(!opRowCountEl) return;

        if(getMode() !== 'byop'){
            opRowCountEl.textContent = '-';
            return;
        }

        const selOpId = (opFilter?.value || '').toString();
        if(!selOpId){
            opRowCountEl.textContent = '-';
            return;
        }

        let visibleRows = 0;
        $$('.fin-item', listByOp).forEach(card => {
            if(card.style.display === 'none') return;
            const wip = parseInt(card.dataset.wip || '0',10) || 0;
            if(wip <= 0) return;
            visibleRows++;
        });

        opRowCountEl.textContent = String(visibleRows);
    }

    // ===== FILTER APPLY (fix: ALL accordion + BYOP cards) =====
    function applyFilter(){
        const mode = getMode();
        const term = (q?.value || '').toString().trim().toUpperCase();
        const selItemId = (itemFilter?.value || '').toString();
        const selOpId = (opFilter?.value || '').toString();

        if(mode === 'byop'){
            $$('.fin-item', listByOp).forEach(card => {
                const code = (card.dataset.code || '').toString().toUpperCase();
                const itemId = (card.dataset.itemId || '').toString();
                const opId = (card.dataset.operatorId || '').toString();
                const wip = parseInt(card.dataset.wip || '0',10) || 0;

                const stillHasWip = wip > 0;
                const matchSearch = !term || code.includes(term);
                const matchItem = !selItemId || itemId === selItemId;
                const matchOp = !selOpId || opId === selOpId;

                card.style.display = (stillHasWip && matchSearch && matchItem && matchOp) ? '' : 'none';
            });
        } else {
            // ALL accordion items
            $$('.all-item', listAll).forEach(el => {
                const code = (el.dataset.code || '').toString().toUpperCase();
                const itemId = (el.dataset.itemId || '').toString();

                const matchSearch = !term || code.includes(term);
                const matchItem = !selItemId || itemId === selItemId;

                el.style.display = (matchSearch && matchItem) ? '' : 'none';
            });
        }

        updateOperatorRowCount();
        computeSubmitEnabled();
    }

    // ===== MODE HINT =====
    const opModeHint = document.getElementById('op-mode-hint');
    function updateModeHint(mode){
        if(!opModeHint) return;
        if(mode === 'byop'){
            opModeHint.innerHTML = '<span style="color:#16a34a;font-weight:700;">✏ Mode input aktif</span> — isi qty setor di bawah';
        } else {
            opModeHint.innerHTML = '<span style="color:#94a3b8;">👁 Ringkasan saja</span> — pilih operator untuk input setor';
        }
    }

    // ===== MODE SWITCH =====
    function setMode(mode){
        mode = (mode === 'byop') ? 'byop' : 'all';
        operatorModeHidden.value = mode;
        updateModeHint(mode);

        const fab = document.querySelector('.fab-wrap');

        if(mode === 'all'){
            listAll.style.display = '';
            listByOp.style.display = 'none';

            if(fab) fab.style.display = 'none';

            setInputsEnabled(listAll, false);
            setInputsEnabled(listByOp, false);
            listAll?.querySelectorAll?.('.row-check')?.forEach(cb => cb.disabled = true);
            listByOp?.querySelectorAll?.('.row-check')?.forEach(cb => cb.disabled = true);

            btnOpenModal.disabled = true;
        } else {
            listAll.style.display = 'none';
            listByOp.style.display = '';

            if(fab) fab.style.display = '';

            setInputsEnabled(listAll, false);
            setInputsEnabled(listByOp, true);
            listAll?.querySelectorAll?.('.row-check')?.forEach(cb => cb.disabled = true);
            listByOp?.querySelectorAll?.('.row-check')?.forEach(cb => cb.disabled = false);

            computeSubmitEnabled();
        }

        applyFilter();
        renderFinishingFinancialPreview();
    }

    // ===== MODAL BUILDERS =====
    function buildModalAllAccordion(){
        const mapItem = new Map();

        $$('.fin-item', listByOp).forEach(card => {
            const wip = parseInt(card.dataset.wip || '0',10) || 0;
            if(wip <= 0) return;

            const code = (card.dataset.code || '').toString() || 'ITEM';
            const itemId = (card.dataset.itemId || '').toString() || '';
            const opId = (card.dataset.operatorId || '').toString() || '0';
            const opLabelRaw = (card.dataset.operatorLabel || '').toString();
            const opLabel = opLabelRaw || (opId ? ('OP-' + opId) : 'OP');

            if(!mapItem.has(code)){
                mapItem.set(code, { itemId, code, ops:new Map() });
            }
            const bucket = mapItem.get(code);
            bucket.ops.set(opLabel, (bucket.ops.get(opLabel) || 0) + wip);
        });

        const items = Array.from(mapItem.values())
            .sort((a,b) => (a.code||'').localeCompare(b.code||''));

        // summary numbers
        let rowsCount = 0;
        let totalWip = 0;
        items.forEach(it => {
            it.ops.forEach(v => {
                rowsCount++;
                totalWip += (v || 0);
            });
        });

        mRows.textContent = String(rowsCount);
        mOk.textContent = '0';
        mRj.textContent = '0';
        mTotal.textContent = nf(totalWip);

        if(!acc) return;
        if(items.length === 0){
            acc.innerHTML = `<div class="text-muted text-center py-3">Tidak ada data.</div>`;
            return;
        }

        acc.innerHTML = items.map((it, idx) => {
            const opsArr = Array.from(it.ops.entries())
                .map(([label,wip]) => ({ label, wip }))
                .sort((a,b) => (a.label||'').localeCompare(b.label||''));

            const itemTotal = opsArr.reduce((s,r)=>s+(r.wip||0),0);
            const opCount = opsArr.length;

            const safeKey = (it.itemId || it.code).replace(/[^a-zA-Z0-9_-]/g,'');
            const collapseId = `item-collapse-${safeKey}-${idx}`;
            const headingId  = `item-heading-${safeKey}-${idx}`;
            const expanded = (idx===0) ? 'true' : 'false';
            const show = (idx===0) ? 'show' : '';

            return `
                <div class="accordion-item" style="border-radius:14px; overflow:hidden; border:1px solid rgba(148,163,184,.18); margin-bottom:.55rem;">
                    <h2 class="accordion-header" id="${headingId}">
                        <button class="accordion-button ${idx===0?'':'collapsed'} acc-op-btn" type="button"
                                data-bs-toggle="collapse" data-bs-target="#${collapseId}"
                                aria-expanded="${expanded}" aria-controls="${collapseId}">
                            <div class="d-flex w-100 justify-content-between align-items-center gap-2 flex-wrap">
                                <div class="mono" style="font-weight:900;">${esc(it.code)}</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="acc-pill">OP <span class="mono">${nf(opCount)}</span></span>
                                    <span class="acc-pill">WIP <span class="mono">${nf(itemTotal)}</span></span>
                                </div>
                            </div>
                        </button>
                    </h2>

                    <div id="${collapseId}" class="accordion-collapse collapse ${show}" aria-labelledby="${headingId}" data-bs-parent="#all-ops-accordion">
                        <div class="accordion-body" style="padding:.7rem .85rem;">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:56px;">No</th>
                                            <th>Operator</th>
                                            <th class="text-end" style="width:140px;">WIP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${opsArr.map((op,i)=>`
                                            <tr>
                                                <td class="mono">${i+1}</td>
                                                <td class="mono" style="font-weight:900;">${esc(op.label)}</td>
                                                <td class="text-end mono" style="font-weight:900;">${nf(op.wip)}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-2 acc-op-sub">Total item: <span class="mono">${nf(itemTotal)}</span></div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function buildModalByopDetail(){
        const rows = [];
        let totalMasuk = 0, totalOk = 0, totalRj = 0;

        $$('.fin-item', listByOp).forEach(card => {
            const wip = parseInt(card.dataset.wip || '0',10) || 0;
            if(wip <= 0) return;

            const { qtyIn, qtyRj, cb } = getEls(card);
            if(!cb?.checked) return;

            const code = (card.dataset.code || '').toString();
            const masuk = Math.max(0, parseInt(qtyIn?.value || 0,10) || 0);
            const rj = Math.max(0, parseInt(qtyRj?.value || 0,10) || 0);
            const ok = masuk;

            totalMasuk += ok + rj;
            totalOk += ok;
            totalRj += rj;
            rows.push({ code, masuk, ok, rj });
        });

        mRows.textContent = String(rows.length);
        mOk.textContent = nf(totalOk);
        mRj.textContent = nf(totalRj);
        mTotal.textContent = nf(totalMasuk);

        const sel = opFilter?.options?.[opFilter.selectedIndex];
        if(mOperator) mOperator.value = sel ? sel.textContent.trim() : '';

        if(!byopDetailBody) return;
        if(rows.length === 0){
            byopDetailBody.innerHTML = `<tr><td colspan="4" class="text-muted text-center py-3">Belum ada item dipilih.</td></tr>`;
            return;
        }

        byopDetailBody.innerHTML = rows.map((r,i)=>`
            <tr>
                <td class="mono">${i+1}</td>
                <td class="mono" style="font-weight:900;">${esc(r.code)}</td>
                <td class="text-end mono" style="font-weight:900;">${nf(r.ok)}</td>
                <td class="text-end mono" style="font-weight:900;">${nf(r.rj)}</td>
            </tr>
        `).join('');
    }

    // ===== EVENTS =====
    q?.addEventListener('input', () => {
        const up = (q.value || '').toString().toUpperCase();
        if(q.value !== up) q.value = up;
        applyFilter();
    });

    itemFilter?.addEventListener('change', () => {
        applyFilter();
        const mode = getMode();
        const root = (mode === 'byop') ? listByOp : listAll;
        const first = $$('.fin-item, .all-item', root).find(c => c.style.display !== 'none');
        if(first) first.scrollIntoView({ behavior:'smooth', block:'start' });
    });

    opFilter?.addEventListener('change', () => {
        const selOpId = (opFilter.value || '').toString();

        if(!selOpId){
            operatorHidden.value = '';
            if(itemFilter) itemFilter.innerHTML = itemFilterAllHTML;
            setMode('all');
            return;
        }

        operatorHidden.value = selOpId;
        setMode('byop');
        buildItemOptionsForOperator(selOpId);
        ensureSelectedItemValid();
        applyFilter();
    });

    // input handler (BYOP)
    form.addEventListener('input', (e) => {
        if(getMode() !== 'byop') return;

        const t = e.target;
        if(!t.classList?.contains('integer-input')) return;

        t.value = sanitizeInt(t.value, true);

        const card = t.closest('.fin-item');
        if(!card) return;

        const isReject = (t.name || '').includes('[qty_reject]');
        const changed = isReject ? 'qty_reject' : 'qty_in';

        clampCard(card, changed);

        syncCheckFromInputs(card);
        syncNotes(card);
        computeSubmitEnabled();
        updateOperatorRowCount();
        renderFinishingFinancialPreview();
    });

    // checkbox handler (BYOP)
    form.addEventListener('change', (e) => {
        if(getMode() !== 'byop') return;

        const t = e.target;
        if(!t.classList?.contains('row-check')) return;

        const card = t.closest('.fin-item');
        if(!card) return;

        const { qtyIn, qtyRj } = getEls(card);

        if(t.checked){
            fillBySisa(card);
            t.checked = true;
        }else{
            if(qtyIn) qtyIn.value = '';
            if(qtyRj) qtyRj.value = '';
            syncNotes(card);
        }

        computeSubmitEnabled();
        updateOperatorRowCount();
        renderFinishingFinancialPreview();
    });

    // select all on focus + keyboard open class
    form.addEventListener('focusin', (e) => {
        const t = e.target;
        if(t?.classList?.contains('select-all-on-focus')){
            setTimeout(() => { try{ t.select(); }catch(_){} }, 0);
        }
        if(window.innerWidth < 768) body.classList.add('keyboard-open');
    });
    form.addEventListener('focusout', () => body.classList.remove('keyboard-open'));

    // ===== MODAL =====
    if(modalEl && typeof bootstrap !== 'undefined'){
        const bsModal = new bootstrap.Modal(modalEl);

        btnOpenModal?.addEventListener('click', (e) => {
            e.preventDefault();

            const mode = getMode();
            mMode.textContent = (mode === 'byop') ? 'BYOP' : 'ALL';

            if(mode === 'all'){
                wrapAllSummary.style.display = '';
                wrapByopDetail.style.display = 'none';
                modalSubmit.style.display = 'none';
                buildModalAllAccordion();
            }else{
                wrapAllSummary.style.display = 'none';
                wrapByopDetail.style.display = '';
                modalSubmit.style.display = '';
                buildModalByopDetail();
            }

            bsModal.show();
        });

        modalSubmit?.addEventListener('click', () => {
            if(getMode() !== 'byop') return;

            // normalize blank -> 0 for checked rows
            $$('.fin-item', listByOp).forEach(card => {
                const { qtyIn, qtyRj, cb } = getEls(card);
                if(!cb?.checked) return;
                if(qtyIn && (qtyIn.value || '').trim() === '') qtyIn.value = '0';
                if(qtyRj && (qtyRj.value || '').trim() === '') qtyRj.value = '0';
            });

            bsModal.hide();
            form.submit();
        });
    }

    // ===== INIT MODE =====x
    const initOp = (opFilter?.value || '').toString();
    if(!initOp){
        setMode('all');
    }else{
        setMode('byop');
        operatorHidden.value = initOp;
        buildItemOptionsForOperator(initOp);
        ensureSelectedItemValid();
    }

    applyFilter();
    computeSubmitEnabled();
    updateOperatorRowCount();
    renderFinishingFinancialPreview();

    // Material correction modal: the row itself supplies the related FG,
    // material, and shortage qty so the correction cannot drift to another SKU.
    document.querySelectorAll('.material-correction-trigger').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            document.getElementById('correction-finishing-item-id').value = trigger.dataset.finishingItemId || '';
            document.getElementById('correction-material-item-id').value = trigger.dataset.materialItemId || '';
            document.getElementById('correction-finishing-label').textContent = trigger.dataset.finishingItemLabel || '-';
            document.getElementById('correction-material-label').textContent = trigger.dataset.materialLabel || '-';
            document.getElementById('correction-qty').value = trigger.dataset.qty || '';
            document.getElementById('correction-unit').textContent = trigger.dataset.unit ? `(${trigger.dataset.unit})` : '';
        });
    });
});
</script>
@endpush
