# System Audit — ERP Business Intelligence

Tanggal audit: 16 Oktober 2026  
Scope: repository Laravel dan database lokal yang terhubung pada workspace ini. Audit bersifat read-only; tidak ada migration, sync marketplace, posting, reversal, atau perubahan kode aplikasi yang dijalankan.

## 1. Executive summary

Sistem adalah ERP Laravel 12 untuk manufaktur apparel dan penjualan omnichannel. Fondasi operasionalnya luas: purchasing, inventory, cutting, sewing, finishing, packing, payroll, costing/HPP, sales, accounting, marketplace order/settlement/payout, ads, import, webhook, API, MCP, dan beberapa dashboard.

Fondasi terkuat ada pada:

- `InventoryService` sebagai jalur mutasi stok terpusat dengan locking, reservasi, transfer, lot cost, dan jejak source.
- HPP snapshot melalui `HppService`, `ItemCostSnapshot`, dan `ProductionCostService`.
- Marketplace finance baru dengan escrow, income, settlement allocation, posting idempotent, reconciliation, closing, dan audit log.
- Pengamanan route melalui `auth`, `access:*`, `role:*`, Sanctum, machine ability, audit request, dan throttle.
- Test suite lintas accounting, marketplace finance, ads, inventory, production, payroll, MCP, dan security.

Risiko utama bukan ketiadaan modul, melainkan banyak representasi untuk fakta bisnis yang sama. Marketplace masih memiliki jalur legacy (`marketplace_orders`/`marketplace_order_settlements`/`sales_invoices`/`marketplace_payouts`) dan subledger baru (`marketplace_financial_transactions`/`marketplace_finance_settlements`). Database lokal menunjukkan subledger baru belum berisi transaksi, sedangkan empat aggregate posting sudah aktif. Karena itu, laporan profit atau journal belum boleh dianggap sebagai satu-satunya sumber kebenaran sebelum rekonsiliasi sumber dan cutoff disepakati.

Kesimpulan kesiapan BI: **siap untuk milestone data-quality dan semantic-layer**, belum siap untuk menyebut seluruh KPI profit sebagai actual atau otomatis menjadi dasar keputusan keuangan tanpa quality gate.

## 2. Evidence dan verifikasi

| Area | Evidence |
|---|---|
| Runtime | `composer.json`: PHP `^8.2`, Laravel Framework `^12.0`, Sanctum, Reverb, PhpSpreadsheet, Dompdf, Laravel MCP, Pest 4 |
| Route surface | `php artisan route:list --except-vendor --json`: 1.087 route |
| Test inventory | 125 file test: 103 Feature, 18 Unit, 1 JS, fixture dan Pest bootstrap |
| Migration state | `php artisan migrate:status`: semua migration sampai 15 Oktober 2026 ran; dua migration 16 Oktober 2026 masih Pending |
| Database snapshot | Read-only aggregate melalui `php artisan tinker`: tabel, status, kualitas, inventory, posting, dan overlap |
| Working tree | Sudah dirty sebelum audit; perubahan user dipertahankan dan tidak dijadikan objek implementasi audit |

Temuan yang berasal dari database lokal adalah snapshot pada tanggal audit, bukan klaim kondisi production. Temuan yang hanya berasal dari source code ditandai **confirmed by code**; hal yang membutuhkan statement marketplace, bank, kontrak, atau data production ditandai **blocked/unverified**.

## 3. Architecture map

```text
Marketplace APIs / CSV / webhooks
        -> channel adapters, import services, sync jobs
        -> marketplace_orders + order_items
        -> legacy settlement/income OR finance subledger baru
        -> profit / finance statement / reconciliation / journal posting

Purchasing -> purchase receipts -> inventory mutations/lots/stocks
Production -> cutting -> sewing -> finishing -> packing -> FG stock
Payroll    -> piecework periods/lines -> production cost -> HPP snapshots
Sales      -> invoices/shipments -> stock-out + HPP journal
Accounting -> journals/journal_lines -> P&L, cash basis, AP, payout

All domains -> route dashboards/reports/exports -> optional AI/MCP consumers
```

Laravel 12 memakai `bootstrap/app.php` untuk routing, middleware alias, command registration, dan sebagian scheduler. `routes/console.php` mengatur scheduler operasional marketplace, inventory, ads, queues, dan cleanup. Route web dipecah berdasarkan domain, tetapi total surface 1.087 route menunjukkan coupling dan regression risk cukup tinggi.

## 4. Module inventory

| Domain | Existing capability | Audit assessment |
|---|---|---|
| Master data | items, categories, suppliers, warehouses, barcodes, BOM, SKU mapping | Existing; identifiers masih memiliki legacy dan canonical variants |
| Purchasing | PO, request, receipt/QC, invoice, payment, returns, supplier mapping | Existing; perlu ikatan eksplisit ke inventory value dan AP reconciliation |
| Inventory | stock balance, mutation ledger, lot, allocation, transfer, opname, adjustment, WIP | Existing and centralised; current snapshot masih menemukan negative balances dan missing costs |
| Production | cutting, sewing pickup/return, finishing, packing, QC, repairs, WIP, production-order family | Partially consolidated; local DB saat audit memiliki data cutting/sewing tetapi tidak memiliki record finishing/production-order baru |
| Costing | RM, labour, stage cost, HPP snapshot, production cost period, BOM estimate | Existing; actual-vs-standard dan overhead/packaging belum lengkap |
| Payroll | piecework generators, rates, daily payroll, journals, dashboards | Existing; cross-period uniqueness and payment lifecycle need hardening |
| Sales | invoices, shipments, returns, item/channel/shipment reports, daily sales | Existing; marketplace sales has a parallel invoice path |
| Accounting | COA, journals, journal lines, P&L, cash basis, AP, payout, cash expense/receipt/transfer | Existing; posting source and period cutoff must be unified |
| Marketplace | order, fulfillment, settlement, income estimate, payout, reconciliation, finance closing | Broad coverage; multiple generations of models remain active |
| Ads | shop/campaign/item daily data, wallet costs, ROAS/ACOS/CTR/CVR/CPC/CPA, experiments, simulator | Existing and rich; attribution and credit semantics remain material limitations |
| BI/analytics | sales dashboard, marketplace analytics/cohort, profit report, financial statement, production/payroll/inventory dashboards | Existing; no single enterprise metric contract or cross-domain snapshot layer |
| AI/MCP | OpenAI agent, pricing/ROAS calculator, local ERP MCP stock tool | Existing; deterministic evidence contract must precede LLM insights |

## 5. Key architecture findings

### A. Marketplace has two accounting generations

`app/Models/MarketplaceOrder.php` exposes legacy fields and relations (`settlement`, `incomeEstimate`, `salesInvoice`-adjacent flows) while also exposing `financialTransactions`. The migration `2026_08_28_100000_create_marketplace_finance_tables.php` creates a newer bounded finance model with idempotent keys and allocation tables. Existing docs in `docs/marketplace-finance/phase-1-report.md` through `phase-10-report.md` confirm the new submodule was deliberately introduced without removing legacy tables.

This is a good migration strategy, but it means readers, posting services, and reports need an explicit source-of-truth policy. At audit time, the new `marketplace_financial_transactions` and `marketplace_finance_settlements` tables both contain zero local rows, while `marketplace_accounting_postings` contains four posted aggregates.

### B. Financial state is separated better than before, but not yet canonical

The current quality service distinguishes final, provisional, incomplete, not-applicable, and unknown states. `MarketplaceProfitOverviewService` deliberately separates actual settlement from Shopee income estimates and excludes incomplete HPP from profit eligibility. `MarketplaceFinancialStatementService` supports `ordered_at` and `settlement_time` date bases and explicitly reports wallet ads separately.

The remaining architecture issue is semantic drift over time. Existing posting snapshots contain `ad_cost_for_gl_source = ads_daily_fallback`, while current `MarketplaceFinancialStatementService` defines wallet actual as the accounting source. Historical snapshots are immutable evidence, but current readers may not reproduce their numbers. Every posted report therefore needs a versioned metric definition and source policy.

### C. Inventory is a true ledger, but negative and uncosted states are allowed

`InventoryService::stockIn()` and `stockOut()` update `inventory_stocks` under `lockForUpdate()` and write `inventory_mutations`. `LotCostService` maintains moving-average cost per lot and supports rebuild from mutations. The design supports traceability, but `allow_negative` exists for some materials and `LotCostService::consume()` can allow lot quantities to become negative while logging a warning. This is operationally useful for production, but BI must expose negative/uncosted inventory as data-quality states rather than silently valuing them as zero.

### D. Manufacturing is broad but has more than one process vocabulary

The repository contains both legacy stage tables (`cutting_jobs`, `sewing_pickups`, `finishing_jobs`, `packing_jobs`) and a newer `production_orders`/`production_activities`/`production_movements`/`production_receipts` family. The local database had 37 cutting jobs and 68 sewing pickups, but zero `finishing_jobs`, zero `production_orders`, zero `production_movements`, and zero `production_receipts` on audit. This does not prove production is absent in production, but it proves the local snapshot cannot support an end-to-end manufacturing KPI without a source-selection decision.

### E. Scheduling is active and API-heavy

`routes/console.php` runs order sync every five minutes, recent and historical settlement sync, income detail, release-time sync, finance sync, ads wallet sync, chats, product snapshots, returns, tracking, and multiple queue workers. `bootstrap/app.php` adds daily sales rebuild, inventory ADS rebuild, hourly/daily ads sync, CRM, and repair jobs.

The use of `withoutOverlapping`, locks, queues, retries, and throttles is a strength. The operating risk is observability: BI freshness must be derived from sync-run success, row counts, lag, and unmatched/error counts, not from the existence of a scheduler definition alone.

### F. Security boundaries are present but broad surface remains

`bootstrap/app.php` registers module access, role, service ability, machine audit, IP limit, and machine throttle middleware. Web and API route files consistently use `auth`, `access:*`, and `role:*` for sensitive areas. Existing API integration audit found stronger controls for Shopee than for several generic external integrations; OTP logging, cleartext `http://ip-api.com`, and direct external calls from controllers should remain security backlog items.

## 6. Current database snapshot

| Observation | Local value | Interpretation |
|---|---:|---|
| Marketplace orders | 49,181 | Large historical/operational population with mixed status vocabulary |
| Marketplace order items | 51,635 | Item grain exists, but many legacy rows lack canonical mapping fields |
| Legacy settlement rows | 7,819 | 3,943 have non-null settlement time |
| New finance transactions | 0 | New bounded finance sync/backfill is not populated locally |
| New finance settlements | 0 | Same conclusion for new settlement headers |
| Posted marketplace accounting postings | 4 | Aggregate snapshots are active and must be reconciled before new posting path |
| Journals / journal lines | 864 / 1,817 | Accounting history exists and is not replaceable by a BI rebuild |
| Inventory mutations / stocks | 2,772 / 236 | Ledger and balances exist |
| Ads daily / item daily | 259 / 968 | Ads analytics has historical facts locally |
| Ads wallet transactions | 570 | Cash-like ad mutations exist separately from Ads performance facts |

## 7. Severity summary

| Severity | Finding | Business consequence |
|---|---|---|
| Critical | Four posted marketplace scopes overlap; posting 3's 188 orders all overlap with other posted scopes | Potential duplicate revenue/fee/clearing coverage and unreliable GL-to-order tie-out |
| Critical | New finance subledger is empty while legacy reports/postings are active | Adding new posting or backfill without cutoff can double count |
| High | Canonical status/mapping coverage is incomplete; 47k+ local orders use legacy status values | Final KPI coverage can appear artificially low or exclude valid history |
| High | Current code and historical posting snapshot use different ad-cost source policies | Historical profitability is not reproducible from today’s reader without versioning |
| High | Negative stock/lot and missing mutation cost exist | Inventory value, COGS, and margin can be materially misstated |
| Medium | Legacy and new production process families coexist | Capacity, yield, WIP, and labour KPIs can double count or omit stages |
| Medium | 1,087 routes and high-frequency scheduled integrations | Regression and freshness monitoring complexity |
| Medium | AI agent and external integrations lack a universal evidence/metric contract | Unsupported explanations may be presented as business advice |

## 8. Audit conclusion

The ERP is a substantial operational system with enough reusable facts for an enterprise BI layer. The next investment should be data governance and reconciliation, not another dashboard surface. Implementation should begin only after approving canonical source rules, resolving posted-scope overlap, defining historical cutoff, and agreeing which local/production datasets are authoritative.

