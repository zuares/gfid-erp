{{-- resources/views/purchasing/purchase_receipts/show.blade.php --}}
@extends('layouts.app')

@section('title', 'GRN ' . $receipt->code)

@push('head')
<style>
  .grn-show-page{ min-height:100vh; }
  .grn-show-page .page-wrap{ max-width:1150px; margin-inline:auto; padding:1rem 1rem 4rem; }

  body[data-theme="light"] .grn-show-page .page-wrap{
    background: radial-gradient(circle at top left,
      rgba(59,130,246,.12) 0,
      rgba(45,212,191,.10) 26%,
      #f9fafb 60%);
  }

  .grn-show-page .card-main{
    background: var(--card);
    border-radius: 16px;
    border: 1px solid rgba(148,163,184,.35);
    box-shadow: 0 10px 30px rgba(15,23,42,.10), 0 0 0 1px rgba(148,163,184,.08);
  }
  .grn-show-page .card-soft{
    background: color-mix(in srgb, var(--card) 94%, var(--bg) 6%);
    border-radius: 16px;
    border: 1px solid var(--line);
  }

  .mono{
    font-variant-numeric: tabular-nums;
    font-family: ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono";
  }

  .grn-show-page .show-header-title{ font-size:1.35rem; font-weight:600; }
  .grn-show-page .show-header-subtitle{ font-size:.8rem; color:var(--muted); }
  .grn-show-page .show-header-pill{
    font-size:.75rem; border-radius:999px; padding:.16rem .7rem;
    border:1px solid rgba(148,163,184,.55);
    background: color-mix(in srgb, var(--card) 80%, var(--bg) 20%);
  }
  .grn-show-page .show-header-status{ font-size:.78rem; border-radius:999px; padding:.14rem .7rem; }

  .grn-show-page .section-title{
    font-size:.86rem; text-transform:uppercase; letter-spacing:.08em; color:var(--muted);
  }
  .grn-show-page dl.small dt{ color:var(--muted); }
  .grn-show-page .card-soft .card-body{ padding:1rem 1.2rem 1.15rem; }
  .grn-show-page .card-main .card-header{ padding:.6rem 1.2rem; background:transparent; border-bottom-color:var(--line); }
  .grn-show-page .card-main .card-body{ padding:.5rem .6rem .7rem; }
  .grn-show-page .summary-hr{ border-top-color:var(--line); opacity:1; }

  /* DETAIL TABLE */
  @media (min-width: 992px){
    .grn-detail-wrapper{ max-height:60vh; overflow-y:auto; overflow-x:hidden; }
    .grn-detail-wrapper::-webkit-scrollbar{ width:6px; height:6px; }
    .grn-detail-wrapper::-webkit-scrollbar-thumb{ background: color-mix(in srgb, var(--muted) 60%, transparent); border-radius:999px; }
    .grn-detail-wrapper::-webkit-scrollbar-track{ background:transparent; }
  }
  @media (max-width: 991.98px){
    .grn-detail-wrapper{ max-height:none; overflow-x:auto; overflow-y:auto; }
  }

  .grn-show-page .table thead th{
    background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
    border-bottom-color: var(--line);
    font-size:.78rem; text-transform:uppercase; letter-spacing:.04em;
    padding-top:.55rem; padding-bottom:.55rem; white-space:nowrap;
  }
  .grn-show-page tbody.small td{
    border-bottom-color: var(--line);
    vertical-align: middle;
    padding-top:.5rem; padding-bottom:.5rem;
    font-size:.8rem;
  }
  .grn-show-page tfoot.table-light td{
    border-top-color: var(--line);
    padding-top:.55rem; padding-bottom:.55rem;
  }
  .grn-show-page .lot-badge{ border-radius:999px; font-size:.75rem; }
  .grn-show-page .cell-item-name{ font-size:.84rem; }
  .grn-show-page .cell-item-code{ font-size:.78rem; }
  .grn-show-page .lot-extra{ font-size:.75rem; }

  .grn-show-page .th-full{ display:inline; }
  .grn-show-page .th-abbr{ display:none; }
  .grn-show-page .val-full{ display:inline; }
  .grn-show-page .val-mobile{ display:none; }

  @media (max-width: 767.98px){
    html,body{ max-width:100%; overflow-x:hidden; }
    .grn-show-page{ overflow-x:hidden; }
    .grn-show-page .page-wrap{ padding-inline:.85rem; }

    .grn-show-page .show-header{ flex-direction:column; align-items:flex-start; gap:.4rem; }
    .grn-show-page .show-header-title{ font-size:1.15rem; }
    .grn-show-page .show-header-actions{
      width:100%; display:flex; flex-wrap:wrap; gap:.35rem;
    }
    .grn-show-page .show-header-actions .btn{ flex:1 1 auto; }

    .grn-show-page .table thead th{ font-size:.7rem; padding-top:.4rem; padding-bottom:.4rem; }
    .grn-show-page tbody.small td{ font-size:.76rem; padding-top:.4rem; padding-bottom:.4rem; }

    .grn-show-page .cell-item-name{ display:none; }
    .grn-show-page .cell-item-code{ font-size:.8rem; color:var(--text); font-weight:600; }
    .grn-show-page .lot-extra{ display:none; }

    .grn-show-page .th-full{ display:none; }
    .grn-show-page .th-abbr{ display:inline; }
    .grn-show-page .val-full{ display:none; }
    .grn-show-page .val-mobile{ display:inline; }
  }

  /* RETURN button */
  .btn-return{
    border-radius:999px;
    border:1px solid rgba(245,158,11,.35);
    background: color-mix(in srgb, rgba(245,158,11,.18) 70%, var(--card) 30%);
    color: color-mix(in srgb, #92400e 80%, var(--text) 20%);
  }
  body[data-theme="dark"] .btn-return{
    color: color-mix(in srgb, #fbbf24 80%, var(--text) 20%);
    border-color: rgba(245,158,11,.35);
    background: rgba(245,158,11,.14);
  }
  .btn-return:hover{ filter:brightness(1.02); }

  /* GRAND TOTAL highlight */
  .grand-card{
    border-radius: 16px;
    border: 1px solid rgba(59,130,246,.22);
    background: color-mix(in srgb, rgba(59,130,246,.10) 55%, var(--card) 45%);
  }
  .grand-amt{ font-size: 1.35rem; font-weight: 800; letter-spacing: .2px; }
  .grand-pill{
    border-radius:999px;
    border:1px solid rgba(148,163,184,.35);
    background: color-mix(in srgb, var(--card) 82%, var(--bg) 18%);
    padding: .22rem .6rem;
    font-size: .75rem;
  }

  /* Return list pills */
  .ret-pill{
    border-radius: 999px;
    padding: .18rem .6rem;
    border: 1px solid rgba(148,163,184,.35);
    background: color-mix(in srgb, var(--card) 88%, var(--bg) 12%);
    font-size: .74rem;
  }
</style>
@endpush

@section('content')
@php
  $user = auth()->user();
  $role = strtolower((string)($user->role ?? ''));

  $isOwner = $user?->isOwner() ?? false;
  $isAdmin = $role === 'admin';
  $canSeeMoney = $isOwner;          // hanya owner
  $canManage = $isOwner || $isAdmin;

  $isDraft = $receipt->status === 'draft';
  $isPosted = $receipt->status === 'posted';

  // ===== ROUTE SAFE CHECK =====
  $router = app('router');

  $returnCreateRouteName = null;
  foreach ([
    'purchasing.grn.returns.create',
    'purchasing.purchasing.grn.returns.create',
  ] as $nm) {
    if ($router->has($nm)) { $returnCreateRouteName = $nm; break; }
  }

  $returnShowRouteName = $router->has('purchasing.purchase_returns.show')
    ? 'purchasing.purchase_returns.show'
    : null;

  // ===== RETURNS LIST (robust) =====
  $returns = collect();
  if (isset($receipt->purchaseReturns)) $returns = $receipt->purchaseReturns;
  elseif (isset($receipt->returns)) $returns = $receipt->returns;

  $returns = $returns instanceof \Illuminate\Support\Collection ? $returns : collect($returns);

  $hasAnyReturn = $returns->count() > 0;
  $postedReturns = $returns->where('status','posted')->where('voided_at', null);
  $draftReturns  = $returns->where('status','draft')->where('voided_at', null);
  $activeReturns = $postedReturns->merge($draftReturns); // non-voided only

  // ✅ rule aman: unpost hanya jika belum pernah return
  $canUnpostSafely = $isPosted && $canManage && !$hasAnyReturn;

  // Cek apakah harga sudah ada (untuk warning admin)
  // Cek: grand_total GRN > 0, atau minimal 1 PO line punya harga > 0
  $grnHasPrice = (float) ($receipt->grand_total ?? 0) > 0;
  if (!$grnHasPrice && $isDraft && $isAdmin) {
      $poId = $receipt->purchase_order_id ?? null;
      if ($poId) {
          $grnHasPrice = \Illuminate\Support\Facades\DB::table('purchase_order_lines')
              ->where('purchase_order_id', $poId)
              ->where('unit_price', '>', 0)
              ->exists();
      }
  }

  // Return yang “utama” untuk CTA:
  $primaryDraftReturn = $draftReturns->sortByDesc('id')->first();
  $primaryAnyReturn = $activeReturns->sortByDesc('id')->first(); // hanya non-voided
@endphp

<div class="grn-show-page">
  <div class="page-wrap">

    {{-- HEADER --}}
    <div class="show-header d-flex justify-content-between align-items-start mb-3 gap-2">
      <div class="d-flex flex-column gap-1">
        <div class="d-flex flex-wrap align-items-center gap-2">
          <h1 class="mb-0 show-header-title">Goods Receipt</h1>

          {{-- STATUS BADGE --}}
          @if ($isPosted)
            <span class="show-header-status bg-success-subtle text-success border border-success-subtle">Posted</span>
          @elseif ($isDraft)
            <span class="show-header-status bg-warning-subtle text-warning border border-warning-subtle">Draft</span>
          @else
            <span class="show-header-status bg-secondary-subtle text-secondary border border-secondary-subtle">
              {{ ucfirst($receipt->status) }}
            </span>
          @endif

          <span class="show-header-pill d-none d-sm-inline">
            {{ $receipt->lines->count() }} baris barang diterima
          </span>

          {{-- badge return --}}
          @if($isPosted && $hasAnyReturn)
            <span class="grand-pill">
              Return: {{ $draftReturns->count() }} draft • {{ $postedReturns->count() }} posted
            </span>
          @endif
        </div>

        <div class="show-header-subtitle">
          <span>Kode: <span class="fw-semibold mono">{{ $receipt->code }}</span></span>

          @if ($receipt->date)
            <span class="mx-2">•</span><span>Tanggal: {{ $receipt->date->format('Y-m-d') }}</span>
          @endif

          @if ($receipt->updated_at)
            <span class="mx-2 d-none d-sm-inline">•</span>
            <span class="d-none d-sm-inline">Update: {{ $receipt->updated_at->format('Y-m-d H:i') }}</span>
          @endif
        </div>
      </div>

      {{-- ACTIONS --}}
      <div class="show-header-actions d-flex align-items-center gap-2">
        <a href="{{ route('purchasing.purchase_receipts.index') }}" class="btn btn-outline-secondary btn-sm">
          &larr; Kembali
        </a>

        {{-- POSTED: RETURN CTA --}}
        @if ($isPosted && $canManage)

          {{-- Kalau ada draft return -> tombolnya jadi "Lanjutkan Return" --}}
          @if ($primaryDraftReturn && $returnShowRouteName)
            <a href="{{ route($returnShowRouteName, $primaryDraftReturn->id) }}"
               class="btn btn-return btn-sm"
               onclick="return confirm('Sudah ada RETURN draft untuk GRN ini.\n\nBuka draft return untuk dilanjutkan?');">
              Lanjutkan Return
            </a>

          {{-- Kalau belum ada draft, tapi ada return aktif (non-void) -> tombol "Lihat Return" --}}
          @elseif ($primaryAnyReturn && $returnShowRouteName)
            <a href="{{ route($returnShowRouteName, $primaryAnyReturn->id) }}"
               class="btn btn-outline-secondary btn-sm">
              Lihat Return
            </a>

          {{-- Kalau semua return sudah di-void / belum pernah return -> bisa buat return baru --}}
          @elseif ($returnCreateRouteName)
            <form action="{{ route($returnCreateRouteName, $receipt->id) }}" method="POST"
                  onsubmit="return confirm('Buat draft RETURN dari GRN ini?\n\n• Membuat dokumen return (draft)\n• Isi qty lalu POST return untuk mengurangi stok');">
              @csrf
              <button type="submit" class="btn btn-return btn-sm">
                Return
              </button>
            </form>
          @endif
        @endif

        {{-- DRAFT actions --}}
        @if ($isDraft && $canManage)
          <a href="{{ route('purchasing.purchase_receipts.edit', $receipt->id) }}" class="btn btn-primary btn-sm">Edit</a>
          @if ($isAdmin && !$grnHasPrice)
            {{-- Admin: harga belum ada di PO → tombol disabled + tooltip --}}
            <button type="button" class="btn btn-success btn-sm disabled"
                    title="Harga belum diisi di PO. Hubungi owner."
                    style="opacity:.55; cursor:not-allowed;">
                Post GRN
            </button>
          @else
            <form action="{{ route('purchasing.purchase_receipts.post', $receipt->id) }}" method="POST"
                  onsubmit="return confirm('Post GRN ini?\n\n• Stok akan bertambah\n• Jurnal akan tercatat');">
              @csrf
              <button type="submit" class="btn btn-success btn-sm">Post GRN</button>
            </form>
          @endif
        @endif

        {{-- UNPOST only if safe --}}
        @if ($canUnpostSafely)
          <form action="{{ route('purchasing.purchase_receipts.unpost', $receipt->id) }}" method="POST"
                onsubmit="return confirm('UNPOST GRN ini?\n\n• Stok akan dibalik (stock-out)\n• Jurnal GRN akan di-void\n\nLanjutkan?');">
            @csrf
            <button type="submit" class="btn btn-danger btn-sm">Unpost</button>
          </form>
        @endif
      </div>
    </div>

    {{-- ALERTS --}}
    @if (session('success'))
      <div class="alert alert-success py-2 mb-3">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div class="alert alert-danger py-2 mb-3">{{ session('error') }}</div>
    @endif

    {{-- WARNING admin: belum bisa post, tanpa menyebut harga --}}
    @if ($isDraft && $isAdmin && !$grnHasPrice)
      <div class="alert mb-3 d-flex align-items-start gap-2"
           style="background:var(--bs-warning-bg-subtle,#fff3cd);border:1px solid var(--bs-warning-border-subtle,#ffc107);border-radius:10px;padding:.75rem 1rem;">
        <span style="font-size:1.15rem;line-height:1.3;">⚠️</span>
        <div style="font-size:.875rem;">
          <strong>GRN ini belum bisa di-post.</strong>
          Hubungi owner untuk menyelesaikan dokumen ini.
        </div>
      </div>
    @endif

    {{-- WARNING owner: grand_total = 0 pada draft GRN --}}
    @if ($isDraft && $isOwner && (float)($receipt->grand_total ?? 0) <= 0)
      <div class="alert mb-3 d-flex align-items-start gap-2"
           style="background:var(--bs-warning-bg-subtle,#fff3cd);border:1px solid var(--bs-warning-border-subtle,#ffc107);border-radius:10px;padding:.75rem 1rem;">
        <span style="font-size:1.15rem;line-height:1.3;">⚠️</span>
        <div style="font-size:.875rem;">
          <strong>Harga belum diisi.</strong>
          Grand total GRN ini masih Rp 0. Silakan edit GRN atau isi harga di PO terkait sebelum melakukan posting.
        </div>
      </div>
    @endif

    <div class="row g-3 mb-3">

      {{-- 1) INFORMASI DOKUMEN --}}
      <div class="col-12 col-lg-6 order-1 order-lg-1">
        <div class="card-soft h-100">
          <div class="card-body">
            <h6 class="section-title mb-3">Informasi Dokumen</h6>

            <dl class="row mb-0 small">
              <dt class="col-sm-4">Kode</dt>
              <dd class="col-sm-8 mono">{{ $receipt->code }}</dd>

              <dt class="col-sm-4">Tanggal</dt>
              <dd class="col-sm-8">{{ $receipt->date?->format('Y-m-d') ?? '-' }}</dd>

              <dt class="col-sm-4">Supplier</dt>
              <dd class="col-sm-8">
                @if ($receipt->supplier)
                  <div class="fw-semibold">{{ $receipt->supplier->name }}</div>
                  <div class="text-muted small">{{ $receipt->supplier->code }}</div>
                @else
                  <span class="text-muted">-</span>
                @endif
              </dd>

              <dt class="col-sm-4">Gudang</dt>
              <dd class="col-sm-8">
                @if ($receipt->warehouse)
                  <div class="fw-semibold">{{ $receipt->warehouse->name }}</div>
                  <div class="text-muted small">{{ $receipt->warehouse->code }}</div>
                @else
                  <span class="text-muted">-</span>
                @endif
              </dd>

              @if ($receipt->purchase_order_id && $receipt->purchaseOrder)
                <dt class="col-sm-4">Dari PO</dt>
                <dd class="col-sm-8">
                  <a href="{{ route('purchasing.purchase_orders.show', $receipt->purchase_order_id) }}" class="text-decoration-none">
                    {{ $receipt->purchaseOrder->code }}
                  </a>
                </dd>
              @endif

              @if ($receipt->surat_jalan_no)
              <dt class="col-sm-4">No. Surat Jalan</dt>
              <dd class="col-sm-8 mono">{{ $receipt->surat_jalan_no }}</dd>
              @endif

              <dt class="col-sm-4">Catatan</dt>
              <dd class="col-sm-8">{{ $receipt->notes ?: '-' }}</dd>
            </dl>

            {{-- RETURNS RELATED --}}
            @if($isPosted)
              <hr class="my-3">

              <div class="small">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                  <div class="fw-semibold">Return Terkait GRN</div>

                  @if($hasAnyReturn)
                    <span class="ret-pill">
                      {{ $draftReturns->count() }} draft • {{ $postedReturns->count() }} posted
                    </span>
                  @else
                    <span class="ret-pill text-muted">Belum ada return</span>
                  @endif
                </div>

                @if($hasAnyReturn)
                  <div class="d-flex flex-column gap-1">
                    @foreach($returns->sortByDesc('id')->take(8) as $r)
                      @php
                        $st = strtoupper($r->status ?? '-');
                        $isVoid = !empty($r->voided_at);
                      @endphp

                      <div class="d-flex justify-content-between align-items-center gap-2">
                        <div class="mono">
                          {{ $r->code ?? ('RET#'.$r->id) }}
                          <span class="text-muted">•</span>
                          <span class="text-muted">{{ $st }}</span>
                          @if($isVoid) <span class="text-danger ms-1">VOID</span> @endif
                        </div>

                        @if($returnShowRouteName)
                          <a class="btn btn-outline-secondary btn-sm"
                             href="{{ route($returnShowRouteName, $r->id) }}">
                            Buka
                          </a>
                        @endif
                      </div>
                    @endforeach
                  </div>

                  @if($returns->count() > 8)
                    <div class="text-muted mt-2">Menampilkan 8 terakhir.</div>
                  @endif
                @else
                  <div class="text-muted">
                    Jika ada kesalahan setelah GRN posted, gunakan <b>Return</b> untuk reversal stok & jurnal.
                  </div>
                @endif
              </div>
            @endif
          </div>
        </div>
      </div>

      @if ($canSeeMoney)
        {{-- 2) RINGKASAN NILAI + GRAND TOTAL --}}
        <div class="col-12 col-lg-6 order-2 order-lg-2">
          <div class="card-soft h-100">
            <div class="card-body">
              <h6 class="section-title mb-3">Ringkasan Nilai</h6>

              <div class="grand-card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <div>
                    <div class="text-muted small">Grand Total</div>
                    <div class="grand-amt mono">{{ rupiah($receipt->grand_total ?? 0) }}</div>
                  </div>
                  <div class="text-end">
                    <div class="grand-pill d-inline-flex align-items-center gap-2">
                      <span class="text-muted">Subtotal</span>
                      <span class="mono fw-semibold">{{ rupiah($receipt->subtotal ?? 0) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <dl class="row mb-0 small mono">
                <dt class="col-sm-5">Subtotal</dt>
                <dd class="col-sm-7 text-end">{{ rupiah($receipt->subtotal ?? 0) }}</dd>

                <dt class="col-sm-5">Diskon</dt>
                <dd class="col-sm-7 text-end">{{ rupiah($receipt->discount ?? 0) }}</dd>

                <dt class="col-sm-5">PPN ({{ decimal_id($receipt->tax_percent ?? 0, 2) }}%)</dt>
                <dd class="col-sm-7 text-end">{{ rupiah($receipt->tax_amount ?? 0) }}</dd>

                <dt class="col-sm-5">Ongkir</dt>
                <dd class="col-sm-7 text-end">{{ rupiah($receipt->shipping_cost ?? 0) }}</dd>

                <hr class="my-2 summary-hr">

                <dt class="col-sm-5 fw-semibold">Grand Total</dt>
                <dd class="col-sm-7 text-end fw-semibold fs-6">{{ rupiah($receipt->grand_total ?? 0) }}</dd>
              </dl>

              @if($isPosted)
                <div class="mt-3 small text-muted">
                  • GRN sudah posted → stok sudah masuk & jurnal tercatat. <br>
                  • Pembatalan setelah posted: gunakan <b>Return</b>.
                </div>
              @endif
            </div>
          </div>
        </div>
      @endif

      {{-- 3) DETAIL BARANG DITERIMA --}}
      <div class="col-12 order-3">
        <div class="card-main">
          <div class="card-header d-flex justify-content-between align-items-center gap-2">
            <span class="fw-semibold small text-uppercase">Detail Barang Diterima</span>
            <span class="small text-muted">Total baris: {{ $receipt->lines->count() }}</span>
          </div>

          <div class="card-body">
            <div class="grn-detail-wrapper">
              <table class="table table-sm mb-0 align-middle">
                <thead class="table-light sticky-top">
                  <tr>
                    <th style="width:4%;" class="text-center"><span class="th-full">No</span><span class="th-abbr">#</span></th>
                    <th style="width:22%"><span class="th-full">Item</span><span class="th-abbr">Item</span></th>
                    <th style="width:16%"><span class="th-full">LOT</span><span class="th-abbr">LOT</span></th>
                    <th style="width:9%" class="text-end"><span class="th-full">Qty In</span><span class="th-abbr">Qty</span></th>
                    <th style="width:9%" class="text-end"><span class="th-full">Qty Reject</span><span class="th-abbr">Rej</span></th>
                    @if ($canSeeMoney)
                      <th style="width:12%" class="text-end"><span class="th-full">Harga/Unit</span><span class="th-abbr">Harga</span></th>
                      <th style="width:12%" class="text-end"><span class="th-full">Total</span><span class="th-abbr">Total</span></th>
                    @endif
                    <th style="width:8%"><span class="th-full">Unit</span><span class="th-abbr">U</span></th>
                    <th style="width:8%"><span class="th-full">Catatan</span><span class="th-abbr">Cat</span></th>
                  </tr>
                </thead>

                <tbody class="small">
                  @forelse ($receipt->lines as $line)
                    <tr>
                      <td class="text-center align-middle">{{ $loop->iteration }}</td>

                      <td>
                        @if ($line->item)
                          <div class="cell-item-name fw-semibold">{{ $line->item->name }}</div>
                          <div class="cell-item-code mono">{{ $line->item->code }}</div>
                        @else
                          <span class="text-muted">-</span>
                        @endif
                      </td>

                      <td>
                        @if ($line->lot)
                          <div class="badge bg-light border text-body mono lot-badge">{{ $line->lot->code }}</div>
                          <div class="lot-extra text-muted mt-1">
                            Saldo LOT: {{ decimal_id($line->lot->qty_onhand, 2) }}
                            @if ($canSeeMoney && !is_null($line->lot->avg_cost))
                              • Avg: {{ rupiah($line->lot->avg_cost) }}
                            @endif
                          </div>
                        @else
                          <span class="text-muted small">-</span>
                        @endif
                      </td>

                      <td class="text-end mono">
                        <span class="val-full">{{ decimal_id($line->qty_received, 2) }}</span>
                        <span class="val-mobile">{{ decimal_id($line->qty_received, 0) }}</span>
                      </td>

                      <td class="text-end mono">
                        <span class="val-full">{{ decimal_id($line->qty_reject, 2) }}</span>
                        <span class="val-mobile">{{ decimal_id($line->qty_reject, 0) }}</span>
                      </td>

                      @if ($canSeeMoney)
                        <td class="text-end mono">
                          <span class="val-full">{{ rupiah($line->unit_price) }}</span>
                          <span class="val-mobile">{{ number_format($line->unit_price ?? 0, 0, ',', '.') }}</span>
                        </td>

                        <td class="text-end mono">
                          <span class="val-full">{{ rupiah($line->line_total) }}</span>
                          <span class="val-mobile">{{ number_format($line->line_total ?? 0, 0, ',', '.') }}</span>
                        </td>
                      @endif

                      <td class="mono">{{ $line->unit ?: ($line->item->unit ?? '-') }}</td>
                      <td>{{ $line->notes ?: '-' }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="{{ $canSeeMoney ? 9 : 7 }}" class="text-center text-muted py-3">Tidak ada detail barang.</td>
                    </tr>
                  @endforelse
                </tbody>

                @if ($receipt->lines->count())
                  <tfoot class="table-light">
                    <tr class="fw-semibold">
                      <td colspan="3" class="text-end">Total</td>
                      <td class="text-end mono">
                        <span class="val-full">{{ decimal_id($receipt->lines->sum('qty_received'), 2) }}</span>
                        <span class="val-mobile">{{ decimal_id($receipt->lines->sum('qty_received'), 0) }}</span>
                      </td>
                      <td class="text-end mono">
                        <span class="val-full">{{ decimal_id($receipt->lines->sum('qty_reject'), 2) }}</span>
                        <span class="val-mobile">{{ decimal_id($receipt->lines->sum('qty_reject'), 0) }}</span>
                      </td>
                      @if ($canSeeMoney)
                        <td class="text-end"></td>
                        <td class="text-end mono">
                          <span class="val-full">{{ rupiah($receipt->lines->sum('line_total')) }}</span>
                          <span class="val-mobile">{{ number_format($receipt->lines->sum('line_total') ?? 0, 0, ',', '.') }}</span>
                        </td>
                      @endif
                      <td colspan="2"></td>
                    </tr>
                  </tfoot>
                @endif
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>

    {{-- ══════════════════════════════════════════════
         Tahap 6: QC / Pemeriksaan Barang
         Hanya tampil jika GRN sudah posted
         QC optional — GRN tetap bisa jalan tanpa QC
    ══════════════════════════════════════════════ --}}
    @if ($isPosted && \Illuminate\Support\Facades\Schema::hasTable('purchase_receipt_qcs'))
      @php
        $qc = $receipt->qc ?? null;
        $canInputQc = $isOwner
            || $isAdmin
            || in_array($role, ['gudang', 'warehouse'], true);
        $qcCreateRoute = 'purchasing.purchase_receipts.qc.create';
        $qcEditRoute   = 'purchasing.purchase_receipts.qc.edit';
        $qcCancelRoute = 'purchasing.purchase_receipts.qc.cancel';
        $hasQcRoutes   = app('router')->has($qcCreateRoute);
      @endphp

      @if ($hasQcRoutes)
      <div class="mt-3">
        <div class="card-soft">
          <div class="card-body">

            <h6 class="section-title mb-3">QC / Pemeriksaan Barang</h6>

            @if (!$qc || $qc->isCancelled())
              {{-- Belum ada QC atau sudah dibatalkan --}}
              <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <div class="text-muted" style="font-size:.88rem;">
                  @if ($qc && $qc->isCancelled())
                    QC sebelumnya dibatalkan.
                  @else
                    Belum ada hasil pemeriksaan QC untuk GRN ini.
                  @endif
                  <span class="ms-1">QC bersifat opsional.</span>
                </div>
                @if ($canInputQc)
                  <a href="{{ route($qcCreateRoute, $receipt->id) }}"
                     class="btn btn-sm btn-outline-primary" style="border-radius:10px;">
                    📋 Input QC
                  </a>
                @endif
              </div>

            @else
              {{-- Ada QC --}}
              @php
                $qcBadgeStyle = match ($qc->status) {
                  'passed'    => 'background:rgba(22,163,74,.12);color:#15803d;border-color:rgba(22,163,74,.5)',
                  'issue'     => 'background:rgba(217,119,6,.10);color:#b45309;border-color:rgba(217,119,6,.5)',
                  'rejected'  => 'background:rgba(220,38,38,.08);color:#b91c1c;border-color:rgba(220,38,38,.5)',
                  default     => 'background:rgba(148,163,184,.10);color:#64748b;border-color:rgba(148,163,184,.4)',
                };
              @endphp

              {{-- Warning jika ada masalah --}}
              @if ($qc->hasIssue())
                <div class="d-flex align-items-start gap-2 p-3 mb-3 rounded-3"
                     style="background:rgba(220,38,38,.06);border:1px solid rgba(220,38,38,.25);">
                  <span style="font-size:1.1rem;line-height:1.3;">⚠️</span>
                  <div style="font-size:.85rem;">
                    <strong>Hasil QC: {{ \App\Models\PurchaseReceiptQc::statusLabel($qc->status) }}</strong>
                    @if ($qc->issue_type)
                      — {{ \App\Models\PurchaseReceiptQc::issueTypeLabel($qc->issue_type) }}
                    @endif
                    <div class="mt-1 text-muted">
                      Jika perlu mengembalikan barang ke supplier, gunakan fitur
                      <strong>Return Supplier</strong> di atas.
                    </div>
                  </div>
                </div>
              @endif

              {{-- Detail QC --}}
              <div class="row g-2 mb-3 small">
                <div class="col-6 col-sm-3">
                  <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Status</div>
                  <div class="mt-1">
                    <span class="d-inline-block px-2 py-1 rounded-pill"
                          style="font-size:.72rem;font-weight:600;border:1px solid;{{ $qcBadgeStyle }}">
                      {{ \App\Models\PurchaseReceiptQc::statusLabel($qc->status) }}
                    </span>
                  </div>
                </div>
                <div class="col-6 col-sm-3">
                  <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Qty Diperiksa</div>
                  <div class="fw-semibold mono mt-1">{{ number_format($qc->qty_checked, 2, ',', '.') }}</div>
                </div>
                <div class="col-6 col-sm-3">
                  <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Qty OK</div>
                  <div class="fw-semibold mono mt-1" style="color:#15803d;">
                    {{ number_format($qc->qty_ok, 2, ',', '.') }}
                  </div>
                </div>
                <div class="col-6 col-sm-3">
                  <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Qty Masalah</div>
                  <div class="fw-semibold mono mt-1" style="{{ $qc->qty_issue > 0 ? 'color:#b91c1c;' : '' }}">
                    {{ number_format($qc->qty_issue, 2, ',', '.') }}
                  </div>
                </div>

                @if ($qc->issue_type)
                  <div class="col-12 col-sm-6">
                    <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Jenis Masalah</div>
                    <div class="mt-1">{{ \App\Models\PurchaseReceiptQc::issueTypeLabel($qc->issue_type) }}</div>
                  </div>
                @endif

                @if ($qc->notes)
                  <div class="col-12">
                    <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Catatan</div>
                    <div class="mt-1" style="white-space:pre-line;">{{ $qc->notes }}</div>
                  </div>
                @endif

                <div class="col-12 col-sm-6">
                  <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;">Diperiksa Oleh</div>
                  <div class="mt-1">
                    {{ $qc->checkedBy?->name ?? '—' }}
                    @if ($qc->checked_at)
                      <span class="text-muted ms-1">· {{ $qc->checked_at->format('d/m/Y H:i') }}</span>
                    @endif
                  </div>
                </div>
              </div>

              {{-- Aksi: edit / cancel --}}
              @if ($canInputQc && !$qc->isCancelled())
                <div class="d-flex gap-2 flex-wrap">
                  <a href="{{ route($qcEditRoute, $receipt->id) }}"
                     class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.82rem;">
                    ✏ Edit QC
                  </a>
                  @if ($canManage)
                    <form method="POST"
                          action="{{ route($qcCancelRoute, $receipt->id) }}"
                          onsubmit="return confirm('Batalkan QC ini?\n\nData QC akan ditandai dibatalkan tapi tidak dihapus.');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger"
                              style="border-radius:10px;font-size:.82rem;">
                        Batalkan QC
                      </button>
                    </form>
                  @endif
                </div>
              @endif

              {{-- ═══════════════════════════════════════════════════
                   Tahap 9 — RESOLUSI QC (hanya jika issue/rejected)
              ═══════════════════════════════════════════════════ --}}
              @if ($qc->hasIssue() && !$qc->isCancelled() && app('router')->has('purchasing.purchase_receipt_qcs.resolve'))

                @if ($qc->isResolved())
                  {{-- Sudah resolved: tampilkan ringkasan --}}
                  <div class="mt-3 p-3 rounded-3"
                       style="background:rgba(22,163,74,.06);border:1px solid rgba(22,163,74,.25);">
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <span style="font-size:1rem;">✅</span>
                      <strong style="font-size:.85rem;">Penyelesaian QC</strong>
                    </div>
                    <div class="row g-2" style="font-size:.82rem;">
                      <div class="col-12 col-sm-4">
                        <div style="font-size:.70rem;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);">Tindakan</div>
                        <div class="mt-1 fw-semibold">
                          {{ \App\Models\PurchaseReceiptQc::resolutionLabel($qc->resolution_type) }}
                        </div>
                      </div>
                      <div class="col-12 col-sm-4">
                        <div style="font-size:.70rem;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);">Diselesaikan</div>
                        <div class="mt-1">{{ $qc->resolved_at?->format('d/m/Y H:i') ?? '—' }}</div>
                      </div>
                      @if ($qc->resolution_notes)
                      <div class="col-12">
                        <div style="font-size:.70rem;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);">Catatan</div>
                        <div class="mt-1" style="white-space:pre-line;">{{ $qc->resolution_notes }}</div>
                      </div>
                      @endif
                      @if ($qc->purchaseReturn && !$qc->purchaseReturn->voided_at)
                      <div class="col-12">
                        <div style="font-size:.70rem;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);">Purchase Return</div>
                        <div class="mt-1">
                          @if (app('router')->has('purchasing.purchase_returns.show'))
                          <a href="{{ route('purchasing.purchase_returns.show', $qc->purchase_return_id) }}"
                             style="font-size:.82rem;font-weight:600;">
                            {{ $qc->purchaseReturn->code }}
                          </a>
                          <span class="ms-1" style="font-size:.72rem;color:var(--muted);">
                            — {{ ucfirst($qc->purchaseReturn->status) }}
                            @if ($qc->purchaseReturn->voided_at) (VOID) @endif
                          </span>
                          @else
                          {{ $qc->purchaseReturn->code }}
                          @endif
                        </div>
                      </div>
                      @elseif ($qc->resolution_notes && str_contains($qc->resolution_notes, 'di-VOID'))
                      <div class="col-12">
                        <div class="text-warning" style="font-size:.78rem;">
                          ⚠ Return sebelumnya sudah di-VOID. Bisa dibuat return baru jika diperlukan.
                        </div>
                      </div>
                      @endif
                    </div>
                  </div>

                @elseif ($canManage)
                  {{-- Belum resolved: tampilkan form resolusi --}}
                  <div class="mt-3 p-3 rounded-3"
                       style="background:rgba(217,119,6,.06);border:1px solid rgba(217,119,6,.25);">
                    <div class="d-flex align-items-center gap-2 mb-3">
                      <span style="font-size:1rem;">⚠️</span>
                      <strong style="font-size:.85rem;">Tindak Lanjuti QC</strong>
                    </div>
                    <form method="POST"
                          action="{{ route('purchasing.purchase_receipt_qcs.resolve', $qc->id) }}"
                          onsubmit="return confirm('Simpan penyelesaian QC ini?\nPilihan retur tidak bisa dibatalkan dari halaman ini.');">
                      @csrf
                      <div class="row g-3">
                        <div class="col-12 col-sm-6">
                          <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.35rem;">
                            Tindakan Penyelesaian *
                          </label>
                          <select name="resolution_type" class="form-select form-select-sm"
                                  style="border-radius:8px;font-size:.82rem;" required>
                            <option value="">— Pilih tindakan —</option>
                            @foreach (\App\Models\PurchaseReceiptQc::resolutionTypes() as $val => $label)
                              <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                          </select>
                          <div class="mt-1" style="font-size:.70rem;color:var(--muted);">
                            <em>Retur</em> akan membuat draft Purchase Return otomatis.
                            <em>Klaim Invoice</em> akan mengarahkan ke Faktur Supplier.
                          </div>
                        </div>
                        <div class="col-12">
                          <label style="font-size:.75rem;font-weight:600;display:block;margin-bottom:.35rem;">
                            Catatan Penyelesaian
                          </label>
                          <textarea name="resolution_notes" rows="2"
                                    class="form-control form-control-sm"
                                    style="border-radius:8px;font-size:.82rem;"
                                    placeholder="Opsional — deskripsi tindakan, nomor klaim, dll."></textarea>
                        </div>
                        <div class="col-12">
                          <button type="submit" class="btn btn-sm btn-warning"
                                  style="border-radius:10px;font-size:.82rem;">
                            Simpan Penyelesaian →
                          </button>
                        </div>
                      </div>
                    </form>
                  </div>

                @else
                  {{-- Punya issue tapi bukan canManage: tampilkan info saja --}}
                  <div class="mt-3" style="font-size:.80rem;color:var(--muted);">
                    ⚠ QC ini perlu ditindaklanjuti oleh owner atau admin.
                  </div>
                @endif

              @endif
            @endif

          </div>
        </div>
      </div>
      @endif
    @endif

  </div>
</div>
@endsection
