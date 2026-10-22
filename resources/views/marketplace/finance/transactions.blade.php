@extends('marketplace.finance.layout')

@section('finance-sidebar')
    @include('marketplace.finance._transaction-sidebar')
@endsection

@section('finance-content')
    @php
        $money = fn ($value): string => number_format((float) $value, 2, ',', '.');
        $activeFilters = collect([
            filled($filters['store_id'] ?? null) ? 'Toko dipilih' : null,
            filled($filters['date_from'] ?? null) || filled($filters['date_to'] ?? null) ? 'Periode tanggal' : null,
            filled($filters['order_sn'] ?? null) ? 'Order SN: '.$filters['order_sn'] : null,
        ])->filter()->values();
    @endphp

    <style>
        .transactions-screen { --transactions-soft: #f8f9fc; --transactions-brand-soft: #eeefff; }
        .transactions-screen .transactions-filter-status { display: flex; align-items: center; gap: .4rem; min-width: 0; flex-wrap: wrap; margin: .15rem 0 .7rem; padding-inline: .1rem; }
        .transactions-screen .transactions-filter-status:empty { display: none; }
        .transactions-screen .transactions-filter-label { color: var(--finance-muted); font-size: .7rem; font-weight: 700; }
        .transactions-screen .transactions-filter-chip { display: inline-flex; align-items: center; max-width: 100%; min-height: 25px; overflow: hidden; padding: 0 .55rem; border: 1px solid #dfe1ff; border-radius: 999px; color: var(--finance-blue); background: var(--transactions-brand-soft); font-size: .67rem; font-weight: 750; text-overflow: ellipsis; white-space: nowrap; }
        .transactions-screen .transactions-panel { overflow: hidden; }
        .transactions-screen .transactions-panel-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.15rem; border-bottom: 1px solid var(--finance-line); background: #fff; }
        .transactions-screen .transactions-panel-title { display: flex; align-items: center; gap: .55rem; }
        .transactions-screen .transactions-panel-title-icon { display: grid; width: 32px; height: 32px; place-items: center; border-radius: 10px; color: var(--finance-blue); background: var(--transactions-brand-soft); }
        .transactions-screen .transactions-panel-title h3 { margin: 0; color: var(--finance-ink); font-size: .82rem; font-weight: 800; }
        .transactions-screen .transactions-panel-title p { margin: .15rem 0 0; color: var(--finance-muted); font-size: .74rem; }
        .transactions-screen .transactions-table { min-width: 790px; }
        .transactions-screen .transactions-table th { padding-top: .75rem; padding-bottom: .75rem; background: var(--transactions-soft); }
        .transactions-screen .transactions-table td { padding-top: .82rem; padding-bottom: .82rem; }
        .transactions-screen .transactions-table tbody tr { transition: background-color .16s ease; }
        .transactions-screen .transactions-table tbody tr:hover { background: #fbfbff; }
        .transactions-screen .transaction-order { color: var(--finance-ink); font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .78rem; font-weight: 800; }
        .transactions-screen .transaction-store { display: block; max-width: 150px; margin-top: .2rem; overflow: hidden; color: var(--finance-muted); font-size: .7rem; text-overflow: ellipsis; white-space: nowrap; }
        .transactions-screen .transaction-context { color: var(--finance-muted); font-size: .74rem; line-height: 1.45; }
        .transactions-screen .transaction-money { color: var(--finance-ink); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .transactions-screen .transaction-money.is-net { color: #067647; font-weight: 800; }
        .transactions-screen .transaction-status { display: inline-flex; align-items: center; min-height: 25px; padding: 0 .56rem; border-radius: 999px; font-size: .68rem; font-weight: 800; white-space: nowrap; }
        .transactions-screen .transaction-status.is-released { color: #067647; background: #ecfdf3; }
        .transactions-screen .transaction-status.is-to-release { color: #a15c07; background: #fffaeb; }
        .transactions-screen .transaction-status.is-other { color: #475467; background: #f2f4f7; }
        .transactions-screen .transaction-journal { display: inline-flex; align-items: center; gap: .3rem; min-height: 25px; padding: 0 .52rem; border: 1px solid #e4e7ec; border-radius: 999px; color: #475467; font-size: .68rem; font-weight: 800; white-space: nowrap; }
        .transactions-screen .transaction-journal.is-complete { color: #067647; border-color: #abefc6; background: #ecfdf3; }
        .transactions-screen .transactions-mobile { display: none; }
        .transactions-screen .transactions-empty { display: grid; justify-items: center; gap: .35rem; padding: 3.5rem 1rem; color: var(--finance-muted); text-align: center; }
        .transactions-screen .transactions-empty i { display: grid; width: 42px; height: 42px; margin-bottom: .25rem; place-items: center; border-radius: 14px; color: var(--finance-blue); background: var(--transactions-brand-soft); font-size: 1.2rem; }
        .transactions-screen .transactions-empty strong { color: var(--finance-ink); font-size: .82rem; }
        .transactions-screen .transactions-empty span { max-width: 28rem; font-size: .72rem; }
        @media (max-width: 767.98px) {
            .transactions-screen .transactions-panel-head { align-items: flex-start; padding: .9rem 1rem; }
            .transactions-screen .transactions-panel-title p { line-height: 1.4; }
            .transactions-screen .transactions-table-wrap { display: none; }
            .transactions-screen .transactions-mobile { display: grid; gap: .7rem; padding: .75rem; background: var(--transactions-soft); }
            .transactions-screen .transaction-card { padding: .9rem; border: 1px solid var(--finance-line); border-radius: 14px; background: #fff; box-shadow: 0 4px 14px rgba(15, 23, 42, .045); }
            .transactions-screen .transaction-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .7rem; padding-bottom: .7rem; border-bottom: 1px solid #eef0f3; }
            .transactions-screen .transaction-card-store { display: block; margin-top: .22rem; color: var(--finance-muted); font-size: .68rem; }
            .transactions-screen .transaction-card-main { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem .85rem; padding-top: .75rem; }
            .transactions-screen .transaction-card-field span { display: block; margin-bottom: .2rem; color: var(--finance-muted); font-size: .68rem; font-weight: 750; }
            .transactions-screen .transaction-card-field strong { display: block; color: var(--finance-ink); font-size: .8rem; font-weight: 800; font-variant-numeric: tabular-nums; }
            .transactions-screen .transaction-card-field strong.is-net { color: #067647; }
            .transactions-screen .transaction-card-context { grid-column: 1 / -1; padding-top: .65rem; border-top: 1px solid #eef0f3; color: var(--finance-muted); font-size: .72rem; line-height: 1.45; }
            .transactions-screen .transactions-mobile-empty { padding: 2.8rem 1rem; border: 1px dashed #d0d5dd; border-radius: 12px; background: #fff; }
            .transactions-screen .finance-pagination { padding: .85rem 1rem !important; }
        }
        @supports (container-type: inline-size) {
            @container finance-page (max-width: 767.98px) {
                .transactions-screen .transactions-panel-head { align-items: flex-start; padding: .9rem 1rem; }
                .transactions-screen .transactions-panel-title p { line-height: 1.4; }
                .transactions-screen .transactions-table-wrap { display: none; }
                .transactions-screen .transactions-mobile { display: grid; gap: .7rem; padding: .75rem; background: var(--transactions-soft); }
                .transactions-screen .transaction-card { padding: .9rem; border: 1px solid var(--finance-line); border-radius: 14px; background: #fff; box-shadow: 0 4px 14px rgba(15, 23, 42, .045); }
                .transactions-screen .transaction-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .7rem; padding-bottom: .7rem; border-bottom: 1px solid #eef0f3; }
                .transactions-screen .transaction-card-store { display: block; margin-top: .22rem; color: var(--finance-muted); font-size: .68rem; }
                .transactions-screen .transaction-card-main { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem .85rem; padding-top: .75rem; }
                .transactions-screen .transaction-card-field span { display: block; margin-bottom: .2rem; color: var(--finance-muted); font-size: .68rem; font-weight: 750; }
                .transactions-screen .transaction-card-field strong { display: block; color: var(--finance-ink); font-size: .8rem; font-weight: 800; font-variant-numeric: tabular-nums; }
                .transactions-screen .transaction-card-field strong.is-net { color: #067647; }
                .transactions-screen .transaction-card-context { grid-column: 1 / -1; padding-top: .65rem; border-top: 1px solid #eef0f3; color: var(--finance-muted); font-size: .72rem; line-height: 1.45; }
                .transactions-screen .transactions-mobile-empty { padding: 2.8rem 1rem; border: 1px dashed #d0d5dd; border-radius: 12px; background: #fff; }
                .transactions-screen .finance-pagination { padding: .85rem 1rem !important; }
            }
        }
    </style>

    <section class="transactions-screen finance-tab-panel finance-transactions" aria-label="Daftar transaksi marketplace">
        <div class="transactions-filter-status" aria-label="Filter aktif">
            @if ($activeFilters->isNotEmpty())
                <span class="transactions-filter-label">Filter aktif:</span>
                @foreach ($activeFilters as $activeFilter)
                    <span class="transactions-filter-chip">{{ $activeFilter }}</span>
                @endforeach
            @endif
        </div>

        <section class="finance-card transactions-panel card" aria-labelledby="transaction-list-title">
            <div class="transactions-panel-head">
                <div class="transactions-panel-title">
                    <span class="transactions-panel-title-icon" aria-hidden="true"><i class="bi bi-list-columns-reverse"></i></span>
                    <div>
                        <h3 id="transaction-list-title">Detail transaksi</h3>
                        <p>Data terfilter · gunakan Order SN untuk menemukan transaksi tertentu.</p>
                    </div>
                </div>
                <span class="finance-section-meta d-none d-sm-inline">{{ number_format($rows->total()) }} hasil</span>
            </div>

            <div class="transactions-table-wrap finance-table-wrap table-responsive">
                <table class="transactions-table finance-table table mb-0">
                    <caption class="visually-hidden">Daftar transaksi finance marketplace</caption>
                    <thead>
                        <tr><th>Order SN</th><th>Toko</th><th>Invoice / Shipment</th><th>Gross</th><th>Fees</th><th>Net</th><th>Income</th><th>Journal</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $journal = $row['journal_amount'];
                                $journalCount = collect($journal)->filter(fn ($value) => $value !== null)->count();
                                $incomeStatus = $row['income_status'] ?? 'unknown';
                                $incomeTone = $incomeStatus === 'released' ? 'is-released' : ($incomeStatus === 'to_release' ? 'is-to-release' : 'is-other');
                            @endphp
                            <tr>
                                <td><span class="transaction-order">{{ $row['order_sn'] }}</span></td>
                                <td><span class="transaction-store">{{ $storeNames[$row['store_id']] ?? 'Toko tidak diketahui' }}</span></td>
                                <td><span class="transaction-context">{{ $row['gross_sales_invoice'] !== null ? 'Invoice linked' : 'Invoice belum ada' }}<br>{{ $row['settlement_received_amount'] > 0 ? 'Settlement diterima' : 'Shipment/settlement pending' }}</span></td>
                                <td><span class="transaction-money">Rp {{ $money($row['gross_sales_invoice'] ?? $row['escrow_gross'] ?? 0) }}</span></td>
                                <td><span class="transaction-money">Rp {{ $money(abs($row['total_components'])) }}</span></td>
                                <td><span class="transaction-money is-net">Rp {{ $money($row['actual_net_income']) }}</span></td>
                                <td><span class="transaction-status {{ $incomeTone }}"><span class="finance-status-dot" aria-hidden="true"></span>{{ str_replace('_', ' ', ucfirst($incomeStatus)) }}</span></td>
                                <td><span class="transaction-journal {{ $journalCount === 3 ? 'is-complete' : '' }}"><i class="bi bi-journal-text" aria-hidden="true"></i>{{ $journalCount }}/3 posted</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><div class="transactions-empty"><i class="bi bi-search" aria-hidden="true"></i><strong>Belum ada transaksi finance</strong><span>Coba ubah filter toko, periode, atau nomor order untuk melihat data lain.</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="transactions-mobile" aria-label="Daftar transaksi versi mobile">
                @forelse ($rows as $row)
                    @php
                        $journal = $row['journal_amount'];
                        $journalCount = collect($journal)->filter(fn ($value) => $value !== null)->count();
                        $incomeStatus = $row['income_status'] ?? 'unknown';
                        $incomeTone = $incomeStatus === 'released' ? 'is-released' : ($incomeStatus === 'to_release' ? 'is-to-release' : 'is-other');
                    @endphp
                    <article class="transaction-card">
                        <div class="transaction-card-top">
                            <div>
                                <span class="transaction-order">{{ $row['order_sn'] }}</span>
                                <span class="transaction-card-store">{{ $storeNames[$row['store_id']] ?? 'Toko tidak diketahui' }}</span>
                            </div>
                            <span class="transaction-status {{ $incomeTone }}"><span class="finance-status-dot" aria-hidden="true"></span>{{ str_replace('_', ' ', ucfirst($incomeStatus)) }}</span>
                        </div>
                        <div class="transaction-card-main">
                            <div class="transaction-card-field"><span>Net income</span><strong class="is-net">Rp {{ $money($row['actual_net_income']) }}</strong></div>
                            <div class="transaction-card-field"><span>Journal</span><strong><span class="transaction-journal {{ $journalCount === 3 ? 'is-complete' : '' }}"><i class="bi bi-journal-text" aria-hidden="true"></i>{{ $journalCount }}/3 posted</span></strong></div>
                            <div class="transaction-card-field"><span>Gross</span><strong>Rp {{ $money($row['gross_sales_invoice'] ?? $row['escrow_gross'] ?? 0) }}</strong></div>
                            <div class="transaction-card-field"><span>Fees</span><strong>Rp {{ $money(abs($row['total_components'])) }}</strong></div>
                            <div class="transaction-card-context">{{ $row['gross_sales_invoice'] !== null ? 'Invoice linked' : 'Invoice belum ada' }} · {{ $row['settlement_received_amount'] > 0 ? 'Settlement diterima' : 'Shipment/settlement pending' }}</div>
                        </div>
                    </article>
                @empty
                    <div class="transactions-empty transactions-mobile-empty"><i class="bi bi-search" aria-hidden="true"></i><strong>Belum ada transaksi finance</strong><span>Coba ubah filter toko, periode, atau nomor order untuk melihat data lain.</span></div>
                @endforelse
            </div>

            @if ($rows->hasPages())
                <div class="finance-pagination card-footer bg-white">
                    <span class="text-muted small">Menampilkan {{ number_format($rows->firstItem() ?? 0) }}–{{ number_format($rows->lastItem() ?? 0) }} dari {{ number_format($rows->total()) }} transaksi</span>
                    {{ $rows->onEachSide(1)->links() }}
                </div>
            @endif
        </section>
    </section>
@endsection
