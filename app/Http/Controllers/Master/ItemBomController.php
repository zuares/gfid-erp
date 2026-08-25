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
    private const QTY_MIN = 0.0001; // biar bisa 0,0095
    private const PAGINATE = 20;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $boms = ItemBom::query()
            ->with(['item:id,code,name'])
            ->withCount([
                'lines',
                'mainMaterialLines',
                'sewingSupplyLines',
                'packingSupplyLines',
            ])
            ->when($q !== '', function ($qq) use ($q) {
                $qq->whereHas('item', function ($i) use ($q) {
                    $i->where('code', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(self::PAGINATE)
            ->withQueryString();

        // Badge "standar vs aktual" per BOM — dari cutting job non-void terakhir.
        // { bom_id => { kg_per_pcs, job_code, std_max, status: over|under|ok } }
        $bomUsageBadges = [];
        $itemIds = $boms->pluck('item_id')->filter()->all();

        if (!empty($itemIds)) {
            $rows = DB::table('cutting_job_bundles as b')
                ->join('cutting_jobs as j', 'j.id', '=', 'b.cutting_job_id')
                ->join('lots as l', 'l.id', '=', 'b.lot_id')
                ->where('j.status', '!=', 'voided')
                ->whereIn('b.finished_item_id', $itemIds)
                ->where('b.qty_pcs', '>', 0)
                ->where('b.qty_used_fabric', '>', 0)
                ->orderByDesc('j.date')
                ->orderByDesc('b.id')
                ->get(['b.finished_item_id', 'l.item_id as material_item_id', 'b.qty_pcs', 'b.qty_used_fabric', 'j.code as job_code']);

            $latest = [];
            foreach ($rows as $r) {
                $fid = (int) $r->finished_item_id;
                if (!isset($latest[$fid])) {
                    $latest[$fid] = $r;
                }
            }

            $stdLines = ItemBomLine::query()
                ->whereIn('item_bom_id', $boms->pluck('id')->all())
                ->where('usage_stage', ItemBomLine::STAGE_MAIN_MATERIAL)
                ->get(['item_bom_id', 'material_item_id', 'qty', 'scrap_pct']);

            $stdMap = [];
            foreach ($stdLines as $l) {
                $stdMap[(int) $l->item_bom_id][(int) $l->material_item_id] = $l;
            }

            foreach ($boms as $b) {
                $r = $latest[(int) $b->item_id] ?? null;
                if (!$r) {
                    continue;
                }
                $actual = round((float) $r->qty_used_fabric / max((float) $r->qty_pcs, 0.0001), 4);
                $std = $stdMap[(int) $b->id][(int) $r->material_item_id] ?? null;
                $stdMax = $std ? (float) $std->qty * (1 + (float) $std->scrap_pct / 100) : null;

                $status = null;
                if ($stdMax !== null) {
                    $status = $actual > $stdMax + 0.0005 ? 'over'
                        : ($actual < $stdMax - 0.0005 ? 'under' : 'ok');
                }

                $bomUsageBadges[(int) $b->id] = [
                    'kg_per_pcs' => $actual,
                    'job_code'   => $r->job_code,
                    'std_max'    => $stdMax,
                    'status'     => $status,
                ];
            }
        }

        return view('master.item_boms.index', compact('boms', 'q', 'bomUsageBadges'));
    }

    public function create(Request $request)
    {
        $rows = old('lines') ?: [$this->defaultRow(10)];
        $prefilledItem = null;

        if ($request->filled('item_id')) {
            $prefilledItem = Item::query()
                ->whereKey((int) $request->input('item_id'))
                ->whereIn('type', ['finished_good', 'wip'])
                ->first(['id', 'code', 'name', 'type']);
        }

        return view('master.item_boms.form', [
            'bom' => null,
            'rows' => $rows,
            'prefilledItem' => $prefilledItem,
        ]);
    }

    public function edit(ItemBom $bom)
    {
        $bom->load([
            'item:id,code,name',
            'lines' => fn($q) => $q->orderBy('sort_order')
                ->with('material:id,code,name,unit'),
        ]);

        // Pemakaian kain AKTUAL terakhir per material (dari cutting job non-void)
        // untuk item BOM ini. Dipakai form sebagai pembanding standar vs realita.
        // Format: { material_item_id => { kg_per_pcs, job_code, date, history: [..3] } }
        $usageByMaterial = [];
        $usageRows = DB::table('cutting_job_bundles as b')
            ->join('cutting_jobs as j', 'j.id', '=', 'b.cutting_job_id')
            ->join('lots as l', 'l.id', '=', 'b.lot_id')
            ->join('items as mi', 'mi.id', '=', 'l.item_id')
            ->where('j.status', '!=', 'voided')
            ->where('b.finished_item_id', $bom->item_id)
            ->where('b.qty_pcs', '>', 0)
            ->where('b.qty_used_fabric', '>', 0)
            ->orderByDesc('j.date')
            ->orderByDesc('b.id')
            ->limit(60)
            ->get([
                'l.item_id as material_item_id',
                'mi.code as material_code', 'mi.name as material_name',
                'b.qty_pcs', 'b.qty_used_fabric', 'j.code as job_code', 'j.date',
            ]);

        foreach ($usageRows as $row) {
            $mid = (int) $row->material_item_id;
            $kgPerPcs = round((float) $row->qty_used_fabric / max((float) $row->qty_pcs, 0.0001), 4);
            $dateStr = \Illuminate\Support\Carbon::parse($row->date)->format('d/m/y');

            if (!isset($usageByMaterial[$mid])) {
                $usageByMaterial[$mid] = [
                    'material_code' => $row->material_code,
                    'material_name' => $row->material_name,
                    'kg_per_pcs' => $kgPerPcs,
                    'job_code'   => $row->job_code,
                    'date'       => $dateStr,
                    'history'    => [],
                ];
            }

            $hist = &$usageByMaterial[$mid]['history'];
            $lastJob = end($hist);
            if (count($hist) < 3 && (!$lastJob || $lastJob['job_code'] !== $row->job_code)) {
                $hist[] = ['kg_per_pcs' => $kgPerPcs, 'job_code' => $row->job_code, 'date' => $dateStr];
            }
            unset($hist);
        }

        $rows = old('lines');

        if (!$rows) {
            $rows = $bom->lines->map(function ($l) {
                $text = trim(($l->material?->code ?? '') . ' — ' . ($l->material?->name ?? ''));

                return [
                    'id' => $l->id,
                    'material_item_id' => (int) $l->material_item_id,
                    'material_text' => $text !== '—' ? $text : null,
                    'usage_stage' => $l->usage_stage ?: ItemBomLine::STAGE_MAIN_MATERIAL,
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

        return view('master.item_boms.form', compact('bom', 'rows', 'usageByMaterial'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request, isUpdate: false);

        return DB::transaction(function () use ($data) {
            $item = Item::whereKey($data['item_id'])->firstOrFail();

            if (!in_array($item->type, ['finished_good', 'wip'], true) || !$item->canMake()) {
                throw ValidationException::withMessages([
                    'item_id' => 'Item ini belum disiapkan untuk produksi sendiri. Aktifkan kemampuan produksi pada Master Item terlebih dahulu.',
                ]);
            }

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
            $item = $bom->item;
            if (!$item || !in_array($item->type, ['finished_good', 'wip'], true) || !$item->canMake()) {
                throw ValidationException::withMessages([
                    'item_id' => 'Item BOM ini tidak lagi disiapkan untuk produksi sendiri. Periksa Metode Pasok pada Master Item.',
                ]);
            }

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

    public function destroy(ItemBom $bom)
    {
        return DB::transaction(function () use ($bom) {
            $bom->lines()->delete();
            $bom->delete();

            return redirect()
                ->route('master.item_boms.index')
                ->with('success', 'BOM berhasil dihapus.');
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
            'lines.*.usage_stage' => ['nullable', 'in:' . implode(',', array_keys(ItemBomLine::usageStageLabels()))],

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
            $data['lines'][$idx]['usage_stage'] = $l['usage_stage'] ?? ItemBomLine::STAGE_MAIN_MATERIAL;

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
            'usage_stage' => ItemBomLine::STAGE_MAIN_MATERIAL,
            'qty' => '',
            'uom' => 'pcs',
            'scrap_pct' => 0,
            'is_optional' => 0,
            'sort_order' => $sortOrder,
        ];
    }

    private function syncLines(ItemBom $bom, array $lines): void
    {
        $this->validateBomComponents($lines);
        ItemBomLine::where('item_bom_id', $bom->id)->delete();

        foreach ($lines as $l) {
            ItemBomLine::create([
                'item_bom_id' => $bom->id,
                'material_item_id' => (int) $l['material_item_id'],
                'usage_stage' => (string) ($l['usage_stage'] ?? ItemBomLine::STAGE_MAIN_MATERIAL),

                // ✅ simpan string, biar decimal DB yang handle presisi
                'qty' => (string) ($this->normalizeDecimal($l['qty']) ?? '0'),
                'uom' => (string) ($l['uom'] ?? 'pcs'),
                'scrap_pct' => (string) ($this->normalizeDecimal($l['scrap_pct'] ?? 0) ?? '0'),

                'is_optional' => (bool) ($l['is_optional'] ?? false),
                'sort_order' => (int) ($l['sort_order'] ?? 0),
            ]);
        }
    }

    private function validateBomComponents(array $lines): void
    {
        $ids = collect($lines)
            ->pluck('material_item_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $eligibleCount = Item::query()
            ->whereIn('id', $ids)
            ->where('type', 'material')
            ->whereDoesntHave('category', fn ($q) => $q->where('kind', 'operational'))
            ->where(function ($q) {
                $q->whereNull('default_allocation')
                    ->orWhere('default_allocation', 'hpp');
            })
            ->count();

        if ($eligibleCount !== $ids->count()) {
            throw ValidationException::withMessages([
                'lines' => 'Komponen BOM harus berupa Material yang masuk persediaan/HPP. Accessories boleh dipilih, sedangkan ATK dan Maintenance Mesin tidak boleh masuk BOM.',
            ]);
        }
    }

    private function inferUsageStageFromCode(string $code): string
    {
        $code = strtoupper(trim($code));

        if (str_starts_with($code, 'RIB') || str_starts_with($code, 'KRT')) {
            return ItemBomLine::STAGE_SEWING_SUPPLY;
        }

        if (str_starts_with($code, 'TLK') || str_starts_with($code, 'OPP') || str_starts_with($code, 'PACK')) {
            return ItemBomLine::STAGE_PACKING_SUPPLY;
        }

        return ItemBomLine::STAGE_MAIN_MATERIAL;
    }

    private function normalizeUsageStage(string $value): ?string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return null;
        }

        $value = str_replace([' ', '-'], '_', $value);

        return match ($value) {
            'main_material', 'bahan_baku', 'bahan_baku_utama', 'utama', 'rm' => ItemBomLine::STAGE_MAIN_MATERIAL,
            'sewing_supply', 'kelengkapan_jahit', 'jahit', 'sewing' => ItemBomLine::STAGE_SEWING_SUPPLY,
            'packing_supply', 'kelengkapan_packing', 'packing', 'pack' => ItemBomLine::STAGE_PACKING_SUPPLY,
            default => null,
        };
    }

    /**
     * Quick-update satu line BOM (qty saja) via AJAX dari form cutting job.
     * PATCH /master/item-boms/{bom}/quick-line
     */
    public function quickUpdateLine(Request $request, ItemBom $bom)
    {
        $data = $request->validate([
            'material_item_id' => ['required', 'integer', 'exists:items,id'],
            'qty'              => ['nullable', 'numeric', 'min:0.0001'],
            'scrap_pct'        => ['nullable', 'numeric', 'min:0', 'max:99'],
        ]);

        if (!isset($data['qty']) && !isset($data['scrap_pct'])) {
            throw ValidationException::withMessages([
                'qty' => 'Minimal salah satu dari qty / scrap_pct harus diisi.',
            ]);
        }

        $line = ItemBomLine::where('item_bom_id', $bom->id)
            ->where('material_item_id', $data['material_item_id'])
            ->where('usage_stage', ItemBomLine::STAGE_MAIN_MATERIAL)
            ->firstOrFail();

        $updates = [];
        if (isset($data['qty'])) {
            $updates['qty'] = (string) round((float) $data['qty'], 8);
        }
        if (isset($data['scrap_pct'])) {
            $updates['scrap_pct'] = (string) round((float) $data['scrap_pct'], 2);
        }
        $line->update($updates);

        return response()->json([
            'success'   => true,
            'new_qty'   => (float) $line->qty,
            'new_scrap' => (float) $line->scrap_pct,
            'message'   => 'BOM berhasil diperbarui.',
        ]);
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

        $items = Item::query()
            ->when($type === 'finished_good', function ($qq) {
                $qq->where('type', 'finished_good')->canBeMade();
            }, function ($qq) {
                // Bahan baku, bahan pendukung, accessories, dan packaging
                // boleh menjadi komponen selama perlakuannya persediaan/HPP.
                $qq->where('type', 'material')
                    ->whereDoesntHave('category', fn ($w) => $w->where('kind', 'operational'))
                    ->where(function ($w) {
                        $w->whereNull('default_allocation')
                            ->orWhere('default_allocation', 'hpp');
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
            $usageStage = isset($idx['usage_stage'])
                ? $this->normalizeUsageStage((string) $row[$idx['usage_stage']])
                : null;
            $scrapRaw = isset($idx['scrap_pct']) ? (string) $row[$idx['scrap_pct']] : '0';
            $scrapNorm = $this->normalizeDecimal($scrapRaw) ?? '0';

            $opt = isset($idx['is_optional']) ? (int) $row[$idx['is_optional']] : 0;
            $sort = isset($idx['sort_order']) ? (int) $row[$idx['sort_order']] : 0;

            $rowsBySku[$skuCode][] = [
                'material_code' => $matCode,
                'qty' => $qtyNorm, // ✅ string
                'usage_stage' => $usageStage,
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
                $sku = Item::where('code', $skuCode)
                    ->where('type', 'finished_good')
                    ->canBeMade()
                    ->first();
                if (!$sku) {$skipped[] = "SKU not found / tidak bisa diproduksi: {$skuCode}";continue;}

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
                        'usage_stage' => $l['usage_stage'] ?: $this->inferUsageStageFromCode($code),
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
        $header = "sku_code,material_code,usage_stage,qty,uom,scrap_pct,is_optional,sort_order\n";
        $sample = [
            "C5BLK,FLC280BLK,main_material,1.2000,kg,2,0,10",
            "C5BLK,RIB280BLK,sewing_supply,0.2500,kg,2,0,20",
            "C5BLK,KRT4CM,sewing_supply,1.0000,pcs,0,0,30",
            "C5BLK,TLKADDS,packing_supply,0.0095,kg,0,0,40",
            "C5BLK,OPP001,packing_supply,1.0000,pcs,0,0,50",
        ];

        $csv = $header . implode("\n", $sample) . "\n";
        $filename = 'item_bom_template_' . now()->format('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function duplicateForm(Request $request)
    {
        $sourceItem = null;

        if ($request->filled('from_item_id')) {
            $sourceItem = Item::query()
                ->whereKey((int) $request->input('from_item_id'))
                ->where('type', 'finished_good')
                ->first(['id', 'code', 'name']);
        }

        return view('master.item_boms.duplicate', compact('sourceItem'));
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
                'usage_stage' => (string) ($l->usage_stage ?: ItemBomLine::STAGE_MAIN_MATERIAL),
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
