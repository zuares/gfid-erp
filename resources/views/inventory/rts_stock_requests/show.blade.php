{{-- resources/views/inventory/rts_stock_requests/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Stock Request RTS • ' . $stockRequest->code)

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-main-strong: rgba(15, 118, 110, 1);
            --rts-main-soft: rgba(45, 212, 191, 0.14);
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: 1rem .9rem 3rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.09) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 65%);
        }

        .card-main {
            background: var(--card);
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .card-header {
            padding: .9rem 1.2rem .65rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.20);
        }

        .card-body {
            padding: .85rem 1.2rem 1.2rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .8rem;
            color: rgba(100, 116, 139, 1);
            text-decoration: none;
            margin-bottom: .4rem;
        }

        .back-link:hover {
            color: rgba(30, 64, 175, 1);
        }

        .page-title {
            font-size: .95rem;
            font-weight: 600;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: rgba(100, 116, 139, 1);
        }

        .doc-main-line {
            font-size: .9rem;
            color: rgba(15, 23, 42, .92);
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
            font-variant-numeric: tabular-nums;
        }

        .muted {
            font-size: .78rem;
            color: rgba(148, 163, 184, 1);
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border-radius: 999px;
            padding: .18rem .7rem;
            font-size: .78rem;
            font-weight: 500;
        }

        .badge-status span.dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
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
            background: rgba(234, 179, 8, 0.12);
            color: rgba(133, 77, 14, 1);
        }

        .badge-status.completed {
            background: rgba(22, 163, 74, 0.12);
            color: rgba(22, 101, 52, 1);
        }

        .badge-warehouse {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .16rem .55rem;
            border-radius: 999px;
            font-size: .75rem;
            border: 1px solid rgba(148, 163, 184, 0.55);
            background: color-mix(in srgb, var(--card) 82%, var(--rts-main-soft));
        }

        .badge-warehouse span.code {
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .summary-row {
            margin-top: .55rem;
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
        }

        .summary-pill {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .16rem .55rem;
            border-radius: 999px;
            font-size: .75rem;
            border: 1px solid rgba(148, 163, 184, 0.5);
            background: rgba(15, 23, 42, 0.02);
        }

        .summary-pill--sisa {
            border-color: var(--rts-main-strong);
            background: var(--rts-main-soft);
            color: var(--rts-main-strong);
        }

        /* CTA STRIP */
        .cta-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            padding: .65rem .75rem;
            border-radius: 10px;
            margin-top: .75rem;
            border: 1px solid rgba(148, 163, 184, 0.32);
            background:
                linear-gradient(135deg,
                    rgba(45, 212, 191, 0.16),
                    rgba(15, 23, 42, 0.03));
        }

        .cta-text-main {
            font-size: .8rem;
            font-weight: 600;
            color: rgba(15, 23, 42, .92);
        }

        .cta-text-sub {
            font-size: .75rem;
            color: rgba(100, 116, 139, 1);
        }

        .cta-text-sub span.step {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            border-radius: 999px;
            font-size: .68rem;
            background: rgba(15, 23, 42, 0.06);
            margin-right: .15rem;
        }

        .btn-confirm-rts {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            padding: .42rem .9rem;
            border-radius: 999px;
            font-size: .8rem;
            text-decoration: none;
            border: none;
            background: var(--rts-main-strong);
            color: #ecfeff;
            font-weight: 600;
            white-space: nowrap;
        }

        .btn-confirm-rts:hover {
            filter: brightness(0.97);
            color: #f0fdfa;
            text-decoration: none;
        }

        .btn-edit-today {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .32rem .8rem;
            border-radius: 999px;
            font-size: .78rem;
            text-decoration: none;
            border: 1px solid rgba(148, 163, 184, 0.8);
            background: rgba(15, 23, 42, 0.01);
            color: rgba(30, 64, 175, 1);
            font-weight: 500;
        }

        .btn-edit-today:hover {
            background: rgba(15, 23, 42, 0.03);
            text-decoration: none;
        }

        .btn-secondary-rts {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            padding: .42rem .9rem;
            border-radius: 999px;
            font-size: .8rem;
            text-decoration: none;
            border: 1px solid rgba(148, 163, 184, 0.8);
            background: rgba(15, 23, 42, 0.01);
            color: rgba(15, 23, 42, .9);
            font-weight: 600;
            white-space: nowrap;
        }

        .btn-secondary-rts:hover {
            background: rgba(15, 23, 42, 0.03);
            text-decoration: none;
        }

        .notes-block {
            margin-bottom: .9rem;
        }

        .notes-label {
            font-size: .74rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .14em;
            color: rgba(148, 163, 184, 1);
            margin-bottom: .25rem;
        }

        .table-wrap {
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
        }

        .table thead {
            background: color-mix(in srgb, var(--card) 80%, rgba(15, 23, 42, 0.06));
        }

        .table th,
        .table td {
            padding: .45rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
            vertical-align: top;
        }

        .table th {
            text-align: left;
            font-weight: 600;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
        }

        .badge-over {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            border-radius: 999px;
            padding: .15rem .55rem;
            font-size: .75rem;
            background: rgba(248, 250, 252, 0.9);
            color: rgba(124, 45, 18, 1);
            border: 1px dashed rgba(248, 113, 113, 0.7);
            margin-top: .22rem;
        }

        .badge-over-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: rgba(248, 113, 113, 1);
        }

        .line-notes {
            font-size: .75rem;
            color: rgba(100, 116, 139, 1);
            margin-top: .2rem;
        }

        .footer-row {
            margin-top: .7rem;
            font-size: .74rem;
            color: rgba(148, 163, 184, 1);
        }

        .footer-row .mono {
            font-size: .74rem;
        }

        /* helpers layout */
        .d-flex {
            display: flex;
        }

        .justify-between {
            justify-content: space-between;
        }

        .align-start {
            align-items: flex-start;
        }

        .align-center {
            align-items: center;
        }

        .flex-col {
            flex-direction: column;
        }

        .gap-1 {
            gap: .25rem;
        }

        .gap-2 {
            gap: .5rem;
        }

        .flex-wrap {
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .page-wrap {
                padding-inline: .7rem;
            }

            .card-header {
                padding-inline: .9rem;
            }

            .card-body {
                padding-inline: .9rem;
            }

            .header-layout {
                flex-direction: column;
                gap: .7rem;
            }

            .cta-strip {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-confirm-rts,
            .btn-secondary-rts {
                width: 100%;
            }

            .btn-edit-today {
                width: 100%;
                justify-content: center;
            }

            .table-wrap {
                margin-top: .7rem;
                border-radius: 10px;
                overflow-x: auto;
            }

            .table {
                min-width: 820px;
            }
        }
    </style>
@endpush

@section('content')
    @php
        /**
         * ====== UPGRADE LOGIC STATUS & CTA ======
         * Flow baru:
         * - PRD dispatch: PRD → TRANSIT => qty_dispatched naik
         * - RTS receive: TRANSIT → RTS => qty_received naik
         *
         * Status yang mungkin: submitted | shipped | partial | completed
         */

        $status = $stockRequest->status;

        $totalRequested = (float) $stockRequest->lines->sum(fn($l) => (float) ($l->qty_request ?? 0));
        $totalDispatched = (float) $stockRequest->lines->sum(fn($l) => (float) ($l->qty_dispatched ?? 0));
        $totalReceived = (float) $stockRequest->lines->sum(fn($l) => (float) ($l->qty_received ?? 0));

        $remainingToDispatch = max($totalRequested - $totalDispatched, 0); // sisa yang PRD belum kirim ke Transit
        $remainingToReceive = max($totalDispatched - $totalReceived, 0); // sisa yang masih ada di Transit (belum diterima RTS)
        $remainingOverall = max($totalRequested - $totalReceived, 0); // sisa menuju selesai (berdasarkan received)

        $hasDispatched = $totalDispatched > 0;
        $hasReceived = $totalReceived > 0;

        $canOpenConfirm = $remainingToReceive > 0 && $status !== 'completed';

        // Label status + badge class
        $statusLabel = match ($status) {
            'submitted' => 'Menunggu PRD kirim',
            'shipped' => 'Barang di Transit (siap diterima RTS)',
            'partial' => 'Sebagian sudah diterima RTS',
            'completed' => 'Selesai',
            default => ucfirst($status ?? 'Draft'),
        };

        $badgeClass = match ($status) {
            'completed' => 'completed',
            'partial' => 'partial',
            'shipped' => 'shipped',
            default => 'pending', // submitted/draft
        };

        $dotColor = match ($status) {
            'completed' => 'rgba(22,163,74,1)',
            'partial' => 'rgba(234,179,8,1)',
            'shipped' => 'rgba(15,118,110,1)',
            default => 'rgba(59,130,246,1)',
        };

        // CTA text (agar tidak selalu "menunggu PRD")
        $ctaMain = '';
        $ctaSub = '';
        $ctaActionLabel = null;

        if ($status === 'completed') {
            $ctaMain = 'Dokumen sudah selesai.';
            $ctaSub = 'Semua qty sudah diterima RTS. Dokumen terkunci.';
        } elseif ($remainingToReceive > 0) {
            // Ada barang di Transit → CTA utama: terima fisik
            $ctaMain = 'Barang sudah ada di Transit. RTS bisa terima sekarang.';
            $ctaSub = 'Input qty yang benar-benar diterima. Sistem akan mutasi stok TRANSIT → RTS.';
            $ctaActionLabel = '⚡ Buka form penerimaan RTS';
        } elseif ($hasDispatched && $remainingToReceive <= 0 && $remainingOverall > 0) {
            // Sudah pernah dispatch tapi saat ini tidak ada sisa di Transit (misal sudah diterima semua yang dikirim),
            // tapi total request belum terpenuhi → tunggu dispatch berikutnya dari PRD
            $ctaMain = 'Menunggu pengiriman berikutnya dari PRD.';
            $ctaSub = 'PRD perlu kirim tambahan (PRD → Transit). Setelah itu RTS bisa terima di sini.';
        } else {
            // Belum ada dispatch sama sekali
            $ctaMain = 'Menunggu PRD mengirim ke Transit.';
            $ctaSub = 'Setelah PRD dispatch (PRD → Transit), RTS akan menerima dari Transit di halaman ini.';
        }

        // Over-request (pakai summary yang dikirim controller kalau ada)
        $hasOver = !empty($summary['has_over_request']);
        $overLinesCount = $summary['over_lines_count'] ?? 0;
        $overQtyTotal = $summary['over_qty_total'] ?? 0;
    @endphp

    <div class="page-wrap">
        <a href="{{ route('rts.stock-requests.index') }}#today" class="back-link">
            ← Kembali ke daftar RTS
        </a>

        <div class="card-main">
            {{-- HEADER --}}
            <div class="card-header">
                <div class="d-flex justify-between align-start header-layout gap-2">
                    {{-- KIRI: info dokumen --}}
                    <div>
                        <div class="page-title">
                            STOCK REQUEST RTS ▸ PRD
                        </div>

                        <div class="doc-main-line mt-1">
                            <span class="mono fw-semibold">{{ $stockRequest->code }}</span>
                            <span class="mx-1 text-secondary">•</span>
                            <span>{{ $stockRequest->date?->format('d M Y') }}</span>
                        </div>

                        <div class="muted mt-1">
                            {{ $stockRequest->requestedBy?->name ?? '—' }}
                            ·
                            <span class="mono">{{ $stockRequest->created_at?->format('d M Y H:i') ?? '—' }}</span>
                        </div>

                        {{-- SUMMARY UPGRADE (request / dispatched / received / sisa transit) --}}
                        <div class="summary-row">
                            <div class="summary-pill">
                                <span>Diminta</span>
                                <span class="mono">{{ (int) $totalRequested }} pcs</span>
                            </div>
                            <div class="summary-pill">
                                <span>Dikirim PRD</span>
                                <span class="mono">{{ (int) $totalDispatched }} pcs</span>
                            </div>
                            <div class="summary-pill">
                                <span>Diterima RTS</span>
                                <span class="mono">{{ (int) $totalReceived }} pcs</span>
                            </div>
                            <div class="summary-pill summary-pill--sisa">
                                <span>Sisa di Transit</span>
                                <span class="mono">{{ (int) $remainingToReceive }} pcs</span>
                            </div>
                        </div>

                        <div class="mt-2 d-flex flex-wrap align-center gap-1">
                            <div class="badge-warehouse">
                                <span class="code">{{ $stockRequest->sourceWarehouse?->code }}</span>
                                <span>{{ $stockRequest->sourceWarehouse?->name }}</span>
                            </div>
                            <span style="font-size:.9rem; opacity:.7;">→</span>
                            <div class="badge-warehouse">
                                <span class="code">WH-TRANSIT</span>
                                <span>Transit</span>
                            </div>
                            <span style="font-size:.9rem; opacity:.7;">→</span>
                            <div class="badge-warehouse">
                                <span class="code">{{ $stockRequest->destinationWarehouse?->code }}</span>
                                <span>{{ $stockRequest->destinationWarehouse?->name }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- KANAN: status + tombol edit --}}
                    <div class="d-flex flex-col align-end gap-1">
                        <div class="badge-status {{ $badgeClass }}">
                            <span class="dot" style="background: {{ $dotColor }};"></span>
                            <span>{{ $statusLabel }}</span>
                        </div>

                        @if ($stockRequest->date && $stockRequest->date->isToday())
                            <a href="{{ route('rts.stock-requests.create', ['date' => $stockRequest->date->toDateString()]) }}"
                                class="btn-edit-today mt-1">
                                ✏️ Ubah permintaan hari ini
                            </a>
                        @endif
                    </div>
                </div>

                {{-- STRIP CTA – UPGRADE (dinamis sesuai stage) --}}
                <div class="cta-strip">
                    <div>
                        <div class="cta-text-main">{{ $ctaMain }}</div>
                        <div class="cta-text-sub mt-1">
                            @if ($status === 'completed')
                                {{ $ctaSub }}
                            @elseif ($remainingToReceive > 0)
                                <span class="step">1</span> Cek fisik (Transit/RTS) ·
                                <span class="step">2</span> Input qty diterima ·
                                <span class="step">3</span> Mutasi stok <strong>TRANSIT → RTS</strong>.
                            @else
                                {{ $ctaSub }}
                            @endif
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        @if ($canOpenConfirm)
                            <a href="{{ route('rts.stock-requests.confirm', $stockRequest) }}" class="btn-confirm-rts">
                                {{ $ctaActionLabel ?? '⚡ Buka form penerimaan RTS' }}
                            </a>
                        @elseif ($status !== 'completed')
                            {{-- tombol alternatif: ke detail PRD process (biar RTS bisa “lihat progres PRD”) --}}
                            <a href="{{ route('prd.stock-requests.edit', $stockRequest) }}" class="btn-secondary-rts">
                                👀 Lihat proses PRD
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- BODY --}}
            <div class="card-body">
                @if ($stockRequest->notes)
                    <div class="notes-block">
                        <div class="notes-label">
                            CATATAN
                        </div>
                        <div class="text-sm text-slate-800 dark:text-slate-100 whitespace-pre-line">
                            {{ $stockRequest->notes }}
                        </div>
                    </div>
                @endif

                {{-- OVER-REQUEST (kecil, nggak rame) --}}
                @if ($hasOver)
                    <div class="mb-2">
                        <span class="badge-over">
                            <span class="badge-over-dot"></span>
                            {{ $overLinesCount }} baris over-request ·
                            selisih {{ $overQtyTotal }} pcs dari stok PRD (snapshot).
                        </span>
                    </div>
                @endif

                <div class="d-flex justify-between align-center mb-2">
                    <div class="muted" style="letter-spacing:.12em; text-transform:uppercase; font-size:.75rem;">
                        DETAIL ITEM
                    </div>
                    <div class="muted">
                        Sisa Transit = Dikirim PRD − Diterima RTS
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 32px;">#</th>
                                <th>Item FG</th>
                                <th style="width: 12%;">Qty Request</th>
                                <th style="width: 14%;">Dikirim PRD</th>
                                <th style="width: 14%;">Diterima RTS</th>
                                <th style="width: 14%;">Sisa Transit</th>
                                <th style="width: 16%;">Stok PRD (snapshot)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stockRequest->lines as $index => $line)
                                @php
                                    $qtyRequest = (float) ($line->qty_request ?? 0);
                                    $qtyDispatched = (float) ($line->qty_dispatched ?? 0);
                                    $qtyReceived = (float) ($line->qty_received ?? 0);

                                    $snapshot =
                                        $line->stock_snapshot_at_request !== null
                                            ? (float) $line->stock_snapshot_at_request
                                            : null;

                                    $isOverRequest = $snapshot !== null && $qtyRequest > $snapshot;

                                    $sisaTransit = max($qtyDispatched - $qtyReceived, 0);
                                @endphp
                                <tr>
                                    <td class="mono align-top">
                                        {{ $index + 1 }}
                                    </td>
                                    <td>
                                        <div class="fw-semibold" style="font-size:.86rem;">
                                            {{ $line->item?->code ?? '—' }}
                                            <span class="text-slate-500">—</span>
                                            {{ $line->item?->name ?? '—' }}
                                        </div>

                                        @if ($line->notes)
                                            <div class="line-notes">
                                                {{ $line->notes }}
                                            </div>
                                        @endif

                                        @if ($isOverRequest)
                                            <span class="badge-over">
                                                <span class="badge-over-dot"></span>
                                                Over-request
                                                <span class="mono">
                                                    ({{ $qtyRequest }} &gt; {{ $snapshot }})
                                                </span>
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Qty Request --}}
                                    <td class="mono align-top">
                                        {{ (int) $qtyRequest }}
                                        <span class="text-slate-400" style="font-size:.7rem;">pcs</span>
                                    </td>

                                    {{-- Dikirim PRD --}}
                                    <td class="mono align-top">
                                        {{ (int) $qtyDispatched }}
                                        <span class="text-slate-400" style="font-size:.7rem;">pcs</span>
                                    </td>

                                    {{-- Diterima RTS --}}
                                    <td class="mono align-top">
                                        {{ (int) $qtyReceived }}
                                        <span class="text-slate-400" style="font-size:.7rem;">pcs</span>
                                    </td>

                                    {{-- Sisa Transit --}}
                                    <td class="mono align-top">
                                        <span class="{{ $sisaTransit > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                                            {{ (int) $sisaTransit }}
                                        </span>
                                        <span class="text-slate-400" style="font-size:.7rem;">pcs</span>
                                    </td>

                                    {{-- Stok PRD snapshot --}}
                                    <td class="mono align-top">
                                        @if ($snapshot !== null)
                                            {{ (int) $snapshot }}
                                            <span class="text-slate-400" style="font-size:.7rem;">pcs</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-sm text-slate-500 py-4">
                                        Tidak ada detail item.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="footer-row d-flex justify-between mt-3">
                    <div>
                        Terakhir update:
                        <span class="mono">
                            {{ $stockRequest->updated_at?->format('d M Y H:i') ?? '—' }}
                        </span>
                    </div>

                    {{-- hint status kecil biar user paham stage --}}
                    <div class="muted">
                        @if ($status === 'completed')
                            Status: selesai.
                        @elseif ($remainingToReceive > 0)
                            Status: siap diterima RTS (ada sisa di Transit).
                        @elseif ($remainingOverall > 0)
                            Status: menunggu PRD kirim tambahan.
                        @else
                            Status: siap selesai (cek data).
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
