<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Kept as a no-op for databases that already recorded this migration.
        // Demo data is now opt-in via StandaloneDummyOpeningBalanceSeeder.
    }

    public function down(): void
    {
        // Preserve existing journals and their audit trail, including old demos.
        // Reversing schema migrations must not delete an opening balance.
    }
};
