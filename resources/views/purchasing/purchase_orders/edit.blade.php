@extends('layouts.app')

@section('title', 'Edit Purchase Order ' . $order->code)

@push('head')
<style>
    .po-edit-shell {
        max-width: 1120px;
        margin-inline: auto;
        padding-bottom: 5.5rem;
    }
    .po-edit-title {
        font-size: 1.35rem;
        font-weight: 900;
        letter-spacing: -.02em;
    }
    .po-edit-actions {
        position: fixed;
        left: 0; right: 0; bottom: 0;
        z-index: 1040;
        display: flex;
        gap: .65rem;
        padding: .65rem 1rem calc(.65rem + env(safe-area-inset-bottom));
        background: color-mix(in srgb, var(--card) 96%, transparent);
        border-top: 1px solid var(--line);
        box-shadow: 0 -8px 24px rgba(15,23,42,.08);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }
    .po-edit-actions .btn {
        min-height: 44px;
        border-radius: 12px;
        font-weight: 850;
    }
    .po-edit-actions .btn-outline-secondary {
        flex: 0 0 auto;
        width: 44px;
        padding-inline: 0;
        font-size: 0;
    }
    .po-edit-actions .btn-outline-secondary::before {
        content: "←";
        font-size: 1.05rem;
    }
    .po-edit-actions .btn-primary {
        flex: 1 1 auto;
    }
    @media (min-width: 768px) {
        .po-edit-actions {
            max-width: 1120px;
            left: 50%; right: auto;
            transform: translateX(-50%);
            width: 100%;
            border-radius: 0;
        }
    }
</style>
@endpush

@section('content')
    <div class="container py-3">
        <div class="po-edit-shell">
            <h1 class="po-edit-title mb-3">Edit PO {{ $order->code }}</h1>

            <form action="{{ route('purchasing.purchase_orders.update', $order->id) }}" method="POST">
                @csrf
                @method('PUT')

                @include('purchasing.purchase_orders._form')
            </form>
        </div>
    </div>

    {{-- Floating actions — di luar form, submit via JS --}}
    <div class="po-edit-actions">
        <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
            class="btn btn-outline-secondary" title="Batal">
        </a>
        <button type="button" class="btn btn-primary"
            onclick="document.querySelector('.po-edit-actions').closest('section, div') && document.querySelector('form[method=POST]').requestSubmit()">
            Simpan Perubahan
        </button>
    </div>

    @push('scripts')
    <script>
        document.querySelector('.po-edit-actions .btn-primary').addEventListener('click', function() {
            const form = document.querySelector('form[action*="purchase-orders"]');
            if (form) form.requestSubmit();
        });
    </script>
    @endpush
@endsection
