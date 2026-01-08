{{-- resources/views/production/wip_fin_adjustments/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • WIP-FIN Adjustments')

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
            color: inherit
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
            opacity: .85
        }

        .input,
        select {
            border: 1px solid var(--line);
            background: var(--card);
            padding: 9px 10px;
            border-radius: 12px;
            width: 100%
        }

        .grid {
            display: grid;
            gap: 10px
        }

        @media(min-width:900px) {
            .grid-4 {
                grid-template-columns: 2fr 1fr 1fr auto
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

        .muted {
            opacity: .75
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <div class="card">
            <div class="card-h">
                <div>
                    <div style="font-weight:700;font-size:16px">WIP-FIN Adjustments</div>
                    <div class="muted" style="font-size:13px">Koreksi saldo WIP-FIN (IN / OUT) per bundle + item (INTEGER)
                    </div>
                </div>

                <a class="btn btn-primary" href="{{ route('production.wip-fin-adjustments.create') }}">
                    + Buat Adjustment
                </a>
            </div>

            <div class="card-b">
                <form method="GET" action="{{ route('production.wip-fin-adjustments.index') }}">
                    <div class="grid grid-4" style="align-items:end">
                        <div>
                            <label class="muted" style="font-size:12px">Search</label>
                            <input class="input" type="text" name="search" value="{{ request('search') }}"
                                placeholder="Kode / reason / notes">
                        </div>
                        <div>
                            <label class="muted" style="font-size:12px">Status</label>
                            <select class="input" name="status">
                                <option value="">Semua</option>
                                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                                <option value="posted" @selected(request('status') === 'posted')>Posted</option>
                                <option value="void" @selected(request('status') === 'void')>Void</option>
                            </select>
                        </div>
                        <div>
                            <label class="muted" style="font-size:12px">Type</label>
                            <select class="input" name="type">
                                <option value="">Semua</option>
                                <option value="in" @selected(request('type') === 'in')>IN</option>
                                <option value="out" @selected(request('type') === 'out')>OUT</option>
                            </select>
                        </div>
                        <div style="display:flex;gap:8px">
                            <button class="btn btn-primary" type="submit">Filter</button>
                            <a class="btn btn-muted" href="{{ route('production.wip-fin-adjustments.index') }}">Reset</a>
                        </div>
                    </div>
                </form>

                <div style="height:12px"></div>

                @if (session('success'))
                    <div class="badge b-posted" style="margin-bottom:10px">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="badge b-void" style="margin-bottom:10px">{{ session('error') }}</div>
                @endif
                @if (session('info'))
                    <div class="badge b-draft" style="margin-bottom:10px">{{ session('info') }}</div>
                @endif

                <div style="overflow:auto">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:160px">Tanggal</th>
                                <th style="width:180px">Kode</th>
                                <th style="width:90px">Type</th>
                                <th style="width:120px">Status</th>
                                <th>Reason / Notes</th>
                                <th style="width:110px" class="mono">Lines</th>
                                <th style="width:120px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adjustments as $adj)
                                <tr>
                                    <td class="mono">{{ optional($adj->date)->format('Y-m-d') }}</td>
                                    <td class="mono" style="font-weight:700">{{ $adj->code }}</td>
                                    <td>
                                        <span class="badge {{ $adj->type === 'in' ? 'b-posted' : 'b-void' }}">
                                            {{ strtoupper($adj->type) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $cls =
                                                $adj->status === 'draft'
                                                    ? 'b-draft'
                                                    : ($adj->status === 'posted'
                                                        ? 'b-posted'
                                                        : 'b-void');
                                        @endphp
                                        <span class="badge {{ $cls }}">{{ strtoupper($adj->status) }}</span>
                                    </td>
                                    <td>
                                        <div style="font-weight:600">{{ $adj->reason ?? '-' }}</div>
                                        <div class="muted" style="font-size:13px">
                                            {{ \Illuminate\Support\Str::limit($adj->notes, 120) }}</div>
                                    </td>
                                    <td class="mono">{{ $adj->lines_count }}</td>
                                    <td>
                                        <a class="btn"
                                            href="{{ route('production.wip-fin-adjustments.show', $adj->id) }}">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="muted">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="height:12px"></div>
                {{ $adjustments->links() }}
            </div>
        </div>

    </div>
@endsection
