@extends('layouts.app')
@section('title', 'Customer Segments')

@push('head')
<style>
.seg-card { background:#fff; border:1.5px solid #e8ecf0; border-radius:16px; padding:1.1rem 1.25rem; cursor:pointer; text-decoration:none; color:inherit; display:block; transition:box-shadow .15s,transform .1s; }
.seg-card:hover { box-shadow:0 4px 20px rgba(0,0,0,.09); transform:translateY(-2px); color:inherit; }
.seg-icon { width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;flex-shrink:0; }
.seg-label { font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.1rem; }
.seg-count { font-size:1.7rem;font-weight:900;line-height:1; }
.seg-desc  { font-size:.72rem;color:#64748b;margin-top:.3rem; }
.seg-action { font-size:.7rem;font-style:italic;color:#94a3b8;margin-top:.4rem; }
.seg-meta { font-size:.72rem;color:#64748b;margin-top:.5rem;padding-top:.5rem;border-top:1px solid #f1f5f9;display:flex;gap:.75rem;flex-wrap:wrap; }
.seg-pct-bar { height:4px;border-radius:4px;margin-top:.5rem;background:#f1f5f9;overflow:hidden; }
.seg-pct-fill { height:4px;border-radius:4px; }
.summary-card { background:#fff;border:1.5px solid #e8ecf0;border-radius:14px;padding:.9rem 1.1rem; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-black mb-0" style="font-size:1.05rem;">Customer Segments</h5>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:2px;">Kelompokan customer berdasarkan perilaku belanja — klik segment untuk action</div>
        </div>
        <a href="{{ route('admin.crm.customers') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.75rem;">
            <i class="bi bi-people me-1"></i> Semua Customers
        </a>
    </div>

    {{-- Summary --}}
    <div class="row g-2 mb-4">
        <div class="col-6 col-md-3">
            <div class="summary-card">
                <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;">Total Customers</div>
                <div style="font-size:1.5rem;font-weight:900;color:#0f172a;">{{ number_format($totalCustomers) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-card">
                <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;">Total Revenue</div>
                <div style="font-size:1.5rem;font-weight:900;color:#0f172a;">Rp{{ number_format($totalRevenue) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-card">
                <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;">Avg CLV</div>
                <div style="font-size:1.5rem;font-weight:900;color:#0f172a;">
                    Rp{{ $totalCustomers > 0 ? number_format($totalRevenue / $totalCustomers) : 0 }}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="summary-card">
                <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;">Segment Aktif</div>
                <div style="font-size:1.5rem;font-weight:900;color:#0f172a;">
                    {{ $overview->filter(fn($s) => $s['count'] > 0)->count() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Segment cards (sorted by count desc) --}}
    <div class="row g-3">
        @foreach($overview->sortByDesc('count') as $seg)
        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.crm.segments.show', $seg['key']) }}" class="seg-card">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="seg-icon" style="background:{{ $seg['bg'] }};color:{{ $seg['color'] }};">
                        <i class="bi {{ $seg['icon'] }}"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="seg-label">{{ $seg['label'] }}</div>
                        <div class="d-flex align-items-baseline gap-2">
                            <div class="seg-count" style="color:{{ $seg['color'] }};">{{ $seg['count'] }}</div>
                            @if($seg['count'] > 0 && $seg['pct'] > 0)
                            <span style="font-size:.72rem;font-weight:600;color:#94a3b8;">{{ $seg['pct'] }}%</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- % of total bar --}}
                @if($seg['count'] > 0)
                <div class="seg-pct-bar">
                    <div class="seg-pct-fill" style="width:{{ $seg['pct'] }}%;background:{{ $seg['color'] }};"></div>
                </div>
                @endif

                <div class="seg-desc mt-2">{{ $seg['desc'] }}</div>
                <div class="seg-action"><i class="bi bi-arrow-right me-1"></i>{{ $seg['action'] }}</div>
                <div class="seg-meta">
                    <span><strong>Rp{{ number_format($seg['revenue']) }}</strong> revenue</span>
                    @if($seg['count'] > 0 && $seg['avg_clv'] > 0)
                    <span>avg <strong>Rp{{ number_format($seg['avg_clv']) }}</strong></span>
                    @endif
                    @if($seg['count'] > 0)
                    <span><strong>{{ $seg['has_account'] }}</strong> akun</span>
                    @endif
                </div>
            </a>
        </div>
        @endforeach
    </div>

    {{-- Legend --}}
    <div class="mt-4" style="background:#f8fafc;border-radius:12px;padding:1rem 1.25rem;">
        <div style="font-size:.72rem;font-weight:800;color:#64748b;margin-bottom:.6rem;">CARA MEMBACA SEGMENT</div>
        <div class="row g-2">
            <div class="col-md-4" style="font-size:.72rem;color:#64748b;">
                <strong style="color:#0f172a;">Recency</strong> — Kapan terakhir order<br>
                ≤30 hari = Baru, 31–90 = Aktif, 91–180 = Menjauh, >180 = Hilang
            </div>
            <div class="col-md-4" style="font-size:.72rem;color:#64748b;">
                <strong style="color:#0f172a;">Frequency</strong> — Berapa kali order<br>
                1× = New/Promising, 2× = Loyal, 3+× = Champions
            </div>
            <div class="col-md-4" style="font-size:.72rem;color:#64748b;">
                <strong style="color:#0f172a;">Monetary</strong> — Total belanja<br>
                ≥ Rp1jt = Big Spender (kalau tidak masuk kategori lain)
            </div>
        </div>
    </div>

</div>
@endsection
