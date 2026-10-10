@extends('layouts.app')

@section('title', 'Principal Sales Performance')

@php
    $summary = is_array($summary ?? null) ? $summary : [];
    $details = collect($details ?? []);
    $filters = $filters ?? [];
    $money = function ($value, $currency = null) {
        if ($value === null || $value === '') {
            return '-';
        }

        return ($currency ? $currency . ' ' : '') . number_format((float) $value, 2, ',', '.');
    };
    $integer = fn ($value) => $value === null || $value === '' ? '-' : number_format((int) $value, 0, ',', '.');
    $percent = fn ($value) => $value === null || $value === '' ? '-' : number_format((float) $value * 100, 2, ',', '.') . '%';
    $currency = $summary['currency'] ?? ($filters['currency'] ?? 'USD');
@endphp

<style>
    .principal-page { max-width: 1500px; margin: 0 auto; padding: 1.25rem; }
    .principal-hero { border-radius: 20px; padding: 1.5rem; color: #fff; background: linear-gradient(135deg, #172554, #2563eb); box-shadow: 0 18px 40px rgba(37, 99, 235, .18); }
    .principal-card { border: 1px solid #e5e7eb; border-radius: 16px; background: var(--bs-body-bg, #fff); box-shadow: 0 8px 24px rgba(15, 23, 42, .05); }
    .principal-card .card-body { padding: 1.15rem; }
    .principal-label { color: #64748b; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .principal-value { font-size: 1.35rem; font-weight: 800; color: #0f172a; }
    .principal-muted { color: #64748b; font-size: .82rem; }
    .principal-table th { color: #64748b; font-size: .7rem; text-transform: uppercase; white-space: nowrap; }
    .principal-table td { white-space: nowrap; vertical-align: middle; }
    .principal-table-wrap { overflow-x: auto; }
    @media (max-width: 768px) { .principal-page { padding: .75rem; } .principal-hero { padding: 1.1rem; } }
</style>

<div class="principal-page">
    <div class="principal-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="small opacity-75 mb-1"><i class="bi bi-globe2 me-1"></i> Shopee Brand Portal</div>
                <h1 class="h3 fw-bold mb-2">Principal Sales Performance</h1>
                <p class="mb-0 opacity-75">Performa penjualan principal yang diringkas berdasarkan wilayah/negara.</p>
            </div>
            <span class="badge rounded-pill text-bg-light text-primary px-3 py-2">Read-only</span>
        </div>
    </div>

    @if ($error)
        <div class="alert alert-danger principal-card border-0 mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ $error }}
        </div>
    @endif

    @if ($stores->isEmpty())
        <div class="alert alert-warning principal-card border-0">
            Belum ada koneksi toko Shopee aktif. Hubungkan toko terlebih dahulu melalui menu Toko Online.
        </div>
    @endif

    <form method="GET" class="principal-card mb-4">
        <div class="card-body">
            <input type="hidden" name="load" value="1">
            <div class="row g-3">
                <div class="col-12 col-lg-3">
                    <label class="form-label principal-label">Koneksi Shopee</label>
                    <select class="form-select" name="store_id" required>
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}" @selected((string) $store->id === (string) ($filters['store_id'] ?? ''))>
                                {{ $store->name }} (#{{ $store->external_shop_id ?? $store->id }})
                            </option>
                        @endforeach
                    </select>
                    <div class="principal-muted mt-1">Token principal harus tersimpan pada koneksi yang dipilih.</div>
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label principal-label">Principal ID</label>
                    <input class="form-control" name="principal_id" value="{{ $filters['principal_id'] ?? '' }}" placeholder="Contoh: 123456" required>
                    <div class="principal-muted mt-1">Bisa diisi dari <code>SHOPEE_PRINCIPAL_ID</code>.</div>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label principal-label">Mulai</label>
                    <input type="date" class="form-control" name="start_date" value="{{ $filters['start_date'] ?? '' }}" max="{{ now()->subDay()->toDateString() }}" required>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label principal-label">Sampai</label>
                    <input type="date" class="form-control" name="end_date" value="{{ $filters['end_date'] ?? '' }}" max="{{ now()->subDay()->toDateString() }}" required>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label principal-label">Timezone</label>
                    <select class="form-select" name="timezone">
                        @foreach (['GMT+7', 'GMT+8', 'GMT-3'] as $timezone)
                            <option value="{{ $timezone }}" @selected(($filters['timezone'] ?? 'GMT+7') === $timezone)>{{ $timezone }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label principal-label">Granularitas</label>
                    <select class="form-select" name="granularity">
                        @foreach (['customize' => 'Custom / Harian', 'day' => 'Hari', 'week' => 'Minggu', 'month' => 'Bulan', 'quarter' => 'Kuartal', 'year' => 'Tahun'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['granularity'] ?? 'customize') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label principal-label">Mata uang</label>
                    <select class="form-select" name="currency">
                        @foreach (['USD', 'LOCAL'] as $currencyOption)
                            <option value="{{ $currencyOption }}" @selected(($filters['currency'] ?? 'USD') === $currencyOption)>{{ $currencyOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <label class="form-label principal-label">Wilayah (opsional)</label>
                    <input class="form-control" name="regions" value="{{ $filters['regions'] ?? '' }}" placeholder="Contoh: ID, MY, SG — kosongkan untuk semua wilayah">
                </div>
                <div class="col-12 col-lg-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-arrow-repeat me-1"></i>Ambil Data</button>
                </div>
            </div>
        </div>
    </form>

    @if ($loaded && !$error)
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">Ringkasan performa</h2>
                <div class="principal-muted">{{ $filters['start_date'] }} – {{ $filters['end_date'] }} · {{ $filters['granularity'] }}</div>
            </div>
            <div class="principal-muted">{{ $details->count() }} wilayah · {{ $currency }}</div>
        </div>

        <div class="row g-3 mb-4">
            @foreach ([
                ['Sales', $money($summary['sales'] ?? null, $currency), 'bi-cash-stack'],
                ['Orders', $integer($summary['orders'] ?? null), 'bi-bag-check'],
                ['Units sold', $integer($summary['units_sold'] ?? null), 'bi-box-seam'],
                ['Product views', $integer($summary['product_views'] ?? null), 'bi-eye'],
                ['Unique visitors', $integer($summary['unique_visitors'] ?? null), 'bi-people'],
                ['Order conversion', $percent($summary['order_conversion_rate'] ?? null), 'bi-graph-up-arrow'],
            ] as [$label, $value, $icon])
                <div class="col-6 col-xl-2">
                    <div class="principal-card h-100"><div class="card-body">
                        <div class="principal-label mb-2"><i class="bi {{ $icon }} me-1"></i>{{ $label }}</div>
                        <div class="principal-value">{{ $value }}</div>
                    </div></div>
                </div>
            @endforeach
        </div>

        <div class="principal-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Breakdown per wilayah</h2>
                        <div class="principal-muted">Data dari <code>get_principal_sales_performance_detail</code></div>
                    </div>
                </div>
                <div class="principal-table-wrap">
                    <table class="table table-hover principal-table mb-0">
                        <thead><tr>
                            <th>Region</th><th>Sales</th><th>Orders</th><th>Units</th><th>Avg basket</th><th>Avg price</th><th>Views</th><th>Visitors</th><th>Item conv.</th><th>Order conv.</th>
                        </tr></thead>
                        <tbody>
                        @forelse ($details as $detail)
                            <tr>
                                <td><span class="badge text-bg-light">{{ $detail['region'] ?? '-' }}</span></td>
                                <td class="fw-semibold">{{ $money($detail['sales'] ?? null, $detail['currency'] ?? $currency) }}</td>
                                <td>{{ $integer($detail['orders'] ?? null) }}</td>
                                <td>{{ $integer($detail['units_sold'] ?? null) }}</td>
                                <td>{{ $money($detail['average_basket_size'] ?? null) }}</td>
                                <td>{{ $money($detail['average_selling_price'] ?? null) }}</td>
                                <td>{{ $integer($detail['product_views'] ?? null) }}</td>
                                <td>{{ $integer($detail['unique_visitors'] ?? null) }}</td>
                                <td>{{ $percent($detail['item_conversion_rate'] ?? null) }}</td>
                                <td>{{ $percent($detail['order_conversion_rate'] ?? null) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-secondary py-4">Shopee tidak mengembalikan detail wilayah untuk filter ini.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @elseif (!$loaded)
        <div class="principal-card"><div class="card-body text-center py-5">
            <i class="bi bi-bar-chart-line fs-1 text-primary"></i>
            <h2 class="h5 fw-bold mt-3">Pilih filter untuk mulai</h2>
            <p class="principal-muted mb-0">Modul ini hanya membaca data performa principal dan tidak mengubah data Shopee.</p>
        </div></div>
    @endif
</div>
