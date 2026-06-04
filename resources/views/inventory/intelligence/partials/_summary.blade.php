@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');

    // SKU paling berisiko (cover tertipis di antara yang punya demand) — untuk daftar cepat.
    $critical = $rows
        ->whereIn('status', ['stockout', 'kritis', 'menipis'])
        ->sortBy(fn($r) => $r->cover_days ?? 0)
        ->take(8)
        ->values();

    $statusLabel = [
        'stockout' => 'Stockout',
        'kritis' => 'Kritis',
        'menipis' => 'Menipis',
        'sehat' => 'Sehat',
        'no_demand' => 'Tanpa demand',
    ];
@endphp

<div class="gf-overview-kpi-grid">
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong">
        <div class="gf-overview-kpi-label">Perlu Perhatian</div>
        <div class="gf-overview-kpi-value">{{ $fmt($summary['below_target']) }}</div>
        <div class="gf-overview-kpi-note">stockout + kritis + menipis</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Stockout</div>
        <div class="gf-overview-kpi-value">{{ $fmt($summary['stockout']) }}</div>
        <div class="gf-overview-kpi-note">ready 0 tapi ada demand</div>
    </div>
    <div class="gf-overview-kpi-card gf-hide-mobile">
        <div class="gf-overview-kpi-label">Sehat</div>
        <div class="gf-overview-kpi-value">{{ $fmt($summary['sehat']) }}</div>
        <div class="gf-overview-kpi-note">cover ≥ 21 hari</div>
    </div>
    <div class="gf-overview-kpi-card gf-hide-mobile">
        <div class="gf-overview-kpi-label">Cover Tertipis</div>
        <div class="gf-overview-kpi-value">{{ $summary['tightest_cover'] === null ? '–' : $fmt($summary['tightest_cover'], 1) }}</div>
        <div class="gf-overview-kpi-note">hari stok ready</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Total Ready</div>
        <div class="gf-overview-kpi-value">{{ $fmt($summary['total_ready']) }}</div>
        <div class="gf-overview-kpi-note">pcs siap jual</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Saran Produksi</div>
        <div class="gf-overview-kpi-value">{{ $fmt($summary['total_suggested']) }}</div>
        <div class="gf-overview-kpi-note">pcs (target cover 21 hari)</div>
    </div>
</div>

<x-gf.panel title="SKU Paling Berisiko" subtitle="Cover stok tertipis — dahulukan produksi / restock">
    @if ($critical->isEmpty())
        <div class="ii-empty">Tidak ada SKU berisiko. Semua stok sehat.</div>
    @else
        <div class="gf-table-scroll">
            <table class="table table-hover align-middle mb-0 gf-clean-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th class="gf-hide-mobile">Kategori</th>
                        <th class="gf-num">Ready</th>
                        <th class="gf-num gf-hide-mobile">Jual/hari</th>
                        <th class="gf-num">Cover (hr)</th>
                        <th>Status</th>
                        <th class="gf-num">Saran Produksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($critical as $r)
                        <tr>
                            <td><span class="gf-chip" title="{{ $r->product }}"><b>{{ $r->sku }}</b></span></td>
                            <td class="text-muted gf-hide-mobile">{{ $r->category }}</td>
                            <td class="gf-num">{{ $fmt($r->ready) }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $fmt($r->ads, 1) }}</td>
                            <td class="gf-num">{{ $r->cover_days === null ? '–' : $fmt($r->cover_days, 1) }}</td>
                            <td>
                                <span class="ii-status ii-status-{{ $r->status }}">
                                    <span class="ii-status-dot"></span>{{ $statusLabel[$r->status] ?? $r->status }}
                                </span>
                            </td>
                            <td class="gf-num"><b>{{ $fmt($r->suggested_qty) }}</b></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-gf.panel>
