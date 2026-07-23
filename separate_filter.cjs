const fs = require('fs');
const path = '/Users/ariefmuhamad/Herd/gfid-dev/resources/views/marketplace/ads.blade.php';
let content = fs.readFileSync(path, 'utf8');

const regex = /<div class="ship-topbar">([\s\S]*?)<\/div>\s+<\/div>/;
const match = content.match(regex);

if (match) {
    const newHeader = `<div class="ship-topbar" style="margin-bottom: .8rem; border-bottom: none; background: transparent;">
        <div>
            <div class="title">Analisa Iklan</div>
            <div class="sub">Performa campaign Ads</div>

            <div class="kpis">
                <span class="kpi" title="Sisa kredit iklan"><span class="lbl">Saldo</span><span class="val" id="kpiBalance">—</span></span>
                <span class="kpi"><span class="lbl">Spend</span><span class="val" id="kpiSpend">—</span></span>
                <span class="kpi"><span class="lbl">Sales</span><span class="val" id="kpiGmv">—</span></span>
                <span class="kpi"><span class="lbl">ROAS</span><span class="val" id="kpiRoas">—</span></span>
                <span class="kpi"><span class="lbl">ACOS</span><span class="val" id="kpiAcos">—</span></span>
                <span class="kpi"><span class="lbl">Orders</span><span class="val" id="kpiOrders">—</span></span>
                <span class="kpi" title="Gross profit - spend"><span class="lbl">Profit</span><span class="val" id="kpiProfit">—</span></span>
            </div>
        </div>
    </div>

    <!-- Filter Bar Separated -->
    <div class="card card-main mb-3" style="border-radius: 8px;">
        <div class="card-body p-2 d-flex align-items-center justify-content-between gap-3 flex-nowrap" style="overflow-x:auto;">
            <div class="d-flex align-items-center gap-2 flex-nowrap">
                <select class="filter-select" id="adsStoreId"></select>
                <div style="position:relative; display:flex; align-items:center;">
                    <i class="bi bi-calendar3" style="position:absolute; left: .65rem; color:#64748b; font-size:.75rem; pointer-events:none;"></i>
                    <input type="text" id="dateRangePicker" class="filter-input text-nowrap" style="padding-left:1.9rem; width:180px; cursor:pointer; text-align:center; font-size:.78rem;" placeholder="Pilih Tanggal..." readonly>
                </div>
                <input type="hidden" id="dateFrom">
                <input type="hidden" id="dateTo">
            </div>
            
            <div class="d-flex align-items-center gap-2 flex-nowrap">
                <button class="btn btn-sm btn-ship-primary btn-pill text-nowrap" id="syncBtn" onclick="runSync()">Sync API</button>
                <button class="btn btn-sm btn-ship-outline btn-pill text-nowrap" onclick="loadAds()">Refresh</button>
            </div>
        </div>
    </div>`;

    content = content.replace(regex, newHeader);
    fs.writeFileSync(path, content);
    console.log("Filter separated successfully");
} else {
    console.log("Regex not matched");
}
