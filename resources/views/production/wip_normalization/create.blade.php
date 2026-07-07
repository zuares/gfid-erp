@extends('layouts.app')

@section('title', 'Normalisasi WIP Baru')

@push('head')
<style>
.wn-wrap{max-width:1000px;margin-inline:auto;padding:.8rem .9rem 3rem}
.wn-title{font-weight:900;font-size:1.15rem;color:#111827;margin-bottom:.2rem}
.wn-sub{color:#64748b;font-size:.84rem;margin-bottom:.9rem}
.wn-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:10px;padding:.9rem 1rem;margin-bottom:.85rem}
.wn-label{font-size:.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.03em;margin-bottom:.25rem;display:block}
.wn-input,.wn-select{width:100%;border:1px solid rgba(148,163,184,.4);border-radius:8px;padding:.45rem .6rem;font-size:.86rem;background:var(--card,#fff);color:#111827}
.wn-row{display:flex;gap:.7rem;flex-wrap:wrap;align-items:flex-end}
.wn-row>div{flex:1;min-width:180px}
.wn-btn{display:inline-flex;align-items:center;gap:.35rem;border-radius:8px;border:1px solid #334155;background:#334155;color:#fff;font-weight:800;font-size:.85rem;padding:.5rem .95rem;cursor:pointer;text-decoration:none}
.wn-btn.ghost{background:transparent;color:#334155}
.wn-table{width:100%;border-collapse:collapse;font-size:.85rem}
.wn-table th{text-align:left;font-size:.68rem;text-transform:uppercase;color:#64748b;font-weight:900;background:rgba(148,163,184,.06);padding:.5rem .6rem;border-bottom:1px solid rgba(148,163,184,.16)}
.wn-table td{padding:.4rem .6rem;border-bottom:1px solid rgba(148,163,184,.1)}
.wn-code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-weight:800;color:#111827}
.wn-muted{color:#94a3b8;font-size:.78rem}
.wn-sys{font-family:ui-monospace,monospace;font-weight:800}
.wn-qtyin{width:100px;text-align:right}
.wn-diff{font-family:ui-monospace,monospace;font-weight:800}
.wn-diff.plus{color:#166534}.wn-diff.minus{color:#b91c1c}
.wn-banner{background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.28);color:#1e40af;border-radius:9px;padding:.55rem .8rem;font-size:.82rem;margin-bottom:.85rem}
.wn-r{text-align:right}
</style>
@endpush

@section('content')
<div class="wn-wrap">
    <div class="wn-title">Normalisasi WIP (Opname WIP)</div>
    <div class="wn-sub">Pilih gudang WIP, masukkan hasil hitung fisik per item. Menyimpan sebagai draft — <b>belum mengubah stok</b> sampai disetujui.</div>

    <div class="wn-banner">ℹ️ Draft ini tidak mengubah stok. Perubahan stok + jurnal selisih (Dr/Cr 1202 vs 6115) baru terjadi setelah <b>approve owner/admin</b>.</div>

    {{-- Pilih gudang --}}
    <form method="GET" action="{{ route('production.wip_normalization.create') }}" class="wn-card">
        <div class="wn-row">
            <div>
                <label class="wn-label">Gudang WIP</label>
                <select name="warehouse_id" class="wn-select" onchange="this.form.submit()">
                    <option value="">— pilih gudang —</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" @selected($selected && $selected->id === $w->id)>{{ $w->code }} — {{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    @if($selected)
    <form method="POST" action="{{ route('production.wip_normalization.store') }}">
        @csrf
        <input type="hidden" name="warehouse_id" value="{{ $selected->id }}">

        <div class="wn-card">
            <div class="wn-row">
                <div>
                    <label class="wn-label">Alasan Normalisasi <span style="color:#b91c1c">*</span></label>
                    <input type="text" name="reason" class="wn-input" required placeholder="mis. opname fisik WIP akhir bulan" value="{{ old('reason') }}">
                </div>
                <div style="max-width:200px">
                    <label class="wn-label">Tanggal Proses Asli</label>
                    <input type="date" name="process_date" class="wn-input" value="{{ old('process_date') }}">
                </div>
            </div>
        </div>

        <div class="wn-card" style="padding:0;overflow:auto">
            @if($items->isEmpty())
                <div style="padding:1.4rem;text-align:center;color:#64748b">Tidak ada stok di gudang {{ $selected->code }}.</div>
            @else
            <table class="wn-table">
                <thead><tr>
                    <th>Item</th><th class="wn-r">Qty Sistem</th><th class="wn-r">Qty Fisik</th><th class="wn-r">Selisih</th><th>Operator</th><th>Catatan</th>
                </tr></thead>
                <tbody>
                @foreach($items as $idx => $it)
                    <tr>
                        <td>
                            <span class="wn-code">{{ $it->item_code }}</span>
                            <div class="wn-muted">{{ $it->item_name }}</div>
                            <input type="hidden" name="lines[{{ $idx }}][item_id]" value="{{ $it->item_id }}">
                        </td>
                        <td class="wn-r"><span class="wn-sys" data-sys="{{ $it->qty_system }}">{{ number_format($it->qty_system,0,',','.') }}</span></td>
                        <td class="wn-r">
                            <input type="number" step="0.001" min="0" class="wn-input wn-qtyin"
                                   name="lines[{{ $idx }}][qty_physical]"
                                   value="{{ number_format($it->qty_system,0,'.','') }}"
                                   oninput="wnDiff(this)">
                        </td>
                        <td class="wn-r"><span class="wn-diff"></span></td>
                        <td>
                            <select name="lines[{{ $idx }}][operator_id]" class="wn-select" style="min-width:130px">
                                <option value="">—</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" name="lines[{{ $idx }}][notes]" class="wn-input" placeholder="opsional"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>

        @if($items->isNotEmpty())
        <div style="display:flex;gap:.6rem;justify-content:flex-end">
            <a href="{{ route('production.wip_normalization.index') }}" class="wn-btn ghost">Batal</a>
            <button type="submit" class="wn-btn">Simpan Draft</button>
        </div>
        @endif
    </form>
    @endif
</div>

<script>
function wnDiff(input){
    var tr = input.closest('tr');
    var sys = parseFloat(tr.querySelector('.wn-sys').dataset.sys || '0');
    var phys = parseFloat(input.value || '0');
    var d = phys - sys;
    var el = tr.querySelector('.wn-diff');
    el.textContent = (d>0?'+':'') + d.toLocaleString('id');
    el.className = 'wn-diff ' + (d>0?'plus':(d<0?'minus':''));
}
document.querySelectorAll('input[name$="[qty_physical]"]').forEach(wnDiff);
</script>
@endsection
