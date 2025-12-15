{{-- resources/views/inventory/rts_stock_requests/confirm.blade.php --}}
@extends('layouts.app')

@section('title', 'RTS • Terima dari Transit • ' . $stockRequest->code)

@push('head')
    <style>
        /* pakai CSS kamu (biar konsisten) */
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-main-strong: rgba(15, 118, 110, 1);
            --rts-main-soft: rgba(45, 212, 191, 0.14);
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: 1.2rem 1.1rem 3.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.08) 0,
                    rgba(45, 212, 191, 0.1) 28%,
                    #f9fafb 70%);
        }

        .page-title {
            font-size: 1.12rem;
            font-weight: 600;
        }

        .page-subtitle {
            font-size: .8rem;
            color: rgba(100, 116, 139, 1);
        }

        .step-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .18rem .6rem;
            border-radius: 999px;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .12em;
            background: rgba(15, 23, 42, 0.02);
            color: #6b7280;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-variant-numeric: tabular-nums;
        }

        .muted {
            color: rgba(148, 163, 184, 1);
            font-size: .78rem;
        }

        .card-main {
            background: var(--card);
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, .26);
            margin-bottom: .9rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05), 0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .card-body {
            padding: 1rem 1.1rem;
        }

        .badge-status {
            padding: .14rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }

        .badge-status.submitted {
            background: rgba(59, 130, 246, .10);
            color: rgba(30, 64, 175, 1);
        }

        .badge-status.shipped {
            background: rgba(45, 212, 191, .14);
            color: rgba(15, 118, 110, 1);
        }

        .badge-status.partial {
            background: rgba(234, 179, 8, .12);
            color: rgba(133, 77, 14, 1);
        }

        .badge-status.completed {
            background: rgba(22, 163, 74, .12);
            color: rgba(22, 101, 52, 1);
        }

        .code-badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            border-radius: 999px;
            padding: .14rem .6rem;
            font-size: .8rem;
            background: rgba(15, 23, 42, .03);
            border: 1px solid rgba(148, 163, 184, .45);
        }

        .code-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: var(--rts-main-strong);
        }

        .pill-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #94a3b8;
        }

        .summary-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .24rem .65rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .5);
            font-size: .76rem;
            background: rgba(15, 23, 42, 0.015);
        }

        .summary-pill--sisa {
            border-color: var(--rts-main-strong);
            background: var(--rts-main-soft);
            color: var(--rts-main-strong);
        }

        .table-wrap {
            margin-top: .7rem;
            border-radius: 10px;
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

        .line-item-name {
            font-size: .8rem;
            color: #6b7280;
        }

        .qty-live {
            color: #0f766e;
        }

        .error-text {
            font-size: .76rem;
            color: #dc2626;
            margin-top: .1rem;
        }

        .hint {
            font-size: .78rem;
            color: #94a3b8;
        }

        .btn-primary-rts {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .42rem 1.1rem;
            border-radius: 999px;
            border: none;
            background: var(--rts-main-strong);
            color: #e0f2f1;
            font-size: .82rem;
            font-weight: 600;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .85rem;
            }

            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                border-bottom: 1px solid rgba(148, 163, 184, .28);
                padding: .6rem .45rem;
            }

            .table tbody td {
                display: flex;
                justify-content: space-between;
                gap: .6rem;
                padding: .22rem 0;
                border-top: none;
                font-size: .84rem;
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
                gap: .45rem;
            }

            .td-row-number::before {
                content: '#';
                flex: 0 0 auto;
                color: #94a3b8;
            }

            .btn-primary-rts {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $status = $stockRequest->status;

        $statusLabel = match ($status) {
            'submitted' => 'Menunggu PRD kirim',
            'shipped' => 'Sudah dikirim PRD (ada di Transit)',
            'partial' => 'Sebagian sudah diterima RTS',
            'completed' => 'Selesai',
            default => ucfirst($status),
        };

        $totalRequested = (float) ($totalRequested ?? 0);
        $totalDispatched = (float) ($totalDispatched ?? 0);
        $totalReceived = (float) ($totalReceived ?? 0);

        $totalReceivable = max($totalDispatched - $totalReceived, 0);
    @endphp

    <div class="page-wrap">

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif
        @if ($errors->has('stock'))
            <div class="alert alert-error">{{ $errors->first('stock') }}</div>
        @endif

        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
            <div>
                <div class="step-chip mb-1">
                    <span>Langkah 2 dari 2</span><span>•</span><span>RTS terima dari Transit</span>
                </div>
                <a href="{{ route('rts.stock-requests.show', $stockRequest) }}" class="btn btn-link btn-sm px-0 mb-1">
                    ← Kembali ke detail
                </a>

                <div class="page-title">Terima Barang dari Transit</div>
                <div class="page-subtitle mt-1">
                    Isi qty yang benar-benar kamu terima di RTS.
                    Sistem akan mutasi stok <strong>TRANSIT → RTS</strong>.
                </div>
            </div>

            <div class="text-end">
                <div class="mb-2">
                    <span class="badge-status {{ $status }}">{{ $statusLabel }}</span>
                </div>
                <div class="code-badge">
                    <span class="dot"></span>
                    <span class="mono">{{ $stockRequest->code }}</span>
                </div>
            </div>
        </div>

        <div class="card-main">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="pill-label mb-1">Tanggal & gudang</div>
                        <div class="mb-1 mono">{{ $stockRequest->date?->format('d M Y') ?? '-' }}</div>
                        <div style="font-size:.82rem;">
                            Transit → <span class="muted">{{ $stockRequest->destinationWarehouse?->name ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="pill-label mb-1">Ringkasan</div>
                        <div class="d-flex flex-wrap gap-2">
                            <div class="summary-pill"><span>Diminta</span><strong
                                    class="mono">{{ (int) $totalRequested }}</strong></div>
                            <div class="summary-pill"><span>Sudah dikirim PRD</span><strong
                                    class="mono">{{ (int) $totalDispatched }}</strong></div>
                            <div class="summary-pill"><span>Sudah diterima RTS</span><strong
                                    class="mono">{{ (int) $totalReceived }}</strong></div>
                            <div class="summary-pill summary-pill--sisa"><span>Sisa di Transit</span><strong
                                    class="mono">{{ (int) $totalReceivable }}</strong></div>
                        </div>
                        <div class="hint mt-2">
                            Rule: Qty diterima tidak boleh melebihi <strong>sisa di Transit</strong>.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('rts.stock-requests.finalize', $stockRequest) }}" method="POST" novalidate>
            @csrf

            <div class="card-main">
                <div class="card-body">
                    <div class="pill-label mb-1">Konfirmasi per item</div>

                    <div class="table-wrap">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Item</th>
                                    <th class="text-end" style="width: 90px;">Diminta</th>
                                    <th class="text-end" style="width: 110px;">Sudah dikirim</th>
                                    <th class="text-end" style="width: 110px;">Sudah diterima</th>
                                    <th class="text-end" style="width: 120px;">Stok Transit</th>
                                    <th class="text-end" style="width: 130px;">Terima sekarang</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stockRequest->lines as $index => $line)
                                    @php
                                        $lineId = $line->id;
                                        $requested = (float) $line->qty_request;
                                        $dispatched = (float) ($line->qty_dispatched ?? 0);
                                        $received = (float) ($line->qty_received ?? 0);
                                        $receivable = max($dispatched - $received, 0);

                                        $transitLive = (float) ($liveStocks[$lineId] ?? 0);

                                        $inputName = "lines[{$lineId}][qty_received]";
                                        $inputValue = old($inputName, 0);
                                        $errorKey = "lines.{$lineId}.qty_received";
                                    @endphp
                                    <tr>
                                        <td class="td-row-number row-number" data-label="#">{{ $index + 1 }}</td>

                                        <td data-label="Item">
                                            <div class="fw-semibold">{{ $line->item?->code ?? '-' }}</div>
                                            <div class="line-item-name">{{ $line->item?->name ?? '' }}</div>
                                        </td>

                                        <td class="text-end mono" data-label="Diminta">{{ (int) $requested }}</td>
                                        <td class="text-end mono" data-label="Sudah dikirim">{{ (int) $dispatched }}</td>
                                        <td class="text-end mono" data-label="Sudah diterima">{{ (int) $received }}</td>

                                        <td class="text-end mono qty-live" data-label="Stok Transit">
                                            {{ (int) $transitLive }}</td>

                                        <td class="text-end" data-label="Terima sekarang">
                                            <x-number-input name="{{ $inputName }}" :value="$inputValue" mode="integer"
                                                min="0" class="text-end mono js-qty-received" />
                                            @error($errorKey)
                                                <div class="error-text">{{ $message }}</div>
                                            @enderror
                                            <div class="muted mt-1">
                                                Maks: <span class="mono">{{ (int) $receivable }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="hint">
                            Baris dengan <strong>Terima sekarang = 0</strong> dianggap tidak ada penerimaan.
                            Mutasi yang terjadi: <strong>TRANSIT → RTS</strong>.
                        </div>
                        <button type="submit" class="btn-primary-rts">✔ Simpan & Terima ke RTS</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = Array.from(document.querySelectorAll('.js-qty-received'));
            inputs.forEach((input) => {
                input.addEventListener('focus', function() {
                    setTimeout(() => this.select(), 10);
                });
                input.addEventListener('keydown', function(e) {
                    const currentIndex = inputs.indexOf(this);
                    if (e.key === 'Enter' || e.key === 'ArrowDown') {
                        e.preventDefault();
                        const next = inputs[currentIndex + 1];
                        if (next) next.focus();
                    }
                    if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        const prev = inputs[currentIndex - 1];
                        if (prev) prev.focus();
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
