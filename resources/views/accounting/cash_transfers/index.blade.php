@extends('layouts.app')

@section('title', 'Accounting • Transfer Kas/Bank')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $statusLabels = ['draft' => 'Draft', 'posted' => 'Tercatat', 'void' => 'Dibatalkan'];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .ct-page { display: grid; gap: 1rem; }
        .ct-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .ct-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; padding: .55rem .95rem; border-radius: 999px; border: 1px solid rgba(15,23,42,.1); background: #fff; color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850; }
        .ct-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .ct-kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
        .ct-kpi { border: 1px solid rgba(15,23,42,.08); border-radius: 12px; background: #fff; padding: .85rem .95rem; }
        .ct-kpi-label { color: #64748b; font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; }
        .ct-kpi-value { margin-top: .18rem; color: #0f172a; font-size: 1.18rem; font-weight: 950; font-variant-numeric: tabular-nums; }
        .ct-kpi-note, .ct-meta { color: #94a3b8; font-size: .74rem; }
        .ct-filter { display: grid; grid-template-columns: minmax(150px,.8fr) minmax(140px,.8fr) minmax(140px,.8fr) auto; gap: .55rem; align-items: end; }
        .ct-filter .form-control, .ct-filter .form-select, .ct-form-control { min-height: 42px; border-radius: 12px; border-color: rgba(15,23,42,.12); box-shadow: none; }
        .ct-filter-actions { display: flex; gap: .45rem; }
        .ct-table th, .ct-table td { vertical-align: middle; }
        .ct-table-wrap { overflow: auto; }
        .ct-row { cursor: pointer; }
        .ct-row:hover td { background: #f8fafc; }
        .ct-account { color: #334155; font-weight: 800; }
        .ct-code { color: #94a3b8; font-size: .76rem; }
        .ct-desc { color: #0f172a; font-weight: 850; text-decoration: none; }
        .ct-num { text-align: right; color: #0f172a; font-weight: 900; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .ct-status { display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px; padding: .22rem .6rem; font-size: .74rem; font-weight: 850; white-space: nowrap; }
        .ct-status::before { content: ''; width: 7px; height: 7px; border-radius: 999px; background: currentColor; }
        .ct-status-draft { color: #b45309; background: #fef3c7; }
        .ct-status-posted { color: #166534; background: #dcfce7; }
        .ct-status-void { color: #b91c1c; background: #fee2e2; }
        .ct-empty { text-align: center; color: #64748b; padding: 2.4rem 1rem; }
        @media (max-width: 768px) {
            .ct-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
            .ct-kpi { padding: .7rem .75rem; }
            .ct-kpi-value { font-size: 1rem; }
            .ct-filter { grid-template-columns: 1fr 1fr; }
            .ct-filter .form-select { grid-column: 1 / -1; }
            .ct-filter-actions { grid-column: 1 / -1; }
            .ct-filter-actions .ct-btn { flex: 1; }
            .ct-table-wrap { overflow: visible; }
            .ct-table { display: block; }
            .ct-table thead { display: none; }
            .ct-table tbody, .ct-table tr, .ct-table td { display: block; width: 100%; }
            .ct-table tr { border: 1px solid rgba(15,23,42,.08); border-radius: 12px; margin-bottom: .65rem; padding: .7rem; }
            .ct-table td { border: 0; padding: .18rem 0; }
            .ct-table td::before { content: attr(data-label); display: inline-block; min-width: 92px; color: #94a3b8; font-size: .72rem; font-weight: 800; text-transform: uppercase; }
            .ct-num { text-align: left; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page eyebrow="Accounting" title="Transfer Kas/Bank" description="Pindahkan saldo antar akun kas atau bank dengan jurnal otomatis.">
        <x-slot:actions>
            <div class="ct-actions">
                <a class="ct-btn ct-btn-primary" href="{{ route('accounting.cash-transfers.create') }}">+ Tambah Transfer</a>
            </div>
        </x-slot:actions>

        <div class="ct-page">
            <div class="ct-kpi-grid">
                <div class="ct-kpi"><div class="ct-kpi-label">Total Transfer</div><div class="ct-kpi-value">Rp {{ $fmt($summary['total_amount']) }}</div><div class="ct-kpi-note">{{ $fmt($summary['total_docs']) }} dokumen</div></div>
                <div class="ct-kpi"><div class="ct-kpi-label">Sudah Tercatat</div><div class="ct-kpi-value">Rp {{ $fmt($summary['posted_amount']) }}</div><div class="ct-kpi-note">masuk jurnal</div></div>
                <div class="ct-kpi"><div class="ct-kpi-label">Draft</div><div class="ct-kpi-value">{{ $fmt($summary['draft_docs']) }}</div><div class="ct-kpi-note">menunggu posting</div></div>
                <div class="ct-kpi"><div class="ct-kpi-label">Dibatalkan</div><div class="ct-kpi-value">{{ $fmt($summary['void_docs']) }}</div><div class="ct-kpi-note">void/reversal</div></div>
            </div>

            <x-gf.panel title="Daftar Transfer" subtitle="Klik baris untuk melihat detail dan memproses draft.">
                <form class="ct-filter mb-3" method="GET" action="{{ route('accounting.cash-transfers.index') }}">
                    <select class="form-select" name="status" aria-label="Status">
                        <option value="">Semua status</option>
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input class="form-control" type="text" name="from" value="{{ request('from') }}" data-gf-date placeholder="Dari tanggal" aria-label="Dari tanggal" autocomplete="off">
                    <input class="form-control" type="text" name="to" value="{{ request('to') }}" data-gf-date placeholder="Sampai tanggal" aria-label="Sampai tanggal" autocomplete="off">
                    <div class="ct-filter-actions"><button class="ct-btn" type="submit">Filter</button><a class="ct-btn" href="{{ route('accounting.cash-transfers.index') }}">Reset</a></div>
                </form>

                @if (session('message'))
                    <div class="gf-mpl-insight mb-3"><span class="gf-mpl-insight-ico">✓</span><b>{{ session('message') }}</b></div>
                @endif

                @if ($transfers->isEmpty())
                    <div class="ct-empty">Belum ada data transfer kas/bank.</div>
                @else
                    <div class="ct-table-wrap">
                        <table class="table table-hover align-middle mb-0 ct-table">
                            <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Dari</th><th>Ke</th><th>Status</th><th class="ct-num">Nominal</th></tr></thead>
                            <tbody>
                                @foreach ($transfers as $transfer)
                                    @php $status = $transfer->status; @endphp
                                    <tr class="ct-row" data-href="{{ route('accounting.cash-transfers.show', $transfer) }}" tabindex="0">
                                        <td data-label="Tanggal">{{ optional($transfer->date)->format('d/m/Y') }}</td>
                                        <td data-label="Keterangan"><a class="ct-desc" href="{{ route('accounting.cash-transfers.show', $transfer) }}">{{ $transfer->description ?: 'Transfer Kas/Bank' }}</a><div class="ct-meta">{{ $transfer->reference ? 'Ref: '.$transfer->reference.' · ' : '' }}ID #{{ $transfer->id }}</div></td>
                                        <td data-label="Dari"><div class="ct-account">{{ $transfer->fromCashAccount?->name ?? '-' }}</div><div class="ct-code">{{ $transfer->fromCashAccount?->code ?? '' }}</div></td>
                                        <td data-label="Ke"><div class="ct-account">{{ $transfer->toCashAccount?->name ?? '-' }}</div><div class="ct-code">{{ $transfer->toCashAccount?->code ?? '' }}</div></td>
                                        <td data-label="Status"><span class="ct-status ct-status-{{ $status }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</span></td>
                                        <td class="ct-num" data-label="Nominal">Rp {{ $fmt($transfer->amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $transfers->links() }}</div>
                @endif
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.ct-row[data-href]').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (event.target.closest('a, button, form, input, select, textarea')) return;
            window.location.href = row.dataset.href;
        });
        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                window.location.href = row.dataset.href;
            }
        });
    });
});
</script>
@endpush
