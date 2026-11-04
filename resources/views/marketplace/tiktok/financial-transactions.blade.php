@extends('layouts.app')
@php($walletMode = $walletMode ?? false)
@section('title', $walletMode ? 'Marketplace • Mutasi Wallet TikTok' : 'Marketplace • Transaksi Finansial TikTok')

@include('marketplace._shared')

@push('head')
<style>
    .tt-finance-page { max-width: 1280px; margin-inline: auto; padding-bottom: 1.5rem; }
    .tt-finance-hero { display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; flex-wrap:wrap; margin-bottom:.85rem; padding:1.1rem 1.25rem; border-radius:18px; color:#fff; background:linear-gradient(135deg,#111827,#1f2937 55%,#0f766e); box-shadow:0 14px 34px rgba(15,23,42,.18); }
    .tt-finance-hero h1 { margin:0 0 .35rem; font-size:1.28rem; font-weight:850; letter-spacing:-.03em; }
    .tt-finance-hero p { max-width:54rem; margin:0; color:#d1fae5; font-size:.8rem; line-height:1.5; }
    .tt-finance-badge { display:inline-flex; align-items:center; gap:.45rem; padding:.38rem .72rem; border:1px solid rgba(255,255,255,.25); border-radius:999px; color:#ccfbf1; background:rgba(0,0,0,.18); font-size:.72rem; font-weight:800; white-space:nowrap; }
    .tt-finance-badge i { width:8px; height:8px; border-radius:50%; background:#4ade80; box-shadow:0 0 0 4px rgba(74,222,128,.2); }
    .tt-finance-card { border:1px solid var(--border-color, #e5e7eb); border-radius:14px; background:var(--card, #fff); }
    .tt-finance-filters { padding:1rem; margin-bottom:.85rem; }
    .tt-finance-filters-grid { display:grid; grid-template-columns:minmax(180px,1.2fr) minmax(135px,.9fr) minmax(135px,.9fr) minmax(140px,1fr) minmax(160px,1.2fr); gap:.75rem; align-items:end; }
    .tt-finance-field label { display:block; margin-bottom:.3rem; color:#64748b; font-size:.68rem; font-weight:850; letter-spacing:.04em; text-transform:uppercase; }
    .tt-finance-field .form-control, .tt-finance-field .form-select { font-size:.82rem; border-radius:9px; }
    .tt-finance-types { display:flex; flex-wrap:wrap; gap:.45rem .8rem; padding-top:.2rem; }
    .tt-finance-type { display:inline-flex; align-items:center; gap:.35rem; color:#475569; font-size:.76rem; }
    .tt-finance-preset-row { display:flex; align-items:center; gap:.65rem; flex-wrap:wrap; margin-top:.75rem; padding-top:.75rem; border-top:1px dashed #e5e7eb; }
    .tt-finance-quick-presets { display:flex; align-items:center; gap:.3rem; flex-wrap:wrap; }
    .tt-finance-quick-label { margin-right:.2rem; color:#64748b; font-size:.67rem; font-weight:850; letter-spacing:.04em; text-transform:uppercase; }
    .tt-finance-quick-btn { padding:.22rem .55rem; border:1px solid #e5e7eb; border-radius:999px; color:#64748b; background:transparent; cursor:pointer; font-size:.7rem; font-weight:650; line-height:1.2; transition:all .15s ease-in-out; }
    .tt-finance-quick-btn:hover { border-color:#99f6e4; color:#0f766e; background:#f0fdfa; }
    .tt-finance-quick-btn.active { border-color:#0f766e; color:#fff; background:#0f766e; box-shadow:0 2px 6px rgba(15,118,110,.25); }
    .tt-finance-search-bar { display:flex; align-items:center; gap:.65rem; flex-wrap:wrap; margin-top:.75rem; }
    .tt-finance-search-wrap { position:relative; flex:1 1 280px; }
    .tt-finance-search-wrap .search-icon { position:absolute; top:50%; left:.75rem; color:#64748b; font-size:.85rem; pointer-events:none; transform:translateY(-50%); }
    .tt-finance-search-wrap input { padding-right:2.15rem; padding-left:2.15rem; border-radius:8px; font-size:.82rem; }
    .tt-finance-search-wrap:focus-within input { border-color:var(--shp-accent,#5b5ce2); box-shadow:0 0 0 2px color-mix(in srgb, var(--shp-accent,#5b5ce2) 15%, transparent); }
    .tt-finance-search-clear { position:absolute; top:50%; right:.55rem; display:none; padding:0 .25rem; border:0; color:#64748b; background:transparent; cursor:pointer; font-size:1.1rem; line-height:1; transform:translateY(-50%); }
    .tt-finance-search-clear.show { display:block; }
    .tt-finance-actions { display:flex; align-items:center; justify-content:space-between; gap:.6rem; flex-wrap:wrap; margin-top:.75rem; }
    .tt-finance-actions .btn, .tt-finance-actions-group .btn { border-radius:7px; font-size:.78rem; font-weight:650; box-shadow:none; }
    .tt-finance-actions-group .btn { min-height:40px; padding-inline:.78rem; }
    .tt-finance-actions-group .btn i { color:inherit; }
    .tt-finance-actions-group .btn-primary { border-color:var(--shp-accent,#5b5ce2); background:var(--shp-accent,#5b5ce2); }
    .tt-finance-actions-group .btn-primary:hover,
    .tt-finance-actions-group .btn-primary:focus-visible { border-color:var(--shp-accent-2,#4142bb); background:var(--shp-accent-2,#4142bb); }
    .tt-finance-actions-group .btn-outline-primary { color:var(--shp-accent,#5b5ce2); border-color:var(--shp-accent,#5b5ce2); }
    .tt-finance-actions-group .btn-outline-primary:hover,
    .tt-finance-actions-group .btn-outline-primary:focus-visible { color:#fff; border-color:var(--shp-accent,#5b5ce2); background:var(--shp-accent,#5b5ce2); }
    .tt-finance-actions-group { display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; }
    .tt-finance-help { display:flex; align-items:center; gap:.4rem; margin-top:.65rem; color:var(--shp-muted,#667085); font-size:.73rem; }
    .tt-finance-help i { flex:0 0 auto; }
    .tt-finance-alert { display:none; padding:.7rem .85rem; margin-bottom:.9rem; border:1px solid #fecaca; border-radius:10px; color:#991b1b; background:#fef2f2; font-size:.78rem; }
    .tt-finance-alert.show { display:block; }
    .tt-finance-alert.success { border-color:#bbf7d0; color:#166534; background:#f0fdf4; }
    .tt-finance-storage-status { color:#64748b; font-size:.72rem; }
    .tt-finance-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; margin-bottom:.85rem; }
    .tt-finance-kpi { padding:.85rem 1rem; }
    .tt-finance-kpi small { display:block; color:#64748b; font-size:.68rem; font-weight:750; }
    .tt-finance-kpi strong { display:block; margin-top:.25rem; color:#111827; font-size:1.15rem; font-weight:900; line-height:1.25; font-variant-numeric:tabular-nums; }
    .tt-finance-kpi-note { margin-top:.2rem; color:#64748b; font-size:.7rem; }
    .tt-finance-kpi.in strong { color:#047857; }
    .tt-finance-kpi.out strong { color:#b91c1c; }
    .tt-finance-kpi.net strong { color:#2563eb; }
    .tt-finance-breakdown { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; margin-bottom:.85rem; padding:.5rem .75rem; border:1px solid #e5e7eb; border-radius:10px; background:var(--card,#fff); }
    .tt-finance-breakdown-label { color:#64748b; font-size:.68rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase; }
    .tt-finance-breakdown-pills { display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; }
    .tt-finance-pill-button { display:inline-flex; align-items:center; gap:.35rem; padding:.2rem .55rem; border:1px solid transparent; border-radius:999px; cursor:pointer; font-size:.7rem; font-weight:700; transition:all .15s; }
    .tt-finance-pill-button:hover { box-shadow:0 2px 5px rgba(0,0,0,.08); transform:translateY(-1px); }
    .tt-finance-pill-button.active { box-shadow:0 0 0 2px #0f766e; }
    .tt-finance-recon { margin-bottom:.9rem; padding:1rem; }
    .tt-finance-recon-head { display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; flex-wrap:wrap; margin-bottom:.8rem; }
    .tt-finance-recon-title { color:#111827; font-size:.95rem; font-weight:850; }
    .tt-finance-recon-note { color:#64748b; font-size:.72rem; line-height:1.5; }
    .tt-finance-recon-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.65rem; }
    .tt-finance-recon-item { padding:.72rem .8rem; border:1px solid #eef2f7; border-radius:10px; background:#fbfdff; }
    .tt-finance-recon-item small { display:block; color:#64748b; font-size:.67rem; font-weight:750; }
    .tt-finance-recon-item strong { display:block; margin-top:.2rem; color:#111827; font-size:.95rem; font-variant-numeric:tabular-nums; }
    .tt-finance-recon-item.gmv strong { color:#b91c1c; }
    .tt-finance-recon-item.billing strong { color:#9a3412; }
    .tt-finance-recon-status { font-size:.72rem; font-weight:800; }
    .tt-finance-recon-status.ready { color:#047857; }
    .tt-finance-recon-status.warn { color:#a16207; }
    @media (max-width: 900px) { .tt-finance-recon-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width: 600px) { .tt-finance-recon-grid { grid-template-columns:1fr 1fr; } }
    .tt-finance-table-wrap { overflow:auto; border-bottom-left-radius:0; border-bottom-right-radius:0; }
    .tt-finance-table { width:100%; min-width:980px; margin:0; font-size:.78rem; }
    .tt-finance-table th { padding:.72rem .8rem; color:#64748b; background:#f8fafc; font-size:.67rem; letter-spacing:.03em; text-transform:uppercase; white-space:nowrap; }
    .tt-finance-table td { padding:.65rem .85rem; vertical-align:middle; border-top:1px solid #eef2f7; white-space:nowrap; }
    .tt-finance-table code { color:#334155; font-size:.72rem; }
    .tt-finance-pill { display:inline-flex; padding:.25rem .5rem; border-radius:999px; font-size:.68rem; font-weight:800; white-space:nowrap; }
    .tt-finance-pill.success { color:#047857; background:#ecfdf5; }
    .tt-finance-pill.primary { color:#1d4ed8; background:#eff6ff; }
    .tt-finance-pill.warning { color:#a16207; background:#fefce8; }
    .tt-finance-pill.danger { color:#b91c1c; background:#fef2f2; }
    .tt-finance-pill.secondary { color:#475569; background:#f1f5f9; }
    .tt-finance-flow-in { color:#047857; font-weight:800; }
    .tt-finance-flow-out { color:#b91c1c; font-weight:800; }
    .tt-finance-flow-unknown { color:#a16207; font-weight:800; }
    .tt-finance-empty { padding:3rem 1rem; color:#64748b; text-align:center; }
    .tt-finance-empty i { display:block; margin-bottom:.5rem; color:#94a3b8; font-size:1.8rem; }
    .tt-finance-pagination { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.75rem 1rem; border:1px solid #e5e7eb; border-top:0; border-radius:0 0 14px 14px; color:#64748b; background:var(--card,#fff); font-size:.74rem; }
    .tt-finance-pagination .btn { border-radius:999px; font-size:.75rem; }
    .tt-finance-modal-backdrop { position:fixed; inset:0; z-index:1050; display:none; align-items:flex-start; justify-content:center; overflow:auto; padding:1.2rem; background:rgba(15,23,42,.65); }
    .tt-finance-modal-backdrop.show { display:flex; }
    .tt-finance-modal { width:min(920px,100%); margin:3vh auto; overflow:hidden; border:1px solid #e5e7eb; border-radius:18px; background:var(--card,#fff); box-shadow:0 25px 80px rgba(15,23,42,.35); }
    .tt-finance-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border-bottom:1px solid #e5e7eb; background:rgba(15,118,110,.04); }
    .tt-finance-modal-title { color:#111827; font-size:1rem; font-weight:900; }
    .tt-finance-modal-sub { margin-top:.2rem; color:#64748b; font-size:.72rem; }
    .tt-finance-close { border:0; color:#64748b; background:transparent; cursor:pointer; font-size:1.35rem; line-height:1; }
    .tt-finance-detail-body { padding:1.15rem; }
    .tt-finance-detail-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; margin-bottom:1.1rem; }
    .tt-finance-detail-card { padding:.65rem .85rem; border:1px solid #e5e7eb; border-radius:10px; background:rgba(148,163,184,.03); }
    .tt-finance-detail-label { color:#64748b; font-size:.64rem; font-weight:850; letter-spacing:.04em; text-transform:uppercase; }
    .tt-finance-detail-value { margin-top:.25rem; color:#111827; font-size:.82rem; font-weight:800; word-break:break-word; }
    .tt-finance-raw { max-height:320px; overflow:auto; margin:0; padding:.85rem; border-radius:10px; color:#cbd5e1; background:#0f172a; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:.7rem; line-height:1.45; }
    .tt-finance-raw { max-width:280px; margin:.4rem 0 0; padding:.45rem; border-radius:7px; color:#475569; background:#f8fafc; font-size:.65rem; white-space:pre-wrap; word-break:break-word; }
    @media (max-width: 992px) { .tt-finance-filters-grid { grid-template-columns:1fr 1fr; } .tt-finance-kpis { grid-template-columns:1fr 1fr; } .tt-finance-detail-grid { grid-template-columns:1fr; } }
    @media (max-width: 600px) { .tt-finance-filters-grid { grid-template-columns:1fr; } .tt-finance-kpis { grid-template-columns:1fr 1fr; } .tt-finance-actions { align-items:stretch; } .tt-finance-actions-group { width:100%; } .tt-finance-actions-group .btn { flex:1 1 auto; } }
</style>
@endpush

@section('content')
<div class="page-wrap tt-finance-page">
    <section class="tt-finance-hero">
        <div>
            <h1>{{ $walletMode ? 'Mutasi Wallet TikTok Shop' : 'Transaksi Finansial TikTok Shop' }}</h1>
            <p>{{ $walletMode ? 'Mutasi saldo seller dari endpoint Finance TikTok. Data disimpan lokal secara idempotent untuk rekonsiliasi; mutasi wallet bukan bukti saldo sudah masuk rekening bank.' : 'Gabungkan mutasi akun seller dengan detail settlement statement TikTok Shop. Data disinkronkan secara idempotent; rincian revenue, ongkir, fee, pajak, reserve, dan adjustment tersedia dari sumber statement.' }}</p>
        </div>
        <span class="tt-finance-badge"><i aria-hidden="true"></i> {{ $walletMode ? 'TikTok Wallet · API 202309' : 'TikTok Finance · API 202309 + 202501' }}</span>
    </section>

    <div id="ttAlert" class="tt-finance-alert" role="alert"></div>

    <section class="tt-finance-card tt-finance-filters" aria-label="Filter transaksi finansial TikTok">
        <div class="tt-finance-filters-grid">
            <div class="tt-finance-field">
                <label for="ttStore">Toko TikTok</label>
                <select id="ttStore" class="form-select">
                    @forelse ($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }} · TikTok Shop</option>
                    @empty
                        <option value="">Tidak ada toko TikTok terhubung</option>
                    @endforelse
                </select>
            </div>
            <div class="tt-finance-field"><label for="ttFrom">Tanggal dari</label><input id="ttFrom" type="date" class="form-control"></div>
            <div class="tt-finance-field"><label for="ttTo">Tanggal sampai</label><input id="ttTo" type="date" class="form-control"></div>
            <div class="tt-finance-field">
                <label for="ttSource">Sumber data</label>
                <select id="ttSource" class="form-select">
                    @if ($walletMode)
                        <option value="account">Mutasi wallet seller</option>
                    @else
                        @foreach ($dataSources as $source)
                            <option value="{{ $source['code'] }}">{{ $source['label'] }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="tt-finance-field"><label for="ttStatus">Status</label><select id="ttStatus" class="form-select"><option value="">Semua status</option>@foreach ($statuses as $status)<option value="{{ $status }}">{{ $status }}</option>@endforeach</select></div>
        </div>

        <div class="tt-finance-preset-row">
            <div class="tt-finance-quick-presets">
                <span class="tt-finance-quick-label">Rentang cepat:</span>
                <button type="button" class="tt-finance-quick-btn" data-preset="today">Hari ini</button>
                <button type="button" class="tt-finance-quick-btn" data-preset="yesterday">Kemarin</button>
                <button type="button" class="tt-finance-quick-btn" data-preset="7days">7 Hari</button>
                <button type="button" class="tt-finance-quick-btn active" data-preset="15days">15 Hari</button>
                <button type="button" class="tt-finance-quick-btn" data-preset="30days">30 Hari</button>
                <button type="button" class="tt-finance-quick-btn" data-preset="thisMonth">Bulan Ini</button>
                <button type="button" class="tt-finance-quick-btn" data-preset="lastMonth">Bulan Lalu</button>
            </div>
        </div>

        <div class="tt-finance-search-bar">
            <div class="tt-finance-search-wrap">
                <i class="bi bi-search search-icon" aria-hidden="true"></i>
                <input id="ttSearch" type="search" class="form-control" placeholder="Cari ID transaksi, order, statement, atau keterangan…" autocomplete="off">
                <button id="ttSearchClear" type="button" class="tt-finance-search-clear" title="Hapus pencarian" aria-label="Hapus pencarian">×</button>
            </div>
            <div class="tt-finance-actions-group">
                <button id="ttReset" type="button" class="btn btn-outline-secondary btn-pill" title="Reset filter ke kondisi awal"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</button>
                <button id="ttExport" type="button" class="btn btn-outline-secondary btn-pill" title="Unduh CSV"><i class="bi bi-download me-1"></i>CSV</button>
                <button id="ttSync" type="button" class="btn btn-outline-primary btn-pill" title="Ambil data terbaru dari TikTok"><i class="bi bi-arrow-repeat me-1"></i>Sync sekarang</button>
                <button id="ttLoad" type="button" class="btn btn-primary btn-pill" title="Baca transaksi yang sudah tersimpan"><i class="bi bi-database me-1"></i>Muat Data Lokal</button>
            </div>
        </div>

        <div class="tt-finance-help">
            <i class="bi bi-info-circle-fill text-success" aria-hidden="true"></i>
            <span id="ttSourceHint">{{ $walletMode ? 'Data dibaca dari mutasi wallet yang tersimpan lokal. Sync sekarang menyegarkan WITHDRAW, SETTLE, TRANSFER, REVERSE, PAYMENT, dan REFUND.' : 'Data dibaca dari database lokal. Rentang maksimal 60 hari; Sync sekarang mengambil data terbaru dari TikTok.' }}</span>
        </div>
        <div id="ttTypesField" class="tt-finance-field mt-3">
            <label>{{ $walletMode ? 'Tipe transaksi wallet' : 'Tipe transaksi untuk sync akun' }}</label>
            <div class="tt-finance-types">
                @foreach ($transactionTypes as $type)
                    <label class="tt-finance-type"><input type="checkbox" class="form-check-input tt-type" value="{{ $type['code'] }}" checked> {{ $type['label'] }} <code>{{ $type['code'] }}</code></label>
                @endforeach
            </div>
        </div>
        <div class="tt-finance-actions">
            <small id="ttStorageStatus" class="tt-finance-storage-status">Sumber: database lokal · Belum ada sinkronisasi</small>
        </div>
    </section>

    <section class="tt-finance-kpis" aria-label="Ringkasan halaman">
        <div class="tt-finance-card tt-finance-kpi"><small>Total transaksi</small><strong id="ttCount">—</strong><div class="tt-finance-kpi-note">total hasil filter</div></div>
        <div class="tt-finance-card tt-finance-kpi in"><small>Total dana masuk (+)</small><strong id="ttIn">—</strong><div class="tt-finance-kpi-note">settlement dan dana masuk</div></div>
        <div class="tt-finance-card tt-finance-kpi out"><small>Total dana keluar (-)</small><strong id="ttOut">—</strong><div class="tt-finance-kpi-note">{{ $walletMode ? 'penarikan dan mutasi keluar wallet' : 'penarikan dan biaya iklan' }}</div></div>
        <div class="tt-finance-card tt-finance-kpi net"><small>Perubahan bersih</small><strong id="ttNet">—</strong><div class="tt-finance-kpi-note">selisih dana masuk dan keluar</div></div>
    </section>

    <section id="ttBreakdownWrap" class="tt-finance-breakdown" style="display:none;" aria-label="Ringkasan tipe transaksi">
        <span class="tt-finance-breakdown-label">Filter tipe:</span>
        <div id="ttBreakdownPills" class="tt-finance-breakdown-pills"></div>
    </section>

    @unless ($walletMode)
    <section class="tt-finance-card tt-finance-recon" aria-label="Rekonsiliasi uang keluar">
        <div class="tt-finance-recon-head">
            <div><div class="tt-finance-recon-title">Rekonsiliasi uang keluar</div><div class="tt-finance-recon-note">Sumber sementara: transaksi GMV Pay dan billing yang sudah tersimpan di Finance TikTok. Dipakai untuk mencocokkan kas keluar, bukan menggantikan Cost iklan harian.</div></div>
            <span id="ttReconStatus" class="tt-finance-recon-status">Belum dimuat</span>
        </div>
        <div class="tt-finance-recon-grid">
            <div class="tt-finance-recon-item gmv"><small>GMV Pay</small><strong id="ttReconGmv">—</strong></div>
            <div class="tt-finance-recon-item billing"><small>Billing iklan</small><strong id="ttReconBilling">—</strong></div>
            <div class="tt-finance-recon-item"><small>Total kas keluar iklan terpetakan</small><strong id="ttReconTotal">—</strong></div>
            <div class="tt-finance-recon-item"><small>Uang keluar belum terklasifikasi</small><strong id="ttReconOther">—</strong></div>
        </div>
        <div id="ttReconMeta" class="tt-finance-recon-note mt-2">Pilih toko dan periode, lalu muat data.</div>
    </section>
    @endunless

    <section class="tt-finance-card">
        <div id="ttTableWrap" class="tt-finance-table-wrap">
            <div class="tt-finance-empty"><i class="bi bi-database"></i>Pilih toko dan periode, lalu muat transaksi dari database lokal.</div>
        </div>
        <div class="tt-finance-pagination">
            <span id="ttPageInfo">Belum ada data</span>
            <div class="d-flex gap-2"><button id="ttPrev" type="button" class="btn btn-sm btn-outline-secondary" disabled>Awal</button><button id="ttNext" type="button" class="btn btn-sm btn-outline-primary" disabled>Berikutnya <i class="bi bi-arrow-right"></i></button></div>
        </div>
    </section>
</div>

<div id="ttModalBackdrop" class="tt-finance-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="ttModalTitle">
    <div class="tt-finance-modal">
        <div class="tt-finance-modal-head">
            <div>
                <div id="ttModalTitle" class="tt-finance-modal-title">Rincian Transaksi TikTok</div>
                <div id="ttModalSub" class="tt-finance-modal-sub">Informasi detail dan raw response TikTok</div>
            </div>
            <button id="ttModalClose" type="button" class="tt-finance-close" aria-label="Tutup">×</button>
        </div>
        <div id="ttModalBody" class="tt-finance-detail-body"></div>
    </div>
</div>

<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const walletMode = @json($walletMode);
    const state = { page: 1, lastPage: 1, hasMore: false, items: [], loading: false, syncing: false };
    const pad = (value) => String(value).padStart(2, '0');
    const formatDate = (date) => date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
    const setDateRange = (from, to, preset = null) => {
        $('ttFrom').value = formatDate(from);
        $('ttTo').value = formatDate(to);
        document.querySelectorAll('.tt-finance-quick-btn').forEach((button) => {
            button.classList.toggle('active', Boolean(preset && button.dataset.preset === preset));
        });
    };
    const applyPreset = (preset) => {
        const now = new Date();
        const current = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        if (preset === 'today') return setDateRange(current, current, preset);
        if (preset === 'yesterday') {
            const yesterday = new Date(current);
            yesterday.setDate(yesterday.getDate() - 1);
            return setDateRange(yesterday, yesterday, preset);
        }
        if (preset === '7days') {
            const from = new Date(current);
            from.setDate(from.getDate() - 6);
            return setDateRange(from, current, preset);
        }
        if (preset === '30days') {
            const from = new Date(current);
            from.setDate(from.getDate() - 29);
            return setDateRange(from, current, preset);
        }
        if (preset === 'thisMonth') return setDateRange(new Date(current.getFullYear(), current.getMonth(), 1), current, preset);
        if (preset === 'lastMonth') return setDateRange(new Date(current.getFullYear(), current.getMonth() - 1, 1), new Date(current.getFullYear(), current.getMonth(), 0), preset);
        const from = new Date(current);
        from.setDate(from.getDate() - 14);
        return setDateRange(from, current, '15days');
    };
    applyPreset('15days');

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const selectedTypes = () => [...document.querySelectorAll('.tt-type:checked')].map((input) => input.value);
    const money = (value) => value === null || value === undefined || Number.isNaN(Number(value)) ? '—' : 'Rp ' + Math.round(Number(value)).toLocaleString('id-ID');
    const showMessage = (message, success = false) => { $('ttAlert').textContent = message; $('ttAlert').classList.toggle('success', success); $('ttAlert').classList.add('show'); };
    const showError = (message) => showMessage(message, false);
    const clearError = () => { $('ttAlert').textContent = ''; $('ttAlert').classList.remove('show', 'success'); };

    const validate = () => {
        if (!$('ttStore').value) return 'Belum ada toko TikTok Shop yang aktif.';
        if (!$('ttFrom').value || !$('ttTo').value) return 'Tanggal wajib diisi.';
        if ($('ttFrom').value > $('ttTo').value) return 'Tanggal mulai tidak boleh melewati tanggal akhir.';
        const days = Math.round((new Date(`${$('ttTo').value}T00:00:00`) - new Date(`${$('ttFrom').value}T00:00:00`)) / 86400000) + 1;
        if (days > 60) return `Rentang ${days} hari terlalu panjang. Maksimal 60 hari.`;
        if ($('ttSource').value === 'account' && !selectedTypes().length) return 'Pilih minimal satu tipe transaksi.';
        return null;
    };

    const buildUrl = (exportMode = false) => {
        const basePath = walletMode ? 'tiktok-wallet-transactions' : 'tiktok-financial-transactions';
        const path = `/api/marketplace/stores/${encodeURIComponent($('ttStore').value)}/${basePath}${exportMode ? '/export' : ''}`;
        const query = new URLSearchParams({ date_from: $('ttFrom').value, date_to: $('ttTo').value, source: $('ttSource').value, types: selectedTypes().join(','), search: $('ttSearch').value.trim() });
        if ($('ttStatus').value) query.set('status', $('ttStatus').value);
        if (!exportMode) { query.set('page', String(state.page)); query.set('page_size', '100'); }
        return `${path}?${query.toString()}`;
    };

    const render = (items) => {
        if (!items.length) {
            $('ttTableWrap').innerHTML = '<div class="tt-finance-empty"><i class="bi bi-inbox"></i>Tidak ada transaksi pada filter ini.</div>';
            return;
        }
        const sourceHeader = walletMode ? '' : '<th>Sumber</th>';
        const sourceCell = walletMode ? '' : `<td><span class="tt-finance-pill ${item.source === 'statement_transactions' ? 'success' : 'primary'}">${item.source === 'statement_transactions' ? 'Statement' : 'Akun'}</span></td>`;
        const referenceHeader = walletMode ? 'Referensi' : 'Statement / Order';
        $('ttTableWrap').innerHTML = `<table class="tt-finance-table"><thead><tr>${sourceHeader}<th>Waktu</th><th>ID</th><th>${referenceHeader}</th><th>Tipe</th><th>Arah</th><th>Nominal</th><th>Status</th><th>Detail</th></tr></thead><tbody>${items.map((item) => {
            const flowClass = item.money_flow === 'MONEY_IN' ? 'tt-finance-flow-in' : (item.money_flow === 'MONEY_OUT' ? 'tt-finance-flow-out' : 'tt-finance-flow-unknown');
            const flowLabel = item.money_flow === 'MONEY_IN' ? 'Dana masuk' : (item.money_flow === 'MONEY_OUT' ? 'Dana keluar' : 'Belum ditentukan');
            const statementOrder = [item.statement_id ? `S: ${escapeHtml(item.statement_id)}` : '', item.order_id ? `O: ${escapeHtml(item.order_id)}` : ''].filter(Boolean).join('<br>') || '—';
            const detailAmounts = item.source === 'statement_transactions' && item.settlement_amount !== null && item.settlement_amount !== undefined
                ? `<small class="d-block text-muted">Settlement: ${escapeHtml(money(item.settlement_amount, item.currency))}</small>` : '';
            return `<tr>${sourceCell}<td>${escapeHtml(item.date_formatted)}</td><td><code>${escapeHtml(item.transaction_id)}</code></td><td><code>${statementOrder}</code></td><td><span class="tt-finance-pill ${escapeHtml(item.badge || 'secondary')}">${escapeHtml(item.transaction_type_label)}<br><small>${escapeHtml(item.transaction_type)}</small></span></td><td class="${flowClass}">${flowLabel}</td><td class="${flowClass}">${escapeHtml(item.signed_formatted_amount || item.formatted_amount)}${detailAmounts}</td><td><span class="tt-finance-pill secondary">${escapeHtml(item.status)}</span></td><td>${escapeHtml(item.reason || 'Tidak ada deskripsi')}<details><summary>Raw JSON</summary><pre class="tt-finance-raw">${escapeHtml(JSON.stringify(item.raw_payload || item, null, 2))}</pre></details></td></tr>`;
        }).join('')}</tbody></table>`;
    };

    const searchItems = (items) => {
        const query = $('ttSearch').value.trim().toLowerCase();
        if (!query) return items;
        return items.filter((item) => [
            item.transaction_id,
            item.statement_id,
            item.order_id,
            item.adjustment_id,
            item.transaction_type,
            item.transaction_type_label,
            item.reason,
            item.status,
        ].filter(Boolean).join(' ').toLowerCase().includes(query));
    };

    const renderBreakdown = (breakdowns) => {
        const wrap = $('ttBreakdownWrap');
        const container = $('ttBreakdownPills');
        if (!breakdowns?.length) {
            wrap.style.display = 'none';
            container.innerHTML = '';
            return;
        }
        wrap.style.display = 'flex';
        container.innerHTML = breakdowns.map((breakdown) => {
            const badge = breakdown.badge || 'secondary';
            return '<button type="button" class="tt-finance-pill-button tt-finance-pill ' + escapeHtml(badge) + '" data-type="' + escapeHtml(breakdown.type) + '">' +
                '<span>' + escapeHtml(breakdown.label) + '</span><span class="badge bg-dark-subtle text-dark">' + Number(breakdown.count || 0).toLocaleString('id-ID') + '</span></button>';
        }).join('');
        container.querySelectorAll('[data-type]').forEach((button) => button.addEventListener('click', () => {
            $('ttSource').value = 'account';
            document.querySelectorAll('.tt-type').forEach((input) => { input.checked = input.value === button.dataset.type; });
            state.page = 1;
            updateSourceControls();
            load();
        }));
    };

    const openDetailModal = (item) => {
        if (!item) return;
        const fields = [
            ['ID Transaksi', item.transaction_id || '—'],
            ['Toko', item.store_name || '—'],
            ['Waktu transaksi', item.date_formatted || '—'],
            ['Tipe transaksi', (item.transaction_type_label || '—') + ' (' + (item.transaction_type || '—') + ')'],
            ['Arah dana', item.money_flow === 'MONEY_IN' ? 'Dana masuk (+)' : (item.money_flow === 'MONEY_OUT' ? 'Dana keluar (-)' : 'Belum ditentukan')],
            ['Nominal', item.signed_formatted_amount || item.formatted_amount || '—'],
            ['Statement ID', item.statement_id || '—'],
            ['Order ID', item.order_id || '—'],
            ['Adjustment ID', item.adjustment_id || '—'],
            ['Status', item.status || '—'],
            ['Keterangan', item.reason || '—'],
        ];
        $('ttModalTitle').textContent = 'Transaksi #' + (item.transaction_id || '—');
        $('ttModalSub').textContent = (item.date_formatted || '—') + ' · ' + (item.transaction_type_label || item.transaction_type || 'Transaksi TikTok');
        $('ttModalBody').innerHTML = '<div class="tt-finance-detail-grid">' + fields.map(([label, value]) => '<div class="tt-finance-detail-card"><div class="tt-finance-detail-label">' + escapeHtml(label) + '</div><div class="tt-finance-detail-value">' + escapeHtml(value) + '</div></div>').join('') + '</div><div class="mt-3"><h6 class="fw-bold mb-2 text-secondary" style="font-size:.78rem;"><i class="bi bi-filetype-json me-1"></i>Raw response TikTok</h6><pre class="tt-finance-raw">' + escapeHtml(JSON.stringify(item.raw_payload || item, null, 2)) + '</pre></div>';
        $('ttModalBackdrop').classList.add('show');
    };

    const load = async () => {
        clearError();
        const error = validate();
        if (error) { showError(error); return; }
        state.loading = true; $('ttLoad').disabled = true; $('ttNext').disabled = true;
        $('ttTableWrap').innerHTML = '<div class="tt-finance-empty"><i class="bi bi-database"></i>Membaca database lokal…</div>';
        try {
            const response = await fetch(buildUrl(), { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const reconciliationPromise = !walletMode && $('ttStore').value
                ? fetch(`/api/marketplace/stores/${encodeURIComponent($('ttStore').value)}/tiktok-financial-transactions/reconciliation?date_from=${encodeURIComponent($('ttFrom').value)}&date_to=${encodeURIComponent($('ttTo').value)}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                : Promise.resolve(null);
            const json = await response.json().catch(() => ({}));
            if (!response.ok || json.success === false) throw new Error(json.message || `Gagal mengambil data (${response.status})`);
            const data = json.data || {}; const summary = data.summary || {}; const pagination = data.pagination || {};
            state.items = data.items || []; state.lastPage = Number(pagination.last_page || 1); state.hasMore = Boolean(pagination.has_more);
            $('ttCount').textContent = Number(summary.total_count || 0).toLocaleString('id-ID');
            $('ttIn').textContent = money(summary.total_money_in); $('ttOut').textContent = money(summary.total_money_out); $('ttNet').textContent = money(summary.net_amount);
            renderBreakdown(summary.type_breakdown || []);
            const visibleItems = searchItems(state.items);
            render(visibleItems);
            document.querySelectorAll('.tt-finance-table details').forEach((details, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-outline-secondary btn-pill';
                button.innerHTML = '<i class="bi bi-code-slash me-1"></i>Detail';
                button.addEventListener('click', () => openDetailModal(visibleItems[index]));
                details.replaceWith(button);
            });
            $('ttNext').disabled = !state.hasMore; $('ttPrev').disabled = state.page <= 1; $('ttPageInfo').textContent = `Halaman ${state.page} dari ${state.lastPage} · ${visibleItems.length} baris dari ${Number(pagination.total || summary.total_count || 0).toLocaleString('id-ID')} transaksi`;
            $('ttStorageStatus').textContent = data.last_synced_at ? `Sumber: database lokal · Sync terakhir ${new Date(data.last_synced_at).toLocaleString('id-ID')}` : 'Sumber: database lokal · Belum ada sinkronisasi untuk periode ini';
            const reconResponse = await reconciliationPromise;
            const reconJson = reconResponse ? await reconResponse.json().catch(() => ({})) : {};
            if (reconResponse?.ok && reconJson.success) {
                const recon = reconJson.data || {}; const bucket = key => (recon.buckets || []).find((item) => item.key === key);
                const buckets = recon.buckets || [];
                const gmv = buckets.find((item) => item.label === 'GMV Pay') || buckets[0] || {};
                const billing = buckets.find((item) => item.label === 'Billing iklan') || buckets[1] || {};
                $('ttReconGmv').textContent = money(gmv.amount || 0); $('ttReconBilling').textContent = money(billing.amount || 0);
                $('ttReconTotal').textContent = money(recon.reconciled_ad_cash_out || 0); $('ttReconOther').textContent = money(recon.unclassified_money_out || 0);
                $('ttReconStatus').textContent = recon.status === 'READY' ? 'Siap direkonsiliasi' : (recon.status === 'NO_DATA' ? 'Belum ada data' : 'Belum ada kas iklan');
                $('ttReconStatus').className = `tt-finance-recon-status ${recon.status === 'READY' ? 'ready' : 'warn'}`;
                $('ttReconMeta').textContent = `${recon.total_transactions || 0} transaksi · Kas keluar total ${money(recon.total_money_out || 0)} · Sync terakhir ${recon.last_synced_at ? new Date(recon.last_synced_at).toLocaleString('id-ID') : 'belum ada'} · ${recon.note || ''}`;
            }
        } catch (error) {
            showError(error.message); $('ttTableWrap').innerHTML = `<div class="tt-finance-empty text-danger"><i class="bi bi-exclamation-triangle"></i>${escapeHtml(error.message)}</div>`; $('ttPageInfo').textContent = 'Gagal membaca database lokal';
        } finally { state.loading = false; $('ttLoad').disabled = false; }
    };

    const syncStatusUrl = () => `/api/marketplace/stores/${encodeURIComponent($('ttStore').value)}/tiktok-financial-transactions/sync-status`;
    const pollSync = async () => {
        for (let attempt = 0; attempt < 40; attempt++) {
            await new Promise((resolve) => setTimeout(resolve, 3000));
            try {
                const response = await fetch(syncStatusUrl(), { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const status = await response.json();
                if (status.label) $('ttStorageStatus').textContent = status.label;
                if (status.status === 'success') { showMessage('Sinkronisasi selesai. Data lokal diperbarui.', true); await load(); return; }
                if (status.status === 'error') { showError(status.message || status.label || 'Sinkronisasi gagal.'); return; }
            } catch (error) { /* polling berikutnya mencoba lagi */ }
        }
        showMessage('Sinkronisasi masih berjalan di background. Muat ulang beberapa saat lagi.', true);
    };

    const syncNow = async () => {
        clearError();
        const error = validate();
        if (error) { showError(error); return; }
        state.syncing = true; $('ttSync').disabled = true; $('ttSync').innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Masuk antrean…';
        try {
            const response = await fetch(`/api/marketplace/stores/${encodeURIComponent($('ttStore').value)}/tiktok-financial-transactions/sync`, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ date_from: $('ttFrom').value, date_to: $('ttTo').value, source: 'all' }),
            });
            const json = await response.json().catch(() => ({}));
            if (!response.ok || json.success === false) throw new Error(json.message || `Gagal memasukkan sync (${response.status})`);
            showMessage('Sinkronisasi masuk antrean. Halaman akan membaca hasil terbaru setelah selesai.', true);
            pollSync();
        } catch (error) { showError(error.message); }
        finally { state.syncing = false; $('ttSync').disabled = false; $('ttSync').innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Sync sekarang'; }
    };

    const reset = () => {
        state.page = 1; state.lastPage = 1; state.hasMore = false; state.items = [];
        $('ttStatus').value = ''; $('ttSource').value = walletMode ? 'account' : 'statements'; $('ttSearch').value = ''; $('ttSearchClear').classList.remove('show');
        applyPreset('15days');
        document.querySelectorAll('.tt-type').forEach((input) => { input.checked = true; });
        updateSourceControls();
        load();
    };
    $('ttLoad').addEventListener('click', () => { state.page = 1; load(); });
    $('ttSync').addEventListener('click', syncNow);
    $('ttNext').addEventListener('click', () => { if (!state.loading && state.hasMore) { state.page++; load(); } });
    $('ttPrev').addEventListener('click', () => { if (!state.loading && state.page > 1) { state.page--; load(); } });
    $('ttReset').addEventListener('click', reset);
    $('ttExport').addEventListener('click', () => { const error = validate(); if (error) { showError(error); return; } window.location.href = buildUrl(true); });
    document.querySelectorAll('.tt-finance-quick-btn').forEach((button) => button.addEventListener('click', () => {
        applyPreset(button.dataset.preset);
        state.page = 1;
        load();
    }));
    let searchDebounce = null;
    $('ttSearch').addEventListener('input', () => {
        $('ttSearchClear').classList.toggle('show', Boolean($('ttSearch').value.trim()));
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => { state.page = 1; load(); }, 350);
    });
    $('ttSearchClear').addEventListener('click', () => {
        $('ttSearch').value = '';
        $('ttSearchClear').classList.remove('show');
        state.page = 1;
        load();
    });
    $('ttModalClose').addEventListener('click', () => $('ttModalBackdrop').classList.remove('show'));
    $('ttModalBackdrop').addEventListener('click', (event) => {
        if (event.target === $('ttModalBackdrop')) $('ttModalBackdrop').classList.remove('show');
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') $('ttModalBackdrop').classList.remove('show');
    });
    const updateSourceControls = () => {
        const accountSource = walletMode || $('ttSource').value === 'account';
        $('ttTypesField').style.opacity = accountSource ? '1' : '.62';
        $('ttTypesField').style.display = accountSource ? '' : 'none';
        document.querySelectorAll('.tt-type').forEach((input) => { input.disabled = !accountSource; });
        $('ttSourceHint').textContent = walletMode
            ? 'Data ditampilkan dari mutasi wallet yang tersimpan lokal; Sync sekarang menyegarkan semua tipe transaksi akun TikTok.'
            : accountSource
            ? 'Data ditampilkan dari tabel lokal; Sync sekarang menyegarkan mutasi akun dan statement TikTok.'
            : 'Data ditampilkan dari tabel lokal; Sync sekarang menyegarkan account + statement TikTok.';
    };
    ['ttStore', 'ttFrom', 'ttTo', 'ttStatus', 'ttSource'].forEach((id) => $(id).addEventListener('change', () => { state.page = 1; if (id === 'ttSource') updateSourceControls(); }));
    updateSourceControls();
    if ($('ttStore').value) load();
})();
</script>
@endsection
