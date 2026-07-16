import re

with open('resources/views/inventory/adjustments/manual_create.blade.php', 'r') as f:
    content = f.read()

# Replace buildRow
content = content.replace("""            function buildRow(item, idx) {
                const onHand = parseFloat(item.on_hand) || 0;

                const tr = document.createElement('tr');
                tr.dataset.code = (item.code || '').toLowerCase();
                tr.dataset.name = (item.name || '').toLowerCase();
                tr.dataset.idx = String(idx);
                tr.dataset.notInWarehouse = item.not_in_warehouse ? '1' : '0';

                tr.innerHTML = `
            <td class="text-muted small mobile-hide">${idx + 1}</td>
            <td>
                <div class="adj-row-main">
                    <div>
                        <span class="text-mono fw-semibold" style="font-size:.86rem;">${item.code ?? ''}</span>
                        ${item.not_in_warehouse ? '<span class="item-badge">baru</span>' : ''}
                        <div style="font-size:.78rem;color:#6b7280;margin-top:.1rem;">${item.name ?? ''}</div>
                    </div>
                    <span class="diff-display mobile-hide text-mono" style="font-size:.8rem;">0.00</span>
                </div>
                <div class="adj-row-meta mobile-hide">
                    <span>Stok: <strong class="text-mono"><span class="on-hand" data-on-hand="${onHand}">${fmt(onHand)}</span></strong></span>
                </div>
                <input type="hidden" class="row-item-id" value="${item.id}">
            </td>""", """            function buildRow(item, idx) {
                const onHand = parseFloat(item.on_hand) || 0;
                const lotId = item.lot_id || '';
                const lotCode = item.lot_code || '';

                const tr = document.createElement('tr');
                tr.dataset.code = (item.code || '').toLowerCase();
                tr.dataset.name = (item.name || '').toLowerCase();
                tr.dataset.idx = String(idx);
                tr.dataset.notInWarehouse = item.not_in_warehouse ? '1' : '0';

                tr.innerHTML = `
            <td class="text-muted small mobile-hide">${idx + 1}</td>
            <td>
                <div class="adj-row-main">
                    <div>
                        <span class="text-mono fw-semibold" style="font-size:.86rem;">${item.code ?? ''}</span>
                        ${item.not_in_warehouse ? '<span class="item-badge">baru</span>' : ''}
                        ${lotCode ? `<span class="item-badge bg-secondary text-white border-secondary">Lot: ${lotCode}</span>` : ''}
                        <div style="font-size:.78rem;color:#6b7280;margin-top:.1rem;">${item.name ?? ''}</div>
                    </div>
                    <span class="diff-display mobile-hide text-mono" style="font-size:.8rem;">0.00</span>
                </div>
                <div class="adj-row-meta mobile-hide">
                    <span>Stok: <strong class="text-mono"><span class="on-hand" data-on-hand="${onHand}">${fmt(onHand)}</span></strong></span>
                </div>
                <input type="hidden" class="row-item-id" value="${item.id}">
                <input type="hidden" class="row-lot-id" value="${lotId}">
            </td>""")

# Replace snapshotInputs
content = content.replace("""            function snapshotInputs() {
                const state = {};
                tbody.querySelectorAll('tr').forEach(tr => {
                    const id = tr.querySelector('.row-item-id')?.value;
                    if (!id) return;
                    state[id] = {
                        physical: tr.querySelector('.physical-input')?.value || '',
                        notes: tr.querySelector('.notes-input')?.value || '',
                    };
                });
                return state;
            }

            function restoreInputs(state) {
                tbody.querySelectorAll('tr').forEach(tr => {
                    const id = tr.querySelector('.row-item-id')?.value;
                    if (!id || !state[id]) return;
                    const physical = tr.querySelector('.physical-input');
                    const notes = tr.querySelector('.notes-input');
                    if (physical) physical.value = state[id].physical || '';
                    if (notes) notes.value = state[id].notes || '';
                    recalcRow(tr);
                });
            }

            function mergeItems(items) {
                const byId = new Map(warehouseItems.map(item => [String(item.id), item]));
                (items || []).forEach(item => {
                    const key = String(item.id);
                    if (byId.has(key)) { byId.set(key, { ...byId.get(key), ...item }); }
                    else { byId.set(key, item); }
                });
                warehouseItems = Array.from(byId.values()).sort((a, b) => String(a.code || '').localeCompare(String(b.code || '')));
            }""", """            function snapshotInputs() {
                const state = {};
                tbody.querySelectorAll('tr').forEach(tr => {
                    const id = tr.querySelector('.row-item-id')?.value;
                    const lotId = tr.querySelector('.row-lot-id')?.value || '';
                    if (!id) return;
                    const key = id + '_' + lotId;
                    state[key] = {
                        physical: tr.querySelector('.physical-input')?.value || '',
                        notes: tr.querySelector('.notes-input')?.value || '',
                    };
                });
                return state;
            }

            function restoreInputs(state) {
                tbody.querySelectorAll('tr').forEach(tr => {
                    const id = tr.querySelector('.row-item-id')?.value;
                    const lotId = tr.querySelector('.row-lot-id')?.value || '';
                    const key = id + '_' + lotId;
                    if (!id || !state[key]) return;
                    const physical = tr.querySelector('.physical-input');
                    const notes = tr.querySelector('.notes-input');
                    if (physical) physical.value = state[key].physical || '';
                    if (notes) notes.value = state[key].notes || '';
                    recalcRow(tr);
                });
            }

            function mergeItems(items) {
                const byId = new Map(warehouseItems.map(item => [String(item.id) + '_' + (item.lot_id||''), item]));
                (items || []).forEach(item => {
                    const key = String(item.id) + '_' + (item.lot_id||'');
                    if (byId.has(key)) { byId.set(key, { ...byId.get(key), ...item }); }
                    else { byId.set(key, item); }
                });
                warehouseItems = Array.from(byId.values()).sort((a, b) => String(a.code || '').localeCompare(String(b.code || '')));
            }""")

# Replace submit loop
content = content.replace("""            form.addEventListener('submit', function() {
                let outIndex = 0;
                tbody.querySelectorAll('tr').forEach(tr => {
                    tr.querySelectorAll('[data-named="1"]').forEach(el => el.remove());
                    const changed = tr.dataset.changed === '1';
                    const qtyChange = parseFloat(tr.querySelector('.qty-change-input').value || '0');
                    if (!changed || isNaN(qtyChange) || Math.abs(qtyChange) < 0.000001) return;
                    const itemId = tr.querySelector('.row-item-id').value;
                    const notes = tr.querySelector('.notes-input').value || '';
                    [['item_id', itemId], ['qty_change', qtyChange.toFixed(2)], ['notes', notes]].forEach(([name, value]) => {
                        const h = document.createElement('input');
                        h.type = 'hidden'; h.name = `lines[${outIndex}][${name}]`; h.value = value; h.dataset.named = '1';
                        tr.appendChild(h);
                    });
                    outIndex++;
                });
            });""", """            form.addEventListener('submit', function() {
                let outIndex = 0;
                tbody.querySelectorAll('tr').forEach(tr => {
                    tr.querySelectorAll('[data-named="1"]').forEach(el => el.remove());
                    const changed = tr.dataset.changed === '1';
                    const qtyChange = parseFloat(tr.querySelector('.qty-change-input').value || '0');
                    if (!changed || isNaN(qtyChange) || Math.abs(qtyChange) < 0.000001) return;
                    const itemId = tr.querySelector('.row-item-id').value;
                    const lotId = tr.querySelector('.row-lot-id')?.value || '';
                    const notes = tr.querySelector('.notes-input').value || '';
                    
                    const fields = [['item_id', itemId], ['qty_change', qtyChange.toFixed(2)], ['notes', notes]];
                    if (lotId) fields.push(['lot_id', lotId]);
                    
                    fields.forEach(([name, value]) => {
                        const h = document.createElement('input');
                        h.type = 'hidden'; h.name = `lines[${outIndex}][${name}]`; h.value = value; h.dataset.named = '1';
                        tr.appendChild(h);
                    });
                    outIndex++;
                });
            });""")

with open('resources/views/inventory/adjustments/manual_create.blade.php', 'w') as f:
    f.write(content)

print("done")
