@extends('layouts.app')

@section('title','Master • BOM SKU')

@push('head')
<style>
/* =========================
   GFID BOM Index (inherit global theme)
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
@media(min-width:768px){ .grid{grid-template-columns: 1.2fr .8fr;} }

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
  background: var(--success);
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--success) 25%, transparent 75%);
}

.hr{ height:1px; background: var(--line); margin: 12px 0; }

/* Table */
.table-wrap{
  overflow:auto;
  border-radius: 14px;
  border: 1px solid var(--line);
}
.table-bom{
  min-width: 860px;
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

/* Alert success */
.alert-successx{
  border-radius: 14px;
  border: 1px solid color-mix(in srgb, var(--success) 35%, var(--line) 65%);
  background: color-mix(in srgb, var(--success) 12%, var(--card) 88%);
  color: var(--text);
  padding: 12px 14px;
  font-weight: 850;
}
</style>
@endpush

@section('content')
<div class="page-wrap">

  <div class="cardx">
    <div class="rowx" style="justify-content:space-between;align-items:flex-start">
      <div>
        <div class="h1">BOM per SKU</div>
        <div class="subt">1 SKU = 1 BOM (paling cepat jalan). Material ambil dari <span class="mono">items</span>.</div>

        <div class="rowx" style="margin-top:10px">
          <span class="chip"><span class="dot"></span> GFID • BOM SKU</span>
          <span class="chip">Total: <span class="mono">{{ $boms->total() }}</span></span>
        </div>
      </div>

      <div class="rowx">
        <a class="btnx" href="{{ route('master.item_boms.import_form') }}">Import CSV</a>
        <a class="btnx" href="{{ route('master.item_boms.duplicate_form') }}">Duplicate BOM</a>
        <a class="btnx primary" href="{{ route('master.item_boms.create') }}">+ BOM Baru</a>
      </div>
    </div>

    <div class="hr"></div>

    <form class="rowx" method="get" style="align-items:flex-end">
      <div style="flex:1;min-width:240px">
        <div class="lbl">Cari SKU</div>
        <input class="inp mono" name="q" value="{{ $q }}" placeholder="Cari SKU (code / nama)..." />
        <div class="small" style="margin-top:6px">Contoh: <span class="mono">C5BLK</span>, <span class="mono">J3MST</span>, <span class="mono">K1NVY</span></div>
      </div>
      <div class="rowx">
        <button class="btnx success" type="submit">Cari</button>
        <a class="btnx ghost" href="{{ route('master.item_boms.index') }}">Reset</a>
      </div>
    </form>
  </div>

  <div style="height:12px"></div>

  @if(session('success'))
    <div class="alert-successx">{{ session('success') }}</div>
    <div style="height:12px"></div>
  @endif

  <div class="cardx sub">
    <div class="table-wrap">
      <table class="table table-sm table-bom align-middle mb-0">
        <thead>
          <tr>
            <th style="width:56px">#</th>
            <th style="width:170px">SKU</th>
            <th>Nama</th>
            <th style="width:120px">Status</th>
            <th style="width:140px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($boms as $i => $b)
            <tr>
              <td class="mono" style="font-weight:950">{{ ($boms->currentPage()-1)*$boms->perPage() + $i + 1 }}</td>
              <td class="mono" style="font-weight:950">{{ $b->item->code }}</td>
              <td>{{ $b->item->name }}</td>
              <td>
                @if($b->active)
                  <span class="badge bg-success">Active</span>
                @else
                  <span class="badge bg-secondary">Off</span>
                @endif
              </td>
              <td>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('master.item_boms.edit',$b) }}">Edit</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-muted">Belum ada BOM.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      {{ $boms->links() }}
    </div>
  </div>

</div>
@endsection
