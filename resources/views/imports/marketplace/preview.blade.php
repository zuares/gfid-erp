{{-- resources/views/imports/marketplace/preview.blade.php --}}
@extends('layouts.app')
@section('title','Imports • Preview Marketplace Import')

@php
  use Illuminate\Support\Str;

  /* =========================================================
    META (NO legacy vars)
  ========================================================= */
  $channelName = $channel_name ?? null;
  $channelKey  = $channel_key ?? null;
  $channelId   = $channel_id ?? null;

  $storeName   = $store_name ?? null;
  $storeId     = $store_id ?? null;

  $sourceFile  = $source_file ?? null;

  /* =========================================================
    DATA (normalized + stats + errors)
  ========================================================= */
  $rows = $normalized ?? [];
  if (!is_array($rows)) $rows = [];

  $stats = $stats ?? [];
  if (!is_array($stats)) $stats = [];

  $importErrors = $import_errors ?? [];
  if (!is_array($importErrors)) $importErrors = [];
  $hasErrors = !empty($importErrors);

  /* =========================================================
    Helpers
  ========================================================= */
  $money = fn($n) => 'Rp ' . number_format((float)($n ?? 0), 0, ',', '.');
  $num   = fn($n) => number_format((float)($n ?? 0), 0, ',', '.');

  $get = function ($arr, $key, $fallback = null) {
    if (!is_array($arr)) return $fallback;
    return array_key_exists($key, $arr) ? $arr[$key] : $fallback;
  };

  $statusLabel = function ($s) {
    $s = strtolower(trim((string)($s ?? '')));
    return match($s){
      'delivered'   => ['Delivered', 'success'],
      'in_transit'  => ['In Transit', 'primary'],
      'canceled'    => ['Canceled', 'secondary'],
      default       => ['Unknown', 'dark'],
    };
  };

  $fmtDate = function ($v) {
    if (!$v) return '-';
    try { return \Carbon\Carbon::parse($v)->format('d-m-Y'); }
    catch (\Throwable $e) { return (string)$v; }
  };

  /* =========================================================
    SUMMARY (fallback compute from rows)
    - stats boleh kosong, tapi UI wajib terisi
  ========================================================= */
  $rowsTotal   = (int)($stats['rows'] ?? $stats['rows_parsed'] ?? count($rows));
  $ordersTotal = $stats['orders'] ?? $stats['orders_parsed'] ?? null;

  $itemsTotal  = $stats['items'] ?? $stats['item_lines'] ?? $stats['items_parsed'] ?? null;
  $qtyTotal    = $stats['qty'] ?? $stats['sum_qty'] ?? null;
  $grandTotal  = $stats['grand_total'] ?? $stats['sum_grand_total'] ?? null;

  $dupTracking = $stats['dup_tracking'] ?? $stats['duplicate_tracking'] ?? null;
  $dupOrder    = $stats['dup_order'] ?? $stats['duplicate_order'] ?? null;

  // minimal fallback untuk orders
  if ($ordersTotal === null) $ordersTotal = count($rows);

  // compute if missing: items/qty/grand/dup
  $calcItems = 0;
  $calcQty = 0;
  $calcGrand = 0;

  $seenOrder = [];
  $seenTracking = [];
  $dupO = 0;
  $dupT = 0;

  $hasHeaderTotals = false;

  foreach ($rows as $r) {
    if (!is_array($r)) continue;

    $oid = trim((string)($r['platform_order_id'] ?? $r['order_id'] ?? ''));
    if ($oid !== '') {
      if (isset($seenOrder[$oid])) $dupO++;
      else $seenOrder[$oid] = true;
    }

    $trk = trim((string)($r['tracking_no'] ?? $r['tracking'] ?? ''));
    if ($trk !== '') {
      if (isset($seenTracking[$trk])) $dupT++;
      else $seenTracking[$trk] = true;
    }

    if (array_key_exists('grand_total', $r) || array_key_exists('total_qty', $r)) {
      $hasHeaderTotals = true;
    }

    $items = $r['items'] ?? [];
    if (is_array($items)) {
      $calcItems += count($items);

      foreach ($items as $it) {
        if (!is_array($it)) continue;

        $q = (float)($it['qty'] ?? $it['quantity'] ?? 0);
        $calcQty += $q;

        $calcGrand += (float)($it['subtotal'] ?? $it['sub_total'] ?? $it['line_total'] ?? 0);
      }
    }
  }

  // kalau header totals ada (lebih akurat), pakai itu untuk qty & grand
  if ($hasHeaderTotals) {
    $calcQty = 0;
    $calcGrand = 0;
    foreach ($rows as $r) {
      if (!is_array($r)) continue;
      $calcQty += (float)($r['total_qty'] ?? 0);
      $calcGrand += (float)($r['grand_total'] ?? $r['total'] ?? 0);
    }
  }

  if ($itemsTotal === null)  $itemsTotal = $calcItems;
  if ($qtyTotal === null)    $qtyTotal = $calcQty;
  if ($grandTotal === null)  $grandTotal = $calcGrand;

  if ($dupOrder === null)    $dupOrder = $dupO;
  if ($dupTracking === null) $dupTracking = $dupT;

  $newTotal = (int) ($stats['new_shipments'] ?? 0);
  $existingTotal = (int) ($stats['existing_shipments'] ?? 0);
  $warnings = is_array($stats['warnings'] ?? null) ? $stats['warnings'] : [];

  // UI message
  $actionHint = $hasErrors ? 'Perbaiki error sebelum commit.' : 'Cek singkat lalu commit.';
@endphp

@push('head')
<style>
  /* =========================
    Layout (match index)
  ========================= */
  .page{
    max-width:1440px;
    margin:0 auto;
    padding: 1rem .9rem 4.8rem;
  }
  @media(min-width:768px){
    .page{ padding: 1.1rem 1rem 4.8rem; }
  }

  /* =========================
    Tokens (same as index)
  ========================= */
  :root{
    --line: rgba(148,163,184,.18);
    --line2: rgba(148,163,184,.22);
    --ink: rgba(15,23,42,.92);
    --muted: rgba(100,116,139,1);
    --soft: rgba(148,163,184,.06);
    --soft2: rgba(148,163,184,.10);
    --shadow: 0 10px 24px rgba(15,23,42,.05);
  }
  body[data-theme="dark"]{
    --ink: rgba(226,232,240,.92);
    --muted: rgba(148,163,184,.85);
    --line: rgba(148,163,184,.14);
    --line2: rgba(148,163,184,.18);
    --soft: rgba(148,163,184,.08);
    --soft2: rgba(148,163,184,.12);
    --shadow: 0 12px 28px rgba(0,0,0,.35);
  }

  /* =========================
    Card + Chip + Mono
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
  .head-actions .btn{ white-space:nowrap; }

  /* =========================
    Summary grid
  ========================= */
  .sum-grid{
    display:grid;
    grid-template-columns: repeat(12, 1fr);
    gap:.6rem;
  }
  .sum-card{ padding:.9rem; }
  .sum-title{ color: var(--muted); font-size:.8rem; }
  .sum-val{ font-weight:800; color: var(--ink); margin-top:.12rem; }
  .sum-sub{ color: var(--muted); font-size:.8rem; margin-top:.12rem; }
  @media(max-width:992px){ .sum-grid{ grid-template-columns: repeat(6, 1fr); } }
  @media(max-width:520px){ .sum-grid{ grid-template-columns: repeat(2, 1fr); } }

  /* =========================
    Preview list (details)
  ========================= */
  .ship-item{
    border:1px solid var(--line);
    border-radius:14px;
    background: var(--card, #fff);
    overflow:hidden;
  }
  .ship-sum{
    padding:.85rem .95rem;
    cursor:pointer;
    display:flex;
    justify-content:space-between;
    gap:1rem;
    align-items:flex-start;
  }
  .ship-sum:hover{ background: var(--soft); }
  .ship-left{ min-width:0; }
  .ship-right{ text-align:right; white-space:nowrap; }
  .ship-title{ font-weight:800; color: var(--ink); }
  .ship-meta{ margin-top:.35rem; display:flex; flex-wrap:wrap; gap:.35rem; }
  .ship-sub{ color: var(--muted); font-size:.85rem; margin-top:.35rem; }
  .ship-body{ padding:.9rem .95rem; border-top:1px solid var(--line); }

  /* items table */
  .it-table th, .it-table td{ vertical-align:middle; }
  .it-table thead{ background: rgba(148,163,184,.10); }
  body[data-theme="dark"] .it-table thead{ background: rgba(148,163,184,.12); }

  /* error list */
  .err-row{
    display:flex;
    justify-content:space-between;
    gap:.75rem;
    padding:.35rem 0;
    border-bottom:1px dashed rgba(148,163,184,.25);
  }
  .err-row:last-child{ border-bottom:none; }

  /* =========================
    Shared marketplace index language
  ========================= */
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
    color:var(--ink);
    font-size:1.35rem;
    font-weight:900;
    letter-spacing:-.04em;
  }
  .shipment-hero-sub{ max-width:48rem; margin-top:.25rem; color:var(--muted); font-size:.82rem; }
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
  .shipment-badges,.shipment-actions,.shipment-tabs{ display:flex; align-items:center; flex-wrap:wrap; gap:.45rem; }
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
  .shipment-hero .btn-outline-secondary,.shipment-hero .btn-outline-danger{ background:var(--card,#fff); }

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
  .import-tabs{ display:flex; gap:.2rem; }
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
  }
  .import-tab.active{ color:#fff; background:#0f172a; }
  .shipment-tab-meta{ padding:0 .8rem; color:var(--muted); font-size:.72rem; font-weight:700; }

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
  .shipment-kpi-label{ color:var(--muted); font-size:.7rem; font-weight:850; text-transform:uppercase; letter-spacing:.04em; }
  .shipment-kpi-value{ margin-top:.25rem; color:var(--ink); font-size:1.25rem; font-weight:900; letter-spacing:-.03em; }
  .shipment-kpi-sub{ margin-top:.2rem; color:var(--muted); font-size:.72rem; }

  .shipment-card{ border:1px solid var(--line); border-radius:18px; background:var(--card,#fff); box-shadow:var(--shadow); }
  .shipment-card-head{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap; padding:1rem 1rem .75rem; }
  .shipment-card-title{ margin:0; color:var(--ink); font-size:.9rem; font-weight:900; }
  .shipment-card-note{ margin-top:.2rem; color:var(--muted); font-size:.72rem; }
  .shipment-meta-grid{ display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:.65rem; padding:0 1rem 1rem; }
  .shipment-meta-item{ min-width:0; padding:.75rem; border:1px solid var(--line); border-radius:14px; background:var(--soft); }
  .shipment-meta-label{ color:var(--muted); font-size:.65rem; font-weight:850; text-transform:uppercase; letter-spacing:.04em; }
  .shipment-meta-value{ margin-top:.25rem; color:var(--ink); font-size:.95rem; font-weight:900; }

  .preview-list{ display:flex; flex-direction:column; gap:.65rem; padding:0 1rem 1rem; }
  .preview-items-scroll{ overflow:auto; overscroll-behavior:contain; scrollbar-gutter:stable both-edges; }

  @media(max-width:1100px){ .shipment-kpi-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); } .shipment-meta-grid{ grid-template-columns:repeat(3,minmax(0,1fr)); } }
  @media(max-width:820px){
    .shipment-kpi-grid{ grid-template-columns:1fr; }
    .shipment-meta-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); }
    .shipment-tabs-wrap{ border-radius:18px; }
    .shipment-tabs{ flex:1 1 100%; }
    .import-tab{ flex:1 1 auto; justify-content:center; }
    .shipment-tab-meta{ width:100%; padding:.3rem .55rem .5rem; }
  }
  @media(max-width:520px){ .shipment-meta-grid{ grid-template-columns:1fr; } .shipment-actions{ width:100%; } .shipment-actions .btn{ flex:1 1 auto; } }
</style>
@endpush

@section('content')
<div class="page">

  {{-- =========================
    HEADER
  ========================= --}}
  <section class="shipment-hero">
    <div>
      <div class="shipment-eyebrow"><i class="bi bi-truck"></i> Marketplace shipments • Preview</div>
      <h1 class="shipment-hero-title">Preview Import Shipment</h1>
      <div class="shipment-hero-sub">
        Validasi data pengiriman sebelum disimpan ke <span class="mono">mp_shipments</span>. Preview ini masih dry-run dan belum mengubah data utama.
      </div>
      <div class="shipment-badges">
        <span class="shipment-chip"><i class="bi bi-diagram-3"></i> {{ $channelName ?? $channelKey ?? '-' }}</span>
        <span class="shipment-chip"><i class="bi bi-shop"></i> {{ $storeName ?? ($storeId ? ('#'.$storeId) : '-') }}</span>
        <span class="shipment-chip mono"><i class="bi bi-file-earmark-text"></i> {{ Str::limit((string)($sourceFile ?? '-'), 44) }}</span>

        @if($hasErrors)
          <span class="shipment-chip" style="border-color: rgba(239,68,68,.35); background: rgba(239,68,68,.10); color:#b91c1c;">
            Ada error validasi
          </span>
        @else
          <span class="shipment-chip" style="border-color: rgba(34,197,94,.35); background: rgba(34,197,94,.10); color:#15803d;">
            Siap di-commit
          </span>
        @endif
      </div>
    </div>

    <div class="shipment-actions head-actions">
      <a class="btn btn-outline-secondary btn-sm px-3" href="{{ route('imports.marketplace.create') }}"><i class="bi bi-arrow-left"></i> Ubah File</a>

      <form method="POST" action="{{ route('imports.marketplace.cancel') }}"
            onsubmit="return confirm('Batalkan preview?')" class="d-inline">
        @csrf
        <button class="btn btn-outline-danger btn-sm px-3"><i class="bi bi-x-circle"></i> Batalkan</button>
      </form>

      <form method="POST" action="{{ route('imports.marketplace.commit') }}" class="d-inline">
        @csrf
        <button class="btn btn-primary btn-sm px-3" {{ $hasErrors ? 'disabled' : '' }}>
          <i class="bi bi-check2-circle"></i> Commit Import
        </button>
      </form>
    </div>
  </section>

  <div class="shipment-tabs-wrap">
    <div class="import-tabs" role="tablist" aria-label="Navigasi preview marketplace shipments">
      <div class="import-tab active" role="tab" aria-selected="true"><i class="bi bi-table"></i> Preview Data</div>
    </div>
    <div class="shipment-tab-meta">Dry-run • cek rows, KPI, error, dan detail item sebelum commit</div>
  </div>

  @if(session('error'))
    <div class="alert alert-danger py-2">{{ session('error') }}</div>
  @endif
  @if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
  @endif

  {{-- =========================
    SUMMARY
  ========================= --}}
  <div class="shipment-kpi-grid">
    <div class="shipment-kpi order">
      <div class="shipment-kpi-label">Rows</div>
      <div class="shipment-kpi-value">{{ $num($rowsTotal) }}</div>
      <div class="shipment-kpi-sub">Baris terbaca dari file</div>
    </div>
    <div class="shipment-kpi ship">
      <div class="shipment-kpi-label">Orders</div>
      <div class="shipment-kpi-value">{{ $num($ordersTotal) }}</div>
      <div class="shipment-kpi-sub">Shipment/order terdeteksi</div>
    </div>
    <div class="shipment-kpi warn">
      <div class="shipment-kpi-label">Items</div>
      <div class="shipment-kpi-value">{{ $num($itemsTotal) }}</div>
      <div class="shipment-kpi-sub">Item lines • Qty {{ $num($qtyTotal) }}</div>
    </div>
    <div class="shipment-kpi muted">
      <div class="shipment-kpi-label">Grand Total</div>
      <div class="shipment-kpi-value">{{ $money($grandTotal) }}</div>
      <div class="shipment-kpi-sub">Total nilai transaksi</div>
    </div>
  </div>

  <section class="shipment-card mb-3">
    <div class="shipment-card-head">
      <div>
        <h2 class="shipment-card-title"><i class="bi bi-clipboard-data"></i> Audit import</h2>
        <div class="shipment-card-note">{{ $actionHint }} Data baru dan update dipisahkan untuk menjaga import tetap idempotent.</div>
      </div>
      <span class="shipment-chip"><i class="bi bi-shield-check"></i> Dry-run</span>
    </div>
    <div class="shipment-meta-grid">
      <div class="shipment-meta-item"><div class="shipment-meta-label">Data Baru</div><div class="shipment-meta-value">{{ $num($newTotal) }}</div></div>
      <div class="shipment-meta-item"><div class="shipment-meta-label">Akan Di-update</div><div class="shipment-meta-value">{{ $num($existingTotal) }}</div></div>
      <div class="shipment-meta-item"><div class="shipment-meta-label">Duplikat Tracking</div><div class="shipment-meta-value">{{ $num($dupTracking) }}</div></div>
      <div class="shipment-meta-item"><div class="shipment-meta-label">Duplikat Order</div><div class="shipment-meta-value">{{ $num($dupOrder) }}</div></div>
      <div class="shipment-meta-item"><div class="shipment-meta-label">Sample</div><div class="shipment-meta-value">{{ $num(count($rows)) }} rows</div></div>
    </div>
  </section>

  @if(!empty($warnings))
    <div class="alert alert-warning py-2 mb-3">
      <strong>Catatan:</strong>
      <ul class="mb-0 ps-3">
        @foreach($warnings as $warning)
          <li>{{ $warning }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- =========================
    ERROR LIST
  ========================= --}}
  @if($hasErrors)
    <section class="shipment-card p-3 mb-3">
      <div class="shipment-card-head px-0 pt-0">
        <div class="shipment-card-title">Daftar Error ({{ count($importErrors) }})</div>
        <span class="shipment-chip" style="border-color: rgba(239,68,68,.35); background: rgba(239,68,68,.10); color:#b91c1c;">
          Commit dinonaktifkan
        </span>
      </div>

      <div class="text-muted small mb-2">Perbaiki file lalu upload ulang.</div>

      <div class="d-flex flex-column">
        @foreach($importErrors as $err)
          @php
            $key = is_array($err) ? ($err['key'] ?? $err['id'] ?? $err['tracking_no'] ?? $err['order_id'] ?? '-') : '-';
            $msg = is_array($err) ? ($err['message'] ?? $err['error'] ?? json_encode($err)) : (string)$err;
          @endphp
          <div class="err-row">
            <div class="mono">{{ $key }}</div>
            <div class="text-muted small" style="flex:1; text-align:right;">{{ $msg }}</div>
          </div>
        @endforeach
      </div>
    </section>
  @endif

  {{-- =========================
    PREVIEW DATA
  ========================= --}}
  <section class="shipment-card">
    <div class="shipment-card-head">
      <div>
        <h2 class="shipment-card-title"><i class="bi bi-table"></i> Preview Data</h2>
        <div class="shipment-card-note">Buka setiap order untuk memeriksa tracking, status, nilai, dan item sebelum commit.</div>
      </div>
      <span class="shipment-card-note">{{ count($rows) }} shipment/order ditampilkan</span>
    </div>

    @if(empty($rows))
      <div class="text-muted">Tidak ada data untuk ditampilkan.</div>
    @else
      <div class="preview-list">
        @foreach($rows as $i => $r)
          @php
            if (!is_array($r)) $r = [];

            $orderId   = $get($r, 'platform_order_id', $get($r, 'order_id', '-'));
            $shipId    = $get($r, 'platform_shipment_id', $get($r, 'shipment_id', null));
            $tracking  = $get($r, 'tracking_no', $get($r, 'tracking', null));
            $status    = $get($r, 'status_norm', $get($r, 'status', 'unknown'));
            $date      = $get($r, 'order_created_at', $get($r, 'order_date', null));
            $qty       = $get($r, 'total_qty', $get($r, 'qty', null));
            $grand     = $get($r, 'grand_total', $get($r, 'total', null));

            $items     = $get($r, 'items', []);
            if (!is_array($items)) $items = [];

            [$stText, $tone] = $statusLabel($status);
          @endphp

          <details class="ship-item" {{ $i < 1 ? 'open' : '' }}>
            <summary class="ship-sum">
              <div class="ship-left">
                <div class="ship-title mono">{{ $orderId }}</div>

                <div class="ship-meta">
                  <span class="chip">Ch: <span class="mono">{{ $channelKey ?? '-' }}</span></span>

                  @if($shipId)
                    <span class="chip mono">Ship: {{ $shipId }}</span>
                  @endif

                  @if($tracking)
                    <span class="chip mono">{{ $tracking }}</span>
                  @endif

                  <span class="chip">
                    <span class="badge bg-{{ $tone }}">{{ $stText }}</span>
                  </span>
                </div>

                <div class="ship-sub">
                  Tanggal: <span class="mono">{{ $fmtDate($date) }}</span>
                  @if($storeName || $storeId)
                    • Store: <span class="mono">{{ $storeName ?? ('#'.$storeId) }}</span>
                  @endif
                </div>
              </div>

              <div class="ship-right">
                <div class="fw-bold">{{ $grand !== null ? $money($grand) : '—' }}</div>
                <div class="text-muted small">Qty {{ $qty !== null ? $num($qty) : '—' }}</div>
                <div class="text-muted small">{{ count($items) }} item line</div>
              </div>
            </summary>

            <div class="ship-body">
              <div class="fw-bold mb-2">Items</div>

              @if(empty($items))
                <div class="text-muted">Tidak ada item.</div>
              @else
                <div class="table-responsive">
                  <table class="table table-sm it-table mb-0 align-middle">
                    <thead>
                      <tr>
                        <th style="width:220px;">SKU</th>
                        <th>Nama</th>
                        <th class="text-end" style="width:110px;">Qty</th>
                        <th class="text-end" style="width:160px;">Subtotal</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($items as $it)
                        @php
                          if (!is_array($it)) $it = [];
                          $sku = $it['sku'] ?? $it['sku_code'] ?? $it['item_sku'] ?? '-';
                          $nm  = $it['name'] ?? $it['product_name'] ?? $it['item_name'] ?? '';
                          $q   = $it['qty'] ?? $it['quantity'] ?? null;
                          $sub = $it['subtotal'] ?? $it['sub_total'] ?? $it['line_total'] ?? null;
                        @endphp
                        <tr>
                          <td class="mono fw-bold">{{ $sku }}</td>
                          <td>{{ $nm }}</td>
                          <td class="text-end">{{ $q !== null ? $num($q) : '—' }}</td>
                          <td class="text-end fw-bold">{{ $sub !== null ? $money($sub) : '—' }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif

              <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small">
                  Status: <span class="badge bg-{{ $tone }}">{{ $stText }}</span>
                </div>
                <div class="text-end">
                  <div class="text-muted small">Order Total</div>
                  <div class="fw-bold">{{ $grand !== null ? $money($grand) : '—' }}</div>
                </div>
              </div>
            </div>
          </details>
        @endforeach
      </div>
    @endif
  </section>

</div>
@endsection
