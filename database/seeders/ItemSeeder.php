<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemCostSnapshot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $data = $this->seedConfig();

            $finishedGoodCategories = ['CRG', 'LJR', 'SJR', 'LCG', 'SHT', 'TJR', 'LBP', 'TTB', 'BRD'];

            // ✅ COA SIMPLE
            $packingExpenseAccountId = $this->lookupAccountIdByCode('6110');

            // =========================================================
            // ✅ Schema guards
            // =========================================================
            $hasAffectsHpp      = Schema::hasColumn('items', 'affects_hpp');
            $hasDefaultAlloc    = Schema::hasColumn('items', 'default_allocation');
            $hasDefaultExpAcc   = Schema::hasColumn('items', 'default_expense_account_id');
            $hasItemRole        = Schema::hasColumn('items', 'item_role');
            $hasIsStocked       = Schema::hasColumn('items', 'is_stocked');
            $hasHppBehavior     = Schema::hasColumn('items', 'hpp_behavior');

            // =========================================================
            // ✅ BACKFILL GLOBAL: PACK category
            // =========================================================
            $packCatId = DB::table('item_categories')->where('code', 'PACK')->value('id');
            if ($packCatId) {
                if ($hasDefaultExpAcc && $packingExpenseAccountId && $hasDefaultAlloc) {
                    DB::table('items')
                        ->where('item_category_id', (int) $packCatId)
                        ->where('default_allocation', 'expense')
                        ->where(function ($q) {
                            $q->whereNull('default_expense_account_id')
                                ->orWhere('default_expense_account_id', 0);
                        })
                        ->update(['default_expense_account_id' => (int) $packingExpenseAccountId, 'updated_at' => now()]);
                }

                DB::table('items')
                    ->where('item_category_id', (int) $packCatId)
                    ->where('active', '!=', 1)
                    ->update(['active' => 1, 'updated_at' => now()]);
            }

            // =========================================================
            // ✅ MAIN SEED
            // =========================================================
            foreach ($data as $catCode => $config) {

                $category = ItemCategory::updateOrCreate(
                    ['code' => $catCode],
                    ['name' => (string) ($config['name'] ?? $catCode), 'active' => 1]
                );

                foreach (($config['items'] ?? []) as $itemDef) {

                    $code         = is_array($itemDef) ? (string) ($itemDef['code'] ?? '') : (string) $itemDef;
                    $unitOverride = is_array($itemDef) ? ($itemDef['unit'] ?? null) : null;
                    $nameOverride = is_array($itemDef) ? ($itemDef['name'] ?? null) : null;
                    $typeOverride = is_array($itemDef) ? ($itemDef['type'] ?? null) : null;

                    if ($code === '') continue;

                    $type = $typeOverride
                        ?? (in_array($catCode, $finishedGoodCategories, true) ? 'finished_good' : 'material');

                    $defaultName = $nameOverride ?: $this->generateName($catCode, $code);
                    $defaultUnit = $unitOverride ?: 'pcs';

                    $affectsHpp  = $this->guessAffectsHpp($catCode, $code, $type);
                    $allocation  = $affectsHpp ? 'hpp' : 'expense';
                    $itemRole    = $this->guessItemRole($catCode, $code, $type);
                    $isStocked   = $this->guessIsStocked($catCode, $code, $type, $affectsHpp, $allocation, $itemRole);
                    $hppBehavior = $this->guessHppBehavior($affectsHpp, $allocation);

                    /** @var Item|null $item */
                    $item = Item::where('code', $code)->first();

                    if (!$item) {
                        $payload = [
                            'code'              => $code,
                            'name'              => $defaultName,
                            'unit'              => $defaultUnit,
                            'type'              => $type,
                            'item_category_id'  => $category->id,
                            'last_purchase_price' => 0,
                            'hpp'               => 0,
                            'active'            => 1,
                        ];

                        if ($hasAffectsHpp)    $payload['affects_hpp']    = $affectsHpp ? 1 : 0;
                        if ($hasDefaultAlloc)  $payload['default_allocation'] = $allocation;
                        if ($hasDefaultExpAcc) {
                            $payload['default_expense_account_id'] = ($catCode === 'PACK' && $allocation === 'expense')
                                ? $packingExpenseAccountId : null;
                        }
                        if ($hasItemRole)    $payload['item_role']    = $itemRole;
                        if ($hasIsStocked)   $payload['is_stocked']   = $isStocked ? 1 : 0;
                        if ($hasHppBehavior) $payload['hpp_behavior'] = $hppBehavior;

                        $item = Item::create($payload);

                    } else {
                        // Update hanya field yang kosong/null (tidak timpa data manual)
                        $dirty = false;

                        if (empty($item->unit))             { $item->unit = $defaultUnit;      $dirty = true; }
                        if (empty($item->type))             { $item->type = $type;             $dirty = true; }
                        if (empty($item->item_category_id)) { $item->item_category_id = $category->id; $dirty = true; }
                        if ($item->last_purchase_price === null) { $item->last_purchase_price = 0; $dirty = true; }
                        if ($item->hpp === null)            { $item->hpp = 0;                  $dirty = true; }
                        if ((int) $item->active !== 1)     { $item->active = 1;               $dirty = true; }

                        if ($hasAffectsHpp) {
                            $attrs = $item->getAttributes();
                            if (array_key_exists('affects_hpp', $attrs) && $item->affects_hpp === null) {
                                $item->affects_hpp = $affectsHpp ? 1 : 0; $dirty = true;
                            }
                        }
                        if ($hasDefaultAlloc) {
                            $attrs = $item->getAttributes();
                            $cur = $attrs['default_allocation'] ?? null;
                            if ($cur === null || $cur === '') { $item->default_allocation = $allocation; $dirty = true; }
                        }
                        if ($hasDefaultExpAcc && $catCode === 'PACK' && $allocation === 'expense' && $packingExpenseAccountId) {
                            $attrs = $item->getAttributes();
                            $cur = $attrs['default_expense_account_id'] ?? null;
                            if ($cur === null || (int) $cur === 0) {
                                $item->default_expense_account_id = (int) $packingExpenseAccountId; $dirty = true;
                            }
                        }
                        if ($hasItemRole) {
                            $attrs = $item->getAttributes();
                            $cur = $attrs['item_role'] ?? null;
                            if ($cur === null || $cur === '') { $item->item_role = $itemRole; $dirty = true; }
                        }
                        if ($hasIsStocked) {
                            $attrs = $item->getAttributes();
                            if (($attrs['is_stocked'] ?? null) === null) { $item->is_stocked = $isStocked ? 1 : 0; $dirty = true; }
                        }
                        if ($hasHppBehavior) {
                            $attrs = $item->getAttributes();
                            $cur = $attrs['hpp_behavior'] ?? null;
                            if ($cur === null || $cur === '') { $item->hpp_behavior = $hppBehavior; $dirty = true; }
                        }

                        if ($dirty) $item->save();
                    }

                    // HPP snapshot untuk Finished Good
                    if ($type === 'finished_good') {
                        $hppGuess = $this->guessHppFromCode($code);

                        if ($hppGuess !== null && ((float) ($item->hpp ?? 0) == 0.0)) {
                            $item->hpp = $hppGuess;
                            $item->save();
                        }

                        if ($hppGuess !== null) {
                            $hasActive = ItemCostSnapshot::where('item_id', $item->id)->where('is_active', 1)->exists();
                            if (!$hasActive) {
                                ItemCostSnapshot::create([
                                    'item_id'              => $item->id,
                                    'warehouse_id'         => null,
                                    'snapshot_date'        => Carbon::today()->toDateString(),
                                    'reference_type'       => 'seed',
                                    'reference_id'         => null,
                                    'qty_basis'            => 1,
                                    'rm_unit_cost'         => 0,
                                    'cutting_unit_cost'    => 0,
                                    'sewing_unit_cost'     => 0,
                                    'finishing_unit_cost'  => 0,
                                    'packaging_unit_cost'  => 0,
                                    'overhead_unit_cost'   => 0,
                                    'unit_cost'            => (int) $hppGuess,
                                    'notes'                => 'Initial HPP seed from ItemSeeder (production-safe)',
                                    'is_active'            => 1,
                                ]);
                            }
                        }
                    }
                }
            }
        });
    }

    // =========================================================
    // seedConfig — source of truth untuk semua item
    // =========================================================
    private function seedConfig(): array
    {
        return [

            // ── Finished Goods ────────────────────────────────────────

            'CRG' => [
                'name'  => 'Jogger Pendek Cargo',
                'items' => ['C5BLK', 'C5MST', 'C5NVY', 'C7BLK', 'C7MST', 'C7NVY'],
            ],

            'LJR' => [
                'name'  => 'Jogger Panjang Basic',
                'items' => [
                    'J3ABT', 'J3BLK', 'J3MST', 'J3NVY',
                    'J5ABT', 'J5BLK', 'J5MST', 'J5NVY',
                    'J7ABT', 'J7BLK', 'J7MST', 'J7NVY',
                ],
            ],

            'SJR' => [
                'name'  => 'Jogger Pendek Basic',
                'items' => [
                    'K1ABT', 'K1BBL', 'K1BLK', 'K1MST', 'K1NVY', 'K1WHT',
                    'K2ABT', 'K2BBL', 'K2BLK', 'K2MST', 'K2NVY', 'K2WHT',
                    'K3ABT', 'K3BBL', 'K3BLK', 'K3MST', 'K3NVY', 'K3WHT',
                    'K5ABT', 'K5BBL', 'K5BLK', 'K5MST', 'K5NVY', 'K5WHT',
                    'K7ABT', 'K7BBL', 'K7BLK', 'K7MST', 'K7NVY', 'K7WHT',
                ],
            ],

            'LCG' => [
                'name'  => 'Jogger Panjang Cargo',
                'items' => ['L1ABT', 'L1BLK', 'L1MST', 'L1NVY', 'L2ABT', 'L2BLK', 'L2MST', 'L2NVY'],
            ],

            'SHT' => [
                'name'  => 'Shot Boxer Brief',
                'items' => [
                    'S2RDM', 'S2RDM-3', 'S2RDM-6',
                    'S3RDM', 'S3RDM-3', 'S3RDM-6',
                    'S4RDM', 'S4RDM-3', 'S4RDM-6',
                    'S5RDM', 'S5RDM-3', 'S5RDM-6',
                ],
            ],

            'TJR' => [
                'name'  => 'Celana Jogger Pendek Bodyfit',
                'items' => ['T1ABT', 'T1BLK', 'T1MST', 'T1NVY', 'T2ABT', 'T2BLK', 'T2MST', 'T2NVY'],
            ],

            'LBP' => [
                'name'  => 'Celana Panjang Baggy Pants',
                'items' => [
                    ['code' => 'BP1ABM', 'name' => 'Celana Panjang Baggy Ukuran L Abu Muda M68'],
                    ['code' => 'BP1ABT', 'name' => 'Celana Panjang Baggy Ukuran L Abu Tua'],
                    ['code' => 'BP1MST', 'name' => 'Celana Panjang Baggy Ukuran L Abu Misty M71'],
                    ['code' => 'BP2ABM', 'name' => 'Celana Panjang Baggy Ukuran XL Abu Muda M68'],
                    ['code' => 'BP2ABT', 'name' => 'Celana Panjang Baggy Ukuran XL Abu Tua'],
                    ['code' => 'BP2BLK', 'name' => 'Celana Panjang Baggy Ukuran XL Hitam'],
                    ['code' => 'BP2MST', 'name' => 'Celana Panjang Baggy Ukuran XL Abu Misty M71'],
                    ['code' => 'LPS-ABT-M', 'name' => 'Celana Panjang Loose Pants Abu Tua Ukuran M'],
                ],
            ],

            'TTB' => [
                'name'  => 'Tracktop',
                'items' => [
                    // Tracktop Garis 3
                    ['code' => 'TTB-BLK-L',   'name' => 'Tracktop Hitam Garis 3 Tangan Ukuiran L'],
                    ['code' => 'TTB-BLK-M',   'name' => 'Tracktop Hitam Garis 3 Tangan Ukuiran M'],
                    ['code' => 'TTB-BLK-XL',  'name' => 'Tracktop Hitam Garis 3 Tangan Ukuiran XL'],
                    ['code' => 'TTB-BLK-XXL', 'name' => 'Tracktop Hitam Garis 3 Tangan Ukuiran XXL'],
                    ['code' => 'TTB-WHT-L',   'name' => 'Tracktop Putih Garis 3 Tangan Ukuiran L'],
                    ['code' => 'TTB-WHT-M',   'name' => 'Tracktop Putih Garis 3 Tangan Ukuiran M'],
                    ['code' => 'TTB-WHT-XL',  'name' => 'Tracktop Putih Garis 3 Tangan Ukuiran XL'],
                    ['code' => 'TTB-WHT-XXL', 'name' => 'Tracktop Putih Garis 3 Tangan Ukuiran XXL'],
                    // Tracktop Strip 2
                    ['code' => 'TTC-BLK-L',   'name' => 'Tracktop Hitam Strip 2 Tangan Ukuiran L'],
                    ['code' => 'TTC-BLK-M',   'name' => 'Tracktop Hitam Strip 2 Tangan Ukuran M'],
                    ['code' => 'TTC-BLK-XL',  'name' => 'Tracktop Hitam Strip 2 Tangan Ukuran XL'],
                    ['code' => 'TTC-BLK-XXL', 'name' => 'Tracktop Hitam Strip 2 Tangan Ukuiran XXL'],
                ],
            ],

            'BRD' => [
                'name'  => 'Celana Boardshort Parasit',
                'items' => [
                    ['code' => 'B3BLK', 'name' => 'Boardshort Parasit Hitam 3L'],
                    ['code' => 'B5BLK', 'name' => 'Boardshort Parasit Hitam 5L'],
                    ['code' => 'B7BLK', 'name' => 'Boardshort Parasit Hitam 7L'],
                ],
            ],

            // ── Material / Bahan Baku ─────────────────────────────────

            'MAT' => [
                'name'  => 'Bahan Baku',
                'items' => [
                    // Fleece 280
                    ['code' => 'FLC280ABT', 'unit' => 'kg'],
                    ['code' => 'FLC280BBL', 'unit' => 'kg'],
                    ['code' => 'FLC280BLK', 'unit' => 'kg'],
                    ['code' => 'FLC280MST', 'unit' => 'kg'],
                    ['code' => 'FLC280NVY', 'unit' => 'kg'],
                    ['code' => 'FLC280WHT', 'unit' => 'kg'],
                    // Fleece 240
                    ['code' => 'FLC240ABM', 'unit' => 'kg', 'name' => 'Fleece 240 Abu Muda M68'],
                    ['code' => 'FLC240ABT', 'unit' => 'kg', 'name' => 'Fleece 240 Abu Tua M68'],
                    ['code' => 'FLC240BLK', 'unit' => 'kg'],
                    ['code' => 'FLC240MST', 'unit' => 'kg'],
                    // Rib 280
                    ['code' => 'RIB280ABT', 'unit' => 'kg'],
                    ['code' => 'RIB280BBL', 'unit' => 'kg'],
                    ['code' => 'RIB280BLK', 'unit' => 'kg'],
                    ['code' => 'RIB280MST', 'unit' => 'kg'],
                    ['code' => 'RIB280NVY', 'unit' => 'kg'],
                    ['code' => 'RIB280WHT', 'unit' => 'kg'],
                    // Anomali: BP1BLK tercatat type=material di DB
                    ['code' => 'BP1BLK', 'name' => 'Celana Panjang Baggy Ukuran L Hitam', 'type' => 'material'],
                ],
            ],

            'BPU' => [
                'name'  => 'Bahan Pendukung',
                'items' => [
                    ['code' => 'TLKADDS', 'name' => 'Tali Karet Adidas'],
                    ['code' => 'KRT4CM',  'name' => 'Karet 4 CM'],
                    ['code' => 'BNGJHT',  'name' => 'Benang Jahit'],
                    ['code' => 'LBLSIZE', 'name' => 'Label Size'],
                ],
            ],

            // ── Packaging & Shipping ──────────────────────────────────

            'PACK' => [
                'name'  => 'Packaging & Shipping',
                'items' => [
                    // Thermal — expense, non-stocked
                    ['code' => 'THR100X150', 'unit' => 'roll', 'name' => 'Thermal 100mm x 150mm'],
                    ['code' => 'THR57X30',   'unit' => 'roll'],
                    ['code' => 'THR57X40',   'unit' => 'roll'],
                    ['code' => 'THR80X50',   'unit' => 'roll'],
                    // OPP — stocked, affects HPP
                    ['code' => 'OPP10X15', 'unit' => 'pack'],
                    ['code' => 'OPP12X20', 'unit' => 'pack'],
                    ['code' => 'OPP15X25', 'unit' => 'pack'],
                    ['code' => 'OPP20X30', 'unit' => 'pack'],
                    // Polymailer — expense, non-stocked
                    ['code' => 'PLY20X30', 'unit' => 'pack'],
                    ['code' => 'PLY25X35', 'unit' => 'pack'],
                    ['code' => 'PLY30X40', 'unit' => 'pack'],
                ],
            ],
        ];
    }

    // =========================================================
    // Helpers
    // =========================================================

    private function lookupAccountIdByCode(string $code): ?int
    {
        if (!Schema::hasTable('accounts')) return null;
        $id = DB::table('accounts')->where('code', $code)->value('id');
        return $id ? (int) $id : null;
    }

    private function guessAffectsHpp(string $catCode, string $code, string $type): bool
    {
        $c = strtoupper($code);
        if ($catCode === 'PACK') {
            if (str_starts_with($c, 'OPP')) return true;
            if (str_starts_with($c, 'THR') || str_starts_with($c, 'PLY')) return false;
        }
        return true;
    }

    private function guessItemRole(string $catCode, string $code, string $type): string
    {
        $c = strtoupper($code);
        if ($type === 'finished_good') return 'finished_good';
        if ($catCode === 'PACK') {
            if (str_starts_with($c, 'OPP')) return 'production_supply';
            if (str_starts_with($c, 'THR') || str_starts_with($c, 'PLY')) return 'shipping_supply';
        }
        if ($catCode === 'BPU') return 'production_supply';
        return 'raw_material';
    }

    private function guessIsStocked(string $catCode, string $code, string $type, bool $affectsHpp, string $allocation, string $itemRole): bool
    {
        $c = strtoupper($code);
        if ($type === 'finished_good') return true;
        if ($catCode === 'PACK') {
            if (str_starts_with($c, 'OPP')) return true;
            if (str_starts_with($c, 'THR') || str_starts_with($c, 'PLY')) return false;
        }
        return !($allocation === 'expense' || !$affectsHpp);
    }

    private function guessHppBehavior(bool $affectsHpp, string $allocation): string
    {
        if ($allocation === 'expense') return 'non_hpp';
        return $affectsHpp ? 'hpp' : 'non_hpp';
    }

    private function generateName(string $catCode, string $code): string
    {
        $colors = [
            'BLK' => 'Hitam', 'MST' => 'Misty (Abu-Abu) M71', 'NVY' => 'Navy',
            'ABT' => 'Abu Tua M81', 'BBL' => 'Baby Blue', 'WHT' => 'Putih', 'RDM' => 'Random',
        ];
        $prefixMap = [
            'CRG' => 'Jogger Pendek Cargo',  'LJR' => 'Jogger Panjang Basic',
            'SJR' => 'Jogger Pendek Basic',  'LCG' => 'Jogger Panjang Cargo',
            'SHT' => 'Shot Boxer Brief',     'TJR' => 'Jogger Pendek Bodyfit',
        ];
        $manual = [
            'TLKADDS' => 'Tali Karet Adidas', 'KRT4CM' => 'Karet 4 CM',
            'BNGJHT'  => 'Benang Jahit',      'LBLSIZE' => 'Label Size',
        ];

        $c = strtoupper($code);
        if (isset($manual[$c])) return $manual[$c];

        if (str_starts_with($c, 'THR') && preg_match('/^THR(\d+)X(\d+)$/', $c, $m))
            return "Kertas Thermal {$m[1]}mm x {$m[2]}mm";
        if (str_starts_with($c, 'OPP') && preg_match('/^OPP(\d+)X(\d+)$/', $c, $m))
            return "Plastik OPP {$m[1]} x {$m[2]}";
        if (str_starts_with($c, 'PLY') && preg_match('/^PLY(\d+)X(\d+)$/', $c, $m))
            return "Polymailer {$m[1]} x {$m[2]}";
        if (str_starts_with($c, 'FLC') && preg_match('/^FLC(\d+)([A-Z]+)$/', $c, $m))
            return 'Fleece ' . $m[1] . ' ' . ($colors[$m[2]] ?? $m[2]);
        if (str_starts_with($c, 'RIB') && preg_match('/^RIB(\d+)([A-Z]+)$/', $c, $m))
            return 'Rib ' . $m[1] . ' ' . ($colors[$m[2]] ?? $m[2]);

        $catName = $prefixMap[$catCode] ?? $catCode;
        preg_match('/^(.*?)([A-Z]{3})(-\d+)?$/', $c, $m);
        $model     = $m[1] ?? $c;
        $clr       = $m[2] ?? '';
        $suffix    = $m[3] ?? '';
        $colorName = $colors[$clr] ?? $clr;

        return trim("{$catName} {$model} {$colorName}{$suffix}");
    }

    private function guessHppFromCode(string $code): ?int
    {
        $c    = strtoupper($code);
        $pre2 = substr($c, 0, 2);
        $base = null;

        if (in_array($pre2, ['K1', 'K2', 'K3', 'K5', 'K7'], true)) $base = 30000;
        if (in_array($pre2, ['J3', 'J5', 'J7'], true))              $base = 45000;
        if (in_array($pre2, ['L1', 'L2'], true))                    $base = 35000;
        if (in_array($pre2, ['C3', 'C5', 'C7'], true))              $base = 35000;
        if (in_array($pre2, ['T1', 'T2'], true))                    $base = 30000;
        if (preg_match('/^S[2-4]RDM/i', $c)) $base = 6700;
        elseif (preg_match('/^S5RDM/i', $c)) $base = 8400;

        if ($base === null) return null;
        if (str_ends_with($c, '-6')) $base *= 6;
        elseif (str_ends_with($c, '-3')) $base *= 3;
        return $base;
    }
}
