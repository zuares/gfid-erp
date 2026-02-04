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
        $status = $request->get('status', 'needs_review');
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
         * Opsi A:
         * - scope: channel/store/date/q + quick toggles
         * - detail filters (min/max conf, has_shipment, key, status chip) hanya untuk rows
         */
        $scope = MpReconciliation::query();

        // 1) scope by mpShipment (channel/store/date) + search (q)
        $scope->when($channel || $storeId || $date || $q !== '', function ($x) use ($channel, $storeId, $date, $q) {
            $x->where(function ($w) use ($channel, $storeId, $date, $q) {

                $w->whereHas('mpShipment', function ($m) use ($channel, $storeId, $date, $q) {
                    $m->when($channel, fn($mq) => $mq->where('channel', $channel))
                        ->when($storeId, fn($mq) => $mq->where('store_id', (int) $storeId));

                    if ($date) {
                        $m->where(function ($dq) use ($date) {
                            $dq->whereDate('shipped_at', $date)
                                ->orWhere(function ($qq) use ($date) {
                                    $qq->whereNull('shipped_at')
                                        ->whereDate('order_created_at', $date);
                                });
                        });
                    }

                    if ($q !== '') {
                        $m->where(function ($sq) use ($q) {
                            $sq->where('platform_order_id', 'like', "%{$q}%")
                                ->orWhere('tracking_no', 'like', "%{$q}%")
                                ->orWhere('platform_shipment_id', 'like', "%{$q}%");
                        });
                    }
                });

                // search juga langsung di mp_reconciliations
                if ($q !== '') {
                    $w->orWhere('mp_shipment_id', 'like', "%{$q}%")
                        ->orWhere('match_key', 'like', "%{$q}%");
                }
            });
        });

        // 2) scope by quick toggles (mode kerja)
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

        // 3) rows = scope + detail filters
        $base = (clone $scope)
            ->with(['mpShipment', 'shipment'])

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

        // 4) counts scoped (ngikut dataset scope + toggles)
        $counts = (clone $scope)
            ->selectRaw("status, COUNT(*) as c")
            ->groupBy('status')
            ->pluck('c', 'status');

        // 5) keys scoped (ngikut dataset scope + toggles)
        $keys = (clone $scope)
            ->select('match_key')
            ->whereNotNull('match_key')
            ->groupBy('match_key')
            ->orderBy('match_key')
            ->pluck('match_key');

        $stores = Store::orderBy('code')->get(['id', 'code', 'name']);

        $preview = session(self::PREVIEW_SESSION_KEY);

        // bantu persist window/threshold di Blade
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
        $notes = $data['notes'] ?? null;

        DB::transaction(function () use ($data, $targetStatus, $notes) {
            MpReconciliation::whereIn('id', $data['ids'])->update([
                'status' => $targetStatus,
                'notes' => $notes ? trim($notes) : DB::raw('notes'),
            ]);
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
