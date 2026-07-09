@extends('layouts.app')
@section('title', 'Marketplace • Pengaturan Global')

@include('marketplace._shared')

@push('head')
<style>
    :root{
        --shp-accent:#334155;
        --shp-accent-2:#1f2937;
        --shp-border:rgba(148,163,184,.20);
        --shp-border-strong:rgba(148,163,184,.30);
        --shp-muted:#64748b;
        --shp-bg-hover:rgba(148,163,184,.08);
    }
    .page-wrap{ max-width:1100px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }

    /* Layout Induk */
    .ms-main { display:grid; grid-template-columns:minmax(0,1fr); gap:2rem; align-items:start; }
    @media(min-width:992px){ .ms-main { grid-template-columns:260px minmax(0,1fr); } }

    /* Sidebar Navigation */
    .ms-side { display:none; }
    @media(min-width:992px){ .ms-side { display:block; position:sticky; top:2rem; } }
    .ms-side-item { 
        display:flex; align-items:center; gap:.75rem; width:100%; text-align:left; 
        padding:.75rem 1rem; border:none; background:transparent; border-radius:10px; 
        cursor:pointer; transition:all .2s ease; margin-bottom:.4rem; 
    }
    .ms-side-item:hover { background:var(--shp-bg-hover); }
    .ms-side-item.active { background:var(--shp-accent); box-shadow:0 4px 6px -1px rgba(0,0,0,0.1); }
    .ms-side-item.active .ms-side-label { color:#fff; }
    .ms-side-item.active .ms-side-desc { color:rgba(255,255,255,.8); }
    .ms-side-ic { font-size:1.25rem; flex-shrink:0; width:28px; text-align:center; }
    .ms-side-label { font-size:.85rem; font-weight:700; color:var(--shp-accent); line-height:1.2; }
    .ms-side-desc { font-size:.7rem; color:var(--shp-muted); line-height:1.3; margin-top:3px; }
    body[data-theme="dark"] .ms-side-label { color:#e5e7eb; }
    body[data-theme="dark"] .ms-side-item.active .ms-side-label { color:#fff; }

    /* Card Panels */
    .card-main{
        background: var(--card, #fff);
        border-radius: 12px;
        border: 1px solid var(--shp-border);
        box-shadow: 0 1px 3px rgba(15,23,42,.05);
        overflow:hidden;
        margin-bottom: 1.5rem;
        transition: opacity .2s;
    }
    body[data-theme="dark"] .card-main{
        border-color: rgba(51,65,85,.85);
        background: #0f172a;
    }
    .card-header-custom {
        padding: 1.25rem 1.5rem;
        background: rgba(248,250,252,.5);
        border-bottom: 1px solid var(--shp-border);
        display: flex; align-items: center; gap: .75rem;
    }
    body[data-theme="dark"] .card-header-custom { background: rgba(15,23,42,.6); }
    .card-title-custom { font-size: 1rem; font-weight: 700; color: var(--shp-accent); margin:0; }
    body[data-theme="dark"] .card-title-custom { color: #e5e7eb; }
    .card-body-custom { padding: 0 1.5rem; }

    /* Form Elements & Layout */
    .settings-row {
        padding: 1.5rem 0;
        border-bottom: 1px solid var(--shp-border);
    }
    .settings-row:last-child { border-bottom: none; }
    .settings-row-label { font-weight: 700; color: var(--shp-accent); font-size: .9rem; margin-bottom: .25rem; }
    .settings-row-desc { font-size: .8rem; color: var(--shp-muted); line-height: 1.4; }
    body[data-theme="dark"] .settings-row-label { color: #d1d5db; }
    
    .form-control, .form-select {
        border-radius: 8px; 
        border: 1px solid var(--shp-border-strong);
        font-size: .85rem;
        padding: .6rem .85rem;
        background: var(--card, #fff);
        color: var(--shp-accent);
        transition: all .2s;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--shp-accent);
        box-shadow: 0 0 0 3px rgba(51,65,85,.1);
    }
    body[data-theme="dark"] .form-control, body[data-theme="dark"] .form-select {
        background: #1e293b; color: #f3f4f6; border-color: rgba(71,85,105,.8);
    }

    /* Switch */
    .form-switch .form-check-input {
        width: 3rem;
        height: 1.5rem;
        cursor: pointer;
    }
    .form-switch .form-check-input:checked {
        background-color: var(--shp-accent);
        border-color: var(--shp-accent);
    }

    /* Buttons */
    .btn-pill{ border-radius:8px; padding-inline:1rem; box-shadow:none!important; font-weight:600; font-size:.85rem; }
    .btn-ship-primary{ background:var(--shp-accent)!important; border-color:var(--shp-accent)!important; color:#fff!important; }
    .btn-ship-primary:hover{ background:var(--shp-accent-2)!important; border-color:var(--shp-accent-2)!important; color:#fff!important; transform:translateY(-1px); }
    
    .ms-section { display: none; }
    .ms-section.active { display: block; animation: ms-fade .3s cubic-bezier(0.16, 1, 0.3, 1); }
    @keyframes ms-fade { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }

    /* Mobile Tabs */
    .ms-tabs { display:flex; overflow-x:auto; gap:.5rem; margin-bottom:1.5rem; padding-bottom:.5rem; }
    .ms-tabs::-webkit-scrollbar { height: 4px; }
    @media(min-width:992px){ .ms-tabs { display:none; } }
    .ms-tab-btn {
        padding: .5rem 1rem; border-radius: 8px; border: 1px solid var(--shp-border);
        background: transparent; color: var(--shp-muted); font-size: .8rem; font-weight: 700;
        white-space: nowrap; transition: all .2s;
    }
    .ms-tab-btn.active { background: var(--shp-accent); color: #fff; border-color: var(--shp-accent); }
    
    .alert-success-custom {
        background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534;
        padding: 1rem 1.25rem; border-radius: 10px; font-size: .85rem; margin-bottom: 1.5rem; font-weight: 600;
        display:flex; align-items:center; gap:.5rem;
    }
    
    .tpl-label { transition: all .2s; cursor:pointer; }
    /* For non-card templates (e.g. "Tidak Pakai") */
    .peer-radio:checked + .tpl-label { border-color: var(--shp-accent) !important; background-color: rgba(51,65,85, 0.05); box-shadow: 0 0 0 2px var(--shp-accent); }
    
    .tpl-card { position:relative; overflow:hidden; border-radius:10px; border:2px solid var(--shp-border); transition: all .2s; cursor:pointer; }
    .tpl-card:hover { border-color: var(--shp-accent); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
    
    /* Selected state for tpl-card (uses :has to detect checked radio inside) */
    .tpl-card:has(.peer-radio:checked) { 
        border-color: #10b981 !important; 
        box-shadow: 0 0 0 2px #10b981, 0 4px 12px rgba(16,185,129,.15); 
    }
    .tpl-card:has(.peer-radio:checked)::after {
        content: '✓ Terpilih';
        position: absolute; top: 6px; right: 6px;
        background: #10b981; color: #fff; font-size: .65rem; font-weight: 700;
        padding: 2px 8px; border-radius: 20px; z-index: 2;
        letter-spacing: .02em;
    }
    
    .tpl-actions {
        position:absolute; bottom:0; left:0; right:0; background:rgba(255,255,255,0.95);
        padding:.5rem; display:flex; gap:.25rem; justify-content:center;
        transform:translateY(100%); transition:transform .2s;
        border-top:1px solid var(--shp-border);
    }
    .tpl-card:hover .tpl-actions { transform:translateY(0); }
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
                                    <label class="w-100 border rounded-3 p-3 text-center cursor-pointer tpl-label d-flex align-items-center justify-content-center" for="fTpl_none" style="background:#f8fafc;">
                                        <i class="fas fa-ban text-muted me-2"></i>
                                        <span class="text-muted fw-bold" style="font-size:.85rem;">Tidak Pakai Footer Template</span>
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
                                <div class="border rounded-3 p-3 d-flex align-items-center justify-content-center" style="border-style: dashed !important; background: rgba(0,0,0,0.02);">
                                    <input type="file" name="add_footer_template" id="addFooterTemplate" class="d-none" accept="image/png, image/jpeg, application/pdf" onchange="document.getElementById('ms-settings-form').submit();">
                                    <label for="addFooterTemplate" class="cursor-pointer d-flex align-items-center gap-2 mb-0">
                                        <i class="fas fa-cloud-upload-alt text-primary"></i>
                                        <span class="fw-bold" style="font-size:.85rem; color:var(--shp-accent)">Upload Footer Baru</span>
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
                                        <div class="h-100 border rounded p-2 d-flex flex-column align-items-center justify-content-center border-dashed" style="border-style: dashed !important; background: rgba(0,0,0,0.02); min-height:180px;">
                                            <input type="file" name="add_greeting_template" id="addGreetingTemplate" class="d-none" accept="image/png, image/jpeg, application/pdf" onchange="document.getElementById('ms-settings-form').submit();">
                                            <label for="addGreetingTemplate" class="cursor-pointer text-center w-100 h-100 d-flex flex-column align-items-center justify-content-center mb-0">
                                                <i class="fas fa-cloud-upload-alt text-primary mb-2 opacity-75" style="font-size: 2.5rem;"></i>
                                                <div class="fw-bold" style="font-size:.9rem; color:var(--shp-accent)">Tambah Baru</div>
                                                <div class="text-muted mt-1" style="font-size:.7rem;">Klik untuk upload</div>
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
