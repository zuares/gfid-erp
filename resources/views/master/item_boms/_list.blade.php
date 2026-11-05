@if($boms->count() === 0)
    <div class="bom-empty">
        <div class="bom-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></div>
        <h2 class="bom-empty-title">{{ $hasFilters ? 'BOM tidak ditemukan' : 'Belum ada BOM' }}</h2>
        <p class="bom-empty-copy">{{ $hasFilters ? 'Coba ubah kata kunci atau filter, atau reset untuk melihat semua BOM.' : 'Buat BOM pertama untuk mulai mengatur standar komponen produksi.' }}</p>
        @if($hasFilters)
            <a href="{{ route('master.item_boms.index') }}" class="bom-btn bom-btn-ghost mt-3">Reset filter</a>
        @else
            <a href="{{ route('master.item_boms.create') }}" class="bom-btn bom-btn-primary mt-3"><i class="bi bi-plus-lg" aria-hidden="true"></i>Buat BOM</a>
        @endif
    </div>
@else
    <div class="bom-table-wrap">
        <table class="bom-table">
            <caption class="visually-hidden">Daftar BOM SKU</caption>
            <thead>
                <tr><th class="bom-index">No.</th><th>SKU / Item</th><th>Struktur BOM</th><th>COGS total / pcs</th><th>Status</th><th class="text-end">Aksi</th></tr>
            </thead>
            <tbody>
                @foreach($boms as $i => $b)
                    @php
                        $ub = $bomUsageBadges[$b->id] ?? null;
                        $cogs = $bomCogs[$b->id] ?? null;
                    @endphp
                    <tr>
                        <td class="bom-index">{{ ($boms->currentPage() - 1) * $boms->perPage() + $i + 1 }}</td>
                        <td>
                            <a href="{{ route('master.item_boms.show', $b) }}" class="bom-item-code">{{ $b->item?->code ?? '—' }}</a>
                            <div class="bom-item-name">{{ $b->item?->name ?? 'Item tidak ditemukan' }}</div>
                            @if($b->name)<div class="bom-name">{{ $b->name }}</div>@endif
                        </td>
                        <td>
                            <div class="bom-structure"><strong>{{ (int) $b->main_material_lines_count }}</strong> utama <span class="text-muted">·</span> <strong>{{ (int) $b->sewing_supply_lines_count }}</strong> jahit <span class="text-muted">·</span> <strong>{{ (int) $b->packing_supply_lines_count }}</strong> packing</div>
                            <div class="bom-structure-meta">{{ (int) $b->lines_count }} komponen total</div>
                            @if($ub)<div class="bom-actual">Aktual <span class="bom-actual-value">{{ number_format($ub['kg_per_pcs'], 3, ',', '') }}/pcs</span> · {{ $ub['job_code'] }} @if($ub['status'] === 'over')<span class="bom-usage-over">· melebihi standar</span>@elseif($ub['status'] === 'under')<span class="bom-usage-under">· lebih hemat</span>@elseif($ub['status'] === 'ok')<span>· sesuai standar</span>@endif</div>@endif
                        </td>
                        <td>
                            <div class="bom-cogs-value">Rp {{ number_format((float) ($cogs['total'] ?? 0), 0, ',', '.') }}</div>
                            <div class="bom-cogs-meta">Material Rp {{ number_format((float) ($cogs['material_total'] ?? 0), 0, ',', '.') }} · Upah Rp {{ number_format((float) ($cogs['production_wage_total'] ?? 0), 0, ',', '.') }}</div>
                        </td>
                        <td><span class="bom-status {{ $b->active ? 'bom-status-active' : 'bom-status-inactive' }}">{{ $b->active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td>
                            <div class="bom-actions">
                                <a href="{{ route('master.item_boms.show', $b) }}" class="bom-btn bom-btn-ghost"><i class="bi bi-calculator" aria-hidden="true"></i><span>HPP</span></a>
                                <a href="{{ route('master.item_boms.edit', $b) }}" class="bom-btn bom-btn-ghost"><i class="bi bi-pencil" aria-hidden="true"></i><span>Edit</span></a>
                                <form method="post" action="{{ route('master.item_boms.destroy', $b) }}" data-gf-confirm data-gf-confirm-title="Hapus BOM?" data-gf-confirm-text="Semua komponen pada BOM ini ikut terhapus." data-gf-confirm-icon="warning" data-gf-confirm-ok="Ya, hapus" data-gf-confirm-cancel="Batal">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bom-btn bom-btn-danger" aria-label="Hapus BOM {{ $b->item?->code ?? '' }}" title="Hapus BOM"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bom-mobile-list">
        @foreach($boms as $b)
            @php
                $ub = $bomUsageBadges[$b->id] ?? null;
                $cogs = $bomCogs[$b->id] ?? null;
            @endphp
            <article class="bom-mobile-card">
                <div class="bom-mobile-head">
                    <div>
                        <a href="{{ route('master.item_boms.show', $b) }}" class="bom-item-code">{{ $b->item?->code ?? '—' }}</a>
                        <div class="bom-item-name">{{ $b->item?->name ?? 'Item tidak ditemukan' }}</div>
                        @if($b->name)<div class="bom-name">{{ $b->name }}</div>@endif
                    </div>
                    <span class="bom-status {{ $b->active ? 'bom-status-active' : 'bom-status-inactive' }}">{{ $b->active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                <div class="bom-mobile-structure"><span><strong>{{ (int) $b->main_material_lines_count }}</strong> utama</span><span><strong>{{ (int) $b->sewing_supply_lines_count }}</strong> jahit</span><span><strong>{{ (int) $b->packing_supply_lines_count }}</strong> packing</span><span><strong>{{ (int) $b->lines_count }}</strong> total</span></div>
                <div class="bom-mobile-cogs"><span>COGS total / pcs</span><strong>Rp {{ number_format((float) ($cogs['total'] ?? 0), 0, ',', '.') }}</strong><small>Material Rp {{ number_format((float) ($cogs['material_total'] ?? 0), 0, ',', '.') }} · Upah Rp {{ number_format((float) ($cogs['production_wage_total'] ?? 0), 0, ',', '.') }}</small></div>
                @if($ub)<div class="bom-actual">Aktual <span class="bom-actual-value">{{ number_format($ub['kg_per_pcs'], 3, ',', '') }}/pcs</span> · {{ $ub['job_code'] }} @if($ub['status'] === 'over')<span class="bom-usage-over">· melebihi standar</span>@elseif($ub['status'] === 'under')<span class="bom-usage-under">· lebih hemat</span>@elseif($ub['status'] === 'ok')<span>· sesuai standar</span>@endif</div>@endif
                <div class="bom-mobile-actions">
                    <a href="{{ route('master.item_boms.show', $b) }}" class="bom-btn bom-btn-ghost"><i class="bi bi-calculator" aria-hidden="true"></i>Lihat HPP</a>
                    <a href="{{ route('master.item_boms.edit', $b) }}" class="bom-btn bom-btn-primary"><i class="bi bi-pencil" aria-hidden="true"></i>Edit BOM</a>
                    <form method="post" action="{{ route('master.item_boms.destroy', $b) }}" data-gf-confirm data-gf-confirm-title="Hapus BOM?" data-gf-confirm-text="Semua komponen pada BOM ini ikut terhapus." data-gf-confirm-icon="warning" data-gf-confirm-ok="Ya, hapus" data-gf-confirm-cancel="Batal">
                        @csrf @method('DELETE')
                        <button type="submit" class="bom-btn bom-btn-danger" aria-label="Hapus BOM {{ $b->item?->code ?? '' }}" title="Hapus BOM"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                    </form>
                </div>
            </article>
        @endforeach
    </div>

    <footer class="bom-footer"><span data-bom-footer-label>Menampilkan {{ $boms->firstItem() }}–{{ $boms->lastItem() }} dari {{ number_format($boms->total(), 0, ',', '.') }} BOM</span><nav data-bom-pagination aria-label="Navigasi halaman BOM">{{ $boms->links() }}</nav></footer>
@endif
