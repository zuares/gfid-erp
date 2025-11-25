@extends('layouts.app')

@section('title', 'Produksi • External Transfer Baru')

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .required::after {
            content: '*';
            color: #ef4444;
            margin-left: 3px;
        }

        thead th {
            background: var(--panel);
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .table-sm td,
        .table-sm th {
            padding-block: .35rem;
        }

        .line-actions {
            white-space: nowrap;
        }

        @media (max-width: 767.98px) {
            .table-wrap {
                overflow-x: auto;
            }
        }

        .btn-icon {
            padding-inline: .45rem;
        }

        .badge-stock {
            border-radius: 999px;
            padding: .05rem .5rem;
            font-size: .7rem;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, .12);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">External Transfer Baru</h4>
                <div class="text-muted small">
                    Buat dokumen perpindahan LOT ke vendor / gudang lain.
                </div>
            </div>
            <div>
                <a href="{{ route('inventory.external_transfers.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger small">
                <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $oldLines = old('lines', []);
        @endphp

        <form action="{{ route('inventory.external_transfers.store') }}" method="post">
            @csrf

            {{-- HEADER --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">

                        {{-- TANGGAL --}}
                        <div class="col-6 col-md-3">
                            <label class="form-label small required">Tanggal</label>
                            <input type="date" name="date" class="form-control form-control-sm"
                                value="{{ old('date', now()->toDateString()) }}">
                        </div>

                        {{-- PROSES --}}
                        <div class="col-6 col-md-3">
                            <label class="form-label small required">Proses</label>
                            @php $proc = old('process', $defaultProcess ?? 'cutting'); @endphp
                            <select name="process" class="form-select form-select-sm" id="process-select">
                                <option value="cutting" {{ $proc === 'cutting' ? 'selected' : '' }}>Cutting</option>
                                <option value="sewing" {{ $proc === 'sewing' ? 'selected' : '' }}>Sewing</option>
                                <option value="finishing" {{ $proc === 'finishing' ? 'selected' : '' }}>Finishing</option>
                                <option value="other" {{ $proc === 'other' ? 'selected' : '' }}>Lainnya</option>
                            </select>
                            <div class="help">Jenis proses yang akan dikerjakan di vendor.</div>
                        </div>

                        {{-- OPERATOR / VENDOR --}}
                        <div class="col-12 col-md-4">
                            <label class="form-label small required">Operator / Vendor</label>
                            @php
                                $oldOp = old('operator_code');
                            @endphp
                            <select name="operator_code" class="form-select form-select-sm" id="operator-select">
                                <option value="">Pilih operator...</option>
                                @foreach ($employees as $emp)
                                    @if ($emp->role === $proc)
                                        <option value="{{ $emp->code }}" data-role="{{ $emp->role }}"
                                            {{ $oldOp === $emp->code ? 'selected' : '' }}>
                                            {{ $emp->code }} — {{ $emp->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="help">Hanya karyawan dengan role sesuai proses yang akan tampil.</div>
                        </div>

                        {{-- DARI GUDANG --}}
                        <div class="col-12 col-md-4">
                            <label class="form-label small required">Dari Gudang</label>
                            @php
                                $fromId = old('from_warehouse_id', $defaultFromWarehouseId ?? null);
                            @endphp
                            <select name="from_warehouse_id" class="form-select form-select-sm" id="from-warehouse-select">
                                <option value="">Pilih...</option>
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}"
                                        {{ (int) $fromId === (int) $wh->id ? 'selected' : '' }}>
                                        {{ $wh->code }} — {{ $wh->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="help">
                                Gudang asal barang saat ini. Mengganti gudang akan me-refresh daftar LOT.
                            </div>
                        </div>

                        {{-- KE GUDANG / VENDOR --}}
                        <div class="col-12 col-md-4">
                            <label class="form-label small required">Ke Gudang / Vendor</label>
                            <input type="text" class="form-control form-control-sm mono" id="to-warehouse-code-display"
                                name="to_warehouse_code" value="{{ old('to_warehouse_code', $autoToWarehouseCode ?? '') }}"
                                readonly>
                            <div class="help">
                                Diisi otomatis dengan pola <span class="mono">CUT-EXT-[EMP]</span>.
                            </div>
                        </div>

                        {{-- CATATAN --}}
                        <div class="col-12">
                            <label class="form-label small">Catatan</label>
                            <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DETAIL LOT --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="fw-semibold small text-uppercase">Detail LOT</div>
                            <div class="help mt-1">
                                Centang / klik baris LOT yang akan dikirim. Qty default = stok LOT, bisa diedit.
                            </div>
                        </div>
                    </div>

                    @if ($lots->isEmpty())
                        <div class="alert alert-warning small mb-2">
                            Gudang yang dipilih
                            <span class="mono">{{ $warehouses->firstWhere('id', $fromId)?->code ?? '-' }}</span>
                            belum punya LOT aktif. Pilih gudang lain atau buat LOT baru terlebih dahulu.
                        </div>
                    @endif

                    <div class="table-wrap">
                        <table class="table table-sm align-middle mb-0" id="lines-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;" class="text-center">
                                        <input type="checkbox" class="form-check-input" id="check-all">
                                    </th>
                                    <th style="min-width: 150px;">LOT</th>
                                    <th style="min-width: 200px;">Item</th>
                                    <th style="min-width: 80px;" class="text-end">Stok</th>
                                    <th style="min-width: 120px;" class="text-end">Qty Kirim</th>
                                    <th style="min-width: 80px;">Satuan</th>
                                    <th style="min-width: 150px;">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($lots as $idx => $lot)
                                    @php
                                        $rowOld = $oldLines[$idx] ?? [];
                                        $oldQty = $rowOld['qty'] ?? '';
                                        $oldunit = $rowOld['unit'] ?? $lot->unit;
                                        $oldNotes = $rowOld['notes'] ?? '';
                                    @endphp
                                    <tr class="lot-row">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input line-check"
                                                data-stock="{{ (float) $lot->stock_remain }}"
                                                {{ (float) $oldQty > 0 ? 'checked' : '' }}>
                                            <input type="hidden" name="lines[{{ $idx }}][lot_id]"
                                                value="{{ $lot->id }}">
                                            <input type="hidden" name="lines[{{ $idx }}][item_id]"
                                                value="{{ $lot->item_id }}">
                                        </td>
                                        <td class="mono small">
                                            {{ $lot->lot_code }}
                                        </td>
                                        <td>
                                            <div class="mono small">
                                                {{ $lot->item_code }} {{ $lot->item_name }}
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge-stock mono">
                                                {{-- ⭐ format Indonesia: 25 -> 25,00 --}}
                                                {{ number_format($lot->stock_remain, 2, ',', '.') }}
                                            </span>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0"
                                                name="lines[{{ $idx }}][qty]"
                                                class="form-control form-control-sm text-end mono line-qty"
                                                data-stock="{{ (float) $lot->stock_remain }}"
                                                value="{{ $oldQty !== '' ? $oldQty : '' }}">
                                        </td>
                                        <td>
                                            <input type="text" name="lines[{{ $idx }}][unit]"
                                                class="form-control form-control-sm text-center mono"
                                                value="{{ $oldunit }}">
                                        </td>
                                        <td>
                                            <input type="text" name="lines[{{ $idx }}][notes]"
                                                class="form-control form-control-sm" value="{{ $oldNotes }}">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted small">
                                            Tidak ada LOT untuk gudang ini.
                                            Pilih gudang lain di atas untuk melihat LOT lain.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ACTIONS --}}
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div class="small text-muted">
                    Status awal dokumen: <span class="mono">SENT</span>.
                    Setelah vendor terima, bisa diproses di menu Vendor Cutting.
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">
                        Simpan Dokumen
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const employees = @json($employees);
            const warehouses = @json($warehouses);

            const procSelect = document.getElementById('process-select');
            const opSelect = document.getElementById('operator-select');
            const fromWhSelect = document.getElementById('from-warehouse-select');
            const toWhDisplay = document.getElementById('to-warehouse-code-display');
            const tbody = document.querySelector('#lines-table tbody');
            const checkAll = document.getElementById('check-all');

            function mapProcessToCode(proc) {
                switch ((proc || '').toLowerCase()) {
                    case 'cutting':
                        return 'CUT';
                    case 'sewing':
                        return 'SEW';
                    case 'finishing':
                        return 'FIN';
                    case 'other':
                        return 'OTH';
                    default:
                        return (proc || '???').substring(0, 3).toUpperCase();
                }
            }

            function updateAutoToWarehouse() {
                if (!procSelect || !opSelect || !toWhDisplay) return;

                const proc = (procSelect.value || '').trim();
                const opCode = (opSelect.value || '').trim();

                if (!proc || !opCode) {
                    toWhDisplay.value = '';
                    return;
                }

                const proc3 = mapProcessToCode(proc);
                const op3 = opCode
                    .replace(/[^A-Z0-9]/gi, '')
                    .substring(0, 3)
                    .toUpperCase();

                const autoCode = `${proc3}-EXT-${op3}`.toUpperCase();
                toWhDisplay.value = autoCode;
            }

            function rebuildOperatorOptions() {
                if (!procSelect || !opSelect) return;

                const currentProc = procSelect.value || 'cutting';
                const currentVal = opSelect.value;

                opSelect.innerHTML = '<option value="">Pilih operator...</option>';

                let list = employees.filter(emp => emp.role === currentProc);
                if (list.length === 0) {
                    list = employees;
                }

                list.forEach(emp => {
                    const opt = document.createElement('option');
                    opt.value = emp.code;
                    opt.dataset.role = emp.role;
                    opt.textContent = `${emp.code} — ${emp.name}`;

                    if (currentVal && currentVal === emp.code) {
                        opt.selected = true;
                    }

                    opSelect.appendChild(opt);
                });
            }

            function reloadByWarehouseChange() {
                if (!fromWhSelect) return;

                const whId = fromWhSelect.value || '';
                const proc = procSelect ? procSelect.value : '';
                const opCode = opSelect ? opSelect.value : '';

                const url = new URL("{{ route('inventory.external_transfers.create') }}", window.location.origin);
                if (whId) url.searchParams.set('from_warehouse_id', whId);
                if (proc) url.searchParams.set('process', proc);
                if (opCode) url.searchParams.set('operator_code', opCode);

                window.location.href = url.toString();
            }

            function initLotCheckboxes() {
                if (!tbody) return;

                function syncHeaderCheckbox() {
                    if (!checkAll) return;
                    const allChk = tbody.querySelectorAll('.line-check');
                    if (allChk.length === 0) {
                        checkAll.checked = false;
                        checkAll.indeterminate = false;
                        return;
                    }

                    const checkedCount = Array.from(allChk).filter(c => c.checked).length;

                    if (checkedCount === 0) {
                        checkAll.checked = false;
                        checkAll.indeterminate = false;
                    } else if (checkedCount === allChk.length) {
                        checkAll.checked = true;
                        checkAll.indeterminate = false;
                    } else {
                        checkAll.indeterminate = true;
                    }
                }

                // checkbox change
                tbody.addEventListener('change', function(e) {
                    if (!e.target.classList.contains('line-check')) return;

                    const chk = e.target;
                    const tr = chk.closest('tr');
                    const qtyInput = tr.querySelector('.line-qty');
                    const stock = parseFloat(chk.dataset.stock || qtyInput.dataset.stock || '0') || 0;

                    if (chk.checked) {
                        if (!qtyInput.value || parseFloat(qtyInput.value || '0') <= 0) {
                            qtyInput.value = stock;
                        }
                    } else {
                        qtyInput.value = '';
                    }

                    syncHeaderCheckbox();
                });

                // qty input manual → auto centang/uncentang
                tbody.addEventListener('input', function(e) {
                    if (!e.target.classList.contains('line-qty')) return;

                    const qtyInput = e.target;
                    const tr = qtyInput.closest('tr');
                    const chk = tr.querySelector('.line-check');

                    const val = parseFloat(qtyInput.value || '0') || 0;
                    chk.checked = val > 0;

                    syncHeaderCheckbox();
                });

                // header "centang semua"
                if (checkAll) {
                    checkAll.addEventListener('change', function() {
                        const allChk = tbody.querySelectorAll('.line-check');

                        allChk.forEach(chk => {
                            const tr = chk.closest('tr');
                            const qtyInput = tr.querySelector('.line-qty');
                            const stock = parseFloat(chk.dataset.stock || qtyInput.dataset.stock ||
                                '0') || 0;

                            chk.checked = checkAll.checked;

                            if (checkAll.checked) {
                                if (!qtyInput.value || parseFloat(qtyInput.value || '0') <= 0) {
                                    qtyInput.value = stock;
                                }
                            } else {
                                qtyInput.value = '';
                            }
                        });

                        checkAll.indeterminate = false;
                    });
                }

                // ⭐ NEW: klik baris = toggle checkbox
                tbody.addEventListener('click', function(e) {
                    const tr = e.target.closest('tr');
                    if (!tr) return;

                    // jangan trigger kalau klik langsung di input/checkbox/textarea/button
                    if (e.target.closest('input, textarea, select, button, label')) {
                        return;
                    }

                    const chk = tr.querySelector('.line-check');
                    if (!chk) return;

                    chk.checked = !chk.checked;
                    // paksa trigger handler 'change' di atas
                    chk.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                });

                syncHeaderCheckbox();
            }

            function initFormSubmission() {
                const form = document.querySelector('form');
                if (!form || !tbody) return;

                form.addEventListener('submit', function(e) {
                    const rows = tbody.querySelectorAll('tr');
                    let selectedCount = 0;

                    rows.forEach(tr => {
                        const chk = tr.querySelector('.line-check');
                        if (!chk) return;

                        if (chk.checked) {
                            selectedCount++;
                        } else {
                            tr.querySelectorAll('input, select, textarea').forEach(el => {
                                el.disabled = true;
                            });
                        }
                    });

                    if (selectedCount === 0) {
                        e.preventDefault();
                        alert('Pilih minimal satu LOT yang akan dikirim.');
                    }
                });
            }

            function initEvents() {
                procSelect?.addEventListener('change', () => {
                    rebuildOperatorOptions();
                    updateAutoToWarehouse();
                });

                opSelect?.addEventListener('change', () => {
                    updateAutoToWarehouse();
                });

                fromWhSelect?.addEventListener('change', () => {
                    reloadByWarehouseChange();
                });

                rebuildOperatorOptions();
                updateAutoToWarehouse();
                initLotCheckboxes();
                initFormSubmission();
            }

            initEvents();
        })();
    </script>
@endpush
