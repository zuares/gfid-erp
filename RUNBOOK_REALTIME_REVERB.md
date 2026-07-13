# Runbook: Realtime Marketplace Orders (Shopee Webhook → Reverb → Browser)

Alur lengkap setelah setup ini:

```
Shopee Push ──► POST /api/webhooks/shopee ──► ProcessShopeeWebhookJob (queue)
                                                  │
                            order ada?  ─ ya ──►  update status lokal
                                        ─ tidak ► syncSpecificOrder via API
                                                  │
                                        event OrderUpdated (broadcast)
                                                  │
                              Reverb (WebSocket) ──► browser /marketplace/orders
                                                  └► row ter-update tanpa refresh
```

Safety net kalau WebSocket/webhook mati: halaman orders otomatis polling tiap 30 detik
(normalnya 5 menit saat WebSocket konek), plus scheduler `marketplace:sync-orders` tetap jalan.

---

## 1. Setup lokal (Herd) — sekali saja

```bash
bash setup-realtime.sh
```

Lalu jalankan 2 proses (terminal terpisah, biarkan hidup):

```bash
php artisan reverb:start     # WebSocket server di port 8080
php artisan queue:work       # proses job webhook (QUEUE_CONNECTION=database)
```

`.env` lokal sudah diisi (`BROADCAST_CONNECTION=reverb`, `REVERB_*`).

**Test:** buka `/marketplace/orders`, lalu dari `/marketplace/webhook-tests` kirim
simulasi `order_status_update`. Row order harus berubah tanpa refresh (maks ~1 detik).

---

## 2. Production greatfit.id — jika VPS (bisa daemon) ✅ disarankan

### 2a. Env production

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=139419
REVERB_APP_KEY=0d1465132b155ce4dc94        # bebas per environment, tidak harus sama dgn lokal
REVERB_APP_SECRET=GANTI_DENGAN_RANDOM_BARU # openssl rand -hex 20
REVERB_HOST="greatfit.id"
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
```

Lalu: `composer require laravel/reverb && php artisan config:clear` (atau `config:cache` ulang).

### 2b. Nginx — proxy WebSocket ke Reverb

Tambahkan di server block `greatfit.id` (yang sudah ada SSL):

```nginx
location ~ ^/app/ {
    proxy_http_version 1.1;
    proxy_set_header Host $http_host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_pass http://127.0.0.1:8080;
}
location ~ ^/apps/ {
    proxy_http_version 1.1;
    proxy_set_header Host $http_host;
    proxy_pass http://127.0.0.1:8080;
}
```

`nginx -t && systemctl reload nginx`

### 2c. Supervisor — 2 daemon wajib hidup

`/etc/supervisor/conf.d/gfid-realtime.conf`:

```ini
[program:gfid-reverb]
command=php /path/ke/project/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
stopwaitsecs=10
stdout_logfile=/var/log/gfid-reverb.log

[program:gfid-queue]
command=php /path/ke/project/artisan queue:work --sleep=1 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=2
process_name=%(program_name)s_%(process_num)02d
user=www-data
stopwaitsecs=30
stdout_logfile=/var/log/gfid-queue.log
```

```bash
supervisorctl reread && supervisorctl update && supervisorctl status
```

> ⚠️ **`queue:work` ini yang paling sering bikin order "nyangkut".**
> `QUEUE_CONNECTION=database` tanpa worker = job webhook menumpuk di tabel `jobs`
> dan tidak pernah dieksekusi. Cek cepat: `php artisan queue:monitor database:default`
> atau `SELECT COUNT(*) FROM jobs;` — harus ~0.

### 2d. Daftarkan webhook di Shopee Open Platform Console

- Push mechanism URL: `https://greatfit.id/api/webhooks/shopee`
- Pastikan `SHOPEE_PUSH_KEY` di env production = Partner Key untuk push (dari console).
- Di production signature **wajib valid** — request dengan signature salah sekarang
  ditolak (dicatat di log sebagai warning + tetap masuk `webhook_logs` untuk audit).

---

## 3. Production — jika shared hosting (tidak bisa daemon) ⚠️ fallback

Reverb butuh proses hidup terus → tidak jalan di shared hosting biasa. Pilihan:

1. **Pusher Cloud** (paling praktis): buat app di pusher.com, isi `PUSHER_*` di env,
   set `BROADCAST_CONNECTION=pusher`, dan ganti snippet Echo di
   `resources/views/layouts/app.blade.php` ke broadcaster `pusher`.
2. **Tanpa WebSocket**: biarkan `BROADCAST_CONNECTION=log`. Halaman orders otomatis
   mendeteksi Echo tidak konek → polling tiap 30 detik. Webhook + API sync tetap jalan,
   hanya delay maks 30 detik.
3. Queue tanpa daemon: cron tiap menit
   `* * * * * php /path/artisan queue:work --stop-when-empty --max-time=50`

---

## 4. Checklist verifikasi end-to-end

| # | Cek | Cara |
|---|-----|------|
| 1 | Reverb hidup | `supervisorctl status gfid-reverb` / buka browser console: Echo state `connected` |
| 2 | Queue worker hidup | `SELECT COUNT(*) FROM jobs` ≈ 0 |
| 3 | Webhook masuk | `/marketplace/webhook-tests` → logs, kolom `signature_verified` = true |
| 4 | Order baru auto muncul | buat order test di Shopee → muncul di `/marketplace/orders` tanpa refresh |
| 5 | Failed jobs | `php artisan queue:failed` — kosong |

## File yang diubah/ditambah

- `config/reverb.php` (baru)
- `.env` — `BROADCAST_CONNECTION=reverb` + blok `REVERB_*`
- `resources/views/layouts/app.blade.php` — Echo via CDN (pusher-js + laravel-echo iife)
- `resources/views/marketplace/orders.blade.php` — listener realtime channel `marketplace`
  event `OrderUpdated` (debounce 800 ms) + polling adaptif (5 mnt konek / 30 dtk putus)
- `app/Http/Controllers/WebhookController.php` — signature wajib valid di production
- `setup-realtime.sh` (baru) — installer sekali jalan
