const fs = require('fs');
const path = '/Users/ariefmuhamad/Herd/gfid-dev/resources/views/marketplace/ads.blade.php';
let content = fs.readFileSync(path, 'utf8');

const oldHtml = `<input type="date" class="filter-input" id="dateFrom">
            <span class="filter-label" style="margin: 0 -.1rem">-</span>
            <input type="date" class="filter-input" id="dateTo">`;

const newHtml = `<div style="position:relative; display:flex; align-items:center;">
                <i class="bi bi-calendar3" style="position:absolute; left: .65rem; color:#64748b; font-size:.75rem; pointer-events:none;"></i>
                <input type="text" id="dateRangePicker" class="filter-input text-nowrap" style="padding-left:1.9rem; width:180px; cursor:pointer; text-align:center; font-size:.78rem;" placeholder="Pilih Tanggal..." readonly>
            </div>
            <input type="hidden" id="dateFrom">
            <input type="hidden" id="dateTo">`;

content = content.replace(oldHtml, newHtml);

// Fix setDateRange function
const oldSetDateRange = `function setDateRange(days) {
        const to = new Date(), from = new Date();
        from.setDate(from.getDate() - (days - 1));
        $('dateFrom').value = toDateStr(from);
        $('dateTo').value   = toDateStr(to);
    }`;

const newSetDateRange = `function setDateRange(days) {
        const to = new Date(), from = new Date();
        from.setDate(from.getDate() - (days - 1));
        $('dateFrom').value = toDateStr(from);
        $('dateTo').value   = toDateStr(to);
        if (window._adsDatePicker) {
            window._adsDatePicker.setDate([from, to]);
        }
    }`;

content = content.replace(oldSetDateRange, newSetDateRange);

// Add init code for flatpickr inside the init function
const oldInit = `async function init() {
        stores = await api('/api/marketplace/stores').catch(() => []);`;

const newInit = `async function init() {
        if (window.flatpickr) {
            window._adsDatePicker = flatpickr('#dateRangePicker', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd M Y',
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        $('dateFrom').value = instance.formatDate(selectedDates[0], 'Y-m-d');
                        $('dateTo').value = instance.formatDate(selectedDates[1], 'Y-m-d');
                        // Optional: auto-refresh on select
                        // loadAds();
                        
                        // Hapus status active dari period-tabs karena pakai custom date
                        document.querySelectorAll('.period-tab').forEach(b => b.classList.remove('active'));
                    }
                }
            });
        }
        
        stores = await api('/api/marketplace/stores').catch(() => []);`;

content = content.replace(oldInit, newInit);

fs.writeFileSync(path, content);
console.log("Flatpickr updated successfully");
