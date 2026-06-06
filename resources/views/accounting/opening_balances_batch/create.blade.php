@extends('layouts.app')

@section('title', 'Accounting • Batch Input Saldo Awal')

@php
    $oldAcc = old('account_id', []);
    $oldDebit = old('debit', []);
    $oldCredit = old('credit', []);
    $oldNote = old('line_note', []);
    $useOld = is_array($oldAcc) && count($oldAcc);
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .obb-form-page { display: grid; gap: 1rem; }
        .obb-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .obb-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            min-height: 40px; padding: .55rem .95rem; border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, .10); background: #fff;
            color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850;
        }
        .obb-btn:hover { color: #0f172a; background: #f8fafc; }
        .obb-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .obb-btn-primary:hover { color: #fff; background: #1e293b; }
        .obb-btn-danger { color: #b91c1c; border-color: #fecaca; background: #fff5f5; }
        .obb-btn-danger:hover { color: #991b1b; background: #fee2e2; }
        .obb-top-grid { display: grid; grid-template-columns: 220px minmax(0, 1fr) 280px; gap: .75rem; align-items: end; }
        .obb-field .form-control,
        .obb-field .form-select,
        .obb-lines-table .form-control {
            min-height: 40px; border-radius: 12px; border-color: rgba(15, 23, 42, .12);
            box-shadow: none; font-size: .88rem;
        }
        .obb-label {
            display: block; margin-bottom: .32rem; color: #475569;
            font-size: .76rem; font-weight: 900;
        }
        .obb-balance-card {
            border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc;
            padding: .68rem .78rem;
        }
        .obb-balance-label {
            color: #64748b; font-size: .66rem; font-weight: 900;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .obb-balance-value {
            color: #0f172a; font-size: 1rem; font-weight: 950;
            font-variant-numeric: tabular-nums; line-height: 1.15;
        }
        .obb-balance-state {
            display: inline-flex; align-items: center; gap: .35rem; margin-top: .3rem;
            border-radius: 999px; padding: .2rem .55rem; font-size: .72rem; font-weight: 880;
            color: #b45309; background: #fef3c7; border: 1px solid #fde68a;
        }
        .obb-balance-state.is-ok { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
        .obb-error {
            border: 1px solid #fecaca; background: #fef2f2; color: #991b1b;
            border-radius: 12px; padding: .85rem .95rem; font-size: .86rem;
        }
        .obb-table-wrap { overflow: auto; -webkit-overflow-scrolling: touch; }
        .obb-lines-table { min-width: 900px; }
        .obb-lines-table th {
            color: #64748b; font-size: .68rem; font-weight: 950;
            text-transform: uppercase; letter-spacing: .06em;
        }
        .obb-lines-table td { vertical-align: middle; }
        .obb-lines-table .js-debit,
        .obb-lines-table .js-credit { font-weight: 850; font-variant-numeric: tabular-nums; }
        .obb-add-row { display: flex; justify-content: flex-end; }
        .obb-note {
            color: #1e3a8a; background: #eff6ff; border: 1px solid rgba(37, 99, 235, .14);
            border-radius: 12px; padding: .75rem .85rem; font-size: .84rem; font-weight: 720;
        }
        @media (max-width: 768px) {
            .gf-master-header { padding: 12px 14px; border-radius: 14px; }
            .gf-master-title { font-size: 18px; }
            .gf-master-desc { font-size: 11.5px; }
            .gf-master-actions { flex: 1 1 100%; }
            .obb-actions { justify-content: stretch; }
            .obb-actions .obb-btn { flex: 1 1 auto; }
            .obb-top-grid { grid-template-columns: 1fr; }
            .obb-add-row { justify-content: stretch; }
            .obb-add-row .obb-btn { flex: 1 1 auto; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Accounting"
        title="Batch Input Saldo Awal"
        description="Isi banyak akun sekaligus. Total debit dan kredit harus sama sebelum bisa diposting.">
        <x-slot:actions>
            <div class="obb-actions">
                <a href="{{ route('accounting.opening-balances-batch.index') }}" class="obb-btn">Daftar Batch</a>
                <a href="{{ route('accounting.accounts.index') }}" class="obb-btn">Accounts</a>
            </div>
        </x-slot:actions>

        <div class="obb-form-page">
            @if ($errors->any())
                <div class="obb-error">
                    <div class="fw-semibold mb-1">Ada data yang perlu dicek:</div>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                action="{{ route('accounting.opening-balances-batch.store') }}"
                id="ob-form"
                data-gf-confirm
                data-gf-confirm-title="Posting saldo awal batch?"
                data-gf-confirm-text="Sistem akan membuat jurnal posted untuk semua baris yang terisi."
                data-gf-confirm-ok="Ya, posting">
                @csrf

                <x-gf.panel title="Informasi Batch" subtitle="Tanggal ini akan dipakai untuk seluruh baris saldo awal.">
                    <div class="obb-top-grid">
                        <div class="obb-field">
                            <label class="obb-label" for="date">Tanggal</label>
                            <input id="date" type="text" name="date" class="form-control"
                                value="{{ old('date', now()->toDateString()) }}" required data-gf-date>
                        </div>
                        <div class="obb-field">
                            <label class="obb-label" for="description">Deskripsi</label>
                            <input id="description" type="text" name="description" class="form-control"
                                value="{{ old('description', 'Opening Balance (Batch)') }}"
                                placeholder="Opening balance awal sistem">
                        </div>
                        <div class="obb-balance-card">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <div class="obb-balance-label">Debit</div>
                                    <div class="obb-balance-value" id="sum-debit">0</div>
                                </div>
                                <div class="text-end">
                                    <div class="obb-balance-label">Kredit</div>
                                    <div class="obb-balance-value" id="sum-credit">0</div>
                                </div>
                            </div>
                            <div class="obb-balance-state" id="balance-indicator">Belum balance</div>
                        </div>
                    </div>
                </x-gf.panel>

                <x-gf.panel title="Detail Akun" subtitle="Isi debit atau kredit saja di tiap baris. Baris kosong akan dilewati.">
                    <x-slot:actions>
                        <div class="obb-add-row">
                            <button type="button" class="obb-btn" id="btn-add">+ Tambah Baris</button>
                        </div>
                    </x-slot:actions>

                    <div class="obb-note mb-3">
                        Contoh umum: kas/bank di debit, modal atau hutang pembuka di kredit. Untuk cash basis, cukup isi akun yang benar-benar punya saldo awal.
                    </div>

                    <div class="obb-table-wrap">
                        <table class="table table-hover align-middle mb-0 gf-clean-table obb-lines-table">
                            <thead>
                                <tr>
                                    <th style="width: 42%">Akun</th>
                                    <th class="text-end" style="width: 18%">Debit</th>
                                    <th class="text-end" style="width: 18%">Kredit</th>
                                    <th style="width: 17%">Catatan</th>
                                    <th class="text-center" style="width: 5%"></th>
                                </tr>
                            </thead>
                            <tbody id="lines-body">
                                @if ($useOld)
                                    @foreach ($oldAcc as $i => $accountId)
                                        @php
                                            $selectedAccount = $accounts->firstWhere('id', (int) $accountId);
                                        @endphp
                                        <tr>
                                            <td>
                                                <x-account-suggest
                                                    name="account_id[]"
                                                    :value="$accountId"
                                                    :display="$selectedAccount ? $selectedAccount->code . ' - ' . $selectedAccount->name : ''"
                                                    :required="false" />
                                            </td>
                                            <td>
                                                <input name="debit[]" class="form-control text-end js-debit" value="{{ $oldDebit[$i] ?? 0 }}" inputmode="decimal">
                                            </td>
                                            <td>
                                                <input name="credit[]" class="form-control text-end js-credit" value="{{ $oldCredit[$i] ?? 0 }}" inputmode="decimal">
                                            </td>
                                            <td>
                                                <input name="line_note[]" class="form-control" value="{{ $oldNote[$i] ?? '' }}" placeholder="-">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="obb-btn obb-btn-danger js-del" aria-label="Hapus baris">x</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    @for ($i = 0; $i < 4; $i++)
                                        <tr>
                                            <td>
                                                <x-account-suggest name="account_id[]" :required="false" />
                                            </td>
                                            <td>
                                                <input name="debit[]" class="form-control text-end js-debit" value="0" inputmode="decimal">
                                            </td>
                                            <td>
                                                <input name="credit[]" class="form-control text-end js-credit" value="0" inputmode="decimal">
                                            </td>
                                            <td>
                                                <input name="line_note[]" class="form-control" placeholder="-">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="obb-btn obb-btn-danger js-del" aria-label="Hapus baris">x</button>
                                            </td>
                                        </tr>
                                    @endfor
                                @endif
                            </tbody>
                        </table>
                    </div>
                </x-gf.panel>

                <div class="d-flex justify-content-end gap-2 flex-wrap">
                    <a href="{{ route('accounting.opening-balances-batch.index') }}" class="obb-btn">Batal</a>
                    <button class="obb-btn obb-btn-primary" type="submit">Posting Batch</button>
                </div>
            </form>
        </div>
    </x-gf.page>
@endsection

@push('scripts')
    <script>
        (function() {
            const body = document.getElementById('lines-body');
            const btnAdd = document.getElementById('btn-add');
            const sumDebitEl = document.getElementById('sum-debit');
            const sumCreditEl = document.getElementById('sum-credit');
            const balanceEl = document.getElementById('balance-indicator');
            const form = document.getElementById('ob-form');

            if (!body || !btnAdd || !form) return;

            const toNum = (value) => {
                const clean = (value ?? '').toString().replace(/,/g, '.').replace(/[^\d.\-]/g, '');
                const number = parseFloat(clean || '0');
                return Number.isFinite(number) ? number : 0;
            };
            const fmt = (number) => new Intl.NumberFormat('id-ID').format(number);

            function recalc() {
                let debit = 0;
                let credit = 0;

                body.querySelectorAll('tr').forEach((row) => {
                    debit += toNum(row.querySelector('.js-debit')?.value);
                    credit += toNum(row.querySelector('.js-credit')?.value);
                });

                sumDebitEl.textContent = fmt(debit);
                sumCreditEl.textContent = fmt(credit);

                const ok = debit > 0 && Math.round(debit * 100) === Math.round(credit * 100);
                balanceEl.textContent = ok ? 'Balance OK' : 'Belum balance';
                balanceEl.classList.toggle('is-ok', ok);

                return ok;
            }

            function bindRow(row) {
                row.querySelectorAll('.js-debit,.js-credit').forEach((input) => {
                    input.addEventListener('input', () => {
                        if (input.classList.contains('js-debit') && toNum(input.value) > 0) {
                            row.querySelector('.js-credit').value = '0';
                        }
                        if (input.classList.contains('js-credit') && toNum(input.value) > 0) {
                            row.querySelector('.js-debit').value = '0';
                        }
                        recalc();
                    });
                });

                row.querySelector('.js-del')?.addEventListener('click', () => {
                    const rows = body.querySelectorAll('tr');
                    if (rows.length > 2) {
                        row.remove();
                    } else {
                        row.querySelectorAll('input').forEach((input) => {
                            input.value = input.classList.contains('js-debit') || input.classList.contains('js-credit') ? '0' : '';
                        });
                    }
                    recalc();
                });
            }

            body.querySelectorAll('tr').forEach(bindRow);

            btnAdd.addEventListener('click', () => {
                const first = body.querySelector('tr');
                if (!first) return;

                const clone = first.cloneNode(true);
                clone.querySelectorAll('input').forEach((input) => {
                    input.value = input.classList.contains('js-debit') || input.classList.contains('js-credit') ? '0' : '';
                });
                clone.querySelectorAll('.acc-suggest-wrap').forEach((wrap) => wrap.removeAttribute('data-init'));

                body.appendChild(clone);
                bindRow(clone);
                window.initAccountSuggest?.(clone);
                recalc();
            });

            form.addEventListener('submit', (event) => {
                if (recalc()) return;

                event.preventDefault();
                if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Belum balance',
                        text: 'Total debit harus sama dengan total kredit dan tidak boleh 0.',
                        confirmButtonText: 'Cek lagi'
                    });
                } else {
                    alert('Total debit harus sama dengan total kredit dan tidak boleh 0.');
                }
            }, true);

            recalc();
        })();
    </script>
@endpush
