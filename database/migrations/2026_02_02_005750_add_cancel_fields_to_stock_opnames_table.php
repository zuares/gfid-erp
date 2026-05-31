<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // NO-OP: Cancel/cancelled fields sudah ditangani migration lain
        // Migration ini sengaja dikosongkan untuk menghindari duplicate column/table/index.
    }

    public function down(): void
    {
        //
    }
};
