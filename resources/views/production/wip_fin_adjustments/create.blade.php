{{-- resources/views/production/wip_fin_adjustments/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Koreksi WIP-FIN (Hasil Hitung)')

@push('head')
    <style>
        /* ====== GFID Contrast Tokens (fallback aman) ====== */
        :root {
            --text: var(--fg, rgba(15, 23, 42, 1));
            --muted: rgba(100, 116, 139, 1);
            --bg: var(--card, #fff);
            --bg2: rgba(148, 163, 184, .10);
            --line2: rgba(148, 163, 184, .28);

            --ok: rgba(22, 163, 74, 1);
            --okbg: rgba(22, 163, 74, .14);

            --bad: rgba(220, 38, 38, 1);
            --badbg: rgba(220, 38, 38, .14);

            --warn: rgba(245, 158, 11, 1);
            --warnbg: rgba(245, 158, 11, .16);
        }

        /* ====== Layout ====== */
        .page-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 14px 14px 48px
        }

        .card {
            background: var(--bg);
            border: 1px solid var(--line, var(--line2));
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .06), 0 0 0 1px rgba(15, 23, 42, .02)
        }

        .card-h {
            padding: 14px 16px;
            border-bottom: 1px solid var(--line, var(--line2));
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap
        }

        .card-b {
            padding: 14px 16px
        }

        /* ====== Buttons ====== */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--line, var(--line2));
            background: var(--bg);
            padding: 9px 12px;
            border-radius: 12px;
            text-decoration: none;
            color: var(--text);
            cursor: pointer
        }

        .btn:hover {
            border-color: rgba(148, 163, 184, .45);
        }

        .btn-primary {
            background: #111827;
            color: #fff;
            border-color: #111827
        }

        .btn-primary:hover {
            filter: brightness(1.05);
        }

        .btn-muted {
            background: var(--bg2);
            color: var(--text);
            border-color: rgba(148, 163, 184, .35);
        }

        /* ====== Inputs ====== */
        .input,
        select,
        textarea {
            border: 1px solid var(--line, var(--line2));
            background: var(--bg);
            padding: 9px 10px;
            border-radius: 12px;
            width: 100%;
            color: var(--text);
        }

        .input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: rgba(59, 130, 246, .55);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, .12);
        }

        textarea {
            min-height: 90px
        }

        .grid {
            display: grid;
            gap: 10px
        }

        @media(min-width:900px) {
            .grid-3 {
                grid-template-columns: 1fr 1fr 1fr
            }
        }

        /* ====== Table ====== */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: var(--bg);
        }

        thead th {
            position: sticky;
            top: 0;
            background: var(--bg2);
            border-bottom: 1px solid var(--line, var(--line2));
            color: var(--text);
            font-weight: 700;
            z-index: 1;
        }

        th,
        td {
            padding: 10px 10px;
            border-bottom: 1px solid var(--line, var(--line2));
            vertical-align: top
        }

        th {
            text-align: left;
            font-size: 12px;
            opacity: 1;
        }

        tbody tr:hover td {
            background: rgba(148, 163, 184, .06);
        }

        /* ====== Text ====== */
        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
            color: var(--text);
        }

        .muted {
            color: var(--muted);
            opacity: 1;
        }

        .err {
            color: var(--bad);
            font-size: 12px;
            margin-top: 6px;
            font-weight: 600;
        }

        /* ====== Badges ====== */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            border: 1px solid var(--line, var(--line2));
            color: var(--text);
            background: var(--bg2);
            font-weight: 700;
        }

        .b-warn {
            background: var(--warnbg);
            border-color: rgba(245, 158, 11, .45);
            color: rgba(120, 53, 15, 1);
        }

        .b-ok {
            background: var(--okbg);
            border-color: rgba(22, 163, 74, .45);
            color: rgba(20, 83, 45, 1);
        }

        .b-bad {
            background: var(--badbg);
            border-color: rgba(220, 38, 38, .45);
            color: rgba(127, 29, 29, 1);
        }

        /* ====== Result badge (override safe on dark themes too) ====== */
        .jsResult {
            min-width: 92px;
            justify-content: center;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="card">
            <div class="card-h">
                <div>
                    <div style="font-weight:800;font-size:16px;color:var(--text)">Koreksi WIP-FIN (Input Hasil Hitung)</div>
                    <div class="muted" style="font-size:13px">
                        Isi <b style="color:var(--text)">Qty Setelah Koreksi</b> (hasil hitungan). Sistem otomatis menentukan
                        IN/OUT dan Qty Adjustment.
                    </div>
                </div>
                <a class="btn" href="{{ route('production.wip-fin-adjustments.index') }}">← Kembali</a>
            </div>

            <div class="card-b">
                @if ($errors->has('warehouse'))
                    <div class="badge b-warn" style="margin-bottom:10px">{{ $errors->first('warehouse') }}</div>
                @endif

                @error('lines')
                    <div class="err">{{ $message }}</div>
                @enderror

                <form method="POST" action="{{ route('production.wip-fin-adjustments.store') }}">
                    @csrf

                    <div class="grid grid-3">
                        <div>
                            <label class="muted" style="font-size:12px">Tanggal (default hari ini)</label>
                            <input class="input mono" type="date" name="date"
                                value="{{ old('date', now()->toDateString()) }}">
                            @error('date')
                                <div class="err">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="muted" style="font-size:12px">Reason</label>
                            <input class="input" type="text" name="reason" value="{{ old('reason') }}"
                                placeholder="Misal: Selisih hitung / Koreksi data">
                            @error('reason')
                                <div class="err">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="muted" style="font-size:12px">Ringkasan</label>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                                <div class="badge b-ok" id="sumIn">IN: 0</div>
                                <div class="badge b-bad" id="sumOut">OUT: 0</div>
                            </div>
                        </div>
                    </div>

                    <div style="height:10px"></div>

                    <div>
                        <label class="muted" style="font-size:12px">Notes</label>
                        <textarea class="input" name="notes" placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="err">{{ $message }}</div>
                        @enderror
                    </div>

                    <div style="height:14px"></div>

                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
                        <div>
                            <div style="font-weight:800;color:var(--text)">Saldo WIP-FIN</div>
                            <div class="muted" style="font-size:13px">
                                Isi Qty Setelah Koreksi. Kalau sama dengan WIP → otomatis OK (tidak dibuat adjustment).
                            </div>
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <button type="button" class="btn btn-muted" id="btnFillSame">Set Semua = WIP</button>
                            <button type="button" class="btn btn-muted" id="btnClear">Kosongkan Semua</button>
                        </div>
                    </div>

                    <div style="height:10px"></div>

                    <div style="overflow:auto;border:1px solid var(--line, var(--line2));border-radius:14px">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:120px">Bundle ID</th>
                                    <th style="width:160px">Cutting Job</th>
                                    <th>Item</th>
                                    <th style="width:110px" class="mono">WIP Qty</th>
                                    <th style="width:170px" class="mono">Qty Setelah Koreksi</th>
                                    <th style="width:150px">Hasil</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $oldMap = collect(old('lines', []))->keyBy('bundle_id');
                                @endphp

                                @forelse(($bundles ?? collect()) as $i => $b)
                                    @php
                                        $bundleId = (int) $b->id;
                                        $itemId = (int) ($b->finished_item_id ?? 0);
                                        $wipQty = (int) ($b->wip_qty ?? 0);

                                        $itemCode = $b->finishedItem?->code ?? 'Item#' . $itemId;
                                        $itemName = $b->finishedItem?->name ?? null;
                                        $cjCode = $b->cuttingJob?->code ?? '-';

                                        $old = $oldMap->get($bundleId, []);
                                        $afterQty = $old['qty_after'] ?? '';
                                    @endphp

                                    <tr data-wip="{{ $wipQty }}">
                                        <td class="mono" style="font-weight:900">{{ $bundleId }}</td>
                                        <td class="mono" style="font-weight:700">{{ $cjCode }}</td>
                                        <td>
                                            <div class="mono" style="font-weight:900">{{ $itemCode }}</div>
                                            <div class="muted" style="font-size:13px">{{ $itemName }}</div>
                                            <div class="muted" style="font-size:12px">Item ID: <span class="mono"
                                                    style="font-weight:800">{{ $itemId }}</span></div>
                                        </td>

                                        <td class="mono jsWip" style="font-weight:900">{{ $wipQty }}</td>

                                        <td>
                                            <input type="hidden" name="lines[{{ $i }}][bundle_id]"
                                                value="{{ $bundleId }}">
                                            <input type="hidden" name="lines[{{ $i }}][item_id]"
                                                value="{{ $itemId }}">

                                            <input class="input mono jsAfter" type="number" min="0"
                                                name="lines[{{ $i }}][qty_after]" value="{{ $afterQty }}"
                                                placeholder="isi hasil hitung">
                                            @error("lines.$i.qty_after")
                                                <div class="err">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        <td>
                                            <span class="badge b-warn jsResult">-</span>
                                            <input type="hidden" class="jsEnabled"
                                                name="lines[{{ $i }}][enabled]" value="0">
                                            <input type="hidden" class="jsType" name="lines[{{ $i }}][type]"
                                                value="">
                                            <input type="hidden" class="jsQty" name="lines[{{ $i }}][qty]"
                                                value="0">
                                        </td>

                                        <td>
                                            <input class="input" name="lines[{{ $i }}][line_notes]"
                                                value="{{ $old['line_notes'] ?? '' }}" placeholder="opsional">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="muted">Tidak ada saldo WIP-FIN yang tersedia.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div style="height:14px"></div>

                    <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap">
                        <a class="btn" href="{{ route('production.wip-fin-adjustments.index') }}">Batal</a>
                        <button class="btn btn-primary" type="submit">Buat Draft Koreksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const sumInEl = document.getElementById('sumIn');
            const sumOutEl = document.getElementById('sumOut');

            function recalc() {
                let sumIn = 0,
                    sumOut = 0;

                document.querySelectorAll('tr[data-wip]').forEach(tr => {
                    const wip = parseInt(tr.getAttribute('data-wip') || '0', 10);
                    const afterInp = tr.querySelector('.jsAfter');
                    const res = tr.querySelector('.jsResult');

                    const enabled = tr.querySelector('.jsEnabled');
                    const type = tr.querySelector('.jsType');
                    const qty = tr.querySelector('.jsQty');

                    const vRaw = (afterInp?.value ?? '').trim();
                    if (vRaw === '') {
                        res.textContent = '-';
                        res.className = 'badge b-warn jsResult';

                        enabled.value = 0;
                        type.value = '';
                        qty.value = 0;
                        return;
                    }

                    const after = Math.max(0, parseInt(vRaw || '0', 10));
                    const diff = after - wip;

                    if (diff === 0) {
                        res.textContent = 'OK (0)';
                        res.className = 'badge b-ok jsResult';

                        enabled.value = 0;
                        type.value = '';
                        qty.value = 0;
                        return;
                    }

                    if (diff > 0) {
                        res.textContent = `IN +${diff}`;
                        res.className = 'badge b-ok jsResult';

                        enabled.value = 1;
                        type.value = 'in';
                        qty.value = diff;
                        sumIn += diff;
                    } else {
                        const d = Math.abs(diff);
                        res.textContent = `OUT -${d}`;
                        res.className = 'badge b-bad jsResult';

                        enabled.value = 1;
                        type.value = 'out';
                        qty.value = d;
                        sumOut += d;
                    }
                });

                sumInEl.textContent = `IN: ${sumIn}`;
                sumOutEl.textContent = `OUT: ${sumOut}`;
            }

            document.addEventListener('input', function(e) {
                if (e.target && e.target.classList.contains('jsAfter')) recalc();
            });

            document.getElementById('btnFillSame')?.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('tr[data-wip]').forEach(tr => {
                    const wip = tr.getAttribute('data-wip') || '0';
                    tr.querySelector('.jsAfter').value = wip;
                });
                recalc();
            });

            document.getElementById('btnClear')?.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('tr[data-wip]').forEach(tr => {
                    tr.querySelector('.jsAfter').value = '';
                });
                recalc();
            });

            recalc();
        })();
    </script>
@endpush
