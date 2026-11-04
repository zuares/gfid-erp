@extends('layouts.app')

@section('title', 'Approval Penghapusan Shipment')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Approval Penghapusan</h1>
            <p class="text-muted mb-0">Permintaan hapus order/item dari admin menunggu keputusan owner.</p>
        </div>
        <a href="{{ route('sales.shipments.index') }}" class="btn btn-outline-secondary btn-sm">Kembali ke shipment</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach([
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ] as $tab => $label)
            <a href="{{ route('sales.shipments.deletion_requests.index', ['status' => $tab]) }}"
               class="btn btn-sm {{ $status === $tab ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Target</th>
                        <th>Shipment</th>
                        <th>Diminta oleh</th>
                        <th>Waktu</th>
                        <th>Status / aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $deletionRequest)
                        @php
                            $target = $deletionRequest->target_type === 'order'
                                ? 'Order '.($deletionRequest->orderScan?->order_no ?: $deletionRequest->target_label)
                                : 'Item '.($deletionRequest->line?->item?->code ?: $deletionRequest->target_label);
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $target }}</div>
                                <div class="small text-muted">{{ $deletionRequest->target_type === 'order' ? 'Order + item di dalamnya' : 'Satu item shipment' }}</div>
                                @if ($deletionRequest->reason)
                                    <div class="small text-muted mt-1">{{ $deletionRequest->reason }}</div>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('sales.shipments.confirm_orders', $deletionRequest->shipment_id) }}">
                                    {{ $deletionRequest->shipment?->code ?: '#'.$deletionRequest->shipment_id }}
                                </a>
                            </td>
                            <td>{{ $deletionRequest->requestedBy?->name ?: 'User #'.$deletionRequest->requested_by }}</td>
                            <td class="small">{{ optional($deletionRequest->created_at)->format('d M Y H:i') }}</td>
                            <td>
                                @if ($deletionRequest->isPending())
                                    <div class="d-flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('sales.shipments.deletion_requests.approve', $deletionRequest) }}" onsubmit="return confirm('Setujui penghapusan target ini?')">
                                            @csrf
                                            <button class="btn btn-sm btn-danger" type="submit">Setujui & hapus</button>
                                        </form>
                                        <form method="POST" action="{{ route('sales.shipments.deletion_requests.reject', $deletionRequest) }}" class="d-flex gap-1">
                                            @csrf
                                            <input type="text" name="review_note" class="form-control form-control-sm" placeholder="Catatan (opsional)">
                                            <button class="btn btn-sm btn-outline-secondary" type="submit">Tolak</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="badge text-bg-{{ $deletionRequest->status === 'approved' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($deletionRequest->status) }}
                                    </span>
                                    <div class="small text-muted mt-1">
                                        {{ $deletionRequest->reviewedBy?->name ?: '-' }} · {{ optional($deletionRequest->reviewed_at)->format('d M Y H:i') }}
                                    </div>
                                    @if ($deletionRequest->review_note)
                                        <div class="small text-muted">{{ $deletionRequest->review_note }}</div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada permintaan pada status ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())
            <div class="card-footer">{{ $requests->links() }}</div>
        @endif
    </div>
</div>
@endsection
