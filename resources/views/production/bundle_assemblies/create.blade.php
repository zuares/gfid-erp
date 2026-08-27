@extends('layouts.app')

@section('title', 'Assembly Bundle Baru')

@push('head')
<style>
.ba-wrap{max-width:900px;margin-inline:auto;padding:.8rem .9rem 3rem}
.ba-head{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;margin-bottom:.8rem}
.ba-title{font-size:1.15rem;font-weight:900;color:#111827;flex:1}
.ba-sub{color:#64748b;font-size:.83rem;width:100%}
.ba-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.2);border-radius:10px;padding:1rem;margin-bottom:.8rem}
.ba-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}
.ba-label{display:block;color:#64748b;font-size:.68rem;font-weight:900;letter-spacing:.04em;text-transform:uppercase;margin-bottom:.25rem}
.ba-input,.ba-select{width:100%;box-sizing:border-box;border:1px solid rgba(148,163,184,.4);border-radius:8px;background:var(--card,#fff);color:#111827;padding:.55rem .65rem;font-size:.86rem}
.ba-help{color:#94a3b8;font-size:.76rem;margin-top:.25rem}
.ba-actions{display:flex;justify-content:flex-end;gap:.55rem;flex-wrap:wrap}
.ba-btn{display:inline-flex;align-items:center;gap:.35rem;border:1px solid #334155;border-radius:8px;background:#334155;color:#fff;padding:.5rem .85rem;font-size:.82rem;font-weight:800;text-decoration:none;cursor:pointer}
.ba-btn.ghost{background:transparent;color:#334155}
.ba-alert{border:1px solid rgba(245,158,11,.35);background:rgba(245,158,11,.08);color:#92400e;border-radius:9px;padding:.65rem .8rem;font-size:.82rem;margin-bottom:.8rem}
@media(max-width:650px){.ba-grid{grid-template-columns:1fr}.ba-card{padding:.8rem}.ba-actions>*{flex:1;justify-content:center}}
</style>
@endpush

@section('content')
<div class="ba-wrap">
    <div class="ba-head">
        <div class="ba-title">Assembly Bundle Baru</div>
        <a href="{{ route('production.bundle_assemblies.index') }}" class="ba-btn ghost">← Kembali</a>
        <div class="ba-sub">Pilih SKU bundle yang memiliki BOM aktif. Simpan dulu sebagai draft untuk meninjau komponen; stok baru berubah setelah posting.</div>
    </div>

    @if($errors->any())
        <div class="ba-alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('production.bundle_assemblies.store') }}">
        @csrf
        <div class="ba-card">
            <div class="ba-grid">
                <div>
                    <label class="ba-label" for="item_id">Item bundle</label>
                    <select id="item_id" name="item_id" class="ba-select" required>
                        <option value="">— pilih item FG/WIP —</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" @selected(old('item_id') == $item->id)>
                                {{ $item->code }} — {{ $item->name }} ({{ $item->stockUnit() }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ba-label" for="warehouse_id">Gudang hasil & konsumsi</label>
                    <select id="warehouse_id" name="warehouse_id" class="ba-select" required>
                        <option value="">— pilih gudang —</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>
                                {{ $warehouse->code }} — {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ba-label" for="qty">Qty bundle</label>
                    <input id="qty" name="qty" type="number" min="0.000001" step="0.000001" class="ba-input" value="{{ old('qty') }}" required>
                    <div class="ba-help">Qty memakai satuan stok item bundle.</div>
                </div>
                <div>
                    <label class="ba-label" for="date">Tanggal assembly</label>
                    <input id="date" name="date" type="date" class="ba-input" value="{{ old('date', now()->toDateString()) }}" required>
                </div>
            </div>
        </div>

        <div class="ba-card">
            <label class="ba-label" for="notes">Catatan</label>
            <textarea id="notes" name="notes" class="ba-input" rows="3" maxlength="1000" placeholder="Opsional">{{ old('notes') }}</textarea>
        </div>

        <div class="ba-actions">
            <a href="{{ route('production.bundle_assemblies.index') }}" class="ba-btn ghost">Batal</a>
            <button type="submit" class="ba-btn">Simpan Draft</button>
        </div>
    </form>
</div>
@endsection
