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
    .sv-filter{display:grid;grid-template-columns:minmax(180px,2fr) repeat(4,minmax(120px,1fr)) auto;gap:.45rem;margin-bottom:.65rem;padding:.6rem;border:1px solid rgba(148,163,184,.2);border-radius:10px;background:var(--card,#fff)}
    .sv-control,.sv-filter .btn{min-height:36px;border-radius:7px;font-size:.76rem}.sv-filter .btn{font-weight:750}
    .sv-panel{overflow:hidden;border:1px solid rgba(148,163,184,.2);border-radius:10px;background:var(--card,#fff)}.sv-scroll{overflow-x:auto}
    .sv-table{width:100%;min-width:1060px;border-collapse:collapse}.sv-table th{padding:.58rem .65rem;border-bottom:1px solid rgba(148,163,184,.2);color:#64748b;font-size:.65rem;font-weight:750;text-transform:uppercase;letter-spacing:.035em;text-align:left;white-space:nowrap}
    .sv-table td{padding:.62rem .65rem;border-top:1px solid rgba(148,163,184,.13);font-size:.75rem;vertical-align:middle}.sv-table tbody tr:first-child td{border-top:0}
    .sv-product{display:flex;align-items:center;gap:.55rem;min-width:235px}.sv-img{width:38px;height:38px;flex:none;border-radius:8px;object-fit:cover;background:#f1f5f9;border:1px solid rgba(148,163,184,.18)}.sv-img-empty{display:grid;place-items:center;color:#94a3b8}.sv-name{max-width:290px;font-weight:760;line-height:1.3}.sv-meta{margin-top:.14rem;color:#64748b;font-size:.65rem}
    .sv-variant{min-width:160px;font-weight:760}.sv-sku{color:#475569;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.67rem}.sv-map{min-width:180px}.sv-map-code{font-weight:760}.sv-internal{color:#047857;font-weight:750;font-variant-numeric:tabular-nums}.sv-average{font-size:.9rem;font-weight:800;font-variant-numeric:tabular-nums}.sv-average small{display:block;color:#64748b;font-size:.61rem;font-weight:500}
    .sv-stock-form{display:flex;align-items:center;gap:.35rem;min-width:190px}.sv-stock-input{width:78px;min-height:34px;border:1px solid rgba(148,163,184,.35);border-radius:7px;padding:.3rem .45rem;background:transparent;color:inherit;font-weight:750;font-variant-numeric:tabular-nums}.sv-stock-input:focus{outline:0;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12)}
    .sv-save{min-height:34px;border:0;border-radius:7px;padding:.35rem .6rem;background:#0f172a;color:#fff;font-size:.7rem;font-weight:750}.sv-save:hover{background:#1e293b}.sv-save:disabled{opacity:.55}.sv-use{border:0;background:transparent;color:#047857;padding:0;font-size:.64rem;text-decoration:underline}.sv-status{display:block;min-height:1em;margin-top:.2rem;color:#64748b;font-size:.62rem}.sv-status.error{color:#b91c1c}.sv-status.ok{color:#047857}
    .sv-sync{color:#64748b;font-size:.66rem;white-space:nowrap}.sv-empty{padding:3rem 1rem!important;text-align:center;color:#64748b}.sv-empty i{display:block;margin-bottom:.45rem;color:#16a34a;font-size:1.35rem}.sv-pages{padding:.6rem;border-top:1px solid rgba(148,163,184,.16)}
    body[data-theme="dark"] .sv-copy,body[data-theme="dark"] .sv-meta,body[data-theme="dark"] .sv-sync{color:#94a3b8}body[data-theme="dark"] .sv-sku{color:#cbd5e1}body[data-theme="dark"] .sv-save{background:#e2e8f0;color:#0f172a}
    @media(max-width:900px){.sv-filter{grid-template-columns:1fr 1fr}.sv-filter .sv-search{grid-column:1/-1}.sv-filter .btn{grid-column:1/-1}}
    @media(max-width:575px){.sv-page{padding:.75rem .75rem 3rem}.sv-head{flex-direction:column}.sv-back{width:100%;justify-content:center}.sv-filter{grid-template-columns:1fr}.sv-filter .sv-search,.sv-filter .btn{grid-column:auto}.sv-title{font-size:1.1rem}}
</style>
@endpush

@section('content')
@php
    $baseFilters = array_filter(['store_id' => $storeId ?: null, 'channel' => $channel ?: null, 'search' => $search ?: null, 'min_avg_sales' => $minAverageSales], fn ($value) => $value !== null && $value !== '');
@endphp
<main class="sv-page">
    <header class="sv-head">
        <div><h1 class="sv-title">Stok Variant Kosong</h1><p class="sv-copy">Variant ter-mapping dengan stok marketplace kosong. Average penjualan dihitung per hari dari 30 hari terakhir.</p></div>
        <a class="sv-back" href="{{ route('marketplace.products') }}"><i class="bi bi-arrow-left"></i>Produk</a>
    </header>

    <nav class="sv-summary" aria-label="Ringkasan stok">
        <a class="sv-stat {{ $stockStatus === '' ? 'active' : '' }}" href="{{ route('marketplace.products.empty-variants', $baseFilters) }}">Semua <strong data-summary="total">{{ number_format($summary['total']) }}</strong></a>
        <a class="sv-stat {{ $stockStatus === 'zero' ? 'active' : '' }}" href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['stock_status' => 'zero'])) }}">Stok 0 <strong data-summary="zero">{{ number_format($summary['zero']) }}</strong></a>
        <a class="sv-stat {{ $stockStatus === 'negative' ? 'active' : '' }}" href="{{ route('marketplace.products.empty-variants', array_merge($baseFilters, ['stock_status' => 'negative'])) }}">Minus <strong data-summary="negative">{{ number_format($summary['negative']) }}</strong></a>
        <span class="sv-stat">Produk <strong>{{ number_format($summary['products']) }}</strong></span>
    </nav>

    <form class="sv-filter" method="GET" action="{{ route('marketplace.products.empty-variants') }}">
        <label class="visually-hidden" for="svSearch">Cari produk, variant, atau SKU</label><input class="form-control sv-control sv-search" id="svSearch" name="search" value="{{ $search }}" maxlength="100" placeholder="Cari produk, variant, atau SKU…">
        <label class="visually-hidden" for="svStore">Toko</label><select class="form-select sv-control" id="svStore" name="store_id"><option value="">Semua toko</option>@foreach($stores as $store)<option value="{{ $store->id }}" @selected($storeId === $store->id)>{{ $store->name }}</option>@endforeach</select>
        <label class="visually-hidden" for="svChannel">Channel</label><select class="form-select sv-control" id="svChannel" name="channel"><option value="">Semua channel</option><option value="shopee" @selected($channel === 'shopee')>Shopee</option><option value="tiktok" @selected($channel === 'tiktok')>TikTok Shop</option></select>
        <label class="visually-hidden" for="svStock">Status stok</label><select class="form-select sv-control" id="svStock" name="stock_status"><option value="">Stok 0 & minus</option><option value="zero" @selected($stockStatus === 'zero')>Stok tepat 0</option><option value="negative" @selected($stockStatus === 'negative')>Stok minus</option></select>
        <label class="visually-hidden" for="svAverage">Average penjualan lebih dari</label><input class="form-control sv-control" id="svAverage" name="min_avg_sales" type="number" min="0" max="1000000" step="0.1" value="{{ $minAverageSales }}" placeholder="Avg jual/hari >">
        <button class="btn btn-primary" type="submit">Terapkan</button>
    </form>

    <section class="sv-panel" aria-live="polite">
        <div class="sv-scroll"><table class="sv-table"><thead><tr><th>Produk</th><th>Variant</th><th>Mapping & stok internal</th><th>Avg jual/hari</th><th>Stok marketplace baru</th><th>Terakhir sync</th></tr></thead><tbody id="svRows">
            @forelse($variants as $variant)
                @php
                    $product = $variant->product;
                    $mapping = $variant->mapping_detail;
                    $available = max(0, (int) floor($mapping['internal_available'] ?? 0));
                @endphp
                <tr data-stock-row data-current-stock="{{ $variant->stock }}">
                    <td><div class="sv-product">@if($product?->image_url)<img class="sv-img" src="{{ $product->image_url }}" alt="" loading="lazy">@else<span class="sv-img sv-img-empty"><i class="bi bi-image"></i></span>@endif<div><div class="sv-name">{{ $product?->item_name ?: 'Produk tanpa nama' }}</div><div class="sv-meta">{{ $product?->store?->name ?: 'Toko tidak ditemukan' }} · {{ $product?->item_id }}</div></div></div></td>
                    <td><div class="sv-variant">{{ $variant->model_name ?: 'Variant tanpa nama' }}</div><div class="sv-sku">{{ $variant->model_sku }}</div></td>
                    <td class="sv-map"><div class="sv-map-code">{{ $mapping['item_code'] ?? '—' }}</div><div class="sv-meta">{{ $mapping['item_name'] ?? 'Item internal' }}</div><div class="sv-meta">Tersedia <span class="sv-internal">{{ number_format($available, 0, ',', '.') }}</span> · fisik {{ number_format($mapping['internal_physical'] ?? 0, 0, ',', '.') }}</div></td>
                    <td><div class="sv-average">{{ number_format((float) $variant->average_daily_sales, 1, ',', '.') }}<small>30 hari</small></div></td>
                    <td>
                        <form class="sv-stock-form" data-stock-form data-product-id="{{ $product?->id }}" data-model-id="{{ $variant->model_id }}">
                            <label class="visually-hidden" for="stock-{{ $variant->id }}">Stok baru {{ $variant->model_name }}</label>
                            <input class="sv-stock-input" id="stock-{{ $variant->id }}" name="stock" type="number" min="0" step="1" value="0" required inputmode="numeric">
                            <button class="sv-save" type="submit">Simpan</button>
                        </form>
                        @if($available > 0)<button class="sv-use" type="button" data-use-stock="{{ $available }}">Pakai stok tersedia: {{ number_format($available, 0, ',', '.') }}</button>@endif
                        <span class="sv-status" data-stock-status aria-live="polite">Stok saat ini: {{ number_format($variant->stock) }}</span>
                    </td>
                    <td><span class="sv-sync">{{ $product?->synced_at?->diffForHumans() ?: 'Belum pernah' }}</span></td>
                </tr>
            @empty
                <tr data-empty-row><td colspan="6" class="sv-empty"><i class="bi bi-check2-circle"></i><strong>Tidak ada variant ditemukan</strong><div class="mt-1">Tidak ada stok kosong yang cocok dengan filter dan average penjualan ini.</div></td></tr>
            @endforelse
        </tbody></table></div>
        @if($variants->hasPages())<div class="sv-pages">{{ $variants->links() }}</div>@endif
    </section>
</main>
@endsection

@push('scripts')
<script>
(() => {
    const rows = document.getElementById('svRows');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const setCount = (name, delta) => {
        const el = document.querySelector(`[data-summary="${name}"]`);
        if (!el) return;
        const value = Number(String(el.textContent).replace(/\D/g, '')) || 0;
        el.textContent = Math.max(0, value + delta).toLocaleString('id-ID');
    };
    const showEmpty = () => {
        if (!rows || rows.querySelector('[data-stock-row]')) return;
        rows.innerHTML = '<tr data-empty-row><td colspan="6" class="sv-empty"><i class="bi bi-check2-circle"></i><strong>Semua stok variant terisi</strong><div class="mt-1">Perubahan berhasil dikirim ke marketplace.</div></td></tr>';
    };

    document.querySelectorAll('[data-use-stock]').forEach(button => button.addEventListener('click', () => {
        const input = button.closest('td')?.querySelector('.sv-stock-input');
        if (input) { input.value = button.dataset.useStock || '0'; input.focus(); }
    }));

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
})();
</script>
@endpush
