@php
    use Illuminate\Support\Facades\Route;

    $stockSummary = $d['marketplace_variant_stock'] ?? [];
    $r = fn (string $name, array $params = []) => Route::has($name) ? route($name, $params) : null;
    $emptyMarketplaceUrl = $r('marketplace.products.empty-variants', [
        'stock_scope' => 'marketplace_empty',
        'stock_status' => 'zero',
    ]);
    $internalEmptyUrl = $r('marketplace.products.empty-variants', [
        'stock_scope' => 'internal_empty_marketplace_stock',
    ]);
@endphp

<section aria-labelledby="dashboard-marketplace-stock-title">
    <div class="dash-sec" id="dashboard-marketplace-stock-title">
        <i class="bi bi-box-seam" aria-hidden="true"></i> Stok marketplace
    </div>
    <div class="dash-stock-alert-grid">
        @if($emptyMarketplaceUrl)
            <a class="dash-stock-alert dash-stock-alert-warning" href="{{ $emptyMarketplaceUrl }}">
        @else
            <div class="dash-stock-alert dash-stock-alert-warning">
        @endif
                <span class="dash-stock-alert-icon" aria-hidden="true"><i class="bi bi-arrow-repeat"></i></span>
                <span class="dash-stock-alert-copy">
                    <strong>Stok MP 0 · Internal Ada</strong>
                    <small>Variant yang siap diperbarui ke marketplace</small>
                </span>
                <span class="dash-stock-alert-value">{{ number_format((int) ($stockSummary['marketplace_zero_internal_available'] ?? 0), 0, ',', '.') }}</span>
                <i class="bi bi-chevron-right dash-stock-alert-arrow" aria-hidden="true"></i>
        @if($emptyMarketplaceUrl)
            </a>
        @else
            </div>
        @endif

        @if($internalEmptyUrl)
            <a class="dash-stock-alert dash-stock-alert-danger" href="{{ $internalEmptyUrl }}">
        @else
            <div class="dash-stock-alert dash-stock-alert-danger">
        @endif
                <span class="dash-stock-alert-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></span>
                <span class="dash-stock-alert-copy">
                    <strong>Stok Kosong tapi di Marketplace Ada Stok</strong>
                    <small>Perlu audit stok internal dan marketplace</small>
                </span>
                <span class="dash-stock-alert-value">{{ number_format((int) ($stockSummary['internal_empty_marketplace_stock'] ?? 0), 0, ',', '.') }}</span>
                <i class="bi bi-chevron-right dash-stock-alert-arrow" aria-hidden="true"></i>
        @if($internalEmptyUrl)
            </a>
        @else
            </div>
        @endif
    </div>
</section>
