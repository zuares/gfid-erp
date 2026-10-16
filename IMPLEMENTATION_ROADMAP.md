# BI Implementation Roadmap

This roadmap is intentionally stopped at audit/design. No phase below is authorized until the business approves definitions and the duplicate-posting risk is resolved.

## 1. Prioritized phases

| Phase | Goal | Main dependency | Complexity | Exit criteria |
|---|---|---|---|---|
| 0 — Control freeze and definitions | Stop new ambiguity and approve KPI policy | Owner/accounting decision | M | Signed source-of-truth, date-basis, revenue, HPP, ads, and cutoff policy |
| 1 — Baseline and data-quality cockpit | Make completeness, freshness, duplicates, and variance measurable | Phase 0 | M | Daily quality run with findings, severity, owner, and trend |
| 2 — Marketplace finance convergence | Reconcile four postings and choose legacy/new finance ownership | Phase 0–1 | L | No unexplained overlap; one active posting owner per event; new path proven in dry-run |
| 3 — Canonical sales/profit semantic layer | Centralize gross/net/proceeds/COGS/contribution definitions | Phase 2 | L | All sales/profit reports consume the same metric contract and version |
| 4 — Inventory and HPP integrity | Reconcile stock/value and expose missing/negative cost states | Phase 1–3 | L | Warehouse/item/lot balances tie; HPP completeness visible; exceptions owned |
| 5 — Production and payroll bridge | Connect stage events, WIP, rejects, labour, and cost periods | Phase 4 plus complete production data | L | Batch-level input/output/yield and payroll-to-production tie-out passes |
| 6 — Historical snapshots and comparison engine | Stable daily/monthly KPI comparisons | Phase 3–5 | M/L | Previous period/month/year and same-date comparisons are reproducible |
| 7 — Ads economics and credits | Separate performance, cash spend, VAT, credits, attribution, and profit | Phase 3 and provider evidence | L | Wallet/Ads Daily variance monitored; credits are not treated as cash revenue |
| 8 — Decision support and insights | Explain performance with evidence and confidence | Phase 1–7 | M | Rule-based insights pass factual/evidence tests; LLM is optional wording layer |

## 2. Phase 0 — Control freeze and definitions

Actions: pause new aggregate marketplace posting scopes that can overlap existing postings; enumerate active journals and post-level order/component coverage; approve recognition event and date basis; approve definitions for GMV, recognized net sales, proceeds, gross profit, contribution profit, operating profit, net profit, and cash flow; approve HPP source and missing-cost treatment; approve wallet ads versus Ads Daily and credit treatment; decide how legacy statuses such as `Selesai` map to canonical status.

Acceptance criteria: accounting owner signs the policy; every KPI has owner, source, denominator, and “not available” behavior.

## 3. Phase 1 — Data quality cockpit

Schedule read-only checks for order identity, settlement completeness, HPP/mapping, inventory balance/cost, production flow, payroll overlap, ads source variance, and journal ownership. Record evidence IDs, run time, freshness, severity, owner, and resolution. Do not auto-correct financial facts.

Acceptance criteria: the 188-order posting overlap, 107 canonical completed orders without settlement, 183 missing canonical item/HPP mappings, six negative stock rows, four negative lots, and 82 uncosted mutations are individually traceable or explicitly waived.

## 4. Phase 2 — Marketplace finance convergence

Reconcile postings 1–4 against order, settlement, component, payout, and journals; classify duplicate coverage versus legitimate timing; establish historical cutoff; dry-run finance backfill with no posting; prove one order/settlement cannot be posted by both legacy and new services; keep estimates out of final financial facts.

Acceptance criteria: zero unexplained critical overlap; all exceptions have approved correction; new finance tables are populated only by an idempotent process; posted and provisional totals tie to source statement extracts.

## 5. Phase 3 — Semantic sales/profit layer

Create metric registry and versioning; centralize date-basis and comparison engine; make output carry actual/estimate/provisional/blocked state; align product/channel/store grains; expose numerator, denominator, source freshness, and excluded rows.

Acceptance criteria: sales dashboard, marketplace profit, financial statement, Ads economic view, and exports return the same values for the same request, or clearly explain scope differences.

## 6. Phase 4 — Inventory and HPP integrity

Reconcile stock projection to mutation ledger; classify intentional negative materials separately; repair or quarantine missing cost mutations through approved workflow; reconcile lot cost rebuilds and HPP snapshots; define WIP, reject, rework, and shipment COGS policy.

Acceptance criteria: all inventory exceptions are known; valued stock and COGS do not silently use zero; sale-level HPP has an immutable effective snapshot.

## 7. Phase 5 — Production and payroll bridge

Choose a canonical production process family or explicit bridge; attach input/output/reject/rework events to batch and item; prevent overlapping payroll periods and duplicate source-event payment; version piece rate by work date; include labour, RM, packaging, and approved overhead in actual-vs-standard HPP.

Acceptance criteria: one production batch reconciles input material, WIP, good output, rejects, labour, and closing cost; payroll journal agrees with finalized period and payment state.

## 8. Phase 6 — Snapshots and comparisons

Persist daily/monthly snapshots only after semantic definitions are approved. Include definition version, source freshness, quality state, actual/provisional flag, and lineage. Do not overwrite closed snapshots; corrections create a new version with reason.

Acceptance criteria: MoM, PoP, same-date, and YoY comparisons are deterministic across reruns and show “not comparable” where source states differ.

## 9. Phase 7 — Ads economics

Model gross spend, VAT treatment, wallet charge, refund, top-up, awarded credit, redeemed credit, and net cash separately. Reconcile campaign/item attributed GMV to total order sales without summing attribution as incremental sales. Keep ROAS/ACOS distinct from contribution profit. Version policy changes such as wallet actual versus Ads Daily fallback.

Acceptance criteria: every ad cost in a profit view has source, date basis, credit treatment, and attribution caveat.

## 10. Phase 8 — Explainable insights

Build rule-based insights first. A rule may fire only when numerator, denominator, time windows, quality state, and driver evidence are available. An LLM can phrase approved evidence but cannot change numbers or assert causation.

Acceptance criteria: each insight includes metric deltas, supported factors, quantified impact, action, confidence, and missing evidence; every insight can be regenerated from a stored snapshot.

## 11. Suggested first milestone

The first implementation milestone should be **Marketplace Financial Control Baseline**: a read-only reconciliation report for the four posted scopes, order/status/mapping coverage, settlement arithmetic, payout-to-journal ownership, and ads source variance. It should not add new posting, alter existing journals, or run migrations. Approval should require accounting sign-off on correction treatment before any BI snapshot or new finance posting is activated.

