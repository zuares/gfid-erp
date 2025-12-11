{{-- resources/views/inventory/rts_stock_requests/create.blade.php --}}
@extends('layouts.app')

@section('title', 'RTS • Permintaan Stok')

@push('head')
    <style>
        :root {
            --rts-main: rgba(45, 212, 191, 1);
            --rts-main-strong: rgba(15, 118, 110, 1);
            --rts-main-soft: rgba(45, 212, 191, 0.14);
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
            position: relative;
            z-index: 0;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.08) 0,
                    rgba(45, 212, 191, 0.1) 28%,
                    #f9fafb 70%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            box-shadow:
                0 8px 24px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.02);
            position: relative;
            z-index: 1;
        }

        .card-header {
            padding: 1rem 1.25rem .75rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
        }

        .card-body {
            padding: .75rem 1.25rem 1rem;
        }

        .page-title {
            font-size: 1.05rem;
            font-weight: 600;
        }

        .page-subtitle {
            font-size: .85rem;
            color: rgba(100, 116, 139, 1);
        }

        .section-title {
            font-size: .9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
        }

        .badge-warehouse {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .15rem .5rem;
            border-radius: 999px;
            font-size: .75rem;
            border: 1px solid rgba(148, 163, 184, 0.55);
            background: color-mix(in srgb, var(--card) 80%, var(--rts-main-soft));
        }

        .badge-warehouse span.code {
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: .75rem 1.25rem;
            align-items: flex-end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: .25rem;
        }

        .form-group label {
            font-size: .8rem;
            font-weight: 500;
            color: rgba(71, 85, 105, 1);
        }

        .form-control,
        select,
        textarea {
            border-radius: .6rem;
            border: 1px solid rgba(148, 163, 184, 0.6);
            padding: .4rem .6rem;
            font-size: .85rem;
            background: var(--background);
        }

        textarea.form-control {
            min-height: 70px;
            resize: vertical;
        }

        .help-text {
            font-size: .75rem;
            color: rgba(148, 163, 184, 1);
        }

        .text-error {
            color: #ef4444;
            font-size: .75rem;
            margin-top: .1rem;
        }

        .input-error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 1px rgba(239, 68, 68, .2);
        }

        .table-wrap {
            margin-top: .75rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            position: relative;
            overflow: visible;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
        }

        .table thead {
            background: color-mix(in srgb, var(--card) 80%, rgba(15, 23, 42, 0.06));
        }

        .table th,
        .table td {
            padding: .45rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
            vertical-align: middle;
        }

        .table th {
            text-align: left;
            font-weight: 600;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
        }

        .mono {
            font-variant-numeric: tabular-nums;
        }

        .stock-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .15rem .5rem;
            font-size: .75rem;
            border: 1px dashed rgba(148, 163, 184, 0.8);
            color: rgba(15, 23, 42, 0.8);
            white-space: nowrap;
        }

        .stock-pill .label {
            color: rgba(100, 116, 139, 1);
        }

        .stock-pill .value {
            font-weight: 600;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: .8rem;
            font-weight: 500;
            padding: .35rem .9rem;
            cursor: pointer;
            background: var(--rts-main-strong);
            color: white;
        }

        .btn:hover {
            background: rgba(4, 120, 87, 1);
        }

        .btn-outline {
            background: transparent;
            border-color: rgba(148, 163, 184, 0.9);
            color: rgba(51, 65, 85, 1);
        }

        .btn-ghost {
            background: transparent;
            border-color: transparent;
            color: rgba(148, 163, 184, 1);
        }

        .btn-icon {
            padding-inline: .5rem;
        }

        .btn[disabled] {
            opacity: .5;
            cursor: not-allowed;
        }

        .actions-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: .75rem;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .actions-row .left,
        .actions-row .right {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .badge-info {
            border-radius: 999px;
            padding: .15rem .6rem;
            font-size: .75rem;
            background: var(--rts-main-soft);
            color: var(--rts-main-strong);
        }

        .remove-row-btn {
            color: rgba(148, 163, 184, 1);
        }

        .remove-row-btn:hover {
            color: rgba(248, 113, 113, 1);
        }

        /* ========= MODAL SUMMARY STOK (KHUSUS RTS) ========= */

        .rts-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3000;
            padding: 1rem;
        }

        .rts-modal-backdrop.show {
            display: flex;
        }

        .rts-modal-panel {
            background: var(--card);
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, .45);
            box-shadow:
                0 22px 50px rgba(15, 23, 42, 0.40),
                0 0 0 1px rgba(15, 23, 42, 0.05);
            max-width: 520px;
            width: 100%;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transform: translateY(8px);
            opacity: 0;
            transition: opacity .18s ease-out, transform .18s ease-out;
        }

        .rts-modal-backdrop.show .rts-modal-panel {
            opacity: 1;
            transform: translateY(0);
        }

        .rts-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            padding: .75rem .9rem .5rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.4);
        }

        .rts-modal-header h3 {
            font-size: .95rem;
            font-weight: 600;
            margin: 0;
        }

        .rts-modal-close {
            border-radius: 999px;
            border: 1px solid transparent;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            cursor: pointer;
            background: transparent;
            color: rgba(148, 163, 184, 1);
        }

        .rts-modal-close:hover {
            background: rgba(148, 163, 184, 0.16);
        }

        .rts-modal-body {
            padding: .4rem .9rem .8rem;
            font-size: .8rem;
            max-height: calc(80vh - 48px);
            overflow-y: auto;
        }

        .rts-modal-body .item-label {
            font-size: .78rem;
            color: rgba(100, 116, 139, 1);
            margin-bottom: .15rem;
        }

        .rts-modal-body .item-name {
            font-size: .9rem;
            font-weight: 600;
            margin-bottom: .4rem;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .8rem;
        }

        .summary-table th,
        .summary-table td {
            padding: .3rem .3rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
            text-align: left;
        }

        .summary-table th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }

            .table-wrap {
                border-radius: 10px;
                overflow-x: auto;
                overflow-y: visible;
            }

            .table {
                min-width: 700px;
            }

            .rts-modal-backdrop {
                align-items: flex-start;
                padding: 4rem 1rem 1.5rem;
            }

            .rts-modal-panel {
                width: 100%;
                max-width: 100%;
                border-radius: 18px 18px 14px 14px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="card">
            <div class="card-header">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <div class="page-title">RTS • Permintaan Stok</div>
                        <div class="page-subtitle mt-1">
                            Permintaan barang dari Gudang Produksi ke Gudang RTS.
                        </div>
                        @if (isset($prefillRequest))
                            <div class="mt-1 text-xs text-amber-700">
                                Mengedit permintaan tanggal
                                <span class="mono">{{ $prefillRequest->date?->format('d M Y') }}</span>
                                (data lama sudah diisi ulang di bawah).
                            </div>
                        @endif
                    </div>
                    <div class="hidden sm:flex flex-col items-end gap-1">
                        <div class="badge-warehouse">
                            <span class="code">{{ $prdWarehouse->code }}</span>
                            <span>{{ $prdWarehouse->name }}</span>
                        </div>
                        <div style="font-size:.9rem; opacity:.7;">→</div>
                        <div class="badge-warehouse">
                            <span class="code">{{ $rtsWarehouse->code }}</span>
                            <span>{{ $rtsWarehouse->name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <form id="rts-stock-request-form" method="POST" action="{{ route('rts.stock-requests.store') }}">
                @csrf

                <div class="card-body">
                    {{-- Header fields --}}
                    <div class="form-grid mb-3">
                        <div class="form-group" style="grid-column: span 3 / span 3;">
                            <label for="date">Tanggal</label>
                            <input type="date" id="date" name="date"
                                class="form-control @error('date') input-error @enderror"
                                value="{{ old('date', $prefillDate ?? now()->toDateString()) }}">
                            @error('date')
                                <div class="text-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group" style="grid-column: span 4 / span 4;">
                            <label>Gudang Asal (Produksi)</label>
                            <input type="text" class="form-control"
                                value="{{ $prdWarehouse->code }} — {{ $prdWarehouse->name }}" readonly>
                            <input type="hidden" name="source_warehouse_id" id="source_warehouse_id"
                                value="{{ $prdWarehouse->id }}">
                        </div>

                        <div class="form-group" style="grid-column: span 4 / span 4;">
                            <label>Gudang Tujuan (RTS / Packing Online)</label>
                            <input type="text" class="form-control"
                                value="{{ $rtsWarehouse->code }} — {{ $rtsWarehouse->name }}" readonly>
                            <input type="hidden" name="destination_warehouse_id" value="{{ $rtsWarehouse->id }}">
                        </div>

                        <div class="form-group" style="grid-column: span 12 / span 12;">
                            <label for="notes">Catatan (opsional)</label>
                            <textarea id="notes" name="notes" class="form-control"
                                placeholder="Contoh: isi stok untuk promo, flash sale, dsb.">{{ old('notes', isset($prefillRequest) ? $prefillRequest->notes : '') }}</textarea>
                        </div>
                    </div>

                    {{-- Detail lines --}}
                    <div class="mt-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="section-title">Detail Item</div>
                            <span class="badge-info">
                                Pilih FG dari gudang produksi. Boleh request lebih besar dari stok live PRD.
                            </span>
                        </div>

                        <div class="table-wrap mt-2">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width: 32px;">#</th>
                                        <th style="width: 35%;">Item FG</th>
                                        <th style="width: 26%;">Stok Gudang Produksi</th>
                                        <th style="width: 18%;">Qty Request</th>
                                        <th style="width: 80px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="lines-body">
                                    @php
                                        use App\Models\Item;

                                        // Satu sumber kebenaran:
                                        // - kalau ada old('lines') → pakai itu
                                        // - kalau tidak, pakai prefillLines dari controller (kalau ada)
                                        // - fallback: 1 baris kosong
                                        $formLines = old(
                                            'lines',
                                            $prefillLines ?? [['item_id' => null, 'qty_request' => null]],
                                        );
                                    @endphp

                                    @foreach ($formLines as $i => $lineData)
                                        @php
                                            $itemId = $lineData['item_id'] ?? null;

                                            // 1️⃣ coba cari di finishedGoodsItems (mungkin cuma item stok > 0)
                                            $selectedItem = $itemId
                                                ? $finishedGoodsItems->firstWhere('id', $itemId)
                                                : null;

                                            // 2️⃣ fallback: kalau tidak ketemu tapi ada item_id → ambil dari DB
                                            if (!$selectedItem && $itemId) {
                                                $selectedItem = Item::select('id', 'code', 'name')->find($itemId);
                                            }

                                            // 3️⃣ teks yang ditampilkan di input
                                            $displayValue = $selectedItem
                                                ? trim(
                                                    ($selectedItem->code ?? '') . ' — ' . ($selectedItem->name ?? ''),
                                                )
                                                : '';
                                        @endphp

                                        <tr class="line-row" data-row-index="{{ $i }}">
                                            <td class="mono align-top">
                                                <span class="row-number">{{ $i + 1 }}</span>
                                            </td>

                                            {{-- ITEM FG pakai x-item-suggest --}}
                                            <td>
                                                <x-item-suggest :id-name="'lines[' . $i . '][item_id]'" :items="$finishedGoodsItems" type="finished_good"
                                                    :extra-params="['warehouse_id' => $prdWarehouse->id]" placeholder="Kode / nama FG" :display-value="$displayValue"
                                                    :id-value="(string) ($itemId ?? '')" />
                                                @error('lines.' . $i . '.item_id')
                                                    <div class="text-error">{{ $message }}</div>
                                                @enderror
                                            </td>

                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <div class="stock-pill">
                                                        <span class="label">Stok PRD:</span>
                                                        <span class="value mono stock-display" data-available="0">-</span>
                                                        <span class="label">pcs</span>
                                                    </div>
                                                    <button type="button" class="btn-ghost btn-icon btn-show-summary"
                                                        title="Lihat stok item ini di semua gudang"
                                                        data-row-index="{{ $i }}">
                                                        🔍
                                                    </button>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="1"
                                                    name="lines[{{ $i }}][qty_request]"
                                                    class="form-control js-next-focus qty-input @error('lines.' . $i . '.qty_request') input-error @enderror"
                                                    data-row-index="{{ $i }}"
                                                    value="{{ $lineData['qty_request'] ?? '' }}">
                                                <div class="text-error qty-warning" style="display:none;"></div>
                                                @error('lines.' . $i . '.qty_request')
                                                    <div class="text-error">{{ $message }}</div>
                                                @enderror
                                            </td>
                                            <td class="text-right">
                                                <button type="button" class="btn-ghost remove-row-btn"
                                                    data-row-index="{{ $i }}" title="Hapus baris">
                                                    ✕
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="actions-row">
                            <div class="left">
                                <button type="button" class="btn-outline" id="add-line-btn">
                                    + Tambah baris
                                </button>
                            </div>
                            <div class="right">
                                <button type="submit" class="btn">
                                    Simpan & Kirim ke Gudang Produksi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Modal summary stok semua gudang --}}
        <div class="rts-modal-backdrop" id="stock-summary-backdrop">
            <div class="rts-modal-panel">
                <div class="rts-modal-header">
                    <h3>Summary Stok per Gudang</h3>
                    <button type="button" class="rts-modal-close" id="stock-summary-close">×</button>
                </div>
                <div class="rts-modal-body">
                    <div id="stock-summary-content">
                        <div class="help-text">
                            Klik ikon 🔍 di baris item untuk lihat posisi stok di semua gudang.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@php
    // Template x-item-suggest untuk baris baru (index diganti "__INDEX__" nanti di JS)
    $itemSuggestTemplate = view('components.item-suggest', [
        'idName' => 'lines[__INDEX__][item_id]',
        'items' => $finishedGoodsItems,
        'type' => 'finished_good',
        'extraParams' => ['warehouse_id' => $prdWarehouse->id],
        'placeholder' => 'Kode / nama FG',
        'displayValue' => '',
        'idValue' => '',
    ])->render();
@endphp
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            (function() {
                const sourceWarehouseId = {{ $prdWarehouse->id }};
                const availableUrl = @json(route('api.stock.available'));
                const summaryUrl = @json(route('api.stock.summary'));
                const itemSuggestTemplate = @json($itemSuggestTemplate);

                const linesBody = document.getElementById('lines-body');
                const addLineBtn = document.getElementById('add-line-btn');

                const backdrop = document.getElementById('stock-summary-backdrop');
                const closeBtn = document.getElementById('stock-summary-close');
                const summaryContent = document.getElementById('stock-summary-content');

                if (!linesBody) {
                    console.error('RTS Stock Request: #lines-body tidak ditemukan');
                    return;
                }

                let currentIndex = (function() {
                    const lastRow = linesBody.querySelector('tr.line-row:last-child');
                    return lastRow ? parseInt(lastRow.getAttribute('data-row-index')) + 1 : 0;
                })();

                function createLineRow(index) {
                    const tr = document.createElement('tr');
                    tr.classList.add('line-row');
                    tr.setAttribute('data-row-index', index);

                    const itemSuggestHtml = itemSuggestTemplate.replace(/__INDEX__/g, index);

                    tr.innerHTML = `
                        <td class="mono align-top">
                            <span class="row-number">${index + 1}</span>
                        </td>
                        <td>
                            ${itemSuggestHtml}
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="stock-pill">
                                    <span class="label">Stok PRD:</span>
                                    <span class="value mono stock-display" data-available="0">-</span>
                                    <span class="label">pcs</span>
                                </div>
                                <button type="button"
                                        class="btn-ghost btn-icon btn-show-summary"
                                        title="Lihat stok item ini di semua gudang"
                                        data-row-index="${index}">
                                    🔍
                                </button>
                            </div>
                        </td>
                        <td>
                            <input type="number"
                                   min="0"
                                   step="1"
                                   name="lines[${index}][qty_request]"
                                   class="form-control qty-input js-next-focus"
                                   data-row-index="${index}">
                            <div class="text-error qty-warning" style="display:none;"></div>
                        </td>
                        <td class="text-right">
                            <button type="button"
                                    class="btn-ghost remove-row-btn"
                                    data-row-index="${index}"
                                    title="Hapus baris">
                                ✕
                            </button>
                        </td>
                    `;

                    if (window.initItemSuggestInputs) {
                        window.initItemSuggestInputs(tr);
                    }

                    return tr;
                }

                function renumberRows() {
                    const rows = linesBody.querySelectorAll('tr.line-row');
                    rows.forEach((row, idx) => {
                        const num = row.querySelector('.row-number');
                        if (num) num.textContent = idx + 1;
                    });
                }

                function handleAddLine() {
                    const row = createLineRow(currentIndex++);
                    linesBody.appendChild(row);
                }

                function findRowByIndex(rowIndex) {
                    return linesBody.querySelector(`tr.line-row[data-row-index="${rowIndex}"]`);
                }

                async function fetchAvailableStock(rowIndex) {
                    const row = findRowByIndex(rowIndex);
                    if (!row) return;

                    const hiddenId = row.querySelector('.js-item-suggest-id');
                    const itemId = hiddenId ? hiddenId.value : '';

                    const stockSpan = row.querySelector('.stock-display');
                    const warningEl = row.querySelector('.qty-warning');
                    const qtyInput = row.querySelector('.qty-input');

                    if (!stockSpan) return;

                    if (!itemId) {
                        stockSpan.textContent = '-';
                        stockSpan.dataset.available = '0';
                        if (warningEl) warningEl.style.display = 'none';
                        qtyInput && qtyInput.classList.remove('input-error');
                        return;
                    }

                    stockSpan.textContent = '…';
                    stockSpan.dataset.available = '0';
                    if (warningEl) warningEl.style.display = 'none';
                    qtyInput && qtyInput.classList.remove('input-error');

                    try {
                        const url = new URL(availableUrl, window.location.origin);
                        url.searchParams.set('warehouse_id', String(sourceWarehouseId));
                        url.searchParams.set('item_id', String(itemId));

                        const res = await fetch(url.toString(), {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        if (!res.ok) {
                            throw new Error('Gagal mengambil stok');
                        }

                        const data = await res.json();
                        const available = typeof data.available === 'number' ?
                            data.available :
                            parseFloat(data.available || 0);

                        stockSpan.textContent = isNaN(available) ? '-' : available;
                        stockSpan.dataset.available = String(available);

                        if (qtyInput && qtyInput.value) {
                            validateQtyAgainstStock(qtyInput, available);
                        }

                    } catch (e) {
                        console.error(e);
                        stockSpan.textContent = 'ERR';
                        stockSpan.dataset.available = '0';
                        if (warningEl) {
                            warningEl.style.display = 'block';
                            warningEl.textContent = 'Gagal mengambil stok, coba lagi.';
                        }
                    }
                }

                // Sekarang hanya info, tidak kasih error merah
                function validateQtyAgainstStock(inputEl, available) {
                    const row = inputEl.closest('tr.line-row');
                    if (!row) return;

                    const warningEl = row.querySelector('.qty-warning');
                    const stockSpan = row.querySelector('.stock-display');

                    const value = parseFloat(inputEl.value || '0');
                    const avail = typeof available === 'number' ?
                        available :
                        parseFloat(stockSpan?.dataset.available || '0');

                    inputEl.classList.remove('input-error');
                    if (warningEl) {
                        warningEl.style.display = 'none';
                        warningEl.textContent = '';
                    }
                }

                async function showStockSummary(rowIndex) {
                    if (!backdrop || !summaryContent) return;

                    const row = findRowByIndex(rowIndex);
                    let itemId = '';

                    if (row) {
                        const hiddenId = row.querySelector('.js-item-suggest-id');
                        itemId = hiddenId ? hiddenId.value : '';
                    }

                    backdrop.classList.add('show');

                    if (!itemId) {
                        summaryContent.innerHTML = `
                            <div class="help-text">
                                Pilih item dulu di baris ini, lalu klik ikon 🔍 lagi.
                            </div>
                        `;
                        return;
                    }

                    summaryContent.innerHTML = '<div class="help-text">Mengambil data stok...</div>';

                    try {
                        const url = new URL(summaryUrl, window.location.origin);
                        url.searchParams.set('item_id', String(itemId));

                        const res = await fetch(url.toString(), {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        if (!res.ok) {
                            throw new Error('Gagal mengambil summary stok');
                        }

                        const data = await res.json();
                        const item = data.item || {};
                        const warehouses = Array.isArray(data.warehouses) ? data.warehouses : [];

                        if (!warehouses.length) {
                            summaryContent.innerHTML = `
                                <div class="help-text">
                                    Belum ada data stok untuk item ini.
                                </div>
                            `;
                            return;
                        }

                        const itemLabel = (item.code || item.name) ?
                            `${item.code ?? ''} ${item.name ? '— ' + item.name : ''}` :
                            `Item ID: ${itemId}`;

                        const rowsHtml = warehouses.map(w => `
                            <tr>
                                <td class="mono">${w.code ?? ''}</td>
                                <td>${w.name ?? ''}</td>
                                <td class="mono">${w.on_hand ?? 0}</td>
                                <td class="mono">${w.reserved ?? 0}</td>
                                <td class="mono">${w.available ?? 0}</td>
                            </tr>
                        `).join('');

                        summaryContent.innerHTML = `
                            <div class="item-label">Item</div>
                            <div class="item-name">${itemLabel}</div>
                            <table class="summary-table">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Gudang</th>
                                        <th>On Hand</th>
                                        <th>Reserved</th>
                                        <th>Available</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${rowsHtml}
                                </tbody>
                            </table>
                        `;
                    } catch (e) {
                        console.error(e);
                        summaryContent.innerHTML = `
                            <div class="text-error">
                                Gagal mengambil summary stok. Coba lagi beberapa saat.
                            </div>
                        `;
                    }
                }

                function attachGlobalListeners() {
                    addLineBtn?.addEventListener('click', handleAddLine);

                    // Change events (item & qty)
                    linesBody.addEventListener('change', function(e) {
                        const target = e.target;

                        if (target.classList.contains('js-item-suggest-id')) {
                            const row = target.closest('tr.line-row');
                            if (row) {
                                const rowIndex = row.getAttribute('data-row-index');
                                fetchAvailableStock(rowIndex);
                            }
                        }

                        if (target.classList.contains('qty-input')) {
                            validateQtyAgainstStock(target);
                        }
                    });

                    // Click events (hapus baris, summary stok)
                    linesBody.addEventListener('click', function(e) {
                        const removeBtn = e.target.closest('.remove-row-btn');
                        const summaryBtn = e.target.closest('.btn-show-summary');

                        if (removeBtn) {
                            const rowIndex = removeBtn.getAttribute('data-row-index');
                            const row = findRowByIndex(rowIndex);
                            if (row) {
                                if (linesBody.querySelectorAll('tr.line-row').length <= 1) {
                                    // Kalau cuma 1 baris, jangan dihapus, cukup clear
                                    const wrap = row.querySelector('.item-suggest-wrap');
                                    if (wrap) {
                                        const input = wrap.querySelector('.js-item-suggest-input');
                                        const hiddenId = wrap.querySelector('.js-item-suggest-id');
                                        const hiddenCat = wrap.querySelector(
                                            '.js-item-suggest-category');

                                        if (input) input.value = '';
                                        if (hiddenId) hiddenId.value = '';
                                        if (hiddenCat) hiddenCat.value = '';
                                    }

                                    const qtyInput = row.querySelector('.qty-input');
                                    if (qtyInput) qtyInput.value = '';

                                    const stockSpan = row.querySelector('.stock-display');
                                    if (stockSpan) {
                                        stockSpan.textContent = '-';
                                        stockSpan.dataset.available = '0';
                                    }

                                    const warn = row.querySelector('.qty-warning');
                                    if (warn) warn.style.display = 'none';

                                    return;
                                }

                                row.remove();
                                renumberRows();
                            }
                        }

                        if (summaryBtn) {
                            const rowIndex = summaryBtn.getAttribute('data-row-index');
                            showStockSummary(rowIndex);
                        }
                    });

                    // Modal close (klik X / klik backdrop)
                    closeBtn?.addEventListener('click', () => {
                        backdrop?.classList.remove('show');
                    });

                    backdrop?.addEventListener('click', (e) => {
                        if (e.target === backdrop) {
                            backdrop.classList.remove('show');
                        }
                    });
                }

                // 🔹 1) Pasang semua listener dulu
                attachGlobalListeners();

                // 🔹 2) Init item-suggest untuk seluruh tbody
                if (window.initItemSuggestInputs) {
                    window.initItemSuggestInputs(linesBody);
                }

                // 🔹 3) Fetch stok untuk semua baris yang sudah punya item (old()/prefill)
                (function initExistingStocks() {
                    const existingHiddenIds = linesBody.querySelectorAll('.js-item-suggest-id');
                    existingHiddenIds.forEach(hidden => {
                        if (hidden.value) {
                            const row = hidden.closest('tr.line-row');
                            if (row) {
                                const rowIndex = row.getAttribute('data-row-index');
                                fetchAvailableStock(rowIndex);
                            }
                        }
                    });
                })();
            })();
        });
    </script>
@endpush
