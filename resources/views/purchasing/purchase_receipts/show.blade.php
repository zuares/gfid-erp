{{-- resources/views/purchasing/purchase_receipts/show.blade.php --}}
@extends('layouts.app')

@section('title', 'GRN ' . $receipt->code)

@push('head')
<style>
  .page-wrap { max-width:1080px; margin-inline:auto; padding-bottom:3rem; }

  .mono {
    font-variant-numeric: tabular-nums;
    font-family: ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono";
  }

  /* Cards */
  .card-info {
    background: var(--card);
    border-radius: 14px;
    border: 1px solid var(--line);
    padding: 1.1rem 1.2rem;
  }
  .card-section {
    background: var(--card);
    border-radius: 14px;
    border: 1px solid var(--line);
    overflow: hidden;
  }
  .card-section-header {
    padding: .6rem 1rem;
    border-bottom: 1px solid var(--line);
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--muted);
    font-weight: 600;
  }

  /* Status badges */
  .badge-status {
    border-radius: 999px;
    font-size: .7rem;
    padding: .1rem .6rem;
    border: 1px solid transparent;
    white-space: nowrap;
  }
  .badge-draft   { background:rgba(148,163,184,.12);color:#64748b;border-color:rgba(148,163,184,.5); }
  .badge-posted  { background:rgba(22,163,74,.12);color:#15803d;border-color:rgba(22,163,74,.6); }
  .badge-closed  { background:rgba(15,23,42,.10);color:#334155;border-color:rgba(15,23,42,.25); }
  [data-theme="dark"] .badge-closed { color:#cbd5e1;border-color:rgba(203,213,225,.3); }

  /* Info label/value */
  .info-label {
    font-size:.72rem;
    text-transform:uppercase;
    letter-spacing:.06em;
    color:var(--muted);
    font-weight:600;
    margin-bottom:.2rem;
  }
  .info-value { font-size:.88rem; }

  /* Summary row (like PO 4-col) */
  .summary-col {
    padding: .85rem 1rem;
    border-right: 1px solid var(--line);
  }
  .summary-col:last-child { border-right: none; }
  .summary-col-label {
    font-size:.7rem;
    text-transform:uppercase;
    letter-spacing:.06em;
    color:var(--muted);
    font-weight:600;
    margin-bottom:.3rem;
  }
  .summary-col-value { font-size:.95rem; font-weight:600; }

  /* Return button */
  .btn-return {
    border:1px solid rgba(245,158,11,.4);
    background:rgba(245,158,11,.1);
    color:#92400e;
  }
  [data-theme="dark"] .btn-return { color:#fbbf24; background:rgba(245,158,11,.14); }
  .btn-return:hover { filter:brightness(1.03); }

  /* Detail table */
  .grn-detail-wrapper { overflow-x:auto; }
  @media (min-width:992px){
    .grn-detail-wrapper { max-height:55vh; overflow-y:auto; }
    .grn-detail-wrapper::-webkit-scrollbar { width:5px; }
    .grn-detail-wrapper::-webkit-scrollbar-thumb { background:color-mix(in srgb,var(--muted) 50%,transparent); border-radius:999px; }
  }

  .grn-detail-wrapper table thead th {
    background:color-mix(in srgb,var(--card) 90%,var(--bg) 10%);
    border-bottom-color:var(--line);
    font-size:.72rem;
    text-transform:uppercase;
    letter-spacing:.05em;
    padding:.5rem .75rem;
    white-space:nowrap;
  }
  .grn-detail-wrapper table tbody td {
    border-bottom-color:var(--line);
    vertical-align:middle;
    padding:.45rem .75rem;
    font-size:.82rem;
  }
  .grn-detail-wrapper table tfoot td {
    border-top:2px solid var(--line);
    padding:.5rem .75rem;
    font-size:.83rem;
  }
  .lot-badge  { border-radius:999px; font-size:.72rem; }
  .lot-extra  { font-size:.72rem; }
  .th-full    { display:inline; }
  .th-abbr    { display:none; }
  .val-full   { display:inline; }
  .val-mobile { display:none; }

  /* Return list */
  .ret-pill {
    border-radius:999px;
    padding:.15rem .55rem;
    border:1px solid var(--line);
    font-size:.72rem;
    background:color-mix(in srgb,var(--card) 88%,var(--bg) 12%);
  }

  @media (max-width:767.98px){
    .page-wrap { padding-inline:.75rem; }
    .summary-col { border-right:none; border-bottom:1px solid var(--line); }
    .summary-col:last-child { border-bottom:none; }
    .grn-detail-wrapper table thead th { font-size:.67rem; }
    .grn-detail-wrapper table tbody td { font-size:.76rem; }
    .th-full { display:none; }
    .th-abbr { display:inline; }
    .val-full { display:none; }
    .val-mobile { display:inline; }
    .cell-item-name { display:none; }
    .cell-item-code { font-size:.8rem; color:var(--text); font-weight:600; }
    .lot-extra { display:none; }
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

<div class="page-wrap py-4">

    {{-- HEADER --}}
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
      {{-- Kiri --}}
      <div style="min-width:0;">
        <h2 class="mb-0 lh-1" style="font-size:1.35rem;">Goods Receipt</h2>
        <div class="text-muted mono mt-1 d-flex align-items-center gap-2" style="font-size:.8rem;">
          Kode: {{ $receipt->code }}
          @if($receipt->is_replacement)
            <span class="badge bg-info text-dark rounded-pill" style="font-size:0.7rem; font-family:var(--bs-font-sans-serif);">
              <i class="bi bi-arrow-repeat me-1"></i> Replacement
            </span>
          @endif
        </div>
      </div>

      {{-- Kanan: semua aksi sejajar --}}
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('purchasing.purchase_receipts.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>

        <a href="{{ route('purchasing.purchase_receipts.barcode', $receipt->id) }}"
           class="btn btn-outline-dark btn-sm" target="_blank">
          <i class="bi bi-upc-scan me-1"></i>Cetak Barcode
        </a>

        @if ($isDraft && $canManage)
          <a href="{{ route('purchasing.purchase_receipts.edit', $receipt->id) }}"
             class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
          </a>

          @if($receipt->is_replacement)
            <form action="{{ route('purchasing.purchase_receipts.destroy', $receipt->id) }}" method="POST"
                  onsubmit="return confirm('Hapus/Batalkan Draft Pengganti ini?');" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-trash me-1"></i>Batal Draft
              </button>
            </form>
          @endif

          @if ($isAdmin && !$grnHasPrice)
            <button type="button" class="btn btn-success btn-sm disabled"
                    title="Harga belum diisi di PO. Hubungi owner."
                    style="opacity:.5;cursor:not-allowed;">
              <i class="bi bi-check-lg me-1"></i>Post GRN
            </button>
          @else
            <form action="{{ route('purchasing.purchase_receipts.post', $receipt->id) }}" method="POST"
                  onsubmit="return confirm('Post GRN ini?\n\n• Stok akan bertambah\n• Jurnal akan tercatat');" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-check-lg me-1"></i>Post GRN
              </button>
            </form>
          @endif
        @endif

        @if ($isPosted && $canManage)
          @if ($primaryDraftReturn && $returnShowRouteName)
            <a href="{{ route($returnShowRouteName, $primaryDraftReturn->id) }}"
               class="btn btn-return btn-sm"
               onclick="return confirm('Sudah ada RETURN draft untuk GRN ini. Buka draft return?');">
              <i class="bi bi-arrow-return-left me-1"></i>Lanjut Return
            </a>
          @elseif ($primaryAnyReturn && $returnShowRouteName)
            <a href="{{ route($returnShowRouteName, $primaryAnyReturn->id) }}"
               class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-eye me-1"></i>Lihat Return
            </a>
          @elseif ($returnCreateRouteName)
            <form action="{{ route($returnCreateRouteName, $receipt->id) }}" method="POST"
                  onsubmit="return confirm('Buat draft RETURN dari GRN ini?');">
              @csrf
              <button type="submit" class="btn btn-return btn-sm">
                <i class="bi bi-arrow-return-left me-1"></i>Return
              </button>
            </form>
          @endif
        @endif

        @if ($canUnpostSafely)
          <form action="{{ route('purchasing.purchase_receipts.unpost', $receipt->id) }}" method="POST"
                onsubmit="return confirm('UNPOST GRN ini? Stok akan dibalik dan jurnal di-void.');">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">
              <i class="bi bi-x me-1"></i>Unpost
            </button>
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

    {{-- SUMMARY ROW (4-col like PO show) --}}
    <div class="card-info mb-3">
      <div class="row g-0">
        <div class="col-6 col-md-3 summary-col">
          <div class="summary-col-label">Status</div>
          <div class="summary-col-value">
            @if ($isPosted)
              <span class="badge-status badge-posted">Posted</span>
            @elseif ($isDraft)
              <span class="badge-status badge-draft">Draft</span>
            @else
              <span class="badge-status badge-draft">{{ ucfirst($receipt->status) }}</span>
            @endif
            @if ($hasAnyReturn)
              <div class="text-muted" style="font-size:.72rem;margin-top:.15rem;">
                {{ $draftReturns->count() }} draft · {{ $postedReturns->count() }} posted return
              </div>
            @endif
          </div>
        </div>
        <div class="col-6 col-md-3 summary-col">
          <div class="summary-col-label">Supplier</div>
          <div class="summary-col-value" style="font-size:.88rem;">{{ $receipt->supplier->name ?? '—' }}</div>
          @if ($receipt->supplier?->code)
            <div class="text-muted mono" style="font-size:.72rem;">{{ $receipt->supplier->code }}</div>
          @endif
        </div>
        <div class="col-6 col-md-3 summary-col">
          <div class="summary-col-label">Gudang</div>
          <div class="summary-col-value" style="font-size:.88rem;">{{ $receipt->warehouse->name ?? '—' }}</div>
          @if ($receipt->warehouse?->code)
            <div class="text-muted mono" style="font-size:.72rem;">{{ $receipt->warehouse->code }}</div>
          @endif
        </div>
        <div class="col-6 col-md-3 summary-col">
          <div class="summary-col-label">{{ $canSeeMoney ? 'Grand Total' : 'Tanggal' }}</div>
          <div class="summary-col-value mono">
            @if ($canSeeMoney)
              {{ rupiah($receipt->grand_total ?? 0) }}
            @else
              {{ $receipt->date?->format('Y-m-d') ?? '-' }}
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-3">

      {{-- 1) INFORMASI DOKUMEN --}}
      <div class="col-12 col-lg-6 order-1 order-lg-1">
        <div class="card-info h-100">
            <div class="info-label mb-3" style="font-size:.75rem;">Informasi Dokumen</div>

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

              @if ($receipt->is_replacement && $receipt->purchase_return_id && $receipt->returnOrigin)
                <dt class="col-sm-4">Pengganti Retur</dt>
                <dd class="col-sm-8">
                  <a href="{{ route('purchasing.purchase_returns.show', $receipt->purchase_return_id) }}" class="text-decoration-none">
                    {{ $receipt->returnOrigin->code }}
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
              <hr class="my-3" style="border-color:var(--line);">

              <div class="small">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                  <div class="info-label mb-0">Return Terkait GRN</div>

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
        </div>{{-- /card-info doc --}}
      </div>{{-- /col --}}

      @if ($canSeeMoney)
        {{-- 2) RINGKASAN NILAI --}}
        <div class="col-12 col-lg-6 order-2 order-lg-2">
          <div class="card-info h-100">
            <div class="info-label mb-3" style="font-size:.75rem;">Ringkasan Nilai</div>

            <dl class="row mb-0" style="font-size:.84rem;">
              <dt class="col-sm-5 info-label mb-1">Subtotal</dt>
              <dd class="col-sm-7 text-end mono mb-1">{{ rupiah($receipt->subtotal ?? 0) }}</dd>

              <dt class="col-sm-5 info-label mb-1">Diskon</dt>
              <dd class="col-sm-7 text-end mono mb-1">{{ rupiah($receipt->discount ?? 0) }}</dd>

              <dt class="col-sm-5 info-label mb-1">PPN ({{ decimal_id($receipt->tax_percent ?? 0, 2) }}%)</dt>
              <dd class="col-sm-7 text-end mono mb-1">{{ rupiah($receipt->tax_amount ?? 0) }}</dd>

              <dt class="col-sm-5 info-label mb-2">Ongkir</dt>
              <dd class="col-sm-7 text-end mono mb-2">{{ rupiah($receipt->shipping_cost ?? 0) }}</dd>
            </dl>

            <div class="pt-2" style="border-top:1px solid var(--line);">
              <div class="d-flex justify-content-between align-items-center">
                <span class="info-label mb-0">Grand Total</span>
                <span class="mono fw-bold" style="font-size:1.05rem;">{{ rupiah($receipt->grand_total ?? 0) }}</span>
              </div>
            </div>

            @if($isPosted)
              <div class="mt-3 text-muted" style="font-size:.78rem;">
                Stok sudah masuk & jurnal tercatat. Pembatalan: gunakan Return.
              </div>
            @endif
          </div>
        </div>
      @endif

      {{-- 3) DETAIL BARANG DITERIMA --}}
      <div class="col-12 order-3">
        <div class="card-section">
          <div class="card-section-header d-flex justify-content-between align-items-center">
            <span>Detail Barang Diterima</span>
            <span>{{ $receipt->lines->count() }} baris</span>
          </div>

          <div style="padding:.5rem 0;">
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
            </div>{{-- /grn-detail-wrapper --}}
          </div>{{-- /padding wrapper --}}
        </div>{{-- /card-section --}}
      </div>{{-- /col --}}

    </div>{{-- /row --}}

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
        <div class="card-section">
          <div class="card-section-header">QC / Pemeriksaan Barang</div>
          <div style="padding:1rem 1.2rem;">

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

          </div>{{-- /padding --}}
        </div>{{-- /card-section qc --}}
      </div>{{-- /mt-3 --}}
      @endif
    @endif

</div>{{-- /page-wrap --}}
@endsection
