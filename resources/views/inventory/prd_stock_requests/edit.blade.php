{{-- resources/views/inventory/prd_stock_requests/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Proses Permintaan RTS • ' . $stockRequest->code)

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-main-strong: rgba(15, 118, 110, 1);
            --rts-main-soft: rgba(45, 212, 191, 0.14);
        }

        .rts-edit-page {
            min-height: 100vh;
        }

        .rts-edit-page .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: 1.4rem 1.1rem 4rem;
        }

        body[data-theme="light"] .rts-edit-page .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.08) 0,
                    rgba(45, 212, 191, 0.1) 28%,
                    #f9fafb 70%);
        }

        .card-main {
            background: var(--card);
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.26);
        }

        .card-main .card-body {
            padding: 1.25rem 1.35rem;
        }

        .page-title {
            font-size: 1.18rem;
            font-weight: 600;
            margin-bottom: .25rem;
        }

        .page-subtitle {
            font-size: .87rem;
            color: rgba(100, 116, 139, 1);
            margin-top: .25rem;
        }

        .step-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .18rem .6rem;
            border-radius: 999px;
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .12em;
            background: rgba(15, 23, 42, 0.02);
            color: #6b7280;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New",
                monospace;
            font-variant-numeric: tabular-nums;
        }

        .muted {
            color: rgba(148, 163, 184, 1);
            font-size: .78rem;
        }

        .pill-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #94a3b8;
        }

        .code-badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            border-radius: 999px;
            padding: .14rem .6rem;
            font-size: .8rem;
            background: rgba(15, 23, 42, 0.03);
            border: 1px solid rgba(148, 163, 184, 0.45);
        }

        .code-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: var(--rts-main-strong);
        }

        .badge-status {
            padding: .14rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            line-height: 1.2;
        }

        .badge-status.submitted {
            background: rgba(59, 130, 246, .10);
            color: rgba(30, 64, 175, 1);
        }

        .badge-status.partial {
            background: rgba(234, 179, 8, .12);
            color: rgba(133, 77, 14, 1);
        }

        .badge-status.completed {
            background: rgba(22, 163, 74, .12);
            color: rgba(22, 101, 52, 1);
        }

        .badge-status.draft {
            background: rgba(148, 163, 184, 0.18);
            color: #475569;
        }

        .summary-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .28rem .75rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .5);
            font-size: .78rem;
            background: rgba(15, 23, 42, 0.02);
        }

        .summary-pill strong {
            font-weight: 600;
        }

        .summary-pill--sisa {
            border-color: var(--rts-main-strong);
            background: var(--rts-main-soft);
            color: var(--rts-main-strong);
        }

        .table-wrap {
            margin-top: 1rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .28);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
        }

        .table th,
        .table td {
            padding: .42rem .55rem;
            border-bottom: 1px solid rgba(148, 163, 184, .22);
            vertical-align: middle;
        }

        .table thead {
            background: rgba(15, 23, 42, 0.035);
        }

        .table thead th {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #6b7280;
        }

        .row-number {
            font-weight: 600;
            color: #64748b;
        }

        .qty-text {
            font-variant-numeric: tabular-nums;
        }

        .line-item-name {
            font-size: .8rem;
            color: #6b7280;
        }

        .qty-available {
            color: #0f766e;
        }

        .error-text {
            font-size: .76rem;
            color: #dc2626;
            margin-top: .1rem;
        }

        .btn-primary-rts {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .42rem 1rem;
            border-radius: 999px;
            border: none;
            background: var(--rts-main-strong);
            color: #e0f2f1;
            font-size: .82rem;
            font-weight: 600;
        }

        .btn-primary-rts:hover {
            filter: brightness(0.97);
            color: #ecfdf5;
        }

        .btn-outline-rts {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .38rem .9rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.8);
            background: transparent;
            color: #0f172a;
            font-size: .8rem;
            font-weight: 500;
        }

        .btn-outline-rts:hover {
            text-decoration: none;
            filter: brightness(0.98);
        }

        .hint {
            font-size: .78rem;
            color: #94a3b8;
        }

        .hint-soft {
            font-size: .78rem;
            color: #64748b;
        }

        @media (max-width: 767.98px) {
            .rts-edit-page .page-wrap {
                padding-inline: .85rem;
            }

            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                border-bottom: 1px solid rgba(148, 163, 184, .28);
                padding: .65rem .35rem;
            }

            .table tbody tr:last-child {
                border-bottom: none;
            }

            .table tbody td {
                display: flex;
                justify-content: space-between;
                gap: .6rem;
                padding: .22rem 0;
                border-top: none;
                font-size: .85rem;
            }

            .table tbody td::before {
                content: attr(data-label);
                font-weight: 500;
                color: #64748b;
                flex: 0 0 42%;
                max-width: 52%;
            }

            .td-row-number {
                justify-content: flex-start;
                gap: .35rem;
            }

            .td-row-number::before {
                content: '#';
                flex: 0 0 auto;
                color: #94a3b8;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $status = $stockRequest->status;
        $statusLabel = match ($status) {
            'submitted' => 'Menunggu diproses PRD',
            'partial' => 'Sebagian sudah dikirim',
            'completed' => 'Selesai',
            default => ucfirst($status),
        };

        $totalRequested = (float) ($stockRequest->total_requested_qty ?? $stockRequest->lines->sum('qty_request'));
        $totalIssued = (float) ($stockRequest->total_issued_qty ?? $stockRequest->lines->sum('qty_issued'));
        $totalOutstanding = max($totalRequested - $totalIssued, 0);
    @endphp

    <div class="rts-edit-page">
        <div class="page-wrap">

            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                <div>
                    <div class="step-chip mb-1">
                        <span>Langkah 1 dari 2</span>
                        <span>•</span>
                        <span>PRD isi rencana kirim</span>
                    </div>

                    <a href="{{ route('prd.stock-requests.index') }}" class="btn btn-link btn-sm px-0 mb-1">
                        ← Kembali ke daftar
                    </a>

                    <div class="page-title">
                        Proses Permintaan RTS
                    </div>
                    <div class="page-subtitle">
                        Tentukan rencana Qty yang akan dikirim dari Gudang Produksi ke Gudang RTS.
                        Stok baru benar-benar pindah setelah RTS konfirmasi fisik.
                    </div>
                </div>
                <div class="text-end">
                    <div class="mb-2">
                        <span class="badge-status {{ $status }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                    <div class="code-badge">
                        <span class="dot"></span>
                        <span class="mono">{{ $stockRequest->code }}</span>
                    </div>
                </div>
            </div>

            <div class="card-main mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="pill-label mb-1">Tanggal & Gudang</div>
                            <div class="mb-1">
                                <span class="mono">
                                    {{ $stockRequest->date?->format('d M Y') ?? '-' }}
                                </span>
                            </div>
                            <div style="font-size:.84rem;">
                                {{ $stockRequest->sourceWarehouse?->name ?? '-' }} →
                                <span class="muted">
                                    {{ $stockRequest->destinationWarehouse?->name ?? '-' }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="pill-label mb-1">Ringkasan Qty</div>
                            <div class="d-flex flex-wrap gap-2">
                                <div class="summary-pill">
                                    <span>Diminta</span>
                                    <strong class="mono">{{ (int) $totalRequested }}</strong>
                                </div>
                                <div class="summary-pill">
                                    <span>Rencana kirim</span>
                                    <strong class="mono">{{ (int) $totalIssued }}</strong>
                                </div>
                                <div class="summary-pill summary-pill--sisa">
                                    <span>Sisa permintaan</span>
                                    <strong class="mono">{{ (int) $totalOutstanding }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="pill-label mb-1">Catatan permintaan</div>
                            <div style="font-size:.82rem;">
                                @if ($stockRequest->notes)
                                    {!! nl2br(e($stockRequest->notes)) !!}
                                @else
                                    <span class="muted">Tidak ada catatan.</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="hint">
                            Di kolom <strong>Qty Kirim</strong>, isi rencana Qty yang akan dikirim.
                            RTS akan konfirmasi Qty fisik saat barang diterima.
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('prd.stock-requests.show', $stockRequest) }}" class="btn-outline-rts">
                                Lihat detail dokumen
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('prd.stock-requests.confirm', $stockRequest) }}" method="POST" novalidate>
                @csrf

                <div class="card-main">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                            <div class="pill-label">
                                Daftar item permintaan
                            </div>
                            <div class="hint-soft">
                                Stok PRD di kolom hijau hanya untuk informasi.
                                Sistem tidak membatasi Qty Kirim terhadap stok atau Outstanding.
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Item</th>
                                        <th class="text-end" style="width: 90px;">Diminta</th>
                                        <th class="text-end" style="width: 100px;">Outstanding</th>
                                        <th class="text-end" style="width: 110px;">Stok PRD</th>
                                        <th class="text-end" style="width: 130px;">Qty Kirim (rencana)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($stockRequest->lines as $index => $line)
                                        @php
                                            $lineId = $line->id;
                                            $requested = (float) $line->qty_request;
                                            $issued = (float) $line->qty_issued;
                                            $outstanding = max($requested - $issued, 0);
                                            $available = (float) ($liveStocks[$lineId] ?? 0);

                                            $inputName = "lines[{$lineId}][qty_issued]";

                                            // Pertama kali kosong; kalau ada error, pakai old()
                                            $inputValue = old($inputName, null);

                                            $errorKey = "lines.{$lineId}.qty_issued";
                                        @endphp

                                        <tr>
                                            <td class="td-row-number row-number" data-label="#">
                                                {{ $index + 1 }}
                                            </td>

                                            <td data-label="Item">
                                                <div class="fw-semibold">
                                                    {{ $line->item?->code ?? '-' }}
                                                </div>
                                                <div class="line-item-name">
                                                    {{ $line->item?->name ?? '' }}
                                                </div>
                                            </td>

                                            <td class="text-end" data-label="Diminta">
                                                <span class="mono qty-text">
                                                    {{ (int) $requested }}
                                                </span>
                                            </td>

                                            <td class="text-end" data-label="Outstanding">
                                                <span class="mono qty-text">
                                                    {{ (int) $outstanding }}
                                                </span>
                                            </td>

                                            <td class="text-end" data-label="Stok PRD">
                                                <span class="mono qty-text qty-available">
                                                    {{ (int) $available }}
                                                </span>
                                            </td>

                                            <td class="text-end" data-label="Qty kirim (rencana)">
                                                <x-number-input name="{{ $inputName }}" :value="$inputValue" mode="integer"
                                                    min="0" class="text-end mono js-qty-issued" />

                                                @error($errorKey)
                                                    <div class="error-text">
                                                        {{ $message }}
                                                    </div>
                                                @enderror
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="hint">
                                Baris dengan <strong>Qty Kirim = 0</strong> dianggap tidak ada rencana kirim untuk item
                                tersebut.
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn-primary-rts">
                                    <span>✔ Simpan rencana Qty Kirim</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            @if ($movementHistory->count() > 0)
                <div class="card-main mt-3">
                    <div class="card-body">
                        <div class="pill-label mb-1">
                            Histori mutasi terkait dokumen ini
                        </div>
                        <div class="hint mb-2">
                            Ringkasan pergerakan stok yang sudah pernah dibuat dari dokumen ini (setelah RTS
                            konfirmasi fisik).
                        </div>

                        <div class="table-wrap">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 110px;">Tanggal</th>
                                        <th>Item</th>
                                        <th class="text-end" style="width: 100px;">Qty</th>
                                        <th style="width: 120px;">Gudang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($movementHistory as $mv)
                                        <tr>
                                            <td class="mono">
                                                {{ $mv->date?->format('d M Y') ?? '-' }}<br>
                                                <span class="muted">{{ $mv->created_at?->format('H:i') }}</span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">
                                                    {{ $mv->item?->code ?? '-' }}
                                                </div>
                                                <div class="line-item-name">
                                                    {{ $mv->item?->name ?? '' }}
                                                </div>
                                            </td>
                                            <td class="text-end mono">
                                                {{ (int) $mv->qty }}
                                            </td>
                                            <td>
                                                {{ $mv->warehouse?->code ?? '-' }}<br>
                                                <span class="muted">
                                                    {{ $mv->warehouse?->name ?? '' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = Array.from(document.querySelectorAll('.js-qty-issued'));

            inputs.forEach((input, index) => {
                input.addEventListener('focus', function() {
                    setTimeout(() => {
                        this.select();
                    }, 10);
                });

                input.addEventListener('keydown', function(e) {
                    const currentIndex = inputs.indexOf(this);

                    if (e.key === 'Enter' || e.key === 'ArrowDown') {
                        e.preventDefault();
                        const next = inputs[currentIndex + 1];
                        if (next) {
                            next.focus();
                        }
                    }

                    if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        const prev = inputs[currentIndex - 1];
                        if (prev) {
                            prev.focus();
                        }
                    }
                });
            });

            if (inputs.length > 0) {
                inputs[0].focus();
                inputs[0].select();
            }
        });
    </script>
@endpush
