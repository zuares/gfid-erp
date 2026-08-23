@php
    /** @var \App\Models\Item|null $item */
    $typeLabels = $typeLabels ?? [
        'material' => 'Material / Bahan',
        'wip' => 'Setengah Jadi (WIP)',
        'finished_good' => 'Barang Jadi (FG)',
    ];
    $isEdit = filled($item?->id);
    $itemBom = $itemBom ?? null;
    $legacySource = old('production_source', $item?->production_source ?? \App\Models\Item::PRODUCTION_BUY);
    $canBuy = old('can_buy', $item?->can_buy ?? ($legacySource === \App\Models\Item::PRODUCTION_BUY ? 1 : 0));
    $canMake = old('can_make', $item?->can_make ?? ($legacySource === \App\Models\Item::PRODUCTION_IN_HOUSE ? 1 : 0));
    $defaultSupplySource = old('default_supply_source', $item?->default_supply_source ?? match ($legacySource) {
        \App\Models\Item::PRODUCTION_IN_HOUSE => \App\Models\Item::SUPPLY_MAKE,
        \App\Models\Item::PRODUCTION_OUTSOURCE => \App\Models\Item::SUPPLY_OUTSOURCE,
        default => \App\Models\Item::SUPPLY_BUY,
    });
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
    .item-section-head { display:flex; align-items:flex-start; gap:10px; margin-bottom:14px; }
    .item-section-icon { width:32px; height:32px; flex:0 0 32px; display:flex; align-items:center; justify-content:center; color:#334155; background:#f1f5f9; border-radius:10px; }
    .item-section-title { margin:0; color:#0f172a; font-size:.95rem; font-weight:900; letter-spacing:-.02em; }
    .item-section-help { margin-top:2px; color:#94a3b8; font-size:.74rem; }
    .item-form .form-label { margin-bottom:5px; color:#475569; font-size:.7rem; font-weight:850; text-transform:uppercase; letter-spacing:.04em; }
    .item-form .form-control, .item-form .form-select { min-height:39px; border-color:#e2e8f0; border-radius:11px; color:#0f172a; font-size:.82rem; font-weight:600; box-shadow:none; }
    .item-form textarea.form-control { min-height:auto; }
    .item-form .form-control:focus, .item-form .form-select:focus { border-color:#94a3b8; box-shadow:0 0 0 .2rem rgba(15,23,42,.07); }
    .item-form .form-text { color:#94a3b8; font-size:.7rem; }
    .item-form .invalid-feedback { font-size:.7rem; }
    .item-required { color:#dc2626; }
    .item-supply-box { padding:13px; border:1px solid #e2e8f0; border-radius:15px; background:#f8fafc; }
    .item-supply-options { display:grid; gap:8px; }
    .item-supply-option { display:flex; align-items:center; gap:9px; padding:10px; border:1px solid #e2e8f0; border-radius:11px; background:#fff; cursor:pointer; }
    .item-supply-option:hover { border-color:#94a3b8; }
    .item-supply-option strong { display:block; font-size:.78rem; }
    .item-supply-option span { display:block; color:#94a3b8; font-size:.69rem; margin-top:1px; }
    .item-supply-summary { display:flex; align-items:center; gap:7px; margin-top:11px; color:#64748b; font-size:.72rem; }
    .item-supply-summary .dot { width:8px; height:8px; border-radius:50%; background:#94a3b8; }
    .item-supply-summary.is-hybrid .dot { background:#7c3aed; }
    .item-supply-summary.is-make .dot { background:#059669; }
    .item-supply-summary.is-buy .dot { background:#2563eb; }
    .item-supply-summary.is-invalid .dot { background:#d97706; }
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
    body[data-theme="dark"] .item-form-card, body[data-theme="dark"] .item-supply-box, body[data-theme="dark"] .item-supply-option, body[data-theme="dark"] .item-status-switch { background:#0f172a; border-color:#334155; }
    body[data-theme="dark"] .item-section-title { color:#f8fafc; }
    body[data-theme="dark"] .item-section-icon { color:#cbd5e1; background:#1e293b; }
    body[data-theme="dark"] .item-form .form-label { color:#cbd5e1; }
    body[data-theme="dark"] .item-form .form-control, body[data-theme="dark"] .item-form .form-select { color:#f8fafc; background:#0f172a; border-color:#475569; }
    body[data-theme="dark"] .item-bom-menu { background:#172554; border-color:#3730a3; }
    body[data-theme="dark"] .item-bom-menu-title { color:#e0e7ff; }
    @media(max-width:700px) { .item-form-section { padding:14px; } .item-form-footer { align-items:stretch; flex-direction:column-reverse; } .item-form-footer > * { width:100%; } .item-form-footer .btn { width:100%; justify-content:center; } }
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
                <div><h2 class="item-section-title">Identitas item</h2><div class="item-section-help">Gunakan kode yang konsisten agar mudah dicari di pembelian, produksi, dan inventory.</div></div>
            </div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="item-code">Kode item <span class="item-required">*</span></label>
                    <input id="item-code" type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $item?->code) }}" autocomplete="off" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="item-sku">SKU</label>
                    <input id="item-sku" type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', $item?->sku) }}" placeholder="Kosong = mengikuti kode" autocomplete="off">
                    @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="item-name">Nama item <span class="item-required">*</span></label>
                    <input id="item-name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $item?->name) }}" placeholder="Contoh: Kemeja Flannel Pria" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="item-unit">Satuan <span class="item-required">*</span></label>
                    <input id="item-unit" type="text" name="unit" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit', $item?->unit ?? 'pcs') }}" required>
                    @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="item-form-section">
            <div class="item-section-head">
                <div class="item-section-icon"><i class="bi bi-diagram-3"></i></div>
                <div><h2 class="item-section-title">Klasifikasi & metode pasok</h2><div class="item-section-help">Tipe menentukan alur item. Untuk barang jadi, aktifkan satu atau dua cara mendapatkan barang.</div></div>
            </div>
            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="item-type">Tipe item <span class="item-required">*</span></label>
                            <select id="item-type" name="type" data-item-type class="form-select @error('type') is-invalid @enderror" required>
                                @foreach($typeLabels as $key => $label)
                                    <option value="{{ $key }}" @selected(old('type', $item?->type ?? 'material') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="item-category">Kategori</label>
                            <select id="item-category" name="item_category_id" data-item-category class="form-select @error('item_category_id') is-invalid @enderror">
                                <option value="">Tanpa kategori</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" data-kind="{{ $category->kind }}" @selected(old('item_category_id', $item?->item_category_id) == $category->id)>{{ $category->code }} — {{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text" data-item-category-help></div>
                            @error('item_category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="default-allocation">Sifat pembelian</label>
                            <select id="default-allocation" name="default_allocation" class="form-select @error('default_allocation') is-invalid @enderror">
                                <option value="hpp" @selected(old('default_allocation', $item?->default_allocation ?? 'hpp') === 'hpp')>Masuk stok / aset</option>
                                <option value="expense" @selected(old('default_allocation', $item?->default_allocation) === 'expense')>Langsung biaya / expense</option>
                            </select>
                            @error('default_allocation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5" data-expense-account-wrap>
                            <label class="form-label" for="expense-account">Akun biaya</label>
                            <select id="expense-account" name="default_expense_account_id" class="form-select @error('default_expense_account_id') is-invalid @enderror">
                                <option value="">Pilih akun</option>
                                @foreach($expenseAccounts ?? [] as $account)
                                    <option value="{{ $account->id }}" @selected(old('default_expense_account_id', $item?->default_expense_account_id) == $account->id)>{{ $account->code }} — {{ $account->name }}</option>
                                @endforeach
                            </select>
                            @error('default_expense_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="col-lg-5" data-supply-policy-wrap>
                    <div class="item-supply-box h-100">
                        <label class="form-label mb-2">Metode pasok</label>
                        <div class="item-supply-options">
                            <input type="hidden" name="can_buy" value="0">
                            <label class="item-supply-option" for="can-buy">
                                <input id="can-buy" type="checkbox" class="form-check-input mt-0" name="can_buy" value="1" data-supply-can-buy @checked((int) $canBuy === 1)>
                                <span><strong>Bisa dibeli jadi</strong><span>Digunakan saat barang jadi tersedia dari supplier.</span></span>
                            </label>
                            <input type="hidden" name="can_make" value="0">
                            <label class="item-supply-option" for="can-make">
                                <input id="can-make" type="checkbox" class="form-check-input mt-0" name="can_make" value="1" data-supply-can-make @checked((int) $canMake === 1)>
                                <span><strong>Bisa diproduksi sendiri</strong><span>Digunakan saat item memiliki BOM dan alur produksi.</span></span>
                            </label>
                        </div>
                        <label class="form-label mt-3" for="default-supply-source">Prioritas default</label>
                        <select id="default-supply-source" name="default_supply_source" data-default-supply-source class="form-select @error('default_supply_source') is-invalid @enderror">
                            @foreach(\App\Models\Item::supplySourceLabels() as $key => $label)
                                <option value="{{ $key }}" @selected($defaultSupplySource === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="item-supply-summary" data-supply-summary><span class="dot"></span><span data-supply-summary-text>Belum ditentukan</span></div>
                        <div class="form-text mt-2">Jika dua pilihan aktif, item akan tampil sebagai Hybrid. Prioritas ini menjadi pilihan awal saat membuat rencana.</div>
                        @error('can_buy')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @error('can_make')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @error('default_supply_source')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="mt-3" data-status-wrap>
                <label class="form-label" for="item-active">Status item</label>
                <div class="item-status-switch">
                    <span><strong data-status-label>{{ old('active', $item?->active ?? 1) ? 'Aktif / bisa dipakai' : 'Nonaktif / disembunyikan' }}</strong><span class="d-block item-section-help">Item nonaktif tidak dipakai untuk transaksi baru.</span></span>
                    <input type="hidden" name="active" value="0">
                    <div class="form-check form-switch mb-0"><input id="item-active" type="checkbox" name="active" value="1" class="form-check-input" @checked(old('active', $item?->active ?? 1) == 1)></div>
                </div>
            </div>
        </div>

        <div class="item-form-section">
            <div class="item-section-head">
                <div class="item-section-icon"><i class="bi bi-cash-coin"></i></div>
                <div><h2 class="item-section-title">Harga & biaya dasar</h2><div class="item-section-help">HPP sementara dapat diperbarui lagi dari menu Set HPP.</div></div>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="last-purchase-price">Harga beli terakhir (Rp)</label>
                    <input id="last-purchase-price" type="number" min="0" step="0.01" name="last_purchase_price" class="form-control @error('last_purchase_price') is-invalid @enderror" value="{{ old('last_purchase_price', $item?->last_purchase_price) }}" placeholder="0">
                    <div class="form-text">Biasanya ter-update dari penerimaan PO.</div>
                    @error('last_purchase_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="unit-cost">HPP sementara (Rp / unit)</label>
                    <input id="unit-cost" type="number" min="0" step="0.01" name="unit_cost" class="form-control @error('unit_cost') is-invalid @enderror" value="{{ old('unit_cost', $activeSnapshot?->unit_cost) }}" placeholder="0">
                    @error('unit_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="hpp-notes">Catatan HPP</label>
                    <input id="hpp-notes" type="text" name="hpp_notes" class="form-control @error('hpp_notes') is-invalid @enderror" value="{{ old('hpp_notes', $activeSnapshot?->notes) }}" maxlength="255" placeholder="Contoh: HPP awal dari faktur">
                    @error('hpp_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            @if($activeSnapshot)
                <div class="form-text mt-3"><i class="bi bi-clock-history me-1"></i>HPP aktif saat ini: <strong>Rp {{ number_format((float) $activeSnapshot->unit_cost, 0, ',', '.') }}</strong> · {{ $activeSnapshot->snapshot_date?->format('d/m/Y') ?? '-' }}</div>
            @endif
        </div>
    </div>

    @php
        $isProductionItem = in_array(old('type', $item?->type ?? 'material'), ['finished_good', 'wip'], true);
        $bomHref = $itemBom
            ? route('master.item_boms.edit', $itemBom)
            : ($isEdit && $item && in_array($item->type, ['finished_good', 'wip'], true)
                ? route('master.item_boms.create', ['item_id' => $item->id])
                : null);
    @endphp
    @php $bomDisabledText = $isProductionItem ? 'Simpan item dulu' : 'Pilih FG/WIP dulu'; @endphp
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
    const supplySummary = document.querySelector('[data-supply-summary]');
    const supplySummaryText = document.querySelector('[data-supply-summary-text]');
    const allocation = document.getElementById('default-allocation');
    const expenseWrap = document.querySelector('[data-expense-account-wrap]');
    const activeInput = document.getElementById('item-active');
    const activeLabel = document.querySelector('[data-status-label]');
    const help = document.querySelector('[data-item-category-help]');
    const categoryHelp = { finished_good: 'Pilih kategori produk untuk barang jadi.', wip: 'WIP mengikuti kategori produk jadi.', material: 'Pilih kategori bahan, pendukung, accessories, atau packaging.' };

    function refreshCategory() {
        if (!typeSelect || !categorySelect) return;
        const allowed = typeSelect.value === 'finished_good' || typeSelect.value === 'wip' ? ['product'] : ['material','support','accessory','packaging','other'];
        Array.from(categorySelect.options).forEach(option => { if (option.value) option.hidden = !allowed.includes(option.dataset.kind || 'other'); });
        const selected = categorySelect.selectedOptions[0];
        if (selected?.value && selected.hidden) categorySelect.value = '';
        if (help) help.textContent = categoryHelp[typeSelect.value] || '';
        const isSupplyItem = typeSelect.value === 'finished_good' || typeSelect.value === 'wip';
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
        if (supplySummary) supplySummary.className = 'item-supply-summary ' + cls;
        if (supplySummaryText) supplySummaryText.textContent = text;
    }

    function refreshAllocation() { if (expenseWrap && allocation) expenseWrap.hidden = allocation.value !== 'expense'; }
    function refreshStatus() { if (activeLabel && activeInput) activeLabel.textContent = activeInput.checked ? 'Aktif / bisa dipakai' : 'Nonaktif / disembunyikan'; }
    typeSelect?.addEventListener('change', refreshCategory);
    canBuy?.addEventListener('change', refreshSupply);
    canMake?.addEventListener('change', refreshSupply);
    allocation?.addEventListener('change', refreshAllocation);
    activeInput?.addEventListener('change', refreshStatus);
    refreshCategory(); refreshAllocation(); refreshStatus();
});
</script>
@endpush
