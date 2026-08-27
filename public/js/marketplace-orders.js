
const IS_DUMMY_MODE = window.IS_DUMMY_MODE;

(function () {
    // Force header actions ke satu baris (display:block → flex)
    document.addEventListener('DOMContentLoaded', () => {
        const actions = document.querySelector('.gf-master-actions');
        if (actions) {
            actions.style.display        = 'flex';
            actions.style.flexDirection  = 'row';
            actions.style.flexWrap       = 'nowrap';
            actions.style.alignItems     = 'center';
            actions.style.gap            = '.4rem';
            actions.style.justifyContent = 'flex-end';
        }
    });

    const { api, fmt, fmtDate, fmtRp, esc, channelPill, statusBadge } = window.mpHelpers;
    
    function orderStatusBadge(s) {
        if (!s) return `<span class="badge-status st-draft">—</span>`;
        if (['UNPAID'].includes(s)) return `<span class="badge-status st-draft">${esc(s)}</span>`;
        if (['READY_TO_SHIP', 'PROCESSED', 'READY_TO_HANDOVER'].includes(s)) return `<span class="badge-status st-submitted">${esc(s)}</span>`;
        if (['SHIPPED', 'TO_CONFIRM_RECEIVE', 'COMPLETED'].includes(s)) return `<span class="badge-status st-posted">${esc(s)}</span>`;
        if (['CANCELLED', 'IN_CANCEL', 'TO_RETURN'].includes(s)) return `<span class="badge-status st-cancelled">${esc(s)}</span>`;
        return `<span class="badge-status st-draft">${esc(s)}</span>`;
    }

    function orderDateText(value) {
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return fmt(value);

        return date.toLocaleString('id-ID', {
            weekday: 'short',
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function shippingStatusFallback(status) {
        const labels = {
            UNPAID: 'Belum dibayar',
            READY_TO_SHIP: 'Siap dikirim',
            PROCESSED: 'Sedang diproses',
            READY_TO_HANDOVER: 'Siap diserahkan',
            SHIPPED: 'Sudah dikirim',
            TO_CONFIRM_RECEIVE: 'Menunggu konfirmasi',
            COMPLETED: 'Selesai',
            CANCELLED: 'Dibatalkan',
        };
        return labels[String(status || '').toUpperCase()] || '';
    }

    function orderCopyHtml(orderNumber) {
        const value = String(orderNumber || '—');
        return `<button type="button" class="ord-order-copy" data-order-number="${esc(value)}"
            onclick="event.stopPropagation(); window.copyOrderNumber(this)"
            title="Klik untuk menyalin nomor order">${esc(value)}</button>`;
    }

    window.copyOrderNumber = async function (button) {
        const value = button?.getAttribute('data-order-number') || '';
        if (!value || value === '—') return;

        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(value);
            } else {
                const input = document.createElement('textarea');
                input.value = value;
                input.style.position = 'fixed';
                input.style.opacity = '0';
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                input.remove();
            }
            const original = button.innerHTML;
            button.classList.add('is-copied');
            button.innerHTML = '✓ Disalin';
            window.setTimeout(() => {
                if (button.isConnected) {
                    button.classList.remove('is-copied');
                    button.innerHTML = original;
                }
            }, 1200);
        } catch (error) {
            console.warn('Gagal menyalin nomor order:', error);
        }
    };

    function buyerPaidAmount(o) {
        const escrowPaid = Number(o.settlement?.buyer_payment_amount);
        return Number.isFinite(escrowPaid) && escrowPaid >= 0 ? escrowPaid : null;
    }

    function sellerSellingPriceAmount(o) {
        const normalized = o.settlement?.order_selling_price;
        if (normalized !== null && normalized !== undefined && normalized !== '') {
            const value = Number(normalized);
            if (Number.isFinite(value)) return Math.abs(value);
        }

        const raw = o.settlement?.raw_json;
        if (!raw || typeof raw !== 'object' || !Object.prototype.hasOwnProperty.call(raw, 'order_selling_price')) return null;

        const value = Number(raw.order_selling_price);
        return Number.isFinite(value) ? Math.abs(value) : null;
    }

    function buyerPaidHtml(o) {
        const buyerPaid = buyerPaidAmount(o);
        const sellerSellingPrice = sellerSellingPriceAmount(o);
        if (buyerPaid === null && sellerSellingPrice === null) return '<span class="ord-payment-empty">—</span>';

        return `<div class="ord-payment-stack">
            ${buyerPaid !== null ? `<div class="ord-payment-line buyer" title="Nominal dibayar pembeli dari Escrow Detail Shopee">
                <span class="ord-payment-label">Dibayar pembeli</span>
                <strong>${esc(fmtRp(buyerPaid))}</strong>
            </div>` : ''}
            ${sellerSellingPrice !== null ? `<div class="ord-payment-line seller" title="Total harga jual seller sebelum promo dan voucher dari Escrow Detail Shopee">
                <span class="ord-payment-label">Harga jual seller</span>
                <span class="ord-payment-note">sebelum promo/voucher</span>
                <strong>${esc(fmtRp(sellerSellingPrice))}</strong>
            </div>` : ''}
        </div>`;
    }

    function paymentMethodLabel(o) {
        const raw = o.settlement?.raw_json?.buyer_payment_method
            || o.settlement?.raw_json?.payment_method
            || '';
        const value = String(raw).trim();
        if (!value) return 'Belum tercatat';

        const labels = {
            cod: 'COD',
            cash_on_delivery: 'COD',
            shopeepay: 'ShopeePay',
            shopee_pay: 'ShopeePay',
            spaylater: 'SPayLater',
            credit_card: 'Kartu Kredit',
            debit_card: 'Kartu Debit',
            bank_transfer: 'Transfer Bank',
        };
        const key = value.toLowerCase().replace(/[\s-]+/g, '_');
        return labels[key] || value.replace(/_/g, ' ');
    }

    function buyerPaymentMethodHtml(o) {
        return `<div class="ord-payment-method" title="Metode pembayaran">
            <span>💳 ${esc(paymentMethodLabel(o))}</span>
        </div>`;
    }

    function voucherAmount(o, keys) {
        const sources = [
            o.settlement,
            o.settlement?.raw_json,
        ];
        const values = sources.flatMap(source => keys.map(key => Number(source?.[key])))
            .filter(value => Number.isFinite(value) && value > 0);

        if (values.length) return Math.abs(values[0]);

        // Beberapa response escrow hanya mengisi voucher pada masing-masing
        // item, bukan pada level order. Jumlahkan satu alias pertama yang
        // tersedia per item agar nilai tidak terhitung ganda.
        const itemLists = sources.flatMap(source => [source?.items, source?.item_list])
            .filter(items => Array.isArray(items));
        return itemLists.flat().reduce((total, item) => {
            const itemValue = keys.map(key => Number(item?.[key]))
                .find(value => Number.isFinite(value) && value > 0) || 0;
            return total + Math.abs(itemValue);
        }, 0);
    }

    function voucherSummaryHtml(o) {
        const sellerVoucher = voucherAmount(o, ['voucher_toko_total', 'voucher_from_seller']);
        const shopeeVoucher = voucherAmount(o, ['voucher_platform_total', 'voucher_from_shopee']);
        const externalVoucher = voucherAmount(o, ['voucher_external_total', 'voucher_from_external_party']);
        const bundleDiscount = voucherAmount(o, ['bundle_discount_total', 'bundle_discount', 'bundle_discount_amount', 'discount_from_bundle', 'discount_from_bundle_deal']);
        const hasVoucherPayload = !!o.settlement;
        if (!hasVoucherPayload && sellerVoucher <= 0 && shopeeVoucher <= 0 && externalVoucher <= 0 && bundleDiscount <= 0) return '';
        const valueHtml = value => hasVoucherPayload ? esc(fmtRp(value)) : 'Belum tersedia';

        return `<div class="ord-voucher-summary" title="Voucher dan diskon dari Escrow Detail Shopee">
            <span class="ord-voucher-chip bundle">🎁 Bundle <strong>${valueHtml(bundleDiscount)}</strong></span>
            <span class="ord-voucher-chip seller">🏷️ Penjual <strong>${valueHtml(sellerVoucher)}</strong></span>
            <span class="ord-voucher-chip shopee">🛍️ Shopee <strong>${valueHtml(shopeeVoucher)}</strong></span>
            <span class="ord-voucher-chip external">🤝 Eksternal <strong>${valueHtml(externalVoucher)}</strong></span>
        </div>`;
    }

    function amsAmount(o) {
        const normalized = o.settlement?.ams_total;
        if (normalized !== null && normalized !== undefined && normalized !== '') {
            const value = Number(normalized);
            if (Number.isFinite(value) && value >= 0) return value;
        }

        const raw = o.settlement?.raw_json;
        if (!raw || typeof raw !== 'object') return null;

        for (const key of ['order_ams_commission_fee', 'ams_commission_fee']) {
            if (Object.prototype.hasOwnProperty.call(raw, key)) {
                const value = Number(raw[key]);
                if (Number.isFinite(value) && value > 0) return Math.abs(value);
            }
        }

        if (Array.isArray(raw.items)) {
            const itemValues = raw.items
                .map(item => Number(item?.ams_commission_fee))
                .filter(value => Number.isFinite(value) && value >= 0);
            if (itemValues.length) return itemValues.reduce((total, value) => total + Math.abs(value), 0);
        }

        return null;
    }

    function amsHtml(o) {
        const amount = amsAmount(o);
        if (amount === null) return '<span class="ord-payment-empty">—</span>';

        return `<span class="ord-ams-value" title="AMS dari Escrow Detail Shopee">${esc(fmtRp(amount))}</span>`;
    }

    function escrowAmount(o) {
        const normalized = o.settlement?.escrow_amount;
        if (normalized !== null && normalized !== undefined && normalized !== '') {
            const value = Number(normalized);
            if (Number.isFinite(value)) return Math.abs(value);
        }

        const raw = o.settlement?.raw_json;
        if (!raw || typeof raw !== 'object' || !Object.prototype.hasOwnProperty.call(raw, 'escrow_amount')) return null;

        const value = Number(raw.escrow_amount);
        return Number.isFinite(value) ? Math.abs(value) : null;
    }

    function escrowHtml(o) {
        const amount = escrowAmount(o);
        if (amount === null) return '<span class="ord-payment-empty">—</span>';

        const isActual = Boolean(o.settlement?.settlement_time);
        const estimatedAmount = o.settlement?.estimated_escrow_amount !== null && o.settlement?.estimated_escrow_amount !== undefined
            ? Number(o.settlement.estimated_escrow_amount)
            : null;
        const hasEstimatedAmount = Number.isFinite(estimatedAmount);
        const valueHtml = (status, value, statusTitle) => `<span class="ord-income-value ${status === 'ACT' ? 'actual' : 'estimated'}" title="Penghasilan dari Escrow Detail Shopee — ${statusTitle}">
            <span class="ord-income-status">${status}</span>
            <strong>${value === null ? '—' : esc(fmtRp(value))}</strong>
        </span>`;

        if (!isActual) {
            return valueHtml('EST', amount, 'Masih estimasi, belum ada settlement time');
        }

        return `<div class="ord-income-stack">
            ${valueHtml('EST', hasEstimatedAmount ? Math.abs(estimatedAmount) : null, hasEstimatedAmount ? 'Estimasi penghasilan sebelum pencairan' : 'Estimasi belum tersedia')}
            ${valueHtml('ACT', amount, 'Sudah cair berdasarkan settlement time')}
        </div>`;
    }

    function itemPaymentSummaryHtml(o) {
        return `<div class="ord-item-payment-summary">${buyerPaidHtml(o)}${voucherSummaryHtml(o)}</div>`;
    }

    function orderContextHtml(o) {
        const storeName = o.store?.name || '';
        const channel = o.store?.channel || null;
        const repeatOrder = Number(o.buyer_previous_order_count || 0);
        const repeatBuyer = o.is_repeat_buyer === true || repeatOrder > 0;
        return `<div class="ord-order-context">
            ${orderStatusBadge(o.order_status)}
            ${storeName ? `<span class="ord-order-store">🏪 ${esc(storeName)}</span>` : ''}
            ${channel ? channelPill(channel) : ''}
            ${repeatBuyer ? `<span class="ord-repeat-badge" title="Pembeli ini sudah pernah melakukan ${repeatOrder} pembelian sebelumnya">🔁 Repeat order · ke-${repeatOrder + 1}</span>` : ''}
        </div>`;
    }

    function itemSalePriceHtml(i) {
        // Payload orders lama/regular memakai model_* sementara payload item
        // tersimpan memakai price_*. Ambil nilai positif pertama agar harga
        // tanpa diskon tetap tampil walaupun salah satu field bernilai 0.
        const positivePrice = (...values) => values
            .map(value => Number(value))
            .find(value => Number.isFinite(value) && value > 0) || 0;
        const original = positivePrice(
            i.price_original,
            i.model_original_price,
            i.original_price,
            i.price,
            i.item_original_price,
        );
        const discounted = positivePrice(
            i.price_after_discount,
            i.model_discounted_price,
            i.discounted_price,
        );
        const sale = discounted > 0 ? discounted : original;
        if (!Number.isFinite(sale) || sale <= 0) return '';

        const qty = Math.max(1, Number(i.qty) || 1);
        const lineTotal = sale * qty;
        const originalHtml = Number.isFinite(original) && original > sale
            ? `<del>${esc(fmtRp(original))}</del>`
            : '';
        return `<div class="ord-item-price" title="Harga jual setelah diskon">
            ${originalHtml}<span>${esc(fmtRp(sale))} / item</span>
            ${qty > 1 ? `<strong>${esc(fmtRp(lineTotal))}</strong>` : ''}
        </div>`;
    }

    let orders           = [];
    let currentPage      = 1;
    let lastPage         = 1;
    let totalOrders      = 0;
    let ordersLoadSeq     = 0;
    let orderCounts       = null;
    let searchReloadTimer = null;

    const urlParams = new URLSearchParams(window.location.search);
    const urlTab = urlParams.get('tab');
    
    let initialTab = 'ready';
    if (urlTab === 'ready_to_ship') initialTab = 'ready';
    else if (urlTab) initialTab = urlTab;
    
    // Selalu reset ke default saat halaman dimuat ulang / pindah halaman
    sessionStorage.setItem('ord_active_tab', initialTab);
    sessionStorage.setItem('ord_sub_tab_processed', 'packing');
    sessionStorage.setItem('ord_sub_tab_ready', 'process');
    
    let activeTab        = initialTab;
    let subTabProcessed  = 'packing';
    let subTabReady      = 'process';
    let subTabShipped    = 'shipped';
    // store_id passed via URL is an ID, but activeStore in JS requires the store name.
    // We will resolve it later during data load if it's an ID, or just set it if it matches.
    let activeStore      = urlParams.get('store_id') || '';
    let fulfilledOrderIds    = new Set();   // order ID yang sudah punya fulfillment confirmed
    let printedOrderIds      = new Set();   // order ID yang sudah dicetak picking list
    let printedDocOrderSns   = new Set();   // channel_order_id yang sudah dicetak resi
    let currentFulfillment   = null;        // fulfillment aktif di modal
    let fulfillmentStatusMap = new Map();   // order_id → {id, status} — pre-loaded dari API

    const $ = id => document.getElementById(id);
    const getFrom   = () => $('mpDateFrom').value;
    const getTo     = () => $('mpDateTo').value;
    const getSearch = () => ($('filterSearch').value || '').toLowerCase().trim();

    // Semua reload order wajib memakai scope yang sama. Sebelumnya silent
    // refresh memanggil endpoint polos sehingga setelah beberapa detik data
    // seluruh database menimpa hasil halaman yang sudah difilter.
    function localOrdersUrl() {
        const lp = new URLSearchParams();
        if (typeof IS_DUMMY_MODE !== 'undefined' && IS_DUMMY_MODE) lp.set('dummy', '1');
        if (getFrom()) lp.set('date_from', getFrom());
        if (getTo())   lp.set('date_to', getTo());
        
        lp.set('tab', activeTab);
        if (activeTab === 'ready') lp.set('sub_tab', subTabReady);
        else if (activeTab === 'processed') lp.set('sub_tab', subTabProcessed);
        else if (activeTab === 'shipped') lp.set('sub_tab', subTabShipped);
        
        if (getSearch()) lp.set('search', getSearch());
        if (activeStore && activeStore !== '') lp.set('store', activeStore);
        lp.set('page', currentPage);
        lp.set('limit', 50);

        return '/api/marketplace/local-orders-paginated' + (lp.toString() ? ('?' + lp.toString()) : '');
    }

    function localOrderCountsUrl() {
        const lp = new URLSearchParams();
        if (typeof IS_DUMMY_MODE !== 'undefined' && IS_DUMMY_MODE) lp.set('dummy', '1');
        if (getFrom()) lp.set('date_from', getFrom());
        if (getTo())   lp.set('date_to', getTo());
        if (getSearch()) lp.set('search', getSearch());
        if (activeStore && activeStore !== '') lp.set('store', activeStore);

        return '/api/marketplace/local-order-counts' + (lp.toString() ? ('?' + lp.toString()) : '');
    }

    // Endpoint ini memakai paginator Laravel (res.data adalah array), tetapi
    // beberapa refresh lama masih mengembalikan array langsung. Normalisasi di
    // satu tempat supaya render tidak pernah menerima object paginator mentah.
    function applyOrdersResponse(res) {
        const payload = Array.isArray(res) ? { data: res } : (res || {});
        orders = Array.isArray(payload.data) ? payload.data : [];
        currentPage = Number(payload.current_page) || 1;
        lastPage = Number(payload.last_page) || 1;
        totalOrders = Number(payload.total) || orders.length;
        return orders;
    }

    // Status order yang dianggap "aktif" (perlu proses / sedang packing)
    const ACTIVE_ORDER_STATUSES = ['READY_TO_SHIP', 'MATCHED', 'PROCESSED'];

    const TAB_STATUSES = {
        all:        null,
        unpaid:     ['UNPAID'],
        ready:      ['READY_TO_SHIP', 'MATCHED'],
        processed:  ['PROCESSED'],
        shipped:    ['SHIPPED', 'TO_CONFIRM_RECEIVE'],
        completed:  ['COMPLETED'],
        issues:     null, // via TAB_FILTERS
    };

    // Semua filter berbasis fungsi
    const TAB_FILTERS = {
        issues:     o => o.has_data_issues === true,
    };

    const TAB_EMPTY = {
        all:        { icon: '📋', text: 'Belum ada order di periode ini.' },
        process:    { icon: '✅', text: 'Semua order sudah di-scan. Cek tab Sedang Packing!' },
        packing:    { icon: '📦', text: 'Belum ada order yang sedang dipacking di periode ini.' },
        unresolved: { icon: '✅', text: 'Tidak ada order yang perlu diperbaiki. Semua item sudah ter-mapping.' },
        fulfilled:  { icon: '🎉', text: 'Belum ada order yang sudah diproses di periode ini.' },
        shipping:   { icon: '🚚', text: 'Tidak ada order dalam pengiriman.' },
        done:       { icon: '🎉', text: 'Belum ada order selesai di periode ini.' },
        cancel:     { icon: '🙂', text: 'Tidak ada order yang dibatalkan.' },
    };

    // ── Dropdown toggle ───────────────────────────────────────────────────
    // Header (.ship-topbar) memakai overflow-x:auto di desktop, yang IKUT memotong
    // dropdown secara vertikal. Supaya tidak tertutup, saat dibuka dropdown
    // dipromosikan ke position:fixed dan diposisikan tepat di bawah tombolnya.
    function closeAllDropdowns() {
        document.querySelectorAll('.hdr-dropdown.open').forEach(d => {
            d.classList.remove('open');
            d.style.position = ''; d.style.top = ''; d.style.left = ''; d.style.right = '';
        });
    }
    function positionDropdown(dd, btn) {
        const r = btn.getBoundingClientRect();
        dd.style.position = 'fixed';
        dd.style.top = (r.bottom + 8) + 'px';
        // Rata kanan dengan tombol, tapi jangan sampai keluar layar
        const w = dd.offsetWidth;
        let left = r.right - w;
        const maxLeft = window.innerWidth - w - 8;
        if (left > maxLeft) left = maxLeft;
        if (left < 8) left = 8;
        dd.style.left = left + 'px';
        dd.style.right = 'auto';
    }
    window.toggleDropdown = function (id, e) {
        e.stopPropagation();
        const dd = $(id);
        const btn = e.currentTarget;
        const isOpen = dd.classList.contains('open');
        closeAllDropdowns();
        if (!isOpen) {
            dd.classList.add('open');       // tampilkan dulu agar offsetWidth terukur
            positionDropdown(dd, btn);
        }
    };
    document.addEventListener('click', closeAllDropdowns);
    // Tutup saat scroll/resize agar posisi fixed tidak "menggantung"
    window.addEventListener('scroll', closeAllDropdowns, true);
    window.addEventListener('resize', closeAllDropdowns);

    // ── Date presets ──────────────────────────────────────────────────────
    const PRESET_LABELS = { 1: 'Hari ini', 7: '7 hari', 30: '30 hari', 90: '90 hari' };

    window.setDatePreset = function (days) {
        const to   = new Date();
        const from = new Date(Date.now() - (days - 1) * 86400000);
        const fmt  = d => d.toISOString().slice(0,10);
        $('mpDateFrom').value  = fmt(from);
        $('mpDateTo').value    = fmt(to);
        $('mpDateRange').value = fmt(from) + ' — ' + fmt(to);
        $('btnDateLabel').textContent = PRESET_LABELS[days] || (days + ' hari');
        document.querySelectorAll('#ddDate .hdr-dropdown-item').forEach(el => el.classList.remove('selected'));
        event.target.closest('.hdr-dropdown-item').classList.add('selected');
        $('ddDate').classList.remove('open');
        history.replaceState(null, '', location.pathname + '?date_from=' + fmt(from) + '&date_to=' + fmt(to));
        loadOrders(); // muat ulang dari server agar order lama (backfill) ikut terambil
    };

    // ── Flatpickr (manual date) ───────────────────────────────────────────
    if (window.flatpickr) {
        flatpickr($('mpDateRange'), {
            mode: 'range', dateFormat: 'Y-m-d',
            defaultDate: [getFrom(), getTo()],
            onChange(dates) {
                if (dates.length === 2) {
                    const fmt  = d => d.toISOString().slice(0,10);
                    const fmtS = d => d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                    $('mpDateFrom').value  = fmt(dates[0]);
                    $('mpDateTo').value    = fmt(dates[1]);
                    $('mpDateRange').value = fmt(dates[0]) + ' — ' + fmt(dates[1]);
                    $('btnDateLabel').textContent = fmtS(dates[0]) + '–' + fmtS(dates[1]);
                    history.replaceState(null, '', location.pathname + '?date_from=' + getFrom() + '&date_to=' + getTo());
                    $('ddDate').classList.remove('open');
                    loadOrders(); // muat ulang dari server agar order lama (backfill) ikut terambil
                }
            }
        });
    }

    // ── Search ────────────────────────────────────────────────────────────
    window.onSearchInput = function (input) {
        const clearBtn = $('searchClearBtn');
        clearBtn.classList.toggle('visible', input.value.length > 0);
        render();

        clearTimeout(searchReloadTimer);
        searchReloadTimer = setTimeout(() => loadOrders(), 300);
    };

    window.clearSearch = function () {
        $('filterSearch').value = '';
        $('searchClearBtn').classList.remove('visible');
        $('filterSearch').focus();
        loadOrders();
    };

    // ── Store dropdown ────────────────────────────────────────────────────
    function populateStoreDropdown() {
        const names = [...new Set(orders.map(o => o.store?.name).filter(Boolean))].sort();
        const el = $('storeDropdownItems');
        el.innerHTML = `<div class="hdr-dropdown-item ${!activeStore?'selected':''}" onclick="selectStore('')">🏪 Semua Toko</div>` +
            names.map(n => `<div class="hdr-dropdown-item ${activeStore===n?'selected':''}" onclick="selectStore('${esc(n)}')">${esc(n)}</div>`).join('');
    }

    // ── Sync Latar Belakang / Backfill Histori (dropdown "⏱ Latar Belakang") ──
    // Endpoint yang dipanggil SUDAH ADA sebelumnya di MarketplaceController & routes/web.php:
    //   - sync-orders-background (syncOrdersBackground): sync pesanan N hari terakhir via queue,
    //     dipakai untuk "Sync Latar Belakang" (default 60 hari, batas maksimum command ini).
    //   - sync-historical (syncHistorical): backfill histori order+retur per tahun via queue,
    //     dipakai untuk "Backfill Histori". Route ini sebelumnya belum di-exempt dari CSRF
    //     (beda dengan sibling route lain seperti sync-orders/sync-orders-background), sudah
    //     disamakan di routes/web.php agar konsisten dengan pola CSRF-exempt untuk semua POST
    //     API marketplace yang dipanggil via window.mpHelpers.api() (fetch tanpa token CSRF).
    // TIDAK membuat endpoint/job baru, TIDAK mengubah openQuickSync()/runQuickSync().
    let bgSyncStoresCache = null;

    // ── Progres sync latar belakang (per toko) ───────────────────────────
    // State disimpan di memori agar bar tetap muncul saat dropdown ditutup lalu
    // dibuka lagi. Polling ke endpoint /sync-progress (dibaca dari Cache server).
    const bgSyncState = {};    // { [storeId]: {percent,label,status,...} }
    const bgSyncPollers = {};  // { [storeId]: intervalId }

    function renderBgProgress(storeId, p) {
        const box = document.getElementById('bgprog-' + storeId);
        if (!box) return; // dropdown sedang tertutup — state tetap tersimpan
        if (!p || p.status === 'idle') { box.style.display = 'none'; return; }
        box.style.display = '';
        const bar = document.getElementById('bgprog-bar-' + storeId);
        const label = document.getElementById('bgprog-label-' + storeId);
        const pct = (p.percent == null) ? 0 : p.percent;
        bar.style.width = pct + '%';
        bar.style.background = p.status === 'error' ? '#dc2626'
            : (p.status === 'done' ? '#16a34a' : '#0f172a');
        const icon = p.status === 'queued' ? '⏳' : p.status === 'done' ? '✅'
            : p.status === 'error' ? '⚠️' : '🔄';
        label.textContent = `${icon} ${pct}% · ${p.label || ''}`;
    }

    async function pollBgProgress(storeId) {
        try {
            const p = await api(`/api/marketplace/stores/${storeId}/sync-progress`);
            bgSyncState[storeId] = p;
            renderBgProgress(storeId, p);
            if (['done', 'error', 'idle'].includes(p.status)) {
                clearInterval(bgSyncPollers[storeId]);
                delete bgSyncPollers[storeId];
                if (p.status === 'idle') delete bgSyncState[storeId];
            }
        } catch (e) { /* abaikan error sesaat, coba lagi di tick berikutnya */ }
    }

    function startBgPoll(storeId) {
        if (bgSyncPollers[storeId]) return;
        pollBgProgress(storeId); // langsung sekali
        bgSyncPollers[storeId] = setInterval(() => pollBgProgress(storeId), 2500);
    }

    async function populateBgSyncDropdown() {
        const el = $('bgSyncDropdownItems');
        el.innerHTML = '<div style="padding:.4rem;font-size:.72rem;color:#94a3b8">Memuat toko…</div>';
        try {
            if (!bgSyncStoresCache) {
                bgSyncStoresCache = await api('/api/marketplace/stores');
            }
            const activeStores = bgSyncStoresCache.filter(s => s.is_active && s.status === 'active');
            if (!activeStores.length) {
                el.innerHTML = '<div style="padding:.4rem;font-size:.72rem;color:#94a3b8">Tidak ada toko aktif.</div>';
                return;
            }
            el.innerHTML = activeStores.map(s => {
                const disconnected = s.connection_status !== 'CONNECTED';
                return `
                <div style="padding:.35rem .6rem;border-top:1px solid #f1f5f9">
                    <div style="font-size:.78rem;font-weight:600;color:#0f172a">${esc(s.name)}
                        ${disconnected ? '<span style="font-size:.62rem;color:#b91c1c;font-weight:700"> · koneksi tidak aktif</span>' : ''}
                    </div>
                    <div style="display:flex;gap:.4rem;margin-top:.25rem">
                        <button class="btn-ship-outline" style="font-size:.68rem;padding:.15rem .5rem;border-radius:5px" onclick="event.stopPropagation(); runOrderBackgroundSync(${s.id}, '${esc(s.name)}')">▶ Sync Latar Belakang</button>
                        <button class="btn-ship-outline" style="font-size:.68rem;padding:.15rem .5rem;border-radius:5px" onclick="event.stopPropagation(); runOrderHistoricalBackfill(${s.id}, '${esc(s.name)}')">🕰 Backfill Histori</button>
                    </div>
                    <div id="bgprog-${s.id}" style="display:none;margin-top:.4rem">
                        <div style="height:6px;background:#f1f5f9;border-radius:99px;overflow:hidden">
                            <div id="bgprog-bar-${s.id}" style="height:100%;width:0%;background:#0f172a;border-radius:99px;transition:width .4s"></div>
                        </div>
                        <div id="bgprog-label-${s.id}" style="font-size:.62rem;color:#64748b;margin-top:.2rem"></div>
                    </div>
                </div>`;
            }).join('');

            // Pulihkan tampilan progres untuk toko yang sedang/baru sync + pastikan polling jalan
            activeStores.forEach(s => {
                if (bgSyncState[s.id]) {
                    renderBgProgress(s.id, bgSyncState[s.id]);
                    if (['queued', 'running'].includes(bgSyncState[s.id].status)) startBgPoll(s.id);
                }
            });
        } catch (e) {
            el.innerHTML = '<div style="padding:.4rem;font-size:.72rem;color:#b91c1c">Gagal memuat daftar toko: ' + esc(e.message) + '</div>';
        }
    }

    // Isi dropdown sekali saat pertama dibuka (bukan tiap render tabel, supaya hemat panggilan API)
    const _origToggleDropdownForBg = true; // penanda: populate dipicu manual di bawah, bukan override toggleDropdown()
    document.addEventListener('DOMContentLoaded', () => {
        const btn = $('btnBgSync');
        if (btn) btn.addEventListener('click', () => { if (!$('ddBgSync').classList.contains('open')) populateBgSyncDropdown(); }, { capture: true });
    });

    window.runOrderBackgroundSync = async function (storeId, storeName) {
        if (!confirm(`Tarik pesanan 60 hari terakhir untuk ${storeName} di latar belakang?\n\nProses berjalan di server (butuh queue worker aktif). Anda bisa memantau progresnya di sini, atau tutup halaman — proses tetap jalan.`)) return;
        // Tampilkan progres awal langsung, lalu mulai polling. Dropdown TIDAK ditutup.
        bgSyncState[storeId] = { percent: 0, label: 'Mengirim ke antrean…', status: 'queued', store: storeName };
        renderBgProgress(storeId, bgSyncState[storeId]);
        try {
            await api(`/api/marketplace/stores/${storeId}/sync-orders-background`, {
                method: 'POST',
                body: JSON.stringify({ days: 60 }),
            });
            startBgPoll(storeId);
        } catch (e) {
            bgSyncState[storeId] = { percent: 100, label: 'Gagal mengirim: ' + e.message, status: 'error', store: storeName };
            renderBgProgress(storeId, bgSyncState[storeId]);
        }
    };

    window.runOrderHistoricalBackfill = async function (storeId, storeName) {
        const year = prompt(`Backfill histori pesanan & retur untuk ${storeName}.\nMasukkan tahun target (contoh: 2022):`, String(new Date().getFullYear() - 1));
        if (!year) return;
        if (!confirm(`Tarik SELURUH histori order & retur tahun ${year} untuk ${storeName} di latar belakang?\n\nBisa berjalan lama (tergantung volume). Anda tetap bisa menutup halaman ini.`)) return;
        try {
            const res = await api(`/api/marketplace/stores/${storeId}/sync-historical`, {
                method: 'POST',
                body: JSON.stringify({ year: year }),
            });
            alert(res.message || `Backfill histori tahun ${year} dikirim ke latar belakang.`);
        } catch (e) {
            alert('Gagal mengirim backfill histori: ' + e.message);
        }
        $('ddBgSync').classList.remove('open');
    };

    window.selectStore = function (name) {
        activeStore = name;
        $('btnStoreLabel').textContent = name || 'Semua Toko';
        $('btnStore').classList.toggle('active', !!name);
        $('ddStore').classList.remove('open');
        populateStoreDropdown();
        render();
    };

    // ── Tab switch ────────────────────────────────────────────────────────
    window.switchSubTabProcessed = function (tab, btn) {
        subTabProcessed = tab;
        sessionStorage.setItem('ord_sub_tab_processed', tab);
        document.querySelectorAll('#subTabProcessedContainer .ord-subtab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentPage = 1;
        loadOrders();
        // Hide badges for active subtabs
        document.querySelectorAll('#subTabProcessedContainer .ord-badge').forEach(b => b.classList.remove('urgent'));
        btn.querySelector('.ord-badge')?.classList.add('urgent');
    };

    window.switchSubTabReady = function (tab, btn) {
        subTabReady = tab;
        sessionStorage.setItem('ord_sub_tab_ready', tab);
        document.querySelectorAll('#subTabReadyContainer .ord-subtab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentPage = 1;
        loadOrders();
        // Hide badges for active subtabs
        document.querySelectorAll('#subTabReadyContainer .ord-badge').forEach(b => b.classList.remove('urgent'));
        btn.querySelector('.ord-badge')?.classList.add('urgent');
    };

    window.switchSubTabShipped = function (tab, btn) {
        subTabShipped = tab;
        sessionStorage.setItem('ord_sub_tab_shipped', tab);
        document.querySelectorAll('#subTabShippedContainer .ord-subtab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        renderTable();
        document.querySelectorAll('#subTabShippedContainer .ord-badge').forEach(b => b.classList.remove('urgent'));
        btn.querySelector('.ord-badge')?.classList.add('urgent');
    };

    window.switchTab = function (tab, btn) {
        activeTab = tab;
        sessionStorage.setItem('ord_active_tab', tab);
        document.querySelectorAll('.ord-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        if (window.selectedPrintOrders) window.selectedPrintOrders.clear();
        
        const subTabContainer = document.getElementById('subTabProcessedContainer');
        if (subTabContainer) {
            subTabContainer.style.display = (tab === 'processed') ? 'flex' : 'none';
        }

        const subTabReadyContainer = document.getElementById('subTabReadyContainer');
        if (subTabReadyContainer) {
            subTabReadyContainer.style.display = (tab === 'ready') ? 'flex' : 'none';
        }

        const subTabShippedContainer = document.getElementById('subTabShippedContainer');
        if (subTabShippedContainer) {
            subTabShippedContainer.style.display = (tab === 'shipped') ? 'flex' : 'none';
        }

        const subTabRrcContainer = document.getElementById('subTabRrcContainer');
        if (subTabRrcContainer) {
            subTabRrcContainer.style.display = (tab === 'rrc') ? 'flex' : 'none';
        }

        const bodyContainer = document.getElementById('ordersBody');
        if (bodyContainer) {
            bodyContainer.classList.remove('animate-tab');
            void bodyContainer.offsetWidth; // trigger reflow
            bodyContainer.classList.add('animate-tab');
        }

        // Tab Retur/Refund/Batal: data diambil LIVE dari API Shopee, bukan dari
        // array `orders` lokal. Jadi jangan panggil renderTable() — panggil loadRrc().
        if (tab === 'rrc') {
            updateToolbar();
            loadRrc();
            return;
        }

        // Tab Sync: status sinkronisasi per toko + riwayat (data live, bukan array orders)
        if (tab === 'sync') {
            updateToolbar();
            loadSyncTab();
            return;
        }

        // Setiap tab memakai query backend yang berbeda. Jangan merender ulang
        // array tab sebelumnya karena filterByTab adalah no-op dan data yang
        // sudah dimuat bisa berasal dari tab lain.
        currentPage = 1;
        loadOrders();
    };

    // ── Tab Retur / Refund / Batal (data LIVE dari API Shopee) ──────────────
    let rrcSub = 'return';
    let rrcLoadSeq = 0;
    const rrcLabels = { return: 'Retur', refund: 'Refund', cancel: 'Batal' };

    window.switchSubTabRrc = function (sub, btn) {
        rrcSub = sub;
        document.querySelectorAll('#subTabRrcContainer .ord-subtab').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');
        loadRrc();
    };

    function fmtUnix(ts) {
        if (!ts) return '—';
        try {
            return new Date(ts * 1000).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        } catch (e) { return '—'; }
    }

    async function loadRrc(force) {
        if (activeTab !== 'rrc') return;
        const seq = ++rrcLoadSeq;
        const body = $('ordersBody');
        body.innerHTML = `<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat data ${rrcLabels[rrcSub]} dari database…</div>`;

        // Baca dari DATABASE (tersimpan, tanpa batas rentang tanggal). Untuk menarik
        // data terbaru dari Shopee, gunakan tombol "Tarik dari Shopee" (syncRrc()).
        const params = new URLSearchParams({ type: rrcSub });
        // Filter tanggal bersifat opsional; kirim hanya bila user membatasi rentang.
        if (getFrom()) params.set('date_from', getFrom());
        if (getTo())   params.set('date_to', getTo());
        const search = getSearch();
        if (search) params.set('search', search);
        if (force) params.set('_', Date.now());

        let res;
        try {
            res = await api('/api/marketplace/returns/stored?' + params.toString());
        } catch (e) {
            if (seq !== rrcLoadSeq) return;
            body.innerHTML = `<div class="ord-empty"><div class="ord-empty-icon">⚠️</div>Gagal mengambil data dari Shopee: ${esc(e.message || 'error')}</div>`;
            return;
        }
        if (seq !== rrcLoadSeq) return; // sudah ada permintaan lebih baru

        let rows = (res && res.data) ? res.data : [];
        if (activeStore) rows = rows.filter(r => r.store_name === activeStore);

        const badge = document.getElementById('badge-sub-rrc-' + rrcSub);
        if (badge) badge.textContent = rows.length;
        const badgeMain = document.getElementById('badge-rrc');
        if (badgeMain) badgeMain.textContent = rows.length;

        renderRrcList(rows, (res && res.errors) ? res.errors : [], (res && res.stores_queried) || 0);
    }
    window.loadRrc = loadRrc;

    // Tarik data terbaru dari Shopee → simpan ke DB (anti-duplikat via upsert) → muat ulang.
    window.syncRrc = async function () {
        const btn = document.getElementById('btnSyncRrc');
        const prev = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Menarik…'; }
        const body = $('ordersBody');
        body.innerHTML = `<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Menarik data dari Shopee & menyimpan ke database… (retur/refund; ini bisa memakan waktu)</div>`;
        try {
            const res = await api('/api/marketplace/returns/sync-all?full=1', { method: 'POST' });
            if (res && res.errors && res.errors.length) {
                console.warn('sync-all errors:', res.errors);
            }
        } catch (e) {
            if (body) body.innerHTML = `<div class="ord-empty"><div class="ord-empty-icon">⚠️</div>Gagal menarik dari Shopee: ${esc(e.message || 'error')}</div>`;
            if (btn) { btn.disabled = false; btn.innerHTML = prev; }
            return;
        }
        if (btn) { btn.disabled = false; btn.innerHTML = prev; }
        loadRrc(true);
    };

    function renderRrcList(rows, errors, storesQueried) {
        const body = $('ordersBody');
        let warn = '';
        if (errors && errors.length) {
            warn = `<div style="margin:0 0 8px;font-size:.72rem;color:#b45309">⚠️ Sebagian toko gagal diambil dari API: ${esc(errors.join('; '))}</div>`;
        }

        if (!rows.length) {
            const hint = `<div style="margin-top:6px;font-size:.72rem;color:#94a3b8">Diperiksa ${storesQueried || 0} toko Shopee untuk periode ${esc(getFrom())} s/d ${esc(getTo())}. Coba lebarkan rentang tanggal di atas (retur/refund biasanya jarang).</div>`;
            body.innerHTML = `<div class="ord-empty"><div class="ord-empty-icon">📭</div>Tidak ada data ${rrcLabels[rrcSub]} pada periode ini.${hint}</div>` + (warn ? `<div style="text-align:center">${warn}</div>` : '');
            return;
        }

        const kindBadge = (kind) => {
            if (kind === 'cancel') return `<span style="font-size:.65rem;background:#fee2e2;color:#991b1b;border-radius:99px;padding:2px 8px;font-weight:800;">🚫 Batal</span>`;
            if (kind === 'refund') return `<span style="font-size:.65rem;background:#fef9c3;color:#854d0e;border-radius:99px;padding:2px 8px;font-weight:800;">💸 Refund</span>`;
            return `<span style="font-size:.65rem;background:#e0e7ff;color:#3730a3;border-radius:99px;padding:2px 8px;font-weight:800;">↩️ Retur</span>`;
        };

        const cards = rows.map(r => {
            const itemsHtml = (r.items || []).map(it => `
                <div class="ord-item-card">
                    ${(it.images && it.images[0]) ? `<img src="${esc(it.images[0])}" style="width:34px;height:34px;object-fit:cover;border-radius:6px;margin-right:6px" onerror="this.style.display='none'">` : ''}
                    <div class="ord-item-qty">${it.quantity || 0}×</div>
                    <div class="ord-item-body">
                        <div class="ord-item-name">${esc(it.item_name || it.item_sku || '—')}</div>
                        ${it.variation_name ? `<div class="ord-item-variant">${esc(it.variation_name)}</div>` : ''}
                        ${(it.variation_sku || it.item_sku) ? `<div class="ord-item-variant" style="color:#94a3b8">SKU: ${esc(it.variation_sku || it.item_sku)}</div>` : ''}
                    </div>
                </div>`).join('');

            const idLabel = r.return_sn || r.order_sn || '—';
            const amount = r.amount ? ('Rp ' + Number(r.amount).toLocaleString('id-ID')) : '';

            return `
            <div class="ord-card" style="padding:12px;margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start">
                    <div style="min-width:180px">
                        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:4px">
                            ${kindBadge(r.kind)}
                            <span style="font-weight:800;font-size:.85rem">${esc(idLabel)}</span>
                        </div>
                        <div style="font-size:.7rem;color:#64748b">Order: ${esc(r.order_sn || '—')}</div>
                        <div style="font-size:.7rem;color:#94a3b8">${fmtUnix(r.create_time)} · ${esc(r.store_name || '')}</div>
                        ${r.status ? `<div style="margin-top:4px"><span style="font-size:.65rem;background:#f1f5f9;color:#475569;border-radius:4px;padding:1px 6px;font-weight:700">${esc(r.status)}</span></div>` : ''}
                        ${r.reason ? `<div style="margin-top:4px;font-size:.7rem;color:#b45309">Alasan: ${esc(r.reason)}</div>` : ''}
                        ${r.tracking_number ? `<div style="margin-top:4px;font-size:.65rem;color:#059669;font-weight:700">Resi: ${esc(r.tracking_number)}</div>` : ''}
                    </div>
                    <div style="text-align:right;min-width:120px">
                        ${amount ? `<div style="font-weight:800;color:#dc2626;font-size:.9rem">${amount}</div>` : ''}
                    </div>
                </div>
                ${itemsHtml ? `<div class="ord-items-cell" style="margin-top:8px">${itemsHtml}</div>` : ''}
            </div>`;
        }).join('');

        body.innerHTML = warn + cards;
    }

    // ── Tab Sync: status sinkronisasi per toko + riwayat sync ─────────────
    let syncTabTimer = null;
    let syncTabSeq = 0;

    async function loadSyncTab(silent) {
        if (activeTab !== 'sync') return;
        const seq = ++syncTabSeq;
        const body = $('ordersBody');
        if (!silent) {
            body.innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat status sinkronisasi…</div>';
        }

        let stores = [], logs = [];
        try {
            [stores, logs] = await Promise.all([
                api('/api/marketplace/stores'),
                api('/api/marketplace/sync-logs').catch(() => []),
            ]);
        } catch (e) {
            if (seq !== syncTabSeq || activeTab !== 'sync') return;
            body.innerHTML = `<div class="ord-empty"><div class="ord-empty-icon">⚠️</div>Gagal memuat status sync: ${esc(e.message || 'error')}</div>`;
            return;
        }
        if (seq !== syncTabSeq || activeTab !== 'sync') return;

        // Progres live per toko (dibaca dari cache backend, di-set oleh proses sync)
        const progress = {};
        await Promise.all((stores || []).map(async s => {
            try { progress[s.id] = await api('/api/marketplace/stores/' + s.id + '/sync-progress'); } catch (e) {}
        }));
        if (seq !== syncTabSeq || activeTab !== 'sync') return;

        renderSyncTab(stores || [], progress, logs || []);

        const running = Object.values(progress).filter(p => p && (p.status === 'running' || p.status === 'queued')).length;
        const badge = document.getElementById('badge-sync');
        if (badge) badge.textContent = running > 0 ? running : '—';

        // Auto-refresh selama masih ada proses berjalan & tab masih dibuka
        clearTimeout(syncTabTimer);
        if (running > 0) {
            syncTabTimer = setTimeout(() => loadSyncTab(true), 4000);
        }
    }
    window.loadSyncTab = loadSyncTab;

    function syncStatusDot(s) {
        if (s.connection_status === 'CONNECTED') return '<span style="color:#16a34a">●</span> Terhubung';
        if (s.connection_status === 'TOKEN_EXPIRED') return '<span style="color:#d97706">●</span> Token kedaluwarsa';
        return '<span style="color:#dc2626">●</span> Perlu login ulang';
    }

    function renderSyncTab(stores, progress, logs) {
        const body = $('ordersBody');
        const thisYear = new Date().getFullYear();

        const shopeeStores = stores.filter(s => s.is_active !== false);
        const cards = shopeeStores.map(s => {
            const p = progress[s.id];
            const isRunning = p && (p.status === 'running' || p.status === 'queued');
            const pct = isRunning ? Math.max(0, Math.min(100, p.percent || 0)) : 0;
            const lastSync = s.last_synced_at ? fmt(s.last_synced_at) : 'Belum pernah';

            let progressHtml = '';
            if (isRunning) {
                progressHtml = `
                <div style="margin-top:8px">
                    <div style="display:flex;justify-content:space-between;font-size:.68rem;color:#475569;margin-bottom:3px">
                        <span>${esc(p.label || 'Sedang berjalan…')}</span><span style="font-weight:700">${pct}%</span>
                    </div>
                    <div style="height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden">
                        <div style="height:100%;width:${pct}%;background:linear-gradient(90deg,#3b82f6,#6366f1);border-radius:99px;transition:width .5s"></div>
                    </div>
                </div>`;
            } else if (p && p.status === 'done') {
                progressHtml = `<div style="margin-top:8px;font-size:.7rem;color:#16a34a;font-weight:700">✅ ${esc(p.label || 'Selesai')}</div>`;
            } else if (p && p.status === 'error') {
                progressHtml = `<div style="margin-top:8px;font-size:.7rem;color:#dc2626;font-weight:700">⚠️ ${esc(p.label || 'Gagal')}</div>`;
            }

            const yearOpts = [];
            for (let y = thisYear - 1; y >= 2020; y--) yearOpts.push(`<option value="${y}">s/d ${y}</option>`);

            const disabled = isRunning ? 'disabled style="opacity:.5;pointer-events:none"' : '';

            return `
            <div class="ord-card" style="padding:14px;margin-bottom:10px">
                <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start">
                    <div>
                        <div style="font-weight:800;font-size:.9rem">${esc(s.name)}</div>
                        <div style="font-size:.7rem;color:#64748b;margin-top:2px">${syncStatusDot(s)} · Sync terakhir: ${esc(lastSync)}</div>
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                        <select id="syncRange-${s.id}" class="form-select form-select-sm" style="width:auto;font-size:.72rem;padding:.25rem 1.6rem .25rem .5rem" ${disabled}>
                            <option value="3">3 hari</option>
                            <option value="7">7 hari</option>
                            <option value="30">30 hari</option>
                            <option value="90">90 hari</option>
                            <option value="180">180 hari</option>
                            <option value="365">1 tahun</option>
                        </select>
                        <button id="btnSyncRun-${s.id}" class="btn btn-sm btn-primary" style="font-size:.72rem;padding:.25rem .6rem" onclick="syncTabRun(${s.id})" ${disabled}>⬇️ Tarik</button>
                        <select id="syncYear-${s.id}" class="form-select form-select-sm" style="width:auto;font-size:.72rem;padding:.25rem 1.6rem .25rem .5rem" ${disabled}>
                            ${yearOpts.join('')}
                        </select>
                        <button class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;padding:.25rem .6rem" onclick="syncTabHistorical(${s.id})" title="Tarik seluruh histori order (per 14 hari, create_time) sampai tahun terpilih — berjalan di latar belakang" ${disabled}>🕰️ Histori</button>
                    </div>
                </div>
                ${progressHtml}
            </div>`;
        }).join('');

        // Riwayat sync terakhir (dari marketplace_sync_logs)
        const logRows = (logs || []).slice(0, 30).map(l => {
            const ok = l.status === 'success';
            const badgeHtml = `<span style="font-size:.62rem;font-weight:800;border-radius:99px;padding:2px 8px;${ok ? 'background:#dcfce7;color:#166534' : 'background:#fee2e2;color:#991b1b'}">${ok ? '✓ sukses' : '✕ gagal'}</span>`;
            return `
            <div style="display:flex;gap:8px;align-items:flex-start;padding:8px 4px;border-bottom:1px solid #f1f5f9;font-size:.72rem">
                <div style="min-width:110px;color:#94a3b8">${l.created_at ? fmt(l.created_at) : '—'}</div>
                <div style="min-width:110px;font-weight:700;color:#334155">${esc(l.store_name || '—')}</div>
                <div style="min-width:100px;color:#64748b">${esc(l.action || '—')}</div>
                <div>${badgeHtml}</div>
                <div style="flex:1;color:#475569">${esc(l.message || '')}</div>
            </div>`;
        }).join('');

        body.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:8px">
            <div style="font-weight:800;font-size:.85rem;color:#334155">Status Sinkronisasi per Toko</div>
            <button class="btn btn-sm btn-outline-secondary" style="font-size:.72rem;padding:.25rem .6rem" onclick="loadSyncTab()">🔄 Segarkan</button>
        </div>
        ${cards || '<div class="ord-empty"><div class="ord-empty-icon">🏪</div>Belum ada toko. Hubungkan toko dulu di halaman Toko.</div>'}
        <div style="margin-top:18px">
            <div style="font-weight:800;font-size:.85rem;color:#334155;margin-bottom:6px">Riwayat Sync Terakhir</div>
            <div class="ord-card" style="padding:6px 10px">${logRows || '<div style="padding:10px;font-size:.75rem;color:#94a3b8">Belum ada riwayat sync.</div>'}</div>
        </div>`;
    }

    // Tarik pesanan N hari ke belakang (jalan di latar belakang via queue)
    window.syncTabRun = async function (storeId) {
        const sel = document.getElementById('syncRange-' + storeId);
        const days = parseInt(sel && sel.value) || 3;
        const btn = document.getElementById('btnSyncRun-' + storeId);
        if (btn) { btn.disabled = true; btn.innerHTML = '⏳…'; }
        try {
            await api('/api/marketplace/stores/' + storeId + '/sync-orders-background', {
                method: 'POST',
                body: JSON.stringify({ days }),
            });
        } catch (e) {
            if (e && e.data && e.data.action && e.data.action.url) {
                if (confirm((e.message || 'Toko perlu login ulang.') + '\n\nBuka halaman koneksi sekarang?')) {
                    window.location = e.data.action.url;
                }
            } else {
                alert('Gagal memulai sync: ' + (e.message || 'error'));
            }
        }
        setTimeout(() => loadSyncTab(true), 800);
    };

    // Backfill histori penuh sampai tahun target (Mesin Waktu — background)
    window.syncTabHistorical = async function (storeId) {
        const sel = document.getElementById('syncYear-' + storeId);
        const year = parseInt(sel && sel.value) || (new Date().getFullYear() - 1);
        if (!confirm('Tarik SEMUA histori order sampai tahun ' + year + '?\n\nProses berjalan di latar belakang (per 14 hari) dan bisa memakan waktu lama. Data masuk bertahap.')) return;
        try {
            await api('/api/marketplace/stores/' + storeId + '/sync-historical', {
                method: 'POST',
                body: JSON.stringify({ year }),
            });
        } catch (e) {
            if (e && e.data && e.data.action && e.data.action.url) {
                if (confirm((e.message || 'Toko perlu login ulang.') + '\n\nBuka halaman koneksi sekarang?')) {
                    window.location = e.data.action.url;
                }
                return;
            }
            alert('Gagal memulai backfill histori: ' + (e.message || 'error'));
            return;
        }
        setTimeout(() => loadSyncTab(true), 800);
    };

    async function autoFetchMissingAwbs() {
        const awbStatuses = ['PROCESSED', 'READY_TO_HANDOVER', 'SHIPPED', 'TO_CONFIRM_RECEIVE', 'COMPLETED'];
        const rows = orders.filter(o => awbStatuses.includes(o.order_status) && !o.shipping_awb_no);
        if (rows.length === 0) return;
        
        let updatedCount = 0;
        for (const o of rows) {
            try {
                const sn = o.booking_sn || o.channel_order_id;
                const data = await api(`/api/marketplace/stores/${o.store_id}/orders/${encodeURIComponent(sn)}/sync-awb`);
                if (data.success && data.awb) {
                    o.shipping_awb_no = data.awb;
                    updatedCount++;
                }
            } catch(e) {}
        }
        
        if (updatedCount > 0) {
            renderTable();
        }
    }

    window.switchTabByName = function (tab) {
        const btn = document.querySelector(`.ord-tab[data-tab="${tab}"]`);
        if (btn) switchTab(tab, btn);
    };

    // Apply saved tab on init
    function restoreSavedTab() {
        const saved = sessionStorage.getItem('ord_active_tab') || 'ready';
        const btn   = document.querySelector(`.ord-tab[data-tab="${saved}"]`);
        if (btn) {
            document.querySelectorAll('.ord-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const subTabContainer = document.getElementById('subTabProcessedContainer');
            if (subTabContainer) {
                subTabContainer.style.display = (saved === 'processed') ? 'flex' : 'none';
            }
            const subTabReadyContainer = document.getElementById('subTabReadyContainer');
            if (subTabReadyContainer) {
                subTabReadyContainer.style.display = (saved === 'ready') ? 'flex' : 'none';
            }
            const subTabShippedContainer = document.getElementById('subTabShippedContainer');
            if (subTabShippedContainer) {
                subTabShippedContainer.style.display = (saved === 'shipped') ? 'flex' : 'none';
            }
            const subTabRrcContainer = document.getElementById('subTabRrcContainer');
            if (subTabRrcContainer) {
                subTabRrcContainer.style.display = (saved === 'rrc') ? 'flex' : 'none';
            }
            // Bila halaman dibuka langsung di tab rrc (mis. ?tab=rrc), muat data live.
            if (saved === 'rrc') {
                setTimeout(() => loadRrc(), 0);
            }
            if (saved === 'sync') {
                setTimeout(() => loadSyncTab(), 0);
            }
        }
        
        // Restore sub-tabs
        const subRBtn = document.querySelector(`#subTabReadyContainer .ord-subtab[data-sub="${subTabReady}"]`);
        if (subRBtn) {
            document.querySelectorAll('#subTabReadyContainer .ord-subtab').forEach(b => b.classList.remove('active'));
            subRBtn.classList.add('active');
        }
        const subPBtn = document.querySelector(`#subTabProcessedContainer .ord-subtab[data-sub="${subTabProcessed}"]`);
        if (subPBtn) {
            document.querySelectorAll('#subTabProcessedContainer .ord-subtab').forEach(b => b.classList.remove('active'));
            subPBtn.classList.add('active');
        }
        
        subTabShipped = sessionStorage.getItem('ord_sub_tab_shipped') || 'shipped';
        const subSBtn = document.querySelector(`#subTabShippedContainer .ord-subtab[data-sub="${subTabShipped}"]`);
        if (subSBtn) {
            document.querySelectorAll('#subTabShippedContainer .ord-subtab').forEach(b => b.classList.remove('active'));
            subSBtn.classList.add('active');
        }
    }

    function fulfillmentBadge(o) {
        if (fulfilledOrderIds.has(o.id)) return '<span class="fstatus fstatus-done">✓ Selesai</span>';
        const f = fulfillmentStatusMap.get(o.id);
        if (!f) return '<span class="fstatus fstatus-none">Belum Proses</span>';
        if (f.status === 'confirmed')      return '<span class="fstatus fstatus-done">✓ Selesai</span>';
        if (o.has_data_issues)             return '<span class="fstatus fstatus-draft">⚠ Perlu Perbaiki</span>';
        if (f.status === 'pending_review') return '<span class="fstatus fstatus-pending">Siap Konfirmasi</span>';
        if (f.status === 'draft')          return '<span class="fstatus fstatus-draft">Draft</span>';
        return '';
    }

    function updateLastSyncTime() {
        const el = document.getElementById('lastSyncTime');
        if (el) {
            const now = new Date();
            el.innerText = 'Last sync: ' + now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0') + ':' + now.getSeconds().toString().padStart(2, '0');
        }
    }

    // ── Load ──────────────────────────────────────────────────────────────
    async function loadOrders() {
        const loadSeq = ++ordersLoadSeq;
        $('ordersBody').innerHTML = '<div class="prod-tab-loading"><span class="prod-tab-spinner"></span> Memuat…</div>';
        // Kirim rentang tanggal aktif ke backend supaya order lama hasil backfill
        // ikut terambil (backend tidak lagi terpaku 200 order terbaru saja).
        const res = await api(localOrdersUrl()).catch(() => ({data: []}));
        if (loadSeq !== ordersLoadSeq) return;
        applyOrdersResponse(res);

        if (activeStore && !isNaN(activeStore)) {
            const storeIdNum = parseInt(activeStore, 10);
            const matchingOrder = orders.find(o => o.store_id === storeIdNum);
            if (matchingOrder && matchingOrder.store) {
                activeStore = matchingOrder.store.name;
                sessionStorage.setItem('ord_store', activeStore);
            } else {
                activeStore = '';
            }
        }

        // Pre-populate fulfillment status dari data API
        fulfillmentStatusMap.clear();
        orders.forEach(o => {
            if (o.fulfillment_status) {
                fulfillmentStatusMap.set(o.id, { id: o.fulfillment_id, status: o.fulfillment_status });
                if (o.fulfillment_status === 'confirmed') fulfilledOrderIds.add(o.id);
            }
        });
        populateStoreDropdown();
        restoreSavedTab();
        render();
        updateLastSyncTime();
        loadOrderCounts(loadSeq, api(localOrderCountsUrl()).catch(() => null));

        if (['processed', 'processed_instant', 'shipped', 'completed'].includes(activeTab)) {
            autoFetchMissingAwbs();
        }

        if (window.autoArrangeAfterSync) {
            window.autoArrangeAfterSync = false;
            setTimeout(() => {
                pendingShipOrders = orders.filter(o => o.order_status === 'READY_TO_SHIP');
                if (pendingShipOrders.length > 0) {
                    const modal = new bootstrap.Modal($('bulkArrangeShipmentModal'));
                    modal.show();
                    $('basConfirmView').style.display  = 'none';
                    $('basProgressView').style.display = 'block';
                    $('basDoneView').style.display     = 'none';
                    startBulkArrangeShipment();
                }
            }, 500);
        }
    }

    async function loadOrderCounts(loadSeq, request) {
        const res = await request;
        if (!res || loadSeq !== ordersLoadSeq) return;

        orderCounts = res;
        renderBadges();
    }

    function inRange(o) { return true; }

    function applyFilters(rows) { return rows; }

    window.selectedPrintOrders = new Set();
    window.render = function () { renderBadges(); renderTable(); updateToolbar(); updatePickingPrintStrip(); };

    // ── Process Toolbar ───────────────────────────────────────────────────
    function getProcessRows() {
        return filterByTab(applyFilters(orders.filter(inRange)), 'ready');
    }

    function getPackingRows() {
        return filterByTab(applyFilters(orders.filter(inRange)), 'processed');
    }

    function getPrintablePickingRows() {
        const filtered = applyFilters(orders.filter(inRange));
        let rows = [];
        if (activeTab === 'ready') {
            rows = filterByTab(filtered, 'ready');
        } else if (activeTab === 'processed') {
            rows = filterByTab(filtered, 'processed');
        } else {
            rows = [...filterByTab(filtered, 'ready'), ...filterByTab(filtered, 'processed')];
        }
        const unique = new Map();
        rows.forEach(o => unique.set(o.id, o));
        return Array.from(unique.values());
    }

    function updatePickingPrintStrip() {
        const rows = getPrintablePickingRows();
        const readyCount = filterByTab(rows, 'ready').length;
        const processedCount = filterByTab(rows, 'processed').length;
        const info = $('pickingPrintInfo');
        const btn = $('btnPrintPickingTop');

        if (info) {
            info.innerHTML = `<strong>${rows.length}</strong> order siap dicetak <span style="color:#94a3b8">(${readyCount} perlu dikirim · ${processedCount} sedang dikemas)</span>`;
        }
        if (btn) {
            btn.disabled = rows.length === 0;
            btn.style.opacity = rows.length === 0 ? '.45' : '1';
            btn.style.pointerEvents = rows.length === 0 ? 'none' : '';
        }
    }

    function updateToolbar() {
        const toolbar = $('processToolbar');
        if (!['ready', 'processed', 'issues', 'shipped', 'rrc'].includes(activeTab)) { toolbar.classList.remove('visible'); return; }

        const isIssues = activeTab === 'issues';
        $('toolbarActionsReady').style.display = (activeTab === 'ready') ? '' : 'none';
        $('toolbarActionsProcessed').style.display = (activeTab === 'processed') ? '' : 'none';
        $('toolbarActionsUnresolved').style.display = isIssues ? '' : 'none';

        if (isIssues) {
            const rows = filterByTab(applyFilters(orders.filter(inRange)), 'issues');
            toolbar.classList.toggle('visible', rows.length > 0);
            $('toolbarInfo').style.display = 'block';
            $('toolbarInfo').innerHTML = `<strong>${rows.length}</strong> order perlu diperbaiki`;
        } else if (activeTab === 'processed') {
            const rows = getPackingRows();
            toolbar.classList.toggle('visible', true);
            $('toolbarInfo').style.display = 'none';
        } else if (activeTab === 'ready') {
            const rows = getProcessRows();
            toolbar.classList.toggle('visible', rows.length > 0);
            $('toolbarInfo').style.display = 'none';
        } else {
            toolbar.classList.toggle('visible', true);
            $('toolbarInfo').style.display = 'none';
        }
    }

    // ── Print Picking List ────────────────────────────────────────────────
    window.printPickingList = function () {
        // Cetak gabungan Perlu Proses + Sedang Proses sesuai filter toko/tanggal/search aktif.
        const rows = getPrintablePickingRows();
        if (!rows.length) { alert('Tidak ada order untuk dicetak.'); return; }

        // Tandai semua order ini sebagai sudah dicetak
        rows.forEach(o => printedOrderIds.add(o.id));
        renderTable();

        const today    = new Date().toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
        const timeNow  = new Date().toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });

        // ── Agregasi item (ringkasan picking) ─────────────────────────
        const itemMap = {};
        rows.forEach(o => {
            (o.items || []).forEach(i => {
                const code    = i.internal_item?.code || '';
                const mSku    = i.model_sku || i.item_sku || '';
                const category = code
                    ? (i.internal_item?.category?.name || i.internal_item?.item_category?.name || i.internal_item?.category_name || 'Tanpa Kategori')
                    : 'Belum Mapping';
                const categoryOrder = code ? (category === 'Tanpa Kategori' ? 2 : 1) : 3;
                const key     = category + '||' + (code || mSku);
                if (!itemMap[key]) itemMap[key] = { code, mSku, category, categoryOrder, qty: 0, mapped: !!code };
                itemMap[key].qty += (i.qty || 1);
            });
        });

        // Urutkan per kategori: kategori normal, Tanpa Kategori, Belum Mapping.
        const sortedItems = Object.values(itemMap).sort((a, b) => {
            if (a.categoryOrder !== b.categoryOrder) return a.categoryOrder - b.categoryOrder;
            if (a.category !== b.category) return a.category.localeCompare(b.category, 'id');
            return (a.code || a.mSku || '').localeCompare((b.code || b.mSku || ''), 'id');
        });

        let currentCategory = null;
        const itemRows = sortedItems.map(it => {
            const groupRow = currentCategory !== it.category
                ? `<tr class="category-row"><td colspan="4">${esc(it.category)}</td></tr>`
                : '';
            currentCategory = it.category;

            const label = it.mapped
                ? `<strong class="sku-code">${it.code}</strong>`
                : `<span class="unmapped-text">${it.mSku || '—'} <em>(belum mapping)</em></span>`;
            return `${groupRow}<tr>
                <td class="chk"><input type="checkbox"></td>
                <td>${label}</td>
                <td class="qty">${it.qty}</td>
                <td class="picked-qty"></td>
            </tr>`;
        }).join('');

        const totalQty    = sortedItems.reduce((s, i) => s + i.qty, 0);

        const html = `<!DOCTYPE html><html><head>
            <meta charset="UTF-8">
            <title>Picking List — ${today}</title>
            <style>
                *, *::before, *::after {
                    box-sizing: border-box;
                    color: #000 !important;
                    border-color: #000 !important;
                    box-shadow: none !important;
                    text-shadow: none !important;
                    filter: none !important;
                    opacity: 1 !important;
                }
                @page { size: 100mm 150mm; margin: 3.5mm; }
                html, body {
                    margin: 0;
                    padding: 0;
                    background: #fff !important;
                    color: #000 !important;
                    font-family: Arial, Helvetica, sans-serif;
                    font-size: 6.5pt;
                    line-height: 1.05;
                    -webkit-print-color-adjust: economy;
                    print-color-adjust: economy;
                    color-scheme: light only;
                }
                /* Toolbar (hanya tampil di layar, tidak dicetak) */
                #toolbar {
                    position: fixed; top: 0; left: 0; right: 0; z-index: 99;
                    background: #0f172a !important; color: #fff !important; padding: .75rem 1rem;
                    display: flex; align-items: center; justify-content: space-between;
                    gap: 1rem;
                }
                #toolbar * { color: #fff !important; }
                #toolbar button {
                    background: #000 !important; color: #fff !important; border: 1px solid #fff; border-radius: 8px;
                    padding: .75rem 1.5rem; font-weight: 900; font-size: 1rem; cursor: pointer;
                    min-width: 132px;
                }
                #toolbar button:hover { background: #111 !important; }
                #content { padding-top: 58px; }
                @media print { #toolbar { display: none; } #content { padding-top: 0; } }
                /* Header */
                .page-header {
                    display: flex; justify-content: space-between; align-items: flex-end;
                    border-bottom: .3mm solid #000; padding-bottom: .8mm; margin-bottom: 1.1mm;
                }
                .header-left { display: flex; align-items: center; gap: 1.5mm; min-width: 0; }
                .print-logo {
                    width: 7mm;
                    height: 7mm;
                    object-fit: contain;
                    flex: 0 0 auto;
                    display: block;
                    filter: grayscale(1) contrast(1.4) !important;
                }
                .page-title { font-size: 6.5pt; font-weight: 900; letter-spacing: 0; }
                .page-date { font-size: 6pt; color: #000 !important; font-weight: 800; margin-top: .2mm; }
                .page-meta  { font-size: 6.5pt; color: #000 !important; text-align: right; font-weight: 900; }
                /* Ringkasan */
                .section-title {
                    font-size: 6.5pt; font-weight: 900; text-transform: uppercase;
                    letter-spacing: .02em; color: #000 !important; margin: 1mm 0 .7mm;
                    border-bottom: .25mm solid #000; padding-bottom: .5mm;
                }
                table { width: 100%; border-collapse: collapse; }
                thead { display: table-row-group; }
                table td, table th { padding: .62mm .8mm; border: .24mm solid #000; vertical-align: middle; }
                table th { font-size: 6.5pt; color: #000 !important; text-transform: uppercase; font-weight: 900; }
                .category-row td {
                    padding: .45mm .8mm;
                    font-size: 6pt;
                    font-weight: 900;
                    text-transform: uppercase;
                    letter-spacing: .03em;
                    color: #fff !important;
                    background: #000 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .chk  { width: 5.5mm; text-align: center; }
                .chk input { width: 2.8mm; height: 2.8mm; accent-color: #000; }
                .qty  { width: 9mm; text-align: center; font-weight: 900 !important; font-size: 6.5pt; }
                .picked-qty { width: 14mm; text-align: center; font-weight: 900 !important; font-size: 6.5pt; }
                .sku-code,
                .unmapped-text {
                    font-family: Arial, Helvetica, sans-serif;
                    font-size: 6.5pt;
                    font-weight: 900 !important;
                    color: #000 !important;
                    line-height: 1;
                }
                .variant-text {
                    display: inline;
                    margin-top: 0;
                    font-size: 6pt;
                    font-weight: 900;
                    color: #000 !important;
                }
                .unmapped-text em { color: #000 !important; font-style: normal; font-weight: 900; }
                .empty-text { color: #000 !important; font-style: normal; font-weight: 900; font-size: 6.5pt; }
                .footer {
                    display: flex; justify-content: space-between; font-weight: 900;
                    font-size: 6.5pt; border-top: .3mm solid #000; padding-top: .7mm; margin-top: 1mm;
                    color: #000 !important;
                }
                @media screen {
                    body {
                        width: 100mm;
                        min-height: 150mm;
                        margin: 0 auto;
                        padding: 0;
                        overflow-x: hidden;
                        background: #fff !important;
                    }
                    #content {
                        width: 100mm;
                        min-height: 150mm;
                        margin: 0;
                        padding-left: 3.5mm;
                        padding-right: 3.5mm;
                        padding-bottom: 3.5mm;
                    }
                }
                @media print {
                    *, *::before, *::after {
                        color: #000 !important;
                        border-color: #000 !important;
                        box-shadow: none !important;
                        text-shadow: none !important;
                        filter: none !important;
                        opacity: 1 !important;
                    }
                    html, body, #content { width: 93mm; background: #fff !important; }
                    thead { display: table-row-group !important; }
                    .qty,
                    .sku-code {
                        font-weight: 900 !important;
                    }
                    .category-row td {
                        color: #fff !important;
                        background: #000 !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                }
            </style>
        </head>
        <body>
            <div id="toolbar">
                <span style="font-size:.85rem;font-weight:600">📋 Picking List — ${rows.length} order · ${totalQty} item</span>
                <button onclick="window.print()">🖨 Print</button>
            </div>
            <div id="content">
                <div class="page-header">
                    <div class="header-left">
                        <img class="print-logo" src="/images/logo-mark.svg" alt="GF">
                        <div>
                            <div class="page-title">PICKING LIST</div>
                            <div class="page-date">${today} · ${timeNow}</div>
                        </div>
                    </div>
                    <div class="page-meta">
                        <div><strong>${rows.length}</strong> order</div>
                        <div><strong>${totalQty}</strong> total item</div>
                    </div>
                </div>

                <div class="section-title">Daftar Barang Diambil</div>
                <table>
                    <thead><tr>
                        <th class="chk"></th>
                        <th style="text-align:left">Kode Item</th>
                        <th class="qty">Qty</th>
                        <th class="picked-qty">Diambil</th>
                    </tr></thead>
                    <tbody>${itemRows}</tbody>
                </table>

                <div class="footer">
                    <span>TOTAL ${rows.length} PESANAN</span>
                    <span>${totalQty} ITEM</span>
                </div>
            </div>
        </body></html>`;

        const win = window.open('', '_blank', 'width=430,height=680');
        if (!win) { alert('Popup diblokir. Izinkan popup untuk halaman ini.'); return; }
        win.document.write(html);
        win.document.close();
        win.focus();
        // Tidak auto-print — user klik tombol Print di toolbar
    };

    // ── Bulk Fulfillment ──────────────────────────────────────────────────
    // ── Bulk Arrange Shipment ─────────────────────────────────────────────
    let pendingShipOrders = [];

    window.openBulkArrangeShipment = function () {
        // Hanya proses order yang tampil di layar saat ini (terfilter by Toko/Search)
        pendingShipOrders = getProcessRows();
        
        if (!pendingShipOrders.length) {
            alert('Tidak ada order yang berstatus Belum Diproses (READY_TO_SHIP) di tampilan saat ini.');
            return;
        }

        $('basConfirmView').style.display  = 'block';
        $('basProgressView').style.display = 'none';
        $('basDoneView').style.display     = 'none';
        $('basStartBtn').disabled = false;
        $('basStartBtn').textContent = '📦 Proses Sekarang';

        $('basSummaryText').innerHTML = `<strong>${pendingShipOrders.length} order</strong> siap diatur pengirimannya ke pihak Marketplace.`;

        new bootstrap.Modal($('bulkArrangeShipmentModal')).show();
    };

    window.startBulkArrangeShipment = async function () {
        if (!pendingShipOrders.length) return;

        $('basConfirmView').style.display  = 'none';
        $('basProgressView').style.display = 'block';
        
        const methodEl = document.querySelector('input[name="basMethod"]:checked');
        const method = methodEl ? methodEl.value : 'dropoff';
        
        const logEl = $('basLog');
        logEl.innerHTML = '';
        
        const logMsg = (msg, color = '#475569') => {
            logEl.innerHTML += `<div style="color:${color};margin-bottom:2px">${msg}</div>`;
            logEl.scrollTop = logEl.scrollHeight;
        };

        let successCount = 0;
        let failCount = 0;
        
        let processedCount = 0;
        const chunkSize = 5; // Eksekusi 5 order sekaligus (Parallel)

        for (let i = 0; i < pendingShipOrders.length; i += chunkSize) {
            const chunk = pendingShipOrders.slice(i, i + chunkSize);
            const promises = chunk.map(async (o) => {
                logMsg(`[${o.channel_order_id}] Memulai...`);
                
                try {
                    let params = {};
                    if (method === 'dropoff') params = { dropoff: {} };
                    else if (method === 'pickup') params = { pickup: {} };

                    // Pesanan Kilat harus lewat endpoint booking (ship_booking) —
                    // channel_order_id barisnya masih booking_sn sebelum MATCHED,
                    // dan endpoint order akan error "The order_sn is not exist".
                    const bulkShipUrl = o.is_kilat
                        ? `/api/marketplace/stores/${o.store_id}/bookings/${o.booking_sn || o.channel_order_id}/ship`
                        : `/api/marketplace/stores/${o.store_id}/orders/${o.channel_order_id}/ship`;

                    await api(bulkShipUrl, {
                        method: 'POST',
                        body: JSON.stringify(params)
                    });
                    
                    logMsg(`[${o.channel_order_id}] Berhasil!`, '#16a34a');
                    successCount++;
                } catch (e) {
                    logMsg(`[${o.channel_order_id}] Gagal: ${e.message}`, '#dc2626');
                    failCount++;
                } finally {
                    processedCount++;
                    const pct = Math.round((processedCount / pendingShipOrders.length) * 100);
                    $('basProgressText').textContent = `Memproses ${processedCount} dari ${pendingShipOrders.length} order...`;
                    $('basProgressBar').style.width = pct + '%';
                }
            });
            
            // Tunggu 5 order ini selesai bersamaan sebelum memuat 5 order berikutnya
            await Promise.all(promises);
            
            // Jeda 500ms antar kelompok untuk mencegah blokir/rate-limit dari Shopee
            await new Promise(r => setTimeout(r, 500));
        }
        
        $('basProgressBar').style.width = '100%';
        $('basProgressView').style.display = 'none';
        $('basDoneView').style.display = 'block';
        
        let resHtml = `Selesai memproses ${pendingShipOrders.length} order.<br>`;
        if (successCount > 0) resHtml += `<span style="color:#16a34a;font-weight:bold">✅ ${successCount} berhasil.</span><br>`;
        if (failCount > 0) resHtml += `<span style="color:#dc2626;font-weight:bold">❌ ${failCount} gagal.</span>`;
        $('basResultText').innerHTML = resHtml;
    };

    // ── Badges ────────────────────────────────────────────────────────────
    function renderBadges() {
        const rows = applyFilters(orders.filter(inRange));
        ['issues', 'ready', 'processed', 'processed_instant', 'shipped', 'completed'].forEach(tab => {
            const el = $('badge-' + tab);
            if (!el) return;
            const hasServerCount = orderCounts && Object.prototype.hasOwnProperty.call(orderCounts, tab);
            const count = hasServerCount ? Number(orderCounts[tab]) : filterByTab(rows, tab).length;
            el.textContent = count;
        });
        
        // Selalu kalkulasi badge subtab agar angkanya siap saat user pindah tab
        const processedRows = filterByTab(rows, 'processed');
        let packingCount = 0;
        let readyCount = 0;
        processedRows.forEach(o => {
            const isPackedOrder = fulfilledOrderIds.has(o.id);
            const isReadyToHandover = o.order_status === 'READY_TO_HANDOVER' 
                || (['READY_TO_SHIP', 'PROCESSED'].includes(o.order_status) && isPackedOrder);
            if (isReadyToHandover) readyCount++;
            else packingCount++;
        });
        const badgeSubAll = $('badge-sub-all');
        if (badgeSubAll) badgeSubAll.textContent = processedRows.length;
        const badgeSubPacking = $('badge-sub-packing');
        if (badgeSubPacking) badgeSubPacking.textContent = packingCount;
        const badgeSubReady = $('badge-sub-ready');
        if (badgeSubReady) badgeSubReady.textContent = readyCount;

        const readyRows = filterByTab(rows, 'ready');
        let unpaidCount = 0;
        let processCount = 0;
        let kilatCount = 0;
        let pendingCount = 0;
        let blockedCount = 0;
        readyRows.forEach(o => {
            if (isUnpaidOrder(o)) unpaidCount++;
            else if (isPendingOrder(o)) pendingCount++;
            else if (isKilatPlatformBlocked(o)) blockedCount++;
            else if (isKilatReadyToShip(o)) kilatCount++;
            else processCount++;
        });
        const badgeSubReadyAll = $('badge-sub-ready-all');
        if (badgeSubReadyAll) badgeSubReadyAll.textContent = readyRows.length;
        const badgeSubReadyUnpaid = $('badge-sub-ready-unpaid');
        if (badgeSubReadyUnpaid) badgeSubReadyUnpaid.textContent = unpaidCount;
        const badgeSubReadyProcess = $('badge-sub-ready-process');
        if (badgeSubReadyProcess) badgeSubReadyProcess.textContent = processCount;
        const badgeSubReadyKilat = $('badge-sub-ready-kilat');
        if (badgeSubReadyKilat) badgeSubReadyKilat.textContent = kilatCount;
        const badgeSubReadyPending = $('badge-sub-ready-pending');
        if (badgeSubReadyPending) badgeSubReadyPending.textContent = pendingCount;
        const badgeSubReadyBlocked = $('badge-sub-ready-blocked');
        if (badgeSubReadyBlocked) badgeSubReadyBlocked.textContent = blockedCount;
        
        const shippedRows = filterByTab(rows, 'shipped');
        let shippedOnlyCount = 0;
        let confirmCount = 0;
        shippedRows.forEach(o => {
            if (o.order_status === 'SHIPPED') shippedOnlyCount++;
            else confirmCount++;
        });
        const badgeSubShippedAll = $('badge-sub-shipped-all');
        if (badgeSubShippedAll) badgeSubShippedAll.textContent = shippedRows.length;
        const badgeSubShippedShipped = $('badge-sub-shipped-shipped');
        if (badgeSubShippedShipped) badgeSubShippedShipped.textContent = shippedOnlyCount;
        const badgeSubShippedConfirm = $('badge-sub-shipped-confirm');
        if (badgeSubShippedConfirm) badgeSubShippedConfirm.textContent = confirmCount;

    }

    // ── Render scan log sebagai konten <td> kolom "Item Scan" ──────────────
    function renderScanLogTd(scanLog) {
        if (!scanLog || !scanLog.length) return '<div class="ord-items-cell" style="color:#cbd5e1;font-size:.72rem;font-style:italic">—</div>';

        // Filter: hanya tampilkan item yang punya code valid dan qty > 0
        const valid = scanLog.filter(s => s.code && s.qty > 0);
        if (!valid.length) return '<div class="ord-items-cell" style="color:#cbd5e1;font-size:.72rem;font-style:italic">—</div>';

        const cards = valid.map(s => {
            const name = s.name || '';
            return `<div class="ord-item-card" style="border-color:#ddd6fe;background:#faf5ff">
                <div class="ord-item-qty" style="background:#ede9fe;color:#6d28d9">${s.qty}×</div>
                <div class="ord-item-body">
                    <div class="ord-item-name" style="color:#4c1d95">${esc(s.code)}</div>
                    ${name ? `<div class="ord-item-variant">${esc(name)}</div>` : ''}
                </div>
            </div>`;
        }).join('');

        return `<div class="ord-items-cell">${cards}</div>`;
    }

    // ── Render fulfilled line card (tab Sudah Proses) ────────────────────
    function renderFulfilledLineCard(l) {
        // Skip split parent placeholder — children di-render sendiri
        if (l.is_split_parent) return '';

        const code     = l.item?.code || l.marketplace_sku || '—';
        const name     = l.item?.name || l.marketplace_item_name || '';
        const qtyFul   = l.qty_fulfilled ?? l.qty_ordered ?? 1;
        const qtyOrd   = l.qty_ordered   ?? qtyFul;
        const isShort  = qtyFul < qtyOrd;

        let ket = '';
        if (l.substituted && !l.split_parent_id) {
            const asal = l.marketplace_sku ? ` dari ${l.marketplace_sku}` : '';
            ket = `<div><span class="ord-ket ord-ket-sub">🔄 Diganti${esc(asal)}</span></div>`;
        } else if (l.split_parent_id) {
            ket = `<div><span class="ord-ket ord-ket-spl">✂ Split</span></div>`;
        }
        if (isShort) {
            ket += `<div><span class="ord-ket" style="background:#fef3c7;color:#92400e;border-color:#fde68a">⚠ Kurang ${qtyFul}/${qtyOrd}</span></div>`;
        }
        if (l.notes) {
            ket += `<div style="font-size:.63rem;color:#64748b;margin-top:.1rem">${esc(l.notes)}</div>`;
        }

        // Card border merah jika kurang, kuning jika diganti/split, normal jika ok
        const cardStyle = isShort
            ? 'border-color:#fecaca;background:#fff8f8'
            : (l.substituted || l.split_parent_id) ? 'border-color:#fde68a;background:#fffbeb' : '';

        const bodyHtml = `<div class="ord-item-name">${esc(code)}</div>`
            + (name ? `<div class="ord-item-variant">${esc(name)}</div>` : '')
            + ket;

        return `<div class="ord-item-card" style="${cardStyle}">
            <div class="ord-item-qty">${qtyFul}×</div>
            <div class="ord-item-body">${bodyHtml}</div>
        </div>`;
    }

    // Apakah order punya masalah di fulfilled lines (kurang atau item tidak sesuai)?
    function fulfilledOrderIssues(lines) {
        const active = lines.filter(l => !l.is_split_parent);
        const hasShort = active.some(l => (l.qty_fulfilled ?? l.qty_ordered) < (l.qty_ordered ?? 1));
        const hasSub   = active.some(l => l.substituted && !l.split_parent_id);
        const hasSplit = active.some(l => l.split_parent_id);
        return { hasShort, hasSub, hasSplit, hasAny: hasShort || hasSub || hasSplit };
    }

    // ── Render satu item card ─────────────────────────────────────────────
    function renderItemCard(i, urgent) {
        const internalCode = i.internal_item?.code || null;
        const marketplaceSku = i.model_sku || i.item_sku || null;
        const stockValue = Number(i.internal_item?.stock_available);
        const stockUnit = i.internal_item?.stock_unit || i.internal_item?.unit || 'pcs';
        const stockHtml = internalCode && Number.isFinite(stockValue)
            ? `<span class="ord-item-stock ${stockValue < 0 ? 'is-negative' : ''}">Stok ${esc(stockValue.toLocaleString('id-ID'))} ${esc(stockUnit)}</span>`
            : '';

        let titleHtml = '';
        let detailsHtml = '';
        if (internalCode) {
            titleHtml = `<div class="ord-item-name" style="color:#4c1d95">${esc(internalCode)}</div>`;
            detailsHtml += marketplaceSku
                ? `<div class="ord-item-source">Marketplace: ${esc(marketplaceSku)}</div>`
                : '';
        } else {
            const variantName = i.variant_name || i.item_name || null;
            const dispName = variantName || marketplaceSku || 'Item tidak diketahui';
            titleHtml = `<div class="ord-item-name" style="color:#64748b">${esc(dispName)}</div>`;
            detailsHtml = `<span class="ord-item-nomap">Belum mapping</span>`
                + (marketplaceSku && variantName !== marketplaceSku
                    ? `<div class="ord-item-source">Marketplace: ${esc(marketplaceSku)}</div>`
                    : '');
        }
        const bodyHtml = `<div class="ord-item-title-row">${titleHtml}${itemSalePriceHtml(i)}</div>${detailsHtml}${stockHtml}`;

        const qtyClass = urgent ? 'ord-item-qty urgent' : 'ord-item-qty';
        return `<div class="ord-item-card">
            <div class="${qtyClass}">${i.qty || 1}×</div>
            <div class="ord-item-body">${bodyHtml}</div>
        </div>`;
    }

    // Kilat yang MASIH perlu diatur pengiriman (belum diatur / READY_TO_SHIP tanpa resi).
    // Dipakai untuk memilih tombol aksi: yang sudah diatur TIDAK menampilkan "Atur Pengiriman".
    function kilatNeedsArrange(o) {
        return o.is_kilat && (o.needs_shipping_arrangement || (o.order_status === 'READY_TO_SHIP' && !o.shipping_awb_no));
    }

    // Status platform menjadi acuan untuk memisahkan sub-tab. Saat API live
    // tersedia, api_order_status tetap menyimpan status asli meskipun
    // order_status dinormalisasi menjadi READY_TO_SHIP karena belum ada bukti
    // pengaturan pengiriman.
    function platformOrderStatus(o) {
        return String(o.api_order_status || o.platform_status || o.order_status || '').toUpperCase();
    }

    function isPendingOrder(o) {
        const logisticsStatus = String(o.api_logistics_status || '').toUpperCase();

        // LOGISTICS_READY berarti platform sudah mengizinkan proses pengiriman.
        // Prioritaskan status terbaru ini bila flag pending dari payload lama
        // masih terbawa.
        if (logisticsStatus === 'LOGISTICS_READY') return false;

        return platformOrderStatus(o) === 'PENDING'
            || o.api_platform_pending === true
            || logisticsStatus === 'LOGISTICS_NOT_START';
    }

    function isUnpaidOrder(o) {
        return platformOrderStatus(o) === 'UNPAID';
    }

    function isKilatReadyToShip(o) {
        return !!o.is_kilat
            && (platformOrderStatus(o) === 'READY_TO_SHIP'
                || (o.is_booking && o.needs_shipping_arrangement))
            && !isPendingOrder(o);
    }

    // MATCHED/PROCESSED tanpa bukti pengiriman belum dapat diatur dari
    // platform. Pisahkan dari Kilat supaya tidak terlihat siap diproses.
    function isKilatPlatformBlocked(o) {
        return !!o.is_kilat
            && !o.is_booking
            && kilatNeedsArrange(o)
            && ['MATCHED', 'PROCESSED'].includes(platformOrderStatus(o));
    }

    // ── Table ─────────────────────────────────────────────────────────────
    function filterByTab(rows, tab) {
        // Data acuan HANYA dari API (backend sudah melakukan filter)
        return rows;
    }

    const isMobile = () => window.innerWidth <= 640;

    let _accId = 0;
    function makeAccordion(label, labelStyle, count, bodyHtml) {
        const id = 'acc-' + (++_accId);
        return `<div class="ord-card-section">
            <div class="ord-acc-toggle" onclick="toggleAcc('${id}',this)">
                <span class="ord-acc-label" style="${labelStyle}">${label}
                    <span class="ord-acc-count">${count}</span>
                </span>
                <span class="ord-acc-chevron">▼</span>
            </div>
            <div class="ord-acc-body" id="${id}">
                <div class="ord-items-cell">${bodyHtml}</div>
            </div>
        </div>`;
    }

    window.toggleAcc = function(id, toggle) {
        const body = document.getElementById(id);
        if (!body) return;
        const open = body.classList.toggle('open');
        toggle.classList.toggle('open', open);
    };

    function renderMobileCards(rows) {
        const isPacking = activeTab === 'processed';

        // Urutkan berdasarkan pesanan terbaru
        rows.sort((a, b) => new Date(b.ordered_at || 0) - new Date(a.ordered_at || 0));

        const cards = rows.map((o, idx) => {
            const items  = o.items || [];
            const urgent = ACTIVE_ORDER_STATUSES.includes(o.order_status);
            const isFulfilled = fulfilledOrderIds.has(o.id);
            let printCount = o.print_count || 0;
            if (printCount === 0 && printedOrderIds.has(o.id)) printCount = 1;
            const isPrinted  = printCount > 0;
            const cetakTeks  = printCount > 1 ? `Sudah Cetak (ke-${printCount})` : 'Sudah Cetak';
            const isInPacking = fulfillmentStatusMap.has(o.id) && !fulfilledOrderIds.has(o.id);
            const rowClass = isFulfilled ? 'row-fulfilled' : (isInPacking ? 'row-packing' : (urgent ? 'row-urgent' : ''));

            // Tombol aksi
            let actionBtn = '';
            
            if (activeTab === 'ready') {
                if (o.order_status === 'UNPAID') {
                    actionBtn = `<button class="btn-fulfillment" style="width:100%; justify-content:center; padding:0.55rem; font-size:0.85rem; border-radius:8px; border-color:#22c55e; color:#16a34a; background:#f0fdf4; font-weight:700" onclick="event.stopPropagation(); openChatForOrder(${o.store_id}, '${o.channel_order_id}')">💬 Chat Pembeli</button>`;
                } else if (o.is_kilat && !kilatNeedsArrange(o)) {
                    // Kilat yang sudah diatur/terkirim: jangan tampilkan "Atur Pengiriman" (akan error).
                    const bkSn = o.is_kilat && o.booking_sn ? `'${o.booking_sn}'` : 'null';
                    actionBtn = `<button class="btn-fulfillment" style="width:100%; justify-content:center; padding:0.55rem; font-size:0.85rem; border-radius:8px; border:1px solid #64748b; color:#475569; font-weight:700" onclick="event.stopPropagation(); printDocument(${o.store_id}, '${o.channel_order_id}', ${bkSn})">🖨 Cetak Resi</button>`;
                } else {
                    const bkArg = o.is_kilat ? `, '${o.booking_sn || o.channel_order_id}'` : '';
                    let ofgLabel = '';
                    if (o.shipping_awb_no) {
                        ofgLabel = `<div style="margin-bottom:6px"><span style="font-size:0.55rem; color:#059669; font-weight:700; padding:1px 6px; background:#d1fae5; border:1px solid #34d399; border-radius:4px; display:inline-block; word-break:break-all;">${printedDocOrderSns.has(o.channel_order_id) ? '🖨️ ' : ''}${esc(o.shipping_awb_no)}</span></div>`;
                    }
                    actionBtn = `
                    <div style="display:flex; flex-direction:column; align-items:center; width:100%">
                        ${ofgLabel}
                        <button class="btn-fulfillment" style="width:100%; justify-content:center; padding:0.55rem; font-size:0.85rem; border-radius:8px; border:none; background:var(--shp-accent); color:#fff; font-weight:700; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1)" onclick="event.stopPropagation(); openArrangeShipment(${o.store_id}, '${o.channel_order_id}'${bkArg})">🚚 Atur Pengiriman</button>
                    </div>`;
                }
            } else if (activeTab === 'processed') {
                if (isFulfilled) {
                    actionBtn = `<div class="btn-fulfillment done" style="width:100%; justify-content:center; padding:0.55rem; font-size:0.85rem; border-radius:8px; font-weight:700">✓ Selesai</div>`;
                } else if (isInPacking) {
                    actionBtn = `<button class="btn-fulfillment" style="width:100%; justify-content:center; padding:0.55rem; font-size:0.85rem; border-radius:8px; background:#f8fafc; color:var(--shp-accent); border:1px solid rgba(148,163,184,.25); font-weight:700" onclick="window.location='/sales/shipments'">Lanjut ke Shipment →</button>`;
                } else {
                    actionBtn = `<button class="btn-fulfillment" style="width:100%; justify-content:center; padding:0.55rem; font-size:0.85rem; border-radius:8px; font-weight:700; border:1px solid #cbd5e1" onclick="window.location='/sales/shipments'">📦 Ke Shipment</button>`;
                }
            } else if (activeTab === 'processed_instant') {
                // Tab Instan (mobile): sediakan Atur Pengiriman manual + Cetak Resi
                // supaya user tidak bingung. Backend aman meski sudah auto-arrange.
                if (o.order_status === 'UNPAID') {
                    actionBtn = `<button class="btn-fulfillment" style="width:100%; justify-content:center; padding:0.55rem; font-size:0.85rem; border-radius:8px; border-color:#22c55e; color:#16a34a; background:#f0fdf4; font-weight:700" onclick="event.stopPropagation(); openChatForOrder(${o.store_id}, '${o.channel_order_id}')">💬 Chat Pembeli</button>`;
                } else {
                    const bkSn = o.is_kilat && o.booking_sn ? `'${o.booking_sn}'` : 'null';
                    actionBtn = `
                    <div style="display:flex; flex-direction:column; gap:6px; width:100%;">
                        <button class="btn-fulfillment" style="width:100%; justify-content:center; padding:0.55rem; font-size:0.85rem; border-radius:8px; border:none; background:var(--shp-accent); color:#fff; font-weight:700; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1)" onclick="event.stopPropagation(); openArrangeShipment(${o.store_id}, '${o.channel_order_id}')">🚚 Atur Pengiriman</button>
                        <button class="btn-fulfillment" style="width:100%; justify-content:center; padding:0.55rem; font-size:0.85rem; border-radius:8px; border:1px solid #64748b; color:#475569; font-weight:700" onclick="event.stopPropagation(); printDocument(${o.store_id}, '${o.channel_order_id}', ${bkSn})">🖨 Cetak Resi</button>
                    </div>`;
                }
            } else {
                if (o.order_status === 'UNPAID') {
                    actionBtn = `<button class="btn-fulfillment" style="width:100%; justify-content:center; padding:0.55rem; font-size:0.85rem; border-radius:8px; border-color:#22c55e; color:#16a34a; background:#f0fdf4; font-weight:700" onclick="event.stopPropagation(); openChatForOrder(${o.store_id}, '${o.channel_order_id}')">💬 Chat Pembeli</button>`;
                } else if (o.needs_shipping_arrangement || (o.order_status === 'READY_TO_SHIP' && !o.shipping_awb_no)) {
                    actionBtn = `<button class="btn-fulfillment" style="width:100%; justify-content:center; padding:0.55rem; font-size:0.85rem; border-radius:8px; border:none; background:var(--shp-accent); color:#fff; font-weight:700; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1)" onclick="event.stopPropagation(); openArrangeShipment(${o.store_id}, '${o.channel_order_id}')">🚚 Atur Pengiriman</button>`;
                } else if (o.order_status === 'READY_TO_SHIP' || o.order_status === 'PROCESSED' || o.order_status === 'SHIPPED') {
                    const bkSn = o.is_kilat && o.booking_sn ? `'${o.booking_sn}'` : 'null';
                    actionBtn = `<button class="btn-fulfillment" style="width:100%; justify-content:center; padding:0.55rem; font-size:0.85rem; border-radius:8px; border:1px solid #64748b; color:#475569; font-weight:700" onclick="event.stopPropagation(); printDocument(${o.store_id}, '${o.channel_order_id}', ${bkSn})">🖨 Cetak Resi</button>`;
                }
            }

            // Sub-info
            let dateTimeText = '';
            if (o.ordered_at) {
                const d = new Date(o.ordered_at);
                if (!isNaN(d.valueOf())) {
                    dateTimeText = orderDateText(o.ordered_at);
                }
            }
            const carrier     = (o.shipping_carrier || '').toLowerCase();
            const isInstant   = carrier.includes('instant') || carrier.includes('same day') || carrier.includes('sameday');
            let instantBadge  = isInstant ? `<span style="font-size:.65rem;background:#fef08a;color:#854d0e;border-radius:4px;padding:1px 5px;font-weight:800;border:1px solid #fde047;margin-right:4px;">⚡ INSTAN</span>` : '';
            const isAdvanceFulfillment = o.raw_json && (o.raw_json.shipped_by_advance_fulfilment === 'Y' || o.raw_json.shipped_by_advance_fulfillment === 'Y');
            if (isAdvanceFulfillment) {
                instantBadge += `<span style="font-size:.65rem;background:#fee2e2;color:#991b1b;border-radius:4px;padding:1px 5px;font-weight:800;border:1px solid #fca5a5;margin-right:4px;" title="Shopee Advance Fulfillment">🚀 KILAT</span>`;
            }

            // ── Section: Item Produk (Tampil default tanpa accordion di mobile)
            const itemCards = items.map(i => renderItemCard(i, urgent)).join('');
            const itemsSection = `<div class="ord-card-section" style="padding:0.6rem 0.9rem; border-top:1px dashed #e2e8f0; background:#f8fafc">
                <div class="ord-items-invoice-head"><span>Item Produk (${items.length})</span>${itemPaymentSummaryHtml(o)}</div>
                <div class="ord-items-cell" style="padding:0">${itemCards}</div>
            </div>`;

            // ── Section: Item Resolve (hanya packing, accordion)
            let resolveSection = '';
            if (isPacking) {
                const resolveLines = (o.fulfillment_resolve_lines || []).filter(l => l.code);
                if (resolveLines.length) {
                    const resolveCards = resolveLines.map(l => `<div class="ord-item-card" style="border-color:#bfdbfe;background:#eff6ff">
                        <div class="ord-item-qty" style="background:#dbeafe;color:#1d4ed8">${l.qty_ordered}×</div>
                        <div class="ord-item-body">
                            <div class="ord-item-name" style="color:#1e3a5f">${esc(l.code)}</div>
                            ${l.name ? `<div class="ord-item-variant">${esc(l.name)}</div>` : ''}
                        </div>
                    </div>`).join('');
                    resolveSection = makeAccordion('✅ Item Resolve', 'color:#1d4ed8', resolveLines.length, resolveCards);
                }
            }

            // ── Section: Item Scan (accordion)
            let scanSection = '';
            if (activeTab === 'processed' || activeTab === 'shipped') {
                const validScan = (o.fulfillment_scan_log || []).filter(s => s.code && s.qty > 0);
                if (validScan.length) {
                    const scanCards = validScan.map(s => `<div class="ord-item-card" style="border-color:#ddd6fe;background:#faf5ff">
                        <div class="ord-item-qty" style="background:#ede9fe;color:#6d28d9">${s.qty}×</div>
                        <div class="ord-item-body">
                            <div class="ord-item-name" style="color:#4c1d95">${esc(s.code)}</div>
                            ${s.name ? `<div class="ord-item-variant">${esc(s.name)}</div>` : ''}
                        </div>
                    </div>`).join('');
                    scanSection = makeAccordion('📦 Item Scan', 'color:#7c3aed', validScan.length, scanCards);
                }
            }

            let slaBadge = '';
            if (o.ship_by_date && ACTIVE_ORDER_STATUSES.includes(o.order_status) && !['shipped','completed'].includes(activeTab)) {
                const hoursLeft = (new Date(o.ship_by_date) - new Date()) / 3600000;
                if (hoursLeft < 12 && hoursLeft > 0) {
                    slaBadge = `<span style="font-size:0.65rem; background:#fef3c7; color:#d97706; padding:1px 6px; border-radius:4px; font-weight:700; border:1px solid #fde68a; margin-left:4px;" title="SLA Pengiriman < 12 Jam">⚠️ SLA ${Math.floor(hoursLeft)}j</span>`;
                } else if (hoursLeft <= 0) {
                    slaBadge = `<span style="font-size:0.65rem; background:#fee2e2; color:#dc2626; padding:1px 6px; border-radius:4px; font-weight:700; border:1px solid #fecaca; margin-left:4px;" title="Terlambat (SLA Terlewati)">🚨 TELAT</span>`;
                }
            }

            let orderIdHtml = orderCopyHtml(o.channel_order_id || '—');
            if (o.is_kilat) orderIdHtml = '<span title="Pesanan Kilat (Booking Shopee)" style="font-size:.6rem;font-weight:800;color:#a16207;background:#fefce8;border:1px solid #fde68a;border-radius:4px;padding:1px 5px;margin-right:5px;white-space:nowrap;">⚡ KILAT</span>' + orderIdHtml;
            if (isAdvanceFulfillment) {
                orderIdHtml = `No. Reservasi ${orderIdHtml}`;
            }
            orderIdHtml += slaBadge;

            return `<div class="ord-card ${rowClass}${isPrinted && !isFulfilled ? ' row-printed' : ''}" style="overflow:hidden; animation-delay: ${idx * 0.04}s">
                <div class="ord-card-header">
                    <div class="ord-card-meta">
                        <div class="ord-id">${orderIdHtml}</div>
                        ${orderContextHtml(o)}
                        ${buyerPaymentMethodHtml(o)}
                        <div class="ord-card-sub">
                            ${instantBadge}
                        </div>
                    </div>
                    <div class="ord-card-actions" style="text-align:right">
                        ${isPrinted && !isFulfilled ? `<div style="margin-bottom:4px;"><span style="font-size:.6rem;background:#e0f2fe;color:#0369a1;border-radius:4px;padding:1px 5px;font-weight:700">🖨 Cetak</span></div>` : ''}
                        ${dateTimeText ? `<div style="font-size:0.65rem; color:#64748b; font-weight:600;">${dateTimeText}</div>` : ''}
                    </div>
                </div>
                ${itemsSection}
                ${resolveSection}
                ${scanSection}
                <div style="padding:0.75rem 0.9rem; border-top:1px solid #e2e8f0; background:#fff">
                    ${actionBtn}
                </div>
            </div>`;
        }).join('');

        return `<div class="ord-cards">${cards}</div>
        <div class="gf-table-foot" style="padding:.6rem .9rem">
            <span class="gf-table-foot-hint">${rows.length} order ditampilkan</span>
        </div>`;
    }

    function renderProcessCardList(rows, tabName = 'ready') {
        const isProcessed = tabName === 'processed' || tabName === 'processed_instant' || tabName === 'ready_to_handover';
        
        let selectAllHtml = '';
        if (isProcessed && rows.length > 0) {
            selectAllHtml = `
            <div style="display:flex; align-items:center; justify-content:space-between; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                <label style="display:flex; align-items:center; gap: 8px; font-weight:600; cursor:pointer; margin:0; color:#0f172a; font-size:0.9rem;">
                    <input type="checkbox" id="chkSelectAllOrders" class="form-check-input" style="width:1.25rem;height:1.25rem;cursor:pointer;accent-color:#0284c7;" onclick="document.querySelectorAll('.chk-print-order').forEach(el => el.checked = this.checked)">
                    Pilih Semua ${rows.length} Pesanan
                </label>
                <div style="font-size:0.8rem; color:#64748b;">Gunakan tombol 🖨️ di kanan atas untuk cetak</div>
            </div>`;
        }

        const pkRows = rows.map(o => {
            const items       = o.items || [];
            const isFulfilled = fulfilledOrderIds.has(o.id);
            let printCount = o.print_count || 0;
            if (printCount === 0 && printedOrderIds.has(o.id)) printCount = 1;
            const isPrinted  = printCount > 0;
            const cetakTeks  = printCount > 1 ? `Sudah Cetak (ke-${printCount})` : 'Sudah Cetak';
            const isInPacking = fulfillmentStatusMap.has(o.id) && !isFulfilled;
            const fStatus     = fulfillmentStatusMap.get(o.id)?.status || '';
            const carrier     = (o.shipping_carrier || '').toLowerCase();
            const isInstant   = carrier.includes('instant') || carrier.includes('same day') || carrier.includes('sameday');
            let instantBadge  = isInstant ? `<span style="font-size:.65rem;background:#fef08a;color:#854d0e;border-radius:4px;padding:1px 5px;font-weight:800;border:1px solid #fde047;margin-right:4px;">⚡ INSTAN</span>` : '';
            const isAdvanceFulfillment = o.raw_json && (o.raw_json.shipped_by_advance_fulfilment === 'Y' || o.raw_json.shipped_by_advance_fulfillment === 'Y');
            if (isAdvanceFulfillment) {
                instantBadge += `<span style="font-size:.65rem;background:#fee2e2;color:#991b1b;border-radius:4px;padding:1px 5px;font-weight:800;border:1px solid #fca5a5;margin-right:4px;" title="Shopee Advance Fulfillment">🚀 KILAT</span>`;
            }

            // Badge fulfillment
            const fBadgeHtml = isFulfilled
                ? `<span class="pk-badge-ok">✓ Selesai</span>`
                : isInPacking
                    ? (fStatus === 'pending_review'
                        ? `<span class="pk-badge-short">⏳ Siap Konfirmasi</span>`
                        : `<span style="display:inline-flex;align-items:center;gap:.2rem;font-size:.65rem;font-weight:700;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:999px;padding:.08rem .45rem">🔄 Dalam Proses</span>`)
                    : `<span style="display:inline-flex;align-items:center;gap:.2rem;font-size:.65rem;font-weight:700;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:999px;padding:.08rem .45rem">● Belum Proses</span>`;

            // Item chips (compact) for 'ready' tab
            let itemChips = '';
            let moreChip = '';
            if (!isProcessed) {
                itemChips = items.slice(0, 5).map(i => {
                    const code = i.internal_item?.code || i.model_sku || i.item_sku || '?';
                    const disp = i.variant_name || i.internal_item?.name || i.item_name || code;
                    const qty  = i.qty || 1;
                    const mapped = !!i.internal_item?.code;
                    return `<span style="display:inline-flex;align-items:center;gap:.2rem;font-size:.65rem;font-weight:700;
                        background:${mapped ? '#f1f5f9' : '#fef3c7'};color:${mapped ? '#334155' : '#92400e'};
                        border-radius:6px;padding:.1rem .38rem;font-family:'SF Mono','Menlo',monospace;white-space:nowrap">
                        ${qty}× ${esc(disp)}
                    </span>`;
                }).join('');
                moreChip = items.length > 5
                    ? `<span style="font-size:.63rem;color:#94a3b8;font-weight:600">+${items.length - 5} lagi</span>`
                    : '';
            }

            // Table format for 'processed' and 'ready_to_handover' tabs (menyerupai Rekon)
            let itemsSection = '';
            
            if (isProcessed) {
                let linesHtml = `<div style="overflow-x:auto; margin-top:.75rem; border:1px solid #e2e8f0; border-radius:8px;">
                    <table style="width:100%; border-collapse:collapse; min-width:600px;">
                    <thead style="background:#f8fafc; font-size:.65rem; color:#64748b; text-align:left; border-bottom:1px solid #e2e8f0;"><tr>
                        <th style="padding:.5rem .8rem">BARANG</th>
                        <th style="padding:.5rem .8rem; text-align:right">DIPESAN</th>
                        <th style="padding:.5rem .8rem; text-align:right">TERSEDIA</th>
                        <th style="padding:.5rem .8rem; text-align:right">KURANG</th>
                        <th style="padding:.5rem .8rem; text-align:center">STATUS</th>
                        <th style="padding:.5rem .8rem">BARANG PENGGANTI</th>
                    </tr></thead><tbody style="font-size:.75rem">`;

                const validScan = (o.fulfillment_scan_log || []).filter(s => s.code && s.qty > 0);
                const resolveLines = (o.fulfillment_resolve_lines || []).filter(l => l.code);
                
                const scans = {};
                validScan.forEach(s => scans[s.code] = (scans[s.code] || 0) + s.qty);
                
                items.forEach(i => {
                    const code = i.internal_item?.code || i.model_sku || i.item_sku || '?';
                    const name = i.variant_name || i.internal_item?.name || i.item_name || '';
                    const dipesan = i.qty || 1;
                    const tersedia = scans[code] || 0;
                    const kurang = Math.max(0, dipesan - tersedia);
                    
                    let statusHtml = '';
                    if (kurang === 0) statusHtml = '<span style="color:#16a34a;font-weight:800">OK</span>';
                    else if (tersedia > 0) statusHtml = '<span style="color:#d97706;font-weight:800">Sebagian</span>';
                    else statusHtml = '<span style="background:#f1f5f9;color:#64748b;padding:2px 6px;border-radius:4px;font-size:.65rem;font-weight:600;white-space:nowrap;">Belum Tertaut</span>';
                    
                    // Cari item di resolveLines yang merupakan substitusi dari item ini
                    let subHtml = '<span style="color:#cbd5e1">—</span>';
                    if (kurang > 0 && resolveLines.length > 0) {
                        const mSku = i.model_sku || i.item_sku;
                        let subsList = resolveLines.filter(r => 
                            (r.substituted || r.split_parent_id || r.code !== code) && 
                            r.marketplace_sku === mSku
                        );
                        
                        // Fallback untuk order single item
                        if (subsList.length === 0 && items.length === 1) {
                            subsList = resolveLines.filter(r => r.substituted || r.split_parent_id || r.code !== code);
                        }
                        
                        if (subsList.length > 0) {
                            subHtml = subsList.map(r => `<div style="font-weight:700;color:#1d4ed8">${r.code} <span style="font-weight:400;color:#64748b">×${r.qty_ordered}</span></div>`).join('');
                        }
                    }

                    linesHtml += `<tr style="border-bottom:1px solid #f1f5f9">
                        <td style="padding:.5rem .8rem"><div style="font-weight:800;color:#334155;font-size:.8rem">${esc(code)}</div><div style="font-size:.68rem;color:#64748b">${esc(name)}</div></td>
                        <td style="padding:.5rem .8rem; text-align:right; font-weight:700; font-size:.8rem">${dipesan}</td>
                        <td style="padding:.5rem .8rem; text-align:right; font-size:.8rem">${tersedia}</td>
                        <td style="padding:.5rem .8rem; text-align:right; color:${kurang>0?'#dc2626':'#334155'}; font-weight:800; font-size:.8rem">${kurang>0?'-'+kurang:kurang}</td>
                        <td style="padding:.5rem .8rem; text-align:center">${statusHtml}</td>
                        <td style="padding:.5rem .8rem">${subHtml}</td>
                    </tr>`;
                });
                
                linesHtml += `</tbody></table></div>`;
                itemsSection = linesHtml;
            }

            // Tombol aksi
            let actionBtn = '';
            let logisticsBtn = '';

            // Logistics Buttons
            if (o.order_status === 'READY_TO_SHIP' && !(o.is_kilat && !kilatNeedsArrange(o))) {
                logisticsBtn = `<button class="btn-review" style="background:#fef9c3;color:#854d0e;border-color:#fef08a"
                    onclick="openArrangeShipment(${o.store_id}, '${o.channel_order_id}')">🚚 Atur Kirim</button>`;
            } else if (o.order_status === 'PROCESSED' || o.order_status === 'SHIPPED' || (o.is_kilat && !kilatNeedsArrange(o))) {
                const bkSn = o.is_kilat && o.booking_sn ? `'${o.booking_sn}'` : 'null';
                logisticsBtn = `<button class="btn-review" style="background:#f1f5f9;color:#475569;border-color:#e2e8f0"
                    onclick="printDocument(${o.store_id}, '${o.channel_order_id}', ${bkSn})">🖨 Cetak Resi</button>`;
            }
            if (isInstant && (o.order_status === 'PROCESSED' || o.order_status === 'SHIPPED')) {
                logisticsBtn += `<button class="btn-review mt-1" style="background:#fffbeb;color:#b45309;border-color:#fde68a"
                    onclick="checkBookingDriver(${o.store_id}, '${o.channel_order_id}')">🔍 Cek Driver / Booking</button>`;
            }
            logisticsBtn += `<button class="btn-review mt-1" style="background:#f3f4f6;color:#374151;border-color:#d1d5db"
                    onclick="checkOrderDetailRaw(${o.store_id}, '${o.channel_order_id}')">👁 Cek Detail API</button>`;

            if (isFulfilled) {
                actionBtn = `<div class="btn-review" style="background:#f0fdf4;color:#16a34a;border-color:#bbf7d0;cursor:default">✓ Selesai</div>`;
            } else if (isInPacking) {
                actionBtn = `<button class="btn-review" style="background:#f8fafc;color:var(--shp-accent);border-color:rgba(148,163,184,.25)"
                    title="Buat atau lanjutkan Draft Shipment Marketplace, lalu scan resi/order ini."
                    onclick="window.location='/sales/shipments'">Lanjut ke Shipment →</button>`;
            } else {
                actionBtn = `<button class="btn-review"
                    title="Buat atau lanjutkan Draft Shipment Marketplace, lalu scan resi/order ini."
                    onclick="window.location='/sales/shipments'">📦 Ke Shipment</button>`;
            }

            const dataIssue = o.has_data_issues
                ? `<span style="font-size:.63rem;font-weight:700;color:#d97706;background:#fef3c7;border:1px solid #fde68a;border-radius:999px;padding:.05rem .38rem">⚠ Belum mapping</span>`
                : '';

            let logBadge = '';
            if (o.logistics_status) {
                let statusText = o.logistics_status.replace('LOGISTICS_', '').replace(/_/g, ' ');
                // Terjemahan status umum Shopee agar lebih mudah dipahami
                if (o.logistics_status === 'LOGISTICS_REQUEST_CREATED') statusText = 'Permintaan Dibuat';
                else if (o.logistics_status === 'LOGISTICS_READY_TO_SHIP') statusText = 'Siap Dikirim';
                else if (o.logistics_status === 'LOGISTICS_NOT_START') statusText = 'Belum Dimulai';
                else if (o.logistics_status === 'LOGISTICS_SHIPPED') statusText = 'Sudah Dikirim';
                
                if (tabName === 'processed' && o.logistics_status === 'LOGISTICS_REQUEST_CREATED') {
                    // Sembunyikan badge 'Permintaan Dibuat' jika di tab Sedang Dikemas
                } else {
                    logBadge = `<span style="font-size:0.65rem; color:#4f46e5; background:#e0e7ff; border:1px solid #c7d2fe; border-radius:4px; padding:1px 6px; font-weight:700;" title="Status Logistik dari Marketplace">📡 ${statusText}</span>`;
                }
            }

            let orderDateHtml = '';
            if (o.ordered_at || o.created_at) {
                const dateVal = o.ordered_at || o.created_at;
                let isLate = false;
                
                const d = new Date(dateVal);
                const todayNoon = new Date();
                todayNoon.setHours(12, 0, 0, 0);
                if (d < todayNoon) {
                    isLate = true;
                }
                
                if (isLate) {
                    orderDateHtml = `<span class="pk-meta-text" style="color:#dc2626; font-weight:700; background:#fef2f2; padding:1px 5px; border-radius:4px; border:1px solid #fecaca;" title="Pesanan masuk sebelum jam 12 hari ini / kemarin">🗓 ${fmt(dateVal)}</span><span class="pk-meta-text" style="color:#e2e8f0">·</span>`;
                } else {
                    orderDateHtml = `<span class="pk-meta-text" style="color:#64748b">🗓 ${fmt(dateVal)}</span><span class="pk-meta-text" style="color:#e2e8f0">·</span>`;
                }
            }

            let slaBadge = '';
            if (o.ship_by_date && ACTIVE_ORDER_STATUSES.includes(o.order_status) && !['shipped','completed'].includes(activeTab)) {
                const hoursLeft = (new Date(o.ship_by_date) - new Date()) / 3600000;
                if (hoursLeft < 12 && hoursLeft > 0) {
                    slaBadge = `<span style="font-size:0.65rem; background:#fef3c7; color:#d97706; padding:1px 6px; border-radius:4px; font-weight:700; border:1px solid #fde68a; margin-left:4px;" title="SLA Pengiriman < 12 Jam">⚠️ SLA ${Math.floor(hoursLeft)}j</span>`;
                } else if (hoursLeft <= 0) {
                    slaBadge = `<span style="font-size:0.65rem; background:#fee2e2; color:#dc2626; padding:1px 6px; border-radius:4px; font-weight:700; border:1px solid #fecaca; margin-left:4px;" title="Terlambat (SLA Terlewati)">🚨 TELAT</span>`;
                }
            }

            let orderIdHtml = orderCopyHtml(o.channel_order_id || '—');
            if (o.is_kilat) orderIdHtml = '<span title="Pesanan Kilat (Booking Shopee)" style="font-size:.6rem;font-weight:800;color:#a16207;background:#fefce8;border:1px solid #fde68a;border-radius:4px;padding:1px 5px;margin-right:5px;white-space:nowrap;">⚡ KILAT</span>' + orderIdHtml;
            if (isAdvanceFulfillment) {
                orderIdHtml = `No. Reservasi ${orderIdHtml}`;
            }
            orderIdHtml += slaBadge;

            return `<div class="pk-row ${isPrinted && !isFulfilled ? 'row-printed' : ''}" style="flex-direction:column; align-items:stretch">
                <div style="display:flex; justify-content:space-between; align-items:center; width:100%">
                    <div class="pk-row-left">
                        <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                            ${isProcessed ? `<input type="checkbox" class="form-check-input chk-print-order" data-order-sn="${esc(o.channel_order_id)}" style="width:1.2rem;height:1.2rem;cursor:pointer;accent-color:#0284c7;margin-right:2px;">` : ''}
                            <span class="pk-order-id">${orderIdHtml}</span>
                            ${orderContextHtml(o)}
                            ${instantBadge}
                            ${logBadge}
                            ${isPrinted && !isFulfilled ? `<span style="font-size:0.7rem; background:#e0f2fe; color:#0369a1; border-radius:4px; padding:1px 6px; font-weight:700; border:1px solid #7dd3fc;">🖨 ${cetakTeks}</span>` : ''}
                            ${fBadgeHtml}
                            ${dataIssue}
                        </div>
                        <div class="pk-row-meta" style="margin-top:.28rem; display:flex; align-items:center; flex-wrap:wrap; gap:.35rem">
                            ${orderDateHtml}
                            <div style="display:flex;flex-wrap:wrap;gap:.25rem;align-items:center">
                                ${itemChips}${moreChip}
                            </div>
                        </div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:0.4rem; align-items:flex-end;">
                        ${o.shipping_awb_no ? `<span style="font-size:0.55rem; color:#059669; font-weight:700; padding:1px 6px; background:#d1fae5; border:1px solid #34d399; border-radius:4px;">${printedDocOrderSns.has(o.channel_order_id) ? '🖨️ ' : ''}${esc(o.shipping_awb_no)}</span>` : ''}
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            ${logisticsBtn}
                            ${actionBtn}
                        </div>
                    </div>
                </div>
                ${isProcessed ? `<div style="margin-top:0.8rem">${itemsSection}</div>` : ''}
            </div>`;
        }).join('');

        const count = rows.length;
        const titleText = isProcessed ? '📋 Sedang Dikemas' : '⚡ Perlu Diproses';
        const subText = isProcessed ? 'Order yang sudah diatur pengirimannya' : 'Order baru menunggu untuk diproses';
        const badgeColor = isProcessed ? '#3b82f6' : '#f59e0b';

        return `<div class="pk-section">
            <div class="pk-section-header">
                <div>
                    <div class="pk-section-title">${titleText}</div>
                    <div class="pk-section-sub">${subText}</div>
                </div>
                <span class="pk-count-badge" style="background:${badgeColor}">${count} order</span>
            </div>
            ${pkRows}
        </div>
        <div class="gf-table-foot">
            <span class="gf-table-foot-hint">${count} order ditampilkan</span>
        </div>`;
    }

    function renderPackingCardList(rows) {
        const pkRows = rows.map(o => {
            const summary = o.fulfillment_packing_summary;
            const fStatus = fulfillmentStatusMap.get(o.id)?.status || '';
            // Packing info text
            const totalOrd = summary?.total_ordered   ?? 0;
            const totalFul = summary?.total_fulfilled ?? 0;
            const hasShort = summary?.has_shortage    ?? false;
            const packInfo = totalOrd > 0
                ? `<span class="pk-pack-info ${hasShort ? 'short' : ''}">${totalFul}/${totalOrd} dipacking</span>`
                : '';

            // Status badge
            const statusBadgeEl = hasShort
                ? `<span class="pk-badge-short">⚠ kurang</span>`
                : (totalFul > 0 ? `<span class="pk-badge-ok">✓ lengkap</span>` : '');

            // fStatus label
            const fStatusLabel = fStatus === 'packed' ? '📦 Packed'
                : fStatus === 'picking' ? '🔄 Picking'
                : fStatus === 'pending_review' ? '⏳ Review' : '';

            let printCount = o.print_count || 0;
            if (printCount === 0 && printedOrderIds.has(o.id)) printCount = 1;
            const isPrinted  = printCount > 0;
            const cetakTeks  = printCount > 1 ? `Sudah Cetak (ke-${printCount})` : 'Sudah Cetak';
            const bkSn = o.is_kilat && o.booking_sn ? `'${o.booking_sn}'` : 'null';
            const printBtn = `<button class="btn-review" style="background:${isPrinted ? '#e0f2fe' : '#f1f5f9'};color:${isPrinted ? '#0369a1' : '#475569'};border-color:${isPrinted ? '#7dd3fc' : '#e2e8f0'}" onclick="printDocument(${o.store_id}, '${o.channel_order_id}', ${bkSn})">🖨 ${isPrinted ? cetakTeks : 'Cetak'}</button>`;

            return `<div class="pk-row ${isPrinted ? 'row-printed' : ''}">
                <div class="pk-row-left">
                    <div class="pk-order-id">
                        ${o.is_kilat ? '<span title="Pesanan Kilat (Booking Shopee)" style="font-size:.6rem;font-weight:800;color:#a16207;background:#fefce8;border:1px solid #fde68a;border-radius:4px;padding:1px 5px;margin-right:5px;white-space:nowrap;">⚡ KILAT</span>' : ''}
                        ${orderCopyHtml(o.channel_order_id || '—')}
                        ${o.shipping_awb_no ? `<span style="font-size:0.55rem; color:#059669; margin-left:8px; font-weight:600; padding:2px 6px; background:#d1fae5; border-radius:4px;">${printedDocOrderSns.has(o.channel_order_id) ? '🖨️ ' : ''}${esc(o.shipping_awb_no)}</span>` : ''}
                    </div>
                    <div class="pk-row-meta">
                        ${orderContextHtml(o)}
                        ${fStatusLabel ? `<span class="pk-meta-text" style="font-weight:700">${fStatusLabel}</span>` : ''}
                        ${statusBadgeEl}
                        ${packInfo}
                    </div>
                </div>
                <div style="display:flex; flex-direction:column; gap:0.25rem;">
                    <button class="btn-review" onclick="openReviewModal(${o.id})">🔍 Review</button>
                    ${printBtn}
                </div>
            </div>`;
        }).join('');

        const count = rows.length;
        return `<div class="pk-section">
            <div class="pk-section-header">
                <div>
                    <div class="pk-section-title">📋 Sedang Proses</div>
                    <div class="pk-section-sub">Order sudah diproses — menunggu konfirmasi potong stok</div>
                </div>
                <span class="pk-count-badge">${count} order</span>
            </div>
            ${pkRows}
        </div>
        <div class="gf-table-foot">
            <span class="gf-table-foot-hint">${count} order ditampilkan</span>
        </div>`;
    }

    function renderTable() {
        // Tab Retur/Refund/Batal dikelola terpisah oleh loadRrc() (data live API).
        // Tab Sync dikelola loadSyncTab(). Jangan timpa isinya dengan data order lokal.
        if (activeTab === 'rrc' || activeTab === 'sync') return;
        const body = $('ordersBody');
        let rows = applyFilters(orders.filter(inRange));
        rows = filterByTab(rows, activeTab);

        if (activeTab === 'processed' && subTabProcessed !== 'all') {
            rows = rows.filter(o => {
                const isPackedOrder = fulfilledOrderIds.has(o.id);
                const isReadyToHandover = o.order_status === 'READY_TO_HANDOVER' 
                    || (['READY_TO_SHIP', 'PROCESSED'].includes(o.order_status) && isPackedOrder);
                if (subTabProcessed === 'packing') return !isReadyToHandover;
                if (subTabProcessed === 'ready') return isReadyToHandover;
                return true;
            });
        }

        if (activeTab === 'ready' && subTabReady !== 'all') {
            rows = rows.filter(o => {
                // Order kilat dipisahkan ke sub-tab "Pengiriman Kilat" (keluar dari yang lain).
                if (subTabReady === 'kilat') {
                    return isKilatReadyToShip(o);
                }
                if (subTabReady === 'pending') return isPendingOrder(o);
                if (subTabReady === 'blocked') return isKilatPlatformBlocked(o);
                if (subTabReady === 'unpaid')  return isUnpaidOrder(o);
                if (subTabReady === 'process') {
                    return !isUnpaidOrder(o)
                        && !isPendingOrder(o)
                        && !o.is_kilat;
                }
                return true;
            });
        }
        
        if (activeTab === 'shipped' && subTabShipped !== 'all') {
            rows = rows.filter(o => {
                if (subTabShipped === 'shipped') return o.order_status === 'SHIPPED';
                if (subTabShipped === 'confirm') return o.order_status === 'TO_CONFIRM_RECEIVE';
                return true;
            });
        }

        if (!rows.length) {
            const { icon, text } = TAB_EMPTY[activeTab] || TAB_EMPTY.all;
            body.innerHTML = `<div class="ord-empty"><div class="ord-empty-icon">${icon}</div>${text}</div>`;
            return;
        }

        // Urutkan pesanan: Instan diletakkan paling atas
        rows.sort((a, b) => {
            const carrierA = (a.shipping_carrier || '').toLowerCase();
            const carrierB = (b.shipping_carrier || '').toLowerCase();
            
            const isInstA = carrierA.includes('instant') || carrierA.includes('same day') || carrierA.includes('sameday');
            const isInstB = carrierB.includes('instant') || carrierB.includes('same day') || carrierB.includes('sameday');
            
            // Urutan prioritas: Instan (2) -> Reguler (1) -> Kilat (0)
            const scoreA = isInstA ? 2 : (a.is_kilat ? 0 : 1);
            const scoreB = isInstB ? 2 : (b.is_kilat ? 0 : 1);
            
            if (scoreA !== scoreB) return scoreB - scoreA;
            
            // Waktu pesanan
            const timeA = new Date(a.ordered_at || a.created_at || 0).getTime();
            const timeB = new Date(b.ordered_at || b.created_at || 0).getTime();
            
            // Jika Reguler (bukan Instan, bukan Kilat): Urutkan Terbaru (Descending)
            if (scoreA === 1) {
                return timeB - timeA;
            }
            // Jika Instan atau Kilat: Urutkan Terlama (Ascending - agar yang paling lama menunggu diproses duluan)
            return timeA - timeB;
        });

        if (isMobile()) {
            body.innerHTML = renderMobileCards(rows);
            return;
        }

        const tableRows = rows.map(o => {
            const items  = o.items || [];
            const urgent = ACTIVE_ORDER_STATUSES.includes(o.order_status);

            let dateHtml = '—';
            if (o.ordered_at || o.created_at) {
                const dateVal = o.ordered_at || o.created_at;
                const d = new Date(dateVal);
                const todayNoon = new Date();
                todayNoon.setHours(12, 0, 0, 0);
                
                if (['ready', 'processed', 'ready_to_handover'].includes(activeTab) && d < todayNoon) {
                    dateHtml = `<span style="color:#ef4444; font-weight:600; font-size:0.65rem; background:#fef2f2; padding:1px 4px; border-radius:4px; border:1px solid #fecaca;" title="Pesanan masuk sebelum jam 12 hari ini / kemarin">${esc(orderDateText(dateVal))}</span>`;
                } else {
                    dateHtml = `<span style="color:#94a3b8; font-size:0.65rem; font-weight:500;">${esc(orderDateText(dateVal))}</span>`;
                }
            }

            let logBadge = '';
            if (o.logistics_status) {
                let statusText = o.logistics_status.replace('LOGISTICS_', '').replace(/_/g, ' ');
                if (o.logistics_status === 'LOGISTICS_REQUEST_CREATED') statusText = 'Permintaan Dibuat';
                else if (o.logistics_status === 'LOGISTICS_READY_TO_SHIP') statusText = 'Siap Dikirim';
                else if (o.logistics_status === 'LOGISTICS_NOT_START') statusText = 'Belum Dimulai';
                else if (o.logistics_status === 'LOGISTICS_SHIPPED') statusText = 'Sudah Dikirim';
                
                logBadge = `<span class="ord-shipping-status">📡 ${esc(statusText)}</span>`;
            }

            let itemsHtml = `<div class="ord-items-cell">
                <div class="ord-items-invoice-head"><span>Item Produk (${items.length})</span></div>`
                + items.map(i => renderItemCard(i, urgent)).join('')
                + `</div>`;
                
            if (activeTab === 'shipped' && o.fulfillment_scan_log?.length) {
                const validScan = o.fulfillment_scan_log.filter(s => s.code && s.qty > 0);
                if (validScan.length) {
                    const scanCards = validScan.map(s => {
                        return `<div class="ord-item-card" style="border-color:#e9d5ff;background:#f3e8ff;margin-top:4px">
                            <div class="ord-item-qty" style="background:#ede9fe;color:#6d28d9">${s.qty}×</div>
                            <div class="ord-item-body">
                                <div class="ord-item-name" style="color:#4c1d95">${esc(s.code)}</div>
                                ${s.name ? `<div class="ord-item-variant">${esc(s.name)}</div>` : ''}
                            </div>
                        </div>`;
                    }).join('');
                    
                    itemsHtml += `<div style="margin-top:8px;margin-bottom:4px;font-size:0.65rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.04em">📦 Item Scan:</div>
                                  <div class="ord-items-cell">${scanCards}</div>`;
                }
            }

            let scanTd = '';
            if (activeTab === 'processed' && subTabProcessed !== 'packing') {
                const validScan = (o.fulfillment_scan_log || []).filter(s => s.code && s.qty > 0);
                if (validScan.length) {
                    const scanCards = validScan.map(s => `<div class="ord-item-card" style="border-color:#e9d5ff;background:#f3e8ff">
                        <div class="ord-item-qty" style="background:#ede9fe;color:#6d28d9">${s.qty}×</div>
                        <div class="ord-item-body">
                            <div class="ord-item-name" style="color:#4c1d95">${esc(s.code)}</div>
                            ${s.name ? `<div class="ord-item-variant">${esc(s.name)}</div>` : ''}
                        </div>
                    </div>`).join('');
                    scanTd = `<td><div class="ord-items-cell">${scanCards}</div></td>`;
                } else {
                    scanTd = `<td><div style="color:#cbd5e1;font-size:.72rem;font-style:italic">—</div></td>`;
                }
            }

            // Item Resolve column (Sedang Proses tab)
            let resolveTd = '';
            if (activeTab === 'processed' && subTabProcessed !== 'packing') {
                const resolveLines = (o.fulfillment_resolve_lines || []).filter(l => l.code);
                if (resolveLines.length) {
                    const resolveCards = resolveLines.map(l => `<div class="ord-item-card" style="border-color:#bfdbfe;background:#eff6ff">
                        <div class="ord-item-qty" style="background:#dbeafe;color:#1d4ed8">${l.qty_ordered}×</div>
                        <div class="ord-item-body">
                            <div class="ord-item-name" style="color:#1e3a5f">${esc(l.code)}</div>
                            ${l.name ? `<div class="ord-item-variant">${esc(l.name)}</div>` : ''}
                        </div>
                    </div>`).join('');
                    resolveTd = `<td><div class="ord-items-cell">${resolveCards}</div></td>`;
                } else {
                    resolveTd = `<td><div style="color:#94a3b8;font-size:.7rem;font-style:italic;padding:4px 0">Tidak ada item pengganti</div></td>`;
                }
            }

            const isFulfilled = fulfilledOrderIds.has(o.id);
            let printCount = o.print_count || 0;
            if (printCount === 0 && printedOrderIds.has(o.id)) printCount = 1;
            const isPrinted  = printCount > 0;
            const cetakTeks  = printCount > 1 ? `Sudah Cetak (ke-${printCount})` : 'Sudah Cetak';
            const isInPacking = fulfillmentStatusMap.has(o.id) && !fulfilledOrderIds.has(o.id);
            const rowClass    = isFulfilled ? 'row-fulfilled' : (isInPacking ? 'row-packing' : (urgent ? 'row-urgent' : ''));
            const carrier     = (o.shipping_carrier || '').toLowerCase();
            const isInstant   = carrier.includes('instant') || carrier.includes('same day') || carrier.includes('sameday');
            let instantBadge  = isInstant ? `<span style="font-size:.65rem;background:#fef08a;color:#854d0e;border-radius:99px;padding:2px 8px;font-weight:800;border:none;margin-right:4px;">⚡ INSTAN</span>` : '';
            const isAdvanceFulfillment = o.raw_json && (o.raw_json.shipped_by_advance_fulfilment === 'Y' || o.raw_json.shipped_by_advance_fulfillment === 'Y');
            if (isAdvanceFulfillment) {
                instantBadge += `<span style="font-size:.65rem;background:#fee2e2;color:#991b1b;border-radius:99px;padding:2px 8px;font-weight:800;border:none;margin-right:4px;" title="Shopee Advance Fulfillment">🚀 KILAT</span>`;
            }

            let perluKirimBadge = '';
            if (o.needs_shipping_arrangement) {
                perluKirimBadge = `<span style="font-size:.65rem;background:#fee2e2;color:#991b1b;border-radius:99px;padding:2px 8px;font-weight:800;border:none;">🚚 Perlu Kirim</span>`;
                if (isInstant) {
                    perluKirimBadge += `<span style="font-size:.65rem;color:#b91c1c;margin-left:4px;font-weight:600;">Pengiriman Kilat belum diatur</span>`;
                }
            }

            let logisticsBtn = '';
            const bkArg = (o.is_kilat && o.booking_sn) ? `, '${o.booking_sn}'` : '';

            // Logistics Buttons
            if (activeTab === 'processed_instant') {
                // Tab Instan: selalu sediakan tombol "Atur Pengiriman" manual (+ Cetak
                // Resi) supaya user tidak bingung. Order instan Shopee umumnya sudah
                // auto-arrange, tapi backend aman — error already_arranged diperlakukan
                // sebagai sukses (lihat MarketplaceLogisticsController::arrangeShipment).
                if (o.order_status === 'UNPAID') {
                    logisticsBtn = `<button class="btn-ship-outline" style="color:#16a34a!important;border-color:#bbf7d0!important;background:#f0fdf4!important;font-size:0.7rem;padding:0.35rem 0.5rem;width:100%;justify-content:center;box-shadow:none" onclick="event.stopPropagation(); openChatForOrder(${o.store_id}, '${o.channel_order_id}')">💬 Chat Pembeli</button>`;
                } else {
                    logisticsBtn = `
                    <div style="display:flex; flex-direction:column; gap:4px; width:100%;">
                        <button class="btn-ship-primary" style="font-size:0.7rem;padding:0.35rem 0.5rem;width:100%;justify-content:center;box-shadow:none" onclick="event.stopPropagation(); openArrangeShipment(${o.store_id}, '${o.channel_order_id}')">🚚 Atur Pengiriman</button>
                    </div>`;
                }
            } else if (activeTab === 'processed') {
                logisticsBtn = '';
            } else if (activeTab === 'ready') {
                if (o.order_status === 'UNPAID') {
                    // Belum bayar → tidak bisa diproses; tawarkan chat ke pembeli
                    logisticsBtn = `<button class="btn-ship-outline" style="color:#16a34a!important;border-color:#bbf7d0!important;background:#f0fdf4!important;font-size:0.7rem;padding:0.35rem 0.5rem;width:100%;justify-content:center;box-shadow:none" onclick="event.stopPropagation(); openChatForOrder(${o.store_id}, '${o.channel_order_id}')">💬 Chat Pembeli</button>`;
                } else {
                    const bkArg = o.is_kilat ? `, '${o.booking_sn || o.channel_order_id}'` : '';
                    logisticsBtn = `<button class="btn-ship-primary" style="font-size:0.7rem;padding:0.35rem 0.5rem;width:100%;justify-content:center;box-shadow:none" onclick="event.stopPropagation(); openArrangeShipment(${o.store_id}, '${o.channel_order_id}'${bkArg})">🚚 Atur Pengiriman</button>`;
                }
            } else {
                if (o.order_status === 'UNPAID') {
                    logisticsBtn = `<button class="btn-ship-outline" style="color:#16a34a!important;border-color:#bbf7d0!important;background:#f0fdf4!important;font-size:0.7rem;padding:0.35rem 0.5rem;width:100%;justify-content:center;box-shadow:none" onclick="event.stopPropagation(); openChatForOrder(${o.store_id}, '${o.channel_order_id}')">💬 Chat Pembeli</button>`;
                } else if (o.needs_shipping_arrangement || (o.order_status === 'READY_TO_SHIP' && !o.shipping_awb_no)) {
                    logisticsBtn = `<button class="btn-ship-primary" style="font-size:0.7rem;padding:0.35rem 0.5rem;width:100%;justify-content:center;box-shadow:none" onclick="event.stopPropagation(); openArrangeShipment(${o.store_id}, '${o.channel_order_id}')">🚚 Atur Pengiriman</button>`;
                } else if (o.order_status === 'READY_TO_SHIP' || o.order_status === 'PROCESSED' || o.order_status === 'SHIPPED') {
                    logisticsBtn = '';
                }
            }

            let printHtml = '';
            const printResiBtn = `<button class="ord-action-btn print" onclick="event.stopPropagation(); printDocument(${o.store_id}, '${o.channel_order_id}'${bkArg})">🖨 Cetak</button>`;
            const printGreetingBtn = `<button class="ord-action-btn greeting" onclick="event.stopPropagation(); printSingleGreeting(${o.store_id}, '${o.channel_order_id}')">💌 Kartu</button>`;
            if (activeTab === 'processed_instant' && o.order_status !== 'UNPAID') {
                printHtml = printResiBtn;
            } else if (activeTab === 'processed') {
                printHtml = `<div class="ord-action-stack">${printResiBtn}${printGreetingBtn}</div>`;
            } else if (['shipped', 'completed'].includes(activeTab) && o.order_status !== 'UNPAID') {
                printHtml = printResiBtn;
            } else if (['READY_TO_SHIP', 'PROCESSED', 'SHIPPED'].includes(o.order_status)) {
                printHtml = printResiBtn;
            }
            if (isPrinted && ['processed', 'shipped'].includes(activeTab)) {
                printHtml += `<div class="ord-printed-note">✓ ${esc(cetakTeks)}</div>`;
            }

            const printedBadge = (isPrinted && !isFulfilled && !['ready', 'processed', 'shipped'].includes(activeTab))
                ? `<span style="font-size:.65rem;background:#e0f2fe;color:#0369a1;border-radius:4px;padding:1px 5px;font-weight:600">🖨 ${cetakTeks}</span>`
                : '';

            let slaBadge = '';
            if (o.ship_by_date && ACTIVE_ORDER_STATUSES.includes(o.order_status) && !['shipped','completed'].includes(activeTab)) {
                const hoursLeft = (new Date(o.ship_by_date) - new Date()) / 3600000;
                if (hoursLeft < 12 && hoursLeft > 0) {
                    slaBadge = `<span style="font-size:0.65rem; background:#fef3c7; color:#d97706; padding:1px 6px; border-radius:4px; font-weight:700; border:1px solid #fde68a; margin-left:4px;" title="SLA Pengiriman < 12 Jam">⚠️ SLA ${Math.floor(hoursLeft)}j</span>`;
                } else if (hoursLeft <= 0) {
                    slaBadge = `<span style="font-size:0.65rem; background:#fee2e2; color:#dc2626; padding:1px 6px; border-radius:4px; font-weight:700; border:1px solid #fecaca; margin-left:4px;" title="Terlambat (SLA Terlewati)">🚨 TELAT</span>`;
                }
            }

            let orderIdHtml = orderCopyHtml(o.channel_order_id || '—');
            if (o.is_kilat) orderIdHtml = '<span title="Pesanan Kilat (Booking Shopee)" style="font-size:.6rem;font-weight:800;color:#a16207;background:#fefce8;border:1px solid #fde68a;border-radius:4px;padding:1px 5px;margin-right:5px;white-space:nowrap;">⚡ KILAT</span>' + orderIdHtml;
            if (isAdvanceFulfillment) {
                orderIdHtml = `No. Reservasi ${orderIdHtml}`;
            }
            orderIdHtml += slaBadge;

            const rowClick = activeTab === 'issues'
                ? `onclick="window.location='/marketplace/issues'" style="cursor:pointer"`
                : `onclick="window.showOrderDetail(${o.id})" style="cursor:pointer" title="Klik untuk melihat detail"`;

            const shippingStatusHtml = logBadge
                || (shippingStatusFallback(o.order_status)
                    ? `<span class="ord-shipping-status fallback">📦 ${esc(shippingStatusFallback(o.order_status))}</span>`
                    : '<span class="ord-payment-empty">—</span>');
            const fBadge = urgent && !['ready', 'processed'].includes(activeTab) ? fulfillmentBadge(o) : '';

            // Badge masalah di tab Sudah Proses
            let issueBadge = '';
            if (activeTab === 'processed' && o.fulfillment_lines?.length) {
                const iss = fulfilledOrderIssues(o.fulfillment_lines);
                if (iss.hasAny) {
                    const parts = [];
                    if (iss.hasShort) parts.push('Kurang');
                    if (iss.hasSub)   parts.push('Diganti');
                    if (iss.hasSplit) parts.push('Split');
                    issueBadge = `<span style="font-size:.63rem;font-weight:700;
                            background:#fef3c7;color:#92400e;border:1px solid #fde68a;
                            border-radius:4px;padding:1px 6px">⚠ ${parts.join(' · ')}</span>`;
                }
            }
            
            let shippingInfoHtml = '';
            if (o.shipping_awb_no) {
                shippingInfoHtml = `<div class="ord-shipping-awb"><span class="ord-shipping-label">No. Resi</span><span class="ord-shipping-awb-value">${printedDocOrderSns.has(o.channel_order_id) ? '🖨️ ' : ''}${esc(o.shipping_awb_no)}</span></div>`;
            } else if (o.shipping_carrier) {
                shippingInfoHtml = `<div class="ord-shipping-carrier">${esc(o.shipping_carrier)}</div>`;
            }
            if (shippingInfoHtml) shippingInfoHtml = `<div class="ord-shipping-stack">${shippingInfoHtml}</div>`;
            const shippingActionHtml = logisticsBtn
                ? `<div class="ord-shipping-action">${logisticsBtn}</div>`
                : '';

            const trackHtml = ['shipped', 'completed'].includes(activeTab) && o.order_status !== 'UNPAID'
                ? `<button class="ord-action-btn track" onclick="event.stopPropagation(); trackOrder(${o.store_id}, '${o.channel_order_id}', event)">🔍 Lacak</button>`
                : '<span class="ord-payment-empty">—</span>';
            const trackPrintHtml = (trackHtml !== '<span class="ord-payment-empty">—</span>' || printHtml)
                ? `<div class="ord-track-print-stack">${trackHtml}${printHtml || ''}</div>`
                : '<span class="ord-payment-empty">—</span>';

            const orderIdContent = `
                <div class="ord-id">${orderIdHtml}</div>
                ${shippingInfoHtml}
                <div class="ord-date" style="margin-top:4px">${dateHtml}</div>
                ${orderContextHtml(o)}
                <div style="display:flex; flex-wrap:wrap; gap:4px; margin-top:8px;">
                    ${perluKirimBadge}
                    ${instantBadge}
                    ${printedBadge}
                    ${fBadge}
                    ${issueBadge}
                </div>
                ${shippingActionHtml}
            `;

            const paymentCell = `<td class="ord-payment-cell">
                <div class="ord-payment-summary">
                    ${buyerPaymentMethodHtml(o)}
                    ${buyerPaidHtml(o)}
                    <div class="ord-payment-income">
                        <span class="ord-payment-income-label">Penghasilan</span>
                        ${escrowHtml(o)}
                    </div>
                </div>
            </td>`;
            const voucherCell = `<td class="ord-voucher-cell">${voucherSummaryHtml(o) || '<span class="ord-payment-empty">—</span>'}</td>`;
            
            const firstColHtml = activeTab === 'processed' 
                ? `<div style="display:flex; gap:0.6rem; align-items:flex-start;">
                    <input type="checkbox" class="form-check-input chk-print-order" data-order-sn="${esc(o.channel_order_id)}" style="width:1.15rem;height:1.15rem;cursor:pointer;accent-color:#0284c7;margin-top:2px;flex-shrink:0;" ${window.selectedPrintOrders.has(o.channel_order_id) ? 'checked' : ''} onclick="if(this.checked) window.selectedPrintOrders.add('${esc(o.channel_order_id)}'); else window.selectedPrintOrders.delete('${esc(o.channel_order_id)}');">
                    <div style="min-width:0;flex:1;">
                        ${orderIdContent}
                    </div>
                   </div>`
                : orderIdContent;

            return `<tr class="${rowClass}${isPrinted && !isFulfilled ? ' row-printed' : ''}" id="ord-row-${o.id}" ${rowClick}>
                <td>
                    ${firstColHtml}
                </td>
                <td>${itemsHtml}</td>
                ${resolveTd}
                ${scanTd}
                ${paymentCell}
                ${voucherCell}
                <td class="ord-ams-cell">${amsHtml(o)}</td>
                <td class="ord-track-print-cell"><div class="ord-track-print-stack">${shippingStatusHtml}${trackPrintHtml}</div></td>
            </tr>`;
        }).join('');

        const hasResolveCol = activeTab === 'processed' && subTabProcessed !== 'packing';
        const hasScanCol    = activeTab === 'processed' && subTabProcessed !== 'packing';
        // col widths: order (+ resi) | items | resolve? | scan? | pembayaran (+ penghasilan) | voucher | AMS | lacak+cetak
        const colItems  = hasResolveCol ? '14%' : '23%';
        const colOrder = hasResolveCol ? '20%' : '24%';
        const colPaid = hasResolveCol ? '18%' : '20%';
        const colVoucher = hasResolveCol ? '13%' : '16%';
        const colAms = '6%';
        const colTrackPrint = hasResolveCol ? '13%' : '11%';
        
        const firstHeaderHtml = activeTab === 'processed'
            ? `<div style="display:flex; align-items:center;">
                <input type="checkbox" id="chkSelectAllOrders" class="form-check-input" style="width:1.1rem;height:1.1rem;cursor:pointer;accent-color:#0284c7;margin-right:8px;" onclick="const isChecked = this.checked; document.querySelectorAll('.chk-print-order').forEach(el => { el.checked = isChecked; const sn = el.getAttribute('data-order-sn'); if(isChecked) window.selectedPrintOrders.add(sn); else window.selectedPrintOrders.delete(sn); })">
                <span>Order &amp; Pengiriman</span>
               </div>`
            : `Order &amp; Pengiriman`;

        body.innerHTML = `
        <div class="gf-table-scroll">
        <table class="ord-table">
            <colgroup>
                <col style="width:${colOrder}">
                <col style="width:${colItems}">
                ${hasResolveCol ? '<col style="width:9%">' : ''}
                ${hasScanCol    ? '<col style="width:9%">' : ''}
                <col style="width:${colPaid}">
                <col style="width:${colVoucher}">
                <col style="width:${colAms}">
                <col style="width:${colTrackPrint}">
            </colgroup>
            <thead><tr>
                <th>${firstHeaderHtml}</th>
                <th>Item Produk</th>
                ${hasResolveCol ? '<th>✅ Item Pengganti</th>' : ''}
                ${hasScanCol    ? '<th>📦 Item Scan</th>'    : ''}
                <th>Pembayaran &amp; Penghasilan</th>
                <th>Voucher &amp; Diskon</th>
                <th>AMS</th>
                <th>Status / Lacak / Cetak</th>
            </tr></thead>
            <tbody>${tableRows}</tbody>
        </table></div>
        <div class="gf-table-foot">
            <span class="gf-table-foot-hint">${rows.length} order ditampilkan</span>
        </div>`;
    }

    // ── Quick Sync ────────────────────────────────────────────────────────
    // ── Quick Sync — Range Picker ────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.qs-range-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.qs-range-btn').forEach(b => {
                    b.style.background = '#fff'; b.style.color = '#475569';
                    b.style.borderColor = '#e2e8f0';
                });
                this.style.background = '#0f172a'; this.style.color = '#fff';
                this.style.borderColor = '#0f172a';
                document.getElementById('qsSyncRangeDays').value = this.dataset.days;

                // Tampilkan peringatan durasi untuk rentang panjang (>=30 hari)
                const hint = document.getElementById('qsRangeHint');
                if (hint) hint.style.display = parseInt(this.dataset.days) >= 30 ? '' : 'none';
            });
        });
    });

    // ── Quick Sync — Background (queue) untuk rentang panjang ─────────────
    window.runBackgroundSync = async function () {
        const days = parseInt(document.getElementById('qsSyncRangeDays').value) || 3;
        const bgBtn = document.getElementById('qsBgBtn');
        const runBtn = document.getElementById('qsRunBtn');
        bgBtn.disabled = true; runBtn.disabled = true;
        bgBtn.innerHTML = '⏳ Mengirim ke antrean…';
        document.getElementById('qsAlert').className = 'alert d-none';

        try {
            const stores = await api('/api/marketplace/stores');
            const active = stores.filter(s => s.is_active && s.status === 'active' && s.connection_status === 'CONNECTED');

            if (!active.length) {
                document.getElementById('qsAlert').className = 'alert alert-warning';
                document.getElementById('qsAlert').textContent = 'Tidak ada toko aktif. Hubungkan minimal satu toko Shopee dulu.';
                bgBtn.disabled = false; runBtn.disabled = false;
                bgBtn.innerHTML = '🌙 Sync di Latar Belakang';
                return;
            }

            let queued = 0;
            for (const s of active) {
                try {
                    await api('/api/marketplace/stores/' + s.id + '/sync-orders-background', {
                        method: 'POST',
                        body: JSON.stringify({ days }),
                    });
                    queued++;
                } catch (e) { /* lanjut toko berikutnya */ }
            }

            document.getElementById('qsConfigPanel').style.display = 'none';
            document.getElementById('qsProgressPanel').style.display = 'none';
            document.getElementById('qsResultPanel').style.display = '';
            document.getElementById('qsResultIcon').textContent = '🌙';
            document.getElementById('qsResultTitle').textContent = 'Dikirim ke Latar Belakang';
            document.getElementById('qsResultSub').textContent =
                `Sync ${days} hari untuk ${queued} toko sedang diproses di latar belakang. Data masuk bertahap — tutup jendela ini dan segarkan daftar beberapa saat lagi.`;
            runBtn.innerHTML = '🔄 Sync Lagi';
            runBtn.disabled = false;
            runBtn.onclick = openQuickSync;
        } catch (e) {
            document.getElementById('qsAlert').className = 'alert alert-danger';
            document.getElementById('qsAlert').textContent = 'Gagal mengirim ke latar belakang. Coba lagi.';
        } finally {
            bgBtn.disabled = false;
            bgBtn.innerHTML = '🌙 Sync di Latar Belakang';
        }
    };

    // ── Quick Sync — Open ────────────────────────────────────────────────
    window.openQuickSync = function () {
        // Reset ke config panel
        document.getElementById('qsConfigPanel').style.display = '';
        document.getElementById('qsProgressPanel').style.display = 'none';
        document.getElementById('qsResultPanel').style.display = 'none';
        document.getElementById('qsAlert').className = 'alert d-none';
        // Reset pilihan rentang ke default 3 hari + sembunyikan hint
        document.querySelectorAll('.qs-range-btn').forEach(b => {
            const isDefault = b.dataset.days === '3';
            b.style.background = isDefault ? '#0f172a' : '#fff';
            b.style.color = isDefault ? '#fff' : '#475569';
            b.style.borderColor = isDefault ? '#0f172a' : '#e2e8f0';
        });
        document.getElementById('qsSyncRangeDays').value = '3';
        const hint = document.getElementById('qsRangeHint');
        if (hint) hint.style.display = 'none';
        const bgBtn = document.getElementById('qsBgBtn');
        if (bgBtn) { bgBtn.disabled = false; bgBtn.innerHTML = '🌙 Sync di Latar Belakang'; }
        const runBtn = document.getElementById('qsRunBtn');
        const cancelBtn = document.getElementById('qsCancelBtn');
        runBtn.disabled = false;
        runBtn.innerHTML = '🔄 Sync Sekarang';
        runBtn.onclick = runQuickSync;
        cancelBtn.textContent = 'Tutup';
        cancelBtn.setAttribute('data-bs-dismiss', 'modal');
        new bootstrap.Modal(document.getElementById('quickSyncModal')).show();
    };

    // ── Quick Sync — Store Card Helpers ──────────────────────────────────
    function qsAddStoreCard(store) {
        const list = document.getElementById('qsStoreList');
        const card = document.createElement('div');
        card.id = 'qs-store-' + store.id;
        card.style.cssText = 'display:flex;align-items:center;gap:.65rem;padding:.55rem .75rem;background:#f8fafc;border-radius:12px;border:1.5px solid #e2e8f0;transition:all .3s';
        card.innerHTML = `
            <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#f1f5f9,#e2e8f0);display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0">🏪</div>
            <div style="flex:1;min-width:0">
                <div style="font-size:.76rem;font-weight:700;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${store.name}</div>
                <div id="qs-store-status-${store.id}" style="font-size:.65rem;color:#94a3b8">Menunggu…</div>
            </div>
            <div id="qs-store-icon-${store.id}" style="font-size:1rem;flex-shrink:0">⏳</div>
        `;
        list.appendChild(card);
        return card;
    }

    function qsUpdateStoreCard(storeId, statusText, icon, bgColor, borderColor) {
        const statusEl = document.getElementById('qs-store-status-' + storeId);
        const iconEl   = document.getElementById('qs-store-icon-'   + storeId);
        const card     = document.getElementById('qs-store-' + storeId);
        if (statusEl) statusEl.textContent = statusText;
        if (iconEl)   iconEl.textContent   = icon;
        if (card && bgColor) {
            card.style.background   = bgColor;
            card.style.borderColor  = borderColor || bgColor;
        }
    }

    let qsTotalNew = 0, qsTotalUpdated = 0, qsTotalIssues = 0;
    function qsAddStat(newN, updN, issN) {
        qsTotalNew     += (newN  || 0);
        qsTotalUpdated += (updN  || 0);
        qsTotalIssues  += (issN  || 0);
        document.getElementById('qsStatNew').textContent     = qsTotalNew;
        document.getElementById('qsStatUpdated').textContent = qsTotalUpdated;
        document.getElementById('qsStatIssues').textContent  = qsTotalIssues;
    }

    function qsSetProgress(pct, text) {
        document.getElementById('qsProgressBar').style.width = pct + '%';
        document.getElementById('qsProgressPct').textContent  = pct + '%';
        document.getElementById('qsProgressText').textContent = text;
    }

    // ── Quick Sync — Run ─────────────────────────────────────────────────
    window.runQuickSync = async function () {
        const days    = parseInt(document.getElementById('qsSyncRangeDays').value) || 3;
        const now     = Math.floor(Date.now() / 1000);
        const from    = now - days * 86400;
        const dryRun  = document.getElementById('qsSyncDryRun').checked;
        const doOrders   = document.getElementById('qsSyncOrders').checked;
        const doBookings = document.getElementById('qsSyncBookings').checked;

        if (!doOrders && !doBookings) {
            document.getElementById('qsAlert').className = 'alert alert-warning';
            document.getElementById('qsAlert').textContent = 'Pilih minimal satu tipe sync.';
            return;
        }

        // Rentang sangat panjang (>= 90 hari) rawan timeout bila dijalankan langsung —
        // otomatis dialihkan ke antrean latar belakang (tetap masuk bertahap ke DB).
        if (days >= 90 && !dryRun && doOrders) {
            return runBackgroundSync();
        }

        // Switch panel ke progress
        document.getElementById('qsConfigPanel').style.display = 'none';
        document.getElementById('qsProgressPanel').style.display = '';
        document.getElementById('qsResultPanel').style.display = 'none';
        document.getElementById('qsAlert').className = 'alert d-none';
        document.getElementById('qsStoreList').innerHTML = '';
        qsTotalNew = 0; qsTotalUpdated = 0; qsTotalIssues = 0;
        document.getElementById('qsStatNew').textContent = '0';
        document.getElementById('qsStatUpdated').textContent = '0';
        document.getElementById('qsStatIssues').textContent = '0';

        const runBtn = document.getElementById('qsRunBtn');
        runBtn.disabled = true;
        runBtn.innerHTML = '⏳ Sedang Sync…';

        try {
            qsSetProgress(5, 'Mengambil daftar toko…');
            const stores = await api('/api/marketplace/stores');
            const active = stores.filter(s => s.is_active && s.status === 'active' && s.connection_status === 'CONNECTED');

            if (!active.length) {
                document.getElementById('qsResultIcon').textContent = '⚠️';
                document.getElementById('qsResultTitle').textContent = 'Tidak Ada Toko Aktif';
                document.getElementById('qsResultSub').textContent = 'Hubungkan minimal satu toko Shopee terlebih dahulu.';
                document.getElementById('qsProgressPanel').style.display = 'none';
                document.getElementById('qsResultPanel').style.display = '';
                runBtn.innerHTML = '🔄 Coba Lagi';
                runBtn.disabled = false;
                runBtn.onclick = openQuickSync;
                return;
            }

            // Buat semua store cards
            active.forEach(s => qsAddStoreCard(s));

            // Hitung total langkah
            // Endpoint sync-orders sudah menjalankan sync booking dalam siklus
            // yang sama. Hindari memanggil endpoint booking dua kali ketika
            // kedua checkbox dipilih.
            const bookingsOnly = doBookings && !doOrders;
            const totalSteps = active.length * (doOrders ? 1 : 0) + active.length * (bookingsOnly ? 1 : 0);
            let doneSteps = 0;
            let hasAuthError = false;

            // ── Sync Orders ──────────────────────────────────────────────
            if (doOrders) {
                for (let i = 0; i < active.length; i++) {
                    if (hasAuthError) break;
                    const s = active[i];
                    const pct = Math.round(5 + ((doneSteps / totalSteps) * 88));
                    qsSetProgress(pct, `Sync pesanan reguler: ${s.name}…`);
                    qsUpdateStoreCard(s.id, 'Sync pesanan reguler…', '🔄', '', '');
                    try {
                        const d = await api('/api/marketplace/stores/' + s.id + '/sync-orders', {
                            method: 'POST',
                            body: JSON.stringify({
                                time_from: from,
                                time_to: now,
                                page_size: 50,
                                dry_run: dryRun ? 1 : 0,
                                sync_bookings: doBookings ? 1 : 0,
                            }),
                        });
                        const newN = d.new || d.synced || 0;
                        const updN = d.updated || 0;
                        const issN = (d.sku_empty||0) + (d.mapping_not_found||0) + (d.missing_hpp||0);
                        qsAddStat(newN, updN, issN);
                        const statusParts = [];
                        if (newN)  statusParts.push(`${newN} baru`);
                        if (updN)  statusParts.push(`${updN} update`);
                        if (issN)  statusParts.push(`${issN} perlu cek`);
                        const statusText = doBookings
                            ? '✅ Order selesai' + (statusParts.length ? ` (${statusParts.join(', ')})` : '')
                            : '✅ Selesai' + (statusParts.length ? ` · ${statusParts.join(', ')}` : '');
                        qsUpdateStoreCard(s.id, statusText, doBookings ? '🔄' : '✅', doBookings ? '' : '#f0fdf4', doBookings ? '' : '#bbf7d0');
                    } catch (e) {
                        if (e.data && e.data.action && e.data.action.type === 'redirect') {
                            hasAuthError = true;
                            qsUpdateStoreCard(s.id, 'Perlu login ulang', '🔐', '#fff7ed', '#fed7aa');
                            const qsModalEl = document.getElementById('quickSyncModal');
                            if (qsModalEl) { const m = bootstrap.Modal.getInstance(qsModalEl); if (m) m.hide(); }
                            if (window.Swal) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Koneksi Bermasalah',
                                    html: (e.data.message || '') + '<br><br>Silakan login ulang agar pesanan dapat disinkronkan.',
                                    showCancelButton: true,
                                    confirmButtonText: e.data.action?.label || 'Login Ulang',
                                    cancelButtonText: 'Nanti',
                                    confirmButtonColor: '#1e293b'
                                }).then(r => { if (r.isConfirmed) window.location.href = e.data.action.url; });
                            }
                            break;
                        }
                        qsUpdateStoreCard(s.id, 'Gagal: ' + (e.message || 'Error'), '❌', '#fff1f2', '#fecdd3');
                    }
                    doneSteps++;
                }
            }

            // ── Sync Bookings (Kilat) ─────────────────────────────────────
            if (bookingsOnly && !hasAuthError) {
                for (let i = 0; i < active.length; i++) {
                    const s = active[i];
                    const pct = Math.round(5 + ((doneSteps / totalSteps) * 88));
                    qsSetProgress(pct, `Sync pesanan kilat: ${s.name}…`);
                    qsUpdateStoreCard(s.id, 'Sync pesanan kilat…', '⚡', '', '');
                    try {
                        await api('/api/marketplace/stores/' + s.id + '/sync-bookings', {
                            method: 'POST',
                            body: JSON.stringify({ time_from: from, time_to: now }),
                        });
                        qsUpdateStoreCard(s.id, '✅ Kilat selesai', '✅', '#f0fdf4', '#bbf7d0');
                    } catch (e) {
                        // sync-bookings mungkin tidak return error kritis — lanjut saja
                        qsUpdateStoreCard(s.id, '⚠ Kilat partial', '⚠️', '#fff7ed', '#fde68a');
                    }
                    doneSteps++;
                }
            }

            // ── Selesai ───────────────────────────────────────────────────
            qsSetProgress(100, 'Selesai!');
            await new Promise(r => setTimeout(r, 500));

            document.getElementById('qsProgressPanel').style.display = 'none';
            document.getElementById('qsResultPanel').style.display = '';

            if (hasAuthError) {
                document.getElementById('qsResultIcon').textContent = '⚠️';
                document.getElementById('qsResultTitle').textContent = 'Sync Sebagian';
                document.getElementById('qsResultSub').textContent = 'Beberapa toko memerlukan login ulang.';
            } else {
                document.getElementById('qsResultIcon').textContent = dryRun ? '🔍' : '✅';
                document.getElementById('qsResultTitle').textContent = dryRun ? 'Simulasi Selesai' : 'Sync Berhasil!';
                const parts = [];
                if (qsTotalNew)     parts.push(`<strong>${qsTotalNew}</strong> order baru`);
                if (qsTotalUpdated) parts.push(`<strong>${qsTotalUpdated}</strong> diperbarui`);
                if (qsTotalIssues)  parts.push(`<strong>${qsTotalIssues}</strong> perlu dicek`);
                document.getElementById('qsResultSub').innerHTML = parts.length
                    ? parts.join(' · ')
                    : 'Semua pesanan sudah terkini.';
            }

            runBtn.innerHTML = '🔄 Sync Lagi';
            runBtn.disabled  = false;
            runBtn.onclick   = openQuickSync;

            document.getElementById('qsCancelBtn').textContent = 'Tutup';

            if (!dryRun) loadOrders();

        } catch (e) {
            document.getElementById('qsProgressPanel').style.display = 'none';
            document.getElementById('qsAlert').className = 'alert alert-danger';
            document.getElementById('qsAlert').textContent = 'Gagal: ' + (e.message || 'Terjadi kesalahan.');
            runBtn.innerHTML = '🔄 Sync Sekarang';
            runBtn.disabled  = false;
            runBtn.onclick   = runQuickSync;
        }
    };

    window.runSyncBookings = async function () {
        const btn = document.getElementById('btnSyncBookings');
        btn.disabled = true;
        btn.innerHTML = '⏳ Syncing...';

        try {
            const stores = await api('/api/marketplace/stores');
            let storesToSync = stores.filter(s => s.is_active && s.status === 'active' && s.connection_status === 'CONNECTED');
            
            if (activeStore) {
                storesToSync = storesToSync.filter(s => s.id == activeStore);
            }

            if (!storesToSync.length) {
                Swal.fire('Info', 'Tidak ada toko yang terhubung untuk di-sync resinya.', 'info');
                btn.disabled = false;
                btn.innerHTML = '🚚 Sync Resi';
                return;
            }

            let msg = '';
            for (const s of storesToSync) {
                try {
                    const res = await api(`/api/marketplace/stores/${s.id}/sync-bookings`, { method: 'POST' });
                    msg += `Toko ${s.name}: ${res.message}<br>`;
                } catch (e) {
                    msg += `Toko ${s.name}: Gagal (${e.message})<br>`;
                }
            }

            Swal.fire({
                title: 'Hasil Sync Resi (15 Hari)',
                html: msg,
                icon: 'success'
            });

            loadOrders();
        } catch (e) {
            Swal.fire('Error', 'Gagal memuat toko: ' + e.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '🚚 Sync Resi';
        }
    };

    function showQsAlert(type, msg) {
        const el = $('qsAlert');
        el.className = `alert alert-${type} mb-3`;
        el.textContent = msg;
    }

    window.loadOrders = loadOrders;
    const shippingParamCache = new Map();
    
    // Jangan prefetch shipping parameter untuk semua order secara otomatis.
    // Endpoint ini memanggil API Shopee per order dan mudah memicu rate limit;
    // parameter diambil saat operator membuka dialog Atur Pengiriman saja.

    // ── Order Details ────────────────────────────────────────────────────
    window.showOrderDetail = function(orderId) {
        window.location.href = `/marketplace/orders/${orderId}`;
    };

    // ── Logistics ────────────────────────────────────────────────────────
    // bookingSn diisi hanya untuk Pesanan Kilat murni (booking belum MATCHED ke
    // order lokal) — pengiriman diatur lewat endpoint booking, bukan order.
    window.openArrangeShipment = async function (storeId, orderSn, bookingSn = null) {
        // Beberapa tombol (tab Instan/Bermasalah/daftar lain) tidak meneruskan
        // bookingSn — derive dari data baris supaya Pesanan Kilat SELALU lewat
        // endpoint booking (endpoint order akan error "order_sn is not exist").
        if (!bookingSn && typeof orders !== 'undefined') {
            const row = orders.find(x => x.channel_order_id === orderSn || x.booking_sn === orderSn);
            if (row && row.is_kilat) bookingSn = row.booking_sn || orderSn;
        }
        $('asLoading').style.display = 'block';
        $('asContent').style.display = 'none';
        $('asStoreId').value = storeId;
        $('asOrderSn').value = orderSn;
        window._asBookingSn = bookingSn || null;
        $('asOptions').innerHTML = '';
        $('asSubmitBtn').disabled = true;

        const modal = new bootstrap.Modal($('arrangeShipmentModal'));
        modal.show();

        try {
            const cacheKey = bookingSn || orderSn;
            let res = shippingParamCache.get(cacheKey);
            if (!res) {
                res = bookingSn
                    ? await api(`/api/marketplace/stores/${storeId}/bookings/${bookingSn}/shipping-parameter`)
                    : await api(`/api/marketplace/stores/${storeId}/orders/${orderSn}/shipping-parameter`);
                shippingParamCache.set(cacheKey, res);
            }
            
            $('asLoading').style.display = 'none';
            $('asContent').style.display = 'block';
            
            // Shopee format is usually ['response']['info_needed']['dropoff'] etc
            // We will simplify and assume it returns options we can show, 
            // but if there's no complex info needed, we just provide a default proceed.
            const responseData = res.response || res;
            const infoNeeded = responseData.info_needed || {};
            
            // Permintaan USER: Untuk pesanan Reguler dan Kilat (Bukan Instan), tampilkan HANYA Drop-off.
            const o = typeof orders !== 'undefined' ? orders.find(x => x.channel_order_id === orderSn) : null;
            const isInst = o ? (o.shipping_carrier || '').toLowerCase().match(/instant|same day|sameday/) : false;
            
            if (!isInst && infoNeeded.dropoff) {
                // Hapus opsi pickup agar kurir reguler/kilat hanya bisa di-dropoff
                delete infoNeeded.pickup;
            }
            
            // Tangani dua kemungkinan bentuk respons Shopee:
            // (a) data di bawah info_needed.pickup/dropoff, atau (b) di top-level pickup/dropoff.
            const pickupData  = (infoNeeded.pickup && infoNeeded.pickup.address_list) ? infoNeeded.pickup : (responseData.pickup || null);
            const dropoffData = (infoNeeded.dropoff && infoNeeded.dropoff.branch_list) ? infoNeeded.dropoff : (responseData.dropoff || null);
            
            let html = '';
            
            if (infoNeeded.dropoff) {
                // Determine if pickup should be default (if it exists and has addresses)
                const shouldDefaultToPickup = false; // Selalu default ke dropoff jika tersedia (karena instruksi user: drop off saja)
                
                html += `<div class="form-check mb-2">
                    <input class="form-check-input as-method-radio" type="radio" name="asMethod" id="asDropoff" value="dropoff" ${!shouldDefaultToPickup ? 'checked' : ''}>
                    <label class="form-check-label" for="asDropoff"><strong>Drop-off</strong> (Antar ke Cabang)</label>
                </div>`;

                if (dropoffData && dropoffData.branch_list && dropoffData.branch_list.length > 0) {
                    html += `<div class="dropoff-options ps-4 mt-2" id="dropoffOptionsWrapper" style="${!shouldDefaultToPickup ? 'display:block;' : 'display:none;'}">
                        <div class="mb-2">
                            <label class="form-label" style="font-size:0.8rem">Pilih Cabang / Titik Drop-off</label>
                            <select class="form-select form-select-sm" id="asDropoffBranch">`;
                    dropoffData.branch_list.forEach((branch) => {
                        let label = [branch.address, branch.city, branch.state, branch.zipcode].filter(Boolean).join(', ');
                        html += `<option value="${branch.branch_id}">${label || ('Titik #' + branch.branch_id)}</option>`;
                    });
                    html += `       </select>
                        </div>
                    </div>`;
                }
            }
            
            if (infoNeeded.pickup) {
                // If dropoff doesn't exist, or we decided pickup is default
                const shouldDefaultToPickup = !infoNeeded.dropoff || (pickupData && pickupData.address_list && pickupData.address_list.length > 0);
                
                html += `<div class="form-check mb-2">
                    <input class="form-check-input as-method-radio" type="radio" name="asMethod" id="asPickup" value="pickup" ${shouldDefaultToPickup ? 'checked' : ''}>
                    <label class="form-check-label" for="asPickup"><strong>Pickup</strong> (Kurir Jemput)</label>
                </div>`;
                
                if (pickupData && pickupData.address_list && pickupData.address_list.length > 0) {
                    html += `<div class="pickup-options ps-4 mt-2" id="pickupOptionsWrapper" style="${shouldDefaultToPickup ? 'display:block;' : 'display:none;'}">
                        <div class="mb-2">
                            <label class="form-label" style="font-size:0.8rem">Alamat Pickup</label>
                            <select class="form-select form-select-sm" id="asPickupAddress">`;
                    pickupData.address_list.forEach((addr, idx) => {
                        let timeslots = JSON.stringify(addr.time_slot_list || []);
                        let isDefault = (addr.address_flag && addr.address_flag.includes('default_address')) ? 'selected' : '';
                        html += `<option value="${addr.address_id}" data-timeslots='${timeslots.replace(/'/g, "&#39;")}' ${isDefault}>${addr.address || addr.address_id}</option>`;
                    });
                    html += `       </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label" style="font-size:0.8rem">Waktu Pickup</label>
                            <select class="form-select form-select-sm" id="asPickupTime"></select>
                        </div>
                    </div>`;
                }
            }
            
            if (html === '') {
                html = `<div class="alert alert-warning py-1" style="font-size:0.75rem">Informasi metode pengiriman tidak spesifik, lanjutkan untuk mencoba arrange shipment otomatis.</div>
                <input type="hidden" name="asMethod" value="auto">`;
            }
            
            $('asOptions').innerHTML = html;
            $('asSubmitBtn').disabled = false;
            
            // Bind events for dynamically added options
            const methodRadios = document.querySelectorAll('.as-method-radio, input[name="asMethod"]');
            const pickupWrapper = document.getElementById('pickupOptionsWrapper');
            const dropoffWrapper = document.getElementById('dropoffOptionsWrapper');
            const addressSelect = document.getElementById('asPickupAddress');
            const timeSelect = document.getElementById('asPickupTime');
            
            methodRadios.forEach(r => {
                r.addEventListener('change', (e) => {
                    if (pickupWrapper) {
                        pickupWrapper.style.display = (e.target.value === 'pickup') ? 'block' : 'none';
                    }
                    if (dropoffWrapper) {
                        dropoffWrapper.style.display = (e.target.value === 'dropoff') ? 'block' : 'none';
                    }
                });
            });
            
            if (addressSelect && timeSelect) {
                const updateTimes = () => {
                    const selected = addressSelect.options[addressSelect.selectedIndex];
                    if (!selected) return;
                    let timeslots = [];
                    try { timeslots = JSON.parse(selected.getAttribute('data-timeslots') || '[]'); } catch(e){}
                    timeSelect.innerHTML = '';
                    if (timeslots.length > 0) {
                        timeslots.forEach(ts => {
                            let text = ts.time_text || ts.pickup_time_id || 'Pilih Waktu';
                            let dateStr = ts.date ? new Date(ts.date * 1000).toLocaleDateString('id-ID') + ' ' : '';
                            // Auto select the recommended timeslot
                            let isRecommended = (ts.flags && ts.flags.includes('recommended')) ? 'selected' : '';
                            timeSelect.innerHTML += `<option value="${ts.pickup_time_id}" ${isRecommended}>${dateStr}${text}</option>`;
                        });
                    } else {
                        timeSelect.innerHTML = '<option value="">(Tidak ada waktu tersedia)</option>';
                    }
                };
                addressSelect.addEventListener('change', updateTimes);
                updateTimes();
            }

        } catch (e) {
            $('asLoading').style.display = 'none';
            $('asContent').style.display = 'block';
            
            const errMsg = (e.message || '').toLowerCase();
            if (errMsg.includes('can only be obtained') || errMsg.includes('already') || errMsg.includes('invalid status')) {
                $('asOptions').innerHTML = `
                    <div class="alert alert-warning py-1" style="font-size:0.75rem">
                        Shopee menginformasikan bahwa pesanan ini kemungkinan besar <strong>sudah diatur pengirimannya</strong> di pusat.<br>
                        Silakan klik tombol <b>Konfirmasi Pengiriman</b> di bawah untuk menarik resi secara otomatis dan mensinkronkan statusnya.
                    </div>
                    <input type="hidden" name="asMethod" value="auto">
                    <input type="hidden" name="auto_sync_only" value="1">
                `;
                $('asSubmitBtn').disabled = false;
            } else {
                $('asOptions').innerHTML = `<div class="alert alert-danger py-1" style="font-size:0.75rem">Gagal: ${e.message}</div>`;
            }
        }
    };

    window.submitArrangeShipment = async function () {
        const storeId = $('asStoreId').value;
        const orderSn = $('asOrderSn').value;
        const methodEl = document.querySelector('input[name="asMethod"]:checked') || document.querySelector('input[name="asMethod"]');
        const method = methodEl ? methodEl.value : '';
        
        const btn = $('asSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '⏳ Memproses...';
        
        let params = {};
        if (method === 'dropoff') {
            params = { dropoff: {} };
            const branchSelect = document.getElementById('asDropoffBranch');
            if (branchSelect && branchSelect.value) {
                params.dropoff.branch_id = Number(branchSelect.value) || branchSelect.value;
            }
        } else if (method === 'pickup') {
            params = { pickup: {} };
            const addressSelect = document.getElementById('asPickupAddress');
            const timeSelect = document.getElementById('asPickupTime');
            if (addressSelect && timeSelect) {
                params.pickup.address_id = Number(addressSelect.value) || addressSelect.value || 0;
                params.pickup.pickup_time_id = timeSelect.value || "";
            }
        }
        
        if (document.querySelector('input[name="auto_sync_only"]')) {
            params.auto_sync_only = 1;
        }

        try {
            const shipUrl = window._asBookingSn
                ? `/api/marketplace/stores/${storeId}/bookings/${window._asBookingSn}/ship`
                : `/api/marketplace/stores/${storeId}/orders/${orderSn}/ship`;
            await api(shipUrl, {
                method: 'POST',
                body: JSON.stringify(params)
            });
            
            bootstrap.Modal.getInstance($('arrangeShipmentModal')).hide();
            
            if (window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Atur pengiriman sukses untuk ' + orderSn,
                    timer: 3000,
                    showConfirmButton: false
                });
            } else {
                showQsAlert('success', 'Atur Pengiriman berhasil untuk ' + orderSn);
            }
            
            loadOrders();

            if ($('asPrintDocument') && $('asPrintDocument').checked) {
                printDocument(storeId, orderSn, window._asBookingSn, false);
            }
        } catch (e) {
            btn.disabled = false;
            btn.innerHTML = 'Coba Lagi';
            if (window.Swal) {
                Swal.fire('Gagal!', 'Atur Pengiriman Gagal: ' + e.message, 'error');
            } else {
                alert('Gagal Atur Pengiriman: ' + e.message);
            }
        }
    };

    window.printDocument = async function (storeId, orderSn, bookingSn = null, storedOnly = true) {
        // Jika order ini punya booking_sn, gunakan booking document API
        let url;
        if (bookingSn) {
            url = `/api/marketplace/stores/${storeId}/bookings/${bookingSn}/document`;
        } else {
            url = `/api/marketplace/stores/${storeId}/orders/${orderSn}/document`;
        }
        if (!storedOnly) url += `${url.includes('?') ? '&' : '?'}stored_only=0`;
        
        const alertText = storedOnly ? 'Membuka dokumen resi tersimpan di aplikasi...' : 'Meminta dokumen resi resmi dari Marketplace...';
        const alertHtml = `<div id="printAlert" style="position:fixed;top:20px;right:20px;background:#3b82f6;color:white;padding:10px 20px;border-radius:8px;z-index:9999;box-shadow:0 4px 6px rgba(0,0,0,0.1)">⏳ ${alertText}</div>`;
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        // Buka langsung agar tidak diblokir popup blocker
        window.open(url, '_blank');
        
        // Jangan langsung update UI. Tunggu 5 detik lalu ambil data asli dari server
        // untuk memastikan resi benar-benar berhasil diproses.
        setTimeout(async () => {
            const el = document.getElementById('printAlert');
            if (el) el.remove();
            
            try {
                const newOrders = await api(localOrdersUrl());
                applyOrdersResponse(newOrders);
                fulfillmentStatusMap.clear();
                orders.forEach(o => {
                    if (o.fulfillment_status) {
                        fulfillmentStatusMap.set(o.id, { id: o.fulfillment_id, status: o.fulfillment_status });
                        if (o.fulfillment_status === 'confirmed') fulfilledOrderIds.add(o.id);
                    }
                });
                render();
            } catch(e) {}
        }, 5000);
    };
    window.printAllDocuments = function() {
        let rows = getPackingRows();
        if (!rows.length) { alert('Tidak ada order yang sedang dikemas.'); return; }
        
        const checkedSns = Array.from(window.selectedPrintOrders || new Set());
        const hasSelection = checkedSns.length > 0;
        
        if (hasSelection) {
            rows = rows.filter(o => checkedSns.includes(o.channel_order_id));
        }
        
        const unprintedRows = rows.filter(o => {
            let printCount = o.print_count || 0;
            if (printCount === 0 && printedOrderIds.has(o.id)) printCount = 1;
            return printCount === 0 && !printedDocOrderSns.has(o.channel_order_id);
        });
        const total = rows.length;
        const unprinted = unprintedRows.length;
        const printed = total - unprinted;

        const modalHtml = `
            <div id="printOptsModal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:99999;backdrop-filter:blur(2px);">
                <div style="background:white;padding:28px;border-radius:16px;width:340px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);font-family:sans-serif;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                        <div style="width:40px;height:40px;border-radius:10px;background:#e0f2fe;color:#0284c7;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">🖨️</div>
                        <h3 style="margin:0;font-size:1.2rem;color:#0f172a;font-weight:700;">Cetak Resi Massal</h3>
                    </div>
                    
                    <p style="font-size:0.875rem;color:#64748b;margin-bottom:16px;line-height:1.5;">Anda akan mencetak dokumen resi lintas toko secara otomatis. Berikut rincian pesanan yang sedang dikemas:</p>
                    
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px;margin-bottom:20px;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.875rem;">
                            <span style="color:#64748b;">${hasSelection ? 'Total Order Terpilih' : 'Total Order (Semua Toko)'}</span>
                            <span style="font-weight:700;color:#0f172a;">${total}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.875rem;">
                            <span style="color:#64748b;">Sudah Pernah Dicetak</span>
                            <span style="font-weight:700;color:#0284c7;">${printed}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:0.875rem;padding-top:8px;border-top:1px dashed #cbd5e1;">
                            <span style="font-weight:700;color:#0f172a;">Belum Dicetak</span>
                            <span style="font-weight:700;color:#dc2626;background:#fef2f2;padding:2px 8px;border-radius:999px;">${unprinted}</span>
                        </div>
                    </div>
                    
                    <div style="margin-bottom:24px;background:#f0fdf4;padding:10px 12px;border-radius:8px;border:1px solid #bbf7d0;display:block;">
                        <label style="display:flex;align-items:center;gap:8px;font-size:0.875rem;cursor:pointer;color:#166534;font-weight:600;margin:0;">
                            <input type="checkbox" id="chkPrintGreeting" checked style="width:16px;height:16px;accent-color:#16a34a;cursor:pointer;">
                            Sertakan Kartu Ucapan
                        </label>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <button onclick="executePrintBulk('unprinted_only')" style="width:100%;padding:12px;border-radius:8px;font-weight:600;font-size:0.9rem;border:none;background:${unprinted === 0 ? '#e2e8f0' : '#10b981'};color:${unprinted === 0 ? '#94a3b8' : 'white'};cursor:${unprinted === 0 ? 'not-allowed' : 'pointer'};box-shadow:${unprinted === 0 ? 'none' : '0 2px 4px rgba(16,185,129,0.2)'};" ${unprinted === 0 ? 'disabled' : ''}>
                            Cetak Resi Belum Pernah Dicetak (${unprinted})
                        </button>
                        <button onclick="executePrintBulk('selected')" style="width:100%;padding:12px;border-radius:8px;font-weight:600;font-size:0.9rem;border:1px solid rgba(148,163,184,.25);background:#f8fafc;color:var(--shp-accent);cursor:pointer;">
                            ${hasSelection ? `Cetak Resi Terpilih (${total})` : `Cetak Semua Resi (${total})`}
                        </button>
                        <button onclick="executePrintBulk('reprint')" style="width:100%;padding:12px;border-radius:8px;font-weight:600;font-size:0.9rem;border:1px solid #f59e0b;background:#fffbeb;color:#b45309;cursor:pointer;">
                            ${hasSelection ? 'Cetak Ulang Resi Terpilih (Reprint)' : 'Cetak Ulang Semua Resi (Reprint)'}
                        </button>
                        <button onclick="document.getElementById('printOptsModal').remove()" style="width:100%;padding:10px;border-radius:8px;font-weight:500;font-size:0.9rem;border:none;background:transparent;color:#64748b;cursor:pointer;margin-top:8px;">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    };
    window.executePrintBulk = async function(mode) {
        let printGreeting = true;
        const chk = document.getElementById('chkPrintGreeting');
        if (chk) printGreeting = chk.checked;
        
        const modal = document.getElementById('printOptsModal');
        if (modal) modal.remove();

        let rows = getPackingRows();
        const checkedSns = Array.from(window.selectedPrintOrders || new Set());
        if (checkedSns.length > 0) {
            rows = rows.filter(o => checkedSns.includes(o.channel_order_id));
        }

        if (mode === 'unprinted_only') {
            rows = rows.filter(o => {
                let printCount = o.print_count || 0;
                if (printCount === 0 && printedOrderIds.has(o.id)) printCount = 1;
                return printCount === 0 && !printedDocOrderSns.has(o.channel_order_id);
            });
        } else if (mode === 'selected') {
            // Check if any selected already printed
            const alreadyPrinted = rows.filter(o => (o.print_count || 0) > 0 || printedOrderIds.has(o.id));
            if (alreadyPrinted.length > 0) {
                if (window.Swal) {
                    const result = await Swal.fire({
                        title: 'Cetak Ulang Resi?',
                        text: `Terdapat ${alreadyPrinted.length} pesanan yang resinya sudah pernah dicetak sebelumnya. Apakah Anda yakin ingin mencetak ulang resi tersebut?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3b82f6',
                        cancelButtonColor: '#ef4444',
                        confirmButtonText: 'Ya, Lanjutkan Cetak',
                        cancelButtonText: 'Batal'
                    });
                    if (!result.isConfirmed) return;
                } else {
                    if (!confirm(`Ada ${alreadyPrinted.length} pesanan yang terpilih sudah pernah dicetak resinya. Tetap lanjutkan cetak ulang?`)) return;
                }
            }
        }

        if (!rows.length) { alert('Tidak ada order untuk dicetak.'); return; }
        
        const payloadOrders = rows.map((o, idx) => ({
            store_id: o.store_id,
            channel_order_id: o.channel_order_id,
            order_sn: o.channel_order_id,
            booking_sn: (o.is_kilat || o.is_booking) ? o.booking_sn : null,
            is_booking: !!o.is_booking,
            is_kilat: !!o.is_kilat,
            position: idx
        }));

        const alertHtml = `<div id="printBulkAlert" style="position:fixed;top:20px;right:20px;background:#f59e0b;color:white;padding:15px 25px;border-radius:8px;z-index:99999;box-shadow:0 4px 6px rgba(0,0,0,0.1);font-family:sans-serif;font-weight:bold;">⏳ Meminta dokumen resi dari Server... Mohon tunggu...</div>`;
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        try {
                        let printUrl = '/api/marketplace/documents/bulk-print';
            let printPayload = { 
                orders: payloadOrders, 
                mode: mode,
                with_greeting: printGreeting ? 1 : 0
            };
            
            if (typeof IS_DUMMY_MODE !== 'undefined' && IS_DUMMY_MODE) {
                printUrl = '/dev/dummy/bulk-print';
                const scenario = document.getElementById('dummyScenarioSelect')?.value;
                if (scenario) {
                    printPayload.scenario = scenario;
                }
            }

            const res = await fetch(printUrl, {
                method: 'POST',
                headers: { 
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content 
                },
                body: JSON.stringify(printPayload)
            });
            
            const data = await res.json();
            
            const el = document.getElementById('printBulkAlert');
            if (el) el.remove();

            if (!res.ok) {
                if (data.success_count === 0 && data.failed_orders && data.failed_orders.length > 0) {
                    showFailedPrintModal(data);
                } else {
                    alert('Gagal: ' + (data.error || 'Terjadi kesalahan'));
                }
                return;
            }
            
            showFailedPrintModal(data);
            
            setTimeout(async () => {
                try {
                    const newOrders = await api(localOrdersUrl());
                    applyOrdersResponse(newOrders);
                    fulfillmentStatusMap.clear();
                    orders.forEach(o => {
                        if (o.fulfillment_status) {
                            fulfillmentStatusMap.set(o.id, { id: o.fulfillment_id, status: o.fulfillment_status });
                            if (o.fulfillment_status === 'confirmed') fulfilledOrderIds.add(o.id);
                        }
                    });
                    render();
                } catch(e) {}
            }, 3000);
            
        } catch (err) {
            const el = document.getElementById('printBulkAlert');
            if (el) el.remove();
            alert('Terjadi kesalahan jaringan atau server timeout.');
        }
    };
    
    window.showFailedPrintModal = function(data) {
        let failedListHtml = '';
        if (data.failed_orders && data.failed_orders.length > 0) {
            failedListHtml = `
            <div style="margin-top:16px;max-height:200px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;">
                <table style="width:100%;font-size:0.8rem;border-collapse:collapse;">
                    <thead style="background:#f1f5f9;position:sticky;top:0;">
                        <tr><th style="text-align:left;padding:8px;">Store</th><th style="text-align:left;padding:8px;">Order SN</th><th style="text-align:left;padding:8px;">Alasan</th></tr>
                    </thead>
                    <tbody>
                        ${data.failed_orders.map(f => `<tr>
                            <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">${f.store_name}</td>
                            <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;font-family:monospace;">${f.channel_order_id}</td>
                            <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;color:#ef4444;">${f.reason}</td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>`;
        }

        const modalHtml = `
            <div id="printResultModal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:99999;backdrop-filter:blur(2px);">
                <div style="background:white;padding:28px;border-radius:16px;width:500px;max-width:90%;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);font-family:sans-serif;">
                    <h3 style="margin:0 0 16px 0;font-size:1.2rem;color:#0f172a;font-weight:700;">Print</h3>
                    <div style="display:flex;gap:16px;margin-bottom:16px;">
                        <div style="flex:1;background:#f0fdf4;padding:12px;border-radius:8px;border:1px solid #bbf7d0;text-align:center;">
                            <div style="font-size:0.8rem;color:#166534;font-weight:bold;text-transform:uppercase;">Berhasil</div>
                            <div style="font-size:2rem;color:#15803d;font-weight:bold;">${data.success_count || 0}</div>
                        </div>
                        <div style="flex:1;background:#fef2f2;padding:12px;border-radius:8px;border:1px solid #fecaca;text-align:center;">
                            <div style="font-size:0.8rem;color:#991b1b;font-weight:bold;text-transform:uppercase;">Gagal</div>
                            <div style="font-size:2rem;color:#b91c1c;font-weight:bold;">${data.failed_count || 0}</div>
                        </div>
                    </div>
                    ${failedListHtml}
                    <div style="display:flex;gap:12px;margin-top:24px;">
                        ${data.success_count > 0 ? `
                        <button onclick="window.open('${data.download_url}', '_blank'); document.getElementById('printResultModal').remove();" style="flex:1;padding:12px;border-radius:8px;font-weight:600;font-size:0.9rem;border:none;background:var(--shp-accent);color:white;cursor:pointer;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                            ⬇️ Unduh PDF Berhasil
                        </button>` : ''}
                        <button onclick="document.getElementById('printResultModal').remove()" style="flex:1;padding:12px;border-radius:8px;font-weight:600;font-size:0.9rem;border:1px solid #cbd5e1;background:white;color:#334155;cursor:pointer;">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    };
    window.printAllGreetings = function() {
        const rows = getPackingRows();
        // Cukup ambil storeId dari baris pertama saja untuk memenuhi route Laravel
        const storeId = rows.length > 0 ? rows[0].store_id : (activeStore || 1);
        
        const alertHtml = `<div id="printBulkAlertGreetings" style="position:fixed;top:20px;right:20px;background:#8b5cf6;color:white;padding:10px 20px;border-radius:8px;z-index:9999;box-shadow:0 4px 6px rgba(0,0,0,0.1)">⏳ Meminta dokumen kartu...</div>`;
        document.body.insertAdjacentHTML('beforeend', alertHtml);

        // Hanya buka 1 tab berisi 1 halaman sesuai permintaan user
        const url = `/api/marketplace/stores/${storeId}/documents/bulk-greetings?orders=1`;
        window.open(url, '_blank');

        setTimeout(() => {
            const el = document.getElementById('printBulkAlertGreetings');
            if (el) el.remove();
        }, 1500);
    };

    window.printSingleGreeting = function(storeId, orderSn) {
        const alertHtml = `<div id="printBulkAlertGreetings" style="position:fixed;top:20px;right:20px;background:#8b5cf6;color:white;padding:10px 20px;border-radius:8px;z-index:9999;box-shadow:0 4px 6px rgba(0,0,0,0.1)">⏳ Meminta dokumen kartu...</div>`;
        document.body.insertAdjacentHTML('beforeend', alertHtml);
        
        const url = `/api/marketplace/stores/${storeId}/documents/bulk-greetings?orders=1&order_sns=${orderSn}`;
        window.open(url, '_blank');

        setTimeout(() => {
            const el = document.getElementById('printBulkAlertGreetings');
            if (el) el.remove();
        }, 1500);
    };

    // ── [DEV ONLY] Fresh Orders ───────────────────────────────────────────────
    async function devFreshOrders() {
        const confirmed = window.confirm(
            '⚠️ [DEV MODE]\n\nIni akan menghapus SEMUA marketplace orders, fulfillments, dan inventory mutations terkait.\n\nLanjutkan?'
        );
        if (!confirmed) return;

        const btn = document.getElementById('btnFreshOrders');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Menghapus...'; }

        try {
            const res = await fetch('/api/dev/fresh-orders', { method: 'POST' });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal');
            alert(data.message);
            loadOrders();
        } catch (e) {
            alert('Error: ' + e.message);
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = '🗑 Fresh'; }
        }
    }
    window.devFreshOrders = devFreshOrders;

    // ── [DEV ONLY] Seed Dummy Orders ─────────────────────────────────────────
    async function devSeedOrders() {
        const input = window.prompt(
            '📥 [DEV MODE] Buat dummy READY_TO_SHIP orders\n\nMasukkan jumlah order (1–50):',
            '5'
        );
        if (input === null) return; // user cancel

        const count = parseInt(input, 10);
        if (isNaN(count) || count < 1 || count > 50) {
            alert('Jumlah tidak valid. Masukkan angka 1–50.');
            return;
        }

        const btn = document.getElementById('btnSeedOrders');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Membuat...'; }

        try {
            const res = await fetch('/api/dev/seed-orders', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ count, status: 'READY_TO_SHIP' }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal');
            alert(data.message);
            loadOrders();
        } catch (e) {
            alert('Error: ' + e.message);
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = '📥 Dummy'; }
        }
    }
    window.devSeedOrders = devSeedOrders;

    // ── [DEV ONLY] Reset Fulfillments ────────────────────────────────────────
    async function devResetFulfillments() {
        const ok = window.confirm('🔄 [DEV MODE]\n\nHapus semua fulfillments?\nOrder akan kembali ke tab "Perlu Proses".\n(Data order tidak dihapus)');
        if (!ok) return;
        const btn = document.getElementById('btnResetFulfillments');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Mereset...'; }
        try {
            const res  = await fetch('/api/dev/reset-fulfillments', { method: 'POST' });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal');
            alert(data.message);
            loadOrders();
            devLoadStats();
        } catch (e) { alert('Error: ' + e.message); }
        finally { if (btn) { btn.disabled = false; btn.textContent = '🔄 Reset Fulfillments'; } }
    }
    window.devResetFulfillments = devResetFulfillments;

    // ── [DEV ONLY] Remap Items ────────────────────────────────────────────────
    async function devRemapItems() {
        const btn = document.getElementById('btnRemapItems');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Remapping...'; }
        try {
            const res  = await fetch('/api/marketplace/remap-items', { method: 'POST' });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal');
            alert(`✅ Remap selesai.\nUpdated: ${data.updated ?? '?'} item\nErrors: ${data.errors ?? 0}`);
            devLoadStats();
        } catch (e) { alert('Error: ' + e.message); }
        finally { if (btn) { btn.disabled = false; btn.textContent = '🔁 Remap Items'; } }
    }
    window.devRemapItems = devRemapItems;

    // ── [DEV ONLY] Panel toggle + stats ──────────────────────────────────────
    async function devLoadStats() {
        try {
            const s   = await (await fetch('/api/dev/stats')).json();
            const el  = document.getElementById('devStats');
            if (el) el.textContent =
                `📦 ${s.orders} orders  |  ⚡ ${s.perluProses} perlu proses  |  🔄 ${s.sedangPacking} packing  |  ✅ ${s.fulfilled} selesai`;
        } catch {}
    }
    function toggleDevPanel() {
        const panel = document.getElementById('devPanel');
        const btn   = document.getElementById('btnDevPanel');
        if (!panel) return;
        const open = panel.style.display === 'none';
        panel.style.display = open ? 'block' : 'none';
        if (btn) btn.style.background = open ? '#ede9fe' : '#faf5ff';
        if (open) devLoadStats();
    }
    window.toggleDevPanel  = toggleDevPanel;
    window.devLoadStats    = devLoadStats;
    // ─────────────────────────────────────────────────────────────────────────

    // Buka halaman chat dengan konteks order (deep-link)
    window.openChatForOrder = function (storeId, orderSn) {
        window.location = `/marketplace/chat?store_id=${storeId}&order_sn=${encodeURIComponent(orderSn)}`;
    };

    loadOrders();

    // ── Silent refresh (dipakai polling & realtime push) ────────────────────
    let silentRefreshBusy = false;
    async function silentRefresh() {
        if (silentRefreshBusy) return;
        silentRefreshBusy = true;
        try {
            const newOrders = await api(localOrdersUrl());
            applyOrdersResponse(newOrders);
            fulfillmentStatusMap.clear();
            orders.forEach(o => {
                if (o.fulfillment_status) {
                    fulfillmentStatusMap.set(o.id, { id: o.fulfillment_id, status: o.fulfillment_status });
                    if (o.fulfillment_status === 'confirmed') fulfilledOrderIds.add(o.id);
                }
            });
            // Hanya update render tanpa merusak UX loading screen yang sudah ada
            render();
            updateLastSyncTime();
        } catch(e) {} finally {
            silentRefreshBusy = false;
        }
    }

    // ── Polling (tanpa Reverb) ───────────────────────────────────────────────
    let lastPollAt = Date.now();
    setInterval(() => {
        // Polling setiap 15 detik
        if (Date.now() - lastPollAt >= 15000) {
            lastPollAt = Date.now();
            // Hanya poll jika halaman sedang aktif/terlihat
            if (!document.hidden) {
                silentRefresh();
            }
        }
    }, 5000);

    // Re-render on resize (mobile ↔ desktop switch)
    // ── Review Modal (Sedang Proses) ────────────────────────────────────────
    window.openReviewModal = function(orderId) {
        const o = orders.find(x => x.id === orderId);
        if (!o) return;

        $('ormTitle').textContent = o.channel_order_id || '—';
        const store = [o.store?.name, o.store?.channel?.name].filter(Boolean).join(' · ');
        $('ormSub').textContent = store;
        $('ordReviewBg').classList.add('open');
        document.body.style.overflow = 'hidden';

        const lines    = o.fulfillment_resolve_lines || [];
        const scanLog  = (o.fulfillment_scan_log || []).filter(s => s.code && s.qty > 0);
        const summary  = o.fulfillment_packing_summary;

        // DATA PESANAN table
        let pesananHtml = '';
        if (lines.length) {
            const rows = lines.filter(l => l.code).map(l => {
                const isShort = (l.qty_fulfilled ?? 0) < (l.qty_ordered ?? 1);
                const statusHtml = isShort
                    ? `<span class="orm-status-short">⚠ Kurang</span>`
                    : `<span class="orm-status-ok">✓ OK</span>`;
                return `<tr>
                    <td>
                        <div class="orm-item-code">${esc(l.code)}</div>
                        ${l.name ? `<div class="orm-item-name">${esc(l.name)}</div>` : ''}
                    </td>
                    <td class="orm-qty">${l.qty_ordered ?? 1}</td>
                    <td class="orm-qty ${isShort ? 'short' : 'ok'}">${l.qty_fulfilled ?? 0}</td>
                    <td style="text-align:center">${statusHtml}</td>
                </tr>`;
            }).join('');
            pesananHtml = `
                <div class="orm-section-label">📋 Data Pesanan
                    <span class="orm-cnt">${lines.filter(l=>l.code).length} item</span>
                </div>
                <table class="orm-table">
                    <thead><tr>
                        <th>Item</th>
                        <th style="text-align:center">Dipesan</th>
                        <th style="text-align:center">Di-Pack</th>
                        <th style="text-align:center">Status</th>
                    </tr></thead>
                    <tbody>${rows}</tbody>
                </table>`;
        }

        // ITEM TERSCAN list
        let scanHtml = '';
        if (scanLog.length) {
            const scanItems = scanLog.map(s => `<div class="orm-scan-item">
                <span class="orm-scan-code">${esc(s.code)}</span>
                <span class="orm-scan-name">${esc(s.name || '')}</span>
                <span class="orm-scan-qty">×${s.qty}</span>
            </div>`).join('');
            scanHtml = `
                <div class="orm-section-label" style="color:#7c3aed">📦 Item Terscan
                    <span class="orm-cnt">${scanLog.length} item</span>
                </div>
                <div class="orm-scan-list">${scanItems}</div>`;
        } else {
            scanHtml = `
                <div class="orm-section-label" style="color:#7c3aed">📦 Item Terscan</div>
                <div style="font-size:.78rem;color:#94a3b8;font-style:italic;padding:.3rem 0">Belum ada item terscan.</div>`;
        }

        $('ormBody').innerHTML = pesananHtml + scanHtml;
    };

    window.closeReviewModal = function(e) {
        if (e && e.target !== $('ordReviewBg')) return;
        $('ordReviewBg').classList.remove('open');
        document.body.style.overflow = '';
    };

    window.checkBookingDriver = async function(storeId, orderSn) {
        try {
            Swal.fire({
                title: 'Loading...',
                text: 'Mengecek data driver ke Shopee...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            const res = await api(`/api/marketplace/stores/${storeId}/orders/${orderSn}/booking-detail`);
            Swal.fire({
                title: 'Data Booking/Driver',
                html: `<pre style="text-align:left; font-size:12px; background:#f8f9fa; padding:10px; border-radius:5px; max-height:400px; overflow:auto;">${JSON.stringify(res, null, 2)}</pre>`,
                width: '600px',
                confirmButtonText: 'Tutup'
            });
        } catch (e) {
            Swal.fire('Error', e.message || 'Gagal mengecek data driver.', 'error');
        }
    };

    window.checkOrderDetailRaw = async function(storeId, orderSn) {
        try {
            Swal.fire({
                title: 'Loading...',
                text: 'Mengambil API get_order_detail...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
            const res = await api(`/api/marketplace/stores/${storeId}/orders/${orderSn}/raw-detail`);
            Swal.fire({
                title: 'Raw API: get_order_detail',
                html: `<pre style="text-align:left; font-size:12px; background:#1e293b; color:#e2e8f0; padding:10px; border-radius:5px; max-height:450px; overflow:auto;">${JSON.stringify(res, null, 2)}</pre>`,
                width: '650px',
                confirmButtonText: 'Tutup'
            });
        } catch (e) {
            Swal.fire('Error', e.message || 'Gagal mengambil detail order.', 'error');
        }
    };

    // ── Re-render on resize ───────────────────────────────────────────────
    let _resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(_resizeTimer);
        _resizeTimer = setTimeout(() => renderTable(), 150);
    });
})();

    window.changePage = function(page) {
        if (page < 1 || page > lastPage) return;
        currentPage = page;
        loadOrders();
    };
