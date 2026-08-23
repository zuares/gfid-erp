@php
    $existingBarcodes = $item?->barcodes ?? collect();
    $oldBarcodes = old('barcodes', $existingBarcodes->map(fn ($barcode) => [
        'id' => $barcode->id,
        'barcode' => $barcode->barcode,
        'type' => $barcode->type,
        'notes' => $barcode->notes,
        'is_active' => $barcode->is_active,
    ])->toArray());
    $barcodeTypes = [
        'main' => 'Utama',
        'alias' => 'Alias',
        'marketplace_sku' => 'SKU Marketplace',
    ];
@endphp

<div class="item-form-card mt-3" data-barcode-card>
    <div class="item-form-section">
        <div class="item-section-head d-flex justify-content-between align-items-start">
            <div class="d-flex gap-2">
                <div class="item-section-icon"><i class="bi bi-upc-scan"></i></div>
                <div><h2 class="item-section-title">Barcode & identitas scan</h2><div class="item-section-help">Tambahkan barcode utama, alias, atau SKU marketplace. Baris kosong akan diabaikan.</div></div>
            </div>
            <button type="button" class="btn btn-sm btn-item-outline rounded-pill fw-bold" data-add-barcode><i class="bi bi-plus-lg"></i><span class="d-none d-sm-inline">Tambah baris</span></button>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0" data-barcode-table style="font-size:.8rem;">
                <thead><tr class="text-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.04em;"><th style="min-width:190px;">Barcode</th><th style="width:180px;">Tipe</th><th>Catatan</th><th class="text-center" style="width:75px;">Aktif</th><th style="width:40px;"></th></tr></thead>
                <tbody data-barcode-body>
                    @forelse($oldBarcodes as $index => $barcode)
                        <tr data-barcode-row data-index="{{ $index }}">
                            <td><input type="hidden" name="barcodes[{{ $index }}][id]" value="{{ $barcode['id'] ?? '' }}"><input type="text" name="barcodes[{{ $index }}][barcode]" class="form-control form-control-sm" value="{{ $barcode['barcode'] ?? '' }}" placeholder="Scan / ketik barcode"></td>
                            <td><select name="barcodes[{{ $index }}][type]" class="form-select form-select-sm">@foreach($barcodeTypes as $key => $label)<option value="{{ $key }}" @selected(($barcode['type'] ?? 'main') === $key)>{{ $label }}</option>@endforeach</select></td>
                            <td><input type="text" name="barcodes[{{ $index }}][notes]" class="form-control form-control-sm" value="{{ $barcode['notes'] ?? '' }}" placeholder="Opsional"></td>
                            <td class="text-center"><input type="hidden" name="barcodes[{{ $index }}][is_active]" value="0"><input type="checkbox" name="barcodes[{{ $index }}][is_active]" value="1" @checked($barcode['is_active'] ?? true)></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger rounded-circle" data-remove-barcode aria-label="Hapus baris"><i class="bi bi-trash3"></i></button></td>
                        </tr>
                    @empty
                        <tr data-barcode-row data-index="0">
                            <td><input type="hidden" name="barcodes[0][id]" value=""><input type="text" name="barcodes[0][barcode]" class="form-control form-control-sm" placeholder="Scan / ketik barcode"></td>
                            <td><select name="barcodes[0][type]" class="form-select form-select-sm">@foreach($barcodeTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></td>
                            <td><input type="text" name="barcodes[0][notes]" class="form-control form-control-sm" placeholder="Opsional"></td>
                            <td class="text-center"><input type="hidden" name="barcodes[0][is_active]" value="0"><input type="checkbox" name="barcodes[0][is_active]" value="1" checked></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger rounded-circle" data-remove-barcode aria-label="Hapus baris"><i class="bi bi-trash3"></i></button></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const card = document.querySelector('[data-barcode-card]');
    const body = card?.querySelector('[data-barcode-body]');
    const addButton = card?.querySelector('[data-add-barcode]');
    if (!card || !body || !addButton) return;
    let nextIndex = Math.max(-1, ...Array.from(body.querySelectorAll('[data-barcode-row]')).map(row => Number(row.dataset.index || -1))) + 1;
    const typeOptions = @json($barcodeTypes);

    addButton.addEventListener('click', function () {
        const index = nextIndex++;
        const options = Object.entries(typeOptions).map(([key, label]) => `<option value="${key}">${label}</option>`).join('');
        body.insertAdjacentHTML('beforeend', `<tr data-barcode-row data-index="${index}">
            <td><input type="hidden" name="barcodes[${index}][id]" value=""><input type="text" name="barcodes[${index}][barcode]" class="form-control form-control-sm" placeholder="Scan / ketik barcode"></td>
            <td><select name="barcodes[${index}][type]" class="form-select form-select-sm">${options}</select></td>
            <td><input type="text" name="barcodes[${index}][notes]" class="form-control form-control-sm" placeholder="Opsional"></td>
            <td class="text-center"><input type="hidden" name="barcodes[${index}][is_active]" value="0"><input type="checkbox" name="barcodes[${index}][is_active]" value="1" checked></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger rounded-circle" data-remove-barcode aria-label="Hapus baris"><i class="bi bi-trash3"></i></button></td>
        </tr>`);
        body.lastElementChild?.querySelector('input[type="text"]')?.focus();
    });

    body.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-barcode]');
        if (!button) return;
        const rows = body.querySelectorAll('[data-barcode-row]');
        if (rows.length <= 1) {
            rows[0].querySelectorAll('input[type="text"]').forEach(input => input.value = '');
            return;
        }
        button.closest('[data-barcode-row]')?.remove();
    });
});
</script>
@endpush
