{{-- resources/views/purchasing/purchase_orders/show.blade.php --}}
@extends('layouts.app')

@section('title', 'PO ' . $order->code)

@push('head')
    <style>
.po-wrap{max-width:var(--app-content-max-width, 1100px);margin-inline:auto;padding:.7rem .75rem 3rem}
.po-topbar{position:sticky;top:0;z-index:250;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;width:100%;max-width:var(--app-content-max-width, 1100px);margin-inline:auto;padding:.55rem .75rem;background:var(--card,#fff);border-bottom:1px solid rgba(148,163,184,.18)}
.po-code{font-weight:900;font-size:1.05rem;color:#111827;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
.po-supplier{font-size:.8rem;color:#64748b;margin-left:.25rem}
.po-spacer{flex:1}
.po-document-date-row{display:flex;align-items:center;gap:.65rem;margin-bottom:.7rem;padding:.8rem 1rem;border:1px solid rgba(37,99,235,.2);border-left:4px solid #2563eb;border-radius:9px;background:linear-gradient(90deg,rgba(239,246,255,.92),rgba(255,255,255,.96));color:#64748b;font-size:.78rem;box-shadow:0 3px 12px rgba(37,99,235,.07)}
.po-document-date-row i{display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:7px;background:#dbeafe;color:#2563eb;font-size:1rem}
.po-document-date-row strong{color:#1e3a8a;font-size:1rem;font-weight:900;letter-spacing:-.01em}
.po-document-date-label{font-size:.68rem;font-weight:900;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
body[data-theme="dark"] .po-document-date-row{border-color:rgba(96,165,250,.35);background:linear-gradient(90deg,rgba(30,58,138,.24),rgba(15,23,42,.92));box-shadow:0 3px 12px rgba(2,6,23,.25)}
body[data-theme="dark"] .po-document-date-row i{background:rgba(59,130,246,.2);color:#93c5fd}
body[data-theme="dark"] .po-document-date-row strong{color:#bfdbfe}
.po-btn,.po-pill{display:inline-flex;align-items:center;justify-content:center;gap:.35rem;border-radius:7px;border:1px solid rgba(148,163,184,.3);background:transparent;color:#475569;text-decoration:none;font-size:.76rem;padding:.28rem .6rem;min-height:34px}
.po-btn{font-weight:800; cursor:pointer;}
.po-btn:hover{background:rgba(148,163,184,.09);color:#111827;text-decoration:none}
.po-primary{background:#334155!important;border-color:#334155!important;color:#fff!important}
.po-success{background:#15803d!important;border-color:#15803d!important;color:#fff!important}
.po-success:hover{background:#166534!important;border-color:#166534!important}
.po-info{background:#0ea5e9!important;border-color:#0ea5e9!important;color:#fff!important}
.po-info:hover{background:#0284c7!important}
.po-wa{background:#25d366!important;border-color:#25d366!important;color:#fff!important}
.po-wa:hover{background:#16a34a!important;border-color:#16a34a!important;color:#fff!important}
.po-wa-sent{background:#ecfdf5!important;border-color:#86efac!important;color:#15803d!important}
.po-wa-sent:hover{background:#dcfce7!important;border-color:#4ade80!important;color:#166534!important}
.po-status{font-weight:850;color:#334155;background:rgba(148,163,184,.08)}
.po-status.approved{color:#166534;background:rgba(22,101,52,.08);border-color:rgba(22,101,52,.2)}
.po-status.closed{color:#0f172a;background:rgba(15,23,42,.08);border-color:rgba(15,23,42,.2)}
.po-status.cancelled{color:#991b1b;background:rgba(153,27,27,.08);border-color:rgba(153,27,27,.2)}
.pay-badge{font-weight:850;color:#475569;background:rgba(148,163,184,.08)}
.pay-badge.pay-paid{color:#166534;background:rgba(22,101,52,.08);border-color:rgba(22,101,52,.2)}
.pay-badge.pay-partial{color:#a16207;background:rgba(234,179,8,.1);border-color:rgba(234,179,8,.25)}
.pay-badge.pay-overpaid{color:#6d28d9;background:rgba(124,58,237,.1);border-color:rgba(124,58,237,.25)}
.pay-badge.pay-unpaid{color:#64748b;background:rgba(148,163,184,.08)}
.accounting-sync-group{display:flex;align-items:center;gap:.35rem;flex-wrap:wrap;margin-top:.3rem}
.accounting-sync{display:inline-flex;align-items:center;gap:.22rem;border:1px solid;border-radius:999px;padding:.12rem .42rem;font-size:.62rem;font-weight:800;line-height:1.2;white-space:nowrap}
.accounting-sync.is-posted{color:#15803d;background:rgba(22,163,74,.08);border-color:rgba(22,163,74,.24)}
.accounting-sync.is-pending{color:#a16207;background:rgba(234,179,8,.1);border-color:rgba(234,179,8,.28)}
.accounting-sync.is-neutral{color:#64748b;background:rgba(148,163,184,.08);border-color:rgba(148,163,184,.24)}
.accounting-sync-link{color:#475569;font-size:.66rem;font-weight:750;text-decoration:none}
.accounting-sync-link:hover{color:#334155;text-decoration:underline}
body[data-theme="dark"] .accounting-sync.is-posted{color:#86efac;background:rgba(22,163,74,.16);border-color:rgba(74,222,128,.32)}
body[data-theme="dark"] .accounting-sync.is-pending{color:#fcd34d;background:rgba(234,179,8,.16);border-color:rgba(250,204,21,.32)}
body[data-theme="dark"] .accounting-sync.is-neutral{color:#cbd5e1;background:rgba(148,163,184,.12);border-color:rgba(148,163,184,.3)}
body[data-theme="dark"] .accounting-sync-link{color:#cbd5e1}
.po-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:.55rem;margin-bottom:.65rem}
.po-card{background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:8px;overflow:hidden;margin-bottom:.65rem}
.po-kpi{padding:.65rem .75rem}
.po-label{font-size:.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.02em}
.po-value{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:1.18rem;font-weight:900;color:#111827;margin-top:.12rem}
.po-head{display:flex;align-items:center;gap:.55rem;justify-content:space-between;padding:.7rem .85rem;border-bottom:1px solid rgba(148,163,184,.12)}
.po-title{font-weight:900;color:#334155}
.po-muted{color:#64748b;font-size:.8rem}
.po-body{padding:.75rem .85rem}
.po-empty{padding:1.6rem 1rem;text-align:center;color:#64748b;font-size:.84rem}
.po-table-wrap{overflow:auto;border:1px solid rgba(148,163,184,.16);border-radius:8px}
.po-table{width:100%;border-collapse:collapse}
.po-table th,.po-table td{padding:.55rem .65rem;border-bottom:1px solid rgba(148,163,184,.12);vertical-align:middle}
.po-table th{text-align:left;font-size:.72rem;color:#64748b;font-weight:900;text-transform:uppercase;letter-spacing:.02em;background:rgba(148,163,184,.04)}
.po-table td{font-size:.86rem;color:#334155}
.po-code-cell{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:900;color:#111827}
.po-name{color:#64748b;font-size:.8rem;margin-top:.08rem}
.po-r{text-align:right}
.po-total td{font-weight:900;color:#111827;background:rgba(148,163,184,.04)}
.po-unit-value{font-weight:800;color:#334155;white-space:nowrap}
.po-unit-conversion{color:#64748b;font-size:.74rem;line-height:1.35;white-space:nowrap}
.po-unit-conversion strong{color:#475569;font-weight:800}
body[data-theme="dark"] .po-unit-value{color:#e2e8f0}
body[data-theme="dark"] .po-unit-conversion{color:#94a3b8}
body[data-theme="dark"] .po-unit-conversion strong{color:#cbd5e1}
.po-tabs{display:flex;gap:.25rem;margin-bottom:.65rem;border-bottom:1px solid rgba(148,163,184,.18);flex-wrap:wrap}
.po-tab{appearance:none;display:inline-flex;align-items:center;gap:.4rem;border:none;background:transparent;color:#64748b;font-weight:800;font-size:.82rem;padding:.55rem .8rem;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px}
.po-tab:hover{color:#334155}
.po-tab.active{color:#111827;border-bottom-color:#334155}
.po-tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:1.35rem;height:1.35rem;padding:0 .3rem;border-radius:999px;background:rgba(148,163,184,.16);color:#475569;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.7rem;font-weight:900}
.po-tab.active .po-tab-count{background:#334155;color:#fff}
.po-tabpane{display:none}
.po-tabpane.active{display:block}
.po-tabpane .po-card{margin-bottom:0}
.po-workflow{margin:0 0 .8rem;padding:.85rem 1rem;background:var(--card,#fff);border:1px solid rgba(148,163,184,.18);border-radius:9px;overflow-x:auto}
.po-workflow-list{display:flex;align-items:flex-start;min-width:650px;margin:0;padding:0;list-style:none}
.po-workflow-step{position:relative;display:flex;flex:1;min-width:125px;flex-direction:column;align-items:center;gap:.35rem;color:#94a3b8;text-align:center;font-size:.7rem;font-weight:800}
.po-workflow-step:not(:last-child)::after{content:'';position:absolute;top:11px;left:calc(50% + 12px);right:calc(-50% + 12px);height:2px;background:rgba(148,163,184,.24)}
.po-workflow-dot{position:relative;z-index:1;display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border:2px solid rgba(148,163,184,.35);border-radius:999px;background:var(--card,#fff);font-size:.68rem;line-height:1}
.po-workflow-step.is-done{color:#15803d}
.po-workflow-step.is-done .po-workflow-dot{border-color:#22c55e;background:#22c55e;color:#fff}
.po-workflow-step.is-done:not(:last-child)::after{background:rgba(34,197,94,.55)}
.po-workflow-step.is-current{color:#1d4ed8}
.po-workflow-step.is-current .po-workflow-dot{border-color:#2563eb;background:#eff6ff;color:#1d4ed8;box-shadow:0 0 0 4px rgba(37,99,235,.1)}
.po-workflow-step.is-cancelled{color:#b91c1c}
.po-workflow-step.is-cancelled .po-workflow-dot{border-color:#ef4444;background:#fef2f2;color:#b91c1c}
.po-workflow-label{white-space:nowrap}
.po-workflow-help{margin-top:.15rem;color:#64748b;font-size:.72rem;font-weight:500;text-align:center}
body[data-theme="dark"] .po-workflow-dot{background:var(--card,#0f172a)}
body[data-theme="dark"] .po-workflow-step.is-current .po-workflow-dot{background:rgba(37,99,235,.18);color:#bfdbfe}
body[data-theme="dark"] .po-workflow-help{color:#94a3b8}

/* Payment modal contract: quiet hierarchy, shared GFID roles, and a compact form. */
#modalAddPayment{
  --payment-brand:var(--gf-color-brand,#5b5ce2);
  --payment-brand-strong:var(--gf-color-brand-strong,#4142bb);
  --payment-ink:var(--gf-color-content,#101828);
  --payment-muted:var(--gf-color-muted,#667085);
  --payment-line:var(--gf-color-line,#e4e7ec);
  --payment-surface:var(--gf-color-surface,#fff);
  --payment-soft:var(--gf-color-surface-muted,#f2f4f7);
}
#modalAddPayment .modal-dialog{max-width:660px;margin-block:.75rem}
#modalAddPayment .payment-modal-content{overflow:hidden;border:1px solid var(--payment-line);border-radius:18px;background:var(--payment-surface);box-shadow:0 24px 60px rgba(48,49,125,.16)}
#modalAddPayment .payment-modal-header{display:grid;grid-template-columns:38px minmax(0,1fr) 40px;align-items:start;gap:.7rem;padding:1rem 1.2rem;border-bottom:1px solid var(--payment-line);background:color-mix(in srgb,var(--payment-soft) 60%,var(--payment-surface))}
#modalAddPayment .payment-modal-icon{display:grid;width:38px;height:38px;place-items:center;border-radius:12px;color:var(--payment-brand-strong);background:#eeefff;font-size:.9rem}
#modalAddPayment .payment-modal-kicker{margin-top:.04rem;color:var(--payment-brand-strong);font-size:.6rem;font-weight:850;letter-spacing:.1em;text-transform:uppercase}
#modalAddPayment .payment-modal-header .modal-title{margin:.16rem 0 0;color:var(--payment-ink);font-size:1.02rem;font-weight:850;letter-spacing:-.035em}
#modalAddPayment .payment-modal-subtitle{max-width:520px;margin:.24rem 0 0;color:var(--payment-muted);font-size:.69rem;line-height:1.45}
#modalAddPayment .payment-modal-header .btn-close{grid-column:3;width:40px;height:40px;margin-top:-.15rem;padding:.75rem;border-radius:9px}
#modalAddPayment .payment-modal-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.45rem;margin-top:.85rem;padding-top:.8rem;border-top:1px solid rgba(148,163,184,.18)}
#modalAddPayment .payment-modal-stat{min-width:0;padding:.48rem .58rem;border:1px solid rgba(148,163,184,.18);border-radius:10px;background:rgba(255,255,255,.58)}
#modalAddPayment .payment-modal-stat-label{display:block;overflow:hidden;color:var(--payment-muted);font-size:.58rem;font-weight:800;letter-spacing:.045em;text-overflow:ellipsis;text-transform:uppercase;white-space:nowrap}
#modalAddPayment .payment-modal-stat-value{display:block;margin-top:.18rem;overflow:hidden;color:var(--payment-ink);font-size:.78rem;font-weight:850;text-overflow:ellipsis;white-space:nowrap}
#modalAddPayment .payment-modal-body{padding:1rem 1.2rem 1.1rem;background:var(--payment-surface)}
#modalAddPayment .payment-modal-note{display:flex;align-items:flex-start;gap:.55rem;margin-bottom:.9rem;padding:.65rem .72rem;border:1px solid #b2ddff;border-radius:10px;color:#175cd3;background:#eff8ff;font-size:.68rem;line-height:1.45}
#modalAddPayment .payment-modal-note-icon{display:grid;flex:0 0 auto;width:25px;height:25px;place-items:center;border-radius:8px;background:#dbeafe;color:#1d4ed8}
#modalAddPayment .payment-modal-note-label{display:block;margin-bottom:.08rem;font-size:.6rem;font-weight:850;letter-spacing:.06em;text-transform:uppercase}
#modalAddPayment .payment-modal-note-copy{display:block}
#modalAddPayment .payment-modal-form{display:grid;gap:.85rem}
#modalAddPayment .payment-modal-field{min-width:0}
#modalAddPayment .payment-modal .form-label,
#modalAddPayment .form-label{display:block;margin-bottom:.3rem;color:var(--payment-ink);font-size:.65rem;font-weight:760}
#modalAddPayment .form-control,#modalAddPayment .form-select{min-height:40px;border-color:#d0d5dd;border-radius:9px;color:var(--payment-ink);background:var(--payment-surface);font-size:.73rem;box-shadow:none}
#modalAddPayment .form-control:focus,#modalAddPayment .form-select:focus{border-color:var(--payment-brand);box-shadow:0 0 0 3px rgba(91,92,226,.12)}
#modalAddPayment .form-control:disabled,#modalAddPayment .form-select:disabled{color:var(--payment-muted);background:var(--payment-soft)}
#modalAddPayment .form-text{margin-top:.25rem;color:var(--payment-muted);font-size:.61rem;line-height:1.4}
#modalAddPayment .input-group-text{border-color:#d0d5dd;color:var(--payment-muted);background:var(--payment-soft);font-size:.72rem}
#modalAddPayment #btnFillRemaining{min-width:84px;border-color:#d0d5dd;color:var(--payment-brand-strong);background:var(--payment-surface);font-size:.67rem;font-weight:750}
#modalAddPayment #btnFillRemaining:hover,#modalAddPayment #btnFillRemaining:focus-visible{border-color:#b9baf4;color:var(--payment-brand-strong);background:#f8f8ff}
#modalAddPayment .payment-modal-alert{margin:0 0 .75rem;padding:.58rem .7rem;border-radius:9px;font-size:.65rem;line-height:1.45}
#modalAddPayment .payment-modal-alert-info{border:1px solid #b2ddff;color:#175cd3;background:#eff8ff}
#modalAddPayment .payment-modal-alert-warning{border:1px solid #fedf89;color:#a15c07;background:#fffaeb}
#modalAddPayment .payment-modal-error{font-size:.64rem!important}
#modalAddPayment .payment-modal-footer{display:flex;align-items:center;justify-content:flex-end;gap:.55rem;padding:.78rem 1.2rem;border-top:1px solid var(--payment-line);background:rgba(252,252,253,.96);box-shadow:0 -8px 18px rgba(16,24,40,.04);backdrop-filter:blur(8px)}
#modalAddPayment .payment-modal-footer .btn{min-height:40px;border-radius:10px;font-size:.7rem;font-weight:780}
#modalAddPayment .payment-modal-footer .btn-outline-secondary{border-color:#d0d5dd;color:#475467;background:var(--payment-surface)}
#modalAddPayment .payment-modal-footer .btn-outline-secondary:hover,#modalAddPayment .payment-modal-footer .btn-outline-secondary:focus-visible{border-color:#b9baf4;color:var(--payment-brand-strong);background:#f8f8ff}
#modalAddPayment .payment-modal-footer .btn-primary{border-color:var(--payment-brand);background:var(--payment-brand);box-shadow:0 7px 14px rgba(91,92,226,.2)}
#modalAddPayment .payment-modal-footer .btn-primary:hover,#modalAddPayment .payment-modal-footer .btn-primary:focus-visible{border-color:var(--payment-brand-strong);background:var(--payment-brand-strong);box-shadow:0 9px 18px rgba(65,66,187,.24)}
#modalAddPayment .payment-modal-footer .btn:disabled{cursor:not-allowed;opacity:.55;box-shadow:none}
body[data-theme="dark"] #modalAddPayment .payment-modal-content,
body[data-theme="dark"] #modalAddPayment .payment-modal-body{border-color:#334155;background:#0f172a}
body[data-theme="dark"] #modalAddPayment .payment-modal-header,
body[data-theme="dark"] #modalAddPayment .payment-modal-footer{border-color:#334155;background:#111827}
body[data-theme="dark"] #modalAddPayment .payment-modal-icon{color:#c4b5fd;background:rgba(91,92,226,.22)}
body[data-theme="dark"] #modalAddPayment .payment-modal-stat{border-color:#334155;background:#111827}
body[data-theme="dark"] #modalAddPayment .payment-modal-note{border-color:#1e40af;color:#bfdbfe;background:#172554}
body[data-theme="dark"] #modalAddPayment .payment-modal-note-icon{color:#bfdbfe;background:#1e3a8a}
body[data-theme="dark"] #modalAddPayment .form-control,
body[data-theme="dark"] #modalAddPayment .form-select{border-color:#475569;color:#f8fafc;background:#0f172a}
body[data-theme="dark"] #modalAddPayment .form-control::placeholder{color:#94a3b8}
body[data-theme="dark"] #modalAddPayment .input-group-text{border-color:#475569;color:#cbd5e1;background:#1e293b}
body[data-theme="dark"] #modalAddPayment #btnFillRemaining,
body[data-theme="dark"] #modalAddPayment .payment-modal-footer .btn-outline-secondary{border-color:#475569;color:#c7d2fe;background:#0f172a}
body[data-theme="dark"] #modalAddPayment .payment-modal-alert-info{border-color:#1e40af;color:#bfdbfe;background:#172554}
body[data-theme="dark"] #modalAddPayment .payment-modal-alert-warning{border-color:#92400e;color:#fcd34d;background:#451a03}
@media(max-width:767px){
  #modalAddPayment .modal-dialog{margin:.5rem}
  #modalAddPayment .payment-modal-header{grid-template-columns:36px minmax(0,1fr) 40px;padding:.9rem}
  #modalAddPayment .payment-modal-body{padding:.9rem}
  #modalAddPayment .payment-modal-summary{grid-template-columns:repeat(2,minmax(0,1fr))}
  #modalAddPayment .payment-modal-stat:last-child{grid-column:1 / -1}
  #modalAddPayment .payment-modal-footer{align-items:stretch;flex-direction:column-reverse;padding:.75rem .9rem}
  #modalAddPayment .payment-modal-footer .btn{width:100%;min-height:44px}
}
@media(prefers-reduced-motion:reduce){#modalAddPayment *{scroll-behavior:auto!important;transition-duration:.01ms!important;animation-duration:.01ms!important}}

@media(max-width:860px){
  .po-wrap{padding:.5rem .5rem 3.5rem}
  .po-topbar{padding:.5rem}
  .po-code{flex:1;min-width:150px;font-size:1.02rem}
  .po-supplier{display:none;}
  .po-topbar .po-pill:not(.po-status),.po-hide-mobile{display:none}
  .po-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:.45rem}
  .po-kpi{padding:.58rem .62rem}
  .po-value{font-size:1.08rem}
  .po-head{padding:.65rem .7rem}
  .po-body{padding:.65rem .7rem}
  .po-table-wrap{border:none;border-radius:0;overflow:visible}
  .po-table,.po-table tbody,.po-table tr,.po-table td{display:block;width:100%}
  .po-table thead{display:none}
  .po-table tr{border:1px solid rgba(148,163,184,.16);border-radius:8px;margin-bottom:.45rem;padding:.55rem .6rem;background:var(--card,#fff)}
  .po-table td{border:0;padding:0}
  .po-table td[data-label]{display:grid;grid-template-columns:7.2rem minmax(0,1fr);gap:.55rem;align-items:baseline;margin-top:.35rem}
  .po-table td[data-label]::before{content:attr(data-label);color:#94a3b8;font-size:.64rem;font-weight:900;letter-spacing:.05em;text-transform:uppercase}
  .po-table td[data-label] > *{min-width:0}
  .po-table td.item-cell{display:block}
  .po-table td.item-cell::before{display:none}
  .po-table td.po-r{text-align:left;margin-top:.35rem}
  .po-table td.progress-cell { margin-top: .5rem; padding-top: .5rem; border-top: 1px dashed rgba(148,163,184,.3); }
  .po-total{display:none!important}
  .po-workflow{overflow-x:visible;padding:.75rem .55rem}
  .po-workflow-list{min-width:0;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.2rem}
  .po-workflow-step{min-width:0;font-size:.62rem;line-height:1.2}
  .po-workflow-step:not(:last-child)::after{top:10px;left:calc(50% + 10px);right:calc(-50% + 10px)}
  .po-workflow-dot{width:22px;height:22px;font-size:.62rem}
  .po-workflow-label{white-space:normal;overflow-wrap:anywhere}
  .po-workflow-help{font-size:.68rem}
}
.mono {
    font-variant-numeric: tabular-nums;
    font-family: ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono";
}
/* Utility */
.badge-posted { background:rgba(22,163,74,.12);color:#15803d;border-color:rgba(22,163,74,.6); border: 1px solid; border-radius: 999px; padding: 0.15rem 0.5rem; font-size: 0.72rem; }
.badge-draft { background:rgba(148,163,184,.12);color:#64748b;border-color:rgba(148,163,184,.5); border: 1px solid; border-radius: 999px; padding: 0.15rem 0.5rem; font-size: 0.72rem; }
</style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $isAdmin = strtolower((string)($user->role ?? '')) === 'admin';
        $canSendWhatsapp = $user && ($user->isOwner() || $isAdmin || $user->isDeveloper());
        $supplierPhone = trim((string) ($order->supplier?->phone ?? ''));
        $canSeeMoney = $user?->canSeePurchasePrices() ?? false;
        $calculatedSubtotal = round((float) ($order->lines?->sum(fn ($line) => $line->calculatedLineTotal()) ?? 0), 2);
        $poDiscount = round(max(0, (float) ($order->discount ?? 0)), 2);
        $poTaxPercent = (float) ($order->tax_percent ?? 0);
        $poTaxBase = max(0, $calculatedSubtotal - $poDiscount);
        $calculatedTax = round($poTaxBase * $poTaxPercent / 100, 2);
        $calculatedGrandTotal = round($poTaxBase + $calculatedTax + (float) ($order->shipping_cost ?? 0), 2);

        // Status PO
        $status = (string) ($order->status ?? 'draft');
        $approvalEnabled = (bool) config('purchasing.po_approval_enabled', false);
        $statusClass = match ($status) {
            'approved' => 'tag tag-status-approved',
            'cancelled' => 'tag tag-status-cancelled',
            default => 'tag tag-status-draft',
        };

        // GRN list
        $grnList = $order->purchaseReceipts ?? collect();
        $grnCount = $grnList->count();
        $hasActivePayments = $order->activePayments()->exists();
        $canEditApprovedWithoutTransactions = $status === 'approved'
            && $grnCount === 0
            && !$hasActivePayments
            && !$order->isLocked()
            && $user
            && ($user->canSeePurchasePrices() || $user->isDeveloper());
        $canCancelApprovedWithoutTransactions = $status === 'approved'
            && $grnCount === 0
            && !$hasActivePayments
            && $user
            && ($user->isOwner() || $user->isDeveloper());
        // Payment yang sudah di-void tetap tersimpan sebagai audit trail di
        // relasi payments, tetapi tidak boleh tampil sebagai pembayaran aktif.
        $activePaymentRows = ($order->payments ?? collect())
            ->filter(fn ($payment) => is_null($payment->voided_at))
            ->values();

        // Payment method "preferensi" PO
        $pm = $order->paymentMethod ?? null;

        // Hutang real berbasis GRN posted (dari controller show)
        $grnPostedTotal = (float) ($grnPostedTotal ?? 0);
        $returnPostedTotal = (float) ($returnPostedTotal ?? 0);
        $apDebt = (float) ($apDebt ?? max(0, round($grnPostedTotal - $returnPostedTotal, 2)));
        $paidPaymentTotal = (float) ($paidPaymentTotal ?? 0); // type=payment
        $dpTotal = (float) ($dpTotal ?? 0); // type=dp
        // Total uang yang sudah dibayarkan ke supplier. dp_apply tidak
        // dihitung lagi karena hanya memindahkan pencatatan DP ke hutang.
        $loanAppliedTotal = (float) ($loanAppliedTotal ?? 0);
        $paidAmount = round($paidPaymentTotal + $dpTotal + $loanAppliedTotal, 2);

        // DP APPLY total (buat UI)
        $dpAppliedTotal =
            (float) ($dpAppliedTotal ?? ($order->activePayments()?->where('type', 'dp_apply')->sum('amount') ?? 0));
        $advanceTotal = round($dpTotal + $loanAppliedTotal, 2);
        $dpAvailable = \App\Models\PurchaseOrder::normalizePaymentRemainder($advanceTotal - $dpAppliedTotal);
        $advanceAvailable = \App\Models\PurchaseOrder::normalizePaymentRemainder($advanceTotal - $dpAppliedTotal);

        // outstanding hutang (should include dp_apply)
        $apOutstanding = \App\Models\PurchaseOrder::normalizePaymentRemainder(
            (float) ($apOutstanding ?? ($apDebt - $paidPaymentTotal - $dpAppliedTotal))
        );

        // Sisa sampai Rp1 dianggap selisih pembulatan dan ditampilkan sebagai nol.
        $formatPaymentMoney = static function ($value): string {
            $value = (float) $value;
            $decimals = abs($value - round($value)) > 0.0001 ? 2 : 0;
            return rupiah($value, $decimals);
        };

        // status bayar
        $payStatus = (string) ($order->payment_status ?? 'unpaid');
        $payBadgeClass = match ($payStatus) {
            'paid' => 'pay-badge pay-paid',
            'partial' => 'pay-badge pay-partial',
            'overpaid' => 'pay-badge pay-overpaid',
            default => 'pay-badge pay-unpaid',
        };
        $payStatusLabel = match ($payStatus) {
            'paid' => 'LUNAS',
            'partial' => 'SEBAGIAN BAYAR',
            'overpaid' => 'PIUTANG SUPPLIER',
            default => 'BELUM BAYAR',
        };

        $canPay = $status !== 'cancelled';
        $hasPayments = ($order->payments?->count() ?? 0) > 0;
        $canManagePayments = $user?->isOwner() ?? false;

        // strict cash/bank list
        $cashAccountsCol = collect($cashAccounts ?? []);
        $cash1101 = $cashAccountsCol->firstWhere('code', '1101');

        $transferBankCodes = ['1111', '1112', '1113', '1114'];
        $transferBanks = $cashAccountsCol
            ->filter(fn($a) => in_array((string) ($a->code ?? ''), $transferBankCodes, true))
            ->values();

        // guard: payment hanya boleh kalau hutang real sudah ada
        $hasAp = $grnPostedTotal > 0.0001;
        $poGrandTotal = (float) ($order->grand_total ?? 0);
        $dpRemaining = max(0, round($poGrandTotal - $advanceTotal, 2));
        $supplierReceivable = \App\Models\PurchaseOrder::normalizePaymentRemainder((float) ($order->paid_amount ?? 0) - $poGrandTotal);
        $canPaySettlement = $canPay && $hasAp && $apOutstanding > 0;
        // DP boleh melebihi nilai PO agar selisihnya tercatat sebagai piutang supplier.
        // Jika total pembayaran PO sudah berstatus LUNAS, jangan tawarkan
        // pembayaran baru dari halaman ini. Saldo DP yang masih tersisa tetap
        // ditampilkan sebagai uang muka supplier dan akan dipakai saat GRN
        // berikutnya, bukan melalui tombol Bayar Sekarang lagi.
        $canPayDp = $canPay
            && $poGrandTotal > 0.01
            && !in_array($payStatus, ['paid', 'overpaid'], true);
        $canOpenPayment = $canPaySettlement || $canPayDp;
        // Pertahankan label aksi utama agar konsisten dengan halaman purchasing;
        // konteks DP/pelunasan dijelaskan di dalam modal.
        $paymentButtonLabel = 'Bayar Sekarang';
        $paymentModalTitle = $canPaySettlement && $canPayDp
            ? 'Bayar Hutang / Tambah DP'
            : ($canPaySettlement ? 'Bayar Hutang Supplier' : 'Tambah DP Supplier');
        $modalPaymentMethods = collect($paymentMethods ?? [])
            ->filter(fn ($pmOpt) => ! in_array(strtoupper((string) ($pmOpt->code ?? '')), ['DP_APPLY', 'LOAN_APPLY'], true))
            ->values();
        $selectedModalPaymentMethodId = old('payment_method_id', $order->payment_method_id);
        $selectedModalPaymentMethod = $modalPaymentMethods->firstWhere('id', (int) $selectedModalPaymentMethodId);
        if (! $selectedModalPaymentMethod || strtolower((string) ($selectedModalPaymentMethod->mode ?? '')) === 'credit') {
            $selectedModalPaymentMethodId = $modalPaymentMethods
                ->first(fn ($pmOpt) => strtolower((string) ($pmOpt->mode ?? '')) === 'cash')?->id
                ?? $modalPaymentMethods
                    ->first(fn ($pmOpt) => strtolower((string) ($pmOpt->mode ?? '')) === 'transfer')?->id
                ?? $selectedModalPaymentMethodId;
        }

        // apply DP guard
        $canApplyDp = $canPay && $hasAp && $dpAvailable > 0 && $apOutstanding > 0;
        $maxApplyDp = max(0, round(min($dpAvailable, $apOutstanding), 2));

        // Status UI harus menjawab pertanyaan akuntansi yang benar: apakah
        // hutang AP sudah nol? Status paid saja tidak cukup karena DP juga
        // membuat payment_status menjadi paid.
        $apUiStatus = match (true) {
            $hasAp && $apOutstanding > 0.0001 && $advanceAvailable > 0.0001 => 'awaiting_offset',
            $hasAp && $apOutstanding > 0.0001 => 'ap_outstanding',
            $hasAp => 'ap_paid',
            $advanceTotal > 0.0001 => 'dp_recorded',
            default => $payStatus,
        };
        $payBadgeClass = match ($apUiStatus) {
            'ap_paid', 'paid' => 'pay-badge pay-paid',
            'awaiting_offset', 'ap_outstanding', 'partial', 'dp_recorded' => 'pay-badge pay-partial',
            'overpaid' => 'pay-badge pay-overpaid',
            default => 'pay-badge pay-unpaid',
        };
        $payStatusLabel = match ($apUiStatus) {
            'ap_paid' => 'HUTANG LUNAS',
            'awaiting_offset' => 'MENUNGGU OFFSET DP',
            'ap_outstanding' => 'HUTANG BELUM LUNAS',
            'dp_recorded' => 'DP TERCATAT',
            default => $payStatusLabel,
        };
        $receivedStatus = (string) ($order->received_status ?? 'not_received');
        $workflowCurrent = match (true) {
            $status === 'cancelled' => 0,
            $status === 'closed' => $approvalEnabled ? 4 : 3,
            $receivedStatus === 'fully_received' && $apUiStatus === 'ap_paid' => $approvalEnabled ? 3 : 2,
            $receivedStatus === 'fully_received' => $approvalEnabled ? 3 : 2,
            $receivedStatus === 'partial' => $approvalEnabled ? 2 : 1,
            $approvalEnabled && $status === 'approved' => 1,
            default => 0,
        };
        $workflowSteps = $approvalEnabled
            ? [
                ['label' => 'Draft', 'icon' => '1'],
                ['label' => 'Approved', 'icon' => '2'],
                ['label' => $receivedStatus === 'partial' ? 'GRN Sebagian' : ($receivedStatus === 'fully_received' ? 'GRN Selesai' : 'GRN'), 'icon' => '3'],
                ['label' => $apUiStatus === 'ap_paid' ? 'Lunas' : 'Bayar', 'icon' => '4'],
                ['label' => 'Closed', 'icon' => '5'],
            ]
            : [
                ['label' => 'Draft', 'icon' => '1'],
                ['label' => $receivedStatus === 'partial' ? 'GRN Sebagian' : ($receivedStatus === 'fully_received' ? 'GRN Selesai' : 'GRN'), 'icon' => '2'],
                ['label' => $apUiStatus === 'ap_paid' ? 'Lunas' : 'Bayar', 'icon' => '3'],
                ['label' => 'Closed', 'icon' => '4'],
            ];
        $draftGrns = $grnList
            ->filter(fn ($grn) => ($grn->status ?? 'draft') === 'draft')
            ->sortBy('created_at')
            ->values();
        $staleDraftDays = max(1, (int) config('purchasing.draft_grn_stale_days', 3));
        $staleDraftGrns = $draftGrns->filter(
            fn ($grn) => optional($grn->created_at)->lt(now()->subDays($staleDraftDays))
        )->values();
        $apSettledTotal = round($paidPaymentTotal + $dpAppliedTotal, 2);
        $paymentModalId = '#modalAddPayment';
        $linePurchaseUnits = ($order->lines ?? collect())
            ->map(fn ($line) => $line->effectivePurchaseUnit())
            ->filter()
            ->unique()
            ->values();
        $totalQty = $linePurchaseUnits->count() === 1
            ? (float) ($order->lines?->sum('qty') ?? 0)
            : null;
        $totalQtyUnit = $linePurchaseUnits->count() === 1
            ? $linePurchaseUnits->first()
            : ($linePurchaseUnits->isEmpty() ? null : 'beragam');
        $hasDiscount = (float) ($order->lines?->sum('discount') ?? 0) > 0.0001;

        // for JS/open modal routing
        $voidActionTemplate = route('purchasing.purchase_orders.payments.void', [
            'purchase_order' => $order->id,
            'payment' => '___PAYMENT___',
        ]);
    @endphp

    
<div class="po-topbar">
    <a href="{{ route('purchasing.purchase_orders.index') }}" class="po-btn" title="Kembali"><i class="bi bi-arrow-left"></i> Kembali</a>
    
    @php
       $statusBadgeClass = match($status) {
           'approved' => 'approved',
           'closed' => 'closed',
           'cancelled' => 'cancelled',
           default => 'draft'
       };
       $statusLabel = match($status) {
           'approved' => 'APPROVED',
           'closed' => 'CLOSED',
           'cancelled' => 'CANCELLED',
           default => 'DRAFT'
       };
    @endphp
    
    <span class="po-code">{{ $order->code }}</span>
    <span class="po-supplier d-none d-md-inline">{{ optional($order->supplier)->name ?? 'Purchase Order' }}</span>
    <span class="po-pill po-status {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
    @if ($canSeeMoney)
        <span class="po-pill {{ $payBadgeClass }}">{{ $payStatusLabel }}</span>
    @endif
    @if ($canManagePayments && ($canApplySupplierLoan ?? false))
        <button type="button" class="po-btn po-info" data-bs-toggle="modal" data-bs-target="#modalApplySupplierLoan" title="Alokasikan saldo pinjaman supplier ke PO">
            <i class="bi bi-person-check d-inline-block d-md-none"></i>
            <span class="d-none d-md-inline">Alokasi Pinjaman</span>
        </button>
    @endif
    
    @if ($order->isLocked())
        <span class="po-pill po-status" style="background:rgba(245,158,11,.1);color:#92400e;border:1px solid rgba(245,158,11,.3);" title="{{ $order->lock_reason }} ({{ optional($order->locked_at)->format('d/m/Y H:i') }})"><i class="bi bi-lock-fill"></i> Locked</span>
    @endif
    
    <span class="po-spacer"></span>

    {{-- PRIMARY ACTIONS --}}
    @if ($canManagePayments && $canOpenPayment)
        @if ($canApplyDp)
            <button type="button" class="po-btn po-success" data-bs-toggle="modal" data-bs-target="#modalApplyDp" title="Offset DP ke Hutang AP">
                <i class="bi bi-arrow-left-right d-inline-block d-md-none"></i>
                <span class="d-none d-md-inline">Offset DP</span>
            </button>
        @endif
        <button type="button" class="po-btn {{ $canApplyDp ? 'po-primary' : 'po-success' }}" data-payment-type="{{ $canPaySettlement ? 'payment' : 'dp' }}" data-bs-toggle="modal" data-bs-target="#modalAddPayment" title="{{ $canPaySettlement ? 'Bayar hutang supplier' : 'Tambah uang muka supplier' }}">
            <i class="bi bi-cash-coin d-inline-block d-md-none"></i>
            <span class="d-none d-md-inline">{{ $paymentButtonLabel }}</span>
        </button>
    @endif
    
    {{-- Terima (buat GRN) --}}
    @if ($user && ($user->isOwner() || $isAdmin) && $order->isReceivableForGrn() && $status !== 'cancelled' && ($canCreateGrn ?? true))
         <a href="{{ route('purchasing.purchase_receipts.create_from_order', $order->id) }}" class="po-btn po-info" title="Terima Barang"><i class="bi bi-box-seam d-inline-block d-md-none"></i> <span class="d-none d-md-inline">Terima</span></a>
    @endif

    @if ($canSendWhatsapp && $supplierPhone !== '')
        <a href="{{ route('whatsapp.messages.compose.purchase_order', $order->id) }}"
           class="po-btn {{ $lastWhatsappMessage ? 'po-wa-sent' : 'po-wa' }}"
           title="{{ $lastWhatsappMessage ? 'Sudah dikirim pada ' . optional($lastWhatsappMessage->sent_at)->format('d/m/Y H:i') . '. Klik untuk kirim ulang.' : 'Review dan kirim ke supplier via WhatsApp' }}">
            <i class="bi {{ $lastWhatsappMessage ? 'bi-check-circle-fill' : 'bi-whatsapp' }}"></i>
            <span class="d-none d-md-inline">{{ $lastWhatsappMessage ? 'Sudah dikirim' : 'WA Supplier' }}</span>
        </a>
    @elseif ($canSendWhatsapp)
        <button type="button" class="po-btn" disabled title="Nomor WhatsApp supplier belum diisi">
            <i class="bi bi-whatsapp"></i>
            <span class="d-none d-md-inline">WA Supplier</span>
        </button>
    @endif

    {{-- Opsi Lainnya --}}
    <div class="dropdown d-inline-block">
        <button class="po-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Opsi Lainnya"><i class="bi bi-three-dots-vertical"></i></button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="font-size: .85rem; border-radius: 12px; padding: .5rem 0;">
            @if ($canSeeMoney)
                <li>
                    <a class="dropdown-item py-2" href="{{ route('purchasing.purchase_orders.print_dot_matrix', $order->id) }}">
                        <i class="bi bi-printer me-2 text-muted"></i> Cetak (Dot Matrix)
                    </a>
                </li>
            @endif
            @if (($status === 'draft' || $canEditApprovedWithoutTransactions) && (!$order->isLocked() || ($user && $user->canSeePurchasePrices())))
                <li>
                    <a class="dropdown-item py-2" href="{{ route('purchasing.purchase_orders.edit', $order->id) }}">
                        <i class="bi bi-pencil me-2 text-muted"></i> Edit PO
                    </a>
                </li>
            @endif
            @if (config('purchasing.po_approval_enabled', false) && $status === 'draft' && $user && ($user->isOwner() || $user->isDeveloper() || in_array($user->role ?? '', ['admin'], true)) && $grnCount === 0)
                <li>
                    <form action="{{ route('purchasing.purchase_orders.approve', $order->id) }}" method="POST"
                          onsubmit="return confirm('Approve PO ini? Setelah approved, PO dapat diproses ke GRN.');">
                        @csrf
                        <button type="submit" class="dropdown-item py-2 text-success fw-semibold">
                            <i class="bi bi-check-circle me-2"></i> Approve PO
                        </button>
                    </form>
                </li>
            @endif
            @if ($canSeeMoney && $canPay && $hasAp)
                <li>
                    <button type="button" class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#modalApplyDp" @if (!$canApplyDp) disabled @endif>
                        <i class="bi bi-arrow-left-right me-2 text-muted"></i> Offset DP
                    </button>
                </li>
            @endif
            @if ($hasAp && $user && ($user->isOwner() || in_array($user->role ?? '', ['accounting', 'developer'])))
                @if (\Illuminate\Support\Facades\Route::has('purchasing.supplier_invoices.create'))
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('purchasing.supplier_invoices.create', ['purchase_order_id' => $order->id]) }}">
                            <i class="bi bi-receipt me-2 text-muted"></i> Buat Invoice Supplier
                        </a>
                    </li>
                @endif
            @endif
            @if ($status === 'draft' && $user && in_array($user->role, ['owner','admin']))
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('purchasing.purchase_orders.cancel', $order->id) }}" method="POST"
                          onsubmit="return confirm('Cancel PO ini?');">
                        @csrf
                        <button type="submit" class="dropdown-item py-2 text-danger">
                            <i class="bi bi-x-circle me-2"></i> Cancel PO
                        </button>
                    </form>
                </li>
            @endif
            @if ($canCancelApprovedWithoutTransactions)
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('purchasing.purchase_orders.cancel', $order->id) }}" method="POST"
                          onsubmit="return confirm('Cancel PO ini? Pastikan supplier tidak mengirim barang dan seluruh DP sudah di-void/refund.');">
                        @csrf
                        <button type="submit" class="dropdown-item py-2 text-danger fw-semibold">
                            <i class="bi bi-x-circle me-2"></i> Cancel PO
                        </button>
                    </form>
                </li>
            @endif
            @if (($status === 'approved' || (!$approvalEnabled && $order->isLocked())) && $user && $user->isOwner() && $grnCount > 0)
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route('purchasing.purchase_orders.close', $order->id) }}" method="POST"
                          onsubmit="return confirm('Tutup Paksa (Short Close) PO ini? Pastikan semua tagihan telah lunas.');">
                        @csrf
                        <button type="submit" class="dropdown-item py-2 text-dark fw-semibold">
                            <i class="bi bi-check2-all me-2"></i> Tutup Paksa PO
                        </button>
                    </form>
                </li>
            @endif
        </ul>
    </div>
</div>

<div class="po-wrap">
    <div class="po-document-date-row" aria-label="Tanggal dokumen PO">
        <i class="bi bi-calendar3" aria-hidden="true"></i>
        <span class="po-document-date-label">Tanggal dokumen</span>
        <strong>{{ id_day($order->date) }}</strong>
    </div>

    <nav class="po-workflow" aria-label="Tahapan Purchase Order">
        <ol class="po-workflow-list">
            @foreach ($workflowSteps as $stepIndex => $step)
                @php
                    $isCancelledStep = $status === 'cancelled' && $stepIndex === 0;
                    $stepClass = $isCancelledStep
                        ? 'is-cancelled'
                        : ($stepIndex < $workflowCurrent ? 'is-done' : ($stepIndex === $workflowCurrent ? 'is-current' : ''));
                @endphp
                <li class="po-workflow-step {{ $stepClass }}" aria-current="{{ $stepIndex === $workflowCurrent ? 'step' : 'false' }}">
                    <span class="po-workflow-dot" aria-hidden="true">{{ $isCancelledStep ? '!' : ($stepIndex < $workflowCurrent ? '✓' : $step['icon']) }}</span>
                    <span class="po-workflow-label">{{ $isCancelledStep ? 'Cancelled' : $step['label'] }}</span>
                </li>
            @endforeach
        </ol>
        <div class="po-workflow-help">
            @if ($status === 'cancelled')
                PO dibatalkan dan tidak dapat dilanjutkan ke penerimaan.
            @elseif ($status === 'draft' && config('purchasing.po_approval_enabled', false))
                PO masih Draft. Approve PO terlebih dahulu sebelum membuat GRN.
            @elseif ($draftGrns->isNotEmpty())
                GRN Draft belum mengubah stok atau jurnal. Post atau hapus draft setelah penerimaan diverifikasi.
            @elseif (!$hasAp)
                Hutang AP baru terbentuk setelah GRN diposting.
            @elseif ($workflowCurrent === ($approvalEnabled ? 3 : 2) && $apUiStatus !== 'ap_paid')
                Barang sudah diterima; lanjutkan pembayaran untuk menutup hutang.
            @else
                Ikuti tahapan dari kiri ke kanan sampai PO ditutup.
            @endif
        </div>
    </nav>

    @if ($status === 'draft' && config('purchasing.po_approval_enabled', false))
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3 mt-2 py-2 px-3" style="font-size:.84rem;">
            <i class="bi bi-shield-exclamation mt-1" aria-hidden="true"></i>
            <div>
                <strong>PO belum aktif untuk penerimaan.</strong>
                Dokumen ini masih Draft. Periksa supplier, item, qty, harga, dan total; setelah benar gunakan <b>Opsi Lainnya → Approve PO</b>.
            </div>
        </div>
    @endif

    @if ($staleDraftGrns->isNotEmpty())
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3 mt-2 py-2 px-3" style="font-size:.84rem;">
            <i class="bi bi-hourglass-split mt-1" aria-hidden="true"></i>
            <div>
                <strong>{{ $staleDraftGrns->count() }} Draft GRN belum selesai.</strong>
                Draft lebih dari {{ $staleDraftDays }} hari belum mengubah stok/jurnal, tetapi sementara mengunci line PO. Buka detail GRN untuk post atau hapus draft.
            </div>
        </div>
    @endif

    @if ($canSeeMoney && $hasAp && $dpAvailable > 0.0001 && $apOutstanding > 0.0001)
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3 mt-2 py-2 px-3" style="font-size:.84rem;">
            <i class="bi bi-exclamation-triangle-fill mt-1" aria-hidden="true"></i>
            <div>
                <strong>DP belum mengurangi hutang.</strong>
                Tersedia <span class="mono">{{ rupiah($dpAvailable) }}</span> untuk di-offset ke hutang AP
                <span class="mono">{{ $formatPaymentMoney($apOutstanding) }}</span>.
                Gunakan tombol <b>Offset DP</b>; aksi ini tidak mengeluarkan uang baru.
            </div>
        </div>
    @endif

    @if (($status === 'approved' || !$approvalEnabled) && ($order->received_status ?? 'not_received') === 'partial' && ($order->payment_status ?? 'unpaid') === 'paid')
        <div class="alert alert-warning d-flex align-items-center mb-3 mt-2" style="font-size: .85rem;">
            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
            <div>
                <strong>Informasi Penerimaan Sebagian:</strong> Terdapat barang yang tidak lengkap/di-reject pada PO ini, namun tagihan untuk barang yang diterima sudah lunas. Jika tidak ada penerimaan lanjutan dari Supplier, Anda dapat menutup transaksi ini dengan memilih <b>Opsi Lainnya > Tutup Paksa PO</b>.
            </div>
        </div>
    @endif
    
    <div class="po-grid mt-2">
        <div class="po-card po-kpi">
            <div class="po-label">Item Batch</div>
            <div class="po-value">
                {{ $order->lines->count() }} <span class="po-muted" style="font-size:.8rem;font-weight:500;">Tipe</span>
                @if ($totalQty !== null)
                    <span class="po-muted mx-1 fw-normal" style="opacity:.4;">•</span>
                    {{ decimal_id($totalQty, 2) }} <span class="po-muted" style="font-size:.8rem;font-weight:500;">{{ $totalQtyUnit }}</span>
                @elseif ($totalQtyUnit)
                    <span class="po-muted mx-1 fw-normal" style="opacity:.4;">•</span>
                    <span class="po-muted" style="font-size:.8rem;font-weight:500;">Satuan {{ $totalQtyUnit }}</span>
                @endif
            </div>
        </div>
        @if ($canSeeMoney)
        <div class="po-card po-kpi">
            <div class="po-label">Total PO</div>
            <div class="po-value">{{ rupiah($poGrandTotal) }}</div>
        </div>
        <div class="po-card po-kpi">
            <div class="po-label">Nilai Barang Diterima</div>
            <div class="po-value">{{ rupiah($grnPostedTotal) }}</div>
        </div>
        <div class="po-card po-kpi">
            <div class="po-label">Dana Dibayar</div>
            <div class="po-value" style="color:#15803d;">{{ $formatPaymentMoney($paidAmount) }}</div>
            <div class="po-muted" style="font-size:.7rem;">DP + pelunasan</div>
        </div>
        <div class="po-card po-kpi">
            <div class="po-label">DP Belum Dipakai</div>
            <div class="po-value" style="color:{{ $dpAvailable > 0.0001 ? '#b45309' : '#15803d' }};">{{ $formatPaymentMoney($dpAvailable) }}</div>
            <div class="po-muted" style="font-size:.7rem;">belum mengurangi hutang</div>
        </div>
        <div class="po-card po-kpi">
            <div class="po-label">Hutang AP Tersisa</div>
            @if ($hasAp)
                <div class="po-value" style="color:{{ $apOutstanding > 0.0001 ? '#b91c1c' : '#15803d' }};">{{ $formatPaymentMoney($payStatus === 'overpaid' ? $supplierReceivable : $apOutstanding) }}</div>
                <div class="po-muted" style="font-size:.7rem;">GRN − payment − offset</div>
            @else
                <div class="po-value" style="color:#64748b;">—</div>
                <div class="po-muted" style="font-size:.7rem;">Belum terbentuk · post GRN dulu</div>
            @endif
        </div>
        @else
        <div class="po-card po-kpi">
            <div class="po-label">Status</div>
            <div class="po-value" style="font-size:1rem">{{ $statusLabel }}</div>
        </div>
        @endif
    </div>

    <div class="po-tabs" role="tablist">
        <button type="button" class="po-tab active" data-tab="item">Rincian Barang <span class="po-tab-count">{{ $order->lines->count() }}</span></button>
        @if ($user && ($user->isOwner() || $isAdmin))
        <button type="button" class="po-tab" data-tab="grn">Penerimaan (GRN) <span class="po-tab-count">{{ $grnCount }}</span></button>
        @endif
        @if ($canSeeMoney && $activePaymentRows->count() > 0)
        <button type="button" class="po-tab" data-tab="payments">Riwayat Bayar <span class="po-tab-count">{{ $activePaymentRows->count() }}</span></button>
        @endif
    </div>

    {{-- TAB ITEM BARANG --}}
    <div class="po-tabpane active" id="po-tab-item" role="tabpanel">
        <div class="po-card">
            <div class="po-head">
                <div>
                    <div class="po-title">Barang Dipesan</div>
                    <div class="po-muted">Ringkasan barang yang dipesan pada dokumen PO ini.</div>
                </div>
            </div>
            <div class="po-body">
                @if($order->lines->isEmpty())
                    <div class="po-empty">Belum ada item.</div>
                @else
                    <div class="po-table-wrap">
                        <table class="po-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="po-r">Qty</th>
                                    <th>Satuan Beli</th>
                                    <th>Konversi Stok</th>
                                    @if($canSeeMoney)
                                        <th class="po-r po-hide-mobile">@ Harga</th>
                                        <th class="po-r po-hide-mobile">Subtotal</th>
                                    @endif
                                    <th class="po-r">Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->lines as $line)
                                    @php
                                        $hasDiscount = (float) $line->discount > 0.0001;
                                        $rcv = (float) ($receivedByLine[$line->id] ?? 0);
                                        $rej = (float) ($rejectedByLine[$line->id] ?? 0);
                                        $ret = (float) ($returnedByLine[$line->id] ?? 0);
                                        $qtyOut = max(0, (float) $line->qty - $rcv - $rej);
                                        // Selalu hitung dari snapshot baris agar detail
                                        // konsisten dengan qty, harga, dan konversi stok.
                                        $lineSubtotal = $line->calculatedLineTotal();
                                    @endphp
                                    <tr>
                                        <td class="item-cell">
                                            <div class="po-code-cell">{{ $line->item->code ?? '-' }}</div>
                                            <div class="po-name">{{ $line->item->name ?? '-' }}</div>
                                            @if($line->expenseAccount)
                                                <div class="text-muted mt-1" style="font-size: .7rem;"><i class="bi bi-wallet2"></i> {{ $line->expenseAccount->code }} - {{ $line->expenseAccount->name }}</div>
                                            @endif
                                            
                                            {{-- MOBILE EXTRA INFO --}}
                                            <div class="d-md-none mt-1">
                                                @if($canSeeMoney)
                                                    <div style="font-size:.8rem;color:#475569;">{{ angka($line->unit_price) }} / {{ $line->effectivePurchaseUnit() }} × {{ decimal_id($line->qty, 2) }} = <b>{{ angka($lineSubtotal) }}</b></div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="po-r" data-label="Qty">
                                            <div class="po-code-cell">{{ decimal_id($line->qty, 2) }}</div>
                                        </td>
                                        <td data-label="Satuan beli">
                                            <span class="po-unit-value">{{ $line->effectivePurchaseUnit() }}</span>
                                        </td>
                                        <td data-label="Konversi stok">
                                            @if($line->effectiveConversionFactor() != 1)
                                                <span class="po-unit-conversion"><strong>{{ decimal_id($line->effectiveConversionFactor(), 2) }}</strong> {{ $line->effectiveStockUnit() }} / {{ $line->effectivePurchaseUnit() }}</span>
                                            @else
                                                <span class="po-unit-conversion">{{ $line->effectiveStockUnit() }}</span>
                                            @endif
                                        </td>
                                        @if($canSeeMoney)
                                            <td class="po-r po-hide-mobile">
                                                {{ angka($line->unit_price) }}
                                                @if($hasDiscount)
                                                    <br><span class="text-danger" style="font-size:.7rem">Disc: -{{ angka($line->discount) }}</span>
                                                @endif
                                            </td>
                                            <td class="po-r po-hide-mobile">{{ angka($lineSubtotal) }}</td>
                                        @endif
                                        <td class="progress-cell" style="min-width: 140px; vertical-align: top;">
                                            @if ($rcv > 0)
                                                <div class="d-flex justify-content-between mb-1" style="font-size:.78rem;">
                                                    <span style="color:#64748b;">Terima</span>
                                                    <strong style="color:#15803d; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;">{{ decimal_id($rcv, 2) }} {{ $line->effectivePurchaseUnit() }}</strong>
                                                </div>
                                            @endif
                                            @if ($ret > 0)
                                                <div class="d-flex justify-content-between mb-1" style="font-size:.78rem;">
                                                    <span style="color:#64748b;">Retur</span>
                                                    <strong style="color:#b91c1c; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;">{{ decimal_id($ret, 2) }} {{ $line->effectivePurchaseUnit() }}</strong>
                                                </div>
                                            @endif
                                            @if ($rej > 0)
                                                <div class="d-flex justify-content-between mb-1" style="font-size:.78rem;">
                                                    <span style="color:#64748b;">Reject</span>
                                                    <strong style="color:#b45309; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;">{{ decimal_id($rej, 2) }} {{ $line->effectivePurchaseUnit() }}</strong>
                                                </div>
                                            @endif
                                            @if ($qtyOut > 0)
                                                <div class="d-flex justify-content-between" style="font-size:.78rem;">
                                                    <span style="color:#64748b;">Sisa</span>
                                                    <strong style="color:#d97706; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;">{{ decimal_id($qtyOut, 2) }} {{ $line->effectivePurchaseUnit() }}</strong>
                                                </div>
                                            @endif
                                            @if ($rcv == 0 && $rej == 0 && $ret == 0 && $qtyOut == 0)
                                                <div class="text-end text-muted">-</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($canSeeMoney)
                    <div class="d-flex justify-content-end mt-3">
                        <div style="width:min(100%, 360px); border-top:1px solid var(--line); padding-top:.65rem;">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="po-muted">Subtotal Items</span>
                                <strong>{{ rupiah($calculatedSubtotal) }}</strong>
                            </div>
                            @if ($poDiscount > 0)
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="po-muted">Diskon PO</span>
                                    <strong class="text-danger">-{{ rupiah($poDiscount) }}</strong>
                                </div>
                            @endif
                            @if ($poTaxPercent > 0)
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="po-muted">PPN ({{ decimal_id($poTaxPercent, 2) }}%)</span>
                                    <strong>{{ rupiah($calculatedTax) }}</strong>
                                </div>
                            @endif
                            @if ((float) ($order->shipping_cost ?? 0) > 0)
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="po-muted">Ongkir</span>
                                    <strong>{{ rupiah($order->shipping_cost) }}</strong>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between pt-2 mt-2" style="border-top:1px dashed var(--line); font-size:1.05rem;">
                                <strong>Grand Total</strong>
                                <strong>{{ rupiah($calculatedGrandTotal) }}</strong>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>{{-- /po-tab-item --}}

    {{-- TAB GRN --}}
    @if ($user && ($user->isOwner() || $isAdmin))
    <div class="po-tabpane" id="po-tab-grn" role="tabpanel">
        <div class="po-card">
            <div class="po-head">
                <div>
                    <div class="po-title">Penerimaan Barang (GRN)</div>
                    <div class="po-muted">Daftar surat jalan yang telah diterbitkan untuk PO ini.</div>
                </div>
            </div>
            <div class="po-body">
                @if ($grnCount === 0)
                    <div class="po-empty">Belum ada penerimaan.</div>
                @else
                    <div class="po-table-wrap">
                        <table class="po-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Dokumen</th>
                                    <th>Gudang</th>
                                    @if ($canSeeMoney)
                                    <th class="po-r">Total</th>
                                    @endif
                                    <th style="text-align:center;">Status</th>
                                    <th class="po-r">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($grnList as $grn)
                                    @php
                                        $isPosted = ($grn->status ?? 'draft') === 'posted';
                                        $badgeStatusClass = $isPosted ? 'badge-posted' : 'badge-draft';
                                        $statusLabelGRN = $isPosted ? 'POSTED' : 'DRAFT';
                                        $wh = $grn->warehouse ?? null;
                                    @endphp
                                    <tr>
                                        <td class="po-code-cell" style="font-weight:normal;color:#64748b;">{{ $grn->date ? id_date($grn->date) : '-' }}</td>
                                        <td>
                                            <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}" class="po-code-cell text-decoration-none" style="color:#0284c7;">{{ $grn->code ?? $grn->id }}</a>
                                            <div class="d-md-none mt-1">
                                                @if ($wh) <span style="font-size:.7rem; color:#64748b;">{{ $wh->name }}</span> @endif
                                            </div>
                                        </td>
                                        <td class="po-hide-mobile">
                                            @if ($wh)
                                                <div style="font-weight:700;">{{ $wh->code }}</div>
                                                <div class="po-muted" style="font-size:.7rem;">{{ $wh->name }}</div>
                                            @else
                                                <span class="po-muted">-</span>
                                            @endif
                                        </td>
                                        @if ($canSeeMoney)
                                        <td class="po-r" style="font-weight:700;">{{ isset($grn->grand_total) ? rupiah($grn->grand_total) : '—' }}</td>
                                        @endif
                                        <td style="text-align:center;">
                                            <span class="{{ $badgeStatusClass }}">{{ $statusLabelGRN }}</span>
                                        </td>
                                        <td class="po-r">
                                            <div class="d-flex justify-content-end gap-1">
                                                <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}" class="po-btn" style="min-height:28px;padding:.1rem .5rem;">Detail</a>
                                                @if (!$isPosted)
                                                    <form action="{{ route('purchasing.purchase_receipts.post', $grn->id) }}" method="POST" onsubmit="return confirm('Post GRN ini? Setelah di-post, stok akan ter-update.');">
                                                        @csrf
                                                        <button type="submit" class="po-btn po-success" style="min-height:28px;padding:.1rem .5rem;">Post</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- TAB PEMBAYARAN --}}
    @if ($canSeeMoney && $activePaymentRows->count() > 0)
    <div class="po-tabpane" id="po-tab-payments" role="tabpanel">
        <div class="po-card">
            <div class="po-head">
                <div>
                    <div class="po-title">Riwayat Pembayaran</div>
                <div class="po-muted">Pembayaran, DP, dan pemindahan DP ke hutang AP.</div>
                </div>
            </div>
            <div class="po-body">
                <div class="po-table-wrap">
                    <table class="po-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Tipe</th>
                                <th>Metode</th>
                                <th class="po-r">Nominal</th>
                                <th class="po-hide-mobile">Ref</th>
                                <th class="po-r">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($activePaymentRows as $p)
                                @php
                                    $type = (string) ($p->type ?? '');
                                    $typeLabel = match ($type) {
                                        'dp' => 'DP',
                                        'dp_apply' => 'Offset DP ke AP',
                                        'loan_apply' => 'Alokasi Pinjaman Supplier',
                                        'return_apply' => 'Retur',
                                        default => 'Pelunasan',
                                    };
                                    $mirror = $p->standaloneJournal;
                                    $mirrorSupported = in_array($type, config('accounting.standalone_dual_posting_types', []), true);
                                    $mirrorPosted = $mirror && $mirror->posted_at && ! $mirror->voided_at;
                                @endphp
                                <tr>
                                    <td class="po-code-cell" style="font-weight:normal;color:#64748b;">{{ $p->date ? id_date($p->date) : '-' }}</td>
                                    <td><span class="po-pill" style="font-size:.7rem;padding:0 .4rem;">{{ $typeLabel }}</span></td>
                                    <td>
                                        <div style="font-weight:700;">{{ $p->paymentMethod?->code ?? '-' }}</div>
                                        <div class="po-muted" style="font-size:.7rem;">{{ $p->paymentMethod?->name ?? '-' }}</div>
                                        <div class="accounting-sync-group" aria-label="Status posting akuntansi">
                                            @if ($mirrorPosted)
                                                <span class="accounting-sync is-posted"><i class="bi bi-check2-circle" aria-hidden="true"></i> Baru: Posted</span>
                                                <a class="accounting-sync-link" href="{{ route('standalone-accounting.journals.show', $mirror->id) }}">Lihat jurnal</a>
                                            @elseif (config('accounting.standalone_dual_posting_enabled', false) && $mirrorSupported)
                                                <span class="accounting-sync is-pending"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> Baru: Tertunda</span>
                                            @else
                                                <span class="accounting-sync is-neutral"><i class="bi bi-dash-circle" aria-hidden="true"></i> Baru: Nonaktif</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="po-r" style="font-weight:700;">
                                        @if(in_array($type, ['dp_apply','loan_apply','return_apply']))
                                            <span style="color:#d97706;">{{ rupiah($p->amount) }}</span>
                                        @else
                                            <span style="color:#15803d;">{{ rupiah($p->amount) }}</span>
                                        @endif
                                    </td>
                                    <td class="po-hide-mobile"><span class="po-code-cell" style="font-weight:normal;">{{ $p->reference_number ?? '-' }}</span></td>
                                    <td class="po-r">
                                        <form action="{{ route('purchasing.purchase_orders.payments.void', [$order->id, $p->id]) }}" method="POST" onsubmit="return confirm('Hapus/Void pembayaran ini?');">
                                            @csrf
                                            <button type="submit" class="po-btn" style="color:#b91c1c;min-height:28px;padding:.1rem .5rem;"><i class="bi bi-trash"></i> Void</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
    
</div>

<script>
(function(){
    var tabs = document.querySelectorAll('.po-tab');
    var panes = document.querySelectorAll('.po-tabpane');
    function activate(name){
        tabs.forEach(function(t){ t.classList.toggle('active', t.dataset.tab === name); });
        panes.forEach(function(p){ p.classList.toggle('active', p.id === 'po-tab-' + name); });
        try { history.replaceState(null, '', '#' + name); } catch(e){}
    }
    tabs.forEach(function(t){
        t.addEventListener('click', function(){ activate(t.dataset.tab); });
    });
    var hash = (location.hash || '').replace('#','');
    if (['item','grn','payments'].indexOf(hash) !== -1) activate(hash);
})();
</script>


    @if ($canManagePayments)
    @push('modals')
    {{-- =========================================================
    MODAL: ADD PAYMENT / DP
========================================================= --}}
    <div class="modal fade" id="modalAddPayment" tabindex="-1" aria-labelledby="paymentModalTitle" aria-describedby="paymentModalSubtitle paymentAccountingHintText" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content payment-modal-content">
                <div class="modal-header payment-modal-header">
                    <span class="payment-modal-icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                    <div>
                        <div class="payment-modal-kicker">Pembayaran supplier</div>
                        <h2 class="modal-title" id="paymentModalTitle">{{ $paymentModalTitle }}</h2>
                        <p class="payment-modal-subtitle mb-0" id="paymentModalSubtitle">
                            {{ $canPaySettlement && $canPayDp
                                ? 'Pilih pelunasan hutang atau catat uang muka baru.'
                                : ($canPaySettlement ? 'Catat pembayaran untuk mengurangi hutang supplier.' : 'Catat uang muka sebelum barang diterima.') }}
                        </p>
                        <div class="payment-modal-summary" aria-label="Ringkasan saldo pembayaran">
                            @if ($hasAp)
                                <div class="payment-modal-stat"><span class="payment-modal-stat-label">GRN posted</span><strong class="payment-modal-stat-value mono">{{ rupiah($grnPostedTotal) }}</strong></div>
                                @if ($returnPostedTotal > 0.0001)
                                    <div class="payment-modal-stat"><span class="payment-modal-stat-label">Retur</span><strong class="payment-modal-stat-value mono">{{ rupiah($returnPostedTotal) }}</strong></div>
                                @endif
                                <div class="payment-modal-stat"><span class="payment-modal-stat-label">Sisa hutang</span><strong class="payment-modal-stat-value mono">{{ $formatPaymentMoney($apOutstanding) }}</strong></div>
                            @else
                                <div class="payment-modal-stat"><span class="payment-modal-stat-label">Total PO</span><strong class="payment-modal-stat-value mono">{{ rupiah($poGrandTotal) }}</strong></div>
                                <div class="payment-modal-stat"><span class="payment-modal-stat-label">DP tercatat</span><strong class="payment-modal-stat-value mono">{{ rupiah($advanceTotal) }}</strong></div>
                                <div class="payment-modal-stat"><span class="payment-modal-stat-label">Sisa nilai PO</span><strong class="payment-modal-stat-value mono">{{ rupiah($dpRemaining) }}</strong></div>
                            @endif
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup modal pembayaran"></button>
                </div>

                <form method="POST" action="{{ route('purchasing.purchase_orders.payments.store', $order->id) }}"
                    id="paymentForm">
                    @csrf

                                <div class="modal-body payment-modal-body">
                                    <div class="payment-modal-note" id="paymentAccountingHint" role="status" aria-live="polite">
                                        <span class="payment-modal-note-icon" aria-hidden="true"><i class="bi bi-journal-text"></i></span>
                                        <div>
                                            <span class="payment-modal-note-label">Posting akuntansi</span>
                                            <span class="payment-modal-note-copy" id="paymentAccountingHintText">{{ $canPaySettlement
                                            ? 'Akuntansi lama + baru: Dr Hutang Dagang / Cr Kas atau Bank. Nominal dibatasi oleh sisa hutang dari GRN POSTED.'
                                            : 'Akuntansi lama + baru: Dr Uang Muka Pembelian / Cr Kas atau Bank. Setelah GRN POSTED, gunakan Offset DP untuk mengurangi hutang.' }}</span>
                                        </div>
                                    </div>
                                    <div class="payment-modal-form">
                            @if ($advanceTotal > 0.0001 && $dpRemaining <= \App\Models\PurchaseOrder::paymentRoundingTolerance())
                                <div class="payment-modal-alert payment-modal-alert-warning">
                                        DP sudah menutup total PO. Tambahan nominal tetap boleh dicatat, tetapi selisihnya akan menjadi piutang supplier.
                                </div>
                            @elseif (!$hasAp)
                                <div class="payment-modal-alert payment-modal-alert-info">
                                        @if ($advanceTotal > 0.0001)
                                            DP sudah tercatat {{ rupiah($advanceTotal) }} (termasuk alokasi pinjaman). Tambahan nominal akan menambah uang muka; pelunasan baru tersedia setelah GRN diposting.
                                        @else
                                            Belum ada GRN POSTED. Nominal ini akan dicatat sebagai DP; pelunasan baru tersedia setelah barang diterima dan GRN diposting.
                                        @endif
                                </div>
                            @endif

                            <div class="row g-2">
                            <div class="col-6 col-md-4 payment-modal-field">
                                <label class="form-label" for="paymentDateModal">Tanggal</label>
                                <input type="text" name="date" id="paymentDateModal" class="form-control form-control-sm gf-date-input"
                                    value="{{ old('date', now()->toDateString()) }}" data-gf-date
                                    autocomplete="off" required>
                            </div>

                            <div class="col-6 col-md-8 payment-modal-field">
                                <label class="form-label" for="typeSelectModal">Tujuan pembayaran</label>
                                @php
                                    $defaultPaymentType = old('type', $canPaySettlement ? 'payment' : 'dp');
                                    if (! in_array($defaultPaymentType, ['payment', 'dp'], true)
                                        || ($defaultPaymentType === 'payment' && ! $canPaySettlement)) {
                                        $defaultPaymentType = $canPaySettlement ? 'payment' : 'dp';
                                    }
                                @endphp
                                <select name="type" class="form-select form-select-sm" required id="typeSelectModal">
                                    @if ($canPaySettlement)
                                        <option value="payment" @selected($defaultPaymentType === 'payment')>Pelunasan</option>
                                    @endif
                                    <option value="dp" @selected($defaultPaymentType === 'dp')>DP (Uang Muka)</option>
                                </select>
                                <div class="form-text small" id="paymentTypeHint">
                                    DP dicatat sebagai uang muka dan bisa di-offset setelah GRN POSTED.
                                </div>
                            </div>

                            <div class="col-12 col-md-6 payment-modal-field">
                                <label class="form-label" for="pmSelectModal">Metode <span class="text-danger">*</span></label>
                                <select name="payment_method_id" class="form-select form-select-sm" required id="pmSelectModal">
                                    @foreach ($modalPaymentMethods as $pmOpt)
                                        <option value="{{ $pmOpt->id }}"
                                            data-mode="{{ strtolower($pmOpt->mode ?? '') }}"
                                            data-code="{{ strtoupper($pmOpt->code ?? '') }}"
                                            @selected((string) $selectedModalPaymentMethodId === (string) $pmOpt->id)>
                                            {{ $pmOpt->name }}
                                            @if (!empty($pmOpt->mode))— {{ strtoupper($pmOpt->mode) }}@endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="text-danger small mt-1 d-none" id="creditPayErrModal">
                                    TEMPO/CREDIT hanya untuk DP, bukan pelunasan.
                                </div>
                            </div>

                            <div class="col-12 col-md-6 payment-modal-field">
                                <label class="form-label" id="cashAccountLabel">Kas/Bank <span class="text-danger">*</span></label>

                                <div id="cashWrap" class="d-none">
                                    <select name="cash_account_id" class="form-select form-select-sm" id="cashSelectCash" aria-labelledby="cashAccountLabel">
                                        <option value="">— Pilih Kas —</option>
                                        @if ($cash1101)
                                            <option value="{{ $cash1101->id }}" data-code="1101"
                                                @selected(old('cash_account_id') == $cash1101->id)>
                                                1101 — {{ $cash1101->name }} (Saldo: {{ rupiah($cash1101->current_balance ?? 0) }})
                                            </option>
                                        @endif
                                    </select>
                                    <div class="text-danger small mt-1 d-none" id="cashErrModal">Wajib pilih akun 1101 (Kas).</div>
                                </div>

                                <div id="bankWrap" class="d-none">
                                    <select name="cash_account_id" class="form-select form-select-sm" id="cashSelectBank" aria-labelledby="cashAccountLabel">
                                        <option value="">— Pilih Bank/E-Wallet —</option>
                                        @foreach ($transferBanks as $acc)
                                            <option value="{{ $acc->id }}" data-code="{{ $acc->code }}"
                                                @selected(old('cash_account_id') == $acc->id)>
                                                {{ $acc->code }} — {{ $acc->name }} (Saldo: {{ rupiah($acc->current_balance ?? 0) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="text-danger small mt-1 d-none" id="bankErrModal">Wajib pilih akun 1111–1114.</div>
                                </div>

                                <div id="creditWrap" class="d-none">
                                    <input type="text" class="form-control form-control-sm"
                                        value="Tidak perlu kas/bank (TEMPO/CREDIT)" disabled>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 payment-modal-field">
                                <label class="form-label" for="amountInput">Nominal <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" name="amount" class="form-control mono" id="amountInput"
                                        placeholder="0" value="{{ old('amount') }}" required>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnFillRemaining">Isi sisa</button>
                                </div>
                                <div class="form-text small" id="paymentAmountHint">Nominal akan dicek kembali terhadap saldo hutang/uang muka.</div>
                            </div>

                            <div class="col-6 col-md-3 payment-modal-field">
                                <label class="form-label" for="paymentRefModal">No. ref</label>
                                <input type="text" name="ref_no" id="paymentRefModal" class="form-control form-control-sm mono" placeholder="Opsional"
                                    value="{{ old('ref_no') }}">
                            </div>

                            <div class="col-6 col-md-9 payment-modal-field">
                                <label class="form-label" for="paymentNotesModal">Catatan</label>
                                <input type="text" name="notes" id="paymentNotesModal" class="form-control form-control-sm" placeholder="Opsional"
                                    value="{{ old('notes') }}">
                            </div>

                            @php $unpaidInvList = $unpaidInvoices ?? collect(); @endphp
                            @if ($unpaidInvList->isNotEmpty())
                            <div class="col-12 payment-modal-field" id="supplierInvoiceWrapModal">
                                <label class="form-label" for="supplierInvoiceSelectModal">Faktur supplier <span class="text-muted fw-normal">(opsional)</span></label>
                                <select name="supplier_invoice_id" id="supplierInvoiceSelectModal" class="form-select form-select-sm">
                                    <option value="">— Tidak dikaitkan ke faktur —</option>
                                    @foreach ($unpaidInvList as $inv)
                                        <option value="{{ $inv->id }}"
                                            {{ old('supplier_invoice_id') == $inv->id ? 'selected' : '' }}>
                                            {{ $inv->invoice_no }}
                                            @if ($inv->supplier_invoice_ref) [{{ $inv->supplier_invoice_ref }}] @endif
                                            — {{ rupiah($inv->outstanding()) }} outstanding
                                            ({{ strtoupper($inv->status) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            </div>

                        @if ($errors->any())
                            <div class="payment-modal-alert alert alert-danger mt-0 mb-0 py-2 small">
                                <div class="fw-semibold mb-1">Gagal:</div>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>

                    <div class="modal-footer payment-modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-sm btn-primary" id="btnSavePayment"><i class="bi bi-check2" aria-hidden="true"></i> <span id="btnSavePaymentText">Simpan</span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- =========================================================
    MODAL: APPLY DP (OFFSET)
========================================================= --}}
    <div class="modal fade" id="modalApplyDp" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('purchasing.purchase_orders.payments.apply_dp', $order->id) }}"
                class="modal-content gf-modal" id="applyDpForm">
                @csrf

                <div class="modal-header">
                    <div>
                        <h6 class="modal-title fw-semibold mb-0">Offset DP</h6>
                        <div class="d-flex gap-1 flex-wrap mt-2">
                            <span class="modal-kpi">Uang muka <strong class="mono">{{ rupiah($dpAvailable) }}</strong></span>
                            <span class="modal-kpi">AP sudah diselesaikan <strong class="mono">{{ rupiah($apSettledTotal) }}</strong></span>
                            @if ($returnPostedTotal > 0.0001)
                                <span class="modal-kpi">Retur <strong class="mono">{{ rupiah($returnPostedTotal) }}</strong></span>
                            @endif
                            <span class="modal-kpi">Sisa <strong class="mono">{{ $formatPaymentMoney($apOutstanding) }}</strong></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info py-2 px-3 mb-3 small">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        Offset DP hanya memindahkan saldo dari <b>Uang Muka Pembelian</b> ke
                        <b>Hutang Dagang</b>; tidak ada uang baru yang keluar.
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tanggal</label>
                            <input type="text" name="date" class="form-control form-control-sm gf-date-input"
                                value="{{ old('date', $order->date?->toDateString() ?? now()->toDateString()) }}" data-gf-date
                                autocomplete="off" required>
                            @error('date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6">
                            <label class="form-label small fw-semibold">Nominal Offset</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="amount" class="form-control mono" id="applyDpAmount"
                                    placeholder="0" value="{{ old('amount') }}" required>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    id="btnFillMaxApplyDp">Max</button>
                            </div>
                            @error('amount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
                            <input type="text" name="notes" class="form-control form-control-sm" maxlength="255"
                                value="{{ old('notes') }}">
                            @error('notes')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary"
                        @if (!$canApplyDp) disabled @endif>Proses Offset</button>
                </div>
            </form>
        </div>
    </div>

    {{-- =========================================================
    MODAL: ALOKASI PINJAMAN SUPPLIER
========================================================= --}}
    @if ($canApplySupplierLoan ?? false)
    <div class="modal fade" id="modalApplySupplierLoan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('purchasing.purchase_orders.payments.apply_supplier_loan', $order->id) }}"
                class="modal-content gf-modal" id="applySupplierLoanForm">
                @csrf
                <div class="modal-header">
                    <div>
                        <h6 class="modal-title fw-semibold mb-0">Alokasi Pinjaman Supplier</h6>
                        <div class="d-flex gap-1 flex-wrap mt-2">
                            <span class="modal-kpi">Sisa PO <strong class="mono">{{ rupiah($poAllocationRemaining) }}</strong></span>
                            <span class="modal-kpi">Maks. alokasi <strong class="mono">{{ rupiah($maxSupplierLoanApply) }}</strong></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 px-3 mb-3 small">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        Alokasi ini tidak mengeluarkan uang baru. Jurnalnya memindahkan
                        <b>Piutang Pinjaman Supplier</b> menjadi <b>Uang Muka Pembelian</b>.
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Tanggal</label>
                            <input type="text" name="date" class="form-control form-control-sm gf-date-input"
                                value="{{ old('date', $order->date?->toDateString() ?? now()->toDateString()) }}" data-gf-date
                                autocomplete="off" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Nominal Alokasi</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="amount" class="form-control mono" id="applySupplierLoanAmount"
                                    placeholder="0" value="{{ old('amount') }}" required>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnFillMaxSupplierLoan">Max</button>
                            </div>
                            <div class="form-text small" id="supplierLoanMaxHint">Pilih pinjaman terlebih dahulu.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Saldo Pinjaman</label>
                            <select name="supplier_loan_id" id="supplierLoanSelect" class="form-select form-select-sm" required>
                                <option value="">— Pilih pinjaman supplier —</option>
                                @foreach ($supplierLoanOptions as $loanOption)
                                    <option value="{{ $loanOption->id }}" data-available="{{ (float) $loanOption->allocation_available }}"
                                        @selected(old('supplier_loan_id') == $loanOption->id)>
                                        {{ optional($loanOption->date)->format('d/m/Y') }}
                                        @if($loanOption->reference) · {{ $loanOption->reference }} @endif
                                        — tersedia {{ rupiah($loanOption->allocation_available) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
                            <input type="text" name="notes" class="form-control form-control-sm" maxlength="255"
                                value="{{ old('notes') }}" placeholder="Contoh: Alokasi sebagian pinjaman ke PO ini">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-info text-white">Alokasikan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- =========================================================
    MODAL: VOID PAYMENT + REASON
========================================================= --}}
    <div class="modal fade" id="modalVoidPayment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="" class="modal-content gf-modal" id="voidPaymentForm">
                @csrf

                <div class="modal-header">
                    <div>
                        <h6 class="modal-title fw-semibold mb-0">Void Pembayaran</h6>
                        <div class="text-muted small mono mt-1" id="voidInfo">—</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <label class="form-label small fw-semibold">Alasan Void</label>
                    <input type="text" name="reason" class="form-control form-control-sm" maxlength="255"
                        placeholder="contoh: salah nominal / duplikat / salah metode" value="{{ old('reason') }}"
                        required>
                    @error('reason')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger">Void</button>
                </div>
            </form>
        </div>
    </div>

    @endpush


    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // =========================================================
                // ADD PAYMENT (existing logic kamu)
                // =========================================================
                const pm = document.getElementById('pmSelectModal');
                const btnSave = document.getElementById('btnSavePayment');
                const cashWrap = document.getElementById('cashWrap');
                const bankWrap = document.getElementById('bankWrap');
                const creditWrap = document.getElementById('creditWrap');

                const cashSelectCash = document.getElementById('cashSelectCash');
                const cashSelectBank = document.getElementById('cashSelectBank');

                const bankErr = document.getElementById('bankErrModal');
                const cashErr = document.getElementById('cashErrModal');
                const creditPayErr = document.getElementById('creditPayErrModal');

                const amountInput = document.getElementById('amountInput');
                const btnFill = document.getElementById('btnFillRemaining');
                const paymentForm = document.getElementById('paymentForm');
                const paymentModal = document.getElementById('modalAddPayment');
                const paymentModalTitle = document.getElementById('paymentModalTitle');
                const paymentModalSubtitle = document.getElementById('paymentModalSubtitle');
                const paymentAccountingHintText = document.getElementById('paymentAccountingHintText');
                const paymentAmountHint = document.getElementById('paymentAmountHint');
                const btnSavePaymentText = document.getElementById('btnSavePaymentText');

                const remaining = {{ (float) $apOutstanding }};
                const dpRemaining = {{ (float) $dpRemaining }};
                const paymentTolerance = {{ (float) \App\Models\PurchaseOrder::paymentRoundingTolerance() }};
                const typeSelect = document.getElementById('typeSelectModal');
                const paymentTypeHint = document.getElementById('paymentTypeHint');
                const supplierInvoiceWrap = document.getElementById('supplierInvoiceWrapModal');
                const supplierInvoiceSelect = document.getElementById('supplierInvoiceSelectModal');

                function fmtMoneyInput(n) {
                    const value = Number(n || 0);
                    const hasFraction = Math.abs(value - Math.round(value)) > 0.0001;

                    return new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: hasFraction ? 2 : 0,
                        maximumFractionDigits: 2
                    }).format(value);
                }

                function detectMode() {
                    const opt = pm?.selectedOptions?.[0];
                    const mode = (opt?.dataset?.mode || '').toLowerCase();
                    const code = (opt?.dataset?.code || '').toUpperCase();

                    if (mode) return mode;
                    if (code.includes('CASH')) return 'cash';
                    if (code.includes('TRF') || code.includes('TRANSFER') || code.includes('BANK')) return 'transfer';
                    if (code.includes('TEMPO') || code.includes('CREDIT')) return 'credit';
                    return '';
                }

                function showOnly(which) {
                    cashWrap?.classList.toggle('d-none', which !== 'cash');
                    bankWrap?.classList.toggle('d-none', which !== 'bank');
                    creditWrap?.classList.toggle('d-none', which !== 'credit');
                    if (cashSelectCash) cashSelectCash.disabled = which !== 'cash';
                    if (cashSelectBank) cashSelectBank.disabled = which !== 'bank';
                }

                function setBankError(show) {
                    if (bankErr) bankErr.classList.toggle('d-none', !show);
                }

                function setCashError(show) {
                    if (cashErr) cashErr.classList.toggle('d-none', !show);
                }

                function setCreditPayError(show) {
                    if (creditPayErr) creditPayErr.classList.toggle('d-none', !show);
                }

                function syncPaymentMethodOptions() {
                    const isPayment = (typeSelect?.value || 'payment') === 'payment';
                    const options = pm ? Array.from(pm.options) : [];

                    options.forEach(option => {
                        option.disabled = isPayment && option.dataset.mode === 'credit';
                    });

                    if (isPayment && detectMode() === 'credit') {
                        const fallback = options.find(option => !option.disabled && option.value);
                        if (fallback) pm.value = fallback.value;
                    }
                }

                function autoPickCash() {
                    if (!cashSelectCash) return;
                    if (!cashSelectCash.value && cashSelectCash.options.length > 1) cashSelectCash.selectedIndex = 1;
                }

                function validateCashBank() {
                    const mode = detectMode();
                    if (mode === 'credit') {
                        setBankError(false);
                        setCashError(false);
                        return true;
                    }
                    if (mode === 'transfer') {
                        const ok = !!(cashSelectBank && cashSelectBank.value);
                        setBankError(!ok);
                        setCashError(false);
                        return ok;
                    }
                    const ok = !!(cashSelectCash && cashSelectCash.value);
                    setCashError(!ok);
                    setBankError(false);
                    return ok;
                }

                function validateTypeVsMode() {
                    const mode = detectMode();
                    const type = (typeSelect?.value || 'payment');
                    if (type === 'payment' && mode === 'credit') {
                        setCreditPayError(true);
                        if (btnSave) btnSave.disabled = true;
                        return false;
                    }
                    setCreditPayError(false);
                    return true;
                }

                function applyAllValidations() {
                    const okCashBank = validateCashBank();
                    const okTypeMode = validateTypeVsMode();
                    if (btnSave) btnSave.disabled = !(okCashBank && okTypeMode);
                }

                function syncAccountingHint() {
                    const isPayment = (typeSelect?.value || 'payment') === 'payment';
                    const mode = detectMode();
                    const methodLabel = mode === 'transfer' ? 'Bank' : (mode === 'cash' ? 'Kas' : 'Kas/Bank');

                    if (paymentModalTitle) {
                        paymentModalTitle.textContent = isPayment ? 'Bayar Hutang Supplier' : 'Tambah DP Supplier';
                    }
                    if (paymentModalSubtitle) {
                        paymentModalSubtitle.textContent = isPayment
                            ? 'Catat pembayaran untuk mengurangi hutang supplier.'
                            : (mode === 'credit'
                                ? 'Catat uang muka supplier tanpa kas atau bank keluar.'
                                : 'Catat uang muka sebelum barang diterima.');
                    }
                    if (paymentAccountingHintText) {
                        paymentAccountingHintText.textContent = isPayment
                            ? `Akuntansi lama + baru: Dr Hutang Dagang / Cr ${methodLabel}. Nominal dibatasi oleh sisa hutang dari GRN POSTED.`
                            : (mode === 'credit'
                                ? 'DP tempo: dicatat sebagai uang muka supplier tanpa kas/bank keluar pada langkah ini.'
                                : `Akuntansi lama + baru: Dr Uang Muka Pembelian / Cr ${methodLabel}. Setelah GRN POSTED, gunakan Offset DP untuk mengurangi hutang.`);
                    }
                }

                function syncModeUI() {
                    const mode = detectMode();

                    if (mode === 'credit') {
                        showOnly('credit');
                        if (cashSelectCash) cashSelectCash.value = '';
                        if (cashSelectBank) cashSelectBank.value = '';
                        setBankError(false);
                        setCashError(false);
                        syncAccountingHint();
                        applyAllValidations();
                        return;
                    }

                    if (mode === 'transfer') {
                        showOnly('bank');
                        syncAccountingHint();
                        applyAllValidations();
                        return;
                    }

                    showOnly('cash');
                    autoPickCash();
                    syncAccountingHint();
                    applyAllValidations();
                }

                function syncTypeRules() {
                    const isPayment = (typeSelect?.value || 'payment') === 'payment';
                    syncPaymentMethodOptions();
                    supplierInvoiceWrap?.classList.toggle('d-none', !isPayment);
                    if (supplierInvoiceSelect) {
                        supplierInvoiceSelect.disabled = !isPayment;
                        if (!isPayment) supplierInvoiceSelect.value = '';
                    }
                    if (paymentTypeHint) {
                        paymentTypeHint.textContent = isPayment
                            ? 'Pelunasan memakai hutang dari GRN POSTED dan tidak boleh melebihi sisa tagihan.'
                            : 'DP dicatat sebagai uang muka dan bisa di-offset setelah GRN POSTED.';
                    }
                    if (paymentAmountHint) {
                        const maxAmount = isPayment ? remaining : dpRemaining;
                        paymentAmountHint.textContent = maxAmount > paymentTolerance
                            ? `Maksimal saat ini: Rp ${fmtMoneyInput(maxAmount)}.`
                            : (isPayment ? 'Tidak ada sisa hutang yang dapat dibayar.' : 'Sisa nilai PO sudah tidak tersedia untuk DP.');
                    }
                    syncAccountingHint();
                    if (btnFill) {
                        const maxAmount = isPayment ? remaining : dpRemaining;
                        const canFill = maxAmount > paymentTolerance;
                        btnFill.textContent = isPayment
                            ? (canFill ? 'Isi sisa tagihan' : 'Sisa tagihan sudah 0')
                            : (canFill ? 'Isi sisa PO' : 'Sisa PO sudah 0');
                        btnFill.disabled = !canFill;
                    }
                    applyAllValidations();
                }

                btnFill?.addEventListener('click', function() {
                    if (!amountInput) return;
                    const maxAmount = (typeSelect?.value || 'payment') === 'dp' ? dpRemaining : remaining;
                    amountInput.value = fmtMoneyInput(maxAmount);
                    amountInput.focus();
                    amountInput.select?.();
                });

                amountInput?.addEventListener('focusout', function() {
                    const raw = (amountInput.value || '').toString().trim();
                    if (raw === '') return;
                    const n = Number(raw.replace(/\./g, '').replace(/,/g, '.'));
                    if (!isNaN(n)) amountInput.value = fmtMoneyInput(n);
                });

                typeSelect?.addEventListener('change', syncTypeRules);
                pm?.addEventListener('change', syncModeUI);
                cashSelectBank?.addEventListener('change', applyAllValidations);
                cashSelectCash?.addEventListener('change', applyAllValidations);

                paymentModal?.addEventListener('show.bs.modal', function(event) {
                    const requestedType = event.relatedTarget?.dataset?.paymentType;
                    if (requestedType && typeSelect?.querySelector(`option[value="${requestedType}"]`)) {
                        typeSelect.value = requestedType;
                    }
                    if (paymentForm) delete paymentForm.dataset.confirmed;
                    syncTypeRules();
                    syncModeUI();
                });

                paymentForm?.addEventListener('submit', function(event) {
                    if (paymentForm.dataset.confirmed === '1') return;

                    const type = typeSelect?.value || 'payment';
                    const rawAmount = (amountInput?.value || '').toString().trim();
                    const amount = Number(rawAmount.replace(/\./g, '').replace(/,/g, '.'));
                    const maxAmount = type === 'payment' ? remaining : dpRemaining;

                    if (!Number.isFinite(amount) || amount <= 0 || amount > maxAmount + paymentTolerance) {
                        event.preventDefault();
                        window.alert(type === 'payment'
                            ? 'Nominal melebihi sisa hutang atau belum diisi.'
                            : 'Nominal melebihi sisa nilai PO atau belum diisi.');
                        return;
                    }

                    const mode = detectMode();
                    const creditLine = type === 'payment'
                        ? 'Jurnal: Dr Hutang Dagang / Cr Kas atau Bank.'
                        : (mode === 'credit'
                            ? 'DP tempo dicatat sebagai uang muka supplier.'
                            : 'Jurnal: Dr Uang Muka Pembelian / Cr Kas atau Bank.');
                    const confirmed = window.confirm(
                        `${type === 'payment' ? 'Catat pelunasan hutang' : 'Catat DP'} sebesar Rp ${fmtMoneyInput(amount)}?\n\n` +
                        `${creditLine}\n` +
                        `Tanggal dan referensi akan masuk ke riwayat pembayaran.`
                    );

                    if (!confirmed) {
                        event.preventDefault();
                        return;
                    }

                    paymentForm.dataset.confirmed = '1';
                    if (btnSave) {
                        btnSave.disabled = true;
                        if (btnSavePaymentText) {
                            btnSavePaymentText.textContent = 'Menyimpan...';
                        } else {
                            btnSave.textContent = 'Menyimpan...';
                        }
                    }
                });

                // init
                syncTypeRules();
                syncModeUI();

                // =========================================================
                // APPLY DP modal helpers
                // =========================================================
                const applyDpAmount = document.getElementById('applyDpAmount');
                const btnFillMaxApplyDp = document.getElementById('btnFillMaxApplyDp');
                const applyDpForm = document.getElementById('applyDpForm');
                const maxApplyDp = {{ (float) $maxApplyDp }};

                btnFillMaxApplyDp?.addEventListener('click', function() {
                    if (!applyDpAmount) return;
                    applyDpAmount.value = fmtMoneyInput(maxApplyDp);
                    applyDpAmount.focus();
                    applyDpAmount.select?.();
                });

                applyDpAmount?.addEventListener('focusout', function() {
                    const raw = (applyDpAmount.value || '').toString().trim();
                    if (raw === '') return;
                    const n = Number(raw.replace(/\./g, '').replace(/,/g, '.'));
                    if (!isNaN(n)) applyDpAmount.value = fmtMoneyInput(n);
                });

                applyDpForm?.addEventListener('submit', function(event) {
                    if (applyDpForm.dataset.confirmed === '1') return;

                    const raw = (applyDpAmount?.value || '').toString().trim();
                    const amount = Number(raw.replace(/\./g, '').replace(/,/g, '.'));
                    if (!Number.isFinite(amount) || amount <= 0) return;

                    if (amount > maxApplyDp + paymentTolerance) {
                        event.preventDefault();
                        window.alert(`Nominal melebihi maksimum offset ${fmtMoneyInput(maxApplyDp)}.`);
                        return;
                    }

                    const confirmed = window.confirm(
                        `Offset DP ${fmtMoneyInput(amount)} ke hutang AP?\n\n` +
                        `Jurnal: Dr Hutang Dagang / Cr Uang Muka Pembelian.\n` +
                        `Tidak ada uang baru yang keluar.`
                    );
                    if (!confirmed) {
                        event.preventDefault();
                        return;
                    }

                    applyDpForm.dataset.confirmed = '1';
                });

                // =========================================================
                // APPLY SUPPLIER LOAN modal helpers
                // =========================================================
                const supplierLoanSelect = document.getElementById('supplierLoanSelect');
                const applySupplierLoanAmount = document.getElementById('applySupplierLoanAmount');
                const btnFillMaxSupplierLoan = document.getElementById('btnFillMaxSupplierLoan');
                const supplierLoanMaxHint = document.getElementById('supplierLoanMaxHint');
                const applySupplierLoanForm = document.getElementById('applySupplierLoanForm');
                const poAllocationRemaining = {{ (float) ($poAllocationRemaining ?? 0) }};

                function selectedSupplierLoanMax() {
                    const opt = supplierLoanSelect?.selectedOptions?.[0];
                    const available = Number(opt?.dataset?.available || 0);
                    return Math.min(available, poAllocationRemaining);
                }

                function syncSupplierLoanMax() {
                    const max = selectedSupplierLoanMax();
                    if (supplierLoanMaxHint) {
                        supplierLoanMaxHint.textContent = max > paymentTolerance
                            ? 'Maksimal alokasi: Rp ' + fmtMoneyInput(max)
                            : 'Saldo pinjaman atau sisa PO sudah 0.';
                    }
                    if (btnFillMaxSupplierLoan) btnFillMaxSupplierLoan.disabled = max <= paymentTolerance;
                }

                supplierLoanSelect?.addEventListener('change', syncSupplierLoanMax);
                syncSupplierLoanMax();

                btnFillMaxSupplierLoan?.addEventListener('click', function() {
                    if (!applySupplierLoanAmount) return;
                    applySupplierLoanAmount.value = fmtMoneyInput(selectedSupplierLoanMax());
                    applySupplierLoanAmount.focus();
                    applySupplierLoanAmount.select?.();
                });

                applySupplierLoanAmount?.addEventListener('focusout', function() {
                    const raw = (applySupplierLoanAmount.value || '').toString().trim();
                    if (raw === '') return;
                    const n = Number(raw.replace(/\./g, '').replace(/,/g, '.'));
                    if (!isNaN(n)) applySupplierLoanAmount.value = fmtMoneyInput(n);
                });

                applySupplierLoanForm?.addEventListener('submit', function(event) {
                    const raw = (applySupplierLoanAmount?.value || '').toString().trim();
                    const amount = Number(raw.replace(/\./g, '').replace(/,/g, '.'));
                    const max = selectedSupplierLoanMax();
                    if (!Number.isFinite(amount) || amount <= 0 || amount > max + paymentTolerance) {
                        event.preventDefault();
                        window.alert('Nominal melebihi maksimum alokasi Rp ' + fmtMoneyInput(max) + '.');
                        return;
                    }
                    if (!window.confirm(
                        'Alokasikan Rp ' + fmtMoneyInput(amount) + ' dari saldo pinjaman supplier ke PO ini?\n\n' +
                        'Tidak ada uang baru yang keluar.'
                    )) {
                        event.preventDefault();
                    }
                });

                // =========================================================
                // VOID modal wiring
                // =========================================================
                const modalVoidEl = document.getElementById('modalVoidPayment');
                const voidForm = document.getElementById('voidPaymentForm');
                const voidInfo = document.getElementById('voidInfo');
                const actionTpl = @json($voidActionTemplate);

                modalVoidEl?.addEventListener('show.bs.modal', function(event) {
                    const btn = event.relatedTarget;
                    if (!btn) return;

                    const pid = btn.getAttribute('data-payment-id');
                    const ptype = btn.getAttribute('data-payment-type') || '-';
                    const pamt = btn.getAttribute('data-payment-amount') || '-';
                    const pdate = btn.getAttribute('data-payment-date') || '-';

                    if (voidForm) voidForm.action = actionTpl.replace('___PAYMENT___', pid);
                    if (voidInfo) voidInfo.textContent = `${ptype} • ${pamt} • ${pdate}`;

                    const reasonInput = voidForm?.querySelector('input[name="reason"]');
                    if (reasonInput) reasonInput.value = '';
                });

                // =========================================================
                // Auto open modal if validation errors (best-effort)
                // =========================================================
                const hasErrors = {{ $errors->any() ? 'true' : 'false' }};

                if (hasErrors && window.bootstrap) {
                    // Heuristic:
                    // - if reason error exists -> open void modal and expand history
                    // - else if amount/date/notes and request came from apply dp -> open apply dp modal
                    // - else open add payment modal
                    const reasonErr = {!! json_encode($errors->has('reason')) !!};
                    const amountErr = {!! json_encode($errors->has('amount')) !!};
                    const dateErr = {!! json_encode($errors->has('date')) !!};

                    const hist = document.getElementById('paymentHistoryCollapse');
                    if (hist) new bootstrap.Collapse(hist, {
                        toggle: true
                    });

                    if (reasonErr) {
                        const el = document.getElementById('modalVoidPayment');
                        if (el) new bootstrap.Modal(el).show();
                        return;
                    }

                    // apply dp biasanya error amount/date juga, tapi add payment juga.
                    // trigger jika user terakhir tekan tombol offset dp (kita pakai session flash flag kalau ada)
                    const fromApplyDp = {!! json_encode((bool) session('from_apply_dp')) !!};

                    if (fromApplyDp) {
                        const el = document.getElementById('modalApplyDp');
                        if (el) new bootstrap.Modal(el).show();
                        return;
                    }

                    // default: add payment
                    const addModalEl = document.getElementById('modalAddPayment');
                    if (addModalEl) new bootstrap.Modal(addModalEl).show();
                }
            });
        </script>


    @endpush
    @endif
@endsection
