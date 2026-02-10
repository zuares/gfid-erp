{{-- resources/views/imports/marketplace_income/show_order.blade.php --}}
@extends('layouts.app')
@section('title','Marketplace Income • Detail Order')

@php
  use Illuminate\Support\Str;

  $fmt0 = fn($n) => number_format((float)($n ?? 0), 0, ',', '.');

  $income   = $income ?? null;
  $shipment = $shipment ?? null;
  $items    = $shipmentItems ?? collect();

  $orderId  = (string)($income->platform_order_id ?? '-');
  $channel  = strtoupper((string)($income->channel ?? '-'));

  $fee    = (float)($income->platform_fee_total ?? 0);
  $refund = (float)($income->refund_total ?? 0);
  $net    = (float)($income->net_payout_actual ?? 0);

  // Subtotal pesanan: default pakai subtotal item (kalau kosong, fallback 0)
  $subtotalPesanan = (float) $items->sum(fn($x) => (float)($x->subtotal ?? 0));
  $totalQty        = (int) $items->sum(fn($x) => (int)($x->qty ?? 0));

  // Jika kamu ingin subtotal pesanan selalu mengikuti formula income:
  // $subtotalPesanan = max(0, $net + $fee + $refund);

  $batchId = (string)($income->import_batch_id ?? '');
@endphp

@push('head')
<style>
  .page{ max-width:1100px; margin:0 auto; padding: 1rem .9rem 4.8rem; }
  @media(min-width:768px){ .page{ padding: 1.1rem 1rem 4.8rem; } }

  :root{
    --line: rgba(148,163,184,.18);
    --line2: rgba(148,163,184,.22);
    --ink: rgba(15,23,42,.92);
    --muted: rgba(100,116,139,1);
    --soft: rgba(148,163,184,.06);
    --shadow: 0 10px 24px rgba(15,23,42,.05);
  }
  body[data-theme="dark"]{
    --ink: rgba(226,232,240,.92);
    --muted: rgba(148,163,184,.85);
    --line: rgba(148,163,184,.14);
    --line2: rgba(148,163,184,.18);
    --soft: rgba(148,163,184,.08);
    --shadow: 0 12px 28px rgba(0,0,0,.35);
  }

  .cardx{ border:1px solid var(--line); border-radius:14px; background: var(--card, #fff); box-shadow: var(--shadow); }
  .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }

  /* Minimal Bootstrap tuning (tanpa ubah warna bootstrap) */
  .table thead th{ font-weight:700; }
  .text-muted-2{ color: var(--muted) !important; }
  .border-soft{ border-color: var(--line) !important; }
</style>
@endpush

@section('content')
<div class="page">

  {{-- Flash --}}
  @if (session('success'))
    <div class="alert alert-success py-2 px-3 mb-3">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger py-2 px-3 mb-3">{{ session('error') }}</div>
  @endif

  {{-- Header minimal --}}
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
      <div class="h5 fw-bold mb-1">Detail Pembayaran</div>
      <div class="text-muted small">
        Order <span class="mono">{{ $orderId }}</span> • {{ $channel }}
        @if(!empty($income?->released_date))
          • {{ $income->released_date }}
        @endif
      </div>
    </div>

    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="{{ route('imports.marketplace_income.index') }}">Kembali</a>

      @if($batchId !== '')
        <a class="btn btn-outline-primary btn-sm" href="{{ route('imports.marketplace_income.show', ['batch' => $batchId]) }}">
          Lihat Batch
        </a>
      @endif
    </div>
  </div>

  <div class="row g-3">
    {{-- LEFT: Items --}}
    <div class="col-12 col-lg-7">
      <div class="cardx">
        <div class="p-3 border-bottom border-soft d-flex justify-content-between align-items-center">
          <div class="fw-bold">Items</div>
          <div class="text-muted small">Total qty: <b>{{ $totalQty }}</b></div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:70px;">No</th>
                <th>SKU</th>
                <th class="text-end" style="width:110px;">Qty</th>
                <th class="text-end" style="width:160px;">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              @forelse($items as $i => $si)
                @php
                  $sku = (string)($si->sku_code ?? $si->sku_parent ?? '-');
                  $qty = (int)($si->qty ?? 0);
                  $sub = (float)($si->subtotal ?? 0);
                @endphp
                <tr>
                  <td class="text-muted">{{ $i + 1 }}</td>
                  <td class="mono fw-semibold">{{ $sku }}</td>
                  <td class="text-end">{{ $qty }}</td>
                  <td class="text-end fw-semibold">Rp{{ $fmt0($sub) }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="p-3 text-muted">Tidak ada item.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="p-3 border-top border-soft d-flex justify-content-between">
          <div class="text-muted small">Subtotal pesanan</div>
          <div class="fw-bold">Rp{{ $fmt0($subtotalPesanan) }}</div>
        </div>
      </div>
    </div>

    {{-- RIGHT: Rincian Penghasilan (simple) --}}
    <div class="col-12 col-lg-5">
      <div class="cardx">
        <div class="p-3 border-bottom border-soft">
          <div class="fw-bold">Rincian Penghasilan</div>
        </div>

        <div class="p-3">
          <div class="d-flex justify-content-between py-2">
            <div class="text-muted-2">Subtotal Pesanan</div>
            <div class="fw-semibold">Rp{{ $fmt0($subtotalPesanan) }}</div>
          </div>

          <div class="d-flex justify-content-between py-2">
            <div class="text-muted-2">Biaya Lainnya</div>
            <div class="fw-semibold">-Rp{{ $fmt0($fee) }}</div>
          </div>

          <div class="d-flex justify-content-between py-2">
            <div class="text-muted-2">Refund Total</div>
            <div class="fw-semibold">-Rp{{ $fmt0($refund) }}</div>
          </div>

          <hr class="my-2 border-soft">

          <div class="d-flex justify-content-between align-items-end pt-2">
            <div class="fw-bold">Estimasi Penghasilan</div>
            <div class="fw-bold fs-4">Rp{{ $fmt0($net) }}</div>
          </div>

          @if(!empty($income?->released_at))
            <div class="text-muted small mt-2">
              Waktu rilis: <span class="mono">{{ $income->released_at }}</span>
            </div>
          @endif
        </div>
      </div>

      {{-- Status match dibuat “user friendly” --}}
      <div class="mt-3">
        @if($shipment)
          <div class="alert alert-success py-2 px-3 mb-0">
            Pembayaran ini sudah terhubung ke data pengiriman.
          </div>
        @else
          <div class="alert alert-warning py-2 px-3 mb-0">
            Pembayaran ini belum terhubung ke data pengiriman.
          </div>
        @endif
      </div>
    </div>
  </div>

</div>
@endsection
