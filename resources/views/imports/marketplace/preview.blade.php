{{-- resources/views/imports/marketplace/preview.blade.php --}}
@extends('layouts.app')
@section('title','Imports • Preview Marketplace Import')

@php
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
    max-width:1220px;
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
</style>
@endpush

@section('content')
<div class="page">

  {{-- =========================
    HEADER
  ========================= --}}
  <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1 fw-bold">Preview Import</h1>
      <div class="text-muted small d-flex flex-wrap gap-1 align-items-center">
        <span class="chip">Channel: <b class="mono">{{ $channelName ?? $channelKey ?? '-' }}</b></span>
        <span class="chip">Store: <b class="mono">{{ $storeName ?? ($storeId ? ('#'.$storeId) : '-') }}</b></span>
        <span class="chip">File: <b class="mono">{{ $sourceFile ?? '-' }}</b></span>

        @if($hasErrors)
          <span class="chip" style="border-color: rgba(239,68,68,.35); background: rgba(239,68,68,.10);">
            Ada error validasi
          </span>
        @else
          <span class="chip" style="border-color: rgba(34,197,94,.35); background: rgba(34,197,94,.10);">
            Siap di-commit
          </span>
        @endif
      </div>
    </div>

    <div class="d-flex gap-2 align-items-center head-actions">
      <a class="btn btn-outline-secondary btn-sm px-3" href="{{ route('imports.marketplace.create') }}">← Ubah File</a>

      <form method="POST" action="{{ route('imports.marketplace.cancel') }}"
            onsubmit="return confirm('Batalkan preview?')" class="d-inline">
        @csrf
        <button class="btn btn-outline-danger btn-sm px-3">Cancel</button>
      </form>

      <form method="POST" action="{{ route('imports.marketplace.commit') }}" class="d-inline">
        @csrf
        <button class="btn btn-success btn-sm px-3" {{ $hasErrors ? 'disabled' : '' }}>
          Commit
        </button>
      </form>
    </div>
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
  <div class="cardx p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="fw-bold">Ringkasan</div>
      <div class="text-muted small">{{ $actionHint }}</div>
    </div>

    <div class="sum-grid">
      <div class="cardx sum-card" style="grid-column: span 3;">
        <div class="sum-title">Rows</div>
        <div class="sum-val">{{ $num($rowsTotal) }}</div>
        <div class="sum-sub">Baris terbaca</div>
      </div>

      <div class="cardx sum-card" style="grid-column: span 3;">
        <div class="sum-title">Orders</div>
        <div class="sum-val">{{ $num($ordersTotal) }}</div>
        <div class="sum-sub">Total shipment/order</div>
      </div>

      <div class="cardx sum-card" style="grid-column: span 3;">
        <div class="sum-title">Items</div>
        <div class="sum-val">{{ $num($itemsTotal) }}</div>
        <div class="sum-sub">Item lines</div>
      </div>

      <div class="cardx sum-card" style="grid-column: span 3;">
        <div class="sum-title">Qty</div>
        <div class="sum-val">{{ $num($qtyTotal) }}</div>
        <div class="sum-sub">Total qty</div>
      </div>

      <div class="cardx sum-card" style="grid-column: span 6;">
        <div class="sum-title">Grand Total</div>
        <div class="sum-val">{{ $money($grandTotal) }}</div>
        <div class="sum-sub">Total nilai transaksi</div>
      </div>

      <div class="cardx sum-card" style="grid-column: span 3;">
        <div class="sum-title">Duplikat Tracking</div>
        <div class="sum-val">{{ $num($dupTracking) }}</div>
        <div class="sum-sub">Duplikat di file</div>
      </div>

      <div class="cardx sum-card" style="grid-column: span 3;">
        <div class="sum-title">Duplikat Order</div>
        <div class="sum-val">{{ $num($dupOrder) }}</div>
        <div class="sum-sub">Duplikat di file</div>
      </div>

      <div class="cardx sum-card" style="grid-column: span 3;">
        <div class="sum-title">Data Baru</div>
        <div class="sum-val">{{ $num($newTotal) }}</div>
        <div class="sum-sub">Shipment baru</div>
      </div>

      <div class="cardx sum-card" style="grid-column: span 3;">
        <div class="sum-title">Akan Di-update</div>
        <div class="sum-val">{{ $num($existingTotal) }}</div>
        <div class="sum-sub">Idempotent import</div>
      </div>
    </div>
  </div>

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
    <div class="cardx p-3 mb-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-bold">Daftar Error ({{ count($importErrors) }})</div>
        <span class="chip" style="border-color: rgba(239,68,68,.35); background: rgba(239,68,68,.10);">
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
    </div>
  @endif

  {{-- =========================
    PREVIEW DATA
  ========================= --}}
  <div class="cardx p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="fw-bold">Preview Data</div>
      <div class="text-muted small">
        {{ count($rows) }} shipment/order ditampilkan
      </div>
    </div>

    @if(empty($rows))
      <div class="text-muted">Tidak ada data untuk ditampilkan.</div>
    @else
      <div class="d-flex flex-column gap-2">
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
  </div>

</div>
@endsection
