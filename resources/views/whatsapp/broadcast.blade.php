@extends('layouts.app')

@section('title', 'Broadcast WhatsApp')

@push('head')
<style>
.wa-broadcast{max-width:900px;margin:0 auto;padding:1rem .75rem 3rem}
.wa-broadcast-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1.1rem;box-shadow:0 4px 16px rgba(15,23,42,.03)}
.wa-broadcast-title{font-size:1.35rem;font-weight:950;color:#0f172a;margin:0}
.wa-broadcast-subtitle{color:#64748b;font-size:.84rem;margin:.25rem 0 1rem}
.wa-label{display:block;margin-bottom:.35rem;font-size:.76rem;font-weight:900;color:#475569}
.wa-field{margin-bottom:.85rem}
.wa-field .form-control,.wa-field .form-select{border-radius:10px;border-color:#e2e8f0;box-shadow:none;font-size:.85rem}
.wa-textarea{min-height:180px;resize:vertical}
.wa-help{font-size:.72rem;color:#94a3b8;margin-top:.25rem}
.wa-btn{display:inline-flex;align-items:center;gap:.4rem;min-height:36px;padding:.4rem .8rem;border:1px solid #166534;border-radius:10px;background:#15803d;color:#fff;font-size:.8rem;font-weight:900;cursor:pointer}
.wa-btn:hover{background:#166534;color:#fff}
.wa-btn-secondary{background:#fff;border-color:#cbd5e1;color:#475569;text-decoration:none}
.wa-btn-secondary:hover{background:#f8fafc;color:#0f172a}
.wa-alert{border-radius:12px;padding:.7rem .9rem;font-size:.82rem;margin-bottom:1rem}
.wa-alert-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.wa-status{border-radius:12px;padding:.7rem .9rem;font-size:.8rem;margin-bottom:1rem}
.wa-status-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
.wa-status-error{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412}
</style>
@endpush

@section('content')
<div class="wa-broadcast">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h1 class="wa-broadcast-title"><i class="bi bi-megaphone" style="color:#15803d"></i> Broadcast WhatsApp</h1>
            <p class="wa-broadcast-subtitle">Kirim satu pesan ke beberapa nomor sekaligus melalui satu request Fonnte.</p>
        </div>
        <a class="wa-btn wa-btn-secondary" href="{{ route('whatsapp.index') }}"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    @if(session('error'))
        <div class="wa-alert wa-alert-error">⚠️ {{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="wa-alert wa-alert-error">
            @foreach($errors->all() as $error)<div>⚠️ {{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="wa-status {{ $isConfigured ? 'wa-status-ok' : 'wa-status-error' }}">
        {{ $isConfigured ? '✅ Token Fonnte sudah tersedia.' : '⚠️ Token Fonnte belum dikonfigurasi. Broadcast belum bisa dikirim.' }}
    </div>

    <div class="wa-broadcast-card">
        <form method="POST" action="{{ route('whatsapp.broadcast.send') }}">
            @csrf
            <div class="wa-field">
                <label class="wa-label" for="recipients">Nomor tujuan</label>
                <textarea id="recipients" name="recipients" class="form-control wa-textarea" placeholder="08123456789&#10;628987654321&#10;&#10;atau pisahkan dengan koma" required>{{ old('recipients') }}</textarea>
                <div class="wa-help">Satu nomor per baris atau pisahkan dengan koma, titik koma, atau spasi. Nomor duplikat otomatis dihapus. Maksimal 100 nomor.</div>
            </div>

            <div class="wa-field">
                <label class="wa-label" for="template_key">Gunakan template (opsional)</label>
                <select id="template_key" name="template_key" class="form-select">
                    <option value="">Pesan manual</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->key }}" @selected(old('template_key') === $template->key)>{{ $template->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="wa-field">
                <label class="wa-label" for="message">Isi pesan</label>
                <textarea id="message" name="message" class="form-control" rows="8" maxlength="4000" required>{{ old('message') }}</textarea>
                <div class="wa-help">Setiap penerima akan mendapatkan isi pesan yang sama. Status dicatat per nomor di riwayat WhatsApp.</div>
            </div>

            <button class="wa-btn" type="submit" @disabled(! $isConfigured)><i class="bi bi-send"></i> Kirim broadcast</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('template_key');
    const message = document.getElementById('message');
    const bodies = @json($templates->mapWithKeys(fn ($template) => [$template->key => $template->body]));

    if (!select || !message) return;
    select.addEventListener('change', function () {
        if (this.value && bodies[this.value] !== undefined) {
            message.value = bodies[this.value];
        }
    });
});
</script>
@endsection
