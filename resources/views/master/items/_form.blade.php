@push('head')
<style>
    :root{
        --item-accent:#334155;
        --item-accent-2:#1f2937;
        --item-border:rgba(148,163,184,.18);
        --item-muted:#64748b;
    }
    .page-wrap{ max-width:1120px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }

    .card-main{
        background: var(--card, #fff);
        border-radius: 8px;
        border: 1px solid var(--item-border);
        box-shadow: none;
        overflow:hidden;
    }
    body[data-theme="dark"] .card-main{
        border-color: rgba(51,65,85,.85);
        box-shadow: none;
    }

    .item-topbar{
        position:sticky;
        top:0;
        z-index:300;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:.6rem;
        flex-wrap:wrap;
        padding:.45rem .75rem;
        margin-inline:-.75rem;
        margin-bottom:1rem;
        background:var(--card,#fff);
        border-bottom:1px solid var(--item-border);
    }
    body[data-theme="dark"] .item-topbar{ background:var(--card,#0f172a); }
    .title{ font-weight: 750; font-size:1rem; letter-spacing: 0; margin:0; }
    .sub{ color:var(--item-muted); font-size:.78rem; }
    body[data-theme="dark"] .sub{ color:#9ca3af; }

    .btn-pill{ border-radius:7px; padding-inline:.78rem; box-shadow:none!important; font-weight:600; }
    .btn-item-primary{ background:var(--item-accent)!important; border-color:var(--item-accent)!important; color:#fff!important; }
    .btn-item-primary:hover{ background:var(--item-accent-2)!important; border-color:var(--item-accent-2)!important; color:#fff!important; }
    .btn-item-outline{ color:#475569!important; background:transparent!important; border:1px solid rgba(148,163,184,.35)!important; }
    .btn-item-outline:hover{ background:rgba(148,163,184,.08)!important; color:#111827!important; }
    body[data-theme="dark"] .btn-item-outline { color: #cbd5e1!important; }
    body[data-theme="dark"] .btn-item-outline:hover { color: #f8fafc!important; background: rgba(148,163,184,.15)!important; }

    .flash-clean{ border-radius:8px; padding:.62rem .75rem; font-size:.84rem; border:1px solid rgba(148,163,184,.25); }

    .form-label { font-size: .78rem; font-weight: 600; color: #334155; }
    body[data-theme="dark"] .form-label { color: #cbd5e1; }
    .form-control-sm, .form-select-sm { border-radius: 6px; font-size: .82rem; border-color: #cbd5e1; }
    .form-control-sm:focus, .form-select-sm:focus { border-color: #94a3b8; box-shadow: 0 0 0 .2rem rgba(148,163,184,.15); }
    body[data-theme="dark"] .form-control-sm, body[data-theme="dark"] .form-select-sm { background: rgba(15,23,42,.6); border-color: #475569; color: #f1f5f9; }
</style>
@endpush

@php
    /** @var \App\Models\Item|null $item */
    $isEdit = isset($item) && $item?->id;
@endphp

<div class="card card-main mb-4">
    <div class="card-body">
        <h6 class="mb-3 fw-bold">Informasi Dasar</h6>
        <div class="row g-3 mb-4">
            {{-- CODE --}}
            <div class="col-md-3">
                <label class="form-label mb-1">Kode Item</label>
                <input type="text" name="code"
                    class="form-control form-control-sm @error('code') is-invalid @enderror"
                    value="{{ old('code', $item->code ?? '') }}" autocomplete="off" required>
                @error('code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- SKU --}}
            <div class="col-md-3">
                <label class="form-label mb-1">SKU (opsional)</label>
                <input type="text" name="sku"
                    class="form-control form-control-sm @error('sku') is-invalid @enderror"
                    value="{{ old('sku', $item->sku ?? '') }}" autocomplete="off" placeholder="Sama dengan Kode jika kosong">
                @error('sku')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- NAME --}}
            <div class="col-md-4">
                <label class="form-label mb-1">Nama Item</label>
                <input type="text" name="name"
                    class="form-control form-control-sm @error('name') is-invalid @enderror"
                    placeholder="Contoh: Kemeja Flannel Pria"
                    value="{{ old('name', $item->name ?? '') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- UNIT --}}
            <div class="col-md-2">
                <label class="form-label mb-1">Satuan</label>
                <input type="text" name="unit"
                    class="form-control form-control-sm @error('unit') is-invalid @enderror"
                    value="{{ old('unit', $item->unit ?? 'pcs') }}" required>
                @error('unit')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <hr class="mb-4" style="border-color: var(--item-border);">

        <h6 class="mb-3 fw-bold">Klasifikasi & Akuntansi</h6>
        <div class="row g-3">
            {{-- TYPE --}}
            <div class="col-md-3">
                <label class="form-label mb-1">Tipe Item</label>
                <select name="type" data-item-type class="form-select form-select-sm @error('type') is-invalid @enderror" required>
                    @php
                        $types = [
                            'material' => 'Bahan Baku / Material',
                            'wip' => 'Setengah Jadi (WIP)',
                            'finished_good' => 'Barang Jadi (Produk)',
                        ];
                    @endphp
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" @selected(old('type', $item->type ?? 'material') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('type')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            {{-- CATEGORY --}}
            <div class="col-md-3">
                <label class="form-label mb-1">Kategori</label>
                <select name="item_category_id"
                    data-item-category class="form-select form-select-sm @error('item_category_id') is-invalid @enderror">
                    <option value="">- Tidak Ada -</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" data-kind="{{ $cat->kind }}" @selected(old('item_category_id', $item->item_category_id ?? null) == $cat->id)>
                            {{ $cat->code }} — {{ $cat->name }} ({{ $cat->kind_label }})
                        </option>
                    @endforeach
                </select>
                @error('item_category_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            {{-- PRODUCTION SOURCE --}}
            <div class="col-md-3" data-production-source-wrap>
                <label class="form-label mb-1">Sumber Produksi</label>
                <select name="production_source" data-production-source
                    class="form-select form-select-sm @error('production_source') is-invalid @enderror">
                    @foreach (\App\Models\Item::productionSourceLabels() as $key => $label)
                        <option value="{{ $key }}" @selected(old('production_source', $item->production_source ?? \App\Models\Item::PRODUCTION_BUY) === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('production_source')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            {{-- DEFAULT ALLOCATION --}}
            <div class="col-md-3">
                <label class="form-label mb-1">Sifat Pembelian</label>
                <select name="default_allocation" id="default_allocation" class="form-select form-select-sm @error('default_allocation') is-invalid @enderror">
                    <option value="hpp" @selected(old('default_allocation', $item->default_allocation ?? 'hpp') === 'hpp')>Masuk Stok (Aset)</option>
                    <option value="expense" @selected(old('default_allocation', $item->default_allocation ?? '') === 'expense')>Langsung Biaya (Expense)</option>
                </select>
                @error('default_allocation')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            {{-- DEFAULT EXPENSE ACCOUNT --}}
            <div class="col-md-4" id="expense_account_wrap" style="display: none;">
                <label class="form-label mb-1">Akun Biaya Pembelian</label>
                <select name="default_expense_account_id" id="default_expense_account_id" class="form-select form-select-sm @error('default_expense_account_id') is-invalid @enderror">
                    <option value="">- Pilih Akun -</option>
                    @foreach ($expenseAccounts ?? [] as $acc)
                        <option value="{{ $acc->id }}" @selected(old('default_expense_account_id', $item->default_expense_account_id ?? '') == $acc->id)>
                            {{ $acc->code }} — {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
                @error('default_expense_account_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            {{-- ACTIVE FLAG --}}
            <div class="col-md-2" id="active_wrap">
                <label class="form-label mb-1 d-block">Status Item</label>
                <input type="hidden" name="active" value="0">
                <div class="form-check form-switch mt-2">
                    <input type="checkbox" class="form-check-input" name="active" value="1" id="activeCheck"
                        @checked(old('active', $item->active ?? 1) == 1)>
                    <label class="form-check-label small text-muted" for="activeCheck">Aktif / Bisa dipakai</label>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="card card-main mb-4">
    <div class="card-body">
        <h6 class="mb-3 fw-bold">Harga & Biaya Dasar</h6>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label mb-1">Harga Beli Terakhir (Rp)</label>
                <input type="number" step="0.01" name="last_purchase_price"
                    class="form-control form-control-sm @error('last_purchase_price') is-invalid @enderror"
                    value="{{ old('last_purchase_price', $item->last_purchase_price ?? '') }}" placeholder="0">
                <div class="form-text small" style="font-size: .7rem;">Bisa otomatis terupdate saat ada PO.</div>
                @error('last_purchase_price')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label mb-1">HPP Sementara (Rp / unit)</label>
                <input type="number" step="0.01" name="unit_cost"
                    class="form-control form-control-sm @error('unit_cost') is-invalid @enderror"
                    value="{{ old('unit_cost', $activeSnapshot->unit_cost ?? '') }}" placeholder="0">
                @error('unit_cost')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="col-md-4">
                <label class="form-label mb-1">Catatan HPP (opsional)</label>
                <input type="text" name="hpp_notes"
                    class="form-control form-control-sm @error('hpp_notes') is-invalid @enderror"
                    value="{{ old('hpp_notes', $activeSnapshot->notes ?? '') }}" placeholder="Contoh: HPP awal dari faktur bulan lalu">
                @error('hpp_notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            @if(isset($activeSnapshot))
                <div class="col-12 mt-1">
                    <div class="small text-muted" style="font-size: .75rem;">
                        HPP aktif saat ini: <strong>Rp {{ number_format($activeSnapshot->unit_cost, 0, ',', '.') }}</strong>
                        <span class="opacity-75">(Diperbarui: {{ $activeSnapshot->snapshot_date?->format('d/m/Y') ?? '-' }})</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- === Include BARCODE FORM === --}}
@include('master.items._barcodes_form')

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('master.items.index') }}" class="btn btn-sm btn-item-outline btn-pill px-4" style="padding-top: .45rem; padding-bottom: .45rem;">
        Batal
    </a>
    <button class="btn btn-sm btn-item-primary btn-pill px-4" style="padding-top: .45rem; padding-bottom: .45rem;">
        Simpan Item
    </button>
</div>

@push('scripts')
    <script>
        (function () {
            const typeSelect = document.querySelector('[data-item-type]');
            const categorySelect = document.querySelector('[data-item-category]');
            const productionSourceWrap = document.querySelector('[data-production-source-wrap]');
            const productionSourceSelect = document.querySelector('[data-production-source]');
            const help = document.querySelector('[data-item-category-help]');
            if (!typeSelect || !categorySelect) return;

            const labels = {
                finished_good: 'Kategori produk jadi ditampilkan lebih dulu.',
                wip: 'WIP biasanya mengikuti kategori produk jadi.',
                material: 'Kategori bahan, pendukung, accessories, dan packaging ditampilkan lebih dulu.',
            };

            function allowedKinds(type) {
                if (type === 'finished_good' || type === 'wip') return ['product'];
                if (type === 'material') return ['material', 'support', 'accessory', 'packaging', 'other'];
                return ['product', 'material', 'support', 'accessory', 'packaging', 'other'];
            }

            function refreshCategoryOptions() {
                const allowed = allowedKinds(typeSelect.value);
                const selected = categorySelect.selectedOptions[0];
                Array.from(categorySelect.options).forEach(option => {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }
                    const keepSelected = selected && option.value === selected.value;
                    option.hidden = !allowed.includes(option.dataset.kind || 'other') && !keepSelected;
                });

                const usesProductionSource = typeSelect.value === 'finished_good' || typeSelect.value === 'wip';
                if (productionSourceWrap) productionSourceWrap.hidden = !usesProductionSource;
                if (productionSourceSelect) productionSourceSelect.disabled = !usesProductionSource;
            }

            typeSelect.addEventListener('change', refreshCategoryOptions);
            refreshCategoryOptions();

            const allocationSelect = document.getElementById('default_allocation');
            const expenseWrap = document.getElementById('expense_account_wrap');
            if (allocationSelect && expenseWrap) {
                function toggleExpense() {
                    expenseWrap.style.display = allocationSelect.value === 'expense' ? 'block' : 'none';
                }
                allocationSelect.addEventListener('change', toggleExpense);
                toggleExpense();
            }
        })();
    </script>
@endpush
