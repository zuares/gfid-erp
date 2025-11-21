@extends('layouts.app')

@section('title', 'Kartu Stok')

@section('content')
    <div class="container py-3">

        {{-- HEADER --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h1 class="h4 mb-0">Kartu Stok</h1>
                <div class="small text-muted">
                    Riwayat mutasi dan saldo stok per item.
                </div>
            </div>
        </div>

        {{-- FILTER --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small">Item</label>
                        <select name="item_id" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Item --</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}" @selected($filters['item_id'] == $item->id)>
                                    {{ $item->code }} - {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small">Gudang</label>
                        <select name="warehouse_id" class="form-select form-select-sm">
                            <option value="">Semua Gudang</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected($filters['warehouse_id'] == $wh->id)>
                                    {{ $wh->code }} - {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small">Dari Tanggal</label>
                        <input type="date" name="from_date" class="form-control form-control-sm"
                            value="{{ $filters['from_date'] }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Sampai</label>
                        <input type="date" name="to_date" class="form-control form-control-sm"
                            value="{{ $filters['to_date'] }}">
                    </div>

                    <div class="col-md-1 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            Tampil
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if ($filters['item_id'] && $selectedItem)
            {{-- INFO ITEM + RINGKASAN --}}
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body small">
                            <h6 class="fw-semibold">Info Item</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Kode</dt>
                                <dd class="col-sm-8">{{ $selectedItem->code }}</dd>

                                <dt class="col-sm-4">Nama</dt>
                                <dd class="col-sm-8">{{ $selectedItem->name }}</dd>

                                <dt class="col-sm-4">Satuan</dt>
                                <dd class="col-sm-8">{{ $selectedItem->unit }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body small">
                            <h6 class="fw-semibold">Ringkasan Periode</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Saldo Awal</dt>
                                <dd class="col-sm-7 text-end">
                                    {{ number_format($openingQty, 3, ',', '.') }}
                                </dd>

                                <dt class="col-sm-5">Saldo Akhir</dt>
                                <dd class="col-sm-7 text-end">
                                    {{ number_format($closingQty, 3, ',', '.') }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TABEL KARTU STOK --}}
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 480px;">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 10%">Tgl</th>
                                    <th style="width: 18%">Gudang</th>
                                    <th style="width: 18%">Sumber</th>
                                    <th class="text-end" style="width: 10%">Masuk</th>
                                    <th class="text-end" style="width: 10%">Keluar</th>
                                    <th class="text-end" style="width: 12%">Saldo</th>
                                    <th style="width: 22%">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                {{-- SALDO AWAL --}}
                                <tr class="table-secondary">
                                    <td colspan="3" class="text-start">
                                        Saldo awal
                                        @if ($filters['from_date'])
                                            s/d {{ $filters['from_date'] }}
                                        @endif
                                    </td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">
                                        {{ number_format($openingQty, 3, ',', '.') }}
                                    </td>
                                    <td></td>
                                </tr>

                                @forelse ($mutations as $m)
                                    <tr>
                                        <td>{{ $m->date?->format('Y-m-d') }}</td>

                                        {{-- GUDANG --}}
                                        <td>
                                            @if ($m->warehouse)
                                                <div class="fw-semibold">{{ $m->warehouse->code }}</div>
                                                <div class="text-muted small">{{ $m->warehouse->name }}</div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        {{-- SUMBER: sekarang bisa diklik kalau punya URL --}}
                                        <td>
                                            @if ($m->source_url)
                                                <a href="{{ $m->source_url }}" class="text-decoration-none">
                                                    <div class="small fw-semibold">
                                                        {{ $m->source_label }}
                                                    </div>
                                                    <div class="text-muted small">
                                                        Klik untuk buka dokumen
                                                    </div>
                                                </a>
                                            @else
                                                <div class="small">
                                                    {{ $m->source_type ?? '-' }}
                                                </div>
                                                <div class="text-muted small">
                                                    ID: {{ $m->source_id ?? '-' }}
                                                </div>
                                            @endif
                                        </td>

                                        {{-- QTY MASUK --}}
                                        <td class="text-end">
                                            @if ($m->qty_change > 0)
                                                {{ number_format($m->qty_change, 3, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </td>

                                        {{-- QTY KELUAR --}}
                                        <td class="text-end">
                                            @if ($m->qty_change < 0)
                                                {{ number_format(abs($m->qty_change), 3, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </td>

                                        {{-- SALDO --}}
                                        <td class="text-end fw-semibold">
                                            {{ number_format($m->running_balance ?? 0, 3, ',', '.') }}
                                        </td>

                                        {{-- CATATAN --}}
                                        <td>
                                            {{ $m->notes ?: '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">
                                            Tidak ada mutasi stok pada periode ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info">
                Silakan pilih <strong>Item</strong> lalu klik <strong>Tampil</strong> untuk melihat kartu stok.
            </div>
        @endif
    </div>
@endsection
