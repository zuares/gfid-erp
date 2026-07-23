# Fase 1 — Validasi Read-Only: Apakah Campaign GMV Max Terbaca via Ads API Project

**Sifat:** read-only. Tidak ada kode/migration yang diubah, tidak ada endpoint tulis (create/edit/pause/activate/delete) dipanggil, tidak ada campaign/ROAS/budget/status yang disentuh. Credential tidak ditampilkan.
**Tanggal:** 23 Juli 2026.

---

# 00. HASIL LENGKAP UJI SETTING + PERFORMANCE (campaign asli 477707399) — TERBUKTI

## Executive result
Endpoint yang **sudah ada di project SUDAH CUKUP** untuk membaca setting **dan** performa GMV Max — **asal parameter diperbaiki**. Terbukti dari response nyata: format `info_type_list` yang benar = **comma-string**, dan `get_product_level_campaign_setting_info` mengembalikan `bidding_method`, `roas_target`, `campaign_budget`, `campaign_status`, `item_id_list`, `start/end time`. Campaign 477707399 = **LIKELY GMV MAX** (auto bidding). **Tidak perlu endpoint GMS khusus, tidak perlu export manual.**

## 2. Format `info_type_list` (Tahap 3)
| Percobaan | Nilai request | HTTP | Error | Berhasil |
|---|---|---|---|---|
| array-single | `[1]` → `info_type_list[0]=1` | 200 | `error_param: InfoTypeList is required` | ❌ |
| **string-single** | `'1'` | 200 | — | ✅ |
| json-single | (tidak diuji, berhenti di sukses pertama) | — | — | — |

**Kesimpulan:** format = **comma-separated string** (mis. `'1,2,3,4'`). PHP array **ditolak** (Guzzle mengubahnya jadi `info_type_list[0]=…` yang tidak diparse Shopee). Ini **konsisten dengan konvensi project** (`campaign_id_list` juga comma-string via `implode`).

## 3. Info type valid & field yang dikembalikan (Tahap 4) — CONFIRMED FROM RESPONSE
| info_type | Node response | Field | Status |
|---|---|---|---|
| 1 | `common_info` | `ad_type, ad_name, campaign_status, bidding_method, campaign_placement, campaign_budget, campaign_duration{start_time,end_time}, item_id_list[]` | CONFIRMED |
| 2 | `manual_bidding_info` | `null` untuk campaign ini (berisi data bila bidding manual/keyword) | CONFIRMED (node), INFERRED (isi) |
| 3 | `auto_bidding_info` | `roas_target` | CONFIRMED |
| 4 | `auto_product_ads_info` | `null` untuk campaign ini | CONFIRMED (node) |

Semua info_type (1–4) valid (http 200, error kosong). Path lengkap contoh: `response.campaign_list.0.common_info.bidding_method`, `response.campaign_list.0.auto_bidding_info.roas_target`.

## 4. Setting campaign 477707399 (Tahap 5)
```
campaign_id:      477707399
item_id:          28944692968        (path: common_info.item_id_list.0)
ad_type:          manual             (pemilihan produk manual = 1 produk)
ad_name:          "GOODFIT | Cargo Pants Wanita Panjang Training Trackpants Loose Fit"
bidding_method:   auto               ← SINYAL KUNCI
auto_bidding_info.roas_target: 0     (target ROAS custom tidak diset / mode maksimalkan GMV)
campaign_budget:  25000              (Rp 25.000)
campaign_status:  closed             (campaign sudah berakhir)
campaign_duration.start_time: 1780938000  (≈ 9 Juni 2026 00:00 WIB)
campaign_duration.end_time:   0      (tak berakhir eksplisit; status = closed)
campaign_placement: all
manual_bidding_info / auto_product_ads_info: null
```

**Identifikasi:** **LIKELY GMV MAX**
- Tingkat keyakinan: **Sedang–tinggi.**
- Bukti field (CONFIRMED FROM RESPONSE): `bidding_method = "auto"` + node `auto_bidding_info.roas_target` terisi (bukan `manual_bidding_info`). Produk tunggal (`item_id_list` 1 item), konsisten dengan GMV Max (1 campaign = 1 produk).
- Alasan (INFERRED): dokumentasi Seller Centre resmi menyatakan **"GMV Max adalah versi upgrade dari Auto Bidding"** → `bidding_method: "auto"` = keluarga Auto Bidding / GMV Max. API **tidak** memberi label literal "gmv_max", jadi status **LIKELY**, bukan CONFIRMED.
- `roas_target = 0`: **INFERRED** = tidak ada target ROAS custom (mode maksimalkan GMV) atau belum diset saat campaign ini ditutup. Field-nya **ada & terbaca**; untuk melihat nilai non-nol, uji campaign GMV Max **aktif** yang memakai Custom ROAS.

> **PENTING (aturan 15):** identifikasi TIDAK didasarkan pada `ad_type = manual`. Sinyal GMV Max = `bidding_method: "auto"` + `auto_bidding_info`.

## 5. Daily performance 477707399 (Tahap 6)
HTTP 200, berhasil. 7 hari (17–23 Juli 2026) — **semua metrik = 0** karena `campaign_status = closed` (tidak ada aktivitas di jendela ini). **Struktur endpoint terbukti bekerja.**

| Tanggal | Spend | Impr | Click | Broad Ord | Direct Ord | Broad GMV | Direct GMV | Broad ROAS | Direct ROAS |
|---|--:|--:|--:|--:|--:|--:|--:|--:|--:|
| 17–23 Jul 2026 | 0 | 0 | 0 | 0 | 0 | 0 | 0 | — | — |

Metrik turunan **tidak terdefinisi** (pembagian nol) karena spend/click/impr = 0 → tampil `—`. **Untuk angka nyata, ulangi dengan campaign GMV Max yang AKTIF & ber-spend.**

**Field metrik yang Shopee sediakan (per hari)** — lebih kaya dari yang project simpan:
`date, impression, clicks, ctr, expense, broad_gmv, broad_order, broad_order_amount, broad_roi, broad_cir, cr, cpc, direct_order, direct_order_amount, direct_gmv, direct_roi, direct_cir, direct_cr, cpdc`.
Catatan: **ROAS (`broad_roi`/`direct_roi`), CTR (`ctr`), CVR (`cr`/`direct_cr`), CPC (`cpc`) disediakan LANGSUNG oleh API** — project saat ini menghitung ulang beberapa di antaranya. `broad_cir`/`direct_cir` = cost-income-ratio (mirip ACOS); `cpdc` = cost per direct conversion.

## 6. Mapping ke database (Tahap 7)
| Field API | Path response | Tabel tujuan | Kolom tersedia? | Gap |
|---|---|---|---|---|
| campaign_id | `campaign_list.0.campaign_id` | marketplace_ad_campaigns | ✅ `channel_campaign_id` | — |
| item_id | `common_info.item_id_list.0` | marketplace_ad_campaigns | ✅ `channel_item_id` (kolom ada) | **belum terisi** (parse path salah, lihat Tahap 8) |
| ad_type | `common_info.ad_type` | marketplace_ad_campaigns | ✅ `campaign_type` (dipakai lain) | perlu kolom khusus |
| bidding_method | `common_info.bidding_method` | — | ❌ | **TAMBAH** |
| roas_target | `auto_bidding_info.roas_target` | — | ❌ | **TAMBAH** `target_roas` |
| recommended_roas | (tidak ada di setting_info) | — | ❌ | butuh endpoint rekomendasi terpisah |
| campaign_budget | `common_info.campaign_budget` | — | ❌ | **TAMBAH** `daily_budget`/`budget` |
| campaign_status | `common_info.campaign_status` | marketplace_ad_campaigns | ⚠️ `status` (generik "ongoing") | selaraskan nilai (closed/ongoing/…) |
| start_time/end_time | `common_info.campaign_duration.*` | — | ❌ | **TAMBAH** `started_at`/`ended_at` |
| raw setting | seluruh `campaign_list.0` | — | ❌ | **TAMBAH** `raw_setting_payload` |
| — | — | — | — | **TAMBAH** `setting_synced_at` |
| performa harian | `metrics_list[]` | marketplace_ad_campaign_dailies | ✅ (spend/gmv/order/impr/click) | opsional simpan `broad_roi/direct_roi/cir/cpdc` dari API |

## 7. Bug project terkonfirmasi (Tahap 8)
```
Judul:      getCampaignSettingInfo tidak mengirim info_type_list (WAJIB)
Severity:   HIGH
File:       app/Services/Channels/Shopee/ShopeeChannel.php
Method:     getCampaignSettingInfo()
Baris:      286–291
Request aktual:  GET /api/v2/ads/get_product_level_campaign_setting_info?...&campaign_id_list=<ids>
Parameter hilang: info_type_list (format comma-string, mis. "1,2,3,4")
Error nyata:      error_param — "info_type_list: InfoTypeList is required"
Dampak langsung:  setting campaign SELALU gagal → bidding_method / roas_target / budget / status / durasi tak terbaca.
Dampak lanjutan:
  - target ROAS tidak tersedia → dashboard tak bisa bandingkan actual vs target ROAS;
  - budget & status tidak tersedia;
  - item_id tidak terbaca → channel_item_id kosong → mapping ke item internal lemah;
  - (BUG KEDUA terungkap) parse item_id di MarketplaceSyncService::syncAdCampaigns memakai path
    'item_id' / 'item_id_list.0' / 'manual_product_ads.0.item_id' / dsb — sedangkan path NYATA =
    'common_info.item_id_list.0'. Jadi meski info_type_list ditambah, item_id tetap tak terpetakan
    sampai path parse diperbaiki.
Perbaikan minimal (nanti):
  1. tambah parameter $infoTypeList ke getCampaignSettingInfo (default aman "1,3" atau "1,2,3,4");
  2. kirim sebagai comma-string (BUKAN array);
  3. perbaiki path parse item_id → common_info.item_id_list.0;
  4. simpan raw_setting_payload + setting_synced_at;
  5. degrade aman bila node bidding null.
Risiko backward compatibility: RENDAH (parameter opsional berdefault; pemanggil lama tetap jalan).
Test: unit test parse path + integration read-only ke 1 campaign aktif.
```

## 8. Keputusan akhir Fase 1 (Tahap 9)
**PILIHAN 3 — "Endpoint existing membutuhkan perbaikan parameter, lalu cukup."**
Bukti: `get_product_level_campaign_setting_info` (dengan `info_type_list` comma-string) mengembalikan seluruh setting GMV Max (bidding_method, roas_target, budget, status, item_id, durasi), dan `get_product_campaign_daily_performance` mengembalikan performa lengkap. **Tidak perlu GMS API, tidak perlu export manual.** Satu-satunya yang belum terverifikasi: nilai `roas_target` **non-nol** pada campaign GMV Max **aktif ber-Custom-ROAS** (campaign uji ini `closed` dengan roas_target 0).

## 9. Rekomendasi implementasi Fase 2 (tanpa ubah kode sekarang)
1. Ubah `getCampaignSettingInfo($store, $ids, $infoTypeList = '1,2,3,4')` → kirim comma-string.
2. Di `syncAdCampaigns`: baca `common_info.*` (item_id_list.0, bidding_method, campaign_budget, campaign_status, campaign_duration) + `auto_bidding_info.roas_target`; simpan.
3. Tambah kolom: `bidding_method, target_roas, daily_budget, campaign_status, started_at, ended_at, raw_setting_payload, setting_synced_at` (+ pertimbangkan `broad_roi/direct_roi/cir` dari performa).
4. Reflection **hanya** untuk uji ini — bukan desain final; perbaikan resmi = ubah signature method.
5. Uji ulang pada **campaign GMV Max aktif ber-Custom ROAS** untuk konfirmasi `roas_target` non-nol + angka performa nyata.

---

# 0. HASIL UJI API NYATA (update 23 Juli 2026, via ngrok + browser login)

Response nyata berhasil diperoleh (toko `shop_id 1076816997`, region ID) lewat route read-only `debugAdApi`. Ringkas 4 panggilan:

| Endpoint | Status | Hasil |
|---|---|---|
| `get_shop_toggle_info` | **200 OK** | `auto_top_up: true`, `campaign_surge: false` → iklan aktif |
| `get_product_level_campaign_id_list` | **200 OK** | **16 campaign, SEMUA `ad_type: "manual"`**, `has_next_page: false` |
| `get_product_level_campaign_setting_info` | **error_param** | **`"info_type_list: InfoTypeList is required"`** ← bug parameter terkonfirmasi |
| `get_product_campaign_daily_performance` | **error_param** | `"invalid product ads campaign ids 34741562,..."` → hanya karena ID contoh hardcoded; endpoint sehat |

**Tiga kesimpulan terkonfirmasi (naik dari UNVERIFIED → CONFIRMED):**

1. **Bug `get_product_level_campaign_setting_info` NYATA.** Shopee **mewajibkan `info_type_list`**; kode project (`ShopeeChannel::getCampaignSettingInfo`, `:286`) tidak mengirimnya → **saat ini target ROAS / budget / bidding_method TIDAK BISA DIBACA sama sekali** (selalu error). Ini penyebab utama data setting GMV Max kosong.
2. **`ad_type` semua "manual" — belum cukup untuk memvonis GMV Max.** Di Shopee, **GMV Max adalah *bidding strategy*, bukan `ad_type`**. Field pembeda (`bidding_method`, `roas_target`) ada di **setting_info** — yang justru error. Jadi **belum bisa dipastikan** apakah 16 campaign ini GMV Max atau Manual Product Ads biasa **sampai setting_info diperbaiki** (kirim `info_type_list`). Yang pasti: **tidak ada `ad_type` "auto"/"gms"/"gmv_max"** di output id_list toko ini.
3. **Endpoint performa & id_list & toggle SEHAT** (200). Performa tinggal dipanggil dengan `campaign_id` asli (mis. `477707399`), bukan ID contoh.

**Langkah lanjut wajib (read-only) ada di §8-B** — panggil setting_info **dengan** `info_type_list` untuk ID asli agar `bidding_method`/`roas_target`/`budget` terlihat. Itu yang akan **memastikan** apakah campaign = GMV Max dan sekaligus membuka target ROAS/budget.

---

# 1. Hasil Validasi (versi awal — sebelum ngrok; konteks)

> **Status awal: belum dapat diuji dari environment audit; kini SUDAH diuji lewat ngrok (lihat §0).**
> Ini bukan kegagalan permission Shopee, melainkan keterbatasan environment audit. Alasan berbasis bukti (bukan asumsi):
>
> 1. **Credential terenkripsi.** `app/Models/Store.php:28` → `'credentials' => 'encrypted:array'`, dan `protected $hidden = ['credentials']`. Token hanya bisa didekripsi oleh aplikasi Laravel dengan `APP_KEY`. Membaca file `database_dev.sqlite` langsung **tidak** memberi token.
> 2. **Tidak ada PHP runtime di environment audit.** Signing request Shopee (`ShopeeChannel::sign()` HMAC dengan `partner_key`) + dekripsi credential mengharuskan menjalankan aplikasi Laravel. Environment ini tidak punya PHP.
> 3. **Access token sudah kadaluarsa.** `stores.token_expires_at` untuk kedua toko = **2026-07-12** (11 hari lalu). Access token Shopee berumur ~4 jam. Untuk memanggil API, aplikasi harus me-refresh token — itu **operasi tulis OAuth** (refresh_token Shopee sekali-pakai & berotasi, lihat `ShopeeChannel.php:353–383`). Sesuai aturan (jangan mengubah OAuth), **saya tidak menjalankannya**.
> 4. **Egress jaringan.** Base URL produksi `partner.shopeemobile.com` (`config/shopee.php:9`) kemungkinan tidak diizinkan dari sandbox audit.
>
> **Yang BISA dipastikan dari kode (tanpa API):** endpoint pembacaan campaign **sudah terpasang** dan **secara desain mampu** menarik campaign product-ads + performa harian. **Namun** ada **dua keterbatasan parameter** di kode yang berpotensi membuat data setting (target ROAS/budget) tidak terbaca (lihat §4 & §6). Karena itu status akhir tetap: **perlu bukti response nyata** untuk memastikan campaign GMV Max ikut muncul.

Pemetaan ke pilihan yang diminta:
- ❌ "GMV Max terdeteksi melalui endpoint yang sudah dipakai" — belum terbukti (butuh response nyata).
- ❌ "GMV Max tidak muncul" — belum terbukti negatif juga.
- ✅ **"Belum dapat diuji karena credential/permission/runtime"** — **ini status yang berlaku** (spesifiknya: runtime + token kadaluarsa, bukan permission).
- ⚠️ "Response tersedia tetapi tipe campaign belum dapat dipastikan" — berlaku setelah Anda menjalankan snippet §8, kecuali field pembeda ditemukan.

---

# 2. Bukti API Nyata

**Tidak ada response API nyata yang dapat saya lampirkan** karena alasan §1 (jujur, sesuai aturan "jangan mengarang hasil"). Bukti yang saya punya adalah **bukti kode** (bahwa request-nya benar terpasang) + **status toko** (read-only, tanpa secret):

**Toko Shopee di DB (metadata saja, credential di-mask):**

| store_id | nama | status | shop_id | credential | token_expires_at | channel |
|---|---|---|---|---|---|---|
| 4 | Insight Corps | active | ada | present (encrypted) | 2026-07-12 05:00 (kadaluarsa) | shopee |
| 5 | Greatfit.id | active | ada | present (encrypted) | 2026-07-12 02:38 (kadaluarsa) | shopee |

*(Query read-only `mode=ro`; tidak menampilkan isi `credentials`.)*

**Endpoint pembacaan yang siap dipanggil (bukti kode `ShopeeChannel.php`):**

| Method (project) | Endpoint | Baris | Parameter dikirim |
|---|---|---|---|
| `getShopToggleInfo` | `/api/v2/ads/get_shop_toggle_info` | `:265` | — |
| `getCampaignIdList` | `/api/v2/ads/get_product_level_campaign_id_list` | `:274` | `page_no`, `page_size` |
| `getCampaignSettingInfo` | `/api/v2/ads/get_product_level_campaign_setting_info` | `:286` | `campaign_id_list` **saja** |
| `getCampaignDailyPerformance` | `/api/v2/ads/get_product_campaign_daily_performance` | `:298` | `campaign_id_list`, `start_date`, `end_date` (DD-MM-YYYY) |
| `getAdsShopDailyPerformance` | `/api/v2/ads/get_all_cpc_ads_daily_performance` | (ada) | `start_date`, `end_date` |
| `getAdsTotalBalance` | `/api/v2/ads/get_total_balance` | (ada) | — |

Request builder: `ShopeeChannel::doGet()` menambahkan `partner_id, timestamp, access_token, shop_id, sign` lalu `Http::get(base_url . path)` — **produksi** (`partner.shopeemobile.com`).

---

# 3. Identitas Campaign GMV Max (dari kode + riset)

Field pembeda yang **harus dicek** pada response nyata (belum bisa saya buktikan, tandai UNVERIFIED):

- `ad_type` pada `get_product_level_campaign_id_list` → response berisi `campaign_list[].{campaign_id, ad_type}` (dikonfirmasi dipakai di `MarketplaceSyncService`: `array_column($list,'campaign_id')`, `has_next_page`). Nilai `ad_type` yang membedakan GMV Max **UNVERIFIED**.
- `campaign_type` / `bidding_method` pada `get_product_level_campaign_setting_info` → kandidat pembeda utama, **tapi lihat §4**: request project **tidak** mengirim selektor tipe info, sehingga field bidding/ROAS **mungkin tidak dikembalikan**.
- Indikasi dari riset (mirror SDK, UNVERIFIED di portal resmi): GMV Max di Open Platform kemungkinan berupa **"GMS campaign"** dengan endpoint terpisah (`getGmsCampaignPerformance`, dll). Jika benar, GMV Max **mungkin tidak muncul** di `get_product_level_campaign_id_list` sama sekali → **ini justru inti yang harus dibuktikan** oleh snippet §8.

**Cara memutuskan dari response nyata:** kumpulkan semua nilai unik `ad_type` di `campaign_list`, dan semua key di `setting_info`. Jika ada campaign yang di Seller Centre memakai GMV Max tapi **tidak** ada di list → berarti butuh endpoint GMS.

---

# 4. Target ROAS dan Budget

**Jawaban tegas: BELUM DAPAT DIBACA oleh implementasi saat ini — dua sebab.**

1. **Sebab data belum ada:** semua tabel iklan 0 baris; belum ada sync campaign.
2. **Sebab kode (lebih penting):** `getCampaignSettingInfo()` (`ShopeeChannel.php:286`) hanya mengirim `campaign_id_list`. Mirror SDK menyebut `getProductLevelCampaignSettingInfo` mengembalikan **"4 info types"** (common / manual bidding / auto bidding / auto product ads) — yang **memuat** bidding_method, ROAS target, dan budget. Umumnya endpoint ini butuh **selektor tipe info** (mis. `info_type_list`) agar node bidding/ROAS/budget ikut dikembalikan.
   - **Status:** UNVERIFIED terhadap portal resmi (`open.shopee.com` ter-block untuk alat saya). Namun **code-evident** bahwa **tidak ada** selektor tipe info yang dikirim → besar kemungkinan response saat ini hanya common info (nama/status), **tanpa** target ROAS/budget.

| Kebutuhan | Endpoint sumber (kandidat) | Nama field (kandidat) | Granularitas | Status |
|---|---|---|---|---|
| Target ROAS | `get_product_level_campaign_setting_info` | `roas_target` / node bidding | per campaign | UNVERIFIED + kode belum minta node-nya |
| Recommended ROAS | `getProductRecommendedRoiTarget` (belum dipakai) | — | per item | UNVERIFIED |
| Daily budget | `get_product_level_campaign_setting_info` | `daily_budget` / node bidding | per campaign | UNVERIFIED + kode belum minta node-nya |
| Total budget | idem | `total_budget` | per campaign | UNVERIFIED |
| Bidding method | idem | `bidding_method` | per campaign | UNVERIFIED |

**Catatan discrepancy parameter (perlu verifikasi portal resmi):** riset SDK menunjukkan `get_product_level_campaign_id_list` memakai `ad_type`, `offset`, `limit`; project memakai `page_no`, `page_size`. Bisa jadi beda versi/region. Perlu dicek saat menjalankan snippet (apakah `has_next_page`/`campaign_list` tetap terisi normal).

---

# 5. Performa Harian

**Untuk campaign product-ads (dan GMV Max BILA ia muncul di id_list): tersedia** — bukti kode di `MarketplaceSyncService::syncAdCampaigns()` yang membaca `response.campaign_list[].metrics_list[]` dengan field:

| Field API (dibaca project) | Disimpan ke |
|---|---|
| `expense` | `marketplace_ad_campaign_dailies.expense` |
| `impression` | `.impressions` |
| `clicks` | `.clicks` |
| `broad_order` | `.broad_order` |
| `direct_order` | `.direct_order` |
| `broad_gmv` | `.broad_gmv` |
| `direct_gmv` | `.direct_gmv` |
| `cpc` | `.cpc` |
| `date` | `.date` (dikonversi via `parseAdMetricDate`) |
| Actual ROAS | **dihitung** = `broad_gmv / expense` (tidak dari API) |

**Syarat mutlak:** performa ini hanya keluar untuk `campaign_id` yang didapat dari `get_product_level_campaign_id_list`. **Jika GMV Max tidak terdaftar di sana, performa GMV Max tidak akan tertarik** meski endpoint performanya bekerja. Inilah yang wajib dibuktikan (§8).

Untuk level toko, `get_all_cpc_ads_daily_performance` (dipakai `SyncAdsDailyCommand`) memberi agregat harian toko — **apakah termasuk spend GMV Max juga UNVERIFIED** (agregat, tanpa breakdown campaign).

---

# 6. Gap Project

| Gap | Dampak | Prioritas | Rekomendasi |
|---|---|---|---|
| `getCampaignSettingInfo` tak kirim selektor tipe info | Target ROAS/budget/bidding_method kemungkinan tak terbaca | **Critical** | Verifikasi param resmi; tambah `info_type_list` (atau setara) saat implementasi — bukan sekarang |
| Belum terbukti GMV Max muncul di `get_product_level_campaign_id_list` | Bila tidak, seluruh data GMV Max tak tertarik | **Critical** | Jalankan snippet §8 di toko ber-GMV Max; jika kosong → siapkan endpoint GMS |
| Param id_list `page_no/page_size` vs indikasi resmi `ad_type/offset/limit` | Pagination/filter bisa tak sesuai versi API | High | Verifikasi saat uji; sesuaikan bila perlu |
| `raw_setting_payload` tidak disimpan | Tak bisa audit field baru tanpa re-call | High | Simpan raw setting saat sync (rekomendasi model) |
| Tabel iklan 0 baris | Belum bisa validasi end-to-end | High | Sync 1 toko setelah uji akses |
| Actual ROAS dihitung dari broad_gmv | Bisa overstate vs Seller Centre | Medium | Sediakan varian direct + rekonsiliasi |
| `token_expires_at` lampau | Perlu refresh (tulis) sebelum call | Medium | Dilakukan otomatis oleh app saat Anda run (normal) |

---

# 7. Keputusan Arsitektur

Berdasarkan bukti kode + status (API nyata belum diuji), keputusan **bertingkat dan bersyarat**:

- **Langkah wajib sebelum memutuskan:** jalankan uji read-only §8 di toko yang **sedang** memakai GMV Max.
- **Jika GMV Max MUNCUL** di `get_product_level_campaign_id_list` dan `setting_info` (dengan selektor tipe info yang benar) mengembalikan ROAS/budget → **lanjut memakai endpoint yang sudah ada** + tambah `info_type_list` + kolom penyimpanan (§ berikut). **Belum perlu GMS API.**
- **Jika GMV Max TIDAK muncul** di id_list → **perlu endpoint GMS khusus** (`getGmsCampaignPerformance` / setting GMS), yang statusnya **UNVERIFIED + kemungkinan butuh permission Ads tambahan/approval** → verifikasi ke portal resmi/partner support dulu.
- **Jika setting ROAS/budget tetap tak tersedia via API mana pun** → **fallback export manual** laporan GMV Max (Seller Centre).
- **Status keamanan lanjut implementasi:** **belum aman menulis** apa pun ke GMV Max; untuk **membaca**, aman lanjut **setelah** uji §8 mengonfirmasi campaign muncul.

**Kesiapan penyimpanan (rekomendasi, TANPA migration):**

| Struktur | Cukup untuk performa? | Kurang untuk GMV Max |
|---|---|---|
| `marketplace_ad_campaign_dailies` | Ya (spend/gmv/impr/klik/order harian) | — |
| `marketplace_ads_dailies` | Ya (agregat toko) | breakdown campaign |
| `marketplace_ad_campaigns` | Sebagian | `campaign_type`, `bidding_method`, `is_gmv_max`, `target_roas`, `recommended_roas`, `daily_budget`, `total_budget`, `campaign_status`, `raw_setting_payload`, `setting_synced_at` |

---

# 8. Langkah Implementasi Berikutnya (urutan; TANPA kode baru sekarang)

1. **Uji akses read-only (Anda jalankan di Herd).** Gunakan snippet di bawah pada toko ber-GMV Max. Tempelkan output (sudah aman, tanpa credential) agar bisa dianalisis lanjut (Tahap 4/5).
2. **Analisis response** (Tahap 4): kumpulkan nilai unik `ad_type`, seluruh key `setting_info`, cari `roas`/`budget`/`gms`/`gmv`. Isi tabel field.
3. **Rekonsiliasi** dengan Seller Centre (Tahap 5): bandingkan spend/GMV/ROAS 1 campaign yang sama.
4. **Bila muncul:** rencanakan tambah `info_type_list` + kolom setting + `raw_setting_payload`.
5. **Bila tidak muncul:** verifikasi endpoint GMS di portal resmi/partner support + cek permission Ads.
6. Baru kemudian: mesin profit (satukan ad spend), dashboard, rule engine (Fase berikutnya audit sebelumnya).

## Snippet uji read-only (jalankan di Herd, aman, tidak mengubah campaign)

> Tempelkan ke `php artisan tinker` **di folder project di mesin Anda** (bukan di sini). Snippet ini hanya memanggil endpoint **baca**, memakai driver resmi project (token di-refresh otomatis oleh app sebagaimana operasi baca normal), dan **tidak mencetak credential**. Ganti `$storeId` bila perlu (4 = Insight Corps, 5 = Greatfit.id).

```php
$storeId = 5; // toko yang sedang pakai GMV Max
$store = \App\Models\Store::findOrFail($storeId);
$drv = app(\App\Services\Channels\ChannelManager::class)->driver($store);
$from = now()->subDays(6)->format('d-m-Y');
$to   = now()->format('d-m-Y');

$mask = function ($data) {
    // buang key sensitif kalau (jarang) muncul di payload
    $json = json_encode($data);
    foreach (['access_token','refresh_token','partner_key','sign'] as $k) {
        $json = preg_replace('/"'.$k.'":"[^"]*"/', '"'.$k.'":"[MASKED]"', $json);
    }
    return json_decode($json, true);
};

// 1) Toggle & saldo
dump('toggle', $mask($drv->getShopToggleInfo($store)));
dump('balance', $mask($drv->getAdsTotalBalance($store)));

// 2) Daftar campaign
$idList = $drv->getCampaignIdList($store, 1, 100);
dump('id_list_keys', array_keys($idList['response'] ?? []));
$campaigns = data_get($idList, 'response.campaign_list', []);
dump('campaign_count', count($campaigns));
dump('ad_types_unik', array_values(array_unique(array_map(fn($c)=>$c['ad_type'] ?? null, $campaigns))));
$ids = array_slice(array_map(fn($c)=>$c['campaign_id'], $campaigns), 0, 10);
dump('sample_campaign_ids', $ids);

// 3) Setting info (perhatikan: cek apakah ROAS/budget ADA di response)
if ($ids) {
    $setting = $drv->getCampaignSettingInfo($store, $ids);
    dump('setting_keys', array_keys(data_get($setting,'response.campaign_list.0', []) ?? []));
    dump('setting_sample', $mask(data_get($setting,'response.campaign_list.0')));
}

// 4) Performa harian (7 hari)
if ($ids) {
    $perf = $drv->getCampaignDailyPerformance($store, $ids, $from, $to);
    dump('perf_campaign_count', count(data_get($perf,'response.campaign_list', [])));
    dump('perf_metric_sample', $mask(data_get($perf,'response.campaign_list.0.metrics_list.0')));
}

// 5) Performa harian level toko
dump('shop_daily', $mask($drv->getAdsShopDailyPerformance($store, $from, $to)));
```

**Yang harus Anda catat dari output** (untuk isi tabel Tahap 4):
- `ad_types_unik` — apakah ada nilai selain product ads biasa (indikasi GMV Max / GMS)?
- `setting_keys` — apakah memuat `roas`/`budget`/`bidding`? Jika tidak → konfirmasi butuh `info_type_list`.
- `campaign_count` vs jumlah campaign GMV Max di Seller Centre — cocok atau kurang?
- Error code (mis. `error_permission_denied`, `error_auth`) bila muncul → catat apa adanya, jangan ubah permission/OAuth.

---

## 8-B. Snippet lanjutan (WAJIB) — baca setting dengan `info_type_list` untuk campaign ASLI

> Tujuannya: memaksa `get_product_level_campaign_setting_info` mengembalikan `bidding_method`, `roas_target`, `budget` untuk campaign nyata → memastikan apakah 16 campaign itu GMV Max, sekaligus membuka target ROAS/budget. **Read-only, tanpa mengubah kode** (memakai `ReflectionMethod` untuk memanggil method signing `get` yang ada + menambah parameter `info_type_list`). Jalankan di `php artisan tinker`:

```php
$store = \App\Models\Store::find(5); // toko GMV Max
$drv = app(\App\Services\Channels\ChannelManager::class)->driver($store);

// Method signing low-level 'get' (protected) dipakai apa adanya (reflection),
// HANYA untuk uji ini — bukan desain final.
$get = new \ReflectionMethod($drv, 'get'); $get->setAccessible(true);
$path = '/api/v2/ads/get_product_level_campaign_setting_info';
$cid  = '477707399'; // campaign ID asli

$mask = function ($d) {
    $j = json_encode($d);
    foreach (['access_token','refresh_token','partner_key','sign'] as $k)
        $j = preg_replace('/"'.$k.'":"[^"]*"/', '"'.$k.'":"[MASKED]"', $j);
    return json_decode($j, true);
};

// Uji format info_type_list BERTAHAP, berhenti di sukses pertama.
$formats = [
    'A array[1]'        => [1],
    'B array[1,2,3,4]'  => [1,2,3,4],
    'C string 1,2,3,4'  => '1,2,3,4',
    'D json[1,2,3,4]'   => json_encode([1,2,3,4]),
];
$ok = null;
foreach ($formats as $label => $val) {
    $r   = $get->invoke($drv, $store, $path, ['campaign_id_list'=>$cid, 'info_type_list'=>$val]);
    $err = $r['error'] ?? '';
    echo "== {$label} | http=".($r['_meta']['http_status'] ?? '?')
       . " | error=".($err ?: 'NONE')." | msg=".($r['message'] ?? '')."\n";
    if ($err === '') { $ok = [$label, $val, $r]; break; }
}
if ($ok) {
    dump('FORMAT_BERHASIL', $ok[0]);
    // kalau yang lolos hanya [1], ambil set penuh dengan encoding yang sama
    if ($ok[1] === [1]) {
        $ok[2] = $get->invoke($drv, $store, $path, ['campaign_id_list'=>$cid, 'info_type_list'=>[1,2,3,4]]);
    }
    dump('SETTING_INFO', $mask($ok[2]));
} else {
    echo "Semua format gagal — tempel baris error di atas (biasanya menyebut nilai valid).\n";
}

// Daily performance ID ASLI (7 hari, timezone Asia/Jakarta)
$from = now('Asia/Jakarta')->subDays(6)->format('d-m-Y');
$to   = now('Asia/Jakarta')->format('d-m-Y');
dump('DAILY_PERF', $mask($drv->getCampaignDailyPerformance($store, ['477707399'], $from, $to)));
```

**Yang dicari di output `SETTING_INFO`:** `bidding_method` / `bidding_strategy` (nilai spt `gmv_max`/`auto`/`manual`?), `roas_target`/`target_roas`, `recommended_roas`, `daily_budget`/`total_budget`, `campaign_status`, `item_id`, `campaign_type`/`objective`, indikator `gms`/`gmv`. **Jangan simpulkan GMV Max dari `ad_type`=manual.** Tempel output `== ...` (tiap percobaan), `SETTING_INFO`, dan `DAILY_PERF` ke sini untuk analisis Tahap 3–6.

---

## Sumber
- Bukti kode: `app/Services/Channels/Shopee/ShopeeChannel.php` (`:265,:274,:286,:298`, `doGet`, `sign`), `app/Services/MarketplaceSyncService.php` (`syncAdCampaigns`), `app/Console/Commands/Marketplace/SyncAdsDailyCommand.php`, `app/Models/Store.php` (`:28,:34,:51`), `config/shopee.php`, skema `database_dev.sqlite` (read-only).
- Riset (UNVERIFIED terhadap portal resmi yang ter-block): mirror SDK — https://github.com/congminh1254/shopee-sdk/blob/main/docs/managers/ads.md ; referensi module Ads resmi (tidak dapat dibuka): https://open.shopee.com/documents?module=105&type=1
