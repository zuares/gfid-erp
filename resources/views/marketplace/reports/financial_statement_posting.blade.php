@extends('layouts.app')

@section('title', 'Marketplace • Posting Settlement')

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
            <h4 class="mb-1">Posting settlement ke jurnal</h4>
            <p class="text-muted mb-0">Periksa draft jurnal terlebih dahulu, lalu simpan batch settlement ke jurnal umum.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('marketplace.reports.financial-statement', $filters) }}"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    </div>

    <div class="alert alert-info d-flex gap-2 align-items-start"><i class="bi bi-info-circle-fill mt-1"></i><div><strong>Yang perlu dilakukan:</strong> pastikan periode dan angka sudah benar, cek balance <strong>Dr = Cr</strong>, lalu klik <strong>Post settlement ke jurnal</strong>. Proses ini membuat satu batch jurnal dan aman diulang.</div></div>
    <div class="alert alert-warning"><i class="bi bi-shield-lock me-1"></i> HPP (Rp {{ $fmt($preview['excluded_from_gl']['hpp']) }}) tetap dikecualikan karena mengikuti posting shipment. Top up saldo iklan (Rp {{ $fmt($preview['included_in_gl']['wallet_ad_topup']) }}) dipindahkan dari 1302 ke <strong>{{ $preview['included_in_gl']['ad_wallet_account_code'] }} {{ $preview['included_in_gl']['ad_wallet_account_name'] }}</strong>. Biaya iklan subledger (Rp {{ $fmt($preview['included_in_gl']['amount']) }}) dari <strong>{{ str_replace('_', ' ', $preview['included_in_gl']['source']) }}</strong> masuk ke akun <strong>{{ $preview['included_in_gl']['account_code'] }} {{ $preview['included_in_gl']['account_name'] }}</strong> dengan lawan saldo prepaid, bukan 1302. Biaya iklan order/settlement tidak dimasukkan.</div>
    @if (($preview['included_in_gl']['source'] ?? 'none') !== 'wallet_actual' && ($preview['included_in_gl']['amount'] ?? 0) > 0)
        <div class="alert alert-info"><i class="bi bi-arrow-repeat me-1"></i> Posting ini masih memakai fallback Ads Daily untuk toko yang belum memiliki transaksi wallet. Untuk posting final yang paling akurat, kembali dan jalankan <strong>Sync biaya iklan</strong> terlebih dahulu.</div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Mapping COA marketplace</div>
        <div class="card-body py-2">
            <div class="row g-2 small">
                @foreach ($accountCodes as $key => $code)
                    @php($account = $accountMappings->get($code))
                    <div class="col-md-4 col-lg-3">
                        <div class="border rounded p-2 h-100">
                            <div class="text-muted">{{ str_replace('_', ' ', ucfirst($key)) }}</div>
                            <code>{{ $code }}</code>
                            <div>{{ $account?->name ?? 'Belum tersedia' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="text-muted mt-2" style="font-size:.8rem;">Biaya settlement masih memakai akun gabungan sesuai aturan existing. SPM top up menggunakan 1302 → saldo iklan prepaid 1304. Pemakaian iklan menggunakan 1304 → biaya iklan 6206. Penarikan ke bank tetap Dr Bank / Cr 1302.</div>
        </div>
    </div>

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
            <button class="btn btn-primary"><i class="bi bi-journal-plus me-1"></i> Post settlement ke jurnal</button>
        </form>
    </div>
</div>
@endsection
