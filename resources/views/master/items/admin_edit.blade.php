@extends('layouts.app')

@section('title', 'Edit Nama Item • ' . ($item->code ?? ''))

@push('head')
<style>
    .admin-item-edit { max-width: 680px; margin: 0 auto; padding: 1rem 1rem 4rem; color: #0f172a; }
    .admin-item-edit-card { border: 1px solid #e5e7eb; border-radius: 18px; background: #fff; box-shadow: 0 10px 28px rgba(15,23,42,.05); overflow: hidden; }
    .admin-item-edit-head { padding: 1.25rem 1.35rem; border-bottom: 1px solid #eef2f7; }
    .admin-item-edit-eyebrow { color: #64748b; font-size: .66rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .admin-item-edit-title { margin: .25rem 0 0; font-size: 1.25rem; font-weight: 850; letter-spacing: -.025em; }
    .admin-item-edit-code { display: inline-flex; margin-top: .6rem; padding: .25rem .5rem; border: 1px solid #e2e8f0; border-radius: 7px; color: #475569; background: #f8fafc; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .72rem; font-weight: 750; }
    .admin-item-edit-body { padding: 1.35rem; }
    .admin-item-edit-note { margin-bottom: 1rem; padding: .7rem .8rem; border: 1px solid #e2e8f0; border-radius: 10px; color: #475569; background: #f8fafc; font-size: .78rem; }
    .admin-item-edit-label { display: block; margin-bottom: .45rem; color: #334155; font-size: .68rem; font-weight: 850; letter-spacing: .06em; text-transform: uppercase; }
    .admin-item-edit .form-control { min-height: 44px; border-color: #cbd5e1; border-radius: 10px; font-size: .88rem; font-weight: 650; box-shadow: none; }
    .admin-item-edit .form-control:focus { border-color: #111827; box-shadow: 0 0 0 3px rgba(15,23,42,.08); }
    .admin-item-edit-actions { display: flex; justify-content: flex-end; gap: .55rem; margin-top: 1.2rem; }
    .admin-item-edit-actions .btn { border-radius: 10px; font-weight: 750; }
    .admin-item-edit-primary { color: #fff; background: #111827; border-color: #111827; }
    body[data-theme="dark"] .admin-item-edit { color: #f8fafc; }
    body[data-theme="dark"] .admin-item-edit-card { background: #0f172a; border-color: #334155; }
    body[data-theme="dark"] .admin-item-edit-head { border-color: #334155; }
    body[data-theme="dark"] .admin-item-edit-eyebrow, body[data-theme="dark"] .admin-item-edit-label { color: #cbd5e1; }
    body[data-theme="dark"] .admin-item-edit-code, body[data-theme="dark"] .admin-item-edit-note { color: #cbd5e1; background: #111827; border-color: #334155; }
    body[data-theme="dark"] .admin-item-edit-note { color: #94a3b8; }
    body[data-theme="dark"] .admin-item-edit .form-control { color: #f8fafc; background: #0f172a; border-color: #475569; }
    @media (max-width: 700px) {
        .admin-item-edit { padding: .65rem .55rem 4rem; }
        .admin-item-edit-actions { flex-direction: column-reverse; }
        .admin-item-edit-actions .btn { width: 100%; }
    }
</style>
@endpush

@section('content')
<div class="admin-item-edit">
    <div class="admin-item-edit-card">
        <div class="admin-item-edit-head">
            <div class="admin-item-edit-eyebrow"><i class="bi bi-pencil-square me-1"></i>Master Item · Admin</div>
            <h1 class="admin-item-edit-title">Edit Nama Item</h1>
            <span class="admin-item-edit-code">{{ $item->code }}</span>
        </div>
        <div class="admin-item-edit-body">
            <div class="admin-item-edit-note"><i class="bi bi-info-circle me-1"></i>Role admin hanya dapat mengubah nama item. Data lainnya dikelola oleh owner atau developer.</div>
            @if($errors->any())
                <div class="alert alert-danger py-2 px-3 small">{{ $errors->first() }}</div>
            @endif
            <form action="{{ route('master.items.update', $item) }}" method="POST">
                @csrf
                @method('PUT')
                <label class="admin-item-edit-label" for="admin-item-name">Nama item</label>
                <input id="admin-item-name" type="text" name="name" class="form-control" value="{{ old('name', $item->name) }}" maxlength="190" required autofocus>
                <div class="admin-item-edit-actions">
                    <a href="{{ route('master.items.show', $item) }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn admin-item-edit-primary"><i class="bi bi-check2 me-1"></i>Simpan Nama</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
