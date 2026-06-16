{{--
    Shared form partial for QC create & edit.
    Variables expected:
      $purchase_receipt  — PurchaseReceipt model
      $totalQtyReceived  — float, default for qty_checked
      $qc                — PurchaseReceiptQc|null (null on create)
--}}
@php
    $isEdit   = isset($qc) && $qc !== null;
    $old      = fn(string $key, $default = '') => old($key, $isEdit ? ($qc->{$key} ?? $default) : $default);
    $oldStatus = old('status', $isEdit ? ($qc->status ?? 'passed') : 'passed');
@endphp

<style>
    .qc-form-wrap { max-width: 680px; margin-inline: auto; padding-bottom: 3rem; }
    .qc-card {
        background: var(--card);
        border-radius: 14px;
        border: 1px solid var(--line);
        margin-bottom: 1rem;
    }
    .qc-card-header {
        padding: .85rem 1.1rem;
        border-bottom: 1px solid var(--line);
        font-weight: 600;
        font-size: .92rem;
    }
    .qc-card-body { padding: 1.1rem; }
    .qc-label { font-size: .8rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; }
    .mono { font-variant-numeric: tabular-nums; }

    /* Status radio cards */
    .qc-status-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .6rem; }
    @media (max-width: 480px) { .qc-status-grid { grid-template-columns: 1fr; } }
    .qc-status-card {
        border: 2px solid var(--line);
        border-radius: 12px;
        padding: .75rem;
        cursor: pointer;
        text-align: center;
        transition: .15s;
        user-select: none;
    }
    .qc-status-card input[type="radio"] { display: none; }
    .qc-status-card:has(input:checked) { border-width: 2px; }
    .qc-status-card.status-passed:has(input:checked)  { border-color: #16a34a; background: rgba(22,163,74,.08); }
    .qc-status-card.status-issue:has(input:checked)   { border-color: #d97706; background: rgba(217,119,6,.08); }
    .qc-status-card.status-rejected:has(input:checked){ border-color: #dc2626; background: rgba(220,38,38,.07); }
    .qc-status-icon  { font-size: 1.4rem; }
    .qc-status-label { font-size: .82rem; font-weight: 600; margin-top: .2rem; }
    .qc-status-desc  { font-size: .72rem; color: var(--muted); margin-top: .1rem; }
</style>

<div class="qc-form-wrap">

    {{-- GRN reference --}}
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('purchasing.purchase_receipts.show', $purchase_receipt->id) }}"
            class="btn btn-sm btn-outline-secondary" style="border-radius:10px;">
            ← Kembali ke GRN
        </a>
        <div>
            <span class="fw-bold">{{ $purchase_receipt->code }}</span>
            <span class="text-muted ms-1" style="font-size:.85rem;">
                {{ $purchase_receipt->supplier?->name ?? '' }}
                @if ($purchase_receipt->date)
                    · {{ $purchase_receipt->date->format('d/m/Y') }}
                @endif
            </span>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2 mb-3">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $e)
                    <li style="font-size:.88rem;">{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Status QC --}}
    <div class="qc-card">
        <div class="qc-card-header">Hasil Pemeriksaan</div>
        <div class="qc-card-body">
            <div class="qc-label mb-2">Status QC *</div>
            <div class="qc-status-grid">
                <label class="qc-status-card status-passed">
                    <input type="radio" name="status" value="passed" {{ $oldStatus === 'passed' ? 'checked' : '' }}>
                    <div class="qc-status-icon">✅</div>
                    <div class="qc-status-label" style="color:#15803d;">Lolos QC</div>
                    <div class="qc-status-desc">Barang OK</div>
                </label>
                <label class="qc-status-card status-issue">
                    <input type="radio" name="status" value="issue" {{ $oldStatus === 'issue' ? 'checked' : '' }}>
                    <div class="qc-status-icon">⚠️</div>
                    <div class="qc-status-label" style="color:#b45309;">Ada Masalah</div>
                    <div class="qc-status-desc">Sebagian bermasalah</div>
                </label>
                <label class="qc-status-card status-rejected">
                    <input type="radio" name="status" value="rejected" {{ $oldStatus === 'rejected' ? 'checked' : '' }}>
                    <div class="qc-status-icon">❌</div>
                    <div class="qc-status-label" style="color:#b91c1c;">Ditolak</div>
                    <div class="qc-status-desc">Tidak diterima</div>
                </label>
            </div>
        </div>
    </div>

    {{-- Qty --}}
    <div class="qc-card">
        <div class="qc-card-header">Jumlah Barang</div>
        <div class="qc-card-body">
            <div class="row g-3">
                <div class="col-12 col-sm-4">
                    <label class="qc-label mb-1">Qty Diperiksa *</label>
                    <input type="number" name="qty_checked" step="0.01" min="0"
                        class="form-control @error('qty_checked') is-invalid @enderror"
                        value="{{ $old('qty_checked', $totalQtyReceived) }}"
                        placeholder="0">
                    @error('qty_checked')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-sm-4">
                    <label class="qc-label mb-1" style="color:#15803d;">Qty OK</label>
                    <input type="number" name="qty_ok" step="0.01" min="0"
                        class="form-control @error('qty_ok') is-invalid @enderror"
                        value="{{ $old('qty_ok', $totalQtyReceived) }}"
                        placeholder="0">
                    @error('qty_ok')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-sm-4">
                    <label class="qc-label mb-1" style="color:#b91c1c;">Qty Bermasalah</label>
                    <input type="number" name="qty_issue" step="0.01" min="0"
                        class="form-control @error('qty_issue') is-invalid @enderror"
                        value="{{ $old('qty_issue', 0) }}"
                        placeholder="0">
                    @error('qty_issue')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="text-muted mt-2" style="font-size:.78rem;">
                Total qty diterima di GRN: <strong class="mono">{{ number_format($totalQtyReceived, 2, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    {{-- Issue type + Notes --}}
    <div class="qc-card" id="issue-section">
        <div class="qc-card-header">Detail Masalah <span class="text-muted fw-normal">(isi jika ada masalah)</span></div>
        <div class="qc-card-body">
            <div class="row g-3">
                <div class="col-12 col-sm-6">
                    <label class="qc-label mb-1">Jenis Masalah</label>
                    <select name="issue_type" class="form-select @error('issue_type') is-invalid @enderror">
                        <option value="">— Pilih jika ada masalah —</option>
                        @foreach (\App\Models\PurchaseReceiptQc::issueTypes() as $val => $label)
                            <option value="{{ $val }}" {{ $old('issue_type') === $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('issue_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <label class="qc-label mb-1">Catatan</label>
                    <textarea name="notes" rows="3"
                        class="form-control @error('notes') is-invalid @enderror"
                        placeholder="Deskripsikan kondisi barang, lokasi masalah, dll...">{{ $old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Submit --}}
    <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('purchasing.purchase_receipts.show', $purchase_receipt->id) }}"
            class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">
            {{ $isEdit ? 'Simpan Perubahan QC' : 'Simpan QC' }}
        </button>
    </div>

</div>
