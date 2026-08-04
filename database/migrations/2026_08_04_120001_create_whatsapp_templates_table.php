<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->string('name', 160);
            $table->text('body');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();
        DB::table('whatsapp_templates')->insert([
            [
                'key' => 'purchase_order_supplier',
                'name' => 'Purchase Order ke Supplier',
                'body' => "Halo {supplier_name},\n\nBerikut Purchase Order dari Great Fit Indonesia:\n\nPO: {po_code}\nTanggal: {po_date}\n\nDetail:\n{details}\n\nTotal: {total}\n\nMohon konfirmasi penerimaan PO ini. Terima kasih.",
                'description' => 'Dipakai saat mengirim ringkasan PO kepada supplier.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'payroll_piecework_confirmation',
                'name' => 'Konfirmasi Gaji Borongan',
                'body' => "Halo {employee_name},\n\nBerikut konfirmasi gaji borongan periode {period}:\n\nTotal: {total}\n\nMohon konfirmasi jika sudah sesuai. Terima kasih.",
                'description' => 'Template awal untuk konfirmasi gaji borongan.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'production_vendor_confirmation',
                'name' => 'Konfirmasi Produksi Vendor',
                'body' => "Halo {vendor_name},\n\nMohon konfirmasi status pekerjaan {job_reference}.\n\nCatatan: {notes}\n\nTerima kasih.",
                'description' => 'Template awal untuk komunikasi cutting/sewing vendor.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
