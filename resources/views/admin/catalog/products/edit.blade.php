@extends('layouts.app')
@section('title', 'Edit: ' . $product->name)

@push('head')
<style>
.form-label-sm { font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin-bottom:.3rem; }
.form-hint { font-size:.7rem;color:#94a3b8;margin-top:.2rem; }
.tab-nav { display:flex;gap:.5rem;border-bottom:2px solid #e8ecf0;margin-bottom:1.5rem; }
.tab-btn { background:none;border:none;padding:.5rem 1rem;font-size:.8rem;font-weight:700;color:#94a3b8;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px; }
.tab-btn.active { color:#0f172a;border-bottom-color:#0f172a; }
.tab-pane { display:none; }
.tab-pane.active { display:block; }
/* Variant cards */
.var-card { border:1.5px solid #e8ecf0;border-radius:12px;overflow:hidden;background:#fff;transition:box-shadow .15s; }
.var-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); }
.var-img { width:100%;height:120px;object-fit:cover;background:#f1f5f9; }
.var-img-placeholder { width:100%;height:120px;display:flex;align-items:center;justify-content:center;background:#f8fafc; }
.var-swatch { width:20px;height:20px;border-radius:50%;border:2px solid rgba(0,0,0,.12);display:inline-block;vertical-align:middle;margin-right:4px; }
.add-var-card { border:2px dashed #e2e8f0;border-radius:12px;height:100%;min-height:220px;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;background:#fafafa;transition:all .15s; }
.add-var-card:hover { border-color:#0f172a;background:#f1f5f9; }
/* Size table */
.size-table th { font-size:.67rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;padding:.5rem .75rem;background:#f8fafc;white-space:nowrap;border-bottom:1.5px solid #e8ecf0; }
.size-table td { font-size:.82rem;padding:.5rem .75rem;border-bottom:1px solid #f1f5f9;vertical-align:middle; }
.size-table tr:last-child td { border-bottom:0; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Flash --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible py-2 mb-3" style="font-size:.8rem;border-radius:10px;">
        <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible py-2 mb-3" style="font-size:.8rem;border-radius:10px;">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $errors->first() }}
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.catalog.products.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-size:.75rem;">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h5 class="fw-black mb-0" style="font-size:1.05rem;">{{ $product->name }}</h5>
                <div class="d-flex gap-2 align-items-center mt-1">
                    <span style="font-size:.68rem;font-family:monospace;color:#94a3b8;">{{ $product->slug }}</span>
                    <span style="font-size:.62rem;font-weight:800;padding:.12rem .4rem;border-radius:5px;
                                 background:{{ $product->type_badge_color }}20;color:{{ $product->type_badge_color }};">
                        {{ $product->type_label }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Publish toggle --}}
        <form method="POST" action="{{ route('admin.catalog.products.toggle-publish', $product) }}" class="d-flex align-items-center gap-2">
            @csrf
            <span style="font-size:.75rem;color:{{ $product->is_published ? '#15803d' : '#94a3b8' }};font-weight:700;">
                <i class="bi bi-circle-fill me-1" style="font-size:.55rem;"></i>
                {{ $product->is_published ? 'Published' : 'Draft' }}
            </span>
            <button type="submit" class="btn btn-sm"
                    style="border-radius:10px;font-size:.75rem;font-weight:700;
                           background:{{ $product->is_published ? '#fef3c7' : '#dcfce7' }};
                           color:{{ $product->is_published ? '#92400e' : '#15803d' }};border:none;">
                <i class="bi bi-{{ $product->is_published ? 'eye-slash' : 'eye' }} me-1"></i>
                {{ $product->is_published ? 'Sembunyikan' : 'Publikasikan' }}
            </button>
        </form>
    </div>

    {{-- Tabs --}}
    <div class="tab-nav">
        <button class="tab-btn active" data-tab="info">
            <i class="bi bi-info-circle me-1"></i>Info Dasar
        </button>
        <button class="tab-btn" data-tab="variants">
            <i class="bi bi-palette me-1"></i>Variant Warna
            <span style="background:#e2e8f0;border-radius:999px;padding:.05rem .45rem;font-size:.65rem;margin-left:.25rem;">{{ $product->variants->count() }}</span>
        </button>
        <button class="tab-btn" data-tab="sizes">
            <i class="bi bi-rulers me-1"></i>Ukuran
            <span style="background:#e2e8f0;border-radius:999px;padding:.05rem .45rem;font-size:.65rem;margin-left:.25rem;">{{ $product->sizes->count() }}</span>
        </button>
    </div>

    {{-- ══ TAB 1: INFO DASAR ════════════════════════════════════════════════════ --}}
    <div class="tab-pane active" id="tab-info">
        <div class="row g-3">
            <div class="col-lg-7">
                <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:14px;padding:1.5rem;">
                    <form method="POST" action="{{ route('admin.catalog.products.update', $product) }}" enctype="multipart/form-data">
                        @csrf @method('PUT')

                        <div class="mb-3">
                            <label class="form-label-sm">Nama Produk</label>
                            <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                                   class="form-control" style="border-radius:10px;font-size:.85rem;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label-sm">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" required
                                   class="form-control" style="border-radius:10px;font-size:.85rem;font-family:monospace;">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label-sm">Tipe Produk</label>
                                <select name="product_type" class="form-select" style="border-radius:10px;font-size:.85rem;">
                                    <option value="regular" {{ $product->product_type === 'regular' ? 'selected' : '' }}>Regular (S–XXL)</option>
                                    <option value="jumbo"   {{ $product->product_type === 'jumbo'   ? 'selected' : '' }}>Jumbo (3XL–6XL)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label-sm">Harga Dasar (Rp)</label>
                                <input type="number" name="base_price" value="{{ old('base_price', $product->base_price) }}" min="0"
                                       class="form-control" style="border-radius:10px;font-size:.85rem;">
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
                                    <option value="{{ $cat->id }}"
                                        {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
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
                                    <option value="{{ $val }}"
                                        {{ old('audience', $product->audience) === $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('audience')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label-sm">Badge Label</label>
                                <input type="text" name="label" value="{{ old('label', $product->label) }}"
                                       class="form-control" style="border-radius:10px;font-size:.85rem;"
                                       placeholder="Best Seller / New / Sale">
                            </div>
                            <div class="col-6">
                                <label class="form-label-sm">Urutan Tampil</label>
                                <input type="number" name="sort_order" value="{{ old('sort_order', $product->sort_order) }}" min="0"
                                       class="form-control" style="border-radius:10px;font-size:.85rem;">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-sm">Deskripsi</label>
                            <textarea name="description" rows="4" class="form-control"
                                      style="border-radius:10px;font-size:.85rem;resize:vertical;">{{ old('description', $product->description) }}</textarea>
                        </div>

                        {{-- Current thumbnail --}}
                        @if($product->getImageSrc())
                        <div class="mb-3">
                            <label class="form-label-sm">Foto Thumbnail Saat Ini</label>
                            <div class="d-flex align-items-start gap-3">
                                <img src="{{ $product->getImageSrc() }}" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1.5px solid #e8ecf0;">
                                <div>
                                    <div class="form-hint">Upload foto baru untuk mengganti</div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="mb-4">
                            <label class="form-label-sm">Ganti Foto Thumbnail</label>
                            <input type="file" name="image" accept="image/*"
                                   class="form-control mb-2" style="border-radius:10px;font-size:.82rem;">
                            <div style="font-size:.7rem;color:#94a3b8;">atau URL:</div>
                            <input type="text" name="image_url" value="{{ old('image_url') }}"
                                   class="form-control mt-1" style="border-radius:10px;font-size:.8rem;font-family:monospace;"
                                   placeholder="https://...">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-dark fw-bold" style="border-radius:10px;font-size:.82rem;">
                                <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                {{-- Danger zone --}}
                <div style="background:#fff;border:1.5px solid #fecdd3;border-radius:14px;padding:1.25rem;">
                    <div style="font-size:.78rem;font-weight:800;color:#be123c;margin-bottom:.75rem;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Danger Zone
                    </div>
                    <div style="font-size:.78rem;color:#64748b;margin-bottom:1rem;">
                        Menghapus produk akan menghapus semua variant dan ukurannya secara permanen. Tindakan ini tidak bisa dibatalkan.
                    </div>
                    <form method="POST" action="{{ route('admin.catalog.products.destroy', $product) }}"
                          onsubmit="return confirm('Hapus produk "{{ addslashes($product->name) }}"? Tindakan ini TIDAK bisa dibatalkan.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm"
                                style="background:#fff;border:1.5px solid #fecdd3;color:#be123c;border-radius:8px;font-size:.75rem;font-weight:700;">
                            <i class="bi bi-trash3 me-1"></i> Hapus Produk Ini
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ TAB 2: VARIANT WARNA ════════════════════════════════════════════════ --}}
    <div class="tab-pane" id="tab-variants">
        <div class="row g-3">

            {{-- Existing variants --}}
            @foreach($product->variants as $v)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="var-card h-100">
                    {{-- Image --}}
                    @if($v->getImageSrc())
                    <img src="{{ $v->getImageSrc() }}" alt="{{ $v->color_name }}" class="var-img">
                    @else
                    <div class="var-img-placeholder">
                        <i class="bi bi-image" style="font-size:1.8rem;color:#cbd5e1;"></i>
                    </div>
                    @endif

                    <div class="p-2">
                        <div class="d-flex align-items-center gap-1 mb-1">
                            @if($v->hex_color)
                            <span class="var-swatch" style="background:{{ $v->hex_color }};"></span>
                            @endif
                            <span style="font-size:.82rem;font-weight:700;color:#0f172a;">{{ $v->color_name }}</span>
                            @if($v->is_default)
                            <span style="font-size:.6rem;font-weight:800;background:#dbeafe;color:#1d4ed8;padding:.1rem .3rem;border-radius:4px;margin-left:auto;">DEFAULT</span>
                            @endif
                        </div>

                        @if($v->price_override)
                        <div style="font-size:.72rem;color:#16a34a;font-weight:700;">
                            Rp{{ number_format($v->price_override) }}
                        </div>
                        @else
                        <div style="font-size:.72rem;color:#94a3b8;">Harga: base</div>
                        @endif

                        @if(!$v->is_active)
                        <span style="font-size:.62rem;background:#f1f5f9;color:#94a3b8;padding:.1rem .35rem;border-radius:4px;font-weight:700;">NONAKTIF</span>
                        @endif

                        <div class="d-flex gap-1 mt-2">
                            <button class="btn btn-sm btn-outline-secondary flex-grow-1"
                                    style="font-size:.68rem;border-radius:7px;padding:.2rem 0;"
                                    onclick="openEditVariant({{ json_encode([
                                        'id'             => $v->id,
                                        'color_name'     => $v->color_name,
                                        'hex_color'      => $v->hex_color ?? '#000000',
                                        'image_src'      => $v->getImageSrc(),
                                        'price_override' => $v->price_override,
                                        'is_default'     => $v->is_default,
                                        'is_active'      => $v->is_active,
                                    ]) }})">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <form method="POST" action="{{ route('admin.catalog.products.variants.destroy', [$product, $v]) }}"
                                  onsubmit="return confirm('Hapus variant {{ addslashes($v->color_name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm"
                                        style="font-size:.68rem;border-radius:7px;padding:.2rem .45rem;background:#fff;border:1.5px solid #fecdd3;color:#be123c;">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Add new variant card --}}
            <div class="col-6 col-md-4 col-lg-3">
                <div class="add-var-card" onclick="document.getElementById('addVariantModal').style.display='flex'">
                    <i class="bi bi-plus-circle" style="font-size:2rem;color:#94a3b8;"></i>
                    <div style="font-size:.78rem;color:#94a3b8;margin-top:.5rem;font-weight:700;">Tambah Warna</div>
                </div>
            </div>

        </div>

        <div style="margin-top:1rem;font-size:.75rem;color:#94a3b8;">
            <i class="bi bi-info-circle me-1"></i>
            Variant pertama otomatis jadi default. Klik "Edit" untuk ganti foto, warna, atau harga khusus per variant.
        </div>
    </div>

    {{-- ══ TAB 3: UKURAN ═══════════════════════════════════════════════════════ --}}
    <div class="tab-pane" id="tab-sizes">
        <div style="background:#fff;border:1.5px solid #e8ecf0;border-radius:14px;overflow:hidden;">
            <table class="table mb-0 size-table">
                <thead>
                    <tr>
                        <th>Ukuran</th>
                        <th>Harga Override</th>
                        <th>Status</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($product->sizes as $s)
                    <tr>
                        <td>
                            <span style="font-size:.9rem;font-weight:800;color:#0f172a;">{{ $s->size_label }}</span>
                        </td>
                        <td>
                            @if($s->price_override)
                            <span style="color:#16a34a;font-weight:700;">Rp{{ number_format($s->price_override) }}</span>
                            @else
                            <span style="color:#94a3b8;">Rp{{ number_format($product->base_price) }} <span style="font-size:.7rem;">(base)</span></span>
                            @endif
                        </td>
                        <td>
                            <span style="font-size:.7rem;font-weight:800;padding:.12rem .4rem;border-radius:5px;
                                         background:{{ $s->is_active ? '#dcfce7' : '#f1f5f9' }};
                                         color:{{ $s->is_active ? '#15803d' : '#94a3b8' }};">
                                {{ $s->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <div class="d-flex gap-1 justify-content-end">
                                <button class="btn btn-sm btn-outline-secondary"
                                        style="font-size:.7rem;border-radius:7px;padding:.2rem .55rem;"
                                        onclick="openEditSize({{ json_encode([
                                            'id'             => $s->id,
                                            'size_label'     => $s->size_label,
                                            'price_override' => $s->price_override,
                                            'is_active'      => $s->is_active,
                                        ]) }})">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <form method="POST" action="{{ route('admin.catalog.products.sizes.destroy', [$product, $s]) }}"
                                      onsubmit="return confirm('Hapus ukuran {{ $s->size_label }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm"
                                            style="font-size:.7rem;border-radius:7px;padding:.2rem .45rem;background:#fff;border:1.5px solid #fecdd3;color:#be123c;">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:2rem;color:#94a3b8;font-size:.82rem;">
                            Belum ada ukuran. Tambahkan di bawah.
                        </td>
                    </tr>
                    @endforelse

                    {{-- Inline add row --}}
                    <tr style="background:#f8fafc;">
                        <form method="POST" action="{{ route('admin.catalog.products.sizes.store', $product) }}" class="d-contents">
                            @csrf
                            <td>
                                <input type="text" name="size_label" placeholder="mis. XL atau 3XL"
                                       class="form-control form-control-sm" required
                                       style="border-radius:8px;font-size:.82rem;max-width:110px;font-weight:700;">
                            </td>
                            <td>
                                <input type="number" name="price_override" min="0" placeholder="Kosong = base price"
                                       class="form-control form-control-sm"
                                       style="border-radius:8px;font-size:.8rem;max-width:160px;">
                            </td>
                            <td colspan="2">
                                <button type="submit" class="btn btn-sm btn-dark"
                                        style="border-radius:8px;font-size:.75rem;font-weight:700;">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Ukuran
                                </button>
                            </td>
                        </form>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Quick add common sizes --}}
        <div class="mt-3" style="font-size:.75rem;color:#64748b;">
            <span style="font-weight:700;">Quick add:</span>
            @php
                $quickSizes = $product->product_type === 'jumbo'
                    ? ['3XL','4XL','5XL','6XL','7XL']
                    : ['S','M','L','XL','XXL'];
                $existingSizes = $product->sizes->pluck('size_label')->toArray();
            @endphp
            @foreach($quickSizes as $qs)
            @if(!in_array($qs, $existingSizes))
            <form method="POST" action="{{ route('admin.catalog.products.sizes.store', $product) }}" class="d-inline">
                @csrf
                <input type="hidden" name="size_label" value="{{ $qs }}">
                <button type="submit" class="btn btn-sm"
                        style="border-radius:999px;font-size:.7rem;font-weight:700;padding:.2rem .65rem;border:1.5px solid #e2e8f0;background:#fff;color:#334155;margin-bottom:.3rem;">
                    + {{ $qs }}
                </button>
            </form>
            @endif
            @endforeach
        </div>
    </div>

</div>

{{-- ══ MODAL: ADD VARIANT ══════════════════════════════════════════════════════ --}}
<div id="addVariantModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1055;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;padding:1.5rem;margin:1rem;">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-black mb-0">Tambah Variant Warna</h6>
            <button type="button" onclick="document.getElementById('addVariantModal').style.display='none'"
                    style="background:none;border:none;font-size:1.25rem;color:#94a3b8;cursor:pointer;">×</button>
        </div>
        <form method="POST" action="{{ route('admin.catalog.products.variants.store', $product) }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="form-label-sm">Nama Warna <span class="text-danger">*</span></label>
                <input type="text" name="color_name" required class="form-control"
                       style="border-radius:10px;" placeholder="mis. Navy, Black, Olive">
            </div>
            <div class="mb-3">
                <label class="form-label-sm">Kode Warna (Hex)</label>
                <div class="d-flex gap-2 align-items-center">
                    <input type="color" name="hex_color" value="#000000" id="addHexPicker"
                           style="width:44px;height:36px;border:1.5px solid #e2e8f0;border-radius:8px;padding:2px;cursor:pointer;">
                    <input type="text" id="addHexText" value="#000000"
                           class="form-control" style="border-radius:10px;font-family:monospace;font-size:.85rem;"
                           placeholder="#000000" maxlength="20">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label-sm">Foto Variant</label>
                <input type="file" name="image" accept="image/*" id="addImageFile"
                       class="form-control mb-2" style="border-radius:10px;font-size:.82rem;"
                       onchange="previewImg(this, 'addImgPreview')">
                <div id="addImgPreview" style="display:none;margin-bottom:.5rem;">
                    <img src="" alt="" style="width:100%;height:140px;object-fit:cover;border-radius:10px;border:1.5px solid #e8ecf0;">
                </div>
                <div style="font-size:.7rem;color:#94a3b8;">atau URL gambar:</div>
                <input type="text" name="image_url" class="form-control mt-1"
                       style="border-radius:10px;font-size:.8rem;font-family:monospace;" placeholder="https://...">
            </div>
            <div class="mb-3">
                <label class="form-label-sm">Harga Khusus Warna Ini (opsional)</label>
                <input type="number" name="price_override" min="0" class="form-control"
                       style="border-radius:10px;" placeholder="Kosong = pakai harga dasar">
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="addIsDefault">
                <label class="form-check-label" for="addIsDefault" style="font-size:.8rem;">
                    Jadikan variant default (foto utama di listing)
                </label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-dark fw-bold flex-grow-1" style="border-radius:10px;">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Variant
                </button>
                <button type="button" onclick="document.getElementById('addVariantModal').style.display='none'"
                        class="btn btn-outline-secondary" style="border-radius:10px;">Batal</button>
            </div>
        </form>
    </div>
</div>

{{-- ══ MODAL: EDIT VARIANT ═════════════════════════════════════════════════════ --}}
<div id="editVariantModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1055;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;padding:1.5rem;margin:1rem;">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-black mb-0">Edit Variant Warna</h6>
            <button type="button" onclick="document.getElementById('editVariantModal').style.display='none'"
                    style="background:none;border:none;font-size:1.25rem;color:#94a3b8;cursor:pointer;">×</button>
        </div>
        <form id="editVariantForm" method="POST" enctype="multipart/form-data">
            @csrf @method('PATCH')
            <div class="mb-3">
                <label class="form-label-sm">Nama Warna</label>
                <input type="text" name="color_name" id="editColorName" required class="form-control" style="border-radius:10px;">
            </div>
            <div class="mb-3">
                <label class="form-label-sm">Kode Warna (Hex)</label>
                <div class="d-flex gap-2 align-items-center">
                    <input type="color" name="hex_color" id="editHexPicker"
                           style="width:44px;height:36px;border:1.5px solid #e2e8f0;border-radius:8px;padding:2px;cursor:pointer;">
                    <input type="text" id="editHexText" class="form-control"
                           style="border-radius:10px;font-family:monospace;font-size:.85rem;" maxlength="20">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label-sm">Foto Variant (upload untuk ganti)</label>
                <div id="editCurrentImg" style="margin-bottom:.5rem;display:none;">
                    <img id="editCurrentImgEl" src="" alt="" style="width:100%;height:120px;object-fit:cover;border-radius:10px;border:1.5px solid #e8ecf0;">
                    <div style="font-size:.7rem;color:#94a3b8;margin-top:3px;">Foto saat ini</div>
                </div>
                <input type="file" name="image" accept="image/*" class="form-control mb-2"
                       style="border-radius:10px;font-size:.82rem;"
                       onchange="previewImg(this, 'editImgPreview')">
                <div id="editImgPreview" style="display:none;margin-bottom:.5rem;">
                    <img src="" alt="" style="width:100%;height:120px;object-fit:cover;border-radius:10px;border:1.5px solid #e8ecf0;">
                </div>
                <div style="font-size:.7rem;color:#94a3b8;">atau URL baru (kosongkan untuk tetap pakai foto lama):</div>
                <input type="text" name="image_url" id="editImageUrl" class="form-control mt-1"
                       style="border-radius:10px;font-size:.8rem;font-family:monospace;" placeholder="https://...">
            </div>
            <div class="mb-3">
                <label class="form-label-sm">Harga Khusus Warna Ini (opsional)</label>
                <input type="number" name="price_override" id="editPriceOverride" min="0" class="form-control"
                       style="border-radius:10px;" placeholder="Kosong = pakai harga dasar">
            </div>
            <div class="d-flex gap-3 mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="editIsDefault">
                    <label class="form-check-label" for="editIsDefault" style="font-size:.8rem;">Default</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editIsActive" checked>
                    <label class="form-check-label" for="editIsActive" style="font-size:.8rem;">Aktif</label>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-dark fw-bold flex-grow-1" style="border-radius:10px;">
                    <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                </button>
                <button type="button" onclick="document.getElementById('editVariantModal').style.display='none'"
                        class="btn btn-outline-secondary" style="border-radius:10px;">Batal</button>
            </div>
        </form>
    </div>
</div>

{{-- ══ MODAL: EDIT SIZE ════════════════════════════════════════════════════════ --}}
<div id="editSizeModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1055;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:100%;max-width:400px;padding:1.5rem;margin:1rem;">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-black mb-0">Edit Ukuran</h6>
            <button type="button" onclick="document.getElementById('editSizeModal').style.display='none'"
                    style="background:none;border:none;font-size:1.25rem;color:#94a3b8;cursor:pointer;">×</button>
        </div>
        <form id="editSizeForm" method="POST">
            @csrf @method('PATCH')
            <div class="mb-3">
                <label class="form-label-sm">Label Ukuran</label>
                <input type="text" name="size_label" id="editSizeLabel" required class="form-control"
                       style="border-radius:10px;font-weight:700;font-size:.9rem;">
            </div>
            <div class="mb-3">
                <label class="form-label-sm">Harga Khusus Ukuran Ini (opsional)</label>
                <input type="number" name="price_override" id="editSizePrice" min="0" class="form-control"
                       style="border-radius:10px;" placeholder="Kosong = pakai harga dasar">
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editSizeActive" checked>
                <label class="form-check-label" for="editSizeActive" style="font-size:.8rem;">Aktif</label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-dark fw-bold flex-grow-1" style="border-radius:10px;">
                    Simpan
                </button>
                <button type="button" onclick="document.getElementById('editSizeModal').style.display='none'"
                        class="btn btn-outline-secondary" style="border-radius:10px;">Batal</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── Tab switching ─────────────────────────────────────────────────────────────
const tabBtns  = document.querySelectorAll('.tab-btn');
const tabPanes = document.querySelectorAll('.tab-pane');

function switchTab(name) {
    tabBtns.forEach(b => b.classList.toggle('active', b.dataset.tab === name));
    tabPanes.forEach(p => p.classList.toggle('active', p.id === 'tab-' + name));
    location.hash = name;
}

tabBtns.forEach(b => b.addEventListener('click', () => switchTab(b.dataset.tab)));

// Restore tab from hash
const hash = location.hash.replace('#', '');
if (['info', 'variants', 'sizes'].includes(hash)) switchTab(hash);

// Redirect to correct tab after flash (error = info, new variant/size = their tab)
@if(session('success'))
    const msg = '{{ session('success') }}';
    if (msg.includes('ariant')) switchTab('variants');
    else if (msg.includes('kuran')) switchTab('sizes');
@endif

// ── Color picker sync ─────────────────────────────────────────────────────────
function syncColor(picker, text) {
    picker.addEventListener('input', () => text.value = picker.value);
    text.addEventListener('input', () => {
        if (/^#[0-9A-Fa-f]{6}$/.test(text.value)) picker.value = text.value;
    });
}
syncColor(document.getElementById('addHexPicker'), document.getElementById('addHexText'));
syncColor(document.getElementById('editHexPicker'), document.getElementById('editHexText'));

// sync hidden hex input name on add form
document.getElementById('addHexPicker').addEventListener('input', function() {
    document.getElementById('addHexText').value = this.value;
});

// ── Image preview ─────────────────────────────────────────────────────────────
function previewImg(input, wrapperId) {
    const wrapper = document.getElementById(wrapperId);
    const img = wrapper.querySelector('img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; wrapper.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

// ── Edit Variant modal ────────────────────────────────────────────────────────
function openEditVariant(data) {
    const baseUrl = '{{ route('admin.catalog.products.variants.update', [$product, '__ID__']) }}';
    document.getElementById('editVariantForm').action = baseUrl.replace('__ID__', data.id);
    document.getElementById('editColorName').value    = data.color_name;
    document.getElementById('editHexPicker').value    = data.hex_color || '#000000';
    document.getElementById('editHexText').value      = data.hex_color || '#000000';
    document.getElementById('editPriceOverride').value = data.price_override || '';
    document.getElementById('editIsDefault').checked  = !!data.is_default;
    document.getElementById('editIsActive').checked   = !!data.is_active;
    document.getElementById('editImageUrl').value     = '';
    // show current image
    const curImg = document.getElementById('editCurrentImg');
    if (data.image_src) {
        document.getElementById('editCurrentImgEl').src = data.image_src;
        curImg.style.display = 'block';
    } else {
        curImg.style.display = 'none';
    }
    document.getElementById('editImgPreview').style.display = 'none';
    document.getElementById('editVariantModal').style.display = 'flex';
}

// ── Edit Size modal ───────────────────────────────────────────────────────────
function openEditSize(data) {
    const baseUrl = '{{ route('admin.catalog.products.sizes.update', [$product, '__ID__']) }}';
    document.getElementById('editSizeForm').action    = baseUrl.replace('__ID__', data.id);
    document.getElementById('editSizeLabel').value    = data.size_label;
    document.getElementById('editSizePrice').value    = data.price_override || '';
    document.getElementById('editSizeActive').checked = !!data.is_active;
    document.getElementById('editSizeModal').style.display = 'flex';
}

// Close modals on backdrop click
['addVariantModal', 'editVariantModal', 'editSizeModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>
@endpush
