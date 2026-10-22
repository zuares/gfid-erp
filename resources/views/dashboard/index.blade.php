@extends('layouts.app')

@section('title', 'Beranda')

@push('head')
    @include('dashboard.partials._styles')
@endpush

@section('content')
    <div class="dash">
        <div class="dash-hero">
            <div>
                <h1>{{ $greeting }}, {{ $userName }} 👋</h1>
                <div class="sub">Ini ringkasan kerja hari ini. Klik kartu untuk langsung menuju halamannya.</div>
                <span class="role-chip">
                    <i class="bi bi-person-badge"></i>
                    {{ ['owner' => 'Pemilik', 'admin' => 'Admin', 'operating' => 'Operator'][$role] ?? 'User' }}
                </span>
            </div>
            <div class="hero-date">
                <i class="bi bi-calendar3"></i> {{ $today }}
            </div>
        </div>

        @if($activeAnomaliesCount > 0)
            <div class="dash-alert dash-alert-danger" role="alert">
                <div class="dash-alert-content">
                    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                    <div>
                        <strong>Peringatan Sistem</strong>
                        <span>Ditemukan <b id="anomalyCount">{{ $activeAnomaliesCount }}</b> anomali stok/fulfillment.</span>
                        <small>Periksa log <code>inventory_audit_findings</code> untuk detail.</small>
                    </div>
                </div>
                @if($canRunInventoryAudit)
                    <button id="btnRunAudit" type="button" class="dash-alert-action" data-audit-url="{{ url('/api/dev/run-audit') }}">
                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Jalankan Audit
                    </button>
                    <span id="auditFeedback" class="dash-alert-feedback" role="status" aria-live="polite"></span>
                @endif
            </div>

            @if($canRunInventoryAudit)
            <script>
            (() => {
                const button = document.querySelector('[data-audit-url]');
                const feedback = document.getElementById('auditFeedback');
                if (!button || !feedback) return;

                button.addEventListener('click', async () => {
                    const original = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = '<i class="bi bi-arrow-repeat spin-icon" aria-hidden="true"></i> Menjalankan...';
                    feedback.textContent = '';

                    try {
                        const response = await fetch(button.dataset.auditUrl, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin',
                        });
                        const data = await response.json();
                        if (!response.ok) throw new Error(data.message || 'Audit gagal dijalankan.');

                        feedback.textContent = data.message || 'Audit selesai.';
                        window.setTimeout(() => window.location.reload(), 500);
                    } catch (error) {
                        feedback.textContent = error.message || 'Gagal menghubungi server.';
                        button.disabled = false;
                        button.innerHTML = original;
                    }
                });
            })();
            </script>
            @endif
        @endif

        @includeFirst(
            ['dashboard.partials.' . $role, 'dashboard.partials.generic'],
            ['d' => $d]
        )
    </div>
@endsection
