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

    'placeholder' => 'Kode / nama barang',

    // filter API
    'type' => null,
    'itemCategoryId' => null,

    // minimal karakter sebelum fetch
    'minChars' => 1,

    // autofocus
    'autofocus' => false,

    // dropdown visible rows
    'maxResults' => 4,

    // UI variant
    'variant' => 'default', // default | mini

    // input display mode
    'displayMode' => 'code-name', // code-name | code

    // dropdown toggles (desktop)
    'showName' => true,
    'showCategory' => true,

    // extra params for API
    'extraParams' => [],

    /**
     * ✅ DEFAULT: false (AMAN untuk block "Tambah item")
     * Kalau benar-benar wajib sebelum submit form → set :required="true"
     */
    'required' => false,

    /**
     * ✅ DEFAULT: true (komponen tidak ikut validasi submit form utama)
     * Kalau komponen bagian form utama (wajib dipilih) → set :skipSubmitValidation="false"
     */
    'skipSubmitValidation' => true,
])

@php
    use Illuminate\Support\Str;

    $uid = 'item-suggest-' . Str::random(6);

    $jsItems = $items
        ->map(
            fn($it) => [
                'id' => $it->id,
                'code' => $it->code,
                'name' => $it->name,
                'item_category_id' => $it->item_category_id,
                'item_category_name' => optional($it->category)->name,
            ],
        )
        ->values();

    if ($variant === 'mini') {
        $displayMode = 'code';
        $showName = false;
        $showCategory = false;
    }
@endphp

<div class="item-suggest-wrap" data-type="{{ $type }}" data-item-category-id="{{ $itemCategoryId }}"
    data-min-chars="{{ (int) $minChars }}" data-max-results="{{ (int) $maxResults }}"
    data-autofocus="{{ $autofocus ? '1' : '0' }}" data-display-mode="{{ $displayMode }}"
    data-show-name="{{ $showName ? '1' : '0' }}" data-show-category="{{ $showCategory ? '1' : '0' }}"
    data-extra-params='@json($extraParams)' data-required="{{ $required ? '1' : '0' }}"
    data-skip-submit="{{ $skipSubmitValidation ? '1' : '0' }}">
    <input type="text" value="{{ strtoupper($displayValue) }}" autocomplete="off"
        class="form-control form-control-sm js-item-suggest-input" placeholder="{{ $placeholder }}"
        data-items='@json($jsItems)' id="{{ $uid }}" {{-- ❌ JANGAN pakai required HTML5 di text input untuk komponen ini --}}
        aria-autocomplete="list">

    <input type="hidden" name="{{ $idName }}" value="{{ $idValue }}" class="js-item-suggest-id">

    @if ($categoryName)
        <input type="hidden" name="{{ $categoryName }}" value="{{ $categoryValue }}"
            class="js-item-suggest-category">
    @endif

    <div class="item-suggest-dropdown shadow-sm" style="display:none;"></div>
</div>

@once
    @push('head')
        <style>
            .table-responsive,
            table td,
            table th,
            .item-suggest-wrap {
                overflow: visible !important;
                position: relative;
            }

            .item-suggest-dropdown {
                position: absolute;
                left: 0;
                right: 0;
                top: calc(100% + 4px);
                background: var(--card, #fff);
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                max-height: 240px;
                overflow-y: auto;
                z-index: 1000;
            }

            .item-suggest-option {
                padding: .4rem .6rem;
                cursor: pointer;
            }

            .item-suggest-option:hover,
            .item-suggest-option.is-active {
                background: rgba(59, 130, 246, 0.12);
            }

            .item-suggest-option-code {
                font-weight: 600;
            }

            .item-suggest-option-name {
                font-size: .78rem;
                color: #6b7280;
            }

            .js-item-suggest-input.is-invalid {
                border-color: #dc3545;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                window.initItemSuggestInputs = function(scope = document) {
                    scope.querySelectorAll('.item-suggest-wrap:not([data-suggest-inited])').forEach(wrap => {
                        setupItemSuggest(wrap);
                        wrap.dataset.suggestInited = "1";
                    });
                };
                window.initItemSuggestInputs();
            });

            function isMobileViewport() {
                return window.matchMedia("(max-width: 768px)").matches;
            }

            function positionDropdown(input, dropdown, maxVisibleRows = 4) {
                const rect = input.getBoundingClientRect();
                const viewportHeight = window.innerHeight;

                dropdown.style.top = "calc(100% + 4px)";
                dropdown.style.bottom = "auto";

                const optionEl = dropdown.querySelector('.item-suggest-option');
                const optionH = optionEl ? optionEl.getBoundingClientRect().height : 40;

                const desiredByRows = optionH * Math.max(1, maxVisibleRows);
                const desired = Math.max(80, Math.min(desiredByRows, 240));
                const spaceBelow = viewportHeight - rect.bottom - 6;

                dropdown.style.maxHeight = Math.max(80, Math.min(desired, spaceBelow)) + "px";
            }

            function setupItemSuggest(wrap) {
                const input = wrap.querySelector('.js-item-suggest-input');
                const hiddenId = wrap.querySelector('.js-item-suggest-id');
                const hiddenCat = wrap.querySelector('.js-item-suggest-category');
                const dropdown = wrap.querySelector('.item-suggest-dropdown');

                dropdown.classList.add('list-group');

                const minChars = parseInt(wrap.dataset.minChars || "1", 10);
                const maxResults = parseInt(wrap.dataset.maxResults || "4", 10);

                const displayMode = wrap.dataset.displayMode || 'code-name';
                const showName = wrap.dataset.showName === "1";
                const showCategory = wrap.dataset.showCategory === "1";

                const required = wrap.dataset.required === "1";
                const skipSubmit = wrap.dataset.skipSubmit === "1";

                const type = wrap.dataset.type || null;
                const itemCategoryId = wrap.dataset.itemCategoryId || null;

                let extraParams = {};
                try {
                    extraParams = JSON.parse(wrap.dataset.extraParams || '{}') || {};
                } catch (e) {
                    extraParams = {};
                }

                let initialItems = [];
                try {
                    initialItems = JSON.parse(input.getAttribute('data-items') || '[]');
                } catch (e) {
                    initialItems = [];
                }

                if (input.value) input.value = input.value.toUpperCase();

                let timer = null;
                let lastItems = [];
                let activeIndex = -1;
                let isSelecting = false;

                function isDisabled() {
                    return (input && input.disabled) || (hiddenId && hiddenId.disabled);
                }

                function isDropdownVisible() {
                    return dropdown.style.display !== "none";
                }

                function show() {
                    if (isDisabled()) return;
                    dropdown.style.display = "block";
                    positionDropdown(input, dropdown, maxResults);
                }

                function hide() {
                    dropdown.style.display = "none";
                    activeIndex = -1;
                    updateActiveClass();
                }

                function updateActiveClass() {
                    const options = dropdown.querySelectorAll('.item-suggest-option');
                    options.forEach((opt, i) => opt.classList.toggle('is-active', i === activeIndex));
                    if (activeIndex >= 0 && activeIndex < options.length) {
                        options[activeIndex].scrollIntoView({
                            block: 'nearest'
                        });
                    }
                }

                function moveActive(delta) {
                    const options = dropdown.querySelectorAll('.item-suggest-option');
                    if (!options.length) return;

                    if (activeIndex === -1) activeIndex = delta > 0 ? 0 : options.length - 1;
                    else {
                        activeIndex += delta;
                        if (activeIndex < 0) activeIndex = options.length - 1;
                        if (activeIndex >= options.length) activeIndex = 0;
                    }
                    updateActiveClass();
                }

                function buildDropdown(items) {
                    if (isDisabled()) return;

                    dropdown.innerHTML = "";

                    if (!items.length) {
                        dropdown.innerHTML = `<div class='p-2 text-muted'>Tidak ada hasil</div>`;
                        lastItems = [];
                        activeIndex = -1;
                        show();
                        return;
                    }

                    lastItems = items;
                    activeIndex = -1;

                    const mobile = isMobileViewport();

                    items.forEach((item) => {
                        const btn = document.createElement("button");
                        btn.type = "button";
                        btn.className = "item-suggest-option list-group-item list-group-item-action";

                        let html = `<div class='item-suggest-option-code'>${(item.code || '').toUpperCase()}</div>`;

                        if (!mobile) {
                            const sub = [];
                            if (showName && item.name) sub.push(item.name);
                            if (showCategory && (item.item_category_name || item.item_category)) {
                                sub.push(item.item_category_name || item.item_category);
                            }
                            if (sub.length) html += `<div class='item-suggest-option-name'>${sub.join(" • ")}</div>`;
                        }

                        btn.innerHTML = html;
                        btn.addEventListener("click", () => selectItem(item));
                        dropdown.appendChild(btn);
                    });

                    updateActiveClass();
                    show();
                }

                function selectItem(item) {
                    if (isDisabled()) return;

                    const mobile = isMobileViewport();
                    let text = item.code || '';

                    if (!mobile && displayMode === "code-name" && item.name) text += " — " + item.name;
                    text = (text || '').toUpperCase();

                    isSelecting = true;

                    input.value = text;
                    hiddenId.value = item.id || '';
                    if (hiddenCat) hiddenCat.value = item.item_category_id || '';

                    input.classList.remove('is-invalid');

                    hiddenId.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                    setTimeout(() => {
                        isSelecting = false;
                    }, 0);

                    hide();
                }

                function selectActiveOrFirst() {
                    if (isDisabled()) return;
                    if (!lastItems.length) return;

                    let idx = activeIndex;
                    if (idx < 0 || idx >= lastItems.length) idx = 0;
                    if (lastItems[idx]) selectItem(lastItems[idx]);
                }

                function fetchData(q, force) {
                    if (isDisabled()) return;
                    q = (q || '').trim();

                    if (!force && q.length < minChars && initialItems.length) {
                        buildDropdown(initialItems);
                        return;
                    }
                    if (!force && q.length < minChars && !initialItems.length) {
                        hide();
                        return;
                    }

                    dropdown.innerHTML = `<div class='p-2 text-muted'>Memuat...</div>`;
                    show();

                    const params = new URLSearchParams();
                    params.set('q', q);

                    if (type) params.set('type', type);
                    if (itemCategoryId) params.set('item_category_id', itemCategoryId);

                    if (extraParams && typeof extraParams === 'object') {
                        Object.keys(extraParams).forEach((key) => {
                            const value = extraParams[key];
                            if (value !== null && value !== undefined && value !== '') params.set(key, value);
                        });
                    }

                    fetch(`/api/v1/items/suggest?${params.toString()}`)
                        .then(r => r.json())
                        .then(json => {
                            const data = json?.data || [];
                            if (!data.length && initialItems.length) buildDropdown(initialItems);
                            else buildDropdown(data);
                        })
                        .catch(() => {
                            if (initialItems.length) buildDropdown(initialItems);
                            else {
                                dropdown.innerHTML = `<div class='p-2 text-danger'>Gagal memuat</div>`;
                                show();
                            }
                        });
                }

                input.addEventListener("input", () => {
                    if (isDisabled()) return;

                    const start = input.selectionStart;
                    const end = input.selectionEnd;

                    const upper = (input.value || '').toUpperCase();
                    if (upper !== input.value) {
                        input.value = upper;
                        if (start !== null && end !== null) input.setSelectionRange(start, end);
                    }

                    if (!isSelecting) {
                        hiddenId.value = "";
                        if (hiddenCat) hiddenCat.value = "";
                        hiddenId.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));

                        if (required) input.classList.add('is-invalid');
                        else input.classList.remove('is-invalid');
                    }

                    clearTimeout(timer);
                    timer = setTimeout(() => fetchData(input.value, false), 200);
                });

                input.addEventListener("focus", () => {
                    if (isDisabled()) return;

                    input.value = (input.value || '').toUpperCase();
                    input.select && input.select();

                    if (initialItems.length && input.value.trim() === '') buildDropdown(initialItems);
                    else fetchData(input.value, true);
                });

                input.addEventListener("keydown", (e) => {
                    if (isDisabled()) return;

                    const key = e.key;

                    if (key === "ArrowDown") {
                        e.preventDefault();
                        if (!isDropdownVisible()) fetchData(input.value, true);
                        else moveActive(1);
                    } else if (key === "ArrowUp") {
                        e.preventDefault();
                        if (!isDropdownVisible()) fetchData(input.value, true);
                        else moveActive(-1);
                    } else if (key === "Enter" || key === "Tab") {
                        if (isDropdownVisible()) {
                            e.preventDefault();
                            selectActiveOrFirst();
                        }
                    } else if (key === "Escape") {
                        hide();
                    }
                });

                input.addEventListener("blur", () => {
                    if (isDisabled()) return;
                    if (!required) return;

                    if (!hiddenId.value) input.classList.add('is-invalid');
                    else input.classList.remove('is-invalid');
                });

                if (wrap.dataset.autofocus === "1") {
                    setTimeout(() => {
                        if (isDisabled()) return;
                        input.focus();
                        input.select && input.select();

                        if (initialItems.length) buildDropdown(initialItems);
                        else fetchData("", true);
                    }, 150);
                }

                document.addEventListener("click", (e) => {
                    if (!wrap.contains(e.target)) hide();
                });
                window.addEventListener('resize', () => {
                    if (isDropdownVisible()) positionDropdown(input, dropdown, maxResults);
                });

                /**
                 * ✅ Validasi submit form:
                 * - hanya untuk required=1
                 * - dan skip-submit=0
                 * - dan tidak disabled
                 */
                if (required && !skipSubmit) {
                    const form = wrap.closest('form');
                    if (form && !form.dataset.itemSuggestRequiredBound) {
                        form.addEventListener('submit', function(e) {
                            let firstInvalid = null;

                            form.querySelectorAll('.item-suggest-wrap[data-required="1"][data-skip-submit="0"]')
                                .forEach(w => {
                                    const hid = w.querySelector('.js-item-suggest-id');
                                    const inp = w.querySelector('.js-item-suggest-input');
                                    if (!hid || !inp) return;
                                    if (inp.disabled || hid.disabled) return;

                                    if (!hid.value) {
                                        inp.classList.add('is-invalid');
                                        if (!firstInvalid) firstInvalid = inp;
                                    }
                                });

                            if (firstInvalid) {
                                e.preventDefault();
                                firstInvalid.focus();
                                firstInvalid.select && firstInvalid.select();
                            }
                        });

                        form.dataset.itemSuggestRequiredBound = "1";
                    }
                }
            }
        </script>
    @endpush
@endonce
