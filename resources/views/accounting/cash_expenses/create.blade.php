@extends('layouts.app')

@section('title', 'Accounting • Tambah Pengeluaran')

@push('head')
    <style>
        .ce-wrap {
            max-width: 900px;
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
            font-weight: 850;
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

        .in {
            width: 100%;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .28);
            background: var(--card);
            padding: .55rem .65rem;
            color: var(--text)
        }

        .ce-body {
            padding: 0 14px 14px
        }

        .ce-foot {
            padding: 12px 14px;
            border-top: 1px solid rgba(148, 163, 184, .22);
            display: flex;
            gap: .5rem;
            justify-content: flex-end;
            flex-wrap: wrap
        }
    </style>
@endpush

@section('content')
    <div class="ce-wrap">
        <div class="ce-card">
            <div class="ce-top">
                <div>
                    <h1 class="ce-title">Tambah Pengeluaran</h1>
                    <div class="muted" style="font-size:.9rem">Simpan dulu sebagai Draft, nanti bisa diposting.</div>
                </div>
                <a class="btnx" href="{{ route('accounting.cash-expenses.index') }}">Kembali</a>
            </div>

            <div class="ce-body">
                <form method="POST" action="{{ route('accounting.cash-expenses.store') }}">
                    @csrf
                    @include('accounting.cash_expenses._form')

                    <div class="ce-foot">
                        <button class="btnx btnx-primary" type="submit">Simpan Draft</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
