@extends('marketplace.finance.layout')

@section('finance-content')
    @php $money = fn ($value): string => number_format((float) $value, 2, ',', '.'); @endphp
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
        <div>
            <div class="finance-eyebrow mb-1">Cost intelligence</div>
            <h2 class="h4 mb-1">Fee Analysis</h2>
            <div class="finance-caption">Bandingkan biaya marketplace berdasarkan tipe, toko, dan order.</div>
        </div>
        <span class="badge rounded-pill text-bg-light border px-3 py-2"><i class="bi bi-lightning-charge me-1 text-warning"></i>Biaya teragregasi</span>
    </div>
    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="finance-card card h-100">
                <div class="card-header bg-white border-0"><div class="finance-section-meta">Komposisi biaya</div><h3 class="h6 mb-0 mt-1">Fee by type</h3></div>
                <div class="finance-table-wrap table-responsive"><table class="finance-table table mb-0"><thead><tr><th>Type</th><th>Count</th><th>Amount</th></tr></thead><tbody>
                    @forelse ($byType as $row)<tr><td data-label="Type">{{ $row['label'] }}</td><td data-label="Count">{{ $row['count'] }}</td><td data-label="Amount">Rp {{ $money($row['amount']) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted py-4">Belum ada fee.</td></tr>@endforelse
                </tbody></table></div>
                @if ($byType->hasPages())<div class="card-footer bg-white d-flex flex-wrap align-items-center justify-content-between gap-2"><span class="text-muted small">{{ number_format($byType->total()) }} tipe</span>{{ $byType->onEachSide(1)->links() }}</div>@endif
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="finance-card card h-100">
                <div class="card-header bg-white border-0"><div class="finance-section-meta">Performa channel</div><h3 class="h6 mb-0 mt-1">Fee by store</h3></div>
                <div class="finance-table-wrap table-responsive"><table class="finance-table table mb-0"><thead><tr><th>Toko</th><th>Count</th><th>Amount</th></tr></thead><tbody>
                    @forelse ($byStore as $row)<tr><td data-label="Toko">{{ $row['store_name'] }}</td><td data-label="Count">{{ $row['count'] }}</td><td data-label="Amount">Rp {{ $money($row['amount']) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted py-4">Belum ada fee.</td></tr>@endforelse
                </tbody></table></div>
                @if ($byStore->hasPages())<div class="card-footer bg-white d-flex flex-wrap align-items-center justify-content-between gap-2"><span class="text-muted small">{{ number_format($byStore->total()) }} toko</span>{{ $byStore->onEachSide(1)->links() }}</div>@endif
            </div>
        </div>
        <div class="col-12">
            <div class="finance-card card">
                <div class="card-header bg-white border-0"><div class="finance-section-meta">Drill-down</div><h3 class="h6 mb-0 mt-1">Fee by order</h3></div>
                <div class="finance-table-wrap table-responsive"><table class="finance-table table mb-0"><thead><tr><th>Order</th><th>Fee %</th><th>Fee</th></tr></thead><tbody>
                    @forelse ($byOrder as $row)<tr><td data-label="Order"><span class="fw-semibold">{{ $row['order_sn'] }}</span><br><small class="text-muted">{{ $row['store_name'] }}</small></td><td data-label="Fee %">{{ $row['fee_percentage'] === null ? '-' : number_format($row['fee_percentage'], 2, ',', '.') . '%' }}</td><td data-label="Fee">Rp {{ $money($row['fee_amount']) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted py-4">Belum ada fee.</td></tr>@endforelse
                </tbody></table></div>
                @if ($byOrder->hasPages())
                    <div class="card-footer bg-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <span class="text-muted small">
                            Menampilkan {{ number_format($byOrder->firstItem() ?? 0) }}–{{ number_format($byOrder->lastItem() ?? 0) }} dari {{ number_format($byOrder->total()) }} order
                        </span>
                        {{ $byOrder->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
