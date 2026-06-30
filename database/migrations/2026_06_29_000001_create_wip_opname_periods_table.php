<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wip_opname_periods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // WOP-CUT-20260629-001
            $table->string('scope')->default('cutting'); // cutting | sewing | all (extensible)
            $table->date('date');
            $table->text('notes')->nullable();

            // open → counting → pending_approval → approved → closed
            $table->string('status', 30)->default('open');

            $table->foreignIdFor(User::class, 'opened_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('opened_at')->nullable();

            $table->foreignIdFor(User::class, 'approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->foreignIdFor(User::class, 'closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['scope', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wip_opname_periods');
    }
};
