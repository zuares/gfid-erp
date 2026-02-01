{{-- resources/views/imports/marketplace/partials/_script.blade.php --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('filterForm');
  const kpiWrap = document.getElementById('kpiWrap');
  const tableWrap = document.getElementById('tableWrap');
  if (!form || !kpiWrap || !tableWrap) return;

  const DEBOUNCE_TABLE = 350;
  const DEBOUNCE_KPI   = 1200;
  const MIN_Q = 2;

  let tTable = null, tKpi = null;
  let abortTable = null, abortKpi = null;

  function qs(extra = {}) {
    const fd = new FormData(form);
    const sp = new URLSearchParams(fd);

    Object.entries(extra).forEach(([k,v]) => {
      if (v === null || typeof v === 'undefined') sp.delete(k);
      else sp.set(k, v);
    });

    return sp;
  }

  async function fetchHtml(partial, extra = {}) {
    const sp = qs({ partial, ...extra });
    const url = form.action + '?' + sp.toString();
    const ctrl = new AbortController();

    const res = await fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      signal: ctrl.signal
    });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const html = await res.text();
    return { html, ctrl };
  }

  async function loadTable(extra = {}) {
    try {
      if (abortTable) abortTable.abort();

      const q = (form.querySelector('[name="q"]')?.value || '').trim();
      if (q.length > 0 && q.length < MIN_Q) return;

      const { html, ctrl } = await fetchHtml('table', extra);
      abortTable = ctrl;

      tableWrap.innerHTML = html;
      bindPagination();

      history.replaceState(null, '', form.action + '?' + qs().toString());
    } catch(e) {}
  }

  async function loadKpi(extra = {}) {
    try {
      if (abortKpi) abortKpi.abort();
      const { html, ctrl } = await fetchHtml('kpi', extra);
      abortKpi = ctrl;
      kpiWrap.innerHTML = html;
    } catch(e) {}
  }

  function scheduleTable(){
    clearTimeout(tTable);
    tTable = setTimeout(() => loadTable({ page: null }), DEBOUNCE_TABLE);
  }
  function scheduleKpi(){
    clearTimeout(tKpi);
    tKpi = setTimeout(() => loadKpi({ page: null }), DEBOUNCE_KPI);
  }

  form.querySelectorAll('input,select').forEach(el => {
    el.addEventListener('input', () => { scheduleTable(); scheduleKpi(); });
    el.addEventListener('change', () => { scheduleTable(); scheduleKpi(); });
  });

  function bindPagination(){
    tableWrap.querySelectorAll('.pagination a').forEach(a => {
      a.addEventListener('click', async (ev) => {
        ev.preventDefault();
        const u = new URL(a.href);
        const page = u.searchParams.get('page') || '1';
        await loadTable({ page });
      });
    });
  }
  bindPagination();
});
</script>
