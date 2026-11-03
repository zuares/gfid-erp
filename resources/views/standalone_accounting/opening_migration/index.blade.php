@extends('layouts.app')
@section('title', 'Akuntansi Baru • Opening Migrasi')

@push('head')
    <style>
        .opening-migration-page { max-width:1280px; margin-inline:auto; display:grid; gap:1rem; }
        .opening-migration-page .migration-mapping { display:grid; grid-template-columns: minmax(0, 1fr) minmax(240px, 1fr); gap:1rem; align-items:center; }
        .opening-migration-page .migration-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.75rem; }
        .opening-migration-page .migration-money { font-variant-numeric:tabular-nums; white-space:nowrap; }
        .opening-migration-page .correction-line { display:grid; grid-template-columns:minmax(0,2fr) minmax(0,1fr) minmax(0,1fr); gap:.5rem; }
        @media(max-width:767px) {
            .opening-migration-page .migration-mapping, .opening-migration-page .migration-summary, .opening-migration-page .correction-line { grid-template-columns:1fr; }
        }
    </style>
@endpush

@section('content')
@php($money = fn ($cents) => 'Rp '.number_format($cents / 100, 2, ',', '.'))
<div class="opening-migration-page">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h4 class="mb-1">Opening Migrasi</h4>
            <p class="text-muted mb-0">Pindahkan saldo pembukuan lama ke Akuntansi Baru satu kali, dengan jejak sumber dan mapping akun.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('standalone-accounting.journal-menu.index') }}">Menu Jurnal</a>
    </div>
    @if (session('message'))<div class="alert alert-success mb-0" role="status">{{ session('message') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger mb-0" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <div class="alert alert-info mb-0">
        Opening ini hanya mencatat nilai ke Akuntansi Baru. Stok, dokumen utang, dan jurnal lama tetap seperti semula.
        Saldo pendapatan dan beban sumber ikut dibawa; proses ini tidak menutup tahun buku atau memasukkan selisih ke modal.
    </div>

    @if ($migration->posted_at)
        <div class="card"><div class="card-body">
            <h5>Opening sudah diposting</h5>
            <p>Saldo akhir {{ $snapshot['as_of'] }} · oleh {{ $migration->poster?->name }} · {{ $migration->posted_at->format('d-m-Y H:i') }}.</p>
            <a class="btn btn-primary" href="{{ route('standalone-accounting.journals.show', $migration->journal_id) }}">Lihat jurnal opening</a>
            <p class="small text-muted mt-3 mb-0">Data di bawah adalah snapshot saat posting. Koreksi dibuat pada bagian terpisah dan tidak mengubah snapshot ini.</p>
        </div></div>
    @else
        <div class="card"><div class="card-body">
            <h5>1. Pilih tanggal saldo akhir</h5>
            <form method="GET" action="{{ route('standalone-accounting.opening-migration.index') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="as-of" class="form-label">Saldo akhir tanggal (termasuk hari tersebut)</label>
                    <input id="as-of" class="form-control" type="date" name="as_of" value="{{ $snapshot['as_of'] }}" min="{{ $snapshot['source_from'] }}" max="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-md-auto"><button class="btn btn-outline-primary">Ambil saldo lama</button></div>
            </form>
        </div></div>
    @endif

    <div class="card"><div class="card-body">
        <h5>{{ $migration->posted_at ? 'Sumber opening' : '2. Mapping saldo sumber' }}</h5>
        <p class="small text-muted">Periode sumber: <strong>{{ $snapshot['source_from'] ?? 'Awal seluruh riwayat (cutoff belum diatur)' }}</strong> sampai <strong>{{ $snapshot['as_of'] }}</strong>.
            Hanya jurnal posted dan aktif; {{ count($snapshot['source_journal_ids']) }} jurnal sumber. Batas ini mengikuti pengaturan cutoff lama.</p>
        <p class="small">Transaksi setelah <strong>{{ $snapshot['as_of'] }}</strong> masuk periode mirror mulai <strong>{{ $snapshot['mirror_from'] }}</strong>.
            Pastikan transaksi sampai tanggal saldo akhir sudah lengkap. Halaman ini tidak mengaktifkan mirror otomatis.</p>
        @if ($snapshot['warnings'])
            <div class="alert alert-warning"><strong>Perlu ditinjau</strong><ul class="mb-0">@foreach ($snapshot['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach</ul></div>
        @endif
        @if ($snapshot['issues'])
            <div class="alert alert-danger" role="alert"><strong>Posting belum tersedia</strong><ul class="mb-0">@foreach ($snapshot['issues'] as $issue)<li>{{ $issue }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" action="{{ route('standalone-accounting.opening-migration.preview') }}">
            @csrf
            <input type="hidden" name="as_of" value="{{ $snapshot['as_of'] }}">
            @forelse ($snapshot['rows'] as $row)
                <div class="migration-mapping border-bottom py-3">
                    <div>
                        <label for="mapping-{{ $row['legacy_id'] }}" class="fw-semibold">{{ $row['code'] }} — {{ $row['name'] }}</label>
                        <div class="small text-muted">{{ $row['type'] }} · {{ $row['net'] > 0 ? 'Debit' : 'Kredit' }} <span class="migration-money">{{ $money(abs($row['net'])) }}</span></div>
                    </div>
                    @if ($migration->posted_at || $previewToken)
                        <div>{{ $row['target_code'] }} — {{ $row['target_name'] }}<input type="hidden" name="mapping[{{ $row['legacy_id'] }}]" value="{{ $row['target_id'] }}"></div>
                    @else
                        <select class="form-select" id="mapping-{{ $row['legacy_id'] }}" name="mapping[{{ $row['legacy_id'] }}]" required>
                            <option value="">Pilih akun baru ({{ $row['type'] }})</option>
                            @foreach ($accounts->where('type', $row['type']) as $account)
                                <option value="{{ $account->id }}" @selected($row['target_id'] == $account->id)>{{ $account->code }} — {{ $account->name }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            @empty
                <p class="text-muted">Belum ada saldo pada periode ini.</p>
            @endforelse
            @if (! $migration->posted_at && count($snapshot['rows']))
                <p class="small text-muted mt-3">Mapping berlaku untuk opening ini. Mapping transaksi harian tetap dikelola terpisah. Nominal sumber tidak dapat diedit; gunakan jurnal koreksi setelah posting.</p>
                @if ($previewToken)
                    <button class="btn btn-outline-secondary" name="edit_mapping" value="1" formaction="{{ route('standalone-accounting.opening-migration.preview') }}">Ubah mapping</button>
                @else
                    <button class="btn btn-primary">Tinjau mapping dan selisih</button>
                @endif
            @endif
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <h5>{{ $migration->posted_at ? 'Hasil pemeriksaan saat posting' : '3. Periksa saldo dan selisih' }}</h5>
        <div class="migration-summary mb-3">
            <div class="border rounded p-3"><div class="small text-muted">Debit sumber</div><strong class="migration-money">{{ $money($snapshot['debit']) }}</strong></div>
            <div class="border rounded p-3"><div class="small text-muted">Kredit sumber</div><strong class="migration-money">{{ $money($snapshot['credit']) }}</strong></div>
            <div class="border rounded p-3"><div class="small text-muted">Selisih debit − kredit</div><strong class="migration-money">{{ $money($snapshot['debit'] - $snapshot['credit']) }}</strong></div>
        </div>
        <p class="small">Belum terpetakan: debit <strong>{{ $money($snapshot['debit'] - $snapshot['mapped_debit']) }}</strong>, kredit <strong>{{ $money($snapshot['credit'] - $snapshot['mapped_credit']) }}</strong>.</p>
        <div class="table-responsive" tabindex="0" aria-label="Perbandingan saldo per akun tujuan">
            <table class="table table-sm align-middle">
                <thead><tr><th>Akun baru</th><th class="text-end">Sumber terpetakan</th><th class="text-end">Saldo baru sebelum</th><th class="text-end">Selisih sumber − baru</th></tr></thead>
                <tbody>@foreach ($snapshot['comparison'] as $line)
                    <tr><td>{{ $line['code'] }} — {{ $line['name'] }}</td><td class="text-end migration-money">{{ $money($line['debit'] - $line['credit']) }}</td><td class="text-end migration-money">{{ $money($line['existing_net']) }}</td><td class="text-end migration-money">{{ $money($line['debit'] - $line['credit'] - $line['existing_net']) }}</td></tr>
                @endforeach</tbody>
            </table>
        </div>
        <p class="small text-muted mb-0">Angka positif = saldo debit, negatif = saldo kredit. Saat ledger awal kosong, selisih adalah nilai opening yang akan dibawa; setelah posting, saldo baru harus sama dengan sumber terpetakan. Saldo lama tidak dinetralkan dengan transaksi baru yang sudah ada.</p>
    </div></div>

    @if ($previewToken && ! $migration->posted_at)
        <div class="card"><div class="card-body">
            <h5>4. Posting opening satu kali</h5>
            <p>Total jurnal: debit <strong>{{ $money($snapshot['mapped_debit']) }}</strong> · kredit <strong>{{ $money($snapshot['mapped_credit']) }}</strong>.</p>
            <form method="POST" action="{{ route('standalone-accounting.opening-migration.store') }}">
                @csrf
                <input type="hidden" name="preview_token" value="{{ $previewToken }}">
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="confirm-opening" name="confirmed" value="1" required><label class="form-check-label" for="confirm-opening">Tanggal, kelengkapan sumber, mapping, dan saldo telah diperiksa. Posting hanya ke Akuntansi Baru tanpa mutasi stok atau dokumen utang.</label></div>
                <button class="btn btn-primary" type="submit">Posting Opening Migrasi</button>
                <p class="small text-muted mt-2 mb-0">Preview berlaku 30 menit. Perubahan sumber atau saldo baru akan meminta preview ulang.</p>
            </form>
        </div></div>
    @endif

    @if ($migration->posted_at)
        <div class="card"><div class="card-body">
            <h5>Koreksi opening</h5>
            <p>Koreksi dicatat sebagai jurnal baru dengan penjelasan dan referensi bukti. Untuk membatalkan koreksi yang salah, buat koreksi balik dengan menyebut referensi sebelumnya.</p>
            @forelse ($migration->corrections as $correction)
                <div class="border rounded p-3 mb-2">
                    <a href="{{ route('standalone-accounting.journals.show', $correction->journal_id) }}">{{ $correction->journal?->reference }}</a>
                    <div class="small text-muted">{{ $correction->journal?->date?->format('d-m-Y') }} · {{ $correction->poster?->name }}</div>
                    <div class="mt-1" style="white-space:pre-wrap">{{ $correction->reason }}</div>
                </div>
            @empty<p class="text-muted">Belum ada koreksi.</p>@endforelse
            <hr>
            <form method="POST" action="{{ route('standalone-accounting.opening-migration.correct') }}">
                @csrf
                <input type="hidden" name="request_token" value="{{ $correctionToken }}">
                <label class="form-label" for="correction-date">Tanggal koreksi</label>
                <input class="form-control mb-3" id="correction-date" name="date" type="date" min="{{ $snapshot['as_of'] }}" max="{{ now()->toDateString() }}" value="{{ old('date', now()->toDateString()) }}" required>
                <label class="form-label" for="correction-reason">Penjelasan dan referensi bukti koreksi</label>
                <textarea class="form-control mb-3" id="correction-reason" name="reason" rows="3" maxlength="2000" required placeholder="Jelaskan akun yang dikoreksi, alasan, dan referensi dokumen pendukung.">{{ old('reason') }}</textarea>
                <div id="correction-lines" class="d-grid gap-3">
                    @foreach (old('lines', array_fill(0, 4, [])) as $i => $line)
                        <div class="correction-line border-bottom pb-3">
                            <div><label class="form-label" for="correction-account-{{ $i }}">Akun {{ $i + 1 }}</label><select class="form-select" id="correction-account-{{ $i }}" name="lines[{{ $i }}][account_id]"><option value="">Pilih akun</option>@foreach ($accounts as $account)<option value="{{ $account->id }}" @selected(($line['account_id'] ?? null) == $account->id)>{{ $account->code }} — {{ $account->name }}</option>@endforeach</select></div>
                            <div><label class="form-label" for="correction-debit-{{ $i }}">Debit (Rp)</label><input class="form-control" id="correction-debit-{{ $i }}" name="lines[{{ $i }}][debit]" type="number" min="0" step="0.01" value="{{ $line['debit'] ?? '' }}"></div>
                            <div><label class="form-label" for="correction-credit-{{ $i }}">Kredit (Rp)</label><input class="form-control" id="correction-credit-{{ $i }}" name="lines[{{ $i }}][credit]" type="number" min="0" step="0.01" value="{{ $line['credit'] ?? '' }}"></div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-outline-secondary my-3" id="add-correction-line">Tambah baris</button>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="confirm-correction" name="confirmed" value="1" required><label class="form-check-label" for="confirm-correction">Koreksi dan dasar buktinya telah diperiksa. Jurnal koreksi hanya memengaruhi Akuntansi Baru.</label></div>
                <button class="btn btn-primary">Posting Koreksi Terpisah</button>
            </form>
        </div></div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.getElementById('add-correction-line')?.addEventListener('click', () => {
    const container = document.getElementById('correction-lines');
    const index = container.children.length;
    if (index >= 100) return;
    const row = container.firstElementChild.cloneNode(true);
    row.querySelectorAll('input, select').forEach(control => {
        control.name = control.name.replace(/lines\[\d+\]/, `lines[${index}]`);
        control.id = control.id.replace(/\d+$/, String(index));
        control.value = '';
    });
    row.querySelectorAll('label').forEach(label => {
        label.htmlFor = label.htmlFor.replace(/\d+$/, String(index));
        if (label.textContent.startsWith('Akun ')) label.textContent = `Akun ${index + 1}`;
    });
    container.appendChild(row);
    row.querySelector('select').focus();
});
</script>
@endpush
