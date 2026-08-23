<!-- MODAL PELACAKAN RESI -->
<div class="modal fade" id="trackingModal" tabindex="-1" aria-labelledby="trackingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-white border-bottom border-light px-4 py-3">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="trackingModalLabel">
                    <i class="bi bi-geo-alt-fill text-primary me-2"></i> Lacak Pengiriman
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light" style="min-height: 250px;">
                <div class="bg-white rounded-3 p-3 shadow-sm mb-4 border border-light d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small mb-1">Pesanan SN</div>
                        <div class="fw-bold text-primary" id="t_orderSn" style="font-family: monospace; font-size: 1.1rem;">-</div>
                    </div>
                    <i class="bi bi-box-seam fs-2 text-muted opacity-50"></i>
                </div>
                <div id="trackingModalBody">
                    <!-- Tracking Timeline Here -->
                </div>
            </div>
            <div class="modal-footer bg-white border-top border-light px-4 py-3">
                <button type="button" class="btn btn-secondary fw-bold px-4 rounded-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<!-- END MODAL -->

{{-- Review Modal (Sedang Proses) --}}
<div class="ord-review-modal-bg" id="ordReviewBg" onclick="closeReviewModal(event)">
    <div class="ord-review-modal" onclick="event.stopPropagation()">
        <div class="orm-header">
            <div>
                <div class="orm-title" id="ormTitle">—</div>
                <div class="orm-sub"  id="ormSub">—</div>
            </div>
            <button class="orm-close" onclick="closeReviewModal()">✕</button>
        </div>
        <div class="orm-body" id="ormBody">
            <div style="text-align:center;padding:2rem;color:#94a3b8">Memuat…</div>
        </div>
        <div class="orm-footer">
            <button class="btn-review" onclick="closeReviewModal()" style="font-size:.78rem;padding:.35rem 1rem">Tutup</button>
        </div>
    </div>
</div>

{{-- Quick Sync Modal --}}
{{-- Arrange Shipment Modal --}}
<div class="modal fade" id="arrangeShipmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:450px">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-black" style="font-size:1rem">🚚 Atur Pengiriman</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <div id="asLoading" style="text-align:center;padding:1.5rem;color:#64748b;font-size:0.85rem;">
                    ⏳ Sedang memeriksa opsi pengiriman dari Marketplace...
                </div>
                <div id="asContent" style="display:none;">
                    <div class="alert alert-info py-2" style="font-size:0.75rem" id="asAlert">
                        Silakan pilih metode pengiriman untuk order ini.
                    </div>
                    
                    <div id="asOptions" class="d-flex flex-column gap-2 mb-3">
                        <!-- Options will be injected here -->
                    </div>
                    
                    <input type="hidden" id="asStoreId">
                    <input type="hidden" id="asOrderSn">

                    <div class="form-check mb-3" style="margin-top: 10px;">
                        <input class="form-check-input" type="checkbox" id="asPrintDocument" checked>
                        <label class="form-check-label" for="asPrintDocument" style="font-size:0.8rem; font-weight:600;">
                            Langsung cetak resi setelah sukses
                        </label>
                    </div>
                    
                    <button class="btn btn-primary w-100 fw-bold rounded-pill" id="asSubmitBtn" onclick="submitArrangeShipment()">Konfirmasi Pengiriman</button>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- Quick Sync Modal (Premium Redesign) --}}
<div class="modal fade" id="quickSyncModal" tabindex="-1" aria-labelledby="quickSyncModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
        <div class="modal-content" style="border-radius:24px;border:none;box-shadow:0 25px 60px rgba(0,0,0,.18);overflow:hidden">

            {{-- Header --}}
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);padding:1.25rem 1.5rem">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:38px;height:38px;background:rgba(255,255,255,.12);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem">🔄</div>
                    <div>
                        <h5 class="modal-title fw-black text-white mb-0" id="quickSyncModalLabel" style="font-size:.95rem;letter-spacing:-.01em">Sync Pesanan</h5>
                        <p class="mb-0" style="font-size:.72rem;color:#94a3b8">Perbarui data dari semua toko terhubung</p>
                    </div>
                </div>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity:.6"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body" style="padding:1.25rem 1.5rem">

                {{-- Config Panel --}}
                <div id="qsConfigPanel">
                    {{-- Rentang Waktu --}}
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.7rem;font-weight:700;color:#64748b;letter-spacing:.04em;text-transform:uppercase;margin-bottom:.4rem">Rentang Waktu</label>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.4rem">
                            <button class="qs-range-btn" data-days="1" style="padding:.45rem .2rem;font-size:.72rem;font-weight:600;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#475569;cursor:pointer;transition:all .2s">1 Hari</button>
                            <button class="qs-range-btn active" data-days="3" style="padding:.45rem .2rem;font-size:.72rem;font-weight:600;border:1.5px solid #0f172a;border-radius:10px;background:#0f172a;color:#fff;cursor:pointer;transition:all .2s">3 Hari</button>
                            <button class="qs-range-btn" data-days="7" style="padding:.45rem .2rem;font-size:.72rem;font-weight:600;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#475569;cursor:pointer;transition:all .2s">7 Hari</button>
                            <button class="qs-range-btn" data-days="14" style="padding:.45rem .2rem;font-size:.72rem;font-weight:600;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#475569;cursor:pointer;transition:all .2s">14 Hari</button>
                            <button class="qs-range-btn" data-days="30" style="padding:.45rem .2rem;font-size:.72rem;font-weight:600;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#475569;cursor:pointer;transition:all .2s">30 Hari</button>
                            <button class="qs-range-btn" data-days="60" style="padding:.45rem .2rem;font-size:.72rem;font-weight:600;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#475569;cursor:pointer;transition:all .2s">60 Hari</button>
                            <button class="qs-range-btn" data-days="90" style="padding:.45rem .2rem;font-size:.72rem;font-weight:600;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#475569;cursor:pointer;transition:all .2s">90 Hari</button>
                            <button class="qs-range-btn" data-days="180" style="padding:.45rem .2rem;font-size:.72rem;font-weight:600;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#475569;cursor:pointer;transition:all .2s">180 Hari</button>
                            <button class="qs-range-btn" data-days="365" style="padding:.45rem .2rem;font-size:.72rem;font-weight:600;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#475569;cursor:pointer;transition:all .2s">1 Tahun</button>
                        </div>
                        <input type="hidden" id="qsSyncRangeDays" value="3">
                        <div id="qsRangeHint" style="display:none;margin-top:.5rem;padding:.5rem .6rem;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:.68rem;color:#92400e;line-height:1.4">
                            ⏳ Rentang panjang butuh waktu lebih lama & ditarik bertahap (jendela 14 hari).
                            Disarankan pakai <b>Sync di Latar Belakang</b> agar tidak menunggu di layar.
                            Rentang ≥ 90 hari otomatis dijalankan di latar belakang.
                        </div>
                    </div>

                    {{-- Tipe Sync --}}
                    <div class="mb-3">
                        <label class="form-label" style="font-size:.7rem;font-weight:700;color:#64748b;letter-spacing:.04em;text-transform:uppercase;margin-bottom:.4rem">Yang Di-sync</label>
                        <div style="display:flex;flex-direction:column;gap:.4rem">
                            <label style="display:flex;align-items:center;gap:.6rem;padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:12px;cursor:pointer;transition:border-color .2s" id="qsTypeOrdersLabel">
                                <input type="checkbox" id="qsSyncOrders" checked style="cursor:pointer;accent-color:#0f172a">
                                <div style="flex:1">
                                    <div style="font-size:.78rem;font-weight:700;color:#1e293b">📦 Pesanan Reguler</div>
                                    <div style="font-size:.68rem;color:#94a3b8">Order normal dari Shopee</div>
                                </div>
                            </label>
                            <label style="display:flex;align-items:center;gap:.6rem;padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:12px;cursor:pointer;transition:border-color .2s" id="qsTypeBookingsLabel">
                                <input type="checkbox" id="qsSyncBookings" checked style="cursor:pointer;accent-color:#0f172a">
                                <div style="flex:1">
                                    <div style="font-size:.78rem;font-weight:700;color:#1e293b">⚡ Pesanan Kilat</div>
                                    <div style="font-size:.68rem;color:#94a3b8">Booking & status pengiriman kilat</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Dry Run toggle --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem .75rem;background:#f8fafc;border-radius:10px;margin-bottom:.75rem">
                        <div>
                            <div style="font-size:.75rem;font-weight:600;color:#475569">Mode Dry Run</div>
                            <div style="font-size:.65rem;color:#94a3b8">Simulasi tanpa menyimpan ke database</div>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="qsSyncDryRun" style="cursor:pointer;accent-color:#0f172a">
                        </div>
                    </div>
                </div>

                {{-- Progress Panel --}}
                <div id="qsProgressPanel" style="display:none">
                    {{-- Overall progress bar --}}
                    <div style="margin-bottom:1rem">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span id="qsProgressText" style="font-size:.78rem;font-weight:600;color:#475569">Mempersiapkan…</span>
                            <span id="qsProgressPct" style="font-size:.72rem;color:#94a3b8">0%</span>
                        </div>
                        <div style="height:6px;background:#e2e8f0;border-radius:999px;overflow:hidden">
                            <div id="qsProgressBar" style="height:100%;background:linear-gradient(90deg,#0f172a,#334155);border-radius:999px;width:0%;transition:width .5s cubic-bezier(.4,0,.2,1)"></div>
                        </div>
                    </div>

                    {{-- Per-store cards --}}
                    <div id="qsStoreList" style="display:flex;flex-direction:column;gap:.5rem;max-height:200px;overflow-y:auto"></div>

                    {{-- Live stats --}}
                    <div id="qsLiveStats" style="display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;margin-top:.75rem">
                        <div style="text-align:center;padding:.5rem;background:#f0fdf4;border-radius:10px">
                            <div id="qsStatNew" style="font-size:1.15rem;font-weight:800;color:#16a34a">0</div>
                            <div style="font-size:.62rem;color:#15803d;font-weight:600">Order Baru</div>
                        </div>
                        <div style="text-align:center;padding:.5rem;background:#eff6ff;border-radius:10px">
                            <div id="qsStatUpdated" style="font-size:1.15rem;font-weight:800;color:var(--shp-accent)">0</div>
                            <div style="font-size:.62rem;color:#1d4ed8;font-weight:600">Diperbarui</div>
                        </div>
                        <div style="text-align:center;padding:.5rem;background:#fff7ed;border-radius:10px">
                            <div id="qsStatIssues" style="font-size:1.15rem;font-weight:800;color:#ea580c">0</div>
                            <div style="font-size:.62rem;color:#c2410c;font-weight:600">Perlu Cek</div>
                        </div>
                    </div>
                </div>

                {{-- Result Panel --}}
                <div id="qsResultPanel" style="display:none;text-align:center;padding:.5rem 0">
                    <div id="qsResultIcon" style="font-size:2.5rem;margin-bottom:.5rem">✅</div>
                    <div id="qsResultTitle" style="font-size:.95rem;font-weight:800;color:#1e293b;margin-bottom:.25rem">Sync Selesai!</div>
                    <div id="qsResultSub" style="font-size:.78rem;color:#64748b"></div>
                </div>

                {{-- Alert --}}
                <div id="qsAlert" class="alert d-none" style="font-size:.78rem;border-radius:12px;margin-top:.75rem;margin-bottom:0"></div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer border-0 flex-column" style="padding:.75rem 1.5rem 1.25rem;gap:.5rem">
                <div class="d-flex w-100" style="gap:.5rem">
                    <button id="qsCancelBtn" class="btn btn-light border fw-bold flex-fill" style="border-radius:999px;font-size:.8rem" data-bs-dismiss="modal">Tutup</button>
                    <button id="qsRunBtn" class="btn btn-dark fw-bold flex-fill" style="border-radius:999px;font-size:.8rem" onclick="runQuickSync()">🔄 Sync Sekarang</button>
                </div>
                <button id="qsBgBtn" class="btn btn-outline-dark fw-bold w-100" style="border-radius:999px;font-size:.78rem" onclick="runBackgroundSync()">🌙 Sync di Latar Belakang</button>
            </div>
        </div>
    </div>
</div>

{{-- Bulk Arrange Shipment Modal --}}
<div class="modal fade" id="bulkArrangeShipmentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:460px">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-black" style="font-size:1rem">🚚 Atur Semua Pengiriman</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <div id="basConfirmView">
                    <p style="font-size:.8rem;color:#64748b;margin-bottom:1rem" id="basSummaryText">
                        Menghitung order yang siap diproses...
                    </p>
                    
                    <div class="mb-3">
                        <label style="font-size:0.75rem;font-weight:700;color:#334155;margin-bottom:0.5rem;display:block">Metode Pengiriman Default</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="basMethod" id="basDropoff" value="dropoff" checked>
                            <label class="form-check-label" for="basDropoff"><strong>Drop-off</strong> (Antar ke Cabang)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="basMethod" id="basPickup" value="pickup">
                            <label class="form-check-label" for="basPickup"><strong>Pickup</strong> (Kurir Jemput)</label>
                        </div>
                    </div>
                    
                    <button class="btn btn-primary w-100 fw-bold rounded-pill" id="basStartBtn" onclick="startBulkArrangeShipment()">Mulai Proses</button>
                </div>
                
                <div id="basProgressView" style="display:none">
                    <p style="font-size:.8rem;color:#334155;font-weight:600;margin-bottom:.5rem" id="basProgressText">Memproses 0 dari 0 order...</p>
                    <div class="progress" style="height:10px;border-radius:5px;background:#e2e8f0;margin-bottom:1rem">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="basProgressBar" style="width:0%"></div>
                    </div>
                    <div id="basLog" style="height:120px;overflow-y:auto;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:.5rem;font-size:.7rem;font-family:monospace;color:#475569">
                        <!-- log lines -->
                    </div>
                </div>

                <div id="basDoneView" style="display:none;text-align:center;padding:1rem 0">
                    <div style="font-size:3rem;margin-bottom:.5rem">✅</div>
                    <h5 class="fw-black text-success">Selesai!</h5>
                    <p style="font-size:.8rem;color:#64748b" id="basResultText">Berhasil mengatur pengiriman.</p>
                    <button class="btn btn-light w-100 fw-bold rounded-pill mt-3" data-bs-dismiss="modal" onclick="loadOrders()">Tutup & Refresh</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Order Detail Modal --}}
<div class="modal fade" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:15px; border:none; box-shadow:0 10px 40px rgba(0,0,0,0.1)">
            <div class="modal-header" style="border-bottom:1px solid #e2e8f0; padding:1.2rem 1.5rem">
                <h5 class="modal-title fw-black" style="font-size:1.1rem; color:#0f172a" id="detailModalTitle">Detail Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailModalBody" style="background:#f8fafc; padding:1.5rem">
                <!-- Injected via JS -->
            </div>
            <div class="modal-footer border-0" style="background:#fff; border-top:1px solid #e2e8f0 !important; border-bottom-left-radius:15px; border-bottom-right-radius:15px">
                <button type="button" class="btn btn-light fw-bold rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
