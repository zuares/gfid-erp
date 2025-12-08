{{-- resources/views/production/cutting_jobs/_pick_lot.blade.php --}}

@push('head')
    <style>
        .lot-picker-wrap {
            margin-bottom: .75rem;
        }

        .lot-picker-header {
            display: flex;
            flex-direction: column;
            gap: .25rem;
            margin-bottom: .75rem;
        }

        @media (min-width: 576px) {
            .lot-picker-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: flex-end;
            }
        }

        .lot-picker-title {
            font-size: .9rem;
            font-weight: 600;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .lot-picker-help {
            font-size: .8rem;
        }

        .lot-picker-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
        }

        .lot-grid {
            display: grid;
            gap: .75rem;
        }

        @media (min-width: 576px) {
            .lot-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 992px) {
            .lot-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .lot-card-modern {
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.45);
            padding: .6rem .7rem .5rem;
            background: var(--card);
            cursor: pointer;
            transition:
                background-color 0.15s ease,
                box-shadow 0.15s ease,
                border-color 0.15s ease,
                transform 0.06s ease;
        }

        .lot-card-modern:hover {
            box-shadow:
                0 10px 26px rgba(15, 23, 42, 0.16),
                0 0 0 1px rgba(59, 130, 246, 0.3);
            border-color: rgba(59, 130, 246, 0.5);
            transform: translateY(-1px);
        }

        .lot-card-modern .lot-code {
            font-size: .9rem;
            letter-spacing: .03em;
        }

        .lot-card-modern.lot-selected {
            border-color: rgba(59, 130, 246, 0.9);
            box-shadow:
                0 10px 35px rgba(37, 99, 235, 0.25),
                0 0 0 1px rgba(59, 130, 246, 0.5);
        }

        /* Ikuti pola lama: JS pakai .lot-row + .lot-hidden */
        .lot-row.lot-hidden {
            display: none;
        }

        .lot-card-badge {
            font-size: .68rem;
            padding: .05rem .45rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.6);
            background: rgba(148, 163, 184, 0.12);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .lot-empty-hint {
            font-size: .8rem;
            color: var(--muted);
            margin-top: .35rem;
        }

        @media (max-width: 767.98px) {
            .lot-card-modern {
                padding: .55rem .6rem .45rem;
            }

            .lot-picker-help {
                font-size: .78rem;
            }

            .btn-pill-sm {
                font-size: .75rem;
                padding-block: .2rem;
            }

            /* Footer di mobile: tombol Simpan LOT full width */
            .lot-picker-footer {
                flex-direction: column;
                align-items: stretch;
                gap: .4rem;
            }

            .lot-picker-footer .btn-primary {
                width: 100%;
                justify-content: center;
            }

            .lot-picker-footer .small.text-muted {
                text-align: left;
            }
        }
    </style>
@endpush

<div class="lot-picker-wrap" id="cutting-pick-lot">
    {{-- Header + select kain + tombol select all --}}
    <div class="lot-picker-header">
        <div>
            <div class="lot-picker-title">
                Pilih LOT Kain
            </div>
            <p class="lot-picker-help mb-0">
                Pilih <strong>item kain</strong> terlebih dahulu, lalu centang LOT yang akan dipakai.
            </p>
        </div>

        <div class="lot-picker-actions">
            {{-- Select item kain --}}
            <select name="fabric_item_id" id="fabric_item_id" class="form-select form-select-sm" style="min-width: 210px;">
                <option value="">Pilih item kain…</option>
                @foreach ($fabricItems as $item)
                    <option value="{{ $item->id }}" @selected(old('fabric_item_id') == $item->id)>
                        {{ $item->code }} — {{ $item->name }}
                    </option>
                @endforeach
            </select>

            {{-- Tombol select all / clear --}}
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-outline-secondary btn-pill-sm" id="btn-select-all-lots" disabled>
                    Centang semua
                </button>
                <button type="button" class="btn btn-outline-secondary btn-pill-sm" id="btn-unselect-all-lots"
                    disabled>
                    Hapus centang
                </button>
            </div>
        </div>
    </div>

    {{-- Hint di bawah (kondisional) --}}
    <div id="lot-grid-hint" class="lot-empty-hint">
        Pilih item kain terlebih dahulu untuk melihat daftar LOT.
    </div>

    {{-- GRID LOT --}}
    <div class="lot-grid mt-2 d-none" id="lot-grid">
        @foreach ($lotStocks as $row)
            @php
                $lot = $row->lot;
                $item = $lot->item;
                $wh = $row->warehouse;
            @endphp

            {{-- .lot-row tetap untuk kompat dengan JS lama --}}
            <div class="lot-card-modern lot-row lot-card-item" data-lot-id="{{ $row->lot_id }}"
                data-item-id="{{ $item->id }}" data-balance="{{ $row->qty_balance }}">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-semibold mono lot-code">
                            {{ $lot->code }}
                        </div>
                        <div class="small text-muted">
                            {{ $item->code }}
                        </div>
                        @if ($wh?->code)
                            <div class="small text-muted d-md-none mt-1">
                                <span class="lot-card-badge">{{ $wh->code }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold mono">
                            {{ number_format($row->qty_balance, 2, ',', '.') }}
                        </div>
                        @if ($wh?->code)
                            <div class="small text-muted d-none d-md-block">
                                {{ $wh->code }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input lot-checkbox" name="selected_lots[]"
                            value="{{ $row->lot_id }}">
                        <span class="ms-1 small">Pakai LOT ini</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Footer info + tombol lanjut --}}
    <div class="d-flex justify-content-between align-items-center mt-3 lot-picker-footer">
        <div class="small text-muted">
            Setelah LOT dipilih, lanjutkan isi bundles di bawah.
        </div>
        <button type="button" class="btn btn-primary btn-sm btn-pill-sm" id="btn-confirm-lots">
            Simpan LOT &amp; Lanjut
        </button>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fabricSelect = document.getElementById('fabric_item_id');
            const lotGrid = document.getElementById('lot-grid');
            const lotHint = document.getElementById('lot-grid-hint');
            const lotCards = Array.from(document.querySelectorAll('.lot-card-item'));
            const btnSelectAll = document.getElementById('btn-select-all-lots');
            const btnUnselectAll = document.getElementById('btn-unselect-all-lots');

            function updateButtonsState(hasLots) {
                if (btnSelectAll) btnSelectAll.disabled = !hasLots;
                if (btnUnselectAll) btnUnselectAll.disabled = !hasLots;
            }

            function applyFilter() {
                if (!fabricSelect || !lotGrid || !lotHint) return;

                const selectedId = parseInt(fabricSelect.value || '0', 10);
                let anyShown = false;

                lotCards.forEach(card => {
                    const itemId = parseInt(card.dataset.itemId || '0', 10);
                    if (!selectedId || itemId !== selectedId) {
                        card.classList.add('d-none');
                    } else {
                        card.classList.remove('d-none');
                        anyShown = true;
                    }
                });

                if (!selectedId) {
                    lotGrid.classList.add('d-none');
                    lotHint.textContent = 'Pilih item kain terlebih dahulu untuk melihat daftar LOT.';
                    updateButtonsState(false);
                    return;
                }

                if (!anyShown) {
                    lotGrid.classList.add('d-none');
                    lotHint.textContent = 'Belum ada LOT untuk kain ini. Cek stok di modul GRN / gudang RM.';
                    updateButtonsState(false);
                } else {
                    lotGrid.classList.remove('d-none');
                    lotHint.textContent = 'Centang LOT yang ingin dipakai untuk Cutting Job.';
                    updateButtonsState(true);
                }
            }

            // Klik card = toggle checkbox + highlight
            lotCards.forEach(card => {
                const checkbox = card.querySelector('.lot-checkbox');
                if (!checkbox) return;

                function syncCardState() {
                    card.classList.toggle('lot-selected', checkbox.checked);
                }

                card.addEventListener('click', function(e) {
                    if (e.target === checkbox) return;
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                });

                checkbox.addEventListener('change', syncCardState);
                syncCardState();
            });

            if (btnSelectAll) {
                btnSelectAll.addEventListener('click', function() {
                    const selectedId = parseInt(fabricSelect.value || '0', 10);
                    if (!selectedId) return;

                    lotCards.forEach(card => {
                        const checkbox = card.querySelector('.lot-checkbox');
                        const itemId = parseInt(card.dataset.itemId || '0', 10);
                        if (checkbox && itemId === selectedId && !checkbox.checked) {
                            checkbox.checked = true;
                            checkbox.dispatchEvent(new Event('change', {
                                bubbles: true
                            }));
                        }
                    });
                });
            }

            if (btnUnselectAll) {
                btnUnselectAll.addEventListener('click', function() {
                    const selectedId = parseInt(fabricSelect.value || '0', 10);
                    if (!selectedId) return;

                    lotCards.forEach(card => {
                        const checkbox = card.querySelector('.lot-checkbox');
                        const itemId = parseInt(card.dataset.itemId || '0', 10);
                        if (checkbox && itemId === selectedId && checkbox.checked) {
                            checkbox.checked = false;
                            checkbox.dispatchEvent(new Event('change', {
                                bubbles: true
                            }));
                        }
                    });
                });
            }

            if (fabricSelect) {
                fabricSelect.addEventListener('change', applyFilter);
            }

            // INIT (support old() fabric_item_id)
            applyFilter();
        });
    </script>
@endpush
