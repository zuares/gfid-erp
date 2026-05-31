{{-- resources/views/master/item_categories/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Kategori Item')

@push('head')
    <style>
        .page-wrap {
            max-width: 820px;
            margin-inline: auto;
            padding: .9rem .8rem 3.2rem;
        }

        body[data-theme="light"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 26%,
                    #f9fafb 60%);
        }

        body[data-theme="dark"] .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.16) 0,
                    rgba(15, 23, 42, 1) 55%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            box-shadow:
                0 10px 24px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.03);
        }

        .page-header-title {
            font-size: 1.06rem;
            font-weight: 600;
            letter-spacing: -.02em;
        }

        .page-header-subtitle {
            font-size: .8rem;
        }

        .btn-add-item {
            border-radius: 999px;
            padding-inline: 1rem;
            font-size: .8rem;
        }

        .table-gfid {
            font-size: .8rem;
            margin-bottom: 0;
        }

        .table-gfid thead th {
            background: color-mix(in srgb, var(--card) 78%, var(--bg) 22%);
            border-bottom: 1px solid rgba(148, 163, 184, 0.45) !important;
            padding-top: .5rem;
            padding-bottom: .5rem;
            font-weight: 600;
            font-size: .75rem;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .table-gfid tbody tr {
            border-color: rgba(148, 163, 184, 0.25);
        }

        .table-gfid tbody tr:nth-child(even) {
            background: color-mix(in srgb, var(--card) 96%, rgba(148, 163, 184, 0.22) 4%);
        }

        .table-gfid td {
            padding-top: .42rem;
            padding-bottom: .42rem;
            vertical-align: middle;
        }

        .badge-pill-soft {
            border-radius: 999px;
            font-size: .7rem;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="page-header-title mb-1">Kategori Item</h5>
                <div class="page-header-subtitle text-muted">
                    Kelola kategori item. Kategori yang masih dipakai item tidak bisa dihapus.
                </div>
            </div>
            <div class="ms-3 d-flex gap-2">
                <a href="{{ route('master.items.index') }}" class="btn btn-outline-secondary btn-sm btn-add-item">
                    ← Master Item
                </a>
                <button type="button" class="btn btn-primary btn-sm btn-add-item text-white" data-bs-toggle="modal"
                    data-bs-target="#categoryModal" id="btnAddCategory">
                    <span class="me-1">＋</span><span>Tambah Kategori</span>
                </button>
            </div>
        </div>

        {{-- FLASH --}}
        @if (session('success'))
            <div class="alert alert-success py-2 px-3 mb-3" style="font-size:.82rem;">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.82rem;">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.82rem;">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- FILTERS --}}
        <div class="card card-main mb-3">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('master.item_categories.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label mb-1" style="font-size:.74rem;">Cari</label>
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Kode / nama kategori"
                            value="{{ request('q') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1" style="font-size:.74rem;">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">- Semua -</option>
                            <option value="active" @selected(request('status') === 'active')>Aktif</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm w-100">Filter</button>
                        @if (request()->hasAny(['q', 'status']))
                            <a href="{{ route('master.item_categories.index') }}"
                                class="btn btn-outline-light border btn-sm">Reset</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="card card-main">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle table-gfid">
                        <thead>
                            <tr class="text-muted">
                                <th style="width: 6%" class="text-center">No.</th>
                                <th style="width: 18%">Kode</th>
                                <th>Nama</th>
                                <th style="width: 16%" class="text-center">Jumlah Item</th>
                                <th style="width: 12%" class="text-center">Status</th>
                                <th style="width: 16%" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $cat)
                                <tr>
                                    <td class="text-center text-muted">{{ $categories->firstItem() + $loop->index }}</td>
                                    <td class="fw-semibold">{{ $cat->code }}</td>
                                    <td>{{ $cat->name }}</td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis px-3">
                                            {{ $cat->items_count }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($cat->active)
                                            <span class="badge badge-pill-soft bg-success-subtle text-success">Aktif</span>
                                        @else
                                            <span class="badge badge-pill-soft bg-danger-subtle text-danger">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-edit-category"
                                                data-id="{{ $cat->id }}" data-code="{{ $cat->code }}"
                                                data-name="{{ $cat->name }}" data-active="{{ $cat->active ? 1 : 0 }}"
                                                data-bs-toggle="modal" data-bs-target="#categoryModal">
                                                Edit
                                            </button>
                                            @if ($cat->items_count == 0)
                                                <form method="POST"
                                                    action="{{ route('master.item_categories.destroy', $cat) }}"
                                                    onsubmit="return confirm('Hapus kategori &quot;{{ $cat->name }}&quot;?');"
                                                    class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger">Hapus</button>
                                                </form>
                                            @else
                                                <button type="button" class="btn btn-outline-secondary" disabled
                                                    title="Masih dipakai {{ $cat->items_count }} item">Hapus</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada kategori.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-3 py-2 d-flex justify-content-between align-items-center"
                    style="border-top:1px solid rgba(148,163,184,.35);">
                    <div style="font-size:.74rem;" class="text-muted">
                        @if ($categories->total() > 0)
                            Menampilkan <strong>{{ $categories->firstItem() }}–{{ $categories->lastItem() }}</strong>
                            dari <strong>{{ $categories->total() }}</strong> kategori
                        @else
                            Tidak ada data kategori.
                        @endif
                    </div>
                    <div>{{ $categories->links() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL CREATE/EDIT --}}
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="categoryForm" class="modal-content">
                @csrf
                <input type="hidden" name="_method" id="categoryMethod" value="POST">
                <div class="modal-header">
                    <h6 class="modal-title" id="categoryModalTitle">Tambah Kategori</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label mb-1" style="font-size:.8rem;">Kode</label>
                    <input type="text" name="code" id="categoryCode" class="form-control form-control-sm" maxlength="50"
                        required>
                    <label class="form-label mb-1 mt-2" style="font-size:.8rem;">Nama</label>
                    <input type="text" name="name" id="categoryName" class="form-control form-control-sm" maxlength="190"
                        required>
                    <div class="form-check mt-3">
                        <input type="hidden" name="active" value="0">
                        <input type="checkbox" name="active" value="1" class="form-check-input" id="categoryActive"
                            checked>
                        <label class="form-check-label" for="categoryActive" style="font-size:.82rem;">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('categoryForm');
            const title = document.getElementById('categoryModalTitle');
            const methodInput = document.getElementById('categoryMethod');
            const codeInput = document.getElementById('categoryCode');
            const nameInput = document.getElementById('categoryName');
            const activeInput = document.getElementById('categoryActive');

            const storeUrl = "{{ route('master.item_categories.store') }}";
            const updateUrlBase = "{{ url('master/item-categories') }}";

            // Reset ke mode "Tambah"
            document.getElementById('btnAddCategory').addEventListener('click', function () {
                form.action = storeUrl;
                methodInput.value = 'POST';
                title.textContent = 'Tambah Kategori';
                codeInput.value = '';
                nameInput.value = '';
                activeInput.checked = true;
            });

            // Mode "Edit"
            document.querySelectorAll('.btn-edit-category').forEach(btn => {
                btn.addEventListener('click', function () {
                    form.action = updateUrlBase + '/' + btn.dataset.id;
                    methodInput.value = 'PUT';
                    title.textContent = 'Edit Kategori';
                    codeInput.value = btn.dataset.code;
                    nameInput.value = btn.dataset.name;
                    activeInput.checked = btn.dataset.active === '1';
                });
            });
        })();
    </script>
@endpush
