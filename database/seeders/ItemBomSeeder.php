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
        // 2) Template BOM (RAW seperti DB kamu)
        //    Target: 0.31, 0.06, 0.019, 0.009
        // =========================
        $templateK = [
            ['code' => 'FLC280{COLOR}', 'qty' => '0.31', 'uom' => 'kg', 'scrap_pct' => '0', 'sort' => 10],
            ['code' => 'RIB280{COLOR}', 'qty' => '0.06', 'uom' => 'kg', 'scrap_pct' => '0', 'sort' => 20],
            ['code' => 'KRT4CM', 'qty' => '0.019', 'uom' => 'kg', 'scrap_pct' => '0', 'sort' => 30],
            ['code' => 'TLKADDS', 'qty' => '0.009', 'uom' => 'kg', 'scrap_pct' => '0', 'sort' => 40],
        ];

        // =========================
        // 3) Generate BOM untuk semua FG kode awalan K%
        // =========================
        $fgs = Item::query()
            ->where('type', 'finished_good')
            ->where('code', 'like', 'K%')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        if ($fgs->isEmpty()) {
            return;
        }

        foreach ($fgs as $fg) {
            $color = $this->extractColor($fg->code); // BLK/NVY/MST/...

            $bom = ItemBom::updateOrCreate(
                ['item_id' => $fg->id],
                ['name' => 'BOM ' . $fg->code, 'active' => true]
            );

            // idempotent: bersihin lines
            ItemBomLine::where('item_bom_id', $bom->id)->delete();

            foreach ($templateK as $t) {
                $matCode = str_replace('{COLOR}', $color, $t['code']);

                $mat = Item::query()
                    ->where('code', $matCode)
                    ->first(['id', 'code', 'unit']);

                if (!$mat) {
                    if ($this->strictMaterial) {
                        throw new \RuntimeException("Material not found: {$matCode} for FG {$fg->code}");
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
        }
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
