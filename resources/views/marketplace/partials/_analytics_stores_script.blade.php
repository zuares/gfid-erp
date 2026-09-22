    const dateTime = value => {
        if (!value) return '—';
        const raw = String(value);
        const date = /^\d{10,13}$/.test(raw) ? new Date(Number(raw) * (raw.length === 10 ? 1000 : 1)) : new Date(raw.replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('id-ID', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'});
    };
    const cashStatus = value => String(value || '—').replace(/_/g, ' ').toLowerCase();
    const cashStat = (label, value, note = '') => `<div class="an-modal-stat"><div class="an-modal-stat-label">${label}</div><div class="an-modal-stat-value">${value}</div>${note ? `<span class="an-modal-stat-note">${note}</span>` : ''}</div>`;
    const cashFeeDetail = (row, label, value, className = '') => `<span>${label}<strong class="${className}">${money(value)}</strong></span>`;
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

    function renderCashOrders() {
        const payload = cashPayload || {};
        const aggregate = payload.summary || {};
        const meta = payload.meta || {};
        const rows = Array.isArray(payload.data) ? payload.data : [];
        const isAll = cashSettlement === 'all';
        const isSettled = cashSettlement === 'settled';
        const isFeeFocus = cashFocus === 'fees';
        const totalOrders = Number(meta.total || aggregate.cash_order_count || 0).toLocaleString('id-ID');
        const feePercent = value => aggregate.cash_order_revenue > 0 ? `${(n(value) / n(aggregate.cash_order_revenue) * 100).toFixed(1)}% omzet order` : '0.0% omzet order';
        const settlementLabel = row => row.settlement_time ? 'Sudah cair' : 'Belum cair';
        const statusLabel = row => row.status_group_label || cashStatus(row.status);
        const modeLabel = { all: 'semua status order', settled: 'payout sudah cair', shipped: 'order shipped · masih dikirim', confirm: 'menunggu konfirmasi pembeli', cancelled: 'order dibatalkan', return_refund: 'order return / refund', unsettled: 'payout belum cair' }[cashSettlement] || 'semua status order';
        $('cashOrdersTitle').textContent = isFeeFocus ? 'Rincian fee marketplace actual' : 'Status order & pencairan';
        $('cashOrdersSubtitle').textContent = `${from()} — ${to()} · ${modeLabel} · ${totalOrders} order`;
        $('cashOrdersSummary').innerHTML = isFeeFocus ? [
            cashStat(isSettled ? 'Total fee marketplace' : 'Fee marketplace tercatat', money(aggregate.cash_marketplace_fees), feePercent(aggregate.cash_marketplace_fees)),
            cashStat('Administrasi', money(aggregate.cash_commission_fee), feePercent(aggregate.cash_commission_fee)),
            cashStat('Layanan', money(aggregate.cash_service_fee), feePercent(aggregate.cash_service_fee)),
            cashStat('Transaksi', money(aggregate.cash_transaction_fee), feePercent(aggregate.cash_transaction_fee)),
        ].join('') : [
            cashStat(isAll ? 'Omzet revenue' : (isSettled ? 'Omzet cair' : 'Omzet belum cair'), money(isSettled ? aggregate.cash_payout : aggregate.cash_gross_sales)),
            cashStat(isSettled ? 'Fee marketplace actual' : 'Fee marketplace tercatat', money(aggregate.cash_marketplace_fees)),
            cashStat('Affiliate / AMS', money(aggregate.cash_affiliate_fees)),
            cashStat('Refund / adjustment', money(aggregate.cash_refund)),
        ].join('');
        const feeBreakdown = isFeeFocus ? `<div class="an-modal-fee-breakdown"><div><span>Total fee marketplace</span><strong>${money(aggregate.cash_marketplace_fees)}<small>${feePercent(aggregate.cash_marketplace_fees)}</small></strong></div><div><span>Administrasi</span><strong>${money(aggregate.cash_commission_fee)}<small>${feePercent(aggregate.cash_commission_fee)}</small></strong></div><div><span>Layanan</span><strong>${money(aggregate.cash_service_fee)}<small>${feePercent(aggregate.cash_service_fee)}</small></strong></div><div><span>Transaksi</span><strong>${money(aggregate.cash_transaction_fee)}<small>${feePercent(aggregate.cash_transaction_fee)}</small></strong></div><div><span>Asuransi</span><strong>${money(aggregate.cash_shipping_insurance_fee)}<small>${feePercent(aggregate.cash_shipping_insurance_fee)}</small></strong></div><div><span>Pajak escrow</span><strong>${money(aggregate.cash_escrow_tax)}<small>${feePercent(aggregate.cash_escrow_tax)}</small></strong></div></div>` : '';
        const renderRows = groupRows => groupRows.map(row => {
            const rowSettled = Boolean(row.settlement_time);
            const payout = rowSettled && n(row.cash_payout) > 0 ? money(row.cash_payout) : (n(row.cash_payout) > 0 ? money(row.cash_payout) : 'Belum tersedia');
            return `<tr><td><div class="an-cash-order">${esc(row.channel_order_id)}</div><span class="an-cash-meta">Order ${esc(dateTime(row.ordered_at))}</span><details class="an-cash-detail"><summary>Rincian fee</summary><div class="an-cash-detail-grid">${cashFeeDetail(row, 'Administrasi', row.commission_fee, 'fee')}${cashFeeDetail(row, 'Layanan', row.service_fee, 'fee')}${cashFeeDetail(row, 'Transaksi', row.transaction_fee, 'fee')}${cashFeeDetail(row, 'Asuransi', row.shipping_insurance_fee, 'fee')}${cashFeeDetail(row, 'Pajak escrow', row.escrow_tax, 'fee')}${cashFeeDetail(row, 'Affiliate fee', row.affiliate_fee_raw, 'affiliate')}${cashFeeDetail(row, 'Activity / AMS', row.activity_fee, 'affiliate')}${cashFeeDetail(row, 'Refund', row.refund)}${cashFeeDetail(row, 'Total fee', row.total_fees, 'fee')}</div></details></td><td><div>${esc(row.store_name)}</div><span class="an-cash-status">${esc(statusLabel(row))}</span><span class="an-cash-meta">Payout: ${settlementLabel(row)}</span></td><td><span class="an-cash-payment">${esc(paymentLabel(row.payment_method))}</span></td><td class="an-cash-money">${money(row.gross_sales)}</td><td class="an-cash-money">${money(row.buyer_payment_amount)}</td><td class="an-cash-money ${rowSettled ? 'good' : ''}">${payout}</td><td class="an-cash-money fee">${money(row.marketplace_fee)}</td><td class="an-cash-money affiliate">${money(row.affiliate_fee)}</td></tr>`;
        }).join('');
        const renderTable = (groupRows, groupLabel = '') => groupRows.length ? `${groupLabel ? `<div class="an-cash-group-head"><strong>${esc(groupLabel)}</strong><span>${groupRows.length.toLocaleString('id-ID')} order</span></div>` : ''}<div class="an-table-wrap"><table class="an-table an-cash-table"><thead><tr><th>Order</th><th>Toko &amp; status</th><th>Metode pembayaran</th><th>${isAll ? 'Nilai order' : 'Omzet order'}</th><th>Pembayaran pembeli</th><th>${isSettled ? 'Omzet cair' : 'Payout tercatat'}</th><th>Fee marketplace</th><th>Affiliate / AMS</th></tr></thead><tbody>${renderRows(groupRows)}</tbody></table></div>` : '';
        const groups = isAll
            ? ['completed', 'shipped', 'confirm', 'return_refund', 'cancelled', 'other'].map(key => ({ key, label: rows.find(row => row.status_group === key)?.status_group_label || key, rows: rows.filter(row => row.status_group === key) })).filter(group => group.rows.length)
            : [{ key: 'flat', label: '', rows }];
        const table = groups.length ? groups.map(group => `<section class="an-cash-group">${renderTable(group.rows, isAll ? group.label : '')}</section>`).join('') : `<div class="an-empty">Tidak ada order pada periode ini.</div>`;
        const body = isFeeFocus
            ? (rows.length ? renderTable(rows) : '<div class="an-empty">Tidak ada order pada periode ini.</div>')
            : table;
        $('cashOrdersBody').innerHTML = feeBreakdown + body;
        const currentPage = Number(meta.current_page || cashPage);
        const lastPage = Number(meta.last_page || 1);
        $('cashOrdersPage').textContent = `Halaman ${currentPage} / ${lastPage}`;
        $('cashOrdersPrev').disabled = cashLoading || currentPage <= 1;
        $('cashOrdersNext').disabled = cashLoading || currentPage >= lastPage;
    }
    function closeCashOrders() {
        $('cashOrdersModal').classList.remove('is-open');
        $('cashOrdersModal').setAttribute('aria-hidden', 'true');
        document.body.classList.remove('an-modal-open');
    }
    function setCashSettlementTab(value) {
        cashSettlement = ['all', 'settled', 'shipped', 'confirm', 'cancelled', 'return_refund', 'unsettled'].includes(value) ? value : 'settled';
        document.querySelectorAll('[data-cash-settlement]').forEach(button => {
            const active = button.dataset.cashSettlement === cashSettlement;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }
    async function loadCashOrders() {
        if (cashLoading) return;
        cashLoading = true;
        $('cashOrdersBody').innerHTML = '<div class="an-empty">Memuat order…</div>';
        $('cashOrdersSummary').innerHTML = '<div class="an-empty">Memuat ringkasan…</div>';
        $('cashOrdersPrev').disabled = true;
        $('cashOrdersNext').disabled = true;
        try {
            const params = new URLSearchParams({ date_from: from(), date_to: to(), settlement: cashSettlement, page: String(cashPage), per_page: '50', _ts: Date.now().toString() });
            if (selectedStore()) params.set('store_id', selectedStore());
            cashPayload = await api('/api/marketplace/analytics-cash-orders?' + params.toString(), { cache: 'no-store' });
            renderCashOrders();
        } catch (e) {
            console.error('Cash order detail load failed', e);
            $('cashOrdersSummary').innerHTML = '<div class="an-error">Ringkasan order cair gagal dimuat.</div>';
            $('cashOrdersBody').innerHTML = '<div class="an-error">Detail order cair gagal dimuat.</div>';
            $('cashOrdersPage').textContent = '—';
        } finally {
            cashLoading = false;
            if (cashPayload) renderCashOrders();
        }
    }
    function openCashOrders() {
        cashFocus = 'payout';
        $('cashOrdersModal').classList.add('is-open');
        $('cashOrdersModal').setAttribute('aria-hidden', 'false');
        document.body.classList.add('an-modal-open');
        cashPage = 1;
        cashPayload = null;
        setCashSettlementTab('all');
        $('cashOrdersSubtitle').textContent = `${from()} — ${to()} · semua status`;
        loadCashOrders();
    }
    function openFeeOrders() {
        closeReturnOrders();
        cashFocus = 'fees';
        $('cashOrdersModal').classList.add('is-open');
        $('cashOrdersModal').setAttribute('aria-hidden', 'false');
        document.body.classList.add('an-modal-open');
        cashPage = 1;
        cashPayload = null;
        setCashSettlementTab('settled');
        $('cashOrdersSubtitle').textContent = `${from()} — ${to()} · settlement complete`;
        loadCashOrders();
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
        const list = summary?.stores || [];
        const contributing = list.filter(store => Number(store.gross_sales || 0) > 0);
        const totalGross = contributing.reduce((sum, store) => sum + Number(store.gross_sales || 0), 0);
        const top = [...contributing].sort((a,b) => Number(b.gross_sales || 0) - Number(a.gross_sales || 0))[0];
        const bestMargin = [...contributing].sort((a,b) => Number(b.margin_pct || 0) - Number(a.margin_pct || 0))[0];
        $('anStorePulse').innerHTML = [
            ['Toko berkontribusi', Number(contributing.length || 0).toLocaleString('id-ID'), 'dengan gross sales terverifikasi'],
            ['Kontributor utama', top?.store_name || '—', top ? `${(Number(top.gross_sales || 0) / Math.max(totalGross,1) * 100).toFixed(1)}% gross sales` : '—'],
            ['Margin terbaik', bestMargin?.store_name || '—', bestMargin ? `${Number(bestMargin.margin_pct || 0).toFixed(1)}% profit operasional` : '—'],
        ].map(([label,value,note]) => `<div class="an-pulse"><div class="an-pulse-label">${label}</div><div class="an-pulse-value" style="font-size:${String(value).length > 16 ? '.78rem' : '1rem'}">${esc(value)}</div><div class="an-pulse-note">${note}</div></div>`).join('');
        const storeBase = Math.max(totalGross, 1);
        $('anStoreCostPulse').innerHTML = [
            ['Payout / gross sales', list.reduce((sum, store) => sum + Number(store.payout || 0), 0) / storeBase * 100],
            ['Fee marketplace / gross sales', list.reduce((sum, store) => sum + Number(store.marketplace_fees || 0), 0) / storeBase * 100],
            ['HPP / gross sales', list.reduce((sum, store) => sum + Number(store.hpp || 0), 0) / storeBase * 100],
            ['Iklan / gross sales', list.reduce((sum, store) => sum + Number(store.ad_cost || 0), 0) / storeBase * 100],
        ].map(([label,value]) => `<div class="an-health-row"><span>${label}<small>basis: gross sales terverifikasi</small></span><div class="an-health-track"><span style="width:${Math.min(100, Math.max(0, value))}%"></span></div><strong>${Number(value || 0).toFixed(1)}%</strong></div>`).join('');
        $('storeBody').innerHTML = list.length ? list.map(s=>`<tr><td style="text-align:left;font-weight:850;color:#0f172a">${esc(s.store_name || 'Tanpa toko')}</td><td>${Number(s.order_total || 0).toLocaleString('id-ID')}</td><td>${Number(s.completed_count || 0).toLocaleString('id-ID')} <small style="color:#94a3b8">(${pct(s.completed_count,s.order_total)})</small></td><td style="color:${s.cancelled_count?'#dc2626':'inherit'}">${Number(s.cancelled_count || 0).toLocaleString('id-ID')}</td><td style="font-weight:900">${money(s.gross_sales)}<span class="an-table-subline">AOV financial-ready ${money(s.order_count ? s.gross_sales / s.order_count : 0)}</span></td><td style="font-weight:900;color:${s.operating_profit>=0?'#15803d':'#dc2626'}">${money(s.operating_profit)}<span class="an-table-subline">Payout − HPP − iklan · Iklan ${money(s.ad_cost)}</span></td></tr>`).join('') : '<tr><td colspan="6"><div class="an-empty">Belum ada data toko siap profit.</div></td></tr>';
    }
    function renderCosts() {
        const current = summary?.current || {};
        const orderRevenue = Number(current.cash_order_revenue || 0) + Number(current.cash_unsettled_order_revenue || 0);
        const base = Math.max(orderRevenue || Number(current.gmv || current.gross_sales || 0), 1);
        const rows = [
            [`Fee marketplace (estimasi ${(Number(current.marketplace_fee_estimate_rate || 0)).toFixed(1)}% actual)`, current.marketplace_fee_estimate],
            ['Refund / adjustment', current.return_refund_amount ?? current.refund],
            ['Biaya iklan (incl. PPN 11%)', current.ad_cost],
            ['HPP total', current.hpp_total ?? current.hpp],
        ];
        $('costBody').innerHTML = rows.map(([label,value]) => `<div class="an-cost-row"><span>${label} <small style="color:#94a3b8">(${(Number(value || 0) / base * 100).toFixed(1)}%)</small></span><strong>${money(value)}</strong><div class="an-bar" style="grid-column:1/-1"><span style="width:${Math.min(100, Math.max(0, Number(value || 0) / base * 100))}%"></span></div></div>`).join('');
    }
