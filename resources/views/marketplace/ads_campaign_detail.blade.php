@extends('layouts.app')

@section('title', 'Detail Campaign Iklan')

@push('head')
@include('dashboard.partials._styles')
<style>
    .campaign-detail-page { --cd-border:#e2e8f0; --cd-muted:#64748b; --cd-ink:#0f172a; background:#f8fafc; min-height:calc(100vh - 70px); }
    .campaign-detail-page .cd-shell { max-width:1400px; margin:0 auto; padding:1.25rem; }
    .cd-card { background:#fff; border:1px solid var(--cd-border); border-radius:16px; box-shadow:0 4px 18px rgba(15,23,42,.05); }
    .cd-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1.25rem; margin-bottom:1rem; }
    .cd-eyebrow { color:#2563eb; font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
    .cd-title { color:var(--cd-ink); font-size:1.35rem; font-weight:850; margin:.25rem 0 .35rem; }
    .cd-subtitle { color:var(--cd-muted); font-size:.76rem; }
    .cd-grid { display:grid; grid-template-columns:minmax(0,1.45fr) minmax(340px,.85fr); gap:1rem; align-items:start; }
    .cd-section { padding:1rem; }
    .cd-section-title { color:var(--cd-ink); font-size:.9rem; font-weight:850; margin-bottom:.15rem; }
    .cd-section-note { color:var(--cd-muted); font-size:.7rem; line-height:1.45; margin-bottom:.8rem; }
    .cd-metrics { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.6rem; }
    .cd-metric { padding:.75rem; border:1px solid var(--cd-border); border-radius:12px; background:#fff; }
    .cd-metric-label { color:var(--cd-muted); font-size:.65rem; }
    .cd-metric-value { color:var(--cd-ink); font-size:1rem; font-weight:850; margin-top:.2rem; }
    .cd-setting-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; }
    .cd-setting { padding:.7rem; border-radius:11px; background:#f8fafc; border:1px solid #eef2f7; }
    .cd-setting-label { color:var(--cd-muted); font-size:.64rem; }
    .cd-setting-value { color:var(--cd-ink); font-size:.78rem; font-weight:800; margin-top:.15rem; word-break:break-word; }
    .cd-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; }
    .cd-form-grid .full { grid-column:1 / -1; }
    .cd-label { display:block; color:var(--cd-ink); font-size:.68rem; font-weight:800; margin-bottom:.25rem; }
    .cd-help { color:var(--cd-muted); font-size:.64rem; line-height:1.4; margin-top:.25rem; }
    .cd-form .form-control, .cd-form .form-select { border-radius:9px; font-size:.75rem; }
    .cd-form .form-control:focus, .cd-form .form-select:focus { border-color:#93c5fd; box-shadow:0 0 0 .18rem rgba(37,99,235,.1); }
    .cd-badge { display:inline-flex; align-items:center; gap:.25rem; border-radius:999px; padding:.25rem .55rem; font-size:.64rem; font-weight:800; }
    .cd-badge-blue { color:#1d4ed8; background:#dbeafe; }
    .cd-badge-gray { color:#475569; background:#f1f5f9; }
    .cd-table { width:100%; border-collapse:collapse; font-size:.72rem; }
    .cd-table th { color:var(--cd-muted); font-size:.63rem; text-transform:uppercase; letter-spacing:.03em; text-align:left; padding:.6rem .5rem; border-bottom:1px solid var(--cd-border); white-space:nowrap; }
    .cd-table td { color:var(--cd-ink); padding:.65rem .5rem; border-bottom:1px solid #f1f5f9; vertical-align:top; }
    .cd-table .text-end { text-align:right; }
    .cd-table-wrap { overflow:auto; }
    .cd-actions { display:flex; gap:.4rem; flex-wrap:wrap; }
    .cd-actions .btn { border-radius:9px; font-size:.72rem; font-weight:800; }
    .cd-divider { border:0; border-top:1px solid var(--cd-border); margin:1rem 0; }
    .cd-empty { color:var(--cd-muted); font-size:.72rem; padding:1.3rem .4rem; text-align:center; }
    .cd-alert { display:none; border-radius:10px; padding:.65rem .75rem; font-size:.7rem; font-weight:700; margin-bottom:.75rem; }
    .cd-alert.error { color:#b91c1c; background:#fef2f2; border:1px solid #fecaca; }
    .cd-alert.success { color:#166534; background:#f0fdf4; border:1px solid #bbf7d0; }
    .cd-checks { display:flex; flex-wrap:wrap; gap:.55rem .8rem; padding:.6rem .7rem; background:#f8fafc; border:1px solid #eef2f7; border-radius:10px; }
    .cd-checks label { display:flex; align-items:center; gap:.3rem; color:var(--cd-muted); font-size:.66rem; }
    @media (max-width: 992px) { .cd-grid { grid-template-columns:1fr; } }
    @media (max-width: 640px) { .campaign-detail-page .cd-shell { padding:.75rem; } .cd-header { flex-direction:column; } .cd-metrics { grid-template-columns:repeat(2,minmax(0,1fr)); } .cd-form-grid,.cd-setting-grid { grid-template-columns:1fr; } .cd-form-grid .full { grid-column:auto; } }
</style>
@endpush

@section('content')
@php
    $campaignId = (string) $campaign->channel_campaign_id;
    $campaignName = $campaign->campaign_name ?: 'Campaign ' . $campaignId;
    $isGms = str_starts_with(strtoupper($campaignId), 'GMS-');
@endphp
<div class="campaign-detail-page">
    <div class="cd-shell">
        <div class="cd-card cd-header">
            <div>
                <a href="{{ $backUrl }}" class="text-decoration-none" style="font-size:.72rem;font-weight:800;color:#2563eb;"><i class="bi bi-arrow-left"></i> Kembali ke Ads Dashboard</a>
                <div class="cd-eyebrow mt-3">Marketplace / Campaign Detail</div>
                <h1 class="cd-title">{{ $campaignName }}</h1>
                <div class="cd-subtitle">
                    {{ $campaign->store?->name ?: 'Toko' }} · ID <code>{{ $campaignId }}</code>
                    @if($campaign->campaign_status)<span class="cd-badge cd-badge-blue ms-1">{{ $campaign->campaign_status }}</span>@endif
                    @if($isGms)<span class="cd-badge cd-badge-gray ms-1">GMV Max</span>@endif
                </div>
            </div>
            <div class="cd-actions">
                <a href="#quick-edit" class="btn btn-primary"><i class="bi bi-sliders2"></i> Edit campaign</a>
                <a href="#record-change" class="btn btn-outline-primary"><i class="bi bi-clipboard2-pulse"></i> Catat perubahan</a>
            </div>
        </div>

        <div id="detailAlert" class="cd-alert"></div>

        <div class="cd-grid">
            <main>
                <section class="cd-card cd-section mb-3">
                    <div class="cd-section-title">Performa campaign</div>
                    <div class="cd-section-note">Data mengikuti periode dashboard. Hari ini belum dihitung sebagai observation evaluasi.</div>
                    <div id="performanceLoading" class="cd-empty">Memuat performa...</div>
                    <div id="performanceMetrics" class="cd-metrics" style="display:none;"></div>
                </section>

                <section class="cd-card cd-section mb-3" id="record-change">
                    <div class="cd-section-title"><i class="bi bi-clipboard2-pulse text-primary"></i> Catat perubahan campaign</div>
                    <div class="cd-section-note">Mode lokal saja: perubahan dicatat ke database dan menjadi baseline evaluasi, tanpa mengirim request ke Seller Center.</div>
                    <div id="recordAlert" class="cd-alert"></div>
                    <form id="recordChangeForm" class="cd-form">
                        <input type="hidden" name="store_id" value="{{ $storeId }}">
                        <input type="hidden" name="channel_campaign_id" value="{{ $campaignId }}">
                        <input type="hidden" name="channel_item_id" value="{{ $campaign->channel_item_id }}">
                        <div class="cd-form-grid">
                            <div>
                                <label class="cd-label" for="recordType">Jenis perubahan</label>
                                <select id="recordType" name="change_type" class="form-select" required>
                                    <option value="creative">Ganti foto / creative</option>
                                    <option value="target_roas">Update Target ROAS</option>
                                    <option value="price">Update harga</option>
                                    <option value="budget">Update budget</option>
                                    <option value="title">Update judul / deskripsi</option>
                                    <option value="other">Perubahan lainnya</option>
                                </select>
                            </div>
                            <div id="recordModelWrap" style="display:none;">
                                <label class="cd-label" for="recordModelId">Varian / model</label>
                                <select id="recordModelId" class="form-select">
                                    @if($product?->models?->isNotEmpty())
                                        @foreach($product->models as $model)
                                            <option value="{{ $model->model_id }}" data-price="{{ $model->price }}">{{ $model->model_name ?: 'Model ' . $model->model_id }} · Rp {{ number_format((float) $model->price, 0, ',', '.') }}</option>
                                        @endforeach
                                    @else
                                        <option value="0">Produk belum tersinkron</option>
                                    @endif
                                </select>
                            </div>
                            <div>
                                <label class="cd-label" for="recordChangedAt">Waktu perubahan</label>
                                <input id="recordChangedAt" name="changed_at" type="datetime-local" class="form-control" required>
                            </div>
                            <div>
                                <label class="cd-label" for="recordBefore">Sebelum</label>
                                <input id="recordBefore" name="before_value" class="form-control" required>
                            </div>
                            <div>
                                <label class="cd-label" for="recordAfter">Sesudah</label>
                                <input id="recordAfter" name="after_value" class="form-control" required>
                            </div>
                            <div>
                                <label class="cd-label" for="recordObjective">Tujuan perubahan</label>
                                <input id="recordObjective" name="objective" class="form-control" required placeholder="Contoh: menaikkan CTR">
                            </div>
                            <div>
                                <label class="cd-label" for="recordKpi">KPI utama</label>
                                <select id="recordKpi" name="primary_kpi" class="form-select" required>
                                    <option value="roas">ROAS</option>
                                    <option value="acos">ACOS</option>
                                    <option value="orders">Pesanan</option>
                                    <option value="cvr">CVR</option>
                                    <option value="profit">Profit</option>
                                    <option value="gmv">GMV</option>
                                    <option value="ctr">CTR</option>
                                </select>
                            </div>
                            <div class="full">
                                <label class="cd-label" for="recordNotes">Catatan <span style="font-weight:500;color:var(--cd-muted);">(opsional)</span></label>
                                <textarea id="recordNotes" name="notes" class="form-control" rows="2" maxlength="2000" placeholder="Apa yang sengaja tidak diubah selama evaluasi?"></textarea>
                            </div>
                            <div class="full">
                                <div class="cd-label">Perubahan bersamaan / confounder</div>
                                <div class="cd-checks">
                                    <label><input type="checkbox" name="confounders[]" value="creative_changed"> Foto / creative lain</label>
                                    <label><input type="checkbox" name="confounders[]" value="target_roas_changed"> Target ROAS</label>
                                    <label><input type="checkbox" name="confounders[]" value="price_changed"> Harga</label>
                                    <label><input type="checkbox" name="confounders[]" value="budget_changed"> Budget</label>
                                    <label><input type="checkbox" name="confounders[]" value="title_changed"> Judul / deskripsi</label>
                                </div>
                            </div>
                        </div>
                        <div id="recordApplyNote" class="cd-help mt-2">Tidak ada perubahan yang dikirim ke Shopee. Form ini hanya mencatat rencana/perubahan ke Evaluasi lokal.</div>
                        <div class="d-flex justify-content-end mt-3">
                            <button id="recordSubmit" class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Catat lokal</button>
                        </div>
                    </form>
                </section>

                <section class="cd-card cd-section">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <div>
                            <div class="cd-section-title">Riwayat evaluasi campaign</div>
                            <div class="cd-section-note mb-0">Semua perubahan yang tercatat untuk campaign ini.</div>
                        </div>
                        <button type="button" id="reloadEvaluations" class="btn btn-sm btn-light" style="font-size:.68rem;"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
                    </div>
                    <div id="evaluationLoading" class="cd-empty" style="display:none;">Memuat evaluasi...</div>
                    <div id="evaluationTableWrap" class="cd-table-wrap" style="display:none;"><table class="cd-table"><thead><tr><th>Perubahan</th><th>Status</th><th>Sebelum → Sesudah</th><th>Waktu</th><th>Tujuan</th></tr></thead><tbody id="evaluationBody"></tbody></table></div>
                    <div id="evaluationEmpty" class="cd-empty" style="display:none;">Belum ada perubahan yang tercatat untuk campaign ini.</div>
                </section>
            </main>

            <aside>
                <section class="cd-card cd-section mb-3" id="quick-edit">
                    <div class="cd-section-title"><i class="bi bi-sliders2 text-primary"></i> Edit cepat campaign</div>
                    <div class="cd-section-note">Mode lokal saja. Perubahan tidak dikirim ke Shopee; hanya dicatat ke Evaluasi lokal. Simpan satu perubahan per klik.</div>
                    <div id="quickAlert" class="cd-alert"></div>
                    <form id="quickEditForm" class="cd-form">
                        <div class="mb-2">
                            <label class="cd-label" for="quickRoas">Target ROAS</label>
                            <input id="quickRoas" name="roas_target" type="number" min="0" step="0.01" class="form-control" placeholder="Kosongkan jika tidak diubah">
                            <div class="cd-help">Isi 0 untuk Auto / Maksimalkan GMV.</div>
                        </div>
                        <div class="mb-2">
                            <label class="cd-label" for="quickBudget">Budget harian</label>
                            <input id="quickBudget" name="daily_budget" type="number" min="0" step="1" class="form-control" placeholder="Kosongkan jika tidak diubah">
                            <div class="cd-help">Isi 0 untuk tidak terbatas.</div>
                        </div>
                        <div class="mb-3">
                            <label class="cd-label" for="quickStatus">Status campaign</label>
                            <select id="quickStatus" name="status_action" class="form-select">
                                <option value="">Tidak diubah</option>
                                <option value="pause">Jeda campaign</option>
                                <option value="resume">Lanjutkan campaign</option>
                                <option value="stop">Hentikan campaign</option>
                            </select>
                        </div>
                        <button id="quickSubmit" class="btn btn-primary w-100" type="submit"><i class="bi bi-cloud-check"></i> Simpan perubahan</button>
                    </form>
                </section>

                <section class="cd-card cd-section">
                    <div class="cd-section-title">Setting tersimpan</div>
                    <div class="cd-section-note">Setting lokal terakhir yang tersinkron dari marketplace.</div>
                    <div id="settingGrid" class="cd-setting-grid">
                        <div class="cd-setting"><div class="cd-setting-label">Campaign</div><div class="cd-setting-value">{{ $campaignName }}</div></div>
                        <div class="cd-setting"><div class="cd-setting-label">Item</div><div class="cd-setting-value">{{ $campaign->internalItem?->name ?: ($campaign->channel_item_id ?: '—') }}</div></div>
                        <div class="cd-setting"><div class="cd-setting-label">Target ROAS</div><div class="cd-setting-value" id="settingRoas">{{ $campaign->target_roas !== null ? number_format((float) $campaign->target_roas, 2, ',', '.') . 'x' : 'Auto / —' }}</div></div>
                        <div class="cd-setting"><div class="cd-setting-label">Budget harian</div><div class="cd-setting-value" id="settingBudget">{{ $campaign->campaign_budget > 0 ? 'Rp ' . number_format((float) $campaign->campaign_budget, 0, ',', '.') : 'Tidak terbatas / —' }}</div></div>
                        <div class="cd-setting"><div class="cd-setting-label">Bidding</div><div class="cd-setting-value" id="settingBidding">{{ $campaign->bidding_method ?: '—' }}</div></div>
                        <div class="cd-setting"><div class="cd-setting-label">Placement</div><div class="cd-setting-value" id="settingPlacement">{{ $campaign->campaign_placement ?: '—' }}</div></div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const campaignId = @json($campaignId);
    const storeId = @json($storeId);
    const channelItemId = @json($campaign->channel_item_id);
    const detailUrl = @json(url('/api/marketplace/ad-campaigns/' . $campaign->id . '/detail'));
    const evaluationsUrl = @json(route('marketplace.ads.experiments.index'));
    const recordUrl = @json(route('marketplace.ads.evaluations.store'));
    const backUrl = @json($backUrl);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const detailFrom = @json($dateFrom);
    const detailTo = @json($dateTo);

    const $ = id => document.getElementById(id);
    const money = value => value == null ? '—' : 'Rp ' + Number(value).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    const number = value => value == null ? '—' : Number(value).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    const roas = value => value == null ? '—' : Number(value).toFixed(2).replace('.', ',') + 'x';
    const pct = value => value == null ? '—' : Number(value).toFixed(2).replace('.', ',') + '%';
    const esc = value => String(value ?? '').replace(/[&<>'"]/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#039;', '"':'&quot;' }[char]));
    const changeLabels = { creative:'Foto / Creative', target_roas:'Target ROAS', price:'Harga', budget:'Budget', title:'Judul / Deskripsi', other:'Lainnya', price_and_target_roas:'Harga + Target ROAS' };
    const changeHints = {
        creative: ['Deskripsi/foto lama', 'Deskripsi atau URL foto baru', 'ctr'],
        target_roas: ['Target ROAS sebelumnya', 'Target ROAS baru', 'roas'],
        price: ['Harga sebelum', 'Harga sesudah', 'profit'],
        budget: ['Budget sebelum', 'Budget sesudah', 'roas'],
        title: ['Judul lama', 'Judul baru', 'ctr'],
        other: ['Nilai/kondisi sebelum', 'Nilai/kondisi sesudah', 'roas'],
    };

    function showAlert(id, message, type = 'error') {
        const el = $(id);
        if (!el) return;
        el.textContent = message || '';
        el.className = 'cd-alert ' + type;
        el.style.display = message ? 'block' : 'none';
    }

    function requestJson(url, options = {}) {
        return fetch(url, Object.assign({ credentials:'same-origin', headers:{ Accept:'application/json' } }, options))
            .then(async response => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Permintaan gagal diproses.');
                return data;
            });
    }

    function renderPerformance(data) {
        const p = data.performance || {};
        const s = data.setting || {};
        $('performanceLoading').style.display = 'none';
        $('performanceMetrics').style.display = 'grid';
        const metrics = [
            ['Belanja iklan', money(p.spend)], ['Broad GMV', money(p.broad_gmv)], ['Broad ROAS', roas(p.broad_roas)], ['Pesanan', number(p.broad_order)],
            ['Impresi', number(p.impressions)], ['Klik', number(p.clicks)], ['CTR', pct(p.ctr)], ['CVR', pct(p.broad_cvr)],
        ];
        $('performanceMetrics').innerHTML = metrics.map(item => '<div class="cd-metric"><div class="cd-metric-label">' + item[0] + '</div><div class="cd-metric-value">' + item[1] + '</div></div>').join('');
        $('quickRoas').value = s.target_roas == null ? '' : s.target_roas;
        $('quickRoas').dataset.current = s.target_roas == null ? '' : s.target_roas;
        $('quickBudget').value = s.campaign_budget == null ? '' : s.campaign_budget;
        $('quickBudget').dataset.current = s.campaign_budget == null ? '' : s.campaign_budget;
        $('quickStatus').dataset.current = String(s.campaign_status || s.status || '').toLowerCase();
        $('settingRoas').textContent = s.target_roas != null && Number(s.target_roas) > 0 ? roas(s.target_roas) : 'Auto / —';
        $('settingBudget').textContent = s.campaign_budget != null && Number(s.campaign_budget) > 0 ? money(s.campaign_budget) : 'Tidak terbatas / —';
        $('settingBidding').textContent = s.bidding_method || '—';
        $('settingPlacement').textContent = s.campaign_placement || '—';
        const beforeMap = { target_roas: s.target_roas == null ? 'Auto' : s.target_roas, budget: s.campaign_budget == null ? '0' : s.campaign_budget };
        $('recordType').dataset.beforeTargetRoas = beforeMap.target_roas;
        $('recordType').dataset.beforeBudget = beforeMap.budget;
        syncRecordType();
    }

    function syncRecordType() {
        const type = $('recordType').value;
        const hint = changeHints[type] || changeHints.other;
        $('recordBefore').placeholder = hint[0];
        $('recordAfter').placeholder = hint[1];
        $('recordKpi').value = hint[2];
        $('recordModelWrap').style.display = type === 'price' ? 'block' : 'none';
        const model = $('recordModelId').selectedOptions?.[0];
        if (type === 'price' && model && !$('recordBefore').value) $('recordBefore').value = model.dataset.price || '';
        if (type === 'target_roas' && !$('recordBefore').value) $('recordBefore').value = $('recordType').dataset.beforeTargetRoas || '';
        if (type === 'budget' && !$('recordBefore').value) $('recordBefore').value = $('recordType').dataset.beforeBudget || '';
        const notes = {
            creative: 'Perubahan foto/creative hanya dicatat sebagai evaluasi lokal.',
            price: 'Perubahan harga hanya dicatat sebagai evaluasi lokal.',
            title: 'Perubahan judul/deskripsi hanya dicatat sebagai evaluasi lokal.',
            target_roas: 'Perubahan Target ROAS hanya dicatat sebagai evaluasi lokal.',
            budget: 'Perubahan budget hanya dicatat sebagai evaluasi lokal.',
            other: 'Perubahan ini hanya dicatat sebagai evaluasi lokal.',
        };
        $('recordApplyNote').textContent = notes[type] || notes.other;
        $('recordSubmit').innerHTML = '<i class="bi bi-save"></i> Catat lokal';
    }

    function loadDetail() {
        $('performanceLoading').style.display = 'block';
        return requestJson(detailUrl + '?date_from=' + encodeURIComponent(detailFrom) + '&date_to=' + encodeURIComponent(detailTo))
            .then(renderPerformance)
            .catch(error => { $('performanceLoading').textContent = error.message; showAlert('detailAlert', error.message); });
    }

    function loadEvaluations() {
        $('evaluationLoading').style.display = 'block';
        $('evaluationEmpty').style.display = 'none';
        $('evaluationTableWrap').style.display = 'none';
        const params = new URLSearchParams({ store_id: storeId, channel_campaign_id: campaignId, limit: '100' });
        return requestJson(evaluationsUrl + '?' + params.toString()).then(payload => {
            const rows = payload.data || [];
            $('evaluationLoading').style.display = 'none';
            if (!rows.length) { $('evaluationEmpty').style.display = 'block'; return; }
            $('evaluationTableWrap').style.display = 'block';
            $('evaluationBody').innerHTML = rows.map(row => {
                const exp = row.experiment || {}, details = row.details || {}, plan = exp.calculation_snapshot || {};
                const status = details.lifecycle_status || exp.lifecycle_status || 'LEARNING';
                const changedAt = exp.changed_at ? new Date(exp.changed_at).toLocaleString('id-ID') : '—';
                return '<tr><td><b>' + esc(changeLabels[exp.change_type] || exp.change_type || 'Perubahan') + '</b><br><span class="text-muted">#' + esc(exp.id) + '</span></td>' +
                    '<td><span class="cd-badge cd-badge-blue">' + esc(status.replaceAll('_', ' ')) + '</span></td>' +
                    '<td>' + esc(plan.before || exp.old_price || exp.old_target_roas || '—') + ' → ' + esc(plan.after || exp.new_price || exp.new_target_roas || '—') + '</td>' +
                    '<td>' + esc(changedAt) + '</td><td>' + esc(plan.objective || '—') + '</td></tr>';
            }).join('');
        }).catch(error => { $('evaluationLoading').style.display = 'none'; showAlert('detailAlert', error.message); });
    }

    $('quickEditForm').addEventListener('submit', function (event) {
        event.preventDefault();
        showAlert('quickAlert', '');
        const formElement = event.currentTarget;
        const form = new FormData(formElement);
        const roasInput = form.get('roas_target') || '';
        const budgetInput = form.get('daily_budget') || '';
        const changedFields = [];

        if (String(roasInput).trim() !== '' && numericChangeValue(roasInput) !== numericChangeValue($('quickRoas').dataset.current || '')) {
            changedFields.push('roas_target');
        }
        if (String(budgetInput).trim() !== '' && numericChangeValue(budgetInput) !== numericChangeValue($('quickBudget').dataset.current || '')) {
            changedFields.push('daily_budget');
        }
        if (form.get('status_action')) {
            changedFields.push('status_action');
        }

        if (changedFields.length > 1) {
            return showAlert('quickAlert', 'Simpan satu perubahan per klik agar tidak memicu rate limit marketplace.');
        }

        const payload = {
            store_id: storeId,
            campaign_id: campaignId,
            channel_campaign_id: campaignId,
            channel_item_id: channelItemId == null ? '' : String(channelItemId),
            change_type: changedFields[0] === 'roas_target' ? 'target_roas' : changedFields[0] === 'daily_budget' ? 'budget' : 'other',
            before_value: changedFields[0] === 'roas_target'
                ? ($('quickRoas').dataset.current || 'Auto')
                : changedFields[0] === 'daily_budget'
                    ? ($('quickBudget').dataset.current || '0')
                    : ($('quickStatus').dataset.current || 'unknown'),
            after_value: changedFields[0] === 'roas_target' ? roasInput : changedFields[0] === 'daily_budget' ? budgetInput : form.get('status_action'),
            changed_at: new Date().toISOString(),
            objective: changedFields[0] === 'status_action' ? 'Mencatat perubahan status campaign' : 'Mencatat perubahan setting campaign',
            primary_kpi: changedFields[0] === 'daily_budget' ? 'roas' : 'roas',
            notes: 'Mode lokal saja; belum diterapkan ke Shopee.',
            confounders: [],
        };
        if (!changedFields.length) return showAlert('quickAlert', 'Belum ada perubahan baru pada campaign.');
        const button = $('quickSubmit'); button.disabled = true; button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
        recordManual(payload)
            .then(data => {
                showAlert('quickAlert', data.message || 'Perubahan berhasil dicatat secara lokal.', 'success');
                $('quickRoas').value = $('quickRoas').dataset.current || '';
                $('quickBudget').value = $('quickBudget').dataset.current || '';
                $('quickStatus').value = '';
                loadEvaluations();
            })
            .catch(error => showAlert('quickAlert', error.message))
            .finally(() => { button.disabled = false; button.innerHTML = '<i class="bi bi-save"></i> Catat lokal'; });
    });

    function recordManual(payload) {
        return requestJson(recordUrl, { method:'POST', headers:{ Accept:'application/json', 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf }, body:JSON.stringify(payload) });
    }

    function numericChangeValue(value) {
        const raw = String(value ?? '').trim();
        if (/^(auto|maksimalkan gmv)$/i.test(raw)) return '0';
        return raw.replace(/[^0-9.,-]/g, '').replace(/\.(?=.*\.)/g, '').replace(',', '.');
    }

    function applyRecordedChange(payload) {
        return recordManual(payload);
    }

    $('recordChangeForm').addEventListener('submit', function (event) {
        event.preventDefault();
        showAlert('recordAlert', '');
        const formElement = event.currentTarget;
        const payload = {};
        new FormData(formElement).forEach((value, key) => {
            if (key === 'confounders[]') { payload.confounders = payload.confounders || []; payload.confounders.push(value); } else payload[key] = value;
        });
        const button = $('recordSubmit'); button.disabled = true; button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
        applyRecordedChange(payload)
            .then(data => { showAlert('recordAlert', data.message || 'Perubahan berhasil diterapkan dan masuk ke Evaluasi.', 'success'); formElement.reset(); setChangedAt(); syncRecordType(); loadDetail(); loadEvaluations(); })
            .catch(error => showAlert('recordAlert', error.message))
            .finally(() => { button.disabled = false; syncRecordType(); });
    });

    function setChangedAt() { const input = $('recordChangedAt'); const now = new Date(); now.setMinutes(now.getMinutes() - now.getTimezoneOffset()); input.value = now.toISOString().slice(0, 16); }
    $('reloadEvaluations').addEventListener('click', loadEvaluations);
    $('recordType').addEventListener('change', syncRecordType);
    $('recordModelId').addEventListener('change', () => { if ($('recordType').value === 'price') $('recordBefore').value = $('recordModelId').selectedOptions?.[0]?.dataset.price || ''; });
    setChangedAt();
    syncRecordType();
    loadDetail();
    loadEvaluations();
})();
</script>
@endpush
