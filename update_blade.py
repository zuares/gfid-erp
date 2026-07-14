import sys

# We'll construct the new blade content using the existing logic

blade_content = r"""{{-- resources/views/purchasing/purchase_returns/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Return Pembelian ' . ($ret->code ?? ''))

@push('head')
<style>
  /* ===== Compact & Minimalist Purchase Return ===== */
  .pr-wrap { max-width: 1040px; margin: 0 auto; padding: 1.5rem 1rem 3rem; color: #374151; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
  .pr-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-variant-numeric: tabular-nums; }
  
  .pr-topbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 1rem; }
  .pr-topbar-left { display: flex; align-items: center; gap: 1rem; }
  .pr-code { font-weight: 700; font-size: 1.5rem; color: #111827; letter-spacing: -0.025em; }
  .pr-sub { color: #6b7280; font-size: 0.875rem; }
  
  .pr-btn {
    display: inline-flex; align-items: center; gap: 0.375rem; border-radius: 6px; padding: 0.375rem 0.75rem;
    font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.2s;
    border: 1px solid #d1d5db; background: #fff; color: #374151; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  }
  .pr-btn:hover { background: #f9fafb; border-color: #9ca3af; color: #111827; }
  .pr-btn i { font-size: 0.95rem; }
  .pr-btn-primary { background: #111827; border-color: #111827; color: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
  .pr-btn-primary:hover { background: #374151; border-color: #374151; color: #fff; }
  .pr-btn-danger { color: #dc2626; border-color: #fca5a5; background: #fef2f2; }
  .pr-btn-danger:hover { background: #fee2e2; border-color: #f87171; }
  .pr-btn-success { background: #059669; border-color: #059669; color: white; }
  .pr-btn-success:hover { background: #047857; color: white; }
  .pr-btn:disabled { opacity: 0.5; cursor: not-allowed; }

  /* Info Grid */
  .pr-header-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
  .pr-info-box { padding: 1.25rem; background: #fafafa; border-radius: 10px; border: 1px solid #f0f0f0; display: flex; flex-direction: column; justify-content: center; }
  .pr-info-label { font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.375rem; }
  .pr-info-val { font-size: 0.95rem; font-weight: 600; color: #111827; }
  .pr-info-val-lg { font-size: 1.5rem; font-weight: 700; color: #111827; margin-top: 0.25rem; }
  .pr-info-val a { color: #4f46e5; text-decoration: none; }
  .pr-info-val a:hover { text-decoration: underline; }
  .pr-info-sub { font-size: 0.75rem; color: #9ca3af; margin-top: 0.125rem; }

  /* Form Elements */
  .pr-section-title { font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem; border-bottom: 1px solid #f3f4f6; padding-bottom: 0.5rem; }
  .pr-formbar { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem; background: #fff; padding: 1.25rem; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
  .pr-form-group { display: flex; flex-direction: column; gap: 0.375rem; }
  .pr-form-group label { font-size: 0.75rem; font-weight: 600; color: #4b5563; }
  .pr-input, .pr-select { border: 1px solid #d1d5db; border-radius: 6px; padding: 0.5rem 0.75rem; font-size: 0.875rem; outline: none; transition: border-color 0.2s; background: #fff; width: 100%; }
  .pr-input:focus, .pr-select:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
  
  /* Table */
  .pr-table-wrap { border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
  .pr-table { width: 100%; border-collapse: collapse; text-align: left; background: #fff; }
  .pr-table th { background: #f9fafb; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; padding: 0.875rem 1rem; border-bottom: 1px solid #e5e7eb; letter-spacing: 0.05em; }
  .pr-table td { padding: 1rem; border-bottom: 1px solid #f3f4f6; vertical-align: top; font-size: 0.875rem; color: #374151; }
  .pr-table tbody tr:last-child td { border-bottom: none; }
  .pr-table tbody tr.has-qty { background-color: #f0fdf4; }
  .pr-table tbody tr.is-empty { opacity: 0.8; }
  .pr-table tbody tr:hover { background: #fdfdfd; }
  .pr-table tbody tr.has-qty:hover { background-color: #ecfdf5; }

  /* Item Layout in Table */
  .item-title { font-weight: 600; color: #111827; }
  .item-meta { font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; }
  .item-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 0.75rem; }
  
  /* Compact Photo Upload */
  .photo-upload-row { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-top: 0.75rem; }
  .photo-thumb-wrap { position: relative; }
  .photo-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid #d1d5db; cursor: pointer; }
  .photo-del { position: absolute; top: -6px; right: -6px; background: #ef4444; color: white; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 10px; cursor: pointer; text-decoration: none; border: none; padding: 0; }
  .photo-add-btn { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; color: #4f46e5; cursor: pointer; padding: 0.375rem 0.625rem; border-radius: 6px; background: #eef2ff; border: 1px dashed #c7d2fe; transition: all 0.2s; font-weight: 500; }
  .photo-add-btn:hover { background: #e0e7ff; border-color: #a5b4fc; }

  /* Pills */
  .pr-badge { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.625rem; font-size: 0.75rem; font-weight: 600; border-radius: 999px; white-space: nowrap; }
  .pr-badge::before { content: ""; width: 6px; height: 6px; border-radius: 50%; }
  .badge-draft { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
  .badge-draft::before { background: #f59e0b; }
  .badge-posted { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
  .badge-posted::before { background: #22c55e; }
  .badge-void { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
  .badge-void::before { background: #ef4444; }
  .badge-info { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
  .badge-info::before { background: #3b82f6; }
  .badge-gray { background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
  .badge-gray::before { display: none; }
  
  .metric-pill { display: inline-flex; padding: 0.125rem 0.375rem; font-size: 0.7rem; font-weight: 600; border-radius: 4px; background: #f3f4f6; color: #4b5563; margin-right: 0.25rem; margin-bottom: 0.25rem; }
  .metric-pill.blue { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
  .metric-pill.green { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }
  .metric-pill.red { background: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }

  /* Qty Input */
  .qty-input-group { display: flex; flex-direction: column; align-items: flex-end; gap: 0.375rem; }
  .qty-input { width: 90px; text-align: right; font-weight: 600; padding: 0.375rem 0.5rem; }
  .qty-btn-group { display: flex; gap: 0.25rem; }
  .qty-btn { padding: 0.125rem 0.5rem; font-size: 0.7rem; font-weight: 600; border-radius: 4px; background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; cursor: pointer; transition: all 0.15s; }
  .qty-btn:hover { background: #e5e7eb; color: #111827; }

  .pr-footer { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
  
  /* Alerts */
  .pr-alert { border-radius: 8px; font-size: 0.875rem; padding: 0.75rem 1rem; margin-bottom: 1.5rem; border: 1px solid transparent; font-weight: 500; }
  .pr-alert-success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
  .pr-alert-danger { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
  .pr-alert-info { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }
  .pr-alert-warn { background: #fffbeb; border-color: #fde68a; color: #92400e; }

  @media (max-width: 768px) {
    .pr-header-grid { grid-template-columns: 1fr; }
    .pr-table thead { display: none; }
    .pr-table tbody tr { display: block; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 0.75rem; padding: 0.5rem; background: #fff; }
    .pr-table tbody td { display: flex; justify-content: space-between; align-items: flex-start; border: none; padding: 0.5rem; }
    .pr-table tbody td::before { content: attr(data-label); font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; width: 40%; flex-shrink: 0; }
    .item-actions { grid-template-columns: 1fr; }
    .qty-input-group { align-items: flex-start; }
    .qty-input { width: 100%; text-align: left; }
    .pr-table-wrap { border: none; background: transparent; box-shadow: none; }
  }
</style>
@endpush

@section('content')
@php
  $canSeeMoney = auth()->user()?->isOwner() ?? false;
  $isDraft = ($ret->status ?? '') === 'draft';
  $isSubmitted = ($ret->status ?? '') === 'submitted';
  $isPosted = ($ret->status ?? '') === 'posted';
  $isVoided = (bool) ($ret->voided_at);
  $isEditable = ($isDraft || $isSubmitted) && ! $isVoided;
  $reasons = \App\Models\PurchaseReturnLine::REASONS;

  $grand = (float)($ret->total ?? 0);
  $totalLines = (int) (($returnRows ?? collect())->count());
  $totalReturnLines = (int) (($returnRows ?? collect())->filter(fn($row) => (float) $row->qty > 0.0001)->count());
  $totalQty = (float) (($returnRows ?? collect())->sum('qty'));
  $grnHref = route('purchasing.purchase_receipts.show', $ret->purchase_receipt_id ?? $ret->grn_id ?? ($ret->grn?->id ?? 0));
  $dateValue = old('date', $ret->date ? \Illuminate\Support\Carbon::parse($ret->date)->format('Y-m-d') : now()->toDateString());
  
  $effect = $journalEffect ?? [];
  $effectTotal = (float) ($effect['total'] ?? 0);
  $effectInv = (float) ($effect['inventory_total'] ?? 0);
  $effectAp = (float) ($effect['ap_portion'] ?? 0);
  $effectClaim = (float) ($effect['claim_portion'] ?? 0);
  
  $totalReceived = (float) (($returnRows ?? collect())->sum('replacement_qty_received'));
  $hasReceivedReplacement = $ret->resolution_type === 'replacement' && (in_array($ret->replacement_status, ['partial', 'received']) || $totalReceived > 0);

  if ($isVoided) { $statusPill = 'badge-void'; $statusText = 'Void'; }
  elseif ($isPosted) {
    if ($ret->resolution_type === 'replacement' && $ret->replacement_status === 'pending') { $statusPill = 'badge-draft'; $statusText = 'Menunggu Pengganti'; }
    elseif ($ret->resolution_type === 'replacement' && $ret->replacement_status === 'partial') { $statusPill = 'badge-info'; $statusText = 'Diterima Sebagian'; }
    elseif ($ret->resolution_type === 'replacement' && $ret->replacement_status === 'received') { $statusPill = 'badge-posted'; $statusText = 'Pengganti Diterima'; }
    else { $statusPill = 'badge-posted'; $statusText = 'Posted'; }
  }
  elseif ($isSubmitted) { $statusPill = 'badge-info'; $statusText = 'Diajukan'; }
  else { $statusPill = 'badge-draft'; $statusText = 'Draft'; }
@endphp

<div class="pr-wrap">
  {{-- TOPBAR --}}
  <div class="pr-topbar">
    <div class="pr-topbar-left">
      <a href="{{ route('purchasing.purchase_returns.index') }}" class="pr-btn pr-btn-gray">
        <i class="bi bi-arrow-left"></i>
      </a>
      <div>
        <div class="pr-code">{{ $ret->code }}</div>
        <div class="pr-sub">Return Pembelian</div>
      </div>
      <span class="pr-badge {{ $statusPill }} ms-2">{{ $statusText }}</span>
    </div>
    <div class="d-flex gap-2 align-items-center">
      <div class="text-end me-3">
        <div style="font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase;">Total Qty</div>
        <div class="pr-mono" style="font-weight: 700; color: #111827;">{{ decimal_id($totalQty, 2) }}</div>
      </div>
      <a href="{{ $grnHref }}" class="pr-btn"><i class="bi bi-box-arrow-up-right"></i> Lihat GRN</a>
    </div>
  </div>

  {{-- ALERTS --}}
  @if(session('success')) <div class="pr-alert pr-alert-success">{{ session('success') }}</div> @endif
  @if(session('error')) <div class="pr-alert pr-alert-danger">{{ session('error') }}</div> @endif
  @if($errors->any())
    <div class="pr-alert pr-alert-danger">
      <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
      <ul class="mb-0 ps-3">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  {{-- INFO GRID --}}
  <div class="pr-header-grid">
    <div class="pr-info-box">
      <div class="pr-info-label">Informasi Dokumen</div>
      <div class="pr-info-val">{{ $ret->date ? \Illuminate\Support\Carbon::parse($ret->date)->format('d M Y') : '-' }}</div>
      <div class="pr-info-sub">Gudang: <strong>{{ $ret->grn?->warehouse?->name ?? '-' }}</strong></div>
    </div>
    <div class="pr-info-box">
      <div class="pr-info-label">Supplier</div>
      <div class="pr-info-val">{{ $ret->grn?->supplier?->name ?? '-' }}</div>
      <div class="pr-info-sub pr-mono">{{ $ret->grn?->supplier?->code }}</div>
    </div>
    <div class="pr-info-box">
      <div class="pr-info-label">Tipe & Status</div>
      <div class="pr-info-val">{{ $ret->resolution_type === 'replacement' ? 'Tukar Barang' : 'Refund' }}</div>
      <div class="pr-info-sub">
        @if($isDraft)
          <span style="color: {{ $stockReady ? '#16a34a' : '#dc2626' }}; font-weight: 600;">{{ $stockReady ? 'Stok Siap' : 'Stok Kurang' }}</span>
        @else
          {{ $journalCount > 0 ? $journalCount . ' jurnal' : 'Belum ada jurnal' }}
        @endif
      </div>
    </div>
    @if($canSeeMoney)
    <div class="pr-info-box">
      <div class="pr-info-label">Total Nilai Return</div>
      <div class="pr-info-val-lg pr-mono">{{ rupiah($grand) }}</div>
    </div>
    @endif
  </div>

  @if($canSeeMoney && ($isDraft || $isPosted))
  {{-- COMPACT EFFECT JURNAL --}}
  <div class="pr-formbar" style="background: #f8fafc; border-color: #e2e8f0; margin-bottom: 2rem;">
    <div>
      <div class="pr-info-label">Efek Return</div>
      <div class="text-muted" style="font-size: 0.75rem;">
        {{ $isVoided ? 'Efek dibatalkan.' : ($isPosted ? 'Efek telah diproses.' : 'Efek setelah diposting.') }}
      </div>
    </div>
    <div>
      <div class="pr-info-label">Kurangi Stok</div>
      <div class="pr-info-val pr-mono">{{ rupiah($effectInv) }}</div>
    </div>
    <div>
      <div class="pr-info-label">Potong Hutang</div>
      <div class="pr-info-val pr-mono">{{ $isDraft && !$isVoided ? rupiah($effectAp) : '-' }}</div>
    </div>
    <div>
      <div class="pr-info-label">Klaim Supplier</div>
      <div class="pr-info-val pr-mono" style="color: {{ $effectClaim > 0 ? '#b91c1c' : 'inherit' }};">{{ $isDraft && !$isVoided ? rupiah($effectClaim) : '-' }}</div>
    </div>
  </div>
  @endif

  {{-- FORM & TABLE --}}
  <form method="POST" action="{{ route('purchasing.purchase_returns.update', $ret->id) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div class="pr-section-title mb-0 border-0">Detail Item Return</div>
      <div style="font-size: 0.875rem; color: #6b7280; font-weight: 500;">
        <span id="live-return-lines" class="text-dark fw-bold">{{ $totalReturnLines }}</span> / {{ $totalLines }} item dipilih
      </div>
    </div>

    @if($isEditable)
      <div class="pr-formbar">
        <div class="pr-form-group">
          <label>Tanggal Return</label>
          <input type="text" name="date" class="pr-input gf-date-input pr-mono" value="{{ $dateValue }}" data-gf-date autocomplete="off" required>
        </div>
        <div class="pr-form-group">
          <label>Tipe Penyelesaian</label>
          <select name="resolution_type" class="pr-select">
            <option value="refund" {{ old('resolution_type', $ret->resolution_type) === 'refund' ? 'selected' : '' }}>Refund (Potong Hutang/Uang Kembali)</option>
            <option value="replacement" {{ old('resolution_type', $ret->resolution_type) === 'replacement' ? 'selected' : '' }}>Tukar Barang (Replacement)</option>
          </select>
        </div>
        <div class="pr-form-group" style="grid-column: span 2;">
          <label>Catatan Umum (Opsional)</label>
          <input type="text" name="notes" class="pr-input" value="{{ old('notes', $ret->notes) }}" placeholder="Tambahkan catatan jika perlu...">
        </div>
      </div>
      
      <div class="d-flex gap-2 mb-3">
        <button type="button" class="pr-btn pr-btn-sm" id="btn-zero-all">Reset Qty</button>
        <button type="button" class="pr-btn pr-btn-sm" id="btn-max-all">Maks Qty</button>
      </div>
    @else
      <input type="hidden" name="date" value="{{ $dateValue }}">
      <input type="hidden" name="notes" value="{{ $ret->notes }}">
      <input type="hidden" name="resolution_type" value="{{ $ret->resolution_type }}">
    @endif

    <div class="pr-table-wrap">
      <table class="pr-table">
        <thead>
          <tr>
            <th>Item & Alasan</th>
            <th class="text-end">Tersedia</th>
            <th class="text-end" style="width: 140px;">Qty Return</th>
            @if($canSeeMoney)<th class="text-end">Nilai (Rp)</th>@endif
          </tr>
        </thead>
        <tbody>
          @foreach($returnRows as $i => $row)
            @php
              $ln = $row->line;
              $rem = (float)($row->remaining ?? 0);
              $stock = (float)($row->stock ?? 0);
              $lotStock = $row->lot_stock;
              $shownStock = $lotStock !== null ? (float) $lotStock : $stock;
              $maxReturn = (float)($row->max_return ?? $rem);
              $isInventoryLine = (bool)($row->is_inventory ?? true);
              $qty = (float)($row->qty ?? 0);
              $unitPrice = (float)($row->unit_price ?? 0);
              $lineTotal = (float)($row->line_total ?? 0);
              $rowClass = $qty > 0.0001 ? 'has-qty' : 'is-empty';
              $stockOk = $shownStock + .0001 >= $qty;
            @endphp
            <tr class="return-row {{ $rowClass }}">
              <td data-label="Item">
                <div class="item-title">{{ $row->item?->name ?? '-' }}</div>
                <div class="item-meta pr-mono">{{ $row->item?->code ?? '-' }} @if($row->lot_id) • LOT #{{ $row->lot_id }} @endif</div>
                
                @if($isEditable)
                  <input type="hidden" name="lines[{{ $i }}][id]" value="{{ $ln?->id }}">
                  <input type="hidden" name="lines[{{ $i }}][purchase_receipt_line_id]" value="{{ $row->purchase_receipt_line_id }}">
                  
                  <div class="item-actions">
                    <select name="lines[{{ $i }}][reason_code]" class="pr-select" style="padding: 0.375rem; font-size: 0.75rem;">
                      <option value="">- Pilih Alasan -</option>
                      @foreach($reasons as $code => $label)
                        <option value="{{ $code }}" @selected(old("lines.$i.reason_code", $ln?->reason_code) === $code)>{{ $label }}</option>
                      @endforeach
                    </select>
                    <input type="text" name="lines[{{ $i }}][notes]" class="pr-input" style="padding: 0.375rem; font-size: 0.75rem;" placeholder="Catatan item..." value="{{ old("lines.$i.notes", $row->notes) }}">
                  </div>

                  <div class="photo-upload-row">
                    @if($ln && $ln->photos && $ln->photos->count())
                      @foreach($ln->photos as $photo)
                        <div class="photo-thumb-wrap">
                          <img src="{{ $photo->url }}" alt="foto" class="photo-thumb" title="{{ $photo->original_name }}">
                          <label class="photo-del" title="Hapus Foto">
                            <i class="bi bi-x"></i>
                            <input type="checkbox" name="delete_photos[]" value="{{ $photo->id }}" hidden>
                          </label>
                        </div>
                      @endforeach
                    @endif
                    <label class="photo-add-btn">
                      <i class="bi bi-camera"></i> Tambah Foto
                      <input type="file" name="lines[{{ $i }}][photos][]" accept="image/*" multiple hidden>
                    </label>
                  </div>
                @else
                  @if($row->reason_label ?? ($ln?->reason_label))
                    <div class="mt-2" style="font-size: 0.8rem;"><span class="text-muted">Alasan:</span> <strong>{{ $ln?->reason_label }}</strong></div>
                  @endif
                  @if($ln && $ln->photos && $ln->photos->count())
                    <div class="photo-upload-row">
                      @foreach($ln->photos as $photo)
                        <a href="{{ $photo->url }}" target="_blank"><img src="{{ $photo->url }}" class="photo-thumb" alt="foto"></a>
                      @endforeach
                    </div>
                  @endif
                @endif
              </td>
              
              <td data-label="Tersedia" class="text-end">
                <div class="d-flex flex-column align-items-end gap-1">
                  <span class="metric-pill blue">Maks: <span class="pr-mono ms-1">{{ rtrim(rtrim(number_format($maxReturn, 4, ',', '.'), '0'), ',') }}</span></span>
                  @if($isInventoryLine)
                    <span class="metric-pill {{ $stockOk ? 'green' : 'red' }}">{{ $lotStock !== null ? 'Lot:' : 'Stok:' }} <span class="pr-mono ms-1">{{ rtrim(rtrim(number_format($shownStock, 4, ',', '.'), '0'), ',') }}</span></span>
                  @else
                    <span class="metric-pill">Non-stok</span>
                  @endif
                </div>
              </td>
              
              <td data-label="Qty Return" class="text-end">
                @if($isEditable)
                  <div class="qty-input-group">
                    <input type="number" name="lines[{{ $i }}][qty]" class="pr-input qty-input qty-return-input" value="{{ old("lines.$i.qty", $qty > 0.0001 ? $qty : '') }}" step="0.0001" min="0" max="{{ $maxReturn }}" placeholder="0" data-max="{{ $maxReturn }}">
                    <div class="qty-btn-group">
                      <button type="button" class="qty-btn js-zero-row">0</button>
                      <button type="button" class="qty-btn js-max-row">Maks</button>
                    </div>
                  </div>
                @else
                  <div class="pr-mono" style="font-weight: 700; font-size: 1rem;">{{ rtrim(rtrim(number_format($qty, 4, ',', '.'), '0'), ',') }}</div>
                @endif
              </td>
              
              @if($canSeeMoney)
                <td data-label="Nilai" class="text-end pr-mono">
                  <div style="font-weight: 700; color: #111827;">{{ number_format($lineTotal, 0, ',', '.') }}</div>
                  <div style="font-size: 0.7rem; color: #9ca3af; margin-top: 0.25rem;">@ {{ number_format($unitPrice, 0, ',', '.') }}</div>
                </td>
              @endif
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @if($isEditable)
      <div class="d-flex justify-content-end mb-4">
        <button type="submit" class="pr-btn pr-btn-primary"><i class="bi bi-save"></i> Simpan Draft Return</button>
      </div>
    @endif
  </form>

  {{-- ACTIONS FOOTER --}}
  <div class="pr-footer">
    <div class="d-flex gap-2 align-items-center flex-wrap">
      @if($isSubmitted) <span class="pr-badge badge-info"><i class="bi bi-hourglass me-1"></i> Menunggu Persetujuan</span> @endif
      @if($isEditable && !$stockReady) <span class="pr-badge badge-void"><i class="bi bi-exclamation-circle me-1"></i> Stok tidak mencukupi</span> @endif
    </div>

    <div class="d-flex gap-2 flex-wrap">
      @if($isDraft && !$isVoided)
        <form method="POST" action="{{ route('purchasing.purchase_returns.submit', $ret->id) }}" class="js-submit-return m-0">
          @csrf <button type="submit" class="pr-btn"><i class="bi bi-send"></i> Ajukan Persetujuan</button>
        </form>
      @endif

      @if($isEditable)
        <form method="POST" action="{{ route('purchasing.purchase_returns.post', $ret->id) }}" class="js-post-return m-0">
          @csrf
          <button type="submit" class="pr-btn {{ $stockReady ? 'pr-btn-success' : 'pr-btn-danger' }}" {{ $stockReady ? '' : 'disabled' }}>
            <i class="bi bi-check2-circle"></i> {{ $stockReady ? 'Post Return' : 'Sesuaikan Qty' }}
          </button>
        </form>
      @endif

      @if($isPosted && !$isVoided)
        @if($ret->resolution_type === 'replacement' && in_array($ret->replacement_status, ['pending', 'partial']))
          <button type="button" class="pr-btn pr-btn-primary" data-bs-toggle="modal" data-bs-target="#receiveReplacementModal">
            <i class="bi bi-box-seam"></i> Terima Pengganti
          </button>
        @endif
        @if(!$hasReceivedReplacement)
          <form method="POST" action="{{ route('purchasing.purchase_returns.void', $ret->id) }}" class="js-void-return m-0">
            @csrf <button type="submit" class="pr-btn pr-btn-danger"><i class="bi bi-x-circle"></i> Void Return</button>
          </form>
        @else
          <div class="text-danger" style="font-size: 0.75rem; font-weight: 500;">* Tidak dapat di-void (ada penerimaan pengganti)</div>
        @endif
      @endif
    </div>
  </div>

</div>

{{-- MODAL RECEIVE REPLACEMENT (Unchanged structure, refined style classes) --}}
@if($isPosted && !$isVoided && $ret->resolution_type === 'replacement' && in_array($ret->replacement_status, ['pending', 'partial']))
<div class="modal fade" id="receiveReplacementModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
      <form action="{{ route('purchasing.purchase_returns.receive_replacement', $ret->id) }}" method="POST">
        @csrf
        <div class="modal-header" style="border-bottom: 1px solid #f3f4f6;">
          <h6 class="modal-title fw-bold">Terima Barang Pengganti</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3 mb-4">
            <div class="col-md-6 pr-form-group">
              <label>Tanggal Terima</label>
              <input type="text" name="received_at" class="pr-input gf-date-input" required value="{{ now()->toDateString() }}">
            </div>
            <div class="col-md-6 pr-form-group">
              <label>Gudang Penerima</label>
              <select name="warehouse_id" class="pr-select" required>
                <option value="">Pilih Gudang...</option>
                @foreach(\App\Models\Warehouse::all() as $wh)
                  <option value="{{ $wh->id }}" {{ $ret->grn?->warehouse_id == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12 pr-form-group">
              <label>Catatan</label>
              <input type="text" name="notes" class="pr-input">
            </div>
          </div>
          <div class="table-responsive border rounded-3">
            <table class="table table-sm align-middle mb-0" style="font-size: 0.875rem;">
              <thead class="bg-light text-uppercase text-secondary" style="font-size: 0.75rem;">
                <tr>
                  <th class="py-2 px-3">Item</th>
                  <th class="py-2 px-3 text-end">Menunggu</th>
                  <th class="py-2 px-3 text-end" width="120">Qty Terima</th>
                </tr>
              </thead>
              <tbody>
                @foreach($ret->lines as $line)
                  @if(round((float)$line->replacement_qty_expected - (float)$line->replacement_qty_received, 4) > 0)
                  @php $outstanding = round((float)$line->replacement_qty_expected - (float)$line->replacement_qty_received, 4); @endphp
                  <tr>
                    <td class="px-3 py-2">
                      <div class="fw-semibold text-dark">{{ $line->item?->name }}</div>
                      <div class="text-muted" style="font-size: 0.75rem;">{{ $line->item?->code }}</div>
                      <input type="hidden" name="lines[{{ $line->id }}][id]" value="{{ $line->id }}">
                    </td>
                    <td class="px-3 py-2 text-end pr-mono">{{ $outstanding }}</td>
                    <td class="px-3 py-2">
                      <input type="number" step="0.0001" min="0" max="{{ $outstanding }}" name="lines[{{ $line->id }}][qty]" class="form-control form-control-sm text-end pr-mono" value="{{ $outstanding }}" required>
                    </td>
                  </tr>
                  @endif
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer" style="border-top: 1px solid #f3f4f6;">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-dark px-4">Simpan Penerimaan</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const qtyInputs = Array.from(document.querySelectorAll('.qty-return-input'));
  const liveLines = document.getElementById('live-return-lines');

  function toNumber(value) {
    if (value === null || value === '') return 0;
    return Number(String(value).replace(',', '.')) || 0;
  }

  function refreshRow(input) {
    const row = input.closest('.return-row');
    if (!row) return;
    const qty = toNumber(input.value);
    row.classList.toggle('has-qty', qty > 0.0001);
    row.classList.toggle('is-empty', qty <= 0.0001);
  }

  function refreshTotals() {
    let count = 0;
    qtyInputs.forEach(function (input) {
      const qty = toNumber(input.value);
      if (qty > 0.0001) count++;
      refreshRow(input);
    });
    if (liveLines) liveLines.textContent = count;
  }

  qtyInputs.forEach(function (input) {
    input.addEventListener('focus', function () { setTimeout(function () { input.select(); }, 0); });
    input.addEventListener('input', refreshTotals);
  });

  document.querySelectorAll('.js-zero-row').forEach(function (button) {
    button.addEventListener('click', function () {
      const input = button.closest('.qty-input-group')?.querySelector('.qty-return-input');
      if (!input) return;
      input.value = '';
      input.focus();
      refreshTotals();
    });
  });

  document.querySelectorAll('.js-max-row').forEach(function (button) {
    button.addEventListener('click', function () {
      const input = button.closest('.qty-input-group')?.querySelector('.qty-return-input');
      if (!input) return;
      input.value = input.dataset.max || input.max || '';
      input.focus();
      refreshTotals();
    });
  });

  document.getElementById('btn-zero-all')?.addEventListener('click', function () {
    qtyInputs.forEach(function (input) { input.value = ''; });
    qtyInputs[0]?.focus();
    refreshTotals();
  });

  document.getElementById('btn-max-all')?.addEventListener('click', function () {
    qtyInputs.forEach(function (input) { input.value = input.dataset.max || input.max || ''; refreshRow(input); });
    qtyInputs[0]?.focus();
    refreshTotals();
  });

  refreshTotals();

  function confirmSubmit(form, options) {
    form.addEventListener('submit', function (event) {
      if (form.dataset.confirmed === '1' || !window.Swal) return;
      event.preventDefault();
      Swal.fire({
        icon: options.icon,
        title: options.title,
        text: options.text,
        showCancelButton: true,
        confirmButtonText: options.confirmText,
        cancelButtonText: 'Batal',
        confirmButtonColor: options.color,
        reverseButtons: true
      }).then(function (result) {
        if (!result.isConfirmed) return;
        form.dataset.confirmed = '1';
        form.submit();
      });
    });
  }

  document.querySelectorAll('.js-post-return').forEach(function (form) {
    confirmSubmit(form, {
      icon: 'question',
      title: 'Posting return?',
      text: 'Draft akan resmi: stok keluar dan jurnal tercatat.',
      confirmText: 'Ya, Posting',
      color: '#059669'
    });
  });

  document.querySelectorAll('.js-void-return').forEach(function (form) {
    confirmSubmit(form, {
      icon: 'warning',
      title: 'Void return?',
      text: 'Stok akan dikembalikan dan jurnal dibatalkan.',
      confirmText: 'Ya, Void',
      color: '#dc2626'
    });
  });
});
</script>
@endpush
"""

with open('/Users/ariefmuhamad/Herd/gfid-dev/resources/views/purchasing/purchase_returns/show.blade.php', 'w') as f:
    f.write(blade_content)
