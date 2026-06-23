@extends('layouts.app')

@section('title', 'Purchase Order Baru')

@section('content')

{{-- ── STEP 1: Pilih Jenis PO + Supplier (muncul jika belum ada params) ── --}}
@php
    $stepDone = request()->filled('order_type') && request()->filled('supplier_id');
@endphp

@if (!$stepDone)
<div class="container py-4">
    <div class="d-flex justify-content-center">
    <div style="width:100%; max-width:440px;">
        <div class="card shadow-sm" style="border-radius:18px; overflow:hidden;">
            <div class="card-body p-4">
                <h5 class="fw-black mb-1">Purchase Order Baru</h5>
                <p class="text-muted mb-4" style="font-size:.85rem;">Pilih jenis PO dan supplier terlebih dahulu.</p>

                <form id="step1-form" method="GET" action="{{ route('purchasing.purchase_orders.create') }}">

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.8rem;">Jenis PO</label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach ([
                                'material'      => ['label' => 'Bahan Baku', 'icon' => '🧵'],
                                'finished_good' => ['label' => 'Barang Jadi', 'icon' => '👕'],
                                'packing'       => ['label' => 'Packing',     'icon' => '📦'],
                                'asset'         => ['label' => 'Aset',        'icon' => '🏭'],
                                'service'       => ['label' => 'Service',     'icon' => '🔧'],
                                'jasa'          => ['label' => 'Jasa',        'icon' => '🤝'],
                                'lainnya'       => ['label' => 'Lainnya',     'icon' => '📋'],
                            ] as $val => $opt)
                            <label class="type-card flex-fill text-center p-3 border rounded-3 cursor-pointer"
                                style="cursor:pointer; transition:.15s; min-width:100px;"
                                data-val="{{ $val }}">
                                <input type="radio" name="order_type" value="{{ $val }}"
                                    class="d-none type-radio" {{ $val === 'material' ? 'checked' : '' }}>
                                <div style="font-size:1.4rem; margin-bottom:.25rem;">{{ $opt['icon'] }}</div>
                                <div class="fw-bold" style="font-size:.85rem;">{{ $opt['label'] }}</div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold" style="font-size:.8rem;">Supplier</label>
                        <select name="supplier_id" id="step1-supplier" class="form-select" required>
                            <option value="">— Pilih supplier —</option>
                            @foreach ($suppliers as $sup)
                                <option value="{{ $sup->id }}"
                                    data-po-types="{{ implode(',', $sup->po_types ?? []) }}">
                                    {{ $sup->name }}
                                </option>
                            @endforeach
                        </select>
                        <div id="no-supplier-msg" class="text-muted mt-1 d-none" style="font-size:.78rem;">
                            Tidak ada supplier terdaftar untuk jenis PO ini.
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('purchasing.purchase_orders.index') }}"
                            class="btn btn-outline-secondary flex-fill">Batal</a>
                        <button type="submit" class="btn btn-primary flex-fill fw-bold">Lanjut →</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>

<style>
.type-card { border-color: rgba(148,163,184,.3) !important; }
.type-card.selected { border-color: #2563eb !important; background: rgba(37,99,235,.07); color:#2563eb; }
</style>

<script>
const supSelect = document.getElementById('step1-supplier');
const noSupMsg  = document.getElementById('no-supplier-msg');
const allOpts   = Array.from(supSelect.querySelectorAll('option[value]')); // exclude placeholder

function filterSuppliers(type) {
    let anyVisible = false;
    allOpts.forEach(opt => {
        const types = opt.dataset.poTypes; // "material,finished_good" or "" (all)
        const show  = !types || types === '' || types.split(',').includes(type);
        opt.hidden = !show;
        if (show) anyVisible = true;
    });
    // reset selection if hidden
    if (supSelect.selectedOptions[0]?.hidden) supSelect.value = '';
    noSupMsg.classList.toggle('d-none', anyVisible);
}

document.querySelectorAll('.type-card').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        const radio = card.querySelector('.type-radio');
        radio.checked = true;
        filterSuppliers(radio.value);
    });
    if (card.querySelector('.type-radio').checked) {
        card.classList.add('selected');
        filterSuppliers(card.querySelector('.type-radio').value);
    }
});
</script>

@else

    <div class="container py-3">
        <h1 class="mb-3">Purchase Order Baru</h1>

        <form action="{{ route('purchasing.purchase_orders.store') }}" method="POST">
            @csrf

            @include('purchasing.purchase_orders._form')

            <div class="mt-3 d-flex justify-content-between">
                <a href="{{ route('purchasing.purchase_orders.create') }}" class="btn btn-outline-secondary">
                    &larr; Ganti Jenis / Supplier
                </a>
                <button type="submit" class="btn btn-primary">
                    Simpan PO
                </button>
            </div>
        </form>
    </div>

@endif

@endsection
