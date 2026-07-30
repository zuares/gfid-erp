@php
    $itemId = $filters['item_id'] ?? null;
    $rows = $mutations instanceof \Illuminate\Pagination\AbstractPaginator ? $mutations->getCollection() : $mutations;
    $fmtMove = fn ($value) => abs((float) $value) < 0.000001 ? '-' : number_format((float) $value, 2, ',', '.');
    $fmtBalance = fn ($value) => abs((float) $value) < 0.000001 ? '0' : number_format((float) $value, 2, ',', '.');
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
                            <th style="width:150px">Oleh</th>
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
                            <th style="width:150px">Oleh</th>
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
                                @if (($m->source_type ?? '') === 'adjustment' && !empty($m->adjustment_reason))
                                    <div class="small muted mt-1">Alasan: {{ $m->adjustment_reason }}</div>
                                @endif
                                @if($m->source_id)
                                    <div class="small muted mt-1">Dokumen #{{ $m->source_id }}</div>
                                @endif
                            </td>

                            <td>
                                <div class="small fw-semibold">{{ $m->created_by_name ?? 'Sistem' }}</div>
                            </td>

                            <td class="text-end">
                                <div class="qty-cell mono">
                                    <span class="qty-num qty-in-num">{{ $fmtMove($in) }}</span>
                                </div>
                            </td>

                            <td class="text-end">
                                <div class="qty-cell mono">
                                    <span class="qty-num qty-out-num">{{ $fmtMove($out) }}</span>
                                </div>
                            </td>

                            <td class="text-end mono fw-semibold {{ (float) ($m->running_qty ?? 0) < 0 ? 'text-danger' : '' }}">
                                <span class="qty-balance {{ abs((float) ($m->running_qty ?? 0)) < 0.000001 ? 'qty-balance-zero' : '' }}">
                                    {{ $fmtBalance($m->running_qty ?? 0) }}
                                </span>
                            </td>

                            @if ($itemId)
                                {{-- saldo item sudah tampil di kolom Stok Akhir --}}
                            @endif
                        </tr>
                    @empty
                        @php
                            $colspan = $itemId ? 8 : 9;
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
