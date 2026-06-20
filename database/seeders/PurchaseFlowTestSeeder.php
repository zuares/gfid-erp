<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Item;
use App\Models\PaymentMethod;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use App\Models\PurchaseReceipt;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use App\Services\Purchasing\GoodsReceiptService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder untuk test alur: PO → GRN → Payment + verifikasi jurnal.
 *
 * Jalankan: php artisan db:seed --class=PurchaseFlowTestSeeder
 *
 * OUTPUT: jurnal yang dibuat akan ditampilkan di console.
 */
class PurchaseFlowTestSeeder extends Seeder
{
    public function __construct(
        protected GoodsReceiptService $grnService,
        protected JournalService $journal,
    ) {}

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('=== PURCHASE FLOW TEST SEEDER ===');

        // ── Ambil data master ───────────────────────────────
        $supplier  = Supplier::firstOrFail();
        $item      = Item::where('hpp', '>', 0)->firstOrFail();
        $warehouse = Warehouse::where('type', 'raw_material')->firstOrFail();
        $pmCash    = PaymentMethod::where('mode', 'cash')->firstOrFail();
        $cashAcc   = Account::where('is_cash', 1)->where('code', '1101')->firstOrFail();
        $owner     = \App\Models\User::where('role', 'owner')->firstOrFail();

        $unitPrice = 50000;   // harga beli per unit
        $qty       = 10;
        $total     = $unitPrice * $qty;  // 500.000

        // ── 1. Buat PO ─────────────────────────────────────
        $this->command->info('1. Membuat Purchase Order...');

        $po = PurchaseOrder::create([
            'code'              => 'TEST-PO-' . now()->format('YmdHis'),
            'date'              => now()->toDateString(),
            'supplier_id'       => $supplier->id,
            'payment_method_id' => $pmCash->id,
            'subtotal'          => $total,
            'discount'          => 0,
            'tax_percent'       => 0,
            'tax_amount'        => 0,
            'shipping_cost'     => 0,
            'grand_total'       => $total,
            'status'            => 'approved',
            'order_type'        => 'purchase',
            'received_status'   => 'pending',
            'created_by'        => $owner->id,
            'approved_by'       => $owner->id,
            'approved_at'       => now(),
        ]);

        $poLine = $po->lines()->create([
            'item_id'    => $item->id,
            'qty'        => $qty,
            'unit_price' => $unitPrice,
            'discount'   => 0,
            'line_total' => $total,
        ]);

        $this->command->info("   ✓ PO dibuat: {$po->code} (id={$po->id}) — Rp " . number_format($total, 0, ',', '.'));

        // ── 2. Buat GRN ────────────────────────────────────
        $this->command->info('2. Membuat GRN (Goods Receipt)...');

        $grn = $this->grnService->create([
            'purchase_order_id' => $po->id,
            'supplier_id'       => $supplier->id,
            'warehouse_id'      => $warehouse->id,
            'date'              => now()->toDateString(),
            'status'            => 'draft',
            'created_by'        => $owner->id,
            'discount'          => 0,
            'tax_percent'       => 0,
            'shipping_cost'     => 0,
            'lines'             => [
                [
                    'item_id'               => $item->id,
                    'qty_received'          => $qty,
                    'qty_reject'            => 0,
                    'unit_price'            => $unitPrice,
                    'purchase_order_line_id'=> $poLine->id,
                ],
            ],
        ]);

        $this->command->info("   ✓ GRN dibuat: {$grn->code} (id={$grn->id}) — status: {$grn->status}");

        // ── 3. Post GRN → stok masuk + jurnal ──────────────
        $this->command->info('3. Posting GRN...');

        try {
            $grn = $this->grnService->post($grn);
            $this->command->info("   ✓ GRN berhasil diposting — status: {$grn->status}");
            $this->command->info("   ✓ Grand total GRN: Rp " . number_format((float)$grn->grand_total, 0, ',', '.'));
            if ($grn->journal_id) {
                $this->command->info("   ✓ Journal ID: {$grn->journal_id}");
            }
        } catch (\Throwable $e) {
            $this->command->error("   ✗ GRN post gagal: " . $e->getMessage());
            return;
        }

        // ── 4. Cek jurnal yang dibuat saat GRN post ─────────
        $this->command->info('4. Mengecek jurnal GRN...');
        $this->printJournalsForSource(['grn_inv', 'grn_exp', 'purchase_receipt_post'], $grn->id);

        // ── 5. Buat Payment ─────────────────────────────────
        $this->command->info('5. Membuat Pembayaran...');

        $payAmount = $total; // lunasi penuh
        $payment = PurchasePayment::create([
            'purchase_order_id'  => $po->id,
            'date'               => now()->toDateString(),
            'payment_method_id'  => $pmCash->id,
            'cash_account_id'    => $cashAcc->id,
            'type'               => 'payment',
            'amount'             => $payAmount,
            'ref_no'             => 'TEST-PAY-' . now()->format('YmdHis'),
            'notes'              => '[TEST] Pembayaran lunas PO test',
            'created_by'         => $owner->id,
        ]);

        $this->command->info("   ✓ Payment dibuat: id={$payment->id} — Rp " . number_format($payAmount, 0, ',', '.'));

        // ── 6. Post jurnal payment ───────────────────────────
        $this->command->info('6. Posting jurnal payment...');

        try {
            $payJournal = $this->journal->postPurchasePayment($payment);
            $this->command->info("   ✓ Journal payment dibuat: id={$payJournal->id}");
        } catch (\Throwable $e) {
            $this->command->error("   ✗ Payment journal gagal: " . $e->getMessage());
        }

        // ── 7. Cek jurnal payment ────────────────────────────
        $this->command->info('7. Mengecek jurnal payment...');
        $this->printJournalsForSource(['purchase_payment'], $payment->id);

        // ── 8. Ringkasan AP ─────────────────────────────────
        $this->command->info('');
        $this->command->info('=== RINGKASAN AKUN 2101 (Hutang Dagang) SETELAH TEST ===');
        $ap = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('a.code', '2101')
            ->whereNull('j.voided_at')
            ->selectRaw('SUM(jl.debit) as dr, SUM(jl.credit) as cr')
            ->first();

        $this->command->info('  Total Cr (hutang masuk dari GRN) : Rp ' . number_format((float)($ap->cr ?? 0), 0, ',', '.'));
        $this->command->info('  Total Dr (bayar/retur)           : Rp ' . number_format((float)($ap->dr ?? 0), 0, ',', '.'));
        $this->command->info('  Saldo AP outstanding             : Rp ' . number_format((float)(($ap->cr ?? 0) - ($ap->dr ?? 0)), 0, ',', '.'));
        $this->command->info('');
        $this->command->info('Test selesai! Cek Trial Balance di UI untuk verifikasi.');
    }

    protected function printJournalsForSource(array $sourceTypes, int $sourceId): void
    {
        $journals = DB::table('journals as j')
            ->whereIn('j.source_type', $sourceTypes)
            ->where('j.source_id', $sourceId)
            ->whereNull('j.voided_at')
            ->get();

        if ($journals->isEmpty()) {
            $this->command->warn('   Tidak ada jurnal ditemukan untuk source_id=' . $sourceId);
            return;
        }

        foreach ($journals as $journal) {
            $this->command->line("   Journal #{$journal->id} [{$journal->source_type}] {$journal->description}");

            $lines = DB::table('journal_lines as jl')
                ->join('accounts as a', 'a.id', '=', 'jl.account_id')
                ->where('jl.journal_id', $journal->id)
                ->select('a.code', 'a.name', 'jl.debit', 'jl.credit')
                ->get();

            foreach ($lines as $line) {
                $dr = (float) $line->debit > 0  ? '  Dr ' . str_pad(number_format((float)$line->debit, 0, ',', '.'), 15) : str_repeat(' ', 22);
                $cr = (float) $line->credit > 0 ? '  Cr ' . number_format((float)$line->credit, 0, ',', '.') : '';
                $this->command->line("     {$line->code} {$line->name}{$dr}{$cr}");
            }

            $totals = DB::table('journal_lines')
                ->where('journal_id', $journal->id)
                ->selectRaw('SUM(debit) as dr, SUM(credit) as cr')
                ->first();
            $balanced = abs((float)$totals->dr - (float)$totals->cr) < 0.01 ? '✓ BALANCED' : '✗ TIDAK BALANCED!';
            $this->command->line("     --- Total Dr=" . number_format((float)$totals->dr, 0, ',', '.') . " Cr=" . number_format((float)$totals->cr, 0, ',', '.') . " [{$balanced}]");
        }
    }
}
