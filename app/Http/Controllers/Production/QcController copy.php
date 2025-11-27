<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJob;
use App\Models\Employee;
use App\Models\QcResult;
use App\Models\SewingReturn;
use App\Services\Production\QcCuttingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QcController extends Controller
{
    public function __construct(
        protected QcCuttingService $qcCutting,
    ) {}

    public function index(Request $request)
    {
        $stage = $request->get('stage', 'cutting'); // default cutting

        if (!in_array($stage, ['cutting', 'sewing', 'packing'], true)) {
            $stage = 'cutting';
        }

        $records = collect();

        switch ($stage) {
            case 'cutting':
                $records = CuttingJob::query()
                    ->with(['warehouse', 'lot.item', 'bundles.finishedItem', 'bundles.qcResults'])
                    ->where('status', 'sent_to_qc') // << utama
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->paginate(20)
                    ->withQueryString();
                break;

            case 'sewing':
                // List semua Sewing Return (hasil QC Sewing)
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

            case 'packing':
                // Nanti diisi dengan model PackingJob / PackingReturn
                // $records = ...
                break;
        }

        return view('production.qc.index', [
            'stage' => $stage,
            'records' => $records,
        ]);
    }

    /**
     * Form QC untuk satu Cutting Job (stage = cutting)
     */
    public function editCutting(CuttingJob $cuttingJob)
    {
        $cuttingJob->load([
            'warehouse',
            'lot.item',
            'bundles.finishedItem',
            'bundles.qcResults',
        ]);

        // QC existing per bundle (1 row per bundle, stage=cutting, karena kita pakai updateOrCreate)
        $existingQc = QcResult::query()
            ->where('stage', 'cutting')
            ->where('cutting_job_id', $cuttingJob->id)
            ->get()
            ->keyBy('cutting_job_bundle_id');

        $rows = [];
        foreach ($cuttingJob->bundles as $bundle) {
            $qc = $existingQc->get($bundle->id);

            $rows[] = [
                'bundle_id' => $bundle->id,
                'bundle_no' => $bundle->bundle_no,
                'bundle_code' => $bundle->bundle_code,
                'item_code' => $bundle->finishedItem?->code,
                'qty_pcs' => $bundle->qty_pcs,
                'status' => $bundle->status, // status bundle (cut / qc_ok / qc_mixed / qc_reject)
                'qty_ok' => $qc?->qty_ok ?? $bundle->qty_pcs, // default semua OK
                'qty_reject' => $qc?->qty_reject ?? 0,
                'notes' => $qc?->notes ?? null,
            ];
        }

        // Operator QC: otomatis dari user login
        // Asumsi: User punya relasi employee -> sesuaikan kalau nama relasinya beda
        $loginOperator = null;
        if (auth()->check() && method_exists(auth()->user(), 'employee')) {
            $loginOperator = auth()->user()->employee;
        }

        $hasQcCutting = $existingQc->isNotEmpty();

        return view('production.qc.cutting_edit', [
            'job' => $cuttingJob,
            'rows' => $rows,
            'loginOperator' => $loginOperator,
            'hasQcCutting' => $hasQcCutting,
        ]);
    }

    /**
     * Simpan hasil QC Cutting
     */

    public function updateCutting(Request $request, CuttingJob $cuttingJob)
    {
        $validated = $request->validate([
            'qc_date' => ['required', 'date'],
            'operator_id' => ['nullable', 'exists:employees,id'],
            'results' => ['required', 'array', 'min:1'],
            'results.*.bundle_id' => ['required', 'exists:cutting_job_bundles,id'],
            'results.*.qty_ok' => ['nullable', 'numeric', 'min:0'],
            'results.*.qty_reject' => ['nullable', 'numeric', 'min:0'],
            'results.*.notes' => ['nullable', 'string'],
        ], [
            'qc_date.required' => 'Tanggal QC wajib diisi.',
            'results.required' => 'Minimal 1 baris QC harus diisi.',
            'results.*.bundle_id.required' => 'Bundle tidak valid.',
        ]);

        // fallback operator_id dari user login (kalau ada relasi employee)
        if (empty($validated['operator_id']) && auth()->check() && method_exists(auth()->user(), 'employee')) {
            $validated['operator_id'] = auth()->user()->employee?->id;
        }

        try {
            $this->qcCutting->saveCuttingQc($cuttingJob, $validated);
        } catch (\RuntimeException $e) {
            return back()
                ->withInput()
                ->with('error', 'QC gagal: ' . $e->getMessage());
        }

        $cuttingJob->update([
            'status' => 'qc_done',
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('production.cutting_jobs.show', $cuttingJob)
            ->with('success', 'QC Cutting berhasil disimpan. Status berubah menjadi QC_DONE.');
    }

}
