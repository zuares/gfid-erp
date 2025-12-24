{{-- resources/views/inventory/rts_stock_requests/show.blade.php --}}
@extends('layouts.app')

@section('title', 'RTS • ' . $stockRequest->code)

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-soft: rgba(45, 212, 191, .14);
            --warn-soft: rgba(245, 158, 11, .14);
            --danger-soft: rgba(239, 68, 68, .12);
        }

        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: .85rem .85rem 4.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, .10) 0,
                    rgba(45, 212, 191, .12) 28%,
                    #f9fafb 65%);
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(15, 23, 42, 0.9) 0,
                    #020617 65%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .30);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, .06),
                0 0 0 1px rgba(15, 23, 42, .03);
            padding: .8rem .85rem;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .meta {
            font-size: .82rem;
            opacity: .82;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            flex-wrap: wrap;
            margin-bottom: .75rem;
        }

        .title {
            margin: 0;
            font-size: 1.12rem;
            font-weight: 900;
            letter-spacing: -.01em;
        }

        .sub {
            margin-top: .18rem;
        }

        .actions {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-primary {
            background: var(--rts-main);
            border-color: var(--rts-main);
            color: #022c22;
        }

        .btn-outline {
            border: 1px solid rgba(148, 163, 184, .45);
            background: transparent;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .55rem;
        }

        .stat {
            background: rgba(148, 163, 184, .06);
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 12px;
            padding: .55rem .6rem;
        }

        .stat .k {
            font-size: .72rem;
            opacity: .72;
            line-height: 1.1;
        }

        .stat .v {
            margin-top: .12rem;
            font-size: 1.12rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .chips {
            display: flex;
            gap: .45rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .16rem .52rem;
            border-radius: 999px;
            font-size: .78rem;
            border: 1px solid rgba(148, 163, 184, .35);
            background: rgba(148, 163, 184, .10);
            white-space: nowrap;
            font-weight: 800;
        }

        .chip.ok {
            border-color: rgba(16, 185, 129, .35);
            background: rgba(16, 185, 129, .14);
        }

        .chip.warn {
            border-color: rgba(245, 158, 11, .40);
            background: var(--warn-soft);
        }

        .chip.danger {
            border-color: rgba(239, 68, 68, .40);
            background: var(--danger-soft);
        }

        .line {
            border-top: 1px dashed rgba(148, 163, 184, .35);
            margin: .7rem 0;
        }

        .note {
            border: 1px solid rgba(148, 163, 184, .25);
            border-radius: 12px;
            padding: .65rem .75rem;
            background: rgba(148, 163, 184, .08);
            font-size: .85rem;
            opacity: .92;
            white-space: pre-wrap;
        }

        /* =========================
                  TABLE (DESKTOP) + STACK (MOBILE)
               ========================== */
        .table-wrap {
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 12px;
        }

        .tbl {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }

        .tbl th,
        .tbl td {
            padding: .55rem .55rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            vertical-align: top;
            font-size: .9rem;
        }

        .tbl thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: var(--card);
            font-size: .78rem;
            letter-spacing: .02em;
            text-transform: uppercase;
            opacity: .75;
            border-bottom: 1px solid rgba(148, 163, 184, .26);
        }

        .tbl tbody tr:hover {
            background: rgba(148, 163, 184, .05);
        }

        .td-right {
            text-align: right;
            white-space: nowrap;
        }

        .td-center {
            text-align: center;
            white-space: nowrap;
        }

        .no {
            width: 44px;
            opacity: .75;
        }

        .item-cell {
            min-width: 100px;
        }

        .item-code {
            font-weight: 900;
        }

        .item-name {
            margin-top: .12rem;
            font-size: .82rem;
            opacity: .82;
        }

        .item-badges {
            margin-top: .35rem;
            display: flex;
            gap: .35rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .mini {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .12rem .45rem;
            border-radius: 999px;
            font-size: .74rem;
            border: 1px solid rgba(148, 163, 184, .30);
            background: rgba(148, 163, 184, .10);
            white-space: nowrap;
            font-weight: 800;
        }

        .mini.ok {
            border-color: rgba(16, 185, 129, .30);
            background: rgba(16, 185, 129, .12);
        }

        .mini.warn {
            border-color: rgba(245, 158, 11, .38);
            background: var(--warn-soft);
        }

        .mini.danger {
            border-color: rgba(239, 68, 68, .38);
            background: var(--danger-soft);
        }

        /* Mobile stacked rows */
        @media (max-width: 780px) {
            .tbl {
                min-width: 0;
                width: 100%;
            }

            .table-wrap {
                border: none;
                border-radius: 0;
                overflow: visible;
            }

            .tbl thead {
                display: none;
            }

            .tbl,
            .tbl tbody,
            .tbl tr,
            .tbl td {
                display: block;
                width: 100%;
            }

            .tbl tr {
                border: 1px solid rgba(148, 163, 184, .22);
                border-radius: 12px;
                padding: .6rem .65rem;
                background: rgba(148, 163, 184, .05);
                margin-bottom: .55rem;
            }

            .tbl td {
                border-bottom: none;
                padding: .22rem 0;
            }

            .tbl td[data-k]::before {
                content: attr(data-k);
                display: inline-block;
                width: 92px;
                font-size: .72rem;
                opacity: .7;
                text-transform: uppercase;
                letter-spacing: .02em;
                margin-right: .5rem;
            }

            .td-right,
            .td-center {
                text-align: left;
            }
        }

        /* =====================================================
                  RTS DIRECT PICKUP MODAL
               ====================================================== */
        #rtsDpOverlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .55);
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        #rtsDpOverlay.is-open {
            display: flex !important;
        }

        #rtsDpOverlay .rts-dp-panel {
            width: min(960px, 100%);
            max-height: calc(100vh - 2rem);
            overflow: auto;
            background: var(--card);
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, .25);
            box-shadow: 0 18px 45px rgba(15, 23, 42, .35);
            padding: .9rem .9rem 1rem;
            transform: translateY(10px);
            opacity: 0;
            transition: transform .18s ease, opacity .18s ease;
        }

        #rtsDpOverlay.is-open .rts-dp-panel {
            transform: translateY(0);
            opacity: 1;
        }

        .modal-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            position: sticky;
            top: 0;
            background: var(--card);
            padding-top: .1rem;
            padding-bottom: .55rem;
            z-index: 2;
        }

        .modal-title {
            font-weight: 900;
            font-size: 1.02rem;
            margin: 0;
        }

        .dp-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: .65rem;
        }

        .dp-table th,
        .dp-table td {
            padding: .58rem .55rem;
            border-bottom: 1px solid rgba(148, 163, 184, .22);
            vertical-align: top;
            font-size: .9rem;
        }

        .dp-table th {
            text-align: left;
            font-size: .78rem;
            opacity: .75;
        }

        .num {
            width: 140px;
            max-width: 100%;
            padding: .45rem .55rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: var(--card);
            color: inherit;
        }

        .sel {
            width: 100%;
            padding: .55rem .6rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: var(--card);
            color: inherit;
        }

        .err {
            margin-top: .35rem;
            font-size: .82rem;
            color: rgba(239, 68, 68, 1);
        }

        @media (max-width: 980px) {
            .page-wrap {
                padding: .75rem .75rem 5rem;
            }

            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            #rtsDpOverlay {
                align-items: flex-end;
                padding: .7rem;
            }

            #rtsDpOverlay .rts-dp-panel {
                width: 100%;
                border-radius: 18px 18px 14px 14px;
                max-height: calc(100vh - 1.2rem);
                transform: translateY(22px);
            }

            .rts-dp-handle {
                width: 56px;
                height: 5px;
                border-radius: 999px;
                background: rgba(148, 163, 184, .55);
                margin: .1rem auto .55rem;
            }

            .dp-table thead {
                display: none;
            }

            .dp-table,
            .dp-table tbody,
            .dp-table tr,
            .dp-table td {
                display: block;
                width: 100%;
            }

            .dp-table tr {
                padding: .45rem 0;
            }

            .dp-table td {
                border-bottom: none;
                padding: .35rem 0;
            }

            .dp-table tr+tr {
                border-top: 1px solid rgba(148, 163, 184, .22);
            }
        }
    </style>
@endpush

@section('content')
    @php
        use App\Models\Employee;

        // Supaya blade bisa jalan tanpa ubah controller show:
        $dpOperators = Employee::query()->orderBy('code')->get();
    @endphp

    <div class="page-wrap">

        {{-- =======================
            HEADER
        ======================== --}}
        <div class="header-row">
            <div>
                <h1 class="title mono">{{ $stockRequest->code }}</h1>
                <div class="meta sub">
                    {{ optional($stockRequest->date)->format('d M Y') }}
                    · {{ $stockRequest->sourceWarehouse->code ?? '-' }}
                    → {{ $stockRequest->destinationWarehouse->code ?? '-' }}
                </div>
            </div>

            <div class="actions">
                <x-status-pill :status="$stockRequest->status" />
                <a href="{{ route('rts.stock-requests.index') }}" class="btn btn-outline">← List</a>
            </div>
        </div>

        {{-- =======================
            SUMMARY
        ======================== --}}
        @php
            $reqTotal = (float) $stockRequest->lines->sum('qty_request');
            $dispTotal = (float) $stockRequest->lines->sum('qty_dispatched');
            $recvTotal = (float) $stockRequest->lines->sum('qty_received');
            $pickTotal = (float) $stockRequest->lines->sum('qty_picked');

            $outRtsTotal = max($reqTotal - $recvTotal - $pickTotal, 0);
            $inTransitTotal = max($dispTotal - $recvTotal, 0);

            if ($outRtsTotal <= 0.0000001) {
                $outCls = 'chip ok';
                $outLabel = 'AMAN';
            } elseif ($outRtsTotal >= $reqTotal * 0.5 && $reqTotal > 0) {
                $outCls = 'chip danger';
                $outLabel = 'URGENT';
            } else {
                $outCls = 'chip warn';
                $outLabel = 'BUTUH';
            }

            // Keterangan status global RTS
            $statusNote = match ($stockRequest->status) {
                'submitted' => 'RTS menunggu proses dari produksi / gudang asal. Belum ada barang dikirim.',
                'shipped' => 'Sebagian / seluruh permintaan sudah dikirim dari produksi dan sedang dalam Transit.',
                'partial' => 'Sebagian permintaan sudah diterima RTS, masih ada barang Transit atau Sisa RTS.',
                'completed' => 'Semua permintaan sudah terpenuhi. RTS selesai.',
                default => null,
            };
        @endphp

        <div class="card">
            <div class="stats">
                <div class="stat">
                    <div class="k">Total Request</div>
                    <div class="v mono">{{ $reqTotal }}</div>
                </div>
                <div class="stat">
                    <div class="k">Transit</div>
                    <div class="v mono">{{ $inTransitTotal }}</div>
                </div>
                <div class="stat">
                    <div class="k">Sisa RTS</div>
                    <div class="v mono">{{ $outRtsTotal }}</div>
                </div>
            </div>

            <div
                style="margin-top:.6rem;display:flex;justify-content:space-between;gap:.6rem;flex-wrap:wrap;align-items:center">
                <div class="chips">
                    {{-- Badge kondisi sisa RTS --}}
                    <span class="{{ $outCls }}">
                        {{ $outLabel }} · Sisa
                        <span class="mono">{{ $outRtsTotal }}</span>
                    </span>

                    {{-- Badge status permintaan dikirim --}}
                    @if ($dispTotal > 0.0000001)
                        <span class="chip ok">
                            Permintaan dikirim · Transit
                            <span class="mono">{{ $inTransitTotal }}</span>
                        </span>
                    @else
                        <span class="chip">
                            Belum ada kirim dari produksi
                        </span>
                    @endif

                    {{-- Badge Direct Pickup jika terpakai --}}
                    @if ($pickTotal > 0.0000001)
                        <span class="chip warn">
                            Direct Pickup · Pickup
                            <span class="mono">{{ $pickTotal }}</span>
                        </span>
                    @endif
                </div>

                {{-- Aksi dinamis --}}
                <div class="actions">
                    @php
                        // Boleh terima dari Transit kalau:
                        // - status sudah 'shipped' / 'partial' (produksi sudah proses)
                        // - masih ada qty Transit
                        $canReceive =
                            in_array($stockRequest->status, ['shipped', 'partial'], true) &&
                            $inTransitTotal > 0.0000001;

                        // Boleh Direct Pickup kalau:
                        // - status minimal 'submitted'
                        // - masih ada Sisa RTS
                        $canPickup =
                            in_array($stockRequest->status, ['submitted', 'shipped', 'partial'], true) &&
                            $outRtsTotal > 0.0000001;
                    @endphp

                    @if ($canReceive || $canPickup)
                        @if ($canReceive)
                            <a href="{{ route('rts.stock-requests.confirm', $stockRequest) }}" class="btn btn-primary">
                                Terima (Transit → RTS)
                            </a>
                        @endif

                        @if ($canPickup)
                            <button type="button" class="btn btn-outline" data-rts-dp-open>
                                Ambil Dari Penjahit (Direct Pickup)
                            </button>
                        @endif
                    @else
                        <span class="chip ok">
                            RTS selesai · tidak ada sisa permintaan
                        </span>
                    @endif
                </div>
            </div>

            {{-- Keterangan status di bawah summary --}}
            @if ($statusNote)
                <div class="meta" style="margin-top:.5rem">
                    Keterangan: {{ $statusNote }}
                </div>
            @endif

            @if ($stockRequest->notes)
                <div class="line"></div>
                <div class="note">{{ $stockRequest->notes }}</div>
            @endif
        </div>

        {{-- =======================
            ITEMS TABLE
        ======================== --}}
        <div class="card" style="margin-top:.85rem">
            <div style="display:flex;justify-content:space-between;align-items:baseline;gap:.6rem;flex-wrap:wrap">
                <div style="font-weight:900;letter-spacing:-.01em">Daftar Item</div>
                <div class="meta">{{ $stockRequest->lines->count() }} item</div>
            </div>

            <div class="line"></div>

            <div class="table-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th class="no">No</th>
                            <th class="item-cell">Item</th>
                            <th class="status-cell">Status</th>
                            <th class="td-right">Req</th>
                            <th class="td-right">Kirim</th>
                            <th class="td-right">Terima</th>
                            <th class="td-right">Sisa RTS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stockRequest->lines as $i => $line)
                            @php
                                $req = (float) $line->qty_request;
                                $disp = (float) $line->qty_dispatched;
                                $recv = (float) $line->qty_received;
                                $pick = (float) $line->qty_picked;

                                $outRts = max($req - $recv - $pick, 0);
                                $inTransit = max($disp - $recv, 0);

                                if ($outRts <= 0.0000001) {
                                    $lCls = 'chip ok';
                                    $lLbl = 'AMAN';
                                } elseif ($outRts >= $req * 0.5 && $req > 0) {
                                    $lCls = 'chip danger';
                                    $lLbl = 'URGENT';
                                } else {
                                    $lCls = 'chip warn';
                                    $lLbl = 'BUTUH';
                                }

                                $bTransitCls =
                                    $inTransit > 0.0000001
                                        ? ($inTransit >= $req * 0.5 && $req > 0
                                            ? 'mini warn'
                                            : 'mini ok')
                                        : 'mini';

                                $bPickCls = $pick > 0.0000001 ? 'mini ok' : 'mini';
                            @endphp

                            <tr>
                                <td class="no td-center" data-k="No">{{ $i + 1 }}</td>

                                <td class="item-cell" data-k="Item">
                                    <div class="item-code mono">{{ $line->item->code }}</div>
                                    <div class="item-name">{{ $line->item->name }}</div>

                                    <div class="item-badges">
                                        <span class="{{ $bTransitCls }}">
                                            Transit <span class="mono">{{ $inTransit }}</span>
                                        </span>

                                        <span class="{{ $bPickCls }}">
                                            Pickup <span class="mono">{{ $pick }}</span>
                                        </span>

                                        <span
                                            class="mini {{ $outRts <= 0.0000001 ? 'ok' : ($outRts >= $req * 0.5 && $req > 0 ? 'danger' : 'warn') }}">
                                            Sisa <span class="mono">{{ $outRts }}</span>
                                        </span>
                                    </div>
                                </td>

                                <td class="status-cell" data-k="Status">
                                    <span class="{{ $lCls }}">{{ $lLbl }}</span>
                                </td>

                                <td class="td-right mono" data-k="Req">{{ $req }}</td>
                                <td class="td-right mono" data-k="Kirim">{{ $disp }}</td>
                                <td class="td-right mono" data-k="Terima">{{ $recv }}</td>
                                <td class="td-right mono" data-k="Sisa RTS">{{ $outRts }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- =======================
            DIRECT PICKUP MODAL
        ======================== --}}
        <div id="rtsDpOverlay" aria-hidden="true">
            <div class="rts-dp-panel" role="dialog" aria-modal="true" aria-label="Direct Pickup">
                <div class="rts-dp-handle" aria-hidden="true"></div>

                <div class="modal-head">
                    <div>
                        <h3 class="modal-title">Direct Pickup (Penjahit/WIP → RTS)</h3>
                        <div class="meta">
                            Wajib pilih penjahit. Sistem akan ambil stok dari Sewing Pickup tertua (FIFO) berdasarkan
                            <b>operator + tanggal pickup</b>.
                            Max input per item = <b>Sisa RTS</b> (Req - Terima - Pickup).
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline" data-rts-dp-close>Tutup</button>
                </div>

                <div class="line"></div>

                <form method="POST" action="{{ route('rts.stock-requests.direct-pickup', $stockRequest) }}"
                    id="rtsDpForm">
                    @csrf

                    {{-- OPERATOR (WAJIB) --}}
                    <div style="margin-bottom:.75rem">
                        <label class="meta" style="display:block;margin-bottom:.25rem">
                            Penjahit / Operator <b style="color:rgba(239,68,68,1)">*</b>
                        </label>
                        <select name="operator_id" class="sel" required>
                            <option value="">— Pilih Penjahit —</option>
                            @foreach ($dpOperators as $op)
                                <option value="{{ $op->id }}" @selected((string) old('operator_id') === (string) $op->id)>
                                    {{ $op->code }} — {{ $op->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('operator_id')
                            <div class="err">{{ $message }}</div>
                        @enderror
                    </div>

                    <table class="dp-table">
                        <thead>
                            <tr>
                                <th style="width:48%">Item</th>
                                <th style="width:26%">Info</th>
                                <th style="width:26%">Qty Pickup</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stockRequest->lines as $line)
                                @php
                                    $req = (float) $line->qty_request;
                                    $recv = (float) $line->qty_received;
                                    $pick = (float) $line->qty_picked;
                                    $maxPick = max($req - $recv - $pick, 0);
                                    $old = old("lines.{$line->id}.qty_picked", 0);
                                @endphp

                                <tr style="{{ $maxPick <= 0.0000001 ? 'opacity:.55' : '' }}">
                                    <td>
                                        <div class="mono" style="font-weight:900">{{ $line->item->code }}</div>
                                        <div class="meta">{{ $line->item->name }}</div>
                                    </td>

                                    <td>
                                        <div class="meta">
                                            Req <b class="mono">{{ $req }}</b>
                                            · Terima <b class="mono">{{ $recv }}</b>
                                            · Pickup <b class="mono">{{ $pick }}</b>
                                        </div>
                                        <div class="meta" style="margin-top:.2rem">
                                            Sisa <b class="mono">{{ $maxPick }}</b>
                                        </div>
                                    </td>

                                    <td>
                                        <input class="num js-rts-pick" type="number" step="0.01" min="0"
                                            max="{{ $maxPick }}" name="lines[{{ $line->id }}][qty_picked]"
                                            value="{{ $old }}" data-max="{{ $maxPick }}"
                                            inputmode="decimal" {{ $maxPick <= 0.0000001 ? 'disabled' : '' }}>
                                        @error("lines.{$line->id}.qty_picked")
                                            <div class="err">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div style="margin-top:.75rem">
                        <label class="meta" style="display:block;margin-bottom:.25rem">Catatan (opsional)</label>
                        <textarea name="notes" rows="2"
                            style="width:100%;border-radius:12px;border:1px solid rgba(148,163,184,.35);padding:.55rem .6rem;background:var(--card);color:inherit">{{ old('notes') }}</textarea>
                    </div>

                    <div style="display:flex;gap:.6rem;justify-content:flex-end;flex-wrap:wrap;margin-top:1rem">
                        <button type="button" class="btn btn-outline" id="rtsDpClear">Kosongkan</button>
                        <button type="submit" class="btn btn-primary" id="rtsDpSubmit">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        (function() {
            const overlay = document.getElementById('rtsDpOverlay');
            const form = document.getElementById('rtsDpForm');
            const btnSubmit = document.getElementById('rtsDpSubmit');
            const btnClear = document.getElementById('rtsDpClear');
            if (!overlay || !form) return;

            const getInputs = () => Array.from(overlay.querySelectorAll('.js-rts-pick'));
            const getOperator = () => overlay.querySelector('select[name="operator_id"]');

            function clamp(el) {
                if (!el || el.disabled) return;
                const max = parseFloat(el.dataset.max || el.getAttribute('max') || '0') || 0;

                let v = (el.value ?? '').toString().trim();
                if (v === '') v = '0';
                v = v.replace(',', '.');
                let n = parseFloat(v);
                if (Number.isNaN(n) || n < 0) n = 0;
                if (n > max) n = max;

                n = Math.round(n * 100) / 100;
                const s = (n % 1 === 0) ? String(n.toFixed(0)) : String(n);
                el.value = s;
            }

            function openDp() {
                overlay.classList.add('is-open');
                overlay.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';

                const op = getOperator();
                setTimeout(() => {
                    if (op) op.focus();
                }, 40);
            }

            function closeDp() {
                overlay.classList.remove('is-open');
                overlay.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            document.addEventListener('click', function(e) {
                if (e.target.closest('[data-rts-dp-open]')) {
                    e.preventDefault();
                    return openDp();
                }
                if (e.target.closest('[data-rts-dp-close]')) {
                    e.preventDefault();
                    return closeDp();
                }
                if (e.target === overlay && overlay.classList.contains('is-open')) {
                    return closeDp();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && overlay.classList.contains('is-open')) closeDp();
            });

            overlay.addEventListener('input', function(e) {
                const el = e.target;
                if (el?.classList?.contains('js-rts-pick')) clamp(el);
            });

            overlay.addEventListener('change', function(e) {
                const el = e.target;
                if (el?.classList?.contains('js-rts-pick')) clamp(el);
            });

            overlay.addEventListener('blur', function(e) {
                const el = e.target;
                if (el?.classList?.contains('js-rts-pick')) clamp(el);
            }, true);

            btnClear?.addEventListener('click', function() {
                getInputs().forEach(i => {
                    if (!i.disabled) {
                        i.value = 0;
                        clamp(i);
                    }
                });
                const op = getOperator();
                op?.focus();
            });

            form.addEventListener('submit', function(e) {
                // operator required (extra guard)
                const op = getOperator();
                if (op && !op.value) {
                    e.preventDefault();
                    alert('Pilih penjahit/operator terlebih dahulu.');
                    op.focus();
                    return false;
                }

                let any = false;
                getInputs().forEach(i => {
                    clamp(i);
                    if (!i.disabled && (parseFloat(i.value || '0') || 0) > 0) any = true;
                });

                if (!any) {
                    e.preventDefault();
                    alert('Isi minimal 1 item (qty > 0).');
                    return false;
                }

                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.innerText = 'Memproses...';
                }
            });

            // Auto-open if validation errors related to modal
            @if ($errors->has('operator_id') || $errors->has('lines.*.qty_picked') || $errors->has('lines'))
                openDp();
            @endif
        })();
    </script>
@endsection
