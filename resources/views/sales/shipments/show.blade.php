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

    .mono{ font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"; }
    .muted{ opacity:.75; }
    .tag{ border-radius:999px; padding:.18rem .65rem; font-size:.72rem; border:1px solid var(--line); background: rgba(148, 163, 184, .12); white-space:nowrap; }
    .tag-ok{ background: rgba(34,197,94,.12); border-color: rgba(34,197,94,.25); }
    .tag-warn{ background: rgba(245,158,11,.12); border-color: rgba(245,158,11,.25); }
    .tag-info{ background: rgba(59,130,246,.10); border-color: rgba(59,130,246,.18); }

    .table-wrap{ overflow:auto; border:1px solid var(--line); border-radius:12px; }
    table{ width:100%; border-collapse:collapse; }
    th, td{ padding:10px 12px; border-bottom:1px solid var(--line); vertical-align:top; }
    th{ text-align:left; font-size:.85rem; position:sticky; top:0; background:var(--card); z-index:1; }
    tr:hover td{ background: rgba(148, 163, 184, .06); }
    td.r{ text-align:right; }
</style>
@endpush

@section('content')
<div class="page-wrap">

    {{-- Header --}}
    <div class="cardx">
        <div class="cardx-hd" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
            <div>
                <div style="font-weight:800; font-size:1.1rem;">Shipment {{ $shipment->code }}</div>
                <div class="muted" style="margin-top:4px;">
                    Store: <span class="mono">{{ $shipment->store?->code ?? '-' }}</span> •
                    Tanggal: <span class="mono">{{ optional($shipment->date)->format('Y-m-d') ?? '-' }}</span> •
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

            @if(!empty($shipment->notes))
                <div style="margin-top:12px;" class="muted">
                    Notes: {{ $shipment->notes }}
                </div>
            @endif
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
                                <th class="sticky">Item</th>
                                <th class="sticky">Kategori</th>
                                <th class="sticky r">Qty</th>
                                <th class="sticky r">Unit HPP</th>
                                <th class="sticky r">Total HPP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($shipment->lines as $line)
                                <tr>
                                    <td>
                                        <div class="mono" style="font-weight:700;">
                                            {{ $line->item?->code ?? '-' }}
                                        </div>
                                        <div class="muted">{{ $line->item?->name ?? '-' }}</div>
                                    </td>
                                    <td class="muted">
                                        {{ $line->item?->category?->name ?? 'Tanpa Kategori' }}
                                    </td>
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

    {{-- Summary per category --}}
    <div class="cardx" style="margin-top:12px;">
        <div class="cardx-hd">
            <div style="font-weight:800;">Summary per Category</div>
        </div>
        <div class="cardx-bd">
            @if(($summaryPerCategory ?? collect())->isEmpty())
                <div class="muted">Tidak ada data kategori.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th class="sticky">Kategori</th>
                                <th class="sticky r">Lines</th>
                                <th class="sticky r">Qty</th>
                                <th class="sticky r">HPP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($summaryPerCategory as $row)
                                <tr>
                                    <td>{{ $row['category_name'] }}</td>
                                    <td class="mono r">{{ number_format((int)$row['total_lines']) }}</td>
                                    <td class="mono r">{{ number_format((int)$row['total_qty']) }}</td>
                                    <td class="mono r">{{ number_format((float)$row['total_hpp'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Marketplace Packets --}}
    <div class="cardx" style="margin-top:12px;">
        <div class="cardx-hd" style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
            <div>
                <div style="font-weight:800;">Marketplace Packets in this Batch</div>
                <div class="muted" style="font-size:.85rem;">
                    Hasil rekonsiliasi MP shipments yang masuk ke batch ini.
                </div>
            </div>

            @php
                $deltaLabel = $deltaQty === 0 ? 'Match' : ($deltaQty > 0 ? 'Batch > MP' : 'MP > Batch');
            @endphp

            <div style="text-align:right;">
                <div class="mono" style="font-weight:800;">
                    MP: {{ number_format($mpTotalQty) }} / Batch: {{ number_format($batchQty) }}
                </div>
                <div style="margin-top:6px;">
                    @if($deltaQty === 0)
                        <span class="tag tag-ok">✅ {{ $deltaLabel }} (Δ 0)</span>
                    @else
                        <span class="tag tag-warn">⚠️ {{ $deltaLabel }} (Δ {{ $deltaQty }})</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="cardx-bd">
            @if(($mpPackets ?? collect())->isEmpty())
                <div class="muted">Belum ada MP packets yang ter-link ke shipment ini.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th class="sticky">MP Shipment</th>
                                <th class="sticky">Tracking</th>
                                <th class="sticky">Order</th>
                                <th class="sticky r">Qty</th>
                                <th class="sticky">Confidence</th>
                                <th class="sticky">Key</th>
                                <th class="sticky">Shipped</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mpPackets as $rec)
                                @php
                                    $mp = $rec->mpShipment;
                                    $qty = (int) ($mp->total_qty ?? 0);
                                    $conf = (int) ($rec->match_confidence ?? 0);

                                    $confClass = $conf >= 90 ? 'tag-ok' : ($conf >= 80 ? 'tag-info' : 'tag-warn');
                                    $shipAt = $mp?->shipped_at ?? $mp?->order_created_at;
                                @endphp
                                <tr>
                                    <td class="mono">#{{ $rec->mp_shipment_id }}</td>
                                    <td class="mono">{{ $mp->tracking_no ?? '-' }}</td>
                                    <td class="mono">{{ $mp->platform_order_id ?? '-' }}</td>
                                    <td class="mono r">{{ number_format($qty) }}</td>
                                    <td>
                                        <span class="tag mono {{ $confClass }}">{{ $conf }}</span>
                                    </td>
                                    <td class="mono">{{ $rec->match_key ?? '-' }}</td>
                                    <td class="mono">{{ $shipAt ? $shipAt->format('Y-m-d H:i') : '-' }}</td>
                                </tr>

                                {{-- OPTIONAL: show first few MP item lines --}}
                                @if($mp && $mp->relationLoaded('items') && $mp->items->isNotEmpty())
                                    <tr>
                                        <td colspan="7" style="background: rgba(148,163,184,.06);">
                                            <div class="muted" style="font-size:.85rem;">
                                                @foreach($mp->items->take(8) as $it)
                                                    <span class="tag mono">
                                                        {{ $it->sku_code ?? '-' }} × {{ (int)($it->qty ?? 0) }}
                                                    </span>
                                                @endforeach
                                                @if($mp->items->count() > 8)
                                                    <span class="muted">+{{ $mp->items->count() - 8 }} lagi</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="r" style="font-weight:800;">TOTAL MP Qty</td>
                                <td class="mono r" style="font-weight:800;">{{ number_format($mpTotalQty) }}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
