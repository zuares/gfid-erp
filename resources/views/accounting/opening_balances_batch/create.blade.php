@extends('layouts.app')

@section('title', 'Accounting • Saldo Awal')

@push('head')
@include('production.dashboard.partials._gf-styles')
<style>
    :root {
        --r: 14px;
        --b: rgba(148,163,184,.22);
        --muted: #6b7280;
        --shadow: 0 10px 26px rgba(15,23,42,.08), 0 0 0 1px rgba(15,23,42,.03);
    }
    .page-wrap { max-width: 680px; margin: 0 auto; padding: 14px 12px 96px; display: grid; gap: 1rem; }

    /* Panel */
    .panel { background: var(--card); border: 1px solid var(--b); border-radius: var(--r); box-shadow: var(--shadow); }
    .panel-h { padding: 10px 14px; border-bottom: 1px solid rgba(148,163,184,.12); display: flex; justify-content: space-between; align-items: center; gap: 10px; }
    .panel-b { padding: 12px 14px; }
    .panel-title { font-weight: 900; font-size: .95rem; margin: 0; }
    .panel-sub { font-size: .72rem; color: var(--muted); font-weight: 700; }

    /* Top info */
    .top-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .65rem; }
    .field label { display: block; font-size: .68rem; font-weight: 900; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: .22rem; }
    .field .form-control, .field .form-select { font-size: .88rem; border-radius: 10px; padding: .38rem .52rem; }

    /* Balance indicator */
    .balance-bar {
        display: flex; justify-content: space-between; align-items: center;
        border: 1px solid rgba(148,163,184,.18); border-radius: 12px;
        padding: .65rem .85rem; background: rgba(148,163,184,.04);
    }
    .balance-col { text-align: center; }
    .balance-lbl { font-size: .58rem; font-weight: 900; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; display: block; }
    .balance-val { font-size: 1rem; font-weight: 950; font-variant-numeric: tabular-nums; }
    .balance-state {
        display: inline-flex; align-items: center; gap: .3rem;
        border-radius: 999px; padding: .22rem .65rem;
        font-size: .72rem; font-weight: 900;
        background: #fef3c7; border: 1px solid #fde68a; color: #92400e;
    }
    .balance-state.ok { background: #dcfce7; border-color: #86efac; color: #166534; }

    /* Group header */
    .group-header {
        display: flex; align-items: center; gap: .5rem;
        padding: .35rem 0 .18rem;
        font-size: .68rem; font-weight: 900; color: var(--muted);
        text-transform: uppercase; letter-spacing: .08em;
        border-bottom: 1px solid rgba(148,163,184,.12);
        margin-bottom: .35rem;
    }
    .group-header .dot { width: 7px; height: 7px; border-radius: 50%; flex: 0 0 auto; }
    .dot-asset { background: #2563eb; }
    .dot-liability { background: #dc2626; }
    .dot-equity { background: #16a34a; }

    /* Account row */
    .acc-row {
        display: flex; align-items: center; gap: .6rem;
        padding: .45rem .52rem;
        border: 1px solid rgba(148,163,184,.15);
        border-radius: 10px; margin-bottom: .32rem;
        background: var(--card);
        transition: border-color .15s, background .15s;
    }
    .acc-row:has(.saldo-input:not([value="0"]):not([value=""])):has(.saldo-input[value]) {
        border-color: rgba(37,99,235,.25);
        background: rgba(37,99,235,.02);
    }
    .acc-info { flex: 1; min-width: 0; }
    .acc-code { font-size: .65rem; font-weight: 900; color: var(--muted); font-variant-numeric: tabular-nums; }
    .acc-name { font-size: .84rem; font-weight: 900; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .acc-type-badge {
        font-size: .58rem; font-weight: 900; padding: .08rem .38rem;
        border-radius: 999px; white-space: nowrap; flex: 0 0 auto;
    }
    .badge-asset { background: rgba(37,99,235,.08); color: #2563eb; }
    .badge-liability { background: rgba(220,38,38,.08); color: #dc2626; }
    .badge-equity { background: rgba(22,163,74,.08); color: #16a34a; }

    .saldo-wrap { flex: 0 0 140px; }
    .saldo-input {
        width: 100%; text-align: right; font-weight: 900; font-size: .9rem;
        font-variant-numeric: tabular-nums;
        border: 1px solid rgba(148,163,184,.25); border-radius: 9px;
        padding: .32rem .5rem; background: var(--card); color: var(--text);
    }
    .saldo-input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,.12); }
    .saldo-hint { font-size: .58rem; color: var(--muted); font-weight: 700; text-align: right; margin-top: .12rem; }
    .auto-badge { font-size: .52rem; font-weight: 900; background: #dbeafe; color: #1d4ed8; border-radius: 999px; padding: .05rem .3rem; margin-left: .25rem; vertical-align: middle; }
    .acc-row.is-auto { border-color: rgba(37,99,235,.22); background: rgba(37,99,235,.025); }
    .btn-detail {
        flex: 0 0 auto; width: 22px; height: 22px; border-radius: 50%;
        border: 1.5px solid rgba(37,99,235,.35); background: rgba(37,99,235,.07);
        color: #2563eb; font-size: .65rem; font-weight: 900; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background .15s, border-color .15s; text-decoration: none;
    }
    .btn-detail:hover { background: rgba(37,99,235,.18); border-color: rgba(37,99,235,.6); }

    /* Note */
    .info-note {
        background: #eff6ff; border: 1px solid rgba(37,99,235,.14);
        border-radius: 12px; padding: .72rem .85rem;
        font-size: .82rem; font-weight: 700; color: #1e3a8a; line-height: 1.5;
    }

    /* FAB */
    .fab-wrap {
        position: fixed; right: 14px; bottom: calc(72px + 12px + env(safe-area-inset-bottom));
        z-index: 1090; display: flex; gap: 10px; align-items: center; pointer-events: none;
    }
    .fab-wrap .btn { pointer-events: auto; border-radius: 999px; font-weight: 900;
        box-shadow: 0 12px 26px rgba(15,23,42,.22), 0 4px 10px rgba(15,23,42,.14); }
    .fab-save { padding: .62rem 1.4rem; }

    @media(max-width:600px) {
        .top-grid { grid-template-columns: 1fr; }
        .saldo-wrap { flex: 0 0 120px; }
        .acc-name { font-size: .8rem; }
    }
</style>
@endpush

@section('content')
@php
    $assetAccounts     = $accounts->where('type', 'asset')->values();
    $liabilityAccounts = $accounts->where('type', 'liability')->values();
    $equityAccounts    = $accounts->where('type', 'equity')->values();
    $prefill           = $prefill ?? [];
    $details           = $details ?? [];
    $hasPrefill        = collect($prefill)->sum() > 0;
@endphp

<div class="page-wrap">
    {{-- Header --}}
    <div class="panel">
        <div class="panel-h">
            <div>
                <div class="panel-title">Saldo Awal</div>
                <div class="panel-sub">Input saldo awal akun — balance otomatis dicek</div>
            </div>
            <a href="{{ route('accounting.opening-balances-batch.index') }}"
               class="btn btn-sm btn-outline-secondary" style="border-radius:999px;">Riwayat</a>
        </div>
    </div>

    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:.85rem .95rem;font-size:.86rem;color:#991b1b;">
        <div style="font-weight:900;margin-bottom:.35rem;">Ada masalah:</div>
        <ul class="mb-0">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('accounting.opening-balances-batch.store') }}" id="ob-form">
        @csrf

        {{-- Info tanggal & deskripsi --}}
        <div class="panel">
            <div class="panel-b">
                <div class="top-grid">
                    <div class="field">
                        <label>Tanggal Saldo Awal</label>
                        <input type="date" name="date" class="form-control"
                               value="{{ old('date', now()->toDateString()) }}" required>
                    </div>
                    <div class="field">
                        <label>Keterangan</label>
                        <input type="text" name="description" class="form-control"
                               value="{{ old('description', 'Opening Balance') }}"
                               placeholder="Saldo awal pembukaan">
                    </div>
                </div>

                {{-- Balance indicator --}}
                <div class="balance-bar mt-3">
                    <div class="balance-col">
                        <span class="balance-lbl">Total Aset</span>
                        <div class="balance-val" id="sum-debit">0</div>
                    </div>
                    <div class="text-center">
                        <div class="balance-state" id="balance-state">Belum balance</div>
                        <div id="selisih-info" class="mt-1" style="font-size:.65rem;color:var(--muted);font-weight:900;"></div>
                    </div>
                    <div class="balance-col text-end">
                        <span class="balance-lbl">Total Hutang + Modal</span>
                        <div class="balance-val" id="sum-credit">0</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Info note --}}
        <div class="info-note">
            💡 Isi <strong>Saldo Awal</strong> masing-masing akun. Aset di kiri = Hutang + Modal di kanan.
            Kosongkan atau isi <strong>0</strong> untuk akun yang tidak punya saldo awal.
            @if($hasPrefill)
            <br><span style="color:#1d4ed8;">✦ Persediaan sudah terisi otomatis dari data stok sistem.</span>
            @endif
        </div>

        {{-- ASET --}}
        <div class="panel">
            <div class="panel-h">
                <div class="panel-title">💰 Aset</div>
                <span class="panel-sub">Kas, bank, persediaan, piutang</span>
            </div>
            <div class="panel-b">
                @foreach($assetAccounts as $acc)
                @php
                    $pval = $prefill[$acc->id] ?? 0;
                    $pdisp = $pval > 0 ? number_format($pval, 0, ',', '.') : '0';
                    $isAuto = $pval > 0;
                    $det = $details[$acc->code] ?? null;
                @endphp
                <div class="acc-row {{ $isAuto ? 'is-auto' : '' }}">
                    <input type="hidden" name="account_id[]" value="{{ $acc->id }}">
                    <input type="hidden" class="js-debit" name="debit[]" value="{{ $pval }}">
                    <input type="hidden" class="js-credit" name="credit[]" value="0">
                    <div class="acc-info">
                        <div class="acc-code">{{ $acc->code }}
                            @if($isAuto)<span class="auto-badge">auto</span>@endif
                        </div>
                        <div class="acc-name">{{ $acc->name }}</div>
                    </div>
                    @if($isAuto && $det)
                    <a href="{{ route('accounting.opening-balances-batch.detail', $acc->code) }}"
                       class="btn-detail"
                       title="Lihat detail">?</a>
                    @endif
                    <div class="saldo-wrap">
                        <input type="text" inputmode="numeric"
                               class="saldo-input" value="{{ $pdisp }}"
                               data-type="asset"
                               placeholder="0">
                        <div class="saldo-hint">Debit</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- KEWAJIBAN --}}
        <div class="panel">
            <div class="panel-h">
                <div class="panel-title">📋 Kewajiban</div>
                <span class="panel-sub">Hutang dagang, pinjaman</span>
            </div>
            <div class="panel-b">
                @foreach($liabilityAccounts as $acc)
                @php
                    $pval = $prefill[$acc->id] ?? 0;
                    $pdisp = $pval > 0 ? number_format($pval, 0, ',', '.') : '0';
                    $isAuto = $pval > 0;
                    $det = $details[$acc->code] ?? null;
                @endphp
                <div class="acc-row {{ $isAuto ? 'is-auto' : '' }}">
                    <input type="hidden" name="account_id[]" value="{{ $acc->id }}">
                    <input type="hidden" class="js-debit" name="debit[]" value="0">
                    <input type="hidden" class="js-credit" name="credit[]" value="{{ $pval }}">
                    <div class="acc-info">
                        <div class="acc-code">{{ $acc->code }}
                            @if($isAuto)<span class="auto-badge">auto</span>@endif
                        </div>
                        <div class="acc-name">{{ $acc->name }}</div>
                    </div>
                    @if($isAuto && $det)
                    <a href="{{ route('accounting.opening-balances-batch.detail', $acc->code) }}"
                       class="btn-detail"
                       title="Lihat detail">?</a>
                    @endif
                    <div class="saldo-wrap">
                        <input type="text" inputmode="numeric"
                               class="saldo-input" value="{{ $pdisp }}"
                               data-type="liability"
                               placeholder="0">
                        <div class="saldo-hint">Kredit</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- EKUITAS --}}
        <div class="panel">
            <div class="panel-h">
                <div class="panel-title">🏛️ Ekuitas / Modal</div>
                <span class="panel-sub">Modal pemilik</span>
            </div>
            <div class="panel-b">
                @foreach($equityAccounts as $acc)
                @php $isModalAuto = $acc->code === '3101'; @endphp
                <div class="acc-row {{ $isModalAuto ? 'is-auto' : '' }}">
                    <input type="hidden" name="account_id[]" value="{{ $acc->id }}">
                    <input type="hidden" class="js-debit" name="debit[]" value="0">
                    <input type="hidden" class="js-credit" name="credit[]" value="0">
                    <div class="acc-info">
                        <div class="acc-code">{{ $acc->code }}
                            @if($isModalAuto)<span class="auto-badge">auto</span>@endif
                        </div>
                        <div class="acc-name">{{ $acc->name }}</div>
                    </div>
                    <div class="saldo-wrap">
                        <input type="text" inputmode="numeric"
                               class="saldo-input" value="0"
                               data-type="equity"
                               @if($isModalAuto) data-modal-auto="true" readonly
                                   style="background:rgba(37,99,235,.06);color:#2563eb;cursor:default;" @endif
                               placeholder="0">
                        <div class="saldo-hint">{{ $isModalAuto ? 'Auto (Aset − Hutang)' : 'Kredit' }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- FAB --}}
        <div class="fab-wrap">
            <button type="submit" class="btn btn-primary fab-save" id="btn-submit">
                Posting Saldo Awal
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('ob-form');
    const sumDebitEl  = document.getElementById('sum-debit');
    const sumCreditEl = document.getElementById('sum-credit');
    const stateEl     = document.getElementById('balance-state');
    const selisihEl   = document.getElementById('selisih-info');

    const fmt = n => new Intl.NumberFormat('id-ID').format(Math.round(n));
    const toNum = v => {
        const n = parseFloat((v ?? '').toString().replace(/\./g, '').replace(',', '.').replace(/[^\d.]/g, ''));
        return isFinite(n) ? n : 0;
    };

    function recalc() {
        let totalAsset = 0, totalLiability = 0, totalEquityManual = 0;
        const modalAutoRow = document.querySelector('.saldo-input[data-modal-auto="true"]');

        // Pass 1: sum asset + liability + manual equity (skip modal-auto)
        document.querySelectorAll('.acc-row').forEach(row => {
            const saldoInput = row.querySelector('.saldo-input');
            if (saldoInput === modalAutoRow) return;
            const type = saldoInput.dataset.type;
            const val  = toNum(saldoInput.value);
            if (type === 'asset')    totalAsset += val;
            else if (type === 'liability') totalLiability += val;
            else totalEquityManual += val; // prive, etc.
        });

        // Auto-set Modal Pemilik = Aset − Hutang − Ekuitas manual lainnya
        const modalVal = Math.max(0, totalAsset - totalLiability - totalEquityManual);
        if (modalAutoRow) {
            modalAutoRow.value = modalVal > 0 ? fmt(modalVal) : '0';
        }

        // Pass 2: write hidden inputs + compute totals
        let totalDebit = 0, totalCredit = 0;
        document.querySelectorAll('.acc-row').forEach(row => {
            const saldoInput   = row.querySelector('.saldo-input');
            const debitHidden  = row.querySelector('.js-debit');
            const creditHidden = row.querySelector('.js-credit');
            const type = saldoInput.dataset.type;
            const val  = saldoInput === modalAutoRow ? modalVal : toNum(saldoInput.value);

            if (type === 'asset') {
                debitHidden.value  = val > 0 ? val : 0;
                creditHidden.value = 0;
                totalDebit += val;
            } else {
                creditHidden.value = val > 0 ? val : 0;
                debitHidden.value  = 0;
                totalCredit += val;
            }
        });

        sumDebitEl.textContent  = fmt(totalDebit);
        sumCreditEl.textContent = fmt(totalCredit);

        const ok = totalDebit > 0 && Math.round(totalDebit * 100) === Math.round(totalCredit * 100);
        stateEl.textContent = ok ? '✓ Balance' : 'Belum balance';
        stateEl.classList.toggle('ok', ok);

        const diff = Math.abs(totalDebit - totalCredit);
        selisihEl.textContent = diff > 0 ? 'Selisih: Rp ' + fmt(diff) : '';
    }

    // Format on blur, allow raw typing (skip modal-auto — readonly)
    document.querySelectorAll('.saldo-input:not([data-modal-auto])').forEach(input => {
        input.addEventListener('focus', () => {
            if (input.value === '0') input.value = '';
        });
        input.addEventListener('blur', () => {
            const n = toNum(input.value);
            input.value = n > 0 ? fmt(n) : '0';
            recalc();
        });
        input.addEventListener('input', recalc);
    });

    form.addEventListener('submit', e => {
        // Recalc final before submit
        recalc();

        let d = 0, c = 0;
        document.querySelectorAll('.js-debit').forEach(el => d += toNum(el.value));
        document.querySelectorAll('.js-credit').forEach(el => c += toNum(el.value));

        if (d <= 0 || Math.round(d * 100) !== Math.round(c * 100)) {
            e.preventDefault();
            alert('Total Aset harus sama dengan Total Hutang + Modal dan tidak boleh 0.\nSelisih: Rp ' + fmt(Math.abs(d - c)));
        }
    });

    recalc();
})();

</script>
@endpush
