@extends('layouts.app')

@section('title', 'Akuntansi Baru • Detail Jurnal')

@push('head')
    <style>
        .journal-detail-wrap { max-width: 980px; margin-inline:auto; }
        .journal-detail-btn { display:inline-flex; align-items:center; gap:.4rem; min-height:38px; padding:.45rem .85rem; border:1px solid #dbe3ee; border-radius:10px; background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:700; }
        .journal-detail-btn:hover { background:#f8fafc; color:#0f172a; }
        .journal-detail-btn-danger { border-color:#fecaca; background:#fff5f5; color:#b91c1c; }
        .journal-detail-btn-danger:hover { background:#fee2e2; color:#991b1b; }
        .journal-detail-meta { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; }
        .journal-detail-meta-item { padding:.75rem .85rem; border:1px solid #e5e7eb; border-radius:12px; background:#fff; }
        .journal-detail-meta-label { display:block; color:#64748b; font-size:.68rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase; }
        .journal-detail-meta-value { display:block; margin-top:.2rem; color:#0f172a; font-size:.9rem; font-weight:750; }
        @media(max-width:700px) { .journal-detail-meta { grid-template-columns:1fr; } }
    </style>
@endpush

@section('content')
    <div class="journal-detail-wrap">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
            <div>
                <h4 class="mb-1">Detail Jurnal Akuntansi Baru</h4>
                <div class="text-muted small">Jurnal ini terhubung ke transaksi sumber dan dapat ditelusuri kembali.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @if (!$journal->voided_at && ($journal->source_type === 'opening_balance_manual' || $journal->reference === 'OB-UMKM-DUMMY-001'))
                    <form method="POST" action="{{ route('standalone-accounting.journals.void', $journal) }}"
                        data-gf-confirm
                        data-gf-confirm-title="Void saldo awal?"
                        data-gf-confirm-text="Saldo dummy akan dikeluarkan dari laporan dan reversal tersimpan sebagai audit trail. Data asli tidak dihapus."
                        data-gf-confirm-icon="warning"
                        data-gf-confirm-ok="Ya, void">
                        @csrf
                        <input type="hidden" name="reason" value="Penggantian saldo awal dummy">
                        <button class="journal-detail-btn journal-detail-btn-danger" type="submit">Void Saldo Awal</button>
                    </form>
                @endif
                <a class="journal-detail-btn" href="{{ route('standalone-accounting.journals.index') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke jurnal</a>
                @if (in_array($journal->source_type, ['opening_migration', 'opening_migration_correction'], true))
                    <a class="journal-detail-btn" href="{{ route('standalone-accounting.opening-migration.index') }}">Snapshot dan koreksi opening</a>
                @endif
            </div>
        </div>

        <div class="journal-detail-meta mb-3">
            <div class="journal-detail-meta-item"><span class="journal-detail-meta-label">Tanggal</span><span class="journal-detail-meta-value">{{ $journal->date?->format('d-m-Y') }}</span></div>
            <div class="journal-detail-meta-item"><span class="journal-detail-meta-label">Referensi</span><span class="journal-detail-meta-value">{{ $journal->reference ?: '-' }}</span></div>
            <div class="journal-detail-meta-item"><span class="journal-detail-meta-label">Status</span><span class="journal-detail-meta-value">{{ $journal->voided_at ? 'VOID' : ($journal->posted_at ? 'POSTED' : 'DRAFT') }}</span></div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="text-muted small mb-1">Keterangan</div>
                <div class="fw-semibold">{{ $journal->description ?: '-' }}</div>
                @if ($journal->reference === 'OB-UMKM-DUMMY-001' && !$journal->voided_at)
                    <div class="alert alert-warning small mt-3 mb-0">
                        Ini saldo awal dummy. Void jurnal ini sebelum mengisi saldo riil.
                    </div>
                @endif
                @if ($journal->source_type && $journal->source_id)
                    <div class="text-muted small mt-2">Sumber: {{ $journal->source_type }} #{{ $journal->source_id }}</div>
                    @if ($sourceStockOpname)
                        <div class="d-flex gap-2 flex-wrap mt-3">
                            <a class="journal-detail-btn" href="{{ route('inventory.stock_opnames.show', $sourceStockOpname) }}"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Buka Stock Opname</a>
                            <a class="journal-detail-btn" href="{{ route('standalone-accounting.stock-opname-variance.index', ['q' => $sourceStockOpname->code]) }}"><i class="bi bi-clipboard2-data" aria-hidden="true"></i> Lihat Variance</a>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Akun</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr></thead>
                    <tbody>
                        @foreach ($journal->lines as $line)
                            <tr>
                                <td><strong>{{ $line->account?->code }}</strong> — {{ $line->account?->name }}</td>
                                <td class="text-end">Rp {{ number_format((float) $line->debit, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format((float) $line->credit, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr><th>Total</th><th class="text-end">Rp {{ number_format((float) $journal->lines->sum('debit'), 0, ',', '.') }}</th><th class="text-end">Rp {{ number_format((float) $journal->lines->sum('credit'), 0, ',', '.') }}</th></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
