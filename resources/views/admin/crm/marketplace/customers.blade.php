@extends('layouts.app')
@section('title', 'Customer Marketplace')
@push('head') @include('admin.crm.marketplace._styles') @endpush

@section('content')
<div class="container-fluid py-3">
    @include('admin.crm.marketplace._nav')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div><h5 class="fw-bold mb-1">Customer Marketplace</h5><div class="text-secondary" style="font-size:.78rem;">Customer berasal dari order marketplace; tidak mengambil data akun storefront.</div></div></div>
    <div class="mpcrm-toolbar mb-3"><form method="GET" class="row g-2 align-items-end"><div class="col-md-5"><label class="form-label mb-1" style="font-size:.7rem;font-weight:800;">Cari customer</label><input name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Nama atau nomor telepon…"></div><div class="col-md-4"><label class="form-label mb-1" style="font-size:.7rem;font-weight:800;">Toko</label><select name="store_id" class="form-select form-select-sm"><option value="">Semua toko</option>@foreach($stores as $store)<option value="{{ $store->id }}" @selected($storeId === $store->id)>{{ $store->name }}</option>@endforeach</select></div><div class="col-md-3 d-flex gap-2"><button class="btn btn-sm btn-dark flex-fill">Filter</button><a href="{{ route('admin.crm.marketplace.customers') }}" class="btn btn-sm btn-outline-secondary">Reset</a></div></form></div>
    @php
        $sortUrl = fn ($column) => request()->fullUrlWithQuery(['sort' => $column, 'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc', 'page' => 1]);
        $sortIcon = fn ($column) => $sort === $column ? ($direction === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up';
    @endphp
    <div class="mpcrm-card p-0 overflow-hidden"><div class="table-responsive"><table class="table mb-0 mpcrm-table"><thead><tr>
        <th><a class="mpcrm-sort {{ $sort === 'customer' ? 'active' : '' }}" href="{{ $sortUrl('customer') }}">Customer <i class="bi {{ $sortIcon('customer') }}"></i></a></th>
        <th><a class="mpcrm-sort {{ $sort === 'location' ? 'active' : '' }}" href="{{ $sortUrl('location') }}">Kota / Provinsi <i class="bi {{ $sortIcon('location') }}"></i></a></th>
        <th><a class="mpcrm-sort {{ $sort === 'orders' ? 'active' : '' }}" href="{{ $sortUrl('orders') }}">Order <i class="bi {{ $sortIcon('orders') }}"></i></a></th>
        <th><a class="mpcrm-sort {{ $sort === 'total' ? 'active' : '' }}" href="{{ $sortUrl('total') }}">Total Belanja <i class="bi {{ $sortIcon('total') }}"></i></a></th>
        <th><a class="mpcrm-sort {{ $sort === 'last_order' ? 'active' : '' }}" href="{{ $sortUrl('last_order') }}">Order Terakhir <i class="bi {{ $sortIcon('last_order') }}"></i></a></th>
    </tr></thead><tbody>
        @forelse($customers as $customer)<tr><td><div class="fw-semibold">{{ $customer->name }}</div><div class="mpcrm-sub">{{ $customer->phone ?: 'Telepon tidak ada' }}</div></td><td>{{ $customer->city ?: '-' }}<div class="mpcrm-sub">{{ $customer->province ?: '-' }}</div></td><td><span class="mpcrm-pill" style="background:#eff6ff;color:#1d4ed8;">{{ number_format($customer->marketplace_order_count) }} order</span></td><td class="fw-bold">Rp{{ number_format($customer->marketplace_total_spent,0,',','.') }}</td><td>{{ $customer->last_marketplace_order_at ? \Carbon\Carbon::parse($customer->last_marketplace_order_at)->format('d M Y H:i') : '-' }}</td></tr>@empty<tr><td colspan="5" class="text-center text-secondary py-5">Belum ada customer marketplace.</td></tr>@endforelse
    </tbody></table></div><div class="p-3">{{ $customers->links() }}</div></div>
</div>
@endsection
