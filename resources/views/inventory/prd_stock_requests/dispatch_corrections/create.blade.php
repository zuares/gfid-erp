@extends('layouts.app')

@section('title', 'PRD Dispatch Correction • ' . $stockRequest->code)

@push('head')
    <style>
        /* Hide number input spinners (Chrome/Safari/Edge) */
        input[type=number]::-webkit-outer-spin-button,
        input[type=number]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Hide number input spinners (Firefox) */
        input[type=number] {
            -moz-appearance: textfield;
        }

        .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        }

        .note-soft {
            background: rgba(45, 212, 191, .10);
            border: 1px solid rgba(45, 212, 191, .25);
        }

        .hint-mini {
            font-size: .82rem;
            opacity: .85;
        }

        .tbl-minw {
            min-width: 980px;
        }

        .adj-input {
            max-width: 180px;
            margin-inline: auto;
        }
    </style>
@endpush

@section('content')
    <div class="container" style="max-width: 1100px; padding-bottom: 4.5rem;">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h4 class="mb-1" style="font-weight:900;">Dispatch Correction</h4>
                <div class="text-muted">
                    <span class="mono">{{ $stockRequest->code }}</span>
                    · PRD → TRANSIT (koreksi + / -)
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('prd.stock-requests.show', $stockRequest) }}" class="btn btn-outline-secondary">←
                    Kembali</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <b>Validasi gagal:</b>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="alert note-soft">
            <b>Aturan:</b>
            <div class="mt-1">
                Kalau barang sudah diterima, bagian itu <b>tidak bisa dibalikin</b>.
            </div>
        </div>

        <form method="POST" action="{{ route('prd.stock-requests.dispatch_corrections.store', $stockRequest) }}">
            @csrf

            <div class="card mb-3">
                <div class="card-body d-flex flex-wrap gap-3 align-items-end">
                    <div>
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="date" class="form-control"
                            value="{{ old('date', optional($stockRequest->date)->toDateString() ?? now()->toDateString()) }}">
                    </div>

                    <div style="flex:1; min-width: 260px;">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes') }}"
                            placeholder="Misal: salah input qty, koreksi dispatch">
                    </div>

                    <div class="ms-auto d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" id="btnClear">Kosongkan</button>
                        <button type="submit" class="btn btn-primary">Simpan Correction</button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <b>Daftar Item</b>
                    <span class="text-muted">{{ $stockRequest->lines->count() }} item</span>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0 tbl-minw">
                        <thead>
                            <tr class="text-uppercase text-muted" style="font-size: .75rem;">
                                <th style="width:60px;">No</th>
                                <th>Item</th>
                                <th class="text-end">Requested</th>
                                <th class="text-end">Dispatched</th>
                                <th class="text-end">Received</th>
                                <th class="text-end">Max Revert</th>
                                <th style="width:240px;" class="text-center">Qty Adjust (+ / -)</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($stockRequest->lines as $i => $line)
                                @php
                                    $req = (int) round((float) ($line->qty_request ?? 0));
                                    $disp = (int) round((float) ($line->qty_dispatched ?? 0));
                                    $recv = (int) round((float) ($line->qty_received ?? 0));

                                    $maxRevert = (int) round((float) ($maxRevertByLine[$line->id] ?? 0));
                                    $liveTransit = (int) round((float) ($liveTransitByLine[$line->id] ?? 0));

                                    $old = old("lines.{$line->id}.qty_adjust", 0);
                                    $old = (int) round((float) $old);
                                @endphp

                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>

                                    <td>
                                        <div class="mono" style="font-weight:800;">{{ $line->item->code }}</div>
                                        <div class="text-muted" style="font-size:.9rem;">{{ $line->item->name }}</div>
                                        <div class="text-muted hint-mini">
                                            Transit live: <span class="mono">{{ $liveTransit }}</span>
                                        </div>
                                    </td>

                                    <td class="text-end mono">{{ $req }}</td>
                                    <td class="text-end mono">{{ $disp }}</td>
                                    <td class="text-end mono">{{ $recv }}</td>

                                    <td class="text-end mono">
                                        {{ $maxRevert }}
                                    </td>

                                    <td class="text-center">
                                        <div class="adj-input">
                                            <input class="form-control text-center mono js-adj" type="number"
                                                inputmode="numeric" step="1" min="-{{ $maxRevert }}"
                                                name="lines[{{ $line->id }}][qty_adjust]" value="{{ $old }}"
                                                data-max-revert="{{ $maxRevert }}" placeholder="contoh: 5 atau -2"
                                                title="Minus minimal: -{{ $maxRevert }} (maks balik). Plus bebas.">


                                            @error("lines.{$line->id}.qty_adjust")
                                                <div class="text-danger mt-1" style="font-size:.85rem;">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @error('stock')
                    <div class="p-3">
                        <div class="alert alert-danger mb-0">
                            <b>Error stock:</b> {{ $message }}
                        </div>
                    </div>
                @enderror
            </div>
        </form>
    </div>

    <script>
        (function() {
            const inputs = Array.from(document.querySelectorAll('.js-adj'));

            function toInt(x) {
                const s = String(x ?? '').trim().replace(',', '.');
                const n = parseFloat(s);
                if (!Number.isFinite(n)) return 0;
                return Math.trunc(n);
            }

            function clamp(el) {
                const maxRevert = toInt(el.dataset.maxRevert);
                let v = toInt(el.value);

                // minus tidak boleh < -maxRevert
                if (v < -maxRevert) v = -maxRevert;

                // plus bebas, tapi pastikan integer
                el.value = String(v);
            }

            function selectAll(el) {
                if (!el) return;
                setTimeout(() => {
                    try {
                        el.select();
                        if (el.setSelectionRange) {
                            const len = el.value.length;
                            el.setSelectionRange(0, len);
                        }
                    } catch (e) {}
                }, 0);
            }

            inputs.forEach(el => {
                // Auto select ketika focus/click
                el.addEventListener('focus', (e) => selectAll(e.target));
                el.addEventListener('click', (e) => {
                    if (document.activeElement === e.target) selectAll(e.target);
                });

                // Clamp ke integer & batas revert
                el.addEventListener('input', () => {
                    // biar user bisa ketik "-" dulu, jangan dipaksa jadi 0 saat masih "-"
                    const raw = String(el.value ?? '').trim();
                    if (raw === '-' || raw === '') return;
                    // buang desimal kalau user paste/ketik
                    el.value = String(toInt(raw));
                });

                el.addEventListener('blur', () => clamp(el));
                el.addEventListener('change', () => clamp(el));
            });

            document.getElementById('btnClear')?.addEventListener('click', () => {
                inputs.forEach(el => el.value = '0');
            });
        })();
    </script>
@endsection
