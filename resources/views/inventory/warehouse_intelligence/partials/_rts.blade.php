@if ($rows->isEmpty())
    <div class="ii-empty">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: #cbd5e1; margin-bottom: 1rem;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        <div>Semua stok di WH-RTS dalam kondisi aman! Tidak ada item mendesak.</div>
    </div>
@else
    @php
        $activePrDraft = $rows->firstWhere('pr_draft_id');
        $activePoDraft = $rows->firstWhere('po_draft_id');
    @endphp

    <div class="card-main mb-3 p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div style="min-width:0; flex:1 1 340px;">
                <div style="font-size:.82rem;font-weight:800;color:#111827;">Filter Operasional</div>
                <div class="text-muted-ii" style="font-size:.72rem; line-height:1.45;">
                    Pilih biar daftar lebih fokus. Di desktop, kamu bisa langsung lihat item yang butuh ditarik, dibeli, atau dijahit.
                    Kalau PR draft sudah ada, lanjut pilih supplier untuk bikin PO draft.
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-start justify-content-md-end">
                <button class="btn-filter-rts active sd-btn sd-primary" data-filter="all"><i class="bi bi-grid"></i> Semua item</button>
                <button class="btn-filter-rts sd-btn" data-filter="kritis" style="color: #d97706;"><i class="bi bi-exclamation-triangle"></i> Stok tipis</button>
                <button class="btn-filter-rts sd-btn" data-filter="tarik_prd" style="color: #059669;"><i class="bi bi-box-seam"></i> Siap dipindah</button>
                <button class="btn-filter-rts sd-btn" data-filter="beli_jadi" style="color: #dc2626;"><i class="bi bi-cart-plus"></i> Perlu beli</button>
            </div>
            
            <div class="ms-auto d-flex gap-2" id="bulk-action-container" style="display: none;">
                <button class="sd-btn sd-primary" id="btn-bulk-minta" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
                    Pindah dari PRD Masal (<span id="bulk-count">0</span>)
                </button>
                <button class="sd-btn sd-primary" id="btn-bulk-pr" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    Buat PR Masal (<span id="bulk-pr-count">0</span>)
                </button>
            </div>
            @if($activePoDraft)
                <a href="{{ route('purchasing.purchase_orders.show', data_get($activePoDraft, 'po_draft_id')) }}" class="sd-btn rts-scope-beli" style="background:#ecfdf5;color:#166534;border-color:rgba(34,197,94,.25);font-weight:800;">
                    Lihat PO draft
                </a>
            @elseif($activePrDraft)
                <a href="{{ route('purchasing.purchase_requests.allocate_suppliers', data_get($activePrDraft, 'pr_draft_id')) }}" class="sd-btn rts-scope-beli" style="background:#f8fafc;color:#7c3aed;border-color:rgba(124,58,237,.25);font-weight:800;">
                    Pilih supplier
                </a>
            @endif
        </div>
    </div>

    <div class="card-main">
        <div class="table-responsive" style="max-height: 72vh; overflow-y: auto;">
            <table class="table table-hover table-sm align-middle table-list m-0 sortable-table" style="min-width: 960px;">
                <thead style="background: rgba(248, 250, 252, 0.95); position: sticky; top: 0; z-index: 10; font-size: .8rem;">
                    <tr>
                        <th style="padding-left: 1rem; cursor: pointer;" class="sortable" data-sort="string">SKU & Produk <span class="sort-icon"></span></th>
                        <th class="text-end sortable" style="cursor: pointer;" data-sort="float" title="Rata-rata penjualan harian">Jual / hari <span class="sort-icon"></span></th>
                        <th class="text-start" style="min-width: 290px;" title="Ringkasan stok dan batas aman">
                            Ringkasan stok
                        </th>
                        <th class="text-start" style="min-width: 240px;" title="Status tindakan dan draft">
                            Tindak lanjut
                        </th>
                    </tr>
                </thead>
                <tbody id="rts-tbody">
                    @foreach ($rows as $r)
                        @php
                            $effectiveMin = $r->rts_min_effective ?? max(5, ceil($r->ads * 7));
                            $effectiveMax = $r->rts_max_effective ?? ceil($r->ads * 14);
                            $isKritis = max(0, $r->ready - $r->ready_allocated) <= $effectiveMin;
                            $productionGroup = (string) ($r->production_group ?? '');
                            $productionGroupLabel = (string) ($r->production_group_label ?? '');
                            $productionBadgeStyle = match ($productionGroup) {
                                'in_house' => ['bg' => '#ecfdf5', 'border' => '#bbf7d0', 'color' => '#047857'],
                                'buy' => ['bg' => '#fff7ed', 'border' => '#fed7aa', 'color' => '#c2410c'],
                                'outsource' => ['bg' => '#f8fafc', 'border' => '#e2e8f0', 'color' => '#475569'],
                                default => ['bg' => '#f8fafc', 'border' => '#e2e8f0', 'color' => '#475569'],
                            };
                        @endphp
                        <tr class="rts-row" 
                            data-kritis="{{ $isKritis ? '1' : '0' }}" 
                            data-prd="{{ $r->wh_prd > 0 ? '1' : '0' }}" 
                            data-minta="{{ $r->minta_prd }}"
                            data-buy="{{ $productionGroup === 'buy' ? '1' : '0' }}"
                            data-source-group="{{ $productionGroup }}">
                            <td style="padding-left: 1rem;">
                                <span class="fw-semibold">{{ $r->sku }}</span>
                                <div class="text-muted-ii" style="font-size: .7rem;">{{ $r->product }}</div>
                                <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                    <span class="text-muted-ii" style="font-size: .65rem;">{{ $r->category }}</span>
                                    @if($productionGroupLabel !== '')
                                        <span class="badge-status" style="background:{{ $productionBadgeStyle['bg'] }};color:{{ $productionBadgeStyle['color'] }};border-color:{{ $productionBadgeStyle['border'] }};font-weight:700;">
                                            {{ $productionGroupLabel }}
                                        </span>
                                    @endif
                                    <button class="btn btn-sm btn-link text-muted p-0 btn-edit-limit"
                                        data-id="{{ $r->item_id }}" 
                                        data-sku="{{ $r->sku }}"
                                        data-min="{{ ($r->rts_min_display ?? 0) > 0 ? $r->rts_min_display : '' }}"
                                        data-max="{{ ($r->rts_max_display ?? 0) > 0 ? $r->rts_max_display : '' }}"
                                        data-def-min="{{ $effectiveMin }}"
                                        data-def-max="{{ $effectiveMax }}"
                                        title="Edit Limit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                </div>
                            </td>
                            <td class="text-end text-muted">{{ $fmt($r->ads, 1) }}</td>
                            <td>
                                <div class="rts-stock-grid">
                                    <div class="rts-stock-metric">
                                        <div class="rts-stock-label">{{ auth()->user()?->role === 'admin' || auth()->user()?->isOwner() ? 'Stok RTS' : 'Stok di RTS' }}</div>
                                        <div class="rts-stock-value {{ $isKritis ? 'text-danger' : 'text-warning' }}">{{ $fmt(max(0, $r->ready - $r->ready_allocated)) }}</div>
                                        <div class="rts-stock-note">fisik {{ $fmt($r->ready) }}</div>
                                    </div>
                                    <div class="rts-stock-metric">
                                        <div class="rts-stock-label">Cukup untuk</div>
                                        <div class="rts-stock-value">{{ $fmt($r->rts_cover, 1) }} hari</div>
                                        <div class="rts-stock-note">perkiraan stok aman</div>
                                    </div>
                                    <div class="rts-stock-metric">
                                        <div class="rts-stock-label">Batas aman</div>
                                        <div class="rts-stock-value">
                                            {{ ($r->rts_min_display ?? 0) > 0 || ($r->rts_max_display ?? 0) > 0 ? (($r->rts_min_display ?? 0) > 0 ? $r->rts_min_display : $effectiveMin) . ' - ' . (($r->rts_max_display ?? 0) > 0 ? $r->rts_max_display : $effectiveMax) : 'Otomatis' }}
                                        </div>
                                        <div class="rts-stock-note">limit display</div>
                                    </div>
                                    <div class="rts-stock-metric">
                                        <div class="rts-stock-label">Stok produksi</div>
                                        <div class="rts-stock-value">{{ $r->wh_prd > 0 ? $fmt($r->wh_prd) : '0' }}</div>
                                        <div class="rts-stock-note">siap dipindah</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding-right: 1rem;">
                                <div class="rts-action-stack" style="font-size: .75rem;">
                                    <div class="rts-action-line">
                                        @if($r->pr_draft_id)
                                            <a href="{{ route('purchasing.purchase_requests.allocate_suppliers', $r->pr_draft_id) }}" class="rts-draft-note rts-scope-beli" title="Pilih supplier">
                                                PR draft siap pilih supplier
                                            </a>
                                        @elseif($isKritis && $r->wh_prd > 0)
                                            <span class="rts-action-chip rts-scope-tarik" style="color: #059669; background: #ecfdf5; border-color: #bbf7d0;">
                                                <i class="bi bi-box-seam me-1"></i>Siap dipindah
                                            </span>
                                        @elseif($isKritis && $r->wh_prd == 0 && $r->production_source !== 'buy')
                                            <span class="rts-action-chip" style="color: #dc2626; background: #fef2f2; border-color: #fecaca;">
                                                <i class="bi bi-scissors me-1"></i>Perlu jahit
                                            </span>
                                        @elseif(!$isKritis)
                                            <span class="rts-action-chip" style="color: #475569; background: #f8fafc; border-color: #e2e8f0;">
                                                <i class="bi bi-check-circle me-1"></i>Aman
                                            </span>
                                        @elseif($r->wh_prd == 0 && $r->production_source === 'buy')
                                            <span class="rts-action-chip" style="color: #b45309; background: #fffbeb; border-color: #fde68a;">
                                                {{ $r->buy_pr_qty_label ?? $fmt(max(1, $r->buy_pr_qty ?? $r->rts_deficit)) }} · 30 hari
                                            </span>
                                        @endif
                                    </div>

                                    <div class="rts-action-line">
                                        @if($r->po_draft_id)
                                            <a href="{{ route('purchasing.purchase_orders.show', $r->po_draft_id) }}" class="rts-draft-note rts-scope-beli" title="Lihat PO draft">
                                                PO draft siap{{ $r->po_draft_code ? ' · ' . $r->po_draft_code : '' }}
                                            </a>
                                        @elseif($r->draft_id)
                                            <a href="{{ route('rts.stock-requests.show', $r->draft_id) }}" class="btn btn-sm rts-action-btn rts-scope-tarik" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd;" title="Lihat draft">
                                                Lihat draft
                                            </a>
                                        @elseif($r->wh_prd > 0 && $r->minta_prd > 0)
                                            <button type="button" class="btn btn-sm btn-minta-stok rts-action-btn rts-scope-tarik" data-item="{{ $r->item_id }}" data-qty="{{ $r->minta_prd }}" style="background: #10b981; color: #fff; border: 1px solid #059669;" title="Tarik Stok dari PRD">
                                                Pindah PRD ({{ $fmt($r->minta_prd) }})
                                            </button>
                                        @elseif($r->pr_draft_id)
                                            <a href="{{ route('purchasing.purchase_requests.allocate_suppliers', $r->pr_draft_id) }}" class="btn btn-sm rts-action-btn rts-scope-beli" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a;" title="Pilih supplier">
                                                Pilih supplier
                                            </a>
                                        @elseif($r->wh_prd == 0 && $r->production_source === 'buy')
                                            <button type="button" class="btn btn-sm btn-minta-pr rts-action-btn rts-scope-beli" data-item="{{ $r->item_id }}" data-qty="{{ max(1, $r->buy_pr_qty ?? $r->rts_deficit) }}" style="background: #fffbeb; color: #d97706; border: 1px solid #fde68a;" title="Buat Purchase Request untuk 1 bulan">
                                                Buat PR beli
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        let currentRtsFilter = 'all';

        function updateRtsScopedActions(filter) {
            currentRtsFilter = filter;
            const showBeli = filter === 'beli_jadi';
            const showTarik = filter === 'tarik_prd';

            document.querySelectorAll('.rts-scope-beli').forEach(el => {
                el.style.display = showBeli ? '' : 'none';
            });

            document.querySelectorAll('.rts-scope-tarik').forEach(el => {
                el.style.display = showTarik ? '' : 'none';
            });

            document.querySelectorAll('.rts-action-line').forEach(line => {
                const visibleChild = Array.from(line.children).some(child => child.style.display !== 'none');
                line.style.display = visibleChild ? 'flex' : 'none';
            });
        }

        document.querySelectorAll('.btn-filter-rts').forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active class
                document.querySelectorAll('.btn-filter-rts').forEach(b => {
                    b.classList.remove('active', 'sd-primary');
                });
                this.classList.add('active', 'sd-primary');

                const filter = this.dataset.filter;
                updateRtsScopedActions(filter);
                let bulkCount = 0;
                let bulkPrCount = 0;
                
                document.querySelectorAll('.rts-row').forEach(row => {
                    const isKritis = row.dataset.kritis === "1";
                    const hasPrd = row.dataset.prd === "1";
                    const isBuy = row.dataset.buy === "1";
                    const mintaPrd = parseFloat(row.dataset.minta || 0);
                    
                    let show = false;
                    if(filter === 'all') show = true;
                    else if(filter === 'kritis' && isKritis) show = true;
                    else if(filter === 'tarik_prd' && hasPrd && mintaPrd > 0) show = true;
                    else if(filter === 'beli_jadi' && !hasPrd && isBuy && isKritis) show = true;

                    row.style.display = show ? '' : 'none';
                    
                    if (show) {
                        const btnMinta = row.querySelector('.btn-minta-stok');
                        if (btnMinta && btnMinta.style.display !== 'none') bulkCount++;
                        
                        const btnPr = row.querySelector('.btn-minta-pr');
                        if (btnPr && btnPr.style.display !== 'none') bulkPrCount++;
                    }
                });
                
                const bulkContainer = document.getElementById('bulk-action-container');
                const btnBulkMinta = document.getElementById('btn-bulk-minta');
                const btnBulkPr = document.getElementById('btn-bulk-pr');
                const showTarikBulk = filter === 'tarik_prd' && bulkCount > 0;
                const showBeliBulk = filter === 'beli_jadi' && bulkPrCount > 0;

                if (showTarikBulk || showBeliBulk) {
                    bulkContainer.style.display = 'flex';
                    if (showTarikBulk) {
                        btnBulkMinta.style.display = 'inline-flex';
                        document.getElementById('bulk-count').innerText = bulkCount;
                    } else {
                        btnBulkMinta.style.display = 'none';
                    }
                    if (showBeliBulk) {
                        btnBulkPr.style.display = 'inline-flex';
                        document.getElementById('bulk-pr-count').innerText = bulkPrCount;
                    } else {
                        btnBulkPr.style.display = 'none';
                    }
                } else {
                    bulkContainer.style.display = 'none';
                    btnBulkMinta.style.display = 'none';
                    btnBulkPr.style.display = 'none';
                }
            });
        });
        
        document.querySelectorAll('.btn-minta-stok').forEach(btn => {
            btn.addEventListener('click', function() {
                const itemId = this.dataset.item;
                const qty = this.dataset.qty;
                const button = this;
                
                button.disabled = true;
                const originalText = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                
                fetch("{{ route('inventory.warehouse_intelligence.request_draft') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ lines: [{ item_id: itemId, qty: qty }] })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const showUrl = "{{ route('rts.stock-requests.show', 'DRAFT_ID') }}".replace('DRAFT_ID', data.draft_id);
                        button.outerHTML = '<a href="'+showUrl+'" class="btn btn-sm" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; font-weight: 600; font-size: .7rem; border-radius: 7px; padding: .2rem .5rem; display: inline-flex; align-items: center; gap: 4px;" title="Lihat draft"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Lihat Draft</a>';
                        if (typeof Toast !== 'undefined') {
                            Toast.fire({ icon: 'success', title: data.message });
                        }
                        
                        // Decrement bulk count
                        const bulkCountEl = document.getElementById('bulk-count');
                        if (bulkCountEl) {
                            let bc = parseInt(bulkCountEl.innerText) - 1;
                            if (bc <= 0) {
                                document.getElementById('bulk-action-container').style.display = 'none';
                            } else {
                                bulkCountEl.innerText = bc;
                            }
                        }
                    } else {
                        button.disabled = false;
                        button.innerHTML = originalText;
                        alert(data.message || 'Terjadi kesalahan.');
                    }
                })
                .catch(err => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                    alert('Gagal menghubungi server.');
                });
            });
        });

        document.querySelectorAll('.btn-minta-pr').forEach(btn => {
            btn.addEventListener('click', function() {
                const itemId = this.dataset.item;
                const qty = this.dataset.qty;
                const button = this;
                
                button.disabled = true;
                const originalText = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                
                fetch("{{ route('inventory.warehouse_intelligence.request_pr_draft') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ lines: [{ item_id: itemId, qty: qty }] })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const showUrl = "{{ route('purchasing.purchase_requests.allocate_suppliers', 'DRAFT_ID') }}".replace('DRAFT_ID', data.pr_draft_id);
                        button.outerHTML = '<a href="'+showUrl+'" class="btn btn-sm" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-weight: 600; font-size: .7rem; border-radius: 7px; padding: .2rem .5rem; display: inline-flex; align-items: center; gap: 4px;" title="Pilih supplier"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Pilih Supplier</a>';
                        if (typeof Toast !== 'undefined') {
                            Toast.fire({ icon: 'success', title: data.message });
                        }
                    } else {
                        button.disabled = false;
                        button.innerHTML = originalText;
                        alert(data.message || 'Terjadi kesalahan.');
                    }
                })
                .catch(err => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                    alert('Gagal menghubungi server.');
                });
            });
        });
        
        document.getElementById('btn-bulk-minta')?.addEventListener('click', function() {
            const lines = [];
            const buttonsToUpdate = [];
            
            document.querySelectorAll('.rts-row').forEach(row => {
                if (row.style.display !== 'none') {
                    const btn = row.querySelector('.btn-minta-stok');
                    if (btn) {
                        lines.push({ item_id: btn.dataset.item, qty: btn.dataset.qty });
                        buttonsToUpdate.push(btn);
                    }
                }
            });
            
            if (lines.length === 0) return;
            
            this.disabled = true;
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
            
            fetch("{{ route('inventory.warehouse_intelligence.request_draft') }}", {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 
                    'Accept': 'application/json' 
                },
                body: JSON.stringify({ lines: lines })
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    const showUrl = "{{ route('rts.stock-requests.show', 'DRAFT_ID') }}".replace('DRAFT_ID', data.draft_id);
                    const newHtml = '<a href="'+showUrl+'" target="_blank" rel="noopener" class="btn btn-sm" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; font-weight: 600; font-size: .7rem; border-radius: 7px; padding: .2rem .5rem; display: inline-flex; align-items: center; gap: 4px;" title="Lihat draft"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Lihat Draft</a>';
                    
                    buttonsToUpdate.forEach(b => b.outerHTML = newHtml);
                    
                    if (typeof Toast !== 'undefined') Toast.fire({ icon: 'success', title: 'Berhasil request ' + lines.length + ' item!' });

                    window.open(showUrl, '_blank', 'noopener');
                    
                    // Update the bulk button to be a link to the draft
                    this.outerHTML = '<a href="'+showUrl+'" target="_blank" rel="noopener" class="sd-btn sd-primary" style="background:#10b981!important;border-color:#10b981!important;color:#fff!important;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Lihat Detail</a>';
                    
                    const btnPr = document.getElementById('btn-bulk-pr');
                    if(btnPr) btnPr.style.display = 'none';
                } else {
                    alert(data.message || 'Terjadi kesalahan.');
                }
            })
            .catch(e => alert('Gagal menghubungi server.'))
            .finally(() => {
                this.disabled = false;
                this.innerHTML = originalText;
            });
        });

        document.getElementById('btn-bulk-pr')?.addEventListener('click', function() {
            const lines = [];
            const buttonsToUpdate = [];
            
            document.querySelectorAll('.rts-row').forEach(row => {
                if (row.style.display !== 'none') {
                    const btn = row.querySelector('.btn-minta-pr');
                    if (btn) {
                        lines.push({ item_id: btn.dataset.item, qty: btn.dataset.qty });
                        buttonsToUpdate.push(btn);
                    }
                }
            });
            
            if (lines.length === 0) return;
            
            this.disabled = true;
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...';
            
            fetch("{{ route('inventory.warehouse_intelligence.request_pr_draft') }}", {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 
                    'Accept': 'application/json' 
                },
                body: JSON.stringify({ lines: lines })
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    const showUrl = "{{ route('purchasing.purchase_requests.allocate_suppliers', 'DRAFT_ID') }}".replace('DRAFT_ID', data.pr_draft_id);
                    const newHtml = '<a href="'+showUrl+'" target="_blank" rel="noopener" class="btn btn-sm" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-weight: 600; font-size: .7rem; border-radius: 7px; padding: .2rem .5rem; display: inline-flex; align-items: center; gap: 4px;" title="Pilih supplier"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Pilih Supplier</a>';
                    
                    buttonsToUpdate.forEach(b => b.outerHTML = newHtml);
                    
                    if (typeof Toast !== 'undefined') Toast.fire({ icon: 'success', title: 'Berhasil buat PR untuk ' + lines.length + ' item!' });

                    window.open(showUrl, '_blank', 'noopener');
                    
                    // Update the bulk button to be a link to the draft
                    this.outerHTML = '<a href="'+showUrl+'" target="_blank" rel="noopener" class="sd-btn sd-primary" style="background:#f59e0b!important;border-color:#f59e0b!important;color:#fff!important;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Pilih Supplier</a>';
                    
                    const btnMinta = document.getElementById('btn-bulk-minta');
                    if(btnMinta) btnMinta.style.display = 'none';
                } else {
                    alert(data.message || 'Terjadi kesalahan.');
                }
            })
            .catch(e => alert('Gagal menghubungi server.'))
            .finally(() => {
                this.disabled = false;
                this.innerHTML = originalText;
            });
        });
        
        updateRtsScopedActions('all');
        // trigger all on load to set colors correctly
        document.querySelector('.btn-filter-rts[data-filter="all"]').click();
    </script>
@endif
