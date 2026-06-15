
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
        grid-template-columns: minmax(220px, 1fr) auto;
        gap: 10px;
        align-items: end;
        margin-bottom: 12px;
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

@section('title', 'Master • Suppliers • Create')

@section('content')
<div class="gf-master-page">
    <div class="gf-master-head">
        <div class="gf-master-head-left">
            <div class="gf-master-icon">
                <i class="bi bi-truck"></i>
            </div>

            <div>
                <div class="gf-master-eyebrow">
                    <i class="bi bi-plus-circle"></i>
                    Master Data
                </div>

                <h1 class="gf-master-title">Tambah Supplier</h1>

                <div class="gf-master-subtitle">
                    Buat supplier baru untuk kebutuhan pembelian dan mapping item.
                </div>
            </div>
        </div>

        <div class="gf-master-actions">
            <a href="{{ route('master.suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="gf-master-card">
        <div class="gf-master-card-body">
            @if ($errors->any())
                <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.82rem;">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('master.suppliers.store') }}" class="gf-form">
                @csrf

                <div class="gf-form-grid">
                    <div>
                        <label class="form-label">Kode Supplier</label>
                        <input name="code" value="{{ old('code') }}" class="form-control" required
                            placeholder="Contoh: SUP001">
                    </div>

                    <div>
                        <label class="form-label">Nama Supplier</label>
                        <input name="name" value="{{ old('name') }}" class="form-control" required
                            placeholder="Nama supplier">
                    </div>

                    <div>
                        <label class="form-label">Phone</label>
                        <input name="phone" value="{{ old('phone') }}" class="form-control"
                            placeholder="Nomor HP / WhatsApp">
                    </div>

                    <div>
                        <label class="form-label">Email</label>
                        <input name="email" value="{{ old('email') }}" class="form-control"
                            placeholder="Email supplier">
                    </div>

                    <div>
                        <label class="form-label">Status</label>
                        <select name="active" class="form-select">
                            <option value="1" @selected(old('active', '1') == '1')>Aktif</option>
                            <option value="0" @selected(old('active') == '0')>Nonaktif</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Jenis PO</label>
                        <div class="d-flex flex-wrap gap-3 mt-1">
                            @foreach (['material' => 'Bahan Baku', 'finished_good' => 'Barang Jadi', 'packing' => 'Packing'] as $val => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                    name="po_types[]" value="{{ $val }}"
                                    id="pt_c_{{ $val }}"
                                    {{ in_array($val, old('po_types', []), true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="pt_c_{{ $val }}">{{ $label }}</label>
                            </div>
                            @endforeach
                        </div>
                        <div class="text-muted" style="font-size:.75rem; margin-top:.25rem;">
                            Kosong = bisa untuk semua jenis PO
                        </div>
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" rows="3" class="form-control"
                            placeholder="Alamat supplier">{{ old('address') }}</textarea>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
                    <a href="{{ route('master.suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
                        Batal
                    </a>

                    <button class="btn btn-primary gf-btn-primary btn-sm" type="submit">
                        <i class="bi bi-check2"></i>
                        Simpan Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
