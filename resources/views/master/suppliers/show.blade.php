@extends('layouts.app')

@section('title', 'Master • Supplier • ' . $supplier->name)

@push('head')
    <style>
        .supplier-show-page .page-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
        }

        .supplier-show-page .page-subtitle {
            font-size: .88rem;
            color: var(--muted);
            margin-top: .25rem;
        }

        .supplier-show-page .btn-pill {
            border-radius: 999px;
            padding: .45rem .95rem;
        }

        .supplier-show-page .card-soft {
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .22);
            background: color-mix(in srgb, var(--card) 94%, var(--bg) 6%);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06);
        }

        .supplier-show-page .chip {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .32rem .6rem;
            border-radius: 999px;
            border: 1px solid rgba(59, 130, 246, .22);
            background: rgba(59, 130, 246, .10);
            color: rgba(29, 78, 216, 1);
            font-size: .82rem;
            user-select: none;
        }

        .supplier-show-page .chip-muted {
            border-color: rgba(148, 163, 184, .22);
            background: rgba(148, 163, 184, .08);
            color: rgba(100, 116, 139, 1);
        }

        .supplier-show-page .code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: .85rem;
            padding: .14rem .42rem;
            border-radius: 8px;
            border: 1px solid rgba(148, 163, 184, .22);
            background: rgba(148, 163, 184, .08);
        }

        .supplier-show-page .muted {
            color: var(--muted);
        }

        .supplier-show-page .mini {
            font-size: .82rem;
        }

        /* Suggest dropdown */
        .supplier-show-page .suggest-wrap {
            position: relative;
        }

        .supplier-show-page .suggest-box {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            z-index: 9999;
            display: none;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .22);
            background: color-mix(in srgb, var(--card) 96%, var(--bg) 4%);
            box-shadow: 0 14px 32px rgba(15, 23, 42, .12);
            max-height: 280px;
            overflow: auto;
        }

        .supplier-show-page .suggest-item {
            padding: .6rem .75rem;
            border-bottom: 1px solid rgba(148, 163, 184, .14);
            cursor: pointer;
        }

        .supplier-show-page .suggest-item:last-child {
            border-bottom: none;
        }

        .supplier-show-page .suggest-item:hover {
            background: rgba(59, 130, 246, .08);
        }

        .supplier-show-page .suggest-item.active {
            background: rgba(59, 130, 246, .12);
        }

        .supplier-show-page .zebra tbody tr:nth-child(odd) {
            background: rgba(148, 163, 184, .06);
        }

        .supplier-show-page .zebra tbody tr:hover {
            background: rgba(59, 130, 246, .08);
        }
    </style>
@endpush

@section('content')
    @php $csrf = csrf_token(); @endphp

    <div class="supplier-show-page container py-3">

        {{-- Header --}}
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h1 class="page-title">{{ $supplier->name }}</h1>
                    <span class="code">{{ $supplier->code }}</span>

                    @if ((int) $supplier->active === 1)
                        <span class="chip"
                            style="background:rgba(34,197,94,.10);border-color:rgba(34,197,94,.22);color:rgba(22,163,74,1)">Active</span>
                    @else
                        <span class="chip chip-muted">Inactive</span>
                    @endif
                </div>

                <div class="page-subtitle">
                    @if ($supplier->phone)
                        {{ $supplier->phone }}
                    @endif
                    @if ($supplier->email)
                        @if ($supplier->phone)
                            •
                        @endif
                        {{ $supplier->email }}
                    @endif
                    @if (!$supplier->phone && !$supplier->email)
                        <span class="muted">Kontak belum diisi</span>
                    @endif
                </div>

                @if ($supplier->address)
                    <div class="muted mini mt-1">{{ $supplier->address }}</div>
                @endif
            </div>

            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary btn-pill"
                    href="{{ route('master.suppliers.edit', $supplier) }}">Edit</a>
                <a class="btn btn-outline-primary btn-pill" href="{{ route('master.suppliers.index') }}">Back</a>
            </div>
        </div>

        {{-- Mapping --}}
        <div class="card card-soft mt-3">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <div class="fw-semibold">Item yang dijual</div>
                        <div class="muted mini">Cari item → tambah mapping → set harga default (last_price).</div>
                    </div>
                    <div id="mapHint" class="muted mini"></div>
                </div>

                <div class="row g-2 mt-2 align-items-end">
                    <div class="col-md-7">
                        <label class="form-label mb-1">Cari Item</label>

                        <div class="suggest-wrap">
                            <input id="map_search" class="form-control" placeholder="Ketik code/nama item... (min 2 huruf)"
                                autocomplete="off">
                            <div id="suggestBox" class="suggest-box"></div>
                        </div>

                        <div id="suggestErr" class="text-danger mini mt-1" style="display:none"></div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label mb-1">Last Price (Rp)</label>
                        <input id="map_last_price" class="form-control" inputmode="decimal" placeholder="0">
                        {{-- ❌ no hint text --}}
                        <div id="suggestHint" class="muted mini mt-1" style="display:none"></div>
                    </div>

                    <div class="col-md-2 d-grid">
                        <button id="btnAttach" class="btn btn-primary btn-pill" type="button" disabled>Tambah</button>
                    </div>
                </div>

                <div class="mt-3">
                    <input id="filterText" class="form-control" placeholder="Filter mapping (code/nama)..." />
                </div>
            </div>
        </div>

        {{-- Table mapping --}}
        <div class="card card-soft mt-3">
            <div class="card-body">
                <div class="fw-semibold mb-2">Daftar item supplier</div>
                <div id="itemsContainer" class="table-responsive">
                    <div class="muted">Loading...</div>
                </div>
            </div>
        </div>

    </div>

    <script>
        (function() {
            const csrf = @json($csrf);

            const urlJson = @json(route('master.suppliers.items.json', $supplier));
            const urlAttach = @json(route('master.suppliers.items.attach', $supplier));
            const urlUpdateTpl = @json(route('master.suppliers.items.update', [$supplier, 0])); // replace /0
            const urlDetachTpl = @json(route('master.suppliers.items.detach', [$supplier, 0])); // replace /0
            const urlSuggest = @json(route('master.items.suggest'));

            const elContainer = document.getElementById('itemsContainer');
            const elHint = document.getElementById('mapHint');
            const elFilter = document.getElementById('filterText');

            const elSearch = document.getElementById('map_search');
            const elBox = document.getElementById('suggestBox');
            const elErr = document.getElementById('suggestErr');
            const elMapPrice = document.getElementById('map_last_price');
            const elSuggestHint = document.getElementById('suggestHint');
            const btnAttach = document.getElementById('btnAttach');

            let cached = null;
            let pickedItem = null;

            let suggestTimer = null;
            let lastSuggestItems = [];
            let activeIndex = -1;

            function escapeHtml(s) {
                return String(s ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function showSuggest(html, visible) {
                elBox.innerHTML = html || '';
                elBox.style.display = visible ? 'block' : 'none';
            }

            function setErr(msg) {
                if (!msg) {
                    elErr.style.display = 'none';
                    elErr.textContent = '';
                    return;
                }
                elErr.style.display = 'block';
                elErr.textContent = msg;
            }

            async function fetchJson() {
                const res = await fetch(urlJson, {
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('Failed to load items');
                return await res.json();
            }

            function buildTable(data, q) {
                const labels = data.labels || {};
                const groups = data.groups || {};
                const order = ['material', 'accessory', 'finished_good', 'other'];

                const keys = order.filter(k => groups[k] && groups[k].length)
                    .concat(Object.keys(groups).filter(k => !order.includes(k) && groups[k] && groups[k].length));

                if (!keys.length) {
                    return `<div class="muted">Belum ada mapping item untuk supplier ini.</div>`;
                }

                const query = (q || '').trim().toLowerCase();
                let html = '';

                keys.forEach(type => {
                    const rows = (groups[type] || []).filter(it => {
                        if (!query) return true;
                        const hay = (it.code + ' ' + it.name).toLowerCase();
                        return hay.includes(query);
                    });

                    if (!rows.length) return;

                    html += `
                        <div class="mb-3">
                          <div class="fw-semibold mb-2">${escapeHtml(labels[type] || type)}
                            <span class="muted">(${rows.length})</span>
                          </div>
                          <table class="table table-sm align-middle zebra">
                            <thead>
                              <tr class="muted">
                                <th style="width: 26%">Item</th>
                                <th>Nama</th>
                                <th style="width: 18%">Harga</th>
                                <th style="width: 10%">Unit</th>
                                <th style="width: 10%"></th>
                              </tr>
                            </thead>
                            <tbody>
                    `;

                    rows.forEach(it => {
                        const updateUrl = urlUpdateTpl.replace(/\/0$/, '/' + it.id);
                        const detachUrl = urlDetachTpl.replace(/\/0$/, '/' + it.id);

                        html += `
                            <tr>
                              <td><b>${escapeHtml(it.code)}</b></td>
                              <td>${escapeHtml(it.name)}</td>
                              <td>
                                <input class="form-control form-control-sm js-price"
                                       inputmode="decimal"
                                       value="${escapeHtml(it.last_price ?? 0)}"
                                       data-update-url="${escapeHtml(updateUrl)}"
                                       placeholder="0" />
                              </td>
                              <td>${escapeHtml(it.unit || 'pcs')}</td>
                              <td class="text-end">
                                <button class="btn btn-outline-danger btn-sm js-detach btn-pill"
                                        data-detach-url="${escapeHtml(detachUrl)}"
                                        type="button">Hapus</button>
                              </td>
                            </tr>
                        `;
                    });

                    html += `</tbody></table></div>`;
                });

                return html || `<div class="muted">Tidak ada hasil untuk filter ini.</div>`;
            }

            async function reload() {
                elContainer.innerHTML = `<div class="muted">Loading...</div>`;
                try {
                    cached = await fetchJson();
                    elHint.textContent = `${cached.count || 0} item termapping`;
                    elContainer.innerHTML = buildTable(cached, elFilter.value);
                    bindRowEvents();
                } catch (e) {
                    elContainer.innerHTML = `<div class="text-danger">Gagal load items supplier.</div>`;
                }
            }

            function bindRowEvents() {
                elContainer.querySelectorAll('.js-price').forEach(input => {
                    input.addEventListener('blur', async () => {
                        const v = input.value === '' ? '0' : input.value;
                        const num = Number(String(v).replaceAll('.', '').replaceAll(',', '.'));
                        if (Number.isNaN(num) || num < 0) return;

                        const url = input.dataset.updateUrl;

                        try {
                            const res = await fetch(url, {
                                method: 'PUT',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrf,
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify({
                                    last_price: num
                                }),
                            });

                            if (!res.ok) throw new Error('update failed');
                            await reload();
                        } catch (e) {
                            alert('Gagal update harga.');
                        }
                    });
                });

                elContainer.querySelectorAll('.js-detach').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        if (!confirm('Hapus mapping item dari supplier?')) return;

                        const url = btn.dataset.detachUrl;

                        try {
                            const res = await fetch(url, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrf,
                                },
                                credentials: 'same-origin',
                            });

                            if (!res.ok) throw new Error('detach failed');
                            await reload();
                        } catch (e) {
                            alert('Gagal hapus mapping.');
                        }
                    });
                });
            }

            // ===== Suggest =====
            async function doSuggest(q) {
                setErr('');

                const u = new URL(urlSuggest, window.location.origin);
                u.searchParams.set('q', q);

                const res = await fetch(u.toString(), {
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                });

                const ct = (res.headers.get('content-type') || '').toLowerCase();

                if (!res.ok) {
                    setErr(`Suggest error ${res.status}.`);
                    const txt = await res.text().catch(() => '');
                    console.error('[SUGGEST] HTTP not ok:', res.status, ct, u.toString(), txt.slice(0, 300));
                    return [];
                }

                if (!ct.includes('application/json')) {
                    setErr('Suggest response bukan JSON (cek auth/middleware).');
                    const txt = await res.text().catch(() => '');
                    console.error('[SUGGEST] Not JSON:', ct, u.toString(), txt.slice(0, 300));
                    return [];
                }

                const data = await res.json().catch((e) => {
                    setErr('Suggest JSON parse error.');
                    console.error('[SUGGEST] JSON parse error:', e);
                    return null;
                });

                if (!data) return [];
                return data.items || [];
            }

            function pickItem(it) {
                pickedItem = it;
                elSearch.value = `${it.code} — ${it.name}`;

                // ❌ no hint text
                elSuggestHint.textContent = '';
                elSuggestHint.style.display = 'none';

                if (!elMapPrice.value || String(elMapPrice.value).trim() === '') {
                    elMapPrice.value = String(it.last_purchase_price || 0);
                }

                btnAttach.disabled = false;
                showSuggest('', false);

                activeIndex = -1;
                lastSuggestItems = [];
            }

            function renderSuggest(items) {
                lastSuggestItems = items || [];
                activeIndex = -1;

                if (!items || !items.length) {
                    showSuggest(`<div class="suggest-item"><div class="muted">Tidak ada hasil.</div></div>`, true);
                    return;
                }

                const html = items.map((it, idx) => {
                    return `
                        <div class="suggest-item" data-id="${it.id}" data-idx="${idx}">
                          <div><b>${escapeHtml(it.code)}</b> — ${escapeHtml(it.name)}</div>
                        </div>
                    `;
                }).join('');

                showSuggest(html, true);

                elBox.querySelectorAll('.suggest-item[data-id]').forEach(div => {
                    div.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        const id = Number(div.dataset.id);
                        const it = items.find(x => x.id === id);
                        if (it) pickItem(it);
                    });
                });
            }

            function setActive(idx) {
                const nodes = elBox.querySelectorAll('.suggest-item[data-idx]');
                nodes.forEach(n => n.classList.remove('active'));

                const target = elBox.querySelector(`.suggest-item[data-idx="${idx}"]`);
                if (!target) return;

                target.classList.add('active');

                const top = target.offsetTop;
                const bottom = top + target.offsetHeight;

                if (top < elBox.scrollTop) elBox.scrollTop = top - 8;
                if (bottom > elBox.scrollTop + elBox.clientHeight) elBox.scrollTop = bottom - elBox.clientHeight + 8;
            }

            elSearch.addEventListener('input', () => {
                const q = (elSearch.value || '').trim();

                pickedItem = null;
                btnAttach.disabled = true;
                setErr('');

                if (q.length < 2) {
                    showSuggest('', false);
                    return;
                }

                clearTimeout(suggestTimer);
                suggestTimer = setTimeout(async () => {
                    const items = await doSuggest(q);
                    renderSuggest(items);
                }, 180);
            });

            elSearch.addEventListener('keydown', (e) => {
                if (elBox.style.display !== 'block') return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (!lastSuggestItems.length) return;
                    activeIndex = Math.min(activeIndex + 1, lastSuggestItems.length - 1);
                    setActive(activeIndex);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (!lastSuggestItems.length) return;
                    activeIndex = Math.max(activeIndex - 1, 0);
                    setActive(activeIndex);
                } else if (e.key === 'Enter') {
                    if (activeIndex >= 0 && lastSuggestItems[activeIndex]) {
                        e.preventDefault();
                        pickItem(lastSuggestItems[activeIndex]);
                    }
                } else if (e.key === 'Escape') {
                    showSuggest('', false);
                }
            });

            document.addEventListener('mousedown', (e) => {
                if (!elBox.contains(e.target) && e.target !== elSearch) {
                    showSuggest('', false);
                }
            });

            btnAttach.addEventListener('click', async () => {
                if (!pickedItem || !pickedItem.id) return alert('Pilih item dari hasil suggest.');

                const raw = elMapPrice.value || '0';
                const num = Number(String(raw).replaceAll('.', '').replaceAll(',', '.'));
                if (Number.isNaN(num) || num < 0) return alert('Harga tidak valid.');

                btnAttach.disabled = true;

                try {
                    const res = await fetch(urlAttach, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            item_id: pickedItem.id,
                            last_price: num
                        }),
                    });

                    if (!res.ok) throw new Error('attach failed');

                    elSearch.value = '';
                    elMapPrice.value = '';
                    pickedItem = null;
                    setErr('');
                    showSuggest('', false);

                    await reload();
                } catch (e) {
                    alert('Gagal tambah mapping.');
                    btnAttach.disabled = false;
                } finally {
                    btnAttach.disabled = true;
                }
            });

            elFilter.addEventListener('input', () => {
                if (!cached) return;
                elContainer.innerHTML = buildTable(cached, elFilter.value);
                bindRowEvents();
            });

            reload();
        })();
    </script>
@endsection
