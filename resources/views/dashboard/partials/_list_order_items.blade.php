@php
    /** Var: title, rows (array), link (url|null) */
    $qty = fn ($value) => number_format((float) $value, 0, ',', '.');
    $accordion = (bool) ($accordion ?? false);
@endphp
@if($accordion)
<details class="dpanel shipment-accordion">
    <summary class="dpanel-head">
@else
<div class="dpanel">
    <div class="dpanel-head">
@endif
        <div class="t"><i class="bi bi-box-seam"></i> {{ $title }}</div>
        @if($accordion)
            <span class="shipment-accordion-toggle" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
        @elseif(!empty($link))
            <a href="{{ $link }}">Lihat semua <i class="bi bi-arrow-right-short"></i></a>
        @endif
@if($accordion)
    </summary>
@else
    </div>
@endif
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
                            'wip_stock' => 0,
                            'wip_known' => true,
                            'sewing' => 0,
                            'sewing_known' => true,
                            'not_sewn' => 0,
                            'not_sewn_known' => true,
                            'gap' => 0,
                        ];
                        $summary['demand'] += (float) ($summaryRow['demand_qty'] ?? 0);
                        $rowStock = $summaryRow['stock_qty'] ?? null;
                        $rowProductionStock = $summaryRow['production_stock_qty'] ?? null;
                        $rowDemand = (float) ($summaryRow['demand_qty'] ?? 0);
                        if ($rowStock === null) {
                            $summary['stock_known'] = false;
                            $summaryGapKnown = false;
                        } else {
                            $summary['stock'] += (float) $rowStock;
                        }
                        if ($rowProductionStock === null) {
                            $summary['production_known'] = false;
                            $summaryProductionKnown = false;
                            $summary['stock_known'] = false;
                            $summaryGapKnown = false;
                        } else {
                            $productionStock = (float) $rowProductionStock;
                            $summary['production_stock'] += $productionStock;
                            $summaryProductionStock += $productionStock;
                        }
                        if ($rowStock !== null && $rowProductionStock !== null) {
                            $rowGap = min(0, (float) $rowStock + (float) $rowProductionStock - $rowDemand);
                            $summary['gap'] += $rowGap;
                            $summaryGap += $rowGap;
                        }
                        if (($summaryRow['wip_stock_qty'] ?? null) === null) {
                            $summary['wip_known'] = false;
                            $summaryWipKnown = false;
                        } else {
                            $wipStock = (float) $summaryRow['wip_stock_qty'];
                            $summary['wip_stock'] = ($summary['wip_stock'] ?? 0) + $wipStock;
                            $summaryWipStock += $wipStock;
                        }
                        $summarySewingQty = $summaryRow['sewing_qty'] ?? ($summaryRow['wip_sew_qty'] ?? null);
                        $summaryNotSewnQty = $summaryRow['not_sewn_qty'] ?? ($summaryRow['wip_cut_qty'] ?? null);
                        if ($summarySewingQty === null) {
                            $summary['sewing_known'] = false;
                        } else {
                            $sewingQty = (float) $summarySewingQty;
                            $summary['sewing'] = ($summary['sewing'] ?? 0) + $sewingQty;
                        }
                        if ($summaryNotSewnQty === null) {
                            $summary['not_sewn_known'] = false;
                        } else {
                            $notSewnQty = (float) $summaryNotSewnQty;
                            $summary['not_sewn'] = ($summary['not_sewn'] ?? 0) + $notSewnQty;
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
                <div class="ditem-legend" role="note">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    <span><strong>Selisih kurang</strong> = (stok RTS + stok produksi) dikurangi permintaan. Angka negatif berarti jumlah yang masih kurang.</span>
                </div>
                <table class="ditem-table">
                    <caption class="visually-hidden">{{ $title }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="numeric">No</th>
                            <th scope="col">Kode item internal</th>
                            <th scope="col" class="numeric">Permintaan</th>
                            <th scope="col" class="numeric ditem-mobile-secondary">Stok RTS</th>
                            <th scope="col" class="numeric ditem-mobile-secondary">Stok produksi</th>
                            <th scope="col" class="numeric">Belum jahit</th>
                            <th scope="col" class="numeric">Sedang jahit</th>
                            <th scope="col" class="numeric ditem-mobile-secondary">Stok WIP</th>
                            <th scope="col" class="numeric" title="(Stok RTS + stok produksi) dikurangi permintaan">Selisih kurang</th>
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
                                $sewingQty = $row['sewing_qty'] ?? ($row['wip_sew_qty'] ?? null);
                                $notSewnQty = $row['not_sewn_qty'] ?? ($row['wip_cut_qty'] ?? null);
                                $stock = $row['stock_qty'] ?? null;
                                $productionStock = $row['production_stock_qty'] ?? null;
                                $wipStock = $row['wip_stock_qty'] ?? null;
                                $wipStages = [
                                    'CUT' => $row['wip_cut_qty'] ?? null,
                                    'SEW' => $row['wip_sew_qty'] ?? null,
                                    'FIN' => $row['wip_fin_qty'] ?? null,
                                    'PACK' => $row['wip_pack_qty'] ?? null,
                                ];
                                $gap = ($stock === null || $productionStock === null)
                                    ? null
                                    : $stock + $productionStock - $demand;
                                $short = $gap !== null && $gap < 0;
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
                                        'sewing' => 0,
                                        'sewing_known' => false,
                                        'not_sewn' => 0,
                                        'not_sewn_known' => false,
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
                                    <td class="numeric ditem-category-total">{{ $categorySummary['not_sewn_known'] ? $qty($categorySummary['not_sewn']) . ' pcs' : '—' }}</td>
                                    <td class="numeric ditem-category-total">{{ $categorySummary['sewing_known'] ? $qty($categorySummary['sewing']) . ' pcs' : '—' }}</td>
                                    <td class="ditem-category-empty ditem-mobile-secondary" aria-hidden="true"></td>
                                    <td class="numeric ditem-gap ditem-category-total">
                                        <span class="ditem-category-gap-value {{ $categoryGapState }}" title="(Stok RTS + stok produksi) dikurangi permintaan kategori">
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
                                <td class="numeric ditem-not-sewn {{ $notSewnQty !== null && $notSewnQty > 0 ? 'has-stock' : '' }}"
                                    title="Stok WIP-CUT, sudah dipotong tetapi belum masuk proses jahit">
                                    {{ $notSewnQty === null ? '—' : $qty($notSewnQty) . ' pcs' }}
                                </td>
                                <td class="numeric ditem-sewing {{ $sewingQty !== null && $sewingQty > 0 ? 'has-stock' : '' }}"
                                    title="Stok WIP-SEW, sedang dikerjakan penjahit">
                                    {{ $sewingQty === null ? '—' : $qty($sewingQty) . ' pcs' }}
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
@if($accordion)
</details>
@else
</div>
@endif
