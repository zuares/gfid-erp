@extends('layouts.app')

@section('title', 'Accounting • Saldo Awal')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $rows = $openingJournals->getCollection();
    $activeRows = $rows->filter(fn($j) => is_null($j->voided_at));
    $voidRows = $rows->filter(fn($j) => !is_null($j->voided_at));
    $amountOf = function ($journal) {
        $cashLine = $journal->lines->first(fn($line) => (float) $line->debit > 0);
        return (float) ($cashLine?->debit ?? 0);
    };
    $activeAmount = $activeRows->sum(fn($j) => $amountOf($j));
    $totalAmount = $rows->sum(fn($j) => $amountOf($j));
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .ob-page { display: grid; gap: 1rem; }
        .ob-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .ob-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            min-height: 40px; padding: .55rem .95rem; border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, .10); background: #fff;
            color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850;
        }
        .ob-btn:hover { color: #0f172a; background: #f8fafc; }
        .ob-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .ob-btn-primary:hover { color: #fff; background: #1e293b; }
        .ob-btn-danger { color: #b91c1c; border-color: #fecaca; background: #fff5f5; }
        .ob-btn-danger:hover { color: #991b1b; background: #fee2e2; }
        .ob-kpi-grid {
            display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
        }
        .ob-kpi {
            border: 1px solid rgba(15, 23, 42, .08); border-radius: 12px;
            background: #fff; padding: .85rem .95rem;
        }
        .ob-kpi-label {
            color: #64748b; font-size: .68rem; font-weight: 900;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .ob-kpi-value {
            margin-top: .18rem; color: #0f172a; font-size: 1.18rem;
            font-weight: 950; line-height: 1.15; font-variant-numeric: tabular-nums;
        }
        .ob-kpi-note { margin-top: .2rem; color: #94a3b8; font-size: .74rem; }
        .ob-filter {
            display: grid; grid-template-columns: minmax(140px, .8fr) minmax(140px, .8fr) minmax(150px, .8fr) auto;
            gap: .55rem; align-items: end;
        }
        .ob-filter .form-control,
        .ob-filter .form-select {
            min-height: 40px; border-radius: 999px; border-color: rgba(15, 23, 42, .12);
            font-size: .84rem; font-weight: 760; box-shadow: none;
        }
        .ob-filter-label {
            display: block; margin-bottom: .28rem; color: #64748b;
            font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: .06em;
        }
        .ob-filter-actions { display: flex; gap: .45rem; }
        .ob-table-wrap { max-height: calc(100vh - 350px); overflow: auto; -webkit-overflow-scrolling: touch; }
        .ob-date {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 104px; border-radius: 999px; padding: .22rem .6rem;
            background: #f8fafc; color: #0f172a; font-weight: 900; font-variant-numeric: tabular-nums;
        }
        .ob-title { color: #0f172a; font-weight: 880; text-decoration: none; }
        .ob-title:hover { color: #1d4ed8; }
        .ob-meta { margin-top: .16rem; color: #64748b; font-size: .78rem; }
        .ob-account { color: #0f172a; font-weight: 850; }
        .ob-status {
            display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px;
            padding: .22rem .6rem; font-size: .74rem; font-weight: 850; border: 1px solid transparent;
        }
        .ob-status::before { content: ''; width: 7px; height: 7px; border-radius: 999px; background: currentColor; }
        .ob-status-active { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .ob-status-void { color: #b91c1c; background: #fee2e2; border-color: #fecaca; }
        .ob-num {
            text-align: right; color: #0f172a; font-weight: 950;
            font-variant-numeric: tabular-nums; white-space: nowrap;
        }
        .ob-row-void { opacity: .72; }
        .ob-empty { text-align: center; color: #64748b; padding: 2.4rem 1rem; }
        .ob-mobile-list { display: none; }
        @media (max-width: 768px) {
            .gf-master-header { padding: 12px 14px; border-radius: 14px; }
            .gf-master-title { font-size: 18px; }
            .gf-master-desc { font-size: 11.5px; }
            .gf-master-actions { flex: 1 1 100%; }
            .ob-actions { justify-content: stretch; }
            .ob-actions .ob-btn { flex: 1 1 auto; }
            .ob-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
            .ob-kpi { padding: .7rem .75rem; }
            .ob-kpi-value { font-size: 1.02rem; }
            .ob-filter { grid-template-columns: 1fr 1fr; }
            .ob-filter > div:nth-child(3),
            .ob-filter-actions { grid-column: 1 / -1; }
            .ob-filter-actions .ob-btn { flex: 1 1 0; }
            .ob-table-wrap { display: none; }
            .ob-mobile-list { display: grid; gap: .62rem; }
            .ob-mobile-card {
                display: grid; gap: .58rem; padding: .8rem;
                border: 1px solid rgba(15, 23, 42, .08); border-radius: 10px; background: #fff;
                box-shadow: 0 6px 18px rgba(15, 23, 42, .04);
            }
            .ob-mobile-top { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; }
            .ob-mobile-title { min-width: 0; }
            .ob-mobile-amount { color: #0f172a; font-weight: 950; font-variant-numeric: tabular-nums; white-space: nowrap; }
            .ob-mobile-actions { display: flex; gap: .45rem; }
            .ob-mobile-actions .ob-btn { flex: 1 1 0; min-height: 36px; padding: .42rem .7rem; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Accounting"
        title="Saldo Awal"
        description="Masukkan posisi awal kas, bank, modal, dan akun pembuka sebelum transaksi harian berjalan.">
        <x-slot:actions>
            <div class="ob-actions">
                <a href="{{ route('accounting.opening-balances-batch.create') }}" class="ob-btn">
                    Batch Input
                </a>
                <a href="{{ route('accounting.opening-balances.create') }}" class="ob-btn ob-btn-primary">
                    + Saldo Awal
                </a>
                <a href="{{ route('accounting.journals.index') }}" class="ob-btn">
                    Semua Jurnal
                </a>
            </div>
        </x-slot:actions>

        <div class="ob-page">
            @if (session('message'))
                <div class="alert alert-{{ session('status') === 'ok' ? 'success' : 'danger' }} mb-0">
                    {{ session('message') }}
                </div>
            @endif

            <div class="ob-kpi-grid">
                <div class="ob-kpi">
                    <div class="ob-kpi-label">Total Data</div>
                    <div class="ob-kpi-value">{{ $fmt($openingJournals->total()) }}</div>
                    <div class="ob-kpi-note">saldo awal sesuai filter</div>
                </div>
                <div class="ob-kpi">
                    <div class="ob-kpi-label">Masih Aktif</div>
                    <div class="ob-kpi-value">{{ $fmt($activeRows->count()) }}</div>
                    <div class="ob-kpi-note">di halaman ini</div>
                </div>
                <div class="ob-kpi">
                    <div class="ob-kpi-label">Nominal Aktif</div>
                    <div class="ob-kpi-value">Rp {{ $fmt($activeAmount) }}</div>
                    <div class="ob-kpi-note">total halaman ini</div>
                </div>
                <div class="ob-kpi">
                    <div class="ob-kpi-label">Void</div>
                    <div class="ob-kpi-value">{{ $fmt($voidRows->count()) }}</div>
                    <div class="ob-kpi-note">dibatalkan di halaman ini</div>
                </div>
            </div>

            <x-gf.panel title="Daftar Saldo Awal" subtitle="Gunakan filter tanggal untuk cek saldo pembuka yang masih aktif atau sudah void.">
                <form method="GET" class="ob-filter mb-3">
                    <div>
                        <label class="ob-filter-label" for="from">Dari Tanggal</label>
                        <input id="from" type="text" name="from" class="form-control" value="{{ request('from') }}" placeholder="YYYY-MM-DD" data-gf-date>
                    </div>
                    <div>
                        <label class="ob-filter-label" for="to">Sampai</label>
                        <input id="to" type="text" name="to" class="form-control" value="{{ request('to') }}" placeholder="YYYY-MM-DD" data-gf-date>
                    </div>
                    <div>
                        <label class="ob-filter-label" for="status">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="" @selected(request('status') === null || request('status') === '')>Semua status</option>
                            <option value="active" @selected(request('status') === 'active')>Aktif</option>
                            <option value="void" @selected(request('status') === 'void')>Void</option>
                        </select>
                    </div>
                    <div class="ob-filter-actions">
                        <button class="ob-btn" type="submit">Filter</button>
                        <a href="{{ route('accounting.opening-balances.index') }}" class="ob-btn">Reset</a>
                    </div>
                </form>

                @if ($openingJournals->isEmpty())
                    <div class="ob-empty">Belum ada data saldo awal.</div>
                @else
                    <div class="ob-table-wrap">
                        <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Saldo Awal</th>
                                    <th>Kas / Bank</th>
                                    <th>Status</th>
                                    <th class="ob-num">Nominal</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($openingJournals as $journal)
                                    @php
                                        $cashLine = $journal->lines->first(fn($line) => (float) $line->debit > 0);
                                        $amount = (float) ($cashLine?->debit ?? 0);
                                        $cashName = $cashLine?->account?->name ?? '-';
                                        $cashCode = $cashLine?->account?->code;
                                        $isVoided = !is_null($journal->voided_at);
                                    @endphp
                                    <tr class="{{ $isVoided ? 'ob-row-void' : '' }}">
                                        <td>
                                            <span class="ob-date">{{ $journal->date?->format('Y-m-d') ?? \Illuminate\Support\Carbon::parse($journal->date)->format('Y-m-d') }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('accounting.journals.show', $journal) }}" class="ob-title">
                                                {{ $journal->description ?: 'Saldo Awal' }}
                                            </a>
                                            <div class="ob-meta">
                                                {{ $isVoided ? 'Dibatalkan dengan jurnal pembalik.' : 'Saldo awal sedang berlaku.' }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="ob-account">{{ $cashName }}</div>
                                            <div class="ob-meta">{{ $cashCode ? 'Kode ' . $cashCode : 'Rekening yang diisi' }}</div>
                                        </td>
                                        <td>
                                            <span class="ob-status {{ $isVoided ? 'ob-status-void' : 'ob-status-active' }}">
                                                {{ $isVoided ? 'Void' : 'Aktif' }}
                                            </span>
                                        </td>
                                        <td class="ob-num">Rp {{ $fmt($amount) }}</td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <a href="{{ route('accounting.journals.show', $journal) }}" class="ob-btn">Detail</a>
                                                @if (!$isVoided)
                                                    <form method="POST"
                                                        action="{{ route('accounting.opening-balances.void', $journal) }}"
                                                        class="d-inline"
                                                        data-gf-confirm
                                                        data-gf-confirm-title="Void saldo awal?"
                                                        data-gf-confirm-text="Sistem akan membuat jurnal pembalik untuk saldo awal ini."
                                                        data-gf-confirm-icon="warning"
                                                        data-gf-confirm-ok="Ya, void">
                                                        @csrf
                                                        <input type="hidden" name="reason" value="Manual void">
                                                        <button class="ob-btn ob-btn-danger" type="submit">Void</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="ob-mobile-list">
                        @foreach ($openingJournals as $journal)
                            @php
                                $cashLine = $journal->lines->first(fn($line) => (float) $line->debit > 0);
                                $amount = (float) ($cashLine?->debit ?? 0);
                                $cashName = $cashLine?->account?->name ?? '-';
                                $cashCode = $cashLine?->account?->code;
                                $isVoided = !is_null($journal->voided_at);
                            @endphp
                            <div class="ob-mobile-card {{ $isVoided ? 'ob-row-void' : '' }}">
                                <div class="ob-mobile-top">
                                    <div class="ob-mobile-title">
                                        <span class="ob-date">{{ $journal->date?->format('Y-m-d') ?? \Illuminate\Support\Carbon::parse($journal->date)->format('Y-m-d') }}</span>
                                        <div class="mt-2">
                                            <a href="{{ route('accounting.journals.show', $journal) }}" class="ob-title">
                                                {{ $journal->description ?: 'Saldo Awal' }}
                                            </a>
                                            <div class="ob-meta">{{ $cashCode ? $cashCode . ' · ' : '' }}{{ $cashName }}</div>
                                        </div>
                                    </div>
                                    <div class="ob-mobile-amount">Rp {{ $fmt($amount) }}</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <span class="ob-status {{ $isVoided ? 'ob-status-void' : 'ob-status-active' }}">
                                        {{ $isVoided ? 'Void' : 'Aktif' }}
                                    </span>
                                    <span class="ob-meta">{{ $isVoided ? 'Sudah dibatalkan' : 'Sedang berlaku' }}</span>
                                </div>
                                <div class="ob-mobile-actions">
                                    <a href="{{ route('accounting.journals.show', $journal) }}" class="ob-btn">Detail</a>
                                    @if (!$isVoided)
                                        <form method="POST"
                                            action="{{ route('accounting.opening-balances.void', $journal) }}"
                                            class="d-flex flex-fill"
                                            data-gf-confirm
                                            data-gf-confirm-title="Void saldo awal?"
                                            data-gf-confirm-text="Sistem akan membuat jurnal pembalik untuk saldo awal ini."
                                            data-gf-confirm-icon="warning"
                                            data-gf-confirm-ok="Ya, void">
                                            @csrf
                                            <input type="hidden" name="reason" value="Manual void">
                                            <button class="ob-btn ob-btn-danger w-100" type="submit">Void</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-gf.panel>

            <div>
                {{ $openingJournals->links() }}
            </div>
        </div>
    </x-gf.page>
@endsection
