<div class="ship-topbar">
        <div>
            <h1 class="title">
                📦 Order Lokal
                <span class="orders-title-badge">Marketplace</span>
                <span id="syncIndicator" style="display:none;font-size:.7rem;color:var(--shp-muted);font-weight:normal;background:#f1f5f9;padding:2px 8px;border-radius:12px;margin-left:8px;">
                    <span class="prod-tab-spinner" style="width:10px;height:10px;border-width:1.5px;margin-right:.2rem;"></span>
                    Menyinkronkan...
                </span>
            </h1>
        </div>
        <div class="controls">
            {{-- Hidden date inputs --}}
            <input type="hidden" id="mpDateFrom" value="{{ $filters['date_from'] }}">
            <input type="hidden" id="mpDateTo"   value="{{ $filters['date_to'] }}">

            {{-- Search bar --}}
            <div class="ord-search-bar" style="background:var(--card); border:1px solid rgba(148,163,184,.35);">
                <span class="search-icon" style="opacity:0.5; font-size:0.9em;">🔍</span>
                <input type="text" id="filterSearch" placeholder="Cari order/resi/SKU…" oninput="onSearchInput(this)" autocomplete="off">
                <button class="ord-search-clear" id="searchClearBtn" onclick="clearSearch()">✕</button>
            </div>

            {{-- Store filter --}}
            <div style="position:relative" class="mobile-hide">
                <button class="btn-ship-outline" id="btnStore" onclick="toggleDropdown('ddStore', event)">
                    <span style="opacity:0.7;">🏪</span> <span id="btnStoreLabel" class="hdr-btn-label" style="color:inherit; font-size:0.75rem;">Semua Toko</span>
                </button>
                <div class="hdr-dropdown" id="ddStore">
                    <div style="padding:.25rem .4rem .1rem;font-size:.65rem;font-weight:700;color:#94a3b8;letter-spacing:.04em">PILIH TOKO</div>
                    <div id="storeDropdownItems"></div>
                </div>
            </div>

            {{-- Date filter --}}
            <div style="position:relative" class="mobile-hide">
                <button class="btn-ship-outline" id="btnDate" onclick="toggleDropdown('ddDate', event)">
                    <span style="opacity:0.7;">📅</span> <span id="btnDateLabel" class="hdr-btn-label" style="color:inherit; font-size:0.75rem;">7 hari terakhir</span>
                </button>
                <div class="hdr-dropdown" id="ddDate" style="right:0;left:auto;min-width:210px">
                    <div style="padding:.25rem .4rem .1rem;font-size:.65rem;font-weight:700;color:#94a3b8;letter-spacing:.04em">PERIODE CEPAT</div>
                    <div class="hdr-dropdown-item" onclick="setDatePreset(1)">📆 Hari ini</div>
                    <div class="hdr-dropdown-item selected" onclick="setDatePreset(7)">📆 7 hari terakhir</div>
                    <div class="hdr-dropdown-item" onclick="setDatePreset(30)">📆 30 hari terakhir</div>
                    <div class="hdr-dropdown-item" onclick="setDatePreset(90)">📆 90 hari terakhir</div>
                    <div class="hdr-dropdown-divider"></div>
                    <div style="padding:.2rem .4rem .1rem;font-size:.65rem;font-weight:700;color:#94a3b8">PILIH MANUAL</div>
                    <div style="padding:.3rem .4rem .4rem">
                        <input type="text" id="mpDateRange" autocomplete="off"
                            style="width:100%;font-size:.78rem;border:1.5px solid #e2e8f0;border-radius:8px;padding:.28rem .6rem"
                            placeholder="Pilih rentang…" value="{{ $filters['date_from'] }} — {{ $filters['date_to'] }}">
                    </div>
                </div>
            </div>

            {{-- Sync --}}
            <span id="lastSyncTime" style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;" class="mobile-hide"></span>
            <button class="btn-ship-primary" onclick="openQuickSync()">🔄 <span class="mobile-hide">Sync Pesanan</span></button>
        </div>
    </div>
