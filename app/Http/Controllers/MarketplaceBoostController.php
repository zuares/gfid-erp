<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceBoostLog;
use App\Models\MarketplaceBoostPool;
use App\Models\MarketplaceBoostSchedule;
use App\Models\MarketplaceProduct;
use App\Models\Store;
use App\Services\MarketplaceBoostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MarketplaceBoostController extends Controller
{
    public function __construct(protected MarketplaceBoostService $boost) {}

    public function page()
    {
        return view('marketplace.boost');
    }

    /** Status slot: produk yang sedang di-boost + sisa waktu. */
    public function status(Request $request)
    {
        $store = $this->resolveStore($request);
        if ($resp = $this->assertConnected($store)) return $resp;

        return response()->json(['store_id' => $store->id] + $this->boost->currentlyBoosted($store));
    }

    /** Naikkan manual sekarang. Body: {store_id, product_ids:[]} */
    public function boostNow(Request $request)
    {
        $data = $request->validate([
            'store_id'     => 'nullable|integer',
            'product_ids'  => 'required|array|min:1|max:5',
            'product_ids.*'=> 'integer',
        ]);

        $store = $this->resolveStore($request);
        if ($resp = $this->assertConnected($store, 'message')) return $resp;

        $products = MarketplaceProduct::where('store_id', $store->id)
            ->whereIn('id', $data['product_ids'])->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'Produk tidak ditemukan di toko ini.'], 422);
        }

        $res = $this->boost->boostNow($store, $products, 'manual');
        return response()->json($res, $res['success'] ? 200 : 422);
    }

    /** Riwayat eksekusi boost terbaru. */
    public function logs(Request $request)
    {
        $store = $this->resolveStore($request);
        $rows = MarketplaceBoostLog::with('product:id,item_name,item_sku')
            ->when($store, fn ($q) => $q->where('store_id', $store->id))
            ->orderByDesc('boosted_at')
            ->limit(100)
            ->get()
            ->map(fn ($l) => [
                'id'         => $l->id,
                'product'    => $l->product?->item_name ?? ('item ' . $l->item_id),
                'sku'        => $l->product?->item_sku,
                'source'     => $l->source,
                'success'    => $l->success,
                'message'    => $l->message,
                'boosted_at' => $l->boosted_at?->toIso8601String(),
                'expires_at' => $l->expires_at?->toIso8601String(),
            ]);

        return response()->json(['logs' => $rows]);
    }

    // ─── Jadwal jam-tetap ────────────────────────────────────────────────────────

    public function schedules(Request $request)
    {
        $store = $this->resolveStore($request);

        $rows = MarketplaceBoostSchedule::with('product:id,item_name,item_sku,image_url,item_status')
            ->when($store, fn ($q) => $q->where('store_id', $store->id))
            ->orderBy('boost_time')
            ->get()
            ->map(fn ($s) => [
                'id'              => $s->id,
                'product_id'      => $s->marketplace_product_id,
                'product'         => $s->product?->item_name,
                'sku'             => $s->product?->item_sku,
                'image_url'       => $s->product?->image_url,
                'time'            => Carbon::parse($s->boost_time)->format('H:i'),
                'priority'        => $s->priority,
                'is_active'       => $s->is_active,
                'last_boosted_at' => $s->last_boosted_at?->toIso8601String(),
            ]);

        return response()->json(['schedules' => $rows]);
    }

    /** Body: {store_id, marketplace_product_id, times:["08:00","20:00"], priority?} */
    public function storeSchedule(Request $request)
    {
        $data = $request->validate([
            'store_id'               => 'nullable|integer',
            'marketplace_product_id' => 'required|integer',
            'times'                  => 'required|array|min:1',
            'times.*'                => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'priority'               => 'nullable|integer|min:0|max:255',
        ]);

        $store = $this->resolveStore($request);
        $product = MarketplaceProduct::where('id', $data['marketplace_product_id'])
            ->when($store, fn ($q) => $q->where('store_id', $store->id))->first();
        if (! $product) return response()->json(['message' => 'Produk tidak ditemukan.'], 422);

        $created = 0;
        foreach (array_unique($data['times']) as $t) {
            $sch = MarketplaceBoostSchedule::updateOrCreate(
                ['marketplace_product_id' => $product->id, 'boost_time' => $t . ':00'],
                ['store_id' => $product->store_id, 'priority' => $data['priority'] ?? 0, 'is_active' => true]
            );
            if ($sch->wasRecentlyCreated) $created++;
        }

        return response()->json([
            'created' => $created,
            'message' => "Jadwal disimpan ({$created} slot baru).",
        ]);
    }

    public function toggleSchedule(MarketplaceBoostSchedule $schedule)
    {
        $schedule->update(['is_active' => ! $schedule->is_active]);
        return response()->json(['success' => true, 'is_active' => $schedule->is_active]);
    }

    public function destroySchedule(MarketplaceBoostSchedule $schedule)
    {
        $schedule->delete();
        return response()->json(['success' => true]);
    }

    // ─── Antrian rotasi (pool) ────────────────────────────────────────────────────

    public function pool(Request $request)
    {
        $store = $this->resolveStore($request);

        $rows = MarketplaceBoostPool::with('product:id,item_name,item_sku,image_url,item_status')
            ->when($store, fn ($q) => $q->where('store_id', $store->id))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($p) => [
                'id'              => $p->id,
                'product_id'      => $p->marketplace_product_id,
                'product'         => $p->product?->item_name,
                'sku'             => $p->product?->item_sku,
                'image_url'       => $p->product?->image_url,
                'is_active'       => $p->is_active,
                'last_boosted_at' => $p->last_boosted_at?->toIso8601String(),
            ]);

        return response()->json(['pool' => $rows]);
    }

    /** Body: {store_id, product_ids:[]} */
    public function storePool(Request $request)
    {
        $data = $request->validate([
            'store_id'      => 'nullable|integer',
            'product_ids'   => 'required|array|min:1',
            'product_ids.*' => 'integer',
        ]);

        $store = $this->resolveStore($request);
        $products = MarketplaceProduct::whereIn('id', $data['product_ids'])
            ->when($store, fn ($q) => $q->where('store_id', $store->id))->get();

        $added = 0;
        foreach ($products as $p) {
            $row = MarketplaceBoostPool::firstOrCreate(
                ['store_id' => $p->store_id, 'marketplace_product_id' => $p->id],
                ['is_active' => true]
            );
            if ($row->wasRecentlyCreated) $added++;
        }

        return response()->json(['added' => $added, 'message' => "{$added} produk ditambahkan ke antrian rotasi."]);
    }

    public function togglePool(MarketplaceBoostPool $poolItem)
    {
        $poolItem->update(['is_active' => ! $poolItem->is_active]);
        return response()->json(['success' => true, 'is_active' => $poolItem->is_active]);
    }

    public function destroyPool(MarketplaceBoostPool $poolItem)
    {
        $poolItem->delete();
        return response()->json(['success' => true]);
    }

    // ─── Helper ─────────────────────────────────────────────────────────────────

    /**
     * Toko dari request, atau — kalau tidak dispesifikkan — toko Shopee aktif
     * pertama yang BENAR-BENAR terhubung (punya access_token). Ini mencegah
     * default ke toko yang belum di-authorize sehingga selalu "invalid token".
     */
    protected function resolveStore(Request $request): ?Store
    {
        $shopee = Store::whereHas('channel', fn ($c) => $c->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('status', 'active')->orderBy('id')->get();

        if ($request->filled('store_id')) {
            return $shopee->firstWhere('id', $request->integer('store_id'))
                ?? Store::find($request->integer('store_id'));
        }

        return $shopee->first(fn ($s) => filled($s->credential('access_token'))) ?? $shopee->first();
    }

    /**
     * Pastikan toko ada & sudah terhubung ke Shopee. Kalau belum, balas pesan
     * jelas (bukan "invalid token" yang membingungkan).
     */
    protected function assertConnected(?Store $store, string $key = 'error'): ?JsonResponse
    {
        if (! $store) {
            return response()->json([$key => 'Toko Shopee tidak ditemukan.', 'items' => []], 422);
        }
        if (blank($store->credential('access_token'))) {
            return response()->json([
                $key            => "Toko \"{$store->name}\" belum terhubung ke Shopee. Buka menu Toko lalu klik Authorize/Re-authorize dulu.",
                'items'         => [],
                'not_connected' => true,
            ], 422);
        }
        return null;
    }
}
