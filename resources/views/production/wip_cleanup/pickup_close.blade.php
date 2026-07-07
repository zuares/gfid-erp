@extends('layouts.app')

@section('title', 'Tutup Pickup Menggantung')

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
    <div class="wc-title">Tutup Pickup Menggantung</div>
    <div class="wc-sub">Selesaikan sisa ambil-jahit yang belum disetor. Langsung diproses (owner/admin) dan bisa dibatalkan (void).</div>

    <form method="POST" action="{{ route('production.wip_cleanup.pickup_close_store') }}" id="pcForm">
        @csrf
        <input type="hidden" name="pickup_line_id" value="{{ $line->id }}">

        <div class="wc-card">
            <div class="wc-item">
                <div>
                    <span class="wc-code">{{ $line->finishedItem?->code ?? '-' }}</span>
                    <div class="wc-muted">{{ $line->finishedItem?->name }}</div>
                    <div class="wc-muted">Ambil: {{ $line->pickup?->code }} · Bundle: {{ $line->bundle?->bundle_code ?? '—' }}</div>
                </div>
                <div style="text-align:right">
                    <div class="wc-label" style="margin:0">Sisa Outstanding</div>
                    <div class="wc-sys">{{ number_format($outstanding,0,',','.') }}</div>
                </div>
            </div>
        </div>

        <div class="wc-card">
            <label class="wc-label">Arti "Tutup"</label>
            <select name="action" id="pcAction" class="wc-select" onchange="pcHint()">
                <option value="settle">Disetor — dianggap selesai, lanjut ke WIP-FIN</option>
                <option value="write_off">Write-off — sisa hilang (Dr 6120 Kerugian)</option>
                <option value="cancel">Batalkan — pick dibatalkan, stok balik ke WIP-CUT</option>
            </select>
            <div class="wc-hint" id="pcHint"></div>

            <label class="wc-label">Qty</label>
            <input type="number" step="0.001" min="0.001" max="{{ $outstanding }}" name="qty" class="wc-input" value="{{ $outstanding }}">

            <label class="wc-label">Alasan <span style="color:#b91c1c">*</span></label>
            <input type="text" name="reason" class="wc-input" required placeholder="mis. barang sudah lama tidak disetor / hilang">
        </div>

        <div style="display:flex;gap:.6rem;justify-content:flex-end">
            <a href="{{ route('production.wip_cleanup.index') }}" class="wc-btn ghost">Batal</a>
            <button type="submit" class="wc-btn">Tutup Pickup</button>
        </div>
    </form>
</div>

<script>
var PC_HINTS = {
    settle: 'Sisa qty dianggap sudah disetor OK: stok WIP-SEW → WIP-FIN, qty_returned_ok bertambah. Tanpa jurnal (nilai tetap WIP).',
    write_off: 'Sisa qty dianggap hilang: keluar dari WIP-SEW. Jurnal Dr 6120 Kerugian / Cr 1202 WIP. Perlu migrasi qty_closed.',
    cancel: 'Batalkan pick: stok WIP-SEW → kembali WIP-CUT, kapasitas bundle dikembalikan. Tanpa jurnal. Perlu migrasi qty_closed.'
};
function pcHint(){ document.getElementById('pcHint').textContent = PC_HINTS[document.getElementById('pcAction').value] || ''; }
pcHint();
</script>
@endsection
