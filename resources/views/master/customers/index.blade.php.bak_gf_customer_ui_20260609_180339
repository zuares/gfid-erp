@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-1">Customers</h1>
                <p class="text-muted mb-0">
                    Master data customer untuk Marketplace Order, Sales Invoice, dan Shipment.
                </p>
            </div>
            <a href="{{ route('master.customers.create') }}" class="btn btn-primary btn-sm">
                + Customer Baru
            </a>
        </div>

        @if (session('status'))
            <div class="alert alert-success py-2 px-3 small">
                {{ session('status') }}
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('master.customers.index') }}" class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label class="col-form-label col-form-label-sm">Cari</label>
                    </div>
                    <div class="col-auto">
                        <input type="text" name="q" class="form-control form-control-sm"
                            placeholder="Nama / kode / HP" value="{{ request('q') }}">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-outline-secondary" type="submit">Filter</button>
                    </div>
                    @if (request('q'))
                        <div class="col-auto">
                            <a href="{{ route('master.customers.index') }}"
                                class="btn btn-sm btn-link text-decoration-none">
                                Reset
                            </a>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>HP</th>
                                <th>Kota</th>
                                <th style="width: 160px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customers as $customer)
                                <tr>
                                    <td>{{ $loop->iteration + ($customers->currentPage() - 1) * $customers->perPage() }}
                                    </td>
                                    <td>{{ $customer->code ?? '-' }}</td>
                                    <td>{{ $customer->name }}</td>
                                    <td>{{ $customer->phone ?? '-' }}</td>
                                    <td>{{ $customer->city ?? '-' }}</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('master.customers.edit', $customer) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                Edit
                                            </a>
                                            <form action="{{ route('master.customers.destroy', $customer) }}"
                                                method="POST" onsubmit="return confirm('Hapus customer ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        Belum ada data customer.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-2">
                    {{ $customers->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
