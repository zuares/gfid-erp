<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @deprecated  Digantikan oleh MarketplaceController::ads() + syncAdCampaigns().
 *              Data iklan sekarang ditarik langsung dari Shopee Ads API.
 *              File ini bisa dihapus setelah routes/web/imports.php dibersihkan.
 */
class MpAdsImportController extends Controller
{
    /** Semua route lama diarahkan ke halaman Analisa Iklan baru. */
    public function __call(string $name, array $args)
    {
        return redirect()->route('marketplace.ads')
            ->with('info', 'Halaman import CSV ads sudah dipindahkan ke Analisa Iklan (API-based).');
    }
}
