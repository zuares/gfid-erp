@extends('layouts.app')
@section('title', 'Customers')

@push('head')
<style>
.crm-stat-card { background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;padding:1rem 1.25rem; }
.crm-stat-label { font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;margin-bottom:.3rem; }
.crm-stat-val { font-size:1.5rem;font-weight:900;color:#0f172a;line-height:1; }
.crm-stat-sub { font-size:.72rem;color:#64748b;margin-top:.2rem;font-weight:600; }
.tab-pill { border:1.5px solid #e2e8f0;border-radius:999px;padding:.25rem .7rem;font-size:.72rem;font-weight:700;text-decoration:none;color:#64748b;background:#fff; }
.tab-pill.active,.tab-pill:hover { background:#0f172a;color:#fff;border-color:#0f172a; }
.crm-table th { font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;border-bottom:1.5px solid #e8ecf0;padding:.5rem .75rem;background:#f8fafc;white-space:nowrap; }
.crm-table td { font-size:.8rem;vertical-align:middle;padding:.55rem .75rem;border-bottom:1px solid #f1f5f9; }
.crm-table tr:last-child td { border-bottom:0; }
.crm-table th a { color:#94a3b8;text-decoration:none; }
.crm-table th a:hover { color:#0f172a; }
.repeat-badge { font-size:.62rem;font-weight:800;background:#fef3c7;color:#92400e;padding:.15rem .4rem;border-radius:6px;white-space:nowrap; }
.vip-badge { font-size:.62rem;font-weight:800;background:#fdf4ff;color:#7e22ce;padding:.15rem .4rem;border-radius:6px;white-space:nowrap; }
.seg-badge { font-size:.6rem;font-weight:800;padding:.12rem .38rem;border-radius:5px;white-space:nowrap; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-black mb-0" style="font-size:1.05rem;">Customers</h5>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:2px;">Semua customer yang pernah order</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.crm.segments') }}" class="btn btn-sm btn-outline-primary" style="border-radius:10px;font-size:.75rem;">
                <i class="bi bi-diagram-3 me-1"></i> Segments
            </a>
            <a href="{{ route('admin.crm.dashboard') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.75rem;">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="crm-stat-card">
                <div class="crm-stat-label">Total Customers</div>
                <div class="crm-stat-val">{{ number_format($totalCustomers) }}</div>
                <div class="crm-stat-sub">unique nomor HP</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="crm-stat-card">
                <div class="crm-stat-label">Repeat Buyers</div>
                <div class="crm-stat-val" style="color:#f59e0b;">{{ number_format($repeatBuyers) }}</div>
                <div class="crm-stat-sub">{{ $totalCustomers > 0 ? number_format($repeatBuyers / $totalCustomers * 100, 1) : 0 }}% dari total customer</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="crm-stat-card">
                <div class="crm-stat-label">Avg CLV</div>
                <div class="crm-stat-val">Rp{{ number_format($avgClv / 1000, 0, ',', '.') }}K</div>
                <div class="crm-stat-sub">rata-rata total belanja per customer</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="crm-stat-card">
                <div class="crm-stat-label">Total Revenue</div>
                <div class="crm-stat-val">Rp{{ number_format($totalRevenue / 1000000, 1, ',', '.') }}JT</div>
                <div class="crm-stat-sub">dari semua order valid</div>
            </div>
        </div>
    </div>

    {{-- Search + filter --}}
    <div class="d-flex gap-2 flex-wrap align-items-center mb-3">
        <form method="GET" action="{{ route('admin.crm.customers') }}" class="d-flex gap-2">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama atau nomor HP…"
                   class="form-control form-control-sm" style="max-width:240px;border-radius:10px;font-size:.8rem;">
            <button type="submit" class="btn btn-sm btn-dark" style="border-radius:10px;font-size:.8rem;">Cari</button>
            @if($search)
            <a href="{{ route('admin.crm.customers', ['sort' => $sort]) }}"
               class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.8rem;">Reset</a>
            @endif
        </form>
        <a href="{{ route('admin.crm.customers', array_merge(request()->query(), ['repeat_only' => $repeat ? 0 : 1])) }}"
           class="tab-pill ms-auto {{ $repeat ? 'active' : '' }}">
            <i class="bi bi-arrow-repeat me-1"></i>Repeat Buyers Only
        </a>
    </div>

    {{-- Table --}}
    <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table class="table mb-0 crm-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Kota</th>
                        <th>
                            <a href="{{ route('admin.crm.customers', array_merge(request()->query(), ['sort' => 'order_count'])) }}">
                                Orders {{ $sort === 'order_count' ? '↓' : '' }}
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('admin.crm.customers', array_merge(request()->query(), ['sort' => 'total_spent'])) }}">
                                CLV {{ $sort === 'total_spent' ? '↓' : '' }}
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('admin.crm.customers', array_merge(request()->query(), ['sort' => 'last_order'])) }}">
                                Terakhir Order {{ $sort === 'last_order' ? '↓' : '' }}
                            </a>
                        </th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php $segDefs = \App\Http\Controllers\Admin\StorefrontSegmentController::segments(); @endphp
                    @forelse($customers as $c)
                    @php
                        $daysSince = now()->diffInDays($c->last_order_at);
                        $segKey    = \App\Http\Controllers\Admin\StorefrontSegmentController::classify((int)$c->order_count, $daysSince, (float)$c->total_spent);
                        $segDef    = $segDefs[$segKey] ?? null;
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div>
                                    <div class="fw-bold" style="font-size:.82rem;">{{ $c->customer_name }}</div>
                                    <div style="font-size:.72rem;color:#64748b;">{{ $c->customer_phone }}</div>
                                    <div class="d-flex gap-1 mt-1 flex-wrap">
                                        @if($segDef)
                                        <a href="{{ route('admin.crm.segments.show', $segKey) }}"
                                           class="seg-badge" style="background:{{ $segDef['bg'] }};color:{{ $segDef['color'] }};text-decoration:none;">
                                            <i class="bi {{ $segDef['icon'] }} me-1"></i>{{ $segDef['label'] }}
                                        </a>
                                        @endif
                                        @if($c->order_count > 1)
                                        <span class="repeat-badge"><i class="bi bi-arrow-repeat me-1"></i>×{{ $c->order_count }}</span>
                                        @endif
                                        @if($c->total_spent >= 1000000)
                                        <span class="vip-badge"><i class="bi bi-star-fill me-1"></i>VIP</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="font-size:.78rem;color:#334155;text-transform:capitalize;">{{ $c->city ? strtolower($c->city) : '—' }}</td>
                        <td style="text-align:center;font-weight:800;">{{ $c->order_count }}</td>
                        <td style="font-weight:800;white-space:nowrap;">Rp{{ number_format($c->total_spent) }}</td>
                        <td style="font-size:.72rem;color:#64748b;white-space:nowrap;">
                            {{ \Carbon\Carbon::parse($c->last_order_at)->diffForHumans() }}<br>
                            <span style="font-size:.68rem;">{{ \Carbon\Carbon::parse($c->last_order_at)->format('d M Y') }}</span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.crm.customers.show', $c->customer_phone) }}"
                                   class="btn btn-sm btn-dark" style="border-radius:8px;font-size:.7rem;padding:.2rem .55rem;">
                                    <i class="bi bi-person-lines-fill"></i> Detail
                                </a>
                                <a href="https://wa.me/62{{ ltrim($c->customer_phone, '0') }}" target="_blank"
                                   class="btn btn-sm" style="background:#25d366;color:#fff;border-radius:8px;font-size:.7rem;padding:.2rem .5rem;">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:2rem;color:#94a3b8;font-size:.85rem;">
                            Belum ada customer
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($customers->hasPages())
    <div class="mt-3 d-flex justify-content-center">
        {{ $customers->links() }}
    </div>
    @endif

</div>
@endsection
