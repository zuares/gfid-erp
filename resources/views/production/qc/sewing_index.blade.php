{{-- resources/views/production/qc/sewing_index.blade.php --}}
@extends('layouts.app')

@section('title', 'QC Jahit')

@push('head')
<style>
    .page-wrap{ max-width:1120px; margin-inline:auto; padding:.65rem .75rem 3.5rem; }
    body[data-theme="light"] .page-wrap{ background:#f8fafc; }
    body[data-theme="dark"] .page-wrap{ background:#020617; }

    .card-main{
        background: var(--card);
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.20);
        box-shadow: none;
    }
    body[data-theme="dark"] .card-main{
        border-color: rgba(51, 65, 85, 0.85);
        box-shadow: none;
    }

    .title{ font-weight: 900; letter-spacing: 0; }
    .sub{ color:#64748b; font-size:.84rem; }
    body[data-theme="dark"] .sub{ color:#9ca3af; }

    .kpis{ display:flex; flex-wrap:wrap; gap:.38rem; margin-top:.55rem; }
    .kpi{
        display:inline-flex; align-items:baseline; gap:.45rem;
        border-radius:7px; padding:.2rem .48rem;
        border:1px solid rgba(148, 163, 184, 0.25);
        background: transparent;
        font-size:.76rem;
    }
    body[data-theme="dark"] .kpi{
        background: rgba(15, 23, 42, 0.96);
        border-color: rgba(51, 65, 85, 0.85);
    }
    .kpi .lbl{ text-transform:none; letter-spacing:0; font-size:.68rem; color:#94a3b8; }
    body[data-theme="dark"] .kpi .lbl{ color:#6b7280; }
    .kpi .val{ font-weight:800; }

    .table-list{ margin-bottom:0; }
    .table-list thead th{
        border-bottom-width:1px;
        font-size:.68rem;
        text-transform:none;
        letter-spacing:0;
        color:#6b7280;
        background: rgba(148, 163, 184, 0.05);
        padding:.58rem .62rem;
        white-space:nowrap;
    }
    body[data-theme="dark"] .table-list thead th{
        background: rgba(15, 23, 42, 0.98);
        color:#9ca3af;
        border-bottom-color: rgba(30, 64, 175, 0.6);
    }
    .table-list tbody td{
        vertical-align:middle;
        border-top-color: rgba(148, 163, 184, 0.16);
        padding:.56rem .62rem;
    }
    body[data-theme="dark"] .table-list tbody td{ border-top-color: rgba(51, 65, 85, 0.85); }

    .code-link{ font-weight:800; text-decoration:none; color:inherit; }
    .code-link:hover{ text-decoration:underline; }
    .muted{ font-size:.82rem; color:#6b7280; }
    body[data-theme="dark"] .muted{ color:#9ca3af; }

    .badge-status{
        border-radius:7px; padding:.16rem .48rem;
        font-size:.7rem; letter-spacing:0; text-transform:none;
        border:1px solid transparent;
        display:inline-flex; align-items:center; gap:.35rem;
        white-space:nowrap;
    }
    .badge-status::before{ content:''; width:7px; height:7px; border-radius:999px; display:inline-block; }

    .st-draft{ background: rgba(148, 163, 184, 0.10); color:#475569; border-color: rgba(148, 163, 184, 0.30); }
    .st-draft::before{ background: rgba(100, 116, 139, 0.95); }
    .st-pending{ background: rgba(245, 158, 11, 0.10); color:#b45309; border-color: rgba(245, 158, 11, 0.30); }
    .st-pending::before{ background: rgba(245, 158, 11, 0.95); }
    .st-posted{ background: rgba(34, 197, 94, 0.10); color:#166534; border-color: rgba(34, 197, 94, 0.30); }
    .st-posted::before{ background: rgba(34, 197, 94, 0.95); }
    .st-voided{ background: rgba(239, 68, 68, 0.10); color:#991b1b; border-color: rgba(239, 68, 68, 0.30); }
    .st-voided::before{ background: rgba(239, 68, 68, 0.95); }

    body[data-theme="dark"] .st-pending{ background: rgba(245, 158, 11, 0.20); color:#fde68a; border-color: rgba(245, 158, 11, 0.55); }
    body[data-theme="dark"] .st-posted{ background: rgba(34, 197, 94, 0.20); color:#dcfce7; border-color: rgba(34, 197, 94, 0.55); }
    body[data-theme="dark"] .st-voided{ background: rgba(239, 68, 68, 0.18); color:#fecaca; border-color: rgba(239, 68, 68, 0.55); }

    .item-chips{ display:flex; flex-wrap:wrap; gap:.25rem; }
    .item-chip{
        display:inline-flex; align-items:baseline; gap:.25rem;
        font-size:.72rem; line-height:1.1;
        padding:.2rem .45rem; border-radius:999px;
        background: rgba(148,163,184,.14);
        border:1px solid rgba(148,163,184,.18);
        white-space:nowrap;
    }
    .item-chip b{ font-weight:700; letter-spacing:.02em; }
    .item-chip .q{ color:#6b7280; }
    .item-chip-more{ background:transparent; color:#6b7280; }

    .btn-pill{ border-radius:7px; padding-inline:.78rem; box-shadow:none!important; }

    .empty{ padding:2.2rem 1.25rem; text-align:center; color:#64748b; }
    body[data-theme="dark"] .empty{ color:#9ca3af; }
    .divider{ height:1px; background: rgba(148, 163, 184, 0.20); }
    body[data-theme="dark"] .divider{ background: rgba(51, 65, 85, 0.85); }
    .flash-clean{ border-radius:8px; padding:.62rem .75rem; font-size:.84rem; border:1px solid rgba(148,163,184,.25); }

    .operator-name{ font-weight:700; }
    .date-label{ font-size:.66rem; color:#94a3b8; text-transform:uppercase; letter-spacing:.02em; }

    @media (max-width: 768px) {
        .page-wrap{ padding:.5rem .5rem 4rem; }
        .title{ font-size:1.05rem; }
        .sub{ display:none; }
        .kpis{ display:none; }
        .table-responsive{ overflow:visible; }
        .table-list thead{ display:none; }
        .table-list,
        .table-list tbody,
        .table-list tr,
        .table-list td{ display:block; width:100%; }
        .table-list tbody tr{
            padding:.68rem;
            border-top:1px solid rgba(148,163,184,.16);
        }
        .table-list tbody td{
            border:0;
            padding:0;
        }
        .table-list tbody td.mobile-hide{ display:none; }
        .sew-row-main{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:.75rem;
        }
        .sew-row-meta{
            display:flex;
            align-items:center;
            gap:.45rem;
            flex-wrap:wrap;
            margin-top:.35rem;
            color:#64748b;
            font-size:.78rem;
        }
        .sew-row-items{
            margin-top:.35rem;
        }
        .sew-row-action{
            margin-top:.55rem;
        }
        .sew-row-action .btn{
            width:100%;
            min-height:38px;
        }
    }
</style>
@endpush

@section('content')
<div class="page-wrap">
    @php
        use Illuminate\Support\Carbon;

        $fmtDate = function ($value, string $format = 'd M Y', string $fallback = '-') {
            if (empty($value)) return $fallback;
            try {
                if ($value instanceof \DateTimeInterface) return $value->format($format);
                return Carbon::parse($value)->format($format);
            } catch (\Throwable $e) {
                return $fallback;
            }
        };

        // Helper: ambil pickup date dari return
        $getPickupDate = function ($ret) {
            $firstLine = $ret->lines->first();
            return $firstLine?->pickupLine?->pickup?->date ?? null;
        };

        // Helper: ambil items per return
        $getItems = function ($ret) {
            return collect($ret->lines)
                ->groupBy(fn($l) => optional(optional($l->pickupLine)?->bundle?->finishedItem)->code ?: '—')
                ->map(fn($g) => [
                    'code' => optional(optional($g->first()->pickupLine)?->bundle?->finishedItem)->code ?: '—',
                    'name' => optional(optional($g->first()->pickupLine)?->bundle?->finishedItem)->name ?: '',
                    'qty'  => (int) $g->sum(fn($l) => (int) ($l->qty_ok ?? 0)),
                ])
                ->sortByDesc('qty')
                ->values();
        };

        // QC status check
        $hasQcSewing = function ($ret) {
            return \App\Models\QcResult::where('stage', \App\Models\QcResult::STAGE_SEWING)
                ->where('sewing_job_id', $ret->id)
                ->exists();
        };

        // Totals
        $totalRecords = $records->total();
        $pageCount = $records->count();
        $pageTotalQtyOk = $records->getCollection()->sum(fn($r) => (int) $r->lines->sum('qty_ok'));
    @endphp

    @if(session('success'))
        <div class="flash-clean alert alert-success mb-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash-clean alert alert-danger mb-2">{{ session('error') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
        <div>
            <div class="title h4 mb-1">QC Jahit</div>
            <div class="sub">Daftar setor jahit untuk di-QC.</div>

            <div class="kpis">
                <span class="kpi"><span class="lbl">Total</span><span class="val">{{ number_format($totalRecords, 0, ',', '.') }}</span></span>
                <span class="kpi"><span class="lbl">Halaman</span><span class="val">{{ number_format($pageCount, 0, ',', '.') }}</span></span>
                <span class="kpi"><span class="lbl">Qty OK</span><span class="val">{{ number_format($pageTotalQtyOk, 0, ',', '.') }}</span></span>
            </div>
        </div>
    </div>

    <div class="card card-main">
        <div class="card-body p-0">
            @if ($records->count() === 0)
                <div class="empty">
                    Belum ada setor jahit untuk di-QC.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-list">
                        <thead>
                            <tr>
                                <th style="width: 46px;">#</th>
                                <th>Operator</th>
                                <th style="width: 110px;">Tgl Ambil</th>
                                <th style="width: 110px;">Tgl Setor</th>
                                <th>Barang</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 100px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($records as $ret)
                                @php
                                    $items = $getItems($ret);
                                    $pickupDate = $getPickupDate($ret);
                                    $isVoided = !empty($ret->voided_at);
                                    $isQcDone = !$isVoided && $hasQcSewing($ret);

                                    $uiStatus = $isVoided ? 'voided' : ($isQcDone ? 'posted' : 'pending');
                                    $statusClass = match ($uiStatus) {
                                        'voided'  => 'st-voided',
                                        'posted'  => 'st-posted',
                                        'pending' => 'st-pending',
                                        default   => 'st-draft',
                                    };
                                    $statusLabel = match ($uiStatus) {
                                        'voided'  => 'Void',
                                        'posted'  => 'QC Selesai',
                                        'pending' => 'Belum QC',
                                        default   => 'Draft',
                                    };

                                    $qcHref = Route::has('production.qc.sewing.edit')
                                        ? route('production.qc.sewing.edit', $ret)
                                        : null;
                                    $detailHref = Route::has('production.sewing.returns.show')
                                        ? route('production.sewing.returns.show', $ret)
                                        : null;
                                    $actionHref = $qcHref ?: $detailHref;
                                    $actionLabel = $isQcDone ? 'Lihat' : 'Input QC';
                                @endphp

                                <tr>
                                    <td class="text-muted small mobile-hide">
                                        {{ ($records->currentPage() - 1) * $records->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="sew-row-main">
                                            <div>
                                                <div class="fw-semibold">{{ $ret->operator?->name ?? '-' }}</div>
                                                <div class="muted" style="font-size:.72rem;">{{ $ret->code }}</div>
                                                <div class="muted mt-1 d-md-none" style="font-size:.74rem;">Ambil {{ $fmtDate($pickupDate) }} · Setor {{ $fmtDate($ret->date) }}</div>
                                            </div>
                                            <span class="badge-status {{ $statusClass }} d-md-none">{{ $statusLabel }}</span>
                                        </div>

                                        @if ($items->isNotEmpty())
                                            <div class="sew-row-items d-md-none">
                                                <div class="item-chips">
                                                    @foreach ($items->take(3) as $it)
                                                        <span class="item-chip" title="{{ $it['name'] }}">
                                                            <b>{{ $it['code'] }}</b>
                                                            <span class="q">{{ number_format($it['qty'], 0, ',', '.') }}</span>
                                                        </span>
                                                    @endforeach
                                                    @if ($items->count() > 3)
                                                        <span class="item-chip item-chip-more">+{{ $items->count() - 3 }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </td>

                                    <td class="mobile-hide">
                                        <div class="fw-semibold" style="font-size:.82rem;">{{ $ret->operator?->name ?? '-' }}</div>
                                    </td>

                                    <td class="mobile-hide">
                                        {{ $fmtDate($pickupDate) }}
                                    </td>

                                    <td class="mobile-hide">
                                        {{ $fmtDate($ret->date) }}
                                    </td>

                                    <td class="mobile-hide">
                                        @if ($items->isEmpty())
                                            <span class="text-muted small">-</span>
                                        @else
                                            <div class="item-chips">
                                                @foreach ($items->take(4) as $it)
                                                    <span class="item-chip" title="{{ $it['name'] }}">
                                                        <b>{{ $it['code'] }}</b>
                                                        <span class="q">{{ number_format($it['qty'], 0, ',', '.') }}</span>
                                                    </span>
                                                @endforeach
                                                @if ($items->count() > 4)
                                                    <span class="item-chip item-chip-more">+{{ $items->count() - 4 }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>

                                    <td class="mobile-hide">
                                        <span class="badge-status {{ $statusClass }}">{{ $statusLabel }}</span>
                                    </td>

                                    <td class="text-end sew-row-action">
                                        @if ($actionHref)
                                            <a href="{{ $actionHref }}"
                                               class="btn btn-sm {{ $isQcDone ? 'btn-outline-secondary' : 'btn-outline-primary' }} btn-pill">
                                                {{ $actionLabel }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="divider"></div>

                <div class="p-3">
                    {{ $records->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
