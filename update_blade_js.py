import re

with open('/Users/ariefmuhamad/Herd/gfid-dev/resources/views/purchasing/purchase_returns/show.blade.php', 'r') as f:
    content = f.read()

# 1. Add the "Tambah Item" section before the table
tambah_html = """
    @if($isEditable)
      <div class="d-flex flex-wrap gap-2 mb-3 align-items-end" id="add-item-section">
        <div class="flex-grow-1" style="max-width: 400px;">
          <label class="form-label" style="font-size: 0.75rem; font-weight: 600; color: #4b5563;">Tambah Item Retur</label>
          <select id="item-selector" class="pr-select">
            <option value="">-- Pilih item yang ingin diretur --</option>
            @foreach($returnRows as $i => $row)
              <option value="{{ $i }}">{{ $row->item?->name ?? '-' }} (Tersedia: {{ rtrim(rtrim(number_format((float)($row->max_return ?? $row->remaining ?? 0), 4, ',', '.'), '0'), ',') }})</option>
            @endforeach
          </select>
        </div>
        <button type="button" class="pr-btn pr-btn-primary" id="btn-add-item"><i class="bi bi-plus-lg"></i> Tambah</button>
      </div>
    @endif
"""
content = content.replace('<div class="pr-table-wrap">', tambah_html + '\n    <div class="pr-table-wrap" id="return-table-wrap">')

# 2. Add 'Remove' button to the rows
# In the actions column (Item Actions), we add a remove button
remove_btn_html = """
                  <div class="item-actions">
                    <select name="lines[{{ $i }}][reason_code]" class="pr-select reason-input" style="padding: 0.375rem; font-size: 0.75rem;">
                      <option value="">- Pilih Alasan -</option>
                      @foreach($reasons as $code => $label)
                        <option value="{{ $code }}" @selected(old("lines.$i.reason_code", $ln?->reason_code) === $code)>{{ $label }}</option>
                      @endforeach
                    </select>
                    <div class="d-flex gap-1">
                        <input type="text" name="lines[{{ $i }}][notes]" class="pr-input notes-input" style="padding: 0.375rem; font-size: 0.75rem;" placeholder="Catatan item..." value="{{ old("lines.$i.notes", $row->notes) }}">
                        <button type="button" class="pr-btn pr-btn-danger px-2 btn-remove-row" style="padding: 0.375rem;" title="Hapus dari daftar"><i class="bi bi-trash"></i></button>
                    </div>
                  </div>
"""
# Need to replace the old item-actions
old_item_actions = """                  <div class="item-actions">
                    <select name="lines[{{ $i }}][reason_code]" class="pr-select" style="padding: 0.375rem; font-size: 0.75rem;">
                      <option value="">- Pilih Alasan -</option>
                      @foreach($reasons as $code => $label)
                        <option value="{{ $code }}" @selected(old("lines.$i.reason_code", $ln?->reason_code) === $code)>{{ $label }}</option>
                      @endforeach
                    </select>
                    <input type="text" name="lines[{{ $i }}][notes]" class="pr-input" style="padding: 0.375rem; font-size: 0.75rem;" placeholder="Catatan item..." value="{{ old("lines.$i.notes", $row->notes) }}">
                  </div>"""
content = content.replace(old_item_actions, remove_btn_html)

# Add data-row-idx to tr
content = content.replace('<tr class="return-row {{ $rowClass }}">', '<tr class="return-row {{ $rowClass }}" data-row-idx="{{ $i }}">')

# 3. Add Javascript to handle the logic
js_logic = """
  const itemSelector = document.getElementById('item-selector');
  const btnAddItem = document.getElementById('btn-add-item');
  const tableWrap = document.getElementById('return-table-wrap');
  
  // Logic to hide empty rows on load and populate select
  function initDynamicRows() {
    const isEditable = document.getElementById('item-selector') !== null;
    let visibleCount = 0;
    
    document.querySelectorAll('.return-row').forEach(row => {
        const qtyInput = row.querySelector('.qty-return-input');
        const reasonInput = row.querySelector('.reason-input');
        const notesInput = row.querySelector('.notes-input');
        
        let hasData = false;
        if (qtyInput && toNumber(qtyInput.value) > 0.0001) hasData = true;
        if (reasonInput && reasonInput.value !== '') hasData = true;
        if (notesInput && notesInput.value !== '') hasData = true;
        // Check if there are existing photos
        if (row.querySelector('.photo-thumb-wrap') || row.querySelector('.photo-thumb')) hasData = true;
        
        if (isEditable) {
            const rowIdx = row.dataset.rowIdx;
            const option = itemSelector.querySelector(`option[value="${rowIdx}"]`);
            
            if (!hasData) {
                row.style.display = 'none';
                if(option) option.style.display = '';
            } else {
                row.style.display = '';
                if(option) option.style.display = 'none';
                visibleCount++;
            }
        } else {
            if (!hasData) row.style.display = 'none';
            else visibleCount++;
        }
    });
    
    // Hide table wrap if no items visible
    if (tableWrap) {
        tableWrap.style.display = visibleCount > 0 ? '' : 'none';
    }
  }

  if (itemSelector) {
      btnAddItem.addEventListener('click', function() {
          const idx = itemSelector.value;
          if (!idx) return;
          
          const row = document.querySelector(`.return-row[data-row-idx="${idx}"]`);
          if (row) {
              row.style.display = '';
              itemSelector.querySelector(`option[value="${idx}"]`).style.display = 'none';
              itemSelector.value = '';
              
              if (tableWrap) tableWrap.style.display = '';
              
              // Focus the qty input
              setTimeout(() => {
                  const qtyInput = row.querySelector('.qty-return-input');
                  if(qtyInput) {
                      qtyInput.focus();
                      qtyInput.select();
                  }
              }, 50);
          }
      });
      
      document.querySelectorAll('.btn-remove-row').forEach(btn => {
          btn.addEventListener('click', function() {
              const row = this.closest('.return-row');
              const idx = row.dataset.rowIdx;
              
              // Clear values
              const qtyInput = row.querySelector('.qty-return-input');
              const reasonInput = row.querySelector('.reason-input');
              const notesInput = row.querySelector('.notes-input');
              
              if (qtyInput) qtyInput.value = '';
              if (reasonInput) reasonInput.value = '';
              if (notesInput) notesInput.value = '';
              
              // Remove new photos (hacky but works for UI reset)
              const newPhotos = row.querySelectorAll('input[type="file"]');
              newPhotos.forEach(input => input.value = '');
              
              // Hide row and show option
              row.style.display = 'none';
              const option = itemSelector.querySelector(`option[value="${idx}"]`);
              if (option) option.style.display = '';
              
              refreshTotals();
              
              // Check if all are hidden
              const anyVisible = Array.from(document.querySelectorAll('.return-row')).some(r => r.style.display !== 'none');
              if (!anyVisible && tableWrap) tableWrap.style.display = 'none';
          });
      });
  }
  
  // Call init on load
  initDynamicRows();
"""

# Insert JS logic into the script block
content = content.replace('function refreshTotals() {', js_logic + '\n  function refreshTotals() {')

with open('/Users/ariefmuhamad/Herd/gfid-dev/resources/views/purchasing/purchase_returns/show.blade.php', 'w') as f:
    f.write(content)
