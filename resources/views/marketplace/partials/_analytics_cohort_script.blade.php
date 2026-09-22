    const cohortMetricOptions = {
        customer: [
            ['retention_pct', 'Retention %'], ['active_customers', 'Active Customers'], ['orders', 'Orders'], ['qty_sold', 'Qty Sold'], ['revenue', 'Gross Sales'],
        ],
        product: [
            ['qty_sold', 'Qty Sold'], ['revenue', 'Gross Sales'], ['gross_profit', 'Gross Profit (covered)'], ['gross_margin_pct', 'Gross Margin %'], ['net_profit', 'Net Profit (covered)'],
        ],
    };
    const cohortMetricHints = {
        retention_pct: 'Persentase customer dari cohort yang kembali aktif pada periode tersebut.',
        active_customers: 'Jumlah customer unik yang aktif pada periode tersebut.',
        orders: 'Jumlah order yang tercatat dari cohort pada periode tersebut.',
        qty_sold: 'Total unit produk yang terjual pada periode tersebut.',
        revenue: 'Gross sales dari order eligible pada periode tersebut; bukan payout bersih.',
        gross_profit: 'Gross sales dikurangi fee marketplace dan HPP dari order dengan settlement complete.',
        gross_margin_pct: 'Gross profit sebagai persentase dari revenue.',
        net_profit: 'Gross profit setelah alokasi biaya iklan pada order dengan settlement complete.',
    };
    function syncCohortMetricOptions() {
        const mode = $('anCohortMode').value === 'product' ? 'product' : 'customer';
        const current = $('anCohortMetric').value;
        const options = cohortMetricOptions[mode];
        $('anCohortMetric').innerHTML = options.map(([value, label]) => `<option value="${value}">${label}</option>`).join('');
        $('anCohortMetric').value = options.some(([value]) => value === current) ? current : options[0][0];
        $('anCohortMetricHint').textContent = cohortMetricHints[$('anCohortMetric').value] || 'Pilih metric untuk melihat definisinya.';
    }
    function setCohortSelectOptions(id, placeholder, values) {
        const select = $(id);
        const current = String(select.value || '');
        const uniqueValues = [...new Set((Array.isArray(values) ? values : []).map(value => String(value || '').trim()).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'id'));
        if (current && !uniqueValues.includes(current)) uniqueValues.unshift(current);
        select.innerHTML = `<option value="">${placeholder}</option>${uniqueValues.map(value => `<option value="${esc(value)}">${esc(value)}</option>`).join('')}`;
        select.value = current;
    }
    async function loadCohortOptions(force = false) {
        const key = [from(), to(), selectedStore(), $('anCohortMarketplace').value].join('|');
        if (!force && cohortOptionsKey === key) return;
        const params = new URLSearchParams({ date_from: from(), date_to: to(), _ts: Date.now().toString() });
        if (selectedStore()) params.set('store_id', selectedStore());
        if ($('anCohortMarketplace').value) params.set('marketplace', $('anCohortMarketplace').value);
        try {
            const result = await api('/api/marketplace/analytics-cohort-options?' + params.toString(), { cache:'no-store' });
            cohortOptionsKey = key;
            setCohortSelectOptions('anCohortMarketplace', 'All marketplaces', result?.marketplaces);
            setCohortSelectOptions('anCohortCategory', 'All categories', result?.categories);
            setCohortSelectOptions('anCohortProduct', 'All products', result?.products);
            setCohortSelectOptions('anCohortSku', 'All SKUs', result?.skus);
        } catch (error) {
            console.warn('Cohort filter options failed', error);
        }
    }
    const cohortFormat = (value, metric) => {
        if (value === null || value === undefined || value === '') return '—';
        if (metric === 'retention_pct' || metric === 'gross_margin_pct') return `${Number(value).toFixed(1)}%`;
        if (['revenue', 'gross_profit', 'net_profit'].includes(metric)) return money(value);
        return Number(value).toLocaleString('id-ID');
    };
    const cohortMonthLabel = value => {
        const date = new Date(`${value}-01T00:00:00`);
        return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString('id-ID', { month:'short', year:'numeric' });
    };
    const cohortParams = () => {
        const params = new URLSearchParams({ date_from: from(), date_to: to(), mode: $('anCohortMode').value, metric: $('anCohortMetric').value, _ts: Date.now().toString() });
        if (selectedStore()) params.set('store_id', selectedStore());
        [['marketplace','anCohortMarketplace'],['category','anCohortCategory'],['product','anCohortProduct'],['sku','anCohortSku']].forEach(([key, id]) => {
            const value = String($(id)?.value || '').trim();
            if (value) params.set(key, value);
        });
        return params;
    };
    function renderCohortActiveFilters() {
        const activeStore = stores.find(store => String(store.id ?? store.store_id) === selectedStore());
        const filters = [
            ['Periode', `${from()} — ${to()}`],
            ['Toko', activeStore?.store_name || activeStore?.name || 'Semua toko'],
            ['Marketplace', $('anCohortMarketplace').value.trim()],
            ['Kategori', $('anCohortCategory').value.trim()],
            ['Produk', $('anCohortProduct').value.trim()],
            ['SKU', $('anCohortSku').value.trim()],
        ].filter(([, value]) => value);
        $('anCohortActiveFilters').innerHTML = `<span class="an-cohort-filter-caption">Active scope</span>${filters.map(([label, value]) => `<span class="an-filter-chip">${esc(label)}: <strong>${esc(value)}</strong></span>`).join('')}`;
    }
    function renderCohortKpis(payload) {
        const summary = payload?.summary || {};
        const isProduct = payload?.mode === 'product';
        const periodLabel = `${from()} — ${to()}`;
        const cards = isProduct
            ? [
                ['Product cohorts', Number(summary.product_count ?? 0).toLocaleString('id-ID'), 'Produk unik dalam scope'],
                ['Primary metric', payload.metric_label || '—', 'Metric aktif'],
                ['Gross sales in scope', money(summary.revenue || 0), 'Order eligible dalam scope'],
                ['Avg. coverage', `${Number(summary.avg_financial_coverage_pct || 0).toFixed(1)}%`, 'Rata-rata settlement complete per cell'],
            ]
            : [
                ['Customer cohorts', Number(summary.cohort_count ?? 0).toLocaleString('id-ID'), 'First transaction month'],
                ['Primary metric', payload.metric_label || '—', 'Metric aktif'],
                ['Latest activity', Number(summary.latest_active_customers || 0).toLocaleString('id-ID'), 'Active customers in latest month'],
                ['Avg. M1 retention', summary.avg_m1_retention_pct === null ? '—' : `${Number(summary.avg_m1_retention_pct).toFixed(1)}%`, 'Customer yang kembali aktif'],
            ];
        $('anCohortKpis').innerHTML = cards.map(([label, value, note], index) => `<div class="an-cohort-summary-card ${index === 0 ? 'is-primary' : ''}"><span class="an-cohort-summary-label">${esc(label)}</span><strong class="an-cohort-summary-value">${esc(value)}</strong><span class="an-cohort-summary-note">${esc(note)}</span></div>`).join('');
        $('anCohortPeriodLabel').textContent = periodLabel;
        renderCohortActiveFilters();
        $('anCohortMatrixSubtitle').textContent = `${payload.metric_label || 'Metric'} · ${Number(payload?.rows?.length || 0).toLocaleString('id-ID')} ${isProduct ? 'product cohorts' : 'customer cohorts'} · klik sel untuk detail`;
        const notes = Array.isArray(payload?.notes) ? Object.values(payload.notes) : [];
        $('anCohortNote').textContent = notes.join(' · ') || 'Agregasi cohort mengikuti filter periode dan toko di halaman ini.';
    }
    function cohortCell(detail, metric, isProduct, maxValue, context = {}) {
        if (!detail) return '<td><span class="an-cohort-cell is-empty" title="Tidak ada aktivitas pada periode ini">—</span></td>';
        const value = detail[metric];
        if (value === null || value === undefined) return '<td><span class="an-cohort-cell is-empty" title="Metric tidak tersedia pada periode ini">—</span></td>';
        const intensity = Math.min(.46, .08 + (Math.abs(Number(value || 0)) / Math.max(maxValue, 1)) * .38);
        const encoded = esc(JSON.stringify({ ...context, ...detail, metric, metric_label: payloadMetricLabel(metric), is_product: isProduct }));
        const supportingValue = isProduct ? `Sales ${money(detail.revenue)} · Cov ${Number(detail.financial_coverage_pct || 0).toFixed(0)}%` : `${Number(detail.active_customers || 0).toLocaleString('id-ID')} active`;
        const cellLabel = `${payloadMetricLabel(metric)} ${cohortFormat(value, metric)}, ${supportingValue}`;
        return `<td><button type="button" class="an-cohort-cell ${isProduct ? 'product' : ''}" style="--heat:${intensity}" title="${esc(cellLabel)}" aria-label="${esc(cellLabel)}" data-cohort-detail="${encoded}"><span class="an-cohort-cell-value">${cohortFormat(value, metric)}</span><span class="an-cohort-cell-sub">${esc(supportingValue)}</span></button></td>`;
    }
    const payloadMetricLabel = metric => ({retention_pct:'Retention %',active_customers:'Active Customers',orders:'Orders',qty_sold:'Qty Sold',revenue:'Gross Sales',gross_profit:'Gross Profit (covered)',gross_margin_pct:'Gross Margin %',net_profit:'Net Profit (covered)'})[metric] || metric;
    const cohortHeader = (label, sub = '') => `<th>${label}${sub ? `<small>${sub}</small>` : ''}</th>`;
    function renderCohortTable(payload) {
        const mode = payload?.mode === 'product' ? 'product' : 'customer';
        const metric = payload?.metric || (mode === 'product' ? 'revenue' : 'retention_pct');
        const maxPeriod = Math.max(0, Number(payload?.max_period || 0));
        const periods = Array.from({ length: maxPeriod + 1 }, (_, index) => index);
        $('anCohortTable').classList.toggle('is-product', mode === 'product');
        $('anCohortTable').classList.toggle('is-dense', periods.length > 8);
        if (mode === 'customer') {
            $('anCohortHead').innerHTML = `<tr>${cohortHeader('Cohort', 'first transaction')}${cohortHeader('Base', 'customers')}${periods.map(index => cohortHeader(`M${index}`, index === 0 ? 'same month' : `+${index} month`)).join('')}</tr>`;
            const maxValue = Math.max(...(payload?.rows || []).flatMap(row => Object.values(row.periods || {}).map(period => Number(period[metric] || 0))), 1);
            $('anCohortBody').innerHTML = payload?.rows?.length ? payload.rows.map(row => `<tr><td class="an-cohort-sticky"><span class="an-cohort-row-title">${esc(cohortMonthLabel(row.cohort_month))}</span><span class="an-cohort-row-sub">First transaction month</span></td><td><span class="an-cohort-base-value">${Number(row.cohort_size || 0).toLocaleString('id-ID')}</span><span class="an-cohort-row-sub">customers</span></td>${periods.map(index => cohortCell(row.periods?.[index], metric, false, maxValue, { cohort_month:row.cohort_month })).join('')}</tr>`).join('') : `<tr><td colspan="${periods.length + 2}"><div class="an-empty">Tidak ada cohort customer untuk filter ini.</div></td></tr>`;
            return;
        }
        $('anCohortHead').innerHTML = `<tr>${cohortHeader('Product', 'catalog item')}${cohortHeader('Cohort', 'first transaction')}${periods.map(index => cohortHeader(`M${index}`, index === 0 ? 'same month' : `+${index} month`)).join('')}</tr>`;
        const maxValue = Math.max(...(payload?.rows || []).flatMap(row => Object.values(row.periods || {}).map(period => Number(period[metric] || 0))), 1);
        $('anCohortBody').innerHTML = payload?.rows?.length ? payload.rows.map(row => `<tr><td class="an-cohort-sticky an-cohort-product-cell"><span class="an-cohort-row-title">${esc(row.product_name)}</span><span class="an-cohort-row-sub">${esc(row.sku)} · ${esc(row.category)}</span></td><td>${esc(cohortMonthLabel(row.cohort_month))}</td>${periods.map(index => cohortCell(row.periods?.[index], metric, true, maxValue, { cohort_month:row.cohort_month, product_name:row.product_name, sku:row.sku, category:row.category })).join('')}</tr>`).join('') : `<tr><td colspan="${periods.length + 2}"><div class="an-empty">Tidak ada cohort product untuk filter ini.</div></td></tr>`;
    }
    function openCohortDetail(detail) {
        const label = detail.is_product ? detail.product_name : cohortMonthLabel(detail.cohort_month);
        $('cohortDetailTitle').textContent = `${detail.metric_label} · ${label}`;
        $('cohortDetailSubtitle').textContent = `M${detail.period_index} · ${detail.period_month || detail.cohort_month} · filter aktif`;
        const entries = detail.is_product
            ? [['Nilai', cohortFormat(detail[detail.metric], detail.metric)], ['Orders', Number(detail.orders || 0).toLocaleString('id-ID')], ['Qty Sold', Number(detail.qty_sold || 0).toLocaleString('id-ID')], ['Gross Sales', money(detail.revenue)], ['Fee marketplace', money(detail.marketplace_fee)], ['HPP', money(detail.hpp)], ['Gross Profit', money(detail.gross_profit)], ['Iklan', money(detail.ads)], ['Net Profit', money(detail.net_profit)], ['Coverage', `${Number(detail.financial_coverage_pct || 0).toFixed(1)}%`]]
            : [['Nilai', cohortFormat(detail[detail.metric], detail.metric)], ['Active Customers', Number(detail.active_customers || 0).toLocaleString('id-ID')], ['Orders', Number(detail.orders || 0).toLocaleString('id-ID')], ['Qty Sold', Number(detail.qty_sold || 0).toLocaleString('id-ID')], ['Revenue', money(detail.revenue)]];
        $('cohortDetailSummary').innerHTML = entries.map(([key, value]) => cashStat(key, value)).join('');
        $('cohortDetailNote').textContent = detail.is_product
            ? 'Nilai ini menunjukkan performa produk pada umur cohort tersebut. Gross sales dapat mencakup order eligible; profit hanya dihitung dari settlement complete dan coverage menunjukkan proporsinya.'
            : 'Nilai ini menunjukkan performa customer cohort pada umur tersebut. M0 adalah bulan transaksi pertama; M1+ adalah aktivitas berulang.';
        $('cohortDetailModal').classList.add('is-open');
        $('cohortDetailModal').setAttribute('aria-hidden', 'false');
        document.body.classList.add('an-modal-open');
    }
    function closeCohortDetail() {
        $('cohortDetailModal').classList.remove('is-open');
        $('cohortDetailModal').setAttribute('aria-hidden', 'true');
        document.body.classList.remove('an-modal-open');
    }
    async function loadCohort() {
        if (cohortLoading) return;
        cohortLoading = true;
        $('anCohortApply').disabled = true;
        $('anCohortApply').innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Loading…';
        renderCohortActiveFilters();
        $('anCohortBody').innerHTML = '<tr><td colspan="8"><div class="an-empty">Memuat cohort…</div></td></tr>';
        try {
            await loadCohortOptions();
            cohortPayload = await api('/api/marketplace/analytics-cohort?' + cohortParams().toString(), { cache:'no-store' });
            renderCohortKpis(cohortPayload);
            renderCohortTable(cohortPayload);
            renderCohortCharts(cohortPayload);
        } catch (error) {
            console.error('Cohort load failed', error);
            cohortPayload = null;
            $('anCohortNote').textContent = 'Cohort gagal dimuat. Periksa filter atau log aplikasi.';
            $('anCohortBody').innerHTML = '<tr><td colspan="8"><div class="an-error">Data cohort gagal dimuat.</div></td></tr>';
            $('anCohortCurveChart').innerHTML = '<div class="an-error">Grafik cohort gagal dimuat.</div>';
            $('anCohortDistributionChart').innerHTML = '<div class="an-error">Grafik cohort gagal dimuat.</div>';
        } finally {
            cohortLoading = false;
            $('anCohortApply').disabled = false;
            $('anCohortApply').innerHTML = '<i class="bi bi-play-fill me-1"></i>Run analysis';
        }
    }
    function resetCohortFilters() {
        $('anCohortMode').value = 'customer';
        syncCohortMetricOptions();
        ['anCohortMarketplace','anCohortCategory','anCohortProduct','anCohortSku'].forEach(id => { $(id).value = ''; });
        loadCohort();
    }
