@extends('layouts.app')

@section('title', 'Purchase Order Baru')

@section('content')

@push('head')
<style>
    .shp-wrap { max-width: 1120px; margin-inline: auto; padding: 0 .75rem 6rem; }
    .shp-topbar {
        position: sticky; top: 0; z-index: 300;
        display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
        padding: .65rem .85rem; background: rgba(255,255,255,.97);
        border-bottom: 1px solid rgba(148,163,184,.22);
        backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
    }
    body[data-theme="dark"] .shp-topbar {
        background: rgba(2,6,23,.96);
        border-bottom-color: rgba(30,64,175,.45);
    }
    .shp-topbar-code { font-weight: 900; font-size: 1.05rem; letter-spacing: .04em; white-space: nowrap; }
    body[data-theme="dark"] .shp-topbar-code { color: #e5e7eb; }
    
    .shp-topbar-spacer { flex: 1; min-width: .5rem; }
    
    .btn-shp-submit {
        border-radius: 7px; font-size: .85rem; font-weight: 600;
        text-transform: none; padding: .35rem .75rem; letter-spacing: 0;
        border: 1px solid #334155; background: #334155; color: #fff;
        transition: all .12s; white-space: nowrap; box-shadow: none;
    }
    .btn-shp-submit:hover { background: #1f2937; border-color: #1f2937; color: #fff; }
    .btn-shp-outline {
        border-radius: 7px; font-size: .85rem; font-weight: 600;
        text-transform: none; padding: .35rem .75rem; letter-spacing: 0;
        border: 1px solid rgba(148,163,184,.35); background: transparent; color: #475569;
        white-space: nowrap; transition: all .12s; box-shadow: none;
    }
    .btn-shp-outline:hover { background: rgba(226,232,240,.7); color: #334155; }
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
        <div class="card shp-table-card" style="border-radius:12px; overflow:hidden;">
            <div class="card-body p-4">
                <h5 class="fw-black mb-1" style="color: #334155;">Purchase Order Baru</h5>
                <p class="text-muted mb-4" style="font-size:.85rem;">Pilih supplier. Item bahan baku, support/ATK, packaging, dan barang jadi dapat digabung dalam satu PO.</p>

                <form id="step1-form" method="GET" action="{{ route('purchasing.purchase_orders.create') }}">
                    {{-- PR-D: carry from_pr through step 1 --}}
                    @if (request()->filled('from_pr'))
                        <input type="hidden" name="from_pr" value="{{ (int) request('from_pr') }}">
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:.8rem;">Jenis PO</label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach ([
                                'material'      => ['label' => 'Pembelian Campuran', 'icon' => '<i class="bi bi-box-seam"></i>'],
                                'packing'       => ['label' => 'Packaging',      'icon' => '<i class="bi bi-archive"></i>'],
                                'service'       => ['label' => 'Operasional',    'icon' => '<i class="bi bi-tools"></i>'],
                                'finished_good' => ['label' => 'Barang Jadi',    'icon' => '<i class="bi bi-bag-check"></i>'],
                            ] as $val => $opt)
                            <label class="type-card flex-fill text-center p-3 border cursor-pointer"
                                style="cursor:pointer; border-radius:12px; transition:.15s; min-width:100px;"
                                data-val="{{ $val }}">
                                <input type="radio" name="order_type" value="{{ $val }}"
                                    class="d-none type-radio" {{ $val === 'material' ? 'checked' : '' }}>
                                <div style="font-size:1.6rem; margin-bottom:.35rem; color:#64748b;" class="type-icon">{!! $opt['icon'] !!}</div>
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
                                    {{ $sup->code ? $sup->code . ' — ' : '' }}{{ $sup->name }}
                                </option>
                            @endforeach
                        </select>
                        <div id="no-supplier-msg" class="text-muted mt-1 d-none" style="font-size:.78rem;">
                            Tidak ada supplier terdaftar untuk jenis PO ini.
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('purchasing.purchase_orders.index') }}"
                            class="btn-shp-outline flex-fill text-center text-decoration-none">Batal</a>
                        <button type="submit" class="btn-shp-submit flex-fill text-center">Lanjut →</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</div>

<style>
.type-card { border-color: rgba(148,163,184,.3) !important; transition: all .15s; }
.type-card:hover { border-color: #475569 !important; background: rgba(148,163,184,.04); }
.type-card.selected { border-color: #334155 !important; background: rgba(15,23,42,.04); color:#334155; }
.type-card.selected .type-icon { color: #334155 !important; }
</style>

<script>
const supSelect = document.getElementById('step1-supplier');
const noSupMsg  = document.getElementById('no-supplier-msg');
const allOpts   = Array.from(supSelect.querySelectorAll('option[value]')); // exclude placeholder

function filterSuppliers(type) {
    allOpts.forEach(opt => {
        opt.hidden = false;
        opt.disabled = false;
    });
    noSupMsg.classList.add('d-none');
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
    {{-- ── STEP 2: Form PO Utama ── --}}
    
    <form id="po-create-form" action="{{ route('purchasing.purchase_orders.store') }}" method="POST">
        @csrf
        <input type="hidden" name="ignore_duplicate" id="ignore_duplicate" value="0">
        
        <div class="shp-topbar mb-3">
            <div class="shp-topbar-code">Buat PO Baru</div>
        </div>
        <div class="shp-wrap pt-2">
            @if(session('duplicate_warning'))
                <div class="alert alert-warning py-3 d-flex align-items-start mb-3" style="border-radius:10px;">
                    <i class="bi bi-exclamation-triangle-fill me-3 fs-4 mt-1"></i>
                    <div>
                        <strong>Peringatan Duplikasi!</strong><br>
                        {{ session('duplicate_warning') }}
                        <div class="mt-2">
                            <button type="button" class="btn btn-warning btn-sm" onclick="document.getElementById('ignore_duplicate').value='1'; document.getElementById('po-create-form').submit();">
                                <i class="bi bi-check2-all me-1"></i> Ya, Lanjutkan Simpan
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            @if(request('from_pr'))
                <input type="hidden" name="from_pr" value="{{ request('from_pr') }}">
                <div class="alert alert-info py-2 d-flex align-items-center mb-3">
                    <i class="bi bi-info-circle-fill me-2 text-primary fs-5"></i>
                    <div>
                        Menyalin item dari <strong>Purchase Request #{{ request('from_pr') }}</strong>
                    </div>
                </div>
            @endif

            @include('purchasing.purchase_orders._form')

            <div class="d-flex justify-content-end gap-2 mt-4 mb-2">
                <a href="{{ route('purchasing.purchase_orders.create') }}" class="btn-shp-outline text-decoration-none">
                    Batal
                </a>
                <button type="submit" class="btn-shp-submit">
                    Simpan PO
                </button>
            </div>
        </div>
    </form>
@endif

@endsection
