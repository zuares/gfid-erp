{{-- resources/views/imports/marketplace/show.blade.php --}}
@extends('layouts.app')
@section('title','Imports • Marketplace Shipment')

@php
  /** @var \App\Models\MpShipment|null $s */
  $s = $s ?? null;

  $money = fn($n) => 'Rp ' . number_format((float)($n ?? 0), 0, ',', '.');
  $dt = function ($v, $fmt='d M Y') {
    if (!$v) return '-';
    try { return \Carbon\Carbon::parse($v)->timezone('Asia/Jakarta')->format($fmt); }
    catch (\Throwable $e) { return (string)$v; }
  };

  $statusChip = function($st){
    return match((string)$st){
      'delivered'   => ['Delivered','chip-ok'],
      'in_transit'  => ['In Transit','chip-info'],
      'canceled'    => ['Canceled','chip-danger'],
      default       => ['Unknown','chip-muted'],
    };
  };
@endphp

@push('head')
<style>
  .page{ max-width:1020px; margin:0 auto; padding:1rem .9rem 5.2rem; }
  @media(min-width:768px){ .page{ padding:1.1rem 1rem 5.2rem; } }

  .cardx{
    background: var(--card, #fff);
    border:1px solid rgba(148,163,184,.18);
    border-radius:16px;
    box-shadow:0 10px 24px rgba(15,23,42,.06), 0 0 0 1px rgba(15,23,42,.02);
  }
  .pad{ padding:1rem; }

  .muted{ color: rgba(100,116,139,1); }
  .soft{ color: rgba(100,116,139,.9); font-weight:500; }
  .softer{ color: rgba(100,116,139,.75); font-size:.82rem; }

  .mono{ font-family: ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; }

  .chip{
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.22rem .55rem; border-radius:999px;
    border:1px solid rgba(148,163,184,.28);
    background: rgba(148,163,184,.08);
    font-size:.78rem;
  }
  .chip-ok{ border-color: rgba(16,185,129,.35); background: rgba(16,185,129,.10); color: rgba(5,150,105,1); }
  .chip-info{ border-color: rgba(59,130,246,.35); background: rgba(59,130,246,.10); color: rgba(37,99,235,1); }
  .chip-danger{ border-color: rgba(239,68,68,.35); background: rgba(239,68,68,.10); color: rgba(220,38,38,1); }
  .chip-muted{ border-color: rgba(148,163,184,.28); background: rgba(148,163,184,.08); color: rgba(100,116,139,1); }

  .hero{ display:flex; justify-content:space-between; gap:1rem; }
  .title{ font-size:1.2rem; font-weight:900; margin:0; }

  .kv{
    display:grid; grid-template-columns:1fr; gap:.55rem; margin-top:.85rem;
  }
  @media(min-width:860px){ .kv{ grid-template-columns:repeat(3,1fr);} }
  .kv .cell{
    padding:.65rem .75rem; border-radius:14px;
    border:1px solid rgba(148,163,184,.16);
    background: rgba(148,163,184,.04);
  }
  .kv .k{ font-size:.78rem; color: rgba(100,116,139,1); }
  .kv .v{ font-weight:800; margin-top:.15rem; }

  table{ width:100%; border-collapse:separate; border-spacing:0; }
  thead th{
    font-size:.72rem; text-transform:uppercase; letter-spacing:.06em;
    color: rgba(100,116,139,1);
    padding:.7rem .85rem;
    border-bottom:1px solid rgba(148,163,184,.2);
    background: rgba(148,163,184,.06);
  }
  tbody td{
    padding:.78rem .85rem;
    border-bottom:1px solid rgba(148,163,184,.14);
    vertical-align:middle;
  }

  .nobadge{
    display:inline-flex; align-items:center; justify-content:center;
    min-width:28px; height:22px;
    padding:0 .4rem; border-radius:999px;
    border:1px solid rgba(148,163,184,.22);
    background: rgba(148,163,184,.08);
    font-size:.78rem; color: rgba(100,116,139,1); font-weight:800;
  }

  /* Mobile */
  .show-sm{ display:none; }
  .hide-sm{ display:table-cell; }
  @media(max-width:820px){
    thead{ display:none; }
    .hide-sm{ display:none; }
    .show-sm{ display:block; }
    tbody tr{ display:block; border-bottom:1px solid rgba(148,163,184,.14); }
    tbody td{ display:block; border-bottom:none; padding:.7rem .85rem; }

    .mgrid{
      display:grid;
      grid-template-columns:44px 1fr auto;
      align-items:center; gap:.6rem;
    }
    .mno{
      width:44px; height:28px;
      display:flex; align-items:center; justify-content:center;
      border-radius:10px;
      border:1px solid rgba(148,163,184,.18);
      background: rgba(148,163,184,.06);
      font-size:.8rem; font-weight:900;
      color: rgba(100,116,139,1);
    }
    .msku{
      font-weight:900;
      font-family: ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .mright{ text-align:right; }
    .mamt{ font-weight:900; }
    .mqty{ font-size:.82rem; color: rgba(100,116,139,1); }
  }
</style>
@endpush

@section('content')
<div class="page">

  <div class="d-flex justify-content-between mb-3">
    <div>
      <h1 class="h4 fw-bold mb-1">Shipment</h1>
      <div class="muted small">Detail <span class="mono">mp_shipments</span></div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('imports.marketplace.index') }}">← Kembali</a>
  </div>

  @if(!$s)
    <div class="alert alert-danger">Data tidak ditemukan.</div>
    @php return; @endphp
  @endif

  @php [$stText,$stTone] = $statusChip($s->status_norm); @endphp

  {{-- Header --}}
  <div class="cardx pad mb-3">
    <div class="hero">
      <div>
        <h2 class="title mono">{{ $s->platform_order_id }}</h2>
        <div class="muted mt-1">
          <span class="chip">Ch: {{ $s->channel }}</span>
          <span class="chip">Store: {{ $s->store->name ?? '-' }}</span>
          <span class="chip {{ $stTone }}">{{ $stText }}</span>
        </div>
      </div>
      <div class="text-end">
        <div class="muted small">Grand total</div>
        <div class="fw-bold">{{ $money($s->grand_total) }}</div>
        <div class="muted small">Qty {{ (int)$s->total_qty }}</div>
      </div>
    </div>

    <div class="kv">
      <div class="cell"><div class="k">Tracking</div><div class="v mono">{{ $s->tracking_no ?: '-' }}</div></div>
      <div class="cell"><div class="k">Order created</div><div class="v">{{ $dt($s->order_created_at) }}</div></div>
      <div class="cell"><div class="k">Shipped → Delivered</div><div class="v">{{ $dt($s->shipped_at) }} → {{ $dt($s->delivered_at) }}</div></div>
    </div>
  </div>

  {{-- Items --}}
  <div class="cardx">
    <div class="pad d-flex justify-content-between">
      <div class="fw-bold">Items</div>
      <div class="muted small">{{ $s->items->count() }} baris</div>
    </div>

    <table>
      <thead>
        <tr>
          <th style="width:70px;text-align:right">No</th>
          <th style="width:240px">SKU</th>
          <th>Nama</th>
          <th style="width:90px;text-align:right">Qty</th>
          <th style="width:160px;text-align:right">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        @foreach($s->items as $i => $it)
        <tr>
          {{-- Mobile --}}
          <td class="show-sm">
            <div class="mgrid">
              <div class="mno">{{ $i+1 }}</div>
              <div class="msku">{{ $it->sku_code ?? '-' }}</div>
              <div class="mright">
                <div class="mamt">{{ $money($it->subtotal) }}</div>
                <div class="mqty">Qty {{ (int)$it->qty }}</div>
              </div>
            </div>
          </td>

          {{-- Desktop --}}
          <td class="hide-sm text-end"><span class="nobadge">{{ $i+1 }}</span></td>
          <td class="hide-sm mono fw-bold">{{ $it->sku_code ?? '-' }}</td>

          {{-- 👉 NAMA DIBUAT SOFT --}}
          <td class="hide-sm">
            <div class="soft">{{ $it->product_name ?? '-' }}</div>
            @if($it->variant_name)
              <div class="softer">{{ $it->variant_name }}</div>
            @endif
          </td>

          <td class="hide-sm text-end">{{ (int)$it->qty }}</td>
          <td class="hide-sm text-end fw-bold">{{ $money($it->subtotal) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

</div>
@endsection
