# Runbook: Scheduler Sync Ads Dashboard (Hourly + Daily)

Halaman terkait: `/marketplace/ads-dashboard`

## Jadwal yang aktif

Semua jadwal ads didefinisikan di `bootstrap/app.php` → `withSchedule()` (Laravel 12).
`app/Console/Kernel.php` **tidak dipakai** oleh Laravel 12 — jadwal di sana tidak jalan.

| Nama | Command | Jadwal | Isi |
|---|---|---|---|
| `sync-ads-main` | `marketplace:sync-ads` | Tiap 4 jam, menit 0 | Balance + Campaigns + Shop/CPC/GMS daily, 3 hari terakhir |
| `sync-ads-hourly` | `marketplace:sync-ads --hourly` | Tiap jam, menit 30 | Hourly performance (heatmap), kemarin + hari ini |

Menit 30 untuk hourly dipilih supaya tidak tabrakan dengan `sync-ads-main` yang jalan di menit 0.
Output kedua jadwal ditulis ke `storage/logs/sync-ads.log`.

## PENTING: kenapa sebelumnya tidak jalan

Jadwalnya sudah benar didefinisikan, tapi **tidak ada yang memanggil `schedule:run`**.
Scheduler Laravel butuh cron (atau `schedule:work`) yang menjalankan `php artisan schedule:run` **setiap menit**. Tanpa itu, semua jadwal diam — di dev maupun prod. Bukti di DB: tabel `marketplace_ads_sync_runs` hanya berisi `manual_dashboard` (klik tombol sync), tidak ada `hourly_all` / `daily_all`.

## Setup DEV (Mac + Herd) — crontab

1. Cari path PHP milik Herd:

   ```bash
   which php
   # biasanya: /Users/ariefmuhamad/Library/Application Support/Herd/bin/php
   ```

2. Edit crontab:

   ```bash
   crontab -e
   ```

3. Tambahkan satu baris (sesuaikan path PHP dari langkah 1):

   ```cron
   * * * * * cd /Users/ariefmuhamad/Herd/gfid-dev && "/Users/ariefmuhamad/Library/Application Support/Herd/bin/php" artisan schedule:run >> /Users/ariefmuhamad/Herd/gfid-dev/storage/logs/scheduler.log 2>&1
   ```

4. Simpan. macOS mungkin minta izin "Full Disk Access" untuk `cron` saat pertama kali — izinkan lewat System Settings → Privacy & Security bila diminta.

Alternatif tanpa crontab (hanya jalan selama terminal terbuka):

```bash
php artisan schedule:work
```

## Setup PROD (VPS via SSH)

Login sebagai user yang menjalankan aplikasi (bukan root, kecuali app-nya milik root), lalu:

```bash
crontab -e
```

Tambahkan:

```cron
* * * * * cd /path/ke/gfid && php artisan schedule:run >> /dev/null 2>&1
```

Catatan:

- Ganti `/path/ke/gfid` dengan path deploy sebenarnya; kalau `php` tidak ada di PATH cron, pakai path absolut (`which php` di server, mis. `/usr/bin/php8.3`).
- Pastikan `APP_ENV=production` dan timezone di `config/app.php` sesuai (jadwal mengikuti timezone aplikasi).
- Cukup SATU baris cron ini untuk semua jadwal (orders, chats, ads, dst.) — jangan buat cron terpisah per command.

## Verifikasi

1. Lihat daftar jadwal + waktu eksekusi berikutnya:

   ```bash
   php artisan schedule:list
   ```

   Harus muncul `marketplace:sync-ads` (tiap 4 jam) dan `marketplace:sync-ads '--hourly'` (menit 30 tiap jam).

2. Tes manual sekali jalan:

   ```bash
   php artisan marketplace:sync-ads --hourly
   php artisan marketplace:sync-ads
   ```

3. Setelah cron terpasang, tunggu lewat menit 30, lalu cek:

   ```bash
   tail -f storage/logs/sync-ads.log
   ```

   dan cek DB:

   ```sql
   SELECT id, sync_type, status, started_at, finished_at
   FROM marketplace_ads_sync_runs ORDER BY id DESC LIMIT 10;
   ```

   Sync terjadwal tercatat sebagai `hourly_all` / `daily_all` (klik manual dari dashboard = `manual_dashboard`).

4. Data dashboard: heatmap membaca `marketplace_ads_hourly_performances`, tren harian membaca `marketplace_ads_dailies` — dua-duanya harus bertambah baris tanpa klik tombol sync.

## Pembagian tempat definisi jadwal (sudah dirapikan)

- `bootstrap/app.php` → `crm:weekly-summary`, `sales:rebuild-daily-item-sales` (01:00), `inventory:recalc-ads-from-daily` (01:10), `marketplace:sync-ads` (daily tiap 4 jam + hourly menit 30).
- `routes/console.php` → semua jadwal marketplace/storefront lain (orders, bookings, returns, settlements, finance, chats, snapshot-products, run-boosts, queue-work, audit-inventory-allocated, cleanup-labels).
- `app/Console/Kernel.php` → **tidak dipakai** Laravel 12; sudah dikosongkan dan hanya berisi catatan. Boleh dihapus (`git rm app/Console/Kernel.php`).

Perapian yang sudah dilakukan:

- Duplikat `sales:rebuild-daily-item-sales` & `inventory:recalc-ads-from-daily` (dulu terdaftar 2× — 00:05/00:10 dan 01:00/01:10) → kini hanya di `bootstrap/app.php`.
- Pola `->name('task_' . uniqid())` dihapus; semua event pakai nama statis sehingga `withoutOverlapping()` benar-benar bekerja.
- Dua jadwal yang dulu hanya ada di `Kernel.php` (jadi tidak pernah jalan) dipindahkan ke `routes/console.php`: `marketplace:sync-bookings` (tiap jam) dan `inventory:audit-allocated` (00:45).
