@extends('layouts.app')

@section('title', 'Marketplace • Stok Variant Kosong')

@push('head')
<style>
    .sv-page{max-width:1280px;margin-inline:auto;padding:1rem 1rem 4rem;color:var(--body-color,#0f172a)}
    .sv-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:.85rem}
    .sv-title{margin:0;font-size:1.25rem;font-weight:850;letter-spacing:-.025em}.sv-copy{margin:.25rem 0 0;color:#64748b;font-size:.78rem;line-height:1.5}
    .sv-back{display:inline-flex;align-items:center;gap:.35rem;min-height:36px;padding:.4rem .7rem;border:1px solid rgba(148,163,184,.32);border-radius:8px;color:inherit;text-decoration:none;font-size:.76rem;font-weight:700;background:var(--card,#fff);white-space:nowrap}.sv-back:hover{background:rgba(148,163,184,.08);color:inherit}
    .sv-summary{display:flex;align-items:center;gap:.4rem;overflow-x:auto;margin-bottom:.65rem;padding-bottom:.1rem;scrollbar-width:none}.sv-summary::-webkit-scrollbar{display:none}
    .sv-stat{display:inline-flex;align-items:center;gap:.45rem;min-height:34px;padding:.35rem .6rem;border:1px solid rgba(148,163,184,.22);border-radius:8px;background:var(--card,#fff);color:inherit;text-decoration:none;white-space:nowrap;font-size:.7rem}.sv-stat strong{font-size:.85rem}.sv-stat.active{border-color:rgba(220,38,38,.5);background:rgba(239,68,68,.05)}
    .sv-store-tabs{display:flex;align-items:center;gap:.35rem;overflow-x:auto;margin-bottom:.55rem;padding:.12rem 0;scrollbar-width:none}.sv-store-tabs::-webkit-scrollbar{display:none}.sv-store-tab{display:inline-flex;align-items:center;gap:.35rem;min-height:34px;padding:.35rem .65rem;border:1px solid rgba(148,163,184,.24);border-radius:999px;background:var(--card,#fff);color:#64748b;text-decoration:none;font-size:.7rem;font-weight:700;white-space:nowrap}.sv-store-tab:hover{border-color:rgba(37,99,235,.35);color:#1d4ed8}.sv-store-tab.active{border-color:#2563eb;background:rgba(37,99,235,.08);color:#1d4ed8}
    .sv-filter{display:grid;grid-template-columns:minmax(180px,2fr) repeat(3,minmax(120px,1fr));gap:.45rem;margin-bottom:.65rem;padding:.6rem;border:1px solid rgba(148,163,184,.2);border-radius:10px;background:var(--card,#fff)}
    .sv-control{min-height:36px;border-radius:7px;font-size:.76rem}.sv-dashboard-content{transition:opacity .15s ease}.sv-dashboard-content.is-loading{opacity:.58;pointer-events:none}
    .sv-panel{overflow:hidden;border:1px solid rgba(148,163,184,.2);border-radius:10px;background:var(--card,#fff)}.sv-scroll{overflow-x:auto}
    .sv-table{width:100%;min-width:1100px;border-collapse:collapse}.sv-table th{padding:.58rem .65rem;border-bottom:1px solid rgba(148,163,184,.2);color:#64748b;font-size:.65rem;font-weight:750;text-transform:uppercase;letter-spacing:.035em;text-align:left;white-space:nowrap}
    .sv-table td{padding:.62rem .65rem;border-top:1px solid rgba(148,163,184,.13);font-size:.75rem;vertical-align:middle}.sv-table tbody tr:first-child td{border-top:0}
    .sv-product-photo{width:54px;text-align:center}.sv-img{width:42px;height:42px;border-radius:8px;object-fit:cover;background:#f1f5f9;border:1px solid rgba(148,163,184,.18)}.sv-img-empty{display:grid;place-items:center;margin-inline:auto;color:#94a3b8}.sv-name{max-width:285px;font-weight:780;line-height:1.3}.sv-meta{margin-top:.14rem;color:#64748b;font-size:.65rem}
    .sv-variant{min-width:220px}.sv-variant-name{margin-top:.2rem;font-weight:700}.sv-sku{color:#475569;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.67rem}.sv-map{min-width:155px}.sv-map-code{font-weight:760}.sv-internal-stock{min-width:120px}.sv-internal{display:block;color:#047857;font-size:1rem;font-weight:800;font-variant-numeric:tabular-nums}.sv-average{font-size:.9rem;font-weight:800;font-variant-numeric:tabular-nums}.sv-average small{display:block;color:#64748b;font-size:.61rem;font-weight:500}
    .sv-stock-form{display:flex;align-items:center;gap:.35rem;min-width:190px}.sv-stock-input{width:78px;min-height:34px;border:1px solid rgba(148,163,184,.35);border-radius:7px;padding:.3rem .45rem;background:transparent;color:inherit;font-weight:750;font-variant-numeric:tabular-nums}.sv-stock-input:focus{outline:0;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12)}
    .sv-save{min-height:34px;border:0;border-radius:7px;padding:.35rem .6rem;background:#0f172a;color:#fff;font-size:.7rem;font-weight:750}.sv-save:hover{background:#1e293b}.sv-save:disabled{opacity:.55}.sv-status{display:block;min-height:1em;margin-top:.2rem;color:#64748b;font-size:.62rem}.sv-status.error{color:#b91c1c}.sv-status.ok{color:#047857}
    .sv-sync{color:#64748b;font-size:.66rem;white-space:nowrap}.sv-empty{padding:3rem 1rem!important;text-align:center;color:#64748b}.sv-empty i{display:block;margin-bottom:.45rem;color:#16a34a;font-size:1.35rem}.sv-pages{padding:.6rem;border-top:1px solid rgba(148,163,184,.16)}
    body[data-theme="dark"] .sv-copy,body[data-theme="dark"] .sv-meta,body[data-theme="dark"] .sv-sync{color:#94a3b8}body[data-theme="dark"] .sv-sku{color:#cbd5e1}body[data-theme="dark"] .sv-save{background:#e2e8f0;color:#0f172a}
    @media(max-width:900px){.sv-filter{grid-template-columns:1fr 1fr}.sv-filter .sv-search{grid-column:1/-1}}
    @media(max-width:575px){.sv-page{padding:.75rem .75rem 3rem}.sv-head{flex-direction:column}.sv-back{width:100%;justify-content:center}.sv-filter{grid-template-columns:1fr}.sv-filter .sv-search{grid-column:auto}.sv-title{font-size:1.1rem}}
</style>
@endpush

@section('content')
@php
    $baseFilters = array_filter(['store_id' => $storeId ?: null, 'channel' => $channel ?: null, 'search' => $search ?: null, 'min_avg_sales' => $minAverageSales], fn ($value) => $value !== null && $value !== '');
@endphp
<main class="sv-page">
    <header class="sv-head">
        <div><h1 class="sv-title">Stok Variant Kosong</h1><p class="sv-copy">Variant ter-mapping dengan stok marketplace kosong. Rata² Terjual/Hari mengikuti nilai item pada modul Inventory.</p></div>
        <a class="sv-back" href="{{ route('marketplace.products') }}"><i class="bi bi-arrow-left"></i>Produk</a>
    </header>

    @php
        $storeSharedFilters = array_filter(['stock_status' => $stockStatus ?: null, 'search' => $search ?: null, 'min_avg_sales' => $minAverageSales], fn ($value) => $value !== null && $value !== '');
        $allStoreFilters = array_merge($storeSharedFilters, array_filter(['channel' => $channel ?: null]));
    @endphp
    <div class="sv-dashboard-content" id="svDashboardContent">
    <nav class="sv-store-tabs" role="tablist" aria-label="Filter toko marketplace">
        <a class="sv-store-tab {{ $storeId === 0 ? 'active' : '' }}" role="tab" aria-selected="{{ $storeId === 0 ? 'true' : 'false' }}" data-dashboard-link href="{{ route('marketplace.products.empty-variants', $allStoreFilters) }}"><i class="bi bi-grid"></i>Semua Toko</a>
        @foreach($stores as $store)
            <a class="sv-store-tab {{ $storeId === $store->id ? 'active' : '' }}" role="tab" aria-selected="{{ $storeId === $store->id ? 'true' : 'false' }}" data-dashboard-link href="{{ route('marketplace.products.empty-variants', array_merge($storeSharedFilters, ['store_id' => $store->id])) }}"><i class="bi bi-shop"></i>{{ $store->name }}</a>
        @endforeach
    </nav>

    <nav class="sv-summary" aria-label="Ringkasan stok">
        <a class="sv-stat {{ $stockStatus === '' ? 'active' : '' }}" data-dashboard-link href="{{ route('marketplace.products.empty-variants', $baseFilters) }}">Semua <strong data-summary="total">{{ number_format($summary['total']) }}</strong></a>
        <a class="sv-stat {{ $stockStatus === 'zero' ? 'active' : '' }}" data-dashboard-link href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['stock_status' => 'zero'])) }}">Stok 0 <strong data-summary="zero">{{ number_format($summary['zero']) }}</strong></a>
        <a class="sv-stat {{ $stockStatus === 'negative' ? 'active' : '' }}" data-dashboard-link href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['stock_status' => 'negative'])) }}">Minus <strong data-summary="negative">{{ number_format($summary['negative']) }}</strong></a>
        <span class="sv-stat">Produk <strong>{{ number_format($summary['products']) }}</strong></span>
    </nav>

    <form class="sv-filter" method="GET" action="{{ route('marketplace.products.empty-variants') }}" data-realtime-filter>
        @if($storeId > 0)<input type="hidden" name="store_id" value="{{ $storeId }}">@endif
        <label class="visually-hidden" for="svSearch">Cari produk, variant, atau SKU</label><input class="form-control sv-control sv-search" id="svSearch" name="search" value="{{ $search }}" maxlength="100" placeholder="Cari produk, variant, atau SKU…">
        <label class="visually-hidden" for="svChannel">Channel</label><select class="form-select sv-control" id="svChannel" name="channel"><option value="">Semua channel</option><option value="shopee" @selected($channel === 'shopee')>Shopee</option><option value="tiktok" @selected($channel === 'tiktok')>TikTok Shop</option></select>
        <label class="visually-hidden" for="svStock">Status stok</label><select class="form-select sv-control" id="svStock" name="stock_status"><option value="">Stok 0 & minus</option><option value="zero" @selected($stockStatus === 'zero')>Stok tepat 0</option><option value="negative" @selected($stockStatus === 'negative')>Stok minus</option></select>
        <label class="visually-hidden" for="svAverage">Rata-rata terjual per hari lebih dari</label><input class="form-control sv-control" id="svAverage" name="min_avg_sales" type="number" min="0" max="1000000" step="0.1" value="{{ $minAverageSales }}" placeholder="Rata² terjual/hari >">
    </form>

    <section class="sv-panel" aria-live="polite">
        <div class="sv-scroll"><table class="sv-table"><thead><tr><th>Produk</th><th>Variant</th><th>Mapping</th><th>Stok internal</th><th>Rata² Terjual/Hari</th><th>Stok marketplace baru</th><th>Terakhir sync</th></tr></thead><tbody id="svRows">
            @forelse($variants as $variant)
                @php
                    $product = $variant->product;
                    $mapping = $variant->mapping_detail;
                    $available = max(0, (int) floor($mapping['internal_available'] ?? 0));
                    $averageWindow = max(1, (int) ($mapping['avg_daily_sales_window'] ?? 30));
                @endphp
                <tr data-stock-row data-current-stock="{{ $variant->stock }}">
                    <td class="sv-product-photo">@if($product?->image_url)<img class="sv-img" src="{{ $product->image_url }}" alt="{{ $product->item_name ?: 'Produk marketplace' }}" loading="lazy">@else<span class="sv-img sv-img-empty" title="Tidak ada foto"><i class="bi bi-image"></i></span>@endif</td>
                    <td class="sv-variant"><div class="sv-name">{{ $product?->item_name ?: 'Produk tanpa nama' }}</div><div class="sv-variant-name">{{ $variant->model_name ?: 'Variant tanpa nama' }}</div><div class="sv-sku">{{ $variant->model_sku }}</div><div class="sv-meta">{{ $product?->store?->name ?: 'Toko tidak ditemukan' }}</div></td>
                    <td class="sv-map"><div class="sv-map-code">{{ $mapping['item_code'] ?? '—' }}</div><div class="sv-meta">{{ $mapping['item_name'] ?? 'Item internal' }}</div></td>
                    <td class="sv-internal-stock"><span class="sv-internal">{{ number_format($available, 0, ',', '.') }}</span><div class="sv-meta">Fisik {{ number_format($mapping['internal_physical'] ?? 0, 0, ',', '.') }}</div><div class="sv-meta">Dialokasi {{ number_format($mapping['internal_allocated'] ?? 0, 0, ',', '.') }}</div></td>
                    <td><div class="sv-average">{{ number_format((float) $variant->average_daily_sales, 1, ',', '.') }}<small>Inventory · {{ $averageWindow }} hari</small></div></td>
                    <td>
                        <form class="sv-stock-form" data-stock-form data-product-id="{{ $product?->id }}" data-model-id="{{ $variant->model_id }}">
                            <label class="visually-hidden" for="stock-{{ $variant->id }}">Stok baru {{ $variant->model_name }}</label>
                            <input class="sv-stock-input" id="stock-{{ $variant->id }}" name="stock" type="number" min="0" step="1" value="{{ $available }}" required inputmode="numeric">
                            <button class="sv-save" type="submit">Simpan</button>
                        </form>
                        <span class="sv-status" data-stock-status aria-live="polite">Stok saat ini: {{ number_format($variant->stock) }}</span>
                    </td>
                    <td><span class="sv-sync">{{ $product?->synced_at?->diffForHumans() ?: 'Belum pernah' }}</span></td>
                </tr>
            @empty
                <tr data-empty-row><td colspan="7" class="sv-empty"><i class="bi bi-check2-circle"></i><strong>Tidak ada variant ditemukan</strong><div class="mt-1">Tidak ada stok kosong yang cocok dengan filter dan average penjualan ini.</div></td></tr>
            @endforelse
        </tbody></table></div>
        @if($variants->hasPages())<div class="sv-pages">{{ $variants->links() }}</div>@endif
    </section>
    </div>
</main>
@endsection

@push('scripts')
<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let filterTimer = null;
    let activeRequest = null;
    const setCount = (name, delta) => {
        const el = document.querySelector(`[data-summary="${name}"]`);
        if (!el) return;
        const value = Number(String(el.textContent).replace(/\D/g, '')) || 0;
        el.textContent = Math.max(0, value + delta).toLocaleString('id-ID');
    };
    const showEmpty = () => {
        const rows = document.getElementById('svRows');
        if (!rows || rows.querySelector('[data-stock-row]')) return;
        rows.innerHTML = '<tr data-empty-row><td colspan="7" class="sv-empty"><i class="bi bi-check2-circle"></i><strong>Semua stok variant terisi</strong><div class="mt-1">Perubahan berhasil dikirim ke marketplace.</div></td></tr>';
    };

    const bindStockActions = () => {
    document.querySelectorAll('[data-stock-form]').forEach(form => form.addEventListener('submit', async event => {
        event.preventDefault();
        const input = form.querySelector('.sv-stock-input');
        const button = form.querySelector('.sv-save');
        const row = form.closest('[data-stock-row]');
        const status = form.parentElement.querySelector('[data-stock-status]');
        const stock = Number(input.value);
        if (!Number.isInteger(stock) || stock < 0) { status.textContent = 'Masukkan stok 0 atau lebih.'; status.className = 'sv-status error'; return; }

        button.disabled = true; input.disabled = true; status.textContent = 'Menyimpan…'; status.className = 'sv-status';
        try {
            const response = await fetch(`/api/marketplace/products/${form.dataset.productId}/stock`, {
                method: 'POST',
                headers: {'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},
                body: JSON.stringify({stock_list:[{model_id:form.dataset.modelId,stock}]})
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            const previous = Number(row.dataset.currentStock || 0);
            status.textContent = 'Tersimpan'; status.className = 'sv-status ok'; row.dataset.currentStock = String(stock);
            if (stock > 0) {
                setCount('total', -1); setCount(previous < 0 ? 'negative' : 'zero', -1);
                row.remove(); showEmpty();
            } else if (previous < 0 && stock === 0) {
                setCount('negative', -1); setCount('zero', 1);
                status.textContent = 'Tersimpan · stok masih 0';
            }
        } catch (error) {
            status.textContent = error.message || 'Gagal menyimpan stok.'; status.className = 'sv-status error';
        } finally {
            if (document.body.contains(button)) { button.disabled = false; input.disabled = false; }
        }
    }));
    };

    const dashboardUrl = form => {
        const params = new URLSearchParams(new FormData(form));
        [...params.entries()].forEach(([key, value]) => { if (String(value).trim() === '') params.delete(key); });
        params.delete('page');
        const query = params.toString();
        return `${form.action}${query ? `?${query}` : ''}`;
    };

    const loadDashboard = async (url, pushState = true) => {
        const content = document.getElementById('svDashboardContent');
        if (!content) { window.location.assign(url); return; }
        activeRequest?.abort();
        activeRequest = new AbortController();
        const activeId = document.activeElement?.id;
        content.classList.add('is-loading'); content.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, {headers:{'Accept':'text/html','X-Requested-With':'XMLHttpRequest'}, signal:activeRequest.signal});
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const documentResult = new DOMParser().parseFromString(await response.text(), 'text/html');
            const fresh = documentResult.getElementById('svDashboardContent');
            if (!fresh) throw new Error('Konten filter tidak ditemukan.');
            content.innerHTML = fresh.innerHTML;
            if (pushState) window.history.pushState({}, '', url);
            bindAll();
            const focusTarget = activeId ? document.getElementById(activeId) : null;
            if (focusTarget) { focusTarget.focus(); if (focusTarget.setSelectionRange) focusTarget.setSelectionRange(focusTarget.value.length, focusTarget.value.length); }
        } catch (error) {
            if (error.name !== 'AbortError') window.location.assign(url);
        } finally {
            content.classList.remove('is-loading'); content.removeAttribute('aria-busy');
        }
    };

    const bindRealtimeFilter = () => {
        const form = document.querySelector('[data-realtime-filter]');
        if (!form) return;
        form.addEventListener('submit', event => { event.preventDefault(); loadDashboard(dashboardUrl(form)); });
        form.querySelectorAll('select').forEach(control => control.addEventListener('change', () => loadDashboard(dashboardUrl(form))));
        form.querySelectorAll('input:not([type="hidden"])').forEach(control => control.addEventListener('input', () => {
            window.clearTimeout(filterTimer);
            filterTimer = window.setTimeout(() => loadDashboard(dashboardUrl(form)), 400);
        }));
    };

    const bindDashboardLinks = () => {
        document.querySelectorAll('[data-dashboard-link], .sv-pages a').forEach(link => link.addEventListener('click', event => {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault(); loadDashboard(link.href);
        }));
    };

    const bindAll = () => { bindStockActions(); bindRealtimeFilter(); bindDashboardLinks(); };
    window.addEventListener('popstate', () => loadDashboard(window.location.href, false));
    bindAll();
})();
</script>
@endpush
