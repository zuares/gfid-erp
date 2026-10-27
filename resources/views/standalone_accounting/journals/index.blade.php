@extends('layouts.app')

@section('title', 'Akuntansi Baru • Jurnal')

@push('head')
    <style>
        .new-accounting-btn { display:inline-flex; align-items:center; min-height:38px; padding:.45rem .85rem; border:1px solid #dbe3ee; border-radius:10px; background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:700; }
        .new-accounting-btn:hover { background:#f8fafc; color:#0f172a; }
        .new-accounting-btn.primary { background:#0f172a; border-color:#0f172a; color:#fff; }
        .new-accounting-btn.primary:hover { background:#1e293b; color:#fff; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
        <div><h4 class="mb-1">Akuntansi Baru — Jurnal</h4><div class="text-muted small">Semua jurnal di sini berdiri sendiri dan langsung memengaruhi neraca saldo baru.</div></div>
        <div class="d-flex gap-2 flex-wrap"><a class="new-accounting-btn" href="{{ route('standalone-accounting.trial-balance.index') }}">Neraca Saldo</a><a class="new-accounting-btn primary" href="{{ route('standalone-accounting.journals.create') }}">+ Buat Jurnal</a></div>
    </div>

    @if (session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>Tanggal</th><th>Referensi</th><th>Keterangan</th><th class="text-end">Total</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($journals as $journal)
                        <tr>
                            <td>{{ $journal->date?->format('d-m-Y') }}</td>
                            <td>{{ $journal->reference ?: '-' }}</td>
                            <td>{{ $journal->description ?: '-' }}</td>
                            <td class="text-end">Rp {{ number_format((float) $journal->lines->sum('debit'), 0, ',', '.') }}</td>
                            <td>{{ $journal->voided_at ? 'VOID' : ($journal->posted_at ? 'POSTED' : 'DRAFT') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">Belum ada jurnal.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($journals->hasPages())<div class="card-footer bg-white">{{ $journals->links() }}</div>@endif
    </div>
@endsection
