@extends('layouts.app')

@section('title','Master • Duplicate BOM')

@push('head')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.page-wrap{max-width:980px;margin:0 auto;padding:14px 12px 96px;}
.cardx{background:#fff;border:1px solid rgba(148,163,184,.28);border-radius:14px;padding:14px;box-shadow:0 10px 26px rgba(15,23,42,.06);}
.btnx{border:1px solid rgba(148,163,184,.35);border-radius:12px;padding:10px 12px;background:#fff;font-weight:900}
.btnx.primary{background:#16a34a;color:#fff;border-color:#16a34a}
.small{color:#64748b;font-weight:700}
.select2-container .select2-selection--single{height:44px;border-radius:12px;border:1px solid rgba(148,163,184,.35);display:flex;align-items:center}
.select2-container--default .select2-selection--single .select2-selection__rendered{line-height:44px}
</style>
@endpush

@section('content')
<div class="page-wrap">
  <div class="cardx">
    <div style="font-size:20px;font-weight:900">Duplicate BOM</div>
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
        <button class="btnx primary" type="submit">Duplicate</button>
        <a class="btnx" href="{{ route('master.item_boms.index') }}">Kembali</a>
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
