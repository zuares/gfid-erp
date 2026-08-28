@extends('layouts.app')

@section('title', 'Generate Payroll • ' . ($moduleLabel ?? ucfirst($module ?? '')))

@push('head')
    <style>
        .pw-wrap {
            max-width: 900px;
            margin: 0 auto;
            padding: .75rem .75rem 2.5rem
        }

        .pw-card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .25);
            border-radius: 14px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06)
        }

        .pw-h {
            padding: .9rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18)
        }

        .pw-b {
            padding: .9rem
        }

        .pw-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800
        }

        .pw-sub {
            margin: .25rem 0 0;
            color: var(--muted);
            font-size: .88rem
        }

        .pw-row {
            display: flex;
            gap: .6rem;
            flex-wrap: wrap
        }

        .pw-field {
            flex: 1 1 240px
        }

        .pw-l {
            display: block;
            font-size: .74rem;
            color: var(--muted);
            letter-spacing: .08em;
            text-transform: uppercase;
            margin: 0 0 .35rem
        }

        .pw-in {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, .28);
            background: transparent;
            color: var(--text);
            border-radius: 12px;
            padding: .55rem .65rem;
            font-size: .9rem
        }

        .pw-actions {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            margin-top: .8rem
        }

        .pw-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border: 1px solid rgba(148, 163, 184, .35);
            background: transparent;
            color: var(--text);
            padding: .5rem .78rem;
            border-radius: 12px;
            text-decoration: none;
            font-size: .9rem
        }

        .pw-btn.primary {
            border-color: color-mix(in srgb, var(--accent) 40%, rgba(148, 163, 184, .35));
            background: color-mix(in srgb, var(--accent-soft) 18%, var(--card) 82%)
        }
    </style>
@endpush

@section('content')
    @php
        $storeRoute = $module === 'daily'
            ? route('payroll.daily.store')
            : route('payroll.piecework.store', ['module' => $module]);
        $indexRoute = $module === 'daily'
            ? route('payroll.daily.index')
            : route('payroll.piecework.index', ['module' => $module]);
    @endphp
    <div class="pw-wrap">
        <div class="pw-card">
            <div class="pw-h">
                <div class="pw-title">Generate Payroll • {{ $moduleLabel ?? ucfirst($module) }}</div>
                <div class="pw-sub">Pilih rentang tanggal → sistem generate periode (draft) dari {{ $module === 'sewing' ? 'qty Ambil Jahit' : 'qty PCS jika belum QC atau qty QC OK jika sudah QC' }}. Jika sudah FINAL tidak bisa
                    diulang.</div>
            </div>

            <div class="pw-b">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ $storeRoute }}">
                    @csrf

                    <div class="pw-row">
                        <div class="pw-field">
                            <label class="pw-l">Periode Start</label>
                            <input class="pw-in" type="date" name="period_start"
                                value="{{ old('period_start', $defaultStart ?? now()->subDays(6)->toDateString()) }}"
                                required>
                        </div>
                        <div class="pw-field">
                            <label class="pw-l">Periode End</label>
                            <input class="pw-in" type="date" name="period_end"
                                value="{{ old('period_end', $defaultEnd ?? now()->toDateString()) }}" required>
                        </div>
                    </div>

                    <div class="pw-actions">
                        <button class="pw-btn primary" type="submit">Generate</button>
                        <a class="pw-btn" href="{{ $indexRoute }}">Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
