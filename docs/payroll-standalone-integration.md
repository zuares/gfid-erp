# Integrasi payroll ke Akuntansi Baru

Implementasi dan migrasi lokal: 4 November 2026. Tidak membuat saldo awal, tidak mengubah jurnal lama, periode/baris payroll, mutasi stok, potongan pinjaman, maupun transaksi tabungan. Ledger baru masih dalam tahap integrasi modul; saldonya belum merupakan saldo lengkap perusahaan.

## Perilaku transaksi

- `PieceworkPayrollPostingService` memanggil `StandalonePayrollPostingService` di dalam transaksi database finalize dan pembayaran. Jika mapping, keseimbangan, atau rekonsiliasi gagal, seluruh perubahan dibatalkan.
- Akrual cutting/sewing tetap mengikuti rekonsiliasi sumber upah produksi pada rentang tanggal periode. Hanya selisih dicatat pada WIP Cutting `1-151-01` atau WIP Sewing `1-151-02`. Sumber produksi yang belum masuk ledger baru disalin melalui service produksi yang sama, dengan identitas sumber yang sama; sumber yang sudah ada diperiksa dan digunakan kembali.
- Gaji harian menggunakan beban `6-180`; hutang upah `2-130`. Piutang pinjaman karyawan `1308` dipetakan ke `1-135`; tabungan `2104` ke `2-140`. Akun kas/bank mengikuti mapping yang sudah tersedia. Mapping kustom tidak ditimpa oleh migration payroll.
- Reklasifikasi payroll harian historis disalin apa adanya, termasuk akun WIP asalnya, agar tidak mengakui beban dua kali.
- Referensi payroll `SA-PAYROLL-J{legacy_journal_id}` unik. Period lock dan pemeriksaan sumber aktif mencegah pencatatan ganda. Unpost me-VOID jurnal baru dan menyimpan reversal audit yang juga VOID, sesuai konvensi laporan aplikasi. Jurnal sumber produksi tetap dimiliki modul produksi.
- Unpost tetap membersihkan mirror lama meskipun flag payroll kemudian dimatikan. Finalize/pay ulang setelah unpost menggunakan jurnal sumber baru dan identitas repost.
- Finalize baru dan pembayaran baru mensyaratkan akrual = total payroll; selisih satu sen pun diposting. Backfill histori mempertahankan nilai sumber dan melaporkan selisih yang sudah ada, bukan melakukan koreksi tanpa bukti.

## Konfigurasi dan migration

`ACCOUNTING_STANDALONE_DUAL_POSTING_PAYROLL_ENABLED` default `true`. Mapping WIP tahap ada di `accounting.standalone_payroll_wip_accounts`.

Migration payroll: `2026_11_04_000001_add_standalone_payroll_accounts.php`. Pada database lokal, migration mapping pendahulunya `2026_11_03_000003_add_opening_legacy_account_mappings.php` juga dijalankan sesuai urutan. Keduanya hanya menyiapkan akun/mapping, tidak mem-posting saldo awal. Migration pending lain tidak dijalankan.

Rollback migration payroll mempertahankan master akun dan mapping, agar histori dan mapping kustom tidak terhapus. Untuk menonaktifkan posting baru gunakan flag; jangan menghapus ledger secara langsung.

## Migrasi histori

```sh
php artisan accounting:backfill-payroll --dry-run --json
php artisan accounting:backfill-payroll --post --json
php artisan accounting:backfill-payroll --period=51 --dry-run
php artisan accounting:backfill-payroll --module=daily --dry-run
```

Default adalah pratinjau tanpa perubahan. `--post` memvalidasi seluruh kandidat sebelum mem-posting batch dalam satu transaksi. Bila satu kandidat tidak valid, tidak ada kandidat yang diposting. Sumber yang sudah ada harus cocok akun, debit/kredit, tanggal, dan identitasnya. Backfill ditolak bila bertabrakan dengan saldo awal yang sudah diposting pada/setelah tanggal sumber. Jurnal manual tanpa hubungan periode tidak ditebak atau dimigrasikan.

Hasil lokal: 26 periode, 416 jurnal unik = 21 akrual payroll + 26 pembayaran + 1 reklasifikasi + 368 jurnal upah produksi pendukung. Debit = kredit = Rp126.646.713,60. Pratinjau sesudah migrasi: 416 sudah diposting, 0 kandidat baru. Saldo awal tetap 0 jurnal.

Backup sebelum perubahan: `storage/app/private/payroll-before-integration-20261104.sqlite`. Laporan JSON: `storage/app/private/payroll-integration-20261104/{preview,post,after}.json`.

### Selisih historis yang perlu ditelusuri

Selisih = akrual sumber dikurangi total payroll. Seluruh pembayaran sesuai total payroll; berikut selisih akrual yang sudah ada pada sumber lama:

| Periode | Modul | Selisih |
|---|---|---:|
| 20 | sewing | Rp1,00 |
| 21 | cutting | Rp16.002,00 |
| 33 | sewing | -Rp45.000,00 |
| 43 | sewing | Rp2,00 |

Jurnal koreksi manual lama #1127, `manual_payroll_correction`, tidak memiliki source ID/periode. Tidak dimigrasikan otomatis. Telusuri bukti, hubungan periode, dan potensi pengaruhnya ke selisih di atas sebelum membuat koreksi terpisah. Jangan membuat jurnal penyeimbang hanya agar selisih nol.

Data histori memiliki rentang periode yang saling bersinggungan. Backfill menggunakan identitas sumber unik sehingga jurnal produksi yang dipakai lebih dari satu periode tidak disalin dua kali. Alokasi sumber upah masih mengikuti metode rentang tanggal sistem lama; perubahan metode tersebut bukan bagian integrasi ini.

Saat saldo awal disusun nanti, transaksi yang sudah ada di ledger baru harus direkonsiliasi dahulu. Jangan menambahkan saldo awal yang memasukkan kembali transaksi historis yang telah dimigrasikan. Modul pencairan pinjaman dan penarikan tabungan perlu diintegrasikan dalam tahap modulnya sendiri; pekerjaan ini mencakup potongan/bonus yang terjadi melalui payroll.

## Tweak halaman terkait

- `/payroll/` kini mengarah ke `/payroll/dashboard` dan tetap memakai autentikasi serta akses payroll.
- Detail `/payroll/piecework/{module}/{period}` dan `/payroll/daily/{period}` menampilkan status posting, jurnal terkait, jumlah sumber produksi yang sudah cocok, gaji bruto, potongan pinjaman, bonus tabungan, pembayaran bersih, dan selisih historis.
- Tautan jurnal hanya ditampilkan untuk admin/owner; akses halaman jurnal tetap dijaga middleware Akuntansi Baru.
- Konfirmasi finalize dan pesan hasil tidak lagi menyebut Dr HPP 5101 untuk rekonsiliasi WIP.
- Pemeriksaan visual dilakukan pada hasil render Blade dengan data contoh di database SQLite memori, desktop dan lebar 390px. Tidak membuat login bypass atau mengubah autentikasi aplikasi lokal.

## Verifikasi

Tes terfokus meliputi cutting, sewing, harian, akrual nol/negatif/satu sen, potongan pinjaman, bonus tabungan, mapping tidak aktif, rollback kedua ledger, perubahan sumber setelah finalize, unpost/repost, klik ulang, histori/reklasifikasi, pratinjau tanpa write, batch atomik, identitas mirror, dan benturan saldo awal.

Suite terkait dijalankan dengan flag default pilot produksi untuk mengisolasi tes dari `.env` lokal:

```sh
ACCOUNTING_STANDALONE_PRODUCTION_AUDIT_REQUIRED=false \
ACCOUNTING_STANDALONE_DUAL_POSTING_PRODUCTION_CUTTING_ENABLED=false \
ACCOUNTING_STANDALONE_DUAL_POSTING_PRODUCTION_SHIPMENT_ENABLED=false \
php artisan test --compact tests/Feature/Accounting tests/Feature/Payroll tests/Feature/StandaloneAccounting
```

Tes pilot yang memerlukan flag aktif mengaturnya sendiri. Tanpa isolasi tersebut, tiga assertion `ProductionAccountingAuditTest` mengharapkan PASS tetapi mendapat REVIEW karena pilot lokal aktif.

Hasil akhir: **187 tes lulus, 1.053 assertions**. Pemeriksaan sintaks PHP dan `git diff --check` lulus. Perbandingan tabel terhadap backup menggunakan `EXCEPT` dua arah memastikan periode/baris payroll, jurnal/baris jurnal lama, pembayaran pinjaman, transaksi tabungan, dan mutasi stok tidak berubah. Pratinjau terakhir tetap 416 jurnal cocok, 0 kandidat baru, 0 error.
