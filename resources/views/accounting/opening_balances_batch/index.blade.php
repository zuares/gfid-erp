@extends('layouts.app')

@section('title', 'Accounting • Saldo Awal Batch')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $rows = $journals->getCollection();
    $activeRows = $rows->filter(fn($journal) => is_null($journal->voided_at));
    $voidRows = $rows->filter(fn($journal) => !is_null($journal->voided_at));
    $activeAmount = $activeRows->sum(fn($journal) => (float) ($journal->lines?->sum('debit') ?? 0));
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .obb-page { display: grid; gap: 1rem; }
        .obb-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .obb-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            min-height: 40px; padding: .55rem .95rem; border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, .10); background: #fff;
            color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850;
        }
        .obb-btn:hover { color: #0f172a; background: #f8fafc; }
        .obb-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .obb-btn-primary:hover { color: #fff; background: #1e293b; }
        .obb-btn-danger { color: #b91c1c; border-color: #fecaca; background: #fff5f5; }
        .obb-btn-danger:hover { color: #991b1b; background: #fee2e2; }
        .obb-kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
        .obb-kpi {
            border: 1px solid rgba(15, 23, 42, .08); border-radius: 12px;
            background: #fff; padding: .85rem .95rem;
        }
        .obb-kpi-label {
            color: #64748b; font-size: .68rem; font-weight: 900;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .obb-kpi-value {
            margin-top: .18rem; color: #0f172a; font-size: 1.18rem;
            font-weight: 950; line-height: 1.15; font-variant-numeric: tabular-nums;
        }
        .obb-kpi-note { margin-top: .2rem; color: #94a3b8; font-size: .74rem; }
        .obb-filter {
            display: grid; grid-template-columns: minmax(140px, .8fr) minmax(140px, .8fr) minmax(150px, .8fr) auto;
            gap: .55rem; align-items: end;
        }
        .obb-filter .form-control,
        .obb-filter .form-select {
            min-height: 40px; border-radius: 999px; border-color: rgba(15, 23, 42, .12);
            font-size: .84rem; font-weight: 760; box-shadow: none;
        }
        .obb-filter-label {
            display: block; margin-bottom: .28rem; color: #64748b;
            font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: .06em;
        }
        .obb-filter-actions { display: flex; gap: .45rem; }
        .obb-table-wrap { max-height: calc(100vh - 350px); overflow: auto; -webkit-overflow-scrolling: touch; }
        .obb-date {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 104px; border-radius: 999px; padding: .22rem .6rem;
            background: #f8fafc; color: #0f172a; font-weight: 900; font-variant-numeric: tabular-nums;
        }
        .obb-title { color: #0f172a; font-weight: 880; text-decoration: none; }
        .obb-title:hover { color: #1d4ed8; }
        .obb-meta { margin-top: .16rem; color: #64748b; font-size: .78rem; }
        .obb-status {
            display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px;
            padding: .22rem .6rem; font-size: .74rem; font-weight: 850; border: 1px solid transparent;
        }
        .obb-status::before { content: ''; width: 7px; height: 7px; border-radius: 999px; background: currentColor; }
        .obb-status-active { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .obb-status-void { color: #b91c1c; background: #fee2e2; border-color: #fecaca; }
        .obb-num { text-align: right; color: #0f172a; font-weight: 950; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .obb-row-void { opacity: .72; }
        .obb-empty { text-align: center; color: #64748b; padding: 2.4rem 1rem; }
        .obb-mobile-list { display: none; }
        @media (max-width: 768px) {
            .gf-master-header { padding: 12px 14px; border-radius: 14px; }
            .gf-master-title { font-size: 18px; }
            .gf-master-desc { font-size: 11.5px; }
            .gf-master-actions { flex: 1 1 100%; }
            .obb-actions { justify-content: stretch; }
            .obb-actions .obb-btn { flex: 1 1 auto; }
            .obb-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
            .obb-kpi { padding: .7rem .75rem; }
            .obb-kpi-value { font-size: 1.02rem; }
            .obb-filter { grid-template-columns: 1fr 1fr; }
            .obb-filter > div:nth-child(3),
            .obb-filter-actions { grid-column: 1 / -1; }
            .obb-filter-actions .obb-btn { flex: 1 1 0; }
            .obb-table-wrap { display: none; }
            .obb-mobile-list { display: grid; gap: .62rem; }
            .obb-mobile-card {
                display: grid; gap: .58rem; padding: .8rem;
                border: 1px solid rgba(15, 23, 42, .08); border-radius: 10px; background: #fff;
                box-shadow: 0 6px 18px rgba(15, 23, 42, .04);
            }
            .obb-mobile-top { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; }
            .obb-mobile-title { min-width: 0; }
            .obb-mobile-amount { color: #0f172a; font-weight: 950; font-variant-numeric: tabular-nums; white-space: nowrap; }
            .obb-mobile-actions { display: flex; gap: .45rem; }
            .obb-mobile-actions .obb-btn { flex: 1 1 0; min-height: 36px; padding: .42rem .7rem; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Accounting"
        title="Saldo Awal Batch"
        description="Input saldo awal banyak akun dalam satu jurnal yang harus balance antara debit dan kredit.">
        <x-slot:actions>
            <div class="obb-actions">
                <a href="{{ route('accounting.opening-balances.index') }}" class="obb-btn">Saldo Awal Single</a>
                <a href="{{ route('accounting.opening-balances-batch.create') }}" class="obb-btn obb-btn-primary">+ Batch Input</a>
                <a href="{{ route('accounting.journals.index') }}" class="obb-btn">Semua Jurnal</a>
            </div>
        </x-slot:actions>

        <div class="obb-page">
            @if (session('message'))
                <div class="alert alert-{{ session('status') === 'ok' ? 'success' : 'danger' }} mb-0">
                    {{ session('message') }}
                </div>
            @endif

            <div class="obb-kpi-grid">
                <div class="obb-kpi">
                    <div class="obb-kpi-label">Total Batch</div>
                    <div class="obb-kpi-value">{{ $fmt($journals->total()) }}</div>
                    <div class="obb-kpi-note">sesuai filter</div>
                </div>
                <div class="obb-kpi">
                    <div class="obb-kpi-label">Masih Aktif</div>
                    <div class="obb-kpi-value">{{ $fmt($activeRows->count()) }}</div>
                    <div class="obb-kpi-note">di halaman ini</div>
                </div>
                <div class="obb-kpi">
                    <div class="obb-kpi-label">Nominal Aktif</div>
                    <div class="obb-kpi-value">Rp {{ $fmt($activeAmount) }}</div>
                    <div class="obb-kpi-note">total debit halaman ini</div>
                </div>
                <div class="obb-kpi">
                    <div class="obb-kpi-label">Void</div>
                    <div class="obb-kpi-value">{{ $fmt($voidRows->count()) }}</div>
                    <div class="obb-kpi-note">dibatalkan di halaman ini</div>
                </div>
            </div>

            <x-gf.panel title="Daftar Batch" subtitle="Cek jurnal saldo awal batch yang masih aktif atau sudah void.">
                <form method="GET" class="obb-filter mb-3">
                    <div>
                        <label class="obb-filter-label" for="from">Dari Tanggal</label>
                        <input id="from" type="text" name="from" class="form-control" value="{{ request('from') }}" placeholder="YYYY-MM-DD" data-gf-date>
                    </div>
                    <div>
                        <label class="obb-filter-label" for="to">Sampai</label>
                        <input id="to" type="text" name="to" class="form-control" value="{{ request('to') }}" placeholder="YYYY-MM-DD" data-gf-date>
                    </div>
                    <div>
                        <label class="obb-filter-label" for="status">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="" @selected(request('status') === null || request('status') === '')>Semua status</option>
                            <option value="active" @selected(request('status') === 'active')>Aktif</option>
                            <option value="void" @selected(request('status') === 'void')>Void</option>
                        </select>
                    </div>
                    <div class="obb-filter-actions">
                        <button class="obb-btn" type="submit">Filter</button>
                        <a href="{{ route('accounting.opening-balances-batch.index') }}" class="obb-btn">Reset</a>
                    </div>
                </form>

                @if ($journals->isEmpty())
                    <div class="obb-empty">Belum ada data saldo awal batch.</div>
                @else
                    <div class="obb-table-wrap">
                        <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jurnal Batch</th>
                                    <th>Ringkasan Akun</th>
                                    <th>Status</th>
                                    <th class="obb-num">Nominal</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($journals as $journal)
                                    @php
                                        $isVoided = !is_null($journal->voided_at);
                                        $sumDebit = (float) ($journal->lines?->sum('debit') ?? 0);
                                        $accountsTouched = (int) ($journal->lines?->count() ?? 0);
                                        $sampleAccounts = $journal->lines
                                            ->take(2)
                                            ->map(fn($line) => $line->account?->name ?: ('Akun #' . $line->account_id))
                                            ->filter()
                                            ->values()
                                            ->all();
                                    @endphp
                                    <tr class="{{ $isVoided ? 'obb-row-void' : '' }}">
                                        <td><span class="obb-date">{{ \Illuminate\Support\Carbon::parse($journal->date)->format('Y-m-d') }}</span></td>
                                        <td>
                                            <a href="{{ route('accounting.journals.show', $journal) }}" class="obb-title">
                                                {{ $journal->description ?: 'Saldo Awal Batch' }}
                                            </a>
                                            <div class="obb-meta">
                                                {{ $journal->source_type }}
                                                @if ($journal->posted_at)
                                                    · Posted {{ \Illuminate\Support\Carbon::parse($journal->posted_at)->format('Y-m-d H:i') }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $accountsTouched }} akun</div>
                                            <div class="obb-meta">
                                                {{ count($sampleAccounts) ? implode(', ', $sampleAccounts) : '-' }}{{ $accountsTouched > 2 ? '...' : '' }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="obb-status {{ $isVoided ? 'obb-status-void' : 'obb-status-active' }}">
                                                {{ $isVoided ? 'Void' : 'Aktif' }}
                                            </span>
                                        </td>
                                        <td class="obb-num">Rp {{ $fmt($sumDebit) }}</td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <a href="{{ route('accounting.journals.show', $journal) }}" class="obb-btn">Detail</a>
                                                @if (!$isVoided)
                                                    <form method="POST"
                                                        action="{{ route('accounting.opening-balances-batch.void', $journal) }}"
                                                        class="d-inline"
                                                        data-gf-confirm
                                                        data-gf-confirm-title="Void saldo awal batch?"
                                                        data-gf-confirm-text="Sistem akan membuat jurnal pembalik untuk seluruh baris batch ini."
                                                        data-gf-confirm-icon="warning"
                                                        data-gf-confirm-ok="Ya, void">
                                                        @csrf
                                                        <input type="hidden" name="reason" value="Manual void">
                                                        <button class="obb-btn obb-btn-danger" type="submit">Void</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="obb-mobile-list">
                        @foreach ($journals as $journal)
                            @php
                                $isVoided = !is_null($journal->voided_at);
                                $sumDebit = (float) ($journal->lines?->sum('debit') ?? 0);
                                $accountsTouched = (int) ($journal->lines?->count() ?? 0);
                                $sampleAccounts = $journal->lines
                                    ->take(2)
                                    ->map(fn($line) => $line->account?->name ?: ('Akun #' . $line->account_id))
                                    ->filter()
                                    ->values()
                                    ->all();
                            @endphp
                            <div class="obb-mobile-card {{ $isVoided ? 'obb-row-void' : '' }}">
                                <div class="obb-mobile-top">
                                    <div class="obb-mobile-title">
                                        <span class="obb-date">{{ \Illuminate\Support\Carbon::parse($journal->date)->format('Y-m-d') }}</span>
                                        <div class="mt-2">
                                            <a href="{{ route('accounting.journals.show', $journal) }}" class="obb-title">
                                                {{ $journal->description ?: 'Saldo Awal Batch' }}
                                            </a>
                                            <div class="obb-meta">
                                                {{ $accountsTouched }} akun · {{ count($sampleAccounts) ? implode(', ', $sampleAccounts) : '-' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="obb-mobile-amount">Rp {{ $fmt($sumDebit) }}</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <span class="obb-status {{ $isVoided ? 'obb-status-void' : 'obb-status-active' }}">
                                        {{ $isVoided ? 'Void' : 'Aktif' }}
                                    </span>
                                    <span class="obb-meta">{{ $isVoided ? 'Sudah dibatalkan' : 'Sedang berlaku' }}</span>
                                </div>
                                <div class="obb-mobile-actions">
                                    <a href="{{ route('accounting.journals.show', $journal) }}" class="obb-btn">Detail</a>
                                    @if (!$isVoided)
                                        <form method="POST"
                                            action="{{ route('accounting.opening-balances-batch.void', $journal) }}"
                                            class="d-flex flex-fill"
                                            data-gf-confirm
                                            data-gf-confirm-title="Void saldo awal batch?"
                                            data-gf-confirm-text="Sistem akan membuat jurnal pembalik untuk seluruh baris batch ini."
                                            data-gf-confirm-icon="warning"
                                            data-gf-confirm-ok="Ya, void">
                                            @csrf
                                            <input type="hidden" name="reason" value="Manual void">
                                            <button class="obb-btn obb-btn-danger w-100" type="submit">Void</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-gf.panel>

            <div>
                {{ $journals->links() }}
            </div>
        </div>
    </x-gf.page>
@endsection
