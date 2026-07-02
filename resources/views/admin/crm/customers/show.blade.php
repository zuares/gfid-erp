@extends('layouts.app')
@section('title', $customer->name . ' — Customer 360')

@push('head')
<style>
.crm-table th { font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;border-bottom:1.5px solid #e8ecf0;padding:.45rem .75rem;background:#f8fafc;white-space:nowrap; }
.crm-table td { font-size:.79rem;vertical-align:middle;padding:.5rem .75rem;border-bottom:1px solid #f1f5f9; }
.crm-table tr:last-child td { border-bottom:0; }
.status-badge { font-size:.65rem;font-weight:800;padding:.18rem .5rem;border-radius:999px;text-transform:uppercase; }
.status-pending    { background:#fef9c3;color:#854d0e; }
.status-confirmed  { background:#dbeafe;color:#1e40af; }
.status-processing { background:#ede9fe;color:#5b21b6; }
.status-shipped    { background:#d1fae5;color:#065f46; }
.status-done       { background:#d1fae5;color:#065f46; }
.status-cancelled  { background:#fee2e2;color:#991b1b; }
.repeat-badge { font-size:.65rem;font-weight:800;background:#fef3c7;color:#92400e;padding:.2rem .5rem;border-radius:6px; }
.vip-badge    { font-size:.65rem;font-weight:800;background:#fdf4ff;color:#7e22ce;padding:.2rem .5rem;border-radius:6px; }
.section-card { background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;overflow:hidden;margin-bottom:1rem; }
.section-title { font-size:.72rem;font-weight:900;text-transform:uppercase;letter-spacing:.07em;color:#64748b;padding:.75rem 1.1rem .6rem;border-bottom:1.5px solid #f1f5f9;background:#f8fafc; }
.info-row { display:flex;flex-direction:column;gap:.1rem;padding:.45rem 0;border-bottom:1px solid #f8fafc; }
.info-row:last-child { border-bottom:0; }
.info-label { font-size:.62rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.04em; }
.info-val { font-size:.8rem;font-weight:600;color:#0f172a; }
.event-row { display:flex;align-items:flex-start;gap:.65rem;padding:.45rem 0;border-bottom:1px solid #f8fafc; }
.event-row:last-child { border-bottom:0; }
.event-icon { width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.77rem;flex-shrink:0; }
.bar-wrap { background:#f1f5f9;border-radius:4px;height:5px;overflow:hidden;min-width:40px;flex:1; }
.bar-fill { height:5px;border-radius:4px; }
.stat-card-sm { background:#fff;border:1.5px solid #e8ecf0;border-radius:14px;padding:.85rem 1rem; }
.stat-label-sm { font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.2rem; }
.stat-val-sm { font-size:1.3rem;font-weight:900;color:#0f172a;line-height:1.1; }
.stat-sub-sm { font-size:.68rem;color:#64748b;margin-top:.15rem; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
        <a href="{{ route('admin.crm.customers') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.75rem;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="fw-black mb-0" style="font-size:1.05rem;">{{ $customer->name }}</h5>
                @if($customer->is_repeat)
                <span class="repeat-badge"><i class="bi bi-arrow-repeat me-1"></i>Repeat Buyer</span>
                @endif
                @if($customer->is_vip)
                <span class="vip-badge"><i class="bi bi-star-fill me-1"></i>VIP</span>
                @endif
            </div>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:2px;">
                {{ $customer->phone }}
                @if($customer->city) · {{ $customer->city }} @endif
                @if($visitors->count() > 1)
                · <span style="color:#6366f1;">{{ $visitors->count() }} device/browser</span>
                @endif
            </div>
        </div>
        @php
            $waNum = str_starts_with($customer->phone, '62')
                ? $customer->phone
                : '62' . ltrim($customer->phone, '0');
        @endphp
        <a href="https://wa.me/{{ $waNum }}" target="_blank"
           class="btn btn-sm fw-bold" style="background:#25d366;color:#fff;border-radius:10px;font-size:.8rem;">
            <i class="bi bi-whatsapp me-1"></i> Hubungi via WA
        </a>
    </div>

    {{-- Stats row --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-sm-4 col-md-2">
            <div class="stat-card-sm">
                <div class="stat-label-sm">CLV</div>
                <div class="stat-val-sm" style="font-size:1.05rem;">Rp{{ number_format($customer->total_spent) }}</div>
                <div class="stat-sub-sm">{{ $customer->valid_count }} order valid</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <div class="stat-card-sm">
                <div class="stat-label-sm">Total Order</div>
                <div class="stat-val-sm">{{ $customer->order_count }}</div>
                <div class="stat-sub-sm">{{ $customer->is_repeat ? 'Repeat buyer ✓' : 'Baru pertama' }}</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <div class="stat-card-sm">
                <div class="stat-label-sm">Avg / Order</div>
                <div class="stat-val-sm" style="font-size:1.05rem;">Rp{{ number_format($customer->avg_order) }}</div>
                <div class="stat-sub-sm">nilai rata-rata</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <div class="stat-card-sm">
                <div class="stat-label-sm">Customer Sejak</div>
                <div class="stat-val-sm" style="font-size:.95rem;">{{ \Carbon\Carbon::parse($customer->first_order)->format('d M Y') }}</div>
                <div class="stat-sub-sm">{{ \Carbon\Carbon::parse($customer->last_order)->diffForHumans() }} terakhir</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <div class="stat-card-sm">
                <div class="stat-label-sm">Total Waktu di Site</div>
                @php
                    $ts = $totalTimeOnSite;
                    $tsFmt = $ts >= 3600
                        ? floor($ts/3600).'j '.floor(($ts%3600)/60).'m'
                        : ($ts >= 60 ? floor($ts/60).'m '.($ts%60).'d' : $ts.'d');
                @endphp
                <div class="stat-val-sm">{{ $ts > 0 ? $tsFmt : '—' }}</div>
                <div class="stat-sub-sm">dari semua kunjungan</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <div class="stat-card-sm">
                <div class="stat-label-sm">Total Events</div>
                <div class="stat-val-sm">{{ $events->count() }}</div>
                <div class="stat-sub-sm">klik + views + order</div>
            </div>
        </div>
    </div>

    <div class="row g-3">

        {{-- Kolom kiri: Orders + Products + Pages --}}
        <div class="col-md-7">

            {{-- Riwayat Order --}}
            <div class="section-card">
                <div class="section-title"><i class="bi bi-bag-check me-1"></i>Riwayat Order</div>
                <div style="overflow-x:auto;">
                    <table class="table mb-0 crm-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Tanggal</th>
                                <th>Produk</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                            <tr>
                                <td style="font-family:monospace;font-size:.75rem;font-weight:700;">{{ $order->order_number }}</td>
                                <td style="white-space:nowrap;color:#64748b;font-size:.75rem;">
                                    {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y') }}<br>
                                    <span style="font-size:.68rem;">{{ \Carbon\Carbon::parse($order->created_at)->format('H:i') }}</span>
                                </td>
                                <td>
                                    @foreach(array_slice($order->items ?? [], 0, 2) as $item)
                                    <div style="font-size:.72rem;">{{ $item['name'] ?? '' }} ×{{ $item['qty'] ?? 1 }}</div>
                                    @endforeach
                                    @if(count($order->items ?? []) > 2)
                                    <div style="font-size:.68rem;color:#94a3b8;">+{{ count($order->items) - 2 }} lainnya</div>
                                    @endif
                                </td>
                                <td style="font-weight:800;white-space:nowrap;">Rp{{ number_format($order->total_amount) }}</td>
                                <td><span class="status-badge status-{{ $order->status }}">{{ $order->status }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Produk pernah dibeli --}}
            @if($products->count())
            <div class="section-card">
                <div class="section-title"><i class="bi bi-box-seam me-1"></i>Produk yang Pernah Dibeli</div>
                <div style="padding:.5rem 1.1rem;">
                    @foreach($products as $p)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid #f8fafc;">
                        <div style="font-size:.8rem;font-weight:600;color:#0f172a;">{{ $p['name'] }}</div>
                        <div style="font-size:.75rem;color:#64748b;">
                            <span style="font-weight:800;color:#0f172a;">{{ $p['qty'] }} pcs</span>
                            dari {{ $p['orders'] }} order
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Halaman yang dikunjungi --}}
            @if($pageVisits->count())
            <div class="section-card">
                <div class="section-title"><i class="bi bi-eye me-1"></i>Halaman yang Pernah Dikunjungi</div>
                <div style="overflow-x:auto;">
                    <table class="table mb-0 crm-table">
                        <thead>
                            <tr>
                                <th>Halaman</th>
                                <th>Tipe</th>
                                <th style="text-align:right;">Kunjungan</th>
                                <th style="min-width:70px;"></th>
                                <th>Terakhir</th>
                            </tr>
                        </thead>
                        <tbody>
                        @php $maxPv = $pageVisits->max('count'); @endphp
                        @foreach($pageVisits as $pv)
                        <tr>
                            <td style="font-size:.78rem;font-weight:600;">{{ $pv['label'] }}</td>
                            <td>
                                @if($pv['type'] === 'product_view')
                                <span style="font-size:.65rem;background:#e0e7ff;color:#3730a3;border-radius:4px;padding:.1rem .35rem;font-weight:700;">produk</span>
                                @else
                                <span style="font-size:.65rem;background:#f1f5f9;color:#64748b;border-radius:4px;padding:.1rem .35rem;">halaman</span>
                                @endif
                            </td>
                            <td style="text-align:right;font-weight:800;">{{ $pv['count'] }}×</td>
                            <td>
                                <div class="bar-wrap">
                                    <div class="bar-fill" style="width:{{ $maxPv > 0 ? round($pv['count']/$maxPv*100) : 0 }}%;background:#6366f1;"></div>
                                </div>
                            </td>
                            <td style="font-size:.7rem;color:#94a3b8;white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($pv['last_at'])->format('d M H:i') }}
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Durasi per halaman --}}
            @if($pageDurations->count())
            <div class="section-card">
                <div class="section-title"><i class="bi bi-clock-history me-1"></i>Durasi per Halaman</div>
                <div style="overflow-x:auto;">
                    <table class="table mb-0 crm-table">
                        <thead>
                            <tr>
                                <th>Halaman</th>
                                <th style="text-align:right;">Total</th>
                                <th style="text-align:right;">Avg</th>
                                <th style="text-align:right;">Sesi</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($pageDurations as $pd)
                        <tr>
                            <td style="font-size:.78rem;font-weight:600;">{{ $pd['label'] }}</td>
                            <td style="text-align:right;font-weight:800;">
                                @php $s = $pd['total_sec']; @endphp
                                {{ $s >= 60 ? floor($s/60).'m '.($s%60).'d' : $s.'d' }}
                            </td>
                            <td style="text-align:right;color:#64748b;">
                                @php $a = $pd['avg_sec']; @endphp
                                {{ $a >= 60 ? floor($a/60).'m '.($a%60).'d' : $a.'d' }}
                            </td>
                            <td style="text-align:right;color:#94a3b8;font-size:.72rem;">{{ $pd['count'] }}×</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>

        {{-- Kolom kanan: Akun + Visitor info + Klik + Timeline --}}
        <div class="col-md-5">

            {{-- Registered Account Info --}}
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-person-badge-fill me-1"></i>Akun Storefront
                </div>
                <div style="padding:.5rem 1.1rem;">
                @if($account)
                    <div class="info-row">
                        <div class="info-label">Status</div>
                        <div class="info-val">
                            @if($account->phone_verified_at)
                            <span style="font-size:.7rem;background:#d1fae5;color:#065f46;border-radius:5px;padding:.15rem .45rem;font-weight:800;">
                                <i class="bi bi-check-circle-fill me-1"></i>Terverifikasi
                            </span>
                            @else
                            <span style="font-size:.7rem;background:#fef3c7;color:#92400e;border-radius:5px;padding:.15rem .45rem;font-weight:800;">
                                <i class="bi bi-clock me-1"></i>Belum verifikasi WA
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Terdaftar Sejak</div>
                        <div class="info-val">{{ \Carbon\Carbon::parse($account->created_at)->format('d M Y H:i') }}</div>
                        <div style="font-size:.68rem;color:#94a3b8;">{{ \Carbon\Carbon::parse($account->created_at)->diffForHumans() }}</div>
                    </div>
                    @if($account->phone_verified_at)
                    <div class="info-row">
                        <div class="info-label">Verifikasi WA</div>
                        <div class="info-val">{{ \Carbon\Carbon::parse($account->phone_verified_at)->format('d M Y H:i') }}</div>
                    </div>
                    @endif
                    @if($account->email)
                    <div class="info-row">
                        <div class="info-label">Email</div>
                        <div class="info-val">{{ $account->email }}</div>
                    </div>
                    @endif
                    <div class="info-row">
                        <div class="info-label">Nomor HP (Akun)</div>
                        <div class="info-val" style="font-family:monospace;font-size:.78rem;">{{ $account->phone }}</div>
                    </div>
                @else
                    <div style="font-size:.82rem;color:#94a3b8;padding:.5rem 0;">
                        <i class="bi bi-person-x me-1"></i>Belum punya akun terdaftar
                    </div>
                    <div style="font-size:.72rem;color:#94a3b8;margin-top:.25rem;">
                        Customer ini belum login / daftar ke storefront.
                    </div>
                @endif
                </div>
            </div>

            {{-- Visitor Info --}}
            <div class="section-card">
                <div class="section-title"><i class="bi bi-person-badge me-1"></i>Info Tracking</div>
                <div style="padding:.5rem 1.1rem;">
                @if($visitor)
                    <div class="info-row">
                        <div class="info-label">Pertama Kali Lihat</div>
                        <div class="info-val">{{ \Carbon\Carbon::parse($visitor->first_seen_at)->format('d M Y H:i') }}</div>
                        <div style="font-size:.68rem;color:#94a3b8;">{{ \Carbon\Carbon::parse($visitor->first_seen_at)->diffForHumans() }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Terakhir Aktif</div>
                        <div class="info-val">{{ \Carbon\Carbon::parse($visitor->last_seen_at)->format('d M Y H:i') }}</div>
                    </div>
                    @if($visitor->utm_source)
                    <div class="info-row">
                        <div class="info-label">UTM Source / Campaign</div>
                        <div class="info-val">{{ $visitor->utm_source }}
                            @if($visitor->utm_campaign) · {{ $visitor->utm_campaign }} @endif
                        </div>
                        @if($visitor->utm_medium)
                        <div style="font-size:.68rem;color:#94a3b8;">medium: {{ $visitor->utm_medium }}</div>
                        @endif
                    </div>
                    @endif
                    @if($visitor->referrer)
                    <div class="info-row">
                        <div class="info-label">Referrer</div>
                        <div style="font-size:.72rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#334155;">{{ $visitor->referrer }}</div>
                    </div>
                    @endif
                    <div class="info-row">
                        <div class="info-label">Device</div>
                        <div class="info-val" style="font-size:.78rem;">
                            @php
                                $devIcon  = match($device) { 'mobile' => 'bi-phone', 'tablet' => 'bi-tablet', default => 'bi-laptop' };
                                $devColor = match($device) { 'mobile' => '#0ea5e9', 'tablet' => '#8b5cf6', default => '#64748b' };
                                $devLabel = $brand !== '—' ? $brand : match($device) { 'mobile' => 'Mobile', 'tablet' => 'Tablet', default => 'Desktop' };
                            @endphp
                            <i class="bi {{ $devIcon }} me-1" style="color:{{ $devColor }};"></i>
                            <span style="color:{{ $devColor }};font-weight:700;">{{ $devLabel }}</span>
                        </div>
                    </div>
                    @if($visitor->ip_address)
                    <div class="info-row">
                        <div class="info-label">IP Address</div>
                        <div class="info-val" style="font-family:monospace;font-size:.75rem;">{{ $visitor->ip_address }}</div>
                    </div>
                    @endif
                    @if($visitors->count() > 1)
                    <div class="info-row">
                        <div class="info-label">Visitor Tokens</div>
                        <div style="font-size:.7rem;color:#64748b;">{{ $visitors->count() }} token (beda device/browser)</div>
                    </div>
                    @endif
                @else
                    <div style="font-size:.82rem;color:#94a3b8;padding:.5rem 0;">Tidak ada data tracking</div>
                @endif
                </div>
            </div>

            {{-- Klik yang dilakukan --}}
            @if($clickSummary->count())
            <div class="section-card">
                <div class="section-title"><i class="bi bi-cursor me-1"></i>Elemen yang Pernah Diklik</div>
                <div style="padding:.5rem 1.1rem;">
                @php
                    $maxClk = $clickSummary->max('count');
                    $clickColors = [
                        'wa_button' => '#25d366', 'product_link' => '#6366f1',
                        'product_card' => '#8b5cf6', 'add_to_cart_cta' => '#f59e0b',
                        'checkout_link' => '#0ea5e9', 'submit_button' => '#10b981',
                        'cart_link' => '#f43f5e', 'nav_link' => '#94a3b8',
                    ];
                @endphp
                @foreach($clickSummary as $ck)
                <div style="display:flex;align-items:center;gap:.6rem;padding:.35rem 0;border-bottom:1px solid #f8fafc;">
                    <div style="width:8px;height:8px;border-radius:50%;background:{{ $clickColors[$ck['label']] ?? '#94a3b8' }};flex-shrink:0;"></div>
                    <div style="font-size:.78rem;flex:1;">{{ str_replace('_', ' ', $ck['label']) }}</div>
                    <div style="font-weight:800;font-size:.78rem;color:#0f172a;">{{ $ck['count'] }}×</div>
                    <div class="bar-wrap" style="max-width:50px;">
                        <div class="bar-fill" style="width:{{ $maxClk > 0 ? round($ck['count']/$maxClk*100) : 0 }}%;background:{{ $clickColors[$ck['label']] ?? '#94a3b8' }};"></div>
                    </div>
                </div>
                @endforeach
                </div>
            </div>
            @endif

            {{-- Event Timeline --}}
            @if($timeline->count())
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-activity me-1"></i>Timeline Aktivitas
                    <span style="font-weight:400;font-size:.68rem;color:#94a3b8;">({{ $timeline->count() }} event terbaru)</span>
                </div>
                <div style="padding:.5rem 1.1rem;max-height:350px;overflow-y:auto;">
                @foreach($timeline as $evt)
                @php
                    $evtCfg = match($evt->event_type) {
                        'page_view'      => ['icon' => 'bi-house',        'bg' => '#f8fafc', 'col' => '#94a3b8'],
                        'product_view'   => ['icon' => 'bi-eye',          'bg' => '#eff6ff', 'col' => '#3b82f6'],
                        'add_to_cart'    => ['icon' => 'bi-cart-plus',    'bg' => '#fef3c7', 'col' => '#d97706'],
                        'checkout_start' => ['icon' => 'bi-bag',          'bg' => '#f0fdf4', 'col' => '#22c55e'],
                        'order_complete' => ['icon' => 'bi-check-circle', 'bg' => '#dcfce7', 'col' => '#16a34a'],
                        'wa_click'       => ['icon' => 'bi-whatsapp',     'bg' => '#f0fdf4', 'col' => '#25d366'],
                        'click'          => ['icon' => 'bi-cursor',       'bg' => '#faf5ff', 'col' => '#8b5cf6'],
                        default          => ['icon' => 'bi-dot',          'bg' => '#f8fafc', 'col' => '#cbd5e1'],
                    };
                @endphp
                <div class="event-row">
                    <div class="event-icon" style="background:{{ $evtCfg['bg'] }};color:{{ $evtCfg['col'] }};">
                        <i class="bi {{ $evtCfg['icon'] }}"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.74rem;font-weight:700;color:#334155;">
                            {{ str_replace('_', ' ', $evt->event_type) }}
                            @if($evt->event_type === 'click' && isset($evt->payload['label']))
                            — <span style="color:#8b5cf6;">{{ str_replace('_', ' ', $evt->payload['label']) }}</span>
                            @endif
                        </div>
                        @if(isset($evt->payload['name']))
                        <div style="font-size:.68rem;color:#64748b;">{{ $evt->payload['name'] }}</div>
                        @elseif(isset($evt->payload['slug']))
                        <div style="font-size:.68rem;color:#64748b;">{{ $evt->payload['slug'] }}</div>
                        @elseif($evt->event_type === 'page_view' && isset($evt->payload['route']))
                        <div style="font-size:.68rem;color:#94a3b8;">{{ str_replace('storefront.', '', $evt->payload['route']) }}</div>
                        @endif
                        <div style="font-size:.63rem;color:#94a3b8;">{{ \Carbon\Carbon::parse($evt->created_at)->format('d M Y H:i') }}</div>
                    </div>
                </div>
                @endforeach
                </div>
            </div>
            @endif

            @if($events->isEmpty())
            <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;padding:1.5rem;text-align:center;color:#94a3b8;font-size:.82rem;">
                <i class="bi bi-graph-up" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
                Belum ada data tracking untuk customer ini
            </div>
            @endif

        </div>
    </div>

</div>
@endsection
