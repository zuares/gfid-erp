{{-- Toolbar: Actions & Sub-Tabs --}}
<div class="process-toolbar" id="processToolbar" style="margin-bottom:1rem; border:1px solid var(--shp-border); background:var(--card); border-radius:8px; padding:6px;">
        <div class="toolbar-top-row" style="display:flex; align-items:center; width:100%;">
            <div class="process-toolbar-info" id="toolbarInfo" style="display:none; font-size:.8rem; padding:.2rem .5rem;">
            </div>
            
            <div id="subTabProcessedContainer" style="display:none; gap: 0.25rem; align-items: center; background: #f8fafc; padding: 3px; border-radius: 8px; border: 1px solid var(--shp-border); margin-left: 0.5rem;">
                <button class="ord-subtab" data-sub="all" onclick="switchSubTabProcessed('all', this)">Semua <span class="ord-badge bg-secondary" id="badge-sub-all">—</span></button>
                <button class="ord-subtab active" data-sub="packing" onclick="switchSubTabProcessed('packing', this)">Belum Packing <span class="ord-badge bg-secondary urgent" id="badge-sub-packing">—</span></button>
                <button class="ord-subtab" data-sub="ready" onclick="switchSubTabProcessed('ready', this)">Siap Kirim <span class="ord-badge bg-secondary" id="badge-sub-ready">—</span></button>
            </div>
            <div id="subTabReadyContainer" style="display:none; gap: 0.25rem; align-items: center; background: #f8fafc; padding: 3px; border-radius: 8px; border: 1px solid var(--shp-border); margin-left: 0.5rem;">
                <button class="ord-subtab" data-sub="all" onclick="switchSubTabReady('all', this)">Semua <span class="ord-badge bg-secondary" id="badge-sub-ready-all">—</span></button>
                <button class="ord-subtab active" data-sub="process" onclick="switchSubTabReady('process', this)">Bisa Proses <span class="ord-badge bg-secondary" id="badge-sub-ready-process">—</span></button>
                <button class="ord-subtab" data-sub="pending" onclick="switchSubTabReady('pending', this)" title="Status platform masih tertunda">⏳ Tertunda <span class="ord-badge bg-secondary" id="badge-sub-ready-pending">—</span></button>
                <button class="ord-subtab" data-sub="blocked" onclick="switchSubTabReady('blocked', this)" title="Platform belum mengizinkan pengaturan pengiriman">🚫 Belum Bisa Atur <span class="ord-badge bg-secondary" id="badge-sub-ready-blocked">—</span></button>
                <button class="ord-subtab" data-sub="unpaid" onclick="switchSubTabReady('unpaid', this)">Belum Bayar <span class="ord-badge bg-secondary" id="badge-sub-ready-unpaid">—</span></button>
                <button class="ord-subtab" data-sub="cancel" onclick="switchSubTabReady('cancel', this)">🚫 Batal <span class="ord-badge bg-secondary" id="badge-sub-ready-cancel">—</span></button>
            </div>
            <div id="subTabShippedContainer" style="display:none; gap: 0.25rem; align-items: center; background: #f8fafc; padding: 3px; border-radius: 8px; border: 1px solid var(--shp-border); margin-left: 0.5rem;">
                <button class="ord-subtab" data-sub="all" onclick="switchSubTabShipped('all', this)">Semua <span class="ord-badge bg-secondary" id="badge-sub-shipped-all">—</span></button>
                <button class="ord-subtab active" data-sub="shipped" onclick="switchSubTabShipped('shipped', this)">Dikirim <span class="ord-badge bg-secondary" id="badge-sub-shipped-shipped">—</span></button>
                <button class="ord-subtab" data-sub="confirm" onclick="switchSubTabShipped('confirm', this)">Menunggu <span class="ord-badge bg-secondary" id="badge-sub-shipped-confirm">—</span></button>
                <button class="ord-subtab" data-sub="failed" onclick="switchSubTabShipped('failed', this)">🚨 Pengiriman Gagal <span class="ord-badge bg-secondary" id="badge-sub-shipped-failed">—</span></button>
                <span class="ord-live-status" id="shippedLiveStatus" aria-live="polite" title="Daftar diperbarui otomatis setiap 8 detik saat tab ini terbuka">
                    <span class="ord-live-dot"></span><span id="shippedLiveStatusText">Live</span>
                </span>
                <button type="button" class="ord-live-refresh" id="shippedLiveRefresh" onclick="refreshShippedLive()" title="Segarkan status pengiriman sekarang" aria-label="Segarkan status pengiriman">↻</button>
            </div>
            <div id="subTabRrcContainer" style="display:none; gap: 0.25rem; align-items: center; background: #f8fafc; padding: 3px; border-radius: 8px; border: 1px solid var(--shp-border); margin-left: 0.5rem;">
                <button class="ord-subtab active" data-sub="return" onclick="switchSubTabRrc('return', this)">↩️ Retur <span class="ord-badge bg-secondary" id="badge-sub-rrc-return">—</span></button>
                <button class="ord-subtab" data-sub="refund" onclick="switchSubTabRrc('refund', this)">💸 Refund <span class="ord-badge bg-secondary" id="badge-sub-rrc-refund">—</span></button>
                <button class="btn btn-sm btn-outline-secondary" style="font-size:0.7rem;padding:0.1rem 0.5rem;margin-left:4px" onclick="loadRrc(true)" title="Muat ulang dari database">🔄 Segarkan</button>
                <button class="btn btn-sm btn-outline-primary" id="btnSyncRrc" style="font-size:0.7rem;padding:0.1rem 0.5rem" onclick="syncRrc()" title="Tarik data terbaru dari Shopee lalu simpan ke database">⬇️ Tarik dari Shopee</button>
            </div>
        </div>

        <div class="process-toolbar-actions" id="toolbarActionsReady" style="display:none; gap:6px;">
            <button class="btn-toolbar" id="btnBulkArrangeShipment" onclick="openBulkArrangeShipment()" style="border:1px solid #bfdbfe; background:#eff6ff; color:#1d4ed8; font-weight:700;">🚚 Atur Kirim</button>
            <button class="btn-toolbar" id="btnBulkPrintReady" onclick="printPickingList()" style="border:1px solid #cbd5e1; background:#f8fafc; color:#475569; font-weight:600;">🖨️ Picking List</button>
        </div>
        <div class="process-toolbar-actions" id="toolbarActionsProcessed" style="display:none; gap:6px;">
            <button class="btn-toolbar" id="btnBulkPrintProcessed" onclick="printPickingList()" style="border:1px solid #cbd5e1; background:#f8fafc; color:#475569; font-weight:600;">🖨️ Picking List</button>
            <button class="btn-toolbar" id="btnBulkPrintDocuments" onclick="printAllDocuments()" style="border:1px solid #a5f3fc; background:#ecfeff; color:#0e7490; font-weight:600;">🖨️ Cetak Resi</button>
            <button class="btn-toolbar" id="btnBulkPrintGreetings" onclick="printAllGreetings()" style="border:1px solid #ddd6fe; background:#f5f3ff; color:#6d28d9; font-weight:600;">💌 Kartu</button>
            <button class="btn-toolbar" id="btnBulkFulfill" onclick="window.location='/sales/shipments'" style="border:1px solid #bbf7d0; background:#f0fdf4; color:#15803d; font-weight:700;">📦 Shipment</button>
        </div>
        <div class="process-toolbar-actions" id="toolbarActionsUnresolved" style="display:none; gap:6px;">
            <a href="/marketplace/issues" class="btn-toolbar primary" style="font-weight:700;">🔗 Perbaiki →</a>
        </div>
    </div>
