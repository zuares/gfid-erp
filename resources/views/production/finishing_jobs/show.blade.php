{{-- resources/views/production/finishing_jobs/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Finishing ' . $job->code)

@push('head')
    <style>
        :root {
            --fin-card-radius: 16px;
            --fin-border: rgba(148, 163, 184, 0.28);
            --fin-muted: #6b7280;
            --fin-bg-light-1: #f5f6fa;
            --fin-bg-light-2: #f9fafb;
            --fin-bg-light-3: #ffffff;
        }

        .finishing-show-page {
            min-height: 100vh;
        }

        .finishing-show-page .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: 1rem 1rem 3.5rem;
        }

        body[data-theme="light"] .finishing-show-page .page-wrap {
            background: linear-gradient(to bottom,
                    var(--fin-bg-light-1) 0,
                    var(--fin-bg-light-2) 40%,
                    var(--fin-bg-light-3) 100%);
        }

        body[data-theme="dark"] .finishing-show-page .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(37, 99, 235, 0.26) 0,
                    rgba(15, 23, 42, 0.9) 55%,
                    #020617 100%);
        }

        .fin-card {
            background: var(--card);
            border-radius: var(--fin-card-radius);
            border: 1px solid var(--fin-border);
            padding: 1rem 1.2rem;
            margin-bottom: 1rem;
            box-shadow:
                0 18px 45px rgba(15, 23, 42, 0.12),
                0 0 0 1px rgba(15, 23, 42, 0.04);
        }

        body[data-theme="dark"] .fin-card {
            border-color: rgba(51, 65, 85, 0.9);
            box-shadow:
                0 18px 50px rgba(0, 0, 0, 0.85),
                0 0 0 1px rgba(15, 23, 42, 0.9);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .fin-alert {
            margin-top: .8rem;
            border-radius: 14px;
            padding: .8rem .9rem;
            border: 1px solid rgba(251, 146, 60, 0.8);
            background: linear-gradient(to right,
                    rgba(251, 146, 60, 0.09),
                    rgba(251, 191, 36, 0.03));
        }

        .fin-alert.danger {
            border-color: rgba(239, 68, 68, 0.85);
            background: radial-gradient(circle at top left,
                    rgba(248, 113, 113, 0.16),
                    rgba(15, 23, 42, 0));
        }

        .fin-alert-title {
            font-weight: 700;
            font-size: .9rem;
            margin-bottom: .2rem;
        }

        .fin-alert p {
            margin: 0;
            font-size: .82rem;
        }

        .fin-alert ul {
            margin: .35rem 0 0;
            padding-left: 1.2rem;
            font-size: .8rem;
        }

        .fin-section-title {
            font-size: .9rem;
            font-weight: 700;
            margin-bottom: .35rem;
        }

        .fin-chip {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .12rem .55rem;
            font-size: .72rem;
            border: 1px solid rgba(148, 163, 184, 0.6);
        }

        .fin-chip-light {
            background: rgba(248, 250, 252, 0.9);
        }

        .help {
            color: var(--fin-muted);
            font-size: .75rem;
        }

        /* ===== TABLE UX ===== */
        .fin-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-gutter: stable;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.22);
        }

        body[data-theme="dark"] .fin-table-wrap {
            border-color: rgba(51, 65, 85, 0.75);
        }

        .fin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: .8rem;
            table-layout: fixed;
            min-width: 820px;
        }

        .fin-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: rgba(248, 250, 252, 0.98);
            color: var(--fin-muted);
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: .55rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.22);
            white-space: nowrap;
        }

        body[data-theme="dark"] .fin-table thead th {
            background: rgba(2, 6, 23, 0.92);
            border-bottom-color: rgba(51, 65, 85, 0.8);
        }

        .fin-table tbody td {
            padding: .55rem .6rem;
            vertical-align: top;
            border-bottom: 1px solid rgba(148, 163, 184, 0.14);
            background: rgba(255, 255, 255, 0.55);
        }

        body[data-theme="dark"] .fin-table tbody td {
            background: rgba(15, 23, 42, 0.55);
        }

        .fin-table tbody tr:hover td {
            background: rgba(59, 130, 246, 0.06);
        }

        body[data-theme="dark"] .fin-table tbody tr:hover td {
            background: rgba(59, 130, 246, 0.10);
        }

        .cell-2line {
            line-height: 1.15;
        }

        .cell-sub {
            margin-top: .15rem;
            font-size: .72rem;
            color: var(--fin-muted);
        }

        .num {
            text-align: right;
        }

        .rej {
            color: #991b1b;
            font-weight: 700;
        }

        /* cols */
        .col-no {
            width: 64px;
        }

        .col-item {
            width: 220px;
        }

        .col-num {
            width: 96px;
        }

        .col-ops {
            width: 160px;
        }

        .col-reason {
            width: 210px;
        }

        .col-snapdate {
            width: 170px;
        }

        /* badges mini bawah item (OWNER only) */
        .mini-badges {
            margin-top: .22rem;
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
        }

        .mini-badge {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            border-radius: 999px;
            padding: .10rem .45rem;
            font-size: .68rem;
            font-weight: 700;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: rgba(248, 250, 252, 0.75);
            white-space: nowrap;
        }

        body[data-theme="dark"] .mini-badge {
            background: rgba(2, 6, 23, 0.35);
            border-color: rgba(51, 65, 85, 0.85);
        }

        .mini-badge .k {
            font-size: .62rem;
            opacity: .7;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        /* MOBILE */
        @media (max-width: 768px) {
            .fin-card {
                padding-inline: .95rem;
            }

            .fin-table {
                font-size: .82rem;
                min-width: 720px;
            }

            .fin-table thead th {
                padding: .5rem .55rem;
            }

            .hide-sm {
                display: none;
            }

            .col-item {
                width: 190px;
            }

            .col-ops {
                width: 140px;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $isOwner = auth()->check() && auth()->user()?->role === 'owner';

        /** @var \Illuminate\Support\Collection|\App\Models\FinishingJobLine[] $rejectLines */
        $rejectLines = $job->lines->filter(fn($line) => (float) $line->qty_reject > 0.0001);

        $totalIn = $job->lines->sum('qty_in');
        $totalOk = $job->lines->sum('qty_ok');
        $totalReject = $job->lines->sum('qty_reject');
    @endphp

    <div class="finishing-show-page">
        <div class="page-wrap">

            {{-- HEADER (tanpa badge row untuk non-owner) --}}
            <div class="fin-card">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div class="space-y-1">
                        <h1 class="text-base md:text-lg font-semibold">
                            Finishing {{ $job->code }}
                        </h1>
                        <div class="text-xs md:text-sm text-slate-500 dark:text-slate-400">
                            <span class="opacity-80">Tanggal:</span>
                            <span class="mono">{{ $job->date }}</span>
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">
                            Dibuat oleh: <span class="font-medium">{{ $job->createdBy?->name ?? '-' }}</span>
                        </div>
                        @if ($job->notes)
                            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Catatan: {{ $job->notes }}
                            </div>
                        @endif
                    </div>

                    {{-- hanya OWNER boleh lihat status chips (opsional kecil), non-owner: kosong --}}
                    @if ($isOwner)
                        <div class="flex flex-wrap gap-2 justify-end">
                            @if ($job->status === 'posted')
                                <span class="mini-badge"><span class="k">status</span><span
                                        class="mono">POSTED</span></span>
                            @else
                                <span class="mini-badge"><span class="k">status</span><span
                                        class="mono">DRAFT</span></span>
                            @endif

                            @if ($isAutoPost)
                                <span class="mini-badge"><span class="k">auto</span><span class="mono">0
                                        RJ</span></span>
                            @endif

                            @if ($hasReject)
                                <span class="mini-badge"><span class="k">flag</span><span class="mono">HAS
                                        RJ</span></span>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- ALERTS --}}
                @if ($hasReject && $job->status !== 'posted')
                    <div class="fin-alert mt-3">
                        <div class="fin-alert-title">
                            Finishing ini punya REJECT &amp; <span class="underline">BELUM diposting</span>.
                        </div>
                        <p>
                            Stok masih berada di gudang <strong>WIP-FIN</strong> dan
                            <strong>belum dipindahkan</strong> ke <strong>WH-PRD</strong> / <strong>REJECT</strong>.
                        </p>
                        <ul>
                            <li>Review kuantitas OK vs Reject dan alasan reject.</li>
                            <li>Jika reject masih bisa diperbaiki, edit finishing hingga qty reject = 0.</li>
                            <li>Jika reject final, klik tombol <strong>"Post Finishing"</strong> di bawah.</li>
                        </ul>
                    </div>
                @endif

                @if ($hasReject && $job->status === 'posted' && !$isAutoPost)
                    <div class="fin-alert danger mt-3">
                        <div class="fin-alert-title">Finishing ini sudah POSTED dengan REJECT.</div>
                        <p>
                            Stok OK telah dipindahkan ke <strong>WH-PRD</strong> dan stok reject ke gudang
                            <strong>REJECT</strong>. Data di bawah dipakai untuk evaluasi kualitas &amp; penanggung jawab.
                        </p>
                    </div>
                @endif

                {{-- ACTIONS --}}
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('production.finishing_jobs.index') }}" class="btn btn-sm btn-ghost">
                        &larr; Kembali
                    </a>

                    @if ($job->status !== 'posted')
                        <a href="{{ route('production.finishing_jobs.edit', $job->id) }}" class="btn btn-sm btn-outline">
                            Edit Finishing
                        </a>

                        <form method="POST" action="{{ route('production.finishing_jobs.post', $job->id) }}"
                            onsubmit="return confirm('Post finishing ini? Stok OK akan pindah ke WH-PRD dan Reject ke gudang REJECT. Lanjutkan?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">
                                Post Finishing ({{ $hasReject ? 'dengan Reject' : '0 Reject' }})
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- RINGKASAN REJECT --}}
            @if ($rejectLines->count())
                <div class="fin-card">
                    <div class="flex items-center justify-between mb-2 gap-2">
                        <h2 class="fin-section-title">Ringkasan Reject</h2>
                        <span class="fin-chip fin-chip-light">Total baris reject: {{ $rejectLines->count() }}</span>
                    </div>

                    <div class="fin-table-wrap">
                        <table class="fin-table">
                            <thead>
                                <tr>
                                    <th class="col-no text-left">No</th>
                                    <th class="col-item text-left">Item</th>
                                    <th class="col-num num">In</th>
                                    <th class="col-num num">Reject</th>
                                    <th class="col-reason text-left">Alasan</th>
                                    <th class="col-ops text-left">Finishing</th>
                                    <th class="col-ops text-left">Jahit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rejectLines->values() as $i => $line)
                                    @php
                                        $itemCode = $line->item?->code ?? ($line->bundle?->finishedItem?->code ?? '-');
                                        $itemName = $line->item?->name ?? ($line->bundle?->finishedItem?->name ?? '');
                                        $bundleCode = $line->bundle?->bundle_code ?? '-';
                                        $jobCode = $line->bundle?->cuttingJob?->code ?? '-';
                                    @endphp
                                    <tr>
                                        <td class="mono col-no">{{ $i + 1 }}</td>

                                        <td class="col-item">
                                            <div class="cell-2line">
                                                {{-- MOBILE: kode saja --}}
                                                <div class="mono font-semibold">{{ $itemCode }}</div>
                                                {{-- DESKTOP: nama item --}}
                                                <div class="cell-sub hide-sm">{{ $itemName }}</div>

                                                {{-- OWNER: mini badges bundle+job di bawah item --}}
                                                @if ($isOwner)
                                                    <div class="mini-badges">
                                                        <span class="mini-badge"><span class="k">b</span><span
                                                                class="mono">{{ $bundleCode }}</span></span>
                                                        <span class="mini-badge"><span class="k">job</span><span
                                                                class="mono">{{ $jobCode }}</span></span>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="mono num col-num">{{ number_format($line->qty_in, 0) }}</td>
                                        <td class="mono num col-num rej">{{ number_format($line->qty_reject, 0) }}</td>
                                        <td class="col-reason">{{ $line->reject_reason ?: '-' }}</td>
                                        <td class="col-ops">{{ $line->operator?->name ?? '-' }}</td>
                                        <td class="col-ops">
                                            {{ $line->sewingOperator?->name ?? ($line->sewingPickupLine?->sewingPickup?->operator?->name ?? '-') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="help mt-2">
                        Operator jahit diambil dari Sewing Return / Sewing Pickup terbaru untuk bundle terkait.
                    </p>
                </div>
            @endif

            {{-- DETAIL BARIS FINISHING --}}
            <div class="fin-card">
                <div class="flex items-center justify-between mb-2 gap-2">
                    <h2 class="fin-section-title">Detail Finishing</h2>
                    <span class="fin-chip fin-chip-light">Total baris: {{ $job->lines->count() }}</span>
                </div>

                <div class="fin-table-wrap">
                    <table class="fin-table">
                        <thead>
                            <tr>
                                <th class="col-no text-left">No</th>
                                <th class="col-item text-left">Item</th>
                                <th class="col-num num">In</th>
                                <th class="col-num num">OK</th>
                                <th class="col-num num">Reject</th>
                                <th class="col-ops text-left">Finishing</th>
                                <th class="col-ops text-left">Jahit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($job->lines->values() as $i => $line)
                                @php
                                    $itemCode = $line->item?->code ?? ($line->bundle?->finishedItem?->code ?? '-');
                                    $itemName = $line->item?->name ?? ($line->bundle?->finishedItem?->name ?? '');
                                    $lotCode = $line->bundle?->lot?->code ?? '-';
                                    $lotName = $line->bundle?->lot?->item?->name ?? '';
                                    $bundleCode = $line->bundle?->bundle_code ?? '-';
                                    $jobCode = $line->bundle?->cuttingJob?->code ?? '-';
                                @endphp
                                <tr>
                                    <td class="mono col-no">{{ $i + 1 }}</td>

                                    <td class="col-item">
                                        <div class="cell-2line">
                                            <div class="mono font-semibold">{{ $itemCode }}</div>
                                            <div class="cell-sub hide-sm">{{ $itemName }}</div>
                                            <div class="cell-sub hide-sm">LOT: {{ $lotCode }} &middot;
                                                {{ $lotName }}</div>

                                            {{-- OWNER: mini badges bundle+job di bawah item --}}
                                            @if ($isOwner)
                                                <div class="mini-badges">
                                                    <span class="mini-badge"><span class="k">b</span><span
                                                            class="mono">{{ $bundleCode }}</span></span>
                                                    <span class="mini-badge"><span class="k">job</span><span
                                                            class="mono">{{ $jobCode }}</span></span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="mono num col-num">{{ number_format($line->qty_in, 0) }}</td>
                                    <td class="mono num col-num">{{ number_format($line->qty_ok, 0) }}</td>
                                    <td class="mono num col-num {{ (float) $line->qty_reject > 0 ? 'rej' : '' }}">
                                        {{ number_format($line->qty_reject, 0) }}
                                    </td>

                                    <td class="col-ops">{{ $line->operator?->name ?? '-' }}</td>
                                    <td class="col-ops">
                                        {{ $line->sewingOperator?->name ?? ($line->sewingPickupLine?->sewingPickup?->operator?->name ?? '-') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 flex flex-wrap gap-3 justify-end text-xs">
                    <div class="fin-chip">
                        Total IN: <span class="mono ml-1">{{ number_format($totalIn, 0) }}</span>
                    </div>
                    <div class="fin-chip">
                        Total OK: <span class="mono ml-1">{{ number_format($totalOk, 0) }}</span>
                    </div>
                    <div class="fin-chip">
                        Total Reject:
                        <span class="mono ml-1 {{ $totalReject > 0 ? 'text-red-600' : '' }}">
                            {{ number_format($totalReject, 0) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- SNAPSHOT HPP RM-ONLY (HANYA OWNER) --}}
            @if ($isOwner && $rmSnapshots->count())
                <div class="fin-card">
                    <div class="flex items-center justify-between mb-2 gap-2">
                        <h2 class="fin-section-title">Snapshot HPP RM-only (Finishing)</h2>
                        <span class="fin-chip fin-chip-light">Total snapshot: {{ $rmSnapshots->count() }}</span>
                    </div>

                    <div class="fin-table-wrap">
                        <table class="fin-table" style="min-width: 780px;">
                            <thead>
                                <tr>
                                    <th class="col-snapdate text-left">Snapshot Date</th>
                                    <th class="col-item text-left">Item</th>
                                    <th class="col-num num">Qty</th>
                                    <th class="col-num num">HPP/pcs</th>
                                    <th class="col-num num">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rmSnapshots as $snap)
                                    @php
                                        $snapItemCode = $snap->item?->code ?? '-';
                                        $snapItemName = $snap->item?->name ?? '';
                                    @endphp
                                    <tr>
                                        <td class="mono col-snapdate">{{ $snap->snapshot_date ?? $snap->created_at }}</td>
                                        <td class="col-item">
                                            <div class="cell-2line">
                                                <div class="mono font-semibold">{{ $snapItemCode }}</div>
                                                <div class="cell-sub hide-sm">{{ $snapItemName }}</div>
                                            </div>
                                        </td>
                                        <td class="mono num col-num">{{ number_format($snap->qty ?? 0, 0) }}</td>
                                        <td class="mono num col-num">{{ number_format($snap->unit_cost ?? 0, 0) }}</td>
                                        <td class="mono num col-num">{{ number_format($snap->total_cost ?? 0, 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="help mt-2">
                        Snapshot ini hanya tampil untuk <strong>owner</strong>.
                    </p>
                </div>
            @endif

        </div>
    </div>
@endsection
