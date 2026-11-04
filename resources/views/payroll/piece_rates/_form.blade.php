@csrf

<div class="row g-3">
    <div class="col-12 col-md-4">
        <label class="form-label small mb-1">Module</label>
        <select name="module" class="form-select form-select-sm @error('module') is-invalid @enderror">
            @foreach ($modules as $key => $label)
                <option value="{{ $key }}" {{ old('module', $rate->module) === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('module')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-8">
        <label class="form-label small mb-1">Operator</label>
        <select name="employee_id" class="form-select form-select-sm @error('employee_id') is-invalid @enderror">
            <option value="">Pilih operator...</option>
            @foreach ($employees as $emp)
                <option value="{{ $emp->id }}"
                    {{ old('employee_id', $rate->employee_id) == $emp->id ? 'selected' : '' }}>
                    {{ $emp->name }}
                </option>
            @endforeach
        </select>
        @error('employee_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label small mb-1">Kategori Item</label>
        <select name="item_category_id" id="piece-rate-category"
            class="form-select form-select-sm @error('item_category_id') is-invalid @enderror">
            <option value="">Pilih kategori item...</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}"
                    {{ old('item_category_id', $rate->item_category_id) == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
        @error('item_category_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text small">
            Tarif berlaku untuk semua item dalam kategori ini jika item spesifik dikosongkan.
        </div>
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label small mb-1">Item (optional, lebih spesifik)</label>
        <select name="item_id" id="piece-rate-item" class="form-select form-select-sm @error('item_id') is-invalid @enderror">
            <option value="">-- Tidak spesifik item --</option>
            @foreach ($items as $it)
                <option value="{{ $it->id }}" data-category-id="{{ $it->item_category_id }}" {{ old('item_id', $rate->item_id) == $it->id ? 'selected' : '' }}>
                    {{ $it->code }} - {{ $it->name }}
                </option>
            @endforeach
        </select>
        @error('item_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text small">
            Jika diisi, item wajib termasuk kategori yang dipilih dan tarif ini meng-override tarif kategori.
        </div>
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label small mb-1">Rate per pcs</label>
        <input type="number" step="0.01" min="0" name="rate_per_pcs"
            value="{{ old('rate_per_pcs', $rate->rate_per_pcs) }}"
            class="form-control form-control-sm @error('rate_per_pcs') is-invalid @enderror">
        @error('rate_per_pcs')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label small mb-1">Berlaku dari</label>
        <input type="date" name="effective_from"
            value="{{ old('effective_from', optional($rate->effective_from)->toDateString()) }}"
            class="form-control form-control-sm @error('effective_from') is-invalid @enderror">
        @error('effective_from')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label small mb-1">Sampai (opsional)</label>
        <input type="date" name="effective_to"
            value="{{ old('effective_to', optional($rate->effective_to)->toDateString()) }}"
            class="form-control form-control-sm @error('effective_to') is-invalid @enderror">
        @error('effective_to')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const category = document.getElementById('piece-rate-category');
            const item = document.getElementById('piece-rate-item');
            if (!category || !item) return;

            const filterItemsByCategory = () => {
                const categoryId = category.value;
                const selected = item.value;

                [...item.options].forEach((option) => {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = !categoryId || option.dataset.categoryId !== categoryId;
                });

                const selectedOption = item.options[item.selectedIndex];
                if (selected && selectedOption?.hidden) {
                    item.value = '';
                }
            };

            category.addEventListener('change', filterItemsByCategory);
            filterItemsByCategory();
        })();
    </script>
@endpush

<div class="mt-3 d-flex justify-content-end gap-2">
    <a href="{{ route('payroll.piece_rates.index') }}" class="btn btn-light btn-sm">
        Batal
    </a>
    <button type="submit" class="btn btn-primary btn-sm">
        Simpan
    </button>
</div>
