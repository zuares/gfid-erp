
@push('head')
<style>
    .gf-master-page {
        max-width: 1180px;
        margin: 0 auto;
        padding: 16px 12px 28px;
        color: #0f172a;
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
        box-shadow: 0 16px 42px rgba(15, 23, 42, .07);
    }

    .gf-master-head-left {
        display: flex;
        align-items: center;
        gap: 13px;
        min-width: 0;
    }

    .gf-master-icon {
        width: 48px;
        height: 48px;
        flex: 0 0 48px;
        border-radius: 17px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        background: linear-gradient(135deg, #0f172a, #334155);
        box-shadow: 0 14px 28px rgba(15, 23, 42, .18);
        font-size: 1.22rem;
    }

    .gf-master-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #334155;
        font-size: .72rem;
        font-weight: 900;
        margin-bottom: 7px;
    }

    .gf-master-title {
        color: #0f172a;
        font-size: 1.34rem;
        font-weight: 950;
        letter-spacing: -.05em;
        line-height: 1.1;
        margin: 0;
    }

    .gf-master-subtitle {
        color: #64748b;
        font-size: .86rem;
        font-weight: 600;
        margin-top: 4px;
    }

    .gf-master-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        justify-content: flex-end;
    }

    .gf-master-card {
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        background: #ffffff;
        box-shadow: 0 16px 42px rgba(15, 23, 42, .07);
        overflow: hidden;
    }

    .gf-master-card-body {
        padding: 14px;
    }

    .gf-kpi-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 12px;
    }

    .gf-kpi-card {
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 14px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 14px 34px rgba(15, 23, 42, .06);
    }

    .gf-kpi-label {
        font-size: .7rem;
        font-weight: 900;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .045em;
    }

    .gf-kpi-value {
        font-size: 1.28rem;
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -.04em;
        margin-top: 5px;
    }

    .gf-kpi-note {
        font-size: .74rem;
        color: #94a3b8;
        margin-top: 2px;
    }

    .gf-filter {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) 180px auto;
        gap: 10px;
        align-items: end;
        margin-bottom: 12px;
    }

    @media (max-width: 640px) {
        .gf-filter { grid-template-columns: 1fr; }
    }

    .gf-label {
        font-size: .72rem;
        font-weight: 900;
        color: #334155;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: .045em;
    }

    .gf-field,
    .gf-filter .form-control,
    .gf-form .form-control,
    .gf-form .form-select {
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        min-height: 38px;
        color: #0f172a;
        font-size: .84rem;
        font-weight: 650;
        background: #ffffff;
        box-shadow: none;
    }

    .gf-field:focus,
    .gf-filter .form-control:focus,
    .gf-form .form-control:focus,
    .gf-form .form-select:focus {
        border-color: #94a3b8;
        box-shadow: 0 0 0 .22rem rgba(15, 23, 42, .08);
    }

    .gf-btn,
    .gf-master-actions .btn,
    .gf-filter .btn,
    .gf-form .btn {
        border-radius: 999px;
        font-weight: 850;
        letter-spacing: -.01em;
        min-height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none;
    }

    .gf-btn-primary,
    .gf-master-actions .btn-primary {
        color: #ffffff !important;
        background: linear-gradient(135deg, #0f172a, #334155) !important;
        border-color: transparent !important;
        box-shadow: 0 12px 24px rgba(15, 23, 42, .12);
    }

    .gf-btn-soft {
        color: #475569 !important;
        background: rgba(255,255,255,.78) !important;
        border: 1px solid #cbd5e1 !important;
    }

    .gf-table-scroll {
        max-height: 68vh;
        overflow: auto;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
    }

    .gf-clean-table {
        font-size: .82rem;
        color: #0f172a;
        margin: 0;
    }

    .gf-clean-table thead th {
        position: sticky;
        top: 0;
        z-index: 8;
        background: #f8fafc;
        color: #64748b;
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .045em;
        font-weight: 900;
        border-bottom: 1px solid #e2e8f0;
        padding: 12px 10px;
        white-space: nowrap;
    }

    .gf-clean-table tbody td {
        border-color: #eef2f7;
        padding: 12px 10px;
        vertical-align: middle;
    }

    .gf-clean-table tbody tr:hover {
        background: #f8fbff;
    }

    .gf-code {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 5px 9px;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        font-size: .73rem;
        font-weight: 900;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    .gf-name {
        font-weight: 850;
        color: #0f172a;
        letter-spacing: -.02em;
    }

    .gf-sub {
        color: #64748b;
        font-size: .74rem;
        margin-top: 2px;
    }

    .gf-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: .72rem;
        font-weight: 850;
        border: 1px solid transparent;
    }

    .gf-badge-green {
        background: #dcfce7;
        color: #166534;
        border-color: #bbf7d0;
    }

    .gf-badge-red {
        background: #fee2e2;
        color: #991b1b;
        border-color: #fecaca;
    }

    .gf-row-actions {
        display: inline-flex;
        gap: 6px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .gf-empty {
        text-align: center;
        color: #64748b;
        padding: 40px 16px;
        border: 1px dashed #cbd5e1;
        border-radius: 18px;
        background: #f8fafc;
    }

    .gf-foot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding-top: 12px;
        color: #64748b;
        font-size: .78rem;
        font-weight: 700;
    }

    .pagination {
        margin: 0;
        gap: 4px;
    }

    .pagination .page-link {
        border-radius: 11px;
        border-color: #e2e8f0;
        color: #475569;
        font-size: .78rem;
        font-weight: 700;
    }

    .pagination .active .page-link,
    .pagination .page-item.active .page-link {
        color: #ffffff;
        background: #0f172a;
        border-color: #0f172a;
    }

    .gf-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .gf-form .form-label {
        font-size: .72rem;
        font-weight: 900;
        color: #334155;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: .045em;
    }

    .gf-live-wrap {
        position: relative;
    }

    .gf-live-indicator {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        display: none;
        color: #334155;
        background: rgba(255,255,255,.88);
        padding-left: 8px;
        font-size: .72rem;
        font-weight: 850;
    }

    .gf-live-indicator.is-show {
        display: inline-flex;
    }

    @media (max-width: 992px) {
        .gf-master-head {
            flex-direction: column;
        }

        .gf-master-actions {
            justify-content: flex-start;
        }

        .gf-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .gf-master-page {
            padding: 12px 10px 24px;
        }

        .gf-master-head {
            padding: 15px;
            border-radius: 20px;
        }

        .gf-master-head-left {
            align-items: flex-start;
        }

        .gf-master-icon {
            width: 42px;
            height: 42px;
            flex-basis: 42px;
            border-radius: 15px;
            font-size: 1.08rem;
        }

        .gf-kpi-grid,
        .gf-filter,
        .gf-form-grid {
            grid-template-columns: 1fr;
        }

        .gf-master-actions,
        .gf-master-actions .btn,
        .gf-filter .btn {
            width: 100%;
        }

        .gf-foot {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
@endpush

@extends('layouts.app')

@section('title', 'Master • Suppliers')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $totalSuppliers = method_exists($suppliers, 'total') ? $suppliers->total() : $suppliers->count();
    $collection = method_exists($suppliers, 'getCollection') ? $suppliers->getCollection() : collect($suppliers);
    $activeCount = $collection->where('active', 1)->count();
    $inactiveCount = $collection->where('active', 0)->count();
    $poType = $poType ?? request('po_type', '');
    $hasFilter = filled($q ?? request('q')) || filled($poType);
@endphp

@section('content')
<div class="gf-master-page">
    <div class="gf-master-head">
        <div class="gf-master-head-left">
            <div class="gf-master-icon">
                <i class="bi bi-truck"></i>
            </div>

            <div>
                <div class="gf-master-eyebrow">
                    <i class="bi bi-stars"></i>
                    Master Data
                </div>

                <h1 class="gf-master-title">Suppliers</h1>

                <div class="gf-master-subtitle">
                    Kelola data supplier, kontak, status aktif, dan mapping item pembelian.
                </div>
            </div>
        </div>

        <div class="gf-master-actions">
            <a href="{{ route('master.suppliers.create') }}" class="btn btn-primary gf-btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i>
                Tambah Supplier
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success py-2 px-3 mb-3" style="font-size:.82rem;">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.82rem;">{{ $errors->first() }}</div>
    @endif

    <div class="gf-kpi-grid">
        <div class="gf-kpi-card">
            <div class="gf-kpi-label">Total Supplier</div>
            <div class="gf-kpi-value">{{ $fmt($totalSuppliers) }}</div>
            <div class="gf-kpi-note">berdasarkan filter aktif</div>
        </div>

        <div class="gf-kpi-card">
            <div class="gf-kpi-label">Aktif</div>
            <div class="gf-kpi-value">{{ $fmt($activeCount) }}</div>
            <div class="gf-kpi-note">di halaman saat ini</div>
        </div>

        <div class="gf-kpi-card">
            <div class="gf-kpi-label">Nonaktif</div>
            <div class="gf-kpi-value">{{ $fmt($inactiveCount) }}</div>
            <div class="gf-kpi-note">perlu dicek ulang</div>
        </div>
    </div>

    <div class="gf-master-card">
        <div class="gf-master-card-body">
            <form class="gf-filter" method="GET" action="{{ route('master.suppliers.index') }}">
                <div>
                    <label class="gf-label">Cari Supplier</label>
                    <input type="search" name="q" value="{{ $q ?? request('q') }}" class="form-control"
                        placeholder="Cari nama, kode, HP, atau email..." autofocus>
                </div>

                <div>
                    <label class="gf-label">Jenis PO</label>
                    <select name="po_type" class="form-select" id="filterPoType">
                        <option value=""             @selected($poType === '')>Semua Jenis</option>
                        <option value="material"     @selected($poType === 'material')>Bahan Baku</option>
                        <option value="finished_good"@selected($poType === 'finished_good')>Barang Jadi</option>
                        <option value="packing"      @selected($poType === 'packing')>Packing</option>
                        <option value="none"         @selected($poType === 'none')>Tanpa Jenis</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-sm" type="submit">
                        <i class="bi bi-funnel"></i>
                        Filter
                    </button>

                    @if ($hasFilter)
                        <a href="{{ route('master.suppliers.index') }}" class="btn btn-outline-light border btn-sm">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            @if ($suppliers->count())
                <div class="gf-table-scroll">
                    <table class="table table-hover align-middle gf-clean-table">
                        <thead>
                            <tr>
                                <th style="width:70px;" class="text-center">No.</th>
                                <th style="width:130px;">Kode</th>
                                <th>Supplier</th>
                                <th style="width:170px;">Kontak</th>
                                <th style="width:160px;">Jenis PO</th>
                                <th style="width:110px;" class="text-center">Status</th>
                                <th style="width:130px;" class="text-end">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($suppliers as $s)
                                <tr>
                                    <td class="text-center text-muted">
                                        {{ method_exists($suppliers, 'firstItem') ? $suppliers->firstItem() + $loop->index : $loop->iteration }}
                                    </td>

                                    <td>
                                        <span class="gf-code">{{ $s->code }}</span>
                                    </td>

                                    <td>
                                        <div class="gf-name">{{ $s->name }}</div>
                                        <div class="gf-sub">
                                            {{ $s->address ?: 'Alamat belum diisi' }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="gf-name">{{ $s->phone ?: '-' }}</div>
                                        @php $banks = $s->bankAccounts ?? collect(); @endphp
                                        @if ($banks->isNotEmpty())
                                            @foreach ($banks as $bk)
                                                <div class="gf-sub" style="display:flex;align-items:center;gap:4px;margin-top:2px;">
                                                    <span style="font-size:.68rem;font-weight:900;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;padding:1px 6px;color:#334155;font-family:ui-monospace,monospace;">{{ $bk->bank_name }}</span>
                                                    <span style="font-size:.74rem;">{{ $bk->account_number }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="gf-sub">—</div>
                                        @endif
                                    </td>

                                    <td>
                                        @php
                                            $poTypes = $s->po_types ?? [];
                                            $labels  = ['material' => 'Bahan Baku', 'finished_good' => 'Barang Jadi', 'packing' => 'Packing'];
                                        @endphp
                                        @if (empty($poTypes))
                                            <span class="text-muted" style="font-size:.78rem;">Semua</span>
                                        @else
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach ($poTypes as $pt)
                                                    <span class="badge rounded-pill"
                                                        style="font-size:.7rem; font-weight:700;
                                                            background:{{ $pt === 'material' ? 'rgba(37,99,235,.12)' : 'rgba(16,185,129,.12)' }};
                                                            color:{{ $pt === 'material' ? '#2563eb' : '#059669' }};">
                                                        {{ $labels[$pt] ?? $pt }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        @if ((int) $s->active === 1)
                                            <span class="gf-badge gf-badge-green">Aktif</span>
                                        @else
                                            <span class="gf-badge gf-badge-red">Nonaktif</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <div class="gf-row-actions">
                                            <a href="{{ route('master.suppliers.show', $s) }}" class="btn btn-outline-primary btn-sm rounded-pill">
                                                Detail
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="gf-empty">
                    <div class="fw-bold text-dark mb-1">Belum ada supplier.</div>
                    <div>Tambah supplier baru untuk mulai mapping item dan harga pembelian.</div>

                    <a href="{{ route('master.suppliers.create') }}" class="btn btn-primary gf-btn-primary btn-sm mt-3">
                        Tambah Supplier
                    </a>
                </div>
            @endif

            <div class="gf-foot">
                <div>
                    @if (method_exists($suppliers, 'firstItem') && $suppliers->total())
                        Menampilkan {{ $suppliers->firstItem() }}–{{ $suppliers->lastItem() }} dari {{ $fmt($suppliers->total()) }} supplier
                    @else
                        Total {{ $fmt($suppliers->count()) }} supplier
                    @endif
                </div>

                <div class="ms-auto">
                    {{ $suppliers->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form.gf-filter');
    if (!form || form.dataset.gfRealtime === '1') return;

    form.dataset.gfRealtime = '1';

    const input = form.querySelector('input[name="q"]');
    const submitBtn = form.querySelector('button[type="submit"]');
    let timer = null;
    let submitting = false;

    if (!input) return;

    let wrap = input.closest('.gf-live-wrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.className = 'gf-live-wrap';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);
    }

    const indicator = document.createElement('span');
    indicator.className = 'gf-live-indicator';
    indicator.textContent = 'filter...';
    wrap.appendChild(indicator);

    function submitLive(delay = 450) {
        clearTimeout(timer);

        timer = setTimeout(function () {
            if (submitting) return;
            submitting = true;

            indicator.classList.add('is-show');

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Filter';
            }

            if (String(input.value || '').trim() === '') {
                input.disabled = true;
            }

            form.requestSubmit ? form.requestSubmit() : form.submit();
        }, delay);
    }

    input.setAttribute('autocomplete', 'off');

    input.addEventListener('input', function () {
        submitLive(450);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            submitLive(0);
        }
    });

    // auto-submit saat dropdown jenis PO berubah
    const poSelect = form.querySelector('#filterPoType');
    if (poSelect) {
        poSelect.addEventListener('change', function () { submitLive(0); });
    }
});
</script>
@endpush
