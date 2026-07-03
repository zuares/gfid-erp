@extends('layouts.app')
@section('title', 'Pengaturan Website')

@push('head')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<style>
/* ── Modal crop foto ── */
.ws-crop-overlay { display:none;position:fixed;inset:0;z-index:1050;background:rgba(15,23,42,.72);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:1.5rem; }
.ws-crop-overlay.open { display:flex; }
.ws-crop-card { background:#fff;border-radius:16px;overflow:hidden;width:min(680px,100%);box-shadow:0 24px 80px rgba(0,0,0,.4); }
.ws-crop-head { display:flex;align-items:center;justify-content:space-between;padding:.8rem 1.1rem;border-bottom:1.5px solid #e8ecf0;font-size:.8rem;font-weight:800; }
.ws-crop-body { background:#0f172a;max-height:62vh;display:flex;justify-content:center; }
.ws-crop-body img { max-width:100%;max-height:62vh;display:block; }
.ws-crop-foot { display:flex;justify-content:flex-end;gap:.5rem;padding:.8rem 1.1rem; }
</style>
<style>
/* ── Layout ── */
.ws-wrap { display:flex;flex-direction:column;gap:1.25rem; }

/* ── Panel kartu ── */
.ws-panel { background:#fff;border:1.5px solid #e8ecf0;border-radius:14px;overflow:hidden; }
.ws-panel-head { padding:.75rem 1.25rem;background:#f8fafc;border-bottom:1.5px solid #e8ecf0;display:flex;align-items:center;gap:.5rem; }
.ws-panel-title { font-size:.78rem;font-weight:800;color:#0f172a;letter-spacing:.04em;text-transform:uppercase; }
.ws-panel-icon { font-size:1rem; }
.ws-panel-body { padding:1.25rem; }

/* ── Form grid ── */
.ws-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:.9rem; }
.ws-grid-full { grid-column:1/-1; }

/* ── Field ── */
.ws-field { display:flex;flex-direction:column;gap:.35rem; }
.ws-label { font-size:.68rem;font-weight:700;color:#64748b;letter-spacing:.04em;text-transform:uppercase; }
.ws-input,
.ws-textarea {
    width:100%;
    padding:.5rem .75rem;
    font-size:.82rem;
    border:1.5px solid #e2e8f0;
    border-radius:8px;
    background:#fff;
    color:#0f172a;
    transition:border-color .15s;
    box-sizing:border-box;
    font-family:inherit;
}
.ws-input:focus,
.ws-textarea:focus { outline:none;border-color:#6366f1; }
.ws-textarea { resize:vertical;min-height:68px; }

/* ── Color picker ── */
.ws-color-row { display:flex;align-items:center;gap:.6rem; }
.ws-color-swatch { width:36px;height:36px;border-radius:8px;border:1.5px solid #e2e8f0;cursor:pointer;flex-shrink:0;padding:0; }
.ws-color-hex { flex:1;font-size:.78rem;font-family:monospace; }

/* ── Preview swatch strip ── */
.ws-swatch-strip { display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.5rem; }
.ws-swatch { width:28px;height:28px;border-radius:6px;border:1.5px solid #e2e8f0; }

/* ── Save bar ── */
.ws-save-bar { position:sticky;bottom:1rem;background:#0f172a;color:#fff;border-radius:12px;padding:.65rem 1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-top:.5rem;box-shadow:0 8px 24px rgba(0,0,0,.18); }
.ws-save-hint { font-size:.72rem;color:#94a3b8; }
.ws-save-btn { background:#E8FF00;color:#0a0a0a;font-size:.78rem;font-weight:900;border:none;padding:.5rem 1.25rem;border-radius:8px;cursor:pointer;transition:opacity .15s; }
.ws-save-btn:hover { opacity:.85; }

/* ── Alert ── */
.ws-alert { border-radius:10px;padding:.65rem 1rem;font-size:.78rem;font-weight:600;margin-bottom:1rem; }
.ws-alert-success { background:#dcfce7;color:#166534; }
.ws-alert-error   { background:#fee2e2;color:#991b1b; }

/* ── Tabs ── */
.ws-tabs { display:flex;gap:.35rem;flex-wrap:wrap;padding:0 0 1rem 0; }
.ws-tab { font-size:.72rem;font-weight:700;padding:.35rem .85rem;border-radius:20px;cursor:pointer;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;transition:all .15s; }
.ws-tab.active { background:#0f172a;color:#fff;border-color:#0f172a; }

/* ── Sections ── */
.ws-section { display:none; }
.ws-section.active { display:block; }

/* ── Image upload widget ── */
.ws-img-wrap { display:flex;flex-direction:column;gap:.5rem; }
.ws-img-preview { position:relative;width:100%;aspect-ratio:4/3;border-radius:10px;border:2px dashed #e2e8f0;overflow:hidden;background:#f8fafc;display:flex;align-items:center;justify-content:center; }
.ws-img-preview.is-portrait { aspect-ratio:3/4; }
.ws-img-preview img { width:100%;height:100%;object-fit:cover; }
.ws-img-preview .ws-img-ph { font-size:2rem;opacity:.35; }
.ws-img-overlay { position:absolute;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;gap:.5rem;opacity:0;transition:opacity .15s; }
.ws-img-preview:hover .ws-img-overlay { opacity:1; }
.ws-img-action-btn { background:#fff;color:#0f172a;border:none;border-radius:8px;padding:.35rem .75rem;font-size:.72rem;font-weight:800;cursor:pointer;font-family:inherit; }
.ws-img-action-btn.danger { background:#fee2e2;color:#b91c1c; }
.ws-img-progress { font-size:.7rem;font-weight:700;color:#6366f1;padding:.2rem 0; }
.ws-img-hint { font-size:.63rem;color:#94a3b8; }
/* small logo variant */
.ws-img-preview.logo-size { aspect-ratio:unset;height:72px;width:auto;max-width:180px;border-radius:8px; }

/* ── Slider (range) ── */
.ws-range-row { display:flex;align-items:center;gap:.75rem; }
.ws-range { flex:1;accent-color:#6366f1;height:22px;cursor:pointer; }
.ws-range-val { min-width:52px;text-align:center;font-size:.78rem;font-weight:800;color:#4f46e5;background:#eef2ff;border-radius:8px;padding:.25rem .4rem; }

/* ── Color picker tanpa teks hex — hasil dilihat di live preview ── */
.ws-color-row .ws-color-hex { display:none; }
.ws-color-row .ws-color-swatch { flex:1;width:100%;height:40px;border-radius:10px; }

/* ── Tile tambah foto hero ── */
.ws-add-photo { aspect-ratio:3/4;border:2px dashed #c7d2fe;border-radius:10px;background:#f8faff;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.4rem;cursor:pointer;color:#6366f1;font-weight:800;font-size:.78rem;transition:all .15s; }
.ws-add-photo:hover { background:#eef2ff;border-color:#6366f1; }
.ws-add-photo .plus { font-size:1.6rem;line-height:1; }

/* ── Focal point dot (geser pada foto) ── */
.ws-focus-dot { position:absolute;width:22px;height:22px;border-radius:50%;border:2.5px solid #fff;background:rgba(99,102,241,.85);box-shadow:0 0 0 2px rgba(99,102,241,.5), 0 2px 8px rgba(0,0,0,.35);transform:translate(-50%,-50%);cursor:grab;touch-action:none;z-index:3; }
.ws-focus-dot:active { cursor:grabbing; }
.ws-focus-dot::after { content:'';position:absolute;inset:6px;border-radius:50%;background:#fff; }
.ws-focus-mode { display:grid;grid-template-columns:1fr 1fr;gap:.35rem;margin-bottom:.4rem; }
.ws-focus-mode button { height:30px;border:1.5px solid #e2e8f0;background:#fff;border-radius:8px;font-size:.68rem;font-weight:800;color:#64748b;cursor:pointer;font-family:inherit; }
.ws-focus-mode button.active { background:#0f172a;border-color:#0f172a;color:#fff; }
.ws-focus-controls { display:grid;gap:.35rem;margin-top:.45rem; }
.ws-focus-control { display:grid;grid-template-columns:68px minmax(0,1fr) 38px;align-items:center;gap:.45rem;font-size:.64rem;font-weight:800;color:#64748b; }
.ws-focus-control input { width:100%;accent-color:#0f172a; }
.ws-focus-control output { text-align:right;color:#94a3b8;font-variant-numeric:tabular-nums; }

/* ── Sortable section items ── */
.ws-sortable-list { display:flex;flex-direction:column;gap:.65rem; }
.ws-sortable-item { display:grid;grid-template-columns:1fr;gap:.65rem;padding:.85rem 1rem;border:1.5px solid #e2e8f0;border-radius:12px;background:#fff;cursor:default;user-select:none;transition:border-color .15s,box-shadow .15s; }
.ws-sortable-item.dragging { opacity:.5; }
.ws-sortable-item.drag-over { border-color:#6366f1;box-shadow:0 0 0 2px #e0e7ff; }
.ws-sec-row { display:flex;align-items:center;gap:.85rem;min-width:0; }
.ws-drag-handle { width:30px;height:30px;border-radius:8px;background:#f8fafc;border:1px solid #eef2f7;display:grid;place-items:center;font-size:1rem;color:#cbd5e1;flex-shrink:0;cursor:grab; }
.ws-sec-meta { flex:1;min-width:0; }
.ws-sec-name { font-size:.82rem;font-weight:800;color:#0f172a; }
.ws-sec-desc { font-size:.68rem;color:#94a3b8;margin-top:.1rem; }
.ws-sec-icon { font-size:1.1rem;flex-shrink:0; }
.ws-sec-action { height:32px;padding:0 .78rem;border-radius:9px;border:1.5px solid #e2e8f0;background:#fff;color:#0f172a;text-decoration:none;font-size:.68rem;font-weight:900;display:inline-flex;align-items:center;gap:.35rem;white-space:nowrap;font-family:inherit;cursor:pointer; }
.ws-sec-action:hover { background:#0f172a;color:#fff;border-color:#0f172a; }
.ws-sec-details { border-top:1px solid #f1f5f9;padding-top:.55rem; }
.ws-sec-details summary { list-style:none;display:inline-flex;align-items:center;gap:.35rem;font-size:.68rem;font-weight:900;color:#64748b;cursor:pointer; }
.ws-sec-details summary::-webkit-details-marker { display:none; }
.ws-sec-details summary::after { content:'↓';font-size:.7rem;color:#94a3b8;transition:transform .15s; }
.ws-sec-details[open] summary::after { transform:rotate(180deg); }
.ws-sec-style { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem;margin-top:.75rem;padding:.8rem;background:#f8fafc;border:1px solid #eef2f7;border-radius:12px; }
.ws-mini-field { display:flex;flex-direction:column;gap:.28rem; }
.ws-mini-field label { font-size:.6rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8; }
.ws-mini-field input[type="range"] { width:100%;accent-color:#0f172a; }
.ws-mini-field input[type="color"] { width:100%;height:32px;border:1.5px solid #e2e8f0;border-radius:8px;padding:0;background:#fff; }
.ws-mini-field select { height:34px;border:1.5px solid #e2e8f0;border-radius:8px;padding:0 .55rem;font-size:.72rem;font-weight:800;color:#0f172a;background:#fff;font-family:inherit; }
.ws-mini-val { font-size:.62rem;font-weight:800;color:#64748b; }
@media(max-width:640px){ .ws-sec-style { grid-template-columns:1fr; } }
/* toggle switch */
.ws-toggle { position:relative;flex-shrink:0; }
.ws-toggle input { opacity:0;width:0;height:0;position:absolute; }
.ws-toggle-track { display:block;width:36px;height:20px;border-radius:999px;background:#e2e8f0;cursor:pointer;transition:background .2s; }
.ws-toggle input:checked + .ws-toggle-track { background:#6366f1; }
.ws-toggle-track::after { content:'';position:absolute;top:3px;left:3px;width:14px;height:14px;border-radius:50%;background:#fff;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2); }
.ws-toggle input:checked + .ws-toggle-track::after { transform:translateX(16px); }

/* ── Halaman ini butuh lebar penuh (form + live preview berdampingan) ── */
.app-main .page-wrap { max-width: 1920px !important; }

/* ── Sidebar navigasi + pencarian ── */
.ws-main { display:grid;grid-template-columns:minmax(0,1fr);gap:1.25rem;align-items:start; }
@media(min-width:992px){ .ws-main { grid-template-columns:215px minmax(0,1fr); } }
.ws-side { display:none; }
@media(min-width:992px){ .ws-side { display:block;position:sticky;top:1rem; } }
@media(min-width:992px){ .ws-tabs { display:none; } }
.ws-search { width:100%;padding:.55rem .8rem .55rem 2.1rem;font-size:.78rem;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='13' height='13' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.3-4.3'/%3E%3C/svg%3E") no-repeat .7rem center;margin-bottom:.75rem; }
.ws-search:focus { outline:none;border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12); }
.ws-side-item { display:flex;align-items:center;gap:.6rem;width:100%;text-align:left;padding:.55rem .7rem;border:none;background:transparent;border-radius:10px;cursor:pointer;transition:all .15s;margin-bottom:2px; }
.ws-side-item:hover { background:#eef2ff; }
.ws-side-item.active { background:#0f172a; }
.ws-side-item.active .ws-side-label { color:#fff; }
.ws-side-item.active .ws-side-desc { color:#94a3b8; }
.ws-side-ic { font-size:1rem;flex-shrink:0;width:22px;text-align:center; }
.ws-side-label { font-size:.78rem;font-weight:800;color:#0f172a;line-height:1.2; }
.ws-side-desc { font-size:.62rem;color:#94a3b8;line-height:1.25;margin-top:1px; }
.ws-side-group { margin:.9rem .7rem .35rem;font-size:.58rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:#94a3b8; }
.ws-side-group:first-child { margin-top:.1rem; }
.ws-mobile-group { flex-basis:100%;margin:.55rem 0 .1rem;font-size:.6rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;color:#94a3b8; }
/* mode pencarian: semua section tampil, field non-match disembunyikan */
.ws-searching .ws-section { display:block !important; }
.ws-searching .ws-field.ws-hide, .ws-searching .ws-sortable-item.ws-hide { display:none !important; }
.ws-searching .ws-panel.ws-hide { display:none !important; }
/* dirty indicator */
.ws-dirty-badge { display:none;font-size:.7rem;font-weight:800;color:#fbbf24;letter-spacing:.02em; }
.ws-save-bar.is-dirty .ws-dirty-badge { display:inline; }
.ws-save-bar.is-dirty .ws-save-btn { animation:ws-pulse 1.6s ease-in-out infinite; }
@keyframes ws-pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.035)} }

/* ── Layout 2 kolom: form + live preview ── */
.ws-layout { display:grid;grid-template-columns:minmax(0,1fr);gap:1.25rem;align-items:start; }
@media(min-width:1200px){
    .ws-layout { grid-template-columns:minmax(0,1fr) 480px; }
}
@media(min-width:1560px){
    .ws-layout { grid-template-columns:minmax(0,1fr) minmax(620px, 42%); }
}
.ws-preview { position:sticky;top:1rem;display:none; }
@media(min-width:1200px){ .ws-preview { display:block; } }
.ws-preview-card { background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(15,23,42,.06); }
.ws-preview-head { display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.6rem .9rem;background:#0f172a; }
.ws-preview-title { font-size:.7rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8; }
.ws-preview-actions { display:flex;align-items:center;gap:.35rem; }
.ws-preview-save { height:28px;border:none;border-radius:8px;background:#E8FF00;color:#0a0a0a;font-size:.68rem;font-weight:900;padding:0 .72rem;cursor:pointer;box-shadow:0 4px 14px rgba(232,255,0,.28); }
.ws-preview-save:hover { opacity:.86; }
.ws-dev-btn { font-size:.68rem;font-weight:700;padding:.28rem .7rem;border-radius:8px;border:1px solid #334155;background:transparent;color:#94a3b8;cursor:pointer;transition:all .15s; }
.ws-dev-btn.active { background:#E8FF00;border-color:#E8FF00;color:#0a0a0a; }
.ws-dev-btn:hover:not(.active) { color:#e2e8f0; }
.ws-preview-body { background:#f1f5f9;display:block;position:relative;overflow:hidden;padding:0; }
.ws-preview-body.is-mobile { display:flex;justify-content:center;padding:14px;overflow:visible; }
#ws-preview-frame { border:none;background:#fff;display:block; }
.ws-preview-body.is-mobile #ws-preview-frame { width:390px !important;height:calc(100vh - 148px) !important;transform:none !important;border-radius:18px;box-shadow:0 10px 40px rgba(0,0,0,.15); }
.ws-preview-scale-note { position:absolute;bottom:8px;right:10px;z-index:2;font-size:.62rem;font-weight:700;color:#64748b;background:rgba(255,255,255,.85);border-radius:6px;padding:2px 8px;pointer-events:none; }
.ws-preview-body.is-mobile .ws-preview-scale-note { display:none; }

/* ── Kenyamanan layout ── */
.ws-wrap { max-width:920px; }
.ws-grid { grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem; }
.ws-panel-body { padding:1.4rem 1.5rem; }
.ws-input, .ws-textarea { padding:.6rem .85rem;font-size:.84rem;border-radius:10px; }
.ws-label { margin-bottom:.1rem; }
.ws-section.active { animation:ws-fade .18s ease-out; }
@keyframes ws-fade { from { opacity:0;transform:translateY(4px); } to { opacity:1;transform:none; } }
.ws-top-save { display:inline-flex;align-items:center;gap:.4rem;background:#E8FF00;color:#0a0a0a;font-size:.78rem;font-weight:900;border:none;padding:.5rem 1.1rem;border-radius:10px;cursor:pointer;box-shadow:0 4px 14px rgba(232,255,0,.35);transition:opacity .15s; }
.ws-top-save:hover { opacity:.85; }
.ws-top-save .ws-top-dot { display:none;width:8px;height:8px;border-radius:50%;background:#dc2626; }
.ws-top-save.is-dirty .ws-top-dot { display:inline-block; }

/* ── Modern polish ── */
.ws-panel { border-radius:16px;box-shadow:0 1px 3px rgba(15,23,42,.04);transition:box-shadow .2s; }
.ws-panel:hover { box-shadow:0 4px 16px rgba(15,23,42,.06); }
.ws-input:focus, .ws-textarea:focus { box-shadow:0 0 0 3px rgba(99,102,241,.12); }
.ws-tabs { position:sticky;top:0;z-index:20;background:rgba(248,250,252,.92);backdrop-filter:blur(8px);padding:.6rem 0;margin:0 0 .4rem;border-radius:12px; }
.ws-tab { transition:all .18s; }
.ws-tab:hover:not(.active) { border-color:#c7d2fe;color:#4f46e5;background:#eef2ff; }
.ws-save-btn { box-shadow:0 4px 14px rgba(232,255,0,.35); }

/* ── Responsive ── */
@media(max-width:640px){
    .ws-grid { grid-template-columns:1fr; }
    .ws-save-bar { flex-direction:column;align-items:stretch; }
}
</style>
@endpush

@section('content')
@php $s = $settings; @endphp

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0" style="font-size:.72rem">
                <li class="breadcrumb-item"><a href="{{ route('admin.catalog.products.index') }}">Admin</a></li>
                <li class="breadcrumb-item active">Pengaturan Website</li>
            </ol>
        </nav>
        <h4 class="mb-0 mt-1 fw-bold" style="font-size:1.15rem">Pengaturan Website</h4>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ url('/') }}" target="_blank" rel="noopener"
           style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem .9rem;border-radius:10px;border:1.5px solid #e2e8f0;font-size:.75rem;font-weight:700;color:#0f172a;text-decoration:none;background:#fff;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            Lihat di Website
        </a>
        <button type="submit" form="ws-settings-form" class="ws-top-save" id="ws-top-save">
            <span class="ws-top-dot"></span> 💾 Simpan
        </button>
    </div>
</div>

@if(session('success'))
<div class="ws-alert ws-alert-success">✅ {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="ws-alert ws-alert-error">❌ {{ session('error') }}</div>
@endif

<div class="ws-layout">
<div class="ws-main">

@php
    $wsNavGroups = [
        'Global' => [
            ['branding', '🏷️', 'Branding',  'Nama, logo, WhatsApp'],
            ['colors',   '🎨', 'Warna',     'Palet warna website'],
            ['footer',   '🔻', 'Footer',    'Kontak & copyright'],
        ],
        'Landing Page' => [
            ['hero',     '🦸', 'Hero',      'Konten, foto, gaya'],
            ['sections', '📐', 'Susunan',   'Urutan & tampil/sembunyi'],
            ['categories','🏷️', 'Kategori', 'Judul & teks koleksi'],
            ['values',   '✨', 'Values',    'Keunggulan brand'],
            ['channels', '🛒', 'Channels',  'Shopee, Tokopedia, TikTok'],
        ],
        'Produk' => [
            ['products', '👕', 'Produk',    'Katalog, kartu, halaman detail'],
        ],
        'Checkout' => [
            ['checkout', '💳', 'Pembayaran','Rekening, QRIS, ongkir'],
        ],
    ];
@endphp

{{-- ── SIDEBAR NAVIGASI (desktop) ── --}}
<aside class="ws-side">
    <input type="text" class="ws-search" id="ws-search" placeholder="Cari pengaturan…" autocomplete="off">
    <nav>
        @php $wsFirstNav = true; @endphp
        @foreach($wsNavGroups as $wsGroupLabel => $wsGroupItems)
        <div class="ws-side-group">{{ $wsGroupLabel }}</div>
        @foreach($wsGroupItems as [$navKey, $navIc, $navLabel, $navDesc])
        <button type="button" class="ws-side-item {{ $wsFirstNav ? 'active' : '' }}" data-tab="{{ $navKey }}"
                onclick="wsShowTab('{{ $navKey }}', this)">
            <span class="ws-side-ic">{{ $navIc }}</span>
            <span>
                <span class="ws-side-label d-block">{{ $navLabel }}</span>
                <span class="ws-side-desc d-block">{{ $navDesc }}</span>
            </span>
        </button>
        @php $wsFirstNav = false; @endphp
        @endforeach
        @endforeach
    </nav>
</aside>

<form method="POST" action="{{ route('admin.website.settings.update') }}" id="ws-settings-form">
@csrf
@method('POST')

<div class="ws-wrap">

    {{-- ── TABS (mobile) ── --}}
    <div class="ws-tabs">
        @php $wsFirstTab = true; @endphp
        @foreach($wsNavGroups as $wsGroupLabel => $wsGroupItems)
        <div class="ws-mobile-group">{{ $wsGroupLabel }}</div>
        @foreach($wsGroupItems as [$navKey, $navIc, $navLabel])
        <button type="button" class="ws-tab {{ $wsFirstTab ? 'active' : '' }}" data-tab="{{ $navKey }}" onclick="wsShowTab('{{ $navKey }}',this)">{{ $navIc }} {{ $navLabel }}</button>
        @php $wsFirstTab = false; @endphp
        @endforeach
        @endforeach
    </div>

    {{-- ─────────────────────────────── BRANDING ──────────────────────────── --}}
    <div class="ws-section active" id="tab-branding">
        <div class="ws-panel">
            <div class="ws-panel-head">
                <span class="ws-panel-icon">🏷️</span>
                <span class="ws-panel-title">Branding</span>
            </div>
            <div class="ws-panel-body">
                <div class="ws-grid">

                    <div class="ws-field">
                        <label class="ws-label">Nama Brand</label>
                        <input type="text" name="branding.brand_name" class="ws-input"
                            value="{{ $s['branding.brand_name'] ?? 'Greatfit' }}"
                            placeholder="Greatfit">
                    </div>

                    <div class="ws-field">
                        <label class="ws-label">Tagline</label>
                        <input type="text" name="branding.tagline" class="ws-input"
                            value="{{ $s['branding.tagline'] ?? '' }}"
                            placeholder="Hal kecil yang bikin hari terasa lebih nyaman.">
                    </div>

                    <div class="ws-field ws-grid-full">
                        <label class="ws-label">Logo</label>
                        @php $logoVal = $s['branding.logo_url'] ?? '/images/logo-mark.svg'; @endphp
                        <div class="ws-img-wrap" data-upload-key="branding.logo_url">
                            <div class="ws-img-preview logo-size" style="display:inline-flex;">
                                @if($logoVal)
                                    <img src="{{ str_starts_with($logoVal,'http') ? $logoVal : asset(ltrim($logoVal,'/')) }}"
                                         alt="Logo" id="preview-branding.logo_url">
                                @else
                                    <span class="ws-img-ph" id="preview-branding.logo_url">🖼️</span>
                                @endif
                                <div class="ws-img-overlay">
                                    <button type="button" class="ws-img-action-btn" onclick="wsPickImg('branding.logo_url')">Ganti</button>
                                    <button type="button" class="ws-img-action-btn danger" onclick="wsClearImg('branding.logo_url')">Hapus</button>
                                </div>
                            </div>
                            <input type="hidden" name="branding.logo_url" id="val-branding.logo_url" value="{{ $logoVal }}">
                            <input type="file" accept="image/*" id="file-branding.logo_url" style="display:none"
                                   onchange="wsUploadImg('branding.logo_url', this)">
                            <div class="ws-img-progress" id="prog-branding.logo_url" style="display:none">Mengupload...</div>
                            <span class="ws-img-hint">JPG/PNG/WebP/SVG · maks 8 MB · atau isi URL manual di bawah</span>
                            <input type="text" class="ws-input" style="margin-top:.25rem;font-size:.75rem;"
                                   placeholder="/images/logo-mark.svg"
                                   value="{{ $logoVal }}"
                                   oninput="wsSetImgVal('branding.logo_url', this.value)">
                        </div>
                    </div>

                    <div class="ws-field">
                        <label class="ws-label">No. WhatsApp</label>
                        <input type="text" name="branding.whatsapp_number" class="ws-input"
                            value="{{ $s['branding.whatsapp_number'] ?? '' }}"
                            placeholder="628123456789">
                        <span style="font-size:.65rem;color:#94a3b8">Format internasional tanpa + (misal 6281234567890)</span>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ─────────────────────────────── COLORS ─────────────────────────────── --}}
    <div class="ws-section" id="tab-colors">
        <div class="ws-panel">
            <div class="ws-panel-head">
                <span class="ws-panel-icon">🎨</span>
                <span class="ws-panel-title">Warna</span>
            </div>
            <div class="ws-panel-body">
                {{-- Preview strip --}}
                <div class="ws-swatch-strip" id="swatchStrip">
                    <div class="ws-swatch" id="swatch-ink"    style="background:{{ $s['colors.ink']    ?? '#0a0a0a' }}" title="Ink"></div>
                    <div class="ws-swatch" id="swatch-accent" style="background:{{ $s['colors.accent'] ?? '#E8FF00' }}" title="Accent"></div>
                    <div class="ws-swatch" id="swatch-mid"    style="background:{{ $s['colors.mid']    ?? '#888888' }}" title="Mid"></div>
                    <div class="ws-swatch" id="swatch-soft"   style="background:{{ $s['colors.soft']   ?? '#f4f4f4' }}" title="Soft"></div>
                    <div class="ws-swatch" id="swatch-line"   style="background:{{ $s['colors.line']   ?? '#e8e8e8' }}" title="Line"></div>
                </div>

                <div class="ws-grid" style="margin-top:1rem">

                    @foreach([
                        ['ink',    'Ink (teks utama)',       '#0a0a0a'],
                        ['accent', 'Accent (highlight)',     '#E8FF00'],
                        ['mid',    'Mid (teks sekunder)',    '#888888'],
                        ['soft',   'Soft (background card)', '#f4f4f4'],
                        ['line',   'Line (border)',          '#e8e8e8'],
                    ] as [$key, $label, $default])
                    @php $val = $s["colors.{$key}"] ?? $default; @endphp
                    <div class="ws-field">
                        <label class="ws-label">{{ $label }}</label>
                        <div class="ws-color-row">
                            <input type="color"
                                id="color-{{ $key }}"
                                class="ws-color-swatch"
                                value="{{ $val }}"
                                oninput="syncColor('{{ $key }}',this.value)">
                            <input type="text"
                                name="colors.{{ $key }}"
                                id="hex-{{ $key }}"
                                class="ws-input ws-color-hex"
                                value="{{ $val }}"
                                maxlength="7"
                                oninput="syncColorFromHex('{{ $key }}',this.value)">
                        </div>
                    </div>
                    @endforeach

                </div>
            </div>
        </div>
    </div>

    {{-- ─────────────────────────────── HERO ───────────────────────────────── --}}
    <div class="ws-section" id="tab-hero">
        <div class="ws-panel">
            <div class="ws-panel-head">
                <span class="ws-panel-icon">🦸</span>
                <span class="ws-panel-title">Hero Section</span>
            </div>
            <div class="ws-panel-body">

                {{-- 1️⃣ KONTEN ─────────────────────────────────────────── --}}
                <p style="font-size:.7rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .75rem">1 · Konten</p>
                <div class="ws-grid" style="margin-bottom:1.5rem">
                    <div class="ws-field">
                        <label class="ws-label">Label Kecil</label>
                        <input type="text" name="hero.label" class="ws-input"
                            value="{{ $s['hero.label'] ?? 'Koleksi Terbaru' }}"
                            placeholder="Koleksi Terbaru">
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">Badge / Pill Teks</label>
                        <input type="text" name="hero.badge_text" class="ws-input"
                            value="{{ $s['hero.badge_text'] ?? '' }}"
                            placeholder="⚡ Pengiriman Hari Ini">
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">Judul Baris 1</label>
                        <input type="text" name="hero.title_line1" class="ws-input"
                            value="{{ $s['hero.title_line1'] ?? 'Good Fit,' }}"
                            placeholder="Good Fit,">
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">Judul Baris 2</label>
                        <input type="text" name="hero.title_line2" class="ws-input"
                            value="{{ $s['hero.title_line2'] ?? 'Good Feel.' }}"
                            placeholder="Good Feel.">
                    </div>
                    <div class="ws-field ws-grid-full">
                        <label class="ws-label">Deskripsi Hero</label>
                        <textarea name="hero.copy" class="ws-textarea"
                            placeholder="Hal kecil yang bikin hari terasa lebih nyaman.">{{ $s['hero.copy'] ?? '' }}</textarea>
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">CTA Utama – Label</label>
                        <input type="text" name="hero.cta_primary_label" class="ws-input"
                            value="{{ $s['hero.cta_primary_label'] ?? 'Lihat Koleksi' }}"
                            placeholder="Lihat Koleksi">
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">CTA Utama – URL</label>
                        <input type="text" name="hero.cta_primary_url" class="ws-input"
                            value="{{ $s['hero.cta_primary_url'] ?? '/products' }}"
                            placeholder="/products">
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">CTA Sekunder – Label</label>
                        <input type="text" name="hero.cta_secondary_label" class="ws-input"
                            value="{{ $s['hero.cta_secondary_label'] ?? 'Cara Order' }}"
                            placeholder="Cara Order">
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">CTA Sekunder – URL</label>
                        <input type="text" name="hero.cta_secondary_url" class="ws-input"
                            value="{{ $s['hero.cta_secondary_url'] ?? '#cara-order' }}"
                            placeholder="#cara-order">
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">Kartu Hero – Judul</label>
                        <input type="text" name="hero.card_title" class="ws-input"
                            value="{{ $s['hero.card_title'] ?? 'Greatfit Club' }}"
                            placeholder="Greatfit Club">
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">Kartu Hero – Subjudul</label>
                        <input type="text" name="hero.card_subtitle" class="ws-input"
                            value="{{ $s['hero.card_subtitle'] ?? '10rb+ pelanggan puas' }}"
                            placeholder="10rb+ pelanggan puas">
                    </div>
                </div>

                <hr style="border:none;border-top:1.5px solid #f1f5f9;margin-bottom:1.25rem">

                {{-- 2️⃣ FOTO ──────────────────────────────────────────── --}}
                @php
                    $wsHeroPhotos = json_decode($s['hero.images'] ?? '', true);
                    if (!is_array($wsHeroPhotos)) {
                        $wsHeroPhotos = [];
                        // fallback legacy: satu foto lama
                        if (!empty($s['hero.image_1'])) {
                            $wsHeroPhotos[] = ['url' => $s['hero.image_1'], 'focus' => $s['hero.image_1_focus'] ?? null];
                        }
                    }
                @endphp
                <p style="font-size:.7rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .35rem">2 · Foto</p>
                <p style="font-size:.68rem;color:#94a3b8;margin:0 0 .75rem">
                    Satu foto = statis · Lebih dari satu = slideshow otomatis · 🎯 Geser titik fokus pada foto · ✂ Crop untuk memotong
                </p>
                <input type="hidden" name="hero.images" id="ws-hero-images" value="{{ json_encode($wsHeroPhotos) }}">
                <input type="file" id="ws-hero-photo-file" accept="image/*" style="display:none">
                <div class="ws-grid" id="ws-hero-photo-list" style="margin-bottom:1.5rem"></div>

                <hr style="border:none;border-top:1.5px solid #f1f5f9;margin-bottom:1.25rem">

                {{-- 3️⃣ Gaya hero ──────────────────────────────────────── --}}
                <p style="font-size:.7rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .75rem">3 · Gaya Tampilan</p>
                <div class="ws-grid" style="margin-bottom:1.5rem">
                    <div class="ws-field">
                        <label class="ws-label">Gaya Hero</label>
                        @php $heroStyle = $s['hero.style'] ?? 'split'; @endphp
                        <select name="hero.style" class="ws-input">
                            <option value="split" {{ $heroStyle !== 'gradient' ? 'selected' : '' }}>Split — teks kiri, panel foto kanan</option>
                            <option value="gradient" {{ $heroStyle === 'gradient' ? 'selected' : '' }}>Gradasi — foto full, melebur ke warna latar</option>
                        </select>
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">Warna Gradasi</label>
                        @php $ovColor = $s['hero.overlay_color'] ?? '#ffffff'; @endphp
                        <div class="ws-color-row">
                            <input type="color" class="ws-color-swatch" value="{{ $ovColor }}" data-hero-overlay-color
                                   oninput="const t=document.getElementById('hex-hero-overlay');t.value=this.value;t.dispatchEvent(new Event('input',{bubbles:true}))">
                            <input type="text" name="hero.overlay_color" id="hex-hero-overlay"
                                   class="ws-input ws-color-hex" value="{{ $ovColor }}" maxlength="7">
                        </div>
                        <span style="font-size:.65rem;color:#94a3b8">Hanya berlaku untuk gaya Gradasi. Default putih.</span>
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">Lebar Gradasi</label>
                        <div class="ws-range-row">
                            <input type="range" name="hero.overlay_strength" class="ws-range"
                                   min="30" max="90" step="5"
                                   value="{{ $s['hero.overlay_strength'] ?? '55' }}"
                                   oninput="this.nextElementSibling.textContent=this.value+'%'">
                            <span class="ws-range-val">{{ $s['hero.overlay_strength'] ?? '55' }}%</span>
                        </div>
                        <span style="font-size:.65rem;color:#94a3b8">Geser & lihat hasilnya di preview.</span>
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">Tinggi Hero</label>
                        <div class="ws-range-row">
                            <input type="range" name="hero.height" class="ws-range"
                                   min="40" max="100" step="5"
                                   value="{{ $s['hero.height'] ?? '100' }}"
                                   oninput="this.nextElementSibling.textContent=this.value+'%'">
                            <span class="ws-range-val">{{ $s['hero.height'] ?? '100' }}%</span>
                        </div>
                        <span style="font-size:.65rem;color:#94a3b8">100% = layar penuh · geser & lihat hasilnya di preview.</span>
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">Fokus Foto</label>
                        <div style="font-size:.75rem;color:#0f172a;background:#eef2ff;border:1px solid #c7d2fe;border-radius:8px;padding:.5rem .7rem;">
                            🎯 <b>Geser titik fokus</b> di atas foto —
                            bagian itu yang dipertahankan saat foto ter-crop.
                        </div>
                    </div>
                </div>

                <hr style="border:none;border-top:1.5px solid #f1f5f9;margin-bottom:1.25rem">

                {{-- Gaya teks & tombol ─────────────────────────────────── --}}
                <p style="font-size:.7rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .75rem">4 · Gaya Teks & Tombol</p>

                {{-- Contoh section — ikut berubah saat pengaturan diubah --}}
                <div id="ws-style-sample" style="position:relative;background:#f6f4f1;border:1.5px solid #e8ecf0;border-radius:14px;padding:1.4rem 1.5rem;margin-bottom:1.25rem;overflow:hidden;">
                    <div id="ss-badge" style="position:absolute;top:14px;right:14px;width:54px;height:54px;border-radius:50%;background:#ffffff;color:#0a0a0a;display:grid;place-items:center;font-size:8px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;text-align:center;line-height:1.4;box-shadow:0 2px 8px rgba(0,0,0,.08);">New<br>2026</div>
                    <div id="ss-label" style="display:flex;align-items:center;gap:7px;font-size:9px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#888888;margin-bottom:10px;"><span id="ss-label-line" style="width:16px;height:2px;background:#888888;display:block;"></span><span id="ss-label-text">KOLEKSI TERBARU</span></div>
                    <div id="ss-title" style="font-size:36px;font-weight:800;line-height:.95;text-transform:uppercase;letter-spacing:-.01em;color:#0a0a0a;margin-bottom:10px;"><span class="ss-title-line">Good Fit,</span><span class="ss-title-line">Good Feel.</span></div>
                    <div id="ss-copy" style="font-size:12px;color:#888888;font-weight:500;line-height:1.6;max-width:320px;margin-bottom:14px;">Hal kecil yang bikin hari terasa lebih nyaman.</div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <span id="ss-cta" style="display:inline-flex;align-items:center;gap:6px;height:36px;padding:0 18px;border-radius:999px;background:#0a0a0a;color:#ffffff;font-size:11px;font-weight:800;">Lihat Koleksi →</span>
                        <span id="ss-cta2" style="display:inline-flex;align-items:center;height:36px;padding:0 18px;border-radius:999px;background:transparent;border:1.5px solid #0a0a0a;color:#0a0a0a;font-size:11px;font-weight:700;">Cara Order</span>
                    </div>
                    <span style="position:absolute;bottom:8px;right:12px;font-size:.6rem;font-weight:700;color:#b6c0cc;">CONTOH</span>
                </div>

                <div class="ws-grid" style="margin-bottom:1.25rem">
                    @php
                        $heroStyleColors = [
                            ['hero.label_color', 'Warna Label Kecil',        '#888888'],
                            ['hero.title_color', 'Warna Judul',              '#0a0a0a'],
                            ['hero.copy_color',  'Warna Deskripsi',          '#888888'],
                            ['hero.badge_bg',    'Badge — Latar',            '#ffffff'],
                            ['hero.badge_color', 'Badge — Teks',             '#0a0a0a'],
                            ['hero.cta_bg',      'CTA Utama — Latar',        '#0a0a0a'],
                            ['hero.cta_color',   'CTA Utama — Teks',         '#ffffff'],
                            ['hero.cta2_color',  'CTA Sekunder — Teks/Garis','#0a0a0a'],
                        ];
                    @endphp
                    @foreach($heroStyleColors as [$hscKey, $hscLabel, $hscDefault])
                    @php
                        $hscVal = $s[$hscKey] ?? $hscDefault;
                        $hscId  = str_replace('.', '-', $hscKey);
                    @endphp
                    <div class="ws-field">
                        <label class="ws-label">{{ $hscLabel }}</label>
                        <div class="ws-color-row">
                            <input type="color" class="ws-color-swatch" value="{{ $hscVal }}"
                                   oninput="const t=document.getElementById('hex-{{ $hscId }}');t.value=this.value;t.dispatchEvent(new Event('input',{bubbles:true}))">
                            <input type="text" name="{{ $hscKey }}" id="hex-{{ $hscId }}"
                                   class="ws-input ws-color-hex" value="{{ $hscVal }}" maxlength="7">
                        </div>
                    </div>
                    @endforeach

                    <div class="ws-field">
                        <label class="ws-label">Ukuran Judul</label>
                        @php $titleSize = $s['hero.title_size'] ?? 'm'; @endphp
                        <select name="hero.title_size" class="ws-input">
                            <option value="xs" {{ $titleSize === 'xs' ? 'selected' : '' }}>Sangat kecil</option>
                            <option value="s" {{ $titleSize === 's' ? 'selected' : '' }}>Kecil</option>
                            <option value="m" {{ !in_array($titleSize, ['xs','s','l','xl'], true) ? 'selected' : '' }}>Normal</option>
                            <option value="l" {{ $titleSize === 'l' ? 'selected' : '' }}>Besar</option>
                            <option value="xl" {{ $titleSize === 'xl' ? 'selected' : '' }}>Sangat besar</option>
                        </select>
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">Gaya Judul</label>
                        @php $titleStyle = $s['hero.title_style'] ?? 'solid'; @endphp
                        <select name="hero.title_style" class="ws-input">
                            <option value="solid" {{ !in_array($titleStyle, ['two_tone_mask', 'promo_poster', 'clean_sans', 'condensed_impact', 'outline_editorial'], true) ? 'selected' : '' }}>Solid — warna judul biasa</option>
                            <option value="two_tone_mask" {{ $titleStyle === 'two_tone_mask' ? 'selected' : '' }}>Two-tone editorial typography with text masking</option>
                            <option value="promo_poster" {{ $titleStyle === 'promo_poster' ? 'selected' : '' }}>Poster Promo — bold rapat seperti campaign</option>
                            <option value="clean_sans" {{ $titleStyle === 'clean_sans' ? 'selected' : '' }}>Clean Sans — modern minimal</option>
                            <option value="condensed_impact" {{ $titleStyle === 'condensed_impact' ? 'selected' : '' }}>Condensed Impact — tinggi dan tegas</option>
                            <option value="outline_editorial" {{ $titleStyle === 'outline_editorial' ? 'selected' : '' }}>Outline Editorial — outline premium</option>
                        </select>
                        <span style="font-size:.65rem;color:#94a3b8">Poster Promo cocok untuk headline seperti iklan katalog singkat.</span>
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">Bentuk Tombol CTA</label>
                        @php $ctaRadius = $s['hero.cta_radius'] ?? 'pill'; @endphp
                        <select name="hero.cta_radius" class="ws-input">
                            <option value="default" {{ !in_array($ctaRadius, ['pill','rounded','square'], true) ? 'selected' : '' }}>Ikuti default — nonaktifkan override</option>
                            <option value="pill"    {{ $ctaRadius === 'pill' ? 'selected' : '' }}>Pill — bulat penuh</option>
                            <option value="rounded" {{ $ctaRadius === 'rounded' ? 'selected' : '' }}>Rounded — sudut membulat</option>
                            <option value="square"  {{ $ctaRadius === 'square' ? 'selected' : '' }}>Kotak — sudut tegas</option>
                        </select>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ─────────────────────────────── KATEGORI ──────────────────────────── --}}
    <div class="ws-section" id="tab-categories">
        <div class="ws-panel">
            <div class="ws-panel-head">
                <span class="ws-panel-icon">🏷️</span>
                <span class="ws-panel-title">Kategori</span>
            </div>
            <div class="ws-panel-body">
                <div style="padding:1rem;background:#f8fafc;border:1.5px solid #e8ecf0;border-radius:12px;margin-bottom:1rem">
                    <div style="font-size:.82rem;font-weight:900;color:#0f172a;margin-bottom:.25rem">Section koleksi di home</div>
                    <div style="font-size:.72rem;color:#64748b;line-height:1.65;max-width:620px">
                        Atur kalimat pendek yang membantu calon pembeli memilih kategori. Daftar kategori produk tetap mengikuti katalog.
                    </div>
                </div>
                <div class="ws-grid">
                    <div class="ws-field">
                        <label class="ws-label">Label kecil</label>
                        <input type="text" name="categories.eyebrow" class="ws-input"
                               value="{{ $s['categories.eyebrow'] ?? 'Koleksi' }}"
                               placeholder="Koleksi">
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">Judul</label>
                        <input type="text" name="categories.title" class="ws-input"
                               value="{{ $s['categories.title'] ?? 'Cari yang paling pas' }}"
                               placeholder="Cari yang paling pas">
                    </div>
                    <div class="ws-field ws-grid-full">
                        <label class="ws-label">Deskripsi singkat</label>
                        <textarea name="categories.copy" class="ws-textarea"
                                  placeholder="Mulai dari kategori yang kamu butuhkan.">{{ $s['categories.copy'] ?? 'Mulai dari kategori yang kamu butuhkan.' }}</textarea>
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">Label tombol semua produk</label>
                        <input type="text" name="categories.all_label" class="ws-input"
                               value="{{ $s['categories.all_label'] ?? 'Lihat semua' }}"
                               placeholder="Lihat semua">
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">Batas kategori tampil</label>
                        @php $catLimit = (int)($s['categories.limit'] ?? 8); @endphp
                        <select name="categories.limit" class="ws-input">
                            @foreach([4,6,8,10,12] as $limit)
                            <option value="{{ $limit }}" {{ $catLimit === $limit ? 'selected' : '' }}>{{ $limit }} kategori</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─────────────────────────────── VALUES ─────────────────────────────── --}}
    <div class="ws-section" id="tab-values">
        <div class="ws-panel">
            <div class="ws-panel-head">
                <span class="ws-panel-icon">✨</span>
                <span class="ws-panel-title">Values (Keunggulan)</span>
            </div>
            <div class="ws-panel-body">
                <div style="display:flex;flex-direction:column;gap:1.25rem">
                @foreach([1,2,3] as $i)
                @php
                    $defNum   = ['01','02','03'][$i-1];
                    $defTitle = ['Nyaman','Presisi','Tahan Lama'][$i-1];
                    $defDesc  = [
                        'Bahan ringan dan breathable yang bikin kamu betah seharian.',
                        'Ukuran konsisten. Cocok di badan, pas di ekspektasi.',
                        'Jahitan kuat, warna awet — menemani aktivitas sehari-hari.',
                    ][$i-1];
                @endphp
                <div style="padding:1rem;background:#f8fafc;border-radius:10px">
                    <p style="font-size:.7rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .75rem">Value {{ $i }}</p>
                    <div class="ws-grid">
                        <div class="ws-field" style="max-width:100px">
                            <label class="ws-label">Angka</label>
                            <input type="text" name="values.{{ $i }}_number" class="ws-input"
                                value="{{ $s["values.{$i}_number"] ?? $defNum }}"
                                placeholder="{{ $defNum }}">
                        </div>
                        <div class="ws-field">
                            <label class="ws-label">Judul</label>
                            <input type="text" name="values.{{ $i }}_title" class="ws-input"
                                value="{{ $s["values.{$i}_title"] ?? $defTitle }}"
                                placeholder="{{ $defTitle }}">
                        </div>
                        <div class="ws-field ws-grid-full">
                            <label class="ws-label">Deskripsi</label>
                            <textarea name="values.{{ $i }}_desc" class="ws-textarea"
                                placeholder="{{ $defDesc }}">{{ $s["values.{$i}_desc"] ?? $defDesc }}</textarea>
                        </div>
                    </div>
                </div>
                @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ─────────────────────────────── CHANNELS ───────────────────────────── --}}
    <div class="ws-section" id="tab-channels">
        <div class="ws-panel">
            <div class="ws-panel-head">
                <span class="ws-panel-icon">🛒</span>
                <span class="ws-panel-title">Channels Penjualan</span>
            </div>
            <div class="ws-panel-body">
                <div class="ws-grid">

                    <div class="ws-field">
                        <label class="ws-label">URL Shopee</label>
                        <input type="text" name="channels.shopee_url" class="ws-input"
                            value="{{ $s['channels.shopee_url'] ?? '' }}"
                            placeholder="https://shopee.co.id/tokomu">
                    </div>

                    <div class="ws-field">
                        <label class="ws-label">URL Tokopedia</label>
                        <input type="text" name="channels.tokopedia_url" class="ws-input"
                            value="{{ $s['channels.tokopedia_url'] ?? '' }}"
                            placeholder="https://www.tokopedia.com/tokomu">
                    </div>

                    <div class="ws-field">
                        <label class="ws-label">URL TikTok Shop</label>
                        <input type="text" name="channels.tiktok_url" class="ws-input"
                            value="{{ $s['channels.tiktok_url'] ?? '' }}"
                            placeholder="https://www.tiktok.com/@tokomu">
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ─────────────────────────────── FOOTER ─────────────────────────────── --}}
    <div class="ws-section" id="tab-footer">
        <div class="ws-panel">
            <div class="ws-panel-head">
                <span class="ws-panel-icon">🔻</span>
                <span class="ws-panel-title">Footer</span>
            </div>
            <div class="ws-panel-body">
                <div class="ws-grid">

                    <div class="ws-field ws-grid-full">
                        <label class="ws-label">Tagline Footer</label>
                        <textarea name="footer.tagline" class="ws-textarea"
                            placeholder="Hal kecil yang bikin hari terasa lebih nyaman, lewat outfit harian Greatfit.">{{ $s['footer.tagline'] ?? '' }}</textarea>
                    </div>

                    <div class="ws-field">
                        <label class="ws-label">Copyright</label>
                        <input type="text" name="footer.copyright" class="ws-input"
                            value="{{ $s['footer.copyright'] ?? '© 2025 Greatfit. All rights reserved.' }}"
                            placeholder="© 2025 Greatfit. All rights reserved.">
                    </div>

                    <div class="ws-field">
                        <label class="ws-label">Made In</label>
                        <input type="text" name="footer.made_in" class="ws-input"
                            value="{{ $s['footer.made_in'] ?? 'Dibuat dengan ❤️ di Indonesia' }}"
                            placeholder="Dibuat dengan ❤️ di Indonesia">
                    </div>

                    <div class="ws-field">
                        <label class="ws-label">URL Instagram</label>
                        <input type="text" name="footer.instagram_url" class="ws-input"
                            value="{{ $s['footer.instagram_url'] ?? '' }}"
                            placeholder="https://instagram.com/tokomu">
                    </div>

                    <div class="ws-field">
                        <label class="ws-label">Email Kontak</label>
                        <input type="email" name="footer.email" class="ws-input"
                            value="{{ $s['footer.email'] ?? '' }}"
                            placeholder="hello@greatfit.id">
                    </div>

                    <div class="ws-field ws-grid-full">
                        <label class="ws-label">Alamat</label>
                        <textarea name="footer.address" class="ws-textarea"
                            placeholder="Jl. Contoh No. 1, Kota, Provinsi">{{ $s['footer.address'] ?? '' }}</textarea>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ─────────────────────────────── PRODUK ────────────────────────────── --}}
    <div class="ws-section" id="tab-products">
        <div class="ws-panel">
            <div class="ws-panel-head">
                <span class="ws-panel-icon">👕</span>
                <span class="ws-panel-title">Halaman Produk</span>
            </div>
            <div class="ws-panel-body">
                <div style="display:grid;gap:1rem">
                    <div style="padding:1rem;background:#f8fafc;border:1.5px solid #e8ecf0;border-radius:12px;">
                        <div style="font-size:.82rem;font-weight:900;color:#0f172a;margin-bottom:.25rem">Katalog produk</div>
                        <div style="font-size:.72rem;color:#64748b;line-height:1.65;max-width:620px">
                            Foto, harga, varian, stok, badge, dan urutan produk dikelola dari katalog.
                            Pengaturan visual umum halaman produk tetap memakai Branding, Warna, dan Footer.
                        </div>
                    </div>

                    <div class="ws-grid">
                        <a href="{{ route('admin.catalog.products.index') }}"
                           style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem;border:1.5px solid #e2e8f0;border-radius:12px;text-decoration:none;color:#0f172a;background:#fff;">
                            <span>
                                <span style="display:block;font-size:.78rem;font-weight:900">Kelola produk katalog</span>
                                <span style="display:block;font-size:.68rem;color:#94a3b8;margin-top:.15rem">Tambah produk, edit varian, mapping item internal</span>
                            </span>
                            <span style="font-size:1.1rem;color:#94a3b8">→</span>
                        </a>
                        <a href="{{ route('storefront.products') }}" target="_blank" rel="noopener"
                           style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem;border:1.5px solid #e2e8f0;border-radius:12px;text-decoration:none;color:#0f172a;background:#fff;">
                            <span>
                                <span style="display:block;font-size:.78rem;font-weight:900">Lihat halaman produk</span>
                                <span style="display:block;font-size:.68rem;color:#94a3b8;margin-top:.15rem">Preview halaman produk di storefront</span>
                            </span>
                            <span style="font-size:1.1rem;color:#94a3b8">↗</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─────────────────────────────── CHECKOUT ───────────────────────────── --}}
    <div class="ws-section" id="tab-checkout">
        <div class="ws-panel">
            <div class="ws-panel-head">
                <span class="ws-panel-icon">💳</span>
                <span class="ws-panel-title">Halaman Checkout — Pembayaran</span>
            </div>
            <div class="ws-panel-body">

                <p style="font-size:.7rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .75rem">Rekening Bank</p>
                <div class="ws-grid" style="margin-bottom:1.5rem">
                    <div class="ws-field ws-grid-full">
                        <label class="ws-label">Nama Pemilik Rekening</label>
                        <input type="text" name="checkout.account_name" class="ws-input"
                            value="{{ $s['checkout.account_name'] ?? 'a.n. Greatfit Indonesia' }}"
                            placeholder="a.n. Greatfit Indonesia">
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">No. Rekening BCA</label>
                        <input type="text" name="checkout.bca_no" class="ws-input"
                            value="{{ $s['checkout.bca_no'] ?? '' }}" placeholder="88600010001">
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">No. Rekening BRI</label>
                        <input type="text" name="checkout.bri_no" class="ws-input"
                            value="{{ $s['checkout.bri_no'] ?? '' }}" placeholder="089001000001303">
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">No. Rekening Mandiri</label>
                        <input type="text" name="checkout.mandiri_no" class="ws-input"
                            value="{{ $s['checkout.mandiri_no'] ?? '' }}" placeholder="15600012345678">
                    </div>
                </div>

                <hr style="border:none;border-top:1.5px solid #f1f5f9;margin-bottom:1.25rem">

                <p style="font-size:.7rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .75rem">QRIS / E-Wallet</p>
                <div class="ws-grid" style="margin-bottom:1.5rem">
                    <div class="ws-field">
                        <label class="ws-label">Gambar QRIS (statis)</label>
                        @php $qrisVal = $s['checkout.qris_image'] ?? ''; @endphp
                        <div class="ws-img-wrap" data-upload-key="checkout.qris_image">
                            <div class="ws-img-preview">
                                @if($qrisVal)
                                    <img src="{{ $qrisVal }}" alt="QRIS" id="preview-checkout.qris_image">
                                @else
                                    <span class="ws-img-ph" id="preview-checkout.qris_image">🔳</span>
                                @endif
                                <div class="ws-img-overlay">
                                    <button type="button" class="ws-img-action-btn" onclick="wsPickImg('checkout.qris_image')">Ganti</button>
                                    <button type="button" class="ws-img-action-btn danger" onclick="wsClearImg('checkout.qris_image')">Hapus</button>
                                </div>
                            </div>
                            <input type="hidden" name="checkout.qris_image" id="val-checkout.qris_image" value="{{ $qrisVal }}">
                            <input type="file" accept="image/*" id="file-checkout.qris_image" style="display:none"
                                   onchange="wsUploadImg('checkout.qris_image', this)">
                            <div class="ws-img-progress" id="prog-checkout.qris_image" style="display:none">Mengupload...</div>
                            <span class="ws-img-hint">Foto/screenshot QRIS merchant. Dipakai untuk semua metode QR. Kosongkan = QR dummy.</span>
                        </div>
                    </div>
                </div>

                <hr style="border:none;border-top:1.5px solid #f1f5f9;margin-bottom:1.25rem">

                <p style="font-size:.7rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .75rem">Metode Pembayaran Aktif</p>
                <div class="ws-sortable-list" style="margin-bottom:1.5rem">
                    @foreach([
                        ['qris',      'QRIS',       'Semua e-wallet via satu QR'],
                        ['gopay',     'GoPay',      'Scan QR GoPay'],
                        ['dana',      'Dana',       'Scan QR Dana'],
                        ['ovo',       'OVO',        'Scan QR OVO'],
                        ['shopeepay', 'ShopeePay',  'Scan QR ShopeePay'],
                        ['bca',       'Transfer BCA',     'Transfer manual'],
                        ['bri',       'Transfer BRI',     'Transfer manual'],
                        ['mandiri',   'Transfer Mandiri', 'Transfer manual'],
                    ] as [$pmKey, $pmName, $pmDesc])
                    <div class="ws-sortable-item" style="cursor:default;">
                        <div class="ws-sec-meta">
                            <div class="ws-sec-name">{{ $pmName }}</div>
                            <div class="ws-sec-desc">{{ $pmDesc }}</div>
                        </div>
                        <label class="ws-toggle" title="Aktif / nonaktif">
                            <input type="hidden"   name="checkout.pay_{{ $pmKey }}" value="0">
                            <input type="checkbox" name="checkout.pay_{{ $pmKey }}" value="1"
                                {{ ($s["checkout.pay_{$pmKey}"] ?? '1') !== '0' ? 'checked' : '' }}>
                            <span class="ws-toggle-track"></span>
                        </label>
                    </div>
                    @endforeach
                </div>

                <hr style="border:none;border-top:1.5px solid #f1f5f9;margin-bottom:1.25rem">

                <p style="font-size:.7rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .75rem">Lainnya</p>
                <div class="ws-grid">
                    <div class="ws-field">
                        <label class="ws-label">Berat per Item — Fallback (kg)</label>
                        <input type="number" step="0.1" min="0.1" name="checkout.weight_per_item" class="ws-input"
                            value="{{ $s['checkout.weight_per_item'] ?? '0.5' }}" placeholder="0.5">
                        <span style="font-size:.65rem;color:#94a3b8">Hanya dipakai jika produk belum diisi "Berat per Pcs" di Catalog Produk Website</span>
                    </div>
                    <div class="ws-field">
                        <label class="ws-label">Teks Jaminan (bawah tombol pesan)</label>
                        <input type="text" name="checkout.secure_notice" class="ws-input"
                            value="{{ $s['checkout.secure_notice'] ?? 'Dikonfirmasi langsung oleh tim Greatfit' }}"
                            placeholder="Dikonfirmasi langsung oleh tim Greatfit">
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ──────────────────────────── SECTIONS ────────────────────────────── --}}
    @php
        $sfSectionDefs = [
            'hero'       => ['icon' => '🦸', 'name' => 'Hero',          'desc' => 'Gambar utama, judul, dan CTA'],
            'categories' => ['icon' => '🏷️', 'name' => 'Kategori',      'desc' => 'Grid pilih kategori produk'],
            'channels'   => ['icon' => '🛒', 'name' => 'Channels',      'desc' => 'Shopee · TikTok · Tokopedia · Website'],
            'values'     => ['icon' => '✨', 'name' => 'Values',         'desc' => 'Nyaman · Presisi · Tahan Lama'],
            'products'   => ['icon' => '👕', 'name' => 'Produk Pilihan', 'desc' => 'Grid produk ranked / featured'],
            'cta'        => ['icon' => '🎯', 'name' => 'Call to Action', 'desc' => 'Blok "Ready to Wear Daily"'],
        ];
        $sfCurrentOrder = array_values(array_unique(array_filter(
            array_map('trim', explode(',', $s['sections.order'] ?? 'hero,categories,channels,values,products,cta'))
        )));
        // Tambahkan section yang tidak ada di order (fallback)
        foreach (array_keys($sfSectionDefs) as $k) {
            if (!in_array($k, $sfCurrentOrder)) $sfCurrentOrder[] = $k;
        }
    @endphp
    <div class="ws-section" id="tab-sections">
        <div class="ws-panel">
            <div class="ws-panel-head">
                <span class="ws-panel-icon">📐</span>
                <span class="ws-panel-title">Urutan & Visibilitas Section</span>
            </div>
            <div class="ws-panel-body">
                <p style="font-size:.72rem;color:#64748b;margin-bottom:1rem">
                    Drag untuk mengubah urutan tampilan di halaman home. Toggle untuk sembunyikan section.
                </p>

                <div class="ws-sortable-list" id="ws-sortable">
                    @foreach($sfCurrentOrder as $sfSecKey)
                    @php $sfSecMeta = $sfSectionDefs[$sfSecKey] ?? ['icon'=>'📄','name'=>$sfSecKey,'desc'=>'']; @endphp
                    <div class="ws-sortable-item" draggable="true" data-key="{{ $sfSecKey }}">
                        <div class="ws-sec-row">
                            <span class="ws-drag-handle">⠿</span>
                            <span class="ws-sec-icon">{{ $sfSecMeta['icon'] }}</span>
                            <div class="ws-sec-meta">
                                <div class="ws-sec-name">{{ $sfSecMeta['name'] }}</div>
                                <div class="ws-sec-desc">{{ $sfSecMeta['desc'] }}</div>
                            </div>
                            @php
                                $sfSecActionTab = [
                                    'hero' => 'hero',
                                    'categories' => 'categories',
                                    'channels' => 'channels',
                                    'values' => 'values',
                                    'products' => 'products',
                                ][$sfSecKey] ?? 'sections';
                            @endphp
                            <button type="button" class="ws-sec-action" data-section-action="{{ $sfSecKey }}" data-target-tab="{{ $sfSecActionTab }}">Atur</button>
                            <label class="ws-toggle" title="Tampilkan / sembunyikan">
                                <input type="hidden"   name="sections_{{ $sfSecKey }}_visible" value="0">
                                <input type="checkbox" name="sections_{{ $sfSecKey }}_visible" value="1"
                                    {{ ($s["sections.{$sfSecKey}_visible"] ?? '1') !== '0' ? 'checked' : '' }}>
                                <span class="ws-toggle-track"></span>
                            </label>
                        </div>
                        @if($sfSecKey !== 'hero')
                        @php
                            $secDefaults = [
                                'padding_top' => $s["sections.{$sfSecKey}_padding_top"] ?? '',
                                'padding_bottom' => $s["sections.{$sfSecKey}_padding_bottom"] ?? '',
                                'margin_top' => $s["sections.{$sfSecKey}_margin_top"] ?? '0',
                                'margin_bottom' => $s["sections.{$sfSecKey}_margin_bottom"] ?? '0',
                                'bg' => $s["sections.{$sfSecKey}_bg"] ?? '#ffffff',
                                'style' => $s["sections.{$sfSecKey}_style"] ?? 'default',
                            ];
                        @endphp
                        <details class="ws-sec-details">
                            <summary>Spacing & style</summary>
                            <div class="ws-sec-style" data-section-style="{{ $sfSecKey }}">
                                <div class="ws-mini-field">
                                    <label>Padding atas <span class="ws-mini-val">{{ $secDefaults['padding_top'] !== '' ? $secDefaults['padding_top'] : 'default' }}</span></label>
                                    <input type="range" min="0" max="96" step="2" name="sections.{{ $sfSecKey }}_padding_top"
                                           value="{{ $secDefaults['padding_top'] !== '' ? $secDefaults['padding_top'] : '44' }}">
                                </div>
                                <div class="ws-mini-field">
                                    <label>Padding bawah <span class="ws-mini-val">{{ $secDefaults['padding_bottom'] !== '' ? $secDefaults['padding_bottom'] : 'default' }}</span></label>
                                    <input type="range" min="0" max="96" step="2" name="sections.{{ $sfSecKey }}_padding_bottom"
                                           value="{{ $secDefaults['padding_bottom'] !== '' ? $secDefaults['padding_bottom'] : '44' }}">
                                </div>
                                <div class="ws-mini-field">
                                    <label>Margin atas <span class="ws-mini-val">{{ $secDefaults['margin_top'] }}</span></label>
                                    <input type="range" min="0" max="80" step="2" name="sections.{{ $sfSecKey }}_margin_top"
                                           value="{{ $secDefaults['margin_top'] }}">
                                </div>
                                <div class="ws-mini-field">
                                    <label>Margin bawah <span class="ws-mini-val">{{ $secDefaults['margin_bottom'] }}</span></label>
                                    <input type="range" min="0" max="80" step="2" name="sections.{{ $sfSecKey }}_margin_bottom"
                                           value="{{ $secDefaults['margin_bottom'] }}">
                                </div>
                                <div class="ws-mini-field">
                                    <label>Warna background</label>
                                    <input type="color" name="sections.{{ $sfSecKey }}_bg" value="{{ $secDefaults['bg'] }}">
                                </div>
                                <div class="ws-mini-field">
                                    <label>Style</label>
                                    <select name="sections.{{ $sfSecKey }}_style">
                                        <option value="default" {{ $secDefaults['style'] === 'default' ? 'selected' : '' }}>Default</option>
                                        <option value="soft" {{ $secDefaults['style'] === 'soft' ? 'selected' : '' }}>Soft band</option>
                                        <option value="line" {{ $secDefaults['style'] === 'line' ? 'selected' : '' }}>Border top</option>
                                        <option value="compact" {{ $secDefaults['style'] === 'compact' ? 'selected' : '' }}>Compact</option>
                                        <option value="outline" {{ $secDefaults['style'] === 'outline' ? 'selected' : '' }}>Outline</option>
                                        <option value="elevated" {{ $secDefaults['style'] === 'elevated' ? 'selected' : '' }}>Elevated</option>
                                        <option value="dark" {{ $secDefaults['style'] === 'dark' ? 'selected' : '' }}>Dark band</option>
                                        <option value="editorial" {{ $secDefaults['style'] === 'editorial' ? 'selected' : '' }}>Editorial</option>
                                    </select>
                                </div>
                            </div>
                        </details>
                        @endif
                    </div>
                    @endforeach
                </div>

                {{-- Hidden input yang diupdate JS saat drag --}}
                <input type="hidden" name="sections_order" id="ws-sections-order"
                       value="{{ $s['sections.order'] ?? 'hero,categories,channels,values,products,cta' }}">
            </div>
        </div>
    </div>

    {{-- ── SAVE BAR ── --}}
    <div class="ws-save-bar" id="ws-save-bar">
        <span class="ws-save-hint">
            <span class="ws-dirty-badge">● Ada perubahan belum disimpan&nbsp;&nbsp;</span>
            Teks & warna langsung tampil di preview — Simpan untuk menerapkan permanen
        </span>
        <button type="submit" class="ws-save-btn">💾 Simpan Pengaturan</button>
    </div>

</div>
</form>
</div>{{-- /.ws-main --}}

{{-- ── LIVE PREVIEW ── --}}
<aside class="ws-preview">
    <div class="ws-preview-card">
        <div class="ws-preview-head">
            <span class="ws-preview-title">👁 Live Preview</span>
            <div class="ws-preview-actions">
                <button type="submit" form="ws-settings-form" class="ws-preview-save">Simpan Perubahan</button>
                <button type="button" class="ws-dev-btn" id="ws-dev-desktop" onclick="wsPreviewDevice('desktop')">🖥 Desktop</button>
                <button type="button" class="ws-dev-btn active" id="ws-dev-mobile" onclick="wsPreviewDevice('mobile')">📱 Mobile</button>
                <button type="button" class="ws-dev-btn" onclick="wsPreviewReload()" title="Muat ulang preview">⟳</button>
            </div>
        </div>
        <div class="ws-preview-body is-mobile" id="ws-preview-body" data-mode="mobile">
            <iframe id="ws-preview-frame" src="{{ url('/') }}" loading="lazy" title="Preview website"></iframe>
            <span class="ws-preview-scale-note" id="ws-preview-scale-note"></span>
        </div>
    </div>
</aside>
</div>{{-- /.ws-layout --}}

{{-- ── MODAL CROP FOTO ── --}}
<div class="ws-crop-overlay" id="ws-crop-overlay">
    <div class="ws-crop-card">
        <div class="ws-crop-head">
            <span>✂ Crop Foto</span>
            <button type="button" class="btn-close" onclick="wsCloseCropper()"></button>
        </div>
        <div class="ws-crop-body">
            <img id="ws-crop-img" src="" alt="Crop">
        </div>
        <div class="ws-crop-foot">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="wsCloseCropper()">Batal</button>
            <button type="button" class="btn btn-sm btn-primary" id="ws-crop-apply">✂ Terapkan Crop</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
// ── Live preview: toggle perangkat + reload ──────────────────────────────────
// Desktop dirender pada lebar 1440px lalu di-SCALE agar muat di kolom —
// sehingga yang tampil benar-benar layout desktop, bukan versi mobile yang sempit.
function wsPreviewApply() {
    const body  = document.getElementById('ws-preview-body');
    const frame = document.getElementById('ws-preview-frame');
    const note  = document.getElementById('ws-preview-scale-note');
    if (!body || !frame) return;

    const mode = body.dataset.mode || 'mobile';
    body.classList.toggle('is-mobile', mode === 'mobile');

    if (mode === 'mobile') {
        frame.style.cssText = '';
        body.style.height = '';
        return;
    }

    const target   = 1440; // lebar desktop yang disimulasikan
    const colWidth = body.clientWidth || 480;
    const scale    = colWidth / target;
    const visibleH = Math.max(window.innerHeight - 130, 520);

    frame.style.width  = target + 'px';
    frame.style.height = Math.round(visibleH / scale) + 'px';
    frame.style.transform = 'scale(' + scale + ')';
    frame.style.transformOrigin = 'top left';
    body.style.height = visibleH + 'px';

    if (note) note.textContent = '1440px · ' + Math.round(scale * 100) + '%';
}

function wsPreviewDevice(mode) {
    const body = document.getElementById('ws-preview-body');
    if (!body) return;
    body.dataset.mode = mode;
    document.getElementById('ws-dev-desktop')?.classList.toggle('active', mode === 'desktop');
    document.getElementById('ws-dev-mobile')?.classList.toggle('active', mode === 'mobile');
    wsPreviewApply();
}

window.addEventListener('resize', wsPreviewApply);
document.addEventListener('DOMContentLoaded', wsPreviewApply);

function wsPreviewReload() {
    const frame = document.getElementById('ws-preview-frame');
    if (!frame) return;
    try { frame.contentWindow.location.reload(); }
    catch (e) { frame.src = frame.src; }
}

// ── Tab switching (sinkron pill mobile + sidebar desktop) ────────────────────
const WS_ACTIVE_TAB_KEY = 'gfid.websiteSettings.activeTab';

function wsShowTab(name, btn) {
    if (!document.getElementById('tab-' + name)) return;
    document.querySelectorAll('.ws-section').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.ws-tab, .ws-side-item').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + name)?.classList.add('active');
    document.querySelectorAll('[data-tab="' + name + '"]').forEach(el => el.classList.add('active'));
    try { sessionStorage.setItem(WS_ACTIVE_TAB_KEY, name); } catch (e) {}
    // keluar dari mode pencarian saat pindah tab
    const search = document.getElementById('ws-search');
    if (search && search.value) { search.value = ''; wsApplySearch(''); }
}

document.addEventListener('DOMContentLoaded', function () {
    let savedTab = '';
    try { savedTab = sessionStorage.getItem(WS_ACTIVE_TAB_KEY) || ''; } catch (e) {}
    if (savedTab && document.getElementById('tab-' + savedTab)) {
        wsShowTab(savedTab);
    }
});

// ── Atur section tanpa pindah halaman ───────────────────────────────────────
document.querySelectorAll('[data-section-action]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const targetTab = this.dataset.targetTab || 'sections';
        const item = this.closest('.ws-sortable-item');

        if (targetTab && targetTab !== 'sections' && document.getElementById('tab-' + targetTab)) {
            wsShowTab(targetTab);
            document.getElementById('tab-' + targetTab)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }

        const details = item?.querySelector('.ws-sec-details');
        if (details) {
            details.open = true;
            details.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
});

// ── Pencarian setting: filter field lintas semua tab ─────────────────────────
function wsApplySearch(q) {
    const wrap = document.querySelector('.ws-wrap');
    if (!wrap) return;
    q = (q || '').trim().toLowerCase();

    if (!q) {
        wrap.classList.remove('ws-searching');
        wrap.querySelectorAll('.ws-hide').forEach(el => el.classList.remove('ws-hide'));
        return;
    }

    wrap.classList.add('ws-searching');
    wrap.querySelectorAll('.ws-field, .ws-sortable-item').forEach(el => {
        el.classList.toggle('ws-hide', !el.textContent.toLowerCase().includes(q)
            && !(el.querySelector('input,textarea,select')?.name || '').toLowerCase().includes(q));
    });
    // sembunyikan panel yang seluruh isinya tersembunyi
    wrap.querySelectorAll('.ws-panel').forEach(panel => {
        const items = panel.querySelectorAll('.ws-field, .ws-sortable-item');
        const anyVisible = Array.from(items).some(el => !el.classList.contains('ws-hide'));
        panel.classList.toggle('ws-hide', items.length > 0 && !anyVisible);
    });
}
document.getElementById('ws-search')?.addEventListener('input', function () { wsApplySearch(this.value); });

// ── LIVE EDIT → PREVIEW (tanpa simpan): teks hero, badge, kartu, warna ────────
(function () {
    const frame = document.getElementById('ws-preview-frame');
    if (!frame) return;

    function fdoc() { try { return frame.contentDocument; } catch (e) { return null; } }

    function setText(selector, value) {
        const d = fdoc(); if (!d) return;
        d.querySelectorAll(selector).forEach(el => { el.textContent = value; });
    }

    function setHeroBadge(value) {
        const d = fdoc(); if (!d) return;
        const text = (value || '').trim();
        d.querySelectorAll('.hd-badge, .hm-badge').forEach(function (el) {
            if (text) {
                el.textContent = text;
            } else {
                el.innerHTML = 'New<br>2026';
            }
        });
    }

    function setHeroTitle() {
        const d = fdoc(); if (!d) return;
        const l1 = document.querySelector('[name="hero.title_line1"]')?.value ?? '';
        const l2 = document.querySelector('[name="hero.title_line2"]')?.value ?? '';
        d.querySelectorAll('.hd-title, .hm-title').forEach(el => {
            el.innerHTML = '';
            const line1 = d.createElement('span');
            const line2 = d.createElement('span');
            line1.className = 'hero-title-line';
            line2.className = 'hero-title-line';
            line1.textContent = l1;
            line2.textContent = l2;
            el.append(line1, line2);
        });
    }

    window.wsHeroPreviewPhotoIndex = window.wsHeroPreviewPhotoIndex || 0;

    function collectHeroDraft() {
        const val = n => document.querySelector('[name="' + n + '"]')?.value ?? '';
        let photos = [];
        try { photos = JSON.parse(document.getElementById('ws-hero-images')?.value || '[]') || []; } catch (e) {}
        const activePhotoIndex = Math.min(Math.max(parseInt(window.wsHeroPreviewPhotoIndex || 0, 10) || 0, 0), Math.max(photos.length - 1, 0));
        return {
            label: val('hero.label'),
            title_line1: val('hero.title_line1'),
            title_line2: val('hero.title_line2'),
            copy: val('hero.copy'),
            badge_text: val('hero.badge_text'),
            cta_primary_label: val('hero.cta_primary_label'),
            cta_primary_url: val('hero.cta_primary_url'),
            cta_secondary_label: val('hero.cta_secondary_label'),
            cta_secondary_url: val('hero.cta_secondary_url'),
            card_title: val('hero.card_title'),
            card_subtitle: val('hero.card_subtitle'),
            style: val('hero.style'),
            overlay_color: val('hero.overlay_color'),
            overlay_strength: val('hero.overlay_strength'),
            height: val('hero.height'),
            label_color: val('hero.label_color'),
            title_color: val('hero.title_color'),
            title_size: val('hero.title_size'),
            title_style: val('hero.title_style'),
            copy_color: val('hero.copy_color'),
            badge_bg: val('hero.badge_bg'),
            badge_color: val('hero.badge_color'),
            cta_bg: val('hero.cta_bg'),
            cta_color: val('hero.cta_color'),
            cta2_color: val('hero.cta2_color'),
            cta_radius: val('hero.cta_radius'),
            photo_fit: val('hero.photo_fit'),
            images: photos,
            active_photo_index: activePhotoIndex,
        };
    }

    function postHeroDraft() {
        if (!frame.contentWindow) return;
        frame.contentWindow.postMessage({
            type: 'gfid:hero-preview',
            settings: collectHeroDraft(),
        }, '*');
    }

    const liveText = {
        'hero.label':         '.hd-label, .hm-label',
        'hero.copy':          '.hd-copy, .hm-copy',
        'hero.card_title':    '.hd-card-t, .hm-card-t',
        'hero.card_subtitle': '.hd-card-s, .hm-card-s',
        'hero.cta_primary_label':   null, // tombol berisi svg — dilewati
    };

    document.querySelectorAll('input, textarea').forEach(inp => {
        const name = inp.getAttribute('name') || '';

        if (name === 'hero.title_line1' || name === 'hero.title_line2') {
            inp.addEventListener('input', setHeroTitle);
        } else if (liveText[name]) {
            inp.addEventListener('input', () => setText(liveText[name], inp.value));
        } else if (name.startsWith('colors.')) {
            const varName = '--' + name.replace('colors.', '');
            inp.addEventListener('input', () => {
                const d = fdoc(); if (!d) return;
                if (/^#[0-9a-fA-F]{6}$/.test(inp.value)) {
                    d.documentElement.style.setProperty(varName, inp.value);
                    d.body && d.body.style.setProperty(varName, inp.value);
                }
            });
        }
    });

    // ── Live style: warna/ukuran/bentuk teks & tombol hero ──
    function applyHeroLiveStyle() {
        const d = fdoc(); if (!d) return;
        const val = n => document.querySelector('[name="' + n + '"]')?.value || '';
        const hex = v => /^#[0-9a-fA-F]{6}$/.test(v) ? v : '';

        let css = '';
        const lc = hex(val('hero.label_color'));
        if (lc) css += '.hd-label,.hm-label{color:' + lc + '}.hd-label::before,.hm-label::before{background:' + lc + '}';
        const tc = hex(val('hero.title_color'));
        if (tc) css += '.hd-title,.hm-title{color:' + tc + '}';
        const cc = hex(val('hero.copy_color'));
        if (cc) css += '.hd-copy,.hm-copy{color:' + cc + '}';
        const bb = hex(val('hero.badge_bg')), bc = hex(val('hero.badge_color'));
        if (bb || bc) css += '.hd-badge,.hm-badge{' + (bb ? 'background:' + bb + ';' : '') + (bc ? 'color:' + bc + ';' : '') + '}';
        const pb = hex(val('hero.cta_bg')), pc = hex(val('hero.cta_color'));
        if (pb || pc) css += '.hero-desktop .btn-dk,.hero-mobile .btn-dk{' + (pb ? 'background:' + pb + ';' : '') + (pc ? 'color:' + pc + ';' : '') + '}';
        const sc = hex(val('hero.cta2_color'));
        if (sc) css += '.hero-desktop .btn-sk,.hero-mobile .btn-sk{color:' + sc + ';border-color:' + sc + '}';
        const rad = { pill: '999px', rounded: '12px', square: '6px' }[val('hero.cta_radius')];
        if (rad) css += '.hero-desktop .btn-dk,.hero-mobile .btn-dk,.hero-desktop .btn-sk,.hero-mobile .btn-sk{border-radius:' + rad + '}';
        const tsd = { xs: '60px', s: '72px', m: '96px', l: '112px', xl: '128px' }[val('hero.title_size')] || '96px';
        const tsm = { xs: '44px', s: '54px', m: '70px', l: '82px', xl: '92px' }[val('hero.title_size')] || '70px';
        css += '.hd-title{font-size:' + tsd + '}.hm-title{font-size:' + tsm + '}';
        if (val('hero.title_style') === 'two_tone_mask') {
            const titleColor = hex(val('hero.title_color')) || 'var(--ink)';
            css += '.hero-title-line{display:block}'
                + '.hd-title .hero-title-line:nth-child(2),.hm-title .hero-title-line:nth-child(2){color:transparent;background:linear-gradient(90deg,' + titleColor + ' 0 52%,var(--mid) 52% 100%);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;-webkit-text-stroke:.012em ' + titleColor + ';text-shadow:none}';
        } else if (val('hero.title_style') === 'promo_poster') {
            const titleColor = hex(val('hero.title_color')) || 'var(--ink)';
            const copyColor = hex(val('hero.copy_color')) || 'var(--ink)';
            const promoDesktop = ({ xs: '44px', s: '52px', m: '64px', l: '76px', xl: '88px' }[val('hero.title_size')] || '64px');
            const promoMobile = ({ xs: '30px', s: '36px', m: '44px', l: '52px', xl: '58px' }[val('hero.title_size')] || '44px');
            css += '.hero-title-line{display:block;color:inherit;background:none;-webkit-background-clip:initial;background-clip:initial;-webkit-text-fill-color:currentColor;-webkit-text-stroke:0}'
                + '.hd-title,.hm-title{font-family:var(--font-body);font-weight:900;text-transform:none;letter-spacing:0;line-height:1.16;color:' + titleColor + '}'
                + '.hd-title{font-size:' + promoDesktop + ';max-width:560px;margin-bottom:20px}'
                + '.hm-title{font-size:' + promoMobile + ';max-width:340px;margin-bottom:18px}'
                + '.hd-title::after,.hm-title::after{content:"";display:block;width:min(320px,100%);height:2px;background:currentColor;margin-top:24px}'
                + '.hd-copy{max-width:520px;margin-top:0;font-size:20px;line-height:1.55;color:' + copyColor + '}'
                + '.hm-copy{max-width:320px;margin-top:0;font-size:15px;line-height:1.6;color:' + copyColor + '}'
                + '.hd-actions .btn-dk,.hm-actions .btn-dk{min-height:48px;padding:0 26px;font-size:17px}'
                + '.hd-actions .btn-sk,.hm-actions .btn-sk{min-height:48px;padding:0 22px;font-size:14px}';
        } else if (val('hero.title_style') === 'clean_sans') {
            const titleColor = hex(val('hero.title_color')) || 'var(--ink)';
            css += '.hero-title-line{display:block;color:inherit;background:none;-webkit-background-clip:initial;background-clip:initial;-webkit-text-fill-color:currentColor;-webkit-text-stroke:0}'
                + '.hd-title,.hm-title{font-family:var(--font-body);font-weight:900;text-transform:none;line-height:1.04;letter-spacing:0;color:' + titleColor + '}';
        } else if (val('hero.title_style') === 'condensed_impact') {
            const titleColor = hex(val('hero.title_color')) || 'var(--ink)';
            css += '.hero-title-line{display:block;color:inherit;background:none;-webkit-background-clip:initial;background-clip:initial;-webkit-text-fill-color:currentColor;-webkit-text-stroke:0}'
                + '.hd-title,.hm-title{font-family:var(--font-display);font-weight:900;text-transform:uppercase;line-height:.82;letter-spacing:.01em;color:' + titleColor + '}';
        } else if (val('hero.title_style') === 'outline_editorial') {
            const titleColor = hex(val('hero.title_color')) || 'var(--ink)';
            css += '.hero-title-line{display:block;background:none;-webkit-background-clip:initial;background-clip:initial}'
                + '.hd-title,.hm-title{color:transparent;-webkit-text-fill-color:transparent;-webkit-text-stroke:.018em ' + titleColor + ';text-transform:uppercase;letter-spacing:.01em}'
                + '.hd-title .hero-title-line:nth-child(1),.hm-title .hero-title-line:nth-child(1){color:' + titleColor + ';-webkit-text-fill-color:currentColor;-webkit-text-stroke:0}';
        } else {
            css += '.hero-title-line{display:block;color:inherit;background:none;-webkit-background-clip:initial;background-clip:initial;-webkit-text-fill-color:currentColor;-webkit-text-stroke:0;text-shadow:inherit}';
        }
        const hh = parseInt(val('hero.height') || '100', 10);
        if (hh >= 40 && hh < 100) {
            css += '.hero-desktop{min-height:calc(' + hh + 'svh - 56px)}'
                 + '.hero-mobile.hero-grad{min-height:' + Math.round(hh * 0.76) + 'svh !important}';
        }

        let st = d.getElementById('ws-live-hero-style');
        if (!st) { st = d.createElement('style'); st.id = 'ws-live-hero-style'; d.head.appendChild(st); }
        st.textContent = css;
    }

    ['hero.label_color', 'hero.title_color', 'hero.copy_color',
     'hero.badge_bg', 'hero.badge_color',
     'hero.cta_bg', 'hero.cta_color', 'hero.cta2_color',
     'hero.title_size', 'hero.title_style', 'hero.cta_radius', 'hero.height'].forEach(function (n) {
        document.querySelectorAll('[name="' + n + '"]').forEach(function (inp) {
            inp.addEventListener('input', applyHeroLiveStyle);
            inp.addEventListener('change', applyHeroLiveStyle);
        });
    });

    function applyHeroMediaLive() {
        const d = fdoc(); if (!d) return;
        const fit = document.querySelector('[name="hero.photo_fit"]')?.value === 'contain' ? 'contain' : 'cover';
        let st = d.getElementById('ws-live-fit-style');
        if (!st) { st = d.createElement('style'); st.id = 'ws-live-fit-style'; d.head.appendChild(st); }
        st.textContent = '.hero-desktop .hd-photo,.hero-mobile .hero-bg{object-fit:' + fit + '}';
    }

    // ── CONTOH SECTION "Gaya Teks & Tombol" — ikut berubah realtime ──
    function updateStyleSample() {
        const val = n => document.querySelector('[name="' + n + '"]')?.value ?? '';
        const hex = v => /^#[0-9a-fA-F]{6}$/.test(v) ? v : '';
        const el  = id => document.getElementById(id);
        if (!el('ws-style-sample')) return;

        // Teks (ikut isi field konten hero)
        const l1 = val('hero.title_line1') || 'Good Fit,';
        const l2 = val('hero.title_line2') || 'Good Feel.';
        el('ss-title').innerHTML = '';
        const ssLine1 = document.createElement('span');
        const ssLine2 = document.createElement('span');
        ssLine1.className = 'ss-title-line';
        ssLine2.className = 'ss-title-line';
        ssLine1.textContent = l1;
        ssLine2.textContent = l2;
        ssLine1.style.display = 'block';
        ssLine2.style.display = 'block';
        el('ss-title').append(ssLine1, ssLine2);
        el('ss-label-text').textContent = (val('hero.label') || 'Koleksi Terbaru').toUpperCase();
        el('ss-copy').textContent  = val('hero.copy') || 'Hal kecil yang bikin hari terasa lebih nyaman.';
        el('ss-badge').innerHTML   = (val('hero.badge_text') || 'New<br>2026').replace(/\n/g, '<br>');
        el('ss-cta').textContent   = (val('hero.cta_primary_label') || 'Lihat Koleksi') + ' →';
        el('ss-cta2').textContent  = val('hero.cta_secondary_label') || 'Cara Order';

        // Warna
        const lc = hex(val('hero.label_color')) || '#888888';
        el('ss-label-text').style.color = lc;
        el('ss-label').style.color = lc;
        el('ss-label-line').style.background = lc;
        el('ss-title').style.color = hex(val('hero.title_color')) || '#0a0a0a';
        if (val('hero.title_style') === 'two_tone_mask') {
            ssLine2.style.color = 'transparent';
            ssLine2.style.background = 'linear-gradient(90deg,' + (hex(val('hero.title_color')) || '#0a0a0a') + ' 0 52%,#888888 52% 100%)';
            ssLine2.style.webkitBackgroundClip = 'text';
            ssLine2.style.backgroundClip = 'text';
            ssLine2.style.webkitTextFillColor = 'transparent';
            ssLine2.style.webkitTextStroke = '.012em ' + (hex(val('hero.title_color')) || '#0a0a0a');
            el('ss-title').style.fontFamily = '';
            el('ss-title').style.textTransform = 'uppercase';
            el('ss-title').style.lineHeight = '.95';
        } else if (val('hero.title_style') === 'promo_poster') {
            el('ss-title').style.fontFamily = 'var(--font-body, system-ui, sans-serif)';
            el('ss-title').style.textTransform = 'none';
            el('ss-title').style.lineHeight = '1.16';
            el('ss-title').style.fontWeight = '900';
            ssLine2.style.color = 'inherit';
            ssLine2.style.background = 'none';
            ssLine2.style.webkitBackgroundClip = 'initial';
            ssLine2.style.backgroundClip = 'initial';
            ssLine2.style.webkitTextFillColor = 'currentColor';
            ssLine2.style.webkitTextStroke = '0';
        } else if (val('hero.title_style') === 'clean_sans') {
            el('ss-title').style.fontFamily = 'var(--font-body, system-ui, sans-serif)';
            el('ss-title').style.textTransform = 'none';
            el('ss-title').style.lineHeight = '1.04';
            el('ss-title').style.fontWeight = '900';
            ssLine2.style.color = 'inherit';
            ssLine2.style.background = 'none';
            ssLine2.style.webkitBackgroundClip = 'initial';
            ssLine2.style.backgroundClip = 'initial';
            ssLine2.style.webkitTextFillColor = 'currentColor';
            ssLine2.style.webkitTextStroke = '0';
        } else if (val('hero.title_style') === 'condensed_impact') {
            el('ss-title').style.fontFamily = '';
            el('ss-title').style.textTransform = 'uppercase';
            el('ss-title').style.lineHeight = '.82';
            el('ss-title').style.fontWeight = '900';
            ssLine2.style.color = 'inherit';
            ssLine2.style.background = 'none';
            ssLine2.style.webkitBackgroundClip = 'initial';
            ssLine2.style.backgroundClip = 'initial';
            ssLine2.style.webkitTextFillColor = 'currentColor';
            ssLine2.style.webkitTextStroke = '0';
        } else if (val('hero.title_style') === 'outline_editorial') {
            el('ss-title').style.fontFamily = '';
            el('ss-title').style.textTransform = 'uppercase';
            el('ss-title').style.lineHeight = '.92';
            el('ss-title').style.fontWeight = '900';
            ssLine1.style.color = hex(val('hero.title_color')) || '#0a0a0a';
            ssLine1.style.webkitTextFillColor = 'currentColor';
            ssLine1.style.webkitTextStroke = '0';
            ssLine2.style.color = 'transparent';
            ssLine2.style.background = 'none';
            ssLine2.style.webkitBackgroundClip = 'initial';
            ssLine2.style.backgroundClip = 'initial';
            ssLine2.style.webkitTextFillColor = 'transparent';
            ssLine2.style.webkitTextStroke = '.018em ' + (hex(val('hero.title_color')) || '#0a0a0a');
        } else {
            el('ss-title').style.fontFamily = '';
            el('ss-title').style.textTransform = 'uppercase';
            el('ss-title').style.lineHeight = '.95';
            ssLine2.style.color = 'inherit';
            ssLine2.style.background = 'none';
            ssLine2.style.webkitBackgroundClip = 'initial';
            ssLine2.style.backgroundClip = 'initial';
            ssLine2.style.webkitTextFillColor = 'currentColor';
            ssLine2.style.webkitTextStroke = '0';
        }
        el('ss-copy').style.color  = hex(val('hero.copy_color')) || '#888888';
        el('ss-badge').style.background = hex(val('hero.badge_bg')) || '#ffffff';
        el('ss-badge').style.color     = hex(val('hero.badge_color')) || '#0a0a0a';
        el('ss-cta').style.background  = hex(val('hero.cta_bg')) || '#0a0a0a';
        el('ss-cta').style.color       = hex(val('hero.cta_color')) || '#ffffff';
        const c2 = hex(val('hero.cta2_color')) || '#0a0a0a';
        el('ss-cta2').style.color = c2;
        el('ss-cta2').style.borderColor = c2;

        // Ukuran judul & bentuk tombol
        el('ss-title').style.fontSize = val('hero.title_style') === 'promo_poster'
            ? ({ xs: '22px', s: '26px', m: '34px', l: '40px', xl: '46px' }[val('hero.title_size')] || '34px')
            : ({ xs: '24px', s: '28px', m: '36px', l: '44px', xl: '50px' }[val('hero.title_size')] || '36px');
        const rad = ({ pill: '999px', rounded: '10px', square: '5px' }[val('hero.cta_radius')]);
        el('ss-cta').style.borderRadius = rad || '';
        el('ss-cta2').style.borderRadius = rad || '';
    }

    ['hero.label', 'hero.title_line1', 'hero.title_line2', 'hero.copy', 'hero.badge_text',
     'hero.cta_primary_label', 'hero.cta_secondary_label',
     'hero.label_color', 'hero.title_color', 'hero.copy_color',
     'hero.badge_bg', 'hero.badge_color',
     'hero.cta_bg', 'hero.cta_color', 'hero.cta2_color',
     'hero.title_size', 'hero.title_style', 'hero.cta_radius'].forEach(function (n) {
        document.querySelectorAll('[name="' + n + '"]').forEach(function (inp) {
            inp.addEventListener('input', updateStyleSample);
            inp.addEventListener('change', updateStyleSample);
        });
    });
    document.addEventListener('DOMContentLoaded', updateStyleSample);
    updateStyleSample();

    // ── LIVE: gaya hero split ↔ gradasi + warna/lebar gradasi ──
    function applyHeroLayoutLive() {
        const d = fdoc(); if (!d) return;
        const val = n => document.querySelector('[name="' + n + '"]')?.value || '';
        const hexOr = (v, f) => /^#[0-9a-fA-F]{6}$/.test(v) ? v : f;
        const isGrad = val('hero.style') === 'gradient';

        d.querySelectorAll('.hero-desktop, .hero-mobile').forEach(el => el.classList.toggle('hero-grad', isGrad));

        let st = d.getElementById('ws-live-grad-style');
        if (!st) { st = d.createElement('style'); st.id = 'ws-live-grad-style'; d.head.appendChild(st); }
        if (!isGrad) { st.textContent = ''; return; }

        const c = hexOr(val('hero.overlay_color'), '#ffffff');
        let s = parseInt(val('hero.overlay_strength') || '55', 10);
        s = Math.min(90, Math.max(30, isNaN(s) ? 55 : s));
        const solid = Math.max(s - 35, 0);

        st.textContent =
            '.hero-desktop.hero-grad{position:relative;grid-template-columns:1fr}'
            + '.hero-desktop.hero-grad .hd-visual{position:absolute;inset:0;background:' + c + '}'
            + '.hero-desktop.hero-grad .hd-photo.active{opacity:1}'
            + '.hero-desktop.hero-grad .hd-visual::after{content:"";position:absolute;inset:0;z-index:1;background:linear-gradient(90deg,' + c + ' ' + solid + '%,' + c + '00 ' + s + '%)}'
            + '.hero-desktop.hero-grad .hd-content{position:relative;z-index:2;max-width:620px}'
            + '.hero-desktop.hero-grad .hd-badge,.hero-desktop.hero-grad .hd-card{z-index:2}'
            + '.hero-desktop.hero-grad .hd-card{left:48%}'
            + '@media (max-width:767.98px){'
            +   '.hero-mobile.hero-grad{position:relative;display:block;min-height:76svh;border-radius:18px;overflow:hidden;background:' + c + '}'
            +   '.hero-mobile.hero-grad .hm-visual{position:absolute;inset:0;height:100%;aspect-ratio:auto;border-radius:0}'
            +   '.hero-mobile.hero-grad .hero-bg.active{opacity:1}'
            +   '.hero-mobile.hero-grad .hm-visual::after{content:"";position:absolute;inset:0;z-index:1;background:linear-gradient(180deg,' + c + '00 32%,' + c + ' 86%)}'
            +   '.hero-mobile.hero-grad .hm-content{position:absolute;left:16px;right:16px;bottom:96px;z-index:2}'
            +   '.hero-mobile.hero-grad .hm-badge,.hero-mobile.hero-grad .hm-card{z-index:2}'
            + '}@media (min-width:768px){.hero-mobile.hero-grad{display:none}}';
    }
    ['hero.style', 'hero.overlay_color', 'hero.overlay_strength'].forEach(function (n) {
        document.querySelectorAll('[name="' + n + '"]').forEach(function (inp) {
            inp.addEventListener('input', applyHeroLayoutLive);
            inp.addEventListener('change', applyHeroLayoutLive);
        });
    });
    document.querySelectorAll('[data-hero-overlay-color]').forEach(function (inp) {
        ['input', 'change'].forEach(function (evt) {
            inp.addEventListener(evt, function () {
                const target = document.querySelector('[name="hero.overlay_color"]');
                if (target) target.value = inp.value;
                const styleSelect = document.querySelector('[name="hero.style"]');
                if (styleSelect && styleSelect.value !== 'gradient') {
                    styleSelect.value = 'gradient';
                }
                applyHeroLayoutLive();
                try { postHeroDraft(); } catch (e) {}
            });
        });
    });

    // ── LIVE: mode foto (cover/contain) ──
    document.querySelectorAll('[name="hero.photo_fit"]').forEach(function (inp) {
        inp.addEventListener('input', applyHeroMediaLive);
        inp.addEventListener('change', applyHeroMediaLive);
    });

    // ── LIVE: foto hero (tambah/hapus/fokus) ──
    window.wsLiveHeroPhotos = function (photos) {
        const d = fdoc(); if (!d) return;
        const activeIndex = Math.min(Math.max(parseInt(window.wsHeroPreviewPhotoIndex || 0, 10) || 0, 0), Math.max(photos.length - 1, 0));
        [['.hd-visual', 'hd-photo'], ['.hm-visual', 'hero-bg']].forEach(function ([contSel, cls]) {
            const cont = d.querySelector(contSel);
            if (!cont) return;
            cont.querySelectorAll('.' + cls).forEach(el => el.remove());
            photos.slice().reverse().forEach(function (p, ri) {
                const sourceIndex = photos.length - 1 - ri;
                const img = d.createElement('img');
                img.className = cls + (sourceIndex === activeIndex ? ' active' : '');
                img.src = p.url;
                const focus = cls === 'hero-bg' ? (p.focus_mobile || p.focus) : (p.focus_desktop || p.focus);
                if (/^\d+% \d+%$/.test(focus || '')) img.style.objectPosition = focus;
                cont.insertBefore(img, cont.firstChild);
            });
        });
    };
    document.querySelectorAll('[name="hero.images"]').forEach(function (inp) {
        inp.addEventListener('input', function () {
            try { window.wsLiveHeroPhotos(JSON.parse(inp.value || '[]') || []); } catch (e) {}
        });
    });

    // ── LIVE: label tombol CTA ──
    function setCtaText(sel, txt) {
        const d = fdoc(); if (!d) return;
        d.querySelectorAll(sel).forEach(function (a) {
            if (a.firstChild && a.firstChild.nodeType === 3) a.firstChild.nodeValue = ' ' + txt + ' ';
            else a.insertBefore(d.createTextNode(' ' + txt + ' '), a.firstChild);
        });
    }
    document.querySelector('[name="hero.cta_primary_label"]')?.addEventListener('input', function () {
        setCtaText('.hero-desktop .btn-dk, .hero-mobile .btn-dk', this.value);
    });
    document.querySelector('[name="hero.cta_secondary_label"]')?.addEventListener('input', function () {
        setCtaText('.hero-desktop .btn-sk, .hero-mobile .btn-sk', this.value);
    });

    // ── LIVE: brand, logo, values, footer tagline ──
    document.querySelector('[name="branding.brand_name"]')?.addEventListener('input', function () {
        const d = fdoc(); if (!d) return;
        d.querySelectorAll('.brand span').forEach(el => el.textContent = this.value);
    });
    document.getElementById('val-branding.logo_url')?.addEventListener('input', function () {
        const d = fdoc(); if (!d) return;
        const src = this.value.startsWith('http') ? this.value : '/' + this.value.replace(/^\/+/, '');
        d.querySelectorAll('.brand img').forEach(el => el.src = src);
    });
    [1, 2, 3].forEach(function (vi) {
        const map = { number: '.val-n', title: '.val-t', desc: '.val-d' };
        Object.entries(map).forEach(function ([field, sel]) {
            document.querySelector('[name="values.' + vi + '_' + field + '"]')?.addEventListener('input', function () {
                const d = fdoc(); if (!d) return;
                const el = d.querySelector('.vals .val:nth-child(' + vi + ') ' + sel);
                if (el) el.textContent = this.value;
            });
        });
    });
    [
        ['categories.eyebrow', '.cat-eyebrow'],
        ['categories.title', '.cat-title'],
        ['categories.copy', '.cat-copy'],
        ['categories.all_label', '.cat-head .sec-a']
    ].forEach(function ([name, selector]) {
        document.querySelector('[name="' + name + '"]')?.addEventListener('input', function () {
            const d = fdoc(); if (!d) return;
            d.querySelectorAll(selector).forEach(el => el.textContent = this.value);
        });
    });
    document.querySelector('[name="footer.tagline"]')?.addEventListener('input', function () {
        const d = fdoc(); if (!d) return;
        d.querySelectorAll('.sf-tagline').forEach(el => el.textContent = this.value);
    });

    // ── LIVE: visibilitas, urutan, spacing & style sections ──
    function applySectionsLive() {
        const d = fdoc(); if (!d) return;
        const sectionVal = (key, suffix, fallback = '') =>
            document.querySelector('[name="sections.' + key + '_' + suffix + '"]')?.value ?? fallback;
        const px = value => {
            const n = parseInt(value, 10);
            return isNaN(n) ? null : Math.max(0, n) + 'px';
        };
        const hex = value => /^#[0-9a-fA-F]{6}$/.test(value || '') ? value : '';

        // visibilitas
        document.querySelectorAll('[name^="sections_"][type="checkbox"]').forEach(function (cb) {
            const key = cb.name.replace('sections_', '').replace('_visible', '');
            if (key === 'hero') {
                // hero = 2 layout + strip berjalan, di luar wrap
                d.querySelectorAll('.hero-desktop, .hero-mobile, .strip').forEach(el => {
                    el.style.display = cb.checked ? '' : 'none';
                });
                return;
            }
            const el = d.querySelector('[data-sf-sec="' + key + '"]');
            if (el) el.style.display = cb.checked ? '' : 'none';
        });

        // spacing, warna, dan style visual
        ['categories', 'channels', 'values', 'products', 'cta'].forEach(function (key) {
            const el = d.querySelector('[data-sf-sec="' + key + '"]');
            if (!el) return;

            el.style.paddingTop = px(sectionVal(key, 'padding_top')) || '';
            el.style.paddingBottom = px(sectionVal(key, 'padding_bottom')) || '';
            el.style.marginTop = px(sectionVal(key, 'margin_top')) || '';
            el.style.marginBottom = px(sectionVal(key, 'margin_bottom')) || '';
            el.style.background = hex(sectionVal(key, 'bg')) || '';
            el.style.borderTop = '';
            el.style.border = '';
            el.style.borderRadius = '';
            el.style.paddingLeft = '';
            el.style.paddingRight = '';
            el.style.boxShadow = '';
            el.style.color = '';
            el.style.borderLeft = '';

            const style = sectionVal(key, 'style', 'default');
            if (style === 'soft') {
                el.style.background = hex(sectionVal(key, 'bg')) || 'var(--soft)';
                el.style.borderRadius = '20px';
                el.style.paddingLeft = '18px';
                el.style.paddingRight = '18px';
            } else if (style === 'line') {
                el.style.borderTop = '1px solid var(--line)';
            } else if (style === 'compact') {
                el.style.paddingTop = '20px';
                el.style.paddingBottom = '20px';
            } else if (style === 'outline') {
                el.style.border = '1px solid var(--line)';
                el.style.borderRadius = '20px';
                el.style.paddingLeft = '18px';
                el.style.paddingRight = '18px';
            } else if (style === 'elevated') {
                el.style.background = hex(sectionVal(key, 'bg')) || '#ffffff';
                el.style.border = '1px solid var(--line)';
                el.style.borderRadius = '20px';
                el.style.paddingLeft = '18px';
                el.style.paddingRight = '18px';
                el.style.boxShadow = '0 14px 36px rgba(15,23,42,.08)';
            } else if (style === 'dark') {
                el.style.background = 'var(--ink)';
                el.style.color = 'var(--white)';
                el.style.borderRadius = '20px';
                el.style.paddingLeft = '18px';
                el.style.paddingRight = '18px';
            } else if (style === 'editorial') {
                el.style.borderLeft = '3px solid var(--ink)';
                el.style.paddingLeft = '22px';
            }
        });

        // urutan (hanya section non-hero di dalam .wrap)
        const order = (document.getElementById('ws-sections-order')?.value || '').split(',');
        let anchor = null;
        order.forEach(function (key) {
            const el = d.querySelector('[data-sf-sec="' + key.trim() + '"]');
            if (!el) return;
            if (anchor) anchor.after(el);
            anchor = el;
        });
    }
    document.querySelectorAll('[name^="sections_"][type="checkbox"]').forEach(cb => cb.addEventListener('change', applySectionsLive));
    document.querySelectorAll('[name^="sections."]').forEach(function (inp) {
        ['input', 'change'].forEach(function (evt) {
            inp.addEventListener(evt, function () {
                const label = inp.closest('.ws-mini-field')?.querySelector('.ws-mini-val');
                if (label && inp.type === 'range') label.textContent = inp.value;
                applySectionsLive();
            });
        });
    });
    document.getElementById('ws-sortable')?.addEventListener('dragend', () => setTimeout(applySectionsLive, 50));

    // ══════════════════════════════════════════════════════════════════
    // APPLY-ALL HERO: jaring pengaman — SEMUA pengaturan hero diterapkan
    // ulang ke preview pada setiap perubahan input hero.* apa pun,
    // dan setiap kali iframe preview selesai dimuat ulang.
    // ══════════════════════════════════════════════════════════════════
    function applyHeroAll() {
        const val = n => document.querySelector('[name="' + n + '"]')?.value ?? '';

        try { setHeroTitle(); } catch (e) {}
        try { setText('.hd-label, .hm-label', val('hero.label')); } catch (e) {}
        try { setText('.hd-copy, .hm-copy', val('hero.copy')); } catch (e) {}
        try { setHeroBadge(val('hero.badge_text')); } catch (e) {}
        try { setText('.hd-card-t, .hm-card-t', val('hero.card_title')); } catch (e) {}
        try { setText('.hd-card-s, .hm-card-s', val('hero.card_subtitle')); } catch (e) {}
        try { setCtaText('.hero-desktop .btn-dk, .hero-mobile .btn-dk', val('hero.cta_primary_label')); } catch (e) {}
        try { setCtaText('.hero-desktop .btn-sk, .hero-mobile .btn-sk', val('hero.cta_secondary_label')); } catch (e) {}

        // URL kedua CTA (href) — sebelumnya tidak live
        try {
            const d = fdoc();
            if (d) {
                d.querySelectorAll('.hero-desktop .btn-dk, .hero-mobile .btn-dk')
                    .forEach(a => a.setAttribute('href', val('hero.cta_primary_url') || '#'));
                d.querySelectorAll('.hero-desktop .btn-sk, .hero-mobile .btn-sk')
                    .forEach(a => a.setAttribute('href', val('hero.cta_secondary_url') || '#'));
            }
        } catch (e) {}

        try { applyHeroLiveStyle(); } catch (e) {}    // warna, ukuran judul, bentuk CTA, tinggi
        try { applyHeroLayoutLive(); } catch (e) {}   // split ↔ gradasi + overlay
        try { applyHeroMediaLive(); } catch (e) {}    // mode foto
        try { postHeroDraft(); } catch (e) {}         // jalur realtime yang diproses oleh halaman home
        try {
            const raw = document.getElementById('ws-hero-images')?.value || '[]';
            window.wsLiveHeroPhotos(JSON.parse(raw) || []);
        } catch (e) {}
        try { updateStyleSample(); } catch (e) {}
    }

    window.wsRefreshHeroPreview = applyHeroAll;

    // Delegasi: input/change APA PUN bernama hero.* → apply semuanya
    const wsForm = document.getElementById('ws-settings-form');
    if (wsForm) {
        ['input', 'change'].forEach(function (evt) {
            wsForm.addEventListener(evt, function (e) {
                const n = e.target?.name || e.target?.id || '';
                if (n.startsWith('hero.') || n === 'ws-hero-images' || n.startsWith('val-hero')) {
                    applyHeroAll();
                }
            });
        });
    }

    // Preview selesai reload (⟳ / navigasi) → terapkan ulang state form saat ini
    frame.addEventListener('load', function () {
        setTimeout(function () {
            applyHeroAll();
            applySectionsLive();
        }, 80);
    });
})();

// ── Foto hero dinamis: tambah / hapus / titik fokus per foto ─────────────────
(function () {
    const listEl = document.getElementById('ws-hero-photo-list');
    const hidden = document.getElementById('ws-hero-images');
    const fileEl = document.getElementById('ws-hero-photo-file');
    if (!listEl || !hidden || !fileEl) return;

    let photos = [];
    try { photos = JSON.parse(hidden.value || '[]') || []; } catch (e) { photos = []; }
    let replaceIdx = null;
    let focusMode = 'desktop';

    function sync() {
        hidden.value = JSON.stringify(photos);
        hidden.dispatchEvent(new Event('input', { bubbles: true })); // dirty state
    }

    function activatePhoto(i) {
        window.wsHeroPreviewPhotoIndex = Math.min(Math.max(parseInt(i, 10) || 0, 0), Math.max(photos.length - 1, 0));
        window.wsRefreshHeroPreview?.();
    }

    function focusKey() {
        return focusMode === 'mobile' ? 'focus_mobile' : 'focus_desktop';
    }

    function focusValue(photo) {
        return photo?.[focusKey()] || photo?.focus || null;
    }

    function render() {
        listEl.innerHTML = '';

        photos.forEach(function (p, i) {
            const field = document.createElement('div');
            field.className = 'ws-field';

            const focus = /^\d+% \d+%$/.test(focusValue(p) || '') ? focusValue(p) : null;
            const [fx, fy] = focus ? focus.split(' ').map(v => parseInt(v)) : [50, 50];

            field.innerHTML =
                '<label class="ws-label">Foto ' + (i + 1) + '</label>'
                + '<div class="ws-img-wrap">'
                + '  <div class="ws-focus-mode">'
                + '    <button type="button" class="js-focus-mode ' + (focusMode === 'desktop' ? 'active' : '') + '" data-mode="desktop">Desktop</button>'
                + '    <button type="button" class="js-focus-mode ' + (focusMode === 'mobile' ? 'active' : '') + '" data-mode="mobile">Mobile</button>'
                + '  </div>'
                + '  <div class="ws-img-preview is-portrait" style="cursor:default;">'
                + '    <img src="' + p.url + '" alt="Hero ' + (i + 1) + '">'
                + '    <div class="ws-focus-dot" title="Geser titik fokus" style="left:' + fx + '%;top:' + fy + '%;' + (focus ? '' : 'display:none;') + '"></div>'
                + '    <div class="ws-img-overlay">'
                + '      <button type="button" class="ws-img-action-btn js-photo-crop">✂ Crop</button>'
                + '      <button type="button" class="ws-img-action-btn js-photo-replace">Ganti</button>'
                + '      <button type="button" class="ws-img-action-btn danger js-photo-remove">Hapus</button>'
                + '    </div>'
                + '  </div>'
                + '  <span class="ws-img-hint">🎯 Pilih Desktop/Mobile, lalu geser titik fokus ke area utama foto</span>'
                + '  <div class="ws-focus-controls">'
                + '    <label class="ws-focus-control">Kiri/Kanan <input type="range" min="0" max="100" value="' + fx + '" class="js-focus-x"><output>' + fx + '%</output></label>'
                + '    <label class="ws-focus-control">Atas/Bawah <input type="range" min="0" max="100" value="' + fy + '" class="js-focus-y"><output>' + fy + '%</output></label>'
                + '  </div>'
                + '</div>';

            const preview = field.querySelector('.ws-img-preview');
            const previewImg = preview.querySelector('img');
            const dot     = field.querySelector('.ws-focus-dot');
            const rangeX  = field.querySelector('.js-focus-x');
            const rangeY  = field.querySelector('.js-focus-y');
            const outX    = rangeX.nextElementSibling;
            const outY    = rangeY.nextElementSibling;

            if (previewImg) previewImg.style.objectPosition = fx + '% ' + fy + '%';

            function applyFocus(x, y) {
                x = Math.min(100, Math.max(0, Math.round(parseInt(x, 10) || 0)));
                y = Math.min(100, Math.max(0, Math.round(parseInt(y, 10) || 0)));
                photos[i][focusKey()] = x + '% ' + y + '%';
                if (!photos[i].focus) photos[i].focus = x + '% ' + y + '%';
                dot.style.left = x + '%';
                dot.style.top = y + '%';
                dot.style.display = 'block';
                rangeX.value = x;
                rangeY.value = y;
                outX.textContent = x + '%';
                outY.textContent = y + '%';
                if (previewImg) previewImg.style.objectPosition = x + '% ' + y + '%';
                sync();
            }

            function setFocusFromPointer(e) {
                const r = preview.getBoundingClientRect();
                const x = Math.min(100, Math.max(0, Math.round((e.clientX - r.left) / r.width * 100)));
                const y = Math.min(100, Math.max(0, Math.round((e.clientY - r.top) / r.height * 100)));
                applyFocus(x, y);
            }

            preview.addEventListener('pointerdown', function (e) {
                if (e.target.closest('button')) return;
                e.preventDefault();
                activatePhoto(i);
                setFocusFromPointer(e);
                preview.setPointerCapture?.(e.pointerId);

                const move = function (ev) {
                    ev.preventDefault();
                    setFocusFromPointer(ev);
                };
                const up = function (ev) {
                    preview.releasePointerCapture?.(ev.pointerId);
                    preview.removeEventListener('pointermove', move);
                    preview.removeEventListener('pointerup', up);
                    preview.removeEventListener('pointercancel', up);
                };

                preview.addEventListener('pointermove', move);
                preview.addEventListener('pointerup', up);
                preview.addEventListener('pointercancel', up);
            });

            [rangeX, rangeY].forEach(function (range) {
                range.addEventListener('input', function () {
                    activatePhoto(i);
                    applyFocus(rangeX.value, rangeY.value);
                });
            });

            field.querySelectorAll('.js-focus-mode').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    focusMode = this.dataset.mode === 'mobile' ? 'mobile' : 'desktop';
                    wsPreviewDevice?.(focusMode);
                    activatePhoto(i);
                    render();
                });
            });

            field.querySelector('.js-photo-remove').addEventListener('click', function () {
                photos.splice(i, 1);
                if ((window.wsHeroPreviewPhotoIndex || 0) >= photos.length) {
                    window.wsHeroPreviewPhotoIndex = Math.max(photos.length - 1, 0);
                }
                sync(); render();
            });

            field.querySelector('.js-photo-replace').addEventListener('click', function () {
                replaceIdx = i;
                activatePhoto(i);
                fileEl.click();
            });

            field.querySelector('.js-photo-crop').addEventListener('click', function () {
                activatePhoto(i);
                wsOpenCropperFor(i);
            });

            listEl.appendChild(field);
        });

        // Tile tambah foto
        const addField = document.createElement('div');
        addField.className = 'ws-field';
        addField.innerHTML = '<label class="ws-label">&nbsp;</label>'
            + '<div class="ws-add-photo"><span class="plus">＋</span><span>Tambah Foto</span></div>';
        addField.querySelector('.ws-add-photo').addEventListener('click', function () {
            replaceIdx = null;
            fileEl.click();
        });
        listEl.appendChild(addField);
    }

    fileEl.addEventListener('change', function () {
        const file = fileEl.files[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('file', file);
        fd.append('_token', WS_CSRF);

        fetch(WS_UPLOAD_URL, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (!data.url) return alert('Upload gagal, coba lagi.');
                if (replaceIdx !== null && photos[replaceIdx]) {
                    photos[replaceIdx].url = data.url;
                    window.wsHeroPreviewPhotoIndex = replaceIdx;
                } else {
                    window.wsHeroPreviewPhotoIndex = photos.length;
                    photos.push({ url: data.url, focus: null });
                }
                replaceIdx = null;
                fileEl.value = '';
                sync(); render();
            })
            .catch(() => alert('Upload gagal, coba lagi.'));
    });

    // ── CROP langsung di gambar (Cropper.js) ──
    let cropper = null;
    let cropIdx = null;
    const cropOverlay = document.getElementById('ws-crop-overlay');
    const cropImg     = document.getElementById('ws-crop-img');

    window.wsOpenCropperFor = function (i) {
        if (!photos[i] || !window.Cropper) return;
        cropIdx = i;
        cropImg.src = photos[i].url;
        cropOverlay.classList.add('open');
        if (cropper) { cropper.destroy(); cropper = null; }
        cropper = new Cropper(cropImg, {
            viewMode: 1,
            autoCropArea: 0.95,
            background: false,
        });
    };

    window.wsCloseCropper = function () {
        cropOverlay.classList.remove('open');
        if (cropper) { cropper.destroy(); cropper = null; }
        cropIdx = null;
    };

    document.getElementById('ws-crop-apply')?.addEventListener('click', function () {
        if (!cropper || cropIdx === null) return;
        const btn = this;
        btn.disabled = true;
        btn.textContent = '⏳ Menyimpan…';

        cropper.getCroppedCanvas({ maxWidth: 1920, maxHeight: 1920 }).toBlob(function (blob) {
            const fd = new FormData();
            fd.append('file', blob, 'crop.jpg');
            fd.append('_token', WS_CSRF);

            fetch(WS_UPLOAD_URL, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.url && photos[cropIdx]) {
                        photos[cropIdx].url = data.url;
                        photos[cropIdx].focus = null; // crop baru = fokus di-reset
                        sync(); render();
                    }
                    window.wsCloseCropper();
                })
                .catch(() => alert('Gagal menyimpan hasil crop.'))
                .finally(() => { btn.disabled = false; btn.textContent = '✂ Terapkan Crop'; });
        }, 'image/jpeg', 0.88);
    });

    render();
})();

// ── Focal point: geser titik pada foto = set titik fokus ─────────────────────
(function () {
    document.querySelectorAll('.ws-img-wrap').forEach(function (wrap) {
        const key        = wrap.dataset.uploadKey;
        const focusInput = document.getElementById('val-' + key + '_focus');
        if (!focusInput) return; // hanya widget foto yang punya focal point

        const preview = wrap.querySelector('.ws-img-preview');
        const dot     = wrap.querySelector('.ws-focus-dot');
        if (!preview) return;

        function setFocusFromPointer(e) {
            const r = preview.getBoundingClientRect();
            const x = Math.min(100, Math.max(0, Math.round((e.clientX - r.left) / r.width * 100)));
            const y = Math.min(100, Math.max(0, Math.round((e.clientY - r.top) / r.height * 100)));

            focusInput.value = x + '% ' + y + '%';
            if (dot) {
                dot.style.left = x + '%';
                dot.style.top  = y + '%';
                dot.style.display = 'block';
            }
            // trigger dirty state
            focusInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        preview.addEventListener('pointerdown', function (e) {
            if (e.target.closest('button, a, input')) return; // Ganti/Hapus tetap normal
            e.preventDefault();
            setFocusFromPointer(e);
            preview.setPointerCapture?.(e.pointerId);

            const move = function (ev) {
                ev.preventDefault();
                setFocusFromPointer(ev);
            };
            const up = function (ev) {
                preview.releasePointerCapture?.(ev.pointerId);
                preview.removeEventListener('pointermove', move);
                preview.removeEventListener('pointerup', up);
                preview.removeEventListener('pointercancel', up);
            };

            preview.addEventListener('pointermove', move);
            preview.addEventListener('pointerup', up);
            preview.addEventListener('pointercancel', up);
        });
    });
})();

// ── Dirty state: tandai perubahan belum disimpan ──────────────────────────────
(function () {
    const form = document.querySelector('form[action*="website/settings"]');
    const bar  = document.getElementById('ws-save-bar');
    if (!form || !bar) return;
    let dirty = false;

    form.addEventListener('input', () => {
        if (!dirty) {
            dirty = true;
            bar.classList.add('is-dirty');
            document.getElementById('ws-top-save')?.classList.add('is-dirty');
        }
    });
    form.addEventListener('submit', () => {
        dirty = false;
        const active = document.querySelector('.ws-section.active')?.id?.replace('tab-', '');
        if (active) {
            try { sessionStorage.setItem(WS_ACTIVE_TAB_KEY, active); } catch (e) {}
        }
    });
    window.addEventListener('beforeunload', (e) => {
        if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });
})();

// ── Color picker sync ─────────────────────────────────────────────────────────
function syncColor(key, val) {
    const hex = document.getElementById('hex-' + key);
    if (hex) hex.value = val;
    updateSwatch(key, val);
}

function syncColorFromHex(key, val) {
    if (/^#[0-9a-fA-F]{6}$/.test(val)) {
        const picker = document.getElementById('color-' + key);
        if (picker) picker.value = val;
        updateSwatch(key, val);
    }
}

function updateSwatch(key, val) {
    const swatch = document.getElementById('swatch-' + key);
    if (swatch && /^#[0-9a-fA-F]{6}$/.test(val)) {
        swatch.style.background = val;
    }
}

// ── Sections drag-and-drop ────────────────────────────────────────────────────
(function () {
    const list = document.getElementById('ws-sortable');
    if (!list) return;

    let dragSrc = null;

    // Drag hanya dimulai dari handle ⠿ — dicatat saat POINTERDOWN, karena pada
    // event dragstart e.target = elemen yang di-drag (item), bukan yang ditekan.
    let pressOnHandle = false;
    list.addEventListener('pointerdown', function (e) {
        pressOnHandle = !!e.target.closest('.ws-drag-handle');
    });

    list.querySelectorAll('.ws-sortable-item').forEach(item => {
        item.addEventListener('dragstart', function (e) {
            if (!pressOnHandle) {
                e.preventDefault();
                return;
            }
            dragSrc = this;
            setTimeout(() => this.classList.add('dragging'), 0);
            e.dataTransfer.effectAllowed = 'move';
        });
        item.addEventListener('dragend', function () {
            pressOnHandle = false;
            this.classList.remove('dragging');
            list.querySelectorAll('.ws-sortable-item').forEach(i => i.classList.remove('drag-over'));
            wsUpdateSectionOrder();
        });
        item.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            list.querySelectorAll('.ws-sortable-item').forEach(i => i.classList.remove('drag-over'));
            if (this !== dragSrc) this.classList.add('drag-over');
        });
        item.addEventListener('dragleave', function () {
            this.classList.remove('drag-over');
        });
        item.addEventListener('drop', function (e) {
            e.preventDefault();
            if (dragSrc && dragSrc !== this) {
                const items  = [...list.querySelectorAll('.ws-sortable-item')];
                const srcIdx = items.indexOf(dragSrc);
                const tgtIdx = items.indexOf(this);
                list.insertBefore(dragSrc, srcIdx < tgtIdx ? this.nextSibling : this);
            }
            this.classList.remove('drag-over');
            wsUpdateSectionOrder();
        });
    });

    function wsUpdateSectionOrder() {
        const keys = [...list.querySelectorAll('.ws-sortable-item')].map(i => i.dataset.key);
        const inp  = document.getElementById('ws-sections-order');
        if (inp) {
            inp.value = keys.join(',');
            // trigger dirty state + live preview reorder
            inp.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
})();

// ── Image upload widget ───────────────────────────────────────────────────────
const WS_UPLOAD_URL = '{{ route('admin.website.settings.upload') }}';
const WS_CSRF       = '{{ csrf_token() }}';

function wsPickImg(key) {
    document.getElementById('file-' + key)?.click();
}

function wsClearImg(key) {
    document.getElementById('val-' + key).value = '';
    const prev = document.getElementById('preview-' + key);
    if (prev) {
        if (prev.tagName === 'IMG') {
            prev.src = '';
            prev.style.display = 'none';
        } else {
            prev.textContent = '📷';
        }
    }
}

function wsSetImgVal(key, url) {
    const hiddenInp = document.getElementById('val-' + key);
    hiddenInp.value = url;
    // trigger live preview + dirty state
    hiddenInp.dispatchEvent(new Event('input', { bubbles: true }));
    // Update preview kalau valid URL
    const prev = document.getElementById('preview-' + key);
    if (prev && url) {
        if (prev.tagName !== 'IMG') {
            // Replace span dengan img
            const img = document.createElement('img');
            img.id  = prev.id;
            img.alt = key;
            prev.parentNode.replaceChild(img, prev);
            img.src = url;
        } else {
            prev.src = url;
            prev.style.display = '';
        }
    }
}

function wsUploadImg(key, input) {
    const file = input.files[0];
    if (!file) return;

    const prog = document.getElementById('prog-' + key);
    if (prog) { prog.textContent = 'Mengupload...'; prog.style.display = ''; }

    const fd = new FormData();
    fd.append('file', file);
    fd.append('_token', WS_CSRF);

    fetch(WS_UPLOAD_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.url) {
                wsSetImgVal(key, data.url);
                // Update teks input juga kalau ada
                const textInput = document.querySelector(`[data-upload-key="${key}"] input[type="text"]`);
                if (textInput) textInput.value = data.url;
            }
            if (prog) prog.style.display = 'none';
            input.value = '';
        })
        .catch(() => {
            if (prog) { prog.textContent = 'Gagal upload, coba lagi.'; }
        });
}
</script>
@endpush
