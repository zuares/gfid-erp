@props([
    // hidden: item_id yang dikirim ke server (wajib)
    'idName',

    // hidden: item_category_id yang dikirim ke server (opsional)
    'categoryName' => null,

    // optional fallback items dari server
    'items' => collect(),

    // nilai awal (untuk mode edit)
    'displayValue' => '',
    'idValue' => '',
    'categoryValue' => '',

    'placeholder' => 'Kode / nama item',

    // filter API
    'type' => null,
    'itemCategoryId' => null,

    // minimal karakter sebelum fetch
    'minChars' => 1,

    // jika true → auto focus + auto buka suggest saat page load
    'autofocus' => false,

    /**
     * VARIAN UI
     * - default  : kode + nama (+ kategori)
     * - mini     : hanya kode (tanpa nama & kategori)
     */
    'variant' => 'default',

    /**
     * Mode tampilan teks di input bagi user:
     * - code-name  : "CODE — Nama item"
     * - code       : "CODE" saja
     *
     * Kalau variant="mini", otomatis pakai "code".
     */
    'displayMode' => 'code-name',

    // Flag granular kalau mau override manual:
    'showName' => true,
    'showCategory' => true,
])

@php
    use Illuminate\Support\Str;

    $uid = 'item-suggest-' . Str::random(6);

    // fallback data untuk JS (kalau API lambat / fail)
    $jsItems = $items
        ->map(
            fn($it) => [
                'id' => $it->id,
                'code' => $it->code,
                'name' => $it->name,
                'item_category_id' => $it->item_category_id ?? null,
                'item_category_name' => $it->item_category_name ?? (optional($it->category)->name ?? null),
            ],
        )
        ->values();

    $effectiveType = $type ?? '';

    // 🌟 Aturan varian mini:
    // - displayMode paksa 'code'
    // - nama & kategori tidak ditampilkan
    if ($variant === 'mini') {
        $displayMode = 'code';
        $showName = false;
        $showCategory = false;
    }

    // normalize boolean → '1' / '0' untuk data-attr JS
    $showNameFlag = $showName ? '1' : '0';
    $showCategoryFlag = $showCategory ? '1' : '0';
@endphp

<div class="item-suggest-wrap" data-uid="{{ $uid }}" data-type="{{ $effectiveType }}"
    data-item-category-id="{{ $itemCategoryId }}" data-min-chars="{{ $minChars }}"
    data-autofocus="{{ $autofocus ? '1' : '0' }}" data-display-mode="{{ $displayMode }}"
    data-show-name="{{ $showNameFlag }}" data-show-category="{{ $showCategoryFlag }}">
    {{-- INPUT DISPLAY (TIDAK DIKIRIM KE SERVER → tidak ada name) --}}
    <input type="text" id="{{ $uid }}" value="{{ $displayValue }}" autocomplete="off"
        class="form-control form-control-sm js-item-suggest-input" placeholder="{{ $placeholder }}"
        data-items='@json($jsItems)' />

    {{-- HIDDEN: item_id --}}
    <input type="hidden" name="{{ $idName }}" value="{{ $idValue }}" class="js-item-suggest-id">

    {{-- HIDDEN: item_category_id (opsional) --}}
    @if ($categoryName)
        <input type="hidden" name="{{ $categoryName }}" value="{{ $categoryValue }}"
            class="js-item-suggest-category">
    @endif

    <div class="item-suggest-dropdown shadow-sm" style="display:none;"></div>
</div>

@once
    @push('head')
        <style>
            .item-suggest-wrap {
                position: relative;
                width: 100%;
            }

            .item-suggest-dropdown {
                background: var(--card, #fff);
                border: 1px solid var(--line, #e5e7eb);
                border-radius: 6px;
                max-height: 220px;
                overflow-y: auto;
                z-index: 99999;
                box-sizing: border-box;
            }

            .item-suggest-option {
                padding: .35rem .6rem;
                width: 100%;
                border: 0;
                background: transparent;
                text-align: left;
                cursor: pointer;
                font-size: .84rem;
            }

            .item-suggest-option:hover,
            .item-suggest-option.is-active {
                background: color-mix(in srgb, var(--primary, #3b82f6) 10%, transparent 90%);
            }

            .item-suggest-option-code {
                font-weight: 600;
                font-variant-numeric: tabular-nums;
            }

            [data-theme="dark"] .item-suggest-option-code {
                color: #ffffff;
            }

            .item-suggest-option-name {
                font-size: .78rem;
                color: var(--muted, #6b7280);
            }

            .item-suggest-empty,
            .item-suggest-loading,
            .item-suggest-error {
                padding: .35rem .6rem;
                font-size: .78rem;
                color: var(--muted, #6b7280);
            }

            .item-suggest-error {
                color: #b91c1c;
            }

            @media (max-width: 576px) {
                .item-suggest-dropdown {
                    border-radius: 8px;
                    max-height: 50vh;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // bisa dipanggil ulang setelah Add Row
                window.initItemSuggestInputs = function(root) {
                    const scope = root || document;
                    const wraps = scope.querySelectorAll('.item-suggest-wrap:not([data-suggest-inited="1"])');
                    wraps.forEach(wrap => {
                        setupItemSuggest(wrap);
                        wrap.dataset.suggestInited = '1';
                    });
                };

                window.initItemSuggestInputs();

                // klik di luar → tutup semua dropdown
                document.addEventListener('click', function(e) {
                    document
                        .querySelectorAll('.item-suggest-wrap[data-suggest-inited="1"]')
                        .forEach(wrap => {
                            const dd = wrap.querySelector('.item-suggest-dropdown');
                            if (dd && !wrap.contains(e.target)) {
                                dd.style.display = 'none';
                            }
                        });
                });

                window.addEventListener('scroll', handleGlobalReposition, true);
                window.addEventListener('resize', handleGlobalReposition);
            });

            function handleGlobalReposition() {
                document
                    .querySelectorAll('.item-suggest-wrap[data-suggest-inited="1"]')
                    .forEach(wrap => {
                        const dropdown = wrap.querySelector('.item-suggest-dropdown');
                        const input = wrap.querySelector('.js-item-suggest-input');
                        if (!dropdown || dropdown.style.display === 'none') return;
                        positionDropdownFixed(input, dropdown);
                    });
            }

            function positionDropdownFixed(inputEl, dropdownEl) {
                const rect = inputEl.getBoundingClientRect();
                dropdownEl.style.position = 'fixed';
                dropdownEl.style.left = rect.left + 'px';
                dropdownEl.style.top = (rect.bottom + 4) + 'px';
                dropdownEl.style.width = rect.width + 'px';
            }

            function setupItemSuggest(wrap) {
                const input = wrap.querySelector('.js-item-suggest-input');
                const hiddenId = wrap.querySelector('.js-item-suggest-id');
                const hiddenCategory = wrap.querySelector('.js-item-suggest-category');
                const dropdown = wrap.querySelector('.item-suggest-dropdown');

                dropdown.classList.add('list-group');

                const type = wrap.dataset.type || '';
                const itemCategoryId = wrap.dataset.itemCategoryId || '';
                const minChars = parseInt(wrap.dataset.minChars || '1', 10);
                const shouldAutofocus = wrap.dataset.autofocus === '1';

                const displayMode = wrap.dataset.displayMode || 'code-name';
                const showName = wrap.dataset.showName === '1';
                const showCategory = wrap.dataset.showCategory === '1';

                let timer = null;
                let activeIndex = -1;
                let lastItems = [];

                let localItems = [];
                try {
                    localItems = JSON.parse(input.dataset.items || '[]');
                } catch (e) {
                    localItems = [];
                }

                const hideDropdown = () => {
                    dropdown.style.display = 'none';
                    activeIndex = -1;
                };

                const showDropdown = () => {
                    positionDropdownFixed(input, dropdown);
                    dropdown.style.display = 'block';
                };

                const setLoading = () => {
                    dropdown.innerHTML = '<div class="item-suggest-loading">Memuat...</div>';
                    showDropdown();
                };

                const setEmpty = () => {
                    dropdown.innerHTML = '<div class="item-suggest-empty">Tidak ada hasil</div>';
                    showDropdown();
                };

                const setError = () => {
                    dropdown.innerHTML = '<div class="item-suggest-error">Gagal memuat data</div>';
                    showDropdown();
                };

                // force = true → abaikan minChars (untuk fokus/klik pertama)
                function performFetch(q, force = false) {
                    if (!force && q.length < minChars) {
                        hideDropdown();
                        return;
                    }

                    let url = `/api/v1/items/suggest?q=${encodeURIComponent(q)}`;

                    if (type) {
                        url += `&type=${encodeURIComponent(type)}`;
                    }

                    if (itemCategoryId) {
                        url += `&item_category_id=${encodeURIComponent(itemCategoryId)}`;
                    }

                    setLoading();

                    fetch(url)
                        .then(res => res.json())
                        .then(json => {
                            const items = json?.data ?? [];
                            if (!items.length) return setEmpty();
                            buildDropdown(items);
                        })
                        .catch(() => setError());
                }

                function highlightActive() {
                    const options = dropdown.querySelectorAll('.item-suggest-option');
                    options.forEach((opt, idx) =>
                        opt.classList.toggle('is-active', idx === activeIndex)
                    );
                }

                function moveActive(delta) {
                    const options = dropdown.querySelectorAll('.item-suggest-option');
                    if (!options.length) return;

                    activeIndex += delta;
                    if (activeIndex < 0) activeIndex = options.length - 1;
                    if (activeIndex >= options.length) activeIndex = 0;

                    highlightActive();
                }

                function selectActive() {
                    const options = dropdown.querySelectorAll('.item-suggest-option');
                    if (activeIndex >= 0 && options[activeIndex]) {
                        options[activeIndex].click();
                    }
                }

                function selectItem(item) {
                    const displayName = item.name ?? item.item_name ?? '';
                    const categoryId = item.item_category_id ?? '';
                    const categoryName = item.item_category_name ??
                        item.item_category ??
                        '';

                    // display di input (tidak dikirim ke server)
                    let text = '';
                    if (displayMode === 'code') {
                        text = item.code || '';
                    } else {
                        text = item.code || '';
                        if (displayName) {
                            text += ' — ' + displayName;
                        }
                    }
                    input.value = text;

                    // hidden id
                    if (hiddenId) {
                        hiddenId.value = item.id;
                    }

                    // hidden category id
                    if (hiddenCategory) {
                        hiddenCategory.value = categoryId;
                    }

                    // update label kategori di kolom Item Category (kalau ada)
                    const row = wrap.closest('tr');
                    if (row) {
                        const catLabel = row.querySelector('.bundle-item-category');
                        if (catLabel) {
                            const labelText =
                                showCategory ?
                                (categoryName || (categoryId ? '#' + categoryId : '-')) :
                                catLabel.textContent; // biarkan kalau mini
                            catLabel.textContent = labelText;
                        }
                    }

                    hideDropdown();

                    // 🔥 pindah fokus ke Qty (pcs) di baris yang sama
                    const rowEl = wrap.closest('tr');
                    if (rowEl) {
                        const nextFocusable = rowEl.querySelector('.bundle-qty, .line-qty');
                        if (nextFocusable) {
                            setTimeout(() => {
                                nextFocusable.focus();
                                nextFocusable.select?.();
                            }, 0);
                        }
                    }
                }

                function buildDropdown(items) {
                    dropdown.innerHTML = '';

                    if (!items.length) {
                        lastItems = [];
                        setEmpty();
                        return;
                    }

                    lastItems = items;

                    items.forEach(item => {
                        const displayName = item.name ?? item.item_name ?? '';
                        const categoryName = item.item_category_name ??
                            item.item_category ??
                            '';
                        const categoryId = item.item_category_id ?? '';

                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'item-suggest-option list-group-item list-group-item-action';

                        let secondaryLine = '';

                        if (showName || showCategory) {
                            const parts = [];
                            if (showName && displayName) {
                                parts.push(displayName);
                            }
                            if (showCategory && (categoryName || categoryId)) {
                                parts.push(
                                    `<span class="text-muted">${
                                        categoryName || ('#' + categoryId)
                                    }</span>`
                                );
                            }

                            if (parts.length > 0) {
                                secondaryLine = `
                                    <div class="item-suggest-option-name">
                                        ${parts.join(' • ')}
                                    </div>
                                `;
                            }
                        }

                        btn.innerHTML = `
                            <div class="item-suggest-option-code">${item.code}</div>
                            ${secondaryLine}
                        `;

                        btn.addEventListener('click', () => selectItem(item));
                        dropdown.appendChild(btn);
                    });

                    activeIndex = -1;
                    showDropdown();
                }

                // input → debounce + minChars
                input.addEventListener('input', function() {
                    const q = this.value.trim();

                    if (q.length === 0) {
                        if (hiddenId) hiddenId.value = '';
                        if (hiddenCategory) hiddenCategory.value = '';
                        hideDropdown();
                        return;
                    }

                    if (timer) clearTimeout(timer);

                    timer = setTimeout(() => {
                        performFetch(q, false);
                    }, 250);
                });

                // fokus → langsung fetch semua (force = true)
                input.addEventListener('focus', function() {
                    input.select();
                    const q = input.value.trim();
                    performFetch(q, true);
                });

                // keyboard navigation
                input.addEventListener('keydown', function(e) {
                    const isOpen = dropdown.style.display !== 'none';

                    // TAB → pilih item aktif/pertama + pindah ke Qty
                    if (e.key === 'Tab') {
                        if (isOpen && lastItems.length > 0) {
                            e.preventDefault();
                            const indexToPick = activeIndex >= 0 ? activeIndex : 0;
                            const item = lastItems[indexToPick];
                            if (item) selectItem(item);
                        }
                        return;
                    }

                    if (!isOpen) return;

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        moveActive(1);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        moveActive(-1);
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        selectActive(); // Enter = pilih item + pindah ke Qty
                    } else if (e.key === 'Escape') {
                        hideDropdown();
                    }
                });

                // autofocus: fokus + langsung buka suggest
                if (shouldAutofocus) {
                    setTimeout(() => {
                        input.focus();
                        input.select();
                        const q = input.value.trim();
                        performFetch(q, true);
                    }, 0);
                }
            }
        </script>
    @endpush
@endonce
