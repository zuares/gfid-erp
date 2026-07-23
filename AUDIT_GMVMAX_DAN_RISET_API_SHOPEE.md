# Audit Project + Riset API Shopee untuk Analisa & Optimasi GMV Max

**Sifat dokumen:** read-only audit + riset dokumentasi. Tidak ada perubahan kode, migration, atau query yang mengubah data pada pengerjaan laporan ini.
**Tanggal:** 23 Juli 2026
**Basis bukti:** kode project (`/Herd/gfid-dev`), skema SQLite dev (`database_dev.sqlite`, read-only `PRAGMA`/`SELECT COUNT`), dan pencarian web ke sumber Shopee.

> **Catatan keterbatasan akses dokumentasi (penting, sesuai aturan 14):**
> Portal dokumentasi resmi **`open.shopee.com/documents`** **tidak dapat dibuka** oleh alat fetch saya (di-block: `cowork_web_fetch_url_blocked`). Halaman Ads bantuan seller (`ads.shopee.*/learn`) dan mirror SDK komunitas dapat diakses lewat web search. Karena itu, semua klaim tentang **endpoint API** yang tidak bisa saya lihat teksnya langsung di portal resmi saya tandai **UNVERIFIED (portal resmi tidak dapat diakses)**, bukan CONFIRMED. Endpoint yang **sudah dipakai di kode project** saya tandai berdasarkan bukti kode (pasti dipanggil), meskipun teks dokumen resminya tidak bisa saya buka ulang.

---

# 1. Executive Summary

**Kondisi project.** Ini ERP Laravel 12 / PHP 8.2 (SQLite, timezone `Asia/Jakarta`) yang matang untuk operasi marketplace Shopee: order, logistik/label, retur, keuangan (escrow/settlement), produk, stok, chat, dan sebuah lapisan akuntansi (`sales_invoices`) dengan HPP ter-snapshot. Integrasi Shopee sudah mencakup **57+ endpoint v2** yang nyata dipanggil (bukti: `app/Services/Channels/Shopee/ShopeeChannel.php`). Scheduler Laravel aktif dan menjalankan sync order/retur/settlement/ads/produk terjadwal (`routes/console.php`, `bootstrap/app.php`, `app/Console/Kernel.php`).

**Kesiapan data.** Fondasi transaksi & keuangan **kuat**: tabel settlement memuat rincian fee escrow (commission, service, transaction, voucher, coin cashback, shipping subsidy, reverse shipping, activity/AMS, escrow tax, final income) plus kolom `ad_cost`. HPP tersedia per item dan ter-snapshot ke baris order/invoice. Yang **belum siap**: data iklan level GMV Max (target ROAS & budget) dan penggabungan spend iklan ke perhitungan profit.

**Apakah API GMV Max tersedia?** **UNVERIFIED / kemungkinan besar via "GMS Campaign".** GMV Max adalah **fitur Seller Centre yang terkonfirmasi** (halaman resmi `ads.shopee.*`: bidding "GMV Max", target ROAS custom/3 pilihan, 1 produk per campaign, budget). Pada Open Platform API (module Ads = **module 105**), mirror SDK komunitas menunjukkan ada rangkaian endpoint **"GMS (Gross Merchandise Sales) Campaign"** (create/edit/performance) yang **karakteristiknya cocok dengan GMV Max** (single product, ROAS target, budget). Korelasinya **kuat tetapi belum saya verifikasi pada teks dokumen resmi** karena portal resmi ter-block. **Kesimpulan aman: jangan asumsikan ada "GMV Max API" bernama demikian; yang perlu diverifikasi langsung ke portal/partner support adalah endpoint GMS campaign.**

**Gap paling kritis (urut prioritas):**
1. **Profit belum dikurangi ad spend.** `PayoutDashboardController` menghitung `net_profit = subtotal − COGS − platform_fee_total − refund_total` — **tanpa** ad spend (bukti: query di §8). Kolom `settlement.ad_cost` ada tapi tidak masuk formula profit.
2. **Target ROAS & daily budget GMV Max belum ada di data model** (tidak ada kolom `target_roas`/`daily_budget` di `marketplace_ad_campaigns`). Padahal ini variabel kontrol utama GMV Max.
3. **Data iklan yang dipakai project = Product/CPC ads generik**, bukan endpoint GMS/GMV Max. Perlu verifikasi apakah campaign GMV Max muncul di `get_product_level_campaign_id_list` atau butuh endpoint GMS khusus.
4. **Tabel-tabel iklan masih kosong (0 baris)** — analisa belum bisa diuji dengan data nyata sampai sync dijalankan.

**Langkah paling dulu:** (a) validasi akses/permission Ads API + cek apakah campaign GMV Max terbaca oleh endpoint yang sudah dipakai; (b) tambah penyimpanan target ROAS & budget; (c) satukan ad spend ke mesin profit.

---

# 2. Arsitektur Project Saat Ini

**Stack (bukti `composer.json`, `.env`):** Laravel `^12.0`, PHP `^8.2`, `laravel/sanctum ^4.2`, `doctrine/dbal ^4.4`, `barryvdh/laravel-dompdf`. DB `sqlite` (`DB_DATABASE=sqlite`), `APP_TIMEZONE=Asia/Jakarta`.

**Peta modul (folder `app/`):**
- **Channels abstraction** — `app/Services/Channels/ChannelManager.php`, kontrak `app/Services/Channels/Contracts/MarketplaceChannel.php`, driver `Shopee/ShopeeChannel.php` & `TikTokShop/TikTokShopChannel.php`. Multi-channel by design.
- **Sync inti** — `app/Services/MarketplaceSyncService.php` (order, settlement, ad campaigns, dll).
- **Import/akunting** — `app/Services/Marketplace/Shopee/ImportShopeeOrdersService.php`, `app/Services/Sales/SalesInvoiceService.php`, `app/Services/Accounting/ProductionValueReportService.php`.
- **Controllers** — `app/Http/Controllers/MarketplaceController.php` (besar; ads, mapping, stores), `Marketplace/MarketplaceOrderController.php`, `Marketplace/Reports/PayoutDashboardController.php`, `ShopeeStoreAuthController.php` (OAuth).
- **Commands** — `app/Console/Commands/Marketplace/*` (SyncOrders, SyncSettlements, SyncAdsDaily, SnapshotProducts, RunBoosts, SyncChats, …), plus `MarketplaceSyncFinanceCommand.php`, `MarketplaceBackfillAdMapping.php`.
- **Scheduler** — Laravel 12 (`bootstrap/app.php` `->withSchedule()`, `routes/console.php`, `app/Console/Kernel.php`).
- **Queue** — driver antrean di-drain lewat cron: `queue:work --stop-when-empty` tiap menit (`routes/console.php:109`).

**Alur data (ringkas):**
```
Shopee OAuth (ShopeeStoreAuthController) → simpan token di Store->credential()
  → sync order (get_order_list/detail, get_package_detail)
  → sync settlement (payment/get_escrow_detail) → marketplace_order_settlements
  → sync ads (ads/*) → marketplace_ad_campaigns (+ dailies)
  → import → sales_invoices/sales_invoice_lines (HPP snapshot) → PayoutDashboard (profit)
  → mapping SKU (sku_mappings, marketplace_order_items.internal_item_id)
```

**Scheduler terjadwal (bukti `routes/console.php`):**

| Command | Jadwal | Baris |
|---|---|---|
| `marketplace:sync-orders` | tiap 5 menit | `routes/console.php:70` |
| `marketplace:sync-returns` | hourly | `:74` |
| `marketplace:sync-settlements` | tiap 4 jam | `:79` |
| `marketplace:sync-chats` | tiap menit | `:84` |
| `marketplace:sync-ads-daily --days=3` | harian 23:30 | `:90` |
| `marketplace:snapshot-products --sync` | harian 23:45 | `:96` |
| `marketplace:run-boosts` | tiap 5 menit | `:102` |
| `queue:work --stop-when-empty` | tiap menit | `:109` |
| `sales:rebuild-daily-item-sales` | 00:05 | `:63` |
| `inventory:recalc-ads-from-daily` | 00:10 | `:66` |
| `marketplace:cleanup-labels` | 01:00 | `:114` |

---

# 3. Integrasi Shopee yang Sudah Ada

**OAuth & token (bukti `ShopeeChannel.php`, `ShopeeStoreAuthController.php`):**
- `partner_id`, `shop_id`, `access_token`, `refresh_token` disimpan via `Store->credential(...)`; expiry di `store.token_expires_at`.
- Auth exchange: `/api/v2/auth/access_token/get`.
- Refresh token **berotasi & sekali-pakai**; kode mengunci refresh agar tidak balapan (`ensureFreshToken()`, komentar di `ShopeeChannel.php:336–383`). Refresh proaktif bila token ≤2 menit sebelum expired.
- Request ditandatangani (`sign()`), timestamp per-request.

**Endpoint Shopee v2 yang nyata dipanggil** (bukti: `grep` path `/api/v2/...` di `app/Services/Channels/Shopee/`):

| Kategori | Endpoint | Fungsi di project |
|---|---|---|
| Auth | `auth/access_token/get` | tukar/refresh token |
| Shop | `shop/get_shop_info` | info toko |
| Order | `order/get_order_list`, `order/get_order_detail`, `order/get_package_detail`, `order/get_booking_list`, `order/get_booking_detail` | sync order & booking |
| Logistics | `logistics/get_shipping_parameter`, `ship_order`, `create_shipping_document`, `download_shipping_document`, `get_tracking_number/info`, + varian booking | label & tracking |
| Finance | `payment/get_escrow_detail` | settlement/escrow per order |
| Returns | `returns/get_return_list`, `get_return_detail`, `confirm`, `get_reverse_tracking_info` | retur |
| Product | `product/get_item_list`, `get_item_base_info`, `get_item_extra_info`, `get_model_list`, `get_category`, `update_price`, `update_stock`, `update_item_base_info`, `update_model`, `boost_item`, `get_boosted_list`, `unlist_item` | katalog, stok, harga, boost |
| **Ads** | `ads/get_product_level_campaign_id_list`, `ads/get_product_level_campaign_setting_info`, `ads/get_product_campaign_daily_performance`, `ads/get_all_cpc_ads_daily_performance`, `ads/get_shop_toggle_info`, `ads/get_total_balance` | **campaign CPC/Product ads + saldo** |
| Marketing | `discount/get_discount_list`, `add_discount`, `add_discount_item`, `update_discount_item`, `end_discount` | diskon toko |
| Chat | `sellerchat/*` | chat |

**Catatan penting Ads:** project **hanya membaca** (6 endpoint: id list, setting info, daily performance produk & toko, toggle, balance). **Tidak** memakai endpoint hourly, GMS/GMV Max, rekomendasi, atau create/edit budget/ROAS. Semua endpoint ads yang dipakai adalah **Product/CPC Ads generik**.

---

# 4. Hasil Riset API Shopee

Sumber yang bisa saya buka: halaman bantuan Ads resmi Shopee (`ads.shopee.*/learn`) dan **mirror SDK komunitas** (`github.com/congminh1254/shopee-sdk`, `docs/managers/ads.md`) yang menyebut modul resmi **`open.shopee.com/documents?module=105&type=1`**. Portal resmi sendiri **tidak dapat saya buka** (ter-block).

**Klasifikasi status (sesuai aturan 15):**

**CONFIRMED (dipakai di kode project — bukti pemanggilan pasti):**
- Ads: `get_product_level_campaign_id_list`, `get_product_level_campaign_setting_info`, `get_product_campaign_daily_performance`, `get_all_cpc_ads_daily_performance`, `get_shop_toggle_info`, `get_total_balance`.
- Finance: `payment/get_escrow_detail`.
- Order/Product/Returns/Logistics: lihat tabel §3.
- *(Catatan: "dipakai di kode" ≠ "teks doc resmi terverifikasi ulang"; portal resmi ter-block.)*

**UNVERIFIED — kemungkinan tersedia di module 105 (bukti: mirror SDK komunitas, bukan doc resmi):**
- **GMS Campaign** (indikasi = GMV Max): `checkCreateGmsProductCampaignEligibility`, `createGmsProductCampaign`, `editGmsProductCampaign`, `editGmsItemProductCampaign`, `listGmsUserDeletedItem`, `getGmsCampaignPerformance`, `getGmsItemPerformance`.
- **Manual Product Ads** dengan kontrol penuh: `createManualProductAds`/`editManualProductAds` (parameter `budget`, `roas_target`, `bidding_method`), `editManualProductAdKeywords`.
- **Performance tambahan:** `getAllCpcAdsHourlyPerformance`, `getProductCampaignHourlyPerformance`.
- **Rekomendasi:** `getRecommendedItemList`, `getRecommendedKeywordList`, `getProductRecommendedRoiTarget`, `getCreateProductAdBudgetSuggestion`.

**DEPRECATED (bukti: catatan SDK):** `createAutoProductAds`, `editAutoProductAds` (Auto Product Ads ditandai deprecated).

**AVAILABLE WITH RESTRICTION / PARTNER-ONLY:** Ads API butuh **permission khusus** + "Contact Shopee Partner Support" dan **tidak semua region** mendukung (bukti: catatan SDK + error `error_permission_denied`). → status **AVAILABLE WITH RESTRICTION**.

**SELLER CENTRE (fitur terkonfirmasi, bukan bukti API):** "GMV Max" sebagai bidding strategy, target ROAS (custom / 3 pilihan), 1 produk per campaign, budget (bukti: `ads.shopee.com.my/learn/faq/505/...`, `ads.shopee.sg/learn/faq/519/1787`, `ads.shopee.ph/learn/faq/478/1829`).

**NOT FOUND IN OFFICIAL API (tidak terverifikasi ada):** endpoint eksplisit bernama "gmv_max_*"; tidak ditemukan. Yang ada indikasinya = "GMS campaign".

---

# 5. Hasil Verifikasi API GMV Max

Menjawab 20 pertanyaan §7 secara tegas, dengan status + bukti:

| # | Pertanyaan | Status | Bukti / catatan |
|---|---|---|---|
| 1 | Ada API GMV Max? | **UNVERIFIED** | Nama "GMV Max" tak ditemukan sebagai endpoint. Indikasi kuat = "GMS Campaign" di module 105 (mirror SDK). Portal resmi ter-block. |
| 2 | Baca daftar campaign GMV Max? | **UNVERIFIED** | Perlu cek apakah muncul di `get_product_level_campaign_id_list` (dipakai project) atau butuh endpoint GMS. Belum bisa dikonfirmasi. |
| 3 | Baca target ROAS? | **UNVERIFIED** | `get_product_level_campaign_setting_info` (dipakai project) disebut punya "4 info types"; kemungkinan memuat ROAS/budget, tapi field belum terverifikasi di doc resmi. |
| 4 | Baca budget? | **UNVERIFIED** | Idem #3. |
| 5 | Baca actual ROAS? | **CONFIRMED (turunan)** | Bisa dihitung: `broad_gmv/expense` dari `get_product_campaign_daily_performance` (dipakai project; bukti kode di `MarketplaceSyncService`). |
| 6 | Baca spend? | **CONFIRMED** | `expense` per hari (idem). |
| 7 | Baca attributed GMV? | **CONFIRMED** | `broad_gmv`, `direct_gmv` (idem). |
| 8 | Baca impression & click? | **CONFIRMED** | `impression`, `clicks` (idem). |
| 9 | Performa harian? | **CONFIRMED** | `get_product_campaign_daily_performance` + `get_all_cpc_ads_daily_performance`. |
| 10 | Performa per produk? | **CONFIRMED** | Product campaign = per produk (1 campaign biasanya 1 item; `get_product_level_campaign_setting_info` memberi `item_id`). |
| 11 | Performa per SKU/model? | **UNVERIFIED** | Level campaign/item, bukan model/variasi. Per-SKU tidak terkonfirmasi. |
| 12 | Ubah target ROAS? | **UNVERIFIED** | `editManualProductAds`/`editGmsProductCampaign` (mirror SDK) — belum terverifikasi resmi; butuh permission. |
| 13 | Ubah budget? | **UNVERIFIED** | Idem #12. |
| 14 | Pause/activate campaign? | **UNVERIFIED** | Diindikasikan lewat edit (status), belum terverifikasi resmi. |
| 15 | Tersedia untuk seller biasa? | **AVAILABLE WITH RESTRICTION** | Butuh permission Ads + kemungkinan approval. |
| 16 | Butuh approval khusus? | **YA (indikasi)** | "Contact Shopee Partner Support" (mirror SDK). |
| 17 | Partner tertentu saja? | **UNVERIFIED** | Ada indikasi partner-only untuk Ads; belum pasti. |
| 18 | Region tertentu? | **YA** | "Not all regions support the Ads API". |
| 19 | Endpoint masih aktif? | **SEBAGIAN** | Product/CPC ads aktif (dipakai). Auto Product Ads **DEPRECATED**. |
| 20 | Ada versi deprecated? | **YA** | Auto Product Ads. |

**Ringkas:** Data **pembacaan performa** GMV Max (spend, GMV attributed, impresi, klik, order, actual ROAS harian per produk) **hampir pasti bisa** lewat endpoint product-ads yang **sudah dipakai** — **asalkan** campaign GMV Max ikut terdaftar di `get_product_level_campaign_id_list`. Pembacaan **setting** (target ROAS, budget) dan **penulisan** (ubah ROAS/budget, pause) **belum terverifikasi** dan **butuh permission + verifikasi ke portal resmi/partner support**. **Jangan bangun fitur tulis GMV Max sebelum poin ini dikonfirmasi.**

---

# 6. Pemetaan API vs Export Manual

| Data | API tersedia | Export Seller Centre | Sudah ada di project | Rekomendasi sumber |
|---|---|---|---|---|
| Ad spend | Ya (CONFIRMED) | Ya | Ya (`marketplace_ad_campaign_dailies.expense`) | API |
| Impression/Click/CTR/CPC | Ya (CONFIRMED) | Ya | Ya (dailies) | API |
| Ad order / items sold | Ya (CONFIRMED) | Ya | Ya (`broad_order`, `direct_order`) | API |
| Attributed revenue (GMV) | Ya (CONFIRMED) | Ya | Ya (`broad_gmv`, `direct_gmv`) | API |
| Actual ROAS | Turunan (CONFIRMED) | Ya | Ya (dihitung) | API |
| **Target ROAS (GMV Max)** | **UNVERIFIED** | **Ya (laporan/UI)** | **Tidak** | Verifikasi API dulu; sementara **export/manual input** |
| **Daily budget (GMV Max)** | **UNVERIFIED** | **Ya (UI)** | **Tidak** | Idem |
| GMV Max campaign flag | UNVERIFIED | Ya | Tidak | Verifikasi API; fallback export |
| Order & order item | Ya (CONFIRMED) | Ya | Ya | API |
| Settlement/escrow fee | Ya (CONFIRMED, `get_escrow_detail`) | Ya (Income report) | Ya (`marketplace_order_settlements`) | API |
| Voucher/diskon | Ya | Ya | Ya (settlement + order) | API |
| Affiliate commission | Sebagian (via escrow `activity_fee`/AMS) | Ya (Affiliate report) | Ya (parsial) | API + export jika perlu detail |
| Return/refund/cancel | Ya (returns API + escrow) | Ya | Ya | API |
| Stock | Ya (`get_item_list`/model) | Ya | Ya | API/internal |
| Rating / review count | Sebagian (`get_item_extra_info`) | Ya | Parsial | API + snapshot |
| HPP / COGS | Tidak (internal) | Tidak | Ya (internal) | Internal |
| Biaya packing / operasional | Tidak | Tidak | **Belum eksplisit** | Internal (input/param) |

> **Jika data GMV Max tidak tersedia via API:** export Seller Centre → **Shopee Ads → GMV Max → laporan performa** (CSV/XLSX). Granularitas umumnya per campaign/produk per hari; periode terbatas (biasanya ≤ beberapa bulan). Risiko: **format kolom bisa berubah** sewaktu-waktu → importer harus toleran skema (project sudah punya pola `mp_ads_imports`/`mp_ads_rows` yang kini deprecated tapi strukturnya bisa dihidupkan untuk ini).

---

# 7. Temuan Database

**Tabel relevan (bukti `PRAGMA table_info`, read-only):**

| Tabel | Fungsi | Kolom penting | Status |
|---|---|---|---|
| `marketplace_orders` | order | `store_id`, `channel_order_id`, `order_status`, `total_amount` | ada |
| `marketplace_order_items` | item order + mapping | `external_item_id`, `external_model_id`, `internal_item_id`, `item_id`, `hpp_unit_snapshot`, `hpp_total_snapshot`, `line_net_amount`, `mapping_status`, `marketplace_sku` | ada |
| `marketplace_order_settlements` | escrow | `buyer_payment_amount`, `commission_fee`, `service_fee`, `transaction_fee`, `seller_voucher`, `seller_coin_cash_back`, `actual_shipping_fee`, `shipping_fee_subsidy`, `reverse_shipping_fee`, `activity_fee`, `escrow_tax`, `final_income`, **`ad_cost`**, `settlement_time`, `raw_json` | ada, **0 baris** (dev) |
| `marketplace_ad_campaigns` | master campaign | `channel_campaign_id`, `channel_item_id`, `internal_item_id`, `ad_group_id`, `spend`, `gmv`, `roas`, `break_even_acos` | ada, 0 baris |
| `marketplace_ad_campaign_dailies` | fakta harian iklan | `expense`, `broad_gmv`, `direct_gmv`, `impressions`, `clicks`, `broad_order`, `direct_order` (unique store+campaign+date) | ada (Fase 1 sebelumnya) |
| `marketplace_ads_dailies` / `_balance_logs` | performa toko harian / saldo | — | migrasi ada |
| `sku_mappings` | mapping SKU→item | `marketplace_sku`, `channel_code`, `item_id` | ada, 53 baris |
| `items` | master item | `code`, `name`, `hpp`, `base_unit_cost` | ada |
| `sales_invoices` / `sales_invoice_lines` | lapisan akuntansi | `subtotal`, `platform_fee_total`, `refund_total`, `net_payout_actual`, `released_at`; line: `hpp_unit_snapshot`, `hpp_total_snapshot`, `line_total`, `qty` | ada |

**Kekurangan / risiko (dengan bukti):**
- **Tidak ada kolom `target_roas`, `daily_budget`, `campaign_objective`/GMV-Max flag** di `marketplace_ad_campaigns` → variabel kontrol GMV Max tidak tersimpan.
- **Tidak ada tabel histori perubahan setting iklan** (budget/ROAS over time) → sulit menganalisa dampak perubahan.
- **Nilai uang: tipe `numeric` di SQLite** (dari cast `decimal:2`) — aman dari float murni, tetapi lintas-DB harus dipastikan tetap `DECIMAL` (di MySQL/Postgres). Bukti: cast di model + kolom `numeric`.
- **Persen tidak konsisten sumbernya:** sebagian disimpan 0..1 (mis. rancangan `mp_ads_rows.ctr`), sebagian dihitung ×100 saat runtime (`MarketplaceSyncService`: `ctr = clicks/impr*100`). Perlu satu konvensi.
- **HPP snapshot per item** ada (bagus, immutability), tapi **biaya order (fee/voucher/shipping) belum dialokasikan ke level item** — hanya ada di level order/settlement. Untuk profit **per produk** perlu alokasi.
- **Mapping iklan→item baru berbasis `channel_item_id`** (Fase 1–3 sebelumnya). `sku_mappings` berbasis SKU yang bisa berubah → risiko mapping tidak stabil bila SKU diedit seller.
- **Timezone**: `APP_TIMEZONE=Asia/Jakarta`, sedangkan Ads API pakai format `DD-MM-YYYY` dan timestamp Shopee UTC epoch — konversi harus konsisten (kode sudah punya `normalizeShopeeTimestamp`, tapi tanggal ads perlu dipastikan align ke zona toko).
- **Data agregat vs detail:** performa toko harian (`get_all_cpc_ads_daily_performance`) agregat tanpa produk — jangan dicampur dengan level campaign saat agregasi profit per produk.

---

# 8. Temuan Formula KPI

**Formula aktual di kode (bukti):**

| Nama | File / lokasi | Formula aktual | Catatan |
|---|---|---|---|
| CTR | `MarketplaceSyncService::syncAdCampaigns` | `clicks / impressions × 100` | sesuai standar |
| CPC | idem | `spend / clicks` | sesuai standar |
| CVR | idem | `orders / clicks × 100` | `orders`=`broad_order` (bukan direct) |
| ROAS | idem & `adsAnalytics` | `gmv / spend` (`broad_gmv`) | pakai **broad** GMV |
| ACOS | `adsAnalytics` | `spend / gmv` (0..1) | pakai broad GMV |
| Break-even ACOS | `MarketplaceAdCampaign::effectiveBreakEvenAcos()` | `(avg_price − hpp)/avg_price`, `avg_price = gmv/units` | **diturunkan** karena `items` tak simpan harga jual |
| Profit after ads (iklan) | `adsAnalytics` | `gmv × break_even_acos − spend` | hanya margin kotor − spend |
| Rekomendasi | `MarketplaceController::adsRecommendation()` | rasio `acos/breakEvenAcos`: ≤0.6 Scale, ≤0.85 Maintain, ≤1.0 Watch, >1.0 Stop | ambang bisa dikonfigurasi |
| **Net profit (payout)** | `PayoutDashboardController` | `subtotal − COGS − platform_fee_total − refund_total` | **TANPA ad spend** ⚠️ |

**Perbandingan dengan formula target (§4 permintaan) — gap:**
- **Net revenue**: project belum punya satu definisi tunggal "net revenue" = gross − seller discount − voucher − refund − shipping subsidy. Komponennya **ada** (di settlement) tapi belum dirangkai jadi satu kolom/DTO.
- **Contribution margin before ads**: belum ada. Butuh `net_revenue − HPP − marketplace_fees − affiliate − packing − variable_ops`. Fee & HPP ada; **packing/variable ops belum**.
- **Profit after ads (level bisnis)**: **belum ada** yang menyatukan spend iklan ke profit. Yang ada hanya versi "iklan-sentris" (`gmv×BE−spend`) yang **tidak** memakai settlement aktual.
- **Break-even ROAS**: belum dihitung eksplisit; project pakai **break-even ACOS**. Relasi: `BE ROAS = 1 / BE ACOS`. Sebaiknya diturunkan dari **contribution margin aktual**, bukan hanya (harga−HPP)/harga.

**Risiko formula:** ROAS/ACOS memakai **broad** GMV (semua produk toko setelah klik), bukan **direct** — bisa **overstate** kontribusi campautan. Untuk keputusan profit yang konservatif, pertimbangkan analisa berbasis **direct** GMV/order juga (datanya sudah tersimpan: `direct_gmv`, `direct_order`).

---

# 9. Kecukupan Data Analisis Profit

**A. Siap digunakan (ada + terhubung):** order, order item, HPP snapshot per item, settlement/escrow fee lengkap, mapping item internal (Fase 1–3), performa iklan harian per campaign (spend/GMV/impr/klik/order).

**B. Ada tapi belum terhubung:** `settlement.ad_cost` (kolom ada, tak masuk profit); `direct_gmv`/`direct_order` (tersimpan, belum dipakai untuk analisa konservatif); settlement fee (ada, belum dialokasikan ke item untuk profit per produk).

**C. Butuh API (verifikasi dulu):** target ROAS & budget GMV Max; flag campaign GMV Max; performa hourly; rekomendasi ROI/budget Shopee.

**D. Butuh export manual (fallback):** setting GMV Max (target ROAS/budget) bila API tak tersedia; laporan affiliate detail; rating/review historis lengkap.

**E. Harus dihitung internal:** contribution margin, net revenue tunggal, profit after ads, break-even ROAS dari margin aktual, stock coverage/safety stock, biaya packing/variabel.

**F. Tidak tersedia / belum terverifikasi:** kemampuan **menulis** setting GMV Max (ubah ROAS/budget/pause) via API; performa per SKU/model iklan.

---

# 10. Temuan Kritis

```
Judul: Profit tidak mengurangi ad spend
Severity: Critical
Bukti kode: PayoutDashboardController net_profit = subtotal − COGS − platform_fee_total − refund_total (tanpa ad spend); settlement.ad_cost ada tapi tak dipakai
Bukti dokumentasi: —
Dampak bisnis: keputusan scale/stop bisa salah karena "profit" overstate; produk boros tak terdeteksi
Dampak teknis: perlu join ad spend (per order via settlement.ad_cost, atau per produk via ad dailies) ke mesin profit
Rekomendasi: definisikan "profit after ads" resmi = contribution margin − ad spend teralokasi
```
```
Judul: Target ROAS & budget GMV Max tidak tersimpan
Severity: High
Bukti kode: marketplace_ad_campaigns tak punya kolom target_roas/daily_budget/objective (PRAGMA table_info)
Bukti dokumentasi: GMV Max Seller Centre memakai target ROAS + budget (ads.shopee.* learn)
Dampak bisnis: tak bisa bandingkan actual vs target ROAS, tak bisa deteksi budget cap-out
Dampak teknis: butuh kolom + sumber (API GMS/setting_info bila terverifikasi, atau export/manual)
Rekomendasi: tambah penyimpanan target ROAS/budget + histori perubahannya
```
```
Judul: Sumber data iklan = Product/CPC ads, bukan GMS/GMV Max terverifikasi
Severity: High
Bukti kode: hanya ads/get_product_level_* & get_*_daily_performance dipakai (ShopeeChannel)
Bukti dokumentasi: mirror SDK menyebut GMS campaign endpoints terpisah (UNVERIFIED resmi)
Dampak bisnis: bila campaign GMV Max TIDAK muncul di get_product_level_campaign_id_list, data iklan GMV Max tak tertarik sama sekali
Dampak teknis: perlu uji lapangan 1 toko: apakah campaign GMV Max ikut terdaftar
Rekomendasi: Fase 1 validasi — panggil id_list + setting_info di toko ber-GMV Max, cek hasilnya
```
```
Judul: Fee/voucher/shipping belum dialokasikan ke level item
Severity: Medium
Bukti kode: settlement per order; hpp per item; tak ada alokasi biaya order→item
Dampak bisnis: profit per PRODUK (yang jadi basis keputusan iklan) belum akurat
Dampak teknis: butuh aturan alokasi (proporsi line_total/subtotal) — pola sudah dipakai di PayoutDashboard net_alloc
Rekomendasi: tabel/DTO alokasi biaya per order_item
```
```
Judul: Konvensi persen & broad vs direct tidak seragam
Severity: Medium
Bukti kode: ctr disimpan ×100 di sync, 0..1 di rancangan mp_ads_rows; ROAS/ACOS pakai broad_gmv
Dampak bisnis: risiko salah baca metrik; broad overstate kontribusi
Dampak teknis: tetapkan 1 konvensi + sediakan varian direct
Rekomendasi: simpan raw (0..1) + hitung tampilan; tambah metrik direct
```
```
Judul: Tabel iklan masih kosong (belum tervalidasi data nyata)
Severity: Medium
Bukti kode: SELECT COUNT = 0 pada marketplace_ad_campaigns / dailies / settlements (dev DB)
Dampak bisnis: analisa belum bisa diuji end-to-end
Dampak teknis: perlu sync 1 toko + rekonsiliasi vs dashboard Shopee
Rekomendasi: jalankan sync terbatas, verifikasi angka
```
```
Judul: Portal dokumentasi resmi Shopee tidak dapat diakses alat audit
Severity: Low (proses)
Bukti: open.shopee.com/documents ter-block (cowork_web_fetch_url_blocked)
Dampak: sebagian klaim endpoint berstatus UNVERIFIED
Rekomendasi: verifikasi manual di portal resmi / partner support sebelum implementasi tulis
```

---

# 11. Rekomendasi Arsitektur

**Model data (usulan, TANPA membuat migration sekarang):**

- **`shopee_ad_campaigns`** (perluas `marketplace_ad_campaigns`): tambah `campaign_objective`/`is_gmv_max`, `target_roas`, `daily_budget`, `started_at`, `ended_at`. Unique: `(store_id, channel_campaign_id)`. Simpan `raw_payload`.
- **`shopee_ad_performances`** (sudah ada sebagai `marketplace_ad_campaign_dailies`): grain campaign×hari. Simpan broad & direct. Unique `(store_id, campaign_id, date)`. Pertimbangkan tambah `hour` bila pakai hourly API.
- **`shopee_ad_setting_histories`** (baru): `campaign_id, changed_at, old_budget, new_budget, old_target_roas, new_target_roas, source(api/export/manual), note`. Immutable append-only.
- **`product_cost_histories`** (baru / cek apakah `ItemCostSnapshot` sudah menutupi): `item_id, effective_from, effective_to, unit_cost`. Untuk profit historis akurat (HPP berubah).
- **`marketplace_order_item_costs`** (baru): alokasi biaya per `order_item_id` (`gross_sales, discount_alloc, voucher_alloc, fee_alloc, affiliate_alloc, shipping_alloc, refund_alloc, hpp, packing_cost`). Sumber: settlement diproporsikan `line_total/subtotal`. Idempoten per order_item.
- **`product_profitability_daily`** (baru, materialized): `date, store_id, product_id/internal_item_id, sku, total_revenue, ad_revenue, organic_revenue, ad_spend, hpp, fees, voucher, affiliate, refund, return_cost, contribution_profit, profit_after_ads, break_even_roas, actual_roas`. Unique `(date, store_id, internal_item_id)`. Recompute idempoten.

**Service/kalkulasi:** satu `ProfitEngine` yang menyatukan settlement + HPP + ad spend + alokasi → `contribution_profit` & `profit_after_ads`. **Jangan** hitung profit di dua tempat (hindari double count).

**Scheduler/importer:** pertahankan sync harian; tambah **importer laporan GMV Max** (hidupkan pola `mp_ads_imports`/`mp_ads_rows`) sebagai fallback bila setting ROAS/budget tak tersedia via API.

**ImmutabilitAS & anti-duplikasi:** raw_payload disimpan; fakta harian upsert by unique key (sudah diterapkan di dailies); settlement idempoten per order; profitabilitas harian recompute, bukan increment.

---

# 12. Rencana Implementasi

**Fase 1 — Validasi akses API & permission.**
Tujuan: pastikan Ads permission aktif & campaign GMV Max terbaca. File terdampak (baca saja): `ShopeeChannel`, `MarketplaceSyncService`. Tabel: —. Dependency: toko ber-GMV Max aktif. Risiko: permission ditolak / GMV Max tak muncul di id_list. Selesai bila: `get_product_level_campaign_id_list` + `get_product_level_campaign_setting_info` dites di toko nyata dan diketahui apakah GMV Max ikut + apakah setting_info memuat target ROAS/budget. Test: panggilan read-only + inspeksi `raw_json`.

**Fase 2 — Perbaikan fondasi data.**
Tujuan: konvensi persen seragam, kolom target_roas/budget/objective, histori setting. Tabel: `marketplace_ad_campaigns` (+kolom), `shopee_ad_setting_histories`. Risiko: migrasi kolom (tabel kosong → aman). Selesai: kolom tersedia + terisi dari sumber terverifikasi. Test: unit test cast & unique.

**Fase 3 — Sinkronisasi order & settlement (pemantapan).**
Tujuan: pastikan escrow fee lengkap & idempoten; hubungkan `settlement.ad_cost`. File: `MarketplaceSyncService` (settlement), `SyncSettlementsCommand`. Tabel: `marketplace_order_settlements`. Risiko: refund pasca-settlement. Selesai: rekonsiliasi vs Income report. Test: idempotensi re-sync.

**Fase 4 — Integrasi data iklan / import laporan.**
Tujuan: tarik performa GMV Max (API bila bisa; else importer laporan). Tabel: `marketplace_ad_campaign_dailies`, (opsional) `mp_ads_rows`. Risiko: format export berubah. Selesai: data harian per campaign/produk terisi + tervalidasi.

**Fase 5 — Mapping SKU & alokasi biaya.**
Tujuan: profit per produk. Tabel: `marketplace_ad_item_maps` (ada), `marketplace_order_item_costs` (baru). Risiko: SKU berubah. Selesai: setiap order_item punya alokasi biaya + HPP.

**Fase 6 — Mesin profitabilitas.**
Tujuan: `ProfitEngine` + `product_profitability_daily`. Dependency: Fase 3–5. Risiko: double count. Selesai: profit_after_ads & break_even_roas per produk/hari, terrekonsiliasi.

**Fase 7 — Dashboard GMV Max.**
Tujuan: UI actual vs target ROAS, profit after ads, stock coverage. File: view ads (sudah ada, tinggal perluas). Selesai: KPI non-ROAS tampil.

**Fase 8 — Rekomendasi scale/maintain/optimize/pause.**
Tujuan: rule engine berbasis profit + stok + minimum data. Dependency: Fase 6–7. Selesai: rekomendasi keluar dengan guardrail data minimum.

---

# 13. Daftar Endpoint Prioritas

```
Prioritas 1
Nama API: get_product_level_campaign_id_list
Endpoint: /api/v2/ads/get_product_level_campaign_id_list
Manfaat: tahu apakah campaign GMV Max terdaftar (penentu strategi)
Permission: Ads
Data: daftar campaign_id + ad_type
Sudah dipakai: YA
```
```
Prioritas 2
Nama API: get_product_level_campaign_setting_info
Endpoint: /api/v2/ads/get_product_level_campaign_setting_info
Manfaat: baca setting (kandidat target ROAS/budget) + item_id (mapping)
Permission: Ads
Data: setting campaign (4 info types) — verifikasi field
Sudah dipakai: YA (untuk item_id)
```
```
Prioritas 3
Nama API: get_product_campaign_daily_performance
Endpoint: /api/v2/ads/get_product_campaign_daily_performance
Manfaat: spend/GMV/impr/klik/order harian per campaign (basis analisa)
Permission: Ads
Data: metrics_list harian (broad & direct)
Sudah dipakai: YA
```
```
Prioritas 4
Nama API: payment/get_escrow_detail
Endpoint: /api/v2/payment/get_escrow_detail
Manfaat: fee/voucher/shipping aktual → profit
Permission: Finance
Data: order_income (fee lengkap)
Sudah dipakai: YA
```
```
Prioritas 5 (UNVERIFIED — verifikasi resmi dulu)
Nama API: GMS campaign performance / setting (getGmsCampaignPerformance, getGmsItemPerformance)
Endpoint: (module 105 — belum terverifikasi teks resmi)
Manfaat: bila GMV Max = GMS, ini sumber langsung target ROAS/budget/performa
Permission: Ads (partner/approval)
Data: performa & setting GMS
Sudah dipakai: TIDAK
```
```
Prioritas 6 (opsional)
Nama API: getProductRecommendedRoiTarget / getCreateProductAdBudgetSuggestion
Endpoint: (module 105 — UNVERIFIED)
Manfaat: rekomendasi ROAS/budget dari Shopee sebagai pembanding
Permission: Ads
Sudah dipakai: TIDAK
```

---

# 14. Pertanyaan yang Belum Terjawab

1. Apakah campaign **GMV Max** ikut muncul di `get_product_level_campaign_id_list`, atau hanya lewat endpoint **GMS** terpisah? (butuh uji di toko nyata + portal resmi)
2. Apakah `get_product_level_campaign_setting_info` mengembalikan **target ROAS & daily budget** untuk campaign GMV Max? (verifikasi field di portal resmi)
3. Apakah akun/aplikasi ini **sudah punya permission Ads** dan region-nya didukung? (cek di Seller/partner console)
4. Apakah endpoint **tulis** (ubah ROAS/budget/pause) tersedia untuk seller ini atau partner-only? (partner support)
5. Format & granularitas **export laporan GMV Max** terbaru di Seller Centre (kolom, periode maksimal)?
6. Apakah `ItemCostSnapshot` yang ada sudah cukup sebagai histori HPP, atau perlu tabel `product_cost_histories` terpisah? (butuh baca detail model tsb)
7. Nilai **biaya packing/variabel** — dari mana sumbernya (konstanta, per item, atau input)?

---

# 15. Kesimpulan

Kombinasi berikut yang berlaku untuk project ini:

- **Fondasi data harus dilengkapi & disatukan lebih dulu** (bukan dibangun dari nol): order/settlement/HPP sudah kuat, tapi **profit belum mengurangi ad spend** dan **target ROAS/budget GMV Max belum tersimpan**.
- **API Shopee mencukupi untuk PEMBACAAN performa iklan** (spend, GMV, impresi, klik, order, actual ROAS harian per produk) — **sudah terpakai** — **selama** campaign GMV Max terdaftar di endpoint product-ads (perlu **Fase 1 validasi**).
- **API untuk SETTING & KONTROL GMV Max (target ROAS/budget, pause) BELUM TERVERIFIKASI** dan kemungkinan **butuh permission/approval + verifikasi ke portal resmi/partner support**. Kandidatnya = **endpoint "GMS campaign"** (module 105) — **jangan diasumsikan tersedia** sampai dikonfirmasi.
- **Perlu kombinasi API + export + data internal:** performa via API; target ROAS/budget via API GMS **atau** export/manual bila API tak tersedia; HPP/packing/alokasi biaya via internal.
- **Kemungkinan perlu reauthorization/permission tambahan** untuk scope Ads penuh (khususnya tulis).

**Urutan aman:** Fase 1 (validasi akses & apakah GMV Max terbaca) → Fase 2–3 (fondasi data + satukan ad spend ke profit) → Fase 4–6 (import/mapping/mesin profit) → Fase 7–8 (dashboard + rule engine). Jangan bangun fitur **tulis** GMV Max sebelum ketersediaan API-nya terbukti di dokumentasi resmi.

---

## Lampiran — Sumber

**Bukti kode (project):** `composer.json`, `.env`, `routes/console.php`, `bootstrap/app.php`, `app/Console/Kernel.php`, `app/Services/Channels/Shopee/ShopeeChannel.php`, `app/Services/MarketplaceSyncService.php`, `app/Http/Controllers/MarketplaceController.php`, `app/Http/Controllers/Marketplace/Reports/PayoutDashboardController.php`, `app/Services/Marketplace/Shopee/ImportShopeeOrdersService.php`, skema `database_dev.sqlite` (read-only).

**Sumber web (Shopee & mirror):**
- Shopee Ads – Introduction to GMV Max (SG): https://ads.shopee.sg/learn/faq/519/1787
- Shopee Ads – GMV Max Custom ROAS FAQ (PH): https://ads.shopee.ph/learn/faq/478/1829
- Shopee Ads – GMV Max FAQ / Shop GMV Max (MY): https://ads.shopee.com.my/learn/faq/505/1831 , https://ads.shopee.com.my/learn/faq/505/2078
- Mirror SDK komunitas (menyebut module resmi 105): https://github.com/congminh1254/shopee-sdk/blob/main/docs/managers/ads.md
- Referensi module Ads resmi (TIDAK dapat dibuka oleh alat — di-block): https://open.shopee.com/documents?module=105&type=1
