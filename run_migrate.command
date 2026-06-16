#!/bin/bash
# Script migrate + optimize:clear untuk gfid-dev
cd /Users/ariefmuhamad/Herd/gfid-dev

echo "======================================"
echo "  php artisan migrate"
echo "======================================"
php artisan migrate

echo ""
echo "======================================"
echo "  php artisan optimize:clear"
echo "======================================"
php artisan optimize:clear

echo ""
echo "======================================"
echo "  SELESAI — bisa ditutup"
echo "======================================"
read -p "Tekan Enter untuk tutup..."
