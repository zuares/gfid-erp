# Marketplace Finance — Phase 9

## Status

Selesai dan siap review.

## Implementasi

- Menambahkan service `MarketplaceFinanceBackfillService` untuk membaca:
  - `marketplace_order_settlements`
  - `marketplace_order_income_estimates`
  - `mp_incomes`
  - `marketplace_payouts`
- Menambahkan command `marketplace:finance-backfill`.
- Default command adalah dry-run; gunakan `--apply` untuk menulis ke tabel finance baru.
- Mendukung filter `--source` dan `--store_id`.
- Transaction memakai key `store_id + channel + order_sn`.
- Component memakai dedupe key stabil berbasis sumber legacy, ID, order, kode, dan field.
- Settlement memakai key `store_id + channel + external_settlement_id`.
- Backfill tidak menyalin `journal_id` legacy dan tidak memanggil posting service.
- Summary mencakup scanned, created, updated, unchanged, unmatched, duplicate, error, serta jumlah transaction/component/settlement/allocation.

## Risiko dan mitigasi

- Data legacy tanpa store/channel/order mapping dilaporkan sebagai unmatched dan tidak dibuatkan fakta finance palsu.
- Payout tanpa detail order tetap dapat menjadi settlement header, tetapi allocation yang tidak dapat dipetakan dilaporkan unmatched.
- Backfill hanya mengisi field finance yang kosong atau status yang lebih maju; state finance/journal yang sudah ada tidak di-reset.
- Data legacy tetap dipertahankan dan tidak dihapus.

## Verifikasi

- Dry-run tidak menulis transaction, component, settlement, allocation, atau journal.
- Apply menghasilkan satu transaction per order, komponen legacy, settlement, dan allocation yang valid.
- Apply kedua tetap idempotent tanpa duplikasi.
- Test unmatched payout lulus.
