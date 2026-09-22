    function setLoading(message) { $('anSyncNote').textContent = message; }
    function fillStores() {
        const current = selectedStore();
        const unique = new Map(stores
            .filter(store => store && store.id)
            .map(store => [String(store.id), store.name || `Toko #${store.id}`]));
        $('anStore').innerHTML = '<option value="">Semua toko</option>' + [...unique.entries()]
            .sort((a,b) => a[1].localeCompare(b[1]))
            .map(([id,name]) => `<option value="${esc(id)}">${esc(name)}</option>`)
            .join('');
        $('anStore').value = unique.has(current) ? current : '';
    }
    function renderKpis(rows) {
        const current = summary?.current || {};
        const adCost = Number(current.ad_cost ?? current.ads_spend ?? 0);
        const adCostBeforeTax = Number(current.ad_cost_before_tax ?? 0);
        const adCostVat = Number(current.ad_cost_vat ?? Math.max(0, adCost - adCostBeforeTax));
        const settledOrderRevenue = Number(current.cash_order_revenue || 0);
        const unsettledOrderRevenue = Number(current.cash_unsettled_order_revenue || 0);
        const orderRevenue = settledOrderRevenue + unsettledOrderRevenue || Number(current.gmv || 0);
        const returnRefundAmount = Number(current.return_refund_amount ?? current.cash_refund ?? current.refund ?? 0);
        const netOrderRevenue = Math.max(0, Number(current.net_order_revenue ?? orderRevenue));
        const cancelledCount = Number(current.cancelled_count || 0);
        const cancelledAmount = Number(current.cancelled_amount || 0);
        const cashPayout = Number(current.cash_payout ?? current.payout ?? 0);
        const reportedHppTotal = Number(current.hpp_total ?? current.hpp ?? 0);
        const rawHppSettled = Number(current.hpp_settled ?? 0);
        const rawHppUnsettled = current.hpp_unsettled !== undefined
            ? Number(current.hpp_unsettled || 0)
            : Math.max(0, reportedHppTotal - rawHppSettled);
        const hppReturnRefund = Number(current.hpp_return_refund ?? 0);
        const hppReturnRefundSettled = Number(current.hpp_return_refund_settled ?? 0);
        const hppReturnRefundUnsettled = Number(current.hpp_return_refund_unsettled ?? 0);
        const hppSettled = Math.max(0, rawHppSettled);
        const hppUnsettled = Math.max(0, rawHppUnsettled);
        // Return/refund HPP is shown only as an audit detail. It belongs to
        // excluded orders and must not reduce estimated profit.
        const hppTotal = hppSettled + hppUnsettled || reportedHppTotal;
        const hppShipped = Number(current.hpp_shipped ?? 0);
        const actualFee = Number(current.cash_marketplace_fees ?? current.marketplace_fees_actual ?? 0);
        const actualFeeRate = settledOrderRevenue > 0 ? actualFee / settledOrderRevenue : 0;
        const effectiveFeeRate = actualFeeRate > 0 ? actualFeeRate : 0.21;
        const estimatedFee = Number(current.marketplace_fee_estimate ?? orderRevenue * effectiveFeeRate);
        const netSettledBeforeRefund = Math.max(0, settledOrderRevenue - actualFee);
        const estimatedUnsettledNetBeforeRefund = Math.max(0, unsettledOrderRevenue * (1 - effectiveFeeRate));
        const netSettled = netSettledBeforeRefund;
        const estimatedUnsettledNet = estimatedUnsettledNetBeforeRefund;
        const estimatedPayout = Number(current.estimated_payout ?? (netSettled + estimatedUnsettledNet));
        const feeComponents = [
            ['bi-receipt', 'Administrasi', Number(current.cash_commission_fee || 0)],
            ['bi-headset', 'Layanan', Number(current.cash_service_fee || 0)],
            ['bi-arrow-left-right', 'Transaksi', Number(current.cash_transaction_fee || 0)],
            ['bi-shield-check', 'Asuransi', Number(current.cash_shipping_insurance_fee || 0)],
            ['bi-bank', 'Pajak escrow', Number(current.cash_escrow_tax || 0)],
        ];
        const feeRateBase = settledOrderRevenue;
        const feeComposition = actualFee > 0 && feeRateBase > 0
            ? feeComponents.filter(([, , value]) => value > 0).map(([icon, label, value]) => `<span title="${label}"><i class="bi ${icon}" aria-hidden="true"></i> ${(value / feeRateBase * 100).toFixed(1)}%</span>`).join(' · ')
            : 'komposisi belum tersedia';
        const feeRateTotal = actualFee > 0 && feeRateBase > 0
            ? `<span title="Total fee actual"><i class="bi bi-percent" aria-hidden="true"></i> ${(actualFee / feeRateBase * 100).toFixed(1)}%</span>`
            : '';
        const feeCompositionText = actualFee > 0 && feeRateBase > 0
            ? feeComponents.filter(([, , value]) => value > 0).map(([, label, value]) => `${({Administrasi: 'Adm', Layanan: 'Lyn', Transaksi: 'Trx', Asuransi: 'Asr', 'Pajak escrow': 'Esc'})[label] || label} ${(value / feeRateBase * 100).toFixed(1)}%`).join(' · ')
            : 'komposisi belum tersedia';
        const grossProfit = estimatedPayout - hppTotal;
        const estimatedProfit = Number(current.estimated_profit ?? (grossProfit - adCost));
        const operatingProfit = cashPayout - hppTotal - adCost;
        const payoutRate = orderRevenue > 0 ? cashPayout / orderRevenue * 100 : 0;
        const placedOrderCount = Number(current.placed_order_count ?? current.order_total ?? 0);
        const returnRefundCount = Number(current.return_refund_order_count ?? current.return_refund_count ?? 0);
        const returnRefundRate = placedOrderCount > 0 ? returnRefundCount / placedOrderCount * 100 : 0;
        const totalQty = Number(current.product_qty ?? current.qty ?? 0);
        const productQtySettled = Number(current.product_qty_settled ?? 0);
        const productQtyUnsettled = Number(current.product_qty_unsettled ?? Math.max(0, totalQty - productQtySettled));
        const productQtyReturnRefund = Number(current.product_qty_return_refund ?? 0);
        const totalOrderCount = Number(current.order_total || 0);
        const adCostPerOrder = totalOrderCount > 0 ? adCost / totalOrderCount : 0;
        const settledOrderCountRaw = Number(current.cash_order_count || 0);
        const unsettledOrderCountRaw = Number(current.cash_unsettled_order_count || 0);
        const returnSettledOrderCount = Number(current.return_refund_settled_order_count || 0);
        const returnUnsettledOrderCount = Number(current.return_refund_unsettled_order_count || 0);
        const settledOrderCount = Math.max(0, settledOrderCountRaw - returnSettledOrderCount);
        const unsettledOrderCount = Math.max(0, unsettledOrderCountRaw - returnUnsettledOrderCount);
        $('kpiOrders').textContent = Number(current.order_total || 0).toLocaleString('id-ID');
        $('kpiCancelled').textContent = cancelledCount.toLocaleString('id-ID');
        $('kpiCancelledNote').innerHTML = `<span title="Nilai order batal"><i class="bi bi-x-circle" aria-hidden="true"></i> ${money(cancelledAmount)}</span> · tidak masuk omzet/profit`;
        $('kpiProductsSold').textContent = totalQty.toLocaleString('id-ID');
        $('kpiProductsSoldNote').innerHTML = `<span title="Produk sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> ${productQtySettled.toLocaleString('id-ID')}</span> · <span title="Produk pending"><i class="bi bi-clock-history" aria-hidden="true"></i> ${productQtyUnsettled.toLocaleString('id-ID')}</span> · <span title="Produk refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> ${productQtyReturnRefund.toLocaleString('id-ID')}</span>`;
        $('kpiProductsSoldInfo').dataset.tooltip = `Total ${totalQty.toLocaleString('id-ID')} · cair ${productQtySettled.toLocaleString('id-ID')} · pending ${productQtyUnsettled.toLocaleString('id-ID')} · refund ${productQtyReturnRefund.toLocaleString('id-ID')}`;
        $('kpiRevenue').textContent = money(netOrderRevenue);
        $('kpiAdminFee').textContent = money(estimatedFee);
        $('kpiEstimatedCash').textContent = money(estimatedPayout);
        $('kpiHpp').textContent = money(hppTotal);
        $('kpiGrossProfit').textContent = money(grossProfit);
        $('kpiAdCost').textContent = money(adCost);
        $('kpiEstimatedProfit').textContent = money(estimatedProfit);
        $('kpiPayout').textContent = money(cashPayout);
        $('kpiOperatingProfit').textContent = money(operatingProfit);
        $('kpiRefund').textContent = money(returnRefundAmount);
        $('kpiRevenueNote').innerHTML = `<span title="Sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> ${money(settledOrderRevenue)}</span> · <span title="Estimasi belum cair"><i class="bi bi-clock-history" aria-hidden="true"></i> ${money(unsettledOrderRevenue)}</span> · <span title="Batal/refund dikecualikan"><i class="bi bi-shield-check" aria-hidden="true"></i> batal/refund dikecualikan</span>`;
        $('kpiEstimatedCashNote').innerHTML = `<span title="Net sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> ${money(netSettled)}</span> · <span title="Estimasi net belum cair"><i class="bi bi-clock-history" aria-hidden="true"></i> ${money(estimatedUnsettledNet)}</span> · <span title="Batal/refund dikecualikan"><i class="bi bi-shield-check" aria-hidden="true"></i> batal/refund dikecualikan</span>`;
        $('kpiAdminFeeNote').innerHTML = feeRateTotal ? `${feeRateTotal} · ${feeComposition}` : feeComposition;
        $('kpiOrdersNote').innerHTML = `<span title="Sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> ${settledOrderCount.toLocaleString('id-ID')}</span> · <span title="Belum cair"><i class="bi bi-clock-history" aria-hidden="true"></i> ${unsettledOrderCount.toLocaleString('id-ID')}</span>`;
        $('kpiHppKpiNote').innerHTML = `<span title="HPP sudah cair"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> ${money(hppSettled)}</span> · <span title="HPP belum cair"><i class="bi bi-clock-history" aria-hidden="true"></i> ${money(hppUnsettled)}</span> · <span title="HPP return/refund"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> ${money(hppReturnRefund)}</span>`;
        $('kpiHppNote').innerHTML = `<span title="Net revenue"><i class="bi bi-cash-stack" aria-hidden="true"></i> ${money(estimatedPayout)}</span> · <span title="Total HPP"><i class="bi bi-box-seam" aria-hidden="true"></i> ${money(hppTotal)}</span>`;
        $('kpiAdCostNote').innerHTML = `<span title="Biaya iklan sebelum PPN"><i class="bi bi-receipt" aria-hidden="true"></i> ${money(adCostBeforeTax)}</span> · <span title="PPN 11%"><i class="bi bi-percent" aria-hidden="true"></i> ${money(adCostVat)}</span> · <span title="Biaya per order"><i class="bi bi-bag" aria-hidden="true"></i> ${money(adCostPerOrder)}</span>`;
        $('kpiEstimatedProfitNote').innerHTML = `<span title="Margin estimasi"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i> ${orderRevenue > 0 ? (estimatedProfit / orderRevenue * 100).toFixed(1) : '0.0'}%</span> · <span title="Net revenue − HPP − iklan"><i class="bi bi-calculator" aria-hidden="true"></i> estimasi</span>`;
        $('kpiPayoutNote').innerHTML = `<span title="Order settlement complete"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> ${Number(current.cash_order_count || 0).toLocaleString('id-ID')}</span> · <span title="Rasio omzet cair"><i class="bi bi-percent" aria-hidden="true"></i> ${payoutRate.toFixed(1)}%</span>`;
        $('kpiOperatingProfitNote').innerHTML = `<span title="Omzet cair"><i class="bi bi-cash-coin" aria-hidden="true"></i> ${money(cashPayout)}</span> · <span title="Total HPP + iklan"><i class="bi bi-dash-circle" aria-hidden="true"></i> ${money(hppTotal + adCost)}</span>`;
        $('kpiRefundNote').innerHTML = `<span title="Jumlah order refund"><i class="bi bi-bag" aria-hidden="true"></i> ${returnRefundCount.toLocaleString('id-ID')} order</span> · rate ${returnRefundRate.toFixed(1)}% · tidak masuk omzet/profit`;
        $('kpiOrdersInfo').dataset.tooltip = `Order berbayar ${Number(current.order_total || 0).toLocaleString('id-ID')} · ditempatkan ${placedOrderCount.toLocaleString('id-ID')} · cair ${settledOrderCount.toLocaleString('id-ID')} · belum cair ${unsettledOrderCount.toLocaleString('id-ID')} · belum bayar tidak dihitung`;
        $('kpiCancelledInfo').dataset.tooltip = `${cancelledCount.toLocaleString('id-ID')} order batal · nilai ${money(cancelledAmount)} · tidak masuk omzet/profit`;
        $('kpiRefundInfo').dataset.tooltip = `${returnRefundCount.toLocaleString('id-ID')} order · nilai ${money(returnRefundAmount)} · tidak masuk omzet/profit`;
        $('kpiRevenueInfo').dataset.tooltip = `Omzet order berbayar ${money(netOrderRevenue)} · order belum bayar, batal ${money(cancelledAmount)} dan refund ${money(returnRefundAmount)} dikecualikan`;
        $('kpiAdminFeeInfo').dataset.tooltip = `Total ${money(estimatedFee)} · rate ${(effectiveFeeRate * 100).toFixed(1)}% · ${feeCompositionText}`;
        $('kpiEstimatedCashInfo').dataset.tooltip = `Total ${money(estimatedPayout)} · cair ${money(netSettled)} · est. belum ${money(estimatedUnsettledNet)} · batal/refund dikecualikan`;
        $('kpiHppInfo').dataset.tooltip = `Total ${money(hppTotal)} · cair ${money(hppSettled)} · belum ${money(hppUnsettled)} · return ${money(hppReturnRefund)}`;
        $('kpiGrossProfitInfo').dataset.tooltip = `Laba ${money(grossProfit)} · net ${money(estimatedPayout)} · HPP ${money(hppTotal)}`;

        const previous = summary?.previous || {};
        const previousGrossOrderRevenue = Number(previous.cash_order_revenue || 0) + Number(previous.cash_unsettled_order_revenue || 0) || Number(previous.gmv || 0);
        const previousReturnRefundAmount = Number(previous.return_refund_amount ?? previous.cash_refund ?? previous.refund ?? 0);
        const previousNetOrderRevenue = Math.max(0, Number(previous.net_order_revenue ?? previousGrossOrderRevenue));
        const previousSettledOrderRevenue = Number(previous.cash_order_revenue || 0);
        const previousUnsettledOrderRevenue = Number(previous.cash_unsettled_order_revenue || 0);
        const previousActualFee = Number(previous.cash_marketplace_fees ?? previous.marketplace_fees_actual ?? 0);
        const previousFeeRate = previousSettledOrderRevenue > 0 ? previousActualFee / previousSettledOrderRevenue : 0.21;
        const previousEstimatedFee = previousGrossOrderRevenue * previousFeeRate;
        const previousNetSettledBeforeRefund = Math.max(0, previousSettledOrderRevenue - previousActualFee);
        const previousEstimatedUnsettledNetBeforeRefund = Math.max(0, previousUnsettledOrderRevenue * (1 - previousFeeRate));
        const previousNetSettled = previousNetSettledBeforeRefund;
        const previousEstimatedUnsettledNet = previousEstimatedUnsettledNetBeforeRefund;
        const previousEstimatedPayout = Number(previous.estimated_payout ?? (previousNetSettled + previousEstimatedUnsettledNet));
        const previousReportedHpp = Number(previous.hpp_total ?? previous.hpp ?? 0);
        const previousHppSettledRaw = Number(previous.hpp_settled ?? 0);
        const previousHppUnsettledRaw = previous.hpp_unsettled !== undefined
            ? Number(previous.hpp_unsettled || 0)
            : Math.max(0, previousReportedHpp - previousHppSettledRaw);
        const previousHppReturnRefund = Number(previous.hpp_return_refund ?? 0);
        const previousHppReturnRefundSettled = Number(previous.hpp_return_refund_settled ?? 0);
        const previousHppReturnRefundUnsettled = Number(previous.hpp_return_refund_unsettled ?? 0);
        const previousHppSettled = Math.max(0, previousHppSettledRaw);
        const previousHppUnsettled = Math.max(0, previousHppUnsettledRaw);
        const previousHppTotal = previousHppSettled + previousHppUnsettled || previousReportedHpp;
        const previousAdCost = Number(previous.ad_cost || 0);
        const previousCashPayout = Number(previous.cash_payout ?? previous.payout ?? 0);
        const previousGrossProfit = previousEstimatedPayout - previousHppTotal;
        const previousEstimatedProfit = Number(previous.estimated_profit ?? (previousGrossProfit - previousAdCost));
        const previousOperatingProfit = previousCashPayout - previousHppTotal - previousAdCost;
        const previousPlacedOrderCount = Number(previous.placed_order_count ?? previous.order_total ?? 0);
        const previousReturnRate = previousPlacedOrderCount > 0 ? Number(previous.return_refund_order_count ?? previous.return_refund_count ?? 0) / previousPlacedOrderCount * 100 : 0;
        const comparisonText = (value, baseline, formatter = money) => {
            const currentValue = Number(value || 0);
            const previousValue = Number(baseline || 0);
            const previousText = formatter(previousValue);
            if (previousValue === 0) {
                return { text: `<i class="bi bi-arrow-left-right" aria-hidden="true"></i> ${previousText} · ${currentValue === 0 ? '0,0%' : 'baru'}`, className: currentValue > 0 ? 'good' : '' };
            }
            const change = (currentValue - previousValue) / Math.abs(previousValue) * 100;
            return { text: `<i class="bi bi-arrow-left-right" aria-hidden="true"></i> ${previousText} · ${change > 0 ? '+' : ''}${change.toFixed(1).replace('.', ',')}%`, className: change > 0 ? 'good' : (change < 0 ? 'bad' : '') };
        };
        const setKpiComparison = (valueId, value, baseline, formatter = money) => {
            const valueElement = $(valueId);
            const card = valueElement?.closest('.an-kpi');
            if (!card) return;
            let element = card.querySelector('.an-kpi-compare');
            if (!element) {
                element = document.createElement('span');
                card.appendChild(element);
            }
            const comparison = comparisonText(value, baseline, formatter);
            element.className = `an-kpi-compare ${comparison.className}`;
            element.title = 'Nilai periode pembanding dan perubahan';
            element.innerHTML = comparison.text;
        };
        const countFormatter = value => Number(value || 0).toLocaleString('id-ID');
        const percentFormatter = value => `${Number(value || 0).toFixed(1).replace('.', ',')}%`;
        setKpiComparison('kpiOrders', totalOrderCount, previous.order_total, countFormatter);
        setKpiComparison('kpiProductsSold', totalQty, previous.product_qty ?? previous.qty, countFormatter);
        setKpiComparison('kpiRevenue', netOrderRevenue, previousNetOrderRevenue);
        setKpiComparison('kpiAdminFee', estimatedFee, previousEstimatedFee);
        setKpiComparison('kpiEstimatedCash', estimatedPayout, previousEstimatedPayout);
        setKpiComparison('kpiHpp', hppTotal, previousHppTotal);
        setKpiComparison('kpiAdCost', adCost, previousAdCost);
        setKpiComparison('kpiPayout', cashPayout, previousCashPayout);
        setKpiComparison('kpiCancelled', cancelledCount, Number(previous.cancelled_count || 0), countFormatter);
        setKpiComparison('kpiRefund', returnRefundAmount, previousReturnRefundAmount);
        setKpiComparison('kpiGrossProfit', grossProfit, previousGrossProfit);
        setKpiComparison('kpiEstimatedProfit', estimatedProfit, previousEstimatedProfit);
        setKpiComparison('kpiOperatingProfit', operatingProfit, previousOperatingProfit);
    }

    function chartPoints(rows) {
        return (rows || []).map(row => ({
            date: row.date,
            rev: n(row.gmv || row.gross_sales),
            prof: n(row.operating_profit),
        }));
    }
    function loadChartLibrary() {
        if (window.Chart) return Promise.resolve(window.Chart);
        if (chartLibraryPromise) return chartLibraryPromise;
        chartLibraryPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.async = true;
            script.onload = () => resolve(window.Chart);
            script.onerror = () => reject(new Error('Chart.js gagal dimuat'));
            document.head.appendChild(script);
        });
        return chartLibraryPromise;
    }
    function renderChart(rows, previousRows) {
        const renderToken = ++chartRenderToken;
        const points = chartPoints(rows), previousPoints = chartPoints(previousRows || []);
        if (!points.length && !previousPoints.length) { $('revenueChart').innerHTML = '<div class="an-empty">Belum ada order selesai untuk dibandingkan.</div>'; return; }
        const labelsSource = points.length ? points : previousPoints;
        const modeLabel = {prev_period:'periode lalu',prev_month:'tanggal sama bulan lalu',prev_quarter:'tanggal sama 3 bulan lalu',prev_year:'tanggal sama tahun lalu'}[$('anCompare')?.value] || 'periode lalu';
        $('chartCompareNote').textContent = `vs ${modeLabel}`;
        if (!window.Chart) {
            $('revenueChart').innerHTML = '<div class="an-empty">Menyiapkan grafik…</div>';
            loadChartLibrary().then(() => {
                if (renderToken === chartRenderToken) renderChart(rows, previousRows);
            }).catch(() => {
                $('revenueChart').innerHTML = '<div class="an-empty">Grafik belum tersedia.</div>';
            });
            return;
        }
        const labels = labelsSource.map(v => new Date(v.date + 'T00:00:00').toLocaleDateString('id-ID', { day:'2-digit', month:'short' }));
        $('revenueChart').innerHTML = '<div class="an-chart-canvas"><canvas id="anRevenueChart" aria-label="Grafik interaktif omzet dan laba"></canvas></div>';
        if (revenueChartInstance) revenueChartInstance.destroy();
        const canvas = $('anRevenueChart');
        const context = canvas.getContext('2d');
        const revenueGradient = context.createLinearGradient(0, 0, 0, 240);
        revenueGradient.addColorStop(0, 'rgba(22, 163, 74, .24)');
        revenueGradient.addColorStop(1, 'rgba(22, 163, 74, 0)');
        const chartMoneyTick = value => {
            const absolute = Math.abs(Number(value || 0));
            if (absolute >= 1000000) return `${value < 0 ? '-' : ''}Rp ${(absolute / 1000000).toFixed(1)} Jt`;
            if (absolute >= 1000) return `${value < 0 ? '-' : ''}Rp ${(absolute / 1000).toFixed(0)} Rb`;
            return `Rp ${Number(value || 0).toLocaleString('id-ID')}`;
        };
        revenueChartInstance = new Chart(context, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label:'Omzet kini', data:points.map(point => point.rev), borderColor:'#16a34a', backgroundColor:revenueGradient, fill:true, tension:.4, borderWidth:2, pointRadius:points.length <= 1 ? 4 : 0, pointHitRadius:15, pointHoverRadius:4, pointHoverBackgroundColor:'#16a34a' },
                    { label:'Estimasi profit', data:points.map(point => point.prof), borderColor:'#2563eb', backgroundColor:'transparent', fill:false, tension:.4, borderWidth:2, pointRadius:points.length <= 1 ? 4 : 0, pointHitRadius:15, pointHoverRadius:4, pointHoverBackgroundColor:'#2563eb' },
                    { label:`Omzet ${modeLabel}`, data:previousPoints.map(point => point.rev), borderColor:'#94a3b8', backgroundColor:'transparent', fill:false, tension:.4, borderWidth:1.6, borderDash:[6,5], pointRadius:0, pointHitRadius:15, pointHoverRadius:3, pointHoverBackgroundColor:'#94a3b8' },
                    { label:`Estimasi profit ${modeLabel}`, data:previousPoints.map(point => point.prof), borderColor:'#f59e0b', backgroundColor:'transparent', fill:false, tension:.4, borderWidth:1.6, borderDash:[6,5], pointRadius:0, pointHitRadius:15, pointHoverRadius:3, pointHoverBackgroundColor:'#f59e0b' },
                ],
            },
            options: {
                responsive:true,
                maintainAspectRatio:false,
                interaction:{ mode:'index', intersect:false },
                animation:{ duration:420, easing:'easeOutQuart' },
                plugins:{
                    legend:{ position:'top', labels:{ usePointStyle:true, boxWidth:6, padding:14, color:'#64748b', font:{ size:10, family:'Inter, sans-serif', weight:'700' } } },
                    tooltip:{ backgroundColor:'rgba(15,23,42,.95)', titleColor:'#f8fafc', bodyColor:'#f8fafc', borderColor:'rgba(255,255,255,.15)', borderWidth:1, padding:10, cornerRadius:8, displayColors:true, boxPadding:4, callbacks:{ label:context => `${context.dataset.label}: ${money(context.parsed.y)}` } },
                },
                scales:{
                    x:{ grid:{ display:false }, ticks:{ color:'#94a3b8', font:{ size:10, family:'Inter, sans-serif' }, maxRotation:0, autoSkip:true, maxTicksLimit:8 } },
                    y:{ beginAtZero:true, grid:{ color:'rgba(148,163,184,.16)', drawBorder:false }, ticks:{ color:'#94a3b8', padding:8, font:{ size:10, family:'Inter, sans-serif' }, callback:chartMoneyTick } },
                },
            },
        });
    }
    function renderCohortCharts(payload) {
        const renderToken = ++cohortChartRenderToken;
        const rows = Array.isArray(payload?.rows) ? payload.rows : [];
        const mode = payload?.mode === 'product' ? 'product' : 'customer';
        const metric = payload?.metric || (mode === 'product' ? 'revenue' : 'retention_pct');
        const periods = Array.from({ length: Math.max(0, Number(payload?.max_period || 0)) + 1 }, (_, index) => index);
        const empty = '<div class="an-empty">Belum ada data cohort untuk divisualisasikan.</div>';
        if (!rows.length) {
            if (cohortChartInstance) cohortChartInstance.destroy();
            if (cohortDistributionChartInstance) cohortDistributionChartInstance.destroy();
            cohortChartInstance = null;
            cohortDistributionChartInstance = null;
            $('anCohortCurveChart').innerHTML = empty;
            $('anCohortDistributionChart').innerHTML = empty;
            return;
        }
        if (!window.Chart) {
            $('anCohortCurveChart').innerHTML = '<div class="an-empty">Menyiapkan grafik…</div>';
            $('anCohortDistributionChart').innerHTML = '<div class="an-empty">Menyiapkan grafik…</div>';
            loadChartLibrary().then(() => {
                if (renderToken === cohortChartRenderToken) renderCohortCharts(payload);
            }).catch(() => {
                $('anCohortCurveChart').innerHTML = '<div class="an-empty">Grafik belum tersedia.</div>';
                $('anCohortDistributionChart').innerHTML = '<div class="an-empty">Grafik belum tersedia.</div>';
            });
            return;
        }
        const isPercent = ['retention_pct', 'gross_margin_pct'].includes(metric);
        const formatValue = value => isPercent ? `${Number(value || 0).toFixed(1)}%` : cohortFormat(value, metric);
        const tickValue = value => {
            if (isPercent) return `${Number(value || 0).toFixed(0)}%`;
            const absolute = Math.abs(Number(value || 0));
            if (absolute >= 1000000) return `${value < 0 ? '-' : ''}Rp ${(absolute / 1000000).toFixed(1)} Jt`;
            if (absolute >= 1000) return `${value < 0 ? '-' : ''}Rp ${(absolute / 1000).toFixed(0)} Rb`;
            return metric === 'qty_sold' || metric === 'orders' || metric === 'active_customers' ? Number(value || 0).toLocaleString('id-ID') : `Rp ${Number(value || 0).toLocaleString('id-ID')}`;
        };
        const averageByPeriod = periods.map(periodIndex => {
            const values = rows.map(row => Number(row.periods?.[periodIndex]?.[metric])).filter(value => Number.isFinite(value));
            return values.length ? values.reduce((sum, value) => sum + value, 0) / values.length : null;
        });
        const distribution = mode === 'customer'
            ? rows.map(row => ({ label: cohortMonthLabel(row.cohort_month), value: Number(row.cohort_size || 0) }))
            : Object.entries(rows.reduce((carry, row) => { carry[row.cohort_month] = (carry[row.cohort_month] || 0) + 1; return carry; }, {})).sort(([a], [b]) => a.localeCompare(b)).map(([label, value]) => ({ label: cohortMonthLabel(label), value }));
        $('anCohortCurveSubtitle').textContent = `${payload.metric_label || 'Metric'} · rata-rata ${mode === 'product' ? (payload.group_by === 'category' ? 'kategori' : 'produk') : 'cohort pelanggan'} per umur`;
        $('anCohortDistributionSubtitle').textContent = mode === 'customer'
            ? 'Ukuran cohort pelanggan berdasarkan bulan transaksi pertama.'
            : `Jumlah ${payload.group_by === 'category' ? 'kategori' : 'produk'} berdasarkan bulan transaksi pertama.`;
        $('anCohortCurveChart').innerHTML = '<canvas id="anCohortCurveCanvas" aria-label="Grafik progression cohort"></canvas>';
        $('anCohortDistributionChart').innerHTML = '<canvas id="anCohortDistributionCanvas" aria-label="Grafik distribusi cohort"></canvas>';
        if (cohortChartInstance) cohortChartInstance.destroy();
        if (cohortDistributionChartInstance) cohortDistributionChartInstance.destroy();
        const curveColor = mode === 'product' ? '#d97706' : '#2563eb';
        cohortChartInstance = new Chart($('anCohortCurveCanvas').getContext('2d'), {
            type:'line',
            data:{ labels:periods.map(index => `M${index}`), datasets:[{ label:payload.metric_label || 'Metric', data:averageByPeriod, borderColor:curveColor, backgroundColor:mode === 'product' ? 'rgba(217,119,6,.14)' : 'rgba(37,99,235,.14)', fill:true, tension:.35, borderWidth:2.4, pointRadius:periods.length <= 8 ? 3 : 1, pointHoverRadius:5, spanGaps:false }] },
            options:{ responsive:true, maintainAspectRatio:false, interaction:{mode:'index', intersect:false}, animation:{duration:420, easing:'easeOutQuart'}, plugins:{ legend:{display:false}, tooltip:{ backgroundColor:'rgba(15,23,42,.95)', titleColor:'#f8fafc', bodyColor:'#f8fafc', borderColor:'rgba(255,255,255,.15)', borderWidth:1, padding:9, callbacks:{ label:context => `${context.dataset.label}: ${formatValue(context.parsed.y)}` } } }, scales:{ x:{grid:{display:false}, ticks:{color:'#94a3b8',font:{size:10,family:'Inter, sans-serif'}}}, y:{grid:{color:'rgba(148,163,184,.16)'}, ticks:{color:'#94a3b8',font:{size:10,family:'Inter, sans-serif'}, callback:tickValue}} } },
        });
        cohortDistributionChartInstance = new Chart($('anCohortDistributionCanvas').getContext('2d'), {
            type:'bar',
            data:{ labels:distribution.map(item => item.label), datasets:[{ label:mode === 'customer' ? 'Pelanggan' : (payload.group_by === 'category' ? 'Kategori' : 'Produk'), data:distribution.map(item => item.value), backgroundColor:mode === 'product' ? 'rgba(217,119,6,.72)' : 'rgba(22,163,74,.72)', borderRadius:5, maxBarThickness:32 }] },
            options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false}, tooltip:{ backgroundColor:'rgba(15,23,42,.95)', titleColor:'#f8fafc', bodyColor:'#f8fafc', borderColor:'rgba(255,255,255,.15)', borderWidth:1, padding:9, callbacks:{ label:context => `${context.dataset.label}: ${Number(context.parsed.y || 0).toLocaleString('id-ID')}` } } }, scales:{ x:{grid:{display:false}, ticks:{color:'#94a3b8',font:{size:10,family:'Inter, sans-serif'},maxRotation:0,autoSkip:true,maxTicksLimit:8}}, y:{beginAtZero:true, grid:{color:'rgba(148,163,184,.16)'}, ticks:{color:'#94a3b8',font:{size:10,family:'Inter, sans-serif'},precision:0}} } },
        });
    }
    function renderFunnel() {
        const current = summary?.current || {};
        const max = Math.max(Number(current.placed_order_count ?? current.order_total ?? 0), 1);
        const data = [
            ['Order masuk', Number(current.placed_order_count ?? current.order_total ?? 0), Number(current.placed_order_count ?? current.order_total ?? 0)],
            ['Order eligible', Number(current.order_total || 0), Number(current.order_total || 0)],
            ['Order dikirim', Number(current.shipped_count || 0), Number(current.shipped_count || 0)],
            ['Order selesai', Number(current.completed_count || 0), Number(current.completed_count || 0)],
            ['Order dibatalkan', Number(current.cancelled_count || 0), Number(current.cancelled_count || 0)],
            ['Return / refund', Number(current.return_refund_count || 0), Number(current.return_refund_count || 0)],
            ['Laba operasional', money(current.operating_profit), Math.max(Number(current.operating_profit || 0), 0)],
        ];
        $('salesFunnel').innerHTML = data.map(([label,value,amount]) => `<div class="an-funnel-row"><span>${label}</span><div class="an-funnel-track"><span style="width:${Math.max(5,Math.round(amount / max * 100))}%"></span></div><strong class="an-funnel-value">${typeof value === 'number' ? value.toLocaleString('id-ID') : value}</strong></div>`).join('');
    }
    function renderFinancePulse() {
        const current = summary?.current || {};
        const previous = summary?.previous || {};
        const adSpend = Number(current.ad_cost || 0);
        const previousAdSpend = Number(previous.ad_cost || 0);
        const cogs = Number(current.hpp_total ?? current.hpp ?? 0);
        const previousCogs = Number(previous.hpp_total ?? previous.hpp ?? 0);
        const estimatedNetProfit = Number(current.estimated_profit ?? 0);
        const previousEstimatedNetProfit = Number(previous.estimated_profit ?? 0);
        const roiBase = cogs + adSpend;
        const previousRoiBase = previousCogs + previousAdSpend;
        const roi = roiBase > 0 ? estimatedNetProfit / roiBase * 100 : 0;
        const previousRoi = previousRoiBase > 0 ? previousEstimatedNetProfit / previousRoiBase * 100 : 0;
        const roe = cogs > 0 ? estimatedNetProfit / cogs * 100 : 0;
        const previousRoe = previousCogs > 0 ? previousEstimatedNetProfit / previousCogs * 100 : 0;
        const compare = (value, baseline, formatter = money) => {
            const currentValue = Number(value || 0);
            const previousValue = Number(baseline || 0);
            if (previousValue === 0) {
                const className = currentValue > 0 ? 'good' : '';
                return `<span class="an-pulse-compare ${className}"><i class="bi bi-arrow-left-right" aria-hidden="true"></i> ${currentValue === 0 ? '0,0% vs lalu' : 'baru'}</span>`;
            }
            const change = (currentValue - previousValue) / Math.abs(previousValue) * 100;
            const className = change > 0 ? 'good' : (change < 0 ? 'bad' : '');
            return `<span class="an-pulse-compare ${className}"><i class="bi bi-arrow-left-right" aria-hidden="true"></i> ${formatter(previousValue)} · ${change > 0 ? '+' : ''}${change.toFixed(1).replace('.', ',')}%</span>`;
        };
        const cards = [
            ['bi-megaphone', 'Spend iklan', money(adSpend), compare(adSpend, previousAdSpend), 'Biaya iklan periode ini'],
            ['bi-box-seam', 'COGS', money(cogs), compare(cogs, previousCogs), 'Total HPP / COGS periode ini'],
            ['bi-piggy-bank', 'Est. Net Profit', money(estimatedNetProfit), compare(estimatedNetProfit, previousEstimatedNetProfit), 'Estimasi net profit setelah fee, return/refund, COGS, dan iklan'],
            ['bi-graph-up-arrow', 'ROI', `${roi.toFixed(1).replace('.', ',')}%`, compare(roi, previousRoi, value => `${Number(value || 0).toFixed(1).replace('.', ',')}%`), 'Net profit ÷ (COGS + spend iklan)'],
            ['bi-person-check', 'ROE estimasi', `${roe.toFixed(1).replace('.', ',')}%`, compare(roe, previousRoe, value => `${Number(value || 0).toFixed(1).replace('.', ',')}%`), 'Proxy modal barang: net profit ÷ COGS'],
        ];
        $('anFinancePulse').innerHTML = cards.map(([icon, label, value, note, title]) => `<div class="an-pulse" title="${title}"><div class="an-pulse-label"><i class="bi ${icon} me-1" aria-hidden="true"></i>${label}</div><div class="an-pulse-value">${value}</div><div class="an-pulse-note">${note}</div></div>`).join('');
    }

    function delta(key) {
        const value = summary?.changes?.[key];
        if (value === null || typeof value === 'undefined') return { text: 'Baru', className: 'good' };
        const numeric = Number(value || 0);
        return { text: `${numeric > 0 ? '+' : ''}${numeric.toFixed(1)}% vs lalu`, className: numeric > 0 ? 'good' : (numeric < 0 ? 'bad' : '') };
    }
    function healthClass(value, inverse = false) {
        const score = inverse ? 100 - Number(value || 0) : Number(value || 0);
        return score >= 85 ? '' : (score >= 65 ? 'warn' : 'bad');
    }
    function renderEnterprise() {
        const current = summary?.current || {};
        const quality = summary?.quality || {};
        const cashPayout = Number(current.cash_payout ?? current.payout ?? 0);
        const actualCashFee = Number(current.cash_marketplace_fees ?? current.marketplace_fees_actual ?? 0);
        const actualAffiliateFee = Number(current.cash_affiliate_fees ?? current.affiliate_fees_actual ?? 0);
        const grossOrderRevenue = Number(current.cash_order_revenue || 0) + Number(current.cash_unsettled_order_revenue || 0) || Number(current.gmv || 0);
        const settledOrderRevenue = Number(current.cash_order_revenue || 0);
        const unsettledOrderRevenue = Number(current.cash_unsettled_order_revenue || 0);
        const netOrderRevenue = Math.max(0, Number(current.net_order_revenue ?? grossOrderRevenue));
        const totalHpp = Number(current.hpp_total ?? current.hpp ?? 0);
        const adCost = Number(current.ad_cost || 0);
        const buyerPayment = Number(current.cash_gross_sales || 0) + Number(current.cash_unsettled_gross_sales || 0);
        const buyerPaymentOrders = Number(current.cash_order_count || 0) + Number(current.cash_unsettled_order_count || 0);
        const apc = buyerPaymentOrders > 0 ? buyerPayment / buyerPaymentOrders : 0;
        const aovNet = Number(current.order_total || 0) > 0 ? netOrderRevenue / Number(current.order_total) : 0;
        const actualFeeRate = Number(current.cash_order_revenue || 0) > 0
            ? Number(current.cash_marketplace_fees || 0) / Number(current.cash_order_revenue)
            : 0.21;
        const estimatedProfitFallback = netOrderRevenue - (grossOrderRevenue * actualFeeRate) - totalHpp - Number(current.ad_cost || 0);
        const totalOrder = Number(current.order_total || 0);
        const totalProducts = Number(current.product_qty ?? current.qty ?? 0);
        const cancelledOrders = Number(current.cancelled_count || 0);
        const cancelledAmount = Number(current.cancelled_amount || 0);
        const returnRefundOrders = Number(current.return_refund_order_count ?? current.return_refund_count ?? 0);
        const returnRefundAmount = Number(current.return_refund_amount ?? current.cash_refund ?? current.refund ?? 0);
        const placedOrders = Number(current.placed_order_count ?? totalOrder);
        const completedOrders = Number(current.completed_count || 0);
        const completionRate = Number(current.completion_rate ?? (totalOrder > 0 ? completedOrders / totalOrder * 100 : 0));
        const previous = summary?.previous || {};
        const previousGrossOrderRevenue = Number(previous.cash_order_revenue || 0) + Number(previous.cash_unsettled_order_revenue || 0) || Number(previous.gmv || 0);
        const previousNetOrderRevenue = Math.max(0, Number(previous.net_order_revenue ?? previousGrossOrderRevenue));
        const previousCancelledAmount = Number(previous.cancelled_amount || 0);
        const previousReturnRefundAmount = Number(previous.return_refund_amount ?? previous.cash_refund ?? previous.refund ?? 0);
        const previousCompletedOrders = Number(previous.completed_count || 0);
        const previousCompletionRate = Number(previous.completion_rate ?? (Number(previous.order_total || 0) > 0 ? previousCompletedOrders / Number(previous.order_total) * 100 : 0));
        const previousHpp = Number(previous.hpp_total ?? previous.hpp ?? 0);
        const previousAdCost = Number(previous.ad_cost || 0);
        const previousFeeRate = Number(previous.cash_order_revenue || 0) > 0
            ? Number(previous.cash_marketplace_fees || 0) / Number(previous.cash_order_revenue)
            : 0.21;
        const previousEstimatedProfitFallback = previousNetOrderRevenue - (previousGrossOrderRevenue * previousFeeRate) - previousHpp - Number(previous.ad_cost || 0);
        const previousAovNet = Number(previous.order_total || 0) > 0 ? previousNetOrderRevenue / Number(previous.order_total) : 0;
        const estimatedProfit = Number(current.estimated_profit ?? estimatedProfitFallback);
        const previousEstimatedProfit = Number(previous.estimated_profit ?? previousEstimatedProfitFallback);
        const estimatedNetProfit = Number(current.estimated_net_profit ?? current.estimated_profit ?? estimatedProfit);
        const previousEstimatedNetProfit = Number(previous.estimated_net_profit ?? previous.estimated_profit ?? previousEstimatedProfit);
        const gmv = Number(current.gmv || grossOrderRevenue || 0);
        const previousGmv = Number(previous.gmv || previousGrossOrderRevenue || 0);
        const settledProfit = Number(current.settled_profit ?? current.operating_profit ?? 0);
        const estimatedUnsettledProfit = Number(current.estimated_unsettled_profit ?? (estimatedProfit - settledProfit));
        const previousSettledProfit = Number(previous.settled_profit ?? previous.operating_profit ?? 0);
        const previousEstimatedUnsettledProfit = Number(previous.estimated_unsettled_profit ?? (previousEstimatedProfit - previousSettledProfit));
        const estimatedMargin = Number(current.estimated_profit_margin ?? (netOrderRevenue > 0 ? estimatedProfit / netOrderRevenue * 100 : 0));
        const previousEstimatedMargin = Number(previous.estimated_profit_margin ?? (previousNetOrderRevenue > 0 ? previousEstimatedProfit / previousNetOrderRevenue * 100 : 0));
        const previousActualCashFee = Number(previous.cash_marketplace_fees ?? previous.marketplace_fees_actual ?? 0);
        const previousActualAffiliateFee = Number(previous.cash_affiliate_fees ?? previous.affiliate_fees_actual ?? 0);
        const estimatedPayout = Number(current.estimated_payout ?? cashPayout);
        const previousCashPayout = Number(previous.cash_payout ?? previous.payout ?? 0);
        const previousEstimatedPayout = Number(previous.estimated_payout ?? previousCashPayout);
        const pendingPayout = Math.max(0, estimatedPayout - cashPayout);
        const previousPendingPayout = Math.max(0, previousEstimatedPayout - previousCashPayout);
        const payoutRealization = estimatedPayout > 0 ? cashPayout / estimatedPayout * 100 : 0;
        const pendingPayoutRate = estimatedPayout > 0 ? pendingPayout / estimatedPayout * 100 : 0;
        const feeRate = settledOrderRevenue > 0 ? actualCashFee / settledOrderRevenue * 100 : 0;
        const adCostRate = netOrderRevenue > 0 ? adCost / netOrderRevenue * 100 : 0;
        const settledProfitMargin = settledOrderRevenue > 0 ? settledProfit / settledOrderRevenue * 100 : 0;
        const estimatedUnsettledProfitMargin = unsettledOrderRevenue > 0 ? estimatedUnsettledProfit / unsettledOrderRevenue * 100 : 0;
        const previousBuyerPayment = Number(previous.cash_gross_sales || 0) + Number(previous.cash_unsettled_gross_sales || 0);
        const previousBuyerPaymentOrders = Number(previous.cash_order_count || 0) + Number(previous.cash_unsettled_order_count || 0);
        const previousApc = previousBuyerPaymentOrders > 0 ? previousBuyerPayment / previousBuyerPaymentOrders : 0;
        const pulseChange = (value, previousValue, formatter = value => String(value)) => {
            const currentValue = Number(value || 0);
            const baseline = Number(previousValue || 0);
            const previousText = formatter(baseline);
            if (baseline === 0) return { text: `<i class="bi bi-arrow-left-right" title="Periode lalu" aria-hidden="true"></i> ${previousText} · ${currentValue === 0 ? '0.0% vs lalu' : 'baru'}`, className: currentValue > 0 ? 'good' : '' };
            const change = (currentValue - baseline) / Math.abs(baseline) * 100;
            return { text: `<i class="bi bi-arrow-left-right" title="Periode lalu" aria-hidden="true"></i> ${previousText} · ${change > 0 ? '+' : ''}${change.toFixed(1)}% vs lalu`, className: change > 0 ? 'good' : (change < 0 ? 'bad' : '') };
        };
        const countText = value => Number(value || 0).toLocaleString('id-ID');
        const moneyText = value => money(value);
        const totalOrderChange = pulseChange(totalOrder, previous.order_total, countText);
        const totalProductsChange = pulseChange(totalProducts, previous.product_qty ?? previous.qty, countText);
        const netRevenueChange = pulseChange(netOrderRevenue, previousNetOrderRevenue, moneyText);
        const adCostChange = pulseChange(adCost, previousAdCost, moneyText);
        const marketplaceFeeChange = pulseChange(actualCashFee, previousActualCashFee, moneyText);
        const affiliateFeeChange = pulseChange(actualAffiliateFee, previousActualAffiliateFee, moneyText);
        const aovChange = pulseChange(aovNet, previousAovNet, moneyText);
        const apcChange = pulseChange(apc, previousApc, moneyText);
        const cancelledAmountChange = pulseChange(cancelledAmount, previousCancelledAmount, moneyText);
        const refundAmountChange = pulseChange(returnRefundAmount, previousReturnRefundAmount, moneyText);
        const exceptionChange = change => ({ ...change, className: change.className === 'good' ? 'bad' : (change.className === 'bad' ? 'good' : '') });
        const estimatedProfitChange = pulseChange(estimatedProfit, previousEstimatedProfit, moneyText);
        const settledProfitChange = pulseChange(settledProfit, previousSettledProfit, moneyText);
        const estimatedUnsettledProfitChange = pulseChange(estimatedUnsettledProfit, previousEstimatedUnsettledProfit, moneyText);
        const percentText = value => `${Number(value || 0).toFixed(1)}%`;
        const completedOrdersChange = pulseChange(completedOrders, previousCompletedOrders, countText);
        const completionRateChange = pulseChange(completionRate, previousCompletionRate, percentText);
        const estimatedMarginChange = pulseChange(estimatedMargin, previousEstimatedMargin, percentText);
        const pendingPayoutChange = pulseChange(pendingPayout, previousPendingPayout, moneyText);
        const cashPayoutChange = pulseChange(cashPayout, previousCashPayout, moneyText);
        const decisionChange = (value, previousValue, inverse = false) => {
            const currentValue = Number(value || 0);
            const baseline = Number(previousValue || 0);
            if (baseline === 0) return { text: currentValue === 0 ? '0% vs lalu' : 'Baru', className: inverse && currentValue > 0 ? 'bad' : (currentValue > 0 ? 'good' : '') };
            const change = (currentValue - baseline) / Math.abs(baseline) * 100;
            return { text: `${change > 0 ? '+' : ''}${change.toFixed(1)}% vs lalu`, className: inverse ? (change > 0 ? 'bad' : (change < 0 ? 'good' : '')) : (change > 0 ? 'good' : (change < 0 ? 'bad' : '')) };
        };
        const gmvChange = decisionChange(gmv, previousGmv);
        const decisionPayoutChange = decisionChange(cashPayout, previousCashPayout);
        const decisionSettledProfitChange = decisionChange(settledProfit, previousSettledProfit);
        const decisionPendingPayoutChange = decisionChange(pendingPayout, previousPendingPayout, true);
        const decisionUnsettledProfitChange = decisionChange(estimatedUnsettledProfit, previousEstimatedUnsettledProfit);
        const decisionFeeChange = decisionChange(actualCashFee, previousActualCashFee, true);
        const decisionAdCostChange = decisionChange(adCost, previousAdCost, true);
        const netProfitChange = decisionChange(estimatedNetProfit, previousEstimatedNetProfit);
        const returnRefundRate = placedOrders > 0 ? returnRefundOrders / placedOrders * 100 : 0;
        const total = Math.max(Number(quality.total || 0), 1);
        const readyRate = Number(quality.ready || 0) / total * 100;
        const topStores = [...(summary?.stores || [])].sort((a,b) => Number(b.gross_sales || 0) - Number(a.gross_sales || 0)).slice(0, 5);
        const pulse = [
            ['Total order', totalOrder.toLocaleString('id-ID'), { text: `eligible · ${totalOrderChange.text}`, className: totalOrderChange.className }, 'orders'],
            ['Order selesai', completedOrders.toLocaleString('id-ID'), { text: `${completionRate.toFixed(1)}% dari order masuk · ${completedOrdersChange.text}`, className: completedOrdersChange.className }, 'completed'],
            ['Completion rate', `${completionRate.toFixed(1)}%`, completionRateChange, 'completion-rate'],
            ['Produk terjual', totalProducts.toLocaleString('id-ID'), { text: `unit · ${totalProductsChange.text}`, className: totalProductsChange.className }, 'products'],
            ['AOV net', money(aovNet), aovChange, 'aov'],
            ['APC pembeli', money(apc), { text: `cair + pending · ${apcChange.text}`, className: apcChange.className }, 'apc'],
            ['Dibatalkan', cancelledOrders.toLocaleString('id-ID'), { text: `${money(cancelledAmount)} · ${cancelledAmountChange.text}`, className: exceptionChange(cancelledAmountChange).className }, 'cancelled'],
            ['Refund / return', money(returnRefundAmount), { text: `${returnRefundOrders.toLocaleString('id-ID')} order · ${returnRefundRate.toFixed(1)}% · ${refundAmountChange.text}`, className: exceptionChange(refundAmountChange).className }, 'refund'],
        ];
        $('anPulseGrid').innerHTML = pulse.map(([label,value,change,metric]) => `<div class="an-pulse an-pulse-action ${selectedPulseMetric === metric ? 'is-active' : ''}" data-pulse-metric="${metric}" role="button" tabindex="0" title="Bandingkan grafik dengan tanggal sama bulan lalu"><div class="an-pulse-label">${label}</div><div class="an-pulse-value">${value}</div><div class="an-pulse-note ${change.className}">${change.text}</div></div>`).join('');
        const decisionPulse = [
            ['GMV', money(gmv), { text: 'omzet order eligible', className: gmvChange.className }, gmvChange, 'cash'],
            ['Dana cair', money(cashPayout), { text: `${payoutRealization.toFixed(1)}% dari estimasi`, className: cashPayoutChange.className }, decisionPayoutChange, 'cash'],
            ['Dana belum cair', money(pendingPayout), { text: `${pendingPayoutRate.toFixed(1)}% dari estimasi`, className: decisionPendingPayoutChange.className }, decisionPendingPayoutChange, 'cash'],
            ['Biaya marketplace', money(actualCashFee), { text: `${feeRate.toFixed(1)}% dari dana cair`, className: exceptionChange(marketplaceFeeChange).className }, decisionFeeChange, 'cost'],
            ['Biaya iklan', money(adCost), { text: `${adCostRate.toFixed(1)}% dari omzet net`, className: exceptionChange(adCostChange).className }, decisionAdCostChange, 'cost'],
            ['Est. net profit', money(estimatedNetProfit), { text: `${estimatedMargin.toFixed(1)}% margin`, className: netProfitChange.className }, netProfitChange, 'profit'],
        ];
        $('anDecisionPulse').innerHTML = decisionPulse.map(([label,value,note,comparison,group]) => `<div class="an-pulse decision-${group}"><div class="an-pulse-label">${label}</div><div class="an-pulse-value">${value}</div><div class="an-decision-pulse-footer"><div class="an-pulse-note ${note.className}">${note.text}</div><span class="an-pulse-compare ${comparison.className}">${comparison.text}</span></div></div>`).join('');
        const health = [
            ['Completion rate', Number(current.completion_rate || 0), false, `${Number(current.completed_count || 0).toLocaleString('id-ID')} selesai`],
            ['Cancellation', Number(current.cancel_rate || 0), true, `${Number(current.cancelled_count || 0).toLocaleString('id-ID')} dibatalkan`],
            ['Data readiness', readyRate, false, `${Number(quality.ready || 0).toLocaleString('id-ID')} siap profit · ${Number(quality.waiting || 0).toLocaleString('id-ID')} menunggu selesai`],
            ['Profit margin', Math.max(0, Number(current.profit_margin || 0)), false, `${Number(current.profit_margin || 0).toFixed(1)}% operasional`],
        ];
        $('anHealthList').innerHTML = health.map(([label,value,inverse,note]) => `<div class="an-health-row"><span>${label}<small>${note}</small></span><div class="an-health-track"><span class="${healthClass(value,inverse)}" style="width:${Math.min(100, Math.max(0, inverse ? 100 - value : value))}%"></span></div><strong>${Number(value || 0).toFixed(1)}%</strong></div>`).join('');
        const alerts = [];
        if (Number(quality.incomplete || 0) + Number(quality.unknown || 0) > 0) alerts.push(['warn','bi-clipboard2-x','Data finansial belum lengkap',`${(Number(quality.incomplete || 0) + Number(quality.unknown || 0)).toLocaleString('id-ID')} order belum siap dihitung profit`,'Audit data']);
        if (Number(current.cancel_rate || 0) >= 10) alerts.push(['bad','bi-x-octagon','Cancellation tinggi',`${Number(current.cancel_rate).toFixed(1)}% dari order masuk`,'Review operasional']);
        else if (Number(current.cancel_rate || 0) >= 5) alerts.push(['warn','bi-exclamation-triangle','Cancellation perlu dipantau',`${Number(current.cancel_rate).toFixed(1)}% dari order masuk`,'Monitor']);
        if (Number(current.profit_margin || 0) < 10) alerts.push(['bad','bi-graph-down-arrow','Margin operasional rendah',`Margin saat ini ${Number(current.profit_margin || 0).toFixed(1)}%`,'Review pricing']);
        if (Number(summary?.changes?.operating_profit || 0) < 0) alerts.push(['warn','bi-arrow-down-right','Laba turun dari periode lalu',`${delta('operating_profit').text}`,'Analisis biaya']);
        if (!alerts.length) alerts.push(['','bi-check2-circle','Tidak ada alert kritis','Performa dan kualitas data berada dalam batas aman','—']);
        $('anAlerts').innerHTML = alerts.map(([level,icon,title,note,action]) => `<div class="an-alert ${level}"><span class="an-alert-icon"><i class="bi ${icon}"></i></span><div><div class="an-alert-title">${title}</div><div class="an-alert-note">${note}</div></div><span class="an-alert-action">${action}</span></div>`).join('');
        const maxStore = Math.max(...topStores.map(store => Number(store.gross_sales || 0)), 1);
        $('anTopStores').innerHTML = topStores.length ? topStores.map(store => `<div class="an-contribution-row"><div><div class="an-contribution-name">${esc(store.store_name || 'Tanpa toko')}</div><div class="an-contribution-meta">${Number(store.order_count || 0).toLocaleString('id-ID')} order financial-ready · profit ${money(store.operating_profit)}</div><div class="an-contribution-bar"><span style="width:${Math.max(4, Number(store.gross_sales || 0) / maxStore * 100)}%"></span></div></div><div class="an-contribution-value">${money(store.gross_sales)}</div></div>`).join('') : '<div class="an-empty">Belum ada kontribusi toko.</div>';
        const settledGross = Number(current.cash_order_revenue ?? current.cash_gross_sales ?? 0);
        const unsettledGross = Number(current.cash_unsettled_order_revenue ?? Math.max(0, Number(current.gmv || 0) - settledGross));
        const totalOmzet = settledGross + unsettledGross || Number(current.gmv || 0);
        const settledPct = totalOmzet > 0 ? settledGross / totalOmzet * 100 : 0;
        const unsettledPct = totalOmzet > 0 ? unsettledGross / totalOmzet * 100 : 0;
        const economicsBase = totalOmzet || cashPayout;
        const cashEconomicsBase = cashPayout;
        const feeEconomicsBase = Number(current.cash_order_revenue || 0);
        const rawHppTotal = Number(current.hpp_total ?? current.hpp ?? 0);
        const rawHppSettled = Number(current.hpp_settled ?? 0);
        const rawHppUnsettled = Number(current.hpp_unsettled ?? Math.max(0, rawHppTotal - rawHppSettled));
        const hppReturnRefund = Number(current.hpp_return_refund || 0);
        const hppReturnRefundSettled = Number(current.hpp_return_refund_settled || 0);
        const hppReturnRefundUnsettled = Number(current.hpp_return_refund_unsettled || 0);
        const hppSettled = Math.max(0, rawHppSettled);
        const hppUnsettled = Math.max(0, rawHppUnsettled);
        const hppTotal = hppSettled + hppUnsettled || rawHppTotal;
        const hppSettledPct = hppTotal > 0 ? hppSettled / hppTotal * 100 : 0;
        const hppUnsettledPct = hppTotal > 0 ? hppUnsettled / hppTotal * 100 : 0;
        const hppReturnRefundPct = hppTotal > 0 ? hppReturnRefund / hppTotal * 100 : 0;
        const economics = [
            ['Omzet', totalOmzet, 100, '#16a34a'],
            ['Return / refund', returnRefundAmount, economicsBase ? returnRefundAmount / economicsBase * 100 : 0, '#dc2626'],
            ['Fee marketplace actual · omzet marketplace', actualCashFee, feeEconomicsBase ? actualCashFee / feeEconomicsBase * 100 : 0, '#d97706'],
            ['Affiliate / AMS actual · omzet cair', actualAffiliateFee, cashEconomicsBase ? actualAffiliateFee / cashEconomicsBase * 100 : 0, '#a855f7'],
            ['HPP', hppTotal, hppTotal > 0 ? 100 : 0, '#64748b'],
            ['Biaya iklan incl. PPN 11%', Number(current.ad_cost || 0), economicsBase ? Number(current.ad_cost || 0) / economicsBase * 100 : 0, '#dc2626'],
        ];
        $('anEconomics').innerHTML = economics.map(([label,amount,rate,color], index) => { const isOmzet = index === 0; const isReturn = index === 1; const isFee = index === 2; const isAffiliate = index === 3; const isHpp = index === 4; const interactive = isOmzet || isReturn || isFee; const progress = isOmzet ? `<div class="an-omzet-progress"><span class="an-omzet-progress-settled" style="width:${settledPct}%"></span><span class="an-omzet-progress-unsettled" style="width:${unsettledPct}%"></span></div><div class="an-omzet-progress-meta"><span>Cair ${money(settledGross)}</span><span>Belum cair ${money(unsettledGross)}</span></div>` : (isHpp ? `<div class="an-omzet-progress"><span class="an-omzet-progress-settled" style="width:${hppSettledPct}%"></span><span class="an-omzet-progress-unsettled" style="width:${hppUnsettledPct}%"></span><span class="an-hpp-progress-return" style="width:${Math.min(100, Math.max(0, hppReturnRefundPct))}%"></span></div><div class="an-omzet-progress-meta an-hpp-progress-meta"><span>HPP cair ${money(hppSettled)} · ${hppSettledPct.toFixed(1)}%</span><span>HPP belum cair ${money(hppUnsettled)} · ${hppUnsettledPct.toFixed(1)}%</span><span>Return/refund ${money(hppReturnRefund)} · ${hppReturnRefundPct.toFixed(1)}%</span></div>` : `<div class="an-contribution-bar"><span style="width:${Math.min(100, Math.max(0, rate))}%;background:${color}"></span></div>`); const actionAttrs = isOmzet ? 'role="button" tabindex="0" aria-controls="cashOrdersModal" aria-label="Lihat status pencairan order" data-open-cash-orders' : (isReturn ? 'role="button" tabindex="0" aria-controls="returnOrdersModal" aria-label="Lihat return dan pengiriman gagal" data-open-return-orders' : (isFee ? 'role="button" tabindex="0" aria-controls="cashOrdersModal" aria-label="Lihat rincian fee marketplace" data-open-fee-orders' : '')); const actionHint = interactive ? '<span class="an-economics-action">Lihat detail</span>' : ''; const basisLabel = isFee ? 'omzet order' : (isAffiliate ? 'omzet cair' : 'total omzet'); return `<div class="an-contribution-row an-economics-row ${interactive ? 'an-economics-clickable' : ''}" ${actionAttrs}><div class="an-economics-copy"><div class="an-contribution-name">${label}</div>${progress}</div><div class="an-contribution-value an-economics-value">${money(amount)}<span class="an-table-subline">${isOmzet ? 'total omzet' : isHpp ? '100.0% dari total HPP' : `${rate.toFixed(1)}% dari ${basisLabel}`}${actionHint}</span></div></div>`; }).join('');
    }
