@extends('layouts.app')

@section('title', 'Akuntansi Baru • Daftar Akun')

@php
    $typeTone = [
        'asset' => 'blue',
        'liability' => 'orange',
        'equity' => 'purple',
        'revenue' => 'green',
        'expense' => 'red',
    ];
    $money = fn ($value) => (float) $value > 0 ? 'Rp ' . number_format(abs((float) $value), 0, ',', '.') : '-';
@endphp

@push('head')
    <style>
        .accounts-page { max-width:1280px; margin-inline:auto; }
        .accounts-eyebrow { color:#64748b; font-size:.72rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .accounts-title { color:#0f172a; font-size:clamp(1.35rem, 2vw, 1.75rem); font-weight:800; letter-spacing:-.035em; }
        .accounts-subtitle { color:#64748b; max-width:680px; }
        .new-accounting-btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; min-height:40px; padding:.5rem .9rem; border:1px solid #dbe3ee; border-radius:10px; background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:750; transition:background .15s ease, border-color .15s ease, transform .15s ease; }
        .new-accounting-btn:hover { background:#f8fafc; border-color:#cbd5e1; color:#0f172a; }
        .new-accounting-btn:focus-visible { outline:3px solid rgba(37,99,235,.22); outline-offset:2px; }
        .new-accounting-btn.primary { background:#0f172a; border-color:#0f172a; color:#fff; }
        .new-accounting-btn.primary:hover { background:#1e293b; border-color:#1e293b; color:#fff; }
        .accounts-summary { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:.75rem; }
        .accounts-summary-card { min-height:92px; padding:1rem; border:1px solid #e2e8f0; border-radius:14px; background:#fff; box-shadow:0 5px 16px rgba(15,23,42,.04); }
        .accounts-summary-label { color:#64748b; font-size:.76rem; font-weight:700; }
        .accounts-summary-value { color:#0f172a; font-size:1.45rem; font-weight:800; line-height:1.2; }
        .accounts-summary-hint { color:#94a3b8; font-size:.72rem; }
        .accounts-toolbar { display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; padding:1rem; border:1px solid #e2e8f0; border-radius:14px; background:#fff; }
        .accounts-search { position:relative; flex:1 1 270px; }
        .accounts-search i { position:absolute; top:50%; left:.85rem; color:#94a3b8; transform:translateY(-50%); pointer-events:none; }
        .accounts-search input { padding-left:2.35rem; }
        .accounts-toolbar .form-control, .accounts-toolbar .form-select { min-height:40px; border-color:#dbe3ee; border-radius:10px; font-size:.86rem; }
        .accounts-toolbar .form-select { flex:0 1 160px; }
        .accounts-table-card { overflow:hidden; border:1px solid #e2e8f0; border-radius:16px; background:#fff; box-shadow:0 5px 16px rgba(15,23,42,.04); }
        .accounts-table { margin:0; }
        .accounts-table thead th { padding:.82rem 1rem; border-bottom:1px solid #e2e8f0; color:#64748b; background:#f8fafc; font-size:.69rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase; white-space:nowrap; }
        .accounts-table tbody td { padding:.9rem 1rem; border-color:#eef2f7; color:#334155; vertical-align:middle; }
        .accounts-table tbody tr:last-child td { border-bottom:0; }
        .account-code { color:#0f172a; font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:.82rem; font-weight:800; white-space:nowrap; }
        .account-name { color:#0f172a; font-weight:750; }
        .account-description { display:-webkit-box; max-width:480px; overflow:hidden; color:#64748b; font-size:.78rem; line-height:1.35; -webkit-box-orient:vertical; -webkit-line-clamp:2; }
        .account-meta { color:#94a3b8; font-size:.72rem; }
        .account-badge { display:inline-flex; align-items:center; gap:.3rem; border-radius:999px; padding:.28rem .55rem; font-size:.72rem; font-weight:750; white-space:nowrap; }
        .account-badge.blue { color:#1d4ed8; background:#eff6ff; }
        .account-badge.orange { color:#c2410c; background:#fff7ed; }
        .account-badge.purple { color:#7e22ce; background:#faf5ff; }
        .account-badge.green { color:#15803d; background:#f0fdf4; }
        .account-badge.red { color:#b91c1c; background:#fef2f2; }
        .account-balance { color:#0f172a; font-variant-numeric:tabular-nums; text-align:right; white-space:nowrap; }
        .account-balance-zero { color:#94a3b8; }
        .account-balance-side { display:block; color:#94a3b8; font-size:.68rem; font-weight:650; }
        .account-status { display:inline-flex; align-items:center; gap:.35rem; color:#166534; font-size:.78rem; font-weight:750; white-space:nowrap; }
        .account-status::before { width:7px; height:7px; border-radius:50%; background:#22c55e; content:""; }
        .account-status.inactive { color:#64748b; }
        .account-status.inactive::before { background:#94a3b8; }
        .accounts-empty { padding:3.5rem 1rem; text-align:center; }
        .accounts-empty i { display:block; margin-bottom:.65rem; color:#94a3b8; font-size:2rem; }
        .accounts-modal .modal-content { border:0; border-radius:18px; box-shadow:0 24px 70px rgba(15,23,42,.22); }
        .accounts-modal .modal-header { padding:1.25rem 1.25rem .75rem; border-bottom:0; }
        .accounts-modal .modal-body { padding:.25rem 1.25rem 1.25rem; }
        .accounts-modal .modal-footer { padding:.75rem 1.25rem 1.25rem; border-top:0; }
        .accounts-modal .form-label { color:#334155; font-size:.8rem; font-weight:750; }
        .accounts-modal .form-control, .accounts-modal .form-select { border-color:#dbe3ee; border-radius:10px; }
        .accounts-modal .form-control:focus, .accounts-modal .form-select:focus { border-color:#93c5fd; box-shadow:0 0 0 .2rem rgba(59,130,246,.12); }
        @media (max-width: 900px) { .accounts-summary { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) {
            .accounts-page { padding-inline:.1rem; }
            .accounts-summary-card { min-height:82px; padding:.8rem; }
            .accounts-summary-value { font-size:1.25rem; }
            .accounts-toolbar { align-items:stretch; padding:.8rem; }
            .accounts-toolbar .form-select, .accounts-toolbar .new-accounting-btn { flex:1 1 100%; }
            .accounts-table-card { border:0; background:transparent; box-shadow:none; overflow:visible; }
            .accounts-table thead { display:none; }
            .accounts-table, .accounts-table tbody, .accounts-table tr, .accounts-table td { display:block; width:100%; }
            .accounts-table tbody { display:grid; gap:.65rem; }
            .accounts-table tbody tr { padding:.9rem; border:1px solid #e2e8f0; border-radius:14px; background:#fff; box-shadow:0 4px 14px rgba(15,23,42,.04); }
            .accounts-table tbody td { display:block; padding:0; border:0; }
            .accounts-table tbody td + td { margin-top:.35rem; }
            .accounts-table tbody td:nth-child(1) { float:left; width:auto; margin:0 .55rem .4rem 0; }
            .accounts-table tbody td:nth-child(2) { min-height:1.25rem; }
            .accounts-table tbody td:nth-child(3), .accounts-table tbody td:nth-child(4), .accounts-table tbody td:nth-child(5), .accounts-table tbody td:nth-child(6) { display:inline-block; width:auto; margin:.65rem .45rem 0 0; }
            .account-balance { text-align:left; }
            .account-description { max-width:none; -webkit-line-clamp:3; }
            .accounts-table tbody td[colspan] { float:none !important; width:100% !important; }
        }
    </style>
@endpush

@section('content')
    <div class="accounts-page d-grid gap-3">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="accounts-eyebrow mb-1">Akuntansi Baru</div>
                <h1 class="accounts-title mb-1">Daftar Akun</h1>
                <div class="accounts-subtitle small">Kelola akun yang digunakan untuk jurnal dan laporan keuangan. Modul ini terpisah dari akuntansi lama.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a class="new-accounting-btn" href="{{ route('standalone-accounting.trial-balance.index') }}"><i class="bi bi-bar-chart-line" aria-hidden="true"></i>Neraca Saldo</a>
                <a class="new-accounting-btn" href="{{ route('standalone-accounting.journals.create') }}"><i class="bi bi-journal-plus" aria-hidden="true"></i>Buat Jurnal</a>
                <button class="new-accounting-btn primary" type="button" data-bs-toggle="modal" data-bs-target="#addAccountModal"><i class="bi bi-plus-lg" aria-hidden="true"></i>Tambah Akun</button>
            </div>
        </div>

        @if (session('message'))
            <div class="alert alert-success mb-0"><i class="bi bi-check-circle me-1" aria-hidden="true"></i>{{ session('message') }}</div>
        @endif

        <div class="accounts-summary" aria-label="Ringkasan akun">
            <div class="accounts-summary-card"><div class="accounts-summary-label">Total akun</div><div class="accounts-summary-value">{{ $totalAccounts }}</div><div class="accounts-summary-hint">Semua tipe akun</div></div>
            <div class="accounts-summary-card"><div class="accounts-summary-label">Akun aktif</div><div class="accounts-summary-value">{{ $activeAccounts }}</div><div class="accounts-summary-hint">Bisa dipakai di jurnal baru</div></div>
            <div class="accounts-summary-card"><div class="accounts-summary-label">Nonaktif</div><div class="accounts-summary-value">{{ $inactiveAccounts }}</div><div class="accounts-summary-hint">Tetap menjaga histori</div></div>
            <div class="accounts-summary-card"><div class="accounts-summary-label">Sudah terpakai</div><div class="accounts-summary-value">{{ $usedAccounts }}</div><div class="accounts-summary-hint">Memiliki baris jurnal</div></div>
        </div>

        <form class="accounts-toolbar" method="GET" action="{{ route('standalone-accounting.accounts.index') }}" role="search">
            <div class="accounts-search"><i class="bi bi-search" aria-hidden="true"></i><label class="visually-hidden" for="account-search">Cari akun</label><input id="account-search" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Cari kode, nama, atau keterangan akun..."></div>
            <label class="visually-hidden" for="account-type-filter">Filter tipe akun</label>
            <select id="account-type-filter" name="type" class="form-select" aria-label="Filter tipe akun">
                <option value="">Semua tipe</option>
                @foreach ($typeLabel as $value => $label)<option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }} ({{ $typeCounts[$value] ?? 0 }})</option>@endforeach
            </select>
            <label class="visually-hidden" for="account-status-filter">Filter status akun</label>
            <select id="account-status-filter" name="status" class="form-select" aria-label="Filter status akun">
                <option value="">Semua status</option>
                <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
                <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
            </select>
            <button class="new-accounting-btn primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i>Filter</button>
            @if ($filters['search'] !== '' || $filters['type'] !== '' || $filters['status'] !== '')<a class="new-accounting-btn" href="{{ route('standalone-accounting.accounts.index') }}">Reset</a>@endif
        </form>

        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div class="small text-muted"><strong class="text-dark">{{ $accounts->count() }}</strong> akun ditampilkan</div>
            <div class="small text-muted">Saldo dihitung dari jurnal yang sudah diposting sampai hari ini.</div>
        </div>

        <div class="accounts-table-card">
            <div class="table-responsive">
                <table class="table accounts-table align-middle">
                    <thead><tr><th scope="col">Kode</th><th scope="col">Nama akun</th><th scope="col">Tipe</th><th scope="col" class="text-end">Saldo</th><th scope="col">Pemakaian</th><th scope="col">Status</th></tr></thead>
                    <tbody>
                        @forelse ($accounts as $account)
                            <tr>
                                <td><a class="account-code text-decoration-none" href="{{ route('standalone-accounting.accounts.show', $account) }}">{{ $account->code }}</a></td>
                                <td><a class="account-name text-decoration-none" href="{{ route('standalone-accounting.accounts.show', $account) }}">{{ $account->name }}</a><div class="account-description" title="{{ $account->description }}">{{ $account->description ?: 'Tidak ada keterangan.' }}</div></td>
                                <td><span class="account-badge {{ $typeTone[$account->type] ?? 'blue' }}">{{ $typeLabel[$account->type] ?? ucfirst($account->type) }}</span></td>
                                <td class="account-balance {{ $account->balance_amount > 0 ? '' : 'account-balance-zero' }}"><a class="text-decoration-none account-balance" href="{{ route('standalone-accounting.accounts.ledger', $account) }}">{{ $money($account->balance_amount) }}@if ($account->balance_side)<span class="account-balance-side">{{ $account->balance_side }}</span>@endif</a></td>
                                <td><span class="account-meta">{{ $account->lines_count > 0 ? $account->lines_count . ' baris jurnal' : 'Belum dipakai' }}</span></td>
                                <td><span class="account-status {{ $account->is_active ? '' : 'inactive' }}">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="accounts-empty"><i class="bi bi-search" aria-hidden="true"></i><strong class="d-block text-dark">Akun tidak ditemukan</strong><span class="small text-muted">Coba ubah kata kunci atau filter yang dipilih.</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade accounts-modal" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header"><div><div class="accounts-eyebrow">Chart of accounts</div><h2 class="modal-title fs-5 fw-bold" id="addAccountModalLabel">Tambah akun</h2><p class="small text-muted mb-0 mt-1">Isi informasi dasar akun yang akan digunakan dalam jurnal.</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                <form method="POST" action="{{ route('standalone-accounting.accounts.store') }}">
                    @csrf
                    <div class="modal-body">
                        @if ($errors->any())<div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                        <div class="mb-3"><label class="form-label" for="code">Kode akun <span class="text-danger">*</span></label><input id="code" name="code" class="form-control" value="{{ old('code') }}" placeholder="Contoh: 1-210" required><div class="form-text">Gunakan kode yang konsisten dengan struktur akun perusahaan.</div></div>
                        <div class="mb-3"><label class="form-label" for="name">Nama akun <span class="text-danger">*</span></label><input id="name" name="name" class="form-control" value="{{ old('name') }}" placeholder="Contoh: Piutang Karyawan" required></div>
                        <div class="mb-3"><label class="form-label" for="type">Tipe akun <span class="text-danger">*</span></label><select id="type" name="type" class="form-select" required>@foreach ($typeLabel as $value => $label)<option value="{{ $value }}" @selected(old('type', 'asset') === $value)>{{ $label }}</option>@endforeach</select></div>
                        <div><label class="form-label" for="description">Keterangan</label><textarea id="description" name="description" class="form-control" rows="3" maxlength="1000" placeholder="Jelaskan fungsi akun dan transaksi yang masuk ke sini.">{{ old('description') }}</textarea></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="new-accounting-btn" data-bs-dismiss="modal">Batal</button><button class="new-accounting-btn primary" type="submit"><i class="bi bi-check-lg" aria-hidden="true"></i>Simpan Akun</button></div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @if ($errors->any())
        <script>document.addEventListener('DOMContentLoaded', function () { const modal = document.getElementById('addAccountModal'); if (modal && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modal).show(); });</script>
    @endif
@endpush
