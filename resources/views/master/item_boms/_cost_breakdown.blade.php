@php
    $includedComponents = collect($bomEstimate['components'] ?? [])
        ->filter(fn (array $component): bool => (bool) ($component['included_in_estimate'] ?? false));
    $costGroups = [
        \App\Models\ItemBomLine::STAGE_MAIN_MATERIAL => [
            'label' => 'Bahan baku utama',
            'description' => 'Material utama yang membentuk produk.',
            'icon' => 'bi-box-seam',
            'tone' => 'material',
        ],
        \App\Models\ItemBomLine::STAGE_SEWING_SUPPLY => [
            'label' => 'Kelengkapan jahit',
            'description' => 'Aksesori dan kebutuhan proses jahit.',
            'icon' => 'bi-scissors',
            'tone' => 'sewing',
        ],
        \App\Models\ItemBomLine::STAGE_PACKING_SUPPLY => [
            'label' => 'Kelengkapan packing',
            'description' => 'Kebutuhan pengemasan produk.',
            'icon' => 'bi-bag-check',
            'tone' => 'packing',
        ],
    ];
@endphp

<section class="bom-cost-breakdown" aria-label="Rincian estimasi HPP BOM">
    <div class="bom-cost-breakdown-head">
        <div>
            <h2>Rincian estimasi HPP BOM</h2>
            <p>Komponen yang dihitung dan upah per proses untuk 1 {{ $bomEstimate['item_unit'] }}.</p>
        </div>
        <div class="bom-cost-total-badge">
            <span>Total HPP BOM</span>
            <strong>Rp {{ number_format((float) $bomEstimate['total'], 0, ',', '.') }}</strong>
        </div>
    </div>

    <div class="bom-cost-section-heading">
        <span class="bom-cost-section-number">1</span>
        <div>
            <h3>Bahan &amp; perlengkapan</h3>
            <p>Biaya komponen yang dihitung untuk 1 {{ $bomEstimate['item_unit'] }}.</p>
        </div>
    </div>

    <div class="bom-cost-groups">
        @foreach($costGroups as $stage => $group)
            @php
                $stageComponents = $includedComponents->where('usage_stage', $stage)->values();
                $stageTotal = (float) $stageComponents->sum('estimated_cost');
            @endphp
            <section class="bom-cost-group-card bom-cost-group-card-{{ $group['tone'] }}" aria-labelledby="bom-cost-group-{{ $stage }}">
                <header class="bom-cost-group-head">
                    <div class="bom-cost-group-title-wrap">
                        <span class="bom-cost-group-icon" aria-hidden="true"><i class="bi {{ $group['icon'] }}"></i></span>
                        <div>
                            <h3 id="bom-cost-group-{{ $stage }}">{{ $group['label'] }}</h3>
                            <p>{{ $group['description'] }}</p>
                        </div>
                    </div>
                    <div class="bom-cost-group-total">
                        <span>Ringkasan · {{ $stageComponents->count() }} item</span>
                        <strong>Rp {{ number_format($stageTotal, 0, ',', '.') }}</strong>
                    </div>
                </header>

                @if($stageComponents->isNotEmpty())
                    <div class="bom-cost-component-list" role="list">
                        @foreach($stageComponents as $component)
                            <article class="bom-cost-component-row" role="listitem">
                                <div class="bom-cost-component-identity">
                                    <strong>{{ $component['code'] ?: '-' }}</strong>
                                    <span>{{ $component['name'] ?: 'Komponen' }}</span>
                                    @if($component['is_optional'] ?? false)
                                        <em>Opsional terpilih</em>
                                    @endif
                                </div>
                                <div class="bom-cost-metric bom-cost-purchase">
                                    <span>Harga terakhir beli</span>
                                    @if((float) ($component['last_purchase_price'] ?? 0) > 0)
                                        <strong>Rp {{ number_format((float) $component['last_purchase_price'], 0, ',', '.') }}</strong>
                                        <small>/{{ $component['purchase_unit'] ?: 'pcs' }}</small>
                                    @else
                                        <strong class="is-empty">Belum ada harga</strong>
                                    @endif
                                </div>
                                <div class="bom-cost-metric">
                                    <span>Qty pemakaian</span>
                                    <strong>{{ number_format((float) ($component['qty_with_scrap'] ?? 0), 3, ',', '.') }} <small>{{ $component['uom'] }}</small></strong>
                                </div>
                                <div class="bom-cost-metric bom-cost-line-total">
                                    <span>Biaya per {{ $bomEstimate['item_unit'] }}</span>
                                    <strong>Rp {{ number_format((float) ($component['estimated_cost'] ?? 0), 0, ',', '.') }}</strong>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="bom-cost-empty">
                        <i class="bi bi-inbox" aria-hidden="true"></i>
                        <span>Belum ada komponen yang dihitung dalam kelompok ini.</span>
                    </div>
                @endif
            </section>
        @endforeach
    </div>

    <div class="bom-cost-summary-row">
        <span>Subtotal bahan &amp; perlengkapan</span>
        <strong>Rp {{ number_format((float) $bomEstimate['material_total'], 0, ',', '.') }}</strong>
    </div>

    <section class="bom-cost-wage-card" aria-labelledby="bom-cost-wage-title">
        <header class="bom-cost-section-heading">
            <span class="bom-cost-section-number is-wage">2</span>
            <div>
                <h3 id="bom-cost-wage-title">Upah produksi</h3>
                <p>Estimasi upah per proses untuk 1 {{ $bomEstimate['item_unit'] }}.</p>
            </div>
            <strong class="bom-cost-wage-total"><span>Subtotal upah produksi</span> Rp {{ number_format((float) $bomEstimate['production_wage_total'], 0, ',', '.') }}</strong>
        </header>
        <div class="bom-cost-wage-list">
            @foreach(($bomEstimate['production_wage']['processes'] ?? []) as $process)
                <div class="bom-cost-wage-row">
                    <div>
                        <strong>{{ $process['label'] }}</strong>
                        <small>{{ $process['source_label'] }}</small>
                    </div>
                    <span>{{ (float) ($process['rate'] ?? 0) > 0 ? 'Rp '.number_format((float) $process['rate'], 0, ',', '.') : '-' }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <footer class="bom-cost-grand-total">
        <span>Total HPP BOM</span>
        <strong>Rp {{ number_format((float) $bomEstimate['total'], 0, ',', '.') }}</strong>
    </footer>
</section>
