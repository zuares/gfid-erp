@extends('layouts.app')

@section('title', 'Edit Purchase Order ' . $order->code)

@push('head')
<style>
    .po-edit-shell {
        max-width: 1120px;
        margin-inline: auto;
        padding-bottom: calc(72px + 4rem);
    }
    .po-edit-title {
        font-size: 1.35rem;
        font-weight: 900;
        letter-spacing: -.02em;
    }
    /* FAB */
    .po-fab-wrap {
        position: fixed;
        right: 14px;
        bottom: calc(72px + 12px + env(safe-area-inset-bottom));
        z-index: 1090;
        display: flex;
        gap: 10px;
        align-items: center;
        pointer-events: none;
    }
    .po-fab-wrap .btn { pointer-events: auto; }
    .po-fab-back {
        width: 36px; height: 36px;
        padding: 0;
        border-radius: 999px;
        font-weight: 700;
        font-size: .88rem;
        background: rgba(255,255,255,.72);
        border-color: rgba(148,163,184,.35);
        color: #64748b;
        box-shadow: 0 4px 12px rgba(15,23,42,.10);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }
    .po-fab-back:hover { background: rgba(255,255,255,.92); color: #0f172a; }
    .po-fab-save {
        border-radius: 999px;
        border: none;
        padding: .52rem 1.15rem;
        font-size: .88rem;
        font-weight: 700;
        background: linear-gradient(135deg, #0d6efd 0%, #2563eb 60%, #1d4ed8 100%);
        color: #f9fafb;
        box-shadow:
            0 12px 24px rgba(15,23,42,.32),
            0 0 0 1px rgba(191,219,254,.85);
        display: inline-flex;
        align-items: center;
        gap: .38rem;
        white-space: nowrap;
    }
    .po-fab-save:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff;
    }
</style>
@endpush

@section('content')
    <div class="container py-3">
        <div class="po-edit-shell">
            <h1 class="po-edit-title mb-3">Edit PO {{ $order->code }}</h1>

            <form id="po-edit-form" action="{{ route('purchasing.purchase_orders.update', $order->id) }}" method="POST">
                @csrf
                @method('PUT')

                @include('purchasing.purchase_orders._form')

                {{-- Desktop: action bar di dalam form --}}
                <div class="d-none d-md-flex justify-content-between align-items-center mt-3 mb-2">
                    <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}" class="btn btn-outline-secondary">
                        &larr; Batal
                    </a>
                    <button type="submit" class="btn btn-primary px-4">
                        Simpan Perubahan
                    </button>
                </div>

            </form>
        </div>
    </div>

    {{-- Mobile FAB --}}
    <div class="po-fab-wrap d-md-none">
        <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
            class="btn btn-outline-secondary po-fab-back" title="Batal">
            ←
        </a>
        <button type="button" class="po-fab-save" id="po-fab-submit">
            <i class="bi bi-check2-circle"></i>
            Simpan Perubahan
        </button>
    </div>

    @push('scripts')
    <script>
        document.getElementById('po-fab-submit')?.addEventListener('click', function() {
            const form = document.getElementById('po-edit-form');
            if (!form) return;
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    </script>
    @endpush
@endsection
