{{-- resources/views/production/wip_fin_adjustments/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Edit WIP-FIN Adjustment')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 14px 14px 48px
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .06), 0 0 0 1px rgba(15, 23, 42, .02)
        }

        .card-h {
            padding: 14px 16px;
            border-bottom: 1px solid var(--line);
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap
        }

        .card-b {
            padding: 14px 16px
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--line);
            background: var(--card);
            padding: 9px 12px;
            border-radius: 12px;
            text-decoration: none;
            color: inherit;
            cursor: pointer
        }

        .btn-primary {
            background: #111827;
            color: #fff;
            border-color: #111827
        }

        .btn-danger {
            background: #dc2626;
            color: #fff;
            border-color: #dc2626
        }

        .input,
        select,
        textarea {
            border: 1px solid var(--line);
            background: var(--card);
            padding: 9px 10px;
            border-radius: 12px;
            width: 100%
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

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0
        }

        th,
        td {
            padding: 10px 10px;
            border-bottom: 1px solid var(--line);
            vertical-align: top
        }

        th {
            text-align: left;
            font-size: 12px;
            opacity: .75
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"
        }

        .err {
            color: #dc2626;
            font-size: 12px;
            margin-top: 6px
        }

        .muted {
            opacity: .75
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 12px;
            border: 1px solid var(--line)
        }

        .b-draft {
            background: rgba(245, 158, 11, .12);
            border-color: rgba(245, 158, 11, .35)
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <div class="card">
            <div class="card-h">
                <div>
                    <div style="font-weight:700;font-size:16px">Edit WIP-FIN Adjustment</div>
                    <div class="muted" style="font-size:13px">
                        Kode: <span class="mono" style="font-weight:700">{{ $adj->code }}</span>
                        <span class="badge b-draft" style="margin-left:8px">{{ strtoupper($adj->status) }}</span>
                    </div>
                </div>
                <a class="btn" href="{{ route('production.wip-fin-adjustments.show', $adj->id) }}">← Kembali</a>
            </div>

            <div class="card-b">
                <form method="POST" action="{{ route('production.wip-fin-adjustments.update', $adj->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-3">
                        <div>
                            <label class="muted" style="font-size:12px">Tanggal</label>
                            <input class="input mono" type="date" name="date"
                                value="{{ old('date', $dateDefault ?? $adj->date?->toDateString()) }}">
                            @error('date')
                                <div class="err">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="muted" style="font-size:12px">Type</label>
                            <select class="input" name="type">
                                <option value="in" @selected(old('type', $adj->type) === 'in')>IN (Tambah WIP-FIN)</option>
                                <option value="out" @selected(old('type', $adj->type) === 'out')>OUT (Kurangi WIP-FIN)</option>
                            </select>
                            @error('type')
                                <div class="err">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="muted" style="font-size:12px">Reason</label>
                            <input class="input" type="text" name="reason" value="{{ old('reason', $adj->reason) }}">
                            @error('reason')
                                <div class="err">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div style="height:10px"></div>

                    <div>
                        <label class="muted" style="font-size:12px">Notes</label>
                        <textarea class="input" name="notes">{{ old('notes', $adj->notes) }}</textarea>
                        @error('notes')
                            <div class="err">{{ $message }}</div>
                        @enderror
                    </div>

                    <div style="height:14px"></div>

                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
                        <div style="font-weight:700">Lines</div>
                        <button type="button" class="btn btn-primary" id="btnAddRow">+ Tambah Baris</button>
                    </div>

                    @error('lines')
                        <div class="err">{{ $message }}</div>
                    @enderror

                    <div style="height:10px"></div>

                    <div style="overflow:auto">
                        <table id="linesTable">
                            <thead>
                                <tr>
                                    <th style="width:180px">Bundle ID</th>
                                    <th style="width:180px">Item ID</th>
                                    <th style="width:140px">Qty (pcs)</th>
                                    <th>Catatan</th>
                                    <th style="width:70px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $oldLines = old('lines');
                                    $lines =
                                        $oldLines ??
                                        $adj->lines
                                            ->map(
                                                fn($l) => [
                                                    'bundle_id' => $l->bundle_id,
                                                    'item_id' => $l->item_id,
                                                    'qty' => $l->qty,
                                                    'line_notes' => $l->line_notes,
                                                ],
                                            )
                                            ->values()
                                            ->all();

                                    if (empty($lines)) {
                                        $lines = [['bundle_id' => '', 'item_id' => '', 'qty' => 1, 'line_notes' => '']];
                                    }
                                @endphp

                                @foreach ($lines as $i => $row)
                                    <tr>
                                        <td>
                                            <input class="input mono" name="lines[{{ $i }}][bundle_id]"
                                                value="{{ $row['bundle_id'] ?? '' }}">
                                            @error("lines.$i.bundle_id")
                                                <div class="err">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input class="input mono" name="lines[{{ $i }}][item_id]"
                                                value="{{ $row['item_id'] ?? '' }}">
                                            @error("lines.$i.item_id")
                                                <div class="err">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input class="input mono" type="number" min="1"
                                                name="lines[{{ $i }}][qty]" value="{{ $row['qty'] ?? 1 }}">
                                            @error("lines.$i.qty")
                                                <div class="err">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input class="input" name="lines[{{ $i }}][line_notes]"
                                                value="{{ $row['line_notes'] ?? '' }}">
                                            @error("lines.$i.line_notes")
                                                <div class="err">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td style="text-align:right">
                                            <button type="button" class="btn btn-danger btnDel">Hapus</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div style="height:14px"></div>

                    <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap">
                        <a class="btn" href="{{ route('production.wip-fin-adjustments.show', $adj->id) }}">Batal</a>
                        <button class="btn btn-primary" type="submit">Update Draft</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const tbody = document.querySelector('#linesTable tbody');
            const btnAdd = document.getElementById('btnAddRow');

            function reindex() {
                const rows = Array.from(tbody.querySelectorAll('tr'));
                rows.forEach((tr, idx) => {
                    tr.querySelectorAll('input').forEach(inp => {
                        inp.name = inp.name
                            .replace(/lines\[\d+\]\[bundle_id\]/, `lines[${idx}][bundle_id]`)
                            .replace(/lines\[\d+\]\[item_id\]/, `lines[${idx}][item_id]`)
                            .replace(/lines\[\d+\]\[qty\]/, `lines[${idx}][qty]`)
                            .replace(/lines\[\d+\]\[line_notes\]/, `lines[${idx}][line_notes]`);
                    });
                });
            }

            function addRow() {
                const idx = tbody.querySelectorAll('tr').length;
                const tr = document.createElement('tr');
                tr.innerHTML = `
            <td><input class="input mono" name="lines[${idx}][bundle_id]" placeholder="123"></td>
            <td><input class="input mono" name="lines[${idx}][item_id]" placeholder="55"></td>
            <td><input class="input mono" type="number" min="1" name="lines[${idx}][qty]" value="1"></td>
            <td><input class="input" name="lines[${idx}][line_notes]" placeholder="opsional"></td>
            <td style="text-align:right"><button type="button" class="btn btn-danger btnDel">Hapus</button></td>
        `;
                tbody.appendChild(tr);
            }

            btnAdd.addEventListener('click', addRow);

            tbody.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('btnDel')) {
                    e.preventDefault();
                    const rows = tbody.querySelectorAll('tr');
                    if (rows.length <= 1) return;
                    e.target.closest('tr').remove();
                    reindex();
                }
            });
        })();
    </script>
@endpush
