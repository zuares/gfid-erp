# Marketplace Finance — Phase 3 Report

Tanggal: 2026-08-28

## Scope

Membuat normalizer dan sync service escrow finance baru. Implementasi menggunakan `EscrowService` existing yang meneruskan request melalui `MarketplaceApiGateway` dan adapter Shopee. Jalur legacy `MarketplaceSyncService::syncSettlements()` tidak dipanggil karena menulis tabel legacy.

## Files changed

- `app/Services/Marketplace/Finance/MarketplaceEscrowNormalizer.php`
- `app/Services/Marketplace/Finance/MarketplaceEscrowSyncService.php`
- `tests/Feature/MarketplaceEscrowFinanceSyncTest.php`

## Behaviour

- Mendukung detail satu order, detail batch maksimal 50 order per request, dan pagination `get_escrow_list`.
- Input batch lebih besar dari 50 otomatis dipecah.
- Menyimpan raw payload tanpa metadata internal gateway, source hash, dan waktu sync.
- Membuat atau memperbarui satu financial transaction per `store_id + channel + order_sn`.
- Menyimpan komponen admin, service, transaction, affiliate, shipping adjustment, voucher, rebate, refund, dan other adjustment.
- Nilai `0` tetap disimpan jika field provider memang hadir.
- Adjustment negatif mempertahankan tanda dan diberi direction `credit`.
- Response kosong, order gagal, order tidak ditemukan, dan order yang hilang dari response batch dilaporkan tanpa membuat data escrow palsu.
- Order API yang tidak punya `MarketplaceOrder` tetap disimpan dengan `marketplace_order_id = null` agar dapat direkonsiliasi kemudian.
- Tidak membuat journal, invoice, shipment, atau menulis tabel legacy.

## Verification

- Phase 3 + Phase 2 + schema tests: 11 test lulus, 74 assertions.
- PHP lint dan formatter lulus untuk file baru.
- `git diff --check`: lulus.
- Existing `MarketplaceEscrowTest` tetap menjadi coverage adapter/UI legacy dan tidak diubah.

## Risk / follow-up

1. Component lama yang tidak muncul lagi pada response terbaru belum dihapus; hal ini menjaga jejak data dan perlu keputusan snapshot-vs-history sebelum reconciliation.
2. Service belum dihubungkan ke route/command/scheduler; wiring operasional ditahan untuk phase route/sync berikutnya agar tidak mengaktifkan dual posting legacy.
3. Income status masih tetap `pending`; transisi resmi akan dibuat pada Phase 4.
