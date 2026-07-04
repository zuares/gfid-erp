{{-- resources/views/production/cutting_jobs/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Cutting Job ' . $job->code)

@php
    // LOCKED jika ada pivot cutting_job_lots
    $isLotsLocked = !empty($selectedLotSummaries);

    $selectedLotsExisting = $selectedLotsExisting ?? [];

    $rowsExisting = $rows ?? [];
    if (empty($rowsExisting)) {
        $rowsExisting = [
            [
                'id' => null,
                'bundle_no' => 1,
                'lot_id' => null,
                'finished_item_id' => null,
                'finished_item_code' => null,
                'finished_item_name' => null,
                'item_category_id' => null,
                'qty_pcs' => null,
                'qty_used_fabric' => 0,
                'notes' => '',
            ],
        ];
    }

    $defaultOperatorId = old('operator_id', optional($job->bundles->first())->operator_id);

    // Untuk summary di atas
    $lockedFabricLabel = '-';
    if (!empty($selectedLotSummaries) && !empty($selectedLotSummaries[0]['item_code'])) {
        $lockedFabricLabel = trim(
            ($selectedLotSummaries[0]['item_code'] ?? '-') . ' — ' . ($selectedLotSummaries[0]['item_name'] ?? '-'),
        );
    }

    // Build map LOT untuk JS (kode + planned)
    $lockedLotInfo = collect($selectedLotSummaries ?? [])
        ->mapWithKeys(function ($s) {
            $lotId = (int) ($s['lot_id'] ?? 0);
            return $lotId
                ? [
                    $lotId => [
                        'lot_id' => $lotId,
                        'code' => $s['code'] ?? 'LOT#' . $lotId,
                        'planned' => (float) ($s['planned'] ?? 0),
                        'used' => (float) ($s['used'] ?? 0),
                    ],
                ]
                : [];
        })
        ->all();
@endphp

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4.5rem
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono"
        }

        .help {
            color: var(--muted);
            font-size: .85rem
        }

        .cutting-card {
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .45);
            background: var(--card);
            margin-bottom: .75rem
        }

        .cutting-card-header {
            padding: .55rem .75rem .45rem;
            border-bottom: 1px solid rgba(148, 163, 184, .35);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem
        }

        .cutting-card-header h5 {
            font-size: .9rem;
            margin: 0
        }

        .cutting-card-body {
            padding: .6rem .75rem;
            overflow: visible;
            position: relative
        }

        .badge-soft {
            font-size: .7rem;
            border-radius: 999px;
            padding: .08rem .5rem;
            border: 1px solid rgba(148, 163, 184, .6);
            background: rgba(148, 163, 184, .14)
        }

        .bundles-table {
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 0;
            font-size: .82rem
        }

        .bundles-table thead th {
            color: var(--muted);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
            border-bottom: 2px solid rgba(148, 163, 184, .18);
            white-space: nowrap
        }

        .bundles-table tbody td {
            vertical-align: middle
        }

        .bundle-row-item-missing .js-item-suggest-input {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 .15rem rgba(220, 38, 38, .12) !important
        }

        .btn-remove-row {
            width: 28px;
            height: 28px;
            border: 1px solid rgba(220, 38, 38, .18) !important;
            border-radius: 999px;
            background: rgba(254, 242, 242, .6);
            line-height: 1;
            padding: 0 !important;
            text-decoration: none;
            font-size: .9rem;
            color: rgba(220, 38, 38, .75);
            display: inline-flex;
            align-items: center;
            justify-content: center
        }

        .bundle-notes-cell {
            min-width: 140px
        }

        .lot-table-wrap {
            overflow-x: auto
        }

        .lot-table td,
        .lot-table th {
            vertical-align: middle
        }

        .lot-code {
            font-weight: 700
        }

        .cutting-main-content.d-none {
            display: none
        }

        .lot-locked-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: .25rem .75rem;
            font-size: .85rem
        }

        .lot-locked-grid .muted {
            color: var(--muted)
        }

        .lot-locked-grid .right {
            text-align: right
        }

        .cutting-actions {
            gap: .5rem
        }

        @media(max-width:767.98px) {
            .page-wrap {
                padding: .65rem .65rem 5rem
            }

            .cutting-card {
                border-radius: 14px
            }

            .cutting-card-header {
                padding: .68rem .85rem;
                align-items: flex-start;
                flex-direction: column;
                gap: .35rem
            }

            .cutting-card-header h5 {
                font-size: .82rem;
                letter-spacing: .03em;
                text-transform: uppercase
            }

            .cutting-card-body {
                padding: .7rem .85rem .85rem
            }

            .bundles-table-wrap {
                overflow-x: visible
            }

            .bundles-table,
            .bundles-table tbody,
            .bundles-table tfoot {
                display: block;
                width: 100%
            }

            .bundles-table thead {
                display: none
            }

            .bundles-table tbody tr.bundle-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr) 76px 34px;
                column-gap: .42rem;
                row-gap: 0;
                align-items: center;
                border: 1px solid rgba(148, 163, 184, .25);
                border-left: 3px solid rgba(37, 99, 235, .32);
                border-radius: 12px;
                padding: .44rem .48rem;
                margin-bottom: .38rem;
                background: var(--card);
                box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
                overflow: visible;
                position: relative
            }

            .bundles-table tbody td {
                border: 0 !important;
                padding: 0 !important
            }

            .bundles-table tbody .bundle-index-cell {
                display: none !important
            }

            .bundles-table tbody .bundle-lot-cell {
                display: none !important
            }

            .bundles-table tbody .bundle-item-cell {
                grid-column: 1;
                grid-row: 1;
                min-width: 0;
                overflow: visible
            }

            .bundles-table tbody .bundle-qty-cell {
                grid-column: 2;
                grid-row: 1
            }

            .bundles-table tbody .bundle-notes-cell {
                display: none !important
            }

            .bundles-table tbody .bundle-action-cell {
                grid-column: 3;
                grid-row: 1;
                display: flex !important;
                align-items: center;
                justify-content: center
            }

            .bundles-table .form-control-sm,
            .bundles-table .form-select-sm {
                min-height: 40px;
                border-radius: 10px;
                font-size: .84rem;
                padding: .34rem .48rem
            }

            .bundles-table .item-suggest-wrap {
                width: 100%;
                min-width: 0
            }

            .bundles-table .js-item-suggest-input {
                font-weight: 800;
                letter-spacing: .01em;
                text-transform: uppercase
            }

            .bundles-table .item-suggest-dropdown {
                min-width: min(92vw, 340px);
                max-height: 48vh
            }

            .bundle-qty {
                text-align: center !important;
                font-size: .92rem !important;
                font-weight: 900 !important;
                padding-inline: .25rem !important
            }

            .bundle-used-preview,
            .bundle-qty + .help {
                display: none
            }

            .btn-remove-row {
                width: 34px;
                height: 34px;
                font-size: 1.05rem
            }

            .bundles-table tfoot tr,
            .bundles-table tfoot td {
                display: block;
                width: 100%
            }

            .bundles-table tfoot td {
                border: 0 !important;
                padding: .2rem 0 0 !important
            }

            #btn-add-row {
                width: 100%;
                min-height: 46px;
                border-radius: 12px
            }

            #used-total-label {
                display: inline-block;
                margin-top: .45rem
            }

            .cutting-actions {
                flex-direction: column-reverse;
                align-items: stretch
            }

            .cutting-actions .btn-primary {
                width: 100%
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <div class="text-muted small">Produksi • Cutting Job</div>
                <h1 class="h5 mb-0">Edit: <span class="mono">{{ $job->code }}</span></h1>
                <div class="help mt-1">Tanggal: {{ $job->date?->format('Y-m-d') ?? $job->date }}</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('production.cutting_jobs.show', $job) }}"
                    class="btn btn-outline-secondary btn-sm">Kembali</a>
                <a href="{{ route('production.cutting_jobs.index') }}" class="btn btn-outline-secondary btn-sm">List</a>
            </div>
        </div>

        <form action="{{ route('production.cutting_jobs.update', $job) }}" method="POST" id="cutting-form">
            @csrf
            @method('PUT')

            {{-- wajib --}}
            <input type="hidden" name="warehouse_id" value="{{ old('warehouse_id', $job->warehouse_id) }}">

            {{-- ✅ aman: fabric_item_id ambil dari job (update backend boleh override dari LOT) --}}
            <input type="hidden" name="fabric_item_id" id="fabric_item_id"
                value="{{ old('fabric_item_id', $job->fabric_item_id) }}">

            {{-- ✅ selected_lots (untuk update backend yang minta selected_lots) --}}
            <div id="selected-lots-hidden">
                @foreach ($selectedLotsExisting ?? [] as $lotId)
                    <input type="hidden" name="selected_lots[]" value="{{ (int) $lotId }}">
                @endforeach
            </div>

            {{-- =========================
            LOT SECTION
        ========================== --}}
            @if ($isLotsLocked)
                <div class="cutting-card" id="cutting-lot-locked">
                    <div class="cutting-card-header">
                        <h5>LOT (Terkunci)</h5>
                        <span class="badge-soft">Edit tidak perlu pilih LOT lagi</span>
                    </div>
                    <div class="cutting-card-body">
                        <div class="lot-locked-grid">
                            @foreach ($selectedLotSummaries as $s)
                                <div>
                                    <div class="mono fw-semibold">{{ $s['code'] }}</div>
                                    <div class="muted small">{{ $s['item_code'] }} — {{ $s['item_name'] }}</div>
                                </div>
                                <div class="right">
                                    <div class="mono fw-semibold">{{ number_format((float) $s['used'], 2, ',', '.') }}
                                    </div>
                                    <div class="muted small">planned:
                                        {{ number_format((float) $s['planned'], 2, ',', '.') }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="cutting-card" id="cutting-pick-lot">
                    <div class="cutting-card-header">
                        <h5>Step 1: Pilih LOT</h5>
                        <span class="badge-soft">Wajib: pilih minimal 1 LOT</span>
                    </div>
                    <div class="cutting-card-body">
                        <div class="d-flex gap-2 flex-wrap mb-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-select-all-lots">Pilih
                                Semua</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                id="btn-unselect-all-lots">Unselect Semua</button>
                            <button type="button" class="btn btn-sm btn-primary" id="btn-confirm-lots">Konfirmasi
                                LOT</button>
                        </div>

                        <div class="lot-table-wrap">
                            <table class="table table-sm lot-table">
                                <thead>
                                    <tr>
                                        <th style="width:44px;"></th>
                                        <th>LOT</th>
                                        <th>Item Kain</th>
                                        <th class="text-end" style="width:140px;">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lotStocks as $row)
                                        @php
                                            $lot = $row->lot;
                                            $item = $lot?->item;
                                            $lotId = (int) ($lot?->id ?? 0);
                                            $itemId = (int) ($item?->id ?? 0);
                                            $balance = (float) ($row->balance ?? 0);
                                            $checked = in_array($lotId, $selectedLotsExisting, true);
                                        @endphp
                                        <tr class="lot-row" data-lot-id="{{ $lotId }}"
                                            data-item-id="{{ $itemId }}"
                                            data-balance="{{ number_format($balance, 4, '.', '') }}"
                                            data-code="{{ $lot?->code ?? 'LOT#' . $lotId }}">
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input lot-checkbox"
                                                    value="{{ $lotId }}" @checked($checked)>
                                            </td>
                                            <td>
                                                <div class="lot-code mono">{{ $lot?->code ?? '-' }}</div>
                                                <div class="text-muted small">{{ $item?->code ?? '-' }}</div>
                                            </td>
                                            <td>{{ $item?->name ?? '-' }}</td>
                                            <td class="text-end mono">{{ number_format($balance, 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @error('selected_lots')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endif

            {{-- =========================
            MAIN CONTENT
        ========================== --}}
            <div id="cutting-main-content" class="cutting-main-content d-none">

                {{-- SUMMARY LOT --}}
                <div class="cutting-card mb-2">
                    <div class="cutting-card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small">
                            <div>
                                <span class="text-muted">Item kain:</span>
                                <span class="fw-semibold" id="current-fabric-label">-</span>
                            </div>
                            <div>
                                <span class="text-muted">LOT terpilih:</span>
                                <span class="fw-semibold" id="current-lot-count">0 LOT</span>
                                <span class="text-muted ms-2">Total planned:</span>
                                <span class="fw-semibold mono" id="current-lot-balance">0.00</span>
                            </div>
                        </div>

                        @if (!$isLotsLocked)
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-change-lots">Ubah
                                LOT</button>
                        @endif
                    </div>
                </div>

                {{-- INFO --}}
                <div class="cutting-card">
                    <div class="cutting-card-header">
                        <h5>Info Cutting Job</h5>
                        <span class="badge-soft">Tanggal • Operator • Catatan</span>
                    </div>
                    <div class="cutting-card-body">
                        <div class="row g-3">
                            <div class="col-md-3 col-6">
                                <label class="form-label">Tanggal</label>
                                <input type="date" name="date"
                                    class="form-control @error('date') is-invalid @enderror"
                                    value="{{ old('date', $job->date?->format('Y-m-d') ?? now()->toDateString()) }}">
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 col-6">
                                <label class="form-label">Operator Cutting</label>
                                <select name="operator_id" class="form-select @error('operator_id') is-invalid @enderror">
                                    <option value="">Pilih operator cutting…</option>
                                    @foreach ($operators as $op)
                                        <option value="{{ $op->id }}" @selected(old('operator_id', $defaultOperatorId) == $op->id)>
                                            {{ $op->code }} — {{ $op->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('operator_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-5 col-12">
                                <label class="form-label">Catatan</label>
                                <input type="text" name="notes" class="form-control"
                                    value="{{ old('notes', $job->notes) }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- BUNDLES --}}
                <div class="cutting-card">
                    <div class="cutting-card-header">
                        <h5>Hasil Cutting</h5>
                        <span class="badge-soft">Item jadi & qty</span>
                    </div>

                    <div class="cutting-card-body">
                        @error('bundles')
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror

                        <div class="table-responsive bundles-table-wrap mb-2">
                            <table class="bundles-table table table-sm" id="bundles-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px;">#</th>
                                        <th style="min-width:120px;">LOT</th>
                                        <th style="min-width:220px;">Item Jadi</th>
                                        <th style="min-width:120px;" class="text-end">Qty (pcs)</th>
                                        <th style="min-width:150px;">Catatan</th>
                                        <th style="width:40px;"></th>
                                    </tr>
                                </thead>

                                <tbody id="bundle-rows">
                                    @foreach ($rowsExisting as $i => $r)
                                        @php
                                            $fiId = old("bundles.$i.finished_item_id", $r['finished_item_id'] ?? '');
                                            $catId = old("bundles.$i.item_category_id", $r['item_category_id'] ?? '');

                                            $dispOld = old("bundles.$i.finished_item_display", '');
                                            $code = $r['finished_item_code'] ?? '';
                                            $name = $r['finished_item_name'] ?? '';
                                            $disp =
                                                $dispOld !== ''
                                                    ? $dispOld
                                                    : trim(($code ?: '') . ($name ? ' — ' . $name : ''));
                                        @endphp

                                        <tr class="bundle-row">
                                            @if (!empty($r['id']))
                                                <input type="hidden" name="bundles[{{ $i }}][id]"
                                                    value="{{ $r['id'] }}">
                                            @endif

                                            <input type="hidden" class="bundle-used-fabric"
                                                name="bundles[{{ $i }}][qty_used_fabric]"
                                                value="{{ old("bundles.$i.qty_used_fabric", $r['qty_used_fabric'] ?? 0) }}">

                                            <td class="bundle-index-cell bundle-index mono">{{ $i + 1 }}</td>

                                            <td class="bundle-lot-cell">
                                                <select class="form-select form-select-sm bundle-lot-select"
                                                    name="bundles[{{ $i }}][lot_id]">
                                                    <option value="">- Pilih LOT -</option>
                                                </select>
                                                @error("bundles.$i.lot_id")
                                                    <div class="text-danger small">{{ $message }}</div>
                                                @enderror

                                                <input type="hidden" class="bundle-lot-selected"
                                                    value="{{ old("bundles.$i.lot_id", $r['lot_id'] ?? '') }}">
                                            </td>

                                            <td class="bundle-item-cell">
                                                <x-item-suggest idName="bundles[{{ $i }}][finished_item_id]"
                                                    categoryName="bundles[{{ $i }}][item_category_id]"
                                                    displayName="bundles[{{ $i }}][finished_item_display]"
                                                    :items="collect()" displayValue="{{ $disp }}"
                                                    idValue="{{ $fiId }}" categoryValue="{{ $catId }}"
                                                    placeholder="Cari item jadi…" type="finished_good" :minChars="1"
                                                    :maxResults="5" variant="mini" :required="true" :skipSubmitValidation="true" />
                                                @error("bundles.$i.finished_item_id")
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </td>

                                            <td class="bundle-qty-cell">
                                                <input type="number" step="1" min="0" inputmode="numeric"
                                                    pattern="[0-9]*" name="bundles[{{ $i }}][qty_pcs]"
                                                    class="form-control form-control-sm text-end bundle-qty @error("bundles.$i.qty_pcs") is-invalid @enderror"
                                                    value="{{ old("bundles.$i.qty_pcs", $r['qty_pcs'] ?? '') }}">
                                                @error("bundles.$i.qty_pcs")
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror

                                                <div class="help mt-1">
                                                    Used: <span class="bundle-used-preview">
                                                        {{ number_format((float) old("bundles.$i.qty_used_fabric", $r['qty_used_fabric'] ?? 0), 2, ',', '.') }}
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="bundle-notes-cell">
                                                <input type="text" class="form-control form-control-sm"
                                                    name="bundles[{{ $i }}][notes]"
                                                    value="{{ old("bundles.$i.notes", $r['notes'] ?? '') }}">
                                            </td>

                                            <td class="bundle-action-cell text-center">
                                            <button type="button"
                                                    class="btn btn-sm btn-link text-danger btn-remove-row"
                                                    aria-label="Hapus baris">&times;</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                                <tfoot>
                                    <tr>
                                        <td colspan="6">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                id="btn-add-row">
                                                + Tambah Baris
                                            </button>

                                            <span class="ms-3 small text-muted">
                                                Total Used: <span class="mono fw-semibold"
                                                    id="used-total-label">0,00</span>
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="help">
                            <b>Wajib:</b> setiap baris harus punya LOT & Item Jadi.
                            @if ($isLotsLocked)
                                LOT sudah <b>terkunci</b>, dropdown hanya menampilkan LOT milik job.
                            @else
                                Pilih LOT di Step 1, lalu dropdown per baris otomatis terisi.
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ACTIONS --}}
                <div class="d-flex justify-content-between align-items-center mt-3 cutting-actions">
                    <button type="submit" class="btn btn-primary btn-sm" id="btn-update-cutting">Update Cutting Job</button>
                    <a href="{{ route('production.cutting_jobs.show', $job) }}"
                        class="btn btn-outline-secondary btn-sm">Batal</a>
                </div>
            </div>

            {{-- TEMPLATE row baru --}}
            <template id="bundle-row-template">
                <tr class="bundle-row">
                    <input type="hidden" class="bundle-used-fabric" name="bundles[__INDEX__][qty_used_fabric]"
                        value="0">
                    <td class="bundle-index-cell bundle-index mono">__NO__</td>

                    <td class="bundle-lot-cell">
                        <select class="form-select form-select-sm bundle-lot-select" name="bundles[__INDEX__][lot_id]">
                            <option value="">- Pilih LOT -</option>
                        </select>
                        <input type="hidden" class="bundle-lot-selected" value="">
                    </td>

                    <td class="bundle-item-cell">
                        <x-item-suggest idName="bundles[__INDEX__][finished_item_id]"
                            categoryName="bundles[__INDEX__][item_category_id]"
                            displayName="bundles[__INDEX__][finished_item_display]" :items="collect()" displayValue=""
                            idValue="" categoryValue="" placeholder="Cari item jadi…" type="finished_good"
                            :minChars="1" :maxResults="5" variant="mini" :required="true" :skipSubmitValidation="true" />
                    </td>

                    <td class="bundle-qty-cell">
                        <input type="number" step="1" min="0" inputmode="numeric" pattern="[0-9]*"
                            name="bundles[__INDEX__][qty_pcs]" class="form-control form-control-sm text-end bundle-qty"
                            value="">
                        <div class="help mt-1">Used: <span class="bundle-used-preview">0,00</span></div>
                    </td>

                    <td class="bundle-notes-cell">
                        <input type="text" class="form-control form-control-sm" name="bundles[__INDEX__][notes]"
                            value="">
                    </td>

                    <td class="bundle-action-cell text-center">
                        <button type="button" class="btn btn-sm btn-link text-danger btn-remove-row"
                            aria-label="Hapus baris">&times;</button>
                    </td>
                </tr>
            </template>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ✅ JANGAN bergantung pada window.__LOTS_LOCKED__ dari inline script
            const lotsLocked = @json((bool) $isLotsLocked);
            const lockedLotsFromServer = @json(array_values($selectedLotsExisting ?? []));
            const lockedLotInfo = @json($lockedLotInfo); // { lotId: {code, planned, used}, ... }
            const lockedFabricLabel = @json($lockedFabricLabel);

            const form = document.getElementById('cutting-form');

            const lotRows = Array.from(document.querySelectorAll('.lot-row'));
            const lotCheckboxes = Array.from(document.querySelectorAll('.lot-checkbox'));
            const btnSelectAllLots = document.getElementById('btn-select-all-lots');
            const btnUnselectAllLots = document.getElementById('btn-unselect-all-lots');
            const btnConfirmLots = document.getElementById('btn-confirm-lots');

            const bundlesTbody = document.getElementById('bundle-rows');
            const btnAddRow = document.getElementById('btn-add-row');
            const submitBtn = document.getElementById('btn-update-cutting');

            const usedTotalLabel = document.getElementById('used-total-label');

            const mainContent = document.getElementById('cutting-main-content');
            const pickLotSection = document.getElementById('cutting-pick-lot');

            const currentFabricLabel = document.getElementById('current-fabric-label');
            const currentLotCount = document.getElementById('current-lot-count');
            const currentLotBalance = document.getElementById('current-lot-balance');
            const btnChangeLots = document.getElementById('btn-change-lots');

            const selectedLotsHiddenWrap = document.getElementById('selected-lots-hidden');

            function isMobile() {
                return window.matchMedia('(max-width: 767.98px)').matches;
            }

            function toInt(val) {
                if (val === null || val === undefined) return 0;
                const s = String(val).replace(',', '.');
                const head = s.split('.')[0];
                const n = parseInt(head.replace(/[^\d]/g, '') || '0', 10);
                return isNaN(n) || n < 0 ? 0 : n;
            }

            function fmt2(n) {
                const x = Number(n || 0);
                return x.toFixed(2).replace('.', ',');
            }

            // lot_id -> info (untuk unlocked mode)
            const lotInfoMap = {};
            lotRows.forEach(tr => {
                const lotId = parseInt(tr.dataset.lotId, 10);
                lotInfoMap[lotId] = {
                    lotId,
                    itemId: parseInt(tr.dataset.itemId || '0', 10),
                    code: tr.dataset.code || (tr.querySelector('.lot-code')?.textContent?.trim() ??
                        `LOT#${lotId}`),
                    balance: parseFloat(tr.dataset.balance ?? '0'),
                };
            });

            let bundleIndexCounter = bundlesTbody ? bundlesTbody.querySelectorAll('.bundle-row').length : 0;

            function getCheckedLots() {
                if (lotsLocked) return (lockedLotsFromServer || []).map(n => parseInt(n, 10)).filter(n => n > 0);
                const ids = [];
                lotCheckboxes.forEach(cb => {
                    if (cb.checked) ids.push(parseInt(cb.value, 10));
                });
                return ids;
            }

            function setSelectedLotsHidden(ids) {
                if (!selectedLotsHiddenWrap) return;
                selectedLotsHiddenWrap.innerHTML = '';
                (ids || []).forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_lots[]';
                    input.value = String(id);
                    selectedLotsHiddenWrap.appendChild(input);
                });
            }

            function showMainContent() {
                if (!mainContent) return;
                mainContent.classList.remove('d-none');
                if (isMobile() && pickLotSection) pickLotSection.classList.add('d-none');
            }

            function showPickLotSection() {
                if (!pickLotSection) return;
                if (isMobile()) {
                    pickLotSection.classList.remove('d-none');
                    if (mainContent) mainContent.classList.add('d-none');
                }
                pickLotSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }

            // planned per lot:
            // - locked: dari pivot (lockedLotInfo[lotId].planned)
            // - unlocked: fallback pakai balance table (lotInfoMap[lotId].balance)
            function plannedForLot(lotId) {
                const k = String(lotId);
                if (lockedLotInfo && lockedLotInfo[k] && Number(lockedLotInfo[k].planned || 0) > 0) return Number(
                    lockedLotInfo[k].planned || 0);
                if (lockedLotInfo && lockedLotInfo[lotId] && Number(lockedLotInfo[lotId].planned || 0) > 0)
                return Number(lockedLotInfo[lotId].planned || 0);
                return Number(lotInfoMap[lotId]?.balance || 0);
            }

            function lotLabel(lotId) {
                const k = String(lotId);
                if (lockedLotInfo && lockedLotInfo[k]?.code) return lockedLotInfo[k].code;
                if (lockedLotInfo && lockedLotInfo[lotId]?.code) return lockedLotInfo[lotId].code;
                return lotInfoMap[lotId]?.code ?? `LOT#${lotId}`;
            }

            function updateCurrentLotSummary() {
                const ids = getCheckedLots();
                const lotCount = ids.length;
                let totalPlanned = 0;
                ids.forEach(id => totalPlanned += plannedForLot(id));

                if (currentLotCount) currentLotCount.textContent = `${lotCount} LOT`;
                if (currentLotBalance) currentLotBalance.textContent = Number(totalPlanned || 0).toFixed(2);

                if (currentFabricLabel) {
                    if (lotsLocked) currentFabricLabel.textContent = lockedFabricLabel || '-';
                    else currentFabricLabel.textContent = (lotCount > 0) ? 'Mengikuti LOT terpilih' : '-';
                }
            }

            function enforceSingleFabricForCheckedLots(changedCb = null) {
                if (lotsLocked) return true; // pivot sudah aman
                const infos = [];
                lotCheckboxes.forEach(cb => {
                    if (!cb.checked) return;
                    const lotId = parseInt(cb.value, 10);
                    if (lotInfoMap[lotId]) infos.push(lotInfoMap[lotId]);
                });
                if (!infos.length) return true;

                const firstItemId = infos[0].itemId || 0;
                const conflict = infos.some(i => (i.itemId || 0) !== firstItemId);
                if (conflict) {
                    if (changedCb) changedCb.checked = false;
                    alert('Semua LOT yang dipilih harus dari item kain yang sama.');
                    return false;
                }
                return true;
            }

            function rebuildLotOptionsForAllRows() {
                if (!bundlesTbody) return;

                const checkedLotIds = getCheckedLots();
                const rows = Array.from(bundlesTbody.querySelectorAll('.bundle-row'));

                rows.forEach((tr, rowIndex) => {
                    const select = tr.querySelector('.bundle-lot-select');
                    if (!select) return;

                    const selectedHidden = tr.querySelector('.bundle-lot-selected');
                    const preferred = selectedHidden ? parseInt(selectedHidden.value || '0', 10) : 0;

                    select.innerHTML = '';
                    const ph = document.createElement('option');
                    ph.value = '';
                    ph.textContent = checkedLotIds.length ? '- Pilih LOT -' : 'Tidak ada LOT terpilih';
                    select.appendChild(ph);

                    checkedLotIds.forEach(lotId => {
                        const opt = document.createElement('option');
                        opt.value = lotId;
                        opt.textContent = lotLabel(lotId);
                        select.appendChild(opt);
                    });

                    if (preferred && checkedLotIds.includes(preferred)) select.value = String(preferred);
                    else if (checkedLotIds.length > 0) select.value = String(checkedLotIds[rowIndex %
                        checkedLotIds.length]);
                    else select.value = '';
                });
            }

            function updateBundleRowIndices() {
                if (!bundlesTbody) return;
                const rows = bundlesTbody.querySelectorAll('.bundle-row');
                rows.forEach((tr, idx) => {
                    const n = tr.querySelector('.bundle-index');
                    if (n) n.textContent = idx + 1;
                    tr.querySelectorAll('[name]').forEach(el => {
                        el.name = el.name.replace(/bundles\[\d+]/, `bundles[${idx}]`);
                    });
                });
            }

            function rowIsActive(tr) {
                const lotId = parseInt(tr.querySelector('.bundle-lot-select')?.value || '0', 10);
                const qty = toInt(tr.querySelector('.bundle-qty')?.value || '');
                const fi = tr.querySelector('input[name*="[finished_item_id]"]')?.value || '';
                return lotId > 0 && qty > 0 && String(fi).trim() !== '';
            }

            // ✅ CORE: Used = planned LOT ÷ jumlah baris aktif pada LOT tsb (last row remainder)
            function redistributeUsedByLot() {
                if (!bundlesTbody) return;

                const rows = Array.from(bundlesTbody.querySelectorAll('.bundle-row'));

                // group rows aktif by lot
                const groups = {};
                rows.forEach(tr => {
                    const lotId = parseInt(tr.querySelector('.bundle-lot-select')?.value || '0', 10);
                    if (!lotId) {
                        // kalau lot kosong, used = 0
                        setRowUsed(tr, 0);
                        return;
                    }
                    if (!rowIsActive(tr)) {
                        setRowUsed(tr, 0);
                        return;
                    }
                    groups[lotId] = groups[lotId] || [];
                    groups[lotId].push(tr);
                });

                Object.keys(groups).forEach(k => {
                    const lotId = parseInt(k, 10);
                    const list = groups[lotId] || [];
                    const planned = plannedForLot(lotId);

                    if (!list.length || planned <= 0) {
                        list.forEach(tr => setRowUsed(tr, 0));
                        return;
                    }

                    const count = list.length;
                    const per = Math.round((planned / count) * 100) / 100; // 2 decimals
                    let usedSoFar = 0;

                    list.forEach((tr, idx) => {
                        if (idx === count - 1) {
                            const last = Math.max(planned - usedSoFar, 0);
                            setRowUsed(tr, last);
                        } else {
                            setRowUsed(tr, per);
                            usedSoFar += per;
                        }
                    });
                });

                rebuildUsedSummaries();
            }

            function setRowUsed(tr, used) {
                const hiddenUsed = tr.querySelector('.bundle-used-fabric');
                const prev = tr.querySelector('.bundle-used-preview');
                const val = Number(used || 0);
                if (hiddenUsed) hiddenUsed.value = val.toFixed(2);
                if (prev) prev.textContent = fmt2(val);
            }

            function rebuildUsedSummaries() {
                if (!bundlesTbody || !usedTotalLabel) return;
                let total = 0;
                Array.from(bundlesTbody.querySelectorAll('.bundle-row')).forEach(tr => {
                    const hidden = tr.querySelector('.bundle-used-fabric');
                    const v = parseFloat((hidden?.value ?? '0').toString().replace(',', '.'));
                    total += isNaN(v) ? 0 : v;
                });
                usedTotalLabel.textContent = fmt2(total);
            }

            function clampQtyInteger(tr) {
                const qtyInput = tr.querySelector('.bundle-qty');
                if (!qtyInput) return;
                const qty = toInt(qtyInput.value || '');
                qtyInput.value = qty ? String(qty) : '';
            }

            function validateBundleItems(markInvalid = false) {
                if (!bundlesTbody) return true;

                let valid = true;
                Array.from(bundlesTbody.querySelectorAll('.bundle-row')).forEach(tr => {
                    const hiddenItem = tr.querySelector('input[name*="[finished_item_id]"]');
                    const itemInput = tr.querySelector('.js-item-suggest-input');
                    const hasItem = !!(hiddenItem && hiddenItem.value);

                    tr.classList.toggle('bundle-row-item-missing', !hasItem);
                    if (itemInput) {
                        if (hasItem) {
                            itemInput.classList.remove('is-invalid');
                        } else if (markInvalid) {
                            itemInput.classList.add('is-invalid');
                        }
                    }

                    if (!hasItem) valid = false;
                });

                return valid;
            }

            function refreshSubmitState(markInvalid = false) {
                const ok = validateBundleItems(markInvalid);
                if (submitBtn) {
                    submitBtn.disabled = !ok;
                    submitBtn.title = ok ? '' : 'Pilih item jadi dulu di semua baris hasil cutting.';
                }
                return ok;
            }

            function focusFirstMissingItem() {
                const firstInvalid = bundlesTbody?.querySelector('.bundle-row .js-item-suggest-input.is-invalid')
                    || bundlesTbody?.querySelector('.bundle-row-item-missing .js-item-suggest-input');
                if (!firstInvalid) return;

                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => firstInvalid.focus(), 120);
            }

            function attachRowListeners(tr) {
                tr.querySelector('.btn-remove-row')?.addEventListener('click', () => {
                    tr.remove();
                    updateBundleRowIndices();
                    rebuildLotOptionsForAllRows();
                    redistributeUsedByLot();
                    refreshSubmitState(true);
                });

                tr.querySelector('.bundle-qty')?.addEventListener('input', () => {
                    clampQtyInteger(tr);
                    redistributeUsedByLot();
                });

                tr.querySelector('.bundle-lot-select')?.addEventListener('change', () => {
                    redistributeUsedByLot();
                });

                // perubahan item-suggest biasanya update hidden finished_item_id
                tr.addEventListener('change', (e) => {
                    if (e.target && String(e.target.name || '').includes('[finished_item_id]')) {
                        const itemInput = tr.querySelector('.js-item-suggest-input');
                        if (e.target.value && itemInput) {
                            itemInput.classList.remove('is-invalid');
                        }
                        redistributeUsedByLot();
                        refreshSubmitState(true);

                        if (e.target.value) {
                            setTimeout(() => {
                                const qty = tr.querySelector('.bundle-qty');
                                qty?.focus();
                                qty?.select?.();
                            }, 40);
                        }
                    }
                }, true);

                const hiddenItem = tr.querySelector('input[name*="[finished_item_id]"]');
                const itemInput = tr.querySelector('.js-item-suggest-input');
                if (itemInput) {
                    itemInput.addEventListener('input', () => {
                        if (hiddenItem) hiddenItem.value = '';
                        itemInput.classList.add('is-invalid');
                        refreshSubmitState(true);
                    });

                    itemInput.addEventListener('blur', () => {
                        if (!hiddenItem?.value) itemInput.classList.add('is-invalid');
                        refreshSubmitState(true);
                    });

                    itemInput.addEventListener('keydown', (e) => {
                        if (e.key !== 'Enter') return;
                        e.preventDefault();

                        if (hiddenItem?.value) {
                            tr.querySelector('.bundle-qty')?.focus();
                        } else {
                            itemInput.classList.add('is-invalid');
                            refreshSubmitState(true);
                        }
                    });
                }

                // init
                clampQtyInteger(tr);
            }

            function createBundleRow() {
                if (!bundlesTbody) return;
                const tpl = document.getElementById('bundle-row-template');
                if (!tpl) return;

                const idx = bundleIndexCounter++;
                const html = tpl.innerHTML
                    .replaceAll('__INDEX__', String(idx))
                    .replaceAll('__NO__', String(idx + 1));

                const temp = document.createElement('tbody');
                temp.innerHTML = html.trim();
                const tr = temp.querySelector('tr');
                if (!tr) return;

                bundlesTbody.appendChild(tr);

                // init item-suggest
                if (window.initItemSuggestInputs) window.initItemSuggestInputs(tr);

                attachRowListeners(tr);
                updateBundleRowIndices();
                rebuildLotOptionsForAllRows();
                redistributeUsedByLot();
                refreshSubmitState(true);

                setTimeout(() => {
                    tr.querySelector('.js-item-suggest-input')?.focus();
                }, 60);
            }

            // =========================
            // INIT
            // =========================
            if (bundlesTbody) {
                Array.from(bundlesTbody.querySelectorAll('.bundle-row')).forEach(tr => attachRowListeners(tr));
                updateBundleRowIndices();
            }

            if (window.initItemSuggestInputs) window.initItemSuggestInputs(document);

            // checkbox mode only when unlocked
            if (!lotsLocked) {
                lotCheckboxes.forEach(cb => {
                    cb.addEventListener('change', () => {
                        const ok = enforceSingleFabricForCheckedLots(cb);
                        if (!ok) return;
                        setSelectedLotsHidden(getCheckedLots());
                        rebuildLotOptionsForAllRows();
                        updateCurrentLotSummary();
                        redistributeUsedByLot();
                    });
                });

                btnSelectAllLots?.addEventListener('click', () => {
                    lotCheckboxes.forEach(cb => cb.checked = true);
                    enforceSingleFabricForCheckedLots();
                    setSelectedLotsHidden(getCheckedLots());
                    rebuildLotOptionsForAllRows();
                    updateCurrentLotSummary();
                    redistributeUsedByLot();
                });

                btnUnselectAllLots?.addEventListener('click', () => {
                    lotCheckboxes.forEach(cb => cb.checked = false);
                    setSelectedLotsHidden([]);
                    rebuildLotOptionsForAllRows();
                    updateCurrentLotSummary();
                    redistributeUsedByLot();
                });

                btnConfirmLots?.addEventListener('click', () => {
                    const checked = getCheckedLots();
                    if (!checked.length) return alert('Pilih minimal satu LOT terlebih dahulu.');
                    const ok = enforceSingleFabricForCheckedLots();
                    if (!ok) return;

                    setSelectedLotsHidden(checked);
                    rebuildLotOptionsForAllRows();
                    updateCurrentLotSummary();
                    showMainContent();
                    redistributeUsedByLot();
                });

                btnChangeLots?.addEventListener('click', () => showPickLotSection());
            } else {
                // locked: pastikan hidden selected_lots selalu ada
                setSelectedLotsHidden(getCheckedLots());
            }

            btnAddRow?.addEventListener('click', () => createBundleRow());

            // open default
            rebuildLotOptionsForAllRows();
            updateCurrentLotSummary();

            // ✅ ini yang bikin “item cutting muncul” saat locked
            if (lotsLocked) showMainContent();
            else if (getCheckedLots().length > 0) showMainContent();

            // hitung used awal (planned ÷ baris)
            redistributeUsedByLot();
            refreshSubmitState(true);

            // safety sebelum submit
            form?.addEventListener('submit', (e) => {
                if (!refreshSubmitState(true)) {
                    e.preventDefault();
                    focusFirstMissingItem();
                    return;
                }

                // pastikan selected_lots[] kebentuk
                setSelectedLotsHidden(getCheckedLots());
                // pastikan used sesuai
                redistributeUsedByLot();
            });
        });
    </script>
@endpush
