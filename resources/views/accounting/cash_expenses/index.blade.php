@extends('layouts.app')

@section('title', 'Accounting • Pengeluaran')

@push('head')
    <style>
        .ce-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: .75rem .75rem 3rem;
        }

        .ce-card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 14px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06)
        }

        .ce-top {
            display: flex;
            gap: .75rem;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            padding: 14px 14px 10px
        }

        .ce-title {
            margin: 0;
            font-weight: 800;
            letter-spacing: -.02em
        }

        .ce-sub {
            margin: .15rem 0 0;
            color: rgba(100, 116, 139, 1);
            font-size: .9rem
        }

        .ce-actions {
            display: flex;
            gap: .5rem;
            align-items: center
        }

        .btnx {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border-radius: 12px;
            padding: .55rem .75rem;
            border: 1px solid rgba(148, 163, 184, .28);
            background: rgba(148, 163, 184, .08);
            color: var(--text);
            text-decoration: none
        }

        .btnx:hover {
            filter: brightness(.98)
        }

        .btnx-primary {
            background: rgba(59, 130, 246, .14);
            border-color: rgba(59, 130, 246, .28)
        }

        .ce-filters {
            padding: 0 14px 12px;
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr 1fr auto;
            gap: .5rem
        }

        .in {
            width: 100%;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .28);
            background: var(--card);
            padding: .55rem .65rem;
            color: var(--text)
        }

        .ce-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0
        }

        .ce-table th,
        .ce-table td {
            padding: .7rem .75rem;
            border-top: 1px solid rgba(148, 163, 184, .22);
            vertical-align: top
        }

        .ce-table th {
            font-size: .82rem;
            color: rgba(100, 116, 139, 1);
            text-align: left
        }

        .muted {
            color: rgba(100, 116, 139, 1)
        }

        .mono {
            font-variant-numeric: tabular-nums
        }

        .right {
            text-align: right
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .22rem .55rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .28);
            background: rgba(148, 163, 184, .08);
            font-size: .8rem
        }

        .chip-draft {
            background: rgba(245, 158, 11, .12);
            border-color: rgba(245, 158, 11, .22)
        }

        .chip-posted {
            background: rgba(34, 197, 94, .12);
            border-color: rgba(34, 197, 94, .22)
        }

        .chip-void {
            background: rgba(239, 68, 68, .10);
            border-color: rgba(239, 68, 68, .18)
        }

        .ce-body {
            padding: 0 14px 14px
        }

        .rowlink {
            color: inherit;
            text-decoration: none
        }

        .rowlink:hover {
            color: inherit;
            text-decoration: underline
        }

        @media (max-width: 860px) {
            .ce-filters {
                grid-template-columns: 1fr 1fr;
                gap: .5rem
            }

            .hide-sm {
                display: none
            }
        }
    </style>
@endpush

@section('content')
    <div class="ce-wrap">
        <div class="ce-card">
            <div class="ce-top">
                <div>
                    <h1 class="ce-title">Pengeluaran</h1>
                    <div class="ce-sub">Catat pengeluaran harian dengan cara yang simpel.</div>
                </div>
                <div class="ce-actions">
                    <a class="btnx btnx-primary" href="{{ route('accounting.cash-expenses.create') }}">+ Tambah</a>
                </div>
            </div>

            <form class="ce-filters" method="GET" action="{{ route('accounting.cash-expenses.index') }}">
                <select class="in" name="status">
                    @php $st = request('status'); @endphp
                    <option value="">Semua status</option>
                    <option value="draft" {{ $st === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="posted" {{ $st === 'posted' ? 'selected' : '' }}>Tercatat</option>
                    <option value="void" {{ $st === 'void' ? 'selected' : '' }}>Dibatalkan</option>
                </select>

                <input class="in" type="date" name="from" value="{{ request('from') }}" placeholder="Dari">
                <input class="in" type="date" name="to" value="{{ request('to') }}" placeholder="Sampai">

                <button class="btnx" type="submit">Filter</button>
                <a class="btnx" href="{{ route('accounting.cash-expenses.index') }}">Reset</a>
            </form>

            <div class="ce-body">
                @if (session('message'))
                    <div
                        style="padding:.65rem .75rem;margin:.75rem 0;border-radius:12px;border:1px solid rgba(148,163,184,.25);background:rgba(148,163,184,.08)">
                        <div style="font-weight:700">{{ session('message') }}</div>
                    </div>
                @endif

                <table class="ce-table">
                    <thead>
                        <tr>
                            <th style="width:120px">Tanggal</th>
                            <th>Ringkasan</th>
                            <th class="hide-sm">Kategori</th>
                            <th class="hide-sm">Bayar dari</th>
                            <th style="width:120px">Status</th>
                            <th class="right" style="width:150px">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cashExpenses as $x)
                            @php
                                $status = $x->status;
                                $chipClass =
                                    $status === 'posted'
                                        ? 'chip-posted'
                                        : ($status === 'void'
                                            ? 'chip-void'
                                            : 'chip-draft');
                                $label =
                                    $status === 'posted' ? 'Tercatat' : ($status === 'void' ? 'Dibatalkan' : 'Draft');
                            @endphp
                            <tr>
                                <td class="mono">{{ \Illuminate\Support\Carbon::parse($x->date)->format('Y-m-d') }}</td>

                                <td>
                                    <a class="rowlink" href="{{ route('accounting.cash-expenses.show', $x) }}">
                                        <div style="font-weight:750">{{ $x->description ?: 'Pengeluaran' }}</div>
                                    </a>
                                    <div class="muted" style="font-size:.88rem">
                                        {{ $x->reference ? 'Ref: ' . $x->reference . ' • ' : '' }}
                                        ID #{{ $x->id }}
                                    </div>
                                </td>

                                <td class="hide-sm">
                                    <div style="font-weight:650">{{ $x->expenseAccount?->name ?? '-' }}</div>
                                    <div class="muted" style="font-size:.85rem">{{ $x->expenseAccount?->code ?? '' }}
                                    </div>
                                </td>

                                <td class="hide-sm">
                                    <div style="font-weight:650">{{ $x->cashAccount?->name ?? '-' }}</div>
                                    <div class="muted" style="font-size:.85rem">{{ $x->cashAccount?->code ?? '' }}</div>
                                </td>

                                <td>
                                    <span class="chip {{ $chipClass }}">{{ $label }}</span>
                                </td>

                                <td class="right mono" style="font-weight:800">
                                    {{ number_format($x->amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="muted" style="padding:1rem .75rem">Belum ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div style="padding:.75rem 0">
                    {{ $cashExpenses->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
