
<div class="gf-form-grid">
    <div>
        <label class="form-label">Kode Customer</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $customer->code) }}"
            placeholder="Contoh: CUST001">
    </div>

    <div>
        <label class="form-label">Nama Customer</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name) }}" required
            placeholder="Nama customer">
    </div>

    <div>
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}"
            placeholder="Nomor HP / WhatsApp">
    </div>

    <div>
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}"
            placeholder="Email customer">
    </div>

    <div style="grid-column: 1 / -1;">
        <label class="form-label">Alamat</label>
        <textarea name="address" rows="3" class="form-control"
            placeholder="Alamat customer">{{ old('address', $customer->address) }}</textarea>
    </div>

    <div>
        <label class="form-label">Kota</label>
        <input type="text" name="city" class="form-control" value="{{ old('city', $customer->city) }}"
            placeholder="Kota">
    </div>

    <div>
        <label class="form-label">Provinsi</label>
        <input type="text" name="province" class="form-control" value="{{ old('province', $customer->province) }}"
            placeholder="Provinsi">
    </div>

    <div>
        <label class="form-label">Kode Pos</label>
        <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code', $customer->postal_code) }}"
            placeholder="Kode pos">
    </div>

    <div style="grid-column: 1 / -1;">
        <label class="form-label">Catatan</label>
        <textarea name="notes" rows="3" class="form-control"
            placeholder="Catatan tambahan">{{ old('notes', $customer->notes) }}</textarea>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
    <a href="{{ route('master.customers.index') }}" class="btn btn-outline-secondary btn-sm">
        Batal
    </a>

    <button type="submit" class="btn btn-primary gf-btn-primary btn-sm">
        <i class="bi bi-check2"></i>
        Simpan Customer
    </button>
</div>
