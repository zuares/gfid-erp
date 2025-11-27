{{-- resources/views/production/packing_jobs/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Packing ' . $job->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding-block: .75rem 1.5rem;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .84rem;
        }

        .badge-status {
            border-radius: 999px;
            padding: .16rem .7rem;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .badge-status-draft {
            background: color-mix(in srgb, var(--card) 85%, orange 15%);
            color: #b35a00;
        }

        .badge-status-posted {
            background: color-mix(in srgb, var(--card) 85%, seagreen 15%);
            color: #166534;
        }

        .table-wrap {
            overflow-x: auto;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .table-wrap {
                font-size: .86rem;
            }

            .card {
                border-radius: 14px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        {{-- FLASH MESSAGE --}}
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- HEADER CARD --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h1 class="h5 mb-0">
                            Packing Job
                            <span class="mono">{{ $job->code }}</span>
                        </h1>

                        @if ($job->status === 'posted')
                            <span class="badge-status badge-status-posted">POSTED</span>
                        @else
                            <span class="badge-status badge-status-draft">DRAFT</span>
                        @endif
                    </div>

                    <div class="help">
                        Tanggal:
                        <span class="mono">
                            {{ function_exists('id_date') ? id_date($job->date) : $job->date->format('Y-m-d') }}
                        </span>

                        @if ($job->channel)
                            · Channel:
                            <span class="mono">{{ $job->channel }}</span>
                        @endif

                        @if ($job->reference)
                            · Ref:
                            <span class="mono">{{ $job->reference }}</span>
                        @endif

                        @if ($job->createdBy)
                            · Dibuat oleh:
                            <span class="mono">{{ $job->createdBy->name }}</span>
                        @endif
                    </div>

                    @if ($job->notes)
                        <div class="mt-2 small">
                            <span class="fw-semibold">Catatan:</span>
                            {!! nl2br(e($job->notes)) !!}
                        </div>
                    @endif
                </div>

                <div class="text-end d-flex flex-column gap-2 align-items-end">
                    <div class="d-flex gap-2">
                        <a href="{{ route('production.packing_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>

                        @if ($job->status === 'draft')
                            <a href="{{ route('production.packing_jobs.edit', $job) }}"
                                class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil-square me-1"></i> Edit Draft
                            </a>
                        @endif
                    </div>

                    @if ($job->status === 'draft')
                        {{-- POST BUTTON --}}
                        <form action="{{ route('production.packing_jobs.post', $job) }}" method="post"
                            onsubmit="return confirm('Posting Packing Job akan mengupdate stok:\nFG → PACKED.\nLanjutkan?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success mt-1">
                                <i class="bi bi-check2-circle me-1"></i>
                                Posting & Update Stok
                            </button>
                        </form>
                    @else
                        {{-- UNPOST BUTTON --}}
                        <form action="{{ route('production.packing_jobs.unpost', $job) }}" method="post"
                            onsubmit="return confirm('Unpost Packing Job akan membalik stok:\nPACKED → FG.\nLanjutkan?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning mt-1">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>
                                Unpost & Balik Stok
                            </button>
                        </form>
                        <div class="help mt-1">
                            Stok sudah ter-update pada saat posting.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- SUMMARY CARD --}}
        @php
            $totalFg = $job->lines->sum('qty_fg');
            $totalPacked = $job->lines->sum('qty_packed');
        @endphp

        <div class="card p-3 mb-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Total FG Dipacking</div>
                    <div class="h5 mono mb-0">{{ number_format($totalFg) }}</div>
                    <div class="help">Jumlah pcs FG yang diambil dari gudang FG.</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Total Qty Packed</div>
                    <div class="h5 mono text-success mb-0">{{ number_format($totalPacked) }}</div>
                    <div class="help">Masuk ke gudang PACKED setelah posting.</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Status</div>
                    <div class="h5 mb-0">
                        @if ($job->status === 'posted')
                            <span class="text-success">Sudah diposting</span>
                        @else
                            <span class="text-warning">Draft (belum update stok)</span>
                        @endif
                    </div>
                    <div class="help">
                        Posting/Unpost bisa dilakukan di panel kanan atas.
                    </div>
                </div>
            </div>
        </div>

        {{-- DETAIL LINES --}}
        <div class="card p-0 mb-4">
            <div class="px-3 pt-3 pb-2 d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">Detail Item</div>
                    <div class="help">
                        Pergerakan stok per item: berapa yang diambil dari FG dan dipindah ke PACKED.
                    </div>
                </div>
                <div class="help">
                    Total baris: {{ $job->lines->count() }}
                </div>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:1%;">#</th>
                            <th>Item</th>
                            <th class="text-end">Qty FG</th>
                            <th class="text-end">Qty Packed</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($job->lines as $i => $line)
                            @php
                                $item = $line->item;
                            @endphp
                            <tr>
                                {{-- NO --}}
                                <td class="text-muted small">
                                    {{ $i + 1 }}
                                </td>

                                {{-- ITEM --}}
                                <td>
                                    @if ($item)
                                        <div class="small fw-semibold">
                                            {{ $item->code ?? '' }} — {{ $item->name ?? '' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $item->color ?? '' }}
                                        </div>
                                    @else
                                        <span class="text-muted small">Item tidak ditemukan</span>
                                    @endif
                                </td>

                                {{-- QTY FG --}}
                                <td class="text-end mono">
                                    {{ number_format($line->qty_fg) }}
                                </td>

                                {{-- QTY PACKED --}}
                                <td class="text-end mono text-success">
                                    {{ number_format($line->qty_packed) }}
                                </td>

                                {{-- NOTES --}}
                                <td>
                                    @if ($line->notes)
                                        <div class="small">
                                            {!! nl2br(e($line->notes)) !!}
                                        </div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Belum ada detail packing untuk job ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($job->lines->isNotEmpty())
                        <tfoot>
                            <tr class="table-light">
                                <th colspan="2" class="text-end">TOTAL</th>
                                <th class="text-end mono">{{ number_format($totalFg) }}</th>
                                <th class="text-end mono text-success">{{ number_format($totalPacked) }}</th>
                                <th></th>
                            </tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- FOOT NOTE --}}
        <div class="help mb-4">
            Dibuat:
            <span class="mono">
                {{ function_exists('id_datetime') ? id_datetime($job->created_at) : $job->created_at->format('Y-m-d H:i') }}
            </span>
            · Diupdate:
            <span class="mono">
                {{ function_exists('id_datetime') ? id_datetime($job->updated_at) : $job->updated_at->format('Y-m-d H:i') }}
            </span>
        </div>
    </div>
@endsection
