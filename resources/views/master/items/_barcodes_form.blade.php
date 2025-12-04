{{-- resources/views/items/_barcodes_form.blade.php --}}

@php
    /** @var \App\Models\Item|null $item */
    $oldBarcodes = old(
        'barcodes',
        $item?->barcodes
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'barcode' => $b->barcode,
                    'type' => $b->type,
                    'notes' => $b->notes,
                    'is_active' => $b->is_active,
                ];
            })
            ->toArray() ?? [],
    );
@endphp

<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">
            Barcode Item
        </span>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-barcode-row">
            + Tambah Baris
        </button>
    </div>

    <div class="card-body p-2">
        <div class="small text-muted mb-2">
            Gunakan barcode utama di label produk, dan barcode tambahan untuk alias atau SKU marketplace.
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" id="table-barcodes">
                <thead>
                    <tr class="text-muted">
                        <th style="width: 35%">Barcode</th>
                        <th style="width: 20%">Tipe</th>
                        <th>Catatan</th>
                        <th style="width: 8%" class="text-center">Aktif</th>
                        <th style="width: 5%"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($oldBarcodes as $index => $barcode)
                        <tr>
                            {{-- hidden id kalau editing --}}
                            <td>
                                <input type="hidden" name="barcodes[{{ $index }}][id]"
                                    value="{{ $barcode['id'] ?? null }}">
                                <input type="text" name="barcodes[{{ $index }}][barcode]"
                                    class="form-control form-control-sm" placeholder="Scan / ketik barcode"
                                    value="{{ $barcode['barcode'] ?? '' }}">
                            </td>
                            <td>
                                <select name="barcodes[{{ $index }}][type]" class="form-select form-select-sm">
                                    @php
                                        $types = [
                                            'main' => 'Utama',
                                            'alias' => 'Alias',
                                            'marketplace_sku' => 'SKU Marketplace',
                                        ];
                                    @endphp
                                    @foreach ($types as $key => $label)
                                        <option value="{{ $key }}" @selected(($barcode['type'] ?? 'main') === $key)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="barcodes[{{ $index }}][notes]"
                                    class="form-control form-control-sm" placeholder="Catatan (opsional)"
                                    value="{{ $barcode['notes'] ?? '' }}">
                            </td>
                            <td class="text-center">
                                <input type="hidden" name="barcodes[{{ $index }}][is_active]" value="0">
                                <input type="checkbox" name="barcodes[{{ $index }}][is_active]" value="1"
                                    @checked($barcode['is_active'] ?? true)>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-barcode-row">
                                    &times;
                                </button>
                            </td>
                        </tr>
                    @empty
                        {{-- 1 baris kosong default --}}
                        <tr>
                            <td>
                                <input type="hidden" name="barcodes[0][id]" value="">
                                <input type="text" name="barcodes[0][barcode]" class="form-control form-control-sm"
                                    placeholder="Scan / ketik barcode">
                            </td>
                            <td>
                                <select name="barcodes[0][type]" class="form-select form-select-sm">
                                    <option value="main">Utama</option>
                                    <option value="alias">Alias</option>
                                    <option value="marketplace_sku">SKU Marketplace</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="barcodes[0][notes]" class="form-control form-control-sm"
                                    placeholder="Catatan (opsional)">
                            </td>
                            <td class="text-center">
                                <input type="hidden" name="barcodes[0][is_active]" value="0">
                                <input type="checkbox" name="barcodes[0][is_active]" value="1" checked>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-barcode-row">
                                    &times;
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            const table = document.getElementById('table-barcodes');
            const btnAdd = document.getElementById('btn-add-barcode-row');
            if (!table || !btnAdd) return;

            function getNextIndex() {
                const rows = table.querySelectorAll('tbody tr');
                return rows.length;
            }

            btnAdd.addEventListener('click', function() {
                const index = getNextIndex();
                const tbody = table.querySelector('tbody');

                const tpl = `
                <tr>
                    <td>
                        <input type="hidden" name="barcodes[${index}][id]" value="">
                        <input type="text"
                            name="barcodes[${index}][barcode]"
                            class="form-control form-control-sm"
                            placeholder="Scan / ketik barcode">
                    </td>
                    <td>
                        <select name="barcodes[${index}][type]" class="form-select form-select-sm">
                            <option value="main">Utama</option>
                            <option value="alias">Alias</option>
                            <option value="marketplace_sku">SKU Marketplace</option>
                        </select>
                    </td>
                    <td>
                        <input type="text"
                            name="barcodes[${index}][notes]"
                            class="form-control form-control-sm"
                            placeholder="Catatan (opsional)">
                    </td>
                    <td class="text-center">
                        <input type="hidden" name="barcodes[${index}][is_active]" value="0">
                        <input type="checkbox" name="barcodes[${index}][is_active]" value="1" checked>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-barcode-row">&times;</button>
                    </td>
                </tr>
                `;

                tbody.insertAdjacentHTML('beforeend', tpl);
            });

            table.addEventListener('click', function(e) {
                if (e.target.closest('.btn-remove-barcode-row')) {
                    const row = e.target.closest('tr');
                    row?.remove();
                }
            });
        })();
    </script>
@endpush
