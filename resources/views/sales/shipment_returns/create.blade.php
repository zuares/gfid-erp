{{-- resources/views/sales/shipment_returns/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Buat Draft Retur')

@push('head')
<style>
    .sret-wrap {
        max-width: 820px;
        margin-inline: auto;
        padding: .75rem .75rem 4rem;
    }

    .sret-topbar {
        position: sticky;
        top: 0;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin: 0 -.75rem .75rem;
        padding: .55rem .75rem;
        border-bottom: 1px solid rgba(148, 163, 184, .22);
        background: rgba(248, 250, 252, .98);
        backdrop-filter: blur(14px);
    }

    .sret-title {
        margin: 0;
        color: #111827;
        font-size: 1.05rem;
        font-weight: 900;
    }

    .sret-sub {
        color: #64748b;
        font-size: .78rem;
        font-weight: 650;
    }

    .sret-card {
        border: 1px solid rgba(148, 163, 184, .24);
        border-radius: 8px;
        background: #fff;
        padding: .85rem;
    }

    .sret-grid {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) 150px;
        gap: .65rem;
    }

    .sret-field-full {
        grid-column: 1 / -1;
    }

    .sret-label {
        display: block;
        margin-bottom: .25rem;
        color: #64748b;
        font-size: .68rem;
        font-weight: 850;
        text-transform: uppercase;
    }

    .sret-card .form-control,
    .sret-card .form-select {
        min-height: 40px;
        border-radius: 8px;
        border-color: rgba(148, 163, 184, .35);
        font-size: .85rem;
        font-weight: 700;
        box-shadow: none !important;
    }

    .sret-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .65rem;
        margin-top: .85rem;
        flex-wrap: wrap;
    }

    .sret-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        border-radius: 8px;
        padding: .5rem .95rem;
        border: 1px solid rgba(148, 163, 184, .35);
        background: #fff;
        color: #334155;
        font-size: .84rem;
        font-weight: 850;
        text-decoration: none;
    }

    .sret-btn-primary {
        border-color: #111827;
        background: #111827;
        color: #fff;
    }

    .sret-mode-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .55rem;
    }

    .sret-mode-card {
        min-height: 92px;
        padding: .7rem .75rem;
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 8px;
        background: transparent;
        color: #475569;
        text-align: left;
        cursor: pointer;
    }

    .sret-mode-card:hover {
        background: rgba(148, 163, 184, .06);
    }

    .sret-mode-card.active {
        border-color: #334155;
        background: rgba(148, 163, 184, .1);
        box-shadow: 0 0 0 1px #334155;
    }

    .sret-mode-title {
        margin-bottom: .25rem;
        color: #334155;
        font-size: .82rem;
        font-weight: 850;
    }

    .sret-mode-sub {
        color: #64748b;
        font-size: .72rem;
        line-height: 1.45;
    }

    @media (max-width: 680px) {
        .sret-wrap {
            padding: .5rem .5rem 4rem;
        }

        .sret-topbar {
            margin: 0 -.5rem .65rem;
            padding: .5rem;
        }

        .sret-sub {
            display: none;
        }

        .sret-grid {
            grid-template-columns: 1fr;
        }

        .sret-mode-grid {
            grid-template-columns: 1fr;
        }

        .sret-actions {
            align-items: stretch;
        }

        .sret-actions .sret-btn {
            flex: 1 1 100%;
        }
    }
</style>
@endpush

@section('content')
@php
    $storeGroups = $stores->groupBy('return_channel');
    $channelOrder = ['Shopee', 'TikTok', 'Offline'];
@endphp

<div class="sret-wrap">
    <div class="sret-topbar">
        <div>
            <h1 class="sret-title">Buat Draft Retur</h1>
            <div class="sret-sub">Pilih mode scanner terlebih dahulu. Marketplace dan shipment asal bisa dihubungkan kemudian.</div>
        </div>
        <a href="{{ route('sales.shipment_returns.index') }}" class="sret-btn">Kembali</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-2" style="border-radius:8px;font-size:.84rem;">
            @foreach ($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('sales.shipment_returns.store') }}" method="POST" autocomplete="off">
        @csrf
        <input type="hidden" name="shipment_id" value="{{ old('shipment_id', $shipment?->id) }}">
        <input type="hidden" name="order_number" value="{{ old('order_number', $shipment?->code) }}">
        <input type="hidden" name="scan_mode" id="shipmentReturnScanMode" value="{{ old('scan_mode', 'item_first') }}">

        <div class="sret-card">
            <div class="sret-grid">
                <div>
                    <label class="sret-label">Marketplace / Store</label>
                    <select name="store_id" class="form-select" autofocus>
                        <option value="">Belum dihubungkan</option>
                        @foreach ($channelOrder as $channel)
                            @php($channelStores = $storeGroups->get($channel, collect()))
                            @continue($channelStores->isEmpty())

                            <optgroup label="{{ $channel }}">
                                @foreach ($channelStores as $store)
                                    <option value="{{ $store->id }}" @selected(old('store_id', $shipment?->store_id) == $store->id)>
                                        {{ $channel }} - {{ $store->name }} ({{ $store->code ?? '-' }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="sret-label">Tanggal</label>
                    <input type="date" name="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" required>
                </div>

                <div class="sret-field-full">
                    <label class="sret-label">Alasan</label>
                    <input type="text" name="reason" class="form-control" value="{{ old('reason') }}" placeholder="Retur / cancel / salah kirim">
                </div>

                <div class="sret-field-full">
                    <label class="sret-label">Catatan</label>
                    <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Opsional">
                </div>

                <div class="sret-field-full">
                    <label class="sret-label">Mode Scanner</label>
                    <div class="sret-mode-grid" role="radiogroup" aria-label="Mode Scanner Retur">
                        <button type="button" class="sret-mode-card {{ old('scan_mode', 'item_first') === 'order_first' ? 'active' : '' }}" data-scan-mode="order_first" role="radio" aria-checked="{{ old('scan_mode', 'item_first') === 'order_first' ? 'true' : 'false' }}">
                            <div class="sret-mode-title">Scan Order Dulu</div>
                            <div class="sret-mode-sub">Scan nomor order/resi, lalu scan item untuk pencatatan.</div>
                        </button>
                        <button type="button" class="sret-mode-card {{ old('scan_mode', 'item_first') === 'item_first' ? 'active' : '' }}" data-scan-mode="item_first" role="radio" aria-checked="{{ old('scan_mode', 'item_first') === 'item_first' ? 'true' : 'false' }}">
                            <div class="sret-mode-title">Scan Item Dulu</div>
                            <div class="sret-mode-sub">Scan semua item dahulu, lalu scan order tanpa menghapus pencatatan item.</div>
                        </button>
                    </div>
                </div>
            </div>

            <div class="sret-actions">
                <a href="{{ route('sales.shipment_returns.index') }}" class="sret-btn">Batal</a>
                <button type="submit" class="sret-btn sret-btn-primary">Buat Draft & Scan</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const input = document.getElementById('shipmentReturnScanMode');
    const buttons = document.querySelectorAll('[data-scan-mode]');
    buttons.forEach(button => button.addEventListener('click', function () {
        const mode = this.dataset.scanMode === 'order_first' ? 'order_first' : 'item_first';
        if (input) input.value = mode;
        buttons.forEach(candidate => {
            const active = candidate === this;
            candidate.classList.toggle('active', active);
            candidate.setAttribute('aria-checked', active ? 'true' : 'false');
        });
    }));
})();
</script>
@endpush
