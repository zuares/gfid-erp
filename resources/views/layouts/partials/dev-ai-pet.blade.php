@php
    $gfDevPetContext = [
        'page_title' => config('app.name', 'GFID'),
        'route' => request()->route()?->getName(),
        'path' => request()->path(),
        'url' => url()->current(),
    ];
@endphp

<style>
    .gf-dev-pet {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 1062;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
        transform: translate3d(var(--gf-dev-pet-shift-x, 0px), var(--gf-dev-pet-shift-y, 0px), 0);
        will-change: transform;
    }

    .gf-dev-pet.is-docked-left {
        left: 18px;
        right: auto;
    }

    .gf-dev-pet.is-docked-right {
        right: 18px;
        left: auto;
    }

    .gf-dev-pet.is-dragging {
        user-select: none;
    }

    .gf-dev-pet-launcher {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-height: 52px;
        padding: 8px 14px 8px 8px;
        border: 1px solid rgba(15, 23, 42, .10);
        border-radius: 18px;
        background: rgba(255, 255, 255, .94);
        box-shadow: 0 18px 40px rgba(15, 23, 42, .16);
        color: #0f172a;
        cursor: pointer;
        transition: transform .16s ease, box-shadow .16s ease, opacity .16s ease;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        touch-action: none;
    }

    .gf-dev-pet-launcher:hover {
        transform: translateY(-1px);
        box-shadow: 0 22px 48px rgba(15, 23, 42, .20);
        color: #0f172a;
    }

    .gf-dev-pet-launcher:focus-visible {
        outline: 3px solid rgba(37, 99, 235, .28);
        outline-offset: 3px;
    }

    .gf-dev-pet-orb {
        width: 38px;
        height: 38px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: linear-gradient(135deg, #0f172a 0%, #2563eb 56%, #38bdf8 100%);
        box-shadow: 0 10px 18px rgba(37, 99, 235, .28);
        animation: gfDevPetBob 4.8s ease-in-out infinite;
        flex: 0 0 auto;
    }

    .gf-dev-pet-copy {
        display: flex;
        flex-direction: column;
        line-height: 1.05;
        text-align: left;
    }

    .gf-dev-pet-copy strong {
        font-size: .83rem;
        font-weight: 900;
        letter-spacing: -.02em;
    }

    .gf-dev-pet-copy small {
        font-size: .68rem;
        color: #64748b;
        font-weight: 700;
    }

    .gf-dev-pet-panel {
        width: min(390px, calc(100vw - 24px));
        max-height: min(72vh, 640px);
        display: none;
        flex-direction: column;
        overflow: hidden;
        border-radius: 24px;
        border: 1px solid rgba(148, 163, 184, .22);
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 30px 70px rgba(15, 23, 42, .24);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
    }

    .gf-dev-pet.is-open .gf-dev-pet-panel {
        display: flex;
    }

    .gf-dev-pet-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        padding: 16px 16px 12px;
        border-bottom: 1px solid rgba(148, 163, 184, .16);
        cursor: grab;
        touch-action: none;
    }

    .gf-dev-pet-header-actions {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        flex: 0 0 auto;
    }

    .gf-dev-pet-title {
        font-size: .96rem;
        font-weight: 950;
        letter-spacing: -.03em;
        color: #0f172a;
    }

    .gf-dev-pet-subtitle {
        margin-top: 2px;
        font-size: .74rem;
        color: #64748b;
    }

    .gf-dev-pet-close {
        border: 0;
        background: transparent;
        color: #64748b;
        padding: 4px;
        width: 30px;
        height: 30px;
        border-radius: 10px;
        line-height: 1;
        font-size: 1.15rem;
        cursor: pointer;
    }

    .gf-dev-pet-close:hover {
        background: rgba(148, 163, 184, .12);
        color: #0f172a;
    }

    .gf-dev-pet.is-minimized .gf-dev-pet-body {
        display: none;
    }

    .gf-dev-pet.is-minimized .gf-dev-pet-panel {
        max-height: none;
    }

    .gf-dev-pet-body {
        display: flex;
        flex-direction: column;
        gap: 12px;
        padding: 14px 16px 16px;
        overflow: auto;
    }

    .gf-dev-pet-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .gf-dev-pet-chip {
        border: 1px solid rgba(37, 99, 235, .16);
        background: #eff6ff;
        color: #1d4ed8;
        border-radius: 999px;
        padding: .38rem .65rem;
        font-size: .72rem;
        font-weight: 800;
        cursor: pointer;
        transition: transform .14s ease, background .14s ease;
    }

    .gf-dev-pet-chip:hover {
        transform: translateY(-1px);
        background: #dbeafe;
    }

    .gf-dev-pet-thread {
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-height: 240px;
        overflow: auto;
        padding-right: 2px;
    }

    .gf-dev-pet-message {
        border-radius: 18px;
        padding: 10px 12px;
        border: 1px solid rgba(148, 163, 184, .16);
        font-size: .84rem;
        line-height: 1.55;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .gf-dev-pet-message.is-user {
        align-self: flex-end;
        max-width: 92%;
        background: #0f172a;
        color: #fff;
        border-color: #0f172a;
    }

    .gf-dev-pet-message.is-ai {
        align-self: flex-start;
        max-width: 100%;
        background: #f8fafc;
        color: #0f172a;
    }

    .gf-dev-pet-task {
        margin-top: 8px;
        padding: 10px 12px;
        border-radius: 16px;
        border: 1px solid rgba(37, 99, 235, .18);
        background: #eff6ff;
    }

    .gf-dev-pet-task-title {
        font-size: .82rem;
        font-weight: 900;
        color: #0f172a;
        margin-bottom: 4px;
    }

    .gf-dev-pet-task-summary {
        font-size: .78rem;
        color: #334155;
        margin-bottom: 8px;
    }

    .gf-dev-pet-task-list {
        margin: 0;
        padding-left: 1rem;
        font-size: .78rem;
        color: #1e293b;
    }

    .gf-dev-pet-form {
        display: grid;
        gap: 10px;
    }

    .gf-dev-pet-input {
        width: 100%;
        min-height: 92px;
        resize: vertical;
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, .28);
        background: #f8fafc;
        color: #0f172a;
        padding: .85rem .95rem;
        font-size: .88rem;
        line-height: 1.5;
    }

    .gf-dev-pet-input:focus {
        outline: none;
        border-color: rgba(37, 99, 235, .48);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
    }

    .gf-dev-pet-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .gf-dev-pet-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border-radius: 999px;
        padding: .48rem .78rem;
        font-size: .75rem;
        font-weight: 900;
        border: 1px solid rgba(15, 23, 42, .10);
        transition: transform .14s ease, box-shadow .14s ease, opacity .14s ease;
    }

    .gf-dev-pet-btn:hover {
        transform: translateY(-1px);
    }

    .gf-dev-pet-btn.is-primary {
        background: #0f172a;
        color: #fff;
        border-color: #0f172a;
        box-shadow: 0 12px 20px rgba(15, 23, 42, .16);
    }

    .gf-dev-pet-btn.is-secondary {
        background: #fff;
        color: #334155;
        border-color: #cbd5e1;
    }

    .gf-dev-pet-status {
        font-size: .72rem;
        color: #64748b;
    }

    .gf-dev-pet.is-busy .gf-dev-pet-launcher {
        opacity: .85;
    }

    .gf-dev-pet.is-dragging .gf-dev-pet-launcher,
    .gf-dev-pet.is-dragging .gf-dev-pet-header {
        cursor: grabbing;
    }

    @keyframes gfDevPetBob {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-2px); }
    }

    body[data-theme="dark"] .gf-dev-pet-launcher,
    body[data-theme="dark"] .gf-dev-pet-panel {
        background: rgba(15, 23, 42, .94);
        color: #e2e8f0;
        border-color: rgba(148, 163, 184, .18);
    }

    body[data-theme="dark"] .gf-dev-pet-title,
    body[data-theme="dark"] .gf-dev-pet-message.is-ai,
    body[data-theme="dark"] .gf-dev-pet-task-title {
        color: #e2e8f0;
    }

    body[data-theme="dark"] .gf-dev-pet-subtitle,
    body[data-theme="dark"] .gf-dev-pet-copy small,
    body[data-theme="dark"] .gf-dev-pet-status {
        color: #94a3b8;
    }

    body[data-theme="dark"] .gf-dev-pet-message.is-ai,
    body[data-theme="dark"] .gf-dev-pet-input {
        background: rgba(15, 23, 42, .82);
        border-color: rgba(148, 163, 184, .16);
    }

    body[data-theme="dark"] .gf-dev-pet-message.is-user {
        background: linear-gradient(135deg, #1d4ed8, #2563eb);
    }

    body[data-theme="dark"] .gf-dev-pet-chip {
        background: rgba(37, 99, 235, .16);
        color: #bfdbfe;
        border-color: rgba(37, 99, 235, .24);
    }

    body[data-theme="dark"] .gf-dev-pet-task {
        background: rgba(37, 99, 235, .10);
        border-color: rgba(96, 165, 250, .20);
    }

    body[data-theme="dark"] .gf-dev-pet-btn.is-secondary {
        background: rgba(15, 23, 42, .78);
        color: #cbd5e1;
        border-color: rgba(148, 163, 184, .22);
    }

    body[data-theme="dark"] .gf-dev-pet-close:hover {
        background: rgba(148, 163, 184, .12);
        color: #f8fafc;
    }

    @media (max-width: 768px) {
        .gf-dev-pet {
            right: 12px;
            bottom: calc(108px + env(safe-area-inset-bottom, 0px));
            left: auto;
        }

        .gf-dev-pet.is-docked-left {
            left: 12px;
            right: auto;
        }

        .gf-dev-pet.is-docked-right {
            right: 12px;
            left: auto;
        }

        .gf-dev-pet-panel {
            width: min(100vw - 20px, 372px);
            max-height: 66vh;
        }
    }
</style>

<div class="gf-dev-pet" id="gfDevPet" data-page-context='@json($gfDevPetContext)'>
    <button type="button" class="gf-dev-pet-launcher" id="gfDevPetLauncher" aria-expanded="false" aria-controls="gfDevPetPanel">
        <span class="gf-dev-pet-orb"><i class="bi bi-stars"></i></span>
        <span class="gf-dev-pet-copy">
            <strong>Dev Pet</strong>
            <small>AI helper untuk halaman ini</small>
        </span>
    </button>

    <section class="gf-dev-pet-panel" id="gfDevPetPanel" hidden>
        <div class="gf-dev-pet-header" id="gfDevPetHeader">
            <div>
                <div class="gf-dev-pet-title">Dev Pet</div>
                <div class="gf-dev-pet-subtitle">Nanya AI dari halaman mana pun saat mode developer aktif.</div>
            </div>
            <div class="gf-dev-pet-header-actions">
                <button type="button" class="gf-dev-pet-close" id="gfDevPetMinimize" aria-label="Minimize pet">−</button>
                <button type="button" class="gf-dev-pet-close" id="gfDevPetClose" aria-label="Tutup pet">×</button>
            </div>
        </div>

        <div class="gf-dev-pet-body">
            <div class="gf-dev-pet-chips" aria-label="Quick prompts">
                <button type="button" class="gf-dev-pet-chip" data-gf-dev-pet-prompt="Ringkas halaman ini dan kasih insight yang paling penting." data-gf-dev-pet-mode="chat">Ringkas halaman ini</button>
                <button type="button" class="gf-dev-pet-chip" data-gf-dev-pet-prompt="Audit halaman ini dan sebutkan bug atau risiko yang paling penting." data-gf-dev-pet-mode="chat">Audit bug</button>
                <button type="button" class="gf-dev-pet-chip" data-gf-dev-pet-prompt="Ubah request ini jadi task kerja yang jelas untuk developer." data-gf-dev-pet-mode="task">Jadi task</button>
            </div>

            <div class="gf-dev-pet-thread" id="gfDevPetThread" aria-live="polite"></div>

            <div class="gf-dev-pet-form">
                <textarea
                    id="gfDevPetPrompt"
                    class="gf-dev-pet-input"
                    placeholder="Tulis pertanyaan, audit, atau request task di sini..."
                ></textarea>
                <div class="gf-dev-pet-actions">
                    <button type="button" class="gf-dev-pet-btn is-primary" id="gfDevPetSendChat">
                        <i class="bi bi-chat-dots"></i>
                        Tanya AI
                    </button>
                    <button type="button" class="gf-dev-pet-btn is-secondary" id="gfDevPetSendTask">
                        <i class="bi bi-list-task"></i>
                        Jadikan task
                    </button>
                </div>
                <div class="gf-dev-pet-status" id="gfDevPetStatus">Siap. Ctrl+Enter untuk kirim.</div>
            </div>
        </div>
    </section>
</div>

<script>
(function () {
    const root = document.getElementById('gfDevPet');
    if (!root) {
        return;
    }

    const launcher = document.getElementById('gfDevPetLauncher');
    const panel = document.getElementById('gfDevPetPanel');
    const minimizeBtn = document.getElementById('gfDevPetMinimize');
    const closeBtn = document.getElementById('gfDevPetClose');
    const threadEl = document.getElementById('gfDevPetThread');
    const promptEl = document.getElementById('gfDevPetPrompt');
    const statusEl = document.getElementById('gfDevPetStatus');
    const sendChatBtn = document.getElementById('gfDevPetSendChat');
    const sendTaskBtn = document.getElementById('gfDevPetSendTask');
    const quickButtons = root.querySelectorAll('[data-gf-dev-pet-prompt]');
    const chatEndpoint = @json(route('ai.agent.chat'));
    const taskEndpoint = @json(route('ai.agent.task'));
    const pageContext = @json($gfDevPetContext);
    const pageContextKey = pageContext.route || pageContext.path || pageContext.url || 'page';
    const storageOpenKey = 'gf-dev-pet-open';
    const storageHistoryKey = 'gf-dev-pet-history';
    const storageDockKey = 'gf-dev-pet-dock';
    const storageShiftYKey = 'gf-dev-pet-shift-y';
    const storageMinimizedKey = 'gf-dev-pet-minimized';
    const storageAutoInsightKey = `gf-dev-pet-auto-insight:${pageContextKey}`;
    const maxHistory = 18;

    pageContext.page_title = document.title;
    let currentDockSide = readJson(storageDockKey, 'right');
    let currentShiftY = Number(readJson(storageShiftYKey, 0)) || 0;
    let dragState = {
        active: false,
        pointerId: null,
        source: null,
        startX: 0,
        startY: 0,
        startShiftY: 0,
        moved: false,
        justDragged: false,
    };

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function readJson(key, fallback) {
        try {
            const raw = localStorage.getItem(key);
            if (!raw) {
                return fallback;
            }
            return JSON.parse(raw);
        } catch (error) {
            return fallback;
        }
    }

    function saveJson(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (error) {
            // Ignore storage failures.
        }
    }

    function saveSessionFlag(key, value) {
        try {
            sessionStorage.setItem(key, value);
        } catch (error) {
            // Ignore storage failures.
        }
    }

    function readSessionFlag(key) {
        try {
            return sessionStorage.getItem(key);
        } catch (error) {
            return null;
        }
    }

    function applyDock(side) {
        currentDockSide = side === 'left' ? 'left' : 'right';
        root.classList.toggle('is-docked-left', currentDockSide === 'left');
        root.classList.toggle('is-docked-right', currentDockSide !== 'left');
        saveJson(storageDockKey, currentDockSide);
    }

    function clampShiftY(value) {
        const maxShift = Math.max(120, window.innerHeight - 160);
        return Math.max(-maxShift, Math.min(maxShift, value));
    }

    function applyShiftY(value) {
        currentShiftY = clampShiftY(Number(value) || 0);
        root.style.setProperty('--gf-dev-pet-shift-y', `${currentShiftY}px`);
        saveJson(storageShiftYKey, currentShiftY);
    }

    function resetDragTransform() {
        root.style.setProperty('--gf-dev-pet-shift-x', '0px');
    }

    function setDragTransform(deltaX, deltaY) {
        root.style.setProperty('--gf-dev-pet-shift-x', `${deltaX}px`);
        root.style.setProperty('--gf-dev-pet-shift-y', `${clampShiftY(deltaY)}px`);
    }

    function normalizeHistory(history) {
        if (!Array.isArray(history)) {
            return [];
        }

        return history
            .filter((item) => item && typeof item === 'object' && typeof item.text === 'string')
            .slice(-maxHistory);
    }

    function defaultHistory() {
        return [{
            role: 'ai',
            text: 'Halo, aku Dev Pet. Tulis request singkat atau klik salah satu prompt cepat di atas.',
        }];
    }

    let history = normalizeHistory(readJson(storageHistoryKey, []));
    if (!history.length) {
        history = defaultHistory();
        saveJson(storageHistoryKey, history);
    }

    applyDock(currentDockSide);
    applyShiftY(currentShiftY);

    function setMinimized(next) {
        const isMinimized = Boolean(next);
        root.classList.toggle('is-minimized', isMinimized);
        saveJson(storageMinimizedKey, isMinimized);
    }

    function setBusy(isBusy, label) {
        root.classList.toggle('is-busy', isBusy);
        launcher.disabled = isBusy;
        sendChatBtn.disabled = isBusy;
        sendTaskBtn.disabled = isBusy;
        quickButtons.forEach((button) => {
            button.disabled = isBusy;
        });

        statusEl.textContent = label || (isBusy ? 'Memproses...' : 'Siap. Ctrl+Enter untuk kirim.');
    }

    function renderTask(task) {
        if (!task || (!task.title && !task.summary && !Array.isArray(task.steps))) {
            return '';
        }

        const steps = Array.isArray(task.steps) ? task.steps.filter(Boolean) : [];
        const stepsHtml = steps.length
            ? `<ul class="gf-dev-pet-task-list">${steps.map((step) => `<li>${escapeHtml(step)}</li>`).join('')}</ul>`
            : '';

        return `
            <div class="gf-dev-pet-task">
                ${task.title ? `<div class="gf-dev-pet-task-title">${escapeHtml(task.title)}</div>` : ''}
                ${task.summary ? `<div class="gf-dev-pet-task-summary">${escapeHtml(task.summary)}</div>` : ''}
                ${stepsHtml}
            </div>
        `;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderHistory() {
        threadEl.innerHTML = history.map((item) => {
            const taskBlock = item.task ? renderTask(item.task) : '';
            return `
                <div class="gf-dev-pet-message is-${item.role === 'user' ? 'user' : 'ai'}">
                    ${escapeHtml(item.text)}
                    ${taskBlock}
                </div>
            `;
        }).join('');

        threadEl.scrollTop = threadEl.scrollHeight;
    }

    function pushMessage(role, text, task) {
        history.push({
            role: role === 'user' ? 'user' : 'ai',
            text: String(text || '').trim(),
            task: task || null,
        });

        history = normalizeHistory(history);
        saveJson(storageHistoryKey, history);
        renderHistory();
    }

    function setOpen(next) {
        const isOpen = Boolean(next);
        root.classList.toggle('is-open', isOpen);
        panel.hidden = !isOpen;
        launcher.setAttribute('aria-expanded', String(isOpen));
        saveJson(storageOpenKey, isOpen);

        if (isOpen) {
            setTimeout(() => promptEl.focus(), 50);
        }
    }

    async function send(mode, promptOverride, options = {}) {
        const {
            silentUser = false,
            openOnSuccess = false,
            markAuto = false,
        } = options;

        const message = String(promptOverride ?? promptEl.value ?? '').trim();
        if (!message) {
            statusEl.textContent = 'Tulis prompt dulu ya.';
            promptEl.focus();
            return;
        }

        const endpoint = mode === 'task' ? taskEndpoint : chatEndpoint;
        const payload = {
            message,
            page_context: pageContext,
            mode: mode === 'task' ? 'task' : 'internal',
        };

        if (!silentUser) {
            pushMessage('user', message);
        }
        promptEl.value = '';
        setBusy(true, 'Menghubungkan ke AI...');

        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                throw new Error(data.message || 'Gagal memproses permintaan.');
            }

            pushMessage('ai', data.reply || '-', data.task || null);
            statusEl.textContent = data.meta?.model
                ? `Selesai dengan model ${data.meta.model}.`
                : 'Selesai.';

            if (markAuto) {
                saveSessionFlag(storageAutoInsightKey, 'done');
            }

            if (openOnSuccess && !root.classList.contains('is-open')) {
                setOpen(true);
            }
        } catch (error) {
            if (!markAuto) {
                pushMessage('ai', error.message || 'Terjadi error saat memanggil AI.');
            }
            statusEl.textContent = error.message || 'Terjadi error.';
            if (markAuto) {
                console.warn('Auto insight gagal:', error);
            }
        } finally {
            setBusy(false);
        }
    }

    function buildAutoInsightPrompt() {
        return [
            'Beri auto insight singkat untuk halaman internal berikut.',
            `Page title: ${pageContext.page_title || document.title}`,
            `Route: ${pageContext.route || '-'}`,
            `Path: ${pageContext.path || '-'}`,
            `URL: ${pageContext.url || '-'}`,
            '',
            'Jawab dalam bahasa Indonesia, sangat singkat, dengan format:',
            '1. tujuan halaman dalam 1 kalimat',
            '2. 1 risiko atau kelemahan yang terlihat',
            '3. 1 saran tindakan paling cepat',
            '',
            'Jangan panjang. Fokus pada hal yang paling berguna untuk developer.'
        ].join('\n');
    }

    function setupDragHandle(handle) {
        handle.addEventListener('pointerdown', function (event) {
            if (event.button !== 0 && event.pointerType === 'mouse') {
                return;
            }

            dragState = {
                active: true,
                pointerId: event.pointerId,
                source: handle,
                startX: event.clientX,
                startY: event.clientY,
                startShiftY: currentShiftY,
                moved: false,
                justDragged: false,
            };

            root.classList.add('is-dragging');
            try {
                handle.setPointerCapture(event.pointerId);
            } catch (error) {
                // ignore pointer capture errors
            }

            event.preventDefault();
        });
    }

    function onPointerMove(event) {
        if (!dragState.active || event.pointerId !== dragState.pointerId) {
            return;
        }

        const deltaX = event.clientX - dragState.startX;
        const deltaY = event.clientY - dragState.startY;
        if (Math.abs(deltaX) > 6 || Math.abs(deltaY) > 6) {
            dragState.moved = true;
        }

        setDragTransform(deltaX, dragState.startShiftY + deltaY);
        event.preventDefault();
    }

    function finishDrag(event) {
        if (!dragState.active || event.pointerId !== dragState.pointerId) {
            return;
        }

        const source = dragState.source;
        const deltaX = event.clientX - dragState.startX;
        const deltaY = event.clientY - dragState.startY;
        const moved = dragState.moved || Math.abs(deltaX) > 6 || Math.abs(deltaY) > 6;

        if (moved) {
            const side = event.clientX < (window.innerWidth / 2) ? 'left' : 'right';
            applyDock(side);
            applyShiftY(dragState.startShiftY + deltaY);
            resetDragTransform();
            dragState.justDragged = true;
        } else {
            dragState.justDragged = false;
        }

        dragState.active = false;
        dragState.pointerId = null;
        root.classList.remove('is-dragging');

        try {
            source?.releasePointerCapture(event.pointerId);
        } catch (error) {
            // ignore pointer capture errors
        }

        dragState.source = null;
    }

    launcher.addEventListener('click', function () {
        if (dragState.justDragged) {
            dragState.justDragged = false;
            return;
        }

        if (root.classList.contains('is-open') && root.classList.contains('is-minimized')) {
            setMinimized(false);
            setOpen(true);
            return;
        }

        setOpen(!root.classList.contains('is-open'));
    });

    setupDragHandle(launcher);
    const petHeader = document.getElementById('gfDevPetHeader');
    if (petHeader) {
        setupDragHandle(petHeader);
    }
    document.addEventListener('pointermove', onPointerMove, { passive: false });
    document.addEventListener('pointerup', finishDrag, { passive: false });
    document.addEventListener('pointercancel', finishDrag, { passive: false });

    closeBtn.addEventListener('click', function () {
        setMinimized(false);
        setOpen(false);
    });

    minimizeBtn.addEventListener('click', function () {
        setOpen(true);
        setMinimized(true);
    });

    quickButtons.forEach((button) => {
        button.addEventListener('click', function () {
            const prompt = button.getAttribute('data-gf-dev-pet-prompt') || '';
            const mode = button.getAttribute('data-gf-dev-pet-mode') || 'chat';
            promptEl.value = prompt;
            send(mode, prompt);
        });
    });

    sendChatBtn.addEventListener('click', function () {
        send('chat');
    });

    sendTaskBtn.addEventListener('click', function () {
        send('task');
    });

    promptEl.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && event.ctrlKey) {
            event.preventDefault();
            send(event.shiftKey ? 'task' : 'chat');
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && root.classList.contains('is-open')) {
            setOpen(false);
        }
    });

    renderHistory();
    setMinimized(readJson(storageMinimizedKey, false));
    setOpen(readJson(storageOpenKey, false));

    if (!readSessionFlag(storageAutoInsightKey)) {
        setTimeout(function () {
            if (readSessionFlag(storageAutoInsightKey)) {
                return;
            }

            send('chat', buildAutoInsightPrompt(), {
                silentUser: true,
                openOnSuccess: true,
                markAuto: true,
            });
        }, 1200);
    }
})();
</script>
