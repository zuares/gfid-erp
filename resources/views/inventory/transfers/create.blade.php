{{-- resources/views/inventory/transfers/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Transfer Stok Baru')

@push('head')
    <style>
        :root {
            --shp-accent: #334155;
            --shp-accent-2: #1f2937;
            --shp-accent-bg: rgba(148, 163, 184, .08);
            --shp-accent-ring: rgba(148, 163, 184, .18);
        }

        .shp-wrap {
            max-width: 1040px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        .shp-topbar {
            position: sticky;
            top: 0;
            z-index: 300;
            display: flex;
            align-items: center;
            gap: .45rem;
            padding: .45rem .75rem;
            background: var(--card, #fff);
            border-bottom: 1px solid rgba(148, 163, 184, .18);
            margin-bottom: 1rem;
        }

        .shp-topbar-code {
            font-weight: 900;
            font-size: 1.05rem;
            letter-spacing: .04em;
        }

        .shp-card {
            background: var(--card, #fff);
            border-radius: 0;
            border: 1px solid rgba(148, 163, 184, .16);
            box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
            padding: 1.1rem 1.25rem;
            margin-bottom: 1rem;
        }

        .btn-shp-submit {
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 800;
            text-transform: uppercase;
            padding: .42rem 1.35rem;
            background: #334155;
            color: #fff;
            border: 1px solid #334155;
            transition: background .12s;
        }

        .btn-shp-submit:hover {
            background: #1f2937;
            border-color: #1f2937;
            color: #fff;
        }

        .btn-shp-outline {
            border-radius: 999px;
            font-size: .77rem;
            text-transform: uppercase;
            padding: .32rem 1rem;
            border: 1px solid rgba(148, 163, 184, .5);
            background: transparent;
            color: #6b7280;
            text-decoration: none;
            transition: background .12s, color .12s;
        }

        .btn-shp-outline:hover {
            background: rgba(226, 232, 240, .7);
            color: #374151;
        }

        @media (max-width: 768px) {
            .shp-wrap {
                padding: .5rem .5rem 5rem;
            }
            .shp-topbar {
                padding: .5rem;
                gap: .38rem;
            }
            .shp-topbar-code {
                flex: 1 1 auto;
                min-width: 145px;
                font-size: 1.05rem;
            }
            .shp-topbar-spacer {
                display: none !important;
            }
            .btn-shp-outline {
                min-height: 38px;
                font-size: .82rem !important;
            }
            .shp-card {
                padding: 1rem;
            }
            .table-responsive {
                margin: 0 -1rem;
                padding: 0 1rem;
            }
        }
    </style>
@endpush

@section('content')
    {{-- HEADER --}}
    <div class="shp-topbar">
        <span class="shp-topbar-code">Transfer Stok Baru</span>
        <div class="shp-topbar-spacer"></div>
        <a href="{{ route('inventory.transfers.index') }}" class="btn-shp-outline" style="text-decoration: none;">
            Daftar Transfer
        </a>
    </div>

    <div class="shp-wrap">

        {{-- ERROR --}}
        @if ($errors->any())
            <div class="alert alert-danger" style="border-radius: 12px;">
                <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('inventory.transfers.store') }}" method="POST" id="form-transfer">
            @csrf

            @php
                $isAdmin = auth()->user()->role === 'admin';
                $rtsId = $warehouses->firstWhere('code', 'WH-RTS')?->id;
                $prdId = $warehouses->firstWhere('code', 'WH-PRD')?->id;
                
                // For admin, from_warehouse_id is forced to WH-RTS and to_warehouse_id defaults to WH-PRD
                $defaultFrom = $isAdmin ? $rtsId : old('from_warehouse_id');
                $defaultTo = old('to_warehouse_id', $isAdmin ? $prdId : null);
            @endphp

            {{-- HEADER TRANSFER --}}
            <div class="shp-card">
                <h5 class="mb-3" style="font-size: .9rem; font-weight: 800; color: #475569; text-transform: uppercase;">Informasi Transfer</h5>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label text-muted fw-bold" style="font-size: .8rem">Tanggal</label>
                        <input type="date" name="date" class="form-control form-control-sm"
                            value="{{ old('date', now()->toDateString()) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-muted fw-bold" style="font-size: .8rem">Gudang Asal</label>
                        <select name="from_warehouse_id" class="form-select form-select-sm" required
                            {{ $isAdmin ? 'disabled' : '' }}>
                            <option value="">-- Pilih Gudang Asal --</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected($defaultFrom == $wh->id)>
                                    {{ $wh->code }} - {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($isAdmin)
                            <input type="hidden" name="from_warehouse_id" value="{{ $rtsId }}">
                        @endif
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-muted fw-bold" style="font-size: .8rem">Gudang Tujuan</label>
                        <select name="to_warehouse_id" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Gudang Tujuan --</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected($defaultTo == $wh->id)>
                                    {{ $wh->code }} - {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label text-muted fw-bold" style="font-size: .8rem">Catatan</label>
                        <textarea name="notes" rows="2" class="form-control form-control-sm">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- DETAIL ITEM --}}
            <div class="shp-card p-0" style="overflow: hidden;">
                <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                    <span style="font-size: .9rem; font-weight: 800; color: #475569; text-transform: uppercase;">Detail Item</span>
                    <button type="button" class="btn-shp-outline" id="btn-add-line" style="font-size: .7rem; padding: .2rem .75rem;">
                        + Tambah Baris
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead style="background: rgba(248,250,252,.98);">
                            <tr>
                                <th style="width: 40%; font-size: .75rem; text-transform: uppercase; color: #9ca3af; padding: .75rem;">Item</th>
                                <th style="width: 20%; font-size: .75rem; text-transform: uppercase; color: #9ca3af; padding: .75rem;">Qty</th>
                                <th style="width: 35%; font-size: .75rem; text-transform: uppercase; color: #9ca3af; padding: .75rem;">Catatan</th>
                                <th style="width: 5%; padding: .75rem;"></th>
                            </tr>
                        </thead>
                        <tbody id="transfer-lines-body">
                            @php
                                $oldItemIds = old('item_id', []);
                                $oldQtys = old('qty', []);
                                $oldLineNotes = old('line_notes', []);
                            @endphp

                            @if (count($oldItemIds))
                                @foreach ($oldItemIds as $i => $itemId)
                                    <tr>
                                        <td class="p-2">
                                            @php
                                                $displayItem = '';
                                                if (!empty($itemId)) {
                                                    $found = collect($items)->firstWhere('id', $itemId);
                                                    if ($found) {
                                                        $displayItem = ($found->name ?? '') . (($found->name && $found->code) ? ' — ' : '') . ($found->code ?? '');
                                                    }
                                                }
                                            @endphp
                                            <x-item-suggest-input idName="item_id[]" :idValue="$itemId" :displayValue="$displayItem" placeholder="Ketik nama/kode item..." />
                                        </td>
                                        <td class="p-2">
                                            <x-number-input name="qty[]" mode="decimal" decimals="2" :value="$oldQtys[$i] ?? 0" class="text-end fw-bold" style="font-size: 1.1rem" />
                                        </td>
                                        <td class="p-2">
                                            <input type="text" name="line_notes[]"
                                                class="form-control form-control-sm"
                                                value="{{ $oldLineNotes[$i] ?? '' }}">
                                        </td>
                                        <td class="text-center p-2">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger btn-remove-line" style="padding: .15rem .45rem;">
                                                &times;
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                {{-- default 1 baris --}}
                                <tr>
                                    <td class="p-2">
                                        <x-item-suggest-input idName="item_id[]" placeholder="Ketik nama/kode item..." />
                                    </td>
                                    <td class="p-2">
                                        <x-number-input name="qty[]" mode="decimal" decimals="2" value="0" class="text-end fw-bold" style="font-size: 1.1rem" />
                                    </td>
                                    <td class="p-2">
                                        <input type="text" name="line_notes[]" class="form-control form-control-sm">
                                    </td>
                                    <td class="text-center p-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line" style="padding: .15rem .45rem;">
                                            &times;
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ACTIONS --}}
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('inventory.transfers.index') }}" class="btn-shp-outline" style="padding: .42rem 1.35rem;">
                    Batal
                </a>
                <button type="button" id="btn-show-confirm" class="btn-shp-submit">
                    Simpan & Jalankan Transfer
                </button>
            </div>
        </form>
    </div>

    {{-- MODAL KONFIRMASI --}}
    <div class="modal fade" id="confirmTransferModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" style="color: var(--shp-accent-2);">Konfirmasi Transfer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3" style="font-size: .9rem;">Anda akan mentransfer barang ke <strong id="confirm-to-warehouse" class="text-dark"></strong>. Berikut adalah rinciannya:</p>
                    
                    <div class="table-responsive border rounded-3" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0" style="font-size: .85rem;">
                            <thead style="background: #f8fafc; position: sticky; top: 0;">
                                <tr>
                                    <th class="py-2 px-3 text-muted text-uppercase" style="font-size: .7rem;">Item</th>
                                    <th class="py-2 px-3 text-muted text-uppercase text-end" style="font-size: .7rem; width: 80px;">Qty</th>
                                </tr>
                            </thead>
                            <tbody id="confirm-items-body">
                                {{-- Diisi via JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn-shp-outline" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn-shp-submit" id="btn-confirm-submit">Ya, Jalankan Transfer</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const body = document.getElementById('transfer-lines-body');
                const btnAdd = document.getElementById('btn-add-line');

                btnAdd?.addEventListener('click', function() {
                    const rows = body.querySelectorAll('tr');
                    const row = rows[0]; // Copy from the first row template
                    
                    const clone = row.cloneNode(true);
                    
                    // Reset item-suggest-input state in cloned row
                    const wrap = clone.querySelector('.item-suggest-wrap');
                    if (wrap) {
                        wrap.removeAttribute('data-suggest-inited');
                        const uid = 'item-suggest-' + Math.random().toString(36).substring(2, 8);
                        wrap.dataset.uid = uid;
                        const input = wrap.querySelector('.js-item-suggest-input');
                        if (input) {
                            input.id = uid;
                            input.value = '';
                        }
                        const hiddenId = wrap.querySelector('.js-item-suggest-id');
                        if (hiddenId) hiddenId.value = '';
                    }

                    // Clear inputs in cloned row
                    clone.querySelectorAll('.js-number-input').forEach(i => {
                        i.removeAttribute('data-number-inited');
                        i.value = '0';
                    });
                    clone.querySelectorAll('input[name="line_notes[]"]').forEach(i => i.value = '');

                    body.appendChild(clone);
                    
                    if (window.initItemSuggestInputs) {
                        window.initItemSuggestInputs();
                    }
                    if (window.initNumberInputs) {
                        window.initNumberInputs();
                    }
                });

                body.addEventListener('click', function(e) {
                    if (e.target.classList.contains('btn-remove-line')) {
                        const rows = body.querySelectorAll('tr');
                        if (rows.length > 1) {
                            const tr = e.target.closest('tr');
                            tr.remove();
                        } else {
                            const tr = e.target.closest('tr');
                            tr.querySelectorAll('.js-number-input').forEach(i => i.value = '0');
                            tr.querySelectorAll('input[name="line_notes[]"]').forEach(i => i.value = '');
                            
                            // Reset suggest
                            const input = tr.querySelector('.js-item-suggest-input');
                            if (input) input.value = '';
                            const hiddenId = tr.querySelector('.js-item-suggest-id');
                            if (hiddenId) hiddenId.value = '';
                        }
                    }
                });

                // KONFIRMASI TRANSFER
                const form = document.getElementById('form-transfer');
                const btnShowConfirm = document.getElementById('btn-show-confirm');
                const btnConfirmSubmit = document.getElementById('btn-confirm-submit');
                
                btnShowConfirm?.addEventListener('click', function() {
                    // Validasi HTML5 dasar
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }

                    // Ambil Gudang Tujuan
                    const toSelect = document.querySelector('select[name="to_warehouse_id"]');
                    const toText = toSelect.options[toSelect.selectedIndex]?.text || '-';
                    document.getElementById('confirm-to-warehouse').textContent = toText;

                    // Kumpulkan Item & Qty
                    const confirmBody = document.getElementById('confirm-items-body');
                    confirmBody.innerHTML = '';
                    
                    const rows = body.querySelectorAll('tr');
                    let hasItem = false;

                    rows.forEach(row => {
                        const itemInput = row.querySelector('.js-item-suggest-input');
                        const qtyInput = row.querySelector('.js-number-input');
                        
                        if (itemInput && qtyInput) {
                            const itemName = itemInput.value.trim();
                            const qty = parseFloat(qtyInput.value.replace(/,/g, '.') || 0);

                            if (itemName && qty > 0) {
                                hasItem = true;
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td class="py-2 px-3 align-middle fw-medium">${itemName}</td>
                                    <td class="py-2 px-3 align-middle text-end fw-bold" style="color: var(--shp-accent);">${qtyInput.value}</td>
                                `;
                                confirmBody.appendChild(tr);
                            }
                        }
                    });

                    if (!hasItem) {
                        alert('Silakan masukkan minimal 1 item dengan Qty > 0 terlebih dahulu.');
                        return;
                    }

                    // Tampilkan Modal
                    const confirmModal = new bootstrap.Modal(document.getElementById('confirmTransferModal'));
                    confirmModal.show();
                });

                btnConfirmSubmit?.addEventListener('click', function() {
                    btnConfirmSubmit.disabled = true;
                    btnConfirmSubmit.innerHTML = 'Memproses...';
                    form.submit();
                });
            });
        </script>
    @endpush
@endsection
