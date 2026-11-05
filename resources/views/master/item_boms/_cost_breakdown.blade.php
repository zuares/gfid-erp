@php
    $includedComponents = collect($bomEstimate['components'] ?? [])
        ->filter(fn (array $component): bool => (bool) ($component['included_in_estimate'] ?? false));
    $costGroups = [
        \App\Models\ItemBomLine::STAGE_MAIN_MATERIAL => 'Bahan baku utama',
        \App\Models\ItemBomLine::STAGE_SEWING_SUPPLY => 'Kelengkapan jahit',
        \App\Models\ItemBomLine::STAGE_PACKING_SUPPLY => 'Kelengkapan packing',
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

    <div class="table-responsive">
        <table class="table bom-cost-breakdown-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Kelompok</th>
                    <th>Komponen / proses</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Harga beli terakhir</th>
                    <th class="text-end">Biaya</th>
                </tr>
            </thead>
            <tbody>
                @foreach($costGroups as $stage => $stageLabel)
                    @php
                        $stageComponents = $includedComponents->where('usage_stage', $stage);
                    @endphp
                    @forelse($stageComponents as $component)
                        <tr>
                            <td class="bom-cost-group">{{ $stageLabel }}</td>
                            <td class="bom-cost-component">{{ $component['code'] ?: '-' }} — {{ $component['name'] ?: 'Komponen' }}</td>
                            <td class="text-end">{{ number_format((float) ($component['qty_with_scrap'] ?? 0), 3, ',', '.') }} {{ $component['uom'] }}</td>
                            <td class="text-end bom-cost-purchase">
                                @if((float) ($component['last_purchase_price'] ?? 0) > 0)
                                    Rp {{ number_format((float) $component['last_purchase_price'], 0, ',', '.') }}
                                    <small>/{{ $component['purchase_unit'] ?: 'pcs' }}</small>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end bom-cost-amount">Rp {{ number_format((float) ($component['estimated_cost'] ?? 0), 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="bom-cost-group">{{ $stageLabel }}</td>
                            <td class="bom-cost-muted" colspan="2">Belum ada komponen yang dihitung</td>
                            <td class="text-end">-</td>
                            <td class="text-end">Rp 0</td>
                        </tr>
                    @endforelse
                @endforeach
                <tr class="bom-cost-subtotal">
                    <td colspan="4">Subtotal bahan &amp; perlengkapan</td>
                    <td class="text-end">Rp {{ number_format((float) $bomEstimate['material_total'], 0, ',', '.') }}</td>
                </tr>
                @foreach(($bomEstimate['production_wage']['processes'] ?? []) as $process)
                    <tr>
                        <td class="bom-cost-group">Upah produksi</td>
                        <td class="bom-cost-component">
                            {{ $process['label'] }}
                            <small class="bom-cost-source">{{ $process['source_label'] }}</small>
                        </td>
                        <td class="text-end">per 1 {{ $bomEstimate['item_unit'] }}</td>
                        <td class="text-end bom-cost-purchase">-</td>
                        <td class="text-end bom-cost-amount">
                            {{ (float) ($process['rate'] ?? 0) > 0 ? 'Rp '.number_format((float) $process['rate'], 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                @endforeach
                <tr class="bom-cost-subtotal">
                    <td colspan="4">Subtotal upah produksi</td>
                    <td class="text-end">Rp {{ number_format((float) $bomEstimate['production_wage_total'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="bom-cost-grand-total">
                    <td colspan="4">Total HPP BOM</td>
                    <td class="text-end">Rp {{ number_format((float) $bomEstimate['total'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
