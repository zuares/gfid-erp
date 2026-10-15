(() => {
    const { api, esc } = window.mpHelpers || {};
    if (!api) return;

    const $ = (id) => document.getElementById(id);
    const root = document.querySelector('.promo-wrap');
    const refs = {
        store: $('storeSelect'), status: $('voucherStatusSelect'), search: $('voucherSearchInput'), body: $('voucherBody'),
        alert: $('voucherAlert'), refresh: $('voucherRefreshBtn'), create: $('voucherCreateBtn'),
        titlePage: $('promotionPageTitle'), subPage: $('promotionPageSub'), discountActions: $('discountActions'),
        discountKpis: $('discountKpis'), voucherKpis: $('voucherKpis'), voucherKpiTotal: $('voucherKpiTotal'),
        voucherKpiOngoing: $('voucherKpiOngoing'), voucherKpiUsage: $('voucherKpiUsage'),
        tabs: Array.from(document.querySelectorAll('[data-promo-tab]')), panels: Array.from(document.querySelectorAll('[data-promo-panel]')),
        modal: $('voucherFormModal'), title: $('voucherFormTitle'), id: $('voucherId'), name: $('voucherName'), code: $('voucherCode'),
        type: $('voucherType'), reward: $('voucherRewardType'), usage: $('voucherUsageQuantity'), value: $('voucherValue'),
        min: $('voucherMinBasketPrice'), max: $('voucherMaxPrice'), start: $('voucherStart'), end: $('voucherEnd'),
        displayStart: $('voucherDisplayStart'), channels: $('voucherDisplayChannels'), items: $('voucherItemIds'), itemsWrap: $('voucherItemsWrap'),
        save: $('voucherSaveBtn'), valueLabel: $('voucherValueLabel'),
    };
    if (!refs.store || !refs.body || !refs.modal) return;

    const modal = new bootstrap.Modal(refs.modal);
    const state = { rows: [], editing: false };
    const initialTab = root?.dataset.activeTab === 'voucher' ? 'voucher' : 'discount';

    const text = (value) => value === null || typeof value === 'undefined' ? '' : String(value);
    const fmtTs = (value) => value ? new Date(Number(value) * 1000).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';
    const fmtRp = (value) => value === null || typeof value === 'undefined' || value === '' ? '—' : `Rp${Number(value || 0).toLocaleString('id-ID')}`;
    const localInput = (value) => {
        if (!value) return '';
        const d = new Date(Number(value) * 1000 - new Date().getTimezoneOffset() * 60000);
        return Number.isNaN(d.getTime()) ? '' : d.toISOString().slice(0, 16);
    };
    const timestamp = (value) => {
        if (!value) return null;
        const d = new Date(String(value));
        return Number.isNaN(d.getTime()) ? null : Math.floor(d.getTime() / 1000);
    };
    const selectedStoreId = () => refs.store.value || '';

    function setAlert(message, type = 'danger') {
        refs.alert.className = `alert alert-${type} mb-3`;
        refs.alert.textContent = message;
        refs.alert.classList.remove('d-none');
    }
    function clearAlert() { refs.alert.classList.add('d-none'); refs.alert.textContent = ''; }
    function statusClass(status) { return ({ ongoing: 'promo-ongoing', upcoming: 'promo-upcoming', expired: 'promo-ended', ended: 'promo-ended', deleted: 'promo-ended' })[String(status || '').toLowerCase()] || 'promo-ended'; }
    function statusLabel(status) { return ({ ongoing: 'Ongoing', upcoming: 'Upcoming', expired: 'Expired', ended: 'Ended', deleted: 'Deleted' })[String(status || '').toLowerCase()] || status || '—'; }
    function benefit(voucher) {
        const reward = Number(voucher.reward_type || 0);
        if (reward === 1) return fmtRp(voucher.discount_amount);
        if (reward === 2) return `${text(voucher.percentage)}%${voucher.max_price ? ` maks. ${fmtRp(voucher.max_price)}` : ''}`;
        if (reward === 3) return `${text(voucher.percentage)}% cashback koin${voucher.max_price ? ` maks. ${fmtRp(voucher.max_price)}` : ''}`;
        return '—';
    }

    function updateVoucherKpis() {
        const total = state.rows.length;
        const ongoing = state.rows.filter((voucher) => String(voucher.voucher_status || '').toLowerCase() === 'ongoing').length;
        const usage = state.rows.reduce((sum, voucher) => sum + Number(voucher.current_usage || 0), 0);
        if (refs.voucherKpiTotal) refs.voucherKpiTotal.textContent = total.toLocaleString('id-ID');
        if (refs.voucherKpiOngoing) refs.voucherKpiOngoing.textContent = ongoing.toLocaleString('id-ID');
        if (refs.voucherKpiUsage) refs.voucherKpiUsage.textContent = usage.toLocaleString('id-ID');
    }

    function activateTab(tab, updateUrl = true) {
        const selected = tab === 'voucher' ? 'voucher' : 'discount';
        const isVoucher = selected === 'voucher';
        refs.tabs.forEach((button) => {
            const active = button.dataset.promoTab === selected;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        refs.panels.forEach((panel) => panel.classList.toggle('d-none', panel.dataset.promoPanel !== selected));
        refs.discountActions?.classList.toggle('d-none', isVoucher);
        refs.discountKpis?.classList.toggle('d-none', isVoucher);
        refs.voucherKpis?.classList.toggle('d-none', !isVoucher);
        if (refs.titlePage) refs.titlePage.textContent = isVoucher ? 'Voucher Toko' : 'Promosi Diskon';
        if (refs.subPage) refs.subPage.textContent = isVoucher ? 'Buat dan kelola voucher seller langsung dari Shopee Open API.' : 'Atur harga promo per campaign tanpa mengubah harga normal produk.';
        if (updateUrl) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', selected);
            window.history.replaceState({}, '', url);
        }
        if (isVoucher && refs.store.value) loadVouchers();
    }

    function render() {
        updateVoucherKpis();
        const query = text(refs.search.value).trim().toLowerCase();
        const rows = state.rows.filter((v) => !query || [v.voucher_name, v.voucher_code, v.voucher_id].some((x) => text(x).toLowerCase().includes(query)));
        if (!rows.length) {
            refs.body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada voucher untuk filter ini.</td></tr>';
            return;
        }
        refs.body.innerHTML = rows.map((v) => {
            const usage = v.usage_quantity ? `${Number(v.current_usage || 0).toLocaleString('id-ID')} / ${Number(v.usage_quantity).toLocaleString('id-ID')}` : '—';
            const actions = `<div class="d-flex justify-content-end gap-1"><button class="btn btn-sm btn-outline-primary" data-voucher-edit="${Number(v.voucher_id)}" title="Edit"><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-outline-warning" data-voucher-end="${Number(v.voucher_id)}" title="Akhiri"><i class="bi bi-stop-circle"></i></button><button class="btn btn-sm btn-outline-danger" data-voucher-delete="${Number(v.voucher_id)}" title="Hapus"><i class="bi bi-trash"></i></button></div>`;
            return `<tr><td><div class="promo-name">${esc(v.voucher_name || 'Tanpa nama')}</div><div class="promo-muted"><code>${esc(v.voucher_code || '—')}</code> · ID ${esc(v.voucher_id)}</div></td><td><div class="promo-name">${esc(benefit(v))}</div><div class="promo-muted">Min. belanja ${esc(fmtRp(v.min_basket_price))}</div></td><td><div>${esc(fmtTs(v.start_time))}</div><div class="promo-muted">s/d ${esc(fmtTs(v.end_time))}</div></td><td>${esc(usage)}</td><td><span class="promo-badge ${statusClass(v.voucher_status)}">${esc(statusLabel(v.voucher_status))}</span></td><td>${actions}</td></tr>`;
        }).join('');
    }

    async function loadVouchers() {
        const storeId = selectedStoreId();
        if (!storeId) { refs.body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Pilih toko Shopee terlebih dahulu.</td></tr>'; return; }
        clearAlert();
        refs.body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4"><span class="prod-tab-spinner"></span> Memuat voucher dari Shopee…</td></tr>';
        try {
            const params = new URLSearchParams({ store_id: storeId, status: refs.status.value || 'all', page_size: '100' });
            const result = await api(`/api/marketplace/vouchers?${params}`);
            state.rows = Array.isArray(result.vouchers) ? result.vouchers : [];
            render();
        } catch (error) {
            state.rows = [];
            refs.body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Gagal memuat voucher.</td></tr>';
            setAlert(error.message || 'Gagal memuat voucher dari Shopee.');
        }
    }

    function setForm(voucher = null) {
        state.editing = Boolean(voucher);
        refs.title.textContent = state.editing ? 'Edit Voucher Toko' : 'Buat Voucher Toko';
        refs.id.value = voucher?.voucher_id || '';
        refs.name.value = voucher?.voucher_name || '';
        refs.code.value = voucher?.voucher_code || '';
        refs.type.value = voucher?.voucher_type || '1';
        refs.reward.value = voucher?.reward_type || '1';
        refs.usage.value = voucher?.usage_quantity ?? '';
        refs.value.value = voucher?.reward_type === 1 ? (voucher?.discount_amount ?? '') : (voucher?.percentage ?? '');
        refs.min.value = voucher?.min_basket_price ?? '';
        refs.max.value = voucher?.max_price ?? '';
        refs.start.value = localInput(voucher?.start_time);
        refs.end.value = localInput(voucher?.end_time);
        refs.displayStart.value = localInput(voucher?.display_start_time);
        const channels = Array.isArray(voucher?.display_channel_list) && voucher.display_channel_list.length ? voucher.display_channel_list.map(String) : ['1'];
        Array.from(refs.channels.options).forEach((option) => { option.selected = channels.includes(option.value); });
        refs.items.value = Array.isArray(voucher?.item_id_list) ? voucher.item_id_list.join(', ') : '';
        refs.code.disabled = state.editing;
        refs.type.disabled = state.editing;
        refs.reward.disabled = state.editing;
        refs.start.disabled = state.editing && voucher?.voucher_status === 'ongoing';
        updateRewardUi();
        updateTypeUi();
    }

    function updateRewardUi() {
        const percentage = refs.reward.value !== '1';
        refs.valueLabel.textContent = percentage ? 'Persentase benefit (%)' : 'Nominal potongan (Rp)';
        refs.value.min = '0'; refs.value.max = percentage ? '100' : '';
    }
    function updateTypeUi() { refs.itemsWrap.classList.toggle('d-none', refs.type.value !== '2'); }

    async function openEdit(voucherId) {
        try {
            const result = await api(`/api/marketplace/vouchers/${selectedStoreId()}/${voucherId}`);
            setForm(result.voucher || {}); modal.show();
        } catch (error) { setAlert(error.message || 'Gagal memuat detail voucher.'); }
    }

    function payload() {
        const reward = Number(refs.reward.value);
        const itemIds = text(refs.items.value).split(',').map((id) => Number(id.trim())).filter((id) => id > 0);
        const channels = Array.from(refs.channels.selectedOptions).map((option) => Number(option.value));
        const base = {
            store_id: Number(selectedStoreId()), voucher_name: refs.name.value.trim(), voucher_code: refs.code.value.trim().toUpperCase(),
            usage_quantity: Number(refs.usage.value || 0), min_basket_price: Number(refs.min.value || 0), max_price: Number(refs.max.value || 0),
            start_time: timestamp(refs.start.value), end_time: timestamp(refs.end.value), display_start_time: timestamp(refs.displayStart.value),
            display_channel_list: channels, item_id_list: itemIds,
        };
        if (!state.editing) { base.voucher_type = Number(refs.type.value); base.reward_type = reward; }
        if (reward === 1) base.discount_amount = Number(refs.value.value || 0);
        else base.percentage = Number(refs.value.value || 0);
        return Object.fromEntries(Object.entries(base).filter(([, value]) => value !== null && value !== '' && !(Array.isArray(value) && value.length === 0)));
    }

    async function save() {
        const body = payload();
        if (!body.voucher_name || !body.usage_quantity || !body.start_time || !body.end_time) { setAlert('Nama, kuota, mulai, dan selesai wajib diisi.'); return; }
        if (!state.editing && (!body.voucher_code || !body.voucher_type || !body.reward_type)) { setAlert('Kode, jenis voucher, dan jenis benefit wajib diisi.'); return; }
        if (body.end_time < body.start_time) { setAlert('Waktu selesai harus setelah waktu mulai.'); return; }
        refs.save.disabled = true;
        try {
            const id = refs.id.value;
            const url = state.editing ? `/api/marketplace/vouchers/${selectedStoreId()}/${id}/update` : '/api/marketplace/vouchers';
            await api(url, { method: 'POST', body: JSON.stringify(body) });
            modal.hide(); await loadVouchers();
        } catch (error) { setAlert(error.message || 'Gagal menyimpan voucher.'); }
        finally { refs.save.disabled = false; }
    }

    async function mutate(voucherId, action, question) {
        if (!window.confirm(question)) return;
        try { await api(`/api/marketplace/vouchers/${selectedStoreId()}/${voucherId}/${action}`, { method: 'POST', body: JSON.stringify({}) }); await loadVouchers(); }
        catch (error) { setAlert(error.message || 'Gagal mengubah voucher.'); }
    }

    refs.body.addEventListener('click', (event) => {
        const edit = event.target.closest('[data-voucher-edit]');
        const end = event.target.closest('[data-voucher-end]');
        const del = event.target.closest('[data-voucher-delete]');
        if (edit) openEdit(edit.dataset.voucherEdit);
        if (end) mutate(end.dataset.voucherEnd, 'end', 'Akhiri voucher ini di Shopee?');
        if (del) mutate(del.dataset.voucherDelete, 'delete', 'Hapus voucher ini dari Shopee?');
    });
    refs.search.addEventListener('input', render);
    refs.status.addEventListener('change', loadVouchers);
    refs.store.addEventListener('change', loadVouchers);
    refs.refresh.addEventListener('click', loadVouchers);
    refs.create.addEventListener('click', () => { setForm(); modal.show(); });
    refs.save.addEventListener('click', save);
    refs.reward.addEventListener('change', updateRewardUi);
    refs.type.addEventListener('change', updateTypeUi);
    refs.tabs.forEach((button) => button.addEventListener('click', () => activateTab(button.dataset.promoTab)));

    window.loadVouchers = loadVouchers;
    window.activatePromotionTab = activateTab;
    window.addEventListener('DOMContentLoaded', () => {
        activateTab(initialTab, false);
        const boot = (tries = 0) => {
            if (refs.store.options.length > 0 || tries >= 20) {
                if (initialTab === 'voucher') loadVouchers();
                return;
            }
            window.setTimeout(() => boot(tries + 1), 150);
        };
        boot();
    });
})();
