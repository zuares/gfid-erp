@props([
    'target' => '',
    'placeholder' => 'Cari…',
    'label' => 'Pencarian',
    'itemLabel' => 'hasil',
    'countId' => null,
    'emptyId' => null,
])

@php
    $inputId = $attributes->get('id') ?: 'ui-search-' . uniqid();
    $countId = $countId ?: $inputId . '-count';
    $emptyId = $emptyId ?: $inputId . '-empty';
@endphp

<div {{ $attributes->merge(['class' => 'ui-search']) }}
     data-ui-search
     data-ui-search-target="{{ $target }}"
     data-ui-search-count-id="{{ $countId }}"
     data-ui-search-empty-id="{{ $emptyId }}"
     data-ui-search-item-label="{{ $itemLabel }}">
    <label class="visually-hidden" for="{{ $inputId }}">{{ $label }}</label>
    <i class="bi bi-search ui-search-icon" aria-hidden="true"></i>
    <input type="search"
           id="{{ $inputId }}"
           class="ui-search-input"
           placeholder="{{ $placeholder }}"
           autocomplete="off"
           spellcheck="false"
           aria-describedby="{{ $countId }}">
    <button type="button" class="ui-search-clear" aria-label="Hapus pencarian" hidden>
        <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
    <div class="ui-search-meta" id="{{ $countId }}" aria-live="polite">
        <span><strong>—</strong> {{ $itemLabel }} tersedia</span>
        <span>Realtime</span>
    </div>
</div>

@once
    @push('head')
        <style>
            .ui-search { position: relative; --ui-search-surface: var(--card, #fff); --ui-search-surface-soft: var(--card-soft, #f8fafc); --ui-search-text: var(--text, #0f172a); --ui-search-muted: var(--muted, #64748b); --ui-search-line: var(--line, #e2e8f0); --ui-search-brand: var(--accent, #2563eb); --ui-search-brand-soft: var(--accent-soft, #dbeafe); }
            .ui-search-icon { position: absolute; top: 19px; left: .75rem; z-index: 1; color: var(--ui-search-muted); font-size: .85rem; pointer-events: none; transform: translateY(-50%); }
            .ui-search-input { width: 100%; min-height: 38px; padding: .45rem 2.2rem .45rem 2.15rem; border: 1px solid var(--ui-search-line); border-radius: 10px; background: var(--ui-search-surface-soft); color: var(--ui-search-text); font-size: .75rem; outline: none; transition: border-color .16s ease, box-shadow .16s ease, background-color .16s ease; }
            .ui-search-input::placeholder { color: var(--ui-search-muted); opacity: .9; }
            .ui-search-input:focus { border-color: var(--ui-search-brand); background: var(--ui-search-surface); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ui-search-brand-soft) 80%, transparent); }
            .ui-search-clear { position: absolute; top: 19px; right: .4rem; display: inline-grid; place-items: center; width: 28px; height: 28px; padding: 0; border: 0; border-radius: 7px; background: transparent; color: var(--ui-search-muted); cursor: pointer; transform: translateY(-50%); }
            .ui-search-clear[hidden] { display: none; }
            .ui-search-clear:hover { background: var(--ui-search-line); color: var(--ui-search-text); }
            .ui-search-clear:focus-visible { outline: 3px solid color-mix(in srgb, var(--ui-search-brand) 32%, transparent); outline-offset: 1px; }
            .ui-search-meta { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin: .45rem 0 .25rem; color: var(--ui-search-muted); font-size: .65rem; }
            .ui-search-meta strong { color: var(--ui-search-text); font-weight: 850; }
            .ui-search [data-ui-search-hidden="true"] { display: none; }
            @media (prefers-reduced-motion: reduce) { .ui-search-input { transition: none; } }
        </style>
    @endpush

    @push('scripts')
        <script>
            (function () {
                function normalize(value) {
                    return value.toLocaleLowerCase('id-ID').trim();
                }

                function setCount(container, count, total, query) {
                    const countElement = document.getElementById(container.dataset.uiSearchCountId);
                    if (!countElement) return;

                    const itemLabel = container.dataset.uiSearchItemLabel || 'hasil';
                    const summary = document.createElement('span');
                    const strong = document.createElement('strong');
                    strong.textContent = count;
                    summary.append(strong, document.createTextNode(query ? ' dari ' + total + ' ' + itemLabel : ' ' + itemLabel + ' tersedia'));

                    const mode = document.createElement('span');
                    mode.textContent = query ? 'Hasil pencarian' : 'Realtime';
                    countElement.replaceChildren(summary, mode);
                }

                function initSearch(container) {
                    const input = container.querySelector('.ui-search-input');
                    const clearButton = container.querySelector('.ui-search-clear');
                    const targetSelector = container.dataset.uiSearchTarget;
                    if (!input || !clearButton || !targetSelector) return;

                    let items;
                    try {
                        items = Array.from(document.querySelectorAll(targetSelector));
                    } catch (error) {
                        console.warn('Invalid search target selector:', targetSelector);
                        return;
                    }
                    if (!items.length) return;

                    const total = items.length;
                    const emptyState = document.getElementById(container.dataset.uiSearchEmptyId);

                    function update() {
                        const query = normalize(input.value);
                        let visible = 0;

                        items.forEach(function (item) {
                            const matches = !query || normalize(item.textContent).includes(query);
                            item.dataset.uiSearchHidden = matches ? 'false' : 'true';
                            if (matches) visible += 1;
                        });

                        clearButton.hidden = !input.value;
                        if (emptyState) {
                            emptyState.classList.toggle('is-visible', visible === 0);
                            emptyState.hidden = visible !== 0;
                        }
                        setCount(container, visible, total, query);
                    }

                    function selectAll() {
                        input.select();
                    }

                    input.addEventListener('focus', selectAll);
                    input.addEventListener('click', selectAll);
                    input.addEventListener('input', update);
                    input.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape' && input.value) {
                            input.value = '';
                            update();
                        }
                    });
                    clearButton.addEventListener('click', function () {
                        input.value = '';
                        update();
                        input.focus();
                    });
                    update();
                }

                function init() {
                    document.querySelectorAll('[data-ui-search]').forEach(initSearch);
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }
            })();
        </script>
    @endpush
@endonce
