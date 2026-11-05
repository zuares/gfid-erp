@extends('layouts.app')

@section('title', 'Master • HPP BOM')

@push('head')
<style>
    .bom-show-page { max-width:var(--gf-content-max,1280px); margin:0 auto; padding:clamp(1rem,2vw,1.5rem) var(--gf-content-gutter,1rem) 4rem; color:var(--gf-color-content); font-family:Inter,ui-sans-serif,system-ui,sans-serif; }
    .bom-show-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:var(--gf-space-section,24px); padding:clamp(1rem,2vw,1.4rem); border:1px solid var(--gf-color-line); border-radius:var(--gf-radius-card,16px); background:var(--gf-color-surface); box-shadow:var(--gf-shadow-sm); }
    .bom-show-eyebrow { display:inline-flex; align-items:center; gap:.4rem; color:var(--gf-color-brand-strong); font-size:.67rem; font-weight:850; letter-spacing:.1em; text-transform:uppercase; }
    .bom-show-eyebrow::before { width:7px; height:7px; border-radius:50%; background:var(--gf-color-accent); box-shadow:0 0 0 4px color-mix(in srgb,var(--gf-color-accent) 14%,transparent); content:''; }
    .bom-show-title { margin:.35rem 0 0; color:var(--gf-color-content); font-size:clamp(1.25rem,2.5vw,1.85rem); font-weight:850; letter-spacing:-.055em; line-height:1.08; }
    .bom-show-subtitle { display:flex; flex-wrap:wrap; align-items:center; gap:.45rem; margin-top:.45rem; color:var(--gf-color-muted); font-size:.8rem; font-weight:650; }
    .bom-show-status { display:inline-flex; align-items:center; gap:.3rem; min-height:22px; padding:0 .52rem; border-radius:999px; font-size:.64rem; font-weight:800; }
    .bom-show-status::before { width:6px; height:6px; border-radius:50%; background:currentColor; content:''; }
    .bom-show-status.is-active { color:var(--gf-color-success); background:color-mix(in srgb,var(--gf-color-success) 10%,var(--gf-color-surface)); }
    .bom-show-status.is-inactive { color:var(--gf-color-muted); background:var(--gf-color-surface-muted); }
    .bom-show-actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:.5rem; }
    .bom-show-btn { display:inline-flex; align-items:center; justify-content:center; gap:.42rem; min-height:36px; padding:0 .78rem; border:1px solid var(--gf-color-line); border-radius:var(--gf-radius-control,10px); color:var(--gf-color-muted); background:var(--gf-color-surface); font:inherit; font-size:.74rem; font-weight:750; text-decoration:none; transition:color .18s ease,background .18s ease,border-color .18s ease,transform .18s ease,box-shadow .18s ease; }
    .bom-show-btn:hover { color:var(--gf-color-brand-strong); border-color:color-mix(in srgb,var(--gf-color-brand) 32%,var(--gf-color-line)); background:var(--gf-color-surface); transform:translateY(-1px); }
    .bom-show-btn-primary { color:#fff; border-color:var(--gf-color-brand); background:var(--gf-color-brand); box-shadow:0 7px 14px color-mix(in srgb,var(--gf-color-brand) 20%,transparent); }
    .bom-show-btn-primary:hover { color:#fff; border-color:var(--gf-color-brand-strong); background:var(--gf-color-brand-strong); }
    .bom-show-btn:focus-visible { outline:3px solid color-mix(in srgb,var(--gf-color-brand) 28%,transparent); outline-offset:2px; }
    .bom-show-card { overflow:hidden; border:1px solid var(--gf-color-line); border-radius:var(--gf-radius-card,16px); background:var(--gf-color-surface); box-shadow:var(--gf-shadow-sm); }
    .bom-show-meta { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1px; padding:1px; border-bottom:1px solid var(--gf-color-line); background:var(--gf-color-line); }
    .bom-show-meta > div { min-width:0; padding:1rem; background:var(--gf-color-surface); }
    .bom-show-meta-label { color:var(--gf-color-muted); font-size:.64rem; font-weight:850; letter-spacing:.06em; text-transform:uppercase; }
    .bom-show-meta-value { margin-top:.35rem; color:var(--gf-color-content); font-size:1rem; font-weight:850; font-variant-numeric:tabular-nums; }
    .bom-show-meta-note { margin-top:.18rem; color:var(--gf-color-muted); font-size:.68rem; line-height:1.4; }
    .bom-cost-breakdown { padding:clamp(1rem,2vw,1.4rem); }
    .bom-cost-breakdown-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
    .bom-cost-breakdown-head h2 { margin:0; color:var(--gf-color-content); font-size:1rem; font-weight:800; letter-spacing:-.03em; }
    .bom-cost-breakdown-head p { margin:.3rem 0 0; color:var(--gf-color-muted); font-size:.72rem; font-weight:650; }
    .bom-cost-total-badge { min-width:170px; padding:.65rem .75rem; border:1px solid color-mix(in srgb,var(--gf-color-brand) 20%,var(--gf-color-line)); border-radius:var(--gf-radius-control,10px); background:color-mix(in srgb,var(--gf-color-brand) 7%,var(--gf-color-surface)); text-align:right; }
    .bom-cost-total-badge span { display:block; color:var(--gf-color-muted); font-size:.61rem; font-weight:850; letter-spacing:.04em; text-transform:uppercase; }
    .bom-cost-total-badge strong { display:block; margin-top:.2rem; color:var(--gf-color-brand-strong); font-size:1.08rem; font-weight:900; font-variant-numeric:tabular-nums; }
    .bom-cost-breakdown .table-responsive { overflow-x:auto; border:1px solid var(--gf-color-line); border-radius:var(--gf-radius-control,10px); scrollbar-color:color-mix(in srgb,var(--gf-color-brand) 32%,var(--gf-color-line)) var(--gf-color-surface-muted); }
    .bom-cost-breakdown-table { min-width:720px; margin:0; color:var(--gf-color-content); font-size:.76rem; }
    .bom-cost-breakdown-table th { position:sticky; top:0; z-index:1; padding:.7rem .75rem; color:var(--gf-color-muted); background:var(--gf-color-surface-muted); border:0; box-shadow:0 1px 0 var(--gf-color-line); font-size:.62rem; font-weight:800; letter-spacing:.055em; text-transform:uppercase; white-space:nowrap; }
    .bom-cost-breakdown-table td { padding:.62rem .75rem; border-top:1px solid color-mix(in srgb,var(--gf-color-line) 75%,transparent); }
    .bom-cost-breakdown-table tbody tr:hover td { background:color-mix(in srgb,var(--gf-color-brand) 3%,var(--gf-color-surface)); }
    .bom-cost-group { color:var(--gf-color-muted); font-weight:800; white-space:nowrap; }
    .bom-cost-component { color:var(--gf-color-content); font-weight:700; }
    .bom-cost-source { display:block; margin-top:.15rem; color:var(--gf-color-muted); font-size:.65rem; font-weight:650; }
    .bom-cost-muted { color:var(--gf-color-muted); font-style:italic; }
    .bom-cost-amount { color:var(--gf-color-content); font-weight:850; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .bom-cost-purchase { color:var(--gf-color-muted); font-weight:750; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .bom-cost-purchase small { display:block; margin-top:.12rem; color:var(--gf-color-muted); font-size:.64rem; font-weight:650; }
    .bom-cost-subtotal td { border-top:1px solid var(--gf-color-line); color:var(--gf-color-content); font-weight:850; }
    .bom-cost-grand-total td { border-top:2px solid var(--gf-color-brand); color:var(--gf-color-brand-strong); font-size:.92rem; font-weight:900; }
    @media (prefers-reduced-motion: reduce) { .bom-show-btn { transition:none; } .bom-show-btn:hover { transform:none; } }
    @media (max-width: 760px) {
        .bom-show-head { flex-direction:column; }
        .bom-show-actions { width:100%; justify-content:flex-start; }
        .bom-show-btn { flex:1 1 140px; }
        .bom-show-meta { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .bom-cost-breakdown-head { flex-direction:column; }
        .bom-cost-total-badge { width:100%; text-align:left; }
    }
    @media (max-width: 440px) { .bom-show-meta { grid-template-columns:1fr; } .bom-show-btn { flex-basis:100%; } }
</style>
@endpush

@section('content')
<div class="bom-show-page">
    <header class="bom-show-head">
        <div>
            <div class="bom-show-eyebrow">Detail HPP BOM</div>
            <h1 class="bom-show-title">{{ $bom->item?->code }} — {{ $bom->item?->name }}</h1>
            <div class="bom-show-subtitle">
                <span>{{ $bom->name ?: 'BOM '.$bom->item?->code }}</span>
                <span class="bom-show-status {{ $bom->active ? 'is-active' : 'is-inactive' }}">{{ $bom->active ? 'Aktif' : 'Nonaktif' }}</span>
            </div>
        </div>
        <div class="bom-show-actions">
            <a class="bom-show-btn" href="{{ route('master.item_boms.index') }}"><i class="bi bi-arrow-left"></i> Kembali</a>
            <a class="bom-show-btn bom-show-btn-primary" href="{{ route('master.item_boms.edit', $bom) }}"><i class="bi bi-pencil"></i> Edit BOM</a>
        </div>
    </header>

    <section class="bom-show-card">
        <div class="bom-show-meta">
            <div>
                <div class="bom-show-meta-label">Komponen</div>
                <div class="bom-show-meta-value">{{ $bomEstimate['line_count'] }}</div>
                <div class="bom-show-meta-note">{{ $bomEstimate['required_line_count'] }} wajib · {{ $bomEstimate['optional_line_count'] }} opsional</div>
            </div>
            <div>
                <div class="bom-show-meta-label">Bahan &amp; perlengkapan</div>
                <div class="bom-show-meta-value">Rp {{ number_format((float) $bomEstimate['material_total'], 0, ',', '.') }}</div>
                <div class="bom-show-meta-note">Optional termahal dihitung</div>
            </div>
            <div>
                <div class="bom-show-meta-label">Upah produksi</div>
                <div class="bom-show-meta-value">Rp {{ number_format((float) $bomEstimate['production_wage_total'], 0, ',', '.') }}</div>
                <div class="bom-show-meta-note">Per 1 {{ $bomEstimate['item_unit'] }}</div>
            </div>
            <div>
                <div class="bom-show-meta-label">Bahan utama</div>
                <div class="bom-show-meta-value">{{ $bomEstimate['included_main_material_line_count'] }} dipakai</div>
                <div class="bom-show-meta-note">Yang termahal jika ada alternatif</div>
            </div>
        </div>
        @include('master.item_boms._cost_breakdown', ['bomEstimate' => $bomEstimate])
    </section>
</div>
@endsection
