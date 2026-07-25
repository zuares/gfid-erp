@extends('layouts.app')
@section('title', 'Kirim Paket Manual')

@push('head')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
    :root{
        --shp-accent:#334155;
        --shp-accent-2:#1f2937;
        --shp-border:rgba(148,163,184,.18);
        --shp-border-strong:rgba(148,163,184,.30);
        --shp-muted:#64748b;
    }
    .page-wrap { max-width: 800px; margin-inline: auto; padding: .75rem .75rem 4rem; background: transparent!important; }

    .card-main {
        background: var(--card, #fff);
        border-radius: 8px;
        border: 1px solid var(--shp-border);
        box-shadow: none;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    body[data-theme="dark"] .card-main {
        border-color: rgba(51,65,85,.85);
        background: rgba(15,23,42,.75);
    }

    .ship-topbar {
        position: sticky;
        top: 0;
        z-index: 300;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .6rem;
        flex-wrap: wrap;
        padding: .45rem .75rem;
        margin-inline: -.75rem;
        margin-bottom: .65rem;
        background: var(--card,#fff);
        border-bottom: 1px solid var(--shp-border);
    }
    body[data-theme="dark"] .ship-topbar { background: var(--card,#0f172a); }
    .title { font-weight: 750; font-size: 1rem; letter-spacing: 0; margin: 0; }
    .sub { color: var(--shp-muted); font-size: .78rem; }
    body[data-theme="dark"] .sub { color: #9ca3af; }

    /* Forms */
    .card-body-padded {
        padding: 1.5rem;
    }
    .form-section-title {
        font-size: .85rem;
        font-weight: 750;
        color: var(--text, #0f172a);
        text-transform: uppercase;
        letter-spacing: .02em;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: .5rem;
        border-bottom: 1px solid var(--shp-border);
        padding-bottom: .5rem;
    }
    body[data-theme="dark"] .form-section-title { color: #f1f5f9; }
    
    .ms-form-group {
        margin-bottom: 1rem;
    }
    .ms-form-group label {
        display: block;
        font-size: .78rem; font-weight: 650;
        color: var(--shp-muted);
        margin-bottom: .35rem;
    }
    .ms-form-group input,
    .ms-form-group textarea {
        width: 100%;
        padding: .5rem .75rem;
        border: 1px solid var(--shp-border-strong);
        border-radius: 6px;
        font-size: .88rem;
        background: var(--card, #fff);
        color: var(--text, #0f172a);
        transition: border-color .2s;
    }
    body[data-theme="dark"] .ms-form-group input,
    body[data-theme="dark"] .ms-form-group textarea {
        background: rgba(30,41,59,.8);
        border-color: rgba(51,65,85,.8);
        color: #f1f5f9;
    }
    .ms-form-group input:focus,
    .ms-form-group textarea:focus {
        outline: none;
        border-color: #3b82f6;
    }
    .ms-form-group input[readonly] {
        background: var(--bg-muted, #f8fafc);
        color: var(--shp-muted);
        cursor: default;
    }
    body[data-theme="dark"] .ms-form-group input[readonly] {
        background: rgba(15,23,42,.6);
    }
    
    /* Items table inside form */
    .table-list { margin-bottom:0; width: 100%; }
    .table-list thead th{
        border-bottom:1px solid var(--shp-border);
        font-size:.68rem;
        text-transform:none;
        letter-spacing:0;
        color:#64748b;
        background: var(--bg-muted,#f8fafc);
        padding:.52rem .62rem;
        font-weight: 650;
    }
    body[data-theme="dark"] .table-list thead th{
        background: rgba(15, 23, 42, 0.98);
        color:#9ca3af;
        border-bottom-color: rgba(51,65,85,.85);
    }
    .table-list tbody td{
        vertical-align:middle;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
        padding:.52rem .62rem;
    }
    body[data-theme="dark"] .table-list tbody td{ border-bottom-color: rgba(51, 65, 85, 0.85); }
    
    .item-qty-input {
        width: 70px;
        text-align: center;
        padding: .4rem;
        border: 1px solid var(--shp-border-strong);
        border-radius: 6px;
        background: var(--card, #fff);
        color: var(--text, #0f172a);
    }
    body[data-theme="dark"] .item-qty-input {
        background: rgba(30,41,59,.8); border-color: rgba(51,65,85,.8); color: #f1f5f9;
    }

    /* Buttons */
    .btn-pill{ border-radius:7px; padding-inline:.78rem; box-shadow:none!important; font-weight:600; font-size:.82rem; }
    .btn-ship-primary{ background:var(--shp-accent)!important; border-color:var(--shp-accent)!important; color:#fff!important; }
    .btn-ship-primary:hover{ background:var(--shp-accent-2)!important; border-color:var(--shp-accent-2)!important; color:#fff!important; }
    .btn-ship-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-ship-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }
    body[data-theme="dark"] .btn-ship-outline { color:#f1f5f9!important; border-color:rgba(51,65,85,.8)!important; }
    body[data-theme="dark"] .btn-ship-outline:hover { background:rgba(30,41,59,.8)!important; }

    .btn-fresh{ border-color:#fecaca; color:#b91c1c; background:transparent; padding: .3rem .5rem; border-radius: 6px; display:inline-flex; align-items:center; justify-content:center;}
    .btn-fresh:hover{ background:#fef2f2; color:#991b1b; border-color:#fca5a5; }

    .ms-error-text { font-size: .75rem; color: #ef4444; font-weight: 600; margin-top: .25rem; }
    .has-error input, .has-error textarea, .has-error .item-suggest-wrap input { border-color: #ef4444 !important; }

    @media (max-width: 768px) {
        .page-wrap{ padding:.5rem .5rem 4rem; }
        .ship-topbar{ margin-inline:-.5rem; padding:.5rem .65rem; }
        .title{ font-size:1.05rem; }
        .sub{ display:none; }
    }
</style>
@endpush

@section('content')
<div class="page-wrap">
    <!-- Topbar -->
    <div class="ship-topbar no-print">
        <div>
            <h1 class="title">Kirim Paket Manual</h1>
            <div class="sub">Buat label pengiriman manual tanpa pesanan marketplace</div>
        </div>
        <div class="controls">
            <a href="{{ route('sales.shipments.index') }}" class="btn btn-sm btn-ship-outline btn-pill">
                Kembali ke Shipments
            </a>
        </div>
    </div>
    
    @if(session('message'))
        <div class="alert alert-{{ session('status') === 'success' ? 'success' : 'danger' }} mb-3 no-print" style="border-radius:8px; border:none; padding:.62rem .75rem; font-size:.84rem;">
            {{ session('message') }}
        </div>
    @endif
    
    @if(session('stock_insufficient'))
        <div class="alert alert-danger mb-3 no-print" style="border-radius:8px; border:none; padding:.62rem .75rem; font-size:.84rem;">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill"></i> Stok Tidak Cukup di WH-RTS!</div>
            <ul class="mb-0 ps-3">
                @foreach (session('stock_insufficient') as $err)
                    <li>
                        <strong>{{ $err['item_code'] }}</strong>: Butuh {{ $err['qty_needed'] }}, 
                        Tersedia {{ $err['stock_available'] }} 
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card-main no-print">
        <form action="{{ route('sales.shipments.manual.store') }}" method="POST" id="formManualShipment" onsubmit="return validateForm()">
            @csrf
            
            <div class="card-body-padded">
                <!-- PENGIRIM -->
                <div class="form-section-title">
                    <i class="bi bi-shop"></i> Data Pengirim
                </div>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="ms-form-group">
                            <label>Nama Pengirim</label>
                            <input type="text" value="GREATFIT.ID" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="ms-form-group">
                            <label>No. Telepon</label>
                            <input type="text" value="081224889319" readonly>
                        </div>
                    </div>
                </div>

                <!-- PENERIMA -->
                <div class="form-section-title">
                    <i class="bi bi-person-fill"></i> Data Penerima
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="ms-form-group" id="grpRecvName">
                            <label>Nama Penerima <span class="text-danger">*</span></label>
                            <input type="text" name="receiverName" id="receiverName" placeholder="Masukkan nama penerima…" value="{{ old('receiverName') }}">
                            @error('receiverName') <div class="ms-error-text">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="ms-form-group" id="grpRecvPhone">
                            <label>No. Penerima <span class="text-danger">*</span></label>
                            <input type="text" name="receiverPhone" id="receiverPhone" placeholder="08xxxxxxxxxx" value="{{ old('receiverPhone') }}">
                            @error('receiverPhone') <div class="ms-error-text">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="ms-form-group mb-4" id="grpRecvAddress">
                    <label>Alamat Penerima <span class="text-danger">*</span></label>
                    <textarea name="receiverAddress" id="receiverAddress" placeholder="Masukkan alamat lengkap penerima…" rows="2">{{ old('receiverAddress') }}</textarea>
                    @error('receiverAddress') <div class="ms-error-text">{{ $message }}</div> @enderror
                </div>

                <!-- ITEM -->
                <div class="form-section-title d-flex justify-content-between">
                    <div><i class="bi bi-box-seam"></i> Item Barang</div>
                    <button type="button" class="btn btn-sm btn-ship-outline btn-pill" onclick="addItemRow()">
                        <i class="bi bi-plus"></i> Tambah Baris
                    </button>
                </div>
                
                @error('items') <div class="alert alert-danger p-2 mb-2" style="font-size:12px;">Minimal 1 item diperlukan.</div> @enderror

                <div class="table-responsive mb-4">
                    <table class="table-list" id="itemsTable">
                        <thead>
                            <tr>
                                <th>Item (Barang Jadi)</th>
                                <th style="width:100px; text-align:center;">Qty</th>
                                <th style="width:40px; text-align:center;"><i class="bi bi-trash"></i></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            @if(old('items'))
                                @foreach(old('items') as $index => $oldItem)
                                    <tr class="item-row">
                                        <td>
                                            <div class="ms-form-group mb-0" id="grpItem{{ $index }}">
                                                <x-item-suggest-input 
                                                    idName="items[{{ $index }}][id]" 
                                                    :idValue="$oldItem['id'] ?? ''"
                                                    displayValue="Item ID: {{ $oldItem['id'] ?? '' }} (Pilih Ulang)"
                                                    placeholder="Ketik kode/nama..." 
                                                    type="finished_good" />
                                            </div>
                                        </td>
                                        <td align="center">
                                            <input type="number" name="items[{{ $index }}][qty]" class="item-qty-input" value="{{ $oldItem['qty'] ?? 1 }}" min="1">
                                        </td>
                                        <td align="center">
                                            <button type="button" class="btn btn-fresh" onclick="removeItemRow(this)"><i class="bi bi-x-lg"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="item-row">
                                    <td>
                                        <div class="ms-form-group mb-0" id="grpItem0">
                                            <x-item-suggest-input 
                                                idName="items[0][id]" 
                                                placeholder="Ketik kode/nama..." 
                                                type="finished_good" />
                                        </div>
                                    </td>
                                    <td align="center">
                                        <input type="number" name="items[0][qty]" class="item-qty-input" value="1" min="1">
                                    </td>
                                    <td align="center">
                                        <button type="button" class="btn btn-fresh" onclick="removeItemRow(this)"><i class="bi bi-x-lg"></i></button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-ship-primary btn-pill px-4 py-2">
                        <i class="bi bi-save"></i> Buat Shipment (Draft)
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let itemRowIndex = {{ old('items') ? count(old('items')) : 1 }};

function addItemRow() {
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    
    const uid = 'js-item-' + Math.random().toString(36).substr(2, 6);
    
    tr.innerHTML = `
        <td>
            <div class="ms-form-group mb-0" id="grpItem${itemRowIndex}">
                <div class="item-suggest-wrap" data-uid="${uid}" data-type="finished_good" data-min-chars="1" data-autofocus="0">
                    <input type="text" id="${uid}" value="" autocomplete="off"
                        class="form-control form-control-sm js-item-suggest-input" placeholder="Ketik kode/nama..." />
                    <input type="hidden" name="items[${itemRowIndex}][id]" value="" class="js-item-suggest-id">
                    <div class="item-suggest-dropdown shadow-sm" style="display:none;"></div>
                </div>
            </div>
        </td>
        <td align="center">
            <input type="number" name="items[${itemRowIndex}][qty]" class="item-qty-input" value="1" min="1">
        </td>
        <td align="center">
            <button type="button" class="btn btn-fresh" onclick="removeItemRow(this)"><i class="bi bi-x-lg"></i></button>
        </td>
    `;
    
    tbody.appendChild(tr);
    itemRowIndex++;
    
    if(typeof window.initAllItemSuggest === 'function') {
        window.initAllItemSuggest();
    } else {
        document.dispatchEvent(new Event('DOMContentLoaded'));
    }
}

function removeItemRow(btn) {
    const tbody = document.getElementById('itemsBody');
    if (tbody.querySelectorAll('.item-row').length > 1) {
        btn.closest('tr').remove();
    } else {
        alert('Minimal harus ada 1 item.');
    }
}

function validateForm() {
    let valid = true;
    const fields = [
        { id: 'receiverName',    grp: 'grpRecvName',    msg: 'Nama penerima wajib diisi' },
        { id: 'receiverPhone',   grp: 'grpRecvPhone',   msg: 'No penerima wajib diisi' },
        { id: 'receiverAddress', grp: 'grpRecvAddress', msg: 'Alamat penerima wajib diisi' },
    ];

    fields.forEach(f => {
        const el  = document.getElementById(f.id);
        const grp = document.getElementById(f.grp);
        const old = grp.querySelector('.ms-error-text');
        if (old) old.remove();
        grp.classList.remove('has-error');

        if (!el.value.trim()) {
            grp.classList.add('has-error');
            const span = document.createElement('div');
            span.className = 'ms-error-text';
            span.textContent = f.msg;
            grp.appendChild(span);
            valid = false;
        }
    });
    
    // Validate items
    const itemRows = document.querySelectorAll('.item-row');
    itemRows.forEach((row, i) => {
        const hiddenId = row.querySelector('.js-item-suggest-id');
        const grp = row.querySelector('.ms-form-group');
        grp.classList.remove('has-error');
        
        if(!hiddenId || !hiddenId.value) {
            grp.classList.add('has-error');
            valid = false;
        }
    });
    
    if(!valid) {
        alert('Mohon lengkapi semua field dan pastikan item sudah dipilih dari dropdown.');
    }

    return valid;
}

document.addEventListener('DOMContentLoaded', function() {
    if(typeof window.initAllItemSuggest === 'function') {
        window.initAllItemSuggest();
    }
});
</script>
@endsection
