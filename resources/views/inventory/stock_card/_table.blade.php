@php
    $itemId = $filters['item_id'] ?? null;
    $rows = $mutations instanceof \Illuminate\Pagination\AbstractPaginator ? $mutations->getCollection() : $mutations;
    $unit = $selectedItem->unit ?? '';
@endphp

<div id="sc_table_wrap">
    <div class="card-main" id="sc_table_card">
        <div class="table-responsive">
            <table class="table table-list mb-0">
                <thead>
                    @if (!$itemId)
                        <tr>
                            <th style="width:105px">Tgl</th>
                            <th style="width:170px">Item</th>
                            <th style="width:170px">Gudang</th>
                            <th style="width:110px">LOT</th>
                            <th style="width:160px">Sumber</th>
                            <th class="text-center" style="width:130px">IN</th>
                            <th class="text-center" style="width:130px">OUT</th>
                            @if ($canViewCost ?? false)
                                <th class="text-end hide-sm" style="width:150px">Nilai</th>
                            @endif
                            <th class="hide-sm">Catatan</th>
                        </tr>
                    @else
                        <tr>
                            <th style="width:105px">Tgl</th>
                            <th style="width:180px">Gudang</th>
                            <th style="width:110px">LOT</th>
                            <th style="width:160px">Sumber</th>
                            <th class="text-center" style="width:130px">IN</th>
                            <th class="text-center" style="width:130px">OUT</th>
                            <th class="text-end" style="width:140px">Saldo</th>
                            @if ($canViewCost ?? false)
                                <th class="text-end hide-sm" style="width:150px">Nilai</th>
                                <th class="text-end" style="width:160px">Saldo Nilai</th>
                            @endif
                            <th class="hide-sm">Catatan</th>
                        </tr>
                    @endif
                </thead>

                <tbody>
                    @if ($itemId)
                        <tr>
                            <td><span class="sub-badge mono">Saldo</span></td>
                            <td class="muted">—</td>
                            <td class="muted">—</td>
                            <td class="muted">—</td>

                            <td class="text-end">
                                <div class="qty-cell mono"><span class="qty-num qty-zero">0,00</span><span
                                        class="qty-unit">{{ $unit }}</span></div>
                            </td>
                            <td class="text-end">
                                <div class="qty-cell mono"><span class="qty-num qty-zero">0,00</span><span
                                        class="qty-unit">{{ $unit }}</span></div>
                            </td>

                            <td class="text-end mono">{{ number_format($openingQty ?? 0, 2, ',', '.') }}</td>
                            @if ($canViewCost ?? false)
                                <td class="text-end mono hide-sm muted">0</td>
                                <td class="text-end mono {{ ($openingValue ?? 0) < 0 ? 'text-danger' : '' }}">
                                    {{ number_format($openingValue ?? 0, 0, ',', '.') }}</td>
                            @endif
                            <td class="muted hide-sm">—</td>
                        </tr>
                    @endif

                    @forelse($rows as $m)
                        @php
                            $qtyAbs = abs((float) $m->qty_change);
                            $isIn = ($m->direction ?? null) === 'in';
                            $isOut = ($m->direction ?? null) === 'out';
                            $in = $isIn ? $qtyAbs : 0;
                            $out = $isOut ? $qtyAbs : 0;

                            $val = (float) ($m->line_value ?? ($m->total_cost ?? 0));

                            $wh = $m->warehouse ? $m->warehouse->code : '-';
                            $lot = $m->lot?->code ?? '-';
                            
                            $srcLabelMap = [
                                'purchase_receipt' => 'GRN Masuk',
                                'purchase_receipt_reverse' => 'Batal GRN',
                                'transfer_out' => 'TF Keluar',
                                'transfer_in' => 'TF Masuk',
                                'adjustment' => 'Opname',
                                'cutting_issue' => 'Ke Cutting',
                                'cutting_receive' => 'Dr Cutting',
                                'sewing_issue' => 'Ke Sewing',
                                'sewing_receive' => 'Dr Sewing',
                            ];
                            $cleanSrc = $srcLabelMap[$m->source_type] ?? ($availableSourceTypes[$m->source_type] ?? $m->source_type);

                            $rowUnit = $itemId ? $unit : $m->item->unit ?? '';
                        @endphp

                        <tr>
                            <td class="mono muted">{{ optional($m->date)->format('d/m/y') ?? $m->date }}</td>

                            @if (!$itemId)
                                <td class="mono">
                                    {{ $m->item->code ?? '-' }}
                                </td>
                            @endif

                            <td><span class="sub-badge mono">{{ $wh }}</span></td>
                            <td class="mono">{{ $lot }}</td>

                            <td>
                                <span class="badge bg-light text-dark mono">{{ $cleanSrc }}</span>
                                @if($m->source_id)
                                    <div class="small muted mt-1">#{{ $m->source_id }}</div>
                                @endif
                            </td>

                            <td class="text-end">
                                <div class="qty-cell mono">
                                    <span class="qty-num qty-in-num">{{ number_format($in, 2, ',', '.') }}</span>
                                    <span class="qty-unit">{{ $rowUnit }}</span>
                                </div>
                            </td>

                            <td class="text-end">
                                <div class="qty-cell mono">
                                    <span class="qty-num qty-out-num">{{ number_format($out, 2, ',', '.') }}</span>
                                    <span class="qty-unit">{{ $rowUnit }}</span>
                                </div>
                            </td>

                            @if ($itemId)
                                <td class="text-end mono">
                                    {{ number_format((float) ($m->running_qty ?? 0), 2, ',', '.') }}
                                </td>
                            @endif

                            @if ($canViewCost ?? false)
                                <td class="text-end mono hide-sm {{ $val < 0 ? 'text-danger' : '' }}">
                                    {{ number_format($val, 0, ',', '.') }}</td>

                                @if ($itemId)
                                    <td
                                        class="text-end mono fw-semibold {{ (float) ($m->running_value ?? 0) < 0 ? 'text-danger' : '' }}">
                                        {{ number_format((float) ($m->running_value ?? 0), 0, ',', '.') }}
                                    </td>
                                @endif
                            @endif

                            <td class="hide-sm small muted">{{ $m->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        @php
                            $colspan = 7;
                            if ($itemId) $colspan += 1;
                            if ($canViewCost ?? false) {
                                $colspan += ($itemId ? 2 : 1);
                            }
                        @endphp
                        <tr>
                            <td colspan="{{ $colspan }}" class="p-3 text-center muted">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="p-2" id="sc_pagination">
        @if ($mutations instanceof \Illuminate\Pagination\AbstractPaginator)
            {{ $mutations->links() }}
        @endif
    </div>
</div>
