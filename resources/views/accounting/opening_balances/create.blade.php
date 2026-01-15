@extends('layouts.app')

@section('title', 'Opening Balance')

@section('content')
    <div class="container" style="max-width:640px">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="mb-0">Opening Balance</h4>
            <a href="{{ route('accounting.journals.index') }}" class="btn btn-light">Journals</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Ada error:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('accounting.opening-balances.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Tanggal Opening</label>
                        <input type="date" name="date" class="form-control"
                            value="{{ old('date', now()->toDateString()) }}" required>
                        <div class="form-text">Biasanya tanggal awal pembukuan (mis. 2026-01-01).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Akun Kas/Bank</label>
                        <select name="cash_account_id" class="form-control" required>
                            <option value="">-- pilih kas/bank --</option>
                            @foreach ($cashAccounts as $acc)
                                <option value="{{ $acc->id }}" @selected(old('cash_account_id') == $acc->id)>
                                    {{ $acc->code }} — {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Akun Lawan (Equity)</label>
                        <select name="equity_account_id" class="form-control" required>
                            @foreach ($equityAccounts as $acc)
                                <option value="{{ $acc->id }}" @selected(old('equity_account_id', $defaultEquity) == $acc->id)>
                                    {{ $acc->code }} — {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Default: Modal. Bisa ganti kalau kamu punya akun equity lain.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nominal Saldo Awal</label>
                        <input type="number" name="amount" class="form-control" value="{{ old('amount') }}"
                            min="0.01" step="0.01" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan (opsional)</label>
                        <input type="text" name="description" class="form-control" value="{{ old('description') }}"
                            placeholder="Opening Balance Bank BCA">
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ url()->previous() }}" class="btn btn-light">Cancel</a>
                        <button class="btn btn-success"
                            onclick="return confirm('Posting opening balance? Ini akan membuat journal POSTED.')">
                            POST Opening Balance
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-3 text-muted" style="font-size:.9rem">
            <div><b>Journal yang dibuat:</b></div>
            <div>Debit: Kas/Bank</div>
            <div>Credit: Equity (Modal)</div>
        </div>
    </div>
@endsection
