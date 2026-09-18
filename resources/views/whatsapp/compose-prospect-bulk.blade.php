@extends('layouts.app')

@section('title', 'Bulk Follow up WhatsApp')

@push('head')
<style>
.wa-bulk{max-width:1080px;margin:0 auto;padding:1rem .75rem 3rem}.wa-bulk-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1rem 1.1rem;box-shadow:0 4px 16px rgba(15,23,42,.03)}.wa-bulk-title{font-size:1.2rem;font-weight:950;color:#0f172a;margin:0}.wa-bulk-subtitle{color:#64748b;font-size:.82rem;margin:.25rem 0 1rem}.wa-bulk-label{display:block;margin-bottom:.35rem;font-size:.76rem;font-weight:900;color:#475569}.wa-bulk-field{margin-bottom:.9rem}.wa-bulk-field .form-control,.wa-bulk-field .form-select{border-radius:10px;border-color:#e2e8f0;box-shadow:none;font-size:.85rem}.wa-bulk-message{min-height:300px;resize:vertical;line-height:1.6}.wa-bulk-alert{border-radius:12px;padding:.7rem .9rem;font-size:.82rem;margin-bottom:1rem}.wa-bulk-warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e}.wa-bulk-recipients{max-height:280px;overflow:auto;margin:0 0 1rem;padding:0;list-style:none;border:1px solid #e2e8f0;border-radius:12px}.wa-bulk-recipient{display:flex;align-items:center;gap:.65rem;padding:.65rem .75rem;border-bottom:1px solid #f1f5f9}.wa-bulk-recipient:last-child{border-bottom:0}.wa-bulk-icon{width:30px;height:30px;display:grid;place-items:center;border-radius:50%;background:#25d366;color:#fff}.wa-bulk-name{font-weight:800;color:#166534;font-size:.8rem}.wa-bulk-phone{font-size:.72rem;color:#64748b}.wa-bulk-actions{display:flex;justify-content:space-between;gap:.6rem;border-top:1px solid #f1f5f9;padding-top:1rem;margin-top:1rem}.wa-bulk-btn{display:inline-flex;align-items:center;gap:.4rem;min-height:38px;padding:.45rem .9rem;border-radius:10px;font-size:.82rem;font-weight:900;text-decoration:none;cursor:pointer}.wa-bulk-primary{border:1px solid #166534;background:#15803d;color:#fff}.wa-bulk-primary:hover{background:#166534;color:#fff}.wa-bulk-secondary{border:1px solid #cbd5e1;background:#fff;color:#475569}.wa-bulk-secondary:hover{background:#f8fafc;color:#0f172a}.wa-bulk-help{font-size:.75rem;color:#64748b;margin-top:.3rem}
</style>
@endpush

@section('content')
<div class="wa-bulk">
    <div class="mb-3">
        <a href="{{ $contextUrl }}" class="text-secondary text-decoration-none" style="font-size:.8rem;"><i class="bi bi-arrow-left me-1"></i>Kembali ke Prospects Marketplace</a>
        <h1 class="wa-bulk-title mt-2"><i class="bi bi-whatsapp" style="color:#25d366"></i> Bulk follow-up lewat Fonnte</h1>
        <p class="wa-bulk-subtitle">Tinjau penerima dan edit pesan sebelum dikirim ke semua customer yang dipilih.</p>
    </div>

    @if(!$isConfigured)<div class="wa-bulk-alert wa-bulk-warn">⚠️ Token Fonnte belum dikonfigurasi. Pesan tidak dapat dikirim sebelum koneksi WhatsApp diatur.</div>@endif
    @if($errors->any())<div class="wa-bulk-alert wa-bulk-warn">@foreach($errors->all() as $error)<div>⚠️ {{ $error }}</div>@endforeach</div>@endif
    @if(session('error'))<div class="wa-bulk-alert wa-bulk-warn">⚠️ {{ session('error') }}</div>@endif

    <div class="wa-bulk-card">
        <div class="d-flex justify-content-between align-items-center mb-2"><div class="fw-bold">Penerima ({{ $customers->count() }})</div><span class="text-secondary" style="font-size:.72rem;">Maksimal 100 customer</span></div>
        <ul class="wa-bulk-recipients">
            @foreach($customers as $customer)
                <li class="wa-bulk-recipient"><div class="wa-bulk-icon"><i class="bi bi-whatsapp"></i></div><div><div class="wa-bulk-name">{{ $customer->name ?: 'Customer Marketplace' }}</div><div class="wa-bulk-phone">{{ $customer->wa_phone }}</div></div><input type="hidden" name="customer_ids[]" value="{{ $customer->id }}" form="bulkProspectSendForm"></li>
            @endforeach
        </ul>

        <form method="POST" action="{{ route('admin.crm.marketplace.prospects.bulk_follow_up.send') }}" id="bulkProspectSendForm" onsubmit="return confirm('Kirim pesan WhatsApp ke {{ $customers->count() }} customer melalui Fonnte?');">
            @csrf
            <div class="wa-bulk-field">
                <label class="wa-bulk-label" for="template_key">Template pesan</label>
                <select id="template_key" name="template_key" class="form-select">
                    <option value="">Pesan custom</option>
                    @foreach($templates as $template)<option value="{{ $template->key }}">{{ $template->name }}</option>@endforeach
                </select>
                <div class="wa-bulk-help">Gunakan <code>{customer_name}</code> agar nama tiap customer otomatis disesuaikan.</div>
            </div>
            <div class="wa-bulk-field">
                <label class="wa-bulk-label" for="message">Isi pesan</label>
                <textarea id="message" name="message" class="form-control wa-bulk-message" maxlength="4000" required>{{ $draftMessage }}</textarea>
                <div id="messageCount" class="wa-bulk-help">{{ strlen($draftMessage) }}/4000</div>
            </div>
            <div class="wa-bulk-actions">
                <a href="{{ $contextUrl }}" class="wa-bulk-btn wa-bulk-secondary"><i class="bi bi-x-lg"></i>Batal</a>
                <button type="submit" class="wa-bulk-btn wa-bulk-primary" @disabled(!$isConfigured)><i class="bi bi-whatsapp"></i>Kirim bulk lewat Fonnte</button>
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
    function updateCount() { if (counter && message) counter.textContent = message.value.length + '/4000'; }
    select?.addEventListener('change', function () { message.value = this.value && bodies[this.value] !== undefined ? bodies[this.value] : ''; updateCount(); });
    message?.addEventListener('input', updateCount);
});
</script>
@endsection
