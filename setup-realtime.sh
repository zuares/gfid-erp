#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# setup-realtime.sh — Setup realtime orders (Laravel Reverb)
# Jalankan SEKALI dari root project:  bash setup-realtime.sh
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

echo "📦  Install laravel/reverb …"
composer require laravel/reverb --no-interaction

echo ""
echo "🧹  Clear cache …"
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "✅  SELESAI!"
echo ""
echo "════════════════════════════════════════════════════════"
echo "  Untuk DEV (Herd), jalankan 2 proses ini di terminal terpisah:"
echo ""
echo "    php artisan reverb:start        # WebSocket server (port 8080)"
echo "    php artisan queue:work          # Proses job webhook"
echo ""
echo "  Test: buka /marketplace/orders lalu kirim simulasi webhook"
echo "  dari halaman /marketplace/webhook-tests → row order harus"
echo "  ter-update TANPA refresh halaman."
echo ""
echo "  Untuk PRODUCTION lihat: RUNBOOK_REALTIME_REVERB.md"
echo "════════════════════════════════════════════════════════"
