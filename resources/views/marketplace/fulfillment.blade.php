@extends('layouts.app')
@section('title', 'Marketplace • Fulfillment')

@include('marketplace._shared')
@include('sales.shipments._scan_styles')

@section('content')
<div class="sr-scan-page">
    <div class="sr-topbar">
        <div class="sr-top-main">
            <h1 class="sr-title">Marketplace Fulfillment</h1>
            <div class="sr-sub">Scan order, konfirmasi barang, dan proses pengiriman.</div>
        </div>
        <div class="sr-top-actions">
            @if(app()->environment('local', 'development', 'testing', 'staging'))
                <button onclick="devLoadNextOrder()" class="sr-btn">⚡ Load Next</button>
                <button onclick="devRemapItemsHere()" class="sr-btn">🔁 Remap</button>
                <button id="btnBypassBlocker" onclick="devToggleBypass()" class="sr-btn" style="color: #92400e;">🚧 Bypass OFF</button>
            @endif
            <a href="/marketplace/orders" class="sr-btn sr-btn-primary">📦 Lihat Orders</a>
        </div>
    </div>

    <div class="sr-shell">
        <div class="sr-workflow-stepper">
            <span class="sr-flow-step active" id="stepScan">1. Scan Barcode</span>
            <span class="sr-flow-sep">-&gt;</span>
            <span class="sr-flow-step" id="stepProcess">2. Proses Item</span>
            <span class="sr-flow-sep">-&gt;</span>
            <span class="sr-flow-step" id="stepReview">3. Konfirmasi</span>
        </div>

        {{-- KPI --}}
        <div class="sr-summary" style="margin-bottom: .25rem">
            <div class="sr-stat"><div class="sr-stat-label">Selesai Hari Ini</div><div class="sr-stat-value" id="kpiDoneToday">—</div></div>
            <div class="sr-stat"><div class="sr-stat-label">Item Diproses</div><div class="sr-stat-value" id="kpiItemsToday">—</div></div>
            <div class="sr-stat"><div class="sr-stat-label">Menunggu Review</div><div class="sr-stat-value" id="kpiWaiting">—</div></div>
        </div>

        {{-- Blocker card --}}
        <div id="blockerCard" style="display:none; margin-bottom: .25rem" class="sr-panel">
            <div class="sr-panel-body" style="background: rgba(254, 242, 242, 0.8)">
                <div style="color: #991b1b; font-size: .95rem; font-weight: 800; display:flex; align-items:center; gap:.5rem">
                    <span style="font-size: 1.25rem">🚫</span> Selesaikan masalah dulu sebelum memproses order baru
                </div>
                <div id="blockerIssueList" style="margin-top: .75rem; margin-bottom: .75rem; font-size: .8rem"></div>
                <a href="{{ route('marketplace.issues') }}" class="sr-btn sr-btn-danger">Perbaiki Sekarang</a>
            </div>
        </div>

        {{-- Scan Box Panel --}}
        <div id="scanBoxPanel" class="sr-panel" style="margin-bottom: .5rem">
            <div class="sr-panel-body sr-scan-card">
                <div class="sr-mode-row">
                    <div style="display:flex; gap: .3rem">
                        <button id="modeTabOrder" onclick="setScanMode('single')" class="sr-btn" style="min-height: 28px; padding: 2px 10px; font-size: 0.7rem">📦 Scan Order</button>
                        <button id="modeTabBatch" onclick="setScanMode('batch')" class="sr-btn sr-btn-primary" style="min-height: 28px; padding: 2px 10px; font-size: 0.7rem">📋 Batch Mode</button>
                    </div>
                    <div id="scanModeBadge" class="sr-mode">BATCH MODE</div>
                </div>

        {{-- ── SINGLE ORDER MODE ── --}}
        <div id="singleScanBox" style="display:none">
            <div style="display:flex;align-items:center;gap:.65rem;margin-bottom:.75rem">
                <span style="font-size:1.35rem">📦</span>
                <div>
                    <div style="color:#0f172a;font-weight:800;font-size:1rem;letter-spacing:-.01em">Scan Order</div>
                    <div style="color:#94a3b8;font-size:.75rem">Scan barcode atau ketik nomor order, lalu tekan Enter</div>
                </div>
            </div>
            {{-- Focal point: input dengan border indigo + ring --}}
            <div style="display:flex;gap:.65rem;align-items:center;background:#f5f3ff;border-radius:14px;padding:.55rem .65rem">
                <input id="scanInput" type="text"
                    placeholder="Contoh: 260609MDP1J4DQ"
                    autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                    style="flex:1;background:transparent;border:none;
                           padding:.2rem .3rem;color:#0f172a;font-size:1.05rem;font-weight:700;font-family:monospace;
                           outline:none;"
                    onkeydown="if(event.key==='Enter')doScan()">
                <button onclick="doScan()" id="scanBtn"
                    style="background:#6366f1;border:none;border-radius:10px;padding:.6rem 1.3rem;color:#fff;
                           font-weight:700;font-size:.9rem;cursor:pointer;transition:background .15s;white-space:nowrap;
                           box-shadow:0 2px 8px rgba(99,102,241,.3)"
                    onmouseover="this.style.background='#4f46e5'" onmouseout="this.style.background='#6366f1'">
                    🔍 Cari
                </button>
            </div>
            <div id="scanResult" style="margin-top:.6rem;font-size:.82rem;min-height:1.2rem;padding:0 .25rem"></div>
        </div>

        {{-- ── BATCH MODE ── --}}
        <div id="batchScanBox">
            {{-- Phase stepper — pill-style, active lebih besar & bold --}}
            <div id="batchPhaseBar" style="display:flex;gap:.3rem;align-items:center;margin-bottom:1.1rem">
                <span id="bphase1"
                    style="padding:.3rem .9rem;border-radius:999px;background:#6366f1;color:#fff;
                           font-size:.78rem;font-weight:800;box-shadow:0 1px 6px rgba(99,102,241,.35);
                           transition:all .2s">① Scan Item</span>
                <span style="color:#cbd5e1;font-size:.75rem">→</span>
                <span id="bphase2"
                    style="padding:.25rem .75rem;border-radius:999px;background:#f1f5f9;color:#94a3b8;
                           font-size:.73rem;font-weight:700;transition:all .2s">② Scan Order</span>
                <span style="color:#cbd5e1;font-size:.75rem">→</span>
                <span id="bphase3"
                    style="padding:.25rem .75rem;border-radius:999px;background:#f1f5f9;color:#94a3b8;
                           font-size:.73rem;font-weight:700;transition:all .2s">③ Konfirmasi</span>
            </div>

            {{-- Phase 1: Scan Items — accent indigo --}}
            <div id="batchPhase1" style="border-left:3px solid #6366f1;padding-left:1rem">
                <div style="color:#4338ca;font-weight:800;font-size:.88rem;margin-bottom:.2rem">Scan Kode Item (SKU Internal)</div>
                <div style="color:#94a3b8;font-size:.72rem;margin-bottom:.7rem">Ambil item dari gudang → scan barcode atau ketik kode, tekan Enter</div>
                {{-- Focal input: background indigo-tint --}}
                <div style="display:flex;gap:.5rem;align-items:center;background:#f5f3ff;border-radius:12px;padding:.45rem .55rem">
                    <input id="batchItemInput" type="text"
                        placeholder="Contoh: KAO-M-RED"
                        autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                        style="flex:1;background:transparent;border:none;
                               padding:.18rem .3rem;color:#0f172a;font-size:1rem;font-weight:700;font-family:monospace;
                               outline:none;"
                        onkeydown="if(event.key==='Enter')batchAddItem()">
                    <button onclick="batchAddItem()" id="batchItemBtn"
                        style="background:#6366f1;border:none;border-radius:8px;padding:.5rem 1rem;color:#fff;
                               font-weight:700;font-size:.85rem;cursor:pointer;white-space:nowrap;
                               box-shadow:0 1px 6px rgba(99,102,241,.3)">
                        + Tambah
                    </button>
                </div>
                <div id="batchItemResult" style="margin-top:.45rem;font-size:.78rem;min-height:1rem;padding:0 .2rem"></div>

                {{-- List item terscan --}}
                <div id="batchItemList" style="margin-top:.75rem;display:none">
                    <div style="color:#94a3b8;font-size:.67rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;margin-bottom:.4rem">Item Terscan</div>
                    <div id="batchItemRows" style="display:flex;flex-direction:column;gap:.3rem;max-height:150px;overflow-y:auto"></div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1rem">
                    <button onclick="batchResetItems()"
                        style="background:transparent;border:1px solid #e2e8f0;border-radius:999px;
                               padding:.3rem .8rem;color:#94a3b8;font-size:.76rem;font-weight:600;cursor:pointer">
                        🗑 Reset
                    </button>
                    {{-- CTA: disabled = abu terlihat, enabled = hijau mencolok --}}
                    <button id="batchToOrdersBtn" onclick="batchGoToPhase2()" disabled
                        style="background:#e2e8f0;border:none;border-radius:999px;padding:.38rem 1.1rem;
                               color:#94a3b8;font-size:.82rem;font-weight:800;cursor:not-allowed;
                               transition:all .18s">
                        Scan Order →
                    </button>
                </div>
            </div>

            {{-- Phase 2: Scan Orders — accent sky blue --}}
            <div id="batchPhase2" style="display:none;border-left:3px solid #0ea5e9;padding-left:1rem">
                <div style="color:#0369a1;font-weight:800;font-size:.88rem;margin-bottom:.2rem">Scan Nomor Order</div>
                <div style="color:#94a3b8;font-size:.72rem;margin-bottom:.7rem">Scan barcode dari label pengiriman, tekan Enter</div>
                <div style="display:flex;gap:.5rem;align-items:center;background:#f0f9ff;border-radius:12px;padding:.45rem .55rem">
                    <input id="batchOrderInput" type="text"
                        placeholder="Contoh: 260609MDP1J4DQ"
                        autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                        style="flex:1;background:transparent;border:none;
                               padding:.18rem .3rem;color:#0f172a;font-size:1rem;font-weight:700;font-family:monospace;
                               outline:none;"
                        onkeydown="if(event.key==='Enter')batchAddOrder()">
                    <button onclick="batchAddOrder()" id="batchOrderBtn"
                        style="background:#0ea5e9;border:none;border-radius:8px;padding:.5rem 1rem;color:#fff;
                               font-weight:700;font-size:.85rem;cursor:pointer;white-space:nowrap;
                               box-shadow:0 1px 6px rgba(14,165,233,.3)">
                        + Tambah
                    </button>
                </div>
                <div id="batchOrderResult" style="margin-top:.45rem;font-size:.78rem;min-height:1rem;padding:0 .2rem"></div>

                {{-- List order terscan --}}
                <div id="batchOrderList" style="margin-top:.75rem;display:none">
                    <div style="color:#94a3b8;font-size:.67rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;margin-bottom:.4rem">Order Terscan</div>
                    <div id="batchOrderRows" style="display:flex;flex-direction:column;gap:.3rem;max-height:150px;overflow-y:auto"></div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1rem">
                    <button onclick="batchGoToPhase1()"
                        style="background:transparent;border:1px solid #e2e8f0;border-radius:999px;
                               padding:.3rem .8rem;color:#64748b;font-size:.76rem;font-weight:600;cursor:pointer">
                        ← Kembali
                    </button>
                    <button id="batchReconcileBtn" onclick="batchReconcile()" disabled
                        style="background:#e2e8f0;border:none;border-radius:999px;padding:.38rem 1.1rem;
                               color:#94a3b8;font-size:.82rem;font-weight:800;cursor:not-allowed;
                               transition:all .18s">
                        ⚖ Rekonsiliasi →
                    </button>
                </div>
            </div>

            {{-- Phase 3: Rekonsiliasi — accent green --}}
            <div id="batchPhase3" style="display:none;border-left:3px solid #22c55e;padding-left:1rem">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
                    <div style="color:#15803d;font-weight:800;font-size:.88rem">Hasil Rekonsiliasi</div>
                    <button onclick="batchGoToPhase2()"
                        style="background:transparent;border:1px solid #e2e8f0;border-radius:999px;
                               padding:.2rem .65rem;color:#64748b;font-size:.72rem;font-weight:600;cursor:pointer">
                        ← Edit
                    </button>
                </div>
                <div id="batchReconBody" style="max-height:260px;overflow-y:auto;margin-bottom:.9rem"></div>

<div style="display:flex;justify-content:space-between;align-items:center">
                    <button onclick="setScanMode('single')"
                        style="background:transparent;border:1px solid #e2e8f0;border-radius:999px;
                               padding:.3rem .85rem;color:#94a3b8;font-size:.77rem;font-weight:600;cursor:pointer">
                        Batal
                    </button>
                    {{-- CTA utama: paling menonjol di halaman --}}
                    <button id="batchConfirmAllBtn" onclick="batchConfirmAll()"
                        style="background:#16a34a;border:none;border-radius:12px;padding:.55rem 1.5rem;
                               color:#fff;font-size:.9rem;font-weight:800;cursor:pointer;
                               box-shadow:0 3px 12px rgba(22,163,74,.35);transition:all .18s"
                        onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                        ✓ Konfirmasi &amp; Potong Stok
                    </button>
                </div>
            </div>
        </div>

    </div>{{-- /scanBoxPanel --}}

    {{-- ── Tab Perlu Konfirmasi (packed orders) ─────────────────────────────── --}}
    <div id="packedQueueCard" style="display:none;background:#fff;border:1px solid var(--gf-border,#e5e7eb);
         border-radius:22px;padding:1.25rem 1.5rem;margin-bottom:1.25rem;
         box-shadow:0 1px 6px rgba(15,23,42,.07)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
            <div style="display:flex;align-items:center;gap:.6rem">
                <span style="font-size:1.1rem">📋</span>
                <div>
                    <div style="font-weight:800;font-size:.92rem;color:#1e293b">Perlu Konfirmasi</div>
                    <div style="font-size:.72rem;color:#94a3b8">Sudah diproses — stok belum dipotong</div>
                </div>
            </div>
            <span id="packedQueueBadge"
                style="background:#f59e0b;color:#451a03;font-size:.72rem;font-weight:800;
                       padding:.2rem .65rem;border-radius:999px"></span>
        </div>
        <div id="packedQueueRows" style="display:flex;flex-direction:column;gap:.5rem"></div>
        <div style="margin-top:.9rem;padding-top:.9rem;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end">
            <button id="packedConfirmAllBtn" onclick="confirmAllPacked()"
                style="background:#0f766e;border:none;border-radius:12px;padding:.5rem 1.4rem;
                       color:#fff;font-size:.85rem;font-weight:800;cursor:pointer;
                       box-shadow:0 2px 8px rgba(15,118,110,.3);transition:all .18s"
                onmouseover="this.style.background='#0d6460'" onmouseout="this.style.background='#0f766e'">
                ✂ Potong Stok Semua
            </button>
        </div>
    </div>
</div>
</div>

{{-- Review Modal (Perlu Konfirmasi) --}}
<div class="modal fade" id="packedReviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-black" id="packedReviewTitle">Review Order</h5>
                    <div class="text-muted" style="font-size:.8rem" id="packedReviewSub"></div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="packedReviewBody">
                <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
            </div>
            <div class="modal-footer border-0" style="gap:.5rem">
                <button class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Tutup</button>
                <button id="packedReviewConfirmBtn"
                    style="background:#0f766e;border:none;border-radius:999px;padding:.45rem 1.3rem;
                           color:#fff;font-weight:800;font-size:.85rem;cursor:pointer">
                    ✂ Potong Stok
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Fulfillment Detail Modal --}}
<div class="modal fade" id="fulfillModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:20px">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-black" id="fulfillModalTitle">Detail Fulfillment</h5>
                    <div class="text-muted" style="font-size:.8rem" id="fulfillModalSub"></div>
                </div>
                <div style="display:flex;align-items:center;gap:.5rem;">
                    <a id="fulfillHistoryLink" href="#" target="_blank"
                       style="font-size:.72rem;font-weight:700;color:#64748b;text-decoration:none;
                              padding:.2rem .55rem;border:1px solid #e2e8f0;border-radius:999px;
                              display:none;white-space:nowrap;"
                       title="Lihat audit history">
                        📋 History
                    </a>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            {{-- Phase indicator --}}
            <div id="fulfillPhaseBar" style="display:none;padding:.5rem 1.5rem 0">
                <div style="display:flex;gap:.5rem;align-items:center;font-size:.75rem;font-weight:700">
                    <span id="phaseStep1" style="padding:.25rem .75rem;border-radius:999px">① Review</span>
                    <span style="color:#94a3b8">→</span>
                    <span id="phaseStep2" style="padding:.25rem .75rem;border-radius:999px">② Picking</span>
                </div>
            </div>
            <div class="modal-body" id="fulfillModalBody">
                <div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>
            </div>
            <div class="modal-footer border-0">
                <div id="fulfillModalAlert" class="alert d-none w-100 mb-0" style="border-radius:12px;font-size:.85rem"></div>
                <button class="btn btn-light border" style="border-radius:999px" data-bs-dismiss="modal">Tutup</button>
                <button class="btn btn-success fw-bold" style="border-radius:999px" id="fulfillActionBtn">
                    ✓ Konfirmasi & Potong Stok
                </button>
            </div>
        </div>
    </div>
</div>

@include('marketplace._mapping-modal')
@endsection

@push('scripts')
<script>
(function () {
    const { api, esc } = window.mpHelpers;
    let fulfillments = [], currentFulfillId = null, currentPhase = 'review';
    let _singleScanItems = {};        // { itemId: { code, name, qty } } — reset per modal open
    let _currentFulfillmentData = null; // cache fulfillment terakhir untuk local scan matching
    const $ = id => document.getElementById(id);

    // ── Blocker: sembunyikan scan jika ada masalah ───────────────────────────
    let _incompleteCount = 0;

    async function checkAndApplyBlocker() {
        // 1. Cek incomplete data dari issue-summary
        try {
            const s = await api('/api/marketplace/issue-summary');
            _incompleteCount = (s.data_incomplete || 0) + (s.profit_incomplete || 0);
        } catch { _incompleteCount = 0; }

        // 2. Unmapped lines dari fulfillments yang sudah di-load
        const unmappedLines = fulfillments.reduce((n, f) => n + (f.lines_count - f.lines_resolved), 0);

        const isBlocked = (_incompleteCount > 0 || unmappedLines > 0) && !window._devBypassBlocker;

        // Toggle scan box vs blocker card
        $('scanBoxPanel').style.display  = isBlocked ? 'none' : 'block';
        $('blockerCard').style.display   = isBlocked ? 'block' : 'none';

        if (isBlocked) {
            // Bangun daftar masalah di dalam blocker card
            let items = '';
            if (_incompleteCount > 0) {
                items += `<div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem">
                    <span style="color:#fca5a5;font-size:.9rem">•</span>
                    <span style="color:#fecaca;font-size:.83rem;font-weight:600">
                        ${_incompleteCount} order dengan data belum lengkap
                        <span style="font-weight:400;color:#fca5a5"> — harga jual / HPP kosong</span>
                    </span>
                </div>`;
            }
            if (unmappedLines > 0) {
                items += `<div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem">
                    <span style="color:#fca5a5;font-size:.9rem">•</span>
                    <span style="color:#fecaca;font-size:.83rem;font-weight:600">
                        ${unmappedLines} item belum dipetakan ke SKU internal
                        <span style="font-weight:400;color:#fca5a5"> — mapping diperlukan agar stok bisa dipotong</span>
                    </span>
                </div>`;
            }
            $('blockerIssueList').innerHTML = items;
        } else {
            // Aman — fokus ke input yang relevan dengan mode aktif
            setTimeout(() => {
                const inp = _scanMode === 'batch' ? $('batchItemInput') : $('scanInput');
                if (inp) inp.focus();
            }, 150);
        }
    }

    // ── Load data (untuk KPI + blocker check) ────────────────────────────────
    async function loadFulfillments() {
        fulfillments = await api('/api/fulfillments').catch(() => []);
        renderKpi();
        renderPackedQueue();
        await checkAndApplyBlocker();
    }

    // ── Packed queue (Perlu Konfirmasi) ───────────────────────────────────────
    function renderPackedQueue() {
        const packed = fulfillments.filter(f => f.status === 'packed');
        const card   = $('packedQueueCard');
        if (!packed.length) { card.style.display = 'none'; return; }

        card.style.display = 'block';
        $('packedQueueBadge').textContent = packed.length + ' order';

        $('packedQueueRows').innerHTML = packed.map(f => {
            const orderNo  = esc(f.order?.channel_order_id || '#' + f.id);
            const store    = esc(f.order?.store?.name || '—');
            const total    = f.lines_count  || 0;
            const packed_  = f.lines_packed || 0;
            const zero     = f.lines_zero   || 0;
            const unmapped = total - (f.lines_resolved || 0);

            // Hitung berapa line bermasalah
            const issueCount = (f.has_shortage ? 1 : 0) + zero + unmapped;
            const allOk      = issueCount === 0 && packed_ === total;

            // Badge indikator
            let badges = '';
            const carrier = (f.order?.shipping_carrier || '').toLowerCase();
            if (carrier.includes('instant') || carrier.includes('same day') || carrier.includes('sameday')) {
                badges += `<span style="background:#fef08a;color:#854d0e;font-size:.65rem;font-weight:800;padding:.1rem .4rem;border-radius:999px;border:1px solid #fde047;white-space:nowrap;margin-right:4px;">⚡ KILAT</span>`;
            }
            if (unmapped > 0) {
                badges += `<span style="background:#fee2e2;color:#b91c1c;font-size:.65rem;font-weight:800;
                                        padding:.1rem .4rem;border-radius:999px;white-space:nowrap">
                            ✗ ${unmapped} blm mapping</span>`;
            }
            if (zero > 0) {
                badges += `<span style="background:#fee2e2;color:#b91c1c;font-size:.65rem;font-weight:800;
                                        padding:.1rem .4rem;border-radius:999px;white-space:nowrap">
                            ✗ ${zero} tdk dipacking</span>`;
            }
            if (f.has_shortage && zero === 0) {
                badges += `<span style="background:#fef3c7;color:#92400e;font-size:.65rem;font-weight:800;
                                        padding:.1rem .4rem;border-radius:999px;white-space:nowrap">
                            ⚠ kurang</span>`;
            }

            // Warna border card
            const borderColor = unmapped > 0 || zero > 0
                ? 'rgba(239,68,68,.3)'
                : f.has_shortage ? 'rgba(245,158,11,.35)' : '#e2e8f0';
            const bg = unmapped > 0 || zero > 0
                ? 'rgba(239,68,68,.03)'
                : f.has_shortage ? 'rgba(245,158,11,.03)' : '#f8fafc';

            return `
            <div style="display:flex;align-items:center;gap:.6rem;background:${bg};
                        border:1.5px solid ${borderColor};border-radius:12px;padding:.6rem .9rem">
                <div style="flex:1;min-width:0">
                    <div style="display:flex;align-items:center;gap:.4rem;flex-wrap:wrap">
                        <span style="font-weight:800;font-size:.85rem;color:#0f172a;font-family:monospace">${orderNo}</span>
                        ${badges}
                    </div>
                    <div style="font-size:.72rem;color:#64748b;margin-top:.15rem">
                        ${store}
                        &nbsp;·&nbsp;
                        <span style="font-weight:700;color:${allOk ? '#16a34a' : '#d97706'}">
                            ${packed_}/${total} dipacking
                        </span>
                    </div>
                </div>
                <button onclick="reviewPacked(${f.id})"
                    style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;padding:.3rem .85rem;
                           color:#475569;font-size:.75rem;font-weight:700;cursor:pointer;white-space:nowrap">
                    🔍 Review
                </button>
                <button onclick="confirmOnePacked(${f.id}, this)"
                    style="background:#0f766e;border:none;border-radius:999px;padding:.3rem .9rem;
                           color:#fff;font-size:.75rem;font-weight:800;cursor:pointer;white-space:nowrap;
                           box-shadow:0 1px 4px rgba(15,118,110,.25)">
                    ✂ Potong Stok
                </button>
            </div>`;
        }).join('');
    }

    window.confirmOnePacked = async function (fulfillmentId, btn) {
        btn = btn || event.target;
        const origText  = btn.textContent;
        btn.disabled    = true;
        btn.textContent = '…';
        try {
            await api(`/api/fulfillments/${fulfillmentId}/confirm-packed`, { method: 'POST' });
            // Tutup review modal jika sedang buka untuk order yang sama
            const reviewModal = bootstrap.Modal.getInstance($('packedReviewModal'));
            if (reviewModal) reviewModal.hide();
            await loadFulfillments();
        } catch (e) {
            btn.disabled    = false;
            btn.textContent = origText;
            alert(e.message);
        }
    };

    // ── Review modal: tampilkan perbandingan order vs scan ────────────────────
    let _reviewFulfillmentId = null;

    window.reviewPacked = async function (fulfillmentId) {
        _reviewFulfillmentId = fulfillmentId;
        const titleEl  = $('packedReviewTitle');
        const subEl    = $('packedReviewSub');
        const bodyEl   = $('packedReviewBody');
        const confirmBtn = $('packedReviewConfirmBtn');

        titleEl.textContent  = 'Review Order';
        subEl.textContent    = 'Memuat…';
        bodyEl.innerHTML     = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        confirmBtn.disabled  = false;
        confirmBtn.textContent = '✂ Potong Stok';

        new bootstrap.Modal($('packedReviewModal')).show();

        try {
            const f = await api('/api/fulfillments/' + fulfillmentId);
            titleEl.textContent = f.order?.channel_order_id || '#' + f.id;
            subEl.textContent   = `${f.order?.store?.name || ''} · ${f.order?.store?.channel || ''}`;

            const lines = (f.lines || []).filter(l => !l.is_split_parent);

            // ── Section 1: Data Pesanan ──────────────────────────────────────
            const orderRows = lines.map(l => {
                const ordered   = l.qty_ordered  || 0;
                const fulfilled = l.qty_fulfilled || 0;
                const isOk      = fulfilled >= ordered;
                const isZero    = fulfilled === 0;

                const statusBadge = isZero
                    ? `<span style="background:#fee2e2;color:#b91c1c;font-size:.65rem;font-weight:800;padding:.1rem .4rem;border-radius:999px">✗ Tdk dipacking</span>`
                    : !isOk
                    ? `<span style="background:#fef3c7;color:#92400e;font-size:.65rem;font-weight:800;padding:.1rem .4rem;border-radius:999px">⚠ Kurang</span>`
                    : `<span style="background:#dcfce7;color:#166534;font-size:.65rem;font-weight:800;padding:.1rem .4rem;border-radius:999px">✓ OK</span>`;

                const rowBg      = isZero ? 'rgba(239,68,68,.04)' : !isOk ? 'rgba(245,158,11,.04)' : '#fff';
                const borderClr  = isZero ? 'rgba(239,68,68,.15)' : !isOk ? 'rgba(245,158,11,.15)' : '#f1f5f9';

                return `<tr style="background:${rowBg}">
                    <td style="padding:.5rem .65rem;border-bottom:1px solid ${borderClr}">
                        <code style="font-size:.78rem;color:#4338ca">${esc(l.item?.code || l.marketplace_sku || '—')}</code>
                        <div style="font-size:.68rem;color:#94a3b8;margin-top:.08rem">${esc(l.marketplace_item_name || l.item?.name || '—')}</div>
                    </td>
                    <td style="padding:.5rem .65rem;border-bottom:1px solid ${borderClr};text-align:center;font-weight:700;font-size:.82rem;color:#64748b">${ordered}</td>
                    <td style="padding:.5rem .65rem;border-bottom:1px solid ${borderClr};text-align:center;font-weight:800;font-size:.88rem;color:${isZero?'#dc2626':isOk?'#16a34a':'#d97706'}">${fulfilled}</td>
                    <td style="padding:.5rem .65rem;border-bottom:1px solid ${borderClr};text-align:right">${statusBadge}</td>
                </tr>`;
            }).join('');

            // ── Section 2: Item Terscan ──────────────────────────────────────
            // Gunakan scan_log (raw scan) kalau ada, fallback ke lines qty_fulfilled > 0
            let scannedItems = [];
            if (f.scan_log && f.scan_log.length) {
                scannedItems = f.scan_log; // [{item_id, qty, code, name}]
            } else {
                // Fallback: derive dari lines yang punya qty_fulfilled > 0
                // Group by item_id supaya tidak duplikat (split lines dll)
                const grouped = {};
                lines.filter(l => (l.qty_fulfilled || 0) > 0).forEach(l => {
                    const key = l.item?.id || l.item_id || l.marketplace_sku;
                    if (!key) return;
                    if (grouped[key]) {
                        grouped[key].qty += l.qty_fulfilled;
                    } else {
                        grouped[key] = {
                            code: l.item?.code || l.marketplace_sku || '—',
                            name: l.item?.name || l.marketplace_item_name || '—',
                            qty:  l.qty_fulfilled,
                        };
                    }
                });
                scannedItems = Object.values(grouped);
            }

            const scannedRows = scannedItems.map(s => `
                <div style="display:flex;align-items:center;gap:.6rem;background:#f8fafc;
                            border:1px solid #e2e8f0;border-radius:9px;padding:.4rem .7rem">
                    <span style="font-size:.72rem;font-weight:800;color:#4338ca;font-family:monospace;flex-shrink:0">
                        ${esc(s.code || '—')}
                    </span>
                    <span style="font-size:.75rem;color:#64748b;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        ${esc(s.name || '—')}
                    </span>
                    <span style="font-size:.82rem;font-weight:800;color:#0f172a;flex-shrink:0">×${s.qty}</span>
                </div>`).join('');

            bodyEl.innerHTML = `
            <div style="margin-bottom:1rem">
                <div style="font-size:.7rem;font-weight:800;color:#94a3b8;letter-spacing:.05em;
                            text-transform:uppercase;margin-bottom:.5rem">📋 Data Pesanan</div>
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <thead>
                        <tr style="background:#f8fafc">
                            <th style="padding:.4rem .65rem;font-size:.68rem;font-weight:700;color:#94a3b8;text-align:left;border-bottom:2px solid #e2e8f0">ITEM</th>
                            <th style="padding:.4rem .65rem;font-size:.68rem;font-weight:700;color:#94a3b8;text-align:center;border-bottom:2px solid #e2e8f0">DIPESAN</th>
                            <th style="padding:.4rem .65rem;font-size:.68rem;font-weight:700;color:#94a3b8;text-align:center;border-bottom:2px solid #e2e8f0">DI-PACK</th>
                            <th style="padding:.4rem .65rem;font-size:.68rem;font-weight:700;color:#94a3b8;text-align:right;border-bottom:2px solid #e2e8f0">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>${orderRows}</tbody>
                </table>
            </div>

            <div>
                <div style="font-size:.7rem;font-weight:800;color:#94a3b8;letter-spacing:.05em;
                            text-transform:uppercase;margin-bottom:.5rem">
                    📦 Item Terscan
                    ${scannedItems.length
                        ? `<span style="font-weight:700;color:#0f172a;font-size:.75rem;letter-spacing:0;text-transform:none;margin-left:.4rem">${scannedItems.length} item</span>`
                        : ''}
                </div>
                ${scannedRows
                    ? `<div style="display:flex;flex-direction:column;gap:.3rem;max-height:200px;overflow-y:auto">${scannedRows}</div>`
                    : `<div style="font-size:.8rem;color:#94a3b8;font-style:italic;padding:.5rem 0">Tidak ada item terscan.</div>`
                }
            </div>`;
        } catch (e) {
            bodyEl.innerHTML = `<div class="oc-empty text-danger">Gagal memuat: ${esc(e.message)}</div>`;
        }
    };

    // Tombol Potong Stok di dalam review modal
    $('packedReviewConfirmBtn').addEventListener('click', async () => {
        if (!_reviewFulfillmentId) return;
        const btn = $('packedReviewConfirmBtn');
        await confirmOnePacked(_reviewFulfillmentId, btn);
    });

    window.confirmAllPacked = async function () {
        const btn = $('packedConfirmAllBtn');
        btn.disabled    = true;
        btn.textContent = 'Memproses…';
        const packed = fulfillments.filter(f => f.status === 'packed');
        try {
            for (const f of packed) {
                await api(`/api/fulfillments/${f.id}/confirm-packed`, { method: 'POST' });
            }
            await loadFulfillments();
        } catch (e) {
            alert(e.message);
        } finally {
            btn.disabled    = false;
            btn.textContent = '✂ Potong Stok Semua';
        }
    };

    async function renderKpi() {
        try {
            const s = await api('/api/fulfillments/batch-stats');
            $('kpiDoneToday').textContent  = s.selesai_hari_ini          ?? '—';
            $('kpiItemsToday').textContent = s.item_diproses_hari_ini    ?? '—';
            $('kpiWaiting').textContent    = s.menunggu_diproses         ?? '—';
            $('kpiUnmapped').textContent   = s.belum_mapping             ?? '—';
        } catch {
            // jika gagal, biarkan "—"
        }
    }


    // ── Open modal ────────────────────────────────────────────────────────────
    window.openFulfillment = async function (id) {
        currentFulfillId = id;
        $('fulfillModalTitle').textContent = 'Fulfillment #' + id;
        $('fulfillModalSub').textContent   = 'Memuat…';
        $('fulfillModalBody').innerHTML    = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        $('fulfillModalAlert').className   = 'alert d-none w-100 mb-0';
        $('fulfillPhaseBar').style.display = 'none';
        const actionBtn = $('fulfillActionBtn');
        actionBtn.disabled    = false;
        actionBtn.textContent = '✓ Konfirmasi & Potong Stok';
        new bootstrap.Modal($('fulfillModal')).show();
        const f = await api('/api/fulfillments/' + id).catch(() => null);
        if (!f) { $('fulfillModalBody').innerHTML = '<div class="oc-empty text-danger">Gagal memuat data.</div>'; return; }
        renderFulfillDetail(f);
    };

    // Auto-focus input scan saat modal fully shown
    $('fulfillModal').addEventListener('shown.bs.modal', () => {
        const inp = $('singleItemScanInput');
        if (inp) inp.focus();
    });

    function renderFulfillDetail(f) {
        _currentFulfillmentData = f;
        $('fulfillModalTitle').textContent = 'Fulfillment — ' + (f.order?.channel_order_id || '#' + f.id);
        $('fulfillModalSub').textContent   = `${f.order?.store?.name} · ${f.order?.store?.channel} · ${f.warehouse?.name || 'Belum ada gudang'}`;

        // History link
        const histLink = $('fulfillHistoryLink');
        histLink.href  = `/marketplace/fulfillment/${f.id}/history`;
        histLink.style.display = 'inline-block';

        // Phase bar disembunyikan — single mode langsung packing
        $('fulfillPhaseBar').style.display = 'none';

        // Langsung tampilkan scan item, skip picking phase
        renderReviewPhase(f);
    }

    // ── Phase 1: Review (Single Mode — UI sama persis dengan Batch Phase 1) ───
    function renderReviewPhase(f) {
        currentPhase = 'review';
        _singleScanItems = {};

        const actionBtn   = $('fulfillActionBtn');
        const lines       = f.lines || [];
        const allResolved = lines.every(l => l.item);
        actionBtn.disabled         = !allResolved;
        actionBtn.textContent      = '📦 Proses';
        actionBtn.style.background = allResolved ? '#0f766e' : '#94a3b8';
        actionBtn.style.borderColor = 'transparent';

        const unmapped = lines.filter(l => !l.item).length;

        $('fulfillModalBody').innerHTML = `
        <div style="border-left:3px solid #6366f1;padding-left:1rem">
            <div style="color:#4338ca;font-weight:800;font-size:.88rem;margin-bottom:.2rem">Scan Kode Item (SKU Internal)</div>
            <div style="color:#94a3b8;font-size:.72rem;margin-bottom:.7rem">Ambil item dari gudang → scan barcode atau ketik kode, tekan Enter</div>

            ${unmapped > 0 ? `
            <div style="background:rgba(239,68,68,.08);border:1.5px solid rgba(239,68,68,.25);border-radius:10px;
                        padding:.55rem 1rem;font-size:.78rem;color:#b91c1c;margin-bottom:.75rem">
                ✗ ${unmapped} item belum dipetakan — konfirmasi diblokir
            </div>` : ''}

            <div style="display:flex;gap:.5rem;align-items:center;background:#f5f3ff;border-radius:12px;padding:.45rem .55rem">
                <input id="singleItemScanInput" type="text" placeholder="Contoh: KAO-M-RED"
                    autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                    style="flex:1;background:transparent;border:none;
                           padding:.18rem .3rem;color:#0f172a;font-size:1rem;font-weight:700;font-family:monospace;
                           outline:none;"
                    onkeydown="if(event.key==='Enter')singleScanAddItem()">
                <button onclick="singleScanAddItem()" id="singleItemScanBtn"
                    style="background:#6366f1;border:none;border-radius:8px;padding:.5rem 1rem;color:#fff;
                           font-weight:700;font-size:.85rem;cursor:pointer;white-space:nowrap;
                           box-shadow:0 1px 6px rgba(99,102,241,.3)">
                    + Tambah
                </button>
            </div>
            <div id="singleItemScanResult" style="margin-top:.45rem;font-size:.78rem;min-height:1rem;padding:0 .2rem"></div>

            <div id="singleItemList" style="margin-top:.75rem;display:none">
                <div style="color:#94a3b8;font-size:.67rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;margin-bottom:.4rem">Item Terscan</div>
                <div id="singleItemRows" style="display:flex;flex-direction:column;gap:.3rem;max-height:150px;overflow-y:auto"></div>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1rem">
                <button onclick="singleScanReset()"
                    style="background:transparent;border:1px solid #e2e8f0;border-radius:999px;
                           padding:.3rem .8rem;color:#94a3b8;font-size:.76rem;font-weight:600;cursor:pointer">
                    🗑 Reset
                </button>
            </div>
        </div>`;

        const inp = $('singleItemScanInput');
        if (inp) inp.focus();
    }

    function renderSingleItemRows() {
        const rows = $('singleItemRows');
        const list = $('singleItemList');
        if (!rows || !list) return;
        const items = Object.values(_singleScanItems);
        if (!items.length) { list.style.display = 'none'; return; }
        list.style.display = 'block';
        rows.innerHTML = items.map(it => `
            <div class="sr-item-row">
                <div style="flex:1; min-width:0">
                    <div class="sr-item-code">${esc(it.code)}</div>
                    <div class="sr-item-name" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(it.name)}</div>
                </div>
                <div style="display:flex;align-items:center;gap:.3rem;flex-shrink:0">
                    <button onclick="singleScanDec(${it.id})"
                        style="background:#e2e8f0;border:none;border-radius:5px;width:22px;height:22px;color:#475569;font-weight:800;cursor:pointer;font-size:.8rem;line-height:1">−</button>
                    <span class="sr-item-qty" style="min-width:20px;text-align:center">${it.qty}</span>
                    <button onclick="singleScanInc(${it.id})"
                        style="background:#e2e8f0;border:none;border-radius:5px;width:22px;height:22px;color:#475569;font-weight:800;cursor:pointer;font-size:.8rem;line-height:1">+</button>
                    <button onclick="singleScanRemove(${it.id})"
                        style="background:#fee2e2;border:none;border-radius:5px;width:22px;height:22px;color:#ef4444;font-weight:800;cursor:pointer;font-size:.8rem;line-height:1;margin-left:.2rem">✕</button>
                </div>
            </div>`).join('');
    }

    window.singleScanAddItem = async function singleScanAddItem() {
        const input    = $('singleItemScanInput');
        const resultEl = $('singleItemScanResult');
        if (!input || !resultEl) return;
        const q = input.value.trim();
        if (!q) return;

        // ── 1. Coba match lokal dari lines order yang sudah dimuat (instant) ──
        const lines = (_currentFulfillmentData?.lines || []).filter(l => l.item);
        const localMatch = lines.find(l =>
            l.item.code?.toLowerCase() === q.toLowerCase() ||
            l.marketplace_sku?.toLowerCase() === q.toLowerCase()
        );

        if (localMatch) {
            const it = localMatch.item;
            if (_singleScanItems[it.id]) {
                _singleScanItems[it.id].qty++;
            } else {
                _singleScanItems[it.id] = { id: it.id, code: it.code, name: it.name, qty: 1 };
            }
            input.value = '';
            renderSingleItemRows();
            resultEl.innerHTML = `<span style="color:#10b981">✓ ${esc(it.code)}</span>`;
            setTimeout(() => { if (resultEl) resultEl.innerHTML = ''; }, 800);
            input.focus();
            return;
        }

        // ── 2. Fallback ke API (item tidak ada di lines order) ────────────────
        resultEl.innerHTML = '<span style="color:#94a3b8">Mencari…</span>';
        try {
            const list = await api(`/api/marketplace/items/search?q=${encodeURIComponent(q)}&limit=5`);
            if (!list.length) {
                resultEl.innerHTML = '<span style="color:#f87171">Item tidak ditemukan.</span>';
                input.focus();
                return;
            }
            const exact = list.find(i => i.code?.toLowerCase() === q.toLowerCase()) || list[0];
            const id = exact.id;
            if (_singleScanItems[id]) {
                _singleScanItems[id].qty++;
            } else {
                _singleScanItems[id] = { id, code: exact.code, name: exact.name, qty: 1 };
            }
            input.value = '';
            renderSingleItemRows();
            resultEl.innerHTML = `<span style="color:#10b981">✓ ${esc(exact.code)}</span>`;
            setTimeout(() => { if (resultEl) resultEl.innerHTML = ''; }, 800);
        } catch {
            resultEl.innerHTML = '<span style="color:#f87171">Gagal menghubungi server.</span>';
        }
        input.focus();
    }

    window.singleScanInc = function (id) {
        if (_singleScanItems[id]) { _singleScanItems[id].qty++; renderSingleItemRows(); }
    };
    window.singleScanDec = function (id) {
        if (_singleScanItems[id]) {
            _singleScanItems[id].qty--;
            if (_singleScanItems[id].qty <= 0) delete _singleScanItems[id];
            renderSingleItemRows();
        }
    };
    window.singleScanRemove = function (id) {
        delete _singleScanItems[id];
        renderSingleItemRows();
    };
    window.singleScanReset = function () {
        _singleScanItems = {};
        renderSingleItemRows();
        const inp = $('singleItemScanInput');
        if (inp) { inp.value = ''; inp.focus(); }
    };

    function renderReviewLine(l) {
        const statusMap = { ok: 'oc-badge-green', low: 'oc-badge-amber', empty: 'oc-badge-red', unresolved: 'oc-badge-red' };
        const statusLbl = { ok: 'Cukup', low: 'Kurang', empty: 'Habis', unresolved: '—' };

        const itemCell = l.item
            ? `<span class="fw-bold">${esc(l.item.code)}</span><br><span class="text-muted" style="font-size:.72rem">${esc(l.item.name)}</span>
               ${l.substituted ? '<span class="oc-badge oc-badge-amber ms-1">Diganti</span>' : ''}`
            : `<div style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap">
                 <input id="mapInput-${l.id}" type="text" placeholder="Ketik SKU internal…"
                   autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                   style="border:1.5px solid #e2e8f0;border-radius:8px;padding:.3rem .6rem;font-size:.8rem;font-family:monospace;width:160px;outline:none"
                   onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'"
                   onkeydown="if(event.key==='Enter'){event.preventDefault();searchAndMapItem(${l.id})}">
                 <button onclick="searchAndMapItem(${l.id})" style="background:#6366f1;border:none;color:#fff;border-radius:8px;padding:.3rem .7rem;font-size:.75rem;font-weight:700;cursor:pointer">Pakai</button>
               </div>
               <div id="mapResults-${l.id}" style="font-size:.75rem;margin-top:.25rem"></div>`;

        const stokCell = l.item
            ? `<span class="oc-badge ${statusMap[l.stock_status]||'oc-badge-muted'}">${statusLbl[l.stock_status]||l.stock_status}</span>
               <div class="text-muted" style="font-size:.7rem">${l.stock_available} tersedia</div>`
            : `<span class="oc-badge oc-badge-red">Belum Mapped</span>`;

        return `<tr id="fline-${l.id}">
            <td><code style="font-size:.78rem">${esc(l.marketplace_sku||'—')}</code></td>
            <td style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(l.marketplace_item_name||'—')}</td>
            <td>${itemCell}</td>
            <td class="text-center fw-bold">${l.qty_ordered}</td>
            <td class="text-center">
                ${l.item
                    ? `<input type="number" class="form-control form-control-sm text-center"
                           style="width:70px;border-radius:8px;display:inline-block"
                           value="${l.qty_fulfilled}" min="0" max="${l.qty_ordered}"
                           onchange="updateQty(${l.id}, this.value)">`
                    : `<span class="text-muted">—</span>`
                }
            </td>
            <td>${stokCell}</td>
            <td>
                ${l.item
                    ? `<button class="btn btn-light border btn-sm" style="border-radius:999px;font-size:.72rem"
                           onclick="editLine(${l.id},'${esc(l.marketplace_sku||'')}')">Edit</button>`
                    : ''
                }
            </td>
        </tr>`;
    }

    // Inline item search + map
    window.searchAndMapItem = async function (lineId) {
        const input = $('mapInput-' + lineId);
        const resultsEl = $('mapResults-' + lineId);
        if (!input) return;
        const q = input.value.trim();
        if (!q) { resultsEl.innerHTML = '<span style="color:#f87171">Ketik SKU dulu.</span>'; return; }

        resultsEl.innerHTML = '<span style="color:#94a3b8">Mencari…</span>';
        try {
            const list = await api(`/api/marketplace/items/search?q=${encodeURIComponent(q)}&limit=5`);
            if (!list.length) { resultsEl.innerHTML = '<span style="color:#f87171">Tidak ditemukan.</span>'; return; }

            // Exact match first, else show dropdown
            const exact = list.find(i => i.code?.toLowerCase() === q.toLowerCase());
            if (exact) {
                await applyItemMapping(lineId, exact.id, exact.code);
                return;
            }

            // Show choices
            resultsEl.innerHTML = list.map(i =>
                `<button onclick="applyItemMapping(${lineId}, ${i.id}, '${esc(i.code)}')"
                    style="display:block;width:100%;text-align:left;background:#f8fafc;border:1px solid #e2e8f0;
                           border-radius:6px;padding:.25rem .6rem;font-size:.75rem;margin-bottom:.2rem;cursor:pointer">
                    <strong>${esc(i.code)}</strong> — ${esc(i.name)}
                </button>`
            ).join('');
        } catch {
            resultsEl.innerHTML = '<span style="color:#f87171">Gagal menghubungi server.</span>';
        }
    };

    window.applyItemMapping = async function (lineId, itemId, itemCode) {
        const resultsEl = $('mapResults-' + lineId);
        if (resultsEl) resultsEl.innerHTML = '<span style="color:#94a3b8">Menyimpan…</span>';
        try {
            await api(`/api/fulfillments/${currentFulfillId}/lines/${lineId}`, {
                method: 'PATCH',
                body: JSON.stringify({ item_id: itemId }),
            });
            // Reload modal
            const f = await api('/api/fulfillments/' + currentFulfillId);
            renderFulfillDetail(f);
        } catch (e) {
            if (resultsEl) resultsEl.innerHTML = `<span style="color:#f87171">${e.message||'Gagal'}</span>`;
        }
    };

    // ── Phase 2: Picking ──────────────────────────────────────────────────────
    function renderPickingPhase(f) {
        currentPhase = 'picking';
        const allLines     = f.lines || [];
        // Hanya hitung active lines (bukan split parent)
        const activeLines  = allLines.filter(l => !l.is_split_parent);
        const pickedCount  = activeLines.filter(l => l.is_picked).length;
        const problemCount = activeLines.filter(l => l.has_problem).length;
        const allDone      = activeLines.length > 0 && pickedCount === activeLines.length && problemCount === 0;

        const actionBtn = $('fulfillActionBtn');
        actionBtn.disabled    = !allDone;
        actionBtn.textContent = '✓ Selesai Picking';
        actionBtn.style.background = allDone ? '#0f766e' : '#94a3b8';

        // Kelompokkan: split parents beserta children-nya, lalu normal lines
        const parentMap = {};  // parentId → [children]
        const splitParents = [];
        const normalLines  = [];
        allLines.forEach(l => {
            if (l.is_split_parent) { splitParents.push(l); parentMap[l.id] = []; }
        });
        allLines.forEach(l => {
            if (l.split_parent_id) { if (parentMap[l.split_parent_id]) parentMap[l.split_parent_id].push(l); }
            else if (!l.is_split_parent) normalLines.push(l);
        });

        let linesHtml = '';
        // Split groups dulu
        splitParents.forEach(p => {
            linesHtml += renderSplitParentLine(p, parentMap[p.id] || []);
        });
        // Normal lines
        normalLines.forEach((l, idx) => { linesHtml += renderPickingLine(l, idx); });

        $('fulfillModalBody').innerHTML = `
        <div style="margin-bottom:.75rem;display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
            <div style="font-weight:700;font-size:.9rem">Picking Checklist</div>
            <span class="oc-badge ${allDone ? 'oc-badge-green' : 'oc-badge-blue'}" style="font-size:.78rem">
                ${pickedCount}/${activeLines.length} item dipick
            </span>
            ${problemCount > 0 ? `<span class="oc-badge oc-badge-red" style="font-size:.78rem">${problemCount} masalah</span>` : ''}
        </div>
        <div id="pickingLines">${linesHtml}</div>`;
    }

    // Render split-parent row: archived badge + Restore button, then children indented
    function renderSplitParentLine(p, children) {
        const itemCode = esc(p.item?.code || p.marketplace_sku || '—');
        const fullName = esc(p.item?.name || p.marketplace_item_name || '—');
        let html = `<div id="pickLine-${p.id}" style="margin-bottom:.5rem">
            <div style="background:#fafafa;border:1.5px dashed #e2e8f0;border-radius:12px;padding:.55rem .9rem;
                        display:flex;align-items:center;gap:.6rem;opacity:.7">
                <span style="font-size:.85rem;color:#94a3b8;flex-shrink:0">▤</span>
                <div style="flex:1;min-width:0">
                    <span style="font-weight:700;font-size:.85rem;color:#64748b">${itemCode}</span>
                    <span style="font-size:.72rem;background:#e0e7ff;color:#4338ca;padding:.1rem .45rem;border-radius:999px;font-weight:700;margin-left:.35rem">SPLIT</span>
                    <div style="font-size:.72rem;color:#94a3b8;margin-top:.08rem">${fullName} &nbsp;×${p.qty_ordered}</div>
                </div>
                <button onclick="restoreSplitLine(${currentFulfillId},${p.id})"
                    style="background:#ede9fe;border:1px solid #c4b5fd;color:#6d28d9;border-radius:999px;padding:.25rem .7rem;font-size:.7rem;font-weight:700;cursor:pointer;white-space:nowrap">
                    ↩ Restore
                </button>
            </div>
            <div style="margin-left:1.5rem;border-left:2px solid #e0e7ff;padding-left:.6rem;margin-top:.2rem">
                ${children.map((c, i) => renderPickingLine(c, i, true)).join('')}
            </div>
        </div>`;
        return html;
    }

    function renderPickingLine(l, idx, isSplitChild = false) {
        const isPicked = l.is_picked;
        const hasProb  = l.has_problem;
        const isSub    = l.substituted;

        let rowBg = '';
        if (hasProb)       rowBg = 'background:rgba(239,68,68,.05);border:1.5px solid rgba(239,68,68,.25);';
        else if (isPicked) rowBg = 'background:rgba(16,185,129,.05);border:1.5px solid rgba(16,185,129,.25);';
        else               rowBg = 'background:#f8fafc;border:1.5px solid #e2e8f0;';

        const icon      = hasProb ? '🚩' : isPicked ? '☑' : '☐';
        const iconColor = hasProb ? '#ef4444' : isPicked ? '#10b981' : '#94a3b8';
        const fullName  = esc(l.item?.name || l.marketplace_item_name || '—');
        const itemCode  = esc(l.item?.code || l.marketplace_sku || '—');

        const splitChildBadge = isSplitChild
            ? '<span style="font-size:.65rem;background:#ede9fe;color:#6d28d9;padding:.08rem .4rem;border-radius:999px;font-weight:700;margin-left:.3rem">↳ split</span>'
            : '';

        // Tombol aksi
        let actionBtns = '';
        if (hasProb) {
            actionBtns = `<button onclick="toggleGantiItem(${l.id})"
                style="background:#f59e0b;border:none;color:#fff;border-radius:999px;padding:.28rem .7rem;font-size:.72rem;font-weight:700;cursor:pointer">🔄 Ganti</button>`;
        } else if (!isPicked) {
            actionBtns = `
                <button onclick="toggleGantiItem(${l.id})"
                    style="background:#e0f2fe;border:1px solid #7dd3fc;color:#0369a1;border-radius:999px;padding:.28rem .65rem;font-size:.72rem;font-weight:600;cursor:pointer">🔄 Ganti</button>
                ${!isSplitChild ? `<button onclick="toggleSplitPanel(${l.id},${l.qty_ordered})"
                    style="background:#ede9fe;border:1px solid #c4b5fd;color:#6d28d9;border-radius:999px;padding:.28rem .65rem;font-size:.72rem;font-weight:600;cursor:pointer">✂ Split</button>` : ''}
                <button onclick="flagPickProblem(${l.id})"
                    style="background:#fee2e2;border:1px solid #fca5a5;color:#ef4444;border-radius:999px;padding:.28rem .6rem;font-size:.72rem;font-weight:600;cursor:pointer">🚩</button>`;
        } else {
            actionBtns = `<button onclick="togglePickLine(${l.id})"
                style="background:none;border:1px solid #d1fae5;color:#6ee7b7;border-radius:999px;padding:.2rem .55rem;font-size:.7rem;cursor:pointer" title="Un-pick">↩</button>`;
        }

        return `<div id="pickLine-${l.id}">
            <div style="${rowBg}border-radius:12px;padding:.65rem 1rem;margin-bottom:.25rem;display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
                <button onclick="togglePickLine(${l.id})"
                    style="font-size:1.5rem;line-height:1;background:none;border:none;cursor:pointer;color:${iconColor};padding:0;flex-shrink:0"
                    title="${isPicked ? 'Un-pick' : 'Tandai sudah dipick'}">${icon}</button>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:800;font-size:.9rem;color:#0f172a;letter-spacing:-.01em">${itemCode}
                        ${isSub ? '<span style="font-size:.7rem;background:#fef3c7;color:#92400e;padding:.1rem .45rem;border-radius:999px;font-weight:700;margin-left:.3rem">DIGANTI</span>' : ''}
                        ${splitChildBadge}
                    </div>
                    <div style="font-size:.78rem;color:#475569;margin-top:.1rem">${fullName}</div>
                    ${hasProb ? `<div style="font-size:.75rem;color:#ef4444;font-weight:600;margin-top:.2rem">⚠ ${esc(l.pick_problem)}</div>` : ''}
                </div>
                <div style="display:flex;align-items:center;gap:.35rem;flex-shrink:0;flex-wrap:wrap;justify-content:flex-end">
                    <span style="font-weight:800;font-size:1rem;color:#0f172a;min-width:2.5rem;text-align:right">×${l.qty_fulfilled || l.qty_ordered}</span>
                    ${actionBtns}
                </div>
            </div>
            {{-- Ganti Item panel --}}
            <div id="gantiPanel-${l.id}" style="display:none;background:#f0f9ff;border:1.5px solid #7dd3fc;border-radius:0 0 12px 12px;padding:.75rem 1rem;margin-top:-.25rem;margin-bottom:.25rem">
                <div style="font-size:.78rem;font-weight:700;color:#0369a1;margin-bottom:.5rem">🔄 Ganti Item — stok akan disinkronkan otomatis</div>
                <div style="display:flex;gap:.5rem;align-items:flex-start;flex-wrap:wrap">
                    <div style="flex:1;min-width:180px">
                        <input id="gantiInput-${l.id}" type="text" placeholder="Ketik SKU pengganti…"
                            autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                            style="width:100%;border:1.5px solid #bae6fd;border-radius:8px;padding:.35rem .65rem;font-size:.82rem;font-family:monospace;outline:none"
                            onfocus="this.style.borderColor='#0369a1'" onblur="this.style.borderColor='#bae6fd'"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();searchGantiItem(${l.id})}">
                        <div id="gantiResults-${l.id}" style="margin-top:.3rem;font-size:.75rem"></div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:.3rem;min-width:90px">
                        <label style="font-size:.72rem;color:#0369a1;font-weight:600">Qty kirim</label>
                        <input id="gantiQty-${l.id}" type="number" min="1" value="${l.qty_ordered}"
                            style="border:1.5px solid #bae6fd;border-radius:8px;padding:.35rem .5rem;font-size:.85rem;font-weight:700;width:80px;outline:none;text-align:center"
                            onfocus="this.style.borderColor='#0369a1'" onblur="this.style.borderColor='#bae6fd'">
                    </div>
                    <div style="display:flex;gap:.3rem;align-self:flex-end;padding-bottom:2px">
                        <button onclick="searchGantiItem(${l.id})"
                            style="background:#0369a1;border:none;color:#fff;border-radius:8px;padding:.38rem .8rem;font-size:.78rem;font-weight:700;cursor:pointer">Cari</button>
                        <button onclick="toggleGantiItem(${l.id})"
                            style="background:#e2e8f0;border:none;color:#64748b;border-radius:8px;padding:.38rem .7rem;font-size:.78rem;cursor:pointer">Batal</button>
                    </div>
                </div>
                <div id="gantiConfirm-${l.id}" style="display:none;margin-top:.6rem;padding:.55rem .75rem;background:#fff;border:1.5px solid #0369a1;border-radius:10px">
                    <div id="gantiConfirmText-${l.id}" style="font-size:.8rem;color:#0f172a;font-weight:600;margin-bottom:.45rem"></div>
                    <div style="font-size:.73rem;color:#ef4444;margin-bottom:.45rem">⚠ Stok item lama akan dikembalikan. Stok item baru akan dipotong.</div>
                    <div style="display:flex;gap:.4rem">
                        <button id="gantiConfirmBtn-${l.id}" onclick="confirmGantiItem(${l.id})"
                            style="background:#0f766e;border:none;color:#fff;border-radius:8px;padding:.35rem .85rem;font-size:.78rem;font-weight:700;cursor:pointer">✓ Konfirmasi Ganti</button>
                        <button onclick="cancelGantiConfirm(${l.id})"
                            style="background:#e2e8f0;border:none;color:#64748b;border-radius:8px;padding:.35rem .7rem;font-size:.78rem;cursor:pointer">← Cari Lagi</button>
                    </div>
                </div>
            </div>
            {{-- Split panel --}}
            <div id="splitPanel-${l.id}" style="display:none;background:#f5f3ff;border:1.5px solid #c4b5fd;border-radius:0 0 12px 12px;padding:.75rem 1rem;margin-top:-.25rem;margin-bottom:.25rem">
                <div style="font-size:.78rem;font-weight:700;color:#6d28d9;margin-bottom:.5rem">✂ Split Line — total qty harus = ${l.qty_ordered}</div>
                <div id="splitRows-${l.id}" style="display:flex;flex-direction:column;gap:.3rem;margin-bottom:.5rem"></div>
                <div style="display:flex;gap:.4rem;align-items:center;margin-bottom:.4rem;flex-wrap:wrap">
                    <input id="splitCode-${l.id}" type="text" placeholder="Kode item…"
                        autocomplete="off" spellcheck="false"
                        style="flex:1;min-width:120px;border:1.5px solid #c4b5fd;border-radius:8px;padding:.33rem .6rem;font-size:.8rem;font-family:monospace;outline:none"
                        onfocus="this.style.borderColor='#6d28d9'" onblur="this.style.borderColor='#c4b5fd'"
                        onkeydown="if(event.key==='Enter'){event.preventDefault();pickSplitSearch(${l.id})}">
                    <input id="splitQty-${l.id}" type="number" min="1" value="1"
                        style="width:60px;border:1.5px solid #c4b5fd;border-radius:8px;padding:.33rem .45rem;font-size:.82rem;font-weight:700;text-align:center;outline:none"
                        onfocus="this.style.borderColor='#6d28d9'" onblur="this.style.borderColor='#c4b5fd'">
                    <button onclick="pickSplitSearch(${l.id})"
                        style="background:#6d28d9;border:none;color:#fff;border-radius:8px;padding:.33rem .75rem;font-size:.78rem;font-weight:700;cursor:pointer">+ Tambah</button>
                </div>
                <div id="splitSearchResult-${l.id}" style="font-size:.73rem;color:#94a3b8;min-height:.8rem;margin-bottom:.4rem"></div>
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.4rem">
                    <span id="splitTotal-${l.id}" style="font-size:.78rem;font-weight:800;color:#94a3b8">Total: 0 / ${l.qty_ordered}</span>
                    <div style="display:flex;gap:.35rem">
                        <button onclick="toggleSplitPanel(${l.id},${l.qty_ordered})"
                            style="background:#e2e8f0;border:none;color:#64748b;border-radius:8px;padding:.3rem .65rem;font-size:.75rem;cursor:pointer">Batal</button>
                        <button id="splitSaveBtn-${l.id}" onclick="pickSplitSave(${l.id},${l.qty_ordered})" disabled
                            style="background:#6d28d9;border:none;color:#fff;border-radius:8px;padding:.3rem .85rem;font-size:.78rem;font-weight:700;cursor:pointer;opacity:.4">Simpan Split</button>
                    </div>
                </div>
            </div>
        </div>`;
    }

    // State untuk ganti item per line
    const _gantiState = {}; // lineId → { itemId, itemCode, itemName }

    window.toggleGantiItem = function (lineId) {
        const panel = $('gantiPanel-' + lineId);
        if (!panel) return;
        const isOpen = panel.style.display !== 'none';
        panel.style.display = isOpen ? 'none' : 'block';
        if (!isOpen) {
            const inp = $('gantiInput-' + lineId);
            if (inp) { inp.value = ''; inp.focus(); }
            const res = $('gantiResults-' + lineId); if (res) res.innerHTML = '';
            const conf = $('gantiConfirm-' + lineId); if (conf) conf.style.display = 'none';
        }
    };

    window.searchGantiItem = async function (lineId) {
        const inp = $('gantiInput-' + lineId);
        const res = $('gantiResults-' + lineId);
        if (!inp || !res) return;
        const q = inp.value.trim();
        if (!q) { res.innerHTML = '<span style="color:#ef4444">Ketik SKU dulu.</span>'; return; }
        res.innerHTML = '<span style="color:#94a3b8">Mencari…</span>';
        try {
            const list = await api(`/api/marketplace/items/search?q=${encodeURIComponent(q)}&limit=6`);
            if (!list.length) { res.innerHTML = '<span style="color:#ef4444">Tidak ditemukan.</span>'; return; }

            const exact = list.find(i => i.code?.toLowerCase() === q.toLowerCase());
            if (exact) { prepareGantiConfirm(lineId, exact); res.innerHTML = ''; return; }

            res.innerHTML = list.map(i =>
                `<button onclick="prepareGantiConfirm(${lineId}, {id:${i.id}, code:'${esc(i.code)}', name:'${esc(i.name)}'})"
                    style="display:block;width:100%;text-align:left;background:#fff;border:1px solid #bae6fd;
                           border-radius:6px;padding:.3rem .65rem;font-size:.75rem;margin-bottom:.2rem;cursor:pointer;color:#0f172a">
                    <strong>${esc(i.code)}</strong> — ${esc(i.name)}
                </button>`
            ).join('');
        } catch {
            res.innerHTML = '<span style="color:#ef4444">Gagal menghubungi server.</span>';
        }
    };

    window.prepareGantiConfirm = function (lineId, item) {
        _gantiState[lineId] = item;
        const res  = $('gantiResults-' + lineId); if (res) res.innerHTML = '';
        const conf = $('gantiConfirm-' + lineId); if (!conf) return;
        const qty  = parseInt($('gantiQty-' + lineId)?.value || 1);
        $('gantiConfirmText-' + lineId).textContent =
            `Ganti dengan: ${item.code} — ${item.name}  ×${qty}`;
        conf.style.display = 'block';
    };

    window.cancelGantiConfirm = function (lineId) {
        const conf = $('gantiConfirm-' + lineId); if (conf) conf.style.display = 'none';
        delete _gantiState[lineId];
    };

    window.confirmGantiItem = async function (lineId) {
        const item = _gantiState[lineId];
        if (!item) return;
        const qty = parseInt($('gantiQty-' + lineId)?.value || 1);
        const btn = $('gantiConfirmBtn-' + lineId);
        if (btn) { btn.disabled = true; btn.textContent = 'Menyimpan…'; }
        try {
            const r = await api(`/api/fulfillments/${currentFulfillId}/lines/${lineId}/substitute`, {
                method: 'POST',
                body: JSON.stringify({ item_id: item.id, qty }),
            });
            // Response mengandung fulfillment lengkap
            renderFulfillDetail(r.fulfillment);
        } catch (e) {
            if (btn) { btn.disabled = false; btn.textContent = '✓ Konfirmasi Ganti'; }
            alert(e.message || 'Gagal mengganti item.');
        }
    };

    window.togglePickLine = async function (lineId) {
        try {
            const r = await api(`/api/fulfillments/${currentFulfillId}/lines/${lineId}/toggle-picked`, { method: 'POST' });
            // Refresh modal
            const f = await api('/api/fulfillments/' + currentFulfillId);
            renderFulfillDetail(f);
        } catch (e) { alert(e.message); }
    };

    window.flagPickProblem = async function (lineId) {
        const reason = prompt('Masalah pada item ini?');
        if (!reason) return;
        try {
            await api(`/api/fulfillments/${currentFulfillId}/lines/${lineId}/flag-problem`, {
                method: 'POST',
                body: JSON.stringify({ reason }),
            });
            const f = await api('/api/fulfillments/' + currentFulfillId);
            renderFulfillDetail(f);
        } catch (e) { alert(e.message); }
    };

    window.resolvePickProblem = async function (lineId) {
        try {
            await api(`/api/fulfillments/${currentFulfillId}/lines/${lineId}/resolve-problem`, {
                method: 'POST',
                body: JSON.stringify({}),
            });
            const f = await api('/api/fulfillments/' + currentFulfillId);
            renderFulfillDetail(f);
        } catch (e) { alert(e.message); }
    };

    // ── Split Panel (picking modal) ───────────────────────────────────────────
    const _pickSplitRows = {}; // lineId → [{item_id,code,name,qty}]

    window.toggleSplitPanel = function (lineId, qtyOrdered) {
        const panel = $('splitPanel-' + lineId);
        if (!panel) return;
        const isOpen = panel.style.display !== 'none';
        if (isOpen) {
            panel.style.display = 'none';
            delete _pickSplitRows[lineId];
        } else {
            _pickSplitRows[lineId] = [];
            panel.style.display = 'block';
            renderPickSplitRows(lineId, qtyOrdered);
            $('splitCode-' + lineId)?.focus();
        }
    };

    function renderPickSplitRows(lineId, qtyOrdered) {
        const rows    = _pickSplitRows[lineId] || [];
        const total   = rows.reduce((s, r) => s + r.qty, 0);
        const ok      = total === qtyOrdered && rows.length >= 2;
        const container = $('splitRows-' + lineId);
        const totalEl   = $('splitTotal-' + lineId);
        const saveBtn   = $('splitSaveBtn-' + lineId);

        if (container) {
            container.innerHTML = rows.length
                ? rows.map((r, i) => `
                    <div style="display:flex;align-items:center;gap:.4rem;background:#fff;border:1px solid #e0e7ff;border-radius:7px;padding:.3rem .5rem">
                        <span style="font-family:monospace;color:#6d28d9;font-size:.72rem">${esc(r.code)}</span>
                        <span style="color:#64748b;font-size:.72rem;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(r.name)}</span>
                        <span style="color:#0f172a;font-weight:800;font-size:.78rem">×${r.qty}</span>
                        <button onclick="pickSplitRemoveRow(${lineId},${i},${qtyOrdered})"
                            style="background:#fee2e2;border:none;border-radius:5px;width:20px;height:20px;color:#ef4444;font-size:.7rem;cursor:pointer">✕</button>
                    </div>`).join('')
                : '<div style="color:#94a3b8;font-size:.72rem;font-style:italic">Belum ada baris.</div>';
        }
        if (totalEl) { totalEl.textContent = `Total: ${total} / ${qtyOrdered}`; totalEl.style.color = ok ? '#059669' : (total > qtyOrdered ? '#ef4444' : '#94a3b8'); }
        if (saveBtn) { saveBtn.disabled = !ok; saveBtn.style.opacity = ok ? '1' : '.4'; }
    }

    window.pickSplitRemoveRow = function (lineId, idx, qtyOrdered) {
        (_pickSplitRows[lineId] || []).splice(idx, 1);
        renderPickSplitRows(lineId, qtyOrdered);
    };

    window.pickSplitSearch = async function (lineId) {
        const code = $('splitCode-' + lineId)?.value.trim().toUpperCase();
        const qty  = parseInt($('splitQty-' + lineId)?.value) || 1;
        const res  = $('splitSearchResult-' + lineId);
        if (!code || !res) return;
        res.style.color = '#94a3b8'; res.textContent = 'Mencari…';
        try {
            const item = await api(`/api/marketplace/items/by-code?code=${encodeURIComponent(code)}`);
            const qtyOrdered = parseInt($('splitPanel-' + lineId)?.querySelector('[id^="splitSaveBtn"]')?.dataset?.qtyOrdered) || 0;
            res.innerHTML = `<div style="display:flex;align-items:center;gap:.4rem;margin-top:.2rem">
                <span style="font-family:monospace;color:#059669;font-size:.73rem">${esc(item.code)}</span>
                <span style="color:#64748b;font-size:.73rem;flex:1">${esc(item.name)}</span>
                <button onclick="pickSplitAddRow(${lineId},${item.id},'${esc(item.code)}','${esc(item.name)}',${qty})"
                    style="background:#6d28d9;border:none;border-radius:6px;padding:.2rem .55rem;color:#fff;font-size:.7rem;font-weight:800;cursor:pointer">+ Tambah</button>
            </div>`;
        } catch (e) {
            res.style.color = '#ef4444'; res.textContent = `✗ ${e.message}`;
        }
    };

    window.pickSplitAddRow = function (lineId, itemId, code, name, qty) {
        if (!_pickSplitRows[lineId]) _pickSplitRows[lineId] = [];
        _pickSplitRows[lineId].push({ item_id: itemId, code, name, qty });
        const codeInp = $('splitCode-' + lineId);
        const qtyInp  = $('splitQty-' + lineId);
        const res     = $('splitSearchResult-' + lineId);
        if (codeInp) { codeInp.value = ''; codeInp.focus(); }
        if (qtyInp) qtyInp.value = 1;
        if (res) res.textContent = '';
        // qtyOrdered dari attribute data
        const saveBtn = $('splitSaveBtn-' + lineId);
        const qtyOrdered = saveBtn ? parseInt(saveBtn.getAttribute('data-qty') || '0') : 0;
        // fallback: hitung dari title text
        renderPickSplitRows(lineId, qtyOrdered || parseInt($('splitTotal-' + lineId)?.textContent?.split('/')[1]) || 1);
    };

    window.pickSplitSave = async function (lineId, qtyOrdered) {
        const rows = _pickSplitRows[lineId] || [];
        const total = rows.reduce((s, r) => s + r.qty, 0);
        if (total !== qtyOrdered || rows.length < 2) return;

        const btn = $('splitSaveBtn-' + lineId);
        if (btn) { btn.disabled = true; btn.textContent = 'Menyimpan…'; }

        try {
            const r = await api(`/api/fulfillments/${currentFulfillId}/lines/${lineId}/split`, {
                method: 'POST',
                body: JSON.stringify({ splits: rows.map(s => ({ item_id: s.item_id, qty: s.qty })) }),
            });
            delete _pickSplitRows[lineId];
            renderFulfillDetail(r.fulfillment);
        } catch (e) {
            if (btn) { btn.disabled = false; btn.textContent = 'Simpan Split'; }
            alert(e.message || 'Gagal split.');
        }
    };

    // ── Restore Split (picking modal) ─────────────────────────────────────────
    window.restoreSplitLine = async function (fulfillId, lineId) {
        // Cek dulu apakah ada yang sudah dipick (HTTP 409 = needs_confirm)
        try {
            const r = await api(`/api/fulfillments/${fulfillId}/lines/${lineId}/restore-split`, { method: 'POST' });
            renderFulfillDetail(r.fulfillment);
        } catch (e) {
            if (e.status === 409 && e.data?.needs_confirm) {
                const ok = confirm(`${e.data.message}\n\nStok tetap akan dikoreksi otomatis. Lanjutkan?`);
                if (!ok) return;
                try {
                    const r2 = await api(`/api/fulfillments/${fulfillId}/lines/${lineId}/restore-split?force=1`, { method: 'POST' });
                    renderFulfillDetail(r2.fulfillment);
                } catch (e2) { alert(e2.message || 'Gagal restore.'); }
            } else {
                alert(e.message || 'Gagal restore.');
            }
        }
    };

    // ── Shared actions ────────────────────────────────────────────────────────
    window.updateQty = async function (lineId, qty) {
        await api(`/api/fulfillments/${currentFulfillId}/lines/${lineId}`, {
            method: 'PATCH', body: JSON.stringify({ qty_fulfilled: parseInt(qty) }),
        }).catch(e => alert(e.message));
    };

    window.editLine = function (lineId, sku) {
        mpMapping.openForLine(lineId, currentFulfillId, sku);
    };


    // ── Action button — proses packing tanpa potong stok ─────────────────────
    $('fulfillActionBtn').addEventListener('click', async () => {
        if (!currentFulfillId) return;
        const btn = $('fulfillActionBtn'), alertEl = $('fulfillModalAlert');
        btn.disabled    = true;
        btn.textContent = 'Memproses…';

        try {
            // Kirim items terscan ke /pack (update qty_fulfilled + set packed, no stock cut)
            const scanned = Object.values(_singleScanItems);
            const items   = scanned.map(s => ({ item_id: s.id, qty: s.qty }));

            await api(`/api/fulfillments/${currentFulfillId}/pack`, {
                method: 'POST',
                body: JSON.stringify({ items }),
            });

            alertEl.className   = 'alert alert-success w-100 mb-0';
            alertEl.textContent = '📦 Order diproses. Stok belum dipotong — review di tab Perlu Konfirmasi.';
            btn.textContent     = '✓ Selesai';
            loadFulfillments();
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance($('fulfillModal'));
                if (modal) modal.hide();
            }, 1200);
        } catch (e) {
            alertEl.className   = 'alert alert-danger w-100 mb-0';
            alertEl.textContent = e.message;
            btn.disabled        = false;
            btn.textContent     = '📦 Proses';
        }
    });

    // ── Scan Box ─────────────────────────────────────────────────────────────
    const scanInput  = $('scanInput');
    const scanResult = $('scanResult');
    const scanBtn    = $('scanBtn');

    const _scanParam = new URLSearchParams(window.location.search).get('scan');
    if (_scanParam) {
        scanInput.value = _scanParam;
        history.replaceState({}, '', window.location.pathname);
        setTimeout(() => doScan(), 400);
    }

    function setScanResult(msg, type) {
        const colors = { loading:'#64748b', success:'#16a34a', warn:'#d97706', error:'#dc2626' };
        scanResult.style.color = colors[type] || '#64748b';
        scanResult.textContent = msg;
    }

    window.doScan = async function () {
        const orderNo = scanInput.value.trim();
        if (!orderNo) { scanInput.focus(); return; }

        scanBtn.disabled = true;
        scanBtn.textContent = '⏳ Mencari…';
        setScanResult('Mencari order…', 'loading');

        try {
            const res = await api('/api/fulfillments/scan', {
                method: 'POST',
                body:   JSON.stringify({ order_no: orderNo }),
            });

            const f = res.fulfillment;
            if (res.already_confirmed) {
                setScanResult(`✓ Order ${orderNo} sudah selesai (${f.confirmed_at ? new Date(f.confirmed_at).toLocaleString('id-ID') : '—'}).`, 'warn');
            } else {
                setScanResult(`✓ Order ${orderNo} ditemukan — membuka…`, 'success');
                scanInput.value = '';
                loadFulfillments();
                setTimeout(() => openFulfillment(f.id), 250);
            }
        } catch (e) {
            setScanResult(`✗ ${e.message || 'Order tidak ditemukan.'}`, 'error');
        } finally {
            scanBtn.disabled = false;
            scanBtn.textContent = '🔍 Cari';
            scanInput.focus();
            scanInput.select();
        }
    };

    // ── Mode toggle ──────────────────────────────────────────────────────────
    let _scanMode = 'batch'; // 'single' | 'batch' — default batch

    window.setScanMode = function (mode) {
        _scanMode = mode;
        const isBatch = mode === 'batch';

        // Toggle visibility
        $('singleScanBox').style.display = isBatch ? 'none' : 'block';
        $('batchScanBox').style.display  = isBatch ? 'block' : 'none';

        // Update tab buttons — active: solid indigo, inactive: ghost
        const tabOrder = $('modeTabOrder'), tabBatch = $('modeTabBatch');
        if (isBatch) {
            tabOrder.style.background = 'transparent';
            tabOrder.style.boxShadow   = 'none';
            tabOrder.style.color       = '#64748b';
            tabBatch.style.background  = '#6366f1';
            tabBatch.style.boxShadow   = '0 1px 4px rgba(99,102,241,.35)';
            tabBatch.style.color       = '#fff';
            $('scanModeBadge').textContent = 'BATCH MODE';
            $('scanModeBadge').style.color = '#6366f1';
        } else {
            tabOrder.style.background  = '#6366f1';
            tabOrder.style.boxShadow   = '0 1px 4px rgba(99,102,241,.35)';
            tabOrder.style.color       = '#fff';
            tabBatch.style.background  = 'transparent';
            tabBatch.style.boxShadow   = 'none';
            tabBatch.style.color       = '#64748b';
            $('scanModeBadge').textContent = 'SINGLE ORDER';
            $('scanModeBadge').style.color = '#94a3b8';
            setTimeout(() => { const inp = $('scanInput'); if (inp) inp.focus(); }, 100);
        }

        if (isBatch) batchShowPhase(1);
        saveState();
    };

    // ── BATCH MODE ───────────────────────────────────────────────────────────
    // State
    let _batchItems    = {};  // { itemId: { id, code, name, qty } }
    let _batchOrders   = [];  // [{ orderNo, fulfillment: {...} }]
    let _batchReconResults = []; // hasil rekonsiliasi

    // ── localStorage persistence ─────────────────────────────────────────────
    let _currentBatchPhase = 1;
    const _STATE_KEY = 'gfid_fulfillment_state';

    function saveState() {
        try {
            localStorage.setItem(_STATE_KEY, JSON.stringify({
                scanMode:             _scanMode,
                batchPhase:           _currentBatchPhase,
                batchItems:           _batchItems,
                batchOrders:          _batchOrders,
                batchReconResults:    _batchReconResults,
                batchSubstitutions:   _batchSubstitutions,
                batchSplits:          _batchSplits,
                batchSplitMeta:       _batchSplitMeta,
                batchRemainingPool:   _batchRemainingPool,
                currentFulfillId,
            }));
        } catch {}
    }

    function clearState() {
        try { localStorage.removeItem(_STATE_KEY); } catch {}
    }

    function loadState() {
        try {
            const raw = localStorage.getItem(_STATE_KEY);
            if (!raw) { setScanMode('batch'); return; }
            const s = JSON.parse(raw);
            _scanMode             = s.scanMode             || 'batch';
            _batchItems           = s.batchItems           || {};
            _batchOrders          = s.batchOrders          || [];
            _batchReconResults    = s.batchReconResults    || [];
            _batchSubstitutions   = s.batchSubstitutions   || {};
            _batchSplits          = s.batchSplits          || {};
            _batchSplitMeta       = s.batchSplitMeta       || {};
            _batchRemainingPool   = s.batchRemainingPool   || {};
            currentFulfillId      = s.currentFulfillId     || null;
            // Save phase to local var — setScanMode → batchShowPhase(1) will reset _currentBatchPhase
            const savedPhase      = s.batchPhase           || 1;

            // Restore tabs + default phase 1 UI
            setScanMode(_scanMode);
            // Jump to the correct saved phase (overrides the batchShowPhase(1) from setScanMode)
            if (_scanMode === 'batch' && savedPhase > 1) {
                batchShowPhase(savedPhase);
            }
            renderBatchItemList();
            renderBatchOrderList();
            if (savedPhase === 3 && _batchReconResults.length) {
                renderBatchRecon();
            }
        } catch { setScanMode('batch'); }
    }
    // ─────────────────────────────────────────────────────────────────────────

    function batchShowPhase(n) {
        _currentBatchPhase = n;
        saveState();
        [1,2,3].forEach(i => {
            $(`batchPhase${i}`).style.display = i === n ? 'block' : 'none';
        });
        // Stepper pills: active = indigo + shadow, done = green-tint, pending = muted
        const stepCfg = {
            active:  { bg:'#6366f1', color:'#fff',    shadow:'0 1px 6px rgba(99,102,241,.35)',   fontSize:'.78rem', padding:'.3rem .9rem' },
            done:    { bg:'#dcfce7', color:'#15803d',  shadow:'none',                             fontSize:'.73rem', padding:'.25rem .75rem' },
            pending: { bg:'#f1f5f9', color:'#94a3b8',  shadow:'none',                             fontSize:'.73rem', padding:'.25rem .75rem' },
        };
        ['bphase1','bphase2','bphase3'].forEach((id, idx) => {
            const el  = $(id);
            const cfg = idx + 1 === n ? stepCfg.active : (idx + 1 < n ? stepCfg.done : stepCfg.pending);
            el.style.background  = cfg.bg;
            el.style.color       = cfg.color;
            el.style.boxShadow   = cfg.shadow;
            el.style.fontSize    = cfg.fontSize;
            el.style.padding     = cfg.padding;
        });
        // CTA button enabled/disabled style
        const toOrdersBtn    = $('batchToOrdersBtn');
        const reconcileBtn   = $('batchReconcileBtn');
        if (toOrdersBtn) {
            const en = !toOrdersBtn.disabled;
            toOrdersBtn.style.background  = en ? '#16a34a' : '#e2e8f0';
            toOrdersBtn.style.color       = en ? '#fff'    : '#94a3b8';
            toOrdersBtn.style.cursor      = en ? 'pointer' : 'not-allowed';
            toOrdersBtn.style.boxShadow   = en ? '0 2px 8px rgba(22,163,74,.3)' : 'none';
        }
        if (reconcileBtn) {
            const en = !reconcileBtn.disabled;
            reconcileBtn.style.background = en ? '#f59e0b' : '#e2e8f0';
            reconcileBtn.style.color      = en ? '#451a03' : '#94a3b8';
            reconcileBtn.style.cursor     = en ? 'pointer' : 'not-allowed';
            reconcileBtn.style.boxShadow  = en ? '0 2px 8px rgba(245,158,11,.3)' : 'none';
        }
        // Focus relevant input
        if (n === 1) setTimeout(() => { const el = $('batchItemInput'); if (el) el.focus(); }, 100);
        if (n === 2) setTimeout(() => { const el = $('batchOrderInput'); if (el) el.focus(); }, 100);
    }

    function setBatchItemResult(msg, type) {
        const colors = { loading:'#64748b', success:'#16a34a', warn:'#d97706', error:'#dc2626' };
        const el = $('batchItemResult');
        el.style.color = colors[type] || '#64748b';
        el.textContent = msg;
    }

    function setBatchOrderResult(msg, type) {
        const colors = { loading:'#64748b', success:'#16a34a', warn:'#d97706', error:'#dc2626' };
        const el = $('batchOrderResult');
        el.style.color = colors[type] || '#94a3b8';
        el.textContent = msg;
    }

    function renderBatchItemList() {
        const rows = $('batchItemRows');
        const list = $('batchItemList');
        const items = Object.values(_batchItems);
        if (!items.length) { list.style.display = 'none'; return; }
        list.style.display = 'block';
        rows.innerHTML = items.map(it => `
            <div class="sr-item-row">
                <div style="flex:1; min-width:0">
                    <div class="sr-item-code">${esc(it.code)}</div>
                    <div class="sr-item-name" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(it.name)}</div>
                </div>
                <div style="display:flex;align-items:center;gap:.3rem;flex-shrink:0">
                    <button onclick="batchDecItem(${it.id})"
                        style="background:#e2e8f0;border:none;border-radius:5px;width:22px;height:22px;color:#475569;font-weight:800;cursor:pointer;font-size:.8rem;line-height:1">−</button>
                    <span class="sr-item-qty" style="min-width:20px;text-align:center">${it.qty}</span>
                    <button onclick="batchIncItem(${it.id})"
                        style="background:#e2e8f0;border:none;border-radius:5px;width:22px;height:22px;color:#475569;font-weight:800;cursor:pointer;font-size:.8rem;line-height:1">+</button>
                    <button onclick="batchRemoveItem(${it.id})"
                        style="background:#fee2e2;border:none;border-radius:5px;width:22px;height:22px;color:#ef4444;font-weight:800;cursor:pointer;font-size:.8rem;line-height:1;margin-left:.2rem">✕</button>
                </div>
            </div>`).join('');

        const btn = $('batchToOrdersBtn');
        btn.disabled = !items.length;
        btn.style.background  = items.length ? '#16a34a' : '#e2e8f0';
        btn.style.color       = items.length ? '#fff'    : '#94a3b8';
        btn.style.cursor      = items.length ? 'pointer' : 'not-allowed';
        btn.style.boxShadow   = items.length ? '0 2px 8px rgba(22,163,74,.3)' : 'none';
    }

    function renderBatchOrderList() {
        // Daftar order tidak ditampilkan — hanya update state tombol rekonsiliasi
        const n   = _batchOrders.length;
        const btn = $('batchReconcileBtn');
        btn.disabled          = !n;
        btn.style.background  = n ? '#f59e0b' : '#e2e8f0';
        btn.style.color       = n ? '#451a03' : '#94a3b8';
        btn.style.cursor      = n ? 'pointer' : 'not-allowed';
        btn.style.boxShadow   = n ? '0 2px 8px rgba(245,158,11,.3)' : 'none';
        btn.textContent       = n ? `⚖ Rekonsiliasi ${n} Order →` : '⚖ Rekonsiliasi →';
    }

    window.batchAddItem = async function () {
        const inp = $('batchItemInput');
        const code = inp.value.trim().toUpperCase();
        if (!code) { inp.focus(); return; }

        $('batchItemBtn').disabled = true;
        setBatchItemResult('Mencari item…', 'loading');

        try {
            const item = await api(`/api/marketplace/items/by-code?code=${encodeURIComponent(code)}`);
            const id = String(item.id);
            if (_batchItems[id]) {
                _batchItems[id].qty++;
            } else {
                _batchItems[id] = { id: item.id, code: item.code, name: item.name, qty: 1 };
            }
            setBatchItemResult(`✓ ${item.code} — ${item.name} (×${_batchItems[id].qty} total)`, 'success');
            inp.value = '';
            renderBatchItemList();
            saveState();
        } catch (e) {
            setBatchItemResult(`✗ ${e.message || 'Kode tidak ditemukan'}`, 'error');
        } finally {
            $('batchItemBtn').disabled = false;
            inp.focus();
        }
    };

    window.batchIncItem    = id => { const k=String(id); if(_batchItems[k]) { _batchItems[k].qty++; renderBatchItemList(); saveState(); } };
    window.batchDecItem    = id => { const k=String(id); if(_batchItems[k]) { _batchItems[k].qty = Math.max(1,_batchItems[k].qty-1); renderBatchItemList(); saveState(); } };
    window.batchRemoveItem = id => { delete _batchItems[String(id)]; renderBatchItemList(); saveState(); };
    window.batchResetItems = () => { _batchItems = {}; renderBatchItemList(); setBatchItemResult('', ''); saveState(); };

    window.batchAddOrder = async function () {
        const inp = $('batchOrderInput');
        const orderNo = inp.value.trim();
        if (!orderNo) { inp.focus(); return; }

        // Cek duplikat
        if (_batchOrders.find(o => o.orderNo === orderNo)) {
            setBatchOrderResult(`⚠ Order ${orderNo} sudah ada.`, 'warn');
            inp.value = ''; inp.focus(); return;
        }

        $('batchOrderBtn').disabled = true;
        setBatchOrderResult('Mencari order…', 'loading');

        try {
            const res = await api('/api/fulfillments/scan', {
                method: 'POST',
                body:   JSON.stringify({ order_no: orderNo }),
            });
            if (res.already_confirmed) {
                setBatchOrderResult(`⚠ Order ${orderNo} sudah pernah dikonfirmasi.`, 'warn');
            } else {
                _batchOrders.push({ orderNo, fulfillment: res.fulfillment });
                setBatchOrderResult(`✓ ${orderNo} — ${res.fulfillment?.order?.store?.name || ''}`, 'success');
                inp.value = '';
                renderBatchOrderList();
                saveState();
            }
        } catch (e) {
            setBatchOrderResult(`✗ ${e.message || 'Order tidak ditemukan'}`, 'error');
        } finally {
            $('batchOrderBtn').disabled = false;
            inp.focus();
        }
    };

    window.batchRemoveOrder = idx => { _batchOrders.splice(idx, 1); renderBatchOrderList(); saveState(); };

    window.batchGoToPhase1 = () => batchShowPhase(1);
    window.batchGoToPhase2 = () => { batchShowPhase(2); renderBatchOrderList(); };
    window.batchClearPool  = () => { _batchRemainingPool = {}; saveState(); renderBatchRecon(); };

    // _batchSubstitutions: { 'orderId_lineId': { item_id, code, name, qty } }
    let _batchSubstitutions = {};
    // _batchSplits: { 'orderId_lineId': [ {item_id, code, name, qty}, ... ] }
    let _batchSplits = {};
    // _batchSplitMeta: { 'orderId_lineId': { matchedItemId, matchedQty } } — untuk restore pool saat clear
    let _batchSplitMeta = {};
    // _batchRemainingPool: sisa pool setelah rekonsiliasi
    let _batchRemainingPool = {};

    window.batchReconcile = async function () {
        _batchSubstitutions = {};
        _batchSplits = {};
        _batchSplitMeta = {};

        // Load detail lines tiap fulfillment
        const detailed = [];
        for (const o of _batchOrders) {
            try {
                const f = await api(`/api/fulfillments/${o.fulfillment.id}`);
                detailed.push({ orderNo: o.orderNo, fulfillment: f });
            } catch {
                detailed.push({ orderNo: o.orderNo, fulfillment: o.fulfillment });
            }
        }

        // Pool copy
        const pool = {};
        for (const [id, it] of Object.entries(_batchItems)) pool[id] = { ...it };

        // Rekonsiliasi
        _batchReconResults = detailed.map(({ orderNo, fulfillment: f }) => {
            const lineResults = (f.lines || [])
                .filter(l => !l.is_split_parent)
                .map(line => {
                    if (!line.item) return { line, status: 'unmapped', needed: line.qty_ordered, matched: 0 };
                    const id = String(line.item.id);
                    const needed = line.qty_ordered;
                    const available = pool[id]?.qty ?? 0;
                    const matched = Math.min(needed, available);
                    if (pool[id]) pool[id].qty -= matched;
                    return { line, needed, matched, shortage: needed - matched, status: matched >= needed ? 'ok' : 'short' };
                });
            const allOk = lineResults.length > 0 && lineResults.every(r => r.status === 'ok');
            return { orderNo, fulfillment: f, lineResults, allOk };
        });

        // Sisa pool setelah semua order dimatching
        _batchRemainingPool = Object.fromEntries(
            Object.entries(pool).filter(([, it]) => it.qty > 0)
        );

        renderBatchRecon();
        batchShowPhase(3); // also calls saveState() internally
    };

    function _batchLineKey(r, lr) { return `${r.fulfillment.id}_${lr.line.id}`; }

    function renderBatchRecon() {
        const body    = $('batchReconBody');
        const matched = _batchReconResults.filter(r => {
            // order bisa dikonfirmasi jika semua lines: ok, atau ada sub/split, atau shortage tapi item sudah mapping
            return r.lineResults.every(lr => {
                if (lr.status === 'ok') return true;
                const key = _batchLineKey(r, lr);
                if (_batchSubstitutions[key] || _batchSplits[key]) return true;
                return lr.line.item !== null; // shortage tapi sudah mapping → boleh confirm dengan qty kurang
            });
        }).length;

        let html = '';

        // ── Per-order cards ──
        html += _batchReconResults.map((r, rIdx) => {
            const allMapped   = r.lineResults.every(lr => lr.line.item !== null);
            const allResolved = r.lineResults.every(lr => {
                if (lr.status === 'ok') return true;
                const key = _batchLineKey(r, lr);
                return _batchSubstitutions[key] || _batchSplits[key];
            });
            const confirmable = allMapped; // bisa confirm selama semua item sudah mapping
            const borderColor = allResolved ? 'rgba(34,197,94,.35)' : confirmable ? 'rgba(245,158,11,.35)' : 'rgba(239,68,68,.3)';
            const badge = allResolved
                ? '<span style="background:#dcfce7;color:#15803d;font-size:.7rem;font-weight:800;padding:.12rem .55rem;border-radius:999px">✓ MATCHED</span>'
                : confirmable
                    ? '<span style="background:#fef3c7;color:#92400e;font-size:.7rem;font-weight:800;padding:.12rem .55rem;border-radius:999px">⚠ KURANG</span>'
                    : '<span style="background:#fee2e2;color:#dc2626;font-size:.7rem;font-weight:800;padding:.12rem .55rem;border-radius:999px">✗ BELUM MAPPING</span>';

            const linesHtml = r.lineResults.map((lr, lIdx) => {
                const key = _batchLineKey(r, lr);
                const sub  = _batchSubstitutions[key];
                const spl  = _batchSplits[key];
                const isOk = lr.status === 'ok';

                let itemCell = lr.line.item
                    ? `<span style="font-family:monospace;color:#4338ca;font-size:.7rem;font-weight:700">${esc(lr.line.item.code)}</span>
                       <span style="color:#64748b;margin-left:.25rem;font-size:.7rem">${esc(lr.line.item.name)}</span>`
                    : `<span style="color:#d97706;font-size:.7rem;font-weight:700">⚠ Belum mapping</span>`;

                // Badge untuk resolved non-ok
                let resolvedBadge = '';
                if (!isOk && sub) {
                    resolvedBadge = `<div style="margin-top:.2rem;font-size:.68rem;color:#16a34a">
                        ↳ Ganti: <span style="font-family:monospace;font-weight:700">${esc(sub.code)}</span> ×${sub.qty}</div>`;
                } else if (!isOk && spl) {
                    resolvedBadge = `<div style="margin-top:.2rem;font-size:.68rem;color:#16a34a">
                        ↳ Split: ${spl.map(s=>`<span style="font-family:monospace;font-weight:700">${esc(s.code)}</span>×${s.qty}`).join(', ')}</div>`;
                }

                // Action buttons untuk short lines
                let actionBtns = '';
                if (!isOk && !sub && !spl) {
                    actionBtns = `
                        <div style="display:flex;gap:.3rem;margin-top:.35rem;flex-wrap:wrap">
                            <button onclick="batchOpenSubst(${rIdx},${lIdx})"
                                style="background:#fef3c7;border:1px solid #fde68a;border-radius:7px;
                                       padding:.18rem .55rem;color:#92400e;font-size:.68rem;font-weight:700;cursor:pointer">
                                🔄 Ganti
                            </button>
                            ${lr.matched > 0 ? `<button onclick="batchOpenAddPool(${rIdx},${lIdx})"
                                style="background:#ecfdf5;border:1px solid #6ee7b7;border-radius:7px;
                                       padding:.18rem .55rem;color:#065f46;font-size:.68rem;font-weight:700;cursor:pointer">
                                ➕ Tambah
                            </button>` : ''}
                            ${lr.needed > 1 ? `<button onclick="batchOpenSplit(${rIdx},${lIdx})"
                                style="background:#ede9fe;border:1px solid #c4b5fd;border-radius:7px;
                                       padding:.18rem .55rem;color:#4338ca;font-size:.68rem;font-weight:700;cursor:pointer">
                                ✂ Split
                            </button>` : ''}
                        </div>`;
                } else if (!isOk && (sub || spl)) {
                    actionBtns = `
                        <button onclick="batchClearResolution(${rIdx},${lIdx})"
                            style="background:transparent;border:1px solid #e2e8f0;border-radius:7px;
                                   padding:.18rem .45rem;color:#94a3b8;font-size:.65rem;font-weight:700;cursor:pointer;margin-top:.3rem">
                            ✕ Batalkan
                        </button>`;
                }

                return `<tr style="border-top:1px solid #f1f5f9" id="brecon-row-${rIdx}-${lIdx}">
                    <td style="padding:.35rem .3rem;vertical-align:top">
                        ${itemCell}${resolvedBadge}${actionBtns}
                    </td>
                    <td style="text-align:center;padding:.35rem .3rem;color:#475569;font-weight:700;vertical-align:top">${lr.needed}</td>
                    <td style="text-align:center;padding:.35rem .3rem;font-weight:800;vertical-align:top;
                               color:${(() => { const rq = sub ? lr.matched + sub.qty : spl ? spl.reduce((s,r)=>s+r.qty,0) : lr.matched; return rq >= lr.needed ? '#16a34a' : (rq > 0 ? '#d97706' : '#dc2626'); })()}">
                        ${sub ? lr.matched + sub.qty : spl ? spl.reduce((s,r)=>s+r.qty,0) : lr.matched}
                    </td>
                    <td style="text-align:center;padding:.35rem .3rem;vertical-align:top;color:${isOk||(sub||spl)?'#16a34a':'#dc2626'}">${isOk||(sub||spl) ? '✓' : '✗'}</td>
                </tr>`;
            }).join('');

            return `<div style="background:#fff;border:1px solid ${borderColor};
                                border-radius:12px;padding:.7rem .9rem;margin-bottom:.5rem;
                                box-shadow:0 1px 3px rgba(15,23,42,.04)">
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.45rem">
                    <span style="font-size:.78rem;font-weight:800;color:#0f172a;font-family:monospace">${esc(r.orderNo)}</span>
                    ${(() => {
                        const carrier = (r.fulfillment?.order?.shipping_carrier || '').toLowerCase();
                        if (carrier.includes('instant') || carrier.includes('same day') || carrier.includes('sameday')) {
                            return `<span style="background:#fef08a;color:#854d0e;font-size:.65rem;font-weight:800;padding:.1rem .4rem;border-radius:999px;border:1px solid #fde047;white-space:nowrap;margin-right:2px;">⚡ KILAT</span>`;
                        }
                        return '';
                    })()}
                    <span style="font-size:.68rem;color:#94a3b8">${esc(r.fulfillment?.order?.store?.name || '')}</span>
                    <span style="margin-left:auto">${badge}</span>
                </div>
                <table style="width:100%;font-size:.72rem;border-collapse:collapse">
                    <thead><tr style="color:#94a3b8;font-weight:700;border-bottom:1px solid #f1f5f9">
                        <th style="text-align:left;padding:.2rem .3rem">Item</th>
                        <th style="text-align:center;padding:.2rem .3rem;width:60px">Butuh</th>
                        <th style="text-align:center;padding:.2rem .3rem;width:60px">Scan</th>
                        <th style="text-align:center;padding:.2rem .3rem;width:36px"></th>
                    </tr></thead>
                    <tbody>${linesHtml}</tbody>
                </table>
            </div>`;
        }).join('');

        // ── Sisa pool ──
        const sisa = Object.values(_batchRemainingPool);
        if (sisa.length) {
            html += `<div style="background:#fffbeb;border:1px solid #fde68a;
                                 border-radius:12px;padding:.7rem .9rem;margin-bottom:.5rem">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.45rem">
                    <div style="color:#92400e;font-weight:800;font-size:.75rem">📦 Sisa Item Terscan</div>
                    <button onclick="batchClearPool()"
                        style="background:transparent;border:1px solid #fde68a;border-radius:7px;
                               padding:.18rem .6rem;color:#92400e;font-size:.68rem;font-weight:700;cursor:pointer">
                        🗑 Kembalikan ke Rak
                    </button>
                </div>
                ${sisa.map(it => `
                    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.25rem">
                        <span style="font-family:monospace;color:#92400e;font-size:.72rem;font-weight:700">${esc(it.code)}</span>
                        <span style="color:#78716c;font-size:.72rem;flex:1">${esc(it.name)}</span>
                        <span style="color:#b45309;font-weight:800;font-size:.78rem">×${it.qty}</span>
                    </div>`).join('')}
            </div>`;
        }

        body.innerHTML = html;

        // Update confirm button
        const btn = $('batchConfirmAllBtn');
        if (matched === 0) {
            btn.disabled = true;
            btn.style.background  = '#e2e8f0';
            btn.style.color       = '#94a3b8';
            btn.style.boxShadow   = 'none';
            btn.style.cursor      = 'not-allowed';
            btn.textContent = '✗ Tidak ada yang matched';
        } else {
            btn.disabled = false;
            btn.style.background  = '#16a34a';
            btn.style.color       = '#fff';
            btn.style.boxShadow   = '0 3px 12px rgba(22,163,74,.35)';
            btn.style.cursor      = 'pointer';
            btn.textContent = `✓ Konfirmasi ${matched} Order${matched>1?' Sekaligus':''} & Potong Stok`;
        }
    }

    // ── Inline Substitusi ────────────────────────────────────────────────────
    // HTML untuk ketika pool kosong — tombol balik ke fase 1 scan item
    function _emptyPoolHtml(panelId) {
        return `<div style="display:flex;flex-direction:column;align-items:center;gap:.5rem;padding:.4rem 0">
            <div style="color:#64748b;font-size:.73rem">Tidak ada item sisa di pool.</div>
            <button onclick="document.getElementById('${panelId}')?.remove(); batchGoToPhase1();"
                style="background:#3b82f6;border:none;border-radius:8px;padding:.3rem .9rem;
                       color:#fff;font-size:.73rem;font-weight:700;cursor:pointer">
                ← Kembali ke Scan Item
            </button>
        </div>`;
    }

    // Hanya item dari _batchRemainingPool (sisa pool setelah rekonsiliasi) yang bisa dipilih.
    let _batchSubstTarget = null; // { rIdx, lIdx }

    window.batchOpenSubst = function (rIdx, lIdx) {
        _batchSubstTarget = { rIdx, lIdx };
        const r  = _batchReconResults[rIdx];
        const lr = r.lineResults[lIdx];
        const needed   = lr.needed - lr.matched;
        const lineName = lr.line.item ? `${lr.line.item.code} ×${lr.needed}` : 'item belum mapping';

        const row = document.getElementById(`brecon-row-${rIdx}-${lIdx}`);
        if (!row) return;
        const old = document.getElementById('batchSubstPanel');
        if (old) old.remove();

        const pool = Object.values(_batchRemainingPool);
        if (!pool.length) { batchGoToPhase1(); return; }
        let poolHtml;
        {
            poolHtml = pool.map(it => `
                <div style="display:flex;align-items:center;gap:.5rem;background:#fff;
                            border:1px solid #fde68a;border-radius:8px;padding:.35rem .6rem">
                    <span style="font-family:monospace;color:#92400e;font-size:.73rem;font-weight:700;flex-shrink:0">${esc(it.code)}</span>
                    <span style="color:#64748b;font-size:.73rem;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(it.name)}</span>
                    <span style="color:#b45309;font-weight:800;font-size:.72rem;flex-shrink:0">sisa ×${it.qty}</span>
                    <input type="number" min="1" max="${it.qty}" value="${Math.min(needed, it.qty)}"
                        id="substQty_${it.id}"
                        style="width:50px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;
                               padding:.2rem .3rem;font-size:.75rem;text-align:center;outline:none;flex-shrink:0">
                    <button onclick="batchApplySubst(${it.id},'${esc(it.code)}','${esc(it.name)}',parseInt(document.getElementById('substQty_${it.id}').value)||1)"
                        style="background:#16a34a;border:none;border-radius:7px;padding:.2rem .6rem;
                               color:#fff;font-size:.72rem;font-weight:800;cursor:pointer;flex-shrink:0">✓ Pakai</button>
                </div>`).join('');
        }

        const panel = document.createElement('tr');
        panel.id = 'batchSubstPanel';
        panel.innerHTML = `<td colspan="4" style="padding:.5rem .3rem">
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:.7rem .9rem">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem">
                    <div style="color:#92400e;font-weight:800;font-size:.75rem">
                        🔄 Ganti "${lineName}" — pilih dari sisa pool terscan
                    </div>
                    <button onclick="document.getElementById('batchSubstPanel').remove()"
                        style="background:transparent;border:none;color:#94a3b8;font-size:.9rem;cursor:pointer;padding:.1rem .3rem">✕</button>
                </div>
                <div style="display:flex;flex-direction:column;gap:.35rem">${poolHtml}</div>
            </div>
        </td>`;
        row.insertAdjacentElement('afterend', panel);
    };

    // ── Tambah dari pool (auto-split: item asli ×matched + item tambahan ×qty) ─
    window.batchOpenAddPool = function (rIdx, lIdx) {
        const r  = _batchReconResults[rIdx];
        const lr = r.lineResults[lIdx];

        document.getElementById('batchSubstPanel')?.remove();
        document.getElementById('batchSplitPanel')?.remove();
        document.getElementById('batchAddPoolPanel')?.remove();

        const pool = Object.values(_batchRemainingPool);
        let poolHtml;
        if (!pool.length) { batchGoToPhase1(); return; }
        {
            poolHtml = pool.map(it => `
                <div style="display:flex;align-items:center;gap:.5rem;background:#fff;
                            border:1px solid #6ee7b7;border-radius:8px;padding:.35rem .6rem">
                    <span style="font-family:monospace;color:#065f46;font-size:.73rem;font-weight:700;flex-shrink:0">${esc(it.code)}</span>
                    <span style="color:#64748b;font-size:.73rem;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(it.name)}</span>
                    <span style="color:#047857;font-weight:800;font-size:.72rem;flex-shrink:0">sisa ×${it.qty}</span>
                    <input type="number" min="1" max="${it.qty}" value="${Math.min(lr.shortage, it.qty)}"
                        id="addPoolQtyInline_${it.id}"
                        style="width:50px;background:#ecfdf5;border:1px solid #6ee7b7;border-radius:6px;
                               padding:.2rem .3rem;font-size:.75rem;text-align:center;outline:none;flex-shrink:0">
                    <button onclick="batchApplyAddPool(${rIdx},${lIdx},${it.id},'${esc(it.code)}','${esc(it.name)}',${it.qty})"
                        style="background:#059669;border:none;border-radius:7px;padding:.2rem .6rem;
                               color:#fff;font-size:.72rem;font-weight:800;cursor:pointer;flex-shrink:0">➕ Tambah</button>
                </div>`).join('');
        }

        const origCode = lr.line.item ? esc(lr.line.item.code) : '—';
        const row = document.getElementById(`brecon-row-${rIdx}-${lIdx}`);
        if (!row) return;
        const panel = document.createElement('tr');
        panel.id = 'batchAddPoolPanel';
        panel.innerHTML = `<td colspan="4" style="padding:.5rem .3rem">
            <div style="background:#ecfdf5;border:1px solid #6ee7b7;border-radius:10px;padding:.7rem .9rem">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem">
                    <div style="color:#065f46;font-weight:800;font-size:.75rem">
                        ➕ Tambah ke "${origCode} ×${lr.needed}" — pilih dari sisa pool
                        <span style="font-weight:400;color:#047857;font-size:.68rem;margin-left:.3rem">
                            (${origCode} ×${lr.matched} tetap dipakai)
                        </span>
                    </div>
                    <button onclick="document.getElementById('batchAddPoolPanel').remove()"
                        style="background:transparent;border:none;color:#94a3b8;font-size:.9rem;cursor:pointer;padding:.1rem .3rem">✕</button>
                </div>
                <div style="display:flex;flex-direction:column;gap:.35rem">${poolHtml}</div>
            </div>
        </td>`;
        row.insertAdjacentElement('afterend', panel);
    };

    window.batchApplyAddPool = function (rIdx, lIdx, itemId, code, name, maxQty) {
        const inp = document.getElementById(`addPoolQtyInline_${itemId}`);
        const qty = Math.min(parseInt(inp?.value) || 1, maxQty);

        const r   = _batchReconResults[rIdx];
        const lr  = r.lineResults[lIdx];
        const key = _batchLineKey(r, lr);

        // Bangun auto-split: item asli ×matched + item tambahan ×qty
        const rows = [];
        if (lr.line.item && lr.matched > 0) {
            rows.push({ item_id: lr.line.item.id, code: lr.line.item.code, name: lr.line.item.name, qty: lr.matched });
        }
        rows.push({ item_id: itemId, code, name, qty });

        // Kurangi pool
        const id = String(itemId);
        if (_batchRemainingPool[id]) {
            _batchRemainingPool[id].qty -= qty;
            if (_batchRemainingPool[id].qty <= 0) delete _batchRemainingPool[id];
        }

        // Simpan sebagai split
        _batchSplits[key] = rows;
        _batchSplitMeta[key] = {
            matchedItemId: lr.line.item ? String(lr.line.item.id) : null,
            matchedQty:    lr.matched || 0,
        };
        delete _batchSubstitutions[key];

        document.getElementById('batchAddPoolPanel')?.remove();
        saveState();
        renderBatchRecon();
    };
    // ─────────────────────────────────────────────────────────────────────────

    window.batchApplySubst = function (itemId, code, name, qty) {
        if (!_batchSubstTarget) return;
        const { rIdx, lIdx } = _batchSubstTarget;
        const key = _batchLineKey(_batchReconResults[rIdx], _batchReconResults[rIdx].lineResults[lIdx]);

        // Kembalikan substitusi lama ke pool (jika ada sebelumnya)
        const prev = _batchSubstitutions[key];
        if (prev) {
            const prevId = String(prev.item_id);
            if (_batchRemainingPool[prevId]) {
                _batchRemainingPool[prevId].qty += prev.qty;
            } else {
                // item pernah ada di pool tapi sudah terhapus — restore dari _batchItems
                const src = _batchItems[prevId];
                if (src) _batchRemainingPool[prevId] = { ...src, qty: prev.qty };
            }
        }

        // Kurangi pool dengan substitusi baru
        const id = String(itemId);
        if (_batchRemainingPool[id]) {
            _batchRemainingPool[id].qty -= qty;
            if (_batchRemainingPool[id].qty <= 0) delete _batchRemainingPool[id];
        }

        _batchSubstitutions[key] = { item_id: itemId, code, name, qty };
        delete _batchSplits[key];
        document.getElementById('batchSubstPanel')?.remove();
        _batchSubstTarget = null;
        saveState();
        renderBatchRecon();
    };

    // ── Inline Split ─────────────────────────────────────────────────────────
    let _batchSplitTarget = null;
    let _batchSplitRows   = []; // [{item_id,code,name,qty}]

    window.batchOpenSplit = function (rIdx, lIdx) {
        const r  = _batchReconResults[rIdx];
        const lr = r.lineResults[lIdx];
        _batchSplitTarget = { rIdx, lIdx, matchedItem: lr.line.item || null, matchedQty: lr.matched || 0 };
        _batchSplitRows   = [];
        const lineName = lr.line.item ? `${lr.line.item.code} ×${lr.needed}` : 'item belum mapping';

        const row = document.getElementById(`brecon-row-${rIdx}-${lIdx}`);
        if (!row) return;
        const old = document.getElementById('batchSubstPanel');
        if (old) old.remove();
        const old2 = document.getElementById('batchSplitPanel');
        if (old2) old2.remove();

        const panel = document.createElement('tr');
        panel.id = 'batchSplitPanel';
        panel.innerHTML = `<td colspan="4" style="padding:.5rem .3rem">
            <div style="background:#f5f3ff;border:1px solid #c4b5fd;border-radius:10px;padding:.7rem .9rem">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.45rem">
                    <div style="color:#4338ca;font-weight:800;font-size:.75rem">✂ Split "${lineName}" — pilih dari sisa pool terscan</div>
                    <button onclick="document.getElementById('batchSplitPanel').remove()"
                        style="background:transparent;border:none;color:#94a3b8;font-size:.9rem;cursor:pointer;padding:.1rem .3rem">✕</button>
                </div>
                <div id="batchSplitRows" style="display:flex;flex-direction:column;gap:.3rem;margin-bottom:.45rem"></div>
                <div style="color:#7c3aed;font-size:.7rem;font-weight:700;margin-bottom:.3rem;text-transform:uppercase;letter-spacing:.04em">📦 Sisa Pool</div>
                <div id="batchSplitPoolTiles" style="display:flex;flex-direction:column;gap:.3rem;margin-bottom:.45rem"></div>
                <div style="display:flex;align-items:center;justify-content:space-between">
                    <span id="batchSplitTotal" style="font-size:.78rem;font-weight:800;color:#64748b">Total: 0 / ${lr.needed}</span>
                    <button id="batchSplitSaveBtn" onclick="batchSaveSplit()" disabled
                        style="background:#e2e8f0;border:none;border-radius:8px;padding:.3rem .85rem;color:#94a3b8;font-weight:800;font-size:.75rem;cursor:not-allowed;transition:all .15s">Simpan Split</button>
                </div>
            </div>
        </td>`;
        row.insertAdjacentElement('afterend', panel);
        renderBatchSplitRows();
        renderSplitPoolTiles();
    };

    // Hitung pool yang tersedia untuk split:
    // = _batchRemainingPool + porsi matched dari line ini (sudah ter-match tapi milik line ini)
    // dikurangi apa yang sudah dipilih di _batchSplitRows
    function _splitWorkingPool() {
        const working = {};
        for (const [id, it] of Object.entries(_batchRemainingPool)) {
            working[id] = { ...it };
        }
        // Tambahkan matched portion dari line yang sedang di-split
        if (_batchSplitTarget?.matchedItem && _batchSplitTarget.matchedQty > 0) {
            const it  = _batchSplitTarget.matchedItem;
            const id  = String(it.id);
            if (working[id]) {
                working[id].qty += _batchSplitTarget.matchedQty;
            } else {
                working[id] = { id: it.id, code: it.code, name: it.name, qty: _batchSplitTarget.matchedQty };
            }
        }
        // Kurangi apa yang sudah ditambahkan ke baris split
        for (const row of _batchSplitRows) {
            const id = String(row.item_id);
            if (working[id]) {
                working[id].qty -= row.qty;
                if (working[id].qty <= 0) delete working[id];
            }
        }
        return working;
    }

    function renderSplitPoolTiles() {
        const container = document.getElementById('batchSplitPoolTiles');
        if (!container) return;
        const pool = Object.values(_splitWorkingPool());
        if (!pool.length) { batchGoToPhase1(); return; }
        container.innerHTML = pool.map(it => `
            <div style="display:flex;align-items:center;gap:.5rem;background:#fff;
                        border:1px solid #c4b5fd;border-radius:8px;padding:.3rem .55rem">
                <span style="font-family:monospace;color:#4338ca;font-size:.72rem;font-weight:700;flex-shrink:0">${esc(it.code)}</span>
                <span style="color:#64748b;font-size:.72rem;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(it.name)}</span>
                <span style="color:#7c3aed;font-weight:800;font-size:.72rem;flex-shrink:0">sisa ×${it.qty}</span>
                <input type="number" min="1" max="${it.qty}" value="1"
                    id="splitPoolQty_${it.id}"
                    style="width:48px;background:#f5f3ff;border:1px solid #c4b5fd;border-radius:6px;
                           padding:.2rem .3rem;font-size:.74rem;text-align:center;outline:none;flex-shrink:0">
                <button onclick="batchAddSplitFromPool(${it.id},'${esc(it.code)}','${esc(it.name)}',${it.qty})"
                    style="background:#6366f1;border:none;border-radius:7px;padding:.2rem .55rem;
                           color:#fff;font-size:.72rem;font-weight:800;cursor:pointer;flex-shrink:0">+ Tambah</button>
            </div>`).join('');
    }

    function renderBatchSplitRows() {
        const container = document.getElementById('batchSplitRows');
        if (!container || !_batchSplitTarget) return;
        const { rIdx, lIdx } = _batchSplitTarget;
        const needed = _batchReconResults[rIdx].lineResults[lIdx].needed;
        const total  = _batchSplitRows.reduce((s, r) => s + r.qty, 0);

        container.innerHTML = _batchSplitRows.length
            ? _batchSplitRows.map((sr, i) => `
                <div style="display:flex;align-items:center;gap:.4rem;background:#fff;border:1px solid #e9d5ff;border-radius:7px;padding:.3rem .5rem">
                    <span style="font-family:monospace;color:#4338ca;font-size:.72rem;font-weight:700">${esc(sr.code)}</span>
                    <span style="color:#64748b;font-size:.72rem;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(sr.name)}</span>
                    <span style="color:#0f172a;font-weight:800;font-size:.78rem">×${sr.qty}</span>
                    <button onclick="batchRemoveSplitRow(${i})"
                        style="background:#fee2e2;border:none;border-radius:5px;width:20px;height:20px;color:#ef4444;font-size:.7rem;cursor:pointer">✕</button>
                </div>`).join('')
            : '<div style="color:#94a3b8;font-size:.72rem;font-style:italic">Belum ada baris — tambah dari pool di bawah.</div>';

        const totalEl = document.getElementById('batchSplitTotal');
        const saveBtn = document.getElementById('batchSplitSaveBtn');
        const ok      = total > 0 && _batchSplitRows.length >= 1;
        const exact   = total === needed;
        if (totalEl) {
            totalEl.textContent = `Total: ${total} / ${needed}${!exact && ok ? ' ⚠ kurang' : ''}`;
            totalEl.style.color = exact ? '#16a34a' : (total > needed ? '#dc2626' : (ok ? '#d97706' : '#64748b'));
        }
        if (saveBtn) {
            saveBtn.disabled = !ok;
            saveBtn.style.background  = ok ? '#6366f1' : '#e2e8f0';
            saveBtn.style.color       = ok ? '#fff'    : '#94a3b8';
            saveBtn.style.cursor      = ok ? 'pointer' : 'not-allowed';
        }
    }

    window.batchRemoveSplitRow = function (i) {
        _batchSplitRows.splice(i, 1);
        renderBatchSplitRows();
        renderSplitPoolTiles();
    };

    window.batchAddSplitFromPool = function (itemId, code, name, maxQty) {
        const inp = document.getElementById(`splitPoolQty_${itemId}`);
        const qty = Math.min(parseInt(inp?.value) || 1, maxQty);
        _batchSplitRows.push({ item_id: itemId, code, name, qty });
        renderBatchSplitRows();
        renderSplitPoolTiles();
    };

    window.batchSaveSplit = function () {
        if (!_batchSplitTarget) return;
        const { rIdx, lIdx } = _batchSplitTarget;
        const key = _batchLineKey(_batchReconResults[rIdx], _batchReconResults[rIdx].lineResults[lIdx]);

        // Kembalikan split lama ke pool (jika ada sebelumnya)
        const prevSplit = _batchSplits[key];
        if (prevSplit) {
            for (const row of prevSplit) {
                const id = String(row.item_id);
                if (_batchRemainingPool[id]) {
                    _batchRemainingPool[id].qty += row.qty;
                } else {
                    const src = _batchItems[id];
                    if (src) _batchRemainingPool[id] = { ...src, qty: row.qty };
                }
            }
        }
        // Kembalikan substitusi lama ke pool (jika ada)
        const prevSub = _batchSubstitutions[key];
        if (prevSub) {
            const id = String(prevSub.item_id);
            if (_batchRemainingPool[id]) {
                _batchRemainingPool[id].qty += prevSub.qty;
            } else {
                const src = _batchItems[id];
                if (src) _batchRemainingPool[id] = { ...src, qty: prevSub.qty };
            }
        }

        // Kurangi pool dengan tiap baris split baru
        // Matched portion dari line ini tidak ada di _batchRemainingPool, jadi skip
        const matchedId  = _batchSplitTarget?.matchedItem ? String(_batchSplitTarget.matchedItem.id) : null;
        const matchedQty = _batchSplitTarget?.matchedQty  || 0;
        let   matchedUsed = 0; // seberapa banyak matched portion yang sudah "dipakai" dari baris split

        for (const row of _batchSplitRows) {
            const id = String(row.item_id);
            if (id === matchedId) {
                // Baris ini menggunakan matched portion — hitung berapa yang dari pool vs matched
                const fromPool    = Math.max(0, row.qty - Math.max(0, matchedQty - matchedUsed));
                matchedUsed      += Math.min(row.qty, matchedQty - matchedUsed);
                if (fromPool > 0 && _batchRemainingPool[id]) {
                    _batchRemainingPool[id].qty -= fromPool;
                    if (_batchRemainingPool[id].qty <= 0) delete _batchRemainingPool[id];
                }
            } else if (_batchRemainingPool[id]) {
                _batchRemainingPool[id].qty -= row.qty;
                if (_batchRemainingPool[id].qty <= 0) delete _batchRemainingPool[id];
            }
        }

        _batchSplits[key] = [..._batchSplitRows];
        _batchSplitMeta[key] = {
            matchedItemId: _batchSplitTarget?.matchedItem ? String(_batchSplitTarget.matchedItem.id) : null,
            matchedQty:    _batchSplitTarget?.matchedQty  || 0,
        };
        delete _batchSubstitutions[key];
        document.getElementById('batchSplitPanel')?.remove();
        _batchSplitTarget = null; _batchSplitRows = [];
        saveState();
        renderBatchRecon();
    };

    window.batchClearResolution = function (rIdx, lIdx) {
        const key = _batchLineKey(_batchReconResults[rIdx], _batchReconResults[rIdx].lineResults[lIdx]);

        // Kembalikan qty substitusi ke pool
        const sub = _batchSubstitutions[key];
        if (sub) {
            const id = String(sub.item_id);
            if (_batchRemainingPool[id]) {
                _batchRemainingPool[id].qty += sub.qty;
            } else {
                const src = _batchItems[id];
                if (src) _batchRemainingPool[id] = { ...src, qty: sub.qty };
            }
        }

        // Kembalikan tiap baris split ke pool (skip matched portion — tidak berasal dari pool)
        const spl  = _batchSplits[key];
        const meta = _batchSplitMeta[key] || {};
        if (spl) {
            let matchedLeft = meta.matchedQty || 0;
            for (const row of spl) {
                const id = String(row.item_id);
                let qtyToRestore = row.qty;
                if (id === meta.matchedItemId && matchedLeft > 0) {
                    const fromMatched = Math.min(qtyToRestore, matchedLeft);
                    matchedLeft   -= fromMatched;
                    qtyToRestore  -= fromMatched;
                }
                if (qtyToRestore > 0) {
                    if (_batchRemainingPool[id]) {
                        _batchRemainingPool[id].qty += qtyToRestore;
                    } else {
                        const src = _batchItems[id];
                        if (src) _batchRemainingPool[id] = { ...src, qty: qtyToRestore };
                    }
                }
            }
        }
        delete _batchSplitMeta[key];

        delete _batchSubstitutions[key];
        delete _batchSplits[key];
        saveState();
        renderBatchRecon();
    };

    // ── Confirm All ───────────────────────────────────────────────────────────
    window.batchConfirmAll = async function () {
        const btn = $('batchConfirmAllBtn');
        btn.disabled = true; btn.textContent = '⏳ Memproses…';

        let ok = 0, skipped = [], errors = [];

        for (const r of _batchReconResults) {
            const fId     = r.fulfillment.id;
            const orderNo = r.orderNo;

            // Blokir hanya jika ada item yang belum mapping sama sekali (item_id null)
            const hasUnmapped = r.lineResults.some(lr => lr.line.item === null);
            if (hasUnmapped) {
                const count = r.lineResults.filter(lr => lr.line.item === null).length;
                skipped.push(`${orderNo} (${count} item belum mapping)`);
                continue;
            }

            // Terapkan substitusi/split/shortage sebelum batch-confirm
            for (const lr of r.lineResults) {
                const key = _batchLineKey(r, lr);
                const sub = _batchSubstitutions[key];
                const spl = _batchSplits[key];

                // Guard: kalau ada keduanya, pakai split (lebih spesifik), abaikan sub
                if (sub && spl) {
                    console.warn(`[batch] Line ${lr.line.id}: ada sub+split sekaligus — pakai split, abaikan substitusi.`);
                }

                if (spl) {
                    try {
                        await api(`/api/fulfillments/${fId}/lines/${lr.line.id}/split`, {
                            method: 'POST',
                            body: JSON.stringify({ splits: spl.map(s => ({ item_id: s.item_id, qty: s.qty })) }),
                        });
                    } catch (e) { errors.push(`${orderNo} (split): ${e.message}`); continue; }
                } else if (sub) {
                    try {
                        await api(`/api/fulfillments/${fId}/lines/${lr.line.id}`, {
                            method: 'PATCH',
                            body: JSON.stringify({ item_id: sub.item_id, qty_fulfilled: sub.qty }),
                        });
                    } catch (e) { errors.push(`${orderNo} (ganti item): ${e.message}`); continue; }
                } else if (lr.status !== 'ok' && lr.matched < lr.needed) {
                    // Shortage tanpa sub/split — update qty_fulfilled ke jumlah yang benar-benar terscan
                    try {
                        await api(`/api/fulfillments/${fId}/lines/${lr.line.id}`, {
                            method: 'PATCH',
                            body: JSON.stringify({ qty_fulfilled: lr.matched }),
                        });
                    } catch (e) { errors.push(`${orderNo} (kurang): ${e.message}`); continue; }
                }
            }

            // Batch-confirm: potong stok + mark picked + langsung confirmed
            try {
                await api(`/api/fulfillments/${fId}/batch-confirm`, { method: 'POST' });
                ok++;
            } catch (e) {
                errors.push(`${orderNo}: ${e.message}`);
            }
        }

        // Reset state
        _batchItems = {}; _batchOrders = []; _batchReconResults = [];
        _batchSubstitutions = {}; _batchSplits = {}; _batchSplitMeta = {}; _batchRemainingPool = {};
        clearState();
        renderBatchItemList(); renderBatchOrderList();
        batchShowPhase(1);
        loadFulfillments();

        // Feedback
        const parts = [];
        if (ok)             parts.push(`✓ ${ok} order dikonfirmasi`);
        if (skipped.length) parts.push(`⏭ ${skipped.length} dilewati:\n  • ${skipped.join('\n  • ')}`);
        if (errors.length)  parts.push(`✗ ${errors.length} error:\n  • ${errors.join('\n  • ')}`);

        if (skipped.length || errors.length) {
            alert(parts.join('\n\n'));
        } else {
            const sr = $('batchItemResult');
            if (sr) { sr.style.color = '#4ade80'; sr.textContent = `✓ ${ok} order batch selesai. Stok dipotong.`; }
        }
    };

    window.loadFulfillments = loadFulfillments;

    // ── [DEV ONLY] Dev panel functions ───────────────────────────────────────
    async function devLoadStatsHere() {
        try {
            const s  = await (await fetch('/api/dev/stats')).json();
            const el = document.getElementById('devStatsFulfillment');
            if (el) el.textContent =
                `📦 ${s.orders} orders  |  ⚡ ${s.perluProses} perlu proses  |  🔄 ${s.sedangPacking} packing  |  ✅ ${s.fulfilled} selesai`;
        } catch {}
    }

    async function devLoadNextOrder() {
        try {
            const res  = await fetch('/api/dev/next-order');
            const data = await res.json();
            if (!data.order) { alert('Tidak ada order yang perlu diproses.\nKlik "Seed Orders" untuk buat order baru.'); return; }
            const o = data.order;
            // Masuk ke Single Order mode dan isi scan box
            setScanMode('order');
            const inp = document.getElementById('scanOrderInput');
            if (inp) {
                inp.value = o.channel_order_id;
                inp.dispatchEvent(new Event('input'));
                // Auto trigger scan
                setTimeout(() => {
                    const btn = document.getElementById('scanOrderBtn');
                    if (btn) btn.click();
                }, 100);
            }
            devLoadStatsHere();
        } catch (e) { alert('Error: ' + e.message); }
    }

    async function devSeedFromFulfillment() {
        const count = parseInt(window.prompt('Buat berapa dummy order?', '3'), 10);
        if (!count || count < 1) return;
        try {
            const res  = await fetch('/api/dev/seed-orders', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ count, status: 'READY_TO_SHIP' }),
            });
            const data = await res.json();
            alert(data.message);
            devLoadStatsHere();
        } catch (e) { alert('Error: ' + e.message); }
    }

    async function devResetFulfillmentsHere() {
        if (!confirm('Hapus semua fulfillments? Order tetap ada.')) return;
        try {
            const res  = await fetch('/api/dev/reset-fulfillments', { method: 'POST' });
            const data = await res.json();
            clearState();
            alert(data.message);
            loadFulfillments();
            devLoadStatsHere();
        } catch (e) { alert('Error: ' + e.message); }
    }

    async function devFreshFromFulfillment() {
        if (!confirm('⚠️ Hapus SEMUA orders + fulfillments?')) return;
        try {
            const res  = await fetch('/api/dev/fresh-orders', { method: 'POST' });
            const data = await res.json();
            clearState();
            alert(data.message);
            loadFulfillments();
            devLoadStatsHere();
        } catch (e) { alert('Error: ' + e.message); }
    }

    // ── [DEV ONLY] Bypass blocker ─────────────────────────────────────────────
    window._devBypassBlocker = false;
    function devToggleBypass() {
        window._devBypassBlocker = !window._devBypassBlocker;
        const btn = document.getElementById('btnBypassBlocker');
        if (btn) {
            btn.textContent = `🚧 Bypass Blocker: ${window._devBypassBlocker ? 'ON ✓' : 'OFF'}`;
            btn.style.background    = window._devBypassBlocker ? '#fef9c3' : '#fefce8';
            btn.style.borderColor   = window._devBypassBlocker ? '#fbbf24' : '#fde68a';
            btn.style.color         = window._devBypassBlocker ? '#713f12' : '#92400e';
        }
        // Re-evaluate blocker state
        loadFulfillments();
    }
    window.devToggleBypass = devToggleBypass;

    async function devRemapItemsHere() {
        const ok = confirm('🔁 Re-resolve semua mapping_status item?\n\nItem lama yang mapping_status = null akan di-update.\nProses ini bisa butuh beberapa detik.');
        if (!ok) return;
        try {
            const res  = await fetch('/api/marketplace/remap-items', { method: 'POST' });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal');
            alert(`✅ Remap selesai.\nUpdated: ${data.updated ?? '?'} item\nErrors: ${data.errors ?? 0}\n\nSekarang cek /marketplace/issues — data harusnya sudah muncul.`);
            devLoadStatsHere();
        } catch (e) { alert('Error: ' + e.message); }
    }

    window.devLoadNextOrder          = devLoadNextOrder;
    window.devSeedFromFulfillment    = devSeedFromFulfillment;
    window.devResetFulfillmentsHere  = devResetFulfillmentsHere;
    window.devFreshFromFulfillment   = devFreshFromFulfillment;
    window.devRemapItemsHere         = devRemapItemsHere;

    // Load stats saat halaman buka (dev only)
    devLoadStatsHere();
    // ─────────────────────────────────────────────────────────────────────────

    loadState(); // restore persisted state, falls back to setScanMode('batch')
    loadFulfillments();
})();
</script>
@endpush
