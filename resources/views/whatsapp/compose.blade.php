@extends('layouts.app')

@section('title', 'Kirim WhatsApp - ' . $contextTitle)

@push('head')
<style>
.wa-compose{max-width:1120px;margin:0 auto;padding:1rem .75rem 3rem}
.wa-breadcrumb{display:flex;align-items:center;gap:.45rem;color:#64748b;font-size:.8rem;margin-bottom:1rem}
.wa-breadcrumb a{color:#166534;text-decoration:none;font-weight:800}
.wa-compose-title{font-size:1.35rem;font-weight:950;color:#0f172a;margin:0}
.wa-compose-subtitle{color:#64748b;font-size:.84rem;margin:.25rem 0 1rem}
.wa-compose-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr);gap:1rem;align-items:start}
.wa-compose-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1.1rem 1.2rem;box-shadow:0 4px 16px rgba(15,23,42,.03)}
.wa-compose-card + .wa-compose-card{margin-top:1rem}
.wa-compose-card-title{font-size:.95rem;font-weight:950;color:#0f172a;margin-bottom:.2rem}
.wa-compose-card-subtitle{font-size:.78rem;color:#64748b;margin-bottom:1rem}
.wa-compose-label{display:block;margin-bottom:.35rem;font-size:.76rem;font-weight:900;color:#475569}
.wa-compose-field{margin-bottom:.85rem}
.wa-compose-field .form-control,.wa-compose-field .form-select{border-radius:10px;border-color:#e2e8f0;box-shadow:none;font-size:.85rem}
.wa-compose-message{min-height:280px;resize:vertical;line-height:1.5}
.wa-compose-recipient{display:flex;align-items:center;gap:.7rem;padding:.75rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;margin-bottom:1rem}
.wa-compose-recipient-icon{width:38px;height:38px;display:grid;place-items:center;border-radius:50%;background:#25d366;color:#fff;font-size:1.1rem}
.wa-compose-recipient-name{font-weight:900;color:#166534}
.wa-compose-recipient-phone{font-size:.76rem;color:#64748b}
.wa-compose-actions{display:flex;align-items:center;justify-content:space-between;gap:.6rem;border-top:1px solid #f1f5f9;padding-top:1rem;margin-top:1rem}
.wa-compose-btn{display:inline-flex;align-items:center;gap:.4rem;min-height:38px;padding:.45rem .9rem;border-radius:10px;font-size:.82rem;font-weight:900;text-decoration:none;cursor:pointer}
.wa-compose-btn-primary{border:1px solid #166534;background:#15803d;color:#fff}.wa-compose-btn-primary:hover{background:#166534;color:#fff}
.wa-compose-btn-secondary{border:1px solid #cbd5e1;background:#fff;color:#475569}.wa-compose-btn-secondary:hover{background:#f8fafc;color:#0f172a}
.wa-context-list{display:grid;gap:.65rem;margin:0}
.wa-context-row{display:flex;justify-content:space-between;gap:.75rem;font-size:.8rem;border-bottom:1px solid #f1f5f9;padding-bottom:.55rem}
.wa-context-row:last-child{border-bottom:0;padding-bottom:0}
.wa-context-label{color:#64748b}.wa-context-value{font-weight:850;color:#334155;text-align:right}
.wa-flow{display:grid;gap:.75rem;margin-top:.9rem}
.wa-flow-step{display:flex;gap:.6rem;align-items:flex-start}
.wa-flow-number{width:24px;height:24px;display:grid;place-items:center;border-radius:50%;background:#dcfce7;color:#166534;font-size:.72rem;font-weight:950;flex:none}
.wa-flow-text{font-size:.78rem;color:#475569;line-height:1.4}.wa-flow-text strong{display:block;color:#334155}
.wa-alert{border-radius:12px;padding:.7rem .9rem;font-size:.82rem;margin-bottom:1rem}
.wa-alert-warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
.wa-help{font-size:.72rem;color:#94a3b8;margin-top:.25rem}
@media(max-width:850px){.wa-compose-grid{grid-template-columns:1fr}.wa-compose-actions{align-items:stretch;flex-direction:column-reverse}.wa-compose-btn{justify-content:center;width:100%}}
</style>
@endpush

@section('content')
<div class="wa-compose">
    <div class="wa-breadcrumb">
        <a href="{{ $contextUrl }}"><i class="bi bi-arrow-left"></i> Kembali ke PO</a>
        <span>/</span>
        <a href="{{ route('whatsapp.index') }}">WhatsApp Center</a>
        <span>/</span>
        <span>{{ $contextTitle }}</span>
    </div>

    <h1 class="wa-compose-title"><i class="bi bi-send" style="color:#15803d"></i> Kirim WhatsApp Supplier</h1>
    <p class="wa-compose-subtitle">Periksa dan sesuaikan pesan sebelum dikirim. Pengiriman akan tercatat di WhatsApp Center.</p>

    @if(!$isConfigured)
        <div class="wa-alert wa-alert-warn">⚠️ FONNTE_TOKEN belum dikonfigurasi. Pesan bisa disiapkan, tetapi pengiriman akan gagal sampai koneksi diaktifkan.</div>
    @endif
    @if(session('error'))
        <div class="wa-alert wa-alert-warn">⚠️ {{ session('error') }}</div>
    @endif

    <div class="wa-compose-grid">
        <div class="wa-compose-card">
            <div class="wa-compose-card-title">1. Review pesan</div>
            <div class="wa-compose-card-subtitle">Template sudah diisi berdasarkan data Purchase Order ini.</div>

            <div class="wa-compose-recipient">
                <div class="wa-compose-recipient-icon"><i class="bi bi-whatsapp"></i></div>
                <div>
                    <div class="wa-compose-recipient-name">{{ $draft['recipient_name'] ?: 'Supplier' }}</div>
                    <div class="wa-compose-recipient-phone">{{ $draft['phone'] }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('whatsapp.messages.send') }}" onsubmit="return confirm('Kirim pesan ini ke supplier melalui WhatsApp?');">
                @csrf
                <input type="hidden" name="module" value="{{ $draft['module'] }}">
                <input type="hidden" name="reference_type" value="{{ $draft['reference_type'] }}">
                <input type="hidden" name="reference_id" value="{{ $draft['reference_id'] }}">
                <input type="hidden" name="reference_label" value="{{ $draft['reference_label'] }}">
                <input type="hidden" name="return_to" value="purchase_order">
                <input type="hidden" name="recipient_name" value="{{ $draft['recipient_name'] }}">
                <input type="hidden" name="recipient_phone" value="{{ $draft['phone'] }}">

                <div class="wa-compose-field">
                    <label class="wa-compose-label" for="template_key">Template</label>
                    <select id="template_key" name="template_key" class="form-select">
                        <option value="">Pesan custom</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->key }}" @selected($draft['template_key'] === $template->key)>{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="wa-compose-field">
                    <label class="wa-compose-label" for="message">Isi pesan</label>
                    <textarea id="message" name="message" class="form-control wa-compose-message" maxlength="4000" required>{{ $draft['message'] }}</textarea>
                    <div class="wa-help">Anda boleh mengedit pesan sebelum dikirim.</div>
                </div>

                <div class="wa-compose-actions">
                    <a href="{{ $contextUrl }}" class="wa-compose-btn wa-compose-btn-secondary"><i class="bi bi-x-lg"></i> Batal</a>
                    <button type="submit" class="wa-compose-btn wa-compose-btn-primary"><i class="bi bi-whatsapp"></i> Kirim ke Supplier</button>
                </div>
            </form>
        </div>

        <div>
            <div class="wa-compose-card">
                <div class="wa-compose-card-title">2. Konteks transaksi</div>
                <div class="wa-compose-card-subtitle">Pesan ini terkait dengan dokumen berikut.</div>
                <div class="wa-context-list">
                    <div class="wa-context-row"><span class="wa-context-label">Dokumen</span><span class="wa-context-value">{{ $order->code }}</span></div>
                    <div class="wa-context-row"><span class="wa-context-label">Tanggal</span><span class="wa-context-value">{{ $order->date?->format('d/m/Y') ?? '-' }}</span></div>
                    <div class="wa-context-row"><span class="wa-context-label">Supplier</span><span class="wa-context-value">{{ $order->supplier?->name ?? '-' }}</span></div>
                    <div class="wa-context-row"><span class="wa-context-label">Total</span><span class="wa-context-value">Rp{{ number_format((float) $order->grand_total, 0, ',', '.') }}</span></div>
                </div>
            </div>

            <div class="wa-compose-card">
                <div class="wa-compose-card-title">3. Alur setelah kirim</div>
                <div class="wa-flow">
                    <div class="wa-flow-step"><div class="wa-flow-number">1</div><div class="wa-flow-text"><strong>Fonnte mengirim pesan</strong>Nomor supplier dinormalisasi otomatis.</div></div>
                    <div class="wa-flow-step"><div class="wa-flow-number">2</div><div class="wa-flow-text"><strong>Dicatat di riwayat</strong>Status terkirim atau gagal disimpan.</div></div>
                    <div class="wa-flow-step"><div class="wa-flow-number">3</div><div class="wa-flow-text"><strong>Kembali ke PO</strong>Hasil pengiriman tampil sebagai notifikasi.</div></div>
                </div>
                <a href="{{ route('whatsapp.index') }}" class="wa-compose-btn wa-compose-btn-secondary mt-3"><i class="bi bi-clock-history"></i> Lihat riwayat WhatsApp</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('template_key');
    const message = document.getElementById('message');
    const bodies = @json($templates->mapWithKeys(fn ($template) => [$template->key => $template->body]));
    const variables = @json($draft['variables']);

    function renderTemplate(body) {
        return body.replace(/\{([a-z0-9_]+)\}/gi, function (match, key) {
            return variables[key] !== undefined ? variables[key] : match;
        });
    }

    if (!select || !message) return;
    select.addEventListener('change', function () {
        message.value = this.value && bodies[this.value] !== undefined
            ? renderTemplate(bodies[this.value])
            : '';
    });
});
</script>
@endsection
