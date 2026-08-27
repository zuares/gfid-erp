@php
    $fmt = fn($n, $d = 0) => number_format((float) $n, $d, ',', '.');
    $rp = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $roleBadge = fn($role) => $role === 'Cutting' ? 'gf-badge-amber' : 'gf-badge-green';
    // Upah jahit berasal dari Ambil Jahit; upah cutting berasal dari hasil QC OK.
    $projOf = fn($r) => $r->amount;

    // ---- Ringkasan KPI (gabungan jahit + cutting, per transaksi) ----
    $txCount = $rows->count();
    $operatorCount = $rows->pluck('operator_code')->unique()->count();
    $upahJahit = (float) $rows->where('module', 'sewing')->sum('amount');
    $upahCutting = (float) $rows->where('module', 'cutting')->sum('amount');
    $upahGabungan = $upahJahit + $upahCutting;
    $totalQty = (float) $rows->sum('qty');
    // Total upah berdasarkan basis pembayaran masing-masing modul.
    $totalUpahProj = (float) $rows->sum($projOf);

    // Opsi filter operator (kode → nama, peran)
    $operatorOptions = $rows
        ->map(fn($r) => ['code' => $r->operator_code, 'name' => $r->operator_name])
        ->unique('code')
        ->sortBy('code')
        ->values();
@endphp

<div class="gf-overview-kpi-grid">
    <div class="gf-overview-kpi-card gf-overview-kpi-card-strong">
        <div class="gf-overview-kpi-label">Total Upah Gabungan</div>
        <div class="gf-overview-kpi-value" data-ks-kpi-total>{{ $rp($upahGabungan) }}</div>
        <div class="gf-overview-kpi-note">jahit Ambil + cutting QC OK</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Upah Jahit</div>
        <div class="gf-overview-kpi-value" data-ks-kpi-jahit>{{ $rp($upahJahit) }}</div>
        <div class="gf-overview-kpi-note">borongan jahit (Ambil Jahit)</div>
    </div>
    <div class="gf-overview-kpi-card">
        <div class="gf-overview-kpi-label">Upah Cutting</div>
        <div class="gf-overview-kpi-value" data-ks-kpi-cutting>{{ $rp($upahCutting) }}</div>
        <div class="gf-overview-kpi-note">borongan cutting (hasil OK)</div>
    </div>
    <div class="gf-overview-kpi-card gf-hide-mobile">
        <div class="gf-overview-kpi-label">Operator Aktif</div>
        <div class="gf-overview-kpi-value" data-ks-kpi-operator>{{ $fmt($operatorCount) }}</div>
        <div class="gf-overview-kpi-note"><span data-ks-kpi-tx>{{ $fmt($txCount) }}</span> transaksi periode</div>
    </div>
</div>

<x-gf.panel title="Aktivitas Borongan (Gabungan)" subtitle="Semua transaksi jahit &amp; cutting dalam satu daftar + upah borongan">
    {{-- Filter realtime (client-side, instan) --}}
    <div class="sj-toolbar" data-ks-toolbar>
        <input type="search" class="form-control sj-search" data-ks-search
            placeholder="Cari operator / SKU / dokumen…" autocomplete="off">

        <select class="form-select" data-ks-role aria-label="Peran">
            <option value="">Semua Peran</option>
            <option value="Jahit">Jahit</option>
            <option value="Cutting">Cutting</option>
        </select>

        <select class="form-select" data-ks-operator aria-label="Operator">
            <option value="">Semua Operator</option>
            @foreach ($operatorOptions as $op)
                <option value="{{ $op['code'] }}">{{ $op['code'] }} — {{ $op['name'] }}</option>
            @endforeach
        </select>

        <select class="form-select" data-ks-kind aria-label="Jenis">
            <option value="">Semua Jenis</option>
            <option value="Ambil">Ambil Jahit</option>
            <option value="Setor">Setor Jahit</option>
            <option value="Potong">Potong (Cutting)</option>
        </select>

        <select class="form-select" data-ks-sort aria-label="Urutkan">
            <option value="date-desc">Terbaru</option>
            <option value="qty-desc">Qty terbanyak</option>
            <option value="amount-desc">Upah terbesar</option>
        </select>

        <span class="sj-count" data-ks-count>{{ $fmt($txCount) }} transaksi · {{ $fmt($operatorCount) }} operator · {{ $rp($totalUpahProj) }}</span>
    </div>

    @if ($rows->isEmpty())
        <div class="prod-empty">Tidak ada aktivitas borongan pada periode ini.</div>
    @else

        <div class="gf-table-scroll gf-table-scroll-sticky">
            <table class="table table-hover align-middle mb-0 gf-clean-table gf-sticky-table" data-ks-table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Peran</th>
                        <th>Operator</th>
                        <th>Jenis</th>
                        <th>SKU</th>
                        <th class="gf-hide-mobile">Produk</th>
                        <th class="gf-num">Qty / OK</th>
                        <th class="gf-num gf-hide-mobile">Reject</th>
                        <th class="gf-num gf-hide-mobile">Piece Rate</th>
                        <th class="gf-num">Total Upah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        <tr data-ks-row
                            data-search="{{ strtolower(trim($r->operator_code . ' ' . $r->operator_name . ' ' . $r->sku . ' ' . $r->product_name . ' ' . $r->category . ' ' . $r->code . ' ' . $r->role)) }}"
                            data-role="{{ $r->role }}"
                            data-module="{{ $r->module }}"
                            data-operator="{{ $r->operator_code }}"
                            data-kind="{{ $r->kind }}"
                            data-date="{{ $r->date }}"
                            data-qty="{{ (float) $r->qty }}"
                            data-reject="{{ (float) $r->qty_reject }}"
                            data-amount="{{ (float) $r->amount }}"
                            data-proj="{{ (float) $projOf($r) }}">
                            <td><x-gf.datecell :date="$r->date" :time="$r->created_at" /></td>
                            <td><span class="gf-badge {{ $roleBadge($r->role) }}">{{ $r->role }}</span></td>
                            <td>
                                <span class="gf-chip" title="{{ $r->operator_name }}"><b>{{ $r->operator_code }}</b></span>
                                <span class="text-muted small d-block">{{ $r->operator_name }}</span>
                            </td>
                            <td><span class="text-muted small">{{ $r->kind }}</span></td>
                            <td><span class="gf-chip" title="{{ $r->product_name }}"><b>{{ $r->sku }}</b></span></td>
                            <td class="text-muted gf-hide-mobile">{{ $r->product_name }}</td>
                            <td class="gf-num"><b>{{ $fmt($r->qty) }}</b></td>
                            <td class="gf-num gf-hide-mobile">{{ $r->qty_reject > 0 ? $fmt($r->qty_reject) : '-' }}</td>
                            <td class="gf-num gf-hide-mobile">{{ $r->rate > 0 ? $rp($r->rate) : '–' }}</td>
                            <td class="gf-num">
                                @if ($r->kind === 'Ambil')
                                    @if ($r->amount > 0)
                                        {{ $rp($r->amount) }}
                                    @else
                                        –
                                    @endif
                                @else
                                    {{ $r->amount > 0 ? $rp($r->amount) : '–' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="gf-total-row" data-ks-foot>
                        <td colspan="6" class="text-muted">Total Qty &amp; upah borongan (Ambil Jahit + Cutting QC OK)</td>
                        <td class="gf-num"><b data-ks-foot-qty>{{ $fmt($totalQty) }}</b></td>
                        <td class="gf-num gf-hide-mobile"></td>
                        <td class="gf-num gf-hide-mobile"></td>
                        <td class="gf-num"><b data-ks-foot-amount>{{ $rp($totalUpahProj) }}</b></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="prod-empty" data-ks-empty hidden>Tidak ada transaksi yang cocok dengan filter.</div>
        <div class="gf-table-foot">
            <span class="gf-table-foot-hint" data-ks-slip-hint hidden>Pilih peran (Jahit/Cutting) + satu operator untuk mencetak slip upah.</span>
            <a class="gf-slip-btn" data-ks-slip hidden target="_blank" rel="noopener">Slip Setor</a>
        </div>
    @endif
</x-gf.panel>
