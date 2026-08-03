@extends('layouts.app')
@section('title', 'AI Agent • GreatFit')

@push('head')
<style>
    .ai-topbar {
        display: flex;
        align-items: center;
        gap: .6rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
        padding: .55rem .75rem;
        border-radius: 14px;
        background: #ffffff;
        border: 1px solid rgba(148,163,184,.18);
        box-shadow: 0 10px 24px rgba(15,23,42,.06);
    }

    .ai-topbar-code {
        font-size: .95rem;
        font-weight: 900;
        letter-spacing: -.03em;
        color: #0f172a;
    }

    .ai-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .26rem .55rem;
        border-radius: 999px;
        background: #e0f2fe;
        color: #075985;
        border: 1px solid #bae6fd;
        font-size: .7rem;
        font-weight: 900;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .ai-topbar-spacer {
        flex: 1 1 auto;
    }

    .btn-ai-primary,
    .btn-ai-outline {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 900;
        padding: .42rem .82rem;
        text-decoration: none;
        transition: .15s ease;
    }

    .btn-ai-primary {
        background: #0f172a;
        color: #fff;
        border: 1px solid #0f172a;
        box-shadow: 0 10px 22px rgba(15,23,42,.14);
    }

    .btn-ai-primary:hover { color: #fff; transform: translateY(-1px); }

    .btn-ai-outline {
        background: #fff;
        color: #334155;
        border: 1px solid #cbd5e1;
    }

    .btn-ai-outline:hover {
        color: #0f172a;
        background: #f8fafc;
        transform: translateY(-1px);
    }

    .ai-hero {
        position: relative;
        overflow: hidden;
        border-radius: 26px;
        background: #f8fbff;
        color: #0f172a;
        border: 1px solid rgba(148,163,184,.18);
        box-shadow: 0 18px 42px rgba(15,23,42,.08);
    }

    .ai-pill {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .4rem .8rem;
        border-radius: 999px;
        background: #ffffff;
        border: 1px solid #dbeafe;
        color: #334155;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .02em;
    }

    .ai-card {
        height: 100%;
        border-radius: 20px;
        border: 1px solid var(--line);
        background: #ffffff;
        box-shadow: 0 16px 38px rgba(15,23,42,.08);
    }

    .ai-chip {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .38rem .7rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 800;
        color: #0f172a;
        background: #e0f2fe;
        border: 1px solid #bae6fd;
    }

    .ai-stat {
        border-radius: 18px;
        padding: 1rem 1.1rem;
        background: #ffffff;
        border: 1px solid rgba(148,163,184,.18);
    }

    .ai-quote {
        border: 1px solid #dbeafe;
        background: #ffffff;
        border-radius: 16px;
        padding: 1rem 1rem 1rem 1.1rem;
    }

    .section-kicker {
        text-transform: uppercase;
        letter-spacing: .14em;
        font-size: .72rem;
        color: var(--muted);
        font-weight: 900;
    }

    .ai-workbench {
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 16px 38px rgba(15,23,42,.08);
    }

    .ai-textarea {
        min-height: 150px;
        border-radius: 16px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        padding: 1rem 1rem;
        resize: vertical;
    }

    .ai-output {
        border-radius: 16px;
        border: 1px solid #dbeafe;
        background: #f8fbff;
        padding: 1rem;
        min-height: 120px;
        white-space: pre-wrap;
    }

    .ai-task-list {
        margin: 0;
        padding-left: 1.1rem;
    }

    .ai-task-list li + li {
        margin-top: .35rem;
    }
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $canOpenMarketplaceChat = $user?->canAccessModule('marketplace') ?? false;
@endphp
<div class="page-wrap">
    <div class="ai-topbar">
        <span class="ai-topbar-code">AI Agent</span>
        <span class="ai-badge">Halaman Khusus AI</span>
        <span class="ai-topbar-spacer"></span>
        <a href="{{ route('dashboard') }}" class="btn-ai-outline">Kembali</a>
        @if ($canOpenMarketplaceChat)
            <a href="{{ route('marketplace.chat') }}" class="btn-ai-primary">Buka Chat</a>
        @endif
    </div>

    <div class="ai-hero p-4 p-md-5 mb-4">
        <div class="position-relative">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="ai-pill"><i class="bi bi-stars"></i> AI Agent Studio</span>
                <span class="ai-pill"><i class="bi bi-heart"></i> Friendly UI</span>
                <span class="ai-pill"><i class="bi bi-chat-dots"></i> Helpful workflows</span>
            </div>

            <div class="row align-items-end g-4">
                <div class="col-lg-8">
                    <h1 class="display-6 fw-black mb-3" style="letter-spacing:-.04em;color:#0f172a;">
                        Buat halaman khusus AI untuk website kamu.
                    </h1>
                    <p class="lead mb-4" style="max-width: 760px; color: #475569;">
                        Halaman ini bisa jadi pusat AI agent: untuk pelanggan, tim internal, atau owner.
                        Kita bisa pakai sebagai landing page, command center, atau pintu masuk workflow AI yang terhubung ke modul lain.
                    </p>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="#use-cases" class="btn-ai-primary">
                            Lihat use case
                        </a>
                        @if ($canOpenMarketplaceChat)
                            <a href="{{ route('marketplace.chat') }}" class="btn-ai-outline">
                                Buka chat operasional
                            </a>
                        @endif
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="ai-quote">
                        <div class="section-kicker mb-2">Brief singkat</div>
                        <div class="fw-bold mb-2" style="color:#0f172a;">“AI yang bukan cuma ngobrol, tapi bantu kerja.”</div>
                        <div style="font-size:.92rem; color:#334155;">
                            Cocok untuk support pelanggan, ringkasan order, rekomendasi aksi, dan dashboard AI yang terasa premium.
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-4">
                <div class="col-6 col-lg-3">
                    <div class="ai-stat">
                        <div class="section-kicker mb-1">Mode</div>
                        <div class="h4 fw-black mb-0" style="color:#0f172a;">3</div>
                        <small class="text-muted">agent mode siap dikembangkan</small>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="ai-stat">
                        <div class="section-kicker mb-1">Fokus</div>
                        <div class="h4 fw-black mb-0" style="color:#0f172a;">Action</div>
                        <small class="text-muted">bukan sekadar chat biasa</small>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="ai-stat">
                        <div class="section-kicker mb-1">Scope</div>
                        <div class="h4 fw-black mb-0" style="color:#0f172a;">Internal</div>
                        <small class="text-muted">ops, sales, dan support</small>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="ai-stat">
                        <div class="section-kicker mb-1">Delivery</div>
                        <div class="h4 fw-black mb-0" style="color:#0f172a;">MVP</div>
                        <small class="text-muted">siap jadi halaman nyata</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($agentModes as $mode)
            <div class="col-md-4">
                <div class="ai-card p-4">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <div class="ai-chip mb-2">{{ $mode['status'] }}</div>
                            <h3 class="h5 fw-black mb-1">{{ $mode['title'] }}</h3>
                        </div>
                        <div class="rounded-4 d-inline-flex align-items-center justify-content-center" style="width:42px;height:42px;background:#0f172a;color:#fff;">
                            <i class="bi bi-braces-asterisk"></i>
                        </div>
                    </div>
                    <p class="text-muted mb-0">{{ $mode['desc'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body p-4">
                    <div class="section-kicker mb-2">Capabilities</div>
                    <h2 class="h4 fw-black mb-3">Apa yang halaman AI ini bisa tampilkan</h2>
                    <div class="row g-3">
                        @foreach ($capabilities as $capability)
                            <div class="col-md-6">
                                <div class="d-flex gap-3 align-items-start p-3 rounded-4" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                    <div class="flex-shrink-0 rounded-3 d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;background:#eff6ff;color:#1d4ed8;">
                                        <i class="bi bi-check2"></i>
                                    </div>
                                    <div class="fw-semibold">{{ $capability }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body p-4">
                    <div class="section-kicker mb-2">AI Prompt Space</div>
                    <h2 class="h4 fw-black mb-3">Area khusus untuk prompt, instruksi, atau workflow</h2>
                    <div class="rounded-4 p-3 mb-3" style="background:#f8fafc;color:#0f172a;border:1px solid #e2e8f0;">
                        <div class="small text-uppercase fw-bold mb-2" style="letter-spacing:.12em;color:#64748b;">Contoh arahan agent</div>
                        <div style="line-height:1.6;">
                            Kamu adalah AI assistant untuk website GreatFit. Jawab singkat, jelas, dan bantu user menyelesaikan tugas.
                            Kalau perlu tindakan, arahkan ke halaman internal yang sesuai.
                        </div>
                    </div>
                    <div class="small text-muted mb-0">
                        Nanti bagian ini bisa kita ubah jadi prompt editor, knowledge base, atau form pengaturan agent.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="use-cases" class="mb-4">
        <div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <div class="section-kicker mb-1">Use cases</div>
                <h2 class="h4 fw-black mb-0">Tiga arah halaman AI yang paling kepakai</h2>
            </div>
        </div>

        <div class="row g-3">
            @foreach ($useCases as $item)
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-body p-4">
                            <div class="ai-chip mb-3">{{ $item['kicker'] }}</div>
                            <h3 class="h5 fw-black mb-2">{{ $item['title'] }}</h3>
                            <p class="text-muted mb-0">{{ $item['body'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-body p-4 p-md-5 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="section-kicker mb-2">Next step</div>
                <h2 class="h4 fw-black mb-2">Kalau mau, kita bisa lanjut bikin versi yang benar-benar interaktif</h2>
                <p class="text-muted mb-0" style="max-width:720px;">
                    Misalnya ada input prompt, tombol aksi cepat, knowledge base, history percakapan, atau widget AI yang terhubung ke modul internal.
                </p>
            </div>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-primary fw-bold px-4 py-2" style="border-radius:999px;">Kembali ke dashboard</a>
        </div>
    </div>

    <div class="ai-workbench p-4 p-md-5 mt-4">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
            <div>
                <div class="section-kicker mb-1">Workbench</div>
                <h2 class="h4 fw-black mb-0">Hub AI internal dan task Codex</h2>
            </div>
            <div class="text-muted small">Gunakan untuk jawab cepat atau ubah request jadi tugas kerja.</div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <label for="aiPrompt" class="form-label fw-bold">Tulis kebutuhan kamu</label>
                <textarea id="aiPrompt" class="form-control ai-textarea" placeholder="Contoh: bantu saya bikin halaman inventory intelligence yang lebih rapi dan bisa filter per gudang."></textarea>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" class="btn-ai-primary" id="btnAiChat">Tanya AI</button>
                    <button type="button" class="btn-ai-outline" id="btnAiTask">Jadikan Task Codex</button>
                </div>
                <div class="small text-muted mt-3" id="aiStatus">Siap menerima prompt.</div>
            </div>

            <div class="col-lg-6">
                <div class="mb-3">
                    <div class="section-kicker mb-2">Hasil AI</div>
                    <div class="ai-output" id="aiReply">Belum ada hasil. Tulis prompt dulu lalu klik salah satu tombol di kiri.</div>
                </div>
                <div>
                    <div class="section-kicker mb-2">Draft Task Codex</div>
                    <div class="ai-output">
                        <div class="fw-bold mb-2" id="taskTitle">-</div>
                        <div class="text-muted mb-3" id="taskSummary">Task summary akan tampil di sini.</div>
                        <ul class="ai-task-list text-dark" id="taskSteps"></ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const promptEl = document.getElementById('aiPrompt');
    const replyEl = document.getElementById('aiReply');
    const statusEl = document.getElementById('aiStatus');
    const taskTitleEl = document.getElementById('taskTitle');
    const taskSummaryEl = document.getElementById('taskSummary');
    const taskStepsEl = document.getElementById('taskSteps');
    const chatBtn = document.getElementById('btnAiChat');
    const taskBtn = document.getElementById('btnAiTask');

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function setLoading(isLoading, label) {
        chatBtn.disabled = isLoading;
        taskBtn.disabled = isLoading;
        statusEl.textContent = label || (isLoading ? 'Memproses...' : 'Siap menerima prompt.');
    }

    function renderTask(task) {
        taskTitleEl.textContent = task?.title || '-';
        taskSummaryEl.textContent = task?.summary || 'Task summary akan tampil di sini.';
        taskStepsEl.innerHTML = '';

        const steps = Array.isArray(task?.steps) ? task.steps : [];
        if (!steps.length) {
            const li = document.createElement('li');
            li.textContent = 'Belum ada langkah task.';
            taskStepsEl.appendChild(li);
            return;
        }

        steps.forEach((step) => {
            const li = document.createElement('li');
            li.textContent = step;
            taskStepsEl.appendChild(li);
        });
    }

    async function callAi(endpoint) {
        const message = (promptEl.value || '').trim();
        if (!message) {
            statusEl.textContent = 'Tulis prompt dulu ya.';
            promptEl.focus();
            return;
        }

        setLoading(true, 'Menghubungkan ke AI...');
        replyEl.textContent = 'Memproses permintaan...';

        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({ message }),
            });

            const data = await res.json();
            if (!res.ok || !data.ok) {
                throw new Error(data.message || 'Gagal memproses permintaan.');
            }

            replyEl.textContent = data.reply || '-';
            renderTask(data.task);
            statusEl.textContent = data.meta?.model ? `Selesai dengan model ${data.meta.model}.` : 'Selesai.';
        } catch (error) {
            replyEl.textContent = 'Terjadi error saat memanggil AI.';
            statusEl.textContent = error.message || 'Terjadi error.';
        } finally {
            setLoading(false);
        }
    }

    chatBtn.addEventListener('click', function () {
        callAi(@json(route('ai.agent.chat')));
    });

    taskBtn.addEventListener('click', function () {
        callAi(@json(route('ai.agent.task')));
    });
})();
</script>
@endpush
