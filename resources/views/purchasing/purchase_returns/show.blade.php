{{-- resources/views/purchasing/purchase_returns/show.blade.php --}}
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
.pr-card { background:var(--card,#fff); border:1px solid rgba(148,163,184,.2); border-radius:10px; overflow:hidden; margin-bottom:.65rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03), 0 2px 4px -1px rgba(0,0,0,0.02); transition: box-shadow 0.2s ease; }
.pr-card:hover { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -2px rgba(0,0,0,0.03); }
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
.pr-form-group label { font-size:.72rem; font-weight:800; color:#475569; text-transform:uppercase; letter-spacing:.02em; }
.pr-input, .pr-select { border:1px solid #64748b; border-radius:7px; padding:.45rem .6rem; font-size:.86rem; outline:none; transition:all .2s; background:#ffffff; color:#0f172a; width:100%; box-shadow: inset 0 1px 2px rgba(0,0,0,0.04); font-weight: 500; }
.pr-input::placeholder, .pr-select::placeholder { color:#94a3b8; font-weight: 400; }
.pr-input:focus, .pr-select:focus { border-color:#4f46e5; background:#fff; box-shadow:inset 0 1px 2px rgba(0,0,0,0.02), 0 0 0 3px rgba(79,70,229,0.2); }

/* Table */
.pr-table-wrap { overflow:auto; border:1px solid rgba(148,163,184,.16); border-radius:8px; }
.pr-table { width:100%; border-collapse:collapse; }
.pr-table th, .pr-table td { padding:.4rem .5rem; border-bottom:1px solid rgba(148,163,184,.12); vertical-align:top; }
.pr-table th { text-align:left; font-size:.72rem; color:#64748b; font-weight:900; text-transform:uppercase; letter-spacing:.02em; background:rgba(148,163,184,.04); }
.pr-table td { font-size:.82rem; color:#334155; }
.pr-table tbody tr:last-child td { border-bottom:none; }
.pr-table tbody tr.has-qty td { background:rgba(22,101,52,.03); }

/* Segmented Radio Buttons */
.pr-segmented { display: inline-flex; border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; background: #f1f5f9; padding: 3px; gap: 3px; width: fit-content; }
.pr-segmented input[type="radio"] { display: none; }
.pr-segmented label { padding: 0.35rem 1rem; font-size: 0.8rem; font-weight: 700; color: #64748b; cursor: pointer; border-radius: 6px; margin: 0; transition: all 0.2s; white-space: nowrap; display: flex; align-items: center; }
.pr-segmented input[type="radio"]:checked + label { background: #fff; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

/* Item Layout */
.item-title { font-weight:900; color:#111827; font-size:.85rem; }
.item-meta { font-size:.72rem; color:#64748b; margin-top:.1rem; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; }
.item-actions { display:flex; gap:.3rem; margin-top:.3rem; flex-wrap:wrap; }

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
        <div class="pr-value" id="effect-total" style="font-size:1rem;color:#b91c1c;">{{ rupiah($effectInv) }}</div>
        <div class="pr-muted">
            Potong AP: <span id="effect-ap">{{ $isDraft && !$isVoided ? rupiah($effectAp) : '-' }}</span><br>
            Klaim: <span id="effect-claim">{{ $isDraft && !$isVoided ? rupiah($effectClaim) : '-' }}</span>
        </div>
      </div>
      @endif
    </div>
  </div>

  <form id="main-return-form" method="POST" action="{{ route('purchasing.purchase_returns.update', $ret->id) }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="pr-card">
      <div class="pr-head">
        <div class="pr-title">Item Retur</div>
      </div>
      <div class="pr-body">
        
        @if($isEditable)
          <div class="pr-formbar">
            <div class="pr-form-group">
              <label>Tanggal</label>
              <input type="text" name="date" class="pr-input gf-date-input pr-mono" value="{{ $dateValue }}" data-gf-date autocomplete="off" required>
            </div>
            <div class="pr-form-group" style="grid-column: span auto;">
              <label>Catatan</label>
              <input type="text" name="notes" class="pr-input" value="{{ old('notes', $ret->notes) }}" placeholder="Opsional">
            </div>
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
                <th style="width: 30px; text-align: center;">#</th>
                <th>Item</th>
                <th style="text-align:right">Maks</th>
                <th style="text-align:right;width:120px;">Qty</th>
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
                  <td data-label="#" style="text-align: center; font-weight: 700; color: #94a3b8;">{{ $loop->iteration }}</td>
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
                          <input type="text" name="lines[{{ $i }}][qty]" class="pr-input qty-input qty-return-input pr-mono gf-decimal text-end" value="{{ old("lines.$i.qty", $qty > 0.0001 ? rtrim(rtrim(number_format($qty, 4, ',', '.'), '0'), ',') : '') }}" placeholder="0,00" autocomplete="off" style="width:100px;" data-price="{{ $unitPrice }}" data-is-inv="{{ $isInventoryLine ? '1' : '0' }}" data-max="{{ $row->max_return ?? $row->remaining ?? 0 }}">
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
    
    @if($isEditable)
    <div class="modal fade" id="resolutionModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
          <div class="modal-header" style="background: rgba(248, 250, 252, 0.8); border-bottom: 1px solid rgba(148, 163, 184, 0.15); backdrop-filter: blur(8px);">
            <h5 class="modal-title" id="resolutionModalTitle" style="font-weight: 800; color: #1e293b;">Konfirmasi Item</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" style="background: #ffffff; padding: 1.5rem;">
            
            <!-- STEP 1 -->
            <div id="modal-step-1">
              <h6 style="margin-bottom:1rem; color:#1e293b; font-weight:700;">Item yang akan diretur:</h6>
              <div id="modal-item-summary" style="max-height: 250px; overflow-y: auto; text-align:left; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0.75rem; font-size: 0.85rem; margin-bottom: 0.5rem;">
              </div>
            </div>

            <!-- STEP 2 -->
            <div id="modal-step-2" style="display:none; text-align:center; padding: 1rem 0;">
              <h6 style="margin-bottom:1.5rem; color:#64748b; font-weight:600;">Pilih bentuk penyelesaian untuk retur ini:</h6>
              <div class="pr-segmented" style="transform: scale(1.15);">
                 <input type="radio" id="res_refund" name="resolution_type" value="refund" {{ old('resolution_type', $ret->resolution_type) === 'refund' ? 'checked' : '' }}>
                 <label for="res_refund"><i class="bi bi-cash me-1"></i> Refund</label>
                 <input type="radio" id="res_replace" name="resolution_type" value="replacement" {{ old('resolution_type', $ret->resolution_type) === 'replacement' ? 'checked' : '' }}>
                 <label for="res_replace"><i class="bi bi-box-seam me-1"></i> Tukar Barang</label>
              </div>
            </div>
            
          </div>
          <div class="modal-footer" style="background: rgba(248, 250, 252, 0.5); border-top: 1px solid rgba(148, 163, 184, 0.1);">
            <button type="button" class="pr-btn" id="btn-modal-back" style="display:none;"><i class="bi bi-arrow-left"></i> Kembali</button>
            <button type="button" class="pr-btn" data-bs-dismiss="modal" id="btn-modal-cancel">Batal</button>
            <button type="button" class="pr-btn pr-primary" id="btn-modal-next">Lanjut Penyelesaian <i class="bi bi-arrow-right"></i></button>
            <button type="submit" name="action_btn" value="post" class="pr-btn pr-btn-success" id="btn-modal-post" style="display:none;"><i class="bi bi-check2-circle"></i> Posting Return</button>
          </div>
        </div>
      </div>
    </div>
    @endif
  </form>

  <div class="pr-card">
    <div class="pr-body pr-footer">
      <div style="display:flex;gap:.5rem;align-items:center;">
        @if($isSubmitted) <span class="pr-pill badge-info"><i class="bi bi-hourglass"></i> Menunggu Persetujuan</span> @endif
        @if($isEditable && !$stockReady) <span class="pr-pill badge-danger"><i class="bi bi-exclamation-circle"></i> Stok tidak mencukupi</span> @endif
      </div>

      <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
        @if($isDraft && !$isVoided)
          <button type="submit" name="action_btn" value="submit" form="main-return-form" class="pr-btn"><i class="bi bi-send"></i> Ajukan</button>
        @endif

        @if($isEditable)
          <button type="button" class="pr-btn pr-btn-success" id="btn-pre-post" data-bs-toggle="modal" data-bs-target="#resolutionModal">
            <i class="bi bi-save2"></i> Simpan
          </button>
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

{{-- MODAL: Terima Pengganti (Replacement) --}}
@if($ret->resolution_type === 'replacement' && in_array($ret->replacement_status, ['pending', 'partial']))
<div class="modal fade" id="receiveReplacementModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form action="{{ route('purchasing.purchase_returns.receive_replacement', $ret->id) }}" method="POST" class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
      @csrf
      <div class="modal-header" style="background: rgba(248, 250, 252, 0.8); border-bottom: 1px solid rgba(148, 163, 184, 0.15); backdrop-filter: blur(8px);">
        <h5 class="modal-title" style="font-weight: 800; color: #1e293b;">Terima Barang Pengganti</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="background: #ffffff;">
        <div class="pr-alert pr-alert-info" style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 0.5rem 0.75rem; font-size: 0.85rem;">
            <i class="bi bi-info-circle me-1"></i> Masukkan Qty pengganti. GRN akan dibuat dan langsung diposting agar stok serta status replacement otomatis diperbarui.
        </div>
        
        <div class="pr-formbar mb-4">
          <div class="pr-form-group">
            <label>Tanggal Diterima</label>
            <input type="text" name="received_at" class="pr-input gf-date-input" value="{{ now()->toDateString() }}" required>
          </div>
          <div class="pr-form-group">
            <label>Gudang Tujuan</label>
            <select name="warehouse_id" class="pr-select" required>
              <option value="">-- Pilih Gudang --</option>
              @foreach(\App\Models\Warehouse::all() as $wh)
                <option value="{{ $wh->id }}" {{ $wh->id == ($ret->grn->warehouse_id ?? '') ? 'selected' : '' }}>{{ $wh->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="pr-formbar mb-4">
          <div class="pr-form-group">
            <label>No. Surat Jalan (Opsional)</label>
            <input type="text" name="document_number" class="pr-input" placeholder="SJ-SUPP-123">
          </div>
          <div class="pr-form-group">
            <label>Catatan (Opsional)</label>
            <input type="text" name="notes" class="pr-input" placeholder="Catatan penerimaan...">
          </div>
        </div>

        <label style="font-size: .72rem; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: .5rem; display: block;">Detail Barang</label>
        <div class="pr-table-wrap">
          <table class="pr-table">
            <thead>
              <tr>
                <th>Item</th>
                <th style="width:120px;text-align:right">Expected</th>
                <th style="width:120px;text-align:right">Diterima</th>
                <th style="width:150px;text-align:right">Terima Sekarang</th>
              </tr>
            </thead>
            <tbody>
              @foreach($returnRows as $i => $row)
                @if($row->qty > 0 && $row->line)
                  @php
                    $expected = (float) $row->line->replacement_qty_expected;
                    $received = (float) $row->line->replacement_qty_received;
                    $outstanding = max(0, $expected - $received);
                  @endphp
                  <tr>
                    <td data-label="Item">
                      <div class="td-content-left">
                        <div class="item-title">{{ $row->item->name }}</div>
                        <div class="item-meta">{{ $row->item->code }}</div>
                        <input type="hidden" name="lines[{{ $i }}][id]" value="{{ $row->line->id }}">
                      </div>
                    </td>
                    <td data-label="Expected" class="pr-mono" style="text-align:right">
                      {{ rtrim(rtrim(number_format($expected, 4, ',', '.'), '0'), ',') }}
                    </td>
                    <td data-label="Diterima" class="pr-mono" style="text-align:right">
                      {{ rtrim(rtrim(number_format($received, 4, ',', '.'), '0'), ',') }}
                    </td>
                    <td data-label="Terima Sekarang" style="text-align:right">
                      <input type="text" name="lines[{{ $i }}][qty]" class="pr-input pr-mono" value="{{ $outstanding > 0 ? $outstanding : 0 }}" {{ $outstanding <= 0 ? 'readonly' : '' }} style="text-align:right" data-gf-decimal>
                    </td>
                  </tr>
                @endif
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer" style="background: rgba(248, 250, 252, 0.5); border-top: 1px solid rgba(148, 163, 184, 0.1);">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border: 1px solid #cbd5e1; font-weight: 600;">Batal</button>
        <button type="submit" class="btn btn-primary" style="background: #334155; border-color: #334155; font-weight: 700; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">Simpan Penerimaan</button>
      </div>
    </form>
  </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const qtyInputs = Array.from(document.querySelectorAll('.qty-return-input'));
  const resolutionRadios = document.querySelectorAll('input[name="resolution_type"]');
  const effectTotal = document.getElementById('effect-total');
  const effectAp = document.getElementById('effect-ap');
  const effectClaim = document.getElementById('effect-claim');
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
    let invTotal = 0;
    let expTotal = 0;
    let anyError = false;
    
    qtyInputs.forEach(function (input) {
      const qty = toNumber(input.value);
      const price = Number(input.dataset.price) || 0;
      const max = Number(input.dataset.max) || 0;
      const isInv = input.dataset.isInv === '1';
      const val = qty * price;
      
      if (qty > max + 0.0001) {
          input.style.borderColor = '#dc2626';
          input.style.backgroundColor = '#fef2f2';
          input.style.color = '#dc2626';
          anyError = true;
      } else {
          input.style.borderColor = '';
          input.style.backgroundColor = '';
          input.style.color = '';
      }
      
      if (qty > 0.0001) count++;
      totalQtyVal += qty;
      if (isInv) invTotal += val; else expTotal += val;
      
      const row = input.closest('.return-row');
      if (row) row.classList.toggle('has-qty', qty > 0.0001);
    });
    
    const formattedQty = formatQty(totalQtyVal);
    if (liveLines) liveLines.textContent = count;
    if (liveQty) liveQty.textContent = formattedQty;
    if (kpiLines) kpiLines.textContent = count;
    if (kpiQty) kpiQty.textContent = formattedQty;
    
    if (effectTotal) {
      const total = invTotal + expTotal;
      let isRefund = true;
      resolutionRadios.forEach(r => { if(r.checked && r.value === 'replacement') isRefund = false; });
      
      effectTotal.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
      if (isRefund) {
        if(effectAp) effectAp.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        if(effectClaim) effectClaim.textContent = 'Rp 0';
      } else {
        if(effectAp) effectAp.textContent = 'Rp 0';
        if(effectClaim) effectClaim.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
      }
    }
    
    const btnPrePost = document.getElementById('btn-pre-post');
    if (btnPrePost) {
        if (anyError) {
            btnPrePost.disabled = true;
            btnPrePost.innerHTML = '<i class="bi bi-exclamation-circle"></i> Qty Melebihi Maks';
            btnPrePost.classList.remove('pr-btn-success');
            btnPrePost.classList.add('pr-btn-danger');
        } else {
            btnPrePost.disabled = false;
            btnPrePost.innerHTML = '<i class="bi bi-save2"></i> Simpan';
            btnPrePost.classList.remove('pr-btn-danger');
            btnPrePost.classList.add('pr-btn-success');
        }
    }
  }

  resolutionRadios.forEach(r => r.addEventListener('change', refreshTotals));

  const resolutionModalEl = document.getElementById('resolutionModal');
  if (resolutionModalEl) {
    const step1 = document.getElementById('modal-step-1');
    const step2 = document.getElementById('modal-step-2');
    const btnCancel = document.getElementById('btn-modal-cancel');
    const btnBack = document.getElementById('btn-modal-back');
    const btnNext = document.getElementById('btn-modal-next');
    const btnPost = document.getElementById('btn-modal-post');
    const summaryDiv = document.getElementById('modal-item-summary');
    const modalTitle = document.getElementById('resolutionModalTitle');
    
    resolutionModalEl.addEventListener('show.bs.modal', function () {
      step1.style.display = 'block';
      step2.style.display = 'none';
      btnCancel.style.display = 'inline-flex';
      btnBack.style.display = 'none';
      btnNext.style.display = 'inline-flex';
      btnPost.style.display = 'none';
      if(modalTitle) modalTitle.textContent = "Konfirmasi Item";
      
      summaryDiv.innerHTML = '';
      let hasItems = false;
      document.querySelectorAll('.return-row').forEach(row => {
          const qtyInput = row.querySelector('.qty-return-input');
          if (!qtyInput) return;
          const qty = toNumber(qtyInput.value);
          if (qty > 0.0001) {
              hasItems = true;
              const name = row.querySelector('.item-title')?.textContent || '-';
              const reasonSelect = row.querySelector('.reason-input');
              const reason = reasonSelect?.options[reasonSelect.selectedIndex]?.text || '-';
              
              const itemDiv = document.createElement('div');
              itemDiv.style.cssText = 'display:flex; justify-content:space-between; margin-bottom: 0.6rem; border-bottom: 1px dashed #cbd5e1; padding-bottom: 0.6rem;';
              itemDiv.innerHTML = `
                  <div>
                    <div style="font-weight:700; color:#0f172a;">${name}</div>
                    <div style="font-size:0.75rem; color:#64748b;">Alasan: ${reason}</div>
                  </div>
                  <div style="font-weight:800; font-size:1rem; font-family:monospace; color:#1d4ed8; white-space:nowrap; padding-left:10px;">${formatQty(qty)}</div>
              `;
              summaryDiv.appendChild(itemDiv);
          }
      });
      
      if (!hasItems) {
          summaryDiv.innerHTML = '<div style="color:#b91c1c; font-weight:600; text-align:center; padding:1rem;">Tidak ada item yang di-retur (Qty = 0)</div>';
          btnNext.disabled = true;
          btnNext.style.opacity = '0.5';
      } else {
          btnNext.disabled = false;
          btnNext.style.opacity = '1';
      }
    });

    btnNext.addEventListener('click', function() {
      step1.style.display = 'none';
      step2.style.display = 'block';
      btnCancel.style.display = 'none';
      btnBack.style.display = 'inline-flex';
      btnNext.style.display = 'none';
      btnPost.style.display = 'inline-flex';
      if(modalTitle) modalTitle.textContent = "Penyelesaian Retur";
    });

    btnBack.addEventListener('click', function() {
      step1.style.display = 'block';
      step2.style.display = 'none';
      btnCancel.style.display = 'inline-flex';
      btnBack.style.display = 'none';
      btnNext.style.display = 'inline-flex';
      btnPost.style.display = 'none';
      if(modalTitle) modalTitle.textContent = "Konfirmasi Item";
    });
  }

  qtyInputs.forEach(function (input) {
    input.addEventListener('focus', function () { setTimeout(function () { input.select(); }, 0); });
    input.addEventListener('input', refreshTotals);
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

  document.querySelectorAll('.js-confirm-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      if (btn.disabled) return;
      if (!window.Swal) return;
      
      Swal.fire({
        icon: 'question',
        title: btn.dataset.confirmTitle,
        text: btn.dataset.confirmText,
        showCancelButton: true,
        confirmButtonColor: btn.dataset.confirmColor,
        confirmButtonText: btn.dataset.confirmBtn
      }).then((result) => {
        if (result.isConfirmed) {
          const form = document.getElementById(btn.dataset.form);
          const hiddenAction = document.createElement('input');
          hiddenAction.type = 'hidden';
          hiddenAction.name = 'action_btn';
          hiddenAction.value = btn.dataset.actionVal;
          form.appendChild(hiddenAction);
          form.submit();
        }
      });
    });
  });

  document.querySelectorAll('.js-void-return').forEach(function (form) {
    confirmSubmit(form, { icon: 'warning', title: 'Void return?', text: 'Lanjutkan void?', confirmText: 'Ya, Void', color: '#dc2626' });
  });
});
</script>
@endpush
