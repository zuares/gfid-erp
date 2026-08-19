(function () {
    const routes = window.AdsDashboardRoutes || {};

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function getStoreId() {
        const select = document.querySelector('select[name="store_id"]');
        if (select && select.value) return select.value;
        return 'all';
    }

    function getToast(msg) {
        if (typeof window.showToast === 'function') return window.showToast(msg);
        window.alert(msg);
    }

    function setBusyButton(btn, html, disabled = true) {
        if (!btn) return;
        if (html !== undefined) btn.innerHTML = html;
        btn.disabled = disabled;
    }

    function normalizeDecimal(value) {
        const raw = String(value ?? '').trim().replace(/\s/g, '');
        if (raw.includes(',') && raw.includes('.')) {
            return raw.replace(/\./g, '').replace(',', '.');
        }
        return raw.replace(',', '.');
    }

    function formatShortIDR(value) {
        const num = Number(value || 0);
        const abs = Math.abs(num);
        const fmt = (n) => n.toFixed(1).replace(/\.0$/, '');

        if (abs >= 1_000_000_000) return fmt(num / 1_000_000_000) + 'B';
        if (abs >= 1_000_000) return fmt(num / 1_000_000) + 'M';
        if (abs >= 1_000) return fmt(num / 1_000) + 'K';
        return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(num);
    }

    window.__syncQuick = async function (type, btn) {
        const label = type === 'yesterday' ? 'kemarin' : 'hari ini';
        const syncRoute = routes.sync || routes.adsSync || routes.ads_sync || '/marketplace/ads-dashboard/sync';
        const original = btn ? btn.innerHTML : '';

        setBusyButton(btn, '<i class="bi bi-arrow-repeat spin-icon" style="display:inline-block;"></i> Mengantre...');

        try {
            const res = await fetch(syncRoute, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    store_id: getStoreId(),
                    sync_type: type,
                }),
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || ('HTTP ' + res.status));

            getToast(data.message || ('Sync ' + label + ' berhasil diantrekan.'));
            setTimeout(() => window.location.reload(), 1200);
        } catch (err) {
            getToast('Gagal menjalankan sync ' + label + ': ' + err.message);
        } finally {
            setBusyButton(btn, original, false);
        }
    };

    window.__syncRefresh = function (btn) {
        if (btn) {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.classList.add('spin-icon');
                setTimeout(() => icon.classList.remove('spin-icon'), 900);
            }
        }
        window.location.reload();
    };

    window.clearAdsData = async function () {
        const clearRoute = routes.clear || '/marketplace/ads-dashboard/clear';
        const jawaban = prompt('PERINGATAN: SEMUA data performa iklan + riwayat sync akan dihapus PERMANEN dan harus di-backfill ulang dari Shopee.\n\nKetik HAPUS (huruf besar) untuk melanjutkan:');
        if (jawaban !== 'HAPUS') {
            if (jawaban !== null) getToast('Dibatalkan — ketik HAPUS persis untuk konfirmasi.');
            return;
        }

        try {
            const res = await fetch(clearRoute, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error(data.message || 'Terjadi kesalahan.');

            getToast('Data iklan berhasil dibersihkan!');
            window.location.reload();
        } catch (err) {
            getToast('Gagal membersihkan data: ' + err.message);
        }
    };

    let campaignHourlyChartObj = null;

    function renderCampaignHourlyChart(hourlyData) {
        const canvas = document.getElementById('campaignHourlyChartCanvas');
        if (!canvas || typeof Chart === 'undefined') return;

        const ctx = canvas.getContext('2d');
        if (campaignHourlyChartObj) campaignHourlyChartObj.destroy();

        if (!hourlyData || hourlyData.length === 0) {
            ctx.canvas.parentElement.innerHTML = '<div style="text-align:center; padding: 2rem; color:var(--dsh-muted); font-size:0.8rem;">Belum ada data performa 24 jam untuk kampanye ini pada hari ini.</div><canvas id="campaignHourlyChartCanvas" style="display:none;"></canvas>';
            return;
        }

        const labels = Array.from({ length: 24 }, (_, i) => `${String(i).padStart(2, '0')}:00`);
        const clicks = new Array(24).fill(0);
        const spend = new Array(24).fill(0);
        const gmv = new Array(24).fill(0);

        hourlyData.forEach(row => {
            if (row.time !== undefined && row.time >= 0 && row.time <= 23) {
                clicks[row.time] = row.click || row.clicks || 0;
                spend[row.time] = row.expense || row.spend || 0;
                gmv[row.time] = row.broad_gmv || row.gmv || 0;
            }
        });

        campaignHourlyChartObj = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'GMV (Rp)',
                        data: gmv,
                        borderColor: 'rgba(16, 185, 129, 0.9)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        yAxisID: 'y1',
                        tension: 0.4,
                        borderDash: [5, 5]
                    },
                    {
                        label: 'Klik',
                        data: clicks,
                        borderColor: 'rgba(59, 130, 246, 0.9)',
                        backgroundColor: 'transparent',
                        type: 'bar',
                        yAxisID: 'y',
                        order: 2
                    },
                    {
                        label: 'Biaya (Rp)',
                        data: spend,
                        borderColor: 'rgba(239, 68, 68, 0.9)',
                        backgroundColor: 'rgba(239, 68, 68, 0.2)',
                        fill: true,
                        yAxisID: 'y1',
                        tension: 0.4,
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        grid: { color: document.body.getAttribute('data-theme') === 'dark' ? '#334155' : '#e2e8f0', drawBorder: false },
                        ticks: { color: document.body.getAttribute('data-theme') === 'dark' ? '#94a3b8' : '#64748b', font: { size: 10 } }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Trafik (Klik)', color: '#3b82f6', font: { size: 10 } },
                        grid: { display: false },
                        ticks: { color: document.body.getAttribute('data-theme') === 'dark' ? '#94a3b8' : '#64748b', font: { size: 10 } }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Finansial (Rp)', color: '#10b981', font: { size: 10 } },
                        grid: { color: document.body.getAttribute('data-theme') === 'dark' ? '#334155' : '#e2e8f0' },
                        ticks: {
                            color: document.body.getAttribute('data-theme') === 'dark' ? '#94a3b8' : '#64748b',
                            font: { size: 10 },
                            callback: function (v) { return formatShortIDR(v); }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: document.body.getAttribute('data-theme') === 'dark' ? '#f8fafc' : '#334155', font: { size: 11, family: 'Inter, sans-serif' }, boxWidth: 12 }
                    },
                    tooltip: {
                        backgroundColor: document.body.getAttribute('data-theme') === 'dark' ? 'rgba(15, 23, 42, 0.9)' : 'rgba(255, 255, 255, 0.95)',
                        titleColor: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#0f172a',
                        bodyColor: document.body.getAttribute('data-theme') === 'dark' ? '#cbd5e1' : '#334155',
                        borderColor: document.body.getAttribute('data-theme') === 'dark' ? '#334155' : '#e2e8f0',
                        borderWidth: 1,
                        padding: 10,
                        callbacks: {
                            label: function (c) {
                                if (c.dataset.yAxisID === 'y1') return c.dataset.label + ': Rp ' + c.raw.toLocaleString('id-ID');
                                return c.dataset.label + ': ' + c.raw.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }

    window.openCampaignHourlyModal = function (campaignId, campaignName) {
        const modalEl = document.getElementById('modalCampaignHourly');
        const loaderEl = document.getElementById('campaignHourlyLoader');
        const contentEl = document.getElementById('campaignHourlyContent');
        const subtitleEl = document.getElementById('campaignHourlySubtitle');
        const chartRoute = routes.campaignHourly || '/marketplace/ads-dashboard/campaign-hourly';
        const storeId = getStoreId();

        if (!modalEl || !loaderEl || !contentEl || !subtitleEl) return;

        subtitleEl.innerHTML = `Kampanye: <span style="color:var(--dsh-primary);">${campaignName}</span>`;
        loaderEl.style.display = 'block';
        contentEl.style.display = 'none';

        if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        fetch(`${chartRoute}?store_id=${encodeURIComponent(storeId)}&campaign_id=${encodeURIComponent(campaignId)}`)
            .then(res => res.json())
            .then(data => {
                loaderEl.style.display = 'none';
                if (data.status === 'success') {
                    contentEl.style.display = 'block';
                    renderCampaignHourlyChart(data.data.campaign_list?.[0]?.daily_performance || []);
                } else {
                    loaderEl.style.display = 'block';
                    loaderEl.innerHTML = `<i class="bi bi-x-circle text-danger fs-3"></i><br>Gagal menarik data performa: ${data.message || 'Error API'}`;
                }
            })
            .catch(() => {
                loaderEl.style.display = 'block';
                loaderEl.innerHTML = '<i class="bi bi-wifi-off text-danger fs-3"></i><br>Koneksi terputus.';
            });
    };

    window.toggleGmsItem = function (itemId, action, btn) {
        const route = routes.gmsItemAction || '/marketplace/ads-dashboard/gms-item-action';
        if (!confirm('Apakah Anda yakin ingin ' + (action === 'add' ? 'mengaktifkan' : 'mematikan') + ' GMS untuk item ini?')) return;

        const originalHtml = btn.innerHTML;
        setBusyButton(btn, '<i class="bi bi-arrow-repeat spin-icon" style="display:inline-block"></i> Proses...');

        fetch(route, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                store_id: getStoreId(),
                item_id: itemId,
                action: action
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                getToast(res.message);
                if (action === 'remove') {
                    btn.className = 'btn btn-sm btn-outline-success';
                    btn.innerHTML = '<i class="bi bi-play-circle"></i> Aktifkan GMS';
                    btn.setAttribute('onclick', `toggleGmsItem('${itemId}', 'add', this)`);
                } else {
                    btn.className = 'btn btn-sm btn-outline-danger';
                    btn.innerHTML = '<i class="bi bi-pause-circle"></i> Hentikan GMS';
                    btn.setAttribute('onclick', `toggleGmsItem('${itemId}', 'remove', this)`);
                }
            } else {
                getToast('Error: ' + (res.message || 'Unknown error'));
                btn.innerHTML = originalHtml;
            }
        })
        .catch(() => {
            getToast('Terjadi kesalahan koneksi.');
            btn.innerHTML = originalHtml;
        })
        .finally(() => {
            btn.disabled = false;
        });
    };

    window.openGmsSettingsForProduct = function (campaignId) {
        const el = document.getElementById('gmsCampaignId');
        if (el) el.value = campaignId || '';
        const tabBtn = document.querySelector('.dash-tab-m[data-target="tab-campaigns"]');
        if (tabBtn) tabBtn.click();
        try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (e) {}
    };

    window.submitInlineGms = function (channelItemId, campaignId, storeId, btnElement) {
        const route = routes.gmsCampaignEdit || '/marketplace/ads-dashboard/gms-campaign-edit';
        const budgetInput = document.getElementById('inlineBudget_' + channelItemId)?.value;
        const roasInput = document.getElementById('inlineRoas_' + channelItemId)?.value;

        if (!budgetInput && !roasInput) {
            getToast('Harap isi minimal salah satu pengaturan (Budget atau ROAS).');
            return;
        }

        const originalHtml = btnElement.innerHTML;
        setBusyButton(btnElement, '<i class="bi bi-arrow-repeat spin-icon"></i>');

        fetch(route, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                store_id: storeId,
                daily_budget: budgetInput,
                roas_target: roasInput,
                campaign_id: campaignId || null
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                btnElement.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
                setTimeout(() => {
                    btnElement.innerHTML = originalHtml;
                    btnElement.disabled = false;
                }, 1200);
            } else {
                getToast(res.message || 'Gagal menyimpan pengaturan.');
                btnElement.innerHTML = originalHtml;
                btnElement.disabled = false;
            }
        })
        .catch(() => {
            getToast('Terjadi kesalahan koneksi.');
            btnElement.innerHTML = originalHtml;
            btnElement.disabled = false;
        });
    };

    window.submitInlineCpc = function (channelItemId, campaignId, storeId, btnElement) {
        const route = routes.cpcCampaignEdit || '/marketplace/ads-dashboard/cpc-campaign-edit';
        const budgetInput = document.getElementById('inlineBudget_' + channelItemId)?.value;
        const roasInput = document.getElementById('inlineRoas_' + channelItemId)?.value;

        if (!budgetInput && !roasInput) {
            getToast('Harap isi minimal salah satu pengaturan (Budget atau ROAS) untuk kampanye CPC.');
            return;
        }

        if (!campaignId) {
            getToast('ID Kampanye CPC tidak ditemukan.');
            return;
        }

        const originalHtml = btnElement.innerHTML;
        setBusyButton(btnElement, '<i class="bi bi-arrow-repeat spin-icon"></i>');

        fetch(route, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                store_id: storeId,
                daily_budget: budgetInput,
                roas_target: roasInput,
                campaign_id: campaignId
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                btnElement.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
                setTimeout(() => {
                    btnElement.innerHTML = originalHtml;
                    btnElement.disabled = false;
                }, 1200);
            } else {
                getToast(res.message || 'Gagal menyimpan pengaturan CPC.');
                btnElement.innerHTML = originalHtml;
                btnElement.disabled = false;
            }
        })
        .catch(() => {
            getToast('Terjadi kesalahan koneksi.');
            btnElement.innerHTML = originalHtml;
            btnElement.disabled = false;
        });
    };

    window.submitGmsSettings = function (e) {
        e.preventDefault();

        const route = routes.gmsCampaignEdit || '/marketplace/ads-dashboard/gms-campaign-edit';
        const btn = document.getElementById('btnSubmitGmsSettings');
        const storeId = document.getElementById('gmsStoreId')?.value || getStoreId();
        const dailyBudget = document.getElementById('gmsDailyBudget')?.value || null;
        const roasTarget = document.getElementById('gmsRoasTarget')?.value;
        const campaignId = document.getElementById('gmsCampaignId')?.value;

        if (!storeId || storeId === 'all') {
            getToast('Pilih satu toko terlebih dahulu untuk mengubah Target ROAS.');
            return;
        }

        if (!dailyBudget && !roasTarget) {
            getToast('Harap isi minimal salah satu pengaturan (Budget atau ROAS).');
            return;
        }

        const originalHtml = btn ? btn.innerHTML : '';
        setBusyButton(btn, '<i class="bi bi-arrow-repeat spin-icon" style="display:inline-block"></i> Menyimpan...');

        fetch(route, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                store_id: storeId,
                daily_budget: dailyBudget,
                roas_target: roasTarget,
                campaign_id: campaignId || null
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                getToast(res.message);
                setTimeout(() => window.location.reload(), 900);
            } else {
                getToast('Error: ' + (res.message || 'Unknown error'));
            }
        })
        .catch(() => {
            getToast('Terjadi kesalahan koneksi.');
        })
        .finally(() => {
            setBusyButton(btn, originalHtml, false);
        });
    };

    window.showInlineEdit = function (el) {
        const wrapper = el.closest('.inline-edit-wrapper');
        if (!wrapper) return;

        wrapper.querySelector('.inline-edit-text').style.display = 'none';
        const inputContainer = wrapper.querySelector('.inline-edit-input');
        inputContainer.style.display = 'inline-flex';
        const input = inputContainer.querySelector('input');
        const focusAndSelect = function () {
            input.focus({ preventScroll: true });
            if (typeof input.setSelectionRange === 'function') {
                input.setSelectionRange(0, input.value.length);
            } else {
                input.select();
            }
        };
        focusAndSelect();
        // Tunggu satu frame agar browser tidak mengembalikan caret ke posisi lama.
        window.requestAnimationFrame ? window.requestAnimationFrame(focusAndSelect) : setTimeout(focusAndSelect, 0);
        input.onfocus = function () {
            setTimeout(focusAndSelect, 0);
        };
        input.onclick = function (e) {
            e.stopPropagation();
        };
        input.onkeydown = function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                window.saveInlineEdit(inputContainer.querySelector('.text-success'));
            } else if (e.key === 'Escape') {
                e.preventDefault();
                const cancelControl = inputContainer.querySelector('.text-secondary, .text-danger');
                if (cancelControl) window.cancelInlineEdit(cancelControl);
            }
        };
    };

    window.cancelInlineEdit = function (el) {
        const wrapper = el.closest('.inline-edit-wrapper');
        if (!wrapper) return;

        wrapper.querySelector('.inline-edit-input').style.display = 'none';
        wrapper.querySelector('.inline-edit-text').style.display = 'inline';
        const input = wrapper.querySelector('input');
        input.value = wrapper.getAttribute('data-val');
    };

    window.saveInlineEdit = function (el) {
        const wrapper = el.closest('.inline-edit-wrapper');
        if (!wrapper) return;

        const input = wrapper.querySelector('input');
        const type = wrapper.getAttribute('data-type');
        const newVal = type === 'roas_target' ? normalizeDecimal(input.value) : input.value;
        const oldVal = wrapper.getAttribute('data-val');

        if (type === 'roas_target' && (newVal === '' || !Number.isFinite(Number(newVal)) || Number(newVal) < 0)) {
            getToast('Target ROAS harus berupa angka 0 atau lebih. Contoh: 5,5');
            input.focus();
            input.select();
            return;
        }

        if (newVal === oldVal) {
            window.cancelInlineEdit(el);
            return;
        }

        const campId = wrapper.getAttribute('data-camp');
        const storeId = getStoreId();
        const campaignKind = wrapper.getAttribute('data-campaign-kind');
        const isGms = campaignKind === 'gms' || String(campId || '').startsWith('GMS-');
        const route = isGms
            ? (routes.gmsCampaignEdit || '/marketplace/ads-dashboard/gms-campaign-edit')
            : (routes.cpcCampaignEdit || '/marketplace/ads-dashboard/cpc-campaign-edit');

        if (storeId === 'all') {
            getToast('Mode Semua Toko — pilih satu toko dulu untuk aksi ini.');
            return;
        }

        const payload = {
            _token: getCsrfToken(),
            store_id: storeId,
            campaign_id: campId
        };
        if (type === 'daily_budget') {
            payload.daily_budget = newVal;
        } else if (type === 'roas_target') {
            payload.roas_target = newVal;
        }

        const originalContent = wrapper.querySelector('.inline-edit-text').innerHTML;
        wrapper.querySelector('.inline-edit-text').innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span> Menyimpan...';
        wrapper.querySelector('.inline-edit-input').style.display = 'none';
        wrapper.querySelector('.inline-edit-text').style.display = 'inline';

        fetch(route, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': payload._token
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                wrapper.setAttribute('data-val', newVal);
                let displayVal = '';
                if (type === 'daily_budget') {
                    const numVal = parseFloat(newVal);
                    displayVal = numVal > 0 ? 'Rp ' + numVal.toLocaleString('id-ID') : 'Unlimited';
                } else {
                    const numVal = parseFloat(newVal);
                    displayVal = numVal > 0 ? numVal.toFixed(2) + 'x' : 'Auto';
                }
                const targetLabel = wrapper.querySelector('.perf-target-roas .perf-target-label');
                if (targetLabel) {
                    wrapper.querySelector('.inline-edit-text').innerHTML =
                        '<span class="perf-target-label">' + (numVal > 0 ? 'Target' : 'Auto') + '</span>' +
                        '<strong>' + displayVal + '</strong>' +
                        '<i class="bi bi-pencil-square" style="font-size:.58rem;"></i>';
                } else {
                    wrapper.querySelector('.inline-edit-text').innerHTML = displayVal + ' <i class="bi bi-pencil-fill text-muted" style="font-size: .6rem; opacity: 0.5;"></i>';
                }
                const campaignRow = wrapper.closest('.perf-row');
                if (campaignRow) campaignRow.setAttribute('data-target_roas', newVal);
            } else {
                getToast('Gagal menyimpan: ' + (data.message || 'Unknown error'));
                wrapper.querySelector('.inline-edit-text').innerHTML = originalContent;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            getToast('Terjadi kesalahan saat menyimpan pengaturan.');
            wrapper.querySelector('.inline-edit-text').innerHTML = originalContent;
        });
    };

    window.toggleCampaignStatus = function (el, campId, action) {
        const route = routes.cpcCampaignEdit || '/marketplace/ads-dashboard/cpc-campaign-edit';
        if (!confirm('Apakah Anda yakin ingin ' + (action === 'pause' ? 'menjeda (pause)' : 'melanjutkan (resume)') + ' kampanye ini?')) {
            return;
        }

        const storeId = getStoreId();
        if (storeId === 'all') {
            getToast('Mode Semua Toko — pilih satu toko dulu untuk aksi ini.');
            return;
        }

        const payload = {
            _token: getCsrfToken(),
            store_id: storeId,
            campaign_id: campId,
            status_action: action
        };

        const originalClass = el.className;
        el.className = 'spinner-border spinner-border-sm text-primary';
        el.style.opacity = '1';

        fetch(route, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': payload._token
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                window.location.reload();
            } else {
                getToast('Gagal mengubah status: ' + (data.message || 'Unknown error'));
                el.className = originalClass;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            getToast('Terjadi kesalahan sistem.');
            el.className = originalClass;
        });
    };

    document.querySelectorAll('.inner-tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const container = this.closest('.dash-tabs-modern');
            if (!container) return;

            container.querySelectorAll('.inner-tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const targetId = this.getAttribute('data-inner-target');
            const parentPane = this.closest('.tab-pane');
            if (!parentPane) return;

            parentPane.querySelectorAll('.inner-tab-pane').forEach(pane => {
                pane.style.display = 'none';
                pane.classList.remove('active');
            });

            const targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.style.display = 'block';
                setTimeout(() => targetPane.classList.add('active'), 10);
            }
        });
    });
})();
