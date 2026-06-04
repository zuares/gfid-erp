@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $rp = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');

    // ---- Ringkasan KPI (dihitung dari koleksi $rows, per transaksi) ----
    $txCount = $rows->count();
    $pemotongCount = $rows->pluck('operator_code')->unique()->count();
    $totalOk = (float) $rows->sum('qty_ok');
    $totalReject = (float) $rows->sum('qty_reject');
    $totalUpah = (float) $rows->sum('amount');
    $totalQty = (float) $rows->sum('qty');

    // Opsi filter pemotong (kode → nama)
    $pemotongOptions = $rows
        ->map(fn($r) => ['code' => $r->operator_code, 'name' => $r->operator_name])
        ->unique('code')
        ->sortBy('code')
        ->values();
@endphp

<div class="gf-overview-kpi-grid">
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong">
        <div class="gf-overview-kpi-label">Pemotong Aktif</div>
        <div class="gf-overview-kpi-value" data-cg-kpi-operator>{{ $fmt($pemotongCount) }}</div>
        <div class="gf-overview-kpi-note"><span data-cg-kpi-tx>{{ $fmt($txCount) }}</span> transaksi periode</div>
    </div>
    <div class="gf-overview-kpi-card gf-hide-mobile">
        <div class="gf-overview-kpi-label">Total Cutting OK</div>
        <div class="gf-overview-kpi-value" data-cg-kpi-ok>{{ $fmt($totalOk) }}</div>
        <div class="gf-overview-kpi-note">pcs lolos QC cutting</div>
    </div>
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong">
        <div class="gf-overview-kpi-label">Total Upah Cutting</div>
        <div class="gf-overview-kpi-value" data-cg-kpi-upah>{{ $rp($totalUpah) }}</div>
        <div class="gf-overview-kpi-note">borongan dari hasil OK</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Total Reject</div>
        <div class="gf-overview-kpi-value" data-cg-kpi-reject>{{ $fmt($totalReject) }}</div>
        <div class="gf-overview-kpi-note">pcs gagal QC cutting</div>
    </div>
</div>

<x-gf.panel title="Aktivitas Cutting" subtitle="Rincian per transaksi — hasil potong per bundle + upah borongan">
    {{-- Filter realtime (client-side, instan) --}}
    <div class="sj-toolbar" data-cg-toolbar>
        <input type="search" class="form-control sj-search" data-cg-search
            placeholder="Cari pemotong / SKU / dokumen…" autocomplete="off">

        <select class="form-select" data-cg-operator aria-label="Pemotong">
            <option value="">Semua Pemotong</option>
            @foreach ($pemotongOptions as $op)
                <option value="{{ $op['code'] }}">{{ $op['code'] }} — {{ $op['name'] }}</option>
            @endforeach
        </select>

        <select class="form-select" data-cg-sort aria-label="Urutkan">
            <option value="date-desc">Terbaru</option>
            <option value="qty-desc">Qty terbanyak</option>
            <option value="amount-desc">Upah terbesar</option>
            <option value="reject-desc">Reject terbanyak</option>
        </select>

        <span class="sj-count" data-cg-count>{{ $fmt($txCount) }} transaksi · {{ $fmt($pemotongCount) }} pemotong · {{ $rp($totalUpah) }}</span>
    </div>

    @if ($rows->isEmpty())
        <div class="prod-empty">Tidak ada aktivitas cutting pada periode ini.</div>
    @else

        <div class="gf-table-scroll gf-table-scroll-sticky">
            <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table" data-cg-table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Pemotong</th>
                        <th>SKU</th>
                        <th class="gf-hide-mobile">Produk</th>
                        <th class="gf-num">Qty Potong</th>
                        <th class="gf-num">OK</th>
                        <th class="gf-num gf-hide-mobile">Reject</th>
                        <th class="gf-num gf-hide-mobile">Piece Rate</th>
                        <th class="gf-num">Total Upah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        <tr data-cg-row
                            data-search="{{ strtolower(trim($r->operator_code . ' ' . $r->operator_name . ' ' . $r->sku . ' ' . $r->product_name . ' ' . $r->category . ' ' . $r->code)) }}"
                            data-operator="{{ $r->operator_code }}"
                            data-date="{{ $r->date }}"
                            data-qty="{{ (float) $r->qty }}"
                            data-ok="{{ (float) $r->qty_ok }}"
                            data-reject="{{ (float) $r->qty_reject }}"
                            data-amount="{{ (float) $r->amount }}">
                            <td><x-gf.datecell :date="$r->date" :time="$r->created_at" /></td>
                            <td>
                                <span class="gf-chip" title="{{ $r->operator_name }}"><b>{{ $r->operator_code }}</b></span>
                                <span class="text-muted small d-block">{{ $r->operator_name }}</span>
                            </td>
                            <td><span class="gf-chip" title="{{ $r->product_name }}"><b>{{ $r->sku }}</b></span></td>
                            <td class="text-muted gf-hide-mobile">{{ $r->product_name }}</td>
                            <td class="gf-num"><b>{{ $fmt($r->qty) }}</b></td>
                            <td class="gf-num">{{ $fmt($r->qty_ok) }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $r->qty_reject > 0 ? $fmt($r->qty_reject) : '-' }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $r->rate > 0 ? $rp($r->rate) : '–' }}</td>
                            <td class="gf-num">{{ $r->amount > 0 ? $rp($r->amount) : '–' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="gf-total-row" data-cg-foot>
                        <td colspan="4" class="text-muted">Total Qty &amp; upah borongan (upah dari hasil OK)</td>
                        <td class="gf-num"><b data-cg-foot-qty>{{ $fmt($totalQty) }}</b></td>
                        <td class="gf-num"></td>
                        <td class="gf-num gf-hide-mobile"></td>
                        <td class="gf-num gf-hide-mobile"></td>
                        <td class="gf-num"><b data-cg-foot-amount>{{ $rp($totalUpah) }}</b></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="prod-empty" data-cg-empty hidden>Tidak ada transaksi yang cocok dengan filter.</div>
        <div class="gf-table-foot">
            <span class="gf-table-foot-hint" data-cg-slip-hint hidden>Pilih satu pemotong untuk mencetak slip upah.</span>
            <a class="gf-slip-btn" data-cg-slip hidden target="_blank" rel="noopener">Cetak Slip</a>
        </div>
    @endif
</x-gf.panel>
