<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemBom;
use App\Models\ItemBomLine;
use App\Models\ItemRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ItemBomSeeder extends Seeder
{
    // Kalau mau wajib semua material ada (tidak boleh skip), set true
    private bool $strictMaterial = false;

    public function run(): void
    {
        // =========================
        // 0) Cari role SUP (kalau pakai tabel item_roles)
        // =========================
        $supRoleId = null;
        if (class_exists(ItemRole::class) && Schema::hasTable('item_roles')) {
            $supRoleId = ItemRole::where('code', ItemRole::SUP)->value('id');
        }

        // helper update massal SUP + optional unit
        $makeSup = function (string $codeLike, ?string $unit = null) use ($supRoleId) {
            $q = Item::query()
                ->where('code', 'like', $codeLike)
                ->where('type', 'material');

            $update = [
                'item_role' => 'production_supply',
                'default_allocation' => 'hpp',
                'is_stocked' => 1,
                'affects_hpp' => 1,
                'hpp_behavior' => 'hpp',
            ];

            if ($unit) {
                $update['unit'] = $unit;
            }

            // legacy columns
            $q->update($update);

            // FK role id (kalau ada)
            if ($supRoleId && Schema::hasColumn('items', 'item_role_id')) {
                Item::where('code', 'like', $codeLike)
                    ->where('type', 'material')
                    ->update(['item_role_id' => (int) $supRoleId]);
            }
        };

        // =========================
        // 1) Update master item jadi SUP (ramah produksi)
        // =========================
        $makeSup('RIB%', 'kg');
        $makeSup('KRT%', 'kg');
        $makeSup('TLK%', 'kg');
        $makeSup('OPP%', null);

        // =========================
        // 2) Template BOM per keluarga produk.
        //    SHT sengaja tidak dibuatkan BOM.
        // =========================
        $templates = [
            // Jogger pendek: basic, bodyfit, cargo.
            'SJR' => $this->templateShorts(),
            'TJR' => $this->templateShorts(),
            'CRG' => $this->templateShorts(),

            // Jogger/celana panjang.
            'LJR' => $this->templateLongPants(),
            'LCG' => $this->templateLongPants(),
            'LBP' => $this->templateLongPants(),
        ];

        // =========================
        // 3) Generate BOM untuk semua FG kecuali kategori SHT.
        // =========================
        $fgs = Item::query()
            ->join('item_categories as c', 'c.id', '=', 'items.item_category_id')
            ->where('type', 'finished_good')
            ->where('c.code', '!=', 'SHT')
            ->orderBy('items.code')
            ->get([
                'items.id',
                'items.code',
                'items.name',
                'items.production_source',
                'c.code as category_code',
            ]);

        if ($fgs->isEmpty()) {
            return;
        }

        foreach ($fgs as $fg) {
            $color = $this->extractColor($fg->code); // BLK/NVY/MST/...
            $template = $templates[$fg->category_code] ?? null;
            if (!$template) {
                continue;
            }

            $bom = ItemBom::updateOrCreate(
                ['item_id' => $fg->id],
                ['name' => 'BOM ' . $fg->code, 'active' => true]
            );

            // idempotent: bersihin lines
            ItemBomLine::where('item_bom_id', $bom->id)->delete();

            foreach ($template as $t) {
                $mat = $this->resolveMaterial($t['codes'], $color);

                if (!$mat) {
                    if ($this->strictMaterial) {
                        throw new \RuntimeException("Material not found for FG {$fg->code}");
                    }
                    continue;
                }

                $uom = $t['uom'] ?: ($mat->unit ?: 'pcs');

                ItemBomLine::create([
                    'item_bom_id' => (int) $bom->id,
                    'material_item_id' => (int) $mat->id,
                    'qty' => $t['qty'], // ✅ string raw
                    'uom' => $uom,
                    'scrap_pct' => $t['scrap_pct'] ?? '0', // ✅ string raw
                    'is_optional' => false,
                    'sort_order' => (int) ($t['sort'] ?? 0),
                ]);
            }

            Item::whereKey($fg->id)->update(['production_source' => Item::PRODUCTION_IN_HOUSE]);
        }
    }

    private function templateShorts(): array
    {
        return [
            ['codes' => ['FLC280{COLOR}', 'FLC240{COLOR}'], 'qty' => '0.31', 'uom' => 'kg', 'scrap_pct' => '0', 'sort' => 10],
            ['codes' => ['RIB280{COLOR}', 'RIB280MST'], 'qty' => '0.06', 'uom' => 'kg', 'scrap_pct' => '0', 'sort' => 20],
            ['codes' => ['KRT4CM'], 'qty' => '0.019', 'uom' => 'kg', 'scrap_pct' => '0', 'sort' => 30],
            ['codes' => ['TLKADDS'], 'qty' => '0.009', 'uom' => 'kg', 'scrap_pct' => '0', 'sort' => 40],
        ];
    }

    private function templateLongPants(): array
    {
        return [
            ['codes' => ['FLC280{COLOR}', 'FLC240{COLOR}'], 'qty' => '0.45', 'uom' => 'kg', 'scrap_pct' => '0', 'sort' => 10],
            ['codes' => ['RIB280{COLOR}', 'RIB280MST'], 'qty' => '0.07', 'uom' => 'kg', 'scrap_pct' => '0', 'sort' => 20],
            ['codes' => ['KRT4CM'], 'qty' => '0.022', 'uom' => 'kg', 'scrap_pct' => '0', 'sort' => 30],
            ['codes' => ['TLKADDS'], 'qty' => '0.010', 'uom' => 'kg', 'scrap_pct' => '0', 'sort' => 40],
        ];
    }

    private function resolveMaterial(array $codes, string $color): ?Item
    {
        foreach ($codes as $code) {
            $matCode = str_replace('{COLOR}', $color, $code);
            $mat = Item::query()
                ->where('code', $matCode)
                ->first(['id', 'code', 'unit']);

            if ($mat) {
                return $mat;
            }
        }

        return null;
    }

    /**
     * Ambil warna dari kode FG.
     * Default: 3 huruf terakhir (BLK, NVY, MST, dll).
     */
    private function extractColor(string $fgCode): string
    {
        $fgCode = strtoupper(trim($fgCode));
        $suffix = substr($fgCode, -3);

        if (!preg_match('/^[A-Z]{3}$/', $suffix)) {
            return 'BLK';
        }

        return $suffix;
    }
}
