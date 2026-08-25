@extends('layouts.app')

@section('title', 'Accounting • Laporan Cash Basis')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $statusLabels = [
        'draft' => 'Draft',
        'posted' => 'Tercatat',
        'void' => 'Dibatalkan',
    ];
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
        title="Laporan Cash Basis"
        description="Ringkasan awam untuk saldo kas/bank dan pengeluaran yang sudah tercatat.">
        <x-slot:actions>
            <div class="cbr-actions">
                <a href="{{ route('accounting.cash-receipts.index') }}" class="cbr-btn cbr-btn-primary">Cash Receipts</a>
                <a href="{{ route('accounting.cash-expenses.index') }}" class="cbr-btn cbr-btn-primary">Cash Expenses</a>
                <a href="{{ route('accounting.accounts.index') }}" class="cbr-btn">Accounts</a>
            </div>
        </x-slot:actions>

        <div class="cbr-page">
            <x-gf.panel title="Filter Periode" subtitle="Default mengikuti bulan berjalan.">
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
                    <div class="cbr-kpi-label">Saldo Kas/Bank</div>
                    <div class="cbr-kpi-value {{ $cashTotal < 0 ? 'neg' : 'pos' }}">Rp {{ $fmt($cashTotal) }}</div>
                    <div class="cbr-kpi-note">saldo aktif semua kas/bank</div>
                </div>
                <div class="cbr-kpi">
                    <div class="cbr-kpi-label">Penerimaan Tercatat</div>
                    <div class="cbr-kpi-value pos">Rp {{ $fmt($postedReceiptTotal ?? 0) }}</div>
                    <div class="cbr-kpi-note">{{ $fmt($postedReceiptCount ?? 0) }} transaksi posted</div>
                </div>
                <div class="cbr-kpi">
                    <div class="cbr-kpi-label">Kas Keluar Tercatat</div>
                    <div class="cbr-kpi-value">Rp {{ $fmt($postedExpenseTotal) }}</div>
                    <div class="cbr-kpi-note">{{ $fmt($postedExpenseCount) }} transaksi posted · operasional + pembayaran PO</div>
                </div>
                <div class="cbr-kpi">
                    <div class="cbr-kpi-label">Draft</div>
                    <div class="cbr-kpi-value">Rp {{ $fmt(($draftExpenseTotal ?? 0) + ($draftReceiptTotal ?? 0)) }}</div>
                    <div class="cbr-kpi-note">{{ $fmt(($draftExpenseCount ?? 0) + ($draftReceiptCount ?? 0)) }} belum masuk jurnal</div>
                </div>
            </div>

            <div class="cbr-grid-2">
                <x-gf.panel title="Saldo Kas / Bank" subtitle="Klik Accounts untuk lihat ledger lebih detail.">
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

                <x-gf.panel title="Pengeluaran Per Kategori" subtitle="Hanya transaksi yang sudah Tercatat.">
                    <div class="cbr-list">
                        @forelse ($expenseByCategory as $row)
                            <div class="cbr-row">
                                <div>
                                    <div class="cbr-row-title">{{ $row->name }}</div>
                                    <div class="cbr-row-meta">{{ $row->code }} · {{ $fmt($row->total_docs) }} transaksi</div>
                                </div>
                                <div class="cbr-row-num">Rp {{ $fmt($row->total_amount) }}</div>
                            </div>
                        @empty
                            <div class="cbr-empty">Belum ada pengeluaran tercatat pada periode ini.</div>
                        @endforelse
                    </div>
                </x-gf.panel>
            </div>

            <x-gf.panel title="Penerimaan Per Sumber" subtitle="Hanya transaksi penerimaan yang sudah Tercatat.">
                <div class="cbr-list">
                    @if ($receiptBySource->isEmpty() && ($payoutByMarketplace ?? collect())->isEmpty())
                        <div class="cbr-empty">Belum ada penerimaan tercatat pada periode ini.</div>
                    @else
                        @foreach ($receiptBySource as $row)
                            <div class="cbr-row">
                                <div>
                                    <div class="cbr-row-title">{{ $row->name }}</div>
                                    <div class="cbr-row-meta">{{ $row->code }} · {{ $fmt($row->total_docs) }} transaksi</div>
                                </div>
                                <div class="cbr-row-num">Rp {{ $fmt($row->total_amount) }}</div>
                            </div>
                        @endforeach
                        @foreach ($payoutByMarketplace ?? [] as $row)
                            <div class="cbr-row">
                                <div>
                                    <div class="cbr-row-title">🛒 {{ $row->marketplace_name }}</div>
                                    <div class="cbr-row-meta">Marketplace · {{ $fmt($row->total_docs) }} transaksi</div>
                                </div>
                                <div class="cbr-row-num">Rp {{ $fmt($row->total_amount) }}</div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </x-gf.panel>

            <x-gf.panel title="Pengeluaran Per Kas / Bank" subtitle="Melihat sumber uang yang paling sering dipakai.">
                <div class="cbr-list">
                    @forelse ($expenseByCash as $row)
                        <div class="cbr-row">
                            <div>
                                <div class="cbr-row-title">{{ $row->name }}</div>
                                <div class="cbr-row-meta">{{ $row->code }} · {{ $fmt($row->total_docs) }} transaksi</div>
                            </div>
                            <div class="cbr-row-num">Rp {{ $fmt($row->total_amount) }}</div>
                        </div>
                    @empty
                        <div class="cbr-empty">Belum ada kas/bank yang dipakai pada periode ini.</div>
                    @endforelse
                </div>
            </x-gf.panel>

            <x-gf.panel title="Pembayaran Pembelian Per Kas / Bank" subtitle="DP dan pelunasan PO yang benar-benar mengurangi kas/bank.">
                <div class="cbr-list">
                    @forelse ($purchasePaymentByCash as $row)
                        <div class="cbr-row">
                            <div>
                                <div class="cbr-row-title">{{ $row->name }}</div>
                                <div class="cbr-row-meta">{{ $row->code }} · {{ $fmt($row->total_docs) }} transaksi</div>
                            </div>
                            <div class="cbr-row-num">Rp {{ $fmt($row->total_amount) }}</div>
                        </div>
                    @empty
                        <div class="cbr-empty">Belum ada pembayaran pembelian pada periode ini.</div>
                    @endforelse
                </div>
            </x-gf.panel>

            <x-gf.panel title="Penerimaan Terakhir" subtitle="Kas masuk + marketplace. Klik untuk buka detail.">
                @if ($recentReceipts->isEmpty())
                    <div class="cbr-empty">Belum ada penerimaan pada periode ini.</div>
                @else
                    <div class="cbr-table-wrap">
                        <table class="table table-hover align-middle mb-0 gf-clean-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Sumber</th>
                                    <th>Terima Ke</th>
                                    <th>Status</th>
                                    <th class="text-end">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentReceipts as $r)
                                    <tr class="cbr-click-row" data-href="{{ $r->_route }}" tabindex="0">
                                        <td>{{ \Illuminate\Support\Carbon::parse($r->date)->format('Y-m-d') }}</td>
                                        <td>
                                            <div class="cbr-row-title">{{ $r->description ?: 'Penerimaan' }}</div>
                                            <div class="cbr-row-meta">
                                                #{{ $r->id }}{{ $r->reference ? ' · ' . $r->reference : '' }}
                                                @if($r->_type === 'marketplace_payout')
                                                    <span style="background:#eff6ff;color:#1d4ed8;border-radius:4px;padding:.1rem .35rem;font-size:.7rem;font-weight:800;margin-left:.25rem">MP</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>{{ $r->source_name }}</td>
                                        <td>{{ $r->bank_name }}</td>
                                        <td>
                                            <span class="cbr-status cbr-status-{{ $r->status }}">
                                                {{ $statusLabels[$r->status] ?? ucfirst((string) $r->status) }}
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold">Rp {{ $fmt($r->amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="cbr-mobile-list">
                        @foreach ($recentReceipts as $r)
                            <div class="cbr-mobile-card" data-href="{{ $r->_route }}" tabindex="0" role="link">
                                <div class="d-flex justify-content-between gap-2">
                                    <div>
                                        <div class="cbr-row-title">{{ $r->description ?: 'Penerimaan' }}</div>
                                        <div class="cbr-row-meta">{{ \Illuminate\Support\Carbon::parse($r->date)->format('Y-m-d') }} · {{ $r->source_name }}</div>
                                    </div>
                                    <div class="cbr-row-num">Rp {{ $fmt($r->amount) }}</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <span class="cbr-status cbr-status-{{ $r->status }}">
                                        {{ $statusLabels[$r->status] ?? ucfirst((string) $r->status) }}
                                    </span>
                                    <span class="cbr-row-meta">{{ $r->bank_name }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-gf.panel>

            <x-gf.panel title="Transaksi Terakhir" subtitle="Klik baris untuk buka detail dan posting jika masih draft.">
                @if ($recentExpenses->isEmpty())
                    <div class="cbr-empty">Belum ada transaksi pada periode ini.</div>
                @else
                    <div class="cbr-table-wrap">
                        <table class="table table-hover align-middle mb-0 gf-clean-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Kategori</th>
                                    <th>Bayar Dari</th>
                                    <th>Status</th>
                                    <th class="text-end">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentExpenses as $expense)
                                    <tr class="cbr-click-row" data-href="{{ route('accounting.cash-expenses.show', $expense) }}" tabindex="0">
                                        <td>{{ \Illuminate\Support\Carbon::parse($expense->date)->format('Y-m-d') }}</td>
                                        <td>
                                            <div class="cbr-row-title">{{ $expense->description ?: 'Pengeluaran' }}</div>
                                            <div class="cbr-row-meta">ID #{{ $expense->id }}{{ $expense->reference ? ' · Ref: ' . $expense->reference : '' }}</div>
                                        </td>
                                        <td>{{ $expense->expenseAccount?->name ?? '-' }}</td>
                                        <td>{{ $expense->cashAccount?->name ?? '-' }}</td>
                                        <td>
                                            <span class="cbr-status cbr-status-{{ $expense->status }}">
                                                {{ $statusLabels[$expense->status] ?? ucfirst((string) $expense->status) }}
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold">Rp {{ $fmt($expense->amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="cbr-mobile-list">
                        @foreach ($recentExpenses as $expense)
                            <div class="cbr-mobile-card" data-href="{{ route('accounting.cash-expenses.show', $expense) }}" tabindex="0" role="link">
                                <div class="d-flex justify-content-between gap-2">
                                    <div>
                                        <div class="cbr-row-title">{{ $expense->description ?: 'Pengeluaran' }}</div>
                                        <div class="cbr-row-meta">{{ \Illuminate\Support\Carbon::parse($expense->date)->format('Y-m-d') }} · {{ $expense->expenseAccount?->name ?? '-' }}</div>
                                    </div>
                                    <div class="cbr-row-num">Rp {{ $fmt($expense->amount) }}</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <span class="cbr-status cbr-status-{{ $expense->status }}">
                                        {{ $statusLabels[$expense->status] ?? ucfirst((string) $expense->status) }}
                                    </span>
                                    <span class="cbr-row-meta">{{ $expense->cashAccount?->name ?? '-' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-gf.panel>

            <x-gf.panel title="Pembayaran PO Terakhir" subtitle="Pembayaran aktual; tidak menggunakan total PO sebagai kas keluar.">
                <div class="cbr-list">
                    @forelse ($recentPurchasePayments as $payment)
                        <div class="cbr-row">
                            <div>
                                <div class="cbr-row-title">
                                    <a href="{{ route('purchasing.purchase_orders.show', $payment->purchaseOrder) }}">
                                        {{ $payment->purchaseOrder?->code ?? 'PO #' . $payment->purchase_order_id }}
                                    </a>
                                </div>
                                <div class="cbr-row-meta">
                                    {{ $payment->purchaseOrder?->supplier?->name ?? '-' }}
                                    · {{ optional($payment->date)->format('Y-m-d') }}
                                    · {{ $payment->type === 'dp' ? 'DP' : 'Pelunasan' }}
                                    · {{ $payment->cashAccount?->name ?? '-' }}
                                </div>
                            </div>
                            <div class="cbr-row-num">Rp {{ $fmt($payment->amount) }}</div>
                        </div>
                    @empty
                        <div class="cbr-empty">Belum ada pembayaran PO pada periode ini.</div>
                    @endforelse
                </div>
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
