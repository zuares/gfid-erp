@extends('layouts.app')
@section('title', 'Rekonsiliasi Gap Cost')

@push('head')
<style>
.rc-wrap   { max-width: 960px; margin: 0 auto; display: grid; gap: 1.5rem; padding: 1rem 1rem 3rem; }
.rc-topbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.rc-title  { font-size: 1.3rem; font-weight: 950; color: #0f172a; letter-spacing: -.025em; }
.rc-sub    { font-size: .82rem; color: #64748b; margin-top: .15rem; }

/* KPI strip */
.rc-kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
.rc-kpi  { border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; padding: .85rem 1rem; }
.rc-kpi-label { font-size: .68rem; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; }
.rc-kpi-val   { font-size: 1.3rem; font-weight: 950; margin-top: .1rem; }
.rc-kpi-val.ok  { color: #166534; }
.rc-kpi-val.warn{ color: #b45309; }
.rc-kpi-val.err { color: #b91c1c; }

/* Section card */
.rc-section { border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; background: #fff; }
.rc-section-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: .7rem 1rem; background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.rc-section-title { font-size: .82rem; font-weight: 900; color: #0f172a; display: flex; align-items: center; gap: .5rem; }
.rc-badge {
    display: inline-flex; align-items: center; padding: .18rem .55rem;
    border-radius: 999px; font-size: .72rem; font-weight: 900;
}
.rc-badge.ok   { background: #dcfce7; color: #166534; }
.rc-badge.warn { background: #fef3c7; color: #92400e; }
.rc-badge.err  { background: #fee2e2; color: #b91c1c; }

/* Table */
.rc-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.rc-table th {
    font-size: .7rem; text-transform: uppercase; letter-spacing: .07em;
    color: #94a3b8; text-align: left; padding: .5rem .85rem;
    border-bottom: 1px solid #f1f5f9;
}
.rc-table td {
    padding: .6rem .85rem; border-bottom: 1px solid #f8fafc;
    font-size: .85rem; vertical-align: middle;
}
.rc-table tr:last-child td { border-bottom: none; }
.rc-table tr:hover td { background: #f8fafc; }
.rc-code  { font-size: .75rem; font-weight: 900; color: #64748b; font-variant-numeric: tabular-nums; }
.rc-name  { font-weight: 700; color: #0f172a; }
.rc-meta  { font-size: .75rem; color: #94a3b8; }
.rc-num   { font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
.rc-neg   { color: #b91c1c; font-weight: 900; }
.rc-link  { color: #1d4ed8; text-decoration: none; font-weight: 850; }
.rc-link:hover { text-decoration: underline; }

/* Empty state */
.rc-empty { padding: 1.5rem; text-align: center; color: #94a3b8; font-size: .85rem; }
.rc-empty-icon { font-size: 1.5rem; display: block; margin-bottom: .35rem; }

/* Hint box */
.rc-hint {
    background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px;
    padding: .75rem 1rem; font-size: .82rem; color: #166534;
    display: flex; gap: .6rem; align-items: flex-start;
}

@media(max-width:640px){
    .rc-kpis { grid-template-columns: repeat(2, 1fr); }
    .rc-hide-sm { display: none; }
}
</style>
@endpush

@section('content')
<div class="rc-wrap">

    {{-- Header --}}
    <div class="rc-topbar">
        <div>
            <div class="rc-title">Rekonsiliasi Gap Cost</div>
            <div class="rc-sub">Update setiap kali halaman dimuat · {{ now()->format('d/m/Y H:i') }}</div>
        </div>
        <a href="{{ url()->previous() }}" class="rc-link" style="font-size:.82rem">← Kembali</a>
    </div>

    {{-- KPI --}}
    <div class="rc-kpis" style="grid-template-columns:repeat(4,1fr)">
        <div class="rc-kpi">
            <div class="rc-kpi-label">Stok Negatif</div>
            <div class="rc-kpi-val {{ $negativeStocks->count() > 0 ? 'err' : 'ok' }}">
                {{ $negativeStocks->count() }}
                <span style="font-size:.75rem;font-weight:700;color:#94a3b8">item</span>
            </div>
        </div>
        <div class="rc-kpi">
            <div class="rc-kpi-label">BOM Belum Lengkap</div>
            <div class="rc-kpi-val {{ $bomGapLines->count() > 0 ? 'warn' : 'ok' }}">
                {{ $bomGapLines->count() }}
                <span style="font-size:.75rem;font-weight:700;color:#94a3b8">baris</span>
            </div>
        </div>
        <div class="rc-kpi">
            <div class="rc-kpi-label">Kelengkapan Belum Keluar</div>
            <div class="rc-kpi-val {{ $unissuedSupplies->count() > 0 ? 'err' : 'ok' }}">
                {{ $unissuedSupplies->count() }}
                <span style="font-size:.75rem;font-weight:700;color:#94a3b8">baris</span>
            </div>
        </div>
        <div class="rc-kpi">
            <div class="rc-kpi-label">Kelengkapan Pending GRN</div>
            <div class="rc-kpi-val {{ $pendingSupplies->count() > 0 ? 'warn' : 'ok' }}">
                {{ $pendingSupplies->count() }}
                <span style="font-size:.75rem;font-weight:700;color:#94a3b8">baris</span>
            </div>
        </div>
    </div>

    @if ($totalGaps === 0)
        <div class="rc-hint">
            <span>✅</span>
            <span>Tidak ada gap. Semua stok positif, BOM lengkap, kelengkapan jahit sudah keluar & ter-GRN.</span>
        </div>
    @else
        <div class="rc-hint" style="background:#fefce8;border-color:#fde047;color:#854d0e;">
            <span>💡</span>
            <span>
                Ada <strong>{{ $totalGaps }} gap</strong> yang perlu direkonsiliasi.
                Masukkan GRN yang kurang, lalu muat ulang halaman ini untuk verifikasi.
                Produksi tetap berjalan normal — ini hanya untuk akurasi cost.
            </span>
        </div>
    @endif

    {{-- 1. Stok Negatif --}}
    <div class="rc-section">
        <div class="rc-section-head">
            <div class="rc-section-title">
                📦 Stok Negatif
                <span class="rc-badge {{ $negativeStocks->count() > 0 ? 'err' : 'ok' }}">
                    {{ $negativeStocks->count() }}
                </span>
            </div>
            @if ($negativeStocks->count() > 0)
                <span style="font-size:.75rem;color:#64748b">Masukkan GRN untuk item di bawah</span>
            @endif
        </div>
        @if ($negativeStocks->isEmpty())
            <div class="rc-empty"><span class="rc-empty-icon">✅</span>Semua stok positif</div>
        @else
            <table class="rc-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Gudang</th>
                        <th class="rc-num">Stok</th>
                        <th class="rc-hide-sm">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($negativeStocks as $row)
                        <tr>
                            <td>
                                <div class="rc-code">{{ $row->item_code }}</div>
                                <div class="rc-name">{{ $row->item_name }}</div>
                            </td>
                            <td>
                                <span class="rc-badge warn">{{ $row->warehouse_code }}</span>
                            </td>
                            <td class="rc-num rc-neg">{{ number_format((float)$row->qty, 2, ',', '.') }}</td>
                            <td class="rc-hide-sm">
                                @if (Route::has('purchase_receipts.create'))
                                    <a class="rc-link" href="{{ route('purchase_receipts.create') }}">+ GRN</a>
                                @else
                                    <span class="rc-meta">Input GRN</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- 2. BOM Finishing Gap --}}
    <div class="rc-section">
        <div class="rc-section-head">
            <div class="rc-section-title">
                🧵 Finishing BOM Belum Lengkap
                <span class="rc-badge {{ $bomGapLines->count() > 0 ? 'warn' : 'ok' }}">
                    {{ $bomGapLines->count() }}
                </span>
            </div>
            @if ($bomGapLines->count() > 0)
                <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
                    <span style="font-size:.75rem;color:#64748b">Input GRN → apply ulang BOM di detail job</span>
                    @if(Route::has('purchasing.material_shortages.index'))
                    <a href="{{ route('purchasing.material_shortages.index') }}"
                       style="font-size:.75rem;padding:.2rem .65rem;border-radius:6px;background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.35);color:#1d4ed8;text-decoration:none;white-space:nowrap;">
                        📋 Cek PR/PO Material →
                    </a>
                    @endif
                </div>
            @endif
        </div>
        @if ($bomGapLines->isEmpty())
            <div class="rc-empty"><span class="rc-empty-icon">✅</span>Semua BOM finishing lengkap</div>
        @else
            <table class="rc-table">
                <thead>
                    <tr>
                        <th>Finishing Job</th>
                        <th>SKU Produksi</th>
                        <th>⚠️ Material Kurang (belum GRN)</th>
                        <th class="rc-num rc-hide-sm">Qty OK</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bomGapLines->groupBy('job_code') as $jobCode => $lines)
                        @php $first = $lines->first(); @endphp
                        <tr>
                            <td>
                                <div class="rc-code">{{ $jobCode }}</div>
                                <div class="rc-meta">{{ \Carbon\Carbon::parse($first->job_date)->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                @foreach ($lines as $l)
                                    <div class="rc-name">{{ $l->item_code }}</div>
                                    <div class="rc-meta">{{ $l->item_name }}</div>
                                @endforeach
                            </td>
                            <td>
                                @foreach ($lines as $l)
                                    @php $missing = $missingMaterials->get($l->fg_item_id, collect()); @endphp
                                    @if ($missing->isEmpty())
                                        <span class="rc-meta">—</span>
                                    @else
                                        @foreach ($missing as $m)
                                            <div style="display:flex;align-items:center;gap:.35rem;margin-bottom:.2rem">
                                                <span class="rc-badge err" style="font-size:.68rem;padding:.1rem .4rem">GRN!</span>
                                                <span class="rc-code" style="color:#b91c1c">{{ $m->mat_code }}</span>
                                                <span class="rc-meta">{{ $m->mat_name }}</span>
                                            </div>
                                        @endforeach
                                    @endif
                                @endforeach
                            </td>
                            <td class="rc-num rc-hide-sm">
                                @foreach ($lines as $l)
                                    <div>{{ number_format((int)$l->qty_ok) }} pcs</div>
                                @endforeach
                            </td>
                            <td>
                                @if (Route::has('production.finishing_jobs.show'))
                                    <a class="rc-link" href="{{ route('production.finishing_jobs.show', $first->job_id) }}">Lihat →</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- 3. Kelengkapan Jahit Belum Dikeluarkan --}}
    <div class="rc-section">
        <div class="rc-section-head">
            <div class="rc-section-title">
                🧶 Kelengkapan Jahit Belum Dikeluarkan
                <span class="rc-badge {{ $unissuedSupplies->count() > 0 ? 'err' : 'ok' }}">
                    {{ $unissuedSupplies->count() }}
                </span>
            </div>
            @if ($unissuedSupplies->count() > 0)
                <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
                    <span style="font-size:.75rem;color:#64748b">Keluarkan kelengkapan dari gudang ke penjahit</span>
                    @if(Route::has('purchasing.material_shortages.index'))
                    <a href="{{ route('purchasing.material_shortages.index') }}"
                       style="font-size:.75rem;padding:.2rem .65rem;border-radius:6px;background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.35);color:#1d4ed8;text-decoration:none;white-space:nowrap;">
                        📋 Cek PR/PO Material →
                    </a>
                    @endif
                </div>
            @endif
        </div>
        @if ($unissuedSupplies->isEmpty())
            <div class="rc-empty"><span class="rc-empty-icon">✅</span>Semua kelengkapan sudah dikeluarkan</div>
        @else
            <table class="rc-table">
                <thead>
                    <tr>
                        <th>Ambil Jahit</th>
                        <th>Kelengkapan</th>
                        <th class="rc-num">Butuh</th>
                        <th class="rc-num">Sudah Keluar</th>
                        <th class="rc-num rc-neg">Kurang</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($unissuedSupplies->groupBy('pickup_code') as $pickupCode => $lines)
                        @php $first = $lines->first(); @endphp
                        <tr>
                            <td>
                                <div class="rc-code">{{ $pickupCode }}</div>
                                <div class="rc-meta">{{ \Carbon\Carbon::parse($first->pickup_date)->format('d/m/Y') }}</div>
                                <div class="rc-meta" style="margin-top:.1rem">
                                    <span class="rc-badge {{ $first->pickup_status === 'completed' ? 'warn' : 'ok' }}" style="font-size:.65rem">
                                        {{ $first->pickup_status }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                @foreach ($lines as $l)
                                    <div class="rc-name">{{ $l->item_code }}</div>
                                    <div class="rc-meta" style="margin-bottom:.3rem">{{ $l->item_name }}</div>
                                @endforeach
                            </td>
                            <td class="rc-num">
                                @foreach ($lines as $l)
                                    <div>{{ number_format((float)$l->required_qty, 2, ',', '.') }} {{ $l->uom }}</div>
                                @endforeach
                            </td>
                            <td class="rc-num">
                                @foreach ($lines as $l)
                                    <div>{{ number_format((float)$l->issued_qty, 2, ',', '.') }} {{ $l->uom }}</div>
                                @endforeach
                            </td>
                            <td class="rc-num rc-neg">
                                @foreach ($lines as $l)
                                    <div>{{ number_format((float)$l->gap_qty, 2, ',', '.') }} {{ $l->uom }}</div>
                                @endforeach
                            </td>
                            <td>
                                @if (Route::has('production.sewing.pickups.show'))
                                    <a class="rc-link" href="{{ route('production.sewing.pickups.show', $first->pickup_id) }}">Lihat →</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- 4. Sewing Supply Pending Cost --}}
    <div class="rc-section">
        <div class="rc-section-head">
            <div class="rc-section-title">
                🪡 Kelengkapan Jahit Pending GRN
                <span class="rc-badge {{ $pendingSupplies->count() > 0 ? 'warn' : 'ok' }}">
                    {{ $pendingSupplies->count() }}
                </span>
            </div>
            @if ($pendingSupplies->count() > 0)
                <span style="font-size:.75rem;color:#64748b">Input GRN agar cost kelengkapan masuk HPP</span>
            @endif
        </div>
        @if ($pendingSupplies->isEmpty())
            <div class="rc-empty"><span class="rc-empty-icon">✅</span>Semua kelengkapan jahit sudah ter-GRN</div>
        @else
            <table class="rc-table">
                <thead>
                    <tr>
                        <th>Ambil Jahit</th>
                        <th>Material</th>
                        <th class="rc-num rc-hide-sm">Qty Keluar</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pendingSupplies->groupBy('pickup_code') as $pickupCode => $lines)
                        @php $first = $lines->first(); @endphp
                        <tr>
                            <td>
                                <div class="rc-code">{{ $pickupCode }}</div>
                                <div class="rc-meta">{{ \Carbon\Carbon::parse($first->pickup_date)->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                @foreach ($lines as $l)
                                    <div class="rc-name" style="margin-bottom:.1rem">{{ $l->item_code }}</div>
                                @endforeach
                            </td>
                            <td class="rc-num rc-hide-sm">
                                @foreach ($lines as $l)
                                    <div>{{ number_format((float)$l->issued_qty, 2, ',', '.') }} {{ $l->uom }}</div>
                                @endforeach
                            </td>
                            <td>
                                @if (Route::has('production.sewing.pickups.show'))
                                    <a class="rc-link" href="{{ route('production.sewing.pickups.show', $first->pickup_id) }}">Lihat →</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>
@endsection
