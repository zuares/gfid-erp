@extends('layouts.app')

@section('title', 'Broadcast WhatsApp')

@push('head')
<style>
.wa-broadcast{max-width:1360px;margin:0 auto;padding:1rem .75rem 3rem;color:#0f172a}
.wa-broadcast-hero{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.15rem}
.wa-broadcast-kicker{display:flex;align-items:center;gap:.45rem;color:#15803d;font-size:.75rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.35rem}
.wa-broadcast-title{font-size:1.55rem;font-weight:950;line-height:1.15;margin:0}
.wa-broadcast-subtitle{color:#64748b;font-size:.85rem;margin:.35rem 0 0;max-width:650px}
.wa-broadcast-back{display:inline-flex;align-items:center;gap:.4rem;color:#475569;text-decoration:none;font-size:.8rem;font-weight:800;white-space:nowrap}
.wa-broadcast-back:hover{color:#166534}
.wa-broadcast-grid{display:grid;grid-template-columns:minmax(0,1.12fr) minmax(340px,.88fr);gap:1rem;align-items:start}
.wa-broadcast-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 8px 24px rgba(15,23,42,.04);overflow:hidden}
.wa-broadcast-card-head{padding:1rem 1.1rem .85rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem}
.wa-broadcast-card-title{font-size:.95rem;font-weight:950;margin:0}
.wa-broadcast-card-subtitle{color:#64748b;font-size:.76rem;margin:.2rem 0 0}
.wa-step{width:28px;height:28px;display:grid;place-items:center;border-radius:9px;background:#dcfce7;color:#166534;font-size:.78rem;font-weight:950;flex:0 0 auto}
.wa-broadcast-card-body{padding:1rem 1.1rem}
.wa-manual-test{border:1px solid #bbf7d0;border-radius:13px;background:#f0fdf4;padding:.8rem;margin-bottom:1rem}.wa-manual-test-head{display:flex;align-items:flex-start;gap:.55rem;margin-bottom:.65rem}.wa-manual-test-icon{width:28px;height:28px;display:grid;place-items:center;border-radius:9px;background:#25d366;color:#fff;font-size:.78rem;flex:0 0 auto}.wa-manual-test-title{font-size:.78rem;font-weight:950;color:#166534}.wa-manual-test-subtitle{font-size:.69rem;color:#4d7c5a;margin-top:.1rem}.wa-manual-test .form-control{border-color:#bbf7d0;background:#fff}.wa-manual-test .form-control:focus{border-color:#22c55e;box-shadow:0 0 0 .2rem rgba(34,197,94,.12)}
.wa-manual-input{display:flex;gap:.45rem}.wa-manual-input .form-control{min-width:0}.wa-manual-add{display:inline-flex;align-items:center;gap:.3rem;border:1px solid #16a34a;border-radius:10px;background:#16a34a;color:#fff;padding:.4rem .7rem;font-size:.75rem;font-weight:900;white-space:nowrap}.wa-manual-add:hover{background:#15803d;color:#fff}.wa-manual-chips{display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.5rem}.wa-manual-chip{display:inline-flex;align-items:center;gap:.35rem;border:1px solid #bbf7d0;border-radius:999px;background:#fff;color:#166534;padding:.25rem .35rem .25rem .55rem;font-size:.72rem;font-weight:850}.wa-manual-chip button{width:19px;height:19px;display:grid;place-items:center;border:0;border-radius:50%;background:#dcfce7;color:#166534;padding:0;cursor:pointer;font-size:.7rem}.wa-manual-chip button:hover{background:#bbf7d0}.wa-manual-help{font-size:.68rem;color:#4d7c5a;margin-top:.4rem}
.wa-audience-tabs{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;margin-bottom:.85rem}
.wa-audience-tab{position:relative;display:block;padding:.65rem .7rem;border:1px solid #e2e8f0;border-radius:12px;color:#475569;text-decoration:none;background:#fff;transition:.15s ease}
.wa-audience-tab:hover{border-color:#86efac;background:#f0fdf4;color:#166534}
.wa-audience-tab.active{border-color:#22c55e;background:#f0fdf4;color:#166534;box-shadow:0 0 0 2px rgba(34,197,94,.1)}
.wa-audience-tab strong{display:block;font-size:.76rem;font-weight:900}.wa-audience-tab span{display:block;font-size:.67rem;color:#94a3b8;margin-top:.18rem}.wa-audience-tab.active span{color:#4ade80}
.wa-search{display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem}.wa-search .form-control{border-radius:10px;border-color:#e2e8f0;font-size:.82rem;box-shadow:none}.wa-search .btn{border-radius:10px;font-size:.78rem;font-weight:800}
.wa-list-toolbar{display:flex;align-items:center;justify-content:space-between;gap:.5rem;color:#64748b;font-size:.74rem;margin-bottom:.55rem}.wa-list-toolbar strong{color:#334155}
.wa-recipient-list{border:1px solid #e2e8f0;border-radius:13px;overflow:hidden;max-height:470px;overflow-y:auto}
.wa-recipient{display:flex;align-items:center;gap:.7rem;padding:.68rem .75rem;border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .15s ease}.wa-recipient:last-child{border-bottom:0}.wa-recipient:hover{background:#f8fafc}.wa-recipient:has(input:checked){background:#f0fdf4}
.wa-recipient input{accent-color:#16a34a;width:16px;height:16px;flex:0 0 auto}.wa-recipient-avatar{width:34px;height:34px;display:grid;place-items:center;border-radius:11px;background:#dcfce7;color:#15803d;font-size:.75rem;font-weight:950;flex:0 0 auto}.wa-recipient-main{min-width:0;flex:1}.wa-recipient-name{font-size:.8rem;font-weight:900;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.wa-recipient-meta{font-size:.7rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.wa-recipient-order{font-size:.66rem;color:#94a3b8;text-align:right;white-space:nowrap}
.wa-empty{padding:2rem 1rem;text-align:center;color:#94a3b8;font-size:.8rem}.wa-empty i{display:block;font-size:1.8rem;margin-bottom:.4rem;color:#cbd5e1}
.wa-pagination{padding-top:.75rem}.wa-pagination nav{display:flex;justify-content:center}.wa-pagination .pagination{margin:0}.wa-pagination .page-link{font-size:.72rem;border-radius:8px!important;margin:0 .12rem;border-color:#e2e8f0;color:#475569}.wa-pagination .active .page-link{background:#15803d;border-color:#15803d}
.wa-field{margin-bottom:1rem}.wa-label{display:block;color:#475569;font-size:.76rem;font-weight:900;margin-bottom:.38rem}.wa-field .form-control,.wa-field .form-select{border-radius:11px;border-color:#e2e8f0;box-shadow:none;font-size:.84rem}.wa-field .form-control:focus,.wa-field .form-select:focus{border-color:#22c55e;box-shadow:0 0 0 .2rem rgba(34,197,94,.12)}
.wa-message{min-height:230px;resize:vertical;line-height:1.55}.wa-message-meta{display:flex;justify-content:space-between;gap:.5rem;color:#94a3b8;font-size:.7rem;margin-top:.3rem}.wa-template-help{font-size:.7rem;color:#64748b;margin-top:.35rem}.wa-template-help code{color:#166534;background:#f0fdf4;padding:.12rem .3rem;border-radius:5px}
.wa-preview{border:1px solid #dcfce7;border-radius:14px;background:#f0fdf4;padding:.8rem;margin-top:.9rem}.wa-preview-label{font-size:.68rem;color:#15803d;font-weight:900;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.45rem}.wa-preview-bubble{background:#fff;border-radius:4px 13px 13px 13px;padding:.7rem .75rem;color:#334155;font-size:.78rem;line-height:1.5;white-space:pre-wrap;min-height:64px;box-shadow:0 2px 8px rgba(22,101,52,.06)}
.wa-summary{display:grid;grid-template-columns:repeat(2,1fr);gap:.55rem;margin:.95rem 0}.wa-summary-item{border:1px solid #e2e8f0;border-radius:11px;padding:.65rem .7rem;background:#f8fafc}.wa-summary-value{font-size:1.1rem;font-weight:950;color:#166534}.wa-summary-label{font-size:.68rem;color:#64748b;margin-top:.1rem}
.wa-alert{border-radius:12px;padding:.7rem .9rem;font-size:.8rem;margin-bottom:1rem}.wa-alert-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}.wa-alert-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}.wa-connection{display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;padding:.3rem .55rem;font-size:.68rem;font-weight:900;white-space:nowrap}.wa-connection.ready{background:#dcfce7;color:#166534}.wa-connection.off{background:#fef3c7;color:#92400e}
.wa-send{width:100%;display:inline-flex;justify-content:center;align-items:center;gap:.45rem;min-height:42px;border:1px solid #166534;border-radius:11px;background:#15803d;color:#fff;font-size:.83rem;font-weight:950;cursor:pointer}.wa-send:hover{background:#166534}.wa-send:disabled{opacity:.55;cursor:not-allowed}.wa-cancel{display:block;text-align:center;color:#64748b;text-decoration:none;font-size:.75rem;font-weight:800;margin-top:.65rem}.wa-cancel:hover{color:#166534}
.wa-history{margin-top:1rem}.wa-history-row{display:flex;align-items:center;gap:.65rem;padding:.65rem 0;border-bottom:1px solid #f1f5f9}.wa-history-row:last-child{border-bottom:0}.wa-history-icon{width:28px;height:28px;display:grid;place-items:center;border-radius:9px;background:#dcfce7;color:#15803d;font-size:.74rem}.wa-history-main{min-width:0;flex:1}.wa-history-name{font-size:.76rem;font-weight:900;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.wa-history-meta{font-size:.68rem;color:#94a3b8}.wa-history-status{font-size:.64rem;font-weight:900;border-radius:999px;padding:.2rem .4rem}.wa-history-status.sent{background:#dcfce7;color:#166534}.wa-history-status.failed{background:#fee2e2;color:#991b1b}.wa-history-status.pending{background:#fef3c7;color:#92400e}
@media(max-width:960px){.wa-broadcast-grid{grid-template-columns:1fr}.wa-broadcast-hero{display:block}.wa-broadcast-back{margin-top:.75rem}.wa-recipient-list{max-height:360px}}
@media(max-width:560px){.wa-audience-tabs{grid-template-columns:1fr}.wa-broadcast-title{font-size:1.3rem}.wa-broadcast-card-head,.wa-broadcast-card-body{padding-left:.85rem;padding-right:.85rem}}
</style>
@endpush

@section('content')
@php
    $selectedIds = collect(old('customer_ids', []))->map(fn ($id) => (int) $id)->all();
    $manualPhones = collect(old('manual_phones', []))->filter()->values();
    $firstRecipient = $recipients->first();
@endphp
<div class="wa-broadcast">
    <div class="wa-broadcast-hero">
        <div>
            <div class="wa-broadcast-kicker"><i class="bi bi-megaphone-fill"></i> WhatsApp Campaign</div>
            <h1 class="wa-broadcast-title">Buat broadcast baru</h1>
            <p class="wa-broadcast-subtitle">Pilih customer, tulis pesan sekali, lalu kirim dengan nama penerima yang otomatis dipersonalisasi.</p>
        </div>
        <div>
            <a class="wa-broadcast-back" href="{{ route('whatsapp.index') }}"><i class="bi bi-arrow-left"></i> Kembali ke WhatsApp Center</a>
            <div class="text-end mt-2"><span class="wa-connection {{ $isConfigured ? 'ready' : 'off' }}"><i class="bi {{ $isConfigured ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' }}"></i>{{ $isConfigured ? 'Koneksi siap' : 'Koneksi belum diatur' }}</span></div>
        </div>
    </div>

    @if(session('success'))<div class="wa-alert wa-alert-ok"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}</div>@endif
    @if(session('error'))<div class="wa-alert wa-alert-error"><i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}</div>@endif
    @if(isset($errors) && $errors->any())<div class="wa-alert wa-alert-error">@foreach($errors->all() as $error)<div><i class="bi bi-exclamation-triangle me-1"></i>{{ $error }}</div>@endforeach</div>@endif

    <form method="POST" action="{{ route('whatsapp.broadcast.send') }}" id="broadcastForm" onsubmit="return confirmBroadcast();">
        @csrf
        <div class="wa-broadcast-grid">
            <section class="wa-broadcast-card">
                <div class="wa-broadcast-card-head"><div><h2 class="wa-broadcast-card-title">Pilih penerima</h2><p class="wa-broadcast-card-subtitle">Hanya customer dengan nomor WhatsApp yang ditampilkan.</p></div><div class="wa-step">1</div></div>
                <div class="wa-broadcast-card-body">
                    <div class="wa-manual-test">
                        <div class="wa-manual-test-head"><span class="wa-manual-test-icon"><i class="bi bi-phone"></i></span><div><div class="wa-manual-test-title">Testing cepat dengan nomor manual</div><div class="wa-manual-test-subtitle">Tambahkan satu atau beberapa nomor. Nomor manual bisa digabung dengan customer terpilih.</div></div></div>
                        <div class="wa-field mb-2"><label class="wa-label" for="manualPhoneInput">Nomor WhatsApp manual</label><div class="wa-manual-input"><input class="form-control" type="text" id="manualPhoneInput" placeholder="Ketik nomor lalu tekan Enter"><button class="wa-manual-add" type="button" id="addManualPhone"><i class="bi bi-plus-lg"></i>Tambah</button></div><div id="manualPhoneChips" class="wa-manual-chips"></div><div class="wa-manual-help"><i class="bi bi-lightbulb me-1"></i>Paste banyak nomor sekaligus, pisahkan dengan Enter, koma, atau spasi.</div></div>
                        <div class="row g-2"><div class="col-md-7"><label class="wa-label" for="manual_name">Nama label nomor manual <span class="fw-normal text-secondary">(opsional)</span></label><input class="form-control" type="text" id="manual_name" name="manual_name" value="{{ old('manual_name') }}" placeholder="Test Recipient"></div><div class="col-md-5 d-flex align-items-end"><div class="small text-secondary pb-2"><span id="manualPhoneCount">0</span> nomor manual ditambahkan</div></div></div>
                    </div>
                    <div class="wa-audience-tabs">
                        <a class="wa-audience-tab {{ $audience === 'all' ? 'active' : '' }}" href="{{ route('whatsapp.broadcast', ['audience' => 'all', 'q' => $search]) }}"><strong>Semua customer</strong><span>Database customer</span></a>
                        <a class="wa-audience-tab {{ $audience === 'recent_90' ? 'active' : '' }}" href="{{ route('whatsapp.broadcast', ['audience' => 'recent_90', 'q' => $search]) }}"><strong>Aktif 90 hari</strong><span>Order terbaru</span></a>
                        <a class="wa-audience-tab {{ $audience === 'repeat' ? 'active' : '' }}" href="{{ route('whatsapp.broadcast', ['audience' => 'repeat', 'q' => $search]) }}"><strong>Repeat buyer</strong><span>Min. 2 order</span></a>
                    </div>

                    <div class="wa-search">
                        <i class="bi bi-search text-secondary"></i>
                        <input class="form-control" type="search" id="recipientSearch" value="{{ $search }}" placeholder="Cari nama, nomor, atau email...">
                        <button class="btn btn-light border" type="button" id="searchRecipients">Cari</button>
                    </div>

                    <div class="wa-list-toolbar"><span><strong>{{ number_format($recipientCount) }}</strong> penerima tersedia</span><button type="button" class="btn btn-link p-0 text-success fw-bold" id="selectVisible">Pilih semua di halaman</button></div>
                    <div class="wa-recipient-list" id="recipientList">
                        @forelse($recipients as $recipient)
                            <label class="wa-recipient">
                                <input type="checkbox" name="customer_ids[]" value="{{ $recipient->id }}" @checked(in_array((int) $recipient->id, $selectedIds, true))>
                                <span class="wa-recipient-avatar">{{ strtoupper(mb_substr(trim($recipient->name ?: 'C'), 0, 1)) }}</span>
                                <span class="wa-recipient-main"><span class="wa-recipient-name">{{ $recipient->name ?: 'Customer' }}</span><span class="wa-recipient-meta">{{ $recipient->phone }}{{ $recipient->email ? ' · ' . $recipient->email : '' }}</span></span>
                                <span class="wa-recipient-order">{{ (int) $recipient->marketplace_order_count }} order</span>
                            </label>
                        @empty
                            <div class="wa-empty"><i class="bi bi-person-x"></i>Tidak ada customer yang sesuai filter.</div>
                        @endforelse
                    </div>
                    @if($recipients->hasPages())<div class="wa-pagination">{{ $recipients->links() }}</div>@endif
                    <div class="small text-secondary mt-2"><i class="bi bi-info-circle me-1"></i>Maksimal 100 penerima per broadcast. Kamu bisa mengirim campaign berikutnya setelah ini.</div>
                </div>
            </section>

            <aside>
                <section class="wa-broadcast-card">
                    <div class="wa-broadcast-card-head"><div><h2 class="wa-broadcast-card-title">Tulis pesan</h2><p class="wa-broadcast-card-subtitle">Gunakan template atau tulis pesan custom.</p></div><div class="wa-step">2</div></div>
                    <div class="wa-broadcast-card-body">
                        <div class="wa-field"><label class="wa-label" for="template_key">Template pesan <span class="fw-normal text-secondary">(opsional)</span></label><select id="template_key" name="template_key" class="form-select"><option value="">Pesan custom</option>@foreach($templates as $template)<option value="{{ $template->key }}" @selected(old('template_key') === $template->key)>{{ $template->name }}</option>@endforeach</select><div class="wa-template-help">Nama penerima akan diisi otomatis dari <code>{customer_name}</code>.</div></div>
                        <div class="wa-field"><label class="wa-label" for="message">Isi pesan</label><textarea id="message" name="message" class="form-control wa-message" maxlength="4000" required>{{ old('message') }}</textarea><div class="wa-message-meta"><span>Pesan dipersonalisasi per penerima</span><span id="messageCount">0/4000</span></div></div>
                        <div class="wa-preview"><div class="wa-preview-label"><i class="bi bi-eye me-1"></i>Preview pesan</div><div class="wa-preview-bubble" id="previewMessage">Tulis pesan untuk melihat preview.</div></div>
                        <div class="wa-summary"><div class="wa-summary-item"><div class="wa-summary-value" id="selectedCount">{{ count($selectedIds) }}</div><div class="wa-summary-label">Penerima dipilih</div></div><div class="wa-summary-item"><div class="wa-summary-value">100</div><div class="wa-summary-label">Batas per kirim</div></div></div>
                        <button class="wa-send" type="submit" id="sendBroadcast" @disabled(!$isConfigured)><i class="bi bi-send-fill"></i>Kirim broadcast</button>
                        @if(!$isConfigured)<div class="small text-warning mt-2"><i class="bi bi-exclamation-triangle me-1"></i>Atur token Fonnte di <a href="{{ route('settings.whatsapp.index') }}" class="text-warning fw-bold">Pengaturan WhatsApp</a>.</div>@endif
                        <a class="wa-cancel" href="{{ route('whatsapp.index') }}">Batal dan kembali</a>
                    </div>
                </section>

                <section class="wa-broadcast-card wa-history">
                    <div class="wa-broadcast-card-head"><div><h2 class="wa-broadcast-card-title">Aktivitas terakhir</h2><p class="wa-broadcast-card-subtitle">Log broadcast terbaru.</p></div><i class="bi bi-clock-history text-secondary"></i></div>
                    <div class="wa-broadcast-card-body py-2">
                        @forelse($broadcastMessages as $broadcastMessage)
                            <div class="wa-history-row"><div class="wa-history-icon"><i class="bi bi-whatsapp"></i></div><div class="wa-history-main"><div class="wa-history-name">{{ $broadcastMessage->recipient_name ?: $broadcastMessage->recipient_phone }}</div><div class="wa-history-meta">{{ $broadcastMessage->created_at?->diffForHumans() }} · {{ \Illuminate\Support\Str::limit($broadcastMessage->message, 42) }}</div></div><span class="wa-history-status {{ $broadcastMessage->status }}">{{ strtoupper($broadcastMessage->status) }}</span></div>
                        @empty
                            <div class="text-secondary small py-3">Belum ada aktivitas broadcast.</div>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const message = document.getElementById('message');
    const counter = document.getElementById('messageCount');
    const preview = document.getElementById('previewMessage');
    const selectedCount = document.getElementById('selectedCount');
    const list = document.getElementById('recipientList');
    const selectVisible = document.getElementById('selectVisible');
    const manualPhoneInput = document.getElementById('manualPhoneInput');
    const addManualPhone = document.getElementById('addManualPhone');
    const manualPhoneChips = document.getElementById('manualPhoneChips');
    const manualPhoneCount = document.getElementById('manualPhoneCount');
    const recipientSearch = document.getElementById('recipientSearch');
    const searchRecipients = document.getElementById('searchRecipients');
    const maxRecipients = 100;
    let manualPhones = @json($manualPhones->values());
    const bodies = @json($templates->mapWithKeys(fn ($template) => [$template->key => $template->body]));

    function digits(value) { return String(value || '').replace(/\D+/g, ''); }
    function renderManualPhones() {
        manualPhones = [...new Set(manualPhones.map(digits).filter(phone => phone.length >= 8))].slice(0, maxRecipients);
        if (manualPhoneChips) {
            manualPhoneChips.innerHTML = manualPhones.map((phone, index) => '<span class="wa-manual-chip">' + phone + '<button type="button" data-remove-manual="' + index + '" aria-label="Hapus nomor ' + phone + '"><i class="bi bi-x"></i></button><input type="hidden" name="manual_phones[]" value="' + phone + '"></span>').join('');
        }
        if (manualPhoneCount) manualPhoneCount.textContent = manualPhones.length;
        updateSelected();
    }
    function addManualPhones(value) {
        const incoming = String(value || '').split(/[\s,;]+/).map(digits).filter(phone => phone.length >= 8);
        const available = Math.max(0, maxRecipients - document.querySelectorAll('input[name="customer_ids[]"]:checked').length);
        manualPhones = [...manualPhones, ...incoming].slice(0, available);
        if (manualPhoneInput) manualPhoneInput.value = '';
        renderManualPhones();
    }

    function updateMessage() {
        const value = message?.value || '';
        if (counter) counter.textContent = value.length + '/4000';
        if (preview) preview.textContent = value ? value.replaceAll('{customer_name}', @json($firstRecipient?->name ?: 'Customer')) : 'Tulis pesan untuk melihat preview.';
    }
    function updateSelected() {
        const checked = list ? list.querySelectorAll('input[name="customer_ids[]"]:checked') : [];
        if (selectedCount) selectedCount.textContent = checked.length + manualPhones.length;
        if (selectVisible) selectVisible.textContent = checked.length === (list?.querySelectorAll('input[name="customer_ids[]"]').length || 0) ? 'Batalkan pilihan halaman' : 'Pilih semua di halaman';
    }
    document.querySelectorAll('input[name="customer_ids[]"]').forEach(function (input) {
        input.addEventListener('change', function () {
            const checked = list.querySelectorAll('input[name="customer_ids[]"]:checked').length;
            if (checked > maxRecipients) { this.checked = false; alert('Maksimal 100 penerima per broadcast.'); }
            updateSelected();
        });
    });
    selectVisible?.addEventListener('click', function () {
        const inputs = [...list.querySelectorAll('input[name="customer_ids[]"]')];
        const allChecked = inputs.length > 0 && inputs.every(input => input.checked);
        if (!allChecked && inputs.filter(input => !input.checked).length > maxRecipients - list.querySelectorAll('input[name="customer_ids[]"]:checked').length - manualPhones.length) { alert('Maksimal 100 penerima per broadcast.'); return; }
        inputs.forEach(input => input.checked = !allChecked);
        updateSelected();
    });
    addManualPhone?.addEventListener('click', function () { addManualPhones(manualPhoneInput?.value); });
    manualPhoneInput?.addEventListener('keydown', function (event) { if (event.key === 'Enter' || event.key === ',') { event.preventDefault(); addManualPhones(this.value); } });
    manualPhoneInput?.addEventListener('paste', function () { setTimeout(() => { if (/[\s,;]/.test(this.value)) addManualPhones(this.value); }, 0); });
    manualPhoneChips?.addEventListener('click', function (event) { const button = event.target.closest('[data-remove-manual]'); if (!button) return; manualPhones.splice(Number(button.dataset.removeManual), 1); renderManualPhones(); });
    document.getElementById('template_key')?.addEventListener('change', function () { if (this.value && bodies[this.value] !== undefined) message.value = bodies[this.value]; updateMessage(); });
    function applySearch() {
        const params = new URLSearchParams({ audience: @json($audience) });
        if (recipientSearch?.value.trim()) params.set('q', recipientSearch.value.trim());
        window.location.href = @json(route('whatsapp.broadcast')) + '?' + params.toString();
    }
    searchRecipients?.addEventListener('click', applySearch);
    recipientSearch?.addEventListener('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); applySearch(); } });
    message?.addEventListener('input', updateMessage);
    renderManualPhones(); updateMessage(); updateSelected();
});
function confirmBroadcast() {
    const count = document.querySelectorAll('input[name="customer_ids[]"]:checked').length;
    const manualCount = document.querySelectorAll('input[name="manual_phones[]"]').length;
    if (!count && !manualCount) { alert('Pilih minimal satu penerima atau tambahkan nomor manual untuk testing.'); return false; }
    const total = count + manualCount;
    return confirm('Kirim broadcast WhatsApp ke ' + total + ' penerima?');
}
</script>
@endsection
