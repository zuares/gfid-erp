# Marketplace Finance — Phase 2 Report

Tanggal: 2026-08-28

## Scope

Membuat order finance bridge dari `MarketplaceOrder`. Bridge memakai `updateOrCreate` dengan identity `store_id + channel + order_sn`, mengisi reference invoice/shipment jika ditemukan, dan tidak membuat journal.

## Files changed

- `app/Services/Marketplace/Finance/MarketplaceFinanceOrderBridgeService.php`
- `tests/Feature/MarketplaceFinanceOrderBridgeTest.php`

Phase 1 relationship files tetap menjadi dependency dan tidak diubah pada phase ini.

## Behaviour

- Channel diambil dari `Store -> Channel`, order SN memprioritaskan `channel_order_id` lalu fallback ke `external_order_id`.
- Invoice dicari dengan `store_id + channel + channel_order_no`.
- Shipment dicari berdasarkan `store_id + sales_invoice_id`.
- Invoice/shipment yang belum ada tidak menggagalkan bridge.
- Status escrow/income, net amount, raw payload, source hash, dan journal reference tidak pernah di-reset oleh retry bridge.
- Default state tetap `escrow=pending`, `income=pending`, `net_amount=0` sampai phase sync berikutnya.

## Verification

- Bridge test dan schema test: 6 test lulus, 36 assertions.
- PHP lint: lulus.
- `git diff --check`: lulus.
- Smoke test finance legacy: 18 test lulus, 4 gagal pada fixture existing yang menduplikasi akun `1304`; sama dengan baseline Phase 1 dan tidak disebabkan bridge.
- Tidak ada API call, journal posting, migration execution, atau perubahan tabel legacy.

## Risk / follow-up

1. Invoice legacy tanpa `channel` tidak otomatis dikaitkan; normalisasi fallback perlu diputuskan sebelum backfill.
2. Shipment tidak memiliki FK langsung ke `MarketplaceOrder`, sehingga bridge hanya dapat mengaitkannya melalui invoice.
3. Integrasi ke order sync harus dilakukan pada phase berikutnya dengan policy yang mencegah dual posting legacy.
