@php
    /** @var \App\Models\Item|null $item */
    $isEdit = isset($item) && $item?->id;
@endphp

<div class="card shadow-sm mb-4"
    style="border-radius: 14px; background: var(--card); border: 1px solid rgba(148,163,184,.28);">
    <div class="card-header fw-semibold py-2" style="font-size:.9rem; letter-spacing:-.01em;">
        {{ $isEdit ? 'Edit Item' : 'Tambah Item Baru' }}
    </div>

    <div class="card-body pb-3">
        <div class="row g-3">

            {{-- CODE --}}
            <div class="col-md-3">
                <label class="form-label fw-semibold small mb-1">Kode Item</label>
                <input type="text" name="code"
                    class="form-control form-control-sm @error('code') is-invalid @enderror"
                    value="{{ old('code', $item->code ?? '') }}" autocomplete="off" required>
                @error('code')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- NAME --}}
            <div class="col-md-5">
                <label class="form-label fw-semibold small mb-1">Nama Item</label>
                <input type="text" name="name"
                    class="form-control form-control-sm @error('name') is-invalid @enderror"
                    value="{{ old('name', $item->name ?? '') }}" required>
                @error('name')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- UNIT --}}
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1">Satuan</label>
                <input type="text" name="unit"
                    class="form-control form-control-sm @error('unit') is-invalid @enderror"
                    value="{{ old('unit', $item->unit ?? 'pcs') }}" required>
                @error('unit')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- ACTIVE FLAG --}}
            <div class="col-md-2">
                <label class="form-label fw-semibold small mb-1 d-block">Aktif?</label>
                <input type="hidden" name="active" value="0">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" name="active" value="1"
                        @checked(old('active', $item->active ?? 1) == 1)>
                    <label class="form-check-label small text-muted">
                        Centang jika item aktif
                    </label>
                </div>
            </div>

            {{-- SKU (opsional, kompatibel dengan index yang tampilkan SKU) --}}
            <div class="col-md-3">
                <label class="form-label fw-semibold small mb-1">SKU (opsional)</label>
                <input type="text" name="sku"
                    class="form-control form-control-sm @error('sku') is-invalid @enderror"
                    value="{{ old('sku', $item->sku ?? '') }}" autocomplete="off">
                @error('sku')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- TYPE --}}
            <div class="col-md-4">
                <label class="form-label fw-semibold small mb-1">Tipe Item</label>
                <select name="type" data-item-type class="form-select form-select-sm @error('type') is-invalid @enderror" required>
                    @php
                        $types = [
                            'material' => 'Material',
                            'wip' => 'WIP',
                            'finished_good' => 'Finished Good',
                        ];
                    @endphp
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" @selected(old('type', $item->type ?? 'material') === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('type')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- CATEGORY --}}
            <div class="col-md-3">
                <label class="form-label fw-semibold small mb-1">Kategori</label>
                <select name="item_category_id"
                    data-item-category class="form-select form-select-sm @error('item_category_id') is-invalid @enderror">
                    <option value="">- Tidak Ada -</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" data-kind="{{ $cat->kind }}" @selected(old('item_category_id', $item->item_category_id ?? null) == $cat->id)>
                            {{ $cat->code }} — {{ $cat->name }} ({{ $cat->kind_label }})
                        </option>
                    @endforeach
                </select>
                <div class="form-text small" data-item-category-help>
                    Pilih tipe item dulu agar pilihan kategori lebih relevan.
                </div>
                @error('item_category_id')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- PRODUCTION SOURCE --}}
            <div class="col-md-2" data-production-source-wrap>
                <label class="form-label fw-semibold small mb-1">Sumber Produksi</label>
                <select name="production_source" data-production-source
                    class="form-select form-select-sm @error('production_source') is-invalid @enderror">
                    @foreach (\App\Models\Item::productionSourceLabels() as $key => $label)
                        <option value="{{ $key }}" @selected(old('production_source', $item->production_source ?? \App\Models\Item::PRODUCTION_BUY) === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text small">Untuk FG/WIP.</div>
                @error('production_source')
                    <div class="invalid-feedback d-block">
                        {{ $message }}
                    </div>
                @enderror
            </div>

        </div>
    </div>
</div>

{{-- === Include BARCODE FORM === --}}
@include('master.items._barcodes_form')

<div class="d-flex justify-content-end gap-2 mt-2">
    <a href="{{ route('master.items.index') }}" class="btn btn-outline-secondary btn-sm px-3">
        Batal
    </a>
    <button class="btn btn-primary btn-sm px-4 py-2" style="border-radius: 10px;">
        Simpan
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
                if (help) help.textContent = labels[typeSelect.value] || 'Pilih kategori yang sesuai dengan tipe item.';

                const usesProductionSource = typeSelect.value === 'finished_good' || typeSelect.value === 'wip';
                if (productionSourceWrap) productionSourceWrap.hidden = !usesProductionSource;
                if (productionSourceSelect) productionSourceSelect.disabled = !usesProductionSource;
            }

            typeSelect.addEventListener('change', refreshCategoryOptions);
            refreshCategoryOptions();
        })();
    </script>
@endpush
