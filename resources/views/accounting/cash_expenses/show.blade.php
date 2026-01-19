@extends('layouts.app')

@section('title', 'Accounting • Detail Pengeluaran')

@push('head')
    <style>
        .ce-wrap {
            max-width: 980px;
            margin: 0 auto;
            padding: .75rem .75rem 3rem
        }

        .ce-card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 14px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06)
        }

        .ce-top {
            padding: 14px 14px 10px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap
        }

        .ce-title {
            margin: 0;
            font-weight: 900;
            letter-spacing: -.02em
        }

        .muted {
            color: rgba(100, 116, 139, 1)
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

        .btnx-primary {
            background: rgba(59, 130, 246, .14);
            border-color: rgba(59, 130, 246, .28)
        }

        .btnx-danger {
            background: rgba(239, 68, 68, .12);
            border-color: rgba(239, 68, 68, .22)
        }

        .btnx-ghost {
            background: transparent
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .22rem .55rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .28);
            background: rgba(148, 163, 184, .08);
            font-size: .82rem
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

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .6rem
        }

        .box {
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 14px;
            padding: .8rem;
            background: rgba(148, 163, 184, .06)
        }

        .k {
            font-size: .82rem;
            color: rgba(100, 116, 139, 1);
            font-weight: 700
        }

        .v {
            margin-top: .15rem;
            font-weight: 850
        }

        .mono {
            font-variant-numeric: tabular-nums
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            align-items: flex-start
        }

        .line {
            border-top: 1px solid rgba(148, 163, 184, .22);
            margin: .85rem 0
        }

        .ce-foot {
            padding: 12px 14px;
            border-top: 1px solid rgba(148, 163, 184, .22);
            display: flex;
            gap: .5rem;
            justify-content: space-between;
            flex-wrap: wrap
        }

        @media (max-width: 860px) {
            .grid {
                grid-template-columns: 1fr
            }
        }
    </style>
@endpush

@section('content')
    @php
        $status = $cashExpense->status;
        $chipClass = $status === 'posted' ? 'chip-posted' : ($status === 'void' ? 'chip-void' : 'chip-draft');
        $label = $status === 'posted' ? 'Tercatat' : ($status === 'void' ? 'Dibatalkan' : 'Draft');
    @endphp

    <div class="ce-wrap">
        <div class="ce-card">
            <div class="ce-top">
                <div>
                    <h1 class="ce-title">Detail Pengeluaran</h1>
                    <div class="muted" style="margin-top:.25rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                        <span class="chip {{ $chipClass }}">{{ $label }}</span>
                        <span>• ID #{{ $cashExpense->id }}</span>
                        @if ($cashExpense->reference)
                            <span>• Ref: {{ $cashExpense->reference }}</span>
                        @endif
                    </div>
                </div>

                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                    <a class="btnx" href="{{ route('accounting.cash-expenses.index') }}">Kembali</a>

                    @if ($cashExpense->status === 'draft')
                        <a class="btnx" href="{{ route('accounting.cash-expenses.edit', $cashExpense) }}">Edit</a>

                        <form method="POST" action="{{ route('accounting.cash-expenses.destroy', $cashExpense) }}"
                            onsubmit="return confirm('Hapus draft ini?')">
                            @csrf @method('DELETE')
                            <button class="btnx btnx-danger" type="submit">Hapus</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="ce-body">
                @if (session('message'))
                    <div
                        style="padding:.65rem .75rem;margin:.75rem 0;border-radius:12px;border:1px solid rgba(148,163,184,.25);background:rgba(148,163,184,.08)">
                        <div style="font-weight:800">{{ session('message') }}</div>
                    </div>
                @endif

                <div class="grid">
                    <div class="box">
                        <div class="k">Tanggal</div>
                        <div class="v mono">{{ \Illuminate\Support\Carbon::parse($cashExpense->date)->format('Y-m-d') }}
                        </div>
                    </div>

                    <div class="box">
                        <div class="k">Nominal</div>
                        <div class="v mono" style="font-size:1.2rem">
                            {{ number_format($cashExpense->amount, 0, ',', '.') }}
                        </div>
                    </div>

                    <div class="box">
                        <div class="k">Kategori Pengeluaran</div>
                        <div class="v">{{ $cashExpense->expenseAccount?->name ?? '-' }}</div>
                        <div class="muted" style="font-size:.85rem">{{ $cashExpense->expenseAccount?->code ?? '' }}</div>
                    </div>

                    <div class="box">
                        <div class="k">Bayar dari</div>
                        <div class="v">{{ $cashExpense->cashAccount?->name ?? '-' }}</div>
                        <div class="muted" style="font-size:.85rem">{{ $cashExpense->cashAccount?->code ?? '' }}</div>
                    </div>
                </div>

                <div class="line"></div>

                <div class="box">
                    <div class="k">Keterangan</div>
                    <div class="v">{{ $cashExpense->description ?: '—' }}</div>
                    @if ($cashExpense->notes)
                        <div class="muted" style="margin-top:.35rem">{{ $cashExpense->notes }}</div>
                    @endif
                </div>

                @if ($cashExpense->status === 'posted' && $cashExpense->journal)
                    <div class="line"></div>
                    <div class="box">
                        <div class="row">
                            <div>
                                <div class="k">Bukti pencatatan</div>
                                <div class="v">Jurnal #{{ $cashExpense->journal->id }}</div>
                                <div class="muted" style="font-size:.88rem">Disimpan otomatis saat pengeluaran diposting.
                                </div>
                            </div>
                            <a class="btnx btnx-ghost"
                                href="{{ route('accounting.journals.show', $cashExpense->journal) }}">
                                Lihat jurnal
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <div class="ce-foot">
                <div class="muted" style="font-size:.9rem">
                    @if ($cashExpense->status === 'draft')
                        Draft masih bisa diedit. Posting akan mengunci data & membuat pencatatan.
                    @elseif($cashExpense->status === 'posted')
                        Sudah tercatat. Jika salah, gunakan Void untuk membatalkan (akan buat pembalik).
                    @else
                        Sudah dibatalkan.
                    @endif
                </div>

                <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                    @if ($cashExpense->status === 'draft')
                        <form method="POST" action="{{ route('accounting.cash-expenses.post', $cashExpense) }}"
                            onsubmit="return confirm('Posting sekarang? Setelah posting, data dikunci.')">
                            @csrf
                            <button class="btnx btnx-primary" type="submit">Posting</button>
                        </form>
                    @endif

                    @if ($cashExpense->status === 'posted')
                        <form method="POST" action="{{ route('accounting.cash-expenses.void', $cashExpense) }}">
                            @csrf
                            @method('PATCH')
                            <input class="in" type="text" name="reason" maxlength="255"
                                placeholder="Alasan batal (opsional)" style="max-width:260px">
                            <button class="btnx btnx-danger" type="submit"
                                onclick="return confirm('Void pengeluaran ini? Ini akan membuat pembalik pencatatan.')">
                                Void
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
