@extends('layouts.app')

@section('title', 'Pengaturan Sistem')

@push('head')
<style>
    .ss-page {
        --ss-surface: var(--card, #fff);
        --ss-surface-soft: var(--card-soft, #f8fafc);
        --ss-text: var(--text, #0f172a);
        --ss-muted: var(--muted, #64748b);
        --ss-line: var(--line, #e2e8f0);
        --ss-brand: var(--accent, #2563eb);
        --ss-brand-soft: var(--accent-soft, #dbeafe);
        --ss-success-soft: var(--success-soft, #dcfce7);
        --ss-warning-soft: #fffbeb;
        --ss-danger: var(--danger, #dc2626);
        --ss-danger-soft: var(--danger-soft, #fee2e2);
        display: grid;
        gap: 1rem;
        width: 100%;
        max-width: 1440px;
        margin-inline: auto;
        padding: .4rem 0 2rem;
    }

    .page-wrap:has(.ss-page) { max-width: 1440px; }

    .ss-page .gf-master-header { background: var(--ss-surface); border-color: var(--ss-line); box-shadow: 0 4px 18px rgba(15, 23, 42, .035); }
    .ss-page .gf-master-title, .ss-page .gf-master-header h1 { color: var(--ss-text); }
    .ss-page .gf-master-desc { color: var(--ss-muted); }

    .ss-alert { display: flex; align-items: flex-start; gap: .65rem; border: 1px solid; border-radius: 12px; padding: .75rem .9rem; font-size: .78rem; line-height: 1.5; }
    .ss-alert i { flex: 0 0 auto; font-size: 1rem; line-height: 1.3; }
    .ss-alert-success { color: #166534; background: var(--ss-success-soft); border-color: #bbf7d0; }
    .ss-alert-warning { color: #92400e; background: var(--ss-warning-soft); border-color: #fde68a; }
    body[data-theme="dark"] .ss-alert-success { color: #bbf7d0; border-color: #166534; }
    body[data-theme="dark"] .ss-alert-warning { color: #fde68a; border-color: #92400e; }

    .ss-layout { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; align-items: start; }
    .ss-primary, .ss-aside { display: grid; gap: 1rem; min-width: 0; }

    .ss-card { background: var(--ss-surface); border: 1px solid var(--ss-line); border-radius: 16px; box-shadow: 0 8px 24px rgba(15, 23, 42, .035); overflow: hidden; }
    .ss-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1.15rem .85rem; border-bottom: 1px solid var(--ss-line); }
    .ss-card-head-copy { min-width: 0; }
    .ss-card-title { display: flex; align-items: center; gap: .5rem; color: var(--ss-text); font-size: .92rem; font-weight: 900; line-height: 1.25; }
    .ss-card-title i { color: var(--ss-brand); font-size: 1rem; }
    .ss-card-sub { max-width: 760px; margin: .25rem 0 0; color: var(--ss-muted); font-size: .76rem; line-height: 1.5; }
    .ss-card-body { padding: 1rem 1.15rem; }

    .ss-status { display: inline-flex; align-items: center; gap: .35rem; flex: 0 0 auto; border: 1px solid; border-radius: 999px; padding: .26rem .6rem; font-size: .69rem; font-weight: 850; white-space: nowrap; }
    .ss-status::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .ss-status-on { color: #166534; background: var(--ss-success-soft); border-color: #bbf7d0; }
    .ss-status-off { color: #92400e; background: #fef3c7; border-color: #fde68a; }
    body[data-theme="dark"] .ss-status-on { color: #bbf7d0; border-color: #166534; }
    body[data-theme="dark"] .ss-status-off { color: #fde68a; border-color: #92400e; }

    .ss-cutoff-hero { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .9rem 1rem; border: 1px solid color-mix(in srgb, var(--ss-brand) 18%, var(--ss-line)); border-radius: 12px; background: linear-gradient(135deg, color-mix(in srgb, var(--ss-brand-soft) 72%, var(--ss-surface) 28%), var(--ss-surface)); }
    .ss-kicker { color: var(--ss-muted); font-size: .65rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
    .ss-date { margin-top: .2rem; color: var(--ss-text); font-size: clamp(1.35rem, 2.2vw, 1.8rem); font-weight: 950; letter-spacing: -.035em; line-height: 1.1; }
    .ss-date-note { margin-top: .22rem; color: var(--ss-muted); font-size: .74rem; }
    .ss-cutoff-icon { color: var(--ss-brand); font-size: 2.25rem; opacity: .85; }

    .ss-stat-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; margin-top: .85rem; }
    .ss-stat { position: relative; min-width: 0; padding: .75rem .85rem; border: 1px solid var(--ss-line); border-radius: 11px; background: var(--ss-surface-soft); overflow: hidden; }
    .ss-stat::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 3px; background: var(--ss-brand); }
    .ss-stat-legacy::before { background: #f59e0b; }
    .ss-stat-label { color: var(--ss-muted); font-size: .64rem; font-weight: 900; letter-spacing: .045em; line-height: 1.35; text-transform: uppercase; }
    .ss-stat-value { margin-top: .25rem; color: var(--ss-text); font-size: 1.12rem; font-weight: 950; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .ss-stat-sub { margin-top: .2rem; color: var(--ss-muted); font-size: .69rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .ss-form-grid { display: grid; grid-template-columns: minmax(180px, .7fr) minmax(0, 1.3fr); gap: .8rem; align-items: start; }
    .ss-field { display: grid; gap: .32rem; }
    .ss-label { color: var(--ss-text); font-size: .73rem; font-weight: 850; }
    .ss-help { color: var(--ss-muted); font-size: .7rem; line-height: 1.45; }
    .ss-field .form-control { min-height: 38px; border-radius: 9px; border-color: var(--ss-line); box-shadow: none; font-size: .82rem; }
    .ss-field textarea.form-control { min-height: 88px; resize: vertical; }
    .ss-field .form-control:focus { border-color: var(--ss-brand); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ss-brand-soft) 80%, transparent); }
    .ss-actions { display: flex; justify-content: flex-end; gap: .5rem; flex-wrap: wrap; margin-top: 1rem; padding-top: .85rem; border-top: 1px solid var(--ss-line); }

    .ss-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-height: 36px; padding: .45rem .8rem; border: 1px solid var(--ss-line); border-radius: 9px; background: var(--ss-surface); color: var(--ss-text); font-size: .75rem; font-weight: 850; line-height: 1.2; text-decoration: none; cursor: pointer; transition: background-color .16s ease, border-color .16s ease, transform .16s ease; }
    .ss-btn:hover { color: var(--ss-text); background: var(--ss-surface-soft); transform: translateY(-1px); }
    .ss-btn:focus-visible, .ss-link:focus-visible { outline: 3px solid color-mix(in srgb, var(--ss-brand) 32%, transparent); outline-offset: 2px; }
    .ss-btn-primary { color: #fff; background: #0f172a; border-color: #0f172a; }
    .ss-btn-primary:hover { color: #fff; background: #1e293b; }
    .ss-btn-danger { color: var(--ss-danger); background: color-mix(in srgb, var(--ss-danger-soft) 55%, var(--ss-surface)); border-color: color-mix(in srgb, var(--ss-danger) 25%, var(--ss-line)); }
    .ss-btn-danger:hover { color: var(--ss-danger); background: var(--ss-danger-soft); }
    body[data-theme="dark"] .ss-btn-primary { color: #0f172a; background: #f8fafc; border-color: #f8fafc; }

    .ss-step-list { list-style: none; padding: 0; margin: 0; display: grid; }
    .ss-step { position: relative; display: grid; grid-template-columns: 27px minmax(0, 1fr); gap: .65rem; padding: .65rem 0; }
    .ss-step:not(:last-child)::after { content: ''; position: absolute; top: 32px; bottom: -4px; left: 12px; width: 1px; background: var(--ss-line); }
    .ss-step-number { position: relative; z-index: 1; display: grid; place-items: center; width: 25px; height: 25px; border: 1px solid var(--ss-line); border-radius: 50%; background: var(--ss-surface); color: var(--ss-muted); font-size: .68rem; font-weight: 900; }
    .ss-step:first-child .ss-step-number { color: var(--ss-brand); border-color: color-mix(in srgb, var(--ss-brand) 38%, var(--ss-line)); background: var(--ss-brand-soft); }
    .ss-step-title { color: var(--ss-text); font-size: .75rem; font-weight: 850; line-height: 1.35; }
    .ss-step-desc { margin-top: .12rem; color: var(--ss-muted); font-size: .68rem; line-height: 1.45; }
    .ss-link { color: var(--ss-brand); font-weight: 750; text-decoration: underline; text-decoration-color: color-mix(in srgb, var(--ss-brand) 35%, transparent); text-underline-offset: 2px; }
    .ss-guide-empty { display: none; padding: .9rem .7rem; border: 1px dashed var(--ss-line); border-radius: 10px; color: var(--ss-muted); font-size: .72rem; line-height: 1.45; text-align: center; }
    .ss-guide-empty.is-visible { display: block; }
    .ss-guide-empty i { display: block; margin-bottom: .3rem; color: var(--ss-brand); font-size: 1rem; }

    .ss-note { border: 1px solid #bfdbfe; border-radius: 12px; padding: .8rem .9rem; background: #eff6ff; color: #1e40af; font-size: .72rem; line-height: 1.55; }
    .ss-note-title { display: flex; align-items: center; gap: .4rem; margin-bottom: .4rem; color: #1d4ed8; font-size: .76rem; font-weight: 900; }
    .ss-note ul { margin: 0; padding-left: 1rem; }
    .ss-note code { color: inherit; font-size: .68rem; }
    body[data-theme="dark"] .ss-note { color: #bfdbfe; background: #172554; border-color: #1d4ed8; }
    body[data-theme="dark"] .ss-note-title { color: #93c5fd; }

    .ss-danger-card { border-color: color-mix(in srgb, var(--ss-danger) 24%, var(--ss-line)); }
    .ss-danger-card .ss-card-title i { color: var(--ss-danger); }
    .ss-danger-body { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .ss-danger-copy { color: var(--ss-muted); font-size: .74rem; line-height: 1.5; }
    .ss-danger-copy strong { color: var(--ss-text); }

    @media (min-width: 1040px) { .ss-layout { grid-template-columns: minmax(0, 1fr) 330px; } .ss-aside { position: sticky; top: 1rem; } }
    @media (max-width: 640px) {
        .ss-page { padding-top: 0; }
        .ss-page .gf-master-header { padding: 12px 14px; }
        .ss-card-head, .ss-card-body { padding-left: .9rem; padding-right: .9rem; }
        .ss-card-head { flex-direction: column; gap: .6rem; }
        .ss-status { align-self: flex-start; }
        .ss-cutoff-hero, .ss-danger-body { align-items: flex-start; flex-direction: column; }
        .ss-cutoff-icon { display: none; }
        .ss-form-grid, .ss-stat-grid { grid-template-columns: 1fr; }
        .ss-actions { justify-content: stretch; }
        .ss-actions .ss-btn { flex: 1 1 100%; }
        .ss-danger-body .ss-btn { width: 100%; }
    }
    @media (prefers-reduced-motion: reduce) { .ss-btn { transition: none; } .ss-btn:hover { transform: none; } }
</style>
@endpush

@section('content')
<x-gf.page
    class="ss-page"
    eyebrow="Owner · System"
    title="Pengaturan Sistem"
    description="Kelola tanggal cut-off untuk memisahkan data legacy dan transaksi produksi baru.">

    @if(session('success'))
        <div class="ss-alert ss-alert-success" role="status">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="ss-layout">
        <div class="ss-primary">
            <section class="ss-card" aria-labelledby="cutoff-status-title">
                <div class="ss-card-head">
                    <div class="ss-card-head-copy">
                        <div class="ss-card-title" id="cutoff-status-title"><i class="bi bi-calendar3" aria-hidden="true"></i>Status cut-off date</div>
                        <p class="ss-card-sub">Tanggal pemisah antara data lama (legacy) dan data baru. Data sebelum cut-off tetap tersimpan dan dapat dilihat dengan filter manual.</p>
                    </div>
                    @if($cutoffDate)
                        <span class="ss-status ss-status-on">Aktif · {{ \Carbon\Carbon::parse($cutoffDate)->format('d M Y') }}</span>
                    @else
                        <span class="ss-status ss-status-off">Belum di-set</span>
                    @endif
                </div>
                <div class="ss-card-body">
                    @if(! $cutoffDate)
                        <div class="ss-alert ss-alert-warning" role="alert"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i><span>Semua laporan masih menampilkan seluruh data historis. Set cut-off date untuk memulai periode produksi baru.</span></div>
                    @else
                        <div class="ss-cutoff-hero">
                            <div>
                                <div class="ss-kicker">Periode sistem baru dimulai</div>
                                <div class="ss-date">{{ \Carbon\Carbon::parse($cutoffDate)->translatedFormat('d F Y') }}</div>
                                <div class="ss-date-note">Laporan baru akan default mulai dari tanggal ini.</div>
                            </div>
                            <i class="bi bi-calendar2-check ss-cutoff-icon" aria-hidden="true"></i>
                        </div>

                        @if($stats)
                            <div class="ss-stat-grid" aria-label="Ringkasan data berdasarkan cut-off">
                                <div class="ss-stat ss-stat-legacy"><div class="ss-stat-label">Mutasi stok · legacy</div><div class="ss-stat-value">{{ number_format($stats->legacy_count) }}</div><div class="ss-stat-sub">Rp {{ number_format($stats->legacy_value, 0, ',', '.') }} sebelum cut-off</div></div>
                                <div class="ss-stat"><div class="ss-stat-label">Mutasi stok · baru</div><div class="ss-stat-value">{{ number_format($stats->new_count) }}</div><div class="ss-stat-sub">Rp {{ number_format($stats->new_value, 0, ',', '.') }} setelah cut-off</div></div>
                                <div class="ss-stat ss-stat-legacy"><div class="ss-stat-label">Jurnal · legacy</div><div class="ss-stat-value">{{ number_format($stats->journal_legacy) }}</div><div class="ss-stat-sub">Jurnal accounting lama</div></div>
                                <div class="ss-stat"><div class="ss-stat-label">Jurnal · baru</div><div class="ss-stat-value">{{ number_format($stats->journal_new) }}</div><div class="ss-stat-sub">Jurnal accounting baru</div></div>
                            </div>
                        @endif

                        @if($cutoffNotes)
                            <div class="ss-note mt-3" style="background:var(--ss-surface-soft);border-color:var(--ss-line);color:var(--ss-muted);"><strong style="color:var(--ss-text);">Catatan cut-off:</strong> {{ $cutoffNotes }}</div>
                        @endif
                    @endif
                </div>
            </section>

            <section class="ss-card" aria-labelledby="cutoff-form-title">
                <div class="ss-card-head">
                    <div class="ss-card-head-copy">
                        <div class="ss-card-title" id="cutoff-form-title"><i class="bi bi-sliders2" aria-hidden="true"></i>{{ $cutoffDate ? 'Ubah cut-off date' : 'Set cut-off date' }}</div>
                        <p class="ss-card-sub">Pilih tanggal awal periode baru. Tanggal tidak boleh berada di masa depan.</p>
                    </div>
                </div>
                <div class="ss-card-body">
                    <form action="{{ route('settings.system.cutoff.store') }}" method="POST">
                        @csrf
                        <div class="ss-form-grid">
                            <div class="ss-field">
                                <label class="ss-label" for="cutoff_date">Tanggal cut-off <span aria-hidden="true">*</span></label>
                                <input type="date" id="cutoff_date" name="cutoff_date" class="form-control @error('cutoff_date') is-invalid @enderror" value="{{ old('cutoff_date', $cutoffDate) }}" max="{{ now()->toDateString() }}" required>
                                @error('cutoff_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="ss-help">Laporan akan memakai tanggal ini sebagai awal periode default.</div>
                            </div>
                            <div class="ss-field">
                                <label class="ss-label" for="cutoff_notes">Catatan <span style="font-weight:500;color:var(--ss-muted);">(opsional)</span></label>
                                <textarea id="cutoff_notes" name="cutoff_notes" class="form-control" rows="2" maxlength="500" placeholder="Contoh: Reset produksi Juli 2026.">{{ old('cutoff_notes', $cutoffNotes) }}</textarea>
                                <div class="ss-help">Simpan konteks perubahan agar mudah ditelusuri oleh owner atau tim accounting.</div>
                            </div>
                        </div>
                        <div class="ss-actions"><button type="submit" class="ss-btn ss-btn-primary"><i class="bi bi-check2" aria-hidden="true"></i>Simpan cut-off date</button></div>
                    </form>
                </div>
            </section>

            @if($cutoffDate)
                <section class="ss-card ss-danger-card" aria-labelledby="clear-cutoff-title">
                    <div class="ss-card-head"><div class="ss-card-head-copy"><div class="ss-card-title" id="clear-cutoff-title"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Reset cut-off date</div><p class="ss-card-sub">Gunakan hanya jika laporan perlu kembali menampilkan seluruh data historis.</p></div></div>
                    <div class="ss-card-body ss-danger-body">
                        <div class="ss-danger-copy"><strong>Data tidak akan dihapus.</strong><br>Yang diubah hanya default filter laporan.</div>
                        <form action="{{ route('settings.system.cutoff.clear') }}" method="POST" onsubmit="return confirm('Yakin hapus cut-off date? Laporan akan kembali menampilkan semua data lama.')">
                            @csrf
                            <button type="submit" class="ss-btn ss-btn-danger"><i class="bi bi-trash3" aria-hidden="true"></i>Hapus cut-off</button>
                        </form>
                    </div>
                </section>
            @endif
        </div>

        <aside class="ss-aside" aria-label="Panduan opening balance">
            <section class="ss-card">
                <div class="ss-card-head"><div class="ss-card-head-copy"><div class="ss-card-title"><i class="bi bi-list-check" aria-hidden="true"></i>Alur opening balance</div><p class="ss-card-sub">Ikuti urutan ini setelah cut-off date ditetapkan.</p></div></div>
                <div class="ss-card-body">
                    <x-search-input
                        id="opening-guide-search"
                        class="ss-guide-search"
                        target="[data-opening-guide-item]"
                        count-id="opening-guide-count"
                        empty-id="opening-guide-empty"
                        item-label="panduan"
                        label="Cari panduan opening balance"
                        placeholder="Cari langkah, menu, atau kata kunci…" />
                    <ol class="ss-step-list">
                        <li class="ss-step" data-opening-guide-item><span class="ss-step-number">1</span><div><div class="ss-step-title">Set cut-off date</div><div class="ss-step-desc">Tentukan tanggal pertama sistem baru di form sebelah kiri.</div></div></li>
                        <li class="ss-step" data-opening-guide-item><span class="ss-step-number">2</span><div><div class="ss-step-title">Opening stock bahan baku</div><div class="ss-step-desc"><a class="ss-link" href="{{ route('inventory.stock_opnames.create') }}?type=opening" target="_blank" rel="noopener">Stock Opname → Opening</a>, pilih gudang RM, isi qty dan HPP.</div></div></li>
                        <li class="ss-step" data-opening-guide-item><span class="ss-step-number">3</span><div><div class="ss-step-title">Opening stock barang jadi</div><div class="ss-step-desc"><a class="ss-link" href="{{ route('inventory.stock_opnames.create') }}?type=opening" target="_blank" rel="noopener">Stock Opname → Opening</a> untuk gudang FG/WH-RTS.</div></div></li>
                        <li class="ss-step" data-opening-guide-item><span class="ss-step-number">4</span><div><div class="ss-step-title">Opening balance accounting</div><div class="ss-step-desc"><a class="ss-link" href="{{ route('accounting.opening-balances-batch.create') }}" target="_blank" rel="noopener">Opening Balance Batch</a> untuk kas, piutang, hutang, dan ekuitas.</div></div></li>
                        <li class="ss-step" data-opening-guide-item><span class="ss-step-number">5</span><div><div class="ss-step-title">Verifikasi trial balance</div><div class="ss-step-desc"><a class="ss-link" href="{{ route('accounting.trial-balance.index') }}" target="_blank" rel="noopener">Trial Balance</a>; pastikan debit dan kredit balance.</div></div></li>
                        <li class="ss-step" data-opening-guide-item><span class="ss-step-number">6</span><div><div class="ss-step-title">Mulai transaksi baru</div><div class="ss-step-desc">Lanjutkan Cutting, Sewing, Finishing, Shipment, dan transaksi operasional lain.</div></div></li>
                    </ol>
                    <div class="ss-guide-empty" id="opening-guide-empty" role="status"><i class="bi bi-search" aria-hidden="true"></i>Tidak ada langkah yang cocok. Coba kata kunci lain.</div>
                </div>
            </section>

            <div class="ss-note">
                <div class="ss-note-title"><i class="bi bi-info-circle-fill" aria-hidden="true"></i>Catatan penting</div>
                <ul><li>Data lama tidak dihapus dan tetap dapat difilter manual.</li><li>Opening stock membuat mutation baru, bukan menghapus stok lama.</li><li>Cek konfigurasi via <code>php artisan settings:show</code>.</li></ul>
            </div>
        </aside>
    </div>
</x-gf.page>
@endsection
