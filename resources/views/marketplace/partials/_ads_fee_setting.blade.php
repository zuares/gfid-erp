@php
    $feeStoreId = $storeId ?? 'all';
    $feeAdsSetting = $adsSetting ?? (object) [];
    $feeMode = $feeAdsSetting->admin_fee_mode ?? 'auto';
    $feeManualPct = $feeAdsSetting->admin_fee_pct ?? null;
    $manualFeeValue = $feeManualPct !== null ? (float) $feeManualPct : 21.9;
    $autoFeeValue = (float) ($autoFeeValue ?? 21.9);
    $activeFeeValue = $feeMode === 'manual' ? $manualFeeValue : $autoFeeValue;
@endphp

@if($feeStoreId !== 'all')
    <div class="ads-tab-panel mb-3 profit-fee-panel">
        <div class="ads-tab-panel-head">
            <div>
                <div class="ads-tab-panel-title">
                    <i class="bi bi-percent" style="color: var(--dsh-accent);"></i> Fee Marketplace
                </div>
                <div class="ads-tab-panel-note">Menjadi acuan perhitungan seluruh tab.</div>
            </div>
        </div>
        <div class="p-3">
            <form id="profitAdminFeeForm" method="POST" action="{{ route('marketplace.ads.fee.setting') }}" class="profit-fee-form">
                @csrf
                <input type="hidden" name="store_id" value="{{ $feeStoreId }}">
                <div>
                    <label class="profit-fee-label">Mode</label>
                    <div class="profit-fee-switch-row">
                        <span>Manual</span>
                        <label class="profit-fee-switch">
                            <input id="profitAdminFeeModeToggle" type="checkbox" {{ $feeMode !== 'manual' ? 'checked' : '' }}>
                            <span class="profit-fee-switch-slider"></span>
                        </label>
                        <span>Otomatis</span>
                        <input id="profitAdminFeeMode" type="hidden" name="admin_fee_mode" value="{{ $feeMode !== 'manual' ? 'auto' : 'manual' }}">
                    </div>
                </div>
                <div class="profit-fee-field">
                    <label class="profit-fee-label">Admin Fee</label>
                    <div id="profitAdminFeeDisplay" class="profit-fee-display" role="button" tabindex="0">
                        {{ number_format($activeFeeValue, 1, ',', '.') }}%
                    </div>
                    <div id="profitAdminFeeEditor" class="input-group profit-fee-editor" style="display:none;">
                        <input id="profitAdminFeePct" type="number" name="admin_fee_pct" step="0.1" min="0" max="99" value="{{ number_format($activeFeeValue, 1, '.', '') }}" class="form-control">
                        <span class="input-group-text">%</span>
                    </div>
                    <div id="profitAdminFeeStatus" aria-live="polite"></div>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        const form = document.getElementById('profitAdminFeeForm');
        const display = document.getElementById('profitAdminFeeDisplay');
        const editor = document.getElementById('profitAdminFeeEditor');
        const modeToggle = document.getElementById('profitAdminFeeModeToggle');
        const modeInput = document.getElementById('profitAdminFeeMode');
        const input = document.getElementById('profitAdminFeePct');
        const status = document.getElementById('profitAdminFeeStatus');
        if (!form || !display || !editor || !modeToggle || !modeInput || !input || !status) return;
        let timer = null;
        let saving = false;
        const autoFeeValue = Number({{ (float) $autoFeeValue }});
        let manualFeeValue = Number({{ (float) $manualFeeValue }});

        function setStatus(message, color) {
            status.textContent = message;
            status.style.color = color || 'var(--dsh-muted)';
            status.style.display = message ? 'block' : 'none';
        }

        function syncModeState() {
            const mode = modeToggle.checked ? 'auto' : 'manual';
            modeInput.value = mode;
            const value = mode === 'auto' ? autoFeeValue : manualFeeValue;
            input.value = value.toFixed(1);
            display.textContent = value.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
            input.disabled = mode !== 'manual';
            input.style.opacity = mode === 'manual' ? '1' : '.6';
            display.style.cursor = mode === 'manual' ? 'pointer' : 'default';
        }

        function openEditor() {
            if (modeToggle.checked) return;
            display.style.display = 'none';
            editor.style.display = 'flex';
            input.focus();
            input.select();
        }

        async function saveFee() {
            if (saving) return;
            const value = Number(input.value);
            if (!Number.isFinite(value) || value < 0 || value > 99) {
                setStatus('Fee 0–99%.', '#b91c1c');
                return;
            }
            saving = true;
            setStatus('Menyimpan…', '#2563eb');
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': form.querySelector('input[name="_token"]')?.value || '', 'Accept': 'application/json' },
                    body: new URLSearchParams(new FormData(form))
                });
                if (!response.ok) throw new Error('Gagal menyimpan fee.');
                setStatus('', '#15803d');
                setTimeout(() => window.location.reload(), 350);
            } catch (error) {
                setStatus(error.message, '#b91c1c');
            } finally {
                saving = false;
            }
        }

        form.addEventListener('submit', (event) => { event.preventDefault(); saveFee(); });
        display.addEventListener('click', openEditor);
        display.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') openEditor();
        });
        input.addEventListener('change', () => {
            manualFeeValue = Number(input.value);
            saveFee();
        });
        modeToggle.addEventListener('change', () => {
            syncModeState();
            clearTimeout(timer);
            timer = setTimeout(saveFee, 120);
        });
        syncModeState();
    })();
    </script>
@endif
