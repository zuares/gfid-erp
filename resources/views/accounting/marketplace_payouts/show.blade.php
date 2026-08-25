@extends('layouts.app')

@section('title', 'Penerimaan Marketplace #{{ $marketplacePayout->id }}')

@php
    $fmt = fn($n) => number_format((float) $n, 0, ',', '.');
    $p = $marketplacePayout;
@endphp

@push('head')
    @include('production.dashboard.partials._gf-styles')
    <style>
        .mp-show-card { background:#fff; border:1px solid rgba(15,23,42,.08); border-radius:14px; padding:1.4rem; }
        .mp-dl { display: grid; grid-template-columns: 180px 1fr; gap: .55rem 1rem; }
        .mp-dl dt { color: #64748b; font-size: .8rem; font-weight: 850; }
        .mp-dl dd { color: #0f172a; font-size: .88rem; font-weight: 760; margin: 0; }
        .mp-status {
            display: inline-flex; align-items: center; gap: .35rem; border-radius: 999px;
            padding: .22rem .6rem; font-size: .74rem; font-weight: 850; border: 1px solid transparent;
        }
        .mp-status::before { content:''; width:7px; height:7px; border-radius:999px; background:currentColor; }
        .mp-status-draft  { color:#b45309; background:#fef3c7; border-color:#fde68a; }
        .mp-status-posted { color:#166534; background:#dcfce7; border-color:#bbf7d0; }
        .mp-status-void   { color:#b91c1c; background:#fee2e2; border-color:#fecaca; }
        .mp-btn {
            display:inline-flex; align-items:center; gap:.45rem; min-height:40px;
            padding:.55rem .95rem; border-radius:999px; border:1px solid rgba(15,23,42,.10);
            background:#fff; color:#0f172a; text-decoration:none; font-size:.84rem; font-weight:850;
        }
        .mp-btn:hover { background:#f8fafc; color:#0f172a; }
        .mp-btn-primary { background:#0f172a; border-color:#0f172a; color:#fff; }
        .mp-btn-primary:hover { background:#1e293b; color:#fff; }
        .mp-btn-danger { background:#dc2626; border-color:#dc2626; color:#fff; }
        .mp-btn-danger:hover { background:#b91c1c; color:#fff; }
        .mp-journal-table th, .mp-journal-table td { vertical-align: middle; }
        .mp-num { text-align:right; font-variant-numeric:tabular-nums; font-weight:900; }
    </style>
@endpush

@section('content')
<div style="display:grid; gap:1rem; max-width:680px">

    {{-- Breadcrumb --}}
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('accounting.marketplace-payouts.index') }}" class="mp-btn">← Daftar</a>
        <span class="text-muted" style="font-size:.82rem">/ Penerimaan #{{ $p->id }}</span>
    </div>

    @if(session('message'))
        <div class="alert alert-{{ session('status') === 'ok' ? 'success' : 'danger' }} py-2 mb-0">
            {{ session('message') }}
        </div>
    @endif

    {{-- Main Card --}}
    <div class="mp-show-card">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h5 class="mb-1 fw-black">{{ $p->marketplace_name }}</h5>
                <span class="mp-status mp-status-{{ $p->status }}">
                    {{ ['draft'=>'Draft','posted'=>'Tercatat','void'=>'Dibatalkan'][$p->status] ?? $p->status }}
                </span>
            </div>
            <div style="text-align:right">
                <div style="font-size:1.35rem; font-weight:950; font-variant-numeric:tabular-nums">
                    Rp {{ $fmt($p->amount) }}
                </div>
                <div style="color:#64748b; font-size:.78rem">{{ $p->date->format('d M Y') }}</div>
            </div>
        </div>

        <dl class="mp-dl">
            <dt>Akun Bank</dt>
            <dd>{{ $p->bankAccount?->code }} – {{ $p->bankAccount?->name }}</dd>

            @if($p->store)
                <dt>Toko</dt>
                <dd>{{ $p->store->name }} <span class="text-muted">({{ ucfirst($p->source ?: 'manual') }})</span></dd>
            @endif

            <dt>Referensi</dt>
            <dd>{{ $p->reference ?: '-' }}</dd>

            <dt>Keterangan</dt>
            <dd>{{ $p->description ?: '-' }}</dd>

            @if($p->notes)
                <dt>Catatan</dt>
                <dd style="white-space:pre-line">{{ $p->notes }}</dd>
            @endif

            <dt>Dibuat</dt>
            <dd>{{ $p->created_at?->format('d M Y H:i') }}</dd>
        </dl>
    </div>

    {{-- Actions --}}
    @if($p->status === 'draft')
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('accounting.marketplace-payouts.edit', $p) }}" class="mp-btn">Edit</a>
            <form method="POST" action="{{ route('accounting.marketplace-payouts.post', $p) }}">
                @csrf
                <button class="mp-btn mp-btn-primary" onclick="return confirm('POST jurnal Dr Bank / Cr Saldo Marketplace?')">
                    POST Jurnal
                </button>
            </form>
            <form method="POST" action="{{ route('accounting.marketplace-payouts.destroy', $p) }}"
                  onsubmit="return confirm('Hapus draft ini?')">
                @csrf @method('DELETE')
                <button class="mp-btn" style="color:#dc2626; border-color:#fca5a5">Hapus Draft</button>
            </form>
        </div>
    @endif

    @if($p->status === 'posted')
        <div class="d-flex gap-2 flex-wrap">
            <button class="mp-btn mp-btn-danger" data-bs-toggle="modal" data-bs-target="#voidModal">
                VOID
            </button>
        </div>
    @endif

    {{-- Journal Detail --}}
    @if($p->journal)
        <div class="mp-show-card">
            <div class="fw-bold mb-2" style="font-size:.85rem">
                Jurnal #{{ $p->journal->id }}
                <span class="text-muted fw-normal">— {{ $p->journal->date }}</span>
            </div>
            <table class="table table-sm mp-journal-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Akun</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($p->journal->lines as $ln)
                        <tr>
                            <td>
                                <span style="color:#94a3b8; font-size:.76rem">{{ $ln->account?->code }}</span>
                                {{ $ln->account?->name }}
                            </td>
                            <td class="mp-num">{{ $ln->debit > 0 ? 'Rp '.$fmt($ln->debit) : '-' }}</td>
                            <td class="mp-num">{{ $ln->credit > 0 ? 'Rp '.$fmt($ln->credit) : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>

{{-- Void Modal --}}
@if($p->status === 'posted')
<div class="modal fade" id="voidModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">VOID Penerimaan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('accounting.marketplace-payouts.void', $p) }}">
                @csrf
                <div class="modal-body">
                    <label class="form-label fw-bold" style="font-size:.8rem">Alasan VOID</label>
                    <input type="text" name="reason" class="form-control" placeholder="opsional">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm">Ya, VOID</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
