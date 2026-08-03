{{-- resources/views/imports/marketplace/index.blade.php --}}
@extends('layouts.app')
@section('title','Imports • Marketplace Shipments')

@php
  $filters = $filters ?? [];
  $stores  = $stores ?? [];
  $draft   = $draft ?? session('mp_import_preview');
  $recentBatches = $recentBatches ?? collect();

  $from = $filters['from'] ?? '';
  $to   = $filters['to'] ?? '';

  $jumpDate = $to ?: ($from ?: now()->toDateString());
  $jumpChannel = $filters['channel'] ?? null;
  $jumpStoreId = $filters['store_id'] ?? null;
  $queueUrl = route('marketplace.reconcile.queue', array_filter([
      'status' => 'needs_review',
      'actionable' => 1,
      'date' => $jumpDate,
      'channel' => $jumpChannel,
      'store_id' => $jumpStoreId,
  ], fn($v) => $v !== null && $v !== ''));

@endphp

@push('head')
<style>
  /* =========================
    Layout
  ========================= */
  .page{
    max-width:1440px;
    margin:0 auto;
    padding: 1rem .9rem 5rem;
  }
.page > .d-flex { position: relative; z-index: 20; }


  /* .head-actions, .page > .d-flex { position: relative; z-index: 10; } */
  @media(min-width:768px){
    .page{ padding: 1.1rem 1rem 4.8rem; }
  }

  /* =========================
    Tokens (fallback)
  ========================= */
  :root{
    --line: rgba(148,163,184,.18);
    --line2: rgba(148,163,184,.22);
    --ink: var(--text);
    --muted: rgba(100,116,139,1);
    --soft: rgba(148,163,184,.06);
    --soft2: rgba(148,163,184,.10);
    --shadow: 0 10px 24px rgba(15,23,42,.05);
  }
  body[data-theme="dark"]{
    --ink: var(--text);
    --muted: rgba(148,163,184,.85);
    --line: rgba(148,163,184,.14);
    --line2: rgba(148,163,184,.18);
    --soft: rgba(148,163,184,.08);
    --soft2: rgba(148,163,184,.12);
    --shadow: 0 12px 28px rgba(0,0,0,.35);
  }

  /* =========================
    Card + Chip
  ========================= */
  .cardx{
    border:1px solid var(--line);
    border-radius:14px;
    background: var(--card, #fff);
    box-shadow: var(--shadow);
  }
  .chip{
    display:inline-flex;
    align-items:center;
    gap:.35rem;
    padding:.18rem .55rem;
    border-radius:999px;
    font-size:.78rem;
    border:1px solid var(--line2);
    background: var(--soft);
    white-space:nowrap;
  }
  .mono{
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace;
  }

  /* =========================
    Loading overlay
  ========================= */
  .overlay-loading{
    position:absolute;
    inset:0;
    background: rgba(255,255,255,.65);
    backdrop-filter: blur(1px);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:5;
    border-radius:14px;
  }
  body[data-theme="dark"] .overlay-loading{ background: rgba(2,6,23,.55); }

  /* =========================
    Table helpers
  ========================= */
  th[data-sort]{ cursor:pointer; user-select:none; }
  .sort-ind{ font-size:.75rem; opacity:.75; margin-left:.25rem; }

  .show-sm{ display:none; }
  .hide-sm{ display:table-cell; }

  /* Tabel panjang: scrollbar berada di dalam tabel, header tetap terlihat. */
  .import-table-scroll{
    position:relative;
    max-height:min(62vh, 680px);
    overflow:auto;
    overscroll-behavior:contain;
  }
  .import-table-scroll table{
    min-width:980px;
    border-collapse:separate;
    border-spacing:0;
  }
  .import-table-scroll thead th{
    position:sticky;
    top:0;
    z-index:4;
    background:var(--card, #fff)!important;
    box-shadow:0 1px 0 var(--line), 0 3px 8px rgba(15,23,42,.05);
  }
  .import-table-scroll tbody td:not(.text-muted),
  .import-table-scroll tbody td:not(.text-muted) .mono{
    color:var(--text)!important;
  }
  .import-table-scroll tbody td.text-muted,
  .import-table-scroll tbody td.text-muted .mono{
    color:var(--muted)!important;
  }
  body[data-theme="dark"] .import-table-scroll thead th{
    background:var(--card, #0f172a)!important;
    box-shadow:0 1px 0 var(--line), 0 3px 8px rgba(0,0,0,.18);
  }

  @media(max-width:820px){
    .import-table-scroll{ max-height:55vh; }
    thead{ display:none; }
    .hide-sm{ display:none; }
    .show-sm{ display:block; }
    tbody td{ display:block; border-bottom:none; padding:.75rem .85rem; }
    tbody tr{ display:block; border-bottom:1px solid rgba(148,163,184,.14); }
    body[data-theme="dark"] tbody tr{ border-bottom:1px solid rgba(148,163,184,.10); }
    .mrow{ display:flex; justify-content:space-between; gap:.75rem; }
    .mright{ text-align:right; white-space:nowrap; }
  }

  /* =========================
    Header actions
  ========================= */
  .head-actions .btn{ white-space:nowrap; border-radius:12px; }

  .shipment-hero{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:1rem;
    flex-wrap:wrap;
    margin-bottom:.9rem;
    padding:1.15rem 1.2rem;
    border:1px solid var(--line);
    border-radius:18px;
    background:var(--card,#fff);
    box-shadow:var(--shadow);
  }
  .shipment-hero-title{
    margin:0;
    color:var(--text);
    font-size:1.35rem;
    font-weight:900;
    letter-spacing:-.04em;
  }
  .shipment-hero-sub{
    max-width:48rem;
    margin-top:.25rem;
    color:var(--muted);
    font-size:.82rem;
  }
  .shipment-eyebrow{
    display:inline-flex;
    align-items:center;
    gap:.35rem;
    margin-bottom:.35rem;
    color:var(--muted);
    font-size:.65rem;
    font-weight:900;
    letter-spacing:.1em;
    text-transform:uppercase;
  }
  .shipment-badges,
  .shipment-actions,
  .shipment-tabs{
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:.45rem;
  }
  .shipment-badges{ margin-top:.8rem; }
  .shipment-chip{
    display:inline-flex;
    align-items:center;
    gap:.35rem;
    padding:.33rem .62rem;
    border:1px solid var(--line2);
    border-radius:999px;
    background:var(--soft);
    color:var(--muted);
    font-size:.7rem;
    font-weight:800;
    white-space:nowrap;
  }
  .shipment-hero .btn{ border-radius:999px; font-weight:800; }
  .shipment-hero .btn-outline-secondary,
  .shipment-hero .btn-outline-primary,
  .shipment-hero .btn-outline-warning{
    background:var(--card);
  }

  .shipment-tabs-wrap{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:1rem;
    flex-wrap:wrap;
    margin-bottom:.9rem;
    padding:.28rem;
    border:1px solid var(--line);
    border-radius:999px;
    background:var(--card,#fff);
    box-shadow:var(--shadow);
  }
  .import-tabs{
    display:flex;
    gap:.2rem;
  }
  .import-tab{
    display:inline-flex;
    align-items:center;
    gap:.35rem;
    padding:.52rem .82rem;
    border:0;
    border-radius:999px;
    background:transparent;
    color:var(--muted);
    font-size:.76rem;
    font-weight:850;
    cursor:pointer;
  }
  .import-tab:hover,
  .import-tab.active{
    color:#fff;
    background:#0f172a;
  }
  body[data-theme="dark"] .import-tab:hover,
  body[data-theme="dark"] .import-tab.active{ background:#2563eb; }
  .import-tab .tab-count{
    display:inline-flex;
    min-width:1.25rem;
    justify-content:center;
    padding:.05rem .3rem;
    border-radius:999px;
    background:rgba(148,163,184,.16);
    font-size:.7rem;
  }
  .shipment-tab-meta{
    padding:0 .8rem;
    color:var(--muted);
    font-size:.72rem;
    font-weight:700;
  }
  .import-tab-panel[hidden]{ display:none!important; }

  .shipment-card{
    border:1px solid var(--line);
    border-radius:18px;
    background:var(--card,#fff);
    box-shadow:var(--shadow);
  }
  .shipment-card-head,
  .shipment-filter-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:.75rem;
    flex-wrap:wrap;
    padding:1rem 1rem .75rem;
  }
  .shipment-card-title{
    margin:0;
    color:var(--text);
    font-size:.9rem;
    font-weight:900;
  }
  .shipment-card-note{
    margin-top:.2rem;
    color:var(--muted);
    font-size:.72rem;
  }
  .shipment-filter-grid{
    display:grid;
    grid-template-columns:1.55fr .85fr .95fr 1.15fr 1.15fr;
    gap:.65rem;
    padding:0 1rem 1rem;
  }
  .shipment-field label{
    display:block;
    margin-bottom:.28rem;
    color:var(--muted);
    font-size:.68rem;
    font-weight:850;
  }
  .shipment-field .form-control,
  .shipment-field .form-select{ min-height:38px; }

  .shipment-kpi-grid{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:.7rem;
    margin-bottom:.9rem;
  }
  .shipment-kpi{
    min-height:112px;
    padding:1rem;
    border:1px solid var(--line);
    border-radius:18px;
    background:var(--card,#fff);
    box-shadow:var(--shadow);
  }
  .shipment-kpi.order{ border-top:3px solid #2563eb; }
  .shipment-kpi.ship{ border-top:3px solid #14b8a6; }
  .shipment-kpi.warn{ border-top:3px solid #f59e0b; }
  .shipment-kpi.muted{ border-top:3px solid #94a3b8; }
  .shipment-kpi-label{
    color:var(--muted);
    font-size:.7rem;
    font-weight:850;
    text-transform:uppercase;
    letter-spacing:.04em;
  }
  .shipment-kpi-value{
    margin-top:.25rem;
    color:var(--text);
    font-size:1.25rem;
    font-weight:900;
    letter-spacing:-.03em;
  }
  .shipment-kpi-sub{ margin-top:.2rem; color:var(--muted); font-size:.72rem; }

  .shipment-table-wrap{
    max-height:min(62vh,680px);
    overflow:auto;
    overscroll-behavior:contain;
    scrollbar-gutter:stable both-edges;
  }
  .shipment-table{
    width:100%;
    min-width:980px;
    border-collapse:separate;
    border-spacing:0;
  }
  .shipment-table th,
  .shipment-table td{
    padding:.7rem .75rem;
    border-bottom:1px solid var(--line);
    vertical-align:top;
  }
  .shipment-table th{
    position:sticky;
    top:0;
    z-index:4;
    background:var(--card,#fff)!important;
    box-shadow:0 1px 0 var(--line),0 3px 8px rgba(15,23,42,.05);
    color:var(--muted);
    font-size:.72rem;
    font-weight:850;
    white-space:nowrap;
  }
  .shipment-table tbody tr{ cursor:pointer; }
  .shipment-table tbody tr:hover td{ background:var(--soft); }
  .shipment-table tbody td:not(.text-muted),
  .shipment-table tbody td:not(.text-muted) .mono{ color:var(--text)!important; }
  .shipment-table tbody td.text-muted,
  .shipment-table tbody td.text-muted .mono{ color:var(--muted)!important; }

  @media(max-width:1100px){
    .shipment-filter-grid{ grid-template-columns:1.4fr 1fr 1fr 1fr; }
    .shipment-kpi-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); }
  }
  @media(max-width:820px){
    .shipment-filter-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); }
    .shipment-table-wrap{ max-height:55vh; }
    .shipment-kpi-grid{ grid-template-columns:1fr; }
    .shipment-tabs-wrap{ border-radius:18px; }
    .shipment-tabs{ flex:1 1 100%; }
    .import-tab{ flex:1 1 auto; justify-content:center; }
    .shipment-tab-meta{ width:100%; padding:.3rem .55rem .5rem; }
  }
  @media(max-width:520px){ .shipment-filter-grid{ grid-template-columns:1fr; } }

  /* =========================
    Periode Bar (pill kanan, tidak full)
  ========================= */
  .period-card .title{
    font-weight:800;
    letter-spacing:.01em;
    color: var(--ink);
  }

  /* grid: [title] [preset] [spacer] [pill right] */
  .period-bar{
    display:grid;
    grid-template-columns: auto minmax(220px, 320px) 1fr auto;
    gap:.75rem;
    align-items:center;
  }

  .period-select{
    width:100%;
    max-width:320px;
    border-radius:10px;
  }

  .range-pill{
    display:inline-flex;
    align-items:center;
    justify-content:space-between;
    gap:.7rem;

    border:1px solid var(--line2);
    background: var(--soft);
    padding:.52rem .85rem;
    border-radius:12px;
    cursor:pointer;

    font-size:.9rem;
    color: var(--ink);

    width: 360px;      /* ✅ tidak full */
    max-width: 100%;
    justify-self: end; /* ✅ nempel kanan */
  }
  .range-pill:hover{ background: var(--soft2); }

  .range-pill .range-text{
    font-weight:650;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    max-width: 230px;
  }
  .range-pill .range-meta{
    display:inline-flex;
    align-items:center;
    gap:.5rem;
    flex:0 0 auto;
  }
  .range-pill .tz{ color: var(--muted); font-size:.82rem; }
  .range-pill .ico{ opacity:.85; }

  @media(max-width:820px){
    .period-bar{ grid-template-columns: 1fr; gap:.6rem; }
    .period-select{ max-width:100%; }
    .range-pill{ width:100%; justify-self: stretch; }
    .range-pill .range-text{ max-width: 70%; }
  }

  /* =========================
    Filter grid
  ========================= */
  .filter-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:.75rem;
    margin-bottom:.55rem;
  }
  .filter-grid{
    display:grid;
    grid-template-columns: 2fr 1fr 1fr 1.2fr;
    gap:.65rem;
    align-items:end;
  }
  @media(max-width:1100px){ .filter-grid{ grid-template-columns: 2fr 1fr 1fr; } }
  @media(max-width:992px){ .filter-grid{ grid-template-columns: 1fr 1fr; } }
  @media(max-width:520px){ .filter-grid{ grid-template-columns: 1fr; } }
  .filter-grid .form-label{
    font-size:.78rem;
    margin-bottom:.25rem;
    color: var(--muted);
  }

  /* KPI delta */
  .kpi-delta{ font-size:.82rem; margin-top:.12rem; }

</style>
@endpush

@section('content')


@php
  $draft = $draft ?? session('mp_import_preview');
//   dd($draft);
  $canResume = !empty($draft)
    && !empty($draft['disk'])
    && !empty($draft['stored_path'])
    && !empty($draft['channel_key'])
    && \Illuminate\Support\Facades\Storage::disk($draft['disk'])->exists($draft['stored_path']);
@endphp

<div class="page">

  {{-- =========================
    HEADER
  ========================= --}}
  <section class="shipment-hero">
    <div>
      <div class="shipment-eyebrow"><i class="bi bi-truck"></i> Marketplace shipments</div>
      <h1 class="shipment-hero-title">Rincian Shipment Import</h1>
      <div class="shipment-hero-sub">
        Status pengiriman, order, tracking, dan performa fulfillment dari file marketplace atau sinkronisasi API.
      </div>
      <div class="shipment-badges">
        <span class="shipment-chip"><i class="bi bi-database"></i> mp_shipments</span>
        <span class="shipment-chip" id="periodChip" style="display:none;"></span>

        @if(!empty($draft))
          <span class="shipment-chip" style="border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.10);">
            Draft import tersedia
          </span>
        @endif
        <span id="filterActiveChip"
              class="shipment-chip"
              style="display:none; border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.10);">
          Filter aktif
        </span>
      </div>
    </div>

    <div class="shipment-actions head-actions">
      <a class="btn btn-outline-secondary btn-sm px-3" href="{{ route('imports.marketplace.index') }}">
        <i class="bi bi-arrow-clockwise"></i> Refresh
      </a>
      <button type="button"
              class="btn btn-outline-secondary btn-sm px-3"
              data-bs-toggle="modal"
              data-bs-target="#exportModal">
        <i class="bi bi-download"></i> Export
      </button>
      @if($canResume)
        <a class="btn btn-outline-warning btn-sm px-3" href="{{ route('imports.marketplace.draft') }}">
          <i class="bi bi-file-earmark-arrow-up"></i> Lanjutkan Draft
        </a>
      @elseif(!empty($draft))
        <a class="btn btn-outline-warning btn-sm px-3" href="{{ route('imports.marketplace.create') }}">
          <i class="bi bi-arrow-repeat"></i> Upload Ulang Draft
        </a>
      @endif
      <a class="btn btn-outline-primary btn-sm px-3" href="{{ $queueUrl }}">
        <i class="bi bi-check2-square"></i> Reconcile
      </a>
      <a class="btn btn-primary btn-sm px-3" href="{{ route('imports.marketplace.create') }}">
        <i class="bi bi-box-seam"></i> Import Order
      </a>
      <a class="btn btn-outline-primary btn-sm px-3" href="{{ route('imports.marketplace_income.create') }}">
        <i class="bi bi-wallet2"></i> Import Income
      </a>
    </div>
  </section>

  <div class="shipment-tabs-wrap">
    <div class="import-tabs" role="tablist" aria-label="Navigasi marketplace shipments">
      <button type="button" class="import-tab active" role="tab" aria-selected="true" data-import-tab="data">
        <i class="bi bi-table"></i> Data Pengiriman
      </button>
      <button type="button" class="import-tab" role="tab" aria-selected="false" data-import-tab="performance">
        <i class="bi bi-graph-up-arrow"></i> Performa
      </button>
      <button type="button" class="import-tab" role="tab" aria-selected="false" data-import-tab="history">
        <i class="bi bi-clock-history"></i> Riwayat Import <span class="tab-count">{{ $recentBatches->count() }}</span>
      </button>
    </div>
    <div class="shipment-tab-meta">Data shipment • KPI fulfillment • riwayat batch CSV/API</div>
  </div>

  <section class="import-tab-panel" data-import-panel="history" role="tabpanel" hidden>
  @if($recentBatches->isNotEmpty())
    <section class="shipment-card mb-3">
      <div class="shipment-card-head">
        <div>
          <h2 class="shipment-card-title"><i class="bi bi-clock-history"></i> Riwayat Import Terakhir</h2>
          <div class="shipment-card-note">Batch CSV/API yang masuk ke data shipment.</div>
        </div>
        <span class="shipment-card-note">{{ $recentBatches->count() }} batch</span>
      </div>
      <div class="table-responsive import-table-scroll">
        <table class="shipment-table">
          <thead>
            <tr>
              <th>Waktu</th>
              <th>Channel</th>
              <th>Store</th>
              <th>File</th>
              <th>Status</th>
              <th class="text-end">Insert / Update</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($recentBatches as $batch)
              <tr>
                <td class="text-muted small">{{ optional($batch->created_at)->format('d M Y H:i') }}</td>
                <td><span class="chip">{{ strtoupper($batch->channel) }}</span></td>
                <td>{{ $batch->store?->name ?? '-' }}</td>
                <td class="mono small" style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $batch->source_file ?: '-' }}</td>
                <td>
                  <span class="badge {{ $batch->status === 'completed' ? 'text-bg-success' : ($batch->status === 'failed' ? 'text-bg-danger' : 'text-bg-warning') }}">
                    {{ strtoupper($batch->status) }}
                  </span>
                </td>
                <td class="text-end small">{{ (int) $batch->inserted_shipments }} / {{ (int) $batch->updated_shipments }}</td>
                <td class="text-end">
                  @if (in_array($batch->status, ['completed', 'failed'], true) && (int) $batch->updated_shipments === 0)
                    <form method="POST"
                          action="{{ route('imports.marketplace.batches.destroy', $batch) }}"
                          class="d-inline"
                          onsubmit="return confirm('Hapus import ini beserta shipment yang dibuat oleh batch tersebut?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus import">
                        <i class="bi bi-trash3"></i>
                      </button>
                    </form>
                  @elseif ((int) $batch->updated_shipments > 0)
                    <span class="text-muted small" title="Batch memperbarui shipment lama">Tidak aman</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </section>
  @else
    <section class="shipment-card p-4 mb-3 text-muted">Belum ada riwayat import.</section>
  @endif
  </section>

  <section class="import-tab-panel" data-import-panel="data" role="tabpanel">

  {{-- Kontrol preset tetap dipakai oleh filter JS; tanggal tampil di filter utama. --}}
  <div class="d-none" aria-hidden="true">
    <select id="presetRange">
      <option value="7">7 hari sebelumnya</option>
      <option value="14">14 hari sebelumnya</option>
      <option value="30">30 hari sebelumnya</option>
      <option value="month">Bulan ini</option>
      <option value="custom">Pilih tanggal…</option>
    </select>
    <button type="button" id="toggleDate"></button>
    <input type="text" id="rangePicker" tabindex="-1">
  </div>

  {{-- =========================
    FILTER (auto apply)
  ========================= --}}
  <section class="shipment-card mb-3">
    <div class="shipment-filter-head">
      <div>
        <h2 class="shipment-card-title"><i class="bi bi-funnel"></i> Filter data</h2>
        <div class="shipment-card-note">Cari order, tracking, status, toko, atau periode pengiriman.</div>
      </div>
      <a href="{{ route('imports.marketplace.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
    </div>

    <form id="filterForm" method="GET" action="{{ route('imports.marketplace.index') }}">
      <div class="shipment-filter-grid">
      <div class="shipment-field">
        <label>Cari</label>
        <input class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Order / Tracking / Shipment">
      </div>

      <div class="shipment-field">
        <label>Channel</label>
        <select class="form-select" name="channel">
          <option value="">Semua</option>
          <option value="shopee" @selected(($filters['channel'] ?? '')==='shopee')>Shopee</option>
          <option value="tiktok" @selected(($filters['channel'] ?? '')==='tiktok')>TikTok</option>
        </select>
      </div>

      <div class="shipment-field">
        <label>Status</label>
        <select class="form-select" name="status">
          <option value="">Semua</option>
          <option value="in_transit" @selected(($filters['status'] ?? '')==='in_transit')>In Transit</option>
          <option value="delivered" @selected(($filters['status'] ?? '')==='delivered')>Delivered</option>
          <option value="canceled" @selected(($filters['status'] ?? '')==='canceled')>Canceled</option>
          <option value="unknown" @selected(($filters['status'] ?? '')==='unknown')>Unknown</option>
        </select>
      </div>

      <div class="shipment-field">
        <label>Store</label>
        <select class="form-select" name="store_id">
          <option value="">Semua</option>
          @foreach($stores as $st)
            <option value="{{ $st->id }}" @selected((string)($filters['store_id'] ?? '') === (string)$st->id)>
              {{ $st->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="shipment-field">
        <label>Date From</label>
        <input type="date" class="form-control" name="from" id="fromHidden" value="{{ $from }}">
      </div>

      <div class="shipment-field">
        <label>Date To</label>
        <input type="date" class="form-control" name="to" id="toHidden" value="{{ $to }}">
      </div>
      <div class="shipment-field" style="display:flex;align-items:end;">
        <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-search"></i> Terapkan</button>
      </div>
      </div>

    </form>
  </section>

  </section>

  <section class="import-tab-panel" data-import-panel="performance" role="tabpanel" hidden>

  {{-- =========================
    KPI
  ========================= --}}
  <div id="kpiWrap" class="position-relative">
    <div class="overlay-loading" id="kpiLoading">
      <div class="spinner-border" role="status"></div>
    </div>

    <div class="shipment-kpi-grid">
      <div class="shipment-kpi order">
        <div class="shipment-kpi-label">Penjualan</div>
        <div class="shipment-kpi-value">—</div>
        <div class="shipment-kpi-sub">Nilai order pada periode terpilih</div>
      </div>
      <div class="shipment-kpi ship">
        <div class="shipment-kpi-label">Pesanan</div>
        <div class="shipment-kpi-value">—</div>
        <div class="shipment-kpi-sub">Order shipment tercatat</div>
      </div>
      <div class="shipment-kpi warn">
        <div class="shipment-kpi-label">Delivered</div>
        <div class="shipment-kpi-value">—</div>
        <div class="shipment-kpi-sub">Pengiriman selesai</div>
      </div>
      <div class="shipment-kpi muted">
        <div class="shipment-kpi-label">Avg Delivery</div>
        <div class="shipment-kpi-value">—</div>
        <div class="shipment-kpi-sub">Rata-rata waktu pengiriman</div>
      </div>
    </div>
  </div>

  </section>

  <section class="import-tab-panel" data-import-panel="data" role="tabpanel">

  {{-- =========================
    TABLE
  ========================= --}}
  <section id="tableWrap" class="shipment-card position-relative">
    <div class="overlay-loading" id="tableLoading">
      <div class="spinner-border" role="status"></div>
    </div>
    <div class="p-3 text-muted">Loading data…</div>
  </section>
  </section>
</div>

{{-- =========================
  EXPORT MODAL
========================= --}}
<div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form class="modal-content" method="GET" action="{{ route('imports.marketplace.export') }}">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">Export CSV</h5>
          <div class="text-muted small">Pilih kolom yang mau diexport.</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div id="exportHiddenFilters">
          @foreach(($filters ?? []) as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
          @endforeach
        </div>

        @php
          $mainCols = [
            'platform_order_id' => 'Order ID',
            'platform_shipment_id' => 'Shipment ID',
            'channel' => 'Channel',
            'store' => 'Store',
            'tracking_no' => 'Tracking',
            'status_norm' => 'Status',
            'order_created_at' => 'Order Date',
            'total_qty' => 'Qty',
            'grand_total' => 'Grand Total',
            'shipped_at' => 'Shipped At',
            'delivered_at' => 'Delivered At',
          ];
        @endphp

        <div class="fw-bold mb-2">Kolom utama</div>
        <div class="row g-2">
          @foreach($mainCols as $k => $label)
            <div class="col-6 col-md-4">
              <label class="form-check">
                <input class="form-check-input" type="checkbox" name="cols[]" value="{{ $k }}" checked>
                <span class="form-check-label">{{ $label }}</span>
              </label>
            </div>
          @endforeach
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary">Download CSV</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const form = document.getElementById('filterForm');
  const kpiWrap = document.getElementById('kpiWrap');
  const tableWrap = document.getElementById('tableWrap');
  const kpiLoading = document.getElementById('kpiLoading');
  const tableLoading = document.getElementById('tableLoading');
  const filterActiveChip = document.getElementById('filterActiveChip');
  const exportHiddenFilters = document.getElementById('exportHiddenFilters');
  const periodChip = document.getElementById('periodChip');

  // period ui
  const preset = document.getElementById('presetRange');
  const toggleDate = document.getElementById('toggleDate');
  const rangeText = document.getElementById('rangeText');
  const fpInput = document.getElementById('rangePicker');

  // real filters
  const fromEl = document.getElementById('fromHidden');
  const toEl = document.getElementById('toHidden');

  if (!form || !kpiWrap || !tableWrap || !fromEl || !toEl || !fpInput) return;

  const API_URL = "{{ route('imports.marketplace.data') }}";
  const INDEX_URL = "{{ route('imports.marketplace.index') }}";
  const SHOW_URL_BASE = "{{ url('/imports/marketplace') }}";

  let sortState = { key: null, dir: 'asc' };
  let lastJson = null;

  const debounce = (fn, ms=300) => {
    let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
  };

  const escapeHtml = (str) => {
    return String(str ?? '')
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  };

  const fmtMoney = (n) => {
    const v = Number(n || 0);
    return 'Rp ' + v.toLocaleString('id-ID', { maximumFractionDigits: 0 });
  };

  const fmtDate = (iso) => {
    if (!iso) return '-';
    const d = new Date(iso);
    return new Intl.DateTimeFormat('id-ID', { day:'2-digit', month:'2-digit', year:'numeric' }).format(d);
  };

  const statusLabel = (s) => {
    switch (s) {
      case 'delivered': return ['Delivered', 'success'];
      case 'in_transit': return ['In Transit', 'primary'];
      case 'canceled': return ['Canceled', 'secondary'];
      default: return ['Unknown', 'dark'];
    }
  };

  const dPct = (v) => {
    const n = Number(v || 0);
    const sign = n >= 0 ? '+' : '';
    return sign + n.toFixed(1) + '%';
  };

  const deltaClass = (v) => {
    const n = Number(v || 0);
    if (n > 0) return 'text-success';
    if (n < 0) return 'text-danger';
    return 'text-muted';
  };

  function ymd(d){
    const x = new Date(d);
    const m = String(x.getMonth()+1).padStart(2,'0');
    const day = String(x.getDate()).padStart(2,'0');
    return `${x.getFullYear()}-${m}-${day}`;
  }

  function dmy(ymdStr){
    if(!ymdStr) return '-';
    const [y,m,d] = String(ymdStr).split('-');
    return `${d}-${m}-${y}`;
  }

  function setRangeText(){
    const f = fromEl.value || '';
    const t = toEl.value || '';
    if (rangeText) rangeText.textContent = `${dmy(f)} - ${dmy(t)}`;
  }

  function setLoading(on=true){
    if (kpiLoading) kpiLoading.style.display = on ? 'flex' : 'none';
    if (tableLoading) tableLoading.style.display = on ? 'flex' : 'none';
  }

  function toQuery(){
    const fd = new FormData(form);
    const params = new URLSearchParams();
    for (const [k,v] of fd.entries()) params.append(k, v);
    return params.toString();
  }

  function stripPageParam(url){
    try{
      const u = new URL(url, window.location.origin);
      u.searchParams.delete('page');
      return u.pathname + (u.searchParams.toString() ? ('?' + u.searchParams.toString()) : '');
    }catch(e){
      return url.replace(/([?&])page=\d+/,'$1').replace(/[?&]$/,'');
    }
  }

  function filterSignature(){
    const qs = toQuery();
    return stripPageParam('?' + qs);
  }

  function syncExportHiddenFilters(){
    if (!exportHiddenFilters) return;
    const fd = new FormData(form);
    exportHiddenFilters.innerHTML = '';
    for (const [k,v] of fd.entries()) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = k;
      input.value = v;
      exportHiddenFilters.appendChild(input);
    }
  }

  function setChips(){
    const fd = new FormData(form);

    let active = false;
    for (const [k,v] of fd.entries()) {
      if (k === 'from' || k === 'to') continue;
      if (String(v || '').trim() !== '') { active = true; break; }
    }
    if (filterActiveChip) filterActiveChip.style.display = active ? 'inline-flex' : 'none';

    const from = fd.get('from') || '';
    const to = fd.get('to') || '';
    if (periodChip && from && to) {
      periodChip.textContent = `${from} – ${to} (GMT+7)`;
      periodChip.style.display = 'inline-flex';
    }
  }

  async function fetchJson(url){
    const res = await fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" }});
    if (!res.ok) throw new Error("HTTP " + res.status);
    return await res.json();
  }

  function getSortVal(row, key){
    const v = row[key];
    if (key === 'order_created_at') return v ? new Date(v).getTime() : 0;
    if (key === 'grand_total' || key === 'total_qty') return Number(v || 0);
    return String(v ?? '').toLowerCase();
  }

  function sortRows(rows, key, dir){
    const m = dir === 'asc' ? 1 : -1;
    return [...rows].sort((a,b) => {
      const va = getSortVal(a, key);
      const vb = getSortVal(b, key);
      if (va < vb) return -1 * m;
      if (va > vb) return  1 * m;
      return 0;
    });
  }

  function sortIndicator(key){
    if (sortState.key !== key) return '';
    return sortState.dir === 'asc'
      ? '<span class="sort-ind">▲</span>'
      : '<span class="sort-ind">▼</span>';
  }

  function kpiDeltaBlock(deltaVal){
    if (deltaVal === null || deltaVal === undefined) return '';
    const cls = deltaClass(deltaVal);
    return `<div class="kpi-delta ${cls}">${dPct(deltaVal)}</div>`;
  }

  function renderKpi(json){
    const o = json.orders || {};
    const s = json.ship || {};
    const d = json.delta || {};

    const avg = (s.avg_delivery_days === null || s.avg_delivery_days === undefined)
      ? '-'
      : (Number(s.avg_delivery_days).toFixed(1) + ' hari');

    kpiWrap.innerHTML = `
      <div class="overlay-loading" id="kpiLoading"><div class="spinner-border" role="status"></div></div>

      <div class="shipment-kpi-grid">
        <div class="shipment-kpi order">
          <div class="shipment-kpi-label">Penjualan</div>
          <div class="shipment-kpi-value">${fmtMoney(o.sales)}</div>
          <div class="shipment-kpi-sub">${kpiDeltaBlock(d.orders_sales) || 'Nilai order pada periode terpilih'}</div>
        </div>
        <div class="shipment-kpi ship">
          <div class="shipment-kpi-label">Pesanan</div>
          <div class="shipment-kpi-value">${Number(o.orders||0)}</div>
          <div class="shipment-kpi-sub">${kpiDeltaBlock(d.orders_orders) || 'Order shipment tercatat'}</div>
        </div>
        <div class="shipment-kpi warn">
          <div class="shipment-kpi-label">Delivered</div>
          <div class="shipment-kpi-value">${Number(s.delivered||0)}</div>
          <div class="shipment-kpi-sub">${kpiDeltaBlock(d.ship_delivered) || 'Pengiriman selesai'}</div>
        </div>
        <div class="shipment-kpi muted">
          <div class="shipment-kpi-label">Avg Delivery</div>
          <div class="shipment-kpi-value">${avg}</div>
          <div class="shipment-kpi-sub">${d.ship_avg_days !== undefined ? kpiDeltaBlock(d.ship_avg_days) : 'Rata-rata waktu pengiriman'}</div>
        </div>
      </div>
    `;
  }

  function renderTable(json){
    lastJson = json;

    const summary = json.summary || {};
    const meta = (json.shipments && json.shipments.meta) ? json.shipments.meta : {};
    let rows = (json.shipments && json.shipments.data) ? json.shipments.data : [];

    if (sortState.key) rows = sortRows(rows, sortState.key, sortState.dir);

    const trs = rows.map(r => {
      const [stText, tone] = statusLabel(r.status_norm);
      const showUrl = `${SHOW_URL_BASE}/${r.id}`;

      const trackingChip = r.tracking_no ? `<span class="chip mono">${escapeHtml(r.tracking_no)}</span>` : '';
      const shipChip = r.platform_shipment_id ? `<span class="chip mono">Ship: ${escapeHtml(r.platform_shipment_id)}</span>` : '';

      return `
        <tr role="button" onclick="window.location='${showUrl}'">
          <td class="show-sm">
            <div class="mrow">
              <div>
                <div class="fw-bold mono">${escapeHtml(r.platform_order_id)}</div>
                <div class="mt-1 d-flex flex-wrap gap-1">
                  <span class="chip">Ch: ${escapeHtml(r.channel)}</span>
                  <span class="chip">${stText}</span>
                  ${trackingChip}
                </div>
                <div class="text-muted small mt-1">${fmtDate(r.order_created_at)}</div>
              </div>
              <div class="mright">
                <div class="fw-bold">${fmtMoney(r.grand_total)}</div>
                <div class="text-muted small">Qty ${Number(r.total_qty||0)}</div>
              </div>
            </div>
          </td>

          <td class="hide-sm">
            <div class="fw-bold mono">${escapeHtml(r.platform_order_id)}</div>
            <div class="text-muted small">
              <span class="chip">Ch: ${escapeHtml(r.channel)}</span>
              ${shipChip}
            </div>
          </td>

          <td class="hide-sm">${escapeHtml(r.store || '-')}</td>
          <td class="hide-sm"><span class="mono">${escapeHtml(r.tracking_no || '-')}</span></td>
          <td class="hide-sm"><span class="badge bg-${tone}">${stText}</span></td>
          <td class="hide-sm">${fmtDate(r.order_created_at)}</td>
          <td class="hide-sm text-end">${Number(r.total_qty||0)}</td>
          <td class="hide-sm text-end fw-bold">${fmtMoney(r.grand_total)}</td>
          <td class="hide-sm text-end" onclick="event.stopPropagation()">
            <a class="btn btn-sm btn-outline-secondary" href="${showUrl}">Detail</a>
          </td>
        </tr>
      `;
    }).join('');

    const pager = `
      <div class="p-3 d-flex justify-content-between align-items-center gap-2">
        <div class="text-muted small">
          Page ${meta.current_page || 1} / ${meta.last_page || 1} • Total ${meta.total || 0}
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm" data-page-url="${meta.prev_page_url || ''}" ${meta.prev_page_url ? '' : 'disabled'}>Prev</button>
          <button class="btn btn-outline-secondary btn-sm" data-page-url="${meta.next_page_url || ''}" ${meta.next_page_url ? '' : 'disabled'}>Next</button>
        </div>
      </div>
    `;

    tableWrap.innerHTML = `
      <div class="overlay-loading" id="tableLoading"><div class="spinner-border" role="status"></div></div>

      <div class="shipment-card-head">
        <div>
          <h2 class="shipment-card-title"><i class="bi bi-list-columns-reverse"></i> Shipment per order</h2>
          <div class="shipment-card-note">Klik baris untuk melihat detail shipment, item, dan riwayat status.</div>
        </div>
        <div class="shipment-card-note">
          ${Number(summary.rows||0)} row • Qty ${Number(summary.sum_qty||0)} • ${fmtMoney(summary.sum_grand_total||0)}
        </div>
      </div>

      <div class="shipment-table-wrap">
        <table class="shipment-table">
          <thead>
            <tr>
              <th data-sort="platform_order_id" style="width:290px;">Order ${sortIndicator('platform_order_id')}</th>
              <th data-sort="store" style="width:170px;">Store ${sortIndicator('store')}</th>
              <th data-sort="tracking_no" style="width:230px;">Tracking ${sortIndicator('tracking_no')}</th>
              <th data-sort="status_norm" style="width:130px;">Status ${sortIndicator('status_norm')}</th>
              <th data-sort="order_created_at" style="width:130px;">Tanggal ${sortIndicator('order_created_at')}</th>
              <th data-sort="total_qty" class="text-end" style="width:90px;">Qty ${sortIndicator('total_qty')}</th>
              <th data-sort="grand_total" class="text-end" style="width:170px;">Grand ${sortIndicator('grand_total')}</th>
              <th class="text-end" style="width:90px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            ${rows.length ? trs : `<tr><td colspan="8" class="p-3 text-muted">Belum ada data.</td></tr>`}
          </tbody>
        </table>
      </div>

      ${pager}
    `;
  }

  async function load(url=null){
    const qs = toQuery();
    const apiUrl = url ? url : (API_URL + (qs ? ("?" + qs) : ""));

    setLoading(true);
    const json = await fetchJson(apiUrl);
    setLoading(false);

    renderKpi(json);
    renderTable(json);

    const newUrl = INDEX_URL + (qs ? ("?" + qs) : "");
    window.history.replaceState({}, "", newUrl);

    setChips();
    syncExportHiddenFilters();
    setRangeText();
  }

  // ===== apply on filter change =====
  let lastSig = null;
  const run = debounce(async () => {
    try{
      lastSig = filterSignature();
      await load(null);
    }catch(e){
      console.error(e);
      setLoading(false);
    }
  }, 300);

  form.addEventListener('input', run);
  form.addEventListener('change', run);
  form.addEventListener('submit', (e) => { e.preventDefault(); run(); });

  tableWrap.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-page-url]');
    if (!btn) return;
    const url = btn.getAttribute('data-page-url');
    if (!url) return;
    try { await load(url); }
    catch(err){ console.error(err); window.location.href = url; }
  });

  tableWrap.addEventListener('click', (e) => {
    const th = e.target.closest('th[data-sort]');
    if (!th) return;

    const key = th.getAttribute('data-sort');
    if (!key) return;

    if (sortState.key === key) sortState.dir = (sortState.dir === 'asc') ? 'desc' : 'asc';
    else { sortState.key = key; sortState.dir = 'asc'; }

    if (lastJson) renderTable(lastJson);
  });

  // ===== Preset =====
  function applyPreset(val){
    const now = new Date();
    let start = null;
    const end = new Date(now);

    if (val === 'month'){
      start = new Date(now.getFullYear(), now.getMonth(), 1);
    } else {
      const n = Number(val || 7);
      start = new Date(now.getFullYear(), now.getMonth(), now.getDate() - (n-1));
    }

    fromEl.value = ymd(start);
    toEl.value   = ymd(end);
    setRangeText();
    run();
  }

  // ===== Flatpickr (GLOBAL helper) =====
  const initialFrom = fromEl.value || '';
  const initialTo = toEl.value || '';

  // ensure text displayed
  setRangeText();

  // init flatpickr through global helper (from app.blade.php)
  const fp = (window.GFID && typeof GFID.initDateRange === 'function')
    ? GFID.initDateRange(fpInput, {
        // UX
        showMonths: 2,
        clickOpens: false,

        // anchor to the pill (nempel kanan)
        positionElement: toggleDate,
        appendTo: document.body,
        position: "below right",

        // initial date
        defaultDate: (initialFrom && initialTo) ? [initialFrom, initialTo] : null,

        onChange: function(selectedDates, dateStr, instance){
          if (selectedDates.length < 2) return;

          const f = ymd(selectedDates[0]);
          const t = ymd(selectedDates[1]);

          fromEl.value = f;
          toEl.value   = t;

          setRangeText();
          if (preset) preset.value = 'custom';

          run();
          instance.close();
        },
      })
    : null;

  toggleDate?.addEventListener('click', () => {
    if (preset) preset.value = 'custom';
    if (fp) fp.open();
  });

  preset?.addEventListener('change', () => {
    const v = preset.value;
    if (v === 'custom') { if (fp) fp.open(); return; }
    applyPreset(v);
  });

  // default preset if empty
  if ((!initialFrom || !initialTo) && preset) {
    preset.value = '7';
    applyPreset('7');
  } else if (preset) {
    preset.value = 'custom';
  }

  // init load
  lastSig = filterSignature();
  load().catch(console.error);

  // ===== page tabs =====
  const importTabButtons = Array.from(document.querySelectorAll('[data-import-tab]'));
  const importTabPanels = Array.from(document.querySelectorAll('[data-import-panel]'));

  function activateImportTab(name, updateHash = true){
    const activeName = importTabButtons.some(btn => btn.dataset.importTab === name) ? name : 'data';

    importTabButtons.forEach(btn => {
      const active = btn.dataset.importTab === activeName;
      btn.classList.toggle('active', active);
      btn.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    importTabPanels.forEach(panel => {
      panel.hidden = panel.dataset.importPanel !== activeName;
    });

    if (updateHash) {
      history.replaceState(null, '', `${window.location.pathname}${window.location.search}#${activeName}`);
    }
  }

  importTabButtons.forEach(btn => {
    btn.addEventListener('click', () => activateImportTab(btn.dataset.importTab));
  });

  activateImportTab(window.location.hash.replace('#', ''), false);

})();
</script>
@endpush
