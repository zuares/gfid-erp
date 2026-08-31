<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('channel', 32);
            $table->foreignId('marketplace_order_id')->nullable()->constrained('marketplace_orders')->nullOnDelete();
            $table->string('order_sn', 120);
            $table->foreignId('sales_invoice_id')->nullable()->constrained('sales_invoices')->nullOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained('shipments')->nullOnDelete();
            $table->string('currency', 10)->default('IDR');
            $table->decimal('gross_amount', 18, 2)->default(0);
            $table->decimal('net_amount', 18, 2)->default(0);
            $table->string('escrow_status', 30)->default('pending');
            $table->string('income_status', 30)->default('pending');
            $table->timestamp('released_at')->nullable();
            $table->string('source_hash', 64)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->foreignId('sale_journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('escrow_journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->timestamps();

            $table->unique(['store_id', 'channel', 'order_sn'], 'mp_financial_transactions_store_channel_order_unique');
            $table->index(['marketplace_order_id', 'sales_invoice_id', 'shipment_id'], 'mp_financial_transactions_operational_refs_idx');
            $table->index(['store_id', 'escrow_status', 'income_status'], 'mp_financial_transactions_status_idx');
            $table->index('released_at');
        });

        Schema::create('marketplace_financial_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_transaction_id')
                ->constrained('marketplace_financial_transactions')
                ->cascadeOnDelete();
            $table->string('component_code', 80);
            $table->string('component_name', 150);
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('direction', 20);
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('provider_line_id', 150)->nullable();
            $table->string('source_hash', 64)->nullable();
            $table->json('raw_payload')->nullable();
            // Application-generated fingerprint. Unlike a nullable composite
            // unique index, this remains effective on MySQL and SQLite when
            // provider_line_id/source_hash are absent.
            $table->string('dedupe_key', 64);
            $table->timestamps();

            $table->unique('dedupe_key', 'mp_financial_components_dedupe_unique');
            $table->index(['financial_transaction_id', 'component_code'], 'mp_financial_components_transaction_code_idx');
        });

        Schema::create('marketplace_finance_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('channel', 32);
            $table->string('external_settlement_id', 150);
            $table->date('settlement_date')->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('currency', 10)->default('IDR');
            $table->foreignId('bank_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('status', 30)->default('pending');
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'channel', 'external_settlement_id'], 'mp_finance_settlements_store_channel_external_unique');
            $table->index(['store_id', 'settlement_date'], 'mp_finance_settlements_store_date_idx');
            $table->index(['status', 'settlement_date'], 'mp_finance_settlements_status_date_idx');
        });

        Schema::create('marketplace_finance_settlement_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')
                ->constrained('marketplace_finance_settlements')
                ->cascadeOnDelete();
            $table->foreignId('financial_transaction_id')
                ->constrained('marketplace_financial_transactions')
                ->cascadeOnDelete();
            $table->string('order_sn', 120);
            $table->decimal('allocated_amount', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['settlement_id', 'financial_transaction_id'], 'mp_finance_allocations_settlement_transaction_unique');
            $table->unique(['settlement_id', 'order_sn'], 'mp_finance_allocations_settlement_order_unique');
            $table->index('financial_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_finance_settlement_allocations');
        Schema::dropIfExists('marketplace_finance_settlements');
        Schema::dropIfExists('marketplace_financial_components');
        Schema::dropIfExists('marketplace_financial_transactions');
    }
};
