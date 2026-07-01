@extends('layouts.app')
@section('title', 'CRM Orders')

@push('head')
<style>
.status-badge { font-size: .68rem; font-weight: 800; padding: .2rem .55rem; border-radius: 999px; text-transform: uppercase; letter-spacing: .05em; }
.status-pending    { background: #fef9c3; color: #854d0e; }
.status-confirmed  { background: #dbeafe; color: #1e40af; }
.status-processing { background: #ede9fe; color: #5b21b6; }
.status-shipped    { background: #d1fae5; color: #065f46; }
.status-done       { background: #d1fae5; color: #065f46; }
.status-cancelled  { background: #fee2e2; color: #991b1b; }
.tab-pill { border: 1.5px solid #e2e8f0; border-radius: 999px; padding: .25rem .7rem; font-size: .72rem; font-weight: 700; text-decoration: none; color: #64748b; background: #fff; white-space: nowrap; }
.tab-pill.active, .tab-pill:hover { background: #0f172a; color: #fff; border-color: #0f172a; }
.crm-table th { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; border-bottom: 1.5px solid #e8ecf0; padding: .5rem .75rem; background: #f8fafc; white-space: nowrap; }
.crm-table td { font-size: .8rem; vertical-align: middle; padding: .55rem .75rem; border-bottom: 1px solid #f1f5f9; }
.crm-table tr:last-child td { border-bottom: 0; }
.crm-status-form select { font-size: .68rem; font-weight: 700; padding: .18rem .45rem; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; cursor: pointer; }
.aging-row td:first-child { border-left: 3px solid #f97316; }
.aging-row { background: #fffbeb; }
.aging-badge { display:inline-flex;align-items:center;gap:.25rem;font-size:.62rem;font-weight:800;background:#fee2e2;color:#dc2626;padding:.1rem .4rem;border-radius:4px;margin-top:.2rem; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-black mb-0" style="font-size:1.05rem;">Order Management</h5>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:2px;">Semua order storefront</div>
        </div>
        <a href="{{ route('admin.crm.dashboard') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.75rem;">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2" style="font-size:.8rem;border-radius:12px;" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Aging alert --}}
    @if($agingCount > 0)
    <div class="alert d-flex align-items-center gap-2 py-2 mb-3"
         style="background:#fff7ed;border:1.5px solid #f97316;border-radius:12px;font-size:.8rem;">
        <i class="bi bi-exclamation-triangle-fill" style="color:#f97316;font-size:1rem;flex-shrink:0;"></i>
        <span>
            <strong>{{ $agingCount }} order pending</strong> sudah lebih dari 24 jam belum diproses — ditandai dengan border oranye.
        </span>
    </div>
    @endif

    {{-- Status tabs --}}
    <div class="d-flex gap-2 flex-wrap mb-3">
        @php
            $allStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'done', 'cancelled'];
            $labels = [
                '' => 'Semua',
                'pending' => 'Pending',
                'confirmed' => 'Confirmed',
                'processing' => 'Processing',
                'shipped' => 'Shipped',
                'done' => 'Done',
                'cancelled' => 'Cancelled',
            ];
        @endphp
        @foreach($labels as $val => $label)
        <a href="{{ route('admin.crm.orders', array_merge(request()->query(), ['status' => $val, 'page' => 1])) }}"
           class="tab-pill {{ $status === $val ? 'active' : '' }}">
            {{ $label }}
            @if($val && isset($statusCounts[$val]))
            <span class="ms-1" style="font-size:.65rem;opacity:.8;">({{ $statusCounts[$val] }})</span>
            @endif
        </a>
        @endforeach
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('admin.crm.orders') }}" class="mb-3 d-flex gap-2">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="text" name="search" value="{{ $search }}" placeholder="Cari nomor order, nama, HP…"
               class="form-control form-control-sm" style="max-width:280px;border-radius:10px;font-size:.8rem;">
        <button type="submit" class="btn btn-sm btn-dark" style="border-radius:10px;font-size:.8rem;">Cari</button>
        @if($search)
        <a href="{{ route('admin.crm.orders', ['status' => $status]) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.8rem;">Reset</a>
        @endif
    </form>

    {{-- Table --}}
    <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:16px;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table class="table mb-0 crm-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Tanggal</th>
                        <th>Customer</th>
                        <th>Kota</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Pembayaran</th>
                        <th>Status</th>
                        <th>WA</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    @php
                        $agingHours = (int) \Carbon\Carbon::parse($order->created_at)->diffInHours(now());
                        $isAging    = $order->status === 'pending' && $agingHours >= 24;
                    @endphp
                    <tr class="{{ $isAging ? 'aging-row' : '' }}">
                        <td>
                            <span class="fw-bold" style="font-family:monospace;font-size:.78rem;">{{ $order->order_number }}</span>
                            @if($isAging)
                            <div class="aging-badge"><i class="bi bi-exclamation-triangle-fill"></i>{{ $agingHours >= 48 ? round($agingHours/24, 0).'h' : $agingHours.'j' }} menunggu</div>
                            @endif
                        </td>
                        <td style="white-space:nowrap;color:#64748b;">
                            {{ \Carbon\Carbon::parse($order->created_at)->format('d M y') }}<br>
                            <span style="font-size:.7rem;">{{ \Carbon\Carbon::parse($order->created_at)->format('H:i') }}</span>
                        </td>
                        <td>
                            <div class="fw-bold" style="font-size:.8rem;">{{ $order->customer_name }}</div>
                            <div style="font-size:.72rem;color:#64748b;">{{ $order->customer_phone }}</div>
                        </td>
                        <td style="font-size:.78rem;color:#334155;text-transform:capitalize;">{{ strtolower($order->city) }}</td>
                        <td>
                            @foreach(array_slice($order->items ?? [], 0, 2) as $item)
                            <div style="font-size:.72rem;color:#334155;">{{ $item['name'] ?? '' }} <span style="color:#94a3b8;">×{{ $item['qty'] ?? 1 }}</span></div>
                            @endforeach
                            @if(count($order->items ?? []) > 2)
                            <div style="font-size:.68rem;color:#94a3b8;">+{{ count($order->items) - 2 }} lainnya</div>
                            @endif
                        </td>
                        <td style="white-space:nowrap;font-weight:800;">Rp{{ number_format($order->total_amount) }}</td>
                        <td style="font-size:.75rem;color:#334155;">
                            {{ $order->payment_method }}<br>
                            @if($order->payment_proof_url)
                            <a href="{{ $order->payment_proof_url }}" target="_blank" style="font-size:.68rem;color:#3b82f6;">Lihat Bukti</a>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.crm.orders.status', $order) }}" class="crm-status-form d-flex align-items-center gap-1">
                                @csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()">
                                    @foreach(['pending','confirmed','processing','shipped','done','cancelled'] as $s)
                                    <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <span class="status-badge status-{{ $order->status }} mt-1 d-inline-block">{{ $order->status }}</span>
                        </td>
                        <td style="text-align:center;">
                            @if($order->wa_sent_at)
                            <i class="bi bi-check2-circle" style="color:#22c55e;font-size:1rem;" title="WA dikirim {{ \Carbon\Carbon::parse($order->wa_sent_at)->format('d M H:i') }}"></i>
                            @else
                            <i class="bi bi-circle" style="color:#cbd5e1;font-size:.9rem;" title="Belum WA"></i>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:2rem;color:#94a3b8;font-size:.85rem;">
                            Tidak ada order
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($orders->hasPages())
    <div class="mt-3 d-flex justify-content-center">
        {{ $orders->links() }}
    </div>
    @endif

</div>
@endsection
