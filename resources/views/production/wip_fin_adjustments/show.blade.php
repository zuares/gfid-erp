{{-- resources/views/production/wip_fin_adjustments/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • WIP-FIN Adjustment')

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

        .btn-muted {
            opacity: .9
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

        .b-posted {
            background: rgba(34, 197, 94, .12);
            border-color: rgba(34, 197, 94, .35)
        }

        .b-void {
            background: rgba(239, 68, 68, .12);
            border-color: rgba(239, 68, 68, .35)
        }

        .alert {
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid var(--line);
            margin-bottom: 10px
        }

        .alert-ok {
            background: rgba(34, 197, 94, .10);
            border-color: rgba(34, 197, 94, .30)
        }

        .alert-bad {
            background: rgba(239, 68, 68, .10);
            border-color: rgba(239, 68, 68, .30)
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <div class="card">
            <div class="card-h">
                <div>
                    <div style="font-weight:800;font-size:16px">
                        WIP-FIN Adjustment <span class="mono">{{ $adj->code }}</span>
                    </div>
                    <div class="muted" style="font-size:13px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                        <span class="mono">{{ $adj->date?->format('Y-m-d') }}</span>
                        <span
                            class="badge {{ $adj->type === 'in' ? 'b-posted' : 'b-void' }}">{{ strtoupper($adj->type) }}</span>
                        @php $cls = $adj->status==='draft' ? 'b-draft' : ($adj->status==='posted' ? 'b-posted' : 'b-void'); @endphp
                        <span class="badge {{ $cls }}">{{ strtoupper($adj->status) }}</span>
                    </div>
                </div>

                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <a class="btn" href="{{ route('production.wip-fin-adjustments.index') }}">← List</a>

                    @if ($adj->status === 'draft')
                        <a class="btn" href="{{ route('production.wip-fin-adjustments.edit', $adj->id) }}">Edit</a>

                        <form method="POST" action="{{ route('production.wip-fin-adjustments.post', $adj->id) }}">
                            @csrf
                            <button class="btn btn-primary" type="submit"
                                onclick="return confirm('POST adjustment ini? Mutasi stok akan dilakukan.')">
                                POST
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card-b">
                @if (session('success'))
                    <div class="alert alert-ok">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-bad">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-bad">
                        <div style="font-weight:700;margin-bottom:6px">Ada error:</div>
                        <ul style="margin:0;padding-left:18px">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid" style="gap:10px">
                    <div>
                        <div class="muted" style="font-size:12px">Reason</div>
                        <div style="font-weight:700">{{ $adj->reason ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="muted" style="font-size:12px">Notes</div>
                        <div>{{ $adj->notes ?: '-' }}</div>
                    </div>
                </div>

                <div style="height:14px"></div>

                <div style="font-weight:800;margin-bottom:8px">Lines</div>

                <div style="overflow:auto">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:120px">Bundle ID</th>
                                <th style="width:160px">Cutting Job</th>
                                <th style="width:120px">Item ID</th>
                                <th>Item</th>
                                <th style="width:120px">Qty</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalQty = 0; @endphp
                            @foreach ($adj->lines as $line)
                                @php $totalQty += (int) $line->qty; @endphp
                                <tr>
                                    <td class="mono">{{ $line->bundle_id }}</td>
                                    <td class="mono">{{ $line->bundle?->cuttingJob?->code ?? '-' }}</td>
                                    <td class="mono">{{ $line->item_id }}</td>
                                    <td>
                                        <div style="font-weight:700" class="mono">{{ $line->item?->code ?? '-' }}</div>
                                        <div class="muted" style="font-size:13px">{{ $line->item?->name }}</div>
                                    </td>
                                    <td class="mono" style="font-weight:800">{{ (int) $line->qty }}</td>
                                    <td class="muted">{{ $line->line_notes ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="muted" style="text-align:right;font-weight:700">TOTAL</td>
                                <td class="mono" style="font-weight:900">{{ $totalQty }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div style="height:14px"></div>

                @if ($adj->status === 'posted')
                    <div class="card" style="padding:14px 16px">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
                            <div>
                                <div style="font-weight:800">VOID</div>
                                <div class="muted" style="font-size:13px">Void akan membalik mutasi stok dan bundle.wip_qty
                                </div>
                            </div>
                        </div>

                        <div style="height:10px"></div>

                        <form method="POST" action="{{ route('production.wip-fin-adjustments.void', $adj->id) }}">
                            @csrf
                            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:end">
                                <div style="flex:1;min-width:260px">
                                    <label class="muted" style="font-size:12px">Alasan VOID</label>
                                    <input class="btn-muted input" name="void_reason" value="{{ old('void_reason') }}"
                                        placeholder="Wajib isi (contoh: salah input)">
                                    @error('void_reason')
                                        <div style="color:#dc2626;font-size:12px;margin-top:6px">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button class="btn btn-danger" type="submit"
                                    onclick="return confirm('VOID adjustment ini? Mutasi akan dibalik.')">
                                    VOID
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                @if ($adj->status === 'void')
                    <div class="card" style="padding:14px 16px">
                        <div style="font-weight:800">Status VOID</div>
                        <div class="muted" style="font-size:13px">
                            Void at: <span class="mono">{{ $adj->voided_at?->format('Y-m-d H:i') }}</span>
                            • Reason: {{ $adj->void_reason ?? '-' }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection
