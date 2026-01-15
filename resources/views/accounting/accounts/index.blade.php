@extends('layouts.app')

@section('title', 'Accounts')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Accounts</h4>
            <a href="{{ route('accounting.accounts.create') }}" class="btn btn-primary">+ New Account</a>
        </div>

        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <select name="type" class="form-control">
                    <option value="">All Types</option>
                    @foreach (['asset', 'liability', 'equity', 'revenue', 'expense'] as $t)
                        <option value="{{ $t }}" @selected(request('type') === $t)>{{ strtoupper($t) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="active" class="form-control">
                    <option value="">All</option>
                    <option value="1" @selected(request('active') === '1')>Active</option>
                    <option value="0" @selected(request('active') === '0')>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary">Filter</button>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:120px;">Code</th>
                            <th>Name</th>
                            <th style="width:120px;">Type</th>
                            <th style="width:90px;">Cash</th>
                            <th style="width:110px;">Active</th>
                            <th style="width:110px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $acc)
                            <tr>
                                <td class="fw-semibold">{{ $acc->code }}</td>
                                <td>{{ $acc->name }}</td>
                                <td><span class="badge bg-secondary">{{ strtoupper($acc->type) }}</span></td>
                                <td>
                                    @if ($acc->is_cash)
                                        <span class="badge bg-info">YES</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($acc->is_active)
                                        <span class="badge bg-success">ACTIVE</span>
                                    @else
                                        <span class="badge bg-danger">INACTIVE</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('accounting.accounts.show', $acc) }}"
                                        class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No accounts</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $accounts->links() }}
        </div>
    </div>
@endsection
