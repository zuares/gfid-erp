@extends('layouts.app')

@section('title', 'Marketplace • Variant Kosong')

@push('head')
<style>
    .ev-page{max-width:1280px;margin-inline:auto;padding:1rem 1rem 4rem;color:var(--body-color,#0f172a)}
    .ev-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1rem}
    .ev-eyebrow{font-size:.72rem;font-weight:750;letter-spacing:.04em;text-transform:uppercase;color:#64748b;margin-bottom:.25rem}
    .ev-title{margin:0;font-size:1.35rem;font-weight:850;letter-spacing:-.025em}
    .ev-subtitle{margin:.3rem 0 0;max-width:720px;color:#64748b;font-size:.82rem;line-height:1.55}
    .ev-back{display:inline-flex;align-items:center;gap:.35rem;min-height:38px;padding:.45rem .75rem;border:1px solid rgba(148,163,184,.35);border-radius:9px;color:inherit;text-decoration:none;font-size:.78rem;font-weight:700;white-space:nowrap;background:var(--card,#fff)}
    .ev-back:hover{background:rgba(148,163,184,.08);color:inherit}
    .ev-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.65rem;margin-bottom:.8rem}
    .ev-kpi{display:block;padding:.8rem .9rem;border:1px solid rgba(148,163,184,.22);border-radius:12px;background:var(--card,#fff);color:inherit;text-decoration:none;box-shadow:0 8px 24px rgba(15,23,42,.04);transition:transform .15s,border-color .15s}
    .ev-kpi:hover{transform:translateY(-1px);border-color:rgba(37,99,235,.38);color:inherit}
    .ev-kpi.active{border-color:#2563eb;box-shadow:0 0 0 2px rgba(37,99,235,.1)}
    .ev-kpi-label{display:flex;align-items:center;gap:.4rem;color:#64748b;font-size:.72rem;font-weight:700}
    .ev-kpi-value{display:block;margin-top:.35rem;font-size:1.35rem;font-weight:850;line-height:1}
    .ev-filter{display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:.55rem;padding:.75rem;margin-bottom:.8rem;border:1px solid rgba(148,163,184,.22);border-radius:12px;background:var(--card,#fff)}
    .ev-control{min-height:38px;border-radius:8px;font-size:.78rem}
    .ev-filter .btn{min-height:38px;border-radius:8px;font-size:.78rem;font-weight:750}
    .ev-card{overflow:hidden;border:1px solid rgba(148,163,184,.22);border-radius:12px;background:var(--card,#fff);box-shadow:0 10px 30px rgba(15,23,42,.05)}
    .ev-table{width:100%;border-collapse:collapse;margin:0}
    .ev-table th{padding:.65rem .75rem;border-bottom:1px solid rgba(148,163,184,.22);color:#64748b;font-size:.68rem;text-transform:uppercase;letter-spacing:.035em;text-align:left;white-space:nowrap}
    .ev-table td{padding:.7rem .75rem;border-top:1px solid rgba(148,163,184,.15);font-size:.77rem;vertical-align:top}
    .ev-product{display:flex;align-items:flex-start;gap:.65rem;min-width:260px}
    .ev-image{width:42px;height:42px;flex:none;border-radius:9px;object-fit:cover;background:#f1f5f9;border:1px solid rgba(148,163,184,.2)}
    .ev-image-placeholder{display:grid;place-items:center;color:#94a3b8}
    .ev-product-name{font-weight:780;line-height:1.35;max-width:360px}
    .ev-meta{margin-top:.2rem;color:#64748b;font-size:.68rem}
    .ev-store{display:inline-flex;align-items:center;gap:.3rem;padding:.2rem .45rem;border-radius:999px;background:rgba(59,130,246,.08);color:#1d4ed8;font-size:.68rem;font-weight:700;white-space:nowrap}
    .ev-models{display:flex;flex-direction:column;gap:.4rem;min-width:260px}
    .ev-model{padding:.45rem .55rem;border:1px solid rgba(148,163,184,.18);border-radius:8px;background:rgba(148,163,184,.035)}
    .ev-model-line{display:flex;align-items:center;justify-content:space-between;gap:.7rem}
    .ev-model-id{font-size:.66rem;color:#64748b}
    .ev-issue{display:inline-flex;align-items:center;gap:.25rem;padding:.18rem .42rem;border-radius:6px;font-size:.64rem;font-weight:750;white-space:nowrap}
    .ev-issue-danger{background:rgba(239,68,68,.1);color:#b91c1c}
    .ev-issue-warn{background:rgba(245,158,11,.12);color:#92400e}
    .ev-empty{padding:3.2rem 1rem;text-align:center;color:#64748b}
    .ev-empty-icon{display:grid;place-items:center;width:48px;height:48px;margin:0 auto .7rem;border-radius:14px;background:rgba(34,197,94,.1);color:#15803d;font-size:1.2rem}
    .ev-pagination{padding:.75rem;border-top:1px solid rgba(148,163,184,.18)}
    .ev-sync{white-space:nowrap;color:#64748b;font-size:.7rem}
    .ev-action{display:inline-flex;align-items:center;gap:.3rem;color:#2563eb;text-decoration:none;font-weight:750;white-space:nowrap}
    .ev-action:hover{text-decoration:underline}
    body[data-theme="dark"] .ev-page{color:#e5e7eb}
    body[data-theme="dark"] .ev-subtitle,body[data-theme="dark"] .ev-eyebrow,body[data-theme="dark"] .ev-meta,body[data-theme="dark"] .ev-model-id,body[data-theme="dark"] .ev-sync{color:#94a3b8}
    body[data-theme="dark"] .ev-store{color:#93c5fd;background:rgba(59,130,246,.14)}
    @media(max-width:900px){.ev-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.ev-filter{grid-template-columns:1fr 1fr}.ev-filter .ev-search{grid-column:1/-1}.ev-filter .btn{grid-column:1/-1}.ev-card{overflow-x:auto}.ev-table{min-width:920px}}
    @media(max-width:575px){.ev-page{padding:.75rem .75rem 3rem}.ev-header{flex-direction:column}.ev-back{width:100%;justify-content:center}.ev-kpis{gap:.45rem}.ev-kpi{padding:.7rem}.ev-filter{grid-template-columns:1fr}.ev-filter .ev-search,.ev-filter .btn{grid-column:auto}.ev-title{font-size:1.15rem}}
</style>
@endpush

@section('content')
@php
    $baseFilters = array_filter([
        'store_id' => $storeId ?: null,
        'channel' => $channel ?: null,
        'search' => $search ?: null,
    ], fn ($value) => $value !== null && $value !== '');
    $issueLabels = [
        'missing_name' => 'Nama varian kosong',
        'missing_sku' => 'SKU varian kosong',
        'no_models' => 'Tidak ada data varian',
    ];
@endphp
<main class="ev-page">
    <header class="ev-header">
        <div>
            <div class="ev-eyebrow"><i class="bi bi-shop me-1"></i>Marketplace / Audit Katalog</div>
            <h1 class="ev-title">Dashboard Variant Kosong</h1>
            <p class="ev-subtitle">Temukan produk multi-varian yang belum memiliki nama varian, SKU varian, atau baris varian hasil sinkronisasi. Produk tanpa varian memang tidak dimasukkan sebagai masalah.</p>
        </div>
        <a class="ev-back" href="{{ route('marketplace.products') }}"><i class="bi bi-arrow-left"></i>Kembali ke Produk</a>
    </header>

    <section class="ev-kpis" aria-label="Ringkasan masalah varian">
        <a class="ev-kpi {{ $issue === '' ? 'active' : '' }}" href="{{ route('marketplace.products.empty-variants', $baseFilters) }}">
            <span class="ev-kpi-label"><i class="bi bi-exclamation-diamond"></i>Produk bermasalah</span>
            <strong class="ev-kpi-value">{{ number_format($summary['total']) }}</strong>
        </a>
        <a class="ev-kpi {{ $issue === 'missing_name' ? 'active' : '' }}" href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['issue' => 'missing_name'])) }}">
            <span class="ev-kpi-label"><i class="bi bi-type"></i>Nama varian kosong</span>
            <strong class="ev-kpi-value">{{ number_format($summary['missing_name']) }}</strong>
        </a>
        <a class="ev-kpi {{ $issue === 'missing_sku' ? 'active' : '' }}" href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['issue' => 'missing_sku'])) }}">
            <span class="ev-kpi-label"><i class="bi bi-upc-scan"></i>SKU varian kosong</span>
            <strong class="ev-kpi-value">{{ number_format($summary['missing_sku']) }}</strong>
        </a>
        <a class="ev-kpi {{ $issue === 'no_models' ? 'active' : '' }}" href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['issue' => 'no_models'])) }}">
            <span class="ev-kpi-label"><i class="bi bi-database-x"></i>Data varian tidak ada</span>
            <strong class="ev-kpi-value">{{ number_format($summary['no_models']) }}</strong>
        </a>
    </section>

    <form class="ev-filter" method="GET" action="{{ route('marketplace.products.empty-variants') }}">
        <label class="visually-hidden" for="evSearch">Cari produk atau varian</label>
        <input class="form-control ev-control ev-search" id="evSearch" name="search" value="{{ $search }}" maxlength="100" placeholder="Cari produk, item ID, nama atau SKU varian…">
        <label class="visually-hidden" for="evStore">Toko</label>
        <select class="form-select ev-control" id="evStore" name="store_id">
            <option value="">Semua toko</option>
            @foreach($stores as $store)
                <option value="{{ $store->id }}" @selected($storeId === $store->id)>{{ $store->name }}</option>
            @endforeach
        </select>
        <label class="visually-hidden" for="evChannel">Channel</label>
        <select class="form-select ev-control" id="evChannel" name="channel">
            <option value="">Semua channel</option>
            <option value="shopee" @selected($channel === 'shopee')>Shopee</option>
            <option value="tiktok" @selected($channel === 'tiktok')>TikTok Shop</option>
        </select>
        <label class="visually-hidden" for="evIssue">Jenis masalah</label>
        <select class="form-select ev-control" id="evIssue" name="issue">
            <option value="">Semua masalah</option>
            @foreach($issueLabels as $value => $label)
                <option value="{{ $value }}" @selected($issue === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
    </form>

    <section class="ev-card" aria-live="polite">
        <table class="ev-table">
            <thead>
                <tr><th>Produk</th><th>Toko</th><th>Variant bermasalah</th><th>Sinkronisasi</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    @php
                        $channelCode = strtolower((string) $product->store?->channel?->code);
                        $channelName = in_array($channelCode, ['tiktok', 'ttk', 'tt'], true) ? 'TikTok Shop' : 'Shopee';
                    @endphp
                    <tr>
                        <td>
                            <div class="ev-product">
                                @if($product->image_url)
                                    <img class="ev-image" src="{{ $product->image_url }}" alt="" loading="lazy">
                                @else
                                    <span class="ev-image ev-image-placeholder"><i class="bi bi-image"></i></span>
                                @endif
                                <div>
                                    <div class="ev-product-name">{{ $product->item_name ?: 'Produk tanpa nama' }}</div>
                                    <div class="ev-meta">Item ID {{ $product->item_id }} · SKU induk {{ $product->item_sku ?: '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="ev-store"><i class="bi {{ $channelName === 'TikTok Shop' ? 'bi-music-note-beamed' : 'bi-bag' }}"></i>{{ $product->store?->name ?: 'Toko tidak ditemukan' }}</span><div class="ev-meta">{{ $channelName }}</div></td>
                        <td>
                            <div class="ev-models">
                                @if($product->models->isEmpty())
                                    <div class="ev-model"><span class="ev-issue ev-issue-danger"><i class="bi bi-database-x"></i>Tidak ada data varian</span></div>
                                @else
                                    @foreach($product->models as $model)
                                        <div class="ev-model">
                                            <div class="ev-model-line"><strong>{{ filled(trim((string) $model->model_name)) ? $model->model_name : 'Nama belum diisi' }}</strong><span class="ev-model-id">ID {{ $model->model_id }}</span></div>
                                            <div class="ev-model-line mt-1">
                                                <span class="ev-meta">SKU: {{ filled(trim((string) $model->model_sku)) ? $model->model_sku : '—' }}</span>
                                                <span>
                                                    @if(blank(trim((string) $model->model_name)))<span class="ev-issue ev-issue-danger">Nama kosong</span>@endif
                                                    @if(blank(trim((string) $model->model_sku)))<span class="ev-issue ev-issue-warn">SKU kosong</span>@endif
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </td>
                        <td><span class="ev-sync">{{ $product->synced_at?->format('d M Y, H:i') ?: 'Belum pernah sync' }}</span></td>
                        <td><a class="ev-action" href="{{ route('marketplace.products', ['search' => $product->item_id]) }}"><i class="bi bi-box-arrow-up-right"></i>Buka produk</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="ev-empty"><span class="ev-empty-icon"><i class="bi bi-check2-circle"></i></span><strong>Tidak ada variant kosong</strong><div class="mt-1">Semua produk multi-varian pada filter ini sudah memiliki data varian yang lengkap.</div></td></tr>
                @endforelse
            </tbody>
        </table>
        @if($products->hasPages())<div class="ev-pagination">{{ $products->links() }}</div>@endif
    </section>
</main>
@endsection
