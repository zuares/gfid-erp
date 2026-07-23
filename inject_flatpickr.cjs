const fs = require('fs');
const path = '/Users/ariefmuhamad/Herd/gfid-dev/resources/views/marketplace/ads.blade.php';
let content = fs.readFileSync(path, 'utf8');

if (!content.includes('flatpickr.min.css')) {
    content = content.replace("@push('head')", "@push('head')\n<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css\">\n<link rel=\"stylesheet\" type=\"text/css\" href=\"https://npmcdn.com/flatpickr/dist/themes/airbnb.css\">");
}

if (!content.includes('flatpickr"></script>')) {
    content = content.replace("@push('scripts')", "@push('scripts')\n<script src=\"https://cdn.jsdelivr.net/npm/flatpickr\"></script>");
}

fs.writeFileSync(path, content);
console.log("Flatpickr CDN injected");
