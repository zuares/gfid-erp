@extends('layouts.app')

@section('title', 'Pengaturan WhatsApp')

@push('head')
<style>
.wa-page { display: grid; gap: 1.25rem; max-width: 800px; }
.wa-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.25rem 1.5rem; }
.wa-card-title { font-size: .95rem; font-weight: 900; color: #0f172a; margin-bottom: .15rem; display: flex; align-items: center; gap: .5rem; }
.wa-card-sub { font-size: .8rem; color: #64748b; margin-bottom: 1rem; }
.wa-label { display: block; margin-bottom: .35rem; font-size: .78rem; font-weight: 900; color: #475569; }
.wa-help { margin-top: .3rem; font-size: .76rem; color: #94a3b8; }
.wa-field { margin-bottom: .95rem; }
.wa-field .form-control { border-radius: 10px; border-color: #e2e8f0; box-shadow: none; font-size: .88rem; }
.wa-btn { display: inline-flex; align-items: center; gap: .4rem; min-height: 38px; padding: .45rem .9rem; border-radius: 999px; border: 1px solid #0f172a; background: #0f172a; color: #fff; font-size: .84rem; font-weight: 850; cursor: pointer; }
.wa-btn:hover { background: #1e293b; color: #fff; }
.wa-badge-ok, .wa-badge-warn { display: inline-flex; border-radius: 999px; padding: .2rem .7rem; font-size: .76rem; font-weight: 900; }
.wa-badge-ok { background: #dcfce7; color: #166534; }
.wa-badge-warn { background: #fef9c3; color: #713f12; }
.wa-alert { border-radius: 12px; padding: .8rem 1rem; font-size: .84rem; margin-bottom: .85rem; }
.wa-alert-ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.wa-alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.wa-list { margin: 0; padding-left: 1.15rem; color: #475569; font-size: .84rem; }
.wa-list li + li { margin-top: .35rem; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="wa-page">
        <div>
            <h4 class="fw-black mb-0">💬 Pengaturan WhatsApp</h4>
            <p class="text-muted small mb-0">Koneksi Fonnte dan pengiriman pesan test — hanya owner yang bisa mengubah.</p>
        </div>

        @if(session('success'))
            <div class="wa-alert wa-alert-ok">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="wa-alert wa-alert-error">⚠️ {{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="wa-alert wa-alert-error">
                @foreach($errors->all() as $error)
                    <div>⚠️ {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="wa-card">
            <div class="wa-card-title">
                🔌 Status koneksi
                @if($isConfigured)
                    <span class="wa-badge-ok">Token tersedia</span>
                @else
                    <span class="wa-badge-warn">Belum dikonfigurasi</span>
                @endif
            </div>
            <div class="wa-card-sub">Token Fonnte dibaca dari <code>FONNTE_TOKEN</code> di <code>.env</code> dan tidak ditampilkan di halaman.</div>
            <ul class="wa-list">
                <li>Provider: Fonnte</li>
                <li>Format nomor: Indonesia, contoh <code>628xxxxxxxxxx</code> atau <code>08xxxxxxxxxx</code></li>
                <li>Pesan test dikirim langsung saat tombol ditekan.</li>
            </ul>
        </div>

        <div class="wa-card">
            <div class="wa-card-title">📤 Kirim pesan test</div>
            <div class="wa-card-sub">Gunakan nomor pribadi atau nomor test terlebih dahulu sebelum mengaktifkan notifikasi operasional.</div>

            <form method="POST" action="{{ route('settings.whatsapp.test') }}">
                @csrf
                <div class="wa-field">
                    <label class="wa-label" for="test_number">Nomor tujuan</label>
                    <input id="test_number" name="test_number" type="text" class="form-control"
                           value="{{ old('test_number', $testNumber) }}"
                           placeholder="628xxxxxxxxxx" required>
                    <div class="wa-help">Nomor akan dinormalisasi otomatis dari format 08... menjadi 62...</div>
                </div>

                <div class="wa-field">
                    <label class="wa-label" for="test_message">Pesan</label>
                    <textarea id="test_message" name="test_message" class="form-control" rows="5" maxlength="1000" required>{{ old('test_message', $testMessage) }}</textarea>
                </div>

                <button class="wa-btn" type="submit">📨 Kirim pesan test</button>
            </form>
        </div>
    </div>
</div>
@endsection
