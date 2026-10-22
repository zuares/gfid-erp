@extends('marketplace.finance.layout')

@section('finance-content')
    @php
        $money = fn ($value): string => number_format((float) $value, 2, ',', '.');
        $cards = [
            ['label' => 'Gross Sales', 'value' => $overview['gross_sales'], 'icon' => 'bi-cart-check'],
            ['label' => 'Total Escrow Fee', 'value' => $overview['total_escrow_fee'], 'icon' => 'bi-percent'],
            ['label' => 'Pending Income', 'value' => $overview['pending_income'], 'icon' => 'bi-hourglass-split'],
            ['label' => 'To Release', 'value' => $overview['to_release'], 'icon' => 'bi-clock-history'],
            ['label' => 'Released', 'value' => $overview['released'], 'icon' => 'bi-check2-circle'],
            ['label' => 'Settlement Received', 'value' => $overview['settlement_received'], 'icon' => 'bi-bank'],
            ['label' => 'Unreconciled Amount', 'value' => $overview['unreconciled_amount'], 'icon' => 'bi-exclamation-triangle'],
        ];
    @endphp

    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
        <div>
            <div class="finance-eyebrow mb-1">Financial overview</div>
            <h2 class="h4 mb-1">Ringkasan performa finance</h2>
            <div class="finance-caption">KPI dihitung dari transaksi dan settlement yang sesuai filter analitik.</div>
        </div>
        <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ number_format($report['summary']['transaction_count']) }} transaksi terfilter</span>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($cards as $card)
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="finance-card card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="text-muted small">{{ $card['label'] }}</span>
                            <i class="bi {{ $card['icon'] }} text-primary"></i>
                        </div>
                        <div class="finance-number">Rp {{ $money($card['value']) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-5">
            <div class="finance-card card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Status rekonsiliasi</h2>
                    <div class="row g-2 text-center mt-2">
                        @foreach (['matched' => 'Matched', 'mismatch' => 'Mismatch', 'unmatched' => 'Unmatched', 'pending' => 'Pending'] as $key => $label)
                            <div class="col-6">
                                <div class="border rounded p-3">
                                    <div class="h4 mb-1">{{ $report['summary'][$key] }}</div>
                                    <div class="text-muted small">{{ $label }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-7">
            <div class="finance-card card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Akses cepat</h2>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a class="btn btn-outline-primary" href="{{ route('marketplace.finance.transactions', request()->query()) }}">Lihat transactions</a>
                        <a class="btn btn-outline-primary" href="{{ route('marketplace.finance.settlements', request()->query()) }}">Lihat settlements</a>
                        <a class="btn btn-outline-primary" href="{{ route('marketplace.finance.reconciliation', request()->query()) }}">Buka reconciliation</a>
                        <a class="btn btn-outline-primary" href="{{ route('marketplace.finance.fee-analysis', request()->query()) }}">Analisa fee</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-7">
            <div class="finance-card card h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <div><div class="finance-section-meta">Aktivitas terbaru</div><h2 class="h6 mb-0 mt-1">Transactions</h2></div>
                    <a class="btn btn-sm btn-light border" href="{{ route('marketplace.finance.transactions', request()->query()) }}">Lihat semua</a>
                </div>
                <div class="finance-table-wrap table-responsive">
                    <table class="finance-table table table-hover mb-0">
                        <thead><tr><th>Order</th><th>Gross</th><th>Net</th><th>Income</th></tr></thead>
                        <tbody>
                            @forelse ($transactions as $row)
                                <tr>
                                    <td data-label="Order"><span class="fw-semibold font-monospace small">{{ $row['order_sn'] }}</span><br><small class="text-muted">{{ $storeNames[$row['store_id']] ?? 'Toko tidak diketahui' }}</small></td>
                                    <td data-label="Gross" class="text-nowrap">Rp {{ $money($row['gross_sales_invoice'] ?? $row['escrow_gross'] ?? 0) }}</td>
                                    <td data-label="Net" class="text-nowrap">Rp {{ $money($row['actual_net_income']) }}</td>
                                    <td data-label="Income"><span class="badge text-bg-{{ $row['income_status'] === 'released' ? 'success' : ($row['income_status'] === 'to_release' ? 'warning' : 'secondary') }}">{{ str_replace('_', ' ', ucfirst($row['income_status'] ?? 'unknown')) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><div class="finance-empty"><i class="bi bi-receipt"></i>Belum ada transaksi finance.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($transactions->hasPages())
                    <div class="card-footer bg-white d-flex flex-wrap align-items-center justify-content-between gap-2"><span class="text-muted small">{{ number_format($transactions->firstItem() ?? 0) }}–{{ number_format($transactions->lastItem() ?? 0) }} dari {{ number_format($transactions->total()) }}</span>{{ $transactions->onEachSide(1)->links() }}</div>
                @endif
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="finance-card card h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <div><div class="finance-section-meta">Payout terbaru</div><h2 class="h6 mb-0 mt-1">Settlements</h2></div>
                    <a class="btn btn-sm btn-light border" href="{{ route('marketplace.finance.settlements', request()->query()) }}">Lihat semua</a>
                </div>
                <div class="finance-table-wrap table-responsive">
                    <table class="finance-table table table-hover mb-0">
                        <thead><tr><th>Settlement</th><th>Amount</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($settlements as $row)
                                @php $settlement = $settlementModels[$row['settlement_id']] ?? null; @endphp
                                <tr>
                                    <td data-label="Settlement"><span class="fw-semibold font-monospace small">{{ $row['external_settlement_id'] }}</span><br><small class="text-muted">{{ $settlement?->settlement_date?->format('d M Y') ?? '-' }}</small></td>
                                    <td data-label="Amount" class="text-nowrap">Rp {{ $money($row['settlement_amount']) }}</td>
                                    <td data-label="Status"><span class="badge text-bg-{{ $row['status'] === 'matched' ? 'success' : ($row['status'] === 'mismatch' ? 'danger' : 'warning') }}">{{ ucfirst($row['status']) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><div class="finance-empty"><i class="bi bi-bank"></i>Belum ada settlement.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($settlements->hasPages())
                    <div class="card-footer bg-white d-flex flex-wrap align-items-center justify-content-between gap-2"><span class="text-muted small">{{ number_format($settlements->firstItem() ?? 0) }}–{{ number_format($settlements->lastItem() ?? 0) }} dari {{ number_format($settlements->total()) }}</span>{{ $settlements->onEachSide(1)->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
