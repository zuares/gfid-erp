@extends('layouts.app')
@section('title', 'CRM Dashboard')

@push('head')
<style>
.crm-period-btn { border: 1.5px solid #e2e8f0; border-radius: 999px; padding: .3rem .85rem; font-size: .75rem; font-weight: 700; background: #fff; color: #0f172a; cursor: pointer; text-decoration: none; transition: all .15s; }
.crm-period-btn.active, .crm-period-btn:hover { background: #0f172a; color: #fff; border-color: #0f172a; }
.crm-stat-card { background: #fff; border: 1.5px solid #e8ecf0; border-radius: 16px; padding: 1.1rem 1.25rem; }
.crm-stat-label { font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .07em; color: #94a3b8; margin-bottom: .35rem; }
.crm-stat-val { font-size: 1.6rem; font-weight: 900; color: #0f172a; line-height: 1; }
.crm-stat-sub { font-size: .72rem; color: #64748b; margin-top: .25rem; font-weight: 600; }
.funnel-row { display: flex; align-items: stretch; gap: 0; }
.funnel-step { flex: 1; background: #f8fafc; border: 1.5px solid #e2e8f0; border-right: 0; padding: .9rem 1rem; text-align: center; position: relative; }
.funnel-step:first-child { border-radius: 12px 0 0 12px; }
.funnel-step:last-child { border-right: 1.5px solid #e2e8f0; border-radius: 0 12px 12px 0; }
.funnel-step-label { font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: .3rem; }
.funnel-step-val { font-size: 1.35rem; font-weight: 900; color: #0f172a; }
.funnel-step-pct { font-size: .68rem; font-weight: 700; color: #22c55e; margin-top: .15rem; }
.funnel-arrow { position: absolute; right: -10px; top: 50%; transform: translateY(-50%); z-index: 2; color: #cbd5e1; font-size: .75rem; }
.crm-section-title { font-size: .75rem; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; color: #64748b; margin-bottom: .75rem; }
.top-prod-row { display: flex; align-items: center; gap: .75rem; padding: .55rem 0; border-bottom: 1px solid #f1f5f9; }
.top-prod-row:last-child { border-bottom: 0; }
.top-prod-bar-wrap { flex: 1; background: #f1f5f9; border-radius: 999px; height: 6px; overflow: hidden; }
.top-prod-bar { background: #0f172a; border-radius: 999px; height: 100%; }
.top-prod-name { font-size: .78rem; font-weight: 700; min-width: 140px; }
.top-prod-cnt { font-size: .75rem; font-weight: 800; color: #0f172a; min-width: 28px; text-align: right; }
.city-row { display: flex; align-items: center; justify-content: space-between; padding: .45rem 0; border-bottom: 1px solid #f1f5f9; font-size: .8rem; }
.city-row:last-child { border-bottom: 0; }
.city-name { font-weight: 600; color: #334155; text-transform: capitalize; }
.city-cnt { font-weight: 800; color: #0f172a; }
.utm-row { display:flex;align-items:center;gap:.6rem;padding:.45rem 0;border-bottom:1px solid #f1f5f9;font-size:.8rem; }
.utm-row:last-child { border-bottom:0; }
.utm-source-badge { font-size:.65rem;font-weight:800;padding:.15rem .4rem;border-radius:6px;background:#e0f2fe;color:#0369a1;min-width:60px;text-align:center; }
.utm-bar-wrap { flex:1;background:#f1f5f9;border-radius:999px;height:5px;overflow:hidden; }
.utm-bar { background:#3b82f6;border-radius:999px;height:100%; }
.peak-bar-wrap { display:flex;align-items:flex-end;gap:2px;height:64px; }
.peak-bar { flex:1;background:#0f172a;border-radius:2px 2px 0 0;min-height:0;transition:opacity .15s; }
.peak-bar:hover { opacity:.7; }
.peak-label-wrap { display:flex;gap:2px;margin-top:3px; }
.peak-label { flex:1;font-size:.5rem;text-align:center;color:#94a3b8; }
.dev-bar-wrap { height:8px;border-radius:999px;overflow:hidden;background:#f1f5f9;display:flex;gap:1px;margin:.4rem 0; }
.conv-table th { font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;padding:.4rem .6rem;border-bottom:1.5px solid #f1f5f9;white-space:nowrap; }
.conv-table td { font-size:.78rem;padding:.45rem .6rem;border-bottom:1px solid #f8fafc;vertical-align:middle; }
.conv-table tr:last-child td { border-bottom:0; }
.conv-bar { height:4px;border-radius:999px;min-width:2px; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Dev reset flash --}}
    @if(session('dev_reset_done'))
    <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3 py-2" style="border-radius:12px;font-size:.82rem;border:1.5px solid #bbf7d0;background:#f0fdf4;color:#166534;">
        <i class="bi bi-check-circle-fill"></i>
        <span>Semua data storefront berhasil direset.</span>
        <button type="button" class="btn-close btn-close-sm ms-auto" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-black mb-0" style="font-size:1.05rem;">CRM Dashboard</h5>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:2px;">Visitor tracking & analisa calon konsumen</div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @foreach([1=>'Hari ini', 7=>'7 Hari', 30=>'30 Hari', 90=>'90 Hari'] as $d => $label)
                <a href="{{ route('admin.crm.dashboard', ['days' => $d]) }}"
                   class="crm-period-btn {{ $days == $d ? 'active' : '' }}">{{ $label }}</a>
            @endforeach

            @if(env('APP_DB_MODE') === 'dev' && auth()->user()?->role === 'owner')
            <form method="POST" action="{{ route('admin.crm.dev_backfill_city') }}">
                @csrf
                <button type="submit"
                        class="btn btn-sm fw-bold"
                        style="background:#f0f9ff;color:#0369a1;border:1.5px dashed #7dd3fc;border-radius:10px;font-size:.72rem;padding:.25rem .7rem;">
                    <i class="bi bi-geo-alt me-1"></i>Isi Kota
                    <span style="font-size:.6rem;background:#bae6fd;color:#0c4a6e;border-radius:4px;padding:.05rem .3rem;margin-left:4px;font-weight:900;">DEV</span>
                </button>
            </form>
            <form method="POST" action="{{ route('admin.crm.dev_reset') }}"
                  onsubmit="return confirm('⚠️ RESET semua data storefront?\n\nIni akan menghapus:\n• storefront_customers\n• storefront_orders\n• storefront_visitors\n• storefront_events\n\nTidak bisa di-undo!')">
                @csrf
                <button type="submit"
                        class="btn btn-sm fw-bold"
                        style="background:#fff0f0;color:#b91c1c;border:1.5px dashed #fca5a5;border-radius:10px;font-size:.72rem;padding:.25rem .7rem;letter-spacing:.02em;">
                    <i class="bi bi-trash3 me-1"></i>Reset Data
                    <span style="font-size:.6rem;background:#fecaca;color:#991b1b;border-radius:4px;padding:.05rem .3rem;margin-left:4px;font-weight:900;">DEV</span>
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Revenue stats --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="crm-stat-card">
                <div class="crm-stat-label">Total Revenue</div>
                <div class="crm-stat-val">Rp{{ number_format($revenue['total'] / 1000, 0, ',', '.') }}K</div>
                <div class="crm-stat-sub">{{ $funnel['orders'] }} order selesai</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="crm-stat-card">
                <div class="crm-stat-label">Avg Order</div>
                <div class="crm-stat-val">Rp{{ number_format($revenue['avg'] / 1000, 0, ',', '.') }}K</div>
                <div class="crm-stat-sub">per transaksi</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="crm-stat-card">
                <div class="crm-stat-label">Pending</div>
                <div class="crm-stat-val" style="{{ $revenue['pending'] > 0 ? 'color:#f59e0b' : '' }}">{{ $revenue['pending'] }}</div>
                <div class="crm-stat-sub">order belum dikonfirmasi</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="crm-stat-card">
                <div class="crm-stat-label">Konversi</div>
                <div class="crm-stat-val">{{ $funnel['visitors'] > 0 ? number_format($funnel['orders'] / $funnel['visitors'] * 100, 1) : 0 }}%</div>
                <div class="crm-stat-sub">visitor → order</div>
            </div>
        </div>
    </div>

    {{-- Registered accounts summary --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="crm-stat-card">
                <div class="crm-stat-label">Akun Terdaftar</div>
                <div class="crm-stat-val" style="color:#6366f1;">{{ number_format($registeredTotal) }}</div>
                <div class="crm-stat-sub">total sejak awal</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="crm-stat-card">
                <div class="crm-stat-label">Terverifikasi WA</div>
                <div class="crm-stat-val" style="color:#10b981;">{{ number_format($registeredVerified) }}</div>
                <div class="crm-stat-sub">{{ $registeredTotal > 0 ? number_format($registeredVerified / $registeredTotal * 100, 0) : 0 }}% dari terdaftar</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="crm-stat-card">
                <div class="crm-stat-label">Daftar periode ini</div>
                <div class="crm-stat-val">{{ number_format($funnel['registered']) }}</div>
                <div class="crm-stat-sub">dalam {{ $days }} hari</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="crm-stat-card">
                <div class="crm-stat-label">Konversi Register</div>
                <div class="crm-stat-val">{{ $funnel['registered'] > 0 ? number_format($funnel['orders'] / $funnel['registered'] * 100, 0) : 0 }}%</div>
                <div class="crm-stat-sub">akun → order (periode ini)</div>
            </div>
        </div>
    </div>

    {{-- Funnel --}}
    <div class="mb-3">
        <div class="crm-section-title">Funnel Konversi</div>
        <div class="funnel-row">
            @php
                $steps = [
                    ['label' => 'Visitor',      'val' => $funnel['visitors'],    'prev' => null,                   'color' => null],
                    ['label' => 'Lihat Produk', 'val' => $funnel['product_view'],'prev' => $funnel['visitors'],    'color' => null],
                    ['label' => 'Add to Cart',  'val' => $funnel['add_to_cart'], 'prev' => $funnel['product_view'],'color' => null],
                    ['label' => 'Daftar Akun',  'val' => $funnel['registered'],  'prev' => $funnel['add_to_cart'], 'color' => '#6366f1'],
                    ['label' => 'Checkout',     'val' => $funnel['checkout'],    'prev' => $funnel['add_to_cart'], 'color' => null],
                    ['label' => 'Order',        'val' => $funnel['orders'],      'prev' => $funnel['checkout'],    'color' => null],
                    ['label' => 'WA Klik',      'val' => $funnel['wa_click'],    'prev' => $funnel['orders'],      'color' => '#25d366'],
                ];
            @endphp
            @foreach($steps as $i => $step)
            <div class="funnel-step">
                <div class="funnel-step-label">{{ $step['label'] }}</div>
                <div class="funnel-step-val" @if($step['color']) style="color:{{ $step['color'] }}" @endif>
                    {{ number_format($step['val']) }}
                </div>
                @if($step['prev'] !== null)
                <div class="funnel-step-pct">
                    {{ $step['prev'] > 0 ? number_format($step['val'] / $step['prev'] * 100, 0) . '%' : '—' }}
                </div>
                @endif
                @if($i < count($steps) - 1)
                <span class="funnel-arrow"><i class="bi bi-chevron-right"></i></span>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- Top products & Kota --}}
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;padding:1rem 1.25rem;">
                <div class="crm-section-title">Top Produk (Add to Cart)</div>
                @forelse($topProducts as $i => $prod)
                <div class="top-prod-row">
                    <div class="top-prod-name">{{ $prod['name'] }}</div>
                    <div class="top-prod-bar-wrap">
                        <div class="top-prod-bar" style="width:{{ $topProducts->first()['count'] > 0 ? round($prod['count'] / $topProducts->first()['count'] * 100) : 0 }}%"></div>
                    </div>
                    <div class="top-prod-cnt">{{ $prod['count'] }}x</div>
                </div>
                @empty
                <div style="color:#94a3b8;font-size:.8rem;padding:.5rem 0;">Belum ada data</div>
                @endforelse
            </div>
        </div>
        <div class="col-md-6">
            <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;padding:1rem 1.25rem;">
                <div class="crm-section-title">Sebaran Kota (dari Orders)</div>
                @forelse($topCities as $city)
                <div class="city-row">
                    <span class="city-name">{{ strtolower($city->city) }}</span>
                    <span class="city-cnt">{{ $city->total }}</span>
                </div>
                @empty
                <div style="color:#94a3b8;font-size:.8rem;padding:.5rem 0;">Belum ada data</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Returning vs New + UTM Sources --}}
    <div class="row g-3 mb-3">

        {{-- Returning vs New --}}
        <div class="col-md-4">
            <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;padding:1rem 1.25rem;height:100%;">
                <div class="crm-section-title">New vs Returning Visitor</div>
                @php
                    $totalActive = $funnel['visitors'] + $returningCount;
                    $newPct      = $totalActive > 0 ? round($funnel['visitors'] / $totalActive * 100) : 0;
                    $retPct      = $totalActive > 0 ? round($returningCount / $totalActive * 100) : 0;
                @endphp
                <div class="d-flex gap-2 mb-2">
                    <div style="flex:1;background:#f8fafc;border-radius:12px;padding:.65rem .75rem;text-align:center;">
                        <div style="font-size:1.25rem;font-weight:900;color:#0f172a;">{{ number_format($funnel['visitors']) }}</div>
                        <div style="font-size:.65rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">New</div>
                        <div style="font-size:.7rem;font-weight:700;color:#3b82f6;">{{ $newPct }}%</div>
                    </div>
                    <div style="flex:1;background:#f8fafc;border-radius:12px;padding:.65rem .75rem;text-align:center;">
                        <div style="font-size:1.25rem;font-weight:900;color:#0f172a;">{{ number_format($returningCount) }}</div>
                        <div style="font-size:.65rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;">Returning</div>
                        <div style="font-size:.7rem;font-weight:700;color:#22c55e;">{{ $retPct }}%</div>
                    </div>
                </div>
                {{-- Mini bar --}}
                <div style="height:6px;border-radius:999px;overflow:hidden;display:flex;gap:2px;">
                    <div style="width:{{ $newPct }}%;background:#3b82f6;border-radius:999px;"></div>
                    <div style="width:{{ $retPct }}%;background:#22c55e;border-radius:999px;"></div>
                </div>
                <div style="font-size:.68rem;color:#94a3b8;margin-top:.4rem;">Total aktif periode ini: <strong style="color:#0f172a;">{{ number_format($totalActive) }}</strong></div>
            </div>
        </div>

        {{-- UTM Sources --}}
        <div class="col-md-8">
            <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;padding:1rem 1.25rem;height:100%;">
                <div class="crm-section-title">Sumber Traffic (UTM)</div>
                @php
                    $totalUtm = $utmSources->sum('total');
                    $utmMax   = $utmSources->max('total') ?: 1;
                @endphp
                <div class="row g-2">
                    <div class="col-md-6">
                        {{-- Organic vs UTM --}}
                        <div class="utm-row" style="border:0;padding:.3rem 0;">
                            <span class="utm-source-badge" style="background:#f0fdf4;color:#15803d;">organic</span>
                            <div class="utm-bar-wrap">
                                <div class="utm-bar" style="width:{{ ($organicCount + $totalUtm) > 0 ? round($organicCount / ($organicCount + $totalUtm) * 100) : 0 }}%;background:#22c55e;"></div>
                            </div>
                            <span style="font-size:.75rem;font-weight:800;color:#0f172a;min-width:28px;text-align:right;">{{ $organicCount }}</span>
                        </div>
                        @forelse($utmSources as $src)
                        <div class="utm-row">
                            <span class="utm-source-badge">{{ $src->utm_source }}</span>
                            <div class="utm-bar-wrap">
                                <div class="utm-bar" style="width:{{ round($src->total / $utmMax * 100) }}%;"></div>
                            </div>
                            <span style="font-size:.75rem;font-weight:800;color:#0f172a;min-width:28px;text-align:right;">{{ $src->total }}</span>
                        </div>
                        @empty
                        <div style="color:#94a3b8;font-size:.78rem;padding:.5rem 0;">Belum ada traffic dari UTM</div>
                        @endforelse
                    </div>
                    <div class="col-md-6">
                        <div style="font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.5rem;">Top Campaigns</div>
                        @forelse($utmCampaigns as $camp)
                        <div class="utm-row">
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:.75rem;font-weight:700;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $camp->utm_campaign }}</div>
                                <div style="font-size:.65rem;color:#94a3b8;">via {{ $camp->utm_source ?? '—' }}</div>
                            </div>
                            <span style="font-size:.75rem;font-weight:800;color:#0f172a;margin-left:.5rem;">{{ $camp->total }}</span>
                        </div>
                        @empty
                        <div style="color:#94a3b8;font-size:.78rem;padding:.5rem 0;">Belum ada campaign data</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Peak Hours --}}
    <div class="mb-3">
        <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;padding:1rem 1.25rem;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="crm-section-title mb-0">Peak Hours (Jam Paling Aktif)</div>
                @php
                    $peakHour = $peakHours->sortByDesc('total')->keys()->first();
                @endphp
                @if($peakHours->count())
                <div style="font-size:.72rem;color:#64748b;">
                    Paling ramai: <strong style="color:#0f172a;">{{ str_pad($peakHour ?? 0, 2, '0', STR_PAD_LEFT) }}:00–{{ str_pad(($peakHour ?? 0) + 1, 2, '0', STR_PAD_LEFT) }}:00</strong>
                    ({{ $peakHours->get($peakHour)?->total ?? 0 }} events)
                </div>
                @endif
            </div>
            <div class="peak-bar-wrap">
                @for($h = 0; $h < 24; $h++)
                @php $cnt = $peakHours->get($h)?->total ?? 0; @endphp
                <div class="peak-bar"
                     style="height:{{ $maxHourCount > 0 ? max(0, round($cnt / $maxHourCount * 100)) : 0 }}%;{{ $cnt === 0 ? 'background:#f1f5f9;' : '' }}"
                     title="{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00 — {{ $cnt }} events"></div>
                @endfor
            </div>
            <div class="peak-label-wrap">
                @for($h = 0; $h < 24; $h++)
                <div class="peak-label">{{ $h % 3 === 0 ? str_pad($h, 2, '0', STR_PAD_LEFT) : '' }}</div>
                @endfor
            </div>
        </div>
    </div>

    {{-- Mobile vs Desktop + Avg Cart→Order --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;padding:1rem 1.25rem;">
                <div class="crm-section-title">Perangkat</div>
                @php
                    $totalDev = $mobileCount + $desktopCount;
                    $mobPct   = $totalDev > 0 ? round($mobileCount / $totalDev * 100) : 0;
                    $dskPct   = 100 - $mobPct;
                @endphp
                <div class="d-flex gap-2 mb-1">
                    <div style="flex:1;text-align:center;">
                        <div style="font-size:1.3rem;font-weight:900;color:#0f172a;">{{ $mobPct }}%</div>
                        <div style="font-size:.65rem;font-weight:800;color:#94a3b8;text-transform:uppercase;"><i class="bi bi-phone me-1"></i>Mobile</div>
                        <div style="font-size:.7rem;color:#64748b;">{{ number_format($mobileCount) }} visitor</div>
                    </div>
                    <div style="flex:1;text-align:center;">
                        <div style="font-size:1.3rem;font-weight:900;color:#0f172a;">{{ $dskPct }}%</div>
                        <div style="font-size:.65rem;font-weight:800;color:#94a3b8;text-transform:uppercase;"><i class="bi bi-display me-1"></i>Desktop</div>
                        <div style="font-size:.7rem;color:#64748b;">{{ number_format($desktopCount) }} visitor</div>
                    </div>
                </div>
                <div class="dev-bar-wrap">
                    <div style="width:{{ $mobPct }}%;background:#8b5cf6;border-radius:999px;"></div>
                    <div style="width:{{ $dskPct }}%;background:#e2e8f0;border-radius:999px;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;padding:1rem 1.25rem;height:100%;">
                <div class="crm-section-title">Rata-rata Add to Cart → Order</div>
                @if($avgCartToOrderMinutes !== null)
                @php
                    if ($avgCartToOrderMinutes < 60) {
                        $avgLabel = $avgCartToOrderMinutes . ' menit';
                    } elseif ($avgCartToOrderMinutes < 1440) {
                        $avgLabel = round($avgCartToOrderMinutes / 60, 1) . ' jam';
                    } else {
                        $avgLabel = round($avgCartToOrderMinutes / 1440, 1) . ' hari';
                    }
                @endphp
                <div style="font-size:2rem;font-weight:900;color:#0f172a;line-height:1.1;">{{ $avgLabel }}</div>
                <div style="font-size:.72rem;color:#64748b;margin-top:.3rem;">
                    dari pertama add to cart sampai order selesai<br>
                    <span style="font-weight:700;color:#0f172a;">{{ $avgCartToOrderSampleSize }}</span> order dianalisa
                </div>
                @else
                <div style="font-size:.85rem;color:#94a3b8;margin-top:.5rem;">Belum ada data cukup</div>
                <div style="font-size:.72rem;color:#94a3b8;margin-top:.25rem;">Perlu minimal 1 order yang tercatat lengkap</div>
                @endif
            </div>
        </div>
        <div class="col-md-4">
            <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;padding:1rem 1.25rem;height:100%;">
                <div class="crm-section-title">Ringkasan Konversi</div>
                @php
                    $convRate = $funnel['visitors'] > 0 ? $funnel['orders'] / $funnel['visitors'] * 100 : 0;
                    $cartRate = $funnel['visitors'] > 0 ? $funnel['add_to_cart'] / $funnel['visitors'] * 100 : 0;
                @endphp
                <div class="d-flex flex-column gap-2">
                    <div>
                        <div style="font-size:.65rem;font-weight:800;color:#94a3b8;text-transform:uppercase;">Visitor → Cart</div>
                        <div style="font-size:1.1rem;font-weight:900;color:#3b82f6;">{{ number_format($cartRate, 1) }}%</div>
                    </div>
                    <div>
                        <div style="font-size:.65rem;font-weight:800;color:#94a3b8;text-transform:uppercase;">Visitor → Order</div>
                        <div style="font-size:1.1rem;font-weight:900;color:#22c55e;">{{ number_format($convRate, 1) }}%</div>
                    </div>
                    <div>
                        <div style="font-size:.65rem;font-weight:800;color:#94a3b8;text-transform:uppercase;">Cart → Order</div>
                        <div style="font-size:1.1rem;font-weight:900;color:#f59e0b;">{{ $funnel['add_to_cart'] > 0 ? number_format($funnel['orders'] / $funnel['add_to_cart'] * 100, 1) : 0 }}%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Konversi Per Produk --}}
    <div class="mb-3">
        <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;overflow:hidden;">
            <div style="padding:.85rem 1.25rem .5rem;border-bottom:1.5px solid #f1f5f9;">
                <div class="crm-section-title mb-0">Konversi Per Produk</div>
                <div style="font-size:.7rem;color:#94a3b8;margin-top:.15rem;">View → Add to Cart → Order</div>
            </div>
            <div style="overflow-x:auto;">
                <table class="table mb-0 conv-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th style="text-align:right;">Views</th>
                            <th style="min-width:80px;"></th>
                            <th style="text-align:right;">Cart</th>
                            <th style="text-align:right;">View→Cart</th>
                            <th style="text-align:right;">Order</th>
                            <th style="text-align:right;">Cart→Order</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $maxViews = $productConversion->max('views') ?: 1; @endphp
                        @forelse($productConversion as $p)
                        <tr>
                            <td>
                                <div class="fw-bold" style="font-size:.78rem;">{{ $p['name'] }}</div>
                                <div style="font-size:.65rem;font-family:monospace;color:#94a3b8;">{{ $p['slug'] }}</div>
                            </td>
                            <td style="text-align:right;font-weight:800;">{{ $p['views'] }}</td>
                            <td>
                                <div class="conv-bar" style="width:{{ max(4, round($p['views'] / $maxViews * 100)) }}%;background:#3b82f6;"></div>
                            </td>
                            <td style="text-align:right;font-weight:800;">{{ $p['carts'] }}</td>
                            <td style="text-align:right;">
                                @if($p['views'] > 0)
                                <span style="font-size:.72rem;font-weight:700;color:{{ ($p['carts']/$p['views']*100) >= 20 ? '#22c55e' : '#f59e0b' }};">
                                    {{ number_format($p['carts'] / $p['views'] * 100, 0) }}%
                                </span>
                                @else <span style="color:#e2e8f0;">—</span> @endif
                            </td>
                            <td style="text-align:right;font-weight:800;">{{ $p['orders'] }}</td>
                            <td style="text-align:right;">
                                @if($p['carts'] > 0)
                                <span style="font-size:.72rem;font-weight:700;color:{{ ($p['orders']/$p['carts']*100) >= 30 ? '#22c55e' : '#f59e0b' }};">
                                    {{ number_format($p['orders'] / $p['carts'] * 100, 0) }}%
                                </span>
                                @else <span style="color:#e2e8f0;">—</span> @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:1.5rem;color:#94a3b8;font-size:.8rem;">
                                Belum ada data product view. Mulai tracking dengan visit storefront.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Quick links --}}
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.crm.orders', ['status' => 'pending']) }}"
           class="btn btn-sm btn-warning fw-bold" style="border-radius:10px;">
            <i class="bi bi-clock me-1"></i> {{ $revenue['pending'] }} Order Pending
        </a>
        <a href="{{ route('admin.crm.prospects') }}"
           class="btn btn-sm btn-outline-dark fw-bold" style="border-radius:10px;">
            <i class="bi bi-person-lines-fill me-1"></i> Lihat Prospects
        </a>
    </div>

</div>
@endsection
