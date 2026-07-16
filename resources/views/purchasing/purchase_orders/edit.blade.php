@extends('layouts.app')

@section('title', 'Edit Purchase Order ' . $order->code)

@push('head')
<style>
    .shp-wrap { max-width: 1120px; margin-inline: auto; padding: 0 .75rem 6rem; }
    .shp-topbar {
        position: sticky; top: 0; z-index: 300;
        display: flex; align-items: center; gap: .6rem; flex-wrap: wrap;
        padding: .65rem .85rem; background: rgba(255,255,255,.97);
        border-bottom: 1px solid rgba(148,163,184,.22);
        backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
    }
    body[data-theme="dark"] .shp-topbar {
        background: rgba(2,6,23,.96);
        border-bottom-color: rgba(30,64,175,.45);
    }
    .shp-topbar-code { font-weight: 900; font-size: 1.05rem; letter-spacing: .04em; white-space: nowrap; }
    body[data-theme="dark"] .shp-topbar-code { color: #e5e7eb; }
    .shp-badge {
        border-radius: 999px; padding: .15rem .65rem; font-size: .7rem;
        letter-spacing: .08em; text-transform: uppercase; white-space: nowrap;
        background: rgba(251,191,36,.1); color: #92400e; border: 1px solid rgba(245,158,11,.28);
    }
    .shp-topbar-spacer { flex: 1; min-width: .5rem; }
    
    .btn-shp-submit {
        border-radius: 7px; font-size: .85rem; font-weight: 600;
        text-transform: none; padding: .35rem .75rem; letter-spacing: 0;
        border: 1px solid #334155; background: #334155; color: #fff;
        transition: all .12s; white-space: nowrap; box-shadow: none;
    }
    .btn-shp-submit:hover { background: #1f2937; border-color: #1f2937; color: #fff; }
    .btn-shp-outline {
        border-radius: 7px; font-size: .85rem; font-weight: 600;
        text-transform: none; padding: .35rem .75rem; letter-spacing: 0;
        border: 1px solid rgba(148,163,184,.35); background: transparent; color: #475569;
        white-space: nowrap; transition: all .12s; box-shadow: none;
    }
    .btn-shp-outline:hover { background: rgba(226,232,240,.7); color: #334155; }
</style>
@endpush

@section('content')
    <form id="po-edit-form" action="{{ route('purchasing.purchase_orders.update', $order->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="shp-topbar mb-3">
            <div class="shp-topbar-code">Edit PO {{ $order->code }}</div>
            @if ($order->status === 'draft')
                <span class="shp-badge">Draft</span>
            @endif
        </div>

        <div class="shp-wrap pt-2">
            @include('purchasing.purchase_orders._form')
            
            <div class="d-flex justify-content-end gap-2 mt-4 mb-2">
                <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}" class="btn-shp-outline text-decoration-none">
                    Batal
                </a>
                <button type="submit" class="btn-shp-submit">
                    Simpan PO
                </button>
            </div>
        </div>
    </form>

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
