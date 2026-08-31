@extends('marketplace.finance.layout')

@section('finance-content')
    @php
        $money = fn ($value): string => number_format((float) $value, 2, ',', '.');
        $cards = [
            ['label' => 'Gross Sales', 'value' => $overview['gross_sales'], 'icon' => 'bi-cart-check'],
            ['label' => 'Total Escrow Fee', 'value' => $overview['total_escrow_fee'], 'icon' => 'bi-percent'],
            ['label' => 'Pending Income', 'value' => $overview['pending_income'], 'icon' => 'bi-hourglass-split'],
            ['label' => 'To Release', 'value' => $overview['to_release'], 'icon' => 'bi-clock-history'],
            ['label' => 'Released', 'value' => $overview['released'], 'icon' => 'bi-check2-circle'],
            ['label' => 'Settlement Received', 'value' => $overview['settlement_received'], 'icon' => 'bi-bank'],
            ['label' => 'Unreconciled Amount', 'value' => $overview['unreconciled_amount'], 'icon' => 'bi-exclamation-triangle'],
        ];
    @endphp

    <div class="row g-3 mb-4">
        @foreach ($cards as $card)
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="finance-card card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="text-muted small">{{ $card['label'] }}</span>
                            <i class="bi {{ $card['icon'] }} text-primary"></i>
                        </div>
                        <div class="finance-number">Rp {{ $money($card['value']) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-5">
            <div class="finance-card card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Status rekonsiliasi</h2>
                    <div class="row g-2 text-center mt-2">
                        @foreach (['matched' => 'Matched', 'mismatch' => 'Mismatch', 'unmatched' => 'Unmatched', 'pending' => 'Pending'] as $key => $label)
                            <div class="col-6">
                                <div class="border rounded p-3">
                                    <div class="h4 mb-1">{{ $report['summary'][$key] }}</div>
                                    <div class="text-muted small">{{ $label }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-7">
            <div class="finance-card card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6">Akses cepat</h2>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a class="btn btn-outline-primary" href="{{ route('marketplace.finance.transactions', request()->query()) }}">Lihat transactions</a>
                        <a class="btn btn-outline-primary" href="{{ route('marketplace.finance.settlements', request()->query()) }}">Lihat settlements</a>
                        <a class="btn btn-outline-primary" href="{{ route('marketplace.finance.reconciliation', request()->query()) }}">Buka reconciliation</a>
                        <a class="btn btn-outline-primary" href="{{ route('marketplace.finance.fee-analysis', request()->query()) }}">Analisa fee</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
