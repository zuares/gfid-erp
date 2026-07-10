@extends('layouts.app')
@section('title', 'Marketplace • Pengaturan Global')

@include('marketplace._shared')

@push('head')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

    :root{
        --shp-accent: #2563eb;
        --shp-accent-2: #1d4ed8;
        --shp-accent-light: #eff6ff;
        --shp-border: rgba(148,163,184, 0.25);
        --shp-border-strong: rgba(148,163,184, 0.40);
        --shp-muted: #64748b;
        --shp-text: #1e293b;
        --shp-bg-hover: rgba(241, 245, 249, 0.8);
        --card-bg: rgba(255, 255, 255, 0.85);
        --glass-border: rgba(255, 255, 255, 0.5);
    }
    
    body[data-theme="dark"] {
        --shp-accent: #3b82f6;
        --shp-accent-2: #60a5fa;
        --shp-accent-light: rgba(59, 130, 246, 0.1);
        --shp-border: rgba(51, 65, 85, 0.8);
        --shp-border-strong: rgba(71, 85, 105, 0.8);
        --shp-muted: #94a3b8;
        --shp-text: #f1f5f9;
        --shp-bg-hover: rgba(30, 41, 59, 0.8);
        --card-bg: rgba(15, 23, 42, 0.75);
        --glass-border: rgba(255, 255, 255, 0.05);
    }

    .page-wrap { 
        max-width: 1140px; 
        margin-inline: auto; 
        padding: 1rem 1rem 4rem; 
        background: transparent !important; 
        font-family: 'Outfit', system-ui, -apple-system, sans-serif;
    }

    /* Layout Induk */
    .ms-main { display:grid; grid-template-columns:minmax(0,1fr); gap:2.5rem; align-items:start; }
    @media(min-width:992px){ .ms-main { grid-template-columns:260px minmax(0,1fr); } }

    /* Sidebar Navigation */
    .ms-side { display:none; }
    @media(min-width:992px){ .ms-side { display:block; position:sticky; top:2rem; } }
    
    .ms-side-item { 
        display:flex; align-items:center; gap:1rem; width:100%; text-align:left; 
        padding: 1rem 1.25rem; border:1px solid transparent; background:transparent; 
        border-radius:14px; cursor:pointer; transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        margin-bottom:0.6rem; position: relative; overflow: hidden;
    }
    .ms-side-item:hover { 
        background: var(--shp-bg-hover);
        transform: translateX(4px);
    }
    .ms-side-item.active { 
        background: linear-gradient(135deg, var(--shp-accent), var(--shp-accent-2)); 
        box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4), 0 8px 10px -6px rgba(37, 99, 235, 0.1); 
        border-color: rgba(255,255,255,0.1);
    }
    
    .ms-side-item.active::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
        transform: translateX(-100%); transition: 0.5s;
    }
    .ms-side-item.active:hover::before { transform: translateX(100%); }

    .ms-side-item.active .ms-side-label { color:#ffffff; font-weight: 700; }
    .ms-side-item.active .ms-side-desc { color:rgba(255,255,255,0.85); }
    .ms-side-item.active .ms-side-ic i { color:#ffffff !important; }
    
    .ms-side-ic { 
        font-size: 1.35rem; flex-shrink: 0; width: 36px; height: 36px; 
        display: flex; align-items: center; justify-content: center; 
        border-radius: 10px; background: var(--shp-accent-light); transition: 0.3s;
    }
    .ms-side-item.active .ms-side-ic { background: rgba(255,255,255,0.2); }
    
    .ms-side-label { font-size: 0.95rem; font-weight: 600; color: var(--shp-text); line-height: 1.2; transition: 0.3s; }
    .ms-side-desc { font-size: 0.75rem; color: var(--shp-muted); line-height: 1.4; margin-top: 4px; transition: 0.3s; }

    /* Card Panels */
    .card-main {
        background: var(--card-bg);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: 20px;
        border: 1px solid var(--glass-border);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.02), inset 0 1px 0 rgba(255,255,255,0.3);
        overflow: hidden;
        margin-bottom: 2rem;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    body[data-theme="dark"] .card-main { box-shadow: 0 10px 30px -10px rgba(0,0,0,0.5); inset 0 1px 0 rgba(255,255,255,0.05); }
    
    .card-main:hover {
        box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.08), 0 4px 8px -4px rgba(0, 0, 0, 0.04);
    }
    
    .card-header-custom {
        padding: 1.5rem 2rem;
        background: transparent;
        border-bottom: 1px solid var(--shp-border);
        display: flex; align-items: center; gap: 1rem;
        position: relative;
    }
    .card-header-custom::after {
        content: ''; position: absolute; bottom: -1px; left: 2rem; right: 2rem; height: 1px;
        background: linear-gradient(90deg, var(--shp-accent), transparent);
        opacity: 0.3;
    }
    
    .card-title-custom { font-size: 1.15rem; font-weight: 700; color: var(--shp-text); margin:0; letter-spacing: -0.01em; }
    .card-body-custom { padding: 0.5rem 2rem 1.5rem; }

    /* Form Elements & Layout */
    .settings-row {
        padding: 1.75rem 0;
        border-bottom: 1px dashed var(--shp-border);
        transition: background 0.3s;
    }
    .settings-row:last-child { border-bottom: none; }
    .settings-row:hover { background: rgba(0,0,0,0.01); }
    body[data-theme="dark"] .settings-row:hover { background: rgba(255,255,255,0.01); }
    
    .settings-row-label { font-weight: 700; color: var(--shp-text); font-size: 0.95rem; margin-bottom: 0.35rem; }
    .settings-row-desc { font-size: 0.85rem; color: var(--shp-muted); line-height: 1.5; }
    
    .form-control, .form-select {
        border-radius: 12px; 
        border: 1px solid var(--shp-border-strong);
        font-size: 0.9rem;
        padding: 0.75rem 1rem;
        background: var(--card-bg);
        color: var(--shp-text);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--shp-accent);
        background: #fff;
        box-shadow: 0 0 0 4px var(--shp-accent-light);
        outline: none;
    }
    body[data-theme="dark"] .form-control:focus, body[data-theme="dark"] .form-select:focus { background: #0f172a; }

    /* Modern iOS-like Switch */
    .form-switch .form-check-input {
        width: 3.2rem;
        height: 1.7rem;
        cursor: pointer;
        border-radius: 2rem;
        transition: background-position .15s ease-in-out, background-color .2s ease, border-color .2s ease;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    }
    .form-switch .form-check-input:checked {
        background-color: #10b981;
        border-color: #10b981;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1), 0 0 0 3px rgba(16, 185, 129, 0.2);
    }
    .form-check-label { cursor: pointer; font-weight: 500; font-size: 0.9rem; margin-top: 3px; color: var(--shp-text); }

    /* Buttons */
    .btn-pill { 
        border-radius: 50px; padding: 0.75rem 1.5rem; box-shadow: none !important; 
        font-weight: 600; font-size: 0.9rem; letter-spacing: 0.02em; transition: all 0.3s; 
    }
    .btn-ship-primary { 
        background: linear-gradient(135deg, var(--shp-accent), var(--shp-accent-2)) !important; 
        border: none !important; color: #fff !important; 
        box-shadow: 0 4px 12px rgba(37,99,235,0.3) !important;
    }
    .btn-ship-primary:hover { 
        transform: translateY(-2px); 
        box-shadow: 0 8px 20px rgba(37,99,235,0.4) !important;
    }
    .btn-ship-primary:active { transform: translateY(0); box-shadow: 0 2px 8px rgba(37,99,235,0.3) !important; }
    
    .ms-section { display: none; opacity: 0; transform: translateY(15px); }
    .ms-section.active { display: block; animation: ms-fade 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    @keyframes ms-fade { to { opacity:1; transform:translateY(0); } }

    /* Mobile Tabs */
    .ms-tabs { display:flex; overflow-x:auto; gap:0.75rem; margin-bottom:2rem; padding-bottom:0.5rem; scroll-behavior: smooth; }
    .ms-tabs::-webkit-scrollbar { height: 0; display: none; }
    @media(min-width:992px){ .ms-tabs { display:none; } }
    .ms-tab-btn {
        padding: 0.75rem 1.25rem; border-radius: 12px; border: 1px solid var(--shp-border);
        background: var(--card-bg); color: var(--shp-muted); font-size: 0.85rem; font-weight: 600;
        white-space: nowrap; transition: all 0.3s;
    }
    .ms-tab-btn.active { 
        background: var(--shp-accent); color: #fff; border-color: var(--shp-accent);
        box-shadow: 0 4px 12px rgba(37,99,235,0.25);
    }
    
    .alert-success-custom {
        background: linear-gradient(to right, #ecfdf5, #f0fdf4); border: 1px solid #a7f3d0; color: #065f46;
        padding: 1rem 1.5rem; border-radius: 14px; font-size: 0.95rem; margin-bottom: 2rem; font-weight: 600;
        display:flex; align-items:center; gap:0.75rem; box-shadow: 0 4px 12px rgba(16,185,129,0.1);
        animation: slideDown 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }
    body[data-theme="dark"] .alert-success-custom { background: rgba(16,185,129,0.1); border-color: rgba(16,185,129,0.3); color: #34d399; }
    @keyframes slideDown { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
    
    /* Interactive Cards */
    .tpl-label { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor:pointer; }
    
    .tpl-card { 
        position:relative; overflow:hidden; border-radius:14px; 
        border:2px solid transparent; background: var(--card-bg);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor:pointer; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.04), 0 0 0 1px var(--shp-border);
    }
    .tpl-card:hover { 
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.08), 0 0 0 1px var(--shp-border-strong); 
    }
    
    .tpl-card:has(.peer-radio:checked) { 
        border-color: #10b981 !important; 
        box-shadow: 0 8px 20px rgba(16,185,129,0.2), 0 0 0 1px #10b981; 
        transform: translateY(-2px);
    }
    
    .tpl-card:has(.peer-radio:checked)::after {
        content: '\f00c'; /* FontAwesome Check */
        font-family: 'Font Awesome 5 Free'; font-weight: 900;
        position: absolute; top: 10px; right: 10px;
        background: #10b981; color: #fff; font-size: 0.8rem;
        width: 24px; height: 24px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        z-index: 2; box-shadow: 0 2px 6px rgba(16,185,129,0.4);
        animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes popIn { 0% { transform: scale(0); } 100% { transform: scale(1); } }
    
    /* For "Tidak Pakai" option */
    .form-check:has(.peer-radio:checked) .tpl-label-none {
        border-color: var(--shp-accent) !important;
        background: var(--shp-accent-light) !important;
        box-shadow: 0 0 0 2px var(--shp-accent);
        color: var(--shp-accent) !important;
    }
    
    .tpl-actions {
        position:absolute; bottom:0; left:0; right:0; background:rgba(255,255,255,0.95);
        backdrop-filter: blur(4px);
        padding: 0.6rem; display:flex; gap:0.5rem; justify-content:center;
        transform:translateY(100%); transition:transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border-top:1px solid var(--shp-border);
    }
    body[data-theme="dark"] .tpl-actions { background: rgba(15,23,42,0.95); }
    .tpl-card:hover .tpl-actions { transform:translateY(0); }
    
    /* Upload Zone */
    .upload-zone {
        border: 2px dashed var(--shp-border-strong);
        border-radius: 14px;
        background: rgba(0,0,0,0.01);
        transition: all 0.3s;
    }
    body[data-theme="dark"] .upload-zone { background: rgba(255,255,255,0.02); }
    .upload-zone:hover {
        background: var(--shp-accent-light);
        border-color: var(--shp-accent);
        border-style: solid;
        transform: translateY(-2px);
    }
    .upload-zone i { transition: transform 0.3s; }
    .upload-zone:hover i { transform: translateY(-4px); color: var(--shp-accent) !important; }
</style>
@endpush

@section('content')
<div class="page-wrap mt-4">
    
    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
        <div>
            <h4 class="mb-1 fw-bold" style="font-size:1.25rem; color:var(--shp-accent);">Pengaturan Global</h4>
            <div style="font-size:.85rem; color:var(--shp-muted);">Konfigurasi inti untuk pencetakan, logistik, dan sinkronisasi.</div>
        </div>
        <div>
            <button type="submit" form="ms-settings-form" class="btn btn-ship-primary btn-pill py-2">
                Simpan Perubahan
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert-success-custom">
            <i class="fas fa-check-circle fs-5"></i> {{ session('success') }}
        </div>
    @endif

    <div class="ms-tabs">
        <button type="button" class="ms-tab-btn active" onclick="showSec('cetak', this)"><i class="fas fa-print me-2"></i>Cetak & Logistik</button>
        <button type="button" class="ms-tab-btn" onclick="showSec('sync', this)"><i class="fas fa-sync-alt me-2"></i>Sinkronisasi</button>
        <button type="button" class="ms-tab-btn" onclick="showSec('fulfillment', this)"><i class="fas fa-box me-2"></i>Fulfillment</button>
        <a href="{{ route('marketplace.cache-monitor') }}" class="ms-tab-btn" style="text-decoration:none; color:inherit; display:flex; align-items:center; justify-content:center;"><i class="fas fa-hdd me-2"></i>Storage Cache</a>
    </div>

    <div class="ms-main">
        <div class="ms-side">
            <button type="button" class="ms-side-item active" onclick="showSec('cetak', this)">
                <div class="ms-side-ic"><i class="fas fa-print text-primary"></i></div>
                <div>
                    <div class="ms-side-label">Cetak & Logistik</div>
                    <div class="ms-side-desc">Resi, footer, dan branding</div>
                </div>
            </button>
            <button type="button" class="ms-side-item" onclick="showSec('sync', this)">
                <div class="ms-side-ic"><i class="fas fa-sync-alt text-success"></i></div>
                <div>
                    <div class="ms-side-label">Sinkronisasi</div>
                    <div class="ms-side-desc">Auto-sync order & stok</div>
                </div>
            </button>
            <button type="button" class="ms-side-item" onclick="showSec('fulfillment', this)">
                <div class="ms-side-ic"><i class="fas fa-box text-warning"></i></div>
                <div>
                    <div class="ms-side-label">Fulfillment</div>
                    <div class="ms-side-desc">Gudang & pemrosesan</div>
                </div>
            </button>
            <a href="{{ route('marketplace.cache-monitor') }}" class="ms-side-item" style="text-decoration:none; color:inherit;">
                <div class="ms-side-ic"><i class="fas fa-hdd text-info"></i></div>
                <div>
                    <div class="ms-side-label">Storage Cache</div>
                    <div class="ms-side-desc">Pemantauan kapasitas resi</div>
                </div>
            </a>
        </div>
        
        <div class="ms-content">
            <form id="ms-settings-form" action="{{ route('marketplace.settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                {{-- SECTION: CETAK & LOGISTIK --}}
                <div id="sec-cetak" class="card-main ms-section active">
                    <div class="card-header-custom">
                        <i class="fas fa-print fs-5 text-primary"></i>
                        <h5 class="card-title-custom">Cetak & Logistik</h5>
                    </div>
                    <div class="card-body-custom">
                        
                        <div class="settings-row row">
                            <div class="col-md-5 mb-3 mb-md-0">
                                <div class="settings-row-label">Format Resi Default</div>
                                <div class="settings-row-desc">Format kertas dasar saat mencetak pesanan untuk toko baru.</div>
                            </div>
                            <div class="col-md-7">
                                <select name="marketplace_print_default_format" class="form-select">
                                    <option value="THERMAL_AIR_WAYBILL" {{ $settings['marketplace_print_default_format'] === 'THERMAL_AIR_WAYBILL' ? 'selected' : '' }}>Thermal (100x150)</option>
                                    <option value="NORMAL_AIR_WAYBILL" {{ $settings['marketplace_print_default_format'] === 'NORMAL_AIR_WAYBILL' ? 'selected' : '' }}>A4 (Normal)</option>
                                </select>
                            </div>
                        </div>

                        <div class="settings-row row">
                            <div class="col-md-5 mb-3 mb-md-0">
                                <div class="settings-row-label">Branding Resi Sosial Media</div>
                                <div class="settings-row-desc">Tampilkan info sosial media di bagian bawah resi utama.</div>
                            </div>
                            <div class="col-md-7 d-flex align-items-center">
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" name="marketplace_print_branding" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="marketplace_print_branding" name="marketplace_print_branding" value="1" {{ ($settings['marketplace_print_branding'] ?? '0') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label ms-2" for="marketplace_print_branding">Aktifkan Branding</label>
                                </div>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="mb-3">
                                <div class="settings-row-label">Footer Template Dinamis</div>
                                <div class="settings-row-desc">Gambar desain yang diletakkan di area footer resi utama. Klik untuk memilih, lalu Simpan.</div>
                            </div>
                            
                            <!-- Tidak Pakai -->
                            <div class="mb-3">
                                <div class="form-check p-0">
                                    <input type="radio" name="marketplace_footer_template" id="fTpl_none" value="none" class="d-none peer-radio" {{ ($settings['marketplace_footer_template'] ?? 'none') == 'none' ? 'checked' : '' }}>
                                    <label class="w-100 border rounded-4 p-3 text-center cursor-pointer tpl-label tpl-label-none d-flex align-items-center justify-content-center" for="fTpl_none" style="background:var(--card-bg);">
                                        <i class="fas fa-ban text-muted me-2"></i>
                                        <span class="text-muted fw-bold" style="font-size:.9rem;">Tidak Pakai Footer Template</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Dynamic Full-Width Footers -->
                            @foreach($footerTemplates as $idx => $tpl)
                                <div class="mb-3">
                                    <div class="form-check p-0 tpl-card">
                                        <input type="radio" name="marketplace_footer_template" id="fTpl_{{ $idx }}" value="{{ $tpl }}" class="d-none peer-radio" {{ ($settings['marketplace_footer_template'] ?? 'none') == $tpl ? 'checked' : '' }}>
                                        <label class="w-100 p-3 cursor-pointer tpl-label bg-white m-0 d-block text-center" for="fTpl_{{ $idx }}">
                                            <img src="{{ asset('storage/templates/footers/' . $tpl) }}?v={{ time() }}" class="img-fluid rounded" alt="Footer {{ $idx }}" style="max-height: 100px; object-fit: contain;">
                                        </label>
                                        <div class="tpl-actions">
                                            <button type="button" class="btn btn-sm btn-light border py-1 px-3 text-danger" onclick="deleteTemplate('footer', '{{ $tpl }}')" title="Hapus Template"><i class="fas fa-trash me-1"></i> Hapus</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            
                            <!-- Add New Footer -->
                            <div class="mb-2">
                                <div class="upload-zone p-4 d-flex align-items-center justify-content-center">
                                    <input type="file" name="add_footer_template" id="addFooterTemplate" class="d-none" accept="image/png, image/jpeg, application/pdf" onchange="document.getElementById('ms-settings-form').submit();">
                                    <label for="addFooterTemplate" class="cursor-pointer d-flex flex-column align-items-center gap-2 mb-0 w-100 h-100">
                                        <i class="fas fa-cloud-upload-alt text-primary fs-3 opacity-75"></i>
                                        <span class="fw-bold" style="font-size:.9rem; color:var(--shp-accent)">Upload Footer Baru</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="settings-row row">
                            <div class="col-md-5 mb-3 mb-md-0">
                                <div class="settings-row-label">Teks Tambahan Footer</div>
                                <div class="settings-row-desc">Pesan kustom dan tata letak untuk bagian paling bawah resi.</div>
                            </div>
                            <div class="col-md-7">
                                <input type="text" name="marketplace_footer_greeting" class="form-control mb-2" placeholder="Contoh: Terima kasih telah berbelanja!" value="{{ old('marketplace_footer_greeting', $settings['marketplace_footer_greeting'] ?? '') }}">
                                <div class="d-flex gap-2">
                                    <select name="marketplace_footer_alignment" class="form-select w-50">
                                        <option value="C" {{ ($settings['marketplace_footer_alignment'] ?? 'C') == 'C' ? 'selected' : '' }}>Rata Tengah</option>
                                        <option value="L" {{ ($settings['marketplace_footer_alignment'] ?? 'C') == 'L' ? 'selected' : '' }}>Rata Kiri</option>
                                    </select>
                                    <select name="marketplace_footer_divider" class="form-select w-50">
                                        <option value="0" {{ ($settings['marketplace_footer_divider'] ?? '0') == '0' ? 'selected' : '' }}>Tanpa Garis Pemisah</option>
                                        <option value="1" {{ ($settings['marketplace_footer_divider'] ?? '0') == '1' ? 'selected' : '' }}>Dengan Garis Putus-putus</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="settings-row row">
                            <div class="col-md-5 mb-3 mb-md-0">
                                <div class="settings-row-label">Detail Pengirim</div>
                                <div class="settings-row-desc">Nama toko dan nomor kontak yang dicetak tebal.</div>
                            </div>
                            <div class="col-md-7">
                                <input type="text" name="marketplace_sender_name" class="form-control mb-2" placeholder="Nama Toko (Contoh: Greatfit.id)" value="{{ old('marketplace_sender_name', $settings['marketplace_sender_name'] ?? '') }}">
                                <input type="text" name="marketplace_sender_phone" class="form-control" placeholder="No. Telepon / WA" value="{{ old('marketplace_sender_phone', $settings['marketplace_sender_phone'] ?? '') }}">
                            </div>
                        </div>

                        <div class="settings-row row border-bottom-0">
                            <div class="col-md-5 mb-3 mb-md-0">
                                <div class="settings-row-label">Akun Sosial Media</div>
                                <div class="settings-row-desc">Ditampilkan sejajar di bawah resi (jika Branding aktif).</div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2 fw-bold" onclick="addSocialMedia()"><i class="fas fa-plus me-1"></i> Tambah Akun</button>
                            </div>
                            <div class="col-md-7">
                                <div id="social-media-container" class="d-flex flex-column gap-2">
                                    <!-- JS will populate rows here -->
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>

                {{-- SECTION: KARTU UCAPAN (New Card) --}}
                <div id="sec-cetak-greeting" class="card-main ms-section active mt-4">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-envelope-open-text fs-5 text-primary"></i>
                            <h5 class="card-title-custom">Kartu Ucapan (100x150mm)</h5>
                        </div>
                        <button type="button" class="btn btn-sm btn-light border fw-bold" onclick="printSampleGreeting()">
                            <i class="fas fa-eye me-1"></i> Lihat Preview PDF
                        </button>
                    </div>
                    <div class="card-body-custom">
                        <div class="settings-row row">
                            <div class="col-md-5 mb-3 mb-md-0">
                                <div class="settings-row-label">Cetak Kartu Ekstra</div>
                                <div class="settings-row-desc">Otomatis mencetak 1 halaman tambahan berisi kartu ucapan/perhatian setelah setiap resi dicetak.</div>
                            </div>
                            <div class="col-md-7 d-flex align-items-center">
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" name="marketplace_print_greeting_card" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="marketplace_print_greeting_card" name="marketplace_print_greeting_card" value="1" {{ ($settings['marketplace_print_greeting_card'] ?? '0') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label ms-2" for="marketplace_print_greeting_card">Aktifkan Halaman Tambahan</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-row row border-bottom-0 pb-4">
                            <div class="col-md-12 mb-3">
                                <div class="settings-row-label">Galeri Kartu Ucapan Dinamis</div>
                                <div class="settings-row-desc mb-3">Pilih desain mana yang akan dicetak sebagai kartu ucapan. Tambah, edit, atau hapus sesuka hati.</div>
                            </div>
                            <div class="col-md-12">
                                <div class="row row-cols-2 row-cols-md-4 g-3">
                                    @foreach($greetingTemplates as $idx => $tpl)
                                        @php $tplId = str_replace(['.png', '.jpg', '.jpeg', '.pdf'], '', $tpl); @endphp
                                        <div class="col">
                                            <div class="form-check p-0 tpl-card h-100">
                                                <input type="radio" name="marketplace_greeting_card_template" id="gTpl_{{ $idx }}" value="{{ $tpl }}" class="d-none peer-radio" {{ ($settings['marketplace_greeting_card_template'] ?? 'template_1.png') == $tpl || ($settings['marketplace_greeting_card_template'] ?? '1') == str_replace('template_', '', $tplId) ? 'checked' : '' }}>
                                                <label class="w-100 h-100 p-2 cursor-pointer tpl-label bg-white m-0 d-flex flex-column align-items-center" for="gTpl_{{ $idx }}">
                                                    <div class="text-xs text-center fw-bold mb-2 text-truncate w-100" title="{{ $tpl }}" style="color:var(--shp-accent)">{{ Str::title(str_replace(['_', '.png', '.jpg', '.jpeg', 'template'], [' ', '', '', '', ''], $tpl)) }}</div>
                                                    <img src="{{ asset('storage/templates/greetings/' . $tpl) }}?v={{ time() }}" class="img-fluid rounded" alt="Greeting {{ $idx }}" style="max-height: 140px; object-fit: contain;">
                                                </label>
                                                <div class="tpl-actions">
                                                    <button type="button" class="btn btn-sm btn-light border py-1 px-2 text-danger w-100" onclick="deleteTemplate('greeting', '{{ $tpl }}')" title="Hapus Template"><i class="fas fa-trash me-1"></i> Hapus</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    
                                    <!-- Tambah Template Baru -->
                                    <div class="col">
                                        <div class="upload-zone h-100 p-3 d-flex flex-column align-items-center justify-content-center" style="min-height:180px;">
                                            <input type="file" name="add_greeting_template" id="addGreetingTemplate" class="d-none" accept="image/png, image/jpeg, application/pdf" onchange="document.getElementById('ms-settings-form').submit();">
                                            <label for="addGreetingTemplate" class="cursor-pointer text-center w-100 h-100 d-flex flex-column align-items-center justify-content-center mb-0">
                                                <i class="fas fa-cloud-upload-alt text-primary mb-3 opacity-75" style="font-size: 2.5rem;"></i>
                                                <div class="fw-bold" style="font-size:.95rem; color:var(--shp-accent)">Tambah Baru</div>
                                                <div class="text-muted mt-1" style="font-size:.75rem;">Klik untuk upload</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION: SINKRONISASI --}}
                <div id="sec-sync" class="card-main ms-section">
                    <div class="card-header-custom">
                        <i class="fas fa-sync-alt fs-5 text-success"></i>
                        <h5 class="card-title-custom">Sinkronisasi Sistem</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="settings-row row">
                            <div class="col-md-5 mb-3 mb-md-0">
                                <div class="settings-row-label">Auto-Sync Order Baru</div>
                                <div class="settings-row-desc">Otomatis menarik pesanan baru ke sistem GFID di background.</div>
                            </div>
                            <div class="col-md-7 d-flex align-items-center">
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" name="marketplace_auto_sync" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="marketplace_auto_sync" name="marketplace_auto_sync" value="1" {{ ($settings['marketplace_auto_sync'] ?? '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label ms-2" for="marketplace_auto_sync">Aktifkan Sinkronisasi Latar Belakang</label>
                                </div>
                            </div>
                        </div>

                        <div class="settings-row row border-bottom-0 pb-4">
                            <div class="col-md-5 mb-3 mb-md-0">
                                <div class="settings-row-label">Auto-Push Sinkronisasi Stok</div>
                                <div class="settings-row-desc">Selalu pastikan stok marketplace di-update sesuai dengan ketersediaan gudang.</div>
                            </div>
                            <div class="col-md-7 d-flex align-items-center">
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" name="marketplace_auto_push_stock" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="marketplace_auto_push_stock" name="marketplace_auto_push_stock" value="1" {{ ($settings['marketplace_auto_push_stock'] ?? '1') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label ms-2" for="marketplace_auto_push_stock">Aktifkan Potong Stok Otomatis</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECTION: FULFILLMENT --}}
                <div id="sec-fulfillment" class="card-main ms-section">
                    <div class="card-header-custom">
                        <i class="fas fa-box fs-5 text-warning"></i>
                        <h5 class="card-title-custom">Fulfillment (Pemenuhan)</h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="settings-row row">
                            <div class="col-md-5 mb-3 mb-md-0">
                                <div class="settings-row-label">Gudang Default Global</div>
                                <div class="settings-row-desc">Gudang fallback jika toko belum diset gudangnya.</div>
                            </div>
                            <div class="col-md-7">
                                <select name="marketplace_default_warehouse" class="form-select">
                                    <option value="">— Tidak Ada (Wajib set per toko) —</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}" {{ ($settings['marketplace_default_warehouse'] ?? '') == $wh->id ? 'selected' : '' }}>{{ $wh->name }} ({{ $wh->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="settings-row row border-bottom-0 pb-4">
                            <div class="col-md-5 mb-3 mb-md-0">
                                <div class="settings-row-label">Auto-Process Orders</div>
                                <div class="settings-row-desc">Pesanan yang stoknya tersedia akan langsung dilempar ke status Siap Packing tanpa direview manual.</div>
                            </div>
                            <div class="col-md-7 d-flex align-items-center">
                                <div class="form-check form-switch mb-0">
                                    <input type="hidden" name="marketplace_auto_process_orders" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="marketplace_auto_process_orders" name="marketplace_auto_process_orders" value="1" {{ ($settings['marketplace_auto_process_orders'] ?? '0') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label ms-2" for="marketplace_auto_process_orders">Otomatis Proses Pesanan</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
            
            <!-- Hidden form for deleting templates -->
            <form id="delete-template-form" action="{{ route('marketplace.settings.delete_template') }}" method="POST" class="d-none">
                @csrf
                <input type="hidden" name="type" id="delete_type">
                <input type="hidden" name="filename" id="delete_filename">
            </form>
            
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Tab & Section switching logic
    function showSec(secId, btnElement = null) {
        // Hide all sections
        document.querySelectorAll('.ms-section').forEach(el => el.classList.remove('active'));
        // Show target sections (cetak has two sections now)
        if(secId === 'cetak') {
            document.getElementById('sec-cetak').classList.add('active');
            document.getElementById('sec-cetak-greeting').classList.add('active');
        } else {
            document.getElementById('sec-' + secId).classList.add('active');
        }
        
        // Remove active class from all sidebar items and tabs
        document.querySelectorAll('.ms-side-item, .ms-tab-btn').forEach(el => el.classList.remove('active'));
        
        // Add active class to clicked button
        if(btnElement) {
            btnElement.classList.add('active');
            
            // Sync active state between mobile tabs and sidebar
            const index = Array.from(btnElement.parentElement.children).indexOf(btnElement);
            if(btnElement.classList.contains('ms-tab-btn')) {
                const sideItems = document.querySelectorAll('.ms-side-item');
                if(sideItems[index]) sideItems[index].classList.add('active');
            } else {
                const tabItems = document.querySelectorAll('.ms-tab-btn');
                if(tabItems[index]) tabItems[index].classList.add('active');
            }
        }
    }

    // Social Media Rows Logic
    const container = document.getElementById('social-media-container');
    const existingAccounts = {!! $settings['marketplace_social_accounts'] ?? '[]' !!};
    
    function addSocialMedia(platform = 'Instagram', username = '') {
        const row = document.createElement('div');
        row.className = 'd-flex gap-2 align-items-center mb-2';
        row.innerHTML = `
            <select name="social_platforms[]" class="form-select bg-light" style="width:130px;">
                <option value="IG" ${platform==='IG'||platform==='Instagram'?'selected':''}>Instagram</option>
                <option value="TT" ${platform==='TT'||platform==='TikTok'?'selected':''}>TikTok</option>
                <option value="FB" ${platform==='FB'||platform==='Facebook'?'selected':''}>Facebook</option>
                <option value="Web" ${platform==='Web'||platform==='Website'?'selected':''}>Website</option>
                <option value="WA" ${platform==='WA'||platform==='WhatsApp'?'selected':''}>WhatsApp</option>
            </select>
            <input type="text" name="social_usernames[]" class="form-control flex-grow-1" placeholder="Username / URL" value="${username}">
            <button type="button" class="btn btn-light border text-danger" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(row);
    }
    
    // Delete template logic
    function deleteTemplate(type, filename) {
        if(confirm('Yakin ingin menghapus template ini secara permanen?')) {
            document.getElementById('delete_type').value = type;
            document.getElementById('delete_filename').value = filename;
            document.getElementById('delete-template-form').submit();
        }
    }
    
    // Preview sample greeting
    function printSampleGreeting() {
        let selectedTpl = '1';
        const radios = document.querySelectorAll('input[name="marketplace_greeting_card_template"]');
        for (const radio of radios) {
            if (radio.checked) {
                selectedTpl = radio.value;
                break;
            }
        }
        window.open('{{ route("marketplace.settings.sample_greeting") }}?template=' + selectedTpl, '_blank');
    }
    
    // Populate social media
    if (existingAccounts.length > 0) {
        existingAccounts.forEach(acc => addSocialMedia(acc.platform, acc.username));
    } else {
        const legacyPlat = "{{ $settings['marketplace_social_platform'] ?? '' }}";
        const legacyUser = "{{ $settings['marketplace_social_username'] ?? '' }}";
        if (legacyUser) addSocialMedia(legacyPlat, legacyUser);
    }
</script>
@endpush
