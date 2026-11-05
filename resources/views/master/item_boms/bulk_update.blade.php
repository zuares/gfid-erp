@extends('layouts.app')

@section('title', 'Update Massal BOM')

@push('head')
<style>
    .bom-bulk-page { width: 100%; max-width: 960px; margin-inline: auto; padding: 1rem var(--gf-content-gutter, .75rem) 4rem; color: var(--gf-color-content, #101828); }
    .bom-bulk-topbar { display: flex; justify-content: flex-end; margin-bottom: .75rem; }
    .bom-bulk-hero, .bom-bulk-card { border: 1px solid var(--gf-color-line, #e4e7ec); border-radius: 18px; background: var(--gf-color-surface, #fff); box-shadow: var(--gf-shadow-md, 0 12px 32px rgba(16,24,40,.08)); }
    .bom-bulk-hero { padding: 1.25rem 1.35rem; margin-bottom: 1rem; background: linear-gradient(120deg, color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 9%, var(--gf-color-surface, #fff)), var(--gf-color-surface, #fff)); }
    .bom-bulk-eyebrow { margin-bottom: .35rem; color: var(--gf-color-brand-strong, #4142bb); font-size: .68rem; font-weight: 850; letter-spacing: .1em; text-transform: uppercase; }
    .bom-bulk-title { margin: 0; font-size: clamp(1.35rem, 2.2vw, 1.8rem); font-weight: 850; letter-spacing: -.04em; }
    .bom-bulk-copy { max-width: 680px; margin: .45rem 0 0; color: var(--gf-color-muted, #667085); font-size: .8rem; line-height: 1.45; }
    .bom-bulk-card { padding: 1.25rem 1.35rem; }
    .bom-bulk-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
    .bom-bulk-field label { display: flex; align-items: center; gap: .4rem; margin: 0 0 .35rem .1rem; color: var(--gf-color-muted, #667085); font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
    .bom-bulk-required { color: var(--gf-color-danger, #b42318); font-size: .6rem; letter-spacing: 0; text-transform: none; }
    .bom-bulk-field input, .bom-bulk-field select { width: 100%; min-height: 42px; padding: .55rem .7rem; border: 1px solid var(--gf-color-line, #e4e7ec); border-radius: 10px; color: var(--gf-color-content, #101828); background: var(--gf-color-surface, #fff); font: inherit; font-size: .8rem; }
    .bom-bulk-field input:focus, .bom-bulk-field select:focus { border-color: var(--gf-color-brand, #5b5ce2); box-shadow: 0 0 0 3px color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 14%, transparent); outline: none; }
    .bom-bulk-field small { display: block; margin-top: .35rem; color: var(--gf-color-muted, #667085); font-size: .68rem; line-height: 1.45; }
    .bom-bulk-category-picker { position: relative; }
    .bom-bulk-category-trigger { display: flex; align-items: center; justify-content: space-between; gap: .7rem; width: 100%; min-height: 46px; padding: .65rem .75rem; border: 1px solid var(--gf-color-line, #e4e7ec); border-radius: 10px; color: var(--gf-color-content, #101828); background: var(--gf-color-surface, #fff); font: inherit; font-size: .8rem; text-align: left; cursor: pointer; }
    .bom-bulk-category-trigger:hover, .bom-bulk-category-trigger:focus-visible, .bom-bulk-category-picker.is-open .bom-bulk-category-trigger { border-color: var(--gf-color-brand, #5b5ce2); box-shadow: 0 0 0 3px color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 14%, transparent); outline: none; }
    .bom-bulk-category-trigger-copy { display: grid; gap: .12rem; min-width: 0; }
    .bom-bulk-category-trigger-label { overflow: hidden; color: var(--gf-color-content, #101828); font-weight: 750; text-overflow: ellipsis; white-space: nowrap; }
    .bom-bulk-category-trigger-meta { color: var(--gf-color-muted, #667085); font-size: .68rem; }
    .bom-bulk-category-menu { position: absolute; z-index: 30; top: calc(100% + .35rem); right: 0; left: 0; display: grid; gap: .55rem; padding: .65rem; border: 1px solid var(--gf-color-line, #e4e7ec); border-radius: 12px; background: var(--gf-color-surface, #fff); box-shadow: 0 16px 36px rgba(16, 24, 40, .16); }
    .bom-bulk-category-menu[hidden] { display: none; }
    .bom-bulk-category-search { min-height: 38px !important; padding: .45rem .6rem !important; }
    .bom-bulk-category-options { display: grid; gap: .35rem; max-height: 230px; overflow-y: auto; }
    .bom-bulk-category-option { display: flex; align-items: flex-start; gap: .55rem; padding: .55rem .6rem; border-radius: 8px; cursor: pointer; }
    .bom-bulk-category-option:hover, .bom-bulk-category-option:has(input:focus-visible) { background: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 6%, var(--gf-color-surface, #fff)); }
    .bom-bulk-category-option.is-all { border-bottom: 1px solid var(--gf-color-line, #e4e7ec); border-radius: 0; color: var(--gf-color-brand-strong, #4142bb); font-weight: 750; }
    .bom-bulk-category-option input { flex: 0 0 auto; width: 1rem; min-height: 1rem; margin-top: .12rem; accent-color: var(--gf-color-brand, #5b5ce2); }
    .bom-bulk-category-option-copy { display: grid; gap: .12rem; min-width: 0; }
    .bom-bulk-category-option-copy strong { font-size: .76rem; }
    .bom-bulk-category-option-copy span { color: var(--gf-color-muted, #667085); font-size: .68rem; line-height: 1.35; }
    .bom-bulk-category-empty { padding: .7rem; color: var(--gf-color-muted, #667085); font-size: .75rem; text-align: center; }
    .bom-bulk-search { position: relative; }
    .bom-bulk-results { display: grid; gap: .35rem; max-height: 220px; margin-top: .4rem; overflow: auto; }
    .bom-bulk-result { display: flex; align-items: center; justify-content: space-between; gap: .7rem; width: 100%; padding: .55rem .65rem; border: 1px solid var(--gf-color-line, #e4e7ec); border-radius: 10px; color: inherit; background: var(--gf-color-surface, #fff); font: inherit; font-size: .76rem; text-align: left; cursor: pointer; }
    .bom-bulk-result:hover, .bom-bulk-result:focus-visible { border-color: var(--gf-color-brand, #5b5ce2); background: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 5%, var(--gf-color-surface, #fff)); outline: none; }
    .bom-bulk-result strong { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    .bom-bulk-result span { color: var(--gf-color-muted, #667085); }
    .bom-bulk-selected { display: flex; align-items: center; justify-content: space-between; gap: .6rem; min-height: 42px; padding: .55rem .7rem; border: 1px solid color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 24%, var(--gf-color-line, #e4e7ec)); border-radius: 10px; color: var(--gf-color-brand-strong, #4142bb); background: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 6%, var(--gf-color-surface, #fff)); font-size: .78rem; font-weight: 750; }
    .bom-bulk-selected-copy { display: flex; align-items: center; gap: .45rem; min-width: 0; overflow: hidden; }
    .bom-bulk-selected-copy span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .bom-bulk-change { flex: 0 0 auto; padding: .25rem .45rem; border: 0; border-radius: 6px; color: var(--gf-color-brand-strong, #4142bb); background: transparent; font: inherit; font-size: .68rem; font-weight: 800; cursor: pointer; }
    .bom-bulk-change:hover, .bom-bulk-change:focus-visible { background: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 12%, transparent); outline: none; }
    .bom-bulk-impact { display: flex; align-items: center; gap: .6rem; margin: 1.1rem 0; padding: .8rem .9rem; border: 1px solid color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 18%, var(--gf-color-line, #e4e7ec)); border-radius: 12px; color: var(--gf-color-muted, #667085); background: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 5%, var(--gf-color-surface, #fff)); font-size: .78rem; }
    .bom-bulk-impact strong { color: var(--gf-color-content, #101828); font-size: 1.05rem; }
    .bom-bulk-actions { display: flex; justify-content: flex-end; gap: .5rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--gf-color-line, #e4e7ec); }
    .bom-bulk-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-height: 42px; padding: 0 .9rem; border: 1px solid transparent; border-radius: 10px; font: inherit; font-size: .78rem; font-weight: 750; text-decoration: none; cursor: pointer; }
    .bom-bulk-btn-primary { color: #fff; border-color: var(--gf-color-brand, #5b5ce2); background: var(--gf-color-brand, #5b5ce2); }
    .bom-bulk-btn-primary:hover, .bom-bulk-btn-primary:focus-visible { color: #fff; background: var(--gf-color-brand-strong, #4142bb); }
    .bom-bulk-btn-secondary { color: var(--gf-color-content, #101828); border-color: var(--gf-color-line, #e4e7ec); background: var(--gf-color-surface, #fff); }
    .bom-bulk-alert { padding: .7rem .85rem; margin-bottom: 1rem; border-radius: 11px; font-size: .78rem; }
    .bom-bulk-alert-success { color: #087443; border: 1px solid #abefc6; background: #ecfdf3; }
    .bom-bulk-alert-error { color: #b42318; border: 1px solid #fecdca; background: #fef3f2; }
    @media (max-width: 700px) {
        .bom-bulk-page { padding: .7rem .65rem 4rem; }
        .bom-bulk-grid { grid-template-columns: 1fr; gap: .8rem; }
        .bom-bulk-hero, .bom-bulk-card { padding: 1rem; border-radius: 15px; }
        .bom-bulk-actions { display: grid; grid-template-columns: 1fr 1fr; }
        .bom-bulk-btn { width: 100%; min-height: 44px; }
    }
</style>
@endpush

@section('content')
<main class="bom-bulk-page">
    <div class="bom-bulk-topbar">
        <a href="{{ route('master.item_boms.index') }}" class="bom-bulk-btn bom-bulk-btn-secondary" aria-label="Kembali ke daftar BOM">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>Kembali ke Daftar BOM
        </a>
    </div>

    @if(session('success'))
        <div class="bom-bulk-alert bom-bulk-alert-success" role="status"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bom-bulk-alert bom-bulk-alert-error" role="alert"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>{{ $errors->first() }}</div>
    @endif

    <section class="bom-bulk-hero">
        <div class="bom-bulk-eyebrow"><i class="bi bi-layers me-1" aria-hidden="true"></i>Master BOM</div>
        <h1 class="bom-bulk-title">Update Massal BOM per Kategori</h1>
        <p class="bom-bulk-copy">Tambahkan atau ubah satu komponen pada BOM finished good yang dipilih.</p>
    </section>

    <section class="bom-bulk-card">
        <form method="post" action="{{ route('master.item_boms.bulk_update') }}" data-bom-bulk-form>
            @csrf
            <div class="bom-bulk-grid">
                <div class="bom-bulk-field">
                    <label for="bulk-category-trigger">Kategori target <span class="bom-bulk-required">Wajib</span></label>
                    @php
                        $oldCategoryValues = collect(old('item_category_ids', []))->map(fn ($id) => (string) $id)->all();
                        $oldCategoryIds = collect($oldCategoryValues)->map(fn ($id) => (int) $id)->filter()->all();
                        $oldApplyAll = in_array('all', $oldCategoryValues, true)
                            || old('all_categories', '0') === '1';
                    @endphp
                    <div class="bom-bulk-category-picker" data-category-picker>
                        <input type="hidden" name="all_categories" value="0">
                        <div data-category-hidden-inputs></div>
                        <button id="bulk-category-trigger" type="button" class="bom-bulk-category-trigger" aria-haspopup="listbox" aria-expanded="false" aria-controls="bulk-category-menu" aria-describedby="bulk-category-help">
                            <span class="bom-bulk-category-trigger-copy">
                                <span class="bom-bulk-category-trigger-label" data-category-trigger-label>{{ $oldApplyAll ? 'Semua kategori finished good aktif' : 'Pilih kategori finished good' }}</span>
                                <span class="bom-bulk-category-trigger-meta" data-category-trigger-meta>{{ $oldApplyAll ? $allBomCount . ' BOM aktif' : 'Belum ada kategori dipilih' }}</span>
                            </span>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </button>
                        <div id="bulk-category-menu" class="bom-bulk-category-menu" role="dialog" aria-label="Pilih kategori finished good" hidden>
                            <input type="search" class="bom-bulk-category-search" placeholder="Cari kategori..." aria-label="Cari kategori" data-category-search>
                            <div class="bom-bulk-category-options" role="listbox" aria-multiselectable="true" aria-label="Daftar kategori finished good">
                                <label class="bom-bulk-category-option is-all" data-category-option data-category-value="all" data-bom-count="{{ $allBomCount }}">
                                    <input type="checkbox" data-category-checkbox @checked($oldApplyAll)>
                                    <span class="bom-bulk-category-option-copy">
                                        <strong>Semua kategori finished good aktif</strong>
                                        <span>{{ $allBomCount }} BOM aktif</span>
                                    </span>
                                </label>
                                @foreach($itemCategories as $category)
                                    <label class="bom-bulk-category-option" data-category-option data-category-value="{{ $category->id }}" data-category-search-text="{{ strtolower($category->code . ' ' . $category->name) }}" data-bom-count="{{ $category->bom_count }}">
                                        <input type="checkbox" data-category-checkbox @checked(in_array((int) $category->id, $oldCategoryIds, true))>
                                        <span class="bom-bulk-category-option-copy">
                                            <strong>{{ $category->code }} — {{ $category->name }}</strong>
                                            <span>{{ $category->bom_count }} BOM aktif</span>
                                        </span>
                                    </label>
                                @endforeach
                                <div class="bom-bulk-category-empty" data-category-empty hidden>Kategori tidak ditemukan.</div>
                            </div>
                        </div>
                    </div>
                    <small id="bulk-category-help">Pilih kategori satu per satu. “Semua” menargetkan seluruh BOM aktif.</small>
                    @error('item_category_ids')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                <div class="bom-bulk-field">
                    <label for="bulk-stage">Kelompok komponen <span class="bom-bulk-required">Wajib</span></label>
                    <select id="bulk-stage" name="usage_stage" required>
                        @foreach(\App\Models\ItemBomLine::usageStageLabels() as $value => $label)
                            <option value="{{ $value }}" @selected(old('usage_stage', \App\Models\ItemBomLine::STAGE_PACKING_SUPPLY) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="bom-bulk-field" style="grid-column: 1 / -1">
                    <label for="bulk-material-search">Komponen <span class="bom-bulk-required">Wajib</span></label>
                    <input id="bulk-material-search" type="search" autocomplete="off" placeholder="Ketik kode atau nama komponen..." aria-describedby="bulk-material-help">
                    <input id="bulk-material-id" type="hidden" name="material_item_id" value="{{ old('material_item_id') }}">
                    <div id="bulk-material-selected" class="bom-bulk-selected" role="status" @if(!old('material_item_id')) hidden @endif>
                        <span class="bom-bulk-selected-copy"><i class="bi bi-check-circle" aria-hidden="true"></i><span data-bulk-material-selected-text>{{ old('material_item_text', 'Komponen terpilih') }}</span></span>
                        <button type="button" class="bom-bulk-change" data-clear-material>Ganti</button>
                    </div>
                    <div id="bulk-material-results" class="bom-bulk-results" role="listbox" aria-label="Hasil pencarian komponen"></div>
                    @error('material_item_id')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                <div class="bom-bulk-field">
                    <label for="bulk-qty">Qty per pcs <span class="bom-bulk-required">Wajib</span></label>
                    <input id="bulk-qty" type="number" name="qty" step="0.00000001" min="0.0001" value="{{ old('qty') }}" placeholder="Contoh: 1 atau 0,5" required>
                </div>
                <div class="bom-bulk-field">
                    <label for="bulk-uom">Satuan</label>
                    <input id="bulk-uom" type="text" name="uom" maxlength="20" value="{{ old('uom') }}" placeholder="Otomatis dari master" readonly aria-readonly="true">
                </div>
                <div class="bom-bulk-field">
                    <label for="bulk-scrap">Waste (%) <span class="bom-bulk-required">Wajib</span></label>
                    <input id="bulk-scrap" type="number" name="scrap_pct" step="0.001" min="0" max="100" value="{{ old('scrap_pct', '0') }}" placeholder="0" required>
                </div>
                <div class="bom-bulk-field">
                    <label for="bulk-optional">Sifat komponen</label>
                    <select id="bulk-optional" name="is_optional">
                        <option value="0" @selected(old('is_optional', '0') === '0')>Wajib dihitung</option>
                        <option value="1" @selected(old('is_optional') === '1')>Opsional</option>
                    </select>
                </div>
            </div>

            <div class="bom-bulk-impact" data-bom-bulk-impact>
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <span>Pilih kategori untuk melihat target BOM.</span>
            </div>

            <div class="bom-bulk-actions">
                <a href="{{ route('master.item_boms.index') }}" class="bom-bulk-btn bom-bulk-btn-secondary">Batal</a>
                <button type="submit" class="bom-bulk-btn bom-bulk-btn-primary" data-bom-bulk-submit><i class="bi bi-check2-circle" aria-hidden="true"></i>Terapkan ke semua BOM</button>
            </div>
        </form>
    </section>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const categoryPicker = document.querySelector('[data-category-picker]');
    const categoryTrigger = document.querySelector('#bulk-category-trigger');
    const categoryMenu = document.querySelector('#bulk-category-menu');
    const categorySearch = document.querySelector('[data-category-search]');
    const categoryOptions = [...document.querySelectorAll('[data-category-option]')];
    const categoryEmpty = document.querySelector('[data-category-empty]');
    const hiddenCategoryInputs = document.querySelector('[data-category-hidden-inputs]');
    const allCategoriesFlag = document.querySelector('input[name="all_categories"]');
    const categoryTriggerLabel = document.querySelector('[data-category-trigger-label]');
    const categoryTriggerMeta = document.querySelector('[data-category-trigger-meta]');
    const clearMaterialButton = document.querySelector('[data-clear-material]');
    const impact = document.querySelector('[data-bom-bulk-impact]');
    const form = document.querySelector('[data-bom-bulk-form]');
    const search = document.querySelector('#bulk-material-search');
    const materialId = document.querySelector('#bulk-material-id');
    const selected = document.querySelector('#bulk-material-selected');
    const selectedText = document.querySelector('[data-bulk-material-selected-text]');
    const results = document.querySelector('#bulk-material-results');
    const uom = document.querySelector('#bulk-uom');
    const ajaxUrl = @json(route('master.item_boms.ajax_items'));
    let timer = null;
    let controller = null;
    const selectedCategories = new Set(categoryOptions
        .filter((option) => option.querySelector('[data-category-checkbox]')?.checked)
        .map((option) => option.dataset.categoryValue));

    const selectedCategoryOptions = () => categoryOptions
        .filter((option) => selectedCategories.has(option.dataset.categoryValue));

    const selectedCategoryCount = () => selectedCategories.has('all')
        ? Number(categoryOptions.find((option) => option.dataset.categoryValue === 'all')?.dataset.bomCount || 0)
        : selectedCategoryOptions().reduce((total, option) => total + Number(option.dataset.bomCount || 0), 0);

    const refreshCategorySummary = () => {
        const selected = selectedCategoryOptions();
        const applyAll = selectedCategories.has('all');
        const count = selectedCategoryCount();

        if (applyAll) {
            categoryTriggerLabel.textContent = 'Semua kategori finished good aktif';
            categoryTriggerMeta.textContent = `${count} BOM aktif`;
        } else if (!selected.length) {
            categoryTriggerLabel.textContent = 'Pilih kategori finished good';
            categoryTriggerMeta.textContent = 'Belum ada kategori dipilih';
        } else {
            const names = selected.map((option) => option.querySelector('strong')?.textContent?.trim() || 'Kategori');
            const visibleNames = names.slice(0, 2).join(', ');
            const remainder = names.length - 2;
            categoryTriggerLabel.textContent = remainder > 0 ? `${visibleNames} +${remainder} lainnya` : visibleNames;
            categoryTriggerMeta.textContent = `${names.length} kategori · ${count} BOM aktif`;
        }

        allCategoriesFlag.value = applyAll ? '1' : '0';
        hiddenCategoryInputs.replaceChildren();
        if (!applyAll) {
            selectedCategories.forEach((categoryId) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'item_category_ids[]';
                input.value = categoryId;
                hiddenCategoryInputs.appendChild(input);
            });
        }
    };

    const refreshImpact = () => {
        const selected = selectedCategoryOptions();
        const applyAll = selectedCategories.has('all');
        if (!selected.length) {
            impact.innerHTML = '<i class="bi bi-info-circle" aria-hidden="true"></i><span>Pilih satu atau beberapa kategori, atau pilih semua kategori finished good.</span>';
            return;
        }
        const count = applyAll
            ? selectedCategoryCount()
            : selected.reduce((total, option) => total + Number(option.dataset.bomCount || 0), 0);
        if (applyAll) {
            impact.innerHTML = `<i class="bi bi-layers" aria-hidden="true"></i><span><strong>${count}</strong> BOM finished good aktif akan menerima perubahan ini.</span>`;
            return;
        }
        impact.innerHTML = `<i class="bi bi-layers" aria-hidden="true"></i><span><strong>${count}</strong> BOM finished good aktif dari kategori terpilih akan menerima perubahan.</span>`;
    };

    const refreshCategoryState = () => {
        categoryOptions.forEach((option) => {
            option.querySelector('[data-category-checkbox]').checked = selectedCategories.has(option.dataset.categoryValue);
        });
        refreshCategorySummary();
        refreshImpact();
    };

    categoryOptions.forEach((option) => {
        option.querySelector('[data-category-checkbox]')?.addEventListener('change', (event) => {
            const value = option.dataset.categoryValue;
            if (event.target.checked) {
                if (value === 'all') {
                    selectedCategories.clear();
                } else {
                    selectedCategories.delete('all');
                }
                selectedCategories.add(value);
            } else {
                selectedCategories.delete(value);
            }
            refreshCategoryState();
        });
    });

    const toggleCategoryMenu = (open) => {
        categoryMenu.hidden = !open;
        categoryPicker.classList.toggle('is-open', open);
        categoryTrigger.setAttribute('aria-expanded', String(open));
        if (open) categorySearch.focus();
    };

    categoryTrigger?.addEventListener('click', () => toggleCategoryMenu(categoryMenu.hidden));
    document.addEventListener('click', (event) => {
        if (!categoryPicker?.contains(event.target)) toggleCategoryMenu(false);
    });
    categoryTrigger?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') toggleCategoryMenu(false);
    });
    categorySearch?.addEventListener('input', () => {
        const term = categorySearch.value.trim().toLowerCase();
        let visibleCount = 0;
        categoryOptions.forEach((option) => {
            const isAll = option.dataset.categoryValue === 'all';
            const matches = isAll
                ? term === '' || 'semua kategori finished good aktif'.includes(term)
                : (option.dataset.categorySearchText || '').includes(term);
            option.hidden = !matches;
            if (matches) visibleCount++;
        });
        categoryEmpty.hidden = visibleCount > 0;
    });

    const clearMaterial = () => {
        materialId.value = '';
        uom.value = '';
        selectedText.textContent = 'Komponen terpilih';
        selected.hidden = true;
        results.replaceChildren();
    };

    const renderResults = (items) => {
        results.replaceChildren();
        items.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'bom-bulk-result';
            const text = String(item.text || '');
            const [code, ...nameParts] = text.split(' — ');
            const codeNode = document.createElement('strong');
            const nameNode = document.createElement('span');
            codeNode.textContent = code;
            nameNode.textContent = nameParts.join(' — ');
            button.append(codeNode, nameNode);
            button.addEventListener('click', () => {
                materialId.value = item.id;
                selectedText.textContent = item.text;
                selected.hidden = false;
                search.value = '';
                results.replaceChildren();
                uom.value = item.unit || '';
            });
            results.appendChild(button);
        });
    };

    search?.addEventListener('input', () => {
        clearMaterial();
        window.clearTimeout(timer);
        const term = search.value.trim();
        if (term.length < 2) return;
        timer = window.setTimeout(async () => {
            controller?.abort();
            controller = new AbortController();
            const url = new URL(ajaxUrl, window.location.origin);
            url.searchParams.set('type', 'material');
            url.searchParams.set('q', term);
            try {
                const response = await fetch(url, { headers: { 'Accept': 'application/json' }, signal: controller.signal });
                if (!response.ok) throw new Error('Search failed');
                const payload = await response.json();
                renderResults(Array.isArray(payload.results) ? payload.results : []);
            } catch (error) {
                if (error.name !== 'AbortError') results.textContent = 'Pencarian komponen gagal.';
            }
        }, 250);
    });

    clearMaterialButton?.addEventListener('click', () => {
        clearMaterial();
        search.focus();
    });

    form?.addEventListener('submit', (event) => {
        if (!materialId.value) {
            event.preventDefault();
            search.focus();
            alert('Pilih komponen terlebih dahulu.');
            return;
        }
        const selected = selectedCategoryOptions();
        const applyAll = selectedCategories.has('all');
        const selectedCount = applyAll ? 0 : selected.length;
        if (!applyAll && selectedCount === 0) {
            event.preventDefault();
            categoryTrigger?.focus();
            alert('Pilih minimal satu kategori atau pilih semua kategori finished good.');
            return;
        }
        const count = applyAll
            ? selectedCategoryCount()
            : selected.reduce((total, option) => total + Number(option.dataset.bomCount || 0), 0);
        const scope = applyAll ? 'semua kategori finished good aktif' : `${selectedCount} kategori terpilih`;
        if (!window.confirm(`Terapkan perubahan ke ${count} BOM finished good pada ${scope}?`)) event.preventDefault();
    });
    refreshCategoryState();
});
</script>
@endpush
