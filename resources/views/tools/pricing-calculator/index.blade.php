@extends('layouts.app')

@section('title', 'Pricing & ROAS Calculator')

@push('head')
    <style>
        .pc-wrap {
            max-width: 1180px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        .pc-head h1 {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0;
            color: var(--text);
        }

        .pc-head p {
            margin: .15rem 0 0;
            font-size: .82rem;
            color: var(--muted);
        }

        .pc-grid {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 1rem;
            margin-top: 1rem;
            align-items: start;
        }

        @media (max-width: 991px) {
            .pc-grid {
                grid-template-columns: 1fr;
            }
        }

        .pc-card {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .28);
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06), 0 0 0 1px rgba(15, 23, 42, .03);
            padding: 1rem 1.05rem;
        }

        @media (min-width: 992px) {
            .pc-sticky {
                position: sticky;
                top: 1rem;
            }
        }

        /* Mode toggle */
        .pc-modes {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .35rem;
            background: var(--card-soft);
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 12px;
            padding: .3rem;
            margin-bottom: 1rem;
        }

        .pc-modes button {
            border: 0;
            background: transparent;
            border-radius: 9px;
            padding: .5rem .4rem;
            font-size: .82rem;
            font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            transition: .15s;
        }

        .pc-modes button.active {
            background: var(--card);
            color: var(--accent);
            box-shadow: 0 2px 8px rgba(15, 23, 42, .08);
        }

        .pc-field {
            margin-bottom: .85rem;
        }

        .pc-field label {
            display: block;
            font-size: .76rem;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: .3rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .pc-field-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .3rem;
        }

        .pc-field-head label {
            margin: 0;
        }

        .pc-unit-toggle {
            display: inline-flex;
            border: 1px solid rgba(148, 163, 184, .4);
            border-radius: 999px;
            overflow: hidden;
        }

        .pc-unit-toggle button {
            border: 0;
            background: transparent;
            color: var(--muted);
            font-size: .7rem;
            font-weight: 700;
            padding: .12rem .55rem;
            cursor: pointer;
            transition: .12s;
        }

        .pc-unit-toggle button.active {
            background: var(--accent);
            color: #fff;
        }

        .pc-fee-hint {
            font-size: .72rem;
            color: var(--accent);
            margin-top: .3rem;
            min-height: .9rem;
        }

        /* Toggle switch PPN */
        .pc-switch {
            display: flex;
            align-items: center;
            gap: .6rem;
            cursor: pointer;
            margin-top: .35rem;
            padding: .6rem .7rem;
            border-radius: 12px;
            background: var(--card-soft);
            border: 1px solid rgba(148, 163, 184, .22);
        }

        .pc-switch input {
            display: none;
        }

        .pc-switch-track {
            flex: 0 0 auto;
            width: 38px;
            height: 22px;
            border-radius: 999px;
            background: rgba(148, 163, 184, .5);
            position: relative;
            transition: .18s;
        }

        .pc-switch-knob {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #fff;
            transition: .18s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .25);
        }

        .pc-switch input:checked + .pc-switch-track {
            background: var(--accent);
        }

        .pc-switch input:checked + .pc-switch-track .pc-switch-knob {
            left: 18px;
        }

        .pc-switch-label {
            font-size: .8rem;
            font-weight: 600;
            color: var(--text);
            line-height: 1.15;
        }

        .pc-switch-label small {
            display: block;
            font-size: .68rem;
            font-weight: 500;
            color: var(--muted);
        }

        .pc-cap {
            font-size: .72rem;
            color: var(--muted);
            margin: .4rem 0 0;
        }

        .pc-input {
            position: relative;
            display: flex;
            align-items: center;
        }

        .pc-input .affix {
            position: absolute;
            font-size: .9rem;
            color: var(--muted);
            pointer-events: none;
        }

        .pc-input .affix.pre {
            left: .7rem;
        }

        .pc-input .affix.suf {
            right: .7rem;
        }

        .pc-input input {
            width: 100%;
            border-radius: 11px;
            border: 1px solid rgba(148, 163, 184, .4);
            background: var(--card-soft);
            color: var(--text);
            font-size: 1.02rem;
            font-weight: 600;
            padding: .6rem .75rem;
            outline: none;
            transition: .15s;
        }

        .pc-input.has-pre input {
            padding-left: 2.1rem;
        }

        .pc-input.has-suf input {
            padding-right: 2rem;
        }

        .pc-input input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-soft);
        }

        /* Presets */
        .pc-presets {
            display: flex;
            flex-wrap: wrap;
            gap: .3rem;
            margin-top: .4rem;
        }

        .pc-preset {
            border: 1px solid rgba(148, 163, 184, .4);
            background: transparent;
            color: var(--muted);
            border-radius: 999px;
            padding: .2rem .6rem;
            font-size: .74rem;
            font-weight: 600;
            cursor: pointer;
            transition: .12s;
        }

        .pc-preset:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .pc-preset.active {
            background: var(--accent-soft);
            border-color: var(--accent);
            color: var(--accent);
        }

        /* Result highlight */
        .pc-hl {
            text-align: center;
            padding: 1.1rem .75rem 1.25rem;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--accent-soft), transparent);
            border: 1px solid rgba(148, 163, 184, .25);
            margin-bottom: 1rem;
        }

        .pc-hl .lbl {
            font-size: .74rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .pc-hl .val {
            font-size: 2.4rem;
            font-weight: 800;
            line-height: 1.05;
            color: var(--text);
            margin: .25rem 0;
        }

        .pc-hl .sub {
            font-size: .8rem;
            color: var(--muted);
        }

        .pc-chips {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            justify-content: center;
            margin-top: .85rem;
        }

        .pc-chip {
            border: 1px solid var(--accent);
            color: var(--accent);
            background: var(--card);
            border-radius: 10px;
            padding: .4rem .7rem;
            font-size: .9rem;
            font-weight: 700;
            cursor: pointer;
            transition: .12s;
        }

        .pc-chip:hover {
            background: var(--accent);
            color: #fff;
        }

        /* Detail grid */
        .pc-metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: .6rem;
        }

        @media (max-width: 575px) {
            .pc-metrics {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .pc-metric {
            background: var(--card-soft);
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 12px;
            padding: .6rem .7rem;
        }

        .pc-metric .m-lbl {
            font-size: .68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: var(--muted);
        }

        .pc-metric .m-val {
            font-size: 1.02rem;
            font-weight: 700;
            color: var(--text);
            margin-top: .15rem;
        }

        .pc-metric .m-sub {
            font-size: .72rem;
            color: var(--muted);
            margin-top: .05rem;
        }

        /* Badges status */
        .pc-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .3rem .7rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .pc-badge.aman {
            background: rgba(16, 185, 129, .14);
            border-color: rgba(16, 185, 129, .4);
            color: #059669;
        }

        .pc-badge.mepet {
            background: rgba(245, 158, 11, .16);
            border-color: rgba(245, 158, 11, .4);
            color: #b45309;
        }

        .pc-badge.rugi {
            background: rgba(239, 68, 68, .14);
            border-color: rgba(239, 68, 68, .4);
            color: #dc2626;
        }

        /* Warnings */
        .pc-warn {
            display: flex;
            gap: .5rem;
            align-items: flex-start;
            background: rgba(239, 68, 68, .1);
            border: 1px solid rgba(239, 68, 68, .35);
            color: #b91c1c;
            border-radius: 11px;
            padding: .6rem .75rem;
            font-size: .82rem;
            margin-bottom: .85rem;
        }

        body[data-theme="dark"] .pc-warn {
            color: #fca5a5;
        }

        /* Sub sections */
        .pc-sub-title {
            font-size: .82rem;
            font-weight: 700;
            color: var(--text);
            margin: 1.15rem 0 .5rem;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .pc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
        }

        .pc-table th {
            text-align: right;
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--muted);
            padding: .4rem .5rem;
            border-bottom: 1px solid rgba(148, 163, 184, .25);
        }

        .pc-table th:first-child,
        .pc-table td:first-child {
            text-align: left;
        }

        .pc-table td {
            padding: .45rem .5rem;
            text-align: right;
            color: var(--text);
            border-bottom: 1px solid rgba(148, 163, 184, .14);
        }

        .pc-table tr.hi td {
            background: var(--accent-soft);
            font-weight: 700;
        }

        .pc-collapse-btn {
            width: 100%;
            text-align: left;
            border: 1px dashed rgba(148, 163, 184, .5);
            background: transparent;
            color: var(--muted);
            border-radius: 11px;
            padding: .55rem .75rem;
            font-size: .82rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1.2rem;
        }

        .pc-range-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .6rem;
            margin: .75rem 0;
        }

        /* Mode-conditional visibility */
        .pc-body.mode-determine .only-analyze {
            display: none;
        }

        .pc-body.mode-analyze .only-determine {
            display: none;
        }
    </style>
@endpush

@section('content')
    <div class="pc-wrap">
        <div class="pc-head">
            <h1>Pricing &amp; ROAS Calculator</h1>
            <p>Tentukan harga jual dari HPP, hitung fee marketplace, budget iklan, target ROAS &amp; net profit — realtime.</p>
        </div>

        <div class="pc-body mode-determine" id="pcBody">
            <div class="pc-grid">
                {{-- ============ INPUT ============ --}}
                <div class="pc-card">
                    <div class="pc-modes">
                        <button type="button" class="active" data-mode="determine">Tentukan Harga Jual</button>
                        <button type="button" data-mode="analyze">Analisa Harga</button>
                    </div>

                    {{-- HPP --}}
                    <div class="pc-field">
                        <label>HPP</label>
                        <div class="pc-input has-pre">
                            <span class="affix pre">Rp</span>
                            <input type="text" id="inpHpp" inputmode="numeric" autocomplete="off"
                                class="js-money" placeholder="45.000" value="45.000">
                        </div>
                    </div>

                    {{-- Harga Jual (analyze only) --}}
                    <div class="pc-field only-analyze">
                        <label>Harga Jual</label>
                        <div class="pc-input has-pre">
                            <span class="affix pre">Rp</span>
                            <input type="text" id="inpSelling" inputmode="numeric" autocomplete="off"
                                class="js-money" placeholder="85.950">
                        </div>
                    </div>

                    {{-- Fee marketplace --}}
                    <div class="pc-field">
                        <div class="pc-field-head">
                            <label>Fee Marketplace</label>
                            <div class="pc-unit-toggle" id="feeUnitToggle">
                                <button type="button" data-unit="pct" class="active">%</button>
                                <button type="button" data-unit="rp">Rp</button>
                            </div>
                        </div>

                        {{-- input persen --}}
                        <div class="pc-input has-suf" data-fee-input="pct">
                            <span class="affix suf">%</span>
                            <input type="text" id="inpFee" inputmode="decimal" autocomplete="off"
                                class="js-dec" value="{{ $defaults['fee_pct'] }}">
                        </div>

                        {{-- input rupiah --}}
                        <div class="pc-input has-pre" data-fee-input="rp" style="display:none">
                            <span class="affix pre">Rp</span>
                            <input type="text" id="inpFeeRp" inputmode="numeric" autocomplete="off"
                                class="js-money" placeholder="18.000">
                        </div>

                        <div class="pc-fee-hint" id="feeHint"></div>

                        <div class="pc-presets" data-preset-for="inpFee" id="feePresets">
                            <button type="button" class="pc-preset" data-val="20">20%</button>
                            <button type="button" class="pc-preset" data-val="21">21%</button>
                            <button type="button" class="pc-preset" data-val="21.17">21.17%</button>
                            <button type="button" class="pc-preset" data-val="22">22%</button>
                        </div>
                    </div>

                    {{-- Target profit --}}
                    <div class="pc-field">
                        <label>Target Profit</label>
                        <div class="pc-input has-suf">
                            <span class="affix suf">%</span>
                            <input type="text" id="inpProfit" inputmode="decimal" autocomplete="off"
                                class="js-dec" value="{{ $defaults['profit_pct'] }}">
                        </div>
                        <div class="pc-presets" data-preset-for="inpProfit">
                            <button type="button" class="pc-preset" data-val="5">5%</button>
                            <button type="button" class="pc-preset" data-val="10">10%</button>
                            <button type="button" class="pc-preset" data-val="15">15%</button>
                            <button type="button" class="pc-preset" data-val="20">20%</button>
                        </div>
                    </div>

                    {{-- Target ROAS (determine only) --}}
                    <div class="pc-field only-determine">
                        <label>Target ROAS</label>
                        <div class="pc-input has-suf">
                            <span class="affix suf">x</span>
                            <input type="text" id="inpRoas" inputmode="decimal" autocomplete="off"
                                class="js-dec" value="{{ $defaults['roas'] }}">
                        </div>
                        <div class="pc-presets" data-preset-for="inpRoas">
                            <button type="button" class="pc-preset" data-val="5">5x</button>
                            <button type="button" class="pc-preset" data-val="6">6x</button>
                            <button type="button" class="pc-preset" data-val="7">7x</button>
                            <button type="button" class="pc-preset" data-val="8">8x</button>
                            <button type="button" class="pc-preset" data-val="10">10x</button>
                        </div>
                    </div>

                    {{-- PPN iklan --}}
                    <label class="pc-switch">
                        <input type="checkbox" id="inpPpn" checked>
                        <span class="pc-switch-track"><span class="pc-switch-knob"></span></span>
                        <span class="pc-switch-label">Biaya iklan kena PPN 11%
                            <small>ROAS platform belum termasuk PPN</small></span>
                    </label>
                </div>

                {{-- ============ RESULT ============ --}}
                <div>
                    <div class="pc-card pc-sticky">
                        <div id="pcWarnings"></div>

                        <div class="pc-hl">
                            <div class="lbl" id="hlLabel">Harga Jual Rekomendasi</div>
                            <div class="val" id="hlValue">—</div>
                            <div class="sub" id="hlSub"></div>
                            <div class="pc-chips only-determine" id="hlChips"></div>
                        </div>

                        <div style="margin-bottom:.85rem" id="statusRow"></div>

                        <div class="pc-metrics" id="pcMetrics"></div>

                        {{-- Simulasi ROAS --}}
                        <div class="pc-sub-title"><i class="bi bi-graph-up-arrow"></i> Simulasi ROAS</div>
                        <table class="pc-table" id="roasTable">
                            <thead>
                                <tr><th>ROAS</th><th>Profit/order</th><th>Margin</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <p class="pc-cap" id="roasCap"></p>

                        {{-- Range HPP (collapsible) --}}
                        <button type="button" class="pc-collapse-btn" id="rangeToggle">
                            <i class="bi bi-sliders"></i> Simulasi Range HPP (opsional)
                        </button>
                        <div id="rangePanel" style="display:none">
                            <div class="pc-range-inputs">
                                <div class="pc-field" style="margin:0">
                                    <label>HPP Minimum</label>
                                    <div class="pc-input has-pre">
                                        <span class="affix pre">Rp</span>
                                        <input type="text" id="inpHppMin" inputmode="numeric" class="js-money" placeholder="43.000">
                                    </div>
                                </div>
                                <div class="pc-field" style="margin:0">
                                    <label>HPP Maximum</label>
                                    <div class="pc-input has-pre">
                                        <span class="affix pre">Rp</span>
                                        <input type="text" id="inpHppMax" inputmode="numeric" class="js-money" placeholder="50.000">
                                    </div>
                                </div>
                            </div>
                            <table class="pc-table" id="hppTable">
                                <thead>
                                    <tr><th>HPP</th><th>ROAS Minimum</th><th>Budget Iklan Maks</th></tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <p style="font-size:.74rem;color:var(--muted);margin:.5rem 0 0">
                                ROAS minimum = ROAS terkecil agar target profit tetap tercapai pada tiap level HPP.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @verbatim
    <script>
    (function () {
        'use strict';

        const body = document.getElementById('pcBody');
        const el = (id) => document.getElementById(id);

        const inpHpp = el('inpHpp'), inpSelling = el('inpSelling'),
              inpFee = el('inpFee'), inpFeeRp = el('inpFeeRp'),
              inpProfit = el('inpProfit'), inpRoas = el('inpRoas'),
              inpPpn = el('inpPpn'),
              inpHppMin = el('inpHppMin'), inpHppMax = el('inpHppMax');

        const PPN = 11; // %
        let mode = 'determine';
        let feeUnit = 'pct'; // 'pct' atau 'rp'

        // Faktor pengali biaya iklan akibat PPN (1.11 bila aktif).
        const adFactor = () => inpPpn.checked ? (1 + PPN / 100) : 1;

        // ---------- Parsing & format ----------
        const parseMoney = (v) => { const d = (v || '').replace(/\D/g, ''); return d ? parseInt(d, 10) : 0; };
        const parseDec = (v) => { const n = parseFloat((v || '').replace(',', '.').replace(/[^0-9.]/g, '')); return isFinite(n) && n > 0 ? n : 0; };
        const fmtThousand = (digits) => digits ? Number(digits).toLocaleString('id-ID') : '';
        const fmtRp = (n) => 'Rp' + Math.round(n).toLocaleString('id-ID');
        const fmtPct = (p) => (p).toFixed(2) + '%';
        const fmtRoas = (r) => (r === null || !isFinite(r)) ? '–' : r.toFixed(2) + 'x';

        // ---------- Calculation core (mirror PricingCalculatorService) ----------
        function suggestPrices(price, count = 4) {
            if (price <= 0) return [];
            let base = Math.floor(price / 1000) * 1000 + 950;
            if (base < price) base += 1000;
            const out = [];
            for (let i = 0; i < count; i++) out.push(base + i * 1000);
            return out;
        }

        function statusOf(sp, hpp, netAfterFee, maxAd) {
            if (sp < hpp || (netAfterFee - hpp) <= 0) return 'rugi';
            if (maxAd <= 0) return 'mepet';
            return 'aman';
        }

        // ---------- Render ----------
        // Tentukan fee% efektif. Bila user input dalam Rupiah, hitung persennya.
        function resolveFee(hpp, profit, roas) {
            if (feeUnit === 'pct') {
                el('feeHint').textContent = '';
                return parseDec(inpFee.value);
            }

            const feeRp = parseMoney(inpFeeRp.value);
            if (feeRp <= 0) { el('feeHint').textContent = ''; return 0; }

            if (mode === 'analyze') {
                const spIn = parseMoney(inpSelling.value);
                if (spIn <= 0) {
                    el('feeHint').textContent = 'Isi harga jual dulu untuk menghitung %';
                    return 0;
                }
                const pct = feeRp / spIn * 100;
                el('feeHint').textContent = '≈ ' + pct.toFixed(2) + '% dari harga jual ' + fmtRp(spIn);
                return pct;
            }

            // mode determine: harga jual = (HPP + feeRp) / (1 - profit - factor/ROAS)
            const denomBase = 1 - profit / 100 - (roas > 0 ? adFactor() / roas : 1);
            if (denomBase <= 0 || (hpp + feeRp) <= 0) {
                el('feeHint').textContent = '';
                return 0;
            }
            const s = (hpp + feeRp) / denomBase;
            const pct = feeRp / s * 100;
            el('feeHint').textContent = '≈ ' + pct.toFixed(2) + '% dari harga rekomendasi ' + fmtRp(s);
            return pct;
        }

        function render() {
            const hpp = parseMoney(inpHpp.value);
            const profit = parseDec(inpProfit.value);
            const roas = parseDec(inpRoas.value);
            const fee = resolveFee(hpp, profit, roas);

            const warnings = [];
            let sp = 0;
            let hlLabel = 'Harga Jual Rekomendasi';

            if (mode === 'determine') {
                const share = fee / 100 + profit / 100 + (roas > 0 ? adFactor() / roas : 1);
                if (share >= 1) {
                    warnings.push('Target tidak memungkinkan. Kurangi fee, target profit, atau naikkan target ROAS.');
                } else if (hpp > 0) {
                    sp = hpp / (1 - share);
                }
            } else {
                hlLabel = 'Harga Jual Dianalisa';
                sp = parseMoney(inpSelling.value);
                if (sp > 0 && hpp > 0 && sp < hpp) {
                    warnings.push('Harga jual berada di bawah HPP.');
                }
            }

            // Derived metrics from reference selling price
            const feeRp = sp * fee / 100;
            const netAfterFee = sp - feeRp;
            const targetProfitRp = sp * profit / 100;
            const maxAd = sp - feeRp - hpp - targetProfitRp;
            const minRoas = maxAd > 0 ? sp * adFactor() / maxAd : null;
            const breakEven = (netAfterFee - hpp) > 0 ? sp * adFactor() / (netAfterFee - hpp) : null;

            if (sp > 0 && maxAd <= 0 && (netAfterFee - hpp) > 0) {
                warnings.push('Tidak ada ruang untuk biaya iklan pada harga jual ini (target profit terlalu tinggi).');
            }

            // --- Warnings ---
            el('pcWarnings').innerHTML = warnings.map(w =>
                '<div class="pc-warn"><i class="bi bi-exclamation-triangle"></i><span>' + w + '</span></div>'
            ).join('');

            // --- Highlight ---
            el('hlLabel').textContent = hlLabel;
            const hasResult = sp > 0 && warnings.indexOf('Target tidak memungkinkan. Kurangi fee, target profit, atau naikkan target ROAS.') === -1;
            el('hlValue').textContent = hasResult ? fmtRp(sp) : '—';
            el('hlSub').textContent = (mode === 'determine' && hasResult)
                ? ('Minimal agar target profit ' + profit + '% tercapai dengan ROAS ' + (roas || 0) + 'x')
                : (mode === 'analyze' && sp > 0 ? 'Analisa untuk harga ini' : '');

            // --- Quick price chips (determine) ---
            const chips = (mode === 'determine' && hasResult) ? suggestPrices(sp) : [];
            el('hlChips').innerHTML = chips.map(p =>
                '<button type="button" class="pc-chip" data-price="' + p + '">' + fmtRp(p) + '</button>'
            ).join('');

            // --- Status badge ---
            if (sp > 0) {
                const st = statusOf(sp, hpp, netAfterFee, maxAd);
                const map = { aman: 'AMAN', mepet: 'MEPET', rugi: 'RUGI' };
                const icon = { aman: 'bi-check-circle', mepet: 'bi-exclamation-circle', rugi: 'bi-x-circle' };
                el('statusRow').innerHTML = '<span class="pc-badge ' + st + '"><i class="bi ' + icon[st] + '"></i> ' + map[st] + '</span>';
            } else {
                el('statusRow').innerHTML = '';
            }

            // --- Metrics grid ---
            const targetRoasVal = mode === 'determine' ? (roas > 0 ? roas : null) : minRoas;
            const roasAfterPpn = (targetRoasVal !== null && inpPpn.checked) ? targetRoasVal / adFactor() : null;
            const metrics = [
                { l: 'HPP', v: fmtRp(hpp) },
                { l: 'Fee Marketplace', v: fmtRp(feeRp), s: fmtPct(fee) },
                { l: 'Budget Iklan Maksimal', v: maxAd > 0 ? fmtRp(maxAd) : 'Rp0', s: '/ order' },
                { l: mode === 'determine' ? 'Target ROAS' : 'ROAS Minimum', v: fmtRoas(targetRoasVal), s: 'sebelum PPN' },
            ];
            if (inpPpn.checked) {
                metrics.push({ l: 'ROAS Setelah PPN 11%', v: fmtRoas(roasAfterPpn), s: 'atas biaya riil' });
            }
            metrics.push(
                { l: 'Target Net Profit', v: fmtRp(targetProfitRp), s: fmtPct(profit) },
                { l: 'Pendapatan Bersih Stlh Fee', v: fmtRp(netAfterFee) },
                { l: 'Break Even ROAS', v: fmtRoas(breakEven) },
            );
            el('pcMetrics').innerHTML = metrics.map(m =>
                '<div class="pc-metric"><div class="m-lbl">' + m.l + '</div><div class="m-val">' + m.v + '</div>' +
                (m.s ? '<div class="m-sub">' + m.s + '</div>' : '') + '</div>'
            ).join('');

            // --- ROAS simulation ---
            const roasLevels = [4, 5, 6, 7, 8, 9, 10];
            const activeRoas = mode === 'determine' ? Math.round(roas) : null;
            el('roasTable').querySelector('tbody').innerHTML = (sp > 0 ? roasLevels : []).map(r => {
                const profitOrder = sp - feeRp - hpp - sp * adFactor() / r;
                const margin = sp > 0 ? profitOrder / sp * 100 : 0;
                const hi = r === activeRoas ? ' class="hi"' : '';
                return '<tr' + hi + '><td>' + r + 'x</td><td>' + fmtRp(profitOrder) + '</td><td>' + margin.toFixed(1) + '%</td></tr>';
            }).join('') || '<tr><td colspan="3" style="text-align:center;color:var(--muted)">Isi HPP untuk melihat simulasi</td></tr>';

            el('roasCap').textContent = inpPpn.checked
                ? 'Biaya iklan sudah termasuk PPN 11% (ROAS = angka platform, sebelum PPN).'
                : 'PPN iklan tidak diperhitungkan.';

            renderHppRange(sp, fee, profit);
        }

        // --- Range HPP simulation ---
        function renderHppRange(sp, fee, profit) {
            const tbody = el('hppTable').querySelector('tbody');
            const lo = parseMoney(inpHppMin.value);
            const hi = parseMoney(inpHppMax.value);
            if (sp <= 0 || lo <= 0 || hi <= 0 || hi < lo) {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:var(--muted)">Isi HPP min & max</td></tr>';
                return;
            }
            const steps = 5;
            const rows = [];
            for (let i = 0; i < steps; i++) {
                const hpp = Math.round(lo + (hi - lo) * i / (steps - 1));
                const feeRp = sp * fee / 100;
                const targetProfitRp = sp * profit / 100;
                const maxAd = sp - feeRp - hpp - targetProfitRp;
                const minRoas = maxAd > 0 ? sp * adFactor() / maxAd : null;
                rows.push('<tr><td>' + fmtRp(hpp) + '</td><td>' + fmtRoas(minRoas) + '</td><td>' +
                    (maxAd > 0 ? fmtRp(maxAd) : 'Rp0') + '</td></tr>');
            }
            tbody.innerHTML = rows.join('');
        }

        // ---------- Wiring ----------
        // Money inputs: live thousand separator, digits-only, no negatives
        document.querySelectorAll('.js-money').forEach(inp => {
            inp.addEventListener('input', () => {
                const pos = inp.value.length - inp.selectionStart;
                inp.value = fmtThousand(inp.value.replace(/\D/g, ''));
                inp.setSelectionRange(inp.value.length - pos, inp.value.length - pos);
                render();
            });
            inp.addEventListener('focus', () => { if (inp.value) inp.select(); });
        });

        // Decimal inputs: allow digits + one dot
        document.querySelectorAll('.js-dec').forEach(inp => {
            inp.addEventListener('input', () => {
                let v = inp.value.replace(',', '.').replace(/[^0-9.]/g, '');
                const parts = v.split('.');
                if (parts.length > 2) v = parts[0] + '.' + parts.slice(1).join('');
                inp.value = v;
                render();
            });
            inp.addEventListener('focus', () => { if (inp.value) inp.select(); });
        });

        // Presets
        document.querySelectorAll('.pc-presets').forEach(group => {
            const targetId = group.getAttribute('data-preset-for');
            group.querySelectorAll('.pc-preset').forEach(btn => {
                btn.addEventListener('click', () => {
                    el(targetId).value = btn.getAttribute('data-val');
                    group.querySelectorAll('.pc-preset').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    render();
                });
            });
        });

        // Fee unit toggle (% / Rp)
        el('feeUnitToggle').querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
                feeUnit = btn.getAttribute('data-unit');
                el('feeUnitToggle').querySelectorAll('button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                document.querySelector('[data-fee-input="pct"]').style.display = feeUnit === 'pct' ? '' : 'none';
                document.querySelector('[data-fee-input="rp"]').style.display = feeUnit === 'rp' ? '' : 'none';
                el('feePresets').style.display = feeUnit === 'pct' ? '' : 'none';
                render();
            });
        });

        // Mode toggle
        document.querySelectorAll('.pc-modes button').forEach(btn => {
            btn.addEventListener('click', () => {
                mode = btn.getAttribute('data-mode');
                document.querySelectorAll('.pc-modes button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                body.className = 'pc-body mode-' + mode;
                render();
            });
        });

        // Chip click -> analyze that price
        el('hlChips').addEventListener('click', (e) => {
            const chip = e.target.closest('.pc-chip');
            if (!chip) return;
            inpSelling.value = fmtThousand(chip.getAttribute('data-price'));
            document.querySelector('.pc-modes button[data-mode="analyze"]').click();
        });

        // PPN toggle
        inpPpn.addEventListener('change', render);

        // Range toggle
        el('rangeToggle').addEventListener('click', () => {
            const p = el('rangePanel');
            p.style.display = p.style.display === 'none' ? 'block' : 'none';
        });
        [inpHppMin, inpHppMax].forEach(i => i.addEventListener('input', render));

        render();
    })();
    </script>
    @endverbatim
@endpush
