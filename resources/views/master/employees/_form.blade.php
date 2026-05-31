@csrf

<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label form-label-sm">Kode *</label>
        <input type="text" name="code" class="form-control form-control-sm" value="{{ old('code', $employee->code) }}"
            placeholder="misal: EMP001" required>
    </div>
    <div class="col-md-5">
        <label class="form-label form-label-sm">Nama *</label>
        <input type="text" name="name" class="form-control form-control-sm"
            value="{{ old('name', $employee->name) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label form-label-sm">Role *</label>
        <select name="role" class="form-select form-select-sm" required>
            @foreach ($roles as $r)
                <option value="{{ $r }}" @selected(old('role', $employee->role) === $r)>{{ ucfirst($r) }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label form-label-sm">Jenis Gaji *</label>
        <select name="payment_type" class="form-select form-select-sm" required>
            @foreach ($paymentTypes as $pt)
                <option value="{{ $pt }}" @selected(old('payment_type', $employee->payment_type) === $pt)>
                    {{ $pt === 'fixed' ? 'Fixed (gaji tetap)' : 'Variable (borongan)' }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label form-label-sm">Gaji Mingguan Tetap (Rp)</label>
        <input type="number" step="0.01" min="0" name="weekly_fixed_salary" class="form-control form-control-sm"
            value="{{ old('weekly_fixed_salary', $employee->weekly_fixed_salary) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label form-label-sm">Rate Default per pcs (Rp)</label>
        <input type="number" step="0.01" min="0" name="default_piece_rate" class="form-control form-control-sm"
            value="{{ old('default_piece_rate', $employee->default_piece_rate) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label form-label-sm">HP</label>
        <input type="text" name="phone" class="form-control form-control-sm"
            value="{{ old('phone', $employee->phone) }}">
    </div>
    <div class="col-md-8">
        <label class="form-label form-label-sm">Alamat</label>
        <textarea name="address" rows="2" class="form-control form-control-sm">{{ old('address', $employee->address) }}</textarea>
    </div>

    <div class="col-12">
        <div class="form-check">
            <input type="hidden" name="active" value="0">
            <input type="checkbox" name="active" value="1" class="form-check-input" id="activeCheck"
                @checked(old('active', $employee->active ?? true))>
            <label class="form-check-label" for="activeCheck">Aktif</label>
        </div>
    </div>
</div>

<div class="mt-3 d-flex justify-content-between align-items-center">
    <div class="text-muted small">
        Tanda * wajib diisi.
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('master.employees.index') }}" class="btn btn-sm btn-light">
            Batal
        </a>
        <button type="submit" class="btn btn-sm btn-primary">
            Simpan
        </button>
    </div>
</div>
