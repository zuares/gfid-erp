{{-- resources/views/inventory/stock_opnames/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Inventory • Stock Opname')

@php
    use App\Models\StockOpname;

    $userRole = auth()->user()->role ?? null;
    $isOperating = $userRole === 'operating';

    // opsi kolom status & tipe
    $statusOptions = [
        StockOpname::STATUS_DRAFT => 'Draft',
        StockOpname::STATUS_COUNTING => 'Counting',
        StockOpname::STATUS_REVIEWED => 'Reviewed',
        StockOpname::STATUS_FINALIZED => 'Finalized',
    ];

    $typeOptions = [
        StockOpname::TYPE_PERIODIC => 'Periodic',
        StockOpname::TYPE_OPENING => 'Opening',
    ];
@endphp

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .85rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(96, 165, 250, 0.14) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 60%);
        }

        body[data-theme="dark"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(15, 23, 42, 0.92) 0,
                    #020617 65%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.03);
        }

        .page-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .85rem;
        }

        .page-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: -.01em;
        }

        .subtle {
            color: rgba(100, 116, 139, 1);
            font-size: .85rem;
            margin: .15rem 0 0;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            border: 1px solid rgba(148, 163, 184, .22);
            background: rgba(15, 23, 42, 0.02);
            color: rgba(71, 85, 105, 1);
        }

        body[data-theme="dark"] .chip {
            background: rgba(148, 163, 184, 0.08);
            border-color: rgba(148, 163, 184, 0.18);
            color: rgba(226, 232, 240, .86);
        }

        /* FILTERS */
        .filters {
            margin-bottom: .85rem;
        }

        .filters .card-body {
            padding: .85rem .95rem;
        }

        .filters .form-label {
            font-size: .75rem;
            color: rgba(100, 116, 139, 1);
            margin-bottom: .3rem;
        }

        .filters .btn {
            white-space: nowrap;
        }

        /* DESKTOP TABLE */
        .table-wrap {
            margin-top: .65rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .22);
            overflow: hidden;
        }

        .table thead th {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
            background: rgba(15, 23, 42, 0.02);
        }

        .badge-status {
            font-size: .7rem;
            padding: .18rem .5rem;
            border-radius: 999px;
            font-weight: 800;
            letter-spacing: .01em;
        }

        .badge-status--draft {
            background: rgba(148, 163, 184, 0.2);
            color: #475569;
        }

        .badge-status--counting {
            background: rgba(59, 130, 246, 0.16);
            color: #1d4ed8;
        }

        .badge-status--reviewed {
            background: rgba(234, 179, 8, 0.18);
            color: #854d0e;
        }

        .badge-status--finalized {
            background: rgba(22, 163, 74, 0.18);
            color: #15803d;
        }

        .badge-type {
            font-size: .65rem;
            padding: .12rem .45rem;
            border-radius: 999px;
            font-weight: 800;
        }

        .badge-type--periodic {
            background: rgba(59, 130, 246, 0.15);
            color: #1d4ed8;
        }

        .badge-type--opening {
            background: rgba(249, 115, 22, 0.16);
            color: #c2410c;
        }

        .meta-small {
            font-size: .78rem;
            color: rgba(100, 116, 139, 1);
        }

        /* MOBILE LIST (CLEAN & COMPACT) */
        .mobile-list {
            display: none;
            margin-top: .65rem;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 12px;
            overflow: hidden;
        }

        .m-item {
            padding: .65rem .75rem;
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            background: var(--card);
        }

        .m-item:last-child {
            border-bottom: none;
        }

        .m-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
        }

        .m-code {
            font-weight: 800;
            letter-spacing: -.01em;
            line-height: 1.1;
        }

        .m-right {
            display: flex;
            align-items: center;
            gap: .4rem;
            flex-shrink: 0;
        }

        .m-meta {
            margin-top: .35rem;
            display: flex;
            flex-wrap: wrap;
            gap: .45rem .6rem;
            align-items: center;
        }

        .m-dot {
            width: 4px;
            height: 4px;
            border-radius: 999px;
            background: rgba(148, 163, 184, .75);
            display: inline-block;
            transform: translateY(-1px);
        }

        .m-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .18rem .5rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            border: 1px solid rgba(148, 163, 184, .22);
            background: rgba(15, 23, 42, 0.02);
            color: rgba(71, 85, 105, 1);
            white-space: nowrap;
        }

        body[data-theme="dark"] .m-pill {
            background: rgba(148, 163, 184, 0.08);
            border-color: rgba(148, 163, 184, 0.18);
            color: rgba(226, 232, 240, .86);
        }

        .m-actions {
            margin-top: .6rem;
            display: flex;
            gap: .5rem;
        }

        .m-actions .btn {
            flex: 1;
        }

        .btn-compact {
            padding: .38rem .6rem;
            font-size: .85rem;
            border-radius: 10px;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .55rem;
            }

            .page-head {
                align-items: flex-start;
            }

            .subtle {
                display: none;
            }

            .page-head .btn {
                padding-inline: .7rem;
            }

            /* switch view */
            .table-wrap {
                display: none;
            }

            .mobile-list {
                display: block;
            }

            /* hide count chip if you want more clean (optional) */
            /* .chip { display:none; } */
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        {{-- HEADER --}}
        <div class="page-head">
            <div>
                <h1 class="page-title">Stock Opname</h1>
                <div class="subtle">Sesi opname per gudang.</div>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if (method_exists($opnames, 'total'))
                    <span class="chip">{{ $opnames->total() }} dokumen</span>
                @endif

                <a href="{{ route('inventory.stock_opnames.create') }}" class="btn btn-sm btn-primary">
                    + Sesi Baru
                </a>
            </div>
        </div>

        {{-- FILTERS (HILANGKAN UNTUK ROLE OPERATING) --}}
        @unless ($isOperating)
            <div class="card card-main filters">
                <div class="card-body">
                    <form method="GET" action="{{ route('inventory.stock_opnames.index') }}" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Gudang</label>
                            <select name="warehouse_id" class="form-select form-select-sm">
                                <option value="">Semua gudang</option>
                                @foreach ($warehouses ?? [] as $wh)
                                    <option value="{{ $wh->id }}"
                                        {{ (string) $wh->id === (string) request('warehouse_id') ? 'selected' : '' }}>
                                        {{ $wh->code }} — {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Semua status</option>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Tipe</label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="">Semua tipe</option>
                                @foreach ($typeOptions as $value => $label)
                                    <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Dari</label>
                            <input type="date" name="date_from" class="form-control form-control-sm"
                                value="{{ request('date_from') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Sampai</label>
                            <input type="date" name="date_to" class="form-control form-control-sm"
                                value="{{ request('date_to') }}">
                        </div>

                        <div class="col-12 col-md-2 d-flex gap-2 mt-2 mt-md-0">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                Terapkan
                            </button>
                            <a href="{{ route('inventory.stock_opnames.index') }}" class="btn btn-sm btn-outline-secondary">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        @endunless

        {{-- LIST --}}
        <div class="card card-main">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="h6 mb-0">Daftar</h2>
                </div>

                @php
                    $hasPagination = method_exists($opnames, 'currentPage');

                    $statusClassOf = function ($status) {
                        return match ($status) {
                            StockOpname::STATUS_DRAFT => 'badge-status badge-status--draft',
                            StockOpname::STATUS_COUNTING => 'badge-status badge-status--counting',
                            StockOpname::STATUS_REVIEWED => 'badge-status badge-status--reviewed',
                            StockOpname::STATUS_FINALIZED => 'badge-status badge-status--finalized',
                            default => 'badge-status badge-status--draft',
                        };
                    };

                    $typeMetaOf = function ($type) {
                        $label = $type === StockOpname::TYPE_OPENING ? 'Opening' : 'Periodic';
                        $class =
                            $type === StockOpname::TYPE_OPENING
                                ? 'badge-type badge-type--opening'
                                : 'badge-type badge-type--periodic';
                        return [$label, $class];
                    };
                @endphp

                {{-- MOBILE: COMPACT LIST (kolom dikurangi) --}}
                <div class="mobile-list">
                    @forelse($opnames as $index => $opname)
                        @php
                            [$typeLabel, $typeClass] = $typeMetaOf($opname->type);
                            $statusClass = $statusClassOf($opname->status);

                            $rowNo = $hasPagination
                                ? ($opnames->currentPage() - 1) * $opnames->perPage() + $index + 1
                                : $index + 1;

                            $itemCount = $opname->lines_count ?? ($opname->lines?->count() ?? 0);
                            $whCode = $opname->warehouse?->code ?? '-';
                            $whName = $opname->warehouse?->name ?? null;
                        @endphp

                        <div class="m-item">
                            <div class="m-top">
                                <div>
                                    <div class="m-code">
                                        <span
                                            style="color: rgba(100,116,139,1); font-weight:700;">#{{ $rowNo }}</span>
                                        &nbsp;{{ $opname->code }}
                                    </div>

                                    <div class="m-meta">
                                        <span class="m-pill">
                                            <span style="font-weight:800;">{{ $whCode }}</span>
                                            @if ($whName)
                                                <span style="opacity:.75;">{{ $whName }}</span>
                                            @endif
                                        </span>

                                        <span class="m-dot"></span>

                                        <span class="m-pill">
                                            {{ $opname->date?->format('d M Y') ?? '-' }}
                                        </span>

                                        <span class="m-dot"></span>

                                        <span class="m-pill">
                                            Item: <b>{{ $itemCount }}</b>
                                        </span>
                                    </div>
                                </div>

                                <div class="m-right">
                                    <span class="{{ $typeClass }}">{{ $typeLabel }}</span>
                                    <span class="{{ $statusClass }}">{{ ucfirst($opname->status) }}</span>
                                </div>
                            </div>

                            <div class="m-actions">
                                <a href="{{ route('inventory.stock_opnames.show', $opname) }}"
                                    class="btn btn-outline-secondary btn-compact">
                                    Detail
                                </a>
                                @if (!$isOperating)
                                    <a href="{{ route('inventory.stock_opnames.edit', $opname) }}"
                                        class="btn btn-outline-primary btn-compact">
                                        Edit
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-3 text-center">
                            <div class="fw-semibold mb-2">Belum ada sesi stock opname</div>
                            <a href="{{ route('inventory.stock_opnames.create') }}" class="btn btn-sm btn-primary">
                                + Buat Sesi Pertama
                            </a>
                        </div>
                    @endforelse
                </div>

                {{-- DESKTOP: TABLE (lengkap) --}}
                <div class="table-wrap">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 44px;">#</th>
                                <th>Kode</th>
                                <th>Tipe</th>
                                <th>Tanggal</th>
                                <th>Gudang</th>
                                <th>Status</th>
                                <th class="text-center">Item</th>
                                <th>Dibuat</th>
                                <th style="width: 90px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($opnames as $index => $opname)
                                @php
                                    $statusClass = $statusClassOf($opname->status);
                                    [$typeLabel, $typeClass] = $typeMetaOf($opname->type);

                                    $rowNo = $hasPagination
                                        ? ($opnames->currentPage() - 1) * $opnames->perPage() + $index + 1
                                        : $index + 1;
                                @endphp

                                <tr>
                                    <td>{{ $rowNo }}</td>

                                    <td>
                                        <div class="fw-semibold">{{ $opname->code }}</div>
                                    </td>

                                    <td>
                                        <span class="{{ $typeClass }}">{{ $typeLabel }}</span>
                                    </td>

                                    <td>
                                        {{ $opname->date?->format('d M Y') ?? '-' }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold">{{ $opname->warehouse?->code ?? '-' }}</div>
                                        <div class="meta-small">{{ $opname->warehouse?->name }}</div>
                                    </td>

                                    <td>
                                        <span class="{{ $statusClass }}">{{ ucfirst($opname->status) }}</span>
                                    </td>

                                    <td class="text-center">
                                        <span class="fw-semibold">
                                            {{ $opname->lines_count ?? ($opname->lines?->count() ?? 0) }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="fw-semibold">{{ $opname->creator?->name ?? '-' }}</div>
                                        <div class="meta-small">
                                            {{ $opname->created_at?->format('d M Y H:i') ?? '' }}
                                        </div>
                                    </td>

                                    <td class="text-end">
                                        <a href="{{ route('inventory.stock_opnames.show', $opname) }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="fw-semibold mb-2">Belum ada sesi stock opname</div>
                                        <a href="{{ route('inventory.stock_opnames.create') }}"
                                            class="btn btn-sm btn-primary">
                                            + Buat Sesi Pertama
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($opnames, 'links'))
                    <div class="mt-3">
                        {{ $opnames->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
