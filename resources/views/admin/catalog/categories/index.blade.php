@extends('layouts.app')

@section('title', 'Kategori Produk Website')

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 gap-3 flex-wrap">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size:12px;">
                    <li class="breadcrumb-item"><a href="{{ route('admin.catalog.products.index') }}">Produk Website</a></li>
                    <li class="breadcrumb-item active">Kategori</li>
                </ol>
            </nav>
            <h4 class="fw-bold mb-0">Kategori Produk</h4>
            <div class="text-muted" style="font-size:13px;">{{ $categories->count() }} kategori terdaftar</div>
        </div>
        <a href="{{ route('admin.catalog.categories.create') }}" class="btn btn-dark btn-sm d-flex align-items-center gap-2">
            <i class="bi bi-plus-lg"></i> Tambah Kategori
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($categories->isEmpty())
        <div class="text-center py-5">
            <i class="bi bi-tag" style="font-size:48px;color:#ccc;"></i>
            <div class="text-muted mt-3 mb-3">Belum ada kategori. Buat kategori pertama untuk mengorganisasi produk.</div>
            <a href="{{ route('admin.catalog.categories.create') }}" class="btn btn-dark btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Buat Kategori
            </a>
        </div>
    @else
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Nama Kategori</th>
                            <th>Slug</th>
                            <th class="text-center">Produk</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Urutan</th>
                            <th style="width:120px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $cat)
                        <tr>
                            <td class="text-muted" style="font-size:12px;">{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold">{{ $cat->name }}</div>
                                @if($cat->description)
                                    <div class="text-muted" style="font-size:12px;">{{ Str::limit($cat->description, 60) }}</div>
                                @endif
                            </td>
                            <td>
                                <code style="font-size:11px;background:#f5f5f5;padding:2px 6px;border-radius:4px;">{{ $cat->slug }}</code>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary rounded-pill">{{ $cat->products_count }}</span>
                            </td>
                            <td class="text-center">
                                @if($cat->is_active)
                                    <span class="badge" style="background:#dcfce7;color:#15803d;font-size:11px;">Aktif</span>
                                @else
                                    <span class="badge" style="background:#f3f4f6;color:#6b7280;font-size:11px;">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-center text-muted" style="font-size:13px;">{{ $cat->sort_order }}</td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <a href="{{ route('admin.catalog.categories.edit', $cat) }}"
                                       class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.catalog.categories.destroy', $cat) }}"
                                          onsubmit="return confirm('Hapus kategori {{ $cat->name }}? Produk di dalamnya akan jadi Tanpa Kategori.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-muted mt-3" style="font-size:12px;">
            <i class="bi bi-info-circle me-1"></i>
            Urutan tampil dipengaruhi kolom <strong>Urutan</strong> (dari kecil ke besar), lalu nama.
        </div>
    @endif

</div>
@endsection
