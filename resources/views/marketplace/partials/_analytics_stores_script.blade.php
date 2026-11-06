    const dateTime = value => {
        if (!value) return '—';
        const raw = String(value);
        const date = /^\d{10,13}$/.test(raw) ? new Date(Number(raw) * (raw.length === 10 ? 1000 : 1)) : new Date(raw.replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('id-ID', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'});
    };
    const cashStatus = value => String(value || '—').replace(/_/g, ' ').toLowerCase();
    const returnRefundStatusLabel = status => ({
        return_requested: 'Return requested',
        refunded: 'Refund',
        settlement_reversal: 'Payout reversal',
    }[String(status || '').toLowerCase()] || 'Refund');
    const signedMoney = value => n(value) < 0 ? `−${money(Math.abs(n(value)))}` : money(value);
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
        const shippingCodes = ['shipping_cost', 'shipping_subsidy', 'shipping_other', 'shipping_difference'];
        const shippingRows = typeRows.filter(row => shippingCodes.includes(row.code));
        const otherRows = typeRows.filter(row => row.code === 'other_fee');
        const platformRows = typeRows.filter(row => row.burden === 'platform');
        const sellerTypeRows = typeRows.filter(row => row.burden === 'seller'
            && row.code !== 'other_fee'
            // Keep TikTok's seller logistics in the seller composition. The
            // derived shipping difference remains only in the logistics view.
            && (!shippingCodes.includes(row.code) || row.code === 'shipping_cost'));
        const netSalesTotal = n(payload?.summary?.cash_order_revenue);
        const sellerFeeTotal = typeRows.filter(row => row.burden === 'seller').reduce((sum, row) => sum + n(row.amount), 0);
        const platformFeeTotal = platformRows.reduce((sum, row) => sum + n(row.amount), 0);
        const otherFeeTotal = otherRows.reduce((sum, row) => sum + n(row.amount), 0);
        const netSalesPercent = amount => netSalesTotal > 0 ? `${(n(amount) / netSalesTotal * 100).toFixed(1)}%` : '—';
        const typeTable = sellerTypeRows.length
            ? `<div class="an-table-wrap"><table class="an-table an-fee-analysis-table" aria-label="Fee seller berdasarkan tipe"><thead><tr><th>Fee seller</th><th>Beban</th><th>Count</th><th>% Net Sales</th><th>Amount</th></tr></thead><tbody>${[...sellerTypeRows].sort((a, b) => n(b.amount) - n(a.amount)).map(row => `<tr><td data-label="Fee seller"><strong class="an-fee-type-name">${esc(row.label || '—')}</strong></td><td data-label="Beban">${burdenBadge('seller')}</td><td data-label="Count">${Number(row.count || 0).toLocaleString('id-ID')}</td><td data-label="% Net Sales" class="an-fee-composition-percent">${netSalesPercent(row.amount)}</td><td data-label="Amount" class="an-fee-analysis-amount">${money(row.amount)}</td></tr>`).join('')}</tbody></table></div>`
            : empty('Belum ada tipe fee', 'Tidak ada fee pada periode dan filter aktif.');
        const platformSection = `<div class="an-fee-platform-content" aria-label="Rincian fee platform"><div class="an-fee-platform-summary"><div><span>Total fee platform</span><small>Biaya dan subsidi yang ditanggung platform</small></div><strong>${money(platformFeeTotal)}<small>${netSalesPercent(platformFeeTotal)} dari net sales</small></strong></div>${platformRows.length ? `<div class="an-fee-platform-list">${[...platformRows].sort((a, b) => n(b.amount) - n(a.amount)).map(row => `<div><span>${esc(row.label || 'Fee platform')}<small>${Number(row.count || 0).toLocaleString('id-ID')} komponen</small></span><strong>${money(row.amount)}<small>${netSalesPercent(row.amount)} dari net sales</small></strong></div>`).join('')}</div>` : `<div class="an-fee-platform-empty">Tidak ada biaya platform pada periode dan filter aktif.</div>`}</div>`;
        const shippingMetric = row => {
            const burden = row.burden === 'platform' ? 'platform' : (row.burden === 'logistics' ? 'logistics' : 'seller');
            const icon = burden === 'platform' ? 'bi-building-check' : 'bi-truck';
            const amount = n(row?.amount);
            const burdenLabel = burden === 'platform' ? 'Dibebankan ke platform' : (burden === 'logistics' ? 'Biaya operasional logistik · bukan fee seller' : 'Dibebankan ke seller');
            return `<article class="an-fee-shipping-metric an-fee-shipping-metric-${burden}"><div class="an-fee-shipping-metric-head"><span class="an-fee-shipping-icon" aria-hidden="true"><i class="bi ${icon}"></i></span><div><strong>${esc(row?.label || 'Biaya logistik')}</strong><small>${burdenLabel} · ${Number(row?.count || 0).toLocaleString('id-ID')} komponen</small></div></div><b>${money(amount)}<small>${netSalesPercent(amount)} dari net sales</small></b></article>`;
        };
        const shippingTotal = shippingRows.filter(row => row.code !== 'shipping_difference').reduce((sum, row) => sum + n(row.amount), 0);
        const shippingSection = `<div class="an-fee-shipping-content" aria-label="Ongkir dan logistik"><div class="an-fee-shipping-summary"><div><span>Total ongkir &amp; logistik</span><small>Biaya kirim dan subsidi; selisih ongkir ditampilkan terpisah sebagai beban Seller</small></div><strong>${money(shippingTotal)}<small>${netSalesPercent(shippingTotal)} dari net sales</small></strong></div>${shippingRows.length ? `<div class="an-fee-shipping-grid">${shippingRows.sort((a, b) => n(b.amount) - n(a.amount)).map(shippingMetric).join('')}</div>` : `<div class="an-fee-shipping-empty">Tidak ada biaya ongkir pada periode dan filter aktif.</div>`}</div>`;
        const otherSection = `<section class="an-fee-other-section" aria-label="Biaya lainnya"><header><div><span class="an-fee-analysis-kicker">Other cost</span><strong>Biaya lainnya</strong><small>${otherRows.length ? 'Biaya tambahan di luar fee utama dan ongkir.' : 'Belum ada biaya lainnya pada periode dan filter aktif.'}</small></div><span class="an-fee-other-chip"><i class="bi bi-plus-circle" aria-hidden="true"></i> ${otherRows.length} komponen</span></header><button class="an-fee-other-total" type="button" data-open-other-fee aria-controls="otherFeeModal" aria-label="Lihat rincian biaya lainnya"><span><span>Total biaya lainnya</span><small>${otherRows.length ? 'Klik untuk melihat rincian biaya' : 'Buka untuk melihat status biaya lainnya'}</small></span><strong>${money(otherFeeTotal)}<small>${netSalesPercent(otherFeeTotal)} dari net sales</small></strong></button></section>`;
        const mergedStores = [...storeRows.reduce((map, row) => {
            const key = String(row.store_id || row.store_name || 'unknown');
            const current = map.get(key) || { store_id: row.store_id, store_name: row.store_name || 'Toko tidak diketahui', count: 0, seller: 0, affiliate: 0, platform: 0, ads: 0, adSource: 'none' };
            const amount = n(row.amount);
            if (row.cost_type === 'ads') {
                current.ads += amount;
                current.adSource = row.source || 'none';
            } else if (row.fee_group === 'affiliate') {
                current.count += Number(row.count || 0);
                current.affiliate += amount;
            } else {
                current.count += Number(row.count || 0);
                current[row.burden === 'platform' ? 'platform' : 'seller'] += amount;
            }
            map.set(key, current);
            return map;
        }, new Map()).values()].sort((a, b) => (b.seller + b.affiliate + b.platform + b.ads) - (a.seller + a.affiliate + a.platform + a.ads));
        const storeTable = mergedStores.length
            ? `<div class="an-table-wrap"><table class="an-table an-fee-analysis-table an-fee-store-table" aria-label="Fee, affiliate, dan biaya iklan berdasarkan toko"><thead><tr><th>Store</th><th>Count</th><th>Seller fee</th><th>Affiliate / AMS</th><th>Biaya iklan</th><th>Total biaya</th></tr></thead><tbody>${mergedStores.map(row => `<tr><td data-label="Store"><strong class="an-fee-type-name">${esc(row.store_name)}</strong></td><td data-label="Count">${row.count.toLocaleString('id-ID')}</td><td data-label="Seller fee" class="an-fee-analysis-amount">${money(row.seller)}</td><td data-label="Affiliate / AMS" class="an-fee-analysis-amount an-fee-affiliate-amount">${money(row.affiliate)}</td><td data-label="Biaya iklan" class="an-fee-analysis-amount an-fee-ads-amount"><span>${money(row.ads)}</span>${row.ads > 0 ? `<small>${row.adSource === 'platform_transaction_fallback' ? 'Mutasi platform' : 'Ads Daily'}</small>` : ''}</td><td data-label="Total biaya" class="an-fee-analysis-amount" title="Termasuk fee platform yang dipisahkan di section Fee platform">${money(row.seller + row.affiliate + row.platform + row.ads)}</td></tr>`).join('')}</tbody></table></div>`
            : empty('Belum ada toko', 'Tidak ada fee pada periode dan filter aktif.');

        $('feeByTypeTable')?.replaceChildren();
        if ($('feeByTypeTable')) $('feeByTypeTable').innerHTML = `${typeTable}${otherSection}`;
        if ($('feeByTypeCount')) $('feeByTypeCount').textContent = `${new Set(sellerTypeRows.map(row => row.code)).size.toLocaleString('id-ID')} tipe seller + lainnya`;
        if ($('feePlatformTable')) $('feePlatformTable').innerHTML = platformSection;
        if ($('feePlatformCount')) $('feePlatformCount').textContent = `${platformRows.length.toLocaleString('id-ID')} komponen`;
        if ($('feeShippingTable')) $('feeShippingTable').innerHTML = shippingSection;
        if ($('feeShippingCount')) $('feeShippingCount').textContent = `${shippingRows.length.toLocaleString('id-ID')} jenis biaya`;
        if ($('feeByTypeSellerTotal')) $('feeByTypeSellerTotal').textContent = money(sellerFeeTotal);
        if ($('feeByTypeSellerPercent')) $('feeByTypeSellerPercent').textContent = `${netSalesPercent(sellerFeeTotal)} dari net sales`;
        if ($('feeByStoreTable')) $('feeByStoreTable').innerHTML = storeTable;
        if ($('feeByStoreCount')) $('feeByStoreCount').textContent = `${new Set(storeRows.map(row => row.store_id)).size.toLocaleString('id-ID')} toko`;
        if ($('feeByOrderCount')) $('feeByOrderCount').textContent = `${Number(payload?.meta?.total || 0).toLocaleString('id-ID')} order`;

        const otherDetailRows = otherRows.flatMap(row => {
            const details = Array.isArray(row.details) ? row.details : [];
            return details.length ? details.map(detail => ({ ...detail, burden: row.burden || detail.burden || 'seller' })) : [row];
        }).sort((a, b) => n(b.amount) - n(a.amount));
        if ($('otherFeeSubtitle')) $('otherFeeSubtitle').textContent = `${from()} — ${to()} · payout sudah cair`;
        if ($('otherFeeSummary')) $('otherFeeSummary').innerHTML = [
            cashStat('Total biaya lainnya', money(otherFeeTotal), `${netSalesPercent(otherFeeTotal)} dari net sales`, 'fee'),
            cashStat('Komponen', otherRows.reduce((sum, row) => sum + Number(row.count || 0), 0).toLocaleString('id-ID'), 'Settlement fee breakdown', 'neutral'),
            cashStat('Beban', 'Seller', 'Dibebankan ke seller', 'primary'),
            cashStat('Kategori', 'Biaya lainnya', 'Di luar fee utama dan ongkir', 'neutral'),
        ].join('');
        if ($('otherFeeBody')) $('otherFeeBody').innerHTML = otherDetailRows.length
            ? `<div class="an-table-wrap"><table class="an-table an-fee-analysis-table an-other-fee-detail-table" aria-label="Rincian biaya lainnya"><thead><tr><th>Jenis biaya</th><th>Sumber data</th><th>Beban</th><th>Komponen</th><th>% Net Sales</th><th>Amount</th></tr></thead><tbody>${otherDetailRows.map(row => `<tr><td data-label="Jenis biaya"><strong class="an-fee-type-name">${esc(row.label || 'Biaya lainnya')}</strong></td><td data-label="Sumber data"><span class="an-fee-source-label">${esc(row.source || 'Settlement fee breakdown')}</span></td><td data-label="Beban">${burdenBadge(row.burden || 'seller')}</td><td data-label="Komponen">${Number(row.count || 0).toLocaleString('id-ID')}</td><td data-label="% Net Sales" class="an-fee-composition-percent">${netSalesPercent(row.amount)}</td><td data-label="Amount" class="an-fee-analysis-amount">${money(row.amount)}</td></tr>`).join('')}</tbody></table></div>`
            : empty('Belum ada biaya lainnya', 'Tidak ada biaya tambahan pada periode dan filter aktif.');
    }

    function openOtherFeeModal() {
        const modal = $('otherFeeModal');
        if (!modal) return;
        otherFeeModalTrigger = document.activeElement;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('an-modal-open');
        modal.querySelector('[data-other-fee-close]')?.focus();
    }

    function closeOtherFeeModal() {
        const modal = $('otherFeeModal');
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('an-modal-open');
        otherFeeModalTrigger?.focus?.();
        otherFeeModalTrigger = null;
    }

    const sellerFeeDetailSourceLabel = source => ({
        commission_fee: 'Settlement marketplace',
        service_fee: 'Settlement marketplace',
        transaction_fee: 'Settlement marketplace',
        seller_order_processing_fee: 'Settlement marketplace',
        seller_transaction_fee: 'Settlement marketplace',
        activity_fee: 'Affiliate / AMS',
        shipping_cost_minus_subsidy: 'Biaya kirim aktual − subsidi ongkir',
        shipping_cost_minus_subsidy_minus_buyer_shipping: 'Biaya kirim aktual − subsidi ongkir − ongkir dibayar pembeli',
        shipping_cost_amount: 'TikTok Finance · potongan settlement',
        shipping_insurance_fee: 'Shopee Finance · potongan settlement',
        shipping_seller_protection_fee_amount: 'Shopee Finance · premi proteksi logistik',
        delivery_seller_protection_fee_premium_amount: 'Shopee Finance · premi proteksi pengiriman',
        premi: 'Shopee Finance · premi potongan settlement',
        fee_tax_amount: 'Settlement TikTok',
    }[source] || source || 'Settlement fee breakdown');

    const sellerFeeDetailRows = row => {
        const breakdown = Array.isArray(row?.fee_breakdown) ? row.fee_breakdown : [];
        const shippingCostFee = breakdown.filter(item => item.code === 'shipping_cost').reduce((sum, item) => sum + n(item.amount), 0);
        const shippingSubsidyFee = breakdown.filter(item => item.code === 'shipping_subsidy').reduce((sum, item) => sum + n(item.amount), 0);
        const derivedLogisticsFee = breakdown.filter(item => item.code === 'shipping_difference').reduce((sum, item) => sum + n(item.amount), 0);
        const hasDerivedLogistics = breakdown.some(item => item.code === 'shipping_difference');
        const isShopeeOrder = String(row?.platform || '').toLowerCase() === 'shopee' || selectedPlatform() === 'shopee';
        const logisticsFee = hasDerivedLogistics
            ? derivedLogisticsFee
            : (isShopeeOrder ? 0 : Math.max(0, shippingCostFee - shippingSubsidyFee));
        const hasSellerShippingCost = breakdown.some(item => item.code === 'shipping_cost' && item.burden === 'seller');
        const rows = breakdown
            .filter(item => item.burden === 'seller'
                && item.code !== 'affiliate_fee'
                && item.category !== 'affiliate'
                && item.category !== 'shipping'
                )
            .map(item => ({
                label: item.label || item.code || 'Biaya Seller',
                source: sellerFeeDetailSourceLabel(item.source),
                amount: n(item.amount),
            }));

        breakdown
            .filter(item => item.code === 'shipping_cost' && item.burden === 'seller')
            .forEach(item => rows.push({
                label: item.label || 'Biaya Logistik Seller · potongan settlement',
                source: sellerFeeDetailSourceLabel(item.source),
                amount: n(item.amount),
            }));

        // TikTok's raw shipping settlement is already classified as seller
        // burden above. Only add the derived shipping difference for Shopee
        // so the modal total does not double-count logistics.
        if (logisticsFee > 0 && !hasSellerShippingCost) {
            rows.push({
                label: 'Selisih Ongkir',
                source: selectedPlatform() === 'shopee'
                    ? 'Biaya kirim aktual − subsidi ongkir − ongkir dibayar pembeli'
                    : 'Biaya kirim aktual − subsidi ongkir',
                amount: logisticsFee,
            });
        }

        return rows.filter(item => item.amount > 0).sort((a, b) => b.amount - a.amount);
    };

    function openSellerFeeModal(key) {
        const row = sellerFeeDetailCache.get(String(key));
        const modal = $('sellerFeeModal');
        if (!row || !modal) return;

        const detailRows = sellerFeeDetailRows(row);
        const total = detailRows.reduce((sum, item) => sum + n(item.amount), 0);
        const netSales = n(row.net_sales || row.gross_sales || row.cash_gross_sales);
        const percent = netSales > 0 ? `${(total / netSales * 100).toFixed(1)}% dari net sales` : '—';
        sellerFeeModalTrigger = document.activeElement;
        $('sellerFeeTitle').textContent = `Detail biaya Seller · ${row.channel_order_id || 'Order'}`;
        $('sellerFeeSubtitle').textContent = `${row.store_name || 'Tanpa toko'} · ${dateTime(row.ordered_at)}`;
        $('sellerFeeSummary').innerHTML = [
            cashStat('Total biaya Seller', money(total), percent, 'fee'),
            cashStat('Net Sales', money(netSales), 'Basis persentase', 'accent'),
            cashStat('Komponen', detailRows.length.toLocaleString('id-ID'), 'Rincian settlement', 'neutral'),
            cashStat('Beban', 'Seller', 'Dibebankan ke seller', 'primary'),
        ].join('');
        const refundAmount = n(row.refund);
        const reversalAmount = n(row.reversal_amount);
        const returnRefundStatus = String(row.return_refund_status || '').toLowerCase();
        const hasReturnRefundState = ['return_requested', 'refunded', 'settlement_reversal'].includes(returnRefundStatus);
        const isReturnRefundOrder = hasReturnRefundState || row.status_group === 'return_refund' || refundAmount > 0;
        const refundNote = isReturnRefundOrder
            ? `<div class="an-fee-refund-note"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i><span><strong>${esc(returnRefundStatusLabel(returnRefundStatus))}</strong><small>${refundAmount > 0 ? 'Nominal refund ditampilkan terpisah.' : 'Nominal refund belum tersedia; adjustment payout tetap dipisahkan.'} ${reversalAmount > 0 ? `Reversal settlement ${money(reversalAmount)}.` : ''} Selisih ongkir tetap dihitung sebagai beban Seller.</small></span></div>`
            : '';
        $('sellerFeeBody').innerHTML = `${refundNote}${detailRows.length
            ? `<div class="an-table-wrap"><table class="an-table an-fee-analysis-table" aria-label="Rincian biaya Seller"><thead><tr><th>Komponen</th><th>Sumber</th><th>% Net Sales</th><th>Amount</th></tr></thead><tbody>${detailRows.map(item => `<tr><td data-label="Komponen"><strong class="an-fee-type-name">${esc(item.label)}</strong></td><td data-label="Sumber"><span class="an-fee-source-label">${esc(item.source)}</span></td><td data-label="% Net Sales" class="an-fee-composition-percent">${netSales > 0 ? `${(item.amount / netSales * 100).toFixed(1)}%` : '—'}</td><td data-label="Amount" class="an-fee-analysis-amount">${money(item.amount)}</td></tr>`).join('')}</tbody></table></div>`
            : `<div class="an-fee-platform-empty">Tidak ada rincian biaya Seller pada order ini.</div>`}`;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('an-modal-open');
        modal.querySelector('[data-seller-fee-close]')?.focus();
    }

    function closeSellerFeeModal() {
        const modal = $('sellerFeeModal');
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('an-modal-open');
        sellerFeeModalTrigger?.focus?.();
        sellerFeeModalTrigger = null;
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
        const scopeLabel = [
            selectedPlatform() ? $('anPlatform')?.selectedOptions?.[0]?.textContent?.trim() : 'Semua platform',
            selectedStore() ? $('anStore')?.selectedOptions?.[0]?.textContent?.trim() : 'Semua toko',
        ].filter(Boolean).join(' · ');
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
            ? feeBurdenRows
                .filter(item => item.burden === 'seller' && item.code !== 'shipping_difference')
                .reduce((sum, item) => sum + n(item.amount), 0)
            : totalFeeAmount;
        const platformFeeAmount = feeBurdenRows.filter(item => item.burden === 'platform').reduce((sum, item) => sum + n(item.amount), 0);
        const otherFeeAmount = feeBurdenRows.filter(item => item.code === 'other_fee').reduce((sum, item) => sum + n(item.amount), 0);
        const adCostAmount = (Array.isArray(payload.fee_by_store) ? payload.fee_by_store : [])
            .filter(item => item.cost_type === 'ads')
            .reduce((sum, item) => sum + n(item.amount), 0);
        const totalCostAmount = totalFeeAmount + adCostAmount;
        const feeCompositionPercent = amount => settledNetSales > 0 ? `${(n(amount) / settledNetSales * 100).toFixed(1)}% dari net sales cair` : 'Net Sales cair tidak tersedia';
        const totalCostPercent = amount => settledNetSales > 0 ? `${(n(amount) / settledNetSales * 100).toFixed(1)}% dari net sales cair` : 'Net Sales cair tidak tersedia';
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
        $('cashOrdersSubtitle').textContent = `${from()} — ${to()} · ${scopeLabel} · ${modeLabel} · ${totalOrders} order`;
        $('cashOrdersSummary').innerHTML = isCombined ? [
            cashStat('Gross Sales', money(grossSales), netSalesPercent(grossSales), 'primary'),
            cashStat('Net Sales', money(netSales), netSalesPercent(netSales), 'accent'),
            cashStat('Payout tercatat', money(aggregate.cash_payout), settledNetSalesPercent(aggregate.cash_payout), 'neutral'),
            cashStat('Order cair', settledOrderCount, 'Settlement complete', 'settled'),
            cashStat('Fee marketplace', money(totalFeeAmount), totalFeePercent, 'fee'),
            cashStat('Refund / adjustment', money(aggregate.cash_refund), settledNetSalesPercent(aggregate.cash_refund), 'neutral'),
        ].join('') : isFeeFocus ? [
            cashStat('Total biaya seller', money(sellerFeeAmount), feeCompositionPercent(sellerFeeAmount), 'fee'),
            cashStat('Total biaya platform', money(platformFeeAmount), platformFeeAmount > 0 ? feeCompositionPercent(platformFeeAmount) : 'Tidak ada subsidi platform', 'accent'),
            cashStat('Biaya iklan', money(adCostAmount), adCostAmount > 0 ? totalCostPercent(adCostAmount) : 'Tidak ada biaya iklan', 'fee'),
            cashStat('Biaya lainnya', money(otherFeeAmount), otherFeeAmount > 0 ? feeCompositionPercent(otherFeeAmount) : 'Tidak ada biaya lainnya', 'neutral'),
            cashStat('Total biaya marketplace', money(totalCostAmount), settledNetSales > 0 ? `${(totalCostAmount / settledNetSales * 100).toFixed(1)}% net sales cair` : '—', 'fee'),
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
        const sellerFeeItems = feeBurdenRows.length
            ? feeBurdenRows.filter(item => item.burden === 'seller' && item.code !== 'other_fee' && item.code !== 'shipping_difference')
            : feeItems.filter(item => item.code !== 'shipping_subsidy' && item.category !== 'platform');
        const platformFeeItems = feeBurdenRows.length
            ? feeBurdenRows.filter(item => item.burden === 'platform')
            : feeItems.filter(item => item.code === 'shipping_subsidy' || item.category === 'platform');
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
        sellerFeeDetailCache = new Map();
        const feeOrderTable = rows.length
            ? `<div class="an-table-wrap"><table class="an-table an-fee-analysis-table an-fee-order-table" aria-label="Beban Seller berdasarkan order"><thead><tr><th>Order &amp; Toko</th><th>Kategori Internal</th><th>Qty Item</th><th>Net Sales</th><th>Dana cair</th><th>Beban Seller</th><th>Affiliate / AMS</th><th>Refund / reversal</th><th>Selisih ongkir</th></tr></thead><tbody>${rows.map((row, rowIndex) => {
                const breakdown = Array.isArray(row.fee_breakdown) ? row.fee_breakdown : [];
                const affiliateFee = breakdown.filter(item => item.code === 'affiliate_fee' || item.category === 'affiliate').reduce((sum, item) => sum + n(item.amount), 0);
                const shippingCostFee = breakdown.filter(item => item.code === 'shipping_cost').reduce((sum, item) => sum + n(item.amount), 0);
                const shippingSubsidyFee = breakdown.filter(item => item.code === 'shipping_subsidy').reduce((sum, item) => sum + n(item.amount), 0);
                const derivedLogisticsFee = breakdown.filter(item => item.code === 'shipping_difference').reduce((sum, item) => sum + n(item.amount), 0);
                const hasDerivedLogistics = breakdown.some(item => item.code === 'shipping_difference');
                const isShopeeOrder = String(row.platform || '').toLowerCase() === 'shopee' || selectedPlatform() === 'shopee';
                const logisticsFee = hasDerivedLogistics
                    ? derivedLogisticsFee
                    : (isShopeeOrder ? 0 : Math.max(0, shippingCostFee - shippingSubsidyFee));
                const sellerShippingCost = breakdown
                    .filter(item => item.code === 'shipping_cost' && item.burden === 'seller')
                    .reduce((sum, item) => sum + n(item.amount), 0);
                const premiumFee = breakdown.filter(item => item.code === 'insurance_fee').reduce((sum, item) => sum + n(item.amount), 0);
                const sellerFee = breakdown.filter(item => item.burden === 'seller' && item.code !== 'affiliate_fee' && item.category !== 'affiliate' && (item.category !== 'shipping' || item.code === 'shipping_difference') && item.code !== 'insurance_fee' && item.code !== 'shipping_difference').reduce((sum, item) => sum + n(item.amount), 0);
                const sellerAmount = sellerFee + premiumFee + (sellerShippingCost > 0 ? sellerShippingCost : logisticsFee);
                const netSales = n(row.net_sales || row.gross_sales || row.cash_gross_sales);
                const cashPayout = n(row.cash_payout);
                const internalCategory = String(row.internal_category || (Array.isArray(row.internal_categories) ? row.internal_categories.join(', ') : '') || 'Belum termapping');
                const internalItemQty = Number(row.internal_item_qty || 0);
                const refundAmount = n(row.refund);
                const reversalAmount = n(row.reversal_amount);
                const returnRefundStatus = String(row.return_refund_status || '').toLowerCase();
                const hasReturnRefundState = ['return_requested', 'refunded', 'settlement_reversal'].includes(returnRefundStatus);
                const sellerPercent = netSales > 0 ? `${(sellerAmount / netSales * 100).toFixed(1)}% dari net sales` : '—';
                const logisticsPercent = netSales > 0 ? `${(logisticsFee / netSales * 100).toFixed(1)}% dari net sales` : '—';
                const affiliatePercent = netSales > 0 ? `${(affiliateFee / netSales * 100).toFixed(1)}% dari net sales` : '—';
                const sellerDetailKey = String(rowIndex);
                sellerFeeDetailCache.set(sellerDetailKey, row);
        const isRefundRow = row.status_group === 'return_refund' || hasReturnRefundState || refundAmount > 0;
                const logisticsNote = logisticsFee > 0
                    ? `${logisticsPercent} · beban Seller`
                    : (isShopeeOrder ? 'Biaya kirim − subsidi − ongkir pembeli' : 'Biaya kirim − subsidi');
        const refundBadge = isRefundRow ? `<span class="an-fee-refund-badge"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> ${esc(returnRefundStatusLabel(returnRefundStatus))}</span>` : '';
        const refundDisplay = [
            refundAmount > 0 ? `<span>−${money(refundAmount)}</span><small class="an-fee-order-percent">Nominal refund</small>` : '',
            reversalAmount > 0 ? `<span>−${money(reversalAmount)}</span><small class="an-fee-order-percent">Payout reversal</small>` : '',
        ].filter(Boolean).join('') || (isRefundRow ? '<span>—</span><small class="an-fee-order-percent">Nominal refund belum tersedia</small>' : '—');
                const sellerNote = logisticsFee > 0 ? `${sellerPercent} · ongkir beban Seller · Detail` : `${sellerPercent} · Detail`;
        return `<tr class="${isRefundRow ? 'an-fee-refund-row' : ''}"><td data-label="Order &amp; Toko"><strong class="an-cash-order">${esc(row.channel_order_id)}</strong><span class="an-cash-meta">${esc(row.store_name || 'Tanpa toko')} · ${esc(dateTime(row.ordered_at))}</span>${refundBadge}</td><td data-label="Kategori Internal"><span class="an-fee-type-name">${esc(internalCategory)}</span></td><td data-label="Qty Item" class="an-fee-analysis-amount">${internalItemQty > 0 ? internalItemQty.toLocaleString('id-ID') : '—'}</td><td data-label="Net Sales" class="an-fee-analysis-amount">${money(netSales)}</td><td data-label="Dana cair" class="an-fee-analysis-amount an-fee-payout-amount">${cashPayout !== 0 ? signedMoney(cashPayout) : '—'}</td><td data-label="Beban Seller" class="an-fee-analysis-amount"><button class="an-fee-seller-detail-trigger" type="button" data-open-seller-fee="${sellerDetailKey}" aria-controls="sellerFeeModal" aria-label="Lihat rincian beban Seller untuk order ${esc(row.channel_order_id)}"><span>${money(sellerAmount)}</span><small class="an-fee-order-percent">${sellerNote}</small></button></td><td data-label="Affiliate / AMS" class="an-fee-analysis-amount an-fee-affiliate-amount"><span>${money(affiliateFee)}</span><small class="an-fee-order-percent">${affiliatePercent}</small></td><td data-label="Refund / reversal" class="an-fee-analysis-amount an-fee-refund-amount">${refundDisplay}</td><td data-label="Selisih ongkir" class="an-fee-analysis-amount an-fee-logistics-amount"><span>${logisticsFee > 0 ? money(logisticsFee) : '—'}</span><small class="an-fee-order-percent">${logisticsNote}</small></td></tr>`;
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
        $('returnOrdersBody').innerHTML = rows.length ? `<div class="an-table-wrap"><table class="an-table an-cash-table an-exception-table" aria-label="Daftar return dan refund"><thead><tr><th>Referensi</th><th>Order &amp; toko</th><th>Status</th><th>Nilai kasus</th><th>Waktu</th></tr></thead><tbody>${rows.map(row => { const kindLabel = row.kind === 'failed_delivery' ? 'Pengiriman gagal' : (row.kind === 'reversal' ? 'Payout reversal' : (row.kind === 'refund' ? 'Refund' : 'Return + refund')); const kindClass = row.kind === 'failed_delivery' ? '' : 'return'; const statusClass = row.kind === 'failed_delivery' ? 'failed' : 'return'; return `<tr><td data-label="Referensi"><div class="an-cash-order">${esc(row.reference)}</div><span class="an-exception-kind ${kindClass}">${kindLabel}</span><span class="an-cash-meta">${esc(row.reason || '—')}</span></td><td data-label="Order &amp; toko"><div class="an-cash-order">${esc(row.order_sn)}</div><span class="an-cash-meta">${esc(row.store_name)}</span></td><td data-label="Status"><span class="an-cash-status ${statusClass}">${esc(cashStatus(row.status))}</span>${row.tracking_number ? `<span class="an-cash-meta">AWB ${esc(row.tracking_number)}</span>` : ''}</td><td data-label="Nilai kasus" class="an-cash-money fee">${money(row.amount)}</td><td data-label="Waktu"><span class="an-cash-meta">${esc(dateTime(row.event_time))}</span></td></tr>`; }).join('')}</tbody></table></div>` : stateMarkup('empty', 'Belum ada data exception', `Belum ada data ${isFailed ? 'pengiriman gagal eksplisit' : 'return / refund'} pada periode ini.`);
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
