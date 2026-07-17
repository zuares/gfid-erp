@extends('layouts.app')
@section('title', 'Log Aktivitas User')

@section('content')
<style>
    /* Styling khusus diselaraskan dengan Shipments */
    .card-main { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .table-list th { text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; color: #6c757d; font-weight: 600; padding: 1rem; border-bottom: 2px solid #f1f3f5; }
    .table-list td { padding: 1rem; border-bottom: 1px solid #f8f9fa; vertical-align: middle; }
    .table-list tr:last-child td { border-bottom: none; }
    
    .badge-action { font-size: 0.75rem; padding: 0.35em 0.65em; border-radius: 50rem; font-weight: 600; }
    .action-visit { background-color: #e0f2fe; color: #0284c7; }
    .action-click { background-color: #fef08a; color: #854d0e; }
    
    .filter-card { background: #f8f9fa; border-radius: 12px; padding: 1rem; border: 1px solid #e9ecef; }
    .btn-pill { border-radius: 50rem; font-weight: 500; padding: 0.4rem 1.2rem; }
    
    .insight-card { border-radius: 12px; transition: transform 0.2s; border-left: 4px solid transparent; }
    .insight-card:hover { transform: translateY(-2px); }
    .insight-warning { background-color: #fffbeb; border-color: #f59e0b; }
    .insight-info { background-color: #eff6ff; border-color: #3b82f6; }
    .insight-danger { background-color: #fef2f2; border-color: #ef4444; }
    .insight-secondary { background-color: #f8f9fa; border-color: #6c757d; }
    
    .url-path { font-family: monospace; font-size: 0.85rem; color: #495057; background: #e9ecef; padding: 0.2rem 0.4rem; border-radius: 4px; }
    .element-name { font-weight: 600; color: #212529; }
    .element-meta { font-size: 0.8rem; color: #6c757d; font-family: monospace; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Log Aktivitas User</h4>
</div>

@if(isset($insights) && count($insights) > 0)
<div class="row g-3 mb-4">
    @foreach($insights as $insight)
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100 insight-card insight-{{ $insight['type'] }}">
            <div class="card-body">
                <h6 class="text-{{ $insight['type'] }} fw-bold mb-2">
                    <i class="bi bi-lightbulb-fill me-1"></i> {{ $insight['title'] }}
                </h6>
                <p class="mb-0 small text-dark">{!! $insight['message'] !!}</p>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

<div class="filter-card mb-4">
    <form method="GET" action="{{ route('owner.activity-logs.index') }}" class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Role / Jabatan</label>
            <select name="role" class="form-select form-select-sm rounded-pill px-3" onchange="this.form.submit()">
                <option value="">Semua Role</option>
                @foreach($roles as $role)
                    <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                        {{ ucfirst($role) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Filter Pengguna</label>
            <select name="user_id" class="form-select form-select-sm rounded-pill px-3" onchange="this.form.submit()">
                <option value="">Semua Karyawan</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }} ({{ $user->role }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Jenis Interaksi</label>
            <select name="action" class="form-select form-select-sm rounded-pill px-3" onchange="this.form.submit()">
                <option value="">Semua Interaksi</option>
                <option value="visit" {{ request('action') == 'visit' ? 'selected' : '' }}>Dwell Time (Visit)</option>
                <option value="click" {{ request('action') == 'click' ? 'selected' : '' }}>UI Clicks (Click)</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Cari Halaman / Elemen</label>
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control rounded-pill-start px-3" placeholder="Contoh: /sales atau Simpan" value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary rounded-pill-end px-3">Cari</button>
            </div>
            <style>
                .rounded-pill-start { border-top-left-radius: 50rem; border-bottom-left-radius: 50rem; }
                .rounded-pill-end { border-top-right-radius: 50rem; border-bottom-right-radius: 50rem; }
            </style>
        </div>
        <div class="col-md-2">
            <a href="{{ route('owner.activity-logs.index') }}" class="btn btn-sm btn-light btn-pill border w-100 mt-4 mt-md-0">Reset Filter</a>
        </div>
    </form>
</div>

<div class="card card-main">
    <div class="card-body p-0">
        @if ($logs->count() === 0)
            <div class="text-center py-5 text-muted">
                <i class="bi bi-activity fs-1 mb-2 d-block opacity-50"></i>
                Belum ada rekaman aktivitas yang sesuai.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle table-list mb-0">
                    <thead>
                        <tr>
                            <th style="width: 150px;">Waktu</th>
                            <th style="width: 200px;">Pengguna</th>
                            <th style="width: 100px;">Interaksi</th>
                            <th style="width: 250px;">URL Path</th>
                            <th>Detail Elemen / Durasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                        <tr>
                            <td class="text-muted small">
                                {{ $log->created_at->format('d M Y') }} <br>
                                <strong>{{ $log->created_at->format('H:i:s') }}</strong>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $log->user->name ?? 'Unknown' }}</div>
                                <div class="small text-muted text-uppercase tracking-wider" style="font-size: 0.7rem;">{{ $log->role }}</div>
                            </td>
                            <td>
                                @if($log->action == 'visit')
                                    <span class="badge-action action-visit">Visit</span>
                                @else
                                    <span class="badge-action action-click">Click</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $path = str_replace(url('/'), '', $log->url) ?: '/';
                                @endphp
                                <span class="url-path text-truncate d-inline-block" style="max-width: 230px;" title="{{ $log->url }}">
                                    {{ $path }}
                                </span>
                            </td>
                            <td>
                                @if($log->action == 'visit')
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-clock-history me-2 text-muted"></i>
                                        <span class="fw-semibold text-dark">
                                            {{ $log->duration_ms ? round($log->duration_ms / 1000, 1) . ' detik' : '-' }}
                                        </span>
                                    </div>
                                @else
                                    @php
                                        // Parse target element formatting if possible
                                        $elementParts = explode(' (', $log->target_element);
                                        $elementName = $elementParts[0] ?? '-';
                                        $elementIdentity = isset($elementParts[1]) ? str_replace(')', '', $elementParts[1]) : '';
                                    @endphp
                                    <div>
                                        <span class="element-name">{{ $elementName }}</span>
                                    </div>
                                    @if($elementIdentity)
                                    <div>
                                        <span class="element-meta">{{ $elementIdentity }}</span>
                                    </div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divider border-top"></div>

            <div class="p-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
