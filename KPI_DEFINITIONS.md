# KPI Definitions

These are proposed governed definitions. They are not a claim that every KPI is currently reliable in the database. Each KPI must return value, numerator, denominator, grain, date basis, source freshness, quality state, and definition version.

## 1. State labels

| State | Meaning |
|---|---|
| Actual | Provider/operational/accounting fact meets the approved completeness gate |
| Provisional | Operationally useful but not final settlement/accounting fact |
| Estimate | Explicitly estimated, such as Income Detail expected payout |
| Simulation | Derived from user assumptions; never posted to GL |
| Blocked | Required source, mapping, cost, or reconciliation evidence is missing |

## 2. Revenue and order KPIs

| KPI | Definition / formula | Primary source | Limitation |
|---|---|---|---|
| Gross merchandise sales (GMV) | Sum of item selling value before seller-funded discount; platform subsidy shown separately | canonical order items, provider order payload | Choose price field precedence per channel |
| Seller discount | Sum of discount absorbed by seller, voucher, coin, or seller-funded campaign component | order items + settlement components | Provider labels can overlap; normalize by component code |
| Platform subsidy | Discount/shipping support funded by platform, not seller | settlement raw/components | Not revenue and not seller cost unless policy says so |
| Recognized net sales | GMV minus approved seller discounts, returns/refunds, and other revenue reductions under accounting policy | finance transaction/order + approved recognition event | Not automatically equal to payout |
| Orders | Count distinct canonical order identity `store + channel + order ID` | `marketplace_orders` | Exclude cancelled/unpaid according to versioned policy |
| Units sold | Sum positive accepted item quantity for included orders | `marketplace_order_items` | Prevent duplicate lines and handle returns separately |
| AOV | Recognized sales or GMV divided by distinct included orders | order/semantic layer | Label numerator explicitly |
| Actual marketplace proceeds | Final income/payout after provider deductions | settlement/finance transaction | Cash availability and accounting recognition may differ |
| Estimated payout | Expected payout from Income Detail or approved estimator | income estimate relation / raw income detail | Never label actual or post to GL |
| Refund rate | Refund amount divided by GMV or recognized sales, with numerator definition shown | settlement/refund facts | Attribution window and return completion matter |
| Cancellation rate | Cancelled eligible orders divided by all created eligible orders | order status events | Requires status transition history to avoid snapshot bias |

Recommended date basis: demand view uses `ordered_at`; recognized revenue uses approved recognition timestamp; proceeds/cash uses settlement/released/payout timestamp; estimated cash uses `estimated_payout_at` with an Estimate label. Do not mix these in one total without a visible date basis.

## 3. Cost and profitability KPIs

| KPI | Definition / formula | Source | Limitation |
|---|---|---|---|
| COGS/HPP | Accepted quantity × immutable HPP snapshot or approved actual cost allocation | `ItemCostSnapshot`, order item HPP, inventory mutation cost | Missing/negative cost makes KPI Blocked or Estimated |
| Gross profit | Recognized net sales − COGS | semantic sales + inventory/cost | Existing `payout − HPP` view needs a different label |
| Gross margin | Gross profit ÷ recognized net sales | derived | Zero denominator returns not comparable |
| Marketplace fee | Commission + service + transaction + affiliate + approved variable marketplace deductions | settlement components | Mapping and sign convention must be versioned |
| Contribution profit | Recognized net sales/proceeds − COGS − marketplace variable fees − variable fulfillment − approved actual ad cost | finance + cost + ads | Revenue base and actual/estimate state must be shown |
| Contribution margin | Contribution profit ÷ recognized net sales/proceeds | derived | Show quality state |
| Operating profit | Contribution profit − operating payroll − overhead − approved operating expenses | posted GL and cost layer | Incomplete until all operating domains post consistently |
| Net profit | Operating profit + non-operating income − non-operating expense − tax | closed accounting P&L | Requires accounting policy and close |
| Cash inflow/outflow | Actual bank/wallet receipts/payments in period | journals, cash receipts, expenses, payouts | Payout can be a balance transfer, not new revenue |

## 4. Advertising KPIs

| KPI | Formula | Source | Limitation |
|---|---|---|---|
| Gross ad spend | Provider performance expense before credit/refund policy | Ads daily/campaign/item daily | May not equal wallet cash movement |
| Wallet ad charge/refund | Sum wallet charge/refund events by transaction date | ad wallet ledger | Separate top-up and credit events |
| Net cash ad spend | Wallet charge − refund, less only redeemed credits when provider evidence supports it | wallet + credit ledger | Never infer credits from spend alone |
| Ads Daily variance | Net wallet spend − Ads Daily spend on the same store/date basis | wallet + Ads Daily | Variance is an exception, not automatically an error |
| Attributed ad sales | Provider GMV for defined attribution window | Ads facts | Not incremental sales and can overlap total sales |
| ROAS / ACOS | attributed sales ÷ spend / spend ÷ attributed sales | Ads facts | Platform efficiency, not profit |
| CTR / CVR | clicks ÷ impressions / attributed orders ÷ clicks | Ads facts | Null if denominator is zero; windows must be shown |
| CPC / CPA | spend ÷ clicks / spend ÷ attributed orders | Ads facts | Do not mix attributed and total orders |
| Ads profit contribution | Approved net revenue − HPP − governed ad cost | experiment calculator + governed facts | Current simulator includes assumptions and VAT factor 1.11 |
| Break-even ROAS | Required ad efficiency to cover contribution and ad cost | experiment calculator | Simulation, not observed fact |

Credit must be tracked as awarded, available, redeemed, and net cash paid. Awarded or available credit is not cash revenue.

## 5. Inventory, working capital, production, and payroll

| KPI | Definition / formula | Source | Limitation |
|---|---|---|---|
| On-hand quantity | Posted IN − OUT plus approved adjustment by warehouse/item/lot | inventory mutations/stocks | Negative and uncosted exceptions visible |
| Inventory value | On-hand quantity × approved lot/item cost | lot cost, mutations, HPP snapshots | Negative lots/null costs block full valuation |
| Stock turnover | COGS ÷ average inventory value | COGS + valuation snapshots | Needs opening/closing snapshots |
| DIO | Average inventory value ÷ COGS × days | same | Not meaningful when COGS is zero/incomplete |
| Slow/dead stock | Value with no/below-threshold movement over approved window | inventory + sales history | Threshold is business policy |
| Reorder point | Demand during lead time + safety stock | daily sales + supplier lead time | Recommendation, not automatic PO |
| Production output | Posted good units by item/batch | finishing/production receipts | Local snapshot has no finishing/new production rows |
| Yield / reject / rework | good output ÷ input; reject ÷ processed; rework ÷ processed | production, QC, repair | Unit conversion and event linkage required |
| Stage lead time | output timestamp − input/start timestamp | production events | Missing timestamps block KPI |
| Labour productivity | good units per paid hour or labour per good unit | payroll + production | Rate and attendance policy required |
| Actual vs standard HPP | actual unit HPP − standard/BOM unit HPP | cost period, BOM, snapshots | Requires full RM/labour/overhead policy |

## 6. Reconciliation and quality KPIs

| KPI | Rule |
|---|---|
| Order-to-settlement completeness | eligible orders with valid final settlement ÷ eligible orders |
| HPP/mapping completeness | valid immutable HPP or internal item lines ÷ included item lines |
| Payout allocation completeness | allocated valid settlement amount ÷ settlement amount |
| Journal ownership completeness | events with exactly one active journal ÷ posted events |
| Duplicate event rate | unexplained duplicate canonical identities or posting owners; target zero |
| Inventory reconciliation variance | closing balance − reconstructed mutation balance; target zero quantity |
| Ads source variance | wallet net − performance spend within approved tolerance |
| Data freshness | now − latest successful sync/aggregate timestamp against source SLA |
| Quality score | weighted pass rate, never hiding Critical findings behind an average |

## 7. Required metadata on every KPI response

```text
metric_key
definition_version
grain
date_basis
date_from/date_to
numerator/denominator
value
state: actual/provisional/estimate/simulation/blocked
source_tables and source IDs
last_successful_sync
excluded_count and exclusion_reasons
quality_findings
confidence
```

This metadata is required before an insight engine or LLM may describe a KPI as business performance.

