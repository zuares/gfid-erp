@if ($rows->isEmpty())
    <div class="ii-empty">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: #cbd5e1; margin-bottom: 1rem;"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        <div>Semua stok di WH-RTS dalam kondisi aman! Tidak ada item mendesak.</div>
    </div>
@else

    <div class="card-main mb-3 p-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span style="font-size: .82rem; font-weight: 800; color: #111827; margin-right: .25rem;">Filter Operasional:</span>
            <button class="btn-filter-rts active sd-btn sd-primary" data-filter="all">Semua Item</button>
            <button class="btn-filter-rts sd-btn" data-filter="kritis">Stok Kritis</button>
            <button class="btn-filter-rts sd-btn" data-filter="tarik_prd">Bisa Tarik PRD</button>
            <button class="btn-filter-rts sd-btn" data-filter="beli_jadi">Perlu Beli (PR)</button>
            
            <div class="ms-auto d-flex gap-2" id="bulk-action-container" style="display: none;">
                <button class="sd-btn sd-primary" id="btn-bulk-minta" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
                    Minta Stok Masal (<span id="bulk-count">0</span>)
                </button>
                <button class="sd-btn sd-primary" id="btn-bulk-pr" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    Buat PR Masal (<span id="bulk-pr-count">0</span>)
                </button>
            </div>
        </div>
    </div>

    <div class="card-main">
        <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
            <table class="table table-hover table-sm align-middle table-list m-0 sortable-table">
                <thead style="background: rgba(248, 250, 252, 0.9); position: sticky; top: 0; z-index: 10; font-size: .8rem;">
                    <tr>
                        <th style="padding-left: 1rem; cursor: pointer;" class="sortable" data-sort="string">SKU & Produk <span class="sort-icon"></span></th>
                        <th class="text-end sortable" style="cursor: pointer;" data-sort="float" title="Laju penjualan harian (ADS)">Jual/Hr <span class="sort-icon"></span></th>
                        <th class="text-center" style="width: 130px;">Min-Max Disp</th>
                        <th class="text-end sortable" style="cursor: pointer;" data-sort="int">
                            {{ auth()->user()?->role === 'admin' || auth()->user()?->isOwner() ? 'Stok Gudang' : 'Stok RTS' }} 
                            <span class="sort-icon"></span>
                        </th>
                        <th class="text-end sortable" style="cursor: pointer;" data-sort="float" title="Estimasi hari stok">
                            Cover 
                            <span class="sort-icon"></span>
                        </th>
                        <th class="text-end sortable" style="cursor: pointer;" data-sort="int">Stok Produksi <span class="sort-icon"></span></th>
                        <th class="text-center">Saran</th>
                        <th class="text-end sortable" style="padding-right: 1rem; cursor: pointer;" data-sort="int">Aksi <span class="sort-icon"></span></th>
                    </tr>
                </thead>
                <tbody id="rts-tbody">
                    @foreach ($rows as $r)
                        @php
                            $isKritis = max(0, $r->ready - $r->ready_allocated) <= ($r->rts_min_display ?? max(5, ceil($r->ads * 7)));
                        @endphp
                        <tr class="rts-row" 
                            data-kritis="{{ $isKritis ? '1' : '0' }}" 
                            data-prd="{{ $r->wh_prd > 0 ? '1' : '0' }}" 
                            data-buy="{{ $r->production_source === 'buy' ? '1' : '0' }}">
                            <td style="padding-left: 1rem;">
                                <span class="fw-semibold">{{ $r->sku }}</span>
                                <div class="text-muted-ii" style="font-size: .7rem;">{{ $r->product }}</div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="text-muted-ii" style="font-size: .65rem;">{{ $r->category }}</span>
                                </div>
                            </td>
                            <td class="text-end text-muted">{{ $fmt($r->ads, 1) }}</td>
                            <td class="text-center">
                                <div class="rts-limit-view" id="limit-view-{{ $r->item_id }}">
                                    @if($r->rts_min_display !== null || $r->rts_max_display !== null)
                                        <span class="fw-semibold">{{ $r->rts_min_display ?? '-' }} - {{ $r->rts_max_display ?? '-' }}</span>
                                    @else
                                        <span class="text-muted-ii fst-italic">Auto</span>
                                    @endif
                                    <button class="btn btn-sm btn-link text-muted p-0 ms-1 btn-edit-limit" 
                                        data-id="{{ $r->item_id }}" 
                                        data-sku="{{ $r->sku }}"
                                        data-min="{{ $r->rts_min_display ?? '' }}"
                                        data-max="{{ $r->rts_max_display ?? '' }}"
                                        data-def-min="{{ max(5, ceil($r->ads * 7)) }}"
                                        data-def-max="{{ ceil($r->ads * 14) }}"
                                        title="Edit Limit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                </div>
                            </td>
                            <td class="text-end fw-bold {{ $isKritis ? 'text-danger' : 'text-warning' }}">
                                {{ $fmt(max(0, $r->ready - $r->ready_allocated)) }}
                                <div class="text-muted-ii" style="font-size: .65rem; font-weight: normal;">
                                    Fisik: {{ $fmt($r->ready) }}
                                </div>
                            </td>
                            <td class="text-end fw-semibold">
                                @if($r->rts_cover < 3)
                                    <span class="text-danger">{{ $fmt($r->rts_cover, 1) }} hr</span>
                                @elseif($r->rts_cover < 7)
                                    <span class="text-warning" style="color: #d97706 !important;">{{ $fmt($r->rts_cover, 1) }} hr</span>
                                @else
                                    <span class="text-muted-ii">{{ $fmt($r->rts_cover, 1) }} hr</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($r->wh_prd > 0)
                                    <span class="fw-bold" style="color: #059669;">{{ $fmt($r->wh_prd) }}</span>
                                @else
                                    <span class="text-muted-ii">0</span>
                                @endif
                            </td>
                            <td class="text-center" style="font-size: .75rem;">
                                @if($isKritis && $r->wh_prd > 0)
                                    <span style="color: #059669; font-weight: 600;"><i class="bi bi-box-seam me-1"></i>Tarik PRD ({{ $fmt($r->minta_prd) }})</span>
                                @elseif($isKritis && $r->wh_prd == 0 && $r->production_source === 'buy')
                                    <span style="color: #d97706; font-weight: 600;"><i class="bi bi-cart-plus me-1"></i>Beli ({{ $fmt(max(1, $r->rts_deficit)) }})</span>
                                @elseif($isKritis && $r->wh_prd == 0 && $r->production_source !== 'buy')
                                    <span style="color: #dc2626; font-weight: 600;"><i class="bi bi-scissors me-1"></i>Jahit ({{ $fmt(max(1, $r->rts_deficit)) }})</span>
                                @elseif(!$isKritis)
                                    <span style="color: #94a3b8; font-weight: 600;"><i class="bi bi-check-circle me-1"></i>Aman</span>
                                @else
                                    <span style="color: #94a3b8; font-weight: 600;">-</span>
                                @endif
                            </td>
                            <td class="text-end" style="padding-right: 1rem;">
                                @if($r->draft_id)
                                    <a href="{{ route('rts.stock-requests.show', $r->draft_id) }}" class="btn btn-sm" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; font-weight: 600; font-size: .7rem; border-radius: 7px; padding: .2rem .5rem; display: inline-flex; align-items: center; gap: 4px;" title="Lihat draft">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                        Lihat Draft
                                    </a>
                                @elseif($r->wh_prd > 0 && $r->minta_prd > 0)
                                    <button type="button" class="btn btn-sm btn-minta-stok" data-item="{{ $r->item_id }}" data-qty="{{ $r->minta_prd }}" style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-weight: 600; font-size: .7rem; border-radius: 7px; padding: .2rem .5rem; display: inline-flex; align-items: center; gap: 4px;" title="Buat Request RTS">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
                                        Minta Stok ({{ $fmt($r->minta_prd) }})
                                    </button>
                                @elseif($r->pr_draft_id)
                                    <a href="{{ route('purchasing.purchase_requests.edit', $r->pr_draft_id) }}" class="btn btn-sm" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-weight: 600; font-size: .7rem; border-radius: 7px; padding: .2rem .5rem; display: inline-flex; align-items: center; gap: 4px;" title="Lihat PR Draft">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                        Lihat Draft PR
                                    </a>
                                @elseif($r->wh_prd == 0 && $r->production_source === 'buy')
                                    <button type="button" class="btn btn-sm btn-minta-pr" data-item="{{ $r->item_id }}" data-qty="{{ max(1, $r->rts_deficit) }}" style="background: #fffbeb; color: #d97706; border: 1px solid #fde68a; font-weight: 600; font-size: .7rem; border-radius: 7px; padding: .2rem .5rem; display: inline-flex; align-items: center; gap: 4px;" title="Buat Purchase Request">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                                        Buat PR (Beli)
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        document.querySelectorAll('.btn-filter-rts').forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active class
                document.querySelectorAll('.btn-filter-rts').forEach(b => {
                    b.classList.remove('active', 'sd-primary');
                });
                this.classList.add('active', 'sd-primary');

                const filter = this.dataset.filter;
                let bulkCount = 0;
                let bulkPrCount = 0;
                
                document.querySelectorAll('.rts-row').forEach(row => {
                    const isKritis = row.dataset.kritis === "1";
                    const hasPrd = row.dataset.prd === "1";
                    const isBuy = row.dataset.buy === "1";
                    
                    let show = false;
                    if(filter === 'all') show = true;
                    else if(filter === 'kritis' && isKritis) show = true;
                    else if(filter === 'tarik_prd' && hasPrd && isKritis) show = true;
                    else if(filter === 'beli_jadi' && !hasPrd && isBuy && isKritis) show = true;

                    row.style.display = show ? '' : 'none';
                    
                    if (show) {
                        const btnMinta = row.querySelector('.btn-minta-stok');
                        if (btnMinta) bulkCount++;
                        
                        const btnPr = row.querySelector('.btn-minta-pr');
                        if (btnPr) bulkPrCount++;
                    }
                });
                
                const bulkContainer = document.getElementById('bulk-action-container');
                const btnBulkMinta = document.getElementById('btn-bulk-minta');
                const btnBulkPr = document.getElementById('btn-bulk-pr');
                
                if (bulkCount > 0 || bulkPrCount > 0) {
                    bulkContainer.style.display = 'flex';
                    if (bulkCount > 0) {
                        btnBulkMinta.style.display = 'inline-flex';
                        document.getElementById('bulk-count').innerText = bulkCount;
                    } else {
                        btnBulkMinta.style.display = 'none';
                    }
                    if (bulkPrCount > 0) {
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
                        const showUrl = "{{ route('purchasing.purchase_requests.edit', 'DRAFT_ID') }}".replace('DRAFT_ID', data.pr_draft_id);
                        button.outerHTML = '<a href="'+showUrl+'" class="btn btn-sm" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-weight: 600; font-size: .7rem; border-radius: 7px; padding: .2rem .5rem; display: inline-flex; align-items: center; gap: 4px;" title="Lihat PR Draft"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Lihat Draft PR</a>';
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
                    const newHtml = '<a href="'+showUrl+'" class="btn btn-sm" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; font-weight: 600; font-size: .7rem; border-radius: 7px; padding: .2rem .5rem; display: inline-flex; align-items: center; gap: 4px;" title="Lihat draft"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Lihat Draft</a>';
                    
                    buttonsToUpdate.forEach(b => b.outerHTML = newHtml);
                    
                    if (typeof Toast !== 'undefined') Toast.fire({ icon: 'success', title: 'Berhasil request ' + lines.length + ' item!' });
                    
                    // Update the bulk button to be a link to the draft
                    this.outerHTML = '<a href="'+showUrl+'" class="sd-btn sd-primary" style="background:#10b981!important;border-color:#10b981!important;color:#fff!important;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Lihat Detail</a>';
                    
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
                    const showUrl = "{{ route('purchasing.purchase_requests.edit', 'DRAFT_ID') }}".replace('DRAFT_ID', data.pr_draft_id);
                    const newHtml = '<a href="'+showUrl+'" class="btn btn-sm" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-weight: 600; font-size: .7rem; border-radius: 7px; padding: .2rem .5rem; display: inline-flex; align-items: center; gap: 4px;" title="Lihat PR Draft"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Lihat Draft PR</a>';
                    
                    buttonsToUpdate.forEach(b => b.outerHTML = newHtml);
                    
                    if (typeof Toast !== 'undefined') Toast.fire({ icon: 'success', title: 'Berhasil buat PR untuk ' + lines.length + ' item!' });
                    
                    // Update the bulk button to be a link to the draft
                    this.outerHTML = '<a href="'+showUrl+'" class="sd-btn sd-primary" style="background:#f59e0b!important;border-color:#f59e0b!important;color:#fff!important;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Lihat Detail</a>';
                    
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
        
        // trigger all on load to set colors correctly
        document.querySelector('.btn-filter-rts[data-filter="all"]').click();
    </script>
@endif
