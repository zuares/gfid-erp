@extends('layouts.app')
@section('title', 'Laporan Hutang Dagang')

@php $fmt = fn($n) => number_format((float)$n, 0, ',', '.'); @endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .rpt-page { display:grid; gap:1rem; }
        .rpt-btn { display:inline-flex; align-items:center; gap:.4rem; min-height:38px; padding:.45rem .9rem; border-radius:999px; border:1px solid rgba(15,23,42,.10); background:#fff; color:#0f172a; font-size:.83rem; font-weight:850; cursor:pointer; text-decoration:none; }
        .rpt-btn:hover { background:#f8fafc; color:#0f172a; }
        .rpt-kpi-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:.65rem; }
        .rpt-kpi { border:1px solid rgba(15,23,42,.08); border-radius:12px; background:#fff; padding:.75rem .9rem; }
        .rpt-kpi-label { color:#64748b; font-size:.67rem; font-weight:900; text-transform:uppercase; letter-spacing:.06em; }
        .rpt-kpi-value { margin-top:.15rem; color:#0f172a; font-size:1.15rem; font-weight:950; }
        .rpt-kpi-value.danger { color:#dc2626; }
        .rpt-kpi-value.warn   { color:#d97706; }
        .rpt-filter { display:flex; flex-wrap:wrap; gap:.55rem; align-items:flex-end; }
        .rpt-filter .form-control, .rpt-filter .form-select { min-height:38px; border-radius:999px; border-color:rgba(15,23,42,.12); font-size:.83rem; font-weight:700; box-shadow:none; }
        .rpt-num { text-align:right; font-variant-numeric:tabular-nums; font-weight:900; }
        .rpt-supplier-header { background:#f1f5f9; font-weight:900; font-size:.82rem; }
        .rpt-total-row td { font-weight:950; border-top:2px solid #0f172a !important; }
        .bucket-0  { color:#16a34a; }
        .bucket-31 { color:#d97706; }
        .bucket-61 { color:#ea580c; }
        .bucket-90 { color:#dc2626; }
        .rpt-empty { text-align:center; color:#64748b; padding:3rem 1rem; }
        @media(max-width:768px){ .rpt-kpi-grid{grid-template-columns:repeat(2,1fr);} }
    </style>
@endpush

@section('content')
<div class="rpt-page">

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="mb-0 fw-black">Hutang Dagang (AP Outstanding)</h5>
            <div class="text-muted" style="font-size:.8rem">Per tanggal · aging berdasarkan tanggal GRN tertua</div>
        </div>
    </div>

    {{-- KPI --}}
    <div class="rpt-kpi-grid">
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">Total Outstanding</div>
            <div class="rpt-kpi-value {{ $grandTotal > 0 ? 'danger' : '' }}">Rp {{ $fmt($grandTotal) }}</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">0–30 Hari</div>
            <div class="rpt-kpi-value bucket-0">Rp {{ $fmt($grand0_30) }}</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">31–60 Hari</div>
            <div class="rpt-kpi-value bucket-31">Rp {{ $fmt($grand31_60) }}</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">61–90 Hari</div>
            <div class="rpt-kpi-value bucket-61">Rp {{ $fmt($grand61_90) }}</div>
        </div>
        <div class="rpt-kpi">
            <div class="rpt-kpi-label">&gt;90 Hari</div>
            <div class="rpt-kpi-value bucket-90">Rp {{ $fmt($grand90plus) }}</div>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" class="rpt-filter">
        <div>
            <label class="form-label fw-bold" style="font-size:.74rem">Per Tanggal</label>
            <input type="date" name="as_of" class="form-control" style="width:160px" value="{{ $asOf }}">
        </div>
        <div>
            <label class="form-label fw-bold" style="font-size:.74rem">Supplier</label>
            <select name="supplier_id" class="form-select" style="min-width:180px">
                <option value="">Semua</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" @selected($supplierId == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rpt-btn" style="background:#0f172a;color:#fff;border-color:#0f172a">Tampilkan</button>
        @if(request()->hasAny(['as_of','supplier_id']))
            <a href="{{ route('accounting.ap-report.index') }}" class="rpt-btn">Reset</a>
        @endif
    </form>

    {{-- Tabel --}}
    <div class="card border-0 shadow-sm p-0">
        <div style="overflow-x:auto">
            <table class="table table-sm mb-0" style="font-size:.83rem">
                <thead class="table-light">
                    <tr>
                        <th>Supplier / PO</th>
                        <th>Tgl GRN</th>
                        <th class="rpt-num">Total GRN</th>
                        <th class="rpt-num">Sudah Bayar</th>
                        <th class="rpt-num">Outstanding</th>
                        <th class="text-center">Umur</th>
                        <th class="rpt-num bucket-0">0–30</th>
                        <th class="rpt-num bucket-31">31–60</th>
                        <th class="rpt-num bucket-61">61–90</th>
                        <th class="rpt-num bucket-90">&gt;90</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bySupplier as $sup)
                        {{-- Supplier header --}}
                        <tr class="rpt-supplier-header">
                            <td colspan="4">{{ $sup->supplier_name }}
                                <span class="text-muted fw-normal" style="font-size:.74rem">· {{ $sup->pos->count() }} PO</span>
                            </td>
                            <td class="rpt-num text-dark">Rp {{ $fmt($sup->total) }}</td>
                            <td></td>
                            <td class="rpt-num bucket-0">{{ $sup->bucket_0_30 > 0 ? 'Rp '.$fmt($sup->bucket_0_30) : '-' }}</td>
                            <td class="rpt-num bucket-31">{{ $sup->bucket_31_60 > 0 ? 'Rp '.$fmt($sup->bucket_31_60) : '-' }}</td>
                            <td class="rpt-num bucket-61">{{ $sup->bucket_61_90 > 0 ? 'Rp '.$fmt($sup->bucket_61_90) : '-' }}</td>
                            <td class="rpt-num bucket-90">{{ $sup->bucket_90plus > 0 ? 'Rp '.$fmt($sup->bucket_90plus) : '-' }}</td>
                        </tr>
                        {{-- PO rows --}}
                        @foreach($sup->pos as $po)
                            @php
                                $d = (int)$po->days_outstanding;
                                $bucketClass = $d <= 30 ? 'bucket-0' : ($d <= 60 ? 'bucket-31' : ($d <= 90 ? 'bucket-61' : 'bucket-90'));
                            @endphp
                            <tr>
                                <td style="padding-left:1.5rem; color:#475569">
                                    <a href="{{ route('purchasing.purchase_orders.show', $po->po_id) }}"
                                       style="color:#2563eb; text-decoration:none; font-weight:700">{{ $po->po_code }}</a>
                                </td>
                                <td style="color:#64748b">{{ \Carbon\Carbon::parse($po->oldest_grn_date)->format('d M Y') }}</td>
                                <td class="rpt-num">Rp {{ $fmt($po->grn_total) }}</td>
                                <td class="rpt-num" style="color:#16a34a">Rp {{ $fmt($po->paid_total) }}</td>
                                <td class="rpt-num fw-black">Rp {{ $fmt($po->outstanding) }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $d <= 30 ? 'bg-success' : ($d <= 60 ? 'bg-warning text-dark' : ($d <= 90 ? 'bg-orange' : 'bg-danger')) }}"
                                          style="{{ $d > 60 && $d <= 90 ? 'background:#ea580c' : '' }}">
                                        {{ $d }}h
                                    </span>
                                </td>
                                <td class="rpt-num bucket-0">{{ $d <= 30 ? 'Rp '.$fmt($po->outstanding) : '-' }}</td>
                                <td class="rpt-num bucket-31">{{ ($d > 30 && $d <= 60) ? 'Rp '.$fmt($po->outstanding) : '-' }}</td>
                                <td class="rpt-num bucket-61">{{ ($d > 60 && $d <= 90) ? 'Rp '.$fmt($po->outstanding) : '-' }}</td>
                                <td class="rpt-num bucket-90">{{ $d > 90 ? 'Rp '.$fmt($po->outstanding) : '-' }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="10" class="rpt-empty">Tidak ada hutang outstanding.</td></tr>
                    @endforelse

                    @if($bySupplier->count() > 1)
                        <tr class="rpt-total-row">
                            <td colspan="4" class="fw-black">TOTAL</td>
                            <td class="rpt-num">Rp {{ $fmt($grandTotal) }}</td>
                            <td></td>
                            <td class="rpt-num bucket-0">Rp {{ $fmt($grand0_30) }}</td>
                            <td class="rpt-num bucket-31">Rp {{ $fmt($grand31_60) }}</td>
                            <td class="rpt-num bucket-61">Rp {{ $fmt($grand61_90) }}</td>
                            <td class="rpt-num bucket-90">Rp {{ $fmt($grand90plus) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
