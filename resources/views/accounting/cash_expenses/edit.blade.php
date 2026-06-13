@extends('layouts.app')

@section('title', 'Accounting • Edit Pengeluaran')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .ce-edit-page { display: grid; gap: 1rem; }
        .ce-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .ce-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            min-height: 40px; padding: .55rem .95rem; border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, .10); background: #fff;
            color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850;
        }
        .ce-btn:hover { color: #0f172a; background: #f8fafc; }
        .ce-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .ce-btn-primary:hover { color: #fff; background: #1e293b; }
        .ce-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
        .ce-field { display: grid; gap: .32rem; margin: 0; }
        .ce-field-full { grid-column: 1 / -1; }
        .ce-field > span {
            color: #64748b; font-size: .75rem; font-weight: 900;
            text-transform: uppercase; letter-spacing: .04em;
        }
        .ce-field small { color: #94a3b8; font-weight: 800; text-transform: none; letter-spacing: 0; }
        .ce-form-control {
            min-height: 42px; border-radius: 12px; border-color: rgba(15, 23, 42, .12);
            box-shadow: none; font-size: .88rem;
        }
        .ce-form-error {
            padding: .75rem .85rem; border-radius: 12px;
            border: 1px solid rgba(239, 68, 68, .24);
            background: rgba(239, 68, 68, .08); color: #991b1b; font-size: .86rem;
        }
        .ce-form-error ul { margin: .35rem 0 0; padding-left: 1.1rem; }
        @media (max-width: 768px) {
            .gf-master-header { padding: 12px 14px; border-radius: 14px; }
            .gf-master-title { font-size: 18px; }
            .gf-master-desc { font-size: 11.5px; }
            .gf-master-actions { flex: 1 1 100%; }
            .ce-actions { justify-content: stretch; }
            .ce-actions .ce-btn { flex: 1 1 auto; }
            .ce-form-grid { grid-template-columns: 1fr; }
        }
    
/* AUTO MOBILE FULL PROOF BUTTON */
.ce-owner-proof-head:empty {
    display: none;
}

@media (max-width: 768px) {
    .ce-owner-proof-actions {
        width: 100%;
    }

    .ce-owner-proof-btn {
        width: 100%;
        display: flex;
        justify-content: center;
        text-align: center;
    }
}

</style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Accounting"
        title="Edit Pengeluaran"
        description="Hanya transaksi Draft yang bisa diedit sebelum diposting ke jurnal.">
        <x-slot:actions>
            <div class="ce-actions">
                <a href="{{ route('accounting.cash-expenses.show', $cashExpense) }}" class="ce-btn">Detail</a>
                <a href="{{ route('accounting.cash-expenses.index') }}" class="ce-btn">Daftar Pengeluaran</a>
            </div>
        </x-slot:actions>

        <div class="ce-edit-page">
            <x-gf.panel title="Form Pengeluaran" subtitle="Update data draft, lalu posting dari halaman detail.">
                <form method="POST"
                    action="{{ route('accounting.cash-expenses.update', $cashExpense) }}"
                    enctype="multipart/form-data"
                    data-gf-confirm
                    data-gf-confirm-title="Update draft?"
                    data-gf-confirm-text="Perubahan pengeluaran draft akan disimpan."
                    data-gf-confirm-ok="Ya, update">
                    @csrf
                    @method('PUT')

                    @include('accounting.cash_expenses._form', ['cashExpense' => $cashExpense])

                    <div class="d-flex justify-content-end gap-2 flex-wrap mt-3">
                        <a href="{{ route('accounting.cash-expenses.show', $cashExpense) }}" class="ce-btn">Batal</a>
                        <button class="ce-btn ce-btn-primary" type="submit">Update Draft</button>
                    </div>
                </form>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection

{{-- AUTO SELECT ALL CASH EXPENSE NOMINAL --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectors = [
        'input[name="amount"]',
        'input[name="nominal"]',
        'input[name="jumlah"]',
        'input[name="total"]',
        'input[data-ce-amount]',
        'input[data-amount]'
    ];

    function getNominalInput(scope) {
        scope = scope || document;
        return scope.querySelector(selectors.join(','));
    }

    function cleanNominal(input) {
        if (!input) return;

        let value = String(input.value || '').trim();

        // Hilangkan .00 dari nominal
        value = value.replace(/\.00$/, '');

        // Jangan izinkan desimal/koma
        value = value.replace(/[^\d]/g, '');

        input.value = value;
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('pattern', '[0-9]*');
        input.setAttribute('step', '1');
        input.setAttribute('autocomplete', 'off');
    }

    function focusAndSelectNominal(scope) {
        const input = getNominalInput(scope || document);
        if (!input) return;

        cleanNominal(input);

        input.removeAttribute('readonly');
        input.removeAttribute('disabled');

        setTimeout(function () {
            input.focus({ preventScroll: false });
            input.click();

            // Select all nominal supaya langsung bisa ditimpa
            try {
                input.select();
                input.setSelectionRange(0, input.value.length);
            } catch (e) {}
        }, 300);
    }

    document.querySelectorAll(selectors.join(',')).forEach(function (input) {
        cleanNominal(input);

        input.addEventListener('focus', function () {
            setTimeout(function () {
                try {
                    input.select();
                    input.setSelectionRange(0, input.value.length);
                } catch (e) {}
            }, 50);
        });

        input.addEventListener('click', function () {
            setTimeout(function () {
                try {
                    input.select();
                    input.setSelectionRange(0, input.value.length);
                } catch (e) {}
            }, 50);
        });

        input.addEventListener('input', function () {
            cleanNominal(input);
        });

        input.addEventListener('blur', function () {
            cleanNominal(input);
        });
    });

    // Bootstrap modal
    document.addEventListener('shown.bs.modal', function (event) {
        focusAndSelectNominal(event.target);
    });

    // Modal custom / tombol buka modal
    document.querySelectorAll(
        '[data-bs-toggle="modal"], [data-toggle="modal"], [data-ce-open-modal], button, a'
    ).forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            setTimeout(function () {
                const openedModal =
                    document.querySelector('.modal.show') ||
                    document.querySelector('[role="dialog"][aria-modal="true"]') ||
                    document.querySelector('.ce-modal');

                focusAndSelectNominal(openedModal || document);
            }, 350);
        });
    });

    // Halaman edit/create biasa, bukan modal
    setTimeout(function () {
        const modalOpen =
            document.querySelector('.modal.show') ||
            document.querySelector('[role="dialog"][aria-modal="true"]');

        if (!modalOpen) {
            focusAndSelectNominal(document);
        }
    }, 400);
});
</script>

{{-- AUTO HIDE EDIT REFERENCE NOTE PROOF INPUT --}}
<style>
/* Hilangkan kolom referensi & catatan */
input[name="reference_no"],
input[name="ref_no"],
input[name="no_reference"],
input[name="no_referensi"],
input[name="note"],
input[name="notes"],
textarea[name="reference_no"],
textarea[name="ref_no"],
textarea[name="no_reference"],
textarea[name="no_referensi"],
textarea[name="note"],
textarea[name="notes"] {
    display: none;
}

/* Hilangkan upload/camera bukti foto di halaman edit */
input[name="proof_photo"],
[data-ce-proof-input],
[data-ce-proof-trigger],
[data-ce-proof-name] {
    display: none !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const hiddenFields = [
        'input[name="reference_no"]',
        'input[name="ref_no"]',
        'input[name="no_reference"]',
        'input[name="no_referensi"]',
        'input[name="note"]',
        'input[name="notes"]',
        'textarea[name="reference_no"]',
        'textarea[name="ref_no"]',
        'textarea[name="no_reference"]',
        'textarea[name="no_referensi"]',
        'textarea[name="note"]',
        'textarea[name="notes"]'
    ];

    document.querySelectorAll(hiddenFields.join(',')).forEach(function (el) {
        el.required = false;
        el.disabled = true;

        const wrap = el.closest('.ce-field, .mb-3, .form-group, .col, .row, label, div');
        if (wrap) wrap.style.display = 'none';
    });

    document.querySelectorAll('input[name="proof_photo"], [data-ce-proof-input]').forEach(function (input) {
        input.required = false;
        input.disabled = true;

        const wrap = input.closest('.ce-field, .mb-3, .form-group, label, div');
        if (!wrap) return;

        wrap.querySelectorAll('[data-ce-proof-trigger], [data-ce-proof-name], [data-open-camera-btn], [data-proof-file-name]').forEach(function (el) {
            el.remove();
        });

        input.style.display = 'none';

        if (!wrap.querySelector('[data-proof-view-only]')) {
            const proofUrl = @json($cashExpense->proof_photo_path ? route('accounting.cash-expenses.proof', $cashExpense) : null);

            const box = document.createElement('div');
            box.setAttribute('data-proof-view-only', '1');
            box.className = 'mt-2';

            if (proofUrl) {
                box.innerHTML = `
                    <a href="${proofUrl}" target="_blank" class="btn btn-light border fw-semibold">
                        Lihat Bukti Foto
                    </a>
                `;
            } else {
                box.innerHTML = `
                    <small class="text-muted">Belum ada bukti foto.</small>
                `;
            }

            wrap.appendChild(box);
        }
    });
});
</script>

{{-- AUTO OWNER FRIENDLY PROOF CARD --}}
<style>
.ce-owner-proof-card {
    border: 1px solid rgba(15, 23, 42, .10);
    border-radius: 14px;
    padding: 12px;
    margin-bottom: 12px;
    background: #fff;
}

.ce-owner-proof-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 0;
}

.ce-owner-proof-title {
    font-weight: 850;
    font-size: 14px;
    color: #0f172a;
    margin: 0;
}

.ce-owner-proof-subtitle {
    color: #64748b;
    font-size: 12px;
    margin-top: 2px;
}

.ce-owner-proof-badge {
    font-size: 11px;
    font-weight: 800;
    padding: 5px 9px;
    border-radius: 999px;
    background: #ecfdf5;
    color: #047857;
    white-space: nowrap;
}

.ce-owner-proof-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.ce-owner-proof-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    padding: 8px 12px;
    font-weight: 800;
    text-decoration: none;
    border: 1px solid rgba(15, 23, 42, .14);
    background: #0f172a;
    color: #fff;
}

.ce-owner-proof-btn:hover {
    color: #fff;
    opacity: .92;
}

.ce-owner-proof-empty {
    color: #b45309;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 12px;
    padding: 10px 12px;
    font-size: 13px;
    font-weight: 700;
}

/* Sembunyikan upload bukti di halaman edit: owner hanya cek bukti */
input[name="proof_photo"],
[data-ce-proof-input],
[data-ce-proof-trigger],
[data-ce-proof-name],
[data-open-camera-btn],
[data-proof-file-name] {
    display: none !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const proofUrl = @json($cashExpense->proof_photo_path ? route('accounting.cash-expenses.proof', $cashExpense) : null);

    function findDateField() {
        return document.querySelector(
            'input[name="date"], input[name="expense_date"], input[name="transaction_date"], input[type="date"]'
        );
    }

    function findDateWrap() {
        const dateInput = findDateField();
        if (!dateInput) return null;

        return dateInput.closest('.ce-field, .mb-3, .form-group, .col, .row, label, div');
    }

    function removeOldProofUpload() {
        document.querySelectorAll('input[name="proof_photo"], [data-ce-proof-input]').forEach(function (input) {
            input.required = false;
            input.disabled = true;

            const wrap = input.closest('.ce-field, .mb-3, .form-group, label, div');
            if (!wrap) return;

            wrap.style.display = 'none';
        });

        document.querySelectorAll('[data-ce-proof-trigger], [data-ce-proof-name], [data-open-camera-btn], [data-proof-file-name]').forEach(function (el) {
            el.remove();
        });
    }

    function makeProofCard() {
        let card = document.querySelector('[data-owner-proof-card]');
        if (card) return card;

        card = document.createElement('div');
        card.className = 'ce-owner-proof-card';
        card.setAttribute('data-owner-proof-card', '1');

        if (proofUrl) {
            card.innerHTML = `
                <div class="ce-owner-proof-head">
                    <div>
                        
                        
                    </div>
                    
                </div>

                <div class="ce-owner-proof-actions">
                    <a class="ce-owner-proof-btn" href="${proofUrl}" target="_blank" rel="noopener">
                        Lihat Bukti
                    </a>
                    
                </div>
            `;
        } else {
            card.innerHTML = `
                <div class="ce-owner-proof-head">
                    <div>
                        
                        
                    </div>
                    <div class="ce-owner-proof-badge" style="background:#fffbeb;color:#b45309;">Belum ada</div>
                </div>

                <div class="ce-owner-proof-empty">Belum ada bukti foto.</div>
            `;
        }

        return card;
    }

    removeOldProofUpload();

    const card = makeProofCard();
    const dateWrap = findDateWrap();

    if (dateWrap && dateWrap.parentNode) {
        dateWrap.parentNode.insertBefore(card, dateWrap);
    } else {
        const form = document.querySelector('form');
        if (form) {
            form.insertBefore(card, form.firstChild);
        }
    }
});
</script>

{{-- AUTO UPDATE PROOF PHOTO BUTTON --}}
<style>
.ce-update-proof-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    padding: 8px 12px;
    font-weight: 800;
    text-decoration: none;
    border: 1px solid rgba(15, 23, 42, .14);
    background: #fff;
    color: #0f172a;
    margin-top: 8px;
}

.ce-update-proof-name {
    display: block;
    margin-top: 6px;
    font-size: 12px;
    color: #64748b;
}

@media (max-width: 768px) {
    .ce-update-proof-btn {
        width: 100%;
        display: flex;
    }

    .ce-owner-proof-actions {
        display: grid;
        width: 100%;
        gap: 8px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const proofInput = document.querySelector('input[name="proof_photo"], [data-ce-proof-input]');
    const proofActions = document.querySelector('.ce-owner-proof-actions');

    if (!proofInput || !proofActions) return;

    proofInput.disabled = false;
    proofInput.required = false;
    proofInput.style.position = 'absolute';
    proofInput.style.left = '-9999px';
    proofInput.style.width = '1px';
    proofInput.style.height = '1px';
    proofInput.style.opacity = '0';
    proofInput.style.display = 'block';

    if (!proofActions.querySelector('[data-update-proof-photo]')) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ce-update-proof-btn';
        btn.setAttribute('data-update-proof-photo', '1');
        btn.textContent = 'Update Foto';

        const name = document.createElement('small');
        name.className = 'ce-update-proof-name';
        name.setAttribute('data-update-proof-name', '1');
        name.textContent = '';

        btn.addEventListener('click', function () {
            proofInput.click();
        });

        proofInput.addEventListener('change', function () {
            if (proofInput.files && proofInput.files.length > 0) {
                name.textContent = 'Foto baru: ' + proofInput.files[0].name;
            } else {
                name.textContent = '';
            }
        });

        proofActions.appendChild(btn);
        proofActions.appendChild(name);
    }
});
</script>
