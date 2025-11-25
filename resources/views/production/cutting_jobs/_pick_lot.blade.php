{{-- resources/views/production/cutting_jobs/_pick_lot.blade.php --}}

<div class="card p-3 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <div>
            <h1 class="h5 mb-0">Pilih Sumber Kain yang Dipotong</h1>
            <div class="help">
                Tahap pertama, pilih dulu LOT kain yang akan dipotong. Setelah itu baru isi output cutting.
            </div>
        </div>

        {{-- Search simple untuk cari LOT / Item / Gudang --}}
        <div style="max-width: 260px; width: 100%;">
            <input type="text" id="lot-search" class="form-control form-control-sm"
                placeholder="Cari LOT / item / gudang...">
        </div>
    </div>

    @if ($lotStocks->isEmpty())
        <div class="alert alert-warning mb-0">
            Belum ada LOT dengan saldo stok &gt; 0. Silakan cek mutasi penerimaan kain terlebih dahulu.
        </div>
    @else
        <div class="row g-2" id="lot-grid">
            @foreach ($lotStocks as $row)
                @php
                    $lot = $row->lot;
                    $warehouse = $row->warehouse;
                    $item = $lot?->item;
                    $qty = $row->qty_balance;
                @endphp

                @if ($lot && $warehouse && $item)
                    <div class="col-md-4 lot-card-wrap"
                        data-search-text="{{ Str::upper($lot->code . ' ' . $warehouse->code . ' ' . ($item->code ?? ($item->sku ?? ''))) }}">
                        <div class="lot-card h-100">
                            <div>
                                <div class="lot-card-header">
                                    <div class="fw-semibold">
                                        {{ $lot->code ?? 'LOT-' . $lot->id }}
                                    </div>
                                    <span class="badge-soft bg-light text-muted">
                                        {{ $warehouse->code }}
                                    </span>
                                </div>
                                <div class="small text-muted">
                                    {{ $item->code ?? ($item->sku ?? 'ITEM-' . $item->id) }}
                                </div>
                                <div class="small mono mt-1">
                                    Sisa: {{ number_format($qty, 2, ',', '.') }}
                                </div>
                            </div>

                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                <span class="help">
                                    Input output cutting dari LOT ini.
                                </span>
                                <a href="{{ route('production.cutting_jobs.create', ['lot_id' => $row->lot_id]) }}"
                                    class="btn btn-sm btn-primary">
                                    Input Outputs
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
    <script>
        (function() {
            const searchInput = document.getElementById('lot-search');
            const cards = document.querySelectorAll('.lot-card-wrap');

            if (!searchInput || !cards.length) return;

            function applyFilter() {
                const q = searchInput.value
                    .toString()
                    .trim()
                    .toUpperCase();

                cards.forEach(card => {
                    const text = card.getAttribute('data-search-text') || '';
                    if (!q) {
                        card.style.display = '';
                    } else {
                        card.style.display = text.includes(q) ? '' : 'none';
                    }
                });
            }

            searchInput.addEventListener('input', applyFilter);
        })();
    </script>
@endpush
