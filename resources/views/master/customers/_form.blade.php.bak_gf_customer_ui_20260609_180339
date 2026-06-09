@csrf

<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label form-label-sm">Kode</label>
        <input type="text" name="code" class="form-control form-control-sm" value="{{ old('code', $customer->code) }}"
            placeholder="Opsional">
    </div>
    <div class="col-md-5">
        <label class="form-label form-label-sm">Nama *</label>
        <input type="text" name="name" class="form-control form-control-sm"
            value="{{ old('name', $customer->name) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label form-label-sm">HP</label>
        <input type="text" name="phone" class="form-control form-control-sm"
            value="{{ old('phone', $customer->phone) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label form-label-sm">Email</label>
        <input type="email" name="email" class="form-control form-control-sm"
            value="{{ old('email', $customer->email) }}">
    </div>

    <div class="col-md-8">
        <label class="form-label form-label-sm">Alamat</label>
        <textarea name="address" rows="2" class="form-control form-control-sm">{{ old('address', $customer->address) }}</textarea>
    </div>

    <div class="col-md-4">
        <label class="form-label form-label-sm">Kota</label>
        <input type="text" name="city" class="form-control form-control-sm"
            value="{{ old('city', $customer->city) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label form-label-sm">Provinsi</label>
        <input type="text" name="province" class="form-control form-control-sm"
            value="{{ old('province', $customer->province) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label form-label-sm">Kode Pos</label>
        <input type="text" name="postal_code" class="form-control form-control-sm"
            value="{{ old('postal_code', $customer->postal_code) }}">
    </div>

    <div class="col-12">
        <label class="form-label form-label-sm">Catatan</label>
        <textarea name="notes" rows="2" class="form-control form-control-sm">{{ old('notes', $customer->notes) }}</textarea>
    </div>
</div>

<div class="mt-3 d-flex justify-content-between align-items-center">
    <div class="text-muted small">
        Tanda * wajib diisi.
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('master.customers.index') }}" class="btn btn-sm btn-light">
            Batal
        </a>
        <button type="submit" class="btn btn-sm btn-primary">
            Simpan
        </button>
    </div>
</div>
