@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $rp = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $typeBadge = fn($t) => $t === 'Ambil' ? 'gf-badge-amber' : 'gf-badge-green';

    // ---- Ringkasan KPI (dihitung dari koleksi $rows, per transaksi) ----
    $txCount = $rows->count();
    $penjahitCount = $rows->pluck('operator_code')->unique()->count();
    $totalSetorOk = (float) $rows->where('type', 'Setor')->sum('qty_ok');
    $totalReject = (float) $rows->where('type', 'Setor')->sum('qty_reject');
    $totalUpah = (float) $rows->sum('amount');
    $totalQty = (float) $rows->sum('qty');
    // Total upah termasuk estimasi Ambil (rate × qty) — HANYA utk footer & area filter, bukan KPI.
    $totalUpahProj = (float) $rows->sum(fn($r) => $r->type === 'Setor' ? $r->amount : $r->rate * $r->qty);

    $penjahitOptions = $rows
        ->map(fn($r) => ['code' => $r->operator_code, 'name' => $r->operator_name])
        ->unique('code')
        ->sortBy('code')
        ->values();
@endphp

<div class="gf-overview-kpi-grid">
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong">
        <div class="gf-overview-kpi-label">Penjahit Aktif</div>
        <div class="gf-overview-kpi-value" data-pj-kpi-penjahit>{{ $fmt($penjahitCount) }}</div>
        <div class="gf-overview-kpi-note"><span data-pj-kpi-tx>{{ $fmt($txCount) }}</span> transaksi periode</div>
    </div>
    <div class="gf-overview-kpi-card gf-hide-mobile">
        <div class="gf-overview-kpi-label">Total Setor OK</div>
        <div class="gf-overview-kpi-value" data-pj-kpi-ok>{{ $fmt($totalSetorOk) }}</div>
        <div class="gf-overview-kpi-note">pcs lolos QC disetor</div>
    </div>
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong">
        <div class="gf-overview-kpi-label">Total Upah Jahit</div>
        <div class="gf-overview-kpi-value" data-pj-kpi-upah>{{ $rp($totalUpah) }}</div>
        <div class="gf-overview-kpi-note">borongan dari setoran OK</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Total Reject</div>
        <div class="gf-overview-kpi-value" data-pj-kpi-reject>{{ $fmt($totalReject) }}</div>
        <div class="gf-overview-kpi-note">pcs gagal QC saat setor</div>
    </div>
</div>

<x-gf.panel title="Aktivitas Penjahit" subtitle="Rincian per transaksi — ambil &amp; setor jahit + upah borongan">
    @if (!$rows->isEmpty())
        <x-slot:actions>
            <div class="pj-head-filters">
                <input type="search" class="form-control gf-head-control pj-head-search" data-pj-search
                    placeholder="Cari penjahit / SKU / dokumen" autocomplete="off">

                <select class="form-select gf-head-control" data-pj-type aria-label="Jenis">
                    <option value="">Semua Jenis</option>
                    <option value="Ambil">Ambil</option>
                    <option value="Setor">Setor</option>
                </select>

                <select class="form-select gf-head-control" data-pj-operator aria-label="Penjahit">
                    <option value="">Semua Penjahit</option>
                    @foreach ($penjahitOptions as $op)
                        <option value="{{ $op['code'] }}">{{ $op['code'] }} — {{ $op['name'] }}</option>
                    @endforeach
                </select>

                <span class="gf-head-count" data-pj-count>
                    {{ $fmt($txCount) }} transaksi · {{ $fmt($penjahitCount) }} penjahit · {{ $rp($totalUpahProj) }}
                </span>
            </div>
        </x-slot:actions>
    @endif

    @if ($rows->isEmpty())
        <div class="prod-empty">Tidak ada aktivitas penjahit pada periode ini.</div>
    @else

        <div class="gf-table-scroll gf-table-scroll-sticky">
            <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table" data-pj-table>
                <thead class="gf-sticky-thead">
                    <tr class="gf-sticky-head-row">
                        <th data-pj-sort-key="date" aria-sort="descending"><button type="button" class="gf-sort-th">Tanggal</button></th>
                        <th data-pj-sort-key="operator"><button type="button" class="gf-sort-th">Penjahit</button></th>
                        <th data-pj-sort-key="type"><button type="button" class="gf-sort-th">Jenis</button></th>
                        <th data-pj-sort-key="sku"><button type="button" class="gf-sort-th">SKU</button></th>
                        <th class="gf-hide-mobile">Produk</th>
                        <th class="gf-num" data-pj-sort-key="qty"><button type="button" class="gf-sort-th">Qty / OK</button></th>
                        <th class="gf-num gf-hide-mobile" data-pj-sort-key="reject"><button type="button" class="gf-sort-th">Reject</button></th>
                        <th class="gf-num gf-hide-mobile">Piece Rate</th>
                        <th class="gf-num" data-pj-sort-key="amount"><button type="button" class="gf-sort-th">Total Upah</button></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        <tr data-pj-row
                            data-search="{{ strtolower(trim($r->operator_code . ' ' . $r->operator_name . ' ' . $r->sku . ' ' . $r->product_name . ' ' . $r->category . ' ' . $r->code)) }}"
                            data-operator="{{ $r->operator_code }}"
                            data-operator-name="{{ strtolower($r->operator_name) }}"
                            data-operator-label="{{ $r->operator_code }} — {{ $r->operator_name }}"
                            data-type="{{ $r->type }}"
                            data-date="{{ $r->date }}"
                            data-sku="{{ strtolower($r->sku) }}"
                            data-product="{{ strtolower($r->product_name) }}"
                            data-qty="{{ (float) $r->qty }}"
                            data-reject="{{ (float) $r->qty_reject }}"
                            data-rate="{{ (float) $r->rate }}"
                            data-amount="{{ (float) $r->amount }}"
                            data-proj="{{ (float) ($r->type === 'Setor' ? $r->amount : $r->rate * $r->qty) }}">
                            <td>
                                <x-gf.datecell :date="$r->date" :time="$r->created_at" />
                                <span class="gf-doc-code">{{ $r->code }}</span>
                            </td>
                            <td>
                                <span class="gf-chip" title="{{ $r->operator_name }}"><b>{{ $r->operator_code }}</b></span>
                                <span class="text-muted small d-block">{{ $r->operator_name }}</span>
                            </td>
                            <td><span class="gf-badge {{ $typeBadge($r->type) }}">{{ $r->type }}</span></td>
                            <td><span class="gf-chip" title="{{ $r->product_name }}"><b>{{ $r->sku }}</b></span></td>
                            <td class="text-muted gf-hide-mobile">{{ $r->product_name }}</td>
                            <td class="gf-num"><b>{{ $fmt($r->qty) }}</b></td>
                            <td class="gf-num gf-hide-mobile">{{ $r->qty_reject > 0 ? $fmt($r->qty_reject) : '-' }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $r->rate > 0 ? $rp($r->rate) : '–' }}</td>
                            <td class="gf-num">
                                @if ($r->type === 'Setor')
                                    {{ $r->amount > 0 ? $rp($r->amount) : '–' }}
                                @elseif ($r->rate > 0)
                                    <span class="text-muted" title="Perkiraan upah bila semua lolos QC (belum final)">~{{ $rp($r->rate * $r->qty) }}</span>
                                @else
                                    –
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="gf-total-row" data-pj-foot>
                        <td colspan="5" class="text-muted">Total Qty &amp; upah borongan (Setor real + perkiraan Ambil)</td>
                        <td class="gf-num"><b data-pj-foot-qty>{{ $fmt($totalQty) }}</b></td>
                        <td class="gf-num gf-hide-mobile"></td>
                        <td class="gf-num gf-hide-mobile"></td>
                        <td class="gf-num"><b data-pj-foot-amount>{{ $rp($totalUpahProj) }}</b></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="prod-empty" data-pj-empty hidden>Tidak ada transaksi yang cocok dengan filter.</div>
        <div class="gf-table-foot">
            <a class="gf-slip-btn" data-pj-slip-setor hidden target="_blank" rel="noopener">Slip Setor</a>
            <a class="gf-slip-btn" data-pj-slip-ambil hidden target="_blank" rel="noopener">Slip Ambil</a>
        </div>
    @endif
</x-gf.panel>
