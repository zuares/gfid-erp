# BI Architecture Proposal

This is a target design, not an implementation authorization. It intentionally reuses existing Laravel services and models and avoids a rewrite.

## 1. Design principles

1. Financial accuracy and lineage precede visual polish.
2. One metric has one definition, one grain, one date basis, and one source policy.
3. Operational facts are not silently rewritten by analytics jobs.
4. Estimates, actuals, simulations, and accounting-posted values remain distinct.
5. Historical reports are reproducible through metric-definition version and source snapshot.
6. Rule-based deterministic analytics precedes LLM-generated explanations.
7. New schema is justified only where current operational tables cannot provide history, lineage, ownership, or performance.

## 2. Proposed layers

```text
1. Source facts
   marketplace APIs/CSV/webhooks, orders, settlements, ads, inventory mutations,
   purchasing, production events, payroll, journals, bank/payouts

2. Ingestion and normalization
   adapters -> raw payload + sync run + source hash -> canonical identity/status/mapping

3. Finance and operational facts
   order, order line, settlement component, payout, ad spend, stock movement,
   production output, payroll event, journal event

4. Semantic metric layer
   KPI registry + date basis + inclusion state + definition version + quality gate

5. Snapshot and aggregate layer
   daily/monthly facts, inventory valuation snapshots, period close snapshots,
   comparison-ready aggregates

6. Serving layer
   Laravel query services, exports, dashboards, reconciliation cockpit, API/MCP

7. Insight layer
   deterministic drivers/anomalies -> evidence-backed narrative -> optional LLM wording
```

## 3. Canonical fact ownership

| Business fact | Preferred owner | Existing reusable source | Rule |
|---|---|---|---|
| Marketplace order | `marketplace_orders` | `MarketplaceOrder` | identity is store + channel + provider order ID |
| Order line | `marketplace_order_items` | item mapping/HPP fields | quantity and line revenue snapshot are immutable after acceptance |
| Final marketplace economics | new finance subledger after approved cutover | `MarketplaceFinancialTransaction` + components | legacy settlement is source input until migrated |
| Payout/withdrawal | one external settlement header + allocation | finance settlement or legacy payout during transition | no posting without external ID and reconciliation state |
| Ads performance | provider performance fact tables | `marketplace_ads_dailies`, campaign/item daily | attributed GMV is not total sales |
| Ads cash spend | wallet transaction ledger | `MarketplaceAdWalletTransaction` | charge/refund/top-up/credit are separate event types |
| Stock | mutation ledger + balance projection | `InventoryMutation`, `InventoryStock` | balance must reconcile to posted mutations |
| Product cost | dated HPP snapshot | `ItemCostSnapshot` | sale uses snapshot effective at sale/recognition date |
| Production | posted stage/production event | existing stage tables plus chosen canonical family | one event must not enter both process families |
| Payroll | finalized period and source event | `PieceworkPayrollPeriod`, lines, rates | payment/accrual lifecycle is immutable and idempotent |
| Accounting | posted journal and lines | `JournalService`, `journals`, `journal_lines` | journal ownership and reversal are explicit |

## 4. Minimal data model additions

Do not add these tables before Phase 0 confirms the need. The preferred first implementation can be service/query based. If scale, historical reproducibility, or lineage requires persistence, the minimum conceptual models are:

### 4.1 `bi_metric_definitions`

Fields: metric key, version, label, formula identifier, numerator/denominator, grain, date basis, source precedence, allowed states, sign convention, owner, effective-from/to, status.

Purpose: make “gross sales”, “net sales”, “gross profit”, “ROAS”, and “cash flow” unambiguous and versioned.

### 4.2 `bi_daily_snapshots`

Fields: metric key/version, date, store/channel/item/warehouse dimensions, value, numerator, denominator, source freshness, quality state, generated-at, lineage reference.

Purpose: stable daily/monthly comparisons without rerunning mutable operational joins.

### 4.3 `bi_reconciliation_runs` and `bi_reconciliation_findings`

Fields: check key/version, run window, source IDs, expected/actual values, variance, severity, status, owner, resolution reference, evidence hash.

Purpose: common control plane for marketplace, inventory, production, payroll, and accounting checks.

### 4.4 `bi_insight_runs` and `bi_insights` (later)

Fields: metric changes, driver evidence, absolute/percentage deltas, assumptions, confidence, correlation disclaimer, action, status, source snapshot/version.

Purpose: reproducible explanations; LLM output is a presentation layer over structured evidence.

These additions are justified only if query/service calculations cannot guarantee reproducibility or acceptable performance. Do not create a generic data warehouse inside the transactional database without a measured need.

## 5. Metric engine contract

Every metric query should receive:

```text
MetricRequest {
  metric_key,
  definition_version,
  date_from,
  date_to,
  date_basis,
  dimensions,
  inclusion_state: actual | provisional | estimate | simulation,
  store/channel/item filters,
  quality_policy,
  comparison_mode
}
```

Every result should return:

```text
MetricResult {
  value,
  numerator,
  denominator,
  grain,
  date_basis,
  source_tables,
  source_freshness,
  quality_state,
  excluded_count,
  definition_version,
  confidence,
  warnings
}
```

This prevents a dashboard from displaying a number without telling the user whether it is actual, estimated, incomplete, fallback, or simulation.

## 6. Financial calculation policy

Use separate measures:

1. **GMV/gross merchandise sales:** customer/item selling value before seller-funded discounts, with platform subsidy shown separately.
2. **Recognized net sales:** revenue recognized under approved accounting policy, after the defined seller discounts/returns, not automatically equal to payout.
3. **Marketplace proceeds:** final income after marketplace deductions; useful for contribution/cash reconciliation.
4. **COGS/HPP:** item quantity multiplied by dated immutable HPP snapshot or actual cost allocation, with missing/estimated flag.
5. **Contribution profit:** recognized net sales or proceeds, less marketplace variable fees, fulfillment variable cost, and actual/approved ad cost according to policy.
6. **Operating profit:** contribution profit less operating payroll/overhead/other operating expenses posted to GL.
7. **Net profit:** operating profit plus/minus non-operating and tax items from closed accounting periods.
8. **Cash flow:** cash/bank movements, including payout timing, ad wallet, supplier payments, payroll payments, and transfers.

`payout - HPP` may remain as a marketplace contribution view, but must not be labelled gross or net profit without the above scope.

## 7. Reconciliation architecture

Run a deterministic reconciliation pipeline after ingestion and before posting/closing:

1. identity and duplicate check;
2. order-to-item mapping and HPP completeness;
3. order-to-settlement and final/provisional state;
4. settlement component arithmetic;
5. settlement-to-payout allocation;
6. payout-to-bank and payout-to-journal;
7. order-to-shipment/HPP;
8. inventory mutation-to-balance and cost completeness;
9. production input/output/WIP/reject;
10. payroll source event and payment idempotency;
11. ads wallet-to-performance and credits.

Closing should be blocked for unresolved Critical findings, while Medium/Low findings can be acknowledged with a documented owner and threshold.

## 8. Comparison engine

Provide one service for previous equal-length period, previous calendar month, previous year, same date range in prior month/year, month-to-date versus prior month-to-date, and settlement-date versus order-date views.

It must align calendar/timezone, store/channel scope, metric definition version, actual/estimate state, and quality policy. A comparison should return “not comparable” instead of a percentage when denominators are zero or source policies differ.

## 9. Insight engine

Start with deterministic drivers:

```text
delta(metric) = current - comparison
delta_pct(metric) = delta / abs(comparison) when comparison != 0
contribution = supported driver change × approved relationship
```

Example output structure:

- observation: sales down X%;
- supporting changes: ads spend down Y%, units down Z%, fee rate changed A pp;
- quantified impact: amount in reporting currency;
- causality: “consistent with” unless a causal experiment exists;
- risk/opportunity;
- next action;
- confidence and missing evidence.

The LLM may summarize this structure, but it must not invent causes, fill missing financial values, or recommend actions without evidence IDs.

## 10. Integration strategy

Reuse `MarketplaceFinanceReconciliationService`, `MarketplaceFinancialDataQualityService`, `MarketplaceFinancialStatementService`, `MarketplaceProfitOverviewService`, HPP snapshot services, `InventoryService`/`LotCostService`, Ads analytics and experiment calculator, `JournalService`, and existing closing/audit logs.

Wrap direct external calls behind shared clients with consistent retry, rate limit, redaction, request ID, and sync-run logging. Keep MCP read-only and expose approved metric results rather than arbitrary raw SQL.

