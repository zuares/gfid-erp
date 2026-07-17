import re

with open('resources/views/production/qc/sewing_edit.blade.php', 'r') as f:
    content = f.read()

# 1. Update totalReject loop
content = content.replace(
"""    foreach ($rows as $idx => $row) {
        $totalIn += (float) $row['qty_max'];
        $totalOk += (float) old("results.{$idx}.qty_ok", $row['qty_ok']);
        $totalReject += (float) old("results.{$idx}.qty_reject", $row['qty_reject']);
    }""",
"""    foreach ($rows as $idx => $row) {
        $totalIn += (float) $row['qty_max'];
        $totalOk += (float) old("results.{$idx}.qty_ok", $row['qty_ok']);
        $totalReject += (float) old("results.{$idx}.qty_reject_jahit", $row['qty_reject_jahit'] ?? 0) + (float) old("results.{$idx}.qty_reject_bahan", $row['qty_reject_bahan'] ?? 0);
    }""")

# 2. Update fab-wrap CSS
content = content.replace(
"""        .fab-wrap {
            position: fixed;
            bottom: var(--fab-bottom);
            left: 0; right: 0;
            padding: .85rem 1rem;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(8px);
            border-top: 1px solid rgba(0,0,0,0.05);
            display: flex; gap: .75rem;
            z-index: 1000;
            box-shadow: 0 -4px 15px rgba(0,0,0,0.02);
            transition: bottom 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .fab-wrap .btn { border-radius: 12px; font-weight: 800; }
        .fab-back { flex: 0 0 auto; width: 48px; display: flex; align-items: center; justify-content: center; }
        .fab-save { flex: 1 1 auto; padding: .65rem 1rem; }""",
"""        .fab-wrap {
            position: fixed; right: 14px; bottom: calc(env(safe-area-inset-bottom, 20px) + var(--vv-kbd, 0px) + 20px);
            z-index: 1090; display: flex; gap: 10px; align-items: center; pointer-events: none;
            transition: bottom 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .fab-wrap .btn { pointer-events: auto; border-radius: 999px; font-weight: 900; box-shadow: 0 12px 26px rgba(139, 92, 246, .35), 0 4px 10px rgba(139, 92, 246, .2); }
        .fab-back { width: 46px; padding-left: 0; padding-right: 0; display: flex; align-items: center; justify-content: center; background: white !important; color: var(--text) !important; border-color: rgba(0,0,0,0.1) !important; }
        .fab-save { width: auto; padding: .75rem 1.5rem; white-space: nowrap; font-size: 1.05rem; }""")

# 3. Update hasQcSewing Alert and Form Wrap
content = content.replace(
"""    @if($hasQcSewing)
        <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
            QC Jahit untuk Setor Jahit ini sudah pernah diinput. Simpan ulang akan menimpa hasil QC sebelumnya.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="panel mb-2">
        <form action="{{ route('production.qc.sewing.update', $sewingReturn) }}" method="POST">
            @csrf
            @method('PUT')""",
"""    @if($hasQcSewing)
        <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
            <strong>Mode Read-Only.</strong> QC Jahit sudah diposting dan stok telah bergerak. Untuk mengoreksi nilai QC, Anda harus menekan tombol <strong>Batalkan QC</strong> dari halaman sebelumnya.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="panel mb-2">
        <form action="{{ route('production.qc.sewing.update', $sewingReturn) }}" method="POST">
            @csrf
            @method('PUT')
            
            @if($hasQcSewing)
                <fieldset disabled>
            @endif""")

# Close fieldset at the end
content = content.replace(
"""        </form>
    </div>
</div>
@endsection""",
"""            @if($hasQcSewing)
                </fieldset>
            @endif
        </form>
    </div>
</div>
@endsection""")

# 4. Replace Input fields (grid2 to grid3)
content = content.replace(
"""                                    <div class="grid2">
                                        <div class="field">
                                            <label>OK</label>
                                            <input type="number" step="1" min="0" max="{{ $row['qty_max'] }}"
                                                   inputmode="numeric"
                                                   class="form-control form-control-sm qty ok num-input select-all-on-focus qty-ok"
                                                   name="results[{{ $i }}][qty_ok]"
                                                   data-idx="{{ $i }}"
                                                   value="{{ old("results.{$i}.qty_ok", $row['qty_ok']) }}" placeholder="0"
                                                   oninput="syncReject(this, {{ $i }}, {{ $row['qty_max'] }})">
                                        </div>
                                        <div class="field">
                                            <label>Reject</label>
                                            <input type="number" step="1" min="0" max="{{ $row['qty_max'] }}"
                                                   inputmode="numeric"
                                                   class="form-control form-control-sm qty rj num-input select-all-on-focus qty-reject"
                                                   name="results[{{ $i }}][qty_reject]"
                                                   id="reject_{{ $i }}"
                                                   data-idx="{{ $i }}"
                                                   value="{{ old("results.{$i}.qty_reject", $row['qty_reject']) }}" placeholder="0"
                                                   oninput="syncOk(this, {{ $i }}, {{ $row['qty_max'] }})">
                                        </div>
                                    </div>
                                    
                                    <div class="field notes qcs-mobile-reason {{ (float) old("results.{$i}.qty_reject", $row['qty_reject']) > 0 ? 'is-show' : '' }}" style="margin-top: .35rem;">
                                        <label>Alasan Reject</label>
                                        <select name="results[{{ $i }}][reject_reason]" class="form-select form-select-sm" style="border-radius: 12px;">
                                            <option value="">- Pilih Alasan -</option>
                                            <option value="Reject Jahit" {{ old("results.{$i}.reject_reason", $row['reject_reason']) == 'Reject Jahit' ? 'selected' : '' }}>Reject Jahit</option>
                                            <option value="Reject Bahan" {{ old("results.{$i}.reject_reason", $row['reject_reason']) == 'Reject Bahan' ? 'selected' : '' }}>Reject Bahan</option>
                                            @if(old("results.{$i}.reject_reason", $row['reject_reason']) && !in_array(old("results.{$i}.reject_reason", $row['reject_reason']), ['Reject Jahit', 'Reject Bahan']))
                                                <option value="{{ old("results.{$i}.reject_reason", $row['reject_reason']) }}" selected>{{ old("results.{$i}.reject_reason", $row['reject_reason']) }}</option>
                                            @endif
                                        </select>
                                    </div>""",
"""                                    <div class="grid3" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
                                        <div class="field">
                                            <label>OK</label>
                                            <input type="number" step="1" min="0" max="{{ $row['qty_max'] }}"
                                                   inputmode="numeric"
                                                   class="form-control form-control-sm qty ok num-input select-all-on-focus qty-ok"
                                                   name="results[{{ $i }}][qty_ok]"
                                                   id="ok_{{ $i }}"
                                                   data-idx="{{ $i }}"
                                                   value="{{ old("results.{$i}.qty_ok", $row['qty_ok']) }}" placeholder="0"
                                                   oninput="syncQty('ok', {{ $i }}, {{ $row['qty_max'] }})">
                                        </div>
                                        <div class="field">
                                            <label>Rj. Jahit</label>
                                            <input type="number" step="1" min="0" max="{{ $row['qty_max'] }}"
                                                   inputmode="numeric"
                                                   class="form-control form-control-sm qty rj num-input select-all-on-focus qty-reject-jahit"
                                                   name="results[{{ $i }}][qty_reject_jahit]"
                                                   id="jahit_{{ $i }}"
                                                   data-idx="{{ $i }}"
                                                   value="{{ old("results.{$i}.qty_reject_jahit", $row['qty_reject_jahit'] ?? 0) }}" placeholder="0"
                                                   oninput="syncQty('jahit', {{ $i }}, {{ $row['qty_max'] }})">
                                        </div>
                                        <div class="field">
                                            <label>Rj. Bahan</label>
                                            <input type="number" step="1" min="0" max="{{ $row['qty_max'] }}"
                                                   inputmode="numeric"
                                                   class="form-control form-control-sm qty rj num-input select-all-on-focus qty-reject-bahan"
                                                   name="results[{{ $i }}][qty_reject_bahan]"
                                                   id="bahan_{{ $i }}"
                                                   data-idx="{{ $i }}"
                                                   value="{{ old("results.{$i}.qty_reject_bahan", $row['qty_reject_bahan'] ?? 0) }}" placeholder="0"
                                                   oninput="syncQty('bahan', {{ $i }}, {{ $row['qty_max'] }})">
                                        </div>
                                    </div>""")

# 5. Update sync scripts
content = content.replace(
"""    function syncReject(okInput, idx, max) {
        const ok = numberValue(okInput);
        const rejectField = document.getElementById('reject_' + idx);
        const suggestedReject = Math.max(0, max - ok);

        if (rejectField) {
            rejectField.value = suggestedReject;
        }

        toggleReason(idx);
        updateSummary();
    }

    function syncOk(rejectInput, idx, max) {
        const reject = numberValue(rejectInput);
        const okInputs = document.querySelectorAll('.qty-ok');
        const okField = okInputs[idx];
        const suggestedOk = Math.max(0, max - reject);

        if (okField) {
            okField.value = suggestedOk;
        }

        toggleReason(idx);
        updateSummary();
    }

    function toggleReason(idx) {
        const rejectField = document.getElementById('reject_' + idx);
        const reasonWrap = rejectField?.closest('.cardx-b')?.querySelector('.notes');

        if (!reasonWrap || !rejectField) return;

        if (numberValue(rejectField) > 0) {
            reasonWrap.classList.add('is-show');
        } else {
            reasonWrap.classList.remove('is-show');
        }
    }

    function updateSummary() {
        const okInputs = document.querySelectorAll('.qty-ok');
        const rejectInputs = document.querySelectorAll('.qty-reject');
        
        let totalOk = 0;
        let totalReject = 0;
        
        okInputs.forEach(el => totalOk += numberValue(el));
        rejectInputs.forEach(el => totalReject += numberValue(el));

        document.getElementById('kpi-ok').textContent = new Intl.NumberFormat('id-ID').format(totalOk);
        document.getElementById('kpi-reject').textContent = new Intl.NumberFormat('id-ID').format(totalReject);
    }""",
"""    function syncQty(changed, idx, max) {
        const okField = document.getElementById('ok_' + idx);
        const jahitField = document.getElementById('jahit_' + idx);
        const bahanField = document.getElementById('bahan_' + idx);

        let ok = numberValue(okField);
        let jahit = numberValue(jahitField);
        let bahan = numberValue(bahanField);

        if (changed === 'ok') {
            const remainder = Math.max(0, max - ok);
            if (jahit + bahan > remainder) {
                if (bahan > remainder) {
                    bahan = remainder;
                    jahit = 0;
                } else {
                    jahit = remainder - bahan;
                }
                jahitField.value = jahit > 0 ? jahit : '';
                bahanField.value = bahan > 0 ? bahan : '';
            }
        } else if (changed === 'jahit') {
            const remainder = Math.max(0, max - jahit);
            if (ok + bahan > remainder) {
                ok = remainder - bahan;
                if (ok < 0) {
                    ok = 0;
                    bahan = remainder;
                    bahanField.value = bahan > 0 ? bahan : '';
                }
                okField.value = ok > 0 ? ok : '';
            }
        } else if (changed === 'bahan') {
            const remainder = Math.max(0, max - bahan);
            if (ok + jahit > remainder) {
                ok = remainder - jahit;
                if (ok < 0) {
                    ok = 0;
                    jahit = remainder;
                    jahitField.value = jahit > 0 ? jahit : '';
                }
                okField.value = ok > 0 ? ok : '';
            }
        }

        updateSummary();
    }

    function updateSummary() {
        const okInputs = document.querySelectorAll('.fin-item:not([style*="display: none"]) .qty-ok, .qty-ok');
        const jahitInputs = document.querySelectorAll('.fin-item:not([style*="display: none"]) .qty-reject-jahit, .qty-reject-jahit');
        const bahanInputs = document.querySelectorAll('.fin-item:not([style*="display: none"]) .qty-reject-bahan, .qty-reject-bahan');
        
        let totalOk = 0;
        let totalReject = 0;
        
        okInputs.forEach(el => totalOk += numberValue(el));
        jahitInputs.forEach(el => totalReject += numberValue(el));
        bahanInputs.forEach(el => totalReject += numberValue(el));

        document.getElementById('kpi-ok').textContent = new Intl.NumberFormat('id-ID').format(totalOk);
        document.getElementById('kpi-reject').textContent = new Intl.NumberFormat('id-ID').format(totalReject);
    }""")

with open('resources/views/production/qc/sewing_edit.blade.php', 'w') as f:
    f.write(content)

