@extends('layouts.app')

@section('title', 'Account Detail')

@section('content')
    <div class="container" style="max-width:750px">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Account Detail</h4>
            <a href="{{ route('accounting.accounts.index') }}" class="btn btn-light">Back</a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Code</dt>
                    <dd class="col-sm-9 fw-semibold">{{ $account->code }}</dd>

                    <dt class="col-sm-3">Name</dt>
                    <dd class="col-sm-9">{{ $account->name }}</dd>

                    <dt class="col-sm-3">Type</dt>
                    <dd class="col-sm-9"><span class="badge bg-secondary">{{ strtoupper($account->type) }}</span></dd>

                    <dt class="col-sm-3">Cash/Bank</dt>
                    <dd class="col-sm-9">
                        @if ($account->is_cash)
                            <span class="badge bg-info">YES</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3">Active</dt>
                    <dd class="col-sm-9">
                        @if ($account->is_active)
                            <span class="badge bg-success">ACTIVE</span>
                        @else
                            <span class="badge bg-danger">INACTIVE</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('accounting.accounts.edit', $account) }}" class="btn btn-warning">Edit</a>

            <form method="POST" action="{{ route('accounting.accounts.destroy', $account) }}"
                onsubmit="return confirm('Nonaktifkan account ini?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger">Deactivate</button>
            </form>
        </div>
    </div>
@endsection
