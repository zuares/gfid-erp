@extends('layouts.app')

@section('title', 'Detail Invoice • ' . $invoice->code)

@section('content')
@php
  $fmt = fn($n, $dec = 0) => number_format((float)($n ?? 0), $dec, ',', '.');
  $status = (string)($invoice->status ?? 'draft');
  $shipmentCount = $invoice->shipments?->count() ?? 0;

  $subtotal = (float) ($invoice->subtotal ?? 0);
  $discountTotal = (float) ($invoice->discount_total ?? 0);
  $taxPercent = (float) ($invoice->tax_percent ?? 0);
  $taxAmount = (float) ($invoice->tax_amount ?? 0);
  $grandTotal = (float) ($invoice->grand_total ?? 0);

  $totalMargin = (float) $invoice->lines->sum('margin_total');
@endphp

<div class="wrap">

  {{-- TOPBAR --}}
  <div class="topbar">
    <div class="left">
      <div class="kicker">DETAIL INVOICE</div>
      <div class="title">{{ $invoice->code }}</div>
      <div class="meta">
        <span>Tanggal {{ id_date($invoice->date) }}</span>

        {{-- STATUS --}}
        @if ($status === 'posted')
          <span class="badge b-ok">Posted</span>
        @elseif ($status === 'unpriced')
          <span class="badge b-warn">Unpriced</span>
        @else
          <span class="badge b-draft">Draft</span>
        @endif

        {{-- SHIPMENT COUNT --}}
        @if ($shipmentCount > 0)
          <span class="badge b-info">{{ $shipmentCount }} Shipment</span>
        @else
          <span class="badge b-muted">Belum ada shipment</span>
        @endif
      </div>
    </div>

    <div class="right">
      <div class="actions">
        @if ($status === 'posted')
          <a href="{{ route('sales.shipments.create', $invoice) }}" class="btnx btnx-primary">Buat Shipment</a>
        @endif

        @if ($status !== 'posted')
          <form action="{{ route('sales.invoices.post', $invoice) }}" method="POST"
                onsubmit="return confirm('Post invoice ini? Stok akan keluar saat shipment diposting dari WH-RTS.');">
            @csrf
            <button type="submit" class="btnx btnx-primary">Post</button>
          </form>
        @endif

        <a href="{{ route('sales.invoices.index') }}" class="btnx btnx-ghost">&larr; Kembali</a>
      </div>

      <div class="stamp">
        <div>Dibuat: {{ id_datetime($invoice->created_at) }}</div>
        <div>Update: {{ id_datetime($invoice->updated_at) }}</div>
      </div>
    </div>
  </div>

  {{-- FLASH --}}
  @if (session('success'))
    <div class="alertx alertx-ok">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="alertx alertx-bad">{{ session('error') }}</div>
  @endif
  @if (session('info'))
    <div class="alertx alertx-info">{{ session('info') }}</div>
  @endif

  {{-- INFO --}}
  <div class="cardx">
    <div class="cardx-h">
      <div>
        <div class="kicker">Info Utama</div>
        <div class="hint">Ringkasan customer, gudang, channel, dan catatan.</div>
      </div>
      <div class="pill">
        Margin: <b>{{ $fmt($totalMargin) }}</b>
      </div>
    </div>

    <div class="cardx-b">
      <div class="grid">
        <div class="kv">
          <div class="k">Customer</div>
          <div class="v">{{ $invoice->customer?->name ?? '-' }}</div>
        </div>
        <div class="kv">
          <div class="k">Gudang</div>
          <div class="v">
            {{ $invoice->warehouse?->code ?? '-' }}
            @if($invoice->warehouse?->name)
              <span class="muted">— {{ $invoice->warehouse->name }}</span>
            @endif
          </div>
        </div>
        <div class="kv">
          <div class="k">Store / Channel</div>
          <div class="v">
            @if ($invoice->store)
              {{ $invoice->store->code }} <span class="muted">— {{ $invoice->store->name }}</span>
            @else
              <span class="muted">Tidak diisi</span>
            @endif
          </div>
        </div>
      </div>

      <div class="note">
        <div class="k">Catatan</div>
        @if ($invoice->remarks)
          <div class="v">{!! nl2br(e($invoice->remarks)) !!}</div>
        @else
          <div class="v muted">Tidak ada catatan.</div>
        @endif
      </div>
    </div>
  </div>

  {{-- ITEMS --}}
  <div class="cardx">
    <div class="cardx-h">
      <div>
        <div class="kicker">Items</div>
        <div class="hint">HPP snapshot diambil dari <b>master item (items.hpp)</b>.</div>
      </div>
      <div class="pill">
        Total item: <b>{{ $invoice->lines->count() }}</b>
      </div>
    </div>

    <div class="table-wrap">
      <table class="tablex">
        <thead>
          <tr>
            <th style="width:52px">No</th>
            <th>Item</th>
            <th class="text-end">Qty</th>
            <th class="text-end">Harga</th>
            <th class="text-end">Disc</th>
            <th class="text-end">Subtotal</th>
            <th class="text-end">HPP/unit</th>
            <th class="text-end">HPP total</th>
            <th class="text-end">Margin</th>
          </tr>
        </thead>
        <tbody>
          @forelse($invoice->lines as $i => $line)
            <tr>
              <td class="muted">{{ $i + 1 }}</td>
              <td>
                <div class="code">{{ $line->item?->code ?? '-' }}</div>
                <div class="name muted">{{ $line->item?->name ?? '' }}</div>
              </td>
              <td class="text-end">{{ $fmt($line->qty) }}</td>
              <td class="text-end">{{ $fmt($line->unit_price) }}</td>
              <td class="text-end">{{ $fmt($line->line_discount) }}</td>
              <td class="text-end">{{ $fmt($line->line_total) }}</td>
              <td class="text-end">{{ $fmt($line->hpp_unit_snapshot) }}</td>
              <td class="text-end">{{ $fmt($line->hpp_total_snapshot ?? (($line->hpp_unit_snapshot ?? 0) * ($line->qty ?? 0))) }}</td>
              <td class="text-end fw-semibold">{{ $fmt($line->margin_total) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="empty">Tidak ada item.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="cardx-f">
      <div class="sum">
        <div class="rowx"><span>Subtotal</span><b>{{ $fmt($subtotal) }}</b></div>
        <div class="rowx"><span>Diskon</span><b>{{ $fmt($discountTotal) }}</b></div>
        <div class="rowx"><span>PPN ({{ $fmt($taxPercent, 2) }}%)</span><b>{{ $fmt($taxAmount) }}</b></div>
        <div class="hr"></div>
        <div class="rowx big"><span>Grand Total</span><b>{{ $fmt($grandTotal) }}</b></div>
        <div class="rowx muted"><span>Total Margin</span><b>{{ $fmt($totalMargin) }}</b></div>
      </div>
    </div>
  </div>

  {{-- SHIPMENTS --}}
  <div class="cardx">
    <div class="cardx-h">
      <div>
        <div class="kicker">Shipments</div>
        <div class="hint">Invoice bisa punya beberapa shipment (split pengiriman / kirim ulang).</div>
      </div>
      <div class="pill">
        Total: <b>{{ $shipmentCount }}</b>
      </div>
    </div>

    <div class="table-wrap">
      <table class="tablex">
        <thead>
          <tr>
            <th style="width:52px">No</th>
            <th>Kode</th>
            <th>Tanggal</th>
            <th>Status</th>
            <th>Metode</th>
            <th>No. Resi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($invoice->shipments as $i => $shp)
            <tr>
              <td class="muted">{{ $i + 1 }}</td>
              <td>
                <a class="link" href="{{ route('sales.shipments.show', $shp) }}">
                  {{ $shp->code ?? ($shp->shipment_no ?? 'SHP#'.$shp->id) }}
                </a>
              </td>
              <td>{{ id_date($shp->date) }}</td>
              <td><span class="badge b-info">{{ ucfirst($shp->status) }}</span></td>
              <td>{{ $shp->shipping_method ?? '-' }}</td>
              <td>{{ $shp->tracking_no ?? '-' }}</td>
            </tr>
          @empty
            <tr><td colspan="6" class="empty">Belum ada shipment terkait invoice ini.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection

@push('head')
<style>
  :root{
    --r:14px;
    --b: rgba(148,163,184,.22);
    --muted:#6b7280;
    --shadow: 0 10px 26px rgba(15,23,42,.10), 0 0 0 1px rgba(15,23,42,.03);
  }

  .wrap{max-width:1150px;margin:0 auto;padding:14px 14px 56px;}
  body[data-theme="light"] .wrap{
    background: radial-gradient(circle at top left, rgba(59,130,246,.10) 0, rgba(45,212,191,.08) 26%, #f9fafb 60%);
    border-radius: 18px;
  }
  body[data-theme="dark"] .wrap{
    background: radial-gradient(circle at top left, rgba(15,23,42,.92) 0, #020617 65%);
    border-radius: 18px;
  }

  .topbar{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;margin-bottom:12px;}
  .kicker{font-size:.72rem;letter-spacing:.10em;text-transform:uppercase;color:var(--muted);}
  body[data-theme="dark"] .kicker{color:#9ca3af;}
  .title{font-size:1.15rem;font-weight:800;line-height:1.15;margin-top:2px;}
  .meta{display:flex;flex-wrap:wrap;gap:8px;align-items:center;font-size:.85rem;color:var(--muted);}

  .actions{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end;}
  .stamp{margin-top:8px;font-size:.78rem;color:var(--muted);text-align:right;}

  .btnx{border-radius:999px;padding:.38rem .9rem;border:1px solid rgba(148,163,184,.55);background:transparent;font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;}
  .btnx:hover{background:rgba(226,232,240,.65);}
  body[data-theme="dark"] .btnx:hover{background:rgba(15,23,42,.9);}
  .btnx-primary{background:#2563eb;border-color:#2563eb;color:#eff6ff;box-shadow:0 6px 16px rgba(37,99,235,.25);}
  .btnx-primary:hover{background:#1d4ed8;border-color:#1d4ed8;}
  .btnx-ghost{color:inherit;}

  .badge{border-radius:999px;padding:.16rem .62rem;font-size:.70rem;letter-spacing:.08em;text-transform:uppercase;border:1px solid rgba(148,163,184,.35);}
  .b-draft{background:rgba(251,191,36,.10);color:#92400e;border-color:rgba(245,158,11,.25);}
  .b-warn{background:rgba(249,115,22,.12);color:#9a3412;border-color:rgba(248,150,108,.6);}
  .b-ok{background:rgba(34,197,94,.10);color:#166534;border-color:rgba(34,197,94,.25);}
  .b-info{background:rgba(59,130,246,.12);color:#1d4ed8;border-color:rgba(59,130,246,.4);}
  .b-muted{background:rgba(148,163,184,.10);color:#4b5563;border-color:rgba(148,163,184,.35);}

  body[data-theme="dark"] .b-draft{background:rgba(251,191,36,.25);color:#fef9c3;border-color:rgba(245,158,11,.7);}
  body[data-theme="dark"] .b-warn{background:rgba(248,150,108,.25);color:#ffedd5;border-color:rgba(248,150,108,.8);}
  body[data-theme="dark"] .b-ok{background:rgba(34,197,94,.25);color:#bbf7d0;border-color:rgba(34,197,94,.8);}
  body[data-theme="dark"] .b-info{background:rgba(37,99,235,.30);color:#bfdbfe;border-color:rgba(37,99,235,.9);}
  body[data-theme="dark"] .b-muted{background:rgba(15,23,42,.85);color:#9ca3af;border-color:rgba(148,163,184,.7);}

  .cardx{background:var(--card);border:1px solid var(--b);border-radius:var(--r);box-shadow:var(--shadow);margin-bottom:12px;overflow:hidden;}
  .cardx-h{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;padding:12px 14px;border-bottom:1px solid rgba(148,163,184,.14);}
  .hint{font-size:.82rem;color:var(--muted);margin-top:4px}
  .pill{border-radius:999px;padding:.22rem .75rem;font-size:.80rem;border:1px solid rgba(148,163,184,.35);background:rgba(248,250,252,.96);}
  body[data-theme="dark"] .pill{background:rgba(15,23,42,.98);border-color:rgba(30,64,175,.65);color:#e5e7eb;}

  .cardx-b{padding:12px 14px;}
  .grid{display:grid;grid-template-columns:repeat(3, minmax(0,1fr));gap:10px;}
  @media (max-width: 820px){.grid{grid-template-columns:1fr;}}
  .kv .k{font-size:.78rem;color:var(--muted);margin-bottom:2px}
  .kv .v{font-weight:700}
  .muted{color:var(--muted)}
  body[data-theme="dark"] .muted{color:#9ca3af}
  .note{margin-top:10px;padding-top:10px;border-top:1px dashed rgba(148,163,184,.25)}
  .note .k{font-size:.78rem;color:var(--muted);margin-bottom:4px}

  .table-wrap{overflow:auto;}
  .tablex{width:100%;border-collapse:separate;border-spacing:0;}
  .tablex thead th{
    position:sticky;top:0;
    font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;
    color:var(--muted);
    background:rgba(248,250,252,.96);
    border-bottom:1px solid rgba(148,163,184,.18);
    padding:10px 12px;
    white-space:nowrap;
  }
  body[data-theme="dark"] .tablex thead th{
    background:rgba(15,23,42,.98);
    border-bottom-color:rgba(30,64,175,.60);
    color:#9ca3af;
  }
  .tablex tbody td{
    padding:10px 12px;
    border-bottom:1px solid rgba(148,163,184,.14);
    vertical-align:middle;
    white-space:nowrap;
  }
  body[data-theme="dark"] .tablex tbody td{border-bottom-color:rgba(51,65,85,.85);}
  .code{font-weight:800;font-size:.88rem}
  .name{font-size:.82rem;margin-top:2px}
  .empty{text-align:center;color:var(--muted);padding:18px 12px}

  .link{text-decoration:none;font-weight:800}
  .link:hover{text-decoration:underline}

  .cardx-f{padding:12px 14px;}
  .sum{max-width:420px;margin-left:auto}
  .rowx{display:flex;justify-content:space-between;gap:12px;padding:4px 0;font-size:.90rem}
  .rowx.big{font-size:1rem}
  .hr{height:1px;background:rgba(148,163,184,.25);margin:8px 0}
  .alertx{border-radius:12px;padding:.55rem .8rem;margin:10px 0;font-size:.85rem;border:1px solid rgba(148,163,184,.25)}
  .alertx-ok{background:rgba(34,197,94,.12);border-color:rgba(34,197,94,.25)}
  .alertx-bad{background:rgba(239,68,68,.10);border-color:rgba(239,68,68,.22)}
  .alertx-info{background:rgba(59,130,246,.10);border-color:rgba(59,130,246,.22)}
</style>
@endpush
