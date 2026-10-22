@extends('layouts.app')
@section('title', 'Edit: ' . $product->name)

@push('head')
<style>
.form-label-sm { font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin-bottom:.3rem; }
.form-hint { font-size:.7rem;color:#94a3b8;margin-top:.2rem; }
.tab-nav { display:flex;gap:.5rem;border-bottom:2px solid #e8ecf0;margin-bottom:1.5rem; }
.tab-btn { background:none;border:none;padding:.5rem 1rem;font-size:.8rem;font-weight:700;color:#94a3b8;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px; }
.tab-btn.active { color:#0f172a;border-bottom-color:#0f172a; }
.tab-pane { display:none; }
.tab-pane.active { display:block; }
/* Variant cards */
.var-card { border:1.5px solid #e8ecf0;border-radius:12px;overflow:hidden;background:#fff;transition:box-shadow .15s; }
.var-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); }
.var-img { width:100%;height:120px;object-fit:cover;background:#f1f5f9; }
.var-img-placeholder { width:100%;height:120px;display:flex;align-items:center;justify-content:center;background:#f8fafc; }
.var-swatch { width:20px;height:20px;border-radius:50%;border:2px solid rgba(0,0,0,.12);display:inline-block;vertical-align:middle;margin-right:4px; }
.item-search-wrap { position:relative; }
.item-suggest { display:none; position:absolute; left:0; right:0; top:calc(100% + 4px); z-index:20; background:#fff; border:1.5px solid #e2e8f0; border-radius:10px; box-shadow:0 12px 28px rgba(15,23,42,.12); max-height:190px; overflow:auto; }
.item-suggest.open { display:block; }
.item-suggest-floating { position:fixed; left:auto; right:auto; top:auto; z-index:4000; }
.item-suggest button { width:100%; border:0; background:#fff; text-align:left; padding:.55rem .75rem; font-size:.78rem; display:flex; flex-direction:column; gap:1px; }
.item-suggest button:hover { background:#f8fafc; }
.item-code { font-family:monospace; font-weight:800; color:#0f172a; }
.item-name { color:#64748b; font-size:.72rem; }
.mapping-table th { font-size:.67rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;padding:.55rem .7rem;background:#f8fafc;white-space:nowrap;border-bottom:1.5px solid #e8ecf0; }
.mapping-table td { font-size:.78rem;padding:.6rem .7rem;border-bottom:1px solid #f1f5f9;vertical-align:top; }
.mapping-table tr:last-child td { border-bottom:0; }
.mapping-color { display:flex;align-items:center;gap:.45rem;font-weight:850;color:#0f172a;white-space:nowrap; }
.mapping-size { font-weight:900;color:#0f172a; }
.mapping-stock { font-size:.7rem;color:#64748b;font-weight:800;margin-top:.25rem; }
.add-var-card { border:2px dashed #e2e8f0;border-radius:12px;height:100%;min-height:220px;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;background:#fafafa;transition:all .15s; }
.add-var-card:hover { border-color:#0f172a;background:#f1f5f9; }

/* Variant workspace */
.variant-workspace { display:grid; gap:1rem; }
.variant-overview { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1.15rem 1.25rem; border:1px solid #e5e7eb; border-radius:16px; background:linear-gradient(135deg,#fff 0%,#f8fafc 100%); }
.variant-overview-copy { min-width:0; }
.variant-eyebrow { color:#64748b; font-size:.62rem; font-weight:900; letter-spacing:.1em; text-transform:uppercase; }
.variant-heading { margin:.25rem 0 0; color:#0f172a; font-size:1rem; font-weight:900; letter-spacing:-.02em; }
.variant-description { max-width:680px; margin:.3rem 0 0; color:#64748b; font-size:.76rem; line-height:1.5; }
.variant-overview-meta { display:flex; align-items:center; gap:.65rem; flex:0 0 auto; }
.variant-count { display:inline-flex; align-items:center; gap:.35rem; padding:.45rem .65rem; border:1px solid #e2e8f0; border-radius:10px; color:#64748b; background:#fff; font-size:.7rem; font-weight:700; white-space:nowrap; }
.variant-count strong { color:#0f172a; font-size:.9rem; }
.variant-add-button { display:inline-flex; align-items:center; gap:.4rem; min-height:36px; padding:.45rem .75rem; border:0; border-radius:10px; color:#fff; background:#111827; font-size:.74rem; font-weight:800; cursor:pointer; box-shadow:0 6px 14px rgba(15,23,42,.12); }
.variant-add-button:hover { color:#fff; background:#000; transform:translateY(-1px); }
.variant-add-button:focus-visible, .variant-action:focus-visible, .variant-add-card:focus-visible, .variant-modal-close:focus-visible { outline:3px solid rgba(37,99,235,.3); outline-offset:2px; }
.variant-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; }
.variant-card { min-width:0; overflow:hidden; border:1px solid #e5e7eb; border-radius:16px; background:#fff; box-shadow:0 8px 22px rgba(15,23,42,.04); transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease; }
.variant-card:hover { border-color:#cbd5e1; box-shadow:0 12px 28px rgba(15,23,42,.09); transform:translateY(-2px); }
.variant-card.is-inactive { opacity:.76; }
.variant-media { position:relative; aspect-ratio:4/3; overflow:hidden; background:#f1f5f9; }
.variant-media img, .variant-media-placeholder { width:100%; height:100%; object-fit:cover; }
.variant-media-placeholder { display:grid; place-items:center; color:#cbd5e1; background:linear-gradient(135deg,#f8fafc,#e2e8f0); font-size:1.9rem; }
.variant-media::after { content:''; position:absolute; inset:auto 0 0; height:35%; background:linear-gradient(transparent,rgba(15,23,42,.25)); pointer-events:none; }
.variant-media-badges { position:absolute; z-index:1; inset:.6rem .6rem auto; display:flex; align-items:flex-start; justify-content:space-between; gap:.35rem; }
.variant-badge { display:inline-flex; align-items:center; gap:.3rem; padding:.25rem .45rem; border:1px solid rgba(255,255,255,.7); border-radius:7px; color:#0f172a; background:rgba(255,255,255,.88); font-size:.6rem; font-weight:900; letter-spacing:.04em; text-transform:uppercase; backdrop-filter:blur(8px); }
.variant-badge-default { color:#1d4ed8; }
.variant-badge-off { color:#92400e; background:rgba(255,251,235,.92); }
.variant-body { padding:.85rem .9rem .9rem; }
.variant-name-row { display:flex; align-items:center; gap:.45rem; min-width:0; }
.variant-swatch { width:18px; height:18px; flex:0 0 18px; border:2px solid rgba(15,23,42,.12); border-radius:50%; box-shadow:0 0 0 2px #fff; }
.variant-name { min-width:0; overflow:hidden; color:#0f172a; font-size:.85rem; font-weight:850; text-overflow:ellipsis; white-space:nowrap; }
.variant-meta { display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-top:.6rem; padding-top:.6rem; border-top:1px solid #eef2f7; }
.variant-meta-label { color:#94a3b8; font-size:.64rem; font-weight:700; }
.variant-meta-value { color:#334155; font-size:.72rem; font-weight:800; text-align:right; }
.variant-meta-value.is-price { color:#15803d; }
.variant-actions { display:flex; gap:.45rem; margin-top:.75rem; }
.variant-action { display:inline-flex; align-items:center; justify-content:center; gap:.3rem; min-height:32px; border-radius:8px; font-size:.7rem; font-weight:800; cursor:pointer; }
.variant-action-edit { flex:1 1 auto; border:1px solid #dbe3ee; color:#334155; background:#fff; }
.variant-action-edit:hover { color:#0f172a; background:#f8fafc; }
.variant-action-delete { width:32px; border:1px solid #fecdd3; color:#be123c; background:#fff; }
.variant-action-delete:hover { background:#fff1f2; }
.variant-add-card { display:flex; min-height:100%; flex-direction:column; align-items:center; justify-content:center; gap:.6rem; min-height:260px; padding:1rem; border:1.5px dashed #cbd5e1; border-radius:16px; color:#64748b; background:#fbfdff; cursor:pointer; transition:all .16s ease; }
.variant-add-card:hover { border-color:#64748b; color:#0f172a; background:#f8fafc; transform:translateY(-2px); }
.variant-add-card i { display:grid; width:42px; height:42px; place-items:center; border:1px solid #e2e8f0; border-radius:12px; color:#475569; background:#fff; font-size:1.15rem; }
.variant-add-card strong { font-size:.78rem; }
.variant-add-card span { color:#94a3b8; font-size:.68rem; text-align:center; }
.variant-empty { display:grid; place-items:center; padding:2.5rem 1rem; border:1px dashed #cbd5e1; border-radius:16px; color:#64748b; background:#fbfdff; text-align:center; }
.variant-empty-icon { display:grid; width:50px; height:50px; place-items:center; border-radius:15px; color:#64748b; background:#f1f5f9; font-size:1.35rem; }
.variant-empty-title { margin-top:.75rem; color:#334155; font-size:.85rem; font-weight:850; }
.variant-empty-copy { max-width:420px; margin:.25rem 0 1rem; color:#94a3b8; font-size:.72rem; line-height:1.5; }
.variant-guidance { display:flex; align-items:flex-start; gap:.5rem; padding:.75rem .85rem; border:1px solid #dbeafe; border-radius:12px; color:#1d4ed8; background:#eff6ff; font-size:.7rem; line-height:1.5; }
.variant-guidance i { flex:0 0 auto; margin-top:.05rem; }

/* Variant modals */
.variant-modal { display:none; position:fixed; inset:0; z-index:1055; align-items:center; justify-content:center; padding:1rem; background:rgba(15,23,42,.55); backdrop-filter:blur(4px); }
.variant-modal-panel { width:100%; max-width:500px; max-height:min(760px,calc(100vh - 2rem)); overflow-y:auto; border:1px solid rgba(255,255,255,.7); border-radius:18px; background:#fff; box-shadow:0 24px 70px rgba(15,23,42,.28); }
.variant-modal-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1.15rem 1.25rem 1rem; border-bottom:1px solid #eef2f7; background:linear-gradient(135deg,#fff,#f8fafc); }
.variant-modal-kicker { color:#64748b; font-size:.62rem; font-weight:900; letter-spacing:.09em; text-transform:uppercase; }
.variant-modal-title { margin:.25rem 0 0; color:#0f172a; font-size:1rem; font-weight:900; letter-spacing:-.02em; }
.variant-modal-subtitle { margin:.25rem 0 0; color:#64748b; font-size:.71rem; line-height:1.45; }
.variant-modal-close { display:grid; width:30px; height:30px; flex:0 0 30px; place-items:center; border:1px solid #e2e8f0; border-radius:9px; color:#64748b; background:#fff; cursor:pointer; }
.variant-modal-close:hover { color:#0f172a; background:#f8fafc; }
.variant-modal-body { padding:1.1rem 1.25rem 1.25rem; }
.variant-modal .form-label-sm { margin-bottom:.35rem; color:#334155; font-size:.68rem; }
.variant-modal .form-control { min-height:40px; border-color:#dbe3ee; border-radius:10px; font-size:.82rem; box-shadow:none; }
.variant-modal .form-control:focus { border-color:#111827; box-shadow:0 0 0 3px rgba(15,23,42,.08); }
.variant-field { margin-bottom:.9rem; }
.variant-field-hint { margin-top:.28rem; color:#94a3b8; font-size:.67rem; line-height:1.4; }
.variant-color-input { width:44px; height:40px; padding:3px; border:1px solid #dbe3ee; border-radius:10px; background:#fff; cursor:pointer; }
.variant-image-preview { margin-bottom:.55rem; overflow:hidden; border-radius:11px; border:1px solid #e2e8f0; background:#f8fafc; }
.variant-image-preview img { display:block; width:100%; height:150px; object-fit:cover; }
.variant-check-row { display:flex; flex-wrap:wrap; gap:1rem; margin:.15rem 0 1rem; }
.variant-check-row .form-check { margin:0; }
.variant-check-row .form-check-label { color:#475569; font-size:.76rem; font-weight:700; }
.variant-modal-footer { display:flex; flex-direction:row-reverse; gap:.5rem; padding-top:.9rem; border-top:1px solid #eef2f7; }
.variant-modal-footer .btn { min-height:38px; border-radius:10px; font-size:.75rem; font-weight:800; }
body[data-theme="dark"] .variant-overview, body[data-theme="dark"] .variant-card, body[data-theme="dark"] .variant-add-card, body[data-theme="dark"] .variant-empty, body[data-theme="dark"] .variant-count, body[data-theme="dark"] .variant-modal-panel { border-color:#334155; background:#0f172a; }
body[data-theme="dark"] .variant-overview { background:linear-gradient(135deg,#0f172a,#111827); }
body[data-theme="dark"] .variant-heading, body[data-theme="dark"] .variant-name, body[data-theme="dark"] .variant-count strong, body[data-theme="dark"] .variant-modal-title { color:#f8fafc; }
body[data-theme="dark"] .variant-description, body[data-theme="dark"] .variant-meta-label, body[data-theme="dark"] .variant-empty-copy, body[data-theme="dark"] .variant-modal-subtitle { color:#94a3b8; }
body[data-theme="dark"] .variant-meta, body[data-theme="dark"] .variant-modal-header, body[data-theme="dark"] .variant-modal-footer { border-color:#334155; }
body[data-theme="dark"] .variant-meta-value, body[data-theme="dark"] .variant-modal .form-label-sm, body[data-theme="dark"] .variant-check-row .form-check-label { color:#cbd5e1; }
body[data-theme="dark"] .variant-action-edit, body[data-theme="dark"] .variant-modal-close { color:#cbd5e1; border-color:#475569; background:#111827; }
body[data-theme="dark"] .variant-action-edit:hover, body[data-theme="dark"] .variant-modal-close:hover { background:#1e293b; }
body[data-theme="dark"] .variant-modal-header { background:linear-gradient(135deg,#0f172a,#111827); }
body[data-theme="dark"] .variant-modal .form-control { color:#f8fafc; background:#111827; border-color:#475569; }
body[data-theme="dark"] .variant-color-input { border-color:#475569; background:#111827; }
@media (max-width:1100px) { .variant-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
@media (max-width:760px) {
    .variant-overview { flex-direction:column; }
    .variant-overview-meta { width:100%; justify-content:space-between; }
    .variant-add-button { flex:1 1 auto; justify-content:center; }
    .variant-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem; }
    .variant-body { padding:.75rem; }
    .variant-add-card { min-height:220px; }
}
@media (max-width:480px) {
    .variant-grid { grid-template-columns:1fr; }
    .variant-add-card { min-height:150px; }
    .variant-modal { padding:.5rem; }
    .variant-modal-panel { max-height:calc(100vh - 1rem); }
    .variant-modal-header, .variant-modal-body { padding-inline:.9rem; }
    .variant-modal-footer { flex-direction:column-reverse; }
    .variant-modal-footer .btn { width:100%; }
}
@media (prefers-reduced-motion:reduce) { .variant-card, .variant-add-card, .variant-add-button { transition:none; } .variant-card:hover, .variant-add-card:hover, .variant-add-button:hover { transform:none; } }
/* Size table */
.size-table th { font-size:.67rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;padding:.5rem .75rem;background:#f8fafc;white-space:nowrap;border-bottom:1.5px solid #e8ecf0; }
.size-table td { font-size:.82rem;padding:.5rem .75rem;border-bottom:1px solid #f1f5f9;vertical-align:middle; }
.size-table tr:last-child td { border-bottom:0; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Flash --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible py-2 mb-3" style="font-size:.8rem;border-radius:10px;">
        <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible py-2 mb-3" style="font-size:.8rem;border-radius:10px;">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $errors->first() }}
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.catalog.products.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.75rem;">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h5 class="fw-black mb-0" style="font-size:1.05rem;">{{ $product->name }}</h5>
                <div class="d-flex gap-2 align-items-center mt-1">
                    <span style="font-size:.68rem;font-family:monospace;color:#94a3b8;">{{ $product->slug }}</span>
                    <span style="font-size:.62rem;font-weight:800;padding:.12rem .4rem;border-radius:5px;
                                 background:{{ $product->type_badge_color }}20;color:{{ $product->type_badge_color }};">
                        {{ $product->type_label }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Publish toggle --}}
        <form method="POST" action="{{ route('admin.catalog.products.toggle-publish', $product) }}" class="d-flex align-items-center gap-2">
            @csrf
            <span style="font-size:.75rem;color:{{ $product->is_published ? '#15803d' : '#94a3b8' }};font-weight:700;">
                <i class="bi bi-circle-fill me-1" style="font-size:.55rem;"></i>
                {{ $product->is_published ? 'Published' : 'Draft' }}
            </span>
            <button type="submit" class="btn btn-sm"
                    style="border-radius:10px;font-size:.75rem;font-weight:700;
                           background:{{ $product->is_published ? '#fef3c7' : '#dcfce7' }};
                           color:{{ $product->is_published ? '#92400e' : '#15803d' }};border:none;">
                <i class="bi bi-{{ $product->is_published ? 'eye-slash' : 'eye' }} me-1"></i>
                {{ $product->is_published ? 'Sembunyikan' : 'Publikasikan' }}
            </button>
        </form>
    </div>

    {{-- Tabs --}}
    <div class="tab-nav">
        <button class="tab-btn active" data-tab="info">
            <i class="bi bi-info-circle me-1"></i>Info Dasar
        </button>
        <button class="tab-btn" data-tab="variants">
            <i class="bi bi-palette me-1"></i>Variant Warna
            <span style="background:#e2e8f0;border-radius:999px;padding:.05rem .45rem;font-size:.65rem;margin-left:.25rem;">{{ $product->variants->count() }}</span>
        </button>
        <button class="tab-btn" data-tab="sizes">
            <i class="bi bi-rulers me-1"></i>Ukuran
            <span style="background:#e2e8f0;border-radius:999px;padding:.05rem .45rem;font-size:.65rem;margin-left:.25rem;">{{ $product->sizes->count() }}</span>
        </button>
        <button class="tab-btn" data-tab="mapping">
            <i class="bi bi-grid-3x3-gap me-1"></i>Mapping Item
            <span style="background:#e2e8f0;border-radius:999px;padding:.05rem .45rem;font-size:.65rem;margin-left:.25rem;">{{ $product->variantItemMappings->count() }}</span>
        </button>
        <button class="tab-btn" data-tab="ranking" id="tab-btn-ranking">
            <i class="bi bi-bar-chart-line me-1"></i>Ranking
            @if($product->rank_position)
            <span style="background:#dbeafe;color:#1d4ed8;border-radius:999px;padding:.05rem .45rem;font-size:.65rem;margin-left:.25rem;">#{{ $product->rank_position }}</span>
            @endif
        </button>
    </div>

    {{-- ══ TAB 1: INFO DASAR ════════════════════════════════════════════════════ --}}
    <div class="tab-pane active" id="tab-info">
        <div class="row g-3">
            <div class="col-lg-7">
                <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:14px;padding:1.5rem;">
                    <form method="POST" action="{{ route('admin.catalog.products.update', $product) }}" enctype="multipart/form-data">
                        @csrf @method('PUT')

                        <div class="mb-3">
                            <label class="form-label-sm">Nama Produk</label>
                            <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                                   class="form-control" style="border-radius:10px;font-size:.85rem;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label-sm">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" required
                                   class="form-control" style="border-radius:10px;font-size:.85rem;font-family:monospace;">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label-sm">Tipe Produk</label>
                                <select name="product_type" class="form-select" style="border-radius:10px;font-size:.85rem;">
                                    <option value="regular" {{ $product->product_type === 'regular' ? 'selected' : '' }}>Regular (S–XXL)</option>
                                    <option value="jumbo"   {{ $product->product_type === 'jumbo'   ? 'selected' : '' }}>Jumbo (3XL–6XL)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label-sm">Harga Dasar (Rp)</label>
                                <input type="number" name="base_price" value="{{ old('base_price', $product->base_price) }}" min="0"
                                       class="form-control" style="border-radius:10px;font-size:.85rem;">
                            </div>
                            <div class="col-6">
                                <label class="form-label-sm">Berat per Pcs (kg)</label>
                                <input type="number" name="weight_kg" value="{{ old('weight_kg', $product->weight_kg) }}" min="0" step="0.001"
                                       class="form-control @error('weight_kg') is-invalid @enderror"
                                       style="border-radius:10px;font-size:.85rem;" placeholder="0.5">
                                <div class="form-hint" style="font-size:.68rem;color:#94a3b8;">Untuk estimasi ongkir di checkout. Kosongkan = pakai default setting website</div>
                                @error('weight_kg')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- Category + Audience row --}}
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label-sm">Kategori</label>
                                <select name="category_id"
                                        class="form-select @error('category_id') is-invalid @enderror"
                                        style="border-radius:10px;font-size:.85rem;">
                                    <option value="">— Tanpa Kategori —</option>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label-sm">Target Audience</label>
                                <select name="audience"
                                        class="form-select @error('audience') is-invalid @enderror"
                                        style="border-radius:10px;font-size:.85rem;">
                                    <option value="">— Semua —</option>
                                    @foreach($audienceOptions as $val => $label)
                                    <option value="{{ $val }}"
                                        {{ old('audience', $product->audience) === $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('audience')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label-sm">Badge Label</label>
                                <input type="text" name="label" value="{{ old('label', $product->label) }}"
                                       class="form-control" style="border-radius:10px;font-size:.85rem;"
                                       placeholder="Best Seller / New / Sale">
                            </div>
                            <div class="col-6">
                                <label class="form-label-sm">Urutan Tampil</label>
                                <input type="number" name="sort_order" value="{{ old('sort_order', $product->sort_order) }}" min="0"
                                       class="form-control" style="border-radius:10px;font-size:.85rem;">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-sm">Deskripsi</label>
                            <textarea name="description" rows="4" class="form-control"
                                      style="border-radius:10px;font-size:.85rem;resize:vertical;">{{ old('description', $product->description) }}</textarea>
                        </div>

                        {{-- Current thumbnail --}}
                        @if($product->getImageSrc())
                        <div class="mb-3">
                            <label class="form-label-sm">Foto Thumbnail Saat Ini</label>
                            <div class="d-flex align-items-start gap-3">
                                <img src="{{ $product->getImageSrc() }}" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1.5px solid #e8ecf0;">
                                <div>
                                    <div class="form-hint">Upload foto baru untuk mengganti</div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="mb-4">
                            <label class="form-label-sm">Ganti Foto Thumbnail</label>
                            <input type="file" name="image" accept="image/*"
                                   class="form-control mb-2" style="border-radius:10px;font-size:.82rem;">
                            <div style="font-size:.7rem;color:#94a3b8;">atau URL:</div>
                            <input type="text" name="image_url" value="{{ old('image_url') }}"
                                   class="form-control mt-1" style="border-radius:10px;font-size:.8rem;font-family:monospace;"
                                   placeholder="https://...">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-dark fw-bold" style="border-radius:10px;font-size:.82rem;">
                                <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                {{-- Danger zone --}}
                <div style="background:#fff;border:1.5px solid #fecdd3;border-radius:14px;padding:1.25rem;">
                    <div style="font-size:.78rem;font-weight:800;color:#be123c;margin-bottom:.75rem;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Danger Zone
                    </div>
                    <div style="font-size:.78rem;color:#64748b;margin-bottom:1rem;">
                        Menghapus produk akan menghapus semua variant dan ukurannya secara permanen. Tindakan ini tidak bisa dibatalkan.
                    </div>
                    <form method="POST" action="{{ route('admin.catalog.products.destroy', $product) }}"
                          onsubmit="return confirm('Hapus produk "{{ addslashes($product->name) }}"? Tindakan ini TIDAK bisa dibatalkan.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm"
                                style="background:#fff;border:1.5px solid #fecdd3;color:#be123c;border-radius:8px;font-size:.75rem;font-weight:700;">
                            <i class="bi bi-trash3 me-1"></i> Hapus Produk Ini
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ TAB 2: VARIANT WARNA ════════════════════════════════════════════════ --}}
    <div class="tab-pane" id="tab-variants">
        @php
            $displayVariants = $product->variants
                ->groupBy(fn($variant) => strtolower(trim((string) $variant->color_name)))
                ->map(fn($group) => $group->firstWhere('size_label', null) ?? $group->first())
                ->values();
            $activeVariantCount = $displayVariants->where('is_active', true)->count();
        @endphp

        <div class="variant-workspace">
            <div class="variant-overview">
                <div class="variant-overview-copy">
                    <div class="variant-eyebrow"><i class="bi bi-palette me-1"></i>Visual katalog</div>
                    <h2 class="variant-heading">Warna & tampilan produk</h2>
                    <p class="variant-description">Atur warna yang muncul di storefront, foto utama tiap warna, dan harga khusus bila berbeda dari harga dasar produk.</p>
                </div>
                <div class="variant-overview-meta">
                    <span class="variant-count"><strong>{{ $activeVariantCount }}</strong> aktif <span>· {{ $displayVariants->count() }} total</span></span>
                    <button type="button" class="variant-add-button" onclick="openAddVariant()">
                        <i class="bi bi-plus-lg"></i> Tambah warna
                    </button>
                </div>
            </div>

            @if($displayVariants->isEmpty())
                <div class="variant-empty">
                    <div class="variant-empty-icon"><i class="bi bi-palette"></i></div>
                    <div class="variant-empty-title">Belum ada variant warna</div>
                    <p class="variant-empty-copy">Tambahkan warna pertama agar produk punya pilihan visual saat tampil di katalog dan storefront.</p>
                    <button type="button" class="variant-add-button" onclick="openAddVariant()"><i class="bi bi-plus-lg"></i> Tambah warna pertama</button>
                </div>
            @else
                <div class="variant-grid">
                    @foreach($displayVariants as $v)
                        <article class="variant-card {{ !$v->is_active ? 'is-inactive' : '' }}">
                            <div class="variant-media">
                                @if($v->getImageSrc())
                                    <img src="{{ $v->getImageSrc() }}" alt="Foto variant {{ $v->color_name }}" loading="lazy">
                                @else
                                    <div class="variant-media-placeholder"><i class="bi bi-image"></i></div>
                                @endif
                                <div class="variant-media-badges">
                                    @if($v->is_default)
                                        <span class="variant-badge variant-badge-default"><i class="bi bi-star-fill"></i> Utama</span>
                                    @else
                                        <span></span>
                                    @endif
                                    @if(!$v->is_active)
                                        <span class="variant-badge variant-badge-off">Nonaktif</span>
                                    @endif
                                </div>
                            </div>

                            <div class="variant-body">
                                <div class="variant-name-row">
                                    @if($v->hex_color)
                                        <span class="variant-swatch" style="background:{{ $v->hex_color }};" aria-hidden="true"></span>
                                    @endif
                                    <span class="variant-name" title="{{ $v->color_name }}">{{ $v->color_name }}</span>
                                </div>
                                <div class="variant-meta">
                                    <span class="variant-meta-label">Harga</span>
                                    @if($v->price_override)
                                        <span class="variant-meta-value is-price">Rp{{ number_format($v->price_override) }}</span>
                                    @else
                                        <span class="variant-meta-value">Harga dasar</span>
                                    @endif
                                </div>
                                <div class="variant-actions">
                                    <button type="button" class="variant-action variant-action-edit"
                                            onclick="openEditVariant({{ json_encode([
                                                'id'             => $v->id,
                                                'color_name'     => $v->color_name,
                                                'hex_color'      => $v->hex_color ?? '#000000',
                                                'image_src'      => $v->getImageSrc(),
                                                'price_override' => $v->price_override,
                                                'is_default'     => $v->is_default,
                                                'is_active'      => $v->is_active,
                                            ]) }})">
                                        <i class="bi bi-pencil-square"></i> Edit variant
                                    </button>
                                    <form method="POST" action="{{ route('admin.catalog.products.variants.destroy', [$product, $v]) }}"
                                          onsubmit="return confirm('Hapus variant {{ addslashes($v->color_name) }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="variant-action variant-action-delete" aria-label="Hapus variant {{ $v->color_name }}" title="Hapus variant">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach

                    <button type="button" class="variant-add-card" onclick="openAddVariant()">
                        <i class="bi bi-plus-lg"></i>
                        <strong>Tambah warna lain</strong>
                        <span>Tambahkan foto dan detail variant baru</span>
                    </button>
                </div>
            @endif

            <div class="variant-guidance">
                <i class="bi bi-info-circle-fill"></i>
                <span>Variant pertama otomatis menjadi warna utama. Warna utama dipakai sebagai foto fallback saat produk tampil di listing.</span>
            </div>
        </div>
    </div>

    {{-- ══ TAB 3: UKURAN ═══════════════════════════════════════════════════════ --}}
    <div class="tab-pane" id="tab-sizes">
        <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:14px;overflow:hidden;">
            <table class="table mb-0 size-table">
                <thead>
                    <tr>
                        <th>Ukuran</th>
                        <th>Harga Override</th>
                        <th>Status</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($product->sizes as $s)
                    <tr>
                        <td>
                            <span style="font-size:.9rem;font-weight:800;color:#0f172a;">{{ $s->size_label }}</span>
                        </td>
                        <td>
                            @if($s->price_override)
                            <span style="color:#16a34a;font-weight:700;">Rp{{ number_format($s->price_override) }}</span>
                            @else
                            <span style="color:#94a3b8;">Rp{{ number_format($product->base_price) }} <span style="font-size:.7rem;">(base)</span></span>
                            @endif
                        </td>
                        <td>
                            <span style="font-size:.7rem;font-weight:800;padding:.12rem .4rem;border-radius:5px;
                                         background:{{ $s->is_active ? '#dcfce7' : '#f1f5f9' }};
                                         color:{{ $s->is_active ? '#15803d' : '#94a3b8' }};">
                                {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <div class="d-flex gap-1 justify-content-end">
                                <button class="btn btn-sm btn-outline-secondary"
                                        style="font-size:.7rem;border-radius:7px;padding:.2rem .55rem;"
                                        onclick="openEditSize({{ json_encode([
                                            'id'             => $s->id,
                                            'size_label'     => $s->size_label,
                                            'price_override' => $s->price_override,
                                            'is_active'      => $s->is_active,
                                        ]) }})">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <form method="POST" action="{{ route('admin.catalog.products.sizes.destroy', [$product, $s]) }}"
                                      onsubmit="return confirm('Hapus ukuran {{ $s->size_label }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm"
                                            style="font-size:.7rem;border-radius:7px;padding:.2rem .45rem;background:#fff;border:1.5px solid #fecdd3;color:#be123c;">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:2rem;color:#94a3b8;font-size:.82rem;">
                            Belum ada ukuran. Tambahkan di bawah.
                        </td>
                    </tr>
                    @endforelse

                    {{-- Inline add row --}}
                    <tr style="background:#f8fafc;">
                        <form method="POST" action="{{ route('admin.catalog.products.sizes.store', $product) }}" class="d-contents">
                            @csrf
                            <td>
                                <input type="text" name="size_label" placeholder="mis. XL atau 3XL"
                                       class="form-control form-control-sm" required
                                       style="border-radius:8px;font-size:.82rem;max-width:110px;font-weight:700;">
                            </td>
                            <td>
                                <input type="number" name="price_override" min="0" placeholder="Kosong = base price"
                                       class="form-control form-control-sm"
                                       style="border-radius:8px;font-size:.8rem;max-width:160px;">
                            </td>
                            <td colspan="2">
                                <button type="submit" class="btn btn-sm btn-dark"
                                        style="border-radius:8px;font-size:.75rem;font-weight:700;">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Ukuran
                                </button>
                            </td>
                        </form>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Quick add common sizes --}}
        <div class="mt-3" style="font-size:.75rem;color:#64748b;">
            <span style="font-weight:700;">Quick add:</span>
            @php
                $quickSizes = $product->product_type === 'jumbo'
                    ? ['3XL','4XL','5XL','6XL','7XL']
                    : ['S','M','L','XL','XXL'];
                $existingSizes = $product->sizes->pluck('size_label')->toArray();
            @endphp
            @foreach($quickSizes as $qs)
            @if(!in_array($qs, $existingSizes))
            <form method="POST" action="{{ route('admin.catalog.products.sizes.store', $product) }}" class="d-inline">
                @csrf
                <input type="hidden" name="size_label" value="{{ $qs }}">
                <button type="submit" class="btn btn-sm"
                        style="border-radius:999px;font-size:.7rem;font-weight:700;padding:.2rem .65rem;border:1.5px solid #e2e8f0;background:#fff;color:#334155;margin-bottom:.3rem;">
                    + {{ $qs }}
                </button>
            </form>
            @endif
            @endforeach
        </div>
    </div>

    {{-- ══ TAB 4: MAPPING ITEM INTERNAL ═══════════════════════════════════════ --}}
    <div class="tab-pane" id="tab-mapping">
        @php
            $mappingVariants = $product->variants
                ->groupBy(fn($variant) => strtolower(trim((string) $variant->color_name)))
                ->map(fn($group) => $group->firstWhere('size_label', null) ?? $group->first())
                ->values();
            $mappingByKey = $product->variantItemMappings->keyBy(fn($m) => $m->variant_id . '-' . $m->size_id);
            $stockResolver = app(\App\Services\Storefront\StockResolver::class);
        @endphp

        <form method="POST" action="{{ route('admin.catalog.products.variant-items.update', $product) }}">
            @csrf @method('PATCH')
            <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:14px;overflow:hidden;">
                <div style="padding:1rem 1.1rem;border-bottom:1px solid #e8ecf0;display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                    <div>
                        <div style="font-size:.82rem;font-weight:900;color:#0f172a;">Mapping item internal</div>
                        <div style="font-size:.72rem;color:#94a3b8;margin-top:.15rem;">Isi item internal per kombinasi warna dan ukuran. Stok website akan mengikuti item yang dipilih.</div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-dark" style="border-radius:9px;font-size:.75rem;font-weight:800;">
                        <i class="bi bi-check-lg me-1"></i> Simpan Mapping
                    </button>
                </div>

                @if($mappingVariants->isEmpty() || $product->sizes->isEmpty())
                <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.82rem;">
                    Tambahkan warna dan ukuran terlebih dahulu sebelum mapping item internal.
                </div>
                @else
                <div style="overflow-x:auto;">
                    <table class="table mb-0 mapping-table">
                        <thead>
                            <tr>
                                <th>Warna</th>
                                <th>Ukuran</th>
                                <th style="min-width:260px;">Item Internal</th>
                                <th style="min-width:150px;">Harga Website</th>
                                <th style="min-width:130px;">Stok Fallback</th>
                                <th>Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mappingVariants as $variant)
                                @foreach($product->sizes as $size)
                                    @php
                                        $mapping = $mappingByKey->get($variant->id . '-' . $size->id);
                                        $itemLabel = $mapping?->item ? trim(($mapping->item->code ?? '') . ' — ' . ($mapping->item->name ?? '')) : '';
                                        $resolvedStock = $mapping ? $stockResolver->forMapping($mapping) : null;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="mapping-color">
                                                @if($variant->hex_color)
                                                <span class="var-swatch" style="background:{{ $variant->hex_color }};"></span>
                                                @endif
                                                <span>{{ $variant->color_name }}</span>
                                            </div>
                                        </td>
                                        <td><span class="mapping-size">{{ $size->size_label }}</span></td>
                                        <td>
                                            <div class="item-search-wrap" data-item-search>
                                                <input type="hidden" name="mappings[{{ $variant->id }}][{{ $size->id }}][item_id]" value="{{ $mapping?->item_id }}" data-item-id>
                                                <input type="text" class="form-control form-control-sm" data-item-input
                                                       value="{{ $itemLabel }}"
                                                       style="border-radius:8px;font-size:.78rem;"
                                                       placeholder="Cari kode/nama item">
                                                <div class="item-suggest" data-item-results></div>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number"
                                                   name="mappings[{{ $variant->id }}][{{ $size->id }}][price_override]"
                                                   value="{{ $mapping?->price_override }}"
                                                   min="0"
                                                   class="form-control form-control-sm"
                                                   style="border-radius:8px;font-size:.78rem;"
                                                   placeholder="Base">
                                        </td>
                                        <td>
                                            <input type="number"
                                                   name="mappings[{{ $variant->id }}][{{ $size->id }}][stock_override]"
                                                   value="{{ $mapping?->stock_override }}"
                                                   min="0"
                                                   class="form-control form-control-sm"
                                                   style="border-radius:8px;font-size:.78rem;"
                                                   placeholder="Manual">
                                        </td>
                                        <td>
                                            @if($mapping?->item)
                                            <div class="mapping-stock" data-stock-output>{{ $resolvedStock }} pcs tersedia</div>
                                            @elseif($mapping?->stock_override !== null)
                                            <div class="mapping-stock" data-stock-output>{{ $mapping->stock_override }} pcs manual</div>
                                            @else
                                            <div class="mapping-stock" data-stock-output style="color:#cbd5e1;">Belum diisi</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </form>
    </div>

</div>

{{-- ══ MODAL: ADD VARIANT ══════════════════════════════════════════════════════ --}}
<div id="addVariantModal" class="variant-modal" role="dialog" aria-modal="true" aria-labelledby="addVariantTitle">
    <div class="variant-modal-panel">
        <div class="variant-modal-header">
            <div>
                <div class="variant-modal-kicker"><i class="bi bi-palette me-1"></i>Variant warna</div>
                <h2 id="addVariantTitle" class="variant-modal-title">Tambah warna baru</h2>
                <p class="variant-modal-subtitle">Lengkapi identitas warna dan aset visual yang akan digunakan di storefront.</p>
            </div>
            <button type="button" class="variant-modal-close" onclick="closeVariantModal('addVariantModal')" aria-label="Tutup dialog">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.catalog.products.variants.store', $product) }}" enctype="multipart/form-data" class="variant-modal-body">
            @csrf
            <div class="variant-field">
                <label class="form-label-sm" for="addColorName">Nama warna <span class="text-danger">*</span></label>
                <input id="addColorName" type="text" name="color_name" required class="form-control" placeholder="Mis. Navy, Black, Olive">
            </div>
            <div class="variant-field">
                <label class="form-label-sm" for="addHexText">Kode warna</label>
                <div class="d-flex gap-2 align-items-center">
                    <input type="color" name="hex_color" value="#000000" id="addHexPicker"
                           class="variant-color-input" aria-label="Pilih warna">
                    <input type="text" id="addHexText" value="#000000"
                           class="form-control font-monospace"
                           placeholder="#000000" maxlength="20">
                </div>
                <div class="variant-field-hint">Dipakai sebagai swatch kecil di pilihan warna.</div>
            </div>
            <div class="variant-field">
                <label class="form-label-sm" for="addImageFile">Foto variant</label>
                <input type="file" name="image" accept="image/*" id="addImageFile"
                       class="form-control"
                       onchange="previewImg(this, 'addImgPreview')">
                <div id="addImgPreview" class="variant-image-preview" style="display:none;margin-top:.55rem;">
                    <img src="" alt="Preview foto variant">
                </div>
                <div class="variant-field-hint">Upload file atau gunakan URL gambar publik.</div>
                <input type="text" name="image_url" class="form-control mt-1"
                       placeholder="https://...">
            </div>
            <div class="variant-field">
                <label class="form-label-sm" for="addPriceOverride">Harga khusus warna <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="number" name="price_override" min="0" class="form-control"
                       id="addPriceOverride" placeholder="Kosong = pakai harga dasar">
            </div>
            <div class="variant-check-row">
                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="addIsDefault">
                <label class="form-check-label" for="addIsDefault">Jadikan warna utama di listing</label>
            </div>
            <div class="variant-modal-footer">
                <button type="button" onclick="closeVariantModal('addVariantModal')" class="btn btn-outline-secondary">Batal</button>
                <button type="submit" class="btn btn-dark"><i class="bi bi-plus-lg me-1"></i> Tambah variant</button>
            </div>
        </form>
    </div>
</div>

{{-- ══ MODAL: EDIT VARIANT ═════════════════════════════════════════════════════ --}}
<div id="editVariantModal" class="variant-modal" role="dialog" aria-modal="true" aria-labelledby="editVariantTitle">
    <div class="variant-modal-panel">
        <div class="variant-modal-header">
            <div>
                <div class="variant-modal-kicker"><i class="bi bi-pencil-square me-1"></i>Kelola variant</div>
                <h2 id="editVariantTitle" class="variant-modal-title">Edit warna variant</h2>
                <p class="variant-modal-subtitle">Perbarui identitas, foto, harga, atau status variant ini.</p>
            </div>
            <button type="button" class="variant-modal-close" onclick="closeVariantModal('editVariantModal')" aria-label="Tutup dialog">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form id="editVariantForm" method="POST" enctype="multipart/form-data" class="variant-modal-body">
            @csrf @method('PATCH')
            <div class="variant-field">
                <label class="form-label-sm" for="editColorName">Nama warna <span class="text-danger">*</span></label>
                <input type="text" name="color_name" id="editColorName" required class="form-control">
            </div>
            <div class="variant-field">
                <label class="form-label-sm" for="editHexText">Kode warna</label>
                <div class="d-flex gap-2 align-items-center">
                    <input type="color" name="hex_color" id="editHexPicker"
                           class="variant-color-input" aria-label="Pilih warna">
                    <input type="text" id="editHexText" class="form-control font-monospace" maxlength="20">
                </div>
            </div>
            <div class="variant-field">
                <label class="form-label-sm" for="editImageFile">Foto variant</label>
                <div id="editCurrentImg" class="variant-image-preview" style="display:none;">
                    <img id="editCurrentImgEl" src="" alt="Foto variant saat ini">
                </div>
                <input type="file" name="image" accept="image/*" id="editImageFile" class="form-control"
                       onchange="previewImg(this, 'editImgPreview')">
                <div id="editImgPreview" class="variant-image-preview" style="display:none;margin-top:.55rem;">
                    <img src="" alt="Preview foto variant baru">
                </div>
                <div class="variant-field-hint">Upload untuk mengganti foto, atau isi URL baru. Kosongkan jika tetap memakai foto lama.</div>
                <input type="text" name="image_url" id="editImageUrl" class="form-control mt-1"
                       placeholder="https://...">
            </div>
            <div class="variant-field">
                <label class="form-label-sm" for="editPriceOverride">Harga khusus warna <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="number" name="price_override" id="editPriceOverride" min="0" class="form-control"
                       placeholder="Kosong = pakai harga dasar">
            </div>
            <div class="variant-check-row">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="editIsDefault">
                    <label class="form-check-label" for="editIsDefault">Warna utama</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editIsActive" checked>
                    <label class="form-check-label" for="editIsActive">Variant aktif</label>
                </div>
            </div>
            <div class="variant-modal-footer">
                <button type="button" onclick="closeVariantModal('editVariantModal')" class="btn btn-outline-secondary">Batal</button>
                <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg me-1"></i> Simpan perubahan</button>
            </div>
        </form>
    </div>
</div>

{{-- ══ MODAL: EDIT SIZE ════════════════════════════════════════════════════════ --}}
<div id="editSizeModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1055;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:400px;padding:1.5rem;margin:1rem;">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-black mb-0">Edit Ukuran</h6>
            <button type="button" onclick="document.getElementById('editSizeModal').style.display='none'"
                    style="background:none;border:none;font-size:1.25rem;color:#94a3b8;cursor:pointer;">×</button>
        </div>
        <form id="editSizeForm" method="POST">
            @csrf @method('PATCH')
            <div class="mb-3">
                <label class="form-label-sm">Label Ukuran</label>
                <input type="text" name="size_label" id="editSizeLabel" required class="form-control"
                       style="border-radius:10px;font-weight:700;font-size:.9rem;">
            </div>
            <div class="mb-3">
                <label class="form-label-sm">Harga Khusus Ukuran Ini (opsional)</label>
                <input type="number" name="price_override" id="editSizePrice" min="0" class="form-control"
                       style="border-radius:10px;" placeholder="Kosong = pakai harga dasar">
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editSizeActive" checked>
                <label class="form-check-label" for="editSizeActive" style="font-size:.8rem;">Aktif</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-dark fw-bold flex-grow-1" style="border-radius:10px;">
                    Simpan
                </button>
                <button type="button" onclick="document.getElementById('editSizeModal').style.display='none'"
                        class="btn btn-outline-secondary" style="border-radius:10px;">Batal</button>
            </div>
        </form>
    </div>
</div>

{{-- ══ TAB 4: RANKING ═════════════════════════════════════════════════════════ --}}
<div class="tab-pane" id="tab-ranking">
<div class="row g-3">

    {{-- Kiri: Override controls --}}
    <div class="col-lg-6">
        <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:14px;padding:1.5rem;">
            <div style="font-size:.78rem;font-weight:800;color:#0f172a;margin-bottom:1rem;">
                <i class="bi bi-sliders me-1"></i>Override Ranking
            </div>

            <form method="POST" action="{{ route('admin.catalog.products.ranking.update', $product) }}">
                @csrf @method('PATCH')

                {{-- Stock fallback --}}
                <div class="mb-3">
                    <label class="form-label-sm">Stok Fallback (produk tanpa variant)</label>
                    <input type="number" name="stock" value="{{ old('stock', $product->stock ?? 0) }}"
                           min="0" class="form-control" style="border-radius:10px;font-size:.85rem;">
                    <div class="form-hint">Jika produk punya variant aktif, stok diambil dari sum variant.stock.</div>
                </div>

                {{-- Pin --}}
                <div class="mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="is_pinned" id="isPinned"
                                   value="1" {{ $product->is_pinned ? 'checked' : '' }}
                                   onchange="document.getElementById('pinPositionRow').style.display = this.checked ? '' : 'none'">
                            <label class="form-check-label form-label-sm mb-0" for="isPinned">
                                📌 Pin produk ini ke atas
                            </label>
                        </div>
                    </div>
                    <div class="form-hint">Produk pinned selalu tampil paling atas, diurutkan by pin_position.</div>
                </div>

                <div class="mb-3" id="pinPositionRow" style="{{ $product->is_pinned ? '' : 'display:none' }}">
                    <label class="form-label-sm">Pin Position</label>
                    <input type="number" name="pin_position" value="{{ old('pin_position', $product->pin_position) }}"
                           min="1" class="form-control" style="border-radius:10px;font-size:.85rem;max-width:120px;">
                    <div class="form-hint">Urutan tampil di antara produk pinned lain (1 = paling atas).</div>
                </div>

                {{-- Manual boost --}}
                <div class="mb-3">
                    <label class="form-label-sm">Manual Boost <span style="color:#94a3b8;font-weight:400;">(additif ke final score)</span></label>
                    <input type="number" name="manual_boost"
                           value="{{ old('manual_boost', number_format($product->manual_boost ?? 0, 3, '.', '')) }}"
                           min="0" max="5" step="0.05"
                           class="form-control" style="border-radius:10px;font-size:.85rem;max-width:160px;">
                    <div class="form-hint">0 = tidak ada boost. 0.5 = dorong naik signifikan. Tidak ternormalisasi.</div>
                </div>

                {{-- Featured until --}}
                <div class="mb-3">
                    <label class="form-label-sm">Featured Until <span style="color:#94a3b8;font-weight:400;">(auto +0.5 boost)</span></label>
                    <input type="datetime-local" name="featured_until"
                           value="{{ old('featured_until', $product->featured_until?->format('Y-m-d\TH:i')) }}"
                           class="form-control" style="border-radius:10px;font-size:.85rem;">
                    <div class="form-hint">Selama tanggal ini belum lewat, otomatis mendapat boost +0.5 ke final score.</div>
                </div>

                <button type="submit" class="btn btn-sm btn-primary" style="border-radius:10px;font-size:.78rem;font-weight:700;">
                    <i class="bi bi-check-lg me-1"></i>Simpan Override
                </button>
            </form>
        </div>

        {{-- Recalculate now --}}
        <div style="background:#f8fafc;border:1.5px solid #e8ecf0;border-radius:12px;padding:1rem 1.25rem;margin-top:1rem;">
            <div style="font-size:.75rem;font-weight:700;color:#64748b;margin-bottom:.5rem;">
                <i class="bi bi-arrow-repeat me-1"></i>Ranking diperbarui otomatis setiap jam
            </div>
            <form method="POST" action="{{ route('admin.catalog.products.rank-now') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.75rem;"
                        onclick="return confirm('Jalankan ulang ranking semua produk sekarang?')">
                    <i class="bi bi-play-fill me-1"></i>Hitung Ulang Sekarang
                </button>
            </form>
        </div>
    </div>

    {{-- Kanan: Rank info + debug --}}
    <div class="col-lg-6">
        <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:14px;padding:1.5rem;">
            <div style="font-size:.78rem;font-weight:800;color:#0f172a;margin-bottom:1rem;">
                <i class="bi bi-speedometer2 me-1"></i>Rank Info
            </div>

            @if($product->rank_position)
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div style="background:#f8fafc;border-radius:10px;padding:.75rem 1rem;">
                        <div style="font-size:.62rem;font-weight:800;text-transform:uppercase;color:#94a3b8;">Posisi</div>
                        <div style="font-size:1.6rem;font-weight:900;color:#0f172a;">#{{ $product->rank_position }}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div style="background:#f8fafc;border-radius:10px;padding:.75rem 1rem;">
                        <div style="font-size:.62rem;font-weight:800;text-transform:uppercase;color:#94a3b8;">Final Score</div>
                        <div style="font-size:1.6rem;font-weight:900;color:#0f172a;">{{ number_format($product->rank_score, 4) }}</div>
                    </div>
                </div>
            </div>
            <div style="font-size:.68rem;color:#94a3b8;margin-bottom:.75rem;">
                Terakhir dihitung: {{ $product->rank_updated_at?->diffForHumans() ?? '—' }}
            </div>

            @if($product->rank_debug)
            {{-- Debug breakdown table --}}
            <div style="font-size:.72rem;font-weight:800;color:#64748b;margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.04em;">
                Breakdown Komponen
            </div>
            @php $d = $product->rank_debug; @endphp
            <table style="width:100%;font-size:.75rem;border-collapse:collapse;">
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:.3rem .5rem;color:#64748b;">Views (30d)</td>
                    <td style="padding:.3rem .5rem;text-align:right;font-weight:700;">{{ $d['views'] ?? '—' }}</td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:.3rem .5rem;color:#64748b;">Add-to-cart (30d)</td>
                    <td style="padding:.3rem .5rem;text-align:right;font-weight:700;">{{ $d['carts'] ?? '—' }}</td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:.3rem .5rem;color:#64748b;">Orders (30d / 7d)</td>
                    <td style="padding:.3rem .5rem;text-align:right;font-weight:700;">{{ $d['orders_30d'] ?? 0 }} / {{ $d['orders_7d'] ?? 0 }}</td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9;background:#fffbeb;">
                    <td style="padding:.3rem .5rem;color:#64748b;">CVR score (×0.35)</td>
                    <td style="padding:.3rem .5rem;text-align:right;font-weight:700;color:#f59e0b;">{{ $d['cvr_score'] ?? '—' }}</td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9;background:#fffbeb;">
                    <td style="padding:.3rem .5rem;color:#64748b;">Trending score (×0.35)</td>
                    <td style="padding:.3rem .5rem;text-align:right;font-weight:700;color:#f59e0b;">{{ $d['trend_score'] ?? '—' }}</td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9;background:#fffbeb;">
                    <td style="padding:.3rem .5rem;color:#64748b;">Engagement score (×0.15)</td>
                    <td style="padding:.3rem .5rem;text-align:right;font-weight:700;color:#f59e0b;">{{ $d['eng_score'] ?? '—' }}</td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:.3rem .5rem;color:#64748b;">New boost (×0.10)</td>
                    <td style="padding:.3rem .5rem;text-align:right;font-weight:700;">{{ $d['new_boost'] ?? '—' }}</td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:.3rem .5rem;color:#64748b;">Stock score (×0.05)</td>
                    <td style="padding:.3rem .5rem;text-align:right;font-weight:700;">{{ $d['stock_score'] ?? '—' }}</td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:.3rem .5rem;color:#64748b;">Featured boost (+additive)</td>
                    <td style="padding:.3rem .5rem;text-align:right;font-weight:700;">{{ $d['featured_boost'] ?? 0 }}</td>
                </tr>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:.3rem .5rem;color:#64748b;">Manual boost (+additive)</td>
                    <td style="padding:.3rem .5rem;text-align:right;font-weight:700;">{{ $d['manual_boost'] ?? 0 }}</td>
                </tr>
                <tr style="background:#f0fdf4;">
                    <td style="padding:.3rem .5rem;font-weight:800;color:#15803d;">Final Score</td>
                    <td style="padding:.3rem .5rem;text-align:right;font-weight:900;color:#15803d;">{{ $d['final_score'] ?? '—' }}</td>
                </tr>
            </table>
            @endif

            @else
            <div style="text-align:center;padding:2rem;color:#94a3b8;font-size:.8rem;">
                <i class="bi bi-bar-chart-line" style="font-size:1.5rem;display:block;margin-bottom:.5rem;opacity:.4;"></i>
                Ranking belum dihitung.<br>
                Klik "Hitung Ulang Sekarang" atau tunggu scheduler berjalan.
            </div>
            @endif
        </div>
    </div>

</div>
</div>
{{-- ── End TAB 4 ──────────────────────────────────────────────────────────── --}}

@endsection

@push('scripts')
<script>
// ── Tab switching ─────────────────────────────────────────────────────────────
const tabBtns  = document.querySelectorAll('.tab-btn');
const tabPanes = document.querySelectorAll('.tab-pane');

function switchTab(name) {
    tabBtns.forEach(b => b.classList.toggle('active', b.dataset.tab === name));
    tabPanes.forEach(p => p.classList.toggle('active', p.id === 'tab-' + name));
    location.hash = name;
}

tabBtns.forEach(b => b.addEventListener('click', () => switchTab(b.dataset.tab)));

// Restore tab from hash
const hash = location.hash.replace('#', '');
if (['info', 'variants', 'sizes', 'mapping', 'ranking'].includes(hash)) switchTab(hash);

// Redirect to correct tab after flash
@if(session('success'))
    const msg = '{{ session('success') }}';
    if (msg.includes('ariant')) switchTab('variants');
    else if (msg.includes('kuran')) switchTab('sizes');
    else if (msg.includes('Mapping')) switchTab('mapping');
    else if (msg.includes('anking') || msg.includes('Override') || msg.includes('dihitung')) switchTab('ranking');
@endif

// ── Color picker sync ─────────────────────────────────────────────────────────
function syncColor(picker, text) {
    picker.addEventListener('input', () => text.value = picker.value);
    text.addEventListener('input', () => {
        if (/^#[0-9A-Fa-f]{6}$/.test(text.value)) picker.value = text.value;
    });
}
syncColor(document.getElementById('addHexPicker'), document.getElementById('addHexText'));
syncColor(document.getElementById('editHexPicker'), document.getElementById('editHexText'));

// sync hidden hex input name on add form
document.getElementById('addHexPicker').addEventListener('input', function() {
    document.getElementById('addHexText').value = this.value;
});

// ── Image preview ─────────────────────────────────────────────────────────────
function previewImg(input, wrapperId) {
    const wrapper = document.getElementById(wrapperId);
    const img = wrapper.querySelector('img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; wrapper.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

// ── Item internal autocomplete ───────────────────────────────────────────────
document.querySelectorAll('[data-item-search]').forEach(function(wrap) {
    const input = wrap.querySelector('[data-item-input]');
    const hidden = wrap.querySelector('[data-item-id]');
    const inlineResults = wrap.querySelector('[data-item-results]');
    const results = document.createElement('div');
    results.className = 'item-suggest item-suggest-floating';
    document.body.appendChild(results);
    if (inlineResults) inlineResults.remove();
    let timer = null;

    function positionResults() {
        const rect = input.getBoundingClientRect();
        results.style.left = rect.left + 'px';
        results.style.top = (rect.bottom + 4) + 'px';
        results.style.width = rect.width + 'px';
    }

    function closeResults() {
        results.classList.remove('open');
        results.innerHTML = '';
    }

    input.addEventListener('input', function() {
        hidden.value = '';
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) {
            closeResults();
            return;
        }

        timer = setTimeout(function() {
            positionResults();
            fetch('{{ route('admin.catalog.products.items.suggest') }}?limit=8&q=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json' }
            })
                .then(function(res) { return res.json(); })
                .then(function(json) {
                    const items = json.data || [];
                    results.innerHTML = items.length
                        ? items.map(function(item) {
                            return '<button type="button" data-id="' + item.id + '" data-stock="' + Number(item.on_hand || 0) + '" data-label="' + escapeHtml((item.code || '') + ' — ' + (item.name || '')) + '">' +
                                '<span class="item-code">' + escapeHtml(item.code || '-') + '</span>' +
                                '<span class="item-name">' + escapeHtml(item.name || '') + ' · Stok tersedia: ' + Number(item.on_hand || 0) + '</span>' +
                            '</button>';
                        }).join('')
                        : '<button type="button" disabled><span class="item-name">Item tidak ditemukan</span></button>';
                    results.classList.add('open');
                });
        }, 220);
    });

    results.addEventListener('click', function(e) {
        const btn = e.target.closest('button[data-id]');
        if (!btn) return;
        hidden.value = btn.dataset.id;
        input.value = btn.dataset.label;
        const stockOutput = wrap.closest('tr')?.querySelector('[data-stock-output]');
        if (stockOutput) {
            stockOutput.textContent = Number(btn.dataset.stock || 0) + ' pcs tersedia';
            stockOutput.style.color = '#64748b';
        }
        closeResults();
    });

    document.addEventListener('click', function(e) {
        if (!wrap.contains(e.target) && !results.contains(e.target)) closeResults();
    });
    window.addEventListener('scroll', closeResults, true);
    window.addEventListener('resize', closeResults);
});

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, function(ch) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
    });
}

// ── Variant modal helpers ─────────────────────────────────────────────────────
function openAddVariant() {
    const modal = document.getElementById('addVariantModal');
    if (!modal) return;
    modal.style.display = 'flex';
    document.getElementById('addColorName')?.focus();
}

function closeVariantModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.style.display = 'none';
}

// ── Edit Variant modal ────────────────────────────────────────────────────────
function openEditVariant(data) {
    const baseUrl = '{{ route('admin.catalog.products.variants.update', [$product, '__ID__']) }}';
    document.getElementById('editVariantForm').action = baseUrl.replace('__ID__', data.id);
    document.getElementById('editColorName').value    = data.color_name;
    document.getElementById('editHexPicker').value    = data.hex_color || '#000000';
    document.getElementById('editHexText').value      = data.hex_color || '#000000';
    document.getElementById('editPriceOverride').value = data.price_override || '';
    document.getElementById('editIsDefault').checked  = !!data.is_default;
    document.getElementById('editIsActive').checked   = !!data.is_active;
    document.getElementById('editImageUrl').value     = '';
    // show current image
    const curImg = document.getElementById('editCurrentImg');
    if (data.image_src) {
        document.getElementById('editCurrentImgEl').src = data.image_src;
        curImg.style.display = 'block';
    } else {
        curImg.style.display = 'none';
    }
    document.getElementById('editImgPreview').style.display = 'none';
    document.getElementById('editVariantModal').style.display = 'flex';
    document.getElementById('editColorName')?.focus();
}

// ── Edit Size modal ───────────────────────────────────────────────────────────
function openEditSize(data) {
    const baseUrl = '{{ route('admin.catalog.products.sizes.update', [$product, '__ID__']) }}';
    document.getElementById('editSizeForm').action    = baseUrl.replace('__ID__', data.id);
    document.getElementById('editSizeLabel').value    = data.size_label;
    document.getElementById('editSizePrice').value    = data.price_override || '';
    document.getElementById('editSizeActive').checked = !!data.is_active;
    document.getElementById('editSizeModal').style.display = 'flex';
}

// Close modals on backdrop click
['addVariantModal', 'editVariantModal', 'editSizeModal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) closeVariantModal(id);
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    ['addVariantModal', 'editVariantModal', 'editSizeModal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal?.style.display === 'flex') closeVariantModal(id);
    });
});
</script>
@endpush
