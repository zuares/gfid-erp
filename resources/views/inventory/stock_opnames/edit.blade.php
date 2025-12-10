{{-- resources/views/inventory/stock_opnames/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Stock Opname • ' . $opname->code)

@push('head')
    <style>
        :root {
            --so-card-radius: 14px;
            --so-border: rgba(148, 163, 184, 0.30);
            --so-muted: #6b7280;
        }

        .so-page {
            min-height: 100vh;
        }

        .so-page .page-wrap {
            max-width: 1000px;
            margin-inline: auto;
            padding: 1rem .85rem 3rem;
        }

        body[data-theme="light"] .so-page .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(148, 163, 184, 0.08) 30%,
                    #f9fafb 70%);
        }

        body[data-theme="dark"] .so-page .page-wrap {
            background:
                radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.26) 0,
                    rgba(15, 23, 42, 1) 55%);
        }

        .card-main {
            background: var(--card);
            border-radius: var(--so-card-radius);
            border: 1px solid var(--so-border);
        }

        .page-subtitle {
            font-size: .82rem;
            color: var(--so-muted);
        }

        .pill-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
        }

        .text-mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New",
                monospace;
        }

        .badge-status {
            font-size: .72rem;
            padding: .16rem .6rem;
            border-radius: 999px;
            font-weight: 600;
        }

        .badge-status--draft {
            background: rgba(148, 163, 184, .16);
            color: #475569;
        }

        .badge-status--counting {
            background: rgba(59, 130, 246, .18);
            color: #1d4ed8;
        }

        .badge-status--reviewed {
            background: rgba(234, 179, 8, .20);
            color: #854d0e;
        }

        .badge-status--finalized {
            background: rgba(22, 163, 74, .20);
            color: #15803d;
        }

        .table-wrap {
            margin-top: .75rem;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, .25);
            overflow: hidden;
            background: rgba(248, 250, 252, .9);
        }

        body[data-theme="dark"] .table-wrap {
            background: rgba(15, 23, 42, 0.92);
            border-color: rgba(51, 65, 85, .9);
        }

        .table thead th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
            background: rgba(15, 23, 42, 0.02);
        }

        body[data-theme="dark"] .table thead th {
            background: rgba(15, 23, 42, 0.8);
            color: #e5e7eb;
        }

        .table tbody td {
            vertical-align: middle;
            font-size: .82rem;
        }

        .diff-plus {
            color: #16a34a;
        }

        .diff-minus {
            color: #dc2626;
        }

        /* kolom yang disembunyikan di mobile */
        .col-mobile-hide {
            /* default: terlihat di desktop */
        }

        .mobile-hide-inline {
            /* default: terlihat di desktop */
        }

        .btn-mobile-full {
            /* default desktop: biasa saja */
        }

        .btn-mobile-icon-delete {
            /* default desktop: biasa saja */
        }

        /* MINI MODAL DUPLIKAT ITEM */
        .duplicate-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1050;
        }

        .duplicate-modal {
            background: var(--card, #fff);
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            padding: .9rem .95rem;
            max-width: 360px;
            width: 100%;
            box-shadow:
                0 18px 40px rgba(15, 23, 42, 0.35),
                0 0 0 1px rgba(15, 23, 42, 0.18);
        }

        .duplicate-modal-title {
            font-size: .95rem;
            font-weight: 600;
            margin-bottom: .4rem;
        }

        .duplicate-modal-body {
            font-size: .8rem;
            color: #475569;
        }

        .duplicate-modal-body .label {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
        }

        .duplicate-modal-body .value {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New",
                monospace;
            font-size: .82rem;
        }

        .duplicate-modal-actions {
            margin-top: .7rem;
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
        }

        @media (max-width: 767.98px) {
            .so-page .page-wrap {
                padding-inline: .6rem;
            }

            /* hanya tampilkan kolom: #, Item (kode), Qty Fisik, Selisih */
            .col-mobile-hide {
                display: none !important;
            }

            .mobile-hide-inline {
                display: none !important;
            }

            .table-wrap {
                overflow-x: auto;
            }

            .btn-mobile-full {
                display: inline-block;
                width: 100%;
            }

            .btn-mobile-icon-delete {
                padding-inline: .4rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $isReadonly = in_array($opname->status, ['reviewed', 'finalized']);
        $isOpening = $opname->type === 'opening';

        // Urutkan baris: paling baru (id terbesar) di atas
        $lines = $opname->lines->sortByDesc('id')->values();
    @endphp

    <div class="so-page">
        <div class="page-wrap">
            {{-- HEADER --}}
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <a href="{{ route('inventory.stock_opnames.show', $opname) }}" class="btn btn-link btn-sm px-0 mb-1">
                        ← Kembali ke detail
                    </a>
                    <h1 class="h5 mb-1">
                        Stock Opname • {{ $opname->code }}
                    </h1>
                    <p class="page-subtitle mb-0">
                        @if ($isReadonly)
                            Dokumen ini sudah {{ $opname->status === 'finalized' ? 'difinalkan' : 'direview' }}.
                        @else
                            @if ($isOpening)
                                Mode saldo awal: Qty &amp; HPP di tabel hanya tampilan. Ubah lewat form di atas.
                            @else
                                Isi hasil hitung fisik per item, lalu simpan.
                            @endif
                        @endif
                    </p>
                </div>
                <div class="text-end">
                    @php
                        $statusClass = match ($opname->status) {
                            'draft' => 'badge-status badge-status--draft',
                            'counting' => 'badge-status badge-status--counting',
                            'reviewed' => 'badge-status badge-status--reviewed',
                            'finalized' => 'badge-status badge-status--finalized',
                            default => 'badge-status badge-status--draft',
                        };
                    @endphp
                    <span class="{{ $statusClass }}">{{ ucfirst($opname->status) }}</span>
                </div>
            </div>

            {{-- FORM TAMBAH ITEM (OPENING MODE, SIMPLE + AJAX) --}}
            @if ($isOpening && !$isReadonly)
                <div class="card card-main mb-3">
                    <div class="card-body">
                        <div class="mb-2">
                            <div class="pill-label mb-1">Tambah item saldo awal</div>
                            <div style="font-size:.8rem;color:#6b7280;">
                                Flow: ketik item → Tab ke Qty → Enter untuk simpan → fokus kembali ke item.
                                Jika item sudah ada, akan muncul konfirmasi sebelum update baris lama.
                            </div>
                        </div>

                        <form id="opening-add-form" action="{{ route('inventory.stock_opnames.lines.store', $opname) }}"
                            method="POST" class="row g-2 align-items-end">
                            @csrf

                            {{-- Item (pakai item-suggest) --}}
                            <div class="col-md-4" id="opening-item-suggest">
                                <label class="pill-label mb-1">Item</label>
                                <x-item-suggest idName="item_id" :idValue="old('item_id')" :displayValue="''"
                                    placeholder="Kode / nama barang" />
                            </div>

                            {{-- Qty Fisik --}}
                            <div class="col-6 col-md-2">
                                <label class="pill-label mb-1">Qty Fisik</label>
                                <x-number-input name="physical_qty" :value="old('physical_qty')" mode="integer" min="0"
                                    class="text-end js-opening-qty" />
                            </div>

                            {{-- HPP / Unit --}}
                            <div class="col-6 col-md-2">
                                <label class="pill-label mb-1">HPP / Unit</label>
                                <x-number-input name="unit_cost" :value="old('unit_cost')" mode="decimal" :decimals="2"
                                    min="0" class="text-end" />
                            </div>

                            {{-- Catatan --}}
                            <div class="col-md-3">
                                <label class="pill-label mb-1">Catatan</label>
                                <input type="text" name="notes" value="{{ old('notes') }}"
                                    class="form-control form-control-sm">
                            </div>

                            {{-- Hidden flag untuk update existing row --}}
                            <input type="hidden" name="update_existing" value="0">

                            {{-- Tombol submit --}}
                            <div class="col-md-1 d-grid">
                                <button type="submit" class="btn btn-sm btn-primary btn-mobile-full">
                                    + Tambah
                                </button>
                            </div>

                            @if ($errors->has('item_id') || $errors->has('physical_qty') || $errors->has('unit_cost'))
                                <div class="col-12 mt-1">
                                    @error('item_id')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                    @error('physical_qty')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                    @error('unit_cost')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            @endif

            {{-- FORM UPDATE UTAMA (HEADER + TABEL) --}}
            <form action="{{ route('inventory.stock_opnames.update', $opname) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- META DOKUMEN --}}
                <div class="card card-main mb-3">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="pill-label mb-1">Kode Dokumen</div>
                                <div class="text-mono fw-semibold">{{ $opname->code }}</div>

                                <div class="pill-label mt-3 mb-1">Tanggal Opname</div>
                                <div style="font-size:.85rem;">
                                    {{ $opname->date?->format('d M Y') ?? '-' }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="pill-label mb-1">Gudang</div>
                                <div class="fw-semibold">
                                    {{ $opname->warehouse?->code ?? '-' }}
                                </div>
                                <div style="font-size:.85rem;color:#6b7280;">
                                    {{ $opname->warehouse?->name }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="pill-label mb-1">Catatan</div>
                                <textarea name="notes" class="form-control form-control-sm" rows="3" placeholder="Catatan tambahan…"
                                    @if ($isReadonly) readonly @endif>{{ old('notes', $opname->notes) }}</textarea>
                            </div>
                        </div>

                        @if (!$isReadonly)
                            <div class="mt-3 d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-sm btn-primary btn-mobile-full">
                                    Simpan Perubahan
                                </button>
                                @if (in_array($opname->status, ['draft', 'counting']))
                                    <button type="submit" name="mark_reviewed" value="1"
                                        class="btn btn-sm btn-outline-primary btn-mobile-full">
                                        Simpan &amp; Tandai Selesai Counting
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- TABEL ITEM --}}
                @php
                    $totalLines = $lines->count();
                    $countedLines = $lines->whereNotNull('physical_qty')->count();
                @endphp

                <div class="card card-main">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                            <div>
                                <div class="pill-label mb-1">
                                    @if ($isOpening)
                                        Saldo awal per item
                                    @else
                                        Hasil hitung fisik per item
                                    @endif
                                </div>
                                <div style="font-size:.78rem;color:#6b7280;">
                                    @if ($isOpening)
                                        Angka Qty &amp; HPP di tabel hanya tampilan (2 digit desimal).
                                        Ubah via form tambah di atas.
                                    @else
                                        Isi Qty Fisik per item, selisih akan dihitung saat update.
                                    @endif
                                </div>
                            </div>
                            <div style="font-size:.8rem;color:#6b7280;">
                                {{ $countedLines }} / {{ $totalLines }} item sudah terisi Qty fisik
                            </div>
                        </div>

                        {{-- TOMBOL RESET QTY & HPP (soft reset) --}}
                        @if ($isOpening && !$isReadonly && $lines->count() > 0)
                            <div class="mb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div style="font-size:.78rem;color:#6b7280;">
                                    Reset hanya mengosongkan Qty Fisik &amp; HPP semua baris, daftar item tetap ada.
                                </div>
                                <form action="{{ route('inventory.stock_opnames.reset_lines', $opname) }}" method="POST"
                                    onsubmit="return confirm('Reset Qty Fisik dan HPP semua baris untuk sesi opname ini?\n\nDaftar item tetap dipertahankan. Lanjutkan?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning btn-mobile-full">
                                        Reset Qty &amp; HPP Semua Baris
                                    </button>
                                </form>
                            </div>
                        @endif

                        <div class="table-wrap mt-2" id="opname-lines-table"
                            data-delete-url-template="{{ route('inventory.stock_opnames.lines.destroy', ['stockOpname' => $opname, 'line' => '__LINE_ID__']) }}">
                            <table class="table table-sm mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th style="width:40px;">#</th>
                                        <th>Item</th>
                                        <th class="text-end col-mobile-hide">Qty Sistem</th>
                                        <th class="text-end" style="width:140px;">Qty Fisik</th>
                                        <th class="text-end">Selisih</th>
                                        @if ($isOpening)
                                            <th class="text-end col-mobile-hide" style="width:140px;">HPP / Unit</th>
                                        @endif
                                        <th class="col-mobile-hide">Catatan</th>
                                        @if ($isOpening && !$isReadonly)
                                            <th class="text-end col-mobile-hide" style="width:70px;">Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lines as $index => $line)
                                        @php
                                            $diff = $line->difference;
                                            $hasPhysical = !is_null($line->physical_qty);
                                            $diffDisplay =
                                                $diff > 0 ? '+' . number_format($diff, 2) : number_format($diff, 2);
                                            $diffClass = $diff > 0 ? 'diff-plus' : ($diff < 0 ? 'diff-minus' : '');
                                            $inputNamePrefix = "lines[{$line->id}]";
                                        @endphp
                                        <tr data-item-id="{{ $line->item_id }}"
                                            data-existing-qty="{{ (float) ($line->physical_qty ?? 0) }}"
                                            data-existing-hpp="{{ (float) ($line->unit_cost ?? 0) }}"
                                            data-item-code="{{ $line->item?->code }}"
                                            data-item-name="{{ $line->item?->name }}">
                                            <td data-label="#">
                                                {{ $index + 1 }}
                                            </td>
                                            <td data-label="Item">
                                                <div class="fw-semibold text-mono">
                                                    {{ $line->item?->code ?? '-' }}
                                                </div>
                                                <div class="mobile-hide-inline" style="font-size:.82rem;color:#6b7280;">
                                                    {{ $line->item?->name ?? '' }}
                                                </div>
                                            </td>
                                            <td data-label="Qty sistem" class="text-end text-mono col-mobile-hide">
                                                {{ number_format((float) $line->system_qty, 2) }}
                                            </td>

                                            {{-- Qty fisik --}}
                                            <td data-label="Qty fisik" class="text-end text-mono">
                                                @if ($isOpening)
                                                    @if (!is_null($line->physical_qty))
                                                        {{ number_format((float) $line->physical_qty, 2) }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                @else
                                                    {{-- mode periodik: tetap bisa input --}}
                                                    <x-number-input :name="$inputNamePrefix . '[physical_qty]'" :value="old(
                                                        $inputNamePrefix . '.physical_qty',
                                                        $line->physical_qty,
                                                    )" mode="decimal"
                                                        :decimals="2" min="0" class="text-end" />
                                                @endif
                                            </td>

                                            {{-- Selisih --}}
                                            <td data-label="Selisih" class="text-end text-mono">
                                                @if ($hasPhysical)
                                                    <span class="{{ $diffClass }}">{{ $diffDisplay }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>

                                            {{-- HPP / Unit (hanya opening, read-only) --}}
                                            @if ($isOpening)
                                                <td data-label="HPP / Unit" class="text-end text-mono col-mobile-hide">
                                                    @if (!is_null($line->unit_cost))
                                                        {{ number_format((float) $line->unit_cost, 2) }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            @endif

                                            {{-- Catatan --}}
                                            <td data-label="Catatan" class="col-mobile-hide">
                                                <input type="text" name="{{ $inputNamePrefix }}[notes]"
                                                    class="form-control form-control-sm"
                                                    value="{{ old($inputNamePrefix . '.notes', $line->notes) }}"
                                                    @if ($isReadonly) readonly @endif>
                                            </td>

                                            {{-- Aksi (hapus) --}}
                                            @if ($isOpening && !$isReadonly)
                                                <td data-label="Aksi" class="text-end col-mobile-hide">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger js-delete-line btn-mobile-icon-delete">
                                                        <span class="d-none d-md-inline">Hapus</span>
                                                        <span class="d-inline d-md-none">✕</span>
                                                    </button>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- MINI MODAL KONFIRMASI DUPLIKAT ITEM --}}
    <div class="duplicate-modal-backdrop" id="duplicate-modal">
        <div class="duplicate-modal">
            <div class="duplicate-modal-title">
                Item sudah ada di daftar opname
            </div>
            <div class="duplicate-modal-body">
                <div class="mb-2">
                    <div class="label">Item</div>
                    <div class="value" id="dup-item-label">-</div>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <div class="label">Qty lama</div>
                        <div class="value" id="dup-old-qty">-</div>
                    </div>
                    <div class="col-6">
                        <div class="label">Qty baru</div>
                        <div class="value" id="dup-new-qty">-</div>
                    </div>
                    <div class="col-6 mt-2">
                        <div class="label">HPP lama</div>
                        <div class="value" id="dup-old-hpp">-</div>
                    </div>
                    <div class="col-6 mt-2">
                        <div class="label">HPP baru</div>
                        <div class="value" id="dup-new-hpp">-</div>
                    </div>
                </div>
                <div class="mt-2" style="font-size:.78rem;color:#6b7280;">
                    Lanjutkan untuk <strong>mengganti Qty &amp; HPP baris lama</strong> dengan nilai baru.
                </div>
            </div>
            <div class="duplicate-modal-actions">
                <button type="button" class="btn btn-sm btn-primary" id="dup-confirm-update">
                    Update baris lama
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="dup-cancel">
                    Batal
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initOpeningAddAjax();
            initDeleteLineAjax();
        });

        function getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) return meta.getAttribute('content');
            const input = document.querySelector('input[name="_token"]');
            if (input) return input.value;
            return '';
        }

        function initOpeningAddAjax() {
            const addForm = document.getElementById('opening-add-form');
            if (!addForm) return;

            const itemInput = document.querySelector('#opening-item-suggest .js-item-suggest-input');
            const qtyInput = addForm.querySelector('.js-opening-qty');
            const updateExistingInput = addForm.querySelector('input[name="update_existing"]');

            // Fokus awal ke item
            if (itemInput) {
                itemInput.focus();
                itemInput.select();
            }

            // Kumpulkan item_id yang sudah ada di tabel
            const existingIds = new Set();
            document.querySelectorAll('tr[data-item-id]').forEach(tr => {
                const id = tr.getAttribute('data-item-id');
                if (id) existingIds.add(id);
            });

            // Enter di Qty Fisik => submit via AJAX
            if (qtyInput) {
                qtyInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        submitOpeningFormAjax(addForm, existingIds, updateExistingInput, itemInput, false);
                    }
                });
            }

            // Klik tombol Tambah => juga AJAX
            addForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitOpeningFormAjax(addForm, existingIds, updateExistingInput, itemInput, false);
            });
        }

        function submitOpeningFormAjax(addForm, existingIds, updateExistingInput, itemInput, skipDuplicateCheck) {
            const itemIdField = addForm.querySelector('input[name="item_id"]');
            if (!itemIdField || !itemIdField.value) {
                alert('Pilih item terlebih dahulu.');
                if (itemInput) {
                    itemInput.focus();
                }
                return;
            }

            const itemId = itemIdField.value;

            // ambil value baru dari form
            const qtyInput = addForm.querySelector('input[name="physical_qty"]');
            const hppInput = addForm.querySelector('input[name="unit_cost"]');
            const newQty = qtyInput ? qtyInput.value : '';
            const newHpp = hppInput ? hppInput.value : '';

            // Cek duplikat → gunakan modal mini
            if (!skipDuplicateCheck && existingIds.has(itemId)) {
                openDuplicateModal({
                    itemId,
                    newQty,
                    newHpp,
                    addForm,
                    existingIds,
                    updateExistingInput,
                    itemInput
                });
                return;
            }

            const formData = new FormData(addForm);

            fetch(addForm.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                })
                .then(async (response) => {
                    if (!response.ok) {
                        let msg = 'Gagal menyimpan item.';
                        try {
                            const data = await response.json();
                            if (data?.message) {
                                msg = data.message;
                            } else if (data?.errors) {
                                const firstKey = Object.keys(data.errors)[0];
                                msg = data.errors[firstKey][0] ?? msg;
                            }
                        } catch (e) {}
                        alert(msg);
                        return null;
                    }
                    return response.json();
                })
                .then((data) => {
                    if (!data) return;
                    if (data.status === 'ok') {
                        // Reload supaya tabel & summary ke-refresh
                        window.location.reload();
                    } else {
                        alert(data.message || 'Gagal menyimpan item.');
                    }
                })
                .catch(() => {
                    alert('Terjadi kesalahan saat menyimpan item.');
                });
        }

        function openDuplicateModal(ctx) {
            const {
                itemId,
                newQty,
                newHpp,
                addForm,
                existingIds,
                updateExistingInput,
                itemInput
            } = ctx;

            const backdrop = document.getElementById('duplicate-modal');
            if (!backdrop) return;

            const row = document.querySelector('tr[data-item-id="' + itemId + '"]');
            let itemLabel = 'Item ID ' + itemId;
            let oldQty = '-';
            let oldHpp = '-';

            if (row) {
                const code = row.dataset.itemCode || '';
                const name = row.dataset.itemName || '';
                itemLabel = code + (name ? ' — ' + name : '');
                oldQty = row.dataset.existingQty ?? '-';
                oldHpp = row.dataset.existingHpp ?? '-';
            }

            const numOrDash = (v) => {
                if (v === '' || v === null || typeof v === 'undefined') return '-';
                const n = parseFloat(String(v).replace(',', '.'));
                if (isNaN(n)) return v;
                return n.toFixed(2);
            };

            document.getElementById('dup-item-label').textContent = itemLabel;
            document.getElementById('dup-old-qty').textContent = numOrDash(oldQty);
            document.getElementById('dup-old-hpp').textContent = numOrDash(oldHpp);
            document.getElementById('dup-new-qty').textContent = numOrDash(newQty);
            document.getElementById('dup-new-hpp').textContent = numOrDash(newHpp);

            backdrop.style.display = 'flex';

            const confirmBtn = document.getElementById('dup-confirm-update');
            const cancelBtn = document.getElementById('dup-cancel');

            function closeModal() {
                backdrop.style.display = 'none';
                confirmBtn.removeEventListener('click', onConfirm);
                cancelBtn.removeEventListener('click', onCancel);
            }

            function onConfirm() {
                if (updateExistingInput) {
                    updateExistingInput.value = '1';
                }
                closeModal();
                // submit lagi tapi skip cek duplikat
                submitOpeningFormAjax(addForm, existingIds, updateExistingInput, itemInput, true);
            }

            function onCancel() {
                closeModal();
            }

            confirmBtn.addEventListener('click', onConfirm);
            cancelBtn.addEventListener('click', onCancel);
        }

        function initDeleteLineAjax() {
            const tableWrap = document.getElementById('opname-lines-table');
            if (!tableWrap) return;

            const urlTemplate = tableWrap.dataset.deleteUrlTemplate;
            if (!urlTemplate) return;

            tableWrap.querySelectorAll('.js-delete-line').forEach(btn => {
                btn.addEventListener('click', function() {
                    const lineId = this.dataset.lineId || this.closest('tr')?.dataset.lineId;
                    if (!lineId) return;

                    if (!confirm('Hapus baris ini dari sesi opname?')) {
                        return;
                    }

                    const url = urlTemplate.replace('__LINE_ID__', lineId);

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-Requested-With'
                                X: 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: new URLSearchParams({
                                '_method': 'DELETE',
                            }),
                        })
                        .then(async (response) => {
                            if (!response.ok) {
                                let msg = 'Gagal menghapus item.';
                                try {
                                    const data = await response.json();
                                    if (data?.message) msg = data.message;
                                } catch (e) {}
                                alert(msg);
                                return null;
                            }
                            return response.json();
                        })
                        .then((data) => {
                            if (!data) return;
                            if (data.status === 'ok') {
                                const tr = btn.closest('tr');
                                if (tr) tr.remove();
                            } else {
                                alert(data.message || 'Gagal menghapus item.');
                            }
                        })
                        .catch(() => {
                            alert('Terjadi kesalahan saat menghapus item.');
                        });
                });
            });
        }
    </script>
@endpush
