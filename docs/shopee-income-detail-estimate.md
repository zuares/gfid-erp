# Integrasi Shopee Income Detail untuk Estimasi Dana Cair

Tanggal implementasi: 26 Agustus 2026
Dokumentasi resmi: <https://open.shopee.com/documents/v2/v2.payment.get_income_detail?module=97&type=1>

## Tujuan

Mengambil estimasi dana yang akan diterima untuk order Shopee yang masih pending tanpa mengubah settlement final, laporan keuangan, atau jurnal.

Endpoint:

`GET /api/v2/payment/get_income_detail`

Untuk toko lokal, gunakan `income_status=2` (`Pending`). Nilai yang digunakan:

- `estimated_escrow_amount`: estimasi dana cair per order.
- `estimated_payout_time`: perkiraan waktu pencairan dalam Unix timestamp.
- `order_sn`: identitas order untuk dicocokkan dengan `(store_id, channel_order_id)`.
- `payment_method`, `status`, `currency`, dan `creation_date`: metadata pendukung.

`income_status=1` adalah data released, sedangkan toko cross-border juga dapat mempunyai `income_status=0` (`To Release`). Estimasi reguler project memakai status `2`.

## Kontrak request

| Parameter | Wajib | Nilai project |
| --- | --- | --- |
| `date_from` | Ya | Kemarin |
| `date_to` | Ya | Hari ini |
| `income_status` | Ya | `2` |
| `cursor` | Tidak | Kosong pada halaman pertama |
| `page_size` | Ya | Maksimal 100 |

Untuk status Pending/To Release, Shopee menampilkan seluruh record yang sedang berada pada status tersebut dan mengabaikan rentang tanggal. Tanggal tetap dikirim agar request memenuhi kontrak API.

Untuk status Released, rentang tanggal tidak boleh melebihi 14 hari dan `date_to` harus setelah `date_from`.

## Kontrak response

Struktur resmi:

```json
{
  "income_detail_list": {
    "list": [
      {
        "order_sn": "ORDER-123",
        "estimated_escrow_amount": 85000,
        "estimated_payout_time": 1787677200,
        "currency": "IDR",
        "status": "Pending"
      }
    ],
    "next_page": {
      "cursor": "NEXT-CURSOR",
      "page_size": 100
    }
  }
}
```

Parser project menerima bentuk resmi baik langsung pada root maupun di dalam `response`. Bentuk response lama tetap didukung untuk backward compatibility.

Pagination berhenti jika:

- cursor berikutnya kosong;
- cursor berikutnya sama dengan cursor saat ini;
- atau batas pengaman 100 halaman tercapai.

## Penyimpanan

Estimasi disimpan pada tabel `marketplace_order_income_estimates`, bukan pada `marketplace_order_settlements`.

Kunci:

- unique `marketplace_order_id`;
- unique `(store_id, channel_order_id)`.

Kolom utama:

- `income_status`;
- `estimated_escrow_amount`;
- `estimated_payout_at`;
- `payment_method`;
- `status_description`;
- `currency`;
- `source_created_at`;
- `synced_at`;
- `raw_json`.

Nilai nol adalah estimasi valid dan tidak boleh dianggap sebagai data kosong.

## Aturan finansial

Estimasi hanya untuk tampilan operasional:

- tidak mengisi `final_income`;
- tidak mengisi `settlement_time`;
- tidak mengubah `financial_data_status` menjadi ready;
- tidak masuk profit report final;
- tidak boleh diposting ke jurnal;
- otomatis tidak dipakai UI setelah settlement aktual mempunyai `settlement_time`.

Urutan prioritas tampilan pending:

1. `marketplace_order_income_estimates.estimated_escrow_amount` dari Shopee;
2. legacy `_income_detail.estimated_escrow_amount` selama masa transisi;
3. fallback manual 24% jika estimasi Shopee belum tersedia.

Setelah semua toko berhasil disinkronkan, fallback 24% sebaiknya ditampilkan dengan label jelas dan dievaluasi untuk dihapus.

## Menjalankan

Deploy schema terlebih dahulu:

```bash
php artisan migrate
```

Sinkron seluruh toko Shopee aktif:

```bash
php artisan marketplace:sync-income-details
```

Sinkron satu toko:

```bash
php artisan marketplace:sync-income-details --store=ID_TOKO
```

Scheduler project menjalankannya setiap empat jam pada menit `:15`, setelah settlement sync pada menit `:07`.

## Perilaku UI

- Filter **Belum Cair** menampilkan KPI `Estimasi Dana Cair`, bukan dana final.
- Filter tanggal pada mode Belum Cair memakai `estimated_payout_at` dari Shopee.
- Setiap order menampilkan sumber `EST. SHOPEE`, `EST. MANUAL 24%`, atau `ESTIMASI BELUM TERSEDIA`.
- Rincian fee settlement disembunyikan untuk order pending karena nilainya belum final.
- Waktu sinkronisasi estimasi terakhir ditampilkan untuk membantu menilai freshness data.
- Tombol **Perbarui Estimasi** tersedia setelah memilih satu toko Shopee dan memanggil endpoint internal:

```text
POST /api/marketplace/stores/{store}/sync-income-details
```

Endpoint internal selalu memakai `income_status = 2` dan tidak mengubah settlement final.

## Monitoring yang disarankan

Pantau per toko:

- jumlah record ditemukan;
- jumlah estimate baru/diperbarui;
- order yang tidak cocok;
- jumlah halaman;
- error API dan `request_id`;
- umur `synced_at`;
- persentase order pending yang sudah mempunyai estimasi resmi Shopee.

`unmatched > 0` berarti Income Detail menemukan order yang belum ada di `marketplace_orders`. Order sync perlu dijalankan atau window order perlu diperluas.

## Checklist UAT

1. Jalankan migration pada staging/backup database.
2. Jalankan command untuk satu toko.
3. Pastikan estimate dibuat tanpa row settlement baru.
4. Cocokkan beberapa nominal dengan Seller Center → Income Details → Pending.
5. Pastikan tanggal estimasi tampil dengan label `Est.`.
6. Pastikan order cair tetap memakai `final_income`, bukan nilai estimate lama.
7. Pastikan laporan finansial dan preview jurnal tidak berubah karena estimate.
8. Setelah lolos, aktifkan scheduler untuk semua toko.
