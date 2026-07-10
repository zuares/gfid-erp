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

        @php
            $activeAnomaliesCount = \App\Models\InventoryAuditFinding::whereNull('resolved_at')->count();
        @endphp
        
        @if($activeAnomaliesCount > 0)
            <div class="alert alert-danger" style="margin-bottom: 20px; border-radius: 8px; padding: 15px; background: #fff5f5; border: 1px solid #ffcaca; color: #d32f2f; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.2rem;"></i>
                    <div>
                        <strong>Peringatan Sistem:</strong> Ditemukan <b><span id="anomalyCount">{{ $activeAnomaliesCount }}</span></b> anomali stok/fulfillment. Hubungi administrator untuk mengecek log <code>inventory_audit_findings</code>.
                    </div>
                </div>
                <div>
                    <button id="btnRunAudit" onclick="runInventoryAudit()" class="btn btn-sm" style="background:#d32f2f; color:white; border-radius:6px; font-weight:bold; border:none; padding: 6px 12px; cursor:pointer;">
                        <i class="bi bi-arrow-repeat"></i> Jalankan Audit
                    </button>
                </div>
            </div>

            <script>
            async function runInventoryAudit() {
                const btn = document.getElementById('btnRunAudit');
                const origText = btn.innerHTML;
                btn.innerHTML = '⏳ Menjalankan...';
                btn.disabled = true;

                try {
                    const res = await fetch('/api/dev/run-audit', { 
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    
                    if(res.ok) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Gagal: ' + (data.message || 'Error'));
                        btn.innerHTML = origText;
                        btn.disabled = false;
                    }
                } catch(e) {
                    alert('Gagal menghubungi server.');
                    btn.innerHTML = origText;
                    btn.disabled = false;
                }
            }
            </script>
        @endif

        @includeFirst(
            ['dashboard.partials.' . $role, 'dashboard.partials.generic'],
            ['d' => $d]
        )
    </div>
@endsection
