@php
    /** @var \App\Models\Item|null $item */
    $typeLabels = $typeLabels ?? [
        'material' => 'Material / Bahan',
        'wip' => 'Setengah Jadi (WIP)',
        'finished_good' => 'Barang Jadi (FG)',
    ];
    $isEdit = filled($item?->id);
    $itemBom = $itemBom ?? null;
    $itemTypeOptions = $itemTypeOptions ?? collect();
    $purchaseTreatmentOptions = $purchaseTreatmentOptions ?? collect();
    $supplierOptions = $suppliers ?? collect();
    $selectedSupplierIds = old('supplier_ids', $item?->suppliers?->pluck('id')->all() ?? []);
    $selectedSupplierIds = array_values(array_unique(array_map('intval', is_array($selectedSupplierIds) ? $selectedSupplierIds : [$selectedSupplierIds])));
    $storedPrimarySupplierId = $item?->suppliers?->first(fn ($supplier) => (bool) $supplier->pivot?->is_primary)?->id
        ?? ($selectedSupplierIds[0] ?? null);
    $selectedPrimarySupplierId = (int) old('primary_supplier_id', $storedPrimarySupplierId ?? 0);
    $itemType = old('type', $item?->type ?? 'material');
    $selectedTypeOptionId = (int) old(
        'item_type_option_id',
        $item?->item_type_option_id
            ?? $itemTypeOptions->firstWhere('base_type', $itemType)?->id
            ?? $itemTypeOptions->firstWhere('code', $itemType)?->id
            ?? 0
    );
    $legacySource = old('production_source', $item?->production_source ?? \App\Models\Item::PRODUCTION_BUY);
    $canBuy = old('can_buy', $item?->can_buy ?? ($legacySource === \App\Models\Item::PRODUCTION_BUY ? 1 : 0));
    $canMake = old('can_make', $item?->can_make ?? ($legacySource === \App\Models\Item::PRODUCTION_IN_HOUSE ? 1 : 0));
    $hasOldSupplyChoice = old('supply_mode_preset') !== null || old('can_buy') !== null || old('can_make') !== null;
    $supplyMode = old('supply_mode_preset', $canBuy && $canMake ? 'hybrid' : ($canMake ? 'make' : ($canBuy ? 'buy' : 'outsource')));
    if (!$isEdit && !$hasOldSupplyChoice) {
        $supplyMode = match ($itemType) {
            'finished_good' => 'hybrid',
            'wip' => 'make',
            default => 'outsource',
        };
        $canBuy = $supplyMode === 'buy' || $supplyMode === 'hybrid' ? 1 : 0;
        $canMake = $supplyMode === 'make' || $supplyMode === 'hybrid' ? 1 : 0;
    }
    $defaultSupplySource = old('default_supply_source', $item?->default_supply_source ?? match ($legacySource) {
        \App\Models\Item::PRODUCTION_IN_HOUSE => \App\Models\Item::SUPPLY_MAKE,
        \App\Models\Item::PRODUCTION_OUTSOURCE => \App\Models\Item::SUPPLY_OUTSOURCE,
        default => \App\Models\Item::SUPPLY_BUY,
    });
    if (!$isEdit && !$hasOldSupplyChoice) {
        $defaultSupplySource = in_array($supplyMode, ['make', 'hybrid'], true)
            ? \App\Models\Item::SUPPLY_MAKE
            : ($supplyMode === 'outsource' ? \App\Models\Item::SUPPLY_OUTSOURCE : \App\Models\Item::SUPPLY_BUY);
    }
    $selectedCategory = collect($categories)->firstWhere('id', (int) old('item_category_id', $item?->item_category_id));
    $categoryKind = $selectedCategory?->kind;
    $defaultAllocation = old('default_allocation', $item?->default_allocation ?? 'hpp');
    $selectedTreatmentId = (int) old(
        'purchase_treatment_id',
        $item?->purchase_treatment_id
            ?? $purchaseTreatmentOptions->firstWhere('allocation', $defaultAllocation)?->id
            ?? 0
    );
    if ($categoryKind === 'operational' && old('default_allocation') === null) {
        $defaultAllocation = 'expense';
    }
    $isProductionItem = in_array($itemType, ['finished_good', 'wip'], true);
    $supplyAccordionOpen = $isProductionItem
        || count($selectedSupplierIds) > 0
        || $errors->has('supplier_ids')
        || $errors->has('primary_supplier_id')
        || $errors->has('can_buy')
        || $errors->has('can_make')
        || $errors->has('default_supply_source');
@endphp

@push('head')
<style>
    .item-crud-page { max-width:1120px; margin:0 auto; padding:16px 12px 34px; }
    .item-crud-header { display:flex; justify-content:space-between; align-items:center; gap:14px; margin-bottom:14px; padding:17px 18px; border:1px solid #e2e8f0; border-radius:20px; background:linear-gradient(135deg,#fff,#f8fafc); box-shadow:0 12px 32px rgba(15,23,42,.05); }
    .item-crud-eyebrow { color:#64748b; font-size:.68rem; font-weight:850; text-transform:uppercase; letter-spacing:.05em; }
    .item-crud-title { margin:3px 0 0; color:#0f172a; font-size:1.25rem; font-weight:900; letter-spacing:-.04em; }
    .item-crud-subtitle { margin-top:3px; color:#64748b; font-size:.8rem; font-weight:600; }
    .item-crud-header .btn { border-radius:999px; font-weight:800; }
    .item-form { max-width:1120px; margin:0 auto; padding:0 0 28px; color:#0f172a; }
    .item-form-card { border:1px solid #e2e8f0; border-radius:20px; background:#fff; box-shadow:0 12px 32px rgba(15,23,42,.05); overflow:hidden; }
    .item-form-section { padding:17px; }
    .item-form-section + .item-form-section { border-top:1px solid #eef2f7; }
    .item-section-head { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
    .item-section-icon { width:32px; height:32px; flex:0 0 32px; display:flex; align-items:center; justify-content:center; color:#334155; background:#f1f5f9; border-radius:10px; }
    .item-section-title { margin:0; color:#0f172a; font-size:.95rem; font-weight:900; letter-spacing:-.02em; }
    .item-form .form-label { margin-bottom:5px; color:#475569; font-size:.7rem; font-weight:850; text-transform:uppercase; letter-spacing:.04em; }
    .item-form .form-control, .item-form .form-select { min-height:39px; border-color:#e2e8f0; border-radius:11px; color:#0f172a; font-size:.82rem; font-weight:600; box-shadow:none; }
    .item-form textarea.form-control { min-height:auto; }
    .item-form .form-control:focus, .item-form .form-select:focus { border-color:#94a3b8; box-shadow:0 0 0 .2rem rgba(15,23,42,.07); }
    .item-form .invalid-feedback { font-size:.7rem; }
    .item-code-field { position:relative; }
    .item-category-picker { display:flex; align-items:center; gap:6px; }
    .item-category-picker .form-select { min-width:0; flex:1 1 auto; }
    .item-option-picker { display:flex; align-items:center; gap:6px; }
    .item-option-picker .form-select { min-width:0; flex:1 1 auto; }
    .item-category-add { width:39px; min-width:39px; height:39px; padding:0; border-radius:11px; }
    .item-option-add { width:39px; min-width:39px; height:39px; padding:0; border-radius:11px; }
    .item-code-suggest { position:absolute; z-index:30; top:calc(100% + 5px); left:0; min-width:320px; max-width:min(440px, 80vw); padding:7px; border:1px solid #cbd5e1; border-radius:13px; background:#fff; box-shadow:0 14px 32px rgba(15,23,42,.14); }
    .item-code-suggest[hidden] { display:none; }
    .item-code-suggest-head { padding:5px 7px 7px; color:#64748b; font-size:.68rem; font-weight:800; }
    .item-code-suggest-head.is-duplicate { color:#b91c1c; }
    .item-code-suggest-row { display:flex; width:100%; align-items:flex-start; gap:8px; padding:7px; border:0; border-radius:9px; background:transparent; text-align:left; cursor:pointer; }
    .item-code-suggest-row + .item-code-suggest-row { margin-top:2px; }
    .item-code-suggest-row:hover { background:#f8fafc; }
    .item-code-suggest-code { color:#0f172a; font-size:.73rem; font-weight:900; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; white-space:nowrap; }
    .item-code-suggest-name { min-width:0; color:#64748b; font-size:.7rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .item-code-duplicate-feedback { display:none; margin-top:5px; color:#b91c1c; font-size:.68rem; font-weight:700; }
    .item-code-duplicate-feedback.is-visible { display:block; }
    .item-required { color:#dc2626; }
    .item-supply-box { padding:13px; border:1px solid #e2e8f0; border-radius:15px; background:#f8fafc; }
    .item-supply-options { display:grid; gap:8px; }
    .item-supply-option { display:flex; align-items:center; gap:9px; padding:10px; border:1px solid #e2e8f0; border-radius:11px; background:#fff; cursor:pointer; }
    .item-supply-option:hover { border-color:#94a3b8; }
    .item-supply-option strong { display:block; font-size:.78rem; }
    .item-supply-option span { display:block; color:#94a3b8; font-size:.69rem; margin-top:1px; }
    .item-supply-preset { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:7px; }
    .item-supply-preset-option { position:relative; display:flex; align-items:flex-start; gap:7px; min-height:58px; padding:9px; border:1px solid #e2e8f0; border-radius:11px; background:#fff; cursor:pointer; }
    .item-supply-preset-option:hover { border-color:#94a3b8; }
    .item-supply-preset-option:has(input:checked) { border-color:#6366f1; background:#eef2ff; box-shadow:0 0 0 2px rgba(99,102,241,.1); }
    .item-supply-preset-option strong { display:block; color:#0f172a; font-size:.72rem; }
    .item-supply-preset-option small { display:block; margin-top:2px; color:#94a3b8; font-size:.63rem; line-height:1.25; }
    .item-supplier-picker { padding:11px; border:1px solid #e2e8f0; border-radius:15px; background:#f8fafc; }
    .item-supplier-add-panel { margin-top:9px; padding:13px; border:1px solid #bfdbfe; border-radius:13px; background:#eff6ff; }
    .item-supplier-add-panel[hidden] { display:none; }
    .item-quick-supplier-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
    .item-quick-supplier-field-full { grid-column:1 / -1; }
    .item-quick-supplier-types { display:flex; flex-wrap:wrap; gap:10px 16px; min-height:39px; align-items:center; padding:7px 10px; border:1px solid #e2e8f0; border-radius:11px; background:#fff; }
    .item-quick-supplier-types .form-check { margin:0; }
    .item-quick-supplier-actions { display:flex; justify-content:flex-end; gap:7px; margin-top:12px; }
    .item-supplier-search { margin-bottom:8px; }
    .item-supplier-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:7px; max-height:220px; overflow:auto; padding-right:3px; }
    .item-supplier-option { display:flex; align-items:flex-start; gap:8px; padding:8px 9px; border:1px solid #e2e8f0; border-radius:10px; background:#fff; cursor:pointer; }
    .item-supplier-option:hover { border-color:#94a3b8; }
    .item-supplier-option:has(input:checked) { border-color:#0ea5e9; background:#f0f9ff; }
    .item-supplier-option strong { display:block; color:#0f172a; font-size:.72rem; }
    .item-supplier-option small { display:block; margin-top:1px; color:#94a3b8; font-size:.64rem; }
    .item-supply-summary { display:flex; align-items:center; gap:7px; margin-top:11px; color:#64748b; font-size:.72rem; }
    .item-supply-summary .dot { width:8px; height:8px; border-radius:50%; background:#94a3b8; }
    .item-supply-summary.is-hybrid .dot { background:#7c3aed; }
    .item-supply-summary.is-make .dot { background:#059669; }
    .item-supply-summary.is-buy .dot { background:#2563eb; }
    .item-supply-summary.is-outsource .dot { background:#d97706; }
    .item-supply-summary.is-invalid .dot { background:#d97706; }
    .item-accordion { border:1px solid #e2e8f0; border-radius:15px; background:#f8fafc; overflow:hidden; }
    .item-accordion + .item-accordion { margin-top:10px; }
    .item-accordion summary { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 13px; cursor:pointer; list-style:none; }
    .item-accordion summary::-webkit-details-marker { display:none; }
    .item-accordion summary:hover { background:#f1f5f9; }
    .item-accordion[open] summary { border-bottom:1px solid #e2e8f0; }
    .item-accordion-title { display:flex; align-items:center; gap:9px; color:#334155; font-size:.78rem; font-weight:900; }
    .item-accordion-title i { color:#6366f1; font-size:1rem; }
    .item-accordion-meta { display:flex; align-items:center; gap:7px; color:#64748b; font-size:.7rem; }
    .item-accordion-meta .bi-chevron-down { transition:transform .18s ease; }
    .item-accordion[open] .item-accordion-meta .bi-chevron-down { transform:rotate(180deg); }
    .item-accordion-body { padding:13px; }
    .item-status-switch { display:flex; align-items:center; justify-content:space-between; gap:10px; min-height:39px; padding:9px 11px; border:1px solid #e2e8f0; border-radius:11px; background:#fff; }
    .item-status-switch strong { font-size:.78rem; }
    .item-form-footer { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-top:15px; }
    .item-form-footer .btn { border-radius:999px; font-weight:800; }
    .item-form .btn-item-primary { color:#fff; background:linear-gradient(135deg,#0f172a,#334155); border-color:transparent; }
    .item-form .btn-item-outline { color:#334155; background:#fff; border:1px solid #cbd5e1; }
    .item-bom-menu { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px 16px; border:1px solid #c7d2fe; border-radius:16px; background:linear-gradient(135deg,#eef2ff,#f8fafc); }
    .item-bom-menu[hidden] { display:none; }
    .item-bom-menu-icon { width:36px; height:36px; flex:0 0 36px; display:flex; align-items:center; justify-content:center; border-radius:11px; color:#4338ca; background:#e0e7ff; }
    .item-bom-menu-title { color:#1e1b4b; font-size:.82rem; font-weight:900; }
    .item-bom-menu-text { margin-top:2px; color:#64748b; font-size:.72rem; }
    .item-bom-menu .btn { border-radius:999px; font-weight:800; white-space:nowrap; }
    body[data-theme="dark"] .item-form { color:#e5e7eb; }
    body[data-theme="dark"] .item-crud-header { background:#0f172a; border-color:#334155; }
    body[data-theme="dark"] .item-crud-title { color:#f8fafc; }
    body[data-theme="dark"] .item-form-card, body[data-theme="dark"] .item-supply-box, body[data-theme="dark"] .item-supply-option, body[data-theme="dark"] .item-supply-preset-option, body[data-theme="dark"] .item-supplier-picker, body[data-theme="dark"] .item-supplier-option, body[data-theme="dark"] .item-status-switch, body[data-theme="dark"] .item-accordion, body[data-theme="dark"] .item-quick-supplier-types { background:#0f172a; border-color:#334155; }
    body[data-theme="dark"] .item-supplier-add-panel { background:#172554; border-color:#3730a3; }
    body[data-theme="dark"] .item-supply-preset-option strong { color:#f8fafc; }
    body[data-theme="dark"] .item-supplier-option strong { color:#f8fafc; }
    body[data-theme="dark"] .item-section-title { color:#f8fafc; }
    body[data-theme="dark"] .item-section-icon { color:#cbd5e1; background:#1e293b; }
    body[data-theme="dark"] .item-accordion summary:hover { background:#1e293b; }
    body[data-theme="dark"] .item-accordion[open] summary { border-color:#334155; }
    body[data-theme="dark"] .item-accordion-title { color:#e2e8f0; }
    body[data-theme="dark"] .item-form .form-label { color:#cbd5e1; }
    body[data-theme="dark"] .item-form .form-control, body[data-theme="dark"] .item-form .form-select { color:#f8fafc; background:#0f172a; border-color:#475569; }
    body[data-theme="dark"] .item-bom-menu { background:#172554; border-color:#3730a3; }
    body[data-theme="dark"] .item-bom-menu-title { color:#e0e7ff; }
    @media(max-width:900px) { .item-supply-preset { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:700px) {
        .item-crud-page { padding:8px 6px 22px; }
        .item-crud-header { align-items:flex-start; flex-direction:column; padding:13px; border-radius:17px; }
        .item-crud-header > div:last-child { width:100%; display:flex; gap:6px; }
        .item-crud-header > div:last-child .btn { flex:1 1 0; justify-content:center; }
        .item-crud-title { font-size:1.15rem; }
        .item-form-section { padding:13px; }
        .item-supplier-list, .item-supply-preset, .item-quick-supplier-grid { grid-template-columns:1fr; }
        .item-accordion summary { align-items:flex-start; }
        .item-accordion-meta { white-space:nowrap; }
        .item-form-footer { align-items:stretch; flex-direction:column-reverse; }
        .item-form-footer > * { width:100%; }
        .item-form-footer .btn { width:100%; justify-content:center; }
        .item-bom-menu { align-items:stretch; flex-direction:column; padding:12px; }
        .item-bom-menu .btn { width:100%; justify-content:center; }
    }
    @media(max-width:440px) {
        .item-crud-header > div:last-child { flex-direction:column; }
        .item-section-title { font-size:.88rem; }
        .item-accordion-meta { font-size:.64rem; }
    }
</style>
@endpush

<div class="item-form">
    @if($errors->any())
        @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.Swal) return;
            const messages = @json($errors->all());
            window.Swal.fire({
                icon: 'error',
                title: 'Data belum bisa disimpan',
                html: `<div class="text-start small">${messages.map(message => `<div class="mb-1">• ${String(message).replace(/[&<>]/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[character]))}</div>`).join('')}</div>`,
                confirmButtonText: 'Periksa data',
                confirmButtonColor: '#334155',
                width: 'min(92vw, 460px)',
            });
        });
        </script>
        @endpush
    @endif

    <div class="item-form-card">
        <div class="item-form-section">
            <div class="item-section-head">
                <div class="item-section-icon"><i class="bi bi-box"></i></div>
                <h2 class="item-section-title">Identitas item</h2>
            </div>
            <div class="row g-2">
                <div class="col-lg-2 col-md-4">
                    <label class="form-label" for="item-code">Kode item <span class="item-required">*</span></label>
                    <div class="item-code-field">
                        <input id="item-code" type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $item?->code) }}" maxlength="50" autocomplete="off" spellcheck="false" required>
                        <div class="item-code-suggest" data-code-suggest hidden></div>
                    </div>
                    <div class="item-code-duplicate-feedback" data-code-duplicate-feedback></div>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label" for="item-sku">SKU</label>
                    <input id="item-sku" type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', $item?->sku) }}" autocomplete="off">
                    @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-5 col-md-8">
                    <label class="form-label" for="item-name">Nama item <span class="item-required">*</span></label>
                    <input id="item-name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $item?->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-3 col-md-4">
                    <label class="form-label" for="item-unit">Satuan <span class="item-required">*</span></label>
                    <input id="item-unit" type="text" name="unit" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit', $item?->unit ?? 'pcs') }}" required>
                    @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="item-form-section">
            <div class="item-section-head">
                <div class="item-section-icon"><i class="bi bi-diagram-3"></i></div>
                <h2 class="item-section-title">Klasifikasi & akuntansi</h2>
            </div>
            <div class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label" for="item-type">Tipe item <span class="item-required">*</span></label>
                    <div class="item-option-picker">
                        <select id="item-type-option" name="item_type_option_id" data-item-type-option class="form-select @error('item_type_option_id') is-invalid @enderror" required>
                            @foreach($itemTypeOptions as $option)
                                <option value="{{ $option->id }}" data-base-type="{{ $option->base_type }}" @selected($selectedTypeOptionId === (int) $option->id)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-primary item-option-add" data-open-type-modal title="Tambah tipe item" aria-label="Tambah tipe item"><i class="bi bi-plus-lg"></i></button>
                    </div>
                    <input id="item-type" type="hidden" name="type" data-item-type value="{{ old('type', $itemType) }}">
                    @error('item_type_option_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label" for="item-category">Kategori</label>
                    <div class="item-category-picker">
                        <select id="item-category" name="item_category_id" data-item-category class="form-select @error('item_category_id') is-invalid @enderror">
                            <option value="">Tanpa kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" data-code="{{ $category->code }}" data-kind="{{ $category->kind }}" @selected(old('item_category_id', $item?->item_category_id) == $category->id)>{{ $category->code }} — {{ $category->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-primary item-category-add" data-open-category-modal title="Tambah kategori" aria-label="Tambah kategori"><i class="bi bi-plus-lg"></i></button>
                    </div>
                    @error('item_category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label" for="default-allocation">Perlakuan pembelian</label>
                    <div class="item-option-picker">
                        <select id="purchase-treatment" name="purchase_treatment_id" data-purchase-treatment class="form-select @error('purchase_treatment_id') is-invalid @enderror">
                            @foreach($purchaseTreatmentOptions as $option)
                                <option value="{{ $option->id }}" data-allocation="{{ $option->allocation }}" data-default-account="{{ $option->default_expense_account_id ?? '' }}" @selected($selectedTreatmentId === (int) $option->id)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-primary item-option-add" data-open-treatment-modal title="Tambah perlakuan pembelian" aria-label="Tambah perlakuan pembelian"><i class="bi bi-plus-lg"></i></button>
                    </div>
                    <input id="default-allocation" type="hidden" name="default_allocation" data-default-allocation value="{{ $defaultAllocation }}">
                    @error('purchase_treatment_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('default_allocation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-3 col-md-6" data-expense-account-wrap>
                    <label class="form-label" for="expense-account">Akun biaya</label>
                    <div class="item-option-picker">
                        <select id="expense-account" name="default_expense_account_id" class="form-select @error('default_expense_account_id') is-invalid @enderror">
                            <option value="">Pilih akun</option>
                            @foreach($expenseAccounts ?? [] as $account)
                                <option value="{{ $account->id }}" data-account-code="{{ $account->code }}" @selected(old('default_expense_account_id', $item?->default_expense_account_id) == $account->id)>{{ $account->code }} — {{ $account->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-primary item-option-add" data-open-account-modal title="Tambah akun biaya" aria-label="Tambah akun biaya"><i class="bi bi-plus-lg"></i></button>
                    </div>
                    @error('default_expense_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <details class="item-accordion mt-3" data-supply-accordion @if($supplyAccordionOpen) open @endif>
                <summary>
                    <span class="item-accordion-title"><i class="bi bi-truck"></i>Pasokan & supplier</span>
                    <span class="item-accordion-meta"><span class="badge rounded-pill text-bg-light" data-supplier-count>0 dipilih</span><i class="bi bi-chevron-down"></i></span>
                </summary>
                <div class="item-accordion-body">
                    <div data-supply-policy-wrap>
                        <div class="item-supply-box">
                            <div class="row g-3 align-items-end">
                                <div class="col-lg-8">
                                    <label class="form-label mb-2">Metode pasok</label>
                                    <div class="item-supply-preset" data-supply-preset-group>
                                        @foreach([
                                            'buy' => 'Beli jadi',
                                            'make' => 'Produksi sendiri',
                                            'hybrid' => 'Hybrid',
                                            'outsource' => 'Makloon',
                                        ] as $mode => $label)
                                            <label class="item-supply-preset-option">
                                                <input type="radio" name="supply_mode_preset" value="{{ $mode }}" class="form-check-input mt-1" data-supply-preset @checked($supplyMode === $mode)>
                                                <span><strong>{{ $label }}</strong></span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label" for="default-supply-source">Prioritas default</label>
                                    <select id="default-supply-source" name="default_supply_source" data-default-supply-source class="form-select @error('default_supply_source') is-invalid @enderror">
                                        @foreach(\App\Models\Item::supplySourceLabels() as $key => $label)
                                            <option value="{{ $key }}" @selected($defaultSupplySource === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <div class="item-supply-summary" data-supply-summary><span class="dot"></span><span data-supply-summary-text>Belum ditentukan</span></div>
                                </div>
                            </div>
                            <div class="item-supply-options">
                                <input type="hidden" name="can_buy" value="0">
                                <label class="item-supply-option d-none" for="can-buy">
                                    <input id="can-buy" type="checkbox" class="form-check-input mt-0" name="can_buy" value="1" data-supply-can-buy @checked((int) $canBuy === 1)>
                                    <span><strong>Bisa dibeli jadi</strong></span>
                                </label>
                                <input type="hidden" name="can_make" value="0">
                                <label class="item-supply-option d-none" for="can-make">
                                    <input id="can-make" type="checkbox" class="form-check-input mt-0" name="can_make" value="1" data-supply-can-make @checked((int) $canMake === 1)>
                                    <span><strong>Bisa diproduksi sendiri</strong></span>
                                </label>
                            </div>
                            @error('can_buy')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @error('can_make')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            @error('default_supply_source')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-3 item-supplier-picker" data-supplier-picker>
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <label class="form-label mb-0">Supplier item</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-open-quick-supplier><i class="bi bi-plus-lg me-1"></i>Tambah supplier</button>
                        </div>
                        <div class="item-supplier-add-panel" data-quick-supplier-panel hidden>
                            <div data-quick-supplier-form>
                                <div class="item-quick-supplier-grid">
                                    <div>
                                        <label class="form-label" for="quick-supplier-code">Kode supplier <span class="item-required">*</span></label>
                                        <input id="quick-supplier-code" data-quick-field="code" type="text" class="form-control" maxlength="50" placeholder="SUP001" autocomplete="off">
                                    </div>
                                    <div>
                                        <label class="form-label" for="quick-supplier-name">Nama supplier <span class="item-required">*</span></label>
                                        <input id="quick-supplier-name" data-quick-field="name" type="text" class="form-control" maxlength="255" placeholder="Nama supplier" autocomplete="off">
                                    </div>
                                    <div>
                                        <label class="form-label" for="quick-supplier-phone">Telepon</label>
                                        <input id="quick-supplier-phone" data-quick-field="phone" type="text" class="form-control" maxlength="50" placeholder="Nomor HP / WhatsApp" autocomplete="off">
                                    </div>
                                    <div>
                                        <label class="form-label" for="quick-supplier-email">Email</label>
                                        <input id="quick-supplier-email" data-quick-field="email" type="email" class="form-control" maxlength="255" placeholder="Email supplier" autocomplete="off">
                                    </div>
                                    <div>
                                        <label class="form-label" for="quick-supplier-active">Status</label>
                                        <select id="quick-supplier-active" data-quick-field="active" class="form-select" data-quick-default="1">
                                            <option value="1" selected>Aktif</option>
                                            <option value="0">Nonaktif</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Jenis PO</label>
                                        <div class="item-quick-supplier-types">
                                            @foreach(['material' => 'Bahan Baku', 'finished_good' => 'Barang Jadi', 'packing' => 'Packing'] as $poType => $poLabel)
                                                <label class="form-check">
                                                    <input class="form-check-input" type="checkbox" data-quick-field="po_types[]" value="{{ $poType }}">
                                                    <span class="form-check-label">{{ $poLabel }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="item-quick-supplier-field-full">
                                        <label class="form-label" for="quick-supplier-address">Alamat</label>
                                        <textarea id="quick-supplier-address" data-quick-field="address" rows="2" class="form-control" maxlength="1000" placeholder="Alamat supplier"></textarea>
                                    </div>
                                </div>
                                <div class="item-quick-supplier-actions">
                                    <button type="button" class="btn btn-sm btn-light" data-close-quick-supplier>Batal</button>
                                    <button type="button" class="btn btn-sm btn-primary" data-quick-supplier-submit><i class="bi bi-check2 me-1"></i>Simpan supplier</button>
                                </div>
                                <div class="small text-danger mt-2" data-quick-supplier-error hidden></div>
                            </div>
                        </div>
                        <input type="search" class="form-control item-supplier-search" placeholder="Cari supplier..." data-supplier-search autocomplete="off">
                        <div class="item-supplier-list" data-supplier-list>
                            @forelse($supplierOptions as $supplier)
                                <label class="item-supplier-option" data-supplier-option data-supplier-code="{{ strtolower($supplier->code) }}" data-search="{{ strtolower($supplier->code . ' ' . $supplier->name) }}">
                                    <input type="checkbox" class="form-check-input mt-1" name="supplier_ids[]" value="{{ $supplier->id }}" data-supplier-checkbox @checked(in_array((int) $supplier->id, $selectedSupplierIds, true))>
                                    <span><strong>{{ $supplier->code }}</strong><small>{{ $supplier->name }}</small></span>
                                </label>
                            @empty
                                <div class="form-text">Belum ada supplier aktif.</div>
                            @endforelse
                        </div>
                        <div class="row g-2 align-items-end mt-1">
                            <div class="col-lg-6">
                                <label class="form-label" for="primary-supplier">Supplier utama</label>
                                <select id="primary-supplier" name="primary_supplier_id" class="form-select" data-primary-supplier>
                                    <option value="">Otomatis gunakan pilihan pertama</option>
                                    @foreach($supplierOptions as $supplier)
                                        <option value="{{ $supplier->id }}" data-primary-option @selected($selectedPrimarySupplierId === (int) $supplier->id)>{{ $supplier->code }} — {{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @error('supplier_ids')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        @error('primary_supplier_id')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </div>

                    <div class="mt-3" data-status-wrap>
                        <label class="form-label" for="item-active">Status item</label>
                        <div class="item-status-switch">
                            <strong data-status-label>{{ old('active', $item?->active ?? 1) ? 'Aktif / bisa dipakai' : 'Nonaktif / disembunyikan' }}</strong>
                            <input type="hidden" name="active" value="0">
                            <div class="form-check form-switch mb-0"><input id="item-active" type="checkbox" name="active" value="1" class="form-check-input" @checked(old('active', $item?->active ?? 1) == 1)></div>
                        </div>
                    </div>
                </div>
            </details>
        </div>

        <div class="modal fade" id="quickItemCategoryModal" tabindex="-1" aria-labelledby="quickItemCategoryTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header">
                        <h5 class="modal-title" id="quickItemCategoryTitle">Tambah kategori</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="quick-category-code">Kode kategori</label>
                            <input id="quick-category-code" type="text" class="form-control" maxlength="50" placeholder="ACC-NEW" autocomplete="off">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="quick-category-name">Nama kategori</label>
                            <input id="quick-category-name" type="text" class="form-control" maxlength="190" placeholder="Accessories baru" autocomplete="off">
                        </div>
                        <div>
                            <label class="form-label" for="quick-category-kind">Kelompok</label>
                            <select id="quick-category-kind" class="form-select">
                                @foreach(\App\Models\ItemCategory::kindLabels() as $kind => $label)
                                    <option value="{{ $kind }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="small text-danger mt-2" data-quick-category-error hidden></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" data-quick-category-submit><i class="bi bi-check2 me-1"></i>Simpan kategori</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="quickItemTypeModal" tabindex="-1" aria-labelledby="quickItemTypeTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header"><h5 class="modal-title" id="quickItemTypeTitle">Tambah tipe item</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label" for="quick-type-code">Kode tipe</label><input id="quick-type-code" type="text" class="form-control" maxlength="50" autocomplete="off"></div>
                        <div class="mb-3"><label class="form-label" for="quick-type-name">Nama tipe</label><input id="quick-type-name" type="text" class="form-control" maxlength="100" autocomplete="off"></div>
                        <div><label class="form-label" for="quick-type-base">Basis perilaku</label><select id="quick-type-base" class="form-select">@foreach($typeLabels as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                        <div class="small text-danger mt-2" data-quick-type-error hidden></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" data-quick-type-submit><i class="bi bi-check2 me-1"></i>Simpan tipe</button></div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="quickPurchaseTreatmentModal" tabindex="-1" aria-labelledby="quickPurchaseTreatmentTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header"><h5 class="modal-title" id="quickPurchaseTreatmentTitle">Tambah perlakuan pembelian</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label" for="quick-treatment-code">Kode perlakuan</label><input id="quick-treatment-code" type="text" class="form-control" maxlength="50" autocomplete="off"></div>
                        <div class="mb-3"><label class="form-label" for="quick-treatment-name">Nama perlakuan</label><input id="quick-treatment-name" type="text" class="form-control" maxlength="120" autocomplete="off"></div>
                        <div class="mb-3"><label class="form-label" for="quick-treatment-allocation">Alokasi akuntansi</label><select id="quick-treatment-allocation" class="form-select"><option value="hpp">Persediaan / HPP</option><option value="expense">Biaya langsung</option></select></div>
                        <div data-quick-treatment-account-wrap><label class="form-label" for="quick-treatment-account">Akun biaya default</label><select id="quick-treatment-account" class="form-select"><option value="">Pilih akun</option>@foreach($expenseAccounts ?? [] as $account)<option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>@endforeach</select></div>
                        <div class="small text-danger mt-2" data-quick-treatment-error hidden></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" data-quick-treatment-submit><i class="bi bi-check2 me-1"></i>Simpan perlakuan</button></div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="quickExpenseAccountModal" tabindex="-1" aria-labelledby="quickExpenseAccountTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header"><h5 class="modal-title" id="quickExpenseAccountTitle">Tambah akun biaya</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label" for="quick-account-code">Kode akun</label><input id="quick-account-code" type="text" class="form-control" maxlength="20" autocomplete="off"></div>
                        <div><label class="form-label" for="quick-account-name">Nama akun</label><input id="quick-account-name" type="text" class="form-control" maxlength="190" autocomplete="off"></div>
                        <div class="small text-danger mt-2" data-quick-account-error hidden></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" data-quick-account-submit><i class="bi bi-check2 me-1"></i>Simpan akun</button></div>
                </div>
            </div>
        </div>

        <div class="item-form-section">
            <div class="item-section-head">
                <div class="item-section-icon"><i class="bi bi-cash-coin"></i></div>
                <h2 class="item-section-title">Harga & biaya dasar</h2>
            </div>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label" for="last-purchase-price">Harga beli terakhir (Rp)</label>
                    <input id="last-purchase-price" type="number" min="0" step="0.01" name="last_purchase_price" class="form-control @error('last_purchase_price') is-invalid @enderror" value="{{ old('last_purchase_price', $item?->last_purchase_price) }}" placeholder="0">
                    @error('last_purchase_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8" data-hpp-fields @if($defaultAllocation === 'expense') hidden @endif>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label" for="unit-cost">HPP sementara (Rp / unit)</label>
                            <input id="unit-cost" type="number" min="0" step="0.01" name="unit_cost" class="form-control @error('unit_cost') is-invalid @enderror" value="{{ old('unit_cost', $activeSnapshot?->unit_cost) }}" placeholder="0">
                            @error('unit_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="hpp-notes">Catatan HPP</label>
                    <input id="hpp-notes" type="text" name="hpp_notes" class="form-control @error('hpp_notes') is-invalid @enderror" value="{{ old('hpp_notes', $activeSnapshot?->notes) }}" maxlength="255">
                            @error('hpp_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
            @if($activeSnapshot)
                <div class="form-text mt-3"><i class="bi bi-clock-history me-1"></i>HPP aktif saat ini: <strong>Rp {{ number_format((float) $activeSnapshot->unit_cost, 0, ',', '.') }}</strong> · {{ $activeSnapshot->snapshot_date?->format('d/m/Y') ?? '-' }}</div>
            @endif
        </div>
    </div>

    @php
        $canManageBom = auth()->user()?->canAccessModule('master') ?? false;
        $bomHref = $canManageBom && $itemBom && $canMake
            ? route('master.item_boms.edit', $itemBom)
            : ($canManageBom && $isEdit && $item && $canMake && in_array($item->type, ['finished_good', 'wip'], true)
                ? route('master.item_boms.create', ['item_id' => $item->id])
                : null);
    @endphp
    @php
        $bomDisabledText = !$canManageBom
            ? 'Akses BOM dibatasi'
            : ($isEdit ? 'Aktifkan produksi sendiri' : 'Simpan item dulu');
    @endphp
    @if($isProductionItem)
    <div class="item-bom-menu mt-3" data-bom-menu>
        <div class="d-flex align-items-center gap-3">
            <div class="item-bom-menu-icon"><i class="bi bi-diagram-3"></i></div>
            <div>
                <div class="item-bom-menu-title">Bill of Materials (BOM)</div>
                <div class="item-bom-menu-text">Atur bahan dan komponen yang dipakai untuk memproduksi item ini.</div>
            </div>
        </div>
        @if($bomHref)
            <a href="{{ $bomHref }}" class="btn btn-sm btn-primary"><i class="bi bi-arrow-up-right-circle"></i>{{ $itemBom ? 'Kelola BOM' : 'Tambah BOM' }}</a>
        @else
            <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="{{ $bomDisabledText }}">{{ $bomDisabledText }}</button>
        @endif
    </div>
    @endif

    @include('master.items._barcodes_form')

    <div class="item-form-footer">
        <div class="d-flex gap-2">
            <a href="{{ route('master.items.index') }}" class="btn btn-item-outline px-4"><i class="bi bi-arrow-left"></i>Batal</a>
            <button type="submit" class="btn btn-item-primary px-4"><i class="bi bi-check2"></i>{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Item' }}</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.querySelector('[data-item-type-option]');
    const typeValue = document.querySelector('[data-item-type]');
    const categorySelect = document.querySelector('[data-item-category]');
    const quickCategoryModal = document.getElementById('quickItemCategoryModal');
    const quickCategoryCode = document.getElementById('quick-category-code');
    const quickCategoryName = document.getElementById('quick-category-name');
    const quickCategoryKind = document.getElementById('quick-category-kind');
    const quickCategorySubmit = document.querySelector('[data-quick-category-submit]');
    const quickCategoryError = document.querySelector('[data-quick-category-error]');
    const quickCategoryRoute = @json(route('master.items.quick_categories.store'));
    const quickTypeModal = document.getElementById('quickItemTypeModal');
    const quickTypeCode = document.getElementById('quick-type-code');
    const quickTypeName = document.getElementById('quick-type-name');
    const quickTypeBase = document.getElementById('quick-type-base');
    const quickTypeSubmit = document.querySelector('[data-quick-type-submit]');
    const quickTypeError = document.querySelector('[data-quick-type-error]');
    const quickTypeRoute = @json(route('master.items.quick_type_options.store'));
    const treatmentSelect = document.querySelector('[data-purchase-treatment]');
    const quickTreatmentModal = document.getElementById('quickPurchaseTreatmentModal');
    const quickTreatmentCode = document.getElementById('quick-treatment-code');
    const quickTreatmentName = document.getElementById('quick-treatment-name');
    const quickTreatmentAllocation = document.getElementById('quick-treatment-allocation');
    const quickTreatmentAccount = document.getElementById('quick-treatment-account');
    const quickTreatmentAccountWrap = document.querySelector('[data-quick-treatment-account-wrap]');
    const quickTreatmentSubmit = document.querySelector('[data-quick-treatment-submit]');
    const quickTreatmentError = document.querySelector('[data-quick-treatment-error]');
    const quickTreatmentRoute = @json(route('master.items.quick_purchase_treatments.store'));
    const quickAccountModal = document.getElementById('quickExpenseAccountModal');
    const quickAccountCode = document.getElementById('quick-account-code');
    const quickAccountName = document.getElementById('quick-account-name');
    const quickAccountSubmit = document.querySelector('[data-quick-account-submit]');
    const quickAccountError = document.querySelector('[data-quick-account-error]');
    const quickAccountRoute = @json(route('master.items.quick_expense_accounts.store'));
    const supplyWrap = document.querySelector('[data-supply-policy-wrap]');
    const canBuy = document.querySelector('[data-supply-can-buy]');
    const canMake = document.querySelector('[data-supply-can-make]');
    const defaultSupply = document.querySelector('[data-default-supply-source]');
    const supplyPresetGroup = document.querySelector('[data-supply-preset-group]');
    const supplierSearch = document.querySelector('[data-supplier-search]');
    let supplierOptions = Array.from(document.querySelectorAll('[data-supplier-option]'));
    const supplierCheckboxes = Array.from(document.querySelectorAll('[data-supplier-checkbox]'));
    const primarySupplier = document.querySelector('[data-primary-supplier]');
    const supplierCount = document.querySelector('[data-supplier-count]');
    const quickSupplierPanel = document.querySelector('[data-quick-supplier-panel]');
    const quickSupplierForm = document.querySelector('[data-quick-supplier-form]');
    const quickSupplierSubmit = document.querySelector('[data-quick-supplier-submit]');
    const quickSupplierError = document.querySelector('[data-quick-supplier-error]');
    const quickSupplierRoute = @json(route('master.items.quick_suppliers.store'));
    const supplySummary = document.querySelector('[data-supply-summary]');
    const supplySummaryText = document.querySelector('[data-supply-summary-text]');
    const allocation = document.getElementById('default-allocation');
    const hppFields = document.querySelector('[data-hpp-fields]');
    const bomMenu = document.querySelector('[data-bom-menu]');
    const codeInput = document.getElementById('item-code');
    const skuInput = document.getElementById('item-sku');
    const codeSuggest = document.querySelector('[data-code-suggest]');
    const codeDuplicateFeedback = document.querySelector('[data-code-duplicate-feedback]');
    const itemForm = document.querySelector('form[data-item-form]');
    const codeSuggestRoute = @json(route('master.items.code_suggestions'));
    const currentItemId = @json($item?->id);
    const expenseWrap = document.querySelector('[data-expense-account-wrap]');
    const activeInput = document.getElementById('item-active');
    const activeLabel = document.querySelector('[data-status-label]');
    const isEditForm = {{ $isEdit ? 'true' : 'false' }};
    let codeSuggestTimer = null;
    let codeSuggestRequest = null;
    let duplicateCodeItem = null;
    let lastCheckedCode = '';
    let allowSubmitOnce = false;
    let skuAutoSync = Boolean(skuInput && (
        !String(skuInput.value || '').trim()
        || normalizeItemCode(skuInput.value) === normalizeItemCode(codeInput?.value)
    ));
    let lastAutoSku = skuAutoSync ? String(skuInput?.value || '').trim() : '';

    function normalizeItemCode(value) {
        return String(value || '').trim().toUpperCase().replace(/\s+/g, '-');
    }

    function selectedBaseType() {
        return typeSelect?.selectedOptions?.[0]?.dataset.baseType || typeValue?.value || 'material';
    }

    function syncBaseType() {
        if (typeValue) typeValue.value = selectedBaseType();
        return selectedBaseType();
    }

    function syncTreatmentAllocation() {
        const option = treatmentSelect?.selectedOptions?.[0];
        if (allocation) allocation.value = option?.dataset.allocation || 'hpp';
        return allocation?.value || 'hpp';
    }

    function syncSkuFromCode() {
        if (!codeInput || !skuInput || !skuAutoSync) return;
        const normalizedCode = normalizeItemCode(codeInput.value);
        if (!normalizedCode) return;
        skuInput.value = normalizedCode;
        lastAutoSku = normalizedCode;
    }

    function allowedQuickCategoryKinds() {
        const type = selectedBaseType();
        return type === 'finished_good' || type === 'wip'
            ? ['product']
            : ['material', 'support', 'accessory', 'packaging', 'operational', 'other'];
    }

    function refreshQuickCategoryKinds() {
        if (!quickCategoryKind) return;
        const allowed = allowedQuickCategoryKinds();
        Array.from(quickCategoryKind.options).forEach(option => {
            option.hidden = !allowed.includes(option.value);
            option.disabled = !allowed.includes(option.value);
        });
        if (!allowed.includes(quickCategoryKind.value)) quickCategoryKind.value = allowed[0] || '';
    }

    function showQuickCategoryError(message = '') {
        if (!quickCategoryError) return;
        quickCategoryError.textContent = message;
        quickCategoryError.hidden = !message;
    }

    async function quickCreateCategory() {
        if (!quickCategorySubmit || !categorySelect) return;
        const code = String(quickCategoryCode?.value || '').trim().toUpperCase();
        const name = String(quickCategoryName?.value || '').trim();
        const kind = quickCategoryKind?.value || '';
        const allowed = allowedQuickCategoryKinds();

        showQuickCategoryError('');
        if (!code || !name || !allowed.includes(kind)) {
            showQuickCategoryError('Kode, nama, dan kelompok kategori wajib diisi sesuai tipe item.');
            return;
        }

        quickCategorySubmit.disabled = true;
        quickCategorySubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

        try {
            const response = await fetch(quickCategoryRoute, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ code, name, kind }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.ok) {
                const validationMessage = data.errors ? Object.values(data.errors).flat()[0] : null;
                throw new Error(validationMessage || data.message || 'Kategori gagal ditambahkan.');
            }

            const option = document.createElement('option');
            option.value = String(data.category.id);
            option.dataset.code = data.category.code;
            option.dataset.kind = data.category.kind;
            option.textContent = `${data.category.code} — ${data.category.name}`;
            categorySelect.appendChild(option);
            categorySelect.value = String(data.category.id);
            refreshCategory();
            if (window.GFID?.toast) window.GFID.toast('Kategori berhasil ditambahkan.');
            window.bootstrap?.Modal?.getOrCreateInstance(quickCategoryModal).hide();
        } catch (error) {
            showQuickCategoryError(error.message || 'Kategori gagal ditambahkan.');
        } finally {
            quickCategorySubmit.disabled = false;
            quickCategorySubmit.innerHTML = '<i class="bi bi-check2 me-1"></i>Simpan kategori';
        }
    }

    function showOptionError(node, message = '') {
        if (!node) return;
        node.textContent = message;
        node.hidden = !message;
    }

    function hideQuickModal(modal) {
        if (modal && window.bootstrap?.Modal) window.bootstrap.Modal.getOrCreateInstance(modal).hide();
    }

    async function postQuickOption(route, payload, submit, errorNode) {
        submit.disabled = true;
        submit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
        showOptionError(errorNode, '');
        try {
            const response = await fetch(route, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.ok) {
                const validationMessage = data.errors ? Object.values(data.errors).flat()[0] : null;
                throw new Error(validationMessage || data.message || 'Data gagal ditambahkan.');
            }
            return data;
        } catch (error) {
            showOptionError(errorNode, error.message || 'Data gagal ditambahkan.');
            return null;
        } finally {
            submit.disabled = false;
        }
    }

    async function quickCreateTypeOption() {
        const code = String(quickTypeCode?.value || '').trim().toLowerCase();
        const name = String(quickTypeName?.value || '').trim();
        const baseType = quickTypeBase?.value || 'material';
        if (!code || !name) {
            showOptionError(quickTypeError, 'Kode dan nama tipe wajib diisi.');
            return;
        }
        const data = await postQuickOption(quickTypeRoute, { code, name, base_type: baseType }, quickTypeSubmit, quickTypeError);
        if (!data) return;
        const option = document.createElement('option');
        option.value = String(data.option.id);
        option.dataset.baseType = data.option.base_type;
        option.textContent = data.option.name;
        typeSelect?.appendChild(option);
        if (typeSelect) typeSelect.value = option.value;
        syncBaseType();
        refreshCategory();
        refreshQuickCategoryKinds();
        hideQuickModal(quickTypeModal);
        if (window.GFID?.toast) window.GFID.toast('Tipe item berhasil ditambahkan.');
    }

    function refreshQuickTreatmentAccount() {
        if (quickTreatmentAccountWrap) quickTreatmentAccountWrap.hidden = quickTreatmentAllocation?.value !== 'expense';
    }

    async function quickCreateTreatment() {
        const code = String(quickTreatmentCode?.value || '').trim().toLowerCase();
        const name = String(quickTreatmentName?.value || '').trim();
        const allocationValue = quickTreatmentAllocation?.value || 'hpp';
        const accountId = quickTreatmentAccount?.value || '';
        if (!code || !name || (allocationValue === 'expense' && !accountId)) {
            showOptionError(quickTreatmentError, allocationValue === 'expense' ? 'Kode, nama, dan akun biaya wajib diisi.' : 'Kode dan nama perlakuan wajib diisi.');
            return;
        }
        const data = await postQuickOption(quickTreatmentRoute, {
            code,
            name,
            allocation: allocationValue,
            default_expense_account_id: accountId || null,
        }, quickTreatmentSubmit, quickTreatmentError);
        if (!data) return;
        const option = document.createElement('option');
        option.value = String(data.option.id);
        option.dataset.allocation = data.option.allocation;
        option.dataset.defaultAccount = data.option.default_expense_account_id || '';
        option.textContent = data.option.name;
        treatmentSelect?.appendChild(option);
        if (treatmentSelect) treatmentSelect.value = option.value;
        syncTreatmentAllocation();
        refreshAllocation();
        hideQuickModal(quickTreatmentModal);
        if (window.GFID?.toast) window.GFID.toast('Perlakuan pembelian berhasil ditambahkan.');
    }

    async function quickCreateExpenseAccount() {
        const code = String(quickAccountCode?.value || '').trim();
        const name = String(quickAccountName?.value || '').trim();
        if (!code || !name) {
            showOptionError(quickAccountError, 'Kode dan nama akun wajib diisi.');
            return;
        }
        const data = await postQuickOption(quickAccountRoute, { code, name }, quickAccountSubmit, quickAccountError);
        if (!data) return;
        const appendAccount = select => {
            if (!select) return;
            const option = document.createElement('option');
            option.value = String(data.account.id);
            option.textContent = data.account.code + ' — ' + data.account.name;
            option.dataset.accountCode = data.account.code;
            select.appendChild(option);
            select.value = option.value;
        };
        appendAccount(document.getElementById('expense-account'));
        appendAccount(quickTreatmentAccount);
        hideQuickModal(quickAccountModal);
        if (window.GFID?.toast) window.GFID.toast('Akun biaya berhasil ditambahkan.');
    }

    function hideCodeSuggestions() {
        if (!codeSuggest) return;
        codeSuggest.hidden = true;
        codeSuggest.replaceChildren();
    }

    function setDuplicateCode(item) {
        duplicateCodeItem = item || null;
        codeInput?.classList.toggle('is-invalid', Boolean(duplicateCodeItem));
        if (!codeDuplicateFeedback) return;
        codeDuplicateFeedback.textContent = duplicateCodeItem
            ? `Kode sudah digunakan oleh ${duplicateCodeItem.name} (${duplicateCodeItem.code}). Gunakan kode lain.`
            : '';
        codeDuplicateFeedback.classList.toggle('is-visible', Boolean(duplicateCodeItem));
    }

    function showDuplicateAlert(item = duplicateCodeItem) {
        if (!item) return;
        const message = `Kode ${item.code} sudah digunakan oleh ${item.name}. Silakan gunakan kode item yang berbeda.`;
        if (window.GFID?.errorAlert) {
            window.GFID.errorAlert(message, { title: 'Kode item sudah ada' });
            return;
        }
        if (window.Swal) {
            window.Swal.fire({
                icon: 'warning',
                title: 'Kode item sudah ada',
                text: message,
                confirmButtonText: 'Gunakan kode lain',
                confirmButtonColor: '#334155',
            });
        }
    }

    function showCodeSuggestions(items) {
        if (!codeSuggest) return;
        codeSuggest.replaceChildren();
        if (!items.length) {
            setDuplicateCode(null);
            hideCodeSuggestions();
            return;
        }

        const normalizedCode = normalizeItemCode(codeInput?.value);
        const duplicate = items.find(item => normalizeItemCode(item.code) === normalizedCode);
        setDuplicateCode(duplicate);
        const head = document.createElement('div');
        head.className = `item-code-suggest-head${duplicate ? ' is-duplicate' : ''}`;
        head.textContent = duplicate
            ? `Kode sudah digunakan oleh “${duplicate.name}”. Gunakan kode lain.`
            : 'Kode yang mirip sudah terdaftar:';
        codeSuggest.appendChild(head);

        items.forEach(item => {
            const row = document.createElement('button');
            row.type = 'button';
            row.className = 'item-code-suggest-row';
            row.title = `Pilih kode ${item.code}`;
            const code = document.createElement('span');
            code.className = 'item-code-suggest-code';
            code.textContent = item.code;
            const name = document.createElement('span');
            name.className = 'item-code-suggest-name';
            name.textContent = item.name;
            row.append(code, name);
            row.addEventListener('click', function () {
                codeInput.value = normalizeItemCode(item.code);
                lastCheckedCode = codeInput.value;
                setDuplicateCode(item);
                hideCodeSuggestions();
                codeInput.focus();
                showDuplicateAlert(item);
            });
            codeSuggest.appendChild(row);
        });

        codeSuggest.hidden = false;
    }

    async function checkItemCode() {
        if (!codeInput || !codeSuggest) return;
        const normalized = normalizeItemCode(codeInput.value);
        if (codeInput.value !== normalized) codeInput.value = normalized;
        if (!normalized) {
            lastCheckedCode = '';
            setDuplicateCode(null);
            hideCodeSuggestions();
            return;
        }

        if (codeSuggestRequest) codeSuggestRequest.abort();
        codeSuggestRequest = new AbortController();
        const params = new URLSearchParams({ q: normalized });
        if (currentItemId) params.set('exclude_id', currentItemId);

        try {
            const response = await fetch(`${codeSuggestRoute}?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: codeSuggestRequest.signal,
            });
            if (!response.ok) throw new Error('Gagal memeriksa kode item.');
            const data = await response.json();
            lastCheckedCode = normalized;
            showCodeSuggestions(Array.isArray(data.items) ? data.items : []);
        } catch (error) {
            if (error.name !== 'AbortError') hideCodeSuggestions();
        }
    }

    codeInput?.addEventListener('input', function () {
        syncSkuFromCode();
        lastCheckedCode = '';
        setDuplicateCode(null);
        window.clearTimeout(codeSuggestTimer);
        codeSuggestTimer = window.setTimeout(checkItemCode, 240);
    });
    codeInput?.addEventListener('focus', function () {
        syncSkuFromCode();
        if (normalizeItemCode(codeInput.value)) checkItemCode();
    });
    skuInput?.addEventListener('input', function () {
        if (String(skuInput.value || '').trim() !== lastAutoSku) skuAutoSync = false;
    });
    codeInput?.addEventListener('blur', function () {
        window.setTimeout(hideCodeSuggestions, 180);
    });
    document.addEventListener('click', function (event) {
        if (!event.target.closest('.item-code-field')) hideCodeSuggestions();
    });

    itemForm?.addEventListener('submit', async function (event) {
        if (allowSubmitOnce) {
            allowSubmitOnce = false;
            return;
        }

        const normalized = normalizeItemCode(codeInput?.value);
        if (codeInput && codeInput.value !== normalized) codeInput.value = normalized;

        if (duplicateCodeItem && lastCheckedCode === normalized) {
            event.preventDefault();
            showDuplicateAlert();
            codeInput?.focus();
            return;
        }

        if (normalized && lastCheckedCode !== normalized) {
            event.preventDefault();
            await checkItemCode();
            if (duplicateCodeItem) {
                showDuplicateAlert();
                codeInput?.focus();
                return;
            }

            allowSubmitOnce = true;
            if (typeof itemForm.requestSubmit === 'function') {
                itemForm.requestSubmit();
            } else {
                itemForm.submit();
            }
        }
    });

    function modeForType(type) {
        return type === 'finished_good' ? 'hybrid' : (type === 'wip' ? 'make' : 'outsource');
    }

    function refreshSupplierPicker() {
        const selected = supplierCheckboxes.filter(input => input.checked).map(input => String(input.value));
        if (supplierCount) supplierCount.textContent = `${selected.length} dipilih`;
        if (primarySupplier) {
            Array.from(primarySupplier.options).forEach(option => {
                if (!option.dataset.primaryOption) return;
                const visible = selected.includes(String(option.value));
                option.hidden = !visible;
                option.disabled = !visible;
            });
            if (primarySupplier.value && !selected.includes(String(primarySupplier.value))) {
                primarySupplier.value = selected[0] || '';
            }
            primarySupplier.disabled = selected.length === 0;
        }
    }

    function appendSupplierOption(supplier) {
        const supplierList = document.querySelector('[data-supplier-list]');
        if (!supplierList) return;

        const label = document.createElement('label');
        label.className = 'item-supplier-option';
        label.dataset.supplierOption = '';
        label.dataset.supplierCode = String(supplier.code || '').trim().toLowerCase();
        label.dataset.search = `${supplier.code} ${supplier.name}`.toLowerCase();

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'form-check-input mt-1';
        checkbox.name = 'supplier_ids[]';
        checkbox.value = String(supplier.id);
        checkbox.checked = true;
        checkbox.dataset.supplierCheckbox = '';
        checkbox.addEventListener('change', refreshSupplierPicker);

        const text = document.createElement('span');
        const code = document.createElement('strong');
        code.textContent = supplier.code;
        const name = document.createElement('small');
        name.textContent = supplier.name;
        text.append(code, name);
        label.append(checkbox, text);
        supplierList.appendChild(label);

        supplierOptions.push(label);
        supplierCheckboxes.push(checkbox);

        if (primarySupplier) {
            const option = document.createElement('option');
            option.value = String(supplier.id);
            option.textContent = `${supplier.code} — ${supplier.name}`;
            option.dataset.primaryOption = '';
            option.selected = true;
            primarySupplier.appendChild(option);
            primarySupplier.value = String(supplier.id);
        }
        refreshSupplierPicker();
    }

    function resetQuickSupplierForm() {
        if (!quickSupplierForm) return;
        quickSupplierForm.querySelectorAll('[data-quick-field]').forEach(field => {
            if (field.type === 'checkbox') {
                field.checked = false;
            } else if (field.tagName === 'SELECT') {
                field.value = field.dataset.quickDefault || '';
            } else {
                field.value = '';
            }
        });
    }

    async function quickCreateSupplier() {
        if (!quickSupplierForm || !quickSupplierSubmit) return;
        if (quickSupplierError) {
            quickSupplierError.hidden = true;
            quickSupplierError.textContent = '';
        }
        const codeField = quickSupplierForm.querySelector('[data-quick-field="code"]');
        const nameField = quickSupplierForm.querySelector('[data-quick-field="name"]');
        const normalizedCode = String(codeField?.value || '').trim().toLowerCase();
        const existingOption = supplierOptions.find(option => String(option.dataset.supplierCode || '').trim().toLowerCase() === normalizedCode);

        if (!normalizedCode || !String(nameField?.value || '').trim()) {
            if (quickSupplierError) {
                quickSupplierError.textContent = 'Kode dan nama supplier wajib diisi.';
                quickSupplierError.hidden = false;
            }
            return;
        }

        if (existingOption) {
            const existingCheckbox = existingOption.querySelector('[data-supplier-checkbox]');
            if (existingCheckbox) {
                existingCheckbox.checked = true;
                existingOption.hidden = false;
                if (supplierSearch) supplierSearch.value = '';
                if (primarySupplier) primarySupplier.value = existingCheckbox.value;
                refreshSupplierPicker();
            }
            resetQuickSupplierForm();
            if (quickSupplierPanel) quickSupplierPanel.hidden = true;
            return;
        }

        const fields = Array.from(quickSupplierForm.querySelectorAll('[data-quick-field]'));
        const payload = new FormData();
        fields.forEach(field => {
            if (field.type === 'checkbox' && !field.checked) return;
            payload.append(field.dataset.quickField, field.value);
        });
        quickSupplierSubmit.disabled = true;
        quickSupplierSubmit.textContent = 'Menyimpan...';
        if (quickSupplierError) {
            quickSupplierError.hidden = true;
            quickSupplierError.textContent = '';
        }

        try {
            const response = await fetch(quickSupplierRoute, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: payload,
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.ok) {
                const validationMessage = data.errors ? Object.values(data.errors).flat()[0] : null;
                throw new Error(validationMessage || data.message || 'Supplier gagal ditambahkan.');
            }

            appendSupplierOption(data.supplier);
            resetQuickSupplierForm();
            if (quickSupplierPanel) quickSupplierPanel.hidden = true;
        } catch (error) {
            if (quickSupplierError) {
                quickSupplierError.textContent = error.message || 'Supplier gagal ditambahkan.';
                quickSupplierError.hidden = false;
            }
        } finally {
            quickSupplierSubmit.disabled = false;
            quickSupplierSubmit.innerHTML = '<i class="bi bi-check2 me-1"></i>Simpan supplier';
        }
    }

    function applySupplyPreset(mode) {
        if (!canBuy || !canMake) return;
        canBuy.checked = mode === 'buy' || mode === 'hybrid';
        canMake.checked = mode === 'make' || mode === 'hybrid';
        if (defaultSupply) {
            defaultSupply.value = mode === 'make' || mode === 'hybrid'
                ? 'make'
                : (mode === 'outsource' ? 'outsource' : 'buy');
        }
        refreshSupply();
    }

    function refreshCategory() {
        if (!typeSelect || !categorySelect) return;
        const type = selectedBaseType();
        const allowed = type === 'finished_good' || type === 'wip' ? ['product'] : ['material','support','accessory','packaging','operational','other'];
        Array.from(categorySelect.options).forEach(option => { if (option.value) option.hidden = !allowed.includes(option.dataset.kind || 'other'); });
        let selected = categorySelect.selectedOptions[0];
        if (selected?.value && selected.hidden) {
            categorySelect.value = '';
            selected = categorySelect.selectedOptions[0];
        }
        applyCategoryPolicy(selected);
        const isSupplyItem = type === 'finished_good' || type === 'wip';
        if (bomMenu) bomMenu.hidden = !isSupplyItem;
        if (supplyWrap) supplyWrap.hidden = !isSupplyItem;
        if (canBuy) canBuy.disabled = !isSupplyItem;
        if (canMake) canMake.disabled = !isSupplyItem;
        if (defaultSupply) defaultSupply.disabled = !isSupplyItem;
        refreshSupply();
    }

    function refreshSupply() {
        if (!defaultSupply) return;
        const canBuyNow = !!canBuy?.checked, canMakeNow = !!canMake?.checked;
        Array.from(defaultSupply.options).forEach(option => { option.disabled = option.value === 'buy' ? !canBuyNow : option.value === 'make' ? !canMakeNow : false; });
        if (defaultSupply.selectedOptions[0]?.disabled) {
            const next = Array.from(defaultSupply.options).find(option => !option.disabled);
            if (next) defaultSupply.value = next.value;
        }
        let text = 'Belum ditentukan', cls = 'is-invalid';
        if (canBuyNow && canMakeNow) { text = 'Hybrid: bisa beli jadi atau produksi sendiri'; cls = 'is-hybrid'; }
        else if (canMakeNow) { text = 'Produksi sendiri'; cls = 'is-make'; }
        else if (canBuyNow) { text = 'Beli jadi'; cls = 'is-buy'; }
        else if (defaultSupply?.value === 'outsource') { text = 'Makloon / outsource'; cls = 'is-outsource'; }
        if (supplySummary) supplySummary.className = 'item-supply-summary ' + cls;
        if (supplySummaryText) supplySummaryText.textContent = text;
    }

    function refreshAllocation() {
        if (!expenseWrap || !allocation) return;
        expenseWrap.hidden = allocation.value !== 'expense';
        if (hppFields) {
            hppFields.hidden = allocation.value === 'expense';
            hppFields.querySelectorAll('input, textarea').forEach(field => {
                field.disabled = allocation.value === 'expense';
            });
        }
        const account = expenseWrap.querySelector('select');
        if (account) account.required = allocation.value === 'expense';
    }

    function applyCategoryPolicy(selected) {
        if (!allocation) return;
        if (!selected) {
            if (treatmentSelect) treatmentSelect.disabled = false;
            refreshAllocation();
            return;
        }

        const isOperational = selected.dataset.kind === 'operational';
        if (isOperational) {
            const expenseTreatment = Array.from(treatmentSelect?.options || []).find(option => option.dataset.allocation === 'expense');
            if (expenseTreatment) treatmentSelect.value = expenseTreatment.value;
            syncTreatmentAllocation();
            if (treatmentSelect) treatmentSelect.disabled = true;

            const expenseCode = selected.dataset.code === 'MNT' ? '6105' : '6104';
            const defaultExpenseAccount = expenseWrap?.querySelector(`option[data-account-code="${expenseCode}"]`);
            if (defaultExpenseAccount && expenseWrap) {
                expenseWrap.querySelector('select').value = defaultExpenseAccount.value;
            }
        } else {
            if (treatmentSelect) treatmentSelect.disabled = false;
        }

        refreshAllocation();
    }
    function refreshStatus() { if (activeLabel && activeInput) activeLabel.textContent = activeInput.checked ? 'Aktif / bisa dipakai' : 'Nonaktif / disembunyikan'; }
    typeSelect?.addEventListener('change', function () {
        syncBaseType();
        refreshCategory();
        refreshQuickCategoryKinds();
        if (!isEditForm || supplyPresetGroup?.dataset.userEdited !== '1') {
            const mode = modeForType(selectedBaseType());
            const preset = supplyPresetGroup?.querySelector(`[data-supply-preset][value="${mode}"]`);
            if (preset) preset.checked = true;
            applySupplyPreset(mode);
        }
    });
    supplyPresetGroup?.addEventListener('change', function (event) {
        const preset = event.target.closest('[data-supply-preset]');
        if (!preset) return;
        supplyPresetGroup.dataset.userEdited = '1';
        applySupplyPreset(preset.value);
    });
    canBuy?.addEventListener('change', refreshSupply);
    canMake?.addEventListener('change', refreshSupply);
    treatmentSelect?.addEventListener('change', function () {
        syncTreatmentAllocation();
        refreshAllocation();
    });
    categorySelect?.addEventListener('change', refreshCategory);
    activeInput?.addEventListener('change', refreshStatus);
    supplierCheckboxes.forEach(input => input.addEventListener('change', refreshSupplierPicker));
    document.querySelector('[data-open-category-modal]')?.addEventListener('click', function () {
        refreshQuickCategoryKinds();
        showQuickCategoryError('');
        if (quickCategoryCode) quickCategoryCode.value = '';
        if (quickCategoryName) quickCategoryName.value = '';
        if (quickCategoryModal && window.bootstrap?.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(quickCategoryModal).show();
        }
    });
    quickCategorySubmit?.addEventListener('click', quickCreateCategory);
    quickCategoryName?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            quickCreateCategory();
        }
    });
    document.querySelector('[data-open-type-modal]')?.addEventListener('click', function () {
        showOptionError(quickTypeError, '');
        if (quickTypeCode) quickTypeCode.value = '';
        if (quickTypeName) quickTypeName.value = '';
        if (quickTypeModal && window.bootstrap?.Modal) window.bootstrap.Modal.getOrCreateInstance(quickTypeModal).show();
    });
    quickTypeSubmit?.addEventListener('click', quickCreateTypeOption);
    document.querySelector('[data-open-treatment-modal]')?.addEventListener('click', function () {
        showOptionError(quickTreatmentError, '');
        if (quickTreatmentCode) quickTreatmentCode.value = '';
        if (quickTreatmentName) quickTreatmentName.value = '';
        if (quickTreatmentAllocation) quickTreatmentAllocation.value = 'expense';
        if (quickTreatmentAccount) quickTreatmentAccount.value = '';
        refreshQuickTreatmentAccount();
        if (quickTreatmentModal && window.bootstrap?.Modal) window.bootstrap.Modal.getOrCreateInstance(quickTreatmentModal).show();
    });
    quickTreatmentAllocation?.addEventListener('change', refreshQuickTreatmentAccount);
    quickTreatmentSubmit?.addEventListener('click', quickCreateTreatment);
    document.querySelector('[data-open-account-modal]')?.addEventListener('click', function () {
        showOptionError(quickAccountError, '');
        if (quickAccountCode) quickAccountCode.value = '';
        if (quickAccountName) quickAccountName.value = '';
        if (quickAccountModal && window.bootstrap?.Modal) window.bootstrap.Modal.getOrCreateInstance(quickAccountModal).show();
    });
    quickAccountSubmit?.addEventListener('click', quickCreateExpenseAccount);
    document.querySelector('[data-open-quick-supplier]')?.addEventListener('click', function () {
        if (!quickSupplierPanel) return;
        quickSupplierPanel.hidden = false;
        quickSupplierForm?.querySelector('[data-quick-field="code"]')?.focus();
    });
    document.querySelector('[data-close-quick-supplier]')?.addEventListener('click', function () {
        if (quickSupplierPanel) quickSupplierPanel.hidden = true;
    });
    quickSupplierSubmit?.addEventListener('click', quickCreateSupplier);
    quickSupplierForm?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            quickCreateSupplier();
        }
    });
    supplierSearch?.addEventListener('input', function () {
        const query = supplierSearch.value.trim().toLowerCase();
        supplierOptions.forEach(option => {
            option.hidden = query !== '' && !String(option.dataset.search || '').includes(query);
        });
    });
    syncBaseType();
    syncTreatmentAllocation();
    refreshQuickTreatmentAccount();
    refreshCategory();
    refreshQuickCategoryKinds();
    refreshSupply();
    refreshAllocation();
    refreshStatus();
    refreshSupplierPicker();
    syncSkuFromCode();
});
</script>
@endpush
