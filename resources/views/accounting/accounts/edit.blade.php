@extends('layouts.app')

@section('title', 'Edit Akun')

@section('content')
    <div class="container py-4" style="max-width:650px">
        <h4 class="mb-3">Edit Akun</h4>
        <p class="text-muted">Perbarui detail akun. Nama akan tampil di daftar akun dan laporan, termasuk histori transaksi.</p>

        <form method="POST" action="{{ route('accounting.accounts.update', $account) }}">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="account-code" class="form-label">Kode akun</label>
                        <input id="account-code" type="text" name="code"
                            class="form-control @error('code') is-invalid @enderror"
                            value="{{ old('code', $account->code) }}" required maxlength="20"
                            aria-describedby="account-code-help @error('code') account-code-error @enderror"
                            @error('code') aria-invalid="true" @enderror>
                        @error('code')
                            <div id="account-code-error" class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div id="account-code-help" class="form-text">Kode harus unik. Kode/jenis akun dengan transaksi tidak dapat diubah.</div>
                    </div>
                    <label for="account-name" class="form-label">Nama akun</label>
                    <input id="account-name" type="text" name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $account->name) }}" required maxlength="255" autofocus
                        aria-describedby="account-name-help @error('name') account-name-error @enderror"
                        @error('name') aria-invalid="true" @enderror>
                    @error('name')
                        <div id="account-name-error" class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div id="account-name-help" class="form-text">Nama akun bisa diubah kapan saja tanpa mengubah histori transaksi.</div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label for="account-type" class="form-label">Jenis akun</label>
                            <select id="account-type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                                @foreach ($types as $type)
                                    <option value="{{ $type }}" @selected(old('type', $account->type) === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 d-flex flex-column justify-content-end">
                            <div class="form-check mb-2">
                                <input type="hidden" name="is_cash" value="0">
                                <input class="form-check-input" type="checkbox" name="is_cash" value="1" id="account-is-cash" @checked(old('is_cash', $account->is_cash))>
                                <label class="form-check-label" for="account-is-cash">Kas / Bank</label>
                            </div>
                            <div class="form-check">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="account-is-active" @checked(old('is_active', $account->is_active))>
                                <label class="form-check-label" for="account-is-active">Akun aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('accounting.accounts.index') }}" class="btn btn-light">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan perubahan</button>
            </div>
        </form>
    </div>
@endsection
