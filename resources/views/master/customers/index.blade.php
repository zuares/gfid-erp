@extends('layouts.app')

@section('title', 'Customers')

@push('head')
    <style>
        .page-wrap {
            max-width: 1000px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 26%,
                    #f9fafb 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.30);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.03);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Customers</h5>
                <div class="small text-muted">
                    Master customer untuk penjualan & marketplace.
                </div>
            </div>
            <div>
                <a href="{{ route('customers.create') }}" class="btn btn-sm btn-primary">
                    + Tambah Customer
                </a>
            </div>
        </div>

        {{-- Filter --}}
        <div class="card card-main mb-3">
            <div class="card-body py-2">
                <form action="{{ route('customers.index') }}" method="GET" class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small mb-1">Cari</label>
                        <input type="text" name="q" class="form-control form-control-sm"
                            placeholder="Nama / telepon / email" value="{{ request('q') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Status</label>
                        <select name="active" class="form-select form-select-sm">
                            <option value="">- Semua -</option>
                            <option value="1" @selected(request('active') === '1')>Aktif</option>
                            <option value="0" @selected(request('active') === '0')>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary btn-sm w-100">
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Table --}}
        <div class="card card-main">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr class="text-muted">
                                <th>Nama</th>
                                <th style="width: 16%">Telepon</th>
                                <th style="width: 18%">Email</th>
                                <th style="width: 10%" class="text-center">Status</th>
                                <th style="width: 16%" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customers as $customer)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $customer->name }}
                                        </div>
                                        @if ($customer->address)
                                            <div class="small text-muted">
                                                {{ Str::limit($customer->address, 60) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $customer->phone }}</td>
                                    <td>{{ $customer->email }}</td>
                                    <td class="text-center">
                                        @if ($customer->active)
                                            <span class="badge bg-success-subtle text-success">Aktif</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('customers.edit', $customer) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>

                                        <form action="{{ route('customers.destroy', $customer) }}" method="POST"
                                            class="d-inline" onsubmit="return confirm('Hapus customer ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        Belum ada customer.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-3 py-2">
                    {{ $customers->links() }}
                </div>
            </div>
        </div>

    </div>
@endsection
