import re

with open('resources/views/inventory/warehouse_intelligence/partials/_rts.blade.php', 'r') as f:
    content = f.read()

# 1. Enhance Filter Buttons
old_filters = """            <button class="btn-filter-rts active sd-btn sd-primary" data-filter="all">Semua Item</button>
            <button class="btn-filter-rts sd-btn" data-filter="kritis">Stok Kritis</button>
            <button class="btn-filter-rts sd-btn" data-filter="tarik_prd">Bisa Tarik PRD</button>
            <button class="btn-filter-rts sd-btn" data-filter="beli_jadi">Perlu Beli (PR)</button>"""

new_filters = """            <button class="btn-filter-rts active sd-btn sd-primary" data-filter="all"><i class="bi bi-grid"></i> Semua Item</button>
            <button class="btn-filter-rts sd-btn" data-filter="kritis" style="color: #d97706;"><i class="bi bi-exclamation-triangle"></i> Stok Kritis</button>
            <button class="btn-filter-rts sd-btn" data-filter="tarik_prd" style="color: #059669;"><i class="bi bi-box-seam"></i> Tersedia di PRD</button>
            <button class="btn-filter-rts sd-btn" data-filter="beli_jadi" style="color: #dc2626;"><i class="bi bi-cart-plus"></i> Perlu Beli (PR)</button>"""

content = content.replace(old_filters, new_filters)

# 2. Update Bulk Action Button
content = content.replace('Minta Stok Masal (<span id="bulk-count">0</span>)', 'Tarik PRD Masal (<span id="bulk-count">0</span>)')

# 3. Update Row Action Button
old_row_btn = """                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg>
                                        Minta Stok ({{ $fmt($r->minta_prd) }})"""

new_row_btn = """                                        <i class="bi bi-box-seam"></i>
                                        Tarik PRD ({{ $fmt($r->minta_prd) }})"""

content = content.replace(old_row_btn, new_row_btn)

# Make the Minta Stok button a bit more visually appealing (Tarik PRD)
old_btn_style = 'style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-weight: 600; font-size: .7rem; border-radius: 7px; padding: .2rem .5rem; display: inline-flex; align-items: center; gap: 4px;" title="Buat Request RTS"'
new_btn_style = 'style="background: #10b981; color: #fff; border: 1px solid #059669; font-weight: 600; font-size: .75rem; border-radius: 7px; padding: .25rem .6rem; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 2px 4px rgba(16,185,129,0.2);" title="Tarik Stok dari PRD"'

content = content.replace(old_btn_style, new_btn_style)


with open('resources/views/inventory/warehouse_intelligence/partials/_rts.blade.php', 'w') as f:
    f.write(content)

