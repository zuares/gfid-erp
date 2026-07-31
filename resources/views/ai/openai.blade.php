@extends('layouts.app')
@section('title', 'Connect OpenAI • GreatFit')

@push('head')
<style>
    .openai-shell {
        position: relative;
        overflow: hidden;
        border-radius: 30px;
        background:
            radial-gradient(circle at top left, rgba(59,130,246,.18), transparent 28%),
            radial-gradient(circle at bottom right, rgba(14,165,233,.12), transparent 32%),
            linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid rgba(148,163,184,.16);
        box-shadow: 0 22px 52px rgba(15,23,42,.08);
    }

    .openai-hero {
        padding: clamp(1.25rem, 3vw, 2.25rem);
    }

    .openai-kicker {
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

    .openai-title {
        margin: 1rem 0 .9rem;
        max-width: 14ch;
        font-size: clamp(2rem, 5vw, 4.1rem);
        line-height: .95;
        letter-spacing: -.07em;
        font-weight: 900;
        color: #0f172a;
    }

    .openai-copy {
        max-width: 760px;
        color: #475569;
        font-size: 1.02rem;
    }

    .section-kicker {
        text-transform: uppercase;
        letter-spacing: .14em;
        font-size: .72rem;
        color: #64748b;
        font-weight: 900;
    }

    .openai-card {
        border-radius: 24px;
        background: #fff;
        border: 1px solid rgba(148,163,184,.16);
        box-shadow: 0 14px 34px rgba(15,23,42,.08);
        padding: 1.15rem;
    }

    .openai-status {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .38rem .7rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
        background: #e2e8f0;
        color: #334155;
    }

    .openai-status.connected {
        background: #dcfce7;
        color: #166534;
    }

    .openai-field {
        margin-bottom: 1rem;
    }

    .openai-field label {
        display: block;
        margin-bottom: .4rem;
        font-size: .75rem;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #64748b;
    }

    .openai-field input,
    .openai-field textarea {
        width: 100%;
        border-radius: 14px;
        border: 1px solid #dbe4ee;
        background: #fff;
        color: #0f172a;
        padding: .88rem .95rem;
        font-size: .96rem;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .openai-field input:focus,
    .openai-field textarea:focus {
        outline: none;
        border-color: #0f172a;
        box-shadow: 0 0 0 3px rgba(15,23,42,.08);
    }

    .openai-help {
        font-size: .84rem;
        color: #64748b;
        margin-top: .45rem;
        line-height: 1.55;
    }

    .openai-grid {
        display: grid;
        gap: .8rem;
    }

    .openai-metric {
        border-radius: 18px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 1rem;
    }

    .openai-metric-label {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .14em;
        color: #64748b;
        font-weight: 900;
        margin-bottom: .35rem;
    }

    .openai-metric-value {
        font-size: 1.05rem;
        font-weight: 900;
        letter-spacing: -.04em;
        color: #0f172a;
        word-break: break-word;
    }

    .openai-list {
        display: grid;
        gap: .65rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .openai-list li {
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

    .openai-list i {
        color: #0284c7;
    }

    .btn-openai {
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
        border: 0;
    }

    .btn-openai:hover {
        transform: translateY(-1px);
    }

    .btn-openai-primary {
        background: #0f172a;
        color: #fff;
        box-shadow: 0 14px 28px rgba(15,23,42,.16);
    }

    .btn-openai-secondary {
        background: #fff;
        color: #0f172a;
        border: 1px solid #cbd5e1;
    }

    .btn-openai-danger {
        background: #fff;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    @media (max-width: 767.98px) {
        .btn-openai {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
@php
    $currentModel = old('model', $connection?->model ?: $defaults['model']);
    $currentLabel = old('label', $connection?->label ?: $defaults['label']);
    $hasConnection = (bool) $connection;
    $currentProjectId = old('project_id', $connection?->project_id);
    $currentOrganizationId = old('organization_id', $connection?->organization_id);
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

    <div class="openai-shell mb-4">
        <div class="openai-hero">
            <div class="row g-4 align-items-start">
                <div class="col-lg-8">
                    <span class="openai-kicker"><i class="bi bi-plug"></i> Connect OpenAI</span>
                    <h1 class="openai-title">{{ $hasConnection ? 'Koneksi OpenAI sudah aktif.' : 'Sambungkan akun OpenAI untuk user ini.' }}</h1>
                    <p class="openai-copy">
                        API key disimpan terenkripsi per user, lalu dipakai otomatis oleh AI Agent.
                        {{ $hasConnection
                            ? 'Kamu bisa memperbarui koneksi langsung dari halaman ini kapan saja.'
                            : 'Halaman ini khusus untuk setup awal. Setelah tersambung, koneksi tetap bisa diedit di halaman yang sama.' }}
                    </p>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a href="{{ route('ai.index') }}" class="btn-openai btn-openai-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali ke AI Studio
                        </a>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="openai-card h-100">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <div>
                                <div class="text-uppercase fw-black" style="font-size:.72rem;letter-spacing:.14em;color:#64748b;">Status</div>
                                <div class="h5 fw-black mb-0 mt-1" style="letter-spacing:-.04em;">Koneksi saat ini</div>
                            </div>
                            <span class="openai-status {{ $hasConnection ? 'connected' : '' }}">
                                {{ $hasConnection ? 'Active' : 'Empty' }}
                            </span>
                        </div>

                        <div class="openai-grid">
                            <div class="openai-metric">
                                <div class="openai-metric-label">Label</div>
                                <div class="openai-metric-value">{{ $connection?->label ?: 'Belum terhubung' }}</div>
                            </div>
                            <div class="openai-metric">
                                <div class="openai-metric-label">Model</div>
                                <div class="openai-metric-value">{{ $connection?->model ?: $defaults['model'] }}</div>
                            </div>
                            <div class="openai-metric">
                                <div class="openai-metric-label">API key</div>
                                <div class="openai-metric-value">{{ $connection ? $connection->maskedKey() : 'Tidak ada' }}</div>
                            </div>
                        </div>

                        @if ($connection?->last_verified_at)
                            <div class="small text-muted mt-3">
                                Verified {{ $connection->last_verified_at->timezone(config('app.timezone'))->format('d M Y, H:i') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="openai-card h-100">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="section-kicker">Connection form</span>
                    <span class="badge rounded-pill text-bg-light border" style="letter-spacing:.08em;text-transform:uppercase;">Encrypted at rest</span>
                </div>

                <form method="POST" action="{{ route('ai.openai.store') }}">
                    @csrf

                    <div class="openai-field">
                        <label for="label">Connection label</label>
                        <input type="text" id="label" name="label" value="{{ $currentLabel }}" placeholder="Personal OpenAI">
                        <div class="openai-help">Nama bebas untuk memudahkan user membedakan koneksi, misalnya "Personal", "Agency", atau "Owner Key".</div>
                    </div>

                    <div class="openai-field">
                        <label for="api_key">OpenAI API key</label>
                        <input type="password" id="api_key" name="api_key" value="{{ old('api_key') }}" placeholder="sk-...">
                        <div class="openai-help">
                            {{ $hasConnection ? 'Kosongkan jika API key tidak ingin diganti.' : 'Wajib diisi untuk koneksi pertama.' }}
                        </div>
                        @error('api_key')
                            <div class="text-danger small fw-semibold mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="openai-field">
                                <label for="model">Preferred model</label>
                                <input type="text" id="model" name="model" value="{{ $currentModel }}" placeholder="{{ config('services.openai.model', 'gpt-5.6-terra') }}">
                                <div class="openai-help">Model ini dipakai untuk request AI milik user ini.</div>
                                @error('model')
                                    <div class="text-danger small fw-semibold mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="openai-field">
                                <label for="project_id">Project ID</label>
                                <input type="text" id="project_id" name="project_id" value="{{ $currentProjectId }}" placeholder="proj_...">
                                <div class="openai-help">Opsional, hanya jika user punya project OpenAI tertentu.</div>
                                @error('project_id')
                                    <div class="text-danger small fw-semibold mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="openai-field">
                        <label for="organization_id">Organization ID</label>
                        <input type="text" id="organization_id" name="organization_id" value="{{ $currentOrganizationId }}" placeholder="org_...">
                        <div class="openai-help">Opsional, untuk akun organisasi yang memang menggunakannya.</div>
                        @error('organization_id')
                            <div class="text-danger small fw-semibold mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button type="submit" class="btn-openai btn-openai-primary">
                            <i class="bi bi-check2-circle"></i> {{ $hasConnection ? 'Simpan Perubahan' : 'Simpan & Verifikasi' }}
                        </button>
                        <a href="{{ route('ai.agent') }}" class="btn-openai btn-openai-secondary">
                            <i class="bi bi-chat-square-text"></i> Coba di AI Agent
                        </a>
                    </div>
                </form>

                @if ($hasConnection)
                    <div class="mt-4 p-3 rounded-4" style="background:#f8fafc;border:1px solid #e2e8f0;">
                        <div class="fw-black mb-1" style="letter-spacing:-.03em;color:#0f172a;">Koneksi aktif</div>
                        <div class="text-muted" style="font-size:.92rem;line-height:1.6;">
                            Perubahan akan langsung dipakai AI Agent setelah disimpan.
                        </div>
                        <form method="POST" action="{{ route('ai.openai.destroy') }}" class="mt-3" onsubmit="return confirm('Hapus koneksi OpenAI untuk user ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-openai btn-openai-danger">
                                <i class="bi bi-trash3"></i> Hapus koneksi
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="openai-card h-100">
                <div class="section-kicker mb-2">How it works</div>
                <h2 class="h4 fw-black mb-3" style="letter-spacing:-.04em;color:#0f172a;">Flow koneksi yang aman.</h2>
                <ul class="openai-list">
                    <li><i class="bi bi-shield-lock"></i> API key disimpan terenkripsi di database.</li>
                    <li><i class="bi bi-person-badge"></i> Koneksi dipakai per user, bukan global untuk semua orang.</li>
                    <li><i class="bi bi-check2-circle"></i> Kunci baru diverifikasi sebelum disimpan.</li>
                    <li><i class="bi bi-diagram-3"></i> AI Agent otomatis pakai koneksi aktif kalau ada.</li>
                    <li><i class="bi bi-arrow-repeat"></i> Kalau key dikosongkan, metadata bisa tetap diperbarui.</li>
                </ul>

                <div class="mt-4 p-3 rounded-4" style="background:#f8fafc;border:1px solid #e2e8f0;">
                    <div class="fw-black mb-1" style="letter-spacing:-.03em;color:#0f172a;">Catatan</div>
                    <div class="text-muted" style="font-size:.92rem;line-height:1.6;">
                        OpenAI API saat ini paling aman diperlakukan sebagai koneksi berbasis API key per user.
                        Jadi pengalaman login tetap pakai OAuth, sementara koneksi OpenAI dikelola terpisah di settings ini.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
