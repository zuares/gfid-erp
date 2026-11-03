{{-- resources/views/production/sewing_returns/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Return ' . $return->code)

@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
@endphp

@push('head')
<style>
  .page-wrap{ max-width:1000px; margin-inline:auto; padding:.8rem .8rem 3.5rem; }
  body[data-theme="light"] .page-wrap{
    background: radial-gradient(circle at top left,
      rgba(16,185,129,.18) 0,
      rgba(110,231,183,.10) 22%,
      #f9fafb 58%);
  }
  .card{
    background: var(--card);
    border-radius: 16px;
    border: 1px solid rgba(148,163,184,.22);
    box-shadow: 0 10px 26px rgba(15,23,42,.08), 0 0 0 1px rgba(15,23,42,.03);
  }
  .card-section{ padding:.9rem 1rem; }
  @media(min-width:768px){
    .page-wrap{ padding:1.1rem 1rem 3.5rem; }
    .card-section{ padding:1rem 1.25rem; }
  }
  .mono{ font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"; }

  .badge-status{
    font-size:.7rem; text-transform:uppercase; letter-spacing:.12em;
    border-radius:999px; padding:.16rem .7rem; font-weight:800;
  }
  .badge-posted{ background: rgba(22,163,74,.14); color:#166534; border:1px solid rgba(22,163,74,.45); }
  .badge-draft{ background: rgba(148,163,184,.18); color:#4b5563; border:1px solid rgba(148,163,184,.6); }
  .badge-reject{ background: rgba(248,113,113,.16); color:#b91c1c; border:1px solid rgba(248,113,113,.6); }
  .badge-void{ background: rgba(107,114,128,.16); color:#374151; border:1px solid rgba(107,114,128,.55); }

  .pill{ border-radius:999px; padding:.12rem .6rem; font-size:.72rem; font-weight:800; }
  .pill-ok{ background: rgba(22,163,74,.12); color:#166534; border:1px solid rgba(22,163,74,.4); }
  .pill-rj{ background: rgba(248,113,113,.14); color:#b91c1c; border:1px solid rgba(248,113,113,.5); }
  .pill-total{ background: rgba(59,130,246,.08); color:#1d4ed8; border:1px solid rgba(59,130,246,.4); }

  .section-title{ font-size:.9rem; font-weight:900; margin-bottom:.15rem; }
  .section-sub{ font-size:.75rem; color: var(--muted); }

  .summary-grid{
    display:grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap:.45rem .9rem;
    font-size:.8rem;
  }
  @media(min-width:768px){
    .summary-grid{ grid-template-columns: repeat(6, minmax(0,1fr)); }
  }
  .summary-label{ font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color: var(--muted); }
  .summary-value{ font-weight:900; }
  .summary-ok{ color:#166534; }
  .summary-rj{ color:#b91c1c; }
  .summary-warn{ color:#b45309; }

  .table thead th{
    font-size:.74rem; text-transform:uppercase; letter-spacing:.08em;
    color: var(--muted); border-top:none;
  }
  .table tbody td{ font-size:.8rem; }
  .row-ok{ background: rgba(240,253,244,.96); }
  .row-rj{ background: rgba(254,242,242,.96); }

  .financial-section{ border-radius:16px; overflow:hidden; border:1px solid rgba(148,163,184,.25); }
  .financial-section summary{ list-style:none; cursor:pointer; padding:.85rem 1rem; }
  .financial-section summary::-webkit-details-marker{ display:none; }
  .financial-section summary:focus-visible{ outline:2px solid currentColor; outline-offset:-2px; }
  .financial-section summary .chevron{ transition:transform .18s ease; }
  .financial-section[open] summary .chevron{ transform:rotate(180deg); }
  .financial-section .financial-body{ padding:.85rem 1rem 1rem; border-top:1px solid rgba(148,163,184,.18); }
  .financial-section .financial-heading{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
  .financial-section .financial-title{ font-size:.9rem; font-weight:900; }
  .financial-section .financial-subtitle{ font-size:.75rem; color:var(--muted); margin-top:.1rem; }
  .financial-section .financial-summary{ display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.55rem; margin-bottom:.8rem; }
  @media(min-width:768px){ .financial-section .financial-summary{ grid-template-columns:repeat(4,minmax(0,1fr)); } }
  .financial-section .financial-kpi{ padding:.6rem .7rem; border-radius:12px; background:rgba(255,255,255,.62); border:1px solid rgba(148,163,184,.18); }
  .financial-section .financial-label{ color:var(--muted); font-size:.68rem; text-transform:uppercase; letter-spacing:.08em; }
  .financial-section .financial-value{ font-weight:900; font-size:.88rem; margin-top:.15rem; }
  .financial-section .financial-table{ margin-bottom:0; }
  .financial-section .financial-table th{ font-size:.68rem; text-transform:uppercase; letter-spacing:.07em; color:var(--muted); border-top:0; white-space:nowrap; }
  .financial-section .financial-table td{ font-size:.78rem; vertical-align:middle; }
  .financial-section .financial-note{ color:var(--muted); font-size:.75rem; margin-top:.7rem; }
  .financial-section.accounting-section{ color:#0f766e; background:linear-gradient(180deg,rgba(240,253,250,.9),rgba(255,255,255,.85)); }
  .financial-section.hpp-section{ color:#1d4ed8; background:linear-gradient(180deg,rgba(239,246,255,.9),rgba(255,255,255,.85)); }
  .financial-section.mutation-section{ color:#475569; background:linear-gradient(180deg,rgba(248,250,252,.95),rgba(255,255,255,.85)); }
  .financial-section.accounting-section .financial-body,
  .financial-section.hpp-section .financial-body,
  .financial-section.mutation-section .financial-body{ color:var(--text,#0f172a); }
  .financial-section .badge-mini{ border:1px solid currentColor; border-radius:999px; padding:.14rem .55rem; font-size:.66rem; font-weight:800; white-space:nowrap; }
  .financial-section .row-reversal{ opacity:.58; }
  @media(prefers-color-scheme:dark){
    .financial-section .financial-kpi{ background:rgba(15,23,42,.22); }
    .financial-section.accounting-section{ background:linear-gradient(180deg,rgba(19,78,74,.25),rgba(15,23,42,.25)); }
    .financial-section.hpp-section{ background:linear-gradient(180deg,rgba(30,64,175,.2),rgba(15,23,42,.25)); }
    .financial-section.mutation-section{ background:linear-gradient(180deg,rgba(51,65,85,.22),rgba(15,23,42,.25)); }
  }

  .detail-bundle-wrap{ overflow-x:auto; }
  .detail-bundle-table{ min-width:900px; table-layout:fixed; }
  .detail-bundle-table .detail-item-col{ width:150px; }
  .detail-bundle-table .detail-bundle-col{ width:170px; }
  .detail-bundle-table .detail-compact-col{ width:155px; }
  .detail-bundle-table .detail-note-col{ width:150px; }
  .detail-bundle-table .detail-item-cell,
  .detail-bundle-table .detail-bundle-cell,
  .detail-bundle-table .detail-completeness-cell,
  .detail-bundle-table .detail-note-cell{ min-width:0; overflow:hidden; }
  .detail-bundle-table .detail-code{ display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .detail-bundle-table .detail-name,
  .detail-bundle-table .detail-completeness{ display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp:2; overflow:hidden; line-height:1.25; }
  .detail-bundle-table .detail-bundle-code{ display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .detail-bundle-table .detail-supply-line{ display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  @media(max-width:767.98px){
    .detail-bundle-table{ min-width:820px; }
    .detail-bundle-table .detail-item-col{ width:130px; }
    .detail-bundle-table .detail-bundle-col{ width:145px; }
    .detail-bundle-table .detail-compact-col{ width:125px; }
    .detail-bundle-table .detail-note-col{ width:125px; }
  }

  .header-actions{
    display:flex; gap:.45rem; flex-wrap:wrap; justify-content:flex-end;
  }
  .btn-pill{
    border-radius:999px; padding:.25rem .75rem; font-size:.78rem;
    display:inline-flex; align-items:center; gap:.35rem;
  }

  /* modal polish */
  .modal .modal-content{
    border-radius: 18px;
    border: 1px solid rgba(148,163,184,.22);
    box-shadow: 0 18px 40px rgba(15,23,42,.14);
  }
  .modal .modal-header{
    border-bottom: 1px solid rgba(148,163,184,.18);
  }
  .modal .modal-footer{
    border-top: 1px solid rgba(148,163,184,.18);
  }
</style>
@endpush

@section('content')
@php
  use Illuminate\Support\Carbon;
  use Illuminate\Support\Facades\Route;
  $user = auth()->user();
  $role = strtolower((string)($user->role ?? ''));

  // Developer flag mengikuti capability owner melalui User::isOwner().
  // Ini harus sama dengan guard backend agar tombol VOID tidak tersembunyi
  // ketika backend memang mengizinkan aksi tersebut.
  $isOwner = $user?->isOwner() ?? false;

  $lines = $return->lines ?? collect();
  $hasReject = ((float)($totalReject ?? 0)) > 0.000001;
  $status = $return->status ?? 'draft';

  // route guard (biar aman kalau route void belum dibuat)
  $hasVoidRoute = Route::has('production.sewing.returns.void');

  $isVoided = !is_null($return->voided_at);

  $lines = $return->lines ?? collect();
  $hasReject = ((float)($totalReject ?? 0)) > 0.000001;
  $status = $return->status ?? 'draft';

  // route guard (biar aman kalau route void belum dibuat)
  $hasVoidRoute = Route::has('production.sewing.returns.void');

  $isVoided = !is_null($return->voided_at);

  try {
    $dateLabel = $return->date
      ? (function_exists('id_day') ? id_day($return->date) : Carbon::parse($return->date)->format('d/m/Y'))
      : '-';
  } catch (\Throwable $e) {
    $dateLabel = optional($return->date)->format('d/m/Y') ?? '-';
  }

  try {
    $pickupDate = $return->pickup?->date
      ? (function_exists('id_day') ? id_day($return->pickup->date) : Carbon::parse($return->pickup->date)->format('d/m/Y'))
      : '-';
  } catch (\Throwable $e) {
    $pickupDate = optional($return->pickup?->date)->format('d/m/Y') ?? '-';
  }

  $voidedAtLabel = '-';
  try {
    $voidedAtLabel = $return->voided_at ? Carbon::parse($return->voided_at)->format('d/m/Y H:i') : '-';
  } catch (\Throwable $e) {}

  // ✅ total direct pickup unik per pickup line (lebih akurat daripada sum per line return)
  $totalDirectPickView = $lines
    ->pluck('sewingPickupLine')
    ->filter()
    ->unique('id')
    ->sum(fn($pl) => (float)($pl->qty_direct_picked ?? 0));

  /**
   * Ringkasan per item:
   * - Setor = sum(qty_ok)
   * - Reject = sum(qty_reject)
   * - Dadakan = sum(qty_direct_picked) (unik per pickup line)
   * - Belum Setor = qty_bundle - (returned_ok + returned_reject + direct_pick + progress_adjusted)
   */
  $perItem = $lines
    ->groupBy(function ($l) {
      return (int)($l->item_id ?? (optional(optional($l->sewingPickupLine)->bundle)->finished_item_id ?? 0));
    })
    ->map(function ($group) {
      $first = $group->first();
      $bundle = optional($first->sewingPickupLine)->bundle;
      $item = optional($bundle)->finishedItem;

      $setor = (float)$group->sum('qty_ok');
      $rj = (float)$group->sum('qty_reject');

      $pickupLines = $group->pluck('sewingPickupLine')->filter()->unique('id');

      $directPick = $pickupLines->sum(fn($pl) => (float)($pl->qty_direct_picked ?? 0));

      $remaining = $pickupLines->sum(function ($pl) {
        $qtyBundle = (float)($pl->qty_bundle ?? 0);
        $returnedOk = (float)($pl->qty_returned_ok ?? 0);
        $returnedRej = (float)($pl->qty_returned_reject ?? 0);
        $directPick = (float)($pl->qty_direct_picked ?? 0);
        $progressAdj = (float)($pl->qty_progress_adjusted ?? 0);
        return max($qtyBundle - ($returnedOk + $returnedRej + $directPick + $progressAdj), 0);
      });

      return [
        'code' => $item?->code ?? '-',
        'name' => $item?->name ?? '',
        'setor' => $setor,
        'rj' => $rj,
        'direct_pick' => $directPick,
        'remaining' => $remaining,
      ];
    })
    ->sortBy('code')
    ->values();

  $sourceWarehouseCode = strtoupper((string) ($return->warehouse?->code ?? ''));
  $destinationWarehouseCode = strtoupper((string) ($return->destinationWarehouse?->code ?? ''));
  $sourceAccountCode = $sourceWarehouseCode === 'REJ-SEW' ? '1-153' : '1-151-02';
  $sourceAccountLabel = $sourceWarehouseCode === 'REJ-SEW' ? 'Persediaan reject jahit' : 'WIP Sewing';
  $destinationAccountCode = $destinationWarehouseCode === 'WIP-FIN' ? '1-151-03' : '1-152';
  $destinationAccountLabel = $destinationWarehouseCode === 'WIP-FIN' ? 'WIP Finishing' : 'Persediaan Barang Jadi';
  $accountingHasActivePosting = ($accountingActiveEntries ?? collect())->isNotEmpty();
  $accountingIsBalanced = abs((float) ($accountingPostedTotalDebit ?? 0) - (float) ($accountingPostedTotalCredit ?? 0)) < 0.000001;
  $accountingStatusLabel = $accountingHasActivePosting
    ? ($accountingIsBalanced ? 'BALANCE' : 'TIDAK BALANCE')
    : (($accountingJournalEntries ?? collect())->isNotEmpty() ? 'VOID' : 'BELUM POSTING');
  $mutationStatusLabel = ($mutationRows ?? collect())->isNotEmpty() ? 'TERCATAT' : 'BELUM ADA MUTASI';

  $accountingSourceLabel = function ($sourceType): string {
    return match (true) {
      str_contains((string) $sourceType, 'reversal') => 'Reversal',
      str_contains((string) $sourceType, 'repost') => 'Repost',
      (string) $sourceType === 'sewing_return_reject' => 'Sewing Return Reject',
      (string) $sourceType === 'sewing_reject_rework_ok' => 'Sewing Rework OK',
      default => 'Sewing Return OK',
    };
  };

  $mutationSourceLabel = function ($sourceType): string {
    return match ((string) $sourceType) {
      'sewing_return_reject' => 'Return Reject',
      'sewing_reject_rework_ok' => 'Rework OK',
      'sewing_qc_in' => 'QC Sewing Masuk',
      'sewing_qc_reject' => 'QC Sewing Reject',
      'sewing_return_void_ok' => 'Reversal Return OK',
      'sewing_return_void_reject' => 'Reversal Return Reject',
      'sewing_reject_rework_void' => 'Reversal Rework',
      default => 'Return OK',
    };
  };
@endphp

<div class="page-wrap">

  {{-- FLASH --}}
  @if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger py-2">{{ session('error') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">
      <div class="fw-bold mb-1">Terjadi error:</div>
      <ul class="mb-0">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- HEADER --}}
  <div class="card mb-2">
    <div class="card-section d-flex justify-content-between flex-wrap gap-2">
      <div>
        <div class="fw-bold mb-1">
          Sewing Return <span class="mono">{{ $return->code }}</span>
        </div>

        <div class="small text-muted">
          <span class="mono">{{ $dateLabel }}</span>
          @if($return->warehouse)
            • Gudang: <span class="mono">{{ $return->warehouse->code }}</span>
          @endif
        </div>

        <div class="small text-muted mt-1">
          Pickup: <span class="mono">{{ $return->pickup?->code ?? '-' }}</span> • {{ $pickupDate }}
        </div>

        <div class="small text-muted mt-1">
          Operator: {{ $return->pickup?->operator?->code ?? '-' }}
          — {{ $return->pickup?->operator?->name ?? '-' }}
        </div>

        @if($isVoided)
          <div class="small text-muted mt-1">
            <span class="badge-status badge-void">VOID</span>
            • Void at: <span class="mono">{{ $voidedAtLabel }}</span>
            @if(!empty($return->void_reason))
              • Alasan: {{ $return->void_reason }}
            @endif
          </div>
        @endif
      </div>

      <div class="d-flex flex-column align-items-end gap-2">
        <div class="d-flex gap-1 flex-wrap justify-content-end">
          @if($isVoided)
            <span class="badge-status badge-void">VOID</span>
          @else
            <span class="badge-status {{ $status === 'posted' ? 'badge-posted' : 'badge-draft' }}">
              {{ strtoupper($status) }}
            </span>
          @endif

          @if($hasReject)
            <span class="badge-status badge-reject">ADA REJECT</span>
          @else
            <span class="pill pill-ok">SEMUA AMAN</span>
          @endif
        </div>

        <div class="header-actions">
          <a href="{{ route('production.sewing.returns.index') }}" class="btn btn-outline-secondary btn-sm btn-pill">
            <i class="bi bi-arrow-left"></i><span>Kembali</span>
          </a>

          <a href="{{ route('production.sewing.returns.print', $return) }}" target="_blank"
             class="btn btn-outline-secondary btn-sm btn-pill">
            <i class="bi bi-printer"></i><span>Cetak</span>
          </a>

          <a href="{{ route('production.sewing.returns.barcode', $return) }}" target="_blank"
             class="btn btn-outline-secondary btn-sm btn-pill">
            <i class="bi bi-upc-scan"></i><span>Cetak Barcode</span>
          </a>

          @if($return->pickup)
            <a href="{{ route('production.sewing.pickups.show', $return->pickup) }}" class="btn btn-outline-primary btn-sm btn-pill">
              <i class="bi bi-box-seam"></i><span>Lihat Pickup</span>
            </a>
          @endif

          {{-- VOID ACTION --}}
      {{-- VOID ACTION (OWNER ONLY) --}}
        @if($isOwner && !$isVoided && $hasVoidRoute)
        <button type="button" class="btn btn-outline-danger btn-sm btn-pill"
                data-bs-toggle="modal" data-bs-target="#voidModal">
            <i class="bi bi-x-octagon"></i><span>VOID</span>
        </button>
        @endif

        </div>
      </div>
    </div>
  </div>

  {{-- SUMMARY --}}
  <div class="card mb-2">
    <div class="card-section">
      <div class="section-title mb-2">Ringkasan</div>

      <div class="summary-grid">
        <div>
          <div class="summary-label">Pickup</div>
          <div class="summary-value mono">{{ number_format($totalPickup ?? 0, 2, ',', '.') }}</div>
        </div>
        <div>
          <div class="summary-label">Diproses (Setor + Reject)</div>
          <div class="summary-value mono">{{ number_format($totalProcessed ?? 0, 2, ',', '.') }}</div>
        </div>
        <div>
          <div class="summary-label">Setor</div>
          <div class="summary-value summary-ok mono">{{ number_format($totalOk ?? 0, 2, ',', '.') }}</div>
        </div>
        <div>
          <div class="summary-label">Reject</div>
          <div class="summary-value summary-rj mono">{{ number_format($totalReject ?? 0, 2, ',', '.') }}</div>
          <div class="section-sub">{{ number_format($rejectPercent ?? 0, 1, ',', '.') }}%</div>
        </div>
        <div>
          <div class="summary-label">Dadakan</div>
          <div class="summary-value summary-warn mono">
            {{ number_format($totalDirectPick ?? ($totalDirectPickView ?? 0), 2, ',', '.') }}
          </div>
        </div>
        <div>
          <div class="summary-label">Belum Setor</div>
          <div class="summary-value summary-warn mono">{{ number_format($totalRemaining ?? 0, 2, ',', '.') }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- ACCOUNTING / HPP / MUTATION TRACE --}}
  @if($isOwner)
    <details class="financial-section accounting-section mb-2">
      <summary>
        <div class="financial-heading">
          <div>
            <div class="financial-title"><i class="bi bi-journal-check me-1"></i>Akuntansi Baru</div>
            <div class="financial-subtitle">Jejak jurnal Sewing Return ini. Jurnal VOID tetap tampil sebagai audit, tetapi tidak masuk saldo aktif.</div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge-mini">{{ $accountingStatusLabel }}</span>
            <i class="bi bi-chevron-down chevron"></i>
          </div>
        </div>
      </summary>
      <div class="financial-body">
        <div class="financial-summary">
          <div class="financial-kpi">
            <div class="financial-label">Debit aktif</div>
            <div class="financial-value mono">Rp {{ number_format((float) ($accountingPostedTotalDebit ?? 0), 0, ',', '.') }}</div>
          </div>
          <div class="financial-kpi">
            <div class="financial-label">Kredit aktif</div>
            <div class="financial-value mono">Rp {{ number_format((float) ($accountingPostedTotalCredit ?? 0), 0, ',', '.') }}</div>
          </div>
          <div class="financial-kpi">
            <div class="financial-label">Mapping</div>
            <div class="financial-value mono">{{ $sourceAccountCode }} → {{ $destinationAccountCode }}</div>
          </div>
          <div class="financial-kpi">
            <div class="financial-label">Jurnal</div>
            <div class="financial-value mono">{{ ($accountingJournalEntries ?? collect())->count() }} total / {{ ($accountingActiveEntries ?? collect())->count() }} aktif</div>
          </div>
        </div>

        @if(($accountingJournalEntries ?? collect())->isEmpty())
          <div class="small text-muted">Belum ada jurnal akuntansi baru untuk Sewing Return ini.</div>
        @else
          <div class="table-responsive">
            <table class="table table-sm financial-table">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Transaksi</th>
                  <th>Referensi</th>
                  <th class="text-end">Debit</th>
                  <th class="text-end">Kredit</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($accountingJournalEntries as $journal)
                  @php
                    $journalDebit = (float) $journal->lines->sum('debit');
                    $journalCredit = (float) $journal->lines->sum('credit');
                    $journalVoided = !is_null($journal->voided_at);
                  @endphp
                  <tr class="{{ $journalVoided ? 'row-reversal' : '' }}">
                    <td class="mono text-muted">{{ optional($journal->created_at)->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $accountingSourceLabel($journal->source_type) }}</td>
                    <td class="mono">{{ $journal->reference ?: '—' }}</td>
                    <td class="text-end mono">Rp {{ number_format($journalDebit, 0, ',', '.') }}</td>
                    <td class="text-end mono">Rp {{ number_format($journalCredit, 0, ',', '.') }}</td>
                    <td><span class="badge-mini">{{ $journalVoided ? 'VOID' : 'POSTED' }}</span></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
        <div class="financial-note">Mapping aktif: Debit {{ $destinationAccountCode }} {{ $destinationAccountLabel }}; Kredit {{ $sourceAccountCode }} {{ $sourceAccountLabel }}. Nominal bersumber dari posting aktual.</div>
      </div>
    </details>

    <details class="financial-section hpp-section mb-2">
      <summary>
        <div class="financial-heading">
          <div>
            <div class="financial-title"><i class="bi bi-calculator me-1"></i>Estimasi HPP Sewing Return</div>
            <div class="financial-subtitle">Realisasi nilai yang keluar dari sumber dan masuk ke tujuan berdasarkan mutasi aktual.</div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge-mini">{{ ($hppRows ?? collect())->count() }} item</span>
            <i class="bi bi-chevron-down chevron"></i>
          </div>
        </div>
      </summary>
      <div class="financial-body">
        <div class="financial-summary">
          <div class="financial-kpi">
            <div class="financial-label">Nilai sumber</div>
            <div class="financial-value mono">Rp {{ number_format(abs((float) ($sourceNetValue ?? 0)), 0, ',', '.') }}</div>
          </div>
          <div class="financial-kpi">
            <div class="financial-label">Qty masuk tujuan</div>
            <div class="financial-value mono">{{ number_format((float) ($destinationNetQty ?? 0), 2, ',', '.') }} pcs</div>
          </div>
          <div class="financial-kpi">
            <div class="financial-label">Total masuk {{ $destinationWarehouseCode ?: 'tujuan' }}</div>
            <div class="financial-value mono">Rp {{ number_format((float) ($destinationNetValue ?? 0), 0, ',', '.') }}</div>
          </div>
          <div class="financial-kpi">
            <div class="financial-label">HPP / pcs</div>
            <div class="financial-value mono">Rp {{ number_format(abs((float) ($destinationNetQty ?? 0)) > 0.000001 ? ((float) ($destinationNetValue ?? 0) / (float) $destinationNetQty) : 0, 0, ',', '.') }}</div>
          </div>
        </div>
        @if(($hppRows ?? collect())->isEmpty())
          <div class="small text-muted">Belum ada mutasi tujuan untuk menghitung HPP.</div>
        @else
          <div class="table-responsive">
            <table class="table table-sm financial-table">
              <thead><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">HPP / pcs</th><th class="text-end">Total</th></tr></thead>
              <tbody>
                @foreach($hppRows as $hpp)
                  <tr>
                    <td><span class="mono fw-bold">{{ $hpp['item_code'] }}</span><span class="small text-muted ms-1">{{ $hpp['item_name'] }}</span></td>
                    <td class="text-end mono">{{ number_format($hpp['qty'], 2, ',', '.') }}</td>
                    <td class="text-end mono">Rp {{ number_format($hpp['unit_cost'], 0, ',', '.') }}</td>
                    <td class="text-end mono">Rp {{ number_format($hpp['value'], 0, ',', '.') }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
        <div class="financial-note">Upah jahit tidak ditambahkan ulang pada Sewing Return karena sudah masuk saat Ambil Jahit.</div>
      </div>
    </details>
  @endif

  <details class="financial-section mutation-section mb-2">
    <summary>
      <div class="financial-heading">
        <div>
          <div class="financial-title"><i class="bi bi-arrow-left-right me-1"></i>Mutasi Stok</div>
          <div class="financial-subtitle">Perpindahan stok aktual, termasuk baris reversal saat dokumen di-VOID.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge-mini">{{ $mutationStatusLabel }}</span>
          <i class="bi bi-chevron-down chevron"></i>
        </div>
      </div>
    </summary>
    <div class="financial-body">
      <div class="financial-summary">
        <div class="financial-kpi"><div class="financial-label">Qty masuk</div><div class="financial-value mono">{{ number_format((float) ($mutationQtyIn ?? 0), 2, ',', '.') }}</div></div>
        <div class="financial-kpi"><div class="financial-label">Qty keluar</div><div class="financial-value mono">{{ number_format((float) ($mutationQtyOut ?? 0), 2, ',', '.') }}</div></div>
        <div class="financial-kpi"><div class="financial-label">Nilai masuk</div><div class="financial-value mono">Rp {{ number_format((float) ($mutationValueIn ?? 0), 0, ',', '.') }}</div></div>
        <div class="financial-kpi"><div class="financial-label">Net qty / nilai</div><div class="financial-value mono">{{ number_format((float) ($mutationNetQty ?? 0), 2, ',', '.') }} / Rp {{ number_format((float) ($mutationNetValue ?? 0), 0, ',', '.') }}</div></div>
      </div>
      @if(($mutationRows ?? collect())->isEmpty())
        <div class="small text-muted">Belum ada mutasi stok untuk Sewing Return ini.</div>
      @else
        <div class="table-responsive">
          <table class="table table-sm financial-table">
            <thead><tr><th>Transaksi</th><th>Gudang</th><th>Item</th><th class="text-end">Qty</th><th class="text-end">Unit cost</th><th class="text-end">Nilai</th><th>Status</th></tr></thead>
            <tbody>
              @foreach($mutationRows as $mutation)
                @php $isReversalMutation = str_contains((string) $mutation->source_type, 'void'); @endphp
                <tr class="{{ $isReversalMutation ? 'row-reversal' : '' }}">
                  <td>{{ $mutationSourceLabel($mutation->source_type) }}</td>
                  <td><span class="mono">{{ $mutation->warehouse_code ?: '-' }}</span><div class="small text-muted">{{ $mutation->warehouse_name ?: '' }}</div></td>
                  <td><span class="mono fw-bold">{{ $mutation->item_code ?: '-' }}</span><div class="small text-muted">{{ $mutation->item_name ?: '' }}</div></td>
                  <td class="text-end mono">{{ number_format((float) $mutation->qty_change, 2, ',', '.') }}</td>
                  <td class="text-end mono">Rp {{ number_format((float) $mutation->unit_cost, 0, ',', '.') }}</td>
                  <td class="text-end mono">Rp {{ number_format((float) $mutation->total_cost, 0, ',', '.') }}</td>
                  <td><span class="badge-mini">{{ $isReversalMutation ? 'REVERSAL' : 'POSTED' }}</span></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </details>

  {{-- PER ITEM --}}
  <div class="card mb-2">
    <div class="card-section">
      <div class="section-title mb-2">Ringkasan per Item</div>

      <table class="table table-sm align-middle mb-0">
        <thead>
          <tr>
            <th style="width:44px;">#</th>
            <th style="width:140px;">Item</th>
            <th>Nama</th>
            <th class="text-end" style="width:120px;">Setor</th>
            <th class="text-end" style="width:120px;">Reject</th>
            <th class="text-end" style="width:130px;">Dadakan</th>
            <th class="text-end" style="width:130px;">Belum Setor</th>
          </tr>
        </thead>
        <tbody>
          @forelse($perItem as $idx => $row)
            <tr class="{{ $row['rj'] > 0 && $row['setor'] == 0 ? 'row-rj' : ($row['setor'] > 0 && $row['rj'] == 0 ? 'row-ok' : '') }}">
              <td class="text-muted mono">{{ $idx + 1 }}</td>
              <td class="mono fw-bold">{{ $row['code'] }}</td>
              <td>{{ $row['name'] }}</td>

              <td class="text-end">
                <span class="pill pill-ok mono">{{ number_format($row['setor'], 2, ',', '.') }}</span>
              </td>

              <td class="text-end">
                @if($row['rj'] > 0)
                  <span class="pill pill-rj mono">{{ number_format($row['rj'], 2, ',', '.') }}</span>
                @else
                  <span class="text-muted mono">0,00</span>
                @endif
              </td>

              <td class="text-end">
                <span class="pill pill-total mono">{{ number_format($row['direct_pick'], 2, ',', '.') }}</span>
              </td>

              <td class="text-end">
                <span class="pill pill-total mono">{{ number_format($row['remaining'], 2, ',', '.') }}</span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted small py-3">Tidak ada data item.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- DETAIL --}}
  <div class="card mb-2">
    <div class="card-section">
      <div class="section-title mb-2">Detail Bundle</div>

      <div class="detail-bundle-wrap">
      <table class="table table-sm align-middle mono detail-bundle-table mb-0">
        <thead>
          <tr>
            <th style="width:40px;">#</th>
            <th class="detail-item-col">Item</th>
            <th class="detail-bundle-col">Bundle / LOT</th>
            <th class="text-end" style="width:110px;">Setor</th>
            <th class="text-end" style="width:110px;">Reject</th>
            <th class="text-end" style="width:110px;">Dadakan</th>
            <th class="text-end" style="width:110px;">Belum Setor</th>
            <th class="detail-compact-col">Kelengkapan</th>
            <th class="detail-note-col">Catatan</th>
          </tr>
        </thead>
        <tbody>
          @forelse($lines as $i => $line)
            @php
              $pickupLine = $line->sewingPickupLine;
              $bundle = optional($pickupLine)->bundle;
              $item = optional($bundle)->finishedItem ?: optional($pickupLine)->finishedItem;
              $lot = optional(optional($bundle)->cuttingJob)->lot;
              $supplyLines = $pickupLine?->supplyLines ?? collect();
              $supplyStatus = $pickupLine?->supply_status ?? 'unmigrated';
              $supplyStatusLabel = [
                'complete' => 'Lengkap',
                'partial' => 'Kurang',
                'incomplete' => 'Belum lengkap',
                'unmigrated' => 'Belum dimigrasikan',
              ][$supplyStatus] ?? 'Belum dimigrasikan';

              $setor = (float) $line->qty_ok;
              $rj = (float) $line->qty_reject;

              $qtyBundle = (float) ($pickupLine->qty_bundle ?? 0);
              $returnedOkAll = (float) ($pickupLine->qty_returned_ok ?? 0);
              $returnedRejAll = (float) ($pickupLine->qty_returned_reject ?? 0);
              $directPickAll = (float) ($pickupLine->qty_direct_picked ?? 0);
              $progressAdjAll = (float) ($pickupLine->qty_progress_adjusted ?? 0);

              $remainingRow = max($qtyBundle - ($returnedOkAll + $returnedRejAll + $directPickAll + $progressAdjAll), 0);
            @endphp

            <tr class="{{ $rj > 0 && $setor == 0 ? 'row-rj' : ($setor > 0 && $rj == 0 ? 'row-ok' : '') }}">
              <td class="text-muted">{{ $i + 1 }}</td>
              <td class="detail-item-cell" title="{{ trim(($item?->code ?? '-') . ' ' . ($item?->name ?? '')) }}">
                <div class="fw-bold detail-code">{{ $item?->code ?? '-' }}</div>
                <div class="small text-muted detail-name">{{ $item?->name ?? '' }}</div>
              </td>
              <td class="detail-bundle-cell" title="{{ trim(($bundle?->bundle_code ?? '-') . ' ' . ($lot ? 'LOT ' . $lot->code : '')) }}">
                <div class="small detail-bundle-code">
                  {{ $bundle?->bundle_code ?? '-' }}
                  @if($lot)
                    • LOT {{ $lot->code }}
                  @endif
                </div>
                <div class="small text-muted mt-1">
                  Pickup: <span class="mono">{{ number_format($qtyBundle, 2, ',', '.') }}</span>
                </div>
              </td>

              <td class="text-end">{{ number_format($setor, 2, ',', '.') }}</td>
              <td class="text-end">{{ number_format($rj, 2, ',', '.') }}</td>
              <td class="text-end">{{ number_format($directPickAll, 2, ',', '.') }}</td>
              <td class="text-end">{{ number_format($remainingRow, 2, ',', '.') }}</td>
              <td class="detail-completeness-cell">
                <div class="fw-bold small">{{ $supplyStatusLabel }}</div>
                @if($supplyLines->isNotEmpty())
                  @foreach($supplyLines as $supply)
                    <div class="small text-muted detail-supply-line" title="{{ $supply->materialItem?->code ?? 'Bahan' }} {{ number_format((float) $supply->issued_qty, 4, ',', '.') }}/{{ number_format((float) $supply->required_qty, 4, ',', '.') }} {{ $supply->uom ?: $supply->materialItem?->unit }}">
                      {{ $supply->materialItem?->code ?? 'Bahan' }}
                      {{ number_format((float) $supply->issued_qty, 4, ',', '.') }}/{{ number_format((float) $supply->required_qty, 4, ',', '.') }}
                      {{ $supply->uom ?: $supply->materialItem?->unit }}
                    </div>
                  @endforeach
                @else
                  <div class="small text-muted">Data lama belum punya detail per bundle.</div>
                @endif
              </td>

              <td class="detail-note-cell" title="{{ $line->notes ?: '—' }}">{{ $line->notes ?: '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center text-muted small py-3">Tidak ada detail.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
      </div>

      @if($return->notes)
        <hr class="my-2">
        <div class="small">
          <span class="text-muted">Catatan:</span> {{ $return->notes }}
        </div>
      @endif
    </div>
  </div>

</div>

{{-- VOID MODAL --}}
@if(!$isVoided && $hasVoidRoute)
  <div class="modal fade" id="voidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="{{ route('production.sewing.returns.void', $return->id) }}">
          @csrf

          <div class="modal-header">
            <div>
              <div class="fw-bold">VOID Sewing Return</div>
              <div class="small text-muted">Kode: <span class="mono">{{ $return->code }}</span></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <div class="alert alert-warning py-2 mb-3">
              <div class="fw-bold">Perhatian</div>
              <div class="small">
                Aksi ini akan <b>membalik stok & counter</b> (WIP-SEW/WH-PRD/REJ-SEW) sesuai data return ini.
              </div>
            </div>

            <label class="form-label">Alasan VOID (opsional)</label>
            <input type="text" name="reason" class="form-control" maxlength="255"
              placeholder="Misal: Salah input qty / salah pilih bundle / double submit"
              value="{{ old('reason') }}">

            <div class="small text-muted mt-2">
              Tips: isi singkat supaya ada jejak audit.
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-danger"
              onclick="return confirm('Yakin VOID? Stok & counter akan dibalik.');">
              Ya, VOID
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endif
@endsection
