@extends('marketplace.finance.layout')

@section('finance-content')
    @php
        $run = $syncRunAudit['run'];
        $audit = $syncRunAudit['audit'] ?? [];
        $phaseLabels = [
            'preflight' => 'Preflight',
            'apply' => 'Apply',
            'verification' => 'Verifikasi',
        ];
        $phases = collect($phaseLabels)
            ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'summary' => $audit[$key] ?? null])
            ->filter(fn ($phase) => is_array($phase['summary']));
        $durationSeconds = $run->started_at && $run->finished_at
            ? $run->started_at->diffInSeconds($run->finished_at)
            : null;
    @endphp

    <style>
        .sync-run-screen { display: grid; gap: 1rem; }
        .sync-run-screen .sync-run-heading { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; }
        .sync-run-screen .sync-run-heading h2 { margin: 0; color: var(--finance-ink); font-size: 1rem; font-weight: 800; }
        .sync-run-screen .sync-run-back { display: inline-flex; align-items: center; gap: .35rem; min-height: 38px; padding: 0 .72rem; border: 1px solid var(--finance-line); border-radius: 10px; color: var(--finance-blue); background: #fff; font-size: .72rem; font-weight: 800; text-decoration: none; }
        .sync-run-screen .sync-run-back:hover { border-color: #c9c8f4; background: #f4f3ff; }
        .sync-run-screen .sync-run-meta { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .8rem; margin: 0; }
        .sync-run-screen .sync-run-meta > div { padding: .85rem; border: 1px solid var(--finance-line); border-radius: 12px; background: #fff; }
        .sync-run-screen .sync-run-meta dt { color: var(--finance-muted); font-size: .65rem; font-weight: 800; letter-spacing: .045em; text-transform: uppercase; }
        .sync-run-screen .sync-run-meta dd { margin: .25rem 0 0; overflow-wrap: anywhere; color: var(--finance-ink); font-size: .8rem; font-weight: 800; }
        .sync-run-screen .sync-run-sources { display: flex; flex-wrap: wrap; gap: .4rem; }
        .sync-run-screen .sync-run-source { display: inline-flex; min-height: 25px; align-items: center; padding: 0 .55rem; border-radius: 999px; color: #344054; background: #f2f4f7; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .66rem; font-weight: 750; }
        .sync-run-screen .sync-run-error { padding: .8rem 1rem; border: 1px solid #fecdca; border-radius: 12px; color: #912018; background: #fef3f2; font-size: .76rem; }
        @media (max-width: 767.98px) {
            .sync-run-screen .sync-run-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .sync-run-screen .sync-run-heading { align-items: stretch; flex-direction: column; }
            .sync-run-screen .sync-run-back { justify-content: center; min-height: 44px; }
        }
    </style>

    <section class="sync-run-screen" aria-labelledby="sync-run-title">
        <div class="sync-run-heading">
            <div>
                <h2 id="sync-run-title">Audit run #{{ $run->id }}</h2>
                <span class="finance-section-meta">Catatan sistem yang tidak mengubah data saat halaman ini dibuka.</span>
            </div>
            <a class="sync-run-back" href="{{ route('marketplace.finance.transactions') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i>Kembali ke transaksi</a>
        </div>

        <div class="finance-callout" role="note">
            <i class="bi bi-shield-check" aria-hidden="true"></i>
            <div>
                <strong>Batas audit: Finance per order</strong>
                <span>Run ini hanya menyalin sumber order ke ledger Finance. Status sukses bukan bukti payout diterima, uang masuk bank, atau jurnal telah diposting.</span>
            </div>
        </div>

        @if ($run->error_message)
            <div class="sync-run-error" role="alert"><strong>Pesan kegagalan:</strong> {{ $run->error_message }}</div>
        @endif

        <dl class="sync-run-meta">
            <div><dt>Status</dt><dd>{{ $syncRunAudit['label'] }}</dd></div>
            <div><dt>Toko</dt><dd>{{ $syncRunAudit['store_name'] }}</dd></div>
            <div><dt>Mulai</dt><dd>{{ $run->started_at?->timezone(config('app.timezone'))->format('d M Y H:i:s') ?? '—' }}</dd></div>
            <div><dt>Selesai / durasi</dt><dd>{{ $run->finished_at?->timezone(config('app.timezone'))->format('d M Y H:i:s') ?? 'Belum selesai' }}{{ $durationSeconds !== null ? ' · '.number_format($durationSeconds, 0).' dtk' : '' }}</dd></div>
        </dl>

        <section class="finance-card card" aria-labelledby="sync-run-phase-title">
            <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h3 id="sync-run-phase-title" class="finance-section-title mb-0">Hasil tiap fase</h3>
                    <span class="finance-section-meta">Preflight harus bersih; verifikasi harus kembali 0 perubahan.</span>
                </div>
                <span class="finance-section-count">{{ $phases->count() }} fase</span>
            </div>
            <div class="finance-table-wrap table-responsive">
                <table class="finance-table table mb-0">
                    <caption class="visually-hidden">Ringkasan fase audit sinkronisasi Finance</caption>
                    <thead><tr><th>Fase</th><th>Dipindai</th><th>Order baru</th><th>Order berubah</th><th>Komponen baru</th><th>Unmatched</th><th>Duplikat</th><th>Error</th></tr></thead>
                    <tbody>
                        @forelse ($phases as $phase)
                            <tr>
                                <td data-label="Fase"><strong>{{ $phase['label'] }}</strong></td>
                                <td data-label="Dipindai">{{ number_format($phase['summary']['scanned'] ?? 0) }}</td>
                                <td data-label="Order baru">{{ number_format($phase['summary']['transactions_created'] ?? 0) }}</td>
                                <td data-label="Order berubah">{{ number_format($phase['summary']['updated'] ?? 0) }}</td>
                                <td data-label="Komponen baru">{{ number_format($phase['summary']['components_created'] ?? 0) }}</td>
                                <td data-label="Unmatched">{{ number_format($phase['summary']['unmatched'] ?? 0) }}</td>
                                <td data-label="Duplikat">{{ number_format($phase['summary']['duplicates'] ?? 0) }}</td>
                                <td data-label="Error">{{ number_format($phase['summary']['errors'] ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8"><div class="finance-empty"><i class="bi bi-inbox" aria-hidden="true"></i>Run ini belum memiliki ringkasan fase.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="finance-card card">
            <div class="card-body">
                <h3 class="finance-section-title mb-2">Sumber order</h3>
                <div class="sync-run-sources">
                    @forelse (($audit['sources'] ?? []) as $source)
                        <span class="sync-run-source">{{ $source }}</span>
                    @empty
                        <span class="finance-section-meta">Sumber tidak tercatat pada audit run ini.</span>
                    @endforelse
                </div>
            </div>
        </section>
    </section>
@endsection
