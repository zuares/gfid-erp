@extends('layouts.app')

@section('title', 'Faktur ' . $supplierInvoice->invoice_no)

@php
    $user        = auth()->user();
    $canSeeMoney = $user?->isOwner() || in_array($user?->role ?? '', ['accounting', 'developer']);
    $isOwner     = $user?->isOwner() ?? false;
    $inv         = $supplierInvoice;
    $isDraft     = $inv->isDraft();
    $isPosted    = $inv->isPosted();
    $isVoid      = $inv->isVoid();
    $outstanding = $inv->outstanding();

    $statusLabel = match($inv->status) {
        'posted'       => 'POSTED',
        'partial_paid' => 'BAYAR SEBAGIAN',
        'paid'         => 'LUNAS',
        'void'         => 'VOID',
        default        => 'DRAFT',
    };
    $statusClass = match($inv->status) {
        'posted'       => 'tag tag-status-posted',
        'partial_paid' => 'tag tag-status-partial',
        'paid'         => 'tag tag-status-paid',
        'void'         => 'tag tag-status-void',
        default        => 'tag tag-status-draft',
    };
@endphp

@push('head')
<style>
    .page-wrap { max-width: 800px; margin-inline: auto; padding-bottom: 3rem; }
    .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"; }

    /* status tags */
    .tag { border-radius: 999px; font-size: .75rem; padding: .15rem .7rem; border: 1px solid transparent; display: inline-block; }
    .tag-status-draft    { background: rgba(148,163,184,.12); color: #64748b; border-color: rgba(148,163,184,.5); }
    .tag-status-posted   { background: rgba(59,130,246,.10); color: #1d4ed8; border-color: rgba(59,130,246,.5); }
    .tag-status-partial  { background: rgba(234,179,8,.12); color: #a16207; border-color: rgba(234,179,8,.5); }
    .tag-status-paid     { background: rgba(22,163,74,.12); color: #15803d; border-color: rgba(22,163,74,.5); }
    .tag-status-void     { background: rgba(220,38,38,.08); color: #b91c1c; border-color: rgba(220,38,38,.5); }

    /* Info grid */
    dl.info-grid { display: grid; grid-template-columns: auto 1fr; gap: .25rem .75rem; font-size: .87rem; }
    dl.info-grid dt { color: var(--muted); font-weight: 400; white-space: nowrap; }
    dl.info-grid dd { margin: 0; }

    /* Summary card */
    .summary-row { display: flex; justify-content: space-between; padding: .3rem 0; border-bottom: 1px solid var(--line); font-size: .87rem; }
    .summary-row:last-child { border-bottom: none; font-weight: 600; font-size: .92rem; }
    .summary-row.highlight { background: rgba(59,130,246,.04); margin: 0 -.75rem; padding: .3rem .75rem; }

    /* Action buttons */
    .action-bar { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .btn-action { font-size: .82rem; }

    /* Warning */
    .warn-box {
        background: rgba(234,179,8,.08);
        border: 1px solid rgba(234,179,8,.4);
        border-radius: 8px;
        padding: .6rem .85rem;
        font-size: .82rem;
        color: #a16207;
    }
    .info-box {
        background: rgba(59,130,246,.06);
        border: 1px solid rgba(59,130,246,.3);
        border-radius: 8px;
        padding: .6rem .85rem;
        font-size: .82rem;
        color: #1d4ed8;
    }
</style>
@endpush

@section('content')
<div class="page-wrap">

    {{-- Flash --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible mb-3">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible mb-3">
            {{ session('error') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <a href="{{ route('purchasing.supplier_invoices.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                ← Daftar Faktur
            </a>
            <h1 class="h5 mb-0 fw-bold">{{ $inv->invoice_no }}</h1>
            <div class="text-muted small mono">Dibuat {{ id_date($inv->invoice_date) }}</div>
        </div>
        <div class="action-bar">
            @if ($isDraft && $canSeeMoney)
                <form method="POST" action="{{ route('purchasing.supplier_invoices.post', $inv->id) }}"
                      onsubmit="return confirm('Post faktur ini? Pastikan nilainya sudah benar.')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary btn-action">Post Faktur</button>
                </form>
            @endif

            @if (!$isVoid && $isOwner)
                <form method="POST" action="{{ route('purchasing.supplier_invoices.void', $inv->id) }}"
                      onsubmit="return confirm('Void faktur ini? Tindakan ini tidak bisa dibatalkan.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger btn-action">Void</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Warning: draft belum di-post --}}
    @if ($isDraft)
        <div class="warn-box mb-3">
            ⚠️ Faktur ini masih Draft. Klik <strong>Post Faktur</strong> setelah data sudah benar.
        </div>
    @endif

    {{-- Info card --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <dl class="info-grid">
                        <dt>Status</dt>
                        <dd><span class="{{ $statusClass }}">{{ $statusLabel }}</span></dd>

                        <dt>Supplier</dt>
                        <dd class="fw-semibold">{{ optional($inv->supplier)->name ?? '—' }}</dd>

                        <dt>Tanggal Faktur</dt>
                        <dd class="mono">{{ id_date($inv->invoice_date) }}</dd>

                        @if ($inv->due_date)
                        <dt>Jatuh Tempo</dt>
                        <dd class="mono {{ $isPosted && $inv->due_date < now() ? 'text-danger fw-semibold' : '' }}">
                            {{ id_date($inv->due_date) }}
                            @if ($isPosted && $outstanding > 0 && $inv->due_date < now())
                                <span class="badge bg-danger ms-1" style="font-size:.65rem;">JATUH TEMPO</span>
                            @endif
                        </dd>
                        @endif

                        @if ($inv->supplier_invoice_ref)
                        <dt>No. Faktur Supplier</dt>
                        <dd class="mono">{{ $inv->supplier_invoice_ref }}</dd>
                        @endif

                        @if ($order)
                        <dt>Purchase Order</dt>
                        <dd>
                            <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
                               class="text-decoration-none mono">{{ $order->code }}</a>
                        </dd>
                        @endif
                    </dl>
                </div>

                <div class="col-md-6">
                    <dl class="info-grid">
                        <dt>Dibuat oleh</dt>
                        <dd>{{ optional($inv->createdBy)->name ?? '—' }}</dd>

                        @if ($inv->posted_at)
                        <dt>Di-post</dt>
                        <dd class="mono">{{ id_date($inv->posted_at) }}</dd>
                        @endif

                        @if ($isVoid && $inv->voided_at)
                        <dt>Di-void</dt>
                        <dd class="mono text-danger">{{ id_date($inv->voided_at) }}</dd>
                        @endif

                        @if ($inv->notes)
                        <dt>Catatan</dt>
                        <dd>{{ $inv->notes }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>

    {{-- Ringkasan Nilai --}}
    @if ($canSeeMoney)
    <div class="card mb-3">
        <div class="card-body">
            <div class="fw-semibold small text-uppercase text-muted mb-3">Ringkasan Nilai</div>

            {{-- Referensi PO / GRN --}}
            @if ($order)
                <div class="info-box mb-3">
                    <div class="row g-2" style="font-size:.83rem;">
                        <div class="col-6">
                            <div class="text-muted">Total PO ({{ $order->code }})</div>
                            <div class="mono fw-semibold">{{ rupiah($order->grand_total) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted">GRN Posted ({{ $grnCount }} doc)</div>
                            <div class="mono fw-semibold">{{ rupiah($grnPostedTotal) }}</div>
                        </div>
                        @if ($returnTotal > 0)
                        <div class="col-6">
                            <div class="text-muted">Total Return</div>
                            <div class="mono fw-semibold text-danger">- {{ rupiah($returnTotal) }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Nilai Faktur --}}
            <div class="summary-row">
                <span class="text-muted">Subtotal Faktur</span>
                <span class="mono">{{ rupiah($inv->subtotal) }}</span>
            </div>
            @if ((float) $inv->discount_amount > 0)
            <div class="summary-row">
                <span class="text-muted">Diskon</span>
                <span class="mono text-danger">- {{ rupiah($inv->discount_amount) }}</span>
            </div>
            @endif
            @if ((float) $inv->return_deduction_amount > 0)
            <div class="summary-row">
                <span class="text-muted">Potongan Retur</span>
                <span class="mono text-danger">- {{ rupiah($inv->return_deduction_amount) }}</span>
            </div>
            @endif
            <div class="summary-row">
                <span>Total Faktur</span>
                <span class="mono">{{ rupiah($inv->total_amount) }}</span>
            </div>
            @if ($isPosted)
            <div class="summary-row">
                <span class="text-muted">Sudah Dibayar</span>
                <span class="mono text-success">{{ rupiah($inv->paid_amount) }}</span>
            </div>
            <div class="summary-row highlight">
                <span>Outstanding</span>
                <span class="mono {{ $outstanding > 0 ? 'text-danger' : 'text-success' }}">
                    {{ rupiah($outstanding) }}
                </span>
            </div>
            @endif
        </div>
    </div>

    {{-- GRN warning jika tidak ada GRN --}}
    @if ($order && $grnPostedTotal <= 0)
        <div class="warn-box mb-3">
            ⚠️ PO {{ $order->code }} belum ada GRN yang di-post. Nilai faktur ini mungkin perlu diverifikasi manual.
        </div>
    @endif

    @endif {{-- end canSeeMoney --}}

    {{-- Link PO untuk non-owner yang tetap boleh lihat --}}
    @if ($order && !$canSeeMoney)
    <div class="card mb-3">
        <div class="card-body small">
            <span class="text-muted">Purchase Order terkait:</span>
            <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
               class="mono ms-1">{{ $order->code }}</a>
        </div>
    </div>
    @endif

</div>
@endsection
