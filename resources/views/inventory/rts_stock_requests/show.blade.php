@extends('layouts.app')

@section('title', 'RTS • ' . $stockRequest->code)

@push('head')
<style>
    :root{
        --shp-accent:#334155;
        --shp-accent-2:#1f2937;
        --shp-border:rgba(148,163,184,.18);
        --shp-border-strong:rgba(148,163,184,.30);
        --shp-muted:#64748b;
    }
    .sd-wrap{ max-width:1040px; margin-inline:auto; padding:.75rem .75rem 4rem; background:transparent!important; }
    .sd-card{ background: var(--card, #fff); border-radius: 8px; border: 1px solid var(--shp-border); overflow:hidden; margin-bottom:.65rem; }
    body[data-theme="dark"] .sd-card{ border-color: rgba(51,65,85,.85); }
    .sd-topbar{ position:sticky; top:0; z-index:300; display:flex; justify-content:space-between; align-items:center; gap:.6rem; flex-wrap:wrap; padding:.45rem .75rem; margin-inline:-.75rem; margin-bottom:.65rem; background:var(--card,#fff); border-bottom:1px solid var(--shp-border); }
    body[data-theme="dark"] .sd-topbar{ background:var(--card,#0f172a); }
    .sd-title{ font-weight: 750; font-size:1rem; letter-spacing: 0; margin:0; }
    .sd-code{ font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-weight:900; color:#111827; }
    .sd-sub{ color:var(--shp-muted); font-size:.78rem; }
    body[data-theme="dark"] .sd-sub{ color:#9ca3af; }
    .sd-btn, .sd-pill{ display:inline-flex; align-items:center; justify-content:center; gap:.35rem; border-radius:7px; border:1px solid rgba(148,163,184,.3); background:transparent; color:#475569; text-decoration:none; font-size:.76rem; padding:.28rem .6rem; min-height:34px; font-weight:800; cursor:pointer; }
    .sd-btn:hover{ background:rgba(148,163,184,.09); color:#111827; text-decoration:none; }
    .sd-primary{ background:#334155!important; border-color:#334155!important; color:#fff!important; }
    .sd-status{ font-weight:850; color:#334155; background:rgba(148,163,184,.08); }
    .sd-grid{ display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.55rem; margin-bottom:.65rem; }
    .sd-kpi{ padding:.65rem .75rem; }
    .sd-label{ font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.02em; }
    .sd-value{ font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:1.18rem; font-weight:900; color:#111827; margin-top:.12rem; }
    .sd-head{ display:flex; align-items:center; gap:.55rem; justify-content:space-between; padding:.7rem .85rem; border-bottom:1px solid rgba(148,163,184,.12); }
    .sd-head-title{ font-weight:900; color:#334155; }
    .sd-body{ padding:.75rem .85rem; }
    .sd-table-wrap{ overflow:auto; border:1px solid rgba(148,163,184,.16); border-radius:8px; }
    .sd-table{ width:100%; border-collapse:collapse; }
    .sd-table th, .sd-table td{ padding:.55rem .65rem; border-bottom:1px solid rgba(148,163,184,.12); vertical-align:middle; }
    .sd-table th{ text-align:left; font-size:.72rem; color:#64748b; font-weight:900; text-transform:uppercase; letter-spacing:.02em; background:rgba(148,163,184,.04); }
    .sd-table td{ font-size:.86rem; color:#334155; }
    .sd-code-cell{ font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-weight:900; color:#111827; }
    .sd-name{ color:#64748b; font-size:.8rem; margin-top:.08rem; }
    .sd-r{ text-align:right; }
    .sd-actions{ display:flex; align-items:center; gap:.35rem; flex-wrap:wrap; }
    @media(max-width:860px){
        .sd-wrap{ padding:.5rem .5rem 3.5rem; }
        .sd-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); gap:.45rem; }
        .sd-table-wrap{ border:none; border-radius:0; overflow:visible; }
        .sd-table, .sd-table tbody, .sd-table tr, .sd-table td{ display:block; width:100%; }
        .sd-table thead{ display:none; }
        .sd-table tr{ border:1px solid rgba(148,163,184,.16); border-radius:8px; margin-bottom:.45rem; padding:.55rem .6rem; background:var(--card,#fff); }
        .sd-table td{ border:0; padding:0; }
        .sd-table td.sd-r{ text-align:left; margin-top:.35rem; }
    }

    .sd-btn-row { display: flex; justify-content: space-between; gap: .75rem; flex-wrap: wrap; margin-top: .9rem; align-items: center; padding-inline: .2rem; }
    .sd-muted { color: #64748b; font-size: .8rem; }
    .sd-actions-bottom { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; justify-content: flex-end; }
</style>
@endpush

@section('content')
    @php
        $role = auth()->user()?->role;
        $canManage = in_array($role, ['owner', 'admin'], true);

        $reqTotal = (float) $stockRequest->lines->sum('qty_request');
        $recvTotal = (float) $stockRequest->lines->sum('qty_received');
        $pickTotal = (float) $stockRequest->lines->sum('qty_picked');
        $outTotal = max($reqTotal - $recvTotal - $pickTotal, 0);

        $canReceive = in_array($stockRequest->status, ['submitted', 'shipped', 'partial'], true) && $outTotal > 0.0000001;
    @endphp

    <div class="sd-wrap">
        <div class="sd-topbar">
            <div>
                <h1 class="sd-title sd-code">{{ $stockRequest->code }}</h1>
                <div class="sd-sub">
                    {{ optional($stockRequest->date)->format('d M Y') }}
                    · {{ $stockRequest->sourceWarehouse->code ?? '-' }} →
                    {{ $stockRequest->destinationWarehouse->code ?? '-' }}
                </div>
            </div>

            <div class="sd-actions">
                <span class="sd-pill sd-status">{{ ucfirst($stockRequest->status) }}</span>
                <a href="{{ route('rts.stock-requests.index') }}" class="sd-btn">← List</a>
                
                <button type="button" class="sd-btn" style="color: #1d4ed8; border-color: #bfdbfe;" onclick="printPickingList()">
                    🖨 Cetak Picking List
                </button>
                
                <a href="{{ route('rts.stock-requests.barcode', $stockRequest->id) }}" target="_blank" class="sd-btn">
                    <i class="bi bi-upc-scan"></i> Cetak Barcode
                </a>
                
                @if ($stockRequest->status === 'draft')
                    <a href="{{ route('rts.stock-requests.edit', $stockRequest) }}" class="sd-btn">
                        <i class="bi bi-pencil"></i> Edit Draft
                    </a>
                @endif
            </div>
        </div>

        <div class="sd-grid">
            <div class="sd-card sd-kpi">
                <div class="sd-label">Req</div>
                <div class="sd-value">{{ number_format($reqTotal, 0, ',', '.') }}</div>
            </div>
            <div class="sd-card sd-kpi">
                <div class="sd-label">Terima Jadi</div>
                <div class="sd-value">{{ number_format($recvTotal, 0, ',', '.') }}</div>
            </div>
            <div class="sd-card sd-kpi">
                <div class="sd-label">Sisa</div>
                <div class="sd-value">{{ number_format($outTotal, 0, ',', '.') }}</div>
            </div>
            <div class="sd-card sd-kpi">
                <div class="sd-label">Item SKU</div>
                <div class="sd-value">{{ $stockRequest->lines->count() }}</div>
            </div>
        </div>

        @if ($stockRequest->notes)
            <div class="sd-card" style="padding:.85rem; font-size:.85rem; color:#475569;">
                <strong>Catatan:</strong> {{ $stockRequest->notes }}
            </div>
        @endif

        <div class="sd-card">
            <div class="sd-head">
                <div class="sd-head-title">Daftar Item</div>
            </div>
            
            <div class="sd-table-wrap">
                <table class="sd-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align:center;">No</th>
                            <th>Item</th>
                            <th class="sd-r">Req</th>
                            <th class="sd-r">Terima Jadi</th>
                            <th class="sd-r">Sisa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stockRequest->lines as $i => $line)
                            @php
                                $req = (float) ($line->qty_request ?? 0);
                                $recv = (float) ($line->qty_received ?? 0);
                                $pick = (float) ($line->qty_picked ?? 0);
                                $out = max($req - $recv - $pick, 0);
                            @endphp
                            <tr>
                                <td style="text-align:center; color:#64748b;">{{ $i + 1 }}</td>
                                <td>
                                    <div class="sd-code-cell">{{ $line->item->code }}</div>
                                    <div class="sd-name">{{ $line->item->name }}</div>
                                </td>
                                <td class="sd-r sd-code-cell">{{ number_format($req, 0, ',', '.') }}</td>
                                <td class="sd-r sd-code-cell">{{ number_format($recv, 0, ',', '.') }}</td>
                                <td class="sd-r sd-code-cell">{{ number_format($out, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    
        <div class="sd-btn-row">
            <div class="sd-muted">Pilih aksi untuk memproses permintaan ini.</div>
            <div class="sd-actions-bottom">
                @if ($canManage && $stockRequest->status !== 'cancelled')
                    <form action="{{ route('rts.stock-requests.destroy', $stockRequest) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan Stock Request ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="sd-btn" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.3);">
                            <i class="bi bi-x-circle"></i> Batalkan
                        </button>
                    </form>
                @endif
                
                @if ($canManage && $canReceive)
                    <a href="{{ route('rts.stock-requests.confirm', $stockRequest) }}" class="sd-btn sd-primary">
                        Terima Barang
                    </a>
                @endif
            </div>
        </div>
</div>
@endsection

@php
    $exportData = $stockRequest->lines->map(function($line) {
        $req = (float) ($line->qty_request ?? 0);
        $recv = (float) ($line->qty_received ?? 0);
        $pick = (float) ($line->qty_picked ?? 0);
        $out = max($req - $recv - $pick, 0);

        return [
            'sku' => $line->item->code ?? '',
            'category' => $line->item->category->name ?? 'Tanpa Kategori',
            'qty' => $out
        ];
    })->filter(function($item) {
        return $item['qty'] > 0;
    })->values();
@endphp
<script>
window.stockRequestItemsData = @json($exportData);

function printPickingList() {
    const items = window.stockRequestItemsData;
    if (!items || items.length === 0) {
        alert("Tidak ada item tersisa untuk dicetak (semua sudah terpenuhi/diambil).");
        return;
    }

    const today = new Date().toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
    const timeNow = new Date().toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });
    
    const itemMap = {};
    let totalQty = 0;
    items.forEach(i => {
        const cat = i.category || 'Tanpa Kategori';
        if (!itemMap[cat]) itemMap[cat] = [];
        itemMap[cat].push(i);
        totalQty += parseInt(i.qty);
    });

    let itemRows = '';
    const sortedCategories = Object.keys(itemMap).sort();
    sortedCategories.forEach(cat => {
        itemRows += `
            <tr class="category-row">
                <td colspan="4">${cat}</td>
            </tr>
        `;
        const catItems = itemMap[cat].sort((a,b) => a.sku.localeCompare(b.sku));
        catItems.forEach(i => {
            itemRows += `
                <tr>
                    <td class="chk"><input type="checkbox"></td>
                    <td class="sku-code">${i.sku}</td>
                    <td class="qty">${i.qty}</td>
                    <td class="picked-qty"></td>
                </tr>
            `;
        });
    });

    const html = `<!DOCTYPE html>
<html>
<head>
    <title>Print Picking List</title>
    <style>
        @page { size: 100mm auto; margin: 0; }
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.05; -webkit-print-color-adjust: economy; print-color-adjust: economy; color-scheme: light only; }
        #toolbar { position: fixed; top: 0; left: 0; right: 0; z-index: 99; background: #0f172a !important; color: #fff !important; padding: .75rem 1rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        #toolbar * { color: #fff !important; }
        #toolbar button { background: #000 !important; color: #fff !important; border: 1px solid #fff; border-radius: 8px; padding: .75rem 1.5rem; font-weight: 900; font-size: 1rem; cursor: pointer; min-width: 132px; }
        #toolbar button:hover { background: #111 !important; }
        #content { padding-top: 58px; }
        @media print { #toolbar { display: none; } #content { padding-top: 0; } }
        .page-header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: .3mm solid #000; padding-bottom: .8mm; margin-bottom: 1.1mm; }
        .header-left { display: flex; align-items: center; gap: 1.5mm; min-width: 0; }
        .print-logo { width: 7mm; height: 7mm; object-fit: contain; flex: 0 0 auto; display: block; filter: grayscale(1) contrast(1.4) !important; }
        .page-title { font-size: 6.5pt; font-weight: 900; letter-spacing: 0; }
        .page-date { font-size: 6pt; color: #000 !important; font-weight: 800; margin-top: .2mm; }
        .page-meta  { font-size: 6.5pt; color: #000 !important; text-align: right; font-weight: 900; }
        .section-title { font-size: 6.5pt; font-weight: 900; text-transform: uppercase; letter-spacing: .02em; color: #000 !important; margin: 1mm 0 .7mm; border-bottom: .25mm solid #000; padding-bottom: .5mm; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-row-group; }
        table td, table th { padding: .62mm .8mm; border: .24mm solid #000; vertical-align: middle; }
        table th { font-size: 6.5pt; color: #000 !important; text-transform: uppercase; font-weight: 900; }
        .category-row td { padding: .45mm .8mm; font-size: 6pt; font-weight: 900; text-transform: uppercase; letter-spacing: .03em; color: #fff !important; background: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .chk  { width: 5.5mm; text-align: center; }
        .chk input { width: 2.8mm; height: 2.8mm; accent-color: #000; }
        .qty  { width: 9mm; text-align: center; font-weight: 900 !important; font-size: 6.5pt; }
        .picked-qty { width: 14mm; text-align: center; font-weight: 900 !important; font-size: 6.5pt; }
        .sku-code { font-family: Arial, Helvetica, sans-serif; font-size: 6.5pt; font-weight: 900 !important; color: #000 !important; line-height: 1; }
        .footer { display: flex; justify-content: space-between; font-weight: 900; font-size: 6.5pt; border-top: .3mm solid #000; padding-top: .7mm; margin-top: 1mm; color: #000 !important; }
        @media screen {
            body { width: 100mm; min-height: 150mm; margin: 0 auto; padding: 0; overflow-x: hidden; background: #fff !important; }
            #content { width: 100mm; min-height: 150mm; margin: 0; padding-left: 3.5mm; padding-right: 3.5mm; padding-bottom: 3.5mm; }
        }
        @media print {
            *, *::before, *::after { color: #000 !important; border-color: #000 !important; box-shadow: none !important; text-shadow: none !important; filter: none !important; opacity: 1 !important; }
            html, body, #content { width: 93mm; background: #fff !important; }
            thead { display: table-row-group !important; }
            .qty, .sku-code { font-weight: 900 !important; }
            .category-row td { color: #fff !important; background: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    
    .sd-btn-row { display: flex; justify-content: space-between; gap: .75rem; flex-wrap: wrap; margin-top: .9rem; align-items: center; padding-inline: .2rem; }
    .sd-muted { color: #64748b; font-size: .8rem; }
    .sd-actions-bottom { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; justify-content: flex-end; }
</style>
</head>
<body>
    <div id="toolbar">
        <span style="font-size:.85rem;font-weight:600">📋 Picking List — ${items.length} SKU · ${totalQty} pcs</span>
        <button onclick="window.print()">🖨 Print</button>
    </div>
    <div id="content">
        <div class="page-header">
            <div class="header-left">
                <img class="print-logo" src="/images/logo-mark.svg" alt="GF">
                <div>
                    <div class="page-title">PICKING LIST {{ $stockRequest->code }}</div>
                    <div class="page-date">${today} · ${timeNow}</div>
                </div>
            </div>
            <div class="page-meta">
                <div><strong>${items.length}</strong> SKU</div>
                <div><strong>${totalQty}</strong> pcs</div>
            </div>
        </div>
        <div class="section-title">Daftar Barang Transfer</div>
        <table>
            <thead><tr><th class="chk"></th><th style="text-align:left">Kode SKU</th><th class="qty">Qty</th><th class="picked-qty">Diambil</th></tr></thead>
            <tbody>${itemRows}</tbody>
        </table>
        <div class="footer">
            <span>TOTAL ${items.length} SKU</span>
            <span>${totalQty} PCS</span>
        </div>
    </div>
</body></html>`;

    const win = window.open('', '_blank', 'width=430,height=680');
    if (!win) { alert('Popup diblokir. Izinkan popup untuk halaman ini.'); return; }
    win.document.write(html);
    win.document.close();
}
</script>