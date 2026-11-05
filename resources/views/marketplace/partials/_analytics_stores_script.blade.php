    const dateTime = value => {
        if (!value) return '—';
        const raw = String(value);
        const date = /^\d{10,13}$/.test(raw) ? new Date(Number(raw) * (raw.length === 10 ? 1000 : 1)) : new Date(raw.replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('id-ID', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'});
    };
    const cashStatus = value => String(value || '—').replace(/_/g, ' ').toLowerCase();
    const cashStat = (label, value, note = '') => `<div class="an-modal-stat"><div class="an-modal-stat-label">${label}</div><div class="an-modal-stat-value">${value}</div>${note ? `<span class="an-modal-stat-note">${note}</span>` : ''}</div>`;
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
                element.textContent = 'Memuat KPI…';
                return;
            }
            const count = Number(summary.cash_order_count || 0).toLocaleString('id-ID');
            const amount = money(cashTabAmount(settlement, summary));
            element.textContent = `${count} order · ${amount}`;
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
        const targetSubtitle = $('revenueOrdersSubtitle');
        if (sourceSubtitle && targetSubtitle) targetSubtitle.textContent = sourceSubtitle.textContent;
        ['Page', 'PageMeta'].forEach(suffix => {
            const target = $('revenueOrders' + suffix);
            const source = $('cashOrders' + suffix);
            if (source && target) target.textContent = source.textContent;
        });
        const targetPrev = $('revenueOrdersPrev');
        const targetNext = $('revenueOrdersNext');
        if (sourcePrev && targetPrev) targetPrev.disabled = sourcePrev.disabled;
        if (sourceNext && targetNext) targetNext.disabled = sourceNext.disabled;
        document.querySelectorAll('[data-revenue-settlement]').forEach(button => {
            const active = button.dataset.revenueSettlement === cashSettlement;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        renderCashTabSummaries();
    }

    async function loadCashTabSummaries() {
        const token = ++cashTabSummaryToken;
        renderCashTabSummaries();
        const settlements = cashSettlementTabs.filter(settlement => !cashTabSummaries[settlement]);
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
        const feePercent = value => aggregate.cash_order_revenue > 0 ? `${(n(value) / n(aggregate.cash_order_revenue) * 100).toFixed(1)}% omzet order` : '0.0% omzet order';
        const settlementLabel = row => row.settlement_time ? 'Sudah cair' : 'Belum cair';
        const statusLabel = row => row.status_group_label || cashStatus(row.status);
        const modeLabel = { all: 'semua pesanan', settled: 'payout sudah cair', shipped: 'order masih dikirim', warehouse: 'disimpan di gudang Shopee', confirm: 'menunggu konfirmasi pembeli', cancelled: 'order dibatalkan', return_refund: 'order return / refund', unsettled: 'dana belum cair' }[cashSettlement] || 'semua pesanan';
        const isTikTokPlatform = selectedPlatform() === 'tiktok';
        const canonicalTotals = Array.isArray(payload.canonical_fee_totals) ? payload.canonical_fee_totals : [];
        const canonicalAmount = code => Number(canonicalTotals.find(item => item.code === code)?.amount || 0);
        const canonicalFeeCards = [
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
            .map(([, label, category, amount]) => cashStat(label, money(amount), category === 'shipping' ? 'mapping pengiriman' : 'mapping fee'));
        const feeSummaryCards = canonicalFeeCards.length > 0 ? canonicalFeeCards : (isTikTokPlatform
            ? [
                cashStat('Total fee marketplace', money(aggregate.cash_marketplace_fees), feePercent(aggregate.cash_marketplace_fees)),
                cashStat('Rincian fee', 'Per order', 'Platform · dinamis · program · proses'),
            ]
            : [
                cashStat('Administrasi', money(aggregate.cash_commission_fee), feePercent(aggregate.cash_commission_fee)),
                cashStat('Layanan / program', money(aggregate.cash_service_fee), feePercent(aggregate.cash_service_fee)),
                cashStat('Proses / transaksi', money(aggregate.cash_transaction_fee), feePercent(aggregate.cash_transaction_fee)),
                cashStat('Affiliate / AMS', money(aggregate.cash_affiliate_fees), feePercent(aggregate.cash_affiliate_fees)),
            ]);
        $('cashOrdersTitle').textContent = isFeeFocus ? 'Rincian fee marketplace actual' : 'Omzet, pencairan & fee marketplace';
        $('cashOrdersSubtitle').textContent = `${from()} — ${to()} · ${modeLabel} · ${totalOrders} order`;
        $('cashOrdersSummary').innerHTML = isCombined ? [
            cashStat(isAll ? 'Omzet revenue' : (isSettled ? 'Omzet cair' : 'Omzet belum cair'), money(isSettled ? aggregate.cash_payout : aggregate.cash_gross_sales)),
            cashStat('Payout tercatat', money(aggregate.cash_payout), feePercent(aggregate.cash_payout)),
            ...feeSummaryCards,
            cashStat('Refund / adjustment', money(aggregate.cash_refund)),
        ].join('') : isFeeFocus ? [
            ...feeSummaryCards,
        ].join('') : [
            cashStat(isAll ? 'Omzet revenue' : (isSettled ? 'Omzet cair' : 'Omzet belum cair'), money(isSettled ? aggregate.cash_payout : aggregate.cash_gross_sales)),
            cashStat(isSettled ? 'Fee marketplace actual' : 'Fee marketplace tercatat', money(aggregate.cash_marketplace_fees)),
            cashStat('Affiliate / AMS', money(aggregate.cash_affiliate_fees)),
            cashStat('Refund / adjustment', money(aggregate.cash_refund)),
        ].join('');
        cashTabSummaries[cashSettlement] = aggregate;
        renderCashTabSummaries();
        const feeBreakdown = (isFeeFocus || isCombined) ? `<div class="an-modal-fee-breakdown"><div><span>Total fee marketplace</span><strong>${money(aggregate.cash_marketplace_fees)}<small>${feePercent(aggregate.cash_marketplace_fees)}</small></strong></div>${isTikTokPlatform ? '<div><span>Mapping detail</span><strong>Per order<small>Platform · dinamis · program · proses</small></strong></div>' : `<div><span>Administrasi</span><strong>${money(aggregate.cash_commission_fee)}<small>${feePercent(aggregate.cash_commission_fee)}</small></strong></div><div><span>Layanan / program</span><strong>${money(aggregate.cash_service_fee)}<small>${feePercent(aggregate.cash_service_fee)}</small></strong></div><div><span>Proses / transaksi</span><strong>${money(aggregate.cash_transaction_fee)}<small>${feePercent(aggregate.cash_transaction_fee)}</small></strong></div>`}<div><span>Asuransi</span><strong>${money(aggregate.cash_shipping_insurance_fee)}<small>${feePercent(aggregate.cash_shipping_insurance_fee)}</small></strong></div><div><span>Pajak escrow</span><strong>${money(aggregate.cash_escrow_tax)}<small>${feePercent(aggregate.cash_escrow_tax)}</small></strong></div></div>` : '';
        const renderRows = groupRows => groupRows.map(row => {
            const rowSettled = Boolean(row.settlement_time);
            const payout = rowSettled && n(row.cash_payout) > 0 ? money(row.cash_payout) : (n(row.cash_payout) > 0 ? money(row.cash_payout) : 'Belum tersedia');
            const mappedDetails = canonicalFeeDetails(row) || `${cashFeeDetail(row, 'Administrasi / komisi', row.commission_fee, 'fee')}${cashFeeDetail(row, 'Layanan / program', row.service_fee, 'fee')}${cashFeeDetail(row, 'Proses / transaksi', row.transaction_fee, 'fee')}${cashFeeDetail(row, 'Affiliate / AMS', row.affiliate_fee_raw, 'affiliate')}`;
            return `<tr><td><div class="an-cash-order">${esc(row.channel_order_id)}</div><span class="an-cash-meta">Order ${esc(dateTime(row.ordered_at))}</span><details class="an-cash-detail"><summary>Rincian mapping fee</summary><div class="an-cash-detail-grid">${mappedDetails}${cashFeeDetail(row, 'Refund / adjustment', row.refund)}${cashFeeDetail(row, 'Total fee', row.total_fees, 'fee')}</div></details></td><td><div>${esc(row.store_name)}</div><span class="an-cash-status">${esc(statusLabel(row))}</span><span class="an-cash-meta">Payout: ${settlementLabel(row)}</span></td><td><span class="an-cash-payment">${esc(paymentLabel(row.payment_method))}</span></td><td class="an-cash-money">${money(row.gross_sales)}</td><td class="an-cash-money">${money(row.buyer_payment_amount)}</td><td class="an-cash-money ${rowSettled ? 'good' : ''}">${payout}</td><td class="an-cash-money fee">${money(row.marketplace_fee)}</td><td class="an-cash-money affiliate">${money(row.affiliate_fee)}</td></tr>`;
        }).join('');
        const renderTable = (groupRows, groupLabel = '') => groupRows.length ? `${groupLabel ? `<div class="an-cash-group-head"><strong>${esc(groupLabel)}</strong><span>${groupRows.length.toLocaleString('id-ID')} order</span></div>` : ''}<div class="an-table-wrap"><table class="an-table an-cash-table"><thead><tr><th>Order</th><th>Toko &amp; status</th><th>Metode pembayaran</th><th>${isAll ? 'Nilai order' : 'Omzet order'}</th><th>Pembayaran pembeli</th><th>${isSettled ? 'Omzet cair' : 'Payout tercatat'}</th><th>Fee marketplace</th><th>Affiliate / AMS</th></tr></thead><tbody>${renderRows(groupRows)}</tbody></table></div>` : '';
        const groups = isAll
            ? ['completed', 'warehouse', 'shipped', 'confirm', 'return_refund', 'cancelled', 'other'].map(key => ({ key, label: rows.find(row => row.status_group === key)?.status_group_label || key, rows: rows.filter(row => row.status_group === key) })).filter(group => group.rows.length)
            : [{ key: 'flat', label: '', rows }];
        const table = groups.length ? groups.map(group => `<section class="an-cash-group">${renderTable(group.rows, isAll ? group.label : '')}</section>`).join('') : `<div class="an-empty">Tidak ada order pada periode ini.</div>`;
        const body = isFeeFocus
            ? (rows.length ? renderTable(rows) : '<div class="an-empty">Tidak ada order pada periode ini.</div>')
            : table;
        $('cashOrdersBody').innerHTML = feeBreakdown + body;
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
        });
    }
    async function loadCashOrders() {
        if (cashLoading) return;
        cashLoading = true;
        $('cashOrdersBody').innerHTML = '<div class="an-empty">Memuat order…</div>';
        $('cashOrdersSummary').innerHTML = '<div class="an-empty">Memuat ringkasan…</div>';
        $('cashOrdersPage').textContent = 'Memuat…';
        $('cashOrdersPageMeta').textContent = 'Mengambil data terbaru';
        $('cashOrdersPrev').disabled = true;
        $('cashOrdersNext').disabled = true;
        syncRevenueOrdersView();
        try {
            const params = new URLSearchParams({ date_from: from(), date_to: to(), settlement: cashSettlement, page: String(cashPage), per_page: '50', _ts: Date.now().toString() });
            if (selectedPlatform()) params.set('platform', selectedPlatform());
            if (selectedStore()) params.set('store_id', selectedStore());
            cashPayload = await api('/api/marketplace/analytics-cash-orders?' + params.toString(), { cache: 'no-store' });
            renderCashOrders();
        } catch (e) {
            console.error('Cash order detail load failed', e);
            $('cashOrdersSummary').innerHTML = '<div class="an-error">Ringkasan order cair gagal dimuat.</div>';
            $('cashOrdersBody').innerHTML = '<div class="an-error">Detail order cair gagal dimuat.</div>';
            $('cashOrdersPage').textContent = 'Gagal memuat';
            $('cashOrdersPageMeta').textContent = 'Coba lagi';
            syncRevenueOrdersView();
        } finally {
            cashLoading = false;
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
        loadCashTabSummaries();
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
        loadCashTabSummaries();
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
        $('returnOrdersBody').innerHTML = rows.length ? `<div class="an-table-wrap"><table class="an-table an-cash-table"><thead><tr><th>Referensi</th><th>Order &amp; toko</th><th>Status</th><th>Nilai kasus</th><th>Waktu</th></tr></thead><tbody>${rows.map(row => { const kindLabel = row.kind === 'failed_delivery' ? 'Pengiriman gagal' : (row.kind === 'refund' ? 'Refund' : 'Return + refund'); const kindClass = row.kind === 'failed_delivery' ? '' : 'return'; return `<tr><td><div class="an-cash-order">${esc(row.reference)}</div><span class="an-exception-kind ${kindClass}">${kindLabel}</span><span class="an-cash-meta">${esc(row.reason || '—')}</span></td><td><div class="an-cash-order">${esc(row.order_sn)}</div><span class="an-cash-meta">${esc(row.store_name)}</span></td><td><span class="an-cash-status">${esc(cashStatus(row.status))}</span>${row.tracking_number ? `<span class="an-cash-meta">AWB ${esc(row.tracking_number)}</span>` : ''}</td><td class="an-cash-money fee">${money(row.amount)}</td><td><span class="an-cash-meta">${esc(dateTime(row.event_time))}</span></td></tr>`; }).join('')}</tbody></table></div>` : `<div class="an-empty">Belum ada data ${isFailed ? 'pengiriman gagal eksplisit' : 'return / refund'} pada periode ini.</div>`;
        const currentPage = Number(meta.current_page || returnPage);
        const lastPage = Number(meta.last_page || 1);
        $('returnOrdersPage').textContent = `Halaman ${currentPage} / ${lastPage}`;
        $('returnOrdersPrev').disabled = returnLoading || currentPage <= 1;
        $('returnOrdersNext').disabled = returnLoading || currentPage >= lastPage;
    }
    function closeReturnOrders() {
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
        if (returnLoading) return;
        returnLoading = true;
        $('returnOrdersBody').innerHTML = '<div class="an-empty">Memuat data return…</div>';
        $('returnOrdersSummary').innerHTML = '<div class="an-empty">Memuat ringkasan…</div>';
        $('returnOrdersPrev').disabled = true;
        $('returnOrdersNext').disabled = true;
        try {
            const params = new URLSearchParams({ date_from: from(), date_to: to(), type: returnType, page: String(returnPage), per_page: '50', _ts: Date.now().toString() });
            if (selectedPlatform()) params.set('platform', selectedPlatform());
            if (selectedStore()) params.set('store_id', selectedStore());
            returnPayload = await api('/api/marketplace/analytics-return-orders?' + params.toString(), { cache: 'no-store' });
            renderReturnOrders();
        } catch (e) {
            console.error('Return detail load failed', e);
            $('returnOrdersSummary').innerHTML = '<div class="an-error">Ringkasan return gagal dimuat.</div>';
            $('returnOrdersBody').innerHTML = '<div class="an-error">Data return gagal dimuat.</div>';
            $('returnOrdersPage').textContent = '—';
        } finally {
            returnLoading = false;
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
