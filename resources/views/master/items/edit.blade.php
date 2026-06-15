@extends('layouts.app')

@section('title', 'Edit Item • ' . ($item->code ?? ''))

@push('head')
<style>
    .gf-edit-page {
        max-width: 1120px;
        margin: 0 auto;
        padding: 18px 14px 32px;
    }

    .gf-edit-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .gf-edit-head-left {
        display: flex;
        align-items: center;
        gap: 13px;
        min-width: 0;
    }

    .gf-edit-head-icon {
        width: 48px;
        height: 48px;
        flex: 0 0 48px;
        border-radius: 17px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: linear-gradient(135deg, #0f172a, #334155);
        box-shadow: 0 14px 28px rgba(15, 23, 42, .18);
        font-size: 1.28rem;
    }

    .gf-edit-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #334155;
        font-size: .72rem;
        font-weight: 900;
        margin-bottom: 7px;
        text-decoration: none;
        transition: background .15s;
    }

    .gf-edit-eyebrow:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    .gf-edit-title {
        color: #0f172a;
        font-size: 1.34rem;
        font-weight: 950;
        letter-spacing: -.05em;
        line-height: 1.1;
        margin: 0;
    }

    .gf-edit-subtitle {
        color: #64748b;
        font-size: .86rem;
        font-weight: 600;
        margin-top: 4px;
    }

    .gf-edit-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .gf-edit-actions .btn {
        border-radius: 999px;
        font-weight: 850;
        letter-spacing: -.01em;
        font-size: .82rem;
    }
</style>
@endpush

@section('content')
<div class="gf-edit-page">

    {{-- Page Header --}}
    <div class="gf-edit-head">
        <div class="gf-edit-head-left">
            <div class="gf-edit-head-icon">
                <i class="bi bi-box-seam"></i>
            </div>
            <div>
                <a href="{{ route('master.items.index') }}" class="gf-edit-eyebrow">
                    <i class="bi bi-arrow-left"></i>
                    Master Item
                </a>
                <h1 class="gf-edit-title">Edit Item</h1>
                <div class="gf-edit-subtitle">{{ $item->code }} — {{ $item->name }}</div>
            </div>
        </div>

        <div class="gf-edit-actions">
            <a href="{{ route('master.items.index') }}" class="btn btn-outline-secondary btn-sm">
                Batal
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="alert alert-success py-2 px-3 mb-3" style="font-size:.82rem; border-radius:12px;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger py-2 px-3 mb-3" style="font-size:.82rem; border-radius:12px;">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('master.items.update', $item) }}" method="POST">
        @csrf
        @method('PUT')

        @include('master.items._form')

    </form>

</div>
@endsection
