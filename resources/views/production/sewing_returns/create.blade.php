{{-- resources/views/production/sewing_returns/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Return')

@push('head')
    <style>
        :root {
            --r: 14px;
            --b: rgba(148, 163, 184, .22);
            --muted: #6b7280;
            --soft2: rgba(148, 163, 184, .05);
            --accent: #16a34a;
            --ok: #16a34a;
            --rj: #b91c1c;
            --shadow: 0 10px 26px rgba(15, 23, 42, .08), 0 0 0 1px rgba(15, 23, 42, .03);
            --bottom-nav-h: 72px;
            --fab-gap: 12px;
            --fab-bottom: calc(var(--bottom-nav-h) + var(--fab-gap) + env(safe-area-inset-bottom));
            --vv-kbd: 0px;
        }

        .page-wrap { max-width: 980px; margin: 0 auto; padding: 14px 12px 96px; }

        @media(max-width:767.98px) {
            .page-wrap { padding-bottom: calc(var(--bottom-nav-h) + 130px + var(--vv-kbd)); }
            body.keyboard-open .page-wrap { padding-bottom: calc(14rem + var(--vv-kbd)); }
            .modal-dialog { margin: .75rem; }
            .modal-content { border-radius: 16px; }
            .modal-body { max-height: calc(100vh - 210px); overflow: auto; }
        }

        .panel { background: var(--card); border: 1px solid var(--b); border-radius: var(--r); box-shadow: var(--shadow); }
        .panel-h { padding: 12px 14px; border-bottom: 1px solid rgba(148, 163, 184, .12); }
        .panel-b { padding: 12px 14px; }

        .h-title { font-weight: 900; font-size: 1.05rem; margin: 0; }

        .meta { border: 1px solid rgba(148, 163, 184, .18); border-radius: var(--r); padding: 10px; background: var(--soft2); }
        body[data-theme="dark"] .meta { background: rgba(15, 23, 42, .35); }

        .form-label-sm { font-size: .75rem; font-weight: 800; color: var(--muted); }
        .form-control-sm, .form-select-sm { font-size: .88rem; padding: .42rem .55rem; border-radius: 12px; }

        .mono { font-variant-numeric: tabular-nums; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas; }

        .list { display: grid; gap: .6rem; margin-top: 12px; }

        .cardx { border: 1px solid rgba(148, 163, 184, .22); border-radius: 16px; background: var(--card); overflow: hidden; }
        .cardx-h { padding: 10px 12px; border-bottom: 1px solid rgba(148, 163, 184, .12); display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; }

        .cardx-left { display: flex; gap: 10px; align-items: flex-start; min-width: 0; }
        .cardx-left>div { min-width: 0; }

        .chk { width: 18px; height: 18px; border-radius: 6px; cursor: pointer; margin-top: 2px; flex: 0 0 auto; }

        .code { font-weight: 900; letter-spacing: .08em; color: var(--accent); font-size: .98rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }

        .meta-inline { margin-top: .28rem; font-size: .72rem; color: var(--muted); font-weight: 900; display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
        .meta-inline .dot { opacity: .6; }
        .meta-inline .truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 260px; display: inline-block; vertical-align: bottom; }

        @media(max-width:767.98px) { .meta-inline .truncate { max-width: 170px; } }

        .right-metrics { font-size: .78rem; color: var(--muted); font-weight: 900; white-space: nowrap; text-align: right; flex: 0 0 auto; }

        .cardx-b { padding: 10px 12px; display: grid; gap: .55rem; }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: .55rem; }

        .field label { display: block; font-size: .7rem; font-weight: 900; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; margin-bottom: .25rem; }

        .qty { text-align: center !important; font-weight: 900; padding: .55rem .55rem !important; border-radius: 999px; }
        .qty.ok { border: 1px solid rgba(22, 163, 74, .22); background: rgba(22, 163, 74, .05); }
        .qty.rj { border: 1px solid rgba(185, 28, 28, .22); background: rgba(185, 28, 28, .05); }
        .qty:focus { box-shadow: none; }

        .notes { display: none; }
        .notes.is-show { display: block; }
        .notes input { border-radius: 12px; }

        .fab-wrap {
            position: fixed; right: 14px; bottom: var(--fab-bottom);
            z-index: 1090; display: flex; gap: 10px; align-items: center; pointer-events: none;
        }
        .fab-wrap .btn { pointer-events: auto; border-radius: 999px; font-weight: 900; box-shadow: 0 12px 26px rgba(15, 23, 42, .22), 0 4px 10px rgba(15, 23, 42, .14); }
        .fab-back { width: 46px; padding-left: 0; padding-right: 0; }
        .fab-save { width: auto; padding: .62rem 1.05rem; white-space: nowrap; }

        @media(max-width:767.98px) {
            .fab-wrap { transition: transform .15s ease, opacity .15s ease; transform: translateY(0); opacity: 1; }
            body.keyboard-open .fab-wrap { bottom: calc(var(--fab-bottom) + var(--vv-kbd)); }
            .fab-wrap.is-hidden { opacity: 0; transform: translateY(10px); pointer-events: none; }
            body.keyboard-open .fab-wrap .btn { box-shadow: none; }
        }

        .modal { z-index: 3000 !important; }
        .modal-backdrop { z-index: 2990 !important; }

        .top-actions { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .pill { border: 1px solid rgba(148, 163, 184, .22); background: rgba(148, 163, 184, .06); border-radius: 999px; padding: .35rem .6rem; font-weight: 900; font-size: .78rem; color: var(--muted); }
        .btn-mini { border-radius: 999px; font-weight: 900; padding: .35rem .6rem; }

        .sum-box { border: 1px solid rgba(148, 163, 184, .18); border-radius: 14px; padding: 10px 12px; background: rgba(148, 163, 184, .06); }
        body[data-theme="dark"] .sum-box { background: rgba(15, 23, 42, .25); }

        .sum-top { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; flex-wrap: wrap; }
        .sum-top .ttl { font-weight: 900; }
        .sum-top .sub { color: var(--muted); font-weight: 900; font-size: .82rem; }

        .sum-pillrow { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .5rem; margin-top: .65rem; }

        .sum-pill { border: 1px solid rgba(148, 163, 184, .18); border-radius: 999px; padding: .35rem .6rem; text-align: center; font-weight: 900; font-size: .82rem; background: rgba(255, 255, 255, .55); }
        body[data-theme="dark"] .sum-pill { background: rgba(15, 23, 42, .15); }
        .sum-pill .lbl { display: block; font-size: .7rem; color: var(--muted); font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .sum-pill .val { display: block; margin-top: .12rem; }

        .acc-op-btn { font-weight: 900; padding: .7rem .85rem; }
        .acc-pill { display: inline-flex; align-items: center; gap: .35rem; padding: .18rem .55rem; border-radius: 999px; border: 1px solid rgba(148, 163, 184, .18); background: rgba(148, 163, 184, .06); font-weight: 900; font-size: .78rem; white-space: nowrap; }
        body[data-theme="dark"] .acc-pill { background: rgba(15, 23, 42, .22); }
        .acc-sub { font-size: .78rem; font-weight: 900; color: var(--muted); }
    </style>
@endpush

@section('content')
@php
    use Carbon\Carbon;

    $dateValue = old('date', now()->toDateString());
    $selectedOperatorId = (string) (request('operator_id', old('operator_id', $operatorId ?? '')) ?? '');
    $selectedPickupDate = (string) (request('pickup_date', '') ?? '');

    $isAllMode = $selectedOperatorId === '' || $selectedOperatorId === '0';

    $isRejectReworkMode = (bool) ($isRejectReworkMode ?? false);
    $lines = $lines ?? collect();
    $hasRejectRows = $lines->contains(fn($line) => (int) ($line->source_reject_return_line_id ?? 0) > 0 || (int) ($line->source_finishing_job_line_id ?? 0) > 0);
    $pageTitle = $isRejectReworkMode ? 'Setor Ulang Reject Jahit' : 'Sewing Return';
    $sourceStockLabel = $isRejectReworkMode ? 'REJ-SEW' : ($hasRejectRows ? 'STOK' : 'WIP');
    $remainingLabel = $isRejectReworkMode ? 'SISA REJECT' : ($hasRejectRows ? 'SISA' : 'BELUM');

    $itemOptions = $lines
        ->map(fn($l) => strtoupper(optional($l->finishedItem)->code ?? ''))
        ->filter()
        ->unique()
        ->sort()
        ->values();

    $pickupDateOptions = $lines
        ->map(fn($l) => optional($l->sewingPickup)->date)
        ->filter()
        ->map(fn($d) => Carbon::parse($d)->toDateString())
        ->unique()
        ->sortDesc()
        ->values();

    $pickupDateLabel = function ($d) {
        try { return Carbon::parse($d)->locale('id')->translatedFormat('l, d M Y'); }
        catch (\Throwable $e) { return $d; }
    };

    // controller mengirim:
    // - $destinationWarehouses (collection)
    // - $defaultDestWarehouseId (int)
    // - $canChooseDestination (bool)
    $destinationWarehouses = $destinationWarehouses ?? collect();
    $defaultDestWarehouseId = (int) ($defaultDestWarehouseId ?? 0);

    $selectedDestId = (int) old('destination_warehouse_id', $defaultDestWarehouseId);
    $canChooseDestination = (bool) ($canChooseDestination ?? false);

    // ===== ALL MODE: build accordion per item -> operator breakdown (sum remaining)
    $groupsAll = collect();
    if ($isAllMode && $lines->isNotEmpty()) {
        $groupsAll = $lines
            ->map(function ($l) {
                $itemCode = strtoupper(optional($l->finishedItem)->code ?? 'ITEM-' . (int) ($l->finished_item_id ?? 0));

                $pickup = $l->sewingPickup;
                $opCode = $pickup?->operator?->code ?? null;
                $opName = $pickup?->operator?->name ?? null;
                $opId = (int) ($pickup?->operator_id ?? 0);
                $opLabel = trim(($opCode ? $opCode . ' — ' : '') . ($opName ?? ''));
                if ($opLabel === '' && $opId > 0) $opLabel = 'OP-' . $opId;

                $remaining = (float) ($l->remaining_qty ?? 0);
                $wip = (float) ($l->wip_stock ?? 0);

                return [
                    'item_code' => $itemCode,
                    'operator_id' => $opId,
                    'operator_label' => $opLabel,
                    'remaining' => $remaining,
                    'wip' => $wip,
                    'pickup_date' => $pickup?->date ? Carbon::parse($pickup?->date)->toDateString() : '',
                ];
            })
            ->filter(fn($x) => (float) $x['remaining'] > 0.000001)
            ->groupBy('item_code')
            ->map(function ($rows, $itemCode) {
                $rows = collect($rows);

                $ops = $rows
                    ->groupBy(fn($r) => $r['operator_label'] ?: 'OP-' . (int) $r['operator_id'])
                    ->map(function ($opRows, $label) {
                        $opRows = collect($opRows);
                        return [
                            'label' => $label,
                            'operator_id' => (int) ($opRows->first()['operator_id'] ?? 0),
                            'remaining_sum' => (float) $opRows->sum('remaining'),
                            'lines_count' => (int) $opRows->count(),
                        ];
                    })
                    ->values()
                    ->sortBy('label')
                    ->values();

                return [
                    'item_code' => $itemCode,
                    'op_count' => (int) ($ops->count()),
                    'remaining_sum' => (float) $rows->sum('remaining'),
                    'wip' => (float) $rows->max('wip'),
                    'pickup_dates' => $rows->pluck('pickup_date')->filter()->unique()->sortDesc()->values(),
                    'ops' => $ops,
                ];
            })
            ->sortKeys()
            ->values();
    }

    $summaryItems = $groupsAll->count();
    $summaryRemaining = (float) $groupsAll->sum('remaining_sum');
    $summaryOps = $groupsAll->flatMap(fn($g) => collect($g['ops'])->pluck('operator_id'))->filter(fn($id) => (int) $id > 0)->unique()->count();
@endphp

<div class="page-wrap">

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <strong>Oops!</strong> Ada error input, cek form di bawah.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="panel mb-2">
        <div class="panel-h">
            <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                <div>
                    <div class="h-title">{{ $pageTitle }}</div>
                    @if ($isRejectReworkMode)
                        <div class="text-muted small mt-1">Sumber stok dari REJ-SEW, lalu disetor ulang ke gudang tujuan.</div>
                    @endif
                </div>

                <a href="{{ $isRejectReworkMode ? route('production.sewing.reject_returns.index') : route('production.sewing.pickups.create') }}"
                   class="btn btn-sm btn-outline-success"
                   style="border-radius:999px;">
                    {{ $isRejectReworkMode ? 'List Reject Jahit' : 'Sewing Pickup' }}
                </a>
            </div>
        </div>
    </div>

    <div class="panel">
        <form id="sewing-return-form" action="{{ route('production.sewing.returns.store') }}" method="POST" novalidate>
            @csrf

            <div class="panel-b">

                <div class="meta">
                    <div class="row g-2 align-items-end">

                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm">Tanggal Return</label>
                            <input type="date" name="date"
                                   class="form-control form-control-sm @error('date') is-invalid @enderror"
                                   value="{{ $dateValue }}">
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6 col-md-4">
                            <label class="form-label form-label-sm">Operator</label>
                            <select id="operator" name="operator_id"
                                    class="form-select form-select-sm @error('operator_id') is-invalid @enderror">
                                <option value="">SEMUA</option>
                                @foreach ($operators as $op)
                                    <option value="{{ $op->id }}" @selected((string) $selectedOperatorId === (string) $op->id)>
                                        {{ $op->code ? $op->code . ' — ' : '' }}{{ $op->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('operator_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6 col-md-3" id="dest-wrap">
                            <label class="form-label form-label-sm">Masuk Ke</label>
                            <input type="hidden" id="destination" name="destination_warehouse_id" value="{{ (int) $defaultDestWarehouseId }}"
                                   data-label="{{ optional($destinationWarehouses->firstWhere('id', $defaultDestWarehouseId))->code ?? 'WIP-FIN' }} — {{ optional($destinationWarehouses->firstWhere('id', $defaultDestWarehouseId))->name ?? 'Sedang Finishing' }}">
                            <div class="form-control form-control-sm mono" style="border-radius:12px; font-weight:900;">
                                {{ optional($destinationWarehouses->firstWhere('id', $defaultDestWarehouseId))->code ?? 'WIP-FIN' }}
                                —
                                {{ optional($destinationWarehouses->firstWhere('id', $defaultDestWarehouseId))->name ?? 'Sedang Finishing' }}
                            </div>
                            @error('destination_warehouse_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label form-label-sm">Filter item</label>
                            <select id="item-filter" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                @foreach ($itemOptions as $code)
                                    <option value="{{ $code }}">{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label form-label-sm">Tanggal Pickup (opsional)</label>
                            <select id="pickup-date" class="form-select form-select-sm">
                                <option value="">Semua tanggal</option>
                                @foreach ($pickupDateOptions as $d)
                                    <option value="{{ $d }}" @selected($selectedPickupDate === $d)>
                                        {{ $pickupDateLabel($d) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-2">
                            <label class="form-label form-label-sm">Cari</label>
                            <input type="text" id="q" class="form-control form-control-sm mono"
                                   placeholder="Kode..." autocomplete="off">
                        </div>
                    </div>

                    <div class="top-actions mt-2" id="top-actions-input" style="{{ $isAllMode ? 'display:none;' : '' }}">
                        <div class="d-flex gap-2 flex-wrap align-items-center">
                            <span class="pill">Total Iket: <span class="mono" id="stat-total-rows">0</span></span>
                            <span class="pill">Di Setor: <span class="mono" id="stat-picked-rows">0</span></span>
                            <span class="pill">{{ $isRejectReworkMode ? 'Total Setor' : 'Total OK' }}: <span class="mono" id="stat-total-ok">0,00</span></span>
                        </div>

                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-mini" id="btn-check-visible">
                                Pilih semua (tampil)
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-mini" id="btn-uncheck-all">
                                Reset semua
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ===== ALL MODE (SEMUA) : ACCORDION ===== --}}
                <div class="list" id="list-all" style="{{ $isAllMode ? '' : 'display:none;' }}">
                    @if ($lines->isEmpty() || $groupsAll->isEmpty())
                        <div class="text-center py-4 text-muted">Tidak ada baris yang bisa disetor.</div>
                    @else
                        <div class="sum-box mb-2">
                            <div class="sum-top">
                                <div><div class="ttl">{{ $isRejectReworkMode ? 'Summary (Reject Siap Setor)' : 'Summary (Belum Setor)' }}</div></div>
                                <div class="text-end">
                                    <div class="sub">Update: <span class="mono">{{ now()->format('H:i') }}</span></div>
                                </div>
                            </div>
                            <div class="sum-pillrow">
                                <div class="sum-pill"><span class="lbl">Item</span><span class="val mono">{{ number_format($summaryItems, 0, ',', '.') }}</span></div>
                                <div class="sum-pill"><span class="lbl">{{ $isRejectReworkMode ? 'Total Reject' : 'Total Belum' }}</span><span class="val mono">{{ number_format($summaryRemaining, 2, ',', '.') }}</span></div>
                                <div class="sum-pill"><span class="lbl">Operator</span><span class="val mono">{{ number_format($summaryOps, 0, ',', '.') }}</span></div>
                            </div>
                        </div>

                        <div class="accordion" id="all-items-accordion">
                            @php $no = 0; @endphp
                            @foreach ($groupsAll as $gidx => $g)
                                @php
                                    $no++;
                                    $itemCode = $g['item_code'];
                                    $opCount = (int) ($g['op_count'] ?? 0);
                                    $remainingSum = (float) ($g['remaining_sum'] ?? 0);
                                    $wip = (float) ($g['wip'] ?? 0);
                                    $ops = collect($g['ops'] ?? []);
                                    $collapseId = 'allItemCollapse-' . $gidx;
                                    $headingId = 'allItemHeading-' . $gidx;
                                @endphp

                                <div class="accordion-item acc-item" data-code="{{ $itemCode }}" data-item="{{ $itemCode }}"
                                     style="border-radius:16px; overflow:hidden; border:1px solid rgba(148,163,184,.18); background:var(--card); margin-bottom:.55rem;">
                                    <h2 class="accordion-header" id="{{ $headingId }}">
                                        <button class="accordion-button collapsed acc-op-btn" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                                                aria-expanded="false" aria-controls="{{ $collapseId }}">
                                            <div class="d-flex w-100 justify-content-between align-items-center gap-2 flex-wrap">
                                                <div class="d-flex align-items-center gap-2 min-w-0">
                                                    <span class="acc-pill"><span class="mono">{{ $no }}</span></span>
                                                    <div class="mono text-truncate" style="font-weight:900; max-width: 62vw;">{{ $itemCode }}</div>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="acc-pill">OP <span class="mono">{{ number_format($opCount, 0, ',', '.') }}</span></span>
                                                    <span class="acc-pill">{{ $remainingLabel }} <span class="mono">{{ number_format($remainingSum, 2, ',', '.') }}</span></span>
                                                    <span class="acc-pill">{{ $sourceStockLabel }} <span class="mono">{{ number_format($wip, 2, ',', '.') }}</span></span>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>

                                    <div id="{{ $collapseId }}" class="accordion-collapse collapse"
                                         aria-labelledby="{{ $headingId }}" data-bs-parent="#all-items-accordion">
                                        <div class="accordion-body" style="padding:.7rem .85rem;">
                                            @if ($ops->isEmpty())
                                                <div class="text-muted text-center py-2">Tidak ada detail operator.</div>
                                            @else
                                                <div class="table-responsive">
                                                    <table class="table table-sm align-middle mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th style="width:56px;">No</th>
                                                                <th>Operator</th>
                                                                <th class="text-end" style="width:170px;">{{ $isRejectReworkMode ? 'Sisa Reject' : 'Belum' }}</th>
                                                                <th class="text-end" style="width:110px;">Iket</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($ops as $i => $op)
                                                                <tr>
                                                                    <td class="mono">{{ $i + 1 }}</td>
                                                                    <td class="mono" style="font-weight:900;">
                                                                        {{ $op['label'] ?: 'OP-' . $op['operator_id'] }}
                                                                    </td>
                                                                    <td class="text-end mono" style="font-weight:900;">
                                                                        {{ number_format((float) $op['remaining_sum'], 2, ',', '.') }}
                                                                    </td>
                                                                    <td class="text-end mono" style="font-weight:900;">
                                                                        {{ number_format((int) $op['lines_count'], 0, ',', '.') }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="mt-2 acc-sub">
                                                    Total item: <span class="mono">{{ number_format($remainingSum, 2, ',', '.') }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- ===== BYOP MODE (operator dipilih): LIST INPUT ===== --}}
                <div class="list" id="list-byop" style="{{ $isAllMode ? 'display:none;' : '' }}">
                    @if ($lines->isEmpty())
                        <div class="text-center py-4 text-muted">Tidak ada baris yang bisa disetor.</div>
                    @else
                        @foreach ($lines as $idx => $line)
                            @php
                                $item = $line->finishedItem;
                                $pickup = $line->sewingPickup;

                                $code = strtoupper($item?->code ?? 'ITEM-' . $line->finished_item_id);
                                $pickupDateRaw = $pickup?->date ? Carbon::parse($pickup->date)->toDateString() : '';
                                $pickupDateText = $pickup?->date ? Carbon::parse($pickup->date)->locale('id')->translatedFormat('D, d M') : '-';

                                $opCode = $pickup?->operator?->code ?? null;
                                $opName = $pickup?->operator?->name ?? null;
                                $opLabel = trim(($opCode ? $opCode . ' — ' : '') . ($opName ?? ''));

                                $remaining = (float) ($line->remaining_qty ?? 0);
                                $wip = (float) ($line->wip_stock ?? 0);

                                $oldRow = old("results.$idx", []);
                                $okVal = $oldRow['qty_ok'] ?? '';
                                $rjVal = $oldRow['qty_reject'] ?? '';
                                $notes = $oldRow['notes'] ?? '';
                                $showNotes = (float) ($rjVal ?: 0) > 0 || trim((string) $notes) !== '';
                            @endphp

                            <div class="cardx mono fin-item"
                                 data-code="{{ $code }}"
                                 data-item="{{ $code }}"
                                 data-pickupdate="{{ $pickupDateRaw }}"
                                 data-remaining="{{ $remaining }}"
                                 data-wip="{{ $wip }}">
                                <div class="cardx-h">
                                    <div class="cardx-left">
                                        <input type="checkbox" class="chk row-check" aria-label="Pilih baris">
                                        <div>
                                            <div class="code">{{ $code }}</div>
                                            <div class="meta-inline">
                                                <span class="dot">•</span>
                                                <span>{{ $pickupDateText }}</span>
                                                @if ($opLabel !== '')
                                                    <span class="dot">•</span>
                                                    <span class="truncate" title="{{ $opLabel }}">OP: {{ $opLabel }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="right-metrics">
                                        {{ $remainingLabel }} {{ number_format($remaining, 2, ',', '.') }}<br>
                                        {{ $sourceStockLabel }} {{ number_format($wip, 2, ',', '.') }}
                                    </div>
                                </div>

                                <div class="cardx-b">
                                    <div class="grid2">
                                        <div class="field">
                                            <label>{{ $isRejectReworkMode ? 'Setor Ulang' : 'Di setor' }}</label>
                                            <input type="number" step="0.01" min="0" inputmode="decimal"
                                                   class="form-control form-control-sm qty ok num-input select-all-on-focus"
                                                   name="results[{{ $idx }}][qty_ok]"
                                                   value="{{ $okVal }}" placeholder="0">
                                        </div>

                                        @if ($isRejectReworkMode)
                                            <input type="hidden" name="results[{{ $idx }}][qty_reject]" value="0">
                                            <div class="field">
                                                <label>Sumber</label>
                                                <div class="form-control form-control-sm mono" style="border-radius:999px; font-weight:900; text-align:center;">
                                                    {{ $line->reject_code ?? 'REJ-SEW' }}
                                                </div>
                                            </div>
                                        @else
                                            <div class="field">
                                                <label>Reject</label>
                                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                                       class="form-control form-control-sm qty rj num-input select-all-on-focus"
                                                       name="results[{{ $idx }}][qty_reject]"
                                                       value="{{ $rjVal }}" placeholder="0">
                                            </div>
                                        @endif
                                    </div>

                                    <div class="notes {{ (!$isRejectReworkMode && $showNotes) ? 'is-show' : '' }}">
                                        <input type="text" class="form-control form-control-sm"
                                               name="results[{{ $idx }}][notes]"
                                               placeholder="Catatan reject (opsional)" value="{{ $notes }}">
                                    </div>

                                    <input type="hidden" name="results[{{ $idx }}][sewing_pickup_line_id]" value="{{ $line->id }}">
                                    @if ($isRejectReworkMode)
                                        @if ((int) ($line->source_reject_return_line_id ?? 0) > 0)
                                            <input type="hidden" name="results[{{ $idx }}][source_reject_return_line_id]" value="{{ (int) $line->source_reject_return_line_id }}">
                                        @endif
                                        @if ((int) ($line->source_finishing_job_line_id ?? 0) > 0)
                                            <input type="hidden" name="results[{{ $idx }}][source_finishing_job_line_id]" value="{{ (int) $line->source_finishing_job_line_id }}">
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- FAB (hanya mode input) --}}
                <div class="fab-wrap" id="fab-wrap" style="{{ $isAllMode ? 'display:none;' : '' }}">
                    <a href="{{ route('production.sewing.returns.index') }}"
                       class="btn btn-sm btn-outline-secondary fab-back">←</a>
                    <button type="button" class="btn btn-sm btn-success fab-save" id="btn-open-modal" disabled>
                        Simpan
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

{{-- MODAL CONFIRM --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Simpan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="p-3 border bg-light" style="border-radius:14px;">
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div style="font-weight:900;">Ringkasan</div>
                        <div class="text-muted" style="font-weight:800;font-size:.86rem;">
                            Baris terisi: <span class="mono" id="m-rows">0</span>
                        </div>
                    </div>

                    <div class="mt-2" style="font-size:.90rem;font-weight:800;color:var(--muted);">
                        Operator: <span class="mono" id="m-op">SEMUA</span><br>
                        Tujuan: <span class="mono" id="m-dest">-</span><br>
                        Total OK: <span class="mono" id="m-ok">0,00</span>
                        • Total Reject: <span class="mono" id="m-rj">0,00</span>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div style="font-weight:900;">Detail Setor</div>
                        <div class="text-muted" style="font-weight:800;font-size:.86rem;">
                            Item: <span class="mono" id="m-items-count">0</span>
                        </div>
                    </div>

                    <div class="border" style="border-radius:14px; overflow:hidden;">
                        <div class="px-3 py-2"
                             style="background:rgba(148,163,184,.06); border-bottom:1px solid rgba(148,163,184,.18); font-size:.72rem; font-weight:900; color:var(--muted); text-transform:uppercase; letter-spacing:.10em;">
                            <div class="d-grid" style="grid-template-columns: 44px 1fr 120px 120px; gap:.5rem; align-items:center;">
                                <div>No</div><div>Item</div><div class="text-end">OK / Setor</div><div class="text-end">Reject</div>
                            </div>
                        </div>

                        <div id="m-items" style="max-height:40vh; overflow:auto; -webkit-overflow-scrolling:touch;">
                            <div class="text-center text-muted py-3" id="m-empty">Tidak ada item.</div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-none" id="modal-fallback-note">
                    <div class="alert alert-warning mb-0">
                        Modal tidak bisa ditampilkan karena Bootstrap JS belum ter-load (bundle). Tombol <b>Simpan</b> akan submit langsung.
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm btn-success" id="btn-confirm-submit">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('sewing-return-form');

    const listAll = document.getElementById('list-all');
    const listByOp = document.getElementById('list-byop');

    const operator = document.getElementById('operator');
    const pickupDate = document.getElementById('pickup-date');
    const itemFilter = document.getElementById('item-filter');
    const q = document.getElementById('q');

    // ✅ fixed: sekarang id-nya beneran ada
    const destWrap = document.getElementById('dest-wrap');
    const destination = document.getElementById('destination');

    const topActionsInput = document.getElementById('top-actions-input');
    const fabWrap = document.getElementById('fab-wrap');

    const btnOpenModal = document.getElementById('btn-open-modal');
    const modalEl = document.getElementById('confirmModal');
    const btnConfirm = document.getElementById('btn-confirm-submit');

    const btnCheckVisible = document.getElementById('btn-check-visible');
    const btnUncheckAll = document.getElementById('btn-uncheck-all');

    const mOp = document.getElementById('m-op');
    const mDest = document.getElementById('m-dest');
    const mOk = document.getElementById('m-ok');
    const mRj = document.getElementById('m-rj');
    const mRows = document.getElementById('m-rows');
    const mItemsCount = document.getElementById('m-items-count');
    const mItems = document.getElementById('m-items');
    const mEmpty = document.getElementById('m-empty');

    const statTotalRows = document.getElementById('stat-total-rows');
    const statPickedRows = document.getElementById('stat-picked-rows');
    const statTotalOk = document.getElementById('stat-total-ok');

    const fallbackNote = document.getElementById('modal-fallback-note');

    const body = document.body;
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
    const isAllMode = () => !((operator?.value || '').toString().trim());

    (function initVV() {
        if (!window.visualViewport) return;
        const vv = window.visualViewport;
        const set = () => {
            const kbd = Math.max(0, (window.innerHeight - vv.height - vv.offsetTop));
            document.documentElement.style.setProperty('--vv-kbd', `${kbd}px`);
        };
        vv.addEventListener('resize', set);
        vv.addEventListener('scroll', set);
        set();
    })();

    function reloadWithFilters() {
        const url = new URL(window.location.href);
        const op = (operator?.value || '').toString();
        const pd = (pickupDate?.value || '').toString();

        if (op) url.searchParams.set('operator_id', op);
        else url.searchParams.delete('operator_id');

        if (pd) url.searchParams.set('pickup_date', pd);
        else url.searchParams.delete('pickup_date');

        window.location.href = url.toString();
    }

    operator?.addEventListener('change', reloadWithFilters);
    pickupDate?.addEventListener('change', reloadWithFilters);

    function sanitizeNum(v) {
        v = (v ?? '').toString().trim();
        if (v === '') return '';
        const n = parseFloat(v);
        if (Number.isNaN(n) || n < 0) return '';
        return String(n);
    }

    function getEls(card) {
        return {
            ok: card.querySelector('input[name*="[qty_ok]"]'),
            rj: card.querySelector('input[name*="[qty_reject]"]'),
            notesWrap: card.querySelector('.notes'),
            cb: card.querySelector('.row-check'),
        };
    }

    function clampCard(card, changed) {
        const rem = parseFloat(card.dataset.remaining || '0') || 0;
        const wip = parseFloat(card.dataset.wip || '0') || 0;
        const { ok, rj } = getEls(card);

        let a = parseFloat(ok?.value || '0'); if (!Number.isFinite(a) || a < 0) a = 0;
        let b = parseFloat(rj?.value || '0'); if (!Number.isFinite(b) || b < 0) b = 0;

        if (a + b > rem) {
            const diff = (a + b) - rem;
            if (changed === 'rj') b = Math.max(0, b - diff);
            else a = Math.max(0, a - diff);
        }

        if (a + b > wip) {
            const diff2 = (a + b) - wip;
            if (changed === 'rj') b = Math.max(0, b - diff2);
            else a = Math.max(0, a - diff2);
        }

        if (ok) ok.value = (a <= 0) ? '' : String(a);
        if (rj) rj.value = (b <= 0) ? '' : String(b);
    }

    function syncNotes(card) {
        const { rj, notesWrap } = getEls(card);
        if (!notesWrap) return;
        const v = parseFloat(rj?.value || '0') || 0;
        if (v > 0) notesWrap.classList.add('is-show');
        else notesWrap.classList.remove('is-show');
    }

    function syncCheck(card) {
        const { ok, rj, cb } = getEls(card);
        const a = parseFloat(ok?.value || '0') || 0;
        const b = parseFloat(rj?.value || '0') || 0;
        if (cb) cb.checked = ((a + b) > 0);
    }

    function autoFillCard(card) {
        const rem = parseFloat(card.dataset.remaining || '0') || 0;
        const wip = parseFloat(card.dataset.wip || '0') || 0;
        const fill = Math.max(0, Math.min(rem, wip));

        const { ok, rj } = getEls(card);
        if (ok) ok.value = (fill > 0) ? String(fill) : '';
        if (rj) rj.value = '';
        clampCard(card, 'ok');
        syncNotes(card);
        syncCheck(card);
    }

    function setModeUI() {
        const all = isAllMode();

        if (listAll) listAll.style.display = all ? '' : 'none';
        if (listByOp) listByOp.style.display = all ? 'none' : '';

        if (topActionsInput) topActionsInput.style.display = all ? 'none' : '';
        if (fabWrap) fabWrap.style.display = all ? 'none' : '';

        // tujuan hanya relevan untuk mode input
        if (destWrap) destWrap.style.display = all ? 'none' : (destWrap.style.display || '');

        if (btnOpenModal) btnOpenModal.disabled = all ? true : btnOpenModal.disabled;
    }

    function computeSubmitEnabled() {
        if (!btnOpenModal || !listByOp) return 0;

        if (isAllMode()) { btnOpenModal.disabled = true; return 0; }

        // tujuan wajib ada nilainya (select owner / hidden input non-owner)
        // kalau owner: select exist
        if (destination && destination.value === '') { btnOpenModal.disabled = true; return 0; }

        let total = 0;
        $$('.fin-item', listByOp).forEach(card => {
            if (card.style.display === 'none') return;
            const { ok, rj } = getEls(card);
            const a = parseFloat(ok?.value || '0') || 0;
            const b = parseFloat(rj?.value || '0') || 0;
            total += (a + b);
        });

        btnOpenModal.disabled = total <= 0;
        return total;
    }

    function computeTopSummary() {
        if (isAllMode() || !listByOp) return;

        let totalRows = 0;
        let pickedRows = 0;
        let totalOk = 0;

        $$('.fin-item', listByOp).forEach(card => {
            if (card.style.display === 'none') return;
            totalRows++;

            const ok = parseFloat(card.querySelector('input[name*="[qty_ok]"]')?.value || '0') || 0;
            const rj = parseFloat(card.querySelector('input[name*="[qty_reject]"]')?.value || '0') || 0;

            if ((ok + rj) > 0) {
                pickedRows++;
                totalOk += ok;
            }
        });

        if (statTotalRows) statTotalRows.textContent = totalRows.toLocaleString('id-ID');
        if (statPickedRows) statPickedRows.textContent = pickedRows.toLocaleString('id-ID');
        if (statTotalOk) statTotalOk.textContent = totalOk.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function applyFilter() {
        const term = (q?.value || '').toString().trim().toUpperCase();
        const selItem = (itemFilter?.value || '').toString().trim().toUpperCase();

        if (isAllMode()) {
            if (!listAll) return;
            $$('.acc-item', listAll).forEach(card => {
                const code = (card.dataset.code || '').toString().toUpperCase();
                const item = (card.dataset.item || '').toString().toUpperCase();
                const matchSearch = !term || code.includes(term);
                const matchItem = !selItem || item === selItem;
                card.style.display = (matchSearch && matchItem) ? '' : 'none';
            });
            return;
        }

        if (!listByOp) return;
        $$('.fin-item', listByOp).forEach(card => {
            const code = (card.dataset.code || '').toString().toUpperCase();
            const item = (card.dataset.item || '').toString().toUpperCase();
            const pd = (card.dataset.pickupdate || '').toString();
            const selPd = (pickupDate?.value || '').toString();

            const matchSearch = !term || code.includes(term);
            const matchItem = !selItem || item === selItem;
            const matchDate = !selPd || pd === selPd;

            card.style.display = (matchSearch && matchItem && matchDate) ? '' : 'none';
        });

        computeSubmitEnabled();
        computeTopSummary();
    }

    form?.addEventListener('input', (e) => {
        if (isAllMode()) return;

        const t = e.target;
        if (!t.classList?.contains('num-input')) return;

        t.value = sanitizeNum(t.value);

        const card = t.closest('.fin-item');
        if (!card) return;

        const changed = (t.name || '').includes('[qty_reject]') ? 'rj' : 'ok';
        clampCard(card, changed);
        syncCheck(card);
        syncNotes(card);

        computeSubmitEnabled();
        computeTopSummary();
    });

    form?.addEventListener('change', (e) => {
        if (isAllMode()) return;

        const t = e.target;

        if (t === destination) {
            computeSubmitEnabled();
            return;
        }

        if (!t.classList?.contains('row-check')) return;

        const card = t.closest('.fin-item');
        if (!card) return;

        const { ok, rj } = getEls(card);

        if (t.checked) {
            const a = parseFloat(ok?.value || '0') || 0;
            const b = parseFloat(rj?.value || '0') || 0;
            if ((a + b) <= 0) autoFillCard(card);
        } else {
            if (ok) ok.value = '';
            if (rj) rj.value = '';
            syncNotes(card);
        }

        computeSubmitEnabled();
        computeTopSummary();
    });

    q?.addEventListener('input', () => {
        const up = (q.value || '').toString().toUpperCase();
        if (q.value !== up) q.value = up;
        applyFilter();
    });

    itemFilter?.addEventListener('change', applyFilter);

    form?.addEventListener('focusin', (e) => {
        const t = e.target;
        if (t?.classList?.contains('select-all-on-focus')) {
            setTimeout(() => { try { t.select(); } catch (_) {} }, 0);
        }
        if (window.innerWidth < 768) body.classList.add('keyboard-open');
    });
    form?.addEventListener('focusout', () => body.classList.remove('keyboard-open'));

    btnCheckVisible?.addEventListener('click', () => {
        if (isAllMode() || !listByOp) return;
        $$('.fin-item', listByOp).forEach(card => {
            if (card.style.display === 'none') return;
            const { cb } = getEls(card);
            if (!cb) return;
            cb.checked = true;
            autoFillCard(card);
        });
        computeSubmitEnabled();
        computeTopSummary();
        applyFilter();
    });

    btnUncheckAll?.addEventListener('click', () => {
        if (isAllMode() || !listByOp) return;
        $$('.fin-item', listByOp).forEach(card => {
            const { cb, ok, rj } = getEls(card);
            if (cb) cb.checked = false;
            if (ok) ok.value = '';
            if (rj) rj.value = '';
            syncNotes(card);
        });
        computeSubmitEnabled();
        computeTopSummary();
        applyFilter();
    });

    // ===== Modal =====
    let bsModal = null;
    const hasBootstrap = (typeof window.bootstrap !== 'undefined' && typeof window.bootstrap.Modal !== 'undefined');

    if (modalEl && hasBootstrap) {
        bsModal = window.bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true, focus: true });

        modalEl.addEventListener('show.bs.modal', () => fabWrap?.classList.add('is-hidden'));
        modalEl.addEventListener('hidden.bs.modal', () => fabWrap?.classList.remove('is-hidden'));
    } else {
        if (fallbackNote) fallbackNote.classList.remove('d-none');
    }

    function esc(s) {
        return String(s ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function rebuildModalItems() {
        if (!mItems || !listByOp) return;
        mItems.innerHTML = '';

        let rows = 0, okSum = 0, rjSum = 0;
        const picked = [];

        $$('.fin-item', listByOp).forEach(card => {
            const { ok, rj } = getEls(card);
            const a = parseFloat(ok?.value || '0') || 0;
            const b = parseFloat(rj?.value || '0') || 0;
            if ((a + b) <= 0) return;

            rows++;
            okSum += a;
            rjSum += b;

            picked.push({ code: (card.dataset.code || '').toString(), ok: a, rj: b });
        });

        picked.sort((x, y) => (x.code || '').localeCompare(y.code || ''));

        if (mRows) mRows.textContent = rows.toLocaleString('id-ID');
        if (mOk) mOk.textContent = okSum.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (mRj) mRj.textContent = rjSum.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (mItemsCount) mItemsCount.textContent = picked.length.toLocaleString('id-ID');

        if (picked.length === 0) {
            if (mEmpty) { mItems.appendChild(mEmpty); mEmpty.style.display = ''; }
            return;
        }
        if (mEmpty) mEmpty.style.display = 'none';

        picked.forEach((it, i) => {
            const row = document.createElement('div');
            row.className = 'px-3 py-2';
            row.style.borderBottom = '1px solid rgba(148,163,184,.12)';
            row.innerHTML = `
                <div class="d-grid" style="grid-template-columns: 44px 1fr 120px 120px; gap:.5rem; align-items:center;">
                    <div class="text-muted" style="font-weight:900;">${i+1}</div>
                    <div style="font-weight:900;" class="mono">${esc(it.code)}</div>
                    <div class="text-end mono" style="font-weight:900;">${it.ok.toLocaleString('id-ID',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
                    <div class="text-end mono" style="font-weight:900; color: var(--rj);">${it.rj.toLocaleString('id-ID',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
                </div>
            `;
            mItems.appendChild(row);
        });
    }

    btnOpenModal?.addEventListener('click', (e) => {
        if (isAllMode()) return;
        if (btnOpenModal.disabled) return;

        e.preventDefault();
        e.stopPropagation();

        const opt = operator?.options?.[operator.selectedIndex];
        if (mOp) mOp.textContent = (operator?.value ? (opt ? opt.text : '-') : 'SEMUA');

        const destOpt = destination?.options?.[destination.selectedIndex];
        if (mDest) mDest.textContent = destination?.dataset?.label || (destOpt ? destOpt.text : '-');

        rebuildModalItems();

        if (!bsModal) { form.submit(); return; }

        try { document.activeElement?.blur?.(); } catch (_) {}
        requestAnimationFrame(() => { bsModal.show(); });
    });

    btnConfirm?.addEventListener('click', () => {
        try { bsModal?.hide(); } catch (_) {}
        form.submit();
    });

    // init
    setModeUI();

    if (listByOp) {
        $$('.fin-item', listByOp).forEach(card => {
            syncCheck(card);
            syncNotes(card);
        });
    }

    applyFilter();
    computeSubmitEnabled();
    computeTopSummary();
});
</script>
@endpush



{{-- SweetAlert existing error handler --}}
<script id="gf-sweetalert-existing-error">
document.addEventListener('DOMContentLoaded', function () {
    const errorBox = document.querySelector('.alert-danger, .alert.alert-danger, [data-error-alert]');

    if (!errorBox) return;

    const rawText = (errorBox.innerText || errorBox.textContent || '').trim();

    if (!rawText) return;

    const cleanText = rawText
        .replace('Terjadi error:', '')
        .replace('Oops! Ada error input, cek form di bawah.', '')
        .trim();

    const showMessage = cleanText || rawText;

    function showSweetAlert() {
        if (typeof Swal === 'undefined') {
            alert(showMessage);
            return;
        }

        Swal.fire({
            icon: 'error',
            title: 'Data belum bisa disimpan',
            html: showMessage + '<br><br><strong>Hubungi Owner</strong>',
            confirmButtonText: 'Mengerti',
            confirmButtonColor: '#dc2626'
        });
    }

    if (typeof Swal === 'undefined') {
        const cdn = document.createElement('script');
        cdn.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        cdn.onload = showSweetAlert;
        document.head.appendChild(cdn);
    } else {
        showSweetAlert();
    }

    errorBox.style.display = 'none';
});
</script>
