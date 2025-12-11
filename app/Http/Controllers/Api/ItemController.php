<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemBarcode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ItemController extends Controller
{

    /**
     * Helper: apply filter umum untuk Item query.
     *
     * - q         : search kode / nama / kategori (multi kata + compact fuzzy)
     * - type      : single / multi (comma separated)
     * - item_category_id
     * - active    : default 1
     * - lot_id    : filter item yang terkait LOT kain tertentu (Cutting, dll)
     */
    protected function applyCommonFilters($query, Request $request)
    {
        // 🔎 Search kode / nama (bisa multi kata + compact code)
        if ($search = trim($request->input('q', ''))) {
            // Normalisasi: spasi, dash, slash, underscore → spasi
            $normalized = preg_replace('/[\s\-\/_]+/', ' ', $search);

            // "CRG BLK" → ["CRG", "BLK"]
            $tokens = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

            // Compact string: buang semua non-alnum → "FLC 280-BLK" → "FLC280BLK"
            $compact = preg_replace('/[^A-Za-z0-9]/', '', $search);

            // Fuzzy pattern: "FLCBLK" → "%F%L%C%B%L%K%"
            $fuzzyPattern = null;
            if (strlen($compact) >= 2) {
                $fuzzyPattern = '%' . implode('%', str_split($compact)) . '%';
            }

            $query->where(function ($q) use ($tokens, $fuzzyPattern) {
                // 🔹 Logic lama: AND per token ("CRG BLK" dll)
                foreach ($tokens as $token) {
                    $token = trim($token);
                    if ($token === '') {
                        continue;
                    }

                    $q->where(function ($qq) use ($token) {
                        $like = '%' . $token . '%';

                        $qq->where('code', 'like', $like)
                            ->orWhere('name', 'like', $like)
                        // optional: cari juga di nama kategori
                            ->orWhereHas('category', function ($qc) use ($like) {
                                $qc->where('name', 'like', $like);
                            });
                    });
                }

                // 🔹 Extra: fuzzy match khusus di code (compact pattern)
                if ($fuzzyPattern) {
                    $q->orWhere('code', 'like', $fuzzyPattern);
                }
            });
        }

        // 🎯 Filter type (single / multi, comma separated)
        if ($type = $request->input('type')) {
            $types = collect(explode(',', $type))
                ->map(fn($t) => trim($t))
                ->filter()
                ->values();

            if ($types->isNotEmpty()) {
                $query->whereIn('type', $types);
            }
        }

        // 🎯 Filter kategori
        if ($categoryId = $request->input('item_category_id')) {
            $query->where('item_category_id', $categoryId);
        }

        // 🎯 Filter berdasarkan LOT (Cutting: item sesuai LOT kain yang dipilih)
        if ($lotId = $request->input('lot_id')) {
            // asumsi: tabel lots punya kolom item_id
            $itemId = DB::table('lots')
                ->where('id', $lotId)
                ->value('item_id');

            if ($itemId) {
                $query->where('items.id', $itemId);
            } else {
                // LOT tidak valid → jangan balikin apa-apa
                $query->whereRaw('1 = 0');
            }
        }

        // ✅ Filter active (default: hanya active=1)
        if ($request->has('active')) {
            $query->where('active', (int) $request->input('active') === 1);
        } else {
            $query->where('active', 1);
        }

        return $query;
    }

    /**
     * Helper: standard bentuk item JSON.
     *
     * $extra bisa diisi:
     * ['on_hand' => 10, 'warehouse_id' => 2, dll]
     */
    protected function mapItem(Item $item, array $extra = []): array
    {
        return array_merge([
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'type' => $item->type,
            'item_category_id' => $item->item_category_id,
            'item_category' => optional($item->category)->name,
        ], $extra);
    }

    /**
     * GET /api/v1/items
     *
     * Listing lengkap + pagination (lebih berat).
     *
     * Query umum:
     * - q
     * - type
     * - item_category_id
     * - active
     * - lot_id
     */
    public function index(Request $request)
    {
        $query = Item::query()->with('category');

        $this->applyCommonFilters($query, $request);

        $perPage = (int) $request->input('per_page', 20);
        $perPage = $perPage > 100 ? 100 : $perPage;

        $items = $query
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $items->getCollection()->map(function (Item $item) {
                return $this->mapItem($item);
            }),
            'meta' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
            'filters' => [
                'q' => $request->input('q'),
                'type' => $request->input('type'),
                'item_category_id' => $request->input('item_category_id'),
                'active' => $request->input('active', 1),
                'lot_id' => $request->input('lot_id'),
            ],
        ]);
    }

    /**
     * GET /api/v1/items/suggest
     *
     * Dipakai untuk autocomplete: return ringan.
     *
     * Query param:
     * - q
     * - type (opsional)
     * - item_category_id (opsional)
     * - limit (default 20, max 50)
     * - warehouse_id (opsional, kalau diisi → include on_hand di gudang tsb)
     * - lot_id (opsional, misal dari halaman Cutting)
     */
    public function suggest(Request $request)
    {
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 50 ? 50 : $limit;

        $warehouseId = $request->query('warehouse_id');

        $query = Item::query()
            ->with('category');

        $this->applyCommonFilters($query, $request);

        if ($warehouseId) {
            // join ke inventory_stocks untuk ambil stok per gudang
            $query->leftJoin('inventory_stocks as s', function ($join) use ($warehouseId) {
                $join->on('s.item_id', '=', 'items.id')
                    ->where('s.warehouse_id', '=', $warehouseId);
            })
                ->addSelect('items.*')
                ->addSelect(DB::raw('COALESCE(s.qty, 0) as on_hand'));
        }

        $query->orderBy('items.code')
            ->limit($limit);

        $items = $query->get();

        return response()->json([
            'data' => $items->map(function ($row) use ($warehouseId) {
                // kalau pakai join, $row->on_hand sudah ada, kalau tidak → null
                $extra = [];
                if ($warehouseId !== null) {
                    $extra['warehouse_id'] = (int) $warehouseId;
                    $extra['on_hand'] = isset($row->on_hand)
                    ? (float) $row->on_hand
                    : 0.0;
                }

                return $this->mapItem($row, $extra);
            }),
        ]);
    }

    /**
     * GET /api/v1/items/{item}
     */
    public function show(Item $item)
    {
        $item->load('category');

        return response()->json([
            'success' => true,
            'data' => $this->mapItem($item),
        ]);
    }

    /**
     * GET /api/v1/items/by-barcode?barcode=XXXX
     *
     * Dipakai di modul scan (shipment, adjustment, opname, dll).
     */
    public function findByBarcode(Request $request)
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:190'],
        ]);

        $barcode = trim($validated['barcode']);

        if ($barcode === '') {
            return response()->json([
                'success' => false,
                'message' => 'Barcode kosong.',
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var \App\Models\ItemBarcode|null $barcodeRow */
        $barcodeRow = ItemBarcode::query()
            ->where('barcode', $barcode)
            ->where('is_active', true)
            ->with(['item.category'])
            ->first();

        if (!$barcodeRow || !$barcodeRow->item) {
            return response()->json([
                'success' => false,
                'message' => 'Item dengan barcode tersebut tidak ditemukan atau nonaktif.',
            ], Response::HTTP_NOT_FOUND);
        }

        $item = $barcodeRow->item;

        return response()->json([
            'success' => true,
            'data' => [
                'item' => $this->mapItem($item),
                'barcode' => [
                    'id' => $barcodeRow->id,
                    'barcode' => $barcodeRow->barcode,
                    'type' => $barcodeRow->type,
                    'notes' => $barcodeRow->notes,
                ],
            ],
        ]);
    }
}
