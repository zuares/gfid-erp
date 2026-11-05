@php
    $itemId = $filters['item_id'] ?? null;
    $rows = $mutations instanceof \Illuminate\Pagination\AbstractPaginator ? $mutations->getCollection() : $mutations;
    $rowCount = $mutations instanceof \Illuminate\Pagination\AbstractPaginator ? $mutations->total() : $rows->count();
    $fmtMove = fn ($value) => abs((float) $value) < 0.000001 ? '-' : number_format((float) $value, 2, ',', '.');
    $fmtBalance = fn ($value) => abs((float) $value) < 0.000001 ? '0' : number_format((float) $value, 2, ',', '.');
@endphp

<section class="card-main sc-table-shell" id="sc_table_card" aria-labelledby="sc-table-title">
    <div class="sc-table-toolbar">
        <div>
            <div class="sc-table-title" id="sc-table-title">Riwayat mutasi</div>
            <div class="sc-table-caption">{{ number_format($rowCount, 0, ',', '.') }} transaksi pada filter aktif</div>
        </div>
        <span class="sc-table-scroll-hint">Geser tabel untuk melihat detail</span>
    </div>

    <div class="table-responsive sc-table-desktop">
        <table class="table table-list sc-stock-table mb-0">
            <thead>
                <tr>
                    <th style="width:120px">Tanggal</th>
                    @if (!$itemId)
                        <th style="width:170px">Item</th>
                    @endif
                    <th style="width:145px">Gudang</th>
                    <th style="width:100px">LOT</th>
                    <th style="width:200px">Sumber</th>
                    <th style="width:150px">Oleh</th>
                    <th class="text-end" style="width:120px">IN</th>
                    <th class="text-end" style="width:120px">OUT</th>
                    <th class="text-end" style="width:135px">Stok akhir</th>
                </tr>
            </thead>

            <tbody>
                @forelse($rows as $m)
                    @php
                        $qtyAbs = abs((float) $m->qty_change);
                        $isIn = ($m->direction ?? null) === 'in';
                        $isOut = ($m->direction ?? null) === 'out';
                        $in = $isIn ? $qtyAbs : 0;
                        $out = $isOut ? $qtyAbs : 0;
                        $wh = $m->warehouse?->code ?? '-';
                        $lot = $m->lot?->code ?? '-';
                    @endphp

                    <tr>
                        <td><x-gf.datecell :date="$m->date" :time="$m->created_at" /></td>

                        @if (!$itemId)
                            <td class="mono sc-item-code">{{ $m->item->code ?? '-' }}</td>
                        @endif

                        <td><span class="sub-badge mono">{{ $wh }}</span></td>
                        <td class="mono">{{ $lot }}</td>

                        <td>
                            <span class="badge mono sc-source-badge">{{ $m->source_label ?? ($availableSourceTypes[$m->source_type] ?? $m->source_type) }}</span>
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

                        <td><div class="small fw-semibold">{{ $m->created_by_name ?? 'Sistem' }}</div></td>

                        <td class="text-end"><span class="mono qty-num qty-in-num">{{ $fmtMove($in) }}</span></td>
                        <td class="text-end"><span class="mono qty-num qty-out-num">{{ $fmtMove($out) }}</span></td>
                        <td class="text-end mono fw-semibold {{ (float) ($m->running_qty ?? 0) < 0 ? 'text-danger' : '' }}">
                            <span class="qty-balance {{ abs((float) ($m->running_qty ?? 0)) < 0.000001 ? 'qty-balance-zero' : '' }}">{{ $fmtBalance($m->running_qty ?? 0) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $itemId ? 8 : 9 }}">
                            <div class="sc-empty-state">
                                <div class="sc-empty-state__icon"><i class="bi bi-inboxes"></i></div>
                                <div class="sc-empty-state__title">Belum ada mutasi</div>
                                <div class="sc-empty-state__desc">Coba ubah periode, gudang, atau filter sumber.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="sc-mobile-list" aria-label="Daftar mutasi stok di perangkat mobile">
        @forelse($rows as $m)
            @php
                $qtyAbs = abs((float) $m->qty_change);
                $isIn = ($m->direction ?? null) === 'in';
                $isOut = ($m->direction ?? null) === 'out';
                $in = $isIn ? $qtyAbs : 0;
                $out = $isOut ? $qtyAbs : 0;
                $wh = $m->warehouse?->code ?? '-';
                $lot = $m->lot?->code ?? '-';
            @endphp

            <article class="sc-mutation-card">
                <div class="sc-mutation-card__top">
                    <x-gf.datecell :date="$m->date" :time="$m->created_at" />
                    <div class="sc-mutation-card__source">
                        <span class="badge mono sc-source-badge">{{ $m->source_label ?? ($availableSourceTypes[$m->source_type] ?? $m->source_type) }}</span>
                        @if(!empty($m->source_detail))
                            <div class="sc-mutation-card__detail">{{ $m->source_detail }}</div>
                        @endif
                    </div>
                </div>

                <div class="sc-mutation-card__meta">
                    @if (!$itemId)
                        <div class="sc-meta-pair">
                            <span class="sc-meta-pair__label">Item</span>
                            <span class="sc-meta-pair__value mono">{{ $m->item->code ?? '-' }}</span>
                        </div>
                    @endif
                    <div class="sc-meta-pair">
                        <span class="sc-meta-pair__label">Gudang</span>
                        <span class="sc-meta-pair__value mono">{{ $wh }}</span>
                    </div>
                    <div class="sc-meta-pair">
                        <span class="sc-meta-pair__label">LOT</span>
                        <span class="sc-meta-pair__value mono">{{ $lot }}</span>
                    </div>
                </div>

                <div class="sc-mutation-card__figures">
                    <div class="sc-figure sc-figure--in">
                        <span class="sc-figure__label">Masuk</span>
                        <span class="sc-figure__value mono">{{ $fmtMove($in) }}</span>
                    </div>
                    <div class="sc-figure sc-figure--out">
                        <span class="sc-figure__label">Keluar</span>
                        <span class="sc-figure__value mono">{{ $fmtMove($out) }}</span>
                    </div>
                    <div class="sc-figure sc-figure--balance">
                        <span class="sc-figure__label">Saldo</span>
                        <span class="sc-figure__value mono">{{ $fmtBalance($m->running_qty ?? 0) }}</span>
                    </div>
                </div>

                <div class="sc-mutation-card__foot">
                    <span><i class="bi bi-person me-1"></i>{{ $m->created_by_name ?? 'Sistem' }}</span>
                    @if($m->source_id)<span>Dokumen #{{ $m->source_id }}</span>@endif
                </div>
                @if (($m->source_type ?? '') === 'adjustment' && !empty($m->adjustment_reason))
                    <div class="sc-mutation-card__detail">Alasan: {{ $m->adjustment_reason }}</div>
                @endif
            </article>
        @empty
            <div class="sc-empty-state">
                <div class="sc-empty-state__icon"><i class="bi bi-inboxes"></i></div>
                <div class="sc-empty-state__title">Belum ada mutasi</div>
                <div class="sc-empty-state__desc">Coba ubah periode, gudang, atau filter sumber.</div>
            </div>
        @endforelse
    </div>
</section>

<div class="p-2" id="sc_pagination">
    @if ($mutations instanceof \Illuminate\Pagination\AbstractPaginator)
        {{ $mutations->links() }}
    @endif
</div>
