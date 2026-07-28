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
                            <th class="text-end" style="width:140px">Stok Akhir</th>
                        </tr>
                    @else
                        <tr>
                            <th style="width:105px">Tgl</th>
                            <th style="width:180px">Gudang</th>
                            <th style="width:110px">LOT</th>
                            <th style="width:160px">Sumber</th>
                            <th class="text-center" style="width:130px">IN</th>
                            <th class="text-center" style="width:130px">OUT</th>
                            <th class="text-end" style="width:140px">Stok Akhir</th>
                        </tr>
                    @endif
                </thead>

                <tbody>
                    @forelse($rows as $m)
                        @php
                            $qtyAbs = abs((float) $m->qty_change);
                            $isIn = ($m->direction ?? null) === 'in';
                            $isOut = ($m->direction ?? null) === 'out';
                            $in = $isIn ? $qtyAbs : 0;
                            $out = $isOut ? $qtyAbs : 0;

                            $wh = $m->warehouse ? $m->warehouse->code : '-';
                            $lot = $m->lot?->code ?? '-';

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
                                <span class="badge bg-light text-dark mono">{{ $m->source_label ?? ($availableSourceTypes[$m->source_type] ?? $m->source_type) }}</span>
                                @if(!empty($m->source_detail))
                                    <div class="small muted mt-1">{{ $m->source_detail }}</div>
                                @endif
                                @if($m->source_id)
                                    <div class="small muted mt-1">Dokumen #{{ $m->source_id }}</div>
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

                            <td class="text-end mono fw-semibold {{ (float) ($m->running_qty ?? 0) < 0 ? 'text-danger' : '' }}">
                                {{ number_format((float) ($m->running_qty ?? 0), 2, ',', '.') }}
                            </td>

                            @if ($itemId)
                                {{-- saldo item sudah tampil di kolom Stok Akhir --}}
                            @endif
                        </tr>
                    @empty
                        @php
                            $colspan = $itemId ? 7 : 8;
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
