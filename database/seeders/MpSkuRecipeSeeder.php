<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MpSkuRecipeSeeder extends Seeder
{
    public function run(): void
    {
        // null = berlaku untuk semua channel
        // kalau mau spesifik: 'shopee'
        $channel = null;

        $items = Item::query()
            ->where('type', 'finished_good')
            ->select('id', 'code')
            ->get();

        // map code->id untuk lookup base
        $idByCode = $items
            ->mapWithKeys(fn($it) => [strtoupper(trim($it->code)) => (int) $it->id])
            ->toArray();

        $upserts = 0;

        foreach ($items as $it) {
            $code = $this->norm($it->code);
            if ($code === '') {
                continue;
            }

            // 1) Direct map: mp_sku_code = items.code -> item x1
            $upserts += $this->upsertRecipeCode(
                channel: $channel,
                mpSkuCode: $code,
                itemId: (int) $it->id,
                multiplier: 1,
                notes: 'seed: direct finished_good map'
            );

            // 2) Auto map MP suffix "-1" -> base item (kalau base ada)
            // contoh: K5ABT-1 (MP) -> K5ABT (ERP) x1
            if (!str_ends_with($code, '-1')) {
                $maybeMp = $code . '-1';
                if (!isset($idByCode[$maybeMp])) {
                    $upserts += $this->upsertRecipeCode(
                        channel: $channel,
                        mpSkuCode: $maybeMp,
                        itemId: (int) $it->id,
                        multiplier: 1,
                        notes: 'seed: mp "-1" alias -> base x1'
                    );
                }
            }

            // 3) Optional explode bundle: S2RDM-3 / S2RDM-6 -> S2RDM x3/x6 (kalau base ada)
            // Ini membantu kalau operasional scan pakai S2RDM pcs, sementara MP kirim bundle.
            if (preg_match('/^([A-Z0-9]+)-(3|6)$/', $code, $m)) {
                $base = $this->norm($m[1]);
                $mult = (int) $m[2];

                // hanya explode kalau base item ada
                if (isset($idByCode[$base])) {
                    $upserts += $this->upsertRecipeCode(
                        channel: $channel,
                        mpSkuCode: $code, // mp sku bundle
                        itemId: (int) $idByCode[$base], // map ke base pcs
                        multiplier: $mult,
                        notes: "seed: explode bundle -> {$base} x{$mult}"
                    );
                }
            }
        }

        $this->command?->info("MpSkuRecipeFromFinishedGoodsSeeder done. Upserts: {$upserts}");
    }

    private function upsertRecipeCode(?string $channel, string $mpSkuCode, int $itemId, int $multiplier, string $notes): int
    {
        $ok = DB::table('mp_sku_recipes')->updateOrInsert(
            [
                'channel' => $channel,
                'mp_sku_parent' => null,
                'mp_sku_code' => $mpSkuCode,
                'item_id' => $itemId,
            ],
            [
                'multiplier' => max(1, (int) $multiplier),
                'notes' => $notes,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return $ok ? 1 : 0;
    }

    private function norm(?string $s): string
    {
        if ($s === null) {
            return '';
        }

        $s = trim(str_replace("\xc2\xa0", ' ', $s));
        $s = strtoupper($s);
        $s = preg_replace('/\s+/', '', $s);
        return $s ?: '';
    }
}
