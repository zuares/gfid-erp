<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemBom;
use App\Models\ItemBomLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ItemBomController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $boms = ItemBom::query()
            ->with(['item:id,code,name'])
            ->when($q !== '', function ($qq) use ($q) {
                $qq->whereHas('item', function ($i) use ($q) {
                    $i->where('code', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('master.item_boms.index', compact('boms', 'q'));
    }

    public function create()
    {
        return view('master.item_boms.form', [
            'bom' => null,
        ]);
    }

    public function edit(ItemBom $bom)
    {
        $bom->load([
            'item:id,code,name',
            'lines' => fn($q) => $q->orderBy('sort_order')->with('material:id,code,name,unit'),
        ]);

        return view('master.item_boms.form', compact('bom'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);

        return DB::transaction(function () use ($data) {
            $item = Item::where('id', $data['item_id'])->firstOrFail();

            $bom = ItemBom::updateOrCreate(
                ['item_id' => $item->id],
                ['name' => $data['name'] ?: ('BOM ' . $item->code), 'active' => (bool) $data['active']]
            );

            $this->syncLines($bom, $data['lines']);

            return redirect()->route('master.item_boms.edit', $bom)->with('success', 'BOM tersimpan.');
        });
    }

    public function update(Request $request, ItemBom $bom)
    {
        $data = $this->validatePayload($request, isUpdate: true);

        return DB::transaction(function () use ($bom, $data) {
            // item_id jangan diubah di update (biar konsisten 1 BOM per SKU)
            $bom->update([
                'name' => $data['name'] ?: $bom->name,
                'active' => (bool) $data['active'],
            ]);

            $this->syncLines($bom, $data['lines']);

            return redirect()->route('master.item_boms.edit', $bom)->with('success', 'BOM terupdate.');
        });
    }

    private function validatePayload(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'item_id' => [$isUpdate ? 'nullable' : 'required', 'integer', 'exists:items,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'in:0,1'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.material_item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.01'],
            'lines.*.uom' => ['nullable', 'string', 'max:20'],
            'lines.*.scrap_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.is_optional' => ['nullable', 'in:0,1'],
            'lines.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];

        $data = $request->validate($rules);

        // FIX: jangan default true
        $data['active'] = $request->boolean('active');

        foreach ($data['lines'] as $idx => $l) {
            $data['lines'][$idx]['uom'] = $l['uom'] ?? 'pcs';
            $data['lines'][$idx]['scrap_pct'] = $l['scrap_pct'] ?? 0;
            $data['lines'][$idx]['is_optional'] = $request->boolean("lines.$idx.is_optional");
            $data['lines'][$idx]['sort_order'] = $l['sort_order'] ?? ($idx * 10);
        }

        // duplikat material
        $ids = array_map(fn($l) => (int) $l['material_item_id'], $data['lines']);
        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'lines' => 'Material tidak boleh duplikat dalam 1 BOM (gabungkan qty-nya).',
            ]);
        }

        return $data;
    }

    private function syncLines(ItemBom $bom, array $lines): void
    {
        // replace all (paling aman & simpel)
        ItemBomLine::where('item_bom_id', $bom->id)->delete();

        foreach ($lines as $l) {
            ItemBomLine::create([
                'item_bom_id' => $bom->id,
                'material_item_id' => (int) $l['material_item_id'],
                'qty' => (float) $l['qty'],
                'uom' => $l['uom'],
                'scrap_pct' => (float) $l['scrap_pct'],
                'is_optional' => (bool) $l['is_optional'],
                'sort_order' => (int) $l['sort_order'],
            ]);
        }
    }

    // Select2 AJAX: FG atau Material
    public function ajaxItems(Request $request)
    {
        $type = $request->query('type', 'material'); // material / finished_good
        $q = trim((string) $request->query('q', ''));

        $items = Item::query()
            ->when($type, fn($qq) => $qq->where('type', $type))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            })
            ->orderBy('code')
            ->limit(30)
            ->get(['id', 'code', 'name', 'unit']);

        $results = $items->map(fn($i) => [
            'id' => $i->id,
            'text' => "{$i->code} — {$i->name}",
            'unit' => $i->unit,
        ]);

        return response()->json(['results' => $results]);
    }

    public function importForm()
    {
        return view('master.item_boms.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
            'mode' => ['nullable', 'in:replace,merge'],
        ]);

        $mode = $request->input('mode', 'replace');
        $path = $request->file('file')->getRealPath();

        $fh = fopen($path, 'r');
        if (!$fh) {
            return back()->withErrors(['file' => 'File tidak bisa dibuka.']);
        }

        $header = fgetcsv($fh);
        if (!$header) {
            return back()->withErrors(['file' => 'CSV kosong.']);
        }

        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        $required = ['sku_code', 'material_code', 'qty'];
        foreach ($required as $r) {
            if (!in_array($r, $header, true)) {
                return back()->withErrors(['file' => "Kolom wajib tidak ada: {$r}."]);
            }
        }

        $idx = array_flip($header);

        $rowsBySku = [];
        $lineNo = 1;
        while (($row = fgetcsv($fh)) !== false) {
            $lineNo++;

            $skuCode = strtoupper(trim((string) ($row[$idx['sku_code']] ?? '')));
            $matCode = strtoupper(trim((string) ($row[$idx['material_code']] ?? '')));
            $qty = (float) ($row[$idx['qty']] ?? 0);

            if ($skuCode === '' || $matCode === '' || $qty <= 0) {
                continue;
            }

            $uom = isset($idx['uom']) ? trim((string) $row[$idx['uom']]) : 'pcs';
            $scrap = isset($idx['scrap_pct']) ? (float) $row[$idx['scrap_pct']] : 0;
            $opt = isset($idx['is_optional']) ? (int) $row[$idx['is_optional']] : 0;
            $sort = isset($idx['sort_order']) ? (int) $row[$idx['sort_order']] : 0;

            $rowsBySku[$skuCode][] = [
                'material_code' => $matCode,
                'qty' => $qty,
                'uom' => $uom ?: 'pcs',
                'scrap_pct' => $scrap,
                'is_optional' => $opt ? 1 : 0,
                'sort_order' => $sort,
            ];
        }
        fclose($fh);

        if (!$rowsBySku) {
            return back()->withErrors(['file' => 'Tidak ada baris valid yang terbaca.']);
        }

        $created = 0;
        $updated = 0;
        $skipped = [];

        DB::transaction(function () use ($rowsBySku, $mode, &$created, &$updated, &$skipped) {
            foreach ($rowsBySku as $skuCode => $lines) {
                $sku = Item::where('code', $skuCode)->where('type', 'finished_good')->first();
                if (!$sku) {$skipped[] = "SKU not found: {$skuCode}";continue;}

                $bom = ItemBom::firstOrCreate(
                    ['item_id' => $sku->id],
                    ['name' => 'BOM ' . $sku->code, 'active' => true]
                );

                $isNew = $bom->wasRecentlyCreated;

                // map material codes -> ids
                $matMap = Item::whereIn('code', array_unique(array_column($lines, 'material_code')))
                    ->get(['id', 'code'])
                    ->keyBy('code');

                $normalized = [];
                $seen = [];
                foreach ($lines as $l) {
                    $code = $l['material_code'];
                    $mat = $matMap->get($code);
                    if (!$mat) {$skipped[] = "Material not found: {$skuCode} -> {$code}";continue;}
                    if (isset($seen[$mat->id])) {$skipped[] = "Duplicate material in CSV: {$skuCode} -> {$code}";continue;}

                    $seen[$mat->id] = true;
                    $normalized[] = [
                        'material_item_id' => $mat->id,
                        'qty' => (float) $l['qty'],
                        'uom' => $l['uom'] ?? 'pcs',
                        'scrap_pct' => (float) ($l['scrap_pct'] ?? 0),
                        'is_optional' => (bool) ($l['is_optional'] ?? 0),
                        'sort_order' => (int) ($l['sort_order'] ?? 0),
                    ];
                }

                if (!$normalized) {$skipped[] = "No valid lines: {$skuCode}";continue;}

                if ($mode === 'replace') {
                    ItemBomLine::where('item_bom_id', $bom->id)->delete();
                    foreach ($normalized as $n) {
                        ItemBomLine::create(['item_bom_id' => $bom->id] + $n);
                    }
                } else { // merge
                    $existing = ItemBomLine::where('item_bom_id', $bom->id)->get()->keyBy('material_item_id');
                    foreach ($normalized as $n) {
                        if ($existing->has($n['material_item_id'])) {
                            $existing[$n['material_item_id']]->update($n);
                        } else {
                            ItemBomLine::create(['item_bom_id' => $bom->id] + $n);
                        }
                    }
                }

                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                }

            }
        });

        return redirect()->route('master.item_boms.index')
            ->with('success', "Import selesai. Created: {$created}, Updated: {$updated}, Skipped: " . count($skipped))
            ->with('skipped', $skipped);
    }

    public function downloadTemplate()
    {
        $header = "sku_code,material_code,qty,uom,scrap_pct,is_optional,sort_order\n";

        // contoh 5 baris (boleh kamu ubah)
        $sample = [
            "C5BLK,FLC280BLK,1.20,pcs,2,0,10",
            "C5BLK,RIB280BLK,0.25,pcs,2,0,20",
            "C5BLK,TLKADDS,1.00,pcs,0,0,30",
            "C5BLK,KRT4CM,1.00,pcs,0,0,40",
            "C5BLK,BNGJHT,0.10,pcs,0,0,50",
        ];

        $csv = $header . implode("\n", $sample) . "\n";

        $filename = 'item_bom_template_' . now()->format('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function duplicateForm()
    {
        return view('master.item_boms.duplicate');
    }

    public function duplicate(Request $request)
    {
        $data = $request->validate([
            'from_item_id' => ['required', 'integer', 'exists:items,id'],
            'to_item_id' => ['required', 'integer', 'exists:items,id', 'different:from_item_id'],
            'mode' => ['nullable', 'in:replace,merge'],
            'copy_name' => ['nullable', 'boolean'],
            'activate' => ['nullable', 'boolean'],
        ]);

        $mode = $data['mode'] ?? 'replace';
        $copyName = (bool) ($data['copy_name'] ?? true);
        $activate = (bool) ($data['activate'] ?? true);

        return DB::transaction(function () use ($data, $mode, $copyName, $activate) {
            $fromItem = Item::where('id', $data['from_item_id'])->firstOrFail();
            $toItem = Item::where('id', $data['to_item_id'])->firstOrFail();

            // Optional: pastikan jenisnya benar
            if ($fromItem->type !== 'finished_good' || $toItem->type !== 'finished_good') {
                throw ValidationException::withMessages([
                    'to_item_id' => 'Duplicate BOM hanya untuk SKU finished_good.',
                ]);
            }

            $fromBom = ItemBom::with('lines')->where('item_id', $fromItem->id)->first();
            if (!$fromBom) {
                throw ValidationException::withMessages([
                    'from_item_id' => "BOM sumber tidak ditemukan untuk SKU {$fromItem->code}.",
                ]);
            }

            // create or get BOM tujuan
            $toBom = ItemBom::firstOrCreate(
                ['item_id' => $toItem->id],
                ['name' => 'BOM ' . $toItem->code, 'active' => true]
            );

            if ($activate) {
                $toBom->active = true;
            }

            if ($copyName) {
                // copy name dari source tapi tetap “make sense”
                $toBom->name = $fromBom->name ?: ('BOM ' . $toItem->code);
            }

            $toBom->save();

            $sourceLines = $fromBom->lines->map(fn($l) => [
                'material_item_id' => (int) $l->material_item_id,
                'qty' => (float) $l->qty,
                'uom' => (string) $l->uom,
                'scrap_pct' => (float) $l->scrap_pct,
                'is_optional' => (bool) $l->is_optional,
                'sort_order' => (int) $l->sort_order,
            ])->values()->all();

            if ($mode === 'replace') {
                ItemBomLine::where('item_bom_id', $toBom->id)->delete();
                foreach ($sourceLines as $n) {
                    ItemBomLine::create(['item_bom_id' => $toBom->id] + $n);
                }
            } else { // merge
                $existing = ItemBomLine::where('item_bom_id', $toBom->id)->get()->keyBy('material_item_id');
                foreach ($sourceLines as $n) {
                    if ($existing->has($n['material_item_id'])) {
                        $existing[$n['material_item_id']]->update($n);
                    } else {
                        ItemBomLine::create(['item_bom_id' => $toBom->id] + $n);
                    }
                }
            }

            return redirect()
                ->route('master.item_boms.edit', $toBom)
                ->with('success', "Duplicate BOM berhasil: {$fromItem->code} → {$toItem->code} ({$mode}).");
        });
    }

}
