<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJob;
use App\Models\QcResult;
use App\Models\SewingReturn;
use App\Services\Production\CuttingService;
use App\Services\Production\QcService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QcController extends Controller
{
    public function __construct(
        protected QcService $qc,
        protected CuttingService $cutting,
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
            // 1️⃣ SIMPAN QC (tanpa mutasi stok)
            $this->qc->saveCuttingQc($cuttingJob, $validated);

            // 2️⃣ BUAT WIP-CUT dari hasil QC
            //    (method ini ada di CuttingService versi yang tadi kita bikin)
            $this->cutting->createWipFromCuttingQc(
                job: $cuttingJob->fresh('bundles'),
                qcDate: $validated['qc_date'],
            );

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

        return redirect()
            ->route('production.cutting_jobs.show', $cuttingJob)
            ->with('success', 'QC Cutting berhasil disimpan & WIP-CUT sudah dibuat.');
    }

    public function cancelCutting(CuttingJob $cuttingJob): RedirectResponse
    {
        $role = Auth::user()->role ?? null;
        if ($role !== 'owner') {
            return back()->with('error', 'Hanya OWNER yang boleh membatalkan QC.');
        }

        try {
            $this->qc->cancelCuttingQc($cuttingJob);
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

}
