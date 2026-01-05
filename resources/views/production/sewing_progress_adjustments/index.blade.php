@extends('layouts.app')

@section('title', 'Sewing Progress Adjustment')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .85rem .85rem 4rem;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .cardx {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .22);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .08), 0 0 0 1px rgba(15, 23, 42, .03);
        }

        .cardx .cardx-body {
            padding: .9rem 1rem;
        }

        @media(min-width:768px) {
            .page-wrap {
                padding: 1.1rem 1rem 4rem
            }

            .cardx .cardx-body {
                padding: 1rem 1.25rem
            }
        }

        .toolbar {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }

        .btn-pill {
            border-radius: 999px;
            padding: .25rem .75rem;
            font-size: .8rem;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .table thead th {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            border-top: none;
        }

        .table tbody td {
            font-size: .85rem;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <div class="toolbar mb-2">
            <div>
                <div class="fw-bold">Sewing Progress Adjustments</div>
                <div class="text-muted small">Dokumen koreksi progress pickup (tanpa mutasi stok).</div>
            </div>

            <a href="{{ route('production.sewing.adjustments.create') }}" class="btn btn-primary btn-sm btn-pill">
                <i class="bi bi-plus-lg"></i><span>Adjustment</span>
            </a>
        </div>

        <div class="cardx">
            <div class="cardx-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:170px;">Kode</th>
                                <th style="width:130px;">Tanggal</th>
                                <th>Pickup</th>
                                <th style="width:220px;">Operator</th>
                                <th style="width:120px;" class="text-end">Lines</th>
                                <th style="width:110px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($docs as $d)
                                <tr>
                                    <td class="mono fw-bold">{{ $d->code }}</td>
                                    <td class="mono">
                                        {{ $d->date ? \Illuminate\Support\Carbon::parse($d->date)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="mono">{{ $d->pickup?->code ?? '-' }}</td>
                                    <td>{{ $d->operator?->name ?? '-' }}</td>
                                    <td class="text-end mono">
                                        {{ number_format((int) ($d->lines_count ?? ($d->lines?->count() ?? 0))) }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('production.sewing.adjustments.show', $d) }}"
                                            class="btn btn-outline-secondary btn-sm btn-pill">
                                            <i class="bi bi-eye"></i><span>Detail</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada dokumen adjustment.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-2">
            {{ $docs->links() }}
        </div>

    </div>
@endsection
