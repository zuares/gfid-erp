# Marketplace Finance — Phase 1 Report

Tanggal: 2026-08-28

## Scope

Phase 1 hanya menambahkan bounded submodule schema, model, enum status, dan relationship. Tidak ada service sync, posting journal, route, UI, backfill, atau perubahan pada tabel legacy.

## Files changed

- `database/migrations/2026_08_28_100000_create_marketplace_finance_tables.php`
- `app/Domain/Marketplace/Finance/Enums/EscrowStatus.php`
- `app/Domain/Marketplace/Finance/Enums/IncomeStatus.php`
- `app/Domain/Marketplace/Finance/Enums/SettlementStatus.php`
- `app/Domain/Marketplace/Finance/Enums/ComponentDirection.php`
- `app/Models/MarketplaceFinancialTransaction.php`
- `app/Models/MarketplaceFinancialComponent.php`
- `app/Models/MarketplaceFinanceSettlement.php`
- `app/Models/MarketplaceFinanceSettlementAllocation.php`
- `app/Models/MarketplaceOrder.php`
- `app/Models/SalesInvoice.php`
- `app/Models/Shipment.php`
- `app/Models/Account.php`
- `tests/Feature/Database/MarketplaceFinanceSchemaTest.php`

## Migration design

Four tabel baru dibuat:

- `marketplace_financial_transactions`
- `marketplace_financial_components`
- `marketplace_finance_settlements`
- `marketplace_finance_settlement_allocations`

External key transaction menggunakan `store_id + channel + order_sn`. Settlement menggunakan `store_id + channel + external_settlement_id`. Component memakai `dedupe_key` non-null yang dihasilkan aplikasi agar unique tetap efektif ketika provider tidak mengirim line ID.

FK ke record operasional dan journal bersifat nullable dengan `nullOnDelete()` untuk menjaga data finance tetap terbaca. Child finance memakai cascade hanya terhadap parent finance baru. Tidak ada data sample dan tidak ada operasi destructive.

## Verification

- PHP lint: lulus untuk seluruh file baru dan model yang diubah.
- `MarketplaceFinanceSchemaTest`: 3 test lulus, 13 assertions.
- Smoke test finance legacy: 21 test lulus, 4 gagal pada fixture existing yang mencoba insert akun `1304` yang sudah dibuat oleh migration existing `2026_08_27_000002_create_marketplace_ad_wallet_account.php`.
- `git diff --check`: lulus.
- `php artisan migrate --pretend`: SQL migration baru berhasil dihasilkan.
- Migration belum dijalankan pada database development aktif.

## Risiko tersisa

1. Rollout Phase 2–6 harus memisahkan journal legacy dari event finance baru untuk mencegah dual posting.
2. Mapping tanda fee, voucher, rebate, refund, dan adjustment harus dipastikan di normalizer sebelum accounting posting.
3. Akun Piutang Shopee, fee, dan bank tetap harus berasal dari config/mapping existing; Phase 1 tidak mengubah COA.
