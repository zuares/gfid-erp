# Rancangan Analisa Iklan — Marketplace Ads

**Halaman:** `/marketplace/ads` (`marketplace.ads` → `MarketplaceController::ads()`)
**Sumber data:** Shopee Ads API (auto-sync)
**Tujuan:** simpan data iklan ke DB dengan struktur yang mudah di-scale up untuk analisa, plus grouping (per item internal **dan** grup manual) dan mapping produk iklan → item internal (beberapa judul produk, item internal sama).
**Status dokumen:** rancangan untuk direview dulu — belum dieksekusi.

---

## 1. Kondisi saat ini (hasil audit)

| Objek | Status | Catatan |
|---|---|---|
| `marketplace_ad_campaigns` | tabel **ada**, kosong (0 baris) | Menyimpan **agregat 1 rentang tanggal** per campaign (`report_date_from`..`report_date_to` di-SUM jadi 1 baris). Tidak ada granularity harian, tidak ada `item_id`, tidak ada grouping. |
| `marketplace_ads_dailies` | migrasi **ada**, tabel **belum dibuat** | Daily **level TOKO** (bukan per campaign, bukan per item). |
| `marketplace_ads_balance_logs` | migrasi **ada**, tabel **belum dibuat** | Riwayat saldo iklan (burn rate). |
| `mp_ads_imports` / `mp_ads_rows` | tabel ada, kosong | Jalur import CSV — sudah **deprecated** (`MpAdsImportController` redirect ke halaman baru). Bisa diabaikan / dibuang nanti. |
| `sku_mappings` | ada, 53 baris | Backbone mapping: `marketplace_sku` + `channel_code` → `item_id` (item internal). |
| `marketplace_order_items` | ada | Sudah punya pola `external_item_id` (item_id Shopee) → `internal_item_id`, `mapping_status`. |

**Sync yang jalan sekarang** (`MarketplaceSyncService::syncAdCampaigns`):
1. `getShopToggleInfo` → cek iklan aktif.
2. `getCampaignIdList` → daftar `{campaign_id, ad_type}`.
3. `getCampaignDailyPerformance` (batch 50) → metrics harian, **tapi langsung di-SUM** jadi 1 baris per campaign.
4. Upsert ke `marketplace_ad_campaigns`.

**Gap utama untuk analisa yang bisa di-scale up:**
- Data harian dibuang (di-SUM). Tidak bisa lihat tren per hari, bandingkan periode, atau rebuild rentang lain tanpa re-sync.
- Tidak ada `item_id` produk Shopee di campaign → tidak bisa mapping ke item internal.
- Tidak ada konsep grup.

---

## 2. Prinsip desain

1. **Simpan data mentah paling granular sekali saja, agregasi belakangan.** Grain terkecil dari Shopee Ads API = **per campaign per hari**. Semua KPI (rentang apapun, per item, per grup) tinggal SUM/agregasi dari sana. Ini kunci "mudah di-scale up".
2. **Pisahkan fakta (angka harian) dari dimensi (campaign, item, grup).** Fakta append-only & idempoten; dimensi bisa berubah (mapping, nama grup) tanpa menyentuh fakta.
3. **Mapping tidak merusak data.** `channel_item_id` (dari Shopee) selalu disimpan apa adanya; `internal_item_id` adalah hasil resolusi yang bisa di-override manual dan di-recompute kapan saja.
4. **Reuse backbone yang ada** (`sku_mappings`, pola `marketplace_order_items`) supaya konsisten dengan mapping order.

---

## 3. Rancangan skema database

### 3.1 Fakta harian per campaign — `marketplace_ad_campaign_dailies` (BARU, inti)

Satu baris = satu campaign, satu tanggal. Ini pengganti "SUM langsung" yang sekarang.

```
id
store_id            FK stores
channel_campaign_id string(80)   index
date                date
ad_type             string(40)   nullable   -- product / shop / dll (dari API)

impressions         bigint  default 0
clicks              bigint  default 0
expense             decimal(15,2) default 0   -- = spend hari itu
broad_order         int     default 0
broad_gmv           decimal(15,2) default 0
direct_order        int     default 0
direct_gmv          decimal(15,2) default 0
cpc                 decimal(10,4) nullable    -- raw dari API (opsional; bisa dihitung ulang)

raw_json            json    nullable
timestamps

UNIQUE (store_id, channel_campaign_id, date)   -- idempoten, aman re-sync
INDEX  (store_id, date)
INDEX  (channel_campaign_id, date)
```

Catatan: KPI turunan (ROAS, CTR, ACOS, CVR) **tidak** disimpan di level harian — dihitung saat agregasi supaya benar (rasio tidak boleh dijumlahkan). Kalau perlu cepat, bisa ditambah generated column / materialized rollup belakangan.

### 3.2 Dimensi campaign — `marketplace_ad_campaigns` (MODIFIKASI)

Tabel tetap dipakai sebagai **master 1 baris per campaign** (bukan per rentang). Ubah artinya dari "agregat rentang" → "identitas + rollup terakhir + mapping".

Kolom **tambahan**:

```
channel_item_id     bigint  nullable index   -- item_id produk Shopee yang diiklankan (product ads)
internal_item_id    FK items nullable index  -- hasil mapping ke item internal
ad_group_id         FK marketplace_ad_groups nullable index  -- grup manual
mapping_status      string(20) default 'unmapped'  -- unmapped / auto / manual
mapping_source      string(20) nullable      -- sku_mappings / order_items / manual
last_synced_range_from  date nullable
last_synced_range_to    date nullable
```

`report_date_from` / `report_date_to` yang lama → dijadikan opsional (rollup snapshot terakhir) atau di-drop; angka riil ada di tabel harian. **UNIQUE lama** `(store_id, channel_campaign_id, report_date_from, report_date_to)` diganti jadi `UNIQUE (store_id, channel_campaign_id)`.

### 3.3 Grup manual — `marketplace_ad_groups` (BARU)

Untuk grouping manual lintas toko/campaign (mis. "Gamis Lebaran 2026").

```
id
name            string(120)
slug            string(120) unique nullable
color           string(20)  nullable   -- untuk UI
notes           text        nullable
created_by      FK users    nullable
timestamps
```

Relasi campaign → grup lewat `marketplace_ad_campaigns.ad_group_id` (1 campaign : 1 grup). Kalau nanti butuh many-to-many (1 campaign masuk beberapa grup), tinggal ganti ke pivot `ad_group_campaign` — tapi untuk kebutuhan sekarang 1:1 cukup dan lebih sederhana.

### 3.4 Override mapping produk iklan → item internal — `marketplace_ad_item_maps` (BARU)

Resolusi default sudah otomatis lewat `channel_item_id` → `sku_mappings` / `marketplace_order_items`. Tabel ini hanya untuk **override manual** kalup otomatis salah/kosong, dan untuk kasus "beberapa judul produk, item internal sama" yang tidak ketangkap `sku_mappings`.

```
id
store_id            FK stores nullable
channel_code        string(20) default 'shopee'
channel_item_id     bigint  nullable    -- kunci utama product ads
channel_campaign_id string(80) nullable -- fallback kalau item_id tak tersedia
internal_item_id    FK items
note                string(255) nullable
created_by          FK users nullable
timestamps

UNIQUE (channel_code, channel_item_id)
UNIQUE (channel_code, channel_campaign_id)
```

**Urutan resolusi `internal_item_id` sebuah campaign:**
1. Override manual di `marketplace_ad_item_maps` (by `channel_item_id`, lalu by `channel_campaign_id`).
2. `sku_mappings` (via SKU/`item_id` channel) → `item_id`.
3. `marketplace_order_items` (`external_item_id` = `channel_item_id`) → `internal_item_id` yang sudah pernah dipetakan di order.
4. Gagal → `mapping_status = 'unmapped'`, tampil di panel "perlu di-mapping".

Karena mapping berbasis **item internal**, dua campaign dengan judul produk berbeda tapi `internal_item_id` sama otomatis tergabung di analisa per item — sesuai kebutuhan "judul beda, item sama".

### 3.5 Saldo & daily toko (jalankan migrasi yang sudah ada)

`marketplace_ads_dailies` (daily level toko) dan `marketplace_ads_balance_logs` (riwayat saldo) migrasinya sudah ada tapi belum di-`migrate`. Cukup dijalankan — berguna untuk KPI saldo & burn rate di halaman.

---

## 4. Relasi (ringkas)

```
stores ─┬─< marketplace_ad_campaigns >─┬─ items (internal_item_id)
        │            │                 └─ marketplace_ad_groups (ad_group_id)
        │            └─< marketplace_ad_campaign_dailies   (fakta harian)
        ├─< marketplace_ads_dailies         (daily toko)
        └─< marketplace_ads_balance_logs    (riwayat saldo)

marketplace_ad_item_maps ─ override manual (channel_item_id/campaign → items)
sku_mappings / marketplace_order_items ─ sumber resolusi otomatis
```

---

## 5. Perubahan sync (`MarketplaceSyncService::syncAdCampaigns`)

1. **Simpan harian, jangan di-SUM.** Loop `metrics_list[]` → upsert per baris ke `marketplace_ad_campaign_dailies` (unique `store_id + campaign_id + date`). Idempoten: re-sync menimpa, tidak dobel.
2. **Ambil `item_id` produk.** Panggil `getCampaignSettingInfo` (`/api/v2/ads/get_product_level_campaign_setting_info`) untuk dapat `item_id` per campaign → simpan ke `channel_item_id`.
3. **Rollup master.** Setelah harian tersimpan, hitung ulang `marketplace_ad_campaigns` (identitas + snapshot terakhir) dan resolve `internal_item_id` (§3.4).
4. **Log saldo.** Simpan `getAdsTotalBalance` ke `marketplace_ads_balance_logs`.
5. **Job artisan terjadwal** (mis. `marketplace:sync-ads`) untuk auto-sync harian semua toko aktif — mendukung "auto-sync".

Endpoint mapping/grouping baru (semua di `routes/web.php`, pola sama dg route ads lain, tanpa CSRF utk PATCH/POST internal):
- `PATCH /ad-campaigns/{campaign}/map-item` — set/override `internal_item_id`.
- `POST /ad-groups` / `PATCH /ad-groups/{group}` / `PATCH /ad-campaigns/{campaign}/group` — CRUD grup + assign.
- `GET /ads-analytics` — diperluas: agregasi by `internal_item_id` atau `ad_group_id`, dari tabel harian.

---

## 6. Update halaman `/marketplace/ads`

Tambahan pada UI yang sudah ada (KPI, tabel campaign sortable, rekomendasi):
- **Toggle "Lihat per":** Campaign · **Per Item Internal** · **Per Grup** — mengubah unit baris tabel (agregasi dari tabel harian).
- **Kolom mapping:** tampilkan item internal hasil map; badge `unmapped` + tombol map inline (search item internal, reuse endpoint `items/search` yang sudah ada).
- **Panel "Perlu di-mapping":** daftar campaign `unmapped` biar cepat dibereskan.
- **Manajemen grup:** buat grup, assign campaign (multi-select), warna/label.
- **Tren harian:** karena data harian tersimpan, tambah sparkline/line chart spend vs GMV per campaign/item/grup dan pembanding periode.

---

## 7. Rencana eksekusi (bertahap, aman untuk ERP)

**Fase 1 — Fondasi data (tanpa ubah UI):**
1. Migrasi: buat `marketplace_ad_campaign_dailies`, `marketplace_ad_groups`, `marketplace_ad_item_maps`; alter `marketplace_ad_campaigns` (kolom mapping + grup, ganti unique); jalankan migrasi `marketplace_ads_dailies` & `marketplace_ads_balance_logs` yang tertunda.
2. Model + relasi (Eloquent) untuk tabel baru.
3. Refactor `syncAdCampaigns` → tulis harian + item_id + rollup + log saldo. Uji di 1 toko dulu.

**Fase 2 — Mapping & grouping:**
4. Service resolusi mapping (§3.4) + command backfill untuk data existing.
5. Endpoint map-item, CRUD grup, assign grup.

**Fase 3 — UI:**
6. Update `ads.blade.php`: toggle per Campaign/Item/Grup, kolom + panel mapping, manajemen grup, tren harian.

**Fase 4 — Otomasi & verifikasi:**
7. Scheduled job auto-sync harian.
8. Verifikasi: bandingkan total spend/GMV hasil agregasi harian vs angka di dashboard Shopee Ads (rekonsiliasi), unit test resolusi mapping, cek idempotensi re-sync.

---

## 8. Keputusan (SUDAH DIKONFIRMASI)

1. **Retensi data harian:** ✅ simpan penuh selamanya.
2. **Grup:** 1 campaign : 1 grup (default; mudah diubah ke many-to-many via pivot nanti).
3. **Break-even ACOS per item:** ✅ diaktifkan. **Catatan penting:** tabel `items` **tidak** menyimpan harga jual — hanya `hpp` / `base_unit_cost`. Jadi BE ACOS **diturunkan dari data**: harga jual rata-rata teramati = `gmv / unit_terjual`, lalu `BE ACOS = (harga - hpp) / harga`. Bisa selalu di-override manual lewat kolom `break_even_acos`. (Lihat `MarketplaceAdCampaign::effectiveBreakEvenAcos()`.)
4. **`report_date_from/to` lama:** ✅ di-drop. Diganti `last_synced_range_from/to` sebagai jejak sync terakhir; angka riil ada di tabel harian.

---

## 9. Status implementasi

**Fase 1 & 2 — SUDAH DIBUAT (backend), tinggal jalankan migrasi:**

Migrasi baru:
- `..._100001_create_marketplace_ad_campaign_dailies_table.php` — fakta harian per campaign.
- `..._100002_create_marketplace_ad_groups_table.php` — grup manual.
- `..._100003_create_marketplace_ad_item_maps_table.php` — override mapping.
- `..._100004_enrich_marketplace_ad_campaigns_for_analysis.php` — kolom mapping/grup, drop kolom rentang lama.
- (migrasi lama `marketplace_ads_dailies` & `marketplace_ads_balance_logs` ikut jalan.)

Model: `MarketplaceAdCampaignDaily`, `MarketplaceAdGroup`, `MarketplaceAdItemMap` (baru); `MarketplaceAdCampaign` (diperbarui + relasi + `effectiveBreakEvenAcos()`).

Service: `App\Services\AdItemMapper` — resolusi produk iklan → item internal (manual → order_items → unmapped) + `backfill()`.

Sync: `MarketplaceSyncService::syncAdCampaigns` di-refactor → simpan harian (idempoten), ambil `item_id` per campaign (`getCampaignSettingInfo`), rollup master + resolve mapping, log saldo.

Endpoint + route baru (semua `withoutMiddleware` CSRF, pola sama route ads lain):
- `PATCH /marketplace/ad-campaigns/{campaign}/map-item`
- `PATCH /marketplace/ad-campaigns/{campaign}/group`
- `POST  /marketplace/ad-groups`  ·  `PATCH /marketplace/ad-groups/{group}`

Command: `php artisan marketplace:backfill-ad-mapping [--store=ID]` — recompute mapping data existing.

**Fase 3 — SUDAH DIBUAT (UI halaman `/marketplace/ads`):**
- `adsAnalytics` di-refactor total → agregasi dari `marketplace_ad_campaign_dailies` (rentang tanggal apapun akurat, tidak lagi bergantung `report_date_from/to` yang sudah di-drop), plus field mapping + parameter `group_by=campaign|item|group`.
- Toggle **Lihat per: Campaign / Item Internal / Grup** di panel performa.
- Kolom **Item / Grup** (view campaign): badge mapping + tombol map (modal cari item internal), chip grup + assign.
- Badge **"⚠️ N belum di-mapping"** + filter "hanya belum di-mapping".
- **Kelola Grup** (buat grup + warna), assign campaign ke grup.
- Break-even ACOS otomatis dari HPP item (atau override manual); di view item/grup jadi rata-rata tertimbang.

**Fase 4 — BELUM:** scheduled job auto-sync harian + verifikasi rekonsiliasi. (Catatan: sparkline tren harian per campaign/item belum ditambahkan — data harian sudah tersimpan, tinggal endpoint kecil + chart bila diperlukan.)

---

## 10. Cara menjalankan (di Herd / terminal)

```bash
php artisan migrate                 # buat tabel-tabel baru
php artisan marketplace:backfill-ad-mapping   # (opsional) map ulang data existing

# setelah sync campaign berikutnya, data harian & mapping terisi otomatis.
```

Semua tabel iklan saat ini masih kosong, jadi migrasi aman (tidak ada data yang hilang). Refactor sync bersifat idempoten — aman dijalankan berulang.
```
