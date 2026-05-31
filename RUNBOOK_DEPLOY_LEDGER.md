# Runbook Deploy — Migrasi Readiness ke Ledger (Fase 0–3b)

Tujuan: mengaktifkan sumber data "Siap Jahit" & "Siap Finishing" dari **ledger**
(`inventory_mutations.cutting_job_bundle_id`) agar **bebas stok hantu**, tanpa
mengubah costing/HPP/jurnal. Semua langkah dirancang **reversibel** sampai langkah
flip toggle, dan punya **rollback instan**.

Prinsip: **preview dulu, baru apply**. Semua command tulis data default *dry-run*.

---

## 0. Prasyarat & catatan penting

- **Backup DB dulu** (snapshot/dump) sebelum mulai. Wajib.
- Costing **tidak disentuh**: semua perpindahan WIP `affectLotCost: false`. Yang
  ditambahkan hanya **label** `cutting_job_bundle_id` + pembacaan readiness.
- **Migrasi penuh `php artisan migrate` mungkin tersangkut** migrasi lain yang rusak
  (`add_marketplace_fields_to_sales_invoices` — duplicate column). Karena itu migrasi
  kita dijalankan **per-path** (lihat Langkah 2). Cek dulu kondisi di produksi.
- Toggle perilaku: env **`INVENTORY_READINESS_SOURCE`** (`cache` | `ledger`),
  dibaca lewat `config/inventory.php`. Default `cache` = perilaku lama.
- Window: pilih jam sepi. Backfill & reconcile aman jalan saat sistem live (read +
  update kolom tag), tapi shadow-compare paling akurat saat tidak ada transaksi berjalan.

---

## 1. Deploy KODE dengan mode = cache (perilaku lama, nol perubahan)

> Tujuan: kode baru terpasang TANPA mengubah perilaku apa pun. Ledger belum aktif.

1. Pastikan `.env` produksi: **`INVENTORY_READINESS_SOURCE=cache`** (atau belum ada
   variabelnya sama sekali → default `cache`). **JANGAN** set `ledger` dulu.
2. Deploy kode (git pull / CI). File yang masuk:
   - `config/inventory.php`
   - `app/Models/{CuttingJobBundle,InventoryMutation}.php`
   - `app/Services/Inventory/InventoryService.php`
   - `app/Services/Production/{CuttingService,QcService}.php`
     (QcService: tag per-bundle utk sewing/finishing QC — saat ini dead code,
      ditag demi konsistensi bila kelak diaktifkan)
   - 5 controller produksi (Sewing Pickup/Return, RtsDirectReceive, FinishingJob, WipFinAdjustment)
   - command baru: `inventory:backfill-mutation-bundle`, `inventory:shadow-compare`,
     `inventory:reconcile-wipfin`, `inventory:e2e-test` + 1 migrasi.
3. Refresh cache config (kalau pakai config cache):
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```
4. **Verifikasi mode**:
   ```bash
   php artisan tinker --execute='echo config("inventory.readiness_source");'   # harus: cache
   ```

✅ **Checkpoint 1**: aplikasi jalan normal, daftar Siap Jahit/Finishing identik
seperti sebelum deploy (karena masih mode cache).

---

## 2. Jalankan migrasi (tambah kolom tag — additive, aman)

> Menambah kolom `cutting_job_bundle_id` (nullable) + index di `inventory_mutations`.
> Tidak mengubah nilai apa pun.

```bash
php artisan migrate --path=database/migrations/2026_05_31_170000_add_cutting_job_bundle_id_to_inventory_mutations.php --force
```

Verifikasi kolom ada:
```bash
php artisan tinker --execute='echo \Schema::hasColumn("inventory_mutations","cutting_job_bundle_id")?"OK":"GAGAL";'
```

**Rollback langkah ini (kalau perlu)**:
```bash
php artisan migrate:rollback --path=database/migrations/2026_05_31_170000_add_cutting_job_bundle_id_to_inventory_mutations.php --force
```

✅ **Checkpoint 2**: kolom + index terbentuk. Perilaku app tetap mode cache.

---

## 3. Backfill tag bundle ke mutasi historis (preview → apply)

> Mengisi `cutting_job_bundle_id` dari notes (cutting_*, SewingPickup, qc_adjust,
> qc_void via "reverse mut#"). Hanya isi label; nilai ledger tak berubah.

**3a. Preview (dry-run):**
```bash
php artisan inventory:backfill-mutation-bundle
```
Periksa tabel ringkasan: kolom **"Notes tanpa kode"** dan **"Kode tak ketemu"**
idealnya 0 untuk tipe cutting_*. Catat berapa "Akan di-tag".

**3b. Apply:**
```bash
php artisan inventory:backfill-mutation-bundle --apply
```

**3c. Verifikasi integritas tag:**
```bash
php artisan tinker --execute='
$orphan=\DB::table("inventory_mutations")->whereNotNull("cutting_job_bundle_id")
  ->whereNotIn("cutting_job_bundle_id",\App\Models\CuttingJobBundle::pluck("id"))->count();
echo "orphan_tags={$orphan} (harus 0)\n";
foreach(["WIP-CUT","WIP-SEW","WIP-FIN"] as $c){
  $w=\App\Models\Warehouse::where("code",$c)->first();
  $s=(float)\DB::table("inventory_stocks")->where("warehouse_id",$w->id)->sum("qty");
  $m=(float)\DB::table("inventory_mutations")->where("warehouse_id",$w->id)->sum("qty_change");
  printf("%-8s stock=%s mutasi=%s drift=%s %s\n",$c,$s,$m,$s-$m,abs($s-$m)<0.01?"OK":"!! CEK");
}'
```
Harapan: `orphan_tags=0`, dan tiap WIP `drift≈0` (ledger internally consistent).

✅ **Checkpoint 3**: mutasi historis ter-tag, 0 orphan, ledger konsisten.
**Catatan**: backfill aman diulang (idempotent — yang sudah ada di-skip).

---

## 4. Shadow-compare (read-only) — pastikan ledger layak jadi sumber

> Membandingkan cache vs saldo ledger per-bundle. READ-ONLY, tidak menulis.

```bash
php artisan inventory:shadow-compare
```
Yang dibaca:
- Baris **"Ledger UNTAGGED"** per stage → idealnya **0 baris / net 0** (artinya
  setiap pergerakan bisa diatribusi ke bundle).
- Selisih cache vs ledger WIP-CUT bisa besar (itu sifat cache `cut_wip_qty` yang
  kumulatif — **bukan** error). Yang penting UNTAGGED = 0.

Kalau masih ada UNTAGGED bernilai di WIP-CUT/WIP-FIN, identifikasi source_type-nya
dan (kalau bisa diturunkan) tambahkan ke backfill, lalu ulang Langkah 3–4.

### (Opsional) Bersihkan stok hantu cache yang sudah terlanjur ada
Kalau cache WIP-FIN over-report (stok hantu lama), jalankan reconcile (preview dulu):
```bash
php artisan inventory:reconcile-wipfin            # preview
php artisan inventory:reconcile-wipfin --apply    # apply (cache-only, ledger tak disentuh)
```
> Catatan: di mode **ledger**, daftar finishing sudah baca ledger jadi reconcile
> ini bersifat kosmetik (merapikan kolom cache). Tetap berguna sebelum Fase 4.

✅ **Checkpoint 4**: UNTAGGED = 0, kamu yakin ledger = kebenaran fisik.

---

## 5. FLIP toggle ke ledger (titik aktivasi)

> Mulai titik ini perilaku berubah: daftar Siap Jahit & Siap Finishing baca ledger.

1. Set di `.env` produksi:
   ```
   INVENTORY_READINESS_SOURCE=ledger
   ```
2. Refresh config:
   ```bash
   php artisan config:clear && php artisan config:cache
   ```
3. Verifikasi aktif:
   ```bash
   php artisan tinker --execute='echo config("inventory.readiness_source");'   # harus: ledger
   ```
4. **Smoke test UI**:
   - Buka **Sewing Pickup → create** & halaman bundles-ready: daftar muncul, qty wajar,
     tidak ada bundle yang stok fisiknya 0.
   - Buka **Finishing → create**: daftar per item+operator muncul; coba 1 setoran kecil
     di staging/sample lalu cek tersimpan benar (OK→WH-PRD, reject→REJECT, jurnal/HPP normal).
   - Buka **WIP-FIN Adjustment → create**: daftar bundle muncul.

✅ **Checkpoint 5**: mode ledger aktif, UI & posting normal.

### 🔴 ROLLBACK INSTAN (kapan saja setelah Langkah 5)
```
.env: INVENTORY_READINESS_SOURCE=cache
php artisan config:clear && php artisan config:cache
```
Semua jalur balik ke cache lama. Tidak perlu redeploy, tidak menyentuh data/migrasi.

---

## 6. Pantau log drift (minggu-minggu setelah flip)

> Indikator kesehatan + tolok ukur kesiapan Fase 4 (drop kolom cache).

Cari peringatan drift cache saat finishing:
```bash
grep "FASE3b" storage/logs/laravel.log
# atau jika pakai daily/stack channel:
grep -r "FASE3b" storage/logs/
```
Tiap baris memuat `bundle_id`, `job_id`, `item_id`, `cache_wip_qty`, `qty_used`, `shortfall`.

Interpretasi:
- **Nol selama beberapa minggu** → cache & ledger sejalan; **aman lanjut Fase 4**
  (lepas guard/reconcile + drop kolom cache `cut_wip_qty`/`wip_qty`/`sewing_picked_qty`).
- **Masih muncul** → ada leak hulu (posting yang tak memelihara cache dengan benar).
  Selidiki memakai data log; perbaiki sebelum buang kolom. Operasi tetap aman karena
  ledger yang menjaga.

(Opsional) jadwalkan `inventory:shadow-compare --since=<tanggal flip>` mingguan untuk
memantau divergensi pasca-cutover.

---

## Ringkasan urutan singkat

| # | Aksi | Reversibel? |
|---|------|-------------|
| 0 | Backup DB | — |
| 1 | Deploy kode, env=cache, config:cache | ya (redeploy) |
| 2 | migrate --path (tambah kolom tag) | ya (migrate:rollback) |
| 3 | backfill (preview → --apply) | tag = kolom nullable; aman |
| 4 | shadow-compare (read-only) + (opsional) reconcile-wipfin | read-only / cache-only |
| 5 | **flip env=ledger + config:cache** | **rollback instan ke cache** |
| 6 | pantau log FASE3b → syarat Fase 4 | — |

**Fase 4 (drop kolom cache) TIDAK termasuk runbook ini** — destruktif & ireversibel,
jalankan hanya setelah log drift nol + persetujuan + rencana migrasi terpisah.
