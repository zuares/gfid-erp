{{-- resources/views/sales/shipments/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detail Shipment • ' . $shipment->code)

@push('head')
<style>
    .page-wrap{ max-width:1080px; margin-inline:auto; padding:16px; }
    .cardx{ background:var(--card); border:1px solid var(--line); border-radius:14px; overflow:hidden; }
    .cardx-hd{ padding:14px 16px; border-bottom:1px solid var(--line); }
    .cardx-bd{ padding:14px 16px; }
    .grid{ display:grid; gap:12px; }
    .grid-3{ grid-template-columns: repeat(3, minmax(0, 1fr)); }
    @media (max-width: 860px){ .grid-3{ grid-template-columns:1fr; } }

    .mono{ font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas; }
    .muted{ opacity:.75; }
    .tag{ border-radius:999px; padding:.18rem .65rem; font-size:.72rem; border:1px solid var(--line); background: rgba(148, 163, 184, .12); }
    .tag-ok{ background: rgba(34,197,94,.12); border-color: rgba(34,197,94,.25); }
    .tag-warn{ background: rgba(245,158,11,.12); border-color: rgba(245,158,11,.25); }
    .tag-info{ background: rgba(59,130,246,.10); border-color: rgba(59,130,246,.18); }

    .btnx{
        display:inline-flex; align-items:center; gap:8px;
        padding:.55rem .9rem; border-radius:12px;
        border:1px solid var(--line); background: rgba(148,163,184,.10);
        text-decoration:none; color:inherit; font-weight:700;
    }
    .btnx:hover{ background: rgba(148,163,184,.16); }
    .btnx-primary{ background: rgba(59,130,246,.14); border-color: rgba(59,130,246,.28); }
    .btnx-primary:hover{ background: rgba(59,130,246,.20); }

    .table-wrap{ overflow:auto; border:1px solid var(--line); border-radius:12px; }
    table{ width:100%; border-collapse:collapse; }
    th, td{ padding:10px 12px; border-bottom:1px solid var(--line); vertical-align:top; }
    th{ text-align:left; font-size:.85rem; position:sticky; top:0; background:var(--card); z-index:1; }
    tr:hover td{ background: rgba(148, 163, 184, .06); }
    td.r{ text-align:right; }

    .actions{ display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
</style>
@endpush

@section('content')
<div class="page-wrap">

    {{-- Header --}}
    <div class="cardx">
        <div class="cardx-hd" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px; flex-wrap:wrap;">
            <div>
                <div style="font-weight:800; font-size:1.1rem;">Shipment {{ $shipment->code }}</div>

                @php
                    $shipDateTxt = '-';
                    if (!empty($shipment->date)) {
                        try { $shipDateTxt = \Illuminate\Support\Carbon::parse($shipment->date)->format('Y-m-d'); }
                        catch (\Throwable $e) { $shipDateTxt = is_string($shipment->date) ? $shipment->date : '-'; }
                    }
                @endphp

                <div class="muted" style="margin-top:4px;">
                    Store: <span class="mono">{{ $shipment->store?->code ?? '-' }}</span> •
                    Tanggal: <span class="mono">{{ $shipDateTxt }}</span> •
                    Status: <span class="tag mono">{{ strtoupper($shipment->status ?? '-') }}</span>
                    @if(!empty($shipment->awb))
                        • AWB: <span class="tag mono">{{ $shipment->awb }}</span>
                    @endif
                </div>
            </div>

            <div style="text-align:right;">
                @if(!empty($shipment->cancelled_at))
                    <span class="tag tag-warn">CANCELLED</span>
                @elseif(!empty($shipment->posted_at))
                    <span class="tag tag-ok">POSTED</span>
                @else
                    <span class="tag tag-info">DRAFT</span>
                @endif

                <div class="muted" style="margin-top:6px; font-size:.85rem;">
                    Created by: {{ $shipment->creator?->name ?? '-' }}
                </div>

                <div class="actions" style="margin-top:10px;">
                    <a class="btnx" href="{{ route('sales.shipments.index') }}">← Back</a>

                    @if(($shipment->status ?? '') === 'draft' && empty($shipment->posted_at) && empty($shipment->cancelled_at))
                        {{-- ✅ Tombol lanjut input --}}
                        <a class="btnx btnx-primary" href="{{ route('sales.shipments.edit', $shipment) }}">
                            ➕ Lanjut Input / Scan
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="cardx-bd">
            <div class="grid grid-3">
                <div class="cardx" style="border-radius:12px;">
                    <div class="cardx-bd">
                        <div class="muted" style="font-size:.85rem;">Total Qty (scan)</div>
                        <div class="mono" style="font-size:1.4rem; font-weight:800;">{{ number_format($totalQty) }}</div>
                    </div>
                </div>
                <div class="cardx" style="border-radius:12px;">
                    <div class="cardx-bd">
                        <div class="muted" style="font-size:.85rem;">Total Lines</div>
                        <div class="mono" style="font-size:1.4rem; font-weight:800;">{{ number_format($totalLines) }}</div>
                    </div>
                </div>
                <div class="cardx" style="border-radius:12px;">
                    <div class="cardx-bd">
                        <div class="muted" style="font-size:.85rem;">Estimasi HPP</div>
                        <div class="mono" style="font-size:1.4rem; font-weight:800;">
                            {{ number_format($totalHpp, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Lines --}}
    <div class="cardx" style="margin-top:12px;">
        <div class="cardx-hd">
            <div style="font-weight:800;">Items in Shipment</div>
            <div class="muted" style="font-size:.85rem;">Berbasis qty_scanned.</div>
        </div>

        <div class="cardx-bd">
            @if($shipment->lines->isEmpty())
                <div class="muted">Belum ada item.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Kategori</th>
                                <th class="r">Qty</th>
                                <th class="r">Unit HPP</th>
                                <th class="r">Total HPP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($shipment->lines as $line)
                                <tr>
                                    <td>
                                        <div class="mono" style="font-weight:700;">{{ $line->item?->code ?? '-' }}</div>
                                        <div class="muted">{{ $line->item?->name ?? '-' }}</div>
                                    </td>
                                    <td class="muted">{{ $line->item?->category?->name ?? 'Tanpa Kategori' }}</td>
                                    <td class="mono r">{{ number_format((int)$line->qty_scanned) }}</td>
                                    <td class="mono r">{{ number_format((float)($line->unit_hpp ?? 0), 0, ',', '.') }}</td>
                                    <td class="mono r">{{ number_format((float)($line->total_hpp ?? 0), 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="r" style="font-weight:800;">TOTAL</td>
                                <td class="mono r" style="font-weight:800;">{{ number_format($totalQty) }}</td>
                                <td></td>
                                <td class="mono r" style="font-weight:800;">{{ number_format($totalHpp, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
