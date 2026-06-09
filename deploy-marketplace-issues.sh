#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# deploy-marketplace-issues.sh
# Jalankan dari root project Laravel:  bash deploy-marketplace-issues.sh
# Semua file PHP sudah ditulis ke project — script ini HANYA jalankan artisan.
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

echo "🗄  Menjalankan migration …"
php artisan migrate --force

echo ""
echo "🧹  Clear cache …"
php artisan route:clear
php artisan view:clear
php artisan config:clear

echo ""
echo "✅  SELESAI!"
echo ""
echo "════════════════════════════════════════════════════════"
echo "  File yang ditambahkan / diubah:"
echo "    + database/migrations/2026_06_09_200000_add_mapping_fields…"
echo "    + app/Services/MarketplaceIssueService.php"
echo "    ~ app/Models/MarketplaceOrderItem.php"
echo "    ~ app/Services/MarketplaceSyncService.php"
echo "    ~ app/Http/Controllers/MarketplaceController.php"
echo "    + resources/views/marketplace/issues.blade.php"
echo "    ~ resources/views/marketplace/toko.blade.php"
echo "    ~ resources/views/layouts/partials/sidebar.blade.php"
echo "    ~ routes/web.php"
echo ""
echo "  Langkah selanjutnya:"
echo "    1. Buka /marketplace/issues → Issue Center"
echo "    2. Klik '⟳ Re-map Semua Item' untuk proses order lama"
echo "    3. /marketplace/toko → warning cards muncul jika ada issue"
echo "    4. Sync order baru → mapping_status, cost_status, hpp_snapshot"
echo "       otomatis terisi tanpa perlu remap manual"
echo "════════════════════════════════════════════════════════"
