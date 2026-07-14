{{-- resources/views/purchasing/purchase_returns/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Purchasing • Return Pembelian')

@push('head')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
  /* Kustomisasi Select2 agar cocok dengan tema */
  .select2-container .select2-selection--single {
      height: 38px;
      border: 1px solid var(--line);
      border-radius: 8px;
      display: flex;
      align-items: center;
  }
  .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 36px;
  }
  
  .purchase-return-index{ --shp-accent:#334155; --shp-accent-2:#1f2937; --shp-border:rgba(148,163,184,.18); --shp-muted:#64748b; }
  .purchase-return-index .page-wrap{ max-width:1040px; margin-inline:auto; padding:.75rem .75rem 4rem; }
  .purchase-return-index .mono{
    font-variant-numeric:tabular-nums;
    font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono";
  }

  /* Sticky topbar (selaras shipment) */
  .purchase-return-index .ship-topbar{
    position:sticky; top:0; z-index:300;
    display:flex; justify-content:space-between; align-items:flex-start;
    gap:.6rem; flex-wrap:wrap;
    padding:.55rem .75rem; margin-inline:-.75rem; margin-bottom:.75rem;
    background:var(--card,#fff); border-bottom:1px solid var(--shp-border);
  }
  body[data-theme="dark"] .purchase-return-index .ship-topbar{ background:var(--card,#0f172a); }
  .purchase-return-index .title{ font-weight:750; font-size:1.05rem; margin:0; line-height:1.1; }
  .purchase-return-index .sub{ color:var(--shp-muted); font-size:.78rem; margin-top:.15rem; }

  .purchase-return-index .kpis{ display:flex; flex-wrap:wrap; gap:.32rem; margin-top:.45rem; }
  .purchase-return-index .kpi{
    display:inline-flex; align-items:baseline; gap:.42rem;
    border-radius:8px; padding:.3rem .65rem;
    border:1px solid rgba(148,163,184,.28); background:#f8fafc; font-size:.75rem; text-decoration:none;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
  }
  .purchase-return-index .kpi .lbl{ font-size:.66rem; color:#94a3b8; }
  .purchase-return-index .kpi .val{ font-weight:650; color:var(--shp-accent); }
  body[data-theme="dark"] .purchase-return-index .kpi .val{ color:#cbd5e1; }
  .purchase-return-index .kpi.kpi-alert .val{ color:#0369a1; }

  /* Buttons */
  .purchase-return-index .btn-pill{ border-radius:7px; padding-inline:.85rem; box-shadow:none!important; font-weight:600; }
  .purchase-return-index .btn-ship-primary{ background:var(--shp-accent)!important; border-color:var(--shp-accent)!important; color:#fff!important; }
  .purchase-return-index .btn-ship-primary:hover{ background:var(--shp-accent-2)!important; border-color:var(--shp-accent-2)!important; color:#fff!important; }
  .purchase-return-index .btn-ship-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
  .purchase-return-index .btn-ship-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }

  /* Unscoped agar juga berlaku di dalam modal (di luar .purchase-return-index) */
  .btn-pill{ border-radius:7px; padding-inline:.85rem; box-shadow:none!important; font-weight:600; }
  .btn-ship-primary{ background:#334155!important; border-color:#334155!important; color:#fff!important; }
  .btn-ship-primary:hover{ background:#1f2937!important; border-color:#1f2937!important; color:#fff!important; }
  .btn-ship-primary:disabled{ opacity:.55; }
  .btn-ship-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
  .btn-ship-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }

  .purchase-return-index .card-clean{ border:1px solid var(--shp-border); border-radius:8px; background:var(--card); }

  .purchase-return-index .code-link{ font-weight:700; }
  .purchase-return-index .muted{ color:#6b7280; font-size:.78rem; }
  body[data-theme="dark"] .purchase-return-index .muted{ color:#9ca3af; }

  /* Dot status badges (selaras shipment) */
  .purchase-return-index .badge-status{
    border-radius:7px; padding:.16rem .48rem; font-size:.68rem; white-space:nowrap; font-weight:600;
    border:1px solid transparent; display:inline-flex; align-items:center; gap:.35rem;
  }
  .purchase-return-index .badge-status::before{ content:''; width:7px; height:7px; border-radius:999px; display:inline-block; }
  .purchase-return-index .st-draft{ background:rgba(245,158,11,.10); color:#b45309; border-color:rgba(245,158,11,.30); }
  .purchase-return-index .st-draft::before{ background:rgba(245,158,11,.95); }
  .purchase-return-index .st-submitted{ background:rgba(59,130,246,.10); color:#1d4ed8; border-color:rgba(59,130,246,.30); }
  .purchase-return-index .st-submitted::before{ background:rgba(59,130,246,.95); }
  .purchase-return-index .st-posted{ background:rgba(34,197,94,.10); color:#166534; border-color:rgba(34,197,94,.30); }
  .purchase-return-index .st-posted::before{ background:rgba(34,197,94,.95); }
  .purchase-return-index .st-void{ background:rgba(239,68,68,.10); color:#b91c1c; border-color:rgba(239,68,68,.30); }
  .purchase-return-index .st-void::before{ background:rgba(239,68,68,.95); }
  .purchase-return-index .effect-text{ margin-top:.2rem; color:var(--shp-muted); font-size:.72rem; line-height:1.2; }

  .purchase-return-index .table-wrap{ overflow-x:auto; }
  .purchase-return-index thead th{
    background:var(--card,#fff); border-bottom-color:var(--shp-border);
    font-size:.68rem; letter-spacing:0; text-transform:none; color:#64748b; white-space:nowrap; padding:.52rem .62rem;
  }
  body[data-theme="dark"] .purchase-return-index thead th{ background:rgba(15,23,42,.98); color:#9ca3af; }
  .purchase-return-index tbody td{ vertical-align:middle; border-top-color:rgba(148,163,184,.16); padding:.52rem .62rem; }
  body[data-theme="dark"] .purchase-return-index tbody td{ border-top-color:rgba(51,65,85,.85); }
  .purchase-return-index .row-link{ cursor:pointer; transition: all 0.2s; }
  .purchase-return-index .row-link:hover{ background:rgba(241, 245, 249, 0.8); transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.02); }

  @media (max-width:767.98px){
    .purchase-return-index .ship-topbar{ margin-inline:-.5rem; }
    .purchase-return-index .table thead{ display:none; }
    .purchase-return-index .table tbody tr{
      display:block; border:1px solid var(--shp-border); border-radius:12px;
      margin-bottom:.6rem; padding:.7rem .8rem;
    }
    .purchase-return-index .table tbody td{
      display:flex; justify-content:space-between; align-items:center; gap:.75rem; border:0; padding:.26rem 0;
    }
    .purchase-return-index .table tbody td[data-label]::before{
      content:attr(data-label); color:var(--shp-muted); font-size:.76rem; font-weight:600;
    }
    .purchase-return-index .table tbody td.td-action{ display:block; margin-top:.4rem; }
    .purchase-return-index .table tbody td.td-action .btn{ width:100%; min-height:40px; }
  }
</style>
@endpush

@section('content')
@php
  $canSeeMoney = auth()->user()?->isOwner() ?? false;

  // Supplier yang punya minimal 1 GRN posted (kandidat retur) — untuk modal supplier-first.
  $returnSuppliers = \App\Models\Supplier::query()
      ->whereIn('id', \App\Models\PurchaseReceipt::query()
          ->where('status', 'posted')->distinct()->pluck('supplier_id')->filter())
      ->orderBy('name')
      ->get(['id', 'code', 'name']);

  $totalReturns = (int) ($summary->total_returns ?? 0);
  $draftCount = (int) ($summary->draft_count ?? 0);
  $submittedCount = (int) ($summary->submitted_count ?? 0);
  $postedCount = (int) ($summary->posted_count ?? 0);
  $voidCount = (int) ($summary->void_count ?? 0);
@endphp

<div class="container py-3 purchase-return-index">
  <div class="page-wrap">
    <div class="ship-topbar">
      <div>
        <div class="title">Return Pembelian</div>
        <div class="sub">Retur barang ke supplier berbasis GRN posted.</div>
        <div class="kpis">
          <span class="kpi"><span class="lbl">Total</span><span class="val mono">{{ angka($totalReturns) }}</span></span>
          <span class="kpi"><span class="lbl">Draft</span><span class="val mono">{{ angka($draftCount) }}</span></span>
          <a href="{{ route('purchasing.purchase_returns.index', ['status' => 'submitted']) }}"
             class="kpi {{ $submittedCount > 0 ? 'kpi-alert' : '' }}">
            <span class="lbl">Menunggu</span><span class="val mono">{{ angka($submittedCount) }}</span>
          </a>
          <span class="kpi"><span class="lbl">Posted</span><span class="val mono">{{ angka($postedCount) }}</span></span>
          <span class="kpi"><span class="lbl">Void</span><span class="val mono">{{ angka($voidCount) }}</span></span>
        </div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-sm btn-ship-primary btn-pill" data-bs-toggle="modal" data-bs-target="#modalCreateReturn">
            <i class="bi bi-plus-lg me-1"></i> Tambah Retur
        </button>
        <button type="button" class="btn btn-sm btn-ship-outline btn-pill" data-bs-toggle="modal" data-bs-target="#modalSearchItem">
            <i class="bi bi-search me-1"></i> Cari Barang
        </button>
        <a href="{{ route('purchasing.purchase_receipts.index') }}" class="btn btn-sm btn-ship-outline btn-pill">
          Lihat GRN
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    <form method="GET" class="card-clean p-3 mb-3" style="background: #f8fafc;">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label small mb-1">Cari</label>
          <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm"
            placeholder="Kode return, GRN, supplier...">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">Semua</option>
            <option value="draft" @selected($status === 'draft')>Draft</option>
            <option value="submitted" @selected($status === 'submitted')>Menunggu Approval</option>
            <option value="posted" @selected($status === 'posted')>Posted</option>
            <option value="void" @selected($status === 'void')>Void</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Dari</label>
          <input type="text" name="from_date" value="{{ request('from_date') }}"
            class="form-control form-control-sm gf-date-input" data-gf-date autocomplete="off">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Sampai</label>
          <input type="text" name="to_date" value="{{ request('to_date') }}"
            class="form-control form-control-sm gf-date-input" data-gf-date autocomplete="off">
        </div>
        <div class="col-6 col-md-2 d-grid">
          <button class="btn btn-ship-primary btn-pill btn-sm">Filter</button>
        </div>
      </div>
    </form>

    <div class="card-clean">
      <div class="table-wrap">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr>
              <th style="width:48px;">No</th>
              <th>Return</th>
              <th>GRN / Supplier</th>
              <th class="text-end">Qty</th>
              @if($canSeeMoney)
                <th class="text-end">Nilai</th>
              @endif
              <th>Status / Efek</th>
              <th style="width: 40px;"></th>
            </tr>
          </thead>
          <tbody>
            @forelse($returns as $ret)
              @php
                $isVoid = (bool) $ret->voided_at;
                $statusCss = $isVoid ? 'st-void' : (($ret->status === 'posted') ? 'st-posted' : (($ret->status === 'submitted') ? 'st-submitted' : 'st-draft'));
                $statusText = $isVoid ? 'VOID' : (($ret->status === 'submitted') ? 'MENUNGGU' : strtoupper((string) $ret->status));
                $effectText = $isVoid
                  ? 'Stok balik, jurnal batal'
                  : (($ret->status === 'posted') ? 'Stok keluar, jurnal masuk' : 'Belum ubah stok/jurnal');
                $href = route('purchasing.purchase_returns.show', $ret->id);
              @endphp
              <tr class="row-link" data-href="{{ $href }}">
                <td class="mono text-muted" data-label="No">{{ $returns->firstItem() + $loop->index }}</td>
                <td data-label="Return">
                  <div class="fw-bold mono">{{ $ret->code }}</div>
                  <div class="small text-muted">{{ $ret->date ? id_date($ret->date) : '-' }}</div>
                </td>
                <td data-label="GRN / Supplier">
                  <div class="mono fw-semibold">{{ $ret->grn?->code ?? '-' }}</div>
                  <div class="small text-muted">{{ $ret->supplier?->name ?? $ret->grn?->supplier?->name ?? '-' }}</div>
                </td>
                <td class="text-end mono" data-label="Qty">
                  {{ decimal_id($ret->total_qty ?? 0, 2) }}
                </td>
                @if($canSeeMoney)
                  <td class="text-end mono" data-label="Nilai">{{ rupiah($ret->total ?? 0) }}</td>
                @endif
                <td data-label="Status / Efek">
                  <span class="badge-status {{ $statusCss }}">{{ $statusText }}</span>
                  <div class="effect-text">{{ $effectText }}</div>
                </td>
                <td class="text-end td-action" style="color: #94a3b8;">
                  <i class="bi bi-chevron-right"></i>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="{{ $canSeeMoney ? 7 : 6 }}" class="text-center text-muted py-4">
                  Belum ada return pembelian.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">
      {{ $returns->links() }}
    </div>
  </div>
</div>

<!-- Modal Create Return -->
<div class="modal fade" id="modalCreateReturn" tabindex="-1" aria-labelledby="modalCreateReturnLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 14px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalCreateReturnLabel">Buat Retur Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formCreateReturn" method="POST" action="">
            @csrf

            {{-- 1) SUPPLIER — focal point --}}
            <div class="mb-3">
                <label class="form-label fw-bold mb-1" style="font-size:.82rem;">
                    <span class="badge rounded-circle text-bg-dark me-1" style="font-size:.62rem;">1</span> Supplier
                </label>
                <select class="form-control" id="supplierSelect" style="width:100%;">
                    <option value=""></option>
                    @foreach ($returnSuppliers as $sup)
                        <option value="{{ $sup->id }}">{{ $sup->name }}{{ $sup->code ? ' — '.$sup->code : '' }}</option>
                    @endforeach
                </select>
                <div class="form-text" style="font-size:.74rem;">Pilih supplier yang barangnya ingin diretur.</div>
            </div>

            {{-- 2) FAKTUR / GRN (tergantung supplier) --}}
            <div class="mb-3">
                <label class="form-label fw-bold mb-1" style="font-size:.82rem;">
                    <span class="badge rounded-circle text-bg-dark me-1" style="font-size:.62rem;">2</span> Faktur / Dokumen GRN
                </label>
                <select class="form-control" id="grnSelect" name="grn_id" required style="width:100%;" disabled>
                    <option value=""></option>
                </select>
                <div class="form-text" style="font-size:.74rem;">Dokumen penerimaan (GRN) yang sudah diposting dari supplier tersebut.</div>
            </div>

            {{-- 3) TANGGAL RETUR --}}
            <div class="mb-4">
                <label class="form-label fw-bold mb-1" style="font-size:.82rem;">
                    <span class="badge rounded-circle text-bg-dark me-1" style="font-size:.62rem;">3</span> Tanggal Retur
                </label>
                <input type="date" name="date" id="returnDate" class="form-control"
                       value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}">
            </div>

            <button type="submit" class="btn btn-ship-primary btn-pill w-100 fw-bold" id="btnSubmitReturn" disabled>Lanjut Buat Retur</button>
        </form>
      </div>
    </div>
  </div>
</div>
<!-- Modal Search Item -->
<div class="modal fade" id="modalSearchItem" tabindex="-1" aria-labelledby="modalSearchItemLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 14px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalSearchItemLabel">Cari Barang untuk Diretur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formSearchItem" method="POST" action="">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-bold mb-1" style="font-size:.82rem;">
                    Scan / Ketik Nama Barang
                </label>
                <select class="form-control" id="itemSearchSelect" name="grn_id" required style="width:100%;">
                    <option value=""></option>
                </select>
                <div class="form-text" style="font-size:.74rem;">Ketik barang untuk mencari GRN (Penerimaan) yang memuat barang ini.</div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold mb-1" style="font-size:.82rem;">
                    Tanggal Retur
                </label>
                <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}">
            </div>

            <button type="submit" class="btn btn-ship-primary btn-pill w-100 fw-bold" id="btnSubmitItemReturn" disabled>Lanjut Buat Retur</button>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
  // Auto-focus ke input Cari saat halaman dibuka (kursor di akhir teks)
  const returnSearch = document.querySelector('.purchase-return-index input[name="search"]');
  if (returnSearch) {
    setTimeout(function () {
      returnSearch.focus();
      const len = returnSearch.value.length;
      try { returnSearch.setSelectionRange(len, len); } catch (e) {}
    }, 100);
  }

  document.querySelectorAll('.purchase-return-index .row-link').forEach(function(row){
    row.addEventListener('click', function(e){
      if (e.target.closest('a, button, input, select')) return;
      const href = row.getAttribute('data-href');
      if (href) window.location.href = href;
    });
  });

  // ── Flow supplier-first: Supplier → Faktur (GRN) → Tanggal ──
  if (typeof jQuery !== 'undefined') {
      const $modal    = $('#modalCreateReturn');
      const $supplier = $('#supplierSelect');
      const $grn      = $('#grnSelect');
      const $btn      = $('#btnSubmitReturn');
      const $form     = $('#formCreateReturn');
      const searchUrl = '{{ route("purchasing.purchase_returns.search_grn") }}';

      // 1) Supplier — focal point (searchable, opsi lokal)
      $supplier.select2({
          theme: 'default',
          placeholder: 'Pilih / cari supplier…',
          allowClear: true,
          dropdownParent: $modal,
      });

      // 2) Faktur/GRN — ajax difilter per supplier terpilih
      $grn.select2({
          theme: 'default',
          placeholder: 'Pilih supplier dulu…',
          dropdownParent: $modal,
          ajax: {
              url: searchUrl,
              dataType: 'json',
              delay: 250,
              data: function (params) {
                  return { q: params.term, supplier_id: $supplier.val() || '' };
              },
              processResults: function (data) { return { results: data.results }; },
              cache: true
          }
      });

      function resetGrn() {
          $grn.val(null).trigger('change');
          $grn.empty().append(new Option('', '', true, true));
          $btn.prop('disabled', true);
          $form.attr('action', '');
      }

      // Ganti supplier → reset faktur + aktif/nonaktif
      $supplier.on('change', function () {
          const sup = $(this).val();
          resetGrn();
          if (sup) {
              $grn.prop('disabled', false)
                  .select2({ theme: 'default', placeholder: 'Cari faktur / GRN / surat jalan…', dropdownParent: $modal,
                             ajax: { url: searchUrl, dataType: 'json', delay: 250,
                                     data: function (p) { return { q: p.term, supplier_id: sup }; },
                                     processResults: function (d) { return { results: d.results }; }, cache: true } });
          } else {
              $grn.prop('disabled', true);
          }
      });

      // Pilih faktur → set action + aktifkan tombol
      $grn.on('change', function () {
          const val = $(this).val();
          if (val) {
              $btn.prop('disabled', false);
              $form.attr('action', '/purchasing/purchase-receipts/' + val + '/returns/create');
          } else {
              $btn.prop('disabled', true);
              $form.attr('action', '');
          }
      });

      // Reset saat modal ditutup agar bersih di pembukaan berikutnya
      $modal.on('hidden.bs.modal', function () {
          $supplier.val(null).trigger('change');
          resetGrn();
          $grn.prop('disabled', true);
      });

      // Fokus ke supplier saat modal dibuka (focal point)
      $modal.on('shown.bs.modal', function () {
          $supplier.select2('open');
      });

      // ── Flow Search by Item ──
      const $modalSearch = $('#modalSearchItem');
      const $itemSearch = $('#itemSearchSelect');
      const $btnSearch = $('#btnSubmitItemReturn');
      const $formSearch = $('#formSearchItem');
      const searchItemUrl = '{{ route("purchasing.purchase_returns.search_by_item") }}';

      $itemSearch.select2({
          theme: 'default',
          placeholder: 'Ketik nama / kode barang...',
          dropdownParent: $modalSearch,
          ajax: {
              url: searchItemUrl,
              dataType: 'json',
              delay: 350,
              data: function (params) {
                  return { q: params.term };
              },
              processResults: function (data) { return { results: data.results }; },
              cache: true
          }
      });

      $itemSearch.on('select2:select', function (e) {
          const data = e.params.data;
          if (data && data.id) {
              $btnSearch.prop('disabled', false);
              $formSearch.attr('action', '/purchasing/purchase-receipts/' + data.id + '/returns/create?item_id=' + data.item_id);
          }
      });
      $itemSearch.on('select2:unselect', function () {
          $btnSearch.prop('disabled', true);
          $formSearch.attr('action', '');
      });

      $modalSearch.on('hidden.bs.modal', function () {
          $itemSearch.val(null).trigger('change');
          $itemSearch.empty().append(new Option('', '', true, true));
      });

      $modalSearch.on('shown.bs.modal', function () {
          $itemSearch.select2('open');
      });
  } else {
      console.error('jQuery is required for Select2.');
  }
});
</script>
@endpush
