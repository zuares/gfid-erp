@extends('layouts.app')

@section('title', 'Accounting • Edit Penerimaan')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .cr-edit-page { display: grid; gap: 1rem; }
        .cr-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; }
        .cr-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            min-height: 40px; padding: .55rem .95rem; border-radius: 999px;
            border: 1px solid rgba(15, 23, 42, .10); background: #fff;
            color: #0f172a; text-decoration: none; font-size: .84rem; font-weight: 850;
        }
        .cr-btn:hover { color: #0f172a; background: #f8fafc; }
        .cr-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .cr-btn-primary:hover { color: #fff; background: #1e293b; }
        .cr-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .8rem; }
        .cr-field { display: grid; gap: .32rem; margin: 0; }
        .cr-field-full { grid-column: 1 / -1; }
        .cr-field > span { color: #64748b; font-size: .75rem; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; }
        .cr-field small { color: #94a3b8; font-weight: 800; text-transform: none; letter-spacing: 0; }
        .cr-form-control { min-height: 42px; border-radius: 12px; border-color: rgba(15, 23, 42, .12); box-shadow: none; font-size: .88rem; }
        .cr-form-error { padding: .75rem .85rem; border-radius: 12px; border: 1px solid rgba(239, 68, 68, .24); background: rgba(239, 68, 68, .08); color: #991b1b; font-size: .86rem; }
        .cr-form-error ul { margin: .35rem 0 0; padding-left: 1.1rem; }
        @media (max-width: 768px) {
            .gf-master-header { padding: 12px 14px; border-radius: 14px; }
            .gf-master-title { font-size: 18px; }
            .gf-master-desc { font-size: 11.5px; }
            .gf-master-actions { flex: 1 1 100%; }
            .cr-actions { justify-content: stretch; }
            .cr-actions .cr-btn { flex: 1 1 auto; }
            .cr-form-grid { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@section('content')
    <x-gf.page
        eyebrow="Accounting"
        title="Edit Penerimaan"
        description="Hanya transaksi Draft yang bisa diedit sebelum diposting ke jurnal.">
        <x-slot:actions>
            <div class="cr-actions">
                <a href="{{ route('accounting.cash-receipts.show', $cashReceipt) }}" class="cr-btn">Detail</a>
                <a href="{{ route('accounting.cash-receipts.index') }}" class="cr-btn">Daftar Penerimaan</a>
            </div>
        </x-slot:actions>

        <div class="cr-edit-page">
            <x-gf.panel title="Form Penerimaan" subtitle="Update data draft, lalu posting dari halaman detail.">
                <form method="POST"
                    action="{{ route('accounting.cash-receipts.update', $cashReceipt) }}"
                    data-gf-confirm
                    data-gf-confirm-title="Update draft?"
                    data-gf-confirm-text="Perubahan penerimaan draft akan disimpan."
                    data-gf-confirm-ok="Ya, update">
                    @csrf
                    @method('PUT')

                    @include('accounting.cash_receipts._form', ['cashReceipt' => $cashReceipt])

                    <div class="d-flex justify-content-end gap-2 flex-wrap mt-3">
                        <a href="{{ route('accounting.cash-receipts.show', $cashReceipt) }}" class="cr-btn">Batal</a>
                        <button class="cr-btn cr-btn-primary" type="submit">Update Draft</button>
                    </div>
                </form>
            </x-gf.panel>
        </div>
    </x-gf.page>
@endsection

{{-- AUTO SWEETALERT CASH RECEIPT PAGE --}}
<style>
@media (max-width: 768px) {
    .cr-actions .cr-btn,
    .cr-actions form,
    .cr-actions form .cr-btn,
    .cr-btn {
        width: 100%;
    }

    .d-flex.justify-content-end.gap-2.flex-wrap.mt-3 {
        display: grid !important;
        width: 100%;
    }

    .d-flex.justify-content-end.gap-2.flex-wrap.mt-3 .cr-btn {
        width: 100%;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function ready(callback) {
        if (window.Swal) {
            callback();
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        script.onload = callback;
        document.head.appendChild(script);
    }

    ready(function () {
        @if (session('message'))
            Swal.fire({
                icon: @json(session('status') === 'error' ? 'error' : 'success'),
                title: @json(session('status') === 'error' ? 'Gagal' : 'Berhasil'),
                text: @json(session('message')),
                confirmButtonText: 'OK'
            });
        @endif

        document.querySelectorAll('form[data-gf-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const title = form.getAttribute('data-gf-confirm-title') || 'Lanjutkan?';
                const text = form.getAttribute('data-gf-confirm-text') || 'Pastikan data sudah benar.';
                const icon = form.getAttribute('data-gf-confirm-icon') || 'question';
                const ok = form.getAttribute('data-gf-confirm-ok') || 'Ya, lanjutkan';

                Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    showCancelButton: true,
                    confirmButtonText: ok,
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.removeAttribute('data-gf-confirm');
                        form.submit();
                    }
                });
            });
        });
    });
});
</script>

