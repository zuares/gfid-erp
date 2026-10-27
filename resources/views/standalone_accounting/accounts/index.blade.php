@extends('layouts.app')

@section('title', 'Akuntansi Baru • Daftar Akun')

@push('head')
    <style>
        .new-accounting-btn { display:inline-flex; align-items:center; min-height:38px; padding:.45rem .85rem; border:1px solid #dbe3ee; border-radius:10px; background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:700; }
        .new-accounting-btn:hover { background:#f8fafc; color:#0f172a; }
        .new-accounting-btn.primary { background:#0f172a; border-color:#0f172a; color:#fff; }
        .new-accounting-btn.primary:hover { background:#1e293b; color:#fff; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
        <div>
            <h4 class="mb-1">Akuntansi Baru — Daftar Akun</h4>
            <div class="text-muted small">Chart of accounts ini tidak terhubung dengan akun akuntansi lama.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a class="new-accounting-btn" href="{{ route('standalone-accounting.trial-balance.index') }}">Neraca Saldo</a>
            <a class="new-accounting-btn primary" href="{{ route('standalone-accounting.journals.create') }}">+ Buat Jurnal</a>
        </div>
    </div>

    @if (session('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Kode</th><th>Nama Akun</th><th>Keterangan</th><th>Tipe</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($accounts as $account)
                                <tr>
                                    <td class="fw-semibold">{{ $account->code }}</td>
                                    <td>{{ $account->name }}</td>
                                    <td class="text-muted small">{{ $account->description ?: '-' }}</td>
                                    <td>{{ ucfirst($account->type) }}</td>
                                    <td>{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-5">Belum ada akun.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="mb-3">Tambah Akun</h6>
                    <form method="POST" action="{{ route('standalone-accounting.accounts.store') }}">
                        @csrf
                        <div class="mb-3"><label class="form-label" for="code">Kode</label><input id="code" name="code" class="form-control" value="{{ old('code') }}" placeholder="1010" required></div>
                        <div class="mb-3"><label class="form-label" for="name">Nama Akun</label><input id="name" name="name" class="form-control" value="{{ old('name') }}" placeholder="Kas Kecil" required></div>
                        <div class="mb-3"><label class="form-label" for="description">Keterangan</label><textarea id="description" name="description" class="form-control" rows="3" maxlength="1000" placeholder="Jelaskan fungsi akun dan transaksi yang masuk ke sini.">{{ old('description') }}</textarea></div>
                        <div class="mb-3">
                            <label class="form-label" for="type">Tipe</label>
                            <select id="type" name="type" class="form-select" required>
                                @foreach (['asset' => 'Aset', 'liability' => 'Liabilitas', 'equity' => 'Ekuitas', 'revenue' => 'Pendapatan', 'expense' => 'Beban'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="new-accounting-btn primary w-100" type="submit">Simpan Akun</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
