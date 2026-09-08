@extends('marketplace.finance.layout')

@section('finance-content')
    @php
        $money = fn ($value): string => number_format((float) $value, 2, ',', '.');
        $summary = $report['summary'];
        $statusMeta = [
            'matched' => ['label' => 'Matched', 'icon' => 'bi-check2-circle', 'tone' => 'success', 'hint' => 'Tidak ada selisih'],
            'mismatch' => ['label' => 'Mismatch', 'icon' => 'bi-exclamation-octagon', 'tone' => 'danger', 'hint' => 'Perlu tindakan'],
            'unmatched' => ['label' => 'Unmatched', 'icon' => 'bi-link-45deg', 'tone' => 'muted', 'hint' => 'Belum terhubung'],
            'pending' => ['label' => 'Pending', 'icon' => 'bi-hourglass-split', 'tone' => 'warning', 'hint' => 'Menunggu data'],
        ];
        $reasonLabels = [
            'order_unmatched' => 'Order belum cocok',
            'missing_escrow' => 'Escrow belum tersedia',
            'missing_income' => 'Income belum tersedia',
            'missing_settlement' => 'Settlement belum dialokasikan',
            'journal_missing' => 'Jurnal belum dibuat',
            'journal_duplicate' => 'Jurnal ganda',
            'amount_mismatch' => 'Nominal berbeda',
            'fee_mismatch' => 'Fee belum balance',
            'duplicate_response' => 'Response duplikat',
        ];
    @endphp

    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
        <div>
            <div class="text-uppercase text-primary fw-semibold small mb-1" style="letter-spacing:.08em;">Audit finance</div>
            <h2 class="h4 mb-1">Rekonsiliasi transaksi</h2>
            <div class="finance-caption">Periksa status order, escrow, fee, settlement, dan jurnal dalam satu tampilan.</div>
        </div>
        <div class="text-md-end">
            <div class="finance-section-meta">Transaksi terfilter</div>
            <div class="h5 mb-0">{{ number_format($summary['transaction_count']) }}</div>
        </div>
    </div>

    <div class="finance-callout mb-4">
        <i class="bi bi-info-circle-fill"></i>
        <div>
            <strong>Aturan rekonsiliasi saat ini</strong>
            <span>Invoice dan shipment bersifat opsional untuk finance. HPP tetap mengikuti jurnal shipment; halaman ini fokus pada transaksi marketplace dan settlement.</span>
        </div>
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

    <div class="finance-card card mb-3">
        <div class="card-body py-2">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="finance-section-meta">Nilai belum direkonsiliasi</div>
                    <div class="h5 mb-0 mt-1">Rp {{ $money($summary['unreconciled_amount']) }}</div>
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

    <div class="finance-card card mb-3">
        <div class="card-header bg-white border-0 px-0 pt-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 px-md-4">
                <div>
                    <h3 class="h6 mb-1">Detail transaksi</h3>
                    <div class="finance-section-meta">Status dan alasan ditampilkan per order.</div>
                </div>
                <span class="badge rounded-pill text-bg-light border">{{ number_format(count($report['transactions'])) }} order</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
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
                            <td class="fw-semibold text-nowrap"><span class="font-monospace small">{{ $row['order_sn'] }}</span></td>
                            <td>
                                <span class="badge rounded-pill text-bg-{{ $meta['tone'] === 'success' ? 'success' : ($meta['tone'] === 'danger' ? 'danger' : 'warning') }}">
                                    <i class="bi {{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}
                                </span>
                            </td>
                            <td class="text-nowrap">Rp {{ $money($row['escrow_gross']) }}</td>
                            <td class="text-nowrap">{{ $row['expected_net_income'] === null ? '-' : 'Rp '.$money($row['expected_net_income']) }}</td>
                            <td class="text-nowrap">Rp {{ $money($row['allocated_amount']) }}</td>
                            <td><span class="finance-journal-count"><i class="bi bi-journal-text"></i>{{ $journalCount }}/3</span></td>
                            <td style="min-width: 240px;">
                                @forelse ($row['reasons'] as $reason)
                                    <span class="finance-reason">{{ $reasonLabels[$reason] ?? str_replace('_', ' ', ucfirst($reason)) }}</span>
                                @empty
                                    <span class="finance-reason finance-reason--neutral">Tidak ada selisih</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="finance-empty"><i class="bi bi-search"></i>Tidak ada transaksi pada filter ini.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="finance-card card">
        <div class="card-header bg-white border-0 px-0 pt-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 px-md-4">
                <div>
                    <h3 class="h6 mb-1">Detail settlement</h3>
                    <div class="finance-section-meta">Payout yang sudah memiliki allocation ke order.</div>
                </div>
                <span class="badge rounded-pill text-bg-light border">{{ number_format(count($report['settlements'])) }} settlement</span>
            </div>
        </div>
        @if (count($report['settlements']) > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Settlement ID</th><th>Status</th><th>Amount</th><th>Allocated</th><th>Unallocated</th><th>Alasan</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($report['settlements'] as $row)
                            <tr>
                                <td class="fw-semibold text-nowrap font-monospace small">{{ $row['external_settlement_id'] }}</td>
                                <td><span class="badge rounded-pill text-bg-{{ $row['status'] === 'matched' ? 'success' : ($row['status'] === 'mismatch' ? 'danger' : 'warning') }}">{{ ucfirst($row['status']) }}</span></td>
                                <td class="text-nowrap">Rp {{ $money($row['settlement_amount']) }}</td>
                                <td class="text-nowrap">Rp {{ $money($row['allocated_amount']) }}</td>
                                <td class="text-nowrap">Rp {{ $money($row['unallocated_amount']) }}</td>
                                <td class="small">{{ $row['reasons'] ? implode(', ', $row['reasons']) : 'Tidak ada' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="finance-empty">
                <i class="bi bi-bank"></i>
                Belum ada settlement dengan allocation order pada filter ini.
            </div>
        @endif
    </div>
@endsection
