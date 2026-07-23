const fs = require('fs');
const path = '/Users/ariefmuhamad/Herd/gfid-dev/resources/views/marketplace/ads.blade.php';
let content = fs.readFileSync(path, 'utf8');

// Fix 1: Event listener for date tabs only
const bug1 = `    document.querySelectorAll('.period-tab').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.period-tab').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            setDateRange(parseInt(this.dataset.days));
            loadAds();
        });
    });`;

const fix1 = `    document.querySelectorAll('.period-tab[data-days]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.period-tab[data-days]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            setDateRange(parseInt(this.dataset.days));
            loadAds();
        });
    });`;

content = content.replace(bug1, fix1);

// Fix 2: Flatpickr removing active from view tabs
const bug2 = `                        // Hapus status active dari period-tabs karena pakai custom date
                        document.querySelectorAll('.period-tab').forEach(b => b.classList.remove('active'));`;

const fix2 = `                        // Hapus status active dari period-tabs karena pakai custom date
                        document.querySelectorAll('.period-tab[data-days]').forEach(b => b.classList.remove('active'));`;

content = content.replace(bug2, fix2);

fs.writeFileSync(path, content);
console.log("JS tab toggle bugs fixed successfully");
