@extends('layouts.app')

@section('title', 'Akuntansi Baru • Jurnal')

@push('head')
    <style>
        .new-accounting-btn { display:inline-flex; align-items:center; min-height:38px; padding:.45rem .85rem; border:1px solid #dbe3ee; border-radius:10px; background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:700; }
        .new-accounting-btn:hover { background:#f8fafc; color:#0f172a; }
        .new-accounting-btn.primary { background:#0f172a; border-color:#0f172a; color:#fff; }
        .new-accounting-btn.primary:hover { background:#1e293b; color:#fff; }
        .journal-account-summary { display:flex; flex-wrap:wrap; gap:.3rem; margin-top:.35rem; }
        .journal-account-chip { display:inline-flex; align-items:center; gap:.25rem; padding:.18rem .42rem; border:1px solid #e2e8f0; border-radius:999px; background:#f8fafc; color:#475569; font-size:.7rem; line-height:1.25; }
        .journal-account-chip.reject { border-color:#fecaca; background:#fff1f2; color:#be123c; font-weight:700; }
        .journal-account-chip strong { color:#0f172a; }
        .journal-account-chip.reject strong { color:#9f1239; }
        .journal-source-label { display:inline-block; margin-top:.25rem; color:#64748b; font-size:.68rem; }
        .journal-reference-link { color:#0f172a; font-weight:750; text-decoration:none; }
        .journal-reference-link:hover { color:#2563eb; text-decoration:underline; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
        <div><h4 class="mb-1">Akuntansi Baru — Jurnal</h4><div class="text-muted small">Semua jurnal di sini berdiri sendiri dan langsung memengaruhi neraca saldo baru.</div></div>
        <div class="d-flex gap-2 flex-wrap"><a class="new-accounting-btn" href="{{ route('standalone-accounting.trial-balance.index') }}">Neraca Saldo</a><a class="new-accounting-btn" href="{{ route('standalone-accounting.journals.create', ['mode' => 'opening']) }}">+ Saldo Awal</a><a class="new-accounting-btn primary" href="{{ route('standalone-accounting.journals.create', ['mode' => 'general']) }}">+ Buat Jurnal</a></div>
    </div>

    @if (session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" style="min-width:860px;">
                <thead class="table-light"><tr><th>Tanggal</th><th>Referensi</th><th>Keterangan</th><th>Akun / Dampak</th><th class="text-end">Total</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($journals as $journal)
                        <tr>
                            <td>{{ $journal->date?->format('d-m-Y') }}</td>
                            <td>
                                <a class="journal-reference-link" href="{{ route('standalone-accounting.journals.show', $journal) }}">
                                    {{ $journal->reference ?: 'Jurnal #'.$journal->id }}
                                </a>
                            </td>
                            <td>
                                <div>{{ $journal->description ?: '-' }}</div>
                                @if ($journal->source_type)
                                    <span class="journal-source-label">Source: {{ $journal->source_type }}{{ $journal->source_id ? ' #'.$journal->source_id : '' }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="journal-account-summary" aria-label="Ringkasan akun jurnal">
                                    @foreach ($journal->lines as $line)
                                        @php
                                            $accountCode = (string) ($line->account?->code ?? '-');
                                            $isReject = $accountCode === '1-153';
                                            $isSewingReject = in_array(
                                                $journal->source_type,
                                                ['sewing_pickup_pre_sewing_reject', 'sewing_pickup_reject'],
                                                true,
                                            );
                                            $accountName = $isReject
                                                ? ($isSewingReject ? 'Reject Susulan' : 'Reject Cutting')
                                                : ($line->account?->name ?: 'Akun');
                                            $rejectQty = $isReject
                                                ? ($isSewingReject
                                                    ? (float) ($sewingRejectQtyByLine[$journal->source_id] ?? 0)
                                                    : (float) ($cuttingRejectQtyByJob[$journal->source_id] ?? 0))
                                                : 0;
                                            $displayName = $rejectQty > 0.000001
                                                ? $accountName.' · '.number_format($rejectQty, 0, ',', '.').' pcs'
                                                : $accountName;
                                        @endphp
                                        @if ((float) $line->debit > 0)
                                            <span class="journal-account-chip{{ $isReject ? ' reject' : '' }}">
                                                <strong>Dr {{ $accountCode }}</strong>
                                                {{ $displayName }} · Rp {{ number_format((float) $line->debit, 0, ',', '.') }}
                                            </span>
                                        @endif
                                        @if ((float) $line->credit > 0)
                                            <span class="journal-account-chip{{ $isReject ? ' reject' : '' }}">
                                                <strong>Cr {{ $accountCode }}</strong>
                                                {{ $displayName }} · Rp {{ number_format((float) $line->credit, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-end">Rp {{ number_format((float) $journal->lines->sum('debit'), 0, ',', '.') }}</td>
                            <td>{{ $journal->voided_at ? 'VOID' : ($journal->posted_at ? 'POSTED' : 'DRAFT') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">Belum ada jurnal.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($journals->hasPages())<div class="card-footer bg-white">{{ $journals->links() }}</div>@endif
    </div>
@endsection
