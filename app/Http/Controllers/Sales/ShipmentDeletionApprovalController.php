<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\ShipmentDeletionRequest;
use App\Services\Sales\ShipmentDeletionApprovalService;
use Illuminate\Http\Request;
use RuntimeException;

class ShipmentDeletionApprovalController extends Controller
{
    public function __construct(private readonly ShipmentDeletionApprovalService $service) {}

    public function index(Request $request)
    {
        abort_unless($request->user()?->isOwner(), 403);

        $status = $request->query('status', ShipmentDeletionRequest::STATUS_PENDING);
        $allowedStatuses = [
            ShipmentDeletionRequest::STATUS_PENDING,
            ShipmentDeletionRequest::STATUS_APPROVED,
            ShipmentDeletionRequest::STATUS_REJECTED,
        ];
        if (! in_array($status, $allowedStatuses, true)) {
            $status = ShipmentDeletionRequest::STATUS_PENDING;
        }

        $requests = ShipmentDeletionRequest::query()
            ->with([
                'shipment:id,code,status',
                'requestedBy:id,name,role',
                'reviewedBy:id,name',
                'orderScan:id,order_no',
                'line:id,item_id,qty_scanned',
                'line.item:id,code,name',
            ])
            ->where('status', $status)
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('sales.shipments.deletion_requests.index', compact('requests', 'status'));
    }

    public function approve(Request $request, ShipmentDeletionRequest $deletionRequest)
    {
        abort_unless($request->user()?->isOwner(), 403);

        try {
            $this->service->approve($deletionRequest, $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Permintaan penghapusan disetujui dan target berhasil dihapus.');
    }

    public function reject(Request $request, ShipmentDeletionRequest $deletionRequest)
    {
        abort_unless($request->user()?->isOwner(), 403);

        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->service->reject($deletionRequest, $request->user(), $data['review_note'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Permintaan penghapusan ditolak.');
    }
}
