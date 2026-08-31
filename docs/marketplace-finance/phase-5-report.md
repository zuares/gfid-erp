# Marketplace Finance — Phase 5 Report

Tanggal: 2026-08-28

## Scope

Menambahkan settlement sync finance baru untuk payout Shopee melalui `get_payout_info`, `get_payout_detail`, dan jalur withdrawal wallet melalui `get_wallet_transaction_list`. Data disimpan ke tabel finance baru tanpa menulis settlement atau payout legacy.

## Files changed

- `app/Services/Marketplace/Finance/MarketplaceSettlementNormalizer.php`
- `app/Services/Marketplace/Finance/MarketplaceSettlementSyncService.php`
- `tests/Feature/MarketplaceSettlementFinanceSyncTest.php`

Tidak ada migration baru pada phase ini karena header dan allocation sudah tersedia dari migration Phase 1.

## Behaviour

- Membuat satu settlement header per `store_id + channel + external_settlement_id`.
- Mendukung pagination payout info/detail dan menyimpan raw payload provider.
- Satu settlement dapat memiliki banyak allocation ke financial transaction yang berbeda.
- Allocation bersifat idempotent dan tidak dapat menggandakan order yang sama pada settlement yang sama.
- Partial allocation dilaporkan sebagai `unmatched` tanpa membuat transaction palsu ketika order/finance transaction belum terpetakan.
- Order yang sudah ada akan melalui order bridge; order tanpa settlement tetap tidak diubah.
- Identifier payout kosong ditolak dan tidak dibuatkan fallback identifier.
- Payout tanpa status valid disimpan sebagai `unknown`; status `received` hanya diberikan jika response memiliki status valid atau withdrawal wallet yang valid.
- Withdrawal wallet hanya menerima tipe withdrawal, sehingga mutasi iklan tidak masuk sebagai settlement finance.
- Sync berulang dan payout wallet yang sudah pernah di-import tetap memakai header yang sama.
- Tidak membuat journal, tidak mengubah journal existing, dan tidak menulis `marketplace_order_settlements` atau `marketplace_payouts` legacy.

## Verification

- Phase 5 + Phase 4 + Phase 3 + Phase 2 + schema tests: 20 test lulus, 143 assertions.
- Laravel Pint check file Phase 5: lulus.
- `git diff --check`: lulus.

## Risk / follow-up

1. Settlement journal dan bank/clearing posting sengaja ditahan untuk Phase 6.
2. `get_payout_info/detail` terutama relevan untuk payout detail; withdrawal wallet dipanggil melalui method terpisah agar caller dapat memilih sumber yang sesuai tipe toko/payout.
3. Settlement tanpa allocation tetap disimpan sebagai header untuk rekonsiliasi, tetapi belum dianggap selesai secara accounting.
