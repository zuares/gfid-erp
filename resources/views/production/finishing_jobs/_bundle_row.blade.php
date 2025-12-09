@php
    // $row may exist when edit or old input
    $bundleId = data_get($row ?? null, 'bundle_id');
    $qtyOk = data_get($row ?? null, 'qty_ok', 0);
    $qtyReject = data_get($row ?? null, 'qty_reject', 0);

    // Attempt to load bundle model if server-side available (for initial render)
    $bundleModel = $bundle ?? ($bundleId ? \App\Models\CuttingJobBundle::with('item')->find($bundleId) : null);
    $itemDisplay = $bundleModel
        ? ($bundleModel->code ?? '') .
            ' — ' .
            ($bundleModel->item?->code ?? '') .
            ' — ' .
            ($bundleModel->item?->name ?? '')
        : '';
@endphp

<div class="card mb-2 bundle-row" data-index="{{ $index }}">
    <div class="card-section">
        <div class="row g-2 align-items-end">

            {{-- BUNDLE SELECT --}}
            <div class="col-12 col-md-5">
                <label class="form-label">Bundle</label>

                {{--
                  If you already have x-bundle-suggest component that renders a select,
                  you can keep it but ensure its options have value=bundle_id.
                  Below is a simple fallback select that you can replace with your component.
                --}}
                <select class="form-control form-control-sm bundle-select" name="bundles[{{ $index }}][bundle_id]"
                    data-index="{{ $index }}">
                    <option value="">-- pilih bundle --</option>
                    {{-- If you want server-side options, uncomment and fill; otherwise component can render --}}
                    {{-- @foreach (\App\Models\CuttingJobBundle::with('item')->limit(200)->get() as $b)
                        <option value="{{ $b->id }}" @if ($b->id == $bundleId) selected @endif>
                            {{ ($b->code ?? 'B'.$b->id) }} — {{ $b->item?->code ?? '' }} — {{ $b->item?->name ?? '' }}
                        </option>
                    @endforeach --}}
                </select>
            </div>

            {{-- ITEM DISPLAY (read-only, tampilkan teks bukan angka) --}}
            <div class="col-6 col-md-3">
                <label class="form-label">Item Jadi</label>
                <input type="text" class="form-control form-control-sm item-display bg-light" readonly
                    name="bundles[{{ $index }}][item_display]"
                    value="{{ old('bundles.' . $index . '.item_display', $itemDisplay) }}"
                    data-index="{{ $index }}" />
            </div>

            {{-- QTY OK --}}
            <div class="col-3 col-md-2">
                <label class="form-label">OK</label>
                <input type="number" class="form-control form-control-sm" name="bundles[{{ $index }}][qty_ok]"
                    value="{{ old('bundles.' . $index . '.qty_ok', $qtyOk) }}">
            </div>

            {{-- QTY REJECT --}}
            <div class="col-3 col-md-2">
                <label class="form-label">Reject</label>
                <input type="number" class="form-control form-control-sm"
                    name="bundles[{{ $index }}][qty_reject]"
                    value="{{ old('bundles.' . $index . '.qty_reject', $qtyReject) }}">
            </div>

            <div class="col-12 mt-2">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">Hapus Baris</button>
            </div>
        </div>
    </div>
</div>
