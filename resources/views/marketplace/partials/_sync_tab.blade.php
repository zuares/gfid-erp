{{--
    TAB SYNC
    Versi ringkas: hanya ada sync hari ini dan kemarin.
--}}
<div class="ads-tab-panel mb-3">

    <div class="p-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <button type="button" class="btn fw-bold" onclick="window.__syncQuick('today', this)"
                style="background: var(--dsh-accent); color:#fff; border-radius:10px; font-size:.75rem; padding:.42rem .85rem;">
                <i class="bi bi-sun"></i> Sync Hari Ini
            </button>
            <button type="button" class="btn fw-bold" onclick="window.__syncQuick('yesterday', this)"
                style="border:1px solid var(--dsh-border); color:var(--text); border-radius:10px; font-size:.75rem; padding:.42rem .85rem;">
                <i class="bi bi-moon"></i> Sync Kemarin
            </button>
            <button type="button" class="btn fw-bold ms-auto" onclick="window.__syncRefresh(this)"
                style="border:1px solid var(--dsh-border); color:var(--dsh-muted); border-radius:10px; font-size:.75rem; padding:.42rem .85rem;">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
        <div id="syncNotice" class="mt-3" style="display:none; font-size:.75rem; padding:.6rem .75rem; border-radius:10px;"></div>
    </div>
</div>

<div class="dpanel ads-tab-panel mb-3" style="padding:1rem;">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <span style="display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .7rem; border-radius:8px; border:1px solid var(--dsh-border); font-size:.72rem;" title="Status terakhir dari tabel riwayat">
            <i class="bi bi-clock-history" style="color:#1d4ed8;"></i>
            <span style="color:var(--dsh-muted);">Riwayat terakhir:</span>
            <b style="color:var(--text); font-weight:800;">{{ isset($syncRuns) && $syncRuns->count() ? $syncRuns->first()->sync_type : '-' }}</b>
        </span>
        <span style="display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .7rem; border-radius:8px; border:1px solid var(--dsh-border); font-size:.72rem;">
            <i class="bi bi-database-check" style="color:#15803d;"></i>
            <span style="color:var(--dsh-muted);">Tabel riwayat menampilkan 20 run terakhir.</span>
        </span>
    </div>
</div>

<div class="ads-tab-panel" style="overflow:hidden;">
    <div class="table-responsive">
        <table class="dpanel-table dpanel-table-sm" style="white-space: nowrap;">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Jenis</th>
                    <th>Rentang Data</th>
                    <th>Status</th>
                    <th class="text-end">Durasi</th>
                    <th class="text-end">Req / Baris</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($syncRuns) && $syncRuns->count() > 0)
                    @foreach($syncRuns as $run)
                        @php
                            $colors = match($run->status) {
                                'success' => ['#15803d', 'rgba(22,163,74,.12)'],
                                'processing' => ['#1d4ed8', 'rgba(59,130,246,.12)'],
                                'rate_limited' => ['#b45309', 'rgba(245,158,11,.14)'],
                                default => ['#b91c1c', 'rgba(220,38,38,.12)'],
                            };
                        @endphp
                        <tr>
                            <td style="font-family: ui-monospace, monospace; font-size:.72rem; color: var(--text);">
                                {{ $run->started_at?->format('d/m H:i:s') ?? $run->created_at->format('d/m H:i:s') }}
                            </td>
                            <td style="font-size:.72rem; color: var(--dsh-muted);">{{ $run->sync_type }}</td>
                            <td style="font-size:.72rem; color: var(--dsh-muted);">
                                {{ $run->date_from?->format('d/m/Y') }} &ndash; {{ $run->date_to?->format('d/m/Y') }}
                            </td>
                            <td>
                                <span style="padding:.2rem .55rem; border-radius:999px; font-size:.64rem; font-weight:700; color: {{ $colors[0] }}; background: {{ $colors[1] }};">
                                    {{ strtoupper($run->status) }}
                                </span>
                            </td>
                            <td class="text-end" style="font-family: ui-monospace, monospace; font-size:.72rem; color: var(--text);">
                                {{ ($run->started_at && $run->finished_at) ? abs($run->finished_at->diffInSeconds($run->started_at)) . 's' : '-' }}
                            </td>
                            <td class="text-end" style="font-family: ui-monospace, monospace; font-size:.72rem; color: var(--text);">
                                {{ (int) $run->total_requests }} / <span style="color:#16a34a;">{{ (int) $run->total_updated }}</span>
                            </td>
                            <td style="font-size:.7rem; color: var(--dsh-muted); max-width:240px; overflow:hidden; text-overflow:ellipsis;" title="{{ $run->error_message }}">
                                {{ $run->error_message ?: '-' }}
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="text-center py-4" style="color: var(--dsh-muted); font-size:.8rem;">Belum ada riwayat sinkronisasi.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
