@php
    $tStoreId   = $targetStoreId ?? ($storeId ?? 'all');
    $tMode      = $targetRoasMode ?? 'auto';
    $tProfit    = $targetProfitPct ?? null;
    $tRoasMan   = $targetRoasManual ?? null;
    $tRoasAuto  = $targetRoasAuto ?? null;
    $tProfitVal = $tProfit !== null ? (float) $tProfit : 10.0;
    $tRoasVal   = $tRoasMan !== null ? (float) $tRoasMan : ($tRoasAuto !== null ? (float) $tRoasAuto : 7.0);
@endphp

@if($tStoreId !== 'all')
    <div class="ads-tab-panel mb-3 profit-fee-panel">
        <div class="ads-tab-panel-head">
            <div>
                <div class="ads-tab-panel-title">
                    <i class="bi bi-bullseye" style="color: var(--dsh-accent);"></i> Target Profit &amp; ROAS
                </div>
                <div class="ads-tab-panel-note">Target ROAS tampil sebagai acuan di semua tab. PPN iklan 11% sudah diperhitungkan.</div>
            </div>
        </div>
        <div class="p-3">
            <form id="adsTargetForm" method="POST" action="{{ route('marketplace.ads.target.setting') }}" class="profit-fee-form">
                @csrf
                <input type="hidden" name="store_id" value="{{ $tStoreId }}">

                {{-- Target profit --}}
                <div class="profit-fee-field">
                    <label class="profit-fee-label">Target Profit</label>
                    <div class="input-group profit-fee-editor" style="max-width:180px;">
                        <input id="adsTargetProfit" type="number" name="target_profit_pct" step="0.1" min="0" max="99"
                            value="{{ number_format($tProfitVal, 1, '.', '') }}" class="form-control">
                        <span class="input-group-text">%</span>
                    </div>
                </div>

                {{-- Mode --}}
                <div class="mt-3">
                    <label class="profit-fee-label">Sumber Target ROAS</label>
                    <div class="profit-fee-switch-row">
                        <span>Manual</span>
                        <label class="profit-fee-switch">
                            <input id="adsTargetModeToggle" type="checkbox" {{ $tMode !== 'manual' ? 'checked' : '' }}>
                            <span class="profit-fee-switch-slider"></span>
                        </label>
                        <span>Otomatis</span>
                        <input id="adsTargetMode" type="hidden" name="target_roas_mode" value="{{ $tMode !== 'manual' ? 'auto' : 'manual' }}">
                    </div>
                </div>

                {{-- Target ROAS --}}
                <div class="profit-fee-field mt-3">
                    <label class="profit-fee-label">Target ROAS</label>
                    <div class="input-group profit-fee-editor" style="max-width:180px;">
                        <input id="adsTargetRoas" type="number" name="target_roas" step="0.01" min="0" max="100"
                            value="{{ number_format($tRoasVal, 2, '.', '') }}" class="form-control">
                        <span class="input-group-text">x</span>
                    </div>
                    <div id="adsTargetRoasHint" class="ads-tab-panel-note mt-1">
                        @if($tRoasAuto !== null)
                            Otomatis dari target profit: <strong>{{ number_format($tRoasAuto, 2, ',', '.') }}x</strong>
                        @else
                            Isi target profit &amp; sinkronkan data agar ROAS otomatis bisa dihitung.
                        @endif
                    </div>
                </div>

                <div id="adsTargetStatus" class="mt-2" aria-live="polite" style="font-size:.78rem;"></div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        const form   = document.getElementById('adsTargetForm');
        const profit = document.getElementById('adsTargetProfit');
        const toggle = document.getElementById('adsTargetModeToggle');
        const mode   = document.getElementById('adsTargetMode');
        const roas   = document.getElementById('adsTargetRoas');
        const status = document.getElementById('adsTargetStatus');
        if (!form || !profit || !toggle || !mode || !roas || !status) return;

        // Rasio dari data agregat untuk hitung target ROAS otomatis (live).
        const netRatio  = Number(@json($adsNetRatio));
        const cogsRatio = Number(@json($adsCogsRatio));
        const hasRatios = Number.isFinite(netRatio) && Number.isFinite(cogsRatio) && netRatio > 0;
        let saving = false;

        function autoRoas() {
            if (!hasRatios) return null;
            const acos = (netRatio - cogsRatio - (Number(profit.value) || 0) / 100) / 1.11;
            return acos > 0 ? Math.round((1 / acos) * 100) / 100 : null;
        }

        function syncMode() {
            const m = toggle.checked ? 'auto' : 'manual';
            mode.value = m;
            const isAuto = m === 'auto';
            roas.disabled = isAuto;
            roas.style.opacity = isAuto ? '.6' : '1';
            if (isAuto) {
                const a = autoRoas();
                if (a !== null) roas.value = a.toFixed(2);
            }
        }

        function setStatus(msg, color) {
            status.textContent = msg;
            status.style.color = color || 'var(--dsh-muted)';
        }

        async function save() {
            if (saving) return;
            const p = Number(profit.value);
            if (!Number.isFinite(p) || p < 0 || p > 99) { setStatus('Target profit 0–99%.', '#b91c1c'); return; }
            if (mode.value === 'manual') {
                const r = Number(roas.value);
                if (!Number.isFinite(r) || r <= 0) { setStatus('Target ROAS harus > 0.', '#b91c1c'); return; }
            }
            saving = true;
            setStatus('Menyimpan…', '#2563eb');
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': form.querySelector('input[name="_token"]')?.value || '', 'Accept': 'application/json' },
                    body: new URLSearchParams(new FormData(form))
                });
                if (!res.ok) throw new Error('Gagal menyimpan target.');
                setStatus('Tersimpan.', '#15803d');
                setTimeout(() => window.location.reload(), 350);
            } catch (e) {
                setStatus(e.message, '#b91c1c');
            } finally {
                saving = false;
            }
        }

        toggle.addEventListener('change', () => { syncMode(); save(); });
        profit.addEventListener('change', () => { syncMode(); save(); });
        roas.addEventListener('change', () => { if (mode.value === 'manual') save(); });
        form.addEventListener('submit', (e) => { e.preventDefault(); save(); });

        syncMode();
    })();
    </script>
@endif
