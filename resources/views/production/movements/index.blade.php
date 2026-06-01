@extends('layouts.app')

@section('title', 'Produksi • Mutasi Produksi')

@push('head')
    <style>
        .pm-wrap {
            max-width: 1180px;
            margin: 0 auto;
            padding: 14px 12px 96px;
        }

        .pm-card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 14px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .08);
        }

        .pm-input {
            padding: .45rem .6rem;
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 10px;
            background: var(--card);
            color: inherit;
            font-size: .85rem;
            width: 100%;
        }

        .pm-label {
            font-size: .72rem;
            font-weight: 700;
            color: #6b7280;
            margin-bottom: .15rem;
            display: block;
        }

        .pm-pill {
            font-size: .68rem;
            font-weight: 800;
            padding: .12rem .5rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, .16);
            white-space: nowrap;
        }

        .pm-pill.to {
            background: rgba(37, 99, 235, .14);
            color: #1e40af;
        }
    </style>
@endpush

@section('content')
    <div class="pm-wrap">

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h1 class="h5 mb-0 fw-bold">Mutasi Produksi</h1>
            <a href="{{ route('production.dashboard') }}" class="btn btn-sm btn-outline-secondary">← Dashboard</a>
        </div>

        {{-- FORM TOMBOL AKSI: pindah status --}}
        <div class="pm-card p-3 mb-3">
            <h2 class="h6 mb-2">Pindahkan Stok Antar Status</h2>
            <form method="POST" action="{{ route('production.movements.store') }}">
                @csrf
                <div class="row g-2">
                    <div class="col-6 col-md-2">
                        <label class="pm-label">Dari status</label>
                        <select name="from_status" class="pm-input" required>
                            @foreach ($statuses as $slug => $s)
                                @if ($s['warehouse'])
                                    <option value="{{ $slug }}" @selected($slug === 'siap-jahit')>{{ $s['label'] }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="pm-label">Ke status</label>
                        <select name="to_status" class="pm-input" required>
                            @foreach ($statuses as $slug => $s)
                                @if ($s['warehouse'])
                                    <option value="{{ $slug }}" @selected($slug === 'sedang-jahit')>{{ $s['label'] }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="pm-label">SKU (Varian)</label>
                        <select name="item_id" class="pm-input" required>
                            <option value="">— pilih —</option>
                            @foreach ($itemOptions as $it)
                                <option value="{{ $it->id }}" @selected(old('item_id') == $it->id)>{{ $it->code }} — {{ $it->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="pm-label">Qty</label>
                        <input type="number" step="0.001" min="0.001" name="qty" class="pm-input"
                            value="{{ old('qty') }}" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="pm-label">Penjahit</label>
                        <select name="operator_id" class="pm-input">
                            <option value="">—</option>
                            @foreach ($operatorOptions as $op)
                                <option value="{{ $op->id }}" @selected(old('operator_id') == $op->id)>{{ $op->code }} — {{ $op->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="pm-label">Tanggal</label>
                        <input type="date" name="date" class="pm-input" value="{{ old('date', now()->toDateString()) }}">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="pm-label">Deadline</label>
                        <input type="date" name="deadline" class="pm-input" value="{{ old('deadline') }}">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="pm-label">Catatan</label>
                        <input type="text" name="notes" class="pm-input" maxlength="1000"
                            value="{{ old('notes') }}" placeholder="opsional">
                    </div>
                    <div class="col-12 col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Pindahkan</button>
                    </div>
                </div>
                @error('qty')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
                @error('to_status')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                <div class="text-muted small mt-2">
                    Stok berpindah lewat ledger (inventory_mutations) & otomatis tercatat sebagai mutasi produksi.
                </div>
            </form>
        </div>

        {{-- FILTER --}}
        <div class="pm-card p-3 mb-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="pm-label">Dari</label>
                    <input type="date" name="date_from" class="pm-input" value="{{ $filters['date_from'] }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="pm-label">Sampai</label>
                    <input type="date" name="date_to" class="pm-input" value="{{ $filters['date_to'] }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="pm-label">Ke status</label>
                    <select name="to_status" class="pm-input">
                        <option value="">Semua</option>
                        @foreach ($statuses as $slug => $s)
                            <option value="{{ $slug }}" @selected($filters['to_status'] === $slug)>{{ $s['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="pm-label">Produk (Kategori)</label>
                    <select name="category_id" class="pm-input">
                        <option value="">Semua</option>
                        @foreach ($categoryOptions as $cat)
                            <option value="{{ $cat->id }}" @selected($filters['category_id'] == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="pm-label">Penjahit</label>
                    <select name="operator_id" class="pm-input">
                        <option value="">Semua</option>
                        @foreach ($operatorOptions as $op)
                            <option value="{{ $op->id }}" @selected($filters['operator_id'] == $op->id)>{{ $op->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                    <a href="{{ route('production.movements.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        {{-- TABEL MUTASI --}}
        <div class="pm-card p-3">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr class="text-uppercase small text-muted">
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Batch</th>
                            <th>SKU / Produk</th>
                            <th>Status</th>
                            <th class="text-end">Qty</th>
                            <th>Penjahit</th>
                            <th>Deadline</th>
                            <th>User</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $m)
                            <tr>
                                <td class="fw-bold">{{ $m->code }}</td>
                                <td>{{ $m->date?->format('d M Y') }}</td>
                                <td>{{ $m->bundle?->bundle_code ?? '-' }}</td>
                                <td>
                                    <span class="pm-pill">{{ $m->item?->code }}</span>
                                    <div class="small text-muted">{{ $m->item?->name }}</div>
                                </td>
                                <td>
                                    <span class="pm-pill">{{ $statuses[$m->from_status]['label'] ?? $m->from_status }}</span>
                                    <span class="mx-1">→</span>
                                    <span class="pm-pill to">{{ $statuses[$m->to_status]['label'] ?? $m->to_status }}</span>
                                </td>
                                <td class="text-end fw-bold">{{ number_format((float) $m->qty, 0, ',', '.') }}</td>
                                <td>{{ $m->operator?->name ?? '-' }}</td>
                                <td>{{ $m->deadline?->format('d M Y') ?? '-' }}</td>
                                <td class="small text-muted">{{ $m->creator?->name ?? '-' }}</td>
                                <td class="small text-muted">{{ \Illuminate\Support\Str::limit($m->notes ?? '', 40) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">Belum ada mutasi pada filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $movements->links() }}</div>
        </div>

    </div>
@endsection
