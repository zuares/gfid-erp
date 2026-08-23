<?php

namespace App\Services\Purchasing;

use App\Helpers\CodeGenerator;
use App\Models\Account;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\SupplierPrice;
use App\Services\Accounting\JournalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function __construct(
        protected JournalService $journalService,
    ) {}

    /**
     * Create Purchase Order baru + detail lines.
     */
    public function create(array $payload): PurchaseOrder
    {
        $linesData = $payload['lines'] ?? [];
        unset($payload['lines']);

        $payload = $this->onlyHeaderFields($payload);

        if (empty($payload['code'] ?? null)) {
            $suppCode = DB::table('suppliers')
                ->where('id', (int) ($payload['supplier_id'] ?? 0))
                ->value('code');
            $prefix = $suppCode ? 'PO-' . strtoupper($suppCode) : 'PO';
            $payload['code'] = CodeGenerator::make($prefix);
        }

        return DB::transaction(function () use ($payload, $linesData) {

            // normalize numbers
            $payload['subtotal'] = 0;
            $payload['discount'] = $this->toNumber($payload['discount'] ?? 0);
            $payload['tax_percent'] = $this->toNumber($payload['tax_percent'] ?? 0);
            $payload['tax_amount'] = 0;
            $payload['shipping_cost'] = $this->toNumber($payload['shipping_cost'] ?? 0);
            $payload['grand_total'] = 0;

            $payload['status'] = $payload['status'] ?? 'draft';
            $payload['order_type'] = $this->normalizeOrderType($payload['order_type'] ?? 'material');

            /** @var PurchaseOrder $order */
            $order = PurchaseOrder::create($payload);

            $subtotal = $this->syncLines($order, is_array($linesData) ? $linesData : []);
            $this->recalculateTotals($order, $subtotal);

            return $order->fresh(['lines.item', 'supplier', 'paymentMethod']);
        }, 3);
    }

    /**
     * Update Purchase Order + detail lines.
     */
    public function update(PurchaseOrder $order, array $payload): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $payload) {
            // ✅ Kunci baris PO saat proses (hindari race dengan GRN create).
            $order = PurchaseOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            $linesData = $payload['lines'] ?? [];
            unset($payload['lines']);

            $payload = $this->onlyHeaderFields($payload);

            // code tidak boleh berubah lewat update (referensi audit)
            unset($payload['code']);

            // ✅ RECEIVING LOCK: PO yang sudah dirujuk GRN tidak boleh ganti supplier
            // dan nomor PO, serta line yang sudah diterima diproteksi (lihat syncLinesLocked()).
            if ($order->isLocked()) {
                if (array_key_exists('supplier_id', $payload)
                    && (int) $payload['supplier_id'] !== (int) $order->supplier_id) {
                    throw ValidationException::withMessages([
                        'supplier_id' => 'PO terkunci (sudah ada GRN). Supplier tidak dapat diubah.',
                    ]);
                }
                unset($payload['supplier_id']); // paksa tetap
            }

            if (array_key_exists('date', $payload)) {
                $order->date = $payload['date'];
            }

            if (array_key_exists('supplier_id', $payload)) {
                $order->supplier_id = (int) $payload['supplier_id'];
            }

            if (array_key_exists('payment_method_id', $payload)) {
                $order->payment_method_id = $payload['payment_method_id'] ? (int) $payload['payment_method_id'] : null;
            }

            if (array_key_exists('discount', $payload)) {
                $order->discount = $this->toNumber($payload['discount']);
            }

            if (array_key_exists('tax_percent', $payload)) {
                $order->tax_percent = $this->toNumber($payload['tax_percent']);
            }

            if (array_key_exists('shipping_cost', $payload)) {
                $order->shipping_cost = $this->toNumber($payload['shipping_cost']);
            }

            if (array_key_exists('notes', $payload)) {
                $order->notes = $payload['notes'];
            }

            if (array_key_exists('status', $payload)) {
                $order->status = (string) $payload['status'];
            }

            // update order_type kalau dikirim (fallback existing)
            if (array_key_exists('order_type', $payload)) {
                $order->order_type = $this->normalizeOrderType($payload['order_type']);
            } else {
                $order->order_type = $this->normalizeOrderType($order->getAttribute('order_type') ?: 'material');
            }

            $order->save();

            $subtotal = $order->isLocked()
                ? $this->syncLinesLocked($order, is_array($linesData) ? $linesData : [])
                : $this->syncLines($order, is_array($linesData) ? $linesData : []);
            $this->recalculateTotals($order, $subtotal);

            return $order->fresh(['lines.item', 'supplier', 'paymentMethod']);
        });
    }

    /**
     * Force hitung ulang subtotal, tax, grand_total dari database.
     */
    public function recalculate(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order) {
            $subtotal = (float) $order->lines()->sum('line_total');
            $this->recalculateTotals($order, $subtotal);

            return $order->fresh(['lines.item', 'supplier', 'paymentMethod']);
        });
    }

    // ======================================================================
    // APPROVE / CANCEL
    // ======================================================================

    /**
     * Approve PO + post EXPENSE lines (allocation=expense) ke jurnal:
     * Dr expense_account_id, Cr AP (2101).
     * Inventory/HPP lines tetap menunggu GRN.
     */
    public function approve(PurchaseOrder $order, int $approvedBy): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $approvedBy) {

            $order->load(['lines', 'supplier', 'paymentMethod']);

            if ($order->status !== 'draft') {
                return $order->fresh(['supplier', 'lines', 'paymentMethod']);
            }

            // feature flags
            $hasLineAllocation = Schema::hasColumn('purchase_order_lines', 'allocation');
            $hasLineExpenseAcc = Schema::hasColumn('purchase_order_lines', 'expense_account_id');

            // ✅ VALIDASI: expense line wajib punya akun biaya (biar GRN bisa jurnal expense)
            if ($hasLineAllocation && $hasLineExpenseAcc) {
                $bad = $order->lines
                    ->where('allocation', 'expense')
                    ->first(function ($ln) {
                        return empty($ln->expense_account_id);
                    });

                if ($bad) {
                    throw ValidationException::withMessages([
                        'lines' => 'Ada item Expense tapi akun biaya belum ter-set. Set default_expense_account_id pada master item / pilih akun biaya di baris PO.',
                    ]);
                }

                foreach ($order->lines->where('allocation', 'expense') as $line) {
                    $this->assertExpenseAccount((int) $line->expense_account_id);
                }
            }

            // ✅ APPROVE ORDER
            $order->status = 'approved';
            $order->approved_by = $approvedBy;
            $order->approved_at = now();
            $order->save();

            return $order->fresh(['supplier', 'lines', 'paymentMethod']);
        });
    }

    public function unapprove(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order) {
            $order->status = 'draft';
            $order->approved_by = null;
            $order->approved_at = null;
            $order->save();

            return $order->fresh(['supplier', 'lines', 'paymentMethod']);
        });
    }

    public function cancel(PurchaseOrder $order, int $cancelledBy): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $cancelledBy) {

            if (!in_array($order->status, ['draft', 'approved'], true)) {
                return $order->fresh(['supplier', 'lines', 'paymentMethod']);
            }

            if ($order->purchaseReceipts()->exists()) {
                return $order->fresh(['supplier', 'lines', 'purchaseReceipts', 'paymentMethod']);
            }

            // NOTE: kalau kamu mau cancel PO approved yg sudah mem-post expense journal:
            // kamu bisa void jurnal by source (SRC_PO_EXPENSE_APPROVE, po_id) di sini.
            // Tapi kamu bilang GRN nanti, jadi kita keep minimal.

            $order->status = 'cancelled';
            $order->cancelled_by = $cancelledBy;
            $order->cancelled_at = now();
            $order->save();

            return $order->fresh(['supplier', 'lines', 'cancelledBy', 'paymentMethod']);
        });
    }

    // ======================================================================
    // RECEIVING LOCK
    // ======================================================================

    /**
     * Kunci PO karena sudah ada GRN yang merujuk ke line-nya.
     * Idempoten: first_grn_id hanya diisi sekali (GRN penyebab lock).
     * Dipanggil dari GoodsReceiptService::create() di dalam transaksi + lockForUpdate.
     */
    public function lockForReceiving(PurchaseOrder $order, int $grnId, ?int $userId = null, ?string $reason = null): void
    {
        if ($order->isLocked()) {
            return; // sudah terkunci — jangan overwrite jejak lock pertama
        }

        $order->forceFill([
            'receiving_started_at' => $order->receiving_started_at ?? now(),
            'locked_at'   => now(),
            'locked_by'   => $userId,
            'lock_reason' => $reason ?: 'GRN pertama dibuat dari PO ini.',
            'first_grn_id' => $grnId,
        ])->save();
    }

    /**
     * Evaluasi apakah lock boleh dilepas setelah seluruh GRN dibatalkan/dihapus.
     *
     * KEPUTUSAN: konservatif. Lock HANYA dilepas bila TIDAK ada jejak yang
     * relevan sama sekali:
     * - tidak ada GRN tersisa (posted maupun draft),
     * - tidak pernah/ tidak ada payment (termasuk voided sebagai jejak),
     * - tidak ada purchase return.
     * Jika salah satu pernah ada (stok/jurnal/payment/return/audit), lock TIDAK dibuka.
     * Default flow tetap terkunci karena GRN normal tidak bisa dihapus.
     */
    public function maybeUnlock(PurchaseOrder $order): bool
    {
        if (!$order->isLocked()) {
            return false;
        }

        $hasAnyGrn = DB::table('purchase_receipts')
            ->where('purchase_order_id', $order->id)->exists();

        $hasAnyPayment = DB::table('purchase_payments')
            ->where('purchase_order_id', $order->id)->exists();

        $hasAnyReturn = DB::table('purchase_returns')
            ->where('purchase_order_id', $order->id)->exists();

        if ($hasAnyGrn || $hasAnyPayment || $hasAnyReturn) {
            return false; // ada jejak relevan → tetap terkunci
        }

        $order->forceFill([
            'locked_at'   => null,
            'locked_by'   => null,
            'lock_reason' => null,
            'first_grn_id' => null,
            'receiving_started_at' => null,
        ])->save();

        return true;
    }

    /**
     * Total qty yang sudah "terpakai" per purchase_order_line_id, dari SEMUA GRN
     * (draft + posted) yang merujuk line tsb. Dipakai sebagai batas bawah qty PO.
     */
    public function receivedQtyByLineId(PurchaseOrder $order): array
    {
        return DB::table('purchase_receipt_lines')
            ->join('purchase_receipts', 'purchase_receipts.id', '=', 'purchase_receipt_lines.purchase_receipt_id')
            ->where('purchase_receipts.purchase_order_id', $order->id)
            ->whereIn('purchase_receipts.status', ['draft', 'posted'])
            ->whereNotNull('purchase_receipt_lines.purchase_order_line_id')
            ->groupBy('purchase_receipt_lines.purchase_order_line_id')
            ->selectRaw('purchase_receipt_lines.purchase_order_line_id as line_id, SUM(purchase_receipt_lines.qty_received + purchase_receipt_lines.qty_reject) as used')
            ->pluck('used', 'line_id')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }

    /**
     * Sinkronisasi line untuk PO TERKUNCI — TIDAK melakukan lines()->delete() buta.
     *
     * Aturan (defense-in-depth level Service):
     * - Line yang sudah dirujuk GRN (received/reject > 0 atau punya receiptLines):
     *   • tidak boleh hilang (dihapus) dari payload,
     *   • item tidak boleh diganti (dicocokkan per item_id),
     *   • qty tidak boleh turun di bawah qty yang sudah diterima,
     *   • harga/diskon/notes boleh diperbarui (owner memperbaiki harga sebelum GRN diposting).
     * - Line lama tanpa referensi GRN boleh diubah/dihapus.
     * - Line baru boleh ditambahkan.
     *
     * Pencocokan dilakukan per item_id karena form PO tidak mengirim id line,
     * dan item pada line yang sudah dirujuk memang dilarang berubah.
     */
    protected function syncLinesLocked(PurchaseOrder $order, array $linesData): float
    {
        $hasLineAllocation = Schema::hasColumn('purchase_order_lines', 'allocation');
        $hasLineExpenseAcc = Schema::hasColumn('purchase_order_lines', 'expense_account_id');
        $hasItemDefaultAlloc = Schema::hasColumn('items', 'default_allocation');
        $hasItemDefaultExpAcc = Schema::hasColumn('items', 'default_expense_account_id');

        $existingLines = $order->lines()->get();
        $receivedByLineId = $this->receivedQtyByLineId($order);

        // item_id → daftar line id yang REFERENCED (dipakai GRN)
        $referencedByItem = [];
        foreach ($existingLines as $ln) {
            $used = (float) ($receivedByLineId[$ln->id] ?? 0);
            if ($used > 0.0001) {
                $referencedByItem[(int) $ln->item_id][] = $ln;
            }
        }

        // Preload item master untuk validasi tipe + default alokasi
        $incomingItemIds = collect($linesData)
            ->pluck('item_id')->filter()->map(fn ($v) => (int) $v)->unique()->values()->all();
        $itemsById = Item::query()
            ->whereIn('id', array_merge($incomingItemIds, array_keys($referencedByItem)))
            ->get()->keyBy('id');

        // Agregasi qty incoming per item (form bisa mengirim item sama di >1 baris)
        $incomingByItem = [];
        foreach ($linesData as $i => $row) {
            $itemId = (int) ($row['item_id'] ?? 0);
            $qty = $this->toNumber($row['qty'] ?? 0);
            if ($itemId <= 0 || $qty <= 0.0001) {
                continue;
            }
            $incomingByItem[$itemId] = ($incomingByItem[$itemId] ?? 0) + $qty;
        }

        // 1) GUARD: semua item yang sudah dirujuk GRN wajib tetap ada dgn qty >= received.
        foreach ($referencedByItem as $itemId => $lines) {
            $requiredMin = 0.0;
            foreach ($lines as $ln) {
                $requiredMin += (float) ($receivedByLineId[$ln->id] ?? 0);
            }

            if (!array_key_exists($itemId, $incomingByItem)) {
                $name = $itemsById[$itemId]->name ?? ('#' . $itemId);
                throw ValidationException::withMessages([
                    'lines' => "Item \"{$name}\" sudah dirujuk GRN dan tidak boleh dihapus dari PO terkunci.",
                ]);
            }

            if ($incomingByItem[$itemId] + 0.0001 < $requiredMin) {
                $name = $itemsById[$itemId]->name ?? ('#' . $itemId);
                throw ValidationException::withMessages([
                    'lines' => "Qty item \"{$name}\" tidak boleh diturunkan di bawah qty yang sudah diterima ({$requiredMin}).",
                ]);
            }
        }

        // 2) Hapus HANYA line lama yang TIDAK dirujuk GRN.
        foreach ($existingLines as $ln) {
            $used = (float) ($receivedByLineId[$ln->id] ?? 0);
            if ($used <= 0.0001) {
                $ln->delete();
            }
        }

        // 3) Rebuild: untuk item referenced → pertahankan line pertama (update harga/qty),
        //    sisanya (item non-referenced) dibuat baru seperti biasa.
        $subtotal = 0.0;
        $consumedReferenced = []; // item_id sudah dipakai untuk update in-place

        foreach ($linesData as $i => $row) {
            $itemId = (int) ($row['item_id'] ?? 0);
            $qty = $this->toNumber($row['qty'] ?? 0);
            $unitPrice = $this->toNumber($row['unit_price'] ?? 0);
            $discount = $this->toNumber($row['discount'] ?? 0);
            $notes = $row['notes'] ?? null;

            if ($itemId <= 0 || $qty <= 0.0001) {
                continue;
            }

            $item = $itemsById->get($itemId);
            if (!$item) {
                continue;
            }
            $lineTotal = round(max(0, ($qty * $unitPrice) - $discount), 2);

            // Alokasi/expense account: pertahankan yang lama untuk referenced, hitung untuk baru.
            $referenced = $referencedByItem[$itemId] ?? [];
            $isReferencedItem = !empty($referenced) && !in_array($itemId, $consumedReferenced, true);

            if ($isReferencedItem) {
                /** @var PurchaseOrderLine $keep */
                $keep = $referenced[0];
                $keep->qty = round($qty, 4);
                $keep->unit_price = round($unitPrice, 4);
                $keep->discount = round($discount, 2);
                $keep->line_total = $lineTotal;
                $keep->notes = $notes;

                // item_id, allocation, expense_account_id SENGAJA tidak diubah, KECUALI bila expense_account_id masih kosong.
                if ($hasLineExpenseAcc && $keep->allocation === 'expense' && empty($keep->expense_account_id)) {
                    $fromLine = $row['expense_account_id'] ?? null;
                    if ($fromLine !== null && $fromLine !== '' && (int) $fromLine > 0) {
                        $keep->expense_account_id = (int) $fromLine;
                    } elseif ($hasItemDefaultExpAcc && !empty($item->default_expense_account_id)) {
                        $keep->expense_account_id = (int) $item->default_expense_account_id;
                    }

                    if (!empty($keep->expense_account_id)) {
                        $this->assertExpenseAccount((int) $keep->expense_account_id);
                    }
                }

                $keep->save();
                $consumedReferenced[] = $itemId;

                // Line referenced ekstra (item sama di >1 line) dibiarkan apa adanya.
            } else {
                $allocation = 'hpp';
                if ($hasLineAllocation) {
                    $fromLine = $row['allocation'] ?? null;
                    $allocRaw = ($fromLine !== null && $fromLine !== '')
                        ? (string) $fromLine
                        : (string) ($hasItemDefaultAlloc ? ($item->default_allocation ?? 'hpp') : 'hpp');
                    $allocation = in_array($allocRaw, ['hpp', 'expense'], true) ? $allocRaw : 'hpp';
                }

                $expenseAccountId = null;
                if ($hasLineExpenseAcc && $allocation === 'expense') {
                    $fromLine = $row['expense_account_id'] ?? null;
                    if ($fromLine !== null && $fromLine !== '' && (int) $fromLine > 0) {
                        $expenseAccountId = (int) $fromLine;
                    } elseif ($hasItemDefaultExpAcc && !empty($item->default_expense_account_id)) {
                        $expenseAccountId = (int) $item->default_expense_account_id;
                    }

                    if ($expenseAccountId) {
                        $this->assertExpenseAccount($expenseAccountId);
                    }
                }

                $payload = [
                    'item_id' => $itemId,
                    'lot_id' => !empty($row['lot_id']) ? (int) $row['lot_id'] : null,
                    'qty' => round($qty, 4),
                    'unit_price' => round($unitPrice, 4),
                    'discount' => round($discount, 2),
                    'line_total' => $lineTotal,
                    'notes' => $notes,
                ];
                if ($hasLineAllocation) {
                    $payload['allocation'] = $allocation;
                }
                if ($hasLineExpenseAcc) {
                    $payload['expense_account_id'] = ($allocation === 'expense') ? $expenseAccountId : null;
                }
                $order->lines()->create($payload);
            }

            $subtotal = round($subtotal + $lineTotal, 2);
            $this->touchLastPrices($order, $itemId, (float) $unitPrice);
        }

        // Tambahkan kembali line_total referenced yang tidak muncul di incoming
        // (seharusnya tidak terjadi karena guard di atas, tetapi jaga konsistensi subtotal).
        $subtotal = round((float) $order->lines()->sum('line_total'), 2);

        return $subtotal;
    }

    // ======================================================================
    // INTERNAL HELPERS
    // ======================================================================

    protected function onlyHeaderFields(array $payload): array
    {
        $allowed = [
            'code',
            'date',
            'supplier_id',
            'payment_method_id',
            'discount',
            'tax_percent',
            'shipping_cost',
            'notes',
            'created_by',
            'status',
            'order_type',
            'purchase_request_id',
        ];

        return array_intersect_key($payload, array_flip($allowed));
    }

    /**
     * syncLines:
     * - simpan semua line (hpp + expense)
     * - allocation & expense_account_id otomatis dari master item (default_allocation, default_expense_account_id)
     * - line boleh override kalau dikirim
     * - expense account boleh null saat draft (validasi keras ada di approve())
     */
    protected function syncLines(PurchaseOrder $order, array $linesData): float
    {
        $order->lines()->delete();

        $subtotal = 0.0;
        // feature flags
        $hasLineAllocation = Schema::hasColumn('purchase_order_lines', 'allocation');
        $hasLineExpenseAcc = Schema::hasColumn('purchase_order_lines', 'expense_account_id');
        $hasItemDefaultAlloc = Schema::hasColumn('items', 'default_allocation');
        $hasItemDefaultExpAcc = Schema::hasColumn('items', 'default_expense_account_id');

        // preload items
        $itemIds = collect($linesData)
            ->pluck('item_id')
            ->filter(fn($v) => $v !== null && $v !== '')
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $itemsById = Item::query()
            ->select(array_values(array_filter([
                'id',
                'type',
                $hasItemDefaultAlloc ? 'default_allocation' : null,
                $hasItemDefaultExpAcc ? 'default_expense_account_id' : null,
            ])))
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        foreach ($linesData as $i => $row) {
            $itemId = $row['item_id'] ?? null;
            $itemId = ($itemId === null || $itemId === '') ? 0 : (int) $itemId;

            $qty = $this->toNumber($row['qty'] ?? 0);
            $unitPrice = $this->toNumber($row['unit_price'] ?? 0);
            $discount = $this->toNumber($row['discount'] ?? 0);
            $notes = $row['notes'] ?? null;
            $lotId = $row['lot_id'] ?? null;

            // Draft boleh menyimpan item yang sudah dipilih meski qty belum
            // diisi. Baris tersebut tetap tampil di detail PO dan bisa
            // dilengkapi dari halaman edit sebelum di-approve/diterima.
            if ($itemId <= 0) {
                continue;
            }

            /** @var Item|null $item */
            $item = $itemsById->get($itemId);
            if (!$item) {
                continue;
            }

            // Allocation (hpp/expense) - auto dari master item, line override kalau ada
            $allocation = 'hpp';
            if ($hasLineAllocation) {
                $fromLine = $row['allocation'] ?? null;

                if ($fromLine !== null && $fromLine !== '') {
                    $allocRaw = (string) $fromLine;
                } else {
                    $allocRaw = (string) ($hasItemDefaultAlloc ? ($item->default_allocation ?? 'hpp') : 'hpp');
                }

                $allocation = in_array($allocRaw, ['hpp', 'expense'], true) ? $allocRaw : 'hpp';
            }

            // Expense account if expense - auto dari master, line override kalau ada
            $expenseAccountId = null;
            if ($hasLineExpenseAcc && $allocation === 'expense') {
                $fromLine = $row['expense_account_id'] ?? null;

                if ($fromLine !== null && $fromLine !== '') {
                    $expenseAccountId = (int) $fromLine;
                    if ($expenseAccountId <= 0) {
                        $expenseAccountId = null;
                    }
                }

                if (!$expenseAccountId && $hasItemDefaultExpAcc && !empty($item->default_expense_account_id)) {
                    $expenseAccountId = (int) $item->default_expense_account_id;
                    if ($expenseAccountId <= 0) {
                        $expenseAccountId = null;
                    }
                }

                if ($expenseAccountId) {
                    $this->assertExpenseAccount($expenseAccountId);
                }
            }

            $lineTotal = max(0, ($qty * $unitPrice) - $discount);
            $lineTotal = round($lineTotal, 2);

            $payload = [
                'item_id' => (int) $itemId,
                'lot_id' => $lotId ? (int) $lotId : null,
                'qty' => round($qty, 4),
                'unit_price' => round($unitPrice, 4),
                'discount' => round($discount, 2),
                'line_total' => $lineTotal,
                'notes' => $notes,
            ];

            if ($hasLineAllocation) {
                $payload['allocation'] = $allocation;
            }
            if ($hasLineExpenseAcc) {
                $payload['expense_account_id'] = ($allocation === 'expense') ? $expenseAccountId : null;
            }

            $order->lines()->create($payload);

            $subtotal = round($subtotal + $lineTotal, 2);

            // update last purchase price
            $this->touchLastPrices($order, (int) $itemId, (float) $unitPrice);
        }

        return round($subtotal, 2);
    }

    protected function recalculateTotals(PurchaseOrder $order, float $subtotal): void
    {
        $discount = $this->toNumber($order->discount);
        $taxPercent = $this->toNumber($order->tax_percent);
        $shippingCost = $this->toNumber($order->shipping_cost);

        $base = max(0, $subtotal - $discount);
        $taxAmount = round($base * $taxPercent / 100, 2);
        $grandTotal = $base + $taxAmount + $shippingCost;

        $order->subtotal = round($subtotal, 2);
        $order->tax_amount = $taxAmount;
        $order->grand_total = round($grandTotal, 2);
        $order->save();
    }

    protected function touchLastPrices(PurchaseOrder $order, int $itemId, float $unitPrice): void
    {
        $unitPrice = round($unitPrice, 2);

        if ($unitPrice <= 0) {
            return;
        }

        Item::whereKey($itemId)->update([
            'last_purchase_price' => $unitPrice,
        ]);

        SupplierPrice::updateOrCreate(
            ['supplier_id' => $order->supplier_id, 'item_id' => $itemId],
            ['last_price' => $unitPrice]
        );
    }

    protected function accountIdByCode(string $code): int
    {
        $acc = Account::query()
            ->where('code', $code)
            ->where('is_active', 1)
            ->first();

        if (!$acc) {
            throw ValidationException::withMessages([
                'account' => "Account code {$code} tidak ditemukan / tidak aktif.",
            ]);
        }

        return (int) $acc->id;
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
                'lines' => "Akun biaya #{$accountId} tidak aktif atau bukan akun expense.",
            ]);
        }
    }

    protected function normalizeOrderType(?string $value): string
    {
        $v = strtolower(trim((string) $value));
        return in_array($v, ['material', 'finished_good', 'packing'], true) ? $v : 'material';
    }

    protected function toNumber($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);

        // format indo: 1.234,56
        if (strpos($value, ',') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
            return (float) $value;
        }

        // ribuan: 1.234.567
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
            return (float) $value;
        }

        return (float) $value;
    }
}
