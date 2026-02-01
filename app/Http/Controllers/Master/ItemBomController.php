<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemBom;
use App\Models\ItemBomLine;
use App\Models\ItemRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ItemBomController extends Controller
{
    private const QTY_MIN = 0.0001; // biar bisa 0,0095
    private const PAGINATE = 20;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $boms = ItemBom::query()
            ->with(['item:id,code,name'])
            ->when($q !== '', function ($qq) use ($q) {
                $qq->whereHas('item', function ($i) use ($q) {
                    $i->where('code', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(self::PAGINATE)
            ->withQueryString();

        return view('master.item_boms.index', compact('boms', 'q'));
    }

    public function create()
    {
        $rows = old('lines') ?: [$this->defaultRow(10)];

        return view('master.item_boms.form', [
            'bom' => null,
            'rows' => $rows,
        ]);
    }

    public function edit(ItemBom $bom)
    {
        $bom->load([
            'item:id,code,name',
            'lines' => fn($q) => $q->orderBy('sort_order')
                ->with('material:id,code,name,unit'),
        ]);

        $rows = old('lines');

        if (!$rows) {
            $rows = $bom->lines->map(function ($l) {
                $text = trim(($l->material?->code ?? '') . ' — ' . ($l->material?->name ?? ''));

                return [
                    'id' => $l->id,
                    'material_item_id' => (int) $l->material_item_id,
                    'material_text' => $text !== '—' ? $text : null,
                    'qty' => (string) $l->qty, // keep as string
                    'uom' => $l->uom ?: ($l->material?->unit ?? 'pcs'),
                    'scrap_pct' => (string) ($l->scrap_pct ?? 0),
                    'is_optional' => (int) ($l->is_optional ? 1 : 0),
                    'sort_order' => (int) ($l->sort_order ?? 0),
                ];
            })->values()->all();

            if (empty($rows)) {
                $rows = [$this->defaultRow(10)];
            }
        }

        return view('master.item_boms.form', compact('bom', 'rows'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request, isUpdate: false);

        return DB::transaction(function () use ($data) {
            $item = Item::whereKey($data['item_id'])->firstOrFail();

            $bom = ItemBom::updateOrCreate(
                ['item_id' => $item->id],
                [
                    'name' => $data['name'] ?: ('BOM ' . $item->code),
                    'active' => (bool) $data['active'],
                ]
            );

            $this->syncLines($bom, $data['lines']);

            return redirect()
                ->route('master.item_boms.edit', $bom)
                ->with('success', 'BOM tersimpan.');
        });
    }

    public function update(Request $request, ItemBom $bom)
    {
        $data = $this->validatePayload($request, isUpdate: true);

        return DB::transaction(function () use ($bom, $data) {
            $bom->update([
                'name' => $data['name'] ?: $bom->name,
                'active' => (bool) $data['active'],
            ]);

            $this->syncLines($bom, $data['lines']);

            return redirect()
                ->route('master.item_boms.edit', $bom)
                ->with('success', 'BOM terupdate.');
        });
    }

    /**
     * =========================
     * VALIDATION + NORMALIZATION
     * =========================
     */
    private function validatePayload(Request $request, bool $isUpdate = false): array
    {
        // Normalisasi qty & scrap_pct dulu (support 0,0095)
        $lines = $request->input('lines', []);
        if (is_array($lines)) {
            foreach ($lines as $i => $l) {
                if (!is_array($l)) {
                    continue;
                }

                if (array_key_exists('qty', $l)) {
                    $lines[$i]['qty'] = $this->normalizeDecimal($l['qty']);
                }
                if (array_key_exists('scrap_pct', $l)) {
                    $lines[$i]['scrap_pct'] = $this->normalizeDecimal($l['scrap_pct']);
                }
            }
            $request->merge(['lines' => $lines]);
        }

        $rules = [
            'item_id' => [$isUpdate ? 'nullable' : 'required', 'integer', 'exists:items,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'in:0,1'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.material_item_id' => ['required', 'integer', 'exists:items,id'],

            // ✅ min kecil biar 0.0095 valid
            // sebelumnya 0.0001
            'lines.*.qty' => ['required', 'numeric', 'min:0.001'],

            'lines.*.uom' => ['nullable', 'string', 'max:20'],
            'lines.*.scrap_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.is_optional' => ['nullable', 'in:0,1'],
            'lines.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];

        $data = $request->validate($rules);
        $data['active'] = $request->boolean('active');

        foreach ($data['lines'] as $idx => $l) {
            $data['lines'][$idx]['uom'] = $l['uom'] ?? 'pcs';

            // keep as string normalized (avoid float)
            $data['lines'][$idx]['qty'] = $this->normalizeDecimal($l['qty']) ?? '0';
            $data['lines'][$idx]['scrap_pct'] = $this->normalizeDecimal($l['scrap_pct'] ?? 0) ?? '0';

            $data['lines'][$idx]['is_optional'] = $request->boolean("lines.$idx.is_optional");
            $data['lines'][$idx]['sort_order'] = $l['sort_order'] ?? ($idx * 10);
        }

        // material tidak boleh duplikat
        $ids = array_map(fn($l) => (int) $l['material_item_id'], $data['lines']);
        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'lines' => 'Material tidak boleh duplikat dalam 1 BOM (gabungkan qty-nya).',
            ]);
        }

        return $data;
    }

    private function normalizeDecimal($v): ?string
    {
        if ($v === null) {
            return null;
        }

        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }

        $v = str_replace(' ', '', $v);

        // format Indonesia: 1.234,56 -> 1234.56
        if (str_contains($v, ',')) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        }

        return $v;
    }

    private function defaultRow(int $sortOrder = 10): array
    {
        return [
            'material_item_id' => null,
            'material_text' => null,
            'qty' => '',
            'uom' => 'pcs',
            'scrap_pct' => 0,
            'is_optional' => 0,
            'sort_order' => $sortOrder,
        ];
    }

    private function syncLines(ItemBom $bom, array $lines): void
    {
        ItemBomLine::where('item_bom_id', $bom->id)->delete();

        foreach ($lines as $l) {
            ItemBomLine::create([
                'item_bom_id' => $bom->id,
                'material_item_id' => (int) $l['material_item_id'],

                // ✅ simpan string, biar decimal DB yang handle presisi
                'qty' => (string) ($this->normalizeDecimal($l['qty']) ?? '0'),
                'uom' => (string) ($l['uom'] ?? 'pcs'),
                'scrap_pct' => (string) ($this->normalizeDecimal($l['scrap_pct'] ?? 0) ?? '0'),

                'is_optional' => (bool) ($l['is_optional'] ?? false),
                'sort_order' => (int) ($l['sort_order'] ?? 0),
            ]);
        }
    }

    /**
     * =========================
     * Select2 AJAX
     * =========================
     */
    public function ajaxItems(Request $request)
    {
        $type = $request->query('type', 'material'); // material / finished_good
        $q = trim((string) $request->query('q', ''));

        $rmId = ItemRole::where('code', ItemRole::RM)->value('id');
        $supId = ItemRole::where('code', ItemRole::SUP)->value('id');
        $pkgId = ItemRole::where('code', ItemRole::PKG)->value('id');

        $roleIds = array_values(array_filter([
            $rmId ? (int) $rmId : null,
            $supId ? (int) $supId : null,
            $pkgId ? (int) $pkgId : null,
        ]));

        $items = Item::query()
            ->when($type === 'finished_good', function ($qq) {
                $qq->where('type', 'finished_good');
            }, function ($qq) use ($roleIds) {
                $qq->where(function ($w) use ($roleIds) {
                    if (!empty($roleIds)) {
                        $w->whereIn('item_role_id', $roleIds);
                    }
                    $w->orWhereIn('item_role', ['raw_material', 'production_supply', 'shipping_supply']);
                });
            })
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('code', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%");
                });
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

    /**
     * =========================
     * IMPORT / TEMPLATE / DUPLICATE
     * =========================
     */
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

        foreach (['sku_code', 'material_code', 'qty'] as $r) {
            if (!in_array($r, $header, true)) {
                return back()->withErrors(['file' => "Kolom wajib tidak ada: {$r}."]);
            }
        }

        $idx = array_flip($header);
        $rowsBySku = [];

        while (($row = fgetcsv($fh)) !== false) {
            $skuCode = strtoupper(trim((string) ($row[$idx['sku_code']] ?? '')));
            $matCode = strtoupper(trim((string) ($row[$idx['material_code']] ?? '')));
            $qtyRaw = (string) ($row[$idx['qty']] ?? '');

            $qtyNorm = $this->normalizeDecimal($qtyRaw);
            if ($skuCode === '' || $matCode === '' || !$qtyNorm) {
                continue;
            }

            // numeric check + min
            if (!is_numeric($qtyNorm) || (float) $qtyNorm < self::QTY_MIN) {
                continue;
            }

            $uom = isset($idx['uom']) ? trim((string) $row[$idx['uom']]) : 'pcs';
            $scrapRaw = isset($idx['scrap_pct']) ? (string) $row[$idx['scrap_pct']] : '0';
            $scrapNorm = $this->normalizeDecimal($scrapRaw) ?? '0';

            $opt = isset($idx['is_optional']) ? (int) $row[$idx['is_optional']] : 0;
            $sort = isset($idx['sort_order']) ? (int) $row[$idx['sort_order']] : 0;

            $rowsBySku[$skuCode][] = [
                'material_code' => $matCode,
                'qty' => $qtyNorm, // ✅ string
                'uom' => $uom ?: 'pcs',
                'scrap_pct' => $scrapNorm, // ✅ string
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
                        'material_item_id' => (int) $mat->id,
                        'qty' => (string) $l['qty'],
                        'uom' => (string) ($l['uom'] ?? 'pcs'),
                        'scrap_pct' => (string) ($l['scrap_pct'] ?? '0'),
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
                } else {
                    $existing = ItemBomLine::where('item_bom_id', $bom->id)->get()->keyBy('material_item_id');
                    foreach ($normalized as $n) {
                        if ($existing->has($n['material_item_id'])) {
                            $existing[$n['material_item_id']]->update($n);
                        } else {
                            ItemBomLine::create(['item_bom_id' => $bom->id] + $n);
                        }
                    }
                }

                $isNew ? $created++ : $updated++;
            }
        });

        return redirect()->route('master.item_boms.index')
            ->with('success', "Import selesai. Created: {$created}, Updated: {$updated}, Skipped: " . count($skipped))
            ->with('skipped', $skipped);
    }

    public function downloadTemplate()
    {
        $header = "sku_code,material_code,qty,uom,scrap_pct,is_optional,sort_order\n";
        $sample = [
            "C5BLK,FLC280BLK,1.2000,pcs,2,0,10",
            "C5BLK,RIB280BLK,0.2500,pcs,2,0,20",
            "C5BLK,TLKADDS,0.0095,pcs,0,0,30",
            "C5BLK,KRT4CM,1.0000,pcs,0,0,40",
            "C5BLK,BNGJHT,0.1000,pcs,0,0,50",
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
            $fromItem = Item::whereKey($data['from_item_id'])->firstOrFail();
            $toItem = Item::whereKey($data['to_item_id'])->firstOrFail();

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

            $toBom = ItemBom::firstOrCreate(
                ['item_id' => $toItem->id],
                ['name' => 'BOM ' . $toItem->code, 'active' => true]
            );

            if ($activate) {
                $toBom->active = true;
            }

            if ($copyName) {
                $toBom->name = $fromBom->name ?: ('BOM ' . $toItem->code);
            }

            $toBom->save();

            $sourceLines = $fromBom->lines->map(fn($l) => [
                'material_item_id' => (int) $l->material_item_id,
                'qty' => (string) $l->qty, // ✅ string
                'uom' => (string) $l->uom,
                'scrap_pct' => (string) $l->scrap_pct, // ✅ string
                'is_optional' => (bool) $l->is_optional,
                'sort_order' => (int) $l->sort_order,
            ])->values()->all();

            if ($mode === 'replace') {
                ItemBomLine::where('item_bom_id', $toBom->id)->delete();
                foreach ($sourceLines as $n) {
                    ItemBomLine::create(['item_bom_id' => $toBom->id] + $n);
                }
            } else {
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
