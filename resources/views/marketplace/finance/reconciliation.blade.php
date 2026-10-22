@extends('marketplace.finance.layout')

@section('finance-content')
    @php
        $money = fn ($value): string => number_format((float) $value, 2, ',', '.');
        $summary = $report['summary'];
        $statusMeta = [
            'matched' => ['label' => 'Matched', 'icon' => 'bi-check2-circle', 'tone' => 'success', 'hint' => 'Tidak ada selisih'],
            'mismatch' => ['label' => 'Perlu ditinjau', 'icon' => 'bi-exclamation-octagon', 'tone' => 'danger', 'hint' => 'Nominal atau jurnal'],
            'unmatched' => ['label' => 'Unmatched', 'icon' => 'bi-link-45deg', 'tone' => 'muted', 'hint' => 'Belum terhubung'],
            'pending' => ['label' => 'Pending', 'icon' => 'bi-hourglass-split', 'tone' => 'warning', 'hint' => 'Menunggu data'],
        ];
        $reasonLabels = [
            'order_unmatched' => 'Order belum cocok',
            'missing_escrow' => 'Escrow belum tersedia',
            'missing_income' => 'Income belum tersedia',
            'missing_settlement' => 'Settlement belum dialokasikan',
            'bank_unverified' => 'Dana masuk bank belum terverifikasi',
            'journal_missing' => 'Jurnal belum dibuat',
            'journal_duplicate' => 'Jurnal ganda',
            'amount_mismatch' => 'Nominal berbeda',
            'fee_mismatch' => 'Fee belum balance',
            'duplicate_response' => 'Response duplikat',
        ];
    @endphp

    <section class="finance-tab-panel finance-reconciliation" aria-label="Rekonsiliasi transaksi marketplace">

    <div class="finance-callout mb-4">
        <i class="bi bi-info-circle-fill"></i>
        <div>
            <strong>Aturan rekonsiliasi saat ini</strong>
            <span>Invoice dan shipment bersifat opsional untuk finance. HPP mengikuti jurnal shipment. Status perlu ditinjau dapat disebabkan jurnal belum ada, bukan selalu selisih nominal atau bank.</span>
        </div>
        <span class="finance-section-count ms-auto">{{ number_format($summary['transaction_count']) }} transaksi</span>
    </div>

    <div class="row g-3 mb-3">
        @foreach ($statusMeta as $key => $meta)
            <div class="col-6 col-xl-3">
                <div class="finance-card finance-kpi finance-kpi--{{ $meta['tone'] }} h-100">
                    <div class="card-body position-relative">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                            <span class="text-muted small">{{ $meta['label'] }}</span>
                            <span class="finance-kpi-icon"><i class="bi {{ $meta['icon'] }}"></i></span>
                        </div>
                        <div class="finance-number">{{ number_format($summary[$key]) }}</div>
                        <div class="finance-caption mt-1">{{ $meta['hint'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="finance-card finance-section-card card mb-3">
        <div class="card-body py-2">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="finance-section-meta">Selisih net terhadap alokasi order belum matched</div>
                    <div class="h5 mb-0 mt-1">Rp {{ $money($summary['unreconciled_amount']) }}</div>
                    <div class="finance-section-meta mt-1">Bukan selisih mutasi bank yang sudah terverifikasi.</div>
                </div>
                <div class="col-6 col-md-4 border-start">
                    <div class="finance-section-meta">Settlement terfilter</div>
                    <div class="fw-bold mt-1">{{ number_format($summary['settlement_count']) }}</div>
                </div>
                <div class="col-6 col-md-4 border-start">
                    <div class="finance-section-meta">Mode</div>
                    <div class="fw-bold mt-1"><i class="bi bi-eye me-1 text-primary"></i>Read-only</div>
                </div>
            </div>
        </div>
    </div>

    <div class="finance-callout mb-3" role="note">
        <i class="bi bi-list-check" aria-hidden="true"></i>
        <div>
            <strong>Alasan yang perlu ditindaklanjuti</strong>
            <span>Settlement belum dialokasikan: {{ number_format($summary['reason_counts']['missing_settlement'] ?? 0) }} order · Dana masuk bank belum terverifikasi: {{ number_format($summary['reason_counts']['bank_unverified'] ?? 0) }} order · Jurnal belum dibuat: {{ number_format($summary['reason_counts']['journal_missing'] ?? 0) }} order · Nominal berbeda: {{ number_format($summary['reason_counts']['amount_mismatch'] ?? 0) }} order · Fee berbeda: {{ number_format($summary['reason_counts']['fee_mismatch'] ?? 0) }} order. Satu order dapat memiliki lebih dari satu alasan.</span>
        </div>
    </div>

    <div class="finance-card finance-section-card card mb-3">
        <div class="card-header bg-white border-0 px-0 pt-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 px-md-4">
                <div>
                    <h3 class="h6 mb-1">Detail transaksi</h3>
                    <div class="finance-section-meta">Status dan alasan ditampilkan per order.</div>
                </div>
                <span class="badge rounded-pill text-bg-light border">{{ number_format($report['transactions']->total()) }} order</span>
            </div>
        </div>
        <div class="finance-table-wrap table-responsive">
            <table class="finance-table table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Order SN</th>
                        <th>Status</th>
                        <th>Gross escrow</th>
                        <th>Expected net</th>
                        <th>Allocated</th>
                        <th>Jurnal</th>
                        <th>Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['transactions'] as $row)
                        @php
                            $journalCount = collect($row['journal_amount'])->filter(fn ($value) => $value !== null)->count();
                            $meta = $statusMeta[$row['status']] ?? $statusMeta['pending'];
                        @endphp
                        <tr>
                            <td data-label="Order SN" class="fw-semibold text-nowrap"><span class="font-monospace small">{{ $row['order_sn'] }}</span></td>
                            <td data-label="Status">
                                <span class="badge rounded-pill text-bg-{{ $meta['tone'] === 'success' ? 'success' : ($meta['tone'] === 'danger' ? 'danger' : 'warning') }}">
                                    <i class="bi {{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}
                                </span>
                            </td>
                            <td data-label="Gross escrow" class="text-nowrap">Rp {{ $money($row['escrow_gross']) }}</td>
                            <td data-label="Expected net" class="text-nowrap">{{ $row['expected_net_income'] === null ? '-' : 'Rp '.$money($row['expected_net_income']) }}</td>
                            <td data-label="Allocated" class="text-nowrap">Rp {{ $money($row['allocated_amount']) }}</td>
                            <td data-label="Jurnal"><span class="finance-journal-count"><i class="bi bi-journal-text" aria-hidden="true"></i>{{ $journalCount }} terkait</span></td>
                            <td data-label="Alasan" style="min-width: 240px;">
                                @forelse ($row['reasons'] as $reason)
                                    <span class="finance-reason">{{ $reasonLabels[$reason] ?? str_replace('_', ' ', ucfirst($reason)) }}</span>
                                @empty
                                    <span class="finance-reason finance-reason--neutral">Tidak ada selisih</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="finance-empty"><i class="bi bi-search"></i>Tidak ada transaksi pada filter ini. Periksa basis tanggal dan periode, lalu reset filter bila perlu.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($report['transactions']->hasPages())
            <div class="card-footer bg-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="text-muted small">Menampilkan {{ number_format($report['transactions']->firstItem() ?? 0) }}–{{ number_format($report['transactions']->lastItem() ?? 0) }} dari {{ number_format($report['transactions']->total()) }} order</span>
                {{ $report['transactions']->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

    <div class="finance-card finance-section-card card">
        <div class="card-header bg-white border-0 px-0 pt-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 px-md-4">
                <div>
                    <h3 class="h6 mb-1">Detail settlement</h3>
                    <div class="finance-section-meta">Payout tersinkron, termasuk yang belum memiliki alokasi ke order.</div>
                </div>
                <span class="badge rounded-pill text-bg-light border">{{ number_format($report['settlements']->total()) }} settlement</span>
            </div>
        </div>
        @if (count($report['settlements']) > 0)
            <div class="finance-table-wrap table-responsive">
                <table class="finance-table table table-hover mb-0">
                    <thead>
                        <tr><th>Settlement ID</th><th>Status</th><th>Amount</th><th>Allocated</th><th>Unallocated</th><th>Alasan</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($report['settlements'] as $row)
                            <tr>
                                <td data-label="Settlement ID" class="fw-semibold text-nowrap font-monospace small">{{ $row['external_settlement_id'] }}</td>
                                <td data-label="Status"><span class="badge rounded-pill text-bg-{{ $row['status'] === 'matched' ? 'success' : ($row['status'] === 'mismatch' ? 'danger' : 'warning') }}">{{ ucfirst($row['status']) }}</span></td>
                                <td data-label="Amount" class="text-nowrap">Rp {{ $money($row['settlement_amount']) }}</td>
                                <td data-label="Allocated" class="text-nowrap">Rp {{ $money($row['allocated_amount']) }}</td>
                                <td data-label="Unallocated" class="text-nowrap">Rp {{ $money($row['unallocated_amount']) }}</td>
                                <td data-label="Alasan" class="small">{{ $row['reasons'] ? implode(', ', array_map(fn (string $reason): string => $reasonLabels[$reason] ?? str_replace('_', ' ', ucfirst($reason)), $row['reasons'])) : 'Tidak ada' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($report['settlements']->hasPages())
                <div class="card-footer bg-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span class="text-muted small">Menampilkan {{ number_format($report['settlements']->firstItem() ?? 0) }}–{{ number_format($report['settlements']->lastItem() ?? 0) }} dari {{ number_format($report['settlements']->total()) }} settlement</span>
                    {{ $report['settlements']->onEachSide(1)->links() }}
                </div>
            @endif
        @else
            <div class="finance-empty">
                <i class="bi bi-bank"></i>
                Belum ada payout Finance pada filter ini. Periksa sumber payout marketplace sebelum menyimpulkan ada selisih bank.
            </div>
        @endif
    </div>
    </section>
@endsection
