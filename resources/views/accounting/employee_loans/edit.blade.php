@extends('layouts.app')

@section('title', 'Accounting • Edit Pinjaman Karyawan')

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .el-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
        .el-form-section { padding: 1rem; border: 1px solid #e2e8f0; border-radius: 15px; background: #fff; }
        .el-form-section + .el-form-section { margin-top: .8rem; }
        .el-section-heading { display: flex; align-items: flex-start; gap: .7rem; margin-bottom: 1rem; }
        .el-section-step { display: inline-flex; flex: 0 0 28px; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 9px; color: #fff; background: #0f172a; font-size: .78rem; font-weight: 900; }
        .el-section-heading h3 { margin: 0; color: #0f172a; font-size: .98rem; font-weight: 900; }
        .el-section-heading p { margin: .2rem 0 0; color: #64748b; font-size: .78rem; }
        .el-field { display: grid; gap: .35rem; min-width: 0; }
        .el-field-full { grid-column: 1 / -1; }
        .el-field-label { color: #334155; font-size: .74rem; font-weight: 900; }
        .el-field-label b { color: #dc2626; }
        .el-field-label em { color: #94a3b8; font-size: .68rem; font-style: normal; font-weight: 700; }
        .el-field-help { color: #94a3b8; font-size: .71rem; line-height: 1.35; }
        .el-control { min-height: 44px; border-radius: 11px; border-color: #dbe3ee; box-shadow: none; }
        .el-control:focus { border-color: #64748b; box-shadow: 0 0 0 .2rem rgba(15, 23, 42, .07); }
        .el-money { display: flex; align-items: center; min-width: 0; border: 1px solid #dbe3ee; border-radius: 11px; background: #fff; overflow: hidden; }
        .el-money > span { padding: 0 .75rem; color: #64748b; font-size: .82rem; font-weight: 900; }
        .el-money .el-control { flex: 1; min-width: 0; border: 0; border-left: 1px solid #eef2f7; border-radius: 0; }
        .el-actions { display: flex; justify-content: flex-end; gap: .55rem; flex-wrap: wrap; }
        .el-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-height: 42px; padding: .6rem 1rem; border: 1px solid #dbe3ee; border-radius: 999px; background: #fff; color: #0f172a; text-decoration: none; font-size: .83rem; font-weight: 850; }
        .el-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
        .el-btn span { color: inherit !important; }
        .el-form-actions { padding-top: 1rem; border-top: 1px solid #eef2f7; }
        @media (max-width: 760px) { .el-form-grid { grid-template-columns: 1fr; } .el-field-full { grid-column: auto; } .el-actions .el-btn { flex: 1 1 0; } .el-form-actions { position: static; bottom: auto; z-index: auto; margin: 1.25rem 0 0; padding: 1rem 0 0; gap: .5rem; background: transparent; border-top: 1px solid #eef2f7; box-shadow: none; } .el-form-actions .el-btn { height: 44px; min-height: 44px; padding: 0 .75rem; border-radius: 12px; font-size: .8rem; line-height: 1; } }
    </style>
@endpush

@section('content')
    <x-gf.page eyebrow="Accounting" title="Edit Pinjaman Karyawan" description="Perubahan hanya diperbolehkan selama status masih DRAFT.">
        <x-slot:actions><div class="el-actions"><a class="el-btn" href="{{ route('accounting.employee-loans.show', $employeeLoan) }}"><i class="bi bi-arrow-left" aria-hidden="true"></i><span>Kembali</span></a></div></x-slot:actions>
        <x-gf.panel title="Informasi pinjaman" subtitle="Periksa kembali data sebelum menyimpan perubahan.">
            <form method="POST" action="{{ route('accounting.employee-loans.update', $employeeLoan) }}">
                @csrf
                @method('PUT')
                @include('accounting.employee_loans._form')
                <div class="el-actions el-form-actions mt-3"><a class="el-btn" href="{{ route('accounting.employee-loans.show', $employeeLoan) }}">Batal</a><button class="el-btn el-btn-primary" type="submit"><i class="bi bi-save" aria-hidden="true"></i><span>Simpan Perubahan</span></button></div>
            </form>
        </x-gf.panel>
    </x-gf.page>
@endsection
