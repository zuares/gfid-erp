# Audit pesanan marketplace, shipment, dan piutang

Tanggal audit: 10 September 2026. Sumber: kode workspace `gfid-dev` dan SQLite lokal `database/database.sqlite`, dibaca menggunakan koneksi `mode=ro`. Angka berikut bukan verifikasi database produksi. Tidak ada transaksi, stok, tautan, atau jurnal yang diubah. Route aktif `/marketplace/orders` diverifikasi mengarah ke `MarketplaceController@orders`.

## Kesimpulan

Shipment sudah mencatat barang keluar dan HPP, tetapi belum menyediakan jejak yang lengkap dari barang keluar ke pesanan marketplace dan penerimaan uangnya. Perbaikan utama adalah rekonsiliasi identitas pesanan/resi, alokasi SKU dan kuantitas per pesanan, lalu pengakuan penjualan/piutang dengan tanggal kejadian yang konsisten. Shipment lama yang sudah posted tidak boleh diposting ulang untuk menambahkan tautan.

Ada masalah terpisah yang harus diprioritaskan: 188 pesanan terdapat dalam lebih dari satu posting penjualan aktif. Menambahkan jurnal piutang sebelum rekonsiliasi posting lama berisiko memperbesar pencatatan ganda.

## Temuan data

Scope shipment historis: `posted_at IS NOT NULL`, `cancelled_at IS NULL`; semua 49 shipment dalam kelompok ini berjenis `manual`, tanggal 4 Juli–29 Agustus 2026. Data draft pengujian dengan nama DUMMY dan wave posted dianalisis terpisah, tidak dimasukkan dalam angka historis ini.

| Pemeriksaan | Hasil lokal | Makna |
| --- | ---: | --- |
| Shipment historis posted | 49 | Seluruhnya `manual`, `store_id` kosong |
| Scan nomor pesanan/resi aktif | 2.228 | Seluruhnya tanpa `fulfillment_id`; jumlah scan, bukan jumlah order unik |
| Baris item shipment | 1.110 | Seluruhnya sudah memiliki `item_id` internal |
| Kuantitas scan barang | 4.519 unit | Kuantitas operasional; belum membuktikan semua pesanan tepat barang |
| Baris tanpa alokasi nomor scan | 948 / 4.006 unit | Tidak dapat ditelusuri langsung ke pesanan |
| Baris sudah punya nomor scan | 162 / 513 unit | Scan tersebut masih belum terhubung ke order marketplace |
| Jurnal HPP shipment aktif | 49 / Rp106.538.652,61 | Debit HPP sudah tercatat |
| HPP shipment Juli / Agustus | Rp46.423.153,67 / Rp60.115.498,94 | Perlu rekonsiliasi dengan periode pengakuan penjualan |
| Order marketplace lokal | 8.794 | Hanya 6 memiliki `shipping_awb_no` terisi |
| Booking lokal | 974 | 59 memiliki `tracking_number` terisi |
| Baris order tanpa kedua ID produk internal | 24 dari 11.265 | Mapping produk marketplace belum seluruhnya lengkap |
| Baris fulfillment tanpa produk | 8 | Delapan order item terkait sudah mempunyai `internal_item_id` |
| Transaksi pada tabel finance baru | 0 | Jangan menganggap subledger finance baru sudah mencatat piutang historis |

Pencocokan baca-saja dengan normalisasi huruf besar dan trim:

- Nomor scan dicocokkan ke order SN, external order ID, booking SN, dan AWB: 44 scan mempunyai satu kandidat order.
- Ditambah penghubung booking ke order berdasarkan toko dan nomor order/booking: 79 scan mempunyai satu kandidat, 2.149 belum mempunyai kandidat, tidak ditemukan kandidat ambigu pada indeks yang diperiksa.
- Angka 79 belum berarti aman ditautkan otomatis: masih perlu validasi status, SKU, qty, duplikasi scan/paket, dan bukti pengiriman. Data mentah payload lain, API langsung, serta arsip label belum ditelusuri seluruhnya.
- Banyak kode scan berawalan SPXI/JY, konsisten dengan kemungkinan scan resi. Minimnya AWB lokal menjelaskan mengapa pencocokan nomor saja sangat terbatas; tidak membuktikan order tersebut tidak ada di marketplace.

## Penjualan berulang pada jurnal aktif

Empat `marketplace_accounting_postings` berstatus posted memiliki jurnal yang belum void. Irisan dihitung dari `statement.orders[].order_id` pada snapshot posting, bukan dari tanggal order yang berubah setelah posting.

| Posting | Basis/periode | Irisan dengan posting #3 |
| --- | --- | ---: |
| #1 / jurnal 823 | Toko 5, ordered_at Juni | 26 order |
| #2 / jurnal 824 | Toko 4, ordered_at Juni | 124 order |
| #3 / jurnal 825 | Semua toko, settlement_time Juli | 188 order total |
| #4 / jurnal 830 | Semua toko, ordered_at Juli | 38 order |

Seluruh 188 order pada posting #3 juga muncul pada #1, #2, atau #4. Jurnal 825 mengkredit Penjualan Rp18.070.581 dan mendebit Clearing Rp14.572.855 sebelum transaksi wallet/iklan tambahan. Ini bukti cakupan order berulang pada posting penjualan aktif. Nilai koreksi final harus dihitung per komponen karena snapshot antarposting dapat berbeda, dan jurnal 825 juga membawa iklan serta top-up wallet; jangan membalik seluruh jurnal secara otomatis.

Kode `MarketplaceAccountingPostingService::post()` hanya menghindari pengulangan `scope_key` identik. Key memasukkan toko, basis tanggal, tanggal awal, dan tanggal akhir. Order yang sama pada scope berbeda belum dicegah oleh pengecekan tersebut.

## Temuan implementasi yang relevan

1. `ShipmentController::doPostShipment()` melakukan stock-out dan `postShipmentCogs()`. `JournalService::postShipmentCogsFromMutations()` memakai nilai aktual mutasi: Dr HPP / Cr Persediaan. Jalur wave mempunyai jurnal sendiri. Tidak ada jurnal piutang pada jalur shipment ini.
2. `shipmentMappingErrors()` langsung melewati validasi untuk shipment selain tipe marketplace. Data historis berjenis manual, sehingga tidak terlindungi oleh validasi alokasi yang sekarang berlaku untuk marketplace.
3. `autoLinkShipmentScans()` dan `linkRekonScan()` membatasi pekerjaan pada shipment draft. `syncScans()` masih mengembalikan pesan bahwa fitur belum diimplementasikan. Karena itu perbaikan tautan shipment posted membutuhkan jalur khusus yang hanya merekam rekonsiliasi.
4. Struktur relasi sudah ada: shipment line → scan → fulfillment → marketplace order; fulfillment line → marketplace order item. Fondasi ini dapat dipakai, tetapi alokasi satu baris batch ke beberapa order memerlukan rincian alokasi kuantitas yang menjumlah kembali ke qty asli.
5. `OrderFulfillmentService::createDraft()` mencari SKU dari `model_sku ?? item_sku` lewat `SkuMapping`; tidak mengutamakan `internal_item_id` yang sudah dimiliki order item. Fulfillment yang sudah ada langsung dikembalikan. Ini merupakan celah konsistensi mapping, selaras dengan delapan baris lokal yang tertinggal; penyebab historis setiap baris belum dibuktikan.
6. `MarketplaceAccountingPostingService` membukukan penjualan/fee/clearing dari laporan final dan mengecualikan HPP. Scope SHIPPED ditolak untuk posting, meski laporan dapat menampilkan piutang provisional. Nilai provisional pada laporan tidak sama dengan saldo piutang yang sudah dijurnal.
7. `MarketplaceFinancePostingService` mempunyai fungsi penjualan/piutang per transaksi, tetapi tidak ditemukan pemanggilan `postSale()`/`postEscrow()` di aplikasi selain deklarasi dalam penelusuran ini. Tabel transaksinya lokal masih kosong. Mengaktifkannya harus disertai migrasi dari posting laporan agar tidak ada dua sumber jurnal untuk penjualan yang sama.
8. `MarketplaceFinanceOrderBridgeService` mencari shipment melalui sales invoice. Jalur scan → fulfillment belum menjadi sumber tautan pada bridge tersebut; shipment historis tanpa toko/invoice tidak akan tertaut melalui jalur itu.
9. COA 1302 bernama **Saldo Marketplace / Clearing**, tetapi konfigurasi menggunakan key `marketplace_receivable`. Memakai akun yang sama untuk piutang belum released dan wallet siap ditarik akan menyulitkan rekonsiliasi saldo.

## Rancangan pencatatan yang disarankan

Prinsip pengakuan pendapatan: penjualan diakui ketika kendali barang berpindah ke pelanggan. Pengiriman fisik dan status API saja tidak selalu membuktikan perpindahan tersebut. Piutang merupakan hak pembayaran tanpa syarat selain berlalunya waktu; hak yang masih bersyarat setelah kewajiban dipenuhi dapat merupakan aset kontrak. Kerangka akuntansi dan syarat transaksi usaha perlu menentukan penerapan akhirnya. Rujukan: [IFRS 15, ringkasan resmi](https://www.ifrs.org/issued-standards/list-of-standards/ifrs-15-revenue-from-contracts-with-customers/) dan [IFRS 15 paragraf 105–108](https://www.ifrs.org/content/dam/ifrs/publications/pdf-standards/english/2024/issued/part-a/ifrs-15-revenue-from-contracts-with-customers.pdf?bypass=on).

Usulan berikut mengasumsikan kendali barang belum berpindah saat paket diserahkan ke kurir, lalu berpindah saat diterima/disetujui pembeli menurut syarat transaksi:

| Kejadian | Perlakuan yang diusulkan |
| --- | --- |
| Barang keluar gudang | Stok tersedia berkurang sekali. Dr Persediaan dalam pengiriman / Cr Persediaan barang jadi sebesar biaya historis; belum menjadi piutang hanya karena resi discan. |
| Kendali berpindah dan hak tagih timbul | Dr Piutang marketplace / Cr Penjualan sebesar hak pendapatan setelah diskon penjual; jika masih bersyarat, evaluasi aset kontrak. Dr HPP / Cr Persediaan dalam pengiriman sebesar biaya yang ditelusuri dari pengiriman. |
| Dana released ke wallet | Dr Saldo marketplace/clearing sebesar neto, Dr biaya marketplace sesuai komponen, Cr Piutang sebesar nilai yang dilunasi. Selisih refund/diskon/ongkir dipisahkan menurut substansinya. |
| Penarikan diterima bank | Dr Bank / Cr Saldo marketplace/clearing. |
| Gagal kirim, barang kembali sebelum penjualan diakui | Dr Persediaan barang jadi / Cr Persediaan dalam pengiriman, dengan penerimaan fisik. Barang hilang/rusak dan retur setelah penjualan memakai penanganan terpisah. |

Jika syarat transaksi menunjukkan kendali memang sudah berpindah saat pengiriman, pengakuan penjualan/piutang dan HPP dapat terjadi saat pengiriman itu. Pilih kebijakan berdasarkan bukti, lalu terapkan konsisten. Label operasional “estimasi dana pesanan dikirim” tetap berguna tanpa menyamakannya dengan piutang GL.

Contoh sederhana tanpa pajak/ongkir/retur: nilai jual setelah diskon penjual Rp100.000, biaya barang Rp40.000, fee Rp10.000. Saat penjualan diakui: piutang Rp100.000 dan HPP Rp40.000. Saat released: wallet Rp90.000 + beban fee Rp10.000 melunasi piutang Rp100.000. Saat payout: bank Rp90.000 menggantikan wallet Rp90.000.

Untuk data lama, HPP sudah dibukukan. Rekonsiliasi tautan tidak membuat stock-out maupun HPP lagi. Apabila audit cutoff menunjukkan HPP terlalu dini, buat koreksi terukur Dr Persediaan dalam pengiriman / Cr HPP untuk barang yang memang belum memenuhi pengakuan penjualan pada tanggal laporan. Jangan membalik seluruh HPP historis dan jangan memasukkan barang itu kembali ke stok tersedia gudang.

## Urutan perbaikan

1. Rekonsiliasi 188 order dalam posting berulang, termasuk fee dan wallet; siapkan daftar koreksi sebelum memposting piutang tambahan. Cegah satu kejadian penjualan per order dibukukan ulang melalui filter laporan berbeda atau engine finance lain.
2. Lengkapi identitas toko, order SN, AWB, booking, dan bila perlu package ID dari ekspor/API/arsip label. Pertahankan kode asli scan. Hasil pencocokan unik menjadi kandidat, bukan langsung bukti alokasi barang.
3. Bangun rekonsiliasi untuk shipment posted yang hanya menyimpan tautan/alokasi, asal bukti, pengguna, dan waktu. Kunci mutasi stok, qty asli, serta biaya historis. Tangani split shipment, beberapa paket per order, retur, dan scan duplikat.
4. Petakan SKU/varian marketplace ke barang internal; perbaiki konsistensi fulfillment. Jumlah alokasi per item/shipment tidak boleh melebihi qty historis. Jika bukti per pesanan tidak cukup, biarkan berstatus belum terekomendasi untuk pencatatan final, tanpa menebak pembagian batch.
5. Tambahkan ringkasan pada `marketplace/orders`: resi dan shipment, kelengkapan alokasi produk/qty, tanggal kendali berpindah, status jurnal penjualan, nilai piutang/aset kontrak, fee, released, payout, saldo terbuka, dan selisih. Status stok, status pengiriman, dan status akuntansi harus dibaca terpisah.
6. Gunakan satu sumber posting penjualan per order/kejadian. Tanggal jurnal mengikuti kejadian bisnis, bukan tanggal sync. Pisahkan akun piutang/aset kontrak dari clearing 1302. Bangun saldo awal terverifikasi dari transaksi lama, bukan menjurnal ulang seluruh order.
7. Tutup buku dengan rekonsiliasi persediaan dalam pengiriman, HPP terhadap penjualan, piutang per order terhadap GL, wallet terhadap laporan marketplace, dan payout terhadap bank.

## Batas verifikasi

Audit menggunakan SELECT terhadap data lokal, pemeriksaan snapshot/jurnal, dan penelusuran kode serta route aktif. Tidak menjalankan posting, sinkronisasi marketplace, migrasi, perubahan mapping, atau pembalikan jurnal. Tidak ada perubahan kode aplikasi; hanya laporan audit ini. Belum dilakukan konfirmasi fisik barang, pemeriksaan kontrak marketplace, atau pencocokan rekening bank/produksi. Nominal piutang yang benar belum dapat disimpulkan hanya dari jumlah scan shipment.
