{{-- resources/views/inventory/rts_direct_receives/create.blade.php --}}
@extends('layouts.app')
@section('title', 'RTS • Buat Dadakan')

@push('head')
    <style>
        :root {
            --r: 14px;
            --b: rgba(148, 163, 184, .22);
            --muted: #6b7280;
            --soft2: rgba(148, 163, 184, .05);
            --accent: #16a34a;
            --ok: #16a34a;
            --rj: #b91c1c;
            --shadow: 0 10px 26px rgba(15, 23, 42, .08), 0 0 0 1px rgba(15, 23, 42, .03);
            --bottom-nav-h: 72px;
            --fab-gap: 12px;
            --fab-bottom: calc(var(--bottom-nav-h) + var(--fab-gap) + env(safe-area-inset-bottom));
            --vv-kbd: 0px;
        }

        .page-wrap { max-width: 980px; margin: 0 auto; padding: 14px 12px 96px; }

        @media(max-width:767.98px) {
            .page-wrap { padding-bottom: calc(var(--bottom-nav-h) + 130px + var(--vv-kbd)); }
            body.keyboard-open .page-wrap { padding-bottom: calc(14rem + var(--vv-kbd)); }
            .modal-dialog { margin: .75rem; }
            .modal-content { border-radius: 16px; }
            .modal-body { max-height: calc(100vh - 210px); overflow: auto; }
        }

        .panel { background: var(--card); border: 1px solid var(--b); border-radius: var(--r); box-shadow: var(--shadow); }
        .panel-h { padding: 12px 14px; border-bottom: 1px solid rgba(148, 163, 184, .12); }
        .panel-b { padding: 12px 14px; }

        .h-title { font-weight: 900; font-size: 1.05rem; margin: 0; }

        .meta { border: 1px solid rgba(148, 163, 184, .18); border-radius: var(--r); padding: 10px; background: var(--soft2); }
        body[data-theme="dark"] .meta { background: rgba(15, 23, 42, .35); }

        .form-label-sm { font-size: .75rem; font-weight: 800; color: var(--muted); }
        .form-control-sm, .form-select-sm { font-size: .88rem; padding: .42rem .55rem; border-radius: 12px; }

        .wh-pill {
            display: inline-flex; align-items: center; gap: .35rem;
            border: 1px solid rgba(148, 163, 184, .25); border-radius: 12px;
            padding: .42rem .55rem; font-weight: 900; font-size: .88rem;
            background: rgba(148, 163, 184, .06); width: 100%;
        }
        body[data-theme="dark"] .wh-pill { background: rgba(15, 23, 42, .25); }
        .wh-pill .arrow { color: var(--muted); }

        .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas; }

        .list { display: grid; gap: .6rem; margin-top: 12px; }
        .mode-box { display: none; }
        .mode-box.active { display: block; }

        .cardx { border: 1px solid rgba(148, 163, 184, .22); border-radius: 16px; background: var(--card); overflow: hidden; }
        .cardx-h { padding: 10px 12px; border-bottom: 1px solid rgba(148, 163, 184, .12); display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; }

        .cardx-left { display: flex; gap: 10px; align-items: flex-start; min-width: 0; flex: 1 1 auto; }
        .cardx-left > div { min-width: 0; flex: 1 1 auto; }

        .chk { width: 18px; height: 18px; border-radius: 6px; cursor: pointer; margin-top: 2px; flex: 0 0 auto; }

        .code { font-weight: 900; letter-spacing: .08em; color: var(--accent); font-size: .98rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }

        .meta-inline { margin-top: .28rem; font-size: .72rem; color: var(--muted); font-weight: 900; display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
        .meta-inline .dot { opacity: .6; }
        .meta-inline .truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 260px; display: inline-block; vertical-align: bottom; }

        @media(max-width:767.98px) { .meta-inline .truncate { max-width: 170px; } }

        .right-metrics { font-size: .78rem; color: var(--muted); font-weight: 900; white-space: nowrap; text-align: right; flex: 0 0 auto; }

        .cardx-b { padding: 10px 12px; display: grid; gap: .55rem; }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: .55rem; }

        .field label { display: block; font-size: .7rem; font-weight: 900; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; margin-bottom: .25rem; }

        .qty { text-align: center !important; font-weight: 900; padding: .55rem .55rem !important; border-radius: 999px; }
        .qty.ok { border: 1px solid rgba(22, 163, 74, .22); background: rgba(22, 163, 74, .05); }
        .qty.rj { border: 1px solid rgba(185, 28, 28, .22); background: rgba(185, 28, 28, .05); }
        .qty:focus { box-shadow: none; }

        .notes { display: none; }
        .notes.is-show { display: block; }
        .notes input { border-radius: 12px; }

        .btn-del { border-radius: 12px; border: 1px solid rgba(239, 68, 68, .35); background: rgba(239, 68, 68, .08); padding: .35rem .6rem; font-weight: 900; cursor: pointer; }
        .btn-del:hover { border-color: rgba(239, 68, 68, .60); background: rgba(239, 68, 68, .12); }

        .fab-wrap { position: fixed; right: 14px; bottom: var(--fab-bottom); z-index: 1090; display: flex; gap: 10px; align-items: center; pointer-events: none; }
        .fab-wrap .btn { pointer-events: auto; border-radius: 999px; font-weight: 900; box-shadow: 0 12px 26px rgba(15, 23, 42, .22), 0 4px 10px rgba(15, 23, 42, .14); }
        .fab-back { width: 46px; padding-left: 0; padding-right: 0; }
        .fab-save { width: auto; padding: .62rem 1.05rem; white-space: nowrap; }

        @media(max-width:767.98px) {
            .fab-wrap { transition: transform .15s ease, opacity .15s ease; transform: translateY(0); opacity: 1; }
            body.keyboard-open .fab-wrap { bottom: calc(var(--fab-bottom) + var(--vv-kbd)); }
            .fab-wrap.is-hidden { opacity: 0; transform: translateY(10px); pointer-events: none; }
            body.keyboard-open .fab-wrap .btn { box-shadow: none; }
        }

        .modal { z-index: 3000 !important; }
        .modal-backdrop { z-index: 2990 !important; }

        .top-actions { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .pill { border: 1px solid rgba(148, 163, 184, .22); background: rgba(148, 163, 184, .06); border-radius: 999px; padding: .35rem .6rem; font-weight: 900; font-size: .78rem; color: var(--muted); }
        .btn-mini { border-radius: 999px; font-weight: 900; padding: .35rem .6rem; }
    </style>
@endpush

@section('content')
@php
    // ✅ default mode: auto_sr (Belum Sewing Return / WIP-SEW)
    $modeOld = old('mode', 'auto_sr');
@endphp

<div class="page-wrap">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <strong>Oops!</strong> Ada error input, cek form di bawah.
            <ul class="mb-0 mt-1" style="font-size:.85rem;">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- HEADER --}}
    <div class="panel mb-2">
        <div class="panel-h">
            <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                <div class="h-title">RTS • Dadakan</div>
                <a href="{{ route('rts.direct-receives.index') }}"
                   class="btn btn-sm btn-outline-success" style="border-radius:999px;">← List</a>
            </div>
        </div>
    </div>

    <div class="panel">
        <form id="direct-receive-form" method="POST" action="{{ route('rts.direct-receives.store') }}" novalidate>
            @csrf

            <div class="panel-b">

                {{-- FILTER / META --}}
                <div class="meta">
                    <div class="row g-2 align-items-end">

                        <div class="col-12 col-md-4">
                            <label class="form-label form-label-sm">Mode</label>
                            <select name="mode" id="modeSelect" class="form-select form-select-sm">
                                <option value="auto_sr" @selected($modeOld === 'auto_sr')>Belum Sewing Return (WIP-SEW → RTS)</option>
                                <option value="normal" @selected($modeOld === 'normal')>Normal (WIP-FIN → RTS)</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label form-label-sm">Tanggal</label>
                            <input type="date" name="date" value="{{ old('date', $date) }}"
                                   class="form-control form-control-sm">
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm">Operator</label>
                            <select name="operator_id" id="operatorSelect" class="form-select form-select-sm">
                                <option value="">- pilih operator -</option>
                                @foreach ($operators as $op)
                                    @php $opRole = strtolower((string)($op->role ?? '')); @endphp
                                    <option value="{{ $op->id }}" data-role="{{ $opRole }}"
                                        @selected(old('operator_id') == $op->id)>
                                        {{ $op->code }} — {{ $op->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label form-label-sm">Perpindahan</label>
                            <div class="wh-pill">
                                <span id="fromLabel" class="mono">{{ $fromWarehouse->code }}</span>
                                <span class="arrow">→</span>
                                <span class="mono">{{ $toWarehouse->code }}</span>
                            </div>
                            {{-- hidden: di-switch oleh JS sesuai mode (JANGAN diubah kontraknya) --}}
                            <input type="hidden" id="fromWarehouseId" name="from_warehouse_id"
                                   value="{{ old('from_warehouse_id', $fromWarehouse->id) }}">
                            <input type="hidden" name="to_warehouse_id" value="{{ $toWarehouse->id }}">
                        </div>

                        {{-- Filter & cari (mode input) --}}
                        <div class="col-6 col-md-3" data-input-only>
                            <label class="form-label form-label-sm">Cari item</label>
                            <input type="text" id="q" class="form-control form-control-sm mono"
                                   placeholder="Kode..." autocomplete="off">
                        </div>

                        <div class="col-12">
                            <label class="form-label form-label-sm">Catatan</label>
                            <input type="text" name="notes" class="form-control form-control-sm"
                                   value="{{ old('notes') }}" placeholder="Opsional">
                        </div>
                    </div>

                    {{-- Summary + aksi (mode auto) --}}
                    <div class="top-actions mt-2" id="top-actions-input">
                        <div class="d-flex gap-2 flex-wrap align-items-center">
                            <span class="pill">Baris: <span class="mono" id="stat-total-rows">0</span></span>
                            <span class="pill">Terisi: <span class="mono" id="stat-picked-rows">0</span></span>
                            <span class="pill">Total OK: <span class="mono" id="stat-total-ok">0,00</span></span>
                            <span class="pill">Total RJ: <span class="mono" id="stat-total-rj">0,00</span></span>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-mini" id="btnReloadAuto">↻ Reload</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-mini" id="btn-uncheck-all">Reset</button>
                        </div>
                    </div>
                </div>

                {{-- ===== MODE NORMAL (manual, WIP-FIN → RTS) ===== --}}
                <div class="list mode-box" id="mode-normal">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-label-sm">Line Items (manual)</div>
                        <button type="button" class="btn btn-sm btn-outline-success btn-mini" id="btnAddNormal">+ Baris</button>
                    </div>
                    <div id="listNormal" class="list" style="margin-top:.4rem;"></div>
                </div>

                {{-- ===== MODE AUTO-SR (WIP-SEW → WIP-FIN → RTS) ===== --}}
                <div class="list mode-box" id="mode-auto">
                    <div id="listAuto" class="list" style="margin-top:0;">
                        <div class="text-center py-4 text-muted">Pilih operator untuk memuat baris.</div>
                    </div>
                </div>

                {{-- FAB --}}
                <div class="fab-wrap" id="fab-wrap">
                    <a href="{{ route('rts.direct-receives.index') }}" class="btn btn-sm btn-outline-secondary fab-back">←</a>
                    <button type="button" class="btn btn-sm btn-success fab-save" id="btn-open-modal" disabled>Simpan</button>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- MODAL CONFIRM --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Simpan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 border bg-light" style="border-radius:14px;">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div style="font-weight:900;">Ringkasan</div>
                        <div class="text-muted" style="font-weight:800;font-size:.86rem;">
                            Baris terisi: <span class="mono" id="m-rows">0</span>
                        </div>
                    </div>
                    <div class="mt-2" style="font-size:.90rem;font-weight:800;color:var(--muted);">
                        Mode: <span class="mono" id="m-mode">-</span><br>
                        Operator: <span class="mono" id="m-op">-</span><br>
                        Perpindahan: <span class="mono" id="m-move">-</span><br>
                        Total OK: <span class="mono" id="m-ok">0,00</span>
                        • Total Reject: <span class="mono" id="m-rj">0,00</span>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div style="font-weight:900;">Detail</div>
                        <div class="text-muted" style="font-weight:800;font-size:.86rem;">
                            Item: <span class="mono" id="m-items-count">0</span>
                        </div>
                    </div>
                    <div class="border" style="border-radius:14px; overflow:hidden;">
                        <div class="px-3 py-2"
                             style="background:rgba(148,163,184,.06); border-bottom:1px solid rgba(148,163,184,.18); font-size:.72rem; font-weight:900; color:var(--muted); text-transform:uppercase; letter-spacing:.10em;">
                            <div class="d-grid" style="grid-template-columns: 44px 1fr 120px 120px; gap:.5rem; align-items:center;">
                                <div>No</div><div>Item</div><div class="text-end">OK</div><div class="text-end">Reject</div>
                            </div>
                        </div>
                        <div id="m-items" style="max-height:40vh; overflow:auto; -webkit-overflow-scrolling:touch;">
                            <div class="text-center text-muted py-3" id="m-empty">Tidak ada item.</div>
                        </div>
                    </div>
                </div>

                <div id="modalErr" class="alert alert-danger mt-3 mb-0 d-none" style="font-size:.9rem;"></div>
                <div class="mt-3 d-none" id="modal-fallback-note">
                    <div class="alert alert-warning mb-0">
                        Bootstrap JS belum ter-load. Tombol <b>Simpan</b> akan submit langsung.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm btn-success" id="btn-confirm-submit">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('direct-receive-form');

    const modeSelect   = document.getElementById('modeSelect');
    const operatorSelect = document.getElementById('operatorSelect');
    const fromLabel    = document.getElementById('fromLabel');
    const fromWarehouseId = document.getElementById('fromWarehouseId');

    const boxNormal = document.getElementById('mode-normal');
    const boxAuto   = document.getElementById('mode-auto');
    const listNormal = document.getElementById('listNormal');
    const listAuto  = document.getElementById('listAuto');

    const q = document.getElementById('q');
    const topActions = document.getElementById('top-actions-input');
    const fabWrap = document.getElementById('fab-wrap');

    const btnAddNormal = document.getElementById('btnAddNormal');
    const btnReloadAuto = document.getElementById('btnReloadAuto');
    const btnUncheckAll = document.getElementById('btn-uncheck-all');
    const btnOpenModal = document.getElementById('btn-open-modal');
    const btnConfirm = document.getElementById('btn-confirm-submit');

    const modalEl = document.getElementById('confirmModal');
    const modalErr = document.getElementById('modalErr');
    const fallbackNote = document.getElementById('modal-fallback-note');

    const mMode = document.getElementById('m-mode');
    const mOp = document.getElementById('m-op');
    const mMove = document.getElementById('m-move');
    const mOk = document.getElementById('m-ok');
    const mRj = document.getElementById('m-rj');
    const mRows = document.getElementById('m-rows');
    const mItemsCount = document.getElementById('m-items-count');
    const mItems = document.getElementById('m-items');
    const mEmpty = document.getElementById('m-empty');

    const statTotalRows = document.getElementById('stat-total-rows');
    const statPickedRows = document.getElementById('stat-picked-rows');
    const statTotalOk = document.getElementById('stat-total-ok');
    const statTotalRj = document.getElementById('stat-total-rj');

    const WIP_FIN_ID   = {{ (int) $fromWarehouse->id }};
    const WIP_FIN_CODE = @json($fromWarehouse->code);
    const WIP_SEW_ID   = {{ (int) $wipSewWarehouse->id }};
    const WIP_SEW_CODE = @json($wipSewWarehouse->code);
    const RTS_CODE     = @json($toWarehouse->code);

    const fetchUrl = @json(route('rts.direct-receives.operator_wip'));
    const items = @json($items->map(fn($i) => ['id' => $i->id, 'code' => $i->code, 'name' => $i->name])->values());

    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
    const body = document.body;

    function esc(s) {
        return String(s ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
    }
    function intSafe(v) { const n = parseInt(String(v ?? '').replace(/[^\d]/g, ''), 10); return isNaN(n) ? 0 : n; }
    function numSafe(v) { const n = parseFloat(String(v ?? '').replace(',', '.')); return (Number.isFinite(n) && n > 0) ? n : 0; }
    function fmt(n) { return Number(n || 0).toLocaleString('id-ID'); }
    function fmt2(n) { return Number(n || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function fmtDate(iso) {
        if (!iso) return '-';
        const d = new Date(String(iso) + 'T00:00:00');
        if (isNaN(d.getTime())) return String(iso);
        return d.toLocaleDateString('id-ID', { weekday: 'short', day: '2-digit', month: 'short' });
    }
    function operatorLabel() {
        const opt = operatorSelect.options[operatorSelect.selectedIndex];
        return (opt && opt.value) ? String(opt.text).trim() : '';
    }

    const isAuto = () => (modeSelect.value || 'auto_sr') === 'auto_sr';

    // ===== visual viewport (keyboard) =====
    (function initVV() {
        if (!window.visualViewport) return;
        const vv = window.visualViewport;
        const set = () => {
            const kbd = Math.max(0, (window.innerHeight - vv.height - vv.offsetTop));
            document.documentElement.style.setProperty('--vv-kbd', `${kbd}px`);
        };
        vv.addEventListener('resize', set); vv.addEventListener('scroll', set); set();
    })();

    function setSectionEnabled(sectionEl, enabled) {
        if (!sectionEl) return;
        sectionEl.querySelectorAll('input, select, textarea, button').forEach(el => {
            el.disabled = !enabled;
        });
    }

    // ✅ mode auto_sr => operator hanya role sewing
    function applyOperatorFilter() {
        const auto = isAuto();
        $$('option', operatorSelect).forEach(opt => {
            if (!opt.value) { opt.hidden = false; opt.disabled = false; return; }
            const role = String(opt.dataset.role || '').toLowerCase();
            if (auto) {
                const ok = role === 'sewing';
                opt.hidden = !ok; opt.disabled = !ok;
                if (!ok && operatorSelect.value === opt.value) operatorSelect.value = '';
            } else { opt.hidden = false; opt.disabled = false; }
        });
    }

    function applyMode() {
        const auto = isAuto();
        boxNormal.classList.toggle('active', !auto);
        boxAuto.classList.toggle('active', auto);
        setSectionEnabled(boxNormal, !auto);
        setSectionEnabled(boxAuto, auto);

        if (auto) { fromLabel.textContent = WIP_SEW_CODE; fromWarehouseId.value = String(WIP_SEW_ID); }
        else { fromLabel.textContent = WIP_FIN_CODE; fromWarehouseId.value = String(WIP_FIN_ID); }

        // top-actions (reload/summary) hanya relevan untuk auto
        topActions.style.display = auto ? '' : 'none';

        applyOperatorFilter();
        if (auto) loadOperatorWip();

        refreshSummary();
    }
    modeSelect.addEventListener('change', applyMode);

    // =========================================================
    // NORMAL MODE — kartu manual
    // =========================================================
    function optsItems(sel) {
        const o = ['<option value="">Pilih item...</option>'];
        for (const it of items) {
            const s = String(sel || '') === String(it.id) ? 'selected' : '';
            o.push(`<option value="${it.id}" ${s}>${esc(it.code)} — ${esc(it.name)}</option>`);
        }
        return o.join('');
    }

    function renumberNormal() {
        $$('.cardx', listNormal).forEach((card, i) => {
            card.querySelector('select').name = `lines[${i}][item_id]`;
            card.querySelector('input.qty').name = `lines[${i}][qty]`;
            card.querySelector('input.note').name = `lines[${i}][notes]`;
            const no = card.querySelector('[data-no]'); if (no) no.textContent = String(i + 1);
        });
    }

    function refreshDisabledNormal() {
        const selects = $$('.cardx select', listNormal);
        const selected = new Set(selects.map(s => s.value).filter(Boolean));
        selects.forEach(s => {
            const cur = s.value;
            $$('option', s).forEach(o => {
                if (!o.value) return;
                o.disabled = (o.value !== cur) && selected.has(o.value);
            });
        });
    }

    function addRowNormal(init = {}) {
        const card = document.createElement('div');
        card.className = 'cardx mono';
        card.innerHTML = `
            <div class="cardx-h">
                <div class="cardx-left">
                    <div style="flex:1 1 auto;">
                        <div class="form-label-sm mb-1">Item <span data-no class="pill">1</span></div>
                        <select class="form-select form-select-sm" required>${optsItems(init.item_id)}</select>
                    </div>
                </div>
                <button type="button" class="btn-del">Hapus</button>
            </div>
            <div class="cardx-b">
                <div class="grid2">
                    <div class="field">
                        <label>Qty</label>
                        <input class="form-control form-control-sm qty ok num-input select-all-on-focus" type="number"
                               step="0.01" min="0" inputmode="decimal" value="${init.qty ?? ''}" placeholder="0">
                    </div>
                    <div class="field">
                        <label>Catatan</label>
                        <input class="form-control form-control-sm note" type="text" value="${esc(init.notes ?? '')}" placeholder="-">
                    </div>
                </div>
            </div>`;
        card.querySelector('.btn-del').addEventListener('click', () => {
            card.remove(); renumberNormal(); refreshDisabledNormal(); refreshSummary();
        });
        card.querySelector('select').addEventListener('change', () => { refreshDisabledNormal(); refreshSummary(); });
        listNormal.appendChild(card);
        renumberNormal(); refreshDisabledNormal();
    }
    btnAddNormal.addEventListener('click', () => addRowNormal({}));

    // =========================================================
    // AUTO-SR MODE — kartu per pickup line (gaya Sewing Return)
    // =========================================================
    function renumberAuto() {
        $$('.cardx[data-row="1"]', listAuto).forEach((card, i) => {
            card.querySelector('input.h_pl').name = `lines[${i}][sewing_pickup_line_id]`;
            card.querySelector('input.h_item').name = `lines[${i}][item_id]`;
            card.querySelector('input.ok').name = `lines[${i}][qty_ok]`;
            card.querySelector('input.rj').name = `lines[${i}][qty_reject]`;
            card.querySelector('input.note').name = `lines[${i}][notes]`;
        });
    }

    function buildAutoCard(r) {
        const remaining = intSafe(r.remaining);
        const wip = intSafe(r.wip_stock);
        const limit = Math.min(remaining, wip);
        const opLabel = operatorLabel();

        const card = document.createElement('div');
        card.className = 'cardx mono';
        card.dataset.row = '1';
        card.dataset.code = String(r.item_code || '').toUpperCase();
        card.dataset.limit = String(limit);

        card.innerHTML = `
            <div class="cardx-h">
                <div class="cardx-left">
                    <input type="checkbox" class="chk row-check" aria-label="Pilih baris">
                    <div>
                        <div class="code" title="${esc(r.item_name || '')}">${esc(r.item_code)}</div>
                        <div class="meta-inline">
                            <span class="dot">•</span>
                            <span>${esc(fmtDate(r.pickup_date))}</span>
                            ${opLabel ? `<span class="dot">•</span><span class="truncate" title="${esc(opLabel)}">OP: ${esc(opLabel)}</span>` : ''}
                        </div>
                    </div>
                </div>
                <div class="right-metrics">BELUM ${fmt2(remaining)}<br>WIP ${fmt2(wip)}</div>
            </div>
            <div class="cardx-b">
                <div class="grid2">
                    <div class="field">
                        <label>Di setor (OK)</label>
                        <input class="form-control form-control-sm qty ok num-input select-all-on-focus" type="number"
                               step="1" min="0" inputmode="numeric" placeholder="0">
                    </div>
                    <div class="field">
                        <label>Reject</label>
                        <input class="form-control form-control-sm qty rj num-input select-all-on-focus" type="number"
                               step="1" min="0" inputmode="numeric" placeholder="0">
                    </div>
                </div>
                <div class="notes">
                    <input class="form-control form-control-sm note" type="text" placeholder="Catatan reject (opsional)">
                </div>
                <input class="h_pl" type="hidden" value="${esc(r.pickup_line_id)}">
                <input class="h_item" type="hidden" value="${esc(r.item_id)}">
            </div>`;

        const okEl = card.querySelector('input.ok');
        const rjEl = card.querySelector('input.rj');
        const cb   = card.querySelector('.row-check');
        const notesWrap = card.querySelector('.notes');

        function clamp(changed) {
            let ok = intSafe(okEl.value), rj = intSafe(rjEl.value);
            if (ok + rj > limit) {
                const diff = (ok + rj) - limit;
                if (changed === 'rj') rj = Math.max(0, rj - diff);
                else ok = Math.max(0, ok - diff);
            }
            okEl.value = ok > 0 ? String(ok) : '';
            rjEl.value = rj > 0 ? String(rj) : '';
            notesWrap.classList.toggle('is-show', rj > 0);
            if (cb) cb.checked = (ok + rj) > 0;
        }
        okEl.addEventListener('input', () => { clamp('ok'); refreshSummary(); });
        rjEl.addEventListener('input', () => { clamp('rj'); refreshSummary(); });

        cb.addEventListener('change', () => {
            if (cb.checked) {
                if (intSafe(okEl.value) + intSafe(rjEl.value) <= 0) { okEl.value = limit > 0 ? String(limit) : ''; clamp('ok'); }
            } else { okEl.value = ''; rjEl.value = ''; notesWrap.classList.remove('is-show'); }
            refreshSummary();
        });

        return card;
    }

    async function loadOperatorWip() {
        if (!isAuto()) return;
        const opId = operatorSelect.value;
        if (!opId) {
            listAuto.innerHTML = `<div class="text-center py-4 text-muted">Pilih operator untuk memuat baris.</div>`;
            refreshSummary(); return;
        }
        listAuto.innerHTML = `<div class="text-center py-4 text-muted">Memuat…</div>`;
        try {
            const res = await fetch(`${fetchUrl}?operator_id=${encodeURIComponent(opId)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const json = await res.json();
            const rows = json.rows || [];
            if (!rows.length) {
                listAuto.innerHTML = `<div class="text-center py-4 text-muted">Tidak ada baris outstanding (stok WIP-SEW kosong).</div>`;
                refreshSummary(); return;
            }
            listAuto.innerHTML = '';
            rows.forEach(r => listAuto.appendChild(buildAutoCard(r)));
            renumberAuto();
        } catch (e) {
            listAuto.innerHTML = `<div class="text-center py-4 text-danger">Gagal memuat data.</div>`;
        }
        refreshSummary();
    }

    operatorSelect.addEventListener('change', () => { if (isAuto()) loadOperatorWip(); });
    btnReloadAuto.addEventListener('click', loadOperatorWip);

    btnUncheckAll.addEventListener('click', () => {
        const list = isAuto() ? listAuto : listNormal;
        $$('.cardx', list).forEach(card => {
            const ok = card.querySelector('input.ok'); const rj = card.querySelector('input.rj');
            const cb = card.querySelector('.row-check'); const nt = card.querySelector('.notes');
            if (ok) ok.value = ''; if (rj) rj.value = ''; if (cb) cb.checked = false;
            if (nt) nt.classList.remove('is-show');
        });
        refreshSummary();
    });

    // ===== filter cari =====
    q.addEventListener('input', () => {
        const up = (q.value || '').toUpperCase();
        if (q.value !== up) q.value = up;
        const list = isAuto() ? listAuto : listNormal;
        $$('.cardx', list).forEach(card => {
            const code = (card.dataset.code || card.querySelector('select option:checked')?.textContent || '').toUpperCase();
            card.style.display = (!up || code.includes(up)) ? '' : 'none';
        });
    });

    // ===== select-all on focus + keyboard class =====
    form.addEventListener('focusin', (e) => {
        const t = e.target;
        if (t?.classList?.contains('select-all-on-focus')) setTimeout(() => { try { t.select(); } catch (_) {} }, 0);
        if (window.innerWidth < 768) body.classList.add('keyboard-open');
    });
    form.addEventListener('focusout', () => body.classList.remove('keyboard-open'));

    // ===== summary + enable simpan =====
    function collectFilled() {
        const out = [];
        if (isAuto()) {
            $$('.cardx[data-row="1"]', listAuto).forEach(card => {
                if (card.style.display === 'none') return;
                const ok = intSafe(card.querySelector('input.ok')?.value);
                const rj = intSafe(card.querySelector('input.rj')?.value);
                if (ok + rj > 0) out.push({ code: card.dataset.code || '', ok, rj });
            });
        } else {
            $$('.cardx', listNormal).forEach(card => {
                if (card.style.display === 'none') return;
                const sel = card.querySelector('select');
                const qty = numSafe(card.querySelector('input.qty')?.value);
                if (sel?.value && qty > 0) {
                    const label = sel.options[sel.selectedIndex]?.textContent || '';
                    out.push({ code: label, ok: qty, rj: 0 });
                }
            });
        }
        return out;
    }

    function refreshSummary() {
        const filled = collectFilled();
        const list = isAuto() ? listAuto : listNormal;
        const totalRows = $$('.cardx', list).filter(c => c.style.display !== 'none').length;
        const okSum = filled.reduce((a, b) => a + b.ok, 0);
        const rjSum = filled.reduce((a, b) => a + b.rj, 0);

        if (statTotalRows) statTotalRows.textContent = fmt(totalRows);
        if (statPickedRows) statPickedRows.textContent = fmt(filled.length);
        if (statTotalOk) statTotalOk.textContent = fmt2(okSum);
        if (statTotalRj) statTotalRj.textContent = fmt2(rjSum);

        if (btnOpenModal) btnOpenModal.disabled = filled.length === 0;
    }

    listNormal.addEventListener('input', refreshSummary);

    // ===== Modal =====
    let bsModal = null;
    const hasBootstrap = (typeof window.bootstrap !== 'undefined' && typeof window.bootstrap.Modal !== 'undefined');
    if (modalEl && hasBootstrap) {
        bsModal = window.bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true, focus: true });
        modalEl.addEventListener('show.bs.modal', () => fabWrap?.classList.add('is-hidden'));
        modalEl.addEventListener('hidden.bs.modal', () => fabWrap?.classList.remove('is-hidden'));
    } else if (fallbackNote) {
        fallbackNote.classList.remove('d-none');
    }

    function rebuildModal() {
        const filled = collectFilled();
        const auto = isAuto();
        const okSum = filled.reduce((a, b) => a + b.ok, 0);
        const rjSum = filled.reduce((a, b) => a + b.rj, 0);

        const opt = operatorSelect.options[operatorSelect.selectedIndex];
        mMode.textContent = auto ? 'Auto-SR (WIP-SEW → RTS)' : 'Normal (WIP-FIN → RTS)';
        mOp.textContent = operatorSelect.value ? (opt ? opt.text : '-') : (auto ? '(wajib pilih)' : 'SEMUA');
        mMove.textContent = (auto ? WIP_SEW_CODE : WIP_FIN_CODE) + ' → ' + RTS_CODE;
        mOk.textContent = fmt2(okSum);
        mRj.textContent = fmt2(rjSum);
        mRows.textContent = fmt(filled.length);
        mItemsCount.textContent = fmt(filled.length);

        mItems.innerHTML = '';
        if (!filled.length) { mItems.appendChild(mEmpty); mEmpty.style.display = ''; return; }
        mEmpty.style.display = 'none';
        filled.forEach((it, i) => {
            const row = document.createElement('div');
            row.className = 'px-3 py-2';
            row.style.borderBottom = '1px solid rgba(148,163,184,.12)';
            row.innerHTML = `
                <div class="d-grid" style="grid-template-columns: 44px 1fr 120px 120px; gap:.5rem; align-items:center;">
                    <div class="text-muted" style="font-weight:900;">${i + 1}</div>
                    <div style="font-weight:900;" class="mono">${esc(it.code)}</div>
                    <div class="text-end mono" style="font-weight:900;">${fmt2(it.ok)}</div>
                    <div class="text-end mono" style="font-weight:900; color: var(--rj);">${fmt2(it.rj)}</div>
                </div>`;
            mItems.appendChild(row);
        });
    }

    function validateBeforeSubmit() {
        if (modalErr) { modalErr.classList.add('d-none'); modalErr.textContent = ''; }
        if (isAuto() && !operatorSelect.value) { return 'Operator wajib dipilih untuk Auto-SR.'; }
        if (collectFilled().length === 0) {
            return isAuto() ? 'Isi minimal 1 baris (OK atau RJ > 0).' : 'Minimal 1 item dengan qty > 0.';
        }
        return null;
    }

    btnOpenModal.addEventListener('click', (e) => {
        if (btnOpenModal.disabled) return;
        e.preventDefault();
        const err = validateBeforeSubmit();
        rebuildModal();
        if (err && modalErr) { modalErr.textContent = err; modalErr.classList.remove('d-none'); }
        if (!bsModal) { if (!err) doSubmit(); return; }
        try { document.activeElement?.blur?.(); } catch (_) {}
        requestAnimationFrame(() => bsModal.show());
    });

    function doSubmit() {
        const err = validateBeforeSubmit();
        if (err) { if (modalErr) { modalErr.textContent = err; modalErr.classList.remove('d-none'); } return; }
        if (isAuto()) renumberAuto(); else renumberNormal();
        if (btnConfirm) { btnConfirm.disabled = true; btnConfirm.textContent = 'Menyimpan…'; }
        form.submit();
    }
    btnConfirm.addEventListener('click', () => { try { bsModal?.hide(); } catch (_) {} doSubmit(); });

    // ===== init =====
    // restore old() normal rows kalau ada error validasi
    @if (old('mode') === 'normal' && is_array(old('lines')))
        @foreach (old('lines') as $ln)
            addRowNormal({ item_id: @json($ln['item_id'] ?? ''), qty: @json($ln['qty'] ?? ''), notes: @json($ln['notes'] ?? '') });
        @endforeach
    @endif
    if (!listNormal.children.length) addRowNormal({});

    applyMode(); // default auto_sr → auto load
});
</script>
@endpush
