{{-- resources/views/inventory/stocks/_items_scripts.blade.php --}}
<script>
    (() => {
        if (window.__stocksItemsBooted) return;
        window.__stocksItemsBooted = true;

        const $ = (sel, root = document) => root.querySelector(sel);
        const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

        const esc = (s) => String(s ?? '')
            .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;').replaceAll("'", "&#039;");

        const escAttr = (s) => String(s ?? '')
            .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;').replaceAll("'", "&#039;");

        const num0 = (v) => {
            const n = Number(v);
            return Number.isFinite(n) ? n : 0;
        };

        const fmtQty = (n) => new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
            .format(Number(n || 0));

        const fmtMoney = (n) => new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            })
            .format(Number(n || 0));

        const moverMeta = (ads) => {
            const a = Number(ads || 0);
            if (a >= 2) return {
                cls: 'badge-fast',
                label: 'FAST',
                icon: 'bi bi-lightning-charge'
            };
            if (a >= .5) return {
                cls: 'badge-med',
                label: 'MED',
                icon: 'bi bi-graph-up'
            };
            if (a >= .1) return {
                cls: 'badge-slow',
                label: 'SLOW',
                icon: 'bi bi-hourglass-split'
            };
            return {
                cls: 'badge-dead',
                label: 'DEAD',
                icon: 'bi bi-moon'
            };
        };

        document.addEventListener('DOMContentLoaded', () => {
            const pageWrap = document.getElementById('stocksItemsPage');
            if (!pageWrap) return;

            const isOwner = (pageWrap.dataset.isOwner === '1');
            const canViewAds = (pageWrap.dataset.canViewAds === '1');
            const hideRtsWarehouse = (pageWrap.dataset.hideRts === '1');
            const stockCardBaseUrl = pageWrap.dataset.stockcardBaseUrl || '';

            const form = $('#stockFilterForm');
            const searchInput = $('#searchInput');
            const warehouseSelect = $('#warehouseSelect');
            const sortSelect = $('#sortSelect');
            const dirSelect = $('#dirSelect');

            const desktopTbody = $('#desktopTbody');
            const mobileList = $('#mobileList');
            const paginationWrap = $('#paginationWrap');

            const dataCard = $('#dataCard');
            const loadingOverlay = $('#loadingOverlay');

            const sumTotalItems = $('#sumTotalItems');
            const sumQty = $('#sumQty');
            const sumValue = $('#sumValue');
            const sumAvgHpp = $('#sumAvgHpp');
            const sumAvgAds = $('#sumAvgAds');
            const hppByCategoryWrap = $('#hppByCategoryWrap');

            const getSelectedWarehouseId = () => {
                const v = warehouseSelect?.value || '';
                const n = Number(v);
                return Number.isFinite(n) && n > 0 ? n : 0;
            };

            const setLoading = (on) => {
                if (!dataCard || !loadingOverlay) return;
                dataCard.classList.toggle('is-loading', !!on);
                loadingOverlay.classList.toggle('show', !!on);
            };

            const fetchJson = async (url) => {
                const res = await fetch(url, {
                    method: 'GET',
                    credentials: 'include',
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            };

            const buildDesktopRow = (row, index, from) => {
                const no = (from || 0) + index;

                let exCols = '';
                if (isOwner) {
                    exCols += `
              <td class="text-end mono">${fmtMoney(num0(row.hpp_per_unit))}</td>
              <td class="text-end mono">${fmtMoney(num0(row.stock_value))}</td>
            `;
                }
                if (canViewAds) {
                    const adsVal = num0(row.ads);
                    const mm = moverMeta(adsVal);
                    exCols += `
              <td class="text-end">
                <div class="d-flex justify-content-end align-items-center gap-2">
                  <span class="mono">${fmtQty(adsVal)}</span>
                  <span class="badge-mover ${mm.cls}"><i class="${mm.icon}"></i>${mm.label}</span>
                </div>
              </td>
              <td class="text-end mono">${fmtMoney(num0(row.coverage_days))}</td>
            `;
                }

                return `
        <tr class="item-row" data-item-id="${row.item_id}" data-locations-url="${esc(row.locations_url)}">
          <td class="text-muted small">${no}</td>
          <td class="mono">
            <button type="button" class="code-btn js-row-toggle">
              <i class="bi bi-caret-right-fill caret"></i><span>${esc(row.item_code)}</span>
            </button>
          </td>
          <td>${esc(row.item_name)}</td>
          <td class="text-end mono">${fmtQty(num0(row.total_qty))}</td>
          <td class="text-end mono" style="color: var(--bs-warning);">
            ${num0(row.allocated_qty) > 0 ? fmtQty(num0(row.allocated_qty)) : '<span class="text-muted">-</span>'}
          </td>
          <td class="text-end mono fw-bold" style="color: var(--bs-success);">
            ${fmtQty(num0(row.total_qty) - num0(row.allocated_qty))}
          </td>
          <td class="text-end mono">${fmtQty(num0(row.fg_qty))}</td>
          <td class="text-end mono">${fmtQty(num0(row.wip_qty))}</td>
          ${exCols}
        </tr>
      `;
            };

            const buildMobileCard = (row, index, from) => {
                const no = (from || 0) + index;
                const rowJson = escAttr(JSON.stringify({
                    total_qty: row.total_qty,
                    allocated_qty: row.allocated_qty,
                    fg_qty: row.fg_qty,
                    wip_qty: row.wip_qty,
                    hpp_per_unit: row.hpp_per_unit,
                    stock_value: row.stock_value,
                    ads: row.ads,
                    coverage_days: row.coverage_days
                }));

                return `
        <div class="mcard item-card"
             data-item-id="${row.item_id}"
             data-locations-url="${esc(row.locations_url)}"
             data-row="${rowJson}">
          <button type="button" class="mcard-btn js-card-toggle">
            <div class="m-left">
              <div class="m-no mono">#${no}</div>
              <div>
                <div class="m-code mono">${esc(row.item_code)}</div>
                <div class="small text-muted mt-1">${esc(row.item_name)}</div>
              </div>
            </div>
            <div class="m-right">
              <div class="m-metric">
                <div>
                  <div class="k">Tersedia</div>
                  <div class="v mono" style="color: var(--bs-success); font-weight: bold;">${fmtQty(num0(row.total_qty) - num0(row.allocated_qty))}</div>
                </div>
              </div>
              <i class="bi bi-caret-right-fill caret"></i>
            </div>
          </button>
          <div class="m-detail row-detail"></div>
        </div>
      `;
            };

            const buildLocationsHtml = (locations, itemId) => {
                let list = (locations || []);

                const selectedWarehouseId = Number(pageWrap?.dataset?.selectedWarehouseId || 0);
                if (selectedWarehouseId > 0) {
                    list = list.filter(loc => Number(loc.id) === selectedWarehouseId);
                }

                if (hideRtsWarehouse) {
                    list = list.filter((loc) => (loc.code || '').toString().toUpperCase() !== 'WH-RTS');
                }

                if (!list.length) return `<div class="text-muted">No stock.</div>`;

                const rows = list.map((loc, idx) => {
                    const whId = loc.id;
                    const whCode = esc(loc.code || '-');
                    const whName = esc(loc.name || '-');
                    const qty = fmtQty(loc.qty || 0);

                    const stockCardUrl = stockCardBaseUrl ?
                        `${stockCardBaseUrl}?item_id=${encodeURIComponent(itemId)}&warehouse_id=${encodeURIComponent(whId)}` :
                        '#';

                    return `
          <tr>
            <td class="text-muted small">${idx + 1}</td>
            <td>
              <div class="fw-semibold">${whCode}</div>
              <div class="small text-muted">${whName}</div>
            </td>
            <td class="text-end mono">${qty}</td>
            <td class="text-end">
              <a href="${stockCardUrl}" class="btn btn-outline-secondary btn-sm py-0 px-2" title="Stock Card">
                <i class="bi bi-journal-text"></i>
              </a>
            </td>
          </tr>
        `;
                }).join('');

                return `<table class="detail-table"><tbody>${rows}</tbody></table>`;
            };

            const buildHppByCategoryHtml = (byCat) => {
                const arr = Array.isArray(byCat) ? byCat : [];
                if (!arr.length) return `<div class="text-muted text-center py-3">No data.</div>`;

                const rows = arr.map((c) => `
        <tr>
          <td class="cat">${esc(c.category || 'Uncategorized')}</td>
          <td class="text-end mono">${fmtQty(num0(c.total_qty))}</td>
          <td class="text-end mono">${fmtMoney(num0(c.total_value))}</td>
          <td class="text-end mono">${fmtMoney(num0(c.avg_hpp_weighted))}</td>
        </tr>
      `).join('');

                return `
        <div class="table-responsive">
          <table class="mini-table mb-0">
            <thead>
              <tr>
                <th>Kategori</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Value</th>
                <th class="text-end">Avg HPP</th>
              </tr>
            </thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      `;
            };

            const buildMobileSummaryHtml = (card) => {
                let r = null;
                try {
                    r = card.dataset.row ? JSON.parse(card.dataset.row) : null;
                } catch {
                    r = null;
                }

                const fg = fmtQty(num0(r?.fg_qty));
                const wip = fmtQty(num0(r?.wip_qty));

                let html = `
        <div class="m-detail-grid">
          <div class="m-kpi"><div class="k">Siap Jual (FG)</div><div class="v mono">${fg}</div></div>
          <div class="m-kpi"><div class="k">Dlm Proses (WIP)</div><div class="v mono">${wip}</div></div>
        </div>
      `;

                if (isOwner) {
                    const hpp = fmtMoney(num0(r?.hpp_per_unit));
                    const val = fmtMoney(num0(r?.stock_value));
                    html += `
          <div class="m-detail-grid">
            <div class="m-kpi"><div class="k">HPP</div><div class="v mono">${hpp}</div></div>
            <div class="m-kpi"><div class="k">Nilai Stok</div><div class="v mono">${val}</div></div>
          </div>
        `;
                }

                if (canViewAds) {
                    const ads = fmtQty(num0(r?.ads));
                    const cover = fmtMoney(num0(r?.coverage_days));
                    const mm = moverMeta(num0(r?.ads));

                    html += `
          <div class="m-detail-grid">
            <div class="m-kpi"><div class="k">Rata² Terjual (ADS)</div><div class="v mono">${ads}
              <span class="badge-mover ${mm.cls}" style="margin-left:.35rem;"><i class="${mm.icon}"></i>${mm.label}</span>
            </div></div>
            <div class="m-kpi"><div class="k">Sisa Hari (Cover)</div><div class="v mono">${cover}</div></div>
          </div>
        `;
                }

                return html;
            };

            const applySummary = (payload) => {
                if (!isOwner) return;

                if (sumTotalItems && payload?.meta?.total !== undefined) {
                    sumTotalItems.textContent = String(payload.meta.total || 0);
                }

                const s = payload?.hpp_summary || null;
                const rows = payload?.rows || [];

                if (s) {
                    if (sumQty) sumQty.textContent = fmtQty(num0(s.total_qty));
                    if (sumValue) sumValue.textContent = fmtMoney(num0(s.total_value));
                    if (sumAvgHpp) sumAvgHpp.textContent = fmtMoney(num0(s.avg_hpp_weighted));
                    if (sumAvgAds) sumAvgAds.textContent = fmtQty(num0(s.avg_ads));
                }

                if (!s && sumAvgAds) {
                    const adsSum = rows.reduce((acc, r) => acc + num0(r.ads), 0);
                    const adsAvg = rows.length ? (adsSum / rows.length) : 0;
                    sumAvgAds.textContent = fmtQty(adsAvg);
                }

                if (hppByCategoryWrap) {
                    hppByCategoryWrap.innerHTML = buildHppByCategoryHtml(payload?.hpp_by_category ||
                []);
                }
            };

            const applyStocksData = (payload) => {
                if (!payload || !payload.ok) return;

                const rows = payload.rows || [];
                const from = payload?.meta?.from || 0;

                $$('tr.detail-row').forEach(n => n.remove());
                $$('tr.item-row.is-open').forEach(n => n.classList.remove('is-open'));
                $$('.item-card.is-open').forEach(n => n.classList.remove('is-open'));

                if (desktopTbody) {
                    if (rows.length) {
                        desktopTbody.innerHTML = rows.map((r, idx) => buildDesktopRow(r, idx, from)).join('');
                    } else {
                        const colSpan = 6 + (isOwner ? 2 : 0) + (canViewAds ? 2 : 0);
                        desktopTbody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center py-4 text-muted">No data.</td></tr>`;
                    }
                }

                if (mobileList) {
                    mobileList.innerHTML = rows.length ?
                        rows.map((r, idx) => buildMobileCard(r, idx, from)).join('') :
                        `<div class="text-center py-4 text-muted">No data.</div>`;
                }

                if (paginationWrap) paginationWrap.innerHTML = payload.pagination_html || '';
                applySummary(payload);
            };

            const fetchStocks = async (extraParams = {}) => {
                if (!form) return;

                const formData = new FormData(form);
                const params = new URLSearchParams(formData);
                Object.entries(extraParams).forEach(([k, v]) => {
                    if (v === undefined || v === null) return;
                    params.set(k, v);
                });

                const url = `${form.action}?${params.toString()}`;

                setLoading(true);
                try {
                    const data = await fetchJson(url);
                    applyStocksData(data);
                } catch (e) {
                    console.error('Fetch JSON failed:', e);
                } finally {
                    setLoading(false);
                }
            };

            const debounce = (fn, delay = 320) => {
                let t;
                return (...args) => {
                    clearTimeout(t);
                    t = setTimeout(() => fn(...args), delay);
                };
            };

            const fetchDebounced = debounce(() => fetchStocks({
                page: 1
            }), 320);

            searchInput?.addEventListener('input', fetchDebounced);

            warehouseSelect?.addEventListener('change', () => {
                pageWrap.dataset.selectedWarehouseId = String(getSelectedWarehouseId());
                fetchStocks({
                    page: 1
                });
            });

            sortSelect?.addEventListener('change', () => fetchStocks({
                page: 1
            }));
            dirSelect?.addEventListener('change', () => fetchStocks({
                page: 1
            }));

            paginationWrap?.addEventListener('click', (e) => {
                const a = e.target.closest('a[href]');
                if (!a) return;
                e.preventDefault();
                const url = new URL(a.href);
                fetchStocks({
                    page: url.searchParams.get('page') || 1
                });
            });

            // desktop toggle
            const openDesktopDetail = (row, html, colspan) => {
                const next = row.nextElementSibling;
                if (next && next.classList.contains('detail-row')) next.remove();

                const tr = document.createElement('tr');
                tr.className = 'detail-row';
                tr.innerHTML = `<td colspan="${colspan}"><div class="detail-inner">${html}</div></td>`;
                row.insertAdjacentElement('afterend', tr);
            };

            const handleDesktopToggle = async (btn) => {
                const row = btn.closest('tr.item-row');
                if (!row) return;

                const colSpan = 6 + (isOwner ? 2 : 0) + (canViewAds ? 2 : 0);
                const isOpen = row.classList.contains('is-open');

                if (isOpen) {
                    row.classList.remove('is-open');
                    const next = row.nextElementSibling;
                    if (next && next.classList.contains('detail-row')) next.remove();
                    return;
                }

                $$('tr.item-row.is-open').forEach((r) => {
                    r.classList.remove('is-open');
                    const n = r.nextElementSibling;
                    if (n && n.classList.contains('detail-row')) n.remove();
                });

                const itemId = row.dataset.itemId;
                const url = row.dataset.locationsUrl || '';
                if (!itemId || !url) return;

                row.classList.add('is-open');
                openDesktopDetail(row, `<div class="text-muted">Loading…</div>`, colSpan);

                try {
                    const data = await fetchJson(url);
                    openDesktopDetail(row, buildLocationsHtml(data.locations || [], itemId),
                        colSpan);
                } catch {
                    openDesktopDetail(row, `<div class="text-muted">Failed.</div>`, colSpan);
                }
            };

            // mobile toggle
            const handleMobileToggle = async (btn) => {
                const card = btn.closest('.item-card');
                if (!card) return;

                const detail = $('.row-detail', card);
                if (!detail) return;

                const isOpen = card.classList.contains('is-open');
                if (isOpen) {
                    card.classList.remove('is-open');
                    detail.style.display = 'none';
                    detail.innerHTML = '';
                    return;
                }

                $$('.item-card.is-open').forEach((c) => {
                    c.classList.remove('is-open');
                    const d = $('.row-detail', c);
                    if (d) {
                        d.style.display = 'none';
                        d.innerHTML = '';
                    }
                });

                const itemId = card.dataset.itemId;
                const url = card.dataset.locationsUrl || '';
                if (!itemId || !url) return;

                card.classList.add('is-open');
                detail.style.display = 'block';
                detail.innerHTML =
                    `<div class="detail-inner"><div class="text-muted">Loading…</div></div>`;

                try {
                    const data = await fetchJson(url);
                    const summaryHtml = buildMobileSummaryHtml(card);
                    const locHtml = buildLocationsHtml(data.locations || [], itemId);
                    detail.innerHTML = `<div class="detail-inner">${summaryHtml}${locHtml}</div>`;
                } catch {
                    detail.innerHTML =
                        `<div class="detail-inner"><div class="text-muted">Failed.</div></div>`;
                }
            };

            document.addEventListener('click', (e) => {
                const desktopBtn = e.target.closest('.js-row-toggle');
                if (desktopBtn) {
                    e.preventDefault();
                    handleDesktopToggle(desktopBtn);
                    return;
                }

                const mobileBtn = e.target.closest('.js-card-toggle');
                if (mobileBtn) {
                    e.preventDefault();
                    handleMobileToggle(mobileBtn);
                    return;
                }
            });

            // initial hydrate
            fetchStocks({
                page: {{ (int) ($currentPage ?? 1) }}
            });
        });
    })();
</script>
