<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJob;
use App\Models\QcResult;
use App\Models\SewingReturn;
use App\Services\Production\QcService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QcController extends Controller
{
    public function __construct(
        protected QcService $qc,
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

        $records = collect();

        switch ($stage) {
            case QcResult::STAGE_CUTTING:
                $records = CuttingJob::query()
                    ->with([
                        'warehouse',
                        'lot.item',
                        'bundles.finishedItem',
                        'bundles.qcResults' => function ($q) {
                            $q->where('stage', QcResult::STAGE_CUTTING);
                        },
                    ])
                    ->where('status', 'sent_to_qc')
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->paginate(20)
                    ->withQueryString();
                break;

            case QcResult::STAGE_SEWING:
                $records = SewingReturn::query()
                    ->with([
                        'operator',
                        'lines.pickupLine.pickup.warehouse',
                        'lines.pickupLine.bundle.finishedItem',
                        'lines.pickupLine.bundle.cuttingJob.lot.item',
                    ])
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->paginate(20)
                    ->withQueryString();
                break;
        }

        return view('production.qc.index', compact('stage', 'records'));
    }

    /**
     * Form QC Cutting.
     */
    public function editCutting(CuttingJob $cuttingJob)
    {
        $cuttingJob->load([
            'warehouse',
            'lot.item',
            'bundles.finishedItem',
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

        // fallback operator → kalau hidden kosong, pakai employee dari user login
        if (empty($validated['operator_id'])) {
            $validated['operator_id'] = \Illuminate\Support\Facades\Auth::user()->employee?->id;
        }

        try {

            // 🔥 SIMPAN QC + MUTASI STOK
            $this->qc->saveCuttingQc($cuttingJob, $validated);

        } catch (\Throwable $e) {
            // Kalau ada error apa pun → balik ke form + tampilin pesan
            return back()
                ->withInput()
                ->with('error', 'QC gagal: ' . $e->getMessage());
        }

        // Update status job → sudah QC
        $cuttingJob->update([
            'status' => 'qc_done',
            'updated_by' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        return redirect()
            ->route('production.cutting_jobs.show', $cuttingJob)
            ->with('success', 'QC Cutting berhasil disimpan.');
    }
}
