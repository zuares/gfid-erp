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
    .bom-cost-section-heading { display:flex; align-items:center; gap:.7rem; }
    .bom-cost-section-heading h3 { margin:0; color:var(--gf-color-content); font-size:.9rem; font-weight:850; letter-spacing:-.025em; }
    .bom-cost-section-heading p { margin:.18rem 0 0; color:var(--gf-color-muted); font-size:.69rem; font-weight:650; }
    .bom-cost-section-number { display:inline-grid; flex:0 0 28px; place-items:center; width:28px; height:28px; border-radius:9px; color:#fff; background:var(--gf-color-brand); font-size:.72rem; font-weight:900; }
    .bom-cost-section-number.is-wage { background:var(--gf-color-accent); }
    .bom-cost-groups { display:grid; gap:.75rem; }
    .bom-cost-group-card { overflow:hidden; border:1px solid var(--gf-color-line); border-radius:var(--gf-radius-control,10px); background:var(--gf-color-surface); }
    .bom-cost-group-head { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.85rem .95rem; border-bottom:1px solid var(--gf-color-line); background:var(--gf-color-surface-muted); }
    .bom-cost-group-title-wrap { display:flex; align-items:center; min-width:0; gap:.65rem; }
    .bom-cost-group-icon { display:inline-grid; flex:0 0 32px; place-items:center; width:32px; height:32px; border-radius:10px; font-size:.9rem; }
    .bom-cost-group-card-material .bom-cost-group-icon { color:var(--gf-color-brand-strong); background:color-mix(in srgb,var(--gf-color-brand) 12%,var(--gf-color-surface)); }
    .bom-cost-group-card-sewing .bom-cost-group-icon { color:var(--gf-color-accent); background:color-mix(in srgb,var(--gf-color-accent) 12%,var(--gf-color-surface)); }
    .bom-cost-group-card-packing .bom-cost-group-icon { color:var(--gf-color-success); background:color-mix(in srgb,var(--gf-color-success) 12%,var(--gf-color-surface)); }
    .bom-cost-group-title-wrap h3 { margin:0; color:var(--gf-color-content); font-size:.8rem; font-weight:850; }
    .bom-cost-group-title-wrap p { margin:.14rem 0 0; color:var(--gf-color-muted); font-size:.66rem; line-height:1.35; }
    .bom-cost-group-total { flex:0 0 auto; text-align:right; }
    .bom-cost-group-total span { display:block; color:var(--gf-color-muted); font-size:.62rem; font-weight:750; }
    .bom-cost-group-total strong { display:block; margin-top:.1rem; color:var(--gf-color-content); font-size:.83rem; font-weight:900; font-variant-numeric:tabular-nums; }
    .bom-cost-component-list { display:grid; }
    .bom-cost-component-row { display:grid; grid-template-columns:minmax(190px,1.7fr) minmax(95px,.65fr) minmax(145px,1fr) minmax(120px,.8fr); align-items:center; gap:1rem; padding:.8rem .95rem; border-top:1px solid color-mix(in srgb,var(--gf-color-line) 76%,transparent); }
    .bom-cost-component-row:first-child { border-top:0; }
    .bom-cost-component-row:hover { background:color-mix(in srgb,var(--gf-color-brand) 3%,var(--gf-color-surface)); }
    .bom-cost-component-identity { min-width:0; }
    .bom-cost-component-identity strong { display:block; overflow:hidden; color:var(--gf-color-content); font-size:.77rem; font-weight:850; text-overflow:ellipsis; white-space:nowrap; }
    .bom-cost-component-identity span { display:block; overflow:hidden; margin-top:.18rem; color:var(--gf-color-muted); font-size:.68rem; font-weight:650; text-overflow:ellipsis; white-space:nowrap; }
    .bom-cost-component-identity em { display:inline-flex; margin-top:.28rem; padding:.14rem .38rem; border-radius:999px; color:var(--gf-color-brand-strong); background:color-mix(in srgb,var(--gf-color-brand) 10%,var(--gf-color-surface)); font-size:.58rem; font-style:normal; font-weight:800; }
    .bom-cost-metric { min-width:0; }
    .bom-cost-metric > span { display:block; color:var(--gf-color-muted); font-size:.61rem; font-weight:750; }
    .bom-cost-metric > strong { display:inline-block; margin-top:.18rem; color:var(--gf-color-content); font-size:.76rem; font-weight:850; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .bom-cost-metric > strong small { color:var(--gf-color-muted); font-size:.63rem; font-weight:700; }
    .bom-cost-metric > small { display:block; margin-top:.12rem; color:var(--gf-color-muted); font-size:.62rem; font-weight:650; }
    .bom-cost-metric .is-empty { color:var(--gf-color-muted); font-size:.7rem; font-weight:700; }
    .bom-cost-line-total { text-align:right; }
    .bom-cost-line-total > strong { color:var(--gf-color-brand-strong); font-size:.82rem; }
    .bom-cost-empty { display:flex; align-items:center; gap:.5rem; padding:.85rem .95rem; color:var(--gf-color-muted); font-size:.7rem; font-style:italic; }
    .bom-cost-empty i { color:var(--gf-color-brand); font-size:.85rem; }
    .bom-cost-summary-row { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-top:.8rem; padding:.75rem .95rem; border:1px solid var(--gf-color-line); border-radius:var(--gf-radius-control,10px); color:var(--gf-color-content); background:var(--gf-color-surface-muted); font-size:.76rem; font-weight:850; }
    .bom-cost-summary-row strong { color:var(--gf-color-content); font-size:.86rem; font-variant-numeric:tabular-nums; }
    .bom-cost-wage-card { margin-top:1.2rem; overflow:hidden; border:1px solid var(--gf-color-line); border-radius:var(--gf-radius-control,10px); background:var(--gf-color-surface); }
    .bom-cost-wage-card > .bom-cost-section-heading { justify-content:flex-start; padding:.85rem .95rem; border-bottom:1px solid var(--gf-color-line); background:var(--gf-color-surface-muted); }
    .bom-cost-wage-total { margin-left:auto; color:var(--gf-color-content); font-size:.86rem; font-variant-numeric:tabular-nums; }
    .bom-cost-wage-total span { display:block; margin-bottom:.12rem; color:var(--gf-color-muted); font-size:.6rem; font-weight:750; letter-spacing:.02em; text-transform:uppercase; }
    .bom-cost-wage-list { display:grid; }
    .bom-cost-wage-row { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.7rem .95rem; border-top:1px solid color-mix(in srgb,var(--gf-color-line) 76%,transparent); }
    .bom-cost-wage-row:first-child { border-top:0; }
    .bom-cost-wage-row > div { display:grid; gap:.14rem; }
    .bom-cost-wage-row strong { color:var(--gf-color-content); font-size:.74rem; font-weight:800; }
    .bom-cost-wage-row small { color:var(--gf-color-muted); font-size:.64rem; font-weight:650; }
    .bom-cost-wage-row > span { color:var(--gf-color-content); font-size:.76rem; font-weight:850; font-variant-numeric:tabular-nums; }
    .bom-cost-grand-total { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-top:1.2rem; padding:1rem .95rem; border-top:2px solid var(--gf-color-brand); color:var(--gf-color-brand-strong); font-size:.9rem; font-weight:900; }
    .bom-cost-grand-total strong { font-size:1.05rem; font-variant-numeric:tabular-nums; }
    @media (prefers-reduced-motion: reduce) { .bom-show-btn { transition:none; } .bom-show-btn:hover { transform:none; } }
    @media (max-width: 760px) {
        .bom-show-head { flex-direction:column; }
        .bom-show-actions { width:100%; justify-content:flex-start; }
        .bom-show-btn { flex:1 1 140px; }
        .bom-show-meta { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .bom-cost-breakdown-head { flex-direction:column; }
        .bom-cost-total-badge { width:100%; text-align:left; }
        .bom-cost-component-row { grid-template-columns:repeat(2,minmax(0,1fr)); gap:.7rem; }
        .bom-cost-component-identity { grid-column:1 / -1; }
        .bom-cost-line-total { text-align:left; }
    }
    @media (max-width: 440px) {
        .bom-show-meta { grid-template-columns:1fr; }
        .bom-show-btn { flex-basis:100%; }
        .bom-cost-group-head { align-items:flex-start; flex-direction:column; }
        .bom-cost-group-total { width:100%; text-align:left; }
        .bom-cost-summary-row,
        .bom-cost-grand-total { align-items:flex-start; flex-direction:column; }
        .bom-cost-wage-total { margin-left:0; }
    }
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
