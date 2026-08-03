@extends('layouts.app')
@section('title', (($mode ?? 'edit') === 'edit') ? 'Marketplace • Edit Promosi' : 'Marketplace • Buat Promosi')

@include('marketplace._shared')

@push('head')
<style>
    .promo-edit-wrap{
        max-width: 1240px;
        margin-inline: auto;
        padding: .9rem .9rem 4rem;
    }
    .promo-edit-hero{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap: .75rem;
        flex-wrap:wrap;
        padding: .85rem .95rem;
        border: 1px solid var(--shp-border);
        border-radius: 16px;
        background: var(--card, #fff);
        box-shadow: 0 10px 24px rgba(15,23,42,.04);
        margin-bottom: .9rem;
    }
    body[data-theme="dark"] .promo-edit-hero{
        background: rgba(15,23,42,.98);
        border-color: rgba(51,65,85,.88);
    }
    .promo-edit-title{
        margin: 0;
        font-size: 1.08rem;
        font-weight: 800;
        letter-spacing: -.02em;
    }
    .promo-edit-sub{
        color: #64748b;
        font-size: .79rem;
        margin-top: .15rem;
    }
    body[data-theme="dark"] .promo-edit-sub{ color: #9ca3af; }
    .promo-edit-actions{
        display:flex;
        gap:.5rem;
        flex-wrap:wrap;
        align-items:center;
    }
    .promo-edit-grid{
        display:grid;
        grid-template-columns: minmax(0, 360px) minmax(0, 1fr);
        gap: .9rem;
        align-items:start;
    }
    .promo-edit-card{
        border:1px solid var(--shp-border);
        border-radius: 14px;
        background: var(--card, #fff);
        overflow:hidden;
        box-shadow:none;
    }
    body[data-theme="dark"] .promo-edit-card{ border-color: rgba(51,65,85,.88); }
    .promo-edit-card-head{
        padding: .8rem .95rem .68rem;
        border-bottom:1px solid rgba(148,163,184,.16);
        background: rgba(148,163,184,.04);
    }
    body[data-theme="dark"] .promo-edit-card-head{
        background: rgba(15,23,42,.72);
        border-bottom-color: rgba(51,65,85,.84);
    }
    .promo-edit-card-body{ padding: .9rem .95rem .95rem; }
    .promo-edit-stats{
        display:grid;
        gap: .5rem;
    }
    .promo-edit-stat{
        border:1px solid rgba(148,163,184,.18);
        border-radius: 12px;
        padding: .6rem .75rem;
        background: rgba(148,163,184,.04);
    }
    .promo-edit-stat .lbl{
        color:#94a3b8;
        font-size:.64rem;
        text-transform:uppercase;
        letter-spacing:.04em;
    }
    .promo-edit-stat .val{
        margin-top:.1rem;
        font-weight:800;
        font-size:.83rem;
    }
    .promo-edit-badge{
        display:inline-flex;
        align-items:center;
        gap:.3rem;
        border-radius:999px;
        padding:.15rem .5rem;
        font-size:.65rem;
        font-weight:800;
        white-space:nowrap;
    }
    .promo-edit-live{
        background: rgba(22,163,74,.10);
        color:#15803d;
        border:1px solid rgba(22,163,74,.22);
    }
    .promo-edit-cached{
        background: rgba(2,132,199,.10);
        color:#0369a1;
        border:1px solid rgba(2,132,199,.22);
    }
    .promo-edit-notice{
        border:1px dashed rgba(148,163,184,.28);
        border-radius: 12px;
        padding: .65rem .75rem;
        background: rgba(148,163,184,.03);
        color:#475569;
        font-size:.75rem;
        line-height:1.45;
        margin-bottom: .9rem;
    }
    body[data-theme="dark"] .promo-edit-notice{
        color:#cbd5e1;
        background: rgba(15,23,42,.45);
        border-color: rgba(51,65,85,.75);
    }
    .promo-builder-item{
        border:1px solid rgba(148,163,184,.18);
        border-radius: 12px;
        padding: .85rem;
        background: rgba(148,163,184,.03);
    }
    body[data-theme="dark"] .promo-builder-item{
        background: rgba(15,23,42,.88);
        border-color: rgba(51,65,85,.88);
    }
    .promo-builder-item + .promo-builder-item{ margin-top:.15rem; }
    .promo-builder-item-head{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:.75rem;
        flex-wrap:wrap;
        margin-bottom:.75rem;
    }
    .promo-builder-model{
        border:1px solid rgba(148,163,184,.12);
        border-radius:10px;
        padding:.75rem;
        background:rgba(255,255,255,.78);
    }
    .promo-builder-item > summary{
        list-style:none;
        cursor:pointer;
    }
    .promo-builder-item > summary::-webkit-details-marker{
        display:none;
    }
    body[data-theme="dark"] .promo-builder-model{
        border-color:rgba(51,65,85,.72);
        background:rgba(2,6,23,.35);
    }
    .promo-builder-model.is-inactive{ opacity:.62; }
    .promo-builder-model + .promo-builder-model{ margin-top:.6rem; }
    .promo-builder-identity{
        display:grid;
        gap:.25rem;
    }
    .promo-builder-identity-lines{
        display:grid;
        gap:.12rem;
        padding:.2rem 0 .05rem;
    }
    .promo-builder-identity-lines .promo-muted{
        line-height:1.35;
    }
    .promo-builder-stat{
        display:inline-flex;
        align-items:baseline;
        gap:.35rem;
        padding:.18rem .5rem;
        border-radius:999px;
        border:1px solid rgba(148,163,184,.24);
        background:rgba(148,163,184,.06);
        font-size:.7rem;
        color:#475569;
    }
    body[data-theme="dark"] .promo-builder-stat{
        border-color:rgba(71,85,105,.75);
        background:rgba(15,23,42,.9);
        color:#cbd5e1;
    }
    .promo-builder-stat b{ font-size:.78rem; color:var(--shp-accent); }
    body[data-theme="dark"] .promo-builder-stat b{ color:#e2e8f0; }
    .promo-builder-stats{ display:flex; flex-wrap:wrap; gap:.35rem; }
    #promoStart,
    #promoEnd{
        background:#fff;
    }
    body[data-theme="dark"] #promoStart,
    body[data-theme="dark"] #promoEnd{
        background:rgba(15,23,42,.98);
    }
    .promo-product-title{
        display:block;
        max-width:320px;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
        font-weight:700;
    }
    .promo-muted{ color:#64748b; font-size:.72rem; }
    body[data-theme="dark"] .promo-muted{ color:#9ca3af; }
    .promo-json{
        background:#0f172a;
        color:#e2e8f0;
        border-radius:8px;
        padding:.75rem;
        font-size:.74rem;
        max-height:380px;
        overflow:auto;
        white-space:pre-wrap;
        word-break:break-word;
    }
    body[data-theme="dark"] .promo-json{ background:#020617; }
    @media (max-width: 980px){
        .promo-edit-grid{ grid-template-columns: 1fr; }
    }
    @media (max-width: 768px){
        .promo-edit-wrap{ padding:.6rem .6rem 4rem; }
    }
</style>
@endpush

@section('content')
<div class="promo-edit-wrap">
    <div class="promo-edit-hero">
        <div>
            <h1 class="promo-edit-title">{{ (($mode ?? 'edit') === 'edit') ? 'Edit Promosi' : 'Buat Promosi' }}</h1>
            <div class="promo-edit-sub" id="pageSubtitle">{{ (($mode ?? 'edit') === 'edit') ? 'Memuat detail promosi...' : 'Siapkan promosi baru untuk toko terpilih.' }}</div>
        </div>
        <div class="promo-edit-actions">
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="refreshPromotionDetail()">
                <i class="bi bi-arrow-repeat me-1"></i>{{ (($mode ?? 'edit') === 'edit') ? 'Refresh detail' : 'Reset draft' }}
            </button>
            <button type="button" class="btn btn-primary" id="saveBtn" onclick="savePromotion()">
                <i class="bi bi-save me-1"></i>{{ (($mode ?? 'edit') === 'edit') ? 'Simpan Perubahan' : 'Simpan Promosi' }}
            </button>
        </div>
    </div>

    <div id="pageAlert" class="alert alert-info d-none"></div>

    <div class="promo-edit-grid">
        <div class="promo-edit-card">
            <div class="promo-edit-card-head">
                <div class="fw-semibold">Ringkasan</div>
                <div class="promo-muted">{{ (($mode ?? 'edit') === 'edit') ? 'Data promo yang sedang diedit.' : 'Data draft promosi baru.' }}</div>
            </div>
            <div class="promo-edit-card-body">
                <div class="promo-edit-stats">
                    <div class="promo-edit-stat">
                        <div class="lbl">Store</div>
                        <div class="val" id="detailStore">—</div>
                    </div>
                    <div class="promo-edit-stat">
                        <div class="lbl">Campaign ID</div>
                        <div class="val" id="detailId">—</div>
                    </div>
                    <div class="promo-edit-stat">
                        <div class="lbl">Status</div>
                        <div class="val" id="detailStatus">—</div>
                    </div>
                    <div class="promo-edit-stat">
                        <div class="lbl">Periode</div>
                        <div class="val" id="detailPeriod">—</div>
                    </div>
                    <div class="promo-edit-stat">
                        <div class="lbl">Items</div>
                        <div class="val" id="detailItems">—</div>
                    </div>
                    <div class="promo-edit-stat">
                        <div class="lbl">Cache</div>
                        <div class="val">
                            <span id="detailCacheBadge" class="promo-edit-badge promo-edit-live">Live</span>
                            <span id="detailCacheTime" class="promo-muted d-none mt-1"></span>
                        </div>
                    </div>
                </div>

                <div class="promo-edit-notice mt-3">
                    {{ (($mode ?? 'edit') === 'edit')
                        ? 'Halaman ini khusus untuk edit promo. Variant bisa kamu aktifkan atau nonaktifkan per baris, dan yang nonaktif tidak ikut terkirim saat disimpan.'
                        : 'Halaman ini khusus untuk membuat promo baru. Variant bisa kamu aktifkan atau nonaktifkan per baris, dan yang nonaktif tidak ikut terkirim saat disimpan.' }}
                </div>

                <details>
                    <summary class="fw-semibold" style="cursor:pointer">Raw JSON</summary>
                    <pre class="promo-json mt-2" id="detailRaw">{}</pre>
                </details>
            </div>
        </div>

        <div class="promo-edit-card">
                <div class="promo-edit-card-head">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div>
                            <div class="fw-semibold">Builder Item / Model</div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addBuilderItem()">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Item
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="importPromoJson()">
                            <i class="bi bi-arrow-down-up me-1"></i>Import JSON
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearBuilder()">
                            <i class="bi bi-trash3 me-1"></i>Bersihkan
                        </button>
                    </div>
                </div>
                <div id="builderStats" class="promo-builder-stats mt-2"></div>
            </div>
            <div class="promo-edit-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Diskon</label>
                        <input type="text" id="promoName" class="form-control" placeholder="GFID Flash Sale Januari">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mulai</label>
                        <input type="text" id="promoStart" class="form-control" data-gf-date="1" autocomplete="off" placeholder="Pilih tanggal & jam">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Selesai</label>
                        <input type="text" id="promoEnd" class="form-control" data-gf-date="1" autocomplete="off" placeholder="Pilih tanggal & jam">
                    </div>
                </div>

                <div class="mt-3 border rounded-3 p-3" style="background:rgba(148,163,184,.04);">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div>
                            <div class="fw-semibold">Visual Item / Model Builder</div>
                            <div class="promo-muted">Tiap model bisa diaktifkan atau dimatikan sendiri.</div>
                        </div>
                    </div>

                    <div id="builderList" class="d-grid gap-3 mt-3"></div>

                    <details class="mt-3">
                        <summary class="fw-semibold" style="cursor:pointer">JSON preview / advanced import</summary>
                        <div class="promo-muted mt-2 mb-2">Preview payload otomatis mengikuti model yang aktif.</div>
                        <textarea id="promoItems" class="form-control font-monospace" rows="10" placeholder='[
  {
    "item_id": 123456789,
    "model_list": [
      { "model_id": 0, "model_promotion_price": 99000 }
    ]
  }
]'></textarea>
                    </details>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const store = @json($store);
    const discountId = @json($discountId);
    const pageMode = @json($mode ?? 'edit');
    const isCreateMode = pageMode !== 'edit';
    const { api, esc } = window.mpHelpers;

    const state = {
        detail: null,
        builderItems: [],
    };

    const refs = {
        pageAlert: document.getElementById('pageAlert'),
        pageSubtitle: document.getElementById('pageSubtitle'),
        detailStore: document.getElementById('detailStore'),
        detailId: document.getElementById('detailId'),
        detailStatus: document.getElementById('detailStatus'),
        detailPeriod: document.getElementById('detailPeriod'),
        detailItems: document.getElementById('detailItems'),
        detailCacheBadge: document.getElementById('detailCacheBadge'),
        detailCacheTime: document.getElementById('detailCacheTime'),
        detailRaw: document.getElementById('detailRaw'),
        promoName: document.getElementById('promoName'),
        promoStart: document.getElementById('promoStart'),
        promoEnd: document.getElementById('promoEnd'),
        promoItems: document.getElementById('promoItems'),
        builderStats: document.getElementById('builderStats'),
        builderList: document.getElementById('builderList'),
        saveBtn: document.getElementById('saveBtn'),
    };

    let promoStartPicker = null;
    let promoEndPicker = null;

    function initPromoPickers() {
        if (window.GFID?.initDate) {
            promoStartPicker = window.GFID.initDate(refs.promoStart, {
                enableTime: true,
                time_24hr: true,
                dateFormat: 'Y-m-d H:i',
                altFormat: 'j M Y, H:i',
            });
            promoEndPicker = window.GFID.initDate(refs.promoEnd, {
                enableTime: true,
                time_24hr: true,
                dateFormat: 'Y-m-d H:i',
                altFormat: 'j M Y, H:i',
            });
        }
    }

    function setPickerValue(picker, value) {
        if (!picker) return;
        if (!value) {
            picker.clear();
            return;
        }

        const date = value instanceof Date ? value : new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            picker.clear();
            return;
        }

        picker.setDate(date, false);
    }

    function setStartTimeLocked(locked) {
        if (!refs.promoStart) return;

        refs.promoStart.disabled = Boolean(locked);
        refs.promoStart.readOnly = Boolean(locked);
        refs.promoStart.setAttribute('aria-disabled', locked ? 'true' : 'false');
    }

    function combineIdentity(id, name) {
        const cleanId = textValue(id).trim();
        const cleanName = textValue(name).trim();

        if (!cleanId && !cleanName) {
            return '';
        }

        return cleanName ? `${cleanId} | ${cleanName}` : cleanId;
    }

    function splitIdentity(value) {
        const raw = textValue(value).trim();
        if (!raw) {
            return { id: '', name: '' };
        }

        const match = raw.match(/^\s*(\d+)\s*(?:[|•\-]\s*(.*))?$/);
        if (match) {
            return {
                id: match[1] || '',
                name: textValue(match[2]).trim(),
            };
        }

        return {
            id: raw,
            name: '',
        };
    }

    function selectAllInput(input) {
        if (!input) return;
        window.requestAnimationFrame(() => {
            if (typeof input.select === 'function') {
                input.select();
            }
        });
    }

    function toast(message, type = 'success') {
        const el = document.createElement('div');
        el.className = `alert alert-${type === 'error' ? 'danger' : type} shadow`;
        el.style.position = 'fixed';
        el.style.right = '16px';
        el.style.bottom = '16px';
        el.style.zIndex = '9999';
        el.style.maxWidth = '420px';
        el.style.margin = '0';
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transition = 'opacity .25s ease';
            setTimeout(() => el.remove(), 250);
        }, 2600);
    }

    function setAlert(message, type = 'info') {
        refs.pageAlert.className = `alert alert-${type === 'error' ? 'danger' : type}`;
        refs.pageAlert.textContent = message;
        refs.pageAlert.classList.remove('d-none');
    }

    function clearAlert() {
        refs.pageAlert.classList.add('d-none');
        refs.pageAlert.textContent = '';
    }

    function fmtTs(ts) {
        if (!ts) return '—';
        const d = new Date(Number(ts) * 1000);
        return d.toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function toLocalInput(ts) {
        if (!ts) return '';
        const d = new Date(Number(ts) * 1000 - (new Date().getTimezoneOffset() * 60000));
        return d.toISOString().slice(0, 16);
    }

    function formatCacheTime(value) {
        if (!value) return '';
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return '';
        return d.toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function setDetailCacheMeta(isCached, cachedAt = null) {
        refs.detailCacheBadge.classList.remove('promo-edit-live', 'promo-edit-cached');
        refs.detailCacheBadge.classList.add(isCached ? 'promo-edit-cached' : 'promo-edit-live');
        refs.detailCacheBadge.textContent = isCached ? 'Cached' : 'Live';

        const timeText = formatCacheTime(cachedAt);
        if (timeText) {
            refs.detailCacheTime.textContent = timeText;
            refs.detailCacheTime.classList.remove('d-none');
        } else {
            refs.detailCacheTime.textContent = '';
            refs.detailCacheTime.classList.add('d-none');
        }
    }

    function fmtMoney(value) {
        if (value === null || typeof value === 'undefined' || value === '') return '—';
        const num = Number(value);
        if (Number.isNaN(num)) return '—';
        return num.toLocaleString('id-ID');
    }

    function fromLocalInput(value) {
        if (!value) return null;
        const parsed = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) return null;
        return Math.floor(parsed.getTime() / 1000);
    }

    function statusClass(status) {
        const key = String(status || '').toLowerCase();
        if (key === 'ongoing') return 'promo-ongoing';
        if (key === 'upcoming') return 'promo-upcoming';
        if (key === 'ended') return 'promo-ended';
        if (key === 'suspended') return 'promo-suspended';
        return 'promo-ended';
    }

    function textValue(value) {
        return value === null || typeof value === 'undefined' ? '' : String(value);
    }

    function parseBoolean(value, defaultValue = true) {
        if (value === null || typeof value === 'undefined' || value === '') return defaultValue;
        if (typeof value === 'boolean') return value;
        if (typeof value === 'number') return value !== 0;
        const normalized = String(value).trim().toLowerCase();
        if (['0', 'false', 'off', 'no', 'nonaktif', 'inactive'].includes(normalized)) return false;
        if (['1', 'true', 'on', 'yes', 'aktif', 'active'].includes(normalized)) return true;
        return defaultValue;
    }

    function isBuilderModelActive(model) {
        return parseBoolean(model?.active, true);
    }

    function toNumberOrNull(value) {
        if (value === null || typeof value === 'undefined' || value === '') {
            return null;
        }

        const normalized = String(value).replace(/,/g, '').trim();
        if (!normalized) {
            return null;
        }

        const num = Number(normalized);
        return Number.isFinite(num) ? num : null;
    }

    function calculatePromoPercentage(originalPrice, promoPrice) {
        const original = toNumberOrNull(originalPrice);
        const promo = toNumberOrNull(promoPrice);

        if (!original || !promo || original <= 0 || promo <= 0 || promo >= original) {
            return '';
        }

        return String(Math.max(0, Math.round(((original - promo) / original) * 100)));
    }

    function syncBuilderModelPercentage(item, model) {
        const percentage = calculatePromoPercentage(
            model?.model_original_price ?? item?.item_original_price ?? null,
            model?.model_promotion_price ?? null
        );

        if (percentage) {
            model.model_promotion_percentage = percentage;
            return;
        }

        if (!textValue(model?.model_promotion_price).trim()) {
            model.model_promotion_percentage = '';
        }
    }

    function normalizeForPayload(items) {
        return (items || []).map((item) => ({
            item_id: Number(item.item_id || 0),
            model_list: (item.model_list || [])
                .filter((model) => isBuilderModelActive(model))
                .map((model) => ({
                    model_id: Number(model.model_id || 0),
                    model_promotion_price: model.model_promotion_price === '' || model.model_promotion_price === null || typeof model.model_promotion_price === 'undefined'
                        ? null
                        : Number(model.model_promotion_price),
                    model_promotion_percentage: model.model_promotion_percentage === '' || model.model_promotion_percentage === null || typeof model.model_promotion_percentage === 'undefined'
                        ? null
                        : Number(model.model_promotion_percentage),
                })),
        })).filter((item) => item.item_id > 0);
    }

    function setBuilderItemIdentity(index, value) {
        if (!state.builderItems[index]) return;
        const identity = splitIdentity(value);
        state.builderItems[index].item_id = identity.id;
        state.builderItems[index].item_name = identity.name;
        syncBuilderPreview();
    }

    function setBuilderModelIdentity(itemIndex, modelIndex, value) {
        const item = state.builderItems[itemIndex];
        if (!item || !item.model_list || !item.model_list[modelIndex]) return;
        const identity = splitIdentity(value);
        item.model_list[modelIndex].model_id = identity.id;
        item.model_list[modelIndex].model_name = identity.name;
        syncBuilderPreview();
    }

    function parseItemList(value) {
        if (!value || !value.trim()) return [];
        const parsed = JSON.parse(value);
        if (!Array.isArray(parsed)) throw new Error('Item JSON harus berupa array.');
        return normalizeForPayload(parsed);
    }

    function newBuilderModel(model = {}) {
        const originalPrice = model.model_original_price ?? null;
        const promoPrice = textValue(model.model_promotion_price);
        const existingPercentage = textValue(model.model_promotion_percentage);

        return {
            model_id: textValue(model.model_id),
            model_name: textValue(model.model_name),
            model_original_price: textValue(originalPrice),
            model_promotion_price: promoPrice,
            model_promotion_percentage: existingPercentage || calculatePromoPercentage(originalPrice, promoPrice),
            active: isBuilderModelActive(model),
        };
    }

    function newBuilderItem(item = {}) {
        const models = Array.isArray(item.model_list) && item.model_list.length ? item.model_list : [{}];
        const originalPrice = item.item_original_price ?? null;
        return {
            item_id: textValue(item.item_id),
            item_name: textValue(item.item_name),
            item_original_price: textValue(originalPrice),
            model_list: models.map((model) => newBuilderModel({
                ...model,
                model_original_price: model.model_original_price ?? originalPrice,
            })),
        };
    }

    function normalizeBuilderItems(items) {
        const list = Array.isArray(items) ? items : [];
        return list.length ? list.map((item) => newBuilderItem(item)) : [newBuilderItem()];
    }

    function builderToPayload() {
        return (state.builderItems || [])
            .map((item) => {
                const itemId = textValue(item.item_id).trim();
                const models = (item.model_list || [])
                    .filter((model) => isBuilderModelActive(model))
                    .map((model) => {
                        const modelId = textValue(model.model_id).trim();
                        const price = textValue(model.model_promotion_price).trim();
                        const percentage = textValue(model.model_promotion_percentage).trim();

                        if (!price && !percentage) {
                            return null;
                        }

                        return {
                            model_id: modelId,
                            model_promotion_price: price || null,
                            model_promotion_percentage: percentage || null,
                        };
                    })
                    .filter(Boolean);

                if (!itemId || !models.length) {
                    return null;
                }

                return {
                    item_id: itemId,
                    model_list: models,
                };
            })
            .filter(Boolean);
    }

    function renderBuilderStats() {
        const draftItems = (state.builderItems || []).length;
        const draftModels = (state.builderItems || []).reduce((sum, item) => sum + ((item.model_list || []).length || 0), 0);
        const activeModels = (state.builderItems || []).reduce((sum, item) => sum + ((item.model_list || []).filter((model) => isBuilderModelActive(model)).length || 0), 0);
        const payloadItems = builderToPayload();
        const payloadModels = payloadItems.reduce((sum, item) => sum + (item.model_list || []).length, 0);

        refs.builderStats.innerHTML = `
            <span class="promo-builder-stat">Draft item <b>${draftItems.toLocaleString('id-ID')}</b></span>
            <span class="promo-builder-stat">Model draft <b>${draftModels.toLocaleString('id-ID')}</b></span>
            <span class="promo-builder-stat">Model aktif <b>${activeModels.toLocaleString('id-ID')}</b></span>
            <span class="promo-builder-stat">Siap kirim <b>${payloadItems.length.toLocaleString('id-ID')}</b></span>
            <span class="promo-builder-stat">Model valid <b>${payloadModels.toLocaleString('id-ID')}</b></span>
        `;
    }

    function syncBuilderPreview() {
        refs.promoItems.value = JSON.stringify(builderToPayload(), null, 2);
        renderBuilderStats();
    }

    function renderBuilder() {
        if (!Array.isArray(state.builderItems) || state.builderItems.length === 0) {
            state.builderItems = [newBuilderItem()];
        }

        refs.builderList.innerHTML = state.builderItems.map((item, itemIndex) => {
            const models = Array.isArray(item.model_list) && item.model_list.length ? item.model_list : [newBuilderModel()];

            return `
                <details class="promo-builder-item">
                    <summary class="promo-builder-item-head">
                        <div>
                            <div class="fw-semibold text-dark">${esc(textValue(item.item_name) || 'Item belum diisi')}</div>
                            <div class="promo-muted">${esc(textValue(item.item_id) || '—')}</div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap align-items-center">
                            <i class="bi bi-chevron-down"></i>
                        </div>
                    </summary>

                    <div class="pt-2">
                        <div class="d-flex gap-2 flex-wrap mb-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addBuilderModel(${itemIndex})">
                                <i class="bi bi-plus-lg me-1"></i>Tambah Model
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeBuilderItem(${itemIndex})">
                                <i class="bi bi-trash3 me-1"></i>Hapus Item
                            </button>
                        </div>

                        <div class="mt-2">
                        ${models.map((model, modelIndex) => `
                            <div class="promo-builder-model ${isBuilderModelActive(model) ? '' : 'is-inactive'}">
                                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                    <div class="d-flex gap-2 flex-wrap align-items-center">
                                        <span class="badge rounded-pill ${isBuilderModelActive(model) ? 'text-bg-success' : 'text-bg-secondary'}">${isBuilderModelActive(model) ? 'Aktif' : 'Nonaktif'}</span>
                                        <button type="button" class="btn btn-sm ${isBuilderModelActive(model) ? 'btn-outline-warning' : 'btn-outline-success'}" onclick="toggleBuilderModelActive(${itemIndex}, ${modelIndex})">
                                            ${isBuilderModelActive(model) ? 'Nonaktifkan' : 'Aktifkan'}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeBuilderModel(${itemIndex}, ${modelIndex})">
                                            <i class="bi bi-dash-lg me-1"></i>Hapus Model
                                        </button>
                                    </div>
                                </div>
                                <div class="row g-2 ${isBuilderModelActive(model) ? '' : 'opacity-50'}">
                                    <div class="col-12 col-lg-5">
                                        <div class="promo-builder-identity">
                                            <div class="promo-builder-identity-lines">
                                                <div class="fw-semibold text-dark">${esc(textValue(model.model_name) || '—')}</div>
                                                <div class="promo-muted">${esc(textValue(model.variant_sku_label || model.model_sku || model.model_id) || '—')}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-lg-2">
                                        <div class="promo-builder-price-box is-muted">
                                            <div class="lbl">Harga asli</div>
                                            <div class="val">${formatPriceLabel(model.model_original_price ?? itemOriginalPrice)}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-lg-3">
                                        <div class="promo-builder-price-box is-primary">
                                            <div class="lbl">Harga promo</div>
                                            <input type="text" inputmode="decimal" class="form-control form-control-sm" value="${esc(model.model_promotion_price || '')}" placeholder="99000" onfocus="selectAllInput(this)" oninput="setBuilderModelField(${itemIndex}, ${modelIndex}, 'model_promotion_price', this.value)" ${isBuilderModelActive(model) ? '' : 'disabled'}>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-2">
                                        <div class="promo-builder-price-box is-secondary">
                                            <div class="lbl">Promo %</div>
                                            <input id="builderPercentage-${itemIndex}-${modelIndex}" type="text" inputmode="decimal" class="form-control form-control-sm" value="${esc(model.model_promotion_percentage || '')}" readonly tabindex="-1" ${isBuilderModelActive(model) ? '' : 'disabled'}>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-2">
                                        <div class="promo-builder-price-box is-muted">
                                            <div class="lbl">Status</div>
                                            <div class="val">${isBuilderModelActive(model) ? 'Siap promo' : 'Nonaktif'}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    </div>
                </details>
            `;
        }).join('');

        syncBuilderPreview();
    }

    function setBuilderItemField(index, field, value) {
        if (!state.builderItems[index]) return;
        state.builderItems[index][field] = value;
        syncBuilderPreview();
    }

    function setBuilderModelField(itemIndex, modelIndex, field, value) {
        const item = state.builderItems[itemIndex];
        if (!item || !item.model_list || !item.model_list[modelIndex]) return;
        item.model_list[modelIndex][field] = value;
        if (field === 'model_promotion_price') {
            syncBuilderModelPercentage(item, item.model_list[modelIndex]);
            const percentageInput = document.getElementById(`builderPercentage-${itemIndex}-${modelIndex}`);
            if (percentageInput) {
                percentageInput.value = item.model_list[modelIndex].model_promotion_percentage || '';
            }
        }
        syncBuilderPreview();
    }

    function toggleBuilderModelActive(itemIndex, modelIndex) {
        const item = state.builderItems[itemIndex];
        if (!item || !item.model_list || !item.model_list[modelIndex]) return;
        item.model_list[modelIndex].active = !isBuilderModelActive(item.model_list[modelIndex]);
        renderBuilder();
    }

    function addBuilderItem() {
        state.builderItems.push(newBuilderItem());
        renderBuilder();
    }

    function removeBuilderItem(index) {
        if (state.builderItems.length <= 1) {
            state.builderItems = [newBuilderItem()];
        } else {
            state.builderItems.splice(index, 1);
        }
        renderBuilder();
    }

    function addBuilderModel(itemIndex) {
        const item = state.builderItems[itemIndex];
        if (!item) return;
        item.model_list = Array.isArray(item.model_list) ? item.model_list : [];
        item.model_list.push(newBuilderModel());
        renderBuilder();
    }

    function removeBuilderModel(itemIndex, modelIndex) {
        const item = state.builderItems[itemIndex];
        if (!item || !Array.isArray(item.model_list)) return;
        if (item.model_list.length <= 1) {
            item.model_list = [newBuilderModel()];
        } else {
            item.model_list.splice(modelIndex, 1);
        }
        renderBuilder();
    }

    function importPromoJson() {
        try {
            const parsed = parseItemList(refs.promoItems.value);
            state.builderItems = normalizeBuilderItems(parsed);
            renderBuilder();
            toast('Payload JSON berhasil diimpor.');
        } catch (err) {
            toast(err.message || 'JSON promo tidak valid.', 'danger');
        }
    }

    function clearBuilder() {
        state.builderItems = [newBuilderItem()];
        renderBuilder();
    }

    function renderSummary(detail, cached, cachedAt) {
        if (isCreateMode && !detail) {
            refs.pageSubtitle.textContent = `Siapkan promosi baru untuk ${store?.name || 'toko terpilih'}`;
            refs.detailStore.textContent = store ? `${store.name}${store.channel ? ' • ' + store.channel.name : ''}` : '—';
            refs.detailId.textContent = 'Baru';
            refs.detailStatus.innerHTML = `<span class="promo-badge promo-upcoming">Draft</span>`;
            refs.detailPeriod.textContent = 'Belum diatur';
            refs.detailItems.textContent = '0';
            refs.detailRaw.textContent = '{}';
            setDetailCacheMeta(false);
            return;
        }

        refs.pageSubtitle.textContent = detail?.discount_name
            ? `${detail.discount_name} • Campaign #${detail.discount_id || discountId}`
            : `Campaign #${discountId}`;
        refs.detailStore.textContent = detail?.store ? `${detail.store.name}${detail.store.channel ? ' • ' + detail.store.channel.name : ''}` : (store?.name || '—');
        refs.detailId.textContent = detail?.discount_id || discountId;
        refs.detailStatus.innerHTML = `<span class="promo-badge ${statusClass(detail?.discount_status)}">${esc(detail?.status_label || detail?.discount_status || '-')}</span>`;
        refs.detailPeriod.textContent = `${fmtTs(detail?.start_time)} - ${fmtTs(detail?.end_time)}`;
        refs.detailItems.textContent = Number(detail?.item_count || 0).toLocaleString('id-ID');
        refs.detailRaw.textContent = JSON.stringify(detail?.raw || {}, null, 2);
        setDetailCacheMeta(Boolean(cached), cachedAt || null);
    }

    function normalizeItemRows(items) {
        const rows = [];
        (items || []).forEach((item) => {
            const models = Array.isArray(item.model_list) && item.model_list.length ? item.model_list : [{}];
            models.forEach((model) => {
                const promoPercentage = model.model_promotion_percentage !== null && typeof model.model_promotion_percentage !== 'undefined' && model.model_promotion_percentage !== ''
                    ? Number(model.model_promotion_percentage)
                    : (() => {
                        const original = Number(model.model_original_price ?? item.item_original_price ?? 0);
                        const promoPrice = Number(model.model_promotion_price ?? 0);
                        if (!original || !promoPrice || promoPrice >= original) {
                            return null;
                        }
                        return Math.max(0, Math.round(((original - promoPrice) / original) * 100));
                    })();
                const variantLabel = model.model_name || model.variant_sku_label || model.model_sku || model.model_id || '—';
                const variantCode = model.sku_mapping_code || item.sku_mapping_code || '';
                const variantLine = variantCode ? `${variantLabel} • ${variantCode}` : variantLabel;
                const promoStock = Number(model.promo_stock ?? item.promo_stock ?? 0);

                rows.push(`
                    <tr>
                        <td>${esc(String(item.item_id || '-'))}</td>
                        <td>
                            <div class="promo-product-title" title="${esc(item.product_title_label || item.item_name || '—')}">${esc(item.product_title_label || item.item_name || '—')}</div>
                            <div class="promo-muted">${esc(variantLine)}</div>
                        </td>
                        <td class="text-end">${fmtMoney(model.model_original_price ?? item.item_original_price)}</td>
                        <td class="text-end">${promoPercentage !== null ? promoPercentage.toLocaleString('id-ID') + '%' : '—'}</td>
                        <td class="text-end">${fmtMoney(model.model_promotion_price)}</td>
                        <td class="text-end">${promoStock.toLocaleString('id-ID')}</td>
                    </tr>
                `);
            });
        });

        return rows;
    }

    async function loadDetail(forceRefresh = false) {
        clearAlert();
        if (isCreateMode) {
            state.detail = null;
            refs.promoName.value = refs.promoName.value || '';
            setPickerValue(promoStartPicker, '');
            setPickerValue(promoEndPicker, '');
            setStartTimeLocked(false);
            state.builderItems = [newBuilderItem()];
            renderSummary(null, false, null);
            renderBuilder();
            return;
        }

        refs.pageSubtitle.textContent = 'Memuat detail promosi...';

        try {
            const params = forceRefresh ? '?refresh=1' : '';
            const res = await api(`/api/marketplace/promotions/${store.id}/${discountId}${params}`);
            state.detail = res.promotion || null;
            const detail = state.detail || {};

            refs.promoName.value = detail.discount_name || '';
            setPickerValue(promoStartPicker, toLocalInput(detail.start_time));
            setPickerValue(promoEndPicker, toLocalInput(detail.end_time));
            setStartTimeLocked(String(detail.discount_status || '').toLowerCase() === 'ongoing');

            renderSummary(detail, res.cached, res.cached_at);
            state.builderItems = normalizeBuilderItems(detail.items || []);
            renderBuilder();
            refs.detailRaw.textContent = JSON.stringify(detail.raw || res.raw || {}, null, 2);

            const rows = normalizeItemRows(detail.items || []);
            refs.pageSubtitle.textContent = detail?.discount_name
                ? `${detail.discount_name} • Campaign #${detail.discount_id || discountId}`
                : `Campaign #${discountId}`;

            if (!rows.length) {
                setAlert('Campaign ini belum memiliki item yang bisa diedit. Kamu bisa menambahkan dari builder di bawah.', 'warning');
            }
        } catch (err) {
            state.detail = null;
            setAlert(err.message || 'Gagal memuat detail promosi.', 'danger');
            refs.pageSubtitle.textContent = `Campaign #${discountId}`;
            refs.detailRaw.textContent = '{}';
        }
    }

    async function refreshPromotionDetail() {
        await loadDetail(true);
    }

    async function savePromotion() {
        const detail = state.detail;
        const itemList = builderToPayload();
        const payload = {
            discount_name: refs.promoName.value.trim(),
            start_time: fromLocalInput(refs.promoStart.value),
            end_time: fromLocalInput(refs.promoEnd.value),
            item_list: itemList,
        };

        if (!payload.discount_name) {
            toast('Nama promo wajib diisi.', 'warning');
            return;
        }

        if (!payload.start_time || !payload.end_time) {
            toast('Waktu mulai dan selesai wajib diisi.', 'warning');
            return;
        }

        if (payload.end_time <= payload.start_time) {
            toast('Waktu selesai harus lebih besar dari waktu mulai.', 'warning');
            return;
        }

        if (!payload.item_list.length) {
            toast('Tambahkan minimal satu item promo yang lengkap.', 'warning');
            return;
        }

        refs.saveBtn.disabled = true;
        refs.saveBtn.textContent = isCreateMode ? 'Menyimpan...' : 'Menyimpan...';

        try {
            const url = isCreateMode
                ? '/api/marketplace/promotions'
                : `/api/marketplace/promotions/${store.id}/${detail?.discount_id || discountId}/update`;
            const body = isCreateMode
                ? { ...payload, store_id: store.id }
                : { ...payload, store_id: store.id };
            const res = await api(url, {
                method: 'POST',
                body: JSON.stringify(body),
            });

            toast(res.message || 'Promo tersimpan.');
            if (isCreateMode) {
                window.location.href = @json(route('marketplace.promotions')) + `?store_id=${encodeURIComponent(store.id)}`;
                return;
            }
            await loadDetail(true);
        } catch (err) {
            toast(err.message || 'Gagal menyimpan promo.', 'danger');
        } finally {
            refs.saveBtn.disabled = false;
            refs.saveBtn.textContent = isCreateMode ? 'Simpan Promosi' : 'Simpan Perubahan';
        }
    }

    window.addBuilderItem = addBuilderItem;
    window.removeBuilderItem = removeBuilderItem;
    window.addBuilderModel = addBuilderModel;
    window.removeBuilderModel = removeBuilderModel;
    window.setBuilderItemField = setBuilderItemField;
    window.setBuilderItemIdentity = setBuilderItemIdentity;
    window.setBuilderModelField = setBuilderModelField;
    window.setBuilderModelIdentity = setBuilderModelIdentity;
    window.toggleBuilderModelActive = toggleBuilderModelActive;
    window.importPromoJson = importPromoJson;
    window.clearBuilder = clearBuilder;
    window.refreshPromotionDetail = refreshPromotionDetail;
    window.savePromotion = savePromotion;
    window.selectAllInput = selectAllInput;

    document.addEventListener('DOMContentLoaded', async () => {
        try {
            initPromoPickers();
            await loadDetail();
        } catch (err) {
            setAlert(err.message || 'Gagal memuat halaman edit promosi.', 'danger');
        }
    });
})();
</script>
@endpush
