@extends('layouts.app')

@section('title', 'Pengaturan Sistem')

@push('head')
<style>
.ss-page { display: grid; gap: 1.25rem; max-width: 800px; }
.ss-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
    padding: 1.25rem 1.5rem;
}
.ss-card-title {
    font-size: .95rem; font-weight: 900; color: #0f172a;
    margin-bottom: .1rem; display: flex; align-items: center; gap: .5rem;
}
.ss-card-sub { font-size: .8rem; color: #94a3b8; margin-bottom: 1rem; }
.ss-label { display: block; margin-bottom: .3rem; font-size: .78rem; font-weight: 900; color: #475569; }
.ss-help  { margin-top: .25rem; font-size: .76rem; color: #94a3b8; }
.ss-field { margin-bottom: .9rem; }
.ss-field .form-control, .ss-field .form-control-sm {
    border-radius: 10px; border-color: #e2e8f0; box-shadow: none; font-size: .88rem;
}
.ss-btn {
    display: inline-flex; align-items: center; gap: .4rem; min-height: 38px;
    padding: .45rem .9rem; border-radius: 999px; border: 1px solid #e2e8f0;
    background: #fff; color: #0f172a; font-size: .84rem; font-weight: 850;
    text-decoration: none; cursor: pointer;
}
.ss-btn:hover { background: #f8fafc; }
.ss-btn-primary { background: #0f172a; color: #fff; border-color: #0f172a; }
.ss-btn-primary:hover { background: #1e293b; color: #fff; }
.ss-btn-danger  { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
.ss-btn-danger:hover { background: #fee2e2; }

.ss-badge-set   { background: #dcfce7; color: #166534; border-radius: 999px; padding: .2rem .7rem; font-size: .78rem; font-weight: 900; }
.ss-badge-none  { background: #fef9c3; color: #713f12; border-radius: 999px; padding: .2rem .7rem; font-size: .78rem; font-weight: 900; }

.ss-stat-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .75rem; margin-top: .75rem; }
.ss-stat-box {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: .75rem 1rem;
}
.ss-stat-label { font-size: .72rem; font-weight: 800; color: #94a3b8; margin-bottom: .15rem; }
.ss-stat-val   { font-size: 1.1rem; font-weight: 900; color: #0f172a; }
.ss-stat-sub   { font-size: .72rem; color: #64748b; }
.ss-legacy { border-left: 3px solid #f59e0b; }
.ss-new    { border-left: 3px solid #22c55e; }
.ss-alert-warn {
    background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px;
    padding: .8rem 1rem; font-size: .84rem; color: #92400e; margin-bottom: .85rem;
}
.ss-alert-ok {
    background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px;
    padding: .8rem 1rem; font-size: .84rem; color: #166534; margin-bottom: .85rem;
}
.ss-section-header {
    font-size: .78rem; font-weight: 900; color: #64748b;
    text-transform: uppercase; letter-spacing: .08em;
    margin-bottom: .6rem; margin-top: .25rem;
}
.ss-checklist { list-style: none; padding: 0; margin: 0; display: grid; gap: .4rem; }
.ss-checklist li {
    display: flex; gap: .5rem; align-items: flex-start;
    font-size: .84rem; color: #334155;
    background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 10px; padding: .5rem .75rem;
}
.ss-checklist li .icon { font-size: 1rem; flex-shrink: 0; margin-top: .05rem; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="ss-page">

        {{-- HEADER --}}
        <div>
            <h4 class="fw-black mb-0">⚙️ Pengaturan Sistem</h4>
            <p class="text-muted small mb-0">Konfigurasi global — hanya owner yang bisa mengubah.</p>
        </div>

        @if(session('success'))
            <div class="ss-alert-ok">✅ {{ session('success') }}</div>
        @endif

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- CARD 1: STATUS CUT-OFF SAAT INI                        --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div class="ss-card">
            <div class="ss-card-title">
                📅 Cut-off Date
                @if($cutoffDate)
                    <span class="ss-badge-set">Set: {{ \Carbon\Carbon::parse($cutoffDate)->format('d M Y') }}</span>
                @else
                    <span class="ss-badge-none">Belum di-set</span>
                @endif
            </div>
            <div class="ss-card-sub">
                Tanggal pemisah antara data lama (legacy) dan data baru. Laporan akan default menampilkan data
                mulai dari tanggal ini. Data sebelum cut-off tetap tersimpan dan bisa dilihat manual.
            </div>

            @if(! $cutoffDate)
                <div class="ss-alert-warn">
                    ⚠️ Cut-off date belum di-set. Semua laporan saat ini menampilkan semua data historis.
                    Set cut-off date untuk memisahkan data lama dengan produksi baru.
                </div>
            @else
                @if($stats)
                <div class="ss-stat-grid">
                    <div class="ss-stat-box ss-legacy">
                        <div class="ss-stat-label">MUTASI STOK — SEBELUM CUT-OFF (LEGACY)</div>
                        <div class="ss-stat-val">{{ number_format($stats->legacy_count) }}</div>
                        <div class="ss-stat-sub">Nilai: Rp {{ number_format($stats->legacy_value, 0, ',', '.') }}</div>
                    </div>
                    <div class="ss-stat-box ss-new">
                        <div class="ss-stat-label">MUTASI STOK — SETELAH CUT-OFF (BARU)</div>
                        <div class="ss-stat-val">{{ number_format($stats->new_count) }}</div>
                        <div class="ss-stat-sub">Nilai: Rp {{ number_format($stats->new_value, 0, ',', '.') }}</div>
                    </div>
                    <div class="ss-stat-box ss-legacy">
                        <div class="ss-stat-label">JURNAL — SEBELUM CUT-OFF (LEGACY)</div>
                        <div class="ss-stat-val">{{ number_format($stats->journal_legacy) }}</div>
                        <div class="ss-stat-sub">Jurnal accounting lama</div>
                    </div>
                    <div class="ss-stat-box ss-new">
                        <div class="ss-stat-label">JURNAL — SETELAH CUT-OFF (BARU)</div>
                        <div class="ss-stat-val">{{ number_format($stats->journal_new) }}</div>
                        <div class="ss-stat-sub">Jurnal accounting baru</div>
                    </div>
                </div>
                @endif

                @if($cutoffNotes)
                    <div class="mt-3 p-3" style="background:#f8fafc;border-radius:10px;font-size:.84rem;color:#475569;">
                        <strong>Catatan:</strong> {{ $cutoffNotes }}
                    </div>
                @endif
            @endif
        </div>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- CARD 2: SET CUT-OFF DATE                               --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div class="ss-card">
            <div class="ss-card-title">
                {{ $cutoffDate ? '✏️ Ubah' : '➕ Set' }} Cut-off Date
            </div>
            <div class="ss-card-sub">
                @if($cutoffDate)
                    Update tanggal cut-off. Semua laporan akan menyesuaikan defaultnya.
                @else
                    Tentukan tanggal mulai produksi baru. Data sebelum tanggal ini dianggap legacy.
                @endif
            </div>

            <form action="{{ route('settings.system.cutoff.store') }}" method="POST">
                @csrf
                <div class="ss-field">
                    <label class="ss-label" for="cutoff_date">Tanggal Cut-off *</label>
                    <input type="date"
                           id="cutoff_date"
                           name="cutoff_date"
                           class="form-control @error('cutoff_date') is-invalid @enderror"
                           value="{{ old('cutoff_date', $cutoffDate) }}"
                           max="{{ now()->toDateString() }}"
                           required>
                    @error('cutoff_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="ss-help">
                        Laporan baru akan default mulai dari tanggal ini. Data lama tetap ada.
                        Tidak perlu sama persis dengan hari ini — pilih tanggal awal periode baru yang diinginkan.
                    </div>
                </div>

                <div class="ss-field">
                    <label class="ss-label" for="cutoff_notes">Catatan (opsional)</label>
                    <textarea id="cutoff_notes" name="cutoff_notes"
                              class="form-control" rows="2"
                              placeholder="contoh: Reset produksi Juli 2026. Data sebelum ini dianggap legacy.">{{ old('cutoff_notes', $cutoffNotes) }}</textarea>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="ss-btn ss-btn-primary">
                        💾 Simpan Cut-off Date
                    </button>
                </div>
            </form>
        </div>

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- CARD 3: HAPUS CUT-OFF                                  --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        @if($cutoffDate)
        <div class="ss-card">
            <div class="ss-card-title">🗑️ Hapus Cut-off Date</div>
            <div class="ss-card-sub">
                Menghapus cut-off date <strong>tidak menghapus data apapun</strong>.
                Laporan akan kembali menampilkan semua data historis.
            </div>
            <form action="{{ route('settings.system.cutoff.clear') }}" method="POST"
                  onsubmit="return confirm('Yakin hapus cut-off date? Laporan akan kembali tampilkan semua data lama.')">
                @csrf
                <button type="submit" class="ss-btn ss-btn-danger">
                    🗑️ Hapus Cut-off Date
                </button>
            </form>
        </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- CARD 4: PANDUAN ALUR OPENING BALANCE                   --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        <div class="ss-card">
            <div class="ss-card-title">📋 Alur Opening Balance (Panduan)</div>
            <div class="ss-card-sub">
                Setelah cut-off date di-set, ikuti langkah berikut untuk memulai sistem produksi baru dari saldo resmi.
            </div>

            <div class="ss-section-header">Langkah yang harus dilakukan (urutan penting)</div>

            <ul class="ss-checklist">
                <li>
                    <span class="icon">1️⃣</span>
                    <div>
                        <strong>Set cut-off date</strong> di halaman ini (sudah selesai jika ada tanggal di atas).<br>
                        <span class="text-muted">Cut-off = tanggal pertama sistem baru. Transaksi setelah ini adalah data baru.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">2️⃣</span>
                    <div>
                        <strong>Input Opening Stock Bahan Baku</strong> via
                        <a href="{{ route('inventory.stock_opnames.create') }}?type=opening" target="_blank">
                            Stock Opname → Tipe: Opening
                        </a>
                        — pilih gudang RM, masukkan qty + HPP per item, finalize.<br>
                        <span class="text-muted">Ini akan membuat inventory_mutation bertipe opening_balance untuk setiap item.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">3️⃣</span>
                    <div>
                        <strong>Input Opening Stock Barang Jadi</strong> via
                        <a href="{{ route('inventory.stock_opnames.create') }}?type=opening" target="_blank">
                            Stock Opname → Tipe: Opening
                        </a>
                        — pilih gudang FG/WH-RTS, masukkan qty + HPP per SKU.<br>
                        <span class="text-muted">Buat satu opname per gudang.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">4️⃣</span>
                    <div>
                        <strong>Input Opening Balance Accounting</strong> via
                        <a href="{{ route('accounting.opening-balances-batch.create') }}" target="_blank">
                            Accounting → Opening Balance Batch
                        </a>
                        — masukkan saldo awal semua akun (kas, piutang, hutang, ekuitas).<br>
                        <span class="text-muted">Jurnal opening_balance_batch akan terbuat otomatis (idempotent).</span>
                    </div>
                </li>
                <li>
                    <span class="icon">5️⃣</span>
                    <div>
                        <strong>Verifikasi Trial Balance</strong> via
                        <a href="{{ route('accounting.trial-balance.index') }}" target="_blank">
                            Accounting → Trial Balance
                        </a>
                        — pastikan total debit = total kredit setelah opening balance.<br>
                        <span class="text-muted">Filter tanggal mulai dari cut-off date.</span>
                    </div>
                </li>
                <li>
                    <span class="icon">6️⃣</span>
                    <div>
                        <strong>Mulai transaksi baru</strong> — Cutting, Sewing, Finishing, Shipment, dll.<br>
                        <span class="text-muted">Semua laporan akan otomatis default mulai dari cut-off date.</span>
                    </div>
                </li>
            </ul>

            <div class="mt-3 p-3" style="background:#eff6ff;border-radius:10px;border:1px solid #bfdbfe;">
                <div style="font-size:.8rem;font-weight:900;color:#1d4ed8;margin-bottom:.35rem;">ℹ️ Catatan Penting</div>
                <ul style="font-size:.82rem;color:#1e3a8a;padding-left:1rem;margin:0;line-height:1.7">
                    <li>Data lama TIDAK dihapus — selalu bisa dilihat dengan filter tanggal manual di setiap laporan.</li>
                    <li>Opening stock membuat inventory mutation bertipe <code>stock_opname_adjustment</code> — bukan hapus stok lama.</li>
                    <li>Artisan: <code>php artisan settings:show</code> — cek semua system settings via terminal.</li>
                    <li>Rollback: <code>php artisan migrate:rollback --step=1</code> — hapus tabel system_settings (tidak mempengaruhi data lain).</li>
                </ul>
            </div>
        </div>

    </div>
</div>
@endsection
