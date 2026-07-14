import re

content = r"""{{-- resources/views/purchasing/purchase_returns/show.blade.php --}}
@extends('layouts.app')
@section('title', 'Return Pembelian ' . ($ret->code ?? ''))

@push('head')
<style>
/* ===== Shipment-Aligned Compact Styling ===== */
.pr-wrap { max-width:1040px; margin-inline:auto; padding:.7rem .75rem 3rem; }
.pr-topbar { position:sticky; top:0; z-index:250; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; padding:.55rem .75rem; background:var(--card,#fff); border-bottom:1px solid rgba(148,163,184,.18); margin-bottom:.65rem; }
.pr-code { font-weight:900; font-size:1rem; color:#111827; }
.pr-sub { color:#64748b; font-size:.74rem; font-weight:650; }
.pr-spacer { flex:1; }

.pr-btn, .pr-pill { display:inline-flex; align-items:center; justify-content:center; gap:.35rem; border-radius:7px; border:1px solid rgba(148,163,184,.3); background:transparent; color:#475569; text-decoration:none; font-size:.76rem; padding:.28rem .6rem; min-height:34px; font-weight:800; cursor:pointer; transition:background .15s; }
.pr-btn:hover { background:rgba(148,163,184,.09); color:#111827; text-decoration:none; }
.pr-primary { background:#334155!important; border-color:#334155!important; color:#fff!important; }
.pr-btn-danger { color:#b91c1c; border-color:rgba(220,38,38,.4); background:transparent; }
.pr-btn-danger:hover { background:rgba(220,38,38,.08); }
.pr-btn-success { background:#16a34a!important; border-color:#16a34a!important; color:#fff!important; }

/* Status Pill */
.pr-status { font-weight:850; color:#334155; background:rgba(148,163,184,.08); }
.badge-posted, .badge-info { color:#166534; background:rgba(22,101,52,.08); border-color:rgba(22,101,52,.2); }
.badge-void, .badge-danger { color:#991b1b; background:rgba(153,27,27,.08); border-color:rgba(153,27,27,.2); }
.badge-draft { color:#92400e; background:rgba(245,158,11,.08); border-color:rgba(245,158,11,.25); }

/* KPI Grid (aligned with shipments sd-grid) */
.pr-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.55rem; margin-bottom:.65rem; }
.pr-card { background:var(--card,#fff); border:1px solid rgba(148,163,184,.18); border-radius:8px; overflow:hidden; margin-bottom:.65rem; }
.pr-kpi { padding:.65rem .75rem; }
.pr-label { font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.02em; }
.pr-value { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:1.18rem; font-weight:900; color:#111827; margin-top:.12rem; }
.pr-muted { color:#64748b; font-size:.8rem; margin-top:.15rem; }

/* Meta Box */
.pr-meta { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:.5rem; padding:.75rem .85rem; }
.pr-meta-box { border:1px solid rgba(148,163,184,.16); border-radius:8px; padding:.55rem .65rem; }

/* Card sections */
.pr-head { display:flex; align-items:center; gap:.55rem; justify-content:space-between; padding:.7rem .85rem; border-bottom:1px solid rgba(148,163,184,.12); flex-wrap:wrap; }
.pr-title { font-weight:900; color:#334155; }
.pr-body { padding:.75rem .85rem; }

/* Form Elements */
.pr-formbar { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:.55rem; margin-bottom:.65rem; }
.pr-form-group { display:flex; flex-direction:column; gap:.25rem; }
.pr-form-group label { font-size:.72rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.02em; }
.pr-input, .pr-select { border:1px solid rgba(148,163,184,.3); border-radius:7px; padding:.35rem .5rem; font-size:.86rem; outline:none; transition:border-color .15s; background:#fff; color:#334155; width:100%; }
.pr-input:focus, .pr-select:focus { border-color:#334155; box-shadow:0 0 0 2px rgba(51,65,85,.1); }

/* Table */
.pr-table-wrap { overflow:auto; border:1px solid rgba(148,163,184,.16); border-radius:8px; }
.pr-table { width:100%; border-collapse:collapse; }
.pr-table th, .pr-table td { padding:.55rem .65rem; border-bottom:1px solid rgba(148,163,184,.12); vertical-align:middle; }
.pr-table th { text-align:left; font-size:.72rem; color:#64748b; font-weight:900; text-transform:uppercase; letter-spacing:.02em; background:rgba(148,163,184,.04); }
.pr-table td { font-size:.86rem; color:#334155; }
.pr-table tbody tr:last-child td { border-bottom:none; }
.pr-table tbody tr.has-qty td { background:rgba(22,101,52,.03); }

/* Item Layout */
.item-title { font-weight:900; color:#111827; }
.item-meta { font-size:.75rem; color:#64748b; margin-top:.15rem; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; }
.item-actions { display:flex; gap:.4rem; margin-top:.45rem; flex-wrap:wrap; }

/* Metric Pills */
.metric-pill { display:inline-flex; align-items:center; gap:.25rem; border:1px solid rgba(148,163,184,.25); border-radius:999px; padding:.1rem .45rem; font-size:.68rem; font-weight:700; color:#64748b; background:#f8fafc; }
.metric-pill.blue { color:#1d4ed8; border-color:rgba(59,130,246,.28); background:rgba(59,130,246,.07); }
.metric-pill.green { color:#166534; border-color:rgba(34,197,94,.28); background:rgba(34,197,94,.07); }
.metric-pill.red { color:#b91c1c; border-color:rgba(220,38,38,.28); background:rgba(220,38,38,.07); }

/* Qty Input */
.qty-input { width:80px; text-align:right; font-weight:900; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; }

/* Photo Upload */
.photo-upload-row { display:flex; flex-wrap:wrap; align-items:center; gap:.4rem; margin-top:.45rem; }
.photo-thumb-wrap { position:relative; }
.photo-thumb { width:32px; height:32px; object-fit:cover; border-radius:4px; border:1px solid rgba(148,163,184,.3); }
.photo-del { position:absolute; top:-5px; right:-5px; background:#b91c1c; color:#fff; border-radius:50%; width:14px; height:14px; display:flex; align-items:center; justify-content:center; font-size:9px; cursor:pointer; }
.photo-add-btn { display:inline-flex; align-items:center; gap:.25rem; font-size:.7rem; color:#334155; cursor:pointer; padding:.2rem .4rem; border-radius:4px; background:rgba(148,163,184,.1); border:1px dashed rgba(148,163,184,.4); font-weight:800; }

/* Alerts */
.pr-alert { border-radius:8px; font-size:.82rem; padding:.55rem .75rem; margin-bottom:.65rem; border:1px solid transparent; font-weight:600; }
.pr-alert-success { background:rgba(34,197,94,.08); border-color:rgba(34,197,94,.25); color:#166534; }
.pr-alert-danger { background:rgba(220,38,38,.07); border-color:rgba(220,38,38,.25); color:#b91c1c; }

/* Actions Bar */
.pr-footer { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:.55rem; padding-top:.5rem; }

@media(max-width:860px){
  .pr-wrap { padding:.5rem .5rem 3.5rem; }
  .pr-topbar { padding:.5rem; }
  .pr-code { flex:1; min-width:150px; font-size:1.02rem; }
  .pr-sub, .pr-hide-mobile { display:none; }
  .pr-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:.45rem; }
  .pr-kpi { padding:.58rem .62rem; }
  .pr-value { font-size:1.08rem; }
  .pr-head, .pr-body { padding:.65rem .7rem; }
  
  .pr-table-wrap { border:none; border-radius:0; overflow:visible; }
  .pr-table, .pr-table tbody, .pr-table tr, .pr-table td { display:block; width:100%; }
  .pr-table thead { display:none; }
  .pr-table tr { border:1px solid rgba(148,163,184,.16); border-radius:8px; margin-bottom:.45rem; padding:.55rem .6rem; background:var(--card,#fff); }
  .pr-table td { border:0; padding:0; display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:.35rem; }
  .pr-table td::before { content:attr(data-label); font-size:.7rem; font-weight:800; color:#64748b; text-transform:uppercase; margin-right:.5rem; flex-shrink:0; padding-top:2px; }
  
  .td-content { flex-grow:1; text-align:right; }
  .td-content-left { flex-grow:1; text-align:left; }
  
  .item-actions { flex-direction:column; align-items:flex-start; }
  .pr-input, .pr-select { width:100%; }
  
  .d-flex.flex-wrap { flex-direction:column; align-items:stretch; }
  .d-flex.flex-wrap > div { max-width:none!important; }
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

<div class="pr-topbar">
  <a href="{{ route('purchasing.purchase_returns.index') }}" class="pr-btn">Kembali</a>
  <span class="pr-code">{{ $ret->code }}</span>
  <span class="pr-pill pr-status {{ $statusPill }}">{{ $statusText }}</span>
  <span class="pr-spacer"></span>
  <span class="pr-pill">Qty <b id="live-return-qty">{{ rtrim(rtrim(number_format($totalQty, 4, ',', '.'), '0'), ',') }}</b></span>
  <span class="pr-pill">Item <b id="live-return-lines">{{ $totalReturnLines }}</b>/{{ $totalLines }}</span>
  <a href="{{ $grnHref }}" class="pr-btn pr-hide-mobile"><i class="bi bi-box-arrow-up-right"></i> GRN</a>
</div>

<div class="pr-wrap">
  @if(session('success')) <div class="pr-alert pr-alert-success">{{ session('success') }}</div> @endif
  @if(session('error')) <div class="pr-alert pr-alert-danger">{{ session('error') }}</div> @endif
  @if($errors->any())
    <div class="pr-alert pr-alert-danger">
      <div>Terjadi kesalahan:</div>
      <ul class="mb-0 ps-3 mt-1">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <div class="pr-grid">
    <div class="pr-card pr-kpi">
      <div class="pr-label">Total Qty</div>
      <div class="pr-value" id="kpi-qty">{{ rtrim(rtrim(number_format($totalQty, 4, ',', '.'), '0'), ',') }}</div>
    </div>
    <div class="pr-card pr-kpi">
      <div class="pr-label">Item Diretur</div>
      <div class="pr-value" id="kpi-lines">{{ $totalReturnLines }}</div>
    </div>
    <div class="pr-card pr-kpi">
      <div class="pr-label">Status Stok</div>
      <div class="pr-value" style="font-size:1rem;margin-top:.2rem;">
        @if($isDraft)
          <span style="color:{{ $stockReady ? '#166534' : '#991b1b' }}">{{ $stockReady ? 'Siap' : 'Kurang' }}</span>
        @else
          {{ $journalCount > 0 ? $journalCount . ' jurnal' : 'Belum jurnal' }}
        @endif
      </div>
    </div>
    @if($canSeeMoney)
    <div class="pr-card pr-kpi">
      <div class="pr-label">Total Nilai</div>
      <div class="pr-value">{{ rupiah($grand) }}</div>
    </div>
    @endif
  </div>

  <div class="pr-card">
    <div class="pr-head">
      <div class="pr-title">Informasi Dokumen</div>
      <span class="pr-pill">{{ $ret->resolution_type === 'replacement' ? 'Tukar Barang' : 'Refund' }}</span>
    </div>
    <div class="pr-meta">
      <div class="pr-meta-box">
        <div class="pr-label">Tanggal</div>
        <div class="pr-value" style="font-size:1rem">{{ $ret->date ? \Illuminate\Support\Carbon::parse($ret->date)->format('d M Y') : '-' }}</div>
        <div class="pr-muted">Gudang: {{ $ret->grn?->warehouse?->name ?? '-' }}</div>
      </div>
      <div class="pr-meta-box">
        <div class="pr-label">Supplier</div>
        <div class="pr-value" style="font-size:1rem">{{ $ret->grn?->supplier?->name ?? '-' }}</div>
        <div class="pr-muted pr-mono">{{ $ret->grn?->supplier?->code }}</div>
      </div>
      @if($canSeeMoney && ($isDraft || $isPosted))
      <div class="pr-meta-box">
        <div class="pr-label">Efek Return</div>
        <div class="pr-value" style="font-size:1rem;color:#b91c1c;">{{ rupiah($effectInv) }}</div>
        <div class="pr-muted">
            Potong AP: {{ $isDraft && !$isVoided ? rupiah($effectAp) : '-' }}<br>
            Klaim: {{ $isDraft && !$isVoided ? rupiah($effectClaim) : '-' }}
        </div>
      </div>
      @endif
    </div>
  </div>

  <form method="POST" action="{{ route('purchasing.purchase_returns.update', $ret->id) }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="pr-card">
      <div class="pr-head">
        <div class="pr-title">Detail Item Return</div>
        @if($isEditable)
          <button type="submit" class="pr-btn pr-primary"><i class="bi bi-save2"></i> Simpan Draft</button>
        @endif
      </div>
      <div class="pr-body">
        
        @if($isEditable)
          <div class="pr-formbar">
            <div class="pr-form-group">
              <label>Tanggal</label>
              <input type="text" name="date" class="pr-input gf-date-input pr-mono" value="{{ $dateValue }}" data-gf-date autocomplete="off" required>
            </div>
            <div class="pr-form-group">
              <label>Penyelesaian</label>
              <select name="resolution_type" class="pr-select">
                <option value="refund" {{ old('resolution_type', $ret->resolution_type) === 'refund' ? 'selected' : '' }}>Refund</option>
                <option value="replacement" {{ old('resolution_type', $ret->resolution_type) === 'replacement' ? 'selected' : '' }}>Tukar Barang</option>
              </select>
            </div>
            <div class="pr-form-group" style="grid-column: span auto;">
              <label>Catatan Umum</label>
              <input type="text" name="notes" class="pr-input" value="{{ old('notes', $ret->notes) }}" placeholder="Opsional">
            </div>
          </div>
          
          <div class="d-flex flex-wrap gap-2 mb-3 align-items-end" id="add-item-section">
            <div class="flex-grow-1" style="max-width: 400px;">
              <label class="pr-label" style="margin-bottom:.25rem;display:block;">Tambah Item Retur</label>
              <select id="item-selector" class="pr-select">
                <option value="">-- Pilih item yang ingin diretur --</option>
                @foreach($returnRows as $i => $row)
                  <option value="{{ $i }}">{{ $row->item?->name ?? '-' }} (Tersedia: {{ rtrim(rtrim(number_format((float)($row->max_return ?? $row->remaining ?? 0), 4, ',', '.'), '0'), ',') }})</option>
                @endforeach
              </select>
            </div>
            <button type="button" class="pr-btn pr-primary" id="btn-add-item"><i class="bi bi-plus"></i> Tambah</button>
          </div>
        @else
          <input type="hidden" name="date" value="{{ $dateValue }}">
          <input type="hidden" name="notes" value="{{ $ret->notes }}">
          <input type="hidden" name="resolution_type" value="{{ $ret->resolution_type }}">
        @endif

        <div class="pr-table-wrap" id="return-table-wrap">
          <table class="pr-table">
            <thead>
              <tr>
                <th>Item & Alasan</th>
                <th style="text-align:right">Tersedia</th>
                <th style="text-align:right;width:120px;">Qty Return</th>
                @if($canSeeMoney)<th style="text-align:right">Nilai</th>@endif
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
                <tr class="return-row {{ $rowClass }}" data-row-idx="{{ $i }}">
                  <td data-label="Item">
                    <div class="td-content-left">
                      <div class="item-title">{{ $row->item?->name ?? '-' }}</div>
                      <div class="item-meta">{{ $row->item?->code ?? '-' }} @if($row->lot_id) • LOT #{{ $row->lot_id }} @endif</div>
                      
                      @if($isEditable)
                        <input type="hidden" name="lines[{{ $i }}][id]" value="{{ $ln?->id }}">
                        <input type="hidden" name="lines[{{ $i }}][purchase_receipt_line_id]" value="{{ $row->purchase_receipt_line_id }}">
                        
                        <div class="item-actions">
                          <select name="lines[{{ $i }}][reason_code]" class="pr-select reason-input" style="padding:.2rem .4rem; font-size:.75rem; width:140px; flex-shrink:0;">
                            <option value="">- Pilih Alasan -</option>
                            @foreach($reasons as $code => $label)
                              <option value="{{ $code }}" @selected(old("lines.$i.reason_code", $ln?->reason_code) === $code)>{{ $label }}</option>
                            @endforeach
                          </select>
                          <input type="text" name="lines[{{ $i }}][notes]" class="pr-input notes-input" style="padding:.2rem .4rem; font-size:.75rem; min-width:150px;" placeholder="Catatan item..." value="{{ old("lines.$i.notes", $row->notes) }}">
                          <button type="button" class="pr-btn pr-btn-danger btn-remove-row" style="padding:.2rem .4rem;min-height:auto;" title="Hapus"><i class="bi bi-trash"></i></button>
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
                          <div class="pr-muted" style="margin-top:.3rem;">Alasan: <b>{{ $ln?->reason_label }}</b></div>
                        @endif
                        @if($ln && $ln->photos && $ln->photos->count())
                          <div class="photo-upload-row">
                            @foreach($ln->photos as $photo)
                              <a href="{{ $photo->url }}" target="_blank"><img src="{{ $photo->url }}" class="photo-thumb" alt="foto"></a>
                            @endforeach
                          </div>
                        @endif
                      @endif
                    </div>
                  </td>
                  
                  <td data-label="Tersedia">
                    <div class="td-content">
                      <div style="margin-bottom:.3rem;"><span class="metric-pill blue">Maks: {{ rtrim(rtrim(number_format($maxReturn, 4, ',', '.'), '0'), ',') }}</span></div>
                      @if($isInventoryLine)
                        <div><span class="metric-pill {{ $stockOk ? 'green' : 'red' }}">{{ $lotStock !== null ? 'Lot:' : 'Stok:' }} {{ rtrim(rtrim(number_format($shownStock, 4, ',', '.'), '0'), ',') }}</span></div>
                      @else
                        <div><span class="metric-pill">Non-stok</span></div>
                      @endif
                    </div>
                  </td>
                  
                  <td data-label="Qty Return">
                    <div class="td-content">
                      @if($isEditable)
                        <div style="display:flex; flex-direction:column; align-items:flex-end; gap:.25rem;">
                          <input type="number" name="lines[{{ $i }}][qty]" class="pr-input qty-input qty-return-input" value="{{ old("lines.$i.qty", $qty > 0.0001 ? $qty : '') }}" step="0.0001" min="0" max="{{ $maxReturn }}" placeholder="0">
                        </div>
                      @else
                        <div class="pr-mono" style="font-weight:900; font-size:1.1rem;">{{ rtrim(rtrim(number_format($qty, 4, ',', '.'), '0'), ',') }}</div>
                      @endif
                    </div>
                  </td>
                  
                  @if($canSeeMoney)
                    <td data-label="Nilai" class="pr-mono">
                      <div class="td-content">
                        <div style="font-weight:900; color:#111827;">{{ number_format($lineTotal, 0, ',', '.') }}</div>
                        <div class="pr-muted">@ {{ number_format($unitPrice, 0, ',', '.') }}</div>
                      </div>
                    </td>
                  @endif
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </form>

  <div class="pr-card">
    <div class="pr-body pr-footer">
      <div style="display:flex;gap:.5rem;align-items:center;">
        @if($isSubmitted) <span class="pr-pill badge-info"><i class="bi bi-hourglass"></i> Menunggu Persetujuan</span> @endif
        @if($isEditable && !$stockReady) <span class="pr-pill badge-danger"><i class="bi bi-exclamation-circle"></i> Stok tidak mencukupi</span> @endif
      </div>

      <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
        @if($isDraft && !$isVoided)
          <form method="POST" action="{{ route('purchasing.purchase_returns.submit', $ret->id) }}" style="margin:0;">
            @csrf <button type="submit" class="pr-btn"><i class="bi bi-send"></i> Ajukan Persetujuan</button>
          </form>
        @endif

        @if($isEditable)
          <form method="POST" action="{{ route('purchasing.purchase_returns.post', $ret->id) }}" class="js-post-return" style="margin:0;">
            @csrf
            <button type="submit" class="pr-btn {{ $stockReady ? 'pr-btn-success' : 'pr-btn-danger' }}" {{ $stockReady ? '' : 'disabled' }}>
              <i class="bi bi-check2-circle"></i> {{ $stockReady ? 'Post Return' : 'Stok Kurang' }}
            </button>
          </form>
        @endif

        @if($isPosted && !$isVoided)
          @if($ret->resolution_type === 'replacement' && in_array($ret->replacement_status, ['pending', 'partial']))
            <button type="button" class="pr-btn pr-primary" data-bs-toggle="modal" data-bs-target="#receiveReplacementModal">
              <i class="bi bi-box-seam"></i> Terima Pengganti
            </button>
          @endif
          @if(!$hasReceivedReplacement)
            <form method="POST" action="{{ route('purchasing.purchase_returns.void', $ret->id) }}" class="js-void-return" style="margin:0;">
              @csrf <button type="submit" class="pr-btn pr-btn-danger"><i class="bi bi-x-circle"></i> Void Return</button>
            </form>
          @else
            <div style="color:#b91c1c; font-size:.7rem; font-weight:700;">* Tidak dapat di-void</div>
          @endif
        @endif
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const itemSelector = document.getElementById('item-selector');
  const btnAddItem = document.getElementById('btn-add-item');
  const tableWrap = document.getElementById('return-table-wrap');
  const qtyInputs = Array.from(document.querySelectorAll('.qty-return-input'));
  const liveLines = document.getElementById('live-return-lines');
  const liveQty = document.getElementById('live-return-qty');
  const kpiLines = document.getElementById('kpi-lines');
  const kpiQty = document.getElementById('kpi-qty');

  function toNumber(value) {
    if (value === null || value === '') return 0;
    return Number(String(value).replace(',', '.')) || 0;
  }
  function formatQty(value) {
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(value);
  }

  function refreshTotals() {
    let count = 0;
    let totalQtyVal = 0;
    qtyInputs.forEach(function (input) {
      const qty = toNumber(input.value);
      if (qty > 0.0001) count++;
      totalQtyVal += qty;
      
      const row = input.closest('.return-row');
      if (row) row.classList.toggle('has-qty', qty > 0.0001);
    });
    const formattedQty = formatQty(totalQtyVal);
    if (liveLines) liveLines.textContent = count;
    if (liveQty) liveQty.textContent = formattedQty;
    if (kpiLines) kpiLines.textContent = count;
    if (kpiQty) kpiQty.textContent = formattedQty;
  }

  function initDynamicRows() {
    const isEditable = itemSelector !== null;
    let visibleCount = 0;
    
    document.querySelectorAll('.return-row').forEach(row => {
        const qtyInput = row.querySelector('.qty-return-input');
        const reasonInput = row.querySelector('.reason-input');
        const notesInput = row.querySelector('.notes-input');
        
        let hasData = false;
        if (qtyInput && toNumber(qtyInput.value) > 0.0001) hasData = true;
        if (reasonInput && reasonInput.value !== '') hasData = true;
        if (notesInput && notesInput.value !== '') hasData = true;
        if (row.querySelector('.photo-thumb-wrap') || row.querySelector('.photo-thumb')) hasData = true;
        
        if (isEditable) {
            const rowIdx = row.dataset.rowIdx;
            const option = itemSelector.querySelector(`option[value="${rowIdx}"]`);
            
            if (!hasData) {
                row.style.display = 'none';
                if(option) option.style.display = '';
            } else {
                row.style.display = '';
                if(option) option.style.display = 'none';
                visibleCount++;
            }
        } else {
            if (!hasData) row.style.display = 'none';
            else visibleCount++;
        }
    });
    
    if (tableWrap) tableWrap.style.display = visibleCount > 0 ? '' : 'none';
  }

  if (itemSelector) {
      btnAddItem.addEventListener('click', function() {
          const idx = itemSelector.value;
          if (!idx) return;
          
          const row = document.querySelector(`.return-row[data-row-idx="${idx}"]`);
          if (row) {
              row.style.display = '';
              itemSelector.querySelector(`option[value="${idx}"]`).style.display = 'none';
              itemSelector.value = '';
              
              if (tableWrap) tableWrap.style.display = '';
              
              setTimeout(() => {
                  const qtyInput = row.querySelector('.qty-return-input');
                  if(qtyInput) { qtyInput.focus(); qtyInput.select(); }
              }, 50);
          }
      });
      
      document.querySelectorAll('.btn-remove-row').forEach(btn => {
          btn.addEventListener('click', function() {
              const row = this.closest('.return-row');
              const idx = row.dataset.rowIdx;
              
              const qtyInput = row.querySelector('.qty-return-input');
              const reasonInput = row.querySelector('.reason-input');
              const notesInput = row.querySelector('.notes-input');
              
              if (qtyInput) qtyInput.value = '';
              if (reasonInput) reasonInput.value = '';
              if (notesInput) notesInput.value = '';
              
              const newPhotos = row.querySelectorAll('input[type="file"]');
              newPhotos.forEach(input => input.value = '');
              
              row.style.display = 'none';
              const option = itemSelector.querySelector(`option[value="${idx}"]`);
              if (option) option.style.display = '';
              
              refreshTotals();
              
              const anyVisible = Array.from(document.querySelectorAll('.return-row')).some(r => r.style.display !== 'none');
              if (!anyVisible && tableWrap) tableWrap.style.display = 'none';
          });
      });
  }
  
  qtyInputs.forEach(function (input) {
    input.addEventListener('focus', function () { setTimeout(function () { input.select(); }, 0); });
    input.addEventListener('input', refreshTotals);
  });

  initDynamicRows();
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
    confirmSubmit(form, { icon: 'question', title: 'Posting return?', text: 'Draft akan resmi: stok keluar dan jurnal tercatat.', confirmText: 'Ya, Posting', color: '#16a34a' });
  });

  document.querySelectorAll('.js-void-return').forEach(function (form) {
    confirmSubmit(form, { icon: 'warning', title: 'Void return?', text: 'Stok akan dikembalikan dan jurnal dibatalkan.', confirmText: 'Ya, Void', color: '#dc2626' });
  });
});
</script>
@endpush
"""

with open('/Users/ariefmuhamad/Herd/gfid-dev/resources/views/purchasing/purchase_returns/show.blade.php', 'w') as f:
    f.write(content)

