@extends('layouts.app')

@section('title', 'Shipment Keluar • Scan Barang')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 26%,
                    #f9fafb 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.10),
                0 0 0 1px rgba(148, 163, 184, 0.12);
        }

        .scan-input-wrap {
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.55);
            padding: 0.75rem 1rem;
            background: color-mix(in srgb, var(--card) 88%, rgba(59, 130, 246, 0.1));
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .scan-input-wrap .form-control {
            border: none;
            box-shadow: none !important;
            background: transparent;
            font-size: 1.05rem;
            font-weight: 500;
        }

        .scan-input-wrap .form-control:focus {
            outline: none;
        }

        .badge-pill-soft {
            border-radius: 999px;
            padding: .25rem .7rem;
            font-size: .75rem;
            border: 1px solid rgba(148, 163, 184, 0.55);
            background: rgba(15, 23, 42, 0.02);
        }

        .table-lines thead th {
            border-bottom-width: 1px;
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7280;
            background: rgba(15, 23, 42, 0.02);
        }

        .table-lines tbody td {
            vertical-align: middle;
            border-top-color: rgba(148, 163, 184, 0.2);
        }

        .qty-input {
            max-width: 80px;
            text-align: right;
        }

        .row-highlight {
            animation: rowHighlight 0.4s ease-out;
        }

        @keyframes rowHighlight {
            from {
                background-color: rgba(59, 130, 246, 0.15);
            }

            to {
                background-color: transparent;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-1">
                    Shipment Keluar
                </h1>
                <p class="text-muted mb-0">
                    Scan barcode / input kode barang, sistem otomatis menambahkan ke daftar keluar.
                </p>
            </div>
            <a href="{{ route('shipments.index') }}" class="btn btn-sm btn-outline-secondary">
                &larr; Kembali
            </a>
        </div>

        {{-- Alert error --}}
        @if ($errors->any())
            <div class="alert alert-danger small">
                <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Flash status --}}
        @if (session('status') === 'error')
            <div class="alert alert-danger">
                {{ session('message') }}
            </div>
        @endif

        <form action="{{ route('shipments.store') }}" method="POST" id="shipment-form" autocomplete="off">
            @csrf

            <div class="card card-main mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Tanggal --}}
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-1">
                                Tanggal
                            </label>
                            <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                                value="{{ old('date', now()->toDateString()) }}">
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Warehouse --}}
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-1">
                                Gudang
                            </label>
                            <select name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror">
                                <option value="">Pilih gudang…</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}" @selected(old('warehouse_id', $selectedWarehouseId ?? null) == $wh->id)>
                                        {{ $wh->code }} — {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Customer (opsional) --}}
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-1">
                                Customer (opsional)
                            </label>
                            <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
                                <option value="">- Tidak diisi -</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Store (opsional) --}}
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-1">
                                Channel / Store
                            </label>
                            <select name="store_id" class="form-select @error('store_id') is-invalid @enderror">
                                <option value="">- Tidak diisi -</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}" @selected(old('store_id') == $store->id)>
                                        {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('store_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Notes --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-1">
                                Catatan (opsional)
                            </label>
                            <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror"
                                placeholder="Contoh: Kirim ke gudang cabang, atau catatan khusus lain…">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Scan area --}}
            <div class="card card-main mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="text-uppercase small fw-semibold text-muted mb-1">
                                Scan / Input Kode Barang
                            </div>
                            <div class="small text-muted">
                                Fokus otomatis ke input ini. Tekan <code>Enter</code> untuk menambah 1 qty.
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <span class="badge-pill-soft">
                                Total item <span id="total-lines">0</span> baris
                            </span>
                            <span class="badge-pill-soft">
                                Total qty <span id="total-qty">0</span>
                            </span>
                        </div>
                    </div>

                    <div class="scan-input-wrap mb-2">
                        <span class="text-muted">
                            <i class="bi bi-upc-scan"></i>
                        </span>
                        <input type="text" id="scan-input" class="form-control"
                            placeholder="Scan barcode / ketik kode barang lalu Enter…">
                        <button type="button" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex"
                            id="btn-refocus">
                            Fokus ke Scan
                        </button>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">
                            Hint: ulangi scan barang yang sama untuk menambah qty.
                        </small>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btn-clear-lines">
                            Hapus semua baris
                        </button>
                    </div>

                    {{-- Tabel lines --}}
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 table-lines">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th style="width: 140px;">Kode</th>
                                    <th>Nama Barang</th>
                                    <th style="width: 110px;" class="text-end">Qty</th>
                                    <th style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody id="lines-body">
                                {{-- Row akan diinject via JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Footer submit --}}
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Pastikan daftar item sudah sesuai sebelum simpan. Shipment akan langsung mengurangi stok gudang.
                </div>
                <button type="submit" class="btn btn-primary px-4" id="submit-btn">
                    Simpan Shipment
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const scanInput = document.getElementById('scan-input');
            const linesBody = document.getElementById('lines-body');
            const totalLinesEl = document.getElementById('total-lines');
            const totalQtyEl = document.getElementById('total-qty');
            const btnRefocus = document.getElementById('btn-refocus');
            const btnClear = document.getElementById('btn-clear-lines');
            const form = document.getElementById('shipment-form');

            // Fokus awal
            focusScan();

            // Jaga fokus tetap di input scan
            btnRefocus?.addEventListener('click', function() {
                focusScan(true);
            });

            document.addEventListener('click', function(e) {
                // kalau klik bukan di input qty, jaga fokus scan
                const isQtyInput = e.target.classList.contains('qty-input');
                const isScan = e.target === scanInput;
                if (!isQtyInput && !isScan) {
                    // kecilin delay supaya nggak ganggu klik tombol
                    setTimeout(focusScan, 120);
                }
            });

            function focusScan(select = false) {
                if (!scanInput) return;
                scanInput.focus();
                if (select) {
                    scanInput.select();
                }
            }

            // Enter pada scan input
            scanInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const raw = scanInput.value.trim();
                    if (!raw) {
                        return;
                    }
                    addByScan(raw);
                }
            });

            // Clear semua baris
            btnClear?.addEventListener('click', function() {
                if (!linesBody.children.length) return;
                if (!confirm('Hapus semua baris shipment?')) return;
                linesBody.innerHTML = '';
                recalcSummary();
                focusScan(true);
            });

            // Tambah / update baris dari hasil scan
            async function addByScan(input) {
                try {
                    const item = await findItemByInput(input);

                    if (!item) {
                        toastError('Item tidak ditemukan untuk: ' + input);
                        scanInput.value = '';
                        focusScan(true);
                        return;
                    }

                    // Cek apakah sudah ada baris dengan item_id ini
                    const existingRow = linesBody.querySelector(
                        'tr[data-item-id="' + item.id + '"]'
                    );

                    if (existingRow) {
                        const qtyInput = existingRow.querySelector('.qty-input');
                        const current = parseInt(qtyInput.value || '0', 10);
                        qtyInput.value = current + 1;
                        highlightRow(existingRow);
                    } else {
                        appendRow(item, 1);
                    }

                    recalcSummary();
                    scanInput.value = '';
                    focusScan(true);
                } catch (err) {
                    console.error(err);
                    toastError('Gagal mencari item. Coba lagi.');
                }
            }

            // Panggil API items (sesuaikan URL kalau beda)
            async function findItemByInput(q) {
                const url = '/api/items?q=' + encodeURIComponent(q) + '&limit=1';
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                    }
                });

                if (!res.ok) {
                    throw new Error('HTTP ' + res.status);
                }

                const data = await res.json();

                if (Array.isArray(data) && data.length > 0) {
                    // asumsi shape: {id, code, name}
                    return data[0];
                }

                // Kalau API balikin dengan key "data"
                if (Array.isArray(data.data) && data.data.length > 0) {
                    return data.data[0];
                }

                return null;
            }

            // Tambah baris baru
            function appendRow(item, qty) {
                const index = linesBody.children.length;

                const tr = document.createElement('tr');
                tr.dataset.itemId = item.id;

                tr.innerHTML = `
                    <td class="text-muted small">${index + 1}</td>
                    <td>
                        <div class="fw-semibold">${item.code ?? ''}</div>
                    </td>
                    <td>
                        <div class="small text-truncate" style="max-width: 340px;">
                            ${escapeHtml(item.name ?? '')}
                        </div>
                    </td>
                    <td class="text-end">
                        <input type="number" name="lines[${index}][qty]"
                               class="form-control form-control-sm qty-input"
                               min="1" value="${qty}">
                    </td>
                    <td class="text-center">
                        <button type="button"
                                class="btn btn-sm btn-outline-danger btn-remove-line"
                                title="Hapus baris">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </td>

                    <input type="hidden" name="lines[${index}][item_id]" value="${item.id}">
                `;

                linesBody.appendChild(tr);
                highlightRow(tr);
            }

            // Delegasi: hapus baris
            linesBody.addEventListener('click', function(e) {
                if (e.target.closest('.btn-remove-line')) {
                    const tr = e.target.closest('tr');
                    tr?.remove();
                    renumberRows();
                    recalcSummary();
                }
            });

            // Recalc qty summary saat qty diubah manual
            linesBody.addEventListener('input', function(e) {
                if (e.target.classList.contains('qty-input')) {
                    recalcSummary();
                }
            });

            // Renumber no urut + name index kalau ada penghapusan baris
            function renumberRows() {
                const rows = Array.from(linesBody.children);
                rows.forEach((tr, idx) => {
                    const noCell = tr.querySelector('td:first-child');
                    if (noCell) {
                        noCell.textContent = idx + 1;
                    }

                    const qtyInput = tr.querySelector('.qty-input');
                    const hiddenItem = tr.querySelector('input[type="hidden"][name*="[item_id]"]');

                    if (qtyInput) {
                        qtyInput.name = `lines[${idx}][qty]`;
                    }
                    if (hiddenItem) {
                        hiddenItem.name = `lines[${idx}][item_id]`;
                    }
                });
            }

            function recalcSummary() {
                const rows = Array.from(linesBody.children);
                const totalLines = rows.length;
                let totalQty = 0;

                rows.forEach((tr) => {
                    const qtyInput = tr.querySelector('.qty-input');
                    const val = parseInt(qtyInput?.value || '0', 10);
                    if (!isNaN(val)) {
                        totalQty += val;
                    }
                });

                totalLinesEl.textContent = totalLines;
                totalQtyEl.textContent = totalQty;
            }

            function highlightRow(tr) {
                tr.classList.remove('row-highlight');
                void tr.offsetWidth; // trigger reflow
                tr.classList.add('row-highlight');
            }

            function toastError(message) {
                // versi simple: pakai alert kecil di atas input
                // Kalau kamu pakai toast JS sendiri, tinggal ganti fungsi ini.
                console.warn(message);
                // bisa juga pakai alert:
                // alert(message);
            }

            function escapeHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // Pastikan ada minimal 1 baris saat submit
            form.addEventListener('submit', function(e) {
                if (!linesBody.children.length) {
                    e.preventDefault();
                    toastError('Tambah minimal 1 item sebelum menyimpan Shipment.');
                    focusScan(true);
                }
            });
        });
    </script>
@endpush
