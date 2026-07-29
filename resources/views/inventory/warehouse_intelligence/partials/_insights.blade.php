@php
    $actions = collect($insights['actions'] ?? []);
    $topRestock = collect($insights['topRestock'] ?? []);
    $generatedAt = $insights['generatedAt'] ?? null;
@endphp

<div class="card-main mb-3" style="border-left: 4px solid #334155;">
    <div class="p-3 p-md-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-status" style="background:#eef2ff;color:#3730a3;border-color:#c7d2fe;font-weight:700;">
                        AI Rekomendasi
                    </span>
                    <span class="text-muted-ii" style="font-size:.72rem;">
                        Dihitung otomatis dari stok, WIP, dan ADS
                    </span>
                </div>
                <div style="font-size:1rem;font-weight:800;color:#0f172a;margin-top:.45rem;">
                    Ringkasan keputusan stok
                </div>
                @if($generatedAt)
                    <div class="text-muted-ii" style="font-size:.72rem;">Diperbarui {{ $generatedAt->format('d M Y, H:i') }}</div>
                @endif
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <div class="px-3 py-2" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;min-width:120px;">
                    <div class="text-muted-ii" style="font-size:.68rem;">Item kritis</div>
                    <div style="font-size:1.1rem;font-weight:800;color:#dc2626;">{{ (int) ($insights['criticalCount'] ?? 0) }}</div>
                </div>
                <div class="px-3 py-2" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;min-width:120px;">
                    <div class="text-muted-ii" style="font-size:.68rem;">Prioritas utama</div>
                    <div style="font-size:1.1rem;font-weight:800;color:#059669;">{{ (int) $actions->count() }}</div>
                </div>
            </div>
        </div>

        @if($actions->isEmpty() && $topRestock->isEmpty())
            <div class="ii-empty" style="padding:1.4rem 1rem;">
                Tidak ada prioritas mendesak saat ini. Stok terlihat aman.
            </div>
        @else
            <div class="row g-3">
                <div class="col-12 col-lg-7">
                    <div style="font-size:.78rem;font-weight:700;color:#334155;margin-bottom:.5rem;">Rekomendasi Tindakan</div>
                    <div class="d-flex flex-column gap-2">
                        @forelse($actions as $item)
                            @php
                                $tone = $item['tone'] ?? 'info';
                                $styles = match ($tone) {
                                    'success' => ['bg' => '#ecfdf5', 'border' => '#bbf7d0', 'color' => '#047857'],
                                    'warning' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'color' => '#b45309'],
                                    'danger' => ['bg' => '#fef2f2', 'border' => '#fecaca', 'color' => '#dc2626'],
                                    default => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'color' => '#1d4ed8'],
                                };
                            @endphp
                            <div style="background:{{ $styles['bg'] }};border:1px solid {{ $styles['border'] }};border-radius:12px;padding:.85rem .9rem;">
                                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                    <div>
                                        <div style="font-weight:800;color:#0f172a;font-size:.92rem;">{{ $item['title'] }}</div>
                                        <div class="text-muted-ii" style="font-size:.72rem;">{{ $item['sku'] ?? '-' }}</div>
                                    </div>
                                    <span class="badge-status" style="background:#fff;color:{{ $styles['color'] }};border-color:{{ $styles['border'] }};font-weight:700;">
                                        {{ $item['label'] ?? 'Saran' }}
                                    </span>
                                </div>
                                <div style="font-size:.8rem;color:#334155;margin-top:.45rem;line-height:1.45;">
                                    {{ $item['reason'] ?? '' }}
                                </div>
                            </div>
                        @empty
                            <div class="ii-empty">Belum ada rekomendasi aksi yang perlu dikerjakan sekarang.</div>
                        @endforelse
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <div style="font-size:.78rem;font-weight:700;color:#334155;margin-bottom:.5rem;">SKU Perlu Diperhatikan</div>
                    <div class="d-flex flex-column gap-2">
                        @forelse($topRestock as $r)
                            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:.8rem .85rem;">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div style="font-weight:800;color:#0f172a;font-size:.9rem;">{{ $r->sku }}</div>
                                        <div class="text-muted-ii" style="font-size:.72rem;">{{ $r->product }}</div>
                                    </div>
                                    <span class="badge-status" style="background:#f8fafc;color:#475569;border-color:#e2e8f0;font-weight:700;">
                                        {{ number_format((float) ($r->suggested_qty ?? 0), 0, ',', '.') }} pcs
                                    </span>
                                </div>
                                <div class="text-muted-ii" style="font-size:.72rem;margin-top:.35rem;">
                                    Status: <strong style="color:#0f172a;">{{ $r->status ?? '-' }}</strong> · Cover {{ number_format((float) ($r->cover_days ?? 0), 1, ',', '.') }} hari
                                </div>
                            </div>
                        @empty
                            <div class="ii-empty">Tidak ada SKU dengan saran restock saat ini.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
