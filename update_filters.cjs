const fs = require('fs');
const path = '/Users/ariefmuhamad/Herd/gfid-dev/resources/views/marketplace/ads.blade.php';
let content = fs.readFileSync(path, 'utf8');

// 1. Update the "Harian" filter header
let target1 = `<div class="card-body p-2 d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-bottom:1px solid var(--shp-border)">
            <div class="d-flex align-items-center gap-2">
                <span class="filter-label d-none d-sm-inline" style="font-size:.7rem;font-weight:700;text-transform:uppercase">Harian</span>
                <div class="period-tabs" id="periodTabs">
                    <button class="period-tab" data-days="7">7 Hari</button>
                    <button class="period-tab active" data-days="30">30 Hari</button>
                    <button class="period-tab" data-days="90">90 Hari</button>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-ship-outline btn-pill" id="btnSyncShopPerf" onclick="syncShopPerf()">Sync DB</button>
                <button class="btn btn-sm btn-ship-outline btn-pill" id="btnShopPerf" onclick="loadShopPerf()">Muat DB</button>
                <button class="btn btn-sm btn-ship-outline btn-pill" id="btnBackfill" onclick="backfillAds()">Tarik 6 Bln</button>
                <button class="btn btn-sm btn-ship-outline btn-pill" onclick="showBalanceHistory()">Riwayat Saldo</button>
            </div>
        </div>`;

let replacement1 = `<div class="card-body p-2" style="border-bottom:1px solid var(--shp-border); overflow-x:auto;">
            <div class="d-flex justify-content-between align-items-center gap-4 flex-nowrap" style="min-width:max-content;">
                <div class="d-flex align-items-center gap-2 flex-nowrap">
                    <span class="filter-label text-nowrap" style="font-size:.7rem;font-weight:700;text-transform:uppercase">Harian</span>
                    <div class="period-tabs flex-nowrap" id="periodTabs">
                        <button class="period-tab text-nowrap" data-days="7">7 Hari</button>
                        <button class="period-tab active text-nowrap" data-days="30">30 Hari</button>
                        <button class="period-tab text-nowrap" data-days="90">90 Hari</button>
                    </div>
                </div>
                
                <div class="d-flex align-items-center gap-2 flex-nowrap">
                    <button class="btn btn-sm btn-ship-outline btn-pill text-nowrap" id="btnSyncShopPerf" onclick="syncShopPerf()">Sync DB</button>
                    <button class="btn btn-sm btn-ship-outline btn-pill text-nowrap" id="btnShopPerf" onclick="loadShopPerf()">Muat DB</button>
                    <button class="btn btn-sm btn-ship-outline btn-pill text-nowrap" id="btnBackfill" onclick="backfillAds()">Tarik 6 Bln</button>
                    <button class="btn btn-sm btn-ship-outline btn-pill text-nowrap" onclick="showBalanceHistory()">Riwayat Saldo</button>
                </div>
            </div>
        </div>`;

// 2. Update the "Campaign" filter header
let target2 = `<div class="card-body p-2 d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-bottom:1px solid var(--shp-border)">
            <div class="period-tabs">
                <button class="period-tab active" data-view="campaign" onclick="setView('campaign')">Campaign</button>
                <button class="period-tab" data-view="item" onclick="setView('item')">Item</button>
                <button class="period-tab" data-view="group" onclick="setView('group')">Grup</button>
            </div>
            
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <span id="unmappedBadge" class="reco-badge reco-warn" style="display:none;cursor:pointer" onclick="toggleUnmappedFilter()"></span>
                <input type="search" id="searchCampaign" class="filter-input" placeholder="Cari..." oninput="applyFilters()" style="width:130px">
                <select id="filterStatus" class="filter-select" onchange="applyFilters()">
                    <option value="">Status (Semua)</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="suspended">Suspended</option>
                    <option value="ended">Ended</option>
                </select>
                <select id="filterReco" class="filter-select" onchange="applyFilters()">
                    <option value="">Rekomendasi (Semua)</option>
                    <option value="🚀">Scale</option>
                    <option value="✅">Pertahankan</option>
                    <option value="⚡">Perhatikan</option>
                    <option value="🔴">Stop</option>
                </select>
                <label class="toggle-switch" id="unmappedFilterWrap" style="display:none; margin-left:.5rem">
                    <input type="checkbox" id="onlyUnmapped" onchange="applyFilters()">
                    Hanya Unmapped
                </label>
                <label class="toggle-switch" style="margin-left:.5rem">
                    <input type="checkbox" id="hideInactive" checked onchange="applyFilters()">
                    Sembunyikan 0
                </label>
                <button class="btn btn-sm btn-ship-outline btn-pill" style="margin-left:.5rem" onclick="manageGroups()">Kelola Grup</button>
            </div>
        </div>`;

let replacement2 = `<div class="card-body p-2" style="border-bottom:1px solid var(--shp-border); overflow-x:auto;">
            <div class="d-flex align-items-center gap-3 flex-nowrap" style="min-width:max-content;">
                
                <div class="period-tabs flex-nowrap" style="border-right:1px solid var(--shp-border); padding-right:.75rem;">
                    <button class="period-tab active text-nowrap" data-view="campaign" onclick="setView('campaign')">Campaign</button>
                    <button class="period-tab text-nowrap" data-view="item" onclick="setView('item')">Item</button>
                    <button class="period-tab text-nowrap" data-view="group" onclick="setView('group')">Grup</button>
                </div>
                
                <div class="d-flex gap-2 align-items-center flex-nowrap" style="border-right:1px solid var(--shp-border); padding-right:.75rem;">
                    <span id="unmappedBadge" class="reco-badge reco-warn" style="display:none;cursor:pointer;white-space:nowrap" onclick="toggleUnmappedFilter()"></span>
                    <input type="search" id="searchCampaign" class="filter-input" placeholder="Cari..." oninput="applyFilters()" style="width:130px">
                    <select id="filterStatus" class="filter-select" onchange="applyFilters()" style="width:auto;">
                        <option value="">Status (Semua)</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="suspended">Suspended</option>
                        <option value="ended">Ended</option>
                    </select>
                    <select id="filterReco" class="filter-select" onchange="applyFilters()" style="width:auto;">
                        <option value="">Rekomendasi (Semua)</option>
                        <option value="🚀">Scale</option>
                        <option value="✅">Pertahankan</option>
                        <option value="⚡">Perhatikan</option>
                        <option value="🔴">Stop</option>
                    </select>
                </div>

                <div class="d-flex gap-3 align-items-center flex-nowrap">
                    <label class="toggle-switch mb-0" id="unmappedFilterWrap" style="display:none; white-space:nowrap;">
                        <input type="checkbox" id="onlyUnmapped" onchange="applyFilters()">
                        Hanya Unmapped
                    </label>
                    <label class="toggle-switch mb-0" style="white-space:nowrap;">
                        <input type="checkbox" id="hideInactive" checked onchange="applyFilters()">
                        Sembunyikan 0
                    </label>
                    <button class="btn btn-sm btn-ship-outline btn-pill text-nowrap" onclick="manageGroups()">Kelola Grup</button>
                </div>
                
            </div>
        </div>`;
        
// Fix period-tabs CSS to allow flex-nowrap globally without breaking
content = content.replace('.period-tabs { display:flex; gap:.2rem; flex-wrap:wrap; }', '.period-tabs { display:flex; gap:.2rem; }');

content = content.replace(target1, replacement1);
content = content.replace(target2, replacement2);

fs.writeFileSync(path, content);
console.log("Filters updated successfully");
