@extends('layouts.app')

@section('title', 'Audit Jurnal Produksi')

@push('head')
    <style>
        .pja-wrap { max-width: 1120px; margin: 0 auto; padding: .8rem .75rem 2.2rem; }
        .pja-top { display: flex; justify-content: space-between; gap: .75rem; align-items: flex-start; margin-bottom: .8rem; }
        .pja-title { margin: 0; font-size: 1.08rem; font-weight: 820; letter-spacing: -.02em; }
        .pja-sub { color: color-mix(in srgb, var(--text) 62%, transparent 38%); font-size: .88rem; margin-top: .12rem; }
        .pja-btn { display: inline-flex; align-items: center; gap: .35rem; border: 1px solid rgba(148,163,184,.35); border-radius: 12px; padding: .44rem .62rem; text-decoration: none; color: var(--text); background: var(--card); font-size: .88rem; }
        .pja-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .65rem; margin-bottom: .8rem; }
        .pja-kpi { border: 1px solid rgba(148,163,184,.26); background: var(--card); border-radius: 12px; padding: .75rem; }
        .pja-kpi .lbl { color: color-mix(in srgb, var(--text) 58%, transparent 42%); font-size: .75rem; font-weight: 750; text-transform: uppercase; letter-spacing: .06em; }
        .pja-kpi .val { display: block; margin-top: .2rem; font-weight: 860; font-size: 1.05rem; }
        .pja-card { border: 1px solid rgba(148,163,184,.26); background: var(--card); border-radius: 12px; overflow: hidden; }
        .pja-table { width: 100%; border-collapse: collapse; }
        .pja-table th, .pja-table td { padding: .62rem .68rem; vertical-align: top; border-bottom: 1px solid rgba(148,163,184,.16); }
        .pja-table th { background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%); color: color-mix(in srgb, var(--text) 58%, transparent 42%); font-size: .72rem; text-transform: uppercase; letter-spacing: .07em; white-space: nowrap; }
        .pja-source { font-weight: 820; }
        .pja-muted { color: color-mix(in srgb, var(--text) 60%, transparent 40%); font-size: .82rem; }
        .pja-code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: .82rem; }
        .pja-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: .2rem .48rem; font-size: .76rem; font-weight: 780; border: 1px solid rgba(148,163,184,.25); }
        .pja-pill.ok { color: #15803d; background: rgba(22,163,74,.08); }
        .pja-pill.warn { color: #b45309; background: rgba(245,158,11,.1); }
        .pja-command { margin-top: .8rem; padding: .75rem; border: 1px solid rgba(148,163,184,.25); background: color-mix(in srgb, var(--card) 88%, var(--bg) 12%); border-radius: 12px; }
        .pja-command code { white-space: normal; word-break: break-word; }
        @media (max-width: 820px) {
            .pja-top { flex-direction: column; }
            .pja-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .pja-card { overflow-x: auto; }
            .pja-table { min-width: 760px; }
        }
    </style>
@endpush

@section('content')
    <div class="pja-wrap">
        <div class="pja-top">
            <div>
                <h1 class="pja-title">Audit Jurnal Produksi</h1>
                <div class="pja-sub">Cek movement produksi yang sudah atau belum punya jurnal otomatis.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a class="pja-btn" href="{{ route('accounting.journals.index') }}">Jurnal</a>
                <a class="pja-btn" href="{{ route('accounting.production-journal-audit.index') }}">Refresh</a>
            </div>
        </div>

        <div class="pja-grid">
            <div class="pja-kpi">
                <div class="lbl">Dokumen Movement</div>
                <span class="val">{{ number_format($totals['document_count'], 0, ',', '.') }}</span>
            </div>
            <div class="pja-kpi">
                <div class="lbl">Jurnal Aktif</div>
                <span class="val">{{ number_format($totals['active_journal_count'], 0, ',', '.') }}</span>
            </div>
            <div class="pja-kpi">
                <div class="lbl">Belum Ada Jurnal</div>
                <span class="val">{{ number_format($totals['missing_count'], 0, ',', '.') }}</span>
            </div>
            <div class="pja-kpi">
                <div class="lbl">Nilai Movement</div>
                <span class="val">Rp {{ number_format($totals['amount'], 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="pja-card">
            <table class="pja-table">
                <thead>
                    <tr>
                        <th>Flow</th>
                        <th class="text-end">Dokumen</th>
                        <th class="text-end">Jurnal Aktif</th>
                        <th class="text-end">Belum Ada</th>
                        <th class="text-end">Nilai</th>
                        <th>Efek</th>
                        <th>Preview Missing</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>
                                <div class="pja-source">{{ $row['label'] }}</div>
                                <div class="pja-muted pja-code">{{ $row['journal_source_type'] }}</div>
                            </td>
                            <td class="text-end pja-code">{{ number_format($row['document_count'], 0, ',', '.') }}</td>
                            <td class="text-end pja-code">{{ number_format($row['active_journal_count'], 0, ',', '.') }}</td>
                            <td class="text-end">
                                @if ((int) $row['missing_count'] > 0)
                                    <span class="pja-pill warn">{{ number_format($row['missing_count'], 0, ',', '.') }}</span>
                                @else
                                    <span class="pja-pill ok">Lengkap</span>
                                @endif
                            </td>
                            <td class="text-end pja-code">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                            <td class="pja-code">{{ $row['effect'] }}</td>
                            <td class="pja-muted pja-code">
                                {{ empty($row['missing_preview']) ? '-' : implode(', ', $row['missing_preview']) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pja-command">
            <div class="fw-bold mb-1">Command Backfill</div>
            <div class="pja-muted mb-2">Default command adalah preview. Tambahkan <span class="pja-code">--apply --force</span> untuk eksekusi.</div>
            <code>php artisan accounting:backfill-production-journals</code><br>
            <code>php artisan accounting:backfill-production-journals --apply --force</code>
        </div>
    </div>
@endsection
