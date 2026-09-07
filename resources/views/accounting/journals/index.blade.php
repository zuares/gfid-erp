@extends('layouts.app')

@section('title', 'Journals')

@push('head')
    <style>
        .jx-wrap {
            max-width: 1240px;
            margin: 0 auto;
            padding: .7rem .7rem 2.1rem
        }

        .jx-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
            margin-bottom: .55rem
        }

        .jx-title {
            font-size: 1.02rem;
            font-weight: 780;
            letter-spacing: -.02em;
            margin: 0
        }

        .jx-actions {
            display: flex;
            gap: .45rem;
            flex-wrap: wrap
        }

        .jx-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            min-height: 40px;
            padding: .42rem .7rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: color-mix(in srgb, var(--card) 88%, transparent 12%);
            text-decoration: none;
            color: var(--text);
            font-size: .84rem;
            white-space: nowrap
        }

        .jx-btn:hover {
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, .35)
        }

        .jx-filter-card {
            margin-bottom: .8rem;
            padding: .85rem;
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 14px;
            background: var(--card);
            box-shadow: 0 8px 22px rgba(15, 23, 42, .045)
        }

        .jx-filter-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .7rem;
            margin-bottom: .75rem
        }

        .jx-filter-title {
            display: flex;
            align-items: center;
            gap: .42rem;
            margin: 0;
            color: var(--text);
            font-size: .9rem;
            font-weight: 760
        }

        .jx-filter-title i {
            color: var(--accent, #2563eb)
        }

        .jx-filter-note {
            margin: .16rem 0 0;
            color: var(--muted);
            font-size: .76rem
        }

        .jx-cutoff-note {
            display: inline-flex;
            align-items: center;
            gap: .32rem;
            flex-shrink: 0;
            padding: .3rem .52rem;
            border-radius: 999px;
            color: color-mix(in srgb, var(--text) 68%, transparent 32%);
            background: color-mix(in srgb, var(--accent-soft) 38%, transparent 62%);
            font-size: .72rem;
            font-weight: 700
        }

        .jx-filter {
            display: grid;
            grid-template-columns: minmax(280px, 2fr) minmax(180px, 1.35fr) minmax(150px, 1fr) minmax(220px, 1.65fr);
            gap: .7rem;
            align-items: end
        }

        .jx-date-range {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .55rem
        }

        .jx-filter-field {
            display: grid;
            gap: .3rem;
            min-width: 0
        }

        .jx-filter-label {
            color: var(--muted);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase
        }

        .jx-in,
        .jx-sel {
            width: 100%;
            min-height: 42px;
            padding: .48rem .62rem;
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 12px;
            background: var(--card);
            color: var(--text);
            font-size: .88rem;
            box-shadow: none
        }

        .jx-in:focus,
        .jx-sel:focus {
            border-color: color-mix(in srgb, var(--accent, #2563eb) 62%, transparent 38%);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-soft) 62%, transparent 38%);
            outline: 0
        }

        .jx-filter-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .7rem;
            margin-top: .7rem;
            padding-top: .7rem;
            border-top: 1px solid rgba(148, 163, 184, .16)
        }

        .jx-filter-option {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            min-height: 38px;
            color: var(--muted);
            font-size: .78rem;
            cursor: pointer
        }

        .jx-filter-option input {
            width: 1rem;
            height: 1rem;
            accent-color: var(--accent, #2563eb)
        }

        .jx-filter-actions {
            display: flex;
            justify-content: flex-end;
            gap: .45rem
        }

        .jx-card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 14px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06)
        }

        .jx-table {
            width: 100%;
            border-collapse: collapse
        }

        .jx-table th,
        .jx-table td {
            padding: .56rem .6rem;
            vertical-align: middle
        }

        .jx-table thead th {
            font-size: .7rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: color-mix(in srgb, var(--text) 60%, transparent 40%);
            border-bottom: 1px solid rgba(148, 163, 184, .25);
            background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
            white-space: nowrap
        }

        .jx-table tbody tr {
            border-bottom: 1px solid rgba(148, 163, 184, .16)
        }

        .jx-table tbody tr:hover {
            background: color-mix(in srgb, var(--accent-soft) 10%, transparent 90%)
        }

        .jx-desc {
            display: flex;
            flex-direction: column;
            gap: .1rem;
            min-width: 210px
        }

        .jx-desc .t {
            font-weight: 650;
            line-height: 1.15
        }

        .jx-desc .m {
            font-size: .83rem;
            color: var(--muted);
            display: flex;
            gap: .35rem;
            flex-wrap: wrap;
            align-items: center
        }

        .jx-chip {
            display: inline-flex;
            align-items: center;
            padding: .14rem .46rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .30);
            font-size: .75rem;
            color: color-mix(in srgb, var(--text) 70%, transparent 30%);
            background: color-mix(in srgb, var(--card) 86%, transparent 14%)
        }

        .jx-chip.ok {
            border-color: rgba(34, 197, 94, .35);
            color: rgba(22, 163, 74, 1);
            background: rgba(34, 197, 94, .08)
        }

        .jx-chip.warn {
            border-color: rgba(245, 158, 11, .35);
            color: rgba(180, 83, 9, 1);
            background: rgba(245, 158, 11, .08)
        }

        .jx-chip.bad {
            border-color: rgba(239, 68, 68, .35);
            color: rgba(185, 28, 28, 1);
            background: rgba(239, 68, 68, .08)
        }

        .jx-money {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums
        }

        .jx-mini {
            font-size: .83rem;
            color: var(--muted)
        }

        .jx-view {
            text-align: right;
            white-space: nowrap
        }

        .jx-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .34rem .52rem;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: transparent;
            text-decoration: none;
            color: var(--text);
            font-size: .88rem
        }

        .jx-link:hover {
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, .35)
        }

        @media (max-width:1024px) {
            .jx-filter {
                grid-template-columns: repeat(2, minmax(0, 1fr))
            }

            .jx-date-range,
            .jx-filter-field:last-child {
                grid-column: 1 / -1
            }
        }

        @media (max-width:576px) {
            .jx-wrap {
                padding-right: .55rem;
                padding-left: .55rem
            }

            .jx-top {
                align-items: flex-start;
                flex-direction: column
            }

            .jx-actions {
                width: 100%
            }

            .jx-actions .jx-btn {
                flex: 1 1 0;
                min-width: 0
            }

            .jx-filter-card {
                padding: .7rem
            }

            .jx-filter-head {
                flex-direction: column;
                gap: .45rem
            }

            .jx-cutoff-note {
                align-self: flex-start
            }

            .jx-filter {
                grid-template-columns: 1fr;
                gap: .62rem
            }

            .jx-date-range,
            .jx-filter-field:last-child {
                grid-column: auto
            }

            .jx-filter-footer {
                align-items: stretch;
                flex-direction: column;
                gap: .5rem
            }

            .jx-filter-actions {
                width: 100%
            }

            .jx-filter-actions .jx-btn {
                flex: 1 1 0
            }

            .jx-hide-sm {
                display: none
            }

            .jx-desc {
                min-width: 0
            }

            .jx-table th,
            .jx-table td {
                padding: .52rem .52rem
            }
        }
    </style>
@endpush

@section('content')
    @php
        /**
         * Map source_type -> label awam
         * Tambahin sendiri sesuai modul kamu.
         */
        $typeLabel = function (?string $t) {
            return match ($t) {
                'cash_expense' => 'Pengeluaran',
                'cash_expense_void' => 'Void Pengeluaran',
                'cash_receipt' => 'Penerimaan',
                'cash_receipt_void' => 'Void Penerimaan',
                'cash_transfer' => 'Transfer Kas/Bank',
                'cash_transfer_void' => 'Void Transfer Kas/Bank',
                'opening_balance' => 'Saldo Awal',
                'opening_balance_void' => 'Void Saldo Awal',
                // contoh masa depan:
                'shipment' => 'Pengiriman',
                'shipment_void' => 'Void Pengiriman',

                default => $t ?: 'Lainnya',
            };
        };
    @endphp

    <div class="jx-wrap">

        <div class="jx-top">
            <h4 class="jx-title">Jurnal</h4>

            <div class="jx-actions">
                @if (Route::has('accounting.cash-expenses.index'))
                    <a class="jx-btn" href="{{ route('accounting.cash-expenses.index') }}">💸 Pengeluaran</a>
                @endif
                @if (Route::has('accounting.cash-transfers.index'))
                    <a class="jx-btn" href="{{ route('accounting.cash-transfers.index') }}">↔️ Transfer Kas/Bank</a>
                @endif
                @if (Route::has('accounting.opening-balances.index'))
                    <a class="jx-btn" href="{{ route('accounting.opening-balances.index') }}">🟢 Saldo Awal</a>
                @endif
            </div>
        </div>

        <div class="jx-filter-card">
            <div class="jx-filter-head">
                <div>
                    <h5 class="jx-filter-title"><i class="bi bi-funnel" aria-hidden="true"></i> Filter jurnal</h5>
                    <p class="jx-filter-note">Pilih periode, status, sumber, atau cari berdasarkan catatan dan akun.</p>
                </div>

                @if (($cutoff['active'] ?? false) && !request()->filled('from'))
                    <span class="jx-cutoff-note"><i class="bi bi-info-circle" aria-hidden="true"></i> Mulai dari cutoff {{ $cutoff['date'] }}</span>
                @endif
            </div>

            <form id="jx-filter-form" method="GET" action="{{ route('accounting.journals.index') }}" class="jx-filter">
                <div class="jx-date-range">
                    <div class="jx-filter-field">
                        <label class="jx-filter-label" for="jx-from">Dari tanggal</label>
                        <input id="jx-from" class="jx-in" type="date" name="from" value="{{ request('from') }}">
                    </div>
                    <div class="jx-filter-field">
                        <label class="jx-filter-label" for="jx-to">Sampai tanggal</label>
                        <input id="jx-to" class="jx-in" type="date" name="to" value="{{ request('to') }}">
                    </div>
                </div>

                <div class="jx-filter-field">
                    <label class="jx-filter-label" for="jx-source-type">Sumber</label>
                    <select id="jx-source-type" class="jx-sel" name="source_type">
                        <option value="">Semua sumber</option>
                        @foreach ($sourceTypes as $st)
                            <option value="{{ $st }}" @selected(request('source_type') === $st)>
                                {{ $sourceTypeOptions->get($st, $typeLabel($st)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="jx-filter-field">
                    <label class="jx-filter-label" for="jx-status">Status</label>
                    <select id="jx-status" class="jx-sel" name="status">
                        <option value="">Semua status</option>
                        <option value="final" @selected(request('status') === 'final')>Final</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                        <option value="voided" @selected(request('status') === 'voided')>Void</option>
                    </select>
                </div>

                <div class="jx-filter-field">
                    <label class="jx-filter-label" for="jx-q">Pencarian</label>
                    <input id="jx-q" class="jx-in" type="search" name="q" value="{{ request('q') }}" placeholder="Catatan, akun, atau ID sumber...">
                </div>
            </form>

            <div class="jx-filter-footer">
                <label class="jx-filter-option" for="jx-show-legacy">
                    <input id="jx-show-legacy" type="checkbox" name="show_legacy" value="1" form="jx-filter-form" @checked(request()->boolean('show_legacy'))>
                    Tampilkan data sebelum cutoff
                </label>

                <div class="jx-filter-actions">
                    <button class="jx-btn" type="submit" form="jx-filter-form"><i class="bi bi-funnel" aria-hidden="true"></i> Terapkan filter</button>
                    <a class="jx-btn" href="{{ route('accounting.journals.index') }}"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</a>
                </div>
            </div>
        </div>

        <div class="jx-card">
            <div class="table-responsive">
                <table class="jx-table">
                    <thead>
                        <tr>
                            <th style="width:110px;">Tanggal</th>
                            <th>Catatan</th>
                            <th style="width:220px;" class="jx-hide-sm">Masuk</th>
                            <th style="width:220px;" class="jx-hide-sm">Keluar</th>
                            <th style="width:74px;" class="jx-view"></th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($journals as $j)
                            @php
                                $lines = $j->lines ?? collect();

                                $debits = $lines->filter(fn($l) => (float) $l->debit > 0);
                                $credits = $lines->filter(fn($l) => (float) $l->credit > 0);

                                $sumDebit = (float) $debits->sum('debit');
                                $sumCredit = (float) $credits->sum('credit');

                                $topDebit = $debits->sortByDesc('debit')->first();
                                $topCredit = $credits->sortByDesc('credit')->first();

                                $debitCount = $debits->count();
                                $creditCount = $credits->count();

                                $statusLabel = $j->posted_at ? 'FINAL' : 'DRAFT';
                                $statusClass = $j->posted_at ? 'ok' : 'warn';

                                $isVoided = !is_null($j->voided_at ?? null);

                                $sourceText = $typeLabel($j->source_type) . ($j->source_id ? ' #' . $j->source_id : '');
                            @endphp

                            <tr>
                                <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($j->date)->format('Y-m-d') }}
                                </td>

                                <td>
                                    <div class="jx-desc">
                                        <div class="t">{{ $j->description ?: '-' }}</div>

                                        <div class="m">
                                            <span class="jx-chip {{ $statusClass }}">{{ $statusLabel }}</span>

                                            @if ($isVoided)
                                                <span class="jx-chip bad">VOID</span>
                                            @endif

                                            <span class="jx-chip">{{ $sourceText }}</span>

                                            <span class="jx-mini">Rp {{ number_format($sumDebit, 0, ',', '.') }}</span>
                                        </div>

                                        {{-- Mobile ringkas --}}
                                        <div class="m d-sm-none" style="margin-top:.1rem;">
                                            <span class="jx-mini">
                                                M: {{ $topDebit?->account?->name ?? '-' }}@if ($debitCount > 1)
                                                    +{{ $debitCount - 1 }}
                                                @endif
                                            </span>
                                            <span class="jx-mini">
                                                K: {{ $topCredit?->account?->name ?? '-' }}@if ($creditCount > 1)
                                                    +{{ $creditCount - 1 }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="jx-hide-sm">
                                    @if ($topDebit)
                                        <div class="d-flex justify-content-between gap-2">
                                            <div class="text-truncate">
                                                <span class="fw-semibold">{{ $topDebit->account->name ?? 'Akun' }}</span>
                                                @if ($debitCount > 1)
                                                    <span class="jx-mini">+{{ $debitCount - 1 }}</span>
                                                @endif
                                            </div>
                                            <div class="jx-money">{{ number_format($topDebit->debit, 0, ',', '.') }}</div>
                                        </div>
                                    @else
                                        <span class="jx-mini">-</span>
                                    @endif
                                </td>

                                <td class="jx-hide-sm">
                                    @if ($topCredit)
                                        <div class="d-flex justify-content-between gap-2">
                                            <div class="text-truncate">
                                                <span class="fw-semibold">{{ $topCredit->account->name ?? 'Akun' }}</span>
                                                @if ($creditCount > 1)
                                                    <span class="jx-mini">+{{ $creditCount - 1 }}</span>
                                                @endif
                                            </div>
                                            <div class="jx-money">{{ number_format($topCredit->credit, 0, ',', '.') }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="jx-mini">-</span>
                                    @endif
                                </td>

                                <td class="jx-view">
                                    <a class="jx-link" href="{{ route('accounting.journals.show', $j) }}">Detail</a>
                                </td>
                            </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                {{ $journals->links() }}
            </div>
        </div>
    @endsection
