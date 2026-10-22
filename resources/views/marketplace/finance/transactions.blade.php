@extends('marketplace.finance.layout')

@section('finance-sidebar')
    @include('marketplace.finance._transaction-sidebar')
@endsection

@section('finance-content')
    @php $money = fn ($value): string => number_format((float) $value, 2, ',', '.'); @endphp
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
        <div>
            <div class="finance-eyebrow mb-1">Marketplace ledger</div>
            <h2 class="h4 mb-1">Transactions</h2>
            <div class="finance-caption">Semua transaksi finance marketplace, termasuk biaya iklan dan adjustment.</div>
        </div>
        <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ number_format($rows->total()) }} transaksi</span>
    </div>
    <div class="finance-card card">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <div><div class="finance-section-meta">Ledger terfilter</div><h3 class="h6 mb-0 mt-1">Detail transaksi</h3></div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Order SN</th><th>Toko</th><th>Invoice / Shipment</th><th>Gross</th><th>Fees</th><th>Net</th><th>Income</th><th>Journal</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $journal = $row['journal_amount'];
                            $journalCount = collect($journal)->filter(fn ($value) => $value !== null)->count();
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $row['order_sn'] }}</td>
                            <td>{{ $storeNames[$row['store_id']] ?? 'Toko tidak diketahui' }}</td>
                            <td class="small">{{ $row['gross_sales_invoice'] !== null ? 'INV linked' : 'INV belum ada' }}<br>{{ $row['settlement_received_amount'] > 0 ? 'Settlement diterima' : 'Shipment/settlement pending' }}</td>
                            <td>Rp {{ $money($row['gross_sales_invoice'] ?? $row['escrow_gross'] ?? 0) }}</td>
                            <td>Rp {{ $money(abs($row['total_components'])) }}</td>
                            <td>Rp {{ $money($row['actual_net_income']) }}</td>
                            <td><span class="badge text-bg-{{ $row['income_status'] === 'released' ? 'success' : ($row['income_status'] === 'to_release' ? 'warning' : 'secondary') }}">{{ str_replace('_', ' ', ucfirst($row['income_status'] ?? 'unknown')) }}</span></td>
                            <td><span class="badge text-bg-{{ $journalCount === 3 ? 'success' : ($journalCount > 0 ? 'warning' : 'secondary') }}">{{ $journalCount }}/3 posted</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">Belum ada transaksi finance.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows->hasPages())
            <div class="card-footer bg-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="text-muted small">
                    Menampilkan {{ number_format($rows->firstItem() ?? 0) }}–{{ number_format($rows->lastItem() ?? 0) }} dari {{ number_format($rows->total()) }} transaksi
                </span>
                {{ $rows->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
@endsection
