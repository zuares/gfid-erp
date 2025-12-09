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
    'type' => null, // contoh: 'finished_good', 'material'
    'itemCategoryId' => null, // filter kategori item spesifik

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
     */
    'displayMode' => 'code-name',

    // toggle elemen di dropdown (desktop)
    'showName' => true,
    'showCategory' => true,

    /**
     * extraParams:
     * array param tambahan yang akan dikirim ke API suggest/index.
     * Contoh:
     *   ['lot_id' => $lotId]
     *   ['warehouse_id' => $warehouseId, 'type' => 'material']
     */
    'extraParams' => [],
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
    data-min-chars="{{ $minChars }}" data-autofocus="{{ $autofocus ? '1' : '0' }}"
    data-display-mode="{{ $displayMode }}" data-show-name="{{ $showName ? '1' : '0' }}"
    data-show-category="{{ $showCategory ? '1' : '0' }}" data-extra-params='@json($extraParams)'>

    <input type="text" value="{{ $displayValue }}" autocomplete="off"
        class="form-control form-control-sm js-item-suggest-input" placeholder="{{ $placeholder }}"
        data-items='@json($jsItems)' id="{{ $uid }}">

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
            /* Biar dropdown tidak kepotong dan bisa tampil di atas table */
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
                /* default di bawah input */
                background: var(--card, #fff);
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                max-height: 200px;
                overflow-y: auto;
                z-index: 5000;
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

            function positionDropdown(input, dropdown) {
                const rect = input.getBoundingClientRect();
                const viewportHeight = window.innerHeight;

                // default di bawah input
                dropdown.style.top = "calc(100% + 4px)";
                dropdown.style.bottom = "auto";

                const desired = Math.min(dropdown.scrollHeight, 200);
                const spaceBelow = viewportHeight - rect.bottom - 6;

                dropdown.style.maxHeight = Math.max(80, Math.min(desired, spaceBelow)) + "px";
            }

            function isMobileViewport() {
                // breakpoint sederhana: max-width 768px
                return window.matchMedia("(max-width: 768px)").matches;
            }

            function setupItemSuggest(wrap) {
                const input = wrap.querySelector('.js-item-suggest-input');
                const hiddenId = wrap.querySelector('.js-item-suggest-id');
                const hiddenCat = wrap.querySelector('.js-item-suggest-category');
                const dropdown = wrap.querySelector('.item-suggest-dropdown');

                dropdown.classList.add('list-group');

                const minChars = parseInt(wrap.dataset.minChars || "1", 10);
                const displayMode = wrap.dataset.displayMode;
                const showName = wrap.dataset.showName === "1";
                const showCategory = wrap.dataset.showCategory === "1";

                // baca filter dari data-attribute
                const type = wrap.dataset.type || null;
                const itemCategoryId = wrap.dataset.itemCategoryId || null;

                // extra params (lot_id, warehouse_id, dll)
                let extraParams = {};
                try {
                    extraParams = JSON.parse(wrap.dataset.extraParams || '{}') || {};
                } catch (e) {
                    extraParams = {};
                }

                // ✅ Fallback: items yang sudah dikirim dari server (Purchase, dll)
                let initialItems = [];
                try {
                    const raw = input.getAttribute('data-items') || '[]';
                    initialItems = JSON.parse(raw);
                } catch (e) {
                    initialItems = [];
                }

                let timer = null;
                let lastItems = [];
                let activeIndex = -1;

                function isDropdownVisible() {
                    return dropdown.style.display !== "none";
                }

                function show() {
                    dropdown.style.display = "block";
                    positionDropdown(input, dropdown);
                }

                function hide() {
                    dropdown.style.display = "none";
                    activeIndex = -1;
                    updateActiveClass();
                }

                function updateActiveClass() {
                    const options = dropdown.querySelectorAll('.item-suggest-option');
                    options.forEach((opt, i) => {
                        opt.classList.toggle('is-active', i === activeIndex);
                    });

                    if (activeIndex >= 0 && activeIndex < options.length) {
                        options[activeIndex].scrollIntoView({
                            block: 'nearest'
                        });
                    }
                }

                function moveActive(delta) {
                    const options = dropdown.querySelectorAll('.item-suggest-option');
                    if (!options.length) return;

                    if (activeIndex === -1) {
                        activeIndex = delta > 0 ? 0 : options.length - 1;
                    } else {
                        activeIndex += delta;
                        if (activeIndex < 0) activeIndex = options.length - 1;
                        if (activeIndex >= options.length) activeIndex = 0;
                    }

                    updateActiveClass();
                }

                function buildDropdown(items) {
                    dropdown.innerHTML = "";

                    if (!items.length) {
                        dropdown.innerHTML = `<div class='p-2 text-muted'>Tidak ada hasil</div>`;
                        show();
                        return;
                    }

                    // 🔢 LIMIT MAKSIMAL 4 ITEM DI DROPDOWN
                    items = items.slice(0, 4);
                    lastItems = items;
                    activeIndex = -1;

                    const mobile = isMobileViewport();

                    items.forEach((item) => {
                        const btn = document.createElement("button");
                        btn.type = "button";
                        btn.className = "item-suggest-option list-group-item list-group-item-action";

                        let html = `<div class='item-suggest-option-code'>${item.code}</div>`;

                        // 🔹 Desktop: boleh tampil nama + kategori
                        // 🔹 Mobile: HANYA kode barang saja (tanpa nama & kategori)
                        if (!mobile) {
                            const sub = [];
                            if (showName && item.name) sub.push(item.name);
                            if (showCategory && (item.item_category_name || item.item_category)) {
                                sub.push(item.item_category_name || item.item_category);
                            }

                            if (sub.length) {
                                html += `<div class='item-suggest-option-name'>${sub.join(" • ")}</div>`;
                            }
                        }

                        btn.innerHTML = html;
                        btn.addEventListener("click", () => selectItem(item));

                        dropdown.appendChild(btn);
                    });

                    updateActiveClass();
                    show();
                }

                function selectItem(item) {
                    const mobile = isMobileViewport();
                    let text;

                    // 📱 MOBILE: paksa selalu hanya kode
                    if (mobile) {
                        text = item.code;
                    } else {
                        // 💻 DESKTOP: ikut displayMode (code / code-name)
                        text = item.code;
                        if (displayMode === "code-name" && item.name) {
                            text += " — " + item.name;
                        }
                    }

                    input.value = text;
                    hiddenId.value = item.id;
                    if (hiddenCat) hiddenCat.value = item.item_category_id;

                    hide();

                    // fokus ke input berikutnya (kalau perlu)
                    const next = wrap.closest("tr")?.querySelector(".bundle-qty, .line-qty, .js-next-focus");
                    if (next) {
                        next.focus();
                        if (next.select) next.select();
                    }
                }

                function selectActiveOrFirst() {
                    if (!lastItems.length) return;

                    let idx = activeIndex;
                    if (idx < 0 || idx >= lastItems.length) {
                        idx = 0;
                    }
                    const item = lastItems[idx];
                    if (item) {
                        selectItem(item);
                    }
                }

                function fetchData(q, force) {
                    q = q || '';

                    // ✅ Kalau belum cukup karakter & ada initialItems dari server → pakai itu
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

                    // 🔁 inject extraParams (lot_id, warehouse_id, dll)
                    if (extraParams && typeof extraParams === 'object') {
                        Object.keys(extraParams).forEach((key) => {
                            const value = extraParams[key];
                            if (value !== null && value !== undefined && value !== '') {
                                params.set(key, value);
                            }
                        });
                    }

                    const url = `/api/v1/items/suggest?` + params.toString();

                    fetch(url)
                        .then(r => r.json())
                        .then(json => {
                            const data = json.data || [];

                            // kalau API kosong tapi ada initialItems → fallback
                            if (!data.length && initialItems.length) {
                                buildDropdown(initialItems);
                            } else {
                                buildDropdown(data);
                            }
                        })
                        .catch(() => {
                            // kalau error, fallback ke initialItems kalau ada
                            if (initialItems.length) {
                                buildDropdown(initialItems);
                            } else {
                                dropdown.innerHTML = `<div class='p-2 text-danger'>Gagal memuat</div>`;
                                show();
                            }
                        });
                }

                input.addEventListener("input", () => {
                    const q = input.value.trim();
                    clearTimeout(timer);
                    timer = setTimeout(() => fetchData(q, false), 200);
                });

                input.addEventListener("focus", () => {
                    input.select();

                    // saat focus: kalau ada initialItems & belum ketik apa-apa → pakai initialItems
                    if (initialItems.length && input.value.trim() === '') {
                        buildDropdown(initialItems);
                    } else {
                        fetchData(input.value.trim(), true);
                    }
                });

                // 🔥 Keyboard navigation: Arrow Up/Down + Enter + Tab + ESC
                input.addEventListener("keydown", (e) => {
                    const key = e.key;

                    if (key === "ArrowDown") {
                        e.preventDefault();

                        if (!isDropdownVisible()) {
                            fetchData(input.value.trim(), true);
                        } else {
                            moveActive(1);
                        }
                    } else if (key === "ArrowUp") {
                        e.preventDefault();

                        if (!isDropdownVisible()) {
                            fetchData(input.value.trim(), true);
                        } else {
                            moveActive(-1);
                        }
                    } else if (key === "Enter") {
                        if (isDropdownVisible()) {
                            e.preventDefault();
                            selectActiveOrFirst();
                        }
                    } else if (key === "Tab") {
                        if (isDropdownVisible()) {
                            e.preventDefault();
                            selectActiveOrFirst();
                        }
                    } else if (key === "Escape") {
                        hide();
                    }
                });

                // kalau butuh autofocus
                if (wrap.dataset.autofocus === "1") {
                    setTimeout(() => {
                        input.focus();
                        input.select();

                        if (initialItems.length) {
                            buildDropdown(initialItems);
                        } else {
                            fetchData("", true);
                        }
                    }, 150);
                }

                // klik di luar → tutup dropdown
                document.addEventListener("click", (e) => {
                    if (!wrap.contains(e.target)) {
                        hide();
                    }
                });

                window.addEventListener('resize', () => {
                    if (isDropdownVisible()) {
                        positionDropdown(input, dropdown);
                    }
                });
            }
        </script>
    @endpush
@endonce
