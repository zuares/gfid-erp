@extends('marketplace.finance.layout')

@section('finance-sidebar')
    @include('marketplace.finance._settlement-sidebar')
@endsection

@section('finance-content')
    @php $money = fn ($value): string => number_format((float) $value, 2, ',', '.'); @endphp
    <section class="finance-tab-panel finance-settlements" aria-label="Riwayat settlement marketplace">
    <div class="finance-callout" role="note">
        <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
        <div>
            <strong>Payout tidak sama dengan escrow per order</strong>
            <span>Halaman ini menampilkan payout marketplace yang tersinkron. “Ditarik dari wallet” berarti saldo Shopee berkurang; “Payout selesai” juga belum membuktikan uang masuk bank. Cocokkan keduanya dengan mutasi rekening sebelum mencatat jurnal bank.</span>
        </div>
    </div>
    <div class="finance-card finance-section-card card">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center gap-3">
            <div>
                <div class="finance-section-meta">Settlement terfilter</div>
                <h3 class="h6 mb-0 mt-1">Daftar payout marketplace</h3>
            </div>
            <span class="finance-section-count">{{ number_format($rows->total()) }} settlement</span>
        </div>
        <div class="finance-table-wrap table-responsive">
            <table class="finance-table table table-hover mb-0">
                <thead><tr><th>Settlement ID</th><th>Tanggal</th><th>Toko</th><th>Status payout</th><th>Jumlah</th><th>Allocated</th><th>Unallocated</th><th>Bank tujuan</th><th>Jurnal</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $settlement = $settlementModels[$row['settlement_id']] ?? null;
                            if ($settlement?->isWalletWithdrawal() && in_array($row['settlement_status'], ['withdrawn', 'received'], true)) {
                                $payoutStatus = ['Ditarik dari wallet', 'warning'];
                            } elseif ($settlement?->isProviderPayout() && in_array($row['settlement_status'], ['payout_completed', 'received'], true)) {
                                $payoutStatus = ['Payout selesai · bank belum dicek', 'warning'];
                            } else {
                                $payoutStatus = match ($row['settlement_status']) {
                                    'received' => ['Diterima', 'success'],
                                    'withdrawn' => ['Ditarik dari wallet', 'warning'],
                                    'payout_completed' => ['Payout selesai · bank belum dicek', 'warning'],
                                    'void' => ['Dibatalkan', 'secondary'],
                                    'pending' => ['Menunggu', 'warning'],
                                    default => ['Belum diketahui', 'secondary'],
                                };
                            }
                        @endphp
                        <tr>
                            <td data-label="Settlement ID" class="fw-semibold">{{ $row['external_settlement_id'] }}</td>
                            <td data-label="Tanggal">{{ $settlement?->settlement_date?->format('d M Y') ?? '-' }}</td>
                            <td data-label="Toko">{{ $settlement?->store?->name ?? 'Toko tidak diketahui' }}</td>
                            <td data-label="Status payout"><span class="badge text-bg-{{ $payoutStatus[1] }}">{{ $payoutStatus[0] }}</span></td>
                            <td data-label="Jumlah">Rp {{ $money($row['settlement_amount']) }}</td>
                            <td data-label="Allocated">Rp {{ $money($row['allocated_amount']) }}</td>
                            <td data-label="Unallocated">Rp {{ $money($row['unallocated_amount']) }}</td>
                            <td data-label="Bank tujuan">{{ $settlement?->bankAccount?->name ?? 'Belum ditentukan' }}</td>
                            <td data-label="Jurnal"><span class="badge text-bg-{{ $row['journal_amount'] !== null ? 'success' : 'secondary' }}">{{ $row['journal_amount'] !== null ? 'Tercatat' : 'Belum tercatat' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><div class="finance-empty"><i class="bi bi-bank"></i>Belum ada payout finance yang tersinkron untuk toko dan periode ini. Periksa sumber payout, koneksi toko, dan pilihan tanggal settlement.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows->hasPages())
            <div class="finance-pagination card-footer bg-white">
                <span class="text-muted small">Menampilkan {{ number_format($rows->firstItem() ?? 0) }}–{{ number_format($rows->lastItem() ?? 0) }} dari {{ number_format($rows->total()) }} settlement</span>
                {{ $rows->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
    </section>
@endsection
