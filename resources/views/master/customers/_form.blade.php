@php
    /** @var \App\Models\Customer|null $customer */
    $isEdit = isset($customer) && $customer?->id;
@endphp

<div class="card card-main mb-3">
    <div class="card-header fw-semibold">
        {{ $isEdit ? 'Edit Customer' : 'Tambah Customer Baru' }}
    </div>
    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-6">
                <label class="form-label fw-semibold small">Nama</label>
                <input type="text" name="name" class="form-control form-control-sm"
                    value="{{ old('name', $customer->name ?? '') }}" required>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small">Telepon/HP</label>
                <input type="text" name="phone" class="form-control form-control-sm"
                    value="{{ old('phone', $customer->phone ?? '') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small">Email</label>
                <input type="email" name="email" class="form-control form-control-sm"
                    value="{{ old('email', $customer->email ?? '') }}">
            </div>

            <div class="col-md-9">
                <label class="form-label fw-semibold small">Alamat</label>
                <textarea name="address" rows="2" class="form-control form-control-sm" placeholder="Alamat lengkap">{{ old('address', $customer->address ?? '') }}</textarea>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small d-block">Status</label>
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" @checked(old('active', $customer->active ?? 1))>
                <span class="small ms-1 text-muted">Centang jika aktif</span>
            </div>

        </div>
    </div>
</div>

<div class="d-flex justify-content-end">
    <button class="btn btn-primary px-4">
        Simpan
    </button>
</div>
