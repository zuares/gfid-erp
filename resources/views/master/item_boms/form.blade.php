@extends('layouts.app')

@section('title', $bom ? 'Master • Edit BOM' : 'Master • Buat BOM')

@push('head')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* =========================
   GFID BOM Form (inherit global theme)
   -> JANGAN define --bg/--card/--text lagi
   ========================= */

:root{
  --bom-r: 16px;
  --bom-shadow: 0 14px 34px rgba(15,23,42,.10), 0 0 0 1px rgba(15,23,42,.04);
  --bom-shadow2: 0 10px 26px rgba(15,23,42,.08), 0 0 0 1px rgba(15,23,42,.03);
}

.page-wrap{
  max-width: 1020px;
  margin: 0 auto;
  padding: 14px 12px calc(110px + env(safe-area-inset-bottom));
}

/* Card GFID */
.cardx{
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--bom-r);
  padding: 14px;
  box-shadow: var(--bom-shadow);
  color: var(--text);
}
.cardx.sub{
  background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
  box-shadow: none;
}

.rowx{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.grid{display:grid;gap:12px}
@media(min-width:768px){ .grid{grid-template-columns: 1.35fr 1fr;} }

.h1{
  font-size: 18px;
  font-weight: 950;
  letter-spacing: -.02em;
  color: var(--text);
}
.sub{
  margin-top: 2px;
  font-size: .92rem;
  color: var(--muted);
  font-weight: 700;
}
.lbl{
  font-weight: 900;
  color: var(--text);
  margin-bottom: 6px;
}

/* Inputs */
.inp{
  border: 1px solid var(--line);
  border-radius: 14px;
  padding: 11px 12px;
  width: 100%;
  background: color-mix(in srgb, var(--card) 95%, var(--bg) 5%);
  color: var(--text);
  outline: none;
  transition: box-shadow .15s ease, border-color .15s ease, transform .05s ease;
}
.inp::placeholder{ color: var(--muted); font-weight: 700; }
.inp:focus{
  border-color: var(--accent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-soft) 70%, var(--accent) 30%);
}

.mono{font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";}

/* Buttons */
.btnx{
  border: 1px solid var(--line);
  border-radius: 14px;
  padding: 10px 12px;
  background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
  color: var(--text);
  font-weight: 950;
  letter-spacing: .01em;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: transform .05s ease, box-shadow .15s ease, border-color .15s ease, filter .15s ease;
}
.btnx:hover{ box-shadow: var(--bom-shadow2); }
.btnx:active{ transform: translateY(1px); }

.btnx.primary{
  background: var(--success);
  border-color: color-mix(in srgb, var(--success) 70%, var(--line) 30%);
  color: #06120a;
}
[data-theme="dark"] .btnx.primary{ color: #031108; }

.btnx.ghost{
  background: transparent;
}

.btnx.danger{
  background: var(--danger);
  border-color: color-mix(in srgb, var(--danger) 70%, var(--line) 30%);
  color: #fff;
  padding: 8px 10px;
  border-radius: 12px;
}

/* Chips */
.chip{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:8px 10px;
  border-radius:999px;
  border: 1px solid var(--line);
  background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
  color: var(--text);
  font-weight: 900;
}
.dot{
  width:10px;height:10px;border-radius:999px;
  background: var(--success);
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--success) 25%, transparent 75%);
}

.hr{ height:1px; background: var(--line); margin: 12px 0; }
.small{ font-size:.92rem; color: var(--muted); font-weight: 800; }

/* Table */
.table-wrap{
  overflow:auto;
  border-radius: 14px;
  border: 1px solid var(--line);
}
.table-bom{
  min-width: 980px;
  margin: 0;
  background: transparent;
}
.table-bom thead th{
  position: sticky;
  top: 0;
  z-index: 1;
  background: color-mix(in srgb, var(--card) 88%, var(--bg) 12%);
  color: var(--text) !important;
  border-bottom: 1px solid var(--line);
  font-weight: 800;
}
.table-bom td{
  border-top-color: var(--line);
  color: var(--text);
  vertical-align: middle;
}
.table-bom tbody tr:hover td{
  background: color-mix(in srgb, var(--accent-soft) 18%, transparent 82%);
}

/* Select2 ikut theme */
.select2-container{ width:100% !important; }
.select2-container .select2-selection--single{
  height: 46px;
  border-radius: 14px !important;
  border: 1px solid var(--line) !important;
  background: color-mix(in srgb, var(--card) 95%, var(--bg) 5%) !important;
  display:flex;
  align-items:center;
}
.select2-container--default .select2-selection--single .select2-selection__rendered{
  line-height: 46px !important;
  padding-left: 12px !important;
  padding-right: 28px !important;
  color: var(--text) !important;
  font-weight: 850;
}
.select2-container--default .select2-selection--single .select2-selection__arrow{
  height: 46px !important;
  right: 8px !important;
}
.select2-dropdown{
  border: 1px solid var(--line) !important;
  border-radius: 14px !important;
  overflow: hidden;
  background: var(--card) !important;
  box-shadow: var(--bom-shadow) !important;
}
.select2-search__field{
  border: 1px solid var(--line) !important;
  border-radius: 12px !important;
  padding: 10px 12px !important;
  outline: none !important;
  background: color-mix(in srgb, var(--card) 95%, var(--bg) 5%) !important;
  color: var(--text) !important;
}
.select2-results__option{
  color: var(--text) !important;
  font-weight: 850;
  padding: 10px 12px !important;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable{
  background: color-mix(in srgb, var(--accent-soft) 55%, var(--accent) 45%) !important;
  color: var(--text) !important;
}

/* Alert */
.alert-danger{
  border-radius: 14px;
  border: 1px solid color-mix(in srgb, var(--danger) 35%, var(--line) 65%);
  background: var(--danger-soft);
  color: var(--text);
}
</style>
@endpush

@section('content')
@php
  $isEdit = (bool)$bom;
  $action = $isEdit ? route('master.item_boms.update',$bom) : route('master.item_boms.store');
@endphp

<div class="page-wrap">
  <form method="post" action="{{ $action }}" class="cardx" id="bom-form">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="rowx" style="justify-content:space-between;align-items:flex-start">
      <div>
        <div class="h1">{{ $isEdit ? 'Edit BOM' : 'Buat BOM' }}</div>
        <div class="sub">BOM per SKU — material dari items (FLC280BLK, RIB280BLK, dll).</div>
        <div class="rowx" style="margin-top:10px">
          <span class="chip"><span class="dot"></span> GFID • BOM SKU</span>
        </div>
      </div>

      <div class="rowx">
        <button class="btnx primary" type="submit">Simpan</button>
        <a class="btnx ghost" href="{{ route('master.item_boms.index') }}">Kembali</a>
      </div>
    </div>

    @if($errors->any())
      <div class="mt-3 alert alert-danger">
        <div style="font-weight:950">Error:</div>
        <ul class="mb-0">
          @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
      </div>
    @endif

    <div class="hr"></div>

    <div class="grid">
      <div>
        <div class="lbl">SKU (Finished Goods)</div>
        @if($isEdit)
          <input class="inp mono" value="{{ $bom->item->code }} — {{ $bom->item->name }}" disabled>
        @else
          <select id="sku_id" name="item_id" style="width:100%"></select>
          <div class="small" style="margin-top:6px">Ketik kode: <span class="mono">C5BLK</span> / <span class="mono">J3MST</span> / dll</div>
        @endif
      </div>

      <div>
        <div class="lbl">Nama BOM (opsional)</div>
        <input class="inp" name="name" value="{{ old('name', $bom->name ?? '') }}" placeholder="Misal: BOM C5BLK">
        <div class="rowx" style="margin-top:10px">
          <label class="rowx" style="gap:8px">
            <input type="checkbox" name="active" value="1" {{ old('active', $bom->active ?? 1) ? 'checked' : '' }}>
            <span class="lbl" style="margin:0">Active</span>
          </label>
          <span class="small">Nonaktifkan kalau BOM lama</span>
        </div>
      </div>
    </div>

    <div style="height:12px"></div>

    <div class="cardx sub">
      <div class="rowx" style="justify-content:space-between;align-items:flex-start">
        <div>
          <div class="lbl" style="margin:0">Lines</div>
          <div class="small">Tambah material: <span class="mono">FLC280BLK</span>, <span class="mono">RIB280BLK</span>, <span class="mono">TLKADDS</span>, <span class="mono">KRT4CM</span>, <span class="mono">BNGJHT</span>, dll.</div>
        </div>
        <button type="button" class="btnx" id="btn-add">+ Tambah Baris</button>
      </div>

      <div class="table-wrap mt-3">
        <table class="table table-sm table-bom align-middle mb-0">
          <thead>
            <tr>
              <th style="width:56px">#</th>
              <th style="min-width:380px">Material</th>
              <th style="width:140px">Qty</th>
              <th style="width:110px">UOM</th>
              <th style="width:120px">Scrap %</th>
              <th style="width:110px">Optional</th>
              <th style="width:90px">Sort</th>
              <th style="width:92px"></th>
            </tr>
          </thead>
          <tbody id="lines">
            @php
              $old = old('lines');
              $rows = [];
              if (is_array($old)) $rows = $old;
              elseif($isEdit) {
                $rows = $bom->lines->map(fn($l)=>[
                  'material_item_id'=>$l->material_item_id,
                  'material_text'=>($l->material->code.' — '.$l->material->name),
                  'qty'=>(string)$l->qty,
                  'uom'=>$l->uom,
                  'scrap_pct'=>(string)$l->scrap_pct,
                  'is_optional'=>$l->is_optional?1:0,
                  'sort_order'=>$l->sort_order,
                ])->toArray();
              } else {
                $rows = [[
                  'material_item_id'=>'',
                  'material_text'=>'',
                  'qty'=>'',
                  'uom'=>'pcs',
                  'scrap_pct'=>'0',
                  'is_optional'=>0,
                  'sort_order'=>10,
                ]];
              }
            @endphp

            @foreach($rows as $i => $r)
              <tr class="line" data-i="{{ $i }}">
                <td class="mono idx" style="font-weight:950">{{ $i+1 }}</td>
                <td>
                  <select class="mat" name="lines[{{ $i }}][material_item_id]" style="width:100%">
                    @if(!empty($r['material_item_id']))
                      <option value="{{ $r['material_item_id'] }}" selected>{{ $r['material_text'] }}</option>
                    @endif
                  </select>
                </td>
                <td><input class="inp mono" name="lines[{{ $i }}][qty]" value="{{ $r['qty'] }}" placeholder="0.00"></td>
                <td><input class="inp mono" name="lines[{{ $i }}][uom]" value="{{ $r['uom'] ?? 'pcs' }}" placeholder="pcs"></td>
                <td><input class="inp mono" name="lines[{{ $i }}][scrap_pct]" value="{{ $r['scrap_pct'] ?? '0' }}" placeholder="0"></td>
                <td>
                  <select class="inp mono" name="lines[{{ $i }}][is_optional]">
                    <option value="0" {{ (int)($r['is_optional']??0)===0?'selected':'' }}>No</option>
                    <option value="1" {{ (int)($r['is_optional']??0)===1?'selected':'' }}>Yes</option>
                  </select>
                </td>
                <td><input class="inp mono" name="lines[{{ $i }}][sort_order]" value="{{ $r['sort_order'] ?? ($i*10) }}"></td>
                <td><button type="button" class="btnx danger btn-del">Hapus</button></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="small mt-2">Tip: jangan duplikat material; kalau perlu, gabungkan qty-nya.</div>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function(){
  const ajaxUrl = @json(route('master.item_boms.ajax_items'));

  function initSkuSelect(){
    const $el = $('#sku_id');
    if(!$el.length) return;

    $el.select2({
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

  function initMaterialSelect($select){
    $select.select2({
      placeholder: 'Cari material...',
      ajax: {
        url: ajaxUrl,
        dataType: 'json',
        delay: 150,
        data: params => ({ q: params.term || '', type: 'material' }),
        processResults: data => data,
        cache: true
      },
      width: 'resolve'
    });
  }

  function renumber(){
    $('#lines tr.line').each(function(idx){
      $(this).find('.idx').text(idx+1);
    });
  }

  function addRow(){
    const $tbody = $('#lines');
    const i = $tbody.find('tr.line').length;
    const sort = (i+1)*10;

    const html = `
      <tr class="line" data-i="${i}">
        <td class="mono idx" style="font-weight:950">${i+1}</td>
        <td><select class="mat" name="lines[${i}][material_item_id]" style="width:100%"></select></td>
        <td><input class="inp mono" name="lines[${i}][qty]" placeholder="0.00"></td>
        <td><input class="inp mono" name="lines[${i}][uom]" value="pcs" placeholder="pcs"></td>
        <td><input class="inp mono" name="lines[${i}][scrap_pct]" value="0" placeholder="0"></td>
        <td>
          <select class="inp mono" name="lines[${i}][is_optional]">
            <option value="0" selected>No</option>
            <option value="1">Yes</option>
          </select>
        </td>
        <td><input class="inp mono" name="lines[${i}][sort_order]" value="${sort}"></td>
        <td><button type="button" class="btnx danger btn-del">Hapus</button></td>
      </tr>
    `;
    const $row = $(html);
    $tbody.append($row);
    initMaterialSelect($row.find('select.mat'));
    renumber();
  }

  $(document).on('click', '#btn-add', addRow);
  $(document).on('click', '.btn-del', function(){
    $(this).closest('tr').remove();
    renumber();
  });

  initSkuSelect();
  $('#lines select.mat').each(function(){ initMaterialSelect($(this)); });
})();
</script>
@endpush
