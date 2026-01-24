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

            $finishedGoodCategories = ['CRG', 'LJR', 'SJR', 'LCG', 'SHT', 'TJR'];

            // ✅ COA SIMPLE
            // 6110 = Biaya Packing
            $packingExpenseAccountId = $this->lookupAccountIdByCode('6110'); // nullable kalau accounting belum ada

            // schema guards (biar aman lintas branch/env)
            $hasAffectsHpp = Schema::hasColumn('items', 'affects_hpp');
            $hasDefaultAllocation = Schema::hasColumn('items', 'default_allocation');
            $hasDefaultExpenseAcc = Schema::hasColumn('items', 'default_expense_account_id');

            // =========================================================
            // ✅ BACKFILL GLOBAL (penting untuk data yang sudah terlanjur ada)
            // - isi default_expense_account_id untuk PACK expense yang masih NULL/0
            // - set active=1 untuk semua item PACK (THR/PLY) yang active masih 0
            // =========================================================
            if ($hasDefaultExpenseAcc && $packingExpenseAccountId) {
                $packCatId = DB::table('item_categories')->where('code', 'PACK')->value('id');

                if ($packCatId) {
                    // backfill akun expense untuk PACK expense
                    DB::table('items')
                        ->where('item_category_id', (int) $packCatId)
                        ->where('default_allocation', 'expense')
                        ->where(function ($q) {
                            $q->whereNull('default_expense_account_id')
                                ->orWhere('default_expense_account_id', 0);
                        })
                        ->update([
                            'default_expense_account_id' => (int) $packingExpenseAccountId,
                            'updated_at' => now(),
                        ]);

                    // set active=1 untuk semua item PACK yang masih 0
                    DB::table('items')
                        ->where('item_category_id', (int) $packCatId)
                        ->where('active', '!=', 1)
                        ->update([
                            'active' => 1,
                            'updated_at' => now(),
                        ]);
                }
            }

            foreach ($data as $catCode => $config) {
                // Category: update/create (aman)
                $category = ItemCategory::updateOrCreate(
                    ['code' => $catCode],
                    [
                        'name' => (string) ($config['name'] ?? $catCode),
                        'active' => 1,
                    ]
                );

                foreach (($config['items'] ?? []) as $itemDef) {
                    $code = is_array($itemDef) ? (string) ($itemDef['code'] ?? '') : (string) $itemDef;
                    if ($code === '') {
                        continue;
                    }

                    $unitOverride = is_array($itemDef) ? ($itemDef['unit'] ?? null) : null;

                    $type = in_array($catCode, $finishedGoodCategories, true)
                    ? 'finished_good'
                    : 'material';

                    $defaultName = $this->generateName($catCode, $code);
                    $defaultUnit = $unitOverride ?: 'pcs';

                    // affects_hpp + allocation
                    $affectsHpp = $this->guessAffectsHpp($catCode, $code, $type);
                    $allocation = $affectsHpp ? 'hpp' : 'expense';

                    /** @var Item|null $item */
                    $item = Item::where('code', $code)->first();

                    if (!$item) {
                        // ✅ CREATE (wajib isi NOT NULL)
                        $payload = [
                            'code' => $code,
                            'name' => $defaultName,
                            'unit' => $defaultUnit,
                            'type' => $type,
                            'item_category_id' => $category->id,
                            'last_purchase_price' => 0,
                            'hpp' => 0,
                            'active' => 1, // ✅ selalu aktif
                        ];

                        if ($hasAffectsHpp) {
                            $payload['affects_hpp'] = $affectsHpp ? 1 : 0;
                        }

                        if ($hasDefaultAllocation) {
                            $payload['default_allocation'] = $allocation;
                        }

                        // ✅ AUTO ASSIGN PACK expense → 6110
                        if ($hasDefaultExpenseAcc) {
                            if ($catCode === 'PACK' && $allocation === 'expense') {
                                $payload['default_expense_account_id'] = $packingExpenseAccountId;
                            } else {
                                $payload['default_expense_account_id'] = null;
                            }
                        }

                        $item = Item::create($payload);
                    } else {
                        // ✅ UPDATE (production-safe)
                        $dirty = false;

                        if (empty($item->name)) {
                            $item->name = $defaultName;
                            $dirty = true;
                        }

                        if (empty($item->unit)) {
                            $item->unit = $defaultUnit;
                            $dirty = true;
                        }

                        if (empty($item->type)) {
                            $item->type = $type;
                            $dirty = true;
                        }

                        if (empty($item->item_category_id)) {
                            $item->item_category_id = $category->id;
                            $dirty = true;
                        }

                        if ($item->last_purchase_price === null) {
                            $item->last_purchase_price = 0;
                            $dirty = true;
                        }

                        if ($item->hpp === null) {
                            $item->hpp = 0;
                            $dirty = true;
                        }

                        // ✅ kamu minta: THR/PLY active harus 1 → force semua item yang ada di seedConfig jadi active=1
                        if ((int) $item->active !== 1) {
                            $item->active = 1;
                            $dirty = true;
                        }

                        // affects_hpp set hanya kalau null
                        if ($hasAffectsHpp) {
                            $attrs = $item->getAttributes();
                            if (array_key_exists('affects_hpp', $attrs) && $item->affects_hpp === null) {
                                $item->affects_hpp = $affectsHpp ? 1 : 0;
                                $dirty = true;
                            }
                        }

                        // default_allocation set hanya kalau null/kosong
                        if ($hasDefaultAllocation) {
                            $attrs = $item->getAttributes();
                            $cur = $attrs['default_allocation'] ?? null;
                            if ($cur === null || $cur === '') {
                                $item->default_allocation = $allocation;
                                $dirty = true;
                            }
                        }

                        // ✅ AUTO ASSIGN + BACKFILL: PACK expense → default_expense_account_id = 6110
                        // set jika masih null/0 dan akun 6110 ada
                        if ($hasDefaultExpenseAcc && $catCode === 'PACK' && $allocation === 'expense' && $packingExpenseAccountId) {
                            $attrs = $item->getAttributes();
                            $cur = $attrs['default_expense_account_id'] ?? null;

                            if ($cur === null || (int) $cur === 0 || (string) $cur === '') {
                                $item->default_expense_account_id = (int) $packingExpenseAccountId;
                                $dirty = true;
                            }
                        }

                        if ($dirty) {
                            $item->save();
                        }
                    }

                    // === HPP untuk Finished Good (opsional seperti seeder kamu) ===
                    if ($type === 'finished_good') {
                        $hppGuess = $this->guessHppFromCode($code);

                        if ($hppGuess !== null && ((float) ($item->hpp ?? 0) == 0.0)) {
                            $item->hpp = $hppGuess;
                            $item->save();
                        }

                        if ($hppGuess !== null) {
                            $hasActiveSnapshot = ItemCostSnapshot::where('item_id', $item->id)
                                ->where('is_active', 1)
                                ->exists();

                            if (!$hasActiveSnapshot) {
                                ItemCostSnapshot::create([
                                    'item_id' => $item->id,
                                    'warehouse_id' => null,
                                    'snapshot_date' => Carbon::today()->toDateString(),
                                    'reference_type' => 'seed',
                                    'reference_id' => null,
                                    'qty_basis' => 1,
                                    'rm_unit_cost' => 0,
                                    'cutting_unit_cost' => 0,
                                    'sewing_unit_cost' => 0,
                                    'finishing_unit_cost' => 0,
                                    'packaging_unit_cost' => 0,
                                    'overhead_unit_cost' => 0,
                                    'unit_cost' => (int) $hppGuess,
                                    'notes' => 'Initial HPP seed from ItemSeeder (production-safe)',
                                    'is_active' => 1,
                                ]);
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Lookup account id by code (return null if not found / table not exists)
     */
    private function lookupAccountIdByCode(string $code): ?int
    {
        if (!Schema::hasTable('accounts')) {
            return null;
        }

        $id = DB::table('accounts')->where('code', $code)->value('id');
        return $id ? (int) $id : null;
    }

    /**
     * Config seed: tambah PACK (thermal, OPP, polymailer) + ukuran
     */
    private function seedConfig(): array
    {
        return [
            'CRG' => [
                'name' => 'Jogger Pendek Cargo',
                'items' => ['C5BLK', 'C5MST', 'C5NVY', 'C7BLK', 'C7MST', 'C7NVY'],
            ],
            'LJR' => [
                'name' => 'Jogger Panjang Basic',
                'items' => ['J3ABT', 'J3BLK', 'J3MST', 'J3NVY', 'J5ABT', 'J5BLK', 'J5MST', 'J5NVY', 'J7ABT', 'J7BLK', 'J7MST', 'J7NVY'],
            ],
            'SJR' => [
                'name' => 'Jogger Pendek Basic',
                'items' => [
                    'K1ABT', 'K1BLK', 'K1MST', 'K1NVY', 'K1WHT',
                    'K2ABT', 'K2BLK', 'K2MST', 'K2NVY',
                    'K3ABT', 'K3BBL', 'K3BLK', 'K3MST', 'K3NVY', 'K3WHT',
                    'K5ABT', 'K5BBL', 'K5BLK', 'K5MST', 'K5NVY', 'K5WHT',
                    'K7ABT', 'K7BBL', 'K7BLK', 'K7MST', 'K7NVY', 'K7WHT',
                    'K1BBL', 'K2BBL', 'K2WHT',
                ],
            ],
            'LCG' => [
                'name' => 'Jogger Panjang Cargo',
                'items' => ['L1BLK', 'L1MST', 'L1NVY', 'L2BLK', 'L2MST', 'L2NVY', 'L1ABT', 'L2ABT'],
            ],
            'SHT' => [
                'name' => 'Shot Boxer Brief',
                'items' => ['S2RDM', 'S2RDM-3', 'S2RDM-6', 'S3RDM', 'S3RDM-3', 'S3RDM-6', 'S4RDM', 'S4RDM-3', 'S4RDM-6', 'S5RDM', 'S5RDM-3', 'S5RDM-6'],
            ],
            'TJR' => [
                'name' => 'Celana Jogger Pendek Bodyfit',
                'items' => ['T1ABT', 'T1BLK', 'T1MST', 'T1NVY', 'T2ABT', 'T2BLK', 'T2MST', 'T2NVY'],
            ],
            'MAT' => [
                'name' => 'Bahan Baku',
                'items' => [
                    'FLC280BLK', 'FLC280NVY', 'FLC280MST', 'FLC280ABT', 'FLC280WHT', 'FLC280BBL',
                    'RIB280BLK', 'RIB280NVY', 'RIB280MST', 'RIB280ABT', 'RIB280WHT', 'RIB280BBL',
                ],
            ],
            'BPU' => [
                'name' => 'Bahan Pendukung',
                'items' => ['TLKADDS', 'KRT4CM', 'BNGJHT'],
            ],

            // ✅ Packaging & Shipping
            'PACK' => [
                'name' => 'Packaging & Shipping',
                'items' => [
                    // Thermal (mm)
                    ['code' => 'THR57X30', 'unit' => 'roll'],
                    ['code' => 'THR57X40', 'unit' => 'roll'],
                    ['code' => 'THR80X50', 'unit' => 'roll'],

                    // OPP (cm)
                    ['code' => 'OPP10X15', 'unit' => 'pack'],
                    ['code' => 'OPP12X20', 'unit' => 'pack'],
                    ['code' => 'OPP15X25', 'unit' => 'pack'],
                    ['code' => 'OPP20X30', 'unit' => 'pack'],

                    // Polymailer (cm)
                    ['code' => 'PLY20X30', 'unit' => 'pack'],
                    ['code' => 'PLY25X35', 'unit' => 'pack'],
                    ['code' => 'PLY30X40', 'unit' => 'pack'],
                ],
            ],
        ];
    }

    private function guessAffectsHpp(string $catCode, string $code, string $type): bool
    {
        $c = strtoupper($code);

        // PACK rule:
        // - OPP masuk HPP (nempel produk)
        // - THR & PLY expense (operasional packing)
        if ($catCode === 'PACK') {
            if (str_starts_with($c, 'OPP')) {
                return true;
            }

            if (str_starts_with($c, 'THR') || str_starts_with($c, 'PLY')) {
                return false;
            }
        }

        return true;
    }

    private function generateName(string $catCode, string $code): string
    {
        $colors = [
            'BLK' => 'Hitam',
            'MST' => 'Misty (Abu-Abu) M71',
            'NVY' => 'Navy',
            'ABT' => 'Abu Tua M81',
            'BBL' => 'Baby Blue',
            'WHT' => 'Putih',
            'RDM' => 'Random',
        ];

        $prefix = [
            'CRG' => 'Jogger Pendek Cargo',
            'LJR' => 'Jogger Panjang Basic',
            'SJR' => 'Jogger Pendek Basic',
            'LCG' => 'Jogger Panjang Cargo',
            'SHT' => 'Shot Boxer Brief',
            'TJR' => 'Jogger Pendek Bodyfit',
        ];

        $manual = [
            'TLKADDS' => 'Tali Karet Adidas',
            'KRT4CM' => 'Karet 4 CM',
            'BNGJHT' => 'Benang Jahit',
        ];

        $c = strtoupper($code);

        if (isset($manual[$c])) {
            return $manual[$c];
        }

        // PACK
        if (str_starts_with($c, 'THR')) {
            if (preg_match('/^THR(\d+)X(\d+)$/', $c, $m)) {
                return "Kertas Thermal {$m[1]}mm x {$m[2]}mm";
            }
            return "Kertas Thermal";
        }

        if (str_starts_with($c, 'OPP')) {
            if (preg_match('/^OPP(\d+)X(\d+)$/', $c, $m)) {
                return "Plastik OPP {$m[1]} x {$m[2]}";
            }
            return "Plastik OPP";
        }

        if (str_starts_with($c, 'PLY')) {
            if (preg_match('/^PLY(\d+)X(\d+)$/', $c, $m)) {
                return "Polymailer {$m[1]} x {$m[2]}";
            }
            return "Polymailer";
        }

        if (str_starts_with($c, 'FLC')) {
            $gram = substr($c, 3, 3);
            $clr = substr($c, 6);
            return 'Fleece ' . $gram . ' ' . ($colors[$clr] ?? $clr);
        }

        if (str_starts_with($c, 'RIB')) {
            $gram = substr($c, 3, 3);
            $clr = substr($c, 6);
            return 'Rib ' . $gram . ' ' . ($colors[$clr] ?? $clr);
        }

        $catName = $prefix[$catCode] ?? $catCode;

        preg_match('/([A-Za-z0-9]+?)([A-Z]{3})$/', $c, $m);
        $model = $m[1] ?? $c;
        $clr = $m[2] ?? '';
        $colorName = $colors[$clr] ?? $clr;

        return "{$catName} {$model} {$colorName}";
    }

    private function guessHppFromCode(string $code): ?int
    {
        $c = strtoupper($code);
        $base = null;

        $prefix2 = substr($c, 0, 2);

        if (in_array($prefix2, ['K1', 'K2', 'K3', 'K5', 'K7'], true)) {
            $base = 30000;
        }

        if (in_array($prefix2, ['J3', 'J5', 'J7'], true)) {
            $base = 45000;
        }

        if (in_array($prefix2, ['L1', 'L2'], true)) {
            $base = 35000;
        }

        if (in_array($prefix2, ['C3', 'C5', 'C7'], true)) {
            $base = 35000;
        }

        if (in_array($prefix2, ['T1', 'T2'], true)) {
            $base = 30000;
        }

        if (preg_match('/^S[2-4]RDM/i', $c)) {
            $base = 6700;
        } elseif (preg_match('/^S5RDM/i', $c)) {
            $base = 8400;
        }

        if ($base === null) {
            return null;
        }

        if (str_ends_with($c, '-6')) {
            $base *= 6;
        } elseif (str_ends_with($c, '-3')) {
            $base *= 3;
        }

        return $base;
    }
}
