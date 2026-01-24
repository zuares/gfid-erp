{{-- resources/views/inventory/rts_stock_requests/confirm.blade.php --}}
@extends('layouts.app')

@section('title', 'RTS Receive • ' . $stockRequest->code)

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-soft: rgba(45, 212, 191, .14);
            --warn-soft: rgba(245, 158, 11, .14);
            --danger-soft: rgba(239, 68, 68, .12);
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .85rem .85rem 4.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, .10) 0,
                    rgba(45, 212, 191, .12) 28%,
                    #f9fafb 65%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .30);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06), 0 0 0 1px rgba(15, 23, 42, .03);
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

        .line {
            border-top: 1px dashed rgba(148, 163, 184, .35);
            margin: .7rem 0;
        }

        /* Table */
        .table-wrap {
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 12px;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
        }

        .tbl {
            width: 100%;
            border-collapse: collapse;
            min-width: 860px;
        }

        .tbl th,
        .tbl td {
            padding: .55rem .55rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            vertical-align: middle;
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
            opacity: .8;
            border-bottom: 1px solid rgba(148, 163, 184, .26);
        }

        .tbl tbody tr:hover {
            background: rgba(148, 163, 184, .05);
        }

        .td-right {
            text-align: right;
            white-space: nowrap;
        }

        .item-cell {
            min-width: 360px;
        }

        .item-code {
            font-weight: 900;
        }

        .item-name {
            margin-top: .12rem;
            font-size: .82rem;
            opacity: .82;
        }

        .num {
            width: 140px;
            max-width: 100%;
            padding: .42rem .55rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: var(--card);
            color: inherit;
            text-align: right;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: .14rem .5rem;
            border-radius: 999px;
            font-size: .74rem;
            border: 1px solid rgba(148, 163, 184, .28);
            background: rgba(148, 163, 184, .10);
            font-weight: 800;
            white-space: nowrap;
        }

        .badge.warn {
            border-color: rgba(245, 158, 11, .40);
            background: var(--warn-soft);
        }

        .badge.danger {
            border-color: rgba(239, 68, 68, .40);
            background: var(--danger-soft);
        }

        .badge.ok {
            border-color: rgba(16, 185, 129, .35);
            background: rgba(16, 185, 129, .14);
        }

        .err {
            margin-top: .35rem;
            font-size: .82rem;
            color: rgba(239, 68, 68, 1);
        }

        @media (max-width: 820px) {
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
                width: 110px;
                font-size: .72rem;
                opacity: .7;
                text-transform: uppercase;
                letter-spacing: .02em;
                margin-right: .5rem;
            }

            .td-right {
                text-align: left;
            }

            .num {
                width: 180px;
                text-align: left;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <div class="header-row">
            <div>
                <h1 class="title mono">{{ $stockRequest->code }} — Receive</h1>
                <div class="meta sub">
                    {{ optional($stockRequest->date)->format('d M Y') }}
                    · {{ $stockRequest->sourceWarehouse->code ?? 'WH-PRD' }} →
                    {{ $stockRequest->destinationWarehouse->code ?? 'WH-RTS' }}
                </div>
            </div>

            <div class="actions">
                <x-status-pill :status="$stockRequest->status" />
                <a href="{{ route('rts.stock-requests.show', $stockRequest) }}" class="btn btn-outline">← Kembali</a>
            </div>
        </div>

        <form id="formReceive" method="POST" action="{{ route('rts.stock-requests.finalize', $stockRequest) }}">
            @csrf

            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:.6rem;flex-wrap:wrap">
                    <div style="font-weight:900;letter-spacing:-.01em">Input Qty Terima</div>

                    <div class="actions">
                        <button type="button" class="btn btn-outline" id="btnFillReq">Isi = Req</button>
                        <button type="button" class="btn btn-outline" id="btnClearAll">Kosongkan</button>
                        <button class="btn btn-primary" type="submit">Simpan</button>
                    </div>
                </div>

                <div class="line"></div>

                <div class="table-wrap">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="td-right">Req</th>
                                <th class="td-right">PRD Live</th>
                                <th class="td-right">Qty Terima</th>
                                <th class="td-right">Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($stockRequest->lines as $line)
                                @php
                                    $req = (float) ($line->qty_request ?? 0);
                                    $live = (float) ($liveStocks[$line->id] ?? 0);
                                    $old = old("lines.{$line->id}.qty_received", 0);

                                    // status awal (berdasarkan live)
                                    $liveBadge = $live <= 0.0000001 ? 'badge warn' : 'badge ok';
                                    $liveText = $live <= 0.0000001 ? 'PRD Minus' : 'OK';
                                @endphp

                                <tr>
                                    <td class="item-cell" data-k="Item">
                                        <div class="item-code mono">{{ $line->item->code }}</div>
                                        <div class="item-name">{{ $line->item->name }}</div>
                                    </td>

                                    <td class="td-right mono" data-k="Req">
                                        {{ rtrim(rtrim(number_format($req, 2, '.', ''), '0'), '.') }}
                                    </td>

                                    <td class="td-right mono" data-k="PRD Live">
                                        {{ rtrim(rtrim(number_format($live, 2, '.', ''), '0'), '.') }}
                                    </td>

                                    <td class="td-right" data-k="Qty Terima">
                                        <input class="num js-recv" type="number" step="0.01" min="0"
                                            name="lines[{{ $line->id }}][qty_received]" value="{{ $old }}"
                                            data-req="{{ $req }}" data-live="{{ $live }}"
                                            inputmode="decimal">
                                        @error("lines.{$line->id}.qty_received")
                                            <div class="err">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <td class="td-right" data-k="Status">
                                        <span class="{{ $liveBadge }} js-status" data-live="{{ $live }}">
                                            {{ $liveText }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @error('stock')
                    <div class="err" style="margin-top:.75rem">{{ $message }}</div>
                @enderror
            </div>
        </form>
    </div>

    <script>
        (function() {
            function toNum(x) {
                const n = parseFloat(String(x ?? '').replace(',', '.'));
                return Number.isFinite(n) ? n : 0;
            }

            function fmtBadge(el, qty) {
                const req = toNum(el.dataset.req || 0);
                const live = toNum(el.dataset.live || 0);

                // cari badge status di row yang sama
                const row = el.closest('tr');
                const badge = row ? row.querySelector('.js-status') : null;
                if (!badge) return;

                // default
                badge.className = 'badge js-status';
                let text = (live <= 0.0000001) ? 'PRD Minus' : 'OK';

                if (qty <= 0.0000001) {
                    badge.classList.add('badge');
                    badge.textContent = '-';
                    return;
                }

                if (qty > req + 0.0000001 && req > 0) {
                    badge.classList.add('danger');
                    badge.textContent = 'Over';
                    return;
                }

                if (live <= 0.0000001) {
                    badge.classList.add('warn');
                    badge.textContent = 'PRD Minus';
                    return;
                }

                badge.classList.add('ok');
                badge.textContent = 'OK';
            }

            function clampNonNegative(el) {
                let v = toNum(el.value);
                if (v < 0) v = 0;
                v = Math.round(v * 100) / 100;
                el.value = (v % 1 === 0) ? String(v.toFixed(0)) : String(v);
                fmtBadge(el, v);
            }

            const inputs = Array.from(document.querySelectorAll('.js-recv'));

            inputs.forEach(el => {
                el.addEventListener('input', () => clampNonNegative(el));
                el.addEventListener('change', () => clampNonNegative(el));
                el.addEventListener('blur', () => clampNonNegative(el));

                // init badge
                clampNonNegative(el);
            });

            document.getElementById('btnFillReq')?.addEventListener('click', () => {
                inputs.forEach(el => {
                    const req = toNum(el.dataset.req || 0);
                    el.value = req > 0 ? req : 0;
                    clampNonNegative(el);
                });
            });

            document.getElementById('btnClearAll')?.addEventListener('click', () => {
                inputs.forEach(el => {
                    el.value = 0;
                    clampNonNegative(el);
                });
            });
        })();
    </script>
@endsection
