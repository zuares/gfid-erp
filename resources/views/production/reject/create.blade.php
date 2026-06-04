{{-- resources/views/production/reject/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Perbaiki Reject Jahit')

@push('head')
    <style>
        :root {
            --r: 14px;
            --b: rgba(148, 163, 184, .22);
            --muted: #6b7280;
            --soft2: rgba(148, 163, 184, .05);
            --accent: #b91c1c;
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

        .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas; }

        .dest-badge { display:inline-flex; align-items:center; gap:.35rem; font-weight:900; font-size:.74rem; padding:.2rem .55rem; border-radius:999px; background:rgba(22,163,74,.12); color:#16a34a; border:1px solid rgba(22,163,74,.22); }

        .list { display: grid; gap: .6rem; margin-top: 12px; }

        .cardx { border: 1px solid rgba(148, 163, 184, .22); border-radius: 16px; background: var(--card); overflow: hidden; }
        .cardx-h { padding: 10px 12px; border-bottom: 1px solid rgba(148, 163, 184, .12); display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; }

        .cardx-left { display: flex; gap: 10px; align-items: flex-start; min-width: 0; }
        .cardx-left>div { min-width: 0; }

        .chk { width: 18px; height: 18px; border-radius: 6px; cursor: pointer; margin-top: 2px; flex: 0 0 auto; }

        .code { font-weight: 900; letter-spacing: .08em; color: var(--accent); font-size: .98rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }

        .meta-inline { margin-top: .28rem; font-size: .72rem; color: var(--muted); font-weight: 900; display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
        .meta-inline .dot { opacity: .6; }
        .meta-inline .truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 260px; display: inline-block; vertical-align: bottom; }

        @media(max-width:767.98px) { .meta-inline .truncate { max-width: 150px; } }

        .right-metrics { font-size: .78rem; color: var(--muted); font-weight: 900; white-space: nowrap; text-align: right; flex: 0 0 auto; }
        .right-metrics .rjnum { color: var(--rj); }

        .cardx-b { padding: 10px 12px; display: grid; gap: .55rem; }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: .55rem; align-items:end; }

        .field label { display: block; font-size: .7rem; font-weight: 900; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; margin-bottom: .25rem; }

        .qty { text-align: center !important; font-weight: 900; padding: .55rem .55rem !important; border-radius: 999px; }
        .qty.ok { border: 1px solid rgba(22, 163, 74, .22); background: rgba(22, 163, 74, .05); }
        .qty:focus { box-shadow: none; }

        .reason { font-size:.74rem; color:var(--muted); font-weight:800; }

        .fab-wrap {
            position: fixed; right: 14px; bottom: var(--fab-bottom);
            z-index: 1090; display: flex; gap: 10px; align-items: center; pointer-events: none;
        }
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
    use Carbon\Carbon;

    $rows = $rows ?? collect();
    $operators = $operators ?? collect();
    $selectedOperatorId = (string) ($operatorId ?? '');

    $whPrdLabel = $whPrd ? ($whPrd->code . ' — ' . $whPrd->name) : 'WH-PRD';

    $itemOptions = $rows
        ->map(fn($r) => strtoupper($r->sku ?? ''))
        ->filter()->unique()->sort()->values();
@endphp

<div class="page-wrap">

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <strong>Oops!</strong> Ada error input, cek form di bawah.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="panel mb-2">
        <div class="panel-h">
            <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                <div>
                    <div class="h-title">Perbaiki Reject Jahit</div>
                    <div class="text-muted" style="font-size:.8rem;font-weight:700;">
                        Barang reject jahit yang sudah diperbaiki akan masuk ke
                        <span class="dest-badge"><i class="bi bi-box-seam"></i>{{ $whPrdLabel }}</span>
                    </div>
                </div>

                <a href="{{ route('production.reject.index') }}"
                   class="btn btn-sm btn-outline-danger" style="border-radius:999px;">
                    Daftar Reject
                </a>
            </div>

            {{-- Filter periode (GET, reload) --}}
            <form method="get" id="filter-form" action="{{ route('production.reject.create') }}" class="mt-2">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label form-label-sm">Dari</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label form-label-sm">Sampai</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label form-label-sm">Operator</label>
                        <select name="operator_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua operator</option>
                            @foreach ($operators as $op)
                                <option value="{{ $op->id }}" @selected($selectedOperatorId === (string) $op->id)>
                                    {{ $op->code ? $op->code . ' — ' : '' }}{{ $op->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-grid">
                        <button type="submit" class="btn btn-sm btn-primary" style="border-radius:12px;">Terapkan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <form id="reject-form" action="{{ route('production.reject.store') }}" method="POST" novalidate>
            @csrf
            <input type="hidden" name="destination_warehouse_id" value="{{ $whPrd->id ?? '' }}">

            <div class="panel-b">

                <div class="meta">
                    {{-- Filter klien (instan) --}}
                    <div class="row g-2 align-items-end">
                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm">Filter item</label>
                            <select id="item-filter" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                @foreach ($itemOptions as $code)
                                    <option value="{{ $code }}">{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm">Cari</label>
                            <input type="text" id="q" class="form-control form-control-sm mono" placeholder="SKU / alasan..." autocomplete="off">
                        </div>
                    </div>

                    <div class="top-actions mt-2" id="top-actions-input">
                        <div class="d-flex gap-2 flex-wrap align-items-center">
                            <span class="pill">Kejadian: <span class="mono" id="stat-total-rows">{{ $rows->count() }}</span></span>
                            <span class="pill">Total Reject: <span class="mono">{{ number_format($totalReject, 2, ',', '.') }}</span></span>
                            <span class="pill">Diperbaiki: <span class="mono" id="stat-fixed">0,00</span></span>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-mini" id="btn-fix-all">Perbaiki semua (tampil)</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-mini" id="btn-reset-all">Reset</button>
                        </div>
                    </div>
                </div>

                {{-- ===== LIST REJECT (per kejadian) ===== --}}
                <div class="list" id="list-rows">
                    @if ($rows->isEmpty())
                        <div class="text-center py-4 text-muted">Tidak ada reject jahit pada periode ini.</div>
                    @else
                        @foreach ($rows as $idx => $r)
                            @php
                                $code = strtoupper($r->sku ?? '-');
                                $dateText = $r->date ? Carbon::parse($r->date)->locale('id')->translatedFormat('D, d M') : '-';
                                $opLabel = $r->operator_code !== '-' ? trim($r->operator_code . ' — ' . $r->operator_name) : '';
                                $rjQty = (float) $r->qty_reject;

                                $oldRow = old("results.$idx", []);
                                $fixVal = $oldRow['qty_fixed'] ?? '';
                            @endphp

                            <div class="cardx mono fin-item"
                                 data-code="{{ $code }}"
                                 data-item="{{ $code }}"
                                 data-search="{{ strtolower(trim($r->sku . ' ' . $r->product_name . ' ' . $r->operator_code . ' ' . $r->operator_name . ' ' . $r->reason)) }}"
                                 data-reject="{{ $rjQty }}">
                                <div class="cardx-h">
                                    <div class="cardx-left">
                                        <input type="checkbox" class="chk row-check" aria-label="Pilih baris">
                                        <div>
                                            <div class="code">{{ $code }}</div>
                                            <div class="meta-inline">
                                                <span class="dot">•</span>
                                                <span>{{ $dateText }}</span>
                                                @if ($r->return_code)
                                                    <span class="dot">•</span>
                                                    <span class="truncate" title="{{ $r->return_code }}">{{ $r->return_code }}</span>
                                                @endif
                                                @if ($opLabel !== '')
                                                    <span class="dot">•</span>
                                                    <span class="truncate" title="{{ $opLabel }}">OP: {{ $opLabel }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="right-metrics">
                                        REJECT <span class="rjnum">{{ number_format($rjQty, 2, ',', '.') }}</span>
                                    </div>
                                </div>

                                <div class="cardx-b">
                                    <div class="grid2">
                                        <div class="field">
                                            <label>Qty Diperbaiki → WH-PRD</label>
                                            <input type="number" step="0.01" min="0" inputmode="decimal"
                                                   class="form-control form-control-sm qty ok num-input select-all-on-focus"
                                                   name="results[{{ $idx }}][qty_fixed]"
                                                   value="{{ $fixVal }}" placeholder="0">
                                        </div>
                                        <div class="reason">
                                            <span class="text-uppercase" style="letter-spacing:.06em;">Alasan reject</span><br>
                                            {{ $r->reason }}
                                        </div>
                                    </div>

                                    <input type="hidden" name="results[{{ $idx }}][sewing_return_line_id]" value="{{ $r->line_id }}">
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="text-center text-muted small py-3" id="no-match" style="display:none;">Tidak ada baris yang cocok dengan filter.</div>

                {{-- FAB --}}
                <div class="fab-wrap" id="fab-wrap" style="{{ $rows->isEmpty() ? 'display:none;' : '' }}">
                    <a href="{{ route('production.reject.index') }}" class="btn btn-sm btn-outline-secondary fab-back">←</a>
                    <button type="button" class="btn btn-sm btn-success fab-save" id="btn-open-modal" disabled>
                        Simpan ke WH-PRD
                    </button>
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
                <h5 class="modal-title">Konfirmasi Perbaikan</h5>
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
                        Tujuan: <span class="mono">{{ $whPrdLabel }}</span><br>
                        Total Diperbaiki: <span class="mono" id="m-fixed">0,00</span>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div style="font-weight:900;">Detail</div>
                        <div class="text-muted" style="font-weight:800;font-size:.86rem;">Item: <span class="mono" id="m-items-count">0</span></div>
                    </div>
                    <div class="border" style="border-radius:14px; overflow:hidden;">
                        <div class="px-3 py-2"
                             style="background:rgba(148,163,184,.06); border-bottom:1px solid rgba(148,163,184,.18); font-size:.72rem; font-weight:900; color:var(--muted); text-transform:uppercase; letter-spacing:.10em;">
                            <div class="d-grid" style="grid-template-columns: 44px 1fr 120px; gap:.5rem; align-items:center;">
                                <div>No</div><div>Item</div><div class="text-end">Diperbaiki</div>
                            </div>
                        </div>
                        <div id="m-items" style="max-height:40vh; overflow:auto; -webkit-overflow-scrolling:touch;">
                            <div class="text-center text-muted py-3" id="m-empty">Tidak ada item.</div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-none" id="modal-fallback-note">
                    <div class="alert alert-warning mb-0">Bootstrap JS belum ter-load. Tombol <b>Simpan</b> akan submit langsung.</div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm btn-success" id="btn-confirm-submit">Simpan ke WH-PRD</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('reject-form');
    const listRows = document.getElementById('list-rows');
    const itemFilter = document.getElementById('item-filter');
    const q = document.getElementById('q');
    const noMatch = document.getElementById('no-match');

    const fabWrap = document.getElementById('fab-wrap');
    const btnOpenModal = document.getElementById('btn-open-modal');
    const modalEl = document.getElementById('confirmModal');
    const btnConfirm = document.getElementById('btn-confirm-submit');

    const btnFixAll = document.getElementById('btn-fix-all');
    const btnResetAll = document.getElementById('btn-reset-all');

    const statFixed = document.getElementById('stat-fixed');

    const mFixed = document.getElementById('m-fixed');
    const mRows = document.getElementById('m-rows');
    const mItemsCount = document.getElementById('m-items-count');
    const mItems = document.getElementById('m-items');
    const mEmpty = document.getElementById('m-empty');
    const fallbackNote = document.getElementById('modal-fallback-note');

    const body = document.body;
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
    const fmt2 = (n) => Number(n || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    (function initVV() {
        if (!window.visualViewport) return;
        const vv = window.visualViewport;
        const set = () => {
            const kbd = Math.max(0, (window.innerHeight - vv.height - vv.offsetTop));
            document.documentElement.style.setProperty('--vv-kbd', `${kbd}px`);
        };
        vv.addEventListener('resize', set);
        vv.addEventListener('scroll', set);
        set();
    })();

    function sanitizeNum(v) {
        v = (v ?? '').toString().trim();
        if (v === '') return '';
        const n = parseFloat(v);
        if (Number.isNaN(n) || n < 0) return '';
        return String(n);
    }

    function getEls(card) {
        return {
            fix: card.querySelector('input[name*="[qty_fixed]"]'),
            cb: card.querySelector('.row-check'),
        };
    }

    function clampCard(card) {
        const cap = parseFloat(card.dataset.reject || '0') || 0;
        const { fix } = getEls(card);
        let a = parseFloat(fix?.value || '0'); if (!Number.isFinite(a) || a < 0) a = 0;
        if (a > cap) a = cap;
        if (fix) fix.value = (a <= 0) ? '' : String(a);
    }

    function syncCheck(card) {
        const { fix, cb } = getEls(card);
        const a = parseFloat(fix?.value || '0') || 0;
        if (cb) cb.checked = (a > 0);
    }

    function autoFillCard(card) {
        const cap = parseFloat(card.dataset.reject || '0') || 0;
        const { fix } = getEls(card);
        if (fix) fix.value = cap > 0 ? String(cap) : '';
        syncCheck(card);
    }

    function computeSubmitEnabled() {
        let total = 0;
        $$('.fin-item', listRows).forEach(card => {
            if (card.style.display === 'none') return;
            const { fix } = getEls(card);
            total += parseFloat(fix?.value || '0') || 0;
        });
        if (btnOpenModal) btnOpenModal.disabled = total <= 0;
        if (statFixed) statFixed.textContent = fmt2(total);
        return total;
    }

    function applyFilter() {
        const term = (q?.value || '').toString().trim().toLowerCase();
        const selItem = (itemFilter?.value || '').toString().trim().toUpperCase();
        let visible = 0;
        $$('.fin-item', listRows).forEach(card => {
            const item = (card.dataset.item || '').toString().toUpperCase();
            const hay = (card.dataset.search || '').toString();
            const matchItem = !selItem || item === selItem;
            const matchSearch = !term || hay.includes(term);
            const show = matchItem && matchSearch;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (noMatch) noMatch.style.display = (visible === 0 && $$('.fin-item', listRows).length > 0) ? '' : 'none';
        computeSubmitEnabled();
    }

    form?.addEventListener('input', (e) => {
        const t = e.target;
        if (!t.classList?.contains('num-input')) return;
        t.value = sanitizeNum(t.value);
        const card = t.closest('.fin-item');
        if (!card) return;
        clampCard(card);
        syncCheck(card);
        computeSubmitEnabled();
    });

    form?.addEventListener('change', (e) => {
        const t = e.target;
        if (!t.classList?.contains('row-check')) return;
        const card = t.closest('.fin-item');
        if (!card) return;
        const { fix } = getEls(card);
        if (t.checked) {
            const a = parseFloat(fix?.value || '0') || 0;
            if (a <= 0) autoFillCard(card);
        } else if (fix) {
            fix.value = '';
        }
        computeSubmitEnabled();
    });

    q?.addEventListener('input', applyFilter);
    itemFilter?.addEventListener('change', applyFilter);

    form?.addEventListener('focusin', (e) => {
        const t = e.target;
        if (t?.classList?.contains('select-all-on-focus')) {
            setTimeout(() => { try { t.select(); } catch (_) {} }, 0);
        }
        if (window.innerWidth < 768) body.classList.add('keyboard-open');
    });
    form?.addEventListener('focusout', () => body.classList.remove('keyboard-open'));

    btnFixAll?.addEventListener('click', () => {
        $$('.fin-item', listRows).forEach(card => {
            if (card.style.display === 'none') return;
            autoFillCard(card);
        });
        computeSubmitEnabled();
    });

    btnResetAll?.addEventListener('click', () => {
        $$('.fin-item', listRows).forEach(card => {
            const { cb, fix } = getEls(card);
            if (cb) cb.checked = false;
            if (fix) fix.value = '';
        });
        computeSubmitEnabled();
    });

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

    function esc(s) {
        return String(s ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
    }

    function rebuildModalItems() {
        if (!mItems) return;
        mItems.innerHTML = '';
        let rows = 0, fixedSum = 0;
        const picked = [];
        $$('.fin-item', listRows).forEach(card => {
            const { fix } = getEls(card);
            const a = parseFloat(fix?.value || '0') || 0;
            if (a <= 0) return;
            rows++;
            fixedSum += a;
            picked.push({ code: (card.dataset.code || '').toString(), fix: a });
        });
        picked.sort((x, y) => (x.code || '').localeCompare(y.code || ''));

        if (mRows) mRows.textContent = rows.toLocaleString('id-ID');
        if (mFixed) mFixed.textContent = fmt2(fixedSum);
        if (mItemsCount) mItemsCount.textContent = picked.length.toLocaleString('id-ID');

        if (picked.length === 0) {
            if (mEmpty) { mItems.appendChild(mEmpty); mEmpty.style.display = ''; }
            return;
        }
        if (mEmpty) mEmpty.style.display = 'none';

        picked.forEach((it, i) => {
            const row = document.createElement('div');
            row.className = 'px-3 py-2';
            row.style.borderBottom = '1px solid rgba(148,163,184,.12)';
            row.innerHTML = `
                <div class="d-grid" style="grid-template-columns: 44px 1fr 120px; gap:.5rem; align-items:center;">
                    <div class="text-muted" style="font-weight:900;">${i+1}</div>
                    <div style="font-weight:900;" class="mono">${esc(it.code)}</div>
                    <div class="text-end mono" style="font-weight:900; color: var(--ok);">${fmt2(it.fix)}</div>
                </div>`;
            mItems.appendChild(row);
        });
    }

    btnOpenModal?.addEventListener('click', (e) => {
        if (btnOpenModal.disabled) return;
        e.preventDefault();
        e.stopPropagation();
        rebuildModalItems();
        if (!bsModal) { form.submit(); return; }
        try { document.activeElement?.blur?.(); } catch (_) {}
        requestAnimationFrame(() => { bsModal.show(); });
    });

    btnConfirm?.addEventListener('click', () => {
        try { bsModal?.hide(); } catch (_) {}
        form.submit();
    });

    // init
    $$('.fin-item', listRows).forEach(card => syncCheck(card));
    applyFilter();
    computeSubmitEnabled();
});
</script>
@endpush
