<?php

namespace App\Http\Controllers\Production;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemBom;
use App\Models\ItemBomLine;
use App\Models\ItemRole;
use App\Models\InventoryAdjustment;
use App\Models\SewingPickup;
use App\Models\SewingPickupLine;
use App\Models\SewingPickupLineSupplyLine;
use App\Models\SewingPickupSupplyLine;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use App\Services\Inventory\InventoryService;
use App\Services\Payroll\PieceRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SewingPickupController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
        protected JournalService $journal,
    ) {}

    public function index(Request $request)
    {
        $pickups = SewingPickup::query()
            ->with([
                'warehouse',
                'operator',
                'lines',
                // ✅ untuk menampilkan detail barang (chip item) di index
                'lines.finishedItem:id,code,name',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('production.sewing_pickups.index', [
            'pickups' => $pickups,
        ]);
    }

    public function print(SewingPickup $pickup): View
    {
        $pickup->load([
            'warehouse',
            'operator',
            'lines.bundle.finishedItem',
            'lines.bundle.cuttingJob.lot',
            'supplyLines.material',
        ]);

        return view('production.sewing_pickups.print', compact('pickup'));
    }

    public function show(SewingPickup $pickup): View
    {
        $pickup->load([
            'warehouse',
            'operator',
            'lines.bundle.finishedItem',
            'lines.bundle.cuttingJob.lot.item',
            'supplyLines.material',
            'physicalAdjustment.lines.item',
        ]);

        $epsilon = 0.000001;

        $totalBundles = $pickup->lines->count();
        $totalQtyPickup = (float) $pickup->lines->sum('qty_bundle');
        $totalReturnOk = (float) $pickup->lines->sum('qty_returned_ok');
        $totalReturnReject = (float) $pickup->lines->sum('qty_returned_reject');

        $totalDirectPick = (float) $pickup->lines->sum(fn($l) => (float) ($l->qty_direct_picked ?? 0));
        $totalProgressAdjusted = (float) $pickup->lines->sum(fn($l) => (float) ($l->qty_progress_adjusted ?? 0));

        $totalProgressAll = $totalReturnOk + $totalReturnReject + $totalDirectPick + $totalProgressAdjusted;

        $overallProgress = $totalQtyPickup > 0
        ? round(($totalProgressAll / $totalQtyPickup) * 100, 1)
        : 0.0;

        // Stats per line
        $notReturnedCount = $pickup->lines->filter(function ($l) use ($epsilon) {
            $progress = (float) ($l->qty_returned_ok ?? 0)
             + (float) ($l->qty_returned_reject ?? 0)
             + (float) ($l->qty_direct_picked ?? 0)
             + (float) ($l->qty_progress_adjusted ?? 0);
            return $progress <= $epsilon;
        })->count();

        $fullReturnedCount = $pickup->lines->filter(function ($l) use ($epsilon) {
            $picked = (float) ($l->qty_bundle ?? 0);
            $progress = (float) ($l->qty_returned_ok ?? 0)
             + (float) ($l->qty_returned_reject ?? 0)
             + (float) ($l->qty_direct_picked ?? 0)
             + (float) ($l->qty_progress_adjusted ?? 0);

            return $picked > 0 && ($picked - $progress) <= $epsilon;
        })->count();

        $partialReturnedCount = $totalBundles - $notReturnedCount - $fullReturnedCount;

        $supplyShortages = $pickup->supplyLines
            ->filter(fn($line) => (float) $line->issued_qty + $epsilon < (float) $line->required_qty)
            ->values();

        // Cek stok RM real-time untuk setiap shortage — supaya view bisa tahu apakah
        // stok sudah ada tapi belum di-issue, atau memang belum ada sama sekali.
        $adj = $pickup->physicalAdjustment;
        $adjResolved = $adj && $adj->status === 'approved';

        if ($supplyShortages->isNotEmpty() && !$adjResolved) {
            $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');
            if ($rmWarehouseId) {
                $supplyShortages->each(function ($line) use ($rmWarehouseId) {
                    $matId = (int) ($line->material_item_id ?? 0);
                    if (!$matId) { $line->rm_stock_qty = 0; return; }
                    $line->rm_stock_qty = \App\Models\InventoryStock::query()
                        ->where('warehouse_id', $rmWarehouseId)
                        ->where('item_id', $matId)
                        ->value('qty') ?? 0;
                    $line->rm_stock_qty = (float) $line->rm_stock_qty;
                    $line->rm_stock_ok  = $line->rm_stock_qty >= (float) $line->shortage_qty;
                });
            }
        }

        // Apakah semua shortage sebenarnya sudah ada stoknya di RM?
        $allStockNowAvailable = !$adjResolved
            && $supplyShortages->isNotEmpty()
            && $supplyShortages->every(fn($l) => (bool) ($l->rm_stock_ok ?? false));

        // Edit kelengkapan route tersedia?
        $canEditSupplies = in_array($pickup->status ?? '', ['draft', 'posted'])
            && Route::has('production.sewing.pickups.supplies.edit');

        return view('production.sewing_pickups.show', [
            'pickup'               => $pickup,
            'totalBundles'         => $totalBundles,
            'totalQtyPickup'       => $totalQtyPickup,
            'totalReturnOk'        => $totalReturnOk,
            'totalReturnReject'    => $totalReturnReject,
            'totalDirectPick'      => $totalDirectPick,
            'totalProgressAdjusted'=> $totalProgressAdjusted,
            'totalProgressAll'     => $totalProgressAll,
            'overallProgress'      => $overallProgress,
            'notReturnedCount'     => $notReturnedCount,
            'partialReturnedCount' => $partialReturnedCount,
            'fullReturnedCount'    => $fullReturnedCount,
            'supplyShortages'      => $supplyShortages,
            'adjResolved'          => $adjResolved,
            'allStockNowAvailable' => $allStockNowAvailable,
            'canEditSupplies'      => $canEditSupplies,
        ]);
    }

    /**
     * Halaman list bundle siap dijahit (opsional).
     */
    public function bundlesReady()
    {
        $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');

        $bundles = CuttingJobBundle::query()
            ->with([
                'finishedItem',
                'cuttingJob.lot.item',
                'latestCuttingQc',
            ])
            ->readyForSewing($wipCutWarehouseId)
            ->orderBy('id')
            ->get();

        return view('production.sewing_pickups.bundles_ready', [
            'bundles' => $bundles,
        ]);
    }

    /**
     * Form create Sewing Pickup.
     * Bundles yang muncul:
     * - wip_warehouse_id = WIP-CUT
     * - wip_qty > sewing_picked_qty
     * - qty_qc_ok (atau QC cutting) masih ada sisa.
     */
    public function create()
    {
        $operators = Employee::where('role', 'sewing')
            ->orderBy('code')
            ->get();

        $warehouses = Warehouse::orderBy('code')->get();

        $wipCutId = Warehouse::where('code', 'WIP-CUT')->value('id');
        $wipSewWarehouse = Warehouse::where('code', 'WIP-SEW')->firstOrFail();

        $bundles = CuttingJobBundle::with(['finishedItem', 'cuttingJob.lot.item', 'qcResults'])
            ->withLedgerBalances(['WIP-CUT'])
            ->readyForSewing($wipCutId)
            ->get();

        $bomSuppliesByItem = $this->buildSewingSupplyChecklist($bundles);

        return view('production.sewing_pickups.create', [
            'operators' => $operators,
            'warehouses' => $warehouses,
            'wipSewWarehouse' => $wipSewWarehouse,
            'bundles' => $bundles,
            'bomSuppliesByItem' => $bomSuppliesByItem,
        ]);
    }

    /**
     * Simpan Sewing Pickup.
     * Pattern:
     * - OUT: WIP-CUT (WIP Cutting)
     * - IN : gudang sewing (biasanya WIP-SEW)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'], // gudang sewing (WIP-SEW)
            'operator_id' => ['required', 'exists:employees,id'],
            'notes' => ['nullable', 'string'],
            'supplies_checklist' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.bundle_id' => ['required', 'exists:cutting_job_bundles,id'],
            'lines.*.qty_bundle' => ['nullable', 'numeric', 'min:0'],
        ], [
            'lines.required' => 'Minimal satu baris bundle harus diisi.',
            'lines.*.bundle_id.required' => 'Bundle tidak valid.',
            'lines.*.qty_bundle.required' => 'Qty pickup wajib diisi.',
        ]);

        $pickup = DB::transaction(function () use ($validated, $request) {

            $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');
            if (!$wipCutWarehouseId) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'Gudang WIP-CUT belum dikonfigurasi. Pastikan ada warehouse dengan code "WIP-CUT".',
                ]);
            }

            $sewingWarehouseId = (int) $validated['warehouse_id']; // biasanya WIP-SEW
            $date = $validated['date'];
            $code = CodeGenerator::generate('SWP');
            $notes = trim((string) ($validated['notes'] ?? ''));
            $suppliesChecklist = $this->formatSuppliesChecklistNote($validated['supplies_checklist'] ?? null);
            if ($suppliesChecklist !== '') {
                $notes = trim($notes . "\n\n" . $suppliesChecklist);
            }

            /** @var SewingPickup $pickup */
            $pickup = SewingPickup::create([
                'code' => $code,
                'date' => $date,
                'warehouse_id' => $sewingWarehouseId,
                'operator_id' => $validated['operator_id'],
                'status' => 'draft',
                'notes' => $notes !== '' ? $notes : null,
            ]);

            $createdLines = 0;
            $epsilon = 0.000001;
            $qtyByFinishedItem = [];
            $createdPickupLines = collect();

            foreach ($validated['lines'] as $row) {

                $qty = (float) ($row['qty_bundle'] ?? 0);
                if ($qty <= $epsilon) {
                    continue;
                }

                /** @var CuttingJobBundle|null $bundle */
                $bundle = CuttingJobBundle::with([
                    'qcResults' => function ($q) {
                        $q->where('stage', 'cutting');
                    },
                    'cuttingJob',
                ])->find($row['bundle_id']);

                if (!$bundle) {
                    continue;
                }

                // ✅ WAJIB: bundle ini harus berada di WIP-CUT (kolom cutting-WIP).
                if ((int) $bundle->cut_wip_warehouse_id !== (int) $wipCutWarehouseId) {
                    throw ValidationException::withMessages([
                        'lines' => "Bundle {$bundle->bundle_code} bukan berada di gudang WIP-CUT.",
                    ]);
                }

                // WIP per bundle (pakai kolom cutting-WIP yang kebal dari tahap hilir)
                $wipQty = (float) ($bundle->cut_wip_qty ?? 0);

                // ✅ FIX: hitung alreadyPicked dari SUM(sewing_pickup_lines.qty_bundle) aktif.
                // JANGAN pakai bundle->sewing_picked_qty — bisa terkontaminasi bug lama / qty_direct_picked.
                // Invariant: sewing_picked_qty = SUM(pickup_lines.qty_bundle WHERE status != 'void').
                $alreadyPicked = (float) SewingPickupLine::query()
                    ->where('cutting_job_bundle_id', $bundle->id)
                    ->where('status', '!=', 'void')
                    ->sum('qty_bundle');

                // ✅ VALIDASI KETAT: qty_bundle tidak boleh melebihi sisa (qty_pcs - alreadyPicked).
                // qty_direct_picked dan qty_progress_adjusted tidak menaikkan sewing_picked_qty.
                $qtyPcs = (float) ($bundle->qty_pcs ?? 0);
                $remainingByBundle = max($qtyPcs - $alreadyPicked, 0.0);
                if ($qty > $remainingByBundle + $epsilon) {
                    throw ValidationException::withMessages([
                        'lines' => "Bundle {$bundle->bundle_code}: qty {$qty} melebihi sisa "
                            . "{$remainingByBundle} pcs (qty_pcs={$qtyPcs}, sudah dipick={$alreadyPicked}). "
                            . "Pickup tidak boleh melebihi qty_pcs bundle.",
                    ]);
                }

                $maxFromWip = max($wipQty - $alreadyPicked, 0.0);

                // QC Cutting terakhir
                $lastQc = $bundle->qcResults
                ? $bundle->qcResults->sortByDesc('qc_date')->first()
                : null;

                // Batas qty berdasarkan QC
                $maxQtyOk = $lastQc && $lastQc->qty_ok !== null
                ? (float) $lastQc->qty_ok
                : (float) $bundle->qty_pcs;

                if ($maxQtyOk <= $epsilon) {
                    continue;
                }

                $remainingByQc = max($maxQtyOk - $alreadyPicked, 0.0);
                $remaining = min($maxFromWip, $remainingByQc);

                if ($remaining <= $epsilon) {
                    continue;
                }

                if ($qty > $remaining) {
                    $qty = $remaining;
                }

                if ($qty <= $epsilon) {
                    continue;
                }

                // ✅ Ambil unit cost dulu (penting supaya bisa disimpan ke line)
                $unitCostPerPiece = (float) $this->inventory->getItemIncomingUnitCost(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $bundle->finished_item_id,
                );
                if ($unitCostPerPiece <= 0) {
                    $itemCode = Item::query()->whereKey($bundle->finished_item_id)->value('code');
                    throw ValidationException::withMessages([
                        'lines' => 'Harga pokok WIP ' . ($itemCode ?: "item #{$bundle->finished_item_id}")
                            . ' belum tersedia. Perbaiki cost hasil cutting sebelum pickup jahit.',
                    ]);
                }

                // 🔹 Ambil upah jahit per pcs dari PieceRateService — WAJIB diset sebelum pickup.
                // Kalau rate belum diisi, lempar ValidationException supaya user tahu harus setup dulu.
                $itemCode = Item::query()->whereKey($bundle->finished_item_id)->value('code') ?? "item #{$bundle->finished_item_id}";
                try {
                    $wagePerPcs = (float) app(PieceRateService::class)->requireRatePerPcs(
                        module: 'sewing',
                        employeeId: (int) $validated['operator_id'],
                        itemId: (int) $bundle->finished_item_id,
                        date: $date,
                    );
                } catch (ValidationException $e) {
                    // Rate belum diset — blokir pickup & informasikan ke user.
                    $operator = Employee::query()->whereKey($validated['operator_id'])->value('name') ?? "operator #{$validated['operator_id']}";
                    throw ValidationException::withMessages([
                        'lines' => "Upah jahit untuk {$operator} — {$itemCode} belum diset. "
                            . "Isi dulu di menu Payroll → Piece Rate sebelum membuat Ambil Jahit.",
                    ]);
                } catch (\Throwable $e) {
                    // Error tak terduga — log tapi jangan blokir produksi.
                    Log::error("SWP {$code}: gagal lookup wage rate untuk {$itemCode} — upah=0.", [
                        'error' => $e->getMessage(),
                    ]);
                    $wagePerPcs = 0.0;
                }

                // WIP-SEW membawa material + upah sejak Ambil Jahit.
                // wage_per_pcs tetap disimpan untuk audit dan reversal per line.
                $unitCostMaterial = $unitCostPerPiece;
                $unitCostWithWage = round($unitCostMaterial + $wagePerPcs, 10);

                // 🔹 Simpan detail sewing pickup (simpan unit_cost + wage_per_pcs)
                $pickupLine = SewingPickupLine::create([
                    'sewing_pickup_id' => $pickup->id,
                    'cutting_job_bundle_id' => $bundle->id,
                    'finished_item_id' => $bundle->finished_item_id,
                    'qty_bundle' => $qty,
                    'unit_cost' => $unitCostWithWage,
                    'wage_per_pcs' => $wagePerPcs,
                    'status' => 'in_progress',
                ]);

                $createdPickupLines->push($pickupLine);

                $finishedItemId = (int) $bundle->finished_item_id;
                $qtyByFinishedItem[$finishedItemId] = ($qtyByFinishedItem[$finishedItemId] ?? 0) + $qty;

                // 🔹 UPDATE qty pick di bundle
                // ✅ FIX: newPicked = SUM dari pickup lines setelah baris ini ditambahkan.
                // Hard cap oleh qty_pcs — sewing_picked_qty tidak boleh melebihi qty_pcs.
                $newPicked = $alreadyPicked + $qty;
                if ($newPicked > $qtyPcs) {
                    $newPicked = $qtyPcs;
                }

                $bundle->sewing_picked_qty = $newPicked;

                if ($newPicked >= $maxQtyOk) {
                    $bundle->status = 'in_sewing'; // sesuaikan jika enum berbeda
                }

                $bundle->save();

                // =======================
                // INVENTORY MOVEMENT
                // =======================
                $notes = "Sewing pickup {$pickup->code} - bundle {$bundle->bundle_code}";

                // 1️⃣ OUT dari WIP-CUT
                $this->inventory->stockOut(
                    warehouseId: $wipCutWarehouseId,
                    itemId: $bundle->finished_item_id,
                    qty: $qty,
                    date: $date,
                    sourceType: SewingPickup::class,
                    sourceId: $pickup->id,
                    notes: $notes,
                    allowNegative: false,
                    lotId: null,
                    unitCostOverride: $unitCostPerPiece,
                    affectLotCost: false,
                    cuttingJobBundleId: $bundle->id,
                );

                // 2️⃣ IN ke gudang sewing (WIP-SEW) — material + upah
                $this->inventory->stockIn(
                    warehouseId: $sewingWarehouseId,
                    itemId: $bundle->finished_item_id,
                    qty: $qty,
                    date: $date,
                    sourceType: SewingPickup::class,
                    sourceId: $pickup->id,
                    notes: $notes . ($wagePerPcs > 0 ? " [upah @{$wagePerPcs}/pcs]" : ''),
                    lotId: null,
                    unitCost: $unitCostWithWage,
                    affectLotCost: false,
                    cuttingJobBundleId: $bundle->id,
                );

                $createdLines++;
            }

            if ($createdLines === 0) {
                throw ValidationException::withMessages([
                    'lines' => 'Minimal satu bundle harus punya Qty Pickup > 0 dan qty ready yang masih tersisa.',
                ]);
            }

            $this->storeSewingPickupSupplies(
                pickup: $pickup,
                qtyByFinishedItem: $qtyByFinishedItem,
                submittedPayload: $validated['supplies_checklist'] ?? null,
            );

            $this->storeSewingPickupLineSupplies(
                pickup: $pickup,
                pickupLines: $createdPickupLines,
                submittedPayload: $validated['supplies_checklist'] ?? null,
            );

            // 1) Konfirmasi fisik operator (checklist) yang melebihi stok sistem
            //    didokumentasikan sebagai adjustment IN pending ("Ditemukan fisik")
            //    — wajib diverifikasi/approve admin. Harus dibuat SEBELUM issued_qty
            //    dinaikkan agar gap-nya masih terbaca.
            $this->createSupplyPhysicalAdjustment(
                $pickup,
                $validated['supplies_checklist'] ?? null,
                (int) $request->user()->id,
            );

            // 2) Naikkan issued_qty ke qty yang dikonfirmasi fisik oleh operator,
            //    walaupun stok sistem kurang. Stok RM boleh minus sementara
            //    (item supply allow_negative) dan kembali normal saat adjustment
            //    "Ditemukan fisik" di-approve.
            $this->applyConfirmedPhysicalToSupplyLines($pickup, $validated['supplies_checklist'] ?? null);

            $this->syncLineSupplyIssuedFromAggregate($pickup);

            // 3) VALIDASI SISA KEKURANGAN setelah konfirmasi fisik:
            //    - default: tolak, arahkan operator konfirmasi checklist
            //    - admin/owner + force_supply_shortage: lolos, gap tetap tercatat
            //      di supply lines (required vs issued) untuk follow-up purchasing
            $pickup->loadMissing('supplyLines.material');
            $stockShortages = $pickup->supplyLines->filter(
                fn($sl) => (float) $sl->required_qty > 0.000001
                    && (float) $sl->issued_qty + 0.000001 < (float) $sl->required_qty
            );
            if ($stockShortages->isNotEmpty()) {
                $role     = $request->user()->role ?? null;
                $canForce = in_array($role, ['admin', 'owner'], true);
                $forced   = $canForce && $request->boolean('force_supply_shortage');

                if (!$forced) {
                    $details = $stockShortages->map(function ($sl) {
                        $code  = $sl->material?->code ?? "material #{$sl->material_item_id}";
                        $need  = number_format((float) $sl->required_qty, 2, ',', '.');
                        $have  = number_format((float) $sl->issued_qty, 2, ',', '.');
                        $short = number_format(
                            max((float) $sl->required_qty - (float) $sl->issued_qty, 0),
                            2, ',', '.'
                        );
                        return "• {$code}: butuh {$need}, tersedia/konfirmasi {$have} (kurang {$short})";
                    })->implode("\n");

                    $hint = $canForce
                        ? 'Jika barang ada di lapangan, isi qty pada checklist kelengkapan. Atau centang "Paksa lanjut tanpa kelengkapan" untuk tetap menyimpan.'
                        : 'Jika barang ada di lapangan, isi qty pada checklist kelengkapan (stok sistem akan dikoreksi otomatis). Jika barang memang kosong, minta admin/owner menyimpan dengan opsi paksa.';

                    throw ValidationException::withMessages([
                        'supplies' => "Stok kelengkapan jahit tidak mencukupi.\n\n{$details}\n\n{$hint}",
                    ]);
                }

                Log::warning("SWP {$pickup->code}: pickup dilanjutkan dengan kekurangan kelengkapan (force oleh {$role}).", [
                    'sewing_pickup_id' => $pickup->id,
                    'shortages' => $stockShortages->map(fn($sl) => [
                        'material_item_id' => (int) $sl->material_item_id,
                        'required_qty' => (float) $sl->required_qty,
                        'issued_qty' => (float) $sl->issued_qty,
                    ])->values()->all(),
                ]);
            }

            $this->issueSewingPickupSupplies($pickup);
            $this->allocateSewingSupplyCostToPickupWip($pickup);

            return $pickup;
        });

        try {
            $this->journal->postSewingPickup($pickup);
        } catch (\Throwable $e) {
            Log::warning('Gagal membuat jurnal sewing pickup', [
                'sewing_pickup_id' => $pickup->id,
                'message' => $e->getMessage(),
            ]);
        }

        foreach ($pickup->lines()->where('status', '!=', 'void')->get() as $line) {
            try {
                $this->journal->postSewingPickupLineWage($line);
            } catch (\Throwable $e) {
                Log::warning('Gagal membuat jurnal upah sewing pickup line', [
                    'sewing_pickup_line_id' => $line->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        try {
            $this->journal->postSewingPickupSupply($pickup);
        } catch (\Throwable $e) {
            Log::warning('Gagal membuat jurnal kelengkapan sewing pickup', [
                'sewing_pickup_id' => $pickup->id,
                'message' => $e->getMessage(),
            ]);
        }

        if ($request->input('print_after_save') === '1') {
            $paperWidth = in_array($request->input('paper_width'), ['50mm','58mm','80mm','100mm'])
                ? $request->input('paper_width')
                : '50mm';
            return redirect()->route('production.sewing.pickups.print', $pickup)
                ->with('auto_print', true)
                ->with('paper_width', $paperWidth);
        }

        $adjustment = InventoryAdjustment::query()
            ->where('reference_type', SewingPickup::class)
            ->where('reference_id', $pickup->id)
            ->where('status', InventoryAdjustment::STATUS_PENDING)
            ->first();

        $message = $adjustment
            ? "Pickup berhasil dibuat. Kelengkapan menyusul tercatat di {$adjustment->code} dan menunggu approval gudang."
            : 'Sewing pickup berhasil dibuat. Stok sudah dipindahkan dari WIP-CUT ke gudang sewing.';

        return redirect()
            ->route('production.sewing.returns.create')
            ->with('success', $message);
    }

    private function issueSewingPickupSupplies(SewingPickup $pickup): void
    {
        $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');
        if (!$rmWarehouseId) {
            throw ValidationException::withMessages([
                'supplies' => 'Gudang RM belum dikonfigurasi. Bahan pendukung jahit belum bisa dicek.',
            ]);
        }

        $pickup->loadMissing('supplyLines.material');

        $lines = $pickup->supplyLines
            ->filter(fn($line) => (float) ($line->issued_qty ?? 0) > 0)
            ->values();

        if ($lines->isEmpty()) {
            return;
        }

        foreach ($lines as $line) {
            $issuedQty = (float) ($line->issued_qty ?? 0);
            $material  = $line->material;
            $unitCost  = $this->inventory->getItemIncomingUnitCost(
                warehouseId: (int) $rmWarehouseId,
                itemId: (int) $line->material_item_id,
            );

            $pendingCost = $unitCost <= 0;

            if ($pendingCost) {
                // GRN belum masuk — catat stok keluar dengan cost 0, tandai pending_cost.
                // Operator TIDAK diblokir. Admin rekonsiliasi via dashboard setelah GRN masuk.
                Log::warning("SWP {$pickup->code}: kelengkapan {$material?->code} belum ada GRN/cost — dicatat pending.", [
                    'sewing_pickup_id'  => $pickup->id,
                    'material_item_id'  => $line->material_item_id,
                ]);
            }

            // Tandai line sebagai pending_cost agar muncul di dashboard rekonsiliasi
            $line->forceFill([
                'pending_cost'    => $pendingCost,
                'issued_unit_cost'=> $pendingCost ? null : $unitCost,
            ])->save();

            $this->inventory->stockOut(
                warehouseId: (int) $rmWarehouseId,
                itemId: (int) $line->material_item_id,
                qty: $issuedQty,
                date: $pickup->date,
                sourceType: JournalService::SRC_SEWING_PICKUP_SUPPLY,
                sourceId: (int) $pickup->id,
                notes: "Kelengkapan jahit {$pickup->code} - " . ($material?->code ?: 'material')
                    . ($pendingCost ? ' [pending GRN]' : ''),
                allowNegative: true,   // izinkan minus — muncul di dashboard
                lotId: null,
                unitCostOverride: $pendingCost ? null : $unitCost,
                affectLotCost: false,
            );
        }
    }

    private function allocateSewingSupplyCostToPickupWip(SewingPickup $pickup): void
    {
        $supplyMutations = DB::table('inventory_mutations')
            ->where('source_type', JournalService::SRC_SEWING_PICKUP_SUPPLY)
            ->where('source_id', $pickup->id)
            ->where('qty_change', '<', 0)
            ->get()
            ->keyBy('item_id');
        if ($supplyMutations->isEmpty()) return;

        $incoming = DB::table('inventory_mutations')
            ->where('source_type', SewingPickup::class)
            ->where('source_id', $pickup->id)
            ->where('warehouse_id', $pickup->warehouse_id)
            ->where('qty_change', '>', 0)
            ->lockForUpdate()
            ->get();
        foreach ($incoming as $mutation) {
            $pickupLine = SewingPickupLine::query()
                ->where('sewing_pickup_id', $pickup->id)
                ->where('cutting_job_bundle_id', $mutation->cutting_job_bundle_id)
                ->first();
            if (!$pickupLine || (float) $pickupLine->qty_bundle <= 0) continue;

            $lineSupplyCost = SewingPickupLineSupplyLine::query()
                ->where('sewing_pickup_line_id', $pickupLine->id)
                ->get()
                ->sum(function ($supply) use ($supplyMutations) {
                    $source = $supplyMutations->get((int) $supply->material_item_id);
                    return (float) $supply->issued_qty * (float) ($source?->unit_cost ?? 0);
                });
            if ($lineSupplyCost <= 0) continue;

            $supplyUnitCost = $lineSupplyCost / (float) $pickupLine->qty_bundle;
            $baseUnitCost = (float) ($mutation->unit_cost ?? 0);
            $newUnitCost = $baseUnitCost + $supplyUnitCost;
            DB::table('inventory_mutations')->where('id', $mutation->id)->update([
                'unit_cost' => $newUnitCost,
                'total_cost' => $newUnitCost * (float) $mutation->qty_change,
            ]);
        }
    }

    private function buildSewingSupplyChecklist($bundles): array
    {
        $finishedItemIds = $bundles
            ->pluck('finished_item_id')
            ->filter()
            ->unique()
            ->values();

        if ($finishedItemIds->isEmpty()) {
            return [];
        }

        $boms = ItemBom::query()
            ->whereIn('item_id', $finishedItemIds)
            ->where('active', true)
            ->with(['lines.material.role'])
            ->get();

        $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');
        $stockCache = [];
        $result = [];

        foreach ($boms as $bom) {
            $supplies = $bom->lines
                ->filter(fn($line) => $this->isSewingSupplyBomLine($line))
                ->map(function ($line) use ($rmWarehouseId, &$stockCache) {
                    $material = $line->material;
                    $materialId = (int) $material->id;

                    if (!array_key_exists($materialId, $stockCache)) {
                        $stockCache[$materialId] = $rmWarehouseId
                            ? $this->inventory->getOnHandQty((int) $rmWarehouseId, $materialId)
                            : 0.0;
                    }

                    return [
                        'id' => $materialId,
                        'code' => (string) $material->code,
                        'name' => (string) $material->name,
                        'qty' => (float) $line->qty,
                        'uom' => (string) ($line->uom ?: $material->unit ?: 'pcs'),
                        'optional' => (bool) $line->is_optional,
                        'stock_available' => (float) $stockCache[$materialId],
                    ];
                })
                ->values()
                ->all();

            $result[(int) $bom->item_id] = $supplies;
        }

        return $result;
    }

    public function editSupplies(SewingPickup $pickup): View
    {
        $pickup->load([
            'operator:id,code,name',
            'warehouse:id,code,name',
            'lines.finishedItem:id,code,name',
            'supplyLines.material:id,code,name,unit',
        ]);

        // Auto-seed supply lines dari BOM jika pickup ini belum punya sama sekali
        if ($pickup->supplyLines->isEmpty() && $pickup->lines->isNotEmpty()) {
            $qtyByItem = $pickup->lines
                ->whereNull('voided_at')
                ->groupBy('finished_item_id')
                ->map(fn($g) => $g->sum('qty_bundle'))
                ->toArray();

            if (!empty($qtyByItem)) {
                $this->storeSewingPickupSupplies($pickup, $qtyByItem, null);
                $pickup->load('supplyLines.material:id,code,name,unit');
            }
        }

        // Hitung kebutuhan material per pickup line (per bundle) dari BOM
        $activeLines = $pickup->lines->whereNull('voided_at')->values();
        $finishedItemIds = $activeLines->pluck('finished_item_id')->unique()->values()->all();

        $bomsByItem = \App\Models\ItemBom::query()
            ->whereIn('item_id', $finishedItemIds)
            ->where('active', true)
            ->with(['lines.material'])
            ->get()
            ->keyBy('item_id');

        // supplyLines index by material_item_id → id (untuk hidden input)
        $supplyLineIdByMaterial = $pickup->supplyLines
            ->keyBy('material_item_id')
            ->map(fn($sl) => $sl->id);

        // issued_pcs saat ini per supply_line_id
        $issuedPcsBySupplyLine = $pickup->supplyLines
            ->keyBy('id')
            ->map(fn($sl) => (float) ($sl->issued_pcs ?? 0));

        // Per pickup line: daftar material yang dibutuhkan
        $bundleRequirements = $activeLines->map(function ($line) use ($bomsByItem) {
            $bom = $bomsByItem[(int) $line->finished_item_id] ?? null;
            $materials = [];
            if ($bom) {
                foreach ($bom->lines as $bomLine) {
                    if (!$this->isSewingSupplyBomLine($bomLine)) continue;
                    $materials[] = [
                        'material_item_id' => (int) $bomLine->material_item_id,
                        'code'             => (string) ($bomLine->material?->code ?? '-'),
                        'name'             => (string) ($bomLine->material?->name ?? ''),
                        'qty_per_pcs'      => (float) $bomLine->qty,
                        'required_qty'     => (float) $bomLine->qty * (float) $line->qty_bundle,
                        'uom'              => (string) ($bomLine->uom ?: $bomLine->material?->unit ?: ''),
                        'required_pcs'     => (int) $line->qty_bundle,
                    ];
                }
            }
            return [
                'id'              => $line->id,
                'code'            => strtoupper($line->finishedItem?->code ?? 'ITEM-' . $line->finished_item_id),
                'qty'             => (int) $line->qty_bundle,
                'finished_item_id'=> (int) $line->finished_item_id,
                'materials'       => $materials,
            ];
        })->filter(fn($b) => count($b['materials']) > 0)->values();

        // Filter per pickup line jika ada ?line_id= di URL (tampilkan satu bundle saja)
        $filterLineId = (int) request('line_id', 0);
        if ($filterLineId > 0) {
            $bundleRequirements = $bundleRequirements
                ->filter(fn($b) => (int) $b['id'] === $filterLineId)
                ->values();
        }

        // Stok RM real-time per material → untuk guard input
        $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');
        $allMaterialIds = $bundleRequirements
            ->flatMap(fn($b) => collect($b['materials'])->pluck('material_item_id'))
            ->unique()->values()->all();

        $rmStockByMaterial = [];
        if ($rmWarehouseId && !empty($allMaterialIds)) {
            $rmStockByMaterial = \App\Models\InventoryStock::query()
                ->where('warehouse_id', $rmWarehouseId)
                ->whereIn('item_id', $allMaterialIds)
                ->pluck('qty', 'item_id')
                ->map(fn($v) => (float) $v)
                ->toArray();
        }

        // Hitung max_pcs per material (berdasarkan stok RM ÷ qty_per_pcs)
        // Disimpan sebagai: material_item_id → max_pcs (int)
        $maxPcsByMaterial = [];
        foreach ($bundleRequirements as $bundle) {
            foreach ($bundle['materials'] as $mat) {
                $mid = (int) $mat['material_item_id'];
                if (isset($maxPcsByMaterial[$mid])) continue; // sudah dihitung
                $stockQty   = (float) ($rmStockByMaterial[$mid] ?? 0);
                $qtyPerPcs  = (float) ($mat['qty_per_pcs'] ?? 0);
                $maxPcsByMaterial[$mid] = $qtyPerPcs > 0
                    ? (int) floor($stockQty / $qtyPerPcs)
                    : ($stockQty > 0 ? PHP_INT_MAX : 0);
            }
        }

        return view('production.sewing_pickups.supplies', [
            'pickup'                 => $pickup,
            'bundleRequirements'     => $bundleRequirements,
            'supplyLineIdByMaterial' => $supplyLineIdByMaterial,
            'issuedPcsBySupplyLine'  => $issuedPcsBySupplyLine,
            'filterLineId'           => $filterLineId,
            'rmStockByMaterial'      => $rmStockByMaterial,
            'maxPcsByMaterial'       => $maxPcsByMaterial,
        ]);
    }

    public function updateSupplies(Request $request, SewingPickup $pickup)
    {
        if (DB::table('inventory_mutations')
            ->where('source_type', JournalService::SRC_SEWING_PICKUP_SUPPLY)
            ->where('source_id', $pickup->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'supplies' => 'Kelengkapan sudah mengurangi stok RM dan tidak boleh diedit. Void pickup lalu buat ulang jika qty salah.',
            ]);
        }

        $validated = $request->validate([
            'supplies' => ['nullable', 'array'],
            'supplies.*.issued_pcs' => ['nullable', 'numeric', 'min:0'],
            'line_supplies' => ['nullable', 'array'],
            'line_supplies.*.*.issued_pcs' => ['nullable', 'numeric', 'min:0'],
            'line_supplies.*.*.issued_qty' => ['nullable', 'numeric', 'min:0'],
            'line_supplies.*.*.required_qty' => ['nullable', 'numeric', 'min:0'],
            'line_supplies.*.*.qty_per_pcs' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Stok RM real-time untuk validasi server-side
        $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');

        DB::transaction(function () use ($pickup, $validated, $rmWarehouseId) {
            $pickup->load('supplyLines');

            foreach ($pickup->supplyLines as $line) {
                // Hanya update baris yang explicitly di-submit (skip jika tidak ada di request)
                if (!isset($validated['supplies'][$line->id])) continue;

                $input       = $validated['supplies'][$line->id]['issued_pcs'] ?? 0;
                $issuedPcs   = max((float) $input, 0);
                $requiredPcs = (float) ($line->required_pcs ?? 0);
                $requiredQty = (float) ($line->required_qty ?? 0);
                $qtyPerPiece = $requiredPcs > 0 ? ($requiredQty / $requiredPcs) : 0;

                // Guard: issued tidak boleh melebihi stok RM yang tersedia
                if ($rmWarehouseId && $qtyPerPiece > 0) {
                    $rmStock = (float) (\App\Models\InventoryStock::query()
                        ->where('warehouse_id', $rmWarehouseId)
                        ->where('item_id', (int) $line->material_item_id)
                        ->value('qty') ?? 0);
                    $maxPcs = $rmStock > 0 ? (int) floor($rmStock / $qtyPerPiece) : 0;
                    if ($issuedPcs > $maxPcs) {
                        $matCode = $line->material?->code ?? "material #{$line->material_item_id}";
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "supplies.{$line->id}" => "Qty untuk {$matCode} melebihi stok RM yang tersedia ({$maxPcs} pcs dari stok {$rmStock}).",
                        ]);
                    }
                }

                $line->issued_pcs = $issuedPcs;
                $line->issued_qty = $qtyPerPiece > 0 ? ($issuedPcs * $qtyPerPiece) : $issuedPcs;
                $line->save();
            }

            foreach (($validated['line_supplies'] ?? []) as $pickupLineId => $materials) {
                $pickupLine = SewingPickupLine::query()
                    ->where('sewing_pickup_id', $pickup->id)
                    ->whereKey((int) $pickupLineId)
                    ->first();

                if (!$pickupLine) {
                    continue;
                }

                foreach ((array) $materials as $materialId => $row) {
                    $materialId = (int) $materialId;
                    if ($materialId <= 0) {
                        continue;
                    }

                    $issuedQty = array_key_exists('issued_pcs', $row)
                        ? max((float) ($row['issued_pcs'] ?? 0), 0) * max((float) ($row['qty_per_pcs'] ?? 0), 0)
                        : max((float) ($row['issued_qty'] ?? 0), 0);

                    SewingPickupLineSupplyLine::updateOrCreate(
                        [
                            'sewing_pickup_line_id' => (int) $pickupLine->id,
                            'material_item_id' => $materialId,
                        ],
                        [
                            'sewing_pickup_id' => (int) $pickup->id,
                            'required_qty' => max((float) ($row['required_qty'] ?? 0), 0),
                            'issued_qty' => $issuedQty,
                        ]
                    );
                }
            }

            // Sync per-bundle line supply records from aggregate supply lines.
            // Diperlukan ketika visible inputs (disabled) tidak ter-submit — issued_qty
            // dihitung proporsional dari aggregate issued_qty per bundle.
            $pickup->loadMissing('lines');
            $activeLines   = $pickup->lines->whereNull('voided_at');
            $totalBundleQty = (float) $activeLines->sum('qty_bundle');
            $updatedSupplyLines = $pickup->supplyLines->keyBy('material_item_id');

            foreach ($activeLines as $pickupLine) {
                $bundleQty = (float) ($pickupLine->qty_bundle ?? 0);
                $share     = $totalBundleQty > 0.0001 ? $bundleQty / $totalBundleQty : 1.0;

                foreach ($updatedSupplyLines as $sl) {
                    if ((float) ($sl->issued_qty ?? 0) <= 0 && (float) ($sl->required_qty ?? 0) <= 0) {
                        continue;
                    }
                    $issuedQtyForLine   = round((float) ($sl->issued_qty ?? 0) * $share, 6);
                    $requiredQtyForLine = round((float) ($sl->required_qty ?? 0) * $share, 6);

                    SewingPickupLineSupplyLine::updateOrCreate(
                        [
                            'sewing_pickup_line_id' => (int) $pickupLine->id,
                            'material_item_id'      => (int) $sl->material_item_id,
                        ],
                        [
                            'sewing_pickup_id' => $pickup->id,
                            'required_qty'     => $requiredQtyForLine,
                            'issued_qty'       => $issuedQtyForLine,
                        ]
                    );
                }
            }
        });

        // Deduct RM stock (sama seperti store()) — hanya jalan sekali karena guard di atas
        // memblokir edit setelah mutasi ada.
        $pickup->load('supplyLines.material');
        $this->issueSewingPickupSupplies($pickup);
        $this->allocateSewingSupplyCostToPickupWip($pickup);

        try {
            $this->journal->postSewingPickupSupply($pickup);
        } catch (\Throwable $e) {
            Log::warning('Gagal membuat jurnal kelengkapan sewing pickup (updateSupplies)', [
                'sewing_pickup_id' => $pickup->id,
                'message'          => $e->getMessage(),
            ]);
        }

        // AJAX request → return JSON
        if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['ok' => true, 'message' => 'Kelengkapan jahit tersimpan.']);
        }

        // Kalau ada redirect_to (misal dari halaman Sewing Return), kembali ke sana
        $redirectTo = $request->input('redirect_to');
        if ($redirectTo && str_starts_with($redirectTo, '/')) {
            return redirect($redirectTo)->with('success', 'Kelengkapan jahit tersimpan.');
        }

        return redirect()
            ->route('production.sewing.pickups.show', $pickup)
            ->with('success', 'Kelengkapan jahit tersimpan.');
    }

    public function updateLineSupplies(Request $request, SewingPickupLine $line)
    {
        if (DB::table('inventory_mutations')
            ->where('source_type', JournalService::SRC_SEWING_PICKUP_SUPPLY)
            ->where('source_id', $line->sewing_pickup_id)
            ->exists()) {
            throw ValidationException::withMessages([
                'supplies' => 'Kelengkapan sudah mengurangi stok RM dan tidak boleh diedit. Void baris pickup jika perlu dikoreksi.',
            ]);
        }

        $validated = $request->validate([
            'supplies' => ['nullable', 'array'],
            'supplies.*.issued_pcs' => ['nullable', 'numeric', 'min:0'],
            'supplies.*.issued_qty' => ['nullable', 'numeric', 'min:0'],
            'supplies.*.required_qty' => ['nullable', 'numeric', 'min:0'],
            'supplies.*.qty_per_pcs' => ['nullable', 'numeric', 'min:0'],
            'supplies.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $isOwner = strtolower((string) (auth()->user()->role ?? '')) === 'owner';
        $supplies = $validated['supplies'] ?? [];

        DB::transaction(function () use ($line, $supplies, $isOwner) {
            $line = SewingPickupLine::query()
                ->whereKey($line->id)
                ->lockForUpdate()
                ->firstOrFail();

            foreach ($supplies as $materialId => $row) {
                $materialId = (int) $materialId;
                if ($materialId <= 0 || !Item::query()->whereKey($materialId)->exists()) {
                    throw ValidationException::withMessages([
                        'supplies' => 'Material kelengkapan jahit tidak valid.',
                    ]);
                }

                $requiredQty = max((float) ($row['required_qty'] ?? 0), 0);
                $issuedQty = array_key_exists('issued_pcs', $row)
                    ? max((float) ($row['issued_pcs'] ?? 0), 0) * max((float) ($row['qty_per_pcs'] ?? 0), 0)
                    : max((float) ($row['issued_qty'] ?? 0), 0);

                $existing = SewingPickupLineSupplyLine::query()
                    ->where('sewing_pickup_line_id', $line->id)
                    ->where('material_item_id', $materialId)
                    ->lockForUpdate()
                    ->first();

                if ($existing && $requiredQty <= 0) {
                    $requiredQty = (float) ($existing->required_qty ?? 0);
                }

                if (!$isOwner && $issuedQty > $requiredQty + 0.000001) {
                    throw ValidationException::withMessages([
                        'supplies' => 'Qty dibawa tidak boleh lebih dari kebutuhan bundle.',
                    ]);
                }

                SewingPickupLineSupplyLine::updateOrCreate(
                    [
                        'sewing_pickup_line_id' => $line->id,
                        'material_item_id' => $materialId,
                    ],
                    [
                        'sewing_pickup_id' => (int) $line->sewing_pickup_id,
                        'required_qty' => $requiredQty,
                        'issued_qty' => $issuedQty,
                        'uom' => $existing?->uom,
                        'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
                    ]
                );
            }
        });

        if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['ok' => true, 'message' => 'Kelengkapan bundle tersimpan.']);
        }

        return back()->with('success', 'Kelengkapan bundle tersimpan.');
    }

    private function storeSewingPickupSupplies(SewingPickup $pickup, array $qtyByFinishedItem, ?string $submittedPayload): void
    {
        $requirements = $this->buildSewingSupplyRequirements($qtyByFinishedItem);
        if (empty($requirements)) {
            return;
        }

        $submittedItems = [];
        $decoded = $submittedPayload ? json_decode($submittedPayload, true) : null;
        $hasSubmittedPayload = is_array($decoded);
        if (is_array($decoded)) {
            foreach (($decoded['items'] ?? []) as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $materialId = (int) ($item['id'] ?? 0);
                if ($materialId <= 0) {
                    continue;
                }

                $submittedItems[$materialId] = [
                    'issued_qty' => max((float) ($item['issued_qty'] ?? $item['issuedQty'] ?? 0), 0),
                    'issued_pcs' => max((float) ($item['issued_pcs'] ?? $item['issuedPieces'] ?? 0), 0),
                ];
            }
        }

        foreach ($requirements as $materialId => $row) {
            $need = (float) $row['need'];
            $requiredPcs = (float) ($row['total_pieces'] ?? 0);
            $requestedQty = $hasSubmittedPayload
                ? min((float) ($submittedItems[$materialId]['issued_qty'] ?? 0), $need)
                : $need;
            $requestedPcs = $hasSubmittedPayload
                ? min((float) ($submittedItems[$materialId]['issued_pcs'] ?? 0), $requiredPcs)
                : $requiredPcs;
            $available = max((float) ($row['stock_available'] ?? 0), 0);
            $issuedQty = min($requestedQty, $available);
            $issuedPcs = $requestedQty > 0
                ? min($requestedPcs * ($issuedQty / $requestedQty), $requiredPcs)
                : 0;

            SewingPickupSupplyLine::updateOrCreate(
                [
                    'sewing_pickup_id' => $pickup->id,
                    'material_item_id' => (int) $materialId,
                ],
                [
                    'required_qty' => $need,
                    'issued_qty' => $issuedQty,
                    'required_pcs' => $requiredPcs,
                    'issued_pcs' => $issuedPcs,
                    'uom' => $row['uom'] ?: null,
                    'stock_available_snapshot' => (float) ($row['stock_available'] ?? 0),
                ]
            );
        }
    }

    private function syncLineSupplyIssuedFromAggregate(SewingPickup $pickup): void
    {
        $remainingByMaterial = $pickup->supplyLines()
            ->pluck('issued_qty', 'material_item_id')
            ->map(fn($qty) => (float) $qty)
            ->all();

        $lines = SewingPickupLineSupplyLine::query()
            ->where('sewing_pickup_id', $pickup->id)
            ->orderBy('id')
            ->get();

        foreach ($lines as $line) {
            $materialId = (int) $line->material_item_id;
            $available = max((float) ($remainingByMaterial[$materialId] ?? 0), 0);
            $issued = min($available, (float) $line->required_qty);
            $line->issued_qty = $issued;
            $line->save();
            $remainingByMaterial[$materialId] = max($available - $issued, 0);
        }
    }

    /**
     * Naikkan issued_qty supply lines ke qty yang dikonfirmasi fisik operator
     * lewat checklist, meskipun stok sistem tidak mencukupi.
     * Gap-nya sudah didokumentasikan lebih dulu oleh createSupplyPhysicalAdjustment().
     */
    private function applyConfirmedPhysicalToSupplyLines(SewingPickup $pickup, ?string $submittedPayload): void
    {
        $requested = $this->parseSubmittedSewingSupplies($submittedPayload);
        if (empty($requested)) {
            return;
        }

        foreach ($pickup->supplyLines()->get() as $supply) {
            $confirmed = min(
                (float) ($requested[(int) $supply->material_item_id]['issued_qty'] ?? 0),
                (float) $supply->required_qty
            );

            if ($confirmed <= (float) $supply->issued_qty + 0.000001) {
                continue;
            }

            $requiredQty = (float) $supply->required_qty;
            $requiredPcs = (float) ($supply->required_pcs ?? 0);

            $supply->issued_qty = $confirmed;
            if ($requiredQty > 0 && $requiredPcs > 0) {
                $supply->issued_pcs = min($requiredPcs * ($confirmed / $requiredQty), $requiredPcs);
            }
            $supply->save();
        }
    }

    private function createSupplyPhysicalAdjustment(
        SewingPickup $pickup,
        ?string $submittedPayload,
        int $userId
    ): ?InventoryAdjustment {
        $requested = $this->parseSubmittedSewingSupplies($submittedPayload);
        if (empty($requested)) {
            return null;
        }

        $lines = [];
        foreach ($pickup->supplyLines()->get() as $supply) {
            $target = min(
                (float) ($requested[(int) $supply->material_item_id]['issued_qty'] ?? 0),
                (float) $supply->required_qty
            );
            $missingPhysical = max($target - (float) $supply->issued_qty, 0);
            if ($missingPhysical <= 0.000001) continue;

            $lines[] = [
                'item_id' => (int) $supply->material_item_id,
                'qty_change' => $missingPhysical,
                'direction' => 'in',
                'notes' => "Ditemukan fisik untuk kelengkapan pickup {$pickup->code}",
            ];
        }
        if (empty($lines)) {
            return null;
        }

        $date = $pickup->date?->toDateString() ?? now()->toDateString();
        $prefix = 'ADJ-' . str_replace('-', '', $date) . '-';
        $lastCode = InventoryAdjustment::query()
            ->where('code', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');
        $sequence = $lastCode ? ((int) substr($lastCode, -3)) + 1 : 1;

        $adjustment = InventoryAdjustment::create([
            'code' => $prefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
            'date' => $date,
            'warehouse_id' => (int) Warehouse::query()->where('code', 'RM')->value('id'),
            'reason' => 'Stok fisik kelengkapan jahit ditemukan',
            'notes' => "Dibuat otomatis dari pickup {$pickup->code}. Wajib cek fisik sebelum approve.",
            'status' => InventoryAdjustment::STATUS_PENDING,
            'created_by' => $userId,
            'reference_type' => SewingPickup::class,
            'reference_id' => (int) $pickup->id,
        ]);
        $adjustment->lines()->createMany($lines);

        return $adjustment;
    }

    private function storeSewingPickupLineSupplies(SewingPickup $pickup, $pickupLines, ?string $submittedPayload): void
    {
        $pickupLines = collect($pickupLines)->filter();
        if ($pickupLines->isEmpty()) {
            return;
        }

        $submittedItems = $this->parseSubmittedSewingSupplies($submittedPayload);
        $submittedBundles = $this->parseSubmittedSewingSupplyBundles($submittedPayload);
        $remainingIssuedByMaterial = collect($submittedItems)
            ->map(fn($row) => (float) ($row['issued_qty'] ?? 0))
            ->toArray();

        $finishedItemIds = $pickupLines->pluck('finished_item_id')->filter()->unique()->values()->all();
        if (empty($finishedItemIds)) {
            return;
        }

        $bomsByItem = ItemBom::query()
            ->whereIn('item_id', $finishedItemIds)
            ->where('active', true)
            ->with(['lines.material'])
            ->get()
            ->keyBy('item_id');

        foreach ($pickupLines as $line) {
            $bom = $bomsByItem->get((int) $line->finished_item_id);
            if (!$bom) {
                continue;
            }

            foreach ($bom->lines as $bomLine) {
                if (!$this->isSewingSupplyBomLine($bomLine)) {
                    continue;
                }

                $materialId = (int) ($bomLine->material_item_id ?? 0);
                if ($materialId <= 0) {
                    continue;
                }

                $requiredQty = (float) ($line->qty_bundle ?? 0) * (float) ($bomLine->qty ?? 0);
                $submittedBundleSupply = $submittedBundles[(int) ($line->cutting_job_bundle_id ?? 0)][$materialId] ?? null;

                if ($submittedBundleSupply) {
                    $issuedQty = min((float) ($submittedBundleSupply['issued_qty'] ?? 0), $requiredQty);
                } elseif (array_key_exists($materialId, $remainingIssuedByMaterial)) {
                    $issuedQty = min((float) ($remainingIssuedByMaterial[$materialId] ?? 0), $requiredQty);
                    $remainingIssuedByMaterial[$materialId] = max((float) ($remainingIssuedByMaterial[$materialId] ?? 0) - $issuedQty, 0);
                } else {
                    $issuedQty = $requiredQty;
                }

                SewingPickupLineSupplyLine::updateOrCreate(
                    [
                        'sewing_pickup_line_id' => (int) $line->id,
                        'material_item_id' => $materialId,
                    ],
                    [
                        'sewing_pickup_id' => (int) $pickup->id,
                        'required_qty' => $requiredQty,
                        'issued_qty' => $issuedQty,
                        'uom' => $bomLine->uom ?: ($bomLine->material?->unit ?: null),
                    ]
                );
            }
        }
    }

    private function parseSubmittedSewingSupplies(?string $payload): array
    {
        $submittedItems = [];
        $decoded = $payload ? json_decode($payload, true) : null;
        if (!is_array($decoded)) {
            return $submittedItems;
        }

        foreach (($decoded['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $materialId = (int) ($item['id'] ?? 0);
            if ($materialId <= 0) {
                continue;
            }

            $submittedItems[$materialId] = [
                'issued_qty' => max((float) ($item['issued_qty'] ?? $item['issuedQty'] ?? 0), 0),
                'issued_pcs' => max((float) ($item['issued_pcs'] ?? $item['issuedPieces'] ?? 0), 0),
            ];
        }

        return $submittedItems;
    }

    private function parseSubmittedSewingSupplyBundles(?string $payload): array
    {
        $result = [];
        $decoded = $payload ? json_decode($payload, true) : null;
        if (!is_array($decoded)) {
            return $result;
        }

        foreach (($decoded['bundles'] ?? []) as $bundle) {
            if (!is_array($bundle)) {
                continue;
            }

            $bundleId = (int) ($bundle['bundle_id'] ?? 0);
            if ($bundleId <= 0) {
                continue;
            }

            foreach (($bundle['supplies'] ?? []) as $supply) {
                if (!is_array($supply)) {
                    continue;
                }

                $materialId = (int) ($supply['id'] ?? 0);
                if ($materialId <= 0) {
                    continue;
                }

                $qtyPerPiece = max((float) ($supply['qty_per_piece'] ?? $supply['qtyPerPiece'] ?? 0), 0);
                $issuedPcs = max((float) ($supply['issued_pcs'] ?? $supply['issuedPieces'] ?? 0), 0);
                $requiredPcs = max((float) ($supply['required_pcs'] ?? $supply['requiredPieces'] ?? 0), 0);

                $result[$bundleId][$materialId] = [
                    'issued_qty' => array_key_exists('issued_qty', $supply)
                        ? max((float) $supply['issued_qty'], 0)
                        : ($issuedPcs * $qtyPerPiece),
                    'required_qty' => array_key_exists('required_qty', $supply)
                        ? max((float) $supply['required_qty'], 0)
                        : ($requiredPcs * $qtyPerPiece),
                ];
            }
        }

        return $result;
    }

    private function buildSewingSupplyRequirements(array $qtyByFinishedItem): array
    {
        if (empty($qtyByFinishedItem)) {
            return [];
        }

        $boms = ItemBom::query()
            ->whereIn('item_id', array_keys($qtyByFinishedItem))
            ->where('active', true)
            ->with(['lines.material.role'])
            ->get();

        $requirements = [];
        foreach ($boms as $bom) {
            $pickupQty = (float) ($qtyByFinishedItem[(int) $bom->item_id] ?? 0);
            if ($pickupQty <= 0) {
                continue;
            }

            foreach ($bom->lines as $line) {
                $material = $line->material;
                if (!$this->isSewingSupplyBomLine($line)) {
                    continue;
                }

                $materialId = (int) $material->id;
                if (!isset($requirements[$materialId])) {
                    $requirements[$materialId] = [
                        'code' => (string) $material->code,
                        'name' => (string) $material->name,
                        'uom' => (string) ($line->uom ?: $material->unit ?: 'pcs'),
                        'need' => 0.0,
                        'total_pieces' => 0.0,
                    ];
                }

                $requirements[$materialId]['need'] += $pickupQty * (float) $line->qty;
                $requirements[$materialId]['total_pieces'] += $pickupQty;
            }
        }

        if (empty($requirements)) {
            return [];
        }

        $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');
        foreach ($requirements as $materialId => $row) {
            $requirements[$materialId]['stock_available'] = $rmWarehouseId
                ? $this->inventory->getOnHandQty((int) $rmWarehouseId, (int) $materialId)
                : 0.0;
        }

        return $requirements;
    }

    private function isSewingSupplyMaterial($material): bool
    {
        if (!$material) {
            return false;
        }

        $roleCode = $material->role_code;
        $legacyRole = (string) ($material->item_role ?? '');
        $code = strtoupper((string) ($material->code ?? ''));

        return $roleCode === ItemRole::SUP
            || $legacyRole === 'production_supply'
            || str_starts_with($code, 'RIB')
            || str_starts_with($code, 'KRT')
            || str_starts_with($code, 'TLK')
            || str_starts_with($code, 'OPP');
    }

    private function isSewingSupplyBomLine($line): bool
    {
        if (!$line || !$line->material) {
            return false;
        }

        if ((bool) ($line->is_optional ?? false)) {
            return false;
        }

        $stage = (string) ($line->usage_stage ?? '');
        if ($stage !== '') {
            return $stage === ItemBomLine::STAGE_SEWING_SUPPLY;
        }

        return $this->isSewingSupplyMaterial($line->material);
    }

    private function formatSuppliesChecklistNote(?string $payload): string
    {
        if (!$payload) {
            return '';
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return '';
        }

        $items = collect($decoded['items'] ?? [])
            ->filter(fn($item) => is_array($item) && !empty($item['code']))
            ->map(function ($item) {
                $qty = isset($item['qty']) ? (float) $item['qty'] : 0;
                $issuedQty = isset($item['issued_qty']) ? (float) $item['issued_qty'] : (float) ($item['issuedQty'] ?? 0);
                $requiredPcs = isset($item['totalPieces']) ? (float) $item['totalPieces'] : 0;
                $issuedPcs = isset($item['issued_pcs']) ? (float) $item['issued_pcs'] : (float) ($item['issuedPieces'] ?? 0);
                $uom = trim((string) ($item['uom'] ?? ''));

                if ($requiredPcs > 0) {
                    $qtyLabel = ' - butuh ' . rtrim(rtrim(number_format($requiredPcs, 2, '.', ''), '0'), '.') . ' pcs'
                        . ', dibawa ' . rtrim(rtrim(number_format($issuedPcs, 2, '.', ''), '0'), '.') . ' pcs'
                        . ($qty > 0 ? ' (BOM ' . rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.') . ($uom ? ' ' . $uom : '') . ')' : '');
                } else {
                    $qtyLabel = $qty > 0
                        ? ' - butuh ' . rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.') . ($uom ? ' ' . $uom : '')
                        . ', dibawa ' . rtrim(rtrim(number_format($issuedQty, 4, '.', ''), '0'), '.') . ($uom ? ' ' . $uom : '')
                        : '';
                }

                return '- ' . $item['code'] . $qtyLabel;
            })
            ->values();

        if ($items->isEmpty()) {
            return '';
        }

        return "Bahan pendukung ambil jahit:\n" . $items->implode("\n");
    }

    /**
     * AJAX: filter bundles ready untuk picker.
     */
    public function ajaxReadyBundles(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $color = trim((string) $request->get('color', ''));
        $size = trim((string) $request->get('size', ''));
        $minReady = $request->get('min_ready');
        $maxReady = $request->get('max_ready');

        $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');

        $bundlesQuery = CuttingJobBundle::with([
            'finishedItem',
            'cuttingJob.lot.item',
            'qcResults',
        ])
            ->readyForSewing($wipCutWarehouseId);

        // SEARCH TEXT
        if ($q !== '') {
            $term = '%' . $q . '%';

            $bundlesQuery->where(function ($qq) use ($term) {
                $qq->where('bundle_code', 'like', $term)
                    ->orWhereHas('finishedItem', function ($q2) use ($term) {
                        $q2->where('code', 'like', $term)
                            ->orWhere('name', 'like', $term);
                    })
                    ->orWhereHas('cuttingJob.lot', function ($q2) use ($term) {
                        $q2->where('code', 'like', $term);
                    })
                    ->orWhereHas('cuttingJob.lot.item', function ($q2) use ($term) {
                        $q2->where('code', 'like', $term)
                            ->orWhere('name', 'like', $term);
                    });
            });
        }

        // FILTER WARNA
        if ($color !== '') {
            $bundlesQuery->whereHas('finishedItem', function ($q2) use ($color) {
                $q2->where('color_code', $color); // sesuaikan field warna
            });
        }

        // FILTER UKURAN
        if ($size !== '') {
            $bundlesQuery->whereHas('finishedItem', function ($q2) use ($size) {
                $q2->where('size_code', $size); // sesuaikan field size
            });
        }

        $bundles = $bundlesQuery
            ->orderBy('id')
            ->get();

        $minReadyF = is_null($minReady) || $minReady === '' ? null : (float) $minReady;
        $maxReadyF = is_null($maxReady) || $maxReady === '' ? null : (float) $maxReady;

        $displayBundles = $bundles->filter(function (CuttingJobBundle $b) use ($minReadyF, $maxReadyF) {
            $qtyRemain = (float) $b->qty_ready_for_sewing;

            if ($qtyRemain <= 0) {
                return false;
            }

            if (!is_null($minReadyF) && $qtyRemain < $minReadyF) {
                return false;
            }

            if (!is_null($maxReadyF) && $qtyRemain > $maxReadyF) {
                return false;
            }

            $b->computed_qty_remain = $qtyRemain;

            return true;
        })->values();

        $totalBundlesReady = $displayBundles->count();
        $totalQtyReady = $displayBundles->sum(function ($b) {
            return (float) ($b->computed_qty_remain ?? 0);
        });

        $html = view('production.sewing.pickups._bundle_picker_rows', [
            'displayBundles' => $displayBundles,
            'oldLines' => [],
            'preselectedBundleId' => null,
        ])->render();

        return response()->json([
            'html' => $html,
            'total_bundles' => $totalBundlesReady,
            'total_ready' => $totalQtyReady,
            'total_ready_formatted' => number_format($totalQtyReady, 2, ',', '.'),
        ]);
    }

    public function void(Request $request, SewingPickup $pickup)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:150'],
        ]);

        $pickupId = (int) $pickup->id;
        $epsilon = 0.000001;

        DB::transaction(function () use ($pickupId, $validated, $epsilon) {

            $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');
            if (!$wipCutWarehouseId) {
                throw ValidationException::withMessages(['pickup' => 'Gudang WIP-CUT belum dikonfigurasi.']);
            }

            /** @var SewingPickup $pickup */
            $pickup = SewingPickup::with('lines')
                ->lockForUpdate()
                ->findOrFail($pickupId);

            if ($pickup->status === 'void') {
                throw ValidationException::withMessages(['pickup' => 'Pickup sudah di-VOID.']);
            }

            // ✅ CHECK NGACU KE sewing_pickup_lines:
            $hasUsedLine = $pickup->lines->contains(function ($line) use ($epsilon) {
                $used =
                (float) ($line->qty_returned_ok ?? 0) +
                (float) ($line->qty_returned_reject ?? 0) +
                (float) ($line->qty_direct_picked ?? 0) +
                (float) ($line->qty_progress_adjusted ?? 0);

                return $used > $epsilon;
            });

            if ($hasUsedLine) {
                throw ValidationException::withMessages([
                    'pickup' => 'Tidak bisa VOID. Ada line pickup yang sudah setor/proses (OK/RJ/DP/Adj).',
                ]);
            }

            $sewingWarehouseId = (int) $pickup->warehouse_id;
            $date = $pickup->date;

            foreach ($pickup->lines as $line) {
                $qty = (float) ($line->qty_bundle ?? 0);
                if ($qty <= $epsilon) {
                    continue;
                }

                $bundle = CuttingJobBundle::lockForUpdate()->findOrFail((int) $line->cutting_job_bundle_id);

                $notes = "VOID Sewing pickup {$pickup->code} - bundle {$bundle->bundle_code}";

                $unitCost = max((float) ($line->unit_cost ?? 0), 0);
                $wagePerPcs = max((float) ($line->wage_per_pcs ?? 0), 0);
                $materialUnitCost = max($unitCost - $wagePerPcs, 0);
                if ($materialUnitCost <= 0) {
                    $materialUnitCost = $unitCost;
                }

                $this->inventory->stockOut(
                    warehouseId: $sewingWarehouseId,
                    itemId: (int) $line->finished_item_id,
                    qty: $qty,
                    date: $date,
                    sourceType: SewingPickup::class,
                    sourceId: $pickup->id,
                    notes: $notes,
                    allowNegative: false,
                    lotId: null,
                    unitCostOverride: $unitCost,
                    affectLotCost: false,
                    cuttingJobBundleId: $bundle->id,
                );

                $this->inventory->stockIn(
                    warehouseId: (int) $wipCutWarehouseId,
                    itemId: (int) $line->finished_item_id,
                    qty: $qty,
                    date: $date,
                    sourceType: SewingPickup::class,
                    sourceId: $pickup->id,
                    notes: $notes,
                    lotId: null,
                    unitCost: $materialUnitCost,
                    affectLotCost: false,
                    cuttingJobBundleId: $bundle->id,
                );

                $line->status = 'void';
                $line->save();

                // ✅ FIX: recalculate sewing_picked_qty dari SUM(non-void pickup lines).
                // Lebih aman daripada subtraksi — otomatis membenahi data korup.
                $newPicked = (float) SewingPickupLine::query()
                    ->where('cutting_job_bundle_id', $bundle->id)
                    ->where('status', '!=', 'void')
                    ->sum('qty_bundle');
                $bundle->sewing_picked_qty = $newPicked;
                $bundle->save();
            }

            $hasSupplyMutations = DB::table('inventory_mutations')
                ->where('source_type', JournalService::SRC_SEWING_PICKUP_SUPPLY)
                ->where('source_id', $pickup->id)
                ->exists();

            if ($hasSupplyMutations) {
                $this->inventory->reverseBySource(
                    originalSourceTypes: [JournalService::SRC_SEWING_PICKUP_SUPPLY],
                    originalSourceId: (int) $pickup->id,
                    voidSourceType: JournalService::SRC_SEWING_PICKUP_SUPPLY . '_void',
                    voidSourceId: (int) $pickup->id,
                    notesPrefix: "VOID kelengkapan jahit {$pickup->code}",
                    date: $date,
                );
            }

            $pickup->status = 'void';
            $pickup->void_reason = $validated['reason'];
            $pickup->voided_at = now();
            $pickup->voided_by = auth()->id();
            $pickup->save();
        });

        try {
            $this->journal->voidBySource(JournalService::SRC_SEWING_PICKUP, $pickupId, 'VOID Sewing Pickup');
        } catch (\Throwable $e) {
            Log::warning('Gagal void jurnal sewing pickup', [
                'sewing_pickup_id' => $pickupId,
                'message' => $e->getMessage(),
            ]);
        }

        foreach (SewingPickupLine::query()->where('sewing_pickup_id', $pickupId)->pluck('id') as $lineId) {
            try {
                $this->journal->voidBySource(JournalService::SRC_SEWING_PICKUP_WAGE, (int) $lineId, 'VOID Upah Sewing Pickup Line');
            } catch (\Throwable $e) {
                Log::warning('Gagal void jurnal upah sewing pickup line', [
                    'sewing_pickup_line_id' => $lineId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        try {
            $this->journal->voidBySource(JournalService::SRC_SEWING_PICKUP_SUPPLY, $pickupId, 'VOID Kelengkapan Sewing Pickup');
        } catch (\Throwable $e) {
            Log::warning('Gagal void jurnal kelengkapan sewing pickup', [
                'sewing_pickup_id' => $pickupId,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('production.sewing.pickups.show', $pickupId)
            ->with('success', 'Pickup berhasil di-VOID. (Semua line belum setor) Stok dibalik ke WIP-CUT.');
    }

    public function voidLine(Request $request, SewingPickup $pickup, SewingPickupLine $line)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:150'],
        ]);

        // pastikan line memang milik pickup ini (anti-inject URL)
        if ((int) $line->sewing_pickup_id !== (int) $pickup->id) {
            abort(404);
        }

        $pickupId = $pickup->id;
        $lineId = $line->id;

        DB::transaction(function () use ($pickupId, $lineId, $validated) {

            $epsilon = 0.000001;

            $wipCutWarehouseId = Warehouse::where('code', 'WIP-CUT')->value('id');
            if (!$wipCutWarehouseId) {
                throw ValidationException::withMessages([
                    'pickup' => 'Gudang WIP-CUT belum dikonfigurasi.',
                ]);
            }

            // lock pickup
            $pickup = SewingPickup::lockForUpdate()->findOrFail($pickupId);

            // lock line
            $line = SewingPickupLine::lockForUpdate()->findOrFail($lineId);

            if ($line->status === 'void') {
                throw ValidationException::withMessages([
                    'pickup' => "Line sudah di-VOID (line #{$line->id}).",
                ]);
            }

            // validasi belum dipakai proses lain
            $used =
            (float) ($line->qty_returned_ok ?? 0) +
            (float) ($line->qty_returned_reject ?? 0) +
            (float) ($line->qty_direct_picked ?? 0) +
            (float) ($line->qty_progress_adjusted ?? 0);

            if ($used > $epsilon) {
                throw ValidationException::withMessages([
                    'pickup' => "Line sudah dipakai proses lain (line #{$line->id}). Tidak bisa VOID.",
                ]);
            }

            $qty = (float) ($line->qty_bundle ?? 0);
            if ($qty <= $epsilon) {
                // qty 0 biasanya tidak perlu di-void, tapi kalau mau tetap boleh, silakan hapus block ini
                throw ValidationException::withMessages([
                    'pickup' => "Qty line 0, tidak ada yang perlu dibalik (line #{$line->id}).",
                ]);
            }

            $sewingWarehouseId = (int) $pickup->warehouse_id;
            $date = $pickup->date;

            // lock bundle
            $bundle = CuttingJobBundle::lockForUpdate()->findOrFail($line->cutting_job_bundle_id);

            // ✅ FIX: consistency check dihapus — nanti dihitung ulang dari SUM setelah void.
            // Arithmetic decrement dihapus karena rawan bug jika sewing_picked_qty sebelumnya korup.

            $notes = "VOID LINE Sewing pickup {$pickup->code} - bundle {$bundle->bundle_code} - line {$line->id}";

            $unitCost = max((float) ($line->unit_cost ?? 0), 0);
            $wagePerPcs = max((float) ($line->wage_per_pcs ?? 0), 0);
            $materialUnitCost = max($unitCost - $wagePerPcs, 0);
            if ($materialUnitCost <= 0) {
                $materialUnitCost = $unitCost;
            }

            // OUT dari WIP-SEW
            $this->inventory->stockOut(
                warehouseId: $sewingWarehouseId,
                itemId: $line->finished_item_id,
                qty: $qty,
                date: $date,
                sourceType: SewingPickup::class,
                sourceId: $pickup->id,
                notes: $notes,
                allowNegative: false,
                lotId: null,
                unitCostOverride: $unitCost,
                affectLotCost: false,
                cuttingJobBundleId: $bundle->id,
            );

            // IN ke WIP-CUT
            $this->inventory->stockIn(
                warehouseId: $wipCutWarehouseId,
                itemId: $line->finished_item_id,
                qty: $qty,
                date: $date,
                sourceType: SewingPickup::class,
                sourceId: $pickup->id,
                notes: $notes,
                lotId: null,
                unitCost: $materialUnitCost,
                affectLotCost: false,
                cuttingJobBundleId: $bundle->id,
            );

            // mark line void
            $line->status = 'void';
            $line->void_reason = $validated['reason'];
            $line->voided_at = now();
            $line->voided_by = auth()->id();
            $line->save();

            $rmWarehouseId = (int) Warehouse::where('code', 'RM')->value('id');
            $lineSupplies = SewingPickupLineSupplyLine::query()
                ->where('sewing_pickup_line_id', $line->id)
                ->where('issued_qty', '>', 0)
                ->lockForUpdate()
                ->get();

            foreach ($lineSupplies as $supply) {
                $unitCost = DB::table('inventory_mutations')
                    ->where('source_type', JournalService::SRC_SEWING_PICKUP_SUPPLY)
                    ->where('source_id', $pickup->id)
                    ->where('item_id', $supply->material_item_id)
                    ->where('qty_change', '<', 0)
                    ->value('unit_cost');

                $this->inventory->stockIn(
                    warehouseId: $rmWarehouseId,
                    itemId: (int) $supply->material_item_id,
                    qty: (float) $supply->issued_qty,
                    date: now(),
                    sourceType: JournalService::SRC_SEWING_PICKUP_SUPPLY_VOID_LINE,
                    sourceId: (int) $line->id,
                    notes: "VOID kelengkapan pickup {$pickup->code} line {$line->id}",
                    lotId: null,
                    unitCost: $unitCost !== null ? (float) $unitCost : null,
                    affectLotCost: false,
                );

                $aggregate = SewingPickupSupplyLine::query()
                    ->where('sewing_pickup_id', $pickup->id)
                    ->where('material_item_id', $supply->material_item_id)
                    ->lockForUpdate()
                    ->first();
                if ($aggregate) {
                    $aggregate->issued_qty = max((float) $aggregate->issued_qty - (float) $supply->issued_qty, 0);
                    $aggregate->issued_pcs = max((float) $aggregate->issued_pcs - (float) $line->qty_bundle, 0);
                    $aggregate->save();
                }

                $supply->issued_qty = 0;
                $supply->save();
            }

            // ✅ FIX: recalculate sewing_picked_qty dari SUM(non-void pickup lines).
            // Ini membenahi data korup sekaligus menjamin invariant ke depan.
            $newPicked = (float) SewingPickupLine::query()
                ->where('cutting_job_bundle_id', $bundle->id)
                ->where('status', '!=', 'void')
                ->sum('qty_bundle');
            $bundle->sewing_picked_qty = $newPicked;
            $bundle->save();

            // OPTIONAL (rapi): kalau semua line sudah void, otomatis void header juga
            $hasNonVoid = SewingPickupLine::where('sewing_pickup_id', $pickup->id)
                ->where('status', '!=', 'void')
                ->exists();

            if (!$hasNonVoid) {
                $pickup->status = 'void';
                // kalau kamu mau simpan void header juga, pastikan kolomnya ada
                if (Schema::hasColumn('sewing_pickups', 'void_reason')) {
                    $pickup->void_reason = 'ALL LINES VOID';
                    $pickup->voided_at = now();
                    $pickup->voided_by = auth()->id();
                }
                $pickup->save();
            }
        });

        // Reversal jurnal WIP material (unit_cost material only) untuk line yang di-void
        try {
            $this->journal->postSewingPickupLineVoid($line->fresh());
        } catch (\Throwable $e) {
            Log::warning('Gagal membuat jurnal void pickup line (WIP)', [
                'sewing_pickup_line_id' => $lineId,
                'message' => $e->getMessage(),
            ]);
        }

        try {
            $this->journal->voidBySource(JournalService::SRC_SEWING_PICKUP_WAGE, (int) $lineId, 'VOID Upah Sewing Pickup Line');
        } catch (\Throwable $e) {
            Log::warning('Gagal void jurnal upah sewing pickup line', [
                'sewing_pickup_line_id' => $lineId,
                'message' => $e->getMessage(),
            ]);
        }

        // Reversal jurnal kelengkapan (supply) untuk line yang di-void
        try {
            $this->journal->postSewingPickupSupplyVoidLine($line->fresh());
        } catch (\Throwable $e) {
            Log::warning('Gagal membuat jurnal void kelengkapan pickup line', [
                'sewing_pickup_line_id' => $lineId,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('production.sewing.pickups.show', $pickup)
            ->with('success', 'Line berhasil di-VOID. Stok line sudah dibalik ke WIP-CUT.');
    }

}
