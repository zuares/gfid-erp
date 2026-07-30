@php
    $hasPacking = $packingPriority->isNotEmpty();
    $hasSewing = $sewingPriority->isNotEmpty();
    $hasCutting = $cuttingPriority->isNotEmpty();
    $sewingRecommendations = collect($sewingRecommendations ?? []);
    $bestSewingOperator = data_get($sewingRecommendations, 'best');
    $operatorList = collect(data_get($sewingRecommendations, 'operators', []));
    $operatorSummary = data_get($sewingRecommendations, 'summary', []);
    $userRole = strtolower((string) (auth()->user()?->role ?? ''));
    $hideAiOnMobile = in_array($userRole, ['admin', 'operating'], true);
@endphp

@if ($packingPriority->isEmpty() && $sewingPriority->isEmpty() && $cuttingPriority->isEmpty())
    <div class="ii-empty">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: #cbd5e1; margin-bottom: 1rem;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <div>Semua permintaan RTS sudah terpenuhi. Tidak ada prioritas mendesak untuk Gudang Produksi.</div>
    </div>
@else
    @php
        $activeTab = '';
        if ($hasPacking) $activeTab = 'packing';
        elseif ($hasSewing) $activeTab = 'sewing';
        elseif ($hasCutting) $activeTab = 'cutting';
    @endphp

    @if($bestSewingOperator)
        <div class="card-main mb-3 p-3 {{ $hideAiOnMobile ? 'd-none d-md-block' : '' }}">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div style="min-width:0; flex:1 1 280px;">
                    <div class="text-muted-ii" style="font-size:.72rem; font-weight:700;">Saran operator jahit</div>
                    <div style="font-size:1rem; font-weight:800; color:#0f172a; line-height:1.35;">
                        {{ $bestSewingOperator['name'] ?? '-' }}
                    </div>
                    <div class="text-muted-ii" style="font-size:.72rem; line-height:1.5;">
                        Paling cocok buat pickup baru karena bebannya masih aman dan hasil 14 hari terakhir stabil.
                    </div>
                    @if(!empty($bestSewingOperator['active_items']))
                        <div class="text-muted-ii mt-1" style="font-size:.72rem;">
                            Lagi pegang:
                            {{ collect($bestSewingOperator['active_items'])->pluck('item_name')->implode(', ') ?: '-' }}
                        </div>
                    @endif
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <div class="px-3 py-2" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;min-width:110px;">
                        <div class="text-muted-ii" style="font-size:.68rem;">Skor</div>
                        <div style="font-size:1rem;font-weight:800;color:#0f172a;">{{ number_format((float) ($bestSewingOperator['score'] ?? 0), 1, ',', '.') }}</div>
                    </div>
                    <div class="px-3 py-2" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;min-width:110px;">
                        <div class="text-muted-ii" style="font-size:.68rem;">Beban aktif</div>
                        <div style="font-size:1rem;font-weight:800;color:#0f172a;">{{ number_format((float) ($bestSewingOperator['active_load'] ?? 0), 0, ',', '.') }} pcs</div>
                    </div>
                    <div class="px-3 py-2" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;min-width:110px;">
                        <div class="text-muted-ii" style="font-size:.68rem;">Kualitas</div>
                        <div style="font-size:1rem;font-weight:800;color:#0f172a;">{{ number_format((float) ($bestSewingOperator['quality_rate'] ?? 0), 1, ',', '.') }}%</div>
                    </div>
                </div>
            </div>
            @if($operatorList->isNotEmpty())
                <div class="d-flex flex-wrap gap-2 mt-3">
                    @foreach($operatorList as $op)
                        <span class="badge-status" style="background:#f8fafc;color:#334155;border-color:#e2e8f0;font-weight:700;">
                            {{ $op['name'] ?? '-' }} · {{ number_format((float) ($op['active_load'] ?? 0), 0, ',', '.') }} pcs
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <ul class="nav nav-pills mb-3 d-flex flex-nowrap overflow-auto" id="prd-subtabs" role="tablist" style="font-size: .85rem; background: #fff; padding: .25rem; border-radius: 8px; border: 1px solid rgba(148,163,184,.18); gap: .25rem; scrollbar-width: none;">
        @if($hasPacking)
        <li class="nav-item" role="presentation">
            <button class="nav-link text-nowrap {{ $activeTab === 'packing' ? 'active' : '' }}" id="tab-packing-tab" data-bs-toggle="pill" data-bs-target="#tab-packing" type="button" role="tab" style="padding: .4rem .75rem; border-radius: 6px; font-weight: 600;">
                <i class="bi bi-box-seam me-1"></i> Packing ({{ $packingPriority->count() }})
            </button>
        </li>
        @endif
        @if($hasSewing)
        <li class="nav-item" role="presentation">
            <button class="nav-link text-nowrap {{ $activeTab === 'sewing' ? 'active' : '' }}" id="tab-sewing-tab" data-bs-toggle="pill" data-bs-target="#tab-sewing" type="button" role="tab" style="padding: .4rem .75rem; border-radius: 6px; font-weight: 600;">
                <i class="bi bi-scissors me-1"></i> Jahit ({{ $sewingPriority->count() }})
            </button>
        </li>
        @endif
        @if($hasCutting)
        <li class="nav-item" role="presentation">
            <button class="nav-link text-nowrap {{ $activeTab === 'cutting' ? 'active' : '' }}" id="tab-cutting-tab" data-bs-toggle="pill" data-bs-target="#tab-cutting" type="button" role="tab" style="padding: .4rem .75rem; border-radius: 6px; font-weight: 600;">
                <i class="bi bi-rulers me-1"></i> Cutting ({{ $cuttingPriority->count() }})
            </button>
        </li>
        @endif
    </ul>

    <div class="tab-content" id="prd-subtabsContent">
        @if($hasPacking)
        <div class="tab-pane fade {{ $activeTab === 'packing' ? 'show active' : '' }}" id="tab-packing" role="tabpanel">
            <div style="font-size: .75rem; color: #94a3b8; margin-bottom: 1rem; line-height: 1.4;">
                <div class="d-none d-md-block mt-1">RTS sedang kehabisan barang-barang ini, dan WH-PRD memiliki stoknya. Mohon segera lakukan packing/transfer agar barang bisa kembali dijual di RTS.</div>
            </div>

            <div class="d-md-none d-flex flex-column gap-2 mb-3">
                @foreach ($packingPriority as $r)
                    <div class="card-main p-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div style="min-width:0;">
                                <div style="font-weight:800;color:#0f172a;line-height:1.35;">{{ $r->product }}</div>
                                <div class="text-muted-ii" style="font-size:.72rem;">RTS tinggal {{ $fmt($r->ready) }} pcs · PRD ada {{ $fmt($r->wh_prd) }} pcs</div>
                            </div>
                            <span class="badge-status" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;font-weight:700;">
                                Kirim {{ $fmt(min($r->rts_deficit, $r->wh_prd)) }} pcs
                            </span>
                        </div>
                        <div class="text-muted-ii mt-2" style="font-size:.7rem;">{{ $r->sku }}</div>
                    </div>
                @endforeach
            </div>

            <div class="card-main mb-4 d-none d-md-block">
                <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                    <table class="table table-hover align-middle table-list m-0 sortable-table">
                        <thead style="background: rgba(248, 250, 252, 0.8); position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th style="padding-left: 1.25rem; cursor: pointer;" class="sortable" data-sort="string">SKU & Produk <span class="sort-icon"></span></th>
                                <th class="text-end sortable d-none d-md-table-cell" style="cursor: pointer;" data-sort="float" title="Laju penjualan harian (ADS)">Jual/Hr <span class="sort-icon"></span></th>
                                <th class="text-end sortable d-none d-md-table-cell" style="cursor: pointer;" data-sort="int">Kekurangan RTS (Defisit) <span class="sort-icon"></span></th>
                                <th class="text-end sortable d-none d-md-table-cell" style="cursor: pointer;" data-sort="int">Stok PRD (Tersedia) <span class="sort-icon"></span></th>
                                <th class="text-end sortable" style="padding-right: 1.25rem; cursor: pointer;" data-sort="int">Saran Aksi <span class="sort-icon"></span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($packingPriority as $r)
                                <tr>
                                    <td style="padding-left: 1.25rem;">
                                        <span class="fw-semibold">{{ $r->sku }}</span>
                                        <div class="text-muted-ii" style="font-size: .7rem;">{{ $r->product }}</div>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="text-muted-ii" style="font-size: .65rem;">{{ $r->category }}</span>
                                        </div>
                                    </td>
                                    <td class="text-end text-muted d-none d-md-table-cell">{{ $fmt($r->ads, 1) }}</td>
                                    <td class="text-end fw-bold text-danger d-none d-md-table-cell">
                                        {{ $fmt($r->rts_deficit) }}
                                        <div class="text-muted-ii" style="font-size: .65rem; font-weight: normal;">RTS saat ini: {{ $fmt($r->ready) }}</div>
                                    </td>
                                    <td class="text-end fw-bold d-none d-md-table-cell" style="color: #059669;">{{ $fmt($r->wh_prd) }}</td>
                                    <td class="text-end" style="padding-right: 1.25rem;">
                                        <span class="badge-status" style="background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; font-weight: 600;">
                                            Kirim: {{ $fmt(min($r->rts_deficit, $r->wh_prd)) }} pcs
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if($hasSewing)
        <div class="tab-pane fade {{ $activeTab === 'sewing' ? 'show active' : '' }}" id="tab-sewing" role="tabpanel">
            <div style="font-size: .75rem; color: #94a3b8; margin-bottom: 1rem; line-height: 1.4;">
                <div class="d-none d-md-block mt-1">Baik RTS maupun PRD sudah kehabisan barang ini, namun ada stok yang sedang dalam proses jahit (WIP). Prioritaskan untuk mengambil/menyelesaikan jahitan ini.</div>
            </div>

            <div class="d-md-none d-flex flex-column gap-2 mb-3">
                @foreach ($sewingPriority as $r)
                    <div class="card-main p-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div style="min-width:0;">
                                <div style="font-weight:800;color:#0f172a;line-height:1.35;">{{ $r->product }}</div>
                                <div class="text-muted-ii" style="font-size:.72rem;">WIP {{ $fmt($r->wip) }} pcs · RTS+PRD {{ $fmt($r->ready + $r->wh_prd) }} pcs</div>
                                @if($bestSewingOperator)
                                    <div class="text-muted-ii mt-1" style="font-size:.7rem;">
                                        Kasih ke: {{ $bestSewingOperator['name'] ?? '-' }}
                                    </div>
                                @endif
                            </div>
                            <span class="badge-status" style="background:#fffbeb;color:#b45309;border-color:#fde68a;font-weight:700;">
                                Ambil jahit
                            </span>
                        </div>
                        <div class="text-muted-ii mt-2" style="font-size:.7rem;">{{ $r->sku }}</div>
                    </div>
                @endforeach
            </div>

            <div class="card-main mb-2 d-none d-md-block">
                <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                    <table class="table table-hover align-middle table-list m-0 sortable-table">
                        <thead style="background: rgba(248, 250, 252, 0.8); position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th style="padding-left: 1.25rem; cursor: pointer;" class="sortable" data-sort="string">SKU & Produk <span class="sort-icon"></span></th>
                                <th class="text-end sortable d-none d-md-table-cell" style="cursor: pointer;" data-sort="float" title="Laju penjualan harian (ADS)">Jual/Hr <span class="sort-icon"></span></th>
                                <th class="text-end sortable d-none d-md-table-cell" style="cursor: pointer;" data-sort="int">Stok Tersisa (RTS+PRD) <span class="sort-icon"></span></th>
                                <th class="text-end sortable d-none d-md-table-cell" style="cursor: pointer;" data-sort="int">Stok Sedang Jahit (WIP) <span class="sort-icon"></span></th>
                                <th class="text-end sortable" style="padding-right: 1.25rem; cursor: pointer;" data-sort="string">Saran Aksi <span class="sort-icon"></span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sewingPriority as $r)
                                <tr>
                                    <td style="padding-left: 1.25rem;">
                                        <span class="fw-semibold">{{ $r->sku }}</span>
                                        <div class="text-muted-ii" style="font-size: .7rem;">{{ $r->product }}</div>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="text-muted-ii" style="font-size: .65rem;">{{ $r->category }}</span>
                                        </div>
                                    </td>
                                    <td class="text-end text-muted d-none d-md-table-cell">{{ $fmt($r->ads, 1) }}</td>
                                    <td class="text-end fw-bold text-danger d-none d-md-table-cell">
                                        {{ $fmt($r->ready + $r->wh_prd) }}
                                    </td>
                                    <td class="text-end fw-bold d-none d-md-table-cell" style="color: #d97706;">{{ $fmt($r->wip) }}</td>
                                    <td class="text-end" style="padding-right: 1.25rem;">
                                        <span class="badge-status" style="background: #fffbeb; color: #b45309; border-color: #fde68a; font-weight: 600;">
                                            Ambil Jahit Segera
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if($hasCutting)
        <div class="tab-pane fade {{ $activeTab === 'cutting' ? 'show active' : '' }}" id="tab-cutting" role="tabpanel">
            <div style="font-size: .75rem; color: #94a3b8; margin-bottom: 1rem; line-height: 1.4;">
                <div class="d-none d-md-block mt-1">Daftar item yang total stok globalnya (RTS + Gudang + WIP Jahit) berada di bawah ambang aman untuk menutupi penjualan 1 bulan (30 hari) ke depan. Disarankan segera jadwalkan proses pemotongan kain. Saran ini dikelompokkan berdasarkan warna agar memudahkan pemotongan gulungan kain.</div>
            </div>

            <div class="d-md-none d-flex flex-column gap-2 mb-3">
                @foreach($cuttingPriority as $r)
                    <div class="card-main p-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div style="min-width:0;">
                                <div style="font-weight:800;color:#0f172a;line-height:1.35;">{{ $r->product }}</div>
                                <div class="text-muted-ii" style="font-size:.72rem;">Total stok {{ $fmt($r->ready + $r->wh_prd + $r->wip) }} · Target 30 hari {{ $fmt($r->target_30d) }}</div>
                            </div>
                            <a href="{{ route('production.cutting_jobs.create', ['item_id' => $r->item_id]) }}" class="btn btn-sm" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-weight:600;font-size:.75rem;border-radius:7px;padding:.3rem .6rem;white-space:nowrap;">
                                Potong {{ $fmt($r->saran_potong) }}
                            </a>
                        </div>
                        <div class="text-muted-ii mt-2" style="font-size:.7rem;">{{ $r->sku }}</div>
                    </div>
                @endforeach
            </div>

            @foreach($cuttingPriorityGrouped as $color => $group)
                @php
                    $items = $group['items'];
                    $materials = $group['materials'];
                @endphp
                <div class="card-main mb-3 d-none d-md-block">
                    <div class="px-3 py-2 d-flex justify-content-between align-items-center" style="background: rgba(248, 250, 252, 0.8); border-bottom: 1px solid #e2e8f0; border-radius: 8px 8px 0 0;">
                        <span style="font-weight: 700; color: #334155;">
                            <i class="bi bi-palette me-1" style="color: #64748b;"></i> Warna: {{ $color }}
                        </span>
                        <span class="badge-status" style="background: #f1f5f9; color: #475569; font-weight: 600; font-size: .7rem;">
                            {{ $items->count() }} Item
                        </span>
                    </div>
                    
                    @if($materials->isNotEmpty())
                    <div class="px-3 py-2" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                        <div style="font-size: .7rem; font-weight: 600; color: #64748b; margin-bottom: .4rem;">Estimasi Kebutuhan Bahan Utama:</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($materials as $mat)
                                <span class="badge-status" style="background: #fff; color: #475569; border: 1px solid #cbd5e1; padding: .25rem .5rem;">
                                    {{ $mat['name'] }}: <strong style="color: #0f172a;">{{ $fmt($mat['req_qty'], 1) }} {{ $mat['uom'] }}</strong>
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="table-responsive" style="max-height: 40vh; overflow-y: auto;">
                        <table class="table table-hover align-middle table-list m-0 sortable-table">
                            <thead style="background: #fff; position: sticky; top: 0; z-index: 10;">
                                <tr>
                                    <th style="padding-left: 1.25rem; cursor: pointer;" class="sortable" data-sort="string">SKU & Produk <span class="sort-icon"></span></th>
                                    <th class="text-end sortable d-none d-md-table-cell" style="cursor: pointer;" data-sort="float" title="Laju penjualan harian (ADS)">Jual/Hr <span class="sort-icon"></span></th>
                                    <th class="text-end sortable d-none d-md-table-cell" style="cursor: pointer;" data-sort="int">Total Stok <span class="sort-icon"></span></th>
                                    <th class="text-end sortable" style="padding-right: 1.25rem; cursor: pointer;" data-sort="int">Saran Potong <span class="sort-icon"></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $r)
                                    <tr>
                                        <td style="padding-left: 1.25rem; padding-top: 1rem; padding-bottom: 1rem;">
                                            <div class="d-flex justify-content-between align-items-start mb-1 d-md-none">
                                                <span class="fw-semibold">{{ $r->sku }}</span>
                                                <span class="text-muted-ii" style="font-size: .65rem;">Jual/Hr: {{ $fmt($r->ads, 1) }}</span>
                                            </div>
                                            <span class="fw-semibold d-none d-md-block">{{ $r->sku }}</span>
                                            <div class="text-muted-ii" style="font-size: .7rem;">{{ $r->product }}</div>
                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <span class="text-muted-ii" style="font-size: .65rem;">{{ $r->category }}</span>
                                            </div>
                                            @if(!empty($r->bom_materials))
                                                <div class="mt-2" style="font-size: .65rem;">
                                                    <div class="fw-bold text-muted-ii mb-1">Kebutuhan per Item:</div>
                                                    <div class="d-flex flex-column gap-1">
                                                        @foreach($r->bom_materials as $mat)
                                                            <div class="d-flex align-items-center text-muted">
                                                                <i class="bi bi-dash me-1"></i>
                                                                <span>{{ $mat['name'] }}: <strong style="color: #475569;">{{ $fmt($mat['req_qty'], 2) }} {{ $mat['uom'] }}</strong></span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-end text-muted d-none d-md-table-cell" style="vertical-align: top; padding-top: 1rem;">{{ $fmt($r->ads, 1) }}</td>
                                        <td class="text-end text-muted d-none d-md-table-cell" style="vertical-align: top; padding-top: 1rem;">
                                            {{ $fmt($r->ready + $r->wh_prd + $r->wip) }}
                                            <div class="text-muted-ii" style="font-size: .65rem;">Target: {{ $fmt($r->target_30d) }}</div>
                                        </td>
                                        <td class="text-end" style="padding-right: 1.25rem; vertical-align: top; padding-top: 1rem;">
                                            <a href="{{ route('production.cutting_jobs.create', ['item_id' => $r->item_id]) }}" class="btn btn-sm" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-weight: 600; font-size: .75rem; border-radius: 7px; padding: .3rem .6rem; text-wrap: nowrap;" title="Buat Cutting Job">
                                                Potong {{ $fmt($r->saran_potong) }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </div>
@endif
