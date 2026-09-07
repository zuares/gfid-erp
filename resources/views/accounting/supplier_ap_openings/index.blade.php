@extends('layouts.app')

@section('title', 'Accounting • Saldo Awal Hutang Supplier')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $collection = $balances->getCollection();
    $activeTotal = $collection->where('status', 'posted')->sum('amount');
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .sap-page { display:grid; gap:1rem; }
        .sap-actions { display:flex; justify-content:flex-end; gap:.5rem; flex-wrap:wrap; }
        .sap-btn { display:inline-flex; align-items:center; justify-content:center; min-height:40px; padding:.55rem .95rem; border-radius:999px; border:1px solid rgba(15,23,42,.1); background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:850; }
        .sap-btn:hover { color:#0f172a; background:#f8fafc; }
        .sap-btn-primary { color:#fff; background:#0f172a; border-color:#0f172a; }
        .sap-btn-primary:hover { color:#fff; background:#1e293b; }
        .sap-btn-danger { color:#b91c1c; background:#fff5f5; border-color:#fecaca; min-height:34px; padding:.35rem .7rem; }
        .sap-filter { display:grid; grid-template-columns:minmax(180px,1fr) 150px 150px 150px auto; gap:.55rem; align-items:end; }
        .sap-filter .form-control,.sap-filter .form-select { min-height:40px; border-radius:999px; border-color:rgba(15,23,42,.12); font-size:.84rem; font-weight:700; box-shadow:none; }
        .sap-label { display:block; margin-bottom:.28rem; color:#64748b; font-size:.68rem; font-weight:900; text-transform:uppercase; letter-spacing:.06em; }
        .sap-table-wrap { overflow:auto; }
        .sap-num { text-align:right; white-space:nowrap; font-weight:900; font-variant-numeric:tabular-nums; }
        .sap-meta { color:#64748b; font-size:.76rem; }
        .sap-status { display:inline-flex; border-radius:999px; padding:.22rem .6rem; font-size:.74rem; font-weight:850; }
        .sap-posted { color:#166534; background:#dcfce7; }
        .sap-void { color:#b91c1c; background:#fee2e2; }
        .sap-empty { text-align:center; color:#64748b; padding:2.5rem 1rem; }
        @media(max-width:900px){ .sap-filter{grid-template-columns:1fr 1fr;} .sap-filter > div:first-child,.sap-filter > div:nth-child(4),.sap-filter > div:last-child{grid-column:1/-1;} }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Accounting"
        title="Saldo Awal Hutang Supplier"
        description="Catat hutang supplier sebelum transaksi PO/GRN berjalan agar ikut muncul di AP Report.">
        <x-slot:actions>
            <div class="sap-actions">
                <a href="{{ route('accounting.ap-report.index') }}" class="sap-btn">AP Report</a>
                <a href="{{ route('accounting.supplier-ap-openings.create') }}" class="sap-btn sap-btn-primary">+ Saldo Awal Hutang</a>
            </div>
        </x-slot:actions>

        <div class="sap-page">
            @if (session('message'))
                <div class="alert alert-{{ session('status') === 'ok' ? 'success' : 'danger' }} mb-0">{{ session('message') }}</div>
            @endif

            <x-gf.panel title="Filter" subtitle="Saldo awal yang aktif akan dihitung oleh AP Report sampai tanggal laporan.">
                <form method="GET" class="sap-filter">
                    <div>
                        <label class="sap-label" for="supplier_id">Supplier</label>
                        <select id="supplier_id" name="supplier_id" class="form-select">
                            <option value="">Semua supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>
                                    {{ $supplier->name }}{{ $supplier->code ? ' · '.$supplier->code : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="sap-label" for="from">Dari</label>
                        <input id="from" type="date" name="from" class="form-control" value="{{ request('from') }}">
                    </div>
                    <div>
                        <label class="sap-label" for="to">Sampai</label>
                        <input id="to" type="date" name="to" class="form-control" value="{{ request('to') }}">
                    </div>
                    <div>
                        <label class="sap-label" for="status">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">Semua status</option>
                            <option value="posted" @selected(request('status') === 'posted')>Aktif</option>
                            <option value="void" @selected(request('status') === 'void')>Void</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="sap-btn">Filter</button>
                        <a href="{{ route('accounting.supplier-ap-openings.index') }}" class="sap-btn">Reset</a>
                    </div>
                </form>
            </x-gf.panel>

            <x-gf.panel title="Daftar Saldo Awal" subtitle="Nominal aktif di halaman ini: Rp {{ $fmt($activeTotal) }}">
                @if ($balances->isEmpty())
                    <div class="sap-empty">Belum ada saldo awal hutang supplier.</div>
                @else
                    <div class="sap-table-wrap">
                        <table class="table table-hover align-middle mb-0 gf-clean-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Supplier</th>
                                    <th>Invoice / Referensi</th>
                                    <th>Jatuh Tempo</th>
                                    <th class="sap-num">Nominal</th>
                                    <th>Akun</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($balances as $balance)
                                    @php $isVoid = $balance->status === 'void' || $balance->voided_at; @endphp
                                    <tr style="{{ $isVoid ? 'opacity:.65' : '' }}">
                                        <td>{{ $balance->date?->format('d M Y') }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $balance->supplier?->name ?? '-' }}</div>
                                            @if ($balance->supplier?->code)<div class="sap-meta">{{ $balance->supplier->code }}</div>@endif
                                        </td>
                                        <td>
                                            <div>{{ $balance->reference_no ?: 'Tanpa nomor referensi' }}</div>
                                            <div class="sap-meta">Tanggal invoice: {{ $balance->invoice_date?->format('d M Y') ?: '-' }}</div>
                                        </td>
                                        <td>{{ $balance->due_date?->format('d M Y') ?: '-' }}</td>
                                        <td class="sap-num">Rp {{ $fmt($balance->amount) }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $balance->apAccount?->code }} · {{ $balance->apAccount?->name }}</div>
                                            <div class="sap-meta">Lawan: {{ $balance->offsetAccount?->code }} · {{ $balance->offsetAccount?->name }}</div>
                                        </td>
                                        <td><span class="sap-status {{ $isVoid ? 'sap-void' : 'sap-posted' }}">{{ $isVoid ? 'Void' : 'Aktif' }}</span></td>
                                        <td class="text-end">
                                            @if (! $isVoid)
                                                <form method="POST" action="{{ route('accounting.supplier-ap-openings.void', $balance) }}" class="d-inline" data-gf-confirm-title="Void saldo awal?" data-gf-confirm-text="Jurnal reversal akan dibuat dan saldo tidak lagi dihitung di AP Report.">
                                                    @csrf
                                                    <button type="submit" class="sap-btn sap-btn-danger">Void</button>
                                                </form>
                                            @else
                                                <span class="sap-meta">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $balances->links() }}</div>
                @endif
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection
