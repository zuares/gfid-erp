{{-- resources/views/production/sewing_returns/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Return')

@push('head')
    <style>
        :root {
            --r: 14px;
        }

        .page-wrap {
            max-width: 980px;
            margin-inline: auto;
            padding: .7rem .75rem 6rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left, rgba(16, 185, 129, .10) 0, rgba(240, 253, 250, .34) 18%, #f9fafb 55%);
        }

        .card {
            background: var(--card);
            border-radius: var(--r);
            border: 1px solid rgba(148, 163, 184, .16);
            box-shadow: 0 6px 18px rgba(15, 23, 42, .06), 0 0 0 1px rgba(15, 23, 42, .02);
        }

        .card-section {
            padding: .8rem .9rem;
        }

        @media(min-width:768px) {
            .card-section {
                padding: .9rem 1.1rem;
            }

            .page-wrap {
                padding-bottom: 3.5rem;
            }
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .hdr {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .hdr h1 {
            font-size: 1rem;
            font-weight: 900;
            margin: 0;
        }

        .sub {
            font-size: .78rem;
            color: var(--muted);
        }

        .lbl {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 800;
            color: var(--muted);
        }

        .pill {
            border-radius: 999px;
            padding: .12rem .6rem;
            font-size: .7rem;
            background: rgba(148, 163, 184, .10);
        }

        .pill-ok {
            background: rgba(22, 163, 74, .12);
            color: #166534;
        }

        .pill-rj {
            background: rgba(248, 113, 113, .16);
            color: #b91c1c;
        }

        .qty-pill {
            border-radius: 999px;
            padding: .06rem .5rem;
            font-size: .72rem;
            font-weight: 900;
            line-height: 1.05;
            border: 1px solid rgba(148, 163, 184, .35);
            background: rgba(148, 163, 184, .07);
        }

        .pill-belum {
            background: rgba(22, 163, 74, .09);
            color: #15803d;
            border-color: rgba(22, 163, 74, .40);
        }

        .pill-dp {
            background: rgba(245, 158, 11, .14);
            color: rgba(146, 64, 14, 1);
            border-color: rgba(245, 158, 11, .45);
        }

        .row-empty {
            box-shadow: inset 3px 0 0 rgba(148, 163, 184, .22);
            background: rgba(255, 255, 255, .98);
        }

        .row-filled {
            background: radial-gradient(circle at top left, rgba(34, 197, 94, .14) 0, rgba(240, 253, 244, .96) 55%);
            box-shadow: inset 3px 0 0 rgba(22, 163, 74, .95), 0 0 0 1px rgba(187, 247, 208, .86);
        }

        .qty-input {
            font-weight: 650;
            font-size: .82rem;
            text-align: center;
        }

        .qty-input-active {
            border-color: rgba(22, 163, 74, .75);
            box-shadow: 0 0 0 1px rgba(22, 163, 74, .35);
            background: rgba(240, 253, 244, .96);
        }

        .notes-input {
            font-size: .76rem;
        }

        @media(max-width:767.98px) {
            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                border-radius: 14px;
                border: 1px solid rgba(148, 163, 184, .22);
                padding: .52rem .7rem .62rem;
                margin-bottom: .55rem;
                box-shadow: 0 10px 22px rgba(15, 23, 42, .06), 0 0 0 1px rgba(15, 23, 42, .02);
            }

            .table td {
                display: block;
                border: none !important;
                padding: .05rem 0;
            }

            .m-top {
                display: flex;
                justify-content: space-between;
                gap: .65rem;
                align-items: flex-start;
                margin-bottom: .25rem;
            }

            .item-code {
                display: inline-flex;
                align-items: center;
                padding: .22rem .7rem;
                border-radius: 999px;
                font-size: 1.05rem;
                font-weight: 950;
                white-space: nowrap;
                background: rgba(22, 163, 74, .07);
                color: #166534;
                border: 1px solid rgba(22, 163, 74, .24);
            }

            .mini {
                color: var(--muted);
                font-size: .74rem;
                line-height: 1.25;
            }

            .qbox {
                text-align: right;
            }

            .qcap {
                font-size: .60rem;
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: .16em;
                color: var(--muted);
            }

            .qinline {
                display: inline-flex;
                gap: .22rem;
                flex-wrap: wrap;
                justify-content: flex-end;
            }

            .cell-qty {
                display: flex;
                gap: .35rem;
                margin-top: .2rem;
            }

            .form-footer {
                position: fixed;
                right: .8rem;
                bottom: 4.5rem;
                left: auto;
                z-index: 30;
                display: flex;
                flex-direction: row-reverse;
                gap: .4rem;
                background: transparent;
                padding: 0;
                margin: 0;
                border: none;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $selectedPickupId = old('pickup_id', $pickupId ?? null);
        $defaultDate = old('date', optional($selectedPickup?->date)->format('Y-m-d') ?? now()->format('Y-m-d'));

        $itemCodes = ($lines ?? collect())
            ->map(fn($l) => optional(optional($l->bundle)->finishedItem)->code)
            ->filter()
            ->unique()
            ->values();

        $wipMap = $wipStockByItemId ?? [];

        $fmtDay = function ($d) {
            if (!$d) {
                return '-';
            }
            try {
                return function_exists('id_day') ? id_day($d) : \Illuminate\Support\Carbon::parse($d)->format('d/m/Y');
            } catch (\Throwable $e) {
                return '-';
            }
        };
    @endphp

    <div class="page-wrap">

        {{-- Header --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="hdr">
                    <div>
                        <h1>Setor Jahit</h1>
                        <div class="sub">
                            Baris tampil hanya jika stok <b>{{ $wipSewWarehouse?->code ?? 'WIP-SEW' }}</b> masih ada.
                            Qty: <b>Belum</b> = sisa pickup (qty_bundle - returned_ok - returned_reject - direct_picked).
                            <b>DP</b> = qty_direct_picked.
                        </div>
                    </div>

                    <a href="{{ route('production.sewing_pickups.create') }}"
                        class="btn btn-sm btn-outline-success rounded-pill d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam"></i><span>Ambil Jahit</span>
                    </a>
                </div>
            </div>
        </div>

        <form id="sewing-return-form" action="{{ route('production.sewing_returns.store') }}" method="POST">
            @csrf

            {{-- Form header --}}
            <div class="card mb-2">
                <div class="card-section">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3 col-6">
                            <div class="lbl mb-1">Tanggal setor</div>
                            <input type="date" name="date"
                                class="form-control form-control-sm @error('date') is-invalid @enderror"
                                value="{{ $defaultDate }}">
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-5 col-12">
                            <div class="lbl mb-1">Pickup</div>
                            <select name="pickup_id"
                                class="form-select form-select-sm @error('pickup_id') is-invalid @enderror"
                                onchange="if(this.value){ window.location='{{ route('production.sewing_returns.create') }}?pickup_id=' + this.value; }">
                                <option value="">Pilih pickup...</option>

                                @foreach ($pickups as $pickup)
                                    @php
                                        $pickupLines = $pickup->lines ?? collect();

                                        $totalRemaining = $pickupLines->sum(function ($line) {
                                            $qtyBundle = (float) ($line->qty_bundle ?? 0);
                                            $returnedOk = (float) ($line->qty_returned_ok ?? 0);
                                            $returnedRej = (float) ($line->qty_returned_reject ?? 0);
                                            $directPicked = (float) ($line->qty_direct_picked ?? 0);
                                            return max($qtyBundle - ($returnedOk + $returnedRej + $directPicked), 0);
                                        });

                                        $pickupLabelDate = $fmtDay($pickup->date);
                                        $pickupCode = $pickup->code ?? 'PICKUP#' . $pickup->id;
                                        $opName = $pickup->operator?->name ?? '(Tanpa operator)';
                                    @endphp

                                    @if ($totalRemaining > 0.000001)
                                        <option value="{{ $pickup->id }}"
                                            {{ (int) $selectedPickupId === (int) $pickup->id ? 'selected' : '' }}>
                                            {{ $pickupCode }} — {{ $opName }} — {{ $pickupLabelDate }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>

                            @error('pickup_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            @if ($selectedPickup)
                                <input type="hidden" name="operator_id" value="{{ $selectedPickup->operator_id }}">
                            @endif
                        </div>

                        @if ($itemCodes->isNotEmpty())
                            <div class="col-md-4 col-12">
                                <div class="lbl mb-1">Filter item</div>
                                <select class="form-select form-select-sm filter-item-code">
                                    <option value="">Semua item...</option>
                                    @foreach ($itemCodes as $code)
                                        <option value="{{ $code }}">{{ $code }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- client error --}}
            <div id="client-error-box" class="alert alert-warning py-2 small d-none mb-2">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <span id="client-error-text"></span>
            </div>

            {{-- table --}}
            <div class="card mb-2">
                <div class="card-section">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <div class="d-flex flex-wrap gap-2">
                            <span class="pill"><span id="summary-row-filled">0</span> baris</span>
                            <span class="pill pill-ok">OK: <span id="summary-ok">0,00</span></span>
                            <span class="pill pill-rj">RJ: <span id="summary-reject">0,00</span></span>
                        </div>
                    </div>

                    @error('results')
                        <div class="alert alert-danger py-1 small mb-2">{{ $message }}</div>
                    @enderror

                    <div class="table-wrap">
                        <table class="table table-sm align-middle mono mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40px" class="text-center">#</th>
                                    <th>Pickup</th>
                                    <th>Item</th>
                                    <th style="width:210px">Qty</th>
                                    <th style="width:120px" class="text-center">OK</th>
                                    <th style="width:120px" class="text-center">RJ</th>
                                    <th style="width:220px">Catatan</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($lines as $idx => $line)
                                    @php
                                        /** @var \App\Models\SewingPickupLine $line */
                                        $bundle = $line->bundle;
                                        $pickup = $line->sewingPickup ?? $selectedPickup;
                                        $lot = $bundle?->cuttingJob?->lot;

                                        $remainingPickup = (float) ($line->remaining_qty ?? 0);

                                        $itemId = (int) ($bundle?->finishedItem?->id ?? 0);
                                        $wipStock = (float) ($line->wip_stock ?? ($wipMap[$itemId] ?? 0));

                                        // ✅ DP harus dari kolom qty_direct_picked (real)
                                        $directPicked = (float) ($line->qty_direct_picked ?? 0);
                                        $hasDirect = $directPicked > 0.000001;

                                        $pickupDateLabel = $pickup?->date ? $fmtDay($pickup->date) : '-';
                                        $pickupCode = $pickup?->code ?? '';
                                        $operatorCode = $pickup?->operator?->code ?? '';
                                        $operatorName = $pickup?->operator?->name ?? '';

                                        $oldResult = old('results.' . $idx, []);
                                        $defaultOk = $oldResult['qty_ok'] ?? null;
                                        $defaultReject = $oldResult['qty_reject'] ?? null;
                                        $defaultNotes = $oldResult['notes'] ?? null;

                                        $showNotes =
                                            (float) ($defaultReject ?? 0) > 0.000001 ||
                                            (is_string($defaultNotes) && trim($defaultNotes) !== '');
                                    @endphp

                                    <tr class="return-row row-empty" data-row-index="{{ $idx }}"
                                        data-remaining="{{ $remainingPickup }}" data-item-id="{{ $itemId }}"
                                        data-wip-stock="{{ $wipStock }}"
                                        data-item-code="{{ $bundle?->finishedItem?->code }}"
                                        data-operator-code="{{ $operatorCode }}" data-operator-name="{{ $operatorName }}"
                                        data-pickup-date="{{ $pickupDateLabel }}"
                                        data-direct-picked="{{ $directPicked }}">

                                        <input type="hidden" name="results[{{ $idx }}][sewing_pickup_line_id]"
                                            value="{{ $line->id }}">
                                        <input type="hidden" name="results[{{ $idx }}][bundle_id]"
                                            value="{{ $bundle?->id }}">

                                        {{-- mobile header cell --}}
                                        <td class="align-top">
                                            <div class="d-none d-md-flex justify-content-center">
                                                <span class="small text-muted">#{{ $loop->iteration }}</span>
                                            </div>

                                            <div class="m-top d-md-none">
                                                <div>
                                                    <div class="item-code">{{ $bundle?->finishedItem?->code ?? '-' }}</div>
                                                    <div class="mini mt-1">
                                                        {{ $bundle?->finishedItem?->name ?? '' }}
                                                        @if ($lot)
                                                            • LOT: <span class="mono">{{ $lot->code }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="mini mt-1">
                                                        @if ($pickupCode)
                                                            <span class="mono">{{ $pickupCode }}</span> •
                                                        @endif
                                                        {{ $operatorCode ? $operatorCode . ' • ' : '' }}{{ $pickupDateLabel ?: '-' }}
                                                        @if ($bundle?->bundle_code)
                                                            • {{ $bundle->bundle_code }}
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="qbox">
                                                    <div class="qcap">QTY</div>
                                                    <div class="qinline mt-1">
                                                        <span class="qty-pill pill-belum">Belum
                                                            {{ number_format($remainingPickup, 2, ',', '.') }}</span>
                                                        @if ($hasDirect)
                                                            <span class="qty-pill pill-dp">DP
                                                                {{ number_format($directPicked, 2, ',', '.') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Pickup (desktop) --}}
                                        <td class="align-top d-none d-md-table-cell">
                                            <div class="fw-semibold">
                                                @if ($pickupCode)
                                                    <span class="mono">{{ $pickupCode }}</span> •
                                                @endif
                                                {{ $pickupDateLabel ?: '-' }}
                                            </div>
                                            <div class="text-muted small">
                                                {{ $operatorCode ?: '-' }}
                                                @if ($bundle?->bundle_code)
                                                    • {{ $bundle->bundle_code }}
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Item (desktop) --}}
                                        <td class="align-top d-none d-md-table-cell">
                                            <div class="fw-semibold">{{ $bundle?->finishedItem?->code ?? '-' }}</div>
                                            <div class="text-muted small">{{ $bundle?->finishedItem?->name ?? '' }}</div>
                                            @if ($lot)
                                                <div class="text-muted small">LOT: <span
                                                        class="mono">{{ $lot->code }}</span></div>
                                            @endif
                                        </td>

                                        {{-- Qty chips (desktop) --}}
                                        <td class="align-top d-none d-md-table-cell">
                                            <div class="d-flex flex-wrap gap-1">
                                                <span class="qty-pill pill-belum">Belum
                                                    {{ number_format($remainingPickup, 2, ',', '.') }}</span>
                                                @if ($hasDirect)
                                                    <span class="qty-pill pill-dp">DP
                                                        {{ number_format($directPicked, 2, ',', '.') }}</span>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- OK input (desktop single / mobile double) --}}
                                        <td class="align-top">
                                            <div class="d-none d-md-block">
                                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                                    name="results[{{ $idx }}][qty_ok]"
                                                    class="form-control form-control-sm qty-input qty-ok-desktop @error("results.$idx.qty_ok") is-invalid @enderror"
                                                    value="{{ $defaultOk ?? '' }}" placeholder="OK">
                                            </div>

                                            <div class="cell-qty d-md-none">
                                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                                    name="results[{{ $idx }}][qty_ok]"
                                                    class="form-control form-control-sm qty-input qty-ok-mobile @error("results.$idx.qty_ok") is-invalid @enderror"
                                                    value="{{ $defaultOk ?? '' }}" placeholder="OK">

                                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                                    name="results[{{ $idx }}][qty_reject]"
                                                    class="form-control form-control-sm qty-input qty-reject-mobile @error("results.$idx.qty_reject") is-invalid @enderror"
                                                    value="{{ $defaultReject ?? '' }}" placeholder="RJ">
                                            </div>

                                            @error("results.$idx.qty_ok")
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            @error("results.$idx.qty_reject")
                                                <div class="invalid-feedback d-block d-md-none">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        {{-- RJ input (desktop) --}}
                                        <td class="align-top d-none d-md-table-cell">
                                            <input type="number" step="0.01" min="0" inputmode="decimal"
                                                name="results[{{ $idx }}][qty_reject]"
                                                class="form-control form-control-sm qty-input qty-reject-desktop @error("results.$idx.qty_reject") is-invalid @enderror"
                                                value="{{ $defaultReject ?? '' }}" placeholder="RJ">
                                            @error("results.$idx.qty_reject")
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        {{-- Notes --}}
                                        <td class="align-top">
                                            <div class="notes-wrapper {{ $showNotes ? '' : 'd-none' }}">
                                                <input type="text" name="results[{{ $idx }}][notes]"
                                                    class="form-control form-control-sm notes-input @error("results.$idx.notes") is-invalid @enderror"
                                                    value="{{ $defaultNotes ?? '' }}" placeholder="Catatan (opsional)">
                                                @error("results.$idx.notes")
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted small py-3">
                                            @if ($selectedPickupId)
                                                Tidak ada baris yang bisa disetor (stok WIP-SEW habis / sisa pickup 0).
                                            @else
                                                Pilih Sewing Pickup dulu.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-none d-md-block mt-2 text-muted small">
                        Klik baris untuk autofill OK = “Belum”. DP adalah qty yang sudah terpakai Direct Pickup.
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="form-footer d-flex justify-content-between align-items-center">
                <a href="{{ route('production.sewing_returns.index') }}"
                    class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="bi bi-arrow-left"></i><span class="d-none d-sm-inline"> Batal</span>
                </a>

                <button type="submit" id="btn-submit-return" class="btn btn-sm btn-success rounded-pill px-3" disabled>
                    <i class="bi bi-check2 me-1"></i> Simpan
                    <span class="d-none d-sm-inline ms-1 text-white-50" id="btn-submit-return-meta">Belum ada isi</span>
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.return-row');
            const filterSelects = document.querySelectorAll('.filter-item-code');

            const clientErrorBox = document.getElementById('client-error-box');
            const clientErrorText = document.getElementById('client-error-text');

            const summaryRowFilled = document.getElementById('summary-row-filled');
            const summaryOk = document.getElementById('summary-ok');
            const summaryReject = document.getElementById('summary-reject');

            const submitBtn = document.getElementById('btn-submit-return');
            const submitMeta = document.getElementById('btn-submit-return-meta');

            const isMobile = () => window.innerWidth <= 767;

            let nf;
            try {
                nf = new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            } catch (e) {
                nf = {
                    format: n => (n || 0).toFixed(2)
                };
            }

            const parseNum = (val) => {
                const n = parseFloat(val);
                return isNaN(n) ? 0 : n;
            };

            const showClientError = (msg) => {
                if (!clientErrorBox || !clientErrorText) return;
                clientErrorText.textContent = msg;
                clientErrorBox.classList.remove('d-none');
            };
            const hideClientError = () => {
                if (!clientErrorBox || !clientErrorText) return;
                clientErrorText.textContent = '';
                clientErrorBox.classList.add('d-none');
            };

            function getOk(row) {
                const d = row.querySelector('.qty-ok-desktop');
                const m = row.querySelector('.qty-ok-mobile');
                if (isMobile() && m) return parseNum(m.value);
                if (d && d.value !== '') return parseNum(d.value);
                if (m && m.value !== '') return parseNum(m.value);
                return 0;
            }

            function getReject(row) {
                const d = row.querySelector('.qty-reject-desktop');
                const m = row.querySelector('.qty-reject-mobile');
                if (isMobile() && m) return parseNum(m.value);
                if (d && d.value !== '') return parseNum(d.value);
                if (m && m.value !== '') return parseNum(m.value);
                return 0;
            }

            function setOk(row, value) {
                const d = row.querySelector('.qty-ok-desktop');
                const m = row.querySelector('.qty-ok-mobile');
                const v = value > 0 ? value : '';
                if (d) d.value = v;
                if (m) m.value = v;
            }

            function setReject(row, value) {
                const d = row.querySelector('.qty-reject-desktop');
                const m = row.querySelector('.qty-reject-mobile');
                const v = value > 0 ? value : '';
                if (d) d.value = v;
                if (m) m.value = v;
            }

            function rowTotal(row) {
                return getOk(row) + getReject(row);
            }

            function itemId(row) {
                const id = parseInt(row.dataset.itemId || '0', 10);
                return isNaN(id) ? 0 : id;
            }

            function itemWip(row) {
                const v = parseNum(row.dataset.wipStock || '0');
                return v < 0 ? 0 : v;
            }

            function sumUsedOtherRows(itId, excludeRow) {
                let used = 0;
                rows.forEach(r => {
                    if (r === excludeRow) return;
                    if (itemId(r) !== itId) return;
                    used += rowTotal(r);
                });
                return used;
            }

            function clamp(row, showError = false) {
                const remainingPickup = parseNum(row.dataset.remaining || '0');
                let ok = getOk(row);
                let rj = getReject(row);

                if (ok < 0) ok = 0;
                if (rj < 0) rj = 0;

                // clamp per-line
                if (ok + rj > remainingPickup + 0.000001) {
                    const diff = (ok + rj) - remainingPickup;
                    const last = row.dataset.lastChanged || 'ok';
                    if (last === 'reject') rj = Math.max(0, rj - diff);
                    else ok = Math.max(0, ok - diff);

                    if (showError) {
                        const idx = parseInt(row.dataset.rowIndex || '0', 10) + 1;
                        showClientError(`Qty OK+RJ melebihi Belum (baris #${idx}). Input disesuaikan.`);
                    }
                }

                // clamp global per-item (stok real WIP-SEW)
                const itId = itemId(row);
                if (itId > 0) {
                    const wip = itemWip(row);
                    const usedOther = sumUsedOtherRows(itId, row);
                    const available = Math.max(wip - usedOther, 0);

                    if (ok + rj > available + 0.000001) {
                        const diff2 = (ok + rj) - available;
                        const last2 = row.dataset.lastChanged || 'ok';
                        if (last2 === 'reject') rj = Math.max(0, rj - diff2);
                        else ok = Math.max(0, ok - diff2);

                        if (showError) {
                            const code = (row.dataset.itemCode || '').trim() || `Item#${itId}`;
                            showClientError(
                                `Stok WIP-SEW tidak cukup untuk ${code}. Sisa ${nf.format(available)}. Input disesuaikan.`
                                );
                        }
                    }
                }

                setOk(row, ok);
                setReject(row, rj);

                if (showError) hideClientError();
            }

            function updateRowVisual(row) {
                if (rowTotal(row) > 0) {
                    row.classList.add('row-filled');
                    row.classList.remove('row-empty');
                } else {
                    row.classList.remove('row-filled');
                    row.classList.add('row-empty');
                }
            }

            function updateNotesVisibility(row) {
                const rj = getReject(row);
                const wrap = row.querySelector('.notes-wrapper');
                if (!wrap) return;

                if (rj > 0) {
                    wrap.classList.remove('d-none');
                    return;
                }
                const input = wrap.querySelector('input[type="text"]');
                if (!input || input.value === '') wrap.classList.add('d-none');
            }

            function updateSummary() {
                let filled = 0,
                    okSum = 0,
                    rjSum = 0;

                rows.forEach(row => {
                    const ok = getOk(row);
                    const rj = getReject(row);
                    if (ok + rj > 0) filled++;
                    okSum += ok;
                    rjSum += rj;
                });

                if (summaryRowFilled) summaryRowFilled.textContent = String(filled);
                if (summaryOk) summaryOk.textContent = nf.format(okSum);
                if (summaryReject) summaryReject.textContent = nf.format(rjSum);

                if (submitBtn) {
                    submitBtn.disabled = filled <= 0;
                    if (submitMeta) submitMeta.textContent = filled > 0 ? `${filled} baris siap` : 'Belum ada isi';
                }
            }

            // filter item
            filterSelects.forEach(sel => {
                sel.addEventListener('change', function() {
                    const code = this.value || '';
                    filterSelects.forEach(s => {
                        if (s !== sel) s.value = code;
                    });
                    rows.forEach(row => {
                        const rowCode = (row.dataset.itemCode || '').trim();
                        row.hidden = !!(code && rowCode !== code);
                    });
                });
            });

            rows.forEach(row => {
                const okD = row.querySelector('.qty-ok-desktop');
                const okM = row.querySelector('.qty-ok-mobile');
                const rjD = row.querySelector('.qty-reject-desktop');
                const rjM = row.querySelector('.qty-reject-mobile');

                row.addEventListener('click', function(e) {
                    if (e.target.closest('input, select, textarea, button, a')) return;

                    const remaining = parseNum(row.dataset.remaining || '0');
                    const currentOk = getOk(row);
                    const currentRj = getReject(row);

                    if (currentOk === remaining && currentRj === 0) {
                        setOk(row, 0);
                        setReject(row, 0);
                    } else {
                        setOk(row, remaining);
                        setReject(row, 0);
                    }

                    row.dataset.lastChanged = 'ok';
                    clamp(row, true);
                    updateRowVisual(row);
                    updateNotesVisibility(row);
                    updateSummary();

                    const target = isMobile() ? (okM || okD) : (okD || okM);
                    if (target) {
                        target.focus();
                        target.select();
                        target.classList.add('qty-input-active');
                    }
                });

                [okD, okM].forEach(inp => {
                    if (!inp) return;
                    inp.addEventListener('focus', function() {
                        this.select();
                        this.classList.add('qty-input-active');
                    });
                    inp.addEventListener('blur', function() {
                        this.classList.remove('qty-input-active');
                        row.dataset.lastChanged = 'ok';
                        clamp(row, true);
                        updateRowVisual(row);
                        updateSummary();
                    });
                    inp.addEventListener('input', function() {
                        row.dataset.lastChanged = 'ok';
                        clamp(row, true);
                        updateRowVisual(row);
                        updateSummary();
                    });
                });

                [rjD, rjM].forEach(inp => {
                    if (!inp) return;
                    inp.addEventListener('focus', function() {
                        this.select();
                        this.classList.add('qty-input-active');
                    });
                    inp.addEventListener('blur', function() {
                        this.classList.remove('qty-input-active');
                        row.dataset.lastChanged = 'reject';
                        clamp(row, true);
                        updateRowVisual(row);
                        updateNotesVisibility(row);
                        updateSummary();
                    });
                    inp.addEventListener('input', function() {
                        row.dataset.lastChanged = 'reject';
                        clamp(row, true);
                        updateRowVisual(row);
                        updateNotesVisibility(row);
                        updateSummary();
                    });
                });

                clamp(row, false);
                updateRowVisual(row);
                updateNotesVisibility(row);
            });

            updateSummary();
        });
    </script>
@endpush
