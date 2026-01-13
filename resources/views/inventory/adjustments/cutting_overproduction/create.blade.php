{{-- resources/views/inventory/adjustments/cutting_overproduction/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Cutting Overproduction Adjustment')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .85rem .85rem 4.5rem;
        }

        .cardx {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .35);
            box-shadow: 0 12px 26px rgba(15, 23, 42, .14);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .86rem;
        }

        .section-title {
            font-size: .82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .18rem .6rem;
            border-radius: 999px;
            font-size: .78rem;
            border: 1px solid rgba(148, 163, 184, .45);
            background: rgba(148, 163, 184, .10);
        }

        .pill-wip {
            border-color: rgba(34, 197, 94, .45);
            background: rgba(34, 197, 94, .12);
        }

        .danger-soft {
            background: rgba(220, 38, 38, .10);
            border: 1px solid rgba(220, 38, 38, .25);
            border-radius: 14px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .qty-input {
            max-width: 150px;
        }

        .sticky-footer {
            position: sticky;
            bottom: 0;
            z-index: 20;
            padding-top: .75rem;
            background: linear-gradient(to top, rgba(2, 6, 23, .08), transparent);
        }

        body[data-theme="dark"] .sticky-footer {
            background: linear-gradient(to top, rgba(2, 6, 23, .85), transparent);
        }

        .summary-box {
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .35);
            background: rgba(148, 163, 184, .08);
            padding: .55rem .65rem;
        }

        .btn-pill {
            border-radius: 999px;
        }

        .lock-hint {
            font-size: .8rem;
            color: rgba(220, 38, 38, .9);
            font-weight: 600;
        }

        .wip-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .2rem .6rem;
            border-radius: 999px;
            border: 1px solid rgba(34, 197, 94, .45);
            background: rgba(34, 197, 94, .10);
            font-size: .78rem;
            font-weight: 800;
        }

        .wip-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #22c55e;
        }
    </style>
@endpush

@section('content')
    @php
        $defaultDate = old('date', now()->toDateString());
        $defaultWarehouseId = (int) old('warehouse_id', $warehouseId ?? 0);

        // $lines dari controller adalah collection -> aman dibuat array
        $rows = old('lines', isset($lines) ? (is_array($lines) ? $lines : $lines->toArray()) : []);

        // optional objects kalau controller ngirim:
        $warehouse = $warehouse ?? null; // Warehouse target (recommended)
        $warehouses = $warehouses ?? null; // list warehouses (optional)

        // default: kunci gudang (anti salah masuk RM)
        $allowChangeWarehouse = old('allow_change_warehouse', '0') === '1';
    @endphp

    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="cardx p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <div class="section-title mb-1">Cutting Overproduction Adjustment</div>
                    <div class="h5 mb-1 mono">{{ $job->code ?? '-' }}</div>

                    <div class="help">
                        Tanggal Cutting Job: {{ $job->date?->format('Y-m-d') ?? ($job->date ?? '-') }}
                        • Gudang Job: <span class="mono">{{ $job->warehouse?->code ?? '-' }}</span>
                    </div>

                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <span class="pill pill-wip">Tujuan: tambah stok WIP (IN)</span>
                        <span class="pill">Dokumen dibuat Draft → lalu POST</span>
                        <span class="wip-badge">
                            <span class="wip-dot"></span>
                            TARGET:
                            <span class="mono">
                                {{ $warehouse?->code ?? 'Warehouse ID #' . $defaultWarehouseId }}
                            </span>
                        </span>
                    </div>

                    <div class="mt-2 lock-hint">
                        ⚠️ Gudang target dikunci ke WIP supaya tidak salah masuk RM.
                    </div>
                </div>

                <div class="text-end">
                    <a href="{{ route('production.cutting_jobs.show', $job) }}"
                        class="btn btn-sm btn-outline-secondary btn-pill">
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        {{-- ERROR BOX --}}
        @if ($errors->any())
            <div class="danger-soft p-3 mb-3" style="font-size:.88rem;">
                <div class="fw-semibold mb-1">Terjadi error:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('production.cutting_overproduction.store') }}" method="POST" id="overprodForm">
            @csrf

            {{-- REQUIRED --}}
            <input type="hidden" name="cutting_job_id" value="{{ (int) ($job->id ?? 0) }}">
            <input type="hidden" name="warehouse_id" value="{{ $defaultWarehouseId }}">

            {{-- HEADER FORM --}}
            <div class="cardx p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="section-title mb-0">Header Dokumen</div>
                    <div class="help">Tanggal wajib. Gudang target sudah dikunci.</div>
                </div>

                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <label class="form-label small text-uppercase fw-bold">Tanggal Dokumen</label>
                        <input type="date" name="date" value="{{ $defaultDate }}"
                            class="form-control {{ $errors->has('date') ? 'is-invalid' : '' }}">
                        @if ($errors->has('date'))
                            <div class="invalid-feedback">{{ $errors->first('date') }}</div>
                        @endif
                    </div>

                    <div class="col-md-4 col-6">
                        <label class="form-label small text-uppercase fw-bold">Warehouse Target (WIP)</label>

                        {{-- tampilkan read-only untuk user (biar jelas) --}}
                        <input type="text" class="form-control"
                            value="{{ $warehouse ? $warehouse->code . ' — ' . $warehouse->name : 'ID #' . $defaultWarehouseId }}"
                            readonly>

                        @if ($errors->has('warehouse_id'))
                            <div class="text-danger small mt-1">{{ $errors->first('warehouse_id') }}</div>
                        @endif

                        {{-- opsional: kalau mau owner bisa ganti, tinggal aktifkan toggle ini --}}
                        @if ($warehouses && auth()->user() && in_array(auth()->user()->role ?? null, ['owner'], true))
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1" id="allowChangeWarehouse"
                                    name="allow_change_warehouse" {{ $allowChangeWarehouse ? 'checked' : '' }}>
                                <label class="form-check-label small" for="allowChangeWarehouse">
                                    Owner: izinkan ubah gudang target
                                </label>
                            </div>

                            <div id="warehouseSelectWrap" class="mt-2"
                                style="{{ $allowChangeWarehouse ? '' : 'display:none;' }}">
                                <select class="form-select" id="warehouseSelect">
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}"
                                            {{ (int) $wh->id === (int) $defaultWarehouseId ? 'selected' : '' }}>
                                            {{ $wh->code }} — {{ $wh->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="help mt-1">
                                    Jika diubah, hidden <span class="mono">warehouse_id</span> akan ikut berubah.
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="col-md-5 col-12">
                        <label class="form-label small text-uppercase fw-bold">Catatan (opsional)</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" class="form-control"
                            placeholder="mis: overproduction cutting / koreksi input">
                    </div>
                </div>
            </div>

            {{-- LINES --}}
            <div class="cardx p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <div>
                        <div class="section-title mb-0">Lines</div>
                        <div class="help">Isi hanya yang ada overproduction. Qty 0 akan di-skip saat store().</div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-pill" id="btnZeroAll">
                            Nolkan semua
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-pill" id="btnFillAll1">
                            Isi semua = 1
                        </button>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:55px;">#</th>
                                <th>Item</th>
                                <th class="text-end" style="width:190px;">Qty IN</th>
                                <th style="width:260px;">Catatan</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($rows as $i => $r)
                                @php
                                    $itemId = (int) ($r['item_id'] ?? 0);
                                    $itemCode = $r['item_code'] ?? '';
                                    $itemName = $r['item_name'] ?? '';

                                    $qtyIn = old("lines.$i.qty_in", $r['suggest_qty_in'] ?? 0);
                                    $notesLine = old("lines.$i.notes", $r['notes'] ?? null);

                                    $eItem = $errors->first("lines.$i.item_id");
                                    $eQty = $errors->first("lines.$i.qty_in");
                                @endphp

                                <tr>
                                    <td class="mono">{{ $i + 1 }}</td>

                                    <td>
                                        <div class="fw-semibold mono">{{ $itemCode }}</div>
                                        <div class="help">{{ $itemName }}</div>

                                        <input type="hidden" name="lines[{{ $i }}][item_id]"
                                            value="{{ $itemId }}">

                                        @if ($eItem)
                                            <div class="text-danger small mt-1">{{ $eItem }}</div>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <input type="number" step="1" min="0" inputmode="numeric"
                                            class="form-control text-end qty-input d-inline-block qty-in {{ $eQty ? 'is-invalid' : '' }}"
                                            name="lines[{{ $i }}][qty_in]" value="{{ $qtyIn }}"
                                            placeholder="0">
                                        @if ($eQty)
                                            <div class="invalid-feedback">{{ $eQty }}</div>
                                        @endif
                                    </td>

                                    <td>
                                        <input type="text" class="form-control"
                                            name="lines[{{ $i }}][notes]" value="{{ $notesLine }}"
                                            placeholder="opsional">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        Tidak ada item (bundle) ditemukan untuk job ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($errors->first('lines'))
                    <div class="text-danger small mt-2">{{ $errors->first('lines') }}</div>
                @endif
            </div>

            {{-- FOOTER ACTIONS --}}
            <div class="sticky-footer">
                <div class="cardx p-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="summary-box">
                        <div class="help mb-1">Ringkasan</div>
                        <div class="mono fw-semibold">
                            Total Qty IN: <span id="totalQtyIn">0</span>
                        </div>
                        <div class="help mt-1">
                            Warehouse target: <span
                                class="mono">{{ $warehouse?->code ?? 'ID #' . $defaultWarehouseId }}</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <a href="{{ route('production.cutting_jobs.show', $job) }}"
                            class="btn btn-outline-secondary btn-pill">
                            Batal
                        </a>

                        <button type="submit" class="btn btn-primary btn-pill"
                            onclick="return confirm('Buat Overproduction Adjustment (Draft)? Setelah itu perlu POST untuk eksekusi stok.')">
                            Simpan Draft
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const qInputs = () => Array.from(document.querySelectorAll('.qty-in'));

            const parseNum = (v) => {
                const n = Number(String(v ?? '').replace(',', '.'));
                return Number.isFinite(n) ? n : 0;
            };

            const fmt = (n) => {
                try {
                    return new Intl.NumberFormat('id-ID', {
                        maximumFractionDigits: 2
                    }).format(n);
                } catch (e) {
                    return String(n);
                }
            };

            const recalc = () => {
                const total = qInputs().reduce((acc, el) => acc + parseNum(el.value), 0);
                const out = document.getElementById('totalQtyIn');
                if (out) out.textContent = fmt(total);
            };

            document.addEventListener('input', (e) => {
                if (e.target && e.target.classList.contains('qty-in')) recalc();
            });

            const btnZeroAll = document.getElementById('btnZeroAll');
            if (btnZeroAll) btnZeroAll.addEventListener('click', () => {
                qInputs().forEach(el => el.value = 0);
                recalc();
            });

            const btnFillAll1 = document.getElementById('btnFillAll1');
            if (btnFillAll1) btnFillAll1.addEventListener('click', () => {
                qInputs().forEach(el => {
                    if (!el.value || parseNum(el.value) === 0) el.value = 1;
                });
                recalc();
            });

            // optional: owner can change warehouse (if dropdown exists)
            const allow = document.getElementById('allowChangeWarehouse');
            const wrap = document.getElementById('warehouseSelectWrap');
            const sel = document.getElementById('warehouseSelect');
            const hiddenWarehouseId = document.querySelector('input[name="warehouse_id"]');

            if (allow && wrap) {
                allow.addEventListener('change', () => {
                    wrap.style.display = allow.checked ? '' : 'none';
                    if (!allow.checked) {
                        // balik lagi ke default (tetap yang hidden saat load)
                        // (biarin aja)
                    }
                });
            }

            if (sel && hiddenWarehouseId) {
                sel.addEventListener('change', () => {
                    hiddenWarehouseId.value = sel.value;
                });
            }

            recalc();
        })();
    </script>
@endpush
