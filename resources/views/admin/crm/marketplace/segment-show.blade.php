@extends('layouts.app')
@section('title', $definition['label'].' Marketplace')
@push('head') @include('admin.crm.marketplace._styles') @endpush

@section('content')
<div class="container-fluid py-3">
    @include('admin.crm.marketplace._nav')
    <div class="d-flex align-items-center gap-3 mb-3"><a href="{{ route('admin.crm.marketplace.segments', $storeId ? ['store_id'=>$storeId] : []) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;"><i class="bi bi-arrow-left"></i></a><div class="flex-grow-1"><div class="d-flex align-items-center gap-2"><div style="width:38px;height:38px;border-radius:11px;background:{{ $definition['bg'] }};color:{{ $definition['color'] }};display:flex;align-items:center;justify-content:center;"><i class="bi {{ $definition['icon'] }}"></i></div><div><h5 class="fw-bold mb-0" style="color:{{ $definition['color'] }};">{{ $definition['label'] }}</h5><div class="text-secondary" style="font-size:.75rem;">{{ $definition['desc'] }}</div>@if($analysisDate)<div style="font-size:.68rem;color:#64748b;margin-top:3px;">Basis recency: {{ \Carbon\Carbon::parse($analysisDate)->format('d M Y') }}</div>@endif</div></div></div><span class="mpcrm-pill" style="background:{{ $definition['bg'] }};color:{{ $definition['color'] }};">{{ $customers->count() }} customer</span></div>
    <div class="mpcrm-card mb-3" style="background:{{ $definition['bg'] }};border-color:{{ $definition['color'] }}33;font-size:.8rem;"><i class="bi bi-lightbulb me-1" style="color:{{ $definition['color'] }};"></i><b>Saran action:</b> {{ $definition['action'] }}</div>
    @php
        $sortUrl = fn ($column) => request()->fullUrlWithQuery(['sort' => $column, 'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc', 'page' => 1]);
        $sortIcon = fn ($column) => $sort === $column ? ($direction === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up';
    @endphp
    <div class="mpcrm-card p-0 overflow-hidden"><div class="table-responsive"><table class="table mb-0 mpcrm-table"><thead><tr>
        <th><a class="mpcrm-sort {{ $sort === 'customer' ? 'active' : '' }}" href="{{ $sortUrl('customer') }}">Customer <i class="bi {{ $sortIcon('customer') }}"></i></a></th>
        <th><a class="mpcrm-sort {{ $sort === 'orders' ? 'active' : '' }}" href="{{ $sortUrl('orders') }}">Order <i class="bi {{ $sortIcon('orders') }}"></i></a></th>
        <th><a class="mpcrm-sort {{ $sort === 'total' ? 'active' : '' }}" href="{{ $sortUrl('total') }}">Total Belanja <i class="bi {{ $sortIcon('total') }}"></i></a></th>
        <th><a class="mpcrm-sort {{ $sort === 'last_order' ? 'active' : '' }}" href="{{ $sortUrl('last_order') }}">Terakhir Order <i class="bi {{ $sortIcon('last_order') }}"></i></a></th>
        <th>Aksi</th>
    </tr></thead><tbody>@forelse($customers as $customer)<tr><td><div class="fw-semibold">{{ $customer->name ?: 'Buyer Marketplace' }}</div><div class="mpcrm-sub">{{ $customer->buyer_username ? '@'.$customer->buyer_username.' · ' : '' }}{{ $customer->phone ?: '-' }}</div></td><td>{{ $customer->marketplace_order_count }}×</td><td class="fw-bold">Rp{{ number_format($customer->marketplace_total_spent,0,',','.') }}</td><td>{{ $customer->last_marketplace_order_at ? \Carbon\Carbon::parse($customer->last_marketplace_order_at)->diffForHumans() : '-' }}<div class="mpcrm-sub">{{ \App\Http\Controllers\Admin\MarketplaceCrmController::formatElapsedDays((int) $customer->days_since_last_order) }}</div></td><td>@if($customer->wa_phone)<a class="btn btn-sm" target="_blank" href="https://wa.me/{{ $customer->wa_phone }}?text={{ urlencode('Halo '.$customer->name.', '.$definition['action']) }}" style="background:#25d366;color:#fff;border-radius:8px;font-size:.68rem;font-weight:700;"><i class="bi bi-whatsapp me-1"></i>WA</a>@endif</td></tr>@empty<tr><td colspan="5" class="text-center text-secondary py-5">Tidak ada customer pada segment ini.</td></tr>@endforelse</tbody></table></div><div class="p-3">{{ $customers->links() }}</div></div>
</div>
@endsection
