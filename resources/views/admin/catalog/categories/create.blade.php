@extends('layouts.app')

@section('title', 'Buat Kategori Baru')

@section('content')
<div class="container-fluid py-4" style="max-width:720px;">

    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb" style="font-size:12px;">
            <li class="breadcrumb-item"><a href="{{ route('admin.catalog.products.index') }}">Produk Website</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.catalog.categories.index') }}">Kategori</a></li>
            <li class="breadcrumb-item active">Buat Baru</li>
        </ol>
    </nav>

    <h4 class="fw-bold mb-4">Buat Kategori Baru</h4>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.catalog.categories.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name-input" value="{{ old('name') }}"
                           class="form-control @error('name') is-invalid @enderror"
                           placeholder="cth: Jaket, Hoodie, Celana, Kaos" required autofocus>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Slug</label>
                    <input type="text" name="slug" id="slug-input" value="{{ old('slug') }}"
                           class="form-control @error('slug') is-invalid @enderror"
                           placeholder="auto-generate dari nama">
                    <div class="form-text">Diisi otomatis dari nama. Gunakan huruf kecil dan tanda hubung saja.</div>
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Deskripsi</label>
                    <textarea name="description" rows="3"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="Opsional — deskripsi singkat kategori ini">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Urutan Tampil</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                           class="form-control @error('sort_order') is-invalid @enderror"
                           min="0" style="max-width:120px;">
                    <div class="form-text">Angka kecil tampil lebih dulu.</div>
                    @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-dark">Simpan Kategori</button>
                    <a href="{{ route('admin.catalog.categories.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
(function() {
    var nameEl = document.getElementById('name-input');
    var slugEl = document.getElementById('slug-input');
    var slugEdited = slugEl.value.trim() !== '';

    function toSlug(str) {
        return str.toLowerCase()
            .replace(/[àáâãäå]/g,'a').replace(/[èéêë]/g,'e')
            .replace(/[ìíîï]/g,'i').replace(/[òóôõö]/g,'o')
            .replace(/[ùúûü]/g,'u').replace(/[ñ]/g,'n')
            .replace(/[^a-z0-9\s-]/g,'')
            .replace(/[\s]+/g,'-')
            .replace(/-+/g,'-')
            .replace(/^-|-$/g,'');
    }

    nameEl.addEventListener('input', function() {
        if (!slugEdited) slugEl.value = toSlug(this.value);
    });

    slugEl.addEventListener('input', function() {
        slugEdited = this.value.trim() !== '';
    });
})();
</script>
@endsection
