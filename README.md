============================================================
 GFID – DATABASE BACKUP & RESTORE ARTISAN COMMANDS
============================================================

1) Membuat backup database SQLite terbaru
------------------------------------------------------------
php artisan db:backup


2) Melihat daftar semua file backup
------------------------------------------------------------
php artisan db:backup:list


3) Melihat daftar backup dengan batas jumlah tertentu
------------------------------------------------------------
php artisan db:backup:list --limit=5
php artisan db:backup:list --limit=10


4) Restore database dari file backup tertentu
------------------------------------------------------------
php artisan db:restore backup_20251120_163501.sqlite


5) Backup otomatis sebelum migrate:fresh
------------------------------------------------------------
php artisan migrate:fresh

(Perintah ini otomatis akan membuat backup sebelum database direset)


============================================================
 Lokasi File Backup:
 storage/backups/
============================================================
