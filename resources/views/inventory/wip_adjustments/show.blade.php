@extends('layouts.app')

@section('title', 'WIP Adjustment • ' . $adjustment->code)

@section('content')
    @php
        $user = auth()->user();
        $role = $user?->role ?? null;
        $canApprove = in_array($role, ['owner', 'admin'], true) && $adjustment->status === 'pending';
    @endphp

    <div class="container-xl py-3">

        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h1 class="h5 mb-0">{{ $adjustment->code }}</h1>
                <div class="text-muted" style="font-size:.85rem;">
                    {{ $adjustment->warehouse?->code }} • {{ $adjustment->date?->format('d M Y') }}
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('inventory.wip_adjustments.index') }}" class="btn btn-sm btn-outline-secondary">
                    ← Kembali
                </a>

                @if ($canApprove)
                    <form method="POST" action="{{ route('inventory.wip_adjustments.approve', $adjustment) }}">
                        @csrf
                        <button class="btn btn-sm btn-success" onclick="return confirm('Approve & koreksi stok WIP?')">
                            Approve
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- HEADER INFO --}}
        <div class="card card-body mb-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted small">Status</div>
                    <div class="fw-semibold">
                        {{ strtoupper($adjustment->status) }}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Dibuat oleh</div>
                    <div>{{ $adjustment->creator?->name }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Disetujui</div>
                    <div>
                        {{ $adjustment->approver?->name ?? '—' }}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Catatan</div>
                    <div>{{ $adjustment->reason }}</div>
                </div>
            </div>
        </div>

        {{-- LINES --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th class="text-end">Qty Sebelum</th>
                            <th class="text-end">Perubahan</th>
                            <th class="text-end">Qty Sesudah</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($adjustment->lines as $i => $line)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <div class="fw-semibold text-mono">{{ $line->item?->code }}</div>
                                    <div class="text-muted" style="font-size:.8rem;">
                                        {{ $line->item?->name }}
                                    </div>
                                </td>
                                <td class="text-end text-mono">
                                    {{ number_format($line->qty_before ?? 0, 2) }}
                                </td>
                                <td
                                    class="text-end text-mono
                            {{ $line->qty_change > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $line->qty_change > 0 ? '+' : '' }}
                                    {{ number_format($line->qty_change, 2) }}
                                </td>
                                <td class="text-end text-mono">
                                    {{ number_format($line->qty_after ?? 0, 2) }}
                                </td>
                                <td>{{ $line->notes }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
