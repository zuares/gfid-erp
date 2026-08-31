# Marketplace Finance — Phase 7 Report

Tanggal: 2026-08-28

## Scope

Menambahkan reconciliation service read-only untuk bounded Marketplace Finance. Phase ini mengikuti spesifikasi asli: route dan UI ditahan untuk Phase 8.

## Files changed

- `app/Services/Marketplace/Finance/MarketplaceFinanceReconciliationService.php`
- `tests/Feature/MarketplaceFinanceReconciliationServiceTest.php`

Tidak ada migration baru dan tidak ada perubahan pada tabel legacy.

## Behaviour

- Menghasilkan status `matched`, `mismatch`, `unmatched`, dan `pending` per finance transaction serta ringkasan settlement.
- Membandingkan gross sales invoice, escrow gross, total component, expected net income, income status, settlement allocation, received amount, dan journal amount.
- Mendukung filter store, tanggal berdasarkan `created_at`/`released_at`, status reconciliation, dan order SN.
- Menyimpan alasan diagnostik minimal: `missing_sales_invoice`, `missing_shipment`, `missing_escrow`, `missing_income`, `missing_settlement`, `fee_mismatch`, `amount_mismatch`, `duplicate_response`, `journal_missing`, `journal_duplicate`, dan `order_unmatched`.
- Settlement tanpa allocation dilaporkan sebagai `settlement_without_allocation` tanpa membuat transaction atau journal.
- Duplicate component response dan active journal duplicate dapat ditandai untuk audit.
- Reconciliation sepenuhnya read-only dan tidak melakukan auto-posting, update status, atau membuat journal.

## Verification

- Phase 7 + Phase 6 + Phase 5 + Phase 4 + Phase 3 + Phase 2 + schema tests: 29 test lulus, 192 assertions.
- Laravel Pint check file Phase 7: lulus.
- `git diff --check`: lulus.

## Risk / follow-up

1. Route, controller, command, scheduler, dan UI finance belum diaktifkan; itu menjadi Phase 8.
2. Reconciliation transaction-level memakai allocation sebagai pembagian received amount untuk settlement multi-order, sementara settlement-level tetap menampilkan total header dan unallocated amount.
3. Reconciliation tidak memperbaiki data; mismatch harus ditindaklanjuti melalui sync/backfill atau koreksi terotorisasi.
