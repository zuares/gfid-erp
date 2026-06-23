@extends('layouts.app')
@section('title', 'Master • Suppliers • Edit')

@push('head')
    <style>
        .supplier-form .main-card {
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .22);
            background: color-mix(in srgb, var(--card) 94%, var(--bg) 6%);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06)
        }

        .supplier-form .btn-pill {
            border-radius: 999px;
            padding: .5rem .95rem
        }

        .supplier-form .muted {
            color: var(--muted)
        }
    </style>
@endpush

@section('content')
    <div class="supplier-form container py-3">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <h1 class="h5 mb-1">Edit Supplier</h1>
                <div class="muted" style="font-size:.9rem">{{ $supplier->code }} • {{ $supplier->name }}</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('master.suppliers.show', $supplier) }}" class="btn btn-outline-secondary btn-pill">Back</a>
            </div>
        </div>

        <div class="main-card mt-3 p-3">
            <form method="POST" action="{{ route('master.suppliers.update', $supplier) }}">
                @csrf
                @method('PUT')

                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Code</label>
                        <input name="code" value="{{ old('code', $supplier->code) }}" class="form-control" required
                            autofocus>
                        @error('code')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Name</label>
                        <input name="name" value="{{ old('name', $supplier->name) }}" class="form-control" required>
                        @error('name')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input name="phone" value="{{ old('phone', $supplier->phone) }}" class="form-control">
                        @error('phone')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input name="email" value="{{ old('email', $supplier->email) }}" class="form-control">
                        @error('email')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="active" class="form-select">
                            <option value="1" @selected(old('active', (string) $supplier->active) == '1')>Active</option>
                            <option value="0" @selected(old('active', (string) $supplier->active) == '0')>Inactive</option>
                        </select>
                        @error('active')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" rows="3" class="form-control">{{ old('address', $supplier->address) }}</textarea>
                        @error('address')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="submit" class="btn btn-primary btn-pill">Update</button>
                </div>
            </form>
        </div>
    </div>
@endsection
