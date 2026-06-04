<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductionRejectController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    /**
     * Daftar kejadian reject produksi (gabungan QC cutting + setoran jahit),
     * bergaya seperti halaman Sewing Returns.
     */
    public function index(Request $request): View
    {
        $filters = [
            'from_date' => $request->get('from_date'),
            'to_date' => $request->get('to_date'),
            'operator_id' => $request->get('operator_id'),
            'stage' => $request->get('stage'), // 'Cutting' | 'Jahit'
            'q' => trim((string) $request->get('q')),
        ];

        $lines = $this->collectRejects($filters);

        // ---- Ringkasan ----
        $totalReject = (float) $lines->sum('qty');
        $rejectCutting = (float) $lines->where('stage', 'Cutting')->sum('qty');
        $rejectSewing = (float) $lines->where('stage', 'Jahit')->sum('qty');
        $totalHpp = (float) $lines->sum('hpp_total');

        // ---- Paginate manual (sumber digabung dari 2 tabel) ----
        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $lines->forPage($page, $perPage)->values();
        $rejects = new LengthAwarePaginator(
            $items,
            $lines->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $operators = Employee::orderBy('code')->get(['id', 'code', 'name']);

        return view('production.reject.index', [
            'rejects' => $rejects,
            'operators' => $operators,
            'filters' => $filters,
            'totalReject' => $totalReject,
            'rejectCutting' => $rejectCutting,
            'rejectSewing' => $rejectSewing,
            'totalHpp' => $totalHpp,
        ]);
    }

    /**
     * Gabungkan reject dari dua tahap QC sesuai filter, lalu hitung HPP per baris.
     *
     * @return \Illuminate\Support\Collection<int,object>
     */
    private function collectRejects(array $f): \Illuminate\Support\Collection
    {
        $from = $f['from_date'] ?: null;
        $to = $f['to_date'] ?: null;
        $stage = $f['stage'] ?: null;
        $q = $f['q'] ?: null;

        $cut = collect();
        if ($stage === null || $stage === 'Cutting') {
            $cq = DB::table('qc_results as qc')
                ->join('cutting_job_bundles as cb', 'cb.id', '=', 'qc.cutting_job_bundle_id')
                ->join('items as it', 'it.id', '=', 'cb.finished_item_id')
                ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
                ->leftJoin('employees as e', 'e.id', '=', 'qc.operator_id')
                ->where('qc.stage', 'cutting')
                ->where('qc.qty_reject', '>', 0);
            if ($from) {
                $cq->whereRaw('DATE(qc.qc_date) >= ?', [$from]);
            }
            if ($to) {
                $cq->whereRaw('DATE(qc.qc_date) <= ?', [$to]);
            }
            if ($f['operator_id']) {
                $cq->where('qc.operator_id', $f['operator_id']);
            }
            $cut = $cq->selectRaw("
                    qc.id as ref_id,
                    NULL as line_id,
                    'Cutting' as stage,
                    DATE(qc.qc_date) as date,
                    it.id as item_id, it.code as sku, it.name as product_name,
                    COALESCE(cat.name,'-') as category,
                    COALESCE(e.code,'-') as operator_code, COALESCE(e.name,'-') as operator_name,
                    qc.qty_reject as qty,
                    COALESCE(NULLIF(qc.reject_reason,''),'-') as reason
                ")
                ->get();
        }

        $sew = collect();
        if ($stage === null || $stage === 'Jahit') {
            $sq = DB::table('sewing_return_lines as rl')
                ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
                ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
                ->join('items as it', 'it.id', '=', 'pl.finished_item_id')
                ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
                ->leftJoin('employees as e', 'e.id', '=', 'r.operator_id')
                ->where('rl.qty_reject', '>', 0)
                ->whereNull('r.voided_at');
            if ($from) {
                $sq->whereRaw('DATE(r.date) >= ?', [$from]);
            }
            if ($to) {
                $sq->whereRaw('DATE(r.date) <= ?', [$to]);
            }
            if ($f['operator_id']) {
                $sq->where('r.operator_id', $f['operator_id']);
            }
            $sew = $sq->selectRaw("
                    r.id as ref_id,
                    rl.id as line_id,
                    'Jahit' as stage,
                    DATE(r.date) as date,
                    it.id as item_id, it.code as sku, it.name as product_name,
                    COALESCE(cat.name,'-') as category,
                    COALESCE(e.code,'-') as operator_code, COALESCE(e.name,'-') as operator_name,
                    rl.qty_reject as qty,
                    COALESCE(NULLIF(rl.notes,''),'-') as reason
                ")
                ->get();
        }

        $all = $cut->concat($sew);

        // Filter pencarian (SKU / produk / operator / alasan)
        if ($q) {
            $needle = mb_strtolower($q);
            $all = $all->filter(function ($r) use ($needle) {
                $hay = mb_strtolower(trim(
                    $r->sku . ' ' . $r->product_name . ' ' . $r->category . ' '
                    . $r->operator_code . ' ' . $r->operator_name . ' ' . $r->reason
                ));
                return str_contains($hay, $needle);
            });
        }

        // HPP satuan per item
        $costByItem = Item::whereIn('id', $all->pluck('item_id')->filter()->unique()->values()->all())
            ->get()
            ->mapWithKeys(fn($it) => [$it->id => (float) ($it->effective_unit_cost ?? 0)]);

        // Qty yang sudah diperbaiki (→ WH-PRD) per baris reject jahit
        $repairedByLine = $this->repairedQtyByLine(
            $all->pluck('line_id')->filter()->map(fn($v) => (int) $v)->unique()->values()->all()
        );

        return $all->map(function ($r) use ($costByItem, $repairedByLine) {
            $qty = (float) $r->qty;
            $r->qty = $qty;
            $r->hpp_total = $qty * (float) ($costByItem[$r->item_id] ?? 0);

            $lineId = isset($r->line_id) ? (int) $r->line_id : 0;
            $repaired = $lineId > 0 ? (float) ($repairedByLine[$lineId] ?? 0) : 0.0;
            $r->repaired_qty = max($repaired, 0.0);
            $r->remaining_qty = max($qty - $r->repaired_qty, 0.0);
            return $r;
        })
            ->sortBy([['date', 'desc'], ['qty', 'desc']])
            ->values();
    }

    /**
     * Net qty yang sudah diperbaiki (masuk WH-PRD) per sewing_return_line.
     *
     * Dihitung dari ledger di gudang WH-PRD: perbaikan = IN (+), void = OUT (-).
     * Jadi nilai akhir = qty perbaikan yang masih berlaku per baris.
     *
     * @param  array<int,int>  $lineIds
     * @return array<int,float>  [line_id => net_repaired_qty]
     */
    private function repairedQtyByLine(array $lineIds): array
    {
        if (empty($lineIds)) {
            return [];
        }

        $whPrd = Warehouse::query()->where('code', 'WH-PRD')->first();
        if (!$whPrd) {
            return [];
        }

        return DB::table('inventory_mutations')
            ->where('warehouse_id', $whPrd->id)
            ->whereIn('source_type', ['reject_repair', 'reject_repair_void'])
            ->whereIn('source_id', $lineIds)
            ->groupBy('source_id')
            ->selectRaw('source_id, SUM(qty_change) as net')
            ->pluck('net', 'source_id')
            ->map(fn($v) => (float) $v)
            ->all();
    }

    /**
     * Form "perbaiki reject jahit → masuk WH-PRD".
     *
     * Sumber baris = reject jahit yang sudah tercatat (sewing_return_lines,
     * qty_reject > 0), sama seperti tab Reject di dashboard. Setiap baris
     * bisa diisi "qty diperbaiki" yang nantinya masuk ke gudang WH-PRD.
     *
     * NOTE: tahap UI saja — penyimpanan belum diaktifkan (lihat store()).
     */
    public function create(Request $request): View
    {
        $today = now();

        $dateFrom = $request->input('date_from');
        $dateFrom = is_string($dateFrom) && trim($dateFrom) !== '' ? trim($dateFrom) : $today->copy()->subDays(6)->toDateString();

        $dateTo = $request->input('date_to');
        $dateTo = is_string($dateTo) && trim($dateTo) !== '' ? trim($dateTo) : $today->toDateString();

        $operatorId = $request->integer('operator_id') ?: null;

        $whPrd = Warehouse::query()->where('code', 'WH-PRD')->first();

        // ---- Reject JAHIT tercatat (selaras tab Reject dashboard) ----
        $q = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->join('items as it', 'it.id', '=', 'pl.finished_item_id')
            ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
            ->leftJoin('employees as e', 'e.id', '=', 'r.operator_id')
            ->where('rl.qty_reject', '>', 0)
            ->whereNull('r.voided_at')
            ->whereRaw('DATE(r.date) BETWEEN ? AND ?', [$dateFrom, $dateTo]);

        if ($operatorId) {
            $q->where('r.operator_id', $operatorId);
        }

        $rows = $q->selectRaw("
                rl.id as line_id,
                DATE(r.date) as date,
                r.code as return_code,
                it.id as item_id, it.code as sku, it.name as product_name,
                COALESCE(cat.name,'-') as category,
                r.operator_id as operator_id,
                COALESCE(e.code,'-') as operator_code, COALESCE(e.name,'-') as operator_name,
                rl.qty_reject as qty_reject,
                COALESCE(NULLIF(rl.notes,''),'-') as reason
            ")
            ->orderByDesc('r.date')
            ->orderByDesc('rl.id')
            ->get();

        // HPP satuan per item (sama dgn tab dashboard: effective_unit_cost)
        $costByItem = Item::whereIn('id', $rows->pluck('item_id')->filter()->unique()->values()->all())
            ->get()
            ->mapWithKeys(fn($it) => [$it->id => (float) ($it->effective_unit_cost ?? 0)]);

        // Qty yang sudah diperbaiki (→ WH-PRD) per baris → hanya tampilkan sisa
        $repairedByLine = $this->repairedQtyByLine(
            $rows->pluck('line_id')->filter()->map(fn($v) => (int) $v)->unique()->values()->all()
        );

        // Stok fisik di gudang REJECT per item (pengaman: barang yg sudah ditarik /
        // di-write-off manual lewat stock opname tidak bisa diperbaiki lagi).
        $rejectStockByItem = [];
        $rejectWh = Warehouse::query()->where('code', 'REJECT')->first();
        if ($rejectWh) {
            $rejectStockByItem = DB::table('inventory_stocks')
                ->where('warehouse_id', $rejectWh->id)
                ->whereIn('item_id', $rows->pluck('item_id')->filter()->unique()->values()->all())
                ->pluck('qty', 'item_id')
                ->map(fn($v) => (float) $v)
                ->all();
        }

        $rows = $rows->map(function ($r) use ($costByItem, $repairedByLine, $rejectStockByItem) {
            $qtyReject = (float) $r->qty_reject;
            $repaired = (float) ($repairedByLine[(int) $r->line_id] ?? 0);
            $remaining = max($qtyReject - max($repaired, 0.0), 0.0);

            // Batasi juga oleh stok fisik yg masih ada di gudang REJECT utk item ini
            $stock = (float) ($rejectStockByItem[(int) $r->item_id] ?? 0);
            $remaining = min($remaining, max($stock, 0.0));

            $r->qty_reject_original = $qtyReject;
            $r->qty_repaired = max($repaired, 0.0);
            $r->qty_reject = $remaining; // dipakai sbg cap input + angka REJECT (sisa)
            $r->hpp_total = $remaining * (float) ($costByItem[$r->item_id] ?? 0);
            return $r;
        })
            // Sembunyikan baris yg sudah diperbaiki / tidak ada stok reject fisiknya
            ->filter(fn($r) => $r->qty_reject > 0.000001)
            ->values();

        // Operator untuk dropdown filter: semua yg punya reject jahit di rentang ini
        $operators = Employee::query()
            ->whereIn('id', DB::table('sewing_return_lines as rl')
                ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
                ->where('rl.qty_reject', '>', 0)
                ->whereNull('r.voided_at')
                ->whereRaw('DATE(r.date) BETWEEN ? AND ?', [$dateFrom, $dateTo])
                ->whereNotNull('r.operator_id')
                ->distinct()
                ->pluck('r.operator_id')
                ->all())
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $totalReject = (float) $rows->sum('qty_reject');
        $totalHpp = (float) $rows->sum('hpp_total');

        return view('production.reject.create', compact(
            'rows',
            'operators',
            'operatorId',
            'dateFrom',
            'dateTo',
            'whPrd',
            'totalReject',
            'totalHpp',
        ));
    }

    /**
     * Simpan perbaikan reject jahit → barang masuk WH-PRD.
     *
     * Alur stok = kebalikan dari pencatatan reject jahit:
     * saat reject, barang di-OUT dari WIP-SEW lalu IN ke gudang REJECT.
     * Saat diperbaiki, barang di-OUT dari REJECT lalu IN ke WH-PRD.
     * Stok di gudang REJECT otomatis menjadi pengaman agar tidak
     * memperbaiki melebihi yang benar-benar reject.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'results' => ['required', 'array', 'min:1'],
            'results.*.sewing_return_line_id' => ['required', 'integer'],
            'results.*.qty_fixed' => ['nullable', 'numeric', 'min:0'],
        ]);

        $date = !empty($validated['date'])
            ? Carbon::parse($validated['date'])->toDateString()
            : now()->toDateString();

        // Ambil baris yang benar-benar diisi (qty_fixed > 0)
        $rows = collect($validated['results'])
            ->map(fn($r) => [
                'line_id' => (int) ($r['sewing_return_line_id'] ?? 0),
                'qty_fixed' => (float) ($r['qty_fixed'] ?? 0),
            ])
            ->filter(fn($r) => $r['line_id'] > 0 && $r['qty_fixed'] > 0.000001)
            ->values();

        if ($rows->isEmpty()) {
            return back()->withInput()->withErrors(['results' => 'Isi minimal 1 qty diperbaiki.']);
        }

        return DB::transaction(function () use ($rows, $date): RedirectResponse {
            $whPrd = Warehouse::query()->where('code', 'WH-PRD')->first();
            $rejectWh = Warehouse::query()->where('code', 'REJECT')->first();

            if (!$whPrd) {
                throw ValidationException::withMessages(['results' => 'Gudang WH-PRD belum ada.']);
            }
            if (!$rejectWh) {
                throw ValidationException::withMessages(['results' => 'Gudang REJECT belum ada — tidak ada sumber barang reject untuk diperbaiki.']);
            }

            // Detail baris reject (item, bundle, qty_reject) sebagai validasi cap per baris
            $details = DB::table('sewing_return_lines as rl')
                ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
                ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
                ->whereIn('rl.id', $rows->pluck('line_id')->all())
                ->whereNull('r.voided_at')
                ->selectRaw('rl.id as line_id, rl.qty_reject, pl.finished_item_id as item_id, pl.cutting_job_bundle_id as bundle_id, r.code as return_code')
                ->get()
                ->keyBy('line_id');

            $totalFixed = 0.0;

            foreach ($rows as $row) {
                $d = $details->get($row['line_id']);
                if (!$d) {
                    throw ValidationException::withMessages(['results' => "Baris reject #{$row['line_id']} tidak valid / sudah void."]);
                }

                $itemId = (int) $d->item_id;
                $bundleId = ((int) $d->bundle_id) ?: null;
                $qtyFixed = (float) $row['qty_fixed'];
                $qtyReject = (float) $d->qty_reject;

                if ($itemId <= 0) {
                    throw ValidationException::withMessages(['results' => "Item tidak diketahui pada baris #{$row['line_id']}."]);
                }
                if ($qtyFixed > $qtyReject + 0.000001) {
                    throw ValidationException::withMessages(['results' => "Qty diperbaiki melebihi qty reject pada baris #{$row['line_id']} (maks {$qtyReject})."]);
                }

                // Harga per unit: ikut nilai barang di gudang REJECT, fallback ke
                // HPP efektif item (sama dgn perhitungan HPP di tab dashboard).
                $unitCost = (float) $this->inventory->getItemIncomingUnitCost($rejectWh->id, $itemId);
                if ($unitCost <= 0) {
                    $unitCost = (float) (Item::query()->find($itemId)?->effective_unit_cost ?? 0);
                }

                // OUT dari REJECT (pengaman: allowNegative=false → tidak bisa double-repair)
                try {
                    $this->inventory->stockOut(
                        warehouseId: $rejectWh->id,
                        itemId: $itemId,
                        qty: $qtyFixed,
                        date: $date,
                        sourceType: 'reject_repair',
                        sourceId: $row['line_id'],
                        notes: "Perbaikan reject {$d->return_code} → WH-PRD",
                        allowNegative: false,
                        lotId: null,
                        unitCostOverride: null,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );
                } catch (\RuntimeException $e) {
                    throw ValidationException::withMessages([
                        'results' => "Stok reject untuk baris #{$row['line_id']} ({$d->return_code}) tidak cukup / sudah diperbaiki sebelumnya.",
                    ]);
                }

                // IN ke WH-PRD
                $this->inventory->stockIn(
                    warehouseId: $whPrd->id,
                    itemId: $itemId,
                    qty: $qtyFixed,
                    date: $date,
                    sourceType: 'reject_repair',
                    sourceId: $row['line_id'],
                    notes: "Perbaikan reject {$d->return_code} masuk WH-PRD",
                    lotId: null,
                    unitCost: $unitCost > 0 ? $unitCost : null,
                    affectLotCost: false,
                    cuttingJobBundleId: $bundleId,
                );

                $totalFixed += $qtyFixed;
            }

            return redirect()
                ->route('production.reject.index')
                ->with('status', 'Berhasil: ' . number_format($totalFixed, 0, ',', '.') . ' pcs reject diperbaiki & masuk WH-PRD.');
        });
    }

    /**
     * Batalkan perbaikan reject jahit untuk satu baris.
     *
     * Kebalikan dari store(): barang di-OUT dari WH-PRD lalu IN kembali ke gudang
     * REJECT, sebesar net qty yang masih tercatat sebagai "diperbaiki" untuk baris itu.
     * Stok WH-PRD jadi pengaman — bila barang sudah lanjut ke proses lain
     * (mis. packing), pembatalan ditolak dengan pesan ramah.
     */
    public function voidRepair(Request $request, int $line): RedirectResponse
    {
        return DB::transaction(function () use ($line): RedirectResponse {
            $whPrd = Warehouse::query()->where('code', 'WH-PRD')->first();
            $rejectWh = Warehouse::query()->where('code', 'REJECT')->first();

            if (!$whPrd || !$rejectWh) {
                throw ValidationException::withMessages(['repair' => 'Gudang WH-PRD / REJECT belum ada.']);
            }

            // Detail baris (item + bundle + kode return)
            $d = DB::table('sewing_return_lines as rl')
                ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
                ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
                ->where('rl.id', $line)
                ->selectRaw('rl.id as line_id, pl.finished_item_id as item_id, pl.cutting_job_bundle_id as bundle_id, r.code as return_code')
                ->first();

            if (!$d || (int) $d->item_id <= 0) {
                throw ValidationException::withMessages(['repair' => "Baris reject #{$line} tidak ditemukan."]);
            }

            // Net qty perbaikan yang masih berlaku untuk baris ini
            $net = (float) DB::table('inventory_mutations')
                ->where('warehouse_id', $whPrd->id)
                ->whereIn('source_type', ['reject_repair', 'reject_repair_void'])
                ->where('source_id', $line)
                ->sum('qty_change');

            if ($net <= 0.000001) {
                throw ValidationException::withMessages(['repair' => "Tidak ada perbaikan aktif untuk baris #{$line}."]);
            }

            $itemId = (int) $d->item_id;
            $bundleId = ((int) $d->bundle_id) ?: null;

            // Harga unit ikut nilai barang di WH-PRD, fallback HPP efektif item
            $unitCost = (float) $this->inventory->getItemIncomingUnitCost($whPrd->id, $itemId);
            if ($unitCost <= 0) {
                $unitCost = (float) (Item::query()->find($itemId)?->effective_unit_cost ?? 0);
            }

            // OUT dari WH-PRD (pengaman: barang harus masih ada di WH-PRD)
            try {
                $this->inventory->stockOut(
                    warehouseId: $whPrd->id,
                    itemId: $itemId,
                    qty: $net,
                    date: now()->toDateString(),
                    sourceType: 'reject_repair_void',
                    sourceId: $line,
                    notes: "Batal perbaikan reject {$d->return_code} → kembali ke REJECT",
                    allowNegative: false,
                    lotId: null,
                    unitCostOverride: null,
                    affectLotCost: false,
                    cuttingJobBundleId: $bundleId,
                );
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages([
                    'repair' => "Tidak bisa membatalkan: stok di WH-PRD untuk baris #{$line} ({$d->return_code}) sudah berkurang / lanjut ke proses lain.",
                ]);
            }

            // IN kembali ke REJECT
            $this->inventory->stockIn(
                warehouseId: $rejectWh->id,
                itemId: $itemId,
                qty: $net,
                date: now()->toDateString(),
                sourceType: 'reject_repair_void',
                sourceId: $line,
                notes: "Batal perbaikan reject {$d->return_code} → kembali ke REJECT",
                lotId: null,
                unitCost: $unitCost > 0 ? $unitCost : null,
                affectLotCost: false,
                cuttingJobBundleId: $bundleId,
            );

            return redirect()
                ->back()
                ->with('status', 'Perbaikan dibatalkan: ' . number_format($net, 0, ',', '.') . ' pcs dikembalikan ke gudang REJECT.');
        });
    }
}
