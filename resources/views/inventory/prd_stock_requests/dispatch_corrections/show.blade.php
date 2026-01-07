@extends('layouts.app')

@section('title', 'PRD Dispatch Correction • ' . ($correction->code ?? '-'))

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .85rem .85rem 4.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(245, 158, 11, .12) 0,
                    rgba(251, 191, 36, .10) 26%,
                    #f9fafb 62%);
        }

        .card {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .28);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, .06),
                0 0 0 1px rgba(15, 23, 42, .03);
            padding: .85rem .95rem;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: flex-start;
            margin-bottom: .75rem;
        }

        .title {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 900;
            letter-spacing: -.01em;
        }

        .meta {
            font-size: .82rem;
            opacity: .8;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .actions {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-outline {
            border: 1px solid rgba(148, 163, 184, .45);
            background: transparent;
        }

        .badges {
            display: flex;
            gap: .4rem;
            flex-wrap: wrap;
            margin-top: .35rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .18rem .55rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 850;
            line-height: 1;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: currentColor;
            opacity: .75;
        }

        .badge.ok {
            background: rgba(16, 185, 129, .16);
            border-color: rgba(16, 185, 129, .35);
            color: rgba(4, 120, 87, 1);
        }

        .badge.warn {
            background: rgba(245, 158, 11, .18);
            border-color: rgba(245, 158, 11, .40);
            color: rgba(146, 64, 14, 1);
        }

        .badge.danger {
            background: rgba(239, 68, 68, .16);
            border-color: rgba(239, 68, 68, .40);
            color: rgba(153, 27, 27, 1);
        }

        .badge.muted {
            background: rgba(148, 163, 184, .16);
            border-color: rgba(148, 163, 184, .35);
            color: rgba(51, 65, 85, 1);
        }

        .table-wrap {
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 12px;
            overflow: auto;
            background: rgba(15, 23, 42, .01);
        }

        table.tbl {
            width: 100%;
            border-collapse: collapse;
            min-width: 820px;
        }

        .tbl th,
        .tbl td {
            padding: .55rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            font-size: .9rem;
            vertical-align: top;
        }

        .tbl thead th {
            position: sticky;
            top: 0;
            background: var(--card);
            font-size: .78rem;
            letter-spacing: .03em;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(148, 163, 184, .35);
        }

        .td-right {
            text-align: right;
            white-space: nowrap;
        }

        .td-center {
            text-align: center;
            white-space: nowrap;
        }

        .note {
            border: 1px solid rgba(148, 163, 184, .25);
            border-radius: 12px;
            padding: .6rem .7rem;
            background: rgba(148, 163, 184, .08);
            font-size: .85rem;
            white-space: pre-wrap;
        }

        .line {
            border-top: 1px dashed rgba(148, 163, 184, .35);
            margin: .7rem 0;
        }
    </style>
@endpush

@section('content')
    @php
        $refReq = $correction->stockRequest ?? null;

        // ✅ FIX: created_by_user_id
        $creatorName =
            $correction->createdBy->name ??
            ($correction->created_by_user_id ? 'User #' . $correction->created_by_user_id : '-');

        // ✅ total ringkas
        $totalPlus = (float) ($correction->lines?->where('qty_adjust', '>', 0)->sum('qty_adjust') ?? 0);
        $totalMinusAbs = (float) abs($correction->lines?->where('qty_adjust', '<', 0)->sum('qty_adjust') ?? 0);
    @endphp

    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="header-row">
            <div>
                <h1 class="title mono">{{ $correction->code ?? '-' }}</h1>
                <div class="meta">
                    PRD Dispatch Correction · {{ optional($correction->date)->format('d M Y') ?? '-' }}
                </div>

                <div class="badges">
                    <span class="badge warn">
                        <span class="dot"></span> Koreksi PRD → TRANSIT
                    </span>

                    <span class="badge muted">
                        <span class="dot"></span>
                        Ref: <span class="mono">{{ $refReq->code ?? '-' }}</span>
                    </span>

                    @if ($totalPlus > 0)
                        <span class="badge ok">
                            <span class="dot"></span> Tambah <b
                                class="mono">{{ number_format((int) round($totalPlus), 0, ',', '.') }}</b>
                        </span>
                    @endif

                    @if ($totalMinusAbs > 0)
                        <span class="badge danger">
                            <span class="dot"></span> Kurangi <b
                                class="mono">{{ number_format((int) round($totalMinusAbs), 0, ',', '.') }}</b>
                        </span>
                    @endif
                </div>
            </div>

            <div class="actions">
                @if ($refReq)
                    <a href="{{ route('prd.stock-requests.show', $refReq) }}" class="btn btn-outline">
                        ← Kembali ke Stock Request
                    </a>
                @else
                    <a href="{{ route('prd.stock-requests.index') }}" class="btn btn-outline">← List</a>
                @endif
            </div>
        </div>

        {{-- INFO --}}
        <div class="card">
            <div style="display:flex;gap:1.2rem;flex-wrap:wrap;align-items:flex-start">
                <div>
                    <div class="meta">Dibuat oleh</div>
                    <div style="font-weight:800">{{ $creatorName }}</div>
                </div>

                <div>
                    <div class="meta">Gudang</div>
                    <div class="mono">
                        {{ $correction->fromWarehouse->code ?? 'PRD' }}
                        →
                        {{ $correction->toWarehouse->code ?? 'WH-TRANSIT' }}
                    </div>
                </div>

                <div>
                    <div class="meta">Status</div>
                    <span class="badge ok">
                        <span class="dot"></span> Posted
                    </span>
                </div>
            </div>

            @if (!empty($correction->notes))
                <div class="line"></div>
                <div class="note">
                    <b>Catatan:</b><br>
                    {{ $correction->notes }}
                </div>
            @endif
        </div>

        {{-- ITEMS --}}
        <div class="card" style="margin-top:.85rem">
            <div style="display:flex;justify-content:space-between;align-items:baseline;gap:.6rem;flex-wrap:wrap">
                <div style="font-weight:900">Daftar Item Koreksi</div>
                <div class="meta">{{ $correction->lines?->count() ?? 0 }} item</div>
            </div>

            <div class="line"></div>

            <div class="table-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th style="width:40px">No</th>
                            <th>Item</th>
                            <th class="td-center" style="width:140px">Arah</th>
                            <th class="td-right" style="width:140px">Qty</th>
                            <th style="width:280px">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($correction->lines ?? [] as $i => $line)
                            @php
                                // ✅ FIX: kolom qty_adjust
                                $qtyRaw = (float) ($line->qty_adjust ?? 0);
                                $qtyInt = (int) round($qtyRaw);
                            @endphp
                            <tr>
                                <td class="td-center">{{ $i + 1 }}</td>

                                <td>
                                    <div class="mono" style="font-weight:800">
                                        {{ $line->item->code ?? '-' }}
                                    </div>
                                    <div class="meta">{{ $line->item->name ?? '' }}</div>
                                </td>

                                <td class="td-center">
                                    @if ($qtyInt > 0)
                                        <span class="badge ok"><span class="dot"></span> Tambah</span>
                                    @elseif ($qtyInt < 0)
                                        <span class="badge danger"><span class="dot"></span> Kurangi</span>
                                    @else
                                        <span class="badge muted"><span class="dot"></span> Nol</span>
                                    @endif
                                </td>

                                <td class="td-right mono" style="font-weight:900">
                                    {{ number_format(abs($qtyInt), 0, ',', '.') }}
                                </td>

                                <td>
                                    <div style="white-space:pre-wrap" class="meta">{{ $line->notes ?? '' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="meta" style="text-align:center;opacity:.75;padding:1.1rem">
                                    Tidak ada baris koreksi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MUTATION HISTORY (optional) --}}
        @if (!empty($movementHistory) && count($movementHistory))
            <div class="card" style="margin-top:.85rem">
                <div style="font-weight:900">Riwayat Mutasi</div>
                <div class="line"></div>

                <div class="table-wrap">
                    <table class="tbl" style="min-width: 860px;">
                        <thead>
                            <tr>
                                <th style="width:120px">Tanggal</th>
                                <th>Item</th>
                                <th class="td-right" style="width:120px">Qty</th>
                                <th style="width:140px">Gudang</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($movementHistory as $m)
                                @php
                                    $mQty = (float) ($m->qty_change ?? 0);
                                    $mInt = (int) round(abs($mQty));
                                @endphp
                                <tr>
                                    <td class="mono">
                                        {{ $m->date ? \Carbon\Carbon::parse($m->date)->format('d M Y') : '-' }}</td>
                                    <td>
                                        <div class="mono" style="font-weight:800">{{ $m->item->code ?? '-' }}</div>
                                        <div class="meta">{{ $m->item->name ?? '' }}</div>
                                    </td>
                                    <td class="td-right mono">{{ number_format($mInt, 0, ',', '.') }}</td>
                                    <td class="mono">{{ $m->warehouse->code ?? '-' }}</td>
                                    <td class="meta" style="white-space:pre-wrap">{{ $m->notes ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
@endsection
