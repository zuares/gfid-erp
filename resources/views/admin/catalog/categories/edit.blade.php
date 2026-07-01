@extends('layouts.app')

@section('title', 'Edit Kategori — ' . $category->name)

@section('content')
<div class="container-fluid py-4" style="max-width:720px;">

    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb" style="font-size:12px;">
            <li class="breadcrumb-item"><a href="{{ route('admin.catalog.products.index') }}">Produk Website</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.catalog.categories.index') }}">Kategori</a></li>
            <li class="breadcrumb-item active">{{ $category->name }}</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <h4 class="fw-bold mb-0">{{ $category->name }}</h4>
        @if($category->is_active)
            <span class="badge" style="background:#dcfce7;color:#15803d;">Aktif</span>
        @else
            <span class="badge" style="background:#f3f4f6;color:#6b7280;">Nonaktif</span>
        @endif
        <span class="text-muted" style="font-size:13px;">{{ $category->products->count() }} produk</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Form Edit --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.catalog.categories.update', $category) }}">
                @csrf @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $category->name) }}"
                           class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $category->slug) }}"
                           class="form-control @error('slug') is-invalid @enderror">
                    <div class="form-text">URL: <code>/products?kategori={{ $category->slug }}</code></div>
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Deskripsi</label>
                    <textarea name="description" rows="3"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-semibold">Urutan Tampil</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}"
                               class="form-control @error('sort_order') is-invalid @enderror" min="0">
                        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch pb-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                   value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_active">Kategori Aktif</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-dark">Simpan Perubahan</button>
                    <a href="{{ route('admin.catalog.categories.index') }}" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar produk dalam kategori ini --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
            <div class="fw-bold">Produk dalam Kategori Ini</div>
            <a href="{{ route('admin.catalog.products.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-box-seam me-1"></i>Kelola Produk
            </a>
        </div>
        <div class="card-body p-0">
            @if($category->products->isEmpty())
                <div class="text-center py-4 text-muted" style="font-size:13px;">
                    Belum ada produk di kategori ini. Tambahkan lewat halaman <a href="{{ route('admin.catalog.products.index') }}">Produk Website</a>.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        @php
                        $audColors = ['pria'=>['bg'=>'#dbeafe','color'=>'#1d4ed8'],'wanita'=>['bg'=>'#fce7f3','color'=>'#be185d'],'anak'=>['bg'=>'#fef3c7','color'=>'#d97706'],'olahraga'=>['bg'=>'#dcfce7','color'=>'#15803d'],'unisex'=>['bg'=>'#f1f5f9','color'=>'#6b7280']];
                        @endphp
                        <thead class="table-light">
                            <tr>
                                <th>Nama Produk</th>
                                <th class="text-center">Tipe</th>
                                <th class="text-center">Audience</th>
                                <th class="text-center">Status</th>
                                <th style="width:60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($category->products as $p)
                            <tr>
                                <td>
                                    <div class="fw-semibold" style="font-size:13px;">{{ $p->name }}</div>
                                    <div class="text-muted" style="font-size:11px;">{{ $p->slug }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill"
                                          style="background:{{ $p->type_badge_color }};font-size:10px;">
                                        {{ $p->type_label }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($p->audience)
                                    @php $ac = $audColors[$p->audience] ?? ['bg'=>'#f1f5f9','color'=>'#6b7280']; @endphp
                                    <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;background:{{ $ac['bg'] }};color:{{ $ac['color'] }};">
                                        {{ $p->audience_label }}
                                    </span>
                                    @else
                                    <span class="text-muted" style="font-size:11px;">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($p->is_published)
                                        <span class="badge" style="background:#dcfce7;color:#15803d;font-size:11px;">Published</span>
                                    @else
                                        <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:11px;">Draft</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.catalog.products.edit', $p) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Danger Zone --}}
    <div class="card border-danger shadow-sm">
        <div class="card-body p-4">
            <h6 class="text-danger fw-bold mb-1">Hapus Kategori</h6>
            <p class="text-muted mb-3" style="font-size:13px;">
                Menghapus kategori ini akan melepas semua produk yang ada (produk tidak dihapus, hanya jadi "Tanpa Kategori").
            </p>
            <form method="POST" action="{{ route('admin.catalog.categories.destroy', $category) }}"
                  onsubmit="return confirm('Yakin hapus kategori {{ $category->name }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i> Hapus Kategori
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
