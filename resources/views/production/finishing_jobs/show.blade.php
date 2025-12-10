{{-- resources/views/production/finishing_jobs/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Finishing ' . $job->code)

@push('head')
    <style>
        :root {
            --fin-card-radius: 16px;
            --fin-border: rgba(148, 163, 184, 0.28);
            --fin-muted: #6b7280;
            --fin-accent: #2563eb;
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

        /* LIGHT MODE background */
        body[data-theme="light"] .finishing-show-page .page-wrap {
            background: linear-gradient(to bottom,
                    var(--fin-bg-light-1) 0,
                    var(--fin-bg-light-2) 40%,
                    var(--fin-bg-light-3) 100%);
        }

        /* DARK MODE background */
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

        .fin-badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            align-items: center;
            justify-content: flex-end;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .18rem .75rem;
            font-size: .78rem;
            font-weight: 500;
            border: 1px solid rgba(148, 163, 184, 0.5);
        }

        .badge-status-draft {
            background: rgba(251, 191, 36, 0.1);
            border-color: rgba(251, 191, 36, 0.6);
            color: #92400e;
        }

        .badge-status-posted {
            background: rgba(34, 197, 94, 0.1);
            border-color: rgba(34, 197, 94, 0.6);
            color: #166534;
        }

        .badge-has-reject {
            background: rgba(239, 68, 68, 0.08);
            border-color: rgba(239, 68, 68, 0.7);
            color: #991b1b;
        }

        .badge-auto-post {
            background: rgba(59, 130, 246, 0.08);
            border-color: rgba(59, 130, 246, 0.7);
            color: #1d4ed8;
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
            font-weight: 600;
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
            font-weight: 600;
            margin-bottom: .35rem;
        }

        .fin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 4px;
            font-size: .8rem;
        }

        .fin-table thead tr {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--fin-muted);
        }

        .fin-table thead th {
            padding: .25rem .5rem;
        }

        .fin-table tbody tr {
            background: rgba(248, 250, 252, 0.9);
        }

        body[data-theme="dark"] .fin-table tbody tr {
            background: rgba(15, 23, 42, 0.9);
        }

        .fin-table tbody td {
            padding: .35rem .5rem;
            vertical-align: top;
        }

        .fin-table tbody tr:first-child td:first-child {
            border-top-left-radius: 10px;
        }

        .fin-table tbody tr:first-child td:last-child {
            border-top-right-radius: 10px;
        }

        .fin-table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 10px;
        }

        .fin-table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 10px;
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

        @media (max-width: 640px) {
            .fin-card {
                padding-inline: .9rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="finishing-show-page">
        <div class="page-wrap">

            {{-- HEADER + BADGE + ACTIONS --}}
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
                            Dibuat oleh:
                            <span class="font-medium">
                                {{ $job->createdBy?->name ?? '-' }}
                            </span>
                        </div>
                        @if ($job->notes)
                            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                Catatan: {{ $job->notes }}
                            </div>
                        @endif
                    </div>

                    <div class="fin-badge-row">
                        {{-- STATUS --}}
                        @if ($job->status === 'posted')
                            <span class="badge-soft badge-status-posted">
                                POSTED
                            </span>
                        @else
                            <span class="badge-soft badge-status-draft">
                                DRAFT
                            </span>
                        @endif

                        {{-- AUTO POST (0 REJECT) --}}
                        @if ($isAutoPost)
                            <span class="badge-soft badge-auto-post">
                                AUTO POST (0 REJECT)
                            </span>
                        @endif

                        {{-- HAS REJECT --}}
                        @if ($hasReject)
                            <span class="badge-soft badge-has-reject">
                                HAS REJECT
                            </span>
                        @endif
                    </div>
                </div>

                {{-- ALERT: DRAFT + HAS REJECT --}}
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

                {{-- ALERT: POSTED + HAS REJECT (bukan auto-post) --}}
                @if ($hasReject && $job->status === 'posted' && !$isAutoPost)
                    <div class="fin-alert danger mt-3">
                        <div class="fin-alert-title">
                            Finishing ini sudah POSTED dengan REJECT.
                        </div>
                        <p>
                            Stok OK telah dipindahkan ke <strong>WH-PRD</strong> dan stok reject ke gudang
                            <strong>REJECT</strong>. Data di bawah dipakai untuk evaluasi kualitas &amp; penanggung jawab.
                        </p>
                    </div>
                @endif

                {{-- ACTION BUTTONS --}}
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

            {{-- RINGKASAN REJECT & PENANGGUNG JAWAB --}}
            @php
                /** @var \Illuminate\Support\Collection|\App\Models\FinishingJobLine[] $rejectLines */
                $rejectLines = $job->lines->filter(fn($line) => (float) $line->qty_reject > 0.0001);
            @endphp

            @if ($rejectLines->count())
                <div class="fin-card">
                    <div class="flex items-center justify-between mb-2 gap-2">
                        <h2 class="fin-section-title">
                            Ringkasan Reject &amp; Penanggung Jawab
                        </h2>
                        <span class="fin-chip fin-chip-light">
                            Total baris reject: {{ $rejectLines->count() }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="fin-table">
                            <thead>
                                <tr>
                                    <th class="text-left">Bundle</th>
                                    <th class="text-left">Item</th>
                                    <th class="text-right">Qty In</th>
                                    <th class="text-right text-red-600">Qty Reject</th>
                                    <th class="text-left">Alasan</th>
                                    <th class="text-left">Operator Finishing</th>
                                    <th class="text-left">Operator Jahit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rejectLines as $line)
                                    <tr>
                                        <td class="mono text-[11px]">
                                            {{ $line->bundle?->bundle_code ?? '-' }}
                                        </td>
                                        <td>
                                            <div class="text-[11px] font-medium">
                                                {{ $line->item?->code ?? ($line->bundle?->finishedItem?->code ?? '-') }}
                                            </div>
                                            <div class="text-[11px] text-slate-500">
                                                {{ $line->item?->name ?? ($line->bundle?->finishedItem?->name ?? '') }}
                                            </div>
                                        </td>
                                        <td class="mono text-right text-[11px]">
                                            {{ number_format($line->qty_in, 0) }}
                                        </td>
                                        <td class="mono text-right text-[11px] text-red-600">
                                            {{ number_format($line->qty_reject, 0) }}
                                        </td>
                                        <td class="text-[11px]">
                                            {{ $line->reject_reason ?: '-' }}
                                        </td>
                                        <td class="text-[11px]">
                                            {{ $line->operator?->name ?? '-' }}
                                        </td>
                                        <td class="text-[11px]">
                                            {{ $line->sewingOperator?->name ?? ($line->sewingPickupLine?->sewingPickup?->operator?->name ?? '-') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="help mt-2">
                        Reject per bundle dikaitkan dengan operator finishing dan operator jahit
                        (diambil dari Sewing Return / Sewing Pickup terbaru untuk bundle terkait).
                    </p>
                </div>
            @endif

            {{-- DETAIL BARIS FINISHING (SEMUA BUNDLE) --}}
            <div class="fin-card">
                <div class="flex items-center justify-between mb-2 gap-2">
                    <h2 class="fin-section-title">
                        Detail Finishing per Bundle
                    </h2>
                    <span class="fin-chip fin-chip-light">
                        Total baris: {{ $job->lines->count() }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="fin-table">
                        <thead>
                            <tr>
                                <th class="text-left">Bundle</th>
                                <th class="text-left">Item</th>
                                <th class="text-right">Qty In</th>
                                <th class="text-right">Qty OK</th>
                                <th class="text-right">Qty Reject</th>
                                <th class="text-left">Operator Finishing</th>
                                <th class="text-left">Operator Jahit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($job->lines as $line)
                                <tr>
                                    <td class="mono text-[11px]">
                                        <div>{{ $line->bundle?->bundle_code ?? '-' }}</div>
                                        <div class="text-[11px] text-slate-500">
                                            Job: {{ $line->bundle?->cuttingJob?->code ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-[11px] font-medium">
                                            {{ $line->item?->code ?? ($line->bundle?->finishedItem?->code ?? '-') }}
                                        </div>
                                        <div class="text-[11px] text-slate-500">
                                            {{ $line->item?->name ?? ($line->bundle?->finishedItem?->name ?? '') }}
                                        </div>
                                        <div class="text-[11px] text-slate-400">
                                            LOT:
                                            {{ $line->bundle?->lot?->code ?? '-' }}
                                            &middot;
                                            {{ $line->bundle?->lot?->item?->name ?? '' }}
                                        </div>
                                    </td>
                                    <td class="mono text-right text-[11px]">
                                        {{ number_format($line->qty_in, 0) }}
                                    </td>
                                    <td class="mono text-right text-[11px]">
                                        {{ number_format($line->qty_ok, 0) }}
                                    </td>
                                    <td
                                        class="mono text-right text-[11px] {{ $line->qty_reject > 0 ? 'text-red-600' : '' }}">
                                        {{ number_format($line->qty_reject, 0) }}
                                    </td>
                                    <td class="text-[11px]">
                                        {{ $line->operator?->name ?? '-' }}
                                    </td>
                                    <td class="text-[11px]">
                                        {{ $line->sewingOperator?->name ?? ($line->sewingPickupLine?->sewingPickup?->operator?->name ?? '-') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @php
                    $totalIn = $job->lines->sum('qty_in');
                    $totalOk = $job->lines->sum('qty_ok');
                    $totalReject = $job->lines->sum('qty_reject');
                @endphp

                <div class="mt-3 flex flex-wrap gap-3 justify-end text-xs">
                    <div class="fin-chip">
                        Total IN:
                        <span class="mono ml-1">
                            {{ number_format($totalIn, 0) }}
                        </span>
                    </div>
                    <div class="fin-chip">
                        Total OK:
                        <span class="mono ml-1">
                            {{ number_format($totalOk, 0) }}
                        </span>
                    </div>
                    <div class="fin-chip">
                        Total Reject:
                        <span class="mono ml-1 {{ $totalReject > 0 ? 'text-red-600' : '' }}">
                            {{ number_format($totalReject, 0) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- SNAPSHOT HPP RM-ONLY --}}
            @if ($rmSnapshots->count())
                <div class="fin-card">
                    <div class="flex items-center justify-between mb-2 gap-2">
                        <h2 class="fin-section-title">
                            Snapshot HPP RM-only (Finishing)
                        </h2>
                        <span class="fin-chip fin-chip-light">
                            Total snapshot: {{ $rmSnapshots->count() }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="fin-table">
                            <thead>
                                <tr>
                                    <th class="text-left">Snapshot Date</th>
                                    <th class="text-left">Item</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">HPP / pcs</th>
                                    <th class="text-right">Total HPP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rmSnapshots as $snap)
                                    <tr>
                                        <td class="mono text-[11px]">
                                            {{ $snap->snapshot_date ?? $snap->created_at }}
                                        </td>
                                        <td>
                                            <div class="text-[11px] font-medium">
                                                {{ $snap->item?->code ?? '-' }}
                                            </div>
                                            <div class="text-[11px] text-slate-500">
                                                {{ $snap->item?->name ?? '' }}
                                            </div>
                                        </td>
                                        <td class="mono text-right text-[11px]">
                                            {{ number_format($snap->qty ?? 0, 0) }}
                                        </td>
                                        <td class="mono text-right text-[11px]">
                                            {{ number_format($snap->unit_cost ?? 0, 0) }}
                                        </td>
                                        <td class="mono text-right text-[11px]">
                                            {{ number_format($snap->total_cost ?? 0, 0) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="help mt-2">
                        Snapshot ini di-generate otomatis saat Finishing diposting menggunakan HPP
                        <strong>RM-only</strong> dari WIP-FIN, untuk keperluan analisa biaya produksi dan
                        konsistensi nilai stok.
                    </p>
                </div>
            @endif

        </div>
    </div>
@endsection
