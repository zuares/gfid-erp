@extends('layouts.app')

@section('title', 'WhatsApp Center')

@push('head')
<style>
.wa-center{max-width:1280px;margin:0 auto;padding:1rem .75rem 3rem}
.wa-hero{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1rem}
.wa-title{font-size:1.35rem;font-weight:950;color:#0f172a;margin:0}
.wa-subtitle{color:#64748b;font-size:.84rem;margin:.25rem 0 0}
.wa-link{color:#166534;text-decoration:none;font-weight:800;font-size:.82rem}
.wa-grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,.95fr);gap:1rem;align-items:start}
.wa-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1rem 1.1rem;box-shadow:0 4px 16px rgba(15,23,42,.03)}
.wa-card + .wa-card{margin-top:1rem}
.wa-card-head{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:.8rem}
.wa-card-title{font-size:.95rem;font-weight:950;color:#0f172a}
.wa-card-sub{font-size:.78rem;color:#64748b;margin-top:.15rem}
.wa-label{display:block;margin-bottom:.35rem;font-size:.76rem;font-weight:900;color:#475569}
.wa-field{margin-bottom:.75rem}
.wa-field .form-control,.wa-field .form-select{border-radius:10px;border-color:#e2e8f0;box-shadow:none;font-size:.85rem}
.wa-textarea{min-height:150px;resize:vertical}
.wa-btn{display:inline-flex;align-items:center;gap:.4rem;min-height:36px;padding:.4rem .8rem;border:1px solid #166534;border-radius:10px;background:#15803d;color:#fff;font-size:.8rem;font-weight:900;cursor:pointer}
.wa-btn:hover{background:#166534;color:#fff}
.wa-btn-secondary{background:#fff;border-color:#cbd5e1;color:#475569}
.wa-btn-secondary:hover{background:#f8fafc;color:#0f172a}
.wa-alert{border-radius:12px;padding:.7rem .9rem;font-size:.82rem;margin-bottom:1rem}
.wa-alert-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
.wa-alert-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.wa-table-wrap{overflow:auto}
.wa-table{width:100%;border-collapse:collapse;font-size:.8rem}
.wa-table th,.wa-table td{padding:.62rem .45rem;border-bottom:1px solid #f1f5f9;vertical-align:top;text-align:left}
.wa-table th{font-size:.7rem;text-transform:uppercase;color:#94a3b8;letter-spacing:.04em;white-space:nowrap}
.wa-message{white-space:pre-wrap;min-width:260px;max-width:480px;color:#475569;line-height:1.45}
.wa-meta{color:#64748b;font-size:.72rem;line-height:1.5}
.wa-badge{display:inline-flex;align-items:center;border-radius:999px;padding:.18rem .5rem;font-size:.68rem;font-weight:900}
.wa-badge-sent{background:#dcfce7;color:#166534}.wa-badge-failed{background:#fee2e2;color:#991b1b}.wa-badge-pending{background:#fef3c7;color:#92400e}
.wa-filter{display:flex;gap:.45rem;flex-wrap:wrap;align-items:center}
.wa-filter .form-select{font-size:.78rem;border-radius:9px;min-width:120px}
.wa-template{border:1px solid #e2e8f0;border-radius:12px;padding:.75rem;margin-bottom:.65rem}
.wa-template:last-child{margin-bottom:0}
.wa-template-head{display:flex;align-items:center;justify-content:space-between;gap:.5rem;margin-bottom:.55rem}
.wa-template-key{font:700 .72rem ui-monospace,SFMono-Regular,Menlo,monospace;color:#64748b}
.wa-template-body{white-space:pre-wrap;color:#475569;font-size:.78rem;background:#f8fafc;border-radius:8px;padding:.55rem;max-height:120px;overflow:auto}
.wa-help{font-size:.72rem;color:#94a3b8;margin-top:.25rem}
@media(max-width:900px){.wa-grid{grid-template-columns:1fr}.wa-hero{display:block}.wa-hero .wa-link{display:inline-block;margin-top:.65rem}}
</style>
@endpush

@section('content')
<div class="wa-center">
    <div class="wa-hero">
        <div>
            <h1 class="wa-title"><i class="bi bi-whatsapp" style="color:#25d366"></i> WhatsApp Center</h1>
            <p class="wa-subtitle">Kirim pesan operasional dan pantau riwayat komunikasi dari semua modul.</p>
        </div>
        <a class="wa-link" href="{{ route('settings.whatsapp.index') }}"><i class="bi bi-gear"></i> Pengaturan koneksi</a>
    </div>

    @if(session('success'))
        <div class="wa-alert wa-alert-ok">✅ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="wa-alert wa-alert-error">⚠️ {{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="wa-alert wa-alert-error">
            @foreach($errors->all() as $error)<div>⚠️ {{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="wa-grid">
        <div>
            <div class="wa-card">
                <div class="wa-card-head">
                    <div>
                        <div class="wa-card-title">📤 Kirim pesan</div>
                        <div class="wa-card-sub">Pesan ini akan tercatat sebagai pesan manual.</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('whatsapp.messages.send') }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="wa-field">
                                <label class="wa-label" for="recipient_phone">Nomor tujuan</label>
                                <input id="recipient_phone" name="recipient_phone" class="form-control" value="{{ old('recipient_phone') }}" placeholder="628xxxxxxxxxx" required>
                                <div class="wa-help">Format 08... juga diterima.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="wa-field">
                                <label class="wa-label" for="recipient_name">Nama penerima (opsional)</label>
                                <input id="recipient_name" name="recipient_name" class="form-control" value="{{ old('recipient_name') }}" placeholder="Nama supplier/karyawan/customer">
                            </div>
                        </div>
                    </div>
                    <div class="wa-field">
                        <label class="wa-label" for="template_key">Gunakan template (opsional)</label>
                        <select id="template_key" name="template_key" class="form-select">
                            <option value="">Pesan manual</option>
                            @foreach($activeTemplates as $template)
                                <option value="{{ $template->key }}" @selected(old('template_key') === $template->key)>{{ $template->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="wa-field">
                        <label class="wa-label" for="message">Isi pesan</label>
                        <textarea id="message" name="message" class="form-control wa-textarea" maxlength="4000" required>{{ old('message') }}</textarea>
                        <div class="wa-help">Placeholder template akan dimasukkan untuk kemudian disesuaikan sebelum dikirim.</div>
                    </div>
                    <button class="wa-btn" type="submit"><i class="bi bi-send"></i> Kirim WhatsApp</button>
                </form>
            </div>

            <div class="wa-card">
                <div class="wa-card-head">
                    <div>
                        <div class="wa-card-title">🧾 Riwayat pesan keluar</div>
                        <div class="wa-card-sub">Semua pengiriman dari test koneksi, PO, dan pesan manual.</div>
                    </div>
                    <form method="GET" action="{{ route('whatsapp.index') }}" class="wa-filter">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua status</option>
                            @foreach(['sent' => 'Terkirim', 'failed' => 'Gagal', 'pending' => 'Pending'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select name="module" class="form-select form-select-sm">
                            <option value="">Semua modul</option>
                            @foreach($modules as $module)
                                <option value="{{ $module }}" @selected(request('module') === $module)>{{ ucfirst($module) }}</option>
                            @endforeach
                        </select>
                        <button class="wa-btn wa-btn-secondary" type="submit">Filter</button>
                    </form>
                </div>
                <div class="wa-table-wrap">
                    <table class="wa-table">
                        <thead><tr><th>Tujuan</th><th>Pesan</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @forelse($messages as $messageLog)
                            <tr>
                                <td>
                                    <strong>{{ $messageLog->recipient_name ?: $messageLog->recipient_phone }}</strong>
                                    @if($messageLog->recipient_name)<div class="wa-meta">{{ $messageLog->recipient_phone }}</div>@endif
                                    <div class="wa-meta">{{ $messageLog->created_at?->format('d/m/Y H:i') }}</div>
                                </td>
                                <td>
                                    <div class="wa-message">{{ $messageLog->message }}</div>
                                    <div class="wa-meta">{{ $messageLog->module ?: 'manual' }}{{ $messageLog->reference_label ? ' · ' . $messageLog->reference_label : '' }}</div>
                                    @if($messageLog->error_message)<div class="text-danger small mt-1">{{ $messageLog->error_message }}</div>@endif
                                </td>
                                <td>
                                    <span class="wa-badge wa-badge-{{ $messageLog->status }}">{{ strtoupper($messageLog->status) }}</span>
                                </td>
                                <td>
                                    @if($messageLog->isFailed())
                                        <form method="POST" action="{{ route('whatsapp.messages.resend', $messageLog) }}" onsubmit="return confirm('Kirim ulang pesan ini?');">
                                            @csrf
                                            <button class="wa-btn wa-btn-secondary" type="submit" title="Kirim ulang"><i class="bi bi-arrow-repeat"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada riwayat pesan.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($messages->hasPages())
                    <div class="mt-3">{{ $messages->links() }}</div>
                @endif
            </div>
        </div>

        <div>
            <div class="wa-card">
                <div class="wa-card-head">
                    <div>
                        <div class="wa-card-title">🧩 Template pesan</div>
                        <div class="wa-card-sub">Gunakan placeholder seperti <code>{supplier_name}</code> sesuai kebutuhan modul.</div>
                    </div>
                </div>
                @forelse($templates as $template)
                    <div class="wa-template">
                        <div class="wa-template-head">
                            <div>
                                <strong>{{ $template->name }}</strong>
                                <div class="wa-template-key">{{ $template->key }}</div>
                            </div>
                            <span class="wa-badge {{ $template->is_active ? 'wa-badge-sent' : 'wa-badge-failed' }}">{{ $template->is_active ? 'AKTIF' : 'NONAKTIF' }}</span>
                        </div>
                        <div class="wa-template-body">{{ $template->body }}</div>
                        <details class="mt-2">
                            <summary class="small fw-bold text-muted" style="cursor:pointer">Edit template</summary>
                            <form method="POST" action="{{ route('whatsapp.templates.update', $template) }}" class="mt-2">
                                @csrf @method('PUT')
                                <input name="name" class="form-control form-control-sm mb-2" value="{{ $template->name }}" required>
                                <textarea name="body" class="form-control form-control-sm mb-2" rows="6" required>{{ $template->body }}</textarea>
                                <textarea name="description" class="form-control form-control-sm mb-2" rows="2" placeholder="Keterangan">{{ $template->description }}</textarea>
                                <label class="small text-muted mb-2"><input type="checkbox" name="is_active" value="1" @checked($template->is_active)> Aktif</label><br>
                                <button class="wa-btn wa-btn-secondary" type="submit">Simpan perubahan</button>
                            </form>
                        </details>
                    </div>
                @empty
                    <div class="text-muted small">Belum ada template.</div>
                @endforelse
            </div>

            <div class="wa-card">
                <div class="wa-card-head">
                    <div>
                        <div class="wa-card-title">➕ Tambah template</div>
                        <div class="wa-card-sub">Template baru bisa dipakai oleh modul lain tanpa mengubah kode pengiriman.</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('whatsapp.templates.store') }}">
                    @csrf
                    <div class="wa-field"><label class="wa-label">Key</label><input name="key" class="form-control" value="{{ old('key') }}" placeholder="shipment_customer_update" required><div class="wa-help">Huruf kecil, angka, titik, garis bawah, atau strip.</div></div>
                    <div class="wa-field"><label class="wa-label">Nama</label><input name="name" class="form-control" value="{{ old('name') }}" placeholder="Update status pengiriman" required></div>
                    <div class="wa-field"><label class="wa-label">Isi pesan</label><textarea name="body" class="form-control" rows="5" placeholder="Halo {customer_name}, ..." required>{{ old('body') }}</textarea></div>
                    <div class="wa-field"><label class="wa-label">Keterangan</label><textarea name="description" class="form-control" rows="2" placeholder="Dipakai untuk ...">{{ old('description') }}</textarea></div>
                    <button class="wa-btn" type="submit">Tambah template</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('template_key');
    const message = document.getElementById('message');
    const bodies = @json($activeTemplates->mapWithKeys(fn ($template) => [$template->key => $template->body]));

    if (!select || !message) return;
    select.addEventListener('change', function () {
        if (this.value && bodies[this.value] !== undefined) {
            message.value = bodies[this.value];
        }
    });
});
</script>
@endsection
