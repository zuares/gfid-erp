@extends('layouts.app')
@section('title', 'Buat Shipment Baru')

@push('head')
<style>
/* ── KPI grid ──────────────────────────────────────────────── */
.shp-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .65rem;
    margin-bottom: 1rem;
}
.shp-kpi-card {
    border: 1px solid rgba(15,23,42,.075);
    border-radius: 16px;
    padding: .82rem .9rem;
    background: linear-gradient(180deg, var(--card, #fff) 0%, var(--card, #fcfcfd) 100%);
}
.shp-kpi-label { color: #64748b; font-size: .66rem; font-weight: 950; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .18rem; }
.shp-kpi-value { color: #0f172a; font-size: 1.25rem; font-weight: 950; line-height: 1.15; letter-spacing: -.02em; }
.shp-kpi-note  { color: #94a3b8; font-size: .7rem; font-weight: 800; margin-top: .2rem; }

/* ── Phase bar ─────────────────────────────────────────────── */
.shp-phase-bar {
    display: flex;
    gap: .3rem;
    align-items: center;
    margin-bottom: 1rem;
}
.shp-step {
    padding: .25rem .75rem;
    border-radius: 999px;
    background: #f1f5f9;
    color: #94a3b8;
    font-size: .73rem;
    font-weight: 700;
    transition: all .2s;
}
.shp-step.active {
    background: #6366f1;
    color: #fff;
    font-size: .78rem;
    font-weight: 800;
    box-shadow: 0 1px 6px rgba(99,102,241,.35);
}
.shp-step.done {
    background: #d1fae5;
    color: #065f46;
}

/* ── Card shell ────────────────────────────────────────────── */
.shp-card {
    background: var(--card, #fff);
    border: 1px solid var(--gf-border, #e5e7eb);
    border-radius: 22px;
    padding: 1.5rem 1.75rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 6px rgba(15,23,42,.07);
}

/* ── Scan box ──────────────────────────────────────────────── */
.shp-scan-wrap {
    display: flex;
    gap: .5rem;
    align-items: center;
    background: #f5f3ff;
    border-radius: 14px;
    padding: .5rem .6rem;
}
.shp-scan-wrap input {
    flex: 1;
    background: transparent;
    border: none;
    padding: .2rem .3rem;
    color: #0f172a;
    font-size: 1rem;
    font-weight: 700;
    font-family: monospace;
    outline: none;
}
.shp-scan-wrap input::placeholder {
    color: #94a3b8;
    font-weight: 400;
    font-family: inherit;
}
.shp-scan-btn {
    background: #6366f1;
    border: none;
    border-radius: 10px;
    padding: .55rem 1.2rem;
    color: #fff;
    font-weight: 700;
    font-size: .88rem;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(99,102,241,.3);
    transition: background .15s;
    white-space: nowrap;
}
.shp-scan-btn:hover { background: #4f46e5; }
.shp-scan-btn:disabled { background: #c7d2fe; cursor: not-allowed; box-shadow: none; }

/* ── Lines table ───────────────────────────────────────────── */
.shp-lines-scroll {
    max-height: 32vh;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(148,163,184,.5) transparent;
}
.shp-lines-scroll::-webkit-scrollbar { width: 5px; }
.shp-lines-scroll::-webkit-scrollbar-thumb { background: rgba(148,163,184,.5); border-radius: 99px; }

.shp-table { margin-bottom: 0; }
.shp-table thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: var(--card, #f9fafb);
    font-size: .7rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #6b7280;
    border-bottom-width: 1px;
    padding-block: .4rem;
}
.shp-table tbody td { vertical-align: middle; padding-block: .28rem; }
.shp-table tbody tr.row-new td { background: rgba(254,243,199,.85) !important; }

/* ── Qty pill ──────────────────────────────────────────────── */
.shp-qty-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 52px;
    padding: .18rem .55rem;
    border-radius: 999px;
    border: 1px solid rgba(148,163,184,.5);
    font-weight: 600;
    font-size: .88rem;
    cursor: pointer;
    transition: background .12s;
    user-select: none;
}
.shp-qty-pill:hover { background: #eff6ff; box-shadow: 0 0 0 1px rgba(99,102,241,.2); }

/* ── Shipment meta bar ─────────────────────────────────────── */
.shp-meta-bar {
    display: flex;
    align-items: center;
    gap: .6rem;
    flex-wrap: wrap;
    padding-bottom: .85rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid var(--gf-border, #e5e7eb);
}
.shp-meta-code  { font-weight: 900; font-size: 1.05rem; color: #0f172a; letter-spacing: -.01em; }
.shp-meta-store {
    display: inline-flex;
    align-items: center;
    padding: .18rem .6rem;
    border-radius: 999px;
    border: 1px solid rgba(148,163,184,.6);
    font-size: .75rem;
    font-weight: 700;
    color: #475569;
}
.shp-meta-date { color: #94a3b8; font-size: .8rem; }

/* ── Toast ─────────────────────────────────────────────────── */
.shp-toast {
    position: fixed;
    top: 4rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1090;
    min-width: 200px;
    max-width: 340px;
    border-radius: 999px;
    padding: .5rem .9rem;
    font-size: .82rem;
    display: none;
    align-items: center;
    gap: .4rem;
    box-shadow: 0 12px 30px rgba(15,23,42,.35);
    pointer-events: none;
    white-space: nowrap;
}
.shp-toast-ok  { background: #16a34a; color: #ecfdf5; }
.shp-toast-err { background: #b91c1c; color: #fee2e2; }

@media (max-width: 576px) {
    .shp-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .shp-card { padding: 1rem 1.1rem; border-radius: 16px; }
}

/* Compact neutral layout, aligned with shipment edit */
:root {
    --shp-accent: #334155;
    --shp-accent-2: #1f2937;
    --shp-accent-bg: rgba(148,163,184,.08);
    --shp-accent-ring: rgba(148,163,184,.18);
}
.shp-wrap {
    max-width: 1040px;
    margin-inline: auto;
    padding: .75rem .75rem 4rem;
    background: transparent !important;
}
.shp-topbar {
    position: sticky;
    top: 0;
    z-index: 300;
    display: flex;
    align-items: center;
    gap: .45rem;
    flex-wrap: wrap;
    padding: .45rem .75rem;
    background: var(--card, #fff);
    border-bottom: 1px solid rgba(148,163,184,.18);
}
body[data-theme="dark"] .shp-topbar {
    background: var(--card, #0f172a);
}
.shp-topbar-code {
    font-weight: 900;
    font-size: .95rem;
    letter-spacing: 0;
    white-space: nowrap;
}
.shp-topbar-spacer {
    flex: 1;
    min-width: .5rem;
}
.shp-badge,
.shp-pill,
.shp-step,
.shp-meta-store {
    border-radius: 7px;
    letter-spacing: 0;
    text-transform: none;
    box-shadow: none !important;
}
.shp-badge,
.shp-pill {
    padding: .18rem .48rem;
    font-size: .68rem;
    background: transparent !important;
    color: #64748b !important;
    border: 1px solid rgba(148,163,184,.28) !important;
    white-space: nowrap;
}
.shp-pill-accent,
.shp-step.active,
.shp-step.done {
    color: #334155 !important;
    background: transparent !important;
    border-color: rgba(148,163,184,.28) !important;
}
.btn-shp-outline,
.shp-scan-btn,
#setupBtn,
#submitForm button,
#phase2Card button,
#exportBtn {
    border-radius: 7px !important;
    letter-spacing: 0;
    text-transform: none;
    box-shadow: none !important;
}
.btn-shp-outline,
#phase2Card button,
#exportBtn {
    padding: .28rem .62rem !important;
    font-size: .74rem !important;
    color: #475569 !important;
    background: transparent !important;
    border: 1px solid rgba(148,163,184,.35) !important;
}
.btn-shp-outline:hover,
#phase2Card button:hover,
#exportBtn:hover {
    background: rgba(148,163,184,.08) !important;
    color: #111827 !important;
}
#setupBtn,
.shp-scan-btn,
#submitForm button {
    background: #334155 !important;
    border: 1px solid #334155 !important;
    color: #fff !important;
}
#setupBtn:hover,
.shp-scan-btn:hover,
#submitForm button:hover {
    background: #1f2937 !important;
    border-color: #1f2937 !important;
}
.shp-kpi-grid {
    gap: .45rem;
    margin: .55rem 0;
}
.shp-kpi-card,
.shp-card {
    border-radius: 8px;
    box-shadow: none !important;
    background: var(--card, #fff);
    border: 1px solid rgba(148,163,184,.18);
}
.shp-kpi-card {
    padding: .55rem .7rem;
}
.shp-kpi-label {
    font-size: .6rem;
    letter-spacing: .02em;
    margin-bottom: .18rem;
}
.shp-kpi-value {
    font-size: 1.05rem;
    color: #334155;
}
.shp-kpi-note {
    display: none;
}
.shp-phase-bar {
    margin: .55rem 0;
    gap: .28rem;
}
.shp-step {
    padding: .18rem .52rem;
    font-size: .7rem;
    background: transparent;
    border: 1px solid rgba(148,163,184,.25);
}
.shp-card {
    padding: .85rem;
    margin-bottom: .65rem;
}
.shp-card [style*="border-left:3px"] {
    border-left: 0 !important;
    padding-left: 0 !important;
    margin-bottom: .75rem !important;
}
.form-control,
.form-control-sm {
    border-radius: 8px;
    border-color: rgba(148,163,184,.35);
    box-shadow: none !important;
}
.form-control:focus,
.form-control-sm:focus {
    border-color: rgba(71,85,105,.75);
    box-shadow: none !important;
}
.shp-scan-wrap {
    background: transparent;
    border: 1px solid rgba(148,163,184,.22);
    border-radius: 8px;
    padding: .45rem;
}
.shp-scan-wrap:focus-within {
    border-color: rgba(100,116,139,.55);
}
.shp-scan-wrap input {
    font-size: 1.25rem;
    letter-spacing: .08em;
}
.shp-lines-scroll {
    max-height: 50vh;
}
.shp-table thead th {
    background: var(--card, #fff);
    letter-spacing: .03em;
}
.shp-qty-pill {
    border-radius: 7px;
    box-shadow: none !important;
}
.shp-toast {
    border-radius: 8px;
    box-shadow: none;
}
.shp-card [style*="color:#4338ca"] {
    color: #334155 !important;
}
.shp-card [style*="color:#94a3b8"] {
    color: #64748b !important;
}
.del-btn,
.qty-save {
    border-radius: 7px !important;
    box-shadow: none !important;
}
.del-btn {
    min-width: 46px;
    padding: .25rem .45rem !important;
    font-size: .72rem !important;
}
@media (max-width: 768px) {
    .shp-wrap {
        padding: .5rem .5rem 5rem;
    }
    .shp-topbar {
        padding: .5rem;
        gap: .38rem;
    }
    .shp-topbar-code {
        flex: 1 1 auto;
        min-width: 145px;
        font-size: 1.05rem;
    }
    .shp-topbar-spacer,
    .shp-badge,
    .shp-topbar > .shp-pill:not(.shp-pill-accent) {
        display: none !important;
    }
    .shp-pill,
    .btn-shp-outline {
        min-height: 38px;
        font-size: .82rem !important;
    }
    .shp-kpi-grid,
    .shp-phase-bar {
        display: none;
    }
    .shp-card {
        padding: .7rem;
        border-radius: 8px;
        margin-bottom: .5rem;
    }
    #phase1Card label {
        font-size: .72rem !important;
        letter-spacing: .03em !important;
    }
    #setupBtn,
    #submitForm button {
        width: 100%;
        min-height: 44px;
    }
    .shp-meta-bar {
        gap: .4rem;
        padding-bottom: .55rem;
        margin-bottom: .65rem;
    }
    .shp-meta-code {
        width: 100%;
        font-size: 1rem;
    }
    .shp-scan-wrap input {
        min-height: 46px;
        font-size: 1.35rem;
        letter-spacing: .06em;
    }
    .shp-scan-btn {
        min-height: 42px;
        padding-inline: .8rem;
    }
    .shp-lines-scroll {
        max-height: 45vh;
    }
}
</style>
@endpush

@section('content')
<div class="shp-topbar">
    <span class="shp-topbar-code" id="topShipCode">Shipment Baru</span>
    <span class="shp-badge" id="topStatus">Setup</span>
    <span class="shp-topbar-spacer"></span>
    <span class="shp-pill">Baris <b id="topLines">0</b></span>
    <span class="shp-pill shp-pill-accent">Qty <b id="topQty">0</b></span>
    <a href="{{ route('sales.shipments.index') }}" class="btn-shp-outline" style="text-decoration:none">
        Daftar Shipment
    </a>
</div>

<div class="shp-wrap">

    {{-- ── KPI ──────────────────────────────────────────────────────────── --}}
    <div class="shp-kpi-grid">
        <div class="shp-kpi-card">
            <div class="shp-kpi-label">Dibuat Hari Ini</div>
            <div class="shp-kpi-value">{{ $kpi['created'] }}</div>
            <div class="shp-kpi-note">total shipment</div>
        </div>
        <div class="shp-kpi-card">
            <div class="shp-kpi-label">Item Keluar</div>
            <div class="shp-kpi-value">{{ number_format($kpi['qty'], 0, ',', '.') }}</div>
            <div class="shp-kpi-note">qty total hari ini</div>
        </div>
        <div class="shp-kpi-card">
            <div class="shp-kpi-label">Masih Draft</div>
            <div class="shp-kpi-value">{{ $kpi['draft'] }}</div>
            <div class="shp-kpi-note">belum di-submit</div>
        </div>
        <div class="shp-kpi-card">
            <div class="shp-kpi-label">Selesai</div>
            <div class="shp-kpi-value">{{ $kpi['posted'] }}</div>
            <div class="shp-kpi-note">stok sudah berkurang</div>
        </div>
    </div>

    {{-- ── Phase bar ────────────────────────────────────────────────────── --}}
    <div class="shp-phase-bar">
        <span class="shp-step active" id="pStep1">Setup</span>
        <span class="shp-step" id="pStep2">Scan Barang</span>
        <span class="shp-step" id="pStep3">Selesai</span>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- PHASE 1 — Setup                                                    --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div class="shp-card" id="phase1Card">

        <div style="border-left:3px solid #6366f1;padding-left:1rem;margin-bottom:1.4rem">
            <div style="color:#4338ca;font-weight:800;font-size:.9rem;margin-bottom:.15rem">Setup Shipment</div>
            <div style="color:#94a3b8;font-size:.72rem">Isi data singkat, lalu lanjut scan barang</div>
        </div>

        <form id="setupForm" autocomplete="off">
            @csrf
            {{-- hidden: diisi JS dari hasil lookup --}}
            <input type="hidden" name="store_id"         id="hiddenStoreId">
            <input type="hidden" name="sales_invoice_id" id="hiddenInvoiceId"
                   value="{{ !empty($invoice) ? $invoice->id : '' }}">

            <div class="row g-3">
                {{-- No Pesanan lookup --}}
                <div class="col-md-6">
                    <label style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;display:block;margin-bottom:.3rem">
                        No Pesanan / Invoice <span style="color:#94a3b8;font-weight:400">(opsional)</span>
                    </label>
                    <div style="position:relative">
                        <input type="text" id="orderInput"
                               class="form-control form-control-sm"
                               placeholder="Ketik no order atau kode invoice"
                               autocomplete="off" spellcheck="false"
                               value="{{ !empty($invoice) ? ($invoice->channel_order_no ?? $invoice->code) : '' }}">
                        <span id="lookupSpinner"
                              style="display:none;position:absolute;right:.6rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.8rem">
                            ...
                        </span>
                    </div>

                    {{-- Result card — found --}}
                    <div id="lookupFound"
                         style="display:none;margin-top:.55rem;padding:.6rem .85rem;border-radius:10px;
                                background:#f0fdf4;border:1px solid #bbf7d0;font-size:.8rem">
                        <div style="font-weight:800;color:#15803d;margin-bottom:.2rem">
                            <span id="lookupStoreName">—</span>
                        </div>
                        <div style="color:#374151">
                            <span id="lookupInvoiceCode" style="font-family:monospace;font-weight:700"></span>
                            <span id="lookupOrderNo" style="color:#6b7280"></span>
                            <span id="lookupDate" style="color:#9ca3af;margin-left:.4rem"></span>
                        </div>
                    </div>

                    {{-- Result card — not found --}}
                    <div id="lookupNotFound"
                         style="display:none;margin-top:.55rem;padding:.5rem .85rem;border-radius:10px;
                                background:#fef9c3;border:1px solid #fde68a;font-size:.79rem;color:#78350f">
                        No pesanan tidak ditemukan. Shipment tetap bisa dibuat.
                        <span style="color:#94a3b8">Rekonsiliasi bisa dilakukan nanti.</span>
                    </div>

                    {{-- Hint: no input --}}
                    <div id="lookupHint"
                         style="margin-top:.4rem;font-size:.72rem;color:#94a3b8">
                        Opsional, bisa rekonsiliasi nanti.
                    </div>
                </div>

                {{-- Date --}}
                <div class="col-md-3">
                    <label style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;display:block;margin-bottom:.3rem">
                        Tanggal
                    </label>
                    <input type="date" name="date" id="dateInput" class="form-control form-control-sm"
                        value="{{ now()->toDateString() }}" required>
                </div>

                {{-- Notes --}}
                <div class="col-md-3">
                    <label style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;display:block;margin-bottom:.3rem">
                        Catatan <span style="color:#94a3b8">(opsional)</span>
                    </label>
                    <input type="text" name="notes" class="form-control form-control-sm"
                        placeholder="Catatan tambahan">
                </div>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.4rem;flex-wrap:wrap;gap:.5rem">
                <div id="setupError" style="color:#dc2626;font-size:.8rem;display:none"></div>
                <div style="margin-left:auto;display:flex;gap:.75rem;align-items:center">
                    <span id="setupSpinner" style="display:none;color:#94a3b8;font-size:.78rem">Membuat shipment...</span>
                    <button type="submit" id="setupBtn"
                        style="background:#6366f1;border:none;border-radius:12px;padding:.6rem 1.6rem;
                               color:#fff;font-weight:800;font-size:.9rem;cursor:pointer;
                               box-shadow:0 3px 12px rgba(99,102,241,.35);transition:background .15s">
                        Mulai Scan
                    </button>
                </div>
            </div>
        </form>
    </div>{{-- /phase1Card --}}

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- PHASE 2 — Scan barang                                              --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div class="shp-card" id="phase2Card" style="display:none">

        {{-- Shipment meta --}}
        <div class="shp-meta-bar">
            <span class="shp-meta-code" id="shipCode">—</span>
            <span class="shp-meta-store" id="shipStore" style="display:none">—</span>
            <span class="shp-meta-date" id="shipDate">—</span>
            <span id="shipInvoice" style="display:none;font-size:.75rem;color:#6b7280;font-family:monospace"></span>
            <div style="margin-left:auto">
                <button onclick="backToSetup()"
                    style="background:transparent;border:1px solid #e2e8f0;border-radius:999px;
                           padding:.25rem .8rem;color:#64748b;font-size:.73rem;font-weight:600;cursor:pointer">
                    Ubah Setup
                </button>
            </div>
        </div>

        {{-- Scan box --}}
        <div style="border-left:3px solid #6366f1;padding-left:1rem;margin-bottom:1.1rem">
            <div style="color:#4338ca;font-weight:800;font-size:.88rem;margin-bottom:.15rem">
                Scan Barang
            </div>
            <div style="color:#94a3b8;font-size:.72rem;margin-bottom:.65rem">
                Scan atau ketik kode item
            </div>
            <div class="shp-scan-wrap">
                <input id="scanInput" type="text"
                    placeholder="Contoh: KAO-M-RED"
                    autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                    onkeydown="if(event.key==='Enter'){event.preventDefault();doScan()}"
                    oninput="this.value=this.value.toUpperCase()">
                <button class="shp-scan-btn" id="scanBtn" onclick="doScan()">Tambah</button>
            </div>
            <div id="scanStatus" style="margin-top:.4rem;font-size:.78rem;min-height:1.1rem;padding:0 .2rem;color:#94a3b8"></div>
        </div>

        {{-- Items table --}}
        <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
                <div style="color:#6b7280;font-size:.67rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase">
                    Item Terscan
                </div>
                <span style="font-size:.78rem;color:#64748b">
                    Baris: <strong id="totalLines">0</strong>
                    &nbsp;|&nbsp;
                    Total Qty: <strong id="totalQty">0</strong>
                </span>
            </div>

            <div class="shp-lines-scroll">
                <table class="table table-sm align-middle shp-table">
                    <thead>
                        <tr>
                            <th style="width:34px">#</th>
                            <th style="width:130px">Kode</th>
                            <th class="d-none d-md-table-cell">Nama Barang</th>
                            <th style="width:120px" class="text-end">Qty</th>
                            <th style="width:46px"></th>
                        </tr>
                    </thead>
                    <tbody id="linesTbody">
                        <tr id="emptyRow">
                            <td colspan="5" class="text-center text-muted py-4" style="font-size:.82rem">
                                Belum ada item yang discan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Footer actions --}}
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;
                    padding-top:.85rem;margin-top:.75rem;border-top:1px solid var(--gf-border,#e5e7eb)">
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                <button onclick="clearAllLines()"
                    style="background:transparent;border:1px solid #fca5a5;border-radius:999px;
                           padding:.3rem .85rem;color:#ef4444;font-size:.75rem;font-weight:700;cursor:pointer">
                    Bersihkan
                </button>
                <a id="exportBtn" href="#" target="_blank"
                    style="display:inline-flex;align-items:center;
                           border:1px solid #e2e8f0;border-radius:999px;
                           padding:.3rem .85rem;color:#64748b;font-size:.75rem;font-weight:600;
                           text-decoration:none;background:transparent">
                    Export CSV
                </a>
            </div>

            <form id="submitForm" method="POST" action=""
                onsubmit="return confirm('Submit shipment ini dan potong stok WH-RTS?')">
                @csrf
                <button type="submit"
                    style="background:#16a34a;border:none;border-radius:12px;padding:.6rem 1.6rem;
                           color:#fff;font-size:.9rem;font-weight:800;cursor:pointer;
                           box-shadow:0 3px 12px rgba(22,163,74,.35);transition:background .15s"
                    onmouseover="this.style.background='#15803d'"
                    onmouseout="this.style.background='#16a34a'">
                    Simpan &amp; Kurangi Stok
                </button>
            </form>
        </div>
    </div>{{-- /phase2Card --}}

</div>

{{-- Toast --}}
<div id="shpToast" class="shp-toast"></div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const CSRF = (document.querySelector('meta[name="csrf-token"]') || {}).content
              || @json(csrf_token());

    let _ship = null; // shipment JSON from server after create

    // ── Audio feedback ─────────────────────────────────────────────────────

    function playBeep(freq, dur = 0.14, vol = 0.18) {
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            const ctx = new Ctx();
            const osc = ctx.createOscillator();
            const g   = ctx.createGain();
            osc.type = 'sine'; osc.frequency.value = freq;
            osc.connect(g); g.connect(ctx.destination);
            g.gain.setValueAtTime(vol, ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + dur);
            osc.start(); osc.stop(ctx.currentTime + dur);
        } catch {}
    }
    const beepOk  = () => playBeep(1046);
    const beepErr = () => playBeep(220, 0.18, 0.25);

    // ── Toast ──────────────────────────────────────────────────────────────

    const toastEl = document.getElementById('shpToast');
    function showToast(type, msg) {
        if (!toastEl) return;
        toastEl.className = 'shp-toast ' + (type === 'ok' ? 'shp-toast-ok' : 'shp-toast-err');
        toastEl.textContent = msg;
        toastEl.style.display = 'flex';
        toastEl.style.opacity  = '1';
        clearTimeout(toastEl._t);
        toastEl._t = setTimeout(() => {
            toastEl.style.transition = 'opacity .3s';
            toastEl.style.opacity = '0';
            setTimeout(() => {
                toastEl.style.display = 'none';
                toastEl.style.opacity = '1';
                toastEl.style.transition = '';
            }, 320);
        }, 1500);
    }

    // ── Status line under scan input ───────────────────────────────────────

    function setScanStatus(msg, color) {
        const el = document.getElementById('scanStatus');
        if (el) { el.textContent = msg; el.style.color = color || '#94a3b8'; }
    }

    // ── Totals ─────────────────────────────────────────────────────────────

    function updateTotals(lines, qty) {
        const lEl = document.getElementById('totalLines');
        const qEl = document.getElementById('totalQty');
        const topLines = document.getElementById('topLines');
        const topQty = document.getElementById('topQty');
        if (lEl) lEl.textContent = lines ?? 0;
        if (qEl) qEl.textContent = new Intl.NumberFormat('id-ID').format(qty ?? 0);
        if (topLines) topLines.textContent = lines ?? 0;
        if (topQty) topQty.textContent = new Intl.NumberFormat('id-ID').format(qty ?? 0);
    }

    function renumber() {
        let i = 1;
        document.querySelectorAll('#linesTbody tr[data-line-id]').forEach(r => {
            const c = r.querySelector('.row-num');
            if (c) c.textContent = i++;
        });
    }

    // ── Phase stepper ──────────────────────────────────────────────────────

    function setPhase(n) {
        const topStatus = document.getElementById('topStatus');
        if (topStatus) {
            topStatus.textContent = n === 1 ? 'Setup' : (n === 2 ? 'Scan' : 'Selesai');
        }
        ['pStep1', 'pStep2', 'pStep3'].forEach((id, idx) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.remove('active', 'done');
            if (idx + 1 < n) el.classList.add('done');
            else if (idx + 1 === n) el.classList.add('active');
        });
    }

    function showP1() {
        document.getElementById('phase1Card').style.display = '';
        document.getElementById('phase2Card').style.display = 'none';
        setPhase(1);
    }

    function showP2() {
        document.getElementById('phase1Card').style.display = 'none';
        document.getElementById('phase2Card').style.display = '';
        setPhase(2);
        setTimeout(() => { const si = document.getElementById('scanInput'); if (si) { si.focus(); si.select(); } }, 80);
    }

    // ── Back to setup ──────────────────────────────────────────────────────

    window.backToSetup = function () {
        if (_ship) {
            const msg = 'Kembali ke setup?\n\nShipment ' + _ship.code + ' sudah dibuat dan bisa dilanjut dari halaman Daftar Shipment.';
            if (!confirm(msg)) return;
        }
        _ship = null;
        resetLinesUI();
        // Re-enable setup button
        const sb = document.getElementById('setupBtn');
        if (sb) { sb.disabled = false; sb.style.background = ''; }
        const ss = document.getElementById('setupSpinner');
        if (ss) ss.style.display = 'none';
        showP1();
    };

    function resetLinesUI() {
        const tbody = document.getElementById('linesTbody');
        if (tbody) tbody.innerHTML = emptyRowHtml();
        updateTotals(0, 0);
        setScanStatus('');
    }

    function emptyRowHtml() {
        return '<tr id="emptyRow"><td colspan="5" class="text-center text-muted py-4" style="font-size:.82rem">Belum ada item yang discan.</td></tr>';
    }

    // ── No Pesanan lookup ─────────────────────────────────────────────────

    const LOOKUP_URL   = '{{ parse_url(route("sales.shipments.invoice_lookup"), PHP_URL_PATH) }}';
    const orderInput   = document.getElementById('orderInput');
    const hiddenStore  = document.getElementById('hiddenStoreId');
    const hiddenInv    = document.getElementById('hiddenInvoiceId');
    const lookupFound  = document.getElementById('lookupFound');
    const lookupNotF   = document.getElementById('lookupNotFound');
    const lookupHint   = document.getElementById('lookupHint');
    const lookupSpin   = document.getElementById('lookupSpinner');

    function clearLookupResult() {
        if (hiddenStore)  hiddenStore.value  = '';
        if (hiddenInv)    hiddenInv.value    = '';
        if (lookupFound)  lookupFound.style.display  = 'none';
        if (lookupNotF)   lookupNotF.style.display   = 'none';
        if (lookupHint)   lookupHint.style.display   = '';
    }

    function applyLookupResult(data) {
        if (lookupHint)  lookupHint.style.display  = 'none';
        if (lookupNotF)  lookupNotF.style.display  = 'none';
        if (lookupFound) lookupFound.style.display = '';

        document.getElementById('lookupStoreName').textContent = data.store_name || '—';
        document.getElementById('lookupInvoiceCode').textContent = data.code || '';
        const orderSpan = document.getElementById('lookupOrderNo');
        if (orderSpan) orderSpan.textContent = data.channel_order_no ? ' · ' + data.channel_order_no : '';
        const dateSpan = document.getElementById('lookupDate');
        if (dateSpan) dateSpan.textContent = data.date ? fmtDate(data.date) : '';

        if (hiddenStore) hiddenStore.value = data.store_id || '';
        if (hiddenInv)   hiddenInv.value   = data.id || '';

        // Auto-fill date from invoice date if user hasn't changed it
        const dateInput = document.getElementById('dateInput');
        if (dateInput && data.date && dateInput.value === new Date().toISOString().slice(0,10)) {
            dateInput.value = data.date;
        }
    }

    let lookupDebounce = null;

    if (orderInput) {
        // Init: if pre-filled (from query param invoice), trigger lookup
        @if (!empty($invoice))
        (async function() {
            try {
                const res  = await fetch(LOOKUP_URL + '?q=' + encodeURIComponent('{{ $invoice->channel_order_no ?? $invoice->code ?? "" }}'));
                const data = await res.json();
                if (data.found) applyLookupResult(data);
            } catch {}
        })();
        @endif

        orderInput.addEventListener('input', function () {
            const q = this.value.trim();
            clearTimeout(lookupDebounce);

            if (!q) {
                clearLookupResult();
                return;
            }
            if (q.length < 3) return;

            if (lookupSpin) lookupSpin.style.display = '';

            lookupDebounce = setTimeout(async function () {
                try {
                    const res  = await fetch(LOOKUP_URL + '?q=' + encodeURIComponent(q));
                    const data = await res.json();
                    if (lookupSpin) lookupSpin.style.display = 'none';

                    if (data.found) {
                        applyLookupResult(data);
                    } else {
                        clearLookupResult();
                        if (lookupHint) lookupHint.style.display = 'none';
                        if (lookupNotF) lookupNotF.style.display = '';
                    }
                } catch {
                    if (lookupSpin) lookupSpin.style.display = 'none';
                }
            }, 400);
        });

        orderInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); /* jangan submit form */ }
        });
    }

    // ── Phase 1: Setup form ────────────────────────────────────────────────

    const setupForm = document.getElementById('setupForm');

    if (setupForm) {
        setupForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const btn = document.getElementById('setupBtn');
            const spinner = document.getElementById('setupSpinner');
            const errEl = document.getElementById('setupError');

            if (btn) { btn.disabled = true; btn.style.background = ''; }
            if (spinner) spinner.style.display = 'inline';
            if (errEl) errEl.style.display = 'none';

            try {
                const res = await fetch('{{ parse_url(route("sales.shipments.store"), PHP_URL_PATH) }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(setupForm),
                });

                let data;
                try { data = await res.json(); } catch { throw new Error('Response tidak valid.'); }

                if (!res.ok || data.status !== 'ok') {
                    // Laravel 422 validation or other error
                    let msg = data.message || 'Gagal membuat shipment.';
                    if (data.errors) {
                        msg = Object.values(data.errors).flat().join(' ');
                    }
                    if (errEl) { errEl.textContent = msg; errEl.style.display = 'block'; }
                    if (btn) { btn.disabled = false; btn.style.background = ''; }
                    if (spinner) spinner.style.display = 'none';
                    return;
                }

                _ship = data.shipment;
                applyShipMeta(_ship);
                resetLinesUI();
                showP2();

            } catch (err) {
                if (errEl) { errEl.textContent = err.message || 'Terjadi kesalahan, coba lagi.'; errEl.style.display = 'block'; }
                if (btn) { btn.disabled = false; btn.style.background = ''; }
                if (spinner) spinner.style.display = 'none';
            }
        });
    }

    function applyShipMeta(s) {
        const codeEl    = document.getElementById('shipCode');
        const storeEl   = document.getElementById('shipStore');
        const dateEl    = document.getElementById('shipDate');
        const invoiceEl = document.getElementById('shipInvoice');

        if (codeEl) codeEl.textContent = s.code || '—';
        const topCode = document.getElementById('topShipCode');
        if (topCode) topCode.textContent = s.code || 'Shipment Baru';

        if (storeEl) {
            const storeLabel = [s.store_code, s.store_name].filter(Boolean).join(' — ');
            if (storeLabel) {
                storeEl.textContent = storeLabel;
                storeEl.style.display = '';
            } else {
                storeEl.style.display = 'none';
            }
        }

        if (dateEl) dateEl.textContent = fmtDate(s.date) || '—';

        if (invoiceEl) {
            if (s.invoice_code || s.channel_order_no) {
                invoiceEl.textContent = [s.invoice_code, s.channel_order_no].filter(Boolean).join(' · ');
                invoiceEl.style.display = '';
            } else {
                invoiceEl.style.display = 'none';
            }
        }

        // Submit form action
        const sf = document.getElementById('submitForm');
        if (sf) sf.action = s.submit_url;

        // Export link
        const ea = document.getElementById('exportBtn');
        if (ea) ea.href = s.export_url;
    }

    function fmtDate(str) {
        if (!str) return '';
        try {
            const d = new Date(str + 'T00:00:00');
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        } catch { return str; }
    }

    // ── Phase 2: Scan item ────────────────────────────────────────────────

    window.doScan = async function () {
        if (!_ship) { showToast('err', 'Shipment belum dibuat.'); return; }

        const input = document.getElementById('scanInput');
        const code  = (input?.value || '').trim().toUpperCase();
        if (!code) { beepErr(); setScanStatus('Kode kosong.', '#ef4444'); input?.focus(); return; }

        const scanBtn = document.getElementById('scanBtn');
        if (scanBtn) scanBtn.disabled = true;

        try {
            const fd = new FormData();
            fd.append('scan_code', code);
            fd.append('_token', CSRF);

            const res = await fetch(_ship.scan_url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            });

            let data;
            try { data = await res.json(); } catch { throw new Error('Response tidak valid.'); }

            if (!res.ok || data.status !== 'ok') {
                beepErr();
                const msg = data.message || 'Item tidak ditemukan.';
                showToast('err', msg);
                setScanStatus(msg, '#ef4444');
                if (input) { input.value = ''; input.focus(); }
                return;
            }

            beepOk();
            const line   = data.line   || {};
            const totals = data.totals || {};

            addOrUpdateRow(line);
            updateTotals(totals.total_lines, totals.total_qty);
            setScanStatus((line.item_code || code) + ' ditambahkan', '#16a34a');
            showToast('ok', data.message || '+1 ' + (line.item_code || code));

            if (input) { input.value = ''; input.focus(); }

        } catch (err) {
            beepErr();
            showToast('err', err.message || 'Terjadi kesalahan.');
            setScanStatus(err.message || 'Error', '#ef4444');
            if (input) { input.value = ''; input.focus(); }
        } finally {
            if (scanBtn) scanBtn.disabled = false;
        }
    };

    // ── Add / update row ──────────────────────────────────────────────────

    function addOrUpdateRow(line) {
        const tbody = document.getElementById('linesTbody');
        if (!tbody || !line.id) return;

        // Remove empty state
        const empty = document.getElementById('emptyRow');
        if (empty) empty.remove();

        let row = tbody.querySelector('tr[data-line-id="' + line.id + '"]');

        if (!row) {
            row = document.createElement('tr');
            row.setAttribute('data-line-id', line.id);

            const delUrl = _ship.destroy_line_url.replace('__LINE_ID__', line.id);
            const qtyUrl = _ship.update_qty_url.replace('__LINE_ID__', line.id);

            row.innerHTML =
                '<td class="text-muted small row-num"></td>' +
                '<td><span class="fw-semibold item-code" style="font-size:.88rem;font-family:monospace"></span></td>' +
                '<td class="d-none d-md-table-cell"><span class="small item-name text-muted"></span></td>' +
                '<td class="text-end">' +
                    '<span class="shp-qty-pill" id="qpill-' + line.id + '"></span>' +
                    '<div class="d-none qty-edit-wrap" style="display:inline-flex;align-items:center;gap:.25rem">' +
                        '<input type="number" class="form-control form-control-sm qty-inp" min="0" style="width:78px;text-align:right"' +
                               ' data-qty-url="' + qtyUrl + '">' +
                        '<button type="button" class="btn btn-primary btn-sm qty-save" style="border-radius:999px;padding-inline:.5rem;font-size:.72rem;line-height:1.4">OK</button>' +
                    '</div>' +
                '</td>' +
                '<td class="text-end">' +
                    '<button class="btn btn-sm btn-outline-danger del-btn" data-del-url="' + delUrl + '" title="Hapus baris">Hapus</button>' +
                '</td>';

            tbody.appendChild(row);
            bindRow(row);
        }

        // Update cells
        const codeEl = row.querySelector('.item-code');
        if (codeEl) codeEl.textContent = line.item_code || '-';

        const nameEl = row.querySelector('.item-name');
        if (nameEl) nameEl.textContent = line.item_name || '';

        const qpill = document.getElementById('qpill-' + line.id);
        if (qpill) qpill.textContent = new Intl.NumberFormat('id-ID').format(line.qty_scanned || 0);

        // Highlight
        row.classList.add('row-new');
        setTimeout(() => row.classList.remove('row-new'), 900);

        renumber();
    }

    // ── Bind row interactions ─────────────────────────────────────────────

    function bindRow(row) {
        // Delete
        const delBtn = row.querySelector('.del-btn');
        if (delBtn) {
            delBtn.addEventListener('click', async function () {
                if (!confirm('Hapus baris ini?')) return;
                try {
                    const fd = new FormData();
                    fd.append('_token', CSRF);
                    fd.append('_method', 'DELETE');
                    const res = await fetch(this.dataset.delUrl, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) { showToast('err', data.message || 'Gagal hapus.'); return; }
                    row.remove();
                    renumber();
                    if (!document.querySelector('#linesTbody tr[data-line-id]')) {
                        document.getElementById('linesTbody').innerHTML = emptyRowHtml();
                    }
                    updateTotals(data.totals?.total_lines, data.totals?.total_qty);
                    showToast('ok', 'Baris dihapus.');
                } catch { showToast('err', 'Gagal hapus baris.'); }
            });
        }

        // Qty edit — click pill to open inline input
        const qpill    = row.querySelector('.shp-qty-pill');
        const editWrap = row.querySelector('.qty-edit-wrap');
        const qinp     = row.querySelector('.qty-inp');
        const qsave    = row.querySelector('.qty-save');

        if (qpill && editWrap && qinp) {
            qpill.addEventListener('click', function () {
                qpill.classList.add('d-none');
                editWrap.style.display = 'inline-flex';
                qinp.value = qpill.textContent.replace(/\./g, '').trim();
                qinp.focus(); qinp.select();
            });

            const saveQty = async function () {
                const newQty = parseInt(qinp.value, 10);
                if (isNaN(newQty) || newQty < 0) { beepErr(); showToast('err', 'Qty tidak valid.'); return; }
                try {
                    const fd = new FormData();
                    fd.append('_token', CSRF);
                    fd.append('_method', 'PATCH');
                    fd.append('qty', newQty);
                    const res = await fetch(qinp.dataset.qtyUrl, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) { beepErr(); showToast('err', data.message || 'Gagal update.'); return; }
                    beepOk();
                    if (data.deleted || newQty === 0) {
                        row.remove(); renumber();
                        if (!document.querySelector('#linesTbody tr[data-line-id]')) {
                            document.getElementById('linesTbody').innerHTML = emptyRowHtml();
                        }
                    } else {
                        qpill.textContent = new Intl.NumberFormat('id-ID').format(newQty);
                        editWrap.style.display = 'none';
                        qpill.classList.remove('d-none');
                    }
                    updateTotals(data.totals?.total_lines, data.totals?.total_qty);
                    showToast('ok', data.message || 'Qty diperbarui.');
                } catch { beepErr(); showToast('err', 'Gagal update qty.'); }
            };

            if (qsave) qsave.addEventListener('click', saveQty);
            qinp.addEventListener('keydown', function (e) {
                if (e.key === 'Enter')  { e.preventDefault(); saveQty(); }
                if (e.key === 'Escape') { editWrap.style.display = 'none'; qpill.classList.remove('d-none'); }
            });
        }
    }

    // ── Clear all lines ───────────────────────────────────────────────────

    window.clearAllLines = async function () {
        if (!_ship || !confirm('Bersihkan semua item? Shipment tetap ada.')) return;
        try {
            const fd = new FormData();
            fd.append('_token', CSRF);
            const res = await fetch(_ship.clear_url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) { showToast('err', data.message || 'Gagal bersihkan.'); return; }
            document.getElementById('linesTbody').innerHTML = emptyRowHtml();
            updateTotals(0, 0);
            setScanStatus('');
            showToast('ok', 'Semua baris dibersihkan.');
            const si = document.getElementById('scanInput');
            if (si) { si.value = ''; si.focus(); }
        } catch { showToast('err', 'Gagal bersihkan.'); }
    };

})();
</script>
@endpush
