<div class="ord-tabs" id="ordTabs">
        <button class="ord-tab" data-tab="issues" onclick="switchTab('issues',this)">
            ⚠️ Bermasalah <span class="ord-badge" id="badge-issues" style="background:#fef2f2;color:#dc2626;border-color:#fecaca">—</span>
        </button>
        <button class="ord-tab active" data-tab="ready" onclick="switchTab('ready',this)">
            Perlu Dikirim <span class="ord-badge urgent" id="badge-ready">—</span>
        </button>
        <button class="ord-tab" data-tab="processed" onclick="switchTab('processed',this)">
            Sedang Dikemas <span class="ord-badge" id="badge-processed" style="background:rgba(148,163,184,.15);color:#334155;border-color:rgba(148,163,184,.25)">—</span>
        </button>
        <button class="ord-tab" data-tab="shipped" onclick="switchTab('shipped',this)">
            Dikirim <span class="ord-badge" id="badge-shipped">—</span>
        </button>
        <button class="ord-tab" data-tab="completed" onclick="switchTab('completed',this)">
            Selesai <span class="ord-badge" id="badge-completed">—</span>
        </button>
        <button class="ord-tab" data-tab="rrc" onclick="switchTab('rrc',this)">
            ↩️ Retur & Refund <span class="ord-badge" id="badge-rrc" style="background:#fef2f2;color:#b91c1c;border-color:#fecaca">—</span>
        </button>
        <button class="ord-tab" data-tab="sync" onclick="switchTab('sync',this)">
            🔄 Sync <span class="ord-badge" id="badge-sync" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe">—</span>
        </button>
    </div>
