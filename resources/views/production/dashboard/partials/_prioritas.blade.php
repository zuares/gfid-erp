@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $gradeClass = fn($g) => match ($g) {
        'Kritis' => 'gf-badge-red',
        'Tinggi' => 'gf-badge-amber',
        'Sedang' => 'gf-badge-blue',
        default => 'gf-badge-muted',
    };
@endphp

<x-gf.panel title="Prioritas Produksi" subtitle="SKU dengan cover stok tipis — dahulukan produksi">
    @if ($priority->isEmpty())
        <div class="prod-empty">Tidak ada data prioritas.</div>
    @else
        <div class="gf-table-scroll">
            <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Produk</th>
                        <th class="gf-num">Ready</th>
                        <th class="gf-num">WIP</th>
                        <th class="gf-num">Jual/hari</th>
                        <th class="gf-num">Cover (hr)</th>
                        <th class="gf-num">Skor</th>
                        <th>Prioritas</th>
                        <th>Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($priority as $p)
                        <tr class="{{ $p->grade === 'Kritis' ? 'gf-row-warn' : '' }}">
                            <td><span class="gf-chip" title="{{ $p->product }}"><b>{{ $p->sku }}</b></span></td>
                            <td class="text-muted">{{ $p->category }}</td>
                            <td class="gf-num">{{ $fmt($p->ready) }}</td>
                            <td class="gf-num">{{ $fmt($p->wip) }}</td>
                            <td class="gf-num">{{ $fmt($p->ads, 1) }}</td>
                            <td class="gf-num">{{ $p->cover_days === null ? '–' : $fmt($p->cover_days, 1) }}</td>
                            <td class="gf-num"><b>{{ $fmt($p->score, 1) }}</b></td>
                            <td><span class="gf-badge {{ $gradeClass($p->grade) }}">{{ $p->grade }}</span></td>
                            <td class="text-muted small">{{ $p->reason }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-gf.panel>
