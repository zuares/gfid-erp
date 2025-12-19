{{-- resources/views/sales/shipment_returns/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Sales • ' . $shipmentReturn->code)

@push('head')
    <style>
        :root {
            --ret-main: rgba(59, 130, 246, 1);
            --ret-soft: rgba(59, 130, 246, .12);
            --warn-soft: rgba(245, 158, 11, .14);
            --danger-soft: rgba(239, 68, 68, .12);
        }

        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: 1rem .9rem 4.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, .10) 0,
                    rgba(45, 212, 191, .10) 28%,
                    #f9fafb 65%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid var(--line);
            box-shadow:
                0 18px 45px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        body[data-theme="light"] .card {
            background: #ffffff;
        }

        .card-section {
            padding: .9rem 1rem;
            border-bottom: 1px solid rgba(148, 163, 184, .25);
        }

        .card-section:last-child {
            border-bottom: none;
        }

        .header-main {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem 1rem;
            justify-content: space-between;
            align-items: center;
        }

        .code-label {
            font-size: .78rem;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .code-text {
            font-size: 1.2rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .2rem .7rem;
            font-size: .78rem;
            font-weight: 500;
        }

        .badge-draft {
            background: rgba(148, 163, 184, .18);
            color: #475569;
        }

        .badge-submitted {
            background: rgba(59, 130, 246, .18);
            color: #1d4ed8;
        }

        .badge-posted {
            background: rgba(16, 185, 129, .18);
            color: #047857;
        }

        .badge-store {
            border-radius: 999px;
            padding: .18rem .6rem;
            font-size: .78rem;
            background: rgba(59, 130, 246, .14);
            color: #1d4ed8;
        }

        .badge-shipment {
            border-radius: 999px;
            padding: .18rem .6rem;
            font-size: .78rem;
            background: rgba(234, 179, 8, .14);
            color: #92400e;
        }

        .stats-row {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .stat-pill {
            min-width: 140px;
            padding: .55rem .75rem;
            border-radius: 12px;
            background: rgba(15, 23, 42, .02);
            border: 1px dashed rgba(148, 163, 184, .6);
            font-size: .8rem;
        }

        .stat-label {
            color: var(--muted);
            margin-bottom: .1rem;
        }

        .stat-value {
            font-variant-numeric: tabular-nums;
            font-weight: 500;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .scan-form-row {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            align-items: center;
        }

        .scan-form-row .form-control {
            border-radius: 999px;
            font-size: .9rem;
        }

        .input-scan-code {
            min-width: 0;
            flex: 1 1 210px;
        }

        .input-scan-qty {
            width: 90px;
        }

        .btn-scan {
            border-radius: 999px;
            border: 1px solid transparent;
            padding: .45rem 1.1rem;
            font-size: .88rem;
            font-weight: 500;
            background: linear-gradient(135deg, var(--ret-main), #22c55e);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .btn-scan:hover {
            filter: brightness(1.05);
            color: #ffffff;
        }

        .scan-help {
            font-size: .78rem;
            color: var(--muted);
            margin-top: .35rem;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table.lines-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .9rem;
        }

        table.lines-table th,
        table.lines-table td {
            padding: .5rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, .24);
            white-space: nowrap;
        }

        table.lines-table th {
            text-align: left;
            font-size: .78rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--muted);
        }

        table.lines-table tbody tr:hover {
            background: rgba(15, 23, 42, .02);
        }

        tr.is-last-scanned {
            background: rgba(59, 130, 246, .10);
        }

        .qty-input-inline {
            width: 80px;
            font-size: .85rem;
            border-radius: 999px;
        }

        .btn-qty-save {
            border-radius: 999px;
            padding: .2rem .6rem;
            font-size: .75rem;
        }

        .actions-footer {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            justify-content: space-between;
            align-items: center;
        }

        .btn-outline-soft {
            border-radius: 999px;
            padding: .45rem 1.1rem;
            font-size: .86rem;
        }

        .btn-submit {
            border-radius: 999px;
            padding: .45rem 1.1rem;
            font-size: .86rem;
            border: none;
            background: linear-gradient(135deg, #f97316, #f59e0b);
            color: #ffffff;
        }

        .btn-post {
            border-radius: 999px;
            padding: .45rem 1.1rem;
            font-size: .86rem;
            border: none;
            background: linear-gradient(135deg, #16a34a, #22c55e);
            color: #ffffff;
        }

        .btn-submit[disabled],
        .btn-post[disabled] {
            opacity: .5;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .card-section {
                padding: .8rem .75rem;
            }

            table.lines-table th,
            table.lines-table td {
                padding: .45rem .45rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $status = $shipmentReturn->status;
        $lastScannedId = session('last_scanned_return_line_id');
    @endphp

    <div class="page-wrap">
        <div class="card">
            {{-- HEADER --}}
            <div class="card-section">
                <div class="header-main">
                    <div>
                        <div class="code-label">Retur Shipment</div>
                        <div class="code-text mono">{{ $shipmentReturn->code }}</div>
                    </div>

                    <div style="text-align:right;">
                        <div class="mb-1">
                            @if ($status === 'draft')
                                <span class="badge-status badge-draft">Draft</span>
                            @elseif ($status === 'submitted')
                                <span class="badge-status badge-submitted">Submitted</span>
                            @elseif ($status === 'posted')
                                <span class="badge-status badge-posted">Posted</span>
                            @else
                                <span class="badge-status badge-draft">{{ ucfirst($status) }}</span>
                            @endif
                        </div>
                        <div class="mono" style="font-size:.8rem; color:var(--muted);">
                            {{ optional($shipmentReturn->date)->format('d M Y') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- STORE + SHIPMENT INFO --}}
            <div class="card-section">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div style="font-size:.78rem; color:var(--muted); margin-bottom:.15rem;">Store</div>
                        @if ($shipmentReturn->store)
                            <div class="badge-store mono">
                                {{ $shipmentReturn->store->code ?? '-' }} — {{ $shipmentReturn->store->name ?? '-' }}
                            </div>
                        @else
                            <div style="font-size:.85rem; color:var(--muted);">-</div>
                        @endif>
                    </div>

                    <div class="col-md-6">
                        <div style="font-size:.78rem; color:var(--muted); margin-bottom:.15rem;">Shipment Asal</div>
                        @if ($shipmentReturn->shipment)
                            <div class="badge-shipment">
                                <span>Retur dari</span>
                                <span class="mono">{{ $shipmentReturn->shipment->code }}</span>
                            </div>
                        @else
                            <div style="font-size:.85rem; color:var(--muted);">Manual (tanpa link shipment)</div>
                        @endif
                    </div>

                    @if ($shipmentReturn->reason)
                        <div class="col-md-12">
                            <div style="font-size:.78rem; color:var(--muted); margin-bottom:.15rem;">Alasan Retur</div>
                            <div style="font-size:.86rem;">{{ $shipmentReturn->reason }}</div>
                        </div>
                    @endif

                    @if ($shipmentReturn->notes)
                        <div class="col-md-12">
                            <div style="font-size:.78rem; color:var(--muted); margin-bottom:.15rem;">Catatan</div>
                            <div style="font-size:.86rem;">{{ $shipmentReturn->notes }}</div>
                        </div>
                    @endif

                    <div class="col-md-12">
                        <div class="stats-row">
                            <div class="stat-pill">
                                <div class="stat-label">Total Qty Retur</div>
                                <div class="stat-value mono">
                                    {{ number_format((int) $shipmentReturn->total_qty) }}
                                </div>
                            </div>

                            @if ($shipmentReturn->submitted_at)
                                <div class="stat-pill">
                                    <div class="stat-label">Submitted</div>
                                    <div class="stat-value mono">
                                        {{ optional($shipmentReturn->submitted_at)->format('d M Y H:i') }}
                                        @if ($shipmentReturn->submittedBy)
                                            <span style="font-size:.78rem; color:var(--muted);">
                                                • {{ $shipmentReturn->submittedBy->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if ($shipmentReturn->posted_at)
                                <div class="stat-pill">
                                    <div class="stat-label">Posted</div>
                                    <div class="stat-value mono">
                                        {{ optional($shipmentReturn->posted_at)->format('d M Y H:i') }}
                                        @if ($shipmentReturn->postedBy)
                                            <span style="font-size:.78rem; color:var(--muted);">
                                                • {{ $shipmentReturn->postedBy->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- SCAN SECTION (hanya draft) --}}
            @if ($status === 'draft')
                <div class="card-section">
                    <div style="font-size:.85rem; font-weight:500; margin-bottom:.35rem;">
                        Scan Barang Retur
                    </div>

                    @if (session('status') && session('message'))
                        <div class="alert alert-{{ session('status') === 'error' ? 'danger' : 'success' }} py-1 mb-2"
                            style="font-size:.8rem;">
                            {{ session('message') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger small py-1 mb-2">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('sales.shipment_returns.scan_item', $shipmentReturn) }}" method="POST"
                        class="scan-form-row" id="scan-form">
                        @csrf

                        <input type="text" name="scan_code" id="scan_code" class="form-control input-scan-code mono"
                            placeholder="Scan barcode / masukkan kode item..." required autofocus>

                        <input type="number" name="qty" class="form-control input-scan-qty mono" min="1"
                            value="1">

                        <button type="submit" class="btn-scan">
                            <span>+ Tambah</span>
                        </button>
                    </form>

                    <div class="scan-help">
                        Enter / klik tombol tambah untuk scan. Qty default 1, bisa diubah sebelum scan.
                    </div>
                </div>
            @endif

            {{-- LINES TABLE --}}
            <div class="card-section">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.4rem;">
                    <div style="font-size:.85rem; font-weight:500;">
                        Detail Barang Retur
                    </div>
                    <div style="font-size:.78rem; color:var(--muted);">
                        Total baris:
                        <span class="mono">{{ $shipmentReturn->lines->count() }}</span>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="lines-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Shipment Line</th>
                                <th style="width:120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($shipmentReturn->lines as $idx => $line)
                                @php
                                    $isLast = $lastScannedId && $lastScannedId == $line->id;
                                @endphp
                                <tr @class(['is-last-scanned' => $isLast])>
                                    <td class="mono" style="font-size:.8rem;">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td>
                                        <div class="mono" style="font-size:.85rem;">
                                            {{ $line->item->code ?? '-' }}
                                        </div>
                                        <div style="font-size:.82rem;">
                                            {{ $line->item->name ?? '' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($status === 'draft')
                                            <form action="{{ route('sales.shipment_returns.update_line_qty', $line) }}"
                                                method="POST" class="d-inline-flex align-items-center gap-1">
                                                @csrf
                                                @method('PATCH')
                                                <input type="number" name="qty"
                                                    class="form-control qty-input-inline mono" min="0"
                                                    value="{{ (int) $line->qty }}">
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-secondary btn-qty-save">
                                                    Simpan
                                                </button>
                                            </form>
                                        @else
                                            <span class="mono">{{ (int) $line->qty }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($line->shipmentLine && $shipmentReturn->shipment)
                                            <span class="mono" style="font-size:.8rem;">
                                                {{ $shipmentReturn->shipment->code }} / Line
                                                #{{ $line->shipmentLine->id }}
                                            </span>
                                        @else
                                            <span style="font-size:.78rem; color:var(--muted);">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($status === 'draft')
                                            <form action="{{ route('sales.shipment_returns.update_line_qty', $line) }}"
                                                method="POST" onsubmit="return confirm('Hapus baris ini?');">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="qty" value="0">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    style="font-size:.75rem; border-radius:999px;">
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <span style="font-size:.78rem; color:var(--muted);">Terkunci</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="padding:.8rem; font-size:.86rem; color:var(--muted);">
                                        Belum ada item retur.
                                        {{ $status === 'draft' ? 'Scan atau tambah item terlebih dahulu.' : '' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- FOOTER ACTIONS --}}
            <div class="card-section">
                <div class="actions-footer">
                    <div>
                        <a href="{{ route('sales.shipment_returns.index') }}"
                            class="btn btn-outline-soft btn-sm btn-outline-secondary">
                            ← Kembali ke daftar
                        </a>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        {{-- Submit draft -> submitted --}}
                        <form action="{{ route('sales.shipment_returns.submit', $shipmentReturn) }}" method="POST"
                            onsubmit="return confirm('Submit retur ini? Setelah submit tidak bisa scan / edit qty.');">
                            @csrf
                            <button type="submit" class="btn-submit" @disabled($status !== 'draft' || $shipmentReturn->lines->count() === 0)>
                                Submit Retur
                            </button>
                        </form>

                        {{-- Post submitted -> posted (stock in WH-RTS) --}}
                        <form action="{{ route('sales.shipment_returns.post', $shipmentReturn) }}" method="POST"
                            onsubmit="return confirm('Posting retur ini dan tambah stok FG ke WH-RTS?');">
                            @csrf
                            <button type="submit" class="btn-post" @disabled($status !== 'submitted' || $shipmentReturn->lines->count() === 0)>
                                Posting ke WH-RTS
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                const scanInput = document.getElementById('scan_code');
                if (scanInput) {
                    scanInput.addEventListener('focus', function() {
                        this.select();
                    });
                }

                const form = document.getElementById('scan-form');
                if (form) {
                    form.addEventListener('submit', function() {
                        if (scanInput && scanInput.value.trim() !== '') {
                            setTimeout(function() {
                                scanInput.focus();
                                scanInput.select();
                            }, 150);
                        }
                    });
                }
            })();
        </script>
    @endpush
@endsection
