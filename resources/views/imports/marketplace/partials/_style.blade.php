{{-- resources/views/imports/marketplace/partials/_style.blade.php --}}
<style>
  .page{ max-width:1220px; margin:0 auto; padding: 1rem .9rem 4.8rem; }
  @media(min-width:768px){ .page{ padding: 1.1rem 1rem 4.8rem; } }

  .cardx{ border:1px solid rgba(148,163,184,.18); border-radius:14px; background: var(--card, #fff); }
  .chip{ display:inline-flex; align-items:center; gap:.35rem; padding:.18rem .55rem; border-radius:999px; font-size:.78rem; border:1px solid rgba(148,163,184,.22); background: rgba(148,163,184,.06); }
  .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace; }

  /* mobile table -> card rows */
  .show-sm{ display:none; }
  .hide-sm{ display:table-cell; }

  @media(max-width:820px){
    thead{ display:none; }
    .hide-sm{ display:none; }
    .show-sm{ display:block; }
    tbody td{ display:block; border-bottom:none; padding:.75rem .85rem; }
    tbody tr{ display:block; border-bottom:1px solid rgba(148,163,184,.14); }
    .mrow{ display:flex; justify-content:space-between; gap:.75rem; }
    .mright{ text-align:right; white-space:nowrap; }
  }
</style>
