@extends('layouts.app')

@section('title', 'WIP Cleanup ' . $adj->code)

@push('head')
<style>
.wc-wrap{max-width:900px;margin-inline:auto;padding:.8rem .9rem 3rem}
.wc-head{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;margin-bottom:.7rem}
.wc-code{font-weight:900;font-size:1.1rem;color:#111827}
.wc-badge{display:inline-flex;align-items:center;border-radius:999px;padding:.14rem .55rem;font-size:.72rem;font-weight:800}
.wc-badge.pending{background:rgba(245,158,11,.12);color:#92400e}
.wc-badge.approved{background:rgba(22,101,52,.12);color:#166534}
.wc-badge.void{background:rgba(148,163,184,.15);color:#475569}
.wc-actionchip{display:inline-flex;align-items:center;border-radius:6px;padding:.14rem .5rem;font-size:.72rem;font-weight:900;background:rgba(59,130,246,.1);color:#1e40af}
.wc-spacer{flex:1}
.wc-btn{display:inline-flex;align-items:center;gap:.35rem;border-radius:8px;border:1px solid #334155;background:#334155;color:#fff;font-weight:800;font-size:.84rem;padding:.5rem .95rem;cursor:pointer;text-decoration:none}
.wc-btn.ghost{background:transparent;color:#334155}
.wc-btn.danger{background:#b91c1c;border-color:#b91c1c}
.wc-banner{border-radius:9px;padding:.55rem .8rem;font-size:.82rem;margin-bottom:.85rem}
.wc-banner.done{background:rgba(22,101,52,.08);border:1px solid rgba(22,101,52,.3);color:#166534}
.wc-banner.void{background:rgba(148,163,184,.12);border:1px solid rgba(148,163,184,.35);color:#475569}
.wc-meta{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.5rem;margin-bottom:.85rem}
.wc-box{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:9px;padding:.55rem .7rem}
.wc-label{font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase}
.wc-val{font-weight:800;color:#111827;margin-top:.1rem;font-size:.9rem}
.wc-muted{color:#94a3b8;font-size:.78rem}
.wc-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:10px;overflow:hidden}
.wc-table{width:100%;border-collapse:collapse;font-size:.85rem}
.wc-table th{text-align:left;font-size:.68rem;text-transform:uppercase;color:#64748b;font-weight:900;background:rgba(148,163,184,.06);padding:.5rem .7rem;border-bottom:1px solid rgba(148,163,184,.16)}
.wc-table td{padding:.5rem .7rem;border-bottom:1px solid rgba(148,163,184,.1);color:#334155}
.wc-itemcode{font-family:ui-monospace,monospace;font-weight:800;color:#111827}
.wc-r{text-align:right}
</style>
@endpush

@section('content')
@php
  $fmt = fn($n)=>number_format((float)$n,0,',','.');
  $isOwnerAdmin = in_array(auth()->user()->role ?? null, ['owner','admin'], true);
  $A = \App\Models\InventoryAdjustment::class;
@endphp
<div class="wc-wrap">
    <div class="wc-head">
        <a href="{{ route('production.wip_cleanup.index') }}" class="wc-btn ghost">← Preview</a>
        <span class="wc-code">{{ $adj->code }}</span>
        <span class="wc-actionchip">{{ $adj->action }}</span>
        <span class="wc-badge {{ $adj->status }}">{{ ucfirst($adj->status) }}</span>
        <span class="wc-spacer"></span>
        @if($adj->status === $A::STATUS_APPROVED && $isOwnerAdmin)
            <form method="POST" action="{{ route('production.wip_cleanup.void', $adj) }}" id="wcVoidForm">
                @csrf
                <input type="hidden" name="void_reason" id="wcVoidReason">
                <button type="submit" class="wc-btn danger" data-confirm="void">Batalkan (Void)</button>
            </form>
        @endif
    </div>

    @if($adj->status === $A::STATUS_APPROVED)
        <div class="wc-banner done">✓ Aksi diproses. Stok dipindah/dikeluarkan lewat inventory_mutations{{ $adj->action==='move' ? '' : ' dan jurnal dibuat' }}.</div>
    @elseif($adj->status === $A::STATUS_VOID)
        <div class="wc-banner void">Dibatalkan. Stok & jurnal telah dikembalikan (reversal).</div>
    @endif

    <div class="wc-meta">
        <div class="wc-box"><div class="wc-label">Dari Gudang</div><div class="wc-val">{{ $adj->warehouse?->code ?? '-' }}</div></div>
        <div class="wc-box"><div class="wc-label">Ke Gudang</div><div class="wc-val">{{ $target?->code ?? '—' }}</div></div>
        <div class="wc-box"><div class="wc-label">Alasan</div><div class="wc-val" style="font-size:.8rem">{{ $adj->reason }}</div></div>
        <div class="wc-box"><div class="wc-label">Oleh</div><div class="wc-val" style="font-size:.82rem">{{ $adj->creator?->name ?? '-' }}</div><div class="wc-muted">{{ $adj->is_legacy ? 'ditandai legacy' : '' }}</div></div>
    </div>

    <div class="wc-card">
        <table class="wc-table">
            <thead><tr><th>Item</th><th class="wc-r">Sebelum</th><th class="wc-r">Sesudah</th><th class="wc-r">Qty Aksi</th></tr></thead>
            <tbody>
            @foreach($adj->lines as $l)
                <tr>
                    <td><span class="wc-itemcode">{{ $l->item?->code ?? '-' }}</span><div class="wc-muted">{{ $l->item?->name }}</div></td>
                    <td class="wc-r">{{ $fmt($l->qty_before) }}</td>
                    <td class="wc-r">{{ $fmt($l->qty_after) }}</td>
                    <td class="wc-r">{{ $fmt(abs($l->qty_change)) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var voidBtn = document.querySelector('[data-confirm="void"]');
    if (voidBtn) {
        voidBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (!window.Swal) return;
            window.Swal.fire({
                icon: 'warning', title: 'Batalkan aksi ini?',
                input: 'text', inputLabel: 'Alasan pembatalan (wajib)',
                showCancelButton: true, confirmButtonText: 'Batalkan', cancelButtonText: 'Tutup',
                confirmButtonColor: '#b91c1c',
                inputValidator: function (v) { return !v ? 'Alasan wajib diisi.' : undefined; },
            }).then(function (r) {
                if (r.isConfirmed) {
                    document.getElementById('wcVoidReason').value = r.value;
                    document.getElementById('wcVoidForm').submit();
                }
            });
        });
    }
});
</script>
@endsection
