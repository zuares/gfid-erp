@extends('layouts.app')

@section('title', 'Kartu Stok')

@section('content')
    <div class="container py-3">

        {{-- =========================
             STYLE HALAMAN INI
        ========================== --}}
        <style>
            /* Zebra monokrom soft desktop */
            @media (min-width: 992px) {
                .stock-table tbody tr:nth-child(odd) {
                    background: color-mix(in srgb, var(--card) 97%, var(--bg) 3%);
                }

                .stock-table tbody tr:nth-child(even) {
                    background: color-mix(in srgb, var(--card) 99%, var(--bg) 1%);
                }
            }

            /* Mobile compact */
            .stock-card-mobile-item+.stock-card-mobile-item {
                margin-top: .65rem;
            }

            .label-sm {
                font-size: .7rem;
                color: var(--muted);
                text-transform: uppercase;
                letter-spacing: .04em;
            }

            .value-sm {
                font-size: .86rem;
            }
        </style>


        {{-- =========================
             HEADER (judul, sort, export)
        ========================== --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">

            <div>
                <h1 class="h4 mb-1">Kartu Stok</h1>
                <div class="small text-muted">Pantau pergerakan stok & nilai persediaan.</div>
            </div>

            @php
                $currentSort = $filters['sort'] ?? 'desc';
                $sortDescUrl = request()->fullUrlWithQuery(['sort' => 'desc']);
                $sortAscUrl = request()->fullUrlWithQuery(['sort' => 'asc']);
            @endphp

            <div class="d-flex flex-wrap gap-2">
                <div class="btn-group btn-group-sm">
                    <a href="{{ $sortDescUrl }}"
                        class="btn btn-outline-secondary {{ $currentSort === 'desc' ? 'active' : '' }}">↑ Terbaru</a>
                    <a href="{{ $sortAscUrl }}"
                        class="btn btn-outline-secondary {{ $currentSort === 'asc' ? 'active' : '' }}">↓ Terlama</a>
                </div>

                @if ($filters['item_id'])
                    <a href="{{ route('inventory.stock_card.export', request()->query()) }}"
                        class="btn btn-success btn-sm">Export</a>
                @endif
            </div>
        </div>



        {{-- =========================
             FILTER (di bawah header)
        ========================== --}}
        <div class="card mb-3 shadow-sm border-0">
            <div class="card-body">

                <form method="GET" class="row g-3">

                    <input type="hidden" name="sort" value="{{ $filters['sort'] ?? 'desc' }}">

                    {{-- Item --}}
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label small text-muted mb-1">Item *</label>
                        <select name="item_id" class="form-select form-select-sm">
                            <option value="">-- Pilih --</option>
                            @foreach ($items as $i)
                                <option value="{{ $i->id }}" @selected($filters['item_id'] == $i->id)>
                                    {{ $i->code }} — {{ $i->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Gudang --}}
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label small text-muted mb-1">Gudang</label>
                        <select name="warehouse_id" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}" @selected($filters['warehouse_id'] == $w->id)>
                                    {{ $w->code }} — {{ $w->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- LOT --}}
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label small text-muted mb-1">LOT</label>
                        <select name="lot_id" class="form-select form-select-sm" @disabled(!$filters['item_id'])>
                            <option value="">Semua</option>
                            @foreach ($lots as $l)
                                <option value="{{ $l->id }}" @selected($filters['lot_id'] == $l->id)>
                                    {{ $l->code }} ({{ number_format($l->qty_onhand, 2, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Dari --}}
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label small text-muted mb-1">Dari</label>
                        <input type="date" name="from_date" class="form-control form-control-sm"
                            value="{{ $filters['from_date'] }}">
                    </div>

                    {{-- Sampai --}}
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label small text-muted mb-1">Sampai</label>
                        <input type="date" name="to_date" class="form-control form-control-sm"
                            value="{{ $filters['to_date'] }}">
                    </div>

                    {{-- Hanya mutasi bernilai --}}
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label small text-muted mb-1">Filter</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="has_cost" value="1" name="has_cost"
                                @checked($filters['has_cost'])>
                            <label class="form-check-label small" for="has_cost">
                                Hanya mutasi dengan nilai (total_cost ≠ null)
                            </label>
                        </div>
                    </div>

                    {{-- Tombol --}}
                    <div class="col-xl-3 col-md-6 d-flex justify-content-end align-items-end gap-2">
                        <button class="btn btn-primary btn-sm px-3">Tampil</button>
                        <a href="{{ route('inventory.stock_card.index') }}"
                            class="btn btn-outline-secondary btn-sm px-3">Reset</a>
                    </div>

                </form>
            </div>
        </div>



        {{-- =========================
             INFO ITEM + RINGKASAN (DI BAWAH FILTER)
        ========================== --}}
        @if ($filters['item_id'] && $selectedItem)
            <div class="row g-3 mb-3">

                {{-- Info Item --}}
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body small">
                            <h6 class="fw-semibold mb-2">Info Item</h6>
                            <dl class="row mb-0">
                                <dt class="col-4 text-muted">Kode</dt>
                                <dd class="col-8">{{ $selectedItem->code }}</dd>

                                <dt class="col-4 text-muted">Nama</dt>
                                <dd class="col-8">{{ $selectedItem->name }}</dd>

                                <dt class="col-4 text-muted">Satuan</dt>
                                <dd class="col-8">{{ $selectedItem->unit }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                {{-- Ringkasan --}}
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body small">
                            <h6 class="fw-semibold mb-2">Ringkasan Periode</h6>

                            <div class="row">
                                <div class="col-6">
                                    <div class="text-muted small">Saldo Awal Qty</div>
                                    <div class="fw-semibold text-end mb-3">
                                        {{ number_format($openingQty, 2, ',', '.') }}
                                    </div>

                                    <div class="text-muted small">Saldo Akhir Qty</div>
                                    <div class="fw-semibold text-end">
                                        {{ number_format($closingQty, 2, ',', '.') }}
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="text-muted small">Saldo Awal Nilai</div>
                                    <div class="fw-semibold text-end {{ $openingValue < 0 ? 'text-danger' : '' }}">
                                        {{ number_format($openingValue, 0, ',', '.') }}
                                    </div>

                                    <div class="text-muted small mt-3">Saldo Akhir Nilai</div>
                                    <div class="fw-semibold text-end {{ $closingValue < 0 ? 'text-danger' : '' }}">
                                        {{ number_format($closingValue, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        @endif




        {{-- =========================
             TABEL DESKTOP + MOBILE COMPACT
        ========================== --}}

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">

                @if (!$filters['item_id'])
                    <div class="alert alert-info m-3">
                        Silakan pilih <strong>Item</strong> untuk menampilkan kartu stok.
                    </div>
                @else
                    {{-- DESKTOP TABLE --}}
                    <div class="d-none d-lg-block">
                        <div class="table-responsive" style="max-height: 520px;">
                            <table class="table table-sm mb-0 stock-table align-middle">
                                <thead class="table-light sticky-top">
                                    <tr class="small text-muted">
                                        <th>Tgl</th>
                                        <th>Gudang</th>
                                        <th>LOT</th>
                                        <th>Sumber</th>
                                        <th class="text-end">Masuk</th>
                                        <th class="text-end">Keluar</th>
                                        <th class="text-end">Saldo Qty</th>
                                        <th class="text-end">Nilai</th>
                                        <th class="text-end">Saldo Nilai</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>

                                <tbody class="small">

                                    {{-- SALDO AWAL --}}
                                    <tr class="table-secondary">
                                        <td colspan="4">Saldo awal</td>
                                        <td class="text-end">-</td>
                                        <td class="text-end">-</td>
                                        <td class="text-end">{{ number_format($openingQty, 2, ',', '.') }}</td>
                                        <td class="text-end">-</td>
                                        <td class="text-end {{ $openingValue < 0 ? 'text-danger' : '' }}">
                                            {{ number_format($openingValue, 0, ',', '.') }}
                                        </td>
                                        <td></td>
                                    </tr>

                                    {{-- MUTASI --}}
                                    @foreach ($mutations as $m)
                                        @php
                                            $in = $m->qty_change > 0 ? $m->qty_change : 0;
                                            $out = $m->qty_change < 0 ? abs($m->qty_change) : 0;
                                            $val = $m->total_cost ?? 0;
                                        @endphp

                                        <tr>
                                            <td>{{ $m->date }}</td>

                                            <td>
                                                {{ $m->warehouse->code ?? '-' }}
                                                <div class="text-muted small">{{ $m->warehouse->name ?? '' }}</div>
                                            </td>

                                            <td>
                                                {{ $m->lot->code ?? '-' }}
                                                @if ($m->lot)
                                                    <div class="text-muted small">
                                                        Avg {{ number_format($m->lot->avg_cost, 0, ',', '.') }}
                                                    </div>
                                                @endif
                                            </td>

                                            <td>
                                                <span class="fw-semibold">{{ $m->source_type }}</span>
                                                <div class="text-muted small">{{ $m->source_id }}</div>
                                            </td>

                                            <td class="text-end">
                                                @if ($in > 0)
                                                    {{ number_format($in, 2, ',', '.') }}
                                                @else
                                                    -
                                                @endif
                                            </td>

                                            <td class="text-end">
                                                @if ($out > 0)
                                                    {{ number_format($out, 2, ',', '.') }}
                                                @else
                                                    -
                                                @endif
                                            </td>

                                            <td class="text-end fw-semibold">
                                                {{ number_format($m->running_qty, 2, ',', '.') }}
                                            </td>

                                            <td class="text-end {{ $val < 0 ? 'text-danger' : '' }}">
                                                {{ number_format($val, 0, ',', '.') }}
                                            </td>

                                            <td class="text-end fw-semibold {{ $m->running_value < 0 ? 'text-danger' : '' }}">
                                                {{ number_format($m->running_value, 0, ',', '.') }}
                                            </td>

                                            <td class="small">
                                                {{ $m->notes ?: '-' }}
                                            </td>
                                        </tr>
                                    @endforeach

                                </tbody>
                            </table>
                        </div>
                    </div>


                    {{-- MOBILE COMPACT --}}
                    <div class="d-block d-lg-none p-2">

                        {{-- SALDO AWAL --}}
                        <div class="card border-0 shadow-sm mb-2 p-2">
                            <div class="label-sm mb-1">Saldo awal</div>
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="label-sm">Qty</div>
                                    <div class="value-sm fw-semibold">
                                        {{ number_format($openingQty, 2, ',', '.') }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="label-sm">Nilai</div>
                                    <div class="value-sm fw-semibold {{ $openingValue < 0 ? 'text-danger' : '' }}">
                                        {{ number_format($openingValue, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- MUTASI --}}
                        @foreach ($mutations as $m)
                            @php
                                $in = $m->qty_change > 0 ? $m->qty_change : 0;
                                $out = $m->qty_change < 0 ? abs($m->qty_change) : 0;
                                $val = $m->total_cost ?? 0;
                            @endphp

                            <div class="card border-0 shadow-sm stock-card-mobile-item p-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <div>
                                        <div class="label-sm">Tanggal</div>
                                        <div class="value-sm fw-semibold">{{ $m->date }}</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="label-sm">Saldo Qty</div>
                                        <div class="value-sm fw-semibold">
                                            {{ number_format($m->running_qty, 2, ',', '.') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-1">
                                    <div class="label-sm">Gudang / LOT</div>
                                    <div class="value-sm">
                                        {{ $m->warehouse->code ?? '-' }} — {{ $m->warehouse->name ?? '' }}<br>
                                        {{ $m->lot->code ?? '-' }}
                                        @if ($m->lot)
                                            <span class="text-muted small">
                                                (Avg {{ number_format($m->lot->avg_cost, 0, ',', '.') }})
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-1">
                                    <div class="label-sm">Sumber</div>
                                    <div class="value-sm">
                                        {{ $m->source_type }} (ID {{ $m->source_id }})
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="label-sm">Masuk / Keluar</div>
                                        <div class="value-sm">
                                            M: {{ $in > 0 ? number_format($in, 2, ',', '.') : '-' }} <br>
                                            K: {{ $out > 0 ? number_format($out, 2, ',', '.') : '-' }}
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="label-sm">Nilai / Saldo</div>
                                        <div class="value-sm">
                                            <div class="{{ $val < 0 ? 'text-danger' : '' }}">
                                                {{ number_format($val, 0, ',', '.') }}
                                            </div>
                                            <div class="fw-semibold {{ $m->running_value < 0 ? 'text-danger' : '' }}">
                                                {{ number_format($m->running_value, 0, ',', '.') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2">
                                    <div class="label-sm">Catatan</div>
                                    <div class="value-sm">{{ $m->notes ?: '-' }}</div>
                                </div>
                            </div>
                        @endforeach

                    </div>

                @endif

            </div>
        </div>

    </div>
@endsection
