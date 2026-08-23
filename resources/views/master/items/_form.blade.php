@php
    /** @var \App\Models\Item|null $item */
    $typeLabels = $typeLabels ?? [
        'material' => 'Material / Bahan',
        'wip' => 'Setengah Jadi (WIP)',
        'finished_good' => 'Barang Jadi (FG)',
    ];
    $isEdit = filled($item?->id);
    $itemBom = $itemBom ?? null;
    $supplierOptions = $suppliers ?? collect();
    $selectedSupplierIds = old('supplier_ids', $item?->suppliers?->pluck('id')->all() ?? []);
    $selectedSupplierIds = array_values(array_unique(array_map('intval', is_array($selectedSupplierIds) ? $selectedSupplierIds : [$selectedSupplierIds])));
    $storedPrimarySupplierId = $item?->suppliers?->first(fn ($supplier) => (bool) $supplier->pivot?->is_primary)?->id
        ?? ($selectedSupplierIds[0] ?? null);
    $selectedPrimarySupplierId = (int) old('primary_supplier_id', $storedPrimarySupplierId ?? 0);
    $itemType = old('type', $item?->type ?? 'material');
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
    .item-section-help { margin-top:2px; color:#94a3b8; font-size:.74rem; }
    .item-form .form-label { margin-bottom:5px; color:#475569; font-size:.7rem; font-weight:850; text-transform:uppercase; letter-spacing:.04em; }
    .item-form .form-control, .item-form .form-select { min-height:39px; border-color:#e2e8f0; border-radius:11px; color:#0f172a; font-size:.82rem; font-weight:600; box-shadow:none; }
    .item-form textarea.form-control { min-height:auto; }
    .item-form .form-control:focus, .item-form .form-select:focus { border-color:#94a3b8; box-shadow:0 0 0 .2rem rgba(15,23,42,.07); }
    .item-form .form-text { color:#94a3b8; font-size:.7rem; }
    .item-form .invalid-feedback { font-size:.7rem; }
    .item-code-field { position:relative; }
    .item-code-hint { margin-top:5px; color:#94a3b8; font-size:.68rem; }
    .item-code-suggest { position:absolute; z-index:30; top:calc(100% + 5px); left:0; min-width:320px; max-width:min(440px, 80vw); padding:7px; border:1px solid #cbd5e1; border-radius:13px; background:#fff; box-shadow:0 14px 32px rgba(15,23,42,.14); }
    .item-code-suggest[hidden] { display:none; }
    .item-code-suggest-head { padding:5px 7px 7px; color:#64748b; font-size:.68rem; font-weight:800; }
    .item-code-suggest-head.is-duplicate { color:#b91c1c; }
    .item-code-suggest-row { display:flex; align-items:flex-start; gap:8px; padding:7px; border-radius:9px; }
    .item-code-suggest-row + .item-code-suggest-row { margin-top:2px; }
    .item-code-suggest-row:hover { background:#f8fafc; }
    .item-code-suggest-code { color:#0f172a; font-size:.73rem; font-weight:900; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; white-space:nowrap; }
    .item-code-suggest-name { min-width:0; color:#64748b; font-size:.7rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
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
    @media(max-width:700px) { .item-form-section { padding:14px; } .item-supplier-list, .item-supply-preset, .item-quick-supplier-grid { grid-template-columns:1fr; } .item-accordion summary { align-items:flex-start; } .item-accordion-meta { white-space:nowrap; } .item-form-footer { align-items:stretch; flex-direction:column-reverse; } .item-form-footer > * { width:100%; } .item-form-footer .btn { width:100%; justify-content:center; } }
</style>
@endpush

<div class="item-form">
    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.8rem;"><i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}</div>
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
                    <div class="item-code-hint">Gunakan huruf kapital dan tanda - agar kode konsisten.</div>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label" for="item-sku">SKU</label>
                    <input id="item-sku" type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', $item?->sku) }}" placeholder="Kosong = mengikuti kode" autocomplete="off">
                    @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-5 col-md-8">
                    <label class="form-label" for="item-name">Nama item <span class="item-required">*</span></label>
                    <input id="item-name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $item?->name) }}" placeholder="Contoh: Kemeja Flannel Pria" required>
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
                    <select id="item-type" name="type" data-item-type class="form-select @error('type') is-invalid @enderror" required>
                        @foreach($typeLabels as $key => $label)
                            <option value="{{ $key }}" @selected(old('type', $item?->type ?? 'material') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label" for="item-category">Kategori</label>
                    <select id="item-category" name="item_category_id" data-item-category class="form-select @error('item_category_id') is-invalid @enderror">
                        <option value="">Tanpa kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" data-code="{{ $category->code }}" data-kind="{{ $category->kind }}" @selected(old('item_category_id', $item?->item_category_id) == $category->id)>{{ $category->code }} — {{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('item_category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label" for="default-allocation">Perlakuan pembelian</label>
                    <select id="default-allocation" name="default_allocation" class="form-select @error('default_allocation') is-invalid @enderror">
                        <option value="hpp" @selected($defaultAllocation === 'hpp')>Persediaan / HPP</option>
                        <option value="expense" @selected($defaultAllocation === 'expense')>Biaya langsung</option>
                    </select>
                    @error('default_allocation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-lg-3 col-md-6" data-expense-account-wrap>
                    <label class="form-label" for="expense-account">Akun biaya</label>
                    <select id="expense-account" name="default_expense_account_id" class="form-select @error('default_expense_account_id') is-invalid @enderror">
                        <option value="">Pilih akun</option>
                        @foreach($expenseAccounts ?? [] as $account)
                            <option value="{{ $account->id }}" data-account-code="{{ $account->code }}" @selected(old('default_expense_account_id', $item?->default_expense_account_id) == $account->id)>{{ $account->code }} — {{ $account->name }}</option>
                        @endforeach
                    </select>
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
                                            'buy' => ['Beli jadi', 'Supplier'],
                                            'make' => ['Produksi sendiri', 'BOM'],
                                            'hybrid' => ['Hybrid', 'Beli / produksi'],
                                            'outsource' => ['Makloon', 'Pihak ketiga'],
                                        ] as $mode => [$label, $helpText])
                                            <label class="item-supply-preset-option">
                                                <input type="radio" name="supply_mode_preset" value="{{ $mode }}" class="form-check-input mt-1" data-supply-preset @checked($supplyMode === $mode)>
                                                <span><strong>{{ $label }}</strong><small>{{ $helpText }}</small></span>
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
                            <input id="hpp-notes" type="text" name="hpp_notes" class="form-control @error('hpp_notes') is-invalid @enderror" value="{{ old('hpp_notes', $activeSnapshot?->notes) }}" maxlength="255" placeholder="Contoh: HPP awal dari faktur">
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
        $bomHref = $itemBom && $canMake
            ? route('master.item_boms.edit', $itemBom)
            : ($isEdit && $item && $canMake && in_array($item->type, ['finished_good', 'wip'], true)
                ? route('master.item_boms.create', ['item_id' => $item->id])
                : null);
    @endphp
    @php $bomDisabledText = $isEdit ? 'Aktifkan produksi sendiri' : 'Simpan item dulu'; @endphp
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
        <div class="item-section-help"><span class="item-required">*</span> wajib diisi</div>
        <div class="d-flex gap-2">
            <a href="{{ route('master.items.index') }}" class="btn btn-item-outline px-4"><i class="bi bi-arrow-left"></i>Batal</a>
            <button type="submit" class="btn btn-item-primary px-4"><i class="bi bi-check2"></i>{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Item' }}</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.querySelector('[data-item-type]');
    const categorySelect = document.querySelector('[data-item-category]');
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
    const codeSuggest = document.querySelector('[data-code-suggest]');
    const codeSuggestRoute = @json(route('master.items.code_suggestions'));
    const currentItemId = @json($item?->id);
    const expenseWrap = document.querySelector('[data-expense-account-wrap]');
    const activeInput = document.getElementById('item-active');
    const activeLabel = document.querySelector('[data-status-label]');
    const isEditForm = {{ $isEdit ? 'true' : 'false' }};
    let codeSuggestTimer = null;
    let codeSuggestRequest = null;

    function normalizeItemCode(value) {
        return String(value || '').trim().toUpperCase().replace(/\s+/g, '-');
    }

    function hideCodeSuggestions() {
        if (!codeSuggest) return;
        codeSuggest.hidden = true;
        codeSuggest.replaceChildren();
    }

    function showCodeSuggestions(items) {
        if (!codeSuggest) return;
        codeSuggest.replaceChildren();
        if (!items.length) {
            hideCodeSuggestions();
            return;
        }

        const normalizedCode = normalizeItemCode(codeInput?.value);
        const duplicate = items.find(item => normalizeItemCode(item.code) === normalizedCode);
        const head = document.createElement('div');
        head.className = `item-code-suggest-head${duplicate ? ' is-duplicate' : ''}`;
        head.textContent = duplicate
            ? `Kode sudah digunakan oleh “${duplicate.name}”. Gunakan kode lain.`
            : 'Kode yang mirip sudah terdaftar:';
        codeSuggest.appendChild(head);

        items.forEach(item => {
            const row = document.createElement('div');
            row.className = 'item-code-suggest-row';
            const code = document.createElement('span');
            code.className = 'item-code-suggest-code';
            code.textContent = item.code;
            const name = document.createElement('span');
            name.className = 'item-code-suggest-name';
            name.textContent = item.name;
            row.append(code, name);
            codeSuggest.appendChild(row);
        });

        codeSuggest.hidden = false;
    }

    async function checkItemCode() {
        if (!codeInput || !codeSuggest) return;
        const normalized = normalizeItemCode(codeInput.value);
        if (codeInput.value !== normalized) codeInput.value = normalized;
        if (!normalized) {
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
            showCodeSuggestions(Array.isArray(data.items) ? data.items : []);
        } catch (error) {
            if (error.name !== 'AbortError') hideCodeSuggestions();
        }
    }

    codeInput?.addEventListener('input', function () {
        window.clearTimeout(codeSuggestTimer);
        codeSuggestTimer = window.setTimeout(checkItemCode, 240);
    });
    codeInput?.addEventListener('focus', function () {
        if (normalizeItemCode(codeInput.value)) checkItemCode();
    });
    codeInput?.addEventListener('blur', function () {
        window.setTimeout(hideCodeSuggestions, 180);
    });
    document.addEventListener('click', function (event) {
        if (!event.target.closest('.item-code-field')) hideCodeSuggestions();
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
        const allowed = typeSelect.value === 'finished_good' || typeSelect.value === 'wip' ? ['product'] : ['material','support','accessory','packaging','operational','other'];
        Array.from(categorySelect.options).forEach(option => { if (option.value) option.hidden = !allowed.includes(option.dataset.kind || 'other'); });
        let selected = categorySelect.selectedOptions[0];
        if (selected?.value && selected.hidden) {
            categorySelect.value = '';
            selected = categorySelect.selectedOptions[0];
        }
        applyCategoryPolicy(selected);
        const isSupplyItem = typeSelect.value === 'finished_good' || typeSelect.value === 'wip';
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
        if (!allocation || !selected) return;

        const isOperational = selected.dataset.kind === 'operational';
        if (isOperational) {
            allocation.value = 'expense';
            allocation.disabled = true;

            const expenseCode = selected.dataset.code === 'MNT' ? '6105' : '6104';
            const defaultExpenseAccount = expenseWrap?.querySelector(`option[data-account-code="${expenseCode}"]`);
            if (defaultExpenseAccount && expenseWrap) {
                expenseWrap.querySelector('select').value = defaultExpenseAccount.value;
            }
        } else {
            allocation.disabled = false;
        }

        refreshAllocation();
    }
    function refreshStatus() { if (activeLabel && activeInput) activeLabel.textContent = activeInput.checked ? 'Aktif / bisa dipakai' : 'Nonaktif / disembunyikan'; }
    typeSelect?.addEventListener('change', function () {
        refreshCategory();
        if (!isEditForm || supplyPresetGroup?.dataset.userEdited !== '1') {
            const mode = modeForType(typeSelect.value);
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
    allocation?.addEventListener('change', refreshAllocation);
    categorySelect?.addEventListener('change', refreshCategory);
    activeInput?.addEventListener('change', refreshStatus);
    supplierCheckboxes.forEach(input => input.addEventListener('change', refreshSupplierPicker));
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
    refreshCategory();
    refreshSupply();
    refreshAllocation();
    refreshStatus();
    refreshSupplierPicker();
});
</script>
@endpush
