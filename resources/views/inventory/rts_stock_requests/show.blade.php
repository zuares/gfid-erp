@extends('layouts.app')

@section('title', 'Stock Request RTS • ' . $stockRequest->code)

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-strong: rgba(15, 118, 110, 1);
            --rts-soft: rgba(45, 212, 191, 0.12);
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .95rem .85rem 3rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.09) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 65%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .card-head {
            padding: .85rem 1.05rem .75rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        }

        .card-body {
            padding: .85rem 1.05rem 1.05rem;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-variant-numeric: tabular-nums;
        }

        /* soft-bold helper */
        .soft-strong {
            font-weight: 650;
        }

        .title-row {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: flex-start;
        }

        .back {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .82rem;
            text-decoration: none;
            color: rgba(100, 116, 139, 1);
            margin-bottom: .35rem;
        }

        .back:hover {
            color: rgba(30, 64, 175, 1);
            text-decoration: none;
        }

        .doc-code {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .12rem .6rem;
            background: rgba(15, 23, 42, 0.04);
            border: 1px solid rgba(148, 163, 184, 0.4);
            font-size: .82rem;
        }

        .dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: var(--rts-strong);
        }

        .meta {
            margin-top: .35rem;
            font-size: .82rem;
            color: rgba(100, 116, 139, 1);
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .14rem .65rem;
            font-size: .78rem;
            font-weight: 650;
            white-space: nowrap;
        }

        .badge-status.pending {
            background: rgba(59, 130, 246, 0.12);
            color: rgba(30, 64, 175, 1);
        }

        .badge-status.shipped {
            background: rgba(45, 212, 191, 0.14);
            color: rgba(15, 118, 110, 1);
        }

        .badge-status.partial {
            background: rgba(234, 179, 8, 0.14);
            color: rgba(133, 77, 14, 1);
        }

        .badge-status.completed {
            background: rgba(22, 163, 74, 0.14);
            color: rgba(22, 101, 52, 1);
        }

        .route {
            margin-top: .45rem;
            display: flex;
            gap: .35rem;
            flex-wrap: wrap;
            align-items: center;
            font-size: .82rem;
        }

        .route-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .12rem .55rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .55);
            background: rgba(15, 23, 42, 0.02);
            font-size: .78rem;
        }

        .sum-row {
            margin-top: .65rem;
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }

        .sum-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .14rem .55rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: rgba(15, 23, 42, 0.02);
            font-size: .78rem;
        }

        .sum-pill--out {
            border-color: var(--rts-strong);
            background: var(--rts-soft);
            color: var(--rts-strong);
        }

        .cta {
            margin-top: .75rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            background: linear-gradient(135deg, rgba(45, 212, 191, 0.14), rgba(15, 23, 42, 0.02));
            padding: .7rem .75rem;
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .cta .text {
            min-width: 240px;
        }

        .cta .main {
            font-size: .86rem;
            font-weight: 650;
            color: rgba(15, 23, 42, .92);
        }

        .cta .sub {
            margin-top: .12rem;
            font-size: .8rem;
            color: rgba(100, 116, 139, 1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            padding: .42rem .95rem;
            border-radius: 999px;
            font-size: .82rem;
            font-weight: 650;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--rts-strong);
            color: #ecfeff;
        }

        .btn-outline {
            background: transparent;
            border-color: rgba(148, 163, 184, 0.85);
            color: rgba(15, 23, 42, .9);
        }

        .btn-danger {
            background: rgba(220, 38, 38, 1);
            color: #fff;
        }

        .notes {
            margin-top: .75rem;
            padding: .65rem .75rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            background: rgba(15, 23, 42, 0.02);
            font-size: .82rem;
            color: rgba(15, 23, 42, .9);
            white-space: pre-line;
        }

        /* direct pickup */
        .direct {
            margin-top: .85rem;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            overflow: hidden;
            background: color-mix(in srgb, var(--card) 88%, rgba(59, 130, 246, 0.06));
        }

        .direct-head {
            padding: .7rem .8rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .6rem;
            flex-wrap: wrap;
        }

        .direct-title {
            font-size: .78rem;
            font-weight: 650;
            letter-spacing: .10em;
            text-transform: uppercase;
            color: rgba(100, 116, 139, 1);
        }

        .direct-body {
            padding: .75rem .8rem 1rem;
            display: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1.8fr .9fr;
            gap: .6rem;
            align-items: center;
            padding: .55rem .6rem;
            border: 1px solid rgba(148, 163, 184, 0.20);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.55);
            margin-bottom: .55rem;
        }

        body[data-theme="dark"] .form-row {
            background: rgba(2, 6, 23, 0.35);
        }

        .item-name {
            font-size: .84rem;
            font-weight: 650;
            color: rgba(15, 23, 42, .92);
        }

        body[data-theme="dark"] .item-name {
            color: rgba(226, 232, 240, 1);
        }

        .item-meta {
            margin-top: .12rem;
            font-size: .76rem;
            color: rgba(100, 116, 139, 1);
        }

        .qty-input {
            width: 100%;
            padding: .42rem .55rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.6);
            background: var(--card);
            font-size: .84rem;
            text-align: right;
        }

        .textarea {
            width: 100%;
            min-height: 70px;
            border-radius: 12px;
            padding: .55rem .65rem;
            border: 1px solid rgba(148, 163, 184, 0.6);
            background: var(--card);
            font-size: .82rem;
        }

        /* detail */
        .section-title {
            margin-top: .95rem;
            font-size: .78rem;
            font-weight: 650;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: rgba(100, 116, 139, 1);
            display: flex;
            justify-content: space-between;
            gap: .6rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .table-wrap {
            margin-top: .55rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
        }

        .table thead {
            background: rgba(15, 23, 42, 0.05);
        }

        .table th,
        .table td {
            padding: .48rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.20);
            vertical-align: top;
        }

        .table th {
            text-align: left;
            font-weight: 650;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
        }

        .mobile-list {
            display: none;
        }

        .item-card {
            background: var(--card);
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            padding: .7rem .75rem;
            margin-top: .55rem;
        }

        .pills {
            margin-top: .45rem;
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .12rem .52rem;
            border-radius: 999px;
            font-size: .76rem;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: rgba(15, 23, 42, 0.02);
            color: rgba(15, 23, 42, .9);
        }

        .pill--ok {
            border-color: rgba(34, 197, 94, 0.55);
            background: rgba(34, 197, 94, 0.10);
            color: rgba(22, 101, 52, 1);
        }

        .pill--warn {
            border-color: rgba(234, 179, 8, 0.55);
            background: rgba(234, 179, 8, 0.12);
            color: rgba(133, 77, 14, 1);
        }

        .pill--info {
            border-color: rgba(59, 130, 246, 0.55);
            background: rgba(59, 130, 246, 0.10);
            color: rgba(30, 64, 175, 1);
        }

        @media (max-width: 768px) {
            .page-wrap {
                padding-inline: .65rem;
            }

            .card-head,
            .card-body {
                padding-inline: .85rem;
            }

            .cta {
                align-items: stretch;
            }

            .btn {
                width: 100%;
            }

            .table-wrap {
                display: none;
            }

            .mobile-list {
                display: block;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    @php
        // totals
        $status = $stockRequest->status;

        $totalRequested = (float) $stockRequest->lines->sum(fn($l) => (float) ($l->qty_request ?? 0));
        $totalDispatched = (float) $stockRequest->lines->sum(fn($l) => (float) ($l->qty_dispatched ?? 0));
        $totalReceived = (float) $stockRequest->lines->sum(fn($l) => (float) ($l->qty_received ?? 0));
        $totalPicked = (float) $stockRequest->lines->sum(fn($l) => (float) ($l->qty_picked ?? 0));

        $remainingToReceive = max($totalDispatched - $totalReceived, 0); // masih di Transit
        $remainingOverall = max($totalRequested - $totalReceived - $totalPicked, 0); // sisa akhir

        $statusLabel = match ($status) {
            'submitted' => 'Menunggu PRD',
            'shipped' => 'Barang di Transit',
            'partial' => 'Sebagian selesai',
            'completed' => 'Selesai',
            default => ucfirst($status ?? 'Draft'),
        };

        $badgeClass = match ($status) {
            'completed' => 'completed',
            'partial' => 'partial',
            'shipped' => 'shipped',
            default => 'pending',
        };

        $canOpenConfirm = $remainingToReceive > 0 && $status !== 'completed';
        $canDirectPickup = $status !== 'completed';

        // CTA
        if ($status === 'completed') {
            $ctaMain = 'Dokumen sudah selesai.';
            $ctaSub = 'Semua barang sudah terpenuhi (diterima + diambil langsung).';
        } elseif ($remainingToReceive > 0) {
            $ctaMain = 'Ada barang di Transit.';
            $ctaSub = 'Klik “Terima dari Transit” lalu isi jumlah yang benar-benar diterima.';
        } elseif ($remainingOverall > 0) {
            $ctaMain = 'Menunggu PRD kirim lagi / bisa ambil langsung.';
            $ctaSub = 'Masih ada sisa yang belum terpenuhi.';
        } else {
            $ctaMain = 'Cek data.';
            $ctaSub = 'Sisa sudah 0, tapi status belum “Selesai”.';
        }

        // over-request (optional)
        $hasOver = !empty($summary['has_over_request']);
        $overLinesCount = $summary['over_lines_count'] ?? 0;
        $overQtyTotal = $summary['over_qty_total'] ?? 0;
    @endphp

    <div class="page-wrap">
        <a href="{{ route('rts.stock-requests.index') }}" class="back">← Kembali</a>

        <div class="card">
            <div class="card-head">
                <div class="title-row">
                    <div>
                        <div class="doc-code">
                            <span class="dot"></span>
                            <span class="mono soft-strong">{{ $stockRequest->code }}</span>
                        </div>

                        <div class="meta">
                            <span class="mono">{{ $stockRequest->date?->format('d M Y') ?? '-' }}</span>
                            <span style="opacity:.7;">•</span>
                            <span>{{ $stockRequest->requestedBy?->name ?? '—' }}</span>
                            <span style="opacity:.7;">•</span>
                            <span class="mono">{{ $stockRequest->created_at?->format('H:i') ?? '—' }}</span>
                        </div>

                        <div class="route">
                            <span class="route-pill">
                                <span class="mono soft-strong">{{ $stockRequest->sourceWarehouse?->code ?? '-' }}</span>
                                <span>{{ $stockRequest->sourceWarehouse?->name ?? '-' }}</span>
                            </span>
                            <span style="opacity:.7;">→</span>
                            <span class="route-pill">
                                <span class="mono soft-strong">TRANSIT</span>
                                <span>Transit</span>
                            </span>
                            <span style="opacity:.7;">→</span>
                            <span class="route-pill">
                                <span
                                    class="mono soft-strong">{{ $stockRequest->destinationWarehouse?->code ?? '-' }}</span>
                                <span>{{ $stockRequest->destinationWarehouse?->name ?? '-' }}</span>
                            </span>
                        </div>

                        <div class="sum-row">
                            <span class="sum-pill">Diminta <span
                                    class="mono soft-strong">{{ (int) $totalRequested }}</span></span>
                            <span class="sum-pill">Dikirim PRD <span
                                    class="mono soft-strong">{{ (int) $totalDispatched }}</span></span>
                            <span class="sum-pill">Diterima RTS <span
                                    class="mono soft-strong">{{ (int) $totalReceived }}</span></span>
                            <span class="sum-pill">Diambil langsung <span
                                    class="mono soft-strong">{{ (int) $totalPicked }}</span></span>
                            <span class="sum-pill sum-pill--out">Sisa <span
                                    class="mono soft-strong">{{ (int) $remainingOverall }}</span></span>
                        </div>
                    </div>

                    <div>
                        <span class="badge-status {{ $badgeClass }}">{{ $statusLabel }}</span>
                    </div>
                </div>

                <div class="cta">
                    <div class="text">
                        <div class="main">{{ $ctaMain }}</div>
                        <div class="sub">{{ $ctaSub }}</div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap" style="display:flex;">
                        @if ($canOpenConfirm)
                            <a href="{{ route('rts.stock-requests.confirm', $stockRequest) }}" class="btn btn-primary">
                                ⚡ Terima dari Transit
                            </a>
                        @endif

                        @if ($status !== 'completed')
                            <a href="{{ route('prd.stock-requests.edit', $stockRequest) }}" class="btn btn-outline">
                                👀 Lihat proses PRD
                            </a>
                        @endif
                    </div>
                </div>

                @if ($hasOver)
                    <div style="margin-top:.55rem; font-size:.8rem; color: rgba(124,45,18,1);">
                        ⚠️ Permintaan melebihi stok PRD (snapshot): {{ $overLinesCount }} baris · selisih
                        {{ $overQtyTotal }} pcs
                    </div>
                @endif
            </div>

            <div class="card-body">
                @if ($stockRequest->notes)
                    <div class="notes">{{ $stockRequest->notes }}</div>
                @endif

                {{-- DIRECT PICKUP --}}
                @if ($canDirectPickup)
                    <div class="direct">
                        <div class="direct-head">
                            <div>
                                <div class="direct-title">Ambil langsung (PRD → RTS)</div>
                            </div>
                            <button type="button" class="btn btn-outline" id="btnToggleDirect">
                                🧾 Isi ambil langsung
                            </button>
                        </div>

                        <div class="direct-body" id="directPanel">
                            <form method="POST" action="{{ route('rts.stock-requests.direct-pickup', $stockRequest) }}">
                                @csrf

                                @foreach ($stockRequest->lines as $line)
                                    @php
                                        $req = (float) ($line->qty_request ?? 0);
                                        $rec = (float) ($line->qty_received ?? 0);
                                        $pick = (float) ($line->qty_picked ?? 0);
                                        $remaining = max($req - $rec - $pick, 0);
                                    @endphp

                                    <div class="form-row">
                                        <div>
                                            <div class="item-name">
                                                {{ $line->item?->code ?? '—' }} — {{ $line->item?->name ?? '—' }}
                                            </div>
                                            <div class="item-meta">
                                                Maksimum bisa diambil: <span
                                                    class="mono soft-strong">{{ (int) $remaining }}</span> pcs
                                            </div>
                                            @error("lines.$line->id.qty_picked")
                                                <div class="text-danger" style="font-size:.78rem; margin-top:.15rem;">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>

                                        <div>
                                            <input class="qty-input mono" type="number"
                                                name="lines[{{ $line->id }}][qty_picked]"
                                                value="{{ old("lines.$line->id.qty_picked") }}" min="0"
                                                step="0.001" placeholder="0">
                                        </div>
                                    </div>
                                @endforeach

                                <div style="margin-top:.45rem;">
                                    <textarea class="textarea" name="notes" placeholder="Catatan (opsional)">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="text-danger" style="font-size:.78rem; margin-top:.15rem;">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                @error('stock')
                                    <div class="text-danger" style="font-size:.8rem; margin-top:.25rem;">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div style="margin-top:.6rem; display:flex; gap:.5rem; flex-wrap:wrap;">
                                    <button type="submit" class="btn btn-danger"
                                        onclick="return confirm('Proses ambil langsung sekarang? Stok akan pindah PRD → RTS.');">
                                        ✅ Simpan ambil langsung
                                    </button>
                                    <button type="button" class="btn btn-outline" id="btnCloseDirect">
                                        ✖️ Tutup
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- DETAIL ITEMS --}}
                <div class="section-title">
                    <span>Detail barang</span>
                    <span>Sisa = Diminta − (Diterima + Diambil langsung)</span>
                </div>

                {{-- DESKTOP TABLE --}}
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:32px;">#</th>
                                <th>Barang</th>
                                <th style="width:12%;">Diminta</th>
                                <th style="width:13%;">Dikirim PRD</th>
                                <th style="width:12%;">Diterima RTS</th>
                                <th style="width:14%;">Diambil langsung</th>
                                <th style="width:10%;">Sisa</th>
                                <th style="width:12%;">Stok PRD (saat request)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stockRequest->lines as $i => $line)
                                @php
                                    $r = (float) ($line->qty_request ?? 0);
                                    $d = (float) ($line->qty_dispatched ?? 0);
                                    $rc = (float) ($line->qty_received ?? 0);
                                    $p = (float) ($line->qty_picked ?? 0);
                                    $o = max($r - $rc - $p, 0);

                                    $snap =
                                        $line->stock_snapshot_at_request !== null
                                            ? (float) $line->stock_snapshot_at_request
                                            : null;
                                @endphp
                                <tr>
                                    <td class="mono">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="soft-strong" style="font-size:.84rem;">
                                            {{ $line->item?->code ?? '—' }} — {{ $line->item?->name ?? '—' }}
                                        </div>
                                        @if ($line->notes)
                                            <div style="margin-top:.15rem; font-size:.78rem; color: rgba(100,116,139,1);">
                                                {{ $line->notes }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="mono">{{ (int) $r }}</td>
                                    <td class="mono">{{ (int) $d }}</td>
                                    <td class="mono">{{ (int) $rc }}</td>
                                    <td class="mono">{{ (int) $p }}</td>
                                    <td class="mono">
                                        <span class="{{ $o > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ (int) $o }}
                                        </span>
                                    </td>
                                    <td class="mono">{{ $snap !== null ? (int) $snap : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE LIST --}}
                <div class="mobile-list">
                    @foreach ($stockRequest->lines as $line)
                        @php
                            $r = (float) ($line->qty_request ?? 0);
                            $d = (float) ($line->qty_dispatched ?? 0);
                            $rc = (float) ($line->qty_received ?? 0);
                            $p = (float) ($line->qty_picked ?? 0);
                            $o = max($r - $rc - $p, 0);
                            $snap =
                                $line->stock_snapshot_at_request !== null
                                    ? (float) $line->stock_snapshot_at_request
                                    : null;
                        @endphp

                        <div class="item-card">
                            <div class="soft-strong" style="font-size:.86rem;">
                                {{ $line->item?->code ?? '—' }} — {{ $line->item?->name ?? '—' }}
                            </div>

                            <div class="pills">
                                <span class="pill">Diminta <span
                                        class="mono soft-strong">{{ (int) $r }}</span></span>
                                <span class="pill pill--info">Dikirim <span
                                        class="mono soft-strong">{{ (int) $d }}</span></span>
                                <span class="pill pill--ok">Diterima <span
                                        class="mono soft-strong">{{ (int) $rc }}</span></span>
                                <span class="pill pill--info">Ambil <span
                                        class="mono soft-strong">{{ (int) $p }}</span></span>
                                <span class="pill pill--warn">Sisa <span
                                        class="mono soft-strong">{{ (int) $o }}</span></span>
                                <span class="pill">Stok (snap) <span
                                        class="mono soft-strong">{{ $snap !== null ? (int) $snap : '—' }}</span></span>
                            </div>

                            @if ($line->notes)
                                <div style="margin-top:.35rem; font-size:.78rem; color: rgba(100,116,139,1);">
                                    {{ $line->notes }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div style="margin-top:.75rem; font-size:.78rem; color: rgba(100,116,139,1);">
                    Terakhir update: <span
                        class="mono">{{ $stockRequest->updated_at?->format('d M Y H:i') ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const btn = document.getElementById('btnToggleDirect');
            const panel = document.getElementById('directPanel');
            const close = document.getElementById('btnCloseDirect');

            if (!btn || !panel) return;

            const open = () => {
                panel.style.display = 'block';
                const first = panel.querySelector('input[type="number"]');
                if (first) first.focus();
            };
            const hide = () => panel.style.display = 'none';

            btn.addEventListener('click', function() {
                (panel.style.display === 'none' || panel.style.display === '') ? open(): hide();
            });

            if (close) close.addEventListener('click', hide);
        })();
    </script>
@endpush
