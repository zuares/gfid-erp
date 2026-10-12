@extends('layouts.app')

@section('title', 'PR Baru')

@push('head')
<style>
    .pr-page {
        max-width: 1120px;
        margin-inline: auto;
        padding: 0 .75rem 6rem;
    }
    .pr-topbar {
        position: sticky;
        top: 0;
        z-index: 300;
        display: flex;
        align-items: center;
        gap: .6rem;
        flex-wrap: wrap;
        padding: .65rem .85rem;
        background: rgba(255,255,255,.97);
        border-bottom: 1px solid rgba(148,163,184,.22);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }
    body[data-theme="dark"] .pr-topbar {
        background: rgba(2,6,23,.96);
        border-bottom-color: rgba(30,64,175,.45);
    }
    .pr-topbar-code {
        font-weight: 900;
        font-size: 1.05rem;
        letter-spacing: .02em;
        white-space: nowrap;
    }
    body[data-theme="dark"] .pr-topbar-code { color: #e5e7eb; }
    .pr-topbar-spacer { flex: 1; min-width: .5rem; }
    .pr-editor-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: .55rem;
        flex-wrap: wrap;
    }
    .pr-editor-actions .btn { min-height: 40px; }
    .btn-pr-submit,
    .btn-pr-outline {
        border-radius: 8px;
        font-size: .85rem;
        font-weight: 700;
        padding: .42rem .8rem;
        white-space: nowrap;
        box-shadow: none;
    }
    .btn-pr-submit {
        border: 1px solid #2563eb;
        background: #2563eb;
        color: #fff;
    }
    .btn-pr-submit:hover,
    .btn-pr-submit:focus-visible {
        border-color: #1d4ed8;
        background: #1d4ed8;
        color: #fff;
    }
    .btn-pr-outline {
        border: 1px solid rgba(148,163,184,.35);
        background: transparent;
        color: #475569;
    }
    .btn-pr-outline:hover,
    .btn-pr-outline:focus-visible {
        background: rgba(226,232,240,.7);
        color: #334155;
    }
    body[data-theme="dark"] .btn-pr-outline { color: #cbd5e1; }

    @media (max-width: 768px) {
        .pr-page { padding-inline: .5rem; }
        .pr-topbar { padding-inline: .65rem; }
        .pr-topbar-code { font-size: .95rem; }
    }
</style>
@endpush

@section('content')
<form method="POST" action="{{ route('purchasing.purchase_requests.store') }}">
        @csrf

    <div class="pr-topbar mb-3">
        <div class="pr-topbar-code">Buat Purchase Request Baru</div>
        <div class="pr-topbar-spacer"></div>
        <a href="{{ route('purchasing.purchase_requests.index') }}" class="btn-pr-outline text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="pr-page pt-2">
        @if ($errors->any())
            <div class="alert alert-danger py-3 d-flex align-items-start mb-3" style="border-radius:10px;">
                <i class="bi bi-exclamation-triangle-fill me-3 fs-5 mt-1"></i>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('purchasing.purchase_requests._form', [
            'pr'          => null,
            'suppliers'   => $suppliers,
            'items'       => $items,
            'canSeeMoney' => $canSeeMoney,
            'linesData'   => $linesData,
        ])

        <div class="pr-editor-actions mt-4 mb-2">
            <a href="{{ route('purchasing.purchase_requests.index') }}" class="btn-pr-outline text-decoration-none">
                <i class="bi bi-x-lg me-1"></i>Batal
            </a>
            <button type="submit" class="btn-pr-submit">
                <i class="bi bi-check2 me-1"></i>Simpan PR
            </button>
        </div>
    </div>
</form>
@endsection
