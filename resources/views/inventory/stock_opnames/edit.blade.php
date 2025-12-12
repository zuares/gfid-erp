{{-- resources/views/inventory/stock_opnames/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Stock Opname • ' . $opname->code)

@php
    use App\Models\StockOpname;
    use App\Models\ItemCostSnapshot;

    // Gunakan helper dari model kalau ada
    $isOpening = method_exists($opname, 'isOpening')
        ? $opname->isOpening()
        : $opname->type === StockOpname::TYPE_OPENING;

    $canModifyLines = method_exists($opname, 'canModifyLines')
        ? $opname->canModifyLines()
        : !in_array($opname->status, [StockOpname::STATUS_REVIEWED, StockOpname::STATUS_FINALIZED], true);

    $isReadonly = !$canModifyLines;
@endphp

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
            overflow-x: auto;
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
            white-space: nowrap;
        }

        body[data-theme="dark"] .table thead th {
            background: rgba(15, 23, 42, 0.8);
            color: #e5e7eb;
        }

        .table tbody td {
            vertical-align: middle;
            font-size: .82rem;
            white-space: nowrap;
        }

        .diff-plus {
            color: #16a34a;
        }

        .diff-minus {
            color: #dc2626;
        }

        @media (max-width: 767.98px) {
            .so-page .page-wrap {
                padding-inline: .6rem;
            }

            .table-wrap {
                border-radius: 8px;
            }

            .table thead th {
                font-size: .7rem;
            }

            .table tbody td {
                font-size: .78rem;
            }

            /* Hide kolom yang kurang penting di mobile */
            .col-diff,
            .col-notes {
                display: none;
            }
        }

        /* Mini modal duplikat item */
        .dup-meta {
            font-size: .78rem;
            color: #6b7280;
        }

        .dup-meta .text-mono {
            font-size: .8rem;
        }
    </style>
@endpush

@section('content')
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
                            Dokumen ini sudah
                            {{ $opname->status === StockOpname::STATUS_FINALIZED ? 'difinalkan' : 'direview' }}.
                        @else
                            @if ($isOpening)
                                Mode saldo awal: isi <span class="text-mono">Qty Fisik</span> dan
                                <span class="text-mono">HPP / Unit</span>.
                            @else
                                Isi hasil hitung fisik per item, lalu simpan.
                            @endif
                        @endif
                    </p>
                </div>
                <div class="text-end">
                    @php
                        $statusClass = match ($opname->status) {
                            StockOpname::STATUS_DRAFT => 'badge-status badge-status--draft',
                            StockOpname::STATUS_COUNTING => 'badge-status badge-status--counting',
                            StockOpname::STATUS_REVIEWED => 'badge-status badge-status--reviewed',
                            StockOpname::STATUS_FINALIZED => 'badge-status badge-status--finalized',
                            default => 'badge-status badge-status--draft',
                        };
                    @endphp
                    <span class="{{ $statusClass }}">{{ ucfirst($opname->status) }}</span>
                </div>
            </div>

            {{-- FORM TAMBAH ITEM (OPENING MODE, SIMPLE + AJAX) --}}
            @if ($isOpening && $canModifyLines)
                <div class="card card-main mb-3">
                    <div class="card-body">
                        <div class="mb-2">
                            <div class="pill-label mb-1">Tambah item saldo awal</div>
                            <div style="font-size:.8rem;color:#6b7280;">
                                Flow: ketik item → Tab (ambil suggest pertama) → isi Qty → Enter untuk simpan → fokus
                                kembali ke item.
                            </div>
                        </div>

                        <form id="opening-add-form" action="{{ route('inventory.stock_opnames.lines.store', $opname) }}"
                            method="POST" class="row g-2 align-items-end">
                            @csrf

                            {{-- Item (pakai item-suggest versi terbaru) --}}
                            <div class="col-md-4" id="opening-item-suggest">
                                <label class="pill-label mb-1">Item</label>
                                <x-item-suggest idName="item_id" :idValue="old('item_id')" :displayValue="''"
                                    placeholder="Kode / nama barang" :autofocus="true" :autoSelectFirst="true" />
                            </div>

                            {{-- Qty Fisik --}}
                            <div class="col-6 col-md-2">
                                <label class="pill-label mb-1">Qty Fisik</label>
                                <x-number-input name="physical_qty" :value="old('physical_qty')" mode="integer" min="0"
                                    class="text-end js-opening-qty js-next-focus" />
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
                                <button type="submit" class="btn btn-sm btn-primary">
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

                        @if ($canModifyLines)
                            <div class="mt-3 d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    Simpan Perubahan
                                </button>
                                @if (in_array($opname->status, [StockOpname::STATUS_DRAFT, StockOpname::STATUS_COUNTING], true))
                                    <button type="submit" name="mark_reviewed" value="1"
                                        class="btn btn-sm btn-outline-primary">
                                        Simpan &amp; Tandai Selesai Counting
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- TABEL ITEM --}}
                @php
                    $totalLines = $opname->lines->count();
                    $countedLines = $opname->lines->whereNotNull('physical_qty')->count();
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
                                    Isi Qty fisik (dan HPP jika opening). Selisih dihitung saat disimpan.
                                </div>
                            </div>
                            <div style="font-size:.8rem;color:#6b7280;">
                                {{ $countedLines }} / {{ $totalLines }} item sudah terisi Qty fisik
                            </div>
                        </div>

                        {{-- TOMBOL RESET QTY & HPP (soft reset) --}}
                        @if ($isOpening && $canModifyLines && $opname->lines->count() > 0)
                            <div class="mb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div style="font-size:.78rem;color:#6b7280;">
                                    Reset hanya mengosongkan Qty Fisik &amp; HPP semua baris, daftar item tetap ada.
                                </div>
                                <form action="{{ route('inventory.stock_opnames.reset_lines', $opname) }}" method="POST"
                                    onsubmit="return confirm('Reset Qty Fisik dan HPP semua baris untuk sesi opname ini?\n\nDaftar item tetap dipertahankan. Lanjutkan?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning">
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
                                        <th class="text-end">Qty Sistem</th>
                                        <th class="text-end">Qty Fisik</th>
                                        <th class="text-end col-diff d-none d-md-table-cell">Selisih</th>
                                        @if ($isOpening)
                                            <th class="text-end">HPP / Unit</th>
                                        @endif
                                        <th class="col-notes d-none d-md-table-cell">Catatan</th>
                                        @if ($isOpening && $canModifyLines)
                                            <th class="text-end" style="width:70px;">Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($opname->lines as $index => $line)
                                        @php
                                            $diff = $line->difference ?? ($line->difference_qty ?? 0);
                                            $hasPhysical = !is_null($line->physical_qty);
                                            $diffDisplay =
                                                $diff > 0 ? '+' . number_format($diff, 2) : number_format($diff, 2);
                                            $diffClass = $diff > 0 ? 'diff-plus' : ($diff < 0 ? 'diff-minus' : '');
                                            $inputNamePrefix = "lines[{$line->id}]";

                                            $rawSystemQty = $line->system_qty ?? 0;

                                            $rawPhysical = old($inputNamePrefix . '.physical_qty', $line->physical_qty);
                                            $hasPhysicalValue = $rawPhysical !== null && $rawPhysical !== '';
                                            if ($hasPhysicalValue) {
                                                $rawPhysical = (float) $rawPhysical;
                                            }

                                            // ==== HPP / UNIT (EFFECTIVE) ====
                                            $rawUnitCost = old($inputNamePrefix . '.unit_cost', $line->unit_cost);
                                            $hasUnitCostValue = $rawUnitCost !== null && $rawUnitCost !== '';
                                            if ($hasUnitCostValue) {
                                                $rawUnitCost = (float) $rawUnitCost;
                                            }

                                            $fallbackUnitCost = null;

                                            if (!$hasUnitCostValue) {
                                                // 1️⃣ coba snapshot aktif per item+gudang
                                                $snapshot = ItemCostSnapshot::getActiveForItem(
                                                    $line->item_id,
                                                    $opname->warehouse_id,
                                                );
                                                if ($snapshot && $snapshot->unit_cost > 0) {
                                                    $fallbackUnitCost = (float) $snapshot->unit_cost;
                                                } elseif ($line->item && $line->item->base_unit_cost > 0) {
                                                    // 2️⃣ fallback ke base_unit_cost item
                                                    $fallbackUnitCost = (float) $line->item->base_unit_cost;
                                                }
                                            }

                                            $effectiveUnitCost = $hasUnitCostValue ? $rawUnitCost : $fallbackUnitCost;
                                        @endphp
                                        <tr data-item-id="{{ $line->item_id }}"
                                            data-item-code="{{ $line->item?->code }}"
                                            data-item-name="{{ $line->item?->name }}"
                                            data-physical-qty="{{ $hasPhysicalValue ? $rawPhysical : '' }}">
                                            <td>
                                                {{ $index + 1 }}
                                            </td>
                                            <td>
                                                <div class="fw-semibold">
                                                    {{ $line->item?->code ?? '-' }}
                                                </div>
                                                <div style="font-size:.82rem;color:#6b7280;">
                                                    {{ $line->item?->name ?? '' }}
                                                </div>
                                            </td>
                                            <td class="text-end text-mono">
                                                {{ number_format($rawSystemQty, 2) }}
                                                <input type="hidden" name="{{ $inputNamePrefix }}[system_qty]"
                                                    value="{{ $rawSystemQty }}">
                                            </td>
                                            <td class="text-end">
                                                @if ($isReadonly || $isOpening)
                                                    {{-- Opening atau readonly: Qty hanya display --}}
                                                    @if ($hasPhysicalValue)
                                                        <span class="text-mono">
                                                            {{ number_format($rawPhysical, 2) }}
                                                        </span>
                                                        <input type="hidden" name="{{ $inputNamePrefix }}[physical_qty]"
                                                            value="{{ $rawPhysical }}">
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                @else
                                                    {{-- Periodic & masih boleh edit -> input --}}
                                                    <input type="number" step="0.01" min="0"
                                                        name="{{ $inputNamePrefix }}[physical_qty]"
                                                        class="form-control form-control-sm text-end"
                                                        value="{{ $hasPhysicalValue ? $rawPhysical : '' }}">
                                                @endif
                                            </td>
                                            <td class="text-end text-mono col-diff d-none d-md-table-cell">
                                                @if ($hasPhysical)
                                                    <span class="{{ $diffClass }}">{{ $diffDisplay }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>

                                            @if ($isOpening)
                                                <td class="text-end">
                                                    @if ($effectiveUnitCost && $effectiveUnitCost > 0)
                                                        <span
                                                            class="text-mono {{ $hasUnitCostValue ? '' : 'text-muted' }}">
                                                            {{ number_format($effectiveUnitCost, 2) }}
                                                        </span>
                                                        @unless ($hasUnitCostValue)
                                                            <div style="font-size:.72rem;color:#9ca3af;">
                                                                (HPP snapshot/master)
                                                            </div>
                                                        @endunless
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif

                                                    {{-- 🔁 SELALU KIRIM HPP KE SERVER --}}
                                                    <input type="hidden" name="{{ $inputNamePrefix }}[unit_cost]"
                                                        value="{{ $effectiveUnitCost !== null ? $effectiveUnitCost : '' }}">
                                                </td>
                                            @endif

                                            <td class="col-notes d-none d-md-table-cell">
                                                <input type="text" name="{{ $inputNamePrefix }}[notes]"
                                                    class="form-control form-control-sm"
                                                    value="{{ old($inputNamePrefix . '.notes', $line->notes) }}"
                                                    @if ($isReadonly) readonly @endif>
                                            </td>
                                            @if ($isOpening && $canModifyLines)
                                                <td class="text-end">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger js-delete-line"
                                                        data-line-id="{{ $line->id }}">
                                                        Hapus
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

    {{-- MINI MODAL: KONFIRMASI DUPLIKAT ITEM --}}
    <div class="modal fade" id="duplicateItemModal" tabindex="-1" aria-labelledby="duplicateItemModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title" id="duplicateItemModalLabel">Item sudah ada di opname</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-1">
                        <div class="fw-semibold" id="dupItemLabel">Item</div>
                        <div class="dup-meta">
                            Akan <span class="fw-semibold">mengganti</span> Qty &amp; HPP baris yang sudah ada untuk item
                            ini.
                        </div>
                    </div>

                    <div class="dup-meta mt-2">
                        <div class="d-flex justify-content-between">
                            <span>Qty lama</span>
                            <span class="text-mono" id="dupQtyOld">0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Qty baru</span>
                            <span class="text-mono" id="dupQtyNew">0</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Perubahan</span>
                            <span class="text-mono" id="dupQtyDiff">0</span>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-1 py-1 px-2" style="font-size:.78rem;">
                        Lanjutkan untuk <strong>update baris existing</strong> di tabel saldo awal.
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-sm btn-primary" data-duplicate-confirm="1">
                        Lanjutkan update
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let duplicateItemModalInstance = null;
        let pendingOpeningSubmit = null;

        document.addEventListener('DOMContentLoaded', function() {
            initDuplicateItemModal();
            initOpeningAddAjax();
            initDeleteLineAjax();
        });

        function initDuplicateItemModal() {
            const modalEl = document.getElementById('duplicateItemModal');
            if (!modalEl || !window.bootstrap) return;

            duplicateItemModalInstance = new bootstrap.Modal(modalEl);

            const confirmBtn = modalEl.querySelector('[data-duplicate-confirm]');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    if (!pendingOpeningSubmit) return;

                    const {
                        addForm,
                        updateExistingInput
                    } = pendingOpeningSubmit;

                    if (updateExistingInput) {
                        updateExistingInput.value = '1';
                    }

                    performOpeningAjaxSubmit(addForm);
                    pendingOpeningSubmit = null;
                    duplicateItemModalInstance.hide();
                });
            }
        }

        function getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) return meta.getAttribute('content');
            const input = document.querySelector('input[name="_token"]');
            if (input) return input.value;
            return '';
        }

        function formatNumberForDisplay(num) {
            const n = Number(num) || 0;
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }).format(n);
        }

        function initOpeningAddAjax() {
            const addForm = document.getElementById('opening-add-form');
            if (!addForm) return;

            const itemInput = document.querySelector('#opening-item-suggest .js-item-suggest-input');
            const qtyInput = addForm.querySelector('.js-opening-qty');
            const updateExistingInput = addForm.querySelector('input[name="update_existing"]');

            // Fokus awal ke item (backup)
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
                        submitOpeningFormAjax(addForm, existingIds, updateExistingInput, itemInput);
                    }
                });
            }

            // Klik tombol Tambah => juga AJAX
            addForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitOpeningFormAjax(addForm, existingIds, updateExistingInput, itemInput);
            });
        }

        function submitOpeningFormAjax(addForm, existingIds, updateExistingInput, itemInput) {
            const itemIdField = addForm.querySelector('input[name="item_id"]');
            if (!itemIdField || !itemIdField.value) {
                alert('Pilih item terlebih dahulu.');
                if (itemInput) {
                    itemInput.focus();
                }
                return;
            }

            const itemId = itemIdField.value;

            // ✅ CEK QTY FISIK WAJIB DIISI
            const qtyField = addForm.querySelector('input[name="physical_qty"]');
            if (!qtyField || qtyField.value.trim() === '') {
                alert('Isi Qty Fisik terlebih dahulu.');
                if (qtyField) {
                    qtyField.focus();
                    qtyField.select && qtyField.select();
                }
                return;
            }

            const newQtyValue = qtyField.value;

            // Jika item sudah ada di tabel → tampilkan mini modal konfirmasi
            if (existingIds.has(itemId)) {
                // Kalau modal bootstrap tersedia, pakai modal
                if (duplicateItemModalInstance) {
                    const row = document.querySelector('tr[data-item-id="' + itemId + '"]');
                    const oldQty = row ? parseFloat(row.getAttribute('data-physical-qty') || '0') : 0;
                    const newQty = parseFloat(newQtyValue || '0');

                    const diff = newQty - oldQty;

                    const code = row ? (row.getAttribute('data-item-code') || '') : '';
                    const name = row ? (row.getAttribute('data-item-name') || '') : '';

                    const labelEl = document.getElementById('dupItemLabel');
                    const qtyOldEl = document.getElementById('dupQtyOld');
                    const qtyNewEl = document.getElementById('dupQtyNew');
                    const qtyDiffEl = document.getElementById('dupQtyDiff');

                    if (labelEl) {
                        labelEl.textContent = (code ? code : 'Item') + (name ? ' — ' + name : '');
                    }
                    if (qtyOldEl) qtyOldEl.textContent = formatNumberForDisplay(oldQty);
                    if (qtyNewEl) qtyNewEl.textContent = formatNumberForDisplay(newQty);
                    if (qtyDiffEl) {
                        const prefix = diff > 0 ? '+' : '';
                        qtyDiffEl.textContent = prefix + formatNumberForDisplay(diff);
                    }

                    pendingOpeningSubmit = {
                        addForm,
                        updateExistingInput
                    };

                    duplicateItemModalInstance.show();
                    return;
                } else {
                    // Fallback: confirm biasa kalau bootstrap modal nggak tersedia
                    const ok = confirm(
                        'Item ini sudah ada di daftar opname.\n\n' +
                        'Apakah Anda ingin mengupdate baris yang sudah ada (Qty / HPP akan diganti)?'
                    );
                    if (!ok) {
                        return;
                    }
                    if (updateExistingInput) {
                        updateExistingInput.value = '1';
                    }
                }
            } else {
                // Item baru
                if (updateExistingInput) {
                    updateExistingInput.value = '0';
                }
            }

            // Lanjut submit AJAX normal
            performOpeningAjaxSubmit(addForm);
        }

        function performOpeningAjaxSubmit(addForm) {
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
                        // Simple: reload supaya tabel & summary ke-refresh
                        window.location.reload();
                    } else {
                        alert(data.message || 'Gagal menyimpan item.');
                    }
                })
                .catch(() => {
                    alert('Terjadi kesalahan saat menyimpan item.');
                });
        }

        function initDeleteLineAjax() {
            const tableWrap = document.getElementById('opname-lines-table');
            if (!tableWrap) return;

            const urlTemplate = tableWrap.dataset.deleteUrlTemplate;
            if (!urlTemplate) return;

            tableWrap.querySelectorAll('.js-delete-line').forEach(btn => {
                btn.addEventListener('click', function() {
                    const lineId = this.dataset.lineId;
                    if (!lineId) return;

                    if (!confirm('Hapus baris ini dari sesi opname?')) {
                        return;
                    }

                    const url = urlTemplate.replace('__LINE_ID__', lineId);

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
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
