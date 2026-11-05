    const dateTime = value => {
        if (!value) return '—';
        const raw = String(value);
        const date = /^\d{10,13}$/.test(raw) ? new Date(Number(raw) * (raw.length === 10 ? 1000 : 1)) : new Date(raw.replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('id-ID', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'});
    };
    const cashStatus = value => String(value || '—').replace(/_/g, ' ').toLowerCase();
    const cashStatIcon = (label, tone) => {
        const text = String(label || '').toLowerCase();
        if (text.includes('refund') || text.includes('adjustment')) return 'bi-arrow-counterclockwise';
        if (text.includes('affiliate') || text.includes('ams')) return 'bi-people';
        if (text.includes('pajak') || text.includes('tax')) return 'bi-percent';
        if (text.includes('ongkir') || text.includes('logistik') || text.includes('premi') || text.includes('asuransi')) return 'bi-truck';
        if (text.includes('fee') || text.includes('komisi') || text.includes('administrasi') || text.includes('layanan') || text.includes('proses')) return 'bi-receipt';
        if (text.includes('payout') || text.includes('cair')) return 'bi-wallet2';
        if (text.includes('omzet') || text.includes('revenue') || text.includes('gross sales') || text.includes('net sales')) return 'bi-graph-up-arrow';
        return tone === 'accent' ? 'bi-wallet2' : 'bi-grid-1x2';
    };
    const cashStatNoteIcon = tone => ({
        primary: 'bi-arrow-up-right',
        accent: 'bi-graph-up-arrow',
        settled: 'bi-check2',
        fee: 'bi-receipt',
        shipping: 'bi-truck',
        tax: 'bi-percent',
    }[tone] || 'bi-dash');
    const cashStat = (label, value, note = '', tone = 'neutral') => `<article class="ds-panel ds-kpi an-modal-stat an-modal-stat-${tone}" aria-label="${esc(label)}: ${esc(value)}"><small>${esc(label)}</small><strong>${esc(value)}</strong><p class="an-modal-stat-note an-modal-stat-note-${tone}"><i class="bi ${cashStatNoteIcon(tone)}" aria-hidden="true"></i> ${esc(note || 'Periode aktif')}</p></article>`;
    const cashSettlementTabs = ['all', 'settled', 'unsettled', 'shipped', 'warehouse', 'confirm', 'cancelled', 'return_refund'];
    const cashFeeDetail = (row, label, value, className = '') => `<span>${esc(label)}<strong class="${className}">${money(value)}</strong></span>`;
    const canonicalFeeDetails = row => {
        const breakdown = Array.isArray(row?.fee_breakdown) ? row.fee_breakdown : [];
        if (!breakdown.length) return '';
        return breakdown.map(item => {
            const tone = item.category === 'shipping' ? 'shipping' : (item.category === 'tax' ? 'tax' : (item.code === 'affiliate_fee' ? 'affiliate' : 'fee'));
            return cashFeeDetail(row, item.label || item.code || 'Biaya', item.amount, tone);
        }).join('');
    };
    const paymentLabel = value => {
        const raw = String(value || '').trim();
        if (!raw) return 'Tidak tersedia';
        const normalized = raw.toUpperCase().replace(/[\s-]+/g, '_');
        const labels = {
            COD: 'COD',
            CASH_ON_DELIVERY: 'COD',
            NON_COD: 'Non-COD',
            BANK_TRANSFER: 'Transfer bank',
            TRANSFER: 'Transfer bank',
            VIRTUAL_ACCOUNT: 'Virtual account',
            CREDIT_CARD: 'Kartu kredit',
            DEBIT_CARD: 'Kartu debit',
            E_WALLET: 'E-wallet',
        };
        return labels[normalized] || raw.replace(/_/g, ' ').toLowerCase().replace(/\b\w/g, letter => letter.toUpperCase());
    };

    const cashTabAmount = (settlement, summary) => {
        if (!summary) return null;
        if (settlement === 'settled') return Number(summary.cash_payout || 0);
        return Number(summary.cash_gross_sales ?? summary.cash_order_revenue ?? 0);
    };

    function renderCashTabSummaries() {
        document.querySelectorAll('[data-cash-settlement], [data-revenue-settlement]').forEach(button => {
            const settlement = button.dataset.cashSettlement || button.dataset.revenueSettlement;
            const summary = cashTabSummaries[settlement];
            const element = button.querySelector('[data-cash-tab-kpi], [data-revenue-tab-kpi]');
            if (!element) return;
            if (!summary) {
                element.textContent = 'Memuat…';
                element.classList.add('is-loading');
                return;
            }
            const count = Number(summary.cash_order_count || 0).toLocaleString('id-ID');
            const amount = money(cashTabAmount(settlement, summary));
            element.textContent = `${count} order · ${amount}`;
            element.classList.remove('is-loading');
        });
    }

    function syncRevenueOrdersView() {
        const sourceSummary = $('cashOrdersSummary');
        const sourceBody = $('cashOrdersBody');
        const sourceSubtitle = $('cashOrdersSubtitle');
        const sourcePrev = $('cashOrdersPrev');
        const sourceNext = $('cashOrdersNext');
        const targetSummary = $('revenueOrdersSummary');
        const targetBody = $('revenueOrdersBody');
        if (sourceSummary && targetSummary) targetSummary.innerHTML = sourceSummary.innerHTML;
        if (sourceBody && targetBody) targetBody.innerHTML = sourceBody.innerHTML;
        const feeSummary = $('feeOrdersSummary');
        const feeBody = $('feeOrdersBody');
        if (sourceSummary && feeSummary) feeSummary.innerHTML = sourceSummary.innerHTML;
        if (sourceBody && feeBody) feeBody.innerHTML = sourceBody.innerHTML;
        const targetSubtitle = $('revenueOrdersSubtitle');
        if (sourceSubtitle && targetSubtitle) targetSubtitle.textContent = sourceSubtitle.textContent;
        const feeSubtitle = $('feeOrdersSubtitle');
        if (sourceSubtitle && feeSubtitle) feeSubtitle.textContent = sourceSubtitle.textContent;
        ['Page', 'PageMeta'].forEach(suffix => {
            const target = $('revenueOrders' + suffix);
            const source = $('cashOrders' + suffix);
            if (source && target) target.textContent = source.textContent;
            const feeTarget = $('feeOrders' + suffix);
            if (source && feeTarget) feeTarget.textContent = source.textContent;
        });
        const targetPrev = $('revenueOrdersPrev');
        const targetNext = $('revenueOrdersNext');
        if (sourcePrev && targetPrev) targetPrev.disabled = sourcePrev.disabled;
        if (sourceNext && targetNext) targetNext.disabled = sourceNext.disabled;
        const feePrev = $('feeOrdersPrev');
        const feeNext = $('feeOrdersNext');
        if (sourcePrev && feePrev) feePrev.disabled = sourcePrev.disabled;
        if (sourceNext && feeNext) feeNext.disabled = sourceNext.disabled;
        document.querySelectorAll('[data-revenue-settlement]').forEach(button => {
            const active = button.dataset.revenueSettlement === cashSettlement;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
            button.tabIndex = active ? 0 : -1;
        });
        renderCashTabSummaries();
    }

    async function loadCashTabSummaries(requestedSettlements = [cashSettlement]) {
        if (!dateRangeReady()) return;
        const token = ++cashTabSummaryToken;
        renderCashTabSummaries();
        const settlements = [...new Set(requestedSettlements)]
            .filter(settlement => cashSettlementTabs.includes(settlement) && !cashTabSummaries[settlement]);
        if (!settlements.length) return;
        const results = await Promise.allSettled(settlements.map(async settlement => {
            const params = new URLSearchParams({ date_from: from(), date_to: to(), settlement, page: '1', per_page: '10', _ts: Date.now().toString() });
            if (selectedPlatform()) params.set('platform', selectedPlatform());
            if (selectedStore()) params.set('store_id', selectedStore());
            return [settlement, await api('/api/marketplace/analytics-cash-orders?' + params.toString(), { cache: 'no-store' })];
        }));
        if (token !== cashTabSummaryToken) return;
        results.forEach(result => {
            if (result.status === 'fulfilled') {
                const [settlement, payload] = result.value;
                cashTabSummaries[settlement] = payload?.summary || {};
            }
        });
        renderCashTabSummaries();
        if (cashPayload) {
            renderCashOrders();
        }
    }

    function renderFeeAnalysis(payload) {
        const typeRows = Array.isArray(payload?.fee_by_type) ? payload.fee_by_type : [];
        const storeRows = Array.isArray(payload?.fee_by_store) ? payload.fee_by_store : [];
        const empty = (title, copy) => stateMarkup('empty', title, copy);
        const burdenBadge = burden => `<span class="an-fee-burden-badge an-fee-burden-badge-${burden}"><i class="bi ${burden === 'platform' ? 'bi-building-check' : 'bi-receipt'}" aria-hidden="true"></i>${burden === 'platform' ? 'Platform' : 'Seller'}</span>`;
        const shippingCodes = ['shipping_cost', 'shipping_subsidy'];
        const shippingRows = typeRows.filter(row => shippingCodes.includes(row.code));
        const otherRows = typeRows.filter(row => row.code === 'other_fee');
        const mainTypeRows = typeRows.filter(row => !shippingCodes.includes(row.code) && row.code !== 'other_fee');
        const feeCompositionTotal = typeRows.reduce((sum, row) => sum + n(row.amount), 0);
        const sellerFeeTotal = typeRows.filter(row => row.burden !== 'platform').reduce((sum, row) => sum + n(row.amount), 0);
        const otherFeeTotal = otherRows.reduce((sum, row) => sum + n(row.amount), 0);
        const compositionPercent = amount => feeCompositionTotal > 0 ? `${(n(amount) / feeCompositionTotal * 100).toFixed(1)}%` : '—';
        const typeTable = mainTypeRows.length
            ? `<div class="an-table-wrap"><table class="an-table an-fee-analysis-table" aria-label="Fee berdasarkan tipe"><thead><tr><th>Fee type</th><th>Beban</th><th>Count</th><th>Komposisi</th><th>Amount</th></tr></thead><tbody>${[...mainTypeRows].sort((a, b) => (a.burden === 'platform') - (b.burden === 'platform') || n(b.amount) - n(a.amount)).map(row => `<tr><td data-label="Fee type"><strong class="an-fee-type-name">${esc(row.label || '—')}</strong></td><td data-label="Beban">${burdenBadge(row.burden === 'platform' ? 'platform' : 'seller')}</td><td data-label="Count">${Number(row.count || 0).toLocaleString('id-ID')}</td><td data-label="Komposisi" class="an-fee-composition-percent">${compositionPercent(row.amount)}</td><td data-label="Amount" class="an-fee-analysis-amount">${money(row.amount)}</td></tr>`).join('')}</tbody></table></div>`
            : empty('Belum ada tipe fee', 'Tidak ada fee pada periode dan filter aktif.');
        const shippingMetric = (code, title, burden, icon) => {
            const row = shippingRows.find(item => item.code === code);
            const amount = n(row?.amount);
            return `<article class="an-fee-shipping-metric an-fee-shipping-metric-${burden}"><div class="an-fee-shipping-metric-head"><span class="an-fee-shipping-icon" aria-hidden="true"><i class="bi ${icon}"></i></span><div><strong>${title}</strong><small>${burden === 'platform' ? 'Dibebankan ke platform' : 'Dibebankan ke seller'} · ${Number(row?.count || 0).toLocaleString('id-ID')} komponen</small></div></div><b>${money(amount)}<small>${compositionPercent(amount)} dari total fee</small></b></article>`;
        };
        const shippingSection = shippingRows.length
            ? `<section class="an-fee-shipping-section" aria-label="Ongkir dan logistik"><header><div><span class="an-fee-analysis-kicker">Logistics cost</span><strong>Ongkir &amp; logistik</strong><small>Dipisahkan dari komposisi fee marketplace untuk membaca beban pengiriman dengan lebih jelas.</small></div><span class="an-fee-shipping-chip"><i class="bi bi-truck" aria-hidden="true"></i> ${shippingRows.length} komponen</span></header><div class="an-fee-shipping-grid">${shippingMetric('shipping_cost', 'Biaya kirim ke jasa kirim', 'seller', 'bi-truck')}${shippingMetric('shipping_subsidy', 'Subsidi / potongan ongkir', 'platform', 'bi-building-check')}</div></section>`
            : '';
        const otherSection = `<section class="an-fee-other-section" aria-label="Biaya lainnya"><header><div><span class="an-fee-analysis-kicker">Other cost</span><strong>Biaya lainnya</strong><small>${otherRows.length ? 'Biaya tambahan di luar fee utama dan ongkir.' : 'Belum ada biaya lainnya pada periode dan filter aktif.'}</small></div><span class="an-fee-other-chip"><i class="bi bi-plus-circle" aria-hidden="true"></i> ${otherRows.length} komponen</span></header><div class="an-fee-other-total"><div><span>Total biaya lainnya</span><small>${otherRows.length ? 'Dibebankan ke seller' : 'Tidak ada biaya tambahan tercatat'}</small></div><strong>${money(otherFeeTotal)}<small>${compositionPercent(otherFeeTotal)} dari total fee</small></strong></div></section>`;
        const mergedStores = [...storeRows.reduce((map, row) => {
            const key = String(row.store_id || row.store_name || 'unknown');
            const current = map.get(key) || { store_id: row.store_id, store_name: row.store_name || 'Toko tidak diketahui', count: 0, seller: 0, platform: 0, ads: 0, adSource: 'none' };
            const amount = n(row.amount);
            if (row.cost_type === 'ads') {
                current.ads += amount;
                current.adSource = row.source || 'none';
            } else {
                current.count += Number(row.count || 0);
                current[row.burden === 'platform' ? 'platform' : 'seller'] += amount;
            }
            map.set(key, current);
            return map;
        }, new Map()).values()].sort((a, b) => (b.seller + b.platform + b.ads) - (a.seller + a.platform + a.ads));
        const storeTable = mergedStores.length
            ? `<div class="an-table-wrap"><table class="an-table an-fee-analysis-table an-fee-store-table" aria-label="Fee dan biaya iklan berdasarkan toko"><thead><tr><th>Store</th><th>Count</th><th>Seller fee</th><th>Platform</th><th>Biaya iklan</th><th>Total biaya</th></tr></thead><tbody>${mergedStores.map(row => `<tr><td data-label="Store"><strong class="an-fee-type-name">${esc(row.store_name)}</strong></td><td data-label="Count">${row.count.toLocaleString('id-ID')}</td><td data-label="Seller fee" class="an-fee-analysis-amount">${money(row.seller)}</td><td data-label="Platform" class="an-fee-analysis-amount an-fee-platform-amount">${money(row.platform)}</td><td data-label="Biaya iklan" class="an-fee-analysis-amount an-fee-ads-amount"><span>${money(row.ads)}</span>${row.ads > 0 ? `<small>${row.adSource === 'platform_transaction_fallback' ? 'Mutasi platform' : 'Ads Daily'}</small>` : ''}</td><td data-label="Total biaya" class="an-fee-analysis-amount">${money(row.seller + row.platform + row.ads)}</td></tr>`).join('')}</tbody></table></div>`
            : empty('Belum ada toko', 'Tidak ada fee pada periode dan filter aktif.');

        $('feeByTypeTable')?.replaceChildren();
        if ($('feeByTypeTable')) $('feeByTypeTable').innerHTML = `${typeTable}${shippingSection}${otherSection}`;
        if ($('feeByTypeCount')) $('feeByTypeCount').textContent = `${new Set(mainTypeRows.map(row => row.code)).size.toLocaleString('id-ID')} tipe + ongkir + lainnya`;
        if ($('feeByTypeSellerTotal')) $('feeByTypeSellerTotal').textContent = money(sellerFeeTotal);
        if ($('feeByTypeSellerPercent')) $('feeByTypeSellerPercent').textContent = `${compositionPercent(sellerFeeTotal)} dari total fee`;
        if ($('feeByStoreTable')) $('feeByStoreTable').innerHTML = storeTable;
        if ($('feeByStoreCount')) $('feeByStoreCount').textContent = `${new Set(storeRows.map(row => row.store_id)).size.toLocaleString('id-ID')} toko`;
        if ($('feeByOrderCount')) $('feeByOrderCount').textContent = `${Number(payload?.meta?.total || 0).toLocaleString('id-ID')} order`;
    }

    function renderCashOrders() {
        const payload = cashPayload || {};
        const aggregate = payload.summary || {};
        const meta = payload.meta || {};
        const rows = Array.isArray(payload.data) ? payload.data : [];
        const isAll = cashSettlement === 'all';
        const isSettled = cashSettlement === 'settled';
        const isCombined = cashFocus === 'combined';
        const isFeeFocus = cashFocus === 'fees';
        const totalOrders = Number(meta.total || aggregate.cash_order_count || 0).toLocaleString('id-ID');
        const settledOrderCount = cashTabSummaries.settled
            ? Number(cashTabSummaries.settled.cash_order_count || 0).toLocaleString('id-ID')
            : (isSettled ? totalOrders : '—');
        const grossSales = n(aggregate.cash_gross_sales);
        const netSales = n(aggregate.cash_order_revenue);
        const settledNetSales = isSettled ? netSales : n(cashTabSummaries.settled?.cash_order_revenue);
        const netSalesPercent = value => netSales > 0 ? `${(n(value) / netSales * 100).toFixed(1)}% net sales` : '—';
        const settledNetSalesPercent = value => settledNetSales > 0 ? `${(n(value) / settledNetSales * 100).toFixed(1)}% net sales cair` : '—';
        const settlementLabel = row => row.settlement_time ? 'Sudah cair' : 'Belum cair';
        const statusLabel = row => row.status_group_label || cashStatus(row.status);
        const statusTone = row => {
            const group = String(row.status_group || '').toLowerCase();
            if (group === 'return_refund') return 'return';
            if (group === 'cancelled') return 'cancelled';
            if (group === 'completed' && row.settlement_time) return 'settled';
            return 'pending';
        };
        const modeLabel = { all: 'semua pesanan', settled: 'payout sudah cair', shipped: 'order masih dikirim', warehouse: 'disimpan di gudang Shopee', confirm: 'menunggu konfirmasi pembeli', cancelled: 'order dibatalkan', return_refund: 'order return / refund', unsettled: 'dana belum cair' }[cashSettlement] || 'semua pesanan';
        const isTikTokPlatform = selectedPlatform() === 'tiktok';
        const canonicalTotals = Array.isArray(payload.canonical_fee_totals) ? payload.canonical_fee_totals : [];
        const canonicalAmount = code => Number(canonicalTotals.find(item => item.code === code)?.amount || 0);
        const canonicalFeeItems = [
            ['platform_commission', 'Komisi Platform', 'fee'],
            ['dynamic_commission', 'Komisi Dinamis', 'fee'],
            ['program_service_fee', 'Program / layanan', 'fee'],
            ['order_processing_fee', 'Pemrosesan Pesanan', 'fee'],
            ['affiliate_fee', 'Komisi Afiliasi', 'fee'],
            ['shipping_cost', 'Biaya Layanan Logistik', 'shipping'],
            ['shipping_subsidy', 'Subsidi / Potongan Ongkir', 'shipping'],
            ['insurance_fee', 'Premi / Asuransi', 'fee'],
            ['other_fee', 'Biaya Lainnya', 'fee'],
            ['tax', 'Pajak', 'tax'],
        ].map(([code, label, category]) => [code, label, category, canonicalAmount(code)])
            .filter(([, , , amount]) => amount > 0)
            .map(([code, label, category, amount]) => ({ code, label, category, amount }));
        const fallbackFeeItems = [
            ['marketplace_fee', 'Fee marketplace', aggregate.cash_marketplace_fees, 'fee'],
            ['affiliate_fee', 'Komisi Afiliasi / AMS', aggregate.cash_affiliate_fees, 'affiliate'],
        ].filter(([, , amount]) => n(amount) > 0).map(([code, label, amount, category]) => ({ code, label, amount: n(amount), category }));
        const feeItems = canonicalFeeItems.length > 0 ? canonicalFeeItems : fallbackFeeItems;
        const totalFeeAmount = feeItems.reduce((sum, item) => sum + n(item.amount), 0);
        const feeBurdenRows = Array.isArray(payload.fee_by_type) ? payload.fee_by_type : [];
        const sellerFeeAmount = feeBurdenRows.length
            ? feeBurdenRows.filter(item => item.burden !== 'platform').reduce((sum, item) => sum + n(item.amount), 0)
            : totalFeeAmount;
        const platformFeeAmount = feeBurdenRows.filter(item => item.burden === 'platform').reduce((sum, item) => sum + n(item.amount), 0);
        const totalFeePercent = settledNetSales > 0
            ? `${(totalFeeAmount / settledNetSales * 100).toFixed(1)}% net sales cair`
            : '—';
        const canonicalFeeCards = canonicalFeeItems.map(item => cashStat(item.label, money(item.amount), `${settledNetSalesPercent(item.amount)} · ${item.category === 'shipping' ? 'logistik' : 'fee'}`, item.category));
        const feeSummaryCards = canonicalFeeCards.length > 0 ? canonicalFeeCards : (isTikTokPlatform
            ? [
                cashStat('Total fee marketplace', money(aggregate.cash_marketplace_fees), settledNetSalesPercent(aggregate.cash_marketplace_fees), 'fee'),
                cashStat('Rincian fee', 'Per order', `${settledNetSalesPercent(aggregate.cash_marketplace_fees)} · platform · program · proses`, 'neutral'),
            ]
            : [
                cashStat('Administrasi', money(aggregate.cash_commission_fee), netSalesPercent(aggregate.cash_commission_fee), 'fee'),
                cashStat('Layanan / program', money(aggregate.cash_service_fee), netSalesPercent(aggregate.cash_service_fee), 'fee'),
                cashStat('Proses / transaksi', money(aggregate.cash_transaction_fee), netSalesPercent(aggregate.cash_transaction_fee), 'fee'),
                cashStat('Affiliate / AMS', money(aggregate.cash_affiliate_fees), netSalesPercent(aggregate.cash_affiliate_fees), 'neutral'),
            ]);
        $('cashOrdersTitle').textContent = isFeeFocus ? 'Rincian fee marketplace actual' : 'Omzet, pencairan & fee marketplace';
        $('cashOrdersSubtitle').textContent = `${from()} — ${to()} · ${modeLabel} · ${totalOrders} order`;
        $('cashOrdersSummary').innerHTML = isCombined ? [
            cashStat('Gross Sales', money(grossSales), netSalesPercent(grossSales), 'primary'),
            cashStat('Net Sales', money(netSales), netSalesPercent(netSales), 'accent'),
            cashStat('Payout tercatat', money(aggregate.cash_payout), settledNetSalesPercent(aggregate.cash_payout), 'neutral'),
            cashStat('Order cair', settledOrderCount, 'Settlement complete', 'settled'),
            cashStat('Fee marketplace', money(totalFeeAmount), totalFeePercent, 'fee'),
            cashStat('Refund / adjustment', money(aggregate.cash_refund), settledNetSalesPercent(aggregate.cash_refund), 'neutral'),
        ].join('') : isFeeFocus ? [
            cashStat('Fee dibebankan ke seller', money(sellerFeeAmount), settledNetSalesPercent(sellerFeeAmount), 'fee'),
            cashStat('Fee dibebankan ke platform', money(platformFeeAmount), platformFeeAmount > 0 ? settledNetSalesPercent(platformFeeAmount) : 'Tidak ada subsidi platform', 'accent'),
            cashStat('Total fee marketplace', money(totalFeeAmount), totalFeePercent, 'fee'),
            cashStat('Order cair', totalOrders, 'Settlement complete', 'neutral'),
        ].join('') : [
            cashStat('Gross Sales', money(grossSales), netSalesPercent(grossSales), 'primary'),
            cashStat('Net Sales', money(netSales), netSalesPercent(netSales), 'accent'),
            cashStat('Net Sales cair', money(settledNetSales), settledNetSalesPercent(settledNetSales), 'settled'),
            cashStat(isSettled ? 'Payout aktual' : 'Payout tercatat', money(aggregate.cash_payout), settledNetSalesPercent(aggregate.cash_payout), 'neutral'),
            cashStat(isSettled ? 'Fee marketplace actual' : 'Fee marketplace tercatat', money(aggregate.cash_marketplace_fees), settledNetSalesPercent(aggregate.cash_marketplace_fees), 'fee'),
            cashStat('Affiliate / AMS', money(aggregate.cash_affiliate_fees), settledNetSalesPercent(aggregate.cash_affiliate_fees), 'neutral'),
            cashStat('Refund / adjustment', money(aggregate.cash_refund), settledNetSalesPercent(aggregate.cash_refund), 'neutral'),
        ].join('');
        cashTabSummaries[cashSettlement] = aggregate;
        renderCashTabSummaries();
        const sellerFeeItems = feeItems.filter(item => item.code !== 'shipping_subsidy' && item.category !== 'platform');
        const platformFeeItems = feeItems.filter(item => item.code === 'shipping_subsidy' || item.category === 'platform');
        const feeBurdenSection = (title, subtitle, items, tone, icon, emptyLabel) => {
            const total = items.reduce((sum, item) => sum + n(item.amount), 0);
            const rows = items.length
                ? items.map(item => `<div class="an-fee-burden-row"><span>${esc(item.label)}</span><strong>${money(item.amount)}<small>${settledNetSalesPercent(item.amount)}</small></strong></div>`).join('')
                : `<div class="an-fee-burden-empty">${emptyLabel}</div>`;
            return `<section class="an-fee-burden an-fee-burden-${tone}" aria-label="${title}"><div class="an-fee-burden-head"><span class="an-fee-burden-icon" aria-hidden="true"><i class="bi ${icon}"></i></span><div><strong>${title}</strong><small>${subtitle}</small></div><b>${items.length ? money(total) : '—'}</b></div><div class="an-fee-burden-list">${rows}</div></section>`;
        };
        const feeBreakdown = isFeeFocus
            ? `<section class="an-revenue-fee-breakdown" aria-label="Rincian fee marketplace"><div class="an-revenue-fee-breakdown-head"><div><strong>Fee marketplace</strong><small>Basis biaya: Net Sales Cair</small></div><div class="an-revenue-fee-breakdown-total"><strong>${money(totalFeeAmount)}</strong><small>${totalFeePercent}</small></div></div><div class="an-fee-burden-grid">${feeBurdenSection('Ditanggung penjual', `${sellerFeeItems.length} komponen`, sellerFeeItems, 'seller', 'bi-receipt', 'Tidak ada fee penjual')}${feeBurdenSection('Ditanggung platform', `${platformFeeItems.length} komponen`, platformFeeItems, 'platform', 'bi-building-check', 'Tidak ada subsidi platform')}</div></section>`
            : '';
        const renderRows = groupRows => groupRows.map(row => {
            const rowSettled = Boolean(row.settlement_time);
            const payout = rowSettled && n(row.cash_payout) > 0 ? money(row.cash_payout) : (n(row.cash_payout) > 0 ? money(row.cash_payout) : 'Belum tersedia');
            const mappedDetails = canonicalFeeDetails(row) || `${cashFeeDetail(row, 'Administrasi / komisi', row.commission_fee, 'fee')}${cashFeeDetail(row, 'Layanan / program', row.service_fee, 'fee')}${cashFeeDetail(row, 'Proses / transaksi', row.transaction_fee, 'fee')}${cashFeeDetail(row, 'Affiliate / AMS', row.affiliate_fee_raw, 'affiliate')}`;
            return `<tr><td data-label="Order"><div class="an-cash-order">${esc(row.channel_order_id)}</div><span class="an-cash-meta">Order ${esc(dateTime(row.ordered_at))}</span><details class="an-cash-detail"><summary>Rincian mapping fee</summary><div class="an-cash-detail-grid">${mappedDetails}${cashFeeDetail(row, 'Refund / adjustment', row.refund)}${cashFeeDetail(row, 'Total fee', row.total_fees, 'fee')}</div></details></td><td data-label="Toko & status"><div>${esc(row.store_name)}</div><span class="an-cash-status ${statusTone(row)}">${esc(statusLabel(row))}</span><span class="an-cash-meta">Payout: ${settlementLabel(row)}</span></td><td data-label="Pembayaran"><span class="an-cash-payment">${esc(paymentLabel(row.payment_method))}</span></td><td data-label="Nilai order" class="an-cash-money">${money(row.gross_sales)}</td><td data-label="Pembayaran pembeli" class="an-cash-money">${money(row.buyer_payment_amount)}</td><td data-label="Payout" class="an-cash-money ${rowSettled ? 'good' : ''}">${payout}</td><td data-label="Fee marketplace" class="an-cash-money fee">${money(row.marketplace_fee)}</td><td data-label="Affiliate / AMS" class="an-cash-money affiliate">${money(row.affiliate_fee)}</td></tr>`;
        }).join('');
        const renderTable = (groupRows, groupLabel = '') => groupRows.length ? `${groupLabel ? `<div class="an-cash-group-head"><strong>${esc(groupLabel)}</strong><span>${groupRows.length.toLocaleString('id-ID')} order</span></div>` : ''}<div class="an-table-wrap"><table class="an-table an-cash-table" aria-label="Rincian omzet, pencairan, dan fee"><thead><tr><th>Order</th><th>Toko &amp; status</th><th>Metode pembayaran</th><th>${isAll ? 'Nilai order' : 'Omzet order'}</th><th>Pembayaran pembeli</th><th>${isSettled ? 'Omzet cair' : 'Payout tercatat'}</th><th>Fee marketplace</th><th>Affiliate / AMS</th></tr></thead><tbody>${renderRows(groupRows)}</tbody></table></div>` : '';
        const groups = isAll
            ? ['completed', 'warehouse', 'shipped', 'confirm', 'return_refund', 'cancelled', 'other'].map(key => ({ key, label: rows.find(row => row.status_group === key)?.status_group_label || key, rows: rows.filter(row => row.status_group === key) })).filter(group => group.rows.length)
            : [{ key: 'flat', label: '', rows }];
        const table = groups.length ? groups.map(group => `<section class="an-cash-group">${renderTable(group.rows, isAll ? group.label : '')}</section>`).join('') : stateMarkup('empty', 'Belum ada order', 'Tidak ada order pada periode ini.');
        const feeOrderTable = rows.length
            ? `<div class="an-table-wrap"><table class="an-table an-fee-analysis-table an-fee-order-table" aria-label="Fee berdasarkan order"><thead><tr><th>Order</th><th>Toko</th><th>Seller</th><th>Platform</th><th>Total fee</th></tr></thead><tbody>${rows.map(row => {
                const breakdown = Array.isArray(row.fee_breakdown) ? row.fee_breakdown : [];
                const sellerFee = breakdown.filter(item => item.burden !== 'platform').reduce((sum, item) => sum + n(item.amount), 0);
                const platformFee = breakdown.filter(item => item.burden === 'platform').reduce((sum, item) => sum + n(item.amount), 0);
                const fee = sellerFee + platformFee || n(row.total_fees);
                const sellerAmount = sellerFee || Math.max(0, fee - platformFee);
                return `<tr><td data-label="Order"><strong class="an-cash-order">${esc(row.channel_order_id)}</strong><span class="an-cash-meta">Order ${esc(dateTime(row.ordered_at))}</span></td><td data-label="Toko">${esc(row.store_name || 'Tanpa toko')}</td><td data-label="Seller" class="an-fee-analysis-amount">${money(sellerAmount)}</td><td data-label="Platform" class="an-fee-analysis-amount an-fee-platform-amount">${money(platformFee)}</td><td data-label="Total fee" class="an-fee-analysis-amount">${money(fee)}</td></tr>`;
            }).join('')}</tbody></table></div>`
            : stateMarkup('empty', 'Belum ada order', 'Tidak ada order pada periode ini.');
        const body = isFeeFocus
            ? feeOrderTable
            : table;
        $('cashOrdersBody').innerHTML = isFeeFocus ? body : feeBreakdown + body;
        if (isFeeFocus) renderFeeAnalysis(payload);
        const currentPage = Number(meta.current_page || cashPage);
        const lastPage = Number(meta.last_page || 1);
        const total = Number(meta.total || 0);
        const perPage = Number(meta.per_page || 50);
        const firstRow = total > 0 ? ((currentPage - 1) * perPage) + 1 : 0;
        const lastRow = total > 0 ? Math.min(currentPage * perPage, total) : 0;
        $('cashOrdersPage').textContent = `Halaman ${currentPage} / ${lastPage}`;
        $('cashOrdersPageMeta').textContent = total > 0
            ? `${firstRow.toLocaleString('id-ID')}–${lastRow.toLocaleString('id-ID')} dari ${total.toLocaleString('id-ID')} order`
            : 'Tidak ada order pada periode ini';
        $('cashOrdersPrev').disabled = cashLoading || currentPage <= 1;
        $('cashOrdersNext').disabled = cashLoading || currentPage >= lastPage;
        syncRevenueOrdersView();
    }
    function closeCashOrders() {
        cashRequestToken++;
        cashAbortController?.abort();
        cashAbortController = null;
        cashLoading = false;
        $('cashOrdersModal').classList.remove('is-open');
        $('cashOrdersModal').setAttribute('aria-hidden', 'true');
        document.body.classList.remove('an-modal-open');
    }
    function setCashSettlementTab(value) {
        cashSettlement = ['all', 'settled', 'shipped', 'warehouse', 'confirm', 'cancelled', 'return_refund', 'unsettled'].includes(value) ? value : 'settled';
        document.querySelectorAll('[data-cash-settlement], [data-revenue-settlement]').forEach(button => {
            const settlement = button.dataset.cashSettlement || button.dataset.revenueSettlement;
            const active = settlement === cashSettlement;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
            if (button.dataset.revenueSettlement) button.tabIndex = active ? 0 : -1;
        });
    }
    async function loadCashOrders() {
        if (!dateRangeReady()) return;
        const requestToken = ++cashRequestToken;
        cashAbortController?.abort();
        const controller = new AbortController();
        cashAbortController = controller;
        cashLoading = true;
        $('cashOrdersBody').innerHTML = stateMarkup('loading', 'Memuat order…');
        $('revenueOrdersBody')?.setAttribute('aria-busy', 'true');
        $('cashOrdersSummary').innerHTML = stateMarkup('loading', 'Memuat ringkasan…');
        $('cashOrdersPage').textContent = 'Memuat…';
        $('cashOrdersPageMeta').textContent = 'Mengambil data terbaru';
        $('cashOrdersPrev').disabled = true;
        $('cashOrdersNext').disabled = true;
        syncRevenueOrdersView();
        try {
            const params = new URLSearchParams({ date_from: from(), date_to: to(), settlement: cashSettlement, page: String(cashPage), per_page: '50', _ts: Date.now().toString() });
            if (selectedPlatform()) params.set('platform', selectedPlatform());
            if (selectedStore()) params.set('store_id', selectedStore());
            const payload = await api('/api/marketplace/analytics-cash-orders?' + params.toString(), { cache: 'no-store', signal: controller.signal });
            if (requestToken !== cashRequestToken) return;
            cashPayload = payload;
            renderCashOrders();
        } catch (e) {
            if (requestToken !== cashRequestToken || isAbortError(e)) return;
            console.error('Cash order detail load failed', e);
            $('cashOrdersSummary').innerHTML = stateMarkup('error', 'Ringkasan order cair gagal dimuat');
            $('cashOrdersBody').innerHTML = stateMarkup('error', 'Detail order cair gagal dimuat');
            $('cashOrdersPage').textContent = 'Gagal memuat';
            $('cashOrdersPageMeta').textContent = 'Coba lagi';
            syncRevenueOrdersView();
        } finally {
            if (requestToken !== cashRequestToken) return;
            cashLoading = false;
            cashAbortController = null;
            $('revenueOrdersBody')?.setAttribute('aria-busy', 'false');
            if (cashPayload) renderCashOrders();
            else syncRevenueOrdersView();
        }
    }
    function openCashOrders() {
        cashFocus = 'payout';
        $('cashOrdersModal').classList.add('is-open');
        $('cashOrdersModal').setAttribute('aria-hidden', 'false');
        document.body.classList.add('an-modal-open');
        cashPage = 1;
        cashPayload = null;
        cashTabSummaries = {};
        cashTabSummaryToken++;
        setCashSettlementTab('all');
        $('cashOrdersSubtitle').textContent = `${from()} — ${to()} · semua status`;
        loadCashOrders();
        loadCashTabSummaries(cashSettlementTabs);
    }
    function openFeeOrders() {
        closeReturnOrders();
        cashFocus = 'fees';
        $('cashOrdersModal').classList.add('is-open');
        $('cashOrdersModal').setAttribute('aria-hidden', 'false');
        document.body.classList.add('an-modal-open');
        cashPage = 1;
        cashPayload = null;
        cashTabSummaries = {};
        cashTabSummaryToken++;
        setCashSettlementTab('settled');
        $('cashOrdersSubtitle').textContent = `${from()} — ${to()} · settlement complete`;
        loadCashOrders();
        loadCashTabSummaries(cashSettlementTabs);
    }
    function renderReturnOrders() {
        const payload = returnPayload || {};
        const meta = payload.meta || {};
        const aggregate = payload.summary || {};
        const rows = Array.isArray(payload.data) ? payload.data : [];
        const isFailed = returnType === 'failed_delivery';
        $('returnOrdersSubtitle').textContent = `${from()} — ${to()} · ${isFailed ? 'exception pengiriman eksplisit' : 'data return / refund tersimpan'} · ${Number(meta.total || 0).toLocaleString('id-ID')} kasus`;
        $('returnOrdersSummary').innerHTML = [
            cashStat('Jumlah kasus', Number(aggregate.case_count || 0).toLocaleString('id-ID')),
            cashStat('Nilai kasus', money(aggregate.amount)),
            cashStat('Kategori', isFailed ? 'Pengiriman gagal' : 'Return / refund'),
            cashStat('Basis data', isFailed ? 'RTS / status gagal' : 'Marketplace returns'),
        ].join('');
        $('returnOrdersBody').innerHTML = rows.length ? `<div class="an-table-wrap"><table class="an-table an-cash-table an-exception-table" aria-label="Daftar return dan refund"><thead><tr><th>Referensi</th><th>Order &amp; toko</th><th>Status</th><th>Nilai kasus</th><th>Waktu</th></tr></thead><tbody>${rows.map(row => { const kindLabel = row.kind === 'failed_delivery' ? 'Pengiriman gagal' : (row.kind === 'refund' ? 'Refund' : 'Return + refund'); const kindClass = row.kind === 'failed_delivery' ? '' : 'return'; const statusClass = row.kind === 'failed_delivery' ? 'failed' : 'return'; return `<tr><td data-label="Referensi"><div class="an-cash-order">${esc(row.reference)}</div><span class="an-exception-kind ${kindClass}">${kindLabel}</span><span class="an-cash-meta">${esc(row.reason || '—')}</span></td><td data-label="Order &amp; toko"><div class="an-cash-order">${esc(row.order_sn)}</div><span class="an-cash-meta">${esc(row.store_name)}</span></td><td data-label="Status"><span class="an-cash-status ${statusClass}">${esc(cashStatus(row.status))}</span>${row.tracking_number ? `<span class="an-cash-meta">AWB ${esc(row.tracking_number)}</span>` : ''}</td><td data-label="Nilai kasus" class="an-cash-money fee">${money(row.amount)}</td><td data-label="Waktu"><span class="an-cash-meta">${esc(dateTime(row.event_time))}</span></td></tr>`; }).join('')}</tbody></table></div>` : stateMarkup('empty', 'Belum ada data exception', `Belum ada data ${isFailed ? 'pengiriman gagal eksplisit' : 'return / refund'} pada periode ini.`);
        const currentPage = Number(meta.current_page || returnPage);
        const lastPage = Number(meta.last_page || 1);
        $('returnOrdersPage').textContent = `Halaman ${currentPage} / ${lastPage}`;
        $('returnOrdersPrev').disabled = returnLoading || currentPage <= 1;
        $('returnOrdersNext').disabled = returnLoading || currentPage >= lastPage;
    }
    function closeReturnOrders() {
        returnRequestToken++;
        returnAbortController?.abort();
        returnAbortController = null;
        returnLoading = false;
        $('returnOrdersModal').classList.remove('is-open');
        $('returnOrdersModal').setAttribute('aria-hidden', 'true');
        document.body.classList.remove('an-modal-open');
    }
    function setReturnTypeTab(value) {
        returnType = value === 'failed_delivery' ? 'failed_delivery' : 'return_refund';
        document.querySelectorAll('[data-return-type]').forEach(button => {
            const active = button.dataset.returnType === returnType;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }
    async function loadReturnOrders() {
        if (!dateRangeReady()) return;
        const requestToken = ++returnRequestToken;
        returnAbortController?.abort();
        const controller = new AbortController();
        returnAbortController = controller;
        returnLoading = true;
        $('returnOrdersBody').innerHTML = stateMarkup('loading', 'Memuat data return…');
        $('returnOrdersSummary').innerHTML = stateMarkup('loading', 'Memuat ringkasan…');
        $('returnOrdersPrev').disabled = true;
        $('returnOrdersNext').disabled = true;
        try {
            const params = new URLSearchParams({ date_from: from(), date_to: to(), type: returnType, page: String(returnPage), per_page: '50', _ts: Date.now().toString() });
            if (selectedPlatform()) params.set('platform', selectedPlatform());
            if (selectedStore()) params.set('store_id', selectedStore());
            const payload = await api('/api/marketplace/analytics-return-orders?' + params.toString(), { cache: 'no-store', signal: controller.signal });
            if (requestToken !== returnRequestToken) return;
            returnPayload = payload;
            renderReturnOrders();
        } catch (e) {
            if (requestToken !== returnRequestToken || isAbortError(e)) return;
            console.error('Return detail load failed', e);
            $('returnOrdersSummary').innerHTML = stateMarkup('error', 'Ringkasan return gagal dimuat');
            $('returnOrdersBody').innerHTML = stateMarkup('error', 'Data return gagal dimuat');
            $('returnOrdersPage').textContent = '—';
        } finally {
            if (requestToken !== returnRequestToken) return;
            returnLoading = false;
            returnAbortController = null;
            if (returnPayload) renderReturnOrders();
        }
    }
    function openReturnOrders() {
        closeCashOrders();
        $('returnOrdersModal').classList.add('is-open');
        $('returnOrdersModal').setAttribute('aria-hidden', 'false');
        document.body.classList.add('an-modal-open');
        returnPage = 1;
        returnPayload = null;
        setReturnTypeTab('return_refund');
        $('returnOrdersSubtitle').textContent = `${from()} — ${to()} · data operasional`;
        loadReturnOrders();
    }

    function renderStores() {
        renderStorePerformance({ pulseId: 'anStorePulse', costPulseId: 'anStoreCostPulse', ledgerBodyId: 'storeBody' });
    }
    function renderCosts() {
        renderMarketplaceCosts('costBody');
    }
