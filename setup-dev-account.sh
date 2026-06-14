#!/bin/bash
# Setup akun developer + developer mode
# Jalankan dari root project: bash setup-dev-account.sh

echo "→ Menjalankan migration is_developer..."
php artisan migrate --path=database/migrations/2026_06_14_000001_add_is_developer_to_users_table.php --force

echo ""
echo "→ Membuat akun developer..."
php artisan db:seed --class=DeveloperUserSeeder

echo ""
echo "✓ Selesai! Login dengan:"
echo "   Employee Code : DEV"
echo "   Password      : developer"
