{{-- resources/views/inventory/barcodes/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Cetak Barcode Barang')

@push('head')
<style>
/* ══════════════════════════════════════════════════
   THEME VARIABLES (selaras dengan Shipments)
══════════════════════════════════════════════════ */
:root {
    --shp-accent:      #334155;
    --shp-accent-2:    #1f2937;
    --shp-accent-bg:   rgba(148,163,184,.08);
    --shp-accent-ring: rgba(148,163,184,.18);
}

/* ── Page wrap ── */
.shp-wrap {
    max-width: 1000px;
    margin-inline: auto;
    padding: 0 .75rem 6rem;
}
body[data-theme="light"] .shp-wrap { background: #f3f4f6; }
body[data-theme="dark"]  .shp-wrap { background: #020617; }

/* ── Sticky topbar ── */
.shp-topbar {
    position: sticky;
    top: 0;
    z-index: 300;
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .5rem .85rem;
    background: rgba(248,250,252,.97);
    border-bottom: 1px solid rgba(148,163,184,.22);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    flex-wrap: wrap;
}
body[data-theme="dark"] .shp-topbar { background: rgba(2,6,23,.96); border-bottom-color: rgba(51,65,85,.85); }
.shp-topbar-code { font-weight: 900; font-size: 1.05rem; letter-spacing: .02em; white-space: nowrap; }
body[data-theme="dark"] .shp-topbar-code { color: #e5e7eb; }

.shp-badge {
    border-radius: 999px;
    padding: .15rem .65rem;
    font-size: .7rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    white-space: nowrap;
    border: 1px solid rgba(148,163,184,.55);
    color: #475569;
    background: transparent;
}
body[data-theme="dark"] .shp-badge { color: #cbd5e1; border-color: rgba(71,85,105,.8); }

.shp-topbar-spacer { flex: 1; min-width: .5rem; }

.shp-pill {
    border-radius: 999px;
    padding: .2rem .75rem;
    font-size: .77rem;
    border: 1px solid rgba(148,163,184,.32);
    background: rgba(248,250,252,.96);
    white-space: nowrap;
    color: inherit;
}
body[data-theme="dark"] .shp-pill { background: rgba(15,23,42,.98); border-color: rgba(51,65,85,.85); color: #e5e7eb; }
.shp-pill b { font-size: .87rem; }
.shp-pill-accent {
    border-color: var(--shp-accent) !important;
    background: var(--shp-accent-bg) !important;
    color: var(--shp-accent) !important;
    font-weight: 700;
}
body[data-theme="dark"] .shp-pill-accent { color: #cbd5e1 !important; }

/* ── Buttons ── */
.btn-shp-submit {
    border-radius: 999px;
    font-size: .8rem;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
    padding: .42rem 1.35rem;
    border: 1px solid var(--shp-accent);
    background: var(--shp-accent);
    color: #fff;
    white-space: nowrap;
    transition: background .12s;
    cursor: pointer;
}
.btn-shp-submit:hover { background: var(--shp-accent-2); border-color: var(--shp-accent-2); color: #fff; }
.btn-shp-outline {
    border-radius: 999px;
    font-size: .77rem;
    letter-spacing: .03em;
    text-transform: uppercase;
    padding: .32rem 1rem;
    border: 1px solid rgba(148,163,184,.5);
    background: transparent;
    color: #6b7280;
    white-space: nowrap;
    text-decoration: none;
    transition: background .12s, color .12s;
    cursor: pointer;
}
.btn-shp-outline:hover { background: rgba(226,232,240,.7); color: #374151; }
body[data-theme="dark"] .btn-shp-outline { color: #d1d5db; border-color: rgba(71,85,105,.8); }
body[data-theme="dark"] .btn-shp-outline:hover { background: rgba(30,41,59,.7); color: #f1f5f9; }

/* ── Table card ── */
.shp-table-card {
    background: var(--card, #fff);
    border-radius: 20px;
    border: 1px solid rgba(148,163,184,.16);
    box-shadow: 0 4px 18px rgba(15,23,42,.05);
    margin-top: .85rem;
    overflow: hidden;
}
body[data-theme="dark"] .shp-table-card { border-color: rgba(51,65,85,.85); box-shadow: 0 12px 36px rgba(15,23,42,.8); }
.shp-table-head {
    display: flex;
    align-items: center;
    gap: .65rem;
    flex-wrap: wrap;
    padding: .85rem 1.25rem .7rem;
    border-bottom: 1px solid rgba(148,163,184,.14);
}
body[data-theme="dark"] .shp-table-head { border-bottom-color: rgba(51,65,85,.8); }
.shp-table-title { font-size: .68rem; text-transform: uppercase; letter-spacing: .1em; color: #9ca3af; font-weight: 700; }
.shp-table-title small {
    display: block; text-transform: none; letter-spacing: 0;
    font-size: .74rem; color: #9ca3af; font-weight: 400; margin-top: .15rem;
}

/* ── Scan box ── */
.shp-scan { padding: .85rem 1.25rem; border-bottom: 1px solid rgba(148,163,184,.12); }
body[data-theme="dark"] .shp-scan { border-bottom-color: rgba(51,65,85,.6); }
.shp-scan-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .1em; color: #9ca3af; font-weight: 700; margin-bottom: .4rem; }
.shp-scan-wrap {
    display: flex;
    gap: .5rem;
    align-items: center;
    background: rgba(148,163,184,.1);
    border: 1px solid rgba(148,163,184,.28);
    border-radius: 8px;
    padding: .45rem .55rem;
    transition: border-color .12s, box-shadow .12s;
}
.shp-scan-wrap:focus-within { border-color: var(--shp-accent); box-shadow: 0 0 0 3px var(--shp-accent-ring); }
body[data-theme="dark"] .shp-scan-wrap { background: rgba(15,23,42,.7); border-color: rgba(51,65,85,.85); }
.shp-scan-wrap input {
    flex: 1; background: transparent; border: none; padding: .2rem .3rem;
    color: inherit; font-size: 1rem; font-weight: 700;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace; outline: none; text-transform: uppercase;
}
.shp-scan-wrap input::placeholder { color: #94a3b8; font-weight: 400; font-family: inherit; text-transform: none; }
.shp-scan-btn {
    background: var(--shp-accent); border: none; border-radius: 6px; padding: .5rem 1.1rem;
    color: #fff; font-weight: 700; font-size: .86rem; cursor: pointer; transition: background .15s; white-space: nowrap;
}
.shp-scan-btn:hover { background: var(--shp-accent-2); }
.shp-scan-status { margin-top: .4rem; font-size: .78rem; min-height: 1.1rem; color: #94a3b8; }
.shp-scan-status.ok  { color: #15803d; }
.shp-scan-status.err { color: #b91c1c; }

/* ── Suggest Box ── */
.shp-suggest {
    display: none;
    position: absolute;
    top: calc(100% + 3px);
    left: 0;
    right: 0;
    z-index: 400;
    max-height: 108px;
    overflow-y: auto;
    background: var(--card, #fff);
    border: 1px solid rgba(148,163,184,.28);
    border-radius: 8px;
    box-shadow: 0 6px 18px rgba(15,23,42,.1);
    padding: .18rem;
    scrollbar-width: thin;
}
body[data-theme="dark"] .shp-suggest {
    background: #0f172a;
    border-color: rgba(51,65,85,.8);
    box-shadow: 0 8px 22px rgba(0,0,0,.5);
}
.shp-suggest.is-open { display: block; }
.shp-suggest-item {
    display: flex;
    align-items: baseline;
    gap: .5rem;
    padding: .32rem .5rem;
    border-radius: 6px;
    cursor: pointer;
    line-height: 1.2;
}
.shp-suggest-item:hover,
.shp-suggest-item.is-active {
    background: rgba(148,163,184,.14);
}
body[data-theme="dark"] .shp-suggest-item:hover,
body[data-theme="dark"] .shp-suggest-item.is-active {
    background: rgba(148,163,184,.16);
}
.shp-suggest-code {
    font-family: monospace;
    font-weight: 700;
    font-size: .8rem;
    letter-spacing: 0;
    white-space: nowrap;
    color: #334155;
}
body[data-theme="dark"] .shp-suggest-code { color: #e2e8f0; }
.shp-suggest-name {
    font-size: .74rem;
    color: #64748b;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
body[data-theme="dark"] .shp-suggest-name { color: #94a3b8; }
.shp-suggest-cat {
    margin-left: auto;
    font-size: .64rem;
    color: #94a3b8;
    white-space: nowrap;
    flex-shrink: 0;
}
.shp-suggest-empty {
    padding: .42rem .5rem;
    font-size: .76rem;
    color: #94a3b8;
}
.shp-suggest mark {
    background: rgba(250,204,21,.4);
    color: inherit;
    padding: 0;
    border-radius: 2px;
}

/* ── Lines table (persis shipments) ── */
.shp-lines-scroll {
    max-height: 46vh;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(148,163,184,.5) transparent;
}
.shp-lines-scroll::-webkit-scrollbar { width: 5px; }
.shp-lines-scroll::-webkit-scrollbar-thumb { background: rgba(148,163,184,.5); border-radius: 99px; }

.shp-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.shp-table thead th {
    position: sticky; top: 0; z-index: 5;
    background: rgba(248,250,252,.98);
    font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; color: #6b7280;
    border-bottom: 1px solid rgba(148,163,184,.16);
    padding: .45rem .85rem;
    text-align: left; white-space: nowrap;
}
body[data-theme="dark"] .shp-table thead th { background: rgba(15,23,42,.98); border-bottom-color: rgba(51,65,85,.7); color: #6b7280; }
.shp-table tbody td { vertical-align: middle; padding: .4rem .85rem; border-top: 1px solid rgba(148,163,184,.1); }
body[data-theme="dark"] .shp-table tbody td { border-top-color: rgba(51,65,85,.6); }
.shp-table tbody tr:nth-child(even) td { background: rgba(249,250,251,.6); }
body[data-theme="dark"] .shp-table tbody tr:nth-child(even) td { background: rgba(15,23,42,.6); }
.shp-table tbody tr.row-new td { background: rgba(254,243,199,.85) !important; }
body[data-theme="dark"] .shp-table tbody tr.row-new td { background: rgba(120,53,15,.5) !important; }

.col-no  { width: 40px; text-align: center; }
.col-qty { width: 180px; text-align: right; }
.col-act { width: 70px; text-align: right; }

.row-num  { color: #9ca3af; font-size: .82rem; text-align: center; }
.item-code { font-weight: 800; font-size: .9rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
.item-name { font-size: .82rem; color: #6b7280; }
body[data-theme="dark"] .item-name { color: #94a3b8; }

/* ── Qty stepper (user friendly) ── */
.qty-stepper {
    display: inline-flex; align-items: stretch;
    border: 1px solid rgba(148,163,184,.4); border-radius: 9px;
    overflow: hidden; background: var(--card, #fff);
    width: 100%; max-width: 150px; margin-left: auto;
}
body[data-theme="dark"] .qty-stepper { background: rgba(15,23,42,.6); border-color: rgba(51,65,85,.85); }
.qty-stepper:focus-within { border-color: var(--shp-accent); box-shadow: 0 0 0 3px var(--shp-accent-ring); }
.qty-stepper .line-qty {
    flex: 1; width: auto !important; min-width: 0;
    border: none !important; outline: none; background: transparent;
    text-align: center; font-size: 1rem; font-weight: 800; font-variant-numeric: tabular-nums;
    padding: .4rem .25rem; color: inherit; box-shadow: none !important; border-radius: 0 !important;
    -moz-appearance: textfield;
}
.qty-stepper .line-qty::-webkit-outer-spin-button,
.qty-stepper .line-qty::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.qty-btn {
    border: none; background: rgba(148,163,184,.14); color: var(--shp-accent);
    width: 38px; flex: 0 0 38px; font-size: 1.2rem; font-weight: 700; line-height: 1; cursor: pointer;
    display: flex; align-items: center; justify-content: center; user-select: none; transition: background .12s;
}
.qty-btn:hover { background: var(--shp-accent); color: #fff; }
.qty-btn:active { background: var(--shp-accent-2); color: #fff; }
body[data-theme="dark"] .qty-btn { color: #cbd5e1; background: rgba(51,65,85,.5); }
body[data-theme="dark"] .qty-btn:hover { background: var(--shp-accent); color: #fff; }
@media (max-width: 640px) {
    .qty-stepper { max-width: none; }
    .qty-btn { width: 44px; flex-basis: 44px; font-size: 1.35rem; }
    .qty-stepper .line-qty { font-size: 1.05rem; padding: .55rem .25rem; }
}

.btn-del {
    border-radius: 999px;
    border: 1px solid rgba(248,113,113,.6);
    background: transparent; color: #ef4444;
    font-size: .74rem; font-weight: 700; padding: .28rem .8rem; cursor: pointer;
    transition: background .12s, color .12s;
}
.btn-del:hover { background: rgba(239,68,68,.1); }

.empty-row td { text-align: center; color: #9ca3af; padding: 2.2rem 1rem !important; font-size: .85rem; }

.shp-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
    padding: .8rem 1.25rem;
    border-top: 1px solid rgba(148,163,184,.12);
}
body[data-theme="dark"] .shp-foot { border-top-color: rgba(51,65,85,.65); }
.shp-foot .muted { color: #6b7280; font-size: .82rem; }

@media (max-width: 640px) {
    .hide-sm { display: none !important; }
}
</style>
@endpush

@section('content')
<form id="barcodeForm" method="GET" action="{{ route('inventory.barcodes.print') }}" target="_blank">

    {{-- ═════════════════ STICKY TOP BAR ═════════════════ --}}
    <div class="shp-topbar">
        <span class="shp-topbar-code">Cetak Barcode</span>
        <span class="shp-badge">Barcode = Kode Item</span>

        <span class="shp-topbar-spacer"></span>

        <span class="shp-pill">Baris <b id="summaryLines">0</b></span>
        <span class="shp-pill shp-pill-accent">Total Label <b id="totalLabels">0</b></span>

        <a href="{{ url()->previous() }}" class="btn-shp-outline">Kembali</a>
        <button type="submit" class="btn-shp-submit">Preview &amp; Cetak</button>
    </div>

    <div class="shp-wrap">
        <div class="shp-table-card">
            <div class="shp-table-head">
                <div class="shp-table-title">
                    Daftar Barang
                    <small>Scan barcode atau ketik kode untuk menambah barang.</small>
                </div>
            </div>

            {{-- Scan box --}}
            <div class="shp-scan">
                <div class="shp-scan-label">Scan / Ketik Kode atau Nama Barang</div>
                <div class="shp-scan-wrap" style="position: relative;">
                    <input id="scanInput" type="text" autocomplete="off" autofocus
                           placeholder="Scan barcode atau ketik kode/nama item lalu Enter">
                    <button type="button" class="shp-scan-btn" id="scanBtn" onclick="window.__doScan()">Tambah</button>
                    <div id="suggestBox" class="shp-suggest" role="listbox"></div>
                </div>
                <div class="shp-scan-status" id="scanStatus"></div>
            </div>

            {{-- Lines table --}}
            <div class="shp-lines-scroll">
                <table class="shp-table">
                    <thead>
                        <tr>
                            <th class="col-no">#</th>
                            <th>Kode</th>
                            <th class="hide-sm">Nama Barang</th>
                            <th class="col-qty">Jumlah Label</th>
                            <th class="col-act"></th>
                        </tr>
                    </thead>
                    <tbody id="linesTbody">
                        <tr class="empty-row" id="emptyRow">
                            <td colspan="5">Belum ada barang. Scan atau ketik kode di atas.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="shp-foot">
                <div class="muted">Barcode berisi kode item (SKU). Nama varian ikut tercetak di label.</div>
                <div style="display:flex; gap:.5rem">
                    <button type="button" class="btn-shp-outline" id="btnClear">Bersihkan</button>
                    <button type="submit" class="btn-shp-submit">Preview &amp; Cetak</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    const tbody    = document.getElementById('linesTbody');
    const form     = document.getElementById('barcodeForm');
    const totalEl  = document.getElementById('totalLabels');
    const linesEl  = document.getElementById('summaryLines');
    const scanInput  = document.getElementById('scanInput');
    const scanStatus = document.getElementById('scanStatus');
    const suggestBox = document.getElementById('suggestBox');

    const EMPTY_HTML = '<tr class="empty-row" id="emptyRow"><td colspan="5">Belum ada barang. Scan atau ketik kode di atas.</td></tr>';

    function num(v) {
        const n = parseInt((v ?? '').toString(), 10);
        return isNaN(n) ? 0 : n;
    }

    function dataRows() { return [...tbody.querySelectorAll('tr[data-item-id]')]; }

    function toggleEmpty() {
        const has = dataRows().length > 0;
        const empty = document.getElementById('emptyRow');
        if (has && empty) empty.remove();
        if (!has && !empty) tbody.innerHTML = EMPTY_HTML;
    }

    function renumber() {
        dataRows().forEach((tr, idx) => {
            tr.querySelector('.row-num').textContent = String(idx + 1);
        });
    }

    function recalcTotal() {
        let total = 0, lines = 0;
        dataRows().forEach(tr => {
            const qty = num(tr.querySelector('.line-qty')?.value);
            if (qty > 0) { total += qty; lines++; }
        });
        totalEl.textContent = total;
        linesEl.textContent = lines;
        saveState();
    }

    function setStatus(msg, kind) {
        scanStatus.textContent = msg || '';
        scanStatus.className = 'shp-scan-status' + (kind ? ' ' + kind : '');
    }

    function flashRow(tr) {
        tr.classList.remove('row-new');
        void tr.offsetWidth;
        tr.classList.add('row-new');
        setTimeout(() => tr.classList.remove('row-new'), 900);
    }

    function findRowByItemId(id) {
        return tbody.querySelector('tr[data-item-id="' + String(id).replace(/"/g, '') + '"]');
    }

    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function renderItemRow(item, qty) {
        const empty = document.getElementById('emptyRow');
        if (empty) empty.remove();

        const tr = document.createElement('tr');
        tr.setAttribute('data-item-id', item.id);
        tr.setAttribute('data-item-name', item.name || '');
        const code = (item.code || '').toUpperCase();
        const qv = Math.max(1, num(qty) || 1);
        tr.innerHTML =
            '<td class="row-num"></td>' +
            '<td><span class="item-code">' + esc(code) + '</span>' +
                '<input type="hidden" name="id[]" value="' + esc(item.id) + '"></td>' +
            '<td class="hide-sm"><span class="item-name">' + esc(item.name || '') + '</span></td>' +
            '<td class="col-qty"><div class="qty-stepper">' +
                '<button type="button" class="qty-btn qty-minus" tabindex="-1" aria-label="Kurangi">−</button>' +
                '<input class="line-qty" name="qty[]" type="number" inputmode="numeric" min="1" step="1" value="' + qv + '">' +
                '<button type="button" class="qty-btn qty-plus" tabindex="-1" aria-label="Tambah">+</button>' +
            '</div></td>' +
            '<td class="col-act"><button type="button" class="btn-del">Hapus</button></td>';

        tbody.appendChild(tr);

        tr.querySelector('.btn-del').addEventListener('click', () => {
            tr.remove();
            renumber(); recalcTotal(); toggleEmpty();
        });
        tr.querySelector('.line-qty').addEventListener('input', recalcTotal);

        renumber(); recalcTotal();
        return tr;
    }

    function scanAddItem(item) {
        const existing = findRowByItemId(item.id);
        if (existing) {
            const q = existing.querySelector('.line-qty');
            if (q) q.value = num(q.value) + 1;
            flashRow(existing);
            recalcTotal();
            return existing;
        }
        const tr = renderItemRow(item, 1);
        flashRow(tr);
        return tr;
    }

    window.__doScan = function () {
        const code = (scanInput.value || '').trim();
        if (!code) { scanInput.focus(); return; }

        setStatus('Mencari…');
        fetch('/api/v1/items/suggest?q=' + encodeURIComponent(code) + '&limit=5', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(json => {
                const data = json?.data || [];
                if (!data.length) { setStatus('❌ "' + code + '" tidak ditemukan', 'err'); return; }
                const up = code.toUpperCase();
                const item = data.find(x => (x.code || '').toUpperCase() === up) || data[0];
                scanAddItem(item);
                setStatus('✅ ' + (item.code || '').toUpperCase() + ' ditambahkan', 'ok');
                scanInput.value = '';
                hideSuggest();
                scanInput.focus();
            })
            .catch(() => setStatus('❌ Gagal memuat data', 'err'));
    };

    /* ── Auto-Suggest logic ── */
    let suggestTimeout;
    let suggestItems = [];
    let activeSuggestIndex = -1;

    scanInput.addEventListener('input', (e) => {
        const val = e.target.value.trim();
        if (!val) {
            hideSuggest();
            return;
        }
        clearTimeout(suggestTimeout);
        suggestTimeout = setTimeout(() => {
            fetchSuggest(val);
        }, 300);
    });

    scanInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (activeSuggestIndex >= 0 && suggestItems[activeSuggestIndex]) {
                selectSuggest(suggestItems[activeSuggestIndex]);
            } else {
                window.__doScan();
                hideSuggest();
            }
        } else if (e.key === 'ArrowDown') {
            if (suggestItems.length > 0) {
                e.preventDefault();
                activeSuggestIndex++;
                if (activeSuggestIndex >= suggestItems.length) activeSuggestIndex = 0;
                updateSuggestActive();
            }
        } else if (e.key === 'ArrowUp') {
            if (suggestItems.length > 0) {
                e.preventDefault();
                activeSuggestIndex--;
                if (activeSuggestIndex < 0) activeSuggestIndex = suggestItems.length - 1;
                updateSuggestActive();
            }
        } else if (e.key === 'Escape') {
            hideSuggest();
        }
    });

    function hideSuggest() {
        if (suggestBox) {
            suggestBox.classList.remove('is-open');
            suggestBox.innerHTML = '';
        }
        suggestItems = [];
        activeSuggestIndex = -1;
    }

    function fetchSuggest(query) {
        // limit 2 agar maksimal 2 baris item auto suggest
        fetch('/api/v1/items/suggest?q=' + encodeURIComponent(query) + '&limit=2', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(json => {
                const data = json?.data || [];
                if (!data.length) {
                    suggestBox.innerHTML = '<div class="shp-suggest-empty">Tidak ada barang cocok.</div>';
                    suggestBox.classList.add('is-open');
                    return;
                }
                suggestItems = data;
                activeSuggestIndex = -1;
                renderSuggest(data, query);
            })
            .catch(() => hideSuggest());
    }

    function highlight(text, term) {
        const safe = esc(text);
        const t = (term || '').trim();
        if (!t) return safe;
        try {
            const re = new RegExp('(' + t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig');
            return safe.replace(re, '<mark>$1</mark>');
        } catch (e) { return safe; }
    }

    function renderSuggest(data, term) {
        suggestBox.innerHTML = '';
        data.forEach((item, idx) => {
            const div = document.createElement('div');
            div.className = 'shp-suggest-item';
            div.setAttribute('role', 'option');
            div.innerHTML = `
                <span class="shp-suggest-code">${highlight(item.code, term)}</span>
                <span class="shp-suggest-name">${highlight(item.name, term)}</span>
                ${item.item_category ? '<span class="shp-suggest-cat">' + esc(item.item_category) + '</span>' : ''}
            `;
            div.addEventListener('click', () => {
                selectSuggest(item);
            });
            suggestBox.appendChild(div);
        });
        suggestBox.classList.add('is-open');
    }

    function selectSuggest(item) {
        scanAddItem(item);
        setStatus('✅ ' + (item.code || '').toUpperCase() + ' ditambahkan', 'ok');
        scanInput.value = '';
        hideSuggest();
        scanInput.focus();
    }

    function updateSuggestActive() {
        const items = suggestBox.querySelectorAll('.shp-suggest-item');
        items.forEach((el, idx) => {
            if (idx === activeSuggestIndex) el.classList.add('is-active');
            else el.classList.remove('is-active');
        });
        
        if (activeSuggestIndex >= 0) {
            const code = suggestItems[activeSuggestIndex]?.code;
            if (code) scanInput.value = code;
        }
    }
    
    // click di luar akan menutup suggest
    document.addEventListener('click', (e) => {
        if (!scanInput.contains(e.target) && !suggestBox.contains(e.target)) {
            hideSuggest();
        }
    });

    document.getElementById('btnClear').addEventListener('click', () => {
        tbody.innerHTML = EMPTY_HTML;
        recalcTotal();
        setStatus('');
        focusScan();
    });

    /* ── Tombol +/- stepper & select-all pada qty ── */
    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('.qty-minus, .qty-plus');
        if (!btn) return;
        const inp = btn.closest('tr')?.querySelector('.line-qty');
        if (!inp) return;
        const next = Math.max(1, num(inp.value) + (btn.classList.contains('qty-plus') ? 1 : -1));
        inp.value = next;
        recalcTotal();
    });
    tbody.addEventListener('focusin', (e) => {
        if (e.target.classList?.contains('line-qty')) setTimeout(() => e.target.select(), 0);
    });

    /* ── Persist item terscan agar tidak hilang saat reload ── */
    const LS_KEY = 'barcode_labels_v1';
    function saveState() {
        try {
            const data = dataRows().map(tr => ({
                id:   tr.getAttribute('data-item-id'),
                name: tr.getAttribute('data-item-name') || '',
                code: tr.querySelector('.item-code')?.textContent || '',
                qty:  num(tr.querySelector('.line-qty')?.value),
            })).filter(x => x.id);
            localStorage.setItem(LS_KEY, JSON.stringify(data));
        } catch (_) {}
    }
    function restoreState() {
        try {
            const raw = localStorage.getItem(LS_KEY);
            if (!raw) return;
            const data = JSON.parse(raw);
            if (!Array.isArray(data) || !data.length) return;
            data.forEach(it => {
                if (!it.id) return;
                renderItemRow({ id: it.id, code: it.code, name: it.name }, it.qty || 1);
            });
        } catch (_) {}
    }

    /* ── Auto-fokus scan (termasuk mobile) ── */
    function focusScan() {
        if (!scanInput) return;
        try { scanInput.focus({ preventScroll: true }); } catch (_) { scanInput.focus(); }
        scanInput.select?.();
    }
    function refocusOnEmpty(e) {
        if (e.target.closest('input, textarea, select, button, a, label, .qty-stepper')) return;
        focusScan();
    }
    document.addEventListener('click', refocusOnEmpty);
    document.addEventListener('touchend', refocusOnEmpty, { passive: true });
    const isTouch = window.matchMedia('(hover: none)').matches || 'ontouchstart' in window;
    if (isTouch) {
        const firstFocus = () => {
            focusScan();
            document.removeEventListener('touchstart', firstFocus);
            document.removeEventListener('pointerdown', firstFocus);
        };
        document.addEventListener('touchstart', firstFocus, { once: true, passive: true });
        document.addEventListener('pointerdown', firstFocus, { once: true });
    }

    form.addEventListener('submit', (e) => {
        // buang baris qty <= 0
        dataRows().forEach(tr => {
            if (num(tr.querySelector('.line-qty')?.value) <= 0) tr.remove();
        });
        renumber(); recalcTotal(); toggleEmpty();

        if (dataRows().length === 0) {
            e.preventDefault();
            alert('Scan / tambahkan minimal 1 barang dengan jumlah label > 0.');
            focusScan();
        }
    });

    // init
    restoreState();
    recalcTotal();
    toggleEmpty();
    setTimeout(focusScan, 60);
})();
</script>
@endsection
