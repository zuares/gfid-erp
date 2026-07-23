# Fase 2 — Implementasi Perbaikan Pembacaan Setting & Performa GMV Max

**Sifat:** perubahan kode read-only terhadap Shopee (tidak ada endpoint tulis, tidak mengubah campaign). Perubahan minimal, backward-compatible, ber-test.
**Basis:** hasil API nyata campaign `477707399` (Fase 1).

> **Catatan eksekusi:** environment saya tidak punya PHP, jadi `php artisan migrate`/`test` **belum saya jalankan** — perintahnya ada di §7 untuk dijalankan di Herd. Kode sudah dicek keseimbangan sintaksis (brace/paren) secara statis.

---

## 1. Ringkasan Implementasi
Memperbaiki **dua bug terkonfirmasi** dan menyimpan setting GMV Max:
1. `getCampaignSettingInfo` kini mengirim `info_type_list` sebagai **comma-string** (default `1,2,3,4`), backward-compatible.
2. Parser memakai **path nyata** `common_info.item_id_list.0` (dan seluruh `common_info.*` + `auto_bidding_info.roas_target`) — memperbaiki `channel_item_id` yang selama ini kosong.
3. Menyimpan `ad_type, bidding_method, target_roas, campaign_budget, campaign_status, campaign_placement, started_at, ended_at, raw_setting_payload, setting_synced_at` (migration aditif, nullable).
4. Partial-failure aman + idempoten. Raw payload disanitasi dari credential.

## 2. File yang Berubah
```
File:      app/Services/Channels/Shopee/ShopeeChannel.php
Perubahan: getCampaignSettingInfo(+param $infoTypeList = [1,2,3,4]); +normalizeInfoTypeList() (public static).
Alasan:    info_type_list WAJIB & harus comma-string (array ditolak Shopee). Backward compatible.

File:      app/Services/MarketplaceSyncService.php
Perubahan: bagian 3b bangun $settingMap via parseCampaignSetting(); bagian 4 isi kolom setting
           HANYA bila setting berhasil (partial-failure aman); +parseCampaignSetting() & stripSensitive() (public static).
Alasan:    perbaiki path parse (bug item_id), simpan setting, jaga idempotensi & partial failure.

File:      app/Models/MarketplaceAdCampaign.php
Perubahan: +10 kolom fillable; +casts (target_roas decimal:4, campaign_budget decimal:2,
           started_at/ended_at/setting_synced_at datetime, raw_setting_payload array).
Alasan:    dukung kolom baru; uang pakai decimal (bukan float).

File:      database/migrations/2026_07_24_100000_add_setting_columns_to_marketplace_ad_campaigns_table.php  (BARU)
Perubahan: tambah 10 kolom nullable ke marketplace_ad_campaigns.
Alasan:    simpan setting; aditif & aman untuk DB berisi data; tidak menghapus kolom existing.

File:      tests/Feature/Services/MarketplaceAdSettingTest.php  (BARU)
Perubahan: unit test normalizer + parser + sanitizer + kasus null/manual.
Alasan:    membuktikan kedua bug fix tanpa memanggil API production.
```

## 3. Migration
Tabel `marketplace_ad_campaigns`, kolom baru (semua **nullable**, aditif):
`ad_type` (string40), `bidding_method` (string40), `target_roas` (decimal 10,4), `campaign_budget` (decimal 15,2), `campaign_status` (string40), `campaign_placement` (string40), `started_at` (timestamp), `ended_at` (timestamp), `raw_setting_payload` (json), `setting_synced_at` (timestamp). Tidak membuat `is_gmv_max` (API tak memberi label literal).

## 4. Perbaikan API Request (sebelum → sesudah)
**Sebelum** (`ShopeeChannel::getCampaignSettingInfo`):
```php
return $this->get($store, '/api/v2/ads/get_product_level_campaign_setting_info', [
    'campaign_id_list' => implode(',', $campaignIds),      // info_type_list HILANG → error_param
]);
```
**Sesudah:**
```php
public function getCampaignSettingInfo(Store $store, array $campaignIds,
    array|string $infoTypeList = [1, 2, 3, 4]): array {
    return $this->get($store, '/api/v2/ads/get_product_level_campaign_setting_info', [
        'campaign_id_list' => implode(',', $campaignIds),
        'info_type_list'   => self::normalizeInfoTypeList($infoTypeList),  // → "1,2,3,4" (comma-string)
    ]);
}
```
`normalizeInfoTypeList`: array|string → comma-string int positif unik; kosong → default `1,2,3,4`; **tidak pernah** kirim array mentah ke Guzzle.

## 5. Perbaikan Parser (path salah → path benar)
**Lama (salah)** di `syncAdCampaigns`:
```php
$itemId = data_get($c,'item_id') ?? data_get($c,'item_id_list.0')
       ?? data_get($c,'manual_product_ads.0.item_id') ?? ...   // TIDAK ADA di response nyata
```
**Baru (benar)** di `parseCampaignSetting()`:
```php
$itemId = data_get($node,'common_info.item_id_list.0')   // path NYATA (prioritas)
       ?? data_get($node,'item_id') ?? data_get($node,'item_id_list.0');
// + common_info.{ad_type,ad_name,bidding_method,campaign_status,campaign_placement,
//    campaign_budget,campaign_duration.*} + auto_bidding_info.roas_target
```
Normalisasi: `start_time>0`→Carbon(tz app), `end_time=0`→**null**, `target_roas` disimpan apa adanya (0 valid), status disimpan nilai asli Shopee (`closed`/`ongoing`/…), budget disimpan apa adanya (asumsi IDR mayor: 25000 = Rp25.000 — sesuai Seller Centre).

## 6. Idempotensi & Failure Handling
- **Idempoten:** master via `firstOrNew(['store_id','channel_campaign_id'])` (unique `uniq_mp_ad_campaigns_campaign`) → re-sync memperbarui row yang sama; harian via `upsert(unique: store+campaign+date)`. Tidak ada duplikasi.
- **Setting gagal:** blok setting hanya jalan `if (isset($settingMap[$cid]))`. Bila gagal → kolom setting lama **tidak ditimpa null**, `setting_synced_at` tidak diubah, `raw_setting_payload` lama tetap; sync **performa tetap jalan**.
- **Performa gagal:** tidak menghapus setting (blok terpisah).
- **Campaign tanpa `auto_bidding_info`** (manual): tetap tersimpan, `target_roas = null`, `bidding_method = manual`.
- **Sanitasi:** `stripSensitive()` membuang `access_token/refresh_token/partner_key/partner_key_v2/sign/credentials` rekursif sebelum simpan raw.

## 7. Test — perintah (jalankan di Herd)
```bash
php artisan migrate --pretend      # tinjau SQL migration tanpa apply
php artisan migrate                # apply (dev)
php artisan test tests/Feature/Services/MarketplaceAdSettingTest.php
php artisan test --filter=MarketplaceAd
php artisan optimize:clear
```
Test yang ditambahkan (Pest, tanpa hit API production): normalisasi `info_type_list` (dedup/trim/kosong/negatif/default), parser fixture nyata 477707399 (assert channel_item_id=28944692968, bidding_method=auto, target_roas=0, campaign_budget=25000, campaign_status=closed, ended_at=null), campaign manual null-node, fallback tanpa item_id, sanitasi credential.
> **Belum dieksekusi di sini** (tak ada PHP). Jika ada failure lama tak terkait, laporkan terpisah — jangan disembunyikan.

## 8. Verifikasi Data (bentuk row yang DIHARAPKAN setelah sync — dari fixture nyata, tanpa credential)
```
channel_campaign_id : 477707399
channel_item_id     : 28944692968
ad_type             : manual
bidding_method      : auto
target_roas         : 0.0000
campaign_budget     : 25000.00
campaign_status     : closed
campaign_placement  : all
started_at          : 2026-06-09 00:00:00 (WIB)
ended_at            : null            (end_time=0)
setting_synced_at   : <now saat sync>
raw_setting_payload : { campaign_id, common_info{...}, auto_bidding_info{roas_target:0}, ... }  (tanpa token/sign)
```
> Harus dikonfirmasi dengan menjalankan sync read-only 1 toko (§Tahap 12) lalu bandingkan dengan response mentah.

## 9. Risiko Tersisa
- Campaign uji `477707399` berstatus **closed** & `roas_target = 0` → **belum** terbukti nilai `target_roas` **non-nol** pada campaign GMV Max **aktif ber-Custom ROAS**. Perlu uji ulang di campaign aktif.
- **Label literal "GMV Max" tidak ada** di API → identifikasi bertumpu pada `bidding_method="auto"` + `auto_bidding_info` (LIKELY, bukan CONFIRMED). Jangan hardcode `is_gmv_max`.
- **Recommended ROAS** tidak tersedia di `setting_info` (butuh endpoint rekomendasi terpisah — di luar Fase 2).
- **Satuan budget** diasumsikan IDR mayor (25000=Rp25.000). Konfirmasi dgn Seller Centre bila ada campaign bernilai besar.
- Test integrasi idempotensi/partial-failure penuh (Http::fake + fixture Store) belum ditulis karena project tak punya factory Store/Channel; dijamin oleh desain (unique key + guard `isset`). Rekomendasi: tambah di fase berikut memakai `Http::fake()`.

## 10. Rekomendasi Fase Berikutnya
1. **Rekonsiliasi campaign aktif**: sync toko dgn campaign GMV Max aktif → verifikasi `target_roas` non-nol + angka performa nyata.
2. **Gabungkan ad spend ke profit**: satukan `settlement.ad_cost`/ad spend ke mesin profit (`PayoutDashboard` saat ini belum kurangi iklan).
3. **Break-even ROAS** dari contribution margin aktual (bukan hanya BE ACOS).
4. **Dashboard actual vs target ROAS** (pakai `target_roas` baru).
5. **Rekomendasi scale/maintain/optimize/pause** berbasis profit + stok + minimum data.

---
### Batas Fase 2 (tidak diimplementasikan — sesuai instruksi)
Tidak ada: update ROAS/budget, pause/activate/create/edit/delete campaign, endpoint tulis Shopee, mesin profit, dashboard baru, refactor besar, perubahan formula profit.
