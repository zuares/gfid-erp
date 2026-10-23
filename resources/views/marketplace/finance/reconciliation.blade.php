@extends('marketplace.finance.layout')

@section('finance-content')
    @php
        $money = fn ($value): string => number_format((float) $value, 2, ',', '.');
        $summary = $report['summary'];
        $statusMeta = [
            'matched' => ['label' => 'Sesuai', 'icon' => 'bi-check2-circle', 'tone' => 'success', 'hint' => 'Gross, fee, dan net balance'],
            'mismatch' => ['label' => 'Net berbeda', 'icon' => 'bi-exclamation-octagon', 'tone' => 'danger', 'hint' => 'Nominal perlu diperiksa'],
            'unmatched' => ['label' => 'Belum terhubung', 'icon' => 'bi-link-45deg', 'tone' => 'muted', 'hint' => 'Order atau sumber belum cocok'],
            'pending' => ['label' => 'Menunggu income', 'icon' => 'bi-hourglass-split', 'tone' => 'warning', 'hint' => 'Income belum final'],
        ];
        $reasonLabels = [
            'order_unmatched' => 'Order belum terhubung',
            'missing_escrow' => 'Escrow belum tersedia',
            'missing_income' => 'Income belum tersedia',
            'income_pending' => 'Income belum dirilis',
            'amount_mismatch' => 'Gross berbeda',
            'fee_mismatch' => 'Expected net berbeda',
            'duplicate_response' => 'Response komponen duplikat',
        ];
    @endphp

    <section class="finance-tab-panel finance-reconciliation" aria-label="Rekonsiliasi finance per order">
        <div class="finance-callout mb-4" role="note">
            <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
            <div>
                <strong>Mode rekonsiliasi per order</strong>
                <span>Status hanya membandingkan identitas order, gross escrow, komponen fee/adjustment, dan net income. Payout, alokasi settlement, mutasi bank, serta jurnal tidak memengaruhi hasil halaman ini.</span>
            </div>
            <span class="finance-section-count ms-auto">{{ number_format($summary['transaction_count']) }} order</span>
        </div>

        <div class="row g-3 mb-3">
            @foreach ($statusMeta as $key => $meta)
                <div class="col-6 col-xl-3">
                    <div class="finance-card finance-kpi finance-kpi--{{ $meta['tone'] }} h-100">
                        <div class="card-body position-relative">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                <span class="text-muted small">{{ $meta['label'] }}</span>
                                <span class="finance-kpi-icon"><i class="bi {{ $meta['icon'] }}" aria-hidden="true"></i></span>
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
                        <div class="finance-section-meta">Total selisih expected net vs actual net</div>
                        <div class="h5 mb-0 mt-1">Rp {{ $money($summary['unreconciled_amount']) }}</div>
                        <div class="finance-section-meta mt-1">Hanya order berstatus “Net berbeda”.</div>
                    </div>
                    <div class="col-6 col-md-4 border-start">
                        <div class="finance-section-meta">Data yang diuji</div>
                        <div class="fw-bold mt-1">Gross · Fee · Net</div>
                    </div>
                    <div class="col-6 col-md-4 border-start">
                        <div class="finance-section-meta">Mode</div>
                        <div class="fw-bold mt-1"><i class="bi bi-eye me-1 text-primary" aria-hidden="true"></i>Read-only order</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="finance-callout mb-3" role="note">
            <i class="bi bi-list-check" aria-hidden="true"></i>
            <div>
                <strong>Alasan yang perlu ditindaklanjuti</strong>
                <span>Income belum dirilis: {{ number_format($summary['reason_counts']['income_pending'] ?? 0) }} · Escrow belum tersedia: {{ number_format($summary['reason_counts']['missing_escrow'] ?? 0) }} · Order belum terhubung: {{ number_format($summary['reason_counts']['order_unmatched'] ?? 0) }} · Gross berbeda: {{ number_format($summary['reason_counts']['amount_mismatch'] ?? 0) }} · Expected net berbeda: {{ number_format($summary['reason_counts']['fee_mismatch'] ?? 0) }}. Satu order dapat memiliki lebih dari satu alasan.</span>
            </div>
        </div>

        <div class="finance-card finance-section-card card">
            <div class="card-header bg-white border-0 px-0 pt-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 px-md-4">
                    <div>
                        <h3 class="h6 mb-1">Hasil rekonsiliasi order</h3>
                        <div class="finance-section-meta">Actual net harus sama dengan gross dikurangi total fee dan adjustment.</div>
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
                            <th>Fee / adjustment</th>
                            <th>Expected net</th>
                            <th>Actual net</th>
                            <th>Selisih</th>
                            <th>Alasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['transactions'] as $row)
                            @php
                                $meta = $statusMeta[$row['status']] ?? $statusMeta['pending'];
                                $badgeTone = match ($meta['tone']) {
                                    'success' => 'success',
                                    'danger' => 'danger',
                                    'muted' => 'secondary',
                                    default => 'warning',
                                };
                            @endphp
                            <tr>
                                <td data-label="Order SN" class="fw-semibold text-nowrap"><span class="font-monospace small">{{ $row['order_sn'] }}</span></td>
                                <td data-label="Status"><span class="badge rounded-pill text-bg-{{ $badgeTone }}"><i class="bi {{ $meta['icon'] }} me-1" aria-hidden="true"></i>{{ $meta['label'] }}</span></td>
                                <td data-label="Gross escrow" class="text-nowrap">{{ $row['escrow_gross'] === null ? '-' : 'Rp '.$money($row['escrow_gross']) }}</td>
                                <td data-label="Fee / adjustment" class="text-nowrap">Rp {{ $money($row['total_components']) }}</td>
                                <td data-label="Expected net" class="text-nowrap">{{ $row['expected_net_income'] === null ? '-' : 'Rp '.$money($row['expected_net_income']) }}</td>
                                <td data-label="Actual net" class="text-nowrap">Rp {{ $money($row['actual_net_income']) }}</td>
                                <td data-label="Selisih" class="text-nowrap">{{ $row['net_difference'] === null ? '-' : 'Rp '.$money($row['net_difference']) }}</td>
                                <td data-label="Alasan" style="min-width: 220px;">
                                    @forelse ($row['reasons'] as $reason)
                                        <span class="finance-reason">{{ $reasonLabels[$reason] ?? str_replace('_', ' ', ucfirst($reason)) }}</span>
                                    @empty
                                        <span class="finance-reason finance-reason--neutral">Gross, fee, dan net sesuai</span>
                                    @endforelse
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><div class="finance-empty"><i class="bi bi-search" aria-hidden="true"></i>Tidak ada order pada filter ini. Periksa toko, basis tanggal, dan periode yang dipilih.</div></td></tr>
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
    </section>
@endsection
