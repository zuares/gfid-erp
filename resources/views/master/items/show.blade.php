@extends('layouts.app')

@section('title', 'Detail Item · ' . $item->code)

@php
    $typeLabels = $typeLabels ?? ['material' => 'Material / Bahan', 'wip' => 'Setengah Jadi (WIP)', 'finished_good' => 'Barang Jadi (FG)'];
    $hpp = (float) ($activeSnapshot?->unit_cost ?? $item->effective_unit_cost ?? 0);
    $modeClass = $item->isHybrid() ? 'item-detail-hybrid' : ($item->canMake() ? 'item-detail-make' : ($item->canBuy() ? 'item-detail-buy' : 'item-detail-undefined'));
    $typeLabel = $item->itemTypeOption?->name ?? ($typeLabels[$item->type] ?? $item->type);
@endphp

@push('head')
<style>
    .item-detail-page { max-width:1040px; margin:0 auto; padding:.75rem .75rem 4rem; color:#0f172a; }
    .item-detail-hero, .item-detail-card { border:1px solid rgba(148,163,184,.18); border-radius:8px; background:#fff; box-shadow:none; }
    .item-detail-hero { display:flex; justify-content:space-between; align-items:center; gap:.6rem; padding:.45rem .75rem; margin-inline:-.75rem; margin-bottom:.65rem; border-width:0 0 1px; }
    .item-detail-main { display:flex; align-items:center; gap:12px; min-width:0; }
    .item-detail-icon { display:none; }
    .item-detail-code { color:#64748b; font-size:.66rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; }
    .item-detail-title { margin:1px 0 0; color:#0f172a; font-size:1rem; font-weight:750; letter-spacing:0; }
    .item-detail-subtitle { margin-top:1px; color:#64748b; font-size:.78rem; }
    .item-detail-actions { display:flex; flex-wrap:wrap; gap:7px; justify-content:flex-end; }
    .item-detail-actions .btn, .item-detail-card .btn { border-radius:7px; font-weight:600; display:inline-flex; align-items:center; gap:6px; }
    .item-detail-primary { color:#fff!important; background:linear-gradient(135deg,#0f172a,#334155)!important; border-color:transparent!important; }
    .item-detail-soft { color:#334155!important; background:#fff!important; border:1px solid #cbd5e1!important; }
    .item-detail-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-bottom:14px; }
    .item-detail-stat { padding:14px; border:1px solid #e2e8f0; border-radius:17px; background:linear-gradient(180deg,#fff,#f8fafc); }
    .item-detail-stat-label { color:#64748b; font-size:.67rem; font-weight:850; text-transform:uppercase; letter-spacing:.04em; }
    .item-detail-stat-value { margin-top:5px; color:#0f172a; font-size:1.05rem; font-weight:900; }
    .item-detail-stat-note { margin-top:2px; color:#94a3b8; font-size:.72rem; }
    .item-detail-section { padding:17px; }
    .item-detail-section + .item-detail-section { border-top:1px solid #eef2f7; }
    .item-detail-heading { margin:0 0 13px; color:#0f172a; font-size:.95rem; font-weight:900; }
    .item-detail-info { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px 18px; }
    .item-detail-label { color:#64748b; font-size:.68rem; font-weight:850; text-transform:uppercase; letter-spacing:.04em; }
    .item-detail-value { margin-top:3px; color:#0f172a; font-size:.83rem; font-weight:700; }
    .item-detail-note { color:#94a3b8; font-size:.72rem; }
    .item-detail-pill { display:inline-flex; align-items:center; gap:5px; border-radius:999px; padding:5px 9px; font-size:.7rem; font-weight:850; }
    .item-detail-buy { color:#1d4ed8; background:#eff6ff; border:1px solid #bfdbfe; }
    .item-detail-make { color:#047857; background:#ecfdf5; border:1px solid #a7f3d0; }
    .item-detail-hybrid { color:#7c3aed; background:#f5f3ff; border:1px solid #ddd6fe; }
    .item-detail-undefined { color:#92400e; background:#fffbeb; border:1px solid #fde68a; }
    .item-detail-table { margin:0; font-size:.8rem; }
    .item-detail-table th { padding:9px 10px; color:#64748b; background:#f8fafc; border-color:#e2e8f0; font-size:.67rem; text-transform:uppercase; letter-spacing:.04em; }
    .item-detail-table td { padding:10px; border-color:#eef2f7; vertical-align:middle; }
    .item-detail-empty { padding:18px; color:#94a3b8; text-align:center; font-size:.78rem; }
    body[data-theme="dark"] .item-detail-page { color:#e5e7eb; }
    body[data-theme="dark"] .item-detail-hero, body[data-theme="dark"] .item-detail-card, body[data-theme="dark"] .item-detail-stat { background:#0f172a; border-color:#334155; }
    body[data-theme="dark"] .item-detail-title, body[data-theme="dark"] .item-detail-value, body[data-theme="dark"] .item-detail-stat-value, body[data-theme="dark"] .item-detail-heading { color:#f8fafc; }
    body[data-theme="dark"] .item-detail-subtitle, body[data-theme="dark"] .item-detail-label, body[data-theme="dark"] .item-detail-stat-label { color:#94a3b8; }
    body[data-theme="dark"] .item-detail-table th { background:#111827; border-color:#334155; }
    body[data-theme="dark"] .item-detail-table td { border-color:#334155; }
    body[data-theme="dark"] .item-detail-soft { background:#0f172a!important; color:#cbd5e1!important; border-color:#475569!important; }
    @media(max-width:700px) { .item-detail-page { padding:.5rem .5rem 4rem; } .item-detail-hero { align-items:flex-start; flex-direction:column; margin-inline:-.5rem; padding:.5rem .65rem; } .item-detail-actions, .item-detail-actions .btn { width:100%; } .item-detail-actions .btn { justify-content:center; } .item-detail-grid, .item-detail-info { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:440px) { .item-detail-grid, .item-detail-info { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
@php($canManageBom = auth()->user()?->canAccessModule('master') ?? false)
<div class="item-detail-page">
    <div class="item-detail-hero">
        <div class="item-detail-main">
            <div class="item-detail-icon"><i class="bi bi-box-seam"></i></div>
            <div>
                <div class="item-detail-code">Master Item · {{ $item->code }}</div>
                <h1 class="item-detail-title">{{ $item->name }}</h1>
                <div class="item-detail-subtitle">{{ $item->sku ?: 'SKU mengikuti kode' }} · stok {{ $item->stockUnit() }} · beli {{ $item->purchaseUnit() }}@if($item->purchaseConversionFactor() != 1) · 1 {{ $item->purchaseUnit() }} = {{ decimal_id($item->purchaseConversionFactor(), 6) }} {{ $item->stockUnit() }}@endif</div>
            </div>
        </div>
        <div class="item-detail-actions">
            <a href="{{ route('master.items.edit', $item) }}" class="btn item-detail-primary btn-sm"><i class="bi bi-pencil"></i>Edit Item</a>
            <a href="{{ route('master.items.create') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-lg"></i>Tambah Item Lagi</a>
            @if($canManageBom && in_array($item->type, ['finished_good', 'wip'], true) && $item->canMake())
                @if($itemBom)
                    <a href="{{ route('master.item_boms.edit', $itemBom) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-diagram-3"></i>Kelola BOM</a>
                @else
                    <a href="{{ route('master.item_boms.create', ['item_id' => $item->id]) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-diagram-3"></i>Atur BOM</a>
                @endif
            @endif
            <a href="{{ route('master.items.index') }}" class="btn item-detail-soft btn-sm"><i class="bi bi-arrow-left"></i>Kembali</a>
            <a href="{{ route('master.items.hpp_temp.edit', $item) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-cash-coin"></i>Set HPP</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success py-2 px-3 mb-3" style="font-size:.8rem;">{{ session('success') }}</div>@endif

    <div class="item-detail-grid">
        <div class="item-detail-stat"><div class="item-detail-stat-label">Status</div><div class="item-detail-stat-value"><span class="item-detail-pill {{ $item->active ? 'item-detail-make' : 'item-detail-undefined' }}">{{ $item->active ? 'Aktif / Bisa dipakai' : 'Nonaktif' }}</span></div><div class="item-detail-stat-note">{{ $item->active ? 'Tersedia untuk transaksi baru' : 'Sembunyikan dari transaksi baru' }}</div></div>
        <div class="item-detail-stat"><div class="item-detail-stat-label">Tipe & kategori</div><div class="item-detail-stat-value">{{ $typeLabel }}</div><div class="item-detail-stat-note">{{ $item->category?->name ?? 'Tanpa kategori' }}</div></div>
        <div class="item-detail-stat"><div class="item-detail-stat-label">HPP aktif</div><div class="item-detail-stat-value">{{ $hpp > 0 ? 'Rp '.number_format($hpp, 0, ',', '.') : 'Belum di-set' }}</div><div class="item-detail-stat-note">{{ $activeSnapshot?->snapshot_date?->format('d/m/Y') ?? 'Belum ada snapshot' }}</div></div>
    </div>

    <div class="item-detail-card">
        <div class="item-detail-section">
            <h2 class="item-detail-heading"><i class="bi bi-diagram-3 me-2"></i>Ringkasan metode pasok</h2>
            @if(in_array($item->type, ['finished_good', 'wip'], true))
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="item-detail-pill {{ $modeClass }}">{{ $item->supply_mode_label }}</span>
                    <span class="item-detail-note">Prioritas default: <strong>{{ $item->default_supply_source_label }}</strong></span>
                </div>
                <div class="item-detail-note mt-2">Item ini dapat digunakan dalam alur pembelian jadi, produksi sendiri, atau keduanya sesuai pilihan di Master Item.</div>
            @else
                <div class="item-detail-note">Metode pasok tidak berlaku untuk item material.</div>
            @endif
        </div>

        @if(in_array($item->type, ['finished_good', 'wip'], true))
            <div class="item-detail-section">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <h2 class="item-detail-heading mb-0"><i class="bi bi-diagram-3 me-2"></i>Bill of Materials (BOM)</h2>
                    @if($itemBom)
                        <span class="item-detail-pill item-detail-make">{{ $itemBom->lines_count }} komponen</span>
                    @endif
                </div>
                @if($itemBom)
                    <div class="item-detail-note">BOM aktif untuk produksi sendiri dan siap dipakai pada kebutuhan material.</div>
                    @if($canManageBom)
                        <a href="{{ route('master.item_boms.edit', $itemBom) }}" class="btn item-detail-primary btn-sm mt-3"><i class="bi bi-pencil"></i>Review BOM</a>
                    @endif
                @elseif($item->canMake())
                    <div class="item-detail-note">Item ini bisa diproduksi sendiri tetapi belum memiliki BOM.</div>
                    @if($canManageBom)
                        <a href="{{ route('master.item_boms.create', ['item_id' => $item->id]) }}" class="btn item-detail-primary btn-sm mt-3"><i class="bi bi-plus-lg"></i>Tambah BOM Sekarang</a>
                    @endif
                @else
                    <div class="item-detail-note">Aktifkan metode pasok Produksi sendiri untuk membuat BOM.</div>
                @endif
            </div>
        @endif

        <div class="item-detail-section">
            <h2 class="item-detail-heading"><i class="bi bi-info-circle me-2"></i>Informasi item</h2>
            <div class="item-detail-info">
                <div><div class="item-detail-label">Kode</div><div class="item-detail-value">{{ $item->code }}</div></div>
                <div><div class="item-detail-label">SKU</div><div class="item-detail-value">{{ $item->sku ?: 'Mengikuti kode' }}</div></div>
                <div><div class="item-detail-label">Satuan stok</div><div class="item-detail-value">{{ $item->stockUnit() }}</div></div>
                <div><div class="item-detail-label">Satuan pembelian</div><div class="item-detail-value">{{ $item->purchaseUnit() }} = {{ decimal_id($item->purchaseConversionFactor(), 6) }} {{ $item->stockUnit() }}</div></div>
                <div><div class="item-detail-label">Kategori</div><div class="item-detail-value">{{ $item->category?->code ?? '-' }}{{ $item->category ? ' · '.$item->category->name : '' }}</div></div>
                <div><div class="item-detail-label">Harga beli terakhir</div><div class="item-detail-value">{{ (float) $item->last_purchase_price > 0 ? 'Rp '.number_format((float) $item->last_purchase_price, 0, ',', '.') : 'Belum ada' }}</div></div>
                <div><div class="item-detail-label">Perlakuan pembelian</div><div class="item-detail-value">{{ $item->purchaseTreatment?->name ?? ($item->default_allocation === 'expense' ? 'Langsung biaya' : 'Masuk stok / aset') }}</div></div>
            </div>
        </div>

        <div class="item-detail-section">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><h2 class="item-detail-heading mb-0"><i class="bi bi-upc-scan me-2"></i>Barcode</h2><span class="item-detail-note">{{ $item->barcodes->count() }} barcode</span></div>
            @if($item->barcodes->count())
                <div class="table-responsive"><table class="table item-detail-table"><thead><tr><th>Barcode</th><th>Tipe</th><th>Catatan</th><th>Status</th></tr></thead><tbody>@foreach($item->barcodes as $barcode)<tr><td class="fw-bold">{{ $barcode->barcode }}</td><td>{{ \Illuminate\Support\Str::headline($barcode->type ?? 'main') }}</td><td class="text-muted">{{ $barcode->notes ?: '-' }}</td><td>{{ $barcode->is_active ? 'Aktif' : 'Nonaktif' }}</td></tr>@endforeach</tbody></table></div>
            @else
                <div class="item-detail-empty">Belum ada barcode untuk item ini.</div>
            @endif
        </div>
    </div>
</div>
@endsection
