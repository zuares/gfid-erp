@extends('marketplace.finance.layout')

@section('finance-content')
    @php $money = fn ($value): string => number_format((float) $value, 2, ',', '.'); @endphp
    <style>
        .fee-analysis-screen .fee-analysis-card { min-width: 0; }
        .fee-analysis-screen .fee-analysis-header { min-height: 76px; }
        .fee-analysis-screen .fee-analysis-header h3 { color: var(--finance-ink); font-size: .86rem; font-weight: 800; letter-spacing: -.01em; }
        .fee-analysis-screen .fee-analysis-header .finance-section-meta { font-size: .7rem; }
        .fee-analysis-screen .fee-analysis-table th:last-child,
        .fee-analysis-screen .fee-analysis-table td:last-child { text-align: right; }
        .fee-analysis-screen .fee-analysis-table td:nth-child(2) { color: var(--finance-muted); font-variant-numeric: tabular-nums; }
        @media (max-width: 767.98px) {
            .fee-analysis-screen .fee-analysis-header { min-height: 0; }
        }
        @supports (container-type: inline-size) {
            @container finance-page (max-width: 767.98px) {
                .fee-analysis-screen .fee-analysis-header { min-height: 0; }
            }
        }
    </style>
    <section class="finance-tab-panel finance-fee-analysis fee-analysis-screen" aria-label="Analisis biaya marketplace">
    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="finance-card finance-section-card fee-analysis-card card h-100">
                <div class="card-header fee-analysis-header bg-white border-0 d-flex align-items-start justify-content-between gap-3">
                    <div><div class="finance-section-meta">Komposisi biaya</div><h3 class="mb-0 mt-1">Fee by type</h3></div>
                    <span class="finance-section-count">{{ number_format($byType->total()) }} tipe</span>
                </div>
                <div class="finance-table-wrap table-responsive"><table class="finance-table fee-analysis-table table mb-0"><thead><tr><th>Type</th><th>Count</th><th>Amount</th></tr></thead><tbody>
                    @forelse ($byType as $row)
                        <tr>
                            <td data-label="Type">
                                {{ $row['label'] }}
                                @if ($row['code'] === 'unmapped_adjustment')
                                    <br><small class="text-muted">Sisa dari gross − net setelah komponen yang dikenali. Bukan otomatis biaya marketplace; perlu ditelusuri.</small>
                                @endif
                            </td>
                            <td data-label="Count">{{ $row['count'] }}</td>
                            <td data-label="Amount">Rp {{ $money($row['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><div class="finance-empty"><i class="bi bi-receipt-cutoff" aria-hidden="true"></i>Belum ada fee.</div></td></tr>
                    @endforelse
                </tbody></table></div>
                @if ($byType->hasPages())<div class="finance-pagination card-footer bg-white"><span class="text-muted small">{{ number_format($byType->total()) }} tipe</span>{{ $byType->onEachSide(1)->links() }}</div>@endif
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="finance-card finance-section-card fee-analysis-card card h-100">
                <div class="card-header fee-analysis-header bg-white border-0 d-flex align-items-start justify-content-between gap-3">
                    <div><div class="finance-section-meta">Performa channel</div><h3 class="mb-0 mt-1">Fee by store</h3></div>
                    <span class="finance-section-count">{{ number_format($byStore->total()) }} toko</span>
                </div>
                <div class="finance-table-wrap table-responsive"><table class="finance-table fee-analysis-table table mb-0"><thead><tr><th>Toko</th><th>Count</th><th>Amount</th></tr></thead><tbody>
                    @forelse ($byStore as $row)
                        <tr><td data-label="Toko">{{ $row['store_name'] }}</td><td data-label="Count">{{ $row['count'] }}</td><td data-label="Amount">Rp {{ $money($row['amount']) }}</td></tr>
                    @empty
                        <tr><td colspan="3"><div class="finance-empty"><i class="bi bi-shop" aria-hidden="true"></i>Belum ada fee.</div></td></tr>
                    @endforelse
                </tbody></table></div>
                @if ($byStore->hasPages())<div class="finance-pagination card-footer bg-white"><span class="text-muted small">{{ number_format($byStore->total()) }} toko</span>{{ $byStore->onEachSide(1)->links() }}</div>@endif
            </div>
        </div>
        <div class="col-12">
            <div class="finance-card finance-section-card fee-analysis-card card">
                <div class="card-header fee-analysis-header bg-white border-0 d-flex align-items-start justify-content-between gap-3">
                    <div><div class="finance-section-meta">Drill-down</div><h3 class="mb-0 mt-1">Fee by order</h3></div>
                    <span class="finance-section-count">{{ number_format($byOrder->total()) }} order</span>
                </div>
                <div class="finance-table-wrap table-responsive"><table class="finance-table fee-analysis-table table mb-0"><thead><tr><th>Order</th><th>Fee %</th><th>Fee</th></tr></thead><tbody>
                    @forelse ($byOrder as $row)
                        <tr><td data-label="Order"><span class="fw-semibold">{{ $row['order_sn'] }}</span><br><small class="text-muted">{{ $row['store_name'] }}</small></td><td data-label="Fee %">{{ $row['fee_percentage'] === null ? '-' : number_format($row['fee_percentage'], 2, ',', '.') . '%' }}</td><td data-label="Fee">Rp {{ $money($row['fee_amount']) }}</td></tr>
                    @empty
                        <tr><td colspan="3"><div class="finance-empty"><i class="bi bi-receipt-cutoff" aria-hidden="true"></i>Belum ada fee.</div></td></tr>
                    @endforelse
                </tbody></table></div>
                @if ($byOrder->hasPages())
                    <div class="finance-pagination card-footer bg-white">
                        <span class="text-muted small">
                            Menampilkan {{ number_format($byOrder->firstItem() ?? 0) }}–{{ number_format($byOrder->lastItem() ?? 0) }} dari {{ number_format($byOrder->total()) }} order
                        </span>
                        {{ $byOrder->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    </section>
@endsection
