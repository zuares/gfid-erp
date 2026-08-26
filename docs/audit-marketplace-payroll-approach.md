# Audit dan Rekomendasi Pencatatan Marketplace & Payroll

Tanggal audit: 26 Agustus 2026
Lingkup: source code Laravel, migration, model, service, route, test terkait, serta pemeriksaan konsistensi database SQLite lokal. Audit database hanya memakai statistik/agregat; data pribadi pelanggan dan karyawan tidak ditampilkan.

## 1. Kesimpulan singkat

Fondasi marketplace project ini sudah cukup kuat: order, item, settlement, payout, quality gate, posting jurnal, closing, dan audit log sudah tersedia. Masalah utamanya bukan kekurangan fitur, melainkan terlalu banyak representasi untuk fakta bisnis yang sama.

Pendekatan terbaik:

1. Jadikan `marketplace_orders` dan `marketplace_order_items` satu-satunya sumber kebenaran order marketplace.
2. Jadikan `marketplace_order_settlements` hanya berisi fakta settlement aktual; status "belum cair" diturunkan dari order yang belum memiliki settlement final, bukan dibuat sebagai settlement semu.
3. Gunakan `marketplace_payouts` hanya untuk perpindahan saldo marketplace ke bank.
4. Jangan lagi membangun jalur marketplace paralel di `sales_invoices`. Jika invoice marketplace tetap dibutuhkan, jadikan hasil turunan yang memiliki referensi unik ke order, bukan sumber data kedua.
5. Satukan payroll tetap dan borongan dalam satu payroll run/slip, tetapi pertahankan komponen perhitungan terpisah dan dapat diaudit.
6. Setiap upah borongan harus menunjuk ke transaksi produksi asal dan tarif yang dibekukan pada tanggal pekerjaan diterima.
7. Payroll yang sudah final tidak boleh diubah; koreksi dibuat sebagai adjustment atau void/reversal.

Ada blocker payroll yang perlu diperbaiki sebelum pembayaran riil: atribut pembayaran dan jurnal pada `PieceworkPayrollPeriod` tidak mass-assignable, sehingga pemanggilan `update()` mengabaikannya. Dampaknya, periode dapat tidak tercatat sudah dibayar dan jurnal pembayaran berisiko dibuat lebih dari sekali.

## 2. Snapshot kondisi database lokal

### Marketplace

| Pemeriksaan | Hasil |
| --- | ---: |
| Order | 3.718 |
| Item order | 4.468 |
| Settlement | 3.226 |
| Settlement dengan waktu cair | 3.117 |
| Settlement pending | 109 |
| Order tanpa settlement | 492 |
| Order tanpa item | 8 |
| Duplikasi `(store_id, channel_order_id)` saat ini | 0 |
| Item dengan mapping dan HPP lengkap | 4.445 |
| Item dengan status mapping/cost belum terisi | 23 |
| Perbedaan `total_amount` vs `total_paid_customer` | 1 order |

Semua 3.718 order masih menyimpan `financial_data_status = unknown`, dan semua 3.226 settlement masih menyimpan `data_status = unknown`.

Audit quality gate secara dry-run menghasilkan:

| Status hasil hitung | Jumlah |
| --- | ---: |
| Ready | 3.117 |
| Incomplete | 11 |
| Not applicable | 590 |
| Settlement lengkap | 3.117 |
| Settlement tidak lengkap/absen | 601 |

Sebelas order incomplete seluruhnya disebabkan settlement data yang belum lengkap. Artinya data dasarnya mayoritas sehat, tetapi cache/status kualitas belum diperbarui. Laporan profit dan posting jurnal memakai status tersimpan `ready`, sehingga kondisi ini dapat membuat laporan kosong atau posting terblokir.

### Payroll

| Pemeriksaan | Hasil |
| --- | ---: |
| Karyawan | 10 |
| Karyawan variable/borongan | 10 |
| Karyawan fixed | 0 |
| Piece rate | 23 |
| Rate cutting | 10 |
| Rate sewing | 13 |
| Pasangan rate dengan masa berlaku tumpang tindih | 1 |
| Payroll period pada database lokal | 0 |
| Test otomatis khusus payroll | 0 |

Schema employee sudah mempunyai `payment_type` dan `weekly_fixed_salary`, tetapi keduanya belum dipakai oleh generator, service, route, dashboard, slip, maupun posting payroll. Jadi gaji tetap baru tersedia sebagai data master, belum menjadi fitur payroll.

## 3. Temuan prioritas

### P0 — hentikan penggunaan pembayaran payroll sampai diperbaiki

`PieceworkPayrollPeriod::$fillable` tidak mencakup:

- `total_amount`
- `finalized_at`, `finalized_by`
- `payable_account_id`, `accrual_journal_id`
- `paid_at`, `paid_by`, `paid_from_account_id`, `payment_journal_id`

Generator dan posting service mengisi field tersebut melalui `update()`. Pemeriksaan runtime membuktikan atribut-atribut itu dibuang oleh mass assignment. `status = final` tetap tersimpan karena termasuk fillable, tetapi tanda bayar dan link jurnal tidak tersimpan. Pemanggilan pay berikutnya dapat lolos lagi dan membuat jurnal pembayaran baru.

Tindakan minimum:

- Tambahkan field yang sah ke model atau gunakan `forceFill()` secara eksplisit pada service yang terkontrol.
- Setelah posting jurnal, simpan marker pembayaran dalam transaksi database yang sama.
- Tambahkan unique/idempotency guard pada journal `(source_type, source_id)` untuk sumber pembayaran payroll.
- Tambahkan test: pay dua kali hanya menghasilkan satu journal.
- Sebelum fix dirilis, blokir tombol/route pay pada environment operasional.

### P0 — pekerjaan yang sama dapat masuk ke dua periode payroll

Controller hanya mencari periode dengan tanggal awal dan akhir yang persis sama. Periode yang saling tumpang tindih tetap boleh dibuat. Payroll line juga tidak menyimpan ID transaksi produksi asal. Akibatnya qty yang sama dapat dihitung kembali pada periode lain tanpa bisa dideteksi database.

Tindakan minimum:

- Tolak payroll run yang periodenya overlap untuk jenis run yang sama.
- Simpan `source_type` dan `source_id` pada payroll line.
- Buat unique constraint untuk sumber borongan yang sudah dibayar, misalnya `(component_code, source_type, source_id)`.

### P1 — status kualitas marketplace belum sinkron dengan fakta

Quality service sudah benar secara konsep dan dry-run menunjukkan 3.117 order siap. Namun status persistennya seluruhnya masih `unknown`.

Tindakan minimum:

- Jalankan refresh non-dry-run setelah backup dan review hasil dry-run.
- Jalankan refresh pada setiap perubahan order, item mapping/HPP, dan settlement.
- Tambahkan scheduled reconciliation harian sebagai safety net.
- Dashboard harus menampilkan umur `financial_checked_at` agar status basi terlihat.

### P1 — order belum dilindungi unique constraint di database

Service memakai `updateOrCreate(['store_id', 'channel_order_id'])`, tetapi tabel hanya memiliki index biasa pada `channel_order_id`. Dua worker paralel masih bisa melakukan insert bersamaan. Data sekarang tidak duplikat, tetapi jaminan tersebut baru ada pada kode, bukan database.

Tambahkan unique constraint `(store_id, channel_order_id)` setelah preflight duplikasi. Tambahkan juga unique key item `(marketplace_order_id, external_item_id, external_model_id, line_no)`.

### P1 — settlement final bercampur dengan placeholder

Satu bagian settlement sync dengan benar menolak membuat row nol ketika API belum memberikan fakta final. Namun order sync tetap membuat placeholder di tabel settlement untuk order yang belum final. Dua arti berbeda berada dalam tabel yang sama.

Rekomendasi:

- Tabel settlement hanya untuk data aktual dari endpoint settlement/income.
- "Belum cair" = order eligible yang tidak memiliki settlement final.
- Bila estimasi diperlukan, simpan sebagai `estimated_payout`/metadata order dengan label `estimate`; jangan menaruhnya pada `final_income` atau struktur settlement aktual.

### P1 — rate efektif payroll dihitung dengan tanggal akhir periode

Generator mengagregasi seluruh qty satu periode, lalu mencari rate menggunakan tanggal akhir periode. Bila tarif berubah di tengah periode, semua pekerjaan dapat memakai tarif terakhir. Generator juga menduplikasi resolver sendiri dan tidak konsisten memakai `PieceRateService`.

Rekomendasi:

- Tentukan rate berdasarkan tanggal setiap transaksi QC/return yang diterima.
- Bekukan `rate_snapshot` pada payroll line.
- Gunakan satu resolver tarif saja.
- Tolak rate nol; jangan membuat line amount nol secara diam-diam.
- Larang masa berlaku rate yang overlap. Database lokal saat ini memiliki satu overlap rate cutting kategori.

### P1 — `sales_invoices` adalah jalur marketplace paralel

Ada import order dan income marketplace ke `sales_invoices`, sementara alur aktif memakai `marketplace_orders` dan settlement. Database lokal belum mempunyai sales invoice, sehingga sekarang adalah waktu yang tepat untuk menyederhanakannya tanpa migrasi data besar.

Keputusan yang disarankan:

- `sales_invoices`: penjualan manual/POS/non-marketplace saja.
- `marketplace_orders`: seluruh transaksi marketplace.
- Bila dibutuhkan invoice pajak/dokumen penjualan, generate sebagai projection dengan `source_type` dan `source_id` unik dari marketplace order.

### P2 — akurasi uang masih bergantung pada PHP float

Kolom database sudah decimal, tetapi banyak perhitungan service mengubahnya menjadi float. Untuk IDR, gunakan integer rupiah atau decimal money utility secara konsisten. Pembulatan hanya dilakukan pada boundary yang jelas, terutama saat membuat jurnal.

### P2 — histori payroll belum cukup immutable

Foreign key employee pada payroll line memakai cascade delete. Penghapusan employee dapat menghapus histori payroll. Master employee dan rate sebaiknya dinonaktifkan, bukan dihapus setelah pernah dipakai.

## 4. Arsitektur marketplace yang disarankan

### Sumber kebenaran

| Fakta | Sumber kebenaran | Catatan |
| --- | --- | --- |
| Identitas/order/status provider | `marketplace_orders` | Unik per toko + nomor order |
| Barang dan qty | `marketplace_order_items` | Snapshot nama, SKU, harga, HPP |
| Dana final per order | `marketplace_order_settlements` | Hanya fakta aktual, satu per order |
| Transfer saldo ke bank | `marketplace_payouts` | Bisa menggabungkan banyak order |
| Jurnal | `journals` + `journal_lines` | Hanya dibuat dari dokumen final |
| Payload dan perubahan | raw payload + event/audit log | Append-only untuk jejak audit |

`sales_invoices` tidak ikut dalam alur marketplace utama.

### Status yang perlu dipisahkan

Jangan gunakan satu status untuk semua tujuan:

- `provider_status`: status asli Shopee/TikTok.
- `fulfillment_status`: status proses internal seperti packed/ready-to-handover.
- `financial_status`: `not_applicable`, `pending`, `ready`, `posted`, `closed`, `issue`.

Kolom legacy (`status`, `order_status`, `order_date`, `ordered_at`, `total_paid_customer`, `total_amount`) dapat dipertahankan sementara selama migrasi, tetapi semua reader baru harus memakai kolom canonical. Setelah tidak ada reader legacy, hentikan dual-write lalu hapus bertahap.

### Alur sederhana

1. API/webhook masuk dan di-upsert idempotently berdasarkan `(store_id, channel_order_id)`.
2. Item di-upsert berdasarkan identitas line provider; mapping SKU dan HPP disnapshot.
3. Order yang belum selesai tetap operasional dan tidak masuk laporan profit final.
4. Saat settlement aktual tersedia, simpan nominal dan payload sumber, lalu jalankan quality gate.
5. Order `ready` masuk subledger finansial.
6. Posting settlement membuat piutang marketplace, penjualan, retur/diskon, serta fee.
7. Payout wallet membuat Dr Bank / Cr Piutang Marketplace.
8. Closing mengunci periode; koreksi setelah closing harus melalui reopen + audit log.

### Definisi angka

- Penjualan barang berasal dari total line item setelah diskon barang, bukan otomatis dari total uang yang dibayar pembeli.
- Ongkir pembeli, subsidi ongkir, pajak, seller discount, fee, refund, dan adjustment harus punya kategori terpisah.
- `final_income` adalah payout aktual per order dan tidak boleh dipakai sebagai gross sales.
- HPP = `qty × hpp_snapshot`; snapshot tidak berubah ketika HPP master berubah.
- Payout ke bank tidak menciptakan pendapatan baru; hanya memindahkan piutang marketplace ke bank.

Rekonsiliasi per toko/periode:

`saldo awal piutang marketplace + settlement yang diposting - payout bank = saldo akhir piutang marketplace`

## 5. Arsitektur payroll yang disarankan

### Model minimum

1. `employees` — identitas dan status kerja, tanpa mengunci karyawan hanya ke fixed atau variable.
2. `employee_salary_rules` — gaji tetap, basis mingguan/bulanan, tanggal efektif.
3. `piece_rates` — tarif borongan efektif per module/item/kategori.
4. `payroll_runs` — periode dan lifecycle draft/final/paid.
5. `payroll_slips` — satu baris per karyawan per run.
6. `payroll_lines` — komponen fixed, piecework, lembur, tunjangan, bonus, potongan, adjustment; setiap line menyimpan qty/rate/amount snapshot dan sumbernya.
7. `payroll_payments` — pembayaran dan alokasi ke slip; mendukung pembayaran sebagian bila kelak dibutuhkan.

`payment_type` sebaiknya menjadi hasil dari aturan aktif, bukan enum tunggal pada employee. Seorang karyawan dapat mempunyai gaji tetap sekaligus insentif borongan.

### Formula

Untuk borongan:

`amount = accepted_qty × rate_yang_berlaku_pada_tanggal_pekerjaan`

Qty hanya berasal dari event yang telah diterima/QC OK. Reject dan rework harus memiliki kebijakan eksplisit.

Untuk gaji tetap mingguan:

`fixed_amount = weekly_salary_snapshot`

Jika ada prorata:

`fixed_amount = weekly_salary_snapshot × hari_berhak_dibayar / hari_kerja_terjadwal`

Jangan memakai jumlah hari kalender secara implisit. Simpan divisor dan hasil snapshot pada line.

Untuk slip:

`gross = fixed + piecework + overtime + allowances + bonus`

`net = gross - absence_deduction - loan - statutory_deductions - other_deductions`

Komponen pajak/BPJS dan aturan ketenagakerjaan harus effective-dated dan dapat dikonfigurasi; jangan hardcode tarif ke service tanpa versi kebijakan yang jelas.

### Lifecycle

`draft -> calculated -> approved/final -> partially_paid -> paid`

Aturan:

- Draft boleh regenerate.
- Final bersifat immutable.
- Pay hanya boleh terhadap slip/run final.
- Setiap jurnal memiliki idempotency key unik.
- Koreksi final dibuat sebagai adjustment run atau void + reversal, bukan edit line lama.

### Posting akuntansi

- Borongan produksi langsung: Dr WIP tahap terkait / Cr Hutang Upah.
- Gaji tetap produksi: Dr WIP atau Overhead Produksi sesuai kebijakan biaya / Cr Hutang Gaji.
- Gaji admin/operasional: Dr Beban Gaji / Cr Hutang Gaji.
- Pembayaran: Dr Hutang Gaji/Upah / Cr Kas-Bank.

Mapping akun sebaiknya berdasarkan komponen dan cost center, bukan hardcode satu akun untuk semua karyawan.

## 6. Roadmap implementasi

### Tahap 0 — pengamanan

- Blokir pembayaran payroll sampai mass-assignment dan idempotency diperbaiki.
- Tambahkan test finalize/pay dua kali.
- Bersihkan satu overlap piece rate.
- Jalankan marketplace quality refresh setelah backup dan approval.
- Tambahkan alert bila status quality belum diperiksa atau sudah basi.

### Tahap 1 — satu sumber order

- Tambahkan unique constraint order dan item dengan preflight.
- Tetapkan `marketplace_orders` sebagai canonical.
- Nonaktifkan import marketplace ke `sales_invoices`.
- Pisahkan placeholder/estimate dari settlement aktual.
- Dokumentasikan kamus status dan komponen finansial.

### Tahap 2 — payroll terpadu

- Tambahkan salary rule effective-dated.
- Buat payroll run, slip, line sumber, dan payment.
- Migrasikan generator cutting/sewing ke satu calculator.
- Rate dihitung per event date dan dibekukan.
- Tambahkan fixed dan hybrid compensation.

### Tahap 3 — rekonsiliasi dan closing

- Hubungkan settlement posting dengan payout clearing.
- Dashboard saldo piutang marketplace per toko.
- Approval/final/void payroll dengan audit log.
- Closing bulanan dan laporan exception.

## 7. Acceptance criteria

Marketplace dianggap aman bila:

- Tidak mungkin ada dua order untuk toko + nomor order yang sama.
- Sync yang dijalankan berulang menghasilkan data dan total yang sama.
- Tidak ada settlement final yang berasal dari placeholder.
- Semua order completed berada pada `ready` atau mempunyai alasan issue yang eksplisit.
- Total subledger settlement dikurangi payout sama dengan saldo akun piutang marketplace.
- Order closed tidak dapat berubah tanpa reopen dan audit log.

Payroll dianggap aman bila:

- Satu transaksi produksi tidak dapat dibayar dua kali.
- Periode overlap ditolak.
- Rate nol atau rate overlap memblokir finalisasi.
- Rate yang dipakai sesuai tanggal pekerjaan, bukan tanggal generate.
- Total run = jumlah slip = jumlah payroll line.
- Net pay = earnings - deductions untuk setiap slip.
- Finalize/pay yang dipanggil dua kali tetap hanya membuat satu jurnal.
- Employee/rate yang pernah dipakai tidak dapat dihapus dari histori.
- Gaji fixed, borongan, dan hybrid dapat muncul pada satu slip yang sama.

## 8. Verifikasi audit

Pemeriksaan yang dijalankan:

- SQLite `PRAGMA quick_check`: `ok`.
- Marketplace quality audit: dry-run, tanpa mutasi data.
- 15 test terkait quality, profit report, financial statement, marketplace accounting posting, dan payout bulk posting: seluruhnya lulus dengan 73 assertions.
- Belum ada automated test payroll; ini harus menjadi bagian dari Tahap 0.
