# Data Quality Audit — Financial Integrity and Reconciliation

Tanggal audit: 16 Oktober 2026. Semua angka database di bawah adalah aggregate lokal read-only; tidak ada data customer, employee, credential, atau raw secret yang dicantumkan.

## 1. Executive conclusion

Data quality tooling sudah lebih maju daripada rata-rata aplikasi operasional: ada raw payload, sync log, status kualitas settlement/order/item, issue reason, audit log, source hash, idempotency key, closing, dan reconciliation service. Namun kualitas belum konsisten pada seluruh histori karena:

1. model legacy dan canonical hidup bersamaan;
2. status marketplace tidak seragam;
3. beberapa fakta keuangan belum memiliki satu grain canonical;
4. posting aggregate yang sudah ada overlap;
5. inventory dapat menyimpan negative/uncosted state;
6. historical snapshots belum membawa versi definisi metrik secara lengkap.

## 2. Observed database evidence

| Check | Result lokal | Severity |
|---|---:|---|
| `marketplace_orders` | 49,181 | Informational |
| `marketplace_order_items` | 51,635 | Informational |
| Order status canonical `COMPLETED` | 435 | High coverage risk |
| Canonical `COMPLETED` tanpa settlement row | 107 | High |
| Canonical `COMPLETED` item lines | 481 | Informational |
| Canonical `COMPLETED` item lines tanpa `internal_item_id` | 183 | High |
| Legacy settlement rows | 7,819 | Informational |
| Settlement rows with non-null `settlement_time` | 3,943 | Informational |
| Settlement `data_status=incomplete` | 450 | High |
| Order `financial_data_status=incomplete` | 427 | High |
| Order `financial_data_status=unknown` | 40,370 | Medium/High |
| New finance transactions | 0 | Critical for rollout |
| New finance settlements | 0 | Critical for rollout |
| Posted marketplace postings | 4 | High |
| Posting 3 overlap with postings 1/2/4 | 188 of 188 posting-3 orders | Critical |
| Negative `inventory_stocks.qty` | 6 | High |
| Negative `lots.qty_onhand` | 4 | High |
| Inventory mutations with null `total_cost` | 82 | High |
| `finishing_jobs` / new production-order records | 0 / 0 | Blocked KPI coverage |

The status result needs careful interpretation: many local historical orders use values such as `Selesai` and `Pesanan Diterima`, while `MarketplaceFinancialDataQualityService` treats only `COMPLETED` as eligible for final financial quality. This is a confirmed coverage mismatch, not proof that the historical orders are financially invalid.

## 3. Financial data integrity

### 3.1 Duplicate posting coverage — Critical

Current local `marketplace_accounting_postings` has four posted scopes. Their statement snapshots contain:

| Posting | Scope | Orders in snapshot |
|---:|---|---:|
| 1 | store 5, `ordered_at`, June 2026 | 76 |
| 2 | store 4, `ordered_at`, June 2026 | 725 |
| 3 | all stores, `settlement_time`, July 2026 | 188 |
| 4 | all stores, `ordered_at`, July 2026 | 764 |

Posting 3 overlaps posting 1 by 26 orders, posting 2 by 124, and posting 4 by 38. The union is all 188 orders in posting 3. The code’s idempotency key prevents the same `scope_key` from posting twice, but different date basis/store/range scopes can still cover the same order.

Business impact: revenue, fee, clearing, and any linked journal lines cannot be summed safely across postings without an order-level coverage bridge. This must be resolved before enabling a new finance posting path.

Required correction: reconcile each posted order/component/journal, classify duplicate versus legitimate reclassification, and create an approved correction schedule. Do not bulk-void an entire journal based only on overlap count.

Required tests: overlapping scopes, different date bases, store-specific versus all-store posting, retry after void, and a proof that one economic event has one active posting ownership.

### 3.2 Two finance sources — Critical

The new tables in `2026_08_28_100000_create_marketplace_finance_tables.php` use unique identities and separate components/settlement allocations. The database currently has zero rows in the new transaction and settlement tables but four active aggregate postings based on the legacy statement path.

Business impact: backfilling new finance tables and posting them without a migration cutoff could create duplicate sales, fee, escrow, or settlement journals.

Required correction: choose one of:

- legacy-as-history with new finance-as-forward source;
- fully reconciled backfill with no posting until the old scope is closed; or
- new finance as read-only reconciliation layer until historical coverage is proven.

Required tests: backfill idempotency, order identity, settlement allocation, journal ownership, and “same order cannot be posted by both paths.”

### 3.3 Revenue and payout semantics — High

`MarketplaceFinancialStatementService::statementRow()` currently derives `net_sales_before_settlement = gross_sales - seller_discount`, `expected_payout = net sales - fees - refund`, and `gross_profit = payout - hpp`. The existing report therefore treats payout as the revenue base for its gross-profit row. That may be a valid contribution presentation for a marketplace cash view, but it is not automatically the same as accounting revenue or gross profit.

The project also has order customer payment and estimated payout fields, actual settlement/final income, income-detail estimates kept separate from final settlement, marketplace payout records and bank journal links, and financial transactions with `gross_amount`, `net_amount`, escrow/income status, and journal references.

Required policy: label each view as GMV, recognized net sales, marketplace net proceeds, contribution profit, or cash movement. Never use “net profit” for `payout - hpp` unless all operating costs are included.

### 3.4 Ads cost source drift — High

Current code explicitly treats wallet actual as the GL source and keeps Ads Daily as comparison. Existing posted snapshots in the local database contain a prior `ads_daily_fallback` policy. This is a confirmed historical semantic change.

Business impact: comparing a current report to an old posted snapshot can show a false business movement caused by policy change rather than operations. Ad wallet charges, refunds, top-ups, Ads Daily spend, credits, and attributed GMV must not be collapsed into one “ads cost” number.

Required correction: version the metric policy in every posted snapshot and expose source/date basis. Reconcile wallet charge/refund with Ads Daily performance and separately track credits awarded and redeemed.

## 4. Order and mapping quality

`MarketplaceOrder` retains both legacy (`external_order_id`, `order_date`, `status`, `total_paid_customer`) and canonical (`channel_order_id`, `ordered_at`, `order_status`, `total_amount`) fields. `MarketplaceOrderItem` has both legacy and newer mapping/HPP fields. This supports migration, but readers can disagree if they do not apply a shared precedence rule.

Current canonical sample:

- 435 orders have `order_status=COMPLETED`;
- 107 lack a legacy settlement row;
- 481 related item lines exist;
- 183 lines lack `internal_item_id` and usable HPP snapshot.

The quality service correctly blocks profit readiness for missing mapping/HPP, but it only recognizes a narrow final-status set. The correction is normalization and evidence-based backfill, not assigning `COMPLETED` blindly.

Recommended controls:

- canonical order identity: `store_id + channel + channel_order_id`;
- canonical line identity: provider line/model SKU plus stable line number;
- explicit status mapping table with source status, canonical status, effective time, and confidence;
- mapping version and HPP snapshot on every fact used in profit;
- unmatched queue for ambiguous SKU/order/fulfillment matches.

## 5. Inventory and costing quality

`InventoryService` creates one mutation for each stock in/out and updates the current stock row under row lock. Cost can come from explicit override, lot moving average, or incoming item average. `LotCostService::consume()` logs a warning but permits lot quantities to become negative, and item-level `allow_negative` can permit negative stock for selected materials.

Local checks found six negative stock balances, four negative lots, and 82 mutations without `total_cost`. These are not automatically bugs: some may be WIP/negative-material policy or historical records. They are nevertheless not safe to treat as valued inventory or COGS without classification.

Required reconciliation:

```text
opening stock + posted IN - posted OUT + approved adjustments = closing stock
```

Run it by warehouse, item, lot, date, source, and cost completeness. Separate physical quantity variance, valuation variance, timing/backdated mutation, intentional negative-material policy, and missing source/cost.

`ItemCostSnapshot` is a strong basis for sale-time HPP, but `ProductionCostService` currently builds unit costs from selected production/payroll periods and uses an RM snapshot at `dateTo`. It does not by itself prove full absorption of overhead, packaging, rework, reject, or WIP carry-forward.

## 6. Production, purchasing, and payroll quality

### Production

Legacy stages and newer production-order tables coexist. Current local data has cutting and sewing records but zero finishing and newer production-order records, so yield and end-to-end lead-time metrics are blocked for this snapshot. A production KPI should use posted/approved events only and must reconcile input material, output good units, rejects, rework, and WIP.

### Purchasing

PO, receipt, QC, returns, supplier invoice, payment, allocation, and supplier mapping tables exist. BI needs a three-way tie-out: PO quantity/value, received accepted quantity/value, and invoiced/paid value. The audit did not inspect supplier statements, so AP completeness is blocked/unverified.

### Payroll

Piecework payroll has periods, lines, rate resolver, final status, accrual/payment journal fields, and reports. Existing audit documentation identifies high-risk lifecycle scenarios: overlapping periods, duplicate pay protection, and rate changes inside a period. These should be treated as financial controls until current production records and payroll postings are rechecked in the target environment.

## 7. Reconciliation control design

| Control | Grain | Rule | Failure output |
|---|---|---|---|
| Order identity | store + channel + order | exactly one canonical order | duplicate/ambiguous identity queue |
| Order to settlement | order | one final settlement or explicit not-yet-final status | missing, duplicate, stale settlement |
| Settlement arithmetic | order/component | buyer payment − seller deductions − refund/adjustment = expected payout; compare actual | amount variance with source payload |
| Settlement to payout | settlement/allocation | allocations sum to settlement; payout header ties to external ID/bank | unallocated or overallocated settlement |
| Payout to GL | payout/journal | one active journal per external transaction/reference | unmatched/duplicate journal |
| Order to HPP | item line | qty × immutable HPP snapshot | unmapped/missing/negative HPP |
| Ads wallet to Ads Daily | store/date | wallet charge − refund compared with Ads Daily spend | variance by date and source |
| Inventory | warehouse/item/lot/date | opening + movements = closing | quantity/value variance |
| Production | batch/item/stage | input + WIP movements reconcile to good/reject output | yield/WIP imbalance |
| Payroll | source event/period | source event cannot be paid twice | duplicate/overlap exception |

Every exception should carry source IDs, first/last seen, freshness, owner, severity, and resolution state. A boolean “ready” is insufficient for an enterprise BI control plane.

## 8. Data that is unavailable or blocked

The audit cannot verify production database versus local snapshot, marketplace official statements and fee/credit contracts, bank statement versus payout journals, accounting policy for revenue recognition, whether all historical statuses can be safely normalized, physical inventory/WIP/reject truth, or external API freshness beyond local logs.

These are evidence gaps, not assumptions. Reports must display “not available” rather than infer values.

