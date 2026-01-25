{{-- resources/views/production/finishing_jobs/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Finishing')

@push('head')
    <style>
        :root {
            --r: 14px;
            --b: rgba(148, 163, 184, .22);
            --muted: #6b7280;
            --soft2: rgba(148, 163, 184, .05);
            --accent: #2563eb;
            --ok: #16a34a;
            --rj: #b91c1c;
            --shadow: 0 10px 26px rgba(15, 23, 42, .08), 0 0 0 1px rgba(15, 23, 42, .03);
            --bottom-nav-h: 72px;
            --fab-gap: 12px;
            --fab-bottom: calc(var(--bottom-nav-h) + var(--fab-gap) + env(safe-area-inset-bottom));
        }

        .page-wrap {
            max-width: 980px;
            margin: 0 auto;
            padding: 14px 12px 96px;
        }

        @media(max-width:767.98px) {
            .page-wrap {
                padding-bottom: calc(var(--bottom-nav-h) + 130px + var(--vv-kbd));
            }

            body.keyboard-open .page-wrap {
                padding-bottom: calc(14rem + var(--vv-kbd));
            }
        }

        .panel {
            background: var(--card);
            border: 1px solid var(--b);
            border-radius: var(--r);
            box-shadow: var(--shadow);
        }

        .panel-h {
            padding: 12px 14px;
            border-bottom: 1px solid rgba(148, 163, 184, .12);
        }

        .panel-b {
            padding: 12px 14px;
        }

        .h-title {
            font-weight: 900;
            font-size: 1.05rem;
            margin: 0;
        }

        .meta {
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: var(--r);
            padding: 10px;
            background: var(--soft2);
        }

        body[data-theme="dark"] .meta {
            background: rgba(15, 23, 42, .35);
        }

        .form-label-sm {
            font-size: .75rem;
            font-weight: 800;
            color: var(--muted);
        }

        .form-control-sm,
        .form-select-sm {
            font-size: .88rem;
            padding: .42rem .55rem;
            border-radius: 12px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .list {
            display: grid;
            gap: .6rem;
            margin-top: 12px;
        }

        .cardx {
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 16px;
            background: var(--card);
            overflow: hidden;
        }

        .cardx-h {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(148, 163, 184, .12);
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: flex-start;
        }

        .cardx-left {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            min-width: 0;
        }

        .cardx-left>div {
            min-width: 0;
        }

        .chk {
            width: 18px;
            height: 18px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 2px;
            flex: 0 0 auto;
        }

        .code {
            font-weight: 900;
            letter-spacing: .08em;
            color: var(--accent);
            font-size: .98rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .op-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            margin-top: .35rem;
            padding: .18rem .5rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .22);
            font-size: .72rem;
            font-weight: 900;
            color: var(--muted);
            background: rgba(148, 163, 184, .04);
            white-space: nowrap;
        }

        body[data-theme="dark"] .op-chip {
            background: rgba(15, 23, 42, .18);
        }

        .meta-inline {
            margin-top: .28rem;
            font-size: .72rem;
            color: var(--muted);
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: .4rem;
            flex-wrap: nowrap;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .meta-inline .dot {
            opacity: .6;
        }

        .wip {
            font-size: .78rem;
            color: var(--muted);
            font-weight: 900;
            white-space: nowrap;
            text-align: right;
            flex: 0 0 auto;
        }

        .cardx-b {
            padding: 10px 12px;
            display: grid;
            gap: .55rem;
        }

        .grid2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .55rem;
        }

        .field label {
            display: block;
            font-size: .7rem;
            font-weight: 900;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: .25rem;
        }

        .qty {
            text-align: center !important;
            font-weight: 900;
            padding: .55rem .55rem !important;
            border-radius: 999px;
        }

        .qty.ok {
            border: 1px solid rgba(22, 163, 74, .22);
            background: rgba(22, 163, 74, .05);
        }

        .qty.rj {
            border: 1px solid rgba(185, 28, 28, .22);
            background: rgba(185, 28, 28, .05);
        }

        .qty:focus {
            box-shadow: none;
        }

        .notes {
            display: none;
        }

        .notes.is-show {
            display: block;
        }

        .notes input {
            border-radius: 12px;
        }

        .fab-wrap {
            position: fixed;
            right: 14px;
            bottom: var(--fab-bottom);
            z-index: 1090;
            display: flex;
            gap: 10px;
            align-items: center;
            pointer-events: none;
        }

        .fab-wrap .btn {
            pointer-events: auto;
            border-radius: 999px;
            font-weight: 900;
            box-shadow: 0 12px 26px rgba(15, 23, 42, .22), 0 4px 10px rgba(15, 23, 42, .14);
        }

        .fab-back {
            width: 46px;
            padding-left: 0;
            padding-right: 0;
        }

        .fab-save {
            width: auto;
            padding: .62rem 1.05rem;
            white-space: nowrap;
        }

        @media(max-width:767.98px) {
            body.keyboard-open .fab-wrap {
                position: static;
                margin-top: 14px;
                justify-content: flex-end;
                pointer-events: auto;
            }

            body.keyboard-open .fab-wrap .btn {
                box-shadow: none;
            }

            .modal-dialog {
                margin: .75rem;
            }

            .modal-content {
                border-radius: 16px;
            }

            .modal-body {
                max-height: calc(100vh - 210px);
                overflow: auto;
            }
        }

        #item-filter-wrap {
            scroll-margin-top: 84px;
        }

        .fin-item {
            scroll-margin-top: 84px;
        }
    </style>
@endpush

@section('content')
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

        @php
            $dateValue = old('date', $dateDefault ?? now()->toDateString());
            $defaultOperator = old('operator_global_id') ?? (auth()->user()->employee_id ?? '');

            $linesAll = $linesAll ?? [];
            $linesByOp = $linesByOp ?? [];

            $oldAll = old('lines_all', []);
            $oldByOp = old('lines_byop', []);

            // options hanya dari item yg wip > 0 (biar filter ga berisi yg udah habis)
            $itemOptionsBase = collect(array_merge($linesAll, $linesByOp))
                ->filter(fn($l) => (int) ($l['total_wip'] ?? 0) > 0)
                ->map(function ($l) {
                    $id = $l['item_id'] ?? null;
                    $code = strtoupper($l['item_code'] ?? 'ITEM-' . $id);
                    return ['id' => $id, 'code' => $code];
                })
                ->filter(fn($x) => !empty($x['id']))
                ->unique('id')
                ->sortBy('code')
                ->values();

            $operatorOptions = collect($linesByOp)
                ->filter(fn($l) => (int) ($l['total_wip'] ?? 0) > 0)
                ->map(function ($l) {
                    $id = $l['operator_id'] ?? null;
                    if (!$id) {
                        return null;
                    }

                    $code = $l['operator_code'] ?? null;
                    $name = $l['operator_name'] ?? null;
                    $label = trim(($code ? $code . ' — ' : '') . ($name ?? ''));

                    return [
                        'id' => (int) $id,
                        'label' => $label !== '' ? $label : 'OP-' . (int) $id,
                    ];
                })
                ->filter()
                ->unique('id')
                ->sortBy('label')
                ->values();

            // helper untuk cek ada data wip
            $hasAnyWipAll = collect($linesAll)->sum(fn($l) => (int) ($l['total_wip'] ?? 0)) > 0;
            $hasAnyWipByOp = collect($linesByOp)->sum(fn($l) => (int) ($l['total_wip'] ?? 0)) > 0;
        @endphp

        <div class="panel mb-2">
            <div class="panel-h">
                <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                    <div>
                        <div class="h-title">Finishing</div>
                    </div>
                    <a href="{{ route('production.finishing_jobs.index') }}" class="btn btn-sm btn-outline-primary"
                        style="border-radius:999px;">
                        Riwayat
                    </a>
                </div>
            </div>
        </div>

        <div class="panel">
            <form id="finishing-form" action="{{ route('production.finishing_jobs.store') }}" method="POST" novalidate>
                @csrf

                {{-- ✅ SINGLE SOURCE OF TRUTH --}}
                <input type="hidden" name="operator_mode" id="operator_mode" value="{{ old('operator_mode', 'all') }}">
                <input type="hidden" name="operator_global_id" id="operator_global_id_hidden"
                    value="{{ old('operator_global_id', $defaultOperator) }}">

                <div class="panel-b">

                    <div class="meta">
                        <div class="row g-2 align-items-end">
                            <div class="col-6 col-md-3">
                                <label class="form-label form-label-sm">Tanggal</label>
                                <input type="date" name="date"
                                    class="form-control form-control-sm @error('date') is-invalid @enderror"
                                    value="{{ $dateValue }}">
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label form-label-sm">Filter operator</label>
                                <select id="op-filter" class="form-select form-select-sm">
                                    <option value="">Semua (gabung)</option>
                                    @foreach ($operatorOptions as $opt)
                                        <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-3" id="item-filter-wrap">
                                <label class="form-label form-label-sm">Filter item</label>
                                <select id="item-filter" class="form-select form-select-sm">
                                    <option value="">Semua</option>
                                    @foreach ($itemOptionsBase as $opt)
                                        <option value="{{ $opt['id'] }}">{{ $opt['code'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label form-label-sm">Cari kode</label>
                                <input type="text" id="q" class="form-control form-control-sm mono"
                                    placeholder="Cari barang..." autocomplete="off">
                            </div>
                        </div>
                    </div>

                    {{-- LIST ALL --}}
                    <div class="list" id="list-all">
                        @if (!$hasAnyWipAll)
                            <div class="text-center py-4 text-muted">Tidak ada WIP-FIN.</div>
                        @else
                            @foreach ($linesAll as $idx => $line)
                                @php
                                    $oldLine = $oldAll[$idx] ?? [];

                                    $itemId = (int) ($oldLine['item_id'] ?? ($line['item_id'] ?? 0));
                                    $wip = (int) ($oldLine['total_wip'] ?? ($line['total_wip'] ?? 0));
                                    $code = strtoupper($line['item_code'] ?? 'ITEM-' . $itemId);

                                    // ✅ HILANGKAN YANG SUDAH HABIS / SUDAH KEPOST
                                    if ($wip <= 0) {
                                        continue;
                                    }

                                    $pickupRaw = $oldLine['pickup_date'] ?? ($line['pickup_date'] ?? null);
                                    $setorRaw = $oldLine['setor_date'] ?? ($line['setor_date'] ?? null);
                                    $pickupTxt = $pickupRaw ? \Carbon\Carbon::parse($pickupRaw)->format('d/m/Y') : '—';
                                    $setorTxt = $setorRaw ? \Carbon\Carbon::parse($setorRaw)->format('d/m/Y') : '—';

                                    $qtyIn = array_key_exists('qty_in', $oldLine)
                                        ? (is_null($oldLine['qty_in'])
                                            ? ''
                                            : (string) (int) $oldLine['qty_in'])
                                        : '';

                                    $qtyReject = array_key_exists('qty_reject', $oldLine)
                                        ? ((int) ($oldLine['qty_reject'] ?? 0) === 0
                                            ? ''
                                            : (string) (int) $oldLine['qty_reject'])
                                        : '';

                                    $rejectNotes = $oldLine['reject_notes'] ?? '';
                                @endphp

                                <div class="cardx mono fin-item" data-idx="{{ $idx }}"
                                    data-item-id="{{ $itemId }}" data-operator-id="0"
                                    data-code="{{ $code }}" data-wip="{{ $wip }}">

                                    <div class="cardx-h">
                                        <div class="cardx-left">
                                            <input type="checkbox" class="chk row-check">
                                            <div>
                                                <div class="code">{{ $code }}</div>
                                                <div class="meta-inline">
                                                    <span title="Terakhir ambil">📦 {{ $pickupTxt }}</span>
                                                    <span class="dot">•</span>
                                                    <span title="Terakhir setor">🧵 {{ $setorTxt }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="wip">SISA {{ number_format($wip, 0, ',', '.') }}</div>
                                    </div>

                                    <div class="cardx-b">
                                        <div class="grid2">
                                            <div class="field">
                                                <label>Setor</label>
                                                <input type="text" inputmode="numeric"
                                                    class="form-control form-control-sm qty ok integer-input select-all-on-focus"
                                                    name="lines_all[{{ $idx }}][qty_in]"
                                                    value="{{ $qtyIn }}" placeholder="0">
                                            </div>

                                            <div class="field">
                                                <label>Reject</label>
                                                <input type="text" inputmode="numeric"
                                                    class="form-control form-control-sm qty rj integer-input select-all-on-focus"
                                                    name="lines_all[{{ $idx }}][qty_reject]"
                                                    value="{{ $qtyReject }}" placeholder="0">
                                            </div>
                                        </div>

                                        <div class="notes">
                                            <input type="text" class="form-control form-control-sm"
                                                name="lines_all[{{ $idx }}][reject_notes]"
                                                placeholder="Catatan reject (opsional)" value="{{ $rejectNotes }}">
                                        </div>

                                        <input type="hidden" name="lines_all[{{ $idx }}][item_id]"
                                            value="{{ $itemId }}">
                                        <input type="hidden" name="lines_all[{{ $idx }}][total_wip]"
                                            value="{{ $wip }}">
                                        <input type="hidden" name="lines_all[{{ $idx }}][pickup_date]"
                                            value="{{ $pickupRaw }}">
                                        <input type="hidden" name="lines_all[{{ $idx }}][setor_date]"
                                            value="{{ $setorRaw }}">
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    {{-- LIST BYOP --}}
                    <div class="list" id="list-byop" style="display:none;">
                        @if (!$hasAnyWipByOp)
                            <div class="text-center py-4 text-muted">Tidak ada WIP-FIN.</div>
                        @else
                            @foreach ($linesByOp as $idx => $line)
                                @php
                                    $oldLine = $oldByOp[$idx] ?? [];

                                    $itemId = (int) ($oldLine['item_id'] ?? ($line['item_id'] ?? 0));
                                    $wip = (int) ($oldLine['total_wip'] ?? ($line['total_wip'] ?? 0));
                                    $code = strtoupper($line['item_code'] ?? 'ITEM-' . $itemId);

                                    // ✅ HILANGKAN YANG SUDAH HABIS / SUDAH KEPOST
                                    if ($wip <= 0) {
                                        continue;
                                    }

                                    $opId = (int) ($oldLine['operator_id'] ?? ($line['operator_id'] ?? 0));
                                    $opCode = $oldLine['operator_code'] ?? ($line['operator_code'] ?? null);
                                    $opName = $oldLine['operator_name'] ?? ($line['operator_name'] ?? null);
                                    $opLabel = trim(($opCode ? $opCode . ' — ' : '') . ($opName ?? ''));
                                    if ($opLabel === '' && $opId > 0) {
                                        $opLabel = 'OP-' . $opId;
                                    }

                                    $pickupRaw = $oldLine['pickup_date'] ?? ($line['pickup_date'] ?? null);
                                    $setorRaw = $oldLine['setor_date'] ?? ($line['setor_date'] ?? null);
                                    $pickupTxt = $pickupRaw ? \Carbon\Carbon::parse($pickupRaw)->format('d/m/Y') : '—';
                                    $setorTxt = $setorRaw ? \Carbon\Carbon::parse($setorRaw)->format('d/m/Y') : '—';

                                    $qtyIn = array_key_exists('qty_in', $oldLine)
                                        ? (is_null($oldLine['qty_in'])
                                            ? ''
                                            : (string) (int) $oldLine['qty_in'])
                                        : '';

                                    $qtyReject = array_key_exists('qty_reject', $oldLine)
                                        ? ((int) ($oldLine['qty_reject'] ?? 0) === 0
                                            ? ''
                                            : (string) (int) $oldLine['qty_reject'])
                                        : '';

                                    $rejectNotes = $oldLine['reject_notes'] ?? '';
                                @endphp

                                <div class="cardx mono fin-item" data-idx="{{ $idx }}"
                                    data-item-id="{{ $itemId }}" data-operator-id="{{ $opId }}"
                                    data-code="{{ $code }}" data-wip="{{ $wip }}">

                                    <div class="cardx-h">
                                        <div class="cardx-left">
                                            <input type="checkbox" class="chk row-check">
                                            <div>
                                                <div class="code">{{ $code }}</div>
                                                <div class="meta-inline">
                                                    <span title="Terakhir ambil">📦 {{ $pickupTxt }}</span>
                                                    <span class="dot">•</span>
                                                    <span title="Terakhir setor">🧵 {{ $setorTxt }}</span>
                                                </div>
                                                @if ($opId > 0)
                                                    <div class="op-chip">OP: {{ $opLabel }}</div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="wip">SISA {{ number_format($wip, 0, ',', '.') }}</div>
                                    </div>

                                    <div class="cardx-b">
                                        <div class="grid2">
                                            <div class="field">
                                                <label>Setor</label>
                                                <input type="text" inputmode="numeric"
                                                    class="form-control form-control-sm qty ok integer-input select-all-on-focus"
                                                    name="lines_byop[{{ $idx }}][qty_in]"
                                                    value="{{ $qtyIn }}" placeholder="0">
                                            </div>

                                            <div class="field">
                                                <label>Reject</label>
                                                <input type="text" inputmode="numeric"
                                                    class="form-control form-control-sm qty rj integer-input select-all-on-focus"
                                                    name="lines_byop[{{ $idx }}][qty_reject]"
                                                    value="{{ $qtyReject }}" placeholder="0">
                                            </div>
                                        </div>

                                        <div class="notes">
                                            <input type="text" class="form-control form-control-sm"
                                                name="lines_byop[{{ $idx }}][reject_notes]"
                                                placeholder="Catatan reject (opsional)" value="{{ $rejectNotes }}">
                                        </div>

                                        <input type="hidden" name="lines_byop[{{ $idx }}][item_id]"
                                            value="{{ $itemId }}">
                                        <input type="hidden" name="lines_byop[{{ $idx }}][total_wip]"
                                            value="{{ $wip }}">
                                        <input type="hidden" name="lines_byop[{{ $idx }}][operator_id]"
                                            value="{{ $opId }}">
                                        @if (!is_null($opCode))
                                            <input type="hidden" name="lines_byop[{{ $idx }}][operator_code]"
                                                value="{{ $opCode }}">
                                        @endif
                                        @if (!is_null($opName))
                                            <input type="hidden" name="lines_byop[{{ $idx }}][operator_name]"
                                                value="{{ $opName }}">
                                        @endif
                                        <input type="hidden" name="lines_byop[{{ $idx }}][pickup_date]"
                                            value="{{ $pickupRaw }}">
                                        <input type="hidden" name="lines_byop[{{ $idx }}][setor_date]"
                                            value="{{ $setorRaw }}">
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    <div class="fab-wrap">
                        <a href="{{ route('production.finishing_jobs.index') }}"
                            class="btn btn-sm btn-outline-secondary fab-back">←</a>
                        <button type="button" class="btn btn-sm btn-primary fab-save" id="btn-open-modal" disabled>
                            Simpan
                        </button>
                    </div>

                    {{-- MODAL --}}
                    <div class="modal fade" id="finishingOperatorModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Konfirmasi</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="alert alert-light border" style="border-radius:14px;">
                                        Pastikan input sudah benar.
                                        <div class="mt-1" style="font-size:.86rem;color:var(--muted);font-weight:800;">
                                            Mode <span class="mono" id="mode-label">ALL</span>
                                        </div>
                                    </div>

                                    <div class="mt-3" id="modal-operator-wrap">
                                        <label class="form-label form-label-sm">Operator jahit (wajib jika mode
                                            ALL)</label>
                                        <select id="modal-operator-select"
                                            class="form-select form-select-sm @error('operator_global_id') is-invalid @enderror">
                                            <option value="">- pilih -</option>
                                            @foreach ($operators as $op)
                                                <option value="{{ $op->id }}" @selected((int) old('operator_global_id', $defaultOperator) === (int) $op->id)>
                                                    {{ $op->code ?? $op->id }} — {{ $op->name }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('operator_global_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror

                                        <div class="invalid-feedback d-none" id="modal-op-required">
                                            Operator wajib dipilih untuk mode ALL.
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-dismiss="modal">Batal</button>
                                    <button type="button" class="btn btn-sm btn-primary"
                                        id="modal-confirm-submit">Simpan</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>{{-- panel-b --}}
            </form>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('finishing-form');

            const listAll = document.getElementById('list-all');
            const listByOp = document.getElementById('list-byop');

            const q = document.getElementById('q');
            const itemFilter = document.getElementById('item-filter');
            const opFilter = document.getElementById('op-filter');

            const btnOpenModal = document.getElementById('btn-open-modal');

            const operatorModeHidden = document.getElementById('operator_mode');
            const operatorHidden = document.getElementById('operator_global_id_hidden');

            const operatorSelectModal = document.getElementById('modal-operator-select');
            const modalConfirmBtn = document.getElementById('modal-confirm-submit');
            const operatorModalEl = document.getElementById('finishingOperatorModal');

            const modalOpWrap = document.getElementById('modal-operator-wrap');
            const modalModeLabel = document.getElementById('mode-label');
            const modalOpRequired = document.getElementById('modal-op-required');

            const body = document.body;
            const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

            function getMode() {
                return (operatorModeHidden?.value || 'all').toString(); // all/byop
            }

            function setMode(mode) {
                mode = (mode === 'byop') ? 'byop' : 'all';
                if (operatorModeHidden) operatorModeHidden.value = mode;

                if (mode === 'all') {
                    listAll.style.display = '';
                    listByOp.style.display = 'none';
                    setInputsEnabled(listAll, true);
                    setInputsEnabled(listByOp, false);
                } else {
                    listAll.style.display = 'none';
                    listByOp.style.display = '';
                    setInputsEnabled(listAll, false);
                    setInputsEnabled(listByOp, true);
                }
                applyFilter();
                computeSubmitEnabled();
            }

            function setInputsEnabled(root, enabled) {
                if (!root) return;
                root.querySelectorAll('input, select, textarea').forEach(el => {
                    el.disabled = !enabled;
                });
            }

            const itemFilterAllHTML = itemFilter ? itemFilter.innerHTML : '';

            function buildItemOptionsForOperator(opId) {
                if (!itemFilter) return;
                if (!opId) {
                    itemFilter.innerHTML = itemFilterAllHTML;
                    return;
                }

                const map = new Map();
                $$('.fin-item', listByOp).forEach(card => {
                    const wip = parseInt(card.dataset.wip || '0', 10) || 0;
                    if (wip <= 0) return;

                    if ((card.dataset.operatorId || '') !== opId) return;
                    const itemId = (card.dataset.itemId || '');
                    const code = (card.dataset.code || '');
                    if (itemId) map.set(itemId, code);
                });

                const arr = Array.from(map.entries())
                    .map(([id, code]) => ({
                        id,
                        code
                    }))
                    .sort((a, b) => (a.code || '').localeCompare(b.code || ''));

                itemFilter.innerHTML = ['<option value="">Semua</option>']
                    .concat(arr.map(x => `<option value="${x.id}">${x.code}</option>`))
                    .join('');
            }

            function ensureSelectedItemValid() {
                if (!itemFilter) return;
                const cur = (itemFilter.value || '').toString();
                if (!cur) return;
                const exists = Array.from(itemFilter.options).some(o => o.value === cur);
                if (!exists) itemFilter.value = '';
            }

            function sanitizeInt(v, allowEmpty) {
                v = (v ?? '').toString().trim();
                if (v === '') return allowEmpty ? '' : '0';
                const digits = v.replace(/[^0-9]/g, '');
                if (digits === '') return allowEmpty ? '' : '0';
                return String(Math.max(0, parseInt(digits, 10)));
            }

            function getActiveList() {
                return (getMode() === 'byop') ? listByOp : listAll;
            }

            function getEls(card) {
                return {
                    qtyIn: card.querySelector('input.integer-input[name*="[qty_in]"]'),
                    qtyRj: card.querySelector('input.integer-input[name*="[qty_reject]"]'),
                    wip: card.querySelector('input[type="hidden"][name*="[total_wip]"]'),
                    notesWrap: card.querySelector('.notes'),
                    cb: card.querySelector('.row-check'),
                };
            }

            function clampCard(card, changed) {
                const {
                    qtyIn,
                    qtyRj,
                    wip
                } = getEls(card);
                const W = Math.max(0, parseInt(wip?.value || 0, 10) || 0);

                let ok = Math.max(0, parseInt(qtyIn?.value || 0, 10) || 0);
                let rj = Math.max(0, parseInt(qtyRj?.value || 0, 10) || 0);

                if (ok > W) ok = W;
                if (rj > W) rj = W;

                if (ok + rj > W) {
                    if (changed === 'qty_in') ok = Math.max(0, W - rj);
                    if (changed === 'qty_reject') rj = Math.max(0, W - ok);
                }

                if (qtyIn) qtyIn.value = ok === 0 ? '' : String(ok);
                if (qtyRj) qtyRj.value = rj === 0 ? '' : String(rj);
            }

            function syncNotes(card) {
                const {
                    qtyRj,
                    notesWrap
                } = getEls(card);
                const rj = Math.max(0, parseInt(qtyRj?.value || 0, 10) || 0);
                if (!notesWrap) return;
                if (rj > 0) notesWrap.classList.add('is-show');
                else notesWrap.classList.remove('is-show');
            }

            function syncCheck(card) {
                const {
                    qtyIn,
                    qtyRj,
                    cb
                } = getEls(card);
                const ok = Math.max(0, parseInt(qtyIn?.value || 0, 10) || 0);
                const rj = Math.max(0, parseInt(qtyRj?.value || 0, 10) || 0);
                if (cb) cb.checked = ((ok + rj) > 0);
            }

            function computeSubmitEnabled() {
                const activeList = getActiveList();
                let total = 0;

                $$('.fin-item', activeList).forEach(card => {
                    const wip = parseInt(card.dataset.wip || '0', 10) || 0;
                    if (wip <= 0) return; // ✅ jangan hitung yg sudah habis

                    const {
                        qtyIn,
                        qtyRj
                    } = getEls(card);
                    const ok = Math.max(0, parseInt(qtyIn?.value || 0, 10) || 0);
                    const rj = Math.max(0, parseInt(qtyRj?.value || 0, 10) || 0);
                    total += (ok + rj);
                });

                btnOpenModal.disabled = total <= 0;
            }

            function applyFilter() {
                const activeList = getActiveList();
                const term = (q?.value || '').toString().trim().toUpperCase();
                const selItemId = (itemFilter?.value || '').toString();
                const selOpId = (opFilter?.value || '').toString();

                $$('.fin-item', activeList).forEach(card => {
                    const code = (card.dataset.code || '').toString().toUpperCase();
                    const itemId = (card.dataset.itemId || '').toString();
                    const opId = (card.dataset.operatorId || '').toString();
                    const wip = parseInt(card.dataset.wip || '0', 10) || 0;

                    const stillHasWip = wip > 0; // ✅ yang sudah kepost/habis -> hilang
                    const matchSearch = !term || code.includes(term);
                    const matchItem = !selItemId || itemId === selItemId;
                    const matchOp = (getMode() === 'byop') ? (!selOpId || opId === selOpId) : true;

                    card.style.display = (stillHasWip && matchSearch && matchItem && matchOp) ? '' : 'none';
                });

                computeSubmitEnabled();
            }

            // SEARCH
            q?.addEventListener('input', () => {
                const up = (q.value || '').toString().toUpperCase();
                if (q.value !== up) q.value = up;
                applyFilter();
            });

            // OP FILTER -> mode switch
            opFilter?.addEventListener('change', () => {
                const selOpId = (opFilter.value || '').toString();

                if (!selOpId) {
                    setMode('all');
                    if (itemFilter) itemFilter.innerHTML = itemFilterAllHTML;
                } else {
                    setMode('byop');
                    buildItemOptionsForOperator(selOpId);
                    ensureSelectedItemValid();
                }

                applyFilter();
                setTimeout(() => {
                    try {
                        itemFilter?.focus?.();
                    } catch (e) {}
                }, 150);
            });

            // ITEM FILTER
            itemFilter?.addEventListener('change', () => {
                applyFilter();
                const activeList = getActiveList();
                const first = $$('.fin-item', activeList).find(c => c.style.display !== 'none');
                if (first) first.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });

            // INPUT clamp
            form.addEventListener('input', (e) => {
                const t = e.target;
                if (!t.classList?.contains('integer-input')) return;

                t.value = sanitizeInt(t.value, true);

                const card = t.closest('.fin-item');
                if (!card) return;

                const changed = (t.name || '').includes('[qty_reject]') ? 'qty_reject' : 'qty_in';
                clampCard(card, changed);
                syncCheck(card);
                syncNotes(card);
                computeSubmitEnabled();
            });

            // checkbox reset
            form.addEventListener('change', (e) => {
                const t = e.target;
                if (!t.classList?.contains('row-check')) return;

                const card = t.closest('.fin-item');
                if (!card) return;

                if (!t.checked) {
                    const {
                        qtyIn,
                        qtyRj
                    } = getEls(card);
                    if (qtyIn) qtyIn.value = '';
                    if (qtyRj) qtyRj.value = '';
                    syncNotes(card);
                }
                computeSubmitEnabled();
            });

            // select all on focus + keyboard class
            form.addEventListener('focusin', (e) => {
                const t = e.target;
                if (t?.classList?.contains('select-all-on-focus')) {
                    setTimeout(() => {
                        try {
                            t.select();
                        } catch (e) {}
                    }, 0);
                }
                if (window.innerWidth < 768) body.classList.add('keyboard-open');
            });
            form.addEventListener('focusout', () => body.classList.remove('keyboard-open'));

            // MODAL
            if (operatorModalEl && typeof bootstrap !== 'undefined') {
                const bsModal = new bootstrap.Modal(operatorModalEl);

                btnOpenModal?.addEventListener('click', (e) => {
                    if (btnOpenModal.disabled) return;
                    e.preventDefault();

                    const mode = getMode();
                    if (modalModeLabel) modalModeLabel.textContent = (mode === 'all') ? 'ALL' : 'BYOP';
                    if (modalOpWrap) modalOpWrap.style.display = (mode === 'all') ? '' : 'none';

                    operatorSelectModal?.classList.remove('is-invalid');
                    if (modalOpRequired) modalOpRequired.classList.add('d-none');

                    bsModal.show();
                });

                modalConfirmBtn?.addEventListener('click', () => {
                    const mode = getMode();

                    if (mode === 'all') {
                        const chosen = (operatorSelectModal?.value || '').toString();
                        if (!chosen) {
                            operatorSelectModal?.classList.add('is-invalid');
                            modalOpRequired?.classList.remove('d-none');
                            return;
                        }
                        operatorHidden.value = chosen; // ✅ operator_global_id terkirim
                    }

                    bsModal.hide();
                    form.submit();
                });
            }

            // normalize submit (only active list)
            form.addEventListener('submit', () => {
                const activeList = getActiveList();

                const selOpId = (opFilter?.value || '').toString();
                if (!selOpId) setMode('all');
                else setMode('byop');

                $$('.integer-input', activeList).forEach(i => {
                    const isReject = (i.name || '').includes('[qty_reject]');
                    let v = (i.value || '').trim();
                    if (v === '') {
                        if (isReject) i.value = '0';
                        return;
                    }
                    i.value = sanitizeInt(v, false);
                });
            });

            // init sync
            [listAll, listByOp].forEach(list => {
                if (!list) return;
                $$('.fin-item', list).forEach(card => {
                    syncCheck(card);
                    syncNotes(card);
                });
            });

            // init default mode = ALL
            setMode((operatorModeHidden?.value || 'all'));
            applyFilter();
            computeSubmitEnabled();
        });
    </script>
@endpush
