<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Tabel backup (aman untuk rollback)
        Schema::create('item_category_migration_backups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->unique(); // 1 backup per item
            $table->unsignedBigInteger('old_item_category_id')->nullable();
            $table->string('old_item_category_code')->nullable();
            $table->unsignedBigInteger('new_item_category_id')->nullable();
            $table->string('new_item_category_code')->nullable();
            $table->timestamp('migrated_at')->nullable();
            $table->timestamps();
        });

        DB::transaction(function () {

            // --- Helper: ambil id category berdasarkan code ---
            $catId = function (string $code): ?int {
                return DB::table('item_categories')->where('code', $code)->value('id');
            };

            // --- (A) Hard mapping: old_code => new_code (yang pasti) ---
            // Contoh: kalau dulu kamu punya "BPU-LAMA" mau dipindah ke "BPU", dsb.
            $directMap = [
                // 'OLD' => 'NEW',
                // 'ACC' => 'ACC', // kalau mau keep, tidak perlu dimigrate
            ];

            // --- (B) Mapping khusus untuk item yang category-nya masih "FG" (ambiguous) ---
            // Ini rule aman berdasarkan prefix code kamu yang sudah konsisten.
            $prefixMapForFG = [
                // Cargo Jogger Pendek
                'C' => 'CRG', // C5BLK, C7MST, dst

                // Cargo Jogger Panjang
                'L' => 'LCG', // L1BLK, L2NVY, dst

                // Shot boxer brief
                'S' => 'SHT', // S2RDM, S4RDM-6, dst

                // Jogger Pendek Bodyfit
                'T' => 'TJR', // T1BLK, T2NVY, dst

                // Jogger Panjang Basic
                'J' => 'LJR', // J3BLK, J5NVY, dst

                // Jogger Pendek Basic
                'K' => 'SJR', // K1BLK, K7WHT, dst
            ];

            // ========== STEP 1: Backup kandidat rows yang akan diubah ==========
            // Kandidat = item yang:
            // - belum ada di backup table (idempotent)
            // - memiliki item_category_id yang mengarah ke category code "FG" atau termasuk directMap keys
            $fgId = $catId('FG');

            // directMap old ids
            $directOldCodes = array_keys($directMap);
            $directOldIds = [];
            foreach ($directOldCodes as $oldCode) {
                $id = $catId($oldCode);
                if ($id) {
                    $directOldIds[] = $id;
                }

            }

            $candidateCategoryIds = array_values(array_filter(array_unique(array_merge(
                $fgId ? [$fgId] : [],
                $directOldIds
            ))));

            if (empty($candidateCategoryIds)) {
                // Tidak ada kandidat, aman exit
                return;
            }

            // Insert backup untuk item kandidat (yang belum ada backup)
            // Ambil old code via join
            $items = DB::table('items as i')
                ->leftJoin('item_categories as c', 'c.id', '=', 'i.item_category_id')
                ->whereIn('i.item_category_id', $candidateCategoryIds)
                ->select('i.id as item_id', 'i.item_category_id', 'c.code as old_code')
                ->get();

            foreach ($items as $row) {
                $exists = DB::table('item_category_migration_backups')->where('item_id', $row->item_id)->exists();
                if ($exists) {
                    continue;
                }

                DB::table('item_category_migration_backups')->insert([
                    'item_id' => $row->item_id,
                    'old_item_category_id' => $row->item_category_id,
                    'old_item_category_code' => $row->old_code,
                    'new_item_category_id' => null,
                    'new_item_category_code' => null,
                    'migrated_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // ========== STEP 2: Apply direct mapping (old_code => new_code) ==========
            foreach ($directMap as $oldCode => $newCode) {
                $oldId = $catId($oldCode);
                $newId = $catId($newCode);

                if (!$oldId || !$newId) {
                    continue;
                }

                // update items
                DB::table('items')
                    ->where('item_category_id', $oldId)
                    ->update(['item_category_id' => $newId]);

                // mark backups for affected items (yang old_code = oldCode)
                DB::table('item_category_migration_backups')
                    ->whereNull('migrated_at')
                    ->where('old_item_category_code', $oldCode)
                    ->update([
                        'new_item_category_id' => $newId,
                        'new_item_category_code' => $newCode,
                        'migrated_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            // ========== STEP 3: Apply FG-prefix mapping ==========
            if ($fgId) {
                foreach ($prefixMapForFG as $prefix => $newCode) {
                    $newId = $catId($newCode);
                    if (!$newId) {
                        continue;
                    }

                    // Update items yang category = FG dan code mulai dengan prefix (case-insensitive)
                    DB::table('items')
                        ->where('item_category_id', $fgId)
                        ->where('code', 'like', $prefix . '%')
                        ->update(['item_category_id' => $newId]);

                    // Update backups yang old_code = FG dan item code prefix tersebut
                    DB::table('item_category_migration_backups as b')
                        ->join('items as i', 'i.id', '=', 'b.item_id')
                        ->whereNull('b.migrated_at')
                        ->where('b.old_item_category_code', 'FG')
                        ->where('i.code', 'like', $prefix . '%')
                        ->update([
                            'b.new_item_category_id' => $newId,
                            'b.new_item_category_code' => $newCode,
                            'b.migrated_at' => now(),
                            'b.updated_at' => now(),
                        ]);
                }
            }

            // Optional safety: kalau masih ada item category FG yang belum termapping:
            // kamu bisa pilih salah satu:
            // 1) biarkan (paling aman)
            // 2) set null (kalau item_category_id boleh null)
            // 3) set ke kategori default tertentu
        });
    }

    public function down(): void
    {
        // Rollback: kembalikan item_category_id ke old_item_category_id berdasar backup
        DB::transaction(function () {
            $rows = DB::table('item_category_migration_backups')
                ->select('item_id', 'old_item_category_id')
                ->get();

            foreach ($rows as $r) {
                DB::table('items')
                    ->where('id', $r->item_id)
                    ->update(['item_category_id' => $r->old_item_category_id]);
            }
        });

        Schema::dropIfExists('item_category_migration_backups');
    }
};
