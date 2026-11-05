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
    .bom-bulk-copy { max-width: 680px; margin: .45rem 0 0; color: var(--gf-color-muted, #667085); font-size: .8rem; line-height: 1.55; }
    .bom-bulk-card { padding: 1.25rem 1.35rem; }
    .bom-bulk-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
    .bom-bulk-field label { display: block; margin: 0 0 .35rem .1rem; color: var(--gf-color-muted, #667085); font-size: .68rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
    .bom-bulk-field input, .bom-bulk-field select { width: 100%; min-height: 42px; padding: .55rem .7rem; border: 1px solid var(--gf-color-line, #e4e7ec); border-radius: 10px; color: var(--gf-color-content, #101828); background: var(--gf-color-surface, #fff); font: inherit; font-size: .8rem; }
    .bom-bulk-field input:focus, .bom-bulk-field select:focus { border-color: var(--gf-color-brand, #5b5ce2); box-shadow: 0 0 0 3px color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 14%, transparent); outline: none; }
    .bom-bulk-field small { display: block; margin-top: .35rem; color: var(--gf-color-muted, #667085); font-size: .68rem; line-height: 1.45; }
    .bom-bulk-search { position: relative; }
    .bom-bulk-results { display: grid; gap: .35rem; max-height: 220px; margin-top: .4rem; overflow: auto; }
    .bom-bulk-result { display: flex; align-items: center; justify-content: space-between; gap: .7rem; width: 100%; padding: .55rem .65rem; border: 1px solid var(--gf-color-line, #e4e7ec); border-radius: 10px; color: inherit; background: var(--gf-color-surface, #fff); font: inherit; font-size: .76rem; text-align: left; cursor: pointer; }
    .bom-bulk-result:hover, .bom-bulk-result:focus-visible { border-color: var(--gf-color-brand, #5b5ce2); background: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 5%, var(--gf-color-surface, #fff)); outline: none; }
    .bom-bulk-result strong { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    .bom-bulk-result span { color: var(--gf-color-muted, #667085); }
    .bom-bulk-selected { display: flex; align-items: center; gap: .45rem; min-height: 42px; padding: .55rem .7rem; border: 1px solid color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 24%, var(--gf-color-line, #e4e7ec)); border-radius: 10px; color: var(--gf-color-brand-strong, #4142bb); background: color-mix(in srgb, var(--gf-color-brand, #5b5ce2) 6%, var(--gf-color-surface, #fff)); font-size: .78rem; font-weight: 750; }
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
    <p class="bom-bulk-copy">Tambahkan atau perbarui satu komponen ke BOM finished good berdasarkan kategori yang dipilih. Pilih semua untuk menerapkan perubahan ke seluruh BOM finished good. BOM yang sudah memiliki komponen tersebut akan diperbarui, sedangkan BOM lain akan ditambahkan tanpa membuat baris duplikat.</p>
    </section>

    <section class="bom-bulk-card">
        <form method="post" action="{{ route('master.item_boms.bulk_update') }}" data-bom-bulk-form>
            @csrf
            <div class="bom-bulk-grid">
                <div class="bom-bulk-field">
                    <label for="bulk-category">Kategori finished good target</label>
                    <select id="bulk-category" name="item_category_id">
                        <option value="" data-bom-count="{{ $allBomCount }}" @selected(blank(old('item_category_id')))>
                            Semua kategori finished good aktif ({{ $allBomCount }} BOM)
                        </option>
                        @foreach($itemCategories as $category)
                            <option value="{{ $category->id }}" data-bom-count="{{ $category->bom_count }}" @selected(old('item_category_id') == $category->id)>
                                {{ $category->code }} — {{ $category->name }} ({{ $category->bom_count }} BOM)
                            </option>
                        @endforeach
                    </select>
                    <small>Hanya kategori dari item bertipe finished good yang memiliki BOM aktif. Material, packaging, WIP, dan BOM inactive tidak termasuk target.</small>
                </div>

                <div class="bom-bulk-field">
                    <label for="bulk-stage">Kelompok komponen</label>
                    <select id="bulk-stage" name="usage_stage" required>
                        @foreach(\App\Models\ItemBomLine::usageStageLabels() as $value => $label)
                            <option value="{{ $value }}" @selected(old('usage_stage', \App\Models\ItemBomLine::STAGE_PACKING_SUPPLY) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small>Default diarahkan ke kelengkapan packing.</small>
                </div>

                <div class="bom-bulk-field" style="grid-column: 1 / -1">
                    <label for="bulk-material-search">Komponen yang diterapkan</label>
                    <input id="bulk-material-search" type="search" autocomplete="off" placeholder="Ketik kode atau nama komponen..." aria-describedby="bulk-material-help">
                    <input id="bulk-material-id" type="hidden" name="material_item_id" value="{{ old('material_item_id') }}">
                    <div id="bulk-material-selected" class="bom-bulk-selected" @if(!old('material_item_id')) hidden @endif>
                        <i class="bi bi-check-circle" aria-hidden="true"></i><span data-bulk-material-selected-text>{{ old('material_item_text', 'Komponen terpilih') }}</span>
                    </div>
                    <div id="bulk-material-results" class="bom-bulk-results" role="listbox" aria-label="Hasil pencarian komponen"></div>
                    <small id="bulk-material-help">Cari minimal 2 karakter. Hanya bahan persediaan/HPP yang dapat diterapkan ke BOM.</small>
                    @error('material_item_id')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                <div class="bom-bulk-field">
                    <label for="bulk-qty">Qty per pcs</label>
                    <input id="bulk-qty" type="number" name="qty" step="0.00000001" min="0.0001" value="{{ old('qty') }}" required>
                </div>
                <div class="bom-bulk-field">
                    <label for="bulk-uom">Satuan</label>
                    <input id="bulk-uom" type="text" name="uom" maxlength="20" value="{{ old('uom') }}" placeholder="Otomatis dari master" readonly aria-readonly="true">
                    <small>Satuan mengikuti Master Item pada komponen yang dipilih.</small>
                </div>
                <div class="bom-bulk-field">
                    <label for="bulk-scrap">Waste (%)</label>
                    <input id="bulk-scrap" type="number" name="scrap_pct" step="0.001" min="0" max="100" value="{{ old('scrap_pct', '0') }}" required>
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
    const category = document.querySelector('#bulk-category');
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

    const refreshImpact = () => {
        const option = category?.selectedOptions?.[0];
        const count = Number(option?.dataset.bomCount || 0);
        if (!option?.value) {
            impact.innerHTML = `<i class="bi bi-layers" aria-hidden="true"></i><span><strong>${count}</strong> BOM finished good aktif akan menerima perubahan ini.</span>`;
            return;
        }
        impact.innerHTML = `<i class="bi bi-layers" aria-hidden="true"></i><span><strong>${count}</strong> BOM finished good aktif dalam kategori ini akan menerima perubahan.</span>`;
    };

    const clearMaterial = () => {
        materialId.value = '';
        selected.hidden = true;
        results.replaceChildren();
    };

    const renderResults = (items) => {
        results.replaceChildren();
        items.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'bom-bulk-result';
            button.innerHTML = `<strong>${item.text.split(' — ')[0]}</strong><span>${item.text.split(' — ').slice(1).join(' — ') || ''}</span>`;
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

    category?.addEventListener('change', refreshImpact);
    form?.addEventListener('submit', (event) => {
        if (!materialId.value) {
            event.preventDefault();
            search.focus();
            alert('Pilih komponen terlebih dahulu.');
            return;
        }
        const option = category.selectedOptions[0];
        const count = Number(option?.dataset.bomCount || 0);
        const scope = option?.value ? 'kategori ini' : 'semua kategori finished good aktif';
        if (!window.confirm(`Terapkan perubahan ke ${count} BOM finished good pada ${scope}?`)) event.preventDefault();
    });
    refreshImpact();
});
</script>
@endpush
