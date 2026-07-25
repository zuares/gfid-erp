@extends('layouts.app')

@section('title', 'Analisis Iklan Shopee')

@push('head')
@include('dashboard.partials._styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* ─────────────────────────────────────────────────────────────────────────────
   MODERN DASHBOARD UI UPGRADE (Rich Aesthetics, Glassmorphism, Micro-animations)
───────────────────────────────────────────────────────────────────────────── */
body {
    font-family: 'Inter', sans-serif !important;
}

.spin-icon { animation: spin 1.5s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }

:root {
    --card-bg: #ffffff;
    --card-border: #e2e8f0;
    --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
    --hero-bg: #f8fafc;
    --card-hover-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
    --dsh-accent-hover: #1d4ed8;
}

body[data-theme="dark"] {
    --card-bg: #1e293b;
    --card-border: #334155;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
    --hero-bg: #0f172a;
    --card-hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
}

.dpanel {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    box-shadow: var(--card-shadow);
    border-radius: 12px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.dpanel:hover {
    transform: translateY(-2px);
    box-shadow: var(--card-hover-shadow);
}

.dash-tabs {
    display: inline-flex;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    border-radius: 10px;
    padding: .35rem;
    gap: .35rem;
    margin-bottom: 1rem;
}

.dash-tab {
    background: transparent;
    border: none;
    padding: .5rem 1.25rem;
    font-size: .85rem;
    font-weight: 600;
    color: var(--dsh-muted);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.dash-tab:hover {
    color: var(--text, #0f172a);
    background: rgba(148,163,184,.1);
}

.dash-tab.active {
    background: var(--dsh-accent);
    color: #fff;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.dash-hero {
    background: var(--hero-bg);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    padding: 1.5rem 2rem;
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 20;
}

.dash-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(37,99,235,0.1) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    pointer-events: none;
}

.dash-hero h1 { font-size: 1.5rem; font-weight: 800; margin: 0; letter-spacing: -0.025em; }
.dash-hero .sub { font-size: .85rem; color: var(--dsh-muted); margin-top: .4rem; font-weight: 500; }
.role-chip { 
    display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .75rem; 
    border-radius: 8px; font-size: .75rem; font-weight: 600; 
    background: var(--card-bg);
    border: 1px solid var(--card-border); color: var(--text); 
}

.live-btn {
    cursor: default;
    transition: all 0.3s ease;
    user-select: none;
}
.live-on {
    background: rgba(22, 163, 74, 0.15) !important;
    color: #15803d !important;
    border: 1px solid rgba(22, 163, 74, 0.3) !important;
    box-shadow: 0 0 12px rgba(22, 163, 74, 0.2);
}
.live-off {
    background: rgba(100, 116, 139, 0.1) !important;
    color: var(--dsh-muted) !important;
    border: 1px solid var(--card-border) !important;
}
body[data-theme="dark"] .live-on { color: #4ade80 !important; }

body[data-theme="dark"] .dash-tab.active {
    background: var(--text);
    color: var(--bg);
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.15);
}
.tab-pane { display: none; opacity: 0; transition: opacity 0.3s ease; }
.tab-pane.active { display: block; opacity: 1; animation: fadeIn 0.4s ease-out; }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Filter Container */
.dash-filter {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    gap: 1rem;
    align-items: flex-end;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}
.filter-item { flex: 1; min-width: 180px; }
.filter-item label { font-size: .75rem; font-weight: 600; color: var(--dsh-muted); margin-bottom: .35rem; display: block; text-transform: uppercase; letter-spacing: 0.05em;}
.filter-item input, .filter-item select {
    width: 100%; font-size: .85rem; padding: .5rem .75rem; border-radius: 8px;
    border: 1px solid var(--dsh-border); background: var(--bg, #f8fafc); color: var(--text, #0f172a);
    transition: all 0.2s ease; font-weight: 500;
}
.filter-item input:focus, .filter-item select:focus {
    outline: none;
    border-color: var(--dsh-accent);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
body[data-theme="dark"] .filter-item input, body[data-theme="dark"] .filter-item select {
    background: rgba(15, 23, 42, 0.6); border-color: rgba(255,255,255,.1); color: #e2e8f0;
}

/* Tabel di dalam dpanel */
.dpanel-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.dpanel-table thead th {
    background: rgba(241, 245, 249, 0.6);
    backdrop-filter: blur(4px);
    border-bottom: 1px solid var(--card-border);
    font-size: .75rem;
    font-weight: 700;
    color: var(--dsh-muted);
    padding: .75rem 1rem;
    text-align: left;
    white-space: nowrap;
    letter-spacing: 0.02em;
}
body[data-theme="dark"] .dpanel-table thead th { background: rgba(30, 41, 59, 0.6); }
.dpanel-table tbody td {
    padding: .85rem 1rem;
    font-size: .85rem;
    border-bottom: 1px solid var(--card-border);
    color: var(--text, #0f172a);
    vertical-align: middle;
    transition: background 0.2s ease;
}
.dpanel-table tbody tr { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.dpanel-table tbody tr:hover td { background: rgba(241, 245, 249, 0.8); }
body[data-theme="dark"] .dpanel-table tbody tr:hover td { background: rgba(51, 65, 85, 0.4); }

/* Periode Bar (Pill) */
.period-bar { display: flex; gap: .75rem; align-items: center; }
.range-pill {
    display: inline-flex; align-items: center; justify-content: space-between; gap: .75rem;
    border: 1px solid var(--card-border); background: var(--bg); padding: .5rem 1rem; border-radius: 8px;
    cursor: pointer; font-size: .85rem; color: var(--text, #0f172a); font-weight: 600;
    transition: all 0.25s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}
body[data-theme="dark"] .range-pill { color: #f8fafc; background: rgba(30, 41, 59, 0.6); }
.range-pill:hover { 
    background: rgba(241, 245, 249, 1); 
    border-color: var(--dsh-accent);
}
body[data-theme="dark"] .range-pill:hover { background: rgba(51, 65, 85, 0.8); }

/* Kpi Cards Upgrade */
.dash-tabs-modern {
    display: inline-flex;
    background: rgba(148, 163, 184, 0.1);
    padding: 0.35rem;
    border-radius: 12px;
    gap: 0.25rem;
}
body[data-theme="dark"] .dash-tabs-modern { background: rgba(30, 41, 59, 0.5); }
.dash-tab-m {
    border: none; background: transparent; padding: 0.6rem 1.25rem; border-radius: 8px;
    font-weight: 600; font-size: 0.85rem; color: var(--dsh-muted); cursor: pointer;
    transition: all 0.2s ease; display: flex; align-items: center; gap: 0.5rem; white-space: nowrap;
}
.dash-tab-m:hover { color: var(--text); }
.dash-tab-m.active {
    background: var(--card-bg); color: var(--text);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.1);
}

.dash-tab-sm {
    border: none; background: transparent; padding: 0.4rem 0.8rem; border-radius: 8px;
    font-weight: 600; font-size: 0.75rem; color: var(--dsh-muted); cursor: pointer;
    transition: all 0.2s ease; display: flex; align-items: center; gap: 0.35rem; white-space: nowrap;
}
.dash-tab-sm:hover { color: var(--text); }
.dash-tab-sm.active {
    background: var(--card-bg); color: var(--text);
    box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.1);
}
body[data-theme="dark"] .dash-tab-sm.active { background: var(--card-bg); }

.kpi {
    background: var(--card-bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: var(--card-shadow);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}
.kpi::after {
    content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%;
    background: currentColor; opacity: 0.7;
}
.kpi:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 16px 32px -12px rgba(0, 0, 0, 0.15);
}
.kpi-label { font-size: .8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; display:flex; align-items:center; gap:0.5rem; opacity:0.8;}
.kpi-value { font-size: 1.75rem; font-weight: 800; margin: 0.5rem 0; letter-spacing: -0.02em; }
.kpi-sub { font-size: .75rem; opacity: 0.85; }

/* ─── Mini Sync Log ─── */
.mini-log-toggle {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .35rem .7rem; border-radius: 8px; font-size: .72rem; font-weight: 600;
    cursor: pointer; user-select: none;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    color: var(--dsh-muted); transition: all 0.25s ease;
}
.mini-log-toggle:hover { border-color: var(--dsh-accent); color: var(--dsh-accent); }
.mini-log-toggle .chevron {
    display: inline-block; transition: transform 0.3s ease; font-size: .6rem;
}
.mini-log-toggle.open .chevron { transform: rotate(180deg); }

.mini-log-panel {
    position: absolute; top: calc(100% + 8px); right: 0; z-index: 9999;
    min-width: 380px; max-width: 440px;
    background: var(--card-bg);
    border: 1px solid var(--card-border); border-radius: 12px;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
    padding: 0; overflow: hidden;
    opacity: 0; transform: translateY(-8px) scale(0.97); pointer-events: none;
    transition: opacity 0.25s ease, transform 0.25s ease;
}
.mini-log-panel.show {
    opacity: 1; transform: translateY(0) scale(1); pointer-events: auto;
}
body[data-theme="dark"] .mini-log-panel {
    box-shadow: 0 16px 48px -12px rgba(0,0,0,0.5);
}

.mini-log-header {
    padding: .65rem .85rem; font-size: .72rem; font-weight: 700;
    color: var(--dsh-muted); text-transform: uppercase; letter-spacing: .06em;
    border-bottom: 1px solid var(--glass-border);
    display: flex; align-items: center; gap: .4rem;
}

.mini-log-entry {
    display: grid; grid-template-columns: 24px 1fr auto;
    gap: .5rem; align-items: center;
    padding: .55rem .85rem;
    border-bottom: 1px solid rgba(0,0,0,0.03);
    font-size: .72rem;
    transition: background 0.15s ease;
}
.mini-log-entry:last-child { border-bottom: none; }
.mini-log-entry:hover { background: rgba(0,0,0,0.02); }
body[data-theme="dark"] .mini-log-entry { border-bottom-color: rgba(255,255,255,0.03); }
body[data-theme="dark"] .mini-log-entry:hover { background: rgba(255,255,255,0.03); }

.mle-icon {
    width: 22px; height: 22px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .6rem;
}
.mle-icon.success { background: rgba(22,163,74,0.12); color: #16a34a; }
.mle-icon.error   { background: rgba(220,38,38,0.12); color: #dc2626; }
.mle-icon.processing { background: rgba(37,99,235,0.12); color: #2563eb; }
.mle-icon.rate_limited { background: rgba(234,179,8,0.12); color: #ca8a04; }

.mle-info { line-height: 1.35; }
.mle-time { font-weight: 600; color: var(--text, #0f172a); }
.mle-type { color: var(--dsh-muted); font-size: .65rem; }
.mle-badge {
    font-size: .6rem; font-weight: 700; letter-spacing: .03em;
    padding: .15rem .4rem; border-radius: 5px;
    text-transform: uppercase; white-space: nowrap;
}
.mle-badge.success { background: rgba(22,163,74,0.1); color: #16a34a; }
.mle-badge.error   { background: rgba(220,38,38,0.1); color: #dc2626; }
.mle-badge.processing { background: rgba(37,99,235,0.1); color: #2563eb; }
.mle-badge.rate_limited { background: rgba(234,179,8,0.1); color: #ca8a04; }

.mle-stats { color: var(--dsh-muted); font-size: .62rem; margin-top: .1rem; }

@keyframes spin { to { transform: rotate(360deg); } }
.mle-icon.processing i { animation: spin 1.2s linear infinite; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabBtns = document.querySelectorAll('.dash-tab-m[data-target]');
    const tabPanes = document.querySelectorAll('.tab-pane');

    const savedTab = localStorage.getItem('adsDashboardActiveTab');
    if (savedTab) {
        tabBtns.forEach(b => b.classList.remove('active'));
        tabPanes.forEach(p => p.classList.remove('active'));
        const targetBtn = document.querySelector(`.dash-tab-m[data-target="${savedTab}"]`);
        const targetPane = document.getElementById(savedTab);
        if (targetBtn && targetPane) {
            targetBtn.classList.add('active');
            targetPane.classList.add('active');
        } else {
            if (tabBtns.length > 0) tabBtns[0].classList.add('active');
            if (tabPanes.length > 0) tabPanes[0].classList.add('active');
        }
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));
            
            btn.classList.add('active');
            const targetId = btn.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
            localStorage.setItem('adsDashboardActiveTab', targetId);

            if (targetId === 'tab-daily' && !dailyChart) {
                setTimeout(() => { renderDailyChart(document.getElementById('metricSelect').value); }, 100);
            }
        });
    });


    window.dispatchEvent(new Event('resize')); // re-render charts on load

    // Flatpickr Logic
    const rangePicker = document.getElementById('rangePicker');
    const fromEl = document.getElementById('fromHidden');
    const toEl = document.getElementById('toHidden');
    const filterForm = document.getElementById('filterForm');

    function ymd(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
    
    if(typeof flatpickr !== 'undefined' && rangePicker) {
        flatpickr(rangePicker, {
            mode: 'range',
            locale: 'id',
            showMonths: 2, // Tampilkan 2 bulan berjejer agar mudah pilih rentang panjang
            dateFormat: 'Y-m-d',
            defaultDate: [fromEl.value, toEl.value],
            onChange: function(selectedDates, dateStr, instance) {
                if(selectedDates.length === 2) {
                    fromEl.value = ymd(selectedDates[0]);
                    toEl.value = ymd(selectedDates[1]);
                    filterForm.submit();
                }
            },
            onClose: function(selectedDates, dateStr, instance) {
                // Jika user hanya memilih 1 tanggal lalu klik di luar kalender (menutup picker),
                // asumsikan mereka ingin melihat data khusus 1 hari tersebut.
                if(selectedDates.length === 1) {
                    fromEl.value = ymd(selectedDates[0]);
                    toEl.value = ymd(selectedDates[0]);
                    
                    // Supaya visual input text-nya rapi menjadi "YYYY-MM-DD — YYYY-MM-DD"
                    instance.setDate([selectedDates[0], selectedDates[0]], false);
                    
                    filterForm.submit();
                }
            }
        });
    }


    // --- NEXT SYNC COUNTDOWN INDICATOR ---
    const syncChip = document.getElementById('syncCountdown');
    
    function updateSyncCountdown() {
        const now = new Date();
        const nextSync = new Date(now);
        nextSync.setHours(now.getHours() + 1, 0, 0, 0); // next :00
        
        const diffMs = nextSync - now;
        const diffMin = Math.floor(diffMs / 60000);
        const diffSec = Math.floor((diffMs % 60000) / 1000);
        
        if (diffMin <= 1) {
            syncChip.innerHTML = `<i class="bi bi-arrow-repeat"></i> Sync segera...`;
            syncChip.classList.remove('live-off');
            syncChip.classList.add('live-on');
        } else {
            syncChip.innerHTML = `<i class="bi bi-clock-history"></i> Sync berikutnya: ${diffMin} mnt`;
            syncChip.classList.remove('live-on');
            syncChip.classList.add('live-off');
        }
    }
    
    updateSyncCountdown();
    setInterval(updateSyncCountdown, 30000); // update tiap 30 detik

});

// --- FETCH REAL-TIME STATUS ---
function fetchRealtimeStatus() {
    const storeId = document.querySelector('select[name="store_id"]').value;
    const container = document.getElementById('realtimeStatusContainer');
    if (!storeId || !container) return;

    fetch(`{{ route('marketplace.ads.realtime.status') }}?store_id=${storeId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const bal = data.data.balance || {};
                const toggle = data.data.toggle_info || {};
                const facil = data.data.facil_rate || {};
                
                const formatRp = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val || 0);
                
                // Format total balance (gratis + berbayar)
                const totalBal = formatRp(bal.total_balance || 0);
                
                // Auto top-up status
                let topupHtml = '';
                if (toggle.auto_top_up === true) {
                    topupHtml = `<span style="color: #16a34a; font-weight: 700;"><i class="bi bi-check-circle-fill"></i> AKTIF</span>`;
                } else {
                    topupHtml = `<span style="color: var(--dsh-muted); font-weight: 700;"><i class="bi bi-x-circle-fill"></i> NON-AKTIF</span>`;
                }
                
                // Ads Facil Status
                let facilHtml = '';
                if (facil.shop_rate && facil.shop_rate > 0) {
                     facilHtml = `<span style="color: #2563eb; font-weight: 700;"><i class="bi bi-info-circle-fill"></i> TERSEDIA (${facil.shop_rate}%)</span>`;
                } else {
                     facilHtml = `<span style="color: var(--dsh-muted); font-weight: 700;"><i class="bi bi-dash-circle-fill"></i> TIDAK TERSEDIA</span>`;
                }

                container.innerHTML = `
                    <div class="dpanel p-3" style="border-left: 4px solid #f59e0b; animation: fadeIn 0.4s ease-out;">
                        <div class="kpi-label" style="color: #b45309;"><i class="bi bi-wallet2"></i> Total Saldo Iklan</div>
                        <div class="kpi-value" style="font-size: 1.5rem; color: #92400e;">${totalBal}</div>
                        <div class="kpi-sub" style="color: var(--dsh-muted);">Update secara real-time</div>
                    </div>
                    <div class="dpanel p-3" style="border-left: 4px solid #16a34a; animation: fadeIn 0.4s ease-out; animation-delay: 0.1s; animation-fill-mode: both;">
                        <div class="kpi-label" style="color: #15803d;"><i class="bi bi-arrow-repeat"></i> Auto Top-Up</div>
                        <div class="kpi-value" style="font-size: 1.2rem; margin-top: 0.75rem;">${topupHtml}</div>
                        <div class="kpi-sub" style="color: var(--dsh-muted); margin-top: 0.25rem;">Pengisian saldo otomatis</div>
                    </div>
                    <div class="dpanel p-3" style="border-left: 4px solid #2563eb; animation: fadeIn 0.4s ease-out; animation-delay: 0.2s; animation-fill-mode: both;">
                        <div class="kpi-label" style="color: #1d4ed8;"><i class="bi bi-globe"></i> Ads Fácil</div>
                        <div class="kpi-value" style="font-size: 1.2rem; margin-top: 0.75rem;">${facilHtml}</div>
                        <div class="kpi-sub" style="color: var(--dsh-muted); margin-top: 0.25rem;">Dukungan rate khusus Ads Fácil</div>
                    </div>
                `;
            } else {
                container.innerHTML = `<div class="dpanel p-3 w-100" style="color: #dc2626; border-left: 4px solid #dc2626;">Gagal memuat informasi real-time.</div>`;
            }
        })
        .catch(err => {
            container.innerHTML = `<div class="dpanel p-3 w-100" style="color: #dc2626; border-left: 4px solid #dc2626;">Koneksi error: Gagal memuat informasi.</div>`;
        });
}

document.addEventListener('DOMContentLoaded', function () {
    setTimeout(fetchRealtimeStatus, 500); // Fetch after 500ms
});

// --- MINI LOG TOGGLE ---
function toggleMiniLog() {
    const panel = document.getElementById('miniLogPanel');
    const toggle = document.getElementById('miniLogToggle');
    if (!panel || !toggle) return;
    
    const isOpen = panel.classList.contains('show');
    if (isOpen) {
        panel.classList.remove('show');
        toggle.classList.remove('open');
    } else {
        panel.classList.add('show');
        toggle.classList.add('open');
    }
}

// Close mini log on click outside
document.addEventListener('click', function(e) {
    const panel = document.getElementById('miniLogPanel');
    const toggle = document.getElementById('miniLogToggle');
    if (!panel || !toggle) return;
    
    if (!panel.contains(e.target) && !toggle.contains(e.target)) {
        panel.classList.remove('show');
        toggle.classList.remove('open');
    }
});
</script>
@endpush

@section('content')
<div class="dash py-3">

    {{-- ==============================================
         HERO SECTION (Header)
    ============================================== --}}
    <div class="dash-hero">
        <div>
            <h1>Analisis Iklan Shopee</h1>
            <div class="sub">Pemantauan biaya, GMV, dan kontrol ROAS harian.</div>
        </div>
        <div style="position: relative; text-align: right;">
            @if(isset($syncRuns) && $syncRuns->isNotEmpty())
                @php
                    $latestRun = $syncRuns->first();
                    $lastSuccess = $lastSuccessRun ?? null;
                @endphp
                @if($latestRun->status === 'error')
                    <div style="font-size: 0.7rem; color: #dc2626; text-align: right; margin-bottom: 0.25rem; font-weight: 600;">
                        <i class="bi bi-exclamation-triangle-fill"></i> Sync Gagal: {{ Str::limit($latestRun->error_message, 60) }}
                    </div>
                @endif
                <div style="font-size: 0.72rem; color: var(--dsh-muted); text-align: right; margin-bottom: 0.35rem; font-weight: 500;">
                    @if($lastSuccess)
                        <span style="color: #16a34a;"><i class="bi bi-check-circle-fill"></i></span>
                        Terakhir Sync: {{ $lastSuccess->updated_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }}
                    @else
                        <span style="color: #eab308;"><i class="bi bi-clock"></i></span>
                        Belum pernah sync berhasil
                    @endif
                </div>
            @endif
            <div style="display: flex; gap: .5rem; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
                <div id="syncCountdown" class="role-chip live-btn live-off">
                    <i class="bi bi-clock-history"></i> Menghitung...
                </div>
                {{-- Mini Log Toggle --}}
                @if(isset($syncRuns) && $syncRuns->isNotEmpty())
                <div class="mini-log-toggle" id="miniLogToggle" onclick="toggleMiniLog()">
                    <i class="bi bi-terminal"></i> Log
                    <span class="chevron">▼</span>
                </div>
                @endif
                
                @if(auth()->user()->role === 'owner')
                <button type="button" class="btn btn-sm btn-ship-outline btn-pill" onclick="clearAdsData()" style="border-radius:8px;font-weight:600;font-size:.72rem;padding:.35rem .7rem; border: 1px solid rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.1); color: #ef4444; box-shadow: var(--card-shadow);">
                    <i class="bi bi-trash"></i> Bersihkan Data
                </button>
                @endif
                
                <button type="button" class="btn btn-sm btn-ship-outline btn-pill" data-bs-toggle="modal" data-bs-target="#modalSyncAds" style="border-radius:8px;font-weight:600;font-size:.72rem;padding:.35rem .7rem; border: 1px solid var(--card-border); background: var(--card-bg); box-shadow: var(--card-shadow); color: var(--text);">
                    <i class="bi bi-arrow-repeat"></i> Sync Manual
                </button>
            </div>

            {{-- Mini Log Panel (Dropdown) --}}
            @if(isset($syncRuns) && $syncRuns->isNotEmpty())
            <div class="mini-log-panel" id="miniLogPanel">
                <div class="mini-log-header">
                    <i class="bi bi-activity"></i> Riwayat Sinkronisasi Terakhir
                </div>
                @foreach($syncRuns->take(7) as $sr)
                    @php
                        $statusClass = match($sr->status) {
                            'success' => 'success',
                            'error' => 'error',
                            'processing' => 'processing',
                            'rate_limited' => 'rate_limited',
                            default => 'processing'
                        };
                        $statusIcon = match($sr->status) {
                            'success' => 'bi-check-lg',
                            'error' => 'bi-x-lg',
                            'processing' => 'bi-arrow-repeat',
                            'rate_limited' => 'bi-hourglass-split',
                            default => 'bi-question'
                        };
                        $statusLabel = match($sr->status) {
                            'success' => 'OK',
                            'error' => 'GAGAL',
                            'processing' => 'PROSES',
                            'rate_limited' => 'LIMIT',
                            default => $sr->status
                        };
                    @endphp
                    <div class="mini-log-entry">
                        <div class="mle-icon {{ $statusClass }}">
                            <i class="bi {{ $statusIcon }}"></i>
                        </div>
                        <div class="mle-info">
                            <div class="mle-time">{{ $sr->started_at ? \Carbon\Carbon::parse($sr->started_at)->timezone('Asia/Jakarta')->format('d/m H:i') : '-' }}</div>
                            <div class="mle-stats">{{ $sr->sync_type ?? '-' }} · {{ $sr->total_requests ?? 0 }} req · {{ $sr->total_updated ?? 0 }} row
                                @if($sr->error_message)
                                    · <span style="color: #dc2626;">{{ Str::limit($sr->error_message, 40) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="mle-badge {{ $statusClass }}">{{ $statusLabel }}</div>
                    </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    @if(session('error'))
        <div class="dpanel dpanel-body" style="border-left: 4px solid #dc2626; color: #dc2626; font-size:.82rem; font-weight: 500;">
            <i class="bi bi-exclamation-circle me-1"></i> {{ session('error') }}
        </div>
    @endif
    @if(session('success'))
        <div class="dpanel dpanel-body" style="border-left: 4px solid #16a34a; color: #16a34a; font-size:.82rem; font-weight: 500;">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        </div>
    @endif

    {{-- ==============================================
         FILTER
    ============================================== --}}
    <form method="GET" action="{{ route('marketplace.ads.dashboard') }}" class="dash-filter" id="filterForm">
        <div class="filter-item">
            <label>Toko Shopee</label>
            <select name="store_id" onchange="this.form.submit()">
                @foreach($stores as $s)
                    <option value="{{ $s->id }}" {{ $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-item" style="flex: 2;">
            <label>Periode Data</label>
            <div class="period-bar">
                <input type="text" id="rangePicker" class="range-pill" style="width: 260px; text-align: center; cursor: pointer; background: rgba(148,163,184,.06); border: 1px solid var(--dsh-border); padding: .4rem .85rem; border-radius: 8px; color: var(--text, #0f172a); font-weight: 650; font-size: .85rem;" placeholder="Pilih Rentang Tanggal..." readonly>
                <input type="hidden" name="date_from" id="fromHidden" value="{{ $dateFrom }}">
                <input type="hidden" name="date_to" id="toHidden" value="{{ $dateTo }}">
            </div>
        </div>
        <div class="filter-item">
            <label>Bandingkan:</label>
            <select name="compare_mode" onchange="this.form.submit()" style="background: rgba(148,163,184,.06); border: 1px solid var(--dsh-border); padding: .4rem .85rem; border-radius: 8px; color: var(--text, #0f172a); font-weight: 600; font-size: .85rem;">
                <option value="prev_period" {{ (isset($compareMode) && $compareMode == 'prev_period') ? 'selected' : '' }}>Durasi Sama (Sblmnya)</option>
                <option value="prev_month" {{ (isset($compareMode) && $compareMode == 'prev_month') ? 'selected' : '' }}>Bulan Lalu (Tgl Sama)</option>
                <option value="prev_year" {{ (isset($compareMode) && $compareMode == 'prev_year') ? 'selected' : '' }}>Tahun Lalu (Tgl Sama)</option>
            </select>
        </div>
    </form>

    @if(empty($storeId))
        <div class="dash-empty">
            <i class="bi bi-shop"></i>
            Pilih toko terlebih dahulu untuk melihat analisis.
        </div>
    @else
        {{-- ==============================================
             TABS (SEGMENTED CONTROL)
        ============================================== --}}
        <div style="margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: 0.25rem; scrollbar-width: none;">
            <div class="dash-tabs-modern">
                <button class="dash-tab-m active" data-target="tab-dashboard"><i class="bi bi-grid-1x2"></i> Ringkasan Utama</button>
                <button class="dash-tab-m" data-target="tab-daily"><i class="bi bi-graph-up"></i> Tren Harian</button>
                <button class="dash-tab-m" data-target="tab-campaigns"><i class="bi bi-megaphone"></i> Kampanye</button>
                <button class="dash-tab-m" data-target="tab-items"><i class="bi bi-box-seam"></i> Performa Produk</button>
                <button class="dash-tab-m" data-target="tab-sync"><i class="bi bi-cloud-arrow-down"></i> Sinkronisasi</button>
            </div>
        </div>

        {{-- ==============================================
             TAB CONTENT
        ============================================== --}}
        
        <!-- DASHBOARD TAB -->
        <div class="tab-pane active" id="tab-dashboard">
            
            {{-- INFORMASI SALDO & STATUS TOKO (REAL-TIME) --}}
            <div class="dash-sec"><i class="bi bi-wallet2 text-primary"></i> Informasi Saldo & Status Toko (Real-time)</div>
            <div class="dash-panels mb-4" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;" id="realtimeStatusContainer">
                {{-- Skeleton Loaders --}}
                <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-border)">
                    <div class="placeholder-glow">
                        <span class="placeholder col-6 mb-2"></span>
                        <span class="placeholder col-4 d-block" style="height: 2rem;"></span>
                    </div>
                </div>
                <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-border)">
                    <div class="placeholder-glow">
                        <span class="placeholder col-7 mb-2"></span>
                        <span class="placeholder col-5 d-block" style="height: 2rem;"></span>
                    </div>
                </div>
                <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-border)">
                    <div class="placeholder-glow">
                        <span class="placeholder col-8 mb-2"></span>
                        <span class="placeholder col-4 d-block" style="height: 2rem;"></span>
                    </div>
                </div>
            </div>

            <div class="dash-sec"><i class="bi bi-robot"></i> Asisten Analisis (Berdasarkan Rentang Tanggal)</div>
            <div class="dash-panels mb-4" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-border)" id="insightHealth"></div>
                <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-border)" id="insightTraffic"></div>
                <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-border)" id="insightTime"></div>
            </div>

            <div class="dash-panels mt-4 mb-4" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                {{-- CHART BIAYA --}}
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small fw-bold mt-0 mb-0"><i class="bi bi-bar-chart-steps text-danger"></i> Top 5 Biaya</div>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 0.5rem;">
                        Produk penyerap biaya terbesar (ID).
                    </div>
                    <div style="position: relative; height: 180px; display: flex; justify-content: center; align-items: center;">
                        @if(empty($itemPerformance) || count($itemPerformance) === 0)
                            <div style="color: var(--dsh-muted); font-size: 0.8rem; text-align: center;">Belum ada data produk aktif.</div>
                        @else
                            <canvas id="chartSpend"></canvas>
                        @endif
                    </div>
                </div>

                {{-- CHART GMV --}}
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small fw-bold mt-0 mb-0"><i class="bi bi-bar-chart-steps text-success"></i> Top 5 GMV</div>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 0.5rem;">
                        Produk penyumbang GMV terbesar (ID).
                    </div>
                    <div style="position: relative; height: 180px; display: flex; justify-content: center; align-items: center;">
                        @if(empty($itemPerformance) || count($itemPerformance) === 0)
                            <div style="color: var(--dsh-muted); font-size: 0.8rem; text-align: center;">Belum ada data produk aktif.</div>
                        @else
                            <canvas id="chartGmv"></canvas>
                        @endif
                    </div>
                </div>

                {{-- CHART KLIK --}}
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small fw-bold mt-0 mb-0"><i class="bi bi-cursor text-primary"></i> Top 5 Trafik (Klik)</div>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 0.5rem;">
                        Produk dengan klik terbanyak (ID).
                    </div>
                    <div style="position: relative; height: 180px; display: flex; justify-content: center; align-items: center;">
                        @if(empty($itemPerformance) || count($itemPerformance) === 0)
                            <div style="color: var(--dsh-muted); font-size: 0.8rem; text-align: center;">Belum ada data produk aktif.</div>
                        @else
                            <canvas id="chartClicks"></canvas>
                        @endif
                    </div>
                </div>

                {{-- CHART IMPRESI --}}
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small fw-bold mt-0 mb-0"><i class="bi bi-eye text-info"></i> Top 5 Impresi</div>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 0.5rem;">
                        Produk dengan tayangan terbanyak (ID).
                    </div>
                    <div style="position: relative; height: 180px; display: flex; justify-content: center; align-items: center;">
                        @if(empty($itemPerformance) || count($itemPerformance) === 0)
                            <div style="color: var(--dsh-muted); font-size: 0.8rem; text-align: center;">Belum ada data produk aktif.</div>
                        @else
                            <canvas id="chartImpressions"></canvas>
                        @endif
                    </div>
                </div>

                {{-- CHART CTR --}}
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small fw-bold mt-0 mb-0"><i class="bi bi-hand-index text-warning"></i> Top 5 CTR</div>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 0.5rem;">
                        Produk dengan rasio klik tertinggi (ID).
                    </div>
                    <div style="position: relative; height: 180px; display: flex; justify-content: center; align-items: center;">
                        @if(empty($itemPerformance) || count($itemPerformance) === 0)
                            <div style="color: var(--dsh-muted); font-size: 0.8rem; text-align: center;">Belum ada data produk aktif.</div>
                        @else
                            <canvas id="chartCtr"></canvas>
                        @endif
                    </div>
                </div>

                {{-- CHART CVR --}}
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small fw-bold mt-0 mb-0"><i class="bi bi-funnel" style="color: #a855f7;"></i> Top 5 CVR</div>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 0.5rem;">
                        Produk dengan tingkat konversi tertinggi (ID).
                    </div>
                    <div style="position: relative; height: 180px; display: flex; justify-content: center; align-items: center;">
                        @if(empty($itemPerformance) || count($itemPerformance) === 0)
                            <div style="color: var(--dsh-muted); font-size: 0.8rem; text-align: center;">Belum ada data produk aktif.</div>
                        @else
                            <canvas id="chartCvr"></canvas>
                        @endif
                    </div>
                </div>
            </div>

            <div class="dash-panels mb-4" style="grid-template-columns: 1fr;">
                {{-- HEATMAP JAM TAYANG --}}
                <div class="dpanel p-3">
                    <div class="dash-sec mt-0 mb-2"><i class="bi bi-clock text-primary"></i> Heatmap Jam Tayang (Golden Hours)</div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 1rem;">
                        💡 <b>Info:</b> Semakin gelap/pekat warnanya, semakin tinggi metrik pada jam tersebut.
                    </div>
                    <div style="position: relative; height: 250px;">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>
            </div>
            
            <hr class="my-4" style="border-color: var(--dsh-border);">
            
            <div class="dash-sec-focal mt-4 mb-3 p-3 p-md-4" style="background: var(--card-bg); border-radius: 12px; border: 1px solid var(--card-border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
                    <div>
                        <div class="dash-sec mb-1" style="font-size: 0.95rem; border-bottom: none; padding-bottom: 0;"><i class="bi bi-clock-history text-primary"></i> Komparasi Historis</div>
                        <div style="font-size: 0.75rem; color: var(--dsh-muted);">Perbandingan performa dengan periode sebelumnya.</div>
                    </div>
                    <div style="overflow-x: auto; padding-bottom: 0.25rem; scrollbar-width: none;">
                        <input type="hidden" id="histMetricSelect" value="roas">
                        <div class="dash-tabs-modern" id="histMetricChips" style="padding: 0.25rem;">
                            <button class="dash-tab-sm active" data-val="roas"><i class="bi bi-lightning-charge"></i> ROAS</button>
                            <button class="dash-tab-sm" data-val="gmv"><i class="bi bi-bag-check"></i> GMV</button>
                            <button class="dash-tab-sm" data-val="spend"><i class="bi bi-wallet2"></i> Biaya</button>
                            <button class="dash-tab-sm" data-val="impressions"><i class="bi bi-eye"></i> Impresi</button>
                            <button class="dash-tab-sm" data-val="clicks"><i class="bi bi-cursor"></i> Klik</button>
                            <button class="dash-tab-sm" data-val="ctr"><i class="bi bi-hand-index"></i> CTR</button>
                            <button class="dash-tab-sm" data-val="cvr"><i class="bi bi-funnel"></i> CVR</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="dash-panels mb-3" style="grid-template-columns: 1fr;">
                <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-border)" id="insightHistorical">
                    <div style="color: var(--dsh-muted); font-size: 0.8rem; display:flex; align-items:center; gap:0.5rem;">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Menganalisis perbandingan periode...
                    </div>
                </div>
            </div>
            
            <div class="dpanel p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85; margin-bottom: 1rem;">
                        💡 <b>Info:</b> Membandingkan performa rentang saat ini dengan rentang sebelumnya yang berdurasi sama persis.
                    </div>
                    <div id="histSummary" style="font-size: 0.8rem; font-weight: 700; color: var(--text); text-align: right;"></div>
                </div>
                <div style="position: relative; height: 350px;">
                    <canvas id="historicalChart"></canvas>
                </div>
            </div>
        </div>

        <!-- ANALISIS HARIAN TAB -->
        <div class="tab-pane" id="tab-daily">
            <div class="dash-sec"><i class="bi bi-graph-up"></i> Grafik Performa Harian</div>
            
            <div class="dash-panels" style="grid-template-columns: 1fr; gap: 1rem;">
                <!-- 1. TREN FINANSIAL HARIAN -->
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div style="font-weight: 650; font-size: 0.85rem; color: var(--dsh-muted);">Tren Finansial Harian</div>
                            <div class="mt-1" style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85;">
                                💡 <b>Finansial:</b> Selisih <b>GMV & Biaya</b> = Margin. <b>Garis ROAS (Emas)</b> = Profitabilitas.
                            </div>
                        </div>
                        <div id="dailySummary" style="font-size: 0.8rem; font-weight: 700; color: var(--text); text-align: right;"></div>
                    </div>
                    
                    <div class="mb-3 p-2" style="border-left: 4px solid var(--dsh-border); background: var(--dsh-bg); border-radius: 4px;" id="insightDailyTrend">
                        <div style="color: var(--dsh-muted); font-size: 0.75rem; display:flex; align-items:center; gap:0.5rem;">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            AI mendeteksi momentum finansial...
                        </div>
                    </div>
                    
                    <div style="height: 280px;">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>

                <!-- 2. TREN TRAFIK HARIAN -->
                <div class="dpanel p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div style="font-weight: 650; font-size: 0.85rem; color: var(--dsh-muted);">Tren Trafik Harian</div>
                            <div class="mt-1" style="font-size: 0.72rem; color: var(--dsh-muted); opacity: 0.85;">
                                💡 <b>Trafik (Funnel):</b> <b>Impresi (Kuning)</b> vs <b>Klik (Biru)</b> = Rasio bocor.
                            </div>
                        </div>
                        <div id="trafficSummary" style="font-size: 0.8rem; font-weight: 700; color: var(--text); text-align: right;"></div>
                    </div>
                    
                    <div class="mb-3 p-2" style="border-left: 4px solid var(--dsh-border); background: var(--dsh-bg); border-radius: 4px;" id="insightDailyTraffic">
                        <div style="color: var(--dsh-muted); font-size: 0.75rem; display:flex; align-items:center; gap:0.5rem;">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            AI menganalisis kebocoran funnel...
                        </div>
                    </div>
                    
                    <div style="height: 280px;">
                        <canvas id="trafficChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB RINCIAN KAMPANYE -->
        <div class="tab-pane" id="tab-campaigns">
            <div class="dash-sec"><i class="bi bi-megaphone"></i> Daftar Kampanye</div>
            
            @php
                $totalBoncos = 0;
                $totalHiddenGem = 0;
                $totalSpendBoncos = 0;
                foreach($campaigns as $c) {
                    $r = $c->spend > 0 ? $c->gmv / $c->spend : 0;
                    if ($c->spend > 50000 && $r < 1.5) { $totalBoncos++; $totalSpendBoncos += $c->spend; }
                    elseif ($r >= 5.0 && $c->spend > 10000) { $totalHiddenGem++; }
                }
            @endphp
            
            <div class="dash-panels mb-3" style="grid-template-columns: 1fr;">
                <div class="dpanel p-3" style="border-left: 4px solid {{ $totalBoncos > 0 ? '#dc2626' : '#16a34a' }}; background: var(--dsh-bg);">
                    <div class="d-flex align-items-center gap-2 mb-1" style="font-weight: 700; color: {{ $totalBoncos > 0 ? '#dc2626' : '#16a34a' }}; font-size: 0.85rem;">
                        <i class="bi {{ $totalBoncos > 0 ? 'bi-exclamation-triangle' : 'bi-check-circle' }}"></i> Audit Kampanye AI
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted);">
                        @if($totalBoncos > 0)
                            Ditemukan <b>{{ $totalBoncos }} kampanye berstatus Boncos</b> yang menyedot biaya sebesar <b>Rp {{ number_format($totalSpendBoncos, 0, ',', '.') }}</b>. Matikan atau perbaiki segera! 
                        @else
                            Tidak ada kampanye yang terindikasi boncos parah. Kondisi sangat sehat.
                        @endif
                        @if($totalHiddenGem > 0)
                            Terdapat <b>{{ $totalHiddenGem }} produk *Hidden Gem*</b> dengan efisiensi tinggi yang siap untuk diskalakan.
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="dpanel">
                <div class="table-responsive">
                    <table class="dpanel-table">
                        <thead>
                            <tr>
                                <th>Kampanye</th>
                                <th>Tipe</th>
                                <th>Diagnosis AI</th>
                                <th class="text-end">Biaya (Spend)</th>
                                <th class="text-end">GMV</th>
                                <th class="text-end">ROAS</th>
                                <th class="text-end">Klik</th>
                                <th class="text-end">Pesanan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($campaigns as $camp)
                                @php
                                    $c_roas = $camp->spend > 0 ? $camp->gmv / $camp->spend : 0;
                                    $c_prev_roas = $camp->prev_spend > 0 ? $camp->prev_gmv / $camp->prev_spend : 0;
                                    $c_cvr = $camp->clicks > 0 ? ($camp->orders / $camp->clicks) * 100 : 0;
                                    
                                    $gmv_growth = $camp->prev_gmv > 0 ? (($camp->gmv - $camp->prev_gmv) / $camp->prev_gmv) * 100 : ($camp->gmv > 0 ? 100 : 0);
                                    $roas_growth = $c_prev_roas > 0 ? (($c_roas - $c_prev_roas) / $c_prev_roas) * 100 : ($c_roas > 0 ? 100 : 0);
                                    $spend_growth = $camp->prev_spend > 0 ? (($camp->spend - $camp->prev_spend) / $camp->prev_spend) * 100 : ($camp->spend > 0 ? 100 : 0);
                                    $clicks_growth = $camp->prev_clicks > 0 ? (($camp->clicks - $camp->prev_clicks) / $camp->prev_clicks) * 100 : ($camp->clicks > 0 ? 100 : 0);
                                    $orders_growth = $camp->prev_orders > 0 ? (($camp->orders - $camp->prev_orders) / $camp->prev_orders) * 100 : ($camp->orders > 0 ? 100 : 0);
                                    
                                    $ai_status = '⚖️ Normal';
                                    $ai_color = 'var(--dsh-muted)';
                                    $ai_bg = 'transparent';
                                    $ai_note = 'Performa standar.';
                                    
                                    if ($camp->spend > 50000 && $c_roas < 1.5) {
                                        $ai_status = '🚨 Boncos (Stop!)';
                                        $ai_color = '#dc2626';
                                        $ai_bg = 'rgba(220, 38, 38, 0.05)';
                                        if ($roas_growth < -20) {
                                            $ai_note = 'ROAS anjlok ' . abs(round($roas_growth)) . '%. Kebocoran ekstrem!';
                                        } else {
                                            $ai_note = 'Membakar uang tanpa hasil konversi.';
                                        }
                                    } elseif ($c_roas >= 5.0 && $camp->spend > 10000) {
                                        $ai_status = '💎 Hidden Gem';
                                        $ai_color = '#16a34a';
                                        $ai_bg = 'rgba(22, 163, 74, 0.05)';
                                        if ($gmv_growth > 20) {
                                            $ai_note = 'On Fire! GMV meroket ' . round($gmv_growth) . '%. Skalakan budget.';
                                        } else {
                                            $ai_note = 'Super efisien! Siap untuk diskalakan.';
                                        }
                                    } elseif ($c_cvr < 0.5 && $camp->clicks > 100) {
                                        $ai_status = '⚠️ Window Shopping';
                                        $ai_color = '#eab308';
                                        $ai_bg = 'rgba(234, 179, 8, 0.05)';
                                        $ai_note = 'Banyak klik tapi zonk. Cek harga/kompetitor.';
                                    } elseif ($c_roas >= 2.0 && $c_roas < 5.0 && $camp->spend > 50000) {
                                        $ai_status = '🚀 Tulang Punggung';
                                        $ai_color = '#3b82f6';
                                        $ai_bg = 'rgba(59, 130, 246, 0.05)';
                                        if ($gmv_growth < -15) {
                                            $ai_note = 'Volume GMV menyusut ' . abs(round($gmv_growth)) . '%. Cek budget harian.';
                                        } else {
                                            $ai_note = 'Mesin pencetak GMV berjalan stabil.';
                                        }
                                    } elseif ($camp->spend < 10000) {
                                        $ai_status = '💤 Pasif';
                                        $ai_color = 'var(--dsh-muted)';
                                        $ai_bg = 'transparent';
                                        $ai_note = 'Kekurangan trafik atau dibatasi budget.';
                                    }
                                @endphp
                                <tr style="background: {{ $ai_bg }}; border-bottom: 1px solid var(--dsh-border);">
                                    <td style="padding-top: 0.8rem; padding-bottom: 0.8rem;">
                                        <div style="font-weight: 700; color: var(--text);">{{ $camp->campaign_name }}</div>
                                        <div style="font-family: ui-monospace, monospace; font-size: .7rem; color: var(--dsh-muted);">ID: {{ $camp->channel_campaign_id }} &bull; <span class="{{ $camp->status == 'ONGOING' ? 'text-success' : 'text-muted' }}">{{ $camp->status }}</span></div>
                                    </td>
                                    <td><span style="font-size: .75rem; color: var(--dsh-muted);">{{ $camp->campaign_type }}</span></td>
                                    <td>
                                        <div style="font-weight: 700; color: {{ $ai_color }}; font-size: 0.75rem;">{{ $ai_status }}</div>
                                        <div style="font-size: 0.65rem; color: var(--dsh-muted); opacity: 0.9;">{{ $ai_note }}</div>
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; font-weight:700; color: #dc2626;">
                                        Rp {{ number_format($camp->spend, 0, ',', '.') }}
                                        @if($spend_growth != 0)
                                            <div style="font-size: 0.65rem; color: {{ $spend_growth > 0 ? '#dc2626' : '#16a34a' }};">
                                                {{ $spend_growth > 0 ? '▲' : '▼' }} {{ abs(round($spend_growth)) }}%
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; font-weight:700; color: #16a34a;">
                                        Rp {{ number_format($camp->gmv, 0, ',', '.') }}
                                        @if($gmv_growth != 0)
                                            <div style="font-size: 0.65rem; color: {{ $gmv_growth > 0 ? '#16a34a' : '#dc2626' }};">
                                                {{ $gmv_growth > 0 ? '▲' : '▼' }} {{ abs(round($gmv_growth)) }}%
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; font-weight:700; color: {{ $c_roas >= 4.0 ? '#16a34a' : ($c_roas >= 2.0 ? '#eab308' : '#dc2626') }};">
                                        {{ number_format($c_roas, 2) }}x
                                        @if($roas_growth != 0)
                                            <div style="font-size: 0.65rem; color: {{ $roas_growth > 0 ? '#16a34a' : '#dc2626' }};">
                                                {{ $roas_growth > 0 ? '▲' : '▼' }} {{ abs(round($roas_growth)) }}%
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; color: var(--dsh-muted);">
                                        {{ number_format($camp->clicks, 0, ',', '.') }}
                                        @if($clicks_growth != 0)
                                            <div style="font-size: 0.65rem; color: {{ $clicks_growth > 0 ? '#16a34a' : '#dc2626' }};">
                                                {{ $clicks_growth > 0 ? '▲' : '▼' }} {{ abs(round($clicks_growth)) }}%
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; color: var(--dsh-muted);">
                                        {{ number_format($camp->orders, 0, ',', '.') }}
                                        @if($orders_growth != 0)
                                            <div style="font-size: 0.65rem; color: {{ $orders_growth > 0 ? '#16a34a' : '#dc2626' }};">
                                                {{ $orders_growth > 0 ? '▲' : '▼' }} {{ abs(round($orders_growth)) }}%
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4" style="color: var(--dsh-muted); font-size: .8rem;">
                                        Belum ada data kampanye.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        </div>

        <!-- TAB PERFORMA PRODUK (GMV MAX) -->
        <div class="tab-pane" id="tab-items">
            <div class="dash-sec"><i class="bi bi-grid-1x2"></i> Indikator Performa (KPI) Keseluruhan</div>
            
            <div class="dash-grid mb-4">
                @php
                    $metrics = [
                        ['title' => 'Biaya (Spend)', 'key' => 'spend', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'red', 'icon' => 'bi-wallet2'],
                        ['title' => 'GMV (Pendapatan)', 'key' => 'gmv', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'green', 'icon' => 'bi-bag-check'],
                        ['title' => 'ROAS', 'key' => 'roas', 'prefix' => '', 'suffix' => 'x', 'cls' => 'blue', 'icon' => 'bi-lightning-charge'],
                        ['title' => 'Pesanan', 'key' => 'orders', 'prefix' => '', 'suffix' => '', 'cls' => 'slate', 'icon' => 'bi-box-seam'],
                        ['title' => 'AOV', 'key' => 'aov', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'slate', 'icon' => 'bi-cart-check'],
                        ['title' => 'Impression', 'key' => 'impressions', 'prefix' => '', 'suffix' => '', 'cls' => 'amber', 'icon' => 'bi-eye'],
                        ['title' => 'CTR', 'key' => 'ctr', 'prefix' => '', 'suffix' => '%', 'cls' => 'amber', 'icon' => 'bi-hand-index'],
                        ['title' => 'Klik', 'key' => 'clicks', 'prefix' => '', 'suffix' => '', 'cls' => 'violet', 'icon' => 'bi-cursor'],
                        ['title' => 'CVR', 'key' => 'cvr', 'prefix' => '', 'suffix' => '%', 'cls' => 'violet', 'icon' => 'bi-funnel'],
                        ['title' => 'CPC', 'key' => 'cpc', 'prefix' => 'Rp ', 'suffix' => '', 'cls' => 'red', 'icon' => 'bi-coin'],
                    ];
                @endphp
                @foreach($metrics as $m)
                    @php
                        $currSpend = $kpi['current']->spend ?? 0;
                        $currGmv = $kpi['current']->gmv ?? 0;
                        $currOrders = $kpi['current']->orders ?? 0;
                        $currClicks = $kpi['current']->clicks ?? 0;
                        $currImpressions = $kpi['current']->impressions ?? 0;

                        $prevSpend = $kpi['previous']->spend ?? 0;
                        $prevGmv = $kpi['previous']->gmv ?? 0;
                        $prevOrders = $kpi['previous']->orders ?? 0;
                        $prevClicks = $kpi['previous']->clicks ?? 0;
                        $prevImpressions = $kpi['previous']->impressions ?? 0;

                        $val = $kpi['current']->{$m['key']} ?? 0;
                        $prevVal = $kpi['previous']->{$m['key']} ?? 0;

                        if($m['key'] === 'roas') {
                            $val = $currSpend > 0 ? round($currGmv / $currSpend, 2) : 0;
                            $prevVal = $prevSpend > 0 ? round($prevGmv / $prevSpend, 2) : 0;
                        } elseif ($m['key'] === 'aov') {
                            $val = $currOrders > 0 ? round($currGmv / $currOrders, 0) : 0;
                            $prevVal = $prevOrders > 0 ? round($prevGmv / $prevOrders, 0) : 0;
                        } elseif ($m['key'] === 'cpc') {
                            $val = $currClicks > 0 ? round($currSpend / $currClicks, 0) : 0;
                            $prevVal = $prevClicks > 0 ? round($prevSpend / $prevClicks, 0) : 0;
                        } elseif ($m['key'] === 'ctr') {
                            $val = $currImpressions > 0 ? round(($currClicks / $currImpressions) * 100, 2) : 0;
                            $prevVal = $prevImpressions > 0 ? round(($prevClicks / $prevImpressions) * 100, 2) : 0;
                        } elseif ($m['key'] === 'cvr') {
                            $val = $currClicks > 0 ? round(($currOrders / $currClicks) * 100, 2) : 0;
                            $prevVal = $prevClicks > 0 ? round(($prevOrders / $prevClicks) * 100, 2) : 0;
                        }

                        $change = $kpi['changes'][$m['key']] ?? 0;
                        if (in_array($m['key'], ['aov', 'cpc', 'ctr', 'cvr'])) {
                            if ($prevVal == 0) {
                                $change = $val > 0 ? 100 : 0;
                            } else {
                                $change = round((($val - $prevVal) / $prevVal) * 100, 2);
                            }
                        }

                        $isUp = $change >= 0;
                        
                        // For cost metrics, going down is good (green). For others, going up is good.
                        if (in_array($m['key'], ['spend', 'cpc'])) {
                            $colorClass = $isUp && $change > 0 ? 'color: #dc2626;' : 'color: #16a34a;';
                        } else {
                            $colorClass = $isUp ? 'color: #16a34a;' : 'color: #dc2626;';
                        }
                    @endphp
                    <div class="kpi {{ $m['cls'] }}">
                        <div class="kpi-label">
                            <div class="ico"><i class="bi {{ $m['icon'] }}"></i></div>
                            {{ $m['title'] }}
                        </div>
                        <div class="kpi-value {{ in_array($m['key'], ['spend', 'gmv', 'aov']) ? 'sm' : '' }}" style="font-family: ui-monospace, monospace;">
                            {{ $m['prefix'] }}{{ is_float($val) ? number_format($val, 2, ',', '.') : number_format($val, 0, ',', '.') }}{{ $m['suffix'] }}
                        </div>
                        <div class="kpi-sub">
                            <span style="font-weight:700; {{ $colorClass }}">
                                <i class="bi bi-arrow-{{ $isUp ? 'up-right' : 'down-right' }}"></i> {{ abs($change) }}%
                            </span> 
                            vs rentang lalu
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="dash-sec"><i class="bi bi-box-seam"></i> Performa Produk (GMV Max)</div>
            
            <div class="dash-panels mb-3" style="grid-template-columns: 1fr;">
                <div class="dpanel p-3" style="border-left: 4px solid var(--dsh-accent); background: var(--dsh-bg);">
                    <div class="d-flex align-items-center gap-2 mb-1" style="font-weight: 700; color: var(--dsh-accent); font-size: 0.85rem;">
                        <i class="bi bi-info-circle"></i> Info Data GMV Max
                    </div>
                    <div style="font-size: 0.72rem; color: var(--dsh-muted);">
                        Tabel ini menampilkan performa spesifik per produk berdasarkan data **Shop GMV Max (GMS)**.
                        Metrik di bawah ini ditarik langsung dari mesin otomatisasi Shopee.
                    </div>
                </div>
            </div>

            <div class="dpanel">
                <div class="table-responsive">
                    <table class="dpanel-table">
                        <thead>
                            <tr>
                                <th>Produk / Item ID</th>
                                <th class="text-end">Biaya (Spend)</th>
                                <th class="text-end">GMV</th>
                                <th class="text-end">ROAS</th>
                                <th class="text-end">Impresi</th>
                                <th class="text-end">Klik</th>
                                <th class="text-end">Pesanan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($itemPerformance ?? [] as $item)
                                @php
                                    $itemRoas = $item->spend > 0 ? $item->gmv / $item->spend : 0;
                                @endphp
                                <tr>
                                    <td style="padding-top: 0.8rem; padding-bottom: 0.8rem;">
                                        <div style="font-weight: 700; color: var(--text); white-space: normal; max-width: 300px;">
                                            {{ $item->item_name ?? 'Produk Tidak Diketahui' }}
                                        </div>
                                        <div style="font-family: ui-monospace, monospace; font-size: .7rem; color: var(--dsh-muted);">
                                            ID: {{ $item->channel_item_id }}
                                        </div>
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; font-weight:700; color: #dc2626;">
                                        Rp {{ number_format($item->spend, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; font-weight:700; color: #16a34a;">
                                        Rp {{ number_format($item->gmv, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; font-weight:700; color: {{ $itemRoas >= 4.0 ? '#16a34a' : ($itemRoas >= 2.0 ? '#eab308' : '#dc2626') }};">
                                        {{ number_format($itemRoas, 2) }}x
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; color: var(--dsh-muted);">
                                        {{ number_format($item->impressions, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; color: var(--dsh-muted);">
                                        {{ number_format($item->clicks, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end" style="font-family: ui-monospace, monospace; color: var(--dsh-muted);">
                                        {{ number_format($item->orders, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4" style="color: var(--dsh-muted); font-size: .8rem;">
                                        Belum ada data performa item GMV Max.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SINKRONISASI TAB -->
        <div class="tab-pane" id="tab-sync">
            
            <!-- Live Progress Bar (Hidden by default) -->
            <div id="liveSyncProgress" class="dpanel mb-3 p-3" style="display: none; border-left: 4px solid var(--dsh-accent); background: var(--dsh-bg);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-weight: 600; font-size: .85rem; color: var(--text);">Sinkronisasi Sedang Berjalan...</span>
                    <span id="liveSyncPercent" style="font-size: .75rem; font-weight: 700; color: var(--dsh-accent);">0%</span>
                </div>
                <div style="width: 100%; height: 6px; background: var(--dsh-border); border-radius: 4px; overflow: hidden;">
                    <div id="liveSyncBar" style="width: 0%; height: 100%; background: var(--dsh-accent); transition: width 0.3s ease;"></div>
                </div>
                <div id="liveSyncLog" style="margin-top: .5rem; font-size: .7rem; font-family: ui-monospace, monospace; color: var(--dsh-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    Menghubungkan ke server...
                </div>
            </div>

            <div class="dash-panels" style="grid-template-columns: 1fr;">
                <!-- Sync Logs -->
                <div>
                    <div class="dash-sec"><i class="bi bi-clock-history"></i> Riwayat Sinkronisasi (Log)</div>
                    <div class="dpanel">
                        <div class="table-responsive">
                            <table class="dpanel-table">
                                <thead>
                                    <tr>
                                        <th>Waktu Mulai</th>
                                        <th>Tipe</th>
                                        <th>Status</th>
                                        <th>Detail / Error</th>
                                        <th class="text-end">Req/Rows</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(isset($syncRuns) && $syncRuns->count() > 0)
                                        @foreach($syncRuns as $run)
                                            @php
                                                $statusClass = 'slate';
                                                if ($run->status == 'success') $statusClass = 'green';
                                                elseif ($run->status == 'error') $statusClass = 'red';
                                                elseif ($run->status == 'rate_limited') $statusClass = 'amber';
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div style="font-weight: 700; color: var(--text);">{{ $run->created_at->format('d/m/Y') }}</div>
                                                    <div style="font-family: ui-monospace, monospace; font-size: .7rem; color: var(--dsh-muted);">{{ $run->created_at->format('H:i:s') }}</div>
                                                </td>
                                                <td><span style="font-size: .75rem; color: var(--dsh-muted);">{{ $run->sync_type }}</span></td>
                                                <td><span class="pill {{ $statusClass }}">{{ strtoupper($run->status) }}</span></td>
                                                <td>
                                                    <div style="font-size: .75rem; color: var(--dsh-muted); max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $run->error_message }}">
                                                        {{ $run->error_message ?? '-' }}
                                                    </div>
                                                </td>
                                                <td class="text-end" style="font-family: ui-monospace, monospace; font-size: .75rem; font-weight: 650; color: var(--text);">
                                                    {{ $run->total_requests }} <span style="font-weight: normal; color: var(--dsh-muted);">/</span> <span style="color: #16a34a;">{{ $run->total_updated }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="5" class="text-center py-4" style="color: var(--dsh-muted); font-size: .8rem;">Belum ada riwayat sinkronisasi.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    @endif
</div>

<!-- Modal Sync Manual -->
<div class="modal fade" id="modalSyncAds" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px; background: var(--card-bg); border: 1px solid var(--card-border); box-shadow: var(--card-shadow);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: var(--text);">Manual Sync Shopee Ads</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Form State -->
                <form id="formSyncAds" action="/api/marketplace/ads-daily/sync" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label style="font-size: .75rem; font-weight: 650; color: var(--dsh-muted); display: block; margin-bottom: .4rem;">Toko Target</label>
                        <select name="store_id" class="form-control" style="border-radius: 8px; font-size: .85rem; background: var(--bg); color: var(--text); border-color: var(--dsh-border);" required>
                            @foreach($stores as $s)
                                <option value="{{ $s->id }}" {{ isset($storeId) && $storeId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label style="font-size: .75rem; font-weight: 650; color: var(--dsh-muted); display: block; margin-bottom: .4rem;">Jangka Waktu Sinkronisasi</label>
                        <select name="sync_type" class="form-control" style="border-radius: 8px; font-size: .85rem; background: var(--bg); color: var(--text); border-color: var(--dsh-border);">
                            <option value="today">Hari Ini</option>
                            <option value="yesterday">Kemarin</option>
                            <option value="1_week">1 Minggu Terakhir</option>
                            <option value="1_month">1 Bulan Terakhir</option>
                            <option value="3_months">3 Bulan Terakhir</option>
                            <option value="custom">Berdasarkan Tanggal Filter</option>
                        </select>
                    </div>
                    <button type="submit" class="btn w-100 fw-bold" style="background: var(--dsh-accent); color: #fff; border-radius: 12px; padding: .6rem;">
                        <i class="bi bi-cloud-download"></i> Jalankan Sync
                    </button>
                </form>

                <!-- Loading State (Hidden by default) -->
                <div id="loadingSyncAds" style="display: none; text-align: left; padding: 1rem;">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <h6 class="fw-bold" style="color: var(--text);"><i class="bi bi-arrow-repeat spin-icon" style="display: inline-block;"></i> Sedang Menarik Data...</h6>
                        <p style="font-size: .8rem; color: var(--dsh-muted);">Proses ini mengambil performa iklan langsung dari Shopee.</p>
                    </div>
                    
                    <div class="progress mb-3" style="height: 10px; border-radius: 10px; background: var(--dsh-border);">
                        <div id="syncProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%; background: var(--dsh-accent);" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    
                    <div id="syncLogs" style="background: rgba(0,0,0,0.4); border: 1px solid var(--dsh-border); border-radius: 8px; padding: 10px; font-family: monospace; font-size: 0.75rem; color: #a1a1aa; height: 150px; overflow-y: auto; text-align: left; line-height: 1.5;">
                        <div style="color: #4ade80;">> Menghubungkan ke server...</div>
                    </div>
                </div>
                
                <!-- Success State (Hidden by default) -->
                <div id="successSyncAds" style="display: none; text-align: center; padding: 2rem 0;">
                    <div style="font-size: 3rem; color: #16a34a; margin-bottom: 1rem;"><i class="bi bi-check-circle-fill"></i></div>
                    <h6 class="fw-bold" style="color: var(--text);">Sinkronisasi Selesai!</h6>
                    <p style="font-size: .8rem; color: var(--dsh-muted);">Data berhasil diperbarui.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {
    window.clearAdsData = async function() {
        if (confirm('Anda yakin ingin membersihkan semua data performa iklan? Proses ini tidak dapat dibatalkan.')) {
            try {
                const res = await fetch("{{ route('marketplace.ads.clear') }}", {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await res.json();
                if (res.ok) {
                    alert('Data iklan berhasil dibersihkan!');
                    location.reload();
                } else {
                    throw new Error(data.message || 'Terjadi kesalahan.');
                }
            } catch (err) {
                alert('Gagal membersihkan data: ' + err.message);
            }
        }
    };

    const formSync = document.getElementById('formSyncAds');
    if (formSync) {
        formSync.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const loading = document.getElementById('loadingSyncAds');
            const success = document.getElementById('successSyncAds');
            
            // Hide form, show loading
            form.style.display = 'none';
            loading.style.display = 'block';
            
            try {
                const formData = new FormData(form);
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/x-ndjson',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });
                
                if (!res.ok) {
                    throw new Error('Terjadi kesalahan koneksi (' + res.status + ')');
                }
                
                const reader = res.body.getReader();
                const decoder = new TextDecoder();
                const logEl = document.getElementById('syncLogs');
                const progressBar = document.getElementById('syncProgressBar');
                
                const liveProgress = document.getElementById('liveSyncProgress');
                const liveBar = document.getElementById('liveSyncBar');
                const livePercent = document.getElementById('liveSyncPercent');
                const liveLog = document.getElementById('liveSyncLog');
                
                if (liveProgress) liveProgress.style.display = 'block';
                
                let savedData = 0;
                let hasErrors = false;
                let allErrors = [];
                let isDone = false;
                
                logEl.innerHTML = '<div style="color: #4ade80;">> Menghubungkan ke server...</div>';
                progressBar.style.width = '0%';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    
                    const chunkStr = decoder.decode(value, { stream: true });
                    const lines = chunkStr.split('\n').filter(line => line.trim() !== '');
                    
                    for (const line of lines) {
                        try {
                            const data = JSON.parse(line);
                            
                            if (data.type === 'log' || data.type === 'info' || data.type === 'done') {
                                const color = data.type === 'done' ? '#60a5fa' : (data.type === 'info' ? '#facc15' : '#a1a1aa');
                                logEl.innerHTML += `<div style="color: ${color};">> ${data.message}</div>`;
                                logEl.scrollTop = logEl.scrollHeight;
                                if (liveLog) liveLog.innerText = `> ${data.message}`;
                            }
                            
                            if (data.progress !== undefined && data.progress !== null) {
                                progressBar.style.width = data.progress + '%';
                                if (liveBar) liveBar.style.width = data.progress + '%';
                                if (livePercent) livePercent.innerText = Math.round(data.progress) + '%';
                            }
                            
                            if (data.type === 'done') {
                                isDone = true;
                                savedData = data.saved || 0;
                                if (data.errors && data.errors.length > 0) {
                                    hasErrors = true;
                                    allErrors = data.errors;
                                }
                            }
                        } catch (e) {
                            console.error('Failed to parse NDJSON line:', line, e);
                        }
                    }
                }
                
                if (hasErrors) {
                    alert('Sync Selesai dengan beberapa masalah:\n' + allErrors.join("\n"));
                }
                
                if (savedData > 0 || !hasErrors) {
                    loading.style.display = 'none';
                    success.style.display = 'block';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    loading.style.display = 'none';
                    form.style.display = 'block';
                }
            } catch (err) {
                alert('Gagal:\n' + err.message);
                loading.style.display = 'none';
                form.style.display = 'block';
            }
        });
    }
});
</script>
@if(!empty($dailyChartData))
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const rawDaily = @json($dailyChartData ?? []);
    const rawHourly = @json($heatmapData ?? []);
    const rawHistorical = @json($historicalData ?? []);
    
    // Pad Daily Data to show full range
    const dailyData = [];
    const fromEl = document.getElementById('fromHidden');
    const toEl = document.getElementById('toHidden');
    if (fromEl && toEl && fromEl.value && toEl.value) {
        // use ymd function defined earlier or create inline logic
        function ymdLocal(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
        const dStart = new Date(fromEl.value);
        const dEnd = new Date(toEl.value);
        for (let d = new Date(dStart); d <= dEnd; d.setDate(d.getDate() + 1)) {
            let ds = ymdLocal(d);
            let found = rawDaily.find(item => {
                if(!item.date) return false;
                let itemDate = new Date(item.date);
                return ymdLocal(itemDate) === ds;
            });
            dailyData.push(found ? found : { date: ds, spend: 0, gmv: 0, roas: 0 });
        }
    } else {
        dailyData.push(...rawDaily);
    }

    // Pad Hourly Data to show full 24 hours
    const hourlyData = [];
    for (let i = 0; i < 24; i++) {
        let found = rawHourly.find(d => parseInt(d.performance_hour) === i);
        hourlyData.push(found ? found : { performance_hour: i, clicks: 0, orders: 0, expense: 0, gmv: 0 });
    }
    
    // Theme & UX Colors
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#94a3b8' : '#64748b'; // softer text for axes
    const gridColor = isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.04)';
    const tooltipBg = isDark ? 'rgba(15,23,42,0.95)' : 'rgba(255,255,255,0.95)';
    const tooltipBorder = isDark ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.08)';
    const tooltipText = isDark ? '#f8fafc' : '#0f172a';
    
    Chart.defaults.color = textColor;
    Chart.defaults.font.family = 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace';

    // Helper: Format Rupiah Singkat (Jt/Rb)
    const formatShortIDR = (value) => {
        if(value >= 1000000) return (value / 1000000).toFixed(1).replace(/\.0$/, '') + ' Jt';
        if(value >= 1000) return (value / 1000).toFixed(1).replace(/\.0$/, '') + ' Rb';
        return value;
    };

    // Helper: Format Indo Date (YYYY-MM-DD to DD MMM YYYY)
    const formatIndoDate = (dateStr) => {
        if(!dateStr) return '';
        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
        let parts = dateStr.split('-');
        if(parts.length !== 3) return dateStr;
        return parseInt(parts[2]) + ' ' + months[parseInt(parts[1])-1] + ' ' + parts[0];
    };

    // Calculate summaries for charts
    let totalDailySpend = dailyData.reduce((sum, d) => sum + parseFloat(d.spend || 0), 0);
    let totalDailyGmv = dailyData.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);
    let totalDailyRoas = totalDailySpend > 0 ? (totalDailyGmv / totalDailySpend).toFixed(2) : "0.00";
    let dsEl = document.getElementById('dailySummary');
    if(dsEl) {
        dsEl.innerHTML = `<span style="color:#dc2626">Rp ${formatShortIDR(totalDailySpend)} Biaya</span> &bull; <span style="color:#16a34a">Rp ${formatShortIDR(totalDailyGmv)} GMV</span> &bull; <span style="color:#eab308">${totalDailyRoas}x ROAS</span>`;
    }

    let totalHourlySpend = hourlyData.reduce((sum, d) => sum + parseFloat(d.expense || 0), 0);
    let totalHourlyGmv = hourlyData.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);
    let totalHourlyRoas = totalHourlySpend > 0 ? (totalHourlyGmv / totalHourlySpend).toFixed(2) : "0.00";
    let hsEl = document.getElementById('hourlySummary');
    if(hsEl) {
        hsEl.innerHTML = `<span style="color:#dc2626">Rp ${formatShortIDR(totalHourlySpend)} Biaya</span> &bull; <span style="color:#10b981">Rp ${formatShortIDR(totalHourlyGmv)} GMV</span> &bull; <span style="color:#eab308">${totalHourlyRoas}x ROAS</span>`;
    }

    let totalDailyImpressions = dailyData.reduce((sum, d) => sum + parseInt(d.impressions || 0), 0);
    let totalDailyClicks = dailyData.reduce((sum, d) => sum + parseInt(d.clicks || 0), 0);
    let avgDailyCtr = totalDailyImpressions > 0 ? ((totalDailyClicks / totalDailyImpressions) * 100).toFixed(2) : "0.00";
    let tsEl = document.getElementById('trafficSummary');
    if(tsEl) {
        tsEl.innerHTML = `<span style="color:#f59e0b">${formatShortIDR(totalDailyImpressions)} Impresi</span> &bull; <span style="color:#3b82f6">${formatShortIDR(totalDailyClicks)} Klik</span> &bull; <span style="color:#8b5cf6">${avgDailyCtr}% CTR</span>`;
    }

    // --- AI INSIGHTS GENERATOR (ULTRA SMART EDITION) ---
    let totalDailyOrders = dailyData.reduce((sum, d) => sum + parseInt(d.orders || 0), 0);
    let avgDailyCvr = totalDailyClicks > 0 ? ((totalDailyOrders / totalDailyClicks) * 100).toFixed(2) : "0.00";
    let avgCpc = totalDailyClicks > 0 ? (totalDailySpend / totalDailyClicks) : 0;
    
    // Hitung Average Order Value (AOV) / Rata-rata Nilai Pesanan
    let aov = totalDailyOrders > 0 ? (totalDailyGmv / totalDailyOrders) : 0;
    // Asumsi batas aman CPC adalah 10% dari AOV (Batas wajar margin)
    let maxSafeCpc = aov * 0.10;
    
    let dayCount = dailyData.length || 1;

    // 1. Health Check & Margin Analysis
    let healthEl = document.getElementById('insightHealth');
    if (healthEl) {
        let healthHtml = '';
        if (totalDailyRoas >= 5.0 && (totalDailySpend / dayCount) < 50000) {
            healthHtml = `<div style="font-weight: 700; color: #16a34a; font-size: 0.85rem; margin-bottom: 0.3rem;">🚀 Kehilangan Momentum (Scaling)</div>
                          <div style="font-size: 0.72rem; color: var(--dsh-muted);">ROAS Anda luar biasa (<b>${totalDailyRoas}x</b>), tapi modal harian terlalu kecil. Anda membiarkan kompetitor mengambil sisa pelanggan! 💡 <b>Saran:</b> Naikkan modal harian 15-20%.</div>`;
            healthEl.style.borderLeftColor = '#16a34a';
        } else if (totalDailyRoas >= 4.0) {
            healthHtml = `<div style="font-weight: 700; color: #16a34a; font-size: 0.85rem; margin-bottom: 0.3rem;">🟢 Mesin Profit Maksimal</div>
                          <div style="font-size: 0.72rem; color: var(--dsh-muted);">Efisiensi luar biasa dengan ROAS <b>${totalDailyRoas}x</b>. Rata-rata keranjang belanja (AOV) berada di <b>Rp ${formatShortIDR(aov)}</b>. Pertahankan strategi ini!</div>`;
            healthEl.style.borderLeftColor = '#16a34a';
        } else if (totalDailyRoas >= 1.5 && avgCpc > maxSafeCpc && maxSafeCpc > 0) {
            healthHtml = `<div style="font-weight: 700; color: #dc2626; font-size: 0.85rem; margin-bottom: 0.3rem;">🚨 Bahaya Margin (CPC vs AOV)</div>
                          <div style="font-size: 0.72rem; color: var(--dsh-muted);">Biaya per klik Anda (<b>Rp ${formatShortIDR(avgCpc)}</b>) terlalu mahal dibanding rata-rata nilai pesanan (<b>Rp ${formatShortIDR(aov)}</b>). Ini akan menggerus profit bersih Anda! 💡 <b>Saran:</b> Naikkan Target ROAS pada kampanye GMV Max Anda agar algoritma mencari pembeli yang lebih murah.</div>`;
            healthEl.style.borderLeftColor = '#dc2626';
        } else if (totalDailyRoas >= 2.0) {
            healthHtml = `<div style="font-weight: 700; color: #eab308; font-size: 0.85rem; margin-bottom: 0.3rem;">🟡 Profit Tipis (Waspada)</div>
                          <div style="font-size: 0.72rem; color: var(--dsh-muted);">ROAS di level <b>${totalDailyRoas}x</b>. Masih profit, namun sangat rentan jika ada retur barang atau perang harga. Evaluasi produk mana di GMV Max yang menyedot biaya tapi seret penjualan.</div>`;
            healthEl.style.borderLeftColor = '#eab308';
        } else {
            healthHtml = `<div style="font-weight: 700; color: #dc2626; font-size: 0.85rem; margin-bottom: 0.3rem;">🔴 Darurat Kebocoran Anggaran</div>
                          <div style="font-size: 0.72rem; color: var(--dsh-muted);">ROAS hancur di angka <b>${totalDailyRoas}x</b>. Anda mensubsidi pembeli. 💡 <b>Saran:</b> Segera evaluasi produk di dalam kampanye GMV Max, atau naikkan Target ROAS secara drastis untuk mengerem pengeluaran!</div>`;
            healthEl.style.borderLeftColor = '#dc2626';
        }
        healthEl.innerHTML = healthHtml;
    }

    // 2. Traffic Detective (CTR vs CVR)
    let trafficEl = document.getElementById('insightTraffic');
    if (trafficEl) {
        let trafficHtml = '';
        if (avgDailyCtr > 3.0 && avgDailyCvr < 1.0 && totalDailyClicks > 50) {
            trafficHtml = `<div style="font-weight: 700; color: #dc2626; font-size: 0.85rem; margin-bottom: 0.3rem;">📉 Sindrom "Cuma Lihat-Lihat"</div>
                           <div style="font-size: 0.72rem; color: var(--dsh-muted);">Iklan sangat memancing klik (CTR <b>${avgDailyCtr}%</b>), tapi gagal jadi penjualan (CVR <b>${avgDailyCvr}%</b>). 💡 <b>Saran:</b> Harga mungkin terlalu mahal dibanding kompetitor, atau ulasan produk kurang meyakinkan.</div>`;
            trafficEl.style.borderLeftColor = '#dc2626';
        } else if (avgDailyCtr < 1.5 && avgDailyCvr > 3.0 && totalDailyImpressions > 500) {
            trafficHtml = `<div style="font-weight: 700; color: #8b5cf6; font-size: 0.85rem; margin-bottom: 0.3rem;">💎 Berlian Tersembunyi</div>
                           <div style="font-size: 0.72rem; color: var(--dsh-muted);">Produk Anda laku keras bagi yang sudah klik (CVR <b>${avgDailyCvr}%</b>), tapi jarang diklik di hasil pencarian (CTR <b>${avgDailyCtr}%</b>). 💡 <b>Saran:</b> Segera ganti foto utama agar lebih mencolok!</div>`;
            trafficEl.style.borderLeftColor = '#8b5cf6';
        } else if (avgDailyCtr < 1.5 && totalDailyImpressions > 1000) {
            trafficHtml = `<div style="font-weight: 700; color: #f59e0b; font-size: 0.85rem; margin-bottom: 0.3rem;">👁️ Kebocoran Trafik</div>
                           <div style="font-size: 0.72rem; color: var(--dsh-muted);">CTR sangat rendah (<b>${avgDailyCtr}%</b>). Ribuan orang melihat iklan tapi lewat begitu saja. 💡 <b>Saran:</b> Optimalkan judul atau pasang label diskon di foto utama.</div>`;
            trafficEl.style.borderLeftColor = '#f59e0b';
        } else {
            trafficHtml = `<div style="font-weight: 700; color: #3b82f6; font-size: 0.85rem; margin-bottom: 0.3rem;">🎯 Trafik Optimal</div>
                           <div style="font-size: 0.72rem; color: var(--dsh-muted);">Daya tarik iklan (CTR <b>${avgDailyCtr}%</b>) dan daya beli (CVR <b>${avgDailyCvr}%</b>) berada dalam rasio yang seimbang dan wajar.</div>`;
            trafficEl.style.borderLeftColor = '#3b82f6';
        }
        trafficEl.innerHTML = trafficHtml;
    }

    // 3. Golden Hour (Dayparting)
    let timeEl = document.getElementById('insightTime');
    if (timeEl) {
        let bestHour = '-';
        let highestScore = 0;
        let gmvAtBest = 0;
        
        hourlyData.forEach(d => {
            let sp = parseFloat(d.expense || 0);
            let gm = parseFloat(d.gmv || 0);
            // Cek jika jam ini menghasilkan GMV setidaknya 5% dari total (bukan kebetulan 1 klik hoki)
            if (sp > 1000 && gm > (totalHourlyGmv * 0.05)) { 
                let r = gm / sp;
                // Score kombinasi antara ROAS dan besaran GMV
                let score = r * (gm / totalHourlyGmv);
                if (score > highestScore) {
                    highestScore = score;
                    bestHour = d.performance_hour;
                    gmvAtBest = gm;
                }
            }
        });
        
        if (highestScore > 0) {
            let gmvPct = ((gmvAtBest / totalHourlyGmv) * 100).toFixed(0);
            timeEl.innerHTML = `<div style="font-weight: 700; color: #d97706; font-size: 0.85rem; margin-bottom: 0.3rem;">⏳ Waktu Emas (Dayparting)</div>
                                <div style="font-size: 0.72rem; color: var(--dsh-muted);">Jam <b>${bestHour}:00 - ${parseInt(bestHour)+1}:00</b> adalah lumbung emas Anda, menyumbang <b>${gmvPct}%</b> dari total pendapatan! 💡 <b>Saran:</b> Habiskan mayoritas budget di jam ini!</div>`;
            timeEl.style.borderLeftColor = '#d97706';
        } else {
            timeEl.innerHTML = `<div style="font-weight: 700; color: var(--dsh-muted); font-size: 0.85rem; margin-bottom: 0.3rem;">⏳ Data Waktu Berpencar</div>
                                <div style="font-size: 0.72rem; color: var(--dsh-muted);">Performa iklan tersebar merata di berbagai jam. Belum ada "Waktu Emas" dominan yang bisa disimpulkan untuk rentang ini.</div>`;
            timeEl.style.borderLeftColor = 'var(--dsh-border)';
        }
    }

    // Helper: Format Full Rupiah untuk Tooltip
    const formatFullIDR = (value) => {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
    };

    // --- DAILY LINE CHART ---
    const ctxDaily = document.getElementById("dailyChart");
    if(ctxDaily) {
        const ctxDaily2D = ctxDaily.getContext('2d');
        
        // Buat Gradient (atas lebih pekat, bawah memudar)
        let gradientSpend = ctxDaily2D.createLinearGradient(0, 0, 0, 300);
        gradientSpend.addColorStop(0, 'rgba(220, 38, 38, 0.25)'); // Red pekat
        gradientSpend.addColorStop(1, 'rgba(220, 38, 38, 0.0)');  // Red pudar

        let gradientGMV = ctxDaily2D.createLinearGradient(0, 0, 0, 300);
        gradientGMV.addColorStop(0, 'rgba(22, 163, 74, 0.25)'); // Green pekat
        gradientGMV.addColorStop(1, 'rgba(22, 163, 74, 0.0)');  // Green pudar

        new Chart(ctxDaily2D, {
            type: 'line',
            data: {
                labels: dailyData.map(d => {
                    // ubah format "2026-07-22" jadi "22 Jul"
                    const date = new Date(d.date);
                    return date.getDate() + ' ' + date.toLocaleString('id-ID', { month: 'short' });
                }),
                datasets: [
                    {
                        label: 'AOV',
                        data: dailyData.map(d => {
                            let gm = parseFloat(d.gmv || 0);
                            let or = parseInt(d.orders || 0);
                            return or > 0 ? parseFloat((gm/or).toFixed(0)) : 0;
                        }),
                        borderColor: '#94a3b8', // slate
                        backgroundColor: '#94a3b8',
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 0, // hide dots
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#94a3b8',
                        yAxisID: 'y2'
                    },
                    {
                        label: 'ROAS',
                        data: dailyData.map(d => {
                            let sp = parseFloat(d.spend || 0);
                            let gm = parseFloat(d.gmv || 0);
                            return sp > 0 ? parseFloat((gm/sp).toFixed(2)) : 0;
                        }),
                        borderColor: '#eab308', // Gold
                        backgroundColor: '#eab308',
                        fill: false,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#eab308',
                        yAxisID: 'y1'
                    },
                    {
                        label: 'GMV (Pendapatan)',
                        data: dailyData.map(d => parseFloat(d.gmv || 0)),
                        borderColor: '#16a34a',
                        backgroundColor: gradientGMV,
                        fill: true,
                        tension: 0.4, 
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#16a34a',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Biaya (Spend)',
                        data: dailyData.map(d => parseFloat(d.spend || 0)),
                        borderColor: '#dc2626',
                        backgroundColor: gradientSpend,
                        fill: true,
                        tension: 0.4, 
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#dc2626',
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: { 
                        position: 'top', 
                        labels: { usePointStyle: true, boxWidth: 6, font: { size: 11, family: 'Inter, sans-serif' } } 
                    },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipText,
                        bodyColor: tooltipText,
                        borderColor: tooltipBorder,
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true,
                        boxPadding: 4,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.dataset.yAxisID === 'y1') {
                                    return label + context.parsed.y + 'x';
                                } else {
                                    return label + formatFullIDR(context.parsed.y);
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: { 
                        grid: { display: false }, 
                        ticks: { font: { size: 10 } } 
                    },
                    y: { 
                        type: 'linear', 
                        position: 'left', 
                        beginAtZero: true, 
                        grid: { color: gridColor, drawBorder: false }, 
                        ticks: { 
                            font: { size: 10 }, 
                            padding: 8,
                            callback: function(value) { return formatShortIDR(value); }
                        }
                    },
                    y1: { 
                        type: 'linear', 
                        position: 'right', 
                        beginAtZero: true, 
                        grid: { drawOnChartArea: false, drawBorder: false }, 
                        ticks: { 
                            font: { size: 10 }, 
                            padding: 8,
                            callback: function(value) { return value + 'x'; }
                        }
                    },
                    y2: {
                        type: 'linear',
                        display: false,
                        position: 'left',
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // --- HOURLY BAR CHART ---
    const ctxHourly = document.getElementById("hourlyChart");
    if(ctxHourly) {
        new Chart(ctxHourly.getContext('2d'), {
            type: 'bar',
            data: {
                labels: hourlyData.map(d => d.performance_hour + ':00'),
                datasets: [
                    {
                        type: 'line',
                        label: 'Klik (Trafik)',
                        data: hourlyData.map(d => parseInt(d.clicks || 0)),
                        borderColor: 'rgba(148, 163, 184, 0)',
                        backgroundColor: 'rgba(148, 163, 184, 0.15)',
                        borderWidth: 0,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 0,
                        yAxisID: 'y2'
                    },
                    {
                        type: 'line',
                        label: 'ROAS',
                        data: hourlyData.map(d => {
                            let sp = parseFloat(d.expense || 0);
                            let gm = parseFloat(d.gmv || 0);
                            return sp > 0 ? parseFloat((gm/sp).toFixed(2)) : 0;
                        }),
                        borderColor: '#eab308',
                        backgroundColor: '#eab308',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        yAxisID: 'y1'
                    },
                    {
                        type: 'bar',
                        label: 'Biaya (Spend)',
                        data: hourlyData.map(d => parseFloat(d.expense || 0)),
                        backgroundColor: 'rgba(220, 38, 38, 0.85)',
                        borderRadius: 4,
                        yAxisID: 'y'
                    },
                    {
                        type: 'bar',
                        label: 'GMV (Pendapatan)',
                        data: hourlyData.map(d => parseFloat(d.gmv || 0)),
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                        borderRadius: 4,
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { 
                    legend: { 
                        display: true, 
                        position: 'top', 
                        labels: { usePointStyle: true, boxWidth: 6, font: { size: 10, family: 'Inter, sans-serif' } } 
                    },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipText,
                        bodyColor: tooltipText,
                        borderColor: tooltipBorder,
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.dataset.yAxisID === 'y1') {
                                    return label + context.parsed.y + 'x';
                                } else if (context.dataset.yAxisID === 'y2') {
                                    return label + context.parsed.y;
                                } else {
                                    return label + formatFullIDR(context.parsed.y);
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { 
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true, 
                        grid: { color: gridColor, drawBorder: false }, 
                        ticks: { 
                            font: { size: 10 }, 
                            padding: 8,
                            callback: function(value) { return formatShortIDR(value); }
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false, drawBorder: false },
                        ticks: { 
                            font: { size: 10 }, 
                            padding: 8,
                            callback: function(value) { return value + 'x'; }
                        }
                    },
                    y2: {
                        type: 'linear',
                        display: false,
                        position: 'left',
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // --- DAILY TRAFFIC CHART ---
    const ctxTraffic = document.getElementById("trafficChart");
    if(ctxTraffic) {
        const ctxTraffic2D = ctxTraffic.getContext('2d');
        
        let gradImp = ctxTraffic2D.createLinearGradient(0, 0, 0, 300);
        gradImp.addColorStop(0, 'rgba(245, 158, 11, 0.25)'); // Amber
        gradImp.addColorStop(1, 'rgba(245, 158, 11, 0.0)');
        
        let gradClick = ctxTraffic2D.createLinearGradient(0, 0, 0, 300);
        gradClick.addColorStop(0, 'rgba(59, 130, 246, 0.25)'); // Blue
        gradClick.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

        new Chart(ctxTraffic2D, {
            type: 'line',
            data: {
                labels: dailyData.map(d => {
                    const date = new Date(d.date);
                    return date.getDate() + ' ' + date.toLocaleString('id-ID', { month: 'short' });
                }),
                datasets: [
                    {
                        label: 'CTR',
                        data: dailyData.map(d => {
                            let imp = parseInt(d.impressions || 0);
                            let clk = parseInt(d.clicks || 0);
                            return imp > 0 ? parseFloat(((clk/imp)*100).toFixed(2)) : 0;
                        }),
                        borderColor: '#8b5cf6', // Violet
                        backgroundColor: '#8b5cf6',
                        fill: false,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        yAxisID: 'y2'
                    },
                    {
                        label: 'Klik',
                        data: dailyData.map(d => parseInt(d.clicks || 0)),
                        borderColor: '#3b82f6',
                        backgroundColor: gradClick,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Impression',
                        data: dailyData.map(d => parseInt(d.impressions || 0)),
                        borderColor: '#f59e0b',
                        backgroundColor: gradImp,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: dailyData.length <= 1 ? 5 : 0,
                        pointHitRadius: 15,
                        pointHoverRadius: 4,
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 6, font: { size: 11, family: 'Inter, sans-serif' } } },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipText,
                        bodyColor: tooltipText,
                        borderColor: tooltipBorder,
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true,
                        boxPadding: 4,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.dataset.yAxisID === 'y2') {
                                    return label + context.parsed.y + '%';
                                } else {
                                    return label + context.parsed.y.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { 
                        type: 'linear', position: 'left', beginAtZero: true, 
                        grid: { color: gridColor, drawBorder: false }, 
                        ticks: { font: { size: 10 }, padding: 8, callback: function(value) { return formatShortIDR(value); } } 
                    },
                    y1: { 
                        type: 'linear', position: 'right', beginAtZero: true, 
                        grid: { drawOnChartArea: false, drawBorder: false }, 
                        ticks: { font: { size: 10 }, padding: 8, callback: function(value) { return formatShortIDR(value); } }
                    },
                    y2: { type: 'linear', display: false, position: 'left', beginAtZero: true }
                }
            }
        });
    }

    // ==========================================
    // 4. HISTORICAL CHART (PERIOD-OVER-PERIOD)
    // ==========================================
    let histChart;
    const ctxHist = document.getElementById('historicalChart');
    const histContainer = ctxHist ? ctxHist.parentElement : null;
    
    try {
        if (ctxHist && rawHistorical && rawHistorical.length > 0) {
            // Check if all periods have 0 data
            let hasAnyData = false;
            rawHistorical.forEach(p => { if (p.data && p.data.length > 0) hasAnyData = true; });
            
            if (!hasAnyData) {
                histContainer.innerHTML = '<div style="text-align:center; padding: 40px; color: var(--dsh-muted);">Tidak ada data historis yang tersedia untuk rentang ini. Pastikan Anda telah melakukan Sinkronisasi data di bulan-bulan sebelumnya.</div>';
            } else {
                
                // Calculate maxDays strictly from the selected date range
                let maxDays = 0;
                const fromElHist = document.getElementById('fromHidden');
                const toElHist = document.getElementById('toHidden');
                const dStartHist = fromElHist && fromElHist.value ? new Date(fromElHist.value) : new Date();
                const dEndHist = toElHist && toElHist.value ? new Date(toElHist.value) : new Date();
                if (dStartHist && dEndHist) {
                    maxDays = Math.round((dEndHist - dStartHist) / (1000 * 60 * 60 * 24)) + 1;
                }
                if (maxDays < 1) maxDays = 1;
                
                // Generate X-Axis: "Hari 1", "Hari 2", etc.
                let histLabels = [];
                for (let i = 1; i <= maxDays; i++) {
                    histLabels.push(`Hari ${i}`);
                }

                // Colors for periods
                const lineColors = [
                    '#ef4444', // Period 0 (Current) - Red
                    '#94a3b8', // Period 1 (Last) - Slate
                    'rgba(148, 163, 184, 0.4)', // Period 2 - Light Slate
                    'rgba(148, 163, 184, 0.2)'  // Period 3 - Lighter
                ];

                const dashStyles = [
                    [], // Solid
                    [5, 5], // Dashed
                    [2, 2], // Dotted
                    [2, 4]
                ];

                const getMetricValue = (d, metric) => {
                    let sp = parseFloat(d.spend || d.expense || 0);
                    let gm = parseFloat(d.gmv || 0);
                    let clicks = parseFloat(d.clicks || 0);
                    let orders = parseFloat(d.orders || d.broad_order || d.direct_order || 0);

                    if (metric === 'roas') {
                        return sp > 0 ? (gm / sp) : 0;
                    } else if (metric === 'gmv') {
                        return gm;
                    } else if (metric === 'spend') {
                        return sp;
                    } else if (metric === 'impressions') {
                        return parseFloat(d.impressions || 0);
                    } else if (metric === 'clicks') {
                        return clicks;
                    } else if (metric === 'ctr') {
                        let im = parseFloat(d.impressions || 0);
                        return im > 0 ? (clicks / im) * 100 : 0;
                    } else if (metric === 'cvr') {
                        return clicks > 0 ? (orders / clicks) * 100 : 0;
                    }
                    return 0;
                };

                const renderHistChart = (metric) => {
                    let datasets = rawHistorical.map((period, idx) => {
                        let dataPoints = new Array(maxDays).fill(null);
                        
                        // Align data to specific day offset
                        if (period.start) {
                            const pStart = new Date(period.start);
                            period.data.forEach(d => {
                                if (d.date) {
                                    const pDate = new Date(d.date);
                                    const dayOffset = Math.round((pDate - pStart) / (1000 * 60 * 60 * 24));
                                    if (dayOffset >= 0 && dayOffset < maxDays) {
                                        dataPoints[dayOffset] = getMetricValue(d, metric);
                                    }
                                }
                            });
                        }
                        
                        let label = idx === 0 ? 'Rentang Saat Ini' : `${idx} Rentang Lalu`;
                        if (idx === 1) label = 'Rentang Sebelumnya';

                        return {
                            label: label,
                            data: dataPoints,
                            borderColor: lineColors[idx] || lineColors[0],
                            borderWidth: idx === 0 ? 3 : 2,
                            borderDash: dashStyles[idx] || [],
                            tension: 0.3,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            fill: false,
                            spanGaps: true
                        };
                    });

                    if (histChart) {
                        histChart.data.datasets = datasets;
                        histChart.update();
                    } else {
                        histChart = new Chart(ctxHist.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: histLabels,
                                datasets: datasets
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: { mode: 'index', intersect: false },
                                plugins: {
                                    legend: { display: true, position: 'top', labels: { color: textColor } },
                                    tooltip: {
                                        backgroundColor: tooltipBg,
                                        titleColor: tooltipText,
                                        bodyColor: tooltipText,
                                        borderColor: tooltipBorder,
                                        borderWidth: 1,
                                        padding: 10,
                                        callbacks: {
                                            label: function(ctx) {
                                                let val = ctx.raw;
                                                let metric = document.getElementById('histMetricSelect').value;
                                                if (metric === 'roas') {
                                                    return ctx.dataset.label + ': ' + val.toFixed(2) + 'x';
                                                } else if (metric === 'cvr' || metric === 'ctr') {
                                                    return ctx.dataset.label + ': ' + val.toFixed(2) + '%';
                                                } else if (metric === 'impressions' || metric === 'clicks') {
                                                    return ctx.dataset.label + ': ' + val.toLocaleString('id-ID');
                                                } else {
                                                    let str = val;
                                                    if(val >= 1000000) str = (val/1000000).toFixed(1) + ' Jt';
                                                    else if(val >= 1000) str = (val/1000).toFixed(1) + ' Rb';
                                                    return ctx.dataset.label + ': Rp ' + str;
                                                }
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
                                    y: { 
                                        beginAtZero: true, 
                                        grid: { color: gridColor }, 
                                        ticks: {
                                            callback: function(value) {
                                                let metric = document.getElementById('histMetricSelect').value;
                                                if (metric === 'roas') return value + 'x';
                                                if (metric === 'cvr' || metric === 'ctr') return value + '%';
                                                if (metric === 'impressions' || metric === 'clicks') {
                                                    if (value >= 1000000) return (value/1000000).toFixed(1) + 'M';
                                                    if (value >= 1000) return (value/1000).toFixed(1) + 'K';
                                                    return value;
                                                }
                                                return formatShortIDR(value);
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // Update histSummary
                    const summaryEl = document.getElementById('histSummary');
                    if (summaryEl && datasets.length > 0) {
                        let html = '';
                        // Rentang saat ini is dataset 0, previous is dataset 1
                        const currData = datasets[0].data.filter(v => v !== null);
                        const currSum = currData.reduce((a,b)=>a+b, 0);
                        let currAvg = currData.length > 0 ? (currSum / currData.length) : 0;
                        
                        let prevSum = 0;
                        let prevAvg = 0;
                        if (datasets.length > 1) {
                            const prevData = datasets[1].data.filter(v => v !== null);
                            prevSum = prevData.reduce((a,b)=>a+b, 0);
                            prevAvg = prevData.length > 0 ? (prevSum / prevData.length) : 0;
                        }

                        let formatVal = (v) => {
                            if (metric === 'roas') return v.toFixed(2) + 'x';
                            if (metric === 'cvr' || metric === 'ctr') return v.toFixed(2) + '%';
                            if (metric === 'impressions' || metric === 'clicks') return Math.round(v).toLocaleString('id-ID');
                            return 'Rp ' + formatShortIDR(v);
                        };

                        let isAvg = (metric === 'roas' || metric === 'cvr' || metric === 'ctr');
                        html += `<div style="margin-bottom:2px;">Sekarang: <span style="color:var(--dsh-accent)">${isAvg ? 'Rata-rata' : 'Total'} ${formatVal(isAvg ? currAvg : currSum)}</span></div>`;
                        if (datasets.length > 1) {
                            let diff = 0;
                            let compareVal1 = isAvg ? currAvg : currSum;
                            let compareVal2 = isAvg ? prevAvg : prevSum;
                            
                            if (compareVal2 > 0) diff = ((compareVal1 - compareVal2) / compareVal2) * 100;
                            else if (compareVal1 > 0) diff = 100;

                            let color = diff >= 0 ? '#10b981' : '#ef4444';
                            if (metric === 'spend' || metric === 'cpc') {
                                color = diff > 0 ? '#ef4444' : '#10b981'; // Spend going up is bad (red)
                            }
                            let sign = diff > 0 ? '+' : '';

                            html += `<div style="font-size:0.75rem; color:var(--dsh-muted)">Sebelumnya: ${formatVal(isAvg ? prevAvg : prevSum)} 
                                     <span style="color:${color}; font-weight:bold; margin-left:5px;">(${sign}${diff.toFixed(1)}%)</span></div>`;
                        }
                        
                        summaryEl.innerHTML = html;
                    }
                };

                // Initial render
                renderHistChart('roas');

                // Handle metric toggle
                const metricSelect = document.getElementById('histMetricSelect');
                const metricChips = document.querySelectorAll('#histMetricChips .dash-tab-sm');
                metricChips.forEach(chip => {
                    chip.addEventListener('click', function() {
                        metricChips.forEach(c => c.classList.remove('active'));
                        this.classList.add('active');
                        metricSelect.value = this.dataset.val;
                        renderHistChart(this.dataset.val);
                    });
                });
                
                // ==========================================
                // CHART PRODUK (6 Bar Charts)
                // ==========================================
                const rawItems = @json($itemPerformance->toArray()).map(c => {
                    c.ctr = parseFloat(c.impressions) > 0 ? (parseFloat(c.clicks) / parseFloat(c.impressions)) * 100 : 0;
                    c.cvr = parseFloat(c.clicks) > 0 ? (parseFloat(c.orders) / parseFloat(c.clicks)) * 100 : 0;
                    return c;
                });

                const renderSingleChart = (ctxId, metric, labelFormat, colorTheme) => {
                    const ctx = document.getElementById(ctxId);
                    if (!ctx || !rawItems || rawItems.length === 0) return;
                    
                    let sorted = [...rawItems].sort((a,b) => parseFloat(b[metric] || 0) - parseFloat(a[metric] || 0)).slice(0, 5);
                    
                    // The original barLabels array is no longer strictly needed for drawing since we hide default ticks,
                    // but we pass empty strings so Chart.js knows how many bars to draw.
                    const barLabels = sorted.map(() => '');
                    
                    const customLabelPlugin = {
                        id: 'customLabels',
                        afterDatasetsDraw: (chart) => {
                            const { ctx, data } = chart;
                            ctx.save();
                            chart.getDatasetMeta(0).data.forEach((bar, index) => {
                                // 1. Draw Custom Y-Axis Labels (Rich text)
                                const sku = sorted[index].item_sku || '-';
                                const id = sorted[index].channel_item_id || 'No ID';
                                
                                const textColor = document.body.getAttribute('data-theme') === 'dark' ? '#f8fafc' : '#334155';
                                const mutedColor = document.body.getAttribute('data-theme') === 'dark' ? '#64748b' : '#94a3b8';

                                ctx.textAlign = 'right';
                                
                                // SKU (Focal Point)
                                ctx.font = 'bold 11px "Inter", sans-serif';
                                ctx.fillStyle = textColor;
                                ctx.textBaseline = 'bottom';
                                
                                // Truncate SKU if too long for the left padding (approx 120px)
                                let displaySku = sku;
                                if (ctx.measureText(displaySku).width > 110) {
                                    displaySku = displaySku.substring(0, 15) + '...';
                                }
                                ctx.fillText(displaySku, bar.base - 10, bar.y - 1);
                                
                                // Product ID (Muted & Small)
                                ctx.font = 'normal 9px "Inter", sans-serif';
                                ctx.fillStyle = mutedColor;
                                ctx.textBaseline = 'top';
                                ctx.fillText(id, bar.base - 10, bar.y + 1);

                                // 2. Draw the metric value on/outside the bar
                                const value = data.datasets[0].data[index];
                                let formattedValue = value.toLocaleString('id-ID');
                                if (labelFormat === 'currency') formattedValue = 'Rp ' + formatShortIDR(value);
                                if (labelFormat === 'percent') formattedValue = value.toLocaleString('id-ID', {minimumFractionDigits: 1, maximumFractionDigits: 1}) + '%';
                                
                                ctx.font = 'bold 10px "Inter", sans-serif';
                                ctx.textBaseline = 'middle';
                                
                                const textWidth = ctx.measureText(formattedValue).width;
                                const barWidth = bar.width;
                                
                                if (barWidth > textWidth + 15) {
                                    ctx.fillStyle = '#ffffff';
                                    ctx.textAlign = 'right';
                                    ctx.fillText(formattedValue, bar.x - 6, bar.y + 1);
                                } else {
                                    ctx.fillStyle = document.body.getAttribute('data-theme') === 'dark' ? '#94a3b8' : '#64748b';
                                    ctx.textAlign = 'left';
                                    ctx.fillText(formattedValue, bar.x + 6, bar.y + 1);
                                }
                            });
                            ctx.restore();
                        }
                    };

                    const barData = sorted.map(c => parseFloat(c[metric] || 0));

                    new Chart(ctx.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: barLabels,
                            datasets: [{
                                data: barData,
                                backgroundColor: colorTheme,
                                borderRadius: 4,
                                barThickness: 16
                            }]
                        },
                        plugins: [customLabelPlugin],
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    left: 125, // Reserve space for custom rich Y-axis labels
                                    right: 50 // Give space for values drawn outside the bar
                                }
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: tooltipBg,
                                    titleColor: tooltipText,
                                    bodyColor: tooltipText,
                                    borderColor: tooltipBorder,
                                    borderWidth: 1,
                                    padding: 10,
                                    callbacks: {
                                        title: function(context) {
                                            let idx = context[0].dataIndex;
                                            let fullSku = sorted[idx].item_sku || sorted[idx].channel_item_id;
                                            let fullName = sorted[idx].item_name || 'Unknown Product';
                                            return fullSku + ' | ' + fullName;
                                        },
                                        label: function(c) {
                                            if (labelFormat === 'currency') return 'Nilai: Rp ' + formatShortIDR(c.raw);
                                            if (labelFormat === 'percent') return 'Nilai: ' + c.raw.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '%';
                                            return 'Nilai: ' + c.raw.toLocaleString('id-ID');
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: { 
                                    display: false, 
                                    grid: { display: false } 
                                },
                                y: { 
                                    grid: { display: false }, 
                                    ticks: { 
                                        display: false // Hide default ticks as we draw them manually
                                    },
                                    border: { display: false }
                                }
                            }
                        }
                    });
                };
                
                // Colors matched to the BI icons:
                renderSingleChart('chartSpend', 'spend', 'currency', 'rgba(239, 68, 68, 0.85)'); // Danger (Red)
                renderSingleChart('chartGmv', 'gmv', 'currency', 'rgba(16, 185, 129, 0.85)'); // Success (Green)
                renderSingleChart('chartClicks', 'clicks', 'number', 'rgba(59, 130, 246, 0.85)'); // Primary (Blue)
                renderSingleChart('chartImpressions', 'impressions', 'number', 'rgba(14, 165, 233, 0.85)'); // Info (Sky)
                renderSingleChart('chartCtr', 'ctr', 'percent', 'rgba(245, 158, 11, 0.85)'); // Warning (Amber)
                renderSingleChart('chartCvr', 'cvr', 'percent', 'rgba(168, 85, 247, 0.85)'); // Purple
                
                // ==========================================
                // AI INSIGHTS HISTORICAL (PERIOD-OVER-PERIOD)
                // ==========================================
                const insightHistEl = document.getElementById('insightHistorical');
                if (insightHistEl && rawHistorical.length >= 2) {
                    // Extract current period (idx 0) and previous period (idx 1)
                    let currentData = rawHistorical[0].data || [];
                    let prevData = rawHistorical[1].data || [];
                    
                    let currSpend = currentData.reduce((sum, d) => sum + parseFloat(d.spend || d.expense || 0), 0);
                    let currGmv = currentData.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);
                    let currRoas = currSpend > 0 ? (currGmv / currSpend) : 0;
                    
                    let prevSpend = prevData.reduce((sum, d) => sum + parseFloat(d.spend || d.expense || 0), 0);
                    let prevGmv = prevData.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);
                    let prevRoas = prevSpend > 0 ? (prevGmv / prevSpend) : 0;
                    
                    // Prevent divide by zero if previous data is entirely 0
                    if (prevSpend > 0 && prevGmv > 0) {
                        let gmvGrowth = ((currGmv - prevGmv) / prevGmv) * 100;
                        let spendGrowth = ((currSpend - prevSpend) / prevSpend) * 100;
                        let roasGrowth = ((currRoas - prevRoas) / prevRoas) * 100;
                        
                        let histHtml = '';
                        
                        // Condition 0: Data Too Small to Judge
                        if (currGmv < 50000 && prevGmv < 50000) {
                            histHtml = `<div style="font-weight: 700; color: var(--dsh-muted); font-size: 0.85rem; margin-bottom: 0.3rem;">🔍 Data Belum Signifikan</div>
                                        <div style="font-size: 0.72rem; color: var(--dsh-muted);">Penjualan di kedua periode masih sangat kecil (di bawah 50rb) sehingga persentase pertumbuhan belum relevan untuk dianalisis.</div>`;
                            insightHistEl.style.borderLeftColor = 'var(--dsh-border)';
                        }
                        // Condition 1: Anomaly - Huge spending spike but no GMV
                        else if (spendGrowth > 100 && gmvGrowth < 10) {
                            histHtml = `<div style="font-weight: 700; color: #dc2626; font-size: 0.85rem; margin-bottom: 0.3rem;">🚨 ANOMALI: Kebocoran Fatal!</div>
                                        <div style="font-size: 0.72rem; color: var(--dsh-muted);">Ada yang salah! Anda membakar uang <b>${spendGrowth.toFixed(1)}%</b> lebih gila dari bulan lalu, tapi omzet hanya bergerak <b>${gmvGrowth.toFixed(1)}%</b>. 💡 <b>Saran:</b> Algoritma GMV Max mungkin memaksakan budget pada produk yang salah. Coba naikkan Target ROAS atau keluarkan produk yang tidak relevan.</div>`;
                            insightHistEl.style.borderLeftColor = '#dc2626';
                        }
                        // Condition 2: Law of Diminishing Returns
                        else if (spendGrowth > 30 && gmvGrowth > 0 && gmvGrowth < (spendGrowth / 2)) {
                            histHtml = `<div style="font-weight: 700; color: #eab308; font-size: 0.85rem; margin-bottom: 0.3rem;">⚠️ Hukum Hasil yang Berkurang (Diminishing Returns)</div>
                                        <div style="font-size: 0.72rem; color: var(--dsh-muted);">Biaya iklan dinaikkan <b>${spendGrowth.toFixed(1)}%</b> tapi omzet hanya naik <b>${gmvGrowth.toFixed(1)}%</b>. Anda mulai membeli klik-klik sampah/mahal. 💡 <b>Saran:</b> Skalasi sudah mentok. Jangan naikkan budget lagi, fokus pada optimasi tingkat konversi (CVR).</div>`;
                            insightHistEl.style.borderLeftColor = '#eab308';
                        }
                        // Condition 3: Healthy Scaling (Algorithmic Favor)
                        else if (spendGrowth > 10 && gmvGrowth >= spendGrowth) {
                            histHtml = `<div style="font-weight: 700; color: #16a34a; font-size: 0.85rem; margin-bottom: 0.3rem;">🚀 Momentum Skalasi Maksimal</div>
                                        <div style="font-size: 0.72rem; color: var(--dsh-muted);">Sempurna! Anda menambah modal <b>${spendGrowth.toFixed(1)}%</b> dan dibalas dengan kenaikan omzet <b>${gmvGrowth.toFixed(1)}%</b>. Algoritma Shopee sedang memihak produk Anda. 💡 <b>Saran:</b> Injak gas! Naikkan budget pelan-pelan selagi momentum ini ada.</div>`;
                            insightHistEl.style.borderLeftColor = '#16a34a';
                        }
                        // Condition 4: Slowdown (GMV down, Spend down)
                        else if (gmvGrowth < -10 && spendGrowth < -10) {
                            histHtml = `<div style="font-weight: 700; color: #f59e0b; font-size: 0.85rem; margin-bottom: 0.3rem;">📉 Tren Gugur (Dying Trend)</div>
                                        <div style="font-size: 0.72rem; color: var(--dsh-muted);">Pasar mereda. Pendapatan anjlok <b>${Math.abs(gmvGrowth).toFixed(1)}%</b> dan sistem mengerem biaya iklan sebesar <b>${Math.abs(spendGrowth).toFixed(1)}%</b>. 💡 <b>Saran:</b> Cek siklus musiman (habis gajian/tanggal kembar). Jika bukan karena musim, berarti kompetitor merebut pasar Anda!</div>`;
                            insightHistEl.style.borderLeftColor = '#f59e0b';
                        }
                        // Condition 5: Stable with subtle warnings
                        else {
                            let gmvDir = gmvGrowth >= 0 ? "naik" : "turun";
                            histHtml = `<div style="font-weight: 700; color: #3b82f6; font-size: 0.85rem; margin-bottom: 0.3rem;">⚖️ Stabilitas (Fase Plateau)</div>
                                        <div style="font-size: 0.72rem; color: var(--dsh-muted);">Bisnis berjalan stabil bagai mesin. Omzet ${gmvDir} perlahan <b>${Math.abs(gmvGrowth).toFixed(1)}%</b> dengan struktur biaya yang terjaga. 💡 <b>Saran:</b> Saatnya bereksperimen dengan memasukkan 1-2 produk jagoan baru ke dalam kampanye GMV Max Anda.</div>`;
                            insightHistEl.style.borderLeftColor = '#3b82f6';
                        }
                        insightHistEl.innerHTML = histHtml;
                    } else {
                        insightHistEl.innerHTML = `<div style="font-weight: 700; color: var(--dsh-muted); font-size: 0.85rem; margin-bottom: 0.3rem;">⏳ Menunggu Data Historis</div>
                                                   <div style="font-size: 0.72rem; color: var(--dsh-muted);">Data pada rentang waktu sebelumnya (Period-1) kosong atau belum disinkronisasi, sehingga sistem tidak bisa membandingkan pertumbuhan.</div>`;
                    }
                } else if (insightHistEl) {
                    insightHistEl.innerHTML = `<div style="font-weight: 700; color: var(--dsh-muted); font-size: 0.85rem; margin-bottom: 0.3rem;">⏳ Kurang Data Pembanding</div>
                                               <div style="font-size: 0.72rem; color: var(--dsh-muted);">Data komparasi tidak cukup panjang untuk memunculkan wawasan AI historis.</div>`;
                }

                // ==========================================
                // AI INSIGHTS DAILY TREND (ANALISIS HARIAN)
                // ==========================================
                const insightDailyEl = document.getElementById('insightDailyTrend');
                if (insightDailyEl && dailyData.length > 0) {
                    // Filter out zero spend days for accurate analysis
                    let activeDays = dailyData.filter(d => parseFloat(d.spend || 0) > 0);
                    
                    if (activeDays.length > 2) {
                        let bestDay = null;
                        let worstDay = null;
                        let maxRoas = -1;
                        let minRoas = 999999;
                        
                        let totalTrendRoas = 0;
                        let roasArray = [];

                        activeDays.forEach(d => {
                            let r = parseFloat(d.spend) > 0 ? (parseFloat(d.gmv || 0) / parseFloat(d.spend)) : 0;
                            totalTrendRoas += r;
                            roasArray.push(r);
                            
                            if (r > maxRoas) { maxRoas = r; bestDay = d.date; }
                            // Consider worst day only if they spent a decent amount (e.g. > 10000)
                            if (r < minRoas && parseFloat(d.spend) > 10000) { minRoas = r; worstDay = d.date; }
                        });
                        
                        let avgTrendRoas = totalTrendRoas / activeDays.length;
                        
                        // Calculate Volatility (Standard Deviation)
                        let variance = roasArray.reduce((acc, val) => acc + Math.pow(val - avgTrendRoas, 2), 0) / roasArray.length;
                        let stdDev = Math.sqrt(variance);
                        let isVolatile = stdDev > (avgTrendRoas * 0.5); // If std dev is > 50% of mean
                        
                        // Trend Direction (Compare first half to second half)
                        let halfPoint = Math.floor(activeDays.length / 2);
                        let firstHalf = activeDays.slice(0, halfPoint);
                        let secondHalf = activeDays.slice(halfPoint);
                        
                        let firstHalfGmv = firstHalf.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);
                        let secondHalfGmv = secondHalf.reduce((sum, d) => sum + parseFloat(d.gmv || 0), 0);

                        // ==========================================
                        // 1. COMPUTE TRAFFIC & FUNNEL METRICS FIRST
                        // ==========================================
                        let maxImpressionsDay = activeDays.reduce((max, d) => (parseInt(d.impressions) > parseInt(max.impressions) ? d : max), activeDays[0]);
                        let maxClicksDay = activeDays.reduce((max, d) => (parseInt(d.clicks) > parseInt(max.clicks) ? d : max), activeDays[0]);
                        
                        let impCtr = parseInt(maxImpressionsDay.impressions) > 0 ? (parseInt(maxImpressionsDay.clicks) / parseInt(maxImpressionsDay.impressions) * 100) : 0;
                        let clkCvr = parseInt(maxClicksDay.clicks) > 0 ? (parseInt(maxClicksDay.orders) / parseInt(maxClicksDay.clicks) * 100) : 0;
                        
                        let isImpressionLeak = (parseInt(maxImpressionsDay.impressions) > 1000 && impCtr < 1.0);
                        let isBounceAnomaly = (parseInt(maxClicksDay.clicks) > 50 && clkCvr < 0.5);

                        // ==========================================
                        // 2. BUILD FINANCIAL NARRATIVE (WITH CROSS-REFERENCE)
                        // ==========================================
                        let trendHtml = '';
                        let trendColor = '';
                        
                        if (isVolatile) {
                            let cause = isBounceAnomaly ? "Akar masalah ada di <b>Trafik (Anomali Bounce Rate)</b>, banyak klik bodong yang merusak rasio konversi." : "Algoritma GMV Max sedang kebingungan mencari audiens yang tepat.";
                            trendHtml = `<div style="font-weight: 700; color: #dc2626; font-size: 0.8rem; margin-bottom: 0.2rem;">🎢 Deteksi Volatilitas ROAS</div>
                                         <div style="font-size: 0.7rem; color: var(--dsh-muted);">Performa sangat tidak stabil (ROAS terbaik <b>${maxRoas.toFixed(1)}x</b> vs terburuk <b>${minRoas.toFixed(1)}x</b>). 🔗 <b>Korelasi AI:</b> ${cause} 💡 <b>Saran:</b> Hentikan perubahan budget/target ROAS agar mesin stabil.</div>`;
                            trendColor = '#dc2626';
                        } else if (secondHalfGmv > firstHalfGmv * 1.2) {
                            let cause = (impCtr >= 1.0 && clkCvr >= 0.5) ? "Kenaikan ini didukung penuh oleh <b>Distribusi Funnel Trafik yang sangat sehat</b> (cek grafik di bawah)." : "Mesin mulai panas meskipun metrik klik belum sempurna.";
                            trendHtml = `<div style="font-weight: 700; color: #16a34a; font-size: 0.8rem; margin-bottom: 0.2rem;">📈 Momentum Algoritma (Uptrend)</div>
                                         <div style="font-size: 0.7rem; color: var(--dsh-muted);">GMV Paruh-2 melampaui Paruh-1. Algoritma optimal pada <b>${formatIndoDate(bestDay)}</b>. 🔗 <b>Korelasi AI:</b> ${cause} 💡 <b>Saran:</b> Jangan ubah Target ROAS saat ini.</div>`;
                            trendColor = '#16a34a';
                        } else if (secondHalfGmv < firstHalfGmv * 0.8) {
                            let cause = isImpressionLeak ? "Penyebab utamanya terlihat di <b>Trafik (Kebocoran Impresi Ekstrem)</b>; iklan tayang tapi orang malas klik." : "Trafik melemah, kemungkinan karena kalah bersaing harga dengan kompetitor.";
                            trendHtml = `<div style="font-weight: 700; color: #f59e0b; font-size: 0.8rem; margin-bottom: 0.2rem;">📉 Peringatan Downtrend</div>
                                         <div style="font-size: 0.7rem; color: var(--dsh-muted);">GMV merosot perlahan (puncak anjlok di <b>${formatIndoDate(worstDay)}</b>). 🔗 <b>Korelasi AI:</b> ${cause} 💡 <b>Saran:</b> Segera evaluasi harga atau perbarui foto produk di GMV Max.</div>`;
                            trendColor = '#f59e0b';
                        } else {
                            trendHtml = `<div style="font-weight: 700; color: #3b82f6; font-size: 0.8rem; margin-bottom: 0.2rem;">🛥️ Konvergensi Stabil</div>
                                         <div style="font-size: 0.7rem; color: var(--dsh-muted);">ROAS harian sangat stabil di <b>${avgTrendRoas.toFixed(1)}x</b>. 🔗 <b>Korelasi AI:</b> Trafik dan konversi berjalan selaras tanpa lonjakan aneh. 💡 <b>Saran:</b> Sistem GMV Max telah konvergen. Anda bisa naikkan Target ROAS 5% per hari.</div>`;
                            trendColor = '#3b82f6';
                        }
                        
                        insightDailyEl.innerHTML = trendHtml;
                        insightDailyEl.style.borderLeftColor = trendColor;
                        
                        // ==========================================
                        // 3. BUILD TRAFFIC NARRATIVE (WITH CROSS-REFERENCE)
                        // ==========================================
                        const insightTrafficDailyEl = document.getElementById('insightDailyTraffic');
                        if (insightTrafficDailyEl) {
                            let tfHtml = '';
                            let tfColor = '';
                            
                            if (isImpressionLeak) {
                                let impact = (secondHalfGmv < firstHalfGmv * 0.8) ? "Sistem akhirnya menghukum Anda dengan <b>Downtrend Finansial</b> di grafik atas." : "Hati-hati, lambat laun ini akan menyeret ROAS Anda ke bawah.";
                                tfHtml = `<div style="font-weight: 700; color: #dc2626; font-size: 0.8rem; margin-bottom: 0.2rem;">🚨 Kebocoran Impresi Ekstrem</div>
                                          <div style="font-size: 0.7rem; color: var(--dsh-muted);">Pada <b>${formatIndoDate(maxImpressionsDay.date)}</b>, ada <b>${parseInt(maxImpressionsDay.impressions).toLocaleString('id-ID')}</b> impresi tapi CTR hanya <b>${impCtr.toFixed(2)}%</b>. 🔗 <b>Dampak Finansial:</b> ${impact} 💡 <b>Saran:</b> Iklan tayang tapi diabaikan. Cek harga/thumbnail segera!</div>`;
                                tfColor = '#dc2626';
                            } else if (isBounceAnomaly) {
                                let impact = isVolatile ? "Inilah alasan mengapa grafik <b>Finansial Anda sangat fluktuatif (Volatile)</b>." : "Budget Anda habis dimakan klik tanpa omzet.";
                                tfHtml = `<div style="font-weight: 700; color: #eab308; font-size: 0.8rem; margin-bottom: 0.2rem;">⚠️ Anomali *Bounce Rate* (Klik Bodong)</div>
                                          <div style="font-size: 0.7rem; color: var(--dsh-muted);">Pada <b>${formatIndoDate(maxClicksDay.date)}</b>, terjadi <b>${parseInt(maxClicksDay.clicks).toLocaleString('id-ID')}</b> klik, tapi nyaris 0 pesanan (CVR <b>${clkCvr.toFixed(2)}%</b>). 🔗 <b>Dampak Finansial:</b> ${impact} 💡 <b>Saran:</b> Cek log kompetitor (apakah Flash Sale?) atau ketersediaan stok Anda.</div>`;
                                tfColor = '#eab308';
                            } else {
                                let impact = (secondHalfGmv > firstHalfGmv * 1.2) ? "Kondisi sehat ini mendorong <b>Momentum Algoritma (Uptrend)</b> pada grafik finansial Anda." : "Finansial Anda terlindungi dari kebocoran yang tidak perlu.";
                                tfHtml = `<div style="font-weight: 700; color: #16a34a; font-size: 0.8rem; margin-bottom: 0.2rem;">✅ Distribusi *Funnel* Sehat</div>
                                          <div style="font-size: 0.7rem; color: var(--dsh-muted);">Tidak ada kebocoran parah pada puncak trafik (<b>${formatIndoDate(maxImpressionsDay.date)}</b>). Konversi ke pesanan mengalir normal. 🔗 <b>Dampak Finansial:</b> ${impact}</div>`;
                                tfColor = '#16a34a';
                            }
                            insightTrafficDailyEl.innerHTML = tfHtml;
                            insightTrafficDailyEl.style.borderLeftColor = tfColor;
                        }
                        
                    } else {
                        insightDailyEl.innerHTML = `<div style="font-weight: 700; color: var(--dsh-muted); font-size: 0.85rem; margin-bottom: 0.3rem;">⏳ Butuh Lebih Banyak Hari</div>
                                                    <div style="font-size: 0.72rem; color: var(--dsh-muted);">AI membutuhkan minimal 3 hari data aktif untuk membaca tren dan volatilitas.</div>`;
                        insightDailyEl.style.borderLeftColor = 'var(--dsh-border)';
                        
                        let itEl = document.getElementById('insightDailyTraffic');
                        if (itEl) {
                            itEl.innerHTML = insightDailyEl.innerHTML;
                            itEl.style.borderLeftColor = 'var(--dsh-border)';
                        }
                    }
                }

            }
        } else if (ctxHist) {
             histContainer.innerHTML = '<div style="text-align:center; padding: 40px; color: var(--dsh-muted);">Data rawHistorical tidak ditemukan atau kosong.</div>';
        }
    } catch (err) {
        if (histContainer) {
            histContainer.innerHTML = '<div style="color:#dc2626; padding: 20px; font-family: monospace;"><b>JS Error:</b> ' + err.message + '<br>' + err.stack + '</div>';
        }
    }
});
</script>
@endif
@endpush
