#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# deploy-marketplace-sync-ux.sh
# Backup → migrate → cache clear
# Jalankan dari root project:  bash deploy-marketplace-sync-ux.sh
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

BACKUP_DIR="storage/app/backups/marketplace-sync-ux"
TS=$(date +%Y%m%d_%H%M%S)

bk() {
    if [ -f "$1" ]; then
        mkdir -p "$BACKUP_DIR"
        cp "$1" "$BACKUP_DIR/${TS}_$(basename "$1")"
        echo "  ✓ backup: $1"
    fi
}

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "  🚀  deploy-marketplace-sync-ux"
echo "═══════════════════════════════════════════════════════════"

echo ""
echo "── 1. Backup file lama ──────────────────────────────────"
bk "app/Services/MarketplaceIssueService.php"
bk "app/Services/MarketplaceSyncService.php"
bk "app/Http/Controllers/MarketplaceController.php"
bk "app/Models/MarketplaceOrderItem.php"
bk "resources/views/marketplace/toko.blade.php"
bk "resources/views/marketplace/issues.blade.php"
bk "resources/views/marketplace/fulfillment.blade.php"
bk "resources/views/layouts/partials/sidebar.blade.php"
bk "routes/web.php"
echo "  Backup tersimpan di: $BACKUP_DIR"

echo ""
echo "── 2. Jalankan migration ────────────────────────────────"
php artisan migrate --force

echo ""
echo "── 3. Clear cache ───────────────────────────────────────"
php artisan route:clear
php artisan view:clear
php artisan config:clear

echo ""
echo "── 4. Re-map existing order items ───────────────────────"
echo "  (Mengisi data_status + marketplace_sku untuk order lama…)"
php artisan tinker --no-interaction << 'TINKER'
$result = app(\App\Services\MarketplaceIssueService::class)->remapItems();
echo "  Remap selesai: updated={$result['updated']}, errors={$result['errors']}\n";
TINKER

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "  ✅  SELESAI!"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "  File yang diubah:"
echo "    + database/migrations/2026_06_09_210000_add_data_status_…"
echo "    ~ app/Services/MarketplaceIssueService.php"
echo "    ~ app/Services/MarketplaceSyncService.php"
echo "    ~ app/Http/Controllers/MarketplaceController.php"
echo "    ~ app/Models/MarketplaceOrderItem.php"
echo "    ~ resources/views/marketplace/toko.blade.php"
echo "    ~ resources/views/marketplace/issues.blade.php   (→ Data Perlu Diperbaiki)"
echo "    ~ resources/views/marketplace/fulfillment.blade.php"
echo "    ~ resources/views/layouts/partials/sidebar.blade.php"
echo "    ~ routes/web.php"
echo ""
echo "  Flow setelah deploy:"
echo "    /marketplace/toko  → Sync Order → Sync Result Summary Modal"
echo "    /marketplace/issues → Data Perlu Diperbaiki"
echo "       ↳ Isi SKU / Mapping Sekarang / Isi HPP dari modal"
echo "    /marketplace/fulfillment → hanya order data_status=valid"
echo ""
