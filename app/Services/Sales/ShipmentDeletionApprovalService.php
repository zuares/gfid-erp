<?php

namespace App\Services\Sales;

use App\Models\Shipment;
use App\Models\ShipmentDeletionRequest;
use App\Models\ShipmentLine;
use App\Models\ShipmentOrderScan;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ShipmentDeletionApprovalService
{
    public function requestOrderDeletion(Shipment $shipment, string $orderNo, User $requester): ShipmentDeletionRequest
    {
        return DB::transaction(function () use ($shipment, $orderNo, $requester) {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            $this->ensureDraft($shipment);

            $scan = ShipmentOrderScan::query()
                ->where('shipment_id', $shipment->id)
                ->where('order_no', mb_strtoupper(trim($orderNo)))
                ->lockForUpdate()
                ->firstOrFail();

            $existing = ShipmentDeletionRequest::query()
                ->where('shipment_id', $shipment->id)
                ->where('target_type', ShipmentDeletionRequest::TARGET_ORDER)
                ->where('shipment_order_scan_id', $scan->id)
                ->where('status', ShipmentDeletionRequest::STATUS_PENDING)
                ->first();

            if ($existing) {
                return $existing;
            }

            return ShipmentDeletionRequest::create([
                'shipment_id' => $shipment->id,
                'target_type' => ShipmentDeletionRequest::TARGET_ORDER,
                'shipment_order_scan_id' => $scan->id,
                'requested_by' => $requester->id,
                'target_label' => $scan->order_no,
                'reason' => 'Admin meminta penghapusan order beserta item shipment.',
                'status' => ShipmentDeletionRequest::STATUS_PENDING,
            ]);
        });
    }

    public function requestLineDeletion(ShipmentLine $line, User $requester): ShipmentDeletionRequest
    {
        return DB::transaction(function () use ($line, $requester) {
            $line = ShipmentLine::query()
                ->with(['item:id,code,name', 'wave'])
                ->lockForUpdate()
                ->findOrFail($line->id);
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($line->shipment_id);
            $this->ensureDraft($shipment);

            if ($line->wave?->isPosted()) {
                throw new RuntimeException('Baris pada gelombang yang sudah diposting tidak bisa dihapus.');
            }

            $existing = ShipmentDeletionRequest::query()
                ->where('shipment_id', $shipment->id)
                ->where('target_type', ShipmentDeletionRequest::TARGET_ITEM)
                ->where('shipment_line_id', $line->id)
                ->where('status', ShipmentDeletionRequest::STATUS_PENDING)
                ->first();

            if ($existing) {
                return $existing;
            }

            return ShipmentDeletionRequest::create([
                'shipment_id' => $shipment->id,
                'target_type' => ShipmentDeletionRequest::TARGET_ITEM,
                'shipment_line_id' => $line->id,
                'requested_by' => $requester->id,
                'target_label' => trim((string) ($line->item?->code ?: 'Item #'.$line->id)),
                'reason' => 'Admin meminta penghapusan item shipment.',
                'status' => ShipmentDeletionRequest::STATUS_PENDING,
            ]);
        });
    }

    public function approve(ShipmentDeletionRequest $deletionRequest, User $reviewer): void
    {
        DB::transaction(function () use ($deletionRequest, $reviewer) {
            $request = ShipmentDeletionRequest::query()
                ->lockForUpdate()
                ->findOrFail($deletionRequest->id);

            if (! $request->isPending()) {
                throw new RuntimeException('Permintaan ini sudah diproses.');
            }

            $shipment = Shipment::query()->lockForUpdate()->findOrFail($request->shipment_id);
            $this->ensureDraft($shipment);

            if ($request->target_type === ShipmentDeletionRequest::TARGET_ORDER) {
                $scan = ShipmentOrderScan::query()
                    ->where('shipment_id', $shipment->id)
                    ->lockForUpdate()
                    ->find($request->shipment_order_scan_id);

                if (! $scan) {
                    throw new RuntimeException('Order yang diminta sudah tidak ditemukan.');
                }

                $this->deleteOrderWithinTransaction($shipment, $scan);
            } elseif ($request->target_type === ShipmentDeletionRequest::TARGET_ITEM) {
                $line = ShipmentLine::query()
                    ->where('shipment_id', $shipment->id)
                    ->lockForUpdate()
                    ->find($request->shipment_line_id);

                if (! $line) {
                    throw new RuntimeException('Item yang diminta sudah tidak ditemukan.');
                }

                $this->deleteLineWithinTransaction($line);
            } else {
                throw new RuntimeException('Jenis permintaan penghapusan tidak valid.');
            }

            $request->update([
                'status' => ShipmentDeletionRequest::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
        });
    }

    public function reject(ShipmentDeletionRequest $deletionRequest, User $reviewer, ?string $note = null): void
    {
        $updated = ShipmentDeletionRequest::query()
            ->whereKey($deletionRequest->id)
            ->where('status', ShipmentDeletionRequest::STATUS_PENDING)
            ->update([
                'status' => ShipmentDeletionRequest::STATUS_REJECTED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note ?: 'Permintaan ditolak oleh owner.',
            ]);

        if ($updated === 0) {
            throw new RuntimeException('Permintaan ini sudah diproses.');
        }
    }

    public function deleteOrder(Shipment $shipment, string $orderNo): void
    {
        DB::transaction(function () use ($shipment, $orderNo) {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            $this->ensureDraft($shipment);

            $scan = ShipmentOrderScan::query()
                ->where('shipment_id', $shipment->id)
                ->where('order_no', mb_strtoupper(trim($orderNo)))
                ->lockForUpdate()
                ->first();

            if ($scan) {
                $this->deleteOrderWithinTransaction($shipment, $scan);
            }
        });
    }

    public function deleteLine(ShipmentLine $line): void
    {
        DB::transaction(function () use ($line) {
            $line = ShipmentLine::query()->lockForUpdate()->findOrFail($line->id);
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($line->shipment_id);
            $this->ensureDraft($shipment);
            $this->deleteLineWithinTransaction($line);
        });
    }

    private function deleteOrderWithinTransaction(Shipment $shipment, ShipmentOrderScan $scan): void
    {
        $activeScanCount = ShipmentOrderScan::query()
            ->where('shipment_id', $shipment->id)
            ->where('status', '!=', 'skip')
            ->count();

        $lines = ShipmentLine::query()
            ->where('shipment_id', $shipment->id)
            ->where(function ($query) use ($scan, $activeScanCount) {
                $query->where('shipment_order_scan_id', $scan->id);
                if ($activeScanCount === 1) {
                    $query->orWhereNull('shipment_order_scan_id');
                }
            })
            ->with('wave')
            ->lockForUpdate()
            ->get();

        if ($lines->contains(fn ($line) => $line->wave?->isPosted())) {
            throw new RuntimeException('Item pada gelombang yang sudah diposting tidak bisa dihapus.');
        }

        foreach ($lines as $line) {
            $this->releaseAllocatedStock($line);
            $line->delete();
        }

        $scan->delete();
    }

    private function deleteLineWithinTransaction(ShipmentLine $line): void
    {
        if ($line->wave?->isPosted()) {
            throw new RuntimeException('Baris pada gelombang yang sudah diposting tidak bisa dihapus.');
        }

        $this->releaseAllocatedStock($line);
        $line->delete();
    }

    private function releaseAllocatedStock(ShipmentLine $line): void
    {
        if ((int) $line->allocated_qty <= 0) {
            return;
        }

        $warehouse = Warehouse::query()->where('code', 'WH-RTS')->first();
        if ($warehouse) {
            app(\App\Services\Inventory\InventoryService::class)->releaseStock(
                $warehouse->id,
                $line->item_id,
                $line->allocated_qty,
            );
        }
    }

    private function ensureDraft(Shipment $shipment): void
    {
        if ($shipment->status !== 'draft') {
            throw new RuntimeException('Shipment sudah tidak berstatus draft.');
        }
    }
}
