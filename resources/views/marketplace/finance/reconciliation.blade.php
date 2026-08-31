@extends('marketplace.finance.layout')

@section('finance-content')
    <div class="row g-3 mb-4">
        @foreach (['matched' => 'Matched', 'mismatch' => 'Mismatch', 'unmatched' => 'Unmatched', 'pending' => 'Pending'] as $key => $label)
            <div class="col-6 col-xl-3"><div class="finance-card card shadow-sm"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="finance-number">{{ $report['summary'][$key] }}</div></div></div></div>
        @endforeach
    </div>

    <div class="finance-card card shadow-sm mb-4">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Detail per transaksi</h2></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Order SN</th><th>Status</th><th>Gross</th><th>Expected Net</th><th>Allocated</th><th>Journal</th><th>Perbedaan</th></tr></thead>
                <tbody>
                    @forelse ($report['transactions'] as $row)
                        @php $journalCount = collect($row['journal_amount'])->filter(fn ($value) => $value !== null)->count(); @endphp
                        <tr>
                            <td class="fw-semibold">{{ $row['order_sn'] }}</td>
                            <td><span class="badge text-bg-{{ $row['status'] === 'matched' ? 'success' : ($row['status'] === 'mismatch' ? 'danger' : 'warning') }}">{{ ucfirst($row['status']) }}</span></td>
                            <td>{{ number_format((float) ($row['escrow_gross'] ?? 0), 2, ',', '.') }}</td>
                            <td>{{ $row['expected_net_income'] === null ? '-' : number_format($row['expected_net_income'], 2, ',', '.') }}</td>
                            <td>{{ number_format($row['allocated_amount'], 2, ',', '.') }}</td>
                            <td>{{ $journalCount }}/3</td>
                            <td class="small">{{ $row['reasons'] ? implode(', ', $row['reasons']) : 'Tidak ada' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">Tidak ada transaksi pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="finance-card card shadow-sm">
        <div class="card-header bg-white"><h2 class="h6 mb-0">Detail settlement</h2></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Settlement ID</th><th>Status</th><th>Amount</th><th>Allocated</th><th>Unallocated</th><th>Perbedaan</th></tr></thead>
                <tbody>
                    @forelse ($report['settlements'] as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['external_settlement_id'] }}</td>
                            <td><span class="badge text-bg-{{ $row['status'] === 'matched' ? 'success' : ($row['status'] === 'mismatch' ? 'danger' : 'warning') }}">{{ ucfirst($row['status']) }}</span></td>
                            <td>{{ number_format($row['settlement_amount'], 2, ',', '.') }}</td>
                            <td>{{ number_format($row['allocated_amount'], 2, ',', '.') }}</td>
                            <td>{{ number_format($row['unallocated_amount'], 2, ',', '.') }}</td>
                            <td class="small">{{ $row['reasons'] ? implode(', ', $row['reasons']) : 'Tidak ada' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">Tidak ada settlement pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
