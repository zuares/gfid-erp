{{--
    TAB SYNC (interaktif) — pusat kendali sinkronisasi ads:
    aksi cepat, progress backfill, antrean per tahap dengan countdown hidup,
    konsol log langsung, dan riwayat run. Polling adaptif: 5 dtk saat ada
    proses aktif, 15 dtk saat idle; countdown di-update tiap detik.
--}}
<div class="ads-tab-panel mb-3">
    <div class="ads-tab-panel-head">
        <div>
            <div class="ads-tab-panel-title"><i class="bi bi-arrow-repeat" style="color: var(--dsh-accent);"></i> Sinkronisasi</div>
            <div class="ads-tab-panel-note">Aksi cepat, antrean, dan riwayat sync.</div>
        </div>
    </div>
    <div class="p-3">
        <div style="display:flex; flex-wrap:wrap; gap:.45rem;">
            <button type="button" class="btn fw-bold" onclick="window.__syncQuick('incremental', this)" style="background: var(--dsh-accent); color:#fff; border-radius:10px; font-size:.75rem; padding:.42rem .8rem;">
                <i class="bi bi-lightning-charge"></i> Cepat
            </button>
            <button type="button" class="btn fw-bold" onclick="window.__syncQuick('hourly', this)" style="border:1px solid var(--dsh-border); color:var(--text); border-radius:10px; font-size:.75rem; padding:.42rem .8rem;">
                <i class="bi bi-clock-history"></i> Heatmap
            </button>
            <button type="button" class="btn fw-bold" data-bs-toggle="modal" data-bs-target="#modalSyncAds" style="border:1px solid var(--dsh-border); color:var(--text); border-radius:10px; font-size:.75rem; padding:.42rem .8rem;">
                <i class="bi bi-cloud-download"></i> Manual
            </button>
            <button type="button" class="btn fw-bold" id="btnCancelQueue" onclick="window.__syncCancelQueue(this)" style="display:none; border:1px solid rgba(220,38,38,.4); color:#dc2626; border-radius:10px; font-size:.75rem; padding:.42rem .8rem;">
                <i class="bi bi-x-circle"></i> Batal
            </button>
            <button type="button" class="btn fw-bold" onclick="window.__syncGapFix(this)" style="border:1px solid rgba(22,163,74,.35); color:#15803d; border-radius:10px; font-size:.75rem; padding:.42rem .8rem;" title="Audit 90 hari & perbaiki tanggal yang datanya hilang">
                <i class="bi bi-bandaid"></i> Audit Bolong
            </button>
            <button type="button" class="btn fw-bold" id="btnSyncNotif" onclick="window.__syncToggleNotif(this)" style="margin-left:auto; border:1px solid var(--dsh-border); color:var(--dsh-muted); border-radius:10px; font-size:.75rem; padding:.42rem .8rem;" title="Notifikasi browser saat proses selesai/gagal (berguna saat tab ditinggal)">
                <i class="bi bi-bell-slash"></i>
            </button>
            <button type="button" class="btn fw-bold" onclick="window.__syncRefresh(this)" style="border:1px solid var(--dsh-border); color:var(--dsh-muted); border-radius:10px; font-size:.75rem; padding:.42rem .8rem;">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>
</div>

<!-- ═══ PANEL 1: STATUS — semua kondisi sistem dalam satu kartu ═══ -->
<div class="dpanel ads-tab-panel mb-3" style="padding:1rem;">
    <div style="display:flex; flex-wrap:wrap; gap:.4rem; align-items:center;">
        <span id="cardCooldown" style="display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .7rem; border-radius:8px; border:1px solid rgba(22,163,74,.25); background:rgba(22,163,74,.05); font-size:.72rem;" title="Status pembatasan API Shopee">
            <i class="bi bi-shield-check" style="color:var(--dsh-muted);"></i>
            <b id="cooldownValue" style="color:#15803d; font-weight:800;">Siap menerima sync</b>
            <span id="cooldownHint" style="display:none;"></span>
        </span>
        <span style="display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .7rem; border-radius:8px; border:1px solid var(--dsh-border); font-size:.72rem;" title="Job sync yang menunggu di antrean">
            <i class="bi bi-stack" style="color:#1d4ed8;"></i>
            <b id="pendingJobsValue" style="color:#1d4ed8; font-weight:800;">0</b>
            <span style="color:var(--dsh-muted);">antre</span>
        </span>
        <span style="display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .7rem; border-radius:8px; border:1px solid var(--dsh-border); font-size:.72rem;" title="Sync sukses terakhir">
            <i class="bi bi-check2-circle" style="color:#15803d;"></i>
            <b id="lastSuccessValue" style="color:var(--text); font-weight:750;">-</b>
            <b id="lastSuccessRel" style="color:#16a34a; font-weight:700;"></b>
            <span id="lastSuccessHint" style="display:none;"></span>
        </span>
        <span style="margin-left:auto; font-size:.64rem; color:var(--dsh-muted);"><i class="bi bi-broadcast"></i> refresh <span id="syncPollInfo">15 dtk</span> &bull; <span id="syncTabLastCheck">-</span></span>
    </div>

    <!-- Progress (muncul hanya saat ada proses berjalan) -->
    <div id="backfillProgressWrap" style="display:none; margin-top:.75rem;">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span style="font-size:.74rem; font-weight:700; color:var(--text);"><i class="bi bi-cloud-download"></i> <span id="backfillProgressLabel">Sinkronisasi berjalan&hellip;</span></span>
            <span id="backfillProgressPct" style="font-size:.72rem; font-weight:800; color:#1d4ed8;"></span>
        </div>
        <div class="progress" style="height:8px; border-radius:99px; background:var(--dsh-border); overflow:hidden;">
            <div id="backfillProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%; background:#3b82f6; transition: width .6s ease;"></div>
        </div>
    </div>

    <!-- Kelengkapan data 60 hari -->
    <div id="coverageWrap" style="display:none; margin-top:.75rem;">
        <div class="d-flex justify-content-between align-items-center" style="margin-bottom:.35rem;">
            <span style="font-size:.68rem; font-weight:700; color:var(--dsh-muted);"><i class="bi bi-calendar-check"></i> KELENGKAPAN 60 HARI
                <span style="font-weight:500; text-transform:none;">&mdash; <span style="color:#16a34a;">&#9632;</span> ada &bull; <span style="color:#dc2626;">&#9632;</span> bolong &bull; <span style="color:#3b82f6;">&#9632;</span> hari ini</span>
            </span>
            <span id="coverageSummary" style="font-size:.68rem; font-weight:700;"></span>
        </div>
        <div id="coverageStrip" style="display:flex; gap:2px; flex-wrap:wrap;"></div>
    </div>

    <div id="skippedStoresNote" style="display:none; margin-top:.6rem; font-size:.64rem; color:var(--dsh-muted);">
        <i class="bi bi-eye-slash"></i> Dilewati (toko nonaktif): <b id="skippedStoresList" style="color:var(--text);"></b>
    </div>
</div>

<!-- ═══ PANEL 2: PROSES — antrean + log dalam satu kartu ═══ -->
<div class="dpanel ads-tab-panel mb-3" style="padding:1rem;">
    <div class="d-flex justify-content-between align-items-center" style="margin-bottom:.5rem;">
        <span style="font-size:.72rem; font-weight:700; color:var(--text);"><i class="bi bi-activity"></i> Proses Berjalan</span>
        <button type="button" onclick="window.__syncClearConsole()" style="border:none; background:none; color:var(--dsh-muted); font-size:.64rem; cursor:pointer;"><i class="bi bi-eraser"></i> bersihkan log</button>
    </div>

    <div id="queueListWrap" style="display:none; margin-bottom:.6rem;">
        <div style="font-size:.66rem; font-weight:700; color:var(--dsh-muted); margin-bottom:.35rem;"><i class="bi bi-list-ol"></i> ANTREAN <span id="queueCount" style="font-weight:600;"></span></div>
        <div id="queueList" style="display:grid; gap:.35rem;"></div>
    </div>

    <div id="syncConsole" style="background:rgba(0,0,0,.45); border:1px solid var(--dsh-border); border-radius:8px; padding:10px; font-family:ui-monospace,monospace; font-size:.72rem; color:#a1a1aa; height:140px; overflow-y:auto; line-height:1.55;"><div style="color:#4ade80;">&gt; Memantau aktivitas sync&hellip;</div></div>
</div>

<!-- Tabel riwayat + filter status -->
<div id="runFilterChips" style="display:flex; gap:.35rem; flex-wrap:wrap; margin-bottom:.75rem;">
    <button type="button" class="run-chip active" data-filter="all">Semua</button>
    <button type="button" class="run-chip" data-filter="success">Sukses</button>
    <button type="button" class="run-chip" data-filter="processing">Proses</button>
    <button type="button" class="run-chip" data-filter="rate_limited">Rate Limit</button>
    <button type="button" class="run-chip" data-filter="error">Error</button>
    <span style="margin-left:auto; font-size:.64rem; color:var(--dsh-muted); align-self:center;">Rate Limit = menunggu &amp; retry otomatis &bull; baris error bisa diklik</span>
</div>
<style>
#runFilterChips .run-chip { border:1px solid var(--dsh-border); background:transparent; border-radius:999px; padding:.22rem .7rem; font-size:.68rem; font-weight:700; color:var(--dsh-muted); cursor:pointer; transition:all .15s; }
#runFilterChips .run-chip:hover { border-color: var(--dsh-accent); color: var(--dsh-accent); }
#runFilterChips .run-chip.active { background: var(--dsh-accent); border-color: var(--dsh-accent); color:#fff; }
#runFilterChips .run-chip[data-filter="success"].active { background:#16a34a; border-color:#16a34a; }
#runFilterChips .run-chip[data-filter="processing"].active { background:#3b82f6; border-color:#3b82f6; }
#runFilterChips .run-chip[data-filter="rate_limited"].active { background:#d97706; border-color:#d97706; }
#runFilterChips .run-chip[data-filter="error"].active { background:#dc2626; border-color:#dc2626; }
#runFilterChips .run-chip[data-filter="success"] { color:#15803d; border-color:rgba(22,163,74,.35); }
#runFilterChips .run-chip[data-filter="processing"] { color:#1d4ed8; border-color:rgba(59,130,246,.35); }
#runFilterChips .run-chip[data-filter="rate_limited"] { color:#b45309; border-color:rgba(245,158,11,.4); }
#runFilterChips .run-chip[data-filter="error"] { color:#b91c1c; border-color:rgba(220,38,38,.35); }
</style>
<div class="ads-tab-panel" style="overflow:hidden;">
    <div class="table-responsive">
        <table class="dpanel-table dpanel-table-sm" style="white-space: nowrap;">
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Jenis</th>
                <th>Rentang Data</th>
                <th>Status</th>
                <th class="text-end">Durasi</th>
                <th class="text-end">Req / Baris</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody id="syncRunsBody">
            @if(isset($syncRuns) && $syncRuns->count() > 0)
                @foreach($syncRuns as $run)
                    <tr>
                        <td style="font-family: ui-monospace, monospace; font-size:.72rem; color: var(--text);">{{ $run->started_at?->format('d/m H:i:s') ?? $run->created_at->format('d/m H:i:s') }}</td>
                        <td style="font-size:.72rem; color: var(--dsh-muted);">{{ $run->sync_type }}</td>
                        <td style="font-size:.72rem; color: var(--dsh-muted);">{{ $run->date_from?->format('d/m/Y') }} &ndash; {{ $run->date_to?->format('d/m/Y') }}</td>
                        <td>
                            @php
                                $c = match($run->status) { 'success' => ['#15803d','rgba(22,163,74,.12)'], 'processing' => ['#1d4ed8','rgba(59,130,246,.12)'], 'rate_limited' => ['#b45309','rgba(245,158,11,.14)'], default => ['#b91c1c','rgba(220,38,38,.12)'] };
                            @endphp
                            <span style="padding:.2rem .55rem; border-radius:999px; font-size:.64rem; font-weight:700; color: {{ $c[0] }}; background: {{ $c[1] }};">{{ strtoupper($run->status) }}</span>
                        </td>
                        <td class="text-end" style="font-family: ui-monospace, monospace; font-size:.72rem; color: var(--text);">{{ ($run->started_at && $run->finished_at) ? abs($run->finished_at->diffInSeconds($run->started_at)) . 's' : '-' }}</td>
                        <td class="text-end" style="font-family: ui-monospace, monospace; font-size:.72rem; color: var(--text);">{{ $run->total_requests }} / <span style="color:#16a34a;">{{ $run->total_updated }}</span></td>
                        <td style="font-size:.7rem; color: var(--dsh-muted); max-width:240px; overflow:hidden; text-overflow:ellipsis;" title="{{ $run->error_message }}">{{ $run->error_message ?: '-' }}</td>
                    </tr>
                @endforeach
            @else
                <tr><td colspan="7" class="text-center py-4" style="color: var(--dsh-muted); font-size:.8rem;">Belum ada riwayat sinkronisasi.</td></tr>
            @endif
        </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    const storeId = @json(($storeId ?? null) === 'all' ? null : ($storeId ?? null));
    const badge = document.getElementById('tabSyncBadge');
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

    let syncTabTimer = null;
    let pollMs = 15000;
    let lastData = null;
    let lastFetchAt = 0;

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));

    // ── Konsol log langsung ──
    const seenRuns = {};
    const seenJobs = {};
    let consoleFirstLoad = true;
    let runFilter = 'all';
    let notifOn = false;
    try { notifOn = localStorage.getItem('adsSyncNotif') === '1' && typeof Notification !== 'undefined' && Notification.permission === 'granted'; } catch (e) {}

    function maybeNotify(title, body) {
        // Notifikasi hanya saat user tidak sedang melihat halaman — di situlah gunanya.
        if (!notifOn || typeof Notification === 'undefined' || Notification.permission !== 'granted' || !document.hidden) return;
        try { new Notification(title, { body: body, icon: '/favicon.ico' }); } catch (e) {}
    }

    function paintNotifBtn() {
        const b = document.getElementById('btnSyncNotif');
        if (!b) return;
        b.innerHTML = notifOn ? '<i class="bi bi-bell-fill" style="color:#f59e0b;"></i>' : '<i class="bi bi-bell-slash"></i>';
        b.style.borderColor = notifOn ? 'rgba(245,158,11,.5)' : 'var(--dsh-border)';
    }

    window.__syncToggleNotif = async function (btn) {
        if (notifOn) {
            notifOn = false;
            try { localStorage.setItem('adsSyncNotif', '0'); } catch (e) {}
            consoleLog('notifikasi browser dimatikan.', '#a1a1aa');
        } else {
            if (typeof Notification === 'undefined') { consoleLog('browser ini tidak mendukung notifikasi.', '#f87171'); return; }
            let perm = Notification.permission;
            if (perm === 'default') perm = await Notification.requestPermission();
            if (perm !== 'granted') { consoleLog('izin notifikasi ditolak browser.', '#facc15'); return; }
            notifOn = true;
            try { localStorage.setItem('adsSyncNotif', '1'); } catch (e) {}
            consoleLog('notifikasi browser aktif — kamu akan diberi tahu saat proses selesai/gagal walau tab ditinggal.', '#4ade80');
        }
        paintNotifBtn();
    };

    window.__syncGapFix = async function (btn) {
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat spin-icon" style="display:inline-block;"></i> Mengantrekan…';
        try {
            const res = await fetch('/marketplace/ads-dashboard/gap-fix', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok) consoleLog('audit bolong: ' + (data.message || 'diantrekan.'), '#4ade80');
            else consoleLog('gagal antre audit (' + res.status + '): ' + (data.message || ''), '#f87171');
        } catch (e) {
            consoleLog('Gagal terhubung: ' + e.message, '#f87171');
        }
        btn.disabled = false;
        btn.innerHTML = orig;
        refreshSyncTab();
    };

    function renderCoverage() {
        const wrap = document.getElementById('coverageWrap');
        const strip = document.getElementById('coverageStrip');
        const sum = document.getElementById('coverageSummary');
        const cov = (lastData && lastData.coverage) || [];
        if (!cov.length) { wrap.style.display = 'none'; return; }
        wrap.style.display = 'block';
        const todayKey = cov[cov.length - 1] ? cov[cov.length - 1].d : null;
        let missing = 0;
        strip.innerHTML = cov.map(c => {
            const isToday = c.d === todayKey;
            let bg = c.ok ? '#16a34a' : '#dc2626';
            if (isToday) bg = '#3b82f6';
            if (!c.ok && !isToday) missing++;
            const tgl = c.d.split('-').reverse().join('/');
            return '<div title="' + tgl + (isToday ? ' — hari ini' : (c.ok ? ' — ada data' : ' — BOLONG')) + '" style="width:10px; height:16px; border-radius:2px; background:' + bg + '; opacity:' + (c.ok || isToday ? '1' : '.9') + '; cursor:default;"></div>';
        }).join('');
        if (missing > 0) {
            sum.style.color = '#dc2626';
            sum.innerText = missing + ' hari bolong';
        } else {
            sum.style.color = '#15803d';
            sum.innerText = 'lengkap ✓';
        }
    }

    function renderRuns() {
        const body = document.getElementById('syncRunsBody');
        const all = (lastData && lastData.runs) || [];
        const runs = runFilter === 'all' ? all : all.filter(r => r.status === runFilter);
        if (!all.length) return;
        if (!runs.length) {
            body.innerHTML = '<tr><td colspan="7" class="text-center py-4" style="color:var(--dsh-muted); font-size:.8rem;">Tidak ada riwayat berstatus ini.</td></tr>';
            return;
        }
        body.innerHTML = runs.map(r => {
            const [fg, bg] = statusStyle(r.status);
            const clickable = r.error ? ' data-err="' + esc(r.error) + '" style="cursor:pointer;"' : '';
            return '<tr' + clickable + '>'
                + '<td style="font-family:ui-monospace,monospace; font-size:.72rem; color:var(--text);">' + esc(r.started_at || '-') + '</td>'
                + '<td style="font-size:.72rem; color:var(--dsh-muted);">' + esc(r.type) + '</td>'
                + '<td style="font-size:.72rem; color:var(--dsh-muted);">' + esc(r.date_from || '-') + ' – ' + esc(r.date_to || '-') + '</td>'
                + '<td><span style="padding:.2rem .55rem; border-radius:999px; font-size:.64rem; font-weight:700; color:' + fg + '; background:' + bg + ';">' + esc((r.status || '').toUpperCase()) + '</span></td>'
                + '<td class="text-end" style="font-family:ui-monospace,monospace; font-size:.72rem; color:var(--text);">' + (r.duration !== null && r.duration !== undefined ? esc(r.duration) + 's' : '-') + '</td>'
                + '<td class="text-end" style="font-family:ui-monospace,monospace; font-size:.72rem; color:var(--text);">' + esc(r.requests) + ' / <span style="color:#16a34a;">' + esc(r.updated) + '</span></td>'
                + '<td style="font-size:.7rem; color:var(--dsh-muted); max-width:240px; overflow:hidden; text-overflow:ellipsis;">' + esc(r.error ? (r.error.length > 60 ? r.error.slice(0, 60) + '… (klik untuk detail)' : r.error) : '-') + '</td>'
                + '</tr>';
        }).join('');
    }

    document.getElementById('runFilterChips').addEventListener('click', (e) => {
        const chip = e.target.closest('.run-chip');
        if (!chip) return;
        document.querySelectorAll('#runFilterChips .run-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        runFilter = chip.dataset.filter;
        renderRuns();
    });

    function consoleLog(msg, color) {
        const box = document.getElementById('syncConsole');
        if (!box) return;
        const now = new Date();
        const ts = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0') + ':' + String(now.getSeconds()).padStart(2,'0');
        const div = document.createElement('div');
        div.style.color = color || '#a1a1aa';
        div.textContent = '> [' + ts + '] ' + msg;
        box.appendChild(div);
        while (box.children.length > 80) box.removeChild(box.firstChild);
        box.scrollTop = box.scrollHeight;
    }

    window.__syncClearConsole = function () {
        const box = document.getElementById('syncConsole');
        box.innerHTML = '';
        consoleLog('konsol dibersihkan.', '#4ade80');
    };

    const statusStyle = (s) => ({
        success:      ['#15803d', 'rgba(22,163,74,.12)'],
        processing:   ['#1d4ed8', 'rgba(59,130,246,.12)'],
        rate_limited: ['#b45309', 'rgba(245,158,11,.14)'],
    }[s] || ['#b91c1c', 'rgba(220,38,38,.12)']);

    // ── Render antrean (dipanggil saat refresh & tiap detik oleh ticker) ──
    function renderQueue() {
        const qWrap = document.getElementById('queueListWrap');
        const qList = document.getElementById('queueList');
        const qCount = document.getElementById('queueCount');
        const cancelBtn = document.getElementById('btnCancelQueue');
        const queue = (lastData && lastData.queue) || [];
        const elapsed = Math.floor((Date.now() - lastFetchAt) / 1000);

        cancelBtn.style.display = queue.length ? 'inline-block' : 'none';

        if (!queue.length) { qWrap.style.display = 'none'; return; }
        qWrap.style.display = 'block';
        qCount.innerText = '(' + queue.length + ' tahap)';
        qList.innerHTML = queue.map((j, i) => {
            const rentang = (j.hourly ? 'Hourly ' : 'Daily ') + esc(j.date_from || '?') + ' – ' + esc(j.date_to || '?');
            const sisa = Math.max(0, (j.available_in || 0) - elapsed);
            let icon, text, color;
            if (j.running) {
                icon = '<i class="bi bi-arrow-repeat spin-icon" style="display:inline-block;"></i>';
                text = 'Sedang diproses'; color = '#1d4ed8';
            } else if (sisa > 90) {
                icon = '<i class="bi bi-hourglass-split"></i>';
                text = 'Menunggu retry ±' + Math.ceil(sisa / 60) + ' mnt (rate limit)'; color = '#b45309';
            } else if (sisa > 0) {
                icon = '<i class="bi bi-clock"></i>';
                text = 'Menunggu giliran (±' + sisa + ' dtk)'; color = 'var(--dsh-muted)';
            } else {
                icon = '<i class="bi bi-clock"></i>';
                text = 'Siap — menunggu worker'; color = 'var(--dsh-muted)';
            }
            const attempt = j.attempts > 1 ? ' • percobaan ke-' + j.attempts : '';
            return '<div style="display:flex; align-items:center; justify-content:space-between; gap:.6rem; padding:.4rem .6rem; border:1px solid var(--dsh-border); border-radius:8px; font-size:.72rem;">'
                + '<span style="font-weight:700; color:var(--text);">' + (i + 1) + '. ' + rentang + '</span>'
                + '<span style="color:' + color + '; font-weight:600; white-space:nowrap;">' + icon + ' ' + text + esc(attempt) + '</span>'
                + '</div>';
        }).join('');
    }

    // ── Render kartu cooldown (refresh + ticker) ──
    function renderCooldown() {
        if (!lastData) return;
        const card = document.getElementById('cardCooldown');
        const val = document.getElementById('cooldownValue');
        const hint = document.getElementById('cooldownHint');
        const elapsed = Math.floor((Date.now() - lastFetchAt) / 1000);
        const sisa = Math.max(0, (lastData.cooldown_seconds || 0) - elapsed);
        if (sisa > 0) {
            card.style.borderColor = 'rgba(245,158,11,.35)';
            card.style.background = 'rgba(245,158,11,.06)';
            val.style.color = '#b45309';
            val.innerText = 'Rate limit — tunggu ' + Math.floor(sisa / 60) + ':' + String(sisa % 60).padStart(2, '0');
            hint.innerText = 'Semua sync otomatis menunda diri sampai jendela ini berlalu';
        } else {
            card.style.borderColor = 'rgba(22,163,74,.25)';
            card.style.background = 'rgba(22,163,74,.05)';
            val.style.color = '#15803d';
            val.innerText = 'Siap menerima sync';
            hint.innerText = 'Tidak ada pembatasan (rate limit) yang aktif';
        }
    }

    async function refreshSyncTab() {
        try {
            const res = await fetch('/marketplace/ads-dashboard/sync-status' + (storeId ? ('?store_id=' + storeId) : ''), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            lastData = data;
            lastFetchAt = Date.now();

            document.getElementById('syncTabLastCheck').innerText = data.server_time || '-';
            document.getElementById('pendingJobsValue').innerText = data.pending_ads_jobs ?? 0;

            renderCooldown();
            renderQueue();

            // Kartu sukses terakhir (+ simpan ts untuk waktu relatif berdetak)
            const lastOk = (data.runs || []).find(r => r.status === 'success');
            if (lastOk) {
                document.getElementById('lastSuccessValue').innerText = lastOk.started_at || '-';
                document.getElementById('lastSuccessHint').innerText = lastOk.type + ' • ' + (lastOk.updated ?? 0) + ' baris diperbarui';
                window.__lastOkTs = lastOk.ts || null;
            }

            renderCoverage();

            // Progress eksplisit dari endpoint background sync
            const bg = data.progress;
            if (bg && bg.status === 'queued') {
                const wrap  = document.getElementById('backfillProgressWrap');
                const label = document.getElementById('backfillProgressLabel');
                const pctEl = document.getElementById('backfillProgressPct');
                const barEl = document.getElementById('backfillProgressBar');
                wrap.style.display = 'block';
                label.innerText = bg.label || 'Sync sedang antre…';
                pctEl.innerText = bg.percent ? bg.percent + '%' : '';
                barEl.style.width = Math.max(4, bg.percent || 3) + '%';
                barEl.classList.add('progress-bar-animated');
                barEl.style.background = '#3b82f6';
            }

            // Progress bar
            const wrap  = document.getElementById('backfillProgressWrap');
            const label = document.getElementById('backfillProgressLabel');
            const pctEl = document.getElementById('backfillProgressPct');
            const barEl = document.getElementById('backfillProgressBar');
            const bf = data.backfill;
            if (bf && bf.total > 0) {
                wrap.style.display = 'block';
                barEl.style.width = Math.max(4, bf.percent) + '%';
                pctEl.innerText = bf.percent + '%';
                if (bf.percent >= 100) {
                    label.innerText = 'Backfill selesai — ' + bf.total + ' tahap beres';
                    barEl.classList.remove('progress-bar-animated');
                    barEl.style.background = '#16a34a';
                } else {
                    label.innerText = 'Backfill berjalan — tahap ' + Math.min(bf.done + 1, bf.total) + ' dari ' + bf.total
                        + (data.cooldown_seconds > 0 ? ' (menunggu rate limit)' : '');
                    barEl.classList.add('progress-bar-animated');
                    barEl.style.background = data.cooldown_seconds > 0 ? '#f59e0b' : '#3b82f6';
                }
            } else if ((data.processing || 0) > 0) {
                wrap.style.display = 'block';
                label.innerText = 'Sinkronisasi sedang berjalan…';
                pctEl.innerText = '';
                barEl.style.width = '100%';
                barEl.classList.add('progress-bar-animated');
                barEl.style.background = '#3b82f6';
            } else {
                wrap.style.display = 'none';
            }

            // Konsol: laporkan perubahan run
            (data.runs || []).slice().reverse().forEach(r => {
                const label2 = '#' + r.id + ' ' + r.type + ' ' + (r.date_from || '?') + '–' + (r.date_to || '?');
                const prev = seenRuns[r.id];
                if (prev === r.status) return;
                if (consoleFirstLoad && r.status !== 'processing') { seenRuns[r.id] = r.status; return; }
                if (prev === undefined && r.status === 'processing') consoleLog(label2 + ' dimulai…', '#60a5fa');
                else if (r.status === 'success') { consoleLog(label2 + ' SUCCESS — ' + r.requests + ' req, ' + r.updated + ' baris' + (r.duration !== null ? ', ' + r.duration + ' dtk' : ''), '#4ade80'); maybeNotify('✓ Sync selesai', r.type + ' ' + (r.date_from || '') + '–' + (r.date_to || '') + ' — ' + (r.updated ?? 0) + ' baris'); }
                else if (r.status === 'rate_limited') { consoleLog(label2 + ' RATE_LIMITED — menunggu & retry otomatis', '#facc15'); maybeNotify('⏳ Rate limit Shopee', 'Sync menunda diri dan akan mencoba lagi otomatis.'); }
                else if (r.status === 'error') { consoleLog(label2 + ' ERROR — ' + (r.error || 'lihat kolom keterangan'), '#f87171'); maybeNotify('✕ Sync gagal', (r.error || 'Lihat tab Sync untuk detail').slice(0, 90)); }
                else if (r.status === 'processing') consoleLog(label2 + ' sedang berjalan…', '#60a5fa');
                seenRuns[r.id] = r.status;
            });
            (data.queue || []).forEach(j => {
                if (j.running && !seenJobs[j.id] && !consoleFirstLoad) {
                    consoleLog('antrean: chunk ' + (j.date_from || '?') + '–' + (j.date_to || '?') + ' mulai diproses', '#60a5fa');
                }
                seenJobs[j.id] = j.running;
            });
            consoleFirstLoad = false;

            // Keterangan toko nonaktif
            const note = document.getElementById('skippedStoresNote');
            if (data.inactive_stores && data.inactive_stores.length) {
                document.getElementById('skippedStoresList').innerText = data.inactive_stores.join(', ');
                note.style.display = 'block';
            } else {
                note.style.display = 'none';
            }

            // Badge tab
            const active = (data.processing || 0) + (data.pending_ads_jobs || 0);
            if (badge) {
                badge.style.display = active > 0 ? 'inline-block' : 'none';
                badge.innerText = active;
            }

            // Tabel riwayat (baris error bisa diklik + filter chip)
            renderRuns();

            // Polling adaptif: 5 dtk saat aktif, 15 dtk saat idle
            const desired = ((data.queue || []).length > 0 || (data.processing || 0) > 0) ? 5000 : 15000;
            if (desired !== pollMs) {
                pollMs = desired;
                document.getElementById('syncPollInfo').innerText = (pollMs / 1000) + ' dtk';
                if (syncTabTimer) { clearInterval(syncTabTimer); syncTabTimer = setInterval(refreshSyncTab, pollMs); }
            }
        } catch (e) {
            console.error('sync-status refresh gagal:', e);
        }
    }

    // ── Aksi cepat ──
    window.__syncQuick = async function (type, btn) {
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat spin-icon" style="display:inline-block;"></i> Menjalankan…';
        consoleLog('perintah sync "' + type + '" dikirim — mohon tunggu…', '#60a5fa');
        try {
            const res = await fetch('/marketplace/ads-dashboard/sync', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ store_id: storeId, sync_type: type })
            });
            const data = await res.json().catch(() => ({}));
            if (res.status === 409) consoleLog(data.message || 'Sync otomatis sedang berjalan — coba lagi beberapa menit.', '#facc15');
            else if (!res.ok) consoleLog('Gagal (' + res.status + '): ' + (data.message || 'error tak dikenal'), '#f87171');
            else if (data.status === 'queued') consoleLog('sync "' + type + '" diantrekan — ' + (data.message || 'OK'), '#4ade80');
            else consoleLog('sync "' + type + '" selesai — ' + (data.message || 'OK'), '#4ade80');
        } catch (e) {
            consoleLog('Gagal terhubung: ' + e.message, '#f87171');
        }
        btn.disabled = false;
        btn.innerHTML = orig;
        refreshSyncTab();
    };

    // ── Batalkan antrean (dua langkah, tanpa dialog) ──
    let cancelArmed = false;
    let cancelDefault = null;
    window.__syncCancelQueue = async function (btn) {
        if (cancelDefault === null) cancelDefault = btn.innerHTML;
        if (!cancelArmed) {
            cancelArmed = true;
            btn.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Yakin? Klik sekali lagi';
            setTimeout(() => { cancelArmed = false; btn.innerHTML = cancelDefault; }, 4000);
            return;
        }
        cancelArmed = false;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat spin-icon" style="display:inline-block;"></i> Membatalkan…';
        try {
            const res = await fetch('/marketplace/ads-dashboard/queue-cancel', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() }
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok) consoleLog('antrean dibatalkan — ' + (data.message || 'OK'), '#facc15');
            else consoleLog('Gagal membatalkan (' + res.status + '): ' + (data.message || ''), '#f87171');
        } catch (e) {
            consoleLog('Gagal terhubung: ' + e.message, '#f87171');
        }
        btn.disabled = false;
        btn.innerHTML = cancelDefault;
        refreshSyncTab();
    };

    window.__syncRefresh = function (btn) {
        if (btn) {
            const i = btn.querySelector('i');
            if (i) { i.classList.add('spin-icon'); setTimeout(() => i.classList.remove('spin-icon'), 900); }
        }
        refreshSyncTab();
    };

    // ── Baris error di tabel bisa diklik untuk detail penuh ──
    document.getElementById('syncRunsBody').addEventListener('click', (e) => {
        const tr = e.target.closest('tr[data-err]');
        if (!tr) return;
        const next = tr.nextElementSibling;
        if (next && next.classList.contains('err-detail')) { next.remove(); return; }
        const d = document.createElement('tr');
        d.className = 'err-detail';
        const td = document.createElement('td');
        td.colSpan = 7;
        td.style.cssText = 'font-size:.7rem; color:#f87171; white-space:normal; background:rgba(220,38,38,.05); padding:.5rem .75rem;';
        td.textContent = tr.dataset.err;
        d.appendChild(td);
        tr.after(d);
    });

    // ── Ticker 1 detik: countdown antrean & cooldown hidup ──
    setInterval(() => {
        if (!lastData || document.hidden) return;
        renderQueue();
        renderCooldown();
        // Waktu relatif sukses terakhir
        const rel = document.getElementById('lastSuccessRel');
        if (rel && window.__lastOkTs) {
            const s = Math.max(0, Math.floor(Date.now() / 1000) - window.__lastOkTs);
            rel.innerText = s < 60 ? s + ' detik lalu'
                : s < 3600 ? Math.floor(s / 60) + ' menit lalu'
                : Math.floor(s / 3600) + ' jam ' + Math.floor((s % 3600) / 60) + ' mnt lalu';
        }
    }, 1000);

    // ── Polling ──
    function startPolling() {
        if (syncTabTimer) return;
        refreshSyncTab();
        syncTabTimer = setInterval(refreshSyncTab, pollMs);
    }
    function stopPolling() {
        if (syncTabTimer) { clearInterval(syncTabTimer); syncTabTimer = null; }
    }

    document.addEventListener('visibilitychange', () => document.hidden ? stopPolling() : startPolling());
    paintNotifBtn();
    startPolling();
})();
</script>
