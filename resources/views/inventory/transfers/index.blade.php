{{-- resources/views/inventory/transfers/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Transfer Stok')

@push('head')
    <style>
        :root {
            --shp-accent: #334155;
            --shp-accent-ring: rgba(148, 163, 184, .18);
        }

        .shp-wrap {
            max-width: 1040px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        .shp-topbar {
            position: sticky;
            top: 0;
            z-index: 300;
            display: flex;
            align-items: center;
            gap: .45rem;
            padding: .45rem .75rem;
            background: var(--card, #fff);
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            margin-bottom: 1rem;
        }

        .shp-topbar-code {
            font-weight: 900;
            font-size: .95rem;
            letter-spacing: 0;
            white-space: nowrap;
        }

        .shp-topbar-spacer {
            flex: 1;
            min-width: .5rem;
        }

        .shp-card {
            background: var(--card, #fff);
            border-radius: 0;
            border: 1px solid rgba(148, 163, 184, .16);
            box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
            margin-bottom: 1rem;
        }

        .btn-shp-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(148, 163, 184, .35);
            background: transparent;
            color: #475569;
            font-size: .84rem;
            font-weight: 600;
            border-radius: 8px;
            padding: .35rem .75rem;
            text-decoration: none;
            transition: background .12s, color .12s;
        }

        .btn-shp-outline:hover {
            background: rgba(226, 232, 240, .7);
            color: #374151;
        }
        
        .btn-shp-primary {
            background: var(--shp-accent);
            color: #fff;
            border-color: var(--shp-accent);
        }
        
        .btn-shp-primary:hover {
            background: #1f2937;
            color: #fff;
        }

        .code-link { font-weight:700; text-decoration:none; color:inherit; }
        .code-link:hover { text-decoration:underline; }

        @media (max-width: 768px) {
            .shp-wrap {
                padding: .5rem .5rem 5rem;
            }
            .shp-topbar {
                padding: .5rem;
                gap: .38rem;
            }
            .shp-topbar-code {
                flex: 1 1 auto;
                min-width: 145px;
                font-size: 1.05rem;
            }
            .shp-topbar-spacer {
                display: none !important;
            }
            .btn-shp-outline {
                min-height: 38px;
                font-size: .82rem !important;
            }
            .table-responsive {
                margin: 0;
                padding: 0;
            }
            .mobile-hide { display: none !important; }
        }
    </style>
@endpush

@section('content')
    <div class="shp-topbar">
        <span class="shp-topbar-code">Daftar Transfer Stok</span>
        <div class="shp-topbar-spacer"></div>
        <a href="{{ route('inventory.transfers.create') }}" class="btn-shp-outline btn-shp-primary" style="text-decoration: none;">
            + Transfer Baru
        </a>
    </div>

    <div class="shp-wrap">
        {{-- ALERTS --}}
        @if (session('success'))
            <div class="alert alert-success py-2 mb-3">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('general'))
            <div class="alert alert-danger py-2 mb-3">
                {{ $errors->first('general') }}
            </div>
        @endif

        {{-- FILTER --}}
        <div class="shp-card p-3 mb-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold mb-1">Gudang Asal</label>
                    <select name="from_warehouse_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(request('from_warehouse_id') == $wh->id)>
                                {{ $wh->code }} - {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold mb-1">Gudang Tujuan</label>
                    <select name="to_warehouse_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected(request('to_warehouse_id') == $wh->id)>
                                {{ $wh->code }} - {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold mb-1">Item (di detail)</label>
                    <select name="item_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}" @selected(request('item_id') == $item->id)>
                                {{ $item->code }} - {{ $item->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-1">
                    <label class="form-label small text-muted fw-bold mb-1">Dari</label>
                    <input type="date" name="from_date" class="form-control form-control-sm"
                        value="{{ request('from_date') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label small text-muted fw-bold mb-1">Sampai</label>
                    <input type="date" name="to_date" class="form-control form-control-sm"
                        value="{{ request('to_date') }}">
                </div>

                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-shp-outline btn-shp-primary w-100" style="min-height: 31px; padding: 0.25rem 0.5rem; font-size: 0.875rem; border-radius: 0.25rem;">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="shp-card p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle table-hover">
                    <thead style="background: rgba(248,250,252,.98); font-size: .78rem; text-transform: uppercase; color: #64748b;">
                        <tr>
                            <th class="px-3 py-2 mobile-hide" style="width: 11%; border-bottom: 1px solid rgba(148,163,184,.18);">Tanggal</th>
                            <th class="px-3 py-2" style="width: 15%; border-bottom: 1px solid rgba(148,163,184,.18);">Kode</th>
                            <th class="px-3 py-2" style="width: 20%; border-bottom: 1px solid rgba(148,163,184,.18);">Mutasi</th>
                            <th class="px-3 py-2 mobile-hide" style="width: 30%; border-bottom: 1px solid rgba(148,163,184,.18);">Item</th>
                            <th class="text-end px-3 py-2" style="width: 10%; border-bottom: 1px solid rgba(148,163,184,.18);">Total Qty</th>
                            <th class="text-end px-3 py-2" style="width: 14%; border-bottom: 1px solid rgba(148,163,184,.18);"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transfers as $trf)
                            @php
                                $firstLine = $trf->lines->first();
                                $lineCount = $trf->lines->count();
                                $totalQty = $trf->lines->sum('qty');
                            @endphp

                            <tr>
                                <td class="px-3 mobile-hide" style="font-size: .85rem; color: #64748b;">
                                    {{ $trf->date?->format('d M Y') }}
                                </td>
                                <td class="px-3">
                                    <a href="{{ route('inventory.transfers.show', $trf->id) }}" class="code-link">
                                        {{ $trf->code }}
                                    </a>
                                </td>
                                <td class="px-3" style="font-size: .85rem;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div>
                                            <span class="fw-bold" style="color: #475569;">{{ $trf->fromWarehouse?->code ?? '-' }}</span>
                                        </div>
                                        <div class="text-muted" style="font-size: .7rem;">&rarr;</div>
                                        <div>
                                            <span class="fw-bold" style="color: #475569;">{{ $trf->toWarehouse?->code ?? '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 mobile-hide">
                                    @if ($firstLine && $firstLine->item)
                                        <div class="fw-semibold" style="font-size: .85rem;">{{ $firstLine->item->name }}</div>
                                        <div class="text-muted" style="font-size: .75rem;">
                                            {{ $firstLine->item->code }}
                                            @if ($lineCount > 1)
                                                &middot; +{{ $lineCount - 1 }} item lain
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end px-3">
                                    <span class="fw-bold" style="color: #475569;">{{ number_format($totalQty, 3, ',', '.') }}</span>
                                </td>
                                <td class="text-end px-3">
                                    <a href="{{ route('inventory.transfers.show', $trf->id) }}"
                                        class="btn btn-outline-secondary btn-sm" style="font-size: .75rem; border-radius: 6px;">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Belum ada transfer stok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($transfers instanceof \Illuminate\Pagination\AbstractPaginator)
                <div class="p-2 border-top">
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
