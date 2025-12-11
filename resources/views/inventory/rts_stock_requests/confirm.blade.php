{{-- resources/views/inventory/rts_stock_requests/confirm.blade.php --}}
@extends('layouts.app')

@section('title', 'RTS • Konfirmasi Fisik • ' . $stockRequest->code)

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
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New",
                monospace;
            font-variant-numeric: tabular-nums;
        }

        .muted {
            color: rgba(148, 163, 184, 1);
            font-size: .78rem;
        }

        .card-main {
            background: var(--card);
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.26);
            margin-bottom: .9rem;
            box-shadow:
                0 10px 24px rgba(15, 23, 42, 0.05),
                0 0 0 1px rgba(15, 23, 42, 0.02);
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

        .qty-text {
            font-variant-numeric: tabular-nums;
        }

        .qty-snapshot {
            color: #0ea5e9;
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

        .hint-danger {
            font-size: .74rem;
            color: #b91c1c;
            margin-top: .08rem;
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
            text-decoration: none;
        }

        .btn-outline-rts:hover {
            filter: brightness(0.98);
            text-decoration: none;
        }

        /* Alert */
        .alert {
            border-radius: 10px;
            padding: .5rem .75rem;
            font-size: .78rem;
            margin-bottom: .55rem;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .alert-warning {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            color: #92400e;
        }

        .alert-neutral {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            color: #4b5563;
        }

        .alert-success {
            background: #ecfdf5;
            border: 1px solid #6ee7b7;
            color: #065f46;
        }

        /* Mobile */
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
            'submitted' => 'Menunggu konfirmasi fisik',
            'partial' => 'Sebagian sudah diterima',
            'completed' => 'Selesai',
            default => ucfirst($status),
        };

        $totalOutstanding = max($totalRequested - $totalPlanned, 0);

        // Deteksi secara kasar: adakah default qty (planned/request) yang bisa bikin stok PRD minus?
        $hasPotentialMinus = false;
        foreach ($stockRequest->lines as $tmpLine) {
            $tmpId = $tmpLine->id;
            $tmpRequested = (float) $tmpLine->qty_request;
            $tmpPlanned = (float) ($tmpLine->qty_issued ?? 0);
            $tmpDefault = $tmpPlanned > 0 ? $tmpPlanned : $tmpRequested;
            $tmpLive = (float) ($liveStocks[$tmpId] ?? 0);

            if ($tmpDefault > $tmpLive) {
                $hasPotentialMinus = true;
                break;
            }
        }
    @endphp

    <div class="page-wrap">

        {{-- ALERT / STATUS BAR --}}
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning">
                {{ session('warning') }}
            </div>
        @endif

        @if ($errors->has('stock') || $errors->has('general'))
            <div class="alert alert-error">
                @if ($errors->has('stock'))
                    <div>{{ $errors->first('stock') }}</div>
                @endif
                @if ($errors->has('general'))
                    <div>{{ $errors->first('general') }}</div>
                @endif
            </div>
        @endif

        @if ($errors->any() && !($errors->has('stock') || $errors->has('general')))
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- Info global tentang stok minus (versi singkat) --}}
        @if ($hasPotentialMinus)
            <div class="alert alert-warning">
                Beberapa baris default Qty lebih besar dari stok PRD. Jika disimpan, stok gudang PRD bisa
                <strong>minus</strong>. Pastikan angka sudah dicek fisik.
            </div>
        @else
            <div class="alert alert-neutral">
                Qty fisik boleh lebih besar dari stok PRD. Jika lebih besar, stok PRD akan
                <strong>minus</strong> (diperbolehkan khusus alur RTS).
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
            <div>
                <div class="step-chip mb-1">
                    <span>Langkah 2 dari 2</span>
                    <span>•</span>
                    <span>Konfirmasi fisik</span>
                </div>
                <div>
                    <a href="{{ route('rts.stock-requests.show', $stockRequest) }}" class="btn btn-link btn-sm px-0 mb-1">
                        ← Kembali ke detail
                    </a>
                </div>
                <div class="page-title">
                    Konfirmasi Fisik Permintaan RTS
                </div>
                <div class="page-subtitle mt-1">
                    Isi qty yang benar-benar diterima di RTS. Sistem akan mutasi stok PRD → RTS sesuai angka ini.
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
                <div class="mt-1 muted">
                    Langkah terakhir sebelum stok PRD → RTS dimutasi.
                </div>
            </div>
        </div>

        <div class="card-main">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="pill-label mb-1">Tanggal & gudang</div>
                        <div class="mb-1 mono">
                            {{ $stockRequest->date?->format('d M Y') ?? '-' }}
                        </div>
                        <div style="font-size:.82rem;">
                            {{ $stockRequest->sourceWarehouse?->name ?? '-' }} →
                            <span class="muted">{{ $stockRequest->destinationWarehouse?->name ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="pill-label mb-1">Ringkasan qty</div>
                        <div class="d-flex flex-wrap gap-2">
                            <div class="summary-pill">
                                <span>Diminta</span>
                                <strong class="mono">{{ (int) $totalRequested }}</strong>
                            </div>
                            <div class="summary-pill">
                                <span>Rencana PRD</span>
                                <strong class="mono">{{ (int) $totalPlanned }}</strong>
                            </div>
                            <div class="summary-pill summary-pill--sisa">
                                <span>Selisih</span>
                                <strong class="mono">{{ (int) $totalOutstanding }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="pill-label mb-1">Catatan</div>
                        <div style="font-size:.82rem;">
                            @if ($stockRequest->notes)
                                {!! nl2br(e($stockRequest->notes)) !!}
                            @else
                                <span class="muted">Tidak ada catatan.</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-3 hint">
                    Fokus di kolom <strong>Qty fisik</strong> saja. Baris yang dibiarkan 0 dianggap tidak ada barang yang
                    diterima.
                </div>
            </div>
        </div>

        <form action="{{ route('rts.stock-requests.finalize', $stockRequest) }}" method="POST" novalidate>
            @csrf

            <div class="card-main">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                        <div class="pill-label">
                            Konfirmasi per item
                        </div>
                        <div class="hint">
                            Gunakan Tab / Enter untuk pindah ke baris berikutnya.
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Item</th>
                                    <th class="text-end" style="width: 90px;">Diminta</th>
                                    <th class="text-end" style="width: 100px;">Rencana PRD</th>
                                    <th class="text-end" style="width: 110px;">Stok PRD</th>
                                    <th class="text-end" style="width: 130px;">Qty fisik</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stockRequest->lines as $index => $line)
                                    @php
                                        $lineId = $line->id;
                                        $requested = (float) $line->qty_request;
                                        $planned = (float) ($line->qty_issued ?? 0);
                                        $snapshot = $line->stock_snapshot_at_request;
                                        $live = (float) ($liveStocks[$lineId] ?? 0);

                                        $inputName = "lines[{$lineId}][qty_received]";
                                        $defaultValue = $planned > 0 ? $planned : $requested;
                                        $inputValue = old($inputName, $defaultValue);
                                        $errorKey = "lines.{$lineId}.qty_received";

                                        // potensi minus dengan nilai default (bukan input user real-time)
                                        $potentialMinus = $defaultValue > $live;
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

                                        <td class="text-end" data-label="Rencana PRD">
                                            <span class="mono qty-text">
                                                {{ (int) $planned }}
                                            </span>
                                        </td>

                                        <td class="text-end" data-label="Stok PRD">
                                            <div class="mono qty-text">
                                                @if (!is_null($snapshot))
                                                    <span class="qty-snapshot">{{ (int) $snapshot }}</span>
                                                    <span class="muted">→</span>
                                                @endif
                                                <span class="qty-live">{{ (int) $live }}</span>
                                            </div>
                                        </td>

                                        <td class="text-end" data-label="Qty fisik">
                                            <x-number-input name="{{ $inputName }}" :value="$inputValue" mode="integer"
                                                min="0" class="text-end mono js-qty-received" />

                                            @error($errorKey)
                                                <div class="error-text">
                                                    {{ $message }}
                                                </div>
                                            @enderror

                                            @if ($live <= 0 && $defaultValue > 0)
                                                <div class="hint-danger">
                                                    Stok PRD 0. Default Qty fisik akan membuat stok PRD minus
                                                    {{ (int) $defaultValue }} pcs.
                                                </div>
                                            @elseif ($potentialMinus && $live > 0)
                                                <div class="hint-danger">
                                                    Default Qty fisik ({{ (int) $defaultValue }}) > stok PRD
                                                    ({{ (int) $live }})
                                                    . Potensi minus
                                                    {{ (int) ($defaultValue - $live) }} pcs.
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="hint">
                            Hanya baris dengan <strong>Qty fisik &gt; 0</strong> yang akan dimutasi PRD → RTS.
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn-primary-rts">
                                ✔ Simpan & mutasi stok PRD → RTS
                            </button>
                        </div>
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
