@extends('layouts.app')

@section('title', 'Aksi WIP Cleanup')

@push('head')
<style>
.wc-wrap{max-width:640px;margin-inline:auto;padding:.9rem .9rem 3rem}
.wc-title{font-weight:900;font-size:1.12rem;color:#111827;margin-bottom:.15rem}
.wc-sub{color:#64748b;font-size:.84rem;margin-bottom:.9rem}
.wc-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.2);border-radius:11px;padding:1rem 1.1rem;margin-bottom:.85rem}
.wc-item{display:flex;align-items:center;gap:.6rem;justify-content:space-between;flex-wrap:wrap}
.wc-code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-weight:900;color:#111827}
.wc-muted{color:#94a3b8;font-size:.8rem}
.wc-sys{font-family:ui-monospace,monospace;font-weight:900;font-size:1.1rem;color:#111827}
.wc-label{font-size:.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.03em;margin:.7rem 0 .25rem;display:block}
.wc-input,.wc-select{width:100%;border:1px solid rgba(148,163,184,.4);border-radius:8px;padding:.5rem .65rem;font-size:.9rem;background:var(--card,#fff);color:#111827}
.wc-btn{display:inline-flex;align-items:center;gap:.35rem;border-radius:8px;border:1px solid #334155;background:#334155;color:#fff;font-weight:800;font-size:.9rem;padding:.55rem 1.1rem;cursor:pointer;text-decoration:none}
.wc-btn.ghost{background:transparent;color:#334155}
.wc-hint{font-size:.76rem;color:#64748b;margin-top:.35rem;line-height:1.4}
</style>
@endpush

@section('content')
<div class="wc-wrap">
    <div class="wc-title">Aksi WIP Cleanup</div>
    <div class="wc-sub">Tentukan tindakan untuk stok WIP menggantung ini. Aksi langsung diproses (stok + jurnal) dan bisa dibatalkan (void) bila salah.</div>

    <form method="POST" action="{{ route('production.wip_cleanup.store_action') }}" id="wcForm">
        @csrf
        <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
        <input type="hidden" name="item_id" value="{{ $item->id }}">

        <div class="wc-card">
            <div class="wc-item">
                <div>
                    <span class="wc-code">{{ $item->code }}</span>
                    <div class="wc-muted">{{ $item->name }}</div>
                    <div class="wc-muted">Gudang: {{ $warehouse->code }} — {{ $warehouse->name }}</div>
                </div>
                <div style="text-align:right">
                    <div class="wc-label" style="margin:0">Qty Sistem</div>
                    <div class="wc-sys">{{ number_format($system,0,',','.') }}</div>
                </div>
            </div>
        </div>

        <div class="wc-card">
            <label class="wc-label">Aksi</label>
            <select name="action" id="wcAction" class="wc-select" onchange="wcToggle()">
                <option value="move">Move Location — pindah ke WIP lain (nilai tetap)</option>
                <option value="finish">Mark as Finished — masuk WH-PRD (Dr 1203)</option>
                <option value="reject">Mark Reject — ke gudang REJECT (Dr 1204)</option>
                <option value="write_off">Write Off — fisik tidak ditemukan (Dr 6120)</option>
                <option value="close_legacy">Close as Legacy — koreksi legacy (Dr 6116)</option>
            </select>
            <div class="wc-hint" id="wcHint"></div>

            <div id="wcTargetWrap" style="display:none">
                <label class="wc-label">Gudang Tujuan (WIP)</label>
                <select name="target_warehouse_id" class="wc-select">
                    <option value="">— pilih —</option>
                    @foreach($wipTargets as $t)
                        <option value="{{ $t->id }}">{{ $t->code }} — {{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <label class="wc-label">Qty</label>
            <input type="number" step="0.001" min="0.001" max="{{ $system }}" name="qty" class="wc-input" value="{{ $system }}">

            <label class="wc-label">Tanggal Proses Asli</label>
            <input type="date" name="process_date" class="wc-input">

            <label class="wc-label">Alasan <span style="color:#b91c1c">*</span></label>
            <input type="text" name="reason" class="wc-input" required placeholder="mis. fisik tidak ditemukan saat opname">
        </div>

        <div style="display:flex;gap:.6rem;justify-content:flex-end">
            <a href="{{ route('production.wip_cleanup.index') }}" class="wc-btn ghost">Batal</a>
            <button type="submit" class="wc-btn">Proses Aksi</button>
        </div>
    </form>
</div>

<script>
var WC_HINTS = {
    move: 'Pindah stok ke gudang WIP lain. Nilai tetap di WIP (1202), tidak ada jurnal laba/rugi.',
    finish: 'Anggap selesai → masuk WH-PRD. Jurnal: Dr 1203 Barang Jadi / Cr 1202 WIP.',
    reject: 'Buang ke gudang REJECT sebagai barang cacat. Jurnal: Dr 1204 / Cr 1202.',
    write_off: 'Fisik tidak ditemukan → keluarkan dari WIP. Jurnal: Dr 6120 Kerugian / Cr 1202.',
    close_legacy: 'Tutup sebagai data legacy. Jurnal: Dr 6116 Koreksi Legacy / Cr 1202.'
};
function wcToggle(){
    var a = document.getElementById('wcAction').value;
    document.getElementById('wcHint').textContent = WC_HINTS[a] || '';
    document.getElementById('wcTargetWrap').style.display = (a === 'move') ? '' : 'none';
}
wcToggle();
</script>
@endsection
