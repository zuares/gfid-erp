{{-- resources/views/production/finishing_jobs/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Finishing ' . $job->code)

@push('head')
<style>
  .page-wrap{ max-width:1000px; margin-inline:auto; padding:.8rem .8rem 3.5rem; }
  body[data-theme="light"] .page-wrap{
    background: radial-gradient(circle at top left,
      rgba(56,189,248,.20) 0,
      rgba(125,211,252,.12) 22%,
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
  .badge-posted{ background: rgba(14,165,233,.14); color:#0369a1; border:1px solid rgba(14,165,233,.45); }
  .badge-draft{ background: rgba(148,163,184,.18); color:#4b5563; border:1px solid rgba(148,163,184,.6); }
  .badge-reject{ background: rgba(248,113,113,.16); color:#b91c1c; border:1px solid rgba(248,113,113,.6); }

  .pill{ border-radius:999px; padding:.12rem .6rem; font-size:.72rem; font-weight:800; }
  .pill-ok{ background: rgba(14,165,233,.12); color:#0369a1; border:1px solid rgba(14,165,233,.4); }
  .pill-rj{ background: rgba(248,113,113,.14); color:#b91c1c; border:1px solid rgba(248,113,113,.5); }
  .pill-total{ background: rgba(59,130,246,.08); color:#1d4ed8; border:1px solid rgba(59,130,246,.4); }
  .pill-warn{ background: rgba(245,158,11,.12); color:#92400e; border:1px solid rgba(245,158,11,.45); }

  .section-title{ font-size:.9rem; font-weight:900; margin-bottom:.15rem; }
  .section-sub{ font-size:.75rem; color: var(--muted); }

  .summary-grid{
    display:grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap:.45rem .9rem;
    font-size:.8rem;
  }
  @media(min-width:768px){
    .summary-grid{ grid-template-columns: repeat(5, minmax(0,1fr)); }
  }
  .summary-label{ font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color: var(--muted); }
  .summary-value{ font-weight:900; }
  .summary-ok{ color:#0369a1; }
  .summary-rj{ color:#b91c1c; }
  .summary-warn{ color:#b45309; }

  .table thead th{
    font-size:.74rem; text-transform:uppercase; letter-spacing:.08em;
    color: var(--muted); border-top:none; white-space:nowrap;
  }
  .table tbody td{ font-size:.8rem; vertical-align:middle; }
  .row-ok{ background: rgba(240,249,255,.96); }
  .row-rj{ background: rgba(254,242,242,.96); }

  .header-actions{
    display:flex; gap:.45rem; flex-wrap:wrap; justify-content:flex-end;
  }
  .btn-pill{
    border-radius:999px; padding:.25rem .75rem; font-size:.78rem;
    display:inline-flex; align-items:center; gap:.35rem;
  }
  .table-scroll{ overflow-x:auto; -webkit-overflow-scrolling:touch; }
  .table-scroll table{ min-width:760px; }
</style>
@endpush

@section('content')
@php
  use Illuminate\Support\Carbon;

  $user = auth()->user();
  $isOwner = strtolower((string)($user?->role ?? '')) === 'owner';
  $status = $job->status ?? 'draft';
  $isPosted = $status === 'posted';

  $lines = $job->lines ?? collect();
  $bomGapCount = $lines->filter(fn($l) => (bool) ($l->bom_has_gaps ?? false))->count();
  $rejectLines = $lines->filter(fn($line) => (float) $line->qty_reject > 0.0001);
  $totalIn = (float) $lines->sum('qty_in');
  $totalOk = (float) $lines->sum('qty_ok');
  $totalReject = (float) $lines->sum('qty_reject');
  $totalProcessed = $totalOk + $totalReject;
  $rejectPercent = $totalProcessed > 0 ? ($totalReject / $totalProcessed) * 100 : 0;
  $bundleCount = $lines->pluck('cutting_job_bundle_id')->filter()->unique()->count();
  $hasReject = $totalReject > 0.000001;

  try {
    $dateLabel = $job->date
      ? (function_exists('id_day') ? id_day($job->date) : Carbon::parse($job->date)->format('d/m/Y'))
      : '-';
  } catch (\Throwable $e) {
    $dateLabel = optional($job->date)->format('d/m/Y') ?? '-';
  }

  $fmt = fn($n, $d = 2) => number_format((float) $n, $d, ',', '.');
  $fmt0 = fn($n) => number_format((float) $n, 0, ',', '.');

  $operatorName = function ($line) {
    return $line->sewingOperator?->name
      ?? $line->sewingPickupLine?->sewingPickup?->operator?->name
      ?? '-';
  };

  $itemMeta = function ($line) {
    return [
      'code' => $line->item?->code ?? ($line->bundle?->finishedItem?->code ?? '-'),
      'name' => $line->item?->name ?? ($line->bundle?->finishedItem?->name ?? ''),
      'lot' => $line->bundle?->lot?->code ?? '-',
      'lot_name' => $line->bundle?->lot?->item?->name ?? '',
      'bundle' => $line->bundle?->bundle_code ?? '-',
      'job' => $line->bundle?->cuttingJob?->code ?? '-',
    ];
  };

  $perItem = $lines
    ->groupBy(fn($line) => (int) ($line->item_id ?? $line->bundle?->finished_item_id ?? 0))
    ->map(function ($group) use ($itemMeta) {
      $first = $group->first();
      $meta = $itemMeta($first);
      return [
        'code' => $meta['code'],
        'name' => $meta['name'],
        'in' => (float) $group->sum('qty_in'),
        'ok' => (float) $group->sum('qty_ok'),
        'reject' => (float) $group->sum('qty_reject'),
      ];
    })
    ->sortBy('code')
    ->values();
@endphp

<div class="page-wrap">

  @if(session('status'))
    <div class="alert alert-success py-2">{{ session('status') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger py-2">{{ session('error') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">
      <div class="fw-bold mb-1">Terjadi error:</div>
      <ul class="mb-0">
        @foreach($errors->all() as $err)
          <li>{!! nl2br(e($err)) !!}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- HEADER --}}
  <div class="card mb-2">
    <div class="card-section d-flex justify-content-between flex-wrap gap-2">
      <div>
        <div class="fw-bold mb-1">
          Finishing <span class="mono">{{ $job->code }}</span>
        </div>

        <div class="small text-muted">
          <span class="mono">{{ $dateLabel }}</span>
          @if($bundleCount > 0)
            • Bundle: <span class="mono">{{ $bundleCount }}</span>
          @endif
        </div>

        <div class="small text-muted mt-1">
          Dibuat oleh: {{ $job->createdBy?->name ?? '-' }}
        </div>

        @if($job->notes)
          <div class="small text-muted mt-1">
            Catatan: {{ $job->notes }}
          </div>
        @endif
      </div>

      <div class="d-flex flex-column align-items-end gap-2">
        <div class="d-flex gap-1 flex-wrap justify-content-end">
          <span class="badge-status {{ $isPosted ? 'badge-posted' : 'badge-draft' }}">
            {{ strtoupper($status) }}
          </span>

          @if($hasReject)
            <span class="badge-status badge-reject">ADA REJECT</span>
          @else
            <span class="pill pill-ok">SEMUA AMAN</span>
          @endif
        </div>

        <div class="header-actions">
          <a href="{{ route('production.finishing_jobs.index') }}" class="btn btn-outline-secondary btn-sm btn-pill">
            <i class="bi bi-arrow-left"></i><span>Kembali</span>
          </a>

          @if(!$isPosted)
            <a href="{{ route('production.finishing_jobs.edit', $job->id) }}" class="btn btn-outline-primary btn-sm btn-pill">
              <i class="bi bi-pencil"></i><span>Edit</span>
            </a>

            <form method="POST" action="{{ route('production.finishing_jobs.post', $job->id) }}"
              onsubmit="return confirm('Post finishing ini? OK akan pindah ke WH-PRD. Reject Finishing ke REJ-FIN, Reject Jahit ke REJ-SEW. Lanjutkan?');">
              @csrf
              <button type="submit" class="btn btn-success btn-sm btn-pill">
                <i class="bi bi-check2-circle"></i><span>Post</span>
              </button>
            </form>
          @endif

          @if($isPosted && $isOwner && $bomGapCount > 0 && Route::has('production.finishing_jobs.reapply_bom'))
            <form method="POST" action="{{ route('production.finishing_jobs.reapply_bom', $job->id) }}"
              onsubmit="return confirm('Apply ulang BOM untuk {{ $bomGapCount }} baris yang belum lengkap?\n\nPastikan GRN sudah diinput terlebih dahulu.');">
              @csrf
              <button type="submit" class="btn btn-warning btn-sm btn-pill">
                <i class="bi bi-arrow-repeat"></i><span>Apply BOM Ulang</span>
              </button>
            </form>
          @endif

        </div>
      </div>
    </div>
  </div>

  {{-- BOM GAP BANNER --}}
  @if($isPosted && $bomGapCount > 0)
  <div style="background:#fefce8;border:1px solid #fde047;border-radius:12px;padding:.75rem 1rem;margin-bottom:.75rem;display:flex;align-items:flex-start;gap:.6rem;font-size:.85rem;color:#854d0e;">
    <span style="font-size:1rem;flex-shrink:0;">⚠️</span>
    <div>
      <strong>BOM tidak lengkap:</strong> {{ $bomGapCount }} baris SKU belum semua materialnya ter-cover karena belum ada GRN saat posting.
      @if($isOwner && Route::has('production.finishing_jobs.reapply_bom'))
        Setelah GRN diinput, klik tombol <strong>Apply BOM Ulang</strong> di atas untuk menyelesaikan rekonsiliasi.
      @else
        Hubungi Owner untuk apply ulang BOM setelah GRN diinput.
      @endif
    </div>
  </div>
  @endif

  {{-- SUMMARY --}}
  <div class="card mb-2">
    <div class="card-section">
      <div class="section-title mb-2">Ringkasan</div>

      <div class="summary-grid">
        <div>
          <div class="summary-label">Masuk FIN</div>
          <div class="summary-value mono">{{ $fmt($totalIn) }}</div>
        </div>
        <div>
          <div class="summary-label">Diproses (OK + Reject)</div>
          <div class="summary-value mono">{{ $fmt($totalProcessed) }}</div>
        </div>
        <div>
          <div class="summary-label">OK</div>
          <div class="summary-value summary-ok mono">{{ $fmt($totalOk) }}</div>
        </div>
        <div>
          <div class="summary-label">Reject</div>
          <div class="summary-value summary-rj mono">{{ $fmt($totalReject) }}</div>
          <div class="section-sub">{{ number_format($rejectPercent, 1, ',', '.') }}%</div>
        </div>
        <div>
          <div class="summary-label">Bundle</div>
          <div class="summary-value summary-warn mono">{{ $bundleCount }}</div>
        </div>
      </div>
    </div>
  </div>

  @if($hasReject && !$isPosted)
    <div class="alert alert-warning py-2 mb-2">
      <div class="fw-bold">Finishing ini punya reject dan belum diposting.</div>
      <div class="small">
        Stok masih di WIP-FIN. Setelah post, OK masuk WH-PRD, reject finishing masuk REJ-FIN, reject jahit masuk REJ-SEW.
      </div>
    </div>
  @endif

  {{-- PER ITEM --}}
  <div class="card mb-2">
    <div class="card-section">
      <div class="section-title mb-2">Ringkasan per Item</div>

      <div class="table-scroll">
        <table class="table table-sm align-middle mb-0">
          <thead>
            <tr>
              <th style="width:44px;">#</th>
              <th style="width:140px;">Item</th>
              <th>Nama</th>
              <th class="text-end" style="width:120px;">Masuk</th>
              <th class="text-end" style="width:120px;">OK</th>
              <th class="text-end" style="width:120px;">Reject</th>
            </tr>
          </thead>
          <tbody>
            @forelse($perItem as $idx => $row)
              <tr class="{{ $row['reject'] > 0 && $row['ok'] == 0 ? 'row-rj' : ($row['ok'] > 0 && $row['reject'] == 0 ? 'row-ok' : '') }}">
                <td class="text-muted mono">{{ $idx + 1 }}</td>
                <td class="mono fw-bold">{{ $row['code'] }}</td>
                <td>{{ $row['name'] }}</td>
                <td class="text-end mono">{{ $fmt($row['in']) }}</td>
                <td class="text-end">
                  <span class="pill pill-ok mono">{{ $fmt($row['ok']) }}</span>
                </td>
                <td class="text-end">
                  @if($row['reject'] > 0)
                    <span class="pill pill-rj mono">{{ $fmt($row['reject']) }}</span>
                  @else
                    <span class="text-muted mono">0,00</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted small py-3">Tidak ada data item.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @if($rejectLines->count())
    <div class="card mb-2">
      <div class="card-section">
        <div class="section-title mb-2">Ringkasan Reject</div>

        <div class="table-scroll">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th style="width:44px;">#</th>
                <th style="width:140px;">Item</th>
                <th>Bundle / LOT</th>
                <th class="text-end" style="width:110px;">Reject</th>
                <th style="width:120px;">Jenis</th>
                <th style="width:180px;">Alasan</th>
                <th style="width:160px;">Finishing</th>
                <th style="width:160px;">Jahit</th>
              </tr>
            </thead>
            <tbody>
              @foreach($rejectLines->values() as $i => $line)
                @php
                  $meta = $itemMeta($line);
                @endphp
                <tr class="row-rj">
                  <td class="text-muted mono">{{ $i + 1 }}</td>
                  <td>
                    <div class="fw-bold mono">{{ $meta['code'] }}</div>
                    <div class="small text-muted">{{ $meta['name'] }}</div>
                  </td>
                  <td>
                    <div class="small">{{ $meta['bundle'] }}</div>
                    <div class="small text-muted">LOT: <span class="mono">{{ $meta['lot'] }}</span></div>
                    @if($isOwner)
                      <div class="small text-muted">Job: <span class="mono">{{ $meta['job'] }}</span></div>
                    @endif
                  </td>
                  <td class="text-end">
                    <span class="pill pill-rj mono">{{ $fmt($line->qty_reject) }}</span>
                  </td>
                  <td>{{ ($line->reject_cause ?? 'finishing') === 'sewing' ? 'Jahit' : 'Finishing' }}</td>
                  <td>{{ $line->reject_reason ?: '—' }}</td>
                  <td>{{ $line->operator?->name ?? '-' }}</td>
                  <td>{{ $operatorName($line) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif

  {{-- DETAIL --}}
  <div class="card mb-2">
    <div class="card-section">
      <div class="section-title mb-2">Detail Bundle</div>

      <div class="table-scroll">
        <table class="table table-sm align-middle mono mb-0">
          <thead>
            <tr>
              <th style="width:40px;">#</th>
              <th style="width:150px;">Item</th>
              <th>Bundle / LOT</th>
              <th class="text-end" style="width:110px;">Masuk</th>
              <th class="text-end" style="width:110px;">OK</th>
              <th class="text-end" style="width:110px;">Reject</th>
              <th style="width:160px;">Finishing</th>
              <th style="width:160px;">Jahit</th>
            </tr>
          </thead>
          <tbody>
            @forelse($lines->values() as $i => $line)
              @php
                $meta = $itemMeta($line);
              @endphp
              @php
                $ok = (float) $line->qty_ok;
                $rj = (float) $line->qty_reject;
              @endphp
              <tr class="{{ $rj > 0 && $ok == 0 ? 'row-rj' : ($ok > 0 && $rj == 0 ? 'row-ok' : '') }}">
                <td class="text-muted">{{ $i + 1 }}</td>
                <td>
                  <div class="fw-bold">{{ $meta['code'] }}</div>
                  <div class="small text-muted">{{ $meta['name'] }}</div>
                </td>
                <td>
                  <div class="small">{{ $meta['bundle'] }}</div>
                  <div class="small text-muted">
                    LOT <span class="mono">{{ $meta['lot'] }}</span>
                    @if($meta['lot_name'])
                      • {{ $meta['lot_name'] }}
                    @endif
                  </div>
                  @if($isOwner)
                    <div class="small text-muted">Job: <span class="mono">{{ $meta['job'] }}</span></div>
                  @endif
                </td>
                <td class="text-end">{{ $fmt($line->qty_in) }}</td>
                <td class="text-end">{{ $fmt($ok) }}</td>
                <td class="text-end">{{ $fmt($rj) }}</td>
                <td>{{ $line->operator?->name ?? '-' }}</td>
                <td>{{ $operatorName($line) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted small py-3">Tidak ada detail.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @if ($isOwner && $rmSnapshots->count())
    <div class="card mb-2">
      <div class="card-section">
        <div class="section-title mb-2">Snapshot HPP RM-only</div>

        <div class="table-scroll">
          <table class="table table-sm align-middle mono mb-0">
            <thead>
              <tr>
                <th style="width:170px;">Snapshot</th>
                <th>Item</th>
                <th class="text-end" style="width:110px;">Qty</th>
                <th class="text-end" style="width:130px;">HPP/pcs</th>
                <th class="text-end" style="width:140px;">Total</th>
              </tr>
            </thead>
            <tbody>
              @foreach($rmSnapshots as $snap)
                <tr>
                  <td>{{ $snap->snapshot_date ?? $snap->created_at }}</td>
                  <td>
                    <div class="fw-bold">{{ $snap->item?->code ?? '-' }}</div>
                    <div class="small text-muted">{{ $snap->item?->name ?? '' }}</div>
                  </td>
                  <td class="text-end">{{ $fmt0($snap->qty ?? 0) }}</td>
                  <td class="text-end">{{ $fmt0($snap->unit_cost ?? 0) }}</td>
                  <td class="text-end">{{ $fmt0($snap->total_cost ?? 0) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif
</div>
@endsection
