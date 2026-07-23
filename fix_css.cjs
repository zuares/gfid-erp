const fs = require('fs');
const path = '/Users/ariefmuhamad/Herd/gfid-dev/resources/views/marketplace/ads.blade.php';
let content = fs.readFileSync(path, 'utf8');

const regex = /<div class="card-body p-2 d-flex align-items-center justify-content-between gap-3 flex-nowrap" style="overflow-x:auto;">([\s\S]*?)<\/div>\s*<\/div>\s*<div id="adsSyncAlert"/;

const newHTML = `<div class="card-body p-2" style="overflow-x:auto;">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-nowrap" style="min-width: max-content;">
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
        </div>
    </div>
    
    <div id="adsSyncAlert"`;

content = content.replace(regex, newHTML);
fs.writeFileSync(path, content);
console.log("CSS Bug Fixed");
