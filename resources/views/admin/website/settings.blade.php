@extends('layouts.app')
@section('title', 'Pengaturan Website')

@push('head')
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

/* ── Sortable section items ── */
.ws-sortable-list { display:flex;flex-direction:column;gap:.5rem; }
.ws-sortable-item { display:flex;align-items:center;gap:.85rem;padding:.7rem 1rem;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;cursor:grab;user-select:none;transition:border-color .15s,box-shadow .15s; }
.ws-sortable-item.dragging { opacity:.5; }
.ws-sortable-item.drag-over { border-color:#6366f1;box-shadow:0 0 0 2px #e0e7ff; }
.ws-drag-handle { font-size:1.1rem;color:#cbd5e1;flex-shrink:0;cursor:grab; }
.ws-sec-meta { flex:1;min-width:0; }
.ws-sec-name { font-size:.82rem;font-weight:800;color:#0f172a; }
.ws-sec-desc { font-size:.68rem;color:#94a3b8;margin-top:.1rem; }
.ws-sec-icon { font-size:1.1rem;flex-shrink:0; }
/* toggle switch */
.ws-toggle { position:relative;flex-shrink:0; }
.ws-toggle input { opacity:0;width:0;height:0;position:absolute; }
.ws-toggle-track { display:block;width:36px;height:20px;border-radius:999px;background:#e2e8f0;cursor:pointer;transition:background .2s; }
.ws-toggle input:checked + .ws-toggle-track { background:#6366f1; }
.ws-toggle-track::after { content:'';position:absolute;top:3px;left:3px;width:14px;height:14px;border-radius:50%;background:#fff;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2); }
.ws-toggle input:checked + .ws-toggle-track::after { transform:translateX(16px); }

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
    <a href="{{ url('/') }}" target="_blank" rel="noopener"
       style="display:inline-flex;align-items:center;gap:.4rem;padding:.4rem .9rem;border-radius:8px;border:1.5px solid #e2e8f0;font-size:.75rem;font-weight:700;color:#0f172a;text-decoration:none;background:#fff;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        Lihat di Website
    </a>
</div>

@if(session('success'))
<div class="ws-alert ws-alert-success">✅ {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="ws-alert ws-alert-error">❌ {{ session('error') }}</div>
@endif

<form method="POST" action="{{ route('admin.website.settings.update') }}">
@csrf
@method('POST')

<div class="ws-wrap">

    {{-- ── TABS ── --}}
    <div class="ws-tabs">
        <button type="button" class="ws-tab active" onclick="wsShowTab('branding',this)">🏷️ Branding</button>
        <button type="button" class="ws-tab" onclick="wsShowTab('colors',this)">🎨 Warna</button>
        <button type="button" class="ws-tab" onclick="wsShowTab('hero',this)">🦸 Hero</button>
        <button type="button" class="ws-tab" onclick="wsShowTab('values',this)">✨ Values</button>
        <button type="button" class="ws-tab" onclick="wsShowTab('channels',this)">🛒 Channels</button>
        <button type="button" class="ws-tab" onclick="wsShowTab('footer',this)">🔻 Footer</button>
        <button type="button" class="ws-tab" onclick="wsShowTab('checkout',this)">💳 Checkout</button>
        <button type="button" class="ws-tab" onclick="wsShowTab('sections',this)">📐 Sections</button>
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

                {{-- Foto hero ──────────────────────────────────────────── --}}
                <p style="font-size:.7rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin:0 0 .75rem">Foto Slideshow (3 gambar)</p>
                <div class="ws-grid" style="margin-bottom:1.5rem">
                    @foreach([1,2,3] as $hi)
                    @php $hImgVal = $s["hero.image_{$hi}"] ?? ''; @endphp
                    <div class="ws-field">
                        <label class="ws-label">Foto {{ $hi }}</label>
                        <div class="ws-img-wrap" data-upload-key="hero.image_{{ $hi }}">
                            <div class="ws-img-preview is-portrait">
                                @if($hImgVal)
                                    <img src="{{ $hImgVal }}" alt="Hero {{ $hi }}" id="preview-hero.image_{{ $hi }}">
                                @else
                                    <span class="ws-img-ph" id="preview-hero.image_{{ $hi }}">📷</span>
                                @endif
                                <div class="ws-img-overlay">
                                    <button type="button" class="ws-img-action-btn" onclick="wsPickImg('hero.image_{{ $hi }}')">Ganti</button>
                                    <button type="button" class="ws-img-action-btn danger" onclick="wsClearImg('hero.image_{{ $hi }}')">Hapus</button>
                                </div>
                            </div>
                            <input type="hidden" name="hero.image_{{ $hi }}" id="val-hero.image_{{ $hi }}" value="{{ $hImgVal }}">
                            <input type="file" accept="image/*" id="file-hero.image_{{ $hi }}" style="display:none"
                                   onchange="wsUploadImg('hero.image_{{ $hi }}', this)">
                            <div class="ws-img-progress" id="prog-hero.image_{{ $hi }}" style="display:none">Mengupload...</div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <hr style="border:none;border-top:1.5px solid #f1f5f9;margin-bottom:1.25rem">

                <div class="ws-grid">

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
                        <span class="ws-drag-handle">⠿</span>
                        <span class="ws-sec-icon">{{ $sfSecMeta['icon'] }}</span>
                        <div class="ws-sec-meta">
                            <div class="ws-sec-name">{{ $sfSecMeta['name'] }}</div>
                            <div class="ws-sec-desc">{{ $sfSecMeta['desc'] }}</div>
                        </div>
                        <label class="ws-toggle" title="Tampilkan / sembunyikan">
                            <input type="hidden"   name="sections_{{ $sfSecKey }}_visible" value="0">
                            <input type="checkbox" name="sections_{{ $sfSecKey }}_visible" value="1"
                                {{ ($s["sections.{$sfSecKey}_visible"] ?? '1') !== '0' ? 'checked' : '' }}>
                            <span class="ws-toggle-track"></span>
                        </label>
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
    <div class="ws-save-bar">
        <span class="ws-save-hint">Perubahan langsung berlaku setelah disimpan</span>
        <button type="submit" class="ws-save-btn">💾 Simpan Pengaturan</button>
    </div>

</div>
</form>
@endsection

@push('scripts')
<script>
// ── Tab switching ─────────────────────────────────────────────────────────────
function wsShowTab(name, btn) {
    document.querySelectorAll('.ws-section').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.ws-tab').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + name)?.classList.add('active');
    btn.classList.add('active');
}

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

    list.querySelectorAll('.ws-sortable-item').forEach(item => {
        item.addEventListener('dragstart', function (e) {
            dragSrc = this;
            setTimeout(() => this.classList.add('dragging'), 0);
            e.dataTransfer.effectAllowed = 'move';
        });
        item.addEventListener('dragend', function () {
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
        if (inp) inp.value = keys.join(',');
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
    document.getElementById('val-' + key).value = url;
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
