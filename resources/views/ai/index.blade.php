@extends('layouts.app')
@section('title', 'AI Studio • GreatFit')

@push('head')
<style>
    .ai-hub-shell {
        position: relative;
        overflow: hidden;
        border-radius: 30px;
        background:
            radial-gradient(circle at 0% 0%, rgba(14,165,233,.22), transparent 28%),
            radial-gradient(circle at 100% 0%, rgba(37,99,235,.12), transparent 30%),
            linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid rgba(148,163,184,.16);
        box-shadow: 0 22px 52px rgba(15,23,42,.08);
    }

    .ai-hero {
        position: relative;
        padding: clamp(1.25rem, 3vw, 2.25rem);
    }

    .ai-kicker {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .4rem .8rem;
        border-radius: 999px;
        background: #0f172a;
        color: #fff;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .ai-title {
        margin: 1rem 0 .95rem;
        max-width: 12ch;
        font-size: clamp(2.1rem, 5vw, 4.5rem);
        line-height: .94;
        letter-spacing: -.07em;
        font-weight: 900;
        color: #0f172a;
    }

    .ai-copy {
        max-width: 760px;
        color: #475569;
        font-size: 1.02rem;
    }

    .ai-actions {
        display: flex;
        gap: .75rem;
        flex-wrap: wrap;
        margin-top: 1.5rem;
    }

    .ai-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        min-height: 46px;
        padding: 0 1rem;
        border-radius: 999px;
        text-decoration: none;
        font-size: .84rem;
        font-weight: 900;
        letter-spacing: .03em;
        transition: transform .16s ease, box-shadow .16s ease, background .16s ease, color .16s ease;
    }

    .ai-btn:hover {
        transform: translateY(-1px);
    }

    .ai-btn-primary {
        background: #0f172a;
        color: #fff;
        box-shadow: 0 14px 28px rgba(15,23,42,.16);
    }

    .ai-btn-primary:hover {
        color: #fff;
    }

    .ai-btn-secondary {
        background: #fff;
        color: #0f172a;
        border: 1px solid #cbd5e1;
    }

    .ai-btn-secondary:hover {
        color: #0f172a;
        background: #f8fafc;
    }

    .ai-metrics {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }

    .ai-metric {
        padding: 1rem;
        border-radius: 18px;
        background: #fff;
        border: 1px solid rgba(148,163,184,.18);
        box-shadow: 0 10px 26px rgba(15,23,42,.06);
    }

    .ai-metric-label {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .14em;
        color: #64748b;
        font-weight: 900;
        margin-bottom: .4rem;
    }

    .ai-metric-value {
        font-size: 1.15rem;
        font-weight: 900;
        letter-spacing: -.04em;
        color: #0f172a;
    }

    .ai-panel {
        border-radius: 24px;
        background: #0f172a;
        color: #fff;
        padding: 1.2rem;
        box-shadow: 0 18px 42px rgba(15,23,42,.18);
    }

    .ai-panel.soft {
        background: #fff;
        color: #0f172a;
        border: 1px solid rgba(148,163,184,.16);
        box-shadow: 0 14px 32px rgba(15,23,42,.08);
    }

    .ai-panel .muted {
        color: rgba(255,255,255,.72);
    }

    .ai-panel.soft .muted {
        color: #64748b;
    }

    .ai-status {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .38rem .7rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
        background: rgba(255,255,255,.1);
        color: #fff;
    }

    .ai-status.connected {
        background: #dcfce7;
        color: #166534;
    }

    .ai-stack {
        display: grid;
        gap: .8rem;
    }

    .ai-card {
        height: 100%;
        border-radius: 22px;
        background: #fff;
        border: 1px solid rgba(148,163,184,.16);
        box-shadow: 0 14px 34px rgba(15,23,42,.08);
        padding: 1.15rem;
    }

    .ai-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e0f2fe;
        color: #0369a1;
        font-size: 1.1rem;
        margin-bottom: .9rem;
    }

    .ai-card-title {
        font-size: 1.02rem;
        font-weight: 900;
        color: #0f172a;
        margin-bottom: .35rem;
    }

    .ai-card-desc {
        color: #475569;
        font-size: .94rem;
    }

    .ai-list {
        display: grid;
        gap: .65rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .ai-list li {
        display: flex;
        align-items: center;
        gap: .55rem;
        padding: .8rem .95rem;
        border-radius: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #334155;
        font-weight: 700;
    }

    .ai-list i {
        color: #0284c7;
    }

    .section-kicker {
        text-transform: uppercase;
        letter-spacing: .14em;
        font-size: .72rem;
        color: #64748b;
        font-weight: 900;
    }

    @media (max-width: 767.98px) {
        .ai-actions .ai-btn {
            width: 100%;
        }

        .ai-metrics {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $canOpenMarketplaceChat = $user?->canAccessModule('marketplace') ?? false;
    $connection = $user?->openAiConnection;
    $isConnected = (bool) ($connectionSummary['connected'] ?? false);
@endphp

<div class="page-wrap">
    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-3">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-3">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-3">
            <div class="fw-bold mb-1">Ada input yang perlu dibetulkan.</div>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    <div class="ai-hub-shell mb-4">
        <div class="ai-hero">
            <div class="row g-4 align-items-start">
                <div class="col-lg-8">
                    <span class="ai-kicker"><i class="bi bi-stars"></i> AI Studio</span>
                    <h1 class="ai-title">AI command center yang aman dan siap kerja.</h1>
                    <p class="ai-copy">
                        Halaman ini jadi pusat kerja AI internal GreatFit. Semua request diproses server-side,
                        koneksi OpenAI bisa diatur per user, dan aksesnya tetap mengikuti login serta role yang ada.
                    </p>

                    <div class="ai-actions">
                        <a href="{{ route('ai.agent') }}" class="ai-btn ai-btn-primary">
                            <i class="bi bi-chat-square-text"></i> Buka AI Agent
                        </a>
                        <a href="{{ route('ai.openai.index') }}" class="ai-btn ai-btn-secondary">
                            <i class="bi bi-plug"></i> {{ $isConnected ? 'Kelola OpenAI' : 'Connect OpenAI' }}
                        </a>
                        @if ($canOpenMarketplaceChat)
                            <a href="{{ route('marketplace.chat') }}" class="ai-btn ai-btn-secondary">
                                <i class="bi bi-bag-check"></i> Chat Marketplace
                            </a>
                        @endif
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="ai-panel soft h-100">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <div>
                                <div class="section-kicker">OpenAI status</div>
                                <div class="h5 fw-black mb-0 mt-1" style="letter-spacing:-.04em;">Koneksi per user</div>
                            </div>
                            <span class="ai-status {{ $isConnected ? 'connected' : '' }}">
                                {{ $isConnected ? 'Connected' : 'Not connected' }}
                            </span>
                        </div>

                        <div class="ai-stack">
                            <div class="ai-metric">
                                <div class="ai-metric-label">Source</div>
                                <div class="ai-metric-value">{{ $connectionSummary['source'] ?? 'Unknown' }}</div>
                            </div>
                            <div class="ai-metric">
                                <div class="ai-metric-label">Model</div>
                                <div class="ai-metric-value">{{ $connectionSummary['model'] ?? config('services.openai.model', 'gpt-5.6-terra') }}</div>
                            </div>
                            <div class="ai-metric">
                                <div class="ai-metric-label">Label</div>
                                <div class="ai-metric-value">{{ $connectionSummary['label'] ?? 'Belum terhubung' }}</div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ route('ai.openai.index') }}" class="ai-btn ai-btn-secondary w-100 justify-content-center">
                                <i class="bi bi-plug"></i> {{ $isConnected ? 'Kelola OpenAI' : 'Setup OpenAI' }}
                            </a>
                        </div>

                        @if ($connectionSummary['verified_at'] ?? null)
                            <div class="mt-3 small text-muted">
                                Verified {{ optional($connectionSummary['verified_at'])->timezone(config('app.timezone'))->format('d M Y, H:i') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($cards as $card)
            <div class="col-md-4">
                <div class="ai-card">
                    <div class="ai-card-icon"><i class="bi {{ $card['icon'] }}"></i></div>
                    <div class="ai-card-title">{{ $card['title'] }}</div>
                    <div class="ai-card-desc">{{ $card['desc'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="ai-card h-100">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="section-kicker">Security</span>
                    <span class="badge rounded-pill text-bg-light border" style="letter-spacing:.08em;text-transform:uppercase;">Backend only</span>
                </div>
                <h2 class="h4 fw-black mb-3" style="letter-spacing:-.04em;color:#0f172a;">Aman by design.</h2>
                <ul class="ai-list">
                    @foreach ($safeguards as $item)
                        <li><i class="bi bi-shield-check"></i> {{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="ai-panel h-100">
                <div class="section-kicker text-white-50 mb-2">Next step</div>
                <h3 class="h4 fw-black mb-3" style="letter-spacing:-.04em;">Sambungkan OpenAI per user, lalu masuk ke workbench.</h3>
                <p class="muted mb-4">
                    Kalau user punya API key sendiri, koneksi akan disimpan terenkripsi dan dipakai otomatis oleh AI Agent.
                    Kalau belum, app tetap bisa fallback ke key server yang ada.
                </p>
                <div class="d-grid gap-2">
                    <a href="{{ route('ai.openai.index') }}" class="ai-btn ai-btn-secondary w-100 justify-content-center">
                        <i class="bi bi-plug"></i> {{ $isConnected ? 'Kelola OpenAI' : 'Setup OpenAI' }}
                    </a>
                    <a href="{{ route('ai.agent') }}" class="ai-btn ai-btn-primary w-100 justify-content-center">
                        <i class="bi bi-rocket-takeoff"></i> Masuk ke Workbench
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
