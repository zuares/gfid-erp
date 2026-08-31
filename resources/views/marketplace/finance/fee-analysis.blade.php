@extends('marketplace.finance.layout')

@section('finance-content')
    @php $money = fn ($value): string => number_format((float) $value, 2, ',', '.'); @endphp
    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <div class="finance-card card shadow-sm h-100">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Fee by type</h2></div>
                <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Type</th><th>Count</th><th>Amount</th></tr></thead><tbody>
                    @forelse ($byType as $row)<tr><td>{{ $row['label'] }}</td><td>{{ $row['count'] }}</td><td>Rp {{ $money($row['amount']) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted py-4">Belum ada fee.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="finance-card card shadow-sm h-100">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Fee by store</h2></div>
                <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Toko</th><th>Count</th><th>Amount</th></tr></thead><tbody>
                    @forelse ($byStore as $row)<tr><td>{{ $row['store_name'] }}</td><td>{{ $row['count'] }}</td><td>Rp {{ $money($row['amount']) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted py-4">Belum ada fee.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="finance-card card shadow-sm h-100">
                <div class="card-header bg-white"><h2 class="h6 mb-0">Fee by order</h2></div>
                <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Order</th><th>Fee %</th><th>Fee</th></tr></thead><tbody>
                    @forelse ($byOrder as $row)<tr><td><span class="fw-semibold">{{ $row['order_sn'] }}</span><br><small class="text-muted">{{ $row['store_name'] }}</small></td><td>{{ $row['fee_percentage'] === null ? '-' : number_format($row['fee_percentage'], 2, ',', '.') . '%' }}</td><td>Rp {{ $money($row['fee_amount']) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted py-4">Belum ada fee.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
    </div>
@endsection
