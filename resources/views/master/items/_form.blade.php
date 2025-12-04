@php
    /** @var \App\Models\Item|null $item */
    $isEdit = isset($item) && $item?->id;
@endphp

<div class="card shadow-sm mb-4" style="border-radius: 14px; background: var(--card);">
    <div class="card-header fw-semibold py-2">
        {{ $isEdit ? 'Edit Item' : 'Tambah Item Baru' }}
    </div>

    <div class="card-body">

        <div class="row g-3">

            {{-- CODE --}}
            <div class="col-md-3">
                <label class="form-label fw-semibold">Kode Item</label>
                <input type="text" name="code" class="form-control" value="{{ old('code', $item->code ?? '') }}"
                    required>
            </div>

            {{-- NAME --}}
            <div class="col-md-5">
                <label class="form-label fw-semibold">Nama Item</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $item->name ?? '') }}"
                    required>
            </div>

            {{-- UNIT --}}
            <div class="col-md-2">
                <label class="form-label fw-semibold">Satuan</label>
                <input type="text" name="unit" class="form-control"
                    value="{{ old('unit', $item->unit ?? 'pcs') }}" required>
            </div>

            {{-- ACTIVE FLAG --}}
            <div class="col-md-2">
                <label class="form-label fw-semibold d-block">Aktif?</label>
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" @checked(old('active', $item->active ?? 1))>
                <span class="ms-1 text-muted small">(centang = aktif)</span>
            </div>

            {{-- TYPE --}}
            <div class="col-md-4">
                <label class="form-label fw-semibold">Tipe Item</label>
                <select name="type" class="form-select" required>
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
            </div>

            {{-- CATEGORY --}}
            <div class="col-md-4">
                <label class="form-label fw-semibold">Kategori</label>
                <select name="item_category_id" class="form-select">
                    <option value="">- Tidak Ada -</option>

                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('item_category_id', $item->item_category_id ?? null) == $cat->id)>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>

    </div>
</div>

{{-- === Include BARCODE FORM === --}}
@include('items._barcodes_form')

<div class="d-flex justify-content-end">
    <button class="btn btn-primary px-4 py-2" style="border-radius: 10px;">Simpan</button>
</div>
