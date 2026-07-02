@extends('layouts.app')
@section('title', 'Ranking Produk')

@push('head')
<style>
/* ── Layout ── */
.rk-wrap { display:flex;flex-direction:column;gap:1rem; }

/* ── Stat cards ── */
.rk-stats { display:flex;gap:.75rem;flex-wrap:wrap; }
.rk-stat { background:#fff;border:1.5px solid #e8ecf0;border-radius:12px;padding:.75rem 1rem;min-width:130px;flex:1; }
.rk-stat-n { font-size:1.4rem;font-weight:900;color:#0f172a;line-height:1; }
.rk-stat-l { font-size:.68rem;color:#94a3b8;font-weight:600;margin-top:2px; }

/* ── Table ── */
.rk-table-wrap { background:#fff;border:1.5px solid #e8ecf0;border-radius:14px;overflow:hidden; }
.rk-table { width:100%;border-collapse:collapse;font-size:.78rem; }
.rk-table thead th { background:#f8fafc;padding:.55rem .75rem;text-align:left;font-size:.65rem;font-weight:800;color:#64748b;letter-spacing:.05em;text-transform:uppercase;border-bottom:1.5px solid #e8ecf0;white-space:nowrap; }
.rk-table tbody tr { border-bottom:1px solid #f1f5f9;transition:background .1s; }
.rk-table tbody tr:last-child { border-bottom:none; }
.rk-table tbody tr:hover { background:#f8fafc; }
.rk-table td { padding:.55rem .75rem;vertical-align:middle; }

/* ── Rank badge ── */
.rk-pos { font-size:.78rem;font-weight:900;color:#94a3b8;min-width:28px;text-align:center; }
.rk-pos.top3 { color:#f59e0b; }
.rk-pos.pinned { color:#6366f1; }

/* ── Product cell ── */
.rk-prod { display:flex;align-items:center;gap:.65rem; }
.rk-thumb { width:36px;height:36px;border-radius:8px;object-fit:cover;background:#f1f5f9;flex-shrink:0; }
.rk-thumb-ph { width:36px;height:36px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.rk-prod-name { font-weight:700;color:#0f172a;font-size:.8rem;line-height:1.2; }
.rk-prod-slug { font-size:.65rem;color:#94a3b8; }

/* ── Score bar ── */
.score-cell { min-width:80px; }
.score-val { font-size:.75rem;font-weight:800;color:#0f172a;margin-bottom:2px; }
.score-bar-wrap { height:4px;background:#e2e8f0;border-radius:2px;overflow:hidden; }
.score-bar { height:100%;border-radius:2px;transition:width .3s; }

/* ── Component scores ── */
.comp-grid { display:flex;gap:6px;flex-wrap:wrap;min-width:200px; }
.comp-item { display:flex;flex-direction:column;gap:2px;min-width:44px; }
.comp-lbl { font-size:.58rem;font-weight:700;color:#94a3b8;letter-spacing:.04em;text-transform:uppercase; }
.comp-val { font-size:.68rem;font-weight:800;color:#334155; }
.comp-bar-wrap { height:3px;background:#e2e8f0;border-radius:1px;overflow:hidden; }
.comp-bar { height:100%;border-radius:1px; }

/* ── Status badges ── */
.badge-pin  { background:#ede9fe;color:#6d28d9;font-size:.62rem;font-weight:800;padding:.15rem .45rem;border-radius:5px; }
.badge-feat { background:#fef3c7;color:#92400e;font-size:.62rem;font-weight:800;padding:.15rem .45rem;border-radius:5px; }
.badge-new  { background:#e0f2fe;color:#0369a1;font-size:.62rem;font-weight:800;padding:.15rem .45rem;border-radius:5px; }
.badge-out  { background:#fee2e2;color:#b91c1c;font-size:.62rem;font-weight:800;padding:.15rem .45rem;border-radius:5px; }
.badge-low  { background:#ffedd5;color:#c2410c;font-size:.62rem;font-weight:800;padding:.15rem .45rem;border-radius:5px; }
.badge-unranked { background:#f1f5f9;color:#94a3b8;font-size:.62rem;font-weight:800;padding:.15rem .45rem;border-radius:5px; }

/* ── Responsive ── */
@media(max-width:768px){
    .hide-mobile { display:none!important; }
    .comp-grid { min-width:130px; }
}
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

{{-- Flash --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible py-2 mb-3" style="font-size:.8rem;border-radius:10px;">
    {{ session('success') }} <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible py-2 mb-3" style="font-size:.8rem;border-radius:10px;">
    {{ session('error') }} <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="rk-wrap">

    {{-- Header --}}
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('admin.catalog.products.index') }}"
                   style="font-size:.72rem;color:#94a3b8;text-decoration:none;">
                    <i class="bi bi-arrow-left me-1"></i>Produk
                </a>
                <span style="color:#e2e8f0;">/</span>
                <h5 class="fw-black mb-0" style="font-size:1rem;">Ranking Produk</h5>
            </div>
            <div style="font-size:.72rem;color:#94a3b8;margin-top:3px;">
                @if($lastUpdated)
                    Terakhir dihitung {{ \Carbon\Carbon::parse($lastUpdated)->diffForHumans() }}
                    <span style="color:#e2e8f0;">·</span>
                    {{ \Carbon\Carbon::parse($lastUpdated)->format('d M Y, H:i') }}
                @else
                    Belum pernah dihitung
                @endif
            </div>
        </div>
        <form method="POST" action="{{ route('admin.catalog.products.rank-now') }}">
            @csrf
            <button type="submit" class="btn btn-dark btn-sm fw-bold" style="border-radius:10px;font-size:.78rem;">
                <i class="bi bi-arrow-clockwise me-1"></i> Hitung Ulang Sekarang
            </button>
        </form>
    </div>

    {{-- Stat cards --}}
    <div class="rk-stats">
        <div class="rk-stat">
            <div class="rk-stat-n">{{ $products->count() }}</div>
            <div class="rk-stat-l">Produk Aktif</div>
        </div>
        <div class="rk-stat">
            <div class="rk-stat-n">{{ $rankedCount }}</div>
            <div class="rk-stat-l">Sudah Diranking</div>
        </div>
        <div class="rk-stat">
            <div class="rk-stat-n" style="color:#6d28d9;">{{ $pinnedCount }}</div>
            <div class="rk-stat-l">Produk Dipinned</div>
        </div>
        <div class="rk-stat">
            <div class="rk-stat-n" style="color:#f59e0b;">
                {{ $products->where('featured_until', '>=', now())->count() }}
            </div>
            <div class="rk-stat-l">Featured Aktif</div>
        </div>
        <div class="rk-stat">
            @php
                $unranked = $products->count() - $rankedCount;
            @endphp
            <div class="rk-stat-n" style="color:{{ $unranked > 0 ? '#ef4444' : '#22c55e' }};">{{ $unranked }}</div>
            <div class="rk-stat-l">Belum Diranking</div>
        </div>
    </div>

    {{-- Legend --}}
    <div style="font-size:.68rem;color:#94a3b8;display:flex;gap:1rem;flex-wrap:wrap;">
        <span>Bobot formula:</span>
        <span><span style="color:#3b82f6;font-weight:800;">CVR</span> 35%</span>
        <span><span style="color:#8b5cf6;font-weight:800;">Trending</span> 35%</span>
        <span><span style="color:#10b981;font-weight:800;">Engagement</span> 15%</span>
        <span><span style="color:#f59e0b;font-weight:800;">New</span> 10%</span>
        <span><span style="color:#64748b;font-weight:800;">Stok</span> 5%</span>
    </div>

    {{-- Table --}}
    <div class="rk-table-wrap">
        @if($products->isEmpty())
        <div style="padding:3rem;text-align:center;color:#94a3b8;font-size:.85rem;">
            Belum ada produk published.
        </div>
        @else
        <table class="rk-table">
            <thead>
                <tr>
                    <th style="width:44px;">#</th>
                    <th>Produk</th>
                    <th style="width:90px;">Skor Final</th>
                    <th class="hide-mobile">Komponen</th>
                    <th class="hide-mobile" style="width:80px;">Stok</th>
                    <th class="hide-mobile" style="width:90px;">Boost / Fitur</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
            @foreach($products as $p)
            @php
                $debug      = $p->rank_debug ?? [];
                $finalScore = $debug['final_score'] ?? $p->rank_score ?? null;
                $cvrScore   = $debug['cvr_score']   ?? null;
                $engScore   = $debug['eng_score']   ?? null;
                $trendScore = $debug['trend_score'] ?? null;
                $newBoost   = $debug['new_boost']   ?? null;
                $stockScore = $debug['stock_score'] ?? null;
                $manualBoost   = $debug['manual_boost']   ?? $p->manual_boost ?? 0;
                $featuredBoost = $debug['featured_boost'] ?? 0;
                $views     = $debug['views']      ?? '—';
                $orders30d = $debug['orders_30d'] ?? '—';
                $orders7d  = $debug['orders_7d']  ?? '—';

                // Stock resolver: sama dengan helper
                $availableStock = $p->variants->isNotEmpty()
                    ? (int) $p->variants->sum('stock')
                    : (int) ($p->stock ?? 0);

                $stockStatus = match(true) {
                    $availableStock === 0 => 'out',
                    $availableStock <= 4  => 'low',
                    default               => 'ok',
                };

                $isNew = (int) now()->diffInDays($p->created_at) < 14;

                // Thumbnail
                $defaultVariant = $p->variants->firstWhere('is_default', true) ?? $p->variants->first();
                $thumb = $defaultVariant?->getImageSrc() ?: $p->getImageSrc();

                // Position display
                $pos = $p->rank_position;
            @endphp
            <tr>
                {{-- Position --}}
                <td>
                    @if($p->is_pinned)
                        <div class="rk-pos pinned" title="Dipinned di posisi {{ $p->pin_position }}">
                            📌 {{ $p->pin_position ?? '—' }}
                        </div>
                    @elseif($pos)
                        <div class="rk-pos {{ $pos <= 3 ? 'top3' : '' }}">
                            {{ $pos <= 3 ? '🏆' : '' }}{{ $pos }}
                        </div>
                    @else
                        <div class="rk-pos">—</div>
                    @endif
                </td>

                {{-- Product --}}
                <td>
                    <div class="rk-prod">
                        @if($thumb)
                            <img src="{{ $thumb }}" class="rk-thumb" alt="{{ $p->name }}">
                        @else
                            <div class="rk-thumb-ph">
                                <i class="bi bi-image" style="font-size:.9rem;color:#cbd5e1;"></i>
                            </div>
                        @endif
                        <div>
                            <div class="rk-prod-name">{{ $p->name }}</div>
                            <div class="rk-prod-slug">{{ $p->slug }}</div>
                            <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:3px;">
                                @if($p->is_pinned)
                                    <span class="badge-pin">📌 Pinned</span>
                                @endif
                                @if($featuredBoost > 0)
                                    <span class="badge-feat">⭐ Featured</span>
                                @endif
                                @if($isNew)
                                    <span class="badge-new">✨ Baru</span>
                                @endif
                                @if($stockStatus === 'out')
                                    <span class="badge-out">Stok Habis</span>
                                @elseif($stockStatus === 'low')
                                    <span class="badge-low">Stok Terbatas</span>
                                @endif
                                @if(!$pos && !$p->is_pinned)
                                    <span class="badge-unranked">Belum diranking</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </td>

                {{-- Final Score --}}
                <td class="score-cell">
                    @if($finalScore !== null)
                        <div class="score-val">{{ number_format($finalScore, 3) }}</div>
                        <div class="score-bar-wrap">
                            <div class="score-bar" style="width:{{ min(100, $finalScore * 100) }}%;background:{{ $finalScore >= 0.7 ? '#22c55e' : ($finalScore >= 0.4 ? '#f59e0b' : '#94a3b8') }};"></div>
                        </div>
                    @else
                        <span style="color:#cbd5e1;font-size:.7rem;">—</span>
                    @endif
                </td>

                {{-- Component scores --}}
                <td class="hide-mobile">
                    @if(!empty($debug))
                    <div class="comp-grid">
                        {{-- CVR --}}
                        <div class="comp-item">
                            <div class="comp-lbl">CVR</div>
                            <div class="comp-val" style="color:#3b82f6;">
                                {{ $cvrScore !== null ? number_format($cvrScore, 2) : '—' }}
                            </div>
                            <div class="comp-bar-wrap">
                                <div class="comp-bar" style="width:{{ ($cvrScore ?? 0) * 100 }}%;background:#3b82f6;"></div>
                            </div>
                            <div style="font-size:.58rem;color:#94a3b8;">{{ $views }}v / {{ $orders30d }}o</div>
                        </div>
                        {{-- Trending --}}
                        <div class="comp-item">
                            <div class="comp-lbl">Trend</div>
                            <div class="comp-val" style="color:#8b5cf6;">
                                {{ $trendScore !== null ? number_format($trendScore, 2) : '—' }}
                            </div>
                            <div class="comp-bar-wrap">
                                <div class="comp-bar" style="width:{{ ($trendScore ?? 0) * 100 }}%;background:#8b5cf6;"></div>
                            </div>
                            <div style="font-size:.58rem;color:#94a3b8;">{{ $orders7d }}o/7d</div>
                        </div>
                        {{-- Engagement --}}
                        <div class="comp-item">
                            <div class="comp-lbl">Eng</div>
                            <div class="comp-val" style="color:#10b981;">
                                {{ $engScore !== null ? number_format($engScore, 2) : '—' }}
                            </div>
                            <div class="comp-bar-wrap">
                                <div class="comp-bar" style="width:{{ ($engScore ?? 0) * 100 }}%;background:#10b981;"></div>
                            </div>
                            <div style="font-size:.58rem;color:#94a3b8;">ATC rate</div>
                        </div>
                        {{-- New boost --}}
                        <div class="comp-item">
                            <div class="comp-lbl">New</div>
                            <div class="comp-val" style="color:#f59e0b;">
                                {{ $newBoost !== null ? number_format($newBoost, 2) : '—' }}
                            </div>
                            <div class="comp-bar-wrap">
                                <div class="comp-bar" style="width:{{ ($newBoost ?? 0) * 100 }}%;background:#f59e0b;"></div>
                            </div>
                        </div>
                        {{-- Stock --}}
                        <div class="comp-item">
                            <div class="comp-lbl">Stok</div>
                            <div class="comp-val" style="color:#64748b;">
                                {{ $stockScore !== null ? number_format($stockScore, 2) : '—' }}
                            </div>
                            <div class="comp-bar-wrap">
                                <div class="comp-bar" style="width:{{ ($stockScore ?? 0) * 100 }}%;background:#64748b;"></div>
                            </div>
                        </div>
                    </div>
                    @else
                        <span style="color:#cbd5e1;font-size:.7rem;">Belum dihitung</span>
                    @endif
                </td>

                {{-- Stock count --}}
                <td class="hide-mobile">
                    <div style="font-size:.78rem;font-weight:800;color:{{ $stockStatus === 'out' ? '#ef4444' : ($stockStatus === 'low' ? '#f97316' : '#22c55e') }};">
                        {{ $availableStock }}
                    </div>
                    <div style="font-size:.62rem;color:#94a3b8;">unit</div>
                </td>

                {{-- Boost / Featured --}}
                <td class="hide-mobile">
                    <div style="display:flex;flex-direction:column;gap:3px;">
                        @if($manualBoost > 0)
                        <div style="font-size:.68rem;color:#f59e0b;font-weight:800;">
                            <i class="bi bi-lightning-charge-fill"></i> +{{ number_format($manualBoost, 2) }}
                        </div>
                        @endif
                        @if($featuredBoost > 0)
                        <div style="font-size:.68rem;color:#f59e0b;font-weight:800;">
                            <i class="bi bi-star-fill"></i> +{{ number_format($featuredBoost, 2) }}
                        </div>
                        @endif
                        @if($p->featured_until && now()->lt($p->featured_until))
                        <div style="font-size:.6rem;color:#92400e;">
                            s/d {{ $p->featured_until->format('d M') }}
                        </div>
                        @endif
                        @if($manualBoost == 0 && $featuredBoost == 0)
                        <span style="color:#e2e8f0;font-size:.7rem;">—</span>
                        @endif
                    </div>
                </td>

                {{-- Actions --}}
                <td>
                    <a href="{{ route('admin.catalog.products.edit', $p) }}#tab-ranking"
                       class="btn btn-sm"
                       style="background:#f1f5f9;color:#334155;border:none;border-radius:8px;font-size:.7rem;padding:.3rem .6rem;white-space:nowrap;">
                        <i class="bi bi-sliders"></i>
                        <span class="hide-mobile"> Override</span>
                    </a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>

</div>{{-- .rk-wrap --}}
</div>{{-- .container-fluid --}}
@endsection
