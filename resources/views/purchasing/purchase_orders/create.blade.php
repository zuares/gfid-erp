@extends('layouts.app')

@section('title', 'Purchase Order Baru')

@section('content')

@push('head')
<style>
    .po-create-page {
        max-width: 1120px;
        margin-inline: auto;
    }
    .po-create-title {
        font-size: 1.35rem;
        font-weight: 900;
        letter-spacing: -.02em;
    }
    .po-create-shell {
        padding-bottom: calc(72px + 5rem) !important;
    }
    @media (max-width: 767.98px) {
        .po-create-shell {
            padding-inline: .65rem !important;
            padding-top: .65rem !important;
        }
        .po-create-title {
            font-size: 1.05rem;
            margin-bottom: .6rem !important;
        }
    }
    /* FAB */
    .po-fab-wrap {
        position: fixed;
        right: 14px;
        bottom: calc(72px + 12px + env(safe-area-inset-bottom));
        z-index: 1090;
        display: flex;
        gap: 10px;
        align-items: center;
        pointer-events: none;
    }
    .po-fab-wrap .btn { pointer-events: auto; }
    .po-fab-back {
        width: 36px; height: 36px;
        padding: 0;
        border-radius: 999px;
        font-weight: 700;
        font-size: .88rem;
        background: rgba(255,255,255,.72);
        border-color: rgba(148,163,184,.35);
        color: #64748b;
        box-shadow: 0 4px 12px rgba(15,23,42,.10);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }
    .po-fab-back:hover { background: rgba(255,255,255,.92); color: #0f172a; }
    .po-fab-save {
        border-radius: 999px;
        border: none;
        padding: .52rem 1.15rem;
        font-size: .88rem;
        font-weight: 700;
        background: linear-gradient(135deg, #0d6efd 0%, #2563eb 60%, #1d4ed8 100%);
        color: #f9fafb;
        box-shadow:
            0 12px 24px rgba(15,23,42,.32),
            0 0 0 1px rgba(191,219,254,.85);
        display: inline-flex;
        align-items: center;
        gap: .38rem;
        white-space: nowrap;
    }
    .po-fab-save:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff;
    }
</style>
@endpush

{{-- ── STEP 1: Pilih Jenis PO + Supplier (muncul jika belum ada params) ── --}}
@php
    $stepDone = true; // skip step pilih jenis PO, langsung ke form
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
                    {{-- PR-D: carry from_pr through step 1 --}}
                    @if (request()->filled('from_pr'))
                        <input type="hidden" name="from_pr" value="{{ (int) request('from_pr') }}">
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.8rem;">Jenis PO</label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach ([
                                'material'      => ['label' => 'Bahan Produksi', 'icon' => '🧵'],
                                'packing'       => ['label' => 'Packaging',      'icon' => '📦'],
                                'service'       => ['label' => 'Operasional',    'icon' => '🔧'],
                                'finished_good' => ['label' => 'Barang Jadi',    'icon' => '👕'],
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

    <div class="container py-3 po-create-shell">
        <div class="po-create-page">
        <h1 class="po-create-title mb-3">Purchase Order Baru</h1>

        {{-- PR-D: banner jika PO ini dibuat dari PR --}}
        @if (!empty($fromPr))
            <div class="alert alert-info d-flex align-items-center gap-2 py-2 mb-3"
                style="font-size:.88rem; border-radius:10px;">
                <span style="font-size:1rem;">📋</span>
                <div>
                    Dibuat dari <strong>Purchase Request: {{ $fromPr->code }}</strong>
                    @if ($fromPr->notes)
                        — <span class="text-muted">{{ Str::limit($fromPr->notes, 80) }}</span>
                    @endif
                </div>
            </div>
        @endif

        <form id="po-create-form" action="{{ route('purchasing.purchase_orders.store') }}" method="POST">
            @csrf

            {{-- PR-D: hidden purchase_request_id agar store() bisa link PR → PO --}}
            @if (!empty($fromPr))
                <input type="hidden" name="purchase_request_id" value="{{ $fromPr->id }}">
            @endif

            @include('purchasing.purchase_orders._form')

        </form>

        <div class="po-fab-wrap">
            <a href="{{ route('purchasing.purchase_orders.create') }}"
                class="btn btn-outline-secondary po-fab-back" title="Ganti Jenis / Supplier">
                ←
            </a>
            <button type="button" class="po-fab-save"
                onclick="document.getElementById('po-create-form').requestSubmit()">
                <i class="bi bi-check2-circle"></i>
                Simpan PO
            </button>
        </div>
        </div>
    </div>

@endif

@endsection
