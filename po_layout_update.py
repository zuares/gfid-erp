import re

with open('resources/views/purchasing/purchase_orders/show.blade.php', 'r') as f:
    content = f.read()

css_block = """<style>
.po-wrap{max-width:1080px;margin-inline:auto;padding:.7rem .75rem 3rem}
.po-topbar{position:sticky;top:0;z-index:250;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;padding:.55rem .75rem;background:var(--card,#fff);border-bottom:1px solid rgba(148,163,184,.18)}
.po-code{font-weight:900;font-size:1.05rem;color:#111827;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
.po-supplier{font-size:.8rem;color:#64748b;margin-left:.25rem}
.po-spacer{flex:1}
.po-btn,.po-pill{display:inline-flex;align-items:center;justify-content:center;gap:.35rem;border-radius:7px;border:1px solid rgba(148,163,184,.3);background:transparent;color:#475569;text-decoration:none;font-size:.76rem;padding:.28rem .6rem;min-height:34px}
.po-btn{font-weight:800; cursor:pointer;}
.po-btn:hover{background:rgba(148,163,184,.09);color:#111827;text-decoration:none}
.po-primary{background:#334155!important;border-color:#334155!important;color:#fff!important}
.po-success{background:#15803d!important;border-color:#15803d!important;color:#fff!important}
.po-success:hover{background:#166534!important;border-color:#166534!important}
.po-info{background:#0ea5e9!important;border-color:#0ea5e9!important;color:#fff!important}
.po-info:hover{background:#0284c7!important}
.po-status{font-weight:850;color:#334155;background:rgba(148,163,184,.08)}
.po-status.approved{color:#166534;background:rgba(22,101,52,.08);border-color:rgba(22,101,52,.2)}
.po-status.closed{color:#0f172a;background:rgba(15,23,42,.08);border-color:rgba(15,23,42,.2)}
.po-status.cancelled{color:#991b1b;background:rgba(153,27,27,.08);border-color:rgba(153,27,27,.2)}
.po-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.55rem;margin-bottom:.65rem}
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
.po-tabs{display:flex;gap:.25rem;margin-bottom:.65rem;border-bottom:1px solid rgba(148,163,184,.18);flex-wrap:wrap}
.po-tab{appearance:none;display:inline-flex;align-items:center;gap:.4rem;border:none;background:transparent;color:#64748b;font-weight:800;font-size:.82rem;padding:.55rem .8rem;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px}
.po-tab:hover{color:#334155}
.po-tab.active{color:#111827;border-bottom-color:#334155}
.po-tab-count{display:inline-flex;align-items:center;justify-content:center;min-width:1.35rem;height:1.35rem;padding:0 .3rem;border-radius:999px;background:rgba(148,163,184,.16);color:#475569;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.7rem;font-weight:900}
.po-tab.active .po-tab-count{background:#334155;color:#fff}
.po-tabpane{display:none}
.po-tabpane.active{display:block}
.po-tabpane .po-card{margin-bottom:0}

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
  .po-table td.po-r{text-align:left;margin-top:.35rem}
  .po-name{display:none}
  .po-total{display:none!important}
}
.mono {
    font-variant-numeric: tabular-nums;
    font-family: ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono";
}
/* Utility */
.badge-posted { background:rgba(22,163,74,.12);color:#15803d;border-color:rgba(22,163,74,.6); border: 1px solid; border-radius: 999px; padding: 0.15rem 0.5rem; font-size: 0.72rem; }
.badge-draft { background:rgba(148,163,184,.12);color:#64748b;border-color:rgba(148,163,184,.5); border: 1px solid; border-radius: 999px; padding: 0.15rem 0.5rem; font-size: 0.72rem; }
</style>"""

content = re.sub(r'<style>[\s\S]*?</style>', css_block, content, count=1)

with open('resources/views/purchasing/purchase_orders/show.blade.php', 'w') as f:
    f.write(content)
