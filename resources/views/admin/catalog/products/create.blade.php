@extends('layouts.app')
@section('title', 'Tambah Produk Website')

@push('head')
<style>
.form-label-sm { font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin-bottom:.3rem; }
.form-hint { font-size:.7rem;color:#94a3b8;margin-top:.2rem; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('admin.catalog.products.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.75rem;">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h5 class="fw-black mb-0" style="font-size:1.05rem;">Tambah Produk Website</h5>
            <div style="font-size:.75rem;color:#94a3b8;">Info dasar — variant & ukuran bisa ditambah setelah simpan</div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:14px;padding:1.5rem;">
                <form method="POST" action="{{ route('admin.catalog.products.store') }}" enctype="multipart/form-data">
                    @csrf

                    {{-- Name --}}
                    <div class="mb-3">
                        <label class="form-label-sm">Nama Produk <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="form-control @error('name') is-invalid @enderror"
                               style="border-radius:10px;font-size:.85rem;" placeholder="mis. GF Track Jacket Regular">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Slug --}}
                    <div class="mb-3">
                        <label class="form-label-sm">Slug (URL)</label>
                        <input type="text" name="slug" value="{{ old('slug') }}" id="slugInput"
                               class="form-control @error('slug') is-invalid @enderror"
                               style="border-radius:10px;font-size:.85rem;font-family:monospace;" placeholder="auto-generate dari nama">
                        <div class="form-hint">Kosongkan = otomatis. Contoh: <code>gf-track-jacket-regular</code></div>
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Type + Price row --}}
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label-sm">Tipe Produk <span class="text-danger">*</span></label>
                            <select name="product_type" required
                                    class="form-select @error('product_type') is-invalid @enderror"
                                    style="border-radius:10px;font-size:.85rem;">
                                <option value="regular" {{ old('product_type') === 'regular' ? 'selected' : '' }}>Regular (S–XXL)</option>
                                <option value="jumbo"   {{ old('product_type') === 'jumbo'   ? 'selected' : '' }}>Jumbo (3XL–6XL)</option>
                            </select>
                            @error('product_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label-sm">Harga Dasar (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="base_price" value="{{ old('base_price', 0) }}" min="0" required
                                   class="form-control @error('base_price') is-invalid @enderror"
                                   style="border-radius:10px;font-size:.85rem;">
                            <div class="form-hint">Bisa dioverride per variant/ukuran</div>
                            @error('base_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Category + Audience row --}}
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label-sm">Kategori</label>
                            <select name="category_id"
                                    class="form-select @error('category_id') is-invalid @enderror"
                                    style="border-radius:10px;font-size:.85rem;">
                                <option value="">— Tanpa Kategori —</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label-sm">Target Audience</label>
                            <select name="audience"
                                    class="form-select @error('audience') is-invalid @enderror"
                                    style="border-radius:10px;font-size:.85rem;">
                                <option value="">— Semua —</option>
                                @foreach($audienceOptions as $val => $label)
                                <option value="{{ $val }}" {{ old('audience') === $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                            @error('audience')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Label + Sort --}}
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label-sm">Badge Label</label>
                            <input type="text" name="label" value="{{ old('label') }}"
                                   class="form-control" style="border-radius:10px;font-size:.85rem;"
                                   placeholder="Best Seller / New / Sale">
                        </div>
                        <div class="col-6">
                            <label class="form-label-sm">Urutan Tampil</label>
                            <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                                   class="form-control" style="border-radius:10px;font-size:.85rem;">
                            <div class="form-hint">Angka kecil = tampil lebih dulu</div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="mb-3">
                        <label class="form-label-sm">Deskripsi</label>
                        <textarea name="description" rows="4"
                                  class="form-control" style="border-radius:10px;font-size:.85rem;resize:vertical;">{{ old('description') }}</textarea>
                    </div>

                    {{-- Thumbnail --}}
                    <div class="mb-4">
                        <label class="form-label-sm">Foto Thumbnail (opsional)</label>
                        <input type="file" name="image" accept="image/*"
                               class="form-control mb-2" style="border-radius:10px;font-size:.82rem;">
                        <div style="font-size:.7rem;color:#94a3b8;">atau pakai URL:</div>
                        <input type="text" name="image_url" value="{{ old('image_url') }}"
                               class="form-control mt-1" style="border-radius:10px;font-size:.8rem;font-family:monospace;"
                               placeholder="https://...">
                        <div class="form-hint">Foto per variant (warna) bisa diupload setelah produk dibuat.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-dark fw-bold px-4" style="border-radius:10px;">
                            <i class="bi bi-check-lg me-1"></i> Simpan & Lanjut Tambah Variant
                        </button>
                        <a href="{{ route('admin.catalog.products.index') }}" class="btn btn-outline-secondary" style="border-radius:10px;">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tips panel --}}
        <div class="col-lg-5">
            <div style="background:#f8fafc;border:1.5px solid #e8ecf0;border-radius:14px;padding:1.25rem;">
                <div style="font-size:.78rem;font-weight:800;color:#0f172a;margin-bottom:.75rem;">
                    <i class="bi bi-lightbulb-fill text-warning me-1"></i> Tips
                </div>
                <div style="font-size:.78rem;color:#64748b;line-height:1.7;">
                    <p><strong>Regular vs Jumbo</strong> = produk terpisah.<br>
                    Buat 2 produk: "GF Jacket Regular" dan "GF Jacket Jumbo" masing-masing dengan harga & ukuran yang berbeda.</p>
                    <p><strong>Variant warna</strong> (Navy, Black, Olive) bisa ditambah setelah simpan — masing-masing variant punya foto sendiri.</p>
                    <p><strong>Ukuran</strong> (S, M, L, XL, dst) juga ditambah setelah produk dibuat. Harga bisa beda per ukuran.</p>
                    <p><strong>Status Draft</strong> = produk tersimpan tapi tidak tampil di website sampai kamu publish.</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
// Auto-generate slug dari nama
const nameInput = document.querySelector('input[name="name"]');
const slugInput = document.getElementById('slugInput');
let slugEdited = slugInput.value.length > 0;

slugInput.addEventListener('input', () => { slugEdited = true; });

nameInput.addEventListener('input', () => {
    if (slugEdited) return;
    slugInput.value = nameInput.value
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-');
});
</script>
@endpush
