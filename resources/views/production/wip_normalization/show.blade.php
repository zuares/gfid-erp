@extends('layouts.app')

@section('title', 'Normalisasi WIP ' . $adj->code)

@push('head')
<style>
.wn-wrap{max-width:1000px;margin-inline:auto;padding:.8rem .9rem 3rem}
.wn-head{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;margin-bottom:.7rem}
.wn-code{font-weight:900;font-size:1.1rem;color:#111827}
.wn-badge{display:inline-flex;align-items:center;border-radius:999px;padding:.14rem .55rem;font-size:.72rem;font-weight:800}
.wn-badge.pending{background:rgba(245,158,11,.12);color:#92400e}
.wn-badge.approved{background:rgba(22,101,52,.12);color:#166534}
.wn-badge.void{background:rgba(148,163,184,.15);color:#475569}
.wn-spacer{flex:1}
.wn-btn{display:inline-flex;align-items:center;gap:.35rem;border-radius:8px;border:1px solid #166534;background:#166534;color:#fff;font-weight:800;font-size:.84rem;padding:.5rem .95rem;cursor:pointer;text-decoration:none}
.wn-btn.ghost{background:transparent;color:#334155;border-color:#334155}
.wn-btn.danger{background:#b91c1c;border-color:#b91c1c}
.wn-meta{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.5rem;margin-bottom:.85rem}
.wn-box{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:9px;padding:.55rem .7rem}
.wn-label{font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase}
.wn-val{font-weight:800;color:#111827;margin-top:.1rem;font-size:.9rem}
.wn-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:10px;overflow:hidden}
.wn-table{width:100%;border-collapse:collapse;font-size:.85rem}
.wn-table th{text-align:left;font-size:.68rem;text-transform:uppercase;color:#64748b;font-weight:900;background:rgba(148,163,184,.06);padding:.5rem .7rem;border-bottom:1px solid rgba(148,163,184,.16)}
.wn-table td{padding:.5rem .7rem;border-bottom:1px solid rgba(148,163,184,.1);color:#334155}
.wn-itemcode{font-family:ui-monospace,monospace;font-weight:800;color:#111827}
.wn-muted{color:#94a3b8;font-size:.78rem}
.wn-r{text-align:right}
.wn-diff{font-family:ui-monospace,monospace;font-weight:800}
.wn-diff.plus{color:#166534}.wn-diff.minus{color:#b91c1c}
.wn-banner{border-radius:9px;padding:.55rem .8rem;font-size:.82rem;margin-bottom:.85rem}
.wn-banner.draft{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.3);color:#92400e}
.wn-banner.done{background:rgba(22,101,52,.08);border:1px solid rgba(22,101,52,.3);color:#166534}
</style>
@endpush

@section('content')
@php $fmt = fn($n)=>number_format((float)$n,0,',','.'); @endphp
<div class="wn-wrap">
    <div class="wn-head">
        <a href="{{ route('production.wip_normalization.index') }}" class="wn-btn ghost">← Daftar</a>
        <span class="wn-code">{{ $adj->code }}</span>
        <span class="wn-badge {{ $adj->status }}">{{ ucfirst($adj->status) }}</span>
        <span class="wn-spacer"></span>
        @php $isOwnerAdmin = in_array(auth()->user()->role ?? null, ['owner','admin'], true); @endphp
        @if($adj->status !== \App\Models\InventoryAdjustment::STATUS_APPROVED && $adj->status !== \App\Models\InventoryAdjustment::STATUS_VOID)
            @if($isOwnerAdmin)
            <form method="POST" action="{{ route('production.wip_normalization.approve', $adj) }}" id="wnApproveForm">
                @csrf
                <button type="submit" class="wn-btn" data-confirm="approve">Approve &amp; Generate</button>
            </form>
            @endif
        @elseif($adj->status === \App\Models\InventoryAdjustment::STATUS_APPROVED && $isOwnerAdmin)
            <form method="POST" action="{{ route('production.wip_normalization.void', $adj) }}" id="wnVoidForm">
                @csrf
                <input type="hidden" name="void_reason" id="wnVoidReason">
                <button type="submit" class="wn-btn danger" data-confirm="void">Batalkan (Void)</button>
            </form>
        @endif
    </div>

    @if($adj->status === \App\Models\InventoryAdjustment::STATUS_APPROVED)
        <div class="wn-banner done">✓ Sudah disetujui. Stok dikoreksi lewat inventory_mutations dan jurnal selisih (1202 vs 6115) telah dibuat.</div>
    @elseif($adj->status === \App\Models\InventoryAdjustment::STATUS_VOID)
        <div class="wn-banner void" style="background:rgba(148,163,184,.12);border:1px solid rgba(148,163,184,.35);color:#475569">Dibatalkan. Stok & jurnal telah dikembalikan (reversal).</div>
    @else
        <div class="wn-banner draft">Draft — belum mengubah stok. Menunggu approval owner/admin.</div>
    @endif

    <div class="wn-meta">
        <div class="wn-box"><div class="wn-label">Gudang</div><div class="wn-val">{{ $adj->warehouse?->code ?? '-' }}</div><div class="wn-muted">{{ $adj->warehouse?->name }}</div></div>
        <div class="wn-box"><div class="wn-label">Alasan</div><div class="wn-val" style="font-size:.82rem">{{ $adj->reason ?? '-' }}</div></div>
        <div class="wn-box"><div class="wn-label">Tgl Proses Asli</div><div class="wn-val">{{ $adj->process_date?->format('d M Y') ?? '-' }}</div></div>
        <div class="wn-box"><div class="wn-label">Dibuat / Disetujui</div><div class="wn-val" style="font-size:.82rem">{{ $adj->creator?->name ?? '-' }}</div><div class="wn-muted">{{ $adj->approver?->name ? 'oleh '.$adj->approver->name : 'belum approve' }}</div></div>
    </div>

    <div class="wn-card">
        <table class="wn-table">
            <thead><tr>
                <th>Item</th><th class="wn-r">Sistem</th><th class="wn-r">Fisik</th><th class="wn-r">Selisih</th><th>Operator</th><th>Catatan</th>
            </tr></thead>
            <tbody>
            @foreach($adj->lines as $l)
                @php $d = (float)$l->qty_change; @endphp
                <tr>
                    <td><span class="wn-itemcode">{{ $l->item?->code ?? '-' }}</span><div class="wn-muted">{{ $l->item?->name }}</div></td>
                    <td class="wn-r">{{ $fmt($l->qty_before) }}</td>
                    <td class="wn-r">{{ $fmt($l->qty_physical ?? $l->qty_after) }}</td>
                    <td class="wn-r"><span class="wn-diff {{ $d>0?'plus':($d<0?'minus':'') }}">{{ ($d>0?'+':'').$fmt($d) }}</span></td>
                    <td>{{ $l->operator?->name ?? '—' }}</td>
                    <td class="wn-muted">{{ $l->notes ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Konfirmasi APPROVE
    var approveBtn = document.querySelector('[data-confirm="approve"]');
    if (approveBtn) {
        approveBtn.addEventListener('click', function (e) {
            if (approveBtn.dataset.ok === '1') return; // sudah dikonfirmasi
            e.preventDefault();
            if (!window.Swal) { document.getElementById('wnApproveForm').submit(); return; }
            window.Swal.fire({
                icon: 'warning',
                title: 'Approve normalisasi?',
                text: 'Stok akan dikoreksi via inventory_mutations dan jurnal selisih (1202 vs 6115) dibuat. Bisa dibatalkan (void) bila salah.',
                showCancelButton: true,
                confirmButtonText: 'Ya, approve',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#166534',
            }).then(function (r) {
                if (r.isConfirmed) { approveBtn.dataset.ok = '1'; document.getElementById('wnApproveForm').submit(); }
            });
        });
    }

    // Konfirmasi VOID (dengan alasan)
    var voidBtn = document.querySelector('[data-confirm="void"]');
    if (voidBtn) {
        voidBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (!window.Swal) return;
            window.Swal.fire({
                icon: 'warning',
                title: 'Batalkan normalisasi?',
                input: 'text',
                inputLabel: 'Alasan pembatalan (wajib)',
                inputPlaceholder: 'mis. salah input hitung fisik',
                showCancelButton: true,
                confirmButtonText: 'Batalkan',
                cancelButtonText: 'Tutup',
                confirmButtonColor: '#b91c1c',
                inputValidator: function (v) { return !v ? 'Alasan wajib diisi.' : undefined; },
            }).then(function (r) {
                if (r.isConfirmed) {
                    document.getElementById('wnVoidReason').value = r.value;
                    document.getElementById('wnVoidForm').submit();
                }
            });
        });
    }
});
</script>
@endsection
