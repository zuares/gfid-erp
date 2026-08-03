@php
    $actions = collect($insights['actions'] ?? []);
    $topRestock = collect($insights['topRestock'] ?? []);
    $signals = collect($insights['signals'] ?? []);
    $watchlist = collect($insights['watchlist'] ?? []);
    $priorities = collect($insights['priorities'] ?? []);
    $dataBasis = collect($insights['dataBasis'] ?? []);
    $itemLookup = collect($insights['itemLookup'] ?? []);
    $summary = $insights['summary'] ?? [];
    $sourceSummary = collect($insights['sourceSummary'] ?? []);
    $overview = (string) ($insights['overview'] ?? '');
    $confidence = (string) ($insights['confidence'] ?? 'medium');
    $generatedAt = $insights['generatedAt'] ?? null;
    $tab = (string) ($insights['tab'] ?? 'rts');
    $role = strtolower((string) (auth()->user()?->role ?? ''));
    $hideAiMeta = in_array($role, ['admin', 'operating'], true);

    $resolveProduct = function (string $sku, string $fallback = '') use ($itemLookup) {
        $item = $itemLookup->get($sku);
        return (string) (data_get($item, 'product') ?: $fallback ?: $sku);
    };

    $criticalCount = (int) ($insights['criticalCount'] ?? 0);
    $priorityCount = (int) $priorities->count();
    $actionsCount = (int) $actions->count();
    $restockCount = (int) $topRestock->count();
@endphp

<details class="card-main mb-3 ai-accordion-shell d-none d-md-block">
    <summary class="ai-accordion-head">
        <div class="ai-accordion-title">
            <div class="ai-accordion-kicker">
                <span class="badge-status" style="background:#eef2ff;color:#3730a3;border-color:#c7d2fe;font-weight:800;">
                    AI Rekomendasi
                </span>
                <span class="text-muted-ii">Default tertutup. Buka kalau mau lihat detail cepat.</span>
            </div>
            <div class="ai-accordion-main">
                {{ $criticalCount }} item perlu perhatian, {{ $priorityCount }} prioritas utama, {{ $actionsCount }} aksi utama, {{ $restockCount }} saran isi ulang.
            </div>
            <div class="ai-accordion-sub">
                Ringkas dulu. Buka untuk lihat detail.
            </div>
        </div>
        <div class="ai-accordion-metrics">
            <div class="ai-mini-metric">
                <div class="label">Kritis</div>
                <div class="value">{{ $criticalCount }}</div>
            </div>
            <div class="ai-mini-metric">
                <div class="label">Aksi</div>
                <div class="value">{{ $actionsCount }}</div>
            </div>
            <div class="ai-mini-metric">
                <div class="label">Prioritas</div>
                <div class="value">{{ $priorityCount }}</div>
            </div>
            <div class="ai-mini-metric">
                <div class="label">Confidence</div>
                <div class="value" style="text-transform:capitalize;">{{ $confidence }}</div>
            </div>
        </div>
    </summary>

    <div class="ai-accordion-body">
        @if (! $hideAiMeta)
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div class="text-muted-ii" style="font-size:.72rem; line-height:1.45;">
                    Data yang dipakai:
                    {{ $dataBasis->isNotEmpty() ? $dataBasis->implode(' · ') : 'stok, WIP, ADS, dan estimasi stok aman.' }}
                    @if($generatedAt)
                        <span class="d-block mt-1">Diperbarui {{ $generatedAt->format('d M Y, H:i') }}</span>
                    @endif
                </div>
                <span class="badge-status" style="background:#f8fafc;color:#334155;border-color:#e2e8f0;font-weight:700;">
                    Bahasa awam
                </span>
            </div>
        @endif

        @if($overview !== '')
            <div class="ai-overview-box mb-3">
                <div class="ai-section-label">Ringkasan AI</div>
                <div class="ai-overview-copy">{{ $overview }}</div>
            </div>
        @endif

        @if($signals->isNotEmpty())
            <div class="ai-section-label">Sinyal cepat</div>
            <div class="ai-pill-row mb-3">
                @foreach($signals as $signal)
                    <span class="ai-pill">
                        <span>{{ $signal['label'] ?? 'Sinyal' }}:</span>
                        <strong>{{ $signal['value'] ?? '-' }}</strong>
                    </span>
                @endforeach
            </div>
        @endif

        @if($tab === 'rts' && $sourceSummary->isNotEmpty())
            <div class="ai-section-label">Grouping sumber</div>
            <div class="ai-pill-row mb-3">
                <span class="ai-pill">
                    <span>Produksi sendiri:</span>
                    <strong>{{ number_format((int) ($sourceSummary->get('in_house') ?? 0), 0, ',', '.') }}</strong>
                </span>
                <span class="ai-pill">
                    <span>Perlu beli:</span>
                    <strong>{{ number_format((int) ($sourceSummary->get('buy') ?? 0), 0, ',', '.') }}</strong>
                </span>
                @if((int) ($sourceSummary->get('outsource') ?? 0) > 0)
                    <span class="ai-pill">
                        <span>Makloon:</span>
                        <strong>{{ number_format((int) ($sourceSummary->get('outsource') ?? 0), 0, ',', '.') }}</strong>
                    </span>
                @endif
            </div>
        @endif

        @if($priorities->isNotEmpty())
            <div class="ai-section-label">Prioritas utama</div>
            <div class="ai-compact-list mb-3">
                @foreach($priorities->take(3) as $item)
                    @php
                        $tone = $item['tone'] ?? 'info';
                        $styles = match ($tone) {
                            'success' => ['bg' => '#ecfdf5', 'border' => '#bbf7d0', 'color' => '#047857'],
                            'warning' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'color' => '#b45309'],
                            'danger' => ['bg' => '#fef2f2', 'border' => '#fecaca', 'color' => '#dc2626'],
                            default => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'color' => '#1d4ed8'],
                        };
                        $sku = (string) ($item['sku'] ?? '');
                        $product = (string) ($item['product'] ?? $resolveProduct($sku));
                        $qtyLabel = trim((string) ($item['qty_label'] ?? ''));
                        $daysLabel = trim((string) ($item['days_label'] ?? ''));
                    @endphp
                    <div class="ai-mini-row">
                        <div style="min-width:0;">
                            <div class="name">
                                <span class="badge-status me-1" style="background:{{ $styles['bg'] }};color:{{ $styles['color'] }};border-color:{{ $styles['border'] }};font-weight:800;">
                                    {{ $item['rank'] ?? 'P?' }}
                                </span>
                                {{ $item['title'] ?? 'Prioritas' }}
                            </div>
                            <div class="desc">{{ $product }}</div>
                            <div class="desc">
                                {{ $item['label'] ?? '-' }}
                                @if($qtyLabel !== '')
                                    · Qty {{ $qtyLabel }}
                                @endif
                                @if($daysLabel !== '')
                                    · Cukup {{ $daysLabel }}
                                @endif
                            </div>
                            <div class="desc">{{ $item['reason'] ?? '-' }}</div>
                            @if($sku !== '')
                                <div class="desc">Kode: {{ $sku }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($actions->isEmpty() && $topRestock->isEmpty() && $watchlist->isEmpty() && $priorities->isEmpty())
            <div class="ii-empty" style="padding:1rem 0;">
                Belum ada prioritas mendesak. Stok terlihat aman untuk sekarang.
            </div>
        @else
            @if($tab === 'rts')
                <div class="row g-3 ai-rts-grid">
                    <div class="col-12 col-lg-4">
                        <div class="ai-section-label">Saran cepat</div>
                        <div class="ai-compact-list">
                            @forelse($actions as $item)
                                @php
                                    $tone = $item['tone'] ?? 'info';
                                    $styles = match ($tone) {
                                        'success' => ['bg' => '#ecfdf5', 'border' => '#bbf7d0', 'color' => '#047857'],
                                        'warning' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'color' => '#b45309'],
                                        'danger' => ['bg' => '#fef2f2', 'border' => '#fecaca', 'color' => '#dc2626'],
                                        default => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'color' => '#1d4ed8'],
                                    };
                                    $sku = (string) ($item['sku'] ?? '');
                                    $product = (string) ($item['product'] ?? $resolveProduct($sku));
                                    $qtyLabel = trim((string) ($item['qty_label'] ?? ''));
                                    $daysLabel = trim((string) ($item['days_label'] ?? ''));
                                @endphp
                                <details class="ai-action-details">
                                    <summary>
                                        <div class="ai-action-head">
                                            <div class="ai-action-title">{{ $item['title'] ?? 'Saran' }}</div>
                                            <div class="ai-action-product">{{ $product }}</div>
                                        </div>
                                        <div class="ai-action-meta">
                                            <div class="ai-action-chip-row">
                                                <span class="ai-action-chip" style="background:{{ $styles['bg'] }};color:{{ $styles['color'] }};border-color:{{ $styles['border'] }};">
                                                    Qty {{ $qtyLabel !== '' ? $qtyLabel : 'belum kebaca' }}
                                                </span>
                                                <span class="ai-action-chip" style="background:#f8fafc;color:#475569;border-color:#e2e8f0;">
                                                    {{ $daysLabel !== '' ? 'Cukup ' . $daysLabel : 'Hari belum kebaca' }}
                                                </span>
                                            </div>
                                            @if($sku !== '')
                                                <div class="ai-action-code">Kode: {{ $sku }}</div>
                                            @endif
                                        </div>
                                    </summary>
                                    <div class="ai-action-body">
                                        <div>{{ $item['reason'] ?? '' }}</div>
                                    </div>
                                </details>
                            @empty
                                <div class="ii-empty">Belum ada aksi yang perlu dikejar sekarang.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="ai-section-label">Yang paling perlu dipantau</div>
                        <div class="ai-compact-list">
                            @forelse($topRestock as $row)
                                @php
                                    $sku = (string) data_get($row, 'sku', '');
                                    $product = (string) data_get($row, 'product', $resolveProduct($sku));
                                    $readyQty = (float) data_get($row, 'ready_qty', 0);
                                    $readyQtyLabel = (string) data_get($row, 'ready_qty_label', number_format($readyQty, 0, ',', '.') . ' pcs');
                                    $suggestedQty = (float) data_get($row, 'suggested_qty', 0);
                                    $suggestedQtyLabel = (string) data_get($row, 'suggested_qty_label', number_format($suggestedQty, 0, ',', '.') . ' pcs');
                                    $coverDays = data_get($row, 'cover_days');
                                    $coverDaysLabel = (string) data_get($row, 'cover_days_label', $coverDays !== null ? 'Stok aman bertahan ' . number_format((float) $coverDays, 1, ',', '.') . ' hari' : 'Stok aman belum kebaca');
                                    $status = (string) data_get($row, 'status', '-');
                                @endphp
                                <div class="ai-mini-row">
                                    <div style="min-width:0;">
                                        <div class="name">{{ $product }}</div>
                                        <div class="desc">{{ $coverDaysLabel }}</div>
                                        <div class="desc">Ready {{ $readyQtyLabel }}</div>
                                        <div class="desc">Status {{ $status }}</div>
                                        @if($sku !== '')
                                            <div class="desc">Kode: {{ $sku }}</div>
                                        @endif
                                    </div>
                                    <span class="badge-status" style="background:#f8fafc;color:#475569;border-color:#e2e8f0;font-weight:800;white-space:nowrap;">
                                        {{ $suggestedQtyLabel }}
                                    </span>
                                </div>
                            @empty
                                <div class="ii-empty">Tidak ada saran isi ulang untuk saat ini.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="ai-section-label">Watchlist AI</div>
                        <div class="ai-compact-list">
                            @forelse($watchlist as $row)
                                @php
                                    $sku = (string) ($row['sku'] ?? '');
                                    $product = $resolveProduct($sku);
                                    $qtyLabel = trim((string) ($row['qty_label'] ?? ''));
                                    $daysLabel = trim((string) ($row['days_label'] ?? ''));
                                @endphp
                                <div class="ai-mini-row">
                                    <div style="min-width:0;">
                                        <div class="name">{{ $product }}</div>
                                        <div class="desc">
                                            Saran qty {{ $qtyLabel !== '' ? $qtyLabel : 'belum kebaca' }}
                                            @if($daysLabel !== '')
                                                · Cukup {{ $daysLabel }}
                                            @endif
                                        </div>
                                        <div class="desc">{{ $row['reason'] ?? '-' }}</div>
                                        @if($sku !== '')
                                            <div class="desc">Kode: {{ $sku }}</div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="ii-empty">Watchlist kosong.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @else
                <div class="row g-3">
                    <div class="col-12 col-lg-7">
                        <div class="ai-section-label">Saran cepat</div>
                        <div class="ai-compact-list">
                            @forelse($actions as $item)
                                @php
                                    $tone = $item['tone'] ?? 'info';
                                    $styles = match ($tone) {
                                        'success' => ['bg' => '#ecfdf5', 'border' => '#bbf7d0', 'color' => '#047857'],
                                        'warning' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'color' => '#b45309'],
                                        'danger' => ['bg' => '#fef2f2', 'border' => '#fecaca', 'color' => '#dc2626'],
                                        default => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'color' => '#1d4ed8'],
                                    };
                                    $sku = (string) ($item['sku'] ?? '');
                                    $product = (string) ($item['product'] ?? $resolveProduct($sku));
                                    $qtyLabel = trim((string) ($item['qty_label'] ?? ''));
                                    $daysLabel = trim((string) ($item['days_label'] ?? ''));
                                @endphp
                                <details class="ai-action-details">
                                    <summary>
                                        <div class="ai-action-head">
                                            <div class="ai-action-title">{{ $item['title'] ?? 'Saran' }}</div>
                                            <div class="ai-action-product">{{ $product }}</div>
                                        </div>
                                        <div class="ai-action-meta">
                                            <div class="ai-action-chip-row">
                                                <span class="ai-action-chip" style="background:{{ $styles['bg'] }};color:{{ $styles['color'] }};border-color:{{ $styles['border'] }};">
                                                    Qty {{ $qtyLabel !== '' ? $qtyLabel : 'belum kebaca' }}
                                                </span>
                                                <span class="ai-action-chip" style="background:#f8fafc;color:#475569;border-color:#e2e8f0;">
                                                    {{ $daysLabel !== '' ? 'Cukup ' . $daysLabel : 'Hari belum kebaca' }}
                                                </span>
                                            </div>
                                            @if($sku !== '')
                                                <div class="ai-action-code">Kode: {{ $sku }}</div>
                                            @endif
                                        </div>
                                    </summary>
                                    <div class="ai-action-body">
                                        <div>{{ $item['reason'] ?? '' }}</div>
                                    </div>
                                </details>
                            @empty
                                <div class="ii-empty">Belum ada aksi yang perlu dikejar sekarang.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="ai-section-label">Yang paling perlu dipantau</div>
                        <div class="ai-compact-list mb-3">
                            @forelse($topRestock as $row)
                                @php
                                    $sku = (string) data_get($row, 'sku', '');
                                    $product = (string) data_get($row, 'product', $resolveProduct($sku));
                                    $readyQty = (float) data_get($row, 'ready_qty', 0);
                                    $readyQtyLabel = (string) data_get($row, 'ready_qty_label', number_format($readyQty, 0, ',', '.') . ' pcs');
                                    $suggestedQty = (float) data_get($row, 'suggested_qty', 0);
                                    $suggestedQtyLabel = (string) data_get($row, 'suggested_qty_label', number_format($suggestedQty, 0, ',', '.') . ' pcs');
                                    $coverDays = data_get($row, 'cover_days');
                                    $coverDaysLabel = (string) data_get($row, 'cover_days_label', $coverDays !== null ? 'Stok aman bertahan ' . number_format((float) $coverDays, 1, ',', '.') . ' hari' : 'Stok aman belum kebaca');
                                    $status = (string) data_get($row, 'status', '-');
                                @endphp
                                <div class="ai-mini-row">
                                    <div style="min-width:0;">
                                        <div class="name">{{ $product }}</div>
                                        <div class="desc">
                                            {{ $coverDaysLabel }} · Ready {{ $readyQtyLabel }} · Status {{ $status }}
                                        </div>
                                        @if($sku !== '')
                                            <div class="desc">Kode: {{ $sku }}</div>
                                        @endif
                                    </div>
                                    <span class="badge-status" style="background:#f8fafc;color:#475569;border-color:#e2e8f0;font-weight:800;white-space:nowrap;">
                                        {{ $suggestedQtyLabel }}
                                    </span>
                                </div>
                            @empty
                                <div class="ii-empty">Tidak ada saran isi ulang untuk saat ini.</div>
                            @endforelse
                        </div>

                        <div class="ai-section-label">Watchlist AI</div>
                        <div class="ai-compact-list">
                            @forelse($watchlist as $row)
                                @php
                                    $sku = (string) ($row['sku'] ?? '');
                                    $product = $resolveProduct($sku);
                                    $qtyLabel = trim((string) ($row['qty_label'] ?? ''));
                                    $daysLabel = trim((string) ($row['days_label'] ?? ''));
                                @endphp
                                <div class="ai-mini-row">
                                    <div style="min-width:0;">
                                        <div class="name">{{ $product }}</div>
                                        <div class="desc">
                                            Saran qty {{ $qtyLabel !== '' ? $qtyLabel : 'belum kebaca' }}
                                            @if($daysLabel !== '')
                                                · Cukup {{ $daysLabel }}
                                            @endif
                                        </div>
                                        <div class="desc">{{ $row['reason'] ?? '-' }}</div>
                                        @if($sku !== '')
                                            <div class="desc">Kode: {{ $sku }}</div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="ii-empty">Watchlist kosong.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</details>
