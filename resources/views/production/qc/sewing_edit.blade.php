{{-- resources/views/production/qc/sewing_edit.blade.php --}}
@extends('layouts.app')

@section('title', 'QC Jahit · ' . $sewingReturn->code)

@push('head')
<style>
:root {
    --qcs-accent: #334155;
    --qcs-ok: #15803d;
    --qcs-reject: #b91c1c;
    --qcs-line-soft: rgba(148,163,184,.22);
}
.qcs-topbar {
    position: sticky;
    top: 0;
    z-index: 300;
    display: flex;
    align-items: center;
    gap: .45rem;
    flex-wrap: wrap;
    padding: .45rem .75rem;
    background: var(--card, #fff);
    border-bottom: 1px solid rgba(148,163,184,.18);
}
body[data-theme="dark"] .qcs-topbar { background: var(--card, #0f172a); }
.qcs-topbar-code {
    font-weight: 900;
    font-size: .95rem;
    color: var(--text, #0f172a);
}
.qcs-topbar-spacer { flex: 1; min-width: .5rem; }
.qcs-badge,
.qcs-pill {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    border-radius: 7px;
    padding: .18rem .48rem;
    font-size: .68rem;
    color: #64748b;
    background: transparent;
    border: 1px solid rgba(148,163,184,.28);
    white-space: nowrap;
}
body[data-theme="dark"] .qcs-badge,
body[data-theme="dark"] .qcs-pill {
    color: #cbd5e1;
    border-color: rgba(148,163,184,.25);
}
.qcs-pill b { margin-left: .25rem; color: var(--text, #0f172a); }
body[data-theme="dark"] .qcs-pill b { color: #e2e8f0; }
.qcs-wrap {
    max-width: 1040px;
    margin-inline: auto;
    padding: .75rem .75rem 5.75rem;
}
.qcs-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    padding: .34rem .78rem;
    border-radius: 7px;
    border: 1px solid rgba(148,163,184,.35);
    background: transparent;
    color: #475569;
    font-size: .76rem;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
}
.qcs-btn:hover { background: rgba(148,163,184,.08); color: #111827; }
body[data-theme="dark"] .qcs-btn { color: #cbd5e1; }
body[data-theme="dark"] .qcs-btn:hover { color: #f8fafc; }
.qcs-btn-primary {
    color: #fff !important;
    background: var(--qcs-accent);
    border-color: var(--qcs-accent);
}
.qcs-btn-primary:hover { background: #1f2937; border-color: #1f2937; }
.qcs-flow {
    display: flex;
    align-items: center;
    gap: .35rem;
    flex-wrap: wrap;
    margin: .55rem 0;
    padding: .45rem .55rem;
    border: 1px solid rgba(148,163,184,.18);
    border-radius: 8px;
    background: var(--card, #fff);
}
.qcs-flow-step {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    padding: .18rem .5rem;
    border-radius: 7px;
    border: 1px solid rgba(148,163,184,.25);
    color: #64748b;
    font-size: .72rem;
    font-weight: 700;
}
.qcs-flow-step.active { color: #fff; background: #334155; border-color: #334155; }
.qcs-flow-step.done { color: #334155; background: rgba(148,163,184,.08); }
.qcs-flow-sep { color: #cbd5e1; font-size: .72rem; }
.qcs-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: .45rem;
    margin-top: .55rem;
}
.qcs-kpi-card {
    background: var(--card, #fff);
    border-radius: 8px;
    border: 1px solid rgba(148,163,184,.16);
    padding: .55rem .7rem;
}
.qcs-kpi-label {
    font-size: .6rem;
    text-transform: uppercase;
    letter-spacing: .02em;
    color: #9ca3af;
    margin-bottom: .18rem;
}
.qcs-kpi-value {
    font-size: 1.05rem;
    font-weight: 900;
    line-height: 1;
    color: #334155;
}
body[data-theme="dark"] .qcs-kpi-value { color: #e2e8f0; }
.qcs-info-strip {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem .75rem;
    margin-top: .55rem;
    padding: .45rem .65rem;
    border-radius: 8px;
    background: transparent;
    border: 1px solid rgba(148,163,184,.18);
    font-size: .82rem;
}
.qcs-info-item { color: #6b7280; }
.qcs-info-item b { color: #1e293b; }
body[data-theme="dark"] .qcs-info-item b { color: #e2e8f0; }
.qcs-panel,
.qcs-table-card {
    background: var(--card, #fff);
    border-radius: 8px;
    border: 1px solid rgba(148,163,184,.18);
}
.qcs-panel {
    margin-top: .55rem;
    padding: .75rem;
}
.qcs-field-grid {
    display: grid;
    grid-template-columns: 180px minmax(180px, 1fr) minmax(120px, auto);
    gap: .65rem;
    align-items: end;
}
.qcs-label {
    font-size: .64rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #9ca3af;
    font-weight: 700;
    margin-bottom: .25rem;
}
.qcs-input {
    width: 100%;
    min-height: 38px;
    border-radius: 8px;
    border: 1px solid rgba(148,163,184,.35);
    background: transparent;
    color: var(--text, #0f172a);
    padding: .42rem .62rem;
    font-size: .9rem;
}
.qcs-input:focus {
    outline: none;
    border-color: rgba(71,85,105,.75);
}
.qcs-static {
    min-height: 38px;
    display: flex;
    align-items: center;
    font-weight: 750;
    color: var(--text, #0f172a);
}
.qcs-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
    font-variant-numeric: tabular-nums;
}
.qcs-table-card {
    margin-top: .55rem;
    overflow: hidden;
}
.qcs-table-head {
    display: flex;
    align-items: center;
    gap: .65rem;
    flex-wrap: wrap;
    padding: .5rem .75rem;
    border-bottom: 1px solid rgba(148,163,184,.14);
}
.qcs-table-title {
    font-size: .68rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: #9ca3af;
    font-weight: 700;
}
.qcs-table-subtitle {
    font-size: .78rem;
    color: #64748b;
}
.qcs-lines-wrapper {
    max-height: 52vh;
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-width: thin;
    scrollbar-color: rgba(148,163,184,.65) transparent;
}
.qcs-table {
    width: 100%;
    margin-bottom: 0;
    border-collapse: collapse;
}
.qcs-table thead th {
    position: sticky;
    top: 0;
    z-index: 6;
    padding: .42rem .6rem;
    border-bottom: 1px solid rgba(148,163,184,.18);
    background: rgba(248,250,252,.98);
    color: #9ca3af;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .03em;
    white-space: nowrap;
}
body[data-theme="dark"] .qcs-table thead th {
    background: rgba(15,23,42,.98);
    border-bottom-color: rgba(51,65,85,.8);
    color: #6b7280;
}
.qcs-table tbody td {
    padding: .42rem .6rem;
    border-top: 1px solid rgba(148,163,184,.12);
    vertical-align: middle;
}
body[data-theme="dark"] .qcs-table tbody td { border-top-color: rgba(51,65,85,.65); }
.qcs-bundle {
    display: inline-flex;
    align-items: center;
    max-width: 170px;
    padding: .18rem .5rem;
    border-radius: 7px;
    border: 1px solid rgba(148,163,184,.28);
    color: #64748b;
    font-size: .76rem;
    font-weight: 800;
}
.qcs-cut-ref {
    margin-top: .18rem;
    color: #94a3b8;
    font-size: .7rem;
}
.qcs-item-code {
    font-size: .9rem;
    font-weight: 850;
    color: var(--text, #0f172a);
}
.qcs-item-name {
    font-size: .78rem;
    color: #64748b;
}
.qcs-qty-display {
    display: inline-flex;
    justify-content: center;
    min-width: 54px;
    padding: .2rem .5rem;
    border-radius: 7px;
    border: 1px solid rgba(148,163,184,.3);
    font-weight: 850;
}
.qcs-qty-input {
    width: 72px;
    min-height: 34px;
    padding: .25rem .45rem;
    border-radius: 7px;
    border: 1px solid rgba(148,163,184,.35);
    background: transparent;
    color: var(--text, #0f172a);
    text-align: right;
    font-weight: 850;
}
.qcs-qty-input:focus,
.qcs-reason-input:focus {
    outline: none;
    border-color: rgba(71,85,105,.75);
}
.qcs-qty-input.is-ok { color: var(--qcs-ok); }
.qcs-qty-input.is-reject { color: var(--qcs-reject); }
.qcs-reason-input {
    width: 100%;
    min-width: 160px;
    min-height: 34px;
    border-radius: 7px;
    border: 1px solid rgba(148,163,184,.35);
    background: transparent;
    color: var(--text, #0f172a);
    padding: .25rem .5rem;
    font-size: .82rem;
}
.qcs-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    flex-wrap: wrap;
    padding: .5rem .75rem;
    border-top: 1px solid rgba(148,163,184,.14);
}
.qcs-summary-label {
    color: #9ca3af;
    font-size: .66rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .03em;
}
.qcs-summary-values {
    display: flex;
    align-items: center;
    gap: .55rem;
}
.qcs-summary-pill {
    display: inline-flex;
    align-items: center;
    gap: .32rem;
    border: 1px solid rgba(148,163,184,.28);
    border-radius: 7px;
    padding: .22rem .5rem;
    font-size: .76rem;
    color: #64748b;
}
.qcs-summary-pill b { font-size: .9rem; color: #334155; }
.qcs-summary-pill.is-ok b { color: var(--qcs-ok); }
.qcs-summary-pill.is-reject b { color: var(--qcs-reject); }
.qcs-mobile-cards { display: none; }
.qcs-empty {
    padding: 2rem 1rem;
    color: #64748b;
    text-align: center;
}
.qcs-action-bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 250;
    padding: .65rem .75rem calc(.65rem + env(safe-area-inset-bottom, 0px));
    background: var(--card, #fff);
    border-top: 1px solid rgba(148,163,184,.18);
}
.qcs-action-inner {
    max-width: 1040px;
    margin-inline: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
}
.qcs-action-title {
    color: #64748b;
    font-size: .68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .03em;
}
.qcs-action-hint {
    color: #94a3b8;
    font-size: .78rem;
}
.qcs-action-buttons {
    display: flex;
    align-items: center;
    gap: .45rem;
}
.qcs-save-btn {
    min-height: 40px;
    padding: .42rem 1rem;
    border-radius: 7px;
    border: 1px solid #15803d;
    background: #15803d;
    color: #fff;
    font-size: .82rem;
    font-weight: 850;
}
.qcs-save-btn:hover { background: #166534; border-color: #166534; }
.qcs-alert {
    border-radius: 8px;
    padding: .5rem .7rem;
    margin-top: .55rem;
}
@media (max-width: 768px) {
    .qcs-topbar { padding: .5rem; gap: .38rem; }
    .qcs-topbar-code {
        flex: 1 1 auto;
        min-width: 145px;
        font-size: 1.05rem;
    }
    .qcs-topbar-spacer,
    .qcs-badge,
    .qcs-topbar > .qcs-pill:not(.qcs-pill-main) {
        display: none !important;
    }
    .qcs-pill-main { margin-left: auto; font-size: .75rem; padding: .24rem .5rem; }
    .qcs-wrap { padding: .5rem .5rem 5.25rem; }
    .qcs-kpi-grid,
    .qcs-info-strip,
    .qcs-flow,
    .qcs-panel {
        display: none;
    }
    .qcs-table-head { padding: .62rem .65rem; }
    .qcs-table-title { display: none; }
    .qcs-table-subtitle { font-size: .86rem; font-weight: 750; }
    .qcs-lines-wrapper { max-height: none; overflow: visible; }
    .qcs-table { display: none; }
    .qcs-mobile-cards {
        display: flex;
        flex-direction: column;
        gap: .35rem;
        padding: .45rem;
    }
    .qcs-mobile-card {
        border: 1px solid rgba(148,163,184,.22);
        border-radius: 8px;
        padding: .5rem;
        background: var(--card, #fff);
    }
    .qcs-mobile-row {
        display: grid;
        grid-template-columns: minmax(0, 1.3fr) 56px 68px 68px;
        gap: .4rem;
        align-items: end;
    }
    .qcs-mobile-item {
        min-width: 0;
        overflow-wrap: anywhere;
    }
    .qcs-mobile-code {
        font-size: 1rem;
        font-weight: 900;
        line-height: 1.1;
    }
    .qcs-mobile-name {
        margin-top: .12rem;
        color: #64748b;
        font-size: .72rem;
        line-height: 1.2;
    }
    .qcs-mobile-qty {
        text-align: center;
        font-weight: 900;
        font-size: .95rem;
    }
    .qcs-mobile-small-label {
        display: block;
        margin-bottom: .12rem;
        color: #94a3b8;
        font-size: .58rem;
        font-weight: 850;
        text-transform: uppercase;
    }
    .qcs-mobile-input {
        width: 100%;
        min-height: 38px;
        border-radius: 7px;
        border: 1px solid rgba(148,163,184,.35);
        background: transparent;
        text-align: center;
        font-weight: 900;
    }
    .qcs-mobile-input.is-ok { color: var(--qcs-ok); }
    .qcs-mobile-input.is-reject { color: var(--qcs-reject); }
    .qcs-mobile-reason {
        display: none;
        margin-top: .45rem;
    }
    .qcs-mobile-reason.is-visible { display: block; }
    .qcs-mobile-reason-input {
        width: 100%;
        min-height: 38px;
        border-radius: 7px;
        border: 1px solid rgba(148,163,184,.35);
        background: transparent;
        padding: .38rem .5rem;
    }
    .qcs-summary { display: none; }
    .qcs-action-info { display: none; }
    .qcs-action-inner,
    .qcs-action-buttons {
        display: block;
        width: 100%;
    }
    .qcs-action-buttons .qcs-btn { display: none; }
    .qcs-save-btn {
        width: 100%;
        min-height: 46px;
        font-size: .9rem;
    }
}
</style>
@endpush

@section('content')
@php
    $statusLabel = $hasQcSewing ? 'QC selesai' : 'Belum QC';
    $totalBundles = count($rows);
    $totalIn = 0;
    $totalOk = 0;
    $totalReject = 0;

    foreach ($rows as $idx => $row) {
        $totalIn += (float) $row['qty_max'];
        $totalOk += (float) old("results.{$idx}.qty_ok", $row['qty_ok']);
        $totalReject += (float) old("results.{$idx}.qty_reject", $row['qty_reject']);
    }
@endphp

<div class="qcs-topbar">
    <span class="qcs-topbar-code">{{ $sewingReturn->code }}</span>
    <span class="qcs-badge">{{ $statusLabel }}</span>
    @if($sewingReturn->operator)
        <span class="qcs-badge">{{ $sewingReturn->operator->name }}</span>
    @endif

    <span class="qcs-topbar-spacer"></span>

    <span class="qcs-pill">Bundle <b>{{ $totalBundles }}</b></span>
    <span class="qcs-pill qcs-pill-main">Masuk <b>{{ number_format($totalIn, 0, ',', '.') }}</b></span>

    <a href="{{ route('production.qc.index', ['stage' => 'sewing']) }}" class="qcs-btn">Kembali</a>
    <a href="{{ route('production.sewing.returns.show', $sewingReturn) }}" class="qcs-btn qcs-btn-primary">Setor Jahit</a>
</div>

<div class="qcs-wrap">
    @if(session('success'))
        <div class="qcs-alert alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="qcs-alert alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($hasQcSewing)
        <div class="qcs-alert alert alert-info">
            QC Jahit untuk Setor Jahit ini sudah pernah diinput. Simpan ulang akan menimpa hasil QC sebelumnya.
        </div>
    @endif

    <div class="qcs-kpi-grid">
        <div class="qcs-kpi-card">
            <div class="qcs-kpi-label">Bundle</div>
            <div class="qcs-kpi-value">{{ $totalBundles }}</div>
        </div>
        <div class="qcs-kpi-card">
            <div class="qcs-kpi-label">Qty masuk</div>
            <div class="qcs-kpi-value">{{ number_format($totalIn, 0, ',', '.') }}</div>
        </div>
        <div class="qcs-kpi-card">
            <div class="qcs-kpi-label">OK</div>
            <div class="qcs-kpi-value" id="kpi-ok">{{ number_format($totalOk, 0, ',', '.') }}</div>
        </div>
        <div class="qcs-kpi-card">
            <div class="qcs-kpi-label">Reject</div>
            <div class="qcs-kpi-value" id="kpi-reject">{{ number_format($totalReject, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="qcs-flow">
        <span class="qcs-flow-step done">Setor Jahit</span>
        <span class="qcs-flow-sep">/</span>
        <span class="qcs-flow-step active">Input QC</span>
        <span class="qcs-flow-sep">/</span>
        <span class="qcs-flow-step {{ $hasQcSewing ? 'done' : '' }}">QC Jahit</span>
    </div>

    <div class="qcs-info-strip">
        <span class="qcs-info-item">Tanggal setor <b>{{ $sewingReturn->date?->format('d/m/Y') ?? '-' }}</b></span>
        <span class="qcs-info-item">Operator jahit <b>{{ $sewingReturn->operator?->name ?? '-' }}</b></span>
        <span class="qcs-info-item">Pergerakan <b>WIP-SEW ke WH-PRD / REJ-SEW</b></span>
    </div>

    @if(empty($rows))
        <div class="qcs-table-card">
            <div class="qcs-empty">Tidak ada bundle yang bisa di-QC pada Setor Jahit ini.</div>
        </div>
    @else
        <form method="POST" action="{{ route('production.qc.sewing.update', $sewingReturn) }}">
            @csrf
            @method('PUT')

            <div class="qcs-panel">
                <div class="qcs-field-grid">
                    <div>
                        <div class="qcs-label">Tanggal QC</div>
                        <input type="date"
                               name="qc_date"
                               value="{{ old('qc_date', now()->toDateString()) }}"
                               required
                               class="qcs-input">
                    </div>
                    <div>
                        <div class="qcs-label">Operator QC</div>
                        <input type="hidden" name="operator_id" value="{{ $loginOperator?->id }}">
                        <div class="qcs-static">{{ $loginOperator?->name ?? '(Operator tidak ditemukan)' }}</div>
                    </div>
                    <div>
                        <div class="qcs-label">Status</div>
                        <div class="qcs-static">{{ $statusLabel }}</div>
                    </div>
                </div>
            </div>

            <div class="qcs-table-card">
                <div class="qcs-table-head">
                    <div>
                        <div class="qcs-table-title">Hasil QC</div>
                        <div class="qcs-table-subtitle">Input qty OK dan reject per bundle</div>
                    </div>
                    <span class="qcs-topbar-spacer"></span>
                    <span class="qcs-pill">Baris <b>{{ $totalBundles }}</b></span>
                </div>

                <div class="qcs-lines-wrapper">
                    <table class="qcs-table">
                        <thead>
                            <tr>
                                <th style="text-align:left">Bundle</th>
                                <th style="text-align:left">Barang</th>
                                <th style="text-align:right">Masuk</th>
                                <th style="text-align:right">OK</th>
                                <th style="text-align:right">Reject</th>
                                <th style="text-align:left">Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $i => $row)
                                <tr>
                                    <td>
                                        <span class="qcs-bundle qcs-mono">{{ $row['bundle_code'] }}</span>
                                        @if($row['cutting_job_code'])
                                            <div class="qcs-cut-ref qcs-mono">{{ $row['cutting_job_code'] }}</div>
                                        @endif
                                        <input type="hidden" name="results[{{ $i }}][sewing_return_line_id]" value="{{ $row['sewing_return_line_id'] }}">
                                        <input type="hidden" name="results[{{ $i }}][bundle_id]" value="{{ $row['bundle_id'] }}">
                                    </td>
                                    <td>
                                        <div class="qcs-item-code qcs-mono">{{ $row['item_code'] }}</div>
                                        <div class="qcs-item-name">{{ $row['item_name'] }}</div>
                                    </td>
                                    <td style="text-align:right">
                                        <span class="qcs-qty-display qcs-mono">{{ number_format($row['qty_max'], 0, ',', '.') }}</span>
                                    </td>
                                    <td style="text-align:right">
                                        <input type="number"
                                               name="results[{{ $i }}][qty_ok]"
                                               class="qcs-qty-input qcs-mono is-ok qty-ok"
                                               value="{{ old("results.{$i}.qty_ok", $row['qty_ok']) }}"
                                               min="0"
                                               max="{{ $row['qty_max'] }}"
                                               step="1"
                                               inputmode="numeric"
                                               pattern="[0-9]*"
                                               onfocus="this.select()"
                                               oninput="syncReject(this, {{ $i }}, {{ $row['qty_max'] }})">
                                    </td>
                                    <td style="text-align:right">
                                        <input type="number"
                                               name="results[{{ $i }}][qty_reject]"
                                               class="qcs-qty-input qcs-mono is-reject qty-reject"
                                               id="reject_{{ $i }}"
                                               value="{{ old("results.{$i}.qty_reject", $row['qty_reject']) }}"
                                               min="0"
                                               max="{{ $row['qty_max'] }}"
                                               step="1"
                                               inputmode="numeric"
                                               pattern="[0-9]*"
                                               onfocus="this.select()"
                                               oninput="syncOk(this, {{ $i }}, {{ $row['qty_max'] }})">
                                    </td>
                                    <td>
                                        <select name="results[{{ $i }}][reject_reason]" class="qcs-reason-input">
                                            <option value="">- Pilih Alasan -</option>
                                            <option value="Reject Jahit" {{ old("results.{$i}.reject_reason", $row['reject_reason']) == 'Reject Jahit' ? 'selected' : '' }}>Reject Jahit</option>
                                            <option value="Reject Bahan" {{ old("results.{$i}.reject_reason", $row['reject_reason']) == 'Reject Bahan' ? 'selected' : '' }}>Reject Bahan</option>
                                            @if(old("results.{$i}.reject_reason", $row['reject_reason']) && !in_array(old("results.{$i}.reject_reason", $row['reject_reason']), ['Reject Jahit', 'Reject Bahan']))
                                                <option value="{{ old("results.{$i}.reject_reason", $row['reject_reason']) }}" selected>{{ old("results.{$i}.reject_reason", $row['reject_reason']) }}</option>
                                            @endif
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="qcs-mobile-cards">
                        @foreach($rows as $i => $row)
                            <div class="qcs-mobile-card">
                                <div class="qcs-mobile-row">
                                    <div class="qcs-mobile-item">
                                        <div class="qcs-mobile-code qcs-mono">{{ $row['item_code'] }}</div>
                                        <div class="qcs-mobile-name">{{ $row['bundle_code'] }}</div>
                                    </div>
                                    <div class="qcs-mobile-qty qcs-mono">
                                        <span class="qcs-mobile-small-label">Masuk</span>
                                        {{ number_format($row['qty_max'], 0, ',', '.') }}
                                    </div>
                                    <div>
                                        <span class="qcs-mobile-small-label">OK</span>
                                        <input type="number"
                                               class="qcs-mobile-input qcs-mono is-ok qty-ok"
                                               data-idx="{{ $i }}"
                                               data-max="{{ $row['qty_max'] }}"
                                               data-target="results[{{ $i }}][qty_ok]"
                                               value="{{ old("results.{$i}.qty_ok", $row['qty_ok']) }}"
                                               min="0"
                                               max="{{ $row['qty_max'] }}"
                                               step="1"
                                               inputmode="numeric"
                                               pattern="[0-9]*"
                                               onfocus="this.select()"
                                               oninput="syncMobile(this)">
                                    </div>
                                    <div>
                                        <span class="qcs-mobile-small-label">Reject</span>
                                        <input type="number"
                                               class="qcs-mobile-input qcs-mono is-reject qty-reject"
                                               id="m_reject_{{ $i }}"
                                               data-idx="{{ $i }}"
                                               data-max="{{ $row['qty_max'] }}"
                                               data-target="results[{{ $i }}][qty_reject]"
                                               value="{{ old("results.{$i}.qty_reject", $row['qty_reject']) }}"
                                               min="0"
                                               max="{{ $row['qty_max'] }}"
                                               step="1"
                                               inputmode="numeric"
                                               pattern="[0-9]*"
                                               onfocus="this.select()"
                                               oninput="syncMobileReject(this)">
                                    </div>
                                </div>
                                <div class="qcs-mobile-reason {{ (float) old("results.{$i}.qty_reject", $row['qty_reject']) > 0 ? 'is-visible' : '' }}">
                                    <span class="qcs-mobile-small-label">Alasan Reject</span>
                                    <select class="qcs-mobile-reason-input" data-target="results[{{ $i }}][reject_reason]">
                                        <option value="">- Pilih Alasan -</option>
                                        <option value="Reject Jahit" {{ old("results.{$i}.reject_reason", $row['reject_reason']) == 'Reject Jahit' ? 'selected' : '' }}>Reject Jahit</option>
                                        <option value="Reject Bahan" {{ old("results.{$i}.reject_reason", $row['reject_reason']) == 'Reject Bahan' ? 'selected' : '' }}>Reject Bahan</option>
                                        @if(old("results.{$i}.reject_reason", $row['reject_reason']) && !in_array(old("results.{$i}.reject_reason", $row['reject_reason']), ['Reject Jahit', 'Reject Bahan']))
                                            <option value="{{ old("results.{$i}.reject_reason", $row['reject_reason']) }}" selected>{{ old("results.{$i}.reject_reason", $row['reject_reason']) }}</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="qcs-summary">
                    <div class="qcs-summary-label">Ringkasan QC</div>
                    <div class="qcs-summary-values">
                        <span class="qcs-summary-pill is-ok">OK <b id="sum-ok">{{ number_format($totalOk, 0, ',', '.') }}</b></span>
                        <span class="qcs-summary-pill is-reject">Reject <b id="sum-reject">{{ number_format($totalReject, 0, ',', '.') }}</b></span>
                    </div>
                </div>
            </div>

            <div class="qcs-action-bar">
                <div class="qcs-action-inner">
                    <div class="qcs-action-info">
                        <div class="qcs-action-title">QC Jahit</div>
                        <div class="qcs-action-hint">Simpan akan memproses stok WIP-SEW ke WIP-FIN / REJ-SEW.</div>
                    </div>
                    <div class="qcs-action-buttons">
                        <a href="{{ route('production.sewing.returns.show', $sewingReturn) }}" class="qcs-btn">Batal</a>
                        <button type="submit" class="qcs-save-btn">Simpan QC</button>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function numberValue(input) {
        return parseFloat(input.value) || 0;
    }

    function syncReject(okInput, idx, max) {
        const ok = numberValue(okInput);
        const rejectField = document.getElementById('reject_' + idx);
        const suggestedReject = Math.max(0, max - ok);

        if (rejectField) {
            rejectField.value = suggestedReject;
        }

        syncToMobile(idx, ok, suggestedReject);
        updateSummary();
    }

    function syncOk(rejectInput, idx, max) {
        const reject = numberValue(rejectInput);
        const okInputs = document.querySelectorAll('.qcs-table .qcs-qty-input.is-ok');
        const okField = okInputs[idx];
        const suggestedOk = Math.max(0, max - reject);

        if (okField) {
            okField.value = suggestedOk;
        }

        syncToMobile(idx, suggestedOk, reject);
        updateSummary();
    }

    function syncMobile(okInput) {
        const idx = okInput.dataset.idx;
        const max = parseFloat(okInput.dataset.max) || 0;
        const ok = numberValue(okInput);
        const rejectField = document.getElementById('m_reject_' + idx);
        const suggestedReject = Math.max(0, max - ok);

        if (rejectField) {
            rejectField.value = suggestedReject;
        }

        syncToDesktop(idx, ok, suggestedReject);
        toggleMobileReason(idx);
        updateSummary();
    }

    function syncMobileReject(rejectInput) {
        const idx = rejectInput.dataset.idx;
        const max = parseFloat(rejectInput.dataset.max) || 0;
        const reject = numberValue(rejectInput);
        const mobileOk = document.querySelector('.qcs-mobile-input.is-ok[data-idx="' + idx + '"]');
        const suggestedOk = Math.max(0, max - reject);

        if (mobileOk) {
            mobileOk.value = suggestedOk;
        }

        syncToDesktop(idx, suggestedOk, reject);
        toggleMobileReason(idx);
        updateSummary();
    }

    function syncToMobile(idx, ok, reject) {
        const mobileOk = document.querySelector('.qcs-mobile-input.is-ok[data-idx="' + idx + '"]');
        const mobileReject = document.getElementById('m_reject_' + idx);

        if (mobileOk) mobileOk.value = ok;
        if (mobileReject) mobileReject.value = reject;

        toggleMobileReason(idx);
    }

    function syncToDesktop(idx, ok, reject) {
        const desktopOk = document.querySelectorAll('.qcs-table .qcs-qty-input.is-ok');
        const desktopReject = document.getElementById('reject_' + idx);

        if (desktopOk[idx]) desktopOk[idx].value = ok;
        if (desktopReject) desktopReject.value = reject;
    }

    function toggleMobileReason(idx) {
        const rejectField = document.getElementById('m_reject_' + idx);
        const reasonWrap = rejectField?.closest('.qcs-mobile-card')?.querySelector('.qcs-mobile-reason');

        if (!reasonWrap || !rejectField) return;

        reasonWrap.classList.toggle('is-visible', numberValue(rejectField) > 0);
    }

    function visibleInputs(selector) {
        return Array.from(document.querySelectorAll(selector)).filter((el) => el.offsetParent !== null);
    }

    function updateSummary() {
        const okInputs = visibleInputs('.qcs-table .qty-ok, .qcs-mobile-cards .qty-ok');
        const rejectInputs = visibleInputs('.qcs-table .qty-reject, .qcs-mobile-cards .qty-reject');
        const activeOkInputs = okInputs.length ? okInputs : Array.from(document.querySelectorAll('.qcs-table .qty-ok'));
        const activeRejectInputs = rejectInputs.length ? rejectInputs : Array.from(document.querySelectorAll('.qcs-table .qty-reject'));
        const totalOk = activeOkInputs.reduce((sum, el) => sum + numberValue(el), 0);
        const totalReject = activeRejectInputs.reduce((sum, el) => sum + numberValue(el), 0);

        const formattedOk = totalOk.toLocaleString('id-ID');
        const formattedReject = totalReject.toLocaleString('id-ID');

        document.getElementById('sum-ok')?.replaceChildren(document.createTextNode(formattedOk));
        document.getElementById('sum-reject')?.replaceChildren(document.createTextNode(formattedReject));
        document.getElementById('kpi-ok')?.replaceChildren(document.createTextNode(formattedOk));
        document.getElementById('kpi-reject')?.replaceChildren(document.createTextNode(formattedReject));
    }

    document.addEventListener('DOMContentLoaded', function() {

        document.querySelectorAll('[id^="m_reject_"]').forEach((el) => {
            toggleMobileReason(el.id.replace('m_reject_', ''));
        });

        const form = document.querySelector('form[method="POST"]');
        if (form) {
            form.addEventListener('submit', function() {
                if (window.innerWidth >= 769) return;

                document.querySelectorAll('.qcs-mobile-input[data-target], .qcs-mobile-reason-input[data-target]').forEach((mobileInput) => {
                    const target = form.querySelector('[name="' + mobileInput.dataset.target + '"]');
                    if (target) target.value = mobileInput.value;
                });
            });
        }

        updateSummary();
    });
</script>
@endpush
