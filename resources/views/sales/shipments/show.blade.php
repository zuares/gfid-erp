{{-- resources/views/sales/shipments/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Shipment ' . $shipment->code)

@push('head')
<style>
.sd-wrap{max-width:1040px;margin-inline:auto;padding:.7rem .75rem 3rem}
.sd-topbar{position:sticky;top:0;z-index:250;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;padding:.55rem .75rem;background:var(--card,#fff);border-bottom:1px solid rgba(148,163,184,.18)}
.sd-code{font-weight:900;font-size:1rem;color:#111827}
.sd-spacer{flex:1}
.sd-btn,.sd-pill{display:inline-flex;align-items:center;justify-content:center;gap:.35rem;border-radius:7px;border:1px solid rgba(148,163,184,.3);background:transparent;color:#475569;text-decoration:none;font-size:.76rem;padding:.28rem .6rem;min-height:34px}
.sd-btn{font-weight:800}
.sd-btn:hover{background:rgba(148,163,184,.09);color:#111827;text-decoration:none}
.sd-primary{background:#334155!important;border-color:#334155!important;color:#fff!important}
.sd-status{font-weight:850;color:#334155;background:rgba(148,163,184,.08)}
.sd-status.submitted,.sd-status.posted{color:#166534;background:rgba(22,101,52,.08);border-color:rgba(22,101,52,.2)}
.sd-status.cancelled{color:#991b1b;background:rgba(153,27,27,.08);border-color:rgba(153,27,27,.2)}
.sd-flow{display:flex;align-items:center;gap:.35rem;flex-wrap:wrap;margin:.6rem 0;padding:.45rem .55rem;border:1px solid rgba(148,163,184,.18);border-radius:8px;background:var(--card,#fff)}
.sd-step{border:1px solid rgba(148,163,184,.25);border-radius:7px;padding:.18rem .5rem;font-size:.72rem;font-weight:800;color:#64748b}
.sd-step.done{background:rgba(148,163,184,.08);color:#334155}
.sd-step.active{background:#334155;border-color:#334155;color:#fff}
.sd-sep{color:#cbd5e1;font-size:.72rem}
.sd-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.55rem;margin-bottom:.65rem}
.sd-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:8px;overflow:hidden;margin-bottom:.65rem}
.sd-kpi{padding:.65rem .75rem}
.sd-label{font-size:.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.02em}
.sd-value{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:1.18rem;font-weight:900;color:#111827;margin-top:.12rem}
.sd-muted{color:#64748b;font-size:.8rem}
.sd-head{display:flex;align-items:center;gap:.55rem;justify-content:space-between;padding:.7rem .85rem;border-bottom:1px solid rgba(148,163,184,.12)}
.sd-title{font-weight:900;color:#334155}
.sd-body{padding:.75rem .85rem}
.sd-list{display:grid;gap:.45rem}
.sd-order{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:.65rem;border:1px solid rgba(148,163,184,.18);border-radius:8px;padding:.58rem .65rem}
.sd-order-no{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827;word-break:break-word}
.sd-badge{display:inline-flex;align-items:center;justify-content:center;border-radius:7px;border:1px solid rgba(148,163,184,.25);padding:.16rem .48rem;font-size:.69rem;font-weight:900;color:#64748b;white-space:nowrap}
.sd-badge.pending{background:rgba(245,158,11,.08);border-color:rgba(245,158,11,.25);color:#92400e}
.sd-badge.skip{background:rgba(148,163,184,.08)}
.sd-empty{padding:1.6rem 1rem;text-align:center;color:#64748b;font-size:.84rem}
.sd-table-wrap{overflow:auto;border:1px solid rgba(148,163,184,.16);border-radius:8px}
.sd-table{width:100%;border-collapse:collapse}
.sd-table th,.sd-table td{padding:.55rem .65rem;border-bottom:1px solid rgba(148,163,184,.12);vertical-align:middle}
.sd-table th{text-align:left;font-size:.72rem;color:#64748b;font-weight:900;text-transform:uppercase;letter-spacing:.02em;background:rgba(148,163,184,.04)}
.sd-table td{font-size:.86rem;color:#334155}
.sd-code-cell{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827}
.sd-name{color:#64748b;font-size:.8rem;margin-top:.08rem}
.sd-r{text-align:right}
.sd-total td{font-weight:900;color:#111827;background:rgba(148,163,184,.04)}
.sd-group{cursor:pointer;user-select:none}
.sd-group td{background:rgba(148,163,184,.10);font-weight:900;color:#334155}
.sd-group:hover td{background:rgba(148,163,184,.16)}
.sd-caret{display:inline-block;margin-right:.4rem;color:#94a3b8;font-size:.62rem;transition:transform .15s}
.sd-group.is-open .sd-caret{transform:rotate(90deg)}
.sd-group-cat{font-size:.72rem;letter-spacing:.02em}
.sd-group-count{margin-left:.4rem;color:#94a3b8;font-weight:700;font-size:.66rem}
.sd-collapsed{display:none!important}
.sd-meta{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.5rem}
.sd-meta-box{border:1px solid rgba(148,163,184,.16);border-radius:8px;padding:.55rem .65rem}
.sd-order-num{display:inline-flex;align-items:center;justify-content:center;min-width:1.5rem;height:1.5rem;padding:0 .35rem;border-radius:6px;background:rgba(148,163,184,.12);color:#475569;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;font-size:.78rem;flex-shrink:0}
.sd-order-lead{display:flex;align-items:flex-start;gap:.55rem;min-width:0}
.sd-order-group{display:block;padding:.58rem .65rem}
.sd-order-group .sd-order-lead{justify-content:space-between}
.sd-order-qty{margin-left:auto;min-width:42px;text-align:center;border-radius:999px;padding:.2rem .45rem;background:rgba(148,163,184,.12);color:#334155;font-size:.76rem;font-weight:900}
.sd-order-items{margin:.55rem 0 0 2.05rem;padding:.2rem .6rem 0;border-top:1px solid rgba(148,163,184,.14)}
.sd-order-item{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.6rem;align-items:center;padding:.48rem 0;border-bottom:1px solid rgba(148,163,184,.1)}
.sd-order-item:last-child{border-bottom:0}
.sd-order-item-code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827}
.sd-order-item-name{color:#64748b;font-size:.76rem;margin-top:.06rem}
.sd-order-item-qty{color:#334155;font-size:.8rem;font-weight:900}
.sd-order-group>.sd-badge{margin:.5rem 0 0 2.05rem}
.sd-tabs{display:flex;gap:.25rem;margin-bottom:.65rem;border-bottom:1px solid rgba(148,163,184,.18);flex-wrap:wrap}
.sd-tab{appearance:none;display:inline-flex;align-items:center;gap:.4rem;border:none;background:transparent;color:#64748b;font-weight:800;font-size:.82rem;padding:.55rem .8rem;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px}
.sd-tab:hover{color:#334155}
.sd-tab.active{color:#111827;border-bottom-color:#334155}
.sd-tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:1.35rem;height:1.35rem;padding:0 .3rem;border-radius:999px;background:rgba(148,163,184,.16);color:#475569;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.7rem;font-weight:900}
.sd-tab.active .sd-tab-count{background:#334155;color:#fff}
.sd-tabpane{display:none}
.sd-tabpane.active{display:block}
.sd-tabpane .sd-card{margin-bottom:0}
.sd-waves{display:grid;gap:.45rem}
.sd-wave{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:.65rem;border:1px solid rgba(148,163,184,.18);border-radius:8px;padding:.6rem .7rem}
.sd-wave-no{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827}
.sd-wave-title{font-weight:900;color:#334155}
.sd-wave-meta{font-size:.76rem;color:#64748b;margin-top:.08rem}
.sd-wave-status{font-size:.7rem;font-weight:900;border-radius:999px;padding:.2rem .5rem;background:rgba(245,158,11,.08);color:#92400e;border:1px solid rgba(245,158,11,.22)}
.sd-wave-status.posted{background:rgba(22,101,52,.08);color:#166534;border-color:rgba(22,101,52,.2)}
@media(max-width:860px){
  .sd-wrap{padding:.5rem .5rem 3.5rem}
  .sd-topbar{padding:.5rem}
  .sd-code{flex:1;min-width:150px;font-size:1.02rem}
  .sd-topbar .sd-pill:not(.sd-status),.sd-flow,.sd-hide-mobile{display:none}
  .sd-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:.45rem}
  .sd-kpi{padding:.58rem .62rem}
  .sd-value{font-size:1.08rem}
  .sd-head{padding:.65rem .7rem}
  .sd-body{padding:.65rem .7rem}
  .sd-order{grid-template-columns:1fr;align-items:flex-start;gap:.4rem}
  .sd-meta{grid-template-columns:1fr}
  .sd-wave{grid-template-columns:auto minmax(0,1fr)}
  .sd-wave-status{grid-column:2;justify-self:start}
  .sd-table-wrap{border:none;border-radius:0;overflow:visible}
  .sd-table,.sd-table tbody,.sd-table tr,.sd-table td{display:block;width:100%}
  .sd-table thead{display:none}
  .sd-table tr{border:1px solid rgba(148,163,184,.16);border-radius:8px;margin-bottom:.45rem;padding:.55rem .6rem;background:var(--card,#fff)}
  .sd-table td{border:0;padding:0}
  .sd-table td.sd-r{text-align:left;margin-top:.35rem}
  .sd-name,.sd-mobile-muted{display:none}
  .sd-total{display:none!important}
}

/* Shipment detail: satu skala visual dengan index dan scan-order. */
.sd-wrap{
  max-width:960px;
  padding:.55rem .7rem 3.5rem;
}
.sd-topbar{
  position:sticky;
  top:0;
  z-index:250;
  margin:0 0 .6rem;
  padding:.55rem .7rem;
  border:1px solid #dbe3ee;
  border-radius:10px;
  background:linear-gradient(135deg,#fff 0%,#f8fbff 100%);
  box-shadow:0 2px 10px rgba(15,23,42,.035);
}
.sd-code{font-size:1rem;font-weight:750;color:#172554}
.sd-btn,.sd-pill{
  min-height:32px;
  padding:.28rem .58rem;
  border-radius:7px;
  font-size:.7rem;
  font-weight:650;
  line-height:1.2;
}
.sd-btn .bi,.sd-pill .bi{font-size:.82rem;line-height:1;vertical-align:-.08em}
.sd-pill{gap:.28rem}
.sd-status{font-size:.66rem;font-weight:700}
.sd-flow{
  gap:.28rem;
  margin:.4rem 0 .55rem;
  padding:.2rem 0;
  border:0;
  background:transparent;
}
.sd-step{padding:.16rem .45rem;border-radius:6px;font-size:.66rem;font-weight:650}
.sd-sep{font-size:.64rem}
.sd-grid{gap:.4rem;margin-bottom:.55rem}
.sd-card{margin-bottom:.55rem;border-color:#dbe3ee;border-radius:10px;box-shadow:0 2px 10px rgba(15,23,42,.035)}
.sd-kpi{padding:.55rem .65rem}
.sd-label{font-size:.62rem;font-weight:700;letter-spacing:.06em}
.sd-value{margin-top:.08rem;font-size:1rem;font-weight:750}
.sd-head{padding:.58rem .7rem}
.sd-title{font-size:.78rem;font-weight:750}
.sd-muted{font-size:.7rem}
.sd-body{padding:.65rem .7rem}
.sd-tabs{gap:.15rem;margin-bottom:.55rem}
.sd-tab{gap:.3rem;padding:.45rem .65rem;font-size:.72rem;font-weight:700}
.sd-tab-count{min-width:1.2rem;height:1.2rem;font-size:.64rem}
.sd-order{gap:.55rem;padding:.5rem .58rem;border-color:#e2e8f0}
.sd-order-no{font-size:.78rem}
.sd-order-num{min-width:1.35rem;height:1.35rem;font-size:.7rem}
.sd-order-qty{min-width:38px;padding:.16rem .4rem;font-size:.7rem}
.sd-order-items{margin:.45rem 0 0 1.9rem;padding:.15rem .5rem 0}
.sd-order-item{gap:.5rem;padding:.42rem 0}
.sd-order-item-code{font-size:.74rem}
.sd-order-item-name{font-size:.7rem}
.sd-order-item-qty{font-size:.72rem}
.sd-badge{padding:.14rem .42rem;font-size:.64rem;font-weight:700}
.sd-table-wrap{border-color:#e2e8f0;border-radius:8px}
.sd-table th,.sd-table td{padding:.48rem .58rem}
.sd-table th{font-size:.64rem;font-weight:750}
.sd-table td{font-size:.76rem}
.sd-name{font-size:.7rem}
.sd-group-cat{font-size:.68rem}
.sd-group-count{font-size:.62rem}
.sd-wave{gap:.55rem;padding:.52rem .6rem}
.sd-wave-title{font-size:.74rem}
.sd-wave-meta{font-size:.68rem}
.sd-wave-status{font-size:.64rem;padding:.16rem .42rem}

body[data-theme="dark"] .sd-topbar,
body[data-theme="dark"] .sd-card,
body[data-theme="dark"] .sd-table tr{
  border-color:rgba(71,85,105,.75);
  background:#111827;
  box-shadow:none;
}
body[data-theme="dark"] .sd-topbar{background:#111827}
body[data-theme="dark"] .sd-code,
body[data-theme="dark"] .sd-value,
body[data-theme="dark"] .sd-order-no,
body[data-theme="dark"] .sd-order-item-code{color:#f1f5f9}

@media(max-width:860px){
  .sd-wrap{padding:.5rem .5rem 3.5rem}
  .sd-topbar{margin:0 0 .55rem;padding:.5rem .55rem}
  .sd-btn,.sd-pill{font-size:.68rem}
  .sd-code{font-size:.94rem}
  .sd-body{padding:.58rem .6rem}
}

/* Detail page follows the compact operator language used by confirm_orders. */
.sd-topbar,
.sd-wrap {
  --sd-content-width: 1100px;
  box-sizing: border-box;
  width: min(calc(100% - 1.5rem), var(--sd-content-width));
  max-width: var(--sd-content-width);
  margin-inline: auto;
}
.sd-topbar {
  margin-top: .5rem;
  margin-bottom: .55rem;
  padding: .45rem .65rem;
  gap: .35rem;
  border: 1px solid rgba(148,163,184,.18);
  border-radius: 9px;
  background: var(--card,#fff);
  box-shadow: 0 1px 8px rgba(15,23,42,.05);
}
.sd-topbar .sd-btn,
.sd-topbar .sd-pill {
  min-height: 30px;
  padding: .2rem .5rem;
  font-size: .7rem;
}
.sd-topbar .sd-code { font-size: .86rem; }
.sd-topbar .sd-primary {
  color: #fff !important;
  background: #2563eb !important;
  border-color: #2563eb !important;
  font-weight: 900;
  box-shadow: 0 3px 10px rgba(37,99,235,.28);
}
.sd-topbar .sd-primary:hover {
  color: #fff !important;
  background: #1d4ed8 !important;
  border-color: #1d4ed8 !important;
}
.sd-topbar .sd-status {
  color: #9a3412;
  background: #fff7ed;
  border-color: #fdba74;
}
.sd-topbar .sd-pill:not(.sd-status) b { color: #1d4ed8; }
.sd-wrap { padding: .6rem .75rem 1.25rem; }
.sd-flow {
  margin: .15rem 0 .65rem;
  padding: .25rem .05rem;
  border: 0;
  background: transparent;
}
.sd-step {
  border-color: #dbe3ee;
  background: #fff;
  font-size: .66rem;
  font-weight: 750;
}
.sd-step.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #fff;
  box-shadow: 0 2px 7px rgba(37,99,235,.2);
}
.sd-step.done { background: #f8fafc; color: #475569; }
.sd-card {
  border-color: #dbe3ee;
  border-radius: 9px;
  box-shadow: 0 2px 8px rgba(15,23,42,.035);
}
.sd-head { padding: .6rem .75rem; background: #fff; }
.sd-body { padding: .6rem .75rem; }
.sd-title { color: #0f172a; font-size: .9rem; }
.sd-muted { font-size: .72rem; }

/* Replace the old four-card KPI row with the same summary treatment as confirm. */
.sd-grid {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .45rem;
  margin: 0 0 .65rem;
  padding: .5rem .7rem;
  border: 1px solid #e2e8f0;
  border-radius: 9px;
  background: #fff;
  box-shadow: 0 1px 4px rgba(15,23,42,.025);
}
.sd-grid .sd-kpi {
  display: flex;
  align-items: baseline;
  gap: .3rem;
  min-width: 0;
  margin: 0;
  padding: .18rem .45rem;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  background: #f8fafc;
  box-shadow: none;
}
.sd-grid .sd-label {
  color: #64748b;
  font-size: .56rem;
  font-weight: 650;
  letter-spacing: .03em;
}
.sd-grid .sd-value {
  margin: 0;
  color: #475569;
  font-size: .7rem;
  font-weight: 600;
}
.sd-tabs {
  display: grid;
  grid-template-columns: repeat(3, minmax(0,1fr));
  gap: .35rem;
  margin-bottom: .65rem;
  padding: .3rem;
  border: 1px solid rgba(148,163,184,.2);
  border-radius: 9px;
  background: var(--card,#fff);
}
.sd-tab {
  min-height: 38px;
  justify-content: center;
  border: 0;
  border-radius: 7px;
  color: #64748b;
  background: transparent;
  font-size: .76rem;
  font-weight: 850;
}
.sd-tab:hover { color: #1d4ed8; background: #eff6ff; }
.sd-tab.active {
  color: #fff;
  border-bottom-color: transparent;
  background: #2563eb;
  box-shadow: 0 3px 9px rgba(37,99,235,.22);
}
.sd-order-group {
  border-color: #dbe3ee;
  border-radius: 9px;
  background: #fff;
  box-shadow: 0 1px 3px rgba(15,23,42,.025);
}
.sd-order-group > .sd-order-lead {
  padding: .65rem .75rem;
  border-bottom: 1px solid #dbe3ee;
  background: #f8fafc;
}
.sd-order-group .sd-order-items { background: #fff; }
.sd-order-group .sd-order-item { padding: .48rem .7rem; }
.sd-order-filters {
  margin: .1rem 0 .65rem;
  padding: .22rem;
  border: 1px solid #e2e8f0;
  border-radius: 9px;
  background: #f8fafc;
}
.sd-order-search { padding: .35rem .3rem .55rem; }
.sd-order-search input {
  width: 100%;
  min-height: 36px;
  box-sizing: border-box;
  padding: .3rem .55rem;
  border: 1px solid #dbe3ee;
  border-radius: 7px;
  color: #334155;
  background: #fff;
  font-size: .75rem;
}
.sd-order-search input:focus { outline: 0; border-color: #2563eb; box-shadow: 0 0 0 .16rem rgba(37,99,235,.12); }
.sd-order-filter-tabs { display: flex; gap: .3rem; overflow-x: auto; scrollbar-width: none; }
.sd-order-filter-tabs::-webkit-scrollbar { display: none; }
.sd-order-filter-tab {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .3rem;
  min-height: 32px;
  flex: 1 0 0;
  padding: .24rem .55rem;
  border: 1px solid transparent;
  border-radius: 7px;
  color: #64748b;
  background: transparent;
  font-size: .68rem;
  font-weight: 750;
  white-space: nowrap;
  cursor: pointer;
}
.sd-order-filter-tab:hover { color: #334155; background: #f1f5f9; }
.sd-order-filter-tab.active { color: #334155; border-color: #dbe3ee; background: #fff; box-shadow: 0 1px 2px rgba(15,23,42,.06); }
.sd-order-filter-tab[data-marketplace-filter="shopee"].active { color: #c2410c; border-color: #fed7aa; background: #fff7ed; }
.sd-order-filter-tab[data-marketplace-filter="tiktok"].active { color: #0f716e; border-color: #bfe9e4; background: #f0fdfa; }
.sd-order-filter-tab[data-marketplace-filter="anomaly"].active { color: #a16207; border-color: #fde68a; background: #fffbeb; }
.sd-order-filter-tab[data-marketplace-filter="problem"].active { color: #b91c1c; border-color: #fecaca; background: #fff1f2; }
.sd-order-filter-tab[data-link-filter="linked"].active { color: #047857; border-color: #a7f3d0; background: #ecfdf5; }
.sd-order-filter-tab[data-link-filter="unlinked"].active { color: #b45309; border-color: #fde68a; background: #fffbeb; }
.sd-order-filter-count { color: #94a3b8; font-size: .62rem; font-weight: 850; }
.sd-order-filter-divider { margin: .3rem 0 0; padding-top: .3rem; border-top: 1px solid #e2e8f0; }
.sd-order-card[hidden] { display: none !important; }
body[data-theme="dark"] .sd-order-filters,
body[data-theme="dark"] .sd-order-search input,
body[data-theme="dark"] .sd-order-filter-tab.active { background: #111827; border-color: rgba(71,85,105,.75); }
body[data-theme="dark"] .sd-order-filter-divider { border-color: rgba(71,85,105,.75); }

body[data-theme="dark"] .sd-topbar,
body[data-theme="dark"] .sd-card,
body[data-theme="dark"] .sd-grid,
body[data-theme="dark"] .sd-order-group,
body[data-theme="dark"] .sd-tab {
  background: #111827;
  border-color: rgba(71,85,105,.75);
  box-shadow: none;
}
body[data-theme="dark"] .sd-head,
body[data-theme="dark"] .sd-order-group > .sd-order-lead,
body[data-theme="dark"] .sd-order-group .sd-order-items { background: #111827; }
body[data-theme="dark"] .sd-grid .sd-kpi { background: #1e293b; border-color: rgba(71,85,105,.75); }

@media(max-width:860px){
  .sd-topbar,
  .sd-wrap { width: calc(100% - 1rem); }
  .sd-grid { align-items: stretch; flex-wrap: wrap; padding: .4rem; }
  .sd-grid .sd-kpi { flex: 1 1 calc(50% - .45rem); justify-content: center; }
  .sd-tabs { grid-template-columns: repeat(3, minmax(0,1fr)); gap: .2rem; padding: .22rem; }
  .sd-tab { min-height: 36px; font-size: .7rem; }
}
</style>
@endpush

@section('content')
@php
    $canSeeNominal = $canSeeNominal ?? ((auth()->user()->role ?? null) !== 'admin');
    $isCancelled = !empty($shipment->cancelled_at);
    $isPosted = !empty($shipment->posted_at) || ($shipment->status ?? null) === 'posted';
    $isSubmitted = !empty($shipment->submitted_at) || ($shipment->status ?? null) === 'submitted';
    $statusKey = $isCancelled ? 'cancelled' : ($isPosted ? 'posted' : ($isSubmitted ? 'submitted' : 'draft'));
    $statusLabel = [
        'draft' => 'Draft',
        'submitted' => 'Stok Dikurangi',
        'posted' => 'Posted',
        'cancelled' => 'Cancelled',
    ][$statusKey] ?? ucfirst($shipment->status ?? '-');
    $shipDateTxt = $shipment->date ? \Illuminate\Support\Carbon::parse($shipment->date)->format('d M Y') : '-';
    $orderScans = $shipment->orderScans ?? collect();
    $waves = $waves ?? collect();
    $ungroupedLines = $shipment->lines->filter(fn ($line) => !$line->shipment_order_scan_id)->values();
    $singleOrderFallbackLines = $orderScans->count() === 1 ? $ungroupedLines : collect();
    $pendingOrders = $orderScans->where('status', 'pending')->count();
    $skippedOrders = $orderScans->where('status', 'skip')->count();
    $marketplaceGroup = static function ($orderNo): string {
        $code = strtoupper(trim((string) $orderNo));
        if (str_starts_with($code, 'SPX') || str_starts_with($code, 'SHP') || str_contains($code, 'SHOPEE')) {
            return 'shopee';
        }
        if (str_starts_with($code, 'JY') || str_starts_with($code, 'TTK') || str_contains($code, 'TIKTOK')) {
            return 'tiktok';
        }
        return 'anomaly';
    };
    $marketplaceCounts = ['all' => (int) $orderScans->count(), 'shopee' => 0, 'tiktok' => 0, 'anomaly' => 0, 'problem' => 0];
    $linkCounts = ['all' => (int) $orderScans->count(), 'linked' => 0, 'unlinked' => 0];
    foreach ($orderScans as $orderScan) {
        $marketplaceCounts[$marketplaceGroup($orderScan->order_no)]++;
        $orderScanLines = $orderScan->lines->merge($singleOrderFallbackLines);
        if ($orderScanLines->isEmpty()) {
            $marketplaceCounts['problem']++;
        }
        $hasMarketplaceLink = (bool) (
            $orderScan->fulfillment?->marketplaceOrder
            || $orderScan->source === 'marketplace'
            || data_get($orderScan->raw_payload, 'linked_source') === 'marketplace_order'
        );
        $linkCounts[$hasMarketplaceLink ? 'linked' : 'unlinked']++;
    }
    if ($orderScans->count() > 1 && $ungroupedLines->isNotEmpty()) {
        $marketplaceCounts['all']++;
        $marketplaceCounts['anomaly']++;
        $marketplaceCounts['problem']++;
        $linkCounts['all']++;
        $linkCounts['unlinked']++;
    }
    $flowStage = $statusKey === 'draft'
        ? ($orderScans->isNotEmpty() ? 'confirm' : ($shipment->lines->isNotEmpty() ? 'scan_order' : 'scan_item'))
        : 'done';
@endphp

@if(isset($isDummy) && $isDummy)
<div style="background-color: #fff3cd; color: #856404; padding: 10px 15px; border-radius: 6px; border: 1px solid #ffeeba; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;">
    <div>
        <strong>🧪 MENGGUNAKAN DUMMY MODE</strong><br>
        <span style="font-size: 0.9em;">Halaman ini sedang menggunakan data order simulasi. Aksi cetak resi dan lainnya tidak akan berdampak pada production/Shopee.</span>
    </div>
    <a href="?" style="background: #856404; color: #fff; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 0.85em; font-weight: bold;">Keluar Dummy</a>
</div>
@endif

<div class="sd-wrap">
<div class="sd-topbar">
        <a href="{{ route('sales.shipments.index') }}" class="sd-btn"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali</a>
    @if(app()->environment(['local', 'testing']))
    <button class="sd-btn" style="background:#fff3cd;color:#856404;border-color:#ffeeba" onclick="window.location.search = '?dummy=1'">🧪 Test Dummy</button>
    @endif
    <span class="sd-code">{{ $shipment->code }}</span>
    <span class="sd-pill sd-status {{ $statusKey }}">{{ $statusLabel }}</span>
    <span class="sd-spacer"></span>
    <span class="sd-pill"><i class="bi bi-box-seam" aria-hidden="true"></i> Qty <b>{{ number_format($totalQty,0,',','.') }}</b></span>
    <span class="sd-pill"><i class="bi bi-receipt" aria-hidden="true"></i> Pesanan <b>{{ number_format($orderScans->count(),0,',','.') }}</b></span>
    @if($shipment->lines->isNotEmpty())
        <a href="{{ route('sales.shipments.export_lines', $shipment) }}" class="sd-btn sd-hide-mobile"><i class="bi bi-download" aria-hidden="true"></i> Export CSV</a>
    @endif
    @if($statusKey === 'draft')
        <a href="{{ route('sales.shipments.edit', $shipment) }}" class="sd-btn sd-primary"><i class="bi bi-upc-scan" aria-hidden="true"></i> Lanjut Scan</a>
    @endif
</div>

    <div class="sd-flow">
        <span class="sd-step {{ in_array($flowStage, ['scan_order','confirm','done'], true) ? 'done' : 'active' }}">Scan Item</span><span class="sd-sep">-&gt;</span>
        <span class="sd-step {{ in_array($flowStage, ['confirm','done'], true) ? 'done' : ($flowStage === 'scan_order' ? 'active' : '') }}">Scan Pesanan</span><span class="sd-sep">-&gt;</span>
        <span class="sd-step {{ $flowStage === 'done' ? 'done' : ($flowStage === 'confirm' ? 'active' : '') }}">Konfirmasi Pesanan</span><span class="sd-sep">-&gt;</span>
        <span class="sd-step {{ $flowStage === 'done' ? 'active' : '' }}">Simpan &amp; Kurangi Stok</span>
    </div>

    @if($isDaily)
        <div class="sd-card">
            <div class="sd-head">
                <div>
                    <div class="sd-title">Gelombang Pengiriman</div>
                    <div class="sd-muted">Satu shipment harian dapat diposting bertahap per gelombang.</div>
                </div>
                <span class="sd-pill">{{ $waves->count() }} gelombang</span>
            </div>
            <div class="sd-body">
                @if($waves->isEmpty())
                    <div class="sd-empty">Gelombang pertama akan dibuat saat mulai scan.</div>
                @else
                    <div class="sd-waves">
                        @foreach($waves as $wave)
                            @php
                                $waveStatus = $wave->status === \App\Models\ShipmentWave::STATUS_POSTED ? 'posted' : '';
                                $waveStatusLabel = $wave->status === \App\Models\ShipmentWave::STATUS_POSTED
                                    ? 'Sudah diposting'
                                    : ($wave->status === \App\Models\ShipmentWave::STATUS_CANCELLED ? 'Dibatalkan' : 'Terbuka');
                                $waveQty = (int) $wave->lines->sum('qty_scanned');
                                $waveOrders = (int) $wave->orderScans->where('status', '!=', 'skip')->count();
                            @endphp
                            <div class="sd-wave">
                                <div class="sd-wave-no">W{{ str_pad((string) $wave->sequence, 2, '0', STR_PAD_LEFT) }}</div>
                                <div>
                                    <div class="sd-wave-title">{{ $wave->label ?: 'Gelombang ' . $wave->sequence }}</div>
                                    <div class="sd-wave-meta">{{ number_format($waveQty, 0, ',', '.') }} item · {{ number_format($waveOrders, 0, ',', '.') }} pesanan</div>
                                </div>
                                <span class="sd-wave-status {{ $waveStatus }}">{{ $waveStatusLabel }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="sd-grid">
        <div class="sd-card sd-kpi">
            <div class="sd-label">Qty Batch</div>
            <div class="sd-value">{{ number_format($totalQty,0,',','.') }}</div>
        </div>
        <div class="sd-card sd-kpi">
            <div class="sd-label">SKU</div>
            <div class="sd-value">{{ number_format($totalLines,0,',','.') }}</div>
        </div>
        <div class="sd-card sd-kpi">
            <div class="sd-label">Pesanan</div>
            <div class="sd-value">{{ number_format($orderScans->count(),0,',','.') }}</div>
        </div>
        @if($canSeeNominal)
            <div class="sd-card sd-kpi">
                <div class="sd-label">Estimasi HPP</div>
                <div class="sd-value">{{ number_format($totalHpp,0,',','.') }}</div>
            </div>
        @else
            <div class="sd-card sd-kpi">
                <div class="sd-label">Status</div>
                <div class="sd-value" style="font-size:1rem">{{ $statusLabel }}</div>
            </div>
        @endif
    </div>

    <div class="sd-tabs" role="tablist">
        <button type="button" class="sd-tab active" data-tab="pesanan">Pesanan <span class="sd-tab-count">{{ number_format($orderScans->count(),0,',','.') }}</span></button>
        <button type="button" class="sd-tab" data-tab="item">Item Batch <span class="sd-tab-count">{{ number_format($totalLines,0,',','.') }}</span></button>
        <button type="button" class="sd-tab" data-tab="info">Info Shipment</button>
    </div>

    <div class="sd-tabpane active" id="sd-tab-pesanan" role="tabpanel">
    <div class="sd-card">
        <div class="sd-head">
            <div>
                <div class="sd-title">Pesanan Tercatat</div>
                <div class="sd-muted">Nomor pesanan disimpan di shipment. Tautan invoice/order menyusul nanti.</div>
            </div>
            <span class="sd-pill">{{ number_format($pendingOrders,0,',','.') }} tunda</span>
        </div>
        <div class="sd-body">
            @if($orderScans->isEmpty())
                <div class="sd-empty">
                    Belum ada nomor pesanan yang dikonfirmasi.
                    @if($statusKey === 'draft')
                        <div style="margin-top:.55rem">
                            <a href="{{ route('sales.shipments.scan_order', $shipment) }}" class="sd-btn sd-primary"><i class="bi bi-upc-scan" aria-hidden="true"></i> Scan Pesanan</a>
                        </div>
                    @endif
                </div>
            @else
                <div class="sd-order-filters" aria-label="Filter order shipment">
                    <div class="sd-order-search">
                        <label class="visually-hidden" for="sdOrderSearch">Cari nomor order atau resi</label>
                        <input id="sdOrderSearch" type="search" placeholder="Cari nomor order atau resi" autocomplete="off">
                    </div>
                    <div class="sd-order-filter-tabs" role="tablist" aria-label="Filter marketplace">
                        @foreach(['all' => 'Semua', 'shopee' => 'Shopee', 'tiktok' => 'TikTok', 'anomaly' => 'Anomali', 'problem' => 'Perlu Diperiksa'] as $filterKey => $filterLabel)
                            <button type="button" class="sd-order-filter-tab {{ $filterKey === 'all' ? 'active' : '' }}" data-marketplace-filter="{{ $filterKey }}" role="tab" aria-selected="{{ $filterKey === 'all' ? 'true' : 'false' }}">
                                {{ $filterLabel }} <span class="sd-order-filter-count">{{ number_format((int) ($marketplaceCounts[$filterKey] ?? 0), 0, ',', '.') }}</span>
                            </button>
                        @endforeach
                    </div>
                    <div class="sd-order-filter-divider">
                        <div class="sd-order-filter-tabs" role="tablist" aria-label="Filter status tautan order">
                            @foreach(['all' => 'Semua', 'linked' => 'Tertaut', 'unlinked' => 'Belum Tertaut'] as $filterKey => $filterLabel)
                                <button type="button" class="sd-order-filter-tab {{ $filterKey === 'all' ? 'active' : '' }}" data-link-filter="{{ $filterKey }}" role="tab" aria-selected="{{ $filterKey === 'all' ? 'true' : 'false' }}">
                                    {{ $filterLabel }} <span class="sd-order-filter-count">{{ number_format((int) ($linkCounts[$filterKey] ?? 0), 0, ',', '.') }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="sd-list">
                    @foreach($orderScans->sortBy('id') as $scan)
                        @php
                            $scanStatus = $scan->status ?: 'pending';
                            $scanLabel = $scanStatus === 'skip' ? 'Diabaikan' : 'Ditunda';
                            $orderLines = $scan->lines
                                ->merge($singleOrderFallbackLines)
                                ->values();
                            $orderQty = (int) $orderLines->sum('qty_scanned');
                            $orderMarketplace = $marketplaceGroup($scan->order_no);
                            $orderNeedsReview = $orderLines->isEmpty();
                            $orderLinked = (bool) (
                                $scan->fulfillment?->marketplaceOrder
                                || $scan->source === 'marketplace'
                                || data_get($scan->raw_payload, 'linked_source') === 'marketplace_order'
                            );
                        @endphp
                        <div class="sd-order sd-order-group sd-order-card"
                             data-marketplace="{{ $orderMarketplace }}"
                             data-link-status="{{ $orderLinked ? 'linked' : 'unlinked' }}"
                             data-needs-review="{{ $orderNeedsReview ? '1' : '0' }}"
                             data-order-search="{{ strtolower(trim((string) $scan->order_no . ' ' . (string) $scan->source)) }}">
                            <div class="sd-order-lead">
                                <span class="sd-order-num">{{ $loop->iteration }}</span>
                                <div>
                                    <div class="sd-order-no">
                                        {{ $scan->order_no }}
                                        @if($scan->fulfillment && $scan->fulfillment->marketplaceOrder && $scan->fulfillment->marketplaceOrder->store)
                                            <span style="font-size:0.6rem; background:#f1f5f9; padding:2px 6px; border-radius:4px; margin-left:6px; color:#475569; border:1px solid #e2e8f0; vertical-align:middle;">{{ $scan->fulfillment->marketplaceOrder->store->name }}</span>
                                        @endif
                                    </div>
                                    <div class="sd-muted">
                                        {{ $scan->source === 'manual_scan' ? 'Belum tertaut' : $scan->source }}
                                        @if($scan->confirmed_at)
                                            · {{ $scan->confirmed_at->format('d M Y H:i') }}
                                        @endif
                                    </div>
                                </div>
                                <span class="sd-order-qty">x{{ number_format($orderQty,0,',','.') }}</span>
                            </div>
                            <div class="sd-order-items">
                                @forelse($orderLines as $line)
                                    <div class="sd-order-item">
                                        <div>
                                            <div class="sd-order-item-code">{{ $line->item?->code ?? '-' }}</div>
                                            <div class="sd-order-item-name">{{ $line->item?->name ?? '' }}</div>
                                        </div>
                                        <div class="sd-order-item-qty">x{{ number_format((int) $line->qty_scanned,0,',','.') }}</div>
                                    </div>
                                @empty
                                    <div class="sd-muted" style="padding:.45rem 0">Belum ada item di order ini.</div>
                                @endforelse
                            </div>
                            <span class="sd-badge {{ $scanStatus }}">{{ $scanLabel }}</span>
                        </div>
                    @endforeach
                    @if($orderScans->count() > 1 && $ungroupedLines->isNotEmpty())
                        <div class="sd-order sd-order-group sd-order-card"
                             data-marketplace="anomaly"
                             data-link-status="unlinked"
                             data-needs-review="1"
                             data-order-search="belum dikelompokkan item lama scan tanpa order">
                            <div class="sd-order-lead">
                                <span class="sd-order-num">—</span>
                                <div>
                                    <div class="sd-order-no">BELUM DIKELOMPOKKAN</div>
                                    <div class="sd-muted">Item lama atau scan tanpa order</div>
                                </div>
                                <span class="sd-order-qty">x{{ number_format((int) $ungroupedLines->sum('qty_scanned'),0,',','.') }}</span>
                            </div>
                            <div class="sd-order-items">
                                @foreach($ungroupedLines as $line)
                                    <div class="sd-order-item">
                                        <div>
                                            <div class="sd-order-item-code">{{ $line->item?->code ?? '-' }}</div>
                                            <div class="sd-order-item-name">{{ $line->item?->name ?? '' }}</div>
                                        </div>
                                        <div class="sd-order-item-qty">x{{ number_format((int) $line->qty_scanned,0,',','.') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
    </div>{{-- /sd-tab-pesanan --}}

    <div class="sd-tabpane" id="sd-tab-item" role="tabpanel">
    <div class="sd-card">
        <div class="sd-head">
            <div>
                <div class="sd-title">Item Batch</div>
                <div class="sd-muted">Ringkasan barang yang discan untuk shipment ini.</div>
            </div>
            <span class="sd-pill">{{ number_format($totalLines,0,',','.') }} SKU</span>
        </div>
        <div class="sd-body">
            @if($shipment->lines->isEmpty())
                <div class="sd-empty">Belum ada item.</div>
            @else
                @php
                    $linesGrouped = $shipment->lines
                        ->groupBy(fn ($l) => $l->item?->category?->name ?: 'Tanpa Kategori')
                        ->sortKeys();
                @endphp
                <div class="sd-table-wrap">
                    <table class="sd-table" id="sdBatchTable">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="sd-r">Qty</th>
                                @if($canSeeNominal)
                                    <th class="sd-r sd-hide-mobile">Unit HPP</th>
                                    <th class="sd-r sd-hide-mobile">Total HPP</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($linesGrouped as $catName => $group)
                                @php
                                    $gIdx = $loop->index;
                                    $catQty = (int) $group->sum(fn ($l) => (int)($l->qty_scanned ?? 0));
                                    $catHpp = (float) $group->sum(fn ($l) => (float)($l->total_hpp ?? 0));
                                    $catSku = $group->count();
                                @endphp
                                <tr class="sd-group" data-group="{{ $gIdx }}" aria-expanded="false">
                                    <td>
                                        <span class="sd-caret">▶</span>
                                        <span class="sd-group-cat">{{ $catName }}</span>
                                        <span class="sd-group-count">{{ $catSku }} SKU</span>
                                    </td>
                                    <td class="sd-r"><span class="sd-badge">{{ number_format($catQty,0,',','.') }} pcs</span></td>
                                    @if($canSeeNominal)
                                        <td class="sd-r sd-hide-mobile"></td>
                                        <td class="sd-r sd-hide-mobile">{{ number_format($catHpp,0,',','.') }}</td>
                                    @endif
                                </tr>
                                @foreach($group as $line)
                                    <tr class="sd-item-row sd-collapsed" data-group-row="{{ $gIdx }}">
                                        <td>
                                            <div class="sd-code-cell">{{ $line->item?->code ?? '-' }}</div>
                                            <div class="sd-name">{{ $line->item?->name ?? '-' }}</div>
                                        </td>
                                        <td class="sd-r">
                                            <span class="sd-badge">{{ number_format((int)($line->qty_scanned ?? 0),0,',','.') }} pcs</span>
                                        </td>
                                        @if($canSeeNominal)
                                            <td class="sd-r sd-hide-mobile">{{ number_format((float)($line->unit_hpp ?? 0),0,',','.') }}</td>
                                            <td class="sd-r sd-hide-mobile">{{ number_format((float)($line->total_hpp ?? 0),0,',','.') }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            @endforeach
                            <tr class="sd-total">
                                <td class="sd-r">Total</td>
                                <td class="sd-r">{{ number_format($totalQty,0,',','.') }}</td>
                                @if($canSeeNominal)
                                    <td></td>
                                    <td class="sd-r">{{ number_format($totalHpp,0,',','.') }}</td>
                                @endif
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    </div>{{-- /sd-tab-item --}}

    <div class="sd-tabpane" id="sd-tab-info" role="tabpanel">
    <div class="sd-card">
        <div class="sd-head">
            <div class="sd-title">Info Shipment</div>
        </div>
        <div class="sd-body">
            <div class="sd-meta">
                <div class="sd-meta-box">
                    <div class="sd-label">Store</div>
                    <div class="sd-value" style="font-size:.95rem">{{ $shipment->store?->code ?? '-' }}</div>
                    <div class="sd-muted">{{ $shipment->store?->name ?? '-' }}</div>
                </div>
                <div class="sd-meta-box">
                    <div class="sd-label">Tanggal</div>
                    <div class="sd-value" style="font-size:.95rem">{{ $shipDateTxt }}</div>
                    <div class="sd-muted">Dibuat oleh {{ $shipment->creator?->name ?? '-' }}</div>
                </div>
                <div class="sd-meta-box">
                    <div class="sd-label">Submit</div>
                    <div class="sd-value" style="font-size:.95rem">{{ $shipment->submitted_at ? $shipment->submitted_at->format('d M Y H:i') : '-' }}</div>
                    <div class="sd-muted">{{ $shipment->submitter?->name ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>
    </div>{{-- /sd-tab-info --}}
</div>

<script>
(function(){
    var tabs = document.querySelectorAll('.sd-tab');
    var panes = document.querySelectorAll('.sd-tabpane');
    function activate(name){
        tabs.forEach(function(t){ t.classList.toggle('active', t.dataset.tab === name); });
        panes.forEach(function(p){ p.classList.toggle('active', p.id === 'sd-tab-' + name); });
        try { history.replaceState(null, '', '#' + name); } catch(e){}
    }
    tabs.forEach(function(t){
        t.addEventListener('click', function(){ activate(t.dataset.tab); });
    });
    var hash = (location.hash || '').replace('#','');
    if (['pesanan','item','info'].indexOf(hash) !== -1) activate(hash);

    /* ── filter order grouping, mengikuti halaman confirm ── */
    var orderCards = Array.prototype.slice.call(document.querySelectorAll('.sd-order-card'));
    var marketplaceFilter = 'all';
    var linkFilter = 'all';
    var orderSearch = document.getElementById('sdOrderSearch');
    function applyOrderFilters(){
        var query = orderSearch ? orderSearch.value.trim().toLowerCase() : '';
        orderCards.forEach(function(card){
            var marketplace = card.dataset.marketplace || 'anomaly';
            var linkStatus = card.dataset.linkStatus || 'unlinked';
            var needsReview = card.dataset.needsReview === '1';
            var text = card.dataset.orderSearch || card.textContent.toLowerCase();
            var marketplaceMatch = marketplaceFilter === 'all'
                || marketplace === marketplaceFilter
                || (marketplaceFilter === 'problem' && needsReview);
            var linkMatch = linkFilter === 'all' || linkStatus === linkFilter;
            var searchMatch = !query || text.indexOf(query) !== -1;
            card.hidden = !(marketplaceMatch && linkMatch && searchMatch);
        });
    }
    document.querySelectorAll('[data-marketplace-filter]').forEach(function(button){
        button.addEventListener('click', function(){
            marketplaceFilter = button.dataset.marketplaceFilter || 'all';
            document.querySelectorAll('[data-marketplace-filter]').forEach(function(item){
                var active = item === button;
                item.classList.toggle('active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            applyOrderFilters();
        });
    });
    document.querySelectorAll('[data-link-filter]').forEach(function(button){
        button.addEventListener('click', function(){
            linkFilter = button.dataset.linkFilter || 'all';
            document.querySelectorAll('[data-link-filter]').forEach(function(item){
                var active = item === button;
                item.classList.toggle('active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            applyOrderFilters();
        });
    });
    if (orderSearch) orderSearch.addEventListener('input', applyOrderFilters);

    /* ── accordion Item Batch per kategori (default tertutup) ── */
    var batchTable = document.getElementById('sdBatchTable');
    if (batchTable) {
        batchTable.querySelectorAll('.sd-group').forEach(function (header) {
            header.addEventListener('click', function () {
                var idx = header.dataset.group;
                var open = header.classList.toggle('is-open');
                header.setAttribute('aria-expanded', open ? 'true' : 'false');
                batchTable.querySelectorAll('.sd-item-row[data-group-row="' + idx + '"]').forEach(function (row) {
                    row.classList.toggle('sd-collapsed', !open);
                });
            });
        });
    }
})();
</script>
@endsection

@if (session('stock_insufficient'))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const items = @json(session('stock_insufficient'));
    if (!window.GFID || typeof window.GFID.errorAlert !== 'function') return;
    const codes = items.map(item => item.code).filter(Boolean).join(', ');
    window.GFID.errorAlert(
        codes
            ? `Stok WH-RTS belum cukup untuk: ${codes}.`
            : 'Stok WH-RTS belum cukup untuk shipment ini.'
    );
});
</script>
@endpush
@endif

@if(($shipment->status ?? null) !== 'draft')
@push('scripts')
<script>
try {
    localStorage.removeItem('rk_state_{{ $shipment->id }}');
} catch {}
</script>
@endpush
@endif
