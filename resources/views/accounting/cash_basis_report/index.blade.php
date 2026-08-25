@extends('layouts.app')

@section('title', 'Accounting • Laporan Cash Basis')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .cbr-page { display: grid; gap: 1rem; }
        .cbr-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .cbr-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            min-height: 40px; padding: .55rem .95rem; border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, .10); background: #fff;
            color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850;
        }
        .cbr-btn:hover { color: #0f172a; background: #f8fafc; }
        .cbr-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .cbr-btn-primary:hover { color: #fff; background: #1e293b; }
        .cbr-filter {
            display: grid; grid-template-columns: minmax(140px, .8fr) minmax(140px, .8fr) auto;
            gap: .55rem; align-items: end;
        }
        .cbr-filter .form-control {
            min-height: 40px; border-radius: 999px; border-color: rgba(15, 23, 42, .12);
            font-size: .84rem; font-weight: 760; box-shadow: none;
        }
        .cbr-filter-label {
            display: block; margin-bottom: .28rem; color: #64748b;
            font-size: .68rem; font-weight: 900; text-transform: uppercase; letter-spacing: .06em;
        }
        .cbr-filter-actions { display: flex; gap: .45rem; }
        .cbr-kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
        .cbr-kpi {
            border: 1px solid rgba(15, 23, 42, .08); border-radius: 12px;
            background: #fff; padding: .85rem .95rem;
        }
        .cbr-kpi-label {
            color: #64748b; font-size: .68rem; font-weight: 900;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .cbr-kpi-value {
            margin-top: .18rem; color: #0f172a; font-size: 1.18rem;
            font-weight: 950; line-height: 1.15; font-variant-numeric: tabular-nums;
        }
        .cbr-kpi-value.pos { color: #166534; }
        .cbr-kpi-value.neg { color: #b91c1c; }
        .cbr-kpi-note { margin-top: .2rem; color: #94a3b8; font-size: .74rem; }
        .cbr-grid-2 { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 1rem; align-items: start; }
        .cbr-list { display: grid; gap: .55rem; }
        .cbr-row {
            display: flex; justify-content: space-between; gap: .75rem; align-items: center;
            border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; padding: .72rem .8rem;
        }
        .cbr-row-title { color: #0f172a; font-weight: 900; }
        .cbr-row-meta { margin-top: .12rem; color: #64748b; font-size: .78rem; }
        .cbr-row-num { color: #0f172a; font-weight: 950; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .cbr-row-num.neg { color: #b91c1c; }
        .cbr-empty { color: #64748b; padding: 1.8rem 1rem; text-align: center; }
        .cbr-table-wrap { overflow: auto; -webkit-overflow-scrolling: touch; }
        .cbr-click-row { cursor: pointer; }
        .cbr-click-row:hover td { background: #f8fafc; }
        .cbr-status {
            display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px;
            padding: .2rem .55rem; font-size: .72rem; font-weight: 850; border: 1px solid transparent;
        }
        .cbr-status::before { content: ''; width: 7px; height: 7px; border-radius: 999px; background: currentColor; }
        .cbr-status-draft { color: #b45309; background: #fef3c7; border-color: #fde68a; }
        .cbr-status-posted { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .cbr-status-void { color: #b91c1c; background: #fee2e2; border-color: #fecaca; }
        .cbr-status-out { color: #b91c1c; background: #fee2e2; border-color: #fecaca; }
        .cbr-mobile-list { display: none; }
        @media (max-width: 768px) {
            .gf-master-header { padding: 12px 14px; border-radius: 14px; }
            .gf-master-title { font-size: 18px; }
            .gf-master-desc { font-size: 11.5px; }
            .gf-master-actions { flex: 1 1 100%; }
            .cbr-actions { justify-content: stretch; }
            .cbr-actions .cbr-btn { flex: 1 1 auto; }
            .cbr-filter { grid-template-columns: 1fr 1fr; }
            .cbr-filter-actions { grid-column: 1 / -1; }
            .cbr-filter-actions .cbr-btn { flex: 1 1 0; }
            .cbr-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
            .cbr-kpi { padding: .7rem .75rem; }
            .cbr-kpi-value { font-size: 1.02rem; }
            .cbr-grid-2 { grid-template-columns: 1fr; }
            .cbr-table-wrap { display: none; }
            .cbr-mobile-list { display: grid; gap: .62rem; }
            .cbr-mobile-card {
                display: grid; gap: .5rem; padding: .8rem;
                border: 1px solid rgba(15, 23, 42, .08); border-radius: 10px; background: #fff;
                box-shadow: 0 6px 18px rgba(15, 23, 42, .04); cursor: pointer;
            }
        }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Accounting"
        title="Laporan Arus Kas (Basis Kas)"
        description="Metode langsung: hanya penerimaan dan pengeluaran kas yang sudah posted.">
        <x-slot:actions>
            <div class="cbr-actions">
                <a href="{{ route('accounting.cash-receipts.index') }}" class="cbr-btn cbr-btn-primary">Cash Receipts</a>
                <a href="{{ route('accounting.cash-expenses.index') }}" class="cbr-btn cbr-btn-primary">Cash Expenses</a>
                <a href="{{ route('accounting.accounts.index') }}" class="cbr-btn">Ledger</a>
            </div>
        </x-slot:actions>

        <div class="cbr-page">
            <x-gf.panel title="Periode Laporan" subtitle="Laporan menggunakan basis kas dan hanya menghitung transaksi posted.">
                <form class="cbr-filter" method="GET" action="{{ route('accounting.cash-basis-report.index') }}">
                    <div>
                        <label class="cbr-filter-label" for="from">Dari Tanggal</label>
                        <input id="from" type="text" name="from" class="form-control" value="{{ $from }}" data-gf-date>
                    </div>
                    <div>
                        <label class="cbr-filter-label" for="to">Sampai</label>
                        <input id="to" type="text" name="to" class="form-control" value="{{ $to }}" data-gf-date>
                    </div>
                    <div class="cbr-filter-actions">
                        <button class="cbr-btn" type="submit">Filter</button>
                        <a href="{{ route('accounting.cash-basis-report.index') }}" class="cbr-btn">Reset</a>
                    </div>
                </form>
            </x-gf.panel>

            <div class="cbr-kpi-grid">
                <div class="cbr-kpi">
                    <div class="cbr-kpi-label">Saldo Awal Periode</div>
                    <div class="cbr-kpi-value {{ $openingCashTotal < 0 ? 'neg' : 'pos' }}">Rp {{ $fmt($openingCashTotal) }}</div>
                    <div class="cbr-kpi-note">sebelum {{ $from }}</div>
                </div>
                <div class="cbr-kpi">
                    <div class="cbr-kpi-label">Kas Masuk</div>
                    <div class="cbr-kpi-value pos">Rp {{ $fmt($cashInTotal) }}</div>
                    <div class="cbr-kpi-note">penerimaan dan marketplace</div>
                </div>
                <div class="cbr-kpi">
                    <div class="cbr-kpi-label">Kas Keluar</div>
                    <div class="cbr-kpi-value {{ $cashOutTotal < 0 ? 'neg' : '' }}">Rp {{ $fmt($cashOutTotal) }}</div>
                    <div class="cbr-kpi-note">operasional dan pembayaran PO</div>
                </div>
                <div class="cbr-kpi">
                    <div class="cbr-kpi-label">Saldo Akhir Periode</div>
                    <div class="cbr-kpi-value {{ $cashTotal < 0 ? 'neg' : 'pos' }}">Rp {{ $fmt($cashTotal) }}</div>
                    <div class="cbr-kpi-note">arus bersih {{ $cashNetFlow < 0 ? 'negatif' : 'positif' }}</div>
                </div>
            </div>

            <x-gf.panel title="Ringkasan Arus Kas" subtitle="Kas masuk dan kas keluar selama periode yang dipilih.">
                <div class="cbr-grid-2">
                    <div class="cbr-table-wrap">
                        <table class="table align-middle mb-0 gf-clean-table">
                            <thead>
                                <tr>
                                    <th>Kas Masuk</th>
                                    <th class="text-end">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($cashInRows as $row)
                                    <tr>
                                        <td>
                                            <div class="cbr-row-title">{{ $row->name }}</div>
                                            <div class="cbr-row-meta">{{ $row->code }} · {{ $fmt($row->total_docs) }} transaksi</div>
                                        </td>
                                        <td class="text-end fw-bold">Rp {{ $fmt($row->total_amount) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="cbr-empty">Belum ada kas masuk.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr><th>Total Kas Masuk</th><th class="text-end">Rp {{ $fmt($cashInTotal) }}</th></tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="cbr-table-wrap">
                        <table class="table align-middle mb-0 gf-clean-table">
                            <thead>
                                <tr>
                                    <th>Kas Keluar</th>
                                    <th class="text-end">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($cashOutRows as $row)
                                    <tr>
                                        <td>
                                            <div class="cbr-row-title">{{ $row->name }}</div>
                                            <div class="cbr-row-meta">{{ $row->code }} · {{ $fmt($row->total_docs) }} transaksi</div>
                                        </td>
                                        <td class="text-end fw-bold">Rp {{ $fmt($row->total_amount) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="cbr-empty">Belum ada kas keluar.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr><th>Total Kas Keluar</th><th class="text-end">Rp {{ $fmt($cashOutTotal) }}</th></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="cbr-row" style="margin-top:1rem">
                    <div>
                        <div class="cbr-row-title">Arus Kas Bersih</div>
                        <div class="cbr-row-meta">Kas masuk dikurangi kas keluar</div>
                    </div>
                    <div class="cbr-row-num {{ $cashNetFlow < 0 ? 'neg' : '' }}">Rp {{ $fmt($cashNetFlow) }}</div>
                </div>
            </x-gf.panel>

            <x-gf.panel title="Saldo Kas / Bank" subtitle="Saldo akhir per akun kas dan bank pada tanggal {{ $to }}.">
                <div class="cbr-list">
                    @forelse ($cashAccounts as $account)
                        <div class="cbr-row">
                            <div>
                                <div class="cbr-row-title">{{ $account->name }}</div>
                                <div class="cbr-row-meta">{{ $account->code }}</div>
                            </div>
                            <div class="cbr-row-num {{ (float) $account->balance < 0 ? 'neg' : '' }}">Rp {{ $fmt($account->balance) }}</div>
                        </div>
                    @empty
                        <div class="cbr-empty">Belum ada akun kas/bank aktif.</div>
                    @endforelse
                </div>
            </x-gf.panel>

            <x-gf.panel title="Transaksi Kas Terakhir" subtitle="Hanya transaksi posted; klik baris untuk membuka detail.">
                @if ($recentCashTransactions->isEmpty())
                    <div class="cbr-empty">Belum ada transaksi kas pada periode ini.</div>
                @else
                    <div class="cbr-table-wrap">
                        <table class="table table-hover align-middle mb-0 gf-clean-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Arah</th>
                                    <th>Keterangan</th>
                                    <th>Kategori / Sumber</th>
                                    <th>Kas / Bank</th>
                                    <th class="text-end">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentCashTransactions as $transaction)
                                    <tr class="cbr-click-row" data-href="{{ $transaction->_route }}" tabindex="0">
                                        <td>{{ optional($transaction->date)->format('Y-m-d') }}</td>
                                        <td>
                                            <span class="cbr-status {{ $transaction->direction === 'in' ? 'cbr-status-posted' : 'cbr-status-out' }}">
                                                {{ $transaction->direction === 'in' ? 'Masuk' : 'Keluar' }}
                                            </span>
                                        </td>
                                        <td>{{ $transaction->description }}</td>
                                        <td>{{ $transaction->category }}</td>
                                        <td>{{ $transaction->cash_account }}</td>
                                        <td class="text-end fw-bold {{ $transaction->direction === 'in' ? 'text-success' : 'text-danger' }}">
                                            {{ $transaction->direction === 'in' ? '+' : '-' }} Rp {{ $fmt($transaction->amount) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="cbr-mobile-list">
                        @foreach ($recentCashTransactions as $transaction)
                            <div class="cbr-mobile-card" data-href="{{ $transaction->_route }}" tabindex="0" role="link">
                                <div class="d-flex justify-content-between gap-2">
                                    <div>
                                        <div class="cbr-row-title">{{ $transaction->description }}</div>
                                        <div class="cbr-row-meta">
                                            {{ optional($transaction->date)->format('Y-m-d') }} · {{ $transaction->category }}
                                        </div>
                                    </div>
                                    <div class="cbr-row-num {{ $transaction->direction === 'in' ? '' : 'neg' }}">
                                        {{ $transaction->direction === 'in' ? '+' : '-' }} Rp {{ $fmt($transaction->amount) }}
                                    </div>
                                </div>
                                <div class="cbr-row-meta">{{ $transaction->cash_account }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-href]').forEach((el) => {
                el.addEventListener('click', (event) => {
                    if (event.target.closest('a,button,input,select,textarea,form')) return;
                    window.location.href = el.dataset.href;
                });
                el.addEventListener('keydown', (event) => {
                    if (!['Enter', ' '].includes(event.key)) return;
                    event.preventDefault();
                    window.location.href = el.dataset.href;
                });
            });
        });
    </script>
@endpush
