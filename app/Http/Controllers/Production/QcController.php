<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentLine;
use App\Models\QcResult;
use App\Models\SewingReturn;
use App\Services\Accounting\JournalService;
use App\Services\Production\CuttingService;
use App\Services\Production\QcService;
use App\Services\Purchasing\MaterialShortageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QcController extends Controller
{
    public function __construct(
        protected QcService $qc,
        protected CuttingService $cutting,
        protected JournalService $journal,
        protected MaterialShortageService $materialShortages,
    ) {}

    /**
     * List QC per stage.
     */
    public function index(Request $request)
    {
        $stage = $request->get('stage', QcResult::STAGE_CUTTING);

        if (!in_array($stage, [QcResult::STAGE_CUTTING, QcResult::STAGE_SEWING, 'packing'], true)) {
            $stage = QcResult::STAGE_CUTTING;
        }

        $user = Auth::user();
        $userRole = $user->role ?? null;

        // Admin: langsung ke QC Sewing
        if ($userRole === 'admin') {
            if ($request->query('stage') !== QcResult::STAGE_SEWING) {
                return redirect()->route('production.qc.index', ['stage' => QcResult::STAGE_SEWING]);
            }

            $stage = QcResult::STAGE_SEWING;

            $records = SewingReturn::query()
                ->with([
                    'operator',
                    'lines.pickupLine.pickup',
                    'lines.pickupLine.bundle.finishedItem',
                    'lines.pickupLine.bundle.cuttingJob.lot.item',
                    'qcResults.qcUser',
                ])
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();

            return view('production.qc.index', compact('stage', 'records', 'userRole'));
        }

        $records = collect();

        switch ($stage) {
            case QcResult::STAGE_CUTTING:
                $query = CuttingJob::query()
                    ->with([
                        'warehouse',
                        'lot.item',
                        'bundles.finishedItem',
                        'bundles.qcResults' => function ($q) {
                            $q->where('stage', QcResult::STAGE_CUTTING);
                        },
                    ])
                    ->orderByDesc('date')
                    ->orderByDesc('id');

                // Non-owner: hanya tampilkan yang BELUM QC
                if ($userRole !== 'owner') {
                    $query->where('status', 'sent_to_qc');
                }

                $records = $query
                    ->paginate(20)
                    ->withQueryString();
                break;

            case QcResult::STAGE_SEWING:
                $records = SewingReturn::query()
                    ->with([
                        'operator',
                        'lines.pickupLine.pickup',
                        'lines.pickupLine.bundle.finishedItem',
                        'lines.pickupLine.bundle.cuttingJob.lot.item',
                        'qcResults.qcUser',
                    ])
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->paginate(20)
                    ->withQueryString();
                break;

            case 'packing':
                // nanti diisi kalau sudah ada model QC Packing
                $records = collect();
                break;
        }

        return view('production.qc.index', compact('stage', 'records', 'userRole'));
    }

    /**
     * Form QC Cutting.
     */
    public function editCutting(CuttingJob $cuttingJob)
    {
        $cuttingJob->load([
            'warehouse',
            'lot.item',
            'lots.lot.item', // multi-LOT: pivot + lot + item
            'bundles.finishedItem',
            'bundles.lot', // LOT per bundle — ditampilkan kecil di bawah nama bahan
            'bundles.qcResults' => function ($q) {
                $q->where('stage', QcResult::STAGE_CUTTING);
            },
        ]);

        // ambil QC existing per bundle
        $existingQc = QcResult::query()
            ->where('stage', QcResult::STAGE_CUTTING)
            ->where('cutting_job_id', $cuttingJob->id)
            ->get()
            ->keyBy('cutting_job_bundle_id');

        $rows = [];
        foreach ($cuttingJob->bundles as $bundle) {
            $qc = $existingQc->get($bundle->id); // index berdasarkan bundle->id

            $rows[] = [
                'cutting_job_bundle_id' => $bundle->id,
                'bundle_no' => $bundle->bundle_no,
                'bundle_code' => $bundle->bundle_code,
                'item_code' => $bundle->finishedItem?->code,
                'item_name' => $bundle->finishedItem?->name, // ⬅️ dipakai di view (desktop)
                'lot_code' => $bundle->lot?->code,
                'qty_pcs' => $bundle->qty_pcs,
                'status' => $bundle->status,
                'qty_ok' => $qc?->qty_ok ?? $bundle->qty_pcs,
                'qty_reject' => $qc?->qty_reject ?? 0,
                'reject_reason' => $qc?->reject_reason ?? null,
                'notes' => $qc?->notes ?? null,
            ];
        }

        $loginOperator = Auth::user()->employee ?? null;
        $hasQcCutting = $existingQc->isNotEmpty();

        return view('production.qc.cutting_edit', compact(
            'cuttingJob',
            'rows',
            'loginOperator',
            'hasQcCutting'
        ));
    }

    /**
     * Simpan QC Cutting.
     */
    public function updateCutting(Request $request, CuttingJob $cuttingJob)
    {
        $validated = $request->validate([
            'qc_date' => ['required', 'date'],
            'operator_id' => ['nullable', 'exists:employees,id'],

            'results' => ['required', 'array', 'min:1'],
            'results.*.cutting_job_bundle_id' => ['required', 'exists:cutting_job_bundles,id'],
            'results.*.qty_ok' => ['nullable', 'numeric', 'min:0'],
            'results.*.qty_reject' => ['nullable', 'numeric', 'min:0'],
            'results.*.reject_reason' => ['nullable', 'string', 'max:100'],
            'results.*.notes' => ['nullable', 'string'],
        ]);

        // Operator QC selalu mengikuti akun yang sedang login.
        $validated['operator_id'] = Auth::user()->employee?->id;
        $validated['qc_by_user_id'] = Auth::id();

        try {
            // 1️⃣ SIMPAN QC (tanpa mutasi stok)
            $this->qc->saveCuttingQc($cuttingJob, $validated);

            // 2️⃣ BUAT WIP-CUT dari hasil QC
            //    (method ini ada di CuttingService versi yang tadi kita bikin)
            $this->cutting->createWipFromCuttingQc(
                job: $cuttingJob->fresh('bundles'),
                qcDate: $validated['qc_date'],
            );

            try {
                $this->journal->postCuttingWip($cuttingJob->fresh(), $validated['qc_date']);
            } catch (\Throwable $journalError) {
                Log::error('Gagal membuat jurnal cutting_wip', [
                    'cutting_job_id' => $cuttingJob->id,
                    'message' => $journalError->getMessage(),
                ]);
            }

        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'QC gagal: ' . $e->getMessage());
        }

        // 3️⃣ Update status job → sudah QC
        $cuttingJob->update([
            'status' => 'qc_done',
            'updated_by' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        $shortageCount = $this->materialShortages->rows()->where('has_shortage', true)->count();

        return redirect()
            ->route('production.cutting_jobs.show', $cuttingJob)
            ->with('success', 'QC Cutting berhasil disimpan & WIP-CUT sudah dibuat.')
            ->with('material_shortage_count', $shortageCount);
    }

    public function quickOkCutting(CuttingJob $cuttingJob): RedirectResponse
    {
        $cuttingJob->loadMissing([
            'bundles.qcResults' => function ($q) {
                $q->where('stage', QcResult::STAGE_CUTTING);
            },
        ]);

        $hasQcCutting = $cuttingJob->bundles->contains(
            fn($bundle) => $bundle->qcResults->isNotEmpty()
        );

        if ($hasQcCutting) {
            return redirect()
                ->route('production.cutting_jobs.show', $cuttingJob)
                ->with('error', 'QC Cutting sudah pernah diinput. Gunakan Lihat / Edit QC atau Batalkan QC dulu.');
        }

        if ($cuttingJob->bundles->isEmpty()) {
            return back()->with('error', 'Belum ada bundle cutting untuk diproses.');
        }

        $operatorId = Auth::user()->employee?->id;
        $qcDate = now()->toDateString();

        $payload = [
            'qc_date' => $qcDate,
            'operator_id' => $operatorId,
            'qc_by_user_id' => Auth::id(),
            'results' => $cuttingJob->bundles->map(fn(CuttingJobBundle $bundle) => [
                'cutting_job_bundle_id' => $bundle->id,
                'qty_ok' => (float) $bundle->qty_pcs,
                'qty_reject' => 0,
                'reject_reason' => null,
                'notes' => 'Auto OK dari tombol Selesai Cutting & Siap Jahit',
            ])->values()->all(),
        ];

        try {
            $this->qc->saveCuttingQc($cuttingJob, $payload);

            $this->cutting->createWipFromCuttingQc(
                job: $cuttingJob->fresh('bundles'),
                qcDate: $qcDate,
            );

            try {
                $this->journal->postCuttingWip($cuttingJob->fresh(), $qcDate);
            } catch (\Throwable $journalError) {
                Log::error('Gagal membuat jurnal cutting_wip', [
                    'cutting_job_id' => $cuttingJob->id,
                    'message' => $journalError->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            return back()
                ->with('error', 'Selesai Cutting gagal: ' . $e->getMessage());
        }

        $cuttingJob->update([
            'status' => 'qc_done',
            'updated_by' => Auth::id(),
        ]);

        $shortageCount = $this->materialShortages->rows()->where('has_shortage', true)->count();

        return redirect()
            ->route('production.cutting_jobs.show', $cuttingJob)
            ->with('success', 'Cutting selesai. Semua bundle otomatis OK dan sudah masuk WIP-CUT / siap jahit.')
            ->with('material_shortage_count', $shortageCount);
    }

    /**
     * Quick OK per bundle — tandai satu bundle sebagai QC OK dan buat WIP-CUT-nya.
     * Tidak perlu semua bundle selesai dulu.
     */
    public function quickOkBundle(Request $request, CuttingJob $cuttingJob, CuttingJobBundle $bundle): RedirectResponse
    {
        // Pastikan bundle milik cutting job ini
        if ((int) $bundle->cutting_job_id !== $cuttingJob->id) {
            return back()->with('error', 'Bundle tidak ditemukan di Cutting Job ini.');
        }

        // Jika bundle sudah punya QC, skip (sudah diproses)
        $existingQc = QcResult::where('stage', QcResult::STAGE_CUTTING)
            ->where('cutting_job_bundle_id', $bundle->id)
            ->first();

        if ($existingQc) {
            return back()->with('info', 'Bundle ' . $bundle->bundle_code . ' sudah pernah di-QC.');
        }

        $operatorId = Auth::user()->employee?->id;
        $qcDate     = now()->toDateString();

        // Ambil qty dari request jika ada, fallback semua OK
        $qtyPcs    = (float) $bundle->qty_pcs;
        $qtyOk     = $request->has('qty_ok')
            ? max(0, min($qtyPcs, (float) $request->input('qty_ok', $qtyPcs)))
            : $qtyPcs;
        $qtyReject = $request->has('qty_reject')
            ? max(0, min($qtyPcs - $qtyOk, (float) $request->input('qty_reject', 0)))
            : 0.0;
        $rejectReason = $request->input('reject_reason');
        $autoNote     = $request->has('qty_ok') ? null : 'Auto OK dari halaman detail cutting';

        $payload = [
            'qc_date'     => $qcDate,
            'operator_id' => $operatorId,
            'qc_by_user_id' => Auth::id(),
            'results'     => [[
                'cutting_job_bundle_id' => $bundle->id,
                'qty_ok'        => $qtyOk,
                'qty_reject'    => $qtyReject,
                'reject_reason' => $rejectReason,
                'notes'         => $autoNote,
            ]],
        ];

        try {
            $this->qc->saveCuttingQc($cuttingJob, $payload);

            $this->cutting->createWipFromCuttingQc(
                job: $cuttingJob->fresh('bundles'),
                qcDate: $qcDate,
            );

            try {
                $this->journal->postCuttingWip($cuttingJob->fresh(), $qcDate);
            } catch (\Throwable $journalError) {
                Log::error('Gagal membuat jurnal cutting_wip (quickOkBundle)', [
                    'cutting_job_id' => $cuttingJob->id,
                    'bundle_id'      => $bundle->id,
                    'message'        => $journalError->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Quick OK bundle gagal: ' . $e->getMessage());
        }

        // Cek apakah SEMUA bundle sudah QC sekarang
        $totalBundles = $cuttingJob->bundles()->count();
        $doneCount    = QcResult::where('stage', QcResult::STAGE_CUTTING)
            ->where('cutting_job_id', $cuttingJob->id)
            ->distinct('cutting_job_bundle_id')
            ->count('cutting_job_bundle_id');

        $newStatus = $doneCount >= $totalBundles ? 'qc_done' : $cuttingJob->status;

        $cuttingJob->update([
            'status'     => $newStatus,
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('production.cutting_jobs.show', $cuttingJob)
            ->with('success', 'Bundle ' . $bundle->bundle_code . ' berhasil di-QC OK dan masuk WIP-CUT.');
    }

    /**
     * Simpan QC per bundle dari halaman edit (updateOrCreate — bisa create & update).
     * Redirect kembali ke halaman edit QC.
     */
    public function saveBundleEdit(Request $request, CuttingJob $cuttingJob, CuttingJobBundle $bundle): RedirectResponse
    {
        if ((int) $bundle->cutting_job_id !== $cuttingJob->id) {
            return back()->with('error', 'Bundle tidak ditemukan di Cutting Job ini.');
        }

        $qtyPcs    = (float) $bundle->qty_pcs;
        $qtyReject = max(0, min($qtyPcs, (float) $request->input('qty_reject', 0)));
        $qtyOk     = max(0, $qtyPcs - $qtyReject);
        $qcDate    = $request->input('qc_date') ?: now()->toDateString();
        $operatorId = Auth::user()->employee?->id;

        $payload = [
            'qc_date'     => $qcDate,
            'operator_id' => $operatorId,
            'qc_by_user_id' => Auth::id(),
            'results'     => [[
                'cutting_job_bundle_id' => $bundle->id,
                'qty_ok'        => $qtyOk,
                'qty_reject'    => $qtyReject,
                'reject_reason' => $request->input('reject_reason'),
                'notes'         => $request->input('notes'),
            ]],
        ];

        try {
            $this->qc->saveCuttingQc($cuttingJob, $payload);

            $this->cutting->createWipFromCuttingQc(
                job: $cuttingJob->fresh('bundles'),
                qcDate: $qcDate,
            );

            try {
                $this->journal->postCuttingWip($cuttingJob->fresh(), $qcDate);
            } catch (\Throwable $journalError) {
                Log::error('Gagal membuat jurnal cutting_wip (saveBundleEdit)', [
                    'cutting_job_id' => $cuttingJob->id,
                    'bundle_id'      => $bundle->id,
                    'message'        => $journalError->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Simpan bundle gagal: ' . $e->getMessage());
        }

        // Update status job — qc_done hanya kalau semua bundle sudah QC
        $totalBundles = $cuttingJob->bundles()->count();
        $doneCount    = QcResult::where('stage', QcResult::STAGE_CUTTING)
            ->where('cutting_job_id', $cuttingJob->id)
            ->distinct('cutting_job_bundle_id')
            ->count('cutting_job_bundle_id');
        $newStatus = $doneCount >= $totalBundles ? 'qc_done' : $cuttingJob->status;
        $cuttingJob->update(['status' => $newStatus, 'updated_by' => Auth::id()]);

        return redirect()
            ->route('production.qc.cutting.edit', $cuttingJob)
            ->with('success', 'Bundle ' . $bundle->bundle_code . ' berhasil disimpan.');
    }

    public function cancelCutting(CuttingJob $cuttingJob): RedirectResponse
    {
        $role = Auth::user()->role ?? null;
        if ($role !== 'owner') {
            return back()->with('error', 'Hanya OWNER yang boleh membatalkan QC.');
        }

        try {
            $this->qc->cancelCuttingQc($cuttingJob);

            try {
                $this->journal->voidBySource(JournalService::SRC_CUTTING_WIP, (int) $cuttingJob->id, "VOID QC Cutting {$cuttingJob->code}");
            } catch (\Throwable $journalError) {
                Log::warning('Gagal void jurnal cutting_wip', [
                    'cutting_job_id' => $cuttingJob->id,
                    'message' => $journalError->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            $payload = $this->buildCancelQcUiPayload($e, $cuttingJob);

            // toast message singkat + data detail untuk modal
            return back()
                ->with('qc_cancel_ui', $payload)
                ->with('error', $payload['toast'] ?? 'Cancel QC gagal.');
        }

        return redirect()
            ->route('production.cutting_jobs.show', $cuttingJob)
            ->with('success', 'QC Cutting dibatalkan. Silakan QC ulang.');
    }

    private function humanizeCancelCuttingQcError(\Throwable $e, CuttingJob $job): string
    {
        $raw = trim((string) $e->getMessage());

        // Pola error inventory service kamu
        $ok = preg_match(
            '/Stok tidak mencukupi untuk item\s+(\d+)\s+di gudang\s+(\d+)\.\s*Stok:\s*([0-9\.,]+),\s*mau keluar:\s*([0-9\.,]+)/i',
            $raw,
            $m
        );

        // Kalau bukan kasus stok, fallback normal
        if (!$ok) {
            return "Cancel QC gagal: " . $raw;
        }

        [$all, $itemId, $warehouseId, $stok, $need] = $m;

        // Cari "penyebab utama" yang paling membantu user:
        // ambil transaksi Sewing Pickup yang BELUM VOID yang paling baru untuk warehouse+item ini
        $pickup = DB::table('inventory_mutations as m')
            ->join('sewing_pickups as sp', function ($j) {
                $j->on('sp.id', '=', 'm.source_id')
                    ->where('m.source_type', '=', 'App\Models\SewingPickup');
            })
            ->select([
                'm.id',
                'm.qty_change',
                'm.notes',
                'sp.code as pickup_code',
                'sp.voided_at',
            ])
            ->where('m.warehouse_id', (int) $warehouseId)
            ->where('m.item_id', (int) $itemId)
            ->where('m.qty_change', '<', 0)
            ->whereNull('sp.voided_at') // ✅ hanya yang BELUM VOID
            ->orderByDesc('m.date')
            ->orderByDesc('m.id')
            ->first();

        // Kalau tidak ada Sewing Pickup aktif, fallback cari transaksi aktif apa pun yang paling baru
        $other = null;
        if (!$pickup) {
            $other = DB::table('inventory_mutations as m')
                ->select(['m.source_type', 'm.source_id', 'm.qty_change', 'm.notes', 'm.id'])
                ->where('m.warehouse_id', (int) $warehouseId)
                ->where('m.item_id', (int) $itemId)
                ->where('m.qty_change', '<', 0)
                ->whereNotIn('m.source_type', ['cutting_wip', 'cutting_reject', 'cutting_qc_void'])
                ->orderByDesc('m.date')
                ->orderByDesc('m.id')
                ->first();
        }

        // ✅ Pesan SIMPLE (satu layar)
        $msg =
            "Cancel QC gagal — hasil QC sudah dipakai\n\n"
            . "Cutting Job: {$job->code}\n"
            . "Gudang: {$warehouseId}\n"
            . "Item: {$itemId}\n"
            . "Stok tersedia: {$stok}\n"
            . "Dibutuhkan: {$need}\n\n";

        if ($pickup) {
            $code = $pickup->pickup_code ?: '(kode tidak ditemukan)';
            $msg .=
                "Penyebab:\n"
                . "Sewing Pickup {$code} (belum void)\n\n"
                . "Solusi:\n"
                . "1) Void Sewing Pickup {$code}\n"
                . "2) Setelah stok kembali, ulangi Cancel QC";
            return $msg;
        }

        if ($other) {
            $label = "{$other->source_type} #{$other->source_id}";
            $msg .=
                "Penyebab:\n"
                . "{$label}\n\n"
                . "Solusi:\n"
                . "1) Void / batalkan transaksi di atas\n"
                . "2) Setelah stok kembali, ulangi Cancel QC";
            return $msg;
        }

        // fallback paling aman
        return "Cancel QC gagal: " . $raw;
    }

    private function buildCancelQcUiPayload(\Throwable $e, CuttingJob $job): array
    {
        $raw = trim((string) $e->getMessage());

        $ok = preg_match(
            '/Stok tidak mencukupi untuk item\s+(\d+)\s+di gudang\s+(\d+)\.\s*Stok:\s*([0-9\.,]+),\s*mau keluar:\s*([0-9\.,]+)/i',
            $raw,
            $m
        );

        // fallback kalau bukan kasus stok
        if (!$ok) {
            return [
                'type' => 'error',
                'toast' => 'Cancel QC gagal.',
                'title' => 'Cancel QC gagal',
                'lines' => [$raw],
                'action' => null,
            ];
        }

        [$all, $itemId, $warehouseId, $stok, $need] = $m;

        // cari Sewing Pickup BELUM VOID paling baru yang menghabiskan stok item+warehouse ini
        $pickup = DB::table('inventory_mutations as m')
            ->join('sewing_pickups as sp', function ($j) {
                $j->on('sp.id', '=', 'm.source_id')
                    ->where('m.source_type', '=', 'App\Models\SewingPickup');
            })
            ->select([
                'sp.id as pickup_id',
                'sp.code as pickup_code',
                'm.qty_change',
                'm.notes',
            ])
            ->where('m.warehouse_id', (int) $warehouseId)
            ->where('m.item_id', (int) $itemId)
            ->where('m.qty_change', '<', 0)
            ->whereNull('sp.voided_at')
            ->orderByDesc('m.date')
            ->orderByDesc('m.id')
            ->first();

        $toast = 'Cancel QC gagal — hasil QC sudah dipakai.';
        $title = 'Cancel QC gagal';

        $lines = [
            "Cutting Job: {$job->code}",
            "Gudang: {$warehouseId}",
            "Item: {$itemId}",
            "Stok tersedia: {$stok}",
            "Dibutuhkan: {$need}",
        ];

        $action = null;

        if ($pickup) {
            $code = $pickup->pickup_code ?: "ID {$pickup->pickup_id}";
            $lines[] = "Penyebab: Sewing Pickup {$code} (belum void)";
            $lines[] = "Solusi: Void Sewing Pickup → lalu ulangi Cancel QC";

            $action = [
                'label' => "Buka {$code}",
                'route' => 'production.sewing.pickups.show',
                'params' => [$pickup->pickup_id],
            ];
        } else {
            $lines[] = "Solusi: Void transaksi yang memakai stok item ini → lalu ulangi Cancel QC";
        }

        return [
            'type' => 'error',
            'toast' => $toast,
            'title' => $title,
            'lines' => $lines,
            'action' => $action,
        ];
    }

    public function adjustCuttingBundle(Request $request, CuttingJob $cuttingJob, CuttingJobBundle $bundle): RedirectResponse
    {
        // ✅ owner-only
        if ((Auth::user()->role ?? null) !== 'owner') {
            return back()->with('error', 'Hanya OWNER yang boleh melakukan adjustment QC.');
        }

        $validated = $request->validate([
            'qc_date' => ['required', 'date'],
            'qty_ok' => ['required', 'numeric', 'min:0'],
            'qty_reject' => ['nullable', 'numeric', 'min:0'],
            'reject_reason' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $operatorId = Auth::user()->employee?->id;

        try {
            // ✅ ini method baru yang kamu tambahkan di QcService
            $this->qc->adjustCuttingBundleQc(
                bundle: $bundle,
                qcDate: $validated['qc_date'],
                newOk: (float) $validated['qty_ok'],
                newReject: (float) ($validated['qty_reject'] ?? 0),
                operatorId: $operatorId,
                qcByUserId: Auth::id(),
                rejectReason: $validated['reject_reason'] ?? null,
                notes: $validated['notes'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Adjust QC gagal: ' . $e->getMessage());
        }

        return redirect()
            ->route('production.cutting_jobs.show', $bundle->cutting_job_id)
            ->with('success', 'QC bundle berhasil di-adjust.');
    }

    /* ============================================================
     * QC JAHIT (SEWING)
     * ============================================================
     */

    /**
     * Form QC Jahit — satu SewingReturn per form.
     */
    public function editSewing(SewingReturn $sewingReturn)
    {
        $sewingReturn->load([
            'operator',
            'lines.pickupLine.bundle.finishedItem',
            'lines.pickupLine.bundle.cuttingJob',
        ]);

        // QC yang sudah tersimpan untuk return ini
        $existingQc = QcResult::query()
            ->where('stage', QcResult::STAGE_SEWING)
            ->where('sewing_job_id', $sewingReturn->id)
            ->get()
            ->keyBy('cutting_job_bundle_id');

        $rows = [];
        $filterItems = [];
        foreach ($sewingReturn->lines as $line) {
            $bundle = $line->pickupLine?->bundle;
            if (! $bundle) {
                continue;
            }

            $qc = $existingQc->get($bundle->id);

            // Qty maksimal = total yang disetor penjahit. Setelah QC, line dipecah OK + reject.
            $maxQty = (float) ($line->qty_ok ?? 0) + (float) ($line->qty_reject ?? 0);

            $qtyRejectJahit = 0;
            $qtyRejectBahan = 0;
            if ($qc) {
                if ($qc->reject_reason === 'Reject Bahan') {
                    $qtyRejectBahan = $qc->qty_reject;
                } elseif ($qc->reject_reason === 'Reject Jahit & Bahan') {
                    // fallback jika ada mixed reason
                    $qtyRejectJahit = $qc->qty_reject; 
                } else {
                    $qtyRejectJahit = $qc->qty_reject;
                }
            }

            $rows[] = [
                'sewing_return_line_id' => $line->id,
                'bundle_id'        => $bundle->id,
                'bundle_code'      => $bundle->bundle_code,
                'item_id'          => $bundle->finished_item_id,
                'item_code'        => $bundle->finishedItem?->code,
                'item_name'        => $bundle->finishedItem?->name,
                'cutting_job_code' => $bundle->cuttingJob?->code,
                'qty_max'          => $maxQty,
                'qty_ok'           => $qc?->qty_ok ?? $maxQty,
                'qty_reject_jahit' => $qtyRejectJahit,
                'qty_reject_bahan' => $qtyRejectBahan,
                'notes'            => $qc?->notes,
            ];

            if ($bundle->finishedItem) {
                $filterItems[$bundle->finished_item_id] = [
                    'id' => $bundle->finished_item_id,
                    'code' => $bundle->finishedItem->code,
                    'name' => $bundle->finishedItem->name,
                ];
            }
        }

        $loginOperator = Auth::user()->employee ?? null;
        $hasQcSewing   = $existingQc->isNotEmpty();
        $filterItems   = collect($filterItems)->values();

        return view('production.qc.sewing_edit', compact(
            'sewingReturn',
            'rows',
            'loginOperator',
            'hasQcSewing',
            'filterItems'
        ));
    }

    /**
     * Simpan QC Jahit — gerakkan stok WIP-SEW → WIP-FIN (OK) / REJ-SEW (Reject).
     */
    public function updateSewing(Request $request, SewingReturn $sewingReturn)
    {
        $validated = $request->validate([
            'qc_date'                 => ['required', 'date'],
            'operator_id'             => ['nullable', 'exists:employees,id'],
            'results'                 => ['required', 'array', 'min:1'],
            'results.*.sewing_return_line_id' => ['nullable', 'exists:sewing_return_lines,id'],
            'results.*.bundle_id'     => ['required', 'exists:cutting_job_bundles,id'],
            'results.*.qty_ok'           => ['nullable', 'numeric', 'min:0'],
            'results.*.qty_reject_jahit' => ['nullable', 'numeric', 'min:0'],
            'results.*.qty_reject_bahan' => ['nullable', 'numeric', 'min:0'],
            'results.*.notes'            => ['nullable', 'string'],
        ]);

        // Operator QC selalu mengikuti akun yang sedang login.
        $validated['operator_id'] = Auth::user()->employee?->id;
        $validated['qc_by_user_id'] = Auth::id();

        $alreadyMoved = DB::table('inventory_mutations')
            ->where('source_type', 'sewing_qc_out')
            ->where('source_id', (int) $sewingReturn->id)
            ->exists();

        if ($alreadyMoved) {
            return back()
                ->with('error', 'QC Jahit sudah pernah diposting dan stok sudah bergerak. Void / koreksi dulu sebelum QC ulang.');
        }

        try {
            $this->qc->saveSewingQc($sewingReturn, $validated);

            if ($sewingReturn->isFillable('status')) {
                $sewingReturn->forceFill(['status' => 'posted'])->save();
            }

            foreach (['postSewingReturnOk', 'postSewingReturnReject'] as $method) {
                try {
                    $this->journal->{$method}($sewingReturn->fresh(), $validated['qc_date']);
                } catch (\Throwable $journalError) {
                    Log::warning("Gagal membuat jurnal {$method} dari QC Jahit", [
                        'sewing_return_id' => $sewingReturn->id,
                        'message' => $journalError->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'QC Jahit gagal: ' . $e->getMessage());
        }

        return redirect()
            ->route('production.qc.index', ['stage' => 'sewing'])
            ->with('success', "QC Jahit berhasil disimpan. Stok {$sewingReturn->warehouse?->code} → WH-PRD & REJ-SEW sudah diperbarui.");
    }

    public function overproductionCuttingBundle(Request $request, CuttingJob $cuttingJob, CuttingJobBundle $bundle)
    {
        // pastikan bundle memang milik cutting job ini
        abort_unless((int) $bundle->cutting_job_id === (int) $cuttingJob->id, 404);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'qty_add' => ['required', 'numeric', 'gt:0'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // item wajib ada
        abort_unless((int) $bundle->finished_item_id > 0, 422);

        $module = 'cutting_overproduction';

        return DB::transaction(function () use ($validated, $bundle, $module) {

            /** @var InventoryAdjustment $adj */
            $adj = InventoryAdjustment::create([
                'module' => $module,
                'date' => $validated['date'] ?? now()->toDateString(), // kalau kamu punya kolom date
                'warehouse_id' => (int) $validated['warehouse_id'], // asumsi header punya warehouse_id
                'status' => 'draft',
                'notes' => trim(
                    ($validated['notes'] ?? '') .
                    "\n[Auto] Cutting Overproduction | Bundle {$bundle->bundle_code}"
                ),
                'created_by' => auth()->id(),
                'cutting_job_bundle_id' => $bundle->id, // header ref (kalau kamu jadiin)
            ]);

            $qtyAdd = (float) $validated['qty_add'];

            InventoryAdjustmentLine::create([
                'inventory_adjustment_id' => $adj->id,
                'item_id' => $bundle->finished_item_id,
                'lot_id' => null, // WIP/FG non-LOT
                'qty_change' => $qtyAdd,
                'direction' => 'in',
                'notes' => "Overproduction dari Cutting bundle {$bundle->bundle_code}",
                'cutting_job_bundle_id' => $bundle->id,
            ]);

            // opsi: langsung post biar sekali klik (atau biarkan draft dulu)
            // $this->inventoryAdjustmentService->post($adj);

            return redirect()
                ->route('inventory.adjustments.show', ['inventoryAdjustment' => $adj->getKey()]) // sesuaikan route show kamu
                ->with('success', 'Draft Cutting Overproduction dibuat. Silakan POST untuk masuk audit trail stok.');
        });
    }

    public function revertCuttingToCutting(CuttingJob $cuttingJob): RedirectResponse
    {
        // owner-only (samakan pola dengan cancelCutting)
        if ((Auth::user()->role ?? null) !== 'owner') {
            return back()->with('error', 'Hanya OWNER yang boleh mengubah status ini.');
        }

        return DB::transaction(function () use ($cuttingJob) {

            // hanya boleh kalau status-nya memang sent_to_qc
            if ($cuttingJob->status !== 'sent_to_qc') {
                return back()->with('error', 'Job ini bukan status sent_to_qc.');
            }

            // kalau sudah ada QC cutting, jangan boleh revert (suruh cancel QC saja)
            $hasQc = QcResult::query()
                ->where('stage', QcResult::STAGE_CUTTING)
                ->where('cutting_job_id', $cuttingJob->id)
                ->exists();

            if ($hasQc) {
                return back()->with('error', 'Sudah ada QC Cutting. Gunakan menu Cancel QC (VOID) untuk membatalkan QC terlebih dulu.');
            }

            // OPTIONAL: reset field QC di bundle (kalau kamu simpan qty_qc_ok/reject/status di bundle)
            $cuttingJob->loadMissing('bundles');

            foreach ($cuttingJob->bundles as $b) {
                $b->qty_qc_ok = 0;
                $b->qty_qc_reject = 0;
                $b->status = 'cut'; // atau 'cutting' tergantung enum status bundle kamu
                $b->wip_qty = 0;
                $b->wip_warehouse_id = null;
                $b->wip_posted_at = null;
                $b->cut_wip_qty = 0;
                $b->cut_wip_warehouse_id = null;
                $b->save();
            }

            // update header
            $cuttingJob->update([
                'status' => 'cutting',
                'updated_by' => Auth::id(),
            ]);

            return redirect()
                ->route('production.cutting_jobs.show', $cuttingJob)
                ->with('success', 'Status Cutting Job berhasil dikembalikan ke CUTTING.');
        });
    }

}
