<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DETAIL_ACCOUNT_CODES = ['1-151-01', '1-151-02', '1-151-03'];

    public function up(): void
    {
        $now = now();

        foreach ([
            [
                'code' => '1-151-01',
                'name' => 'WIP Cutting',
                'description' => 'Persediaan dalam proses pada tahap cutting. Akun 1-151 tetap dipertahankan sebagai akun kontrol lama.',
                'type' => 'asset',
            ],
            [
                'code' => '1-151-02',
                'name' => 'WIP Sewing',
                'description' => 'Persediaan dalam proses pada tahap sewing dan jahit.',
                'type' => 'asset',
            ],
            [
                'code' => '1-151-03',
                'name' => 'WIP Finishing',
                'description' => 'Persediaan dalam proses pada tahap finishing sebelum dipindahkan menjadi barang jadi.',
                'type' => 'asset',
            ],
        ] as $account) {
            DB::table('standalone_accounting_accounts')->updateOrInsert(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'description' => $account['description'],
                    'type' => $account['type'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        Schema::create('standalone_production_account_mappings', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type', 100);
            $table->string('legacy_account_code', 30);
            $table->string('line_side', 10);
            $table->string('standalone_account_code', 30);
            $table->string('stage', 40);
            $table->string('purpose', 255);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(
                ['source_type', 'legacy_account_code', 'line_side'],
                'standalone_production_mapping_source_legacy_side_unique',
            );
            $table->index(['source_type', 'stage']);
        });

        foreach ($this->mappings() as $mapping) {
            DB::table('standalone_production_account_mappings')->updateOrInsert(
                [
                    'source_type' => $mapping['source_type'],
                    'legacy_account_code' => $mapping['legacy_account_code'],
                    'line_side' => $mapping['line_side'],
                ],
                [
                    'standalone_account_code' => $mapping['standalone_account_code'],
                    'stage' => $mapping['stage'],
                    'purpose' => $mapping['purpose'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        $accountIds = DB::table('standalone_accounting_accounts')
            ->whereIn('code', self::DETAIL_ACCOUNT_CODES)
            ->pluck('id');

        if ($accountIds->isNotEmpty()
            && DB::table('standalone_accounting_journal_lines')->whereIn('account_id', $accountIds)->exists()) {
            throw new RuntimeException('Akun WIP detail sudah dipakai jurnal dan tidak boleh dihapus saat rollback.');
        }

        Schema::dropIfExists('standalone_production_account_mappings');
        DB::table('standalone_accounting_accounts')
            ->whereIn('code', self::DETAIL_ACCOUNT_CODES)
            ->delete();
    }

    /** @return array<int, array{source_type:string,legacy_account_code:string,line_side:string,standalone_account_code:string,stage:string,purpose:string}> */
    private function mappings(): array
    {
        $cutting = [
            ['source_type' => 'cutting_job', 'legacy_account_code' => '1202', 'line_side' => 'debit', 'standalone_account_code' => '1-151-01', 'stage' => 'cutting', 'purpose' => 'Material masuk WIP Cutting'],
            ['source_type' => 'cutting_job', 'legacy_account_code' => '1201', 'line_side' => 'credit', 'standalone_account_code' => '1-150', 'stage' => 'cutting', 'purpose' => 'Material keluar dari persediaan bahan'],
            ['source_type' => 'cutting_job_wage', 'legacy_account_code' => '1202', 'line_side' => 'debit', 'standalone_account_code' => '1-151-01', 'stage' => 'cutting', 'purpose' => 'Upah masuk WIP Cutting'],
            ['source_type' => 'cutting_job_wage', 'legacy_account_code' => '2102', 'line_side' => 'credit', 'standalone_account_code' => '2-130', 'stage' => 'cutting', 'purpose' => 'Akrual upah cutting'],
            ['source_type' => 'cutting_wip', 'legacy_account_code' => '1202', 'line_side' => 'debit', 'standalone_account_code' => '1-151-01', 'stage' => 'cutting', 'purpose' => 'Hasil QC masuk WIP Cutting'],
            ['source_type' => 'cutting_wip', 'legacy_account_code' => '1202', 'line_side' => 'credit', 'standalone_account_code' => '1-151-01', 'stage' => 'cutting', 'purpose' => 'Material keluar dari WIP Cutting'],
            ['source_type' => 'cutting_wip', 'legacy_account_code' => '1204', 'line_side' => 'debit', 'standalone_account_code' => '1-153', 'stage' => 'cutting', 'purpose' => 'Hasil reject cutting'],
            ['source_type' => 'cutting_wip', 'legacy_account_code' => '2102', 'line_side' => 'credit', 'standalone_account_code' => '2-130', 'stage' => 'cutting', 'purpose' => 'Akrual biaya cutting'],
        ];

        return [
            ...$cutting,
            ['source_type' => 'App\\Models\\SewingPickup', 'legacy_account_code' => '1202', 'line_side' => 'debit', 'standalone_account_code' => '1-151-02', 'stage' => 'sewing', 'purpose' => 'Material masuk WIP Sewing'],
            ['source_type' => 'App\\Models\\SewingPickup', 'legacy_account_code' => '1202', 'line_side' => 'credit', 'standalone_account_code' => '1-151-01', 'stage' => 'sewing', 'purpose' => 'Material keluar WIP Cutting'],
            ['source_type' => 'sewing_pickup_wage', 'legacy_account_code' => '1202', 'line_side' => 'debit', 'standalone_account_code' => '1-151-02', 'stage' => 'sewing', 'purpose' => 'Upah masuk WIP Sewing'],
            ['source_type' => 'sewing_pickup_wage', 'legacy_account_code' => '2102', 'line_side' => 'credit', 'standalone_account_code' => '2-130', 'stage' => 'sewing', 'purpose' => 'Akrual upah sewing'],
            ['source_type' => 'sewing_pickup_supply', 'legacy_account_code' => '1202', 'line_side' => 'debit', 'standalone_account_code' => '1-151-02', 'stage' => 'sewing', 'purpose' => 'Kelengkapan masuk WIP Sewing'],
            ['source_type' => 'sewing_pickup_supply', 'legacy_account_code' => '1201', 'line_side' => 'credit', 'standalone_account_code' => '1-150', 'stage' => 'sewing', 'purpose' => 'Kelengkapan keluar dari persediaan bahan'],
            ['source_type' => 'sewing_pickup_supply_followup', 'legacy_account_code' => '1202', 'line_side' => 'debit', 'standalone_account_code' => '1-151-02', 'stage' => 'sewing', 'purpose' => 'Kelengkapan susulan masuk WIP Sewing'],
            ['source_type' => 'sewing_pickup_supply_followup', 'legacy_account_code' => '1201', 'line_side' => 'credit', 'standalone_account_code' => '1-150', 'stage' => 'sewing', 'purpose' => 'Kelengkapan susulan keluar dari persediaan bahan'],
            ['source_type' => 'sewing_pickup_line_void', 'legacy_account_code' => '1202', 'line_side' => 'debit', 'standalone_account_code' => '1-151-01', 'stage' => 'sewing', 'purpose' => 'Reversal material kembali ke WIP Cutting'],
            ['source_type' => 'sewing_pickup_line_void', 'legacy_account_code' => '1202', 'line_side' => 'credit', 'standalone_account_code' => '1-151-02', 'stage' => 'sewing', 'purpose' => 'Reversal material keluar dari WIP Sewing'],
            ['source_type' => 'sewing_pickup_supply_void_line', 'legacy_account_code' => '1201', 'line_side' => 'debit', 'standalone_account_code' => '1-150', 'stage' => 'sewing', 'purpose' => 'Reversal kelengkapan kembali ke bahan'],
            ['source_type' => 'sewing_pickup_supply_void_line', 'legacy_account_code' => '1202', 'line_side' => 'credit', 'standalone_account_code' => '1-151-02', 'stage' => 'sewing', 'purpose' => 'Reversal kelengkapan keluar dari WIP Sewing'],
            ['source_type' => 'sewing_return_ok', 'legacy_account_code' => '1203', 'line_side' => 'debit', 'standalone_account_code' => '1-152', 'stage' => 'sewing', 'purpose' => 'Sewing Return langsung menjadi barang jadi; WIP-FIN memakai override tahap'],
            ['source_type' => 'sewing_return_ok', 'legacy_account_code' => '1202', 'line_side' => 'credit', 'standalone_account_code' => '1-151-02', 'stage' => 'sewing', 'purpose' => 'Material keluar dari WIP Sewing'],
            ['source_type' => 'sewing_return_reject', 'legacy_account_code' => '1204', 'line_side' => 'debit', 'standalone_account_code' => '1-153', 'stage' => 'sewing', 'purpose' => 'Reject sewing masuk persediaan reject'],
            ['source_type' => 'sewing_return_reject', 'legacy_account_code' => '1202', 'line_side' => 'credit', 'standalone_account_code' => '1-151-02', 'stage' => 'sewing', 'purpose' => 'Reject sewing keluar dari WIP Sewing'],
            ['source_type' => 'sewing_reject_rework_ok', 'legacy_account_code' => '1203', 'line_side' => 'debit', 'standalone_account_code' => '1-152', 'stage' => 'sewing', 'purpose' => 'Rework reject menjadi barang jadi'],
            ['source_type' => 'sewing_reject_rework_ok', 'legacy_account_code' => '1204', 'line_side' => 'credit', 'standalone_account_code' => '1-153', 'stage' => 'sewing', 'purpose' => 'Rework keluar dari persediaan reject'],
            ['source_type' => 'finishing_job', 'legacy_account_code' => '1203', 'line_side' => 'debit', 'standalone_account_code' => '1-152', 'stage' => 'finishing', 'purpose' => 'Finishing OK menjadi barang jadi'],
            ['source_type' => 'finishing_job', 'legacy_account_code' => '1204', 'line_side' => 'debit', 'standalone_account_code' => '1-153', 'stage' => 'finishing', 'purpose' => 'Finishing reject masuk persediaan reject'],
            ['source_type' => 'finishing_job', 'legacy_account_code' => '1202', 'line_side' => 'credit', 'standalone_account_code' => '1-151-03', 'stage' => 'finishing', 'purpose' => 'Finishing keluar dari WIP Finishing'],
            ['source_type' => 'finishing_bom', 'legacy_account_code' => '1202', 'line_side' => 'debit', 'standalone_account_code' => '1-151-03', 'stage' => 'finishing', 'purpose' => 'Biaya material finishing masuk WIP Finishing'],
            ['source_type' => 'finishing_bom', 'legacy_account_code' => '1201', 'line_side' => 'credit', 'standalone_account_code' => '1-150', 'stage' => 'finishing', 'purpose' => 'Material finishing keluar dari persediaan bahan'],
            ['source_type' => 'shipment_cogs', 'legacy_account_code' => '5101', 'line_side' => 'debit', 'standalone_account_code' => '5-110', 'stage' => 'shipment', 'purpose' => 'HPP barang terjual'],
            ['source_type' => 'shipment_cogs', 'legacy_account_code' => '1203', 'line_side' => 'credit', 'standalone_account_code' => '1-152', 'stage' => 'shipment', 'purpose' => 'Barang jadi keluar saat penjualan'],
            ['source_type' => 'shipment_wave_cogs', 'legacy_account_code' => '5101', 'line_side' => 'debit', 'standalone_account_code' => '5-110', 'stage' => 'shipment', 'purpose' => 'HPP wave shipment'],
            ['source_type' => 'shipment_wave_cogs', 'legacy_account_code' => '1203', 'line_side' => 'credit', 'standalone_account_code' => '1-152', 'stage' => 'shipment', 'purpose' => 'Barang jadi keluar saat wave shipment'],
        ];
    }
};
