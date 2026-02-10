<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\MpReconciliation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MpReconciliationController extends Controller
{
    /**
     * Resolve / update a reconciliation record.
     *
     * Actions:
     * - link_to_shipment
     * - apply_to_lines        ✅ NEW (apply MP items -> shipment_lines)
     * - mark_needs_review
     * - mark_skipped
     * - mark_resolved
     */
    public function resolve(Request $request, MpReconciliation $rec)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in([
                'link_to_shipment',
                'apply_to_lines',
                'mark_needs_review',
                'mark_skipped',
                'mark_resolved',
            ])],
            'shipment_id' => ['nullable', 'integer', 'exists:shipments,id'],
            'notes' => ['nullable', 'string', 'max:5000'],

            // optional: untuk apply_to_lines
            // replace = set qty_scanned = mp_qty (default, paling aman)
            // add     = qty_scanned += mp_qty
            'mode' => ['nullable', Rule::in(['replace', 'add'])],
        ]);

        $action = $data['action'];

        $notes = array_key_exists('notes', $data) ? trim((string) ($data['notes'] ?? '')) : null;
        if ($notes === '') {
            $notes = null;
        }

        // -------------------------
        // link_to_shipment
        // -------------------------
        if ($action === 'link_to_shipment') {
            if (empty($data['shipment_id'])) {
                return back()
                    ->withErrors(['shipment_id' => 'shipment_id wajib diisi untuk link_to_shipment'])
                    ->withInput();
            }

            $rec->shipment_id = (int) $data['shipment_id'];
            $rec->status = 'resolved';
            $rec->match_key = 'manual';
            $rec->match_confidence = 100;
            $rec->matched_by = Auth::id();
            $rec->matched_at = now();

            if ($notes !== null) {
                $rec->notes = $notes;
            }

            $rec->save();
            return back()->with('ok', 'Reconcile: linked to shipment.');
        }

        // -------------------------
        // apply_to_lines ✅ NEW
        // -------------------------
        if ($action === 'apply_to_lines') {
            // kalau request kirim shipment_id, set dulu (biar bisa apply walau belum pernah link)
            if (!empty($data['shipment_id'])) {
                $rec->shipment_id = (int) $data['shipment_id'];
            }

            if (!$rec->shipment_id) {
                return back()->with('error', 'Apply ditolak: shipment_id belum dipilih.');
            }

            $mode = $data['mode'] ?? 'replace'; // default replace

            $apply = $this->applyMpItemsToShipmentLines(
                shipmentId: (int) $rec->shipment_id,
                mpShipmentId: (string) $rec->mp_shipment_id,
                mode: $mode
            );

            if (!($apply['ok'] ?? false)) {
                return back()->with('error', $apply['message'] ?? 'Apply gagal.');
            }

            // setelah apply, set resolved
            $rec->status = 'resolved';
            $rec->match_key = $rec->match_key ?: ('apply_lines:' . $mode);
            $rec->match_confidence = max((int) ($rec->match_confidence ?? 0), 95);
            $rec->matched_by = Auth::id();
            $rec->matched_at = now();

            if ($notes !== null) {
                $rec->notes = $notes;
            }

            $rec->save();

            return back()->with('ok', 'Applied to shipment_lines: '
                . ($apply['lines_upserted'] ?? 0) . ' line(s)'
                . ' • unmapped sku: ' . ($apply['unmapped_sku_count'] ?? 0)
            );
        }

        // -------------------------
        // mark_needs_review
        // -------------------------
        if ($action === 'mark_needs_review') {
            $rec->status = 'needs_review';

            // reset hasil match (balik review)
            $rec->match_key = null;
            $rec->match_confidence = null;
            $rec->matched_by = null;
            $rec->matched_at = null;

            // shipment_id tetap (kalau mau unlink, aktifkan)
            // $rec->shipment_id = null;

            if ($notes !== null) {
                $rec->notes = $notes;
            }

            $rec->save();
            return back()->with('ok', 'Reconcile: marked needs_review.');
        }

        // -------------------------
        // mark_resolved
        // -------------------------
        if ($action === 'mark_resolved') {
            if (!$rec->shipment_id) {
                return back()->with('error', 'Tidak bisa mark_resolved tanpa shipment_id. Link dulu.');
            }

            $rec->status = 'resolved';
            $rec->match_key = $rec->match_key ?: 'manual';
            $rec->match_confidence = $rec->match_confidence ?: 100;
            $rec->matched_by = Auth::id();
            $rec->matched_at = now();

            if ($notes !== null) {
                $rec->notes = $notes;
            }

            $rec->save();
            return back()->with('ok', 'Reconcile: marked resolved.');
        }

        // -------------------------
        // mark_skipped (default)
        // -------------------------
        $rec->status = 'skipped';
        $rec->matched_by = Auth::id();
        $rec->matched_at = now();

        if ($notes !== null) {
            $rec->notes = $notes;
        }

        $rec->save();
        return back()->with('ok', 'Reconcile: skipped.');
    }

    /**
     * Apply MP items (mp_packet_items) to shipment_lines.
     *
     * mode:
     * - replace: qty_scanned = mp_qty (default, aman untuk "rekonsiliasi")
     * - add: qty_scanned += mp_qty
     */
    private function applyMpItemsToShipmentLines(int $shipmentId, string $mpShipmentId, string $mode = 'replace'): array
    {
        // 1) aggregate MP items yang sudah mapped (item_id NOT NULL)
        $mpItems = DB::table('mp_packet_items as mpi')
            ->selectRaw('mpi.item_id as item_id, SUM(mpi.qty) as mp_qty')
            ->whereRaw("CAST(mpi.mp_shipment_id AS TEXT) = ?", [$mpShipmentId])
            ->whereNotNull('mpi.item_id')
            ->groupBy('mpi.item_id')
            ->get();

        // 2) cek unmapped sku count (buat info user)
        $unmappedSkuCount = (int) DB::table('mp_packet_items as mpi')
            ->whereRaw("CAST(mpi.mp_shipment_id AS TEXT) = ?", [$mpShipmentId])
            ->whereNull('mpi.item_id')
            ->distinct()
            ->count('sku');

        if ($mpItems->isEmpty()) {
            return [
                'ok' => false,
                'message' => 'Tidak ada mp_packet_items yang sudah termapping (item_id null semua). Map SKU dulu.',
                'unmapped_sku_count' => $unmappedSkuCount,
            ];
        }

        $now = now();

        DB::transaction(function () use ($shipmentId, $mpItems, $mode, $now) {
            foreach ($mpItems as $r) {
                $itemId = (int) $r->item_id;
                $qty = (int) $r->mp_qty;

                // cari existing line
                $lineId = DB::table('shipment_lines')
                    ->where('shipment_id', $shipmentId)
                    ->where('item_id', $itemId)
                    ->value('id');

                if ($lineId) {
                    if ($mode === 'add') {
                        DB::table('shipment_lines')
                            ->where('id', $lineId)
                            ->update([
                                'qty_scanned' => DB::raw('COALESCE(qty_scanned,0) + ' . $qty),
                                'updated_at' => $now,
                            ]);
                    } else {
                        // replace
                        DB::table('shipment_lines')
                            ->where('id', $lineId)
                            ->update([
                                'qty_scanned' => $qty,
                                'updated_at' => $now,
                            ]);
                    }
                } else {
                    DB::table('shipment_lines')->insert([
                        'shipment_id' => $shipmentId,
                        'item_id' => $itemId,
                        'qty_scanned' => $qty,
                        'notes' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });

        return [
            'ok' => true,
            'lines_upserted' => $mpItems->count(),
            'unmapped_sku_count' => $unmappedSkuCount,
            'mode' => $mode,
        ];
    }

    /**
     * Compare MP items vs Shipment lines (item-level diff).
     * Returns JSON for modal (includes unmapped SKUs).
     */
    public function diff(MpReconciliation $rec)
    {
        if (!$rec->shipment_id) {
            return response()->json([
                'ok' => false,
                'message' => 'Belum ada shipment_id pada reconciliation ini.',
            ], 422);
        }

        // 1) Shipment aggregate: shipment_lines.qty_scanned
        $shipAgg = DB::table('shipment_lines as sl')
            ->selectRaw('sl.item_id, SUM(sl.qty_scanned) as ship_qty')
            ->where('sl.shipment_id', (int) $rec->shipment_id)
            ->groupBy('sl.item_id')
            ->pluck('ship_qty', 'item_id'); // [item_id => qty]

        // 2) MP aggregate: mp_packet_items.qty by item_id (hasil mapping)
        $mpAgg = DB::table('mp_packet_items as mpi')
            ->selectRaw('mpi.item_id as item_id, SUM(mpi.qty) as mp_qty')
            ->whereRaw("CAST(mpi.mp_shipment_id AS TEXT) = ?", [(string) $rec->mp_shipment_id])
            ->whereNotNull('mpi.item_id')
            ->groupBy('mpi.item_id')
            ->pluck('mp_qty', 'item_id'); // [item_id => qty]

        // 3) Unmapped SKUs (item_id null)
        $unmapped = DB::table('mp_packet_items as mpi')
            ->selectRaw('mpi.sku, SUM(mpi.qty) as qty')
            ->whereRaw("CAST(mpi.mp_shipment_id AS TEXT) = ?", [(string) $rec->mp_shipment_id])
            ->whereNull('mpi.item_id')
            ->groupBy('mpi.sku')
            ->orderByRaw('SUM(mpi.qty) desc')
            ->limit(50)
            ->get()
            ->map(fn($r) => ['sku' => (string) $r->sku, 'qty' => (int) $r->qty])
            ->values()
            ->all();

        // 4) Union item ids
        $itemIds = collect(array_keys($shipAgg->toArray()))
            ->merge(array_keys($mpAgg->toArray()))
            ->unique()
            ->values();

        if ($itemIds->isEmpty()) {
            return response()->json([
                'ok' => true,
                'rec_id' => $rec->id,
                'mp_shipment_id' => $rec->mp_shipment_id,
                'shipment_id' => $rec->shipment_id,
                'stats' => [
                    'mp_total' => 0,
                    'ship_total' => 0,
                    'delta_total' => 0,
                    'missing_count' => 0,
                    'extra_count' => 0,
                    'lines' => 0,
                ],
                'lines' => [],
                'unmapped' => $unmapped,
                'unmapped_count' => count($unmapped),
            ]);
        }

        $items = Item::query()
            ->whereIn('id', $itemIds)
            ->get(['id', 'code', 'name', 'unit'])
            ->keyBy('id');

        $lines = [];
        $mpTotal = 0.0;
        $shipTotal = 0.0;
        $missingCount = 0;
        $extraCount = 0;

        foreach ($itemIds as $itemId) {
            $mpQty = (float) ($mpAgg[$itemId] ?? 0);
            $shipQty = (float) ($shipAgg[$itemId] ?? 0);
            $diff = $shipQty - $mpQty;

            $mpTotal += $mpQty;
            $shipTotal += $shipQty;

            $status = 'ok';
            if ($diff < 0) {
                $status = 'missing';
                $missingCount++;
            } elseif ($diff > 0) {
                $status = 'extra';
                $extraCount++;
            }

            $it = $items[$itemId] ?? null;

            $lines[] = [
                'item_id' => (int) $itemId,
                'code' => $it?->code ?? ('#' . $itemId),
                'name' => $it?->name ?? '-',
                'unit' => $it?->unit ?? null,
                'mp_qty' => $mpQty,
                'ship_qty' => $shipQty,
                'diff' => $diff,
                'status' => $status,
            ];
        }

        usort($lines, fn($a, $b) => abs($b['diff']) <=> abs($a['diff']));

        return response()->json([
            'ok' => true,
            'rec_id' => $rec->id,
            'mp_shipment_id' => $rec->mp_shipment_id,
            'shipment_id' => $rec->shipment_id,
            'stats' => [
                'mp_total' => $mpTotal,
                'ship_total' => $shipTotal,
                'delta_total' => $shipTotal - $mpTotal,
                'missing_count' => $missingCount,
                'extra_count' => $extraCount,
                'lines' => count($lines),
            ],
            'lines' => $lines,
            'unmapped' => $unmapped,
            'unmapped_count' => count($unmapped),
        ]);
    }
}
