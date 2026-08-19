# Ads Experiment & Impact — Phase 0 Data Contract

Status: Phase 0 specification. This document does not create or change database schema.

## 1. Purpose

`Ads Experiment & Impact` records a single setting change and compares the same product/campaign scope before and after the change.

The business objective is `TOTAL NET PROFIT`. ROAS, traffic, CTR, CVR, quantity, revenue, and spend are diagnostic metrics.

The system must describe an association after a change. It must not claim that the change caused the result.

## 2. Scope identity and grain

The canonical experiment scope is:

```text
store_id
channel_campaign_id
channel_item_id
internal_item_id (nullable until mapping is complete)
```

For a store-wide GMV Max target change where the platform does not expose a
campaign identifier, channel_campaign_id and channel_item_id may both be null
and source_granularity must be store. This record is explicitly not a
product-level attribution.

One experiment represents one setting change for one scope:

```text
one scope + one change event = one experiment record
```

Supported change types:

- `price`
- `target_roas`
- `price_and_target_roas`

`price_and_target_roas` is accepted for compatibility, but is immediately marked `confounded = true` because it violates the one-variable-at-a-time rule.

Campaigns with multiple items are not allowed to produce a confident product-level verdict unless the source data is item-grained. If only campaign-level data exists, the experiment is either campaign-scoped or `INCONCLUSIVE`.

## 3. Source-of-truth matrix

| Metric | Canonical source | Calculation rule | Data state |
|---|---|---|---|
| Impressions | `marketplace_ads_dailies` or `marketplace_ad_campaign_dailies` | Sum counters | Available |
| Clicks | Ads daily tables | Sum counters | Available |
| CTR | Derived | `clicks / impressions` | Available when impressions > 0 |
| Orders | `broad_order` for primary ads KPI | Sum counters | Available |
| Qty | `broad_order_amount` when present; order fallback otherwise | Sum quantity | Estimated when fallback is used |
| GMV | `broad_gmv` | Sum GMV | Available |
| Spend | `expense` / `spend` | Sum spend before ads VAT | Available |
| CVR | Derived | `orders / clicks` | Available when clicks > 0 |
| Platform ROAS | Derived | `GMV / spend` | Available when spend > 0 |
| Price current | `marketplace_product_models.price` / raw price info | Current value only | Available |
| Price historical | `marketplace_product_dailies` | Daily snapshot | Available, not intraday |
| Target ROAS current | `marketplace_ad_campaigns.target_roas` or store setting | Current value only | Available |
| HPP current | `ItemHppResolver` | Current/variant average | Available when mapped |
| HPP realized | `marketplace_order_items.hpp_snapshot` | Qty-weighted sum | Available only for valid orders |
| Payout realized | `marketplace_order_settlements.final_income` | Sum settlement payout | Available after settlement |
| Ad cost realized | `marketplace_order_settlements.ad_cost` | Sum allocated ad cost | Incomplete until allocation exists |
| Net profit estimated | Ads Dashboard calculation | Net revenue estimate - HPP - ads spend after VAT | Available with caveat |
| Net profit actual | Profit Report calculation | Payout - HPP snapshot - allocated ad cost | Only when quality gate passes |

`Traffic` in this contract means ads delivery traffic: impressions and clicks. Unique visitors are not available and must not be displayed as if they were available.

## 4. Profit basis

The experiment exposes a required `profit_basis`:

- `estimated`: derived from ads performance, payout ratio, HPP, and ads spend.
- `actual`: derived from settlement payout, HPP snapshot, and allocated ad cost.
- `incomplete`: profit cannot be safely evaluated.

Rules:

1. Early monitoring may show estimated profit.
2. A final `SUCCESSFUL` or `SUCCESSFUL SCALE` verdict requires `actual` profit unless the user explicitly accepts an estimated verdict.
3. If actual ad-cost allocation is unavailable, the final business verdict is `INCONCLUSIVE`.
4. The UI must never silently mix estimated and actual profit in one comparison.

The Ads Dashboard's default net revenue ratio (`0.781`) is an estimation fallback only. It is not a permanent experiment fact unless stored in the experiment calculation snapshot.

## 5. Calculation snapshot

At experiment creation, store the inputs used by the simulation so that later changes in current product data do not rewrite the original decision context:

- old and new price
- old and new target ROAS
- HPP and HPP source
- fee mode and fee ratio/source
- ads VAT factor
- price basis (`current_model`, `daily_snapshot`, `observed_gmv_per_order`)
- profit basis
- source granularity (`store`, `campaign`, `item`)
- mapping status
- calculation version

The snapshot is explanatory/audit data. Daily performance remains sourced from the ads fact tables.

## 6. Date and timezone contract

Application timezone is `Asia/Jakarta`.

The change event keeps both:

- `changed_at`: exact timestamp in application timezone;
- `effective_date`: local calendar date of the change.

Because most ads facts are daily aggregate:

- the partial change day is excluded from baseline and observation;
- baseline ends on the day before `effective_date`;
- observation starts on the day after `effective_date`;
- hourly facts may be shown for context but do not replace the daily comparison in MVP.

Default windows:

```text
baseline:    7 completed days before effective_date
observation: 7 completed days after effective_date
extension:   up to 14 completed days
```

All date ranges are inclusive and stored as local dates.

## 7. Data quality states

Each period has a data quality result separate from lifecycle status:

- `complete`: required facts and mapping are present;
- `estimated_qty`: quantity uses order fallback;
- `missing_metric`: one or more required metrics are absent;
- `missing_mapping`: product cannot be linked to internal item/HPP;
- `low_volume`: traffic or order volume is below the MVP threshold;
- `partial_day_excluded`: the change day was intentionally excluded;
- `revised_source`: source was refreshed after the first evaluation.

Suggested MVP sufficiency thresholds:

- both periods contain 7 completed days;
- at least 30 clicks per period;
- at least 3 orders per period for CVR/profit interpretation;
- HPP mapping covers at least 90% of observed quantity or revenue;
- actual profit requires complete settlement and ad-cost allocation.

Thresholds must be configurable later; they must not be scattered through Blade templates.

## 8. Metric aggregation contract

Never average daily percentages directly.

For each period:

```text
impressions = SUM(impressions)
clicks      = SUM(clicks)
orders      = SUM(orders)
qty         = SUM(qty)
revenue     = SUM(GMV)
spend       = SUM(spend)

CTR         = clicks / impressions
CVR         = orders / clicks
ROAS        = revenue / spend
```

When a denominator is zero, return `null`/`N/A`, not zero percent.

Each before/after metric must expose:

```text
before_value
after_value
absolute_delta
percentage_delta
is_available
data_quality
```

Percentage delta is unavailable when the before value is zero.

## 9. Profit and BEP contract

The canonical simulation is:

```text
marketplace_fee = price × fee_percent + fixed_fee
ad_cost_per_order = price / actual_roas
profit_per_unit = net_revenue_unit - HPP - ad_cost_per_order
```

If ads VAT is included in cash profit:

```text
ad_cost_real = ad_cost_per_order × 1.11
```

If the project uses settlement payout ratio instead of explicit fee components:

```text
net_revenue_unit = price × payout_ratio
```

Fees must not be subtracted again after applying payout ratio.

BE quantities:

```text
qty_bep = qty_old × profit_per_unit_old / profit_per_unit_new
qty_growth_bep = (qty_bep / qty_old - 1) × 100
```

Do not divide when the new profit is zero/negative or when old quantity is zero. Return `N/A` with a warning.

## 10. Lifecycle status

Status precedence:

1. `CONFOUNDED` conflict state if attribution is compromised;
2. `INSUFFICIENT_DATA` if volume/data is not sufficient;
3. `LEARNING` for completed days 0–2;
4. `EARLY_SIGNAL` for completed days 3–6;
5. `READY_TO_EVALUATE` from day 7 when data is sufficient;
6. `COMPLETED` after a final verdict is saved.

The display label may show both lifecycle and quality, for example:

```text
Early Signal · estimated profit · low volume
```

## 11. Conflict detection

An experiment is confounded when another material change overlaps its observation window:

- another price experiment for the same item;
- another target ROAS experiment for the same campaign/item;
- price snapshot shows a second change;
- active promotion includes the item;
- boost log exists for the item;
- campaign status/budget changes materially;
- stock reaches zero or becomes materially constrained.

Existing promo/boost records may be used as external evidence. They do not need to be copied into the experiment table in MVP.

## 12. Verdict contract

Verdict is based primarily on total net profit, not ROAS.

Suggested default materiality:

- profit: at least 5% and/or a configurable minimum nominal delta;
- volume: at least 10% increase.

Rules:

- `SUCCESSFUL`: actual total net profit increases materially and the experiment objective is met;
- `SUCCESSFUL_SCALE`: target ROAS experiment increases volume and actual total net profit even if ROAS falls;
- `VOLUME_GROWTH`: volume increases materially but total net profit does not increase materially;
- `UNPROFITABLE`: actual total net profit falls materially;
- `INCONCLUSIVE`: actual profit unavailable, data insufficient, or metrics conflict;
- `CONFOUNDED`: another material change weakens attribution.

The report must use neutral language: `associated with`, `after the change`, and `main indication`.

## 13. Phase 1 acceptance criteria

Phase 1 may start only when these decisions are accepted:

- canonical scope is store + campaign + item;
- one record equals one setting change;
- partial change day is excluded;
- default comparison is 7 completed days versus 7 completed days;
- actual and estimated profit are separate;
- no final success verdict without actual profit completeness;
- campaign multi-item attribution is not silently treated as product attribution;
- all original simulation inputs are snapshotted;
- no destructive migration is needed.

## 14. Known blocker for actual verdicts

The current project does not consistently allocate ads spend to settlement/order/item level. Therefore Phase 1 can safely implement history and observation tracking, but a truly actual profit verdict remains blocked until the allocation basis is defined.

Until then, the system should show estimated impact and explicitly mark final verdict as `INCONCLUSIVE` when actual profit coverage is incomplete.
