@extends('marketplace.finance.layout')

@section('finance-content')
    @php $money = fn ($value): string => number_format((float) $value, 2, ',', '.'); @endphp
    <div class="finance-card card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Settlements</h2>
            <span class="text-muted small">{{ count($rows) }} settlement</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Settlement ID</th><th>Tanggal</th><th>Toko</th><th>Jumlah</th><th>Allocated</th><th>Unallocated</th><th>Bank</th><th>Journal</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php $settlement = $settlementModels[$row['settlement_id']] ?? null; @endphp
                        <tr>
                            <td class="fw-semibold">{{ $row['external_settlement_id'] }}</td>
                            <td>{{ $settlement?->settlement_date?->format('d M Y') ?? '-' }}</td>
                            <td>{{ $settlement?->store?->name ?? 'Toko tidak diketahui' }}</td>
                            <td>Rp {{ $money($row['settlement_amount']) }}</td>
                            <td>Rp {{ $money($row['allocated_amount']) }}</td>
                            <td>Rp {{ $money($row['unallocated_amount']) }}</td>
                            <td>{{ $settlement?->bankAccount?->name ?? 'Belum ditentukan' }}</td>
                            <td><span class="badge text-bg-{{ $row['journal_amount'] !== null ? 'success' : 'secondary' }}">{{ $row['journal_amount'] !== null ? 'Posted' : 'Belum posted' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">Belum ada settlement finance.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
