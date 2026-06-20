@extends('layouts.app')

@section('title', 'Kartu Nilai Produksi')

@php
    $money = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $signed = fn($n) => ((float) $n < 0 ? '(' : '') . 'Rp ' . number_format(abs((float) $n), 0, ',', '.') . ((float) $n < 0 ? ')' : '');
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .pvr-page { display: grid; gap: .9rem; }
        .pvr-top { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; }
        .pvr-title { margin: 0; font-size: 1.08rem; font-weight: 900; letter-spacing: -.02em; }
        .pvr-muted { color: #64748b; font-size: .82rem; }
        .pvr-btn { display: inline-flex; align-items: center; min-height: 36px; padding: .42rem .74rem; border-radius: 999px; border: 1px solid rgba(15,23,42,.12); background: #fff; color: #0f172a; text-decoration: none; font-size: .8rem; font-weight: 850; }
        .pvr-btn.primary { background: #0f172a; color: #fff; border-color: #0f172a; }
        .pvr-filter { display: flex; gap: .45rem; align-items: end; flex-wrap: wrap; }
        .pvr-input { min-height: 36px; border-radius: 999px; border: 1px solid rgba(15,23,42,.14); padding: .35rem .75rem; font-size: .82rem; font-weight: 750; }
        .pvr-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .7rem; }
        .pvr-card { background: #fff; border: 1px solid rgba(15,23,42,.08); border-radius: 12px; padding: .82rem; box-shadow: 0 8px 20px rgba(15,23,42,.04); }
        .pvr-card .lbl { color: #64748b; font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; font-weight: 900; }
        .pvr-card .val { display: block; margin-top: .25rem; font-size: 1.08rem; font-weight: 950; color: #0f172a; }
        .pvr-card .hint { margin-top: .25rem; color: #64748b; font-size: .76rem; min-height: 2.1em; }
        .pvr-table-card { background: #fff; border: 1px solid rgba(15,23,42,.08); border-radius: 12px; overflow: hidden; }
        .pvr-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
        .pvr-table th, .pvr-table td { padding: .58rem .66rem; border-bottom: 1px solid rgba(15,23,42,.06); vertical-align: middle; }
        .pvr-table th { background: #f8fafc; color: #64748b; font-size: .7rem; text-transform: uppercase; letter-spacing: .07em; white-space: nowrap; }
        .pvr-num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 850; }
        .pvr-code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: .78rem; }
        .pvr-pill { border-radius: 999px; padding: .18rem .46rem; font-size: .72rem; font-weight: 850; }
        .pvr-pill.ok { background: #dcfce7; color: #15803d; }
        .pvr-pill.warn { background: #fee2e2; color: #b91c1c; }
        .pvr-section-title { font-size: .9rem; font-weight: 950; margin: .15rem 0 .45rem; }
        @media(max-width: 920px) {
            .pvr-top { flex-direction: column; }
            .pvr-grid { grid-template-columns: 1fr; }
            .pvr-table-card { overflow-x: auto; }
            .pvr-table { min-width: 840px; }
        }
    </style>
@endpush

@section('content')
<div class="pvr-page">
    <div class="pvr-top">
        <div>
            <h1 class="pvr-title">Kartu Nilai Produksi</h1>
            <div class="pvr-muted">Ringkasan nilai produksi, saldo akun utama, dan audit stok vs jurnal.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a class="pvr-btn" href="{{ route('accounting.production-journal-audit.index') }}">Audit Jurnal</a>
            <a class="pvr-btn" href="{{ route('accounting.journals.index') }}">Jurnal</a>
        </div>
    </div>

    <form method="GET" class="pvr-filter">
        <div>
            <label class="form-label fw-bold mb-1" style="font-size:.72rem">Dari</label>
            <input type="date" name="from" class="pvr-input" value="{{ $from }}">
        </div>
        <div>
            <label class="form-label fw-bold mb-1" style="font-size:.72rem">Sampai</label>
            <input type="date" name="to" class="pvr-input" value="{{ $to }}">
        </div>
        <div>
            <label class="form-label fw-bold mb-1" style="font-size:.72rem">Saldo Akun per</label>
            <input type="date" name="as_of" class="pvr-input" value="{{ $asOf }}">
        </div>
        <button class="pvr-btn primary" type="submit">Tampilkan</button>
    </form>

    <div class="pvr-grid">
        <div class="pvr-card">
            <div class="lbl">Pendapatan</div>
            <span class="val">{{ $money($profitLoss->revenue) }}</span>
            <div class="hint">Periode {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
        </div>
        <div class="pvr-card">
            <div class="lbl">HPP + Beban</div>
            <span class="val">{{ $money($profitLoss->cogs + $profitLoss->expenses) }}</span>
            <div class="hint">HPP dari akun 5xxx dan beban dari akun 6xxx.</div>
        </div>
        <div class="pvr-card">
            <div class="lbl">Laba / Rugi Bersih</div>
            <span class="val">{{ $signed($profitLoss->net_profit) }}</span>
            <div class="hint">Snapshot sederhana dari jurnal periode.</div>
        </div>
    </div>

    <div>
        <div class="pvr-section-title">Saldo Akun Utama</div>
        <div class="pvr-table-card">
            <table class="pvr-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Akun</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                        <th class="text-end">Saldo</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mainAccounts as $account)
                        <tr>
                            <td class="pvr-code">{{ $account->code }}</td>
                            <td>{{ $account->name }}</td>
                            <td class="pvr-num">{{ $money($account->debit) }}</td>
                            <td class="pvr-num">{{ $money($account->credit) }}</td>
                            <td class="pvr-num">{{ $signed($account->balance) }}</td>
                            <td>
                                @if ($account->is_unusual)
                                    <span class="pvr-pill warn">Cek</span>
                                @else
                                    <span class="pvr-pill ok">Masuk akal</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="pvr-section-title">Kartu Nilai Produksi</div>
        <div class="pvr-grid">
            @foreach ($cards as $card)
                <div class="pvr-card">
                    <div class="lbl">{{ $card->label }}</div>
                    <span class="val">{{ $money($card->amount) }}</span>
                    <div class="hint">{{ $card->hint }}</div>
                    <div class="pvr-muted pvr-code mt-1">Jurnal: {{ $money($card->journal_amount) }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div>
        <div class="pvr-section-title">Audit Stok vs Jurnal</div>
        <div class="pvr-table-card">
            <table class="pvr-table">
                <thead>
                    <tr>
                        <th>Flow</th>
                        <th class="text-end">Mutasi In</th>
                        <th class="text-end">Mutasi Out</th>
                        <th class="text-end">Jurnal Debit</th>
                        <th class="text-end">Jurnal Kredit</th>
                        <th class="text-end">Basis Selisih</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($auditRows as $row)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $row->label }}</div>
                                <div class="pvr-muted pvr-code">{{ $row->source_type }} → {{ $row->journal_source_type }}</div>
                            </td>
                            <td class="pvr-num">{{ $money($row->mutation_in) }}</td>
                            <td class="pvr-num">{{ $money($row->mutation_out) }}</td>
                            <td class="pvr-num">{{ $money($row->journal_debit) }}</td>
                            <td class="pvr-num">{{ $money($row->journal_credit) }}</td>
                            <td class="pvr-num">{{ $signed($row->diff) }}</td>
                            <td>
                                @if (abs((float) $row->diff) < 1)
                                    <span class="pvr-pill ok">Match</span>
                                @else
                                    <span class="pvr-pill warn">Selisih</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pvr-muted mt-2">
            Basis selisih membandingkan sisi mutasi yang mewakili flow dengan satu sisi jurnal. Untuk setoran jahit, nilai kartu memakai cost keluar WIP-SEW; mutasi masuk WIP-FIN bisa lebih tinggi karena ada tambahan biaya jahit.
        </div>
    </div>
</div>
@endsection
