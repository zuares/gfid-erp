{{-- resources/views/sales/shipments/cancel.blade.php --}}
@extends('layouts.app')

@section('title', 'Batalkan Shipment · ' . $shipment->code)

@push('head')
<style>
    .sc-page { max-width: 720px; margin: 0 auto; padding: .75rem .75rem 5rem; color: #111827; }
    .sc-topbar { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.55rem .75rem; border-bottom:1px solid rgba(148,163,184,.24); background:#f8fafc; }
    .sc-title { margin:0; font:900 1.05rem ui-monospace,SFMono-Regular,Menlo,monospace; }
    .sc-sub { color:#64748b; font-size:.78rem; font-weight:650; }
    .sc-card { margin-top:.75rem; padding:1rem; border:1px solid rgba(148,163,184,.22); border-radius:8px; background:#fff; }
    .sc-warning { padding:.75rem; border:1px solid rgba(185,28,28,.24); border-radius:8px; color:#991b1b; background:rgba(254,242,242,.8); font-size:.84rem; font-weight:650; }
    .sc-meta { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.5rem; margin-top:.75rem; }
    .sc-meta-item { padding:.55rem .65rem; border:1px solid rgba(148,163,184,.22); border-radius:8px; background:#f8fafc; }
    .sc-label { color:#64748b; font-size:.68rem; font-weight:850; text-transform:uppercase; }
    .sc-value { margin-top:.12rem; color:#111827; font-size:.88rem; font-weight:900; overflow-wrap:anywhere; }
    .sc-form { display:grid; gap:.55rem; margin-top:1rem; }
    .sc-input { width:100%; min-height:44px; border:1px solid rgba(185,28,28,.32); border-radius:8px; padding:.55rem .7rem; font-size:.86rem; }
    .sc-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; margin-top:.85rem; }
    .sc-btn { display:inline-flex; align-items:center; justify-content:center; min-height:40px; border:1px solid rgba(148,163,184,.35); border-radius:8px; padding:.45rem .9rem; color:#334155; background:#fff; font-size:.8rem; font-weight:850; text-decoration:none; cursor:pointer; }
    .sc-btn-danger { border-color:#991b1b; color:#fff; background:#991b1b; }
    @media (max-width:640px) { .sc-meta{grid-template-columns:1fr 1fr}.sc-actions,.sc-actions .sc-btn{width:100%} }
</style>
@endpush

@section('content')
<div class="sc-page">
    <div class="sc-topbar">
        <div>
            <h1 class="sc-title">{{ $shipment->code }}</h1>
            <div class="sc-sub">Jalur pembatalan shipment</div>
        </div>
        <a href="{{ route('sales.shipments.scan_order', $shipment) }}" class="sc-btn">Kembali</a>
    </div>

    <div class="sc-card">
        <div class="sc-warning">
            Shipment akan dibatalkan tanpa melanjutkan proses scan item. Untuk draft, alokasi stok WH-RTS akan dilepas.
        </div>

        <div class="sc-meta">
            <div class="sc-meta-item"><div class="sc-label">Status</div><div class="sc-value">{{ ucfirst($shipment->status) }}</div></div>
            <div class="sc-meta-item"><div class="sc-label">Item</div><div class="sc-value">{{ number_format($shipment->lines->count(), 0, ',', '.') }}</div></div>
            <div class="sc-meta-item"><div class="sc-label">Qty</div><div class="sc-value">{{ number_format((int) $shipment->lines->sum('qty_scanned'), 0, ',', '.') }}</div></div>
        </div>

        <form action="{{ route('sales.shipments.cancel', $shipment) }}" method="POST" class="sc-form" onsubmit="return confirm('Yakin batalkan shipment ini?');">
            @csrf
            <label class="sc-label" for="cancel_reason">Alasan pembatalan</label>
            <input id="cancel_reason" name="cancel_reason" class="sc-input" maxlength="255" required autofocus placeholder="Contoh: salah batch / order dibatalkan">
            <div class="sc-actions">
                <a href="{{ route('sales.shipments.scan_order', $shipment) }}" class="sc-btn">Kembali Scan Order</a>
                <button type="submit" class="sc-btn sc-btn-danger">Batalkan Shipment</button>
            </div>
        </form>
    </div>
</div>
@endsection

@if ($errors->any())
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.GFID || typeof window.GFID.errorAlert !== 'function') return;
    window.GFID.errorAlert(@json(implode("\n", $errors->all())));
});
</script>
@endpush
@endif
