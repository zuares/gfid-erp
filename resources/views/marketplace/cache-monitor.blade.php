@extends('layouts.app')
@section('title', 'Marketplace • Pemantauan Cache Resi')

@include('marketplace._shared')

@push('head')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

    :root{
        --shp-accent: #2563eb;
        --shp-accent-light: #eff6ff;
        --shp-border: rgba(148,163,184, 0.25);
        --shp-muted: #64748b;
        --shp-text: #1e293b;
        --card-bg: rgba(255, 255, 255, 0.85);
    }
    
    body[data-theme="dark"] {
        --shp-accent: #3b82f6;
        --shp-accent-light: rgba(59, 130, 246, 0.1);
        --shp-border: rgba(51, 65, 85, 0.8);
        --shp-muted: #94a3b8;
        --shp-text: #f1f5f9;
        --card-bg: rgba(15, 23, 42, 0.75);
    }

    .page-wrap { 
        max-width: 900px; 
        margin-inline: auto; 
        padding: 2rem 1rem 4rem; 
        font-family: 'Outfit', system-ui, -apple-system, sans-serif;
    }

    .monitor-card {
        background: var(--card-bg);
        border: 1px solid var(--shp-border);
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        backdrop-filter: blur(12px);
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-box {
        background: var(--shp-accent-light);
        border: 1px solid var(--shp-border);
        border-radius: 10px;
        padding: 1.5rem;
        text-align: center;
    }

    .stat-val {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--shp-accent);
        line-height: 1;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 0.85rem;
        color: var(--shp-muted);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .action-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--shp-border);
    }

    .btn-clean {
        background: #ef4444;
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }
    
    .btn-clean:hover {
        background: #dc2626;
        transform: translateY(-1px);
    }

    .btn-clean:disabled {
        background: #94a3b8;
        cursor: not-allowed;
        transform: none;
    }

    .sys-info {
        font-size: 0.8rem;
        color: var(--shp-muted);
    }
</style>
@endpush

@section('content')
<div class="page-wrap">
    
    <div style="margin-bottom:2rem">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:0.5rem">
            <a href="/marketplace/settings" style="color:var(--shp-muted);text-decoration:none">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <h1 style="font-size:1.75rem;font-weight:700;margin:0;color:var(--shp-text)">Pemantauan Cache Resi</h1>
        </div>
        <p style="color:var(--shp-muted);margin:0;font-size:.9rem;padding-left:30px">
            Dashboard untuk memantau kapasitas penyimpanan lokal dari fitur <strong>Cetak Resi Anti-Double</strong>.
        </p>
    </div>

    @if(session('success'))
    <div style="background:#dcfce7; border:1px solid #86efac; color:#166534; padding:1rem; border-radius:8px; margin-bottom:1.5rem;">
        <div style="font-weight:600; margin-bottom:0.25rem">Pembersihan Selesai!</div>
        <div style="font-size:0.85rem">{!! session('success') !!}</div>
    </div>
    @endif

    <div class="monitor-card">
        
        <div class="stat-grid">
            <div class="stat-box">
                <div class="stat-val">{{ number_format($totalFiles) }}</div>
                <div class="stat-label">Total File Resi</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-val">{{ number_format($totalSizeBytes / 1024 / 1024, 2) }}</div>
                <div class="stat-label">Kapasitas (MB)</div>
            </div>
            
            <div class="stat-box" style="background:#fef2f2; border-color:#fecaca">
                <div class="stat-val" style="color:#ef4444">{{ number_format($expiredFiles) }}</div>
                <div class="stat-label">Siap Dihapus (Expired)</div>
            </div>
        </div>

        <div style="font-size:0.85rem; color:var(--shp-text); line-height:1.5; background:var(--shp-accent-light); padding:1rem; border-radius:8px; border:1px solid var(--shp-border);">
            <strong>💡 Informasi Sistem:</strong><br>
            File resi ini dikompresi secara maksimal menggunakan algoritma <strong>GZIP Level 9</strong> (.pdf.gz).<br>
            Sistem secara otomatis akan menghapus file yang usia pesanannya (berstatus COMPLETED) sudah lebih dari 4 hari setiap jam <strong>01:00 dini hari</strong>.
        </div>

        <div class="action-bar">
            <div class="sys-info">
                Lokasi Direktori: <code>storage/app/shipping_labels/</code>
            </div>
            
            <form action="{{ route('marketplace.cache-monitor.run') }}" method="POST" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='Memproses...'">
                <button type="submit" class="btn-clean" {{ $expiredFiles == 0 ? 'disabled' : '' }}>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                    Jalankan Auto-Cleanup Sekarang
                </button>
            </form>
        </div>

    </div>

</div>
@endsection
