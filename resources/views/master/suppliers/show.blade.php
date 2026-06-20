@extends('layouts.app')

@section('title', 'Supplier • ' . $supplier->name)

@push('head')
<style>
    .gf-master-page {
        max-width: 1100px;
        margin: 0 auto;
        padding: 16px 12px 40px;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .gf-master-head {
        display: flex;
        justify-content: space-between;
        align-items: stretch;
        gap: 14px;
        margin-bottom: 14px;
        padding: 18px;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 58%, #f1f5f9 100%);
        box-shadow: 0 16px 42px rgba(15,23,42,.07);
    }

    .gf-master-head-left {
        display: flex;
        align-items: center;
        gap: 13px;
        min-width: 0;
    }

    .gf-master-icon {
        width: 48px; height: 48px; flex: 0 0 48px;
        border-radius: 17px;
        display: inline-flex; align-items: center; justify-content: center;
        color: #ffffff;
        background: linear-gradient(135deg, #0f172a, #334155);
        box-shadow: 0 14px 28px rgba(15,23,42,.18);
        font-size: 1.22rem;
    }

    .gf-master-eyebrow {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #f1f5f9; border: 1px solid #e2e8f0;
        color: #334155; font-size: .72rem; font-weight: 900;
        margin-bottom: 7px;
    }

    .gf-master-title {
        color: #0f172a; font-size: 1.28rem; font-weight: 950;
        letter-spacing: -.05em; line-height: 1.15; margin: 0;
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }

    .gf-master-subtitle {
        color: #64748b; font-size: .84rem; font-weight: 600; margin-top: 4px;
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }

    .gf-master-actions {
        display: flex; flex-wrap: wrap; gap: 8px;
        align-items: center; justify-content: flex-end;
    }

    .gf-master-card {
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        background: #ffffff;
        box-shadow: 0 8px 28px rgba(15,23,42,.06);
        overflow: hidden;
        margin-bottom: 12px;
    }

    .gf-card-header {
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px; flex-wrap: wrap;
        padding: 14px 18px 0;
    }

    .gf-card-title {
        font-size: .72rem; font-weight: 900; color: #64748b;
        text-transform: uppercase; letter-spacing: .06em;
    }

    .gf-card-body { padding: 14px 18px 18px; }

    /* Form */
    .gf-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .gf-label {
        font-size: .72rem; font-weight: 900; color: #334155;
        margin-bottom: 5px; text-transform: uppercase; letter-spacing: .045em;
        display: block;
    }

    .gf-field,
    .gf-form-grid .form-control,
    .gf-form-grid .form-select,
    .gf-form-row .form-control,
    .gf-form-row .form-select {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        font-size: .84rem; font-weight: 600;
        background: #ffffff;
        box-shadow: none;
        color: #0f172a;
    }

    .gf-field:focus,
    .gf-form-grid .form-control:focus,
    .gf-form-grid .form-select:focus,
    .gf-form-row .form-control:focus,
    .gf-form-row .form-select:focus {
        border-color: #94a3b8;
        box-shadow: 0 0 0 .2rem rgba(15,23,42,.08);
    }

    /* Buttons */
    .gf-btn {
        border-radius: 999px; font-weight: 850; letter-spacing: -.01em;
        min-height: 34px; display: inline-flex; align-items: center;
        justify-content: center; gap: 6px; text-decoration: none;
    }

    .gf-btn-primary {
        color: #ffffff !important;
        background: linear-gradient(135deg, #0f172a, #334155) !important;
        border-color: transparent !important;
        box-shadow: 0 8px 20px rgba(15,23,42,.14);
    }

    .gf-btn-soft {
        color: #475569 !important;
        background: rgba(255,255,255,.78) !important;
        border: 1px solid #cbd5e1 !important;
    }

    /* Table */
    .gf-table-wrap {
        border: 1px solid #eef2f7;
        border-radius: 14px;
        overflow: hidden;
    }

    .gf-clean-table {
        font-size: .82rem; color: #0f172a; margin: 0;
    }

    .gf-clean-table thead th {
        background: #f8fafc;
        color: #64748b; font-size: .69rem;
        text-transform: uppercase; letter-spacing: .045em;
        font-weight: 900; border-bottom: 1px solid #e2e8f0;
        padding: 10px 12px; white-space: nowrap;
        border-top: none;
    }

    .gf-clean-table tbody td {
        border-color: #eef2f7;
        padding: 10px 12px; vertical-align: middle;
    }

    .gf-clean-table tbody tr:hover { background: #f8fbff; }

    /* Chips & badges */
    .gf-code {
        display: inline-flex; align-items: center;
        border-radius: 999px; padding: 4px 9px;
        background: #f1f5f9; color: #334155;
        border: 1px solid #e2e8f0;
        font-size: .72rem; font-weight: 900;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    .gf-badge {
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 999px; padding: 4px 10px;
        font-size: .7rem; font-weight: 850; border: 1px solid transparent;
    }

    .gf-badge-green  { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
    .gf-badge-red    { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
    .gf-badge-blue   { background: rgba(37,99,235,.1); color: #2563eb; border-color: rgba(37,99,235,.2); }
    .gf-badge-teal   { background: rgba(16,185,129,.1); color: #059669; border-color: rgba(16,185,129,.2); }
    .gf-badge-slate  { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }

    /* Empty state */
    .gf-empty-state {
        text-align: center; color: #64748b;
        padding: 32px 16px;
        border: 1px dashed #cbd5e1;
        border-radius: 14px; background: #f8fafc;
        font-size: .82rem;
    }

    /* Section divider label */
    .gf-section-label {
        font-size: .69rem; font-weight: 900; color: #94a3b8;
        text-transform: uppercase; letter-spacing: .06em;
        margin-bottom: 10px;
    }

    .gf-type-chip-material { background: rgba(37,99,235,.1); color: #2563eb; border-color: rgba(37,99,235,.2); }
    .gf-type-chip-finished { background: rgba(16,185,129,.1); color: #059669; border-color: rgba(16,185,129,.2); }

    /* form-row for add items / bank */
    .gf-form-row {
        display: grid;
        gap: 10px;
        align-items: end;
    }

    .gf-form-row-items  { grid-template-columns: 1fr 160px 100px; }
    .gf-form-row-banks  { grid-template-columns: 130px 1fr 1fr 140px 100px; }

    @media (max-width: 900px) {
        .gf-form-row-items { grid-template-columns: 1fr 130px 90px; }
        .gf-form-row-banks { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 640px) {
        .gf-master-head    { flex-direction: column; }
        .gf-master-actions { justify-content: flex-start; }
        .gf-form-grid      { grid-template-columns: 1fr; }
        .gf-form-row-items,
        .gf-form-row-banks { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
@php
    $csrf = csrf_token();
    $poTypes  = $supplier->po_types ?? [];
    $poLabels = ['material' => 'Bahan Baku', 'finished_good' => 'Barang Jadi', 'packing' => 'Packing'];
@endphp

<div class="gf-master-page">

    {{-- ── HEADER ── --}}
    <div class="gf-master-head">
        <div class="gf-master-head-left">
            <div class="gf-master-icon"><i class="bi bi-truck"></i></div>
            <div>
                <div class="gf-master-eyebrow">
                    <i class="bi bi-stars"></i> Master Data / Suppliers
                </div>
                <div class="gf-master-title">
                    {{ $supplier->name }}
                    <span class="gf-code" style="font-size:.7rem;">{{ $supplier->code }}</span>
                    @if ((int)$supplier->active === 1)
                        <span class="gf-badge gf-badge-green">Aktif</span>
                    @else
                        <span class="gf-badge gf-badge-red">Nonaktif</span>
                    @endif
                </div>
                <div class="gf-master-subtitle">
                    @if ($supplier->phone)
                        <span><i class="bi bi-telephone" style="font-size:.8rem;"></i> {{ $supplier->phone }}</span>
                    @endif
                    @if (!empty($poTypes))
                        @foreach ($poTypes as $pt)
                            <span class="gf-badge {{ $pt === 'material' ? 'gf-badge-blue' : ($pt === 'packing' ? 'gf-badge-slate' : 'gf-badge-teal') }}" style="font-size:.68rem;">
                                {{ $poLabels[$pt] ?? $pt }}
                            </span>
                        @endforeach
                    @else
                        <span class="gf-badge gf-badge-slate" style="font-size:.68rem;">Semua Jenis PO</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="gf-master-actions">
            <a href="{{ route('master.suppliers.index') }}" class="btn btn-sm gf-btn gf-btn-soft">
                ← Kembali
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success py-2 px-3 mb-3" style="font-size:.82rem; border-radius:14px;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.82rem; border-radius:14px;">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- ── EDIT FORM ── --}}
    <div class="gf-master-card">
        <div class="gf-card-header">
            <div class="gf-card-title">Info Supplier</div>
        </div>
        <div class="gf-card-body">
            <form method="POST" action="{{ route('master.suppliers.update', $supplier) }}">
                @csrf
                @method('PUT')
                <div class="gf-form-grid">
                    <div>
                        <label class="gf-label">Kode</label>
                        <input name="code" value="{{ old('code', $supplier->code) }}"
                            class="form-control form-control-sm" required>
                    </div>
                    <div>
                        <label class="gf-label">Nama Supplier</label>
                        <input name="name" value="{{ old('name', $supplier->name) }}"
                            class="form-control form-control-sm" required>
                    </div>
                    <div>
                        <label class="gf-label">No. Telepon</label>
                        <input name="phone" value="{{ old('phone', $supplier->phone) }}"
                            class="form-control form-control-sm" placeholder="Contoh: 08123...">
                    </div>
                    <div>
                        <label class="gf-label">Email</label>
                        <input name="email" type="email" value="{{ old('email', $supplier->email) }}"
                            class="form-control form-control-sm" placeholder="email@domain.com">
                    </div>
                    <div>
                        <label class="gf-label">Status</label>
                        <select name="active" class="form-select form-select-sm">
                            <option value="1" @selected(old('active', (string)(int)$supplier->active) == '1')>Aktif</option>
                            <option value="0" @selected(old('active', (string)(int)$supplier->active) == '0')>Nonaktif</option>
                        </select>
                    </div>
                    <div>
                        <label class="gf-label">Jenis PO</label>
                        <div class="d-flex flex-wrap gap-3 pt-1">
                            @foreach (['material' => 'Bahan Baku', 'finished_good' => 'Barang Jadi', 'packing' => 'Packing'] as $val => $lbl)
                            @php $chk = in_array($val, old('po_types', $supplier->po_types ?? []), true); @endphp
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                    name="po_types[]" value="{{ $val }}" id="pt_{{ $val }}"
                                    {{ $chk ? 'checked' : '' }}>
                                <label class="form-check-label" style="font-size:.84rem; font-weight:600;" for="pt_{{ $val }}">{{ $lbl }}</label>
                            </div>
                            @endforeach
                        </div>
                        <div style="font-size:.7rem; color:#94a3b8; margin-top:.3rem;">Kosong = semua jenis PO</div>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label class="gf-label">Alamat</label>
                        <textarea name="address" rows="2" class="form-control form-control-sm"
                            placeholder="Alamat lengkap supplier...">{{ old('address', $supplier->address) }}</textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <button class="btn btn-sm gf-btn gf-btn-primary" type="submit">
                        <i class="bi bi-check-lg"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── REKENING BANK ── --}}
    <div class="gf-master-card">
        <div class="gf-card-header">
            <div class="gf-card-title">Rekening Bank</div>
            <div id="bankHint" style="font-size:.75rem; color:#94a3b8; font-weight:700;"></div>
        </div>
        <div class="gf-card-body">
            <div class="gf-form-row gf-form-row-banks mb-3">
                <div>
                    <label class="gf-label">Bank</label>
                    <select id="bankName" class="form-select form-select-sm">
                        <option value="">— Pilih —</option>
                        @foreach (\App\Models\SupplierBankAccount::bankOptions() as $val => $lbl)
                            <option value="{{ $val }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="gf-label">No. Rekening</label>
                    <input id="bankNumber" class="form-control form-control-sm" placeholder="Contoh: 1234567890">
                </div>
                <div>
                    <label class="gf-label">Atas Nama</label>
                    <input id="bankHolder" class="form-control form-control-sm" placeholder="Nama pemilik rekening">
                </div>
                <div>
                    <label class="gf-label">Keterangan</label>
                    <input id="bankNotes" class="form-control form-control-sm" placeholder="Opsional">
                </div>
                <div style="padding-top:1.4rem;">
                    <button id="btnAddBank" class="btn btn-sm gf-btn gf-btn-primary w-100" type="button">
                        + Tambah
                    </button>
                </div>
            </div>
            <div id="bankContainer">
                <div style="color:#94a3b8; font-size:.82rem;">Memuat...</div>
            </div>
        </div>
    </div>

    {{-- ── ITEM MAPPING ── --}}
    <div class="gf-master-card">
        <div class="gf-card-header">
            <div class="gf-card-title">Item yang Dijual</div>
            <div id="mapHint" style="font-size:.75rem; color:#94a3b8; font-weight:700;"></div>
        </div>
        <div class="gf-card-body">
            <div class="gf-form-row gf-form-row-items mb-3" style="grid-template-columns:{{ $canSeeMoney ? '1fr 160px 100px' : '1fr 100px' }};">
                <div>
                    <label class="gf-label">Cari Item</label>
                    <x-item-suggest
                        id-name="attach_item_id"
                        placeholder="Ketik kode / nama item..."
                        :show-name="true"
                        :show-category="true"
                        :min-chars="1"
                        :max-results="8"
                    />
                </div>
                @if ($canSeeMoney)
                    <div>
                        <label class="gf-label">Harga Terakhir (Rp)</label>
                        <input id="map_last_price" class="form-control form-control-sm" inputmode="decimal" placeholder="0">
                    </div>
                @endif
                <div style="padding-top:1.4rem;">
                    <button id="btnAttach" class="btn btn-sm gf-btn gf-btn-primary w-100" type="button" disabled>
                        Tambah
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <input id="filterText" class="form-control form-control-sm"
                    style="border-radius:12px;"
                    placeholder="Filter item (code / nama)..." />
            </div>

            <div id="itemsContainer">
                <div style="color:#94a3b8; font-size:.82rem;">Memuat...</div>
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    const csrf = @json($csrf);
    const canSeeMoney = @json($canSeeMoney);

    const urlJson      = @json(route('master.suppliers.items.json',   $supplier));
    const urlAttach    = @json(route('master.suppliers.items.attach',  $supplier));
    const urlUpdateTpl = @json(route('master.suppliers.items.update', [$supplier, 0]));
    const urlDetachTpl = @json(route('master.suppliers.items.detach', [$supplier, 0]));

    const elContainer = document.getElementById('itemsContainer');
    const elHint      = document.getElementById('mapHint');
    const elFilter    = document.getElementById('filterText');
    const elMapPrice  = document.getElementById('map_last_price');
    const btnAttach   = document.getElementById('btnAttach');

    // hidden input diisi oleh komponen item-suggest
    const elHiddenId   = document.querySelector('[name="attach_item_id"]');
    const elSuggestTxt = document.querySelector('.js-item-suggest-input');

    function onItemSelected() { btnAttach.disabled = !elHiddenId.value; }
    elHiddenId.addEventListener('change', onItemSelected);
    elHiddenId.addEventListener('input',  onItemSelected);

    let cached = null;

    function escapeHtml(s) {
        return String(s ?? '')
            .replaceAll('&','&amp;').replaceAll('<','&lt;')
            .replaceAll('>','&gt;').replaceAll('"','&quot;')
            .replaceAll("'",'&#039;');
    }

    async function fetchJson() {
        const res = await fetch(urlJson, { headers:{'Accept':'application/json'}, credentials:'same-origin' });
        if (!res.ok) throw new Error('Failed');
        return res.json();
    }

    function buildTable(data, q) {
        const labels = data.labels || {};
        const groups = data.groups || {};
        const order  = ['material','accessory','finished_good','other'];
        const keys   = order.filter(k => groups[k]?.length)
            .concat(Object.keys(groups).filter(k => !order.includes(k) && groups[k]?.length));

        if (!keys.length) {
            return `<div class="gf-empty-state">Belum ada mapping item untuk supplier ini.</div>`;
        }

        const query = (q||'').trim().toLowerCase();
        let html = '';

        keys.forEach(type => {
            const rows = (groups[type]||[]).filter(it => {
                if (!query) return true;
                return (it.code+' '+it.name).toLowerCase().includes(query);
            });
            if (!rows.length) return;

            html += `
                <div class="mb-3">
                  <div class="gf-section-label">${escapeHtml(labels[type]||type)} <span style="color:#cbd5e1;">(${rows.length})</span></div>
                  <div class="gf-table-wrap">
                  <table class="table gf-clean-table">
                    <thead><tr>
                      <th style="width:22%">Kode</th>
                      <th>Nama Item</th>
                      ${canSeeMoney ? '<th style="width:18%">Harga (Rp)</th>' : ''}
                      <th style="width:8%">Satuan</th>
                      <th style="width:8%"></th>
                    </tr></thead>
                    <tbody>`;

            rows.forEach(it => {
                const updateUrl = urlUpdateTpl.replace(/\/0$/,'/'+it.id);
                const detachUrl = urlDetachTpl.replace(/\/0$/,'/'+it.id);
                html += `
                    <tr>
                      <td><span class="gf-code">${escapeHtml(it.code)}</span></td>
                      <td style="font-weight:700; color:#0f172a;">${escapeHtml(it.name)}</td>
                      ${canSeeMoney ? `<td>
                        <input class="form-control form-control-sm js-price"
                               style="border-radius:10px; font-weight:700; font-size:.82rem;"
                               inputmode="decimal"
                               value="${escapeHtml(it.last_price??0)}"
                               data-update-url="${escapeHtml(updateUrl)}"
                               placeholder="0"/>
                      </td>` : ''}
                      <td style="color:#64748b; font-size:.78rem;">${escapeHtml(it.unit||'pcs')}</td>
                      <td class="text-end">
                        <button class="btn btn-sm js-detach"
                                style="border-radius:999px; font-size:.72rem; font-weight:800;
                                       border:1px solid #fca5a5; color:#dc2626; background:transparent; padding:3px 10px;"
                                data-detach-url="${escapeHtml(detachUrl)}" type="button">Hapus</button>
                      </td>
                    </tr>`;
            });

            html += `</tbody></table></div></div>`;
        });

        return html || `<div class="gf-empty-state">Tidak ada hasil untuk filter ini.</div>`;
    }

    async function reload() {
        elContainer.innerHTML = `<div style="color:#94a3b8;font-size:.82rem;">Memuat...</div>`;
        try {
            cached = await fetchJson();
            elHint.textContent = cached.count ? `${cached.count} item` : '';
            elContainer.innerHTML = buildTable(cached, elFilter.value);
            bindRowEvents();
        } catch {
            elContainer.innerHTML = `<div class="text-danger" style="font-size:.82rem;">Gagal memuat item.</div>`;
        }
    }

    function bindRowEvents() {
        elContainer.querySelectorAll('.js-price').forEach(input => {
            input.addEventListener('blur', async () => {
                const num = Number(String(input.value||'0').replaceAll('.','').replaceAll(',','.'));
                if (Number.isNaN(num) || num < 0) return;
                try {
                    const r = await fetch(input.dataset.updateUrl, {
                        method:'PUT',
                        headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},
                        credentials:'same-origin',
                        body: JSON.stringify({last_price:num}),
                    });
                    if (!r.ok) throw new Error();
                    await reload();
                } catch { alert('Gagal update harga.'); }
            });
        });

        elContainer.querySelectorAll('.js-detach').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Hapus mapping item dari supplier?')) return;
                try {
                    const r = await fetch(btn.dataset.detachUrl, {
                        method:'DELETE',
                        headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf},
                        credentials:'same-origin',
                    });
                    if (!r.ok) throw new Error();
                    await reload();
                } catch { alert('Gagal hapus mapping.'); }
            });
        });
    }

    btnAttach.addEventListener('click', async () => {
        const itemId = elHiddenId?.value;
        if (!itemId) return alert('Pilih item dari hasil suggest.');
        const num = canSeeMoney
            ? Number(String(elMapPrice?.value||'0').replaceAll('.','').replaceAll(',','.'))
            : 0;
        if (Number.isNaN(num) || num < 0) return alert('Harga tidak valid.');

        btnAttach.disabled = true;
        try {
            const r = await fetch(urlAttach, {
                method:'POST',
                headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},
                credentials:'same-origin',
                body: JSON.stringify({item_id:Number(itemId), last_price:num}),
            });
            if (!r.ok) throw new Error();
            if (elHiddenId)   elHiddenId.value   = '';
            if (elSuggestTxt) elSuggestTxt.value = '';
            if (elMapPrice) elMapPrice.value = '';
            await reload();
        } catch {
            alert('Gagal tambah mapping.');
            btnAttach.disabled = false;
        } finally { btnAttach.disabled = !elHiddenId?.value; }
    });

    elFilter.addEventListener('input', () => {
        if (!cached) return;
        elContainer.innerHTML = buildTable(cached, elFilter.value);
        bindRowEvents();
    });

    reload();

    // ===== BANK ACCOUNTS =====
    (function () {
        const urlBankIndex  = @json(route('master.suppliers.bank_accounts.index',   $supplier));
        const urlBankStore  = @json(route('master.suppliers.bank_accounts.store',   $supplier));
        const urlBankDelTpl = @json(route('master.suppliers.bank_accounts.destroy', [$supplier, 0]));

        const elBC   = document.getElementById('bankContainer');
        const elBH   = document.getElementById('bankHint');
        const eName  = document.getElementById('bankName');
        const eNum   = document.getElementById('bankNumber');
        const eHold  = document.getElementById('bankHolder');
        const eNotes = document.getElementById('bankNotes');
        const btnAdd = document.getElementById('btnAddBank');

        const BANK_LABELS = @json(\App\Models\SupplierBankAccount::bankOptions());

        async function loadBanks() {
            try {
                const res = await fetch(urlBankIndex, { headers:{'Accept':'application/json'}, credentials:'same-origin' });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                renderBanks(data.accounts || []);
                elBH.textContent = data.count ? `${data.count} rekening` : '';
            } catch (e) {
                elBC.innerHTML = `<div class="text-danger" style="font-size:.82rem;">Gagal memuat rekening (${e.message}). Pastikan <code>php artisan migrate</code> sudah dijalankan.</div>`;
            }
        }

        function renderBanks(accounts) {
            if (!accounts.length) {
                elBC.innerHTML = `<div class="gf-empty-state">Belum ada rekening bank tercatat.</div>`;
                return;
            }
            const rows = accounts.map(a => {
                const delUrl = urlBankDelTpl.replace(/\/0$/,'/'+a.id);
                const label  = escapeHtml(BANK_LABELS[a.bank_name] || a.bank_name);
                return `<tr>
                    <td><span class="gf-badge gf-badge-blue">${label}</span></td>
                    <td style="font-family:ui-monospace,monospace; font-weight:800; font-size:.82rem;">${escapeHtml(a.account_number)}</td>
                    <td style="font-weight:700;">${escapeHtml(a.account_holder)}</td>
                    <td style="color:#64748b; font-size:.78rem;">${escapeHtml(a.notes||'—')}</td>
                    <td class="text-end">
                        <button class="btn btn-sm js-bank-del"
                                style="border-radius:999px; font-size:.72rem; font-weight:800;
                                       border:1px solid #fca5a5; color:#dc2626; background:transparent; padding:3px 10px;"
                                data-del-url="${escapeHtml(delUrl)}" type="button">Hapus</button>
                    </td>
                </tr>`;
            }).join('');

            elBC.innerHTML = `
                <div class="gf-table-wrap">
                <table class="table gf-clean-table">
                    <thead><tr>
                        <th style="width:13%">Bank</th>
                        <th style="width:24%">No. Rekening</th>
                        <th style="width:28%">Atas Nama</th>
                        <th>Keterangan</th>
                        <th style="width:9%"></th>
                    </tr></thead>
                    <tbody>${rows}</tbody>
                </table></div>`;

            elBC.querySelectorAll('.js-bank-del').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (!confirm('Hapus rekening ini?')) return;
                    try {
                        const r = await fetch(btn.dataset.delUrl, {
                            method:'DELETE',
                            headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf},
                            credentials:'same-origin',
                        });
                        if (!r.ok) throw new Error();
                        await loadBanks();
                    } catch { alert('Gagal hapus rekening.'); }
                });
            });
        }

        btnAdd.addEventListener('click', async () => {
            const bank_name      = eName.value.trim();
            const account_number = eNum.value.trim();
            const account_holder = eHold.value.trim();
            const notes          = eNotes.value.trim();

            if (!bank_name)      return alert('Pilih nama bank.');
            if (!account_number) return alert('Isi nomor rekening.');
            if (!account_holder) return alert('Isi nama pemilik rekening.');

            btnAdd.disabled = true;
            try {
                const r = await fetch(urlBankStore, {
                    method:'POST',
                    headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},
                    credentials:'same-origin',
                    body: JSON.stringify({bank_name, account_number, account_holder, notes}),
                });
                if (!r.ok) throw new Error();
                eName.value = eNum.value = eHold.value = eNotes.value = '';
                await loadBanks();
            } catch { alert('Gagal menyimpan rekening.'); }
            finally { btnAdd.disabled = false; }
        });

        loadBanks();
    })();
})();
</script>
@endsection
