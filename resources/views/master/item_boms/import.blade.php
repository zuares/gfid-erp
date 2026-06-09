@extends('layouts.app')

@section('title','Master • Import BOM CSV')

@push('head')

<style>
    /* GF BOM UI FINAL - selaras Master Items */
    .gf-category-page,
    .page-wrap {
        max-width: 1180px !important;
        margin: 0 auto !important;
        padding: 16px 12px 28px !important;
        color: #0f172a !important;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
    }

    .cardx {
        border: 1px solid #e2e8f0 !important;
        border-radius: 24px !important;
        background: #ffffff !important;
        box-shadow: 0 16px 42px rgba(15, 23, 42, .07) !important;
        padding: 16px !important;
        overflow: hidden !important;
    }

    .cardx.gf-category-head,
    .gf-bom-form {
        margin-bottom: 14px !important;
        padding: 18px !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 24px !important;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 58%, #f1f5f9 100%) !important;
        box-shadow: 0 16px 42px rgba(15, 23, 42, .07) !important;
    }

    .cardx.sub {
        margin-top: 14px !important;
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 24px !important;
        box-shadow: 0 16px 42px rgba(15, 23, 42, .07) !important;
    }

    .rowx {
        display: flex !important;
        gap: 10px !important;
        flex-wrap: wrap !important;
        align-items: center !important;
    }

    .h1 {
        color: #0f172a !important;
        font-size: 1.34rem !important;
        font-weight: 950 !important;
        letter-spacing: -.05em !important;
        line-height: 1.1 !important;
        margin: 0 !important;
    }

    .subt,
    .small {
        color: #64748b !important;
        font-size: .82rem !important;
        font-weight: 650 !important;
        line-height: 1.45 !important;
    }

    .chip {
        display: inline-flex !important;
        align-items: center !important;
        gap: 7px !important;
        padding: 6px 10px !important;
        border-radius: 999px !important;
        background: #f1f5f9 !important;
        border: 1px solid #e2e8f0 !important;
        color: #334155 !important;
        font-size: .72rem !important;
        font-weight: 900 !important;
    }

    .dot {
        width: 7px !important;
        height: 7px !important;
        border-radius: 999px !important;
        background: #0f172a !important;
        display: inline-block !important;
    }

    .mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace !important;
        letter-spacing: -.02em !important;
    }

    .hr {
        height: 1px !important;
        background: #e2e8f0 !important;
        border: 0 !important;
        margin: 14px 0 !important;
    }

    .lbl {
        font-size: .72rem !important;
        font-weight: 900 !important;
        color: #334155 !important;
        margin-bottom: 5px !important;
        text-transform: uppercase !important;
        letter-spacing: .045em !important;
    }

    .grid {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 12px !important;
    }

    .inp,
    .form-control,
    .form-select {
        border-radius: 14px !important;
        border: 1px solid #e2e8f0 !important;
        min-height: 38px !important;
        color: #0f172a !important;
        font-size: .84rem !important;
        font-weight: 650 !important;
        background: #ffffff !important;
        box-shadow: none !important;
    }

    .inp:focus,
    .form-control:focus,
    .form-select:focus {
        border-color: #94a3b8 !important;
        box-shadow: 0 0 0 .22rem rgba(15, 23, 42, .08) !important;
    }

    .btnx,
    .btn {
        border-radius: 999px !important;
        font-weight: 850 !important;
        letter-spacing: -.01em !important;
        min-height: 34px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        text-decoration: none !important;
    }

    .btnx {
        padding: 8px 13px !important;
        border: 1px solid #cbd5e1 !important;
        background: rgba(255,255,255,.78) !important;
        color: #475569 !important;
    }

    .btnx:hover {
        background: #f8fafc !important;
        color: #0f172a !important;
        box-shadow: 0 10px 22px rgba(15, 23, 42, .08) !important;
    }

    .btnx.primary,
    .btnx.success,
    .btn-primary {
        color: #ffffff !important;
        background: linear-gradient(135deg, #0f172a, #334155) !important;
        border-color: transparent !important;
        box-shadow: 0 12px 24px rgba(15, 23, 42, .12) !important;
    }

    .btnx.ghost {
        background: #ffffff !important;
        color: #64748b !important;
        border-color: #e2e8f0 !important;
    }

    .btnx.danger,
    .btn-outline-danger {
        color: #991b1b !important;
        background: #fee2e2 !important;
        border-color: #fecaca !important;
    }

    .alert-successx,
    .alert-success {
        margin-top: 14px !important;
        border-radius: 16px !important;
        padding: 10px 12px !important;
        background: #dcfce7 !important;
        color: #166534 !important;
        border: 1px solid #bbf7d0 !important;
        font-size: .82rem !important;
        font-weight: 800 !important;
    }

    .alert,
    .alert-danger {
        border-radius: 16px !important;
        font-size: .82rem !important;
    }

    .table-wrap {
        max-height: 68vh !important;
        overflow: auto !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 18px !important;
        background: #ffffff !important;
    }

    .table-bom,
    .gf-clean-table {
        min-width: 860px !important;
        margin: 0 !important;
        background: #ffffff !important;
        color: #0f172a !important;
        font-size: .82rem !important;
    }

    .table-bom thead th,
    .gf-clean-table thead th,
    .gf-sticky-table thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 8 !important;
        background: #f8fafc !important;
        color: #64748b !important;
        font-size: .7rem !important;
        text-transform: uppercase !important;
        letter-spacing: .045em !important;
        font-weight: 900 !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 12px 10px !important;
        white-space: nowrap !important;
    }

    .table-bom td,
    .gf-clean-table td {
        border-color: #eef2f7 !important;
        padding: 12px 10px !important;
        vertical-align: middle !important;
        color: #0f172a !important;
    }

    .table-bom tbody tr:hover td,
    .gf-clean-table tbody tr:hover td {
        background: #f8fbff !important;
    }

    .badge {
        border-radius: 999px !important;
        padding: 6px 10px !important;
        font-weight: 850 !important;
    }

    .pagination {
        margin: 0 !important;
        gap: 4px !important;
    }

    .pagination .page-link {
        border-radius: 11px !important;
        border-color: #e2e8f0 !important;
        color: #475569 !important;
        font-size: .78rem !important;
        font-weight: 700 !important;
    }

    .pagination .active .page-link,
    .pagination .page-item.active .page-link {
        color: #ffffff !important;
        background: #0f172a !important;
        border-color: #0f172a !important;
    }

    .select2-container {
        width: 100% !important;
    }

    .select2-container .select2-selection--single {
        height: 40px !important;
        border-radius: 14px !important;
        border: 1px solid #e2e8f0 !important;
        display: flex !important;
        align-items: center !important;
        background: #ffffff !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 40px !important;
        color: #0f172a !important;
        font-size: .84rem !important;
        font-weight: 650 !important;
        padding-left: 12px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
        right: 8px !important;
    }

    .select2-dropdown {
        border: 1px solid #e2e8f0 !important;
        border-radius: 14px !important;
        overflow: hidden !important;
        box-shadow: 0 18px 46px rgba(15, 23, 42, .14) !important;
    }

    .select2-results__option {
        font-size: .84rem !important;
        padding: 8px 12px !important;
    }

    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background: #0f172a !important;
        color: #ffffff !important;
    }

    .gf-live-filter-wrap {
        position: relative !important;
    }

    .gf-live-filter-indicator {
        position: absolute !important;
        right: 12px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        display: none !important;
        color: #334155 !important;
        background: rgba(255,255,255,.88) !important;
        padding-left: 8px !important;
        font-size: .72rem !important;
        font-weight: 850 !important;
    }

    .gf-live-filter-indicator.is-show {
        display: inline-flex !important;
    }

    @media (max-width: 992px) {
        .cardx.gf-category-head,
        .gf-bom-form {
            border-radius: 22px !important;
        }

        .grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 768px) {
        .gf-category-page,
        .page-wrap {
            padding: 12px 10px 24px !important;
        }

        .cardx,
        .cardx.gf-category-head,
        .gf-bom-form {
            border-radius: 20px !important;
            padding: 14px !important;
        }

        .rowx {
            align-items: stretch !important;
        }

        .btnx,
        .btn {
            width: auto;
        }

        form.rowx {
            display: grid !important;
            grid-template-columns: 1fr !important;
        }

        form.rowx > div {
            width: 100% !important;
        }

        form.rowx .btnx {
            width: 100% !important;
        }
    }

    .gf-bom-head-layout {
        display: flex !important;
        align-items: flex-start !important;
        gap: 13px !important;
        min-width: 0 !important;
    }

    .gf-bom-head-icon {
        width: 48px !important;
        height: 48px !important;
        flex: 0 0 48px !important;
        border-radius: 17px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        color: #ffffff !important;
        background: linear-gradient(135deg, #0f172a, #334155) !important;
        box-shadow: 0 14px 28px rgba(15, 23, 42, .18) !important;
        font-size: 1.22rem !important;
    }

    .gf-bom-head-content {
        min-width: 0 !important;
    }

    @media (max-width: 768px) {
        .gf-bom-head-icon {
            width: 42px !important;
            height: 42px !important;
            flex-basis: 42px !important;
            border-radius: 15px !important;
            font-size: 1.08rem !important;
        }
    }

</style>

@endpush

@section('content')
<div class="gf-category-page">

  <div class="cardx gf-category-head">
    <div class="rowx" style="justify-content:space-between;align-items:flex-start">
      <div class="gf-bom-head-layout">
        <div class="gf-bom-head-icon"><i class="bi bi-upload"></i></div>
        <div class="gf-bom-head-content">
          <div class="h1">Import BOM CSV</div>
          <div class="subt">
          Kolom wajib: <span class="mono">sku_code, material_code, qty</span>.
          Opsional: <span class="mono">uom, scrap_pct, is_optional, sort_order</span>.
        </div>

        <div class="rowx" style="margin-top:10px">
          <span class="chip"><span class="dot"></span> GFID • Import BOM</span>
          </div>
        </div>
      </div>

      <div class="rowx">
        <a class="btnx" href="{{ route('master.item_boms.index') }}"><i class="bi bi-arrow-left"></i> Kembali</a>
      </div>
    </div>

    <div class="hr"></div>

    <div class="cardx sub" style="padding:12px">
      <div class="small">Contoh baris:</div>
      <div class="mono" style="font-weight:950;margin-top:6px">
        C5BLK,FLC280BLK,1.20,pcs,2,0,10
      </div>
      <div class="small" style="margin-top:6px">
        Mode <span class="mono">replace</span> akan menghapus lines lama lalu isi dari CSV.
      </div>

      <div class="rowx" style="margin-top:10px">
        <a class="btnx" href="{{ route('master.item_boms.download_template') }}"><i class="bi bi-download"></i> Download Template CSV</a>
      </div>
    </div>

    <form class="mt-3" method="post" action="{{ route('master.item_boms.import') }}" enctype="multipart/form-data">
      @csrf

      <div class="grid">
        <div>
          <div class="lbl">Mode</div>
          <select class="inp mono" name="mode">
            <option value="replace" selected>replace (hapus lines lama, isi dari CSV)</option>
            <option value="merge">merge (update/insert per material)</option>
          </select>
          <div class="small" style="margin-top:6px">Saran: pakai <span class="mono">replace</span> untuk import awal.</div>
        </div>

        <div>
          <div class="lbl">File CSV</div>
          <input class="inp" type="file" name="file" accept=".csv,text/csv">
          <div class="small" style="margin-top:6px">Pastikan delimiter koma <span class="mono">,</span> dan decimal pakai titik.</div>
        </div>
      </div>

      @if($errors->any())
        <div class="alert alert-danger mt-3">
          <div style="font-weight:950">Error:</div>
          <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
      @endif

      <div class="rowx" style="margin-top:14px;justify-content:flex-end">
        <button class="btnx success" type="submit"><i class="bi bi-upload"></i> Import</button>
        <a class="btnx ghost" href="{{ route('master.item_boms.index') }}">Batal</a>
      </div>
    </form>
  </div>

  {{-- optional: tampilkan skipped dari controller --}}
  @if(session('skipped') && is_array(session('skipped')) && count(session('skipped')))
    <div style="height:12px"></div>
    <div class="cardx sub">
      <div class="h1" style="font-size:16px">Skipped / Warning</div>
      <div class="small">Baris yang dilewati (contoh: SKU/material tidak ditemukan atau duplikat).</div>
      <div class="hr"></div>
      <ul class="mb-0">
        @foreach(session('skipped') as $s)
          <li class="mono" style="font-weight:850">{{ $s }}</li>
        @endforeach
      </ul>
    </div>
  @endif

</div>
@endsection
