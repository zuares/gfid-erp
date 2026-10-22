@extends('marketplace.finance.layout')

@section('finance-content')
    @php $money = fn ($value): string => number_format((float) $value, 2, ',', '.'); @endphp
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
        <div>
            <div class="finance-eyebrow mb-1">Payout marketplace</div>
            <h2 class="h4 mb-1">Settlements</h2>
            <div class="finance-caption">Daftar payout yang masuk dari marketplace dan status posting jurnalnya.</div>
        </div>
        <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ number_format($rows->total()) }} settlement</span>
    </div>
    <div class="finance-card card">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <div>
                <div class="finance-section-meta">Settlement terfilter</div>
                <h3 class="h6 mb-0 mt-1">Riwayat penerimaan dana</h3>
            </div>
        </div>
        <div class="finance-table-wrap table-responsive">
            <table class="finance-table table table-hover mb-0">
                <thead><tr><th>Settlement ID</th><th>Tanggal</th><th>Toko</th><th>Jumlah</th><th>Allocated</th><th>Unallocated</th><th>Bank</th><th>Journal</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php $settlement = $settlementModels[$row['settlement_id']] ?? null; @endphp
                        <tr>
                            <td data-label="Settlement ID" class="fw-semibold">{{ $row['external_settlement_id'] }}</td>
                            <td data-label="Tanggal">{{ $settlement?->settlement_date?->format('d M Y') ?? '-' }}</td>
                            <td data-label="Toko">{{ $settlement?->store?->name ?? 'Toko tidak diketahui' }}</td>
                            <td data-label="Jumlah">Rp {{ $money($row['settlement_amount']) }}</td>
                            <td data-label="Allocated">Rp {{ $money($row['allocated_amount']) }}</td>
                            <td data-label="Unallocated">Rp {{ $money($row['unallocated_amount']) }}</td>
                            <td data-label="Bank">{{ $settlement?->bankAccount?->name ?? 'Belum ditentukan' }}</td>
                            <td data-label="Journal"><span class="badge text-bg-{{ $row['journal_amount'] !== null ? 'success' : 'secondary' }}">{{ $row['journal_amount'] !== null ? 'Posted' : 'Belum posted' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="finance-empty"><i class="bi bi-bank"></i>Belum ada settlement finance.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows->hasPages())
            <div class="card-footer bg-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="text-muted small">Menampilkan {{ number_format($rows->firstItem() ?? 0) }}–{{ number_format($rows->lastItem() ?? 0) }} dari {{ number_format($rows->total()) }} settlement</span>
                {{ $rows->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
@endsection
