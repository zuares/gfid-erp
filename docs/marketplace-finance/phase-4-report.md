# Marketplace Finance — Phase 4 Report

Tanggal: 2026-08-28

## Scope

Menambahkan income sync finance baru berbasis `get_income_detail` existing melalui `MarketplaceApiGateway`. Jalur ini terpisah dari `MarketplaceSyncService::syncIncomeDetails()` legacy agar tidak menulis tabel estimasi lama dan tidak mengubah settlement final.

## Files changed

- `database/migrations/2026_08_28_110000_add_income_metadata_to_marketplace_financial_transactions.php`
- `app/Models/MarketplaceFinancialTransaction.php`
- `app/Services/Marketplace/Finance/MarketplaceIncomeNormalizer.php`
- `app/Services/Marketplace/Finance/MarketplaceIncomeSyncService.php`
- `tests/Feature/MarketplaceIncomeFinanceSyncTest.php`

## Behaviour

- Memakai adapter/gateway existing untuk `get_income_detail`, termasuk cursor pagination maksimal 100 halaman.
- Menormalisasi status Shopee menjadi `pending`, `to_release`, `released`, atau `unknown`.
- Status yang sudah maju tidak diturunkan oleh response lama; status `unknown` tidak menimpa status known yang sudah ada.
- Menyimpan metadata income pada kolom terpisah: `income_source_hash`, `income_raw_payload`, dan `income_synced_at`.
- Memakai order bridge existing untuk mengaitkan transaction ke `MarketplaceOrder`, serta menyimpan order yang belum cocok untuk rekonsiliasi.
- Hanya mengubah state income, metadata income, dan `released_at` ketika status released memiliki waktu pencairan.
- Tidak mengubah gross/net escrow, escrow status, component, source hash/raw escrow, invoice, shipment, atau journal.
- Retry dan duplicate response tetap memakai satu transaction berdasarkan `store_id + channel + order_sn`.

## Verification

- Phase 4 + Phase 3 + Phase 2 + schema tests: 16 test lulus, 114 assertions.
- PHP lint seluruh file finance baru: lulus.
- Laravel Pint check untuk file Phase 4: lulus.
- `git diff --check`: lulus.

## Risk / follow-up

1. Wiring route/command/scheduler dan otorisasi operasional tetap ditahan untuk phase berikutnya.
2. Mapping integer Shopee mengikuti kontrak project existing: `2 = Pending`, `0 = To Release`, `1 = Released`; string status dari response juga diterima.
3. Income sync belum membuat jurnal; posting baru boleh dilakukan pada phase journal dengan idempotency key yang terpisah.
