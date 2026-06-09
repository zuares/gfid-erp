{{-- resources/views/master/item_categories/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Kategori Item')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $activeKind = request('kind', '');
    $activeKindLabel = $activeKind ? ($kindLabels[$activeKind] ?? $activeKind) : null;
    $isItemMode = $activeKind !== '';
    $hasFilters = request()->hasAny(['q', 'kind', 'status']);
    $tabBase = request()->only(['q', 'status']);
    $tabUrl = function (?string $kind = null) use ($tabBase) {
        $params = $tabBase;
        if ($kind !== null && $kind !== '') {
            $params['kind'] = $kind;
        }
        return route('master.item_categories.index', array_filter($params, fn($v) => $v !== null && $v !== ''));
    };
    $kindTone = [
        'product' => 'dark',
        'material' => 'blue',
        'support' => 'amber',
        'accessory' => 'slate',
        'packaging' => 'green',
        'other' => 'muted',
    ];
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .gf-category-page {
            display: grid;
            gap: 1rem;
        }

        .gf-category-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: .55rem;
            flex-wrap: wrap;
        }

        .gf-category-actions .btn,
        .gf-category-filter .btn {
            min-height: 38px;
            border-radius: 999px !important;
            font-size: .8rem;
            font-weight: 850;
            padding-inline: .95rem;
        }

        .gf-category-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .7rem;
        }

        .gf-category-kpi-card {
            border: 1px solid rgba(15, 23, 42, .075);
            border-radius: 16px;
            background: linear-gradient(180deg, #fff 0%, #fcfcfd 100%);
            padding: .86rem .95rem;
        }

        .gf-category-kpi-label {
            color: #64748b;
            font-size: .66rem;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: .32rem;
        }

        .gf-category-kpi-value {
            color: #0f172a;
            font-size: 1.18rem;
            font-weight: 950;
            line-height: 1.1;
        }

        .gf-category-kpi-note {
            color: #94a3b8;
            font-size: .72rem;
            font-weight: 800;
            margin-top: .22rem;
        }

        .gf-category-filter {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) minmax(150px, .45fr) auto;
            gap: .65rem;
            align-items: end;
        }

        .gf-category-filter .form-label {
            color: #64748b;
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .02em;
            margin-bottom: .3rem;
        }

        .gf-category-filter .form-control,
        .gf-category-filter .form-select {
            min-height: 40px;
            border-radius: 14px;
            border-color: rgba(15, 23, 42, .10);
            font-size: .84rem;
            box-shadow: none;
        }

        .gf-category-tabs {
            margin-top: .75rem;
        }

        .gf-category-kind {
            display: inline-flex;
            align-items: center;
            gap: .36rem;
            border-radius: 999px;
            padding: .18rem .58rem;
            font-size: .72rem;
            font-weight: 850;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .gf-category-kind-dot {
            width: .44rem;
            height: .44rem;
            border-radius: 999px;
            background: currentColor;
        }

        .gf-category-kind-dark { color: #0f172a; background: #f1f5f9; border-color: #e2e8f0; }
        .gf-category-kind-blue { color: #1d4ed8; background: #dbeafe; border-color: #bfdbfe; }
        .gf-category-kind-amber { color: #92400e; background: #fef3c7; border-color: #fde68a; }
        .gf-category-kind-slate { color: #475569; background: #f1f5f9; border-color: #e2e8f0; }
        .gf-category-kind-green { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .gf-category-kind-muted { color: #64748b; background: #f8fafc; border-color: #e2e8f0; }

        .gf-category-code {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 46px;
            border-radius: 10px;
            padding: .22rem .48rem;
            background: #0f172a;
            color: #fff;
            font-size: .74rem;
            font-weight: 900;
            letter-spacing: .03em;
        }

        .gf-category-name {
            color: #0f172a;
            font-weight: 850;
        }

        .gf-category-sub {
            color: #94a3b8;
            font-size: .72rem;
            margin-top: .12rem;
        }

        .gf-category-row-actions {
            display: flex;
            justify-content: flex-end;
            gap: .35rem;
            flex-wrap: nowrap;
        }

        .gf-category-row-actions .btn {
            border-radius: 999px !important;
            font-size: .74rem;
            font-weight: 800;
        }

        .gf-category-count {
            display: inline-flex;
            align-items: baseline;
            justify-content: center;
            gap: .25rem;
            min-width: 58px;
            border-radius: 999px;
            padding: .2rem .65rem;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 900;
            font-variant-numeric: tabular-nums;
        }

        .gf-category-count small {
            color: #60a5fa;
            font-size: .66rem;
            font-weight: 850;
        }

        .gf-category-type {
            display: inline-flex;
            border-radius: 999px;
            padding: .18rem .55rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-size: .72rem;
            font-weight: 850;
            white-space: nowrap;
        }

        .gf-category-foot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
            border-top: 1px solid #eef2f7;
            padding: .85rem 1rem;
            color: #64748b;
            font-size: .76rem;
        }

        .gf-category-empty {
            text-align: center;
            color: #64748b;
            padding: 2.2rem 1rem;
        }

        .gf-category-empty-title {
            color: #0f172a;
            font-weight: 900;
            margin-bottom: .2rem;
        }

        @media (max-width: 768px) {
            .gf-category-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .gf-category-filter {
                grid-template-columns: 1fr;
            }

            .gf-category-actions {
                justify-content: flex-start;
            }

            .gf-category-row-actions {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 576px) {
            .gf-category-kpi-grid {
                grid-template-columns: 1fr;
            }

            .gf-master-actions {
                flex-basis: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <x-gf.page
        class="gf-category-page"
        eyebrow="Master Data"
        title="Kategori Item"
        description="Pisahkan kategori produk, bahan baku, pendukung, accessories, dan packaging agar Master Item tidak bercampur.">

        <x-slot:actions>
            <div class="gf-category-actions">
                <a href="{{ route('master.items.index') }}" class="btn btn-outline-secondary btn-sm">
                    Master Item
                </a>
                <button type="button" class="btn btn-primary btn-sm text-white" data-bs-toggle="modal"
                    data-bs-target="#categoryModal" id="btnAddCategory">
                    + Tambah Kategori
                </button>
            </div>
        </x-slot:actions>

        {{-- FLASH --}}
        @if (session('success'))
            <div class="alert alert-success py-2 px-3 mb-0" style="font-size:.82rem;">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2 px-3 mb-0" style="font-size:.82rem;">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 mb-0" style="font-size:.82rem;">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="gf-category-kpi-grid">
            <div class="gf-category-kpi-card">
                <div class="gf-category-kpi-label">Total Kategori</div>
                <div class="gf-category-kpi-value">{{ $fmt($totalCategories) }}</div>
                <div class="gf-category-kpi-note">semua kelompok</div>
            </div>
            <div class="gf-category-kpi-card">
                <div class="gf-category-kpi-label">Aktif</div>
                <div class="gf-category-kpi-value">{{ $fmt($activeCount) }}</div>
                <div class="gf-category-kpi-note">bisa dipilih di item</div>
            </div>
            <div class="gf-category-kpi-card">
                <div class="gf-category-kpi-label">Dipakai Item</div>
                <div class="gf-category-kpi-value">{{ $fmt($usedCount) }}</div>
                <div class="gf-category-kpi-note">kategori punya isi</div>
            </div>
            <div class="gf-category-kpi-card">
                <div class="gf-category-kpi-label">Kosong</div>
                <div class="gf-category-kpi-value">{{ $fmt($emptyCount) }}</div>
                <div class="gf-category-kpi-note">aman ditinjau/hapus</div>
            </div>
        </div>

        <x-gf.panel
            :title="$isItemMode ? 'Daftar Item ' . $activeKindLabel : 'Daftar Kategori'"
            :subtitle="$isItemMode ? 'Tab kelompok menampilkan semua item dalam kelompok tersebut.' : 'Gunakan tab kelompok untuk melihat isi item per kelompok.'"
            :flush="true">
            <div class="gf-panel-body">
                <form method="GET" action="{{ route('master.item_categories.index') }}" class="gf-category-filter">
                    <input type="hidden" name="kind" value="{{ $activeKind }}">

                    <div>
                        <label class="form-label">Cari</label>
                        <input type="search" name="q" class="form-control"
                            placeholder="{{ $isItemMode ? 'Kode / nama item' : 'Kode / nama kategori' }}"
                            value="{{ request('q') }}" autocomplete="off">
                    </div>

                    <div>
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="active" @selected(request('status') === 'active')>Aktif</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary w-100">Filter</button>
                        @if ($hasFilters)
                            <a href="{{ route('master.item_categories.index') }}"
                                class="btn btn-outline-light border">Reset</a>
                        @endif
                    </div>
                </form>

                <div class="gf-marketplace-tabs gf-category-tabs" aria-label="Filter kelompok kategori">
                    <a href="{{ $tabUrl(null) }}"
                        class="gf-marketplace-tab text-decoration-none {{ $activeKind === '' ? 'is-active' : '' }}">
                        Kategori <span class="ms-1">{{ $fmt($totalCategories) }}</span>
                    </a>
                    @foreach ($kindLabels as $key => $label)
                        <a href="{{ $tabUrl($key) }}"
                            class="gf-marketplace-tab text-decoration-none {{ $activeKind === $key ? 'is-active' : '' }}">
                            {{ $label }} <span class="ms-1">{{ $fmt($kindItemCounts[$key] ?? 0) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            @if ($isItemMode)
                @if ($kindItems->isEmpty())
                    <div class="gf-category-empty">
                        <div class="gf-category-empty-title">Tidak ada item {{ strtolower($activeKindLabel) }}.</div>
                        <div>Ubah filter atau cek kategori item di Master Item.</div>
                    </div>
                @else
                    <div class="gf-table-scroll">
                        <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table">
                            <thead>
                                <tr>
                                    <th style="width: 68px;" class="text-center">No.</th>
                                    <th style="width: 110px;">Kode Item</th>
                                    <th>Nama Item</th>
                                    <th style="width: 190px;">Kategori</th>
                                    <th style="width: 110px;">Tipe</th>
                                    <th style="width: 150px;">Produksi</th>
                                    <th style="width: 90px;">Satuan</th>
                                    <th style="width: 92px;" class="text-center">Barcode</th>
                                    <th style="width: 130px;" class="text-end">HPP</th>
                                    <th style="width: 100px;" class="text-center">Status</th>
                                    <th style="width: 90px;" class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($kindItems as $item)
                                    @php
                                        $hpp = $item->active_unit_cost ?? 0;
                                        $typeLabels = [
                                            'material' => 'Material',
                                            'wip' => 'WIP',
                                            'finished_good' => 'Finished Good',
                                        ];
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted">{{ $kindItems->firstItem() + $loop->index }}</td>
                                        <td><span class="gf-category-code">{{ $item->code }}</span></td>
                                        <td>
                                            <div class="gf-category-name">{{ $item->name }}</div>
                                            <div class="gf-category-sub">{{ $item->active ? 'Aktif di master item' : 'Nonaktif di master item' }}</div>
                                        </td>
                                        <td>
                                            @if ($item->category)
                                                <div class="gf-category-name">{{ $item->category->code }}</div>
                                                <div class="gf-category-sub">{{ $item->category->name }}</div>
                                            @else
                                                <span class="text-muted small">Tanpa kategori</span>
                                            @endif
                                        </td>
                                        <td><span class="gf-category-type">{{ $typeLabels[$item->type] ?? $item->type }}</span></td>
                                        <td>
                                            @if (in_array($item->type, ['finished_good', 'wip'], true))
                                                <span class="gf-category-type">{{ $item->production_source_label }}</span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td><span class="gf-category-type">{{ $item->unit }}</span></td>
                                        <td class="text-center">
                                            <span class="gf-category-count">{{ $fmt($item->barcodes_count) }}</span>
                                        </td>
                                        <td class="text-end">
                                            @if ($hpp > 0)
                                                <strong>Rp {{ number_format($hpp, 0, ',', '.') }}</strong>
                                            @else
                                                <span class="text-muted small">Belum di-set</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($item->active)
                                                <span class="gf-badge gf-badge-green">Aktif</span>
                                            @else
                                                <span class="gf-badge gf-badge-red">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('master.items.edit', $item) }}" class="btn btn-outline-primary btn-sm rounded-pill">
                                                Edit
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @elseif ($categories->isEmpty())
                <div class="gf-category-empty">
                    <div class="gf-category-empty-title">Tidak ada kategori yang cocok.</div>
                    <div>Ubah filter atau tambah kategori baru untuk kelompok ini.</div>
                </div>
            @else
                <div class="gf-table-scroll">
                    <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table">
                        <thead>
                            <tr>
                                <th style="width: 68px;" class="text-center">No.</th>
                                <th style="width: 90px;">Kode</th>
                                <th>Nama Kategori</th>
                                <th style="width: 210px;">Kelompok</th>
                                <th style="width: 120px;" class="text-center">Item</th>
                                <th style="width: 110px;" class="text-center">Status</th>
                                <th style="width: 160px;" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $cat)
                                @php
                                    $tone = $kindTone[$cat->kind] ?? 'muted';
                                @endphp
                                <tr>
                                    <td class="text-center text-muted">{{ $categories->firstItem() + $loop->index }}</td>
                                    <td><span class="gf-category-code">{{ $cat->code }}</span></td>
                                    <td>
                                        <div class="gf-category-name">{{ $cat->name }}</div>
                                        <div class="gf-category-sub">
                                            {{ $cat->items_count > 0 ? 'Dipakai di master item' : 'Belum dipakai item' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="gf-category-kind gf-category-kind-{{ $tone }}">
                                            <span class="gf-category-kind-dot"></span>
                                            {{ $cat->kind_label }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="gf-category-count">
                                            {{ $fmt($cat->items_count) }} <small>item</small>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($cat->active)
                                            <span class="gf-badge gf-badge-green">Aktif</span>
                                        @else
                                            <span class="gf-badge gf-badge-red">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="gf-category-row-actions">
                                            <button type="button" class="btn btn-outline-primary btn-sm btn-edit-category"
                                                data-id="{{ $cat->id }}" data-code="{{ $cat->code }}"
                                                data-name="{{ $cat->name }}" data-kind="{{ $cat->kind }}"
                                                data-active="{{ $cat->active ? 1 : 0 }}"
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
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                                </form>
                                            @else
                                                <button type="button" class="btn btn-outline-secondary btn-sm" disabled
                                                    title="Masih dipakai {{ $cat->items_count }} item">
                                                    Hapus
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="gf-category-foot">
                <div>
                    @if ($isItemMode)
                        @if ($kindItems->total() > 0)
                            Menampilkan <strong>{{ $kindItems->firstItem() }}-{{ $kindItems->lastItem() }}</strong>
                            dari <strong>{{ $kindItems->total() }}</strong> item {{ strtolower($activeKindLabel) }}
                        @else
                            Tidak ada item {{ strtolower($activeKindLabel) }}.
                        @endif
                    @elseif ($categories->total() > 0)
                        Menampilkan <strong>{{ $categories->firstItem() }}-{{ $categories->lastItem() }}</strong>
                        dari <strong>{{ $categories->total() }}</strong> kategori
                    @else
                        Tidak ada data kategori.
                    @endif
                </div>
                <div>{{ $isItemMode ? $kindItems->links() : $categories->links() }}</div>
            </div>
        </x-gf.panel>
    </x-gf.page>

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

                    <label class="form-label mb-1 mt-2" style="font-size:.8rem;">Kelompok</label>
                    <select name="kind" id="categoryKind" class="form-select form-select-sm" required>
                        @foreach ($kindLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>

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
            const kindInput = document.getElementById('categoryKind');
            const activeInput = document.getElementById('categoryActive');
            const addButton = document.getElementById('btnAddCategory');

            const storeUrl = "{{ route('master.item_categories.store') }}";
            const updateUrlBase = "{{ url('master/item-categories') }}";

            if (addButton) {
                addButton.addEventListener('click', function () {
                    form.action = storeUrl;
                    methodInput.value = 'POST';
                    title.textContent = 'Tambah Kategori';
                    codeInput.value = '';
                    nameInput.value = '';
                    kindInput.value = '{{ $activeKind ?: 'product' }}';
                    activeInput.checked = true;
                });
            }

            document.querySelectorAll('.btn-edit-category').forEach(btn => {
                btn.addEventListener('click', function () {
                    form.action = updateUrlBase + '/' + btn.dataset.id;
                    methodInput.value = 'PUT';
                    title.textContent = 'Edit Kategori';
                    codeInput.value = btn.dataset.code;
                    nameInput.value = btn.dataset.name;
                    kindInput.value = btn.dataset.kind || 'product';
                    activeInput.checked = btn.dataset.active === '1';
                });
            });
        })();
    </script>
@endpush
