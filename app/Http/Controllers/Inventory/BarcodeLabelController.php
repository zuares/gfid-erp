<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Generator barcode label global.
 * Admin memilih item + jumlah label, lalu mencetak label thermal (barcode = kode item).
 */
class BarcodeLabelController extends Controller
{
    /** Batas aman supaya tidak meledakkan halaman print. */
    private const MAX_TOTAL_LABELS = 2000;
    private const MAX_QTY_PER_ITEM = 500;

    /**
     * Form: pilih item + qty label.
     */
    public function create(): View
    {
        return view('inventory.barcodes.create');
    }

    /**
     * Halaman print: bangun daftar label dari input id[] + qty[].
     * GET agar bisa di-reload / dibuka di tab baru dari form.
     */
    public function print(Request $request): View
    {
        $ids  = (array) $request->query('id', []);
        $qtys = (array) $request->query('qty', []);

        // Rangkai pasangan (item_id => qty) sambil buang baris kosong & duplikat digabung.
        $requested = [];
        foreach ($ids as $i => $rawId) {
            $itemId = (int) $rawId;
            if ($itemId <= 0) {
                continue;
            }
            $qty = (int) ($qtys[$i] ?? 1);
            $qty = max(1, min(self::MAX_QTY_PER_ITEM, $qty));
            $requested[$itemId] = ($requested[$itemId] ?? 0) + $qty;
        }

        $items = Item::query()
            ->select('id', 'code', 'name', 'unit')
            ->whereIn('id', array_keys($requested))
            ->get()
            ->keyBy('id');

        // Bangun array label (satu entri = satu label yang dicetak).
        $labels = [];
        $total  = 0;
        foreach ($requested as $itemId => $qty) {
            $item = $items->get($itemId);
            if (!$item || !$item->code) {
                continue;
            }
            for ($n = 0; $n < $qty; $n++) {
                if ($total >= self::MAX_TOTAL_LABELS) {
                    break 2;
                }
                $labels[] = [
                    'code' => $item->code,
                    'name' => $item->name,
                ];
                $total++;
            }
        }

        // 27 label per sheet (9 baris x 3 kolom) = pas 100x150mm.
        $perPage = 27;
        $pages   = array_chunk($labels, $perPage);

        // Back-link bisa dikonfigurasi (mis. dari halaman GRN). Hanya terima path internal.
        $back = (string) $request->query('back', '');
        $backUrl = (str_starts_with($back, '/') && !str_starts_with($back, '//'))
            ? $back
            : route('inventory.barcodes.create');

        return view('inventory.barcodes.print', [
            'labels'   => $labels,
            'pages'    => $pages,
            'total'    => count($labels),
            'itemCount' => count($requested),
            'backUrl'  => $backUrl,
        ]);
    }
}
