@extends('layouts.app')

@section('title', 'Audit Akuntansi Produksi')

@push('head')
    <style>
        .paa-wrap { max-width: 1280px; margin: 0 auto; padding: .8rem .75rem 2.2rem; }
        .paa-top { display: flex; justify-content: space-between; gap: .75rem; align-items: flex-start; margin-bottom: .8rem; }
        .paa-title { margin: 0; font-size: 1.08rem; font-weight: 820; letter-spacing: -.02em; }
        .paa-sub { color: color-mix(in srgb, var(--text) 62%, transparent 38%); font-size: .88rem; margin-top: .12rem; }
        .paa-btn { display: inline-flex; align-items: center; gap: .35rem; border: 1px solid rgba(148,163,184,.35); border-radius: 12px; padding: .44rem .62rem; text-decoration: none; color: var(--text); background: var(--card); font-size: .88rem; }
        .paa-card { border: 1px solid rgba(148,163,184,.26); background: var(--card); border-radius: 12px; overflow: hidden; margin-bottom: .8rem; }
        .paa-filter { display: flex; gap: .55rem; flex-wrap: wrap; align-items: end; padding: .75rem; }
        .paa-filter label { display: grid; gap: .25rem; font-size: .78rem; font-weight: 750; }
        .paa-filter input, .paa-filter select { min-height: 36px; border: 1px solid rgba(148,163,184,.38); border-radius: 9px; padding: .35rem .5rem; background: var(--card); color: var(--text); }
        .paa-primary { border: 0; border-radius: 9px; min-height: 36px; padding: .35rem .7rem; color: #fff; background: #2563eb; cursor: pointer; }
        .paa-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .65rem; margin-bottom: .8rem; }
        .paa-kpi { border: 1px solid rgba(148,163,184,.26); background: var(--card); border-radius: 12px; padding: .75rem; }
        .paa-kpi .lbl { color: color-mix(in srgb, var(--text) 58%, transparent 42%); font-size: .72rem; font-weight: 750; text-transform: uppercase; letter-spacing: .06em; }
        .paa-kpi .val { display: block; margin-top: .2rem; font-weight: 860; font-size: 1.02rem; }
        .paa-scroll { overflow-x: auto; }
        .paa-table { width: 100%; min-width: 1050px; border-collapse: collapse; }
        .paa-table th, .paa-table td { padding: .58rem .62rem; vertical-align: top; border-bottom: 1px solid rgba(148,163,184,.16); }
        .paa-table th { background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%); color: color-mix(in srgb, var(--text) 58%, transparent 42%); font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; }
        .paa-muted { color: color-mix(in srgb, var(--text) 60%, transparent 40%); font-size: .82rem; }
        .paa-code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: .8rem; }
        .paa-pill { display: inline-flex; border-radius: 999px; padding: .18rem .45rem; font-size: .73rem; font-weight: 780; border: 1px solid rgba(148,163,184,.25); }
        .paa-pill.ok { color: #15803d; background: rgba(22,163,74,.08); }
        .paa-pill.warn { color: #b45309; background: rgba(245,158,11,.1); }
        .paa-pill.info { color: #1d4ed8; background: rgba(37,99,235,.08); }
        .paa-details { padding: .65rem .75rem .8rem; background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%); }
        .paa-details summary { cursor: pointer; font-weight: 760; }
        @media (max-width: 900px) {
            .paa-top { flex-direction: column; }
            .paa-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>
@endpush

@section('content')
    <div class="paa-wrap">
        <div class="paa-top">
            <div>
                <h1 class="paa-title">Audit Akuntansi Produksi</h1>
                <div class="paa-sub">Read-only: inventory mutation, jurnal legacy, dan gap mirror akuntansi baru.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a class="paa-btn" href="{{ route('accounting.production-journal-audit.index') }}">Audit jurnal legacy</a>
                <a class="paa-btn" href="{{ route('accounting.production-accounting-audit.index') }}">Reset</a>
            </div>
        </div>

        <div class="paa-card">
            <form class="paa-filter" method="get">
                <label>Mulai
                    <input type="date" name="from" value="{{ $filters['from'] }}">
                </label>
                <label>Sampai
                    <input type="date" name="to" value="{{ $filters['to'] }}">
                </label>
                <label>Stage
                    <select name="stage[]" multiple size="3" aria-label="Pilih stage produksi">
                        @foreach ($definitions as $key => $definition)
                            <option value="{{ $key }}" @selected(in_array($key, $filters['stage'], true))>{{ $definition['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="paa-primary" type="submit">Audit</button>
            </form>
        </div>

        <div class="paa-grid">
            @foreach ([
                ['label' => 'Stage', 'value' => $report['summary']['stages']],
                ['label' => 'Dokumen', 'value' => $report['summary']['documents']],
                ['label' => 'Mutasi', 'value' => $report['summary']['mutations']],
                ['label' => 'Legacy missing', 'value' => $report['summary']['legacy_missing']],
                ['label' => 'Mirror missing', 'value' => $report['summary']['standalone_missing']],
                ['label' => 'Historical gap', 'value' => $report['summary']['standalone_historical_gaps']],
                ['label' => 'Mapping missing', 'value' => $report['summary']['mapping_missing']],
            ] as $kpi)
                <div class="paa-kpi">
                    <div class="lbl">{{ $kpi['label'] }}</div>
                    <span class="val">{{ number_format((int) $kpi['value'], 0, ',', '.') }}</span>
                </div>
            @endforeach
        </div>

        <div class="paa-card paa-scroll">
            <table class="paa-table">
                <thead>
                    <tr>
                        <th>Stage</th>
                        <th>Dokumen</th>
                        <th>Mutasi</th>
                        <th>Legacy<br>aktif / missing</th>
                        <th>Legacy<br>debit / credit</th>
                        <th>Mirror<br>aktif / missing</th>
                        <th>Mapping</th>
                        <th>Selisih stok-jurnal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['rows'] as $row)
                        <tr>
                            <td><div class="fw-bold">{{ $row['label'] }}</div><div class="paa-muted paa-code">{{ $row['key'] }}</div></td>
                            <td class="paa-code">{{ number_format($row['document_count'], 0, ',', '.') }}</td>
                            <td class="paa-code">{{ number_format($row['movement_count'], 0, ',', '.') }}</td>
                            <td class="paa-code">{{ $row['legacy_active_journal_count'] }} / {{ $row['legacy_missing_count'] }}</td>
                            <td class="paa-code">Rp {{ number_format($row['legacy_debit'], 2, ',', '.') }}<br>Rp {{ number_format($row['legacy_credit'], 2, ',', '.') }}</td>
                            <td class="paa-code">
                                {{ $row['standalone_active_journal_count'] }} / {{ $row['standalone_missing_count'] }}
                                @if ((int) $row['standalone_historical_gap_count'] > 0)
                                    <div class="paa-muted">historical: {{ $row['standalone_historical_gap_count'] }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($row['mapping_status'] === 'PASS')
                                    <span class="paa-pill ok">Lengkap</span>
                                @else
                                    <span class="paa-pill warn">{{ implode(', ', $row['mapping_missing']) }}</span>
                                @endif
                            </td>
                            <td class="paa-code">Rp {{ number_format($row['stock_vs_legacy_variance'], 2, ',', '.') }}</td>
                            <td>
                                <span class="paa-pill {{ $row['status'] === 'PASS' ? 'ok' : 'warn' }}">{{ $row['status'] }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="9" class="paa-details">
                                <details>
                                    <summary>Detail dokumen ({{ count(array_filter($report['details'], fn ($detail) => $detail['stage'] === $row['key'])) }})</summary>
                                    <div class="paa-scroll mt-2">
                                        <table class="paa-table">
                                            <thead><tr><th>Source ID</th><th>Mutasi</th><th>Nilai movement</th><th>Legacy</th><th>Debit / credit</th><th>Mirror</th><th>Selisih</th></tr></thead>
                                            <tbody>
                                                @forelse (array_filter($report['details'], fn ($detail) => $detail['stage'] === $row['key']) as $detail)
                                                    <tr>
                                                        <td class="paa-code">#{{ $detail['source_id'] }}</td>
                                                        <td class="paa-code">{{ $detail['movement_count'] }}<br>IN {{ number_format($detail['movement_qty_in'], 3, ',', '.') }} / OUT {{ number_format($detail['movement_qty_out'], 3, ',', '.') }}</td>
                                                        <td class="paa-code">Rp {{ number_format($detail['movement_value'], 2, ',', '.') }}</td>
                                                        <td><span class="paa-pill {{ $detail['legacy_status'] === 'POSTED' ? 'ok' : 'warn' }}">{{ $detail['legacy_status'] }}</span></td>
                                                        <td class="paa-code">Rp {{ number_format($detail['legacy_debit'], 2, ',', '.') }} / Rp {{ number_format($detail['legacy_credit'], 2, ',', '.') }}</td>
                                                        <td>
                                                            @if ($detail['standalone_historical_gap'])
                                                                <span class="paa-pill info">HISTORICAL_GAP</span>
                                                            @else
                                                                <span class="paa-pill {{ $detail['standalone_status'] === 'POSTED' ? 'info' : 'warn' }}">{{ $detail['standalone_status'] }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="paa-code">Rp {{ number_format($detail['legacy_variance'], 2, ',', '.') }}</td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="7" class="paa-muted">Tidak ada dokumen dalam scope.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="paa-muted">Tidak ada stage yang dipilih.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="paa-muted">Audit ini tidak membuat jurnal, tidak mengubah stok, dan tidak melakukan backfill.</div>
    </div>
@endsection
