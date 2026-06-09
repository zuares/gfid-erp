
<div class="gf-form-grid">
    <div>
        <label class="form-label">Kode Karyawan</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $employee->code) }}"
            placeholder="Contoh: EMP001" required>
    </div>

    <div>
        <label class="form-label">Nama Karyawan</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $employee->name) }}" required
            placeholder="Nama karyawan">
    </div>

    <div>
        <label class="form-label">Role</label>
        <select name="role" class="form-select" required>
            @foreach ($roles as $r)
                <option value="{{ $r }}" @selected(old('role', $employee->role) === $r)>{{ ucfirst($r) }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="form-label">Payment Type</label>
        <select name="payment_type" class="form-select" required>
            @foreach (['fixed' => 'Fixed', 'variable' => 'Variable'] as $pt => $label)
                <option value="{{ $pt }}" @selected(old('payment_type', $employee->payment_type) === $pt)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="form-label">Gaji Tetap Mingguan</label>
        <input type="number" step="0.01" min="0" name="weekly_fixed_salary" class="form-control"
            value="{{ old('weekly_fixed_salary', $employee->weekly_fixed_salary) }}"
            placeholder="0">
    </div>

    <div>
        <label class="form-label">Default Piece Rate</label>
        <input type="number" step="0.01" min="0" name="default_piece_rate" class="form-control"
            value="{{ old('default_piece_rate', $employee->default_piece_rate) }}"
            placeholder="0">
    </div>

    <div>
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $employee->phone) }}"
            placeholder="Nomor HP / WhatsApp">
    </div>

    <div style="grid-column: 1 / -1;">
        <label class="form-label">Alamat</label>
        <textarea name="address" rows="3" class="form-control"
            placeholder="Alamat karyawan">{{ old('address', $employee->address) }}</textarea>
    </div>

    <div style="grid-column: 1 / -1;">
        <label class="d-flex align-items-center gap-2 mb-0">
            <input type="checkbox" name="active" value="1" class="form-check-input"
                @checked(old('active', $employee->active ?? true))>
            <span class="form-label mb-0">Aktif</span>
        </label>
        <div class="gf-sub mt-1">Nonaktifkan kalau karyawan sudah tidak dipakai di operasional.</div>
    </div>
</div>

<div class="d-flex flex-wrap justify-content-end gap-2 mt-3">
    <a href="{{ route('master.employees.index') }}" class="btn btn-outline-secondary btn-sm">
        Batal
    </a>

    <button type="submit" class="btn btn-primary gf-btn-primary btn-sm">
        <i class="bi bi-check2"></i>
        Simpan Karyawan
    </button>
</div>
