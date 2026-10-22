{{-- resources/views/sales/shipments/confirm_orders.blade.php --}}
@extends('layouts.app')

@section('title', 'Konfirmasi Shipment · ' . $shipment->code)

@push('head')
<style>
.sd-wrap{max-width:1040px;margin-inline:auto;padding:.7rem .75rem 1.5rem}
.sd-topbar{position:sticky;top:0;z-index:250;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;padding:.55rem .75rem;background:var(--card,#fff);border-bottom:1px solid rgba(148,163,184,.18)}
.sd-code{font-weight:900;font-size:1rem;color:#111827}
.sd-spacer{flex:1}
.sd-btn,.sd-pill{display:inline-flex;align-items:center;justify-content:center;gap:.35rem;border-radius:7px;border:1px solid rgba(148,163,184,.3);background:transparent;color:#475569;text-decoration:none;font-size:.76rem;padding:.28rem .6rem;min-height:34px}
.sd-btn{font-weight:800;cursor:pointer}.sd-btn:disabled{opacity:.45;cursor:not-allowed;pointer-events:none}
.sd-btn:hover{background:rgba(148,163,184,.09);color:#111827;text-decoration:none}
.sd-primary{background:#334155!important;border-color:#334155!important;color:#fff!important}
.sd-link{color:#1d4ed8;border-color:rgba(37,99,235,.35);background:rgba(239,246,255,.7)}
.sd-link:hover{color:#1e40af;background:rgba(219,234,254,.9)}
.sd-danger{color:#991b1b;border-color:rgba(153,27,27,.25);background:transparent}
.sd-status{font-weight:850;color:#334155;background:rgba(148,163,184,.08)}
.sd-flash{max-width:1040px;margin:.65rem auto 0;padding:.65rem .8rem;border:1px solid rgba(37,99,235,.2);border-radius:8px;background:#eff6ff;color:#1e40af;font-size:.78rem;font-weight:700}
.sd-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:8px;overflow:hidden;margin-bottom:.65rem}
.sd-muted{color:#64748b;font-size:.8rem}
.sd-head{display:flex;align-items:center;gap:.55rem;justify-content:space-between;padding:.7rem .85rem;border-bottom:1px solid rgba(148,163,184,.12)}
.sd-head-actions{display:flex;align-items:center;justify-content:flex-end;gap:.4rem;flex-wrap:wrap}
.sd-head-actions form{margin:0}
.sd-sync-btn{color:#1e40af;background:#eff6ff;border-color:#bfdbfe;font-weight:850}
.sd-sync-btn:hover{color:#1e3a8a;background:#dbeafe;border-color:#93c5fd}
.sd-sync-btn.is-loading{cursor:wait;color:#1e40af;background:#dbeafe;border-color:#93c5fd}
.sd-sync-spinner{width:.85em;height:.85em;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:sd-sync-spin .7s linear infinite;flex:0 0 auto}
@keyframes sd-sync-spin{to{transform:rotate(360deg)}}
.sd-title{font-weight:900;color:#334155}
.sd-body{padding:.75rem .85rem}
.sd-order-search{padding:.6rem .85rem;border-bottom:1px solid rgba(148,163,184,.12)}.sd-order-search input{width:100%;min-height:36px;border:1px solid rgba(148,163,184,.3);border-radius:7px;padding:.35rem .6rem;color:#334155;background:var(--card,#fff);font-size:.78rem}.sd-order-search input:focus{outline:0;border-color:#334155;box-shadow:0 0 0 .16rem rgba(51,65,85,.1)}
.sd-list{display:grid;gap:.45rem}
.sd-order{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:.65rem;border:1px solid rgba(148,163,184,.18);border-radius:8px;padding:.58rem .65rem}
.sd-order{background:transparent;border-color:transparent}
.sd-order-no{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827;word-break:break-word}
.sd-order-identifiers{display:flex;flex-wrap:wrap;gap:.1rem .65rem;margin-top:.18rem;color:#64748b;font-size:.68rem;line-height:1.4}
.sd-order-identifier{display:inline-flex;align-items:baseline;gap:.25rem;min-width:0}
.sd-order-identifier-value{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;color:#475569;overflow-wrap:anywhere}
.sd-copy-value{display:inline-flex;align-items:center;gap:.3rem;max-width:100%;padding:0;border:0;background:transparent;color:inherit;font:inherit;line-height:inherit;text-align:left;overflow-wrap:anywhere;cursor:pointer}
.sd-copy-value:hover{text-decoration:underline;text-decoration-style:dotted;text-underline-offset:.18em}
.sd-copy-value:focus-visible{outline:2px solid #64748b;outline-offset:3px;border-radius:2px}
.sd-copy-identifier{padding:.12rem .3rem;border:1px solid rgba(148,163,184,.35);border-radius:5px;background:rgba(255,255,255,.7);font-size:.68rem}
.sd-copy-identifier:hover{border-color:#94a3b8;background:#fff;text-decoration:none}
.sd-copy-action,.sd-copy-confirmation{display:inline-flex;align-items:center;gap:.18rem;font-family:inherit;font-size:.62rem;font-weight:700;white-space:nowrap}
.sd-copy-action{color:#64748b}
.sd-copy-confirmation{color:#15803d}
.sd-copy-action[hidden],.sd-copy-confirmation[hidden]{display:none}
.sd-copy-toast{position:fixed;right:1rem;bottom:1rem;z-index:1000;padding:.45rem .65rem;border:1px solid #cbd5e1;border-radius:7px;background:#fff;color:#334155;box-shadow:0 6px 20px rgba(15,23,42,.12);font-size:.75rem;font-weight:650}
.sd-copy-toast[hidden]{display:none}
.sd-order-num{display:inline-flex;align-items:center;justify-content:center;min-width:1.5rem;height:1.5rem;padding:0 .35rem;border-radius:6px;background:rgba(148,163,184,.12);color:#475569;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;font-size:.78rem;flex-shrink:0}
.sd-order-lead{display:flex;align-items:flex-start;gap:.55rem;min-width:0}
.sd-order-group{display:block;padding:0;border:1px solid rgba(148,163,184,.2);border-radius:9px;overflow:hidden;background:#fff}
.sd-order-group > .sd-order-lead{padding:.58rem .65rem;border:0;border-bottom:1px solid rgba(37,99,235,.16);border-radius:0;background:#eff6ff}
.sd-order-group .sd-order-lead{justify-content:space-between}
.sd-order-qty{margin-left:auto;min-width:42px;text-align:center;border-radius:999px;padding:.2rem .45rem;background:rgba(148,163,184,.12);color:#334155;font-size:.76rem;font-weight:900}
.sd-order-items{margin:0;padding:.2rem .6rem;background:#f8fafc}
.sd-order-item{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:.6rem;align-items:center;padding:.48rem 0;border-bottom:1px solid rgba(148,163,184,.1)}
.sd-order-item:last-child{border-bottom:0}
.sd-order-item-num{display:inline-flex;align-items:center;justify-content:center;min-width:1.35rem;height:1.35rem;padding:0 .3rem;border-radius:5px;background:rgba(148,163,184,.14);color:#475569;font-size:.72rem;font-weight:900}
.sd-order-item-code{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:500;color:#334155}
.sd-order-item-name{color:#64748b;font-size:.76rem;margin-top:.06rem}
.sd-order-item-qty{color:#334155;font-size:.8rem;font-weight:900}
.sd-marketplace-detail{margin:.7rem 0 0 2.05rem;padding:.7rem .75rem;border:1px solid rgba(37,99,235,.16);border-radius:8px;background:rgba(239,246,255,.55)}
.sd-marketplace-head{display:flex;align-items:flex-start;justify-content:space-between;gap:.6rem;flex-wrap:wrap;margin-bottom:.55rem}
.sd-marketplace-title{font-size:.72rem;font-weight:900;text-transform:uppercase;letter-spacing:.04em;color:#1e40af}
.sd-marketplace-meta{display:flex;gap:.4rem .75rem;flex-wrap:wrap;margin-top:.15rem;color:#64748b;font-size:.72rem}
.sd-marketplace-actions{display:flex;gap:.35rem;flex-wrap:wrap}
.sd-marketplace-actions a{min-height:28px;padding:.18rem .5rem;font-size:.7rem}
.sd-marketplace-table{width:100%;border-collapse:collapse}
.sd-marketplace-table th,.sd-marketplace-table td{padding:.38rem .4rem;border-bottom:1px solid rgba(37,99,235,.1);font-size:.73rem;vertical-align:middle}
.sd-marketplace-table th{text-align:left;color:#64748b;font-size:.64rem;text-transform:uppercase;letter-spacing:.03em}
.sd-marketplace-table td{color:#334155}.sd-marketplace-table tr:last-child td{border-bottom:0}
.sd-marketplace-table .sd-marketplace-code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#0f172a}
.sd-marketplace-table .sd-marketplace-number{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
.sd-marketplace-short{color:#b91c1c;font-weight:900}.sd-marketplace-ok{color:#15803d;font-weight:900}
body[data-theme="dark"] .sd-marketplace-detail{background:rgba(30,64,175,.14);border-color:rgba(96,165,250,.35)}
body[data-theme="dark"] .sd-marketplace-title{color:#bfdbfe}body[data-theme="dark"] .sd-marketplace-table td{color:#cbd5e1}body[data-theme="dark"] .sd-marketplace-table .sd-marketplace-code{color:#f1f5f9}
.sd-badge{display:inline-flex;align-items:center;justify-content:center;border-radius:7px;border:1px solid rgba(148,163,184,.25);padding:.16rem .48rem;font-size:.69rem;font-weight:900;color:#64748b;white-space:nowrap}
.sd-badge.pending{background:rgba(245,158,11,.08);border-color:rgba(245,158,11,.25);color:#92400e}
.sd-badge.linked{background:#ecfdf5;border-color:#a7f3d0;color:#047857}
.sd-badge.unlinked{background:#f8fafc;border-color:#cbd5e1;color:#64748b}
.sd-link-status{display:flex;align-items:center;justify-content:flex-end;gap:.35rem;flex-wrap:wrap}
.sd-booking-status{color:#9a3412;font-size:.67rem;font-weight:700;white-space:nowrap}
.sd-sku-mismatch{display:block;margin-top:.12rem;color:#b45309;font-family:inherit;font-size:.65rem;font-weight:700;white-space:normal}
.sd-order-item-marketplace-sku.is-mismatch{overflow:visible;white-space:normal}
.sd-sku-unmatched{padding:.4rem .1rem .2rem;color:#92400e;font-size:.7rem}
.sd-duplicate-badge{display:inline-flex;align-items:center;justify-content:center;margin-left:.35rem;border:1px solid #fcd34d;border-radius:999px;padding:.12rem .4rem;background:#fffbeb;color:#a16207;font-size:.62rem;font-weight:850;line-height:1;vertical-align:middle}
.sd-missing-item-badge{display:inline-flex;align-items:center;justify-content:center;margin-left:.35rem;border:1px solid #cbd5e1;border-radius:999px;padding:.12rem .4rem;background:#f8fafc;color:#64748b;font-size:.62rem;font-weight:750;line-height:1;vertical-align:middle}
.sd-empty{padding:1.6rem 1rem;text-align:center;color:#64748b;font-size:.84rem}
.sd-info-banner{display:flex;align-items:center;gap:.45rem;margin-bottom:.65rem;padding:.62rem .75rem;border:1px solid rgba(37,99,235,.2);border-radius:8px;background:rgba(239,246,255,.8);color:#1e40af;font-size:.78rem;font-weight:700}
.sd-stock-warning{margin-bottom:.65rem;border:1px solid rgba(220,38,38,.24);border-radius:8px;background:rgba(254,242,242,.72);overflow:hidden}
.sd-stock-head{padding:.65rem .75rem;border-bottom:1px solid rgba(220,38,38,.16);color:#991b1b;font-size:.8rem;font-weight:900}
.sd-stock-table-wrap{overflow:auto}.sd-stock-table{width:100%;border-collapse:collapse}.sd-stock-table th,.sd-stock-table td{padding:.5rem .65rem;border-bottom:1px solid rgba(220,38,38,.12);font-size:.78rem}.sd-stock-table th{text-align:left;color:#991b1b;font-size:.68rem;text-transform:uppercase;letter-spacing:.02em;background:rgba(254,226,226,.45)}.sd-stock-table td{color:#475569}.sd-stock-table tr:last-child td{border-bottom:0}.sd-stock-table .sd-stock-code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827}.sd-stock-table .sd-stock-number{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}.sd-stock-table .sd-stock-short{color:#dc2626;font-weight:900}
@media(max-width:860px){
  .sd-wrap{padding:.5rem .5rem 1.5rem}.sd-topbar{padding:.5rem}.sd-code{flex:1;min-width:150px;font-size:1.02rem}.sd-head{padding:.65rem .7rem}.sd-body{padding:.65rem .7rem}.sd-order{grid-template-columns:1fr;align-items:flex-start;gap:.4rem}
}
.sd-topbar .sd-topbar-nav { white-space:nowrap; }
.sd-inline-form { display:contents; }
.sd-topbar .sd-topbar-primary { white-space:nowrap; }
@media(max-width:860px){
  .sd-topbar .sd-topbar-nav { min-height:34px; padding-inline:.5rem; }
  .sd-topbar .sd-topbar-primary { width:100%; order:5; }
}

/* Visual hierarchy: the submit action must be the clearest control. */
.sd-topbar .sd-topbar-primary {
  color:#fff!important;
  background:#2563eb!important;
  border-color:#2563eb!important;
  font-weight:900;
  box-shadow:0 3px 10px rgba(37,99,235,.28);
}
.sd-topbar .sd-topbar-primary:hover:not(:disabled) {
  color:#fff!important;
  background:#1d4ed8!important;
  border-color:#1d4ed8!important;
}
.sd-topbar .sd-topbar-primary:disabled {
  color:#64748b!important;
  background:#e2e8f0!important;
  border-color:#cbd5e1!important;
  box-shadow:none;
}
.sd-topbar .sd-topbar-edit {
  color:#1d4ed8;
  background:#eff6ff;
  border-color:#bfdbfe;
  font-weight:850;
}
.sd-topbar .sd-topbar-edit:hover {
  color:#1e40af;
  background:#dbeafe;
  border-color:#93c5fd;
}
.sd-topbar .sd-status {
  color:#9a3412!important;
  background:#fff7ed!important;
  border-color:#fdba74!important;
}
.sd-topbar .sd-pill:not(.sd-status) b { color:#1d4ed8; }
.sd-order-search input:focus {
  border-color:#2563eb;
  box-shadow:0 0 0 .16rem rgba(37,99,235,.14);
}
.sd-title { color:#0f172a; font-size:.9rem; }
.sd-order-no { color:#0f172a; }
.sd-tabs { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.35rem; margin-bottom:.65rem; padding:.3rem; border:1px solid rgba(148,163,184,.2); border-radius:9px; background:var(--card,#fff); }
.sd-tab-btn { display:inline-flex; align-items:center; justify-content:center; gap:.35rem; min-height:38px; border:0; border-radius:7px; color:#64748b; background:transparent; font-size:.76rem; font-weight:850; cursor:pointer; }
.sd-tab-btn:hover { color:#1d4ed8; background:#eff6ff; }
.sd-tab-btn.active { color:#fff; background:#2563eb; box-shadow:0 3px 9px rgba(37,99,235,.22); }
.sd-tab-panel[hidden] { display:none!important; }
.sd-marketplace-tabs { display:flex; gap:.3rem; margin:.1rem .75rem .65rem; padding:.22rem; border:1px solid #e2e8f0; border-radius:9px; background:#f8fafc; overflow-x:auto; scrollbar-width:none; }
.sd-marketplace-tabs::-webkit-scrollbar { display:none; }
.sd-marketplace-tab { display:inline-flex; align-items:center; justify-content:center; gap:.32rem; min-height:32px; flex:1 0 0; padding:.24rem .58rem; border:1px solid transparent; border-radius:7px; color:#64748b; background:transparent; font-size:.68rem; font-weight:750; white-space:nowrap; cursor:pointer; transition:background .12s ease,color .12s ease,border-color .12s ease,box-shadow .12s ease; }
.sd-marketplace-tab:hover { color:#334155; background:#f1f5f9; }
.sd-marketplace-tab.active { color:#334155; border-color:#dbe3ee; background:#fff; box-shadow:0 1px 2px rgba(15,23,42,.06); }
.sd-marketplace-tab-count { color:#94a3b8; font-size:.64rem; font-weight:850; }
.sd-marketplace-tab::before { content:''; width:5px; height:5px; border-radius:999px; background:#94a3b8; }
.sd-marketplace-tab[data-order-marketplace-tab="problem"]::before { background:#dc2626; }
.sd-marketplace-tab[data-order-marketplace-tab="problem"].active { color:#b91c1c; border-color:#fecaca; background:#fff1f2; }
.sd-filter-stack { margin:.1rem .75rem .65rem; padding:.22rem; border:1px solid #e2e8f0; border-radius:9px; background:#f8fafc; }
.sd-filter-stack .sd-marketplace-tabs { margin:0; padding:0; border:0; background:transparent; }
.sd-link-tabs { display:flex; align-items:center; gap:.3rem; margin:.32rem 0 0; padding:.32rem 0 0; border-top:1px solid #e2e8f0; overflow-x:auto; scrollbar-width:none; }
.sd-link-tabs::-webkit-scrollbar { display:none; }
.sd-link-tab { display:inline-flex; align-items:center; justify-content:center; gap:.3rem; min-height:32px; flex:1 0 0; padding:.24rem .58rem; border:1px solid transparent; border-radius:7px; color:#64748b; background:transparent; font-size:.68rem; font-weight:750; white-space:nowrap; cursor:pointer; transition:background .12s ease,color .12s ease,border-color .12s ease,box-shadow .12s ease; }
.sd-link-tab:hover { color:#334155; background:#f1f5f9; }
.sd-link-tab.active { color:#334155; border-color:#dbe3ee; background:#fff; box-shadow:0 1px 2px rgba(15,23,42,.05); }
.sd-link-tab[data-order-link-tab="linked"].active { color:#047857; border-color:#a7f3d0; background:#ecfdf5; }
.sd-link-tab[data-order-link-tab="unlinked"].active { color:#b45309; border-color:#fde68a; background:#fffbeb; }
.sd-link-tab-count { color:#94a3b8; font-size:.62rem; font-weight:850; }
.sd-item-list { display:grid; gap:.45rem; }
.sd-item-row { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:.65rem; align-items:center; padding:.6rem .65rem; border:1px solid rgba(148,163,184,.18); border-radius:8px; }
.sd-item-code { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#334155; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:.8rem; font-weight:500; }
.sd-item-name { margin-top:.1rem; color:#64748b; font-size:.74rem; }
.sd-item-map { margin-top:.25rem; color:#2563eb; font-size:.68rem; font-weight:800; }
.sd-item-qty { min-width:48px; padding:.22rem .45rem; border-radius:999px; color:#334155; background:rgba(148,163,184,.12); font-size:.76rem; font-weight:900; text-align:center; }
@media(max-width:600px){
  .sd-tabs,.sd-tabs.has-unlinked{grid-template-columns:repeat(2, minmax(0, 1fr));gap:.2rem;padding:.22rem}.sd-tab-btn{min-height:36px;font-size:.7rem}.sd-tab-btn i{display:none}
}

/* Confirm page: compact operator view. */
.sd-topbar,
.sd-flash,
.sd-wrap {
  --sd-content-width: 1100px;
  box-sizing: border-box;
  width: min(calc(100% - 1.5rem), var(--sd-content-width));
  max-width: var(--sd-content-width);
  margin-inline: auto;
}
.sd-topbar {
  margin-top: .5rem;
  border: 1px solid rgba(148,163,184,.18);
  border-radius: 9px;
}
.sd-flash {
  margin-top: .65rem;
}
.sd-wrap {
  padding-inline: 0;
}
.sd-topbar{gap:.35rem;padding:.45rem .65rem;box-shadow:0 1px 8px rgba(15,23,42,.05)}
.sd-topbar .sd-btn,.sd-topbar .sd-pill{min-height:30px;padding:.2rem .5rem;font-size:.7rem}
.sd-topbar .sd-code{font-size:.86rem}
.sd-wrap{max-width:1100px;padding:.6rem .75rem 1.25rem}
.sd-card{border-radius:9px;box-shadow:0 1px 5px rgba(15,23,42,.03)}
.sd-head{padding:.6rem .75rem}
.sd-body{padding:.6rem .75rem}
.sd-order-search{padding:.45rem .75rem}
.sd-order-search input{min-height:32px;padding:.28rem .5rem}
.sd-order{padding:.5rem .6rem}
.sd-order-items{margin-left:0}
.sd-marketplace-detail{margin:.55rem 0 0 1.8rem;padding:.55rem .65rem}
.sd-marketplace-table th,.sd-marketplace-table td{padding:.32rem .35rem}
@media(max-width:860px){
  .sd-topbar,
  .sd-flash,
  .sd-wrap {
    width: calc(100% - 1rem);
  }
}

/* Enterprise order/item layout: keep confirm aligned with the scan-order editor. */
.sd-card {
  border-color: #dbe3ee;
  box-shadow: 0 2px 8px rgba(15,23,42,.035);
}
.sd-head {
  padding: .7rem .8rem;
  background: #fff;
}
.sd-order-search {
  padding: .55rem .75rem;
  background: #fff;
}
.sd-order-search input {
  min-height: 40px;
  border-color: #dbe3ee;
  background: #f8fafc;
}
.sd-order-group {
  border-color: #dbe3ee;
  box-shadow: 0 1px 3px rgba(15,23,42,.025);
}
.sd-order-group > .sd-order-lead {
  padding: .65rem .75rem;
  border-bottom-color: #dbe3ee;
  background: #f8fafc;
}
.sd-order-qty {
  display: inline-flex;
  align-items: baseline;
  justify-content: center;
  gap: .25rem;
  min-width: 62px;
  padding: .28rem .5rem;
  border: 1px solid #dbe3ee;
  border-radius: 7px;
  background: #fff;
}
.sd-order-qty-label {
  color: #64748b;
  font-size: .6rem;
  font-weight: 750;
  letter-spacing: .03em;
  text-transform: uppercase;
}
.sd-order-qty-value {
  color: #0f172a;
  font-size: .82rem;
  font-variant-numeric: tabular-nums;
}
.sd-order-cogs {
  display: inline-flex;
  align-items: baseline;
  justify-content: center;
  gap: .25rem;
  min-width: 96px;
  padding: .28rem .5rem;
  border: 1px solid #dbe3ee;
  border-radius: 7px;
  color: #475569;
  background: #f8fafc;
  font-size: .75rem;
  font-variant-numeric: tabular-nums;
  font-weight: 800;
  white-space: nowrap;
}
.sd-order-cogs-label {
  color: #64748b;
  font-size: .6rem;
  font-weight: 750;
  letter-spacing: .03em;
  text-transform: uppercase;
}
.sd-order-cogs-value { color: #0f172a; }
.sd-order-summary {
  display: grid;
  grid-template-columns: repeat(3, minmax(0,1fr));
  align-items: center;
  gap: 0;
  padding: .5rem .7rem;
  border-top: 1px solid #eef2f7;
  background: #fff;
  color: #94a3b8;
  font-size: .68rem;
}
.sd-order-summary.sd-summary-has-cogs { grid-template-columns: repeat(4, minmax(0,1fr)); }
.sd-order-summary-item {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: .08rem;
  min-width: 0;
  padding: 0 .65rem;
  color: #94a3b8;
  font-size: .56rem;
  font-weight: 650;
  letter-spacing: .04em;
  text-transform: uppercase;
  white-space: nowrap;
}
.sd-order-summary-item:first-child { padding-left: 0; }
.sd-order-summary-item:last-child { padding-right: 0; }
.sd-order-summary-item strong {
  color: #475569;
  font-size: .76rem;
  font-weight: 550;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0;
  text-transform: none;
}
.sd-order-summary-item + .sd-order-summary-item {
  border-left: 1px solid #f1f5f9;
}
.sd-order-head-side {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: .35rem;
  flex-shrink: 0;
  max-width: 58%;
}
.sd-order-summary-inline {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  width: auto;
  padding: 0;
  border: 0;
  background: transparent;
  gap: .3rem .65rem;
}
.sd-order-summary-inline.sd-summary-has-cogs {
  display: flex;
}
.sd-order-summary-inline .sd-order-summary-item {
  flex-direction: row;
  align-items: baseline;
  gap: .2rem;
  padding: .18rem .45rem;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  background: #f8fafc;
  color: #64748b;
  white-space: nowrap;
}
.sd-order-summary-inline .sd-order-summary-item strong {
  color: #475569;
  font-size: .68rem;
  font-weight: 600;
}
.sd-order-summary-inline .sd-order-summary-item + .sd-order-summary-item {
  border-left: 1px solid #e2e8f0;
}
.sd-receipt-total {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
  margin-top: .65rem;
  padding: .55rem .7rem;
  border: 1px solid #e2e8f0;
  border-radius: 9px;
  background: #fff;
  box-shadow: 0 1px 4px rgba(15,23,42,.025);
}
.sd-receipt-total-top {
  margin-top: 0;
  margin-bottom: .65rem;
}
.sd-receipt-total-title {
  color: #64748b;
  font-size: .62rem;
  font-weight: 800;
  letter-spacing: .05em;
  text-transform: uppercase;
  white-space: nowrap;
}
.sd-receipt-total-badges {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: .3rem .55rem;
}
.sd-receipt-total-badge {
  display: flex;
  align-items: baseline;
  gap: .2rem;
  padding: .18rem .45rem;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  background: #f8fafc;
  color: #64748b;
  font-size: .56rem;
  font-weight: 650;
  letter-spacing: .03em;
  text-transform: uppercase;
  white-space: nowrap;
}
.sd-receipt-total-badge strong {
  color: #475569;
  font-size: .7rem;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0;
  text-transform: none;
}
@media(max-width:600px){
  .sd-order-summary {
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: .55rem 0;
    padding: .5rem .6rem;
  }
  .sd-order-summary.sd-summary-has-cogs { grid-template-columns: repeat(2, minmax(0,1fr)); }
  .sd-order-summary-item { min-width: 0; padding-inline: .55rem; }
  .sd-order-summary-item:nth-child(odd) { padding-left: 0; }
  .sd-order-summary-item:nth-child(even) { padding-right: 0; }
  .sd-order-summary-item:nth-child(3) { border-left: 0; }
  .sd-order-head-side {
    align-items: stretch;
    max-width: 100%;
  }
  .sd-order-summary-inline,
  .sd-order-summary-inline.sd-summary-has-cogs {
    display: flex;
    justify-content: flex-end;
    gap: .25rem .55rem;
  }
  .sd-order-summary-inline .sd-order-summary-item {
    flex-direction: row;
    align-items: baseline;
    padding-inline: .4rem;
  }
  .sd-receipt-total { align-items: flex-start; flex-direction: column; gap: .4rem; }
  .sd-receipt-total-badges { justify-content: flex-start; width: 100%; }
}
.sd-order-items {
  padding: 0;
  background: #fff;
}
.sd-items-head {
  display: grid;
  grid-template-columns: 30px minmax(0,1fr) 52px;
  align-items: center;
  gap: .75rem;
  padding: .45rem .7rem;
  border-bottom: 1px solid #dbe3ee;
  background: #f8fafc;
  color: #64748b;
  font-size: .62rem;
  font-weight: 850;
  letter-spacing: .05em;
  text-transform: uppercase;
}
.sd-items-head.sd-has-actions { grid-template-columns: 30px minmax(0,1fr) 52px 30px; }
.sd-items-head.sd-has-cogs { grid-template-columns: 30px minmax(0,1fr) 52px 86px 110px; }
.sd-items-head.sd-has-cogs.sd-has-actions { grid-template-columns: 30px minmax(0,1fr) 52px 86px 110px 30px; }
.sd-items-head span:nth-child(n+3) { text-align: right; }
.sd-items-head span:last-child { text-align: center; }
.sd-order-item {
  grid-template-columns: 30px minmax(0,1fr) 52px;
  gap: .75rem;
  padding: .48rem .7rem;
  border-bottom-color: #edf1f6;
  background: #fff;
  transition: background .12s ease;
}
.sd-order-item.sd-has-actions { grid-template-columns: 30px minmax(0,1fr) 52px 30px; }
.sd-order-item.sd-has-cogs { grid-template-columns: 30px minmax(0,1fr) 52px 86px 110px; }
.sd-order-item.sd-has-cogs.sd-has-actions { grid-template-columns: 30px minmax(0,1fr) 52px 86px 110px 30px; }
.sd-items-head.sd-has-marketplace-sku { grid-template-columns: 30px minmax(0,1fr) 52px 112px; }
.sd-items-head.sd-has-marketplace-sku.sd-has-actions { grid-template-columns: 30px minmax(0,1fr) 52px 112px 30px; }
.sd-items-head.sd-has-marketplace-sku.sd-has-cogs { grid-template-columns: 30px minmax(0,1fr) 52px 112px 86px 110px; }
.sd-items-head.sd-has-marketplace-sku.sd-has-cogs.sd-has-actions { grid-template-columns: 30px minmax(0,1fr) 52px 112px 86px 110px 30px; }
.sd-order-item.sd-has-marketplace-sku { grid-template-columns: 30px minmax(0,1fr) 52px 112px; }
.sd-order-item.sd-has-marketplace-sku.sd-has-actions { grid-template-columns: 30px minmax(0,1fr) 52px 112px 30px; }
.sd-order-item.sd-has-marketplace-sku.sd-has-cogs { grid-template-columns: 30px minmax(0,1fr) 52px 112px 86px 110px; }
.sd-order-item.sd-has-marketplace-sku.sd-has-cogs.sd-has-actions { grid-template-columns: 30px minmax(0,1fr) 52px 112px 86px 110px 30px; }
.sd-item-row.sd-has-actions { grid-template-columns: 30px minmax(0,1fr) 52px 30px; }
.sd-item-row.sd-has-cogs { grid-template-columns: 30px minmax(0,1fr) 52px 86px 110px; }
.sd-item-row.sd-has-cogs.sd-has-actions { grid-template-columns: 30px minmax(0,1fr) 52px 86px 110px 30px; }
.sd-order-item:hover { background: #f8fafc; }
.sd-order-item-num {
  min-width: 0;
  height: auto;
  padding: 0;
  border-radius: 0;
  background: transparent;
  color: #64748b;
  font-size: .72rem;
  font-variant-numeric: tabular-nums;
  text-align: center;
}
.sd-item-list {
  gap: 0;
  overflow: hidden;
  border: 1px solid #dbe3ee;
  border-radius: 8px;
  background: #fff;
}
.sd-item-list-head {
  border-bottom: 1px solid #dbe3ee;
}
.sd-item-row {
  grid-template-columns: 30px minmax(0,1fr) 52px;
  gap: .75rem;
  padding: .48rem .7rem;
  border: 0;
  border-bottom: 1px solid #edf1f6;
  border-radius: 0;
  transition: background .12s ease;
}
.sd-item-row:hover { background: #f8fafc; }
.sd-item-row:last-child { border-bottom: 0; }
.sd-item-cogs,
.sd-order-item-cogs,
.sd-item-subtotal-cogs,
.sd-order-item-subtotal-cogs {
  min-width: 0;
  padding: 0;
  border: 0;
  border-radius: 0;
  background: transparent;
  color: #334155;
  font-size: .75rem;
  font-weight: 500;
  font-variant-numeric: tabular-nums;
  text-align: right;
  white-space: nowrap;
}
.sd-item-qty,
.sd-order-item-qty {
  color: #334155;
  font-size: .75rem;
  font-weight: 850;
  font-variant-numeric: tabular-nums;
  text-align: right;
}
.sd-item-subtotal-cogs,
.sd-order-item-subtotal-cogs { color: #334155; font-weight: 500; }
.sd-line-action { display: inline-flex; justify-content: flex-end; min-width: 30px; }
.sd-action-delete {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: 1px solid #fecdd3;
  border-radius: 6px;
  color: #be123c;
  background: #fff1f2;
  cursor: pointer;
}
.sd-action-delete:hover { border-color: #fda4af; background: #ffe4e6; }
.sd-action-delete:disabled { opacity: .55; cursor: wait; }
.sd-order-item-code,
.sd-item-code {
  line-height: 1.2;
}
.sd-order-item-name,
.sd-item-name,
.sd-marketplace-item-name {
  display: block;
  margin-top: .12rem;
  color: #94a3b8;
  font-size: .68rem;
  font-weight: 450;
  line-height: 1.25;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.sd-marketplace-item-name { margin-top: .08rem; font-size: .66rem; }
.sd-order-item-marketplace-sku {
  min-width: 0;
  overflow: hidden;
  color: #64748b;
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: .7rem;
  font-variant-numeric: tabular-nums;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.sd-order-items-breakdown { overflow-x: auto; }
.sd-items-head.sd-qty-breakdown.sd-has-marketplace-sku,
.sd-order-item.sd-qty-breakdown.sd-has-marketplace-sku {
  grid-template-columns: 26px minmax(180px,1fr) 48px 48px 48px 90px;
  min-width: 500px;
}
.sd-items-head.sd-qty-breakdown.sd-has-marketplace-sku.sd-has-cogs,
.sd-order-item.sd-qty-breakdown.sd-has-marketplace-sku.sd-has-cogs {
  grid-template-columns: 26px minmax(180px,1fr) 48px 48px 48px 90px 76px 84px;
  min-width: 680px;
}
.sd-items-head.sd-qty-breakdown.sd-has-marketplace-sku.sd-has-actions,
.sd-order-item.sd-qty-breakdown.sd-has-marketplace-sku.sd-has-actions {
  grid-template-columns: 26px minmax(180px,1fr) 48px 48px 48px 90px 28px;
  min-width: 530px;
}
.sd-items-head.sd-qty-breakdown.sd-has-marketplace-sku.sd-has-cogs.sd-has-actions,
.sd-order-item.sd-qty-breakdown.sd-has-marketplace-sku.sd-has-cogs.sd-has-actions {
  grid-template-columns: 26px minmax(180px,1fr) 48px 48px 48px 90px 76px 84px 28px;
  min-width: 720px;
}
.sd-qty-breakdown > .sd-order-item-qty,
.sd-qty-breakdown > .sd-order-item-diff {
  text-align: right;
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
}
.sd-order-item-diff { color:#64748b; font-size:.75rem; font-weight:750; }
.sd-order-item-diff.sd-marketplace-ok { color:#15803d; }
.sd-order-item-diff.sd-marketplace-short { color:#b91c1c; }
@media(max-width:600px){
  .sd-items-head,
  .sd-order-item,
  .sd-item-row { grid-template-columns: 26px minmax(0,1fr) 44px; gap: .55rem; }
  .sd-items-head.sd-has-actions,
  .sd-order-item.sd-has-actions,
  .sd-item-row.sd-has-actions { grid-template-columns: 26px minmax(0,1fr) 44px 28px; }
  .sd-items-head.sd-has-cogs,
  .sd-order-item.sd-has-cogs,
  .sd-item-row.sd-has-cogs { grid-template-columns: 26px minmax(0,1fr) 44px 76px 84px; }
  .sd-items-head.sd-has-cogs.sd-has-actions,
  .sd-order-item.sd-has-cogs.sd-has-actions,
  .sd-item-row.sd-has-cogs.sd-has-actions { grid-template-columns: 26px minmax(0,1fr) 44px 76px 84px 28px; }
  .sd-items-head.sd-has-marketplace-sku { grid-template-columns: 26px minmax(0,1fr) 44px 90px; }
  .sd-items-head.sd-has-marketplace-sku.sd-has-actions { grid-template-columns: 26px minmax(0,1fr) 44px 90px 28px; }
  .sd-items-head.sd-has-marketplace-sku.sd-has-cogs { grid-template-columns: 26px minmax(0,1fr) 44px 90px 76px 84px; }
  .sd-items-head.sd-has-marketplace-sku.sd-has-cogs.sd-has-actions { grid-template-columns: 26px minmax(0,1fr) 44px 90px 76px 84px 28px; }
  .sd-order-item.sd-has-marketplace-sku { grid-template-columns: 26px minmax(0,1fr) 44px 90px; }
  .sd-order-item.sd-has-marketplace-sku.sd-has-actions { grid-template-columns: 26px minmax(0,1fr) 44px 90px 28px; }
  .sd-order-item.sd-has-marketplace-sku.sd-has-cogs { grid-template-columns: 26px minmax(0,1fr) 44px 90px 76px 84px; }
  .sd-order-item.sd-has-marketplace-sku.sd-has-cogs.sd-has-actions { grid-template-columns: 26px minmax(0,1fr) 44px 90px 76px 84px 28px; }
  .sd-items-head.sd-qty-breakdown.sd-has-marketplace-sku,
  .sd-order-item.sd-qty-breakdown.sd-has-marketplace-sku { grid-template-columns: 26px minmax(180px,1fr) 48px 48px 48px 90px; }
  .sd-items-head.sd-qty-breakdown.sd-has-marketplace-sku.sd-has-cogs,
  .sd-order-item.sd-qty-breakdown.sd-has-marketplace-sku.sd-has-cogs { grid-template-columns: 26px minmax(180px,1fr) 48px 48px 48px 90px 76px 84px; }
  .sd-items-head.sd-qty-breakdown.sd-has-marketplace-sku.sd-has-actions,
  .sd-order-item.sd-qty-breakdown.sd-has-marketplace-sku.sd-has-actions { grid-template-columns: 26px minmax(180px,1fr) 48px 48px 48px 90px 28px; }
  .sd-items-head.sd-qty-breakdown.sd-has-marketplace-sku.sd-has-cogs.sd-has-actions,
  .sd-order-item.sd-qty-breakdown.sd-has-marketplace-sku.sd-has-cogs.sd-has-actions { grid-template-columns: 26px minmax(180px,1fr) 48px 48px 48px 90px 76px 84px 28px; }
}
</style>
@endpush

@section('content')
@php
    $statusKey = $shipment->status ?? 'draft';
    $statusLabel = $statusKey === 'draft' ? 'Draft' : ucfirst($statusKey);
    $lines = $shipment->lines ?? collect();
    $orderScans = ($shipment->orderScans ?? collect())->sortBy('id')->values();
    $ungroupedLines = $lines->filter(fn ($line) => !$line->shipment_order_scan_id)->values();
    $singleOrderFallbackLines = $orderScans->count() === 1 ? $ungroupedLines : collect();
    $totalQty = (int) $lines->sum('qty_scanned');
    $totalLines = (int) $lines->count();
    $itemTypeKey = static fn ($line): string => (string) ($line->item_id ?: ($line->item?->code ?: 'line-' . $line->id));
    $totalItemTypes = (int) $lines->map($itemTypeKey)->filter()->unique()->count();
    $pendingOrders = $orderScans->where('status', 'pending')->count();
    $isItemFirst = ($shipment->scan_mode ?? 'item_first') === 'item_first';
    $isDaily = (bool) ($isDaily ?? (($shipment->dispatch_mode ?? 'single') === 'daily'));
    $currentWave = $currentWave ?? null;
    $kilatMatches = $kilatMatches ?? [];
    $bookingSkusByScan = $bookingSkusByScan ?? [];
    $bookingItemsByScan = $bookingItemsByScan ?? [];
    $bookingItemCountsByScan = $bookingItemCountsByScan ?? [];
    $bookingOrderQtyByScan = $bookingOrderQtyByScan ?? [];
    $bookingStatusesByScan = $bookingStatusesByScan ?? [];
    $bookingIdentifiersByScan = $bookingIdentifiersByScan ?? [];
    $mappedLines = $lines->filter(fn ($line) => !empty($line->shipment_order_scan_id))->values();
    $unmappedLines = $lines->filter(fn ($line) => empty($line->shipment_order_scan_id))->values();
    $mappedQty = (int) $mappedLines->sum('qty_scanned');
    $unmappedQty = (int) $unmappedLines->sum('qty_scanned');
    $unlinkedOrderScans = $orderScans->filter(fn ($scan) => !$scan->fulfillment?->marketplaceOrder && $scan->source !== 'marketplace' && data_get($scan->raw_payload, 'linked_source') !== 'marketplace_order' && $scan->source !== 'sales_invoice')->values();
    $mappingErrors = $mappingErrors ?? [];
    $isItemOnlyMarketplace = !$isDaily
        && $shipment->shipment_type === \App\Models\Shipment::TYPE_MARKETPLACE
        && $orderScans->isEmpty()
        && $lines->isNotEmpty();
    if ($isItemOnlyMarketplace) {
        // Mode single/item-only tidak menunggu nomor order. Buang pesan lama
        // dari session/controller agar banner dan tombol submit konsisten.
        $mappingErrors = collect($mappingErrors)
            ->reject(fn ($error) => str_contains((string) $error, 'minimal satu nomor order'))
            ->values()
            ->all();
    }
    $editShipmentUrl = route('sales.shipments.scan_order', $shipment);
    $canDeleteShipmentRows = auth()->user()?->role === 'owner';
    $canSeeCogs = auth()->user()?->role === 'owner';
    $canSeeScanDiagnostics = auth()->user()?->role !== 'admin';
    $itemGridClass = trim(implode(' ', array_filter([
        $canDeleteShipmentRows ? 'sd-has-actions' : null,
        $canSeeCogs ? 'sd-has-cogs' : null,
    ])));
    $orderItemGridClass = trim($itemGridClass . ' sd-has-marketplace-sku');
    $lineUnitCogs = static function ($line): float {
        return (float) ($line->item?->latest_hpp ?? $line->item?->hpp ?? $line->item?->last_purchase_price ?? 0);
    };
    $lineCogs = static function ($line) use ($lineUnitCogs): float {
        return $lineUnitCogs($line) * (int) ($line->qty_scanned ?? 0);
    };
    $totalCogs = (float) $lines->sum(fn ($line) => $lineCogs($line));
    $ungroupedQty = (int) $ungroupedLines->sum('qty_scanned');
    $ungroupedItemTypes = (int) $ungroupedLines->map($itemTypeKey)->filter()->unique()->count();
    $ungroupedCogs = (float) $ungroupedLines->sum(fn ($line) => $lineCogs($line));
    $marketplaceCounts = ['all' => (int) $orderScans->count(), 'problem' => 0];
    $sourceTabs = ['platform' => [], 'store' => []];
    $linkCounts = ['all' => (int) $orderScans->count(), 'linked' => 0, 'unlinked' => 0];
    foreach ($orderScans as $orderScan) {
        $orderScanLines = $orderScan->lines->merge($singleOrderFallbackLines);
        if ((int) data_get($orderScan->raw_payload, 'duplicate_count', 0) > 0 || $orderScanLines->isEmpty()) {
            $marketplaceCounts['problem']++;
        }
        $linkedStore = $orderScan->fulfillment?->marketplaceOrder?->store;
        $linkedChannel = $linkedStore?->channel;
        if ($linkedChannel?->code && $linkedChannel?->name) {
            $platformKey = strtolower((string) $linkedChannel->code);
            $sourceTabs['platform'][$platformKey] ??= ['label' => $linkedChannel->name, 'count' => 0];
            $sourceTabs['platform'][$platformKey]['count']++;
        }
        if ($linkedStore?->id && ($linkedStore->name || $linkedStore->code)) {
            $storeKey = (string) $linkedStore->id;
            $sourceTabs['store'][$storeKey] ??= [
                'label' => $linkedStore->name ?: $linkedStore->code,
                'platform' => $linkedChannel?->name ?: 'Toko',
                'count' => 0,
            ];
            $sourceTabs['store'][$storeKey]['count']++;
        }
        $hasMarketplaceLink = str_starts_with(strtoupper(trim((string) $orderScan->order_no)), 'SPX')
            ? (bool) ($orderScan->fulfillment?->marketplaceOrder || !empty($kilatMatches[$orderScan->id]))
            : (bool) (
                $orderScan->fulfillment?->marketplaceOrder
                || $orderScan->source === 'marketplace'
                || data_get($orderScan->raw_payload, 'linked_source') === 'marketplace_order'
                || !empty($kilatMatches[$orderScan->id])
            );
        $linkCounts[$hasMarketplaceLink ? 'linked' : 'unlinked']++;
    }
    if ($orderScans->count() > 1 && $ungroupedLines->isNotEmpty()) {
        $marketplaceCounts['all']++;
        $linkCounts['all']++;
        $linkCounts['unlinked']++;
    }
    if ($orderScans->isEmpty() && $isItemFirst && $ungroupedLines->isNotEmpty()) {
        $marketplaceCounts['all'] = 1;
        $linkCounts = ['all' => 1, 'linked' => 0, 'unlinked' => 1];
    }
@endphp

<div class="sd-topbar">
    <a href="{{ route('sales.shipments.scan_order', $shipment) }}" class="sd-btn sd-topbar-nav" title="Kembali ke scan order">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Scan Order
    </a>
    <span class="sd-code">{{ $shipment->code }}</span>
    @if($isDaily && $currentWave)
        <span class="sd-pill">{{ $currentWave->label ?: ('Gelombang ' . $currentWave->sequence) }}</span>
    @endif
    <span class="sd-pill sd-status">{{ $statusLabel }}</span>
    <span class="sd-spacer"></span>
    @if ($isItemFirst)
        <span class="sd-pill">Auto</span>
    @else
        <span class="sd-pill">Order <b>{{ number_format($orderScans->count(), 0, ',', '.') }}</b></span>
    @endif
    @if($statusKey === 'draft')
        <a href="{{ $editShipmentUrl }}" class="sd-btn sd-topbar-edit" title="Edit qty dan item shipment">
            <i class="bi bi-pencil-square" aria-hidden="true"></i> Edit
        </a>
        <form id="shipmentSubmitForm" class="sd-inline-form" action="{{ $isDaily ? route('sales.shipments.wave_post', $shipment) : route('sales.shipments.submit', $shipment) }}" method="POST"
              data-gf-confirm
              data-gf-confirm-title="Submit shipment?"
              data-gf-confirm-summary='@json(["orders" => $orderScans->count(), "items" => $totalLines, "qty" => $totalQty])'
              data-gf-confirm-text="{{ $isDaily ? 'Gelombang: ' . ($currentWave?->label ?: '-') . ' · Item/SKU: ' . $totalLines . ' · Total qty: ' . $totalQty : ($isItemFirst ? 'Mapping item otomatis · Item/SKU: ' . $totalLines . ' · Total qty: ' . $totalQty : 'Order discan: ' . $orderScans->count() . ' · Item/SKU: ' . $totalLines . ' · Total qty: ' . $totalQty) }}. Stok akan dikurangi dari WH-RTS."
              data-gf-confirm-ok="{{ $isDaily ? 'Selesaikan Gelombang' : 'Submit' }}"
              data-gf-confirm-cancel="Batal">
            @csrf
            <button type="submit" class="sd-btn sd-primary sd-topbar-primary"
                    title="{{ !empty($stockInsufficient) ? 'Stok WH-RTS belum cukup' : 'Submit shipment' }}"
                    @disabled($lines->count() === 0 || !empty($stockInsufficient) || !empty($mappingErrors))>
                <i class="bi bi-check2-circle" aria-hidden="true"></i> {{ $isDaily ? 'Selesaikan' : 'Submit' }}
            </button>
        </form>
    @else
        <a href="{{ route('sales.shipments.show', $shipment) }}" class="sd-btn sd-primary sd-topbar-primary">Lihat Detail</a>
    @endif
</div>

@if(session('message'))
    <div class="sd-flash" role="status">{{ session('message') }}</div>
@endif

<div class="sd-wrap">

    <div class="sd-receipt-total sd-receipt-total-top" aria-label="Total shipment">
        <span class="sd-receipt-total-title">Ringkasan Shipment</span>
        <div class="sd-receipt-total-badges">
            <span class="sd-receipt-total-badge"><span>Item</span><strong>{{ number_format($totalLines, 0, ',', '.') }}</strong></span>
            <span class="sd-receipt-total-badge"><span>Jenis</span><strong>{{ number_format($totalItemTypes, 0, ',', '.') }}</strong></span>
            <span class="sd-receipt-total-badge"><span>Qty</span><strong>{{ number_format($totalQty, 0, ',', '.') }}</strong></span>
            @if($canSeeCogs)
                <span class="sd-receipt-total-badge" aria-label="COGS"><strong>Rp{{ number_format($totalCogs, 0, ',', '.') }}</strong></span>
            @endif
        </div>
    </div>

    @if($shipment->shipment_type === 'marketplace')
        <div class="sd-info-banner" role="status">
            <i class="bi bi-diagram-3" aria-hidden="true"></i>
            <span>
                <strong>Finance &amp; akuntansi:</strong>
                submit memotong stok dan mencatat HPP dari mutasi inventory. Revenue, fee, refund, dan payout dicatat dari sinkronisasi Finance marketplace, bukan dari scan/submit shipment.
                @if($shipment->store)
                    Toko: {{ $shipment->store->name ?? $shipment->store->code }} · Channel: {{ $shipment->store->channel?->name ?? $shipment->store->channel?->code ?? 'belum disetel' }}.
                @else
                    <strong>Shipment belum memilih toko.</strong> Pastikan setiap order tertaut ke toko/channel yang benar sebelum submit.
                @endif
            </span>
        </div>
    @endif

    @if(!empty($stockInsufficient))
        <div class="sd-stock-warning" role="alert">
            <div class="sd-stock-head">Stok WH-RTS belum cukup untuk shipment ini</div>
            <div class="sd-stock-table-wrap">
                <table class="sd-stock-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Item</th>
                            <th class="sd-stock-number">Stok</th>
                            <th class="sd-stock-number">Perlu</th>
                            <th class="sd-stock-number">Kurang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stockInsufficient as $stockError)
                            <tr>
                                <td class="sd-stock-code">{{ $stockError['code'] ?? '-' }}</td>
                                <td>{{ $stockError['name'] ?? '-' }}</td>
                                <td class="sd-stock-number">{{ number_format((int) ($stockError['stock'] ?? 0), 0, ',', '.') }}</td>
                                <td class="sd-stock-number">{{ number_format((int) ($stockError['needed'] ?? 0), 0, ',', '.') }}</td>
                                <td class="sd-stock-number sd-stock-short">-{{ number_format((int) ($stockError['short'] ?? 0), 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if(!empty($mappingErrors))
        <div class="sd-stock-warning" role="alert">
            <div class="sd-stock-head">Shipment belum siap dikirim</div>
            <div class="sd-body" style="padding-top:.55rem;padding-bottom:.55rem">
                <ul style="margin:0;padding-left:1.1rem;color:#991b1b;font-size:.78rem;line-height:1.5">
                    @foreach($mappingErrors as $mappingError)
                        <li>{{ $mappingError }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if($isItemOnlyMarketplace)
        <div class="sd-info-banner" role="status">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <span>Mode item-only aktif. Nomor order belum dicatat; shipment tetap dapat disubmit tanpa gelombang.</span>
        </div>
    @endif

    @if($isItemFirst)
        <div class="sd-tabs" role="tablist" aria-label="Ringkasan shipment">
            <button type="button" class="sd-tab-btn active" data-sd-tab="orders" role="tab" aria-selected="true">
                <i class="bi bi-receipt" aria-hidden="true"></i> Order
            </button>
            <button type="button" class="sd-tab-btn" data-sd-tab="items" role="tab" aria-selected="false">
                <i class="bi bi-box-seam" aria-hidden="true"></i> Items
            </button>
        </div>
        <div id="sd-tab-orders" class="sd-tab-panel" role="tabpanel">
    @endif

    <div class="sd-card">
            <div class="sd-head">
                <div>
                    <div class="sd-title">{{ $isItemFirst ? 'Order & Item' : 'Order' }}</div>
                    <div class="sd-muted">{{ $isItemFirst ? 'Order dan item yang sudah dimapping.' : 'Order dan item yang siap dikonfirmasi.' }}</div>
                </div>
                <div class="sd-head-actions">
                    <span class="sd-pill">{{ number_format($pendingOrders, 0, ',', '.') }} pending</span>
                    @if($statusKey === 'draft' && $orderScans->isNotEmpty())
                        <form action="{{ route('sales.shipments.rekon_auto_link', $shipment) }}" method="POST">
                            @csrf
                            <input type="hidden" name="completed_window" value="recent">
                            <button type="submit" class="sd-btn sd-sync-btn" title="Sinkronkan ulang nomor resi dengan order marketplace">
                                <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Sync Resi
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="sd-order-search">
                <input type="search" id="orderSearchInput" placeholder="Cari nomor order atau resi" autocomplete="off">
            </div>
            <div class="sd-filter-stack">
            <div class="sd-marketplace-tabs" role="tablist" aria-label="Filter daftar order">
                @foreach([
                    'all' => ['label' => 'Semua', 'icon' => 'bi-grid'],
                    'problem' => ['label' => 'Perlu Diperiksa', 'icon' => 'bi-exclamation-octagon'],
                ] as $tab => $tabMeta)
                    <button type="button"
                            class="sd-marketplace-tab {{ $tab === 'all' ? 'active' : '' }}"
                            data-order-marketplace-tab="{{ $tab }}"
                            role="tab"
                            aria-selected="{{ $tab === 'all' ? 'true' : 'false' }}">
                        <i class="bi {{ $tabMeta['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $tabMeta['label'] }}</span>
                        <span class="sd-marketplace-tab-count">{{ number_format((int) ($marketplaceCounts[$tab] ?? 0), 0, ',', '.') }}</span>
                    </button>
                @endforeach
            </div>
            @if(count($sourceTabs['platform']) || count($sourceTabs['store']))
                <div class="sd-marketplace-tabs sd-source-tabs" role="tablist" aria-label="Filter berdasarkan platform atau toko">
                    @foreach($sourceTabs['platform'] as $key => $tab)
                        <button type="button" class="sd-marketplace-tab" data-order-marketplace-tab="platform:{{ $key }}" role="tab" aria-selected="false">
                            <span>Platform · {{ $tab['label'] }}</span><span class="sd-marketplace-tab-count">{{ number_format($tab['count'], 0, ',', '.') }}</span>
                        </button>
                    @endforeach
                    @foreach($sourceTabs['store'] as $key => $tab)
                        <button type="button" class="sd-marketplace-tab" data-order-marketplace-tab="store:{{ $key }}" role="tab" aria-selected="false">
                            <span>{{ $tab['platform'] }} · {{ $tab['label'] }}</span><span class="sd-marketplace-tab-count">{{ number_format($tab['count'], 0, ',', '.') }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
            <div class="sd-link-tabs" role="tablist" aria-label="Filter status tautan order">
                @foreach([
                    'all' => ['label' => 'Semua', 'icon' => 'bi-list-ul'],
                    'linked' => ['label' => 'Tertaut', 'icon' => 'bi-link-45deg'],
                    'unlinked' => ['label' => 'Belum Tertaut', 'icon' => 'bi-link-45deg'],
                ] as $tab => $tabMeta)
                    <button type="button"
                            class="sd-link-tab {{ $tab === 'all' ? 'active' : '' }}"
                            data-order-link-tab="{{ $tab }}"
                            role="tab"
                            aria-selected="{{ $tab === 'all' ? 'true' : 'false' }}">
                        <i class="bi {{ $tabMeta['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $tabMeta['label'] }}</span>
                        <span class="sd-link-tab-count">{{ number_format((int) ($linkCounts[$tab] ?? 0), 0, ',', '.') }}</span>
                    </button>
                @endforeach
            </div>
            </div>
            <div class="sd-body">
                @if($orderScans->isEmpty() && $isItemFirst)
                    <div class="sd-order sd-order-group" data-order-card data-order-link-state="unlinked" data-order-search="item shipment">
                        <div class="sd-order-lead">
                            <div>
                                <div class="sd-order-no">ITEM SHIPMENT</div>
                                <div class="sd-muted">Belum ada order spesifik; item dipakai sebagai mapping otomatis.</div>
                            </div>
                        </div>
                        <div class="sd-order-items">
                            @if($ungroupedLines->isNotEmpty())
                                <div class="sd-items-head {{ $itemGridClass }}"><span>No</span><span>Kode Item</span><span>Qty</span>@if($canSeeCogs)<span>COGS</span><span>Subtotal COGS</span>@endif @if($canDeleteShipmentRows)<span aria-label="Aksi"><i class="bi bi-three-dots" aria-hidden="true"></i></span>@endif</div>
                            @endif
                            @forelse($ungroupedLines as $line)
                                <div class="sd-order-item {{ $itemGridClass }}">
                                    <span class="sd-order-item-num">{{ $loop->iteration }}</span>
                                    <div>
                                        <div class="sd-order-item-code">{{ $line->item?->code ?? '-' }}</div>
                                        <div class="sd-order-item-name">{{ $line->item?->name ?? 'Nama item belum tersedia' }}</div>
                                    </div>
                                    <div class="sd-order-item-qty">{{ number_format((int) $line->qty_scanned, 0, ',', '.') }}</div>
                                    @if($canSeeCogs)
                                        <div class="sd-order-item-cogs">Rp{{ number_format($lineUnitCogs($line), 0, ',', '.') }}</div>
                                        <div class="sd-order-item-subtotal-cogs">Rp{{ number_format($lineCogs($line), 0, ',', '.') }}</div>
                                    @endif
                                    @if($canDeleteShipmentRows)
                                        <span class="sd-line-action">
                                            <form class="sd-line-delete-form" action="{{ route('sales.shipments.destroy_line', $line) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="sd-action-delete" title="Hapus item"><i class="bi bi-trash3" aria-hidden="true"></i><span class="visually-hidden">Hapus item</span></button>
                                            </form>
                                        </span>
                                    @endif
                                </div>
                            @empty
                                <div class="sd-muted" style="padding:.45rem 0">Belum ada item yang discan.</div>
                            @endforelse
                        </div>
                        <div class="sd-order-summary {{ $canSeeCogs ? 'sd-summary-has-cogs' : '' }}">
                            <span class="sd-order-summary-item"><span>Item</span><strong>{{ number_format($totalLines, 0, ',', '.') }}</strong></span>
                            <span class="sd-order-summary-item"><span>Jenis</span><strong>{{ number_format($totalItemTypes, 0, ',', '.') }}</strong></span>
                            <span class="sd-order-summary-item"><span>Qty</span><strong>{{ number_format($totalQty, 0, ',', '.') }}</strong></span>
                            @if($canSeeCogs)
                                <span class="sd-order-summary-item"><span>COGS</span><strong>Rp{{ number_format($totalCogs, 0, ',', '.') }}</strong></span>
                            @endif
                        </div>
                    </div>
                @elseif($orderScans->isEmpty())
                    <div class="sd-empty">Belum ada order.</div>
                @else
                    <div class="sd-list">
                        @foreach($orderScans as $scan)
                            @php
                                $scanStatus = $scan->status ?: 'pending';
                                $orderCodeUpper = strtoupper(trim((string) $scan->order_no));
                                $isSpxShopee = str_starts_with($orderCodeUpper, 'SPX');
                                $hasMarketplaceOrder = (bool) $scan->fulfillment?->marketplaceOrder;
                                $hasBookingOnly = $isSpxShopee && !$hasMarketplaceOrder && !empty($kilatMatches[$scan->id]);
                                $hasMarketplaceLink = $isSpxShopee
                                    ? ($hasMarketplaceOrder || $hasBookingOnly)
                                    : (bool) (
                                        $hasMarketplaceOrder
                                        || $scan->source === 'marketplace'
                                        || data_get($scan->raw_payload, 'linked_source') === 'marketplace_order'
                                        || !empty($kilatMatches[$scan->id])
                                    );
                                $scanIsUnlinked = !$hasMarketplaceLink;
                                $matchMethod = $scan->match_method ?: data_get($scan->raw_payload, 'match_method');
                                $scanCopyLabel = $isSpxShopee || str_starts_with($orderCodeUpper, 'JY') || in_array($matchMethod, ['awb', 'awb_booking'], true)
                                    ? 'No. resi'
                                    : 'No. pesanan';
                                $matchReason = $scan->match_reason ?: data_get($scan->raw_payload, 'match_reason') ?: data_get($scan->raw_payload, 'lookup_status');
                                $matchMethodLabel = match ($matchMethod) {
                                    'awb' => 'AWB order',
                                    'awb_booking' => 'AWB booking',
                                    'booking_sn' => 'Booking SN',
                                    'order_no' => 'No. pesanan',
                                    default => null,
                                };
                                $matchReasonLabel = match ($matchReason) {
                                    'order_not_found' => 'Order tidak ditemukan',
                                    'empty_scan_code' => 'Kode scan kosong',
                                    'duplicate_active_shipment' => 'Order sudah ada di shipment lain',
                                    'fulfillment_cancelled' => 'Fulfillment dibatalkan',
                                    default => str_starts_with((string) $matchReason, 'status_')
                                        ? 'Status tidak aktif'
                                        : null,
                                };
                                $scanLabel = $scanStatus === 'skip'
                                    ? 'Diabaikan'
                                    : ($hasBookingOnly ? 'Booking ditemukan' : ($scanIsUnlinked ? 'Belum Tertaut' : 'Tertaut'));
                                $scanBadgeClass = $scanStatus === 'skip'
                                    ? 'skip'
                                    : ($hasBookingOnly ? 'pending' : ($hasMarketplaceLink ? 'linked' : 'unlinked'));
                                $duplicateCount = (int) data_get($scan->raw_payload, 'duplicate_count', 0);
                                $orderLines = $scan->lines->merge($singleOrderFallbackLines)->values();
                                $orderItemCount = (int) $orderLines->count();
                                $orderItemTypes = (int) $orderLines->map($itemTypeKey)->filter()->unique()->count();
                                $orderQty = (int) $orderLines->sum('qty_scanned');
                                $orderCogs = (float) $orderLines->sum(fn ($line) => $lineCogs($line));
                                $hasNoItems = $orderLines->isEmpty();
                                $bookingSkus = $hasBookingOnly ? ($bookingSkusByScan[$scan->id] ?? []) : [];
                                $bookingItems = collect($hasBookingOnly ? ($bookingItemsByScan[$scan->id] ?? []) : []);
                                $scannedItemIds = $orderLines->pluck('item_id')->map(fn ($id) => (int) $id);
                                $bookingItemIds = $bookingItems->pluck('item_id')->map(fn ($id) => (int) $id);
                                $bookingCanVerify = $hasBookingOnly
                                    && $bookingItems->isNotEmpty()
                                    && $orderLines->isNotEmpty()
                                    && $bookingItems->every(fn ($item) => (int) ($item['item_id'] ?? 0) > 0 && (int) ($item['qty'] ?? 0) > 0)
                                    && $scannedItemIds->every(fn ($id) => $id > 0)
                                    && $scannedItemIds->unique()->count() === $orderLines->count()
                                    && $bookingItemIds->unique()->sort()->values()->all() === $scannedItemIds->unique()->sort()->values()->all();
                                $bookingQtyByItem = $bookingCanVerify
                                    ? $bookingItems->groupBy('item_id')->map(fn ($items) => (int) $items->sum('qty'))
                                    : collect();
                                $bookingSkuByItem = $bookingCanVerify
                                    ? $bookingItems->groupBy('item_id')->map(fn ($items) => $items->pluck('sku')->filter()->unique()->implode(', '))
                                    : collect();
                                $hasSingleBookingItem = $hasBookingOnly
                                    && ($bookingItemCountsByScan[$scan->id] ?? 0) === 1
                                    && $orderLines->count() === 1;
                                $bookingOrderQty = $hasSingleBookingItem ? ($bookingOrderQtyByScan[$scan->id] ?? null) : null;
                                $bookingStatus = $hasBookingOnly ? ($bookingStatusesByScan[$scan->id] ?? '') : '';
                                $bookingIdentifiers = $bookingIdentifiersByScan[$scan->id] ?? [];
                                $linkedOrder = $scan->fulfillment?->marketplaceOrder;
                                $bookingNumber = trim((string) ($linkedOrder?->booking_sn ?: ($bookingIdentifiers['booking_sn'] ?? '')));
                                $marketplaceOrderNumber = trim((string) ($linkedOrder?->channel_order_id
                                    ?: $linkedOrder?->external_order_id
                                    ?: ($bookingIdentifiers['order_sn'] ?? '')));
                                $orderSearchText = strtolower(trim(implode(' ', array_filter([
                                    $scan->order_no,
                                    $scan->source,
                                    $bookingNumber,
                                    $marketplaceOrderNumber,
                                ]))));
                                $showSingleBookingSku = count($bookingSkus) === 1
                                    && $hasSingleBookingItem;
                            @endphp
                            <div class="sd-order sd-order-group" data-order-card data-order-link-state="{{ $hasMarketplaceLink ? 'linked' : 'unlinked' }}" data-order-problem="{{ $duplicateCount > 0 || $hasNoItems ? 'true' : 'false' }}" data-order-platform="{{ $linkedOrder?->store?->channel?->code ? strtolower((string) $linkedOrder->store->channel->code) : '' }}" data-order-store="{{ $linkedOrder?->store_id ?? '' }}" data-order-search="{{ $orderSearchText }}">
                                <div class="sd-order-lead">
                                    <div>
                                        <div class="sd-order-no">
                                            <button type="button" class="sd-copy-value sd-copy-scan" data-copy-value="{{ $scan->order_no }}" data-copy-label="{{ $scanCopyLabel }}" title="Salin {{ strtolower($scanCopyLabel) }}" aria-label="Salin {{ strtolower($scanCopyLabel) }} {{ $scan->order_no }}"><span>{{ $scan->order_no }}</span><span class="sd-copy-action" data-copy-action><i class="bi bi-copy" aria-hidden="true"></i> Salin</span><span class="sd-copy-confirmation" data-copy-feedback hidden><i class="bi bi-check2" aria-hidden="true"></i> Disalin</span></button>
                                            @if($duplicateCount > 0)<span class="sd-duplicate-badge" title="Order ini discan lebih dari sekali">Duplikat</span>@endif
                                            @if($hasNoItems)<span class="sd-missing-item-badge" title="Order belum memiliki item">Belum ada item</span>@endif
                                        </div>
                                        @if($bookingNumber !== '' || $marketplaceOrderNumber !== '')
                                            <div class="sd-order-identifiers">
                                                @if($bookingNumber !== '')<span class="sd-order-identifier"><span>Booking</span><button type="button" class="sd-copy-value sd-copy-identifier" data-copy-value="{{ $bookingNumber }}" data-copy-label="No. booking" title="Salin nomor booking" aria-label="Salin nomor booking {{ $bookingNumber }}"><span class="sd-order-identifier-value">{{ $bookingNumber }}</span><span class="sd-copy-action" data-copy-action><i class="bi bi-copy" aria-hidden="true"></i> Salin</span><span class="sd-copy-confirmation" data-copy-feedback hidden><i class="bi bi-check2" aria-hidden="true"></i> Disalin</span></button></span>@endif
                                                @if($marketplaceOrderNumber !== '')<span class="sd-order-identifier"><span>No. pesanan</span><button type="button" class="sd-copy-value sd-copy-identifier" data-copy-value="{{ $marketplaceOrderNumber }}" data-copy-label="No. pesanan" title="Salin nomor pesanan" aria-label="Salin nomor pesanan {{ $marketplaceOrderNumber }}"><span class="sd-order-identifier-value">{{ $marketplaceOrderNumber }}</span><span class="sd-copy-action" data-copy-action><i class="bi bi-copy" aria-hidden="true"></i> Salin</span><span class="sd-copy-confirmation" data-copy-feedback hidden><i class="bi bi-check2" aria-hidden="true"></i> Disalin</span></button></span>@endif
                                            </div>
                                        @endif
                                        @if($canSeeScanDiagnostics)
                                            <div class="sd-muted">
                                                @if(!$scanIsUnlinked)
                                                    {{ $matchMethodLabel ?: ($scan->source ?: 'Tertaut') }}
                                                    @if($scan->created_at) · @endif
                                                @endif
                                                @if($scan->created_at)Scan: {{ $scan->created_at->format('d M Y H:i') }}@endif
                                                @if($scan->confirmed_at) · Confirmed: {{ $scan->confirmed_at->format('d M Y H:i') }}@endif
                                            </div>
                                            @if($scanIsUnlinked && !$hasBookingOnly && $matchReasonLabel)
                                                <div class="sd-muted" title="{{ $matchReason }}">{{ $matchReasonLabel }}</div>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="sd-order-head-side">
                                        <div class="sd-link-status">
                                            <span class="sd-badge {{ $scanBadgeClass }}">{{ $scanLabel }}</span>
                                            @if($bookingStatus !== '')<span class="sd-booking-status" title="Status booking Shopee">{{ str_replace('_', ' ', $bookingStatus) }}</span>@endif
                                        </div>
                                        <div class="sd-order-summary sd-order-summary-inline {{ $canSeeCogs ? 'sd-summary-has-cogs' : '' }}">
                                            <span class="sd-order-summary-item"><span>Item</span><strong>{{ number_format($orderItemCount, 0, ',', '.') }}</strong></span>
                                            <span class="sd-order-summary-item"><span>Jenis</span><strong>{{ number_format($orderItemTypes, 0, ',', '.') }}</strong></span>
                                            <span class="sd-order-summary-item"><span>Qty</span><strong>{{ number_format($orderQty, 0, ',', '.') }}</strong></span>
                                            @if($canSeeCogs)
                                                <span class="sd-order-summary-item" aria-label="COGS"><strong>Rp{{ number_format($orderCogs, 0, ',', '.') }}</strong></span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if(!$scan->fulfillment?->marketplaceOrder)
                                    <div class="sd-order-items {{ $hasBookingOnly ? 'sd-order-items-breakdown' : '' }}">
                                        @if($orderLines->isNotEmpty())
                                            <div class="sd-items-head {{ $orderItemGridClass }} {{ $hasBookingOnly ? 'sd-qty-breakdown' : '' }}"><span>No</span><span>Kode Item</span>@if($hasBookingOnly)<span>Order</span><span>Ship</span><span>Diff</span>@else<span>Qty</span>@endif<span>SKU Marketplace</span>@if($canSeeCogs)<span>COGS</span><span>Subtotal COGS</span>@endif @if($canDeleteShipmentRows)<span aria-label="Aksi"><i class="bi bi-three-dots" aria-hidden="true"></i></span>@endif</div>
                                        @endif
                                        @forelse($orderLines as $line)
                                            @php
                                                $lineOrderQty = $bookingCanVerify
                                                    ? $bookingQtyByItem->get($line->item_id)
                                                    : $bookingOrderQty;
                                                $lineDifference = $bookingCanVerify
                                                    ? (int) $line->qty_scanned - (int) $lineOrderQty
                                                    : null;
                                                $lineBookingSku = $bookingCanVerify
                                                    ? $bookingSkuByItem->get($line->item_id, '')
                                                    : ($showSingleBookingSku ? $bookingSkus[0] : ($hasBookingOnly && $bookingSkus !== [] ? 'Lihat daftar' : '—'));
                                            @endphp
                                            <div class="sd-order-item {{ $orderItemGridClass }} {{ $hasBookingOnly ? 'sd-qty-breakdown' : '' }}">
                                                <span class="sd-order-item-num">{{ $loop->iteration }}</span>
                                                <div>
                                                    <div class="sd-order-item-code">{{ $line->item?->code ?? '-' }}</div>
                                                    <div class="sd-order-item-name">{{ $line->item?->name ?? 'Nama item belum tersedia' }}</div>
                                                </div>
                                                @if($hasBookingOnly)
                                                    <div class="sd-order-item-qty">{{ $lineOrderQty === null ? '—' : number_format($lineOrderQty, 0, ',', '.') }}</div>
                                                    <div class="sd-order-item-qty">{{ number_format((int) $line->qty_scanned, 0, ',', '.') }}</div>
                                                    <div class="sd-order-item-diff {{ $lineDifference === 0 ? 'sd-marketplace-ok' : 'sd-marketplace-short' }}" @if($lineDifference === null) title="Mapping SKU booking ke item scan belum terverifikasi" @endif>{{ $lineDifference === null ? 'Periksa' : (($lineDifference > 0 ? '+' : '') . number_format($lineDifference, 0, ',', '.')) }}</div>
                                                @else
                                                    <div class="sd-order-item-qty">{{ number_format((int) $line->qty_scanned, 0, ',', '.') }}</div>
                                                @endif
                                                <div class="sd-order-item-marketplace-sku" @if($showSingleBookingSku && !$bookingCanVerify) title="SKU Shopee dari booking atau produk toko; item scan belum diverifikasi" @endif>{{ $lineBookingSku }}</div>
                                                @if($canSeeCogs)
                                                    <div class="sd-order-item-cogs">Rp{{ number_format($lineUnitCogs($line), 0, ',', '.') }}</div>
                                                    <div class="sd-order-item-subtotal-cogs">Rp{{ number_format($lineCogs($line), 0, ',', '.') }}</div>
                                                @endif
                                                @if($canDeleteShipmentRows)
                                                    <span class="sd-line-action">
                                                        <form class="sd-line-delete-form" action="{{ route('sales.shipments.destroy_line', $line) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="sd-action-delete" title="Hapus item"><i class="bi bi-trash3" aria-hidden="true"></i><span class="visually-hidden">Hapus item</span></button>
                                                        </form>
                                                    </span>
                                                @endif
                                            </div>
                                        @empty
                                            <div class="sd-muted" style="padding:.45rem 0">Belum ada item.</div>
                                        @endforelse
                                    </div>
                                    @if($hasBookingOnly && !$showSingleBookingSku && !$bookingCanVerify)
                                        <div class="sd-sku-unmatched">{{ $bookingSkus === [] ? 'SKU booking belum tersedia.' : 'SKU booking: ' . implode(', ', $bookingSkus) }}</div>
                                    @endif
                                @endif
                                @if($scan->fulfillment?->marketplaceOrder)
                                    @php
                                        $marketplaceOrder = $scan->fulfillment->marketplaceOrder;
                                        $marketplaceLines = $marketplaceOrder->items ?? collect();
                                        $pickedByItem = $orderLines->groupBy('item_id')->map(fn ($itemLines) => (int) $itemLines->sum('qty_scanned'));
                                        $cogsByItem = $orderLines->groupBy('item_id')->map(fn ($itemLines) => (float) $itemLines->sum(fn ($line) => $lineCogs($line)));
                                        $marketplaceStatus = strtoupper(str_replace('_', ' ', (string) ($marketplaceOrder->order_status ?: $marketplaceOrder->status ?: '')));
                                        $unmatchedMarketplaceLines = $isSpxShopee && $orderLines->isNotEmpty()
                                            ? $marketplaceLines->filter(fn ($candidate) => !$orderLines->contains(fn ($line) => (int) ($candidate->internal_item_id ?: $candidate->item_id) === (int) $line->item_id))
                                            : collect();
                                    @endphp
                                    <div class="sd-marketplace-detail">
                                        <div class="sd-marketplace-head">
                                            <div>
                                                <div class="sd-marketplace-meta">
                                                    <span>Status: <b>{{ $marketplaceStatus ?: '—' }}</b></span>
                                                    @if($marketplaceOrder->buyer_username)<span>Pembeli: {{ $marketplaceOrder->buyer_username }}</span>@endif
                                                    @if($marketplaceOrder->shipping_awb_no)<span>Resi: <b>{{ $marketplaceOrder->shipping_awb_no }}</b></span>@endif
                                                    @if($marketplaceOrder->total_amount)<span>Total: <b>Rp{{ number_format((float) $marketplaceOrder->total_amount, 0, ',', '.') }}</b></span>@endif
                                                </div>
                                            </div>
                                            <div class="sd-marketplace-actions">
                                                @if(Route::has('marketplace.orders.show'))
                                                    <a href="{{ route('marketplace.orders.show', $marketplaceOrder) }}" class="sd-btn" target="_blank" rel="noopener">Lihat Order</a>
                                                @endif
                                                <a href="{{ $editShipmentUrl }}" class="sd-btn">Edit Qty</a>
                                            </div>
                                        </div>
                                        @if($marketplaceLines->isNotEmpty())
                                            @php
                                                $displayLines = $orderLines;
                                                if ($displayLines->isEmpty()) {
                                                    $displayLines = $marketplaceLines->map(function ($marketplaceLine) {
                                                        return (object) [
                                                            'id' => null,
                                                            'item_id' => $marketplaceLine->internal_item_id ?: $marketplaceLine->item_id,
                                                            'item' => $marketplaceLine->internalItem,
                                                            'qty_scanned' => (int) $marketplaceLine->qty,
                                                            'marketplace_line' => $marketplaceLine,
                                                        ];
                                                    });
                                                }
                                            @endphp
                                            <div class="sd-order-items sd-order-items-breakdown">
                                                <div class="sd-items-head {{ $orderItemGridClass }} sd-qty-breakdown"><span>No</span><span>Kode Item</span><span>Order</span><span>Ship</span><span>Diff</span><span>SKU Marketplace</span>@if($canSeeCogs)<span>COGS</span><span>Subtotal COGS</span>@endif @if($canDeleteShipmentRows)<span aria-label="Aksi"><i class="bi bi-three-dots" aria-hidden="true"></i></span>@endif</div>
                                                @foreach($displayLines as $line)
                                                    @php
                                                        $matchedMarketplaceLine = $line->marketplace_line ?? $marketplaceLines->first(function ($candidate) use ($line) {
                                                            return (int) ($candidate->internal_item_id ?: $candidate->item_id) === (int) $line->item_id;
                                                        });
                                                        // Satu item scan dan satu item order boleh ditampilkan berdampingan,
                                                        // tetapi perbedaan item tidak boleh dianggap Diff 0.
                                                        $itemMismatch = $isSpxShopee && !$matchedMarketplaceLine && $orderLines->isNotEmpty();
                                                        $singleLineMismatch = $itemMismatch && $orderLines->count() === 1 && $marketplaceLines->count() === 1;
                                                        $marketplaceLine = $matchedMarketplaceLine ?: ($singleLineMismatch ? $marketplaceLines->first() : null);
                                                        // Tampilkan SKU asli dari payload marketplace saja.
                                                        // Jangan gunakan marketplace_sku hasil mapping nama atau kode internal.
                                                        $marketplaceSku = $marketplaceLine?->model_sku ?: $marketplaceLine?->item_sku ?: '—';
                                                        $qtyOrder = $itemMismatch && !$singleLineMismatch ? null : (int) ($marketplaceLine?->qty ?? $line->qty_scanned);
                                                        $qtyShipment = (int) ($pickedByItem[$line->item_id] ?? $line->qty_scanned);
                                                        $difference = $qtyShipment - (int) $qtyOrder;
                                                    @endphp
                                                    <div class="sd-order-item {{ $orderItemGridClass }} sd-qty-breakdown">
                                                        <span class="sd-order-item-num">{{ $loop->iteration }}</span>
                                                        <div>
                                                            <div class="sd-order-item-code">{{ $line->item?->code ?: ($marketplaceLine?->item_code_snapshot ?: '-') }}</div>
                                                            <div class="sd-order-item-name">{{ $line->item?->name ?: 'Nama item belum tersedia' }}</div>
                                                        </div>
                                                        <div class="sd-order-item-qty">{{ $qtyOrder === null ? '—' : number_format($qtyOrder, 0, ',', '.') }}</div>
                                                        <div class="sd-order-item-qty">{{ number_format($qtyShipment, 0, ',', '.') }}</div>
                                                        <div class="sd-order-item-diff {{ !$itemMismatch && $difference === 0 ? 'sd-marketplace-ok' : 'sd-marketplace-short' }}">{{ $itemMismatch ? 'Periksa' : (($difference > 0 ? '+' : '') . number_format($difference, 0, ',', '.')) }}</div>
                                                        <div class="sd-order-item-marketplace-sku {{ $itemMismatch ? 'is-mismatch' : '' }}">{{ $marketplaceSku }}@if($itemMismatch)<span class="sd-sku-mismatch">{{ $singleLineMismatch ? 'Item berbeda' : 'Lihat SKU order' }}</span>@endif</div>
                                                        @if($canSeeCogs)
                                                            <div class="sd-order-item-cogs">Rp{{ number_format($lineUnitCogs($line), 0, ',', '.') }}</div>
                                                            <div class="sd-order-item-subtotal-cogs">Rp{{ number_format($lineCogs($line), 0, ',', '.') }}</div>
                                                        @endif
                                                        @if($canDeleteShipmentRows && !empty($line->id))
                                                            <span class="sd-line-action">
                                                                <form class="sd-line-delete-form" action="{{ route('sales.shipments.destroy_line', $line) }}" method="POST">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="sd-action-delete" title="Hapus item"><i class="bi bi-trash3" aria-hidden="true"></i><span class="visually-hidden">Hapus item</span></button>
                                                                </form>
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                            @if($isSpxShopee && $unmatchedMarketplaceLines->isNotEmpty() && !($orderLines->count() === 1 && $marketplaceLines->count() === 1))
                                                <div class="sd-sku-unmatched">Item order belum cocok: {{ $unmatchedMarketplaceLines->map(fn ($line) => $line->model_sku ?: $line->item_sku ?: 'SKU belum tersedia')->join(', ') }}</div>
                                            @endif
                                        @else
                                            <div class="sd-muted">Detail item marketplace belum tersedia.</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        @if($orderScans->count() > 1 && $ungroupedLines->isNotEmpty())
                            <div class="sd-order sd-order-group" data-order-card data-order-link-state="unlinked" data-order-search="belum dikelompokkan">
                                <div class="sd-order-lead"><div><div class="sd-order-no">SISA BELUM TERKELOMPOK</div><div class="sd-muted">Item menunggu nomor order atau belum cocok</div></div></div>
                                <div class="sd-order-items">
                                    <div class="sd-items-head {{ $orderItemGridClass }}"><span>No</span><span>Kode Item</span><span>Qty</span><span>SKU Marketplace</span>@if($canSeeCogs)<span>COGS</span><span>Subtotal COGS</span>@endif @if($canDeleteShipmentRows)<span aria-label="Aksi"><i class="bi bi-three-dots" aria-hidden="true"></i></span>@endif</div>
                                    @foreach($ungroupedLines as $line)
                                        <div class="sd-order-item {{ $orderItemGridClass }}"><span class="sd-order-item-num">{{ $loop->iteration }}</span><div><div class="sd-order-item-code">{{ $line->item?->code ?? '-' }}</div><div class="sd-order-item-name">{{ $line->item?->name ?? 'Nama item belum tersedia' }}</div></div><div class="sd-order-item-qty">{{ number_format((int) $line->qty_scanned, 0, ',', '.') }}</div><div class="sd-order-item-marketplace-sku">—</div>@if($canSeeCogs)<div class="sd-order-item-cogs">Rp{{ number_format($lineUnitCogs($line), 0, ',', '.') }}</div><div class="sd-order-item-subtotal-cogs">Rp{{ number_format($lineCogs($line), 0, ',', '.') }}</div>@endif @if($canDeleteShipmentRows)<span class="sd-line-action"><form class="sd-line-delete-form" action="{{ route('sales.shipments.destroy_line', $line) }}" method="POST">@csrf @method('DELETE')<button type="submit" class="sd-action-delete" title="Hapus item"><i class="bi bi-trash3" aria-hidden="true"></i><span class="visually-hidden">Hapus item</span></button></form></span>@endif</div>
                                    @endforeach
                                </div>
                                <div class="sd-order-summary {{ $canSeeCogs ? 'sd-summary-has-cogs' : '' }}">
                                    <span class="sd-order-summary-item"><span>Item</span><strong>{{ number_format($ungroupedLines->count(), 0, ',', '.') }}</strong></span>
                                    <span class="sd-order-summary-item"><span>Jenis</span><strong>{{ number_format($ungroupedItemTypes, 0, ',', '.') }}</strong></span>
                                    <span class="sd-order-summary-item"><span>Qty</span><strong>{{ number_format((int) $ungroupedLines->sum('qty_scanned'), 0, ',', '.') }}</strong></span>
                                    @if($canSeeCogs)
                                        <span class="sd-order-summary-item"><span>COGS</span><strong>Rp{{ number_format($ungroupedCogs, 0, ',', '.') }}</strong></span>
                                    @endif
                                </div>
                                <div class="sd-muted" style="padding:.55rem .75rem;border-top:1px dashed rgba(148,163,184,.25)">Belum ada order.</div>
                            </div>
                        @endif
                    </div>
                    <div class="sd-empty" id="orderSearchEmpty" hidden>Tidak ada order/no resi yang cocok.</div>
                @endif
            </div>
    </div>

    @if($isItemFirst)
        </div>

        <div id="sd-tab-items" class="sd-tab-panel" role="tabpanel" hidden>
            <div class="sd-card">
                <div class="sd-head">
                    <div>
                        <div class="sd-title">Items Shipment</div>
                        <div class="sd-muted">Daftar item yang sudah discan dan hasil mapping order.</div>
                    </div>
                    <span class="sd-pill">{{ number_format($totalLines, 0, ',', '.') }} SKU</span>
                </div>
                <div class="sd-body">
                    <div class="sd-item-list">
                        @if($lines->isNotEmpty())
                            <div class="sd-items-head sd-item-list-head {{ $itemGridClass }}"><span>No</span><span>Kode Item</span><span>Qty</span>@if($canSeeCogs)<span>COGS</span><span>Subtotal COGS</span>@endif @if($canDeleteShipmentRows)<span aria-label="Aksi"><i class="bi bi-three-dots" aria-hidden="true"></i></span>@endif</div>
                        @endif
                        @forelse($lines as $line)
                            @php
                                $lineOrder = $line->shipment_order_scan_id
                                    ? $orderScans->firstWhere('id', $line->shipment_order_scan_id)
                                    : null;
                            @endphp
                            <div class="sd-item-row {{ $itemGridClass }}">
                                <span class="sd-order-item-num">{{ $loop->iteration }}</span>
                                <div>
                                    <div class="sd-item-code">{{ $line->item?->code ?? '-' }}</div>
                                    <div class="sd-item-name">{{ $line->item?->name ?? 'Nama item belum tersedia' }}</div>
                                    <div class="sd-item-map">
                                        {{ $lineOrder ? 'Order: ' . $lineOrder->order_no : 'Belum terhubung ke order' }}
                                    </div>
                                </div>
                                <span class="sd-item-qty">{{ number_format((int) $line->qty_scanned, 0, ',', '.') }}</span>
                                @if($canSeeCogs)
                                    <span class="sd-item-cogs">Rp{{ number_format($lineUnitCogs($line), 0, ',', '.') }}</span>
                                    <span class="sd-item-subtotal-cogs">Rp{{ number_format($lineCogs($line), 0, ',', '.') }}</span>
                                @endif
                                @if($canDeleteShipmentRows)
                                    <span class="sd-line-action">
                                        <form class="sd-line-delete-form" action="{{ route('sales.shipments.destroy_line', $line) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="sd-action-delete" title="Hapus item"><i class="bi bi-trash3" aria-hidden="true"></i><span class="visually-hidden">Hapus item</span></button>
                                        </form>
                                    </span>
                                @endif
                            </div>
                        @empty
                            <div class="sd-empty">Belum ada item yang discan.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    @endif
</div>

<div id="sdCopyToast" class="sd-copy-toast" role="status" aria-live="polite" hidden></div>

<script>
(function(){
    var copyToast = document.getElementById('sdCopyToast');
    var copyToastTimer;
    var copyButtonTimer;
    var lastCopiedButton;

    function legacyCopyText(value){
        var previousFocus = document.activeElement;
        var input = document.createElement('textarea');
        input.value = value;
        input.setAttribute('readonly', '');
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        try {
            if (!document.execCommand('copy')) throw new Error('Copy failed');
        } finally {
            input.remove();
            if (previousFocus && typeof previousFocus.focus === 'function') {
                previousFocus.focus({preventScroll: true});
            }
        }
    }

    function copyText(value){
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(value).catch(function(){ legacyCopyText(value); });
        }
        try {
            legacyCopyText(value);
            return Promise.resolve();
        } catch (error) {
            return Promise.reject(error);
        }
    }

    function showCopyToast(message){
        if (!copyToast) return;
        copyToast.textContent = message;
        copyToast.hidden = false;
        clearTimeout(copyToastTimer);
        copyToastTimer = setTimeout(function(){ copyToast.hidden = true; }, 1800);
    }

    function resetCopyButton(button){
        if (!button) return;
        var action = button.querySelector('[data-copy-action]');
        var feedback = button.querySelector('[data-copy-feedback]');
        if (action) action.hidden = false;
        if (feedback) feedback.hidden = true;
    }

    function showCopySuccess(button){
        clearTimeout(copyButtonTimer);
        resetCopyButton(lastCopiedButton);
        var action = button.querySelector('[data-copy-action]');
        var feedback = button.querySelector('[data-copy-feedback]');
        if (action) action.hidden = true;
        if (feedback) feedback.hidden = false;
        lastCopiedButton = button;
        copyButtonTimer = setTimeout(function(){ resetCopyButton(button); lastCopiedButton = null; }, 1800);
        showCopyToast((button.dataset.copyLabel || 'Nomor') + ' berhasil disalin');
    }

    document.querySelectorAll('[data-copy-value]').forEach(function(button){
        button.addEventListener('click', function(){
            var value = button.dataset.copyValue || '';
            if (!value) return;
            copyText(value)
                .then(function(){ showCopySuccess(button); })
                .catch(function(){ showCopyToast('Gagal menyalin nomor'); });
        });
    });

    var orderSearchInput = document.getElementById('orderSearchInput');
    var orderSearchEmpty = document.getElementById('orderSearchEmpty');
    var orderCards = document.querySelectorAll('[data-order-card]');
    var marketplaceTabButtons = document.querySelectorAll('[data-order-marketplace-tab]');
    var linkTabButtons = document.querySelectorAll('[data-order-link-tab]');
    var activeMarketplaceTab = 'all';
    var activeLinkTab = 'all';

    function matchesMarketplace(card){
        if (activeMarketplaceTab === 'all') return true;
        if (activeMarketplaceTab === 'problem') return card.dataset.orderProblem === 'true';
        if (activeMarketplaceTab.indexOf('platform:') === 0) return card.dataset.orderPlatform === activeMarketplaceTab.slice(9);
        if (activeMarketplaceTab.indexOf('store:') === 0) return card.dataset.orderStore === activeMarketplaceTab.slice(6);
        return false;
    }

    function updateLinkTabCounts(){
        var counts = {all: 0, linked: 0, unlinked: 0};

        orderCards.forEach(function(card){
            if (!matchesMarketplace(card)) return;

            counts.all++;
            var linkState = card.dataset.orderLinkState || 'unlinked';
            if (Object.prototype.hasOwnProperty.call(counts, linkState)) counts[linkState]++;
        });

        linkTabButtons.forEach(function(button){
            var tab = button.dataset.orderLinkTab || 'all';
            var count = button.querySelector('.sd-link-tab-count');
            if (count) count.textContent = Number(counts[tab] || 0).toLocaleString('id-ID');
        });
    }

    function applyOrderFilters(){
        var query = (orderSearchInput?.value || '').trim().toLowerCase();
        var visibleCount = 0;

        updateLinkTabCounts();

        orderCards.forEach(function(card){
            var searchable = (card.dataset.orderSearch || '').toLowerCase();
            var linkState = card.dataset.orderLinkState || 'unlinked';
            var visible = (!query || searchable.indexOf(query) !== -1)
                && matchesMarketplace(card)
                && (activeLinkTab === 'all' || linkState === activeLinkTab);
            card.hidden = !visible;
            if (visible) visibleCount++;
        });

        if (orderSearchEmpty) orderSearchEmpty.hidden = visibleCount > 0;
    }

    orderSearchInput?.addEventListener('input', applyOrderFilters);

    marketplaceTabButtons.forEach(function(button){
        button.addEventListener('click', function(){
            activeMarketplaceTab = button.dataset.orderMarketplaceTab || 'all';
            marketplaceTabButtons.forEach(function(tab){
                var active = tab === button;
                tab.classList.toggle('active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            applyOrderFilters();
        });
    });

    linkTabButtons.forEach(function(button){
        button.addEventListener('click', function(){
            activeLinkTab = button.dataset.orderLinkTab || 'all';
            linkTabButtons.forEach(function(tab){
                var active = tab === button;
                tab.classList.toggle('active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            applyOrderFilters();
        });
    });

    var tabButtons = document.querySelectorAll('[data-sd-tab]');
    var tabPanels = document.querySelectorAll('.sd-tab-panel');
    tabButtons.forEach(function(button){
        button.addEventListener('click', function(){
            var target = button.dataset.sdTab;
            tabButtons.forEach(function(tab){
                var active = tab === button;
                tab.classList.toggle('active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            tabPanels.forEach(function(panel){
                panel.hidden = panel.id !== 'sd-tab-' + target;
            });
        });
    });

    document.querySelectorAll('.sd-line-delete-form').forEach(function(form){
        form.addEventListener('submit', function(event){
            event.preventDefault();

            if (!window.confirm('Hapus item ini dari shipment?')) return;

            var button = form.querySelector('button[type="submit"]');
            var token = form.querySelector('input[name="_token"]')?.value || '';
            if (button) button.disabled = true;

            fetch(form.action, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token,
                },
            }).then(async function(response){
                var payload = await response.json().catch(function(){ return {}; });
                if (!response.ok || payload.status !== 'ok') {
                    throw new Error(payload.message || 'Gagal menghapus item.');
                }
                window.location.reload();
            }).catch(function(error){
                if (button) button.disabled = false;
                window.alert(error.message || 'Gagal menghapus item.');
            });
        });
    });

    document.querySelectorAll('form').forEach(function(form){
        var syncButton = form.querySelector('.sd-sync-btn');
        if (!syncButton) return;

        form.addEventListener('submit', function(event){
            if (form.dataset.syncing === '1') {
                event.preventDefault();
                return;
            }

            form.dataset.syncing = '1';
            syncButton.disabled = true;
            syncButton.classList.add('is-loading');
            syncButton.setAttribute('aria-busy', 'true');
            syncButton.innerHTML = '<span class="sd-sync-spinner" aria-hidden="true"></span><span>Menyinkronkan...</span>';
        });
    });

})();
</script>
@endsection
