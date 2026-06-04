@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');

    // Ringkasan jumlah event per jenis (untuk count chip)
    $cCutting = $timeline->where('type', 'cutting')->count();
    $cPickup = $timeline->where('type', 'pickup')->count();
    $cReturn = $timeline->where('type', 'return')->count();

    $typeMeta = [
        'cutting' => ['label' => 'Beres Cutting', 'verb' => 'memotong', 'dot' => 'gf-tl-blue', 'badge' => 'gf-badge-blue'],
        'pickup' => ['label' => 'Ambil Jahit', 'verb' => 'mengambil', 'dot' => 'gf-tl-amber', 'badge' => 'gf-badge-amber'],
        'return' => ['label' => 'Setor Jahit', 'verb' => 'menyetor', 'dot' => 'gf-tl-green', 'badge' => 'gf-badge-green'],
    ];
@endphp

{{-- ============ Alur Produksi (ringkas) ============ --}}
<x-gf.panel title="Alur Produksi" subtitle="Throughput periode terpilih (Cutting → Ambil → Setor → Finishing)" class="gf-hide-mobile">
    @php
        $steps = [
            ['Cutting OK', $summary['cutting_ok'], 'accent-blue'],
            ['Diambil Penjahit', $summary['pickup_total'], 'accent-amber'],
            ['Setor Jahit OK', $summary['sewing_ok'], 'accent-green'],
            ['Finishing OK', $summary['finishing_ok'], 'accent-green'],
        ];
        $funnelMax = max(1, collect($steps)->max(fn($s) => (float) $s[1]));
    @endphp
    <div class="gf-funnel">
        @foreach ($steps as [$label, $qty, $accent])
            <div class="gf-funnel-step {{ $accent }}">
                <div class="gf-funnel-label">{{ $label }}</div>
                <div class="gf-funnel-val">{{ $fmt($qty) }}</div>
                <div class="gf-bar-track">
                    <div class="gf-bar-fill" style="width: {{ round((float) $qty / $funnelMax * 100) }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="d-flex flex-wrap gap-3 mt-3 gf-subtext">
        <span>Reject total: <b class="text-danger">{{ $fmt($summary['total_reject']) }}</b> ({{ $summary['reject_rate'] }}%)</span>
        <span>Yield jahit: <b>{{ $summary['sewing_yield'] }}%</b></span>
        <span>Total output OK: <b>{{ $fmt($summary['total_ok']) }}</b></span>
    </div>
</x-gf.panel>

{{-- ============ Timeline aktivitas (siapa ngerjain apa) ============ --}}
<x-gf.panel title="Aktivitas Produksi" subtitle="Kronologis kejadian periode — beres cutting, ambil jahit, setor jahit">
    <div class="sj-toolbar" data-ov-toolbar>
        <input type="search" class="form-control sj-search" data-ov-search
            placeholder="Cari operator / kode dokumen…" autocomplete="off">

        <select class="form-select" data-ov-type aria-label="Jenis aktivitas">
            <option value="">Semua Aktivitas</option>
            <option value="cutting">Beres Cutting</option>
            <option value="pickup">Ambil Jahit</option>
            <option value="return">Setor Jahit</option>
        </select>

        <span class="sj-count" data-ov-count>{{ $fmt($timeline->count()) }} kejadian · {{ $fmt($cCutting) }} cutting · {{ $fmt($cPickup) }} ambil · {{ $fmt($cReturn) }} setor</span>
    </div>

    @if ($timeline->isEmpty())
        <div class="prod-empty">Tidak ada aktivitas pada periode ini.</div>
    @else
        <div class="gf-tl" data-ov-list>
            @php $lastDate = null; @endphp
            @foreach ($timeline as $ev)
                @php $meta = $typeMeta[$ev->type]; @endphp
                @if ($ev->date !== $lastDate)
                    @php $lastDate = $ev->date; @endphp
                    <div class="gf-tl-day" data-ov-day data-date="{{ $ev->date }}">
                        {{ \Carbon\Carbon::parse($ev->date)->translatedFormat('l, d M Y') }}
                    </div>
                @endif
                <div class="gf-tl-item" data-ov-row
                    data-type="{{ $ev->type }}"
                    data-search="{{ strtolower(trim($ev->operator_code . ' ' . $ev->operator_name . ' ' . $ev->code . ' ' . $meta['label'])) }}">
                    <div class="gf-tl-dot {{ $meta['dot'] }}"></div>
                    <div class="gf-tl-body">
                        <div class="gf-tl-main">
                            <div class="gf-tl-who">
                                <span class="gf-badge {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                                <span class="gf-chip" title="{{ $ev->operator_name }}"><b>{{ $ev->operator_code }}</b></span>
                                <span class="gf-tl-name">{{ $ev->operator_name }}</span>
                            </div>
                            <div class="gf-tl-qty">
                                @if ($ev->type === 'pickup')
                                    <span><b>{{ $fmt($ev->qty_total) }}</b> pcs</span>
                                @else
                                    <span><b>{{ $fmt($ev->qty_ok) }}</b> OK</span>
                                    @if ($ev->qty_reject > 0)
                                        <span class="text-danger">+{{ $fmt($ev->qty_reject) }} reject</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="gf-tl-sub">
                            @if ($ev->created_at)
                                <span class="gf-tl-code">{{ \Carbon\Carbon::parse($ev->created_at)->format('H:i') }}</span>
                                <span>·</span>
                            @endif
                            <span>{{ ucfirst($meta['verb']) }} {{ $fmt($ev->sku_count) }} SKU</span>
                            <span class="gf-tl-code">· {{ $ev->code }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="prod-empty" data-ov-empty hidden>Tidak ada aktivitas yang cocok dengan filter.</div>
    @endif
</x-gf.panel>
