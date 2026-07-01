@extends('layouts.app')
@section('title', 'Produk Website')

@push('head')
<style>
.cat-pill { border:1.5px solid #e2e8f0;border-radius:999px;padding:.25rem .8rem;font-size:.72rem;font-weight:700;text-decoration:none;color:#64748b;background:#fff;white-space:nowrap; }
.cat-pill.active,.cat-pill:hover { background:#0f172a;color:#fff;border-color:#0f172a; }
.prod-card { background:#fff;border:1.5px solid #e8ecf0;border-radius:14px;overflow:hidden;transition:box-shadow .15s; }
.prod-card:hover { box-shadow:0 4px 18px rgba(0,0,0,.08); }
.prod-thumb { width:100%;height:140px;object-fit:cover;background:#f1f5f9; }
.prod-thumb-placeholder { width:100%;height:140px;background:linear-gradient(135deg,#f1f5f9 0%,#e2e8f0 100%);display:flex;align-items:center;justify-content:center; }
.type-badge { font-size:.6rem;font-weight:800;padding:.15rem .45rem;border-radius:5px; }
.pub-badge { font-size:.62rem;font-weight:800;padding:.15rem .45rem;border-radius:5px; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Flash --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible py-2 mb-3" style="font-size:.8rem;border-radius:10px;">
        {{ session('success') }} <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-black mb-0" style="font-size:1.05rem;">Produk Website</h5>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:2px;">Kelola produk yang tampil di storefront</div>
        </div>
        <a href="{{ route('admin.catalog.products.create') }}"
           class="btn btn-dark btn-sm fw-bold" style="border-radius:10px;font-size:.78rem;">
            <i class="bi bi-plus-lg me-1"></i> Tambah Produk
        </a>
    </div>

    {{-- Filter pills --}}
    <div class="d-flex gap-2 flex-wrap mb-1">
        <a href="{{ route('admin.catalog.products.index') }}"
           class="cat-pill {{ !$type && !$unpublished && !$categorySlug ? 'active' : '' }}">
            Semua <span style="opacity:.6;">{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('admin.catalog.products.index', ['type' => 'regular']) }}"
           class="cat-pill {{ $type === 'regular' && !$categorySlug ? 'active' : '' }}">
            Regular <span style="opacity:.6;">{{ $counts['regular'] }}</span>
        </a>
        <a href="{{ route('admin.catalog.products.index', ['type' => 'jumbo']) }}"
           class="cat-pill {{ $type === 'jumbo' && !$categorySlug ? 'active' : '' }}">
            Jumbo <span style="opacity:.6;">{{ $counts['jumbo'] }}</span>
        </a>
        @if($counts['unpublished'] > 0)
        <a href="{{ route('admin.catalog.products.index', ['unpublished' => 1]) }}"
           class="cat-pill {{ $unpublished ? 'active' : '' }}">
            Draft <span style="opacity:.6;">{{ $counts['unpublished'] }}</span>
        </a>
        @endif
    </div>

    {{-- Category pills --}}
    @if($categories->isNotEmpty())
    <div class="d-flex gap-2 flex-wrap mb-1" style="padding-left:2px;">
        @foreach($categories as $cat)
        <a href="{{ route('admin.catalog.products.index', ['kategori' => $cat->slug]) }}"
           class="cat-pill" style="{{ $categorySlug === $cat->slug ? 'background:#4f46e5;color:#fff;border-color:#4f46e5;' : 'font-size:.65rem;' }}">
            <i class="bi bi-tag me-1" style="font-size:.6rem;"></i>{{ $cat->name }}
            <span style="opacity:.6;">{{ $cat->products_count ?? $cat->products()->count() }}</span>
        </a>
        @endforeach
    </div>
    @endif

    {{-- Audience pills --}}
    @php
    $audienceColors = [
        'pria'     => ['bg'=>'#dbeafe','color'=>'#1d4ed8'],
        'wanita'   => ['bg'=>'#fce7f3','color'=>'#be185d'],
        'anak'     => ['bg'=>'#fef3c7','color'=>'#d97706'],
        'olahraga' => ['bg'=>'#dcfce7','color'=>'#15803d'],
        'unisex'   => ['bg'=>'#f1f5f9','color'=>'#6b7280'],
    ];
    @endphp
    <div class="d-flex gap-2 flex-wrap mb-4" style="padding-left:2px;">
        @foreach($audienceOptions as $val => $label)
        @php $ac = $audienceColors[$val]; @endphp
        <a href="{{ route('admin.catalog.products.index', ['audience' => $val]) }}"
           class="cat-pill"
           style="font-size:.65rem;{{ $audienceFilter === $val ? "background:{$ac['color']};color:#fff;border-color:{$ac['color']};" : '' }}">
            <i class="bi bi-person me-1" style="font-size:.6rem;"></i>{{ $label }}
        </a>
        @endforeach
    </div>

    @if($products->isEmpty())
    <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:14px;padding:3rem;text-align:center;">
        <i class="bi bi-box-seam" style="font-size:2.5rem;color:#cbd5e1;"></i>
        <div style="font-size:.9rem;color:#94a3b8;margin-top:1rem;">Belum ada produk</div>
        <a href="{{ route('admin.catalog.products.create') }}" class="btn btn-dark btn-sm mt-3" style="border-radius:10px;">
            <i class="bi bi-plus-lg me-1"></i> Tambah Produk Pertama
        </a>
    </div>
    @else

    {{-- Product grid --}}
    <div class="row g-3">
        @foreach($products as $p)
        <div class="col-6 col-md-4 col-lg-3">
            <div class="prod-card h-100 d-flex flex-column">

                {{-- Thumbnail --}}
                @php
                    $defaultVariant = $p->variants->first();
                    $thumb = $defaultVariant?->getImageSrc() ?: $p->getImageSrc();
                @endphp
                @if($thumb)
                <img src="{{ $thumb }}" alt="{{ $p->name }}" class="prod-thumb">
                @else
                <div class="prod-thumb-placeholder">
                    <i class="bi bi-image" style="font-size:2rem;color:#cbd5e1;"></i>
                </div>
                @endif

                {{-- Body --}}
                <div class="p-3 flex-grow-1 d-flex flex-column">
                    <div class="d-flex gap-1 mb-2 flex-wrap">
                        <span class="type-badge"
                              style="background:{{ $p->type_badge_color }}20;color:{{ $p->type_badge_color }};">
                            {{ $p->type_label }}
                        </span>
                        @if($p->label)
                        <span class="type-badge" style="background:#fef3c7;color:#92400e;">{{ $p->label }}</span>
                        @endif
                        <span class="pub-badge ms-auto"
                              style="background:{{ $p->is_published ? '#dcfce7' : '#f1f5f9' }};color:{{ $p->is_published ? '#15803d' : '#94a3b8' }};">
                            {{ $p->is_published ? 'Published' : 'Draft' }}
                        </span>
                    </div>

                    <div class="fw-bold" style="font-size:.85rem;color:#0f172a;line-height:1.3;">{{ $p->name }}</div>
                    <div style="font-size:.7rem;color:#94a3b8;margin-top:2px;">{{ $p->slug }}</div>

                    @if($p->category || $p->audience)
                    <div class="mt-1 d-flex gap-1 flex-wrap">
                        @if($p->category)
                        <a href="{{ route('admin.catalog.products.index', ['kategori' => $p->category->slug]) }}"
                           style="font-size:.65rem;font-weight:700;color:#4f46e5;background:#ede9fe;padding:.15rem .45rem;border-radius:5px;text-decoration:none;">
                            <i class="bi bi-tag" style="font-size:.58rem;"></i> {{ $p->category->name }}
                        </a>
                        @endif
                        @if($p->audience)
                        @php $ac = $audienceColors[$p->audience] ?? ['bg'=>'#f1f5f9','color'=>'#6b7280']; @endphp
                        <a href="{{ route('admin.catalog.products.index', ['audience' => $p->audience]) }}"
                           style="font-size:.65rem;font-weight:700;color:{{ $ac['color'] }};background:{{ $ac['bg'] }};padding:.15rem .45rem;border-radius:5px;text-decoration:none;">
                            <i class="bi bi-person" style="font-size:.58rem;"></i> {{ $p->audience_label }}
                        </a>
                        @endif
                    </div>
                    @endif

                    <div class="mt-2" style="font-size:.82rem;font-weight:800;color:#0f172a;">
                        Rp{{ number_format($p->base_price) }}
                    </div>

                    <div class="d-flex gap-2 mt-2" style="font-size:.7rem;color:#64748b;">
                        <span><i class="bi bi-palette me-1"></i>{{ $p->variants_count }} warna</span>
                        <span><i class="bi bi-rulers me-1"></i>{{ $p->sizes_count }} ukuran</span>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex gap-2 mt-auto pt-3">
                        <a href="{{ route('admin.catalog.products.edit', $p) }}"
                           class="btn btn-sm btn-dark flex-grow-1" style="border-radius:8px;font-size:.72rem;">
                            <i class="bi bi-pencil-square me-1"></i> Edit
                        </a>
                        <form method="POST" action="{{ route('admin.catalog.products.toggle-publish', $p) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm"
                                    style="border-radius:8px;font-size:.72rem;background:{{ $p->is_published ? '#fef3c7' : '#dcfce7' }};color:{{ $p->is_published ? '#92400e' : '#15803d' }};border:none;white-space:nowrap;">
                                <i class="bi bi-{{ $p->is_published ? 'eye-slash' : 'eye' }}"></i>
                                {{ $p->is_published ? 'Hide' : 'Publish' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
