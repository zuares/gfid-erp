<?php

namespace App\Services\Purchasing;

use App\Helpers\CodeGenerator;
use App\Models\Account;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\ItemCostSnapshot;
use App\Models\Lot;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
use App\Models\SupplierPrice;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use App\Services\Inventory\InventoryService;
use App\Services\Purchasing\PurchaseOrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class GoodsReceiptService
{
    public function __construct(
        protected InventoryService $inventory,
        protected JournalService $journal,
        protected PurchaseOrderService $purchaseOrders,
    ) {}

    /**
     * Buat GRN baru (status: draft).
     */
    public function create(array $payload): PurchaseReceipt
    {
        return DB::transaction(function () use ($payload) {
            $linesData = $payload['lines'] ?? [];
            unset($payload['lines']);

            $supplierId = (int) ($payload['supplier_id'] ?? 0);
            $actorId = (int) ($payload['created_by'] ?? (auth()->id() ?? 0)) ?: null;

            // =====================================================
            // VALIDASI RELASI PO (defense-in-depth level Service)
            // - PO boleh: draft / approved / closed ; TIDAK cancelled/terhapus.
            // - Supplier GRN wajib = supplier PO.
            // - Kunci baris PO agar tidak balapan dengan edit PO.
            // =====================================================
            $po = null;
            $poId = !empty($payload['purchase_order_id']) ? (int) $payload['purchase_order_id'] : null;
            if ($poId) {
                $po = PurchaseOrder::query()->whereKey($poId)->lockForUpdate()->first();
                $this->assertPoReceivable($po, $supplierId, $poId);
            }

            // Validasi per-baris + PAKSA harga dari PO (abaikan harga dari request).
            $linesData = $this->validateAndEnrichLinesFromPo(
                is_array($linesData) ? $linesData : [],
                $po,
                $poId
            );

            if (empty($payload['code'] ?? null)) {
                $suppCode = DB::table('suppliers')
                    ->where('id', (int) ($payload['supplier_id'] ?? 0))
                    ->value('code');
                $prefix = $suppCode ? 'GRN-' . strtoupper($suppCode) : 'GRN';
                $payload['code'] = CodeGenerator::make($prefix);
            }

            // Auto-generate surat_jalan_no jika user tidak isi manual
            if (empty($payload['surat_jalan_no'] ?? null)) {
                $suppCode = $suppCode ?? DB::table('suppliers')
                    ->where('id', (int) ($payload['supplier_id'] ?? 0))
                    ->value('code');
                $sjPrefix = $suppCode ? 'SJ-' . strtoupper($suppCode) : 'SJ';
                $payload['surat_jalan_no'] = CodeGenerator::make($sjPrefix);
            }

            $payload['subtotal'] = 0;
            $payload['discount'] = $this->num($payload['discount'] ?? 0);
            $payload['tax_percent'] = $this->num($payload['tax_percent'] ?? 0);
            $payload['tax_amount'] = 0;
            $payload['shipping_cost'] = $this->num($payload['shipping_cost'] ?? 0);
            $payload['grand_total'] = 0;
            $payload['status'] = $payload['status'] ?? 'draft';

            /** @var PurchaseReceipt $grn */
            $grn = PurchaseReceipt::create($payload);

            // sync lines FULL (hpp + expense)
            $subtotalAll = $this->syncLines($grn, $linesData);

            // subtotal header = total semua line (jujur)
            $this->recalcTotals($grn, $subtotalAll);

            // =====================================================
            // KUNCI PO saat GRN pertama berhasil disimpan (create),
            // supaya line yang dirujuk GRN tidak bisa dihapus/diubah.
            // =====================================================
            if ($po) {
                $this->purchaseOrders->lockForReceiving(
                    $po,
                    (int) $grn->id,
                    $actorId,
                    "Dikunci oleh GRN {$grn->code}."
                );
            }

            return $grn->fresh(['lines.item', 'supplier', 'warehouse']);
        });
    }

    /**
     * Validasi PO boleh menjadi acuan GRN.
     * Boleh: draft / approved / closed. Tolak: tidak ada / cancelled / supplier beda.
     */
    protected function assertPoReceivable(?PurchaseOrder $po, int $supplierId, int $poId): void
    {
        if (!$po) {
            throw ValidationException::withMessages([
                'purchase_order_id' => "Purchase Order #{$poId} tidak ditemukan atau sudah dihapus.",
            ]);
        }

        if ($po->status === 'cancelled') {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'PO berstatus cancelled tidak bisa dijadikan acuan GRN.',
            ]);
        }

        if (!$po->isReceivableForGrn()) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'GRN hanya boleh mengacu ke PO berstatus draft, approved, atau closed.',
            ]);
        }

        if ($supplierId > 0 && (int) $po->supplier_id !== $supplierId) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Supplier GRN harus sama dengan supplier pada PO.',
            ]);
        }
    }

    /**
     * Validasi setiap baris terhadap PO + PAKSA unit_price dari PO (server-side).
     *
     * Aturan:
     * - Baris ber-purchase_order_line_id WAJIB punya header PO, dan line tsb WAJIB
     *   milik PO header (bukan PO lain / bukan line terhapus).
     * - item_id baris harus == item_id PO line.
     * - qty_received + qty_reject tidak boleh melebihi outstanding PO line.
     * - unit_price DISETEL server-side dari PO line (harga dari request diabaikan).
     */
    protected function validateAndEnrichLinesFromPo(
        array $linesData,
        ?PurchaseOrder $po,
        ?int $poId,
        ?int $excludeReceiptId = null,
    ): array
    {
        // Kumpulkan po_line_id yang direferensikan
        $poLineIds = collect($linesData)
            ->pluck('purchase_order_line_id')
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (int) $v)
            ->unique()->values()->all();

        $poLines = collect();
        $alreadyByLine = [];
        if (!empty($poLineIds)) {
                $poLines = PurchaseOrderLine::query()
                    ->whereIn('id', $poLineIds)
                    ->with('item:id,unit,stock_unit,purchase_unit,purchase_conversion_factor,default_allocation,default_expense_account_id')
                    ->lockForUpdate()
                    ->get(['id', 'purchase_order_id', 'item_id', 'qty', 'unit_price', 'purchase_unit', 'stock_unit', 'conversion_factor', 'allocation', 'expense_account_id'])
                    ->keyBy('id');

            // qty terpakai per PO line dari GRN lain (draft + posted) — cegah over-receipt.
            $alreadyQuery = DB::table('purchase_receipt_lines as prl')
                ->join('purchase_receipts as pr', 'pr.id', '=', 'prl.purchase_receipt_id')
                ->whereIn('prl.purchase_order_line_id', $poLineIds)
                ->whereIn('pr.status', ['draft', 'posted'])
                // Replacement GRN mengganti barang retur, bukan penerimaan
                // baru atas PO asal.
                ->where(function ($q) {
                    $q->whereNull('pr.is_replacement')
                        ->orWhere('pr.is_replacement', false);
                });

            if ($excludeReceiptId) {
                $alreadyQuery->where('pr.id', '!=', $excludeReceiptId);
            }

            $alreadyByLine = $alreadyQuery
                ->groupBy('prl.purchase_order_line_id')
                ->selectRaw('prl.purchase_order_line_id as line_id, SUM(COALESCE(prl.stock_qty_received, prl.qty_received * COALESCE(prl.conversion_factor, 1)) + COALESCE(prl.stock_qty_reject, prl.qty_reject * COALESCE(prl.conversion_factor, 1))) as used_stock')
                ->pluck('used_stock', 'line_id')
                ->map(fn ($v) => (float) $v)
                ->toArray();
        }

        $out = [];
        foreach ($linesData as $i => $row) {
            $poLineId = ($row['purchase_order_line_id'] ?? null);
            $poLineId = ($poLineId === null || $poLineId === '') ? null : (int) $poLineId;

            if ($poLineId) {
                if (!$po) {
                    throw ValidationException::withMessages([
                        "lines.$i" => 'Baris merujuk PO line tetapi GRN tidak punya PO header.',
                    ]);
                }

                /** @var PurchaseOrderLine|null $poLine */
                $poLine = $poLines->get($poLineId);
                if (!$poLine) {
                    throw ValidationException::withMessages([
                        "lines.$i" => "PO line #{$poLineId} tidak ditemukan (mungkin sudah dihapus).",
                    ]);
                }

                if ((int) $poLine->purchase_order_id !== (int) $poId) {
                    throw ValidationException::withMessages([
                        "lines.$i" => "PO line #{$poLineId} bukan milik PO ini (berasal dari PO lain).",
                    ]);
                }

                $itemId = (int) ($row['item_id'] ?? 0);
                if ($itemId > 0 && $itemId !== (int) $poLine->item_id) {
                    throw ValidationException::withMessages([
                        "lines.$i" => 'Item baris GRN tidak sama dengan item pada PO line.',
                    ]);
                }
                // Paksa item mengikuti PO line (jangan percaya request).
                $row['item_id'] = (int) $poLine->item_id;

                $row['purchase_unit'] = $poLine->effectivePurchaseUnit();
                $row['stock_unit'] = $poLine->effectiveStockUnit();
                $row['conversion_factor'] = $poLine->effectiveConversionFactor();

                // Outstanding check
                $ordered = (float) $poLine->qty;
                $factor = max(0.000001, (float) $poLine->effectiveConversionFactor());
                $already = (float) ($alreadyByLine[$poLineId] ?? 0);
                $remaining = max(0.0, round(($ordered * $factor) - $already, 6));
                $req = array_key_exists('stock_qty_received', $row) || array_key_exists('stock_qty_reject', $row)
                    ? $this->num($row['stock_qty_received'] ?? 0) + $this->num($row['stock_qty_reject'] ?? 0)
                    : ($this->num($row['qty_received'] ?? 0) + $this->num($row['qty_reject'] ?? 0)) * $factor;
                if ($ordered > 0 && $req > $remaining + 0.0001) {
                    throw ValidationException::withMessages([
                        "lines.$i" => "Qty penerimaan melebihi sisa PO. Sisa stok: {$remaining}.",
                    ]);
                }

                // ✅ HARGA SERVER-SIDE: selalu ambil dari PO line, abaikan request.
                $row['unit_price'] = (float) $poLine->unit_price;
                $row['allocation'] = in_array($poLine->allocation, ['hpp', 'expense'], true)
                    ? $poLine->allocation
                    : (($poLine->item?->default_allocation ?? 'hpp') === 'expense' ? 'expense' : 'hpp');
                $row['expense_account_id'] = $row['allocation'] === 'expense'
                    ? ($poLine->expense_account_id ?: $poLine->item?->default_expense_account_id)
                    : null;
            } else {
                // Baris tanpa PO line: hanya boleh bila GRN memang tidak berbasis PO,
                // atau baris ad-hoc pada GRN ber-PO (diizinkan, harga apa adanya dari controller).
                if ($poId) {
                    // GRN punya PO header tetapi baris tak menyebut PO line → tetap izinkan
                    // sebagai baris ad-hoc, namun tidak ada enrichment PO.
                }
            }

            if (empty($row['purchase_unit']) || empty($row['stock_unit']) || empty($row['conversion_factor'])) {
                $item = Item::query()
                    ->select(['id', 'unit', 'stock_unit', 'purchase_unit', 'purchase_conversion_factor'])
                    ->find((int) ($row['item_id'] ?? 0));
                if ($item) {
                    $row['purchase_unit'] = $row['purchase_unit'] ?? $item->purchaseUnit();
                    $row['stock_unit'] = $row['stock_unit'] ?? $item->stockUnit();
                    $row['conversion_factor'] = (float) ($row['conversion_factor'] ?? $item->purchaseConversionFactor());
                }
            }
            $factor = (float) ($row['conversion_factor'] ?? 1);
            $factor = $factor > 0 ? $factor : 1;
            $row['stock_qty_received'] = array_key_exists('stock_qty_received', $row)
                ? round(max(0, $this->num($row['stock_qty_received'])), 6)
                : round($this->num($row['qty_received'] ?? 0) * $factor, 6);
            $row['stock_qty_reject'] = array_key_exists('stock_qty_reject', $row)
                ? round(max(0, $this->num($row['stock_qty_reject'])), 6)
                : round($this->num($row['qty_reject'] ?? 0) * $factor, 6);

            $out[] = $row;
        }

        return $out;
    }

    public function update(PurchaseReceipt $grn, array $payload): PurchaseReceipt
    {
        return DB::transaction(function () use ($grn, $payload) {
            $grn = PurchaseReceipt::query()
                ->whereKey($grn->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($grn->status !== 'draft') {
                throw new \RuntimeException("Goods Receipt sudah {$grn->status}, tidak bisa diubah.");
            }

            $linesData = $payload['lines'] ?? [];
            unset($payload['lines'], $payload['code']);

            $oldPurchaseOrderId = $grn->purchase_order_id ? (int) $grn->purchase_order_id : null;
            $targetPurchaseOrderId = !empty($payload['purchase_order_id'])
                ? (int) $payload['purchase_order_id']
                : null;

            // Jangan pindahkan atau melepas referensi PO dari GRN draft yang
            // sudah mengunci PO. Ini mencegah PO lama tertinggal dalam status
            // locked tanpa GRN aktif yang merujuk ke sana.
            if ($oldPurchaseOrderId && $oldPurchaseOrderId !== $targetPurchaseOrderId) {
                throw ValidationException::withMessages([
                    'purchase_order_id' => 'PO pada GRN draft tidak dapat diganti atau dikosongkan. Hapus draft ini lalu buat ulang dari PO yang benar.',
                ]);
            }

            $po = null;
            if ($targetPurchaseOrderId) {
                $po = PurchaseOrder::query()
                    ->whereKey($targetPurchaseOrderId)
                    ->lockForUpdate()
                    ->first();

                $this->assertPoReceivable(
                    $po,
                    (int) ($payload['supplier_id'] ?? 0),
                    $targetPurchaseOrderId
                );
            }

            // Gunakan enrichment yang sama dengan create agar edit tidak dapat
            // mengganti item, harga PO, unit, konversi, allocation, atau
            // outstanding qty melalui request mentah.
            $linesData = $this->validateAndEnrichLinesFromPo(
                is_array($linesData) ? $linesData : [],
                $po,
                $targetPurchaseOrderId,
                (int) $grn->id,
            );

            $allowedFields = [
                'date',
                'supplier_id',
                'warehouse_id',
                'purchase_order_id',
                'discount',
                'tax_percent',
                'shipping_cost',
                'notes',
                'surat_jalan_no',
            ];

            foreach ($allowedFields as $field) {
                if (!array_key_exists($field, $payload)) {
                    continue;
                }

                if (in_array($field, ['discount', 'tax_percent', 'shipping_cost'], true)) {
                    $grn->{$field} = $this->num($payload[$field]);
                } else {
                    $grn->{$field} = $payload[$field];
                }
            }

            $grn->save();

            $subtotalAll = $this->syncLines($grn, $linesData);

            // subtotal header = total semua line (jujur)
            $this->recalcTotals($grn, $subtotalAll);

            if ($po && !$po->isLocked()) {
                $this->purchaseOrders->lockForReceiving(
                    $po,
                    (int) $grn->id,
                    (int) ($grn->created_by ?? (auth()->id() ?? 0)) ?: null,
                    "Dikunci oleh GRN {$grn->code}."
                );
            }

            return $grn->fresh(['lines.item', 'supplier', 'warehouse']);
        });
    }

    /**
     * POST GRN → stock-in (HPP only) + jurnal (2 jurnal terpisah) + (opsional) apply DP.
     */
    public function post(PurchaseReceipt $grn): PurchaseReceipt
    {
        return DB::transaction(function () use ($grn) {
            // Kunci dokumen sebelum validasi dan mutasi stok. Tanpa ini dua
            // request POST bersamaan sama-sama melihat status draft dan dapat
            // menambah stok dua kali.
            $grn = PurchaseReceipt::query()
                ->whereKey($grn->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($grn->status !== 'draft') {
                throw new \RuntimeException("Goods Receipt tidak dalam status draft.");
            }
            if (!$grn->warehouse_id) {
                throw new \RuntimeException("Goods Receipt belum punya gudang tujuan.");
            }

            $grn->loadMissing(['lines.item', 'supplier']);

            if ($grn->lines->count() === 0) {
                throw ValidationException::withMessages(['grn' => 'GRN tidak punya line.']);
            }

            // Auto-fill harga dari PO line jika unit_price = 0
            // (terjadi ketika admin buat GRN — tidak bisa input harga)
            // Replacement receipt sudah membawa harga dari return line.
            // Jangan timpa dengan harga PO line asal, karena item pengganti
            // dapat berbeda dari item pada PO.
            if (!$grn->is_replacement) {
                $this->backfillPricesFromPoLines($grn);
            }
            $grn->refresh();
            $grn->loadMissing(['lines.item', 'supplier']);

            $grandTotal = (float) $grn->grand_total;
            if ($grandTotal <= 0) {
                throw ValidationException::withMessages(['grn' => 'Total GRN harus > 0. Pastikan harga sudah diisi pada PO.']);
            }

            // Deteksi harga mencurigakan: grand_total sangat kecil (< Rp 100)
            // kemungkinan harga di PO belum diisi dengan benar (contoh: Rp 1 dari data test).
            // Hard block di Rp 0 sudah ada di atas; ini soft warning di log + error agar user sadar.
            if ($grandTotal < 100) {
                Log::warning('[GRN] grand_total sangat kecil — kemungkinan harga PO belum diisi.', [
                    'grn_id'      => $grn->id,
                    'grn_code'    => $grn->code,
                    'grand_total' => $grandTotal,
                    'supplier'    => $grn->supplier?->name,
                ]);

                throw ValidationException::withMessages([
                    'grn' => "Total GRN terlalu kecil (Rp " . number_format($grandTotal, 0, ',', '.') . "). "
                           . "Pastikan harga sudah diisi dengan benar pada PO sebelum posting GRN.",
                ]);
            }

            // Map allocation + expense account (sumber dari PO line + master item)
            $maps = $this->buildLineMetaMapsForGrn($grn);

            // ==========================
            // 1) STOCK IN (LOT + moving average) — hanya HPP
            // ==========================
            foreach ($grn->lines as $line) {
                if ((float) $line->qty_received <= 0) {
                    continue;
                }

                $isHpp = $this->isLineEligibleForStock(
                    $line->purchase_order_line_id,
                    (int) $line->item_id,
                    $maps['eligibility'],
                    (int) $line->id,
                );

                if (!$isHpp) {
                    continue; // expense -> tidak masuk stok
                }

                // Pastikan LOT ada
                if ($line->lot_id) {
                    $lot = $line->lot ?? Lot::findOrFail($line->lot_id);
                } else {
                    $lot = Lot::create([
                        'code' => CodeGenerator::generate('LOT'),
                        'item_id' => (int) $line->item_id,
                        'initial_qty' => 0,
                        'initial_cost' => 0,
                        'qty_onhand' => 0,
                        'total_cost' => 0,
                        'avg_cost' => 0,
                        'status' => 'open',
                    ]);

                    $line->lot_id = $lot->id;
                    $line->save();
                }

                $this->inventory->stockIn(
                    warehouseId: $this->resolveStockWarehouseId($line, (int) $grn->warehouse_id),
                    itemId: (int) $line->item_id,
                    qty: $line->stockQtyReceived(),
                    date: $grn->date,
                    sourceType: 'purchase_receipt',
                    sourceId: (int) $grn->id,
                    notes: "GRN {$grn->code} line {$line->id}",
                    lotId: (int) $lot->id,
                    unitCost: $line->stockUnitPrice(),
                );

                // update last price + moving average HPP
                $this->touchLastPrices($grn, (int) $line->item_id, $line->stockUnitPrice(), $line->stockQtyReceived());
            }

            // ==========================
            // 2) SET STATUS POSTED
            // ==========================
            $grn->status = 'posted';
            $grn->posted_at = now();
            $grn->approved_by = auth()->id() ?: $grn->approved_by;
            $grn->save();

            // Sync received_status di PO terkait + pastikan PO terkunci (fallback
            // untuk GRN yang mungkin dibuat sebelum mekanisme lock aktif).
            if ($grn->purchase_order_id) {
                $po = PurchaseOrder::query()->whereKey((int) $grn->purchase_order_id)->lockForUpdate()->first();
                if ($po && !$po->isLocked()) {
                    $this->purchaseOrders->lockForReceiving(
                        $po,
                        (int) $grn->id,
                        (int) ($grn->created_by ?? (auth()->id() ?? 0)) ?: null,
                        "Dikunci saat posting GRN {$grn->code}."
                    );
                }
                // GRN replacement bukan penerimaan baru atas PO asal.
                if (!$grn->is_replacement) {
                    $this->syncReceivedStatus((int) $grn->purchase_order_id);
                }
            }

            // ==========================
            // 3) Resolve akun via CODE
            // ==========================
            $inventoryCode = (string) (config('accounting.inventory_account_code') ?: '1201');
            $apCode = (string) (config('accounting.ap_account_code') ?: '2101');
            
            if ($grn->is_replacement) {
                $apCode = JournalService::CODE_SUPPLIER_CLAIM;
            }

            $inventoryAccountId = (int) (Account::where('code', $inventoryCode)->value('id') ?? 0);
            $apAccountId = (int) (Account::where('code', $apCode)->value('id') ?? 0);

            if ($inventoryAccountId <= 0 || $apAccountId <= 0) {
                throw ValidationException::withMessages([
                    'grn' => "Akun tidak lengkap. Pastikan ada COA: Inventory {$inventoryCode} dan AP {$apCode}.",
                ]);
            }

            // item_role → inventory account code mapping
            // finished_good → 1203, wip → 1202, raw_material → 1201 (fallback)
            $itemRoleToInvCode = [
                'finished_good' => '1203',
                'wip'           => '1202',
                'raw_material'  => '1201',
                'production_supply' => '1201',
                'shipping_supply' => '1205',
            ];
            // Pre-load account ids by code (cache)
            $invCodeToId = [];
            foreach (array_unique(array_values($itemRoleToInvCode)) as $code) {
                $id = (int) (Account::where('code', $code)->value('id') ?? 0);
                if ($id > 0) {
                    $invCodeToId[$code] = $id;
                }
            }
            $invCodeToId[$inventoryCode] = $invCodeToId[$inventoryCode] ?? $inventoryAccountId;

            // akun tambahan
            $shippingExpenseCode = '6102'; // Biaya Transport/Ongkir
            $taxInputCode = '1401'; // PPN Masukan (optional)
            $purchaseExpenseFallbackCode = '6110'; // fallback biaya pembelian / pembelian expense

            $shippingExpenseId = (int) (Account::where('code', $shippingExpenseCode)->value('id') ?? 0);
            $taxInputId = (int) (Account::where('code', $taxInputCode)->value('id') ?? 0); // boleh 0
            $purchaseExpenseFallbackId = (int) (Account::where('code', $purchaseExpenseFallbackCode)->value('id') ?? 0); // boleh 0, tapi sebaiknya ada

            // ==========================
            // 4) Build split amounts (HPP vs Expense) + prorate discount
            // ==========================
            $discount = (float) $grn->discount;
            $taxAmount = (float) $grn->tax_amount;
            $shippingCost = (float) $grn->shipping_cost;

            $totals = $this->calculateSplitTotals($grn, $maps);

            $totalBeforeDiscount = $totals['hpp_total'] + $totals['expense_total'];

            // apply discount prorata
            $discount = min($discount, max(0, $totalBeforeDiscount));
            $hppAfterDiscount = $totals['hpp_total'];
            $expenseAfterDiscountByAcc = $totals['expense_by_account'];

            if ($discount > 0.0001 && $totalBeforeDiscount > 0.0001) {
                $hppShare = $totals['hpp_total'] / $totalBeforeDiscount;
                $hppDisc = round($discount * $hppShare, 2);
                $hppAfterDiscount = max(0, round($totals['hpp_total'] - $hppDisc, 2));

                $remainingDisc = round($discount - $hppDisc, 2);

                $expenseAfterDiscountByAcc = [];
                $expTotal = (float) $totals['expense_total'];

                if ($expTotal > 0.0001 && $remainingDisc > 0.0001) {
                    $running = 0.0;
                    $accIds = array_keys($totals['expense_by_account']);
                    $lastAccId = end($accIds);

                    foreach ($totals['expense_by_account'] as $accId => $amt) {
                        $amt = (float) $amt;
                        $share = $amt / $expTotal;
                        $accDisc = round($remainingDisc * $share, 2);

                        if ((int) $accId === (int) $lastAccId) {
                            $accDisc = round($remainingDisc - $running, 2);
                        } else {
                            $running = round($running + $accDisc, 2);
                        }

                        $expenseAfterDiscountByAcc[$accId] = max(0, round($amt - $accDisc, 2));
                    }
                } else {
                    $expenseAfterDiscountByAcc = $totals['expense_by_account'];
                }
            }

            // ==========================
            // 5) POST JOURNAL GRN (2 JURNAL TERPISAH)
            // ==========================
            $invDebit = round((float) $hppAfterDiscount, 2);

            // total expense debit = sum expense_by_acc + tax + shipping
            $expDebit = 0.0;
            foreach ($expenseAfterDiscountByAcc as $accId => $amt) {
                $amt = (float) $amt;
                if ($amt <= 0.0001) {
                    continue;
                }

                $expDebit = round($expDebit + $amt, 2);
            }
            if ($taxAmount > 0.0001) {
                $expDebit = round($expDebit + (float) $taxAmount, 2);
            }
            if ($shippingCost > 0.0001) {
                $expDebit = round($expDebit + (float) $shippingCost, 2);
            }

            $expCreditAp = round($grandTotal - $invDebit, 2);

            if ($expCreditAp < -0.01) {
                throw ValidationException::withMessages([
                    'grn' => 'Split jurnal GRN invalid (sisa AP negatif). Cek mapping allocation/diskon/total.',
                ]);
            }

            // (A) JURNAL INVENTORY: Dr Persediaan (per item_role) / Cr AP
            if ($invDebit > 0.0001) {
                // Split debit per item_role → account, prorated from hppAfterDiscount
                $hppByRole   = $totals['hpp_by_item_role'] ?? [];
                $hppOriginal = $totals['hpp_total'];
                $invLines    = [];
                $invRunning  = 0.0;
                $roles       = array_keys($hppByRole);
                $lastRole    = end($roles) ?: null;

                foreach ($hppByRole as $role => $roleAmt) {
                    $roleCode = $itemRoleToInvCode[$role] ?? $inventoryCode;
                    $roleAccId = $invCodeToId[$roleCode] ?? $inventoryAccountId;

                    // Prorate: roleAmt / hppOriginal * invDebit
                    if ($role === $lastRole) {
                        $roleDebit = round($invDebit - $invRunning, 2);
                    } else {
                        $roleDebit = $hppOriginal > 0
                            ? round(($roleAmt / $hppOriginal) * $invDebit, 2)
                            : 0.0;
                    }

                    if ($roleDebit > 0.0001) {
                        // Merge if same account
                        $found = false;
                        foreach ($invLines as &$il) {
                            if ($il['account_id'] === $roleAccId) {
                                $il['debit'] = round($il['debit'] + $roleDebit, 2);
                                $found = true;
                                break;
                            }
                        }
                        unset($il);
                        if (!$found) {
                            $invLines[] = ['account_id' => $roleAccId, 'debit' => $roleDebit, 'credit' => 0];
                        }
                        $invRunning = round($invRunning + $roleDebit, 2);
                    }
                }

                // Fallback: jika hpp_by_item_role kosong, pakai single account
                if (empty($invLines)) {
                    $invLines[] = ['account_id' => $inventoryAccountId, 'debit' => $invDebit, 'credit' => 0];
                }

                $invLines[] = ['account_id' => $apAccountId, 'debit' => 0, 'credit' => $invDebit];

                $invJournal = $this->journal->post(
                    date: is_string($grn->date) ? $grn->date : $grn->date->format('Y-m-d'),
                    sourceType: 'grn_inv',
                    sourceId: (int) $grn->id,
                    description: "GRN {$grn->code} - Inventory - {$grn->supplier?->name}",
                    lines: $invLines
                );

                // Simpan journal_id ke GRN agar mudah di-trace
                if ($invJournal && empty($grn->journal_id)) {
                    $grn->journal_id = (int) $invJournal->id;
                    $grn->save();
                }
            }

            // (B) JURNAL EXPENSE: Dr Expense/Tax/Shipping / Cr AP (sisa)
            if ($expDebit > 0.0001 || $expCreditAp > 0.0001) {
                $expLines = [];

                foreach ($expenseAfterDiscountByAcc as $accId => $amt) {
                    $accId = (int) $accId;
                    $amt = (float) $amt;
                    if ($amt <= 0.0001) {
                        continue;
                    }

                    if ($accId <= 0) {
                        throw ValidationException::withMessages([
                            'grn' => 'Expense line belum memiliki akun biaya. Lengkapi akun biaya pada Master Item atau baris PO sebelum posting GRN.',
                        ]);
                    }

                    $expLines[] = ['account_id' => $accId, 'debit' => round($amt, 2), 'credit' => 0];
                }

                // Tax input -> expense journal
                if ($taxAmount > 0.0001) {
                    $expLines[] = [
                        'account_id' => ($taxInputId > 0 ? $taxInputId : ($purchaseExpenseFallbackId ?: $inventoryAccountId)),
                        'debit' => round((float) $taxAmount, 2),
                        'credit' => 0,
                    ];
                }

                // Shipping -> expense journal
                if ($shippingCost > 0.0001) {
                    if ($shippingExpenseId <= 0) {
                        throw ValidationException::withMessages([
                            'grn' => "Akun ongkir belum ada. Pastikan COA {$shippingExpenseCode} (Biaya Transport/Ongkir).",
                        ]);
                    }
                    $expLines[] = ['account_id' => $shippingExpenseId, 'debit' => round((float) $shippingCost, 2), 'credit' => 0];
                }

                if ($expCreditAp > 0.0001) {
                    $expLines[] = ['account_id' => $apAccountId, 'debit' => 0, 'credit' => $expCreditAp];
                }

                if (count($expLines) >= 2) {
                    $this->journal->post(
                        date: is_string($grn->date) ? $grn->date : $grn->date->format('Y-m-d'),
                        sourceType: 'grn_exp',
                        sourceId: (int) $grn->id,
                        description: "GRN {$grn->code} - Expense - {$grn->supplier?->name}",
                        lines: $expLines
                    );
                }
            }

            if ($grn->is_replacement && $grn->purchase_return_id) {
                $returnOrigin = \App\Models\PurchaseReturn::find($grn->purchase_return_id);
                if ($returnOrigin) {
                    $this->syncReplacementProgress($returnOrigin);
                }
            }

            return $grn->fresh(['lines.item', 'supplier', 'warehouse']);
        }, 3);
    }

    /**
     * FG dari GRN langsung ditempatkan di WH-RTS.
     * Item selain FG tetap mengikuti gudang tujuan pada GRN.
     */
    protected function resolveStockWarehouseId(PurchaseReceiptLine $line, int $defaultWarehouseId): int
    {
        if (!$line->item?->isFinishedGood()) {
            return $defaultWarehouseId;
        }

        $rtsWarehouseId = (int) Warehouse::query()
            ->where('code', 'WH-RTS')
            ->value('id');

        if ($rtsWarehouseId <= 0) {
            throw ValidationException::withMessages([
                'grn' => 'Gudang WH-RTS belum tersedia. Buat/aktifkan gudang WH-RTS sebelum posting GRN FG.',
            ]);
        }

        return $rtsWarehouseId;
    }

    /**
     * UNPOST GRN → reverse stock (hpp only) + void journals.
     */
    public function unpost(PurchaseReceipt $grn): PurchaseReceipt
    {
        return DB::transaction(function () use ($grn) {
            $grn = PurchaseReceipt::query()
                ->whereKey($grn->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($grn->status !== 'posted') {
                throw new \RuntimeException("Hanya GRN yang sudah posted yang bisa di-unpost.");
            }

            // ✅ BLOCK kalau ada payment aktif di PO terkait
            if ($this->hasActivePaymentsForOrder($grn->purchase_order_id)) {
                throw ValidationException::withMessages([
                    'grn' => 'Tidak bisa UNPOST karena sudah ada Payment/DP aktif pada PO ini. Void payment dulu, baru unpost.',
                ]);
            }

            if (!$grn->warehouse_id) {
                throw new \RuntimeException("Goods Receipt tidak punya gudang.");
            }

            $grn->loadMissing(['lines', 'supplier']);

            // ==========================
            // 1) REVERSE STOCK (HPP lines saja)
            // ==========================
            $hasStockMutation = DB::table('inventory_mutations')
                ->where('source_type', 'purchase_receipt')
                ->where('source_id', (int) $grn->id)
                ->exists();

            if ($hasStockMutation) {
                $this->inventory->reverseBySource(
                    originalSourceTypes: ['purchase_receipt'],
                    originalSourceId: (int) $grn->id,
                    voidSourceType: 'purchase_receipt_void',
                    voidSourceId: (int) $grn->id,
                    notesPrefix: "UNPOST GRN {$grn->code}",
                    date: $grn->date,
                );
            }

            // ==========================
            // 2) VOID 2 JURNAL TERPISAH
            // ==========================
            $this->journal->voidBySource('grn_inv', (int) $grn->id);
            $this->journal->voidBySource('grn_exp', (int) $grn->id);

            // (kalau kamu punya jurnal apply dp di GRN, void di sini juga)
            // $this->journal->voidBySource('grn_apply_dp', (int) $grn->id);

            $grn->status = 'draft';
            $grn->posted_at = null;
            $grn->approved_by = null;
            $grn->save();

            // Sync received_status di PO terkait
            if ($grn->purchase_order_id && !$grn->is_replacement) {
                $this->syncReceivedStatus((int) $grn->purchase_order_id);
            }

            // Jika Replacement Receipt di-unpost, kita perlu merekonsiliasi ulang replacement_status pada Return origin
            if ($grn->is_replacement && $grn->purchase_return_id) {
                $returnOrigin = \App\Models\PurchaseReturn::find($grn->purchase_return_id);
                if ($returnOrigin) {
                    $this->syncReplacementProgress($returnOrigin);
                }
            }

            // ==========================
            // 3) RECALC MOVING AVERAGE HPP (hapus kontribusi GRN ini)
            // ==========================
            $affectedItemIds = $grn->lines
                ->pluck('item_id')
                ->filter()
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            foreach ($affectedItemIds as $itemId) {
                $this->recomputeHppFromHistory($itemId, excludeGrnId: (int) $grn->id);
                $this->recomputeSupplierLastPrice(
                    (int) $grn->supplier_id,
                    $itemId,
                    excludeGrnId: (int) $grn->id,
                );
            }

            return $grn->fresh(['lines.item', 'supplier', 'warehouse']);
        }, 3);
    }

    public function recalculate(PurchaseReceipt $grn): PurchaseReceipt
    {
        return DB::transaction(function () use ($grn) {
            $subtotal = (float) $grn->lines()
                ->with('item')
                ->get()
                ->sum(fn (PurchaseReceiptLine $line) => $line->calculatedLineTotal());
            $this->recalcTotals($grn, $subtotal);
            return $grn->fresh(['lines.item', 'supplier', 'warehouse']);
        });
    }

    // =====================================================================
    // INTERNAL HELPERS
    // =====================================================================

    /**
     * syncLines FULL: simpan semua line (hpp + expense).
     * subtotal yang dikembalikan = total semua line_total (jujur).
     */
    protected function syncLines(PurchaseReceipt $grn, array $linesData): float
    {
        $grn->lines()->delete();

        $subtotal = 0.0;
        $hasAllocation = Schema::hasColumn('purchase_receipt_lines', 'allocation');
        $hasExpenseAccount = Schema::hasColumn('purchase_receipt_lines', 'expense_account_id');

        foreach ($linesData as $row) {
            $itemId = $row['item_id'] ?? null;
            $itemId = ($itemId === null || $itemId === '') ? 0 : (int) $itemId;

            $qtyReceived = $this->num($row['qty_received'] ?? 0);
            $qtyReject = $this->num($row['qty_reject'] ?? 0);

            $unitPrice = $this->num($row['unit_price'] ?? 0);
            $unit = $row['unit'] ?? null;
            $item = Item::query()
                ->select(['id', 'unit', 'stock_unit', 'purchase_unit', 'purchase_conversion_factor', 'default_allocation', 'default_expense_account_id'])
                ->find($itemId);
            $purchaseUnit = trim((string) ($row['purchase_unit'] ?? $unit ?? $item?->purchaseUnit() ?? 'pcs'));
            $stockUnit = trim((string) ($row['stock_unit'] ?? $item?->stockUnit() ?? 'pcs'));
            $conversionFactor = $this->num($row['conversion_factor'] ?? $item?->purchaseConversionFactor() ?? 1);
            $conversionFactor = $conversionFactor > 0 ? $conversionFactor : 1;
            $stockQtyReceived = array_key_exists('stock_qty_received', $row)
                ? round(max(0, $this->num($row['stock_qty_received'])), 6)
                : round($qtyReceived * $conversionFactor, 6);
            $stockQtyReject = array_key_exists('stock_qty_reject', $row)
                ? round(max(0, $this->num($row['stock_qty_reject'])), 6)
                : round($qtyReject * $conversionFactor, 6);
            $notes = $row['notes'] ?? null;
            $lotId = $row['lot_id'] ?? null;

            $poLineId = $row['purchase_order_line_id'] ?? null;
            $poLineId = ($poLineId === null || $poLineId === '') ? null : (int) $poLineId;

            if ($itemId <= 0 || ($qtyReceived <= 0 && $qtyReject <= 0)) {
                continue;
            }

            $stockUnitPrice = $unitPrice / max(0.000001, $conversionFactor);
            $lineTotal = round($stockQtyReceived * $stockUnitPrice, 2);

            $allocation = (($row['allocation'] ?? $item?->default_allocation ?? 'hpp') === 'expense')
                ? 'expense'
                : 'hpp';
            $expenseAccountId = $allocation === 'expense'
                ? (int) ($row['expense_account_id'] ?? $item?->default_expense_account_id ?? 0)
                : null;

            if ($allocation === 'expense' && $expenseAccountId > 0) {
                $this->assertExpenseAccount($expenseAccountId);
            }

            $linePayload = [
                'purchase_receipt_id' => $grn->id,
                'purchase_order_line_id' => $poLineId,
                'item_id' => $itemId,
                'lot_id' => $lotId,
                'qty_received' => $qtyReceived,
                'qty_reject' => $qtyReject,
                'stock_qty_received' => $stockQtyReceived,
                'stock_qty_reject' => $stockQtyReject,
                'unit' => $purchaseUnit,
                'purchase_unit' => $purchaseUnit,
                'stock_unit' => $stockUnit,
                'conversion_factor' => $conversionFactor,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'notes' => $notes,
            ];
            if ($hasAllocation) {
                $linePayload['allocation'] = $allocation;
            }
            if ($hasExpenseAccount) {
                $linePayload['expense_account_id'] = $allocation === 'expense'
                    ? ($expenseAccountId ?: null)
                    : null;
            }

            PurchaseReceiptLine::create($linePayload);

            $subtotal += $lineTotal;
        }

        return round($subtotal, 2);
    }

    protected function recalcTotals(PurchaseReceipt $grn, float $subtotal): void
    {
        $discount = $this->num($grn->discount);
        $taxPercent = $this->num($grn->tax_percent);
        $shippingCost = $this->num($grn->shipping_cost);

        $base = max(0, $subtotal - $discount);
        $taxAmount = round($base * $taxPercent / 100, 2);
        $grand = $base + $taxAmount + $shippingCost;

        $grn->subtotal = round($subtotal, 2);
        $grn->tax_amount = $taxAmount;
        $grn->grand_total = round($grand, 2);
        $grn->save();
    }

    protected function touchLastPrices(PurchaseReceipt $grn, int $itemId, float $unitPrice, float $qtyReceived = 0): void
    {
        $unitPrice = round($unitPrice, 2);

        $item = Item::find($itemId);
        if (!$item) {
            return;
        }

        // ============================================================
        // MOVING AVERAGE HPP
        // Formula: new_avg = (old_qty × old_hpp + qty_beli × harga_beli) / (old_qty + qty_beli)
        // stockIn sudah dijalankan sebelum method ini, jadi total qty di DB
        // sudah termasuk pembelian baru → old_qty = total_sekarang - qty_beli
        // ============================================================
        if ($qtyReceived > 0 && $unitPrice > 0) {
            $totalQtyAfter = (float) InventoryStock::where('item_id', $itemId)->sum('qty');
            $oldQty        = max(0.0, $totalQtyAfter - $qtyReceived);
            $oldHpp        = (float) ($item->hpp ?? 0);

            if ($totalQtyAfter > 0) {
                $newHpp = ($oldQty * $oldHpp + $qtyReceived * $unitPrice) / $totalQtyAfter;
            } else {
                $newHpp = $unitPrice;
            }

            $item->hpp = round($newHpp, 2);
        }

        $item->last_purchase_price = $unitPrice;
        $item->save();

        if (isset($newHpp)) {
            // Nonaktifkan snapshot aktif lama
            ItemCostSnapshot::where('item_id', $itemId)->active()->update(['is_active' => 0]);
            
            // Simpan riwayat perubahan
            ItemCostSnapshot::create([
                'item_id' => $itemId,
                'warehouse_id' => null,
                'snapshot_date' => \Illuminate\Support\Carbon::now()->toDateString(),
                'reference_type' => 'purchase_receipt',
                'reference_id' => $grn->id,
                'qty_basis' => $totalQtyAfter,
                'rm_unit_cost' => round($newHpp, 2),
                'cutting_unit_cost' => 0,
                'sewing_unit_cost' => 0,
                'finishing_unit_cost' => 0,
                'packaging_unit_cost' => 0,
                'overhead_unit_cost' => 0,
                'unit_cost' => round($newHpp, 2),
                'notes' => "Auto-calculated dari GRN {$grn->code}",
                'is_active' => 1,
                'created_by' => auth()->id() ?? null,
            ]);
        }

        SupplierPrice::updateOrCreate(
            ['supplier_id' => $grn->supplier_id, 'item_id' => $itemId],
            ['last_price' => $unitPrice]
        );
    }

    /**
     * Recalculate moving average HPP dari seluruh riwayat GRN posted,
     * opsional exclude satu GRN (dipakai saat unpost).
     *
     * Cara: replay semua GRN stockIn per item secara kronologis,
     * hitung running average → tulis ke items.hpp.
     */
    protected function recomputeHppFromHistory(int $itemId, ?int $excludeGrnId = null): void
    {
        // Ambil hanya line yang benar-benar masuk stok. Line expense tetap
        // tercatat di GRN/jurnal, tetapi tidak boleh ikut moving average HPP.
        $query = DB::table('purchase_receipt_lines as prl')
            ->join('purchase_receipts as pr', 'pr.id', '=', 'prl.purchase_receipt_id')
            ->leftJoin('purchase_order_lines as pol', 'pol.id', '=', 'prl.purchase_order_line_id')
            ->leftJoin('items as it', 'it.id', '=', 'prl.item_id')
            ->where('pr.status', 'posted')
            ->where('prl.item_id', $itemId)
            ->whereRaw('CAST(prl.qty_received AS REAL) > 0')
            ->whereRaw('CAST(prl.unit_price AS REAL) > 0')
            ->where(function ($q) {
                $q->where('pr.is_replacement', true)
                    ->orWhereRaw("COALESCE(prl.allocation, pol.allocation, it.default_allocation, 'hpp') <> 'expense'");
            });

        if ($excludeGrnId) {
            $query->where('pr.id', '!=', $excludeGrnId);
        }

        $lines = $query
            ->orderBy('pr.date')
            ->orderBy('pr.id')
            ->select('prl.qty_received', 'prl.unit_price', 'prl.stock_qty_received', 'prl.conversion_factor')
            ->get();

        if ($lines->isEmpty()) {
            // Tidak ada riwayat beli → reset ke 0
            Item::where('id', $itemId)->update(['hpp' => 0]);
            ItemCostSnapshot::where('item_id', $itemId)->active()->update(['is_active' => 0]);
            return;
        }

        // Replay moving average
        $runningQty = 0.0;
        $runningHpp = 0.0;

        foreach ($lines as $line) {
            $factor = max(0.000001, (float) ($line->conversion_factor ?: 1));
            $qty   = (float) ($line->stock_qty_received ?? $line->qty_received);
            $price = (float) $line->unit_price / $factor;

            $newQty = $runningQty + $qty;
            $runningHpp = $newQty > 0
                ? ($runningQty * $runningHpp + $qty * $price) / $newQty
                : $price;
            $runningQty = $newQty;
        }

        $runningHppRound = round($runningHpp, 2);
        Item::where('id', $itemId)->update(['hpp' => $runningHppRound]);
        
        // Catat sebagai snapshot koreksi dari Unpost
        ItemCostSnapshot::where('item_id', $itemId)->active()->update(['is_active' => 0]);
        ItemCostSnapshot::create([
            'item_id' => $itemId,
            'warehouse_id' => null,
            'snapshot_date' => \Illuminate\Support\Carbon::now()->toDateString(),
            'reference_type' => 'recalculation',
            'qty_basis' => $runningQty,
            'rm_unit_cost' => $runningHppRound,
            'cutting_unit_cost' => 0,
            'sewing_unit_cost' => 0,
            'finishing_unit_cost' => 0,
            'packaging_unit_cost' => 0,
            'overhead_unit_cost' => 0,
            'unit_cost' => $runningHppRound,
            'notes' => 'Recalculated HPP (UNPOST GRN)',
            'is_active' => 1,
            'created_by' => auth()->id() ?? null,
        ]);
    }

    /**
     * Kembalikan harga supplier ke GRN posted terakhir setelah unpost.
     * Harga dari GRN yang sudah di-unpost tidak boleh menjadi auto-suggest.
     */
    protected function recomputeSupplierLastPrice(int $supplierId, int $itemId, ?int $excludeGrnId = null): void
    {
        $query = DB::table('purchase_receipt_lines as prl')
            ->join('purchase_receipts as pr', 'pr.id', '=', 'prl.purchase_receipt_id')
            ->where('pr.supplier_id', $supplierId)
            ->where('prl.item_id', $itemId)
            ->where('pr.status', 'posted')
            ->whereRaw('CAST(prl.unit_price AS REAL) > 0')
            ->orderByDesc('pr.date')
            ->orderByDesc('pr.id');

        if ($excludeGrnId) {
            $query->where('pr.id', '!=', $excludeGrnId);
        }

        $lastPrice = (float) ($query->value('prl.unit_price') ?? 0);

        SupplierPrice::updateOrCreate(
            ['supplier_id' => $supplierId, 'item_id' => $itemId],
            ['last_price' => round(max(0, $lastPrice), 2)]
        );
    }

    /**
     * Build meta maps:
     * - eligibility: allocation from PO line > item default > fallback hpp
     * - expense_account_id: from PO line if exists, else 0
     */
    protected function buildLineMetaMapsForGrn(PurchaseReceipt $grn): array
    {
        $eligibility = $this->buildEligibilityMapsForGrnLines($grn);

        $hasLineExpenseAcc = Schema::hasColumn('purchase_order_lines', 'expense_account_id');
        $expenseAccByPoLineId = collect();
        $expenseAccByReceiptLineId = collect();

        if ($hasLineExpenseAcc) {
            $poLineIds = $grn->lines
                ->pluck('purchase_order_line_id')
                ->filter()
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            if (!empty($poLineIds)) {
                $expenseAccByPoLineId = DB::table('purchase_order_lines')
                    ->whereIn('id', $poLineIds)
                    ->pluck('expense_account_id', 'id');
            }
        }

        if (Schema::hasColumn('purchase_receipt_lines', 'expense_account_id')) {
            $expenseAccByReceiptLineId = $grn->lines
                ->pluck('expense_account_id', 'id');
        }

        return [
            'eligibility' => $eligibility,
            'expenseAccByPoLineId' => $expenseAccByPoLineId,
            'expenseAccByReceiptLineId' => $expenseAccByReceiptLineId,
        ];
    }

    protected function calculateSplitTotals(PurchaseReceipt $grn, array $maps): array
    {
        $elig = $maps['eligibility'] ?? ['poAllocByLineId' => collect(), 'itemAllocById' => collect()];
        $expenseAccByPoLineId = $maps['expenseAccByPoLineId'] ?? collect();
        $expenseAccByReceiptLineId = $maps['expenseAccByReceiptLineId'] ?? collect();

        $hppTotal = 0.0;
        $hppByItemRole = []; // item_role → total hpp amount
        $expenseByAcc = [];

        foreach ($grn->lines as $line) {
            $amt = (float) ($line->line_total ?? 0);
            if ($amt <= 0.0001) {
                continue;
            }

            // Kalau GRN ini replacement, anggap selalu masuk stok (HPP) sesuai barang pengganti
            if ($grn->is_replacement) {
                $isHpp = true;
            } else {
                $isHpp = $this->isLineEligibleForStock($line->purchase_order_line_id, (int) $line->item_id, $elig, (int) $line->id);
            }

            if ($isHpp) {
                $hppTotal = round($hppTotal + $amt, 2);
                // Track by item_role for account split
                $role = (string) ($line->item?->item_role ?? 'raw_material');
                $hppByItemRole[$role] = round((float) ($hppByItemRole[$role] ?? 0) + $amt, 2);
                continue;
            }

            $accId = 0;
            $poLineId = $line->purchase_order_line_id ? (int) $line->purchase_order_line_id : 0;

            if ($poLineId > 0) {
                $accId = (int) ($expenseAccByPoLineId[$poLineId] ?? 0);
            }

            if ($accId <= 0 && $line->id) {
                $accId = (int) ($expenseAccByReceiptLineId[(int) $line->id] ?? 0);
            }

            if ($accId <= 0) {
                $accId = (int) ($line->item?->default_expense_account_id ?? 0);
            }

            $expenseByAcc[$accId] = round((float) ($expenseByAcc[$accId] ?? 0) + $amt, 2);
        }

        $expenseTotal = 0.0;
        foreach ($expenseByAcc as $accId => $amt) {
            $expenseTotal = round($expenseTotal + (float) $amt, 2);
        }

        return [
            'hpp_total' => $hppTotal,
            'hpp_by_item_role' => $hppByItemRole,
            'expense_total' => $expenseTotal,
            'expense_by_account' => $expenseByAcc,
        ];
    }

    /**
     * Sinkronisasi ulang (Rekonsiliasi) status replacement berdasarkan aggregate GRN pengganti yang posted.
     */
    public function syncReplacementProgress(\App\Models\PurchaseReturn $purchaseReturn): void
    {
        // 1. Lock the return and its lines
        $lockedReturn = \App\Models\PurchaseReturn::query()
            ->whereKey($purchaseReturn->id)
            ->lockForUpdate()
            ->first();

        if (!$lockedReturn) return;
        
        $lockedReturnLines = $lockedReturn->lines()->lockForUpdate()->get();

        // 2. Fetch all POSTED replacement receipts for this return
        // Note: we don't count 'draft' or 'void'
        $postedReceipts = \App\Models\PurchaseReceipt::query()
            ->with('lines')
            ->where('purchase_return_id', $lockedReturn->id)
            ->where('is_replacement', true)
            ->where('status', 'posted')
            ->get();

        // 3. Aggregate qty per return line ID
        $receivedByReturnLine = [];
        foreach ($postedReceipts as $rec) {
            foreach ($rec->lines as $line) {
                if ($line->purchase_return_line_id && $line->qty_received > 0) {
                    $receivedByReturnLine[$line->purchase_return_line_id] = 
                        ($receivedByReturnLine[$line->purchase_return_line_id] ?? 0.0) + (float) $line->qty_received;
                }
            }
        }

        // 4. Update return lines
        $isCompleted = true;
        $totalReceived = 0.0;

        foreach ($lockedReturnLines as $rLine) {
            $newReceived = $receivedByReturnLine[$rLine->id] ?? 0.0;
            
            // Check over-replacement
            if (round($newReceived, 4) > round((float) $rLine->replacement_qty_expected, 4) + 0.0001) {
                throw ValidationException::withMessages([
                    'replacement' => "Fatal Error: Rekonsiliasi menemukan qty replacement ({$newReceived}) melebihi expected ({$rLine->replacement_qty_expected}) pada line return {$rLine->id}.",
                ]);
            }

            $rLine->replacement_qty_received = $newReceived;
            $rLine->save();

            $totalReceived += $newReceived;

            if (round($newReceived, 4) < round((float) $rLine->replacement_qty_expected, 4)) {
                $isCompleted = false;
            }
        }

        // 5. Update header status deterministically
        if ($isCompleted && $lockedReturnLines->count() > 0) {
            $lockedReturn->replacement_status = 'received';
            $lockedReturn->replacement_received_at = now();
        } else {
            if ($totalReceived <= 0.0001) {
                $lockedReturn->replacement_status = 'pending';
            } else {
                $lockedReturn->replacement_status = 'partial';
            }
            $lockedReturn->replacement_received_at = null;
        }

        $lockedReturn->save();
    }


    protected function buildEligibilityMapsForGrnLines(PurchaseReceipt $grn): array
    {
        $hasPoLineAlloc = Schema::hasColumn('purchase_order_lines', 'allocation');
        $hasItemDefaultAlloc = Schema::hasColumn('items', 'default_allocation');

        $poAllocByLineId = collect();
        $itemAllocById = collect();
        $receiptAllocByLineId = collect();

        if (Schema::hasColumn('purchase_receipt_lines', 'allocation')) {
            $receiptAllocByLineId = $grn->lines->pluck('allocation', 'id');
        }

        if ($hasPoLineAlloc) {
            $poLineIds = $grn->lines
                ->pluck('purchase_order_line_id')
                ->filter()
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            if (!empty($poLineIds)) {
                $poAllocByLineId = DB::table('purchase_order_lines')
                    ->whereIn('id', $poLineIds)
                    ->pluck('allocation', 'id');
            }
        }

        if ($hasItemDefaultAlloc) {
            $itemIds = $grn->lines
                ->pluck('item_id')
                ->filter()
                ->map(fn($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            if (!empty($itemIds)) {
                $itemAllocById = Item::query()
                    ->whereIn('id', $itemIds)
                    ->pluck('default_allocation', 'id');
            }
        }

        return [
            'poAllocByLineId' => $poAllocByLineId,
            'itemAllocById' => $itemAllocById,
            'receiptAllocByLineId' => $receiptAllocByLineId,
        ];
    }

    protected function isLineEligibleForStock($purchaseOrderLineId, int $itemId, array $maps, ?int $receiptLineId = null): bool
    {
        $poAllocByLineId = $maps['poAllocByLineId'] ?? collect();
        $itemAllocById = $maps['itemAllocById'] ?? collect();
        $receiptAllocByLineId = $maps['receiptAllocByLineId'] ?? collect();

        if ($receiptLineId && $receiptAllocByLineId->has($receiptLineId)) {
            $receiptAllocation = (string) ($receiptAllocByLineId[$receiptLineId] ?? '');

            if (in_array($receiptAllocation, ['hpp', 'expense'], true)) {
                return $receiptAllocation !== 'expense';
            }
        }

        $poLineId = ($purchaseOrderLineId === null || $purchaseOrderLineId === '') ? null : (int) $purchaseOrderLineId;

        return $this->isEligibleFromMaps($poLineId, $itemId, $poAllocByLineId, $itemAllocById);
    }

    protected function assertExpenseAccount(int $accountId): void
    {
        $valid = Account::query()
            ->whereKey($accountId)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->exists();

        if (!$valid) {
            throw ValidationException::withMessages([
                'grn' => "Akun biaya #{$accountId} tidak aktif atau bukan akun expense.",
            ]);
        }
    }

    protected function isEligibleFromMaps(?int $poLineId, int $itemId, $poAllocByLineId, $itemAllocById): bool
    {
        if ($poLineId) {
            $alloc = (string) ($poAllocByLineId[$poLineId] ?? 'hpp');
            return $alloc !== 'expense';
        }

        $alloc = (string) ($itemAllocById[$itemId] ?? 'hpp');
        return $alloc !== 'expense';
    }

    protected function num($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);

        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
            return (float) $value;
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
            return (float) $value;
        }

        return (float) $value;
    }

    protected function hasActivePaymentsForOrder(?int $purchaseOrderId): bool
    {
        if (!$purchaseOrderId) {
            return false;
        }

        return DB::table('purchase_payments')
            ->where('purchase_order_id', $purchaseOrderId)
            ->whereNull('voided_at')
            ->exists();
    }

    /**
     * Hitung dan update received_status di purchase_orders berdasarkan
     * seluruh GRN yang sudah posted.
     *
     * Logika:
     * - Tidak ada GRN posted        → not_received
     * - Ada GRN posted tapi qty kurang → partial
     * - Semua PO line sudah terpenuhi  → fully_received
     *
     * Catatan: hanya jalan jika kolom received_status sudah ada (safe guard).
     */
    public function syncReceivedStatus(int $purchaseOrderId): void
    {
        if (!Schema::hasColumn('purchase_orders', 'received_status')) {
            return; // migration belum dijalankan, skip
        }

        // Ambil semua PO lines
        $poLines = DB::table('purchase_order_lines')
            ->where('purchase_order_id', $purchaseOrderId)
            ->select('id', 'qty')
            ->get();

        if ($poLines->isEmpty()) {
            DB::table('purchase_orders')->where('id', $purchaseOrderId)
                ->update(['received_status' => 'not_received', 'updated_at' => now()]);
            return;
        }

        $poLineIds = $poLines->pluck('id')->all();

        // Total qty yang sudah diproses per PO line (diterima + reject).
        // Reject juga menghabiskan qty PO; replacement diproses sebagai GRN
        // terpisah dan tidak mengonsumsi kuota PO asal.
        $receivedByLine = DB::table('purchase_receipt_lines as prl')
            ->join('purchase_receipts as pr', 'pr.id', '=', 'prl.purchase_receipt_id')
            ->where('pr.purchase_order_id', $purchaseOrderId)
            ->where('pr.status', 'posted')
            ->where(function ($q) {
                $q->whereNull('pr.is_replacement')
                    ->orWhere('pr.is_replacement', false);
            })
            ->whereIn('prl.purchase_order_line_id', $poLineIds)
            ->selectRaw('prl.purchase_order_line_id, SUM(COALESCE(prl.qty_received, 0) + COALESCE(prl.qty_reject, 0)) as total_received')
            ->groupBy('prl.purchase_order_line_id')
            ->pluck('total_received', 'purchase_order_line_id');

        $totalLines = $poLines->count();
        $fullyReceivedCount = 0;
        $anyAccounted = false;

        foreach ($poLines as $line) {
            $received = (float) ($receivedByLine[$line->id] ?? 0);
            $ordered  = (float) $line->qty;

            if ($received > 0) {
                $anyAccounted = true;
            }

            if ($ordered > 0 && $received >= $ordered) {
                $fullyReceivedCount++;
            }
        }

        if ($fullyReceivedCount >= $totalLines) {
            $status = 'fully_received';
        } elseif ($anyAccounted) {
            $status = 'partial';
        } else {
            $status = 'not_received';
        }

        DB::table('purchase_orders')->where('id', $purchaseOrderId)
            ->update(['received_status' => $status, 'updated_at' => now()]);

        // Auto Close / Re-open evaluation
        $po = \App\Models\PurchaseOrder::find($purchaseOrderId);
        if ($po) {
            $po->evaluateAutoClose();
        }
    }

    /**
     * Backfill unit_price dari PO line jika GRN line masih 0.
     * Dipanggil saat post() — agar admin yang tidak input harga tetap bisa posting
     * dengan harga yang diambil dari PO (yang diisi owner).
     */
    protected function backfillPricesFromPoLines(PurchaseReceipt $grn): void
    {
        $lines = $grn->lines ?? collect();
        if ($lines->isEmpty()) {
            return;
        }

        // ✅ HARGA SERVER-SIDE: untuk SEMUA baris yang punya po_line_id, harga
        // SELALU diambil dari PO line (bukan hanya saat 0), sehingga nilai dari
        // request admin tidak pernah dipercaya.
        $poLinked = $lines->filter(fn($l) => !empty($l->purchase_order_line_id));

        if ($poLinked->isEmpty()) {
            return;
        }

        // Ambil harga PO sekaligus
        $poLineIds = $poLinked->pluck('purchase_order_line_id')->unique()->filter();
        $poLines = PurchaseOrderLine::with('item')
            ->whereIn('id', $poLineIds)
            ->get()
            ->keyBy('id');

        foreach ($poLinked as $line) {
            $poLine = $poLines->get($line->purchase_order_line_id);
            $poPrice = (float) ($poLine?->unit_price ?? 0);
            if ($poPrice <= 0) {
                continue; // PO belum ada harga → biarkan (guard grand_total>0 akan menahan post)
            }

            $factor = max(0.000001, (float) ($line->conversion_factor ?: $poLine?->effectiveConversionFactor() ?: 1));
            $stockQtyReceived = (float) ($line->stock_qty_received ?? ((float) ($line->qty_received ?? 0) * $factor));
            $stockQtyReject = (float) ($line->stock_qty_reject ?? ((float) ($line->qty_reject ?? 0) * $factor));
            $stockUnitPrice = $poPrice / $factor;
            $lineTotal = round($stockQtyReceived * $stockUnitPrice, 2);

            $needsUnitSnapshot = empty($line->purchase_unit)
                || empty($line->stock_unit)
                || abs((float) $line->conversion_factor - $factor) > 0.0001
                || is_null($line->stock_qty_received)
                || is_null($line->stock_qty_reject);

            if ($needsUnitSnapshot
                || abs((float) $line->unit_price - $poPrice) > 0.0001
                || abs((float) $line->line_total - $lineTotal) > 0.0001) {
                PurchaseReceiptLine::where('id', $line->id)->update([
                    'unit_price' => $poPrice,
                    'purchase_unit' => $line->purchase_unit ?: $poLine?->effectivePurchaseUnit(),
                    'stock_unit' => $line->stock_unit ?: $poLine?->effectiveStockUnit(),
                    'conversion_factor' => $factor,
                    'stock_qty_received' => round($stockQtyReceived, 6),
                    'stock_qty_reject' => round($stockQtyReject, 6),
                    'line_total' => $lineTotal,
                    'updated_at' => now(),
                ]);
            }
        }

        // Recalculate header
        $freshSubtotal = (float) PurchaseReceiptLine::where('purchase_receipt_id', $grn->id)
            ->sum('line_total');

        $discount   = (float) ($grn->discount ?? 0);
        $taxPct     = (float) ($grn->tax_percent ?? 0);
        $shipping   = (float) ($grn->shipping_cost ?? 0);
        $base       = max(0, $freshSubtotal - $discount);
        $taxAmount  = round($base * $taxPct / 100, 2);
        $grandTotal = round($base + $taxAmount + $shipping, 2);

        DB::table('purchase_receipts')->where('id', $grn->id)->update([
            'subtotal'    => round($freshSubtotal, 2),
            'tax_amount'  => $taxAmount,
            'grand_total' => $grandTotal,
            'updated_at'  => now(),
        ]);
    }
}
