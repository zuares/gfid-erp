@extends('layouts.app')

@section('title', 'Master • HPP BOM')

@push('head')
<style>
    .bom-show-page { max-width: 1180px; margin: 0 auto; padding: 1rem 1.1rem 4rem; color: #0f172a; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
    .bom-show-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:14px; padding:18px; border:1px solid #e2e8f0; border-radius:18px; background:#fff; box-shadow:0 10px 28px rgba(15,23,42,.05); }
    .bom-show-eyebrow { color:#64748b; font-size:.68rem; font-weight:900; letter-spacing:.06em; text-transform:uppercase; }
    .bom-show-title { margin:.25rem 0 0; color:#0f172a; font-size:1.35rem; font-weight:950; letter-spacing:-.04em; }
    .bom-show-subtitle { margin-top:.35rem; color:#64748b; font-size:.84rem; font-weight:650; }
    .bom-show-actions { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:8px; }
    .bom-show-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; min-height:38px; padding:8px 14px; border:1px solid #cbd5e1; border-radius:999px; color:#475569; background:#fff; font-size:.78rem; font-weight:850; text-decoration:none; }
    .bom-show-btn:hover { color:#0f172a; background:#f8fafc; }
    .bom-show-btn-primary { color:#fff; border-color:#0f172a; background:linear-gradient(135deg,#0f172a,#334155); }
    .bom-show-btn-primary:hover { color:#fff; background:#1e293b; }
    .bom-show-card { overflow:hidden; border:1px solid #e2e8f0; border-radius:18px; background:#fff; box-shadow:0 10px 28px rgba(15,23,42,.05); }
    .bom-show-meta { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; padding:16px; border-bottom:1px solid #eef2f7; background:#f8fafc; }
    .bom-show-meta-label { color:#64748b; font-size:.64rem; font-weight:900; letter-spacing:.04em; text-transform:uppercase; }
    .bom-show-meta-value { margin-top:3px; color:#0f172a; font-size:.86rem; font-weight:850; }
    .bom-show-meta-note { margin-top:2px; color:#64748b; font-size:.7rem; }
    .bom-cost-breakdown { padding:16px; }
    .bom-cost-breakdown-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; margin-bottom:12px; }
    .bom-cost-breakdown-head h2 { margin:0; color:#0f172a; font-size:.98rem; font-weight:950; }
    .bom-cost-breakdown-head p { margin:.3rem 0 0; color:#64748b; font-size:.75rem; font-weight:650; }
    .bom-cost-total-badge { min-width:160px; padding:9px 12px; border:1px solid #bfdbfe; border-radius:12px; background:#eff6ff; text-align:right; }
    .bom-cost-total-badge span { display:block; color:#64748b; font-size:.62rem; font-weight:900; text-transform:uppercase; }
    .bom-cost-total-badge strong { display:block; margin-top:3px; color:#0f172a; font-size:1.05rem; font-weight:950; }
    .bom-cost-breakdown-table { min-width:720px; color:#0f172a; font-size:.78rem; }
    .bom-cost-breakdown-table th { padding:.7rem .75rem; color:#64748b; background:#f8fafc; border:0; font-size:.64rem; font-weight:900; letter-spacing:.04em; text-transform:uppercase; white-space:nowrap; }
    .bom-cost-breakdown-table td { padding:.62rem .75rem; border-top:1px solid #eef2f7; }
    .bom-cost-group { color:#475569; font-weight:850; white-space:nowrap; }
    .bom-cost-component { font-weight:750; }
    .bom-cost-source { display:block; margin-top:2px; color:#64748b; font-size:.68rem; font-weight:650; }
    .bom-cost-muted { color:#94a3b8; font-style:italic; }
    .bom-cost-amount { font-weight:900; white-space:nowrap; }
    .bom-cost-subtotal td { border-top:1px solid #cbd5e1; font-weight:900; }
    .bom-cost-grand-total td { border-top:2px solid #0f172a; color:#0f172a; font-size:.92rem; font-weight:950; }
    @media (max-width: 760px) {
        .bom-show-head { flex-direction:column; }
        .bom-show-actions { justify-content:flex-start; }
        .bom-show-meta { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .bom-cost-breakdown-head { flex-direction:column; }
        .bom-cost-total-badge { width:100%; text-align:left; }
    }
</style>
@endpush

@section('content')
<div class="bom-show-page">
    <header class="bom-show-head">
        <div>
            <div class="bom-show-eyebrow">Detail HPP BOM</div>
            <h1 class="bom-show-title">{{ $bom->item?->code }} — {{ $bom->item?->name }}</h1>
            <div class="bom-show-subtitle">{{ $bom->name ?: 'BOM '.$bom->item?->code }} · {{ $bom->active ? 'Aktif' : 'Nonaktif' }}</div>
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
