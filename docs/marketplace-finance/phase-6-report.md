# Marketplace Finance — Phase 6 Report

Tanggal: 2026-08-28

## Scope

Menambahkan accounting posting terpusat untuk event `SALE_POSTED`, `ESCROW_FINALIZED`, dan `SETTLEMENT_RECEIVED` menggunakan `JournalService` existing.

## Files changed

- `app/Services/Accounting/JournalService.php`
- `app/Services/Marketplace/Finance/MarketplaceFinancePostingService.php`
- `tests/Feature/MarketplaceFinancePostingServiceTest.php`

Tidak ada migration baru pada phase ini; relasi journal sudah tersedia pada tabel finance Phase 1.

## Behaviour

- `SALE_POSTED`: Dr akun marketplace receivable dari config, Cr akun sales dari config.
- `ESCROW_FINALIZED`: memetakan setiap fee/adjustment component ke akun config atau akun component yang sudah ditetapkan, dengan lawan marketplace receivable.
- `SETTLEMENT_RECEIVED`: Dr akun bank settlement yang aktif dan bertipe cash, Cr marketplace receivable.
- Source key idempotent:
  - `marketplace_sale + financial_transaction_id`
  - `marketplace_escrow + financial_transaction_id`
  - `marketplace_settlement + settlement_id`
- Setiap posting memakai `lockForUpdate` pada sumber finance dan mengembalikan journal existing jika event aktif sudah pernah diposting.
- Reversal memakai `JournalService::voidBySource()` sehingga journal asli dan reversal menjadi audit trail; posting ulang setelah reversal membuat journal aktif baru.
- Posting settlement diblokir jika status belum `received`, nominal tidak valid, akun bank kosong/tidak aktif/non-cash, atau mapping COA tidak tersedia.
- HPP tidak dibuat oleh Marketplace Finance; sumber HPP tetap shipment.
- Tidak mengubah nama atau saldo historis akun dan tidak membuat journal melalui `Journal::create` langsung.

## Verification

- Phase 6 + Phase 5 + Phase 4 + Phase 3 + Phase 2 + schema tests: 25 test lulus, 164 assertions.
- PHP lint file posting dan `JournalService`: lulus.
- `git diff --check`: lulus.
- Perubahan legacy `JournalService` dibatasi pada tiga source constant marketplace.

## Risk / follow-up

1. Posting belum dihubungkan ke route/command/scheduler; wiring operasional tetap menunggu Phase 7.
2. Mapping default marketplace receivable mengikuti config existing (`MARKETPLACE_ACCOUNT_RECEIVABLE`); bila COA Piutang Shopee dan Clearing hendak dipisahkan, perlu keputusan mapping dan approval migration/config terpisah.
3. Reconciliation matched/mismatch/pending/unmatched menjadi scope Phase 7.
