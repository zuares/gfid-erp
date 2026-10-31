@extends('layouts.app')

@section('title', 'Marketplace • Variant Stok Kosong')

@push('head')
<style>
    .ev-page{max-width:1280px;margin-inline:auto;padding:1rem 1rem 4rem;color:var(--body-color,#0f172a)}
    .ev-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1rem}
    .ev-eyebrow{font-size:.72rem;font-weight:750;letter-spacing:.04em;text-transform:uppercase;color:#64748b;margin-bottom:.25rem}
    .ev-title{margin:0;font-size:1.35rem;font-weight:850;letter-spacing:-.025em}
    .ev-subtitle{margin:.3rem 0 0;max-width:760px;color:#64748b;font-size:.82rem;line-height:1.55}
    .ev-back{display:inline-flex;align-items:center;gap:.35rem;min-height:38px;padding:.45rem .75rem;border:1px solid rgba(148,163,184,.35);border-radius:9px;color:inherit;text-decoration:none;font-size:.78rem;font-weight:700;white-space:nowrap;background:var(--card,#fff)}
    .ev-back:hover{background:rgba(148,163,184,.08);color:inherit}
    .ev-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.65rem;margin-bottom:.8rem}
    .ev-kpi{display:block;padding:.8rem .9rem;border:1px solid rgba(148,163,184,.22);border-radius:12px;background:var(--card,#fff);color:inherit;text-decoration:none;box-shadow:0 8px 24px rgba(15,23,42,.04);transition:transform .15s,border-color .15s}
    .ev-kpi:hover{transform:translateY(-1px);border-color:rgba(220,38,38,.35);color:inherit}.ev-kpi.active{border-color:#dc2626;box-shadow:0 0 0 2px rgba(220,38,38,.08)}
    .ev-kpi-label{display:flex;align-items:center;gap:.4rem;color:#64748b;font-size:.72rem;font-weight:700}.ev-kpi-value{display:block;margin-top:.35rem;font-size:1.35rem;font-weight:850;line-height:1}
    .ev-filter{display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:.55rem;padding:.75rem;margin-bottom:.8rem;border:1px solid rgba(148,163,184,.22);border-radius:12px;background:var(--card,#fff)}
    .ev-control{min-height:38px;border-radius:8px;font-size:.78rem}.ev-filter .btn{min-height:38px;border-radius:8px;font-size:.78rem;font-weight:750}
    .ev-card{overflow:hidden;border:1px solid rgba(148,163,184,.22);border-radius:12px;background:var(--card,#fff);box-shadow:0 10px 30px rgba(15,23,42,.05)}
    .ev-table{width:100%;border-collapse:collapse;margin:0}.ev-table th{padding:.65rem .75rem;border-bottom:1px solid rgba(148,163,184,.22);color:#64748b;font-size:.68rem;text-transform:uppercase;letter-spacing:.035em;text-align:left;white-space:nowrap}
    .ev-table td{padding:.7rem .75rem;border-top:1px solid rgba(148,163,184,.15);font-size:.77rem;vertical-align:top}.ev-product{display:flex;align-items:flex-start;gap:.65rem;min-width:250px}
    .ev-image{width:42px;height:42px;flex:none;border-radius:9px;object-fit:cover;background:#f1f5f9;border:1px solid rgba(148,163,184,.2)}.ev-image-placeholder{display:grid;place-items:center;color:#94a3b8}
    .ev-product-name{font-weight:780;line-height:1.35;max-width:330px}.ev-meta{margin-top:.2rem;color:#64748b;font-size:.68rem}.ev-variant{font-weight:780}.ev-sku{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.7rem;color:#475569}
    .ev-store{display:inline-flex;align-items:center;gap:.3rem;padding:.2rem .45rem;border-radius:999px;background:rgba(59,130,246,.08);color:#1d4ed8;font-size:.68rem;font-weight:700;white-space:nowrap}
    .ev-mapping{min-width:190px}.ev-mapping strong{display:block}.ev-stock{font-variant-numeric:tabular-nums;font-weight:850;font-size:1rem}.ev-stock-empty{color:#b91c1c}.ev-internal{font-variant-numeric:tabular-nums;font-weight:780;color:#047857}
    .ev-badge{display:inline-flex;align-items:center;gap:.25rem;padding:.18rem .42rem;border-radius:6px;background:rgba(34,197,94,.1);color:#166534;font-size:.64rem;font-weight:750;white-space:nowrap}
    .ev-empty{padding:3.2rem 1rem;text-align:center;color:#64748b}.ev-empty-icon{display:grid;place-items:center;width:48px;height:48px;margin:0 auto .7rem;border-radius:14px;background:rgba(34,197,94,.1);color:#15803d;font-size:1.2rem}
    .ev-pagination{padding:.75rem;border-top:1px solid rgba(148,163,184,.18)}.ev-sync{white-space:nowrap;color:#64748b;font-size:.7rem}.ev-action{display:inline-flex;align-items:center;gap:.3rem;color:#2563eb;text-decoration:none;font-weight:750;white-space:nowrap}.ev-action:hover{text-decoration:underline}
    body[data-theme="dark"] .ev-page{color:#e5e7eb}body[data-theme="dark"] .ev-subtitle,body[data-theme="dark"] .ev-eyebrow,body[data-theme="dark"] .ev-meta,body[data-theme="dark"] .ev-sync{color:#94a3b8}body[data-theme="dark"] .ev-sku{color:#cbd5e1}body[data-theme="dark"] .ev-store{color:#93c5fd;background:rgba(59,130,246,.14)}body[data-theme="dark"] .ev-badge{color:#86efac}
    @media(max-width:1000px){.ev-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.ev-filter{grid-template-columns:1fr 1fr}.ev-filter .ev-search{grid-column:1/-1}.ev-filter .btn{grid-column:1/-1}.ev-card{overflow-x:auto}.ev-table{min-width:1050px}}
    @media(max-width:575px){.ev-page{padding:.75rem .75rem 3rem}.ev-header{flex-direction:column}.ev-back{width:100%;justify-content:center}.ev-kpis{gap:.45rem}.ev-kpi{padding:.7rem}.ev-filter{grid-template-columns:1fr}.ev-filter .ev-search,.ev-filter .btn{grid-column:auto}.ev-title{font-size:1.15rem}}
</style>
@endpush

@section('content')
@php
    $baseFilters = array_filter(['store_id' => $storeId ?: null, 'channel' => $channel ?: null, 'search' => $search ?: null], fn ($value) => $value !== null && $value !== '');
@endphp
<main class="ev-page">
    <header class="ev-header">
        <div><div class="ev-eyebrow"><i class="bi bi-shop me-1"></i>Marketplace / Audit Stok</div><h1 class="ev-title">Dashboard Variant Stok Kosong</h1><p class="ev-subtitle">Menampilkan hanya varian multi-SKU yang sudah ter-mapping ke item internal tetapi stok marketplace-nya 0 atau minus. Stok internal tersedia ditampilkan sebagai pembanding untuk menentukan varian yang perlu diperbarui.</p></div>
        <a class="ev-back" href="{{ route('marketplace.products') }}"><i class="bi bi-arrow-left"></i>Kembali ke Produk</a>
    </header>

    <section class="ev-kpis" aria-label="Ringkasan varian stok kosong">
        <a class="ev-kpi {{ $stockStatus === '' ? 'active' : '' }}" href="{{ route('marketplace.products.empty-variants', $baseFilters) }}"><span class="ev-kpi-label"><i class="bi bi-box-seam"></i>Total varian kosong</span><strong class="ev-kpi-value">{{ number_format($summary['total']) }}</strong></a>
        <a class="ev-kpi {{ $stockStatus === 'zero' ? 'active' : '' }}" href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['stock_status' => 'zero'])) }}"><span class="ev-kpi-label"><i class="bi bi-0-circle"></i>Stok tepat 0</span><strong class="ev-kpi-value">{{ number_format($summary['zero']) }}</strong></a>
        <a class="ev-kpi {{ $stockStatus === 'negative' ? 'active' : '' }}" href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['stock_status' => 'negative'])) }}"><span class="ev-kpi-label"><i class="bi bi-dash-circle"></i>Stok minus</span><strong class="ev-kpi-value">{{ number_format($summary['negative']) }}</strong></a>
        <span class="ev-kpi"><span class="ev-kpi-label"><i class="bi bi-boxes"></i>Produk terdampak</span><strong class="ev-kpi-value">{{ number_format($summary['products']) }}</strong></span>
    </section>

    <form class="ev-filter" method="GET" action="{{ route('marketplace.products.empty-variants') }}">
        <label class="visually-hidden" for="evSearch">Cari produk, varian, SKU, atau item internal</label><input class="form-control ev-control ev-search" id="evSearch" name="search" value="{{ $search }}" maxlength="100" placeholder="Cari produk, varian, SKU, atau item internal…">
        <label class="visually-hidden" for="evStore">Toko</label><select class="form-select ev-control" id="evStore" name="store_id"><option value="">Semua toko</option>@foreach($stores as $store)<option value="{{ $store->id }}" @selected($storeId === $store->id)>{{ $store->name }}</option>@endforeach</select>
        <label class="visually-hidden" for="evChannel">Channel</label><select class="form-select ev-control" id="evChannel" name="channel"><option value="">Semua channel</option><option value="shopee" @selected($channel === 'shopee')>Shopee</option><option value="tiktok" @selected($channel === 'tiktok')>TikTok Shop</option></select>
        <label class="visually-hidden" for="evStockStatus">Status stok</label><select class="form-select ev-control" id="evStockStatus" name="stock_status"><option value="">Stok 0 & minus</option><option value="zero" @selected($stockStatus === 'zero')>Stok tepat 0</option><option value="negative" @selected($stockStatus === 'negative')>Stok minus</option></select>
        <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
    </form>

    <section class="ev-card" aria-live="polite"><table class="ev-table"><thead><tr><th>Produk</th><th>Toko</th><th>Variant ter-mapping</th><th>Item internal</th><th>Stok marketplace</th><th>Stok internal tersedia</th><th>Sinkronisasi</th><th>Aksi</th></tr></thead><tbody>
        @forelse($variants as $variant)
            @php
                $product = $variant->product;
                $mapping = $variant->mapping_detail;
                $channelCode = strtolower((string) $product?->store?->channel?->code);
                $channelName = in_array($channelCode, ['tiktok','ttk','tt'], true) ? 'TikTok Shop' : 'Shopee';
            @endphp
            <tr>
                <td><div class="ev-product">@if($product?->image_url)<img class="ev-image" src="{{ $product->image_url }}" alt="" loading="lazy">@else<span class="ev-image ev-image-placeholder"><i class="bi bi-image"></i></span>@endif<div><div class="ev-product-name">{{ $product?->item_name ?: 'Produk tanpa nama' }}</div><div class="ev-meta">Item ID {{ $product?->item_id ?: '—' }}</div></div></div></td>
                <td><span class="ev-store"><i class="bi {{ $channelName === 'TikTok Shop' ? 'bi-music-note-beamed' : 'bi-bag' }}"></i>{{ $product?->store?->name ?: 'Toko tidak ditemukan' }}</span><div class="ev-meta">{{ $channelName }}</div></td>
                <td><div class="ev-variant">{{ $variant->model_name ?: 'Variant tanpa nama' }}</div><div class="ev-sku">{{ $variant->model_sku }}</div><div class="ev-meta">Model ID {{ $variant->model_id }}</div></td>
                <td class="ev-mapping"><span class="ev-badge"><i class="bi bi-link-45deg"></i>Sudah mapping</span><strong class="mt-1">{{ $mapping['item_code'] ?? '—' }}</strong><div class="ev-meta">{{ $mapping['item_name'] ?? 'Item internal tidak ditemukan' }}</div></td>
                <td><span class="ev-stock ev-stock-empty">{{ number_format($variant->stock) }}</span></td>
                <td><span class="ev-internal">{{ number_format($mapping['internal_available'] ?? 0, 0, ',', '.') }}</span><div class="ev-meta">Fisik {{ number_format($mapping['internal_physical'] ?? 0, 0, ',', '.') }} · Dialokasi {{ number_format($mapping['internal_allocated'] ?? 0, 0, ',', '.') }}</div></td>
                <td><span class="ev-sync">{{ $product?->synced_at?->format('d M Y, H:i') ?: 'Belum pernah sync' }}</span></td>
                <td><a class="ev-action" href="{{ route('marketplace.products', ['search' => $variant->model_sku]) }}"><i class="bi bi-box-arrow-up-right"></i>Buka produk</a></td>
            </tr>
        @empty
            <tr><td colspan="8" class="ev-empty"><span class="ev-empty-icon"><i class="bi bi-check2-circle"></i></span><strong>Tidak ada stok variant kosong</strong><div class="mt-1">Semua varian yang sudah ter-mapping pada filter ini memiliki stok marketplace di atas 0.</div></td></tr>
        @endforelse
    </tbody></table>@if($variants->hasPages())<div class="ev-pagination">{{ $variants->links() }}</div>@endif</section>
</main>
@endsection
