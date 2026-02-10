<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MpReconciliation;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceReconcileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MpReconciliationQueueController extends Controller
{
    private const PREVIEW_SESSION_KEY = 'mp_queue_reconcile_preview';

    public function index(Request $request)
    {
        $status = (string) $request->get('status', 'needs_review');
        $q = trim((string) $request->get('q', ''));

        $channel = $request->get('channel'); // shopee|tiktok|null
        $storeId = $request->get('store_id'); // int|null
        $minConf = $request->get('min_conf'); // int|null
        $maxConf = $request->get('max_conf'); // int|null
        $key = $request->get('key'); // match_key|null
        $hasShipment = $request->get('has_shipment'); // yes|no|null
        $date = $request->get('date'); // YYYY-MM-DD|null (MP day)

        // Quick toggles (MODE kerja)
        $actionable = (string) $request->get('actionable', '') === '1';
        $lowConf = (string) $request->get('low_conf', '') === '1';
        $unlinked = (string) $request->get('unlinked', '') === '1';

        /**
         * SCOPE = dataset konteks (mempengaruhi counts & keys)
         * - scope: channel/store/date + q (tanpa membypass)
         * - quick toggles masuk scope
         * - detail filters (min/max conf, has_shipment, key, status chip) hanya untuk rows
         */
        $scope = MpReconciliation::query();

        // -----------------------------
        // 1) Scope by mpShipment (channel/store/date)
        // -----------------------------
        $hasShipmentScope = (bool) ($channel || $storeId || $date);

        if ($hasShipmentScope) {
            $scope->whereHas('mpShipment', function ($mq) use ($channel, $storeId, $date) {
                $mq->when($channel, fn($z) => $z->where('channel', $channel))
                    ->when($storeId, fn($z) => $z->where('store_id', (int) $storeId));

                if ($date) {
                    $mq->where(function ($dq) use ($date) {
                        $dq->whereDate('shipped_at', $date)
                            ->orWhere(function ($qq) use ($date) {
                                $qq->whereNull('shipped_at')
                                    ->whereDate('order_created_at', $date);
                            });
                    });
                }
            });
        }

        // -----------------------------
        // 2) Search q (TIDAK membypass scope)
        // - kalau scope channel/store/date aktif => q harus match salah satu field di mpShipment ATAU reconciliation
        // - kalau scope tidak aktif => q bisa cari global (mpShipment atau reconciliation)
        // -----------------------------
        if ($q !== '') {
            $scope->where(function ($w) use ($q) {
                $w->whereHas('mpShipment', function ($mq) use ($q) {
                    $mq->where(function ($sq) use ($q) {
                        $sq->where('platform_order_id', 'like', "%{$q}%")
                            ->orWhere('tracking_no', 'like', "%{$q}%")
                            ->orWhere('platform_shipment_id', 'like', "%{$q}%");
                    });
                })
                    ->orWhere('mp_shipment_id', 'like', "%{$q}%")
                    ->orWhere('match_key', 'like', "%{$q}%");
            });
        }

        // -----------------------------
        // 3) Scope by quick toggles (mode kerja)
        // -----------------------------
        $scope
            ->when($actionable, function ($x) {
                $x->where('status', 'needs_review')
                    ->whereNotNull('shipment_id')
                    ->where(function ($w) {
                        $w->whereNull('match_key')
                            ->orWhere('match_key', '!=', 'no_ops_on_day');
                    });
            })
            ->when($lowConf, function ($x) {
                $x->where('status', 'needs_review')
                    ->where('match_confidence', '<=', 75);
            })
            ->when($unlinked, function ($x) {
                $x->where('status', 'needs_review')
                    ->whereNull('shipment_id');
            });

        // -----------------------------
        // 4) Rows = scope + detail filters
        // -----------------------------
        $base = (clone $scope)
            ->with([
                // batasi kolom agar ringan
                'mpShipment:id,store_id,channel,platform_order_id,platform_shipment_id,tracking_no,shipped_at,order_created_at,total_qty,status_norm,grand_total',
                'shipment:id,code,date,status,awb',
            ])

            // status chip normal hanya kalau actionable tidak aktif
            ->when(!$actionable && $status !== 'all', fn($x) => $x->where('status', $status))

            // detail filters
            ->when($minConf !== null && $minConf !== '', fn($x) => $x->where('match_confidence', '>=', (int) $minConf))
            ->when($maxConf !== null && $maxConf !== '', fn($x) => $x->where('match_confidence', '<=', (int) $maxConf))
            ->when($key !== null && $key !== '', fn($x) => $x->where('match_key', $key))
            ->when($hasShipment === 'yes', fn($x) => $x->whereNotNull('shipment_id'))
            ->when($hasShipment === 'no', fn($x) => $x->whereNull('shipment_id'))

            ->orderByDesc('id');

        $rows = $base->paginate(50)->withQueryString();

        // -----------------------------
        // 5) counts (scoped)
        // -----------------------------
        $counts = (clone $scope)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        // -----------------------------
        // 6) keys (scoped) - batasi biar tidak berat
        // -----------------------------
        $keys = (clone $scope)
            ->select('match_key')
            ->whereNotNull('match_key')
            ->groupBy('match_key')
            ->orderBy('match_key')
            ->limit(200)
            ->pluck('match_key');

        $stores = Store::orderBy('code')->get(['id', 'code', 'name']);

        $preview = session(self::PREVIEW_SESSION_KEY);
        $window = $preview['params']['window'] ?? null;
        $threshold = $preview['params']['threshold'] ?? null;

        return view('marketplace.reconcile_queue', compact(
            'rows', 'status', 'q', 'counts',
            'channel', 'storeId', 'minConf', 'maxConf', 'key', 'hasShipment', 'date',
            'keys', 'stores',
            'actionable', 'lowConf', 'unlinked',
            'preview', 'window', 'threshold'
        ));
    }

    /**
     * Bulk approve / skip selected queue rows.
     * (ini BUKAN reconcile run)
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'skip'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:mp_reconciliations,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $targetStatus = $data['action'] === 'approve' ? 'resolved' : 'skipped';

        // bedakan: notes tidak dikirim vs dikirim tapi kosong (mau clear)
        $hasNotes = array_key_exists('notes', $data);

        DB::transaction(function () use ($data, $targetStatus, $hasNotes) {
            $payload = ['status' => $targetStatus];

            if ($hasNotes) {
                $notes = trim((string) ($data['notes'] ?? ''));
                $payload['notes'] = $notes === '' ? null : $notes;
            }

            MpReconciliation::whereIn('id', $data['ids'])->update($payload);
        });

        return back()->with('ok', 'Bulk update: ' . count($data['ids']) . ' row → ' . $targetStatus);
    }

    /**
     * Preview reconcile run (dry-run)
     */
    public function preview(Request $request, MarketplaceReconcileService $svc)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'channel' => ['nullable', 'in:shopee,tiktok'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'window' => ['required', 'integer', 'min:0', 'max:7'],
            'threshold' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $params = $this->normalizeParams($data);

        $res = $svc->reconcileByDate(
            dateYmd: $params['date'],
            channel: $params['channel'],
            storeId: $params['store_id'],
            windowDays: $params['window'],
            threshold: $params['threshold'],
            dryRun: true
        );

        session()->put(self::PREVIEW_SESSION_KEY, [
            'params' => $params,
            'result' => $res,
        ]);

        return back()->with('ok', 'Preview siap.');
    }

    /**
     * Commit reconcile run (write)
     * Safety:
     * - wajib ada preview session
     * - params commit harus sama dengan preview terakhir
     */
    public function commit(Request $request, MarketplaceReconcileService $svc)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'channel' => ['nullable', 'in:shopee,tiktok'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'window' => ['required', 'integer', 'min:0', 'max:7'],
            'threshold' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $params = $this->normalizeParams($data);

        $pv = session(self::PREVIEW_SESSION_KEY);
        if (!$pv || empty($pv['params'])) {
            return back()->with('error', 'Commit ditolak: lakukan Preview dulu.');
        }

        $pvParams = $this->normalizeParams($pv['params']);

        foreach (['date', 'channel', 'store_id', 'window', 'threshold'] as $k) {
            if (($pvParams[$k] ?? null) !== ($params[$k] ?? null)) {
                return back()->with('error', 'Commit ditolak: parameter berbeda dari Preview terakhir. Preview ulang.');
            }
        }

        $res = $svc->reconcileByDate(
            dateYmd: $params['date'],
            channel: $params['channel'],
            storeId: $params['store_id'],
            windowDays: $params['window'],
            threshold: $params['threshold'],
            dryRun: false
        );

        session()->forget(self::PREVIEW_SESSION_KEY);

        return back()->with('ok', 'Reconcile tersimpan. Auto: ' . ($res['stats']['matched'] ?? 0) . ' • Review: ' . ($res['stats']['needs_review'] ?? 0));
    }

    private function normalizeParams(array $data): array
    {
        return [
            'date' => (string) ($data['date'] ?? ''),
            'channel' => ($data['channel'] ?? null) ?: null,
            'store_id' => isset($data['store_id']) && $data['store_id'] !== '' ? (int) $data['store_id'] : null,
            'window' => (int) ($data['window'] ?? 1),
            'threshold' => (int) ($data['threshold'] ?? 80),
        ];
    }
}
