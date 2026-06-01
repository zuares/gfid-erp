@extends('layouts.app')

@section('title', 'Produksi • Prioritas Produksi')

@push('head')
    <style>
        .pp-wrap {
            max-width: 1280px;
            margin: 0 auto;
            padding: 14px 12px 96px;
        }

        .pp-card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 14px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .08);
        }

        .pp-input {
            padding: .45rem .6rem;
            border: 1px solid rgba(148, 163, 184, .35);
            border-radius: 10px;
            background: var(--card);
            color: inherit;
            font-size: .85rem;
            width: 100%;
        }

        .pp-label {
            font-size: .72rem;
            font-weight: 700;
            color: #6b7280;
            margin-bottom: .15rem;
            display: block;
        }

        .pp-pill {
            font-size: .68rem;
            font-weight: 800;
            padding: .12rem .5rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, .16);
            white-space: nowrap;
        }

        .pp-score {
            font-weight: 800;
            font-size: .9rem;
            min-width: 2.6rem;
            display: inline-block;
            text-align: center;
        }

        .pp-grade {
            font-size: .66rem;
            font-weight: 800;
            padding: .1rem .5rem;
            border-radius: 999px;
            white-space: nowrap;
        }

        .pp-grade.g-kritis {
            background: rgba(220, 38, 38, .14);
            color: #b91c1c;
        }

        .pp-grade.g-tinggi {
            background: rgba(234, 88, 12, .14);
            color: #c2410c;
        }

        .pp-grade.g-sedang {
            background: rgba(217, 119, 6, .14);
            color: #b45309;
        }

        .pp-grade.g-rendah {
            background: rgba(16, 185, 129, .14);
            color: #047857;
        }

        .pp-bar {
            height: 6px;
            border-radius: 999px;
            background: rgba(148, 163, 184, .2);
            overflow: hidden;
            margin-top: .25rem;
        }

        .pp-bar > span {
            display: block;
            height: 100%;
            border-radius: 999px;
        }

        .pp-reason {
            font-size: .72rem;
            color: #6b7280;
        }
    </style>
@endpush

@section('content')
    <div class="pp-wrap">

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h1 class="h5 mb-0 fw-bold">Prioritas Produksi</h1>
            <a href="{{ route('production.dashboard') }}" class="btn btn-sm btn-outline-secondary">← Dashboard</a>
        </div>

        <div class="pp-card p-3 mb-3">
            <p class="small text-muted mb-2">
                Skor 0–100 dari 5 faktor: cover ready stock (35), cover pipeline (20), kekuatan demand (20),
                deadline (15), umur WIP (10). Makin tinggi skor = makin mendesak diproduksi.
            </p>
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="pp-label">Produk (Kategori)</label>
                    <select name="category_id" class="pp-input">
                        <option value="">Semua</option>
                        @foreach ($categoryOptions as $cat)
                            <option value="{{ $cat->id }}" @selected($filters['category_id'] == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="pp-label">SKU (Varian)</label>
                    <select name="item_id" class="pp-input">
                        <option value="">Semua</option>
                        @foreach ($itemOptions as $it)
                            <option value="{{ $it->id }}" @selected($filters['item_id'] == $it->id)>{{ $it->code }} — {{ $it->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="pp-label">Grade</label>
                    <select name="grade" class="pp-input">
                        <option value="">Semua</option>
                        @foreach (['Kritis', 'Tinggi', 'Sedang', 'Rendah'] as $g)
                            <option value="{{ $g }}" @selected($filters['grade'] === $g)>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                    <a href="{{ route('production.priority.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="pp-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <h2 class="h6 mb-0">Daftar Prioritas ({{ $rows->count() }} SKU)</h2>
                <span class="small text-muted">Urut: skor tertinggi</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr class="text-uppercase small text-muted">
                            <th style="width:42px">#</th>
                            <th>SKU / Produk</th>
                            <th class="text-end">Ready</th>
                            <th class="text-end">WIP</th>
                            <th class="text-end">Jual 7h</th>
                            <th class="text-end">Jual 30h</th>
                            <th class="text-end">Laju/hr</th>
                            <th class="text-end">Cover</th>
                            <th class="text-end">Pipeline</th>
                            <th>Deadline</th>
                            <th class="text-end">Umur WIP</th>
                            <th style="min-width:130px">Skor</th>
                            <th>Alasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $i => $r)
                            @php
                                $gClass = [
                                    'Kritis' => 'g-kritis',
                                    'Tinggi' => 'g-tinggi',
                                    'Sedang' => 'g-sedang',
                                    'Rendah' => 'g-rendah',
                                ][$r->grade] ?? 'g-rendah';
                                $barColor = [
                                    'Kritis' => '#dc2626',
                                    'Tinggi' => '#ea580c',
                                    'Sedang' => '#d97706',
                                    'Rendah' => '#10b981',
                                ][$r->grade] ?? '#10b981';
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <span class="pp-pill">{{ $r->sku }}</span>
                                    <div class="small text-muted">{{ $r->product }}</div>
                                    <div class="small text-muted">{{ $r->category }}</div>
                                </td>
                                <td class="text-end">{{ number_format($r->ready, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($r->wip, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($r->s7, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($r->s30, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($r->ads, 2, ',', '.') }}</td>
                                <td class="text-end">{{ $r->cover_days === null ? '—' : number_format($r->cover_days, 1, ',', '.') . ' hr' }}</td>
                                <td class="text-end">{{ $r->pipe_cover_days === null ? '—' : number_format($r->pipe_cover_days, 1, ',', '.') . ' hr' }}</td>
                                <td>
                                    @if ($r->deadline)
                                        {{ \Carbon\Carbon::parse($r->deadline)->format('d M Y') }}
                                        @if ($r->days_to_deadline !== null)
                                            <div class="small {{ $r->days_to_deadline <= 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                                {{ $r->days_to_deadline <= 0 ? 'lewat ' . abs($r->days_to_deadline) . ' hr' : $r->days_to_deadline . ' hr lagi' }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ $r->age_days === null ? '—' : $r->age_days . ' hr' }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="pp-score" style="color:{{ $barColor }}">{{ $r->score }}</span>
                                        <span class="pp-grade {{ $gClass }}">{{ $r->grade }}</span>
                                    </div>
                                    <div class="pp-bar">
                                        <span style="width:{{ $r->score }}%;background:{{ $barColor }}"></span>
                                    </div>
                                </td>
                                <td class="pp-reason">{{ $r->reason }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center text-muted py-4">Tidak ada SKU pada filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
