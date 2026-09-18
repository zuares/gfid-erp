@extends('layouts.app')

@section('title', 'Follow up WhatsApp - '.$contextTitle)

@push('head')
<style>
.wa-prospect{max-width:1080px;margin:0 auto;padding:1rem .75rem 3rem}
.wa-prospect-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1rem 1.1rem;box-shadow:0 4px 16px rgba(15,23,42,.03)}
.wa-prospect-title{font-size:1.2rem;font-weight:950;color:#0f172a;margin:0}
.wa-prospect-subtitle{color:#64748b;font-size:.82rem;margin:.25rem 0 1rem}
.wa-prospect-label{display:block;margin-bottom:.35rem;font-size:.76rem;font-weight:900;color:#475569}
.wa-prospect-field{margin-bottom:.9rem}
.wa-prospect-field .form-control,.wa-prospect-field .form-select{border-radius:10px;border-color:#e2e8f0;box-shadow:none;font-size:.85rem}
.wa-prospect-message{min-height:300px;resize:vertical;line-height:1.6}
.wa-prospect-recipient{display:flex;align-items:center;gap:.7rem;padding:.75rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;margin-bottom:1rem}
.wa-prospect-recipient-icon{width:38px;height:38px;display:grid;place-items:center;border-radius:50%;background:#25d366;color:#fff;font-size:1.1rem}
.wa-prospect-recipient-name{font-weight:900;color:#166534}.wa-prospect-recipient-phone{font-size:.76rem;color:#64748b}
.wa-prospect-actions{display:flex;justify-content:space-between;gap:.6rem;border-top:1px solid #f1f5f9;padding-top:1rem;margin-top:1rem}
.wa-prospect-btn{display:inline-flex;align-items:center;gap:.4rem;min-height:38px;padding:.45rem .9rem;border-radius:10px;font-size:.82rem;font-weight:900;text-decoration:none;cursor:pointer}
.wa-prospect-btn-primary{border:1px solid #166534;background:#15803d;color:#fff}.wa-prospect-btn-primary:hover{background:#166534;color:#fff}
.wa-prospect-btn-secondary{border:1px solid #cbd5e1;background:#fff;color:#475569}.wa-prospect-btn-secondary:hover{background:#f8fafc;color:#0f172a}
.wa-prospect-alert{border-radius:12px;padding:.7rem .9rem;font-size:.82rem;margin-bottom:1rem}.wa-prospect-alert-warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
.wa-prospect-help{font-size:.75rem;color:#64748b;margin-top:.3rem}
</style>
@endpush

@section('content')
<div class="wa-prospect">
    <div class="mb-3">
        <a href="{{ $contextUrl }}" class="text-secondary text-decoration-none" style="font-size:.8rem;"><i class="bi bi-arrow-left me-1"></i>Kembali ke Prospects Marketplace</a>
        <h1 class="wa-prospect-title mt-2"><i class="bi bi-whatsapp" style="color:#25d366"></i> Follow up lewat Fonnte</h1>
        <p class="wa-prospect-subtitle">Tinjau dan edit pesan sebelum dikirim melalui koneksi WhatsApp Fonnte aplikasi.</p>
    </div>

    @if(!$isConfigured)
        <div class="wa-prospect-alert wa-prospect-alert-warn">⚠️ Token Fonnte belum dikonfigurasi. Pesan tidak dapat dikirim sebelum koneksi WhatsApp diatur.</div>
    @endif
    @if($errors->any())
        <div class="wa-prospect-alert wa-prospect-alert-warn">@foreach($errors->all() as $error)<div>⚠️ {{ $error }}</div>@endforeach</div>
    @endif

    <div class="wa-prospect-card">
        <div class="wa-prospect-recipient">
            <div class="wa-prospect-recipient-icon"><i class="bi bi-whatsapp"></i></div>
            <div><div class="wa-prospect-recipient-name">{{ $draft['recipient_name'] ?: 'Customer Marketplace' }}</div><div class="wa-prospect-recipient-phone">{{ $draft['phone'] }}</div></div>
        </div>

        <form method="POST" action="{{ route('whatsapp.messages.send') }}" onsubmit="return confirm('Kirim pesan ini melalui WhatsApp Fonnte?');">
            @csrf
            <input type="hidden" name="module" value="{{ $draft['module'] }}">
            <input type="hidden" name="reference_type" value="{{ $draft['reference_type'] }}">
            <input type="hidden" name="reference_id" value="{{ $draft['reference_id'] }}">
            <input type="hidden" name="reference_label" value="{{ $draft['reference_label'] }}">
            <input type="hidden" name="return_to" value="marketplace_prospect">
            <input type="hidden" name="recipient_name" value="{{ $draft['recipient_name'] }}">
            <input type="hidden" name="recipient_phone" value="{{ $draft['phone'] }}">

            <div class="wa-prospect-field">
                <label class="wa-prospect-label" for="template_key">Template pesan</label>
                <select id="template_key" name="template_key" class="form-select">
                    <option value="">Pesan custom</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->key }}">{{ $template->name }}</option>
                    @endforeach
                </select>
                <div class="wa-prospect-help">Template diambil dari WhatsApp Center dan masih bisa diedit sebelum dikirim.</div>
            </div>

            <div class="wa-prospect-field">
                <label class="wa-prospect-label" for="message">Isi pesan</label>
                <textarea id="message" name="message" class="form-control wa-prospect-message" maxlength="4000" required>{{ $draft['message'] }}</textarea>
                <div id="messageCount" class="wa-prospect-help">{{ strlen($draft['message']) }}/4000</div>
            </div>

            <div class="wa-prospect-actions">
                <a href="{{ $contextUrl }}" class="wa-prospect-btn wa-prospect-btn-secondary"><i class="bi bi-x-lg"></i>Batal</a>
                <button type="submit" class="wa-prospect-btn wa-prospect-btn-primary" @disabled(!$isConfigured)><i class="bi bi-whatsapp"></i>Kirim lewat Fonnte</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('template_key');
    const message = document.getElementById('message');
    const counter = document.getElementById('messageCount');
    const bodies = @json($templates->mapWithKeys(fn ($template) => [$template->key => $template->body]));
    const variables = @json($draft['variables']);

    function renderTemplate(body) {
        return body.replace(/\{([a-z0-9_]+)\}/gi, (match, key) => variables[key] !== undefined ? variables[key] : match);
    }
    function updateCount() {
        if (counter && message) counter.textContent = message.value.length + '/4000';
    }
    if (!select || !message) return;
    select.addEventListener('change', function () {
        message.value = this.value && bodies[this.value] !== undefined ? renderTemplate(bodies[this.value]) : '';
        updateCount();
    });
    message.addEventListener('input', updateCount);
});
</script>
@endsection
