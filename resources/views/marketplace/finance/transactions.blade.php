@extends('marketplace.finance.layout')

@section('finance-content')
    @php $money = fn ($value): string => number_format((float) $value, 2, ',', '.'); @endphp
    <div class="finance-card card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Transactions</h2>
            <span class="text-muted small">{{ count($rows) }} transaksi</span>
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
    </div>
@endsection
