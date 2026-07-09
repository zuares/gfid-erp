@extends('layouts.app')
@section('title', 'Visitor Analytics')

@push('head')
<style>
.tab-pill { border: 1.5px solid #e2e8f0; border-radius: 999px; padding: .25rem .7rem; font-size: .72rem; font-weight: 700; text-decoration: none; color: #64748b; background: #fff; white-space: nowrap; }
.tab-pill.active, .tab-pill:hover { background: #0f172a; color: #fff; border-color: #0f172a; }
.stat-card { background:#fff; border:1.5px solid #e8ecf0; border-radius:16px; padding:1.1rem 1.25rem; }
.stat-label { font-size:.7rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; margin-bottom:.25rem; }
.stat-value { font-size:1.6rem; font-weight:900; color:#0f172a; line-height:1.1; }
.stat-sub { font-size:.72rem; color:#64748b; margin-top:.2rem; }
.crm-table th { font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; border-bottom:1.5px solid #e8ecf0; padding:.5rem .75rem; background:#f8fafc; white-space:nowrap; }
.crm-table td { font-size:.8rem; vertical-align:middle; padding:.55rem .75rem; border-bottom:1px solid #f1f5f9; }
.crm-table tr:last-child td { border-bottom:0; }
.section-card { background:#fff; border:1.5px solid #e8ecf0; border-radius:16px; overflow:hidden; }
.section-title { font-size:.78rem; font-weight:800; color:#0f172a; padding:.75rem 1rem; border-bottom:1.5px solid #f1f5f9; background:#f8fafc; }
.bar-wrap { background:#f1f5f9; border-radius:4px; height:6px; overflow:hidden; min-width:60px; }
.bar-fill { height:6px; border-radius:4px; background:#6366f1; }
.event-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:4px; }
.act-badge { display:inline-flex; align-items:center; gap:.2rem; font-size:.62rem; font-weight:800; padding:.1rem .38rem; border-radius:5px; white-space:nowrap; }
.live-dot { display:inline-block; width:7px; height:7px; border-radius:50%; background:#22c55e; animation: livePulse 1.8s ease-in-out infinite; }
.live-dot.paused { background:#94a3b8; animation:none; }
@keyframes livePulse { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:.4; transform:scale(.75); } }
.active-dot { display:inline-block;width:6px;height:6px;border-radius:50%;background:#22c55e;margin-right:4px;animation:livePulse 1.8s ease-in-out infinite; }
.flash-new { animation: flashNew .8s ease; }
@keyframes flashNew { 0% { background:#fef9c3; } 100% { background:transparent; } }
/* Internal visitor styles */
.internal-badge { display:inline-flex;align-items:center;gap:.2rem;font-size:.6rem;font-weight:800;padding:.08rem .35rem;border-radius:4px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;white-space:nowrap; }
.internal-row td { opacity: 0.55; }
.internal-row { background: repeating-linear-gradient(45deg, transparent, transparent 8px, rgba(251,191,36,.03) 8px, rgba(251,191,36,.03) 16px) !important; }
.btn-mark-internal { font-size:.6rem;padding:.1rem .4rem;border-radius:4px;border:1px solid #e2e8f0;background:transparent;cursor:pointer;color:#94a3b8;transition:all .15s;line-height:1.3; }
.btn-mark-internal:hover { border-color:#f59e0b;color:#b45309;background:#fef9c3; }
.btn-mark-internal.marked { border-color:#fde68a;background:#fef3c7;color:#92400e; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-black mb-0" style="font-size:1.05rem;">Visitor Analytics</h5>
                <span id="live-badge" style="display:inline-flex;align-items:center;gap:5px;font-size:.65rem;font-weight:800;color:#16a34a;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:999px;padding:.15rem .55rem;">
                    <span class="live-dot" id="live-dot"></span> LIVE
                </span>
            </div>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:2px;">
                Perilaku pengunjung — durasi, klik, halaman, akun ·
                <span id="last-updated">memuat...</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <form id="filter-form" method="GET" action="{{ route('admin.crm.visitors') }}" class="m-0">
                <input type="hidden" name="start_date" id="inp-start" value="{{ $startDate ?? now()->subDays($days)->format('Y-m-d') }}">
                <input type="hidden" name="end_date" id="inp-end" value="{{ $endDate ?? now()->format('Y-m-d') }}">
                <div class="input-group input-group-sm" style="width:230px; box-shadow:0 2px 6px rgba(0,0,0,0.03); border-radius:8px; overflow:hidden;">
                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-color:#e2e8f0;"><i class="bi bi-calendar3"></i></span>
                    <input type="text" id="inp-date-range" class="form-control border-start-0 ps-0" placeholder="Pilih Periode..." style="font-size:.75rem; font-weight:700; color:#475569; cursor:pointer; background:#fff; border-color:#e2e8f0;" readonly>
                </div>
            </form>
            
            <button type="button" class="btn btn-sm d-flex align-items-center" style="font-size:.75rem; font-weight:700; color:#92400e; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.02); transition:all 0.2s;"
                onclick="document.getElementById('ip-settings-panel').classList.toggle('d-none')" onmouseover="this.style.background='#fef3c7'; this.style.borderColor='#fcd34d';" onmouseout="this.style.background='#fffbeb'; this.style.borderColor='#fde68a';">
                <i class="bi bi-shield-check me-1" style="font-size:.8rem;"></i> Pengecualian IP
            </button>
            
            @if(env('APP_DB_MODE') === 'dev' && auth()->user()?->role === 'owner')
            <form method="POST" action="{{ route('admin.crm.dev_backfill_city') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm" style="font-size:.75rem; font-weight:700; color:#0369a1; background:#f0f9ff; border:1px dashed #7dd3fc; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.02);">
                    <i class="bi bi-geo-alt me-1"></i>Isi Kota <span style="font-size:.55rem; background:#bae6fd; color:#0c4a6e; border-radius:4px; padding:.1rem .3rem; margin-left:2px;">DEV</span>
                </button>
            </form>
            @endif
            
            <a href="{{ route('admin.crm.dashboard') }}" class="btn btn-sm d-flex align-items-center" style="font-size:.75rem; font-weight:600; color:#475569; background:#fff; border:1px solid #e2e8f0; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.02); transition:all 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    {{-- IP Settings Panel --}}
    <div id="ip-settings-panel" class="{{ session('success_ip') ? '' : 'd-none' }} mb-3">
        <div style="background:rgba(255,255,255,0.7); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); border:1px solid rgba(245,158,11,0.3); border-radius:16px; padding:1.25rem 1.5rem; box-shadow:0 4px 15px rgba(0,0,0,0.02);">
            <div class="row align-items-center">
                <div class="col-md-5 mb-3 mb-md-0">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div style="width:36px;height:36px;border-radius:10px;background:#fef3c7;color:#d97706;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h6 class="mb-0 fw-bold" style="color:#92400e; font-size:.9rem;">Pengecualian IP (WiFi Kantor)</h6>
                    </div>
                    <p style="font-size:.75rem; color:#b45309; line-height:1.6; margin-bottom:.75rem; opacity:0.9;">
                        Visitor yang mengakses menggunakan IP dalam daftar ini akan langsung ditandai sebagai <strong>Internal</strong> dan tidak akan dihitung ke dalam analitik pengunjung.
                    </p>
                    <div style="font-size:.7rem; color:#d97706; background:rgba(253,230,138,0.3); padding:.5rem .75rem; border-radius:8px; display:inline-block;">
                        <i class="bi bi-info-circle me-1"></i> Mendukung format: <strong>IP spesifik</strong> (192.168.1.1), <strong>CIDR</strong> (10.0.0.0/24), atau <strong>Wildcard</strong> (192.168.1.*)
                    </div>
                    @if(session('success_ip'))
                    <div class="mt-3 flash-new" style="background:#dcfce7; border:1px solid #86efac; color:#166534; border-radius:8px; padding:.5rem .75rem; font-size:.75rem; font-weight:600; display:inline-flex; align-items:center;">
                        <i class="bi bi-check-circle-fill me-2 text-success"></i> {{ session('success_ip') }}
                    </div>
                    @endif
                </div>
                <div class="col-md-7">
                    <form method="POST" action="{{ route('admin.crm.visitors.save_ip_settings') }}">
                        @csrf
                        <input type="hidden" name="days" value="{{ $days }}">
                        @if(isset($startDate) && isset($endDate))
                        <input type="hidden" name="start_date" value="{{ $startDate }}">
                        <input type="hidden" name="end_date" value="{{ $endDate }}">
                        @endif
                        <div class="p-3" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px;">
                            <label class="fw-bold mb-2 d-flex justify-content-between align-items-center" style="font-size:.75rem; color:#475569;">
                                Daftar IP (Satu IP per baris)
                                <button type="button" class="btn btn-sm" style="font-size:.7rem; padding:.2rem .5rem; background:#fff; border:1px solid #cbd5e1; color:#0369a1; border-radius:6px; font-weight:600; transition:all 0.2s;" onclick="detectMyIp()" onmouseover="this.style.borderColor='#7dd3fc'; this.style.background='#f0f9ff';" onmouseout="this.style.borderColor='#cbd5e1'; this.style.background='#fff';">
                                    <i class="bi bi-wifi me-1"></i>Deteksi IP Saya
                                </button>
                            </label>
                            <textarea name="crm_internal_ips"
                                placeholder="Contoh:&#10;192.168.1.0/24&#10;182.253.124.101"
                                style="width:100%; font-size:.8rem; font-family:'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; border:1.5px solid #cbd5e1; border-radius:8px; padding:.75rem; background:#fff; min-height:100px; resize:vertical; line-height:1.6; color:#334155; transition:border-color 0.2s;"
                                onfocus="this.style.borderColor='#f59e0b'; this.style.outline='none';"
                                onblur="this.style.borderColor='#cbd5e1';"
                            >{{ $currentInternalIps }}</textarea>
                            
                            <div id="ip-detect-result" style="font-size:.7rem; color:#d97706; margin-top:.5rem; display:none; background:#fffbeb; padding:.4rem .6rem; border-radius:6px; border:1px dashed #fcd34d;"></div>

                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-sm fw-bold px-3" style="background:linear-gradient(135deg, #f59e0b, #d97706); color:#fff; border:none; border-radius:8px; box-shadow:0 2px 8px rgba(245,158,11,0.3); font-size:.75rem; padding:.4rem 1rem; transition:transform 0.1s;" onmousedown="this.style.transform='scale(0.96)'" onmouseup="this.style.transform='scale(1)'">
                                    <i class="bi bi-save me-1"></i> Simpan Konfigurasi
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-2">
            <div class="stat-card" style="border-color:#bbf7d0;">
                <div class="stat-label" style="color:#16a34a;">Aktif Sekarang</div>
                <div class="stat-value" id="stat-active" style="color:#16a34a;">—</div>
                <div class="stat-sub">dalam 5 menit terakhir</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="stat-card">
                <div class="stat-label">Unique Visitors</div>
                <div class="stat-value" id="stat-unique">{{ number_format($uniqueVisitors) }}</div>
                <div class="stat-sub">{{ $days }} hari terakhir</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="stat-card">
                <div class="stat-label">Akun Terdaftar</div>
                <div class="stat-value" id="stat-registered" style="color:#6366f1;">{{ number_format($registeredVisitors) }}</div>
                <div class="stat-sub" id="stat-registered-pct">
                    {{ $uniqueVisitors > 0 ? number_format($registeredVisitors / $uniqueVisitors * 100, 0) : 0 }}% dari visitor
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="stat-card">
                <div class="stat-label">Return Rate</div>
                <div class="stat-value" id="stat-return">{{ $returnRate }}%</div>
                <div class="stat-sub">{{ number_format($returningVisitors) }} returning</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="stat-card">
                <div class="stat-label">Bounce Rate</div>
                <div class="stat-value" id="stat-bounce">{{ $bounceRate }}%</div>
                <div class="stat-sub">{{ $bouncedCount }} dari {{ $totalSessions }} sesi</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="stat-card" style="border-color:#fde68a;">
                <div class="stat-label" style="color:#b45309;">Internal (Kantor)</div>
                <div class="stat-value" style="color:#92400e;font-size:1.3rem;">{{ number_format($internalCount) }}</div>
                <div class="stat-sub d-flex align-items-center justify-content-between">
                    <span>tidak dihitung di atas</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Daily Visitors Chart --}}
    <div class="section-card mb-3">
        <div class="section-title">
            <i class="bi bi-graph-up me-1"></i> Unique Visitors per Hari
        </div>
        <div style="padding:1rem;">
            <canvas id="visitorsChart" height="70"></canvas>
        </div>
    </div>

    {{-- Most Visited Pages + Click Events --}}
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="section-card h-100">
                <div class="section-title"><i class="bi bi-eye me-1"></i> Halaman Paling Sering Dikunjungi</div>
                @if($pageViews->isEmpty())
                <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.82rem;">Belum ada data page view</div>
                @else
                <div style="overflow-x:auto;">
                    <table class="table mb-0 crm-table">
                        <thead>
                            <tr>
                                <th>Halaman</th>
                                <th style="text-align:right;">Views</th>
                                <th style="text-align:right;">Visitors</th>
                                <th style="min-width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                        @php $maxPv = $pageViews->max('count'); @endphp
                        @foreach($pageViews as $pv)
                        @php
                            $isAuth = in_array($pv['route'] ?? '', ['storefront.login','storefront.register','storefront.login.verify']);
                        @endphp
                        <tr>
                            <td>
                                @if($pv['slug'])
                                <span style="font-size:.65rem;background:#e0e7ff;color:#3730a3;border-radius:4px;padding:.1rem .3rem;font-weight:700;">produk</span>
                                @elseif($isAuth)
                                <span style="font-size:.65rem;background:#f3e8ff;color:#7e22ce;border-radius:4px;padding:.1rem .3rem;font-weight:700;">auth</span>
                                @endif
                                <span style="margin-left:.2rem;">{{ $pv['label'] }}</span>
                            </td>
                            <td style="text-align:right;font-weight:800;">{{ $pv['count'] }}</td>
                            <td style="text-align:right;color:#64748b;">{{ $pv['visitors'] }}</td>
                            <td>
                                <div class="bar-wrap">
                                    <div class="bar-fill" style="width:{{ $maxPv > 0 ? round($pv['count']/$maxPv*100) : 0 }}%;background:{{ $isAuth ? '#a855f7' : '#6366f1' }};"></div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <div class="col-md-6">
            <div class="section-card h-100">
                <div class="section-title"><i class="bi bi-cursor me-1"></i> Elemen Paling Banyak Diklik</div>
                @if($clickEvents->isEmpty())
                <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.82rem;">
                    Belum ada data klik.<br>
                    <span style="font-size:.72rem;">Data muncul setelah pengunjung storefront aktif klik.</span>
                </div>
                @else
                <div style="overflow-x:auto;">
                    <table class="table mb-0 crm-table">
                        <thead>
                            <tr>
                                <th>Elemen</th>
                                <th style="text-align:right;">Klik</th>
                                <th style="text-align:right;">Visitors</th>
                                <th style="min-width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                        @php
                            $maxClk = $clickEvents->max('count');
                            $clickColors = [
                                'wa_button'       => '#25d366',
                                'product_link'    => '#6366f1',
                                'product_card'    => '#8b5cf6',
                                'add_to_cart_cta' => '#f59e0b',
                                'checkout_link'   => '#0ea5e9',
                                'submit_button'   => '#10b981',
                                'cart_link'       => '#f43f5e',
                                'nav_link'        => '#94a3b8',
                                'login_btn'       => '#a855f7',
                                'register_btn'    => '#a855f7',
                            ];
                        @endphp
                        @foreach($clickEvents as $ce)
                        <tr>
                            <td>
                                <span class="event-dot" style="background:{{ $clickColors[$ce['label']] ?? '#94a3b8' }}"></span>
                                {{ str_replace('_', ' ', $ce['label']) }}
                            </td>
                            <td style="text-align:right;font-weight:800;">{{ $ce['count'] }}</td>
                            <td style="text-align:right;color:#64748b;">{{ $ce['visitors'] }}</td>
                            <td>
                                <div class="bar-wrap">
                                    <div class="bar-fill" style="width:{{ $maxClk > 0 ? round($ce['count']/$maxClk*100) : 0 }}%;background:{{ $clickColors[$ce['label']] ?? '#94a3b8' }};"></div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Page Duration + Recent Sessions --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="section-card h-100">
                <div class="section-title"><i class="bi bi-clock-history me-1"></i> Avg Durasi per Halaman</div>
                @if($pageDurations->isEmpty())
                <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.82rem;">
                    Belum ada data durasi.<br>
                    <span style="font-size:.72rem;">Muncul setelah pengunjung buka halaman storefront.</span>
                </div>
                @else
                <div style="overflow-x:auto;">
                    <table class="table mb-0 crm-table">
                        <thead>
                            <tr><th>Halaman</th><th style="text-align:right;">Avg</th><th style="text-align:right;">N</th></tr>
                        </thead>
                        <tbody>
                        @foreach($pageDurations as $pd)
                        <tr>
                            <td style="font-size:.75rem;">{{ $pd['page'] }}</td>
                            <td style="text-align:right;font-weight:800;white-space:nowrap;">
                                @if($pd['avg_sec'] >= 60)
                                    {{ floor($pd['avg_sec']/60) }}m {{ $pd['avg_sec']%60 }}s
                                @else
                                    {{ $pd['avg_sec'] }}s
                                @endif
                            </td>
                            <td style="text-align:right;color:#94a3b8;font-size:.72rem;">{{ $pd['count'] }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <div class="col-md-8">
            <div class="section-card h-100">
                <div class="section-title d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-person-lines-fill me-1"></i> Sesi Visitor Terbaru</span>
                    <div class="d-flex align-items-center gap-2">
                        @if($internalCount > 0)
                        <span style="font-size:.62rem;color:#92400e;background:#fef3c7;border:1px solid #fde68a;border-radius:4px;padding:.1rem .4rem;font-weight:700;">
                            <i class="bi bi-building me-1"></i>{{ $internalCount }} internal
                        </span>
                        @endif
                        <button type="button" class="btn-mark-internal" id="btnRescanIPs" onclick="rescanIPs(this)" title="Re-scan semua visitor berdasarkan daftar IP kantor">
                            <i class="bi bi-arrow-clockwise"></i> Rescan IP
                        </button>
                    </div>
                </div>
                @if($recentSessions->isEmpty())
                <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.82rem;">Belum ada visitor</div>
                @else
                <div style="overflow-x:auto;">
                    <table class="table mb-0 crm-table">
                        <thead>
                            <tr>
                                <th>Visitor</th>
                                <th>Kota</th>
                                <th style="text-align:right;">Pages</th>
                                <th style="text-align:right;">Waktu</th>
                                <th>Aktivitas</th>
                                <th>Terakhir</th>
                                <th title="Tandai sebagai kunjungan internal (kantor)"></th>
                            </tr>
                        </thead>
                        <tbody id="sessions-tbody">
                        @foreach($recentSessions as $s)
                        @php $v = $s['visitor']; $token14 = substr($v->visitor_token, 0, 14) . '…'; @endphp
                        <tr data-token="{{ $token14 }}" data-visitor-id="{{ $v->id }}" class="{{ $s['is_internal'] ? 'internal-row' : '' }}">
                            <td>
                                @if($s['is_internal'])
                                    <span class="internal-badge mb-1 d-inline-flex"><i class="bi bi-building"></i> Internal{{ $s['internal_reason'] === 'ip' ? ' · IP' : '' }}</span><br>
                                @endif
                                @if($v->customer_name)
                                    <div class="fw-bold" style="font-size:.78rem;">{{ $v->customer_name }}</div>
                                    @if($v->customer_phone)
                                    <div style="font-size:.65rem;color:#64748b;">{{ $v->customer_phone }}</div>
                                    @endif
                                @else
                                    <div style="font-size:.72rem;font-family:monospace;color:#94a3b8;">{{ substr($v->visitor_token, 0, 14) }}…</div>
                                    <span style="font-size:.62rem;background:#f1f5f9;color:#64748b;border-radius:4px;padding:.05rem .3rem;">Anon</span>
                                @endif
                                @php
                                    $deviceIcon  = match($s['device']) { 'mobile' => 'bi-phone', 'tablet' => 'bi-tablet', default => 'bi-laptop' };
                                    $deviceLabel = match($s['device']) { 'mobile' => 'Mobile', 'tablet' => 'Tablet', default => 'Desktop' };
                                    $deviceColor = match($s['device']) { 'mobile' => '#0ea5e9', 'tablet' => '#8b5cf6', default => '#64748b' };
                                @endphp
                                <div style="font-size:.62rem;color:{{ $deviceColor }};margin-top:2px;">
                                    <i class="bi {{ $deviceIcon }}"></i> {{ $s['brand'] !== '—' ? $s['brand'] : $deviceLabel }}
                                </div>
                            </td>
                            <td style="font-size:.75rem;color:#334155;text-transform:capitalize;">
                                {{ $v->city ? strtolower($v->city) : '—' }}
                                @if($v->province && $v->city)
                                <div style="font-size:.65rem;color:#94a3b8;">{{ $v->province }}</div>
                                @endif
                            </td>
                            <td style="text-align:right;font-weight:700;">{{ $s['page_views'] }}</td>
                            <td style="text-align:right;font-size:.75rem;color:#64748b;white-space:nowrap;">
                                @if($s['total_sec'] > 0)
                                    @if($s['total_sec'] >= 60) {{ floor($s['total_sec']/60) }}m {{ $s['total_sec']%60 }}s
                                    @else {{ $s['total_sec'] }}s @endif
                                @else —
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    @if($s['has_order'])
                                    <span class="act-badge" style="background:#dcfce7;color:#166534;"><i class="bi bi-bag-check-fill"></i> Order</span>
                                    @endif
                                    @if($s['has_account'])
                                    <span class="act-badge" style="background:#ede9fe;color:#5b21b6;"><i class="bi bi-person-check-fill"></i> Akun</span>
                                    @endif
                                    @if($s['has_cart'] && !$s['has_order'])
                                    <span class="act-badge" style="background:#fef3c7;color:#92400e;"><i class="bi bi-cart3"></i> Cart</span>
                                    @endif
                                    @if($s['has_login'] && !$s['has_account'] && !$s['has_cart'])
                                    <span class="act-badge" style="background:#f3e8ff;color:#7e22ce;"><i class="bi bi-key"></i> Login</span>
                                    @endif
                                    @if($s['has_start_shopping'])
                                    <span class="act-badge" style="background:#fee2e2;color:#991b1b;"><i class="bi bi-cart-plus-fill"></i> Mulai Belanja</span>
                                    @endif
                                    @if($s['has_marketplace'])
                                    <span class="act-badge" style="background:#ffedd5;color:#9a3412;"><i class="bi bi-shop"></i> Marketplace</span>
                                    @endif
                                    @if(!$s['has_order'] && !$s['has_cart'] && !$s['has_account'] && !$s['has_login'] && !$s['has_start_shopping'] && !$s['has_marketplace'])
                                    <span style="color:#cbd5e1;font-size:.72rem;">Browse</span>
                                    @endif
                                </div>
                            </td>
                            <td style="font-size:.7rem;color:#94a3b8;white-space:nowrap;">
                                @if($s['is_active'])<span class="active-dot"></span>@endif{{ \Carbon\Carbon::parse($v->last_seen_at)->diffForHumans() }}
                            </td>
                            <td style="white-space:nowrap;">
                                <button type="button"
                                    class="btn-mark-internal {{ $s['is_internal'] ? 'marked' : '' }}"
                                    onclick="toggleInternal(this, {{ $v->id }})"
                                    title="{{ $s['is_internal'] ? 'Hapus tanda Internal' : 'Tandai sebagai pengunjung internal (kantor)' }}">
                                    <i class="bi bi-building{{ $s['is_internal'] ? '-fill' : '' }}"></i>
                                    {{ $s['is_internal'] ? 'Internal' : 'Tandai' }}
                                </button>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>


</div>
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const labels = @json($chartLabels);
const data   = @json($chartData);

new Chart(document.getElementById('visitorsChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Unique Visitors',
            data: data,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,.08)',
            borderWidth: 2,
            pointRadius: data.length <= 14 ? 4 : 2,
            pointBackgroundColor: '#6366f1',
            tension: 0.3,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y} visitor` } }
        },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10 }, maxTicksLimit: 15 } },
            y: {
                beginAtZero: true,
                ticks: { font: { size: 10 }, precision: 0, stepSize: 1 },
                grid: { color: 'rgba(0,0,0,.04)' }
            }
        }
    }
});
</script>

<script>
// ── LIVE POLLING ────────────────────────────────────────────────────────────
(function () {
    const POLL_MS    = 10000;
    const liveUrl    = '{{ route("admin.crm.visitors.live") }}';
    const START_DATE = document.getElementById('inp-start').value;
    const END_DATE   = document.getElementById('inp-end').value;
    let pollTimer    = null;

    // Seed known tokens from initial server render
    let knownTokens = new Set(
        [...document.querySelectorAll('#sessions-tbody tr[data-token]')].map(r => r.dataset.token)
    );

    function fmtSec(sec) {
        if (!sec) return '—';
        return sec >= 60 ? Math.floor(sec / 60) + 'm ' + (sec % 60) + 's' : sec + 's';
    }

    function buildRow(s) {
        const isNew   = !knownTokens.has(s.token);
        const classes = isNew ? 'flash-new' : '';

        const deviceIcon  = s.device === 'mobile' ? 'bi-phone' : s.device === 'tablet' ? 'bi-tablet' : 'bi-laptop';
        const deviceLabel = s.device === 'mobile' ? 'Mobile' : s.device === 'tablet' ? 'Tablet' : 'Desktop';
        const deviceColor = s.device === 'mobile' ? '#0ea5e9' : s.device === 'tablet' ? '#8b5cf6' : '#64748b';
        const deviceName  = (s.brand && s.brand !== '—') ? s.brand : deviceLabel;
        const deviceHtml  = `<div style="font-size:.62rem;color:${deviceColor};margin-top:2px;"><i class="bi ${deviceIcon}"></i> ${deviceName}</div>`;

        let visitorHtml = '';
        if (s.is_internal) {
            visitorHtml += `<span class="internal-badge mb-1 d-inline-flex"><i class="bi bi-building"></i> Internal${s.internal_reason === 'ip' ? ' &middot; IP' : ''}</span><br>`;
        }
        
        if (s.name) {
            visitorHtml += `<div class="fw-bold" style="font-size:.78rem;">${s.name}</div>`;
            if (s.phone) visitorHtml += `<div style="font-size:.65rem;color:#64748b;">${s.phone}</div>`;
        } else {
            visitorHtml += `<div style="font-size:.72rem;font-family:monospace;color:#94a3b8;">${s.token}</div>`;
            visitorHtml += `<span style="font-size:.62rem;background:#f1f5f9;color:#64748b;border-radius:4px;padding:.05rem .3rem;">Anon</span>`;
        }
        visitorHtml += deviceHtml;

        let cityHtml = s.city ? s.city : '—';
        if (s.province && s.city) cityHtml += `<div style="font-size:.65rem;color:#94a3b8;">${s.province}</div>`;

        let badges = '';
        if (s.has_order)                            badges += `<span class="act-badge" style="background:#dcfce7;color:#166534;"><i class="bi bi-bag-check-fill"></i> Order</span>`;
        if (s.has_account)                          badges += `<span class="act-badge" style="background:#ede9fe;color:#5b21b6;"><i class="bi bi-person-check-fill"></i> Akun</span>`;
        if (s.has_cart && !s.has_order)             badges += `<span class="act-badge" style="background:#fef3c7;color:#92400e;"><i class="bi bi-cart3"></i> Cart</span>`;
        if (s.has_login && !s.has_account && !s.has_cart) badges += `<span class="act-badge" style="background:#f3e8ff;color:#7e22ce;"><i class="bi bi-key"></i> Login</span>`;
        if (s.has_start_shopping)                   badges += `<span class="act-badge" style="background:#fee2e2;color:#991b1b;"><i class="bi bi-cart-plus-fill"></i> Mulai Belanja</span>`;
        if (s.has_marketplace)                      badges += `<span class="act-badge" style="background:#ffedd5;color:#9a3412;"><i class="bi bi-shop"></i> Marketplace</span>`;
        if (!s.has_order && !s.has_cart && !s.has_account && !s.has_login && !s.has_start_shopping && !s.has_marketplace)
                                                    badges  = `<span style="color:#cbd5e1;font-size:.72rem;">Browse</span>`;

        let trClasses = classes;
        if (s.is_internal) trClasses += ' internal-row';

        const btnMarkInternal = `<button type="button" 
            class="btn-mark-internal ${s.is_internal ? 'marked' : ''}" 
            onclick="toggleInternal(this, ${s.visitor_id})" 
            title="${s.is_internal ? 'Hapus tanda Internal' : 'Tandai sebagai pengunjung internal (kantor)'}">
            <i class="bi bi-building${s.is_internal ? '-fill' : ''}"></i>
            ${s.is_internal ? 'Internal' : 'Tandai'}
        </button>`;

        return `<tr class="${trClasses}" data-token="${s.token}">
            <td>${visitorHtml}</td>
            <td style="font-size:.75rem;color:#334155;text-transform:capitalize;">${cityHtml}</td>
            <td style="text-align:right;font-weight:700;">${s.page_views}</td>
            <td style="text-align:right;font-size:.75rem;color:#64748b;white-space:nowrap;">${fmtSec(s.total_sec)}</td>
            <td><div class="d-flex gap-1 flex-wrap">${badges}</div></td>
            <td style="font-size:.7rem;color:#94a3b8;white-space:nowrap;">${s.is_active ? '<span class="active-dot"></span>' : ''}${s.last_seen}</td>
            <td style="white-space:nowrap;">${btnMarkInternal}</td>
        </tr>`;
    }

    function updateStats(stats) {
        document.getElementById('stat-active').textContent     = stats.active_now;
        document.getElementById('stat-unique').textContent     = stats.unique_visitors.toLocaleString('id-ID');
        document.getElementById('stat-registered').textContent = stats.registered_visitors.toLocaleString('id-ID');
        const pct = stats.unique_visitors > 0
            ? Math.round(stats.registered_visitors / stats.unique_visitors * 100) : 0;
        document.getElementById('stat-registered-pct').textContent = pct + '% dari visitor';
        document.getElementById('stat-return').textContent = stats.return_rate + '%';
        document.getElementById('stat-bounce').textContent = stats.bounce_rate + '%';
    }

    function updateSessions(sessions, fetchedAt) {
        const tbody = document.getElementById('sessions-tbody');
        if (!tbody) return;

        tbody.innerHTML = sessions.length
            ? sessions.map(s => buildRow(s)).join('')
            : '<tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:2rem;">Belum ada visitor</td></tr>';

        knownTokens = new Set(sessions.map(s => s.token));
        document.getElementById('last-updated').textContent = 'diperbarui ' + fetchedAt;
    }

    async function poll() {
        try {
            const res  = await fetch(`${liveUrl}?start_date=${START_DATE}&end_date=${END_DATE}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            updateStats(data.stats);
            updateSessions(data.sessions, data.fetched_at);
        } catch (e) {
            console.warn('[live] poll error:', e);
        }
    }

    function startPolling() {
        if (pollTimer) return;
        poll();
        pollTimer = setInterval(poll, POLL_MS);
        document.getElementById('live-dot')?.classList.remove('paused');
    }

    function stopPolling() {
        clearInterval(pollTimer);
        pollTimer = null;
        document.getElementById('live-dot')?.classList.add('paused');
        document.getElementById('last-updated').textContent = 'polling dijeda';
    }

    document.addEventListener('visibilitychange', () => {
        document.hidden ? stopPolling() : startPolling();
    });

    startPolling();

    // Initialize Flatpickr for date range selection
    if (typeof window.flatpickr !== 'undefined') {
        window.flatpickr('#inp-date-range', {
            mode: "range",
            dateFormat: "Y-m-d",
            defaultDate: [START_DATE, END_DATE],
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const from = window.flatpickr.formatDate(selectedDates[0], 'Y-m-d');
                    const to   = window.flatpickr.formatDate(selectedDates[1], 'Y-m-d');
                    document.getElementById('inp-start').value = from;
                    document.getElementById('inp-end').value = to;
                    document.getElementById('filter-form').submit();
                }
            }
        });
    }
})();

// ── Mark/unmark visitor as internal ──────────────────────────────────────────
async function toggleInternal(btn, visitorId) {
    btn.disabled = true;
    try {
        const res = await fetch(`/admin/crm/visitors/${visitorId}/toggle-internal`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Gagal');

        const row = btn.closest('tr');
        if (data.is_internal) {
            btn.classList.add('marked');
            btn.innerHTML = '<i class="bi bi-building-fill"></i> Internal';
            btn.title = 'Hapus tanda Internal';
            row.classList.add('internal-row');
            // Add badge if not already present
            const firstTd = row.querySelector('td:first-child');
            if (!firstTd.querySelector('.internal-badge')) {
                firstTd.insertAdjacentHTML('afterbegin', '<span class="internal-badge mb-1 d-inline-flex"><i class="bi bi-building"></i> Internal</span><br>');
            }
        } else {
            btn.classList.remove('marked');
            btn.innerHTML = '<i class="bi bi-building"></i> Tandai';
            btn.title = 'Tandai sebagai pengunjung internal (kantor)';
            row.classList.remove('internal-row');
            const badge = row.querySelector('.internal-badge');
            if (badge) { badge.nextSibling?.remove(); badge.remove(); }
        }

        // Flash row to confirm change
        row.classList.add('flash-new');
        setTimeout(() => row.classList.remove('flash-new'), 800);
    } catch (e) {
        alert('Error: ' + e.message);
    } finally {
        btn.disabled = false;
    }
}

// ── Bulk rescan visitors based on IP settings ─────────────────────────────────
async function rescanIPs(btn) {
    if (!confirm('Re-scan semua visitor berdasarkan daftar IP yang dikonfigurasi di Pengaturan?\nVisitor yang dimark manual tidak akan terpengaruh.')) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Scanning...';
    try {
        const res = await fetch('/admin/crm/visitors/rescan-internal', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        alert(data.message);
        if (data.updated > 0) location.reload();
    } catch (e) {
        alert('Error: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Rescan IP';
    }
}
// ── Detect current user's IP (for adding to internal list) ───────────────────
async function detectMyIp() {
    const resultEl = document.getElementById('ip-detect-result');
    resultEl.style.display = 'block';
    resultEl.textContent = 'Mendeteksi IP...';
    try {
        const res  = await fetch('https://api.ipify.org?format=json');
        const data = await res.json();
        const ip   = data.ip;
        resultEl.innerHTML = `IP Anda saat ini: <strong>${ip}</strong> — <a href="#" onclick="appendIp('${ip}');return false;" style="color:#b45309;font-weight:700;">+ Tambahkan ke daftar</a>`;
    } catch {
        resultEl.textContent = 'Tidak bisa mendeteksi IP (periksa koneksi).';
    }
}
function appendIp(ip) {
    const ta = document.querySelector('textarea[name="crm_internal_ips"]');
    const current = ta.value.trim();
    if (current.split('\n').map(s => s.trim()).includes(ip)) {
        document.getElementById('ip-detect-result').textContent = ip + ' sudah ada di daftar.';
        return;
    }
    ta.value = current ? current + '\n' + ip : ip;
    document.getElementById('ip-detect-result').textContent = ip + ' ditambahkan. Klik Simpan untuk menyimpan.';
}
</script>
@endpush
@endsection
