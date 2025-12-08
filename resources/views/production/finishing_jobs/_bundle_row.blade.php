@php
    /** @var \App\Models\FinishingJobLine|null $line */
    /** @var int $index */

    use App\Models\CuttingJobBundle;

    // ambil bundle dari:
    // - old('bundles.X.bundle_id') kalau ada
    // - else dari $line->bundle jika mode edit
    $oldBundleId = old('bundles.' . $index . '.bundle_id');
    if ($oldBundleId) {
        $bundle = CuttingJobBundle::find($oldBundleId);
        $bundleIdValue = $oldBundleId;
    } else {
        $bundle = $line->bundle ?? null;
        $bundleIdValue = $line->bundle_id ?? null;
    }

    $qtyOkValue = old('bundles.' . $index . '.qty_ok', $line->qty_ok ?? 0);
    $qtyRejectValue = old('bundles.' . $index . '.qty_reject', $line->qty_reject ?? 0);
@endphp

<div class="card mb-2 bundle-row" data-index="{{ $index }}">
    <div class="card-section">
        <div class="row g-2 align-items-end">
            {{-- PILIH BUNDLE --}}
            <div class="col-12 col-md-5">
                <label class="form-label">Bundle</label>
                <x-bundle-suggest name="bundles[{{ $index }}][bundle_id]" :value="$bundleIdValue" />
                @error('bundles.' . $index . '.bundle_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            {{-- ITEM JADI --}}
            <div class="col-6 col-md-3">
                <label class="form-label">Item Jadi</label>
                <input type="text" class="form-control form-control-sm bg-light" readonly
                    value="{{ $bundle ? $bundle->item?->code . ' — ' . $bundle->item?->name : '' }}">
            </div>

            {{-- QTY OK --}}
            <div class="col-3 col-md-2">
                <label class="form-label">OK</label>
                <input type="number"
                    class="form-control form-control-sm @error('bundles.' . $index . '.qty_ok') is-invalid @enderror"
                    name="bundles[{{ $index }}][qty_ok]" value="{{ $qtyOkValue }}" min="0">
                @error('bundles.' . $index . '.qty_ok')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- QTY REJECT --}}
            <div class="col-3 col-md-2">
                <label class="form-label">Reject</label>
                <input type="number"
                    class="form-control form-control-sm @error('bundles.' . $index . '.qty_reject') is-invalid @enderror"
                    name="bundles[{{ $index }}][qty_reject]" value="{{ $qtyRejectValue }}" min="0">
                @error('bundles.' . $index . '.qty_reject')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>
