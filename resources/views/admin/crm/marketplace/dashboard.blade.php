@extends('layouts.app')
@section('title', 'CRM Marketplace')
@push('head') @include('admin.crm.marketplace._styles') @endpush

@section('content')
<div class="container-fluid py-3">
    @include('admin.crm.marketplace._nav')

    @if(session('success'))
        <div class="alert alert-success py-2" style="border-radius:12px;font-size:.8rem;">{{ session('success') }}</div>
    @endif
    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger py-2" style="border-radius:12px;font-size:.8rem;">{{ $errors->first() }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h5 class="fw-bold mb-1">CRM Marketplace</h5>
            <div class="text-secondary" style="font-size:.78rem;">Customer dan order dari marketplace, terpisah dari CRM Storefront.</div>
        </div>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <select name="store_id" class="form-select form-select-sm" style="min-width:190px;border-radius:10px;" onchange="this.form.submit()">
                <option value="">Semua toko</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" @selected($storeId === $store->id)>{{ $store->name }} · {{ $store->channel?->name ?? '-' }}</option>
                @endforeach
            </select>
            <div class="btn-group btn-group-sm">
                @foreach([7=>'7H',30=>'30H',90=>'90H',365=>'1T'] as $value => $label)
                    <a class="btn {{ $days === $value ? 'btn-dark' : 'btn-outline-secondary' }}" href="{{ route('admin.crm.marketplace.dashboard', ['days'=>$value,'store_id'=>$storeId]) }}">{{ $label }}</a>
                @endforeach
            </div>
        </form>
    </div>

    <div class="row g-2 mb-3">
        @foreach([
            ['Order', number_format($totalOrders), 'periode terpilih', 'bi-bag-check', '#2563eb'],
            ['Revenue', 'Rp'.number_format($totalRevenue, 0, ',', '.'), 'tidak termasuk cancel', 'bi-cash-stack', '#16a34a'],
            ['Customer', number_format($customerCount), 'username unik pada order periode ini', 'bi-people', '#7c3aed'],
            ['Repeat Buyer', number_format($repeatCustomers), 'username dengan lebih dari satu order', 'bi-arrow-repeat', '#ea580c'],
        ] as [$label,$value,$sub,$icon,$color])
        <div class="col-6 col-xl-3"><div class="mpcrm-card h-100">
            <div class="d-flex justify-content-between"><div class="mpcrm-label">{{ $label }}</div><i class="bi {{ $icon }}" style="color:{{ $color }}"></i></div>
            <div class="mpcrm-value">{{ $value }}</div><div class="mpcrm-sub">{{ $sub }}</div>
        </div></div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-8"><div class="mpcrm-card h-100">
            <div class="d-flex justify-content-between align-items-center"><div><div class="fw-bold">Order & revenue harian</div><div class="mpcrm-sub">{{ $days }} hari terakhir</div></div><span class="mpcrm-pill" style="background:#fff7ed;color:#c2410c;">{{ $pendingOrders }} perlu dipantau</span></div>
            @php
                $maxRevenue = max(1, (float) $dailyOrders->max('revenue'));
                $maxOrders = max(1, (int) $dailyOrders->max('orders'));
            @endphp
            @if($dailyOrders->isEmpty())
                <div class="text-secondary py-5 text-center" style="font-size:.8rem;">Belum ada order marketplace. Import file untuk mulai mengisi CRM.</div>
            @else
                <div class="mpcrm-chart-shell">
                    <div class="mpcrm-chart-axis">
                        <span>Rp{{ number_format($maxRevenue,0,',','.') }}</span>
                        <span>Rp{{ number_format($maxRevenue / 2,0,',','.') }}</span>
                        <span>Rp0</span>
                    </div>
                    <div class="mpcrm-chart-row">
                    @foreach($dailyOrders as $day)
                        @php
                            $revenue = (float) $day->revenue;
                            $barHeight = $revenue > 0
                                ? (($revenue / $maxRevenue) * 100)
                                : (((int) $day->orders / $maxOrders) * 100);
                        @endphp
                        <div class="mpcrm-chart-column" title="{{ $day->tooltip ?? $day->label ?? $day->date }} · {{ $day->orders }} order · Rp{{ number_format($revenue,0,',','.') }}">
                            <div class="mpcrm-chart-value">{{ number_format($day->orders) }}</div>
                            <div class="mpcrm-bar" style="height:{{ max(8, $barHeight) }}%;"></div>
                            <div class="mpcrm-bar-label">{{ $day->label ?? \Carbon\Carbon::parse($day->date)->format('d/m') }}</div>
                        </div>
                    @endforeach
                    </div>
                </div>
            @endif
        </div></div>
        <div class="col-xl-4"><div class="mpcrm-card h-100">
            <div class="fw-bold mb-2">Status order</div>
            @foreach(['new'=>'New','packed'=>'Packed','shipped'=>'Shipped','completed'=>'Completed','cancelled'=>'Cancelled'] as $key=>$label)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span style="font-size:.8rem;">{{ $label }}</span><span class="fw-bold">{{ number_format($statusCounts[$key] ?? 0) }}</span></div>
            @endforeach
        </div></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7"><div class="mpcrm-card">
            <div class="d-flex justify-content-between align-items-center mb-2"><div class="fw-bold">Produk terlaris</div><a class="mpcrm-link" style="font-size:.75rem;" href="{{ route('admin.crm.marketplace.orders') }}">Lihat order</a></div>
            @php
                $sortUrl = fn ($column) => request()->fullUrlWithQuery(['sort' => $column, 'direction' => $productSort === $column && $productDirection === 'asc' ? 'desc' : 'asc', 'page' => 1]);
                $sortIcon = fn ($column) => $productSort === $column ? ($productDirection === 'asc' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up';
            @endphp
            <div class="table-responsive"><table class="table mb-0 mpcrm-table"><thead><tr><th><a class="mpcrm-sort {{ $productSort === 'name' ? 'active' : '' }}" href="{{ $sortUrl('name') }}">Produk <i class="bi {{ $sortIcon('name') }}"></i></a></th><th><a class="mpcrm-sort {{ $productSort === 'qty' ? 'active' : '' }}" href="{{ $sortUrl('qty') }}">Qty <i class="bi {{ $sortIcon('qty') }}"></i></a></th><th><a class="mpcrm-sort {{ $productSort === 'revenue' ? 'active' : '' }}" href="{{ $sortUrl('revenue') }}">Revenue <i class="bi {{ $sortIcon('revenue') }}"></i></a></th></tr></thead><tbody>
                @forelse($topProducts as $product)<tr><td>{{ \Illuminate\Support\Str::limit($product->name, 70) }}</td><td>{{ number_format($product->qty) }}</td><td>Rp{{ number_format($product->revenue,0,',','.') }}</td></tr>@empty<tr><td colspan="3" class="text-center text-secondary">Belum ada item.</td></tr>@endforelse
            </tbody></table></div><div class="pt-3">{{ $topProducts->links() }}</div>
        </div></div>
        <div class="col-xl-5"><div class="mpcrm-card">
            <div class="fw-bold mb-2">Import terakhir</div>
            @forelse($recentImports as $batch)
                <div class="d-flex justify-content-between gap-2 py-2 border-bottom"><div><div style="font-size:.78rem;font-weight:750;">{{ \Illuminate\Support\Str::limit($batch->source_file, 34) }}</div><div class="mpcrm-sub">{{ $batch->store?->name ?? '-' }} · {{ $batch->created_at?->format('d M Y H:i') }}</div></div><div class="text-end"><div class="fw-bold" style="font-size:.8rem;">{{ $batch->shipments_parsed }} order</div><div class="mpcrm-sub">{{ $batch->inserted_shipments }} baru · {{ $batch->updated_shipments }} update</div></div></div>
            @empty <div class="text-secondary" style="font-size:.8rem;">Belum ada import.</div> @endforelse
        </div></div>
    </div>
</div>
@endsection
