@php
    /** Var: title, rows (array), link (url|null) */
    $qty = fn ($value) => number_format((float) $value, 0, ',', '.');
@endphp
<div class="dpanel">
    <div class="dpanel-head">
        <div class="t"><i class="bi bi-box-seam"></i> {{ $title }}</div>
        @if(!empty($link))<a href="{{ $link }}">Lihat semua <i class="bi bi-arrow-right-short"></i></a>@endif
    </div>
    <div class="dpanel-body">
        @if(!empty($rows))
            <div class="ditem-table-wrap">
                @php
                    $categorySummaries = [];
                    $summaryGap = 0;
                    $summaryGapKnown = true;
                    $summaryProductionStock = 0;
                    $summaryProductionKnown = true;
                    $summaryWipStock = 0;
                    $summaryWipKnown = true;
                    foreach ($rows as $summaryRow) {
                        $summaryCategory = $summaryRow['category'] ?? 'Tanpa kategori';
                        $summary = $categorySummaries[$summaryCategory] ?? [
                            'demand' => 0,
                            'stock' => 0,
                            'stock_known' => true,
                            'production_stock' => 0,
                            'production_known' => true,
                            'gap' => 0,
                        ];
                        $summary['demand'] += (float) ($summaryRow['demand_qty'] ?? 0);
                        if (($summaryRow['stock_qty'] ?? null) === null) {
                            $summary['stock_known'] = false;
                            $summaryGapKnown = false;
                        } else {
                            $rowStock = (float) $summaryRow['stock_qty'];
                            $rowDemand = (float) ($summaryRow['demand_qty'] ?? 0);
                            $rowGap = min(0, $rowStock - $rowDemand);
                            $summary['stock'] += $rowStock;
                            $summary['gap'] += $rowGap;
                            $summaryGap += $rowGap;
                        }
                        if (($summaryRow['production_stock_qty'] ?? null) === null) {
                            $summary['production_known'] = false;
                            $summaryProductionKnown = false;
                        } else {
                            $productionStock = (float) $summaryRow['production_stock_qty'];
                            $summary['production_stock'] += $productionStock;
                            $summaryProductionStock += $productionStock;
                        }
                        if (($summaryRow['wip_stock_qty'] ?? null) === null) {
                            $summary['wip_known'] = false;
                            $summaryWipKnown = false;
                        } else {
                            $wipStock = (float) $summaryRow['wip_stock_qty'];
                            $summary['wip_stock'] = ($summary['wip_stock'] ?? 0) + $wipStock;
                            $summaryWipStock += $wipStock;
                        }
                        $categorySummaries[$summaryCategory] = $summary;
                    }
                    $summaryGapState = !$summaryGapKnown ? 'unknown' : ($summaryGap < 0 ? 'short' : 'safe');
                @endphp
                <div class="ditem-summary" aria-label="Ringkasan permintaan stok">
                    <div class="ditem-summary-item">
                        <span><i class="bi bi-tags" aria-hidden="true"></i> Kategori</span>
                        <strong>{{ count($categorySummaries) }}</strong>
                    </div>
                    <div class="ditem-summary-item">
                        <span><i class="bi bi-box-seam" aria-hidden="true"></i> Item</span>
                        <strong>{{ count($rows) }}</strong>
                    </div>
                    <div class="ditem-summary-item">
                        <span><i class="bi bi-arrow-down-right" aria-hidden="true"></i> Selisih kurang</span>
                        <strong class="ditem-summary-gap {{ $summaryGapState }}">
                            {{ $summaryGapKnown ? $qty($summaryGap) : '—' }}
                        </strong>
                    </div>
                    <div class="ditem-summary-item">
                        <span><i class="bi bi-gear-wide-connected" aria-hidden="true"></i> Stok produksi</span>
                        <strong>{{ $summaryProductionKnown ? $qty($summaryProductionStock) . ' pcs' : '—' }}</strong>
                    </div>
                    <div class="ditem-summary-item">
                        <span><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Stok WIP</span>
                        <strong>{{ $summaryWipKnown ? $qty($summaryWipStock) . ' pcs' : '—' }}</strong>
                    </div>
                </div>
                <table class="ditem-table">
                    <thead>
                        <tr>
                            <th class="numeric">No</th>
                            <th>Kode item internal</th>
                            <th class="numeric">Permintaan</th>
                            <th class="numeric ditem-mobile-secondary">Stok FG/RTS</th>
                            <th class="numeric ditem-mobile-secondary">Stok produksi</th>
                            <th class="numeric ditem-mobile-secondary">Stok WIP</th>
                            <th class="numeric">Selisih kurang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $lastCategory = null;
                            $categoryItemNumber = 0;
                        @endphp
                        @foreach($rows as $row)
                            @php
                                $category = $row['category'] ?? 'Tanpa kategori';
                                $demand = (float) ($row['demand_qty'] ?? 0);
                                $stock = $row['stock_qty'] ?? null;
                                $productionStock = $row['production_stock_qty'] ?? null;
                                $wipStock = $row['wip_stock_qty'] ?? null;
                                $wipStages = [
                                    'CUT' => $row['wip_cut_qty'] ?? null,
                                    'SEW' => $row['wip_sew_qty'] ?? null,
                                    'FIN' => $row['wip_fin_qty'] ?? null,
                                    'PACK' => $row['wip_pack_qty'] ?? null,
                                ];
                                $short = $stock !== null && $stock < $demand;
                                $gap = $stock === null ? null : $stock - $demand;
                            @endphp
                            @if($category !== $lastCategory)
                                @php
                                    $categorySummary = $categorySummaries[$category] ?? [
                                        'demand' => 0,
                                        'stock' => 0,
                                        'stock_known' => false,
                                        'production_stock' => 0,
                                        'production_known' => false,
                                        'wip_stock' => 0,
                                        'wip_known' => false,
                                        'gap' => 0,
                                    ];
                                    $categoryGap = $categorySummary['stock_known'] ? $categorySummary['gap'] : null;
                                    $categoryGapState = $categoryGap === null
                                        ? 'unknown'
                                        : ($categoryGap < 0 ? 'short' : 'safe');
                                @endphp
                                <tr class="ditem-category-row">
                                    <td></td>
                                    <td><span class="ditem-category-name"><i class="bi bi-tag"></i> {{ $category }}</span></td>
                                    <td class="numeric ditem-category-total" title="Total permintaan kategori">{{ $qty($categorySummary['demand']) }} pcs</td>
                                    <td class="ditem-category-empty ditem-mobile-secondary" aria-hidden="true"></td>
                                    <td class="ditem-category-empty ditem-mobile-secondary" aria-hidden="true"></td>
                                    <td class="ditem-category-empty ditem-mobile-secondary" aria-hidden="true"></td>
                                    <td class="numeric ditem-gap ditem-category-total">
                                        <span class="ditem-category-gap-value {{ $categoryGapState }}" title="Selisih stok FG/RTS dikurangi permintaan kategori">
                                            {{ $categoryGap === null ? '—' : $qty($categoryGap) }}
                                        </span>
                                    </td>
                                </tr>
                                @php
                                    $lastCategory = $category;
                                    $categoryItemNumber = 0;
                                @endphp
                            @endif
                            @php
                                $categoryItemNumber++;
                            @endphp
                            <tr>
                                <td class="numeric ditem-number">{{ $categoryItemNumber }}</td>
                                <td>
                                    <div class="ditem-code">{{ $row['code'] ?: 'Belum mapping' }}</div>
                                </td>
                                <td class="numeric ditem-demand">{{ $qty($demand) }} pcs</td>
                                <td class="numeric ditem-stock ditem-mobile-secondary {{ $short ? 'short' : '' }}">
                                    {{ $stock === null ? '—' : $qty($stock) . ' pcs' }}
                                </td>
                                <td class="numeric ditem-production-stock ditem-mobile-secondary {{ $productionStock !== null && $productionStock > 0 ? 'has-stock' : '' }}">
                                    @if($productionStock === null)
                                        —
                                    @elseif($productionStock > 0)
                                        <span class="ditem-production-indicator" title="Ada stok di proses produksi">
                                            <i class="bi bi-gear-wide-connected ditem-production-icon" aria-hidden="true"></i>
                                            <span>{{ $qty($productionStock) }} pcs</span>
                                        </span>
                                    @else
                                        0 pcs
                                    @endif
                                </td>
                                <td class="numeric ditem-production-stock ditem-mobile-secondary {{ $wipStock !== null && $wipStock > 0 ? 'has-stock' : '' }}">
                                    @if($wipStock === null)
                                        —
                                    @elseif($wipStock > 0)
                                        <span class="ditem-production-indicator" title="Ada stok setengah jadi di proses produksi">
                                            <i class="bi bi-arrow-repeat ditem-production-icon" aria-hidden="true"></i>
                                            <span>{{ $qty($wipStock) }} pcs</span>
                                        </span>
                                    @else
                                        0 pcs
                                    @endif
                                    @if($wipStock !== null)
                                        <div class="ditem-wip-breakdown" title="CUT {{ $wipStages['CUT'] === null ? '—' : $qty($wipStages['CUT']) }} · SEW {{ $wipStages['SEW'] === null ? '—' : $qty($wipStages['SEW']) }} · FIN {{ $wipStages['FIN'] === null ? '—' : $qty($wipStages['FIN']) }} · PACK {{ $wipStages['PACK'] === null ? '—' : $qty($wipStages['PACK']) }}">
                                            @foreach($wipStages as $stage => $stageQty)
                                                <span>{{ substr($stage, 0, 1) }}{{ $stageQty === null ? '—' : $qty($stageQty) }}</span>@if(!$loop->last) · @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="numeric ditem-gap">
                                    @php
                                        $gap = $gap === null ? null : min(0, $gap);
                                        $gapState = $gap === null ? 'unknown' : ($gap < 0 ? 'short' : 'safe');
                                    @endphp
                                    <span class="ditem-gap-indicator {{ $gapState }}"
                                          title="{{ $gapState === 'safe' ? 'Tidak ada kekurangan' : ($gapState === 'short' ? 'Stok kurang' : 'Stok belum diketahui') }}">
                                        <i class="bi {{ $gapState === 'safe' ? 'bi-check-circle-fill' : ($gapState === 'short' ? 'bi-exclamation-circle-fill' : 'bi-question-circle') }} ditem-gap-icon" aria-hidden="true"></i>
                                        <span>{{ $gap === null ? '—' : $qty($gap) }}</span>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="dash-empty"><i class="bi bi-check2-circle"></i> Tidak ada permintaan yang menunggu. Aman!</div>
        @endif
    </div>
</div>
