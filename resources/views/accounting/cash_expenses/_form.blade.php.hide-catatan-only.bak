@php
    use Illuminate\Support\Facades\Storage;

    $isEdit = isset($cashExpense) && $cashExpense->exists;
    $selectedExpenseAccountId = old('expense_account_id', $cashExpense->expense_account_id ?? 0);
    $defaultCashAccountId = $cashAccounts->firstWhere('code', '1101')?->id ?? $cashAccounts->first()?->id;
    $selectedCashAccountId = old('cash_account_id', $cashExpense->cash_account_id ?? ($isEdit ? 0 : $defaultCashAccountId));
    $newCategoryName = old('category_new');
@endphp

<div class="ce-form-grid">
    <label class="ce-field">
        <span>Tanggal</span>
        <input class="form-control ce-form-control" type="text" name="date" data-ce-date
            value="{{ old('date', optional($cashExpense->date ?? null)->toDateString() ?: now()->toDateString()) }}"
            required autocomplete="off" tabindex="4">
    </label>

    <label class="ce-field">
        <span>Nominal</span>
        <div class="input-group input-group-lg ce-amount-group">
            <span class="input-group-text bg-white fw-black">Rp</span>
            <input class="form-control ce-form-control ce-amount-input" type="number" step="1" min="0" inputmode="numeric" name="amount"
                value="{{ old('amount', $cashExpense->amount ?? null) }}" required placeholder="0" autofocus tabindex="1"
                data-ce-amount>
        </div>
    </label>

    <label class="ce-field">
        <span>Kategori Pengeluaran</span>
        <input type="hidden" name="category_new" value="{{ $newCategoryName }}" data-ce-new-category-hidden>
        <select class="form-select ce-form-control" name="expense_account_id" required tabindex="2" data-ce-category>
            <option value="">Pilih kategori</option>
            @foreach ($expenseAccounts as $a)
                <option value="{{ $a->id }}" @selected((int) $selectedExpenseAccountId === (int) $a->id)>
                    {{ $a->name }}{{ $a->code ? " · {$a->code}" : '' }}
                </option>
            @endforeach
            @if ($selectedExpenseAccountId === '__new_category__' && $newCategoryName)
                <option value="__new_category__" selected>{{ $newCategoryName }} · kategori baru</option>
            @endif
            <option value="__open_modal__">+ Tambah kategori baru</option>
        </select>
    </label>

    <label class="ce-field">
        <span>Bayar dari</span>
        <select class="form-select ce-form-control" name="cash_account_id" required tabindex="5">
            <option value="">Pilih kas / bank</option>
            @foreach ($cashAccounts as $a)
                <option value="{{ $a->id }}" @selected((int) $selectedCashAccountId === (int) $a->id)>
                    {{ $a->name }}{{ $a->code ? " · {$a->code}" : '' }}
                </option>
            @endforeach
        </select>
    </label>

    <label class="ce-field ce-field-full">
        <span>Keterangan</span>
        <input class="form-control ce-form-control" type="text" name="description"
            value="{{ old('description', $cashExpense->description ?? null) }}" maxlength="255"
            placeholder="contoh: Beli kardus, bensin, ongkir" tabindex="3" data-format-sentence-case data-ce-description>
    </label>

    <label class="ce-field ce-field-full">
        <span>No. Referensi <small>opsional</small></span>
        <input class="form-control ce-form-control" type="text" name="reference"
            value="{{ old('reference', $cashExpense->reference ?? null) }}" maxlength="100"
            placeholder="contoh: INV/STRUK/NO NOTA" tabindex="6">
    </label>

    <label class="ce-field ce-field-full">
        <span>Bukti Foto {{ $isEdit && ($cashExpense->proof_photo_path ?? null) ? 'opsional jika tidak diganti' : '' }}</span>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-light border fw-semibold" data-ce-proof-trigger tabindex="7">
                Buka Kamera / Pilih Foto
            </button>
            <small class="text-muted" data-ce-proof-name>Belum ada foto dipilih</small>
        </div>
        <input class="form-control ce-form-control mt-2" type="file" name="proof_photo"
            accept="image/*" capture="environment" data-ce-proof-input
            {{ $isEdit && ($cashExpense->proof_photo_path ?? null) ? '' : 'required' }} tabindex="-1">
        @if ($isEdit && ($cashExpense->proof_photo_path ?? null))
            <small>
                Foto saat ini:
                <a href="{{ Storage::disk('public')->url($cashExpense->proof_photo_path) }}" target="_blank" rel="noopener">
                    lihat bukti
                </a>
            </small>
        @else
            <small>Wajib upload foto struk/bukti pembayaran.</small>
        @endif
    </label>

    <label class="ce-field ce-field-full">
        <span>Catatan <small>opsional</small></span>
        <textarea class="form-control ce-form-control" name="notes" rows="3" placeholder="Catatan tambahan..." tabindex="8" data-format-sentence-case>{{ old('notes', $cashExpense->notes ?? null) }}</textarea>
    </label>

    @if ($errors->any())
        <div class="ce-form-error ce-field-full">
            <b>Periksa lagi:</b>
            <ul>
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<div class="modal fade ce-category-modal" id="cashExpenseCategoryModal" tabindex="-1"
    aria-labelledby="cashExpenseCategoryModalLabel" aria-hidden="true" data-ce-category-modal>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div>
                    <div class="ce-modal-sub">Kategori Baru</div>
                    <h5 class="modal-title fw-black" id="cashExpenseCategoryModalLabel">
                        Tambah Kategori Pengeluaran
                    </h5>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <label class="form-label small fw-semibold mb-1">Nama kategori</label>
                <input type="text" class="form-control form-control-lg ce-form-control"
                    placeholder="Contoh: Bonus Karyawan" autocomplete="off" data-ce-new-category-input>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="ce-btn" data-bs-dismiss="modal">
                    Batal
                </button>

                <button type="button" class="ce-btn ce-btn-primary" data-ce-save-new-category>
                    Pakai Kategori
                </button>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function() {
                function sentenceCase(value) {
                    value = (value || '').trimStart();
                    return value ? value.charAt(0).toUpperCase() + value.slice(1) : value;
                }

                function initCashExpenseModalBehavior(root) {
                    const scope = root || document;

                    scope.querySelectorAll('[data-format-sentence-case]:not([data-ce-case-bound])').forEach((input) => {
                        input.dataset.ceCaseBound = '1';
                        input.addEventListener('blur', () => {
                            input.value = sentenceCase(input.value);
                        });
                    });

                    scope.querySelectorAll('[data-ce-proof-trigger]:not([data-ce-proof-bound])').forEach((button) => {
                        button.dataset.ceProofBound = '1';
                        const form = button.closest('form');
                        const input = form?.querySelector('[data-ce-proof-input]');

                        button.addEventListener('click', () => {
                            input?.click();
                        });
                    });

                    scope.querySelectorAll('[data-ce-proof-input]:not([data-ce-proof-input-bound])').forEach((input) => {
                        input.dataset.ceProofInputBound = '1';
                        const form = input.closest('form');
                        const name = form?.querySelector('[data-ce-proof-name]');

                        input.addEventListener('change', () => {
                            if (!name) return;
                            name.textContent = input.files?.[0]?.name || 'Belum ada foto dipilih';
                        });
                    });

                    scope.querySelectorAll('[data-ce-category]:not([data-ce-category-bound])').forEach((select) => {
                        select.dataset.ceCategoryBound = '1';

                        const form = select.closest('form');
                        const hidden = form?.querySelector('[data-ce-new-category-hidden]');
                        const description = form?.querySelector('[data-ce-description]');
                        const modalEl = form?.querySelector('[data-ce-category-modal]') || document.querySelector('[data-ce-category-modal]');
                        const input = modalEl?.querySelector('[data-ce-new-category-input]');
                        const save = modalEl?.querySelector('[data-ce-save-new-category]');
                        const modal = modalEl && window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(modalEl) : null;
                        let previousValue = select.value || '';

                        select.addEventListener('focus', () => {
                            previousValue = select.value || '';
                        });

                        select.addEventListener('change', () => {
                            if (select.value !== '__open_modal__') {
                                previousValue = select.value || '';
                                return;
                            }

                            select.value = previousValue;
                            if (!modal || !input) return;

                            input.value = hidden?.value || '';
                            modal.show();
                            modalEl.addEventListener('shown.bs.modal', () => {
                                input.focus();
                                input.select?.();
                            }, { once: true });
                        });

                        save?.addEventListener('click', () => {
                            const name = sentenceCase(input?.value || '').trim();
                            if (!name) {
                                input?.focus();
                                return;
                            }

                            let option = Array.from(select.options).find((opt) => opt.value === '__new_category__');
                            if (!option) {
                                option = new Option('', '__new_category__');
                                select.add(option, select.options[select.options.length - 1] || null);
                            }

                            option.textContent = `${name} · kategori baru`;
                            option.selected = true;
                            if (hidden) hidden.value = name;
                            previousValue = '__new_category__';
                            modal?.hide();

                            setTimeout(() => {
                                description?.focus();
                            }, 180);
                        });

                        input?.addEventListener('keydown', (event) => {
                            if (event.key !== 'Enter') return;
                            event.preventDefault();
                            save?.click();
                        });
                    });
                }

                document.addEventListener('DOMContentLoaded', () => {
                    initCashExpenseModalBehavior(document);
                    const firstAmount = document.querySelector('#cashExpenseCreateModal [data-ce-amount]');
                    document.getElementById('cashExpenseCreateModal')?.addEventListener('shown.bs.modal', () => {
                        firstAmount?.focus();
                        firstAmount?.select?.();
                    });
                });

                window.GFID = window.GFID || {};
                window.GFID.initCashExpenseModalBehavior = initCashExpenseModalBehavior;
            })();
        </script>
    @endpush
@endonce
