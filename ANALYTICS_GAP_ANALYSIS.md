# Analytics Gap Analysis

Status uses the requested classifications: **Existing and reliable**, **Existing but needs improvement**, **Partially implemented**, **Missing**, and **Blocked by unavailable data**.

## 1. Capability matrix

| Capability | Status | Existing evidence | Gap/dependency | Complexity |
|---|---|---|---|---|
| Gross sales / GMV | Existing but needs improvement | marketplace profit/statement services, order item facts, sales reports | Multiple gross definitions and status/date sources | M |
| Net sales after seller discount | Partially implemented | `MarketplaceFinancialStatementService::statementRow()` | Must separate seller discount, platform subsidy, shipping, refund, tax, and accounting recognition | M |
| Orders and units | Existing but needs improvement | order/item models, sales dashboard, daily item sales | Legacy/canonical status and duplicate line grain | M |
| AOV | Existing and reliable only within one report | statement row derives gross/order count | Needs one enterprise date basis and order inclusion policy | S |
| Marketplace fees/commissions | Existing but needs improvement | settlement fields, fee breakdown, finance components | Alias normalization and source precedence need one contract | M |
| Affiliate/voucher/subsidy | Partially implemented | settlement fields/raw aliases, promotions/vouchers modules | Credits and subsidies are not uniformly classified as seller cost, platform subsidy, or cash | M |
| Returns/refunds/cancellations | Partially implemented | return jobs/controllers, settlement refund fields | Event-level accounting and attribution window not canonical | L |
| Estimated payout | Existing and reliable as estimate | income detail service and separate income-estimate relation | Must never enter final accounting KPI | S |
| Actual payout/settlement | Existing but needs improvement | legacy settlement, payout, new finance tables, reconciliation service | Two generations and zero new local rows | L |
| SKU/product/channel profitability | Partially implemented | profit overview, item HPP snapshots, channel reports | HPP/mapping coverage and single revenue definition | L |
| Gross profit | Existing but needs improvement | reports calculate payout/HPP or net/HPP variants | Naming and accounting basis are inconsistent | M |
| Contribution profit | Partially implemented | Ads experiment calculator and marketplace reports | Need variable fee/ads/fulfillment policy and actual versus estimated flags | L |
| Operating profit | Partially implemented | GL P&L and financial statement | Marketplace statement currently has narrower operating cost scope | L |
| Net profit | Existing in accounting report, not unified BI KPI | journals, P&L, cash basis | Cross-domain posting completeness and period close | L |
| Cash flow | Existing but needs improvement | cash basis, receipts, expenses, transfers, payouts | Marketplace wallet/clearing and bank reconciliation | L |
| Ads spend | Existing but needs improvement | Ads Daily, campaign/item daily, wallet transactions | Wallet versus performance/credits and date basis | M |
| ROAS/ACOS/CTR/CVR/CPC/CPA | Existing and reliable as platform metrics | `AdsAnalyticsService`, `AdsDashboardService` | Attribution and fallback coverage must be labelled | M |
| Product-level ad efficiency | Partially implemented | item daily and campaign item tables | Product mapping and attributed revenue overlap | M |
| Ads credits/compensation | Missing as canonical financial bridge | raw/provider fields may exist; no approved credit ledger found | Need awarded/redeemed/expired/net-cash facts | L |
| Break-even ROAS / pricing simulation | Existing but needs improvement | `AdsExperimentCalculator`, pricing calculator | Scenarios use assumptions and 1.11 VAT factor; not a ledger fact | M |
| Inventory on hand | Existing and reliable only after exception handling | `InventoryService`, stock cards, mutations | Six negative stocks locally | M |
| Inventory valuation | Partially implemented | lot moving average, mutation cost, HPP snapshots | 82 null mutation costs, negative lots, WIP policy | L |
| Turnover / DIO | Missing as governed KPI | raw movements and sales exist | Needs valuation snapshots and consistent COGS period | M |
| Slow/dead stock | Partially implemented | inventory intelligence and daily sales | Needs approved velocity window, ageing, and stock state | M |
| Stockout/reorder | Existing but needs improvement | warehouse intelligence/procurement recommendation | Forecast confidence and supplier lead time quality | M |
| Purchasing spend / supplier performance | Existing but needs improvement | PO/receipt/QC/payment and purchasing dashboard | Three-way match and price variance semantic layer | M |
| Production output/yield/reject | Partially implemented | stage controllers, QC, production dashboards | Current local finishing/new production data empty | L |
| Production lead time/capacity | Blocked by unavailable data | event tables and dashboard routes exist | Need complete stage timestamps and calendars | L |
| Actual vs standard HPP | Partially implemented | BOM estimator, HPP snapshots, production cost | Need actual material/labour/overhead allocation and variance | L |
| Payroll productivity/cost per unit | Existing but needs improvement | payroll generators/reports | Period overlap, rate history, event linkage | M |
| MoM / PoP / same-date comparisons | Partially implemented | Ads historical comparison, sales dashboard comparisons, date-basis filters | No shared comparison engine and metric version | M |
| Historical daily/monthly snapshots | Partially implemented | daily item sales, ads facts, product snapshots | No enterprise snapshot across finance/inventory/production | L |
| Reconciliation cockpit | Existing but needs improvement | marketplace finance reconciliation and UI | Inventory, production, payroll, bank are not one control plane | L |
| Explainable business insights | Partially implemented | rule-based Ads recommendations and OpenAI agent | No mandatory evidence, confidence, causality separation, or reproducibility contract | M |
| AI/LLM decision support | Partially implemented | OpenAI service/agent, MCP stock read tool | Should consume approved metric snapshots only | M |

## 2. Highest-value gaps

### Gap 1 — Canonical metric contract

The same business concept can currently be read from order fields, settlement fields, sales invoices, finance snapshots, Ads facts, or GL. Before adding more dashboards, define each metric’s grain, date basis, source precedence, status eligibility, sign convention, and version.

### Gap 2 — Financial event ownership

One order can appear in multiple posted scopes, while the new transaction subledger is empty. A finance event registry is needed so a sale, fee, escrow release, payout, refund, and reversal each have one owner and one active journal lifecycle.

### Gap 3 — Historical comparability

Comparison UI exists in multiple places, but comparison semantics vary: previous period, previous month, previous year, settlement date, order date, current ad-fee ratio, or actual wallet source. A shared comparison engine must compare like-for-like facts and display “not comparable” when source quality differs.

### Gap 4 — Manufacturing-to-profit bridge

HPP snapshots exist, but production output, reject/rework, WIP, labour, and overhead are not yet proven to reconcile end to end. Without this bridge, SKU profitability can be directionally useful but not a full manufacturing margin.

### Gap 5 — Ads credit and attribution governance

Ads platform metrics are available, but gross spend, VAT factor, wallet cash movement, provider credit, redeemed credit, and attributed GMV are not one governed ledger. ROAS should remain a platform efficiency metric, not be interpreted as incremental profit without attribution assumptions.

## 3. Prioritization

1. **P0:** reconcile the four posted marketplace scopes and freeze new duplicate posting.
2. **P0:** define legacy-to-canonical order/status/SKU precedence and final versus provisional policy.
3. **P1:** build cross-domain reconciliation and freshness scorecards.
4. **P1:** establish one metric registry and versioned date-basis/comparison engine.
5. **P1:** make inventory valuation and HPP completeness visible in every profit KPI.
6. **P2:** unify production/payroll events and calculate actual-versus-standard cost.
7. **P2:** add governed ads credit/attribution facts and scenarios.
8. **P3:** add rule-based insight narratives, then evaluate LLM assistance.

