<?php

namespace App\Services\Developer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DeveloperLedgerResetService
{
    /**
     * Transactional accounting, inventory, production, and purchasing data.
     * Master data (COA, mappings, suppliers, items, warehouses, BOM, users)
     * is preserved.
     */
    private const TABLES = [
        // Accounting ledgers.
        'journal_lines',
        'journals',
        'standalone_accounting_journal_lines',
        'standalone_accounting_journals',
        'cash_expense_reclassifications',
        'employee_loan_repayments',
        'employee_savings_transactions',
        'loan_repayments',
        'marketplace_accounting_postings',
        'piecework_payroll_lines',
        'piecework_payroll_periods',
        'supplier_loan_repayments',

        // Purchasing transaction documents. Child tables must be deleted
        // before their headers so the reset remains valid with FK checks.
        'purchase_return_line_photos',
        'purchase_return_lines',
        'purchase_receipt_qcs',
        'purchase_receipt_lines',
        'purchase_payments',
        'supplier_invoices',
        'purchase_returns',
        'purchase_receipts',
        'purchase_order_lines',
        'purchase_orders',
        'purchase_request_lines',
        'purchase_requests',

        // Inventory ledger and stock documents.
        'inventory_audit_findings',
        'inventory_adjustment_lines',
        'inventory_adjustments',
        'inventory_mutations',
        'inventory_stocks',
        'external_transfer_lines',
        'external_transfers',
        'inventory_transfer_lines',
        'inventory_transfers',
        'stock_opname_lines',
        'stock_opnames',
        'stock_request_lines',
        'stock_requests',

        // Production and WIP documents.
        // Correction records are transactional production data too. Keep them
        // in the same reset scope as cutting jobs, otherwise the cutting page
        // still shows historical "Dikoreksi" rows after balances are cleared.
        'cutting_bundle_corrections',
        'cutting_job_lots',
        'cutting_qc_cancellations',
        'cutting_job_bundles',
        'cutting_jobs',
        'finishing_repair_lines',
        'finishing_repairs',
        'finishing_job_lines',
        'finishing_jobs',
        'production_activities',
        'production_cost_periods',
        'production_issue_lines',
        'production_issues',
        'production_logs',
        'production_movements',
        'production_order_lines',
        'production_orders',
        'production_receipt_lines',
        'production_receipts',
        'qc_results',
        'sewing_pickup_line_supply_lines',
        'sewing_pickup_lines',
        'sewing_pickup_supply_lines',
        'sewing_pickups',
        'sewing_reject_conversions',
        'sewing_return_lines',
        'sewing_returns',
        'wip_fin_adjustment_lines',
        'wip_fin_adjustments',
        'wip_opname_lines',
        'wip_opname_periods',
    ];

    /**
     * Journal references on preserved documents must not point to deleted rows.
     * Foreign keys with nullOnDelete handle most of these, but some historical
     * columns are plain unsigned integers, so clear them explicitly.
     */
    private const PRESERVED_JOURNAL_LINKS = [
        'cash_expenses' => ['journal_id'],
        'cash_receipts' => ['journal_id'],
        'cash_transfers' => ['journal_id'],
        'employee_loans' => ['journal_id'],
        'loan_repayments' => ['journal_id'],
        'loans' => ['journal_id'],
        'marketplace_finance_settlements' => ['journal_id'],
        'marketplace_financial_transactions' => ['sale_journal_id', 'escrow_journal_id'],
        'marketplace_payouts' => ['journal_id'],
        'purchase_orders' => ['expense_journal_id'],
        'purchase_payments' => ['journal_id', 'standalone_journal_id'],
        'purchase_receipts' => ['journal_id'],
        'purchase_returns' => ['journal_id'],
        'sales_invoices' => ['journal_id'],
        'supplier_ap_opening_balances' => ['journal_id'],
        'supplier_loans' => ['journal_id'],
    ];

    /**
     * @return array{tables: array<string, int>, total: int}
     */
    public function reset(): array
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Reset saldo hanya boleh dijalankan di APP_ENV=local.');
        }

        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new RuntimeException('Reset saldo developer saat ini hanya mendukung SQLite lokal.');
        }

        $connection = DB::connection();
        $connection->statement('PRAGMA foreign_keys = OFF');

        try {
            $deleted = DB::transaction(function (): array {
                $counts = [];

                foreach (self::PRESERVED_JOURNAL_LINKS as $table => $columns) {
                    if (! Schema::hasTable($table)) {
                        continue;
                    }

                    foreach ($columns as $column) {
                        if (Schema::hasColumn($table, $column)) {
                            DB::table($table)->update([$column => null]);
                        }
                    }
                }

                foreach (self::TABLES as $table) {
                    if (! Schema::hasTable($table)) {
                        continue;
                    }

                    $counts[$table] = DB::table($table)->delete();
                }

                // Lots are referenced by preserved purchasing/sales documents.
                // Keep those references valid while clearing their balance fields.
                if (Schema::hasTable('lots')) {
                    $lotBalanceColumns = array_values(array_filter([
                        'initial_qty',
                        'initial_cost',
                        'qty_onhand',
                        'total_cost',
                        'avg_cost',
                    ], fn (string $column): bool => Schema::hasColumn('lots', $column)));

                    if ($lotBalanceColumns !== []) {
                        DB::table('lots')->update(array_fill_keys($lotBalanceColumns, 0));
                    }
                }

                if (Schema::hasTable('sqlite_sequence')) {
                    foreach (array_keys($counts) as $table) {
                        DB::table('sqlite_sequence')->where('name', $table)->delete();
                    }
                }

                return $counts;
            });
        } finally {
            $connection->statement('PRAGMA foreign_keys = ON');
        }

        return [
            'tables' => $deleted,
            'total' => array_sum($deleted),
        ];
    }
}
