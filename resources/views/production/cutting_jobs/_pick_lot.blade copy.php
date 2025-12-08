{{-- resources/views/production/cutting_jobs/_pick_lot.blade.php --}}

@php
    use Illuminate\Support\Str;
@endphp

@push('head')
    <style>
        .lot-picker-header {
            display: flex;
            flex-direction: column;
            gap: .35rem;
            margin-bottom: .9rem;
        }

        @media (min-width: 576px) {
            .lot-picker-header {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        .lot-search-input {
            max-width: 260px;
        }

        .lot-grid {
            display: grid;
            gap: .75rem;
        }

        @media (min-width: 576px) {
            .lot-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 992px) {
            .lot-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        /* Card LOT – minimal & elegan */
        .lot-card-modern {
            border-radius: 8px;
            border: 1px solid var(--line);
            background: color-mix(in srgb, var(--card) 94%, var(--line) 6%);
            padding: .9rem;
            transition: border-color .14s ease-out, background-color .14s ease-out;
        }

        .lot-card-modern:hover {
            border-color: rgba(59, 130, 246, .65);
            background: color-mix(in srgb, var(--card) 97%, var(--line) 3%);
        }

        .lot-chip {
            border-radius: 999px;
            padding: .12rem .6rem;
            font-size: .7rem;
            border: 1px solid var(--line);
            background: transparent;
        }

        .lot-name {
            font-size: 1rem;
            font-weight: 600;
        }

        .lot-meta {
            font-size: .78rem;
            color: var(--muted);
        }

        /* ------------------------------ */
        /* 🔥 TOMBOL MINIMALIS (HP)       */
        /* ------------------------------ */

        .btn-lot-cta {
            width: 100%;
            padding: .65rem 0.9rem;
            /* lebih tipis = lebih elegan */
            border-radius: 4px;
            /* ⬅ kotak minimalis */
            font-size: .9rem;
            font-weight: 600;

            /* Style minimal */
            background: var(--primary);
            border: 1px solid var(--primary);
            box-shadow: none;
        }

        .btn-lot-cta:hover {
            background: color-mix(in srgb, var(--primary) 88%, black 12%);
            border-color: color-mix(in srgb, var(--primary) 88%, black 12%);
        }

        .lot-empty-card {
            border-radius: 8px;
            border: 1px dashed var(--line);
            padding: 1rem .9rem;
            text-align: center;
        }

        .lot-qty-label {
            font-size: .75rem;
            color: var(--muted);
        }

        .lot-qty-value {
            font-size: 1.1rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }
    </style>
@endpush


<div class="mb-2">

    {{-- Header + search --}}
    <div class="lot-picker-header">
        <div>
            {{-- lebih kecil karena di dalam cutting-card --}}
            <h2 class="h6 mb-1">Daftar LOT Tersedia</h2>
            <p class="help mb-0">
                Pilih <strong>LOT yang masih ada sisa</strong> dalam Kg untuk dipakai di Cutting Job.
            </p>
        </div>

        @if ($lotStocks->isNotEmpty())
            <div class="mt-2 mt-sm-0">
                <input type="search" id="lot-search" class="form-control form-control-sm lot-search-input"
                    placeholder="Cari LOT / nama kain...">
            </div>
        @endif
    </div>

    {{-- List LOT --}}
    @if ($lotStocks->isEmpty())
        <div class="lot-empty-card">
            <div class="mb-1 fw-semibold">Belum ada LOT tersedia</div>
            <p class="help mb-2">
                Pastikan stok kain di gudang RM masih ada (cek modul GRN / Goods Receipt).
            </p>
        </div>
    @else
        <div class="lot-grid" id="lot-grid">

            @foreach ($lotStocks as $row)
                @php
                    $lot = $row->lot;
                    $item = $lot?->item;
                    $warehouse = $row->warehouse;
                    $qtyKg = (float) $row->qty_balance;
                @endphp

                <div class="lot-card lot-card-modern lot-card-item"
                    data-search="{{ Str::lower(($lot->code ?? '') . ' ' . ($item->name ?? '') . ' ' . ($item->code ?? '')) }}">

                    {{-- Nama kain --}}
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="lot-name">{{ $item->name }}</div>
                            <div class="lot-meta">
                                {{ $item->code }} • LOT {{ $lot->code }}
                            </div>
                        </div>
                        <span class="lot-chip">{{ $warehouse->code }}</span>
                    </div>

                    {{-- Sisa KG --}}
                    <div class="mb-3">
                        <div class="lot-qty-label">Sisa (Kg)</div>
                        <div class="lot-qty-value">{{ decimal_id($qtyKg) }} kg</div>
                    </div>

                    {{-- Tombol full width – minimalis --}}
                    <a href="{{ route('production.cutting_jobs.create', ['lot_id' => $row->lot_id]) }}"
                        class="btn btn-primary btn-lot-cta">
                        Pakai LOT Ini
                    </a>

                </div>
            @endforeach

        </div>

    @endif

</div>


@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('lot-search');
            const cards = document.querySelectorAll('.lot-card-item');

            if (!input) return;

            input.addEventListener('input', function(e) {
                const q = e.target.value.toLowerCase().trim();
                cards.forEach(card => {
                    const data = card.getAttribute('data-search') || '';
                    card.style.display = (!q || data.includes(q)) ? '' : 'none';
                });
            });
        });
    </script>
@endpush
