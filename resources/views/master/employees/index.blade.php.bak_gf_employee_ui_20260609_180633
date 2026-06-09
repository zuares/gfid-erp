@extends('layouts.app')

@section('title', 'Karyawan')

@section('content')
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-1">Karyawan</h1>
                <p class="text-muted mb-0">
                    Master data karyawan untuk produksi & payroll.
                </p>
            </div>
            <a href="{{ route('master.employees.create') }}" class="btn btn-primary btn-sm">
                + Karyawan Baru
            </a>
        </div>

        @if (session('status'))
            <div class="alert alert-success py-2 px-3 small">
                {{ session('status') }}
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('master.employees.index') }}" class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label class="col-form-label col-form-label-sm">Cari</label>
                    </div>
                    <div class="col-auto">
                        <input type="text" name="q" class="form-control form-control-sm"
                            placeholder="Nama / kode / HP" value="{{ request('q') }}">
                    </div>
                    <div class="col-auto">
                        <select name="role" class="form-select form-select-sm">
                            <option value="">Semua Role</option>
                            @foreach ($roles as $r)
                                <option value="{{ $r }}" @selected(request('role') === $r)>{{ ucfirst($r) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-outline-secondary" type="submit">Filter</button>
                    </div>
                    @if (request('q') || request('role'))
                        <div class="col-auto">
                            <a href="{{ route('master.employees.index') }}"
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
                                <th>Role</th>
                                <th>Jenis Gaji</th>
                                <th>HP</th>
                                <th>Status</th>
                                <th style="width: 160px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($employees as $employee)
                                <tr>
                                    <td>{{ $loop->iteration + ($employees->currentPage() - 1) * $employees->perPage() }}
                                    </td>
                                    <td>{{ $employee->code }}</td>
                                    <td>{{ $employee->name }}</td>
                                    <td>{{ ucfirst($employee->role) }}</td>
                                    <td>{{ $employee->payment_type === 'fixed' ? 'Fixed' : 'Variable' }}</td>
                                    <td>{{ $employee->phone ?? '-' }}</td>
                                    <td>
                                        @if ($employee->active)
                                            <span class="badge bg-success-subtle text-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('master.employees.edit', $employee) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                Edit
                                            </a>
                                            <form action="{{ route('master.employees.destroy', $employee) }}"
                                                method="POST" onsubmit="return confirm('Hapus karyawan ini?');">
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
                                    <td colspan="8" class="text-center text-muted py-3">
                                        Belum ada data karyawan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-2">
                    {{ $employees->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
