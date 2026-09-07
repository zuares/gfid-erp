@extends('layouts.app')

@section('title', 'Accounting • Input Saldo Awal Hutang Supplier')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .sap-form { max-width:900px; display:grid; gap:1rem; }
        .sap-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.85rem; }
        .sap-field label { display:block; margin-bottom:.3rem; color:#475569; font-size:.76rem; font-weight:850; }
        .sap-field .form-control,.sap-field .form-select { min-height:42px; border-color:rgba(15,23,42,.12); box-shadow:none; }
        .sap-help { color:#64748b; font-size:.78rem; line-height:1.5; }
        .sap-actions { display:flex; gap:.55rem; justify-content:flex-end; flex-wrap:wrap; }
        .sap-btn { display:inline-flex; align-items:center; justify-content:center; min-height:40px; padding:.55rem .95rem; border-radius:999px; border:1px solid rgba(15,23,42,.1); background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:850; }
        .sap-btn-primary { color:#fff; background:#0f172a; border-color:#0f172a; }
        .sap-mode { display:flex; gap:.55rem; flex-wrap:wrap; padding:.25rem; border-radius:12px; background:#f8fafc; }
        .sap-mode label { display:flex; align-items:center; gap:.45rem; padding:.65rem .8rem; border-radius:9px; color:#475569; font-size:.82rem; font-weight:850; cursor:pointer; }
        .sap-mode input { accent-color:#0f172a; }
        .sap-mode-help { color:#64748b; font-size:.78rem; }
        .sap-bulk-box { grid-column:1/-1; border:1px solid rgba(15,23,42,.1); border-radius:12px; overflow:hidden; }
        .sap-bulk-toolbar { display:flex; justify-content:space-between; align-items:center; gap:.75rem; flex-wrap:wrap; padding:.75rem .85rem; background:#f8fafc; }
        .sap-bulk-search { max-width:260px; }
        .sap-bulk-table-wrap { max-height:420px; overflow:auto; }
        .sap-bulk-table { margin:0; font-size:.82rem; }
        .sap-bulk-table th { color:#64748b; font-size:.68rem; text-transform:uppercase; letter-spacing:.05em; }
        .sap-bulk-table input { min-width:170px; }
        .sap-bulk-total { padding:.7rem .85rem; border-top:1px solid rgba(15,23,42,.08); text-align:right; font-size:.85rem; font-weight:900; }
        .sap-hidden { display:none !important; }
        @media(max-width:700px){ .sap-grid{grid-template-columns:1fr;} }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Accounting"
        title="Input Saldo Awal Hutang Supplier"
        description="Jurnal akan langsung diposting sebagai Debit akun lawan dan Kredit Hutang Dagang.">
        <x-slot:actions>
            <a href="{{ route('accounting.supplier-ap-openings.index') }}" class="sap-btn">← Kembali</a>
        </x-slot:actions>

        <div class="sap-form">
            @if ($errors->any())
                <div class="alert alert-danger mb-0">
                    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <x-gf.panel title="Detail saldo awal" subtitle="Pilih satu supplier atau isi nominal beberapa supplier sekaligus.">
                <form method="POST" action="{{ route('accounting.supplier-ap-openings.store') }}" class="sap-grid">
                    @csrf
                    <div class="sap-field" style="grid-column:1/-1">
                        <label>Mode input</label>
                        <div class="sap-mode">
                            <label>
                                <input type="radio" name="bulk" value="0" @checked(old('bulk', '0') !== '1')>
                                Satu Supplier
                            </label>
                            <label>
                                <input type="radio" name="bulk" value="1" @checked(old('bulk') === '1')>
                                Semua Supplier
                            </label>
                        </div>
                        <div class="sap-mode-help mt-2">Mode Semua Supplier menampilkan semua supplier aktif. Baris dengan nominal kosong atau 0 akan dilewati.</div>
                    </div>

                    <div id="sap-single-fields" class="sap-field">
                        <label for="supplier_id">Supplier *</label>
                        <select id="supplier_id" name="supplier_id" class="form-select" required>
                            <option value="">Pilih supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', request('supplier_id')) === (string) $supplier->id)>
                                    {{ $supplier->name }}{{ $supplier->code ? ' · '.$supplier->code : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="sap-single-amount" class="sap-field">
                        <label for="amount">Nominal hutang *</label>
                        <input id="amount" type="text" name="amount" class="form-control" value="{{ old('amount') }}" placeholder="Contoh: 10.000.000" required>
                    </div>
                    <div id="sap-bulk-fields" class="sap-bulk-box sap-hidden">
                        <div class="sap-bulk-toolbar">
                            <div class="sap-help">Isi nominal hanya untuk supplier yang memiliki saldo hutang.</div>
                            <input id="sap-bulk-search" type="search" class="form-control sap-bulk-search" placeholder="Cari supplier...">
                        </div>
                        <div class="sap-bulk-table-wrap">
                            <table class="table table-sm align-middle sap-bulk-table">
                                <thead>
                                    <tr>
                                        <th>Supplier</th>
                                        <th>Kode</th>
                                        <th style="width:240px">Nominal hutang</th>
                                    </tr>
                                </thead>
                                <tbody id="sap-bulk-rows">
                                    @foreach ($suppliers as $supplier)
                                        <tr data-supplier-row data-search="{{ strtolower($supplier->name.' '.$supplier->code) }}">
                                            <td class="fw-bold">{{ $supplier->name }}</td>
                                            <td class="text-muted">{{ $supplier->code ?: '-' }}</td>
                                            <td>
                                                <input type="text" name="amounts[{{ $supplier->id }}]" class="form-control sap-bulk-amount" value="{{ old('amounts.'.$supplier->id) }}" placeholder="0">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="sap-bulk-total">Total dipilih: Rp <span id="sap-bulk-total">0</span></div>
                    </div>
                    <div class="sap-field">
                        <label for="date">Tanggal saldo awal *</label>
                        <input id="date" type="date" name="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" required>
                        <div class="sap-help mt-1">Tanggal jurnal dan batas awal posisi hutang.</div>
                    </div>
                    <div class="sap-field">
                        <label for="invoice_date">Tanggal invoice</label>
                        <input id="invoice_date" type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date') }}">
                        <div class="sap-help mt-1">Dipakai untuk aging; kosong akan memakai tanggal saldo awal.</div>
                    </div>
                    <div class="sap-field">
                        <label for="due_date">Jatuh tempo</label>
                        <input id="due_date" type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                    </div>
                    <div class="sap-field">
                        <label for="reference_no">Nomor invoice / referensi</label>
                        <input id="reference_no" type="text" name="reference_no" class="form-control" value="{{ old('reference_no') }}" maxlength="100" placeholder="Contoh: INV-LAMA-001">
                    </div>
                    <div class="sap-field">
                        <label for="ap_account_id">Akun Hutang Dagang *</label>
                        <select id="ap_account_id" name="ap_account_id" class="form-select" required>
                            @foreach ($accounts->where('type', 'liability') as $account)
                                <option value="{{ $account->id }}" @selected((string) old('ap_account_id', $defaultAp?->id) === (string) $account->id)>{{ $account->code }} · {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sap-field">
                        <label for="offset_account_id">Akun lawan *</label>
                        <select id="offset_account_id" name="offset_account_id" class="form-select" required>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" @selected((string) old('offset_account_id', $defaultOffset?->id) === (string) $account->id)>{{ $account->code }} · {{ $account->name }} ({{ $account->type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sap-field" style="grid-column:1/-1">
                        <label for="notes">Catatan</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Keterangan tambahan">{{ old('notes') }}</textarea>
                    </div>
                    <div style="grid-column:1/-1" class="sap-actions">
                        <a href="{{ route('accounting.supplier-ap-openings.index') }}" class="sap-btn">Batal</a>
                        <button type="submit" class="sap-btn sap-btn-primary" data-gf-confirm-title="Posting saldo awal?" data-gf-confirm-text="Jurnal opening balance akan langsung dibuat dan muncul di AP Report.">Posting Saldo Awal</button>
                    </div>
                </form>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection

@push('scripts')
    <script>
        (() => {
            const singleFields = document.getElementById('sap-single-fields');
            const singleAmount = document.getElementById('sap-single-amount');
            const bulkFields = document.getElementById('sap-bulk-fields');
            const supplierSelect = document.getElementById('supplier_id');
            const amountInput = document.getElementById('amount');
            const bulkRows = [...document.querySelectorAll('.sap-bulk-amount')];
            const modeInputs = [...document.querySelectorAll('input[name="bulk"]')];
            const searchInput = document.getElementById('sap-bulk-search');
            const totalOutput = document.getElementById('sap-bulk-total');

            const isBulk = () => modeInputs.find((input) => input.checked)?.value === '1';
            const parseAmount = (value) => {
                let normalized = String(value || '').replace(/\s/g, '');
                if (normalized.includes(',')) {
                    normalized = normalized.replace(/\./g, '').replace(',', '.');
                } else if (/^\d{1,3}(\.\d{3})+$/.test(normalized)) {
                    normalized = normalized.replace(/\./g, '');
                }
                const amount = Number(normalized);
                return Number.isFinite(amount) ? amount : 0;
            };
            const updateTotal = () => {
                const total = bulkRows.reduce((sum, input) => sum + parseAmount(input.value), 0);
                totalOutput.textContent = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(total);
            };
            const syncMode = () => {
                const bulk = isBulk();
                singleFields.classList.toggle('sap-hidden', bulk);
                singleAmount.classList.toggle('sap-hidden', bulk);
                bulkFields.classList.toggle('sap-hidden', !bulk);
                supplierSelect.disabled = bulk;
                amountInput.disabled = bulk;
                supplierSelect.required = !bulk;
                amountInput.required = !bulk;
                bulkRows.forEach((input) => { input.disabled = !bulk; });
                updateTotal();
            };

            modeInputs.forEach((input) => input.addEventListener('change', syncMode));
            bulkRows.forEach((input) => input.addEventListener('input', updateTotal));
            searchInput?.addEventListener('input', () => {
                const query = searchInput.value.trim().toLowerCase();
                document.querySelectorAll('[data-supplier-row]').forEach((row) => {
                    row.classList.toggle('sap-hidden', query !== '' && !row.dataset.search.includes(query));
                });
            });
            syncMode();
        })();
    </script>
@endpush
