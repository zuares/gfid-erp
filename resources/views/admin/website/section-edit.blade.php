@extends('layouts.app')
@section('title', 'Atur Section ' . $meta['name'])

@push('head')
<style>
.se-wrap { max-width:980px;margin:0 auto;display:grid;gap:1rem; }
.se-head { display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap; }
.se-back { display:inline-flex;align-items:center;gap:.45rem;height:38px;padding:0 .85rem;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#0f172a;text-decoration:none;font-size:.76rem;font-weight:800; }
.se-title { display:flex;align-items:center;gap:.65rem;margin-top:.25rem; }
.se-icon { width:42px;height:42px;border-radius:12px;background:#f8fafc;border:1px solid #eef2f7;display:grid;place-items:center;font-size:1.25rem; }
.se-title h1 { margin:0;font-size:1.2rem;font-weight:900;color:#0f172a;letter-spacing:0; }
.se-title p { margin:.12rem 0 0;font-size:.76rem;color:#64748b;font-weight:600; }
.se-card { background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,.04); }
.se-card-h { padding:.85rem 1rem;background:#f8fafc;border-bottom:1.5px solid #e8ecf0;font-size:.74rem;font-weight:900;letter-spacing:.06em;text-transform:uppercase;color:#64748b; }
.se-card-b { padding:1rem; }
.se-grid { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.9rem; }
.se-field { display:flex;flex-direction:column;gap:.35rem; }
.se-label { font-size:.65rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8; }
.se-input,.se-select,.se-textarea { width:100%;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#0f172a;font-size:.84rem;font-family:inherit;padding:.65rem .78rem; }
.se-textarea { min-height:82px;resize:vertical; }
.se-range-row { display:grid;grid-template-columns:minmax(0,1fr) 48px;gap:.65rem;align-items:center; }
.se-range-row input { width:100%;accent-color:#0f172a; }
.se-range-row output { font-size:.72rem;font-weight:900;color:#64748b;text-align:right;font-variant-numeric:tabular-nums; }
.se-color { height:42px;padding:0;border-radius:10px; }
.se-toggle { display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 1rem;border:1.5px solid #e2e8f0;border-radius:12px;background:#fff; }
.se-toggle strong { font-size:.84rem; }
.se-toggle span { display:block;margin-top:.12rem;font-size:.7rem;color:#94a3b8;font-weight:600; }
.se-switch { position:relative;display:inline-block;width:42px;height:24px;flex:0 0 auto; }
.se-switch input { opacity:0;width:0;height:0; }
.se-slider { position:absolute;cursor:pointer;inset:0;background:#e2e8f0;border-radius:999px;transition:.18s; }
.se-slider:before { content:"";position:absolute;width:18px;height:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.18s;box-shadow:0 1px 3px rgba(0,0,0,.18); }
.se-switch input:checked + .se-slider { background:#0f172a; }
.se-switch input:checked + .se-slider:before { transform:translateX(18px); }
.se-actions { position:sticky;bottom:1rem;background:#0f172a;color:#fff;border-radius:14px;padding:.75rem 1rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;box-shadow:0 12px 32px rgba(15,23,42,.2); }
.se-btn { border:none;border-radius:10px;background:#E8FF00;color:#0a0a0a;font-size:.78rem;font-weight:900;padding:.58rem 1.15rem;cursor:pointer; }
.se-link-grid { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem; }
.se-link { display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.9rem;border:1.5px solid #e2e8f0;border-radius:12px;text-decoration:none;color:#0f172a;background:#fff; }
.se-link strong { font-size:.8rem; }
.se-link span { display:block;margin-top:.12rem;font-size:.68rem;color:#94a3b8;font-weight:600; }
@media(max-width:720px){ .se-grid,.se-link-grid{grid-template-columns:1fr}.se-actions{align-items:stretch;flex-direction:column}.se-btn{width:100%} }
</style>
@endpush

@section('content')
@php
    $visible = ($settings["sections.{$section}_visible"] ?? '1') !== '0';
    $spacing = [
        'padding_top' => $settings["sections.{$section}_padding_top"] ?? '44',
        'padding_bottom' => $settings["sections.{$section}_padding_bottom"] ?? '44',
        'margin_top' => $settings["sections.{$section}_margin_top"] ?? '0',
        'margin_bottom' => $settings["sections.{$section}_margin_bottom"] ?? '0',
        'bg' => $settings["sections.{$section}_bg"] ?? '#ffffff',
        'style' => $settings["sections.{$section}_style"] ?? 'default',
    ];
@endphp

<div class="se-wrap">
    <div class="se-head">
        <div>
            <a class="se-back" href="{{ route('admin.website.settings') }}" onclick="try{sessionStorage.setItem('gfid.websiteSettings.activeTab','sections')}catch(e){}">← Kembali ke Susunan</a>
            <div class="se-title">
                <div class="se-icon">{{ $meta['icon'] }}</div>
                <div>
                    <h1>{{ $meta['name'] }}</h1>
                    <p>{{ $meta['desc'] }}</p>
                </div>
            </div>
        </div>
        <a class="se-back" href="{{ route('storefront.home') }}" target="_blank" rel="noopener">Lihat halaman ↗</a>
    </div>

    @if(session('success'))
    <div class="alert alert-success py-2 px-3 mb-0" style="font-size:.8rem;font-weight:700">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.website.settings.sections.update', $section) }}">
        @csrf

        <div class="se-card">
            <div class="se-card-h">Status section</div>
            <div class="se-card-b">
                <label class="se-toggle">
                    <span>
                        <strong>Tampilkan section</strong>
                        <span>Matikan jika section ini belum ingin tampil di landing page.</span>
                    </span>
                    <span class="se-switch">
                        <input type="checkbox" name="visible" value="1" {{ $visible ? 'checked' : '' }}>
                        <span class="se-slider"></span>
                    </span>
                </label>
            </div>
        </div>

        @if($section !== 'hero')
        <div class="se-card mt-3">
            <div class="se-card-h">Spacing & style</div>
            <div class="se-card-b">
                <div class="se-grid">
                    @foreach([
                        ['padding_top', 'Padding atas', 0, 96],
                        ['padding_bottom', 'Padding bawah', 0, 96],
                        ['margin_top', 'Margin atas', 0, 80],
                        ['margin_bottom', 'Margin bawah', 0, 80],
                    ] as [$field, $label, $min, $max])
                    <div class="se-field">
                        <label class="se-label">{{ $label }}</label>
                        <div class="se-range-row">
                            <input type="range" min="{{ $min }}" max="{{ $max }}" step="2" name="{{ $field }}" value="{{ $spacing[$field] }}" oninput="this.nextElementSibling.value=this.value">
                            <output>{{ $spacing[$field] }}</output>
                        </div>
                    </div>
                    @endforeach

                    <div class="se-field">
                        <label class="se-label">Warna background</label>
                        <input class="se-input se-color" type="color" name="bg" value="{{ $spacing['bg'] }}">
                    </div>

                    <div class="se-field">
                        <label class="se-label">Style</label>
                        <select class="se-select" name="style">
                            <option value="default" {{ $spacing['style'] === 'default' ? 'selected' : '' }}>Default</option>
                            <option value="soft" {{ $spacing['style'] === 'soft' ? 'selected' : '' }}>Soft band</option>
                            <option value="line" {{ $spacing['style'] === 'line' ? 'selected' : '' }}>Border top</option>
                            <option value="compact" {{ $spacing['style'] === 'compact' ? 'selected' : '' }}>Compact</option>
                            <option value="outline" {{ $spacing['style'] === 'outline' ? 'selected' : '' }}>Outline</option>
                            <option value="elevated" {{ $spacing['style'] === 'elevated' ? 'selected' : '' }}>Elevated</option>
                            <option value="dark" {{ $spacing['style'] === 'dark' ? 'selected' : '' }}>Dark band</option>
                            <option value="editorial" {{ $spacing['style'] === 'editorial' ? 'selected' : '' }}>Editorial</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($section === 'channels')
        <div class="se-card mt-3">
            <div class="se-card-h">Isi channels</div>
            <div class="se-card-b">
                <div class="se-grid">
                    <div class="se-field"><label class="se-label">URL Shopee</label><input class="se-input" name="channels_shopee_url" value="{{ $settings['channels.shopee_url'] ?? '' }}"></div>
                    <div class="se-field"><label class="se-label">URL Tokopedia</label><input class="se-input" name="channels_tokopedia_url" value="{{ $settings['channels.tokopedia_url'] ?? '' }}"></div>
                    <div class="se-field"><label class="se-label">URL TikTok</label><input class="se-input" name="channels_tiktok_url" value="{{ $settings['channels.tiktok_url'] ?? '' }}"></div>
                </div>
            </div>
        </div>
        @endif

        @if($section === 'categories')
        <div class="se-card mt-3">
            <div class="se-card-h">Isi kategori</div>
            <div class="se-card-b">
                <div class="se-grid">
                    <div class="se-field"><label class="se-label">Label kecil</label><input class="se-input" name="categories_eyebrow" value="{{ $settings['categories.eyebrow'] ?? 'Koleksi' }}"></div>
                    <div class="se-field"><label class="se-label">Judul</label><input class="se-input" name="categories_title" value="{{ $settings['categories.title'] ?? 'Cari yang paling pas' }}"></div>
                    <div class="se-field" style="grid-column:1/-1"><label class="se-label">Deskripsi singkat</label><textarea class="se-textarea" name="categories_copy">{{ $settings['categories.copy'] ?? 'Mulai dari kategori yang kamu butuhkan.' }}</textarea></div>
                    <div class="se-field"><label class="se-label">Label tombol semua produk</label><input class="se-input" name="categories_all_label" value="{{ $settings['categories.all_label'] ?? 'Lihat semua' }}"></div>
                    <div class="se-field">
                        <label class="se-label">Batas kategori tampil</label>
                        @php $seCatLimit = (int)($settings['categories.limit'] ?? 8); @endphp
                        <select class="se-select" name="categories_limit">
                            @foreach([4,6,8,10,12] as $limit)
                            <option value="{{ $limit }}" {{ $seCatLimit === $limit ? 'selected' : '' }}>{{ $limit }} kategori</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($section === 'values')
        <div class="se-card mt-3">
            <div class="se-card-h">Isi values</div>
            <div class="se-card-b">
                <div style="display:grid;gap:1rem">
                    @foreach([1,2,3] as $i)
                    <div class="se-grid" style="padding:1rem;background:#f8fafc;border-radius:12px">
                        <div class="se-field"><label class="se-label">Angka {{ $i }}</label><input class="se-input" name="values_{{ $i }}_number" value="{{ $settings["values.{$i}_number"] ?? str_pad($i, 2, '0', STR_PAD_LEFT) }}"></div>
                        <div class="se-field"><label class="se-label">Judul {{ $i }}</label><input class="se-input" name="values_{{ $i }}_title" value="{{ $settings["values.{$i}_title"] ?? '' }}"></div>
                        <div class="se-field" style="grid-column:1/-1"><label class="se-label">Deskripsi {{ $i }}</label><textarea class="se-textarea" name="values_{{ $i }}_desc">{{ $settings["values.{$i}_desc"] ?? '' }}</textarea></div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <div class="se-card mt-3">
            <div class="se-card-h">Pengaturan isi lain</div>
            <div class="se-card-b">
                <div class="se-link-grid">
                    @if($section === 'hero')
                    <a class="se-link" href="{{ route('admin.website.settings') }}" onclick="try{sessionStorage.setItem('gfid.websiteSettings.activeTab','hero')}catch(e){}"><span><strong>Edit Hero</strong><span>Foto, CTA, warna, dan fokus</span></span><b>→</b></a>
                    @elseif($section === 'categories')
                    <a class="se-link" href="{{ route('admin.catalog.categories.index') }}"><span><strong>Kelola Kategori</strong><span>Nama, slug, dan urutan kategori</span></span><b>→</b></a>
                    @elseif($section === 'products')
                    <a class="se-link" href="{{ route('admin.catalog.products.index') }}"><span><strong>Kelola Produk</strong><span>Produk, varian, stok, foto</span></span><b>→</b></a>
                    <a class="se-link" href="{{ route('admin.catalog.products.ranking') }}"><span><strong>Ranking Produk</strong><span>Atur produk pilihan</span></span><b>→</b></a>
                    @elseif($section === 'cta')
                    <a class="se-link" href="{{ route('admin.website.settings') }}" onclick="try{sessionStorage.setItem('gfid.websiteSettings.activeTab','sections')}catch(e){}"><span><strong>CTA Landing</strong><span>Konten CTA masih mengikuti template landing page</span></span><b>→</b></a>
                    @else
                    <a class="se-link" href="{{ route('admin.website.settings') }}" onclick="try{sessionStorage.setItem('gfid.websiteSettings.activeTab','sections')}catch(e){}"><span><strong>Kembali ke Settings</strong><span>Atur section lain</span></span><b>→</b></a>
                    @endif
                </div>
            </div>
        </div>

        <div class="se-actions mt-3">
            <span style="font-size:.72rem;color:#94a3b8;font-weight:800">Simpan untuk menerapkan permanen di storefront</span>
            <button class="se-btn" type="submit">Simpan Section</button>
        </div>
    </form>
</div>
@endsection
