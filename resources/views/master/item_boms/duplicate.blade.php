@extends('layouts.app')

@section('title','Master • Duplicate BOM')

@push('head')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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
    <div style="font-size:20px;font-weight:900"><i class="bi bi-files"></i> Duplicate BOM</div>
    <div class="small">Copy BOM dari SKU sumber ke SKU tujuan.</div>

    @if($errors->any())
      <div class="alert alert-danger mt-3">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
    @endif

    <form class="mt-3" method="post" action="{{ route('master.item_boms.duplicate') }}">
      @csrf

      <div class="mb-2">
        <div class="small">SKU Sumber (punya BOM)</div>
        <select id="from_item_id" name="from_item_id" style="width:100%"></select>
      </div>

      <div class="mb-2">
        <div class="small">SKU Tujuan</div>
        <select id="to_item_id" name="to_item_id" style="width:100%"></select>
      </div>

      <div class="mb-2">
        <div class="small">Mode</div>
        <select class="form-control" name="mode" style="border-radius:12px">
          <option value="replace" selected>replace (hapus lines tujuan, copy dari sumber)</option>
          <option value="merge">merge (update/insert per material)</option>
        </select>
      </div>

      <div class="d-flex gap-3 flex-wrap mt-2">
        <label class="small" style="display:flex;gap:8px;align-items:center">
          <input type="checkbox" name="copy_name" value="1" checked>
          Copy nama BOM
        </label>
        <label class="small" style="display:flex;gap:8px;align-items:center">
          <input type="checkbox" name="activate" value="1" checked>
          Set Active
        </label>
      </div>

      <div class="d-flex gap-2 mt-3 flex-wrap">
        <button class="btnx primary" type="submit"><i class="bi bi-files"></i> Duplicate</button>
        <a class="btnx" href="{{ route('master.item_boms.index') }}"><i class="bi bi-arrow-left"></i> Kembali</a>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function(){
  const ajaxUrl = @json(route('master.item_boms.ajax_items'));

  function skuSelect(id){
    $(id).select2({
      placeholder: 'Cari SKU...',
      ajax: {
        url: ajaxUrl,
        dataType: 'json',
        delay: 150,
        data: params => ({ q: params.term || '', type: 'finished_good' }),
        processResults: data => data,
        cache: true
      },
      width: 'resolve'
    });
  }

  skuSelect('#from_item_id');
  skuSelect('#to_item_id');
})();
</script>
@endpush
