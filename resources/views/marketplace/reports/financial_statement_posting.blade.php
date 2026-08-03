@extends('layouts.app')

@section('title', 'Marketplace • Review Posting')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $statement = $preview['statement'];
    $summary = $statement['summary'];
    $filters = $preview['filters'];
@endphp

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <div class="text-uppercase text-muted small fw-semibold">Marketplace · Owner finance</div>
            <h4 class="mb-1">Review Posting Accounting</h4>
            <p class="text-muted mb-0">Periksa mapping COA dan total debit/kredit. Belum ada jurnal yang dibuat pada halaman ini.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('marketplace.reports.financial-statement', $filters) }}"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    </div>

    <div class="alert alert-warning"><i class="bi bi-shield-lock me-1"></i> Posting hanya memasukkan settlement marketplace. HPP (Rp {{ $fmt($preview['excluded_from_gl']['hpp']) }}) dan iklan (Rp {{ $fmt($preview['excluded_from_gl']['ad_cost']) }}) tetap menjadi metrik subledger sampai sumber kredit/asetnya ditetapkan.</div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Periode</div><strong>{{ $filters['date_from'] }} s/d {{ $filters['date_to'] }}</strong></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Order ready</div><strong>{{ $fmt($summary['order_count']) }}</strong></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Payout</div><strong>Rp {{ $fmt($summary['payout']) }}</strong></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm border-success"><div class="card-body"><div class="text-muted small">Balance check</div><strong class="text-success">Dr = Cr Rp {{ $fmt($preview['total_debit']) }}</strong></div></div></div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Draft journal · {{ $preview['accounting_scope'] }}</div>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>Akun</th><th>Keterangan</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
            <tbody>
                @foreach ($preview['lines'] as $line)
                    <tr><td><code>{{ $line['account_code'] }}</code></td><td>{{ $line['account_name'] }}</td><td class="text-end">{{ $line['debit'] > 0 ? 'Rp ' . $fmt($line['debit']) : '-' }}</td><td class="text-end">{{ $line['credit'] > 0 ? 'Rp ' . $fmt($line['credit']) : '-' }}</td></tr>
                @endforeach
                <tr class="table-light fw-bold"><td colspan="2">Total</td><td class="text-end">Rp {{ $fmt($preview['total_debit']) }}</td><td class="text-end">Rp {{ $fmt($preview['total_credit']) }}</td></tr>
            </tbody>
        </table></div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('marketplace.reports.financial-statement', $filters) }}">Batal</a>
        <form method="POST" action="{{ route('marketplace.reports.financial-statement.post') }}" onsubmit="return confirm('Posting batch ini ke jurnal umum? Pastikan mapping dan periode sudah benar.');">
            @csrf
            @foreach ($filters as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <button class="btn btn-primary"><i class="bi bi-journal-plus me-1"></i> Post ke jurnal umum</button>
        </form>
    </div>
</div>
@endsection
