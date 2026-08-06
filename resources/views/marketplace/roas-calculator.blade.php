@extends('layouts.app')
@section('title', 'Marketplace • ROAS Calculator')

@push('head')
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<!-- Tom Select for better dropdowns -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    :root {
        --roas-bg-light: #f8fafc;
        --roas-bg-dark: #0f172a;
        --roas-card-light: rgba(255, 255, 255, 0.85);
        --roas-card-dark: rgba(30, 41, 59, 0.75);
        --roas-glass-border-light: rgba(255, 255, 255, 0.4);
        --roas-glass-border-dark: rgba(255, 255, 255, 0.08);
        --roas-primary: #3b82f6;
        --roas-primary-hover: #2563eb;
        --roas-success: #10b981;
        --roas-warning: #f59e0b;
        --roas-danger: #ef4444;
        --roas-text-main-light: #1e293b;
        --roas-text-muted-light: #64748b;
        --roas-text-main-dark: #f1f5f9;
        --roas-text-muted-dark: #94a3b8;
    }

    .roas-container {
        font-family: 'Outfit', sans-serif;
        max-width: 1300px;
        margin: 0 auto;
        padding: 2rem 1rem;
        min-height: calc(100vh - 100px);
        position: relative;
        z-index: 1;
    }

    /* Animated Background Blobs */
    .roas-bg-shape {
        position: fixed;
        border-radius: 50%;
        filter: blur(100px);
        z-index: -1;
        opacity: 0.5;
        animation: float 20s infinite ease-in-out alternate;
    }
    
    body[data-theme="dark"] .roas-bg-shape {
        opacity: 0.2;
    }

    .shape-1 {
        top: -10%; left: -10%; width: 50vw; height: 50vw;
        background: radial-gradient(circle, rgba(59,130,246,0.4) 0%, rgba(147,197,253,0) 70%);
        animation-delay: 0s;
    }
    .shape-2 {
        bottom: -20%; right: -10%; width: 60vw; height: 60vw;
        background: radial-gradient(circle, rgba(16,185,129,0.3) 0%, rgba(167,243,208,0) 70%);
        animation-delay: -5s;
    }
    .shape-3 {
        top: 40%; left: 40%; width: 40vw; height: 40vw;
        background: radial-gradient(circle, rgba(139,92,246,0.3) 0%, rgba(196,181,253,0) 70%);
        animation-delay: -10s;
    }

    @keyframes float {
        0% { transform: translate(0, 0) scale(1); }
        50% { transform: translate(5%, 10%) scale(1.1); }
        100% { transform: translate(-5%, -5%) scale(0.9); }
    }

    /* Glassmorphism Cards */
    .glass-card {
        background: var(--roas-card-light);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid var(--roas-glass-border-light);
        border-radius: 24px;
        padding: 2rem;
        box-shadow: 0 10px 40px -10px rgba(0,0,0,0.08);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease;
    }
    body[data-theme="dark"] .glass-card {
        background: var(--roas-card-dark);
        border: 1px solid var(--roas-glass-border-dark);
        box-shadow: 0 10px 40px -10px rgba(0,0,0,0.3);
    }
    .glass-card:hover { box-shadow: 0 20px 40px -10px rgba(0,0,0,0.12); }
    body[data-theme="dark"] .glass-card:hover { box-shadow: 0 20px 40px -10px rgba(0,0,0,0.4); }

    /* Typography */
    .roas-title {
        font-size: 2.75rem; font-weight: 800;
        background: linear-gradient(135deg, #1e293b, #3b82f6);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        margin-bottom: 0.5rem; letter-spacing: -0.02em;
    }
    body[data-theme="dark"] .roas-title {
        background: linear-gradient(135deg, #f8fafc, #60a5fa);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .roas-subtitle { color: var(--roas-text-muted-light); font-size: 1.1rem; font-weight: 400; margin-bottom: 3rem; }
    body[data-theme="dark"] .roas-subtitle { color: var(--roas-text-muted-dark); }

    /* Layout */
    .roas-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
    @media (max-width: 991px) { .roas-grid { grid-template-columns: 1fr; } }

    /* Form Inputs */
    .input-group-custom { position: relative; margin-bottom: 1.25rem; }
    .input-group-custom label {
        display: block; font-weight: 600; margin-bottom: 0.4rem;
        color: var(--roas-text-main-light); font-size: 0.95rem; transition: color 0.3s ease;
    }
    body[data-theme="dark"] .input-group-custom label { color: var(--roas-text-main-dark); }

    .input-wrapper { position: relative; display: flex; align-items: center; }
    .input-prefix, .input-suffix {
        position: absolute; color: var(--roas-text-muted-light);
        font-weight: 600; font-size: 1.1rem; z-index: 10;
    }
    body[data-theme="dark"] .input-prefix, body[data-theme="dark"] .input-suffix { color: var(--roas-text-muted-dark); }
    .input-prefix { left: 1rem; } .input-suffix { right: 1rem; }

    .glass-input {
        width: 100%; background: rgba(255, 255, 255, 0.5);
        border: 2px solid rgba(226, 232, 240, 0.8); border-radius: 12px;
        padding: 0.75rem 1rem; font-size: 1.1rem; font-weight: 700;
        font-family: 'Outfit', monospace; color: var(--roas-text-main-light);
        transition: all 0.3s ease; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
    }
    .glass-input.has-prefix { padding-left: 2.5rem; }
    .glass-input.has-suffix { padding-right: 2.5rem; text-align: right; }
    
    .glass-input[readonly] { background: rgba(241, 245, 249, 0.8); cursor: not-allowed; opacity: 0.8; border-color: rgba(226, 232, 240, 0.5); }

    body[data-theme="dark"] .glass-input {
        background: rgba(15, 23, 42, 0.5); border-color: rgba(51, 65, 85, 0.8); color: var(--roas-text-main-dark);
    }
    body[data-theme="dark"] .glass-input[readonly] { background: rgba(15, 23, 42, 0.8); border-color: rgba(51, 65, 85, 0.4); }

    .glass-input:focus:not([readonly]) {
        outline: none; border-color: var(--roas-primary);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); background: #fff;
    }
    body[data-theme="dark"] .glass-input:focus:not([readonly]) {
        background: rgba(15, 23, 42, 0.9); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25);
    }

    /* Action Banner */
    .fetch-banner {
        background: rgba(59, 130, 246, 0.08);
        border: 1px dashed rgba(59, 130, 246, 0.4);
        border-radius: 16px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    body[data-theme="dark"] .fetch-banner {
        background: rgba(59, 130, 246, 0.05);
        border-color: rgba(59, 130, 246, 0.2);
    }
    
    .date-row {
        display: flex;
        gap: 1rem;
    }
    
    .date-row > div { flex: 1; }
    
    .btn-fetch {
        background: var(--roas-primary);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        font-weight: 700;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
    }
    .btn-fetch:hover {
        background: var(--roas-primary-hover);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
    }
    .btn-fetch:disabled {
        background: #94a3b8;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    /* Result Blocks */
    .result-block {
        display: flex; flex-direction: column; justify-content: center; align-items: center;
        padding: 1.5rem; border-radius: 20px; text-align: center;
        position: relative; overflow: hidden; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .result-block::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
        transform: translateX(-100%); transition: transform 0.6s ease;
    }
    .result-block:hover::before { transform: translateX(100%); }

    .result-block.main-result {
        grid-column: 1 / -1; background: linear-gradient(135deg, var(--roas-primary), #8b5cf6);
        color: white; padding: 2rem; box-shadow: 0 15px 30px rgba(59, 130, 246, 0.3);
    }
    .result-label { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.9; margin-bottom: 0.25rem; }
    .result-value { font-size: 2.2rem; font-weight: 800; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .main-result .result-value { font-size: 3.5rem; text-shadow: 0 4px 12px rgba(0,0,0,0.1); }

    .sub-results { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; }
    .sub-result-card {
        background: rgba(241, 245, 249, 0.7); border-radius: 16px; padding: 1.25rem 1rem;
        text-align: center; border: 1px solid rgba(226, 232, 240, 0.8);
    }
    body[data-theme="dark"] .sub-result-card { background: rgba(30, 41, 59, 0.6); border-color: rgba(51, 65, 85, 0.8); }
    
    .sub-result-value { font-size: 1.6rem; font-weight: 800; margin-top: 0.25rem; color: var(--roas-text-main-light); }
    body[data-theme="dark"] .sub-result-value { color: var(--roas-text-main-dark); }

    /* Status Colors */
    .status-excellent { color: var(--roas-success) !important; }
    .status-good { color: var(--roas-primary) !important; }
    .status-warning { color: var(--roas-warning) !important; }
    .status-danger { color: var(--roas-danger) !important; }
    .main-result.status-excellent { background: linear-gradient(135deg, #059669, #10b981); box-shadow: 0 15px 30px rgba(16, 185, 129, 0.3); }
    .main-result.status-warning { background: linear-gradient(135deg, #d97706, #f59e0b); box-shadow: 0 15px 30px rgba(245, 158, 11, 0.3); }
    .main-result.status-danger { background: linear-gradient(135deg, #b91c1c, #ef4444); box-shadow: 0 15px 30px rgba(239, 68, 68, 0.3); }

    .info-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 18px; height: 18px; border-radius: 50%; background: rgba(148, 163, 184, 0.2);
        color: var(--roas-text-muted-light); font-size: 0.7rem; margin-left: 0.4rem;
        cursor: help; transition: all 0.2s;
    }
    body[data-theme="dark"] .info-icon { color: var(--roas-text-muted-dark); }
    .info-icon:hover { background: var(--roas-primary); color: white; }

    .range-slider {
        -webkit-appearance: none; width: 100%; height: 8px; border-radius: 4px;
        background: #e2e8f0; outline: none; margin-top: 0.5rem;
    }
    body[data-theme="dark"] .range-slider { background: #334155; }
    .range-slider::-webkit-slider-thumb {
        -webkit-appearance: none; appearance: none; width: 20px; height: 20px;
        border-radius: 50%; background: var(--roas-primary); cursor: pointer;
        box-shadow: 0 2px 6px rgba(59,130,246,0.4); transition: transform 0.1s;
    }
    .range-slider::-webkit-slider-thumb:hover { transform: scale(1.1); }
    
    .chart-container { position: relative; height: 250px; width: 100%; margin-top: 1rem; }
    
    /* Tom Select Custom Overrides */
    .ts-wrapper.glass-select .ts-control {
        background: rgba(255, 255, 255, 0.5);
        border: 2px solid rgba(226, 232, 240, 0.8);
        border-radius: 12px;
        padding: 0.75rem 1rem;
        font-family: 'Outfit', sans-serif;
        font-size: 1.05rem;
        font-weight: 600;
        box-shadow: none;
        transition: all 0.3s ease;
    }
    body[data-theme="dark"] .ts-wrapper.glass-select .ts-control {
        background: rgba(15, 23, 42, 0.5); border-color: rgba(51, 65, 85, 0.8); color: #f1f5f9;
    }
    .ts-wrapper.glass-select.focus .ts-control {
        border-color: var(--roas-primary);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); background: #fff;
    }
    body[data-theme="dark"] .ts-wrapper.glass-select.focus .ts-control {
        background: rgba(15, 23, 42, 0.9); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25);
    }
    .ts-dropdown { border-radius: 12px; font-family: 'Outfit', sans-serif; }
    
    .loading-overlay {
        position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(255,255,255,0.7); backdrop-filter: blur(4px);
        border-radius: 24px; display: flex; flex-direction: column;
        justify-content: center; align-items: center; z-index: 100;
        opacity: 0; pointer-events: none; transition: opacity 0.3s;
    }
    body[data-theme="dark"] .loading-overlay { background: rgba(15,23,42,0.7); }
    .loading-overlay.active { opacity: 1; pointer-events: all; }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
@endpush

@section('content')
<div class="roas-bg-shape shape-1"></div>
<div class="roas-bg-shape shape-2"></div>
<div class="roas-bg-shape shape-3"></div>

<div class="roas-container" id="roasApp">
    <div class="text-center">
        <h1 class="roas-title">Marketplace ROAS Calculator</h1>
        <p class="roas-subtitle">Simulasi performa iklan spesifik per produk & terhubung langsung dengan Analisa Iklan.</p>
    </div>

    <div class="roas-grid">
        <!-- Input Section -->
        <div class="glass-card d-flex flex-column gap-3 position-relative">
            
            <div class="loading-overlay" id="loadingOverlay">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-3 fw-bold" style="color: var(--roas-text-main-light)">Menarik Data Iklan...</div>
            </div>

            <div>
                <h5 class="fw-bold mb-3" style="color: var(--roas-text-main-light)"><i class="bi bi-cloud-download me-2 text-primary"></i> Ambil Data Riwayat</h5>
                
                <div class="fetch-banner">
                    <div class="input-group-custom mb-0">
                        <label for="productSelect">Pilih Produk (Master)</label>
                        <select id="productSelect" class="glass-select" placeholder="Cari kode atau nama produk...">
                            <option value="">Pilih Produk...</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" data-hpp="{{ $p->hpp }}">{{ $p->code }} - {{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="date-row">
                        <div class="input-group-custom mb-0">
                            <label for="dateFrom">Dari Tanggal</label>
                            <input type="date" id="dateFrom" class="glass-input" value="{{ now()->subDays(30)->toDateString() }}">
                        </div>
                        <div class="input-group-custom mb-0">
                            <label for="dateTo">Sampai</label>
                            <input type="date" id="dateTo" class="glass-input" value="{{ now()->toDateString() }}">
                        </div>
                    </div>
                    
                    <button type="button" class="btn-fetch" id="btnFetchAds" disabled>
                        <i class="bi bi-magic"></i> Tarik Data dari Analisa Iklan
                    </button>
                    <div id="fetchAlert" class="small mt-1 fw-semibold" style="display:none;"></div>
                </div>

                <div class="row g-3 mb-2">
                    <div class="col-md-6">
                        <div class="input-group-custom">
                            <label for="hpp">HPP per Unit (Otomatis)</label>
                            <div class="input-wrapper">
                                <span class="input-prefix">Rp</span>
                                <input type="text" id="hpp" class="glass-input has-prefix" value="0" readonly title="HPP diambil dari master produk">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group-custom">
                            <label for="sellingPrice">Harga Jual per Unit <i class="bi bi-info-circle info-icon" title="Jika data ditarik otomatis, harga ini dihitung dari GMV dibagi Qty"></i></label>
                            <div class="input-wrapper">
                                <span class="input-prefix">Rp</span>
                                <input type="text" id="sellingPrice" class="glass-input has-prefix" value="0" inputmode="numeric">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group-custom">
                            <label for="qtySold">Target Qty Terjual</label>
                            <div class="input-wrapper">
                                <input type="text" id="qtySold" class="glass-input has-suffix" value="1" inputmode="numeric">
                                <span class="input-suffix">Pcs</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group-custom">
                            <label for="adSpend">Biaya Iklan (Sblm PPN) <i class="bi bi-info-circle info-icon" title="Nominal yang Anda isi di dashboard iklan (belum termasuk PPN 11%)"></i></label>
                            <div class="input-wrapper">
                                <span class="input-prefix">Rp</span>
                                <input type="text" id="adSpend" class="glass-input has-prefix" value="0" inputmode="numeric">
                            </div>
                            <small class="d-block mt-1 text-muted" style="font-size: 0.75rem;">Total (Inc. PPN 11%): <span id="adSpendIncPpn" class="fw-bold text-primary">Rp 0</span></small>
                        </div>
                    </div>
                </div>

                <div class="input-group-custom mt-3">
                     <div class="d-flex justify-content-between align-items-end mb-2">
                        <label for="marketplaceFee" class="mb-0">Fee Platform (Admin + Layanan)</label>
                        <span class="fw-bold text-primary" style="font-size: 1.1rem" id="feeDisplay">6.5%</span>
                    </div>
                    <input type="range" id="marketplaceFee" class="range-slider" min="0" max="25" value="6.5" step="0.1">
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div class="d-flex flex-column gap-3">
            <div class="glass-card p-0 overflow-hidden d-flex flex-column h-100" style="background: transparent; border: none; box-shadow: none;">
                
                <!-- Main ROAS Result -->
                <div class="result-block main-result" id="roasBlock">
                    <div class="result-label text-white">Real Return on Ad Spend (ROAS)</div>
                    <div class="result-value" id="roasValue">0.0x</div>
                    <div class="mt-2 fw-semibold" style="opacity: 0.9; font-size: 1.1rem;" id="roasMessage">Menunggu Input...</div>
                </div>

                <!-- Sub Results -->
                <div class="sub-results flex-grow-1">
                    <div class="sub-result-card">
                        <div class="result-label" style="color: var(--roas-text-muted-light)">Estimasi Pendapatan</div>
                        <div class="sub-result-value" id="revenueValue" style="font-size: 1.4rem;">Rp 0</div>
                        <div class="small mt-1 text-muted">Omzet kotor (Harga &times; Qty)</div>
                    </div>
                    
                    <div class="sub-result-card">
                        <div class="result-label" style="color: var(--roas-text-muted-light)">Break-Even ROAS</div>
                        <div class="sub-result-value" id="beRoasValue" style="font-size: 1.4rem;">0.0x</div>
                        <div class="small mt-1 text-muted">Target minimal balik modal</div>
                    </div>

                    <div class="sub-result-card" style="grid-column: 1 / -1; padding: 1.5rem">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="result-label mb-0" style="color: var(--roas-text-muted-light)">Net Profit (Laba Bersih)</div>
                            <span class="badge bg-primary rounded-pill" id="roiBadge">ROI 0%</span>
                        </div>
                        <div class="sub-result-value text-start" id="netProfitValue" style="font-size: 2.2rem; color: var(--roas-success)">Rp 0</div>
                        <div class="small mt-2 text-muted text-start d-flex justify-content-between">
                            <span>- HPP Total: <b id="lblHpp">Rp 0</b></span>
                            <span>- Fee Platform: <b id="lblFee">Rp 0</b></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        
        <!-- Chart Section -->
        <div class="glass-card" style="grid-column: 1 / -1; padding: 1.5rem 2rem;">
             <h5 class="fw-bold mb-0 text-center" style="color: var(--roas-text-main-light)">Visualisasi Proporsi Finansial</h5>
             <div class="chart-container">
                 <canvas id="roasChart"></canvas>
             </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.body.getAttribute('data-theme') === 'dark';
    
    // Inputs
    const productSelect = new TomSelect('#productSelect', {
        create: false,
        sortField: { field: "text", direction: "asc" }
    });
    
    const hppInput = document.getElementById('hpp');
    const sellingPriceInput = document.getElementById('sellingPrice');
    const qtySoldInput = document.getElementById('qtySold');
    const adSpendInput = document.getElementById('adSpend');
    const marketplaceFeeInput = document.getElementById('marketplaceFee');
    const dateFromInput = document.getElementById('dateFrom');
    const dateToInput = document.getElementById('dateTo');
    
    // Buttons & Elements
    const btnFetchAds = document.getElementById('btnFetchAds');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const fetchAlert = document.getElementById('fetchAlert');
    
    // Displays
    const feeDisplay = document.getElementById('feeDisplay');
    const adSpendIncPpn = document.getElementById('adSpendIncPpn');
    
    const roasValue = document.getElementById('roasValue');
    const roasBlock = document.getElementById('roasBlock');
    const roasMessage = document.getElementById('roasMessage');
    const revenueValue = document.getElementById('revenueValue');
    const beRoasValue = document.getElementById('beRoasValue');
    const netProfitValue = document.getElementById('netProfitValue');
    const roiBadge = document.getElementById('roiBadge');
    
    const lblHpp = document.getElementById('lblHpp');
    const lblFee = document.getElementById('lblFee');
    
    // Chart
    const ctx = document.getElementById('roasChart').getContext('2d');
    let chart;

    // Formatting utilities
    const formatRp = (num) => {
        return new Intl.NumberFormat('id-ID').format(Math.round(num));
    };

    const parseRp = (str) => {
        return parseInt(str.toString().replace(/[^0-9]/g, '')) || 0;
    };

    const formatInput = (e) => {
        let val = parseRp(e.target.value);
        e.target.value = val === 0 && e.target.id !== 'hpp' ? '' : formatRp(val);
        calculate();
    };

    // Event Listeners
    sellingPriceInput.addEventListener('input', formatInput);
    qtySoldInput.addEventListener('input', formatInput);
    adSpendInput.addEventListener('input', formatInput);
    
    marketplaceFeeInput.addEventListener('input', (e) => {
        feeDisplay.textContent = e.target.value + '%';
        calculate();
    });
    
    productSelect.on('change', function(value) {
        if(value) {
            const hpp = this.options[value].dataset.hpp;
            hppInput.value = formatRp(hpp);
            btnFetchAds.disabled = false;
        } else {
            hppInput.value = 0;
            btnFetchAds.disabled = true;
        }
        calculate();
    });
    
    // Fetch Ads Data Logic
    btnFetchAds.addEventListener('click', async function() {
        const itemId = productSelect.getValue();
        if (!itemId) return;
        
        loadingOverlay.classList.add('active');
        fetchAlert.style.display = 'none';
        
        try {
            const url = `{{ route('marketplace.api.roas-calculator.ads-data') }}?item_id=${itemId}&date_from=${dateFromInput.value}&date_to=${dateToInput.value}`;
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.status === 'success') {
                const expense = data.expense || 0;
                const gmv = data.gmv || 0;
                const orders = data.orders || 0;
                
                // Populate inputs
                adSpendInput.value = formatRp(expense);
                qtySoldInput.value = formatRp(orders);
                
                // Calculate average selling price
                if (orders > 0) {
                    sellingPriceInput.value = formatRp(gmv / orders);
                } else if (gmv > 0) {
                    // Fallback if no orders but there is GMV
                    sellingPriceInput.value = formatRp(gmv);
                    qtySoldInput.value = "1"; 
                } else {
                    sellingPriceInput.value = "0";
                }
                
                calculate();
                
                fetchAlert.style.display = 'block';
                fetchAlert.className = 'small mt-2 fw-semibold text-success';
                fetchAlert.innerHTML = `<i class="bi bi-check-circle"></i> Berhasil menarik data iklan (${orders} terjual, GMV Rp ${formatRp(gmv)}).`;
            } else {
                fetchAlert.style.display = 'block';
                fetchAlert.className = 'small mt-2 fw-semibold text-warning';
                fetchAlert.innerHTML = `<i class="bi bi-exclamation-triangle"></i> Produk belum memiliki data iklan untuk rentang tanggal ini.`;
                
                adSpendInput.value = 0;
                qtySoldInput.value = 0;
                calculate();
            }
            
        } catch (error) {
            console.error(error);
            fetchAlert.style.display = 'block';
            fetchAlert.className = 'small mt-2 fw-semibold text-danger';
            fetchAlert.innerHTML = `<i class="bi bi-x-circle"></i> Terjadi kesalahan saat menarik data.`;
        } finally {
            setTimeout(() => {
                loadingOverlay.classList.remove('active');
            }, 300);
            
            // Hide alert after 5 seconds
            setTimeout(() => {
                if (fetchAlert.style.display === 'block') {
                    fetchAlert.style.display = 'none';
                }
            }, 5000);
        }
    });
    
    function initChart() {
        chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Analisis Biaya & Profit (Rp)'],
                datasets: [
                    {
                        label: 'Pendapatan Kotor',
                        data: [0],
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: 'HPP Total',
                        data: [0],
                        backgroundColor: 'rgba(100, 116, 139, 0.5)',
                        borderColor: 'rgb(100, 116, 139)',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: 'Fee Platform',
                        data: [0],
                        backgroundColor: 'rgba(139, 92, 246, 0.5)',
                        borderColor: 'rgb(139, 92, 246)',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: 'Iklan (Inc. PPN)',
                        data: [0],
                        backgroundColor: 'rgba(245, 158, 11, 0.7)',
                        borderColor: 'rgb(245, 158, 11)',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: 'Laba Bersih',
                        data: [0],
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 1,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: isDark ? '#cbd5e1' : '#475569', font: { family: 'Outfit', weight: '500' } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.y !== null) {
                                    label += 'Rp ' + formatRp(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: isDark ? '#94a3b8' : '#64748b',
                            callback: function(value) { return 'Rp ' + (value / 1000000 >= 1 ? (value/1000000).toFixed(1) + ' Jt' : formatRp(value)); }
                        },
                        grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        ticks: { color: isDark ? '#94a3b8' : '#64748b' },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    function calculate() {
        const unitHpp = parseRp(hppInput.value) || 0;
        const sellingPrice = parseRp(sellingPriceInput.value) || 0;
        const qty = parseRp(qtySoldInput.value) || 0;
        const baseAdSpend = parseRp(adSpendInput.value) || 0;
        const feePct = parseFloat(marketplaceFeeInput.value) / 100;
        
        // PPN 11% for Ad Spend
        const realAdSpend = baseAdSpend * 1.11;
        adSpendIncPpn.textContent = 'Rp ' + formatRp(realAdSpend);
        
        const grossRevenue = sellingPrice * qty;
        const totalHpp = unitHpp * qty;
        const totalFee = grossRevenue * feePct;
        
        // Net Margin per unit (Price - Hpp - Fee)
        const netMarginUnit = sellingPrice - unitHpp - (sellingPrice * feePct);
        const netMarginPct = sellingPrice > 0 ? (netMarginUnit / sellingPrice) : 0;
        
        const netProfit = grossRevenue - totalHpp - totalFee - realAdSpend;
        
        // Real ROAS (Gross Rev / Ad Spend inc PPN)
        const roas = realAdSpend > 0 ? (grossRevenue / realAdSpend) : 0;
        
        // BEP ROAS = 1 / Profit Margin % (based on revenue)
        const beRoas = netMarginPct > 0 ? (1 / netMarginPct) : 0;
        
        // ROI = Net Profit / Real Ad Spend
        const roi = realAdSpend > 0 ? (netProfit / realAdSpend) * 100 : 0;

        // Update Text DOM
        revenueValue.textContent = 'Rp ' + formatRp(grossRevenue);
        lblHpp.textContent = 'Rp ' + formatRp(totalHpp);
        lblFee.textContent = 'Rp ' + formatRp(totalFee);
        
        roasValue.textContent = realAdSpend > 0 ? roas.toFixed(2) + 'x' : '0.00x';
        beRoasValue.textContent = (isFinite(beRoas) && beRoas > 0) ? beRoas.toFixed(2) + 'x' : 'N/A';
        
        netProfitValue.textContent = 'Rp ' + formatRp(Math.abs(netProfit));
        netProfitValue.style.color = netProfit >= 0 ? 'var(--roas-success)' : 'var(--roas-danger)';
        if(netProfit < 0) {
            netProfitValue.textContent = '- ' + netProfitValue.textContent;
        }

        roiBadge.textContent = 'ROI ' + formatRp(roi) + '%';
        roiBadge.className = 'badge rounded-pill ' + (roi >= 0 ? 'bg-success' : 'bg-danger');

        // Styling based on performance
        roasBlock.classList.remove('status-excellent', 'status-warning', 'status-danger');
        
        if (realAdSpend === 0 && grossRevenue === 0) {
            roasMessage.textContent = 'Masukkan Data Iklan & Penjualan...';
        } else if (grossRevenue > 0 && realAdSpend === 0) {
             roasBlock.classList.add('status-excellent');
             roasMessage.textContent = 'Penjualan Organik (Tanpa Iklan) 🚀';
        } else if (roas >= beRoas * 1.5) {
            roasBlock.classList.add('status-excellent');
            roasMessage.textContent = 'Performa Iklan Sangat Baik! 🔥';
        } else if (roas >= beRoas) {
            roasMessage.textContent = 'Profitabel (Aman) 👍';
        } else if (roas >= beRoas * 0.8) {
            roasBlock.classList.add('status-warning');
            roasMessage.textContent = 'Hati-hati, Hampir Rugi ⚠️';
        } else {
            roasBlock.classList.add('status-danger');
            roasMessage.textContent = 'Iklan Boncos / Rugi 📉';
        }
        
        // Update Chart
        if (chart) {
            chart.data.datasets[0].data = [grossRevenue];
            chart.data.datasets[1].data = [totalHpp];
            chart.data.datasets[2].data = [totalFee];
            chart.data.datasets[3].data = [realAdSpend];
            chart.data.datasets[4].data = [netProfit > 0 ? netProfit : 0]; // Only show positive profit on bar
            chart.update();
        }
    }

    initChart();
});
</script>
@endsection
