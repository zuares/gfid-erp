@extends('layouts.app')

@section('title', 'Master • Suppliers')

@push('head')
    <style>
        .master-suppliers-page :root {}

        .master-suppliers-page .page-header-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0
        }

        .master-suppliers-page .page-header-subtitle {
            font-size: .86rem;
            color: var(--muted);
            margin-top: .2rem
        }

        .master-suppliers-page .main-card {
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .22);
            background: color-mix(in srgb, var(--card) 94%, var(--bg) 6%);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06);
        }

        .master-suppliers-page .btn-pill {
            border-radius: 999px;
            padding: .5rem .95rem
        }

        .master-suppliers-page .chip {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .35rem .65rem;
            border-radius: 999px;
            border: 1px solid rgba(59, 130, 246, .22);
            background: rgba(59, 130, 246, .10);
            color: rgba(29, 78, 216, 1);
            font-size: .82rem;
            user-select: none;
        }

        .master-suppliers-page .chip a {
            color: inherit;
            text-decoration: none
        }

        .master-suppliers-page .zebra tbody tr:nth-child(odd) {
            background: rgba(148, 163, 184, .06)
        }

        .master-suppliers-page .zebra tbody tr:hover {
            background: rgba(59, 130, 246, .08)
        }

        .master-suppliers-page .muted {
            color: var(--muted)
        }

        .master-suppliers-page .code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: .85rem;
            padding: .15rem .4rem;
            border-radius: 8px;
            border: 1px solid rgba(148, 163, 184, .22);
            background: rgba(148, 163, 184, .08);
        }
    </style>
@endpush

@section('content')
    <div class="master-suppliers-page container py-3">

        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <h1 class="page-header-title">Suppliers</h1>
                <div class="page-header-subtitle">Master data supplier + mapping item & harga default.</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('master.suppliers.create') }}" class="btn btn-primary btn-pill">+ Supplier</a>
            </div>
        </div>

        <div class="main-card mt-3 p-3">
            <form class="row g-2 align-items-center" method="GET" action="{{ route('master.suppliers.index') }}">
                <div class="col-md-6">
                    <input name="q" value="{{ $q ?? '' }}" class="form-control"
                        placeholder="Cari supplier: nama / code / hp / email" autofocus>
                </div>
                <div class="col-md-6 d-flex gap-2 justify-content-md-end">
                    @if (!empty($q))
                        <span class="chip">
                            Filter: <b>{{ $q }}</b>
                            <a href="{{ route('master.suppliers.index') }}" title="Reset">✕</a>
                        </span>
                    @endif
                    <button class="btn btn-outline-secondary btn-pill" type="submit">Cari</button>
                </div>
            </form>

            <div class="table-responsive mt-3">
                <table class="table zebra align-middle">
                    <thead>
                        <tr class="muted">
                            <th style="width:16%">Code</th>
                            <th>Nama</th>
                            <th style="width:18%">Kontak</th>
                            <th style="width:16%">Status</th>
                            <th style="width:12%" class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $s)
                            <tr>
                                <td><span class="code">{{ $s->code }}</span></td>
                                <td>
                                    <div class="fw-semibold">{{ $s->name }}</div>
                                    <div class="muted" style="font-size:.85rem">
                                        {{ $s->address ? Str::limit($s->address, 70) : '—' }}
                                    </div>
                                </td>
                                <td class="muted" style="font-size:.9rem">
                                    {{ $s->phone ?: '—' }}<br>
                                    {{ $s->email ?: '' }}
                                </td>
                                <td>
                                    @if ((int) $s->active === 1)
                                        <span class="chip"
                                            style="background:rgba(34,197,94,.10);border-color:rgba(34,197,94,.22);color:rgba(22,163,74,1)">Active</span>
                                    @else
                                        <span class="chip"
                                            style="background:rgba(148,163,184,.10);border-color:rgba(148,163,184,.22);color:rgba(100,116,139,1)">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-outline-primary btn-sm btn-pill"
                                        href="{{ route('master.suppliers.show', $s) }}">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="muted">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end">
                {{ $suppliers->links() }}
            </div>
        </div>
    </div>
@endsection
