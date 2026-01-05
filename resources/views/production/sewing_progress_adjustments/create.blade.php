@extends('layouts.app')

@section('title', 'Buat Sewing Progress Adjustment')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .85rem .85rem 4rem;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .cardx {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .22);
            box-shadow: 0 10px 26px rgba(15, 23, 42, .08), 0 0 0 1px rgba(15, 23, 42, .03);
        }

        .cardx .cardx-body {
            padding: .9rem 1rem;
        }

        @media(min-width:768px) {
            .page-wrap {
                padding: 1.1rem 1rem 4rem
            }

            .cardx .cardx-body {
                padding: 1rem 1.25rem
            }
        }

        .btn-pill {
            border-radius: 999px;
            padding: .25rem .75rem;
            font-size: .8rem;
            display: inline-flex;
            gap: .35rem;
            align-items: center;
        }

        .table thead th {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            border-top: none;
        }

        .table tbody td {
            font-size: .85rem;
        }

        .hint {
            font-size: .78rem;
            color: var(--muted);
        }

        .row-warn {
            background: rgba(254, 243, 199, .55);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
            <div>
                <div class="fw-bold">Buat Progress Adjustment</div>
                <div class="text-muted small">Update “progress” pickup (tanpa mutasi stok).</div>
            </div>

            <a href="{{ route('production.sewing.adjustments.index') }}" class="btn btn-outline-secondary btn-sm btn-pill">
                <i class="bi bi-arrow-left"></i><span>Kembali</span>
            </a>
        </div>

        {{-- PILIH PICKUP (GET) --}}
        <div class="cardx mb-2">
            <div class="cardx-body">
                <form method="GET" action="{{ route('production.sewing.adjustments.create') }}"
                    class="row g-2 align-items-end">
                    <div class="col-12 col-md-8">
                        <label class="form-label small text-muted mb-1">Sewing Pickup</label>
                        <select name="pickup_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Pilih Sewing Pickup --</option>
                            @foreach ($pickups as $p)
                                <option value="{{ $p->id }}" @selected(($pickupId ?? request('pickup_id')) == $p->id)>
                                    {{ $p->code }} • {{ $p->date }} • {{ $p->operator?->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="hint mt-1">Pilih pickup dulu untuk menampilkan line yang masih “Belum Setor”.</div>
                    </div>

                    <div class="col-12 col-md-4">
                        <button type="submit" class="btn btn-outline-secondary btn-sm btn-pill w-100">
                            <i class="bi bi-arrow-repeat"></i><span>Muat</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if ($pickup)
            {{-- FORM POST --}}
            <form method="POST" action="{{ route('production.sewing.adjustments.store') }}">
                @csrf
                <input type="hidden" name="pickup_id" value="{{ $pickup->id }}">

                <div class="cardx">
                    <div class="cardx-body">

                        <div class="row g-2 mb-2">
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1">Tanggal</label>
                                <input type="date" name="date" class="form-control form-control-sm"
                                    value="{{ old('date', now()->toDateString()) }}" required>
                            </div>
                            <div class="col-12 col-md-8">
                                <label class="form-label small text-muted mb-1">Pickup</label>
                                <div class="form-control form-control-sm d-flex align-items-center gap-2"
                                    style="background: transparent;">
                                    <span class="mono fw-bold">{{ $pickup->code }}</span>
                                    <span class="text-muted">•</span>
                                    <span class="text-muted small">{{ $pickup->operator?->name ?? '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th style="width:160px;">Item</th>
                                        <th>Bundle</th>
                                        <th class="text-end" style="width:110px;">Pickup</th>
                                        <th class="text-end" style="width:110px;">Returned</th>
                                        <th class="text-end" style="width:110px;">Dadakan</th>
                                        <th class="text-end" style="width:120px;">Belum</th>
                                        <th class="text-end" style="width:120px;">Adjust</th>
                                        <th style="width:220px;">Alasan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $hasRows = false; @endphp

                                    @foreach ($lines as $i => $l)
                                        @php
                                            $qtyBundle = (float) ($l->qty_bundle ?? 0);
                                            $returnedOk = (float) ($l->qty_returned_ok ?? 0);
                                            $returnedRj = (float) ($l->qty_returned_reject ?? 0);
                                            $directPick = (float) ($l->qty_direct_picked ?? 0);
                                            $progressAdj = (float) ($l->qty_progress_adjusted ?? 0);

                                            $remaining = isset($l->remaining_qty)
                                                ? (float) $l->remaining_qty
                                                : max(
                                                    $qtyBundle -
                                                        ($returnedOk + $returnedRj + $directPick + $progressAdj),
                                                    0,
                                                );

                                            if ($remaining <= 0.000001) {
                                                continue;
                                            }
                                            $hasRows = true;
                                        @endphp

                                        <tr class="row-warn">
                                            <td>
                                                <div class="mono fw-bold">{{ $l->bundle?->finishedItem?->code ?? '-' }}
                                                </div>
                                                <div class="small text-muted">{{ $l->bundle?->finishedItem?->name ?? '' }}
                                                </div>
                                            </td>
                                            <td class="mono">
                                                {{ $l->bundle?->bundle_code ?? '#' . $l->cutting_job_bundle_id }}</td>
                                            <td class="text-end mono">{{ number_format($qtyBundle, 2, ',', '.') }}</td>
                                            <td class="text-end mono">
                                                {{ number_format($returnedOk + $returnedRj, 2, ',', '.') }}</td>
                                            <td class="text-end mono">{{ number_format($directPick, 2, ',', '.') }}</td>
                                            <td class="text-end mono fw-bold">{{ number_format($remaining, 2, ',', '.') }}
                                            </td>

                                            <td class="text-end">
                                                <input type="number" step="0.01"
                                                    name="lines[{{ $i }}][qty_adjust]"
                                                    class="form-control form-control-sm text-end mono" placeholder="0"
                                                    value="{{ old("lines.$i.qty_adjust") }}">

                                                <input type="hidden"
                                                    name="lines[{{ $i }}][sewing_pickup_line_id]"
                                                    value="{{ $l->id }}">
                                            </td>

                                            <td>
                                                <input type="text" name="lines[{{ $i }}][reason]"
                                                    class="form-control form-control-sm"
                                                    placeholder="contoh: retur offline / missing input"
                                                    value="{{ old("lines.$i.reason") }}">
                                            </td>
                                        </tr>
                                    @endforeach

                                    @if (!$hasRows)
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-3">
                                                Tidak ada line “Belum Setor” untuk pickup ini.
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small text-muted mb-1">Catatan (opsional)</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Catatan umum dokumen...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button class="btn btn-success btn-sm btn-pill">
                                <i class="bi bi-check2-circle"></i><span>Simpan Adjustment</span>
                            </button>
                        </div>

                        <div class="hint mt-2">
                            Dokumen ini hanya mengubah angka progress (qty_progress_adjusted), bukan stok inventory.
                        </div>

                    </div>
                </div>
            </form>
        @endif

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('focusin', (e) => {
            const el = e.target;
            if (!el) return;
            if (el.tagName === 'INPUT' && el.type === 'number') {
                try {
                    el.select();
                } catch (_) {}
            }
        });
    </script>
@endpush
