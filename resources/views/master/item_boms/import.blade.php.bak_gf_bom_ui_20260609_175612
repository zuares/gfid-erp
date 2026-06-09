@extends('layouts.app')

@section('title','Master • Import BOM CSV')

@push('head')
<style>
/* =========================
   GFID BOM Import (inherit global theme)
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
@media(min-width:768px){ .grid{grid-template-columns: 1fr 1fr;} }

.h1{
  font-size: 18px;
  font-weight: 950;
  letter-spacing: -.02em;
  color: var(--text);
}
.subt{
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
.small{ font-size:.92rem; color: var(--muted); font-weight: 800; }
.mono{font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";}

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
  text-decoration: none;
}
.btnx:hover{ box-shadow: var(--bom-shadow2); }
.btnx:active{ transform: translateY(1px); }

.btnx.primary{
  background: var(--accent);
  border-color: color-mix(in srgb, var(--accent) 70%, var(--line) 30%);
  color: #06120a;
}
[data-theme="dark"] .btnx.primary{ color: #04140b; }

.btnx.success{
  background: var(--success);
  border-color: color-mix(in srgb, var(--success) 70%, var(--line) 30%);
  color: #06120a;
}
[data-theme="dark"] .btnx.success{ color: #031108; }

.btnx.ghost{ background: transparent; }

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
  background: var(--accent);
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--accent) 25%, transparent 75%);
}

.hr{ height:1px; background: var(--line); margin: 12px 0; }

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
<div class="page-wrap">

  <div class="cardx">
    <div class="rowx" style="justify-content:space-between;align-items:flex-start">
      <div>
        <div class="h1">Import BOM CSV</div>
        <div class="subt">
          Kolom wajib: <span class="mono">sku_code, material_code, qty</span>.
          Opsional: <span class="mono">uom, scrap_pct, is_optional, sort_order</span>.
        </div>

        <div class="rowx" style="margin-top:10px">
          <span class="chip"><span class="dot"></span> GFID • Import BOM</span>
        </div>
      </div>

      <div class="rowx">
        <a class="btnx" href="{{ route('master.item_boms.index') }}">Kembali</a>
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
        <a class="btnx" href="{{ route('master.item_boms.download_template') }}">Download Template CSV</a>
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
        <button class="btnx success" type="submit">Import</button>
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
