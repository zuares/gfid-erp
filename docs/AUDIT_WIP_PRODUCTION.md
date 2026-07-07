# Audit Produksi & WIP — Persiapan Fitur WIP Normalization / Cleanup

Tanggal: 7 Juli 2026
Status: **audit only — belum ada kode fitur baru.**
Prinsip yang dijaga: jangan hapus data, jangan update qty tanpa log, jangan stok
dobel, dan jangan bikin tabel/flow baru yang redundan bila yang lama sudah cukup.

Basis pemeriksaan: `routes/web/production.php`, `routes/web/inventory.php`,
controller & service produksi, model produksi, migration/skema aktual di
`database_dev.sqlite`, `ProductionFlowService`, `InventoryService`,
`JournalService`, serta view produksi.

---

## Ringkasan eksekutif

Kabar baik: **infrastruktur yang kamu butuhkan hampir semuanya sudah ada.**

- `inventory_mutations` = **ledger stok** (sumber kebenaran, punya `direction`,
  `unit_cost`, `total_cost`).
- `production_movements` = **movement log produksi** (dari/ke gudang, dari/ke
  status, operator, deadline, `created_by`, link ke `inventory_mutation_id`).
  Sudah dipakai `ProductionFlowService::move()`.
- `inventory_adjustments` + `inventory_adjustment_lines` = **record penyesuaian
  ber-approval** yang sudah punya `purpose='wip'`, `wip_stage`, `operator_id`,
  `reference_type/reference_id`, `cutting_job_bundle_id`, `qty_before/after/change`,
  `direction`, `created_by`, `approved_by`, `approved_at`.
- `wip_opname_periods` + `wip_opname_lines` = **scaffolding WIP opname** (saat ini
  scope cutting saja, dan belum menghasilkan movement/jurnal).

Artinya: field yang kamu usulkan untuk `wip_normalizations` / `wip_cleanup_lines`
**90% sudah tersedia** di tabel-tabel di atas. Rekomendasi utama: **kombinasi —
pakai ulang tabel existing + tambah sedikit kolom**, bukan bikin set tabel baru
paralel (itu justru bikin sumber WIP jadi makin banyak dan rawan dobel).

Tiga risiko struktural yang wajib disadari sebelum ngoding:

1. **WIP-CUT dicatat ganda**: di level bundle (`cutting_job_bundles.cut_wip_qty`)
   **dan** di level item (`inventory_stocks` gudang WIP-CUT). Keduanya bisa
   *drift*. Normalisasi harus lewat service yang menjaga dua-duanya sinkron.
2. **`inventory_stocks` tidak punya `ref_type`/`ref_id`**, padahal
   `WipAdjustmentController` jalur WIP-CUT mereferensikannya → kemungkinan besar
   **jalur itu error/dead** dan tidak boleh dijadikan pola.
3. **`wip_opname` approve menimpa `cut_wip_qty` langsung tanpa mutasi/jurnal** →
   melanggar "jangan update qty tanpa log" dan bikin nilai neraca WIP salah
   (lihat juga audit accounting sebelumnya).

---

## A. Flow produksi saat ini (cutting → WH-PRD)

Berdasarkan `ProductionFlowService::STATUSES`, model, dan `JournalService`:

```
Rencana ──► Cutting ──► WIP-CUT ──► (ambil jahit) ──► WIP-SEW ──► (setor jahit)
                                                              │
                                             ┌── OK ──────────┤
                                             │                └── Reject ─► REJ-SEW / Barang Cacat
                                             ▼
                                          WIP-FIN ──► (finishing) ──► FG / WH-PRD
                                             │                          │
                                             └── reject ─► Barang Cacat  └─► (transfer) ─► WH-RTS ─► Shipment/COGS
                                                             │
                                                      (repair/rework) ─► kembali ke WIP
```

Detail per tahap (gudang & pemicu):

| Tahap | Dari → Ke | Pemicu (kode) | Stok |
|---|---|---|---|
| Cutting | RM → WIP-CUT | `CuttingJobController` + `CuttingService` | `inventory_mutations` + bundle `cut_wip_qty`/`wip_qty` |
| QC Cutting | dalam WIP-CUT | `QcController` (+ `postCuttingWip`) | update `qty_qc_ok/reject` bundle |
| Ambil Jahit | WIP-CUT → WIP-SEW | `SewingPickupController` | `sewing_pickup_lines`, `sewing_picked_qty` bundle |
| Setor Jahit OK | WIP-SEW → WIP-FIN | `SewingReturnController` | `sewing_return_lines` |
| Setor Jahit Reject | WIP-SEW → REJ-SEW/Cacat | `SewingReturnController` | idem |
| Rework/Repair | Cacat → WIP | `SewingReturnController` / `FinishingRepairController` | `finishing_repairs` |
| Finishing | WIP-FIN → FG/WH-PRD | `FinishingJobController` | `finishing_job_lines`, `destination_warehouse_id` |
| Packing | WIP-PACK | `PackingJobController` | `packing_jobs` |
| Transfer ke RTS | WH-PRD → WH-RTS | stock request / transfer | `inventory_transfers` / `stock_requests` |
| Movement manual antar status | any → any | `ProductionFlowService::move()` | `production_movements` + `inventory_mutations` |

Gudang WIP yang ada: **WIP-CUT, WIP-SEW, WIP-FIN, WIP-PACK** (+ reject: REJ-CUT,
REJ-SEW, REJ-FIN, REJECT).

---

## B. Sumber data WIP (dari tabel apa)

**Kombinasi beberapa tabel — tidak tunggal.** Inilah akar kerumitan hanging WIP.

| Stage WIP | Sumber qty utama | Sumber sekunder / jejak |
|---|---|---|
| **WIP-CUT** | `cutting_job_bundles.cut_wip_qty` (per bundle, "kebal" hilir) | `inventory_stocks` (WIP-CUT, per item) + `inventory_mutations` |
| **WIP-SEW** | `inventory_stocks` (WIP-SEW, per item) | `sewing_pickup_lines` (qty_bundle vs qty_returned_*) |
| **WIP-FIN** | `inventory_stocks` (WIP-FIN, per item) | `finishing_job_lines` |
| **WIP-PACK** | `inventory_stocks` (WIP-PACK, per item) | `packing_jobs` |
| Progress antar tahap | — | `sewing_pickups/returns`, `qc_results`, `production_movements` |

Catatan penting:
- `cutting_job_bundles` menyimpan **tiga** angka: `wip_qty` (bisa ditimpa hilir),
  `cut_wip_qty` (stok WIP cutting murni), `sewing_picked_qty` (sudah ditarik jahit).
  Outstanding siap-jahit = `cut_wip_qty − sewing_picked_qty` (scope
  `CuttingJobBundle::readyForSewing`).
- `inventory_stocks` hanya `(warehouse_id, item_id, qty)` — **level item, bukan
  bundle**, dan **tanpa `ref_type/ref_id`**. Jadi WIP-CUT punya dua representasi
  (bundle vs item) yang bisa berbeda.
- `ProductionFlowService::stockTotalsByStatus()` menghitung WIP dashboard dari
  `inventory_stocks` per kode gudang.

➡️ **Untuk fitur normalisasi, "qty_system" harus jelas diambil dari mana per
stage**: WIP-CUT dari bundle, WIP-SEW/FIN/PACK dari `inventory_stocks`.

---

## C. Tracking operator & waktu

Sudah cukup lengkap, tersebar per tabel:

| Field diminta | Ada? | Lokasi |
|---|---|---|
| cutting_by | ✅ | `cutting_jobs.operator_id`, `.created_by`, `.updated_by`; `cutting_job_bundles.operator_id` |
| sewing_operator_id | ✅ | `sewing_pickups.operator_id`; `finishing_job_lines.sewing_operator_id` (+`sewing_operator_name`) |
| qc_by | ✅ | `qc_results.operator_id` |
| packing_by | ✅ | `packing_jobs.created_by`/`updated_by` |
| created_by | ✅ | cutting_jobs, finishing_jobs, packing_jobs, production_orders, inventory_adjustments, production_movements |
| approved_by | ✅ | `inventory_adjustments.approved_by`, `wip_opname_periods.approved_by` |
| process_date | ⚠️ sebagian | ada `date`/`qc_date`/`processed_at`, **tidak ada kolom "tanggal proses asli" khusus** untuk normalisasi |
| completed_at | ⚠️ | `finishing_jobs.posted_at`, `cutting_job_bundles.wip_posted_at`; belum seragam |
| returned_at | ⚠️ | pakai `sewing_returns.date` (bukan timestamp khusus) |
| checked_at | ✅ | `qc_results.qc_date`; `finishing_job_lines.processed_at` |

Gap: **belum ada satu kolom "process_date / original process date" yang eksplisit
untuk kebutuhan normalisasi**, dan beberapa timestamp masih tersebar. Untuk
normalisasi WIP legacy (barang lama yang tanggal aslinya perlu dicatat manual),
kolom `process_date` perlu ditambahkan di level baris normalisasi.

---

## D. Movement log / audit trail

| Kebutuhan log | Sudah ada? | Tabel |
|---|---|---|
| WIP masuk / keluar | ✅ | `inventory_mutations` (`direction` in/out, `total_cost`) |
| Pindah lokasi WIP | ✅ | `production_movements` (from/to warehouse+status) + mutasi |
| Reject | ✅ | `qc_results`, `sewing_return_lines`, mutasi ke REJ-*/Cacat |
| Repair | ⚠️ | `finishing_repairs` (0 baris, jarang dipakai) |
| Finished → WH-PRD | ✅ | `finishing_jobs.destination_warehouse_id` + mutasi |
| Adjustment | ✅ | `inventory_adjustments` + `inventory_adjustment_lines` (ber-approval) |
| Write-off | ⚠️ | secara teknis bisa via `inventory_adjustments`, **tapi belum ada action/label khusus write-off & belum berjurnal** |

Kesimpulan D: **audit trail stok sudah kuat** (ledger + movement + adjustment).
Yang belum: (1) **konsep "action" bertingkat** untuk cleanup (keep/move/finish/
repair/reject/writeoff/link/legacy), (2) `production_movements` belum di-wire ke
semua tahap (baru dipakai `move()` manual), (3) beberapa adjustment WIP **belum
menghasilkan jurnal** (temuan audit accounting sebelumnya).

---

## E. Celah WIP menggantung (dengan query kandidat konkret)

Semua bisa dideteksi dari kolom yang ADA sekarang:

1. **Cut sudah siap, belum ditarik jahit** (WIP-CUT outstanding)
   `cutting_job_bundles` where `cut_wip_qty > 0` AND `sewing_picked_qty < cut_wip_qty`.
2. **Sudah ditarik jahit, belum lengkap disetor** (WIP-SEW menggantung)
   `sewing_pickup_lines` where `qty_bundle > (qty_returned_ok + qty_returned_reject)`
   AND `voided_at IS NULL`.
3. **Residu item-level di WIP-SEW/FIN/PACK**
   `inventory_stocks` JOIN `warehouses` where `code IN ('WIP-SEW','WIP-FIN','WIP-PACK')`
   AND `qty > 0.01`.
4. **QC pending menua**
   `qc_results` where `status='pending'` (atau umur `qc_date` > N hari).
5. **Drift WIP-CUT (hantu)**
   `inventory_stocks` WIP-CUT per item vs `SUM(cutting_job_bundles.cut_wip_qty)`
   per item → selisih = stok item tanpa bundle padanan.
6. **WIP tanpa operator / tanpa bundle**
   bundle/pickup dengan `operator_id IS NULL`; `inventory_stocks` WIP tanpa
   bundle terkait; `production_movements` `cutting_job_bundle_id IS NULL`.
7. **Barang selesai tapi masih di WIP**
   qty di WIP-FIN/PACK padahal `finishing_jobs`/`packing_jobs` terkait sudah
   `posted`.
8. **Legacy pra-tracking**
   `inventory_stocks` WIP dengan qty>0 tanpa bundle & tanpa `production_movements`
   dan dibuat sebelum tanggal go-live tracking.

➡️ Ini menjadi dasar **query kandidat "WIP menggantung"** untuk halaman preview
(langkah 3 di rencana implementasi).

---

## F. Rekomendasi desain fitur

### F.1 WIP Normalization (opname WIP)
Perluas konsep `wip_opname_*` yang sudah ada (jangan bikin baru):

- Header periode + baris hitung fisik (sudah ada di `wip_opname_periods/lines`).
- Per baris minimal: `item_id`, `qty_system` (dari sumber sesuai stage — lihat B),
  `qty_physical`, `wip_stage`/`from_location`, **proses terakhir**, `operator_id`,
  **`process_date` (tanggal proses asli, kolom baru)**, `reason`, `notes`.
- Perluas `scope` dari cutting-only ke semua stage WIP (SEW/FIN/PACK).
- **Saat approve → JANGAN timpa qty mentah.** Buat `inventory_adjustment`
  (`purpose='wip_normalization'`) + baris selisih → `InventoryService::adjustByDifference`
  (menulis `inventory_mutations`) + `production_movements` + jurnal via
  `JournalService` (Dr/Cr 1202 vs 6115 Selisih Stock Opname). Baru setelah itu
  qty bundle/stock mengikuti mutasi.

### F.2 WIP Cleanup (tutup WIP menggantung)
- **List** dari query kandidat E (halaman preview, read-only dulu).
- **Action per baris** → tiap action = movement/adjustment berjenis, **wajib
  `reason`**, action besar **wajib approval owner/admin**:

  | Action | Efek | Vehicle |
  |---|---|---|
  | Keep Open | tidak ada perubahan, hanya catatan | log ringan |
  | Move Location | pindah gudang WIP | `ProductionFlowService::move()` |
  | Mark as Finished / WH-PRD | WIP → FG/WH-PRD | pola `postFinishingJob` / `move()` + jurnal |
  | Send to Repair | WIP/Cacat → WIP repair | `finishing_repairs` + movement |
  | Mark Reject | WIP → REJ-*/Cacat | adjustment + jurnal (6120) |
  | Write Off | fisik hilang → keluar WIP | `inventory_adjustment` + jurnal (6120/6115) |
  | Link to Batch/Bundle | tautkan stok item ke bundle | update ref + movement (tanpa ubah qty) |
  | Close as Legacy | tandai legacy, keluar dari WIP aktif | adjustment + jurnal (6116) + flag legacy |

- **Data lama tetap tersimpan**: tidak menghapus baris; cukup tandai
  `status`/`is_legacy` supaya tidak muncul lagi sebagai WIP aktif.

---

## G. Rekomendasi database — **KOMBINASI (reuse + sedikit tambah)**

**Jangan** bikin set tabel paralel penuh (`wip_normalizations`,
`wip_cleanup_batches`, dst) dari nol — itu menambah sumber WIP baru dan melanggar
prinsip #6 (redundan). Sebaliknya:

**Pakai ulang:**
- `inventory_mutations` → engine stok (jangan pernah tulis qty langsung).
- `production_movements` → log perpindahan (sudah punya operator/date/from-to/
  created_by/mutation link). Tambah `source_type`/`source_id` bila perlu menaut
  ke record cleanup.
- `inventory_adjustments` + `_lines` → record ber-approval untuk normalisasi &
  cleanup (sudah punya operator, bundle, wip_stage, reference, approver).
- `wip_opname_periods` + `_lines` → header/baris opname WIP (perluas scope).

**Tambah kolom minimal (aditif) — bukan tabel baru:**

Pada `inventory_adjustments`:
```
action           varchar  NULL   -- keep|move|finish|repair|reject|writeoff|link|legacy|normalize
process_date     date     NULL   -- tanggal proses asli (untuk legacy)
from_location_id fk warehouses NULL
to_location_id   fk warehouses NULL
is_legacy        boolean  default 0
```
Pada `inventory_adjustment_lines` (kalau perlu granular per baris):
```
action           varchar NULL
process_date     date    NULL
```
Pada `wip_opname_lines` (untuk normalisasi kaya-konteks):
```
wip_stage        varchar NULL
operator_id      fk employees NULL
process_date     date    NULL
reason           varchar NULL
```
Dan pastikan hasil approve menaut ke movement: simpan `inventory_mutation_id` /
`production_movement_id` sebagai `movement_log_id`.

**Tabel baru hanya bila benar-benar perlu**: kalau workflow cleanup (satu batch
banyak baris dengan action berbeda + approval) tidak nyaman dipetakan ke
`inventory_adjustments`, boleh tambah **satu** header tipis `wip_cleanup_batches`
+ `wip_cleanup_lines` yang **tetap men-generate** `inventory_adjustments` /
`production_movements` di belakang (bukan menyimpan stok sendiri). Prinsipnya:
**tabel cleanup = workflow/UI, stok tetap di ledger existing.**

Pemetaan field usulanmu → existing:

| Usulan | Sudah ada di |
|---|---|
| item_id, qty_system/physical/adjusted | `inventory_adjustment_lines` (qty_before/after/change) + opname line |
| from_location/to_location | tambah `from_location_id`/`to_location_id` |
| action | **kolom baru** `action` |
| operator_id | `inventory_adjustments.operator_id` |
| process_date | **kolom baru** `process_date` |
| reason, notes, status | `inventory_adjustments.reason/notes/status` |
| created_by, approved_by, approved_at | `inventory_adjustments.*` (sudah ada) |
| source_type, source_id | `inventory_adjustments.reference_type/reference_id` |
| movement_log_id | link ke `production_movements.id` / `inventory_mutations.id` |

---

## H. Risiko implementasi

| Risiko | Akar masalah di kode | Cara cegah |
|---|---|---|
| **Stok dobel** | WIP-CUT ada di bundle **dan** `inventory_stocks`; `move()`/`adjustByDifference` vs update bundle manual | Semua perubahan lewat `InventoryService` (+ domain updater bundle), **tidak** tulis `inventory_stocks`/`cut_wip_qty` mentah |
| **Histori hilang** | `wip_opname.approve` menimpa `cut_wip_qty` tanpa mutasi | Selalu buat `inventory_adjustment` + `inventory_mutations` + `production_movements`; jangan hard-update |
| **HPP rusak** | qty tanpa `total_cost`; WIP-CUT jalur `ref_type/ref_id` yang kolomnya tak ada | Nilai dari `LotCostService`/`unit_cost`; blokir movement bernilai 0 (fallback standar cost + review) |
| **WIP aktif tertutup padahal masih proses** | tidak ada pembeda aged vs in-progress | Kandidat cleanup pakai umur + status; action besar wajib approval owner/admin |
| **Operator tracking tak lengkap** | legacy tanpa `operator_id`/bundle | Wajibkan `operator_id`+`process_date` di baris normalisasi; untuk legacy tandai eksplisit |
| **Data legacy tercampur** | tidak ada penanda legacy | `is_legacy`/status khusus; laporan WIP aktif memfilter legacy |
| **Jurnal salah/dobel** | sebagian adjustment WIP belum berjurnal; `JournalService` idempotent per (source_type,source_id) | Reuse `JournalService` + akun baru (1204/6115/6116/6120 dari fase lalu); satu source unik per aksi |

---

## I. Rencana implementasi paling aman (step-by-step)

1. **Backup** DB (`database_dev.sqlite`) + tag git sebelum mulai.
2. **Audit tabel** (dokumen ini) — konfirmasi sumber qty per stage (B) & kandidat (E).
3. **Query kandidat WIP menggantung** — tulis sebagai read-only query/scope
   (pakai definisi di bagian E), belum ada aksi.
4. **Halaman preview** (read-only) — daftar WIP menggantung + sumbernya, tanpa
   tombol aksi. Validasi angkanya cocok dengan dashboard `ProductionFlowService`.
5. **Draft normalisasi** — perluas `wip_opname` ke semua stage + kolom
   `process_date`/`operator`/`reason`; input fisik disimpan sebagai **draft**,
   belum mengubah stok.
6. **Approval** — owner/admin menyetujui; status draft → approved. Action besar
   (write-off, reject, close legacy, mark finished) wajib approval.
7. **Generate movement** — saat approve, buat `inventory_adjustment`
   (`purpose='wip_normalization'`/`'wip_cleanup'`, isi `action`) → panggil
   `InventoryService::adjustByDifference` / `ProductionFlowService::move()` →
   tulis `production_movements` → `JournalService` (akun 1202/6115/6120/6116).
   **Tidak ada** penulisan qty langsung.
8. **Validasi stok** — setelah generate, cek: (a) `inventory_stocks` = Σ mutasi,
   (b) bundle `cut_wip_qty` sinkron, (c) jurnal balance & tidak dobel
   (`ProductionJournalAuditService` bisa dipakai untuk cek).
9. **Test manual** — di data dev: satu contoh tiap action (move, finish, reject,
   writeoff, legacy) + cek histori tersimpan & WIP aktif berkurang benar.
10. **Aktifkan di produksi** — setelah migrasi akun (fase lalu) & fitur lulus uji;
    rollout bertahap, mulai dari WIP-CUT (paling banyak menggantung).

---

## Catatan penutup

- **Tidak menghapus data**: semua aksi = tambah record (adjustment/movement/jurnal)
  + penanda status/legacy. Baris lama tetap ada.
- **Tidak update qty tanpa log**: satu-satunya jalan ubah stok = `InventoryService`
  (menulis `inventory_mutations`).
- **Tidak redundan**: fitur baru = lapisan workflow/UI di atas
  `inventory_adjustments` + `production_movements` + `wip_opname` yang sudah ada.
- **Belum ada kode fitur** yang ditulis. Setelah kamu setujui pilihan DB (G) dan
  daftar action (F.2), implementasi bisa jalan mengikuti urutan I.
