@extends('layouts.app')

@section('title', $purchase_request->code)

@php
    $pr = $purchase_request;
    $estTotal        = $pr->lines->sum(fn ($l) => ($l->qty ?? 0) * ($l->unit_price ?? 0));
    $hasEstimate     = $pr->lines->whereNotNull('unit_price')->isNotEmpty();
    $actualSuppliers = $pr->lines->pluck('supplier')->filter()->unique('id');
    $firstPo         = $pr->purchaseOrders->first();
    $isDraft         = $pr->isDraft();
    $isConverted     = $pr->isConverted();
    $isConvertible   = $pr->isConvertible();
@endphp

@push('head')
<style>
  .page-wrap { max-width:1080px; margin-inline:auto; padding-bottom:3rem; }
  .mono { font-variant-numeric:tabular-nums; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,"Liberation Mono"; }

  /* Cards */
  .card-info    { background:var(--card); border-radius:14px; border:1px solid var(--line); padding:1.1rem 1.2rem; }
  .card-section { background:var(--card); border-radius:14px; border:1px solid var(--line); overflow:hidden; }
  .card-section-header {
    padding:.6rem 1rem; border-bottom:1px solid var(--line);
    font-size:.72rem; text-transform:uppercase; letter-spacing:.07em;
    color:var(--muted); font-weight:600;
  }

  /* Summary 4-col */
  .summary-col { padding:.85rem 1rem; border-right:1px solid var(--line); }
  .summary-col:last-child { border-right:none; }
  .summary-col-label { font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); font-weight:600; margin-bottom:.3rem; }
  .summary-col-value { font-size:.9rem; font-weight:600; }

  /* Status badges */
  .pr-badge { display:inline-flex; border-radius:999px; font-size:.7rem; padding:.1rem .55rem; border:1px solid transparent; white-space:nowrap; }
  .pr-draft     { background:rgba(148,163,184,.12);color:#64748b;border-color:rgba(148,163,184,.5); }
  .pr-approved  { background:rgba(22,163,74,.1);color:#15803d;border-color:rgba(22,163,74,.45); }
  .pr-rejected  { background:rgba(220,38,38,.08);color:#b91c1c;border-color:rgba(220,38,38,.45); }
  .pr-converted { background:rgba(59,130,246,.1);color:#1d4ed8;border-color:rgba(59,130,246,.45); }
  .pr-cancelled { background:rgba(100,116,139,.08);color:#475569;border-color:rgba(100,116,139,.4); }

  /* Lines table */
  .pr-lines-table thead th {
    background:color-mix(in srgb,var(--card) 90%,var(--bg) 10%);
    border-bottom:1px solid var(--line);
    font-size:.72rem; text-transform:uppercase; letter-spacing:.05em;
    color:var(--muted); padding:.5rem .85rem; white-space:nowrap;
  }
  .pr-lines-table tbody td { border-bottom:1px solid var(--line); vertical-align:middle; padding:.55rem .85rem; font-size:.82rem; }
  .pr-item-name { font-weight:650; line-height:1.2; }
  .pr-item-code { color:var(--muted); font-size:.74rem; margin-top:.1rem; }

  /* History timeline */
  .pr-history { display:flex; overflow-x:auto; }
  .pr-history-step { min-width:160px; flex:1 0 0; padding:.85rem 1rem; border-right:1px solid var(--line); }
  .pr-history-step:last-child { border-right:none; }
  .pr-history-title { font-size:.82rem; font-weight:650; }
  .pr-history-meta  { color:var(--muted); font-size:.72rem; margin-top:.15rem; }

  @media (max-width:767.98px){
    .page-wrap { padding-inline:.75rem; }
    .summary-col { border-right:none; border-bottom:1px solid var(--line); }
    .summary-col:last-child { border-bottom:none; }
    .pr-lines-table thead { display:none; }
    .pr-lines-table, .pr-lines-table tbody, .pr-lines-table tr, .pr-lines-table td { display:block; width:100%; }
    .pr-lines-table tr { padding:.7rem .85rem; border-bottom:1px solid var(--line); }
    .pr-lines-table td { border:0; padding:.18rem 0; text-align:left !important; }
    .pr-lines-table td[data-label]::before { content:attr(data-label); display:block; color:var(--muted); font-size:.66rem; font-weight:700; text-transform:uppercase; margin-top:.25rem; }
  }
</style>
@endpush

@section('content')
<div class="page-wrap py-3">

  {{-- HEADER --}}
  <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
    <div style="min-width:0;">
      <h2 class="mb-0 lh-1" style="font-size:1.35rem;">Purchase Request</h2>
      <div class="text-muted mono mt-1" style="font-size:.8rem;">Kode: {{ $pr->code }}</div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('purchasing.purchase_requests.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Kembali
      </a>
      @if ($isDraft)
        <a href="{{ route('purchasing.purchase_requests.edit', $pr) }}" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-pencil me-1"></i>Edit
        </a>
      @endif
      @if ($canApproveReject)
        <form method="POST" action="{{ route('purchasing.purchase_requests.approve', $pr) }}"
              onsubmit="return confirm('Setujui {{ $pr->code }}?')">
          @csrf
          <button class="btn btn-success btn-sm" type="submit">
            <i class="bi bi-check-lg me-1"></i>Approve
          </button>
        </form>
        <form method="POST" action="{{ route('purchasing.purchase_requests.reject', $pr) }}"
              onsubmit="return confirm('Tolak {{ $pr->code }}?')">
          @csrf
          <button class="btn btn-outline-danger btn-sm" type="submit">
            <i class="bi bi-x me-1"></i>Tolak
          </button>
        </form>
      @endif
      @if ($isConvertible && ($user->isOwner() || in_array($user->role, ['admin'], true) || $user->isDeveloper()))
        <a href="{{ route('purchasing.purchase_requests.allocate_suppliers', $pr) }}" class="btn btn-primary btn-sm">
          <i class="bi bi-arrow-right-circle me-1"></i>Pilih Supplier
        </a>
      @elseif ($firstPo)
        <a href="{{ route('purchasing.purchase_orders.show', $firstPo) }}" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-box-arrow-up-right me-1"></i>Buka PO
        </a>
      @endif
    </div>
  </div>

  @if (session('success'))<div class="alert alert-success py-2 small mb-3">{{ session('success') }}</div>@endif
  @if (session('error'))<div class="alert alert-danger py-2 small mb-3">{{ session('error') }}</div>@endif

  {{-- SUMMARY ROW --}}
  <div class="card-info mb-3">
    <div class="row g-0">
      <div class="col-6 col-md-3 summary-col">
        <div class="summary-col-label">Status</div>
        <div class="summary-col-value">
          <span class="pr-badge pr-{{ $pr->status }}">{{ pr_status_label($pr->status) }}</span>
        </div>
      </div>
      <div class="col-6 col-md-3 summary-col">
        <div class="summary-col-label">Tanggal</div>
        <div class="summary-col-value mono" style="font-size:.88rem;">{{ $pr->date?->format('d/m/Y') ?? '-' }}</div>
      </div>
      <div class="col-6 col-md-3 summary-col">
        <div class="summary-col-label">Peminta</div>
        <div class="summary-col-value" style="font-size:.88rem;">{{ $pr->requestedBy?->name ?? '-' }}</div>
      </div>
      <div class="col-6 col-md-3 summary-col">
        <div class="summary-col-label">{{ $canSeeMoney ? 'Estimasi' : 'Jumlah Item' }}</div>
        <div class="summary-col-value mono">
          @if ($canSeeMoney)
            {{ $hasEstimate ? rupiah($estTotal) : '—' }}
          @else
            {{ $pr->lines->count() }} item
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- INFO CARD --}}
  <div class="card-info mb-3">
    <div class="row g-3">
      <div class="col-12 col-md-6">
        <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:600;margin-bottom:.5rem;">
          Supplier
        </div>
        @if ($actualSuppliers->isNotEmpty())
          @foreach ($actualSuppliers as $sup)
            <div class="fw-semibold" style="font-size:.88rem;">{{ $sup->name }}</div>
            <div class="text-muted mono" style="font-size:.74rem;">{{ $sup->code }}</div>
          @endforeach
        @elseif ($pr->supplier)
          <div class="fw-semibold" style="font-size:.88rem;">{{ $pr->supplier->name }}</div>
          <div class="text-muted mono" style="font-size:.74rem;">{{ $pr->supplier->code }} · Supplier awal</div>
        @else
          <span class="text-muted" style="font-size:.85rem;">Otomatis saat PO dibuat</span>
        @endif
      </div>
      <div class="col-12 col-md-6">
        <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:600;margin-bottom:.5rem;">
          Purchase Order Terkait
        </div>
        @forelse ($pr->purchaseOrders as $order)
          <a href="{{ route('purchasing.purchase_orders.show', $order) }}"
             class="d-inline-flex align-items-center gap-1 me-2 mb-1 text-decoration-none fw-semibold mono"
             style="font-size:.85rem;">
            <i class="bi bi-box-arrow-up-right" style="font-size:.75rem;"></i>{{ $order->code }}
          </a>
        @empty
          <span class="text-muted" style="font-size:.85rem;">Belum ada PO</span>
        @endforelse
      </div>
      @if ($pr->notes)
        <div class="col-12 pt-2" style="border-top:1px solid var(--line);">
          <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:600;margin-bottom:.35rem;">Catatan</div>
          <div style="font-size:.87rem;white-space:pre-line;">{{ $pr->notes }}</div>
        </div>
      @endif
    </div>
  </div>

  {{-- LINES TABLE --}}
  <div class="card-section mb-3">
    <div class="card-section-header d-flex justify-content-between align-items-center">
      <span>Kebutuhan Barang</span>
      <span>{{ $pr->lines->count() }} item</span>
    </div>
    <div class="table-responsive">
      <table class="table table-sm mb-0 pr-lines-table">
        <thead>
          <tr>
            <th>Barang</th>
            <th class="text-end">Qty</th>
            @if ($canSeeMoney)
              <th class="text-end">Harga Est.</th>
              <th class="text-end">Total</th>
            @endif
            <th>Catatan</th>
            @if ($isConverted)
              <th>Supplier / PO</th>
            @endif
          </tr>
        </thead>
        <tbody>
          @forelse ($pr->lines as $line)
            <tr>
              <td>
                <div class="pr-item-name">{{ $line->item?->name ?? 'Barang tidak ditemukan' }}</div>
                <div class="pr-item-code mono">{{ $line->item?->code }}</div>
              </td>
              <td class="text-end mono" data-label="Qty">
                {{ number_format($line->qty, 2, ',', '.') }} {{ $line->item?->unit }}
              </td>
              @if ($canSeeMoney)
                <td class="text-end mono" data-label="Harga">
                  {{ $line->unit_price !== null ? rupiah($line->unit_price) : '—' }}
                </td>
                <td class="text-end mono" data-label="Total">
                  {{ $line->unit_price !== null ? rupiah($line->qty * $line->unit_price) : '—' }}
                </td>
              @endif
              <td data-label="Catatan" class="text-muted">{{ $line->notes ?? '—' }}</td>
              @if ($isConverted)
                <td data-label="Supplier/PO">
                  <span class="fw-semibold">{{ $line->supplier?->code ?? '—' }}</span>
                  @if ($line->purchaseOrder)
                    <a href="{{ route('purchasing.purchase_orders.show', $line->purchaseOrder) }}"
                       class="d-block mono text-decoration-none" style="font-size:.75rem;">
                      {{ $line->purchaseOrder->code }}
                    </a>
                  @endif
                </td>
              @endif
            </tr>
          @empty
            <tr>
              <td colspan="{{ 3 + ($canSeeMoney ? 2 : 0) + ($isConverted ? 1 : 0) }}"
                  class="text-center text-muted py-4">Belum ada barang.</td>
            </tr>
          @endforelse
        </tbody>
        @if ($canSeeMoney && $hasEstimate && $pr->lines->count() > 1)
          <tfoot>
            <tr class="fw-semibold" style="border-top:2px solid var(--line);">
              <td colspan="{{ $isConverted ? 2 : 2 }}" class="text-end" style="padding:.5rem .85rem;font-size:.8rem;">Total Estimasi</td>
              <td style="padding:.5rem .85rem;font-size:.8rem;"></td>
              <td class="text-end mono" style="padding:.5rem .85rem;font-size:.85rem;">{{ rupiah($estTotal) }}</td>
              <td colspan="{{ $isConverted ? 2 : 1 }}"></td>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>
  </div>

  {{-- HISTORY --}}
  <div class="card-section">
    <div class="card-section-header">Riwayat</div>
    <div class="pr-history">
      <div class="pr-history-step">
        <div class="pr-history-title">Dibuat</div>
        <div class="pr-history-meta">{{ $pr->requestedBy?->name ?? '—' }}</div>
        <div class="pr-history-meta">{{ $pr->created_at?->format('d/m/Y H:i') }}</div>
      </div>
      @if ($pr->approved_by || $pr->isApproved() || $pr->isConverted())
        <div class="pr-history-step">
          <div class="pr-history-title text-success">Disetujui</div>
          <div class="pr-history-meta">{{ $pr->approvedBy?->name ?? '—' }}</div>
        </div>
      @endif
      @if ($pr->isRejected())
        <div class="pr-history-step">
          <div class="pr-history-title text-danger">Ditolak</div>
          <div class="pr-history-meta">{{ $pr->rejectedBy?->name ?? '—' }}</div>
          <div class="pr-history-meta">{{ $pr->updated_at?->format('d/m/Y H:i') }}</div>
        </div>
      @endif
      @if ($pr->isConverted())
        <div class="pr-history-step">
          <div class="pr-history-title" style="color:#1d4ed8;">Dibuatkan PO</div>
          <div class="pr-history-meta mono">{{ $pr->purchaseOrders->pluck('code')->join(', ') }}</div>
          <div class="pr-history-meta">{{ $pr->converted_at?->format('d/m/Y H:i') }}</div>
        </div>
      @endif
      @if ($isDraft)
        <div class="pr-history-step">
          <div class="pr-history-title text-muted">Menunggu persetujuan</div>
          <div class="pr-history-meta">Belum diproses</div>
        </div>
      @endif
    </div>
  </div>

</div>
@endsection
