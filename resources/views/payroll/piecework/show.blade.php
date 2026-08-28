@extends('layouts.app')

@section('title', 'Payroll Detail • ' . ($moduleLabel ?? ucfirst($module ?? '')))

@push('head')
    <style>
        .pw-wrap {
            max-width: 1040px;
            margin: 0 auto;
            padding: .75rem .75rem 2.5rem
        }

        .pw-top {
            position: sticky;
            top: 0;
            z-index: 300;
            display: flex;
            gap: .75rem;
            align-items: flex-start;
            justify-content: space-between;
            padding: .45rem .75rem;
            margin: 0 -.75rem .65rem;
            background: var(--card);
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            box-shadow: none
        }

        .pw-heading {
            min-width: 0
        }

        .pw-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 750;
            letter-spacing: 0
        }

        .pw-sub {
            margin: 0;
            color: var(--muted);
            font-size: .78rem
        }

        .pw-card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .25);
            border-radius: 10px;
            box-shadow: none
        }

        .pw-h {
            padding: .7rem .8rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: center
        }

        .pw-b {
            padding: .8rem
        }

        .pw-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border: 1px solid rgba(148, 163, 184, .35);
            background: transparent;
            color: var(--text);
            padding: .42rem .65rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: .8rem
        }

        .pw-btn.primary {
            border-color: color-mix(in srgb, var(--accent) 40%, rgba(148, 163, 184, .35));
            background: color-mix(in srgb, var(--accent-soft) 18%, var(--card) 82%)
        }

        .pw-btn.danger {
            border-color: rgba(239, 68, 68, .35);
            color: rgba(239, 68, 68, 1)
        }

        .pw-btn.success {
            border-color: rgba(16, 185, 129, .35);
            color: rgba(16, 185, 129, 1)
        }

        .pw-row {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center
        }

        .pw-in,
        .pw-sel {
            border: 1px solid rgba(148, 163, 184, .28);
            background: transparent;
            color: var(--text);
            border-radius: 12px;
            padding: .46rem .6rem;
            font-size: .88rem
        }

        .pw-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0
        }

        .pw-table-wrap {
            overflow-x: auto
        }

        .pw-table th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: var(--card);
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            text-align: left;
            padding: .55rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18)
        }

        .pw-table td {
            padding: .6rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, .12);
            vertical-align: top
        }

        .pw-right {
            text-align: right
        }

        .pw-chip {
            display: inline-flex;
            align-items: center;
            border: 1px solid rgba(148, 163, 184, .25);
            padding: .18rem .48rem;
            border-radius: 999px;
            font-size: .78rem;
            color: var(--muted)
        }

        .pw-chip.final {
            border-color: rgba(16, 185, 129, .35);
            color: rgba(16, 185, 129, 1)
        }

        .pw-chip.draft {
            border-color: rgba(245, 158, 11, .35);
            color: rgba(245, 158, 11, 1)
        }

        .pw-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: .75rem
        }

        @media (min-width: 992px) {
            .pw-grid {
                grid-template-columns: 380px 1fr
            }
        }

        @media (max-width: 640px) {
            .pw-hide-sm {
                display: none
            }

            .pw-top {
                flex-direction: column;
                align-items: stretch
            }

            .pw-top > .pw-row {
                justify-content: stretch
            }

            .pw-top > .pw-row .pw-btn {
                flex: 1;
                justify-content: center
            }
        }
    </style>
@endpush

@section('content')
    @php
        $qtyLabel = $module === 'sewing' ? 'Qty Ambil' : 'Qty Payroll';
        $periodStart = \Carbon\Carbon::parse($period->period_start)->locale('id');
        $periodEnd = \Carbon\Carbon::parse($period->period_end)->locale('id');
        $periodWeek = $periodStart->weekOfMonth;
        $periodMonth = $periodStart->translatedFormat('F Y');
        $periodDateRange = $periodStart->translatedFormat('l, d/m/Y') . ' – ' . $periodEnd->translatedFormat('l, d/m/Y');
    @endphp
    <div class="pw-wrap">
        <div class="pw-top">
            <div class="pw-heading">
                <h1 class="pw-title">{{ $moduleLabel ?? ucfirst($module) }} • Payroll Borongan</h1>
                <div class="pw-sub">
                    Minggu ke-{{ $periodWeek }} · {{ $periodMonth }} · {{ $periodDateRange }} · ID #{{ $period->id }}
                    ·
                    @if ($period->status === 'final')
                        <span class="pw-chip final">FINAL</span>
                    @else
                        <span class="pw-chip draft">DRAFT</span>
                    @endif
                    @if ($period->paid_at)
                        • Paid {{ \Carbon\Carbon::parse($period->paid_at)->format('d/m/Y H:i') }}
                    @endif
                </div>
            </div>

            <div class="pw-row">
                <a class="pw-btn" href="{{ route('payroll.piecework.overview', ['module' => $module]) }}">Daftar Payroll</a>
                <a class="pw-btn primary" href="{{ route('payroll.piecework.overview', ['module' => $module]) }}">＋ Generate</a>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="pw-grid">
            {{-- LEFT: Summary + Actions --}}
            <div class="pw-card">
                <div class="pw-h">
                    <div style="font-weight:900">Ringkasan</div>
                    <div class="pw-row">
                        <span class="pw-chip">Qty:
                            {{ rtrim(rtrim(number_format((float) $grandTotalQty, 2, '.', ''), '0'), '.') }}</span>
                        <span class="pw-chip">Total: {{ number_format((float) $grandTotalAmount, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="pw-b">
                    {{-- ACTIONS --}}
                    <div class="pw-row" style="margin-bottom:.75rem">
                        @if ($period->status !== 'final')
                            <form method="POST"
                                action="{{ route('payroll.piecework.finalize', ['module' => $module, 'period' => $period]) }}">
                                @csrf
                                <button class="pw-btn primary" type="submit"
                                    onclick="return confirm('Finalkan periode ini? Ini akan mencatat: Dr HPP (5101) / Cr Hutang Upah Borongan (2102).')">
                                    FINALIZE
                                </button>
                            </form>

                            <form method="POST"
                                action="{{ route('payroll.piecework.regenerate', ['module' => $module, 'period' => $period]) }}">
                                @csrf
                                <button class="pw-btn danger" type="submit"
                                    onclick="return confirm('Regenerate draft ini? Lines akan dihitung ulang.')">
                                    REGENERATE
                                </button>
                            </form>
                        @else
                            <span class="pw-chip final">FINAL LOCKED</span>
                        @endif
                    </div>

                    {{-- PAY (only if final & not paid) --}}
                    @if ($period->status === 'final' && !$period->paid_at)
                        <form class="pw-row" method="POST"
                            action="{{ route('payroll.piecework.pay', ['module' => $module, 'period' => $period]) }}">
                            @csrf
                            <select class="pw-sel" name="paid_from_account_id" required>
                                <option value="">Bayar dari...</option>
                                @foreach ($cashAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} • {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>

                            <button class="pw-btn success" type="submit"
                                onclick="return confirm('Catat pembayaran? Ini akan melunasi hutang payroll.')">
                                BAYAR
                            </button>
                        </form>
                        <div class="pw-sub" style="margin-top:.5rem">
                            Bayar akan mencatat: Dr Hutang Upah Borongan (2102) / Cr Kas/Bank.
                        </div>
                    @elseif($period->paid_at)
                        <div class="pw-sub">Pembayaran sudah dicatat.</div>
                    @endif

                    <hr style="border:none;border-top:1px solid rgba(148,163,184,.18);margin:1rem 0">

                    {{-- SUMMARY TABLE --}}
                    <div class="pw-table-wrap">
                        <table class="pw-table">
                            <thead>
                                <tr>
                                    <th>Operator</th>
                                    <th class="pw-right">{{ $qtyLabel }}</th>
                                    <th class="pw-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($summaryByEmployee as $s)
                                    <tr>
                                        <td>
                                            <div style="font-weight:800">{{ $s['employee_name'] }}</div>
                                            <div class="pw-row" style="margin-top:.3rem">
                                                <a class="pw-btn"
                                                    href="{{ route('payroll.piecework.slip', ['module' => $module, 'period' => $period, 'employee' => $s['employee_id']]) }}">
                                                    Slip
                                                </a>
                                            </div>
                                        </td>
                                        <td class="pw-right">
                                            {{ rtrim(rtrim(number_format((float) $s['total_qty'], 2, '.', ''), '0'), '.') }}
                                        </td>
                                        <td class="pw-right" style="font-weight:800">
                                            {{ number_format((float) $s['total_amount'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" style="padding:1rem;color:var(--muted)">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if (!empty($allowSlipAll))
                        <div class="pw-row" style="margin-top:.75rem">
                            <a class="pw-btn"
                                href="{{ route('payroll.piecework.slip_all', ['module' => $module, 'period' => $period]) }}">Slip
                                All</a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- RIGHT: Detail lines --}}
            <div class="pw-card">
                <div class="pw-h">
                    <div style="font-weight:900">Detail Lines</div>
                    <div class="pw-sub" style="margin:0">Tampil per operator → kategori → item.</div>
                </div>

                <div class="pw-b" style="padding:0">
                    <div class="pw-table-wrap">
                        <table class="pw-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th class="pw-hide-sm">Category</th>
                                    <th>Item</th>
                                    <th class="pw-right">{{ $qtyLabel }}</th>
                                    <th class="pw-right pw-hide-sm">Rate</th>
                                    <th class="pw-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lines as $l)
                                    <tr>
                                        <td style="font-weight:700">{{ $l->employee?->name ?? '-' }}</td>
                                        <td class="pw-hide-sm">{{ $l->category?->name ?? '-' }}</td>
                                        <td>{{ $l->item?->name ?? '-' }}</td>
                                        <td class="pw-right">
                                            {{ rtrim(rtrim(number_format((float) $l->total_qty_ok, 2, '.', ''), '0'), '.') }}
                                        </td>
                                        <td class="pw-right pw-hide-sm">
                                            {{ number_format((float) $l->rate_per_pcs, 0, ',', '.') }}</td>
                                        <td class="pw-right" style="font-weight:800">
                                            {{ number_format((float) $l->amount, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="padding:1rem;color:var(--muted)">Tidak ada lines.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
