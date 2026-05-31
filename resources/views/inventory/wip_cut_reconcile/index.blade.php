@extends('layouts.app')

@section('title', 'Rekonsiliasi WIP-CUT')

@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
@endphp

@section('content')
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h1 class="h4 mb-1">Rekonsiliasi WIP-CUT</h1>
                <p class="text-muted mb-0" style="max-width: 760px;">
                    Membandingkan stok fisik <strong>ledger</strong> (inventory_mutations) di gudang WIP-CUT
                    dengan kolom <code>wip_qty</code> pada bundle (cutting_job_bundles).
                    <strong>Drift</strong> = selisih keduanya → indikasi data tidak konsisten.
                </p>
            </div>
        </div>

        @if ($missingWarehouse)
            <div class="alert alert-warning">
                Gudang dengan kode <strong>WIP-CUT</strong> belum ada. Tidak bisa rekonsiliasi.
            </div>
            @php return; @endphp
        @endif

        {{-- Penjelasan singkat --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2 small text-muted">
                <span class="me-3"><span class="badge bg-danger-subtle text-danger">Drift</span>
                    Ledger ≠ Σ wip_qty → perlu diperiksa (sebab C).</span>
                <span class="me-3"><strong>Ready</strong> = yang muncul di halaman Ambil Jahit
                    = min(QC OK, wip_qty) − sudah dipick.</span>
                <span><strong>Ledger &gt; Ready</strong> tapi tidak drift = wajar (ada reject / belum QC — sebab B).</span>
            </div>
        </div>

        @if ($summary)
            <div class="row g-2 mb-3">
                <div class="col-auto">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body py-2 px-3">
                            <div class="text-muted small">Item di WIP-CUT</div>
                            <div class="h5 mb-0">{{ $summary['total_items'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body py-2 px-3">
                            <div class="text-muted small">Item Drift</div>
                            <div class="h5 mb-0 {{ $summary['drift_items'] > 0 ? 'text-danger' : 'text-success' }}">
                                {{ $summary['drift_items'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('inventory.wip_cut_reconcile.index') }}"
                    class="row g-2 align-items-center">
                    <div class="col-auto">
                        <input type="text" name="search" class="form-control form-control-sm"
                            placeholder="Cari kode / nama item" value="{{ $filters['search'] }}">
                    </div>
                    <div class="col-auto form-check">
                        <input type="checkbox" name="all" value="1" class="form-check-input" id="allCheck"
                            @checked($filters['all'])>
                        <label class="form-check-label small" for="allCheck">Tampilkan semua (bukan hanya drift)</label>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-outline-secondary" type="submit">Filter</button>
                    </div>
                    @if ($filters['search'] || $filters['all'])
                        <div class="col-auto">
                            <a href="{{ route('inventory.wip_cut_reconcile.index') }}"
                                class="btn btn-sm btn-link text-decoration-none">Reset</a>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-end" title="Jumlah bundle di WIP-CUT">Bundle</th>
                                <th class="text-end" title="Stok fisik dari inventory_mutations">Ledger (fisik)</th>
                                <th class="text-end" title="Σ (wip_qty − dipick) dari cutting_job_bundles = sisa fisik bundle">Sisa bundle (net)</th>
                                <th class="text-end" title="Selisih ledger − sisa bundle net">Drift</th>
                                <th class="text-end" title="Σ qty_qc_ok">QC OK</th>
                                <th class="text-end" title="Σ sewing_picked_qty">Dipick</th>
                                <th class="text-end" title="Yang muncul di Ambil Jahit">Ready Jahit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $r)
                                <tr class="{{ $r->is_drift ? 'table-danger' : '' }}">
                                    <td>
                                        <a href="{{ route('inventory.wip_cut_reconcile.show', $r->item_id) }}"
                                            class="fw-semibold text-decoration-none">{{ $r->item_code }}</a>
                                        <div class="text-muted small">{{ $r->item_name }}</div>
                                    </td>
                                    <td class="text-end">{{ $r->bundle_count }}</td>
                                    <td class="text-end">{{ $fmt($r->ledger_qty) }}</td>
                                    <td class="text-end" title="Gross wip_qty: {{ $fmt($r->bundle_wip_qty) }}">{{ $fmt($r->bundle_net_wip) }}</td>
                                    <td class="text-end fw-bold {{ $r->is_drift ? 'text-danger' : 'text-muted' }}">
                                        {{ $r->drift > 0 ? '+' : '' }}{{ $fmt($r->drift) }}
                                    </td>
                                    <td class="text-end">{{ $fmt($r->qty_cutting_ok) }}</td>
                                    <td class="text-end">{{ $fmt($r->picked) }}</td>
                                    <td class="text-end">{{ $fmt($r->ready) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        @if ($filters['search'])
                                            Tidak ada item cocok dengan pencarian.
                                        @else
                                            🎉 Tidak ada drift. Ledger WIP-CUT konsisten dengan data bundle.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
