@extends('layouts.app')

@section('title', 'Drift WIP-CUT · ' . $item->code)

@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 3, ',', '.'), '0'), ',');
    $eps = 0.0001;
    $driftLedgerWip = $summary['drift_ledger_vs_wip'];
    $driftWipProd = $summary['drift_wip_vs_prod'];
@endphp

@section('content')
    <div class="page-wrap">
        <div class="mb-3">
            <a href="{{ route('inventory.wip_cut_reconcile.index') }}"
                class="btn btn-sm btn-link text-decoration-none px-0">&larr; Kembali ke Rekonsiliasi</a>
            <h1 class="h4 mb-1">{{ $item->code }} <span class="text-muted fw-normal">· {{ $item->name }}</span></h1>
            <p class="text-muted mb-0">Analisa penyebab drift WIP-CUT untuk item ini.</p>
        </div>

        {{-- KARTU PERBANDINGAN --}}
        <div class="row g-2 mb-3">
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Ledger (fisik)</div>
                        <div class="h5 mb-0">{{ $fmt($summary['ledger_total']) }}</div>
                        <div class="text-muted" style="font-size:.7rem;">inventory_mutations</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Sisa bundle (net)</div>
                        <div class="h5 mb-0">{{ $fmt($summary['sum_net_wip']) }}</div>
                        <div class="text-muted" style="font-size:.7rem;">Σ(wip_qty − dipick) · gross {{ $fmt($summary['sum_wip_qty']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">Drift Ledger vs sisa bundle</div>
                        <div class="h5 mb-0 {{ abs($driftLedgerWip) > $eps ? 'text-danger' : 'text-success' }}">
                            {{ $driftLedgerWip > 0 ? '+' : '' }}{{ $fmt($driftLedgerWip) }}
                        </div>
                        <div class="text-muted" style="font-size:.7rem;">ledger − Σ(wip−dipick)</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-2 px-3">
                        <div class="text-muted small">wip_qty vs Produksi</div>
                        <div class="h5 mb-0 {{ abs($driftWipProd) > $eps ? 'text-warning' : 'text-success' }}">
                            {{ $driftWipProd > 0 ? '+' : '' }}{{ $fmt($driftWipProd) }}
                        </div>
                        <div class="text-muted" style="font-size:.7rem;">vs (QC OK − dipick)</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- INTERPRETASI --}}
        <div class="alert {{ abs($driftLedgerWip) > $eps ? 'alert-danger' : 'alert-success' }} small">
            @if (abs($driftLedgerWip) <= $eps)
                ✅ <strong>Konsisten.</strong> Ledger WIP-CUT = Σ sisa bundle (wip_qty − dipick). Tidak ada drift untuk
                item ini.
            @else
                ⚠️ <strong>Drift {{ $fmt($driftLedgerWip) }}.</strong>
                Ledger fisik ({{ $fmt($summary['ledger_total']) }}) tidak sama dengan total sisa bundle
                ({{ $fmt($summary['sum_net_wip']) }} = Σ wip_qty − dipick). Lihat tabel di bawah untuk dokumen mana yang
                menggerakkan stok ledger, lalu bandingkan dengan jumlah di sisi bundle.
            @endif
        </div>

        {{-- BUNDLE NYANGKUT (sisa produksi tapi bukan WIP-CUT) --}}
        @if (($summary['orphan_count'] ?? 0) > 0)
            <div class="card border-warning shadow-sm mb-3">
                <div class="card-header bg-warning-subtle">
                    <strong>⚠️ {{ $summary['orphan_count'] }} bundle "nyangkut"</strong>
                    <span class="text-muted small">— masih punya sisa produksi
                        ({{ $fmt($summary['orphan_net_prod']) }} pcs) tapi <em>tidak</em> terdaftar di gudang WIP-CUT,
                        sehingga TIDAK muncul di halaman Ambil Jahit.</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Bundle</th>
                                    <th>Gudang WIP</th>
                                    <th class="text-end">QC OK</th>
                                    <th class="text-end">wip_qty</th>
                                    <th class="text-end">Dipick</th>
                                    <th class="text-end">Sisa (QC OK − dipick)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orphanBundles as $b)
                                    <tr>
                                        <td class="small">
                                            {{ $b->bundle_code ?? ('#' . $b->id) }}
                                            <span class="badge bg-light text-muted">{{ $b->status }}</span>
                                        </td>
                                        <td class="small text-muted">{{ $b->wip_warehouse_code ?? '(tidak di-set)' }}</td>
                                        <td class="text-end">{{ $fmt($b->qty_cutting_ok) }}</td>
                                        <td class="text-end">{{ $fmt($b->wip_qty) }}</td>
                                        <td class="text-end">{{ $fmt($b->picked) }}</td>
                                        <td class="text-end fw-semibold text-warning">
                                            {{ $fmt(max($b->qty_cutting_ok - $b->picked, 0)) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <div class="row g-3">
            {{-- LEDGER PER DOKUMEN --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <strong>Pergerakan Ledger WIP-CUT per Dokumen</strong>
                        <div class="text-muted small">net = masuk + keluar; total harus = Ledger fisik</div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Dokumen (source_type)</th>
                                        <th class="text-end">N</th>
                                        <th class="text-end">Masuk</th>
                                        <th class="text-end">Keluar</th>
                                        <th class="text-end">Net</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($ledgerBySource as $s)
                                        <tr>
                                            <td class="small">{{ class_basename($s->source_type ?? '(null)') }}</td>
                                            <td class="text-end">{{ $s->n }}</td>
                                            <td class="text-end text-success">{{ $fmt($s->qty_in) }}</td>
                                            <td class="text-end text-danger">{{ $fmt($s->qty_out) }}</td>
                                            <td class="text-end fw-semibold">{{ $fmt($s->net) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">Tidak ada pergerakan
                                                ledger.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end">Total ledger</th>
                                        <th class="text-end">{{ $fmt($summary['ledger_total']) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- BUNDLE --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <strong>Bundle di WIP-CUT</strong>
                        <div class="text-muted small">QC OK = batas; wip_qty = stok bundle; ready = muncul di Ambil Jahit
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Bundle</th>
                                        <th class="text-end">QC OK</th>
                                        <th class="text-end">wip_qty</th>
                                        <th class="text-end">Dipick</th>
                                        <th class="text-end" title="wip_qty − dipick">Sisa</th>
                                        <th class="text-end">Ready</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($bundles as $b)
                                        <tr>
                                            <td class="small">
                                                {{ $b->bundle_code ?? ('#' . $b->id) }}
                                                <span class="badge bg-light text-muted">{{ $b->status }}</span>
                                            </td>
                                            <td class="text-end">{{ $fmt($b->qty_cutting_ok) }}</td>
                                            <td class="text-end fw-semibold">{{ $fmt($b->wip_qty) }}</td>
                                            <td class="text-end">{{ $fmt($b->picked) }}</td>
                                            <td class="text-end fw-semibold">{{ $fmt($b->net_wip) }}</td>
                                            <td class="text-end">{{ $fmt($b->ready) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">Tidak ada bundle di
                                                WIP-CUT.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>Σ</th>
                                        <th class="text-end">{{ $fmt($summary['sum_qty_cutting_ok']) }}</th>
                                        <th class="text-end">{{ $fmt($summary['sum_wip_qty']) }}</th>
                                        <th class="text-end">{{ $fmt($summary['sum_picked']) }}</th>
                                        <th class="text-end">{{ $fmt($summary['sum_net_wip']) }}</th>
                                        <th class="text-end">{{ $fmt($summary['sum_ready']) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- PERGERAKAN TERAKHIR --}}
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-white">
                <strong>100 Pergerakan Ledger Terakhir (WIP-CUT)</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 460px;">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Dokumen</th>
                                <th class="text-end">Ref ID</th>
                                <th class="text-end">Qty</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ledgerRecent as $m)
                                <tr>
                                    <td class="small">{{ $m->date }}</td>
                                    <td class="small">{{ class_basename($m->source_type ?? '(null)') }}</td>
                                    <td class="text-end small">{{ $m->source_id ?? '-' }}</td>
                                    <td class="text-end {{ $m->qty_change < 0 ? 'text-danger' : 'text-success' }}">
                                        {{ $m->qty_change > 0 ? '+' : '' }}{{ $fmt($m->qty_change) }}
                                    </td>
                                    <td class="small text-muted">{{ $m->notes }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Tidak ada pergerakan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
